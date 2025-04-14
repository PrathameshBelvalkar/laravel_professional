# Project Laravel API app

## Requirements

1. PHP version 8.2
2. Composer

## Steps for uses

-   Copy .env.example to .env file if .env file not exist
-   Install composer using command `composer install`
-   Generate APP key `php artisan key:generate`
-   Set db connection in .env file && set proper env variable in it

-   run following command in seqeunce to migrations with all folders
    1 `php artisan migrate`
    2 `php artisan migrate --path=database/migrations/*`

-   run following command in seqeunce to migrations
    1 ` php artisan db:seed`

-   set mail variable as per your configuration in .env file

-   To run applicaiton
    `php artisan serve --host=192.168.1.32 --port=3005`

-   set following .env variables

    ENC_KEY=project_enc_key
    VERIFY_TOKEN_EXP=86400
    CAPTCHA_SECRET_KEY=google_captcha_secret_key [for localhost Use 6LcwAXUpAAAAABcdHXb2e34o3yr_Xvk3YH7jVKV4]
    NEW_USER_LIFE_VALUE=73
    AGGREGATION_COST=3
    AUGER_FEE=2.9
    SUPPORT_MAIL=support@project.io
    MAX_LOGIN_ATTEMPTS=5
    MAX_VERIFY_EMAIL_REQUESTS=5
    MAX_VERIFY_2FA_REQUESTS=5
    TWILIO_ACCOUNT_SID=<your-twilio-account-sid-value>
    TWILIO_AUTH_TOKEN=<your-twilio-account-authtoken>
    TWILIO_NUMBER=<your-twilio-account-number>  
    TWILIO_API_VERSION=<your-twilio-account-api-version>
    CALENDAR_URL=<your-calendar-url>
    ACCOUNT_URL=<your-account-url>
    MAIL_URL=<your-mail-url>
    FFMPEGBINARIES=/var/www/vhosts/silocloud.io/api.silocloud.io/ffmpeg/ffmpeg
    FFPROBEBINARIES=/var/www/vhosts/silocloud.io/api.silocloud.io/ffmpeg/ffprobe
    ALLOWED_DOMAINS=
    SSO_BASE_URL=<your-sso-auth-base-URL>
    SSO_URL=<your-sso-base-URL>
    BEARERTOKEN=<your-bearer-token>

-   add following images as it is in gitignore

1. public/assets/images/logo/logo.png
2. public/assets/images/logo/logo-dark.png
3. assets/default/videos/channel_video.mp4

-   when use mail application then use this mail variables add in .env file
    INFO_MAIL_MAILER=smtp
    INFO_MAIL_HOST=silocloud.io
    INFO_MAIL_PORT=465
    INFO_MAIL_USERNAME=mail@silocloud.io
    INFO_MAIL_PASSWORD=<your-mail-password>
    INFO_MAIL_ENCRYPTION=tls
    INFO_MAIL_FROM_ADDRESS=mail@silocloud.io
    INFO_FROM_NAME=Silocloud

```
Make below command to run ffmpeg and ffprobe
sudo chmod +x /var/www/vhosts/silocreditunion.com/api.silocreditunion.com/ffmpeg/ffmpeg
sudo chmod +x /var/www/vhosts/silocreditunion.com/api.silocreditunion.com/ffmpeg/ffprobe
```

```
silocreditunion.com/api.silocreditunion.com will be your server routes
```
