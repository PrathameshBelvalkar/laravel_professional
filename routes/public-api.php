<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicNewsController;
use App\Http\Controllers\API\V1\Coin\CoinController;
use App\Http\Controllers\API\V1\PublicAPIController;
use App\Http\Controllers\API\V1\Coin\ReportController;
use App\Http\Controllers\API\V1\StreamDeck\TvController;
use App\Http\Controllers\API\V1\Podcast\ArtistController;
use App\Http\Controllers\API\V1\Account\ProfileController;
use App\Http\Controllers\API\V1\Coin\InvestmentController;
use App\Http\Controllers\API\V1\Connect\ConnectController;
use App\Http\Controllers\API\V1\Podcast\PodcastController;
use App\Http\Controllers\API\V1\Flipbook\FlipbookController;
use App\Http\Controllers\API\V1\SiloSearch\SearchController;
use App\Http\Controllers\API\V1\Wallet\TransactionController;
use App\Http\Controllers\API\V1\Assembler\AssemblerController;
use App\Http\Controllers\API\V1\Marketplace\ProductController;
use App\Http\Controllers\API\V1\Account\SubscriptionController;
use App\Http\Controllers\API\V1\FileManager\FileManagerController;
use App\Http\Controllers\API\V1\Flipbook\FlipbookPublicationController;
use App\Http\Controllers\API\V1\LiveChat\Livechatting;
use App\Http\Controllers\API\V1\Marketplace\MarketplaceController;
use App\Http\Controllers\API\V1\Marketplace\MarketPlaceLiveController;
use App\Http\Controllers\API\V1\Marketplace\MarketplaceStoreController;
use App\Http\Controllers\API\V1\StreamDeck\StreamDeckController;
use App\Models\Marketplace\MarketPlaceLive;
use App\Http\Controllers\API\V1\AppDetails\AppDetailsController;

Route::group(['prefix' => 'public'], function () {
  Route::get('/countries', [PublicAPIController::class, "getCountries"]);
  Route::get('/states', [PublicAPIController::class, "getStates"]);
  Route::get('/cities', [PublicAPIController::class, "getCities"]);
  Route::post('/get-site-setting', [PublicAPIController::class, "getSiteSetting"]);
  Route::post('/get-site-settings', [PublicAPIController::class, "getSiteSettings"]);
  Route::get('/channel-video', [PublicAPIController::class, "defaultchannelvideo"]);
  Route::get('/apps', [PublicAPIController::class, "getApps"]);
  Route::get('/send-event-notification', [PublicAPIController::class, "cloudSendEventcrobJob"]);
  Route::get('/get-qr-image/{qr_id}', [PublicAPIController::class, "getQrimage"]);
  Route::post('/get-filepdf', [PublicAPIController::class, "getPdfQr"]);
  Route::post('/set-qr-scan', [PublicAPIController::class, "setScan"]);
  Route::post('/get-blog', [PublicAPIController::class, "getBlog"]);
  Route::post('/get-list-blog', [PublicAPIController::class, "getBlogList"]);
  Route::post('/get-list-blogs', [PublicAPIController::class, "getBlogLists"]);
  Route::post('/get-category-count', [PublicAPIController::class, "getAllCategories"]);
  Route::post('/get-list-nav', [PublicAPIController::class, "getNavList"]);
  Route::post('/addContactus', [PublicAPIController::class, "addContactus"]);
  Route::get('/expire-subscription', [SubscriptionController::class, "expireSubscription"]);
  Route::get('/expire-subscription-alert', [SubscriptionController::class, "expireSubscriptionAlert"]);
  Route::get('/activate-package-subscription', [SubscriptionController::class, "activatePackageSubscription"]);
  Route::get('/activate-service-subscription', [SubscriptionController::class, "activateServiceSubscription"]);
  Route::post('/add-silosecure', [PublicAPIController::class, "addSilosecureConsultation"]);
  Route::get('/get-silosecure', [PublicAPIController::class, "getSilosecureConsultation"]);
  Route::post('/fetch-tv-programs', [PublicAPIController::class, "fetchTvPrograms"]);
  Route::post('/get-website-programs', [PublicAPIController::class, "fetchWebsitePrograms"]);
  Route::post('/get-public-website', [PublicAPIController::class, 'getWebsiteProgramData']);
  Route::get('/token-value-log', [TransactionController::class, "tokenLog"]);
  Route::post('/track-tv-view', [PublicAPIController::class, "trackView"]);
  Route::post('/add-mail-labels', [PublicAPIController::class, 'addEmailLabels']);
  Route::post('/get-mail-labels', [PublicAPIController::class, 'getEmailLabels']);
  Route::get('/get-mail-colors', [PublicAPIController::class, "getMailColors"]);
  Route::get('/get-mail-navs', [PublicAPIController::class, "getMailnavs"]);
  Route::post('/get-marketplace-live', [PublicAPIController::class, "getMarketPlaceLiveData"]);
  Route::get('/get-live-sellers', [PublicAPIController::class, "getMarketplaceLiveSellers"]);
  Route::post('/short-url-qr', [PublicAPIController::class, "shortenUrlQR"]);
  Route::post('/get-original-url', [PublicAPIController::class, "getOriginalUrl"]);
  Route::post('/frontend-settings', [PublicAPIController::class, "frontendSettings"]);
  Route::get('/get-tempurl', [PublicAPIController::class, "getFlipbookTemporaryURL"]);
  Route::get('/fetch-public-news/{type}', [PublicNewsController::class, "fetchPublicNews"]);
  Route::get('/get-public-news', [PublicNewsController::class, "getPublicNews"]);
  Route::post('/read-public-news', [PublicNewsController::class, "readPublicNews"]);
  Route::get('/remove-public-news', [PublicNewsController::class, "removePublicNews"]);
  Route::post("/is-public-meeting/{room_name}", [ConnectController::class, "isPublicMeeting"]);
  Route::get("/send-meeting-alert", [ConnectController::class, "sendMeetingAlert"]);
  Route::post("/leave-meeting/{room_name}", [ConnectController::class, "leaveMeeting"]);
  Route::get("/search-products", [PublicAPIController::class, "searchProducts"]);
  Route::post('/get-category-list', [PublicAPIController::class, "getCategoryList"]);
  Route::post('/get-channel-position', [StreamDeckController::class, "getChannelLogoPosition"]);
  Route::get('/live/get-product-pin', [MarketPlaceLiveController::class, 'getProductPin']);
});


