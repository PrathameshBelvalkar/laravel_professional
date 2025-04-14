<?php

use App\Models\Mail\MailReply;
use App\Models\Mail\MailSent;
use App\Models\Mail\MailSnippets;
use App\Models\Mail\SpamEmailLog;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

if (!function_exists('checkForInvalidDomain')) {
    function checkForInvalidDomain($emails)
    {
        foreach ($emails as $email) {
            if (str_contains($email, '@noitavonne.com')) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('saveOrUpdateMail')) {
    function saveOrUpdateMail($request, $user, $is_draft, $attachmentFilePath, $isRecipients, $mail_id = null, $draft_id = null,$reply_id = null,$emailContainsUserEmail=null,$spamScore=null)
    {
        $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');
        if ($draft_id) {
            $draft = MailSent::find($draft_id);
            if ($draft) {
                $updateData = [
                    'sender' => $user->username.'@silocloud.io',
                    'recipients' => $request->recipients,
                    'cc' => $request->cc,
                    'bcc' => $request->bcc,
                    'subject' => $request->subject,
                    'message' => $request->message,
                    'attachment' => $attachmentFilePath,
                    'email_type' => $is_draft,
                    'is_draft' => $is_draft == '1' ? $user->id : null,
                    'created_at' => $currentDateTime
                ];

                $updateReplyData = [
                    'from' => $user->username . '@silocloud.io',
                    'recipients' => $request->recipients,
                    'cc' => $request->cc,
                    'bcc' => $request->bcc,
                    'message' => $request->message,
                    'attachment' => $attachmentFilePath,
                    'created_at' => $currentDateTime
                ];

                if ($is_draft == '0' && $isRecipients) {
                    $existingRecipients = $draft->is_recipients ? explode(',', $draft->is_recipients) : [];
                    $newRecipients = explode(',', $isRecipients);
                    $mergedRecipients = array_unique(array_merge($existingRecipients, $newRecipients));
                    $updateData['is_recipients'] = implode(',', $mergedRecipients);
                    $updateReplyData['is_recipients'] = implode(',', $mergedRecipients);
                }
                $draft->update($updateData);

                MailReply::where('mail_id', $draft_id)->update($updateReplyData);

                return $draft;
            }
        } else {
            if ($is_draft == '0' && !empty($mail_id) && !empty($reply_id)) {
                $mail_sent = MailSent::find($mail_id);
                if ($mail_sent) {
                    $existingRecipients = $mail_sent->is_recipients ? explode(',', $mail_sent->is_recipients) : [];
                    $newRecipients = explode(',', $isRecipients);
                    $mergedRecipients = array_unique(array_merge($existingRecipients, $newRecipients));
                    $mail_sent->is_recipients = implode(',', $mergedRecipients);

                    $existingSender = $mail_sent->user_id ? explode(',', $mail_sent->user_id) : [];
                    if (!in_array($user->id, $existingSender)) {
                        $existingSender[] = $user->id;
                    }
                    $mail_sent->user_id = implode(',', $existingSender);

                    // Remove $isRecipients values from is_read
                    if (!empty($mail_sent->is_read)) {
                        $existingReads = explode(',', $mail_sent->is_read);
                        foreach ($newRecipients as $recipient) {
                            if (($key = array_search($recipient, $existingReads)) !== false) {
                                unset($existingReads[$key]);
                            }
                        }
                        $mail_sent->is_read = implode(',', $existingReads);
                    }

                    $isRecipientsArray = explode(',', $isRecipients);
                    if ($mail_sent->is_delete) {
                        $existingDeletes = explode(',', $mail_sent->is_delete);
                        $updatedDeletes = array_diff($existingDeletes, $isRecipientsArray);
                        $mail_sent->is_delete = implode(',', $updatedDeletes);
                    }

                    if ($mail_sent->is_trash) {
                        $existingTrashes = explode(',', $mail_sent->is_trash);
                        $updatedTrashes = array_diff($existingTrashes, $isRecipientsArray);
                        $mail_sent->is_trash = implode(',', $updatedTrashes);
                    }

                    if ($mail_sent->deleted_at) {
                        $deletedAtArray = json_decode($mail_sent->deleted_at, true);
                        foreach ($deletedAtArray as $key => $entry) {
                            if (in_array($entry['id'], $isRecipientsArray)) {
                                unset($deletedAtArray[$key]);
                            }
                        }
                        $mail_sent->deleted_at = json_encode(array_values($deletedAtArray));
                    }

                    $mail_sent->created_at = $currentDateTime;
                    $mail_sent->save();

                    return $mail_sent;
                }
            } else {
                $createData = [
                    'user_id' => $user->id,
                    'sender' => $user->username . '@silocloud.io',
                    'recipients' => $request->recipients,
                    'cc' => $request->cc,
                    'bcc' => $request->bcc,
                    'subject' => $request->subject,
                    'message' => $request->message,
                    'attachment' => $attachmentFilePath,
                    'email_type' => $is_draft,
                ];

                if ($is_draft == '1') {
                    $createData['is_draft'] = $user->id;
                    $createData['is_read'] = $user->id;
                }

                if ($is_draft == '0') {
                    if ($spamScore >= 20) {
                        $createData['is_spam'] = $isRecipients;
                        $recipientsArray = explode(',', $isRecipients);
                        $deleted_at_json = [];

                        foreach ($recipientsArray as $recipient) {
                            $deleted_at_json[] = [
                                'id' => (int)$recipient,
                                'date' => now()->format('Y-m-d H:i:s')
                            ];
                        }

                        $createData['deleted_at'] = json_encode($deleted_at_json);
                    }
                    $createData['is_recipients'] = $isRecipients;
                    if (!$emailContainsUserEmail) {
                        $createData['is_read'] = $user->id;
                    }
                }

                return MailSent::create($createData);
            }
        }
        return null;
    }
}

if (!function_exists('createMailReply')) {
    function createMailReply($user, $request, $attachmentFilePath, $isRecipients, $mail_id = null, $mail_sent_id = null, $reply_id = null, $draft_id = null)
    {
        $userId = $user->id;
        $isRecipientsArray = explode(',', $isRecipients);

        if (!in_array($userId, $isRecipientsArray)) {
            $isRecipientsArray[] = $userId;
        }
        $isRecipients = implode(',', $isRecipientsArray);
        $replyData = [
            'user_id' => $user->id,
            'from' => $user->username . '@silocloud.io',
            'recipients' => $request->recipients,
            'cc' => $request->cc,
            'bcc' => $request->bcc,
            'message' => $request->message,
            'attachment' => $attachmentFilePath,
            'is_recipients' => $isRecipients,
            'mail_id' => $mail_id ?: $mail_sent_id,
            'reply_id' => $reply_id,
        ];
        if ($draft_id) {
            MailReply::where('mail_id', $draft_id)->update($replyData);
        } else {
            MailReply::create($replyData);
        }
    }
}

if (!function_exists('getInboxList')) {
    function getInboxList($userId, $search = '', $limit = 10, $offset = 0)
    {
        $search = strip_tags($search);
        $searchTerms = explode(' ', $search);

        $userQuery = MailSent::where(function ($query) use ($userId) {
            $query->whereRaw("FIND_IN_SET(?, is_recipients)", [$userId]);
        })
            ->where(function ($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_delete)", [$userId])
                    ->orWhereNull('is_delete');
            })
            ->where(function ($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_archive)", [$userId])
                    ->orWhereNull('is_archive');
            })
            ->where(function ($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_spam)", [$userId])
                    ->orWhereNull('is_spam');
            });
        if ($search) {
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if ($term) {
                    $userQuery->where(function ($q) use ($term) {
                        $q->orWhereRaw('REGEXP_REPLACE(subject, "<[^>]*>", "") LIKE ?', ['%' . $term . '%'])
                            ->orWhereRaw('REGEXP_REPLACE(message, "<[^>]*>", "") LIKE ?', ['%' . $term . '%'])
                            ->orWhereRaw('recipients LIKE ?', ['%' . $term . '%'])
                            ->orWhereRaw('cc LIKE ?', ['%' . $term . '%'])
                            ->orWhereRaw('bcc LIKE ?', ['%' . $term . '%'])
                            ->orWhereRaw('sender LIKE ?', ['%' . $term . '%']);
                    });
                }
            }
        }

        $total_records = $userQuery->count();

        $userQuery->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit);

        $inbox_emails_list = $userQuery->get();

        $allRecordsQuery = MailSent::where(function ($query) use ($userId) {
            $query->whereRaw("FIND_IN_SET(?, is_recipients)", [$userId]);
        })
            ->where(function ($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_delete)", [$userId])
                    ->orWhereNull('is_delete');
            })
            ->where(function ($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_archive)", [$userId])
                    ->orWhereNull('is_archive');
            })
            ->where(function ($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_spam)", [$userId])
                    ->orWhereNull('is_spam');
            });

        $all_inbox_emails_list = $allRecordsQuery->orderBy('created_at', 'desc')->get();

        return [
            'data' => $inbox_emails_list,
            'total_records' => $total_records,
            'inbox_list' => $all_inbox_emails_list
        ];
    }
}

