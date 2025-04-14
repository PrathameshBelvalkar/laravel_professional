<?php extract($emailData); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
</head>

<body>
    <div class="section-mail">
        <style>
            .text-center {
                text-align: center;
            }

            p {
                margin-bottom: 14px !important;
            }

            .section-mail {
                max-width: 70%;
                margin: auto;
                box-shadow: 0 0.125rem 0.25rem rgba(43, 55, 72, 0.15) !important;
                padding-bottom: 2rem;
                border-radius: 14px 14px 0px 0px;
                position: relative;
                overflow: hidden;
                background-color: #fff;
                white-space: initial;
            }

            .text-uppercase {
                margin-bottom: 0px;
            }

            .image-welcome {
                margin: auto;
                width: 100%;
            }

            .content {
                padding: 1rem 3rem;
            }

            .silo-btn {
                padding: 0.6rem 1.4rem;
                border-radius: 4px;
                background-color: #d42330;
                color: #fff;
                border: none;
                font-weight: 600;
                text-decoration: none !important;
                display: inline-block;
            }

            .silo-btn:hover {
                color: #fff !important;
            }

            .cloude-img {
                position: absolute;
                left: 50%;
                bottom: 3rem;
                height: 175px;
                display: none;
            }

            .loader {
                height: 12px;
                width: 100%;
                display: block;
                border-radius: 6px;
                background: linear-gradient(90deg,
                        rgba(237, 50, 55, 1) 0%,
                        rgba(245, 134, 52, 1) 20%,
                        rgba(57, 166, 196, 1) 40%,
                        rgba(0, 168, 89, 1) 60%,
                        rgba(57, 49, 133, 1) 80%,
                        rgba(237, 50, 55, 1) 100%);
                background-size: 200% 100%;
                animation: moveGradient 6s linear infinite;
            }

            @keyframes moveGradient {
                0% {
                    background-position: 200% 0;
                }

                100% {
                    background-position: -200% 0;
                }
            }

            .fw-bold {
                font-weight: bold;
            }

            ul li {
                line-height: 2;
            }

            .my-3 {
                margin: 3rem 0px;
            }

            a {
                color: #d42330;
                text-decoration: none;
            }
        </style>
        <!-- https://api.silocloud.io/assets/images/mail_public/Welcome.png -->
        <img src="{{ $logo }}" alt="" class="image-welcome" />
        <h1 class="text-center fw-bold my-3">SiloCloud Connect Invitation</h1>
        <div class="content">
            <p class="text-center"></p>
            <p class="">{{ $message }}</p>
            <p style="margin-bottom: 10px">
                <b>Meeting Code:</b> {{ $meeting_code }}
            </p>
            @if ($meeting_start_time)
                <p style="margin-bottom: 10px">
                    <b>Meeting Start Time:</b>
                    {{ $meeting_start_time }}
                    <small>{{ strtoupper($meeting_time_zone) }}</small>
                </p>
                @endif @if ($meeting_end_time)
                    <p style="margin-bottom: 10px">
                        <b>Meeting End Time:</b>
                        {{ $meeting_end_time }}
                        <small>{{ strtoupper($meeting_time_zone) }}</small>
                    </p>
                @endif
                <div class="text-center my-3">
                    <a href="{{ $link }}" class="silo-btn text-center">{{ $linkTitle }}</a>
                </div>
                <p>
                    SiloCloud is designed to streamline your workflow and make your daily
                    tasks a breeze. Explore our platform and discover how these tools can
                    help you achieve more.
                </p>
                <p>
                    If you need assistance or have any questions, our support team is
                    always ready to help.
                </p>
                <h4>
                    The SiloCloud Team <br />
                    <a href="https://silocloud.io/">- silocloud.io</a>
                </h4>
        </div>
    </div>
</body>

</html>
