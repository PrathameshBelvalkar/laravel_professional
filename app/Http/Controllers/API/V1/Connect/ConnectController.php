<?php

namespace App\Http\Controllers\API\V1\Connect;

use App\Events\CloseMeetingEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Connect\AdminJoinRequest;
use App\Http\Requests\Connect\DeleteScheduleMeetingRequest;
use App\Http\Requests\Connect\InviteMeetingRequest;
use App\Http\Requests\Connect\LeftMeetingRequest;
use App\Http\Requests\Connect\ScheduleMeetingListRequest;
use App\Http\Requests\Connect\ScheduleMeetingRequest;
use App\Http\Requests\Connect\SetVisibilityRequest;
use App\Http\Requests\Connect\UpdateSettingRequest;
use App\Mail\SendMail;
use App\Models\Account\Connection;
use App\Models\CalendarEvent;
use App\Models\Connect;
use App\Models\Connect\ConnectSetting;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ConnectController extends Controller
{
    public function createMeeting(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');
            $meeting = new Connect();
            $room_name = generateUniqueString("Connect", "room_name", 9, "upper");
            $meeting->user_id = $user->id;
            $meeting->room_name = $room_name;
            $meeting->status = "0";
            $meeting->save();

            $data['meeting'] = $meeting->toArray();
            $data['is_admin'] = true;
            $data['room_name'] = $room_name;
            $data['setting'] = null;
            $connectSettings = ConnectSetting::where("user_id", $user->id)->first();
            if (!$connectSettings) {
                $connectSettings = new ConnectSetting();
                $connectSettings->user_id = $user->id;
                $connectSettings->camera = "2";
                $connectSettings->mic = "2";
                $connectSettings->save();
            }
            $data['setting'] = $connectSettings->toArray();

            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Meeting created successfully", 'toast' => true], $data);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Error in Connect createMeeting " . $e->getMessage() . " file " . $e->getFile() . " at line no " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function verifyMeeting(Request $request, $room_name)
    {
        try {
            $user = $request->attributes->get('user');
            $meeting = Connect::where("room_name", $room_name)->whereNot("status", "2")->first();

            if ($meeting) {

                if ($meeting->meeting_start_time) {
                    $meetingStartTime = $meeting->meeting_start_time;

                    $meetingStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $meetingStartTime);

                    if ($meetingStartTime->greaterThan(now())) {
                        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Meeting not started yet", 'toast' => true]);
                    } else if ($meetingStartTime->lessThan(now())) {
                        $currentTime = Carbon::now();
                        $meetingEndTime = $meeting->meeting_end_time;
                        $meetingEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $meetingEndTime);
                        if (!$currentTime->between($meetingStartTime, $meetingEndTime)) {
                            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Meeting expired", 'toast' => true]);
                        }
                    } else {
                        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Meeting expired", 'toast' => true]);
                    }
                }
                if ($meeting->meeting_end_time) {
                    $meetingEndTime = $meeting->meeting_end_time;
                    $meetingEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $meetingEndTime);
                    if (!$meetingEndTime->greaterThan(now())) {
                        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Meeting time expired", 'toast' => true]);
                    }
                }
                if ($meeting->is_admin_joined == "0" && $meeting->user_id != $user->id)
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Meeting organizer has not joined yet", 'toast' => true]);

                $data['setting'] = null;
                $connectSettings = ConnectSetting::where("user_id", $user->id)->first();
                if (!$connectSettings) {
                    $connectSettings = new ConnectSetting();
                    $connectSettings->user_id = $user->id;
                    $connectSettings->camera = "2";
                    $connectSettings->mic = "2";
                    $connectSettings->save();
                }
                $data['setting'] = $connectSettings->toArray();
                $data['is_admin'] = false;
                $data['room_name'] = null;
                if ($meeting->user_id == $user->id) {
                    $data['is_admin'] = true;
                    $data['room_name'] = $meeting->room_name;
                    $data['meeting'] = $meeting->toArray();
                }
                if ($meeting->visibility) {
                    $visibility = $meeting->visibility;
                    if ($visibility == "1") {
                        $followings = $user->profile->following ? json_decode($user->profile->following, true) : [];
                        if ($followings && in_array($meeting->user_id, $followings)) {
                            $data['room_name'] = $meeting->room_name;
                        }
                    } else if ($visibility == "2") {
                        $connectionExists = Connection::where(function ($query) use ($user, $meeting) {
                            $query->where('user_1_id', $user->id)
                                ->where('user_2_id', $meeting->user_id);
                        })->orWhere(function ($query) use ($user, $meeting) {
                            $query->where('user_1_id', $meeting->user_id)
                                ->where('user_2_id', $user->id);
                        })->whereNull('deleted_at')->where("status", "1")->count();
                        if ($connectionExists) {
                            $data['room_name'] = $meeting->room_name;
                        }
                    } else if ($visibility == "3") {
                        $data['room_name'] = $meeting->room_name;
                    }
                }
                if (!$data['room_name'] && $meeting->invited_usernames) {
                    $invitedUsernames = explode(',', $meeting->invited_usernames);
                    if ($invitedUsernames && in_array($user->username, $invitedUsernames)) {
                        $data['room_name'] = $meeting->room_name;
                    }
                }
                if (!$data['room_name'] && $meeting->invited_emails) {
                    $invitedEmails = explode(',', $meeting->invited_emails);
                    if ($invitedEmails && in_array(strtolower($user->email), $invitedEmails)) {
                        $data['room_name'] = $meeting->room_name;
                    }
                }
                if ($data['room_name']) {
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Valid meeting code", 'toast' => true], $data);
                } else {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Invalid meeting code", 'toast' => true]);
                }
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Invalid meeting code", 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info("Error in Connect verifyMeeting " . $e->getMessage() . " file " . $e->getFile() . " at line no " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function inviteToMeeting(InviteMeetingRequest $request, $room_name)
    {
        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');

            $meeting = Connect::where('user_id', $user->id)->where('room_name', $room_name)->first();

            if (!$meeting) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Invalid meeting code", 'toast' => true]);
            }

            $invited_usernames = isset($request->invited_usernames) ? $request->invited_usernames : null;
            $invited_emails = isset($request->invited_emails) ? $request->invited_emails : null;
            $visibility = isset($request->visibility) ? $request->visibility : "0";
            $meeting_time_zone = isset($request->meeting_time_zone) ? getTimeZone($request->meeting_time_zone) : "UTC";
            $phoneNumberArray = [];
            $emailUserArray = [];
            $followers = [];
            $connections = [];
            $usernameArray = [];
            $otherMails = [];

            if ($visibility) {
                if ($visibility == "1") {
                    $followers = $user->profile->followers ? json_decode($user->profile->followers, true) : [];
                    $tempConnections = Connection::where('status', '1')->where(function ($query) use ($user) {
                        $query->where('user_1_id', $user->id)
                            ->orWhere('user_2_id', $user->id);
                    })->selectRaw("CASE WHEN user_1_id = {$user->id} THEN user_2_id ELSE user_1_id END as connected_user")->pluck("connected_user");
                    $connections = $tempConnections->toArray();
                } else if ($visibility == "2") {
                    $tempConnections = Connection::where('status', '1')->where(function ($query) use ($user) {
                        $query->where('user_1_id', $user->id)
                            ->orWhere('user_2_id', $user->id);
                    })->selectRaw("CASE WHEN user_1_id = {$user->id} THEN user_2_id ELSE user_1_id END as connected_user")->pluck("connected_user");
                    $connections = $tempConnections->toArray();
                }
            }
            $meeting->visibility = $visibility;
            if ($invited_usernames) {
                $invited_users = User::whereIn("username", $invited_usernames)->selectRaw('id as user_id')->pluck('user_id');
                if ($invited_users) {
                    $usernameArray = $invited_users->toArray();
                }
                $meeting->invited_usernames = implode(",", $invited_usernames);
            } else {
                $meeting->invited_usernames = null;
            }
            if ($invited_emails) {
                $invited_email_users = User::whereIn("email", $invited_emails)->selectRaw('id as user_id')->pluck('user_id');
                $valid_invited_email_users = User::whereIn("email", $invited_emails)->selectRaw('email')->pluck('email');
                if ($invited_email_users) {
                    $emailUserArray = $invited_email_users->toArray();
                }
                if ($valid_invited_email_users) {
                    $valid_invited_email_users = $valid_invited_email_users->toArray();
                    $otherMails = array_diff($invited_emails, $valid_invited_email_users);
                }
                $meeting->invited_emails = strtolower(implode(",", $invited_emails));
            } else {
                $meeting->invited_emails = null;
            }
            $final_userId_array = array_unique(array_merge($followers, $connections, $usernameArray, $emailUserArray));

            // save meeting information
            $meeting->save();

            $this->addCalendarEvent($user, $meeting, $final_userId_array);
            $this->addConnectNotifications($user, $meeting, $request);

            if ($final_userId_array)
                $this->addConnectMails($user, $final_userId_array, $meeting);

            DB::commit();
            // send emails to all final_userId_array
            $all_emails = User::whereIn("id", $final_userId_array)->select(['email'])->pluck('email');
            $all_emails = $all_emails->toArray();

            // temporarily invitations not sent to otherMails 
            /* foreach ($otherMails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                } else {
                    $all_emails[] = $email;
                }
            } */


            $updated_meetings = Connect::where("connects.id", $meeting->id)->leftJoin("users", "connects.user_id", "=", "users.id")->selectRaw("connects.*,users.username as hostname")->orderBy("meeting_start_time", "asc")->get();


            $updated_meetings->transform(function ($meeting) use ($meeting_time_zone, $user) {

                $meeting->host = $user->id == $meeting->user_id ? true : false;

                $meeting->status = $meeting->host ? $meeting->status : null;
                $meeting->invited_emails = $meeting->host ? $meeting->invited_emails : null;
                $meeting->invited_usernames = $meeting->host ? $meeting->invited_usernames : null;
                $invited_emails_list = !empty($meeting->invited_emails) ? explode(",", $meeting->invited_emails) : [];
                $invited_usernames_list = !empty($meeting->invited_usernames) ? explode(",", $meeting->invited_usernames) : [];
                $meeting->invited_emails_list = $meeting->host ? $invited_emails_list : [];
                $meeting->invited_usernames_list = $meeting->host ? $invited_usernames_list : [];
                $meeting->visibility = $meeting->host ? $meeting->visibility : null;

                unset($meeting->created_at, $meeting->updated_at, $meeting->deleted_at, $meeting->user_id, $meeting->password, $meeting->phone_number, $meeting->meeting_time_zone);

                $meeting->meeting_start_time = convertTimeZone($meeting->meeting_start_time, "UTC", $meeting_time_zone);
                $meeting->meeting_end_time = convertTimeZone($meeting->meeting_end_time, "UTC", $meeting_time_zone);
                return $meeting;
            });

            if (!$all_emails) {
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Setting Updated", 'toast' => true], ["meeting" => $meeting->toArray(), "updated_meetings" => $updated_meetings->toArray()]);
            }
            $message = ucfirst($user->username) . " invites you to join connect meeting";
            $subject = config('app.app_name') . " Connect invitation";
            $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
            $emailData['title'] = $subject;
            $emailData['username'] = ucfirst($user->username);
            $emailData['message'] = $message;
            $emailData['subject'] = $subject;
            $emailData['link'] = config("app.connect_url") . "connect/" . $meeting->room_name;

            $emailData['meeting_start_time'] = "";
            $emailData['meeting_end_time'] = "";
            $emailData['meeting_time_zone'] = $meeting_time_zone;
            if ($meeting->meeting_start_time)
                $emailData['meeting_start_time'] = convertTimeZone($meeting->meeting_start_time, "UTC", $meeting_time_zone, "l, F d, Y h:i A");
            if ($meeting->meeting_end_time)
                $emailData['meeting_end_time'] = convertTimeZone($meeting->meeting_end_time, "UTC", $meeting_time_zone, "l, F d, Y h:i A");

            $emailData['linkTitle'] = "Connect";
            $emailData['supportMail'] = config('app.support_mail');
            $emailData['projectName'] = config('app.app_name');
            $emailData['meeting_code'] = $meeting->room_name;
            $emailData['view'] = "mail-templates.connect-invitation";

            Mail::to($user->email)->bcc($all_emails)->send(new SendMail($emailData, $emailData['view']));

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Invitation sending in process", 'toast' => true], ["meeting" => $meeting->toArray(), "updated_meetings" => $updated_meetings->toArray()]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::info("Error in Connect inviteToMeeting " . $e->getMessage() . " file " . $e->getFile() . " at line no " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function updateSettings(UpdateSettingRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');
            $action = $request->action;
            $connectSettings = ConnectSetting::where("user_id", $user->id)->first();
            if (!$connectSettings) {
                $connectSettings = new ConnectSetting();
                $connectSettings->user_id = $user->id;
            }
            $message = "Connect settings retrieved";
            if ($action == "update") {
                $message = "Connect settings updated";
                $connectSettings->camera = isset($request->camera) ? $request->camera : "2";
                $connectSettings->mic = isset($request->mic) ? $request->mic : "2";
            }
            $connectSettings->save();
            DB::commit();
            $newSettings = $connectSettings->where("user_id", $user->id)->first();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'toast' => true], ['settings' => $newSettings->toArray()]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::info("Error in Connect updateMeeting " . $e->getMessage() . " file " . $e->getFile() . " at line no " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function closeMeeting(InviteMeetingRequest $request, $room_name)
    {
        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');
            $meeting = Connect::where('user_id', $user->id)->where('room_name', $room_name)->first();

            if (!$meeting) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Invalid meeting code", 'toast' => true]);
            }

            $meeting->status = "2";
            $meeting->admin_id = null;
            $meeting->is_admin_joined = "0";
            $meeting->save();
            DB::commit();
            $meeting_usernames = $this->getMeetingsUserIds($user, $meeting, false, true);
            if ($meeting_usernames) {
                $authToken = $request->header('authToken');
                $data['room_name'] = $room_name;
                $data['host'] = $user->username;
                $title = ucfirst($user->username) . "  closed meeting";
                $body = ucfirst($user->username) . " has closed meeting";
                sendSocketNotification($user->id, $title, $body, "19", $user->username, $authToken, "notification_left_" . $room_name, $data);
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Meeting closed", 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::info("Error in Connect closeMeeting " . $e->getMessage() . " file " . $e->getFile() . " at line no " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function isPublicMeeting(Request $request, $room_name)
    {
        try {
            $meeting = Connect::where('room_name', $room_name)->where("visibility", "3")->whereNot("status", "2")->first();
            if (!$meeting) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Not public meeting", 'toast' => true]);
            }
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Valid public meeting", 'toast' => true]);
        } catch (\Exception $e) {
            Log::info("Error in Connect isPublicMeeting " . $e->getMessage() . " file " . $e->getFile() . " at line no " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function scheduleMeeting(ScheduleMeetingRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');

            $meeting_time_zone = isset($request->meeting_time_zone) ? getTimeZone($request->meeting_time_zone) : "UTC";
            $title = isset($request->title) ? $request->title : null;
            $description = isset($request->description) ? $request->description : null;
            $meeting_start_time = $request->meeting_start_time;
            $meeting_end_time = $request->meeting_end_time;
            $id = isset($request->id) ? $request->id : null;

            if ($meeting_time_zone != "UTC") {
                $meeting_start_time = convertTimeZone($meeting_start_time, $meeting_time_zone, "UTC");
                $meeting_end_time = convertTimeZone($meeting_end_time, $meeting_time_zone, "UTC");
            }
            if ($meeting_start_time <= date("Y-m-d H")) {
                return generateResponse(['type' => 'info', 'code' => 200, 'status' => false, 'message' => 'Start time should be greater than or equal to current time']);
            }

            if ($meeting_end_time <= $meeting_start_time) {
                return generateResponse(['type' => 'info', 'code' => 200, 'status' => false, 'message' => 'End time should be greater than or equal to start time']);
            }

            if ($id) {
                $meeting = Connect::where("id", $id)->where("user_id", $user->id)->first();
                $room_name = $meeting->room_name;
            } else {
                $meeting = new Connect();
                $room_name = generateUniqueString("Connect", "room_name", 9, "upper");
                $meeting->user_id = $user->id;
                $meeting->room_name = $room_name;
                $meeting->status = "0";
            }
            $meeting->meeting_start_time = $meeting_start_time;
            $meeting->meeting_end_time = $meeting_end_time;
            $meeting->meeting_time_zone = $meeting_time_zone;
            $meeting->description = $description;
            $meeting->title = $title;
            $meeting->is_admin_joined = "0";
            $meeting->save();

            $data['meeting'] = $meeting->toArray();
            $data['is_admin'] = true;
            $data['room_name'] = $room_name;
            $data['meeting_start_time'] = $meeting_start_time;
            $data['meeting_end_time'] = $meeting_end_time;
            $data['meeting_time_zone'] = $meeting_time_zone;
            $data['setting'] = null;
            $connectSettings = ConnectSetting::where("user_id", $user->id)->first();
            if (!$connectSettings) {
                $connectSettings = new ConnectSetting();
                $connectSettings->user_id = $user->id;
                $connectSettings->camera = "2";
                $connectSettings->mic = "2";
                $connectSettings->save();
            }
            $data['setting'] = $connectSettings->toArray();

            $updated_meetings = Connect::where("connects.id", $meeting->id)->leftJoin("users", "connects.user_id", "=", "users.id")->selectRaw("connects.*,users.username as hostname")->orderBy("meeting_start_time", "asc")->get();


            $updated_meetings->transform(function ($meeting) use ($meeting_time_zone, $user) {

                $meeting->host = $user->id == $meeting->user_id ? true : false;

                $meeting->status = $meeting->host ? $meeting->status : null;
                $meeting->invited_emails = $meeting->host ? $meeting->invited_emails : null;
                $meeting->invited_usernames = $meeting->host ? $meeting->invited_usernames : null;
                $invited_emails_list = !empty($meeting->invited_emails) ? explode(",", $meeting->invited_emails) : [];
                $invited_usernames_list = !empty($meeting->invited_usernames) ? explode(",", $meeting->invited_usernames) : [];
                $meeting->invited_emails_list = $meeting->host ? $invited_emails_list : [];
                $meeting->invited_usernames_list = $meeting->host ? $invited_usernames_list : [];
                $meeting->visibility = $meeting->host ? $meeting->visibility : null;

                unset($meeting->created_at, $meeting->updated_at, $meeting->deleted_at, $meeting->user_id, $meeting->password, $meeting->phone_number, $meeting->meeting_time_zone);

                $meeting->meeting_start_time = convertTimeZone($meeting->meeting_start_time, "UTC", $meeting_time_zone);
                $meeting->meeting_end_time = convertTimeZone($meeting->meeting_end_time, "UTC", $meeting_time_zone);
                return $meeting;
            });
            $data['updated_meetings'] = $updated_meetings;
            $action = $id ? 'update' : 'add';
            $this->addCalendarEvent($user, $meeting, null, $action);
            if ($id)
                $this->addConnectNotifications($user, $meeting, $request);
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Meeting scheduled", 'toast' => true], $data);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Error in ScheduleMeeting " . $e->getMessage() . " file " . $e->getFile() . " at line " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function scheduleMeetingList(ScheduleMeetingListRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $meeting_time_zone = isset($request->meeting_time_zone) ? getTimeZone($request->meeting_time_zone) : "UTC";
            $duration_length = $request->duration_length;
            $duration_type = $request->duration_type;
            $duration = $request->duration;
            $connectSettings = ConnectSetting::where("user_id", $user->id)->first();
            if (!$connectSettings) {
                $connectSettings = new ConnectSetting();
                $connectSettings->user_id = $user->id;
                $connectSettings->camera = "2";
                $connectSettings->mic = "2";
                $connectSettings->save();
            }
            $connectSettings = $connectSettings->toArray();
            $lastTime = Carbon::now();
            switch ($duration_type) {
                case 'days':
                    $lastTime = $duration == "length" ? Carbon::now()->addDays($duration_length) : Carbon::now()->endOfDay();
                    break;
                case 'months':
                    $lastTime = $duration == "length" ? Carbon::now()->addMonths($duration_length) : Carbon::now()->endOfMonth();
                    break;
                case 'hours':
                    $lastTime = $duration == "length" ? Carbon::now()->addHours($duration_length) : Carbon::now()->endOfHour();
                    break;
                case 'minutes':
                    $lastTime = $duration == "length" ? Carbon::now()->addMinutes($duration_length) : Carbon::now()->endOfMinute();
                    break;
                case 'years':
                    $lastTime = $duration == "length" ? Carbon::now()->addYears($duration_length) : Carbon::now()->endOfYear();
                    break;
                case 'weeks':
                    $lastTime = $duration == "length" ? Carbon::now()->addWeeks($duration_length) : Carbon::now()->endOfWeek();
                    break;
            }

            $lastTime = $lastTime->format('Y-m-d H:i:s');



            $self_meetings = Connect::where(function ($query) use ($lastTime) {
                $query->where('meeting_start_time', '>=', date('Y-m-d H:i:s'))
                    ->where('meeting_start_time', '<', $lastTime)
                    ->orWhere('meeting_end_time', '>=', date('Y-m-d H:i:s'))
                    ->where('meeting_end_time', '<', $lastTime);
            })->where("user_id", $user->id)->selectRaw("id")->pluck("id");

            $by_emails_meetings = Connect::where(function ($query) use ($lastTime) {
                $query->where('meeting_start_time', '>=', date('Y-m-d H:i:s'))
                    ->where('meeting_start_time', '<', $lastTime)
                    ->orWhere('meeting_end_time', '>=', date('Y-m-d H:i:s'))
                    ->where('meeting_end_time', '<', $lastTime);
            })->whereRaw("FIND_IN_SET(?, invited_emails)", [$user->email])->whereNot("user_id", $user->id)->selectRaw("id")->pluck("id");

            $by_usernames_meetings_query = Connect::query();
            $by_usernames_meetings_query->where(function ($query) use ($lastTime) {
                $query->where('meeting_start_time', '>=', date('Y-m-d H:i:s'))
                    ->where('meeting_start_time', '<', $lastTime)
                    ->orWhere('meeting_end_time', '>=', date('Y-m-d H:i:s'))
                    ->where('meeting_end_time', '<', $lastTime);
            });
            $by_usernames_meetings_query->whereRaw("FIND_IN_SET(?, invited_usernames)", [$user->username]);
            $by_usernames_meetings = $by_usernames_meetings_query->whereNot("user_id", $user->id)->selectRaw("id")->pluck("id");




            $following_meetings = [];
            $followings = $user->profile->following ? json_decode($user->profile->following, true) : [];
            if ($followings) {
                $following_meetings = Connect::where(function ($query) use ($lastTime) {
                    $query->where('meeting_start_time', '>=', date('Y-m-d H:i:s'))
                        ->where('meeting_start_time', '<', $lastTime)
                        ->orWhere('meeting_end_time', '>=', date('Y-m-d H:i:s'))
                        ->where('meeting_end_time', '<', $lastTime);
                })->whereIn("user_id", $followings)->whereNot("user_id", $user->id)->whereIn("visibility", ['1', "3"])->selectRaw("id")->pluck("id");
            }

            $connections = Connection::where('status', '1')->where(function ($query) use ($user) {
                $query->where('user_1_id', $user->id)
                    ->orWhere('user_2_id', $user->id);
            })->selectRaw("CASE WHEN user_1_id = {$user->id} THEN user_2_id ELSE user_1_id END as connected_user_id")->pluck("connected_user_id");

            $connected_meetings = [];

            if ($connections) {
                $connected_meetings = Connect::where(function ($query) use ($lastTime) {
                    $query->where('meeting_start_time', '>=', date('Y-m-d H:i:s'))
                        ->where('meeting_start_time', '<', $lastTime)
                        ->orWhere('meeting_end_time', '>=', date('Y-m-d H:i:s'))
                        ->where('meeting_end_time', '<', $lastTime);
                })->whereIn("user_id", $connections->toArray())->whereNot("user_id", $user->id)->whereIn("visibility", [
                            "1",
                            "2",
                            "3"
                        ])->selectRaw("id")->pluck("id");
            }

            $unique_upcoming_meetings_ids = [];

            $by_usernames_meetings = ($by_usernames_meetings) ? $by_usernames_meetings->toArray() : [];
            $self_meetings = ($self_meetings) ? $self_meetings->toArray() : [];
            $by_emails_meetings = ($by_emails_meetings) ? $by_emails_meetings->toArray() : [];
            $following_meetings = ($following_meetings) ? $following_meetings->toArray() : [];
            $connected_meetings = ($connected_meetings) ? $connected_meetings->toArray() : [];
            $unique_upcoming_meetings_ids = [];
            if ($by_usernames_meetings || $by_emails_meetings || $following_meetings || $connected_meetings || $self_meetings) {

                $unique_upcoming_meetings_ids = array_unique(array_merge($self_meetings, $by_emails_meetings, $by_usernames_meetings, $following_meetings, $connected_meetings));
            }

            if ($unique_upcoming_meetings_ids) {
                $meetings = Connect::whereIn("connects.id", $unique_upcoming_meetings_ids)->leftJoin("users", "connects.user_id", "=", "users.id")->whereNot("connects.status", "2")->selectRaw("connects.*,users.username as hostname")->orderBy("meeting_start_time", "asc")->get();

                $meetings->transform(function ($meeting) use ($meeting_time_zone, $user) {

                    $meeting->host = $user->id == $meeting->user_id ? true : false;

                    $meeting->status = $meeting->host ? $meeting->status : null;
                    $meeting->invited_emails = $meeting->host ? $meeting->invited_emails : null;
                    $meeting->invited_usernames = $meeting->host ? $meeting->invited_usernames : null;
                    $invited_emails_list = !empty($meeting->invited_emails) ? explode(",", $meeting->invited_emails) : [];
                    $invited_usernames_list = !empty($meeting->invited_usernames) ? explode(",", $meeting->invited_usernames) : [];
                    $meeting->invited_emails_list = $meeting->host ? $invited_emails_list : [];
                    $meeting->invited_usernames_list = $meeting->host ? $invited_usernames_list : [];
                    $meeting->visibility = $meeting->host ? $meeting->visibility : null;

                    unset($meeting->created_at, $meeting->updated_at, $meeting->deleted_at, $meeting->user_id, $meeting->password, $meeting->phone_number, $meeting->meeting_time_zone);

                    $meeting->meeting_start_time = convertTimeZone($meeting->meeting_start_time, "UTC", $meeting_time_zone);
                    $meeting->meeting_end_time = convertTimeZone($meeting->meeting_end_time, "UTC", $meeting_time_zone);
                    return $meeting;
                });

                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Scheduled meeting list retrieved", 'toast' => true], ['meetings' => $meetings->toArray(), "settings" => $connectSettings]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "No scheduled meetings found", 'toast' => true], ["settings" => $connectSettings]);
            }
        } catch (\Exception $e) {
            Log::info("Error in scheduleMeetingList " . $e->getMessage() . " file " . $e->getFile() . " at line " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function deleteScheduleMeeting(DeleteScheduleMeetingRequest $request)
    {
        try {
            DB::beginTransaction();
            $id = $request->id;
            $user = $request->attributes->get('user');
            $meeting = Connect::where("id", $id)->where("user_id", $user->id)->first();
            $meeting->delete();

            Notification::where("module", "19")->where("reference_id", $id)->delete();
            CalendarEvent::where("meeting_id", $id)->delete();

            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Meeting deleted", 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Error in deleteScheduleMeeting " . $e->getMessage() . " file " . $e->getFile() . " at line " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    private function addCalendarEvent($user, $meeting, $user_ids, $action = "add")
    {
        if (!$meeting->meeting_start_time || $meeting->meeting_end_time) {
            return false;
        }
        if (!$user_ids) {
            $user_ids = $this->getMeetingsUserIds($user, $meeting);
        }

        if ($user_ids)
            $user_ids = array_unique($user_ids);

        if ($action == "add") {
            $parent_id = null;
            $calendarData = [
                'user_id' => $meeting->user_id,
                'event_title' => $meeting->title ?: 'New Meetings',
                'category' => "Meeting",
                'start_date_time' => date("Y-m-d H:i:s", strtotime($meeting->meeting_start_time)),
                'end_date_time' => date("Y-m-d H:i:s", strtotime($meeting->meeting_end_time)),
                'event_description' => $meeting->description ? $meeting->description : 'New Meetings',
                'meetingLink' => config("app.connect_url") . "connect/" . $meeting->room_name,
                // 'link' => config("app.connect_url") . "connect/" . $meeting->room_name,
                'subCategory' => config("app.app_short_name") . " Connect",
                'meeting_id' => $meeting->id,
                'invited_by_email' => $meeting->invited_emails,
                'invited_by_username' => $meeting->invited_usernames,
                'organizer_user_id' => $meeting->user_id,
            ];
            $meetingEvent = CalendarEvent::where('user_id', $meeting->user_id)->where("meeting_id", $meeting->id)->first();
            if (!$meetingEvent) {
                $meetingEvent = CalendarEvent::create($calendarData);
            } else {
                $tempCalendarData = $calendarData;
                $tempCalendarData['parent_id'] = null;
                unset($tempCalendarData['user_id'], $tempCalendarData['meeting_id']);
                $meetingEvent->update($tempCalendarData);
            }
            $parent_id = $meetingEvent->id;
            foreach ($user_ids as $user_id) {
                if ($user_id == $meeting->user_id)
                    continue;
                $calendarData['user_id'] = $user_id;
                $calendarData['parent_id'] = $parent_id;
                $calendarData['event_title'] = $meeting->title ? $meeting->title : 'New meetings invitation from ' . ucfirst($user->username);

                $guestMeetingEvent = CalendarEvent::where('user_id', $user_id)->where("meeting_id", $meeting->id)->first();
                if (!$guestMeetingEvent) {
                    $guestMeetingEvent = CalendarEvent::create($calendarData);
                } else {
                    $tempGuestCalendarData = $calendarData;
                    unset($tempGuestCalendarData['user_id'], $tempGuestCalendarData['meeting_id']);
                    $guestMeetingEvent->update($tempGuestCalendarData);
                }
            }
        } else {

            $dataToUpdate = [
                'start_date_time' => date("Y-m-d H:i:s", strtotime($meeting->meeting_start_time)),
                'end_date_time' => date("Y-m-d H:i:s", strtotime($meeting->meeting_end_time)),
                'event_description' => $meeting->description ? $meeting->description : 'New Meetings',
                'invited_by_email' => $meeting->invited_emails,
                'invited_by_username' => $meeting->invited_usernames,
            ];
            $dataToUpdate['meetingLink'] = config("app.connect_url") . "connect/" . $meeting->room_name;
            // $dataToUpdate['link'] = config("app.connect_url") . "connect/" . $meeting->room_name;
            $dataToUpdate['event_title'] = $meeting->title ? $meeting->title : 'New meetings invitation from ' . ucfirst($user->username);
            CalendarEvent::where('meeting_id', $meeting->id)->whereNot("user_id", $user->id)->update($dataToUpdate);
            $dataToUpdate['event_title'] = $meeting->title ?: 'New Meetings';
            CalendarEvent::where('meeting_id', $meeting->id)->where("user_id", $user->id)->update($dataToUpdate);
        }
        $calendarDataUsers = CalendarEvent::where('meeting_id', $meeting->id)->select(['user_id'])->whereNot("user_id", $user->id)->pluck('user_id');
        $calendarDataUsers = $calendarDataUsers ? $calendarDataUsers->toArray() : [];

        $removedUserIds = array_diff($calendarDataUsers, $user_ids);
        if ($removedUserIds) {
            CalendarEvent::whereIn("user_id", $removedUserIds)->where("meeting_id", $meeting->id)->delete();
        }
    }
    private function addConnectNotifications($user, $meeting, $request, $onlyVisibility = false)
    {
        $user_ids = $this->getMeetingsUserIds($user, $meeting, $onlyVisibility);

        if ($user_ids) {
            if (!$onlyVisibility) {
                deleteNotifications($user_ids, "19", $meeting->id);
            }
            $authToken = $request->header('authToken');
            $title = $meeting->title ? "Meetings invitation from " . ucfirst($user->username) . " for " . $meeting->title : 'New meetings invitation from ' . ucfirst($user->username);
            $link = config("app.connect_url") . "connect/" . $meeting->room_name;
            $description = $meeting->description ?: $title;
            addNotificationsBulk($user_ids, $user->id, $title, $description, $meeting->id, "19", $link, null, $authToken);
        }
    }
    private function getMeetingsUserIds($user, $meeting, $onlyVisibility = false, $usernames = null)
    {
        $user_ids = [];
        $tempInvitedUserName = $meeting->invited_usernames ? explode(",", $meeting->invited_usernames) : [];
        $tempInvitedUserEmails = $meeting->invited_emails ? explode(",", $meeting->invited_emails) : [];
        if ($tempInvitedUserName && !$onlyVisibility) {
            $tempUsernameIds = User::whereIn("username", $tempInvitedUserName)->select(['id'])->pluck("id");
            if ($tempUsernameIds) {
                $tempUsernameIds = $tempUsernameIds->toArray();
                $user_ids = array_merge($user_ids, $tempUsernameIds);
            }
        }
        if ($tempInvitedUserEmails && !$onlyVisibility) {
            $tempUserEmailIds = User::whereIn("email", $tempInvitedUserEmails)->select(['id'])->pluck("id");
            if ($tempUserEmailIds) {
                $tempUserEmailIds = $tempUserEmailIds->toArray();
                $user_ids = array_merge($user_ids, $tempUserEmailIds);
            }
        }
        $userIds = getConnectionsAndFollowerUserIds($user->id);
        if ($userIds['connection_user_ids'])
            if ($meeting->visibility == "2" || $meeting->visibility == "1")
                $user_ids = array_merge($user_ids, $userIds['connection_user_ids']);
        if ($userIds['follower_user_ids'] && $meeting->visibility == "1")
            $user_ids = array_merge($user_ids, $userIds['follower_user_ids']);

        if ($user_ids) {
            $user_ids = array_unique($user_ids);
        }
        if ($usernames) {
            $usernames = User::whereIn("id", $user_ids)->pluck("username");
            if ($usernames) {
                $usernames = $usernames->toArray();
                return $usernames;
            }
        }
        return $user_ids;
    }
    public function setMeetingVisibility(SetVisibilityRequest $request, $room_name)
    {

        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');
            $visibility = $request->visibility;
            $meeting = Connect::where('user_id', $user->id)->where('room_name', $room_name)->first();

            if (!$meeting) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Invalid meeting code", 'toast' => true]);
            }
            $meeting->visibility = $visibility;
            $meeting->save();
            DB::commit();
            $this->addConnectNotifications($user, $meeting, $request, true);
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Setting Updated", 'toast' => true], );
        } catch (\Exception $e) {
            DB::rollback();
            Log::info("Error in Connect setMeetingVisibility " . $e->getMessage() . " file " . $e->getFile() . " at line no " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function sendMeetingAlert(Request $request)
    {
        try {
            $now = Carbon::now();
            $fiveMinutesLater = $now->copy()->addMinutes(5);

            $meetings = Connect::whereNot('status', "2")
                ->where("meeting_start_time", '>=', $now)
                ->where("meeting_start_time", '<', $fiveMinutesLater)
                ->get();

            if ($meetings) {
                $meetings = $meetings->toArray();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Sending alert in process", 'toast' => true]);
            } else {
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "No Meetings in upcoming 5 minutes", 'toast' => true]);
            }



        } catch (\Exception $e) {
            Log::info("Error in Connect sendMeetingAlert " . $e->getMessage() . " file " . $e->getFile() . " at line no " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function adminJoin(AdminJoinRequest $request, $room_name)
    {
        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');
            $is_admin_joined = $request->is_admin_joined;
            $admin_id = $request->admin_id;
            $meeting = Connect::where("room_name", $room_name)->where("user_id", $user->id)->whereNot("status", "2")->first();

            if (!$meeting) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "No meeting found", 'toast' => true]);
            }
            $tempResponse = ['status' => true, 'message' => ""];
            if ($meeting->meeting_start_time) {
                $meetingStartTime = $meeting->meeting_start_time;

                $meetingStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $meetingStartTime);

                if ($meetingStartTime->greaterThan(now())) {
                    $tempResponse = ['status' => false, 'message' => "Meeting not started yet"];
                    $is_admin_joined = "0";
                } else if ($meetingStartTime->lessThan(now())) {
                    $currentTime = Carbon::now();
                    $meetingEndTime = $meeting->meeting_end_time;
                    $meetingEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $meetingEndTime);
                    if (!$currentTime->between($meetingStartTime, $meetingEndTime)) {
                        $tempResponse = ['status' => false, 'message' => "Meeting expired"];
                        $is_admin_joined = "0";
                    }
                } else {
                    $tempResponse = ['status' => false, 'message' => "Meeting expired"];
                    $is_admin_joined = "0";
                }
            }
            if ($meeting->meeting_end_time) {
                $meetingEndTime = $meeting->meeting_end_time;
                $meetingEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $meetingEndTime);
                if (!$meetingEndTime->greaterThan(now())) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Meeting time expired", 'toast' => true]);
                }
            }

            if ($is_admin_joined) {
                $meeting->admin_id = $admin_id;
                $meeting->is_admin_joined = "1";
            } else {
                $meeting->admin_id = null;
                $meeting->is_admin_joined = "0";
            }
            $meeting->save();
            DB::commit();

            if ($is_admin_joined) {
                $meeting_usernames = $this->getMeetingsUserIds($user, $meeting, false, true);
                if ($meeting_usernames) {
                    $authToken = $request->header('authToken');
                    $data['room_name'] = $room_name;
                    $data['usernames'] = $meeting_usernames;
                    $data['host'] = $user->username;
                    $title = $meeting->title ? $meeting->title . " meeting started" : "Meeting started";
                    $body = ucfirst($user->username) . " has joined meeting started";
                    sendSocketNotification($user->id, $title, $body, "19", $user->username, $authToken, "notification_organizer_joined", $data);
                }
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Meeting organizer status updated", 'toast' => true]);


        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Error in Connect sendMeetingAlert " . $e->getMessage() . " file " . $e->getFile() . " at line no " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    public function leaveMeeting(LeftMeetingRequest $request, $room_name)
    {
        try {
            DB::beginTransaction();
            $admin_id = $request->admin_id;
            $meeting = Connect::where("room_name", $room_name)->where("admin_id", $admin_id)->first();
            if ($meeting) {
                $meeting->admin_id = null;
                $meeting->is_admin_joined = "0";
                $meeting->save();
                DB::commit();
                $user = User::where("id", $meeting->user_id)->first();
                $meeting_usernames = $this->getMeetingsUserIds($user, $meeting, false, true);
                if ($meeting_usernames) {
                    $authToken = generateAuthToken($user);
                    $data['room_name'] = $room_name;
                    $data['host'] = $user->username;
                    $title = ucfirst($user->username) . "  left meeting";
                    $body = ucfirst($user->username) . " has left meeting";
                    sendSocketNotification($user->id, $title, $body, "19", $user->username, $authToken, "notification_left_" . $room_name, $data);
                }
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "No meeting found", 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Error in Connect adminJoin " . $e->getMessage() . " file " . $e->getFile() . " at line no " . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true]);
        }
    }
    private function addConnectMails($user, $userIds, $meeting)
    {
        $request = new \stdClass();
        $emailData['meeting_code'] = $meeting->room_name;
        $emailData['message'] = ucfirst($user->username) . " invites you to join connect meeting";
        $emailData['link'] = config("app.connect_url") . "connect/" . $meeting->room_name;
        $emailData['linkTitle'] = "Connect";
        $emailData['meeting_start_time'] = "";
        $emailData['meeting_end_time'] = "";
        $emailData['meeting_time_zone'] = $meeting->meeting_time_zone;
        $emailData['logo'] = asset('assets/images/mail_public/Welcome.png');
        if ($meeting->meeting_start_time)
            $emailData['meeting_start_time'] = convertTimeZone($meeting->meeting_start_time, "UTC", $meeting->meeting_time_zone, "l, F d, Y h:i A");
        if ($meeting->meeting_end_time)
            $emailData['meeting_end_time'] = convertTimeZone($meeting->meeting_end_time, "UTC", $meeting->meeting_time_zone, "l, F d, Y h:i A");
        $request->message = view('mail-templates.connect-mail', compact('emailData'))->render();
        $request->subject = config('app.app_name') . " Connect invitation";
        $bcc = User::whereIn("id", $userIds)->selectRaw("concat(username,'@','silocloud.io') as user_email_address")->pluck("user_email_address");
        $bccUsers = User::whereIn("id", $userIds)->selectRaw("id as bcc_user_ids")->pluck("bcc_user_ids");
        $bcc = $bcc->toArray();
        $request->recipients = $bcc[0];
        unset($bcc[0]);
        if ($bcc)
            $request->bcc = implode(",", $bcc);
        else
            $request->bcc = "";

        $bccUsers = $bccUsers->toArray();
        if ($bccUsers)
            $isRecipients = $bccUsers[0];
        else
            $isRecipients = null;

        $request->cc = "";
        $is_draft = 0;
        $mail_sent = saveOrUpdateMail($request, $user, $is_draft, null, $isRecipients);
        createMailReply($user, $request, null, $isRecipients, null, $mail_sent ? $mail_sent->id : null, null, null);
    }
}
