<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác thực OTP</title>
    <style>
        /* Reset CSS */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        table {
            border-collapse: collapse !important;
        }

        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: #e9f8ec;">

    <div
        style="display: none; font-size: 1px; color: #e9f8ec; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
        Mã xác thực của bạn là {{ $otp }}.
    </div>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding: 40px 10px 40px 10px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%"
                    style="max-width: 600px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #cce7d0;">

                    <tr>
                        <td align="center" style="padding: 30px 30px 10px 30px; background-color: #ffffff;">
                            <a href="{{ url('/') }}" target="_blank" style="text-decoration: none;">
                                <h1
                                    style="margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 28px; font-weight: 700; color: #088178; text-transform: uppercase; letter-spacing: 2px;">
                                    HOME LUX
                                </h1>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 10px 30px 20px 30px;">
                            <h2
                                style="margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 20px; font-weight: 600; color: #212121;">
                                Xác thực tài khoản
                            </h2>
                        </td>
                    </tr>

                    <tr>
                        <td align="left" style="padding: 0px 30px 20px 30px;">
                            <p
                                style="margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 15px; line-height: 24px; color: #465b52;">
                                Xin chào, <br><br>
                                Cảm ơn bạn đã đăng ký tài khoản tại <strong>HOME LUX</strong>. Để hoàn tất quá trình xác
                                thực, vui lòng nhập mã OTP dưới đây:
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 10px 30px 30px 30px;">
                            <table border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center"
                                        style="border-radius: 8px; background-color: #e9f8ec; border: 2px dashed #088178;">
                                        <p
                                            style="margin: 0; font-size: 32px; font-family: monospace; font-weight: bold; color: #088178; padding: 15px 40px; letter-spacing: 8px;">
                                            {{ $otp }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 0px 30px 40px 30px;">
                            <p
                                style="margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; color: #9ca3af; line-height: 20px;">
                                Mã này chỉ giới hạn 1 lần sử dụng. Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email
                                này.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 20px; background-color: #088178;">
                            <p
                                style="margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; color: #ffffff;">
                                &copy; {{ date('Y') }} HOME LUX Store. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>