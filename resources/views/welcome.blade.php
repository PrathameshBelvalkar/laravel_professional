<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.app_name') }}</title>
    <style>
        body {
            background: #000;
            color: #fff;
        }
    </style>
</head>

<body>
    <h1>{{ config('app.app_name') }} API Documentation</h1>
    <div>
        <ol>
            <li>User Authentication
                <ol>
                    <li>Login</li>
                    <li>Registration</li>
                    <li>Forgot Password</li>
                    <li>Change Password</li>
                    <li>Check Verification Code</li>
                </ol>
            </li>
        </ol>
    </div>
</body>

</html>
