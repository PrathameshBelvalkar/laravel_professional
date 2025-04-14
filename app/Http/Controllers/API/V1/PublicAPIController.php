<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\Account\FrontendSettingRequest;
use App\Http\Requests\Public\AppsRequest;
use App\Models\Account\FrontendSetting;
use App\Models\Blog\BlogNav;
use App\Models\City;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;
use App\Models\App;
use App\Models\Role;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Mail\SendMail;
use App\Models\QrCode;
use App\Models\Country;
use App\Models\Website;
use App\Models\ShortUrl;
use App\Models\Blog\Blog;
use App\Models\StateModel;
use App\Helpers\TextHelper;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use App\Models\CollectionFb;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\CalendarEvent;
use App\Models\coin\NewsModel;
use App\Models\Public\SiteSetting;
use App\Models\StreamDeck\Channel;
use Illuminate\Support\Facades\DB;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProducts;
use App\Models\Marketplace\LiveProductPin;
use App\Models\StoreProductReviews;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Subscription\Service;
use Illuminate\Support\Facades\Mail;
use App\Models\MarketplaceSubCategory;
use App\Models\StreamDeck\TvLivestream;
use Illuminate\Support\Facades\Storage;
use App\Models\Silosecuredata\ContactUs;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Blog\GetBlogRequest;
use App\Models\Flipbook\CollectionFlipbook;
use App\Models\Marketplace\MarketplaceStore;
use App\Http\Requests\Public\GetSiteSettingRequest;
use App\Http\Requests\Public\GetNotificationRequest;
use App\Models\Subscription\UserServiceSubscription;
use App\Models\Silosecuredata\SilosecureConsultation;
use App\Models\Flipbook\Flipbook;
use App\Http\Requests\Silosecuredata\addContactusRequest;
use App\Http\Requests\Public\SetNotificationStatusRequest;
use App\Http\Requests\Silosecuredata\AddSilosecureConsultationRequest;
use App\Models\Blog\BlogCategory;
use App\Models\Mail\MailColors;
use App\Models\Mail\MailLabels;
use App\Models\Mail\MailNavs;

