<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Order Alert</title>

    <link href="https://fonts.googleapis.com/css?family=Roboto:400,600" rel="stylesheet" type="text/css">

    <style>
        html, body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
            font-family: 'Roboto', sans-serif !important;
            font-size: 14px;
            margin-bottom: 10px;
            line-height: 24px;
            color: #8094ae;
            font-weight: 400;
        }
        table, td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }
        a {
            text-decoration: none;
        }
        img {
            -ms-interpolation-mode: bicubic;
        }
    </style>
</head>

<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #f5f6fa;">
    <center style="width: 100%; background-color: #f5f6fa;">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#f5f6fa">
            <tr>
                <td style="padding: 40px 0;">
                    <table style="width:100%;max-width:620px;margin:0 auto;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding-bottom:25px">
                                    <a href="#"><img style="width: 200px" src="{{ $logoUrl }}" alt="logo"></a>
                                    <p style="font-size: 14px; color: #4fcf4d; padding-top: 12px;">Order Alert</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table style="width:100%;max-width:620px;margin:0 auto;background-color:#ffffff;">
                        <tbody>
                            <tr>
                                <td style="padding: 30px 30px 20px">
                                    <p style="margin-bottom: 10px;">Hi {{ $sellerName }},</p>
                                    <p style="margin-bottom: 10px;">You have one order on our online store - {{ $projectName }}.</p>
                                    <p><b>Order Details:</b></p>
                                    <p>
                                        Order ID: {{ $orderId }}<br>
                                        Order Status: {{ $orderStatus }}<br>
                                        Payment Status: {{ $paymentStatus }}<br>
                                        Product Name: {{ $productName }}<br>
                                        Product Link: <a href="{{ $productLink }}" target="_blank">{{ $productLink }}</a><br>
                                    </p>
                                    <p><b>Customer Shipping Address Details:</b></p>
                                    <p>
                                        Name: {{ $customerName }}<br>
                                        Address: {{ $shippingAddress }}<br>
                                        City: {{ $shippingCity }}<br>
                                        Zip Code: {{ $shippingPostalCode }}<br>
                                        State: {{ $shippingState }}<br>
                                        Country: {{ $shippingCountry }}<br>
                                        Phone Number: {{ $shippingPhoneNumber }}<br>
                                        Email Address: {{ $shippingEmailId }}
                                    </p>
                                    <p><b>Note:</b></p>
                                    <p>1) Ship product for paid orders only and update order status.<br>
                                       2) Please confirm customer’s shipping address before actual shipping.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table style="width:100%;max-width:620px;margin:0 auto;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding:25px 20px 0;">
                                    <p style="font-size: 13px;">Copyright &#169; {{ date('Y') }} {{ $projectName }}. All rights reserved.</p>
                                    <p style="padding-top: 15px; font-size: 12px;">This email was sent to you as a registered user of <a style="color: #4fcf4d; text-decoration:none;" href="#">{{ $projectName }}</a>.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>
