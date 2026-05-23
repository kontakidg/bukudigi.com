<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $order->book->title }} — Reader · bukudigi.com</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; }
        .reader-shell { display: flex; flex-direction: column; height: 100vh; }
        .reader-topbar { display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #0f172a; color: #f1f5f9; font-size: 14px; }
        .reader-topbar a { color: #cbd5e1; text-decoration: none; }
        .reader-topbar a:hover { color: #fff; }
        .reader-topbar .title { flex: 1; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .reader-topbar button { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #f1f5f9; padding: 6px 10px; border-radius: 6px; font-size: 12px; cursor: pointer; }
        .reader-topbar button:hover { background: rgba(255,255,255,0.18); }
        .reader-topbar button:disabled { opacity: 0.4; cursor: not-allowed; }
        .reader-body { flex: 1; position: relative; overflow: hidden; background: #fff; }
        #viewer { width: 100%; height: 100%; }
        .reader-loading { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 12px; color: #475569; font-size: 14px; }
        .reader-loading .spinner { width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #4f46e5; border-radius: 50%; animation: spin 0.9s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .reader-error { padding: 20px; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; margin: 20px; }

        /* TOC sidebar */
        .reader-toc { position: absolute; top: 0; left: 0; bottom: 0; width: 280px; background: #fff; border-right: 1px solid #e2e8f0; overflow-y: auto; padding: 12px 14px; transform: translateX(-100%); transition: transform 0.2s; z-index: 20; }
        .reader-toc.open { transform: translateX(0); }
        .reader-toc h3 { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; margin: 0 0 10px; }
        .reader-toc ul { list-style: none; padding: 0; margin: 0; }
        .reader-toc li { margin: 4px 0; }
        .reader-toc a { display: block; padding: 6px 8px; color: #334155; text-decoration: none; border-radius: 4px; font-size: 13px; }
        .reader-toc a:hover { background: #f1f5f9; color: #4f46e5; }

        @media (max-width: 640px) {
            .reader-topbar .title { font-size: 12px; }
            .reader-toc { width: 86vw; }
        }
    </style>
</head>
<body>
<div class="reader-shell">
    <div class="reader-topbar">
        <a href="{{ route('library') }}" title="Kembali ke Library">← Library</a>
        <button id="tocBtn" type="button" title="Daftar Isi">☰ TOC</button>
        <div class="title">{{ $order->book->title }}</div>
        <button id="prevBtn" type="button" title="Halaman sebelumnya">‹ Prev</button>
        <button id="nextBtn" type="button" title="Halaman berikutnya">Next ›</button>
        <a href="{{ route('download.epub', $order->order_code) }}" title="Download .epub" style="margin-left:6px;font-size:12px;">⬇ EPUB</a>
    </div>
    <div class="reader-body">
        <div class="reader-toc" id="tocPanel">
            <h3>Daftar Isi</h3>
            <ul id="tocList"><li style="color:#94a3b8;font-size:12px;">Loading…</li></ul>
        </div>
        <div class="reader-loading" id="loading">
            <div class="spinner"></div>
            <div>Memuat EPUB…</div>
        </div>
        <div id="viewer"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/epubjs@0.3.88/dist/epub.min.js"></script>
<script>
    (async function () {
        const url = @json($epubStreamUrl);
        const downloadUrl = @json(route('download.epub', $order->order_code));
        const loading = document.getElementById('loading');
        const tocList = document.getElementById('tocList');
        const tocPanel = document.getElementById('tocPanel');

        const showError = (msg, raw) => {
            console.error('[EPUB Reader]', msg, raw);
            loading.innerHTML =
                '<div class="reader-error">' +
                '<strong>Gagal memuat EPUB di reader.</strong><br><br>' +
                'Detail: ' + (msg || 'unknown') + '<br><br>' +
                '<a href="' + downloadUrl + '" style="color:#4f46e5;font-weight:600;">⬇ Download .epub saja</a> — baca pakai apk lain (Calibre, Apple Books, dll).' +
                '</div>';
        };

        // 1) Fetch EPUB sebagai ArrayBuffer — supaya HTTP error langsung ke-detect
        let buffer;
        try {
            const resp = await fetch(url, { credentials: 'same-origin' });
            if (! resp.ok) {
                showError('HTTP ' + resp.status + ' ' + resp.statusText);
                return;
            }
            buffer = await resp.arrayBuffer();
            if (! buffer || buffer.byteLength < 100) {
                showError('File EPUB kosong atau terlalu kecil (' + (buffer?.byteLength || 0) + ' bytes)');
                return;
            }
        } catch (e) {
            showError('Network error: ' + e.message, e);
            return;
        }

        // 2) Pass ArrayBuffer ke ePub() — lebih reliable daripada URL-based loading
        let book, rendition;
        try {
            book = ePub(buffer);
            rendition = book.renderTo('viewer', {
                width: '100%',
                height: '100%',
                spread: 'auto',
                flow: 'paginated',
                allowScriptedContent: false,
            });
            rendition.themes.fontSize('110%');
        } catch (e) {
            showError('Init reader error: ' + e.message, e);
            return;
        }

        // 3) Timeout fallback — kalau lebih 20 detik masih loading, tampilkan opsi download
        const timeoutId = setTimeout(() => {
            if (loading.style.display !== 'none') {
                showError('Loading timeout (lebih dari 20 detik). EPUB mungkin tidak kompatibel dengan reader inline.');
            }
        }, 20000);

        try {
            await book.ready;
            await rendition.display();
            loading.style.display = 'none';
            clearTimeout(timeoutId);
        } catch (e) {
            clearTimeout(timeoutId);
            showError('Render error: ' + (e?.message || e), e);
            return;
        }

        // 4) TOC sidebar
        book.loaded.navigation.then((nav) => {
            tocList.innerHTML = '';
            const renderToc = (items, depth = 0) => {
                items.forEach((item) => {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = '#';
                    a.style.paddingLeft = (depth * 12 + 8) + 'px';
                    a.textContent = (item.label || '').trim() || '(Untitled)';
                    a.onclick = (e) => {
                        e.preventDefault();
                        rendition.display(item.href);
                        tocPanel.classList.remove('open');
                    };
                    li.appendChild(a);
                    tocList.appendChild(li);
                    if (item.subitems && item.subitems.length) {
                        renderToc(item.subitems, depth + 1);
                    }
                });
            };
            if (nav.toc && nav.toc.length) {
                renderToc(nav.toc);
            } else {
                tocList.innerHTML = '<li style="color:#94a3b8;font-size:12px;">TOC tidak tersedia.</li>';
            }
        }).catch((e) => {
            console.warn('[EPUB Reader] TOC load failed:', e);
            tocList.innerHTML = '<li style="color:#94a3b8;font-size:12px;">TOC gagal dimuat.</li>';
        });

        // Controls
        document.getElementById('prevBtn').onclick = () => rendition.prev();
        document.getElementById('nextBtn').onclick = () => rendition.next();
        document.getElementById('tocBtn').onclick = () => tocPanel.classList.toggle('open');

        document.addEventListener('keyup', (e) => {
            if (e.key === 'ArrowLeft') rendition.prev();
            if (e.key === 'ArrowRight') rendition.next();
        });
        rendition.on('keyup', (e) => {
            if (e.key === 'ArrowLeft') rendition.prev();
            if (e.key === 'ArrowRight') rendition.next();
        });

        document.addEventListener('click', (e) => {
            if (!tocPanel.contains(e.target) && e.target.id !== 'tocBtn' && tocPanel.classList.contains('open')) {
                tocPanel.classList.remove('open');
            }
        });
    })();
</script>
</body>
</html>
