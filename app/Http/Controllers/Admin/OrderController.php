<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderDeliveringMail;
use App\Mail\OrderCompletedMail;
use App\Mail\OrderCancelledMail;

class OrderController extends Controller
{
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,delivering,completed,cancelled'
        ]);

        $oldStatus = $order->status;

        $order->update([
            'status' => $request->status
        ]);

        // Load relation trước khi gửi mail
        $order->load(['user', 'items.product']);

        if ($oldStatus !== $order->status) {

            switch ($order->status) {

                case 'delivering':
                    Mail::to($order->user->email)
                        ->send(new OrderDeliveringMail($order));
                    break;

                    // case 'completed':
                    //     Mail::to($order->user->email)
                    //         ->send(new OrderCompletedMail($order));
                    //     break;

                    // case 'cancelled':
                    //     Mail::to($order->user->email)
                    //         ->send(new OrderCancelledMail($order));
                    //     break;
            }
        }

        return back()->with('success', 'Cập nhật trạng thái thành công');
    }
}
