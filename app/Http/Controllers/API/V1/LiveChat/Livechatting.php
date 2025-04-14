<?php

namespace App\Http\Controllers\API\V1\LiveChat;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Livechat\LiveChat;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class Livechatting extends Controller
{
    public function index(Request $request)
    {
        try {
            $socketUrl = config("app.socket_url") . '/chat';
            $user = $request->attributes->get('user');
            $user_id = $user->id;
            $broadcast_id = $request->get('broadcast_id');
            $message = $request->get('message');
            $is_influencer = $request->get('is_influencer');
            $unique_id = Str::uuid();
            $newMessageData = [
                'user_id' => $user_id,
                'message' => $message,
                'id' => $unique_id,
            ];

            $liveChat = LiveChat::where('broadcast_id', $broadcast_id)->first();

            if ($liveChat) {
                $existingMessageData = json_decode($liveChat->message_data, true);
                $existingMessageData[] = $newMessageData;

                $liveChat->message_data = json_encode($existingMessageData);
                $liveChat->save();
            } else {
                LiveChat::create([
                    'broadcast_id' => $broadcast_id,
                    'message_data' => json_encode([$newMessageData]),
                ]);
            }

            $userData = User::with('profile')->where('id', $user_id)->first();
            $avatar_url = $userData->profile->profile_image_path ? getFileTemporaryURL($userData->profile->profile_image_path) : null;

            $authToken = $request->header('authToken');
            $headers = [
                'authToken' => $authToken,
            ];
            $data = [
                'id' => $unique_id,
                'broadcast_id' => $broadcast_id,
                'username' => $user->username,
                'avatar_url' => $avatar_url,
                'is_influencer' => $is_influencer,
                'message' => $message,
            ];
            $response = Http::withHeaders($headers)->post($socketUrl, $data);

            if ($response->successful()) {
                return generateResponse([
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'message sent successfully',
                    'toast' => true,
                ], ['message_data' => $response['receivedData']]);
            } else {
                Log::error('Failed to send socket notification: ' . $response->body());
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => $response->body(),
                    'toast' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::info('Error sending message: ' . $e->getMessage());
            return generateResponse([
                'type' => 'error',
                'code' => 200,
                'status' => false,
                'message' => 'Error sending message',
                'toast' => true,
            ]);
        }
    }
    public function getChat(Request $request)
    {
        try {
            $broadcast_id = $request->get('broadcast_id');
            $liveChat = LiveChat::where('broadcast_id', $broadcast_id)->first();

            if ($liveChat) {
                $message_data = json_decode($liveChat->message_data, true);

                $processedMessages = [];
                foreach ($message_data as $message) {
                    $user = User::with('profile')->where('id', $message['user_id'])->first();
                    if ($user) {
                        $username = $user->username;
                        $avatar_url = $user->profile->profile_image_path ? getFileTemporaryURL($user->profile->profile_image_path) : null;
                        $processedMessages[] = [
                            // 'user_id' => $message['user_id'],
                            'username' => $username,
                            'avatar_url' => $avatar_url,
                            'is_influecer' => $user->is_influencer,
                            'message' => $message['message'],
                            'id' => $message['id'],
                        ];
                    }
                }

                return generateResponse([
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Messages fetched successfully',
                    'toast' => true,
                ], ['message_data' => $processedMessages,]);
            } else {
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'No messages found',
                    'toast' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::info('Error fetching messages: ' . $e->getMessage());
            return generateResponse([
                'type' => 'error',
                'code' => 200,
                'status' => false,
                'message' => 'Error fetching messages',
                'toast' => true,
            ]);
        }
    }
}