if (!function_exists('getThemeById')) {
    function getThemeById($id)
    {
        $lastDigit = substr($id, -1);

        $themes = ['0' => 'info', '1' => 'danger', '2' => 'warning', '3' => '', '4' => 'success', '5' => 'info', '6' => 'danger', '7' => 'warning', '8' => '', '9' => 'success'];

        return $themes[$lastDigit] ?? '';
    }
}
if (!function_exists('getFavouriteList')) {
    function getFavouriteList($userId)
    {
        return MailSent::where(function ($query) use ($userId) {
            $query->whereRaw("FIND_IN_SET(?, is_favourites)", [$userId])
                ->orWhereRaw("FIND_IN_SET(?, is_recipients)", [$userId])
                ->orWhereRaw("FIND_IN_SET(?, is_draft)", [$userId])
                ->orWhereRaw("FIND_IN_SET(?, is_archive)", [$userId])
                ->orWhere('user_id', $userId);
        })
            ->where(function ($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_delete)", [$userId])
                    ->orWhereNull('is_delete');
            })
            ->where(function ($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_archive)", [$userId])
                    ->orWhereNull('is_archive');
            })
            ->where(function ($query) use ($userId) {
                $query->whereNull('is_spam')
                    ->orWhere('is_spam', '')
                    ->orWhereRaw("NOT FIND_IN_SET(?, is_spam)", [$userId]);
            })
            ->get();
    }
}

