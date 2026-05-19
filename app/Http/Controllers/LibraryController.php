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

        return view('public.library', compact('orders'));
    }
}
