<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

  /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

  'name' => env('APP_NAME', 'Laravel'),

  /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

  'env' => env('APP_ENV', 'production'),

  /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

  'debug' => (bool) env('APP_DEBUG', false),

  /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

  'url' => env('APP_URL', 'http://localhost:5000/'),
  'enc_key' => env('ENC_KEY', ''),
  'account_url' => env('ACCOUNT_URL', 'http://localhost:3000/'),
  'calendar_url' => env('CALENDAR_URL', 'http://localhost:3001/'),
  'mail_url' => env('MAIL_URL', 'http://localhost:3002/'),
  'new_user_life_value' => env('NEW_USER_LIFE_VALUE', ''),
  'aggregation_cost' => env('AGGREGATION_COST', ''),
  'auger_fee' => env('AUGER_FEE', 2.9),
  'verify_token_exp' => env('VERIFY_TOKEN_EXP', ''),
  "captcha_secret_key" => env('CAPTCHA_SECRET_KEY', ''),
  'allowed_domains' => explode(',', env('ALLOWED_DOMAINS', [])),
  // 'asset_url' => env('ASSET_URL'),
  'asset_url' => env('http://localhost:8000'),
  'app_name' => env('APP_NAME', 'PROJECT_NAME'),
  'support_mail' => env('SUPPORT_MAIL', 'support@api.io'),
  'max_login_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
  'max_verify_email_requests' => env('MAX_VERIFY_EMAIL_REQUESTS', 5),
  'max_verify_2fa_requests' => env('MAX_VERIFY_2FA_REQUESTS', 5),

  'twilio_number' => env('TWILIO_NUMBER', 5),
  'twilio_auth_token' => env('TWILIO_AUTH_TOKEN', 5),
  'twilio_account_sid' => env('TWILIO_ACCOUNT_SID', 5),
  'twilio_api_version' => env('TWILIO_API_VERSION', 5),

  'file_temporary_url_expiration_time' => env('FILE_TEMPORARY_URL_EXPIRATION_TIME', 60),
  'storage_service_id' => 6,
  'ffmpeg_binaries' => env('FFMPEGBINARIES'),
  'ffprobe_binaries' => env('FFPROBEBINARIES'),
  'sso_base_url' => env('SSO_BASE_URL', ""),
  'sso_url' => env('SSO_URL', ""),
  'bearerToken' => env('BEARERTOKEN', ""),
  'parent_domain' => env('PARENT_DOMAIN', "parent-domain.com"),
  'app_short_name' => env('APP_SHORT_NAME', "parent-domain.com"),
  'ai_url' => env('AI_URL', "https://ai.com/"),
  'free_flipbook_sell_count' => env('FREE_FLIPBOOK_SELL_COUNT', 5),
  'folders_to_avoid_in_storage' => explode(',', env('FOLDERS_TO_AVOID_IN_STORAGE', '')),
  'folders_to_avoid_in_apps_storage' => explode(',', env('FOLDERS_TO_AVOID_IN_APPS_STORAGE', '')),
  'world_news_api_key' => env('WORLD_NEWS_API_KEY', ""),
  'world_news_api_search_key' => env('WORLD_NEWS_API_SEARCH_KEY', ""),
  'connect_url' => env('CONNECT_URL', ""),
  'socket_url' => env('SOCKET_URL', ""),
  'max_public_news_read_count' => env('MAX_PUBLIC_NEWS_READ_COUNT', 100),
  'max_public_news_table_count' => env('MAX_PUBLIC_NEWS_TABLE_COUNT', 1000),
  'sport_news_api_key' => env('SPORT_NEWS_API_KEY', 1000),




  /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

  'timezone' => 'UTC',

  /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

  'locale' => 'en',

  /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

  'fallback_locale' => 'en',

  /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

  'faker_locale' => 'en_US',

  /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

  'key' => env('APP_KEY'),

  'cipher' => 'AES-256-CBC',

  /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

  'maintenance' => [
    'driver' => 'file',
    // 'store' => 'redis',
  ],

  /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

  'providers' => ServiceProvider::defaultProviders()->merge([
    /*
     * Package Service Providers...
     */

    /*
     * Application Service Providers...
     */
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    // App\Providers\BroadcastServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    ProtoneMedia\LaravelFFMpeg\Support\ServiceProvider::class,
    SimpleSoftwareIO\QrCode\QrCodeServiceProvider::class,

  ])->toArray(),

  /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

  'aliases' => Facade::defaultAliases()->merge([
    // 'Example' => App\Facades\Example::class,
    'FFMpeg' => ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::class,
    'QRCode' => SimpleSoftwareIO\QrCode\Facades\QrCode::class,


  ])->toArray(),
];