if (!function_exists('getSentEmailsList')) {
    function getSentEmailsList($userId)
    {
        return MailSent::where('email_type', '0')
            ->where('user_id', '=', $userId)
            ->where(function ($query) use ($userId) {
                $query->whereNull('is_delete')
                    ->orWhere('is_delete', 'NOT LIKE', '%' . $userId . '%');
            })
            ->where(function ($query) use ($userId) {
                $query->whereNull('is_archive')
                    ->orWhere('is_archive', 'NOT LIKE', '%' . $userId . '%');
            })
            ->where(function ($query) use ($userId) {
                $query->whereNull('is_spam')
                    ->orWhere('is_spam', '')
                    ->orWhereRaw("NOT FIND_IN_SET(?, is_spam)", [$userId]);
            })
            ->get();
    }
}

if (!function_exists('getTotalRecordsCount')) {
    function getTotalRecordsCount($userId)
    {
        return MailSent::where(function($query) use ($userId) {
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
            })
        ->count();
    }
}

if (!function_exists('getAllEmails')) {
    function getAllEmails($userId)
    {
        return MailSent::where(function ($query) use ($userId) {
            $query->whereRaw("FIND_IN_SET(?, is_recipients)", [$userId])
                ->orWhereRaw("FIND_IN_SET(?, is_draft)", [$userId])
                ->orWhereRaw("FIND_IN_SET(?, is_archive)", [$userId])
                ->orWhere('user_id', $userId);
        })
            ->where(function ($query) use ($userId) {
                $query->whereNull('is_delete')
                    ->orWhere('is_delete', '')
                    ->orWhereRaw("NOT FIND_IN_SET(?, is_delete)", [$userId]);
            })
            ->where(function ($query) use ($userId) {
                $query->whereNull('is_trash')
                    ->orWhere('is_trash', '')
                    ->orWhereRaw("NOT FIND_IN_SET(?, is_trash)", [$userId]);
            })
            ->where(function ($query) use ($userId) {
                $query->whereNull('is_spam')
                    ->orWhere('is_spam', '')
                    ->orWhereRaw("NOT FIND_IN_SET(?, is_spam)", [$userId]);
            })
            ->get();
    }
}

