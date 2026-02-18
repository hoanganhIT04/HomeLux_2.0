<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order->load('items.product', 'user');
    }

    public function build()
    {
        return $this
            ->subject('Đơn hàng #' . $this->order->public_id . ' đã bị hủy')
            ->view('emails.order-cancelled');
    }
}
