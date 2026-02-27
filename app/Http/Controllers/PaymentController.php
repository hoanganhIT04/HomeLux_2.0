<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderCreatedMail;
use App\Models\Product;

class PaymentController extends Controller
{
    public function createMomo(Request $request)
    {
        $checkoutData = session('checkout_data');

        if (!$checkoutData) {
            return redirect()->route('cart.index')
                ->with('error', 'Không tìm thấy dữ liệu thanh toán.');
        }

        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

        $partnerCode = env('MOMO_PARTNER_CODE');
        $accessKey   = env('MOMO_ACCESS_KEY');
        $secretKey   = env('MOMO_SECRET_KEY');

        $amount = (string) $checkoutData['total'];
        $orderId = 'MOMO-' . strtoupper(Str::random(10)); // ID tạm
        $orderInfo = "Thanh toán đơn hàng";

        $redirectUrl = env('MOMO_REDIRECT_URL');
        $ipnUrl      = env('MOMO_IPN_URL');
        $requestId   = time();
        $requestType = "captureWallet";
        $extraData   = "";

        $rawHash = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $response = Http::post($endpoint, [
            'partnerCode' => $partnerCode,
            'accessKey'   => $accessKey, // 🔥 THÊM DÒNG NÀY
            'requestId'   => $requestId,
            'amount'      => $amount,
            'orderId'     => $orderId,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl'      => $ipnUrl,
            'lang'        => 'vi',
            'extraData'   => $extraData,
            'requestType' => $requestType,
            'signature'   => $signature
        ]);

        $data = $response->json();

        if (!isset($data['payUrl'])) {
            Log::error('MoMo error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return redirect()->route('cart.index')
                ->with('error', 'Không thể kết nối đến MoMo.');
        }

        return Inertia::location($data['payUrl']);
    }

    public function momoCallback(Request $request)
    {
        Log::info("MoMo callback", $request->all());

        if ($request->resultCode != 0) {
            return redirect()->route('cart.index')
                ->with('error', 'Thanh toán thất bại.');
        }

        $checkoutData = session('checkout_data');

        if (!$checkoutData) {
            return redirect()->route('cart.index')
                ->with('error', 'Session thanh toán đã hết hạn.');
        }

        $order = DB::transaction(function () use ($checkoutData) {

            $publicId = 'ORD-' . strtoupper(Str::random(8));

            $order = Order::create([
                'public_id'      => $publicId,
                'user_id'        => $checkoutData['user_id'],
                'status'         => 'paid',
                'total_price'    => $checkoutData['total'],
                'shipping_fee'   => $checkoutData['shipping_fee'],
                'payment_method' => 'momo',

                ...$checkoutData['form_data']
            ]);

            foreach ($checkoutData['cart_items'] as $item) {

                $product = Product::find($item['product_id']);

                if ($item['quantity'] > $product->quantity) {
                    throw new \Exception("Sản phẩm {$product->name} không đủ tồn kho.");
                }

                // 🔥 TẠO ORDER ITEM
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'price'      => $item['price'],
                    'quantity'   => $item['quantity'],
                ]);

                // 🔥 TRỪ TỒN KHO
                $product->decrement('quantity', $item['quantity']);
            }

            CartItem::where('user_id', $checkoutData['user_id'])->delete();

            return $order;
        });

        // 🔥 Load quan hệ để dùng trong mail
        $order->load(['user', 'items.product']);

        // 🔥 GỬI MAIL TẠI ĐÂY
        Mail::to($order->user->email)
            ->queue(new OrderCreatedMail($order));

        session()->forget('checkout_data');

        return redirect()->route('orders.show', $order->id)
            ->with('success', 'Thanh toán thành công.');
    }
}