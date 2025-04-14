<?php extract($mail_data); ?>
<div class="section-mail">
    <style>
        .text-center { text-align: center; }
        p { margin-bottom: 14px !important; }
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
        .text-uppercase { margin-bottom: 0px; }
        .image-welcome { margin: auto; width: 100%; }
        .content { padding: 1rem 3rem; }
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
        .silo-btn:hover { color: #fff !important; }
        .cloude-img { position: absolute; left: 50%; bottom: 3rem; height: 175px; display: none; }
        .loader { 
            height: 12px; 
            width: 100%; 
            display: block; 
            border-radius: 6px; 
            background: linear-gradient(90deg, rgba(237, 50, 55, 1) 0%, rgba(245, 134, 52, 1) 20%, rgba(57, 166, 196, 1) 40%, rgba(0, 168, 89, 1) 60%, rgba(57, 49, 133, 1) 80%, rgba(237, 50, 55, 1) 100%); 
            background-size: 200% 100%; 
            animation: moveGradient 6s linear infinite; 
        }
        @keyframes moveGradient { 
            0% { background-position: 200% 0; } 
            100% { background-position: -200% 0; } 
        }
        .fw-bold { font-weight: bold; }
        ul li { line-height: 2; }
        .my-3 { margin: 3rem 0px; }
        a { color: #d42330; text-decoration: none; }
    </style>

    <img src="{{ $logo }}" alt="Welcome Image" class="image-welcome" />
    <h1 class="text-center fw-bold my-3">Welcome to the SiloCloud family!</h1>
    
    <div class="content">
        <p class="text-center">We’re thrilled to have you join the SiloCloud community!</p>
        <p class="text-center">Your new account opens the door to a world of productivity and collaboration.</p>
        <p class="fw-bold">It's a comprehensive productivity platform designed to help you. With SiloCloud, you’ve got a powerful suite of tools at your fingertips:</p>
        
        <ul class="mb-3">
            <li><strong>Connect:</strong> Host video conferences with crystal-clear audio and video quality.</li>
            <li><strong>Storage:</strong> Securely store and access your files from anywhere, anytime.</li>
            <li><strong>Mail:</strong> Send and receive emails with the added convenience of recording and attaching live videos and audio.</li>
            <li><strong>QR Generator:</strong> Create custom QR codes for easy sharing and scanning.</li>
            <li><strong>Streamdeck:</strong> Start your own TV channel by uploading videos or going live in seconds.</li>
            <li><strong>Calendar:</strong> Organize your schedule, create events, and share them with your team.</li>
            <li><strong>Community:</strong> Connect with like-minded individuals, share posts, and build a community.</li>
        </ul>

        <div class="text-center my-3">
            <a href="{{ $app_url }}" class="silo-btn text-center">Explore now</a>
        </div>

        <p><b>And that's just the beginning!</b> There’s so much more to discover on SiloCloud.</p>
        <p>SiloCloud is designed to streamline your workflow and make your daily tasks a breeze. Explore our platform and discover how these tools can help you achieve more.</p>
        <p>If you need assistance or have any questions, our support team is always ready to help.</p>
        
        <h4>The SiloCloud Team <br />
            <a href="{{ $app_url }}">- silocloud.io</a>
        </h4>
    </div>
    
    <img src="cloud.png" alt="Cloud Image" class="cloude-img" />
    <span class="loader"></span>
</div>