if (!function_exists('getDraftEmailsList')) {
    function getDraftEmailsList($userId)
    {
        return MailSent::where('email_type', '1')
            ->where(function ($query) use ($userId) {
                $query->whereRaw("FIND_IN_SET(?, is_favourites)", [$userId])
                    ->orWhere('user_id', $userId);
            })
            ->where(function ($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_delete)", [$userId])
                    ->orWhereNull('is_delete');
            })
            ->where(function ($query) use ($userId) {
                $query->whereRaw("NOT FIND_IN_SET(?, is_archive)", [$userId])
                    ->orWhereNull('is_archive');
            })
            ->get();
    }
}

if (!function_exists('getLogUserData')) {
    function getLogUserData($userId)
    {
        return DB::table('users')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->where('users.id', $userId)
            ->select('users.username', 'users.email', 'user_profiles.profile_image_path','users.first_name', 'users.last_name')
            ->first();
    }
}
if (!function_exists('getUserData')) {
    function getUserData($username)
    {
        return DB::table('users')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->where('users.username', $username)
            ->select('users.username', 'users.email', 'user_profiles.profile_image_path','users.first_name', 'users.last_name')
            ->first();
    }
}

if (!function_exists('getRecipientData')) {
    function getRecipientData($emails)
    {
        if (empty($emails)) {
            return [];
        }

        if (is_string($emails)) {
            $emails = explode(',', $emails);
        }

        $results = [];

        $silocloudEmails = [];
        foreach ($emails as $email) {
            if (str_contains($email, '@silocloud.io')) {
                $username = explode('@', $email)[0];
                $silocloudEmails[] = $username;
            }
        }

        $existingUsers = [];
        if (!empty($silocloudEmails)) {
            $existingUsers = DB::table('users')
                ->whereIn('username', $silocloudEmails)
                ->select('id', 'username', 'email','first_name','last_name')
                ->get()
                ->keyBy('username');
        }

        foreach ($emails as $email) {
            if (str_contains($email, '@silocloud.io')) {
                $username = explode('@', $email)[0];
                if (isset($existingUsers[$username])) {
                    $results[] = (object)[
                        'id' => $existingUsers[$username]->id,
                        'username' => $existingUsers[$username]->username,
                        'email' => $email,
                        'first_name' => $existingUsers[$username]->first_name,
                        'last_name' => $existingUsers[$username]->last_name,
                        'profile_image_path' => null
                    ];
                } else {
                    $results[] = (object)[
                        'id' => null,
                        'username' => null,
                        'email' => $email,
                        'profile_image_path' => null
                    ];
                }
            } else {
                $results[] = (object)[
                    'id' => null,
                    'username' => null,
                    'email' => $email,
                    'profile_image_path' => null
                ];
            }
        }

        return $results;
    }
}

if (!function_exists('isSent')) {
    function isSent($userId, $messageId)
    {
        return MailSent::whereRaw("FIND_IN_SET(?, user_id)", [$userId])
            ->where('id', $messageId)
            ->exists();
    }
}