class PublicAPIController extends Controller
{
  public function getCountries()
  {
    try {
      $selectedColumns = ['id', 'name', "shortname", 'phonecode'];
      $countries = Country::all()->select($selectedColumns);
      if ($countries && $countries->toArray()) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Country data retrieved successfully', 'toast' => false, 'data' => ["countries" => $countries->toArray()]]);
      } else {

        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Country data not found', 'toast' => true,]);
      }
    } catch (\Exception $e) {
      Log::info('public API error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function getStates(Request $request)
  {
    try {
      $selectedColumns = ['id', 'name', "country_id"];
      if (isset($request->country_id)) {
        $states = StateModel::where('country_id', $request->country_id)->select($selectedColumns)->get();
      } else {
        $states = StateModel::all()->select($selectedColumns);
      }
      if ($states && $states->toArray()) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'States data retrieved successfully', 'toast' => false, 'data' => ["states" => $states->toArray()]]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'States data not found', 'toast' => true,]);
      }
    } catch (\Exception $e) {
      Log::info('public API error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getCities(Request $request)
  {
    try {
      $selectedColumns = ['id', 'name', "state_id"];
      if (isset($request->state_id)) {
        $cities = City::where('state_id', $request->state_id)->select($selectedColumns)->get();
      } else {
        $cities = City::all()->select($selectedColumns);
      }
      if ($cities && $cities->toArray()) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Cities data retrieved successfully', 'toast' => false, 'data' => ["cities" => $cities->toArray()]]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Cities data not found', 'toast' => true,]);
      }
    } catch (\Exception $e) {
      Log::info('public API error getCities : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getusersname(Request $request)
  {
    DB::beginTransaction();
    try {
      $query = User::query()->select('username');
      if ($request->has('search')) {
        $searchTerm = $request->input('search');
        $query->where('username', 'like', "%$searchTerm%");
      }
      $limit = $request->input('limit');
      $offset = $request->input('offset', 0);

      if ($limit > 0) {
        $query->offset($offset)->limit($limit);
      }

      $usernames = $query->pluck('username');
      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Users retrieved successfully', 'toast' => true, 'data' => ["UserNames" => $usernames]]);
    } catch (\Exception $e) {
      Log::info('Calendar Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while retrieving users', 'toast' => true]);
    }
  }
  public function cloudSendEventcrobJob()
  {
    $res_response = array('type' => 'info', 'code' => 200, 'status' => true, 'message' => 'Notification in process');

    DB::enableQueryLog();
    $events = CalendarEvent::whereNull('parent_id')->where("start_date_time", '>=', Carbon::now())->get();


    for ($i = 0; $i < count($events); $i++) {
      $event_owner = User::where('id', $events[$i]["user_id"])->first();
      $reminders = json_decode($events[$i]->reminder, true);

      $response['for_i'][] = $reminders;
      $startDateTime = date("Y-m-d H:i", strtotime($events[$i]->start_date_time));
      if (!empty($reminders)) {
        for ($j = 0; $j < count($reminders); $j++) {
          if (!is_numeric($reminders[$j]['minutehour'])) {
            $response['for_j_continue'][] = $reminders[$j];
            continue;
          }

          if ($reminders[$j]['minutehour'] == 'h') {
            $minutes = (int) $reminders[$j]['minutehour'] * 60;
          } else {
            $minutes = $reminders[$j]['minutehour'];
          }
          $interval = date('Y-m-d H:i:s', (strtotime($startDateTime) - 60 * $minutes));

          $response['interval'][] = $interval;
          $currentDateTime = new DateTime();
          $currentDateTime->setTimezone(new DateTimeZone('GMT'));
          $currentTimeInGMT = $currentDateTime->format('Y-m-d H:i:s');
          $currentTimeInGMTinm = $currentDateTime->format('Y-m-d H:i');


          $response['now'][] = $currentTimeInGMT;
          $response['now_1'][] = date_default_timezone_get();

          if ($currentTimeInGMTinm == date('Y-m-d H:i', strtotime($interval))) {
            $response['for_j'][] = $reminders[$j];
            $dateTimeObject1 = date_create(date('Y-m-d H:i:s'));
            $dateTimeObject2 = date_create($interval);
            $diff = date_diff($dateTimeObject1, $dateTimeObject2);

            $min = $diff->days * 24 * 60;
            $min += $diff->h * 60;
            $min += $diff->i;
            $invite_user_email = [];
            $invite_user_email = explode(',', $events[$i]->invited_by_email);
            if ($events[$i]->invited_by_username) {
              $invite_usernames = explode(',', $events[$i]->invited_by_username);
              if (!empty($invite_usernames)) {
                foreach ($invite_usernames as $user) {
                  $user_details = User::where('username', $user)->first();
                  $invite_user_email[] = $user_details->email;
                }
              }
            }

            if ($reminders[$j]['remindertype'] == "sms") {
              $shortTitle = substr($events[$i]->title, 0, 12);
              if ($events[$i]->invited_by_username !== "") {
                $invite_user_id = explode(',', $events[$i]->invited_by_username);


                foreach ($invite_user_id as $user) {

                  $user_details = User::where('username', $user)->first();
                  if ($user_details) {

                    $user_profile = UserProfile::where('user_id', $user_details->id)->first();
                    $message = 'Dear ' . $user_details->username . ', This is reminder for ' . $shortTitle . ' which will start at ' . date('M-d-Y h:i a', strtotime($startDateTime)) . " (GMT)";

                    // if (!empty($user_profile)) {
                    //   $country = Country::where("id", $user_profile->country)->first();
                    //   if (!empty($user_profile) && isset($country->phonecode)) {
                    // $phone_number = "+" . $country->phonecode . $user_profile->phone_number;
                    $contacts = getUsersContact($user_details->id);
                    Log::info("217 Calendar reminder: " . $message . ' ' . json_encode($contacts));
                    if ($contacts['country_code'] && $contacts['phone_no']) {
                      $phone_number = "+" . $contacts['country_code'] . $contacts['phone_no'];
                      $send_sms = send_sms($phone_number, $message);
                      Log::info("Calendar reminder: " . $phone_number . " " . $message);
                    }
                    //   }
                    // }
                  }
                }

                if ($event_owner) {
                  $user_profile_owner = UserProfile::where('user_id', $event_owner->id)->first();
                  $owner_message = 'Dear ' . $event_owner->username . ', This is reminder for ' . $shortTitle . ' which will start at ' . date('M-d-Y h:i a', strtotime($startDateTime)) . " (GMT)";
                  if (!empty($user_profile_owner)) {
                    $country_owner = Country::where("id", $user_profile_owner->country)->first();
                    if (!empty($user_profile_owner) && isset($country_owner->phonecode)) {
                      $phone_number = "+" . $country_owner->phonecode . $user_profile_owner->phone_number;
                      $send_sms = send_sms($phone_number, $message);
                    }
                  }
                }
              }
            } else if ($reminders[$j]['remindertype'] == "email") {
              $invite_user_email[] = $event_owner->email;
              if (!empty($invite_user_email)) {
                $filtered_emails = array_filter($invite_user_email, function ($email) {
                  return !empty($email);
                });

                foreach ($filtered_emails as $key => $value) {
                  try {
                    $emailData['subject'] = "Calendar event reminder";
                    $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                    $emailData['title'] = $events[$i]->event_title . " Calendar event reminder";
                    $emailData['view'] = 'mail-templates.event-reminder';
                    $emailData['owner_name'] = $event_owner->username;
                    $emailData['StartTime'] = date('F d, Y, H:i a', strtotime($events[$i]->start_date_time));
                    $emailData['endTime'] = date('F d, Y, H:i a', strtotime($events[$i]->end_date_time));
                    $emailData['event_title'] = $events[$i]->event_title;
                    $emailData['calender_url'] = config("app.calendar_url");
                    $emailData['projectName'] = config('app.app_name');
                    Mail::to($value)->send(new SendMail($emailData, $emailData['view']));
                    $res_response['email'][] = $value;
                  } catch (\Exception $e) {
                    Log::error('Error sending invitation email to ' . $value . ': ' . $e->getMessage());
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error sending email: ' . $e->getMessage(), 'toast' => true]);
                  }
                }
              }
            }
          }
        }
      }
    }

    return generateResponse($res_response);
  }
  public function defaultchannelvideo()
  {
    try {
      return response()->file(config("app.APP_URL") . "assets/default/videos/channel_video.mp4");
    } catch (\Exception $e) {
      Log::info('video retrieving : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while retrieving video', 'toast' => true]);
    }
  }
  public function getNotifications(GetNotificationRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      $module = $request->module ?? null;
      $multipleModules = $request->multiple_module ?? null;
      $recent = $request->recent ?? null;
      $limit = $request->limit ?? 10;
      $offset = $request->offset ?? 0;
      $notificationsQuery = Notification::query();

      $role_id = $user->role_id;

      if (($role_id == '2' || $role_id == '1') && ($module == '14')) {
        $notificationsQuery->where("is_admin", '1');
      } else {
        $notificationsQuery->where("to_user_id", $user->id)->where("is_admin", '0');
        if ($multipleModules) {
          $modules = explode(',', $multipleModules);
          $notificationsQuery->whereIn("module", $modules);
        } elseif ($module) {
          $notificationsQuery->where("module", $module);
        }
      }

      $notificationsQuery->where("seen", "0");

      if (isset($request->view_all)) {
        $notifications = $notificationsQuery->orderBy("updated_at", "desc");
      } else {
        $notifications = $notificationsQuery->offset($offset)->limit($limit)->orderBy("updated_at", "desc");
      }

      $notifications = $notificationsQuery->get();
      $apps2 = config("core_apps");

      if ($notifications->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No Notifications Found', 'toast' => true]);
      } else {
        if ($multipleModules) {
          $modules = explode(',', $multipleModules);
          $notificationCount = Notification::where("seen", "0")->whereIn("module", $modules)->where("to_user_id", $user->id)->count();
        } elseif ($module) {
          $notificationCount = Notification::where("seen", "0")->where("module", $module)->where("to_user_id", $user->id)->count();
        } else {
          $notificationCount = Notification::where("seen", "0")->where("to_user_id", $user->id)->count();
        }
        $apps = getDefaultFrontendSettings();
        $tempNotification = $notifications->toArray();
        $notificationsArr = [];
        foreach ($tempNotification as $notification) {
          $notification['recent'] = false;
          if ($recent) {
            $pastDate = Carbon::parse($notification['created_at']);
            $now = Carbon::now();
            $tenMinutesAgo = $now->subMinutes($recent);
            $notification['recent'] = $pastDate->between($tenMinutesAgo, $now);
          }

          $notification['is_sender'] = $notification['from_user_id'] == $user->id;
          $notification['imgSrc'] = null;
          if (!in_array($notification['module'], ["1", "3", "8", "10", "13", "14"])) {
            $appKey = array_search($notification['module'], array_column($apps['apps'], 'key'));
            $notification['imgSrc'] = $appKey && ($apps['apps'][$appKey]) && isset($apps['apps'][$appKey]['imgSrc']) ? $apps['apps'][$appKey]['imgSrc'] : null;
          } else {
            $appKey = array_search($notification['module'], array_column($apps2, 'key'));
            $notification['imgSrc'] = $appKey && ($apps2[$appKey]) && isset($apps2[$appKey]['imgSrc']) ? $apps2[$appKey]['imgSrc'] : null;
          }
          $notificationsArr[] = $notification;
        }

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Notification fetched', 'toast' => true], ['notifications' => $notificationsArr, 'total_count' => $notificationCount]);
      }
    } catch (\Exception $e) {
      Log::info('getting notifications : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function setNotificationsStatus(SetNotificationStatusRequest $request)
  {
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $module = isset($request->module) ? $request->module : null;
      $status = $request->status ? "1" : "0";
      $notification_id = $request->notification_id;
      $is_admin = $request->is_admin;
      if ($notification_id) {
        if (is_array($notification_id)) {
          if ($is_admin) {
            $notification = Notification::whereIn("id", $notification_id)->first();
          }
          if ($module) {
            $notification = Notification::whereIn("id", $notification_id)->where("module", $module)->where("to_user_id", $user->id)->first();
          } else {
            $notification = Notification::whereIn("id", $notification_id)->where("to_user_id", $user->id)->first();
          }

          if ($notification) {
            $notification->seen = $status;
            $notification->save();
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Notification status changed', 'toast' => true]);
          } else {
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Notification not found', 'toast' => true]);
          }
        } else {

          if ($module) {
            $notification = Notification::where("id", $notification_id)->where("module", $module)->where("to_user_id", $user->id)->first();
          } else {
            $notification = Notification::where("id", $notification_id)->where("to_user_id", $user->id)->first();
          }

          if ($is_admin) {
            $notification = Notification::where("id", $notification_id)->first();
          }
          if ($notification) {
            $notification->seen = $status;
            $notification->save();
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Notification status changed', 'toast' => true]);
          } else {
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Notification not found', 'toast' => true]);
          }
        }
      } else {
        if ($is_admin) {
          Notification::where('module', $module)
            ->update(['seen' => $status]);
        } else {
          Notification::where('to_user_id', $user->id)
            ->update(['seen' => $status]);
        }
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Notifications status changed', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('getting notifications : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getApps(AppsRequest $request)
  {
    try {

      $categoryName = ["", 'All', 'Social', 'Streaming', 'Financial', 'Tool', 'AI'];
      $appQuery = App::query();
      if ($request->type == 'banner') {
        $appQuery->where("is_frontend_banner", "1");
      } else if ($request->type == 'app') {
        $appQuery->where("is_frontend_app", "1");
      }
      if ($request->category) {
        $apps_category_key = array_search($request->category, $categoryName) ? array_search($request->category, $categoryName) : "none";
        $appQuery->where("apps_category", $apps_category_key);
      }
      $tempApps = $appQuery->get();
      if ($tempApps && $tempApps->toArray()) {
        $tempApps = $tempApps->toArray();
        $apps = [];
        foreach ($tempApps as $app) {
          $ta = [];
          $ta["title"] = $app['title'];
          $ta["image"] = $ta["dark_image"] = $app['image'];
          if ($ta["title"] == "Silo Merchants" || $ta["title"] == "Silo Gateway") {
            $ta["image"] = "assets/images/silo_apps/silo-gateway-light.png";
          }
          if ($request->type == 'app') {
            $ta["category"] = $app['category'];
            $ta["rating"] = $app['rating'];
            $ta["app_category"] = $app['free'] ? "" : "Free";
            $ta["url"] = $app['url'];
          } else if ($request->type == 'banner') {
            $ta["subTitle"] = $app['sub_title'];
          } else {
            $ta["category"] = $app['category'];
            $ta["rating"] = $app['rating'];
            $ta["app_category"] = $app['free'] ? "" : "Free";
            $ta["url"] = $app['url'];
            $ta["subTitle"] = $app['sub_title'];
          }


          $apps[$categoryName[$app['tabs_category']]][] = $ta;
        }
        $tabs = $categoryName;
        unset($tabs[0]);
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Apps data retrieved successfully', 'toast' => false, 'data' => ["apps" => $apps, "tabs" => $tabs]]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'App data not found', 'toast' => true,]);
      }
    } catch (\Exception $e) {
      Log::info('public API getApps error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getRole(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $role = Role::where('id', $user->role_id)->select(["id", "name", "key"])->first();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Role data retrieved successfully', 'toast' => false, 'data' => ["role" => $role->toArray()]]);
    } catch (\Exception $e) {
      Log::info('public API get role error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function checkToken(Request $request)
  {
    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Token is valid', 'toast' => false]);
  }
  public function getSiteSettings(GetSiteSettingRequest $request)
  {
    try {
      $module_id = $request->module_id;
      $temp_site_settings = SiteSetting::where("module", $module_id)->get();
      if (isset($request->field_key)) {
        $field_key = $request->field_key;
        $temp_site_settings = SiteSetting::where("module", $module_id)->where("field_key", $field_key)->get();
      }

      if ($temp_site_settings && $temp_site_settings->toArray()) {
        $temp_site_settings = $temp_site_settings->toArray();
        $site_settings = [];
        foreach ($temp_site_settings as $row) {
          unset($row['updated_at']);
          unset($row['deleted_at']);
          unset($row['created_at']);
          $site_settings[] = $row;
        }
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Settings found', 'toast' => true], ['site_settings' => $site_settings]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No settings found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('public API get site settings: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function getSiteSetting(Request $request)
  {
    try {
      $returnData = [];

      if ($request->has('field_id')) {
        $field_id = $request->field_id;
        $siteSetting = SiteSetting::where('id', $field_id)->first();

        if (!$siteSetting) {

          return generateResponse([
            'type' => 'error',
            'code' => 404,
            'status' => false,
            'message' => 'Site setting not found',
            'toast' => true
          ]);
        }

        $imageData = null;

        if ($siteSetting->field_value) {
          $filePath = storage_path('app/' . $siteSetting->field_value);

          // $imageData = getFileTemporaryURL($siteSetting->field_value);

          if (file_exists($filePath)) {
            $imageData = getFileTemporaryURL($siteSetting->field_value);
          } else {
            Log::error('Image file not found at path: ' . $filePath);
          }
        }

        $returnData[$siteSetting->field_key] = [
          'field_value' => $siteSetting->field_value,
          'image_data' => $imageData
        ];

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Site setting retrieved successfully',
          'toast' => true,
          'data' => $returnData
        ]);
      } else {
        $siteSettings = SiteSetting::all();

        foreach ($siteSettings as $setting) {
          $imageData = null;
          if ($setting->field_value) {
            $filePath = storage_path('app/' . $setting->field_value);
            if (file_exists($filePath)) {
              $imageData = getFileTemporaryURL($setting->field_value);
            } else {
              Log::error('Image file not found at path: ' . $filePath);
            }
          }

          $returnData[$setting->field_key] = [
            'field_value' => $setting->field_value,
            'image_data' => $imageData
          ];
        }

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Site settings retrieved successfully',
          'toast' => true,
          'data' => $returnData
        ]);
      }
    } catch (\Exception $e) {
      Log::error('Error retrieving site setting: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error retrieving site setting',
        'toast' => true
      ]);
    }
  }



  public function getQrimage(Request $request, $qr_id)
  {
    try {
      $qrcode = QrCode::where('qrcode_id', $qr_id)->first();
      if ($qrcode) {
        $file_path = $qrcode->file_path;
        if (Storage::exists($file_path)) {
          $image_path = storage_path('app/' . $file_path);
          return response()->file($image_path);
        } else {
          abort(404);
        }
      } else {
        abort(404);
      }
    } catch (\Exception $e) {
      Log::error('Error while processing QR image: ' . $e->getMessage());
      abort(500);
    }
  }

  public function getPdfQr(Request $request)
  {
    try {
      $key = $request->input('key');

      $qrData = DB::table('qr_codes')->where('file_key', $key)->first();

      if (!$qrData) {
        throw new \Exception('QR code not found');
      }

      $pdfPath = $qrData->pdf_path;
      $createdAt = $qrData->created_at;
      $user_id = $qrData->user_id;
      //get User Subscription
      $qrService = Service::where("key", "silo_qr")->first();
      $qrSubscription = UserServiceSubscription::selectRaw("
            service_plan_data,
            start_date,
            end_date
            ")
        ->where('user_id', $user_id)
        ->where('service_id', $qrService->id)
        ->whereDate('end_date', '>', Carbon::today())
        ->first();
      $firstQrCode = QRCode::where('user_id', $user_id)->orderBy('created_at', 'asc')->first();
      if ($firstQrCode) {
        $firstQrDate = $firstQrCode->created_at;
        $diffInDaystrial = now()->diffInDays($firstQrDate);
        if ($diffInDaystrial > 14 && !$qrSubscription) {
          return generateResponse(
            [
              'type' => 'error',
              'code' => 200,
              'status' => false,
              'message' => 'Your trial period is exceeded. Please upgrade your plan.',
              'toast' => true
            ],
            ['limit' => true]
          );
        }
      }
      if (!$pdfPath || !Storage::exists($pdfPath)) {
        throw new \Exception('PDF file not found or does not exist in the storage');
      }
      $diffInDays = now()->diffInDays($createdAt);
      if ($diffInDays >= 14 && !$qrSubscription) {
        return generateResponse(
          [
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Your limit is exceeded. Please upgrade your plan.',
            'toast' => true
          ],
          ['limit' => true]
        );
      }
      $pdfContent = getFileTemporaryURL($pdfPath);
      return generateResponse(
        [
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'PDF file retrieved successfully',
          'toast' => true
        ],
        ['pdfContent' => $pdfContent]
      );
    } catch (\Exception $e) {
      Log::error('Get file by key error: ' . $e->getMessage());
      return generateResponse(
        [
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Error retrieving the PDF file',
          'toast' => true
        ]
      );
    }
  }

  public function setScan(Request $request)
  {
    try {
      $urlPath = $request->url_path;
      $qrCode = QrCode::where('qrcode_data', 'LIKE', '%' . $urlPath . '%')->first();
      if (!$qrCode) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'QR code not found', 'toast' => true]);
      }

      $qrCode->scans += 1;
      $qrCode->save();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Scan count updated', 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Get scan issue: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Something went wrong', 'toast' => true]);
    }
  }

  public function getBlog(GetBlogRequest $request)
  {
    try {
      if (isset($request->category_id)) {
        $category = $request->input('category_id');

        $blogs = Blog::where('categories', $category)->get();
        $count = $blogs->count();

        foreach ($blogs as $blog) {
          if ($blog->blog_image) {
            $blog->blog_image = getFileTemporaryURL($blog->blog_image);
          }
          if ($blog->blog_detail_image) {
            $blog->blog_detail_image = getFileTemporaryURL($blog->blog_detail_image);
          }
          if ($blog->blog_video) {
            $blog->blog_video = getFileTemporaryURL($blog->blog_video);
          }
          if ($blog->blog_audio) {
            $blog->blog_audio = getFileTemporaryURL($blog->blog_audio);
          }
        }

        if ($blogs->isEmpty()) {
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blogs retrieved successfully', 'toast' => true, 'data' => ['count' => $count]]);
        }
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blogs retrieved successfully', 'toast' => true, 'data' => ['blogs' => $blogs->toArray(), 'count' => $count]]);
      } else {

        $id = $request->id;
        $blog = Blog::where('id', $id)->first();

        if (!$blog) {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Blog with ID ' . $id . ' not found', 'toast' => true]);
        }
        if ($blog->blog_image) {
          $blog->blog_image = getFileTemporaryURL($blog->blog_image);
        }
        if ($blog->blog_detail_image) {
          $blog->blog_detail_image = getFileTemporaryURL($blog->blog_detail_image);
        }
        if ($blog->blog_video) {
          $blog->blog_video = getFileTemporaryURL($blog->blog_video);
        }
        if ($blog->blog_audio) {
          $blog->blog_audio = getFileTemporaryURL($blog->blog_audio);
        }

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blogs retrieved successfully', 'toast' => true, 'data' => ['blogs' => $blog->toArray()]]);
      }
    } catch (\Exception $e) {
      Log::error('Error while retrieving blogs: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true
      ]);
    }
  }
  public function getBlogList(Request $request)
  {
    try {
      $query = Blog::query();
      $current_page = $request->input('current_page', 1);
      $perPage = $request->limit;

      if ($request->filled('search')) {
        $searchTerm = $request->input('search');
        if ($searchTerm) {
          $query->where('title', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('categories', 'LIKE', '%' . $searchTerm . '%');
        }
      }

      $query->orderBy('id', 'desc');

      $offset = ($current_page - 1) * $perPage;

      $total = $query->count();
      $blogs = $query->skip($offset)->take($perPage)->get();

      $pagination = [
        'current_page' => $current_page,
        'per_page' => $perPage,
        'total' => $total,
        'last_page' => ceil($total / $perPage),
      ];

      $result = array();
      foreach ($blogs as $key => $blog) {
        if ($blog->blog_image) {
          $blog->blog_image = getFileTemporaryURL($blog->blog_image);
        }
        if ($blog->blog_detail_image) {
          $blog->blog_detail_image = getFileTemporaryURL($blog->blog_detail_image);
        }
        if ($blog->blog_video) {
          $blog->blog_video = getFileTemporaryURL($blog->blog_video);
        }
        $blog->blogs_id = $offset + $key + 1;
        $result[] = $blog;
      }

      if ($total <= 0) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No blog found matching the search criteria', 'toast' => true]);
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Blogs retrieved successfully',
        'toast' => true,
        'data' => ['blogs' => $result, 'pagination' => $pagination],
        'count' => $total,
      ]);
    } catch (\Exception $e) {
      Log::error('Error while retrieving blogs: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true
      ]);
    }
  }
  public function getBlogLists(Request $request)
  {
    try {
      $query = Blog::query();
      $current_page = $request->input('current_page', 1);
      $perPage = $request->input('limit', 10);

      if ($request->filled('search')) {
        $searchTerm = $request->input('search');
        if ($searchTerm) {
          $query->where('title', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('categories', 'LIKE', '%' . $searchTerm . '%');
        }
      }

      $query->orderBy('id', 'desc');

      $offset = ($current_page - 1) * $perPage;

      $total = $query->count();
      $blogs = $query->skip($offset)->take($perPage)->get();

      $pagination = [
        'current_page' => $current_page,
        'per_page' => $perPage,
        'total' => $total,
        'last_page' => ceil($total / $perPage),
      ];

      $result = array();
      foreach ($blogs as $key => $blog) {
        if ($blog->blog_image) {
          $blog->blog_image = getFileTemporaryURL($blog->blog_image);
        }
        if ($blog->blog_detail_image) {
          $blog->blog_detail_image = getFileTemporaryURL($blog->blog_detail_image);
        }
        if ($blog->blog_video) {
          $blog->blog_video = getFileTemporaryURL($blog->blog_video);
        }
        $blog->blogs_id = $offset + $key + 1;
        $result[] = $blog;
      }

      if ($total <= 0) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No blog found matching the search criteria', 'toast' => true]);
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Blogs retrieved successfully',
        'toast' => true,
        'data' => ['blogs' => $result, 'pagination' => $pagination],
        'count' => $total,
      ]);
    } catch (\Exception $e) {
      Log::error('Error while retrieving blogs: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true
      ]);
    }
  }
  public function getAllCategories()
  {
    try {
      $firstRecord = Blog::orderBy('created_at')->first();
      $lastRecord = Blog::orderBy('created_at', 'desc')->first();

      $categories = BlogCategory::get();
      $categoryCounts = [];

      foreach ($categories as $category) {
        $count = Blog::where('categories', $category->category)
          ->whereBetween('created_at', [$firstRecord->created_at, $lastRecord->created_at])
          ->count();

        $categoryCounts[] = [
          'category' => $category,
          'count' => $count
        ];
      }

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Categories count retrieved successfully', 'toast' => true, 'data' => $categoryCounts,]);
    } catch (\Exception $e) {
      Log::error('Error while retrieving categories: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getNavList(Request $request)
  {
    DB::beginTransaction();
    try {
      $query = BlogNav::query();

      $navs = $query->select(['*'])
        ->get();

      if ($navs->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Nav data not found', 'toast' => true]);
      }

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Navs data retrieved successfully', 'toast' => false, 'data' => ["navs" => $navs]]);
    } catch (\Exception $e) {
      Log::error('public API error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }


  //get coin news

  public function getNews(Request $request)
  {
    try {

      if (isset($request->coin_id)) {
        $coin_id = $request->input('coin_id');
        $perPage = 6;
        $page = $request->input('page', 1);

        $query = NewsModel::where('coin_id', $coin_id)->orderBy('created_at', 'desc');
        $total = $query->count();
        $news = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        foreach ($news as $new) {
          if ($new->news_img) {
            $new->news_img = getFileTemporaryURL("public/" . $new->news_img);
          } else {
            $new->news_img = asset('assets/default/images/coin-news.png');
          }
        }

        $pagination = [
          'current_page' => $page,
          'per_page' => $perPage,
          'total' => $total,
          'last_page' => ceil($total / $perPage),
        ];

        $responseData = $news->isEmpty() ?
          ['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'News retrieved successfully', 'toast' => true, 'data' => ['count' => $total]] :
          ['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'News retrieved successfully', 'toast' => true, 'data' => ['News' => $news->toArray(), 'pagination' => $pagination]];

        return generateResponse($responseData);
      } else {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'coin_id is required', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::error('Error while retrieving News: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function addContactus(addContactusRequest $request)
  {

    DB::beginTransaction();
    try {
      $feedback = new ContactUs();
      $feedback->first_name = $request->first_name;
      $feedback->last_name = $request->last_name;
      $feedback->email = $request->email;
      $feedback->phone = $request->phone;
      $feedback->services = $request->services;
      $feedback->note = $request->note;
      $feedback->save();
      DB::commit();
      $userReview = ContactUs::where('id', $feedback->id)->first();
      $feedback_data = [
        'id' => $userReview->id,
        'first_name' => $userReview->first_name,
        'last_name' => $userReview->last_name,
        'email' => $userReview->email,
        'phone' => $userReview->phone,
        'services' => $userReview->services,
        'note' => $userReview->note,
      ];

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'feedback data added successfully.', 'toast' => true], ['feedback' => $feedback_data]);
    } catch (\Exception $e) {
      Log::info('feedback register API error : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function frontendSettings(FrontendSettingRequest $request)
  {
    try {
      DB::beginTransaction();
      $authHeader = $request->header('authtoken');
      $settings = getDefaultFrontendSettings();
      $settings['apps'] = getDefaultFrontendSettings("apps");
      $note[] = "footer_visibility => 2 means non visible and 1 => visible";
      $note[] = "theme => 1 means light and 2 => dark";
      $note[] = "column key == 1 => account 2 => qr 3 => support 4 => streaming 5 => marketplace 6 => calendar 7 => storage 8 => wallet 9 => mail 10 => game 11 => talk 12 => tv 13 => coin-exchange 14 => admin 15 => three_d 16 => publisher 17 => site 18 => community 19 => connect";
      if (!$authHeader) {
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Authorization header missing, returning default settings',
          'toast' => true,
        ], [
          'frontend_settings' => $settings,
          "note" => $note,
          'is_login' => false
        ]);
      }
      $token = str_replace('Bearer ', '', $authHeader);

      try {
        $decoded = JWT::decode($token, new Key(config('app.enc_key'), 'HS256'));
        $decodedArray = (array) $decoded;

        if ($decodedArray['exp'] < time()) {
          return generateResponse([
            'type' => 'success',
            'code' => 200,
            'status' => true,
            'message' => 'Token expired, returning default settings',
            'toast' => true,
          ], [
            'frontend_settings' => $settings,
            "note" => $note,
            'is_login' => false
          ]);
        }
        $userId = $decodedArray['cloudUserId'] ?? $decodedArray['cloud_user_id'] ?? null;
        if (!$userId) {
          return generateResponse([
            'type' => 'error',
            'code' => 401,
            'status' => false,
            'message' => 'Invalid token payload',
            'toast' => true,
          ], ['is_login' => false]);
        }
      } catch (\Exception $e) {
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Invalid token, returning default settings',
          'toast' => true,
        ], [
          'frontend_settings' => $settings,
          "note" => $note,
          'is_login' => false
        ]);
      }

      $userFrontendSettingsQuery = FrontendSetting::query()->where("user_id", $userId);
      if (!$userFrontendSettingsQuery->count()) {
        $userFrontendSettings = new FrontendSetting();
        $userFrontendSettings->user_id = $userId;
      } else {
        $userFrontendSettings = $userFrontendSettingsQuery->first();
      }

      $settings = getDefaultFrontendSettings();
      $action = $request->action;

      if ($action == 'update') {
        $apps = getDefaultFrontendSettings('apps');
        $column = $request->column;
        $columnValue = $request->columnValue;

        if ($column == 'theme') {
          $userFrontendSettings->theme = $columnValue;
        } elseif ($column == 'apps') {
          $columnKey = $request->columnKey;

          if ($userFrontendSettings->apps) {
            $apps = json_decode($userFrontendSettings->apps, true);

            if (!$apps) {
              $apps = getDefaultFrontendSettings("apps");
            }
          }

          foreach ($apps as &$app) {
            if ($app['key'] == $columnKey) {
              $app['footer_visibility'] = (string) $columnValue;
              break;
            }
          }

          $userFrontendSettings->apps = json_encode($apps);
        }

        $userFrontendSettings->save();
        DB::commit();

        $settings['apps'] = $apps;
        $settings['theme'] = $userFrontendSettings->theme;
      } else if ($action == 'fetch') {
        if ($userFrontendSettingsQuery->count()) {
          if ($userFrontendSettings->apps) {
            $apps = json_decode($userFrontendSettings->apps, true);

            if (!$apps) {
              $settings['apps'] = getDefaultFrontendSettings("apps");
            } else {
              $settings['apps'] = $apps;
            }
          }

          $settings['theme'] = $userFrontendSettings->theme;
        }
      } else if ($action == "rearrange") {
        $apps = $request->apps;
        $settings['apps'] = $apps;
        $userFrontendSettings->apps = json_encode($apps);
        $userFrontendSettings->save();
        DB::commit();
      }

      $token_routes['talk'] = "token-login/" . generateTokenForURL($userId);
      array_multisort(array_column($settings['apps'], 'sequence_no'), SORT_ASC, $settings['apps']);
      $message = $action == 'update' ? "Frontend setting updated" : "Frontend settings retrieved";
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => $message,
        'toast' => true,
      ], [
        'frontend_settings' => $settings,
        "note" => $note,
        'token_routes' => $token_routes,
        'is_login' => true
      ]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('frontendSettings API error : ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true,
        'is_login' => false
      ]);
    }
  }

  //addsilosecureconsultation
  public function addSilosecureConsultation(AddSilosecureConsultationRequest $request)
  {
    DB::beginTransaction();
    try {
      $Silosecure = new SilosecureConsultation();
      $Silosecure->date = $request->date;
      $Silosecure->time = $request->time;
      $Silosecure->full_name = $request->full_name;
      $Silosecure->email = $request->email;
      $Silosecure->phone = $request->phone;
      $Silosecure->message = $request->message;
      $Silosecure->save();

      DB::commit();

      $SilosecureDetails = SilosecureConsultation::find($Silosecure->id);
      $Silosecure_data = [
        'id' => $SilosecureDetails->id,
        'date' => $SilosecureDetails->date,
        'time' => $SilosecureDetails->time,
        'fullname' => $SilosecureDetails->full_name,
        'email' => $SilosecureDetails->email,
        'phone' => $SilosecureDetails->phone,
        'message' => $SilosecureDetails->message,
      ];

      return response()->json([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Silosecure request submitted successfully.',
        'toast' => true,
        'Silosecure' => $Silosecure_data,
      ]);
    } catch (\Exception $e) {
      Log::error('Silosecure register API error: ' . $e->getMessage());
      DB::rollBack();

      return response()->json([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while processing Silosecure request.',
        'toast' => true,
      ]);
    }
  }
  //get-SilosecureConsultation
  public function getSilosecureConsultation(Request $request)
  {
    try {
      $SilosecureId = $request->input('Silosecure_id');
      $page = $request->input('page', 1);
      $limit = $request->input('limit', 10);

      if ($SilosecureId) {
        $validator = Validator::make($request->all(), [
          'Silosecure_id' => 'integer|exists:silosecure_consultation,id',
        ]);

        if ($validator->fails()) {
          return response()->json([
            'type' => 'error',
            'code' => 400,
            'status' => false,
            'message' => 'The Silosecure_id must be a valid integer.',
            'errors' => $validator->errors(),
          ]);
        }

        $Silosecure = SilosecureConsultation::find($SilosecureId);

        if ($Silosecure) {
          $Silosecure_data = [
            'id' => $Silosecure->id,
            'date' => $Silosecure->date,
            'time' => $Silosecure->time,
            'full_name' => $Silosecure->full_name,
            'email' => $Silosecure->email,
            'phone' => $Silosecure->phone,
            'message' => $Silosecure->message,
          ];

          $SilosecureCount = SilosecureConsultation::where('id', $SilosecureId)->count();

          return response()->json([
            'type' => 'success',
            'code' => 200,
            'status' => true,
            'message' => 'Silosecure details retrieved successfully.',
            'Silosecure' => $Silosecure_data,
            'count' => $SilosecureCount,
          ]);
        } else {
          return response()->json([
            'type' => 'error',
            'code' => 404,
            'status' => false,
            'message' => 'Silosecure not found.',
          ]);
        }
      } else {
        $query = SilosecureConsultation::query();
        $totalCount = $query->count();

        if ($request->filled('search_keyword')) {
          $searchKeyword = $request->input('search_keyword');
          $keywords = explode(' ', $searchKeyword);
          $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
              $q->where('full_name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%");
            }
          });
        }

        $totalFilteredCount = $query->count();
        $Silosecures = $query->skip(($page - 1) * $limit)->take($limit)->get();

        $Silosecure_data = $Silosecures->map(function ($Silosecure) {
          return [
            'id' => $Silosecure->id,
            'date' => $Silosecure->date,
            'time' => $Silosecure->time,
            'full_name' => $Silosecure->full_name,
            'email' => $Silosecure->email,
            'phone' => $Silosecure->phone,
            'message' => $Silosecure->message,
          ];
        });

        return response()->json([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'All Silosecures retrieved successfully.',
          'Silosecures' => $Silosecure_data,
          'pagination' => [
            'total' => $totalCount,
            'total_filtered' => $totalFilteredCount,
            'page' => $page,
            'limit' => $limit,
          ]
        ]);
      }
    } catch (\Exception $e) {
      Log::error('Get Silosecure API error: ' . $e->getMessage());

      return response()->json([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while processing Silosecure request.',
      ]);
    }
  }

  //function to fetch live programs
  public function fetchTvPrograms(Request $request)
  {
    try {
      $date = $request->date;
      $channelId = $request->channel_id;
      $domainId = $request->domain_id;
      $time_zone = $request->time_zone ? $request->time_zone : "Asia/Calcutta";
      $today = $date;
      $todayTime = (new DateTime('now', new DateTimeZone($time_zone)))->format('H:i:s');

      $programDetailsQuery = TvLivestream::join('schedule_channles', 'tv_livestreams.channel_id', '=', 'schedule_channles.channel_id')
        ->join('channels', 'tv_livestreams.channel_id', '=', 'channels.id')
        ->where('tv_livestreams.status', '1')
        ->select(
          'tv_livestreams.output_blob',
          'tv_livestreams.playlistpathLink',
          'schedule_channles.epg_data',
          'channels.channel_name',
          'channels.logo',
          'channels.channelUuid',
          'channels.schedule_duration',
          'tv_livestreams.channel_id',
          'tv_livestreams.id'
        );

      $channelIdsArray = [];

      if ($domainId) {
        $channelIds = DB::table('website')
          ->where('domain_id', $domainId)
          ->value('channels');

        if ($channelIds) {
          $channelIdsArray = explode(',', $channelIds);
          Log::info('Filtered Channel IDs: ', $channelIdsArray);
          $programDetailsQuery->whereIn('tv_livestreams.channel_id', $channelIdsArray);
        } else {
          return generateResponse([
            'type' => 'error',
            'code' => 404,
            'status' => false,
            'message' => 'No channels found for the provided domain_id.',
            'toast' => true,
          ]);
        }
      } elseif ($channelId) {
        $programDetailsQuery->where('tv_livestreams.channel_id', $channelId);
        $channelIdsArray = [$channelId];
      }

      $programDetails = $programDetailsQuery->get();

      $liveStreams = DB::table('live_stream')
        ->where('stream_status', '1')
        ->get();

      $liveStreamMap = $liveStreams->mapWithKeys(function ($item) use ($channelIdsArray) {
        if (!empty($item->destination_id)) {
          $destinations = explode(',', $item->destination_id);
          $filteredDestinations = $channelIdsArray ? array_intersect($destinations, $channelIdsArray) : $destinations;
          return array_fill_keys($filteredDestinations, $item->stream_url_live);
        }
        return [];
      });

      $additionalChannels = DB::table('channels')
        ->whereIn('id', array_keys($liveStreamMap->toArray()))
        ->get()
        ->keyBy('id');

      if ($channelId) {
        $programDetail = $programDetails->first();
        if (!$programDetail) {
          if (isset($liveStreamMap[$channelId])) {
            return generateResponse([
              'type' => 'success',
              'code' => 200,
              'status' => true,
              'message' => 'Program details fetched successfully.',
              'toast' => false,
            ], [
              'output_blob' => '',
              'playlistpathLink' => $liveStreamMap[$channelId],
              'epg_data' => [
                [
                  "id" => "00a1cbd8-7ac8-4747-95ff-ef2b5c0f82c7",
                  "title" => $additionalChannels[$channelId]->channel_name . "- Live",
                  "image" => $additionalChannels[$channelId]->logo ? getFileTemporaryURL($additionalChannels[$channelId]->logo) : config('app.url') . "assets/default/images/apps/logos/streamdeck_logo.png",
                  "since" => $today . "T" . $todayTime,
                  "till" => $today . "T23:59:59",
                  "date" => $today,
                  "channelUuid" => $additionalChannels[$channelId]->channelUuid,
                  "channel_id" => $channelId,
                  "isLive" => true,
                  "channelIndex" => 0,
                  "channelPosition" => [
                    "top" => 0,
                    "height" => 80
                  ],
                  "index" => 0,
                ]
              ],
              'channelData' => [
                'channel_name' => $additionalChannels[$channelId]->channel_name,
                'logo' => $additionalChannels[$channelId]->logo ? getFileTemporaryURL($additionalChannels[$channelId]->logo) : config('app.url') . "assets/default/images/apps/logos/streamdeck_logo.png",
                'uuid' => $additionalChannels[$channelId]->channelUuid,
                'is_live' => true,
                'slug' => Str::slug($additionalChannels[$channelId]->channel_name) . "-" . rand(9, 99)
              ],
            ]);
          }

          return generateResponse([
            'type' => 'error',
            'code' => 404,
            'status' => false,
            'message' => 'No program details found for the given date and channel.',
            'toast' => true,
          ]);
        }

        if (isset($liveStreamMap[$channelId])) {
          return generateResponse([
            'type' => 'success',
            'code' => 200,
            'status' => true,
            'message' => 'Program details fetched successfully.',
            'toast' => false,
          ], [
            'output_blob' => '',
            'playlistpathLink' => $liveStreamMap[$channelId],
            'epg_data' => [
              [
                "id" => "00a1cbd8-7ac8-4747-95ff-ef2b5c0f82c7",
                "title" => "Live",
                "image" => $programDetail->logo ? getFileTemporaryURL($programDetail->logo) : config('app.url') . "assets/default/images/apps/logos/Streaming.png",
                "since" => $today . "T" . $todayTime,
                "till" => $today . "T23:59:59",
                "date" => $today,
                "channelUuid" => $programDetail->channelUuid,
                "channel_id" => $channelId,
                "isLive" => true,
                "channelIndex" => 0,
                "channelPosition" => [
                  "top" => 0,
                  "height" => 80
                ],
                "index" => 0,
              ]
            ],
            'channelData' => [
              'channel_name' => $programDetail->channel_name,
              'logo' => $programDetail->logo ? getFileTemporaryURL($programDetail->logo) : config('app.url') . "assets/default/images/apps/logos/Streaming.png",
              'uuid' => $programDetail->channelUuid,
              'is_live' => true,
              'slug' => Str::slug($programDetail->channel_name) . "-" . rand(9, 99)
            ],
          ]);
        }

        // Decode and filter EPG data
        $epgData = array_filter(json_decode($programDetail->epg_data, true), function ($epg) {
          return isset($epg['is_broadcasted']) && $epg['is_broadcasted'] != 0;
        });

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Program details fetched successfully.',
          'toast' => false,
        ], [
          'output_blob' => getFileTemporaryURL($programDetail->output_blob),
          'playlistpathLink' => getFileTemporaryURL($programDetail->playlistpathLink),
          'epg_data' => array_values($epgData),
          'channelData' => [
            'channel_name' => $programDetail->channel_name,
            'logo' => $programDetail->logo ? getFileTemporaryURL($programDetail->logo) : config('app.url') . "assets/default/images/apps/logos/streamdeck_logo.png",
            'uuid' => $programDetail->channelUuid,
            'is_live' => false,
            'schedule_duration' => $programDetail->schedule_duration,
            'channel_id' => $programDetail->channel_id,
            'livestream_id' => $programDetail->id,
          ],
        ]);
      } else {
        if ($programDetails->isEmpty() && $liveStreamMap->isEmpty()) {
          $channels = DB::table('channels')
            ->whereIn('id', $channelIdsArray)
            ->get();

          $allPlaylistpathLinks = [];
          $allEpgData = [];
          $allChannelData = [];

          foreach ($channels as $channel) {
            $allChannelData[] = [
              'channel_name' => $channel->channel_name,
              'logo' => $channel->logo ? getFileTemporaryURL($channel->logo) : config('app.url') . "assets/default/images/apps/logos/streamdeck_logo.png",
              'channel_id' => $channel->id,
              'is_live' => false,
              'schedule_duration' => $channel->schedule_duration,
              'slug' => Str::slug($channel->channel_name) . "-" . rand(9, 99)
            ];

            $allPlaylistpathLinks[] = [
              'channel_id' => $channel->id,
              'playlistpathLink' => ''
            ];
          }

          return generateResponse([
            'type' => 'success',
            'code' => 200,
            'status' => true,
            'message' => 'Program details fetched successfully.',
            'toast' => false,
          ], [
            'output_blob' => '',
            'playlistpathLinks' => $allPlaylistpathLinks,
            'epg_data' => $allEpgData,
            'channelData' => $allChannelData,
          ]);
        }

        $allEpgData = [];
        $allChannelData = [];
        $allPlaylistpathLinks = [];

        foreach ($programDetails as $programDetail) {
          $epgData = array_filter(json_decode($programDetail->epg_data, true), function ($epg) {
            return isset($epg['is_broadcasted']) && $epg['is_broadcasted'] != 0;
          });

          $isLive = isset($liveStreamMap[$programDetail->channel_id]);

          if ($isLive) {
            $epgData = [
              [
                "id" => "00a1cbd8-7ac8-4747-95ff-ef2b5c0f82c7",
                "title" => $programDetail->channel_name . "- Live",
                "image" => $programDetail->logo ? getFileTemporaryURL($programDetail->logo) : config('app.url') . "assets/default/images/apps/logos/Streaming.png",
                "since" => $today . "T" . $todayTime,
                "till" => $today . "T23:59:59",
                "date" => $today,
                "channelUuid" => $programDetail->channelUuid,
                "channel_id" => $programDetail->channel_id,
                "isLive" => true,
                "channelIndex" => 0,
                "channelPosition" => [
                  "top" => 0,
                  "height" => 80
                ],
                "index" => 0,
              ]
            ];
          }

          foreach ($epgData as $epg) {
            $allEpgData[] = $epg;
          }

          $channelData = [
            'channel_name' => $programDetail->channel_name,
            'logo' => getFileTemporaryURL($programDetail->logo),
            'uuid' => $programDetail->channelUuid,
            'channel_id' => $programDetail->channel_id,
            'is_live' => $isLive,
            'livestream_id' => $programDetail->id,
            'schedule_duration' => $programDetail->schedule_duration,
            'slug' => Str::slug($programDetail->channel_name) . "-" . rand(9, 99)
          ];

          $allChannelData[] = $channelData;

          $playlistpathLink = $isLive ? $liveStreamMap[$programDetail->channel_id] : getFileTemporaryURL($programDetail->playlistpathLink);

          $outoutbloblink = $isLive ? $liveStreamMap[$programDetail->channel_id] : getFileTemporaryURL($programDetail->output_blob);

          $allPlaylistpathLinks[] = [
            'channel_id' => $programDetail->channel_id,
            'playlistpathLink' => $playlistpathLink,
            'output_blob' => $outoutbloblink
          ];
        }

        foreach ($liveStreamMap as $channelId => $streamUrl) {
          if (!$programDetails->contains('channel_id', $channelId)) {
            $additionalChannel = $additionalChannels[$channelId];

            $allEpgData[] = [
              "id" => "00a1cbd8-7ac8-4747-95ff-ef2b5c0f82c7",
              "title" => $additionalChannel->channel_name . "- Live",
              "image" => $additionalChannel->logo ? getFileTemporaryURL($additionalChannel->logo) : config('app.url') . "assets/default/images/apps/logos/Streaming.png",
              "since" => $today . "T" . $todayTime,
              "till" => $today . "T23:59:59",
              "date" => $today,
              "channelUuid" => $additionalChannel->channelUuid,
              "channel_id" => $channelId,
              "isLive" => true,
              "channelIndex" => 0,
              "channelPosition" => [
                "top" => 0,
                "height" => 80
              ],
              "index" => 0,
            ];

            $allChannelData[] = [
              'channel_name' => $additionalChannel->channel_name,
              'logo' => $additionalChannel->logo ? getFileTemporaryURL($additionalChannel->logo) : config('app.url') . "assets/default/images/apps/logos/streamdeck_logo.png",
              'uuid' => $additionalChannel->channelUuid,
              'channel_id' => $channelId,
              'is_live' => true,
              'livestream_id' => null,
              'schedule_duration' => $additionalChannel->schedule_duration,
              'slug' => Str::slug($additionalChannel->channel_name) . "-" . rand(9, 99)
            ];

            $allPlaylistpathLinks[] = [
              'channel_id' => $channelId,
              'playlistpathLink' => $streamUrl,
            ];
          }
        }

        $sortedChannelData = [];
        $sortedPlaylistpathLinks = [];
        $sortedEpgData = [];

        $addToSortedArrays = function ($channel, $playlistLink, $epg) use (&$sortedChannelData, &$sortedPlaylistpathLinks, &$sortedEpgData) {
          $sortedChannelData[] = $channel;
          $sortedPlaylistpathLinks[] = $playlistLink;
          $sortedEpgData = array_merge($sortedEpgData, $epg);
        };

        foreach ($allChannelData as $index => $channel) {
          if ($channel['is_live']) {
            $addToSortedArrays(
              $channel,
              $allPlaylistpathLinks[$index],
              array_filter($allEpgData, function ($epg) use ($channel) {
                return $epg['channel_id'] == $channel['channel_id'];
              })
            );
          }
        }

        foreach ($allChannelData as $index => $channel) {
          if (!$channel['is_live']) {
            $channelEpg = array_filter($allEpgData, function ($epg) use ($channel) {
              return $epg['channel_id'] == $channel['channel_id'];
            });
            if (!empty($channelEpg)) {
              $addToSortedArrays($channel, $allPlaylistpathLinks[$index], $channelEpg);
            }
          }
        }

        foreach ($allChannelData as $index => $channel) {
          if (!$channel['is_live']) {
            $channelEpg = array_filter($allEpgData, function ($epg) use ($channel) {
              return $epg['channel_id'] == $channel['channel_id'];
            });
            if (empty($channelEpg)) {
              $addToSortedArrays($channel, $allPlaylistpathLinks[$index], []);
            }
          }
        }

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Program details fetched successfully.',
          'toast' => false,
        ], [
          'output_blob' => '',
          'playlistpathLinks' => $sortedPlaylistpathLinks,
          'epg_data' => $sortedEpgData,
          'channelData' => $sortedChannelData,
        ]);
      }
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'An error occurred while fetching program details: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }
  public function getWebsiteProgramData(Request $request)
  {
    try {
      $websites_id = $request->website_id;
      $websites_data = Website::where('domain_id', $websites_id)->first();
      if ($websites_data) {
        $channelIds = explode(',', $websites_data->channels);

        $channels = Channel::whereIn('id', $channelIds)
          ->select('id', 'channel_name')
          ->get();

        $websites_data->channels = $channels;
        $websites_data->site_logo = getFileTemporaryURL($websites_data->site_logo);
        $websites_data->site_favicon = getFileTemporaryURL($websites_data->site_favicon);

        $websites_data['playback_options'] = json_decode($websites_data['playback_options']);
        $websites_data['display_options'] = json_decode($websites_data['display_options']);
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Data retrieved successfully', 'toast' => true, 'data' => [$websites_data]]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true]);
      }
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'An error occurred while fetching program details.',
        'toast' => true,
      ]);
    }
  }
  public function trackView(Request $request)
  {
    try {
      $channelId = $request->channel_id;
      $livestreamId = $request->livestream_id;
      $deviceType = $request->device_type;
      $browserInfo = $request->browser_info;
      $location = $request->location;
      $channel = Channel::findOrFail($channelId);
      $userId = $channel->user_id;
      $views = json_decode($channel->views, true);
      if (!$views) {
        $views = [];
      }
      $views[] = [
        'livestream_id' => $livestreamId,
        'user_id' => $userId,
        'timestamp' => now()->toDateTimeString(),
        'device_type' => $deviceType,
        'browser_info' => $browserInfo,
        'location' => $location,
      ];
      $channel->update([
        'views' => json_encode($views),
      ]);
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Data retrieved successfully', 'toast' => true], ['views' => $views]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'An error occurred while fetching program details.',
        'toast' => true,
      ]);
    }
  }
  public function getMailnavs()
  {
    DB::beginTransaction();
    try {
      $query = MailNavs::query();

      $navs = $query->select(['*'])->get();

      if ($navs->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Navs data not found', 'toast' => true]);
      }

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Navs data retrieved successfully', 'toast' => false, 'data' => ["navs" => $navs]]);
    } catch (\Exception $e) {
      Log::error('public navs API error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getSiloApps(Request $request)
  {
    try {
      $sections = DB::table('app_sections')->get();
      $apps = DB::table('silo_apps')
        ->select('id', 'name', 'section_id', 'image_link', 'project_link', 'dark_image_link')
        ->get()
        ->map(function ($app) {
          // If dark_image_link is null, set it to image_link
          $app->dark_image_link = $app->dark_image_link ?? $app->image_link;
          return $app;
        });

      $formattedResponse = [];
      foreach ($sections as $section) {
        $sectionName = strtolower(str_replace(' ', '_', $section->name));
        $formattedResponse[$sectionName] = [
          'label' => $section->name,
          'data' => $apps->filter(function ($app) use ($section) {
            return $app->section_id == $section->id;
          })->values()->toArray()
        ];
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Data retrieved successfully',
        'toast' => true,
        'data' => $formattedResponse
      ]);
    } catch (\Exception $e) {
      Log::info('Error while processing: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'An error occurred while fetching the app details.',
        'toast' => true,
      ]);
    }
  }


  public function getUserInformationFromId(Request $request)
  {
    $userId = $request->user_id;
    try {
      if ($userId && $userId != null) {
        $user_profile = getProfile($userId, null);

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User profile data retrieved successfully', 'toast' => true], ["profile" => $user_profile]);
      } else {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => false, 'message' => 'User with provided id not found OR Invalid user_id', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('profile Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true, 'data' => ["account_tokens" => 0]]);
    }
  }
  public function fetchUsersListFromSearch(Request $request)
  {
    try {
      $searchValue = $request->input('search');
      $users = User::where(function ($query) use ($searchValue) {
        $query->where('first_name', 'like', "%$searchValue%")
          ->orWhere('last_name', 'like', "%$searchValue%")
          ->orWhere('username', 'like', "%$searchValue%")
          ->orWhere('email', 'like', "%$searchValue%");
      })
        ->limit(10)
        ->get(["id", "username", "email"]);


      $userList = generateUsersListForSearch($users);

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Userlist fetched successfully', 'toast' => true], ["userList" => $userList]);
    } catch (\Exception $e) {
      Log::error('User search error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error on user search', 'toast' => true]);
    }
  }

  public function getMarketPlaceLiveData(Request $request)
  {
    try {
      $broadcastId = $request->input('broadcast_id');
      if (!$broadcastId) {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Broadcast ID is required.', 'toast' => true]);
      }
      $productIdsQuery = DB::table('marketplace_live')
        ->where('broadcast_id', $broadcastId)
        ->where('stream_status', '1')
        ->value('product_ids');
      if (!$productIdsQuery) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'No live stream found with the given broadcast ID.', 'toast' => true], ['is_live' => false]);
      }
      $productIds = explode(',', $productIdsQuery);
      $livestreamData = DB::table('marketplace_live')
        ->join('users', 'marketplace_live.user_id', '=', 'users.id')
        ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
        ->select('users.username', 'user_profiles.profile_image_path', 'marketplace_live.stream_url_live', 'marketplace_live.stream_status')
        ->where('marketplace_live.broadcast_id', $broadcastId)
        ->where('marketplace_live.stream_status', '1')
        ->first();

      if (!$livestreamData) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'No data found for the given broadcast ID.', 'toast' => true], ['is_live' => false]);
      }
      $livestreamData->profile_image_path = getFileTemporaryURL($livestreamData->profile_image_path);
      $productsData = DB::table('marketplace_products')
        ->select(
          'marketplace_products.id',
          'marketplace_products.product_name',
          'marketplace_products.price',
          'marketplace_products.thumbnail',
          'marketplace_products.product_short_name',
          'marketplace_products.discount_percentage'
        )
        ->join('marketplace_live', function ($join) use ($productIds, $broadcastId) {
          $join->on(DB::raw("FIND_IN_SET(marketplace_products.id, marketplace_live.product_ids)"), '>', DB::raw('0'))
            ->where('marketplace_live.broadcast_id', $broadcastId)
            ->where('marketplace_live.stream_status', '1');
        })
        ->whereIn('marketplace_products.id', $productIds)
        ->whereNull('marketplace_products.deleted_at')
        ->get();

      $productsData->transform(function ($product) {
        $product->thumbnail = url('storage/' . $product->thumbnail);
        return $product;
      });

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Live stream data fetched successfully.',
        'toast' => true,
      ], [
        'common_data' => [
          'username' => $livestreamData->username,
          'profile_image_path' => $livestreamData->profile_image_path,
          'stream_url_live' => $livestreamData->stream_url_live,
        ],
        'live_data' => $productsData,
        'is_live' => $livestreamData->stream_status === "1" ? true : false,
      ]);
    } catch (\Exception $e) {
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error on Fetching: ' . $e->getMessage(), 'toast' => true]);
    }
  }
  public function getMarketplaceLiveSellers(Request $request)
  {
    try {
      $data = DB::table('marketplace_live as ml')
        ->join('users as u', 'u.id', '=', 'ml.user_id')
        ->join('user_profiles as up', 'up.user_id', '=', 'u.id')
        ->join('marketplace_products as mp', DB::raw('FIND_IN_SET(mp.id, ml.product_ids)'), '>', DB::raw('0'))
        ->select(
          'ml.user_id',
          'ml.product_ids',
          'ml.broadcast_id',
          'u.username',
          'up.profile_image_path',
          'mp.product_short_name',
          'mp.price',
          'mp.discount_percentage',
          'mp.thumbnail'
        )
        ->where('ml.stream_status', '1')
        ->orderBy('ml.broadcast_id')
        ->orderBy('mp.id')
        ->get();

      $result = [];

      foreach ($data as $item) {
        if (!isset($result[$item->broadcast_id])) {
          $result[$item->broadcast_id] = [
            'user_id' => $item->user_id,
            'product_ids' => $item->product_ids,
            'broadcast_id' => $item->broadcast_id,
            'username' => $item->username,
            'profile_image_path' => getFileTemporaryURL($item->profile_image_path),
            'products' => []
          ];
        }
        $result[$item->broadcast_id]['products'][] = [
          'product_short_name' => $item->product_short_name,
          'price' => $item->price,
          'discount_percentage' => $item->discount_percentage,
          'thumbnail' => url('storage/' . $item->thumbnail)
        ];
      }
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Live stream data fetched successfully.',
        'toast' => true,
        'data' => array_values($result)
      ]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error on Fetching sellers: ' . $e->getMessage(),
        'toast' => true
      ]);
    }
  }
  public function getUsersEmail(Request $request)
  {
    try {
      $limit = $request->input('limit', 10);
      $users = DB::table('users')
        ->select('users.id', 'users.username', 'users.email', 'user_profiles.profile_image_path')
        ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
        ->limit($limit)
        ->get();

      return response()->json(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Users retrieved successfully', 'toast' => true, 'data' => $users]);
    } catch (\Exception $e) {
      Log::error('Error fetching Users: ' . $e->getMessage());
      return response()->json(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function shortenUrlQR(Request $request)
  {
    try {
      $request->validate([
        'url' => 'required|url'
      ]);

      $originalUrl = $request->input('url');
      $shortUrl = ShortUrl::where('original_url', $originalUrl)->first();

      if (!$shortUrl) {
        $shortCode = $this->generateShortCode($originalUrl);
        $shortUrl = ShortUrl::create([
          'original_url' => $originalUrl,
          'short_code' => $shortCode,
        ]);
      }
      $shortenedUrl = $shortUrl->short_code;

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'URL shortened successfully.',
        'toast' => true
      ], ['shortened_url' => $shortenedUrl]);
    } catch (\Exception $e) {
      Log::error($e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Something went wrong',
        'toast' => true
      ]);
    }
  }

  private function generateShortCode($url)
  {
    return Str::random(6);
  }

  public function getOriginalUrl(Request $request)
  {
    try {
      $request->validate([
        'short_code' => 'required|string'
      ]);

      $shortCode = $request->input('short_code');
      $shortUrl = ShortUrl::where('short_code', $shortCode)->first();
      if ($shortUrl) {
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Original URL retrieved successfully.',
          'toast' => true
        ], ['original_url' => $shortUrl->original_url]);
      } else {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Short code not found.',
          'toast' => true
        ]);
      }
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Something went wrong',
        'toast' => true
      ]);
    }
  }
  public function allStoresFilter(Request $request)
  {
    try {
      $categoryIds = [];
      $subcategories = [];
      $categories = [];

      if ($request->filled('store_id')) {
        $storeIds = explode(',', $request->input('store_id'));
        $stores = MarketplaceStore::whereIn('id', $storeIds)
          ->pluck('categories');
        $categoryIds = $stores->flatMap(function ($categoryList) {
          return explode(',', $categoryList);
        })->unique()->toArray();
        $categories = MarketplaceCategory::select('id as category_id', 'category_name')
          ->whereIn('id', $categoryIds)
          ->orderBy('id')
          ->get();
      } else {
        $categories = MarketplaceCategory::select('id as category_id', 'category_name')
          ->orderBy('id')
          ->get();
      }

      if ($request->filled('category_id')) {
        $categoryIds = explode(',', $request->input('category_id'));
      }

      if (!empty($categoryIds)) {
        $firstCategoryId = $categoryIds[0];

        $subcategories = MarketplaceSubCategory::select('id as sub_category_id', 'sub_category_name', 'parent_category_id')
          ->where('parent_category_id', $firstCategoryId)
          ->orderBy('id')
          ->get();
      } else {
        if (!$request->filled('store_id') && $categories->isNotEmpty()) {
          $firstCategoryId = $categories->first()->category_id;
          $subcategories = MarketplaceSubCategory::select('id as sub_category_id', 'sub_category_name', 'parent_category_id')
            ->where('parent_category_id', $firstCategoryId)
            ->orderBy('id')
            ->get();
        }
      }

      $stores = MarketplaceStore::select('id as store_id', 'name as store_name')
        ->orderBy('id')
        ->get();

      $productsQuery = MarketplaceProducts::select(
        'id',
        'product_name',
        'product_short_name',
        'price',
        'discount_percentage',
        'description',
        'thumbnail',
        'threed_image',
        'product_video'
      );

      if (!empty($categoryIds)) {
        $productsQuery->whereIn('category_id', $categoryIds);
      }
      if ($request->filled('sub_category_id')) {
        $productsQuery->whereIn('sub_category_id', explode(',', $request->input('sub_category_id')));
      }

      if ($request->filled('store_id')) {
        $productsQuery->whereIn('store_id', $storeIds);
      }
      if ($request->filled('search')) {
        $search = $request->input('search');
        $productsQuery->where(function ($query) use ($search) {
          $query->where('product_name', 'like', '%' . $search . '%')
            ->orWhere('product_short_name', 'like', '%' . $search . '%')
            ->orWhere('description', 'like', '%' . $search . '%')
            ->orWhere('price', 'like', '%' . $search . '%')
            ->orWhere('discount_percentage', 'like', '%' . $search . '%');
        });
      }

      if ($request->filled('min_price') && $request->filled('max_price')) {
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $productsQuery->whereBetween('price', [$minPrice, $maxPrice]);
      }

      $sortOption = $request->input('sort');
      switch ($sortOption) {
        case 'high_to_low_price':
          $productsQuery->orderBy('price', 'desc');
          break;
        case 'low_to_high_price':
          $productsQuery->orderBy('price', 'asc');
          break;
        case 'a_to_z_name':
          $productsQuery->orderBy('product_name', 'asc');
          break;
        case 'z_to_a_name':
          $productsQuery->orderBy('product_name', 'desc');
          break;
        case 'gltf_file':
          $productsQuery->whereNotNull('threed_image');
          break;
        case 'video_file':
          $productsQuery->whereNotNull('product_video');
          break;
        case 'high_to_low_rating':
          $productsQuery->addSelect([
            'average_rating' => StoreProductReviews::selectRaw('COALESCE(AVG(rating), 0)')
              ->whereColumn('product_id', 'marketplace_products.id')
              ->groupBy('product_id'),
            'review_count' => StoreProductReviews::selectRaw('COUNT(*)')
              ->whereColumn('product_id', 'marketplace_products.id')
              ->groupBy('product_id')
          ])->orderBy('average_rating', 'desc');
          break;
        case 'low_to_high_rating':
          $productsQuery->addSelect([
            'average_rating' => StoreProductReviews::selectRaw('COALESCE(AVG(rating), 0)')
              ->whereColumn('product_id', 'marketplace_products.id')
              ->groupBy('product_id'),
            'review_count' => StoreProductReviews::selectRaw('COUNT(*)')
              ->whereColumn('product_id', 'marketplace_products.id')
              ->groupBy('product_id')
          ])->orderBy('average_rating', 'asc');
          break;
        default:
          $productsQuery->orderBy('id');
          break;
      }


      $limit = $request->input('limit', 10);
      $offset = $request->input('offset', 0);

      $totalProducts = $productsQuery->count();


      $products = $productsQuery->skip($offset)->take($limit)->get();
      $productIds = $products->pluck('id');


      $ratingsAndCounts = StoreProductReviews::select('product_id', DB::raw('COALESCE(AVG(rating), 0) as average_rating'), DB::raw('COUNT(*) as review_count'))
        ->whereIn('product_id', $productIds)
        ->groupBy('product_id')
        ->get()
        ->keyBy('product_id');


      $products->transform(function ($product) use ($ratingsAndCounts) {
        $ratingData = $ratingsAndCounts->get($product->id);
        $product->thumbnail = config("app.url") . 'uploads/' . $product->thumbnail;
        $product->average_rating = $ratingData ? (float) $ratingData->average_rating : 0;
        $product->review_count = $ratingData ? (int) $ratingData->review_count : 0;
        return $product;
      });


      $sidebar_data = [
        'category' => $categories,
        'subcategory' => $subcategories,
        'store' => $stores,
      ];

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Data retrieved successfully.',
        'toast' => true
      ], [
        'sidebardata' => $sidebar_data,
        'product_data' => $products,
        'total_products' => $totalProducts,
        'limit' => $limit,
        'offset' => $offset,
      ]);
    } catch (\Exception $e) {
      Log::error('Error retrieving data: ' . $e->getMessage());

      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Something went wrong',
        'toast' => true
      ]);
    }
  }


  public function getFlipbookTemporaryURL(Request $request)
  {
    try {
      $request->validate([
        'pdf_id' => 'required|integer|exists:flipbooks,id',
      ]);

      $pdfId = $request->input('pdf_id');

      $flipbook = Flipbook::find($pdfId);

      if (!$flipbook) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Flipbook not found', 'toast' => true]);
      }
      $pdfTemporaryUrl = getFileTemporaryURL($flipbook->pdf_file);
      $file_name = basename($flipbook->pdf_file, ".pdf");

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Flipbook URL retrieved successfully', 'toast' => true], ['pdf_file_url' => $pdfTemporaryUrl, 'file_name' => $file_name]);
    } catch (\Exception $e) {
      Log::error('Get PDF Temporary URL error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function searchProducts(Request $request)
  {
    $search = $request->query('name');

    $products = DB::table('products')
      ->where('name', 'like', '%' . $search . '%')
      ->orWhere('product_type', 'like', '%' . $search . '%')
      ->get();

    if ($products->isEmpty()) {
      return response()->json([
        'success' => false,
        'message' => 'No products found',
        'data' => []
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data' => [
        'products' => $products
      ]
    ]);
  }
  public function getCategoryList(Request $request)
  {
    try {
      $query = BlogCategory::query();
      $current_page = $request->input('current_page', 1);
      $perPage = $request->limit;

      if ($request->filled('search')) {
        $searchTerm = $request->input('search');
        if ($searchTerm) {
          $query->where('category', 'LIKE', '%' . $searchTerm . '%');
        }
      }

      $query->orderBy('id', 'desc');

      $offset = ($current_page - 1) * $perPage;

      $total = $query->count();
      $categories = $query->skip($offset)->take($perPage)->get();

      $pagination = [
        'current_page' => $current_page,
        'per_page' => $perPage,
        'total' => $total,
        'last_page' => ceil($total / $perPage),
      ];

      $result = array();
      foreach ($categories as $key => $category) {
        $category->categorys_id = $offset + $key + 1;
        $result[] = $category;
      }

      if ($total <= 0) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No category found matching the search criteria', 'toast' => true]);
      }

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Category retrieved successfully', 'toast' => true, 'data' => ['categories' => $result, 'pagination' => $pagination], 'count' => $total,]);
    } catch (\Exception $e) {
      Log::error('Error while retrieving blogs: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getInfluencer(Request $request)
  {
    try {
      $limit = $request->input('limit', 10);
      $page = $request->input('page', 1);
      $search = $request->input('search', '');
      $offset = ($page - 1) * $limit;

      $query = User::where('is_influencer', '1')->with('profile')->when($search, function ($q) use ($search) {
        $q->where('username', 'like', "%$search%");
      })->offset($offset)->limit($limit)->select('id', 'username')->get();

      if ($query->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No influencers found', 'toast' => true]);
      }

      $influencers = $query->map(function ($user) {
        $profileImagePath = $user->profile->profile_image_path ?? null;
        if ($profileImagePath) {
          $profileImagePath = getFileTemporaryURL($profileImagePath);
        }

        return [
          'id' => $user->id,
          'username' => $user->username,
          'profile_image_path' => $profileImagePath,
        ];
      });

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Influencers retrieved successfully', 'toast' => true], ['influencers' => $influencers]);
    } catch (\Exception $e) {
      Log::error('Get influencers error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'An error occurred while retrieving influencers', 'toast' => true]);
    }
  }
}
