<?php extract($mail_data); ?>
<div class="section-mail">
    <style>
        .text-center {
            text-align: center;
        }
        .section-mail ul {
            list-style: none;
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
            bottom: 3rem;
            height: 175px;
            animation: sendmail 6s linear infinite;
        }
        @keyframes sendmail {
            0% {
                right: 10%;
                bottom: 3rem;
                opacity: 1;
            }
            10% {
                right: 15%;
                bottom: 4rem;
                opacity: 0.95;
            }
            20% {
                right: 20%;
                bottom: 6rem;
                opacity: 0.95;
            }
            30% {
                right: 25%;
                bottom: 10rem;
                opacity: 0.95;
            }
            40% {
                right: 30%;
                bottom: 15rem;
                opacity: 0.9;
            }
            50% {
                right: 35%;
                bottom: 20rem;
                opacity: 0.9;
            }
            60% {
                right: 40%;
                bottom: 25rem;
                opacity: 0.8;
            }
            70% {
                right: 45%;
                bottom: 30rem;
                opacity: 0.8;
            }
            80% {
                right: 50%;
                bottom: 35rem;
                opacity: 0.8;
            }
            90% {
                right: 60%;
                bottom: 40rem;
                opacity: 0.7;
            }
            100% {
                right: 80%;
                bottom: 45rem;
                opacity: 0;
            }
        }
        .loader {
            height: 12px;
            width: 100%;
            display: block;
            border-radius: 6px;
            background: linear-gradient(
                90deg,
                rgba(237, 50, 55, 1) 0%,
                rgba(245, 134, 52, 1) 20%,
                rgba(57, 166, 196, 1) 40%,
                rgba(0, 168, 89, 1) 60%,
                rgba(57, 49, 133, 1) 80%,
                rgba(237, 50, 55, 1) 100%
            );
            background-size: 200% 100%;
            animation: moveGradient 6s linear infinite;
        }
        ul {
            list-style-type: none !important;
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
        .mb-1 {
            margin-bottom: 8px !important;
        }
        .gifvideo {
            position: relative;
        }
        .gifvideo:after {
            content: "";
            background-color: red;
            width: 90%;
            height: 90%;
            position: absolute;
            right: -1rem;
            top: -1rem;
        }
    </style>

    <img src="{{ $logo }}" alt="" class="image-welcome" />
    <h1 class="text-center fw-bold my-3"> Welcome to SiloMail – Elevate Your Email with Video! </h1>
    <div class="content">
        <p class="text-center"> We’re thrilled to have you onboard. SiloMail enhances your email experience by allowing you to send not only traditional text but also live video and audio messages right from your inbox. </p>
        <p class="fw-bold text-center">Here’s what SiloMail can do for you:</p>
        <ul class="mb-3 text-center">
            <li> Record and attach live video or audio messages.</li>
        </ul>
        <div class="text-center">
            <img src="{{ $gif }}" alt="Mail GIF" class="gifvideo" height="250px" />
        </div>
        <ul class="mb-3 text-center">
            <li> Send standard and customised text emails.</li>
            <li> Organize your inbox easily with folders and labels.</li>
            <li> Quickly search for specific emails.</li>
            <li> Enjoy a secure and reliable platform.</li>
        </ul>
        <div class="text-center my-3">
            <a href="{{ $app_url }}" class="silo-btn text-center">Visit now</a>
        </div>
        <p class="text-center"> If you need any help, feel free to reach out to our support team. </p>
        <p class="text-center">Thank you for choosing SiloMail!</p>
        <h4 class="text-center mb-1">Best regards,</h4>
        <h4 class="text-center mb-1">The SiloMail Team <br /></h4>
        <h4 class="text-center mb-1">
            <a href="{{ $mail_url }}">mail.silocloud.io </a>
        </h4>
    </div>
    <img src="{{ $mail_image }}" alt="" class="cloude-img" />
    <span class="loader"></span>
</div>
