<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'paid', 'watermarking', 'ready'])
            ->with(['book.author'])
            ->latest('created_at')
            ->paginate(20);

        // Auto-refresh halaman kalau masih ada order yang sedang diproses (watermark job)
        $hasProcessing = $orders->contains(fn ($o) => in_array($o->status, ['paid', 'watermarking']));

        return view('public.library', compact('orders', 'hasProcessing'));
    }
}