Route::group(['prefix' => 'silo-home'], function () {
  Route::get('/cloud-search', [SearchController::class, "cloudSearch"]);
});
Route::group(['prefix' => 'marketplace'], function () {
  Route::get('/get-products', [MarketplaceController::class, 'getProduct']);
  Route::post('/get-product-file', [ProductController::class, 'getProductFile']);
  Route::get('/get-stores-public', [MarketplaceStoreController::class, 'getStores']);
  Route::get('/get-header-categories', [MarketplaceController::class, "getHeaderCategories"]);
  Route::get('/get-banner', [MarketplaceController::class, 'getSiteBanner']);
  Route::post('/get-product-questions', [MarketplaceController::class, 'getStoreProductQuestions']);
  Route::post('live/add-views', [MarketPlaceLiveController::class, 'addViews']);
  Route::post('/get-product-reviews', [MarketplaceController::class, 'getStoreProductReviews']);
  Route::post('/store-filters', [PublicAPIController::class, 'allStoresFilter']);
  Route::get('/video/download/{filePath}', [MarketplaceController::class, 'downloadVideo'])
    ->where('filePath', '.*')
    ->name('video.download');
  Route::get('/download/{path}', function ($path) {
    $filePath = storage_path('app/' . $path);

    if (file_exists($filePath)) {
      return response()->file($filePath);
    } else {
      abort(404, 'File not found.');
    }
  })->where('path', '.*');
  Route::get('/download/{path}', [MarketplaceController::class, 'download'])->where('path', '.*');
  Route::get('/download/{path}', function ($path) {
    $filePath = storage_path('app/' . $path);
    if (file_exists($filePath)) {
      return response()->file($filePath);
    } else {
      abort(404, 'File not found.');
    }
  })->where('path', '.*');
});
Route::group(['prefix' => 'coin'], function () {
  Route::get('/get-coin', [CoinController::class, "getCoin"]);
  Route::get('/get-coin-calendar', [CoinController::class, "getCoinCalendar"]);
  Route::get('/dashboard', [CoinController::class, "getDashboard"]);
  Route::post('/recently-added', [CoinController::class, "recentlyAdded"]);
  Route::get('/trending-coin', [CoinController::class, "trendingcoin"]);
  Route::get('/get-coin-video', [CoinController::class, "getCoinVideo"]);
  Route::get('/get-monthly-investment', [CoinController::class, "getInvestments"]);
  Route::post('/get-review', [CoinController::class, "getReview"]);
  Route::get('/get-investment-data', [InvestmentController::class, "getInvestmentData"]);
  Route::post('/get-coin-news', [PublicAPIController::class, "getNews"]);
});

