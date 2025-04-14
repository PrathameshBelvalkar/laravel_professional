<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\StreamDeck\TvController;
use App\Http\Controllers\API\V1\StreamDeck\TestingController;
use App\Http\Controllers\API\V1\Qr\QrController;
use App\Http\Controllers\API\V1\Coin\KYController;
use App\Http\Controllers\API\V1\Blog\BlogController;
use App\Http\Controllers\API\V1\Coin\CoinController;
use App\Http\Controllers\API\V1\Game\TeamController;
use App\Http\Controllers\API\V1\Mail\MailController;
use App\Http\Controllers\API\V1\PublicAPIController;
use App\Http\Controllers\API\V1\Game\MatchController;
use App\Http\Controllers\API\V1\Game\SportController;
use \App\Http\Controllers\API\V1\Auth\LoginController;
use App\Http\Controllers\API\V1\Game\PlayerController;
use App\Http\Controllers\API\V1\LiveChat\Livechatting;
use App\Http\Controllers\API\V1\Game\VersionController;
use App\Http\Controllers\API\V1\SiloTalk\TalkController;
use App\Http\Controllers\API\V1\ThreeD\ThreeDController;
use App\Http\Controllers\API\V1\Podcast\ArtistController;
use App\Http\Controllers\API\V1\Wallet\PaymentController;
use App\Http\Controllers\API\V1\Account\ProfileController;
use App\Http\Controllers\API\V1\Coin\InvestmentController;
use App\Http\Controllers\API\V1\Connect\ConnectController;
use App\Http\Controllers\API\V1\Game\TournamentController;
use App\Http\Controllers\API\V1\Podcast\PodcastController;
use App\Http\Controllers\API\V1\Storage\StorageController;
use App\Http\Controllers\API\V1\support\SupportController;
use App\Http\Controllers\API\V1\Calendar\CalendarController;
use App\Http\Controllers\API\V1\ContactUs\ContactController;
use App\Http\Controllers\API\V1\Flipbook\FlipbookController;
use App\Http\Controllers\API\V1\Account\ConnectionController;
use App\Http\Controllers\API\V1\Wallet\TransactionController;
use App\Http\Controllers\API\V1\Marketplace\AddressController;
use App\Http\Controllers\API\V1\Marketplace\ProductController;
use App\Http\Controllers\API\V1\Account\SubscriptionController;
use App\Http\Controllers\API\V1\ThreeD\ThreeDproductController;
use App\Http\Controllers\API\V1\StreamDeck\StreamDeckController;
use App\Http\Controllers\API\V1\Wallet\ExternalWalletController;
use App\Http\Controllers\API\V1\Community\CommunityLiveController;
use App\Http\Controllers\API\V1\Community\CommunityPostController;
use App\Http\Controllers\API\V1\FileManager\FileManagerController;
use App\Http\Controllers\API\V1\Marketplace\MarketplaceController;
use App\Http\Controllers\API\V1\Marketplace\SellerBuyerController;
use App\Http\Controllers\API\V1\SiteSetting\SiteSettingController;
use App\Http\Controllers\API\V1\Community\CommunityStoryController;
use App\Http\Controllers\API\V1\Dashboard\DashboardImagesController;
use App\Http\Controllers\API\V1\Game\BasketballScoreBoardController;
use App\Http\Controllers\API\V1\Flipbook\FlipbookCollectionController;
use App\Http\Controllers\API\V1\Marketplace\MarketPlaceLiveController;
use App\Http\Controllers\API\V1\Flipbook\FlipbookPublicationController;
use App\Http\Controllers\API\V1\Marketplace\MarketplaceStoreController;
use App\Http\Controllers\API\V1\Community\CommunityDraftStoryController;
use App\Http\Controllers\API\V1\Community\CommunityPostCommentsController;
use App\Http\Controllers\API\V1\AppDetails\AppDetailsController;
use App\Http\Controllers\API\V1\Flipbook\FlipbookAnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
  return $request->user();
});
Route::group(['prefix' => 'v1'], function () {
  Route::group(['prefix' => 'auth'], function () {
    Route::post('/verify-link', [LoginController::class, "verifyLink"]);
    Route::post('/resend-link', [LoginController::class, "resendLink"]);

    Route::post('/sso-register', [LoginController::class, "ssoRegister"]); //->middleware('captcha');
    Route::post('/register', [LoginController::class, "register"]); //->middleware('captcha');
    Route::post('/login', [LoginController::class, "login"]);
    Route::post('/is-registered-user', [LoginController::class, "isRegisteredUser"]);
    Route::post('/verify-email-otp', [LoginController::class, "verifyEmailOtp"]);
    Route::post('/verify-sms-otp', [LoginController::class, "verifySmsOtp"]);
    Route::post('/register-shipper', [LoginController::class, 'register']);

    Route::post('/resend-email-otp', [LoginController::class, "resendEmailOtp"]);
    Route::post('/resend-sms-otp', [LoginController::class, "resendSmsOtp"]);

    Route::post('/resend-2fa-otp', [LoginController::class, "resend2FAOtp"]);
    Route::post('/verify-2fa-otp', [LoginController::class, "verifyLogin2FAOtp"]);
    Route::post('/verify-account', [LoginController::class, "verifyAccount"]);

    Route::post('/forgot-password', [LoginController::class, "sendforgotPasswordOTP"]);
    Route::post('/reset-password', [LoginController::class, "resetForgotPassword"]);
  });

  // whatever file you are uploading make sure key value is "files" --vb
  Route::group(['middleware' => ['project.auth']], function () {
    Route::group(['middleware' => ['null.stringto.null']], function () {
      Route::group(['prefix' => 'calendar-events'], function () {
        Route::post('/add', [CalendarController::class, "add"])->middleware('check.storage.limit');
        Route::post('/update', [CalendarController::class, "update"]);
        Route::post('/delete', [CalendarController::class, "delete"]);
        Route::post('/list', [CalendarController::class, "getEvents"]);
        Route::post('/get-event-attachment', [CalendarController::class, "geteventattachment"]);

        Route::post('/store', [CalendarController::class, "store"]);
        Route::post('/getCalenderEvent', [CalendarController::class, "getCalenderEvent"]);
      });

      Route::group(['prefix' => 'sitesetting'], function () {
        Route::post('/add-sitesetting', [SiteSettingController::class, "addSitesetting"]);
        Route::get('/get-sidebar-menu', [SiteSettingController::class, 'getSidebarMenu']);
      });

      Route::group(['prefix' => 'ContactUs'], function () {
        Route::post('/get-contact-data', [ContactController::class, "getContactUs"]);
        Route::get('/delete-feedback', [ContactController::class, "deleteFeedback"]);
      });

      Route::group(['prefix' => 'qr-code'], function () {
        Route::post('/add-qr', [QrController::class, "addqr"]);
        Route::post('/update-qr', [QrController::class, "updateqr"]);
        Route::post('/delete-qr', [QrController::class, "deleteqr"]);
        Route::post('/fetch-qr', [QrController::class, "fetchqr"]);
        Route::post('/get', [QrController::class, "showfiles"]);
        Route::post('/file-qr', [QrController::class, "fileqr"])->middleware('check.storage.limit');
        Route::post('/get-image', [QrController::class, "getEmbedImage"]);
        Route::post('/get-count', [QrController::class, "coutQrByUser"]);
        Route::post('/get-qr-subscription', [QrController::class, "getSubscriptionByUser"]);
        Route::post('/get-qr-facts', [QrController::class, "getQrFacts"]);
        Route::post('/get-qr-files', [QrController::class, "getUserFiles"]);
        Route::post('/get-product-qr', [QrController::class, "getProductQr"]);
        Route::get('/get-sub-details', [QrController::class, "subscriptionContains"]);
        Route::post('/generate-qr', [QrController::class, "generateQr"]);
        Route::post('/fetch-scan', [QrController::class, "fetchScan"]);
        Route::post('/test-notification', [QrController::class, "testNotification"]);
        Route::post('/scan/store-scan', [QrController::class, "storeScan"]);
        Route::post('/scan/get-scan', [QrController::class, "getScannedDataByUserId"]);
        Route::post('/add-qr-sku', [QrController::class, "addQRSku"]);
        Route::get('/get-qr-sku', [QrController::class, "getQrSku"]);
        Route::post('/update-qr-sku', [QrController::class, "updateQrSku"]);
      });

      Route::group(['prefix' => 'file-manager', 'middleware' => 'check.storage.subscription'], function () {
        Route::post('/add', [FileManagerController::class, "add"])->middleware('check.storage.limit');
        Route::post('/fetch', [FileManagerController::class, "fetchFiles"]);
        Route::post('/create-folder', [FileManagerController::class, "createFolder"]);
        Route::post('/share', [FileManagerController::class, "share"]);
        Route::post('/fetch-share', [FileManagerController::class, "fetchShare"]);
        Route::post('/delete', [FileManagerController::class, "delete"]);
        Route::post('/restore', [FileManagerController::class, "restore"]);
        Route::post('/fetch-delete', [FileManagerController::class, "fetchDelete"]);
        Route::post('/toggle-star', [FileManagerController::class, "toggleStar"]);
        Route::post('/fetch-star', [FileManagerController::class, "fetchStar"]);
        Route::post('/move', [FileManagerController::class, "move"]);
        Route::post('/fetch-folders-list', [FileManagerController::class, "getFolders"]);
        Route::post('/fetch-users', [FileManagerController::class, "fetchUsers"]);
        Route::post('/rename-file', [FileManagerController::class, "renameFile"]);
        Route::post('/get-all-videos-info', [FileManagerController::class, "getAllVideos"]);
        Route::post('/get-video-file', [FileManagerController::class, "getVideo"]);

        // Silo apps storage API's
        Route::post('/get-apps-storage', [FileManagerController::class, "getAppsStorageInfo"]);

        // SiloTalk API's
        Route::post('/upload-chat-files', [FileManagerController::class, "uploadSiloTalkChatFiles"])->middleware('check.storage.limit');
      });

      //App Details API's
      Route::group(["prefix" => "app-details"], function () {
        //Route::get('/get-app-details', [AppDetailsController::class, "getAppDetails"]);
        Route::post('/add-rating-review', [AppDetailsController::class, "addRatingReview"]);
        Route::post('/update-app-details', [AppDetailsController::class, "updateAppDetails"]);
        Route::post('/delete-app-details', [AppDetailsController::class, "deleteAppDetails"]);
        Route::post('/add-app-details', [AppDetailsController::class, "addDetails"]);
        Route::post('/like-dislike-review', [AppDetailsController::class, "likeDislikeReview"]);
        Route::post('/delete-review-rating', [AppDetailsController::class, "deleteRatingReview"]);
      });

      //Flipbook Analytics API's
      Route::group(["prefix" => "flipbook-analytics"], function () {
        Route::post('/save-views', [FlipbookAnalyticsController::class, 'saveViews']);
        Route::post('/save-downloads', [FlipbookAnalyticsController::class, 'saveDownload']);
        Route::get('/get-flipbook-analytics', [FlipbookAnalyticsController::class, 'getFlipbookAnalytics']);
        Route::post('/save-countries', [FlipbookAnalyticsController::class, 'saveCountries']);
        Route::post('/save-device-name', [FlipbookAnalyticsController::class, 'saveDeviceName']);
        Route::post('/save-clicks', [FlipbookAnalyticsController::class, 'saveClicks']);
      });

      Route::group(["prefix" => "community-post"], function () {
        Route::post('/users', [CommunityPostController::class, "users"]);
        Route::post('/upload-post', [CommunityPostController::class, "uploadpost"]);
        Route::post('/update-post', [CommunityPostController::class, "updatepost"]);
        Route::get('/get-tagged-post', [CommunityPostController::class, "getTaggedPosts"]);
        Route::post('/hide-untag-post', [CommunityPostController::class, "hideUntagPost"]);
        Route::post('/delete-post', [CommunityPostController::class, "deletePost"]);
        Route::get('/get-posts', [CommunityPostController::class, "getPosts"]);
        Route::get('/get-all-users-posts', [CommunityPostController::class, "getAllUsersPosts"]);
        Route::post('/get-post-by-link', [CommunityPostController::class, "getPostByUniqueLink"]);
        Route::post('/like-dislike-post', [CommunityPostController::class, "toggleLikePost"]);
        Route::post('/add-archive', [CommunityPostController::class, "addArchivePost"]);
        Route::post('/unarchive-post', [CommunityPostController::class, "unarchivePost"]);
        Route::post('/delete-archive-post', [CommunityPostController::class, "deleteArchivedPost"]);
        Route::post('/report-post', [CommunityPostController::class, "reportPost"]);
        Route::post('/set-community-profile', [CommunityPostController::class, "setCommunityProfile"]);

        Route::group(["prefix" => "community-post-comments"], function () {
          Route::post('/add-comment', [CommunityPostCommentsController::class, "addcomment"]);
          Route::get('/get-comments', [CommunityPostCommentsController::class, "getcomments"]);
          Route::post('/comment-reply', [CommunityPostCommentsController::class, "commentreply"]);
          Route::post('/delete-comment-reply', [CommunityPostCommentsController::class, "deleteCommentOrReply"]);
          Route::post('/like-dislike-reply', [CommunityPostCommentsController::class, "toggleLikeReply"]);
          Route::post('/like-dislike-comment', [CommunityPostCommentsController::class, "toggleCommentLike"]);
          Route::get('/comment-reply-like-count', [CommunityPostCommentsController::class, "getCommentReplyLikesCount"]);
        });
      });
      Route::group(["prefix" => "community-story"], function () {
        Route::post('/upload-story', [CommunityStoryController::class, "uploadStory"]);
        Route::post('/delete-story', [CommunityStoryController::class, "deleteStory"]);
        Route::get('/get-story', [CommunityStoryController::class, "getStory"]);
        Route::get('/get-all-user-story', [CommunityStoryController::class, "getAllUserStory"]);
        Route::get('/get-archive-story', [CommunityStoryController::class, "getArchiveStories"]);
        Route::post('/delete-archive-story', [CommunityStoryController::class, "deleteArchiveStory"]);
        Route::post('/add-highlights', [CommunityStoryController::class, "addHighlight"]);
        Route::post('/add-stories-to-highlights', [CommunityStoryController::class, "addStoriesToHighlight"]);
        Route::get('/get-highlights', [CommunityStoryController::class, "getHighlights"]);
        Route::post('/delete-highlight', [CommunityStoryController::class, "deleteHighlight"]);
        Route::post('/update-highlight', [CommunityStoryController::class, "updateHighlight"]);
        Route::get('/get-tagged-story', [CommunityStoryController::class, "getTaggedStories"]);
        Route::post('/like-dislike-story', [CommunityStoryController::class, "toggleLikeStory"]);
        Route::get('/get-likescount', [CommunityStoryController::class, "getLikesCount"]);
        Route::post('/report-story', [CommunityStoryController::class, "reportStory"]);
        Route::get('/seen-story', [CommunityStoryController::class, "StorySeenStatus"]);
        Route::get('/get-seenstory', [CommunityStoryController::class, "getStorySeenStatus"]);

        Route::group(["prefix" => "community-draft-story"], function () {
          Route::post('/savedraft-story', [CommunityDraftStoryController::class, "saveDraft"]);
          Route::get('/getsavedraft-story', [CommunityDraftStoryController::class, "getSavedDrafts"]);
          Route::post('/updatedraft-story', [CommunityDraftStoryController::class, "updateDraft"]);
          Route::post('/deleteDraft-story', [CommunityDraftStoryController::class, "deleteDraft"]);
        });
      });

      Route::group(['prefix' => 'community-live-stream'], function () {
        Route::post('/create-live-stream', [CommunityLiveController::class, "createCommunityLiveStream"]);
        Route::get('/get-live-stream', [CommunityLiveController::class, "getCommunityLiveById"]);
        Route::post('/delete-live-stream', [CommunityLiveController::class, "deleteCommunityLiveStream"]);
        Route::post('/add-live-comment', [CommunityLiveController::class, "addLiveComment"]);
        Route::get('/get-live-comment', [CommunityLiveController::class, "getLiveComments"]);
      });
      Route::group(['prefix' => 'dashboard-image'], function () {
        Route::post('/upload-image', [DashboardImagesController::class, "uploadImage"]);
        Route::get('/get-images', [DashboardImagesController::class, "getImages"]);
        Route::post('/delete-image', [DashboardImagesController::class, "deleteImage"]);
        Route::post('/store-image-selection', [DashboardImagesController::class, "storeImageSelection"]);
        Route::get('/get-image-selection', [DashboardImagesController::class, "getImageSelection"]);
      });
      Route::group(['prefix' => 'wallet'], function () {
        Route::post('/buy', [TransactionController::class, "buy"]);
        Route::post('/stats', [TransactionController::class, "dashboard"]);
        Route::post('/users', [TransactionController::class, "users"]);
        Route::post('/transfer', [TransactionController::class, "transfer"]);
        Route::post('/paypal-settings', [TransactionController::class, "paypalSettings"]);
        Route::post('/request', [TransactionController::class, "request"]);
        Route::post('/logs', [TransactionController::class, "logs"]);
        Route::post('/request-logs', [TransactionController::class, "requestLogs"]);
        Route::post('/payment', [PaymentController::class, "add"]);
        Route::get('/external-wallet-master', [ExternalWalletController::class, "externalWalletMasterList"]);
        Route::resource('external-wallet', ExternalWalletController::class);
      });

      Route::group(['prefix' => 'payment'], function () {
        Route::post('/add', [PaymentController::class, "add"]);
      });

      Route::group(['prefix' => 'storage'], function () {
        Route::get('/get-plans', [StorageController::class, "getStoragePlans"]);
        Route::get('/get-user-plan', [StorageController::class, "getUserStoragePlan"]);
        Route::get('/get-storage-service', [StorageController::class, "getStorageServiceDetails"]);
      });

      Route::group(['prefix' => 'account'], function () {
        Route::post('/get-profile', [ProfileController::class, "getProfile"]);
        Route::post('/set-profile', [ProfileController::class, "setProfile"]);
        Route::post('/get-profile-img', [ProfileController::class, "getProfileImg"]);
        Route::post('/get-only-profile-img', [ProfileController::class, "getOnlyProfileImg"]);
        Route::post('/change-password', [ProfileController::class, "changePassword"]);
        Route::post('/set2FAProfile', [ProfileController::class, "set2FAProfile"]);
        Route::post('/verify2FAOTP', [ProfileController::class, "verify2FAOTP"]);
        Route::post('/oFF2FAOTP', [ProfileController::class, "oFF2FAOTP"]);
      });

      Route::group(['prefix' => 'support'], function () {
        Route::post('/add-ticket', [SupportController::class, "addticket"])->middleware('check.storage.limit');
        Route::post('/reply-ticket', [SupportController::class, "replyToTicket"]);
        Route::post('/change-status-ticket', [SupportController::class, "changeTicketStatus"]);
        Route::post('/get-all-tickets', [SupportController::class, "getTicketList"]);
        Route::post('/get-ticket', [SupportController::class, "getTicket"]);
        Route::get('/get-categories', [SupportController::class, "getCategories"]);
        Route::post('/get-questions', [SupportController::class, "getQuestions"]);
        Route::post('/download-ticket-attachment', [SupportController::class, "downloadAttachment"]);
        Route::post('/get-ticket-summary', [SupportController::class, "getTicketSummary"]);
        Route::post('/get-tech-users', [SupportController::class, "getTechUsers"])->middleware('admin');
        Route::post('/assign-tech-user', [SupportController::class, "assignTechUser"])->middleware('admin');
      });

      Route::group(['prefix' => 'help'], function () {
        Route::post('/questions-category', [SupportController::class, "getquestionsbycategory"]);
        Route::post('/question-answer', [SupportController::class, "questionandanswer"]);
        Route::post('/get-titles', [SupportController::class, "getTitles"]);
      });
      Route::group(['prefix' => 'streamdeck'], function () {
        Route::post('/create-channel', [StreamDeckController::class, 'createchannel']);
        Route::post('/update-channel', [StreamDeckController::class, 'updatechannel']);
        Route::post('/delete-channel', [StreamDeckController::class, 'deletechannel']);
        Route::post('/get-channel', [StreamDeckController::class, 'getchannel']);
        Route::post('/get-logo', [StreamDeckController::class, 'getchannellogo']);
        Route::post('/create-live-stream', [StreamDeckController::class, 'createLiveStream']);
        Route::post('/update-live-stream', [StreamDeckController::class, 'updateLiveStream']);
        Route::post('/delete-live-stream', [StreamDeckController::class, 'deleteLiveStream']);
        Route::post('/get-live-stream', [StreamDeckController::class, 'getLiveStream']);
        Route::post('/add-video', [StreamDeckController::class, 'addvideo']);
        Route::post('/update-video', [StreamDeckController::class, 'updatevideo']);
        Route::post('/delete-video', [StreamDeckController::class, 'deletesvideo']);
        Route::post('/get-all-video', [StreamDeckController::class, 'getallvideo']);
        Route::post('/get-video', [StreamDeckController::class, 'getvideo']);
        Route::post('/download-video', [StreamDeckController::class, 'downloadvideo']);
        Route::post('/create-website', [StreamDeckController::class, 'createwebsite']);
        Route::post('/update-website', [StreamDeckController::class, 'updatewebsite']);
        Route::post('/delete-website', [StreamDeckController::class, 'deletewebsite']);
        Route::post('/get-website', [StreamDeckController::class, 'getwebsite']);
        Route::post('/schedule-channel', [StreamDeckController::class, 'schedulechannel']);
        Route::post('/update-schedule-channel', [StreamDeckController::class, 'updateschedulechannel']);
        Route::post('/delete-schedule-channel', [StreamDeckController::class, 'deleteschedulechannel']);
        Route::post('/clear-schedule-channel', [StreamDeckController::class, 'clearschedulechannel']);
        Route::post('/get-schedule-channel', [StreamDeckController::class, 'getschedulechannel']);
        Route::post('/looped-add-schedule-video', [StreamDeckController::class, 'loopedaddschedulevideo']);
        Route::post('/delete-looped-schedule-video', [StreamDeckController::class, 'deleteloopedschedulevideo']);
        Route::post('/get-looped-schedule-video', [StreamDeckController::class, 'getloopedschedulevideo']);
        Route::post('/rearrange-schedule-video', [StreamDeckController::class, 'rearrangevideos']);
        Route::post('/copy-channel', [StreamDeckController::class, 'copychannel']);
        Route::post('/analytics', [StreamDeckController::class, 'analytics']);
        Route::post('/check-channel-name', [StreamDeckController::class, 'checkChannelName']);
        Route::post('/remove-destination-channel', [StreamDeckController::class, 'removedestinationchannel']);
        Route::post('/remove-blank-space', [StreamDeckController::class, 'removeblankspace']);
        Route::post('/m3u8', [StreamDeckController::class, 'conCatenate']);
        Route::post('/set-broadcast-status', [StreamDeckController::class, 'setBroadcastStatus']);
        Route::post('/add-to-scheduler', [StreamDeckController::class, 'addScheduler']);
        Route::post('/show-from-scheduler', [StreamDeckController::class, 'showSchedule']);
        Route::post('/delete-programs', [StreamDeckController::class, 'deletePrograms']);
        Route::post('/set-programs-channels', [StreamDeckController::class, 'setSchedule'])->middleware('check.storage.limit');
        Route::post('/get-programs-channels', [StreamDeckController::class, 'getScheduleData']);
        Route::post('/concate-videos', [StreamDeckController::class, 'conCatenate']);
        Route::post('/set-stream-status', [StreamDeckController::class, 'setStreamStatus']);
        Route::post('/upload-m3u8-stream', [StreamDeckController::class, 'uploadM3U8Stream']); // camera
        Route::get('/videos/{userId}', [StreamDeckController::class, 'getVideos']); // camera get videos
        Route::post('/videos/{userId}/{folder}', [StreamDeckController::class, 'deleteVideo']); // camera delete
        Route::post('/upload-m3u8', [StreamDeckController::class, 'uploadM3U8']); // obs
        Route::get('/manifest-files/{userId}', [StreamDeckController::class, 'getManifestFiles']); //obs
        Route::delete('/delete-manifest-file/{userId}/{key}/{fileName}', [StreamDeckController::class, 'deleteManifestFile']); // obs
        Route::post('/delete-program', [StreamDeckController::class, 'deleteProgram']); // obs
        Route::post('/tally-settings', [StreamDeckController::class, 'tallySettings']); // obs
        Route::post('/fetch-Programs', [StreamDeckController::class, 'fetchProgramDetailsByDate']);
        Route::post('/is-scheduled', [StreamDeckController::class, 'isChannelSchedule']);
        Route::get('/get-channel-by-user', [StreamDeckController::class, 'getChannelByUser']);
        Route::post('/update-program-schedule', [StreamDeckController::class, 'updateProgramSchedule']);
        Route::post('/update-live-stream-url', [StreamDeckController::class, 'updateLiveStreamUrl']);
        Route::post('/add-to-media-library', [StreamDeckController::class, 'addStreamToMediaLibrary']);
        Route::get('/get-videos-gallegry', [StreamDeckController::class, 'getVideosGallery']);
        Route::delete('/delete-video/{folder}', [StreamDeckController::class, 'deleteVideoGallery']);
        //charts
        Route::get('/tvlivestreams/chartdata', [StreamDeckController::class, 'getLiveStreamChartData']);
        Route::get('/analytics/overview', [StreamDeckController::class, 'getAnalyticsOverview']);
        Route::get('/analytics/overviewlive', [StreamDeckController::class, 'getAnalyticsOverviewLive']);
        Route::get('/analytics/channel/{channel_id}/views', [StreamDeckController::class, 'getChannelViews']);
        Route::get('/analytics/all-channels/views', [StreamDeckController::class, 'getAllChannelsViews']);
        Route::post('/get-trackView', [StreamDeckController::class, 'trackView']);
        Route::get('/get-locations', [StreamDeckController::class, 'getLocations']);
        Route::get('/get-country-percentages', [StreamDeckController::class, 'getCountryPercentages']);
        Route::post('analytics/download-excel', [StreamDeckController::class, 'downloadChartExcel']);
        Route::post('/analytics/overview-stream', [StreamDeckController::class, 'getAnalyticsStreamOverview']);

        Route::get('/get-channelfor-view', [StreamDeckController::class, 'getChannelforView']);
        Route::post('/analytics/download-pdf', [StreamDeckController::class, 'downloadChartPdf']);

        //webm videos
        Route::post('/upload-video', [StreamDeckController::class, 'upload'])->middleware('check.storage.limit');
        Route::post('/get-webm-videos/{userId}', [StreamDeckController::class, 'getWebmVideos']);
        Route::post('/videos/deletewebvideos', [StreamDeckController::class, 'deleteWebVideo']);

        //PDF data
        Route::post('/get-broadcast-pdf', [StreamDeckController::class, 'getBroadcastPDF']);
        //sent notifications to live
        Route::post('/send-live-notification-to-connected-user', [StreamDeckController::class, 'sendLiveNotiFicationToConnectedUser']);
        //user connections
        Route::get('/get-user-connections', [StreamDeckController::class, 'getUserConnections']);
        Route::get('/get-tally-file-data/{channelId}', [StreamDeckController::class, 'getTallySettingData']);
        //send mail to connected user
        Route::post('/send-mail-to-connection', [StreamDeckController::class, 'sendMailToConnections']);
        //subscription
        Route::get('/get-subscription-data', [StreamDeckController::class, 'checkService']);
        //New Hls video
        Route::post('/add-new-hls-video', [StreamDeckController::class, 'addHLSvideo']);
      });
      Route::group(['prefix' => 'marketplace'], function () {
        Route::post('add-product', [MarketplaceController::class, 'addProducts']);
        Route::post('add-store', [MarketplaceStoreController::class, 'addStore']);
        Route::post('add-product-specifications', [ProductController::class, 'addProductSpecification']);
        Route::post('update-product', [MarketplaceController::class, 'updateProduct']);
        Route::post('delete-product', [MarketplaceController::class, 'deleteProduct']);
        Route::post('store-product-questions', [MarketplaceController::class, 'storeProductQuestions']);
        Route::post('store-product-reviews', [MarketplaceController::class, 'storeProductReviews']);
        Route::post('manage-cart', [MarketplaceController::class, 'manageCart']);
        Route::post('get-cart', [MarketplaceController::class, 'getuserCart']);
        Route::post('add-banner', [MarketplaceController::class, 'addSiteBanner']);
        Route::resource('address', AddressController::class);
        Route::post('get-store', [MarketplaceStoreController::class, 'getStoresprivate']);
        Route::post('update-store', [MarketplaceStoreController::class, 'updateStore']);
        Route::post('delete-store', [MarketplaceStoreController::class, 'deleteStore']);
        Route::post('product-category', [MarketplaceStoreController::class, 'storeCategory']);
        Route::post('product-update-category/{id}', [MarketplaceStoreController::class, 'updateCategory']);
        Route::post('product-category-delete/{id}', [MarketplaceStoreController::class, 'deleteCategory']);
        Route::post('product-sub-category', [MarketplaceStoreController::class, 'storeSubCategory']);
        Route::post('product-update-sub-category/{id}', [MarketplaceStoreController::class, 'updateSubCategory']);
        Route::post('product-delete-sub-category/{id}', [MarketplaceStoreController::class, 'deleteSubCategory']);
        Route::post('product-sub-category-tag', [MarketplaceStoreController::class, 'storeSubCategoryTag']);
        Route::post('product-update-sub-category-tag/{id}', [MarketplaceStoreController::class, 'updateSubCategoryTag']);
        Route::post('product-delete-sub-category-tag/{id}', [MarketplaceStoreController::class, 'deleteSubCategoryTag']);
        Route::post('get-tags-data', [MarketplaceStoreController::class, 'getTags']);
        Route::post('get-categories-data', [MarketplaceStoreController::class, 'getCategories']);
        Route::post('get-sub-categories', [MarketplaceStoreController::class, 'getSubCategories']);
        Route::post('sellers/list', [SellerBuyerController::class, 'getSellersList']);
        Route::post('buyers/list', [SellerBuyerController::class, 'getBuyersList']);
        Route::post('store-category/add', [MarketplaceStoreController::class, 'storeAddCat']);
        Route::post('store-category/update/{id}', [MarketplaceStoreController::class, 'updateStoreCat']);
        Route::post('store-category/delete/{id}', [MarketplaceStoreController::class, 'deleteStoreCat']);
        Route::post('get-store-category', [MarketplaceStoreController::class, 'getStoreCategories']);
        Route::post('marketplace-slider', [MarketplaceController::class, 'marketplaceSlider']);
        Route::post('marketplace-slider/update/{id}', [MarketplaceController::class, 'updateSlider']);
        Route::post('marketplace-slider/get/{id}', [MarketplaceController::class, 'getSlider']);
        Route::post('marketplace-slider/delete/{id}', [MarketplaceController::class, 'deleteSlider']);
        Route::post('paid-banners', [MarketplaceController::class, 'createPaidBanner']);
        Route::post('get-paid-banners', [MarketplaceController::class, 'getPaidBanners']);
        Route::post('super-admin-table-product-data-rating', [MarketplaceController::class, 'getsuperadminProductListrating']);
        Route::post('get-paid-banners-merchant', [MarketplaceController::class, 'getPaidBannersMerchant']);
        Route::post('table-product-data-rating-merchant', [MarketplaceController::class, 'getProductListrating']);
        Route::post('merchant-shippers', [MarketplaceController::class, 'tableMerchantShippers']);
        Route::post('add-shipper', [MarketplaceController::class, 'addShipper']);
        Route::post('merchant-shippers', [MarketplaceController::class, 'tableMerchantShippers']);
        Route::post('my-table-order-data-marketplace', [MarketplaceController::class, 'getMyOrderList']);
        Route::post('my-table-delivery-order-data-marketplace', [MarketplaceController::class, 'getDeliveryOrderList']);
        /** Marketplcae Dashboard**/
        Route::post('my-table-order-data-dashboard', [MarketplaceController::class, 'getMyOrderListDashboard']);
        Route::post('get-merchant-earnings', [MarketplaceController::class, 'getMerchantEarnings']);
        Route::post('marketplace-my-store-show-list', [MarketplaceController::class, 'getStoreList']);
        Route::post('marketplace-my-product-show-list', [MarketplaceController::class, 'getProductList']);
        Route::post('marketplace/Marketplace_access/Addpermission', [MarketplaceController::class, 'Addpermission']);
        Route::post('merchant-product-visitor', [MarketplaceController::class, 'getMerchantVisitorsProductList']);
        Route::post('filter-elements', [MarketplaceController::class, 'filterElements']);
        Route::post('email-unsubscription', [MarketplaceController::class, 'emailUnsubscription']);
        Route::post('email-resubscription/{email_resubscribe}', [MarketplaceController::class, 'emailResubscription']);
        Route::post('subscribe', [MarketplaceController::class, 'subscribe']);
        Route::post('get-admin-merchant-earnings', [MarketplaceController::class, 'getAdminMerchantEarnings']);
        Route::post('marketplace-payment', [SellerBuyerController::class, 'processOrderTransaction']);
        Route::get('order-details/{order_id}', [SellerBuyerController::class, 'orderDetails']);
        Route::post('super-admin-table-order-data', [MarketplaceController::class, 'getSuperadminOrderList']);
        Route::post('update-order-status', [MarketplaceController::class, 'updateOrderStatus']);
        Route::post('update-order-status-cancel', [SellerBuyerController::class, 'cancelOrderTransaction']);
        Route::post('super-admin-table-product-data', [MarketplaceController::class, 'getSuperAdminProductList']);
        Route::post('table-buyers-data', [MarketplaceController::class, 'getBuyerList']);
        Route::post('table-buyer-orders', [MarketplaceController::class, 'buyerOrderTable']);
        Route::post('delete-header-category/{categoryId}', [MarketplaceController::class, 'deleteHeaderCategory']);
        Route::post('edit-header-category/{categoryId}', [MarketplaceController::class, 'editHeaderCategory']);
        Route::post('marketplace-top-product-category', [MarketplaceController::class, 'ProductsCategory']);
        Route::post('address-validation', [SellerBuyerController::class, 'addressValidation']);
        Route::post('shipping-charges', [SellerBuyerController::class, 'ShippingCharges']);
        Route::post('product-shipping-charges', [SellerBuyerController::class, 'ProductShippingCharges']);
        Route::post('create-shipment', [SellerBuyerController::class, 'createShipment']);
        Route::get('get-shipping-label/{trackingNumber}', [SellerBuyerController::class, 'getShippingLabel']);
        Route::post('product-pickup', [SellerBuyerController::class, 'ProductPickup']);
        Route::post('tracking-by-number', [SellerBuyerController::class, 'TrackingByNumber']);
        Route::post('service-package-options', [SellerBuyerController::class, 'servicePackageOptions']);
        Route::post('cancel-shipment', [SellerBuyerController::class, 'cancelShipment']);
        Route::post('save-seller-details', [SellerBuyerController::class, 'saveSellerDetails']);
        Route::post('shipment-settings', [SellerBuyerController::class, 'getShipmentSettings']);
        Route::post('getmarket-placeload-morelist', [ProductController::class, 'getMarketPlaceLoadMoreList']);
        Route::post('add-to-wishlist', [ProductController::class, 'addToWishList']);
        Route::post('marketplace-enable-disable-product', [ProductController::class, 'enableDisableProduct']);
        Route::post('marketplace-enable-disable-store', [ProductController::class, 'enableDisableStore']);
        Route::post('admin-status-change', [ProductController::class, 'adminStatusChange']);
        Route::post('allow-product-notify', [ProductController::class, 'allowProduct']);
        Route::post('product-inquiries-list', [ProductController::class, 'productInquiriesList']);
        Route::post('product-return-replacelist', [ProductController::class, 'productReturnReplaceList']);
        Route::post('product-visited-count', [ProductController::class, 'getProduct']);
        //Live
        Route::post('live/get-live-stream', [MarketPlaceLiveController::class, 'getmarketplacelivestream']);
        Route::post('live/create-live-stream', [MarketPlaceLiveController::class, 'createmarketplacelivestream']);
        Route::post('live/update-live-stream', [MarketPlaceLiveController::class, 'updatemarketplacelivestream']);
        Route::post('live/delete-live-stream', [MarketPlaceLiveController::class, 'deletemarketplacelivestream']);
        Route::post('live/update-live-stream-url', [MarketPlaceLiveController::class, 'updateLiveStreamUrl']);
        Route::post('live/set-stream-status', [MarketPlaceLiveController::class, 'setStreamStatus']);
        Route::post('live/add-product-pin', [MarketPlaceLiveController::class, 'addProductPin']);
        Route::post('influencer-follow', [MarketPlaceLiveController::class, 'toggleFollow']);
        Route::get('get-influencer-followers-info', [MarketPlaceLiveController::class, 'getFollowerInfo']);
        Route::get('get-product-count', [MarketPlaceLiveController::class, 'getProductCount']);
        Route::get('get-stream-product', [MarketPlaceLiveController::class, 'getStreamProducts']);
        Route::get('get-influencer-dashboard', [MarketPlaceLiveController::class, 'getInfluencerDashboard']);

        //Coupan
        Route::post('coupon/add', [ProductController::class, 'addCoupon']);
        Route::get('coupon/list', [ProductController::class, 'listCoupons']);
        Route::post('coupon/apply', [ProductController::class, 'applyCoupon']);
        Route::post('coupons/validity', [ProductController::class, 'checkValidity']);
        Route::post('coupon/delete/{id}', [ProductController::class, 'deleteCoupon']);
        Route::post('coupon/update/{id}', [ProductController::class, 'updateCoupon']);
      });
      Route::group(['prefix' => 'flipbook'], function () {
        Route::post('update-collection/{id}', [FlipbookCollectionController::class, "updateCollection"]);
        Route::resource('collection', FlipbookCollectionController::class);

        Route::post('/upload', [FlipbookController::class, 'upload'])->middleware('check.storage.limit');
        Route::get('/get-flipbooks', [FlipbookController::class, 'getFlipbooks']);
        Route::post('/get-by-collection', [FlipbookController::class, 'getByCollectionList']);
        Route::delete('/delete/{id}', [FlipbookController::class, 'destroy']);


        Route::post('/publish', [FlipbookPublicationController::class, 'publish']);
        Route::get('/get-publication', [FlipbookPublicationController::class, 'getPublication']);
        Route::post('/update-publication', [FlipbookPublicationController::class, 'updatePublication']);
        Route::post('/publication', [FlipbookPublicationController::class, 'getPublicationList']);

        Route::post('/sell-publication', [FlipbookPublicationController::class, 'sellPublication']);
        Route::delete('/delete-publication/{id}', [FlipbookPublicationController::class, 'deletePublication']);
        Route::post('/publication/sell-list', [FlipbookPublicationController::class, 'getPublicationSellList']);
        Route::post('/publication/purchase-list', [FlipbookPublicationController::class, 'getPublicationPurchaseList']);
        Route::post('/publication/sold-list', [FlipbookPublicationController::class, 'getPublicationSoldList']);
        Route::post('/purchase-publication', [FlipbookPublicationController::class, 'purchasePublication']);

        Route::post('/share', [FlipbookController::class, 'share']);
        Route::get('/fetch-share', [FlipbookController::class, 'fetchSharedCollection']);
        Route::post('/publisher-profile', [FlipbookPublicationController::class, 'publicationProfile']);
        Route::post('/convert-to-pdf', [FlipbookController::class, 'convertToPdf']);
        Route::post('/review/add-review', [FlipbookPublicationController::class, 'addflipbookReviews']);
      });
      Route::group(['prefix' => 'mail'], function () {
        Route::post('/send-mail', [MailController::class, 'sendEmail']);
        Route::post('/get-inbox-mail-list', [MailController::class, 'getInboxList']);
        Route::post('/favourite-inbox-list-email', [MailController::class, 'favouriteInboxMail']);
        Route::post('/get-favourite-list', [MailController::class, 'getFavouriteList']);
        Route::post('/get-trash-list', [MailController::class, 'getTrashList']);
        Route::post('/get-all-mail-list', [MailController::class, 'getAllMailList']);
        Route::post('/get-sent-mail-list', [MailController::class, 'getSentMailList']);
        Route::post('/change-archive-status', [MailController::class, 'setArchiveEmailStatus']);
        Route::post('/delete-email', [MailController::class, 'deleteEmail']);
        Route::post('/get-email', [MailController::class, 'getEmail']);
        Route::post('/get-draft-email', [MailController::class, 'getDraftEmail']);
        Route::post('/read-email', [MailController::class, 'readEmail']);
        Route::post('/get-spam-email', [MailController::class, 'getSpamEmail']);
        Route::post('/reply-email', [MailController::class, 'replyEmail']);
        Route::post('/delete-reply-email', [MailController::class, 'deleteReplyEmail']);
        Route::post('/undo-trash-email', [MailController::class, 'undoTrashEmail']);
        Route::get('/show-labels-count', [MailController::class, 'getLabelsCount']);
        Route::post('/add-snippet', [MailController::class, 'addSnippet']);
        Route::post('/delete-snippet', [MailController::class, 'deleteSnippet']);
        Route::post('/get-snippet', [MailController::class, 'getSnippet']);
        Route::post('/upload-attachment', [MailController::class, 'uploadAttachment'])->middleware('check.storage.limit');
        Route::post('/delete-attachment', [MailController::class, 'deleteAttachment']);
        Route::post('/get-suggestions', [MailController::class, 'getSuggestions']);
        Route::post('/undo-spam-email', [MailController::class, 'undoSpamEmail']);
        Route::post('/add-label', [MailController::class, 'addLabel']);
        Route::post('/delete-label', [MailController::class, 'deleteLabel']);
        Route::post('/get-label', [MailController::class, 'getLabel']);
        Route::post('/update-email-label', [MailController::class, 'updateEmailLabel']);
        Route::post('/filter-label', [MailController::class, 'getFilterlabel']);
      });
      Route::group(['prefix' => 'public'], function () {
        Route::post('/check-token', [PublicAPIController::class, "checkToken"]);
        Route::post('/users', [PublicAPIController::class, "getusersname"]);
        Route::post('/get-notifications', [PublicAPIController::class, "getNotifications"]);
        Route::post('/set-notification-status', [PublicAPIController::class, "setNotificationsStatus"]);
        Route::post('/get-role', [PublicAPIController::class, "getRole"]);
        Route::post('/live/create-influencer', [MarketPlaceLiveController::class, 'makeInfluencer']);
        Route::get('/live/check-influencer', [MarketPlaceLiveController::class, 'checkInfluencer']);
      });
      Route::group(['prefix' => 'subscription'], function () {
        Route::get('/get-package-list', [SubscriptionController::class, "getPackageList"]);
        Route::post('/subscribe-package', [SubscriptionController::class, "subscribePackage"]);
        Route::post('/get-subscribed-package', [SubscriptionController::class, "getSubscribedPackage"]);
        Route::post('/get-package', [SubscriptionController::class, "getPackage"]);
        Route::post('/get-invoice', [SubscriptionController::class, "getInvoice"]);
        Route::post('/get-invoice-list', [SubscriptionController::class, "getInvoiceList"]);
        Route::post('/get-service-invoice', [SubscriptionController::class, "getServiceInvoice"]);
        Route::get('/get-services', [SubscriptionController::class, "getServices"]);
        Route::post('/get-service-detail', [SubscriptionController::class, "getServiceDetail"]);
        Route::post('/subscribe-service', [SubscriptionController::class, "subscribeService"]);
        Route::post('/subscribe-external-service', [SubscriptionController::class, "subscribeExternalService"]);
        Route::post('/downgrade-package', [SubscriptionController::class, "downgradePackage"]);
        Route::post('/downgrade-service', [SubscriptionController::class, "downgradeService"]);
        Route::post('/get-plan', [SubscriptionController::class, "getPlan"]);
        Route::post('/validate-promocode', [SubscriptionController::class, "validatePromocode"]);
      });
      Route::group(['prefix' => 'blog'], function () {
        Route::post('/add-blog', [BlogController::class, "addBlog"]);
        Route::post('/update-blog', [BlogController::class, "updateBlog"]);
        Route::post('/delete-blog', [BlogController::class, "deleteBlog"]);
        Route::post('/add-update-category', [BlogController::class, "addOrUpdateCategory"]);
        Route::post('/delete-category', [BlogController::class, "deleteCategory"]);
      });

      Route::group(['prefix' => 'game'], function () {
        Route::post('/get-sport', [SportController::class, "getSports"]);
        Route::post('/add-sport', [SportController::class, "addSport"]);
        Route::post('/update-sport', [SportController::class, "updateSport"]);
        Route::post('/delete-sport', [SportController::class, "deletesport"]);

        Route::group(['prefix' => 'tournament'], function () {
          Route::post('/get-tournament', [TournamentController::class, "getTournament"]);
          Route::post('/get-list-tournament', [TournamentController::class, "getTournamentList"]);
          Route::post('/add-tournament', [TournamentController::class, "addTournament"]);
          Route::post('/update-tournament', [TournamentController::class, "updateTournament"]);
          Route::post('/delete-tournament', [TournamentController::class, "deteteTournament"]);
        });
        Route::group(['prefix' => 'team'], function () {
          Route::post('/get-team', [TeamController::class, "getTeam"]);
          Route::post('/get-list-team', [TeamController::class, "getTeamList"]);
          Route::post('/add-team', [TeamController::class, "addTeam"]);
          Route::post('/update-team', [TeamController::class, "updateTeam"]);
          Route::post('/delete-team', [TeamController::class, "deteteTeam"]);
        });
        Route::group(['prefix' => 'match'], function () {
          Route::post('/get-match', [MatchController::class, "getMatch"]);
          Route::post('/get-list-match', [MatchController::class, "getMatchList"]);
          Route::post('/add-match', [MatchController::class, "addMatch"]);
          Route::post('/update-match', [MatchController::class, "updateMatch"]);
          Route::post('/delete-match', [MatchController::class, "deleteMatch"]);
        });
        Route::group(['prefix' => 'player'], function () {
          Route::post('/get-player', [PlayerController::class, "getPlayer"]);
          Route::post('/get-list-player', [PlayerController::class, "getPlayerList"]);
          Route::post('/add-player', [PlayerController::class, "addPlayer"]);
          Route::post('/update-player', [PlayerController::class, "updatePlayer"]);
          Route::post('/delete-player', [PlayerController::class, "deletePlayer"]);
        });
        Route::group(['prefix' => 'version-control'], function () {
          Route::post('/get-version-control', [VersionController::class, "getVersionControl"]);
          Route::post('/get-list-version-control', [VersionController::class, "getVersionControlList"]);
          Route::post('/add-version-control', [VersionController::class, "addVersionControl"]);
          Route::post('/update-version-control', [VersionController::class, "updateVersionControl"]);
          Route::post('/delete-version-control', [VersionController::class, "deteteVersionControl"]);
        });

        Route::group(['prefix' => 'basketball-score'], function () {
          Route::post('/add-score', [BasketballScoreBoardController::class, "addScore"]);
          // Route::post('/get-list-player', [BasketballScoreBoardController::class, "getPlayerList"]);
          // Route::post('/add-player', [BasketballScoreBoardController::class, "addPlayer"]);
        });
      });
      Route::group(['prefix' => 'coin'], function () {
        Route::post('/make-investment', [InvestmentController::class, "makeInvestment"]);
        Route::post('/submit-review', [CoinController::class, "addFeedback"]);
        Route::post('/increment-view', [CoinController::class, "incrementView"]);
        Route::post('/get-token-value', [CoinController::class, "getTokenValue"]);
        Route::get('/get-user-investment', [InvestmentController::class, "getUserInvestment"]);
        Route::get('/get-investment-data', [InvestmentController::class, "getInvestmentData"]);
      });
      Route::group(['prefix' => 'kyc'], function () {
        Route::post('/submit-kyc', [KYController::class, "submitKYC"]);
        Route::get('/check-kyc', [KYController::class, "checkKYC"]);
        // Route::post('/kyc-approve-reject', [KYController::class, "approveRejectKYC"]);
        Route::get('/get-user-kyc-data', [KYController::class, "getuserkycdata"]);
        Route::post('/re-submit-kyc', [KYController::class, "reSubmitkyc"]);
      });
      Route::group(['prefix' => 'connection'], function () {
        Route::post('/users', [ConnectionController::class, "getUsers"]);
        Route::post('/follow', [ConnectionController::class, "toggleFollow"]);
        Route::post('/get-invitation', [ConnectionController::class, "getInvitation"]);
        Route::post('/ignore-invitation', [ConnectionController::class, "ignoreInvitation"]);
        Route::post('/request', [ConnectionController::class, "requestToConnect"]);
        Route::post('/accept', [ConnectionController::class, "acceptToConnect"]);
        Route::post('/reject', [ConnectionController::class, "removeInvitation"]);
        Route::post('/delete', [ConnectionController::class, "deleteConnection"]);
        Route::post('/summary', [ConnectionController::class, "connectionSummary"]);

        Route::post('/invitation', [ConnectionController::class, "connectionInvitation"]);
        Route::post('/list', [ConnectionController::class, "connectionList"]);
        Route::post('/follow/list', [ConnectionController::class, "followList"]);
        Route::post('/request/list', [ConnectionController::class, "connectionRequests"]);
        Route::post('/remove-follower', [ConnectionController::class, "removeFollowers"]);
      });
      Route::group(['prefix' => 'three-d'], function () {
        Route::post('/upload-threed-file', [ThreeDController::class, 'uploadThreeDFile'])->middleware('check.storage.limit');
        Route::post('/delete-threed-file', [ThreeDController::class, 'deleteThreeDFile']);
        Route::get('/get-all-threed-file', [ThreeDController::class, 'getUserThreeD']);
        Route::post('/get-threed-file', [ThreeDController::class, 'getThreeDFile']);
      });
      Route::group(['prefix' => 'three-d-product'], function () {
        Route::post('/get-threed-product', [ThreeDproductController::class, 'getThreedproduct']);
        Route::post('/add-threed-product', [ThreeDproductController::class, 'addThreedproduct']);
        Route::post('/update-threed-product', [ThreeDproductController::class, 'updateThreedproduct']);
        Route::post('/delete-threed-product', [ThreeDproductController::class, 'deleteThreedproduct']);
      });
      Route::group(['prefix' => 'podcast'], function () {
        Route::post('/add-podcast', [PodcastController::class, 'addPodcast']);
        Route::post('/update-podcast', [PodcastController::class, 'updatePodcast']);
        Route::post('/delete-podcast', [PodcastController::class, 'deletePodcast']);
        Route::get('/get-podcast/{id}', [PodcastController::class, 'getPodcastById']);
        Route::post('/add-episode', [PodcastController::class, 'addEpisode']);
        Route::post('/update-episode', [PodcastController::class, 'updateEpisode']);
        Route::post('/delete-episode', [PodcastController::class, 'deleteEpisode']);
        Route::get('/get-episode/{id}', [PodcastController::class, 'getEpisodeById']);
        Route::get('/user/podcasts/{podcast_id?}', [PodcastController::class, 'getUserPodcasts']);
        Route::post('/add-category-tags', [PodcastController::class, 'addPodcastCategoriesAndTags']);
        Route::post('/like-episode', [PodcastController::class, 'likeEpisode']);
        Route::get('/get-like-episode-list', [PodcastController::class, 'getLikedEpisodes']);

        //Artist Routes
        Route::post('/artist/update', [ArtistController::class, 'updateArtist']);
        Route::get('/artist/follow/{id}', [ArtistController::class, 'toggleFollowArtist']);
        Route::get('/artist/get-follow-podcast-list', [ArtistController::class, 'getFollowPodcastList']);
      });

      // API's for talk.silocloud.io
      Route::group(['prefix' => 'silotalk'], function () {
        Route::post('/upload-chat-files', [TalkController::class, "uploadSiloTalkChatFiles"])->middleware('check.storage.limit');
        Route::post('/add-call-notification', [TalkController::class, "addNotification"]);
      });
      // API's for connect
      Route::group(['prefix' => "connect"], function () {
        Route::post("/create-meeting-code", [ConnectController::class, "createMeeting"]);
        Route::post("/verify-meeting-code/{room_name}", [ConnectController::class, "verifyMeeting"]);
        Route::post("/invite-to-meeting/{room_name}", [ConnectController::class, "inviteToMeeting"]);
        Route::post("/update-settings", [ConnectController::class, "updateSettings"]);
        Route::post("/close-meeting/{room_name}", [ConnectController::class, "closeMeeting"]);
        Route::post("/schedule-meeting", [ConnectController::class, "scheduleMeeting"]);
        Route::post("/delete-schedule-meeting", [ConnectController::class, "deleteScheduleMeeting"]);
        Route::post("/schedule-meeting-list", [ConnectController::class, "scheduleMeetingList"]);
        Route::post("/set-meeting-visibility/{room_name}", [ConnectController::class, "setMeetingVisibility"]);
        Route::post("/admin-join/{room_name}", [ConnectController::class, "adminJoin"]);
      });
      Route::group(['prefix' => "live-chat"], function () {
        Route::post("/send-chat", [Livechatting::class, "index"]);
        Route::post("/get-chat", [Livechatting::class, "getChat"]);
      });

      // API's for On-Demand channel
      Route::group(['prefix' => "tv"], function () {

        // API's for TV-Series
        Route::post('/add-series', [TvController::class, "addTvSeries"]);
        Route::post('/update-series', [TvController::class, 'updateTvSeries']);
        Route::post('/delete-series', [TvController::class, 'deleteTvSeries']);

        // API's for TV-Seasons
        Route::post('/add-series-seasons', [TvController::class, 'addSeriesSeasons']);
        Route::post('/update-series-seasons', [TvController::class, 'updateSeriesSeason']);
        Route::post('/delete-series-seasons', [TvController::class, 'deleteSeason']);

        // API's for TV-Seasons-Episodes
        Route::post('/add-season-episode', [TvController::class, 'addSeasonEpisode']);
        Route::post('/update-season-episode', [TvController::class, 'UpdateSeasonEpisode']);
        Route::post('/delete-season-episode', [TvController::class, 'deleteSeasonEpisode']);

        //API's for User-episodes-watchlist
        Route::post('/add-watchlist', [TvController::class, 'addEpisodeWatchlist']);
        Route::post('/delete-watchlist', [TvController::class, 'DeleteWatchlistEpisodes']);

        //API's for User-history
        Route::post('/add-user-history', [TvController::class, 'AddUserWatchHistory']);
        Route::post('/update-user-history', [TvController::class, 'UpdateUserWatchHistory']);
        Route::post('/delete-user-history', [TvController::class, 'deleteUserHistory']);

        //API's for User-favorite-series
        Route::post('/add-favorite-series', [TvController::class, 'addFavoritesSeries']);
        Route::post('/update-favorite-series', [TvController::class, 'UpdateFavoriteSeries']);
        Route::post('/delete-favorite-series', [TvController::class, 'deleteFavoriteSeries']);

        //API's for review-of-series
        Route::post('/add-series-review', [TvController::class, 'addSeriesReview']);
        Route::post('/update-series-review', [TvController::class, 'UpdateSeriesReview']);
        Route::post('/delete-series-review', [TvController::class, 'deleteSeriesReview']);

        //All get API's for On-Demand
        Route::get('/get-on-demand-channel', [TvController::class, 'getOnDemandChannels']);
        Route::post('/get-series', [TvController::class, 'getSeries']);
        Route::post('/get-seasons', [TvController::class, 'getSeasons']);
        Route::post('/set-genre', [TvController::class, 'setGenre']);
        Route::get('/get-recommended-series', [TvController::class, 'getRecommendedSeries']);
        Route::post('/update-episode-sequence', [TvController::class, 'updateEpisodeSequence']);
        Route::post('/get-episodes', [TvController::class, 'getEpiosdes']);
        Route::get('/get-recently-added-seies', [TvController::class, 'getRecentlyAdded']);
        Route::get('/get-continues-watching-episode', [TvController::class, 'getContinueWatching']);
        Route::get('/get-watchlist-episodes', [TvController::class, 'getWatchList']);
        Route::get('/get-favorite-series', [TvController::class, 'getFavroitesSeries']);
        Route::post('/get-reviewed-series', [TvController::class, 'getSeriesReviews']);
        Route::post('/get-episode-by-id', [TvController::class, 'getEpisodeId']);
      });
      require __DIR__ . '/admin-api.php';
    });
  });
  require __DIR__ . '/public-api.php';
});
