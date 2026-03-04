<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đơn hàng đã giao thành công</title>
</head>

<body style="margin:0;padding:0;background-color:#e9f8ec;">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:40px 10px;">

                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;
              border:1px solid #cce7d0;overflow:hidden;">

                    <!-- Logo -->
                    <tr>
                        <td align="center" style="padding:30px 30px 10px;">
                            <h1 style="margin:0;font-family:'Segoe UI';
                       font-size:28px;font-weight:700;
                       color:#088178;letter-spacing:2px;">
                                HOME LUX
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:10px 30px 20px;">
                            <h2 style="margin:0;font-family:'Segoe UI';
                       font-size:20px;font-weight:600;">
                                Đơn hàng đã giao thành công 🎉
                            </h2>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 30px 25px;
                            font-family:'Segoe UI';
                            font-size:15px;
                            line-height:24px;
                            color:#465b52;">
                            Xin chào <strong>{{ $order->receiver_name ?? $order->user->name }}</strong>,<br><br>

                            Đơn hàng của bạn tại <strong>TOY MARK</strong> đã được giao thành công.
                            Cảm ơn bạn đã tin tưởng và mua sắm cùng chúng tôi.
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 30px 25px;">
                            <table width="100%" cellpadding="15" cellspacing="0" style="background:#f6fffa;border:1px solid #cce7d0;border-radius:8px;
                   font-family:'Segoe UI';font-size:14px;line-height:24px;color:#333;">
                                <tr>
                                    <td>

                                        <strong>Mã đơn hàng:</strong> {{ $order->public_id }} <br>
                                        <strong>Ngày đặt:</strong> {{ $order->created_at->format('d/m/Y H:i') }} <br>
                                        <strong>Phương thức:</strong> {{ strtoupper($order->payment_method) }} <br>
                                        <strong>Tổng tiền:</strong> {{ number_format($order->total_price) }} VNĐ <br>
                                        <strong>Trạng thái:</strong> Hoàn thành
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Product List -->
                    <tr>
                        <td style="padding:0 30px;">
                            <table width="100%" cellpadding="10" cellspacing="0"
                                style="border-collapse:collapse;font-family:'Segoe UI';font-size:14px;">

                                <thead>
                                    <tr style="background:#088178;color:white;">
                                        <th align="left">Sản phẩm</th>
                                        <th align="center" width="80">SL</th>
                                        <th align="right" width="130">Giá</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @php $subtotal = 0; @endphp

                                    @foreach($order->items as $item)
                                    @php
                                    $lineTotal = $item->price * $item->quantity;
                                    $subtotal += $lineTotal;
                                    @endphp
                                    <tr style="border-bottom:1px solid #eee;">
                                        <td>{{ $item->product->name }}</td>
                                        <td align="center">{{ $item->quantity }}</td>
                                        <td align="right">{{ number_format($lineTotal) }} VNĐ</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <!-- Summary Box -->
                    <tr>
                        <td style="padding:20px 30px 30px;">
                            <table width="100%" cellpadding="10" cellspacing="0" style="background:#fafafa;border:1px solid #eee;border-radius:8px;
                   font-family:'Segoe UI';font-size:14px;">

                                <tr>
                                    <td align="right">Tạm tính:</td>
                                    <td align="right" width="130">
                                        {{ number_format($subtotal) }} VNĐ
                                    </td>
                                </tr>

                                <tr>
                                    <td align="right">Phí vận chuyển:</td>
                                    <td align="right">
                                        {{ number_format($order->shipping_fee) }} VNĐ
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                        <hr style="border:none;border-top:1px solid #ddd;">
                                    </td>
                                </tr>

                                <tr style="font-weight:bold;font-size:16px;color:#088178;">
                                    <td align="right">Tổng thanh toán:</td>
                                    <td align="right">
                                        {{ number_format($order->total_price) }} VNĐ
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- Shipping Info -->
                    <tr>
                        <td style="padding:0 30px 30px;
                        font-family:'Segoe UI';
                        font-size:14px;
                        line-height:22px;">
                            <strong>Địa chỉ giao hàng:</strong><br>
                            {{ $order->full_address }}<br>
                            {{ $order->receiver_phone }}
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding:20px;background:#088178;
                   color:#fff;font-size:14px;">
                            &copy; {{ date('Y') }} HOME LUX Store.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>