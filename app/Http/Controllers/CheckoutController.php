<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserAddress;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CheckoutStoreRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderCreatedMail;
use App\Models\Product;

class CheckoutController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $cartItems = CartItem::where('user_id', $user->id)
            ->with('product.primaryImage')
            ->get();

        // Chặn nếu giỏ hàng trống
        if ($cartItems->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Giỏ hàng đang trống. Không thể tiến hành thanh toán.');
        }

        $total = $cartItems->sum(fn($item) => $item->price * $item->quantity);

        $defaultAddress = UserAddress::where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->first();

        return inertia('Checkout', [
            'cartItems' => $cartItems,
            'total' => $total,
            'user' => $user,
            'defaultAddress' => $defaultAddress,
        ]);
    }


    public function store(CheckoutStoreRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        $cartItems = CartItem::where('user_id', $user->id)
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return back()->withErrors([
                'cart' => 'Giỏ hàng đang trống.'
            ]);
        }

        $totalPrice = $cartItems->sum(
            fn($item) => $item->price * $item->quantity
        );

        $shippingFee = 30000;
        $total = $totalPrice + $shippingFee;

        // ==========================
        // COD → TẠO ORDER NGAY
        // ==========================
        if ($data['payment_method'] === 'cod') {

            $order = DB::transaction(function () use (
                $user,
                $data,
                $cartItems,
                $total,
                $shippingFee
            ) {

                $publicId = 'ORD-' . strtoupper(Str::random(8));

                $order = Order::create([
                    'public_id'      => $publicId,
                    'user_id'        => $user->id,
                    'status'         => 'pending',
                    'total_price'    => $total,
                    'shipping_fee'   => $shippingFee,
                    'payment_method' => 'cod',

                    'receiver_name'  => $data['receiver_name'],
                    'receiver_phone' => $data['receiver_phone'],
                    'receiver_email' => $data['receiver_email'] ?? null,

                    'province'       => $data['province'],
                    'district'       => $data['district'],
                    'ward'           => $data['ward'],
                    'detail'         => $data['detail'],
                    'full_address'   => $data['full_address'],
                    'note'           => $data['note'] ?? null,
                ]);

                foreach ($cartItems as $item) {
                    $product = Product::find($item->product_id);

                    if ($item->quantity > $product->quantity) {
                        return back()->withErrors([
                            'stock' => "Sản phẩm {$product->name} chỉ còn {$product->quantity} sản phẩm."
                        ]);
                    }
                }

                CartItem::where('user_id', $user->id)->delete();

                return $order;
            });

            // 🔥 LOAD RELATION TRƯỚC KHI GỬI MAIL
            $order->load([
                'user',
                'items.product'
            ]);

            // 🔥 GỬI MAIL XÁC NHẬN
            Mail::to($order->user->email)
                ->send(new OrderCreatedMail($order));

            return redirect()
                ->route('cart.index')
                ->with('success', "Đặt hàng thành công. Mã đơn: {$order->public_id}");
        }


        // ==========================
        // MOMO → KHÔNG TẠO ORDER
        // ==========================
        if ($data['payment_method'] === 'momo') {

            // Lưu tạm thông tin vào session
            session([
                'checkout_data' => [
                    'user_id' => $user->id,
                    'cart_items' => $cartItems->toArray(),
                    'total' => $total,
                    'shipping_fee' => $shippingFee,
                    'form_data' => $data
                ]
            ]);

            return redirect()->route('payment.momo.create', [
                'amount' => $total
            ]);
        }
    }
}
