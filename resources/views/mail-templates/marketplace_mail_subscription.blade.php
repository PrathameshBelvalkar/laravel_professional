<!DOCTYPE html>
<html lang="en" dir="ltr" xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ config('app.app_name') }} | Email Subscription</title>
    <style>
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }

        .body {
            font-family: sans-serif;
        }

        .text-center {
            text-align: center;
        }

        .py-5 {
            padding: 30px 0px;
        }

        .pb-4 {
            padding-bottom: 24px;
        }

        .p-1 {
            padding: 5px;
        }

        .p-3 {
            padding: 0px 20px;
        }

        .m-0 {
            margin: 0;
        }

        .mt-4 {
            margin-top: 24px;
        }

        .fs-12px {
            font-size: 12px;
        }

        .w-50 {
            width: 50%;
        }

        .w-33 {
            width: 33%;
        }

        .email-wraper {
            background: #f5f6fa;
            font-size: 14px;
            line-height: 22px;
            font-weight: 400;
            color: #8094ae;
            width: 100%;
        }

        .email-wraper a {
            color: #6576ff;
            word-break: break-all;
        }

        .email-wraper .link-block {
            display: block;
        }

        .email-body,
        .email-contact {
            width: 96%;
            max-width: 620px;
            margin: 0 auto;
            background: #ffffff;
        }

        .email-header,
        .email-footer {
            width: 100%;
            max-width: 620px;
            margin: 0 auto;
        }

        .email-logo {
            height: 60px;
        }

        .email-title {
            font-size: 23px;
            color: #8094ae;
            padding-top: 12px;
            margin: 0px;
        }

        .email-copyright-text {
            font-size: 13px;
        }

        .email-social {
            padding: 0;
        }

        .email-social li {
            display: inline-block;
            padding: 4px;
        }

        .email-social li a {
            display: inline-block;
            height: 20px;
            width: 20px;
            border-radius: 50%;
            background: #e82121;
        }

        .email-social li a img {
            width: 20px;
        }

        .twitter-logo img {
            height: 20px;
        }

        @media (max-width: 480px) {
            .p-sm-5 {
                padding: 2.75rem !important;
            }
        }
    </style>
</head>

<body class="body">
    <div role="article" aria-roledescription="email" aria-label="email name" lang="en" dir="ltr"
        style="font-size:medium; font-size:max(16px, 1rem)">
        <table class="email-wraper">
            <tbody>
                <tr>
                    <td class="py-5">
                        <table class="email-header">
                            <tbody>
                                <tr>
                                    <td class="text-center pb-4">
                                        <a href="#"><img class="email-logo"
                                                src="{{ asset('assets/images/logo/logo-dark.png') }}"
                                                alt="SiloCloud"></a>
                                        <p class="email-title">Welcome to SiloCloud</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table class="email-body">
                            <tbody>
                                <tr>
                                    <td class="p-3 p-sm-5">
                                        <p><strong>Hello,</strong></p>
                                        <div>
                                            <p>Thank you for subscribing to our marketplace. Stay tuned for the latest
                                                updates and offers.</p>
                                            <p>If you ever want to unsubscribe, you can do so by clicking the link
                                                below:</p>
                                            <p><a href="{{ $unsubscribe_url }}">Unsubscribe</a></p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table class="email-footer">
                            <tbody>
                                <tr>
                                    <td class="text-center mt-4">
                                        <hr />
                                        <p>If you no longer wish to receive our emails, you can unsubscribe by clicking
                                            the link below:</p>
                                        <a href="{{ $unsubscribe_url }}">Unsubscribe</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
