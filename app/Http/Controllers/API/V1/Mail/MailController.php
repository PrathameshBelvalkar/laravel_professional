<?php

namespace App\Http\Controllers\API\V1\Mail;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckStorageLimit;
use App\Http\Requests\Mail\AddMailLabelsRequest;
use App\Http\Requests\Mail\SentMailRequest;
use App\Mail\SendEmail;
use Illuminate\Http\Request;
use App\Mail\SendMail;
use App\Models\Mail\EmailLabel;
use App\Models\Mail\MailQuickResponse;
use App\Models\Mail\MailReply;
use App\Models\Mail\MailSent;
use App\Models\Mail\MailSnippets;
use App\Models\Mail\SpamEmailLog;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MailController extends Controller
{
    public function sendEmail(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $is_draft = $request->is_draft;
            $is_draft2 = $is_draft;
            $draft_id = $request->input('id');
            $mail_id = $request->input('mail_id');
            $reply_id = $request->input('reply_id');
            $userData = User::where('id', $user->id)->first();
            $authToken = $request->header('authToken');

            if ($is_draft == '0' && empty($request->recipients) && empty($request->cc) && empty($request->bcc)) {
                return response()->json(['type' => 'error','code' => 400,'status' => false,'message' => 'Recipients are required','toast' => true]);
            }

            $recipients = array_filter(array_map('trim', explode(',', $request->recipients)));
            $cc = array_filter(array_map('trim', explode(',', $request->cc)));
            $bcc = array_filter(array_map('trim', explode(',', $request->bcc)));

            $processedRecipients = processEmails($recipients);
            $processedCc = processEmails($cc);
            $processedBcc = processEmails($bcc);

            $silocloudUserEmails = array_merge(
                $processedRecipients['silocloudUserEmails'],
                $processedCc['silocloudUserEmails'],
                $processedBcc['silocloudUserEmails']
            );

            $otherEmails = array_merge(
                $processedRecipients['otherEmails'],
                $processedCc['otherEmails'],
                $processedBcc['otherEmails']
            );
            $allEmails = array_merge($silocloudUserEmails, $otherEmails);
            $filteredRecipients = array_filter($processedRecipients['silocloudUserEmails'], fn($email) => !str_contains($email, 'noitavonne.com'));
            $filteredCc = array_filter($processedCc['silocloudUserEmails'], fn($email) => !str_contains($email, 'noitavonne.com'));
            $filteredBcc = array_filter($processedBcc['silocloudUserEmails'], fn($email) => !str_contains($email, 'noitavonne.com'));

            $filteredOtherRecipients = array_filter($processedRecipients['otherEmails'], fn($email) => !str_contains($email, 'noitavonne.com'));
            $filteredOtherCc = array_filter($processedCc['otherEmails'], fn($email) => !str_contains($email, 'noitavonne.com'));
            $filteredOtherBcc = array_filter($processedBcc['otherEmails'], fn($email) => !str_contains($email, 'noitavonne.com'));

            $recipients_merge_emails = array_unique(array_merge($filteredRecipients, $filteredOtherRecipients));
            $cc_merge_emails = array_unique(array_merge($filteredCc, $filteredOtherCc));
            $bcc_merge_emails = array_unique(array_merge($filteredBcc, $filteredOtherBcc));

            $userIds = User::whereIn('email', $silocloudUserEmails)->pluck('id')->toArray();
            $isRecipients = implode(',', $userIds);
            $isRecipients_user_id = array_map(function($id) {
                return (int) $id; 
            }, $userIds);
            $userEmail = $user->email;
            $emailContainsUserEmail = in_array($userEmail, $allEmails);

            $mailData = [
                'title' => $request->subject,
                'message' => $request->message,
                'sender' => $userData->username.'@silocloud.io',
                'logo' => asset('assets/images/socialMedia/silocloud-logo.png'),
                'facebook' => asset('assets/images/socialMedia/facebook.png'),
                'twitter' => asset('assets/images/socialMedia/twitter.png'),
                'pinterest' => asset('assets/images/socialMedia/pinterest.png'),
                'instagram' => asset('assets/images/socialMedia/instagram.png'),
                'facebook_link' => '#',
                'twitter_link' => '#',
                'pinterest_link' => '#',
                'instagram_link' => '#',
                'link' => "https://mail.silocloud.io/",
                'files' => [],
            ];

            $attachments = [];
            if ($request->has('attachment')) {
                $files = $request->attachment;

                if (is_string($files)) {
                    $files = json_decode($files, true);
                }

                foreach ($files as $attachment) {
                    if (is_array($attachment)) {
                        $attachmentName = $attachment['name'];
                        $filePath = $attachment['shortpath'];
                        $fileSize = $attachment['size'];

                        $mailData['files'][] = storage_path('app/' . $filePath);

                        $attachments[] = [
                            'fileName' => $attachmentName,
                            'size' => $fileSize,
                            'path' => $filePath,
                        ];
                    }
                }
            }

            $attachmentsJson = json_encode($attachments);
            $spamLog = SpamEmailLog::where('user_id', $user->id)->first();
            $spamScore = $spamLog ? $spamLog->spam_score : null;
            $mail_sent = saveOrUpdateMail($request, $user, $is_draft, $attachmentsJson, $isRecipients, $mail_id, $draft_id, $reply_id, $emailContainsUserEmail,$spamScore);

            createMailReply($user, $request, $attachmentsJson, $isRecipients, $mail_id, $mail_sent ? $mail_sent->id : null, $reply_id, $draft_id);

            calculateSpamScore($request->message,$user->id,$mail_sent->id,$isRecipients);

            try{
                if ($is_draft == '0') {
                    Mail::mailer("info")
                        ->to($recipients_merge_emails)
                        ->cc($cc_merge_emails)
                        ->bcc($bcc_merge_emails)
                        ->send(new SendEmail(array_merge($mailData, ['files' => $mailData['files']])));
                     
                    DB::commit();
                    addNotificationsBulk($isRecipients_user_id, $user->id, "New Email from $userData->username !", "Check your inbox for the latest message", $mail_sent ? $mail_sent->id : null, "9", "https://mail.silocloud.io/", null, $authToken);
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Email processed successfully', 'toast' => true]);
                } else {
                    DB::commit();
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Email saved as draft', 'toast' => true]);
                }
            }catch (\Exception $e) {
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Email processed successfully', 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sent Mail data retrieval error: ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error processing the request', 'toast' => true]);
        }
    }
    public function uploadAttachment(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            if (!$user || !isset($user->id) || !isset($user->email)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User Not found', 'toast' => true]);
            }

            $attachments = [];

            if ($request->hasFile('attachment')) {
                $files = $request->file('attachment');
                foreach ($files as $attachment) {
                    if ($attachment->isValid()) {
                        $originalName = $attachment->getClientOriginalName();
                        $fileName = pathinfo($originalName, PATHINFO_FILENAME);
                        $extension = $attachment->getClientOriginalExtension();
                        $filePath = "users/private/{$user->id}/attachments/";
                        $fullFilePath = $filePath . $originalName;

                        $counter = 1;
                        // while (Storage::disk('public')->exists($fullFilePath)) {
                        while (Storage::exists($fullFilePath) || Storage::disk('public')->exists($fullFilePath)){
                            $newFileName = $fileName . "({$counter})." . $extension;
                            $fullFilePath = $filePath . $newFileName;
                            $counter++;
                        }

                        Storage::disk('public')->put($fullFilePath, file_get_contents($attachment));
                        Storage::put($fullFilePath, file_get_contents($attachment));
                        $mailData['files'][] = storage_path('app/public/' . $fullFilePath);

                        $fileSize = $attachment->getSize();

                        $attachments[] = [
                            'fileName' => basename($originalName),
                            'size' => $fileSize,
                            'shortpath' => 'public/' . $fullFilePath,
                            'path' =>getFileTemporaryURL('public/' . $fullFilePath)
                        ];
                    } else {
                        Log::error('Invalid attachment file: ' . $attachment->getErrorMessage());
                    }
                }
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'File uploaded successfully', 'data'=>$attachments, 'toast' => true]);

        } catch (\Exception $e) {
            Log::info('Upload Attachment API error: ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }


    public function deleteAttachment(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            if (!$user || !isset($user->id) || !isset($user->email)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User Not found', 'toast' => true]);
            }

            $attachmentPath = $request->input('attachment_path');
            if (!$attachmentPath) {
                return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'Attachment name is required','toast' => true ]);
            }
    
            $filePath = str_replace('public/', '', $attachmentPath);
            //new
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
    
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
    
                return generateResponse(['type' => 'success','code' => 200,'status' => true, 'message' => 'File deleted successfully','toast' => true ]);
            } else {
                return generateResponse(['type' => 'error','code' => 404,'status' => false,'message' => 'File not found','toast' => true ]);
            }

        } catch (\Exception $e) {
            Log::info('delete Attachment file in storage API error: ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function getInboxList(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            if (!$user || !isset($user->id) || !isset($user->email)) {
                return response()->json(['type' => 'error','code' => 400, 'status' => false,'message' => 'User Not found','toast' => true ]);
            }
            
            $userId = $user->id;
            $search = $request->input('search', '');
            $limit = $request->input('limit', 10);
            $currentPage = $request->input('page', 1);
            $offset = ($currentPage - 1) * $limit;

            $inboxData = getInboxList($userId, $search, $limit, $offset);
            $inbox_list = $inboxData['data'];
            $total_records = $inboxData['total_records'];

            if ($inbox_list->isEmpty()) {
                return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'No Inbox list found','data' => [],'inbox_list_count' => 0,'toast' => true ]);
            }
    
            $responseData = [];
    
            foreach ($inbox_list as $index => $message) {
                $userData = getLogUserData($userId);
                $userDataUsername = $userData->username ?? '';
                $userDataFirstName = $userData->first_name ?? '';
                $userDataLastName = $userData->last_name ?? '';
                $isFavourite = isFavourite($userId, $message->id);
                $isArchive = isArchive($userId, $message->id);
                $isRead = isRead($userId, $message->id);
                $replyData = getReplyData($message->id,$userId);
                $tags = getTagsFromLabels($message->is_label, $user->id);

                $responseData[] = [
                    'id' => $message->id,
                    'userId' => $user->id ?? null,
                    'name' => $userDataUsername,
                    'firstname' => $userDataFirstName,
                    'lastname' => $userDataLastName,
                    'profile_image_path' => $userData->profile_image_path ? getFileTemporaryURL($userData->profile_image_path) : null,
                    'theme' => getThemeById($message->user_id),
                    'message' => [
                        'subject' => $message->subject,
                        'meta' => [
                            'tags' => $tags,
                            'inbox' =>  true,
                            'checked' => false,
                            'favourite' => $isFavourite,
                            'archived' => $isArchive,
                            'trash' => false,
                            'sent' => false,
                            'read' => $isRead
                        ],
                        'reply' => $replyData,
                    ],
                ];
            } 
            $lastPage = ceil($total_records / $limit);
            return response()->json(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Inbox list retrieved successfully', 'data' => $responseData, 'pagination' => ['total_records' => $total_records, 'per_page' => $limit, 'current_page' => $currentPage, 'last_page' => $lastPage], 'toast' => true]);
    
        } catch (\Exception $e) {
            Log::info('Get Inbox list API error: ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Error while processing','toast' => true ]);
        }
    }
    
    public function favouriteInboxMail(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            if (!$user || !isset($user->id)) 
            {
            return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User Not found','toast' => true ]);
            }
            $favourite_id =  $request->input('favourite_id');

            $emails = $user->email;
            
            $userId = User::where('email', $emails)->pluck('id')->first();

            $existingFavourites = MailSent::where('id', $favourite_id)->value('is_favourites');
            $favouritesArray = $existingFavourites ? explode(',', $existingFavourites) : [];

            if (in_array($userId, $favouritesArray)) {
                $favouritesArray = array_diff($favouritesArray, [$userId]);
            } else {
                $favouritesArray[] = $userId;
            }

            $newFavouriteString = implode(',', $favouritesArray);

            MailSent::where('id', $favourite_id)->update(['is_favourites' => $newFavouriteString]);

            return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Favourite status updated successfully','toast' => true ]);

        } catch (\Exception $e) {
            Log::info('Favourite mails in inbox list API error : ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Error while processing','toast' => true ]);
        }
    }

    public function getFavouriteList(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User Not found', 'toast' => true]);
            }
    
            $userId = $user->id;
            $search = $request->input('search');
            $limit = $request->input('limit', 10);
            $current_page = $request->input('page', 1);
            $offset = ($current_page - 1) * $limit;
    
            $userQuery = MailSent::where(function($query) use ($userId) {
                $query->whereRaw("FIND_IN_SET(?, is_favourites)", [$userId])
                    ->orWhereRaw("FIND_IN_SET(?, is_recipients)", [$userId])
                    ->orWhereRaw("FIND_IN_SET(?, is_draft)", [$userId])
                    ->orWhereRaw("FIND_IN_SET(?, user_id)", [$userId]);
            })
            ->where(function($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_delete)", [$userId])
                    ->orWhereNull('is_delete');
            })
            ->where(function($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_archive)", [$userId])
                    ->orWhereNull('is_archive');
            })
            ->where(function($query) use ($userId) {
                $query->whereNull('is_spam')
                    ->orWhere('is_spam', '')
                    ->orWhereRaw("NOT FIND_IN_SET(?, is_spam)", [$userId]);
            })
            ->orderBy('created_at', 'desc');
            if ($search) {
                $allMessages = searchMessages($search,$userQuery);
            } else {
                $allMessages = $userQuery->get();
            }
            $filtered_favourite_count = 0;
            $filteredMessages = [];
    
            foreach ($allMessages as $message) {
                $userData = getLogUserData($userId);
                $userDataUsername = $userData->username ?? '';
                $userDataFirstName = $userData->first_name ?? '';
                $userDataLastName = $userData->last_name ?? '';
                $tags = getTagsFromLabels($message->is_label, $user->id);
    
                $isFavourite = isFavourite($userId, $message->id);
                $isArchive = isArchive($userId, $message->id);
                $isRead = isRead($userId, $message->id);
                $isRecipient = in_array($userId, explode(',', $message->is_recipients));
                $isDraft = isDraft($userId, $message->id);
                $isSent = isSent($userId, $message->id);
    
                if (!$isFavourite && ($isRecipient || $isArchive || $isSent || $isRead)) {
                    continue;
                }
    
                $replyData = getReplyData($message->id, $userId);
    
                $filteredMessages[] = [
                    'id' => $message->id,
                    'userId' => $user->id,
                    'name' => $userDataUsername,
                    'firstname' => $userDataFirstName,
                    'lastname' => $userDataLastName,
                    'profile_image_path' => $userData->profile_image_path ? getFileTemporaryURL($userData->profile_image_path) : null,
                    'theme' => getThemeById($message->user_id),
                    'message' => [
                        'subject' => $message->subject,
                        'meta' => [
                            'tags' => $tags,
                            'inbox' => $isRecipient,
                            'checked' => false,
                            'favourite' => $isFavourite,
                            'archived' => $isArchive,
                            'trash' => false,
                            'sent' => $isSent,
                            'draft' => $isDraft,
                            'read' => $isRead,
                        ],
                        'reply' => $replyData,
                    ],
                ];
                $filtered_favourite_count++;
            }
    
            $paginatedMessages = array_slice($filteredMessages, $offset, $limit);
            $last_page = ceil($filtered_favourite_count / $limit);
    
            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'Favourite list retrieved successfully','data' => $paginatedMessages,
                'pagination' => [
                    'total_records' => $filtered_favourite_count,
                    'per_page' => $limit,
                    'current_page' => $current_page,
                    'last_page' => $last_page,
                ],
                'toast' => true
            ]);
    
        } catch (\Exception $e) {
            Log::info('Get Favourite list API error : ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function getTrashList(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User Not found', 'toast' => true ]);
            }

            $userId = $user->id;
            $search = $request->input('search', '');
            $limit = $request->input('limit', 10);
            $currentPage = $request->input('page', 1);
            $offset = ($currentPage - 1) * $limit;

            $userQuery = MailSent::where(function($query) use ($userId) {
                $query->whereRaw("FIND_IN_SET(?, is_delete)", [$userId]);
            })
            ->where(function($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_trash)", [$userId])
                    ->orWhereNull('is_trash');
            })
            ->where(function($query) use ($userId) {
                $query->whereNull('is_spam')
                    ->orWhere('is_spam', '')
                    ->orWhereRaw("NOT FIND_IN_SET(?, is_spam)", [$userId]);
            });
            if ($search) {
                $allMessages = searchMessages($search, $userQuery);
            } else {
                $allMessages = $userQuery->get();
            }

            $total_records = $allMessages->count();
            $filtered_mail_list = $userQuery->orderBy('created_at', 'desc') 
                                ->offset($offset)
                                ->limit($limit)
                                ->get();
            $responseData = [];
            foreach ($filtered_mail_list as $mail) {
                $userData = getLogUserData($userId);
                $userDataUsername = $userData->username ?? '';
                $userDataFirstName = $userData->first_name ?? '';
                $userDataLastName = $userData->last_name ?? '';
                $replyData = getReplyData($mail->id, $userId);
                $isFavourite = isFavourite($userId, $mail->id);
                $isRead = isRead($userId, $mail->id);
                $isInbox = in_array($userId, explode(',', $mail->is_recipients ?? ''));
                $isSent = isSent($userId, $mail->id);
                $isTrash = isDelete($userId, $mail->id);
                $isDraft = isDraft($userId, $mail->id);
                $tags = getTagsFromLabels($mail->is_label, $user->id);

                if($isTrash)
                {
                    $mailData = checkDeletedMail($mail, $userId);
                }else{
                    $mailData = $mail;
                }
                
                if (!$mailData) {
                    continue; 
                }

                $responseData[] = [
                    'id' => $mail->id,
                    'userId' => $userId,
                    'name' => $userDataUsername,
                    'firstname' => $userDataFirstName,
                    'lastname' => $userDataLastName,
                    'profile_image_path' => $userData->profile_image_path ? getFileTemporaryURL($userData->profile_image_path) : null,
                    'theme' => getThemeById($mail->user_id),
                    'message' => [
                        'subject' => $mail->subject,
                        'meta' => [
                            'tags' => $tags,
                            'inbox' => $isInbox,
                            'checked' => false,
                            'favourite' => $isFavourite,
                            'archived' => false,
                            'trash' => $isTrash,
                            'sent' => $isSent,
                            'draft' => $isDraft,
                            'read' => $isRead,
                        ],
                        'reply' => $replyData,
                    ],
                ];
            }

            $last_page = ceil($total_records / $limit);

            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'Trash Emails list retrieved successfully','data' => $responseData,
                'pagination' => [
                    'total_records' => $total_records,
                    'per_page' => $limit,
                    'current_page' => $currentPage,
                    'last_page' => $last_page,
                ],
                'toast' => true
            ]);

        } catch (\Exception $e) {
            Log::info('Get mails API error : ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function getAllMailList(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $userId =$user->id;
            
            if (!$user || !isset($user->id)) 
            {
            return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User Not found','toast' => true ]);
            }

            $search = $request->input('search', '');
            $limit = $request->input('limit', 10);
            $currentPage = $request->input('page', 1);
            $offset = ($currentPage - 1) * $limit;

            $userQuery = MailSent::where(function($query) use ($userId) {
                    $query->whereRaw("FIND_IN_SET(?, is_recipients)", [$userId])
                      ->orWhereRaw("FIND_IN_SET(?, is_draft)", [$userId])
                      ->orWhereRaw("FIND_IN_SET(?, is_archive)", [$userId])
                      ->orWhereRaw("FIND_IN_SET(?, user_id)", [$userId]);
                    })
                    ->where(function($query) use ($userId) {
                        $query->whereNull('is_delete')
                            ->orWhere('is_delete', '')
                            ->orWhereRaw("NOT FIND_IN_SET(?, is_delete)", [$userId]);
                    })
                    ->where(function($query) use ($userId) {
                        $query->whereNull('is_trash')
                            ->orWhere('is_trash', '')
                            ->orWhereRaw("NOT FIND_IN_SET(?, is_trash)", [$userId]);
                    })
                    ->where(function($query) use ($userId) {
                        $query->whereNull('is_spam')
                            ->orWhere('is_spam', '')
                            ->orWhereRaw("NOT FIND_IN_SET(?, is_spam)", [$userId]);
                    });
                    if ($search) {
                        $allMessages = searchMessages($search, $userQuery);
                    } else {
                        $allMessages = $userQuery->get();
                    }
        
                    $total_records = $allMessages->count();
                    $emails = $userQuery->orderBy('created_at', 'desc') 
                                        ->offset($offset)
                                        ->limit($limit)
                                        ->get();

            $responseData = [];
        
                foreach ($emails as $index => $message) {
                    $userData = getLogUserData($userId);
                    $userDataUsername = $userData->username ?? '';
                    $userDataFirstName = $userData->first_name ?? '';
                    $userDataLastName = $userData->last_name ?? '';
                    $replyData = getReplyData($message->id,$userId);
                    $isFavourite = isFavourite($userId, $message->id);
                    $isArchive = isArchive($userId, $message->id);
                    $isRead = isRead($userId, $message->id);
                    $tags = getTagsFromLabels($message->is_label, $user->id);

                    $isRecipients = MailSent::where(function ($query) use ($userId, $message) {
                        $query->whereRaw("FIND_IN_SET(?, is_recipients)", [$userId])
                            ->where('id', $message->id);
                    })->exists();

                    $isDraft = MailSent::where(function ($query) use ($userId, $message) {
                        $query->whereRaw("FIND_IN_SET(?, is_draft)", [$userId])
                            ->where('id', $message->id);
                    })->exists();

                    $isSent = isSent($userId, $message->id);
        
                    $responseData[] = [
                        'id' => $message->id,
                        'userId' => $userId,
                        'name' => $userDataUsername,
                        'firstname' => $userDataFirstName,
                        'lastname' => $userDataLastName,
                        'profile_image_path' => $userData->profile_image_path ? getFileTemporaryURL($userData->profile_image_path) : null,
                        'theme' => getThemeById($message->user_id),
                        'message' => [
                            'subject' => $message->subject,
                            'meta' => [
                                'tags' => $tags,
                                'inbox' =>  $isRecipients,
                                'checked' => false,
                                'favourite' => $isFavourite,
                                'archived' => $isArchive,
                                'trash' => false,
                                'sent' => $isSent,
                                'draft' => $isDraft,
                                'all_mail'=> true,
                                'read' =>$isRead
                            ],
                            'reply' => $replyData,
                        ],
                    ];
                }
                
                $last_page = ceil($total_records / $limit);
            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'All Emails list retrieved successfully','data' => $responseData,'pagination' => ['total_records' => $total_records,'per_page' => $limit,'current_page' => $currentPage,'last_page' => $last_page,],'toast' => true ]);

        } catch (\Exception $e) {
            Log::info('Get All Emails list API error : ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 200,'status' => false,'message' => 'Error while processing','toast' => true ]);
        }
    }
    
    public function getSentMailList(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $userId =$user->id;

            if (!$user || !isset($user->id)) 
            {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User Not found', 'toast' => true ]);
            }

            $search = $request->input('search');
            $limit = $request->input('limit', 10);  
            $current_page = $request->input('page', 1); 
            $offset = ($current_page - 1) * $limit; 

            $userQuery = MailSent::where('email_type', '0')->whereRaw("FIND_IN_SET(?, user_id)", [$userId])
                ->where(function ($query) use ($userId) {
                $query->whereNull('is_delete')
                        ->orWhere('is_delete', 'NOT LIKE', '%'.$userId.'%');
                })
                ->where(function ($query) use ($userId) {
                    $query->whereNull('is_archive')
                        ->orWhere('is_archive', 'NOT LIKE', '%'.$userId.'%');
                })
                ->where(function($query) use ($userId) {
                    $query->whereNull('is_spam')
                        ->orWhere('is_spam', '')
                        ->orWhereRaw("NOT FIND_IN_SET(?, is_spam)", [$userId]);
                });

                if ($search) {
                    $allMessages = searchMessages($search, $userQuery);
                } else {
                    $allMessages = $userQuery->get();
                }

                $total_records = $allMessages->count();
                $sent_emails_list = $userQuery->orderBy('created_at', 'desc') 
                    ->offset($offset)
                    ->limit($limit)
                    ->get();

            $responseData = [];
    
            foreach ($sent_emails_list as $index => $message) {
                $userData = getLogUserData($userId);
                $userDataUsername = $userData->username ?? '';
                $userDataFirstName = $userData->first_name ?? '';
                $userDataLastName = $userData->last_name ?? '';
                $replyData = getReplyData($message->id,$userId);
                $isFavourite = isFavourite($userId, $message->id);
                $isRead = isRead($userId, $message->id);
                $tags = getTagsFromLabels($message->is_label, $user->id);

                $responseData[] = [
                    'id' => $message->id,
                    'userId' => $user->id,
                    'name' => $userDataUsername,
                    'firstname' => $userDataFirstName,
                    'lastname' => $userDataLastName,
                    'profile_image_path' => $userData->profile_image_path ? getFileTemporaryURL($userData->profile_image_path) : null,
                    'theme' => getThemeById($message->user_id),
                    'message' => [
                        'subject' => $message->subject,
                        'meta' => [
                            'tags' => $tags,
                            'inbox' => false,
                            'checked' => false,
                            'favourite' => $isFavourite,
                            'archived' => false,
                            'trash' => false,
                            'sent' => true,
                            'read' =>$isRead
                        ],
                        'reply' => $replyData,
                    ],
                ];
            }
            $last_page = ceil($total_records / $limit);
            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'Sent Emails list retrieved successfully','data' => $responseData,'pagination' => ['total_records' => $total_records,'per_page' => $limit,'current_page' => $current_page,'last_page' => $last_page,],'toast' => true ]);

        } catch (\Exception $e) {
          Log::info('Get Sent Emails list API error : ' . $e->getMessage());
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true ]);
        }
    }
   
    public function setArchiveEmailStatus(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $archieve_id = $request->input('archieve_id');
        
            if (!$user || !isset($user->id)) 
            {
            return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User Not found','toast' => true ]);
            }
            if (empty($archieve_id)) {
            return generateResponse(['type' => 'error', 'code' => 400, 'status' => false,'message' => 'Archive ID is required','toast' => true ]);
            }
            $emails = $user->email;
            
            $userId = User::where('email', $emails)->pluck('id')->first();
            $existingArchive = MailSent::where('id', $archieve_id)->value('is_archive');
            $archiveArray = $existingArchive ? explode(',', $existingArchive) : [];

            if (in_array($userId, $archiveArray)) {
                $archiveArray = array_diff($archiveArray, [$userId]);
            } else {
                $archiveArray[] = $userId;
            }

            $newFavouriteString = implode(',', $archiveArray);

            MailSent::where('id', $archieve_id)->update(['is_archive' => $newFavouriteString]);

            return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Archive status updated successfully','toast' => true ]);
        } catch (\Exception $e) {
            Log::error('Error updating archive status: ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Error while processing','toast' => true ]);
        }
    }

    public function deleteEmail(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $email_ids = $request->input('email_id'); 

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User Not found', 'toast' => true]);
            }

            if (empty($email_ids)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Email ID(s) is required', 'toast' => true]);
            }

            $email_ids = explode(',', $email_ids);

            foreach ($email_ids as $email_id) {
                $email_id = trim($email_id);

                $exist_delete_id = MailSent::where('id', $email_id)->first();
                if (!$exist_delete_id) {
                    return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Email id data not found', 'toast' => true]);
                }

                // $mail_replies = MailReply::where('mail_id', $email_id)->get();
                // if ($mail_replies->isEmpty()) {
                //     return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'MailReply with email id not found', 'toast' => true]);
                // }
                $currentIsSpam = $exist_delete_id->is_spam;
                $spamUserIds = $currentIsSpam ? explode(',', $currentIsSpam) : [];

                $currentIsDelete = $exist_delete_id->is_delete;
                $deletedUserIds = $currentIsDelete ? explode(',', $currentIsDelete) : [];

                $currentIsTrash = $exist_delete_id->is_trash;
                $trashedUserIds = $currentIsTrash ? explode(',', $currentIsTrash) : [];

                if (in_array($user->id, $spamUserIds)) {
                    if (!in_array($user->id, $deletedUserIds)) {
                        $deletedUserIds[] = $user->id;
                    }
                    if (!in_array($user->id, $trashedUserIds)) {
                        $trashedUserIds[] = $user->id;
                    }
                } else {
                    if (!in_array($user->id, $deletedUserIds)) {
                        $deletedUserIds[] = $user->id;
                    } else {
                        if (!in_array($user->id, $trashedUserIds)) {
                            $trashedUserIds[] = $user->id;
                        }
                    }
                }

                // foreach ($mail_replies as $mail_reply) {
                //     $replyDeletedUserIds = $mail_reply->is_delete ? explode(',', $mail_reply->is_delete) : [];
    
                //     if (!in_array($user->id, $replyDeletedUserIds)) {
                //         $replyDeletedUserIds[] = $user->id;
                //         $mail_reply->is_delete = implode(',', $replyDeletedUserIds);
                //         $mail_reply->save();
                //     }
                // }

                $deleted_at_json = $exist_delete_id->deleted_at ? json_decode($exist_delete_id->deleted_at, true) : [];
                $userExistsInDeletedAt = array_search($user->id, array_column($deleted_at_json, 'id')) !== false;

                if (!$userExistsInDeletedAt) {
                    $deleted_at_json[] = [
                        'id' => $user->id,
                        'date' => now()->format('Y-m-d H:i:s')
                    ];
                }

                $updated = MailSent::where('id', $email_id)
                    ->update([
                        'is_delete' => implode(',', $deletedUserIds),
                        'is_trash' => implode(',', $trashedUserIds),
                        'deleted_at' => json_encode(array_values($deleted_at_json))
                    ]);

                if (!$updated) {
                    return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Email not found for ID: ' . $email_id, 'toast' => true]);
                }
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Emails deleted successfully', 'toast' => true]);

        } catch (\Exception $e) {
            Log::error('Error Delete Email: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    
    public function getEmail(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $userId =$user->id;
            $email_id = $request->input('email_id');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User Not found','toast' => true ]);
            }
            if (empty($email_id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false,'message' => 'Email ID is required','toast' => true ]);
            }
            
            $email_data = MailSent::where('id', $email_id)
                ->where(function ($query) use ($userId) {
                    $query->whereRaw("FIND_IN_SET(?, user_id)", [$userId])
                        ->orWhereRaw('FIND_IN_SET(?, is_recipients)', [$userId]);
                })
                ->select('id', 'user_id', 'sender', 'recipients', 'cc', 'bcc', 'subject', 'message', 'attachment','is_label','created_at')
                ->first();

            if ($email_data) {
                if ($email_data->attachment) {
                    $email_data->attachment = getFileTemporaryURL($email_data->attachment);
                }

                $userData = getLogUserData($userId);
                $userDataUsername = $userData->username ?? '';
                $replyData = getReplyData($email_data->id,$userId);
                $tags = getTagsFromLabels($email_data->is_label, $user->id);

                $isSent = isSent($userId, $email_data->id);
                $isRead = isRead($userId, $email_data->id);
                $isFavourite = isFavourite($userId, $email_data->id);
                $isArchive = isArchive($userId, $email_data->id);
                $isDraft = isDraft($userId, $email_data->id);
                $isSpam = isSpam($userId, $email_data->id);
                $isDelete = isDelete($userId, $email_data->id);
        
                $responseData = [
                    'id' => $email_data->id,
                    'userId' => $userId,
                    'name' => $userDataUsername,
                    'profile_image_path' => $userData->profile_image_path ? getFileTemporaryURL($userData->profile_image_path) : null,
                    'theme' => getThemeById($email_data->user_id),
                    'message' => [
                        'subject' => $email_data->subject,
                        'meta' => [
                            'tags' => $tags,
                            'inbox' => true,
                            'checked' => false,
                            'favourite' => $isFavourite,
                            'archived' => $isArchive,
                            'trash' => $isDelete,
                            'sent' => $isSent,
                            'read' =>$isRead,
                            'draft'=>$isDraft,
                            'spam' =>$isSpam
                        ],
                        'reply' => $replyData ,
                    ],
                ];

                return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'Email retrieved successfully','data' => $responseData,'toast' => true ]);
            } else {
                return generateResponse(['type' => 'error','code' => 404,'status' => false,'message' => 'Email not found','toast' => true ]);
            }

        } catch (\Exception $e) {
            Log::error('Error Show Email:Details ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Error while processing','toast' => true ]);
        }
    }

    public function getDraftEmail(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $userId = $user->id;

            if (!$user || !isset($user->id)) 
            {
            return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User Not found','toast' => true ]);
            }

            $search = $request->input('search');
            $limit = $request->input('limit', 10);  
            $current_page = $request->input('page', 1); 
            $offset = ($current_page - 1) * $limit; 
            
            $userQuery = MailSent::where('email_type', '1')
                ->where(function($query) use ($userId) {
                    $query->whereRaw("FIND_IN_SET(?, is_favourites)", [$userId])
                           ->orWhereRaw("FIND_IN_SET(?, user_id)", [$userId]);
                })
                ->where(function($query) use ($userId) {
                    $query->whereRaw("NOT FIND_IN_SET(?, is_delete)", [$userId])
                        ->orWhereNull('is_delete');
                })
                ->where(function($query) use ($userId) {
                    $query->whereRaw("NOT FIND_IN_SET(?, is_archive)", [$userId])
                        ->orWhereNull('is_archive');
                });
                if ($search) {
                    $allMessages = searchMessages($search, $userQuery);
                } else {
                    $allMessages = $userQuery->get();
                }
    
                $total_records = $allMessages->count();
                $email_data = $userQuery->orderBy('created_at', 'desc') 
                                    ->offset($offset)
                                    ->limit($limit)
                                    ->get();


            if ($email_data) {
                $responseData = [];
        
                foreach ($email_data as $index => $message) {
                    $userData = getLogUserData($userId);
                    $userDataUsername = $userData->username ?? '';
                    $userDataFirstName = $userData->first_name ?? '';
                    $userDataLastName = $userData->last_name ?? '';
                    $replyData = getReplyData($message->id,$userId);
                    $isFavourite = isFavourite($userId, $message->id);
                    $isArchive = isArchive($userId, $message->id);
                    $isRead = isRead($userId, $message->id);
                    $tags = getTagsFromLabels($message->is_label, $user->id);
        
                    if (!$isFavourite && ($isArchive)) {
                        continue; 
                    }
        
                    $responseData[] = [
                        'id' => $message->id,
                        'userId' => $message->user_id,
                        'name' => $userDataUsername,
                        'firstname' => $userDataFirstName,
                        'lastname' => $userDataLastName,
                        'profile_image_path' => $userData->profile_image_path ? getFileTemporaryURL($userData->profile_image_path) : null,
                        'theme' => getThemeById($message->user_id),
                        'message' => [
                            'subject' => $message->subject,
                            'meta' => [
                                'tags' => $tags,
                                'inbox' =>  false,
                                'checked' => false,
                                'favourite' =>  $isFavourite,
                                'archived' =>  $isArchive,
                                'trash' => false,
                                'sent' =>  false,
                                'draft' =>  true,
                                'read' =>$isRead
                            ],
                            'reply' => $replyData,
                        ],
                    ];
                }
                $last_page = ceil($total_records / $limit);
                return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'Draft email retrieved successfully','data'=>$responseData,'pagination' => ['total_records' => $total_records,'per_page' => $limit,'current_page' => $current_page,'last_page' => $last_page,],'toast' => true ]);
            } else {
                return generateResponse(['type' => 'error','code' => 404,'status' => false,'message' => 'Email not found','toast' => true ]);
            }

        } catch (\Exception $e) {
            Log::error('Error Draft Email:Details ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Error while processing','toast' => true ]);
        }
    }

    public function readEmail(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User Not found', 'toast' => true]);
            }

            $read_ids = $request->input('read_id');
            $action = $request->input('action');

            $read_idsArray = explode(',', $read_ids);
            $userId = User::where('email', $user->email)->pluck('id')->first();

            foreach ($read_idsArray as $read_id) {
                $existingReads = MailSent::where('id', $read_id)->value('is_read');
                $readsArray = $existingReads ? explode(',', $existingReads) : [];

                if ($action === 'read') {
                    if (!in_array($userId, $readsArray)) {
                        $readsArray[] = $userId;
                    }
                } elseif ($action === 'unread') {
                    if (in_array($userId, $readsArray)) {
                        $readsArray = array_diff($readsArray, [$userId]);
                    }
                }

                $newReadString = implode(',', $readsArray);
                MailSent::where('id', $read_id)->update(['is_read' => $newReadString]);
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Read status updated successfully', 'toast' => true]);
        } catch (\Exception $e) {
            Log::info('Read mails list API error : ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function getSpamEmail(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User Not found','toast' => true ]);
            }
    
            $userId = $user->id;
            $search = $request->input('search', '');
            $limit = $request->input('limit', 10);
            $currentPage = $request->input('page', 1);
            $offset = ($currentPage - 1) * $limit;
    
            $currentDate = now()->format('Y-m-d H:i:s');
            $thirtyDaysAgo = now()->subDays(30)->format('Y-m-d H:i:s');
            $cutoffDate = now()->subDays(30)->format('Y-m-d H:i:s');
            
            $userQuery = MailSent::where(function($query) use ($userId) {
                $query->whereRaw("FIND_IN_SET(?, is_spam)", [$userId]);
            })
            ->where(function($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_delete)", [$userId])
                      ->orWhereNull('is_delete');
            })
            ->where(function($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_trash)", [$userId])
                      ->orWhereNull('is_trash');
            })
            ->where(function ($query) use ($userId, $cutoffDate) {
                $query->whereRaw("JSON_CONTAINS(deleted_at, JSON_OBJECT('id', ?))", [$userId])
                     ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(deleted_at, '$[0].date')) >= ?", [$cutoffDate]);
            });
            if ($search) {
                $allMessages = searchMessages($search, $userQuery);
            } else {
                $allMessages = $userQuery->get();
            }

            $total_records = $allMessages->count();
            $filtered_spam_list = $userQuery->orderBy('created_at', 'desc') 
                                ->offset($offset)
                                ->limit($limit)
                                ->get();
    
            $responseData = [];
    
            foreach ($filtered_spam_list as $index => $message) {
                $userData = getLogUserData($userId);
                $userDataUsername = $userData->username ?? '';
                $userDataFirstName = $userData->first_name ?? '';
                $userDataLastName = $userData->last_name ?? '';
                $replyData = getReplyData($message->id, $userId);
                $isFavourite = isFavourite($userId, $message->id);
                $isRead = isRead($userId, $message->id);
                $isInbox = in_array($userId, explode(',', $message->is_recipients ?? ''));
                $isSent = isSent($userId, $message->id);
                $tags = getTagsFromLabels($message->is_label, $user->id);
    
                $responseData[] = [
                    'id' => $message->id,
                    'userId' => $userId,
                    'name' => $userDataUsername,
                    'firstname' => $userDataFirstName,
                    'lastname' => $userDataLastName,
                    'profile_image_path' => $userData->profile_image_path ? getFileTemporaryURL($userData->profile_image_path) : null,
                    'theme' => getThemeById($message->user_id),
                    'message' => [
                        'subject' => $message->subject,
                        'meta' => [
                            'tags' => $tags,
                            'inbox' =>  $isInbox,
                            'checked' => false,
                            'favourite' => $isFavourite,
                            'archived' => false,
                            'trash' => false,
                            'sent' => $isSent,
                            'spam' => true,
                            'read' => $isRead
                        ],
                        'reply' => $replyData,
                    ],
                ];
            }
    
            $last_page = ceil($total_records / $limit);
    
            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'Spam list retrieved successfully','data' => $responseData,
                'pagination' => [
                    'total_records' => $total_records, 
                    'per_page' => $limit,
                    'current_page' => $currentPage,
                    'last_page' => $last_page,
                ],
                'toast' => true
            ]);
    
        } catch (\Exception $e) {
            Log::info('Get Spam list API error : ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function replyEmail(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $email = $request->input('from_email');
            $mail_id = $request->input('mail_id');
            $recipients =$request->recipients;
            
            $attachmentName = null;
            $attachmentPath = null;
            $attachmentFilePath =null;
            if ($request->hasFile('attachement')) {
                $attachment = $request->file('attachement');
                $attachmentName = time() . '_' . $attachment->getClientOriginalName();
                $filePath = "users/private/{$user->id}/attachment/{$attachmentName}";
                $attachmentPath = Storage::put($filePath, file_get_contents($attachment));
                $attachmentFilePath = $filePath; 
            }
        
            $replyData = [
                'user_id' => $user->id,
                'from' => $email,
                'recipients' =>$recipients,
                'mail_id' => $mail_id,
                'attachment' => $attachmentFilePath,
            ];

            if (isset($request->cc)) {
                $replyData['cc'] = $request->cc;
            }
            if (isset($request->bcc)) {
                $replyData['bcc'] = $request->bcc;
            }
            if (isset($request->message)) {
                $replyData['message'] = $request->message;
            }

            MailReply::create($replyData);
            
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Reply send successfully', 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reply Mail data retrieval error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error processing the request', 'toast' => true]);
        }
    }
    public function deleteReplyEmail(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $reply_id = $request->input('reply_id');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User not found', 'toast' => true]);
            }

            if (empty($reply_id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Reply ID is required', 'toast' => true]);
            }

            $mailReply = MailReply::where('id', $reply_id)->first();
            
            if (!$mailReply) {
                return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Reply not found', 'toast' => true]);
            }

            $recipientsIds = $mailReply->is_recipients ? explode(',', $mailReply->is_recipients) : [];
            if (!in_array($user->id, $recipientsIds)) {
                $recipientsIds[] = $user->id;
                $mailReply->update(['is_recipients' => implode(',', $recipientsIds)]);
            }

            $deleteIds = $mailReply->is_delete ? explode(',', $mailReply->is_delete) : [];
            
            if (!in_array($user->id, $deleteIds)) {
                $deleteIds[] = $user->id;
                $mailReply->update(['is_delete' => implode(',', $deleteIds)]);
            }elseif (in_array($user->id, $deleteIds)) {
                MailReply::where('id', $reply_id)->update(['is_trash' => $user->id]);
            }

            $mailSentData = MailSent::where('id', $mailReply->mail_id)->first();
            if ($mailSentData) {
                $mailReplies = MailReply::where('mail_id', $mailSentData->id)->get();

                $totalRecipientsCount = 0;
                $totalDeleteCount = 0;

                foreach ($mailReplies as $reply) {
                    $isRecipientsIds = $reply->is_recipients ? explode(',', $reply->is_recipients) : [];
                    $isDeleteIds = $reply->is_delete ? explode(',', $reply->is_delete) : [];

                    if (in_array($user->id, $isRecipientsIds)) {
                        $totalRecipientsCount++;
                        if (in_array($user->id, $isDeleteIds)) {
                            $totalDeleteCount++;
                        }
                    }
                }

                if ($totalRecipientsCount > 0 && $totalRecipientsCount === $totalDeleteCount) {
                    $mailSentDeleteIds = $mailSentData->is_delete ? explode(',', $mailSentData->is_delete) : [];
                    
                    $deleted_at_json = $mailSentData->deleted_at ? json_decode($mailSentData->deleted_at, true) : [];
                    $userExistsInDeletedAt = array_search($user->id, array_column($deleted_at_json, 'id')) !== false;
    
                    if (!$userExistsInDeletedAt) {
                        $deleted_at_json[] = [
                            'id' => $user->id,
                            'date' => now()->format('Y-m-d H:i:s')
                        ];
                    }
                    
                    if (!in_array($user->id, $mailSentDeleteIds)) {
                        $mailSentDeleteIds[] = $user->id;
                        $mailSentData->update(['is_delete' => implode(',', $mailSentDeleteIds),'deleted_at' => json_encode(array_values($deleted_at_json))]);
                    }
                }
            }

            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Reply deleted successfully', 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete Reply Mail data retrieval error: ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error processing the request', 'toast' => true]);
        }
    }

    public function undoTrashEmail(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $email_ids = $request->input('email_id');
    
            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User not found', 'toast' => true]);
            }
            if (empty($email_ids)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Email ID is required', 'toast' => true]);
            }
    
            $email_ids_array = explode(',', $email_ids);
    
            foreach ($email_ids_array as $email_id) {
                $exist_email = MailSent::where('id', $email_id)->first();
                if (!$exist_email) {
                    return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => "Email ID {$email_id} data not found", 'toast' => true]);
                }

                $isDeleteValues = $exist_email->is_delete ? explode(',', $exist_email->is_delete) : [];
                    $key = array_search($user->id, $isDeleteValues);
                    unset($isDeleteValues[$key]);
                    $updatedIsDelete = implode(',', $isDeleteValues);
    
                    MailSent::where('id', $email_id)->update(['is_delete' => $updatedIsDelete]);
    
                    $deletedAtValues = $exist_email->deleted_at ? json_decode($exist_email->deleted_at, true) : [];
                    
                    $updatedDeletedAtValues = array_filter($deletedAtValues, function ($entry) use ($user) {
                        return $entry['id'] != $user->id;
                    });
    
                    if (isset($deletedAtValues[1]) && is_array($deletedAtValues)) {
                        $updatedDeletedAtValues = array_values($updatedDeletedAtValues);
                    }
    
                    MailSent::where('id', $email_id)->update(['deleted_at' => json_encode($updatedDeletedAtValues)]);
    
                    $isArchiveValues = $exist_email->is_archive ? explode(',', $exist_email->is_archive) : [];
                    if (($archiveKey = array_search($user->id, $isArchiveValues)) !== false) {
                        unset($isArchiveValues[$archiveKey]);
                        $updatedIsArchive = implode(',', $isArchiveValues);
    
                        MailSent::where('id', $email_id)->update(['is_archive' => $updatedIsArchive]);
                    }
    
                    // $mailReplies = MailReply::where('mail_id', $email_id)->get();
                    // foreach ($mailReplies as $reply) {
                    //     $replyIsDeleteValues = $reply->is_delete ? explode(',', $reply->is_delete) : [];
                    //     $replyIsTrashValues = $reply->is_trash ? explode(',', $reply->is_trash) : [];
    
                    //     if (($replyKey = array_search($user->id, $replyIsDeleteValues)) !== false && !in_array($user->id, $replyIsTrashValues)) {
                    //         unset($replyIsDeleteValues[$replyKey]);
                    //         $updatedReplyIsDelete = implode(',', $replyIsDeleteValues);
    
                    //         MailReply::where('id', $reply->id)->update(['is_delete' => $updatedReplyIsDelete]);
                    //     }
                    // }
            }
    
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Undo trash email successfully', 'toast' => true]);
    
        } catch (\Exception $e) {
            Log::error('Error Undoing Trash Email: ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Error while processing','toast' => true ]);
        }
    }
    
    public function getLabelsCount(Request $request)
    {
        try {
            $user = $request->attributes->get('user');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User not found','toast' => true ]);
            }
            $userId = $user->id;
            $inboxListData = getInboxList($userId);
            $inboxLists = $inboxListData['inbox_list'];

            $inboxCount = $inboxLists->filter(function ($message) use ($userId) {
                $isRead = in_array($userId, explode(',', $message->is_read));
                return !$isRead;
            })->count();

            
            $favouriteList = getFavouriteList($userId);
            $filteredFavouriteCount = $favouriteList->filter(function ($message) use ($userId) {
                $isFavourite = in_array($userId, explode(',', $message->is_favourites));
                $isRecipient = in_array($userId, explode(',', $message->is_recipients));
                $isArchive = in_array($userId, explode(',', $message->is_archive));
                $isSent = $message->user_id == $userId;
                $isRead = in_array($userId, explode(',', $message->is_read));

                return $isFavourite && ($isRecipient || $isArchive || $isSent || $isRead);
            })->count();

            $sentEmailsListCount = getSentEmailsList($userId)->count();
            $spamEmailList = getSpamList($userId)->count();

            $filteredTrashListCount = getTotalRecordsCount($userId);
            $allEmailsCount = getAllEmails($userId)->count();
            $draftEmailsListCount = getDraftEmailsList($userId)->count();

            return response()->json([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Counts retrieved successfully',
                'inbox_count' => $inboxCount,
                'draft_count' => $draftEmailsListCount,
                'favourite_count' => $filteredFavouriteCount,
                'sent_count' => $sentEmailsListCount,
                'spam_count' =>$spamEmailList,
                'trash_count' => $filteredTrashListCount,
                'all_mails_count' => $allEmailsCount,
                'toast' => true 
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving email counts: ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Error while processing','toast' => true ]);
        }
    }
    public function addSnippet(Request $request)
    {
        try {
            $user = $request->attributes->get('user');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User not found', 'toast' => true ]);
            }

            $snippet = $request->input('snippet');
            $name = $request->input('name');
            $type = $request->input('type'); 

            if (empty($snippet)) {
                return ['type' => 'error','code' => 400,'status' => false,'message' => 'Snippet cannot be empty','toast' => true ];
            }

            $result = handleSnippet($snippet, $name,$type, $user->id, $request->input('id'));

            if ($result['success']) {
                return ['type' => 'success','code' => 200,'status' => true,'message' => $result['message'],'toast' => true ];
            } else {
                return ['type' => 'error','code' => 404,'status' => false,'message' => $result['message'],'toast' => true ];
            }

        } catch (\Exception $e) {
            Log::error('Error adding/updating snippet for user ID ' . ($user->id ?? 'unknown') . ': ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true ]);
        }
    }
    public function deleteSnippet(Request $request)
    {
        try {
            $user = $request->attributes->get('user');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User not found', 'toast' => true ]);
            }
            $id = $request->input('id');
            $type = $request->input('type');
            if (empty($id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'id not found', 'toast' => true]);
            }
            $snippetData = MailSnippets::where('id', $id)->where('type', $type)->first();

            if (!$snippetData) {
                $message = ($type == '0') ? 'Quick response not found' : 'Signature not found';
                return generateResponse(['type' => 'error','code' => 404,'status' => false,'message' => $message,'toast' => true ]);
            }

            $snippetData->delete();
            $message = ($type == '0') ? 'Quick response deleted successfully' : 'Signature deleted successfully';

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'toast' => true ]);

        } catch (\Exception $e) {
            Log::error('Error delete quick response of user' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true ]);
        }
    }
    public function getSnippet(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $type = $request->input('type');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User not found', 'toast' => true ]);
            }
            $quickResponseList = MailSnippets::where('user_id', $user->id)->where('type',$type)
            ->get(['id', 'quick_response','signature','name']) 
            ->map(fn($response) => [
                'id' => $response->id,
                'snippet' => ($type == '0') ? $response->quick_response : $response->signature,
                'name' => $response->name
            ]);
            if ($quickResponseList->isEmpty()) {
                $message = 'No records found';
            } else {
                $message = ($type == '0') ? 'Quick response retrieved successfully' : 'Signature retrieved successfully';
            }
            
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'data'=>$quickResponseList, 'toast' => true ]);

        } catch (\Exception $e) {
            Log::error('Error get snippet of user' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true ]);
        }
    }
    public function getSuggestions(Request $request)
    {
        try {
            $user = $request->attributes->get('user');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User not found', 'toast' => true ]);
            }

            $suggestion = $request->input('suggestion');

            if (empty($suggestion)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Suggestion not provided', 'toast' => true ]);
            }
            $result = User::where('email', 'like', '%' . $suggestion . '%')
                ->orWhere('username', 'like', '%' . $suggestion . '%')
                ->orWhere('first_name', 'like', '%' . $suggestion . '%')
                ->orWhere('last_name', 'like', '%' . $suggestion . '%')
                ->get();

            if ($result->isEmpty()) {
                return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'No matching records found', 'toast' => true ]);
            }
            $json = [];
            foreach ($result as $user) {
                $email = $user->username . '@silocloud.io'; 
                $json[] = ['id' => $user->email, 'text' => $email,'firstname' =>$user->first_name,'lastname' =>$user->last_name];
            }

            $json[] = ['id' => $suggestion, 'text' => $suggestion];

            return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Suggestions retrieved successfully','data' => $json,'toast' => true ]);

        } catch (\Exception $e) {
            Log::error('Error getting suggestions for user: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true ]);
        }
    }
    public function undoSpamEmail(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $email_ids = $request->input('email_id');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User not found', 'toast' => true]);
            }
            if (empty($email_ids)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Email ID is required', 'toast' => true]);
            }

            $email_ids_array = explode(',', $email_ids);

            foreach ($email_ids_array as $email_id) {
                $exist_email = MailSent::where('id', $email_id)->first();
                if (!$exist_email) {
                    return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => "Email ID {$email_id} data not found", 'toast' => true]);
                }

                $isSpamValues = $exist_email->is_spam ? explode(',', $exist_email->is_spam) : [];
                $deleted_at_json = $exist_email->deleted_at ? json_decode($exist_email->deleted_at, true) : [];

                if (($key = array_search($user->id, $isSpamValues)) !== false) {
                    unset($isSpamValues[$key]);
                    $updatedIsSpam = implode(',', $isSpamValues);

                    $deleted_at_json = array_filter($deleted_at_json, function ($entry) use ($user) {
                        return $entry['id'] != $user->id;
                    });

                    MailSent::where('id', $email_id)->update(['is_spam' => $updatedIsSpam,'deleted_at' => json_encode(array_values($deleted_at_json))]);

                    $deletedAtValues = $exist_email->deleted_at ? json_decode($exist_email->deleted_at, true) : [];
                    $updatedDeletedAtValues = array_filter($deletedAtValues, function ($entry) use ($user) {
                        return $entry['id'] != $user->id;
                    });
                    MailSent::where('id', $email_id)->update(['deleted_at' => json_encode($updatedDeletedAtValues)]);

                    $spamLog = SpamEmailLog::where('user_id', $user->id)->first();
                    $spam_score = max(0, $spamLog->spam_score - 1);

                    $mail_id_array = explode(',', $spamLog->mail_id);
                    if (($mailKey = array_search($email_id, $mail_id_array)) !== false) {
                        unset($mail_id_array[$mailKey]);
                    }
                    $unique_mail_ids = implode(',', array_unique($mail_id_array));

                    $spamLog->update([
                        'spam_score' => $spam_score,
                        'mail_id' => $unique_mail_ids,
                    ]);

                    $isArchiveValues = $exist_email->is_archive ? explode(',', $exist_email->is_archive) : [];
                    if (($archiveKey = array_search($user->id, $isArchiveValues)) !== false) {
                        unset($isArchiveValues[$archiveKey]);
                        $updatedIsArchive = implode(',', $isArchiveValues);
                        MailSent::where('id', $email_id)->update(['is_archive' => $updatedIsArchive]);
                    }
                } else {
                    $userExistsInDeletedAt = collect($deleted_at_json)->contains(function ($entry) use ($user) {
                        return $entry['id'] == $user->id;
                    });
    
                    if (!$userExistsInDeletedAt) {
                        $isSpamValues[] = $user->id;
                        $updatedIsSpam = implode(',', $isSpamValues);
    
                        $deleted_at_json[] = [
                            'id' => $user->id,
                            'date' => now()->format('Y-m-d H:i:s')
                        ];
                        MailSent::where('id', $email_id)->update(['is_spam' => $updatedIsSpam, 'deleted_at' => json_encode(array_values($deleted_at_json))]);
    
                        $spamLog = SpamEmailLog::where('user_id', $user->id)->first();
                        if ($spamLog) {
                            $spamScore = $spamLog->spam_score ?? 0;
                            $spam_score = $spamScore + 1;
                    
                            $mail_id_array = $spamLog->mail_id ? explode(',', $spamLog->mail_id) : [];
                            $mail_id_array[] = $email_id;
                            $unique_mail_ids = implode(',', array_unique($mail_id_array));
                    
                            $spamLog->update([
                                'spam_score' => $spam_score,
                                'mail_id' => $unique_mail_ids,
                            ]);
                        } else {
                            SpamEmailLog::create([
                                'user_id' => $user->id,
                                'spam_score' => 1,
                                'mail_id' => $email_id
                            ]);
                        }
                    }
    
                    $isArchiveValues = $exist_email->is_archive ? explode(',', $exist_email->is_archive) : [];
                    if (($archiveKey = array_search($user->id, $isArchiveValues)) !== false) {
                        unset($isArchiveValues[$archiveKey]);
                        $updatedIsArchive = implode(',', $isArchiveValues);
                        MailSent::where('id', $email_id)->update(['is_archive' => $updatedIsArchive]);
                    }
                
                }
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Undo spam email successfully', 'toast' => true]);

        } catch (\Exception $e) {
            Log::error('Error Undoing spam Email: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function addLabel(AddMailLabelsRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $label = $request->input('label');
            $theme = $request->input('theme');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User not found','toast' => true ]);
            }

            if (empty($label)) {
                return generateResponse(['type' => 'error','code' => 422,'status' => false,'message' => 'Label is required','toast' => true ]);
            }

            $emailLabel = EmailLabel::create([
                'user_id' => $user->id,
                'labels' => $label,
                'theme' => $theme
            ]);

            if (!$emailLabel) {
                return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Failed to add label','toast' => true ]);
            }

            return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Label added successfully','toast' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error adding email label: ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'An error occurred while processing the request','toast' => true ]);
        }
    }
    public function deleteLabel(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $label_id = $request->input('id');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User not found','toast' => true ]);
            }

            if (empty($label_id)) {
                return generateResponse(['type' => 'error','code' => 422,'status' => false,'message' => 'Label id is required','toast' => true ]);
            }
            $label = EmailLabel::where('id', $label_id)->where('user_id', $user->id)->first();

            if (!$label) {
                return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Label  not found','toast' => true ]);
            }
            
            $label->delete();

            return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Label deleted successfully','toast' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error delete email label: ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'An error occurred while processing the request','toast' => true ]);
        }
    }
    public function getLabel(Request $request)
    {
        try {
            $user = $request->attributes->get('user');

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User not found','toast' => true ]);
            }
            
            $label_data = EmailLabel::where(function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                        ->orWhereNull('user_id');
            })->get();

            if (!$label_data) {
                return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Label  not found','toast' => true ]);
            }

            return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Label retrieved successfully','data'=>$label_data,'toast' => true ]);
            
        } catch (\Exception $e) {
            Log::error('Error delete email label: ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'An error occurred while processing the request','toast' => true ]);
        }
    }
    public function updateEmailLabel(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $email_id = $request->input('email_id');
            $label_ids = explode(',', $request->input('label_id'));

            if (!$user || !isset($user->id)) {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User not found', 'toast' => true]);
            }

            $email = MailSent::find($email_id);

            if (!$email) {
                return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Email not found', 'toast' => true]);
            }

            $labels = $email->is_label ? json_decode($email->is_label, true) : [];
            if (!is_array($labels)) {
                $labels = [];
            }

            $updated = false;

            $label_ids = array_map('intval', $label_ids);

            foreach ($labels as &$entry) {
                if (isset($entry['uid']) && $entry['uid'] == $user->id) {
                    $entry['label_id'] = $label_ids;
                    $updated = true;
                    break;
                }
            }

            if (!$updated) {
                $labels[] = [
                    'uid' => $user->id,
                    'label_id' => $label_ids
                ];
            }

            $email->is_label = json_encode($labels, JSON_PRETTY_PRINT);
            $email->save();

            return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Updated email labels successfully','data' => $labels,'toast' => true ]);

        } catch (\Exception $e) {
            Log::error('Error updating email label: ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'An error occurred while processing the request','toast' => true ]);
        }
    }
    public function getFilterlabel(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $userId =$user->id;
            
            if (!$user || !isset($user->id)) 
            {
            return generateResponse(['type' => 'error','code' => 400,'status' => false,'message' => 'User Not found','toast' => true ]);
            }

            $search = $request->input('search', '');
            $limit = $request->input('limit', 10);
            $currentPage = $request->input('page', 1);
            $offset = ($currentPage - 1) * $limit;
            $label_id = $request->input('label_id');

            $userQuery = MailSent::where(function($query) use ($userId) {
                    $query->whereRaw("FIND_IN_SET(?, is_recipients)", [$userId])
                      ->orWhereRaw("FIND_IN_SET(?, is_draft)", [$userId])
                      ->orWhereRaw("FIND_IN_SET(?, is_archive)", [$userId])
                      ->orWhereRaw("FIND_IN_SET(?, user_id)", [$userId]);
                    })
                    ->where(function($query) use ($userId) {
                        $query->whereNull('is_delete')
                            ->orWhere('is_delete', '')
                            ->orWhereRaw("NOT FIND_IN_SET(?, is_delete)", [$userId]);
                    })
                    ->where(function($query) use ($userId) {
                        $query->whereNull('is_trash')
                            ->orWhere('is_trash', '')
                            ->orWhereRaw("NOT FIND_IN_SET(?, is_trash)", [$userId]);
                    })
                    ->where(function($query) use ($userId) {
                        $query->whereNull('is_spam')
                            ->orWhere('is_spam', '')
                            ->orWhereRaw("NOT FIND_IN_SET(?, is_spam)", [$userId]);
                    })
                    ->whereRaw("JSON_CONTAINS(JSON_EXTRACT(is_label, '$[*].uid'), ?)", [$userId])
                    ->whereRaw("JSON_SEARCH(JSON_EXTRACT(is_label, '$[*].label_id'), 'one', ?) IS NOT NULL", [$label_id]);
                    if ($search) {
                        $allMessages = searchMessages($search, $userQuery);
                    } else {
                        $allMessages = $userQuery->get();
                    }
        
                    $total_records = $allMessages->count();
                    $email_data = $userQuery->orderBy('created_at', 'desc') 
                                        ->offset($offset)
                                        ->limit($limit)
                                        ->get();
            

            $responseData = [];
        
                foreach ($email_data as $index => $message) {
                    $userData = getLogUserData($userId);
                    $userDataUsername = $userData->username ?? '';
                    $userDataFirstName = $userData->first_name ?? '';
                    $userDataLastName = $userData->last_name ?? '';
                    $replyData = getReplyData($message->id,$userId);
                    $isFavourite = isFavourite($userId, $message->id);
                    $isArchive = isArchive($userId, $message->id);
                    $isRead = isRead($userId, $message->id);
                    $tags = getTagsFromLabels($message->is_label, $user->id);

                    $isRecipients = MailSent::where(function ($query) use ($userId, $message) {
                        $query->whereRaw("FIND_IN_SET(?, is_recipients)", [$userId])
                            ->where('id', $message->id);
                    })->exists();

                    $isDraft = MailSent::where(function ($query) use ($userId, $message) {
                        $query->whereRaw("FIND_IN_SET(?, is_draft)", [$userId])
                            ->where('id', $message->id);
                    })->exists();

                    $isSent = isSent($userId, $message->id);
        
                    $responseData[] = [
                        'id' => $message->id,
                        'userId' => $userId,
                        'name' => $userDataUsername,
                        'firstname' => $userDataFirstName,
                        'lastname' => $userDataLastName,
                        'profile_image_path' => $userData->profile_image_path ? getFileTemporaryURL($userData->profile_image_path) : null,
                        'theme' => getThemeById($message->user_id),
                        'message' => [
                            'subject' => $message->subject,
                            'meta' => [
                                'tags' => $tags,
                                'inbox' =>  $isRecipients,
                                'checked' => false,
                                'favourite' => $isFavourite,
                                'archived' => $isArchive,
                                'trash' => false,
                                'sent' => $isSent,
                                'draft' => $isDraft,
                                'all_mail'=> true,
                                'read' =>$isRead
                            ],
                            'reply' => $replyData,
                        ],
                    ];
                }
                
                $last_page = ceil($total_records / $limit);
            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'Filter Emails list retrieved successfully','data' => $responseData,'pagination' => ['total_records' => $total_records,'per_page' => $limit,'current_page' => $currentPage,'last_page' => $last_page,],'toast' => true ]);

        } catch (\Exception $e) {
            Log::info('Get All Emails list API error : ' . $e->getMessage());
            return generateResponse(['type' => 'error','code' => 200,'status' => false,'message' => 'Error while processing','toast' => true ]);
        }
    }
}