if (!function_exists('isFavourite')) {
    function isFavourite($userId, $messageId)
    {
        return MailSent::whereRaw("FIND_IN_SET(?, is_favourites)", [$userId])
            ->where('id', $messageId)
            ->exists();
    }
}

if (!function_exists('isArchive')) {
    function isArchive($userId, $messageId)
    {
        return MailSent::whereRaw("FIND_IN_SET(?, is_archive)", [$userId])
            ->where('id', $messageId)
            ->exists();
    }
}
if (!function_exists('isDraft')) {
    function isDraft($userId, $messageId)
    {
        return MailSent::whereRaw("FIND_IN_SET(?, is_draft)", [$userId])
            ->where('id', $messageId)
            ->exists();
    }
}

if (!function_exists('isRead')) {
    function isRead($userId, $messageId)
    {
        return MailSent::whereRaw("FIND_IN_SET(?, is_read)", [$userId])
            ->where('id', $messageId)
            ->exists();
    }
}
if (!function_exists('isSpam')) {
    function isSpam($userId, $messageId)
    {
        return MailSent::whereRaw("FIND_IN_SET(?, is_spam)", [$userId])
            ->where('id', $messageId)
            ->exists();
    }
}
if (!function_exists('isDelete')) {
    function isDelete($userId, $messageId)
    {
        return MailSent::whereRaw("FIND_IN_SET(?, is_delete)", [$userId])
            ->where('id', $messageId)
            ->exists();
    }
}
if (!function_exists('getSpamList')) {
    function getSpamList($userId)
    {
        $cutoffDate = now()->subDays(30)->format('Y-m-d H:i:s');

        return DB::table('sent_mails')
            ->whereRaw('FIND_IN_SET(?, is_spam)', [$userId])
            ->whereRaw('(FIND_IN_SET(?, is_delete) = 0 OR is_delete IS NULL)', [$userId])
            ->whereRaw('(FIND_IN_SET(?, is_trash) = 0 OR is_trash IS NULL)', [$userId])
            ->where(function ($query) use ($userId, $cutoffDate) {
                $query->whereRaw("JSON_CONTAINS(deleted_at, JSON_OBJECT('id', ?))", [$userId])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(deleted_at, '$[0].date')) >= ?", [$cutoffDate]);
            })
            ->get();
    }
}

if (!function_exists('getFormattedRecipients')) {
    function getFormattedRecipients($emails)
    {
        $recipientData = getRecipientData($emails);

        $recipients = [];
        foreach ($recipientData as $recipient) {

            $recipients[] = [
                'name' => $recipient->username ?? '',
                'firstname' => $recipient->first_name ?? '',
                'lastname' => $recipient->last_name ?? '',
                'mail' => $recipient->email,
                'theme' => getThemeById($recipient->id),
            ];
        }
        return $recipients;
    }
}

