<?php

namespace App\Services\Admin;

use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderDeliveringMail;
use App\Mail\OrderCompletedMail;
use App\Mail\OrderCancelledMail;

class OrderService
{
    /**
     * Updates an order's status and runs business logic (e.g., sending emails)
     * Throws an Exception if the status transition is invalid.
     */
    public function updateOrderStatus(Order $order, string $newStatus): void
    {
        $oldStatus = $order->status;

        $this->validateStateTransition($oldStatus, $newStatus);

        $order->update(['status' => $newStatus]);

        $this->dispatchStatusEmail($order, $oldStatus, $newStatus);
    }

    /**
     * Checks if a transition between two order statuses is permitted.
     */
    private function validateStateTransition(string $oldStatus, string $newStatus): void
    {
        if (in_array($oldStatus, ['completed', 'cancelled'])) {
            throw new \Exception('Đơn hàng đã kết thúc, không thể đổi trạng thái.');
        }

        if ($newStatus === 'delivering' && !in_array($oldStatus, ['pending', 'paid'])) {
            throw new \Exception('Chỉ có thể giao hàng khi đơn ở trạng thái Chờ duyệt hoặc Đã thanh toán.');
        }

        if (in_array($newStatus, ['completed', 'cancelled']) && $oldStatus !== 'delivering') {
            throw new \Exception('Cần chuyển trạng thái sang "Đang giao" trước khi hoàn thành hoặc hủy.');
        }
    }

    /**
     * Dispatches the appropriate email based on the new status
     */
    private function dispatchStatusEmail(Order $order, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        $order->load(['user', 'items.product']);

        switch ($newStatus) {
            case 'delivering':
                Mail::to($order->user->email)->send(new OrderDeliveringMail($order));
                break;
            case 'completed':
                Mail::to($order->user->email)->send(new OrderCompletedMail($order));
                break;
            case 'cancelled':
                Mail::to($order->user->email)->send(new OrderCancelledMail($order));
                break;
        }
    }
}