//App Details
Route::group(["prefix" => "app-details"], function () {
  Route::get('/get-app-details', [AppDetailsController::class, "getAppDetails"]);
});

Route::group(['prefix' => 'reports'], function () {
  Route::get('/get-category', [ReportController::class, "getCategory"]);
  Route::get('/get-sub-category', [ReportController::class, "getSubCategory"]);
  Route::get('/get-reports', [ReportController::class, "getReportsCategory"]);
  Route::post('/get-pdf-reports', [ReportController::class, "getReports"]);
});
Route::group(['prefix' => 'apps'], function () {
  Route::get('/get-apps', [PublicAPIController::class, "getSiloApps"]);
});
Route::group(['prefix' => 'user-information'], function () {
  Route::post('/get-information-from-id', [PublicAPIController::class, "getUserInformationFromId"]);
  Route::post('/fetch-users-from-search', [PublicAPIController::class, "fetchUsersListFromSearch"]);
});
Route::group(['prefix' => 'podcast'], function () {
  Route::post('/get-podcast', [PodcastController::class, "getPodcasts"]);
  Route::get('/categories-and-tags', [PodcastController::class, 'getPodcastCategoriesAndTags']);

  Route::get('/languages', [PodcastController::class, "getLanguages"]);
  Route::post('/increment-listen-count/{id}', [PodcastController::class, "incrementEpisodeListenCount"]);
  Route::get('/artist/get/{id}', [ArtistController::class, "getArtistById"]);
  Route::get('/artist/get', [ArtistController::class, "getArtist"]);
  Route::get('/get-audio-url', [PodcastController::class, 'getAudioUrlByPodcastId']);
  Route::get('/get-episode-by-categoryid', [PodcastController::class, 'getEpisodeByCategoryId']);
});
Route::group(['prefix' => 'flipbook'], function () {
  Route::get('/search', [FlipbookPublicationController::class, 'searchPublication']);
  Route::post('/publisher-profile', [FlipbookPublicationController::class, 'publicationProfile']);
  Route::post('/view/publication', [FlipbookController::class, 'viewFlipbookNotification']);
  Route::get('/publish/categories', [FlipbookPublicationController::class, "getFlipbookCategories"]);
  Route::get('/review/get-review', [FlipbookPublicationController::class, "getFlipbookReviews"]);
});

// API's for Assembler
Route::group(['prefix' => 'assembler'], function () {
  Route::post('/upload-file', [AssemblerController::class, "uploadUserAssemblerFile"]);
});
Route::group(['prefix' => 'influencer'], function () {
  Route::get('/get-influencer', [PublicAPIController::class, "getInfluencer"]);
  Route::get('/stop-live-streams', [MarketPlaceLiveController::class, "StopAllStreams"]);
});
Route::group(['prefix' => "live-chat"], function () {
  Route::post("/get-chat", [Livechatting::class, "getChat"]);
});
Route::group(['prefix' => 'tv'], function () {
    Route::get('/get-genres',[TvController::class,'getGenres']);
    Route::get('/get-all-public-category',[TvController::class,'getAllPublicCategories']);
    Route::post('/get-public-seasons',[TvController::class,'getPublicSeasons']);
    Route::post('/get-public-episodes',[TvController::class,'getPublicEpiosdes']);
    Route::post('/get-searched-series',[TvController::class,'SearchSeries']);
    Route::get('/get-series-by-genre/{id}',[TvController::class,'FindSeriesByGenre']);
    Route::post('/increase-view',[TvController::class,'increaseView']);
    Route::post('/get-series-by-id',[TvController::class,'getSeriesById']);
});