if (!function_exists('getReplyData')) {
    function getReplyData($messageId, $userId)
    {
        $replies = MailReply::where('mail_id', $messageId)->get();

        $replyData = [];

        foreach ($replies as $reply) {
            $replyCreatedAtUTC = \Carbon\Carbon::parse($reply->created_at)->setTimezone('UTC');
            $logUser = getLogUserData($userId);
            $logEmail = $logUser->username . '@silocloud.io';
            $replyFromName = explode('@', $reply->from)[0];
            $userData = getUserData($replyFromName);

            $toRecipients = getFormattedRecipients($reply->recipients);
            $ccRecipients = getFormattedRecipients($reply->cc);
            $bccRecipients = getFormattedRecipients($reply->bcc);

            $isRecipient = MailReply::where(function ($query) use ($userId, $reply) {
                $query->whereRaw("FIND_IN_SET(?, is_recipients)", [$userId])
                    ->where('id', $reply->id);
            })->exists();
            $isDelete = MailReply::where(function ($query) use ($userId, $reply) {
                $query->whereRaw("FIND_IN_SET(?, is_delete)", [$userId])
                    ->where('id', $reply->id);
            })->exists();
            $isTrash = MailReply::where(function ($query) use ($userId, $reply) {
                $query->whereRaw("FIND_IN_SET(?, is_trash)", [$userId])
                    ->where('id', $reply->id);
            })->exists();

            $toSection = [
                'recipient' => $toRecipients,
                'cc' => $ccRecipients,
            ];

            $showBcc = false;
            $bccData = [];

            $isLogUserSender = ($logEmail === $reply->from);
            $isBccEmailMatch = false;

            foreach ($bccRecipients as $bccRecipient) {
                if ($bccRecipient['mail'] === $logEmail) {
                    $isBccEmailMatch = true;
                    $bccData = [$bccRecipient];
                    break;
                }
            }

            if ($isLogUserSender) {
                $showBcc = true;
                $bccData = $bccRecipients;
            }

            if ($showBcc || $isBccEmailMatch) {
                $toSection['bcc'] = $bccData;
            }


            $attachmentData = [];
            if (!empty($reply->attachment)) {
                $attachments = json_decode($reply->attachment, true);
                if (is_array($attachments)) {
                    foreach ($attachments as $attachment) {
                        $attachmentData[] = [
                            'name' => $attachment['fileName'],
                            'size' => $attachment['size'],
                            'shortpath' => $attachment['path'],
                            'path' => getFileTemporaryURL($attachment['path']),
                        ];
                    }
                }
            }

            $replyData[] = [
                'replyId' => $reply->id,
                'userId' => $reply->user_id ?? null,
                'name' => $userData->username ?? '',
                'firstname' => $userData->first_name ?? '',
                'lastname' => $userData->last_name ?? '',
                'mail' => $reply->from ?? '',
                'profile_image_path' => isset($userData) && $userData->profile_image_path ? getFileTemporaryURL($userData->profile_image_path) : null,
                'utctime' => $reply->updated_at,
                'theme' => getThemeById($reply->user_id),
                'to' => $toSection,
                'attachment' => $attachmentData,
                'replyOf' => $reply->reply_id ?? null,
                'isRecipient' => $isRecipient,
                'isDelete' => $isDelete,
                'isTrash' => $isTrash,
                'date' => $replyCreatedAtUTC->format('d M, Y'),
                'time' => $replyCreatedAtUTC->format('h:i A'),
                'replyMessage' => $reply->message ?? "",
            ];
        }

        return $replyData;
    }
}
if (!function_exists('updateEmailColumn')) {
    function updateEmailColumn($email_id, $user_id, $column)
    {
        $exist_email = MailSent::where('id', $email_id)->first();
        if (!$exist_email) {
            return ['error' => true, 'message' => "Email ID {$email_id} data not found"];
        }

        $currentValues = $exist_email->{$column};
        $currentValuesArray = $currentValues ? explode(',', $currentValues) : [];

        if (($key = array_search($user_id, $currentValuesArray)) !== false) {
            unset($currentValuesArray[$key]);
            $updatedValues = implode(',', $currentValuesArray);

            $updated = MailSent::where('id', $email_id)->update([$column => $updatedValues]);
            if (!$updated) {
                return ['error' => true, 'message' => "Failed to update {$column} for Email ID {$email_id}"];
            }
        }

        return ['error' => false, 'message' => "{$column} updated successfully for Email ID {$email_id}"];
    }
}
if (!function_exists('processEmails')) {
    function processEmails($emails)
    {
        $silocloudEmails = [];
        $otherEmails = [];

        foreach ($emails as $email) {
            if (str_contains($email, '@silocloud.io')) {
                $username = explode('@', $email)[0];
                $silocloudEmails[] = $username;
            } else {
                $otherEmails[] = $email;
            }
        }

        // Get the actual emails for silocloud.io usernames
        $silocloudUserEmails = User::whereIn('username', $silocloudEmails)->pluck('email')->toArray();

        return [
            'silocloudUserEmails' => $silocloudUserEmails,
            'otherEmails' => $otherEmails,
        ];
    }
}

