<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderCancelledMail;

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
        // Chỉ cho user hủy đơn của chính mình
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Chỉ được hủy khi chưa giao
        if (!in_array($order->status, ['pending', 'paid'])) {
            return redirect()
                ->route('orders.show', $order->id)
                ->with('error', 'Không thể hủy đơn khi đã được giao hoặc hoàn thành.');
        }

        $oldStatus = $order->status;

        $order->update([
            'status' => 'cancelled'
        ]);

        // Load relation trước khi gửi mail
        $order->load(['user', 'items.product']);

        // Tránh gửi trùng
        if ($oldStatus !== 'cancelled') {
            Mail::to($order->user->email)
                ->send(new OrderCancelledMail($order));
        }

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Đã hủy đơn hàng thành công.');
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
            $order->public_id . '-' . now()->format('Ymd') . '.pdf'
        );
    }
}
