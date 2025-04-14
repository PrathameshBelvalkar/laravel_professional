<?php

use App\Models\CalendarEvent;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

if (!function_exists('makeCurlCall')) {
    function makeCURLCall($url, $post_data, $authToken = null, $decode = true, $bearerToken = null)
    {
        try {
            $post_string = '';
            foreach ($post_data as $key => $value) {
                $post_items[] = $key . '=' . $value;
            }
            $post_string = implode('&', $post_items);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            if ($bearerToken) {
                $headers = array(
                    'Authorization: Bearer ' . $bearerToken, // Use Bearer keyword for token type
                );
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            if ($authToken) {
                $headers = array(
                    'authToken: ' . $authToken,
                );
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if ($decode) {
                $response = json_decode(curl_exec($ch), true);
                curl_close($ch);
                return $response;
            } else {
                curl_exec($ch);
                return array("status" => false, "message" => "Error while processing");
            }
        } catch (\Exception $e) {
            Log::info('makeCURLCall Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return array("status" => false, "message" => "Error while processing");
        }
    }
}
// if (!function_exists('addNotification')) {
//     function addNotification($to_user_id, $from_user_id, $title, $description, $reference_id, $module, $link)
//     {
//         $notification = new Notification();
//         $notification->to_user_id = $to_user_id;
//         $notification->from_user_id = $from_user_id;
//         $notification->title = $title;
//         $notification->description = $description;
//         $notification->reference_id = $reference_id;
//         $notification->module = $module;
//         $notification->link = $link;
//         $notification->save();
//         return $notification->id;
//     }
// }

if (!function_exists('addNotification')) {
    function addNotification($to_user_id, $from_user_id, $title, $description, $reference_id, $module, $link, $is_admin = null, $auth_token = null)
    {
        $is_admin = $is_admin !== null ? (string) $is_admin : '0';
        $notification = new Notification();
        $notification->to_user_id = $to_user_id;
        $notification->from_user_id = $from_user_id;
        $notification->title = $title;
        $notification->description = $description;
        $notification->reference_id = $reference_id;
        $notification->module = $module;
        $notification->link = $link;
        $notification->is_admin = $is_admin;

        $notification->save();
        if ($auth_token) {
            $socket_user = User::where('id', $to_user_id)->first();
            sendSocketNotification($to_user_id, $title, $description, $module, $socket_user->username, $auth_token);
        }
        return $notification->id;
    }
}
if (!function_exists('addNotificationsBulk')) {
    function addNotificationsBulk($to_user_ids, $from_user_id, $title, $description, $reference_id, $module, $link, $is_admin = null, $auth_token = null)
    {
        $notificationsData = [];
        foreach ($to_user_ids as $to_user_id) {
            $tempReferenceNo = getNotificationReferenceNumberForCalendar($to_user_id, $module, $reference_id);

            $notificationsData[] = [
                'to_user_id' => $to_user_id,
                'from_user_id' => $from_user_id,
                'title' => $title,
                'description' => $description,
                'reference_id' => $module == "6" ? $tempReferenceNo : $reference_id,
                'module' => $module,
                'link' => $link,
                'is_admin' => $is_admin ?? '0',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ];
        }

        Notification::insert($notificationsData);

        foreach ($to_user_ids as $to_user_id) {
            $notification = Notification::where('to_user_id', $to_user_id)
                ->where('reference_id', $reference_id)
                ->where('module', $module)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($notification && $auth_token) {
                $socket_user = User::where('id', $notification->to_user_id)->first();
                sendSocketNotification($to_user_id, $title, $description, $module, $socket_user->username, $auth_token);
            }
        }

        return true;
    }
}

if (!function_exists('sendSocketNotification')) {
    function sendSocketNotification($userId, $title, $body, $module, $username, $auth_token = null, $type = null, $data = [])
    {
        $type = $type ?: "notification_" . $module . "_" . $username;
        $socketUrl = config("app.socket_url") . '/notification';
        $headers = [
            'authToken' => $auth_token,
        ];
        // $decoded = JWT::decode($auth_token, new Key(config('app.enc_key'), 'HS256'));
        // $username = $decoded->username;

        $data = [
            'type' => $type,
            'module' => $module,
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'data' => json_encode($data),
        ];

        try {
            $response = Http::withHeaders($headers)->post($socketUrl, $data);

            if ($response->successful()) {
                return true;
            } else {
                Log::error('Failed to send socket notification: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Socket notification error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('send_sms')) {
    function send_sms($phone_number, $message = "")
    {
        $fromNumber = config('app.twilio_number');
        $account_sid = config('app.twilio_account_sid');
        $auth_token = config('app.twilio_auth_token');

        $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
        $fields = array(
            'From' => $fromNumber,
            'Body' => $message,
            'To' => $phone_number,
        );
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $temp_sms_response = curl_exec($ch);
        $sms_response = json_decode($temp_sms_response);
        return $sms_response;
    }
}
if (!function_exists('deleteNotifications')) {
    function deleteNotifications($user_ids, $module, $reference_id)
    {
        $deletedNotifications = Notification::whereIn("to_user_id", $user_ids)->where("module", $module)->where("reference_id", $reference_id)->delete();
    }
}
if (!function_exists('getNotificationReferenceNumberForCalendar')) {
    function getNotificationReferenceNumberForCalendar($to_user_id, $module, $reference_id)
    {
        if ($module == "6") {
            $event = CalendarEvent::where("id", $reference_id)->first();
            if ($event) {
                if ($event->user_id != $to_user_id) {
                    $guestEvent = CalendarEvent::where("user_id", $to_user_id)->where("parent_id", $event->id)->first();
                    if ($guestEvent) {
                        return $guestEvent->id;
                    }
                }
            }
        }
        return $reference_id;
    }
}