if (!function_exists('calculateSpamScore')) {
    function calculateSpamScore($messageContent, $userId, $mailId, $isRecipients)
    {
        $spamKeywords = [
            "congratulations you've won",
            "click here",
            "urgent action required",
            "free money",
            "limited time offer",
            "risk-free",
            "100% guaranteed",
            "you have been selected",
            "this is not a scam",
            "unclaimed reward",
            "act now",
            "call now",
            "get rich quick",
            "investment opportunity",
            "increase your income",
            "lowest price",
            "no hidden fees",
            "work from home",
            "earn money fast",
            "credit card required",
            "investment",
            "income",
            "loans",
            "mortgage rates",
            "debt relief",
            "earn cash",
            "cash bonus",
            "easy money",
            "financial freedom",
            "double your income",
            "cure cancer",
            "lose weight fast",
            "miracle cure",
            "no prescription needed",
            "free trial",
            "eliminate wrinkles",
            "reverse aging",
            "enhance performance",
            "adult",
            "xxx",
            "porn",
            "viagra",
            "cialis",
            "erectile dysfunction",
            "sexual health",
            "verify your account",
            "account suspended",
            "login required",
            "password reset",
            "update payment information",
            "security alert",
            "free gift",
            "free trial",
            "no catch",
            "guaranteed",
            "special promotion",
            "risk-free",
            "exclusive deal",
            "no cost",
            "act now",
            "immediate action required",
            "time-sensitive",
            "limited time only",
            "last chance",
            "don't miss out",
            "0% risk",
            "100% free",
            "unlimited access",
            "fast cash",
            "be your own boss",
            "virus detected",
            "security breach",
            "contact support",
            "urgent system update",
            "cheap",
            "affordable",
            "discount",
            "winner",
            "bonus",
            "special offer",
            "cash prize",
            "pre-approved"
        ];

        $keywordMatches = 0;
        foreach ($spamKeywords as $keyword) {
            if (Str::contains(Str::lower($messageContent), Str::lower($keyword))) {
                $keywordMatches++;
            }
        }
        if ($keywordMatches >= 2) {
            $newSpamScore = floor($keywordMatches / 2);

            $spamLog = SpamEmailLog::where('user_id', $userId)->first();

            if ($spamLog) {
                $currentDate = now()->format('Y-m-d');
                $lastUpdatedDate = $spamLog->updated_at->format('Y-m-d');

                if ($currentDate > $lastUpdatedDate) {
                    $decreasedSpamScore = max(0, $spamLog->spam_score - 10);
                    $updatedSpamScore = $decreasedSpamScore + $newSpamScore;
                } else {
                    $updatedSpamScore = $spamLog->spam_score + $newSpamScore;
                }
                $existingMailIds = explode(',', $spamLog->mail_id);
                if (!in_array($mailId, $existingMailIds)) {
                    $updatedMailIds = $spamLog->mail_id . ',' . $mailId;
                } else {
                    $updatedMailIds = $spamLog->mail_id;
                }

                $existingRecipients = $spamLog->recipients ? explode(',', $spamLog->recipients) : [];
                $newRecipients = explode(',', $isRecipients);
                $mergedRecipients = array_unique(array_merge($existingRecipients, $newRecipients));

                $spamLog->update([
                    'spam_score' => $updatedSpamScore,
                    'recipients' => implode(',', $mergedRecipients),
                    'mail_id' => $updatedMailIds,
                ]);
            } else {
                SpamEmailLog::create([
                    'user_id' => $userId,
                    'spam_score' => $newSpamScore,
                    'recipients' => $isRecipients,
                    'mail_id' => $mailId,
                ]);
            }
        }
    }
}
if (!function_exists('handleSnippet')) {
    function handleSnippet($snippet, $name, $type, $userId, $id = null)
    {
        $quickResponse = $id ? MailSnippets::where('user_id', $userId)->where('id', $id)->first() : new MailSnippets();

        if (!$quickResponse && $id) {
            return ['success' => false, 'message' => 'Quick response not found'];
        }

        $quickResponse->type = $type;
        $quickResponse->user_id = $userId;
        $quickResponse->name = $name;

        if ($type === '0') {
            $quickResponse->quick_response = $snippet;
            $quickResponse->signature = null;
            $message = 'Quick response saved successfully';
        } elseif ($type === '1') {
            $quickResponse->signature = $snippet;
            $quickResponse->quick_response = null;
            $message = 'Signature saved successfully';
        }

        $quickResponse->save();

        return [
            'success' => true,
            'message' => $message
        ];
    }
}
if (!function_exists('getTagsFromLabels')) {
    function getTagsFromLabels($isLabelJson, $userId)
    {
        $labelData = json_decode($isLabelJson, true);
        $tags = [];

        if (!is_array($labelData)) {
            return $tags;
        }

        foreach ($labelData as $labelEntry) {
            if (isset($labelEntry['uid']) && $labelEntry['uid'] == $userId && isset($labelEntry['label_id']) && is_array($labelEntry['label_id'])) {
                $labelIds = array_unique($labelEntry['label_id']);

                $labels = DB::table('email_labels')->whereIn('id', $labelIds)->get();
                foreach ($labels as $label) {
                    $tags[] = [
                        'id' => $label->id,
                        'labels' => $label->labels,
                        'theme' => $label->theme,
                    ];
                }
                break;
            }
        }

        return $tags;
    }
}
if (!function_exists('checkDeletedMail')) {
    function checkDeletedMail($mail, $userId)
    {
        $data = json_decode($mail, true);
        
        $deletedAtData = json_decode($data['deleted_at'], true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($deletedAtData)) {
            foreach ($deletedAtData as $deletedRecord) {
                if (isset($deletedRecord['id']) && $deletedRecord['id'] == $userId) {
                    $deletedDate = Carbon::parse($deletedRecord['date']);
                    $currentDate = Carbon::now();
                    
                    if ($deletedDate->diffInDays($currentDate) <= 30) {
                        return $mail; 
                    } else {
                        // MailSent::where('id', $data['id'])->update(['is_trash' => $userId]);
                        $mailSent = MailSent::find($data['id']);
                        if ($mailSent) {
                            $existingTrash = $mailSent->is_trash;

                            $trashArray = !empty($existingTrash) ? explode(',', $existingTrash) : [];

                            if (!in_array($userId, $trashArray)) {
                                $trashArray[] = $userId; 
                            }

                            $updatedTrash = implode(',', $trashArray);

                            $mailSent->update(['is_trash' => $updatedTrash]);
                        }
                    }
                }
            }
        } else {
            Log::error('JSON Decode Error for mail ID ' . $data['id'] . ': ' . json_last_error_msg());
        }
        return null; 
    }
}
if (!function_exists('searchMessages')) {
    function searchMessages($searchTerm,$userQuery)
    {
        if (!empty($searchTerm)) {
            $searchTerms = explode(' ', strip_tags($searchTerm));
            $userQuery->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhereRaw('REGEXP_REPLACE(subject, "<[^>]*>", "") LIKE ?', ['%' . $term . '%'])
                      ->orWhereRaw('REGEXP_REPLACE(message, "<[^>]*>", "") LIKE ?', ['%' . $term . '%'])
                      ->orWhereRaw('sender LIKE ?', ['%' . $term . '%'])
                      ->orWhereRaw('recipients LIKE ?', ['%' . $term . '%'])
                      ->orWhereRaw('cc LIKE ?', ['%' . $term . '%'])
                      ->orWhereRaw('bcc LIKE ?', ['%' . $term . '%']);
                }
            });
        }
        return $userQuery->get();
    }
}











