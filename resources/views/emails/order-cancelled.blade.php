<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đơn hàng đã bị hủy</title>
</head>

<body style="margin:0;padding:0;background-color:#fff4f4;">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:40px 10px;">

                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;
       border:1px solid #f5c2c7;overflow:hidden;">

                    <tr>
                        <td align="center" style="padding:30px 30px 10px;">
                            <h1 style="margin:0;font-family:'Segoe UI';
           font-size:28px;font-weight:700;
           color:#dc3545;letter-spacing:2px;">
                                TOY MARK
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:10px 30px 20px;">
                            <h2 style="margin:0;font-family:'Segoe UI';
           font-size:20px;font-weight:600;">
                                Đơn hàng đã bị hủy ❌
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

                            Đơn hàng của bạn tại <strong>TOY MARK</strong> đã được hủy.

                            Nếu bạn đã thanh toán trước đó, tiền sẽ được hoàn lại theo phương thức thanh toán ban đầu
                            (trong vòng 3-5 ngày làm việc).

                            Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi.

                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 30px 25px;">
                            <table width="100%" cellpadding="15" cellspacing="0" style="background:#fff5f5;border:1px solid #f5c2c7;border-radius:8px;
       font-family:'Segoe UI';font-size:14px;line-height:24px;color:#333;">
                                <tr>
                                    <td>

                                        <strong>Mã đơn hàng:</strong> {{ $order->public_id }} <br>
                                        <strong>Ngày đặt:</strong> {{ $order->created_at->format('d/m/Y H:i') }} <br>
                                        <strong>Phương thức:</strong> {{ strtoupper($order->payment_method) }} <br>
                                        <strong>Tổng tiền:</strong> {{ number_format($order->total_price) }} VNĐ <br>
                                        <strong>Trạng thái:</strong> Đã hủy

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:20px;background:#dc3545;
    color:#fff;font-size:14px;">
                            &copy; {{ date('Y') }} TOY MARK Store.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>