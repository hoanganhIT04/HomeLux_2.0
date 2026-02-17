<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function show(Order $order)
    {
        // Chỉ cho phép user xem đơn của chính mình
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load([
            'items.product.primaryImage',
        ]);

        return Inertia::render('Orders/Show', [
            'order' => $order
        ]);
    }

    public function cancel(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if (!in_array($order->status, ['pending', 'paid'])) {
            return redirect()->route('orders.show', $order->id);
        }

        $order->update([
            'status' => 'cancelled'
        ]);

        return redirect()->route('orders.show', $order->id);
    }

    // public function cancel(Order $order)
    // {
    //     $order->status = 'cancelled';
    //     $order->save();

    //     return redirect()->route('orders.show', $order->id);
    // }
    public function invoice(Order $order)
    {
        $order->load('items.product');

        return view('invoices.invoice', [
            'order' => $order,
            'isPdf' => false
        ]);
    }

    public function invoicePdf(Order $order)
    {
        $order->load('items.product');

        $pdf = Pdf::loadView('invoices.invoice', [
            'order' => $order,
            'isPdf' => true
        ]);

        return $pdf->download(
            $order->public_id.'-'.now()->format('Ymd').'.pdf'
        );

    }
}
