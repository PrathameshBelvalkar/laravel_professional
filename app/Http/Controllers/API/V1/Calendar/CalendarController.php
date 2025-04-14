<?php

namespace App\Http\Controllers\API\V1\calendar;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\SendMail;
use Illuminate\Http\Request;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\calendar\CalendarEventAddRequest;
use App\Http\Requests\calendar\CalendarEventUpdateRequest;
use Illuminate\Support\Facades\Validator;
use DateTime;
use DateTimeZone;

class CalendarController extends Controller
{
    public function add(CalendarEventAddRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $timezone = $request->header('Timezone');
            $authToken = $request->header('authToken');
            $newTZ = new DateTimeZone($timezone);
            $GMT = new DateTimeZone("GMT");
            $current_date_time = new DateTime();
            $current_date = $current_date_time->format('Y-m-d H:i:s');

            $start_date = new DateTime($request->start_date_time, $newTZ);
            $start_date->setTimezone($GMT);
            $startDateTimeGMT = $start_date->format('Y-m-d H:i:s');

            $end_date = new DateTime($request->end_date_time, $newTZ);
            $end_date->setTimezone($GMT);
            $endDateTimeGMT = $end_date->format('Y-m-d H:i:s');

            if ($startDateTimeGMT < $current_date) {
                // return generateResponse(['type' => 'info', 'code' => 200, 'status' => false, 'message' => 'Start time should be greater than or equal to current time']);
            }
            if ($endDateTimeGMT < $startDateTimeGMT) {
                return generateResponse(['type' => 'info', 'code' => 200, 'status' => false, 'message' => 'The end date should be equal or greater than start date']);
            }
            $existingEvent = CalendarEvent::where('user_id', $user->id)
                ->where('event_title', $request->event_title)
                ->where('start_date_time', $request->start_date_time)
                ->where('end_date_time', $request->end_date_time)
                ->where('event_description', $request->event_description)
                ->where('organizer_user_id', $user->id)
                ->where('status', $request->status)
                ->where('reminder', $request->reminder)
                ->first();

            if ($existingEvent) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => true, 'message' => 'Event already exists']);
            }

            $calendar = new CalendarEvent();
            $user = $request->attributes->get('user');
            $calendar->user_id = $user->id;
            $calendar->organizer_user_id = $user->id;
            $calendar->event_title = $request->event_title;
            $calendar->location = $request->location;
            $calendar->start_date_time = $startDateTimeGMT;
            $calendar->end_date_time = $endDateTimeGMT;
            $calendar->event_description = $request->event_description;
            $calendar->status = $request->status;
            $calendar->reminder = $request->reminder;
            if ($request->filled('visibility')) {
                $calendar->visibility = $request->visibility;
            }
            if ($request->filled('category')) {
                $calendar->category = $request->category;
            }
            if ($request->filled('subCategory')) {
                $calendar->subCategory = $request->subCategory;
            }
            if ($request->filled('meetingLink')) {
                $calendar->meetingLink = $request->meetingLink;
            }
            $calendar->event_attachment = null;

            if ($request->hasFile('event_attachment')) {
                $file = $request->file('event_attachment');
                $fileName = $request->event_title . '.' . $file->getClientOriginalExtension();
                $filePath = "users/private/{$user->id}/calendar/{$fileName}";
                Storage::put($filePath, file_get_contents($file));
                $calendar->event_attachment = $filePath;
            }

            $invited_emails = [];
            $invited_usernames = [];
            if ($request->filled('invite_by_email') || $request->filled('invite_by_username')) {


                $calendar->save();

                if ($request->filled('invite_by_email')) {
                    $emails = explode(',', $request->invite_by_email);
                    foreach ($emails as $email) {
                        $userRow = User::where('email', $email)->first();
                        if ($userRow) {

                            $invited_emails[] = $email;
                        } else {
                            try {
                                $emailData['subject'] = "Calendar event Invite";
                                $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                                $emailData['title'] = config('app.app_name') . " Calendar event invitation";
                                $emailData['view'] = 'mail-templates.calendar-invite';
                                $emailData['username'] = $user->username;
                                $emailData['StartTime'] = date('F d, Y, H:i', strtotime($calendar->start_date_time));
                                $emailData['endTime'] = date('F d, Y, H:i', strtotime($calendar->end_date_time));
                                $emailData['event_title'] = $calendar->event_title;
                                $emailData['projectName'] = config('app.app_name');
                                Mail::to($email)->send(new SendMail($emailData, $emailData['view']));
                                $invited_emails[] = $email;
                            } catch (\Exception $e) {
                                Log::error('Error sending invitation email to ' . $email . ': ' . $e->getMessage());
                                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error sending email', 'toast' => true]);
                            }
                        }
                    }
                }
                if ($request->filled('invite_by_username')) {
                    $usernames = explode(',', $request->invite_by_username);
                    $notificationUserIds = [];
                    foreach ($usernames as $username) {
                        $userRow = User::where('username', $username)->first();
                        if ($user) {
                            $invitedCalendar = $calendar->replicate();
                            $invitedCalendar->user_id = $userRow->id;
                            $invitedCalendar->organizer_user_id = $user->id;
                            $invitedCalendar->parent_id = $calendar->id;
                            if ($request->filled('invite_by_username')) {
                                $invitedCalendar->invited_by_username = implode(",", $usernames);
                            }
                            $invitedCalendar->save();
                            $title = $description = $request->event_title ? "Event Invitation: " . $request->event_title : "Event Invitation: No Title";
                            $notificationUserIds[] = $userRow->id;
                            $invited_usernames[] = $username;
                        } else {
                            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found for username: ' . $username, 'toast' => true]);
                        }
                    }
                    if ($notificationUserIds) {
                        addNotificationsBulk($notificationUserIds, $user->id, $title, $description, $invitedCalendar->id, "6", "#", null, $authToken);
                    }
                }
                if (!empty($invited_emails)) {
                    $calendar->invited_by_email = implode(",", $invited_emails);
                }

                if (!empty($invited_usernames)) {
                    $calendar->invited_by_username = implode(",", $invited_usernames);
                }
                $calendar->save();
                if ($userRow) {
                    $emailData['subject'] = "Calendar event Invite";
                    $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                    $emailData['title'] = config('app.app_name') . " Calendar event invitation";
                    $emailData['view'] = 'mail-templates.calendar-invite';
                    $emailData['username'] = $user->username;
                    $emailData['StartTime'] = date('F d, Y, H:i a', strtotime($calendar->start_date_time));
                    $emailData['endTime'] = date('F d, Y, H:i a', strtotime($calendar->end_date_time));
                    $emailData['event_title'] = $calendar->event_title;
                    $emailData['projectName'] = config('app.app_name');
                    try {
                        Mail::to($userRow->email)->send(new SendMail($emailData, $emailData['view']));
                    } catch (\Exception $e) {
                        Log::error('Error sending email: ' . $e->getMessage());
                        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error sending email', 'toast' => true]);
                    }
                }
            }
            $calendar->save();
            $title = $description = $request->event_title ? "New Event: " . $request->event_title : "New Event: No Title";
            addNotification($user->id, $user->id, $title, $description, $calendar->id, "6", "#", null, $authToken);
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Calendar event added', 'toast' => true], ['eventData' => array_merge($calendar->toArray(), ['parent_id' => null, "invited_by_username" => $invited_usernames, "invited_by_email" => $invited_emails, "start_date_time" => $request->start_date_time, "end_date_time" => $request->end_date_time])]);
        } catch (\Exception $e) {
            Log::info('calendar event add error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error processing the event', 'toast' => true]);
        }
    }
    public function update(CalendarEventUpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $id = $request->input('id');
            $authToken = $request->header('authToken');

            $calendar = CalendarEvent::where('user_id', $user->id)->where('id', $id)->first();
            $timezone = $request->header('Timezone');

            $newTZ = new DateTimeZone($timezone);
            $GMT = new DateTimeZone("GMT");
            $current_date_time = new DateTime();
            $current_date = $current_date_time->format('Y-m-d H:i:s');

            if (!$calendar) {
                DB::rollBack();
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Calendar not found', 'toast' => true]);
            }

            $old_event_start_date = $calendar->start_date_time;

            if ($request->filled('start_date_time')) {
                $start_date = new DateTime($request->start_date_time, $newTZ);
                $start_date->setTimezone($GMT);
                $startDateTimeGMT = $start_date->format('Y-m-d H:i:s');
                $calendar->start_date_time = $startDateTimeGMT;
            } else {
                $startDateTimeGMT = $calendar->start_date_time;
            }

            if ($request->filled('end_date_time')) {
                $end_date = new DateTime($request->end_date_time, $newTZ);
                $end_date->setTimezone($GMT);
                $endDateTimeGMT = $end_date->format('Y-m-d H:i:s');
                $calendar->end_date_time = $endDateTimeGMT;
            } else {
                $endDateTimeGMT = $calendar->end_date_time;
            }

            if ($endDateTimeGMT < $startDateTimeGMT) {
                return generateResponse(['type' => 'info', 'code' => 200, 'status' => false, 'message' => 'The end date should be equal or greater than start date']);
            }

            $calendar->event_title = $request->filled('event_title') ? $request->event_title : null;

            $calendar->location = $request->filled('location') ? $request->location : null;

            $calendar->event_description = $request->filled('event_description') ? $request->event_description : null;

            $calendar->status = $request->filled('status') ? $request->status : $calendar->status;

            $calendar->reminder = $request->filled('reminder') ? $request->reminder : null;

            $calendar->visibility = $request->filled('visibility') ? $request->visibility : $calendar->visibility;

            $calendar->category = $request->filled('category') ? $request->category : null;

            $calendar->subCategory = $request->has('subCategory') ? $request->subCategory : null;

            $calendar->meetingLink = $request->has('meetingLink') ? $request->meetingLink : null;

            if ($request->hasFile('event_attachment')) {
                if ($calendar->event_attachment) {
                    Storage::delete($calendar->event_attachment);
                }

                $file = $request->file('event_attachment');
                $fileName = $request->event_title . '.' . $file->getClientOriginalExtension();
                $filePath = "users/private/{$user->id}/calendar/{$fileName}";
                Storage::put($filePath, file_get_contents($file));

                $calendar->event_attachment = $filePath;
            } else {
                if ($calendar->event_attachment) {
                    Storage::delete($calendar->event_attachment);
                    $calendar->event_attachment = null;
                }
            }

            $calendar->save();
            $old_invited_user = [];
            $invitedUsernameArr = [];
            $invitedEmailsArr = [];

            if (!empty($calendar->invited_by_username)) {
                $invitedUsernameArr = explode(",", $calendar->invited_by_username);
            }

            if (!empty($calendar->invited_by_email)) {
                $invitedEmailsArr = explode(",", $calendar->invited_by_email);
            }

            $invited_emails = [];
            $invited_usernames = [];
            $notificationUserIds = [];

            if ($request->filled('invite_by_email') || $request->filled('invite_by_username')) {
                if ($request->filled('invite_by_email')) {
                    $emails = explode(',', $request->invite_by_email);
                    foreach ($emails as $email) {
                        $userRow = User::where('email', $email)->first();
                        if ($userRow) {
                            $invited_emails[] = $email;
                        } else {
                            if (!empty($invitedEmailsArr) && in_array($email, $invitedEmailsArr)) {
                                $invited_emails[] = $email;
                                $old_invited_user[] = $email;
                            } else {
                                try {
                                    $emailData['subject'] = "Calendar event Invite";
                                    $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                                    $emailData['title'] = config('app.app_name') . " Calendar event invitation";
                                    $emailData['view'] = 'mail-templates.calendar-invite';
                                    $emailData['username'] = $user->username;
                                    $emailData['StartTime'] = date('F d, Y, H:i a', strtotime($calendar->start_date_time));
                                    $emailData['endTime'] = date('F d, Y, H:i a', strtotime($calendar->end_date_time));
                                    $emailData['event_title'] = $calendar->event_title;
                                    $emailData['projectName'] = config('app.app_name');
                                    Mail::to($email)->send(new SendMail($emailData, $emailData['view']));
                                    $invited_emails[] = $email;
                                } catch (\Exception $e) {
                                    Log::error('Error sending invitation email to ' . $email . ': ' . $e->getMessage());
                                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error sending email', 'toast' => true]);
                                }
                            }
                        }
                    }
                } else {
                    $calendar->invited_by_email = '';
                }

                if ($request->filled('invite_by_username')) {
                    $usernames = explode(',', $request->invite_by_username);
                    foreach ($usernames as $username) {
                        $userRow = User::where('username', $username)->first();
                        if ($userRow) {
                            if (!empty($invitedUsernameArr) && in_array($username, $invitedUsernameArr)) {
                                $old_invited_user[] = $userRow->email;
                            } else {
                                $invitedCalendar = $calendar->replicate();
                                $invitedCalendar->user_id = $userRow->id;
                                $notificationUserIds[] = $userRow->id;
                                $invitedCalendar->parent_id = $calendar->id;
                                $invitedCalendar->save();
                            }
                            $invited_usernames[] = $username;
                        } else {
                            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found for username: ' . $username, 'toast' => true]);
                        }
                    }
                } else {
                    $calendar->invited_by_username = '';
                }

                if (!empty($invited_emails)) {
                    $calendar->invited_by_email = implode(",", $invited_emails);
                }

                if (!empty($invited_usernames)) {
                    $calendar->invited_by_username = implode(",", $invited_usernames);
                }



                if (!empty($old_invited_user)) {
                    if ($old_event_start_date !== $calendar->start_date_time) {
                        foreach ($old_invited_user as $in_email) {
                            $emailData['subject'] = "Calendar event Invite";
                            $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                            $emailData['title'] = config('app.app_name') . " Calendar update event invitation";
                            $emailData['view'] = 'mail-templates.calender-invite-update';
                            $emailData['username'] = $user->username;
                            $emailData['StartTime'] = date('F d, Y, H:i a', strtotime($calendar->start_date_time));
                            $emailData['endTime'] = date('F d, Y, H:i a', strtotime($calendar->end_date_time));
                            $emailData['event_title'] = $calendar->event_title;
                            $emailData['projectName'] = config('app.app_name');

                            try {
                                Mail::to($in_email)->send(new SendMail($emailData, $emailData['view']));
                            } catch (\Exception $e) {
                                Log::error('Error sending email: ' . $e->getMessage());
                                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error sending email', 'toast' => true]);
                            }
                        }
                    }
                }
            } else {
                $calendar->invited_by_email = null;
                $calendar->invited_by_username = null;
            }
            $calendar->save();
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Event Updated', 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Something went wrong. Try again later.', 'toast' => true]);
        }
    }


    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $id = $request->input('id');
            $calendar = CalendarEvent::where('user_id', $user->id)->where('id', $id)->first();

            if (!$calendar) {
                DB::rollBack();
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Event not found', 'toast' => true]);
            }
            $invitedEvents = CalendarEvent::where('parent_id', $calendar->id)->get();
            foreach ($invitedEvents as $invitedEvent) {
                $invitedEvent->delete();
            }
            $calendar->delete();
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Event Deleted', 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Calendar event delete error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error processing the event', 'toast' => true]);
        }
    }
    public function getEvents(Request $request)
    {
        try {
            $user = $request->attributes->get('user');

            $timezone = $request->header('Timezone');

            $temp_events = CalendarEvent::where('user_id', $user->id)->orderBy("created_at", "desc")->leftJoin("users", "organizer_user_id", "=", "users.id")->selectRaw("calendar_events.*,users.username as organizer_username")->get();

            if (!$temp_events)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No logs available', 'toast' => true, 'data' => []]);

            $temp_events = $temp_events->toArray();

            $events = [];

            foreach ($temp_events as $event) {
                $newTZ = new DateTimeZone($timezone);
                $GMT = new DateTimeZone("GMT");
                $date = new DateTime($event['start_date_time'], $GMT);
                $date->setTimezone($newTZ);

                $event['file_size'] = 0;
                if ($event['event_attachment']) {
                    $filePath = storage_path('app/' . $event['event_attachment']);


                    if (file_exists($filePath)) {
                        $event['file_size'] = filesize($filePath);
                    }
                }

                $end_date = new DateTime($event['end_date_time'], $GMT);
                $end_date->setTimezone($newTZ);

                $event["email"] = $event["invited_by_email"] ? explode(",", $event["invited_by_email"]) : [];
                $event["users"] = $event["invited_by_username"] ? explode(",", $event["invited_by_username"]) : [];
                $event['start_date_time'] = $date->format('Y-m-d H:i:s');
                $event['end_date_time'] = $end_date->format('Y-m-d H:i:s');

                $events[] = $event;
            }

            if (!$events)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No events available', 'toast' => true, 'data' => []]);

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Events retrieved successfully', 'toast' => true, 'data' => ["events" => $events]]);
        } catch (\Exception $e) {
            Log::info('Calendar Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function geteventattachment(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $calendar_id = $request->calendar_id;

            $file = CalendarEvent::where('user_id', $user->id)->where('id', $calendar_id)->first();

            if ($file) {
                if ($file->event_attachment) {
                    $filePath = storage_path('app/' . $file->event_attachment);


                    if (!file_exists($filePath)) {
                        return response()->json(['status' => false]);
                    } else {
                        return response()->file($filePath);
                    }
                } else {
                    return response()->json(['status' => false]);
                }
            }
            return response()->json(['status' => false]);
        } catch (\Exception $e) {
            Log::info('File add error : ' . $e->getMessage());
            return response()->json(['status' => false]);
        }
    }
    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'user_id' => '',
            'event_title' => 'required|string',
            'start_date_time' => 'required|date',
            'end_date_time' => 'required|date|after_or_equal:start_date_time',
            'event_description' => 'required|string',
            //'status' => 'required',
            //'reminder' => ' ',
            'visibility' => 'required',
            'category' => 'required|string',
            // Assuming event_attachment is an optional file upload
            'event_attachment' => 'sometimes|file',
        ]);

        if ($validator->fails()) {
            //return response("validation failed");
            return response()->json($validator->errors(), 400);
        }

        // Create a new calendar event
        $calendarEvent = CalendarEvent::create($request->all());

        // If there's an attachment, handle the file upload
        if ($request->hasFile('event_attachment')) {
            $file = $request->file('event_attachment');
            // Save the file and store the path in the database or handle as needed
        }

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Calendar event added', 'toast' => true]);


        //return response()->json($calendarEvent, 201);
    }
    public function getCalenderEvent(Request $request)
    {
        try {
            // $user = $request->attributes->get('user');
            $event = $request->attributes->get('event_title');
            $id = $request->input('id');

            if ($id) {
                //$c_id = calendar_events::where('user_id', $user->id)->where('id', $id)->first();
                $c_id = CalendarEvent::where('id', $id)->get();

                // return response()->json($c_id);
                //return($c_id);

                /*if (!$c_id) {
                                      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Please specify the ID.', 'toast' => true]);
                                  } else {
                                      $three_d->thumbnail_path = getFileTemporaryURL($three_d->thumbnail_path);
                                      $three_d->file_path = getFileTemporaryURL($three_d->file_path);
                                  }*/
            } else {
                $c_id = CalendarEvent::all();
                //$c_id =CalendarEvent::where('event_title', $event)->orderByDesc('id')->get();
                //return($c_id);
            }
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'calendar event retrieved successfully.', 'toast' => true], ['CalendarEvent' => $c_id]);
        } catch (\Exception $e) {
            Log::info('Error while retrieving 3D: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error retrieving channel.', 'toast' => true]);
        }
    }
}
