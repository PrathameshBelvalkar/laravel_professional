<?php

namespace App\Http\Controllers\API\V1\support;

use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\Support\AssignTechUserRequest;
use App\Http\Requests\Support\DownloadTicketAttachment;
use App\Http\Requests\Support\GetQuestionRequest;
use App\Http\Requests\Support\GetTechUsersRequest;
use App\Http\Requests\Support\GetTicket;
use App\Http\Requests\Support\GetTicketListRequest;
use App\Http\Requests\Support\GetTicketSummaryRequest;
use App\Http\Requests\Support\ReplyTicketRequest;
use App\Http\Requests\Support\TicketStatusRequest;
use App\Models\Country;
use App\Models\Support\Reply;
use App\Models\SupportQuestion;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportCategory;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Support\AddTicketRequest;
use Illuminate\Support\Str;


class SupportController extends Controller
{
    public function getCategories(Request $request)
    {
        DB::beginTransaction();
        try {
            $support_categories = SupportCategory::selectRaw("id,description,title,category_key,tags")->get();
            if ($support_categories->isNotEmpty()) {
                $temp_support_categories = $support_categories->toArray();
                $categories = [];
                foreach ($temp_support_categories as $row) {
                    $row['tags'] = json_decode($row['tags'], true);
                    $categories[] = $row;
                }

                return generateResponse([
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Support Categories retrieved successfully',
                    'toast' => true,
                    'data' => ["support_categories" => $categories]
                ]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No categories found', 'toast' => true]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error while retrieving support categories: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while retrieving support categories', 'toast' => true]);
        }
    }
    public function getQuestions(GetQuestionRequest $request)
    {
        try {
            $categoryId = $request->category_id;
            $query = SupportQuestion::query()->where('category_id', $categoryId);
            if (isset($request->search)) {
                $searchTerm = $request->input('search');
                $query->where('title', 'like', "%$searchTerm%");
            }
            $page = 1;
            $limit = 10;
            if (isset($request->page)) {
                $page = $request->page;
            }
            if (isset($request->limit)) {
                $limit = $request->limit;
            }
            $offset = $page > 1 ? ($page - 1) * $limit : 0;
            $question = $query->offset($offset)->limit($limit)->selectRaw('id,title,answer,slug,category_id')->get();

            DB::commit();
            if ($question->isEmpty()) {
                return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'No questions found', 'toast' => true]);
            } else {
                return generateResponse([
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Questions retrieved successfully',
                    'toast' => true,
                    'data' => ["questions" => $question->toArray()]
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error while retrieving questions: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while retrieving titles', 'toast' => true]);
        }
    }

    public function addticket(AddTicketRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $category_id = $request->category_id;

            $query = new SupportTicket();

            $userFolder = "users/private/{$user->id}/support/ticket";
            Storage::makeDirectory($userFolder);

            if ($request->hasFile('file_upload')) {
                $attachments = [];
                $i = 1;
                $tmpfileName = $user->id;
                foreach ($request->file('file_upload') as $file) {
                    if ($file->isValid()) {
                        $attachmentName = $tmpfileName . "-" . $i++ . time() . '.' . $file->extension();
                        $attachmentPath = $file->storeAs($userFolder, $attachmentName);
                        $attachments[] = $attachmentPath; // Add path to attachments array
                    }
                }
                if (count($attachments) > 0) {
                    $query->file_upload = json_encode($attachments); // Store as JSON array
                }
            }
            $query->user_id = $user->id;
            $query->ticket_unique_id = $this->generateUniqueID();
            $query->title = $request->title;
            $query->category_id = $category_id;
            $query->description = isset($request->description) ? $request->description : null;
            $query->tags = isset($request->tags) ? $request->tags : null;
            $query->save();
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Ticket submitted successfully', 'toast' => true], ["ticket_id" => $query->id]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error while retrieving questions: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    function generateUniqueID()
    {
        $id = Str::random(7);
        while (SupportTicket::where('ticket_unique_id', $id)->exists()) {
            $id = Str::random(7);
        }
        return $id;
    }
    public function replyToTicket(ReplyTicketRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $ticket_id = $request->ticket_id;
            $chat_type = $request->chat_type;
            $ticketreply = $request->reply;
            $ticket = SupportTicket::where("ticket_unique_id", $ticket_id)->first();

            if ($ticket && $ticket->toArray()) {
                $tech_user_list = $ticket->tech_users ? json_decode($ticket->tech_users, true) : [];
                if (array_key_exists($user->id, $tech_user_list) || $user->id == $ticket->user_id || $user->role_id == "6") {
                    $reply = new Reply();
                    $userFolder = "users/private/{$user->id}/support/ticket";
                    Storage::makeDirectory($userFolder);

                    if ($request->hasFile('file_upload')) {
                        $attachments = [];
                        $i = 1;
                        $tmpfileName = $user->id;
                        foreach ($request->file('file_upload') as $file) {
                            if ($file->isValid()) {
                                $attachmentName = $tmpfileName . "-" . $i++ . time() . '.' . $file->extension();
                                $attachmentPath = $file->storeAs($userFolder, $attachmentName);
                                $attachments[] = $attachmentPath; // Add path to attachments array
                            }
                        }
                        if (count($attachments) > 0) {
                            $reply->file_upload = json_encode($attachments); // Store as JSON array
                        }
                    }
                    $reply->ticket_id = $ticket->id;
                    $reply->user_id = $user->id;
                    $reply->chat_type = $chat_type;
                    $reply->reply = json_encode($ticketreply);
                    $reply->save();
                    $ticket->last_reply_id = $reply->id;
                    $ticket->save();

                    if ($user->role_id == "2" && $chat_type == "1") {
                        addNotification($ticket->user_id, $user->id, "Reply to ticket '" . $ticket->title . "'", "Admin has replied to ticket,view for more details", $ticket->id, "3", "/ticket/" . $ticket_id);
                        $link = config("app.account_url") . "ticket/" . $ticket_id;
                        $message = "Admin has replied to ticket,view for more details";
                        $this->sendSupportMail($ticket->user_id, $message, "Reply to ticket '" . $ticket->title . "'", $link);
                    }
                    DB::commit();
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Reply added to ticket', 'toast' => true]);
                } else {
                    return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Unauthorized access to ticket', 'toast' => true]);
                }
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Ticket not found', 'toast' => true]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error while replying to ticket: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'An error occurred: ' . $e->getMessage(), 'toast' => true]);
        }
    }
    public function changeTicketStatus(TicketStatusRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $ticket_id = $request->ticket_id;
            $status = $request->status;
            $type = $request->type;
            $ticket = SupportTicket::where("ticket_unique_id", $ticket_id)->first();
            if ($ticket && $ticket->toArray()) {
                $tech_user_list = $ticket->tech_users ? json_decode($ticket->tech_users, true) : [];
                if (array_key_exists($user->id, $tech_user_list) || $user->id == $ticket->user_id || $user->role_id == "6") {
                    $ticket->$type = $status == "1" ? "1" : "0";
                    $ticket->save();

                    addNotification($ticket->user_id, $user->id, "Ticket - " . $ticket->title . " status changed", "View ticket for more details", $ticket->id, "3", "/ticket/" . $ticket_id);
                    $link = config("app.account_url") . "ticket/" . $ticket_id;
                    $message = "Status of your ticket has changed, view for more details";
                    $this->sendSupportMail($ticket->user_id, $message, "Status ticket '" . $ticket->title . "' changed", $link);

                    DB::commit();
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Ticket status changed', 'toast' => true]);
                } else {
                    return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Unauthorized access to ticket', 'toast' => true]);
                }
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Ticket not found', 'toast' => true]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error while replying to ticket: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'An error occurred: ' . $e->getMessage(), 'toast' => true]);
        }
    }
    public function getTicketList(GetTicketListRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $limit = isset($request->limit) ? $request->limit : 10;
            $offset = isset($request->offset) ? $request->offset : 0;
            $status = isset($request->status) ? $request->status : null;
            $search = isset($request->search) ? $request->search : null;
            $is_stared = isset($request->is_stared) ? $request->is_stared : null;

            $tempList = $this->getTicketByUserRole($request, $user);
            $query = SupportTicket::query();
            if ($user->role_id != "6") {
                $query->whereIn('id', $tempList);
            }
            if (isset($request->status)) {
                $query->where('status', $request->status);
            }
            if (isset($request->is_stared)) {
                $query->where('is_stared', $request->is_stared);
            }
            if ($search) {
                $query->where('title', 'like', "%$search%")->orWhere('ticket_unique_id', 'like', "%$search%");
            }
            $tickets = $query->orderBy("updated_at", "desc")->offset($offset)->limit($limit)->get();
            DB::commit();
            if ($tickets->isEmpty()) {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No tickets found', 'toast' => true]);
            } else {
                $ticketlist = [];
                $tempTicketlist = $tickets->toArray();
                foreach ($tempTicketlist as $row) {
                    unset($row['file_upload']);
                    if (!$row['description']) {
                        $row['description'] = null;
                    }
                    if (!$row['tags']) {
                        $row['tags'] = null;
                    }
                    $row['category'] = SupportCategory::find($row['category_id'])->toArray();
                    $ticketuser = User::where('id', $row['user_id'])->first();
                    if ($ticketuser) {
                        $ticketuser = $ticketuser->toArray();
                        $row['user'] = array("username" => $ticketuser['username']);
                    } else {
                        $row['user'] = array("username" => null);
                    }
                    $ticket_tech_users = $row['tech_users'] ? json_decode($row['tech_users'], true) : [];
                    $row['tech_user'] = null;
                    if ($ticket_tech_users) {
                        $temp_tech_users = User::whereIn('id', array_keys($ticket_tech_users))->select("username")->get();
                        if ($temp_tech_users && $temp_tech_users->toArray()) {
                            $row['tech_user'] = $temp_tech_users->toArray();
                        }
                    }
                    $ticket = SupportTicket::where("id", $row['id'])->first();
                    $row['replies'] = $this->getReplies($ticket, $user);
                    unset($row['tech_users']);
                    $ticketlist[] = $row;
                }
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Support tickets retrieved successfully', 'toast' => true, 'data' => ["tickets" => $ticketlist, 'list' => $tempList]]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error while retrieving support ticket list: ' . $e->getLine() . " " . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while retrieving support tickets', 'toast' => true]);
        }
    }
    protected function getReplies($ticket, $user)
    {
        $replies = $ticket->replies;
        $ticket = $ticket->toArray();
        unset($ticket['replies']);
        $reply_array = [];
        foreach ($replies as $row) {
            $row['user'] = $row->user;
            if ($row->chat_type == "1") {
                $reply_array[] = $row->toArray();
            } else if ($row->chat_type == "2") {
                if ($user->role_id == "2" || $user->role_id == "1" || $user->role_id == "6")
                    $reply_array[] = $row->toArray();
            }
        }
        return $reply_array;
    }
    public function getTicket(GetTicket $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $ticket_id = $request->ticket_id;
            $ticket = SupportTicket::where("id", $ticket_id)->orWhere("ticket_unique_id", $ticket_id)->first();
            if ($ticket && $ticket->toArray()) {
                $tech_user_list = $ticket->tech_users ? json_decode($ticket->tech_users, true) : [];
                if (array_key_exists($user->id, $tech_user_list) || $user->id == $ticket->user_id || $user->role_id == "6") {
                    $ticket_user = User::where("id", $ticket->user_id)->first();

                    $replies = $this->getReplies($ticket, $user);
                    $ticket = $ticket->toArray();
                    $ticket['category'] = SupportCategory::find($ticket['category_id'])->toArray();
                    $ticket['user'] = array();
                    if ($ticket_user) {
                        $ticket['user'] = array("username" => $ticket_user->username);
                    }
                    unset($ticket['replies']);
                    DB::commit();
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Ticket retrieved', 'toast' => true], ['ticket' => $ticket, "replies" => $replies]);
                } else {
                    return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Unauthorized access to ticket', 'toast' => true]);
                }
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Ticket not found', 'toast' => true]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error while getting to ticket: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'An error occurred: ' . $e->getMessage(), 'toast' => true]);
        }
    }
    public function downloadAttachment(DownloadTicketAttachment $request)
    {
        try {
            $user = $request->attributes->get('user');
            $file_path = $request->file_path;
            $db_id = $request->db_id;
            $type = $request->type;
            if ($type == 'ticket')
                $ticketData = SupportTicket::where('id', $db_id)->first();
            else
                $ticketData = Reply::where('id', $db_id)->first();
            if ($ticketData && $ticketData->toArray()) {
                $ticketData = $ticketData->toArray();
                $files = json_decode($ticketData['file_upload'], true);
                if ($files && in_array($file_path, $files)) {
                    if ($user->role_id == "2" || $user->id == $ticketData['user_id']) {
                        $filePath = storage_path('app/' . $file_path);
                        if (!file_exists($filePath)) {
                            return new JsonResponse([
                                'code' => 404,
                                'message' => 'No file found on server'
                            ], 404);
                        } else {
                            ob_clean();
                            $file = explode('/', $filePath);
                            return Response::download($filePath, $file[count($file) - 1]);
                        }
                    } else {
                        return new JsonResponse([
                            'code' => 404,
                            'message' => 'Invalid Access'
                        ], 404);
                    }
                } else {
                    return new JsonResponse([
                        'code' => 404,
                        'message' => 'No file found in data'
                    ], 404);
                }
            } else {
                return new JsonResponse([
                    'code' => 404,
                    'message' => 'No data found'
                ], 404);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error while downloading file: ' . $e->getMessage());
            return new JsonResponse([
                'code' => 404,
                'message' => 'Something went wrong ' . $e->getMessage()
            ], 404);

        }
    }
    public function getTicketSummary(GetTicketSummaryRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $duration_type = $request->duration_type;
            $duration = $request->duration;
            $query = SupportTicket::query();

            if ($user->role_id != "1") {
                $tempList = $this->getTicketByUserRole($request, $user);
                $query->whereIn('id', $tempList);
            }
            $currentTime = Carbon::now();
            $now = Carbon::now();
            $start_date = match ($duration_type) {
                'd' => $now->subDays($duration),
                'm' => $now->subMonths($duration),
                'y' => $now->subYears($duration),
                default => throw new Exception('Invalid duration type.'),
            };

            $query->whereBetween('created_at', [$start_date, $currentTime]);
            $tickets = $query->get();

            $data = [];
            $data['closed_tickets'] = 0;
            $data['starred_tickets'] = 0;
            $data['active_tickets'] = 0;
            $data['all_tickets'] = 0;
            $data['country'] = [];
            $data['top_countries'] = $temp_top_countries = [];
            if (!$tickets->isEmpty()) {
                $tempTicketlist = $tickets->toArray();
                foreach ($tempTicketlist as $row) {
                    if ($row['status'] == "0") {
                        $data['active_tickets'] = $data['active_tickets'] + 1;
                    } else if ($row['status'] == "1") {
                        $data['closed_tickets'] = $data['closed_tickets'] + 1;
                    }
                    if ($row['is_stared'] == "1") {
                        $data['starred_tickets'] = $data['starred_tickets'] + 1;
                    }
                    $data['all_tickets'] = $data['all_tickets'] + 1;

                    $user_profile = UserProfile::where('user_id', $row['user_id'])->first();
                    if ($user_profile && $user_profile->country && is_numeric($user_profile->country)) {
                        $userCountry = Country::where('id', $user_profile->country)->first();
                        if ($userCountry) {
                            $userCountry->shortname = strtolower($userCountry->shortname);
                            $data['country'][$userCountry->shortname] = isset($data['country'][$userCountry->shortname]) ? $data['country'][$userCountry->shortname] + 1 : 1;
                        }
                    } else {
                        $data['country']["other"] = isset($data['country']["other"]) && is_numeric($data['country']["other"]) ? $data['country']["other"]++ : 1;
                    }
                }
            }
            $countryList = Country::get();
            if (!$countryList->isEmpty()) {
                $countryList = $countryList->toArray();

                foreach ($countryList as $tempCountry) {
                    if (!array_key_exists(strtolower($tempCountry['shortname']), $data['country'])) {
                        // $data['country'][strtolower($tempCountry['shortname'])] = 0;
                    }
                }
            }
            if (count($data['country']) > 0) {
                arsort($data['country']);
                $temp_top_countries = array_slice($data['country'], 0, 4);
            }
            if ($temp_top_countries) {
                foreach ($temp_top_countries as $shortname => $value) {
                    $tempC = Country::where("shortname", $shortname)->first();
                    if ($tempC) {
                        array_push($data['top_countries'], array($tempC->name => $value));
                    } else {
                        array_push($data['top_countries'], array($shortname => $value));
                    }
                }
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Support tickets summary retrieved successfully', 'toast' => true, 'data' => ["tickets" => $data]]);
        } catch (Exception $e) {
            Log::error('Error while getting ticket summary: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'An error occurred: ' . $e->getMessage(), 'toast' => true]);
        }
    }
    public function getTechUsers(GetTechUsersRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $limit = isset($request->limit) ? $request->limit : 10;
            $offset = isset($request->offset) ? $request->offset : 0;
            $search = isset($request->search) ? $request->search : null;
            $assignUser = isset($request->assignUser) ? $request->assignUser : false;
            $ticket_id = $request->ticket_id;
            $ticket = SupportTicket::where('ticket_unique_id', $ticket_id)->first()->toArray();
            $ticket_users = $ticket['tech_users'] ? json_decode($ticket['tech_users'], true) : [];
            $userQuery = User::query();
            $userQuery->where('role_id', "2");
            if ($search) {
                $userQuery->where('username', 'like', "%$search%");
            }
            if ($assignUser) {
                $users = $userQuery->orderBy("username", "asc")->get();
            } else {
                $users = $userQuery->orderBy("username", "asc")->offset($offset)->limit($limit)->get();
            }

            if ($users->isEmpty()) {
                return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'No tech users found', 'toast' => true]);
            } else {
                $techUsers = [];
                $users = $users->toArray();
                foreach ($users as $user) {
                    $tempUser = [];
                    $tempUser['id'] = $user['id'];
                    $tempUser['username'] = $user['username'];
                    $tempUser['role_id'] = $user['role_id'];
                    $tempUser['isAssigned'] = array_key_exists($user['id'], $ticket_users);
                    $techUsers[] = $tempUser;
                }
                return generateResponse([
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Tech users retrieved successfully',
                    'toast' => true,
                    'data' => ["tech_users" => $techUsers]
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error while getting tech users: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'An error occurred: ' . $e->getMessage(), 'toast' => true]);
        }
    }
    public function assignTechUser(AssignTechUserRequest $request)
    {
        DB::beginTransaction();
        try {

            $user = $request->attributes->get('user');
            $tech_user_ids = $request->user_id;
            $ticket_id = $request->ticket_id;
            $action = $request->action;
            $ticket = SupportTicket::where('ticket_unique_id', $ticket_id)->first();
            $tech_user_list = $ticket->tech_users ? json_decode($ticket->tech_users, true) : [];

            foreach ($tech_user_ids as $tech_user_id) {

                if ($action === "assign") {
                    $notificationTitle = "You have assigned to ticket " . $ticket->title;
                    $notificationDescription = "You have assigned to ticket " . $ticket->title;
                    $tech_user_list[$tech_user_id] = [
                        'datetime' => date('Y-m-d H:i:s'),
                        'assigned_by' => $user->id,
                        'user_id' => $tech_user_id
                    ];
                } else {
                    $notificationTitle = "You have removed from ticket " . $ticket->title;
                    $notificationDescription = "You have removed from ticket " . $ticket->title;
                    unset($tech_user_list[$tech_user_id]);
                }
                addNotification($tech_user_id, $user->id, $notificationTitle, $notificationDescription, $ticket->id, "3", "/ticket/" . $ticket_id);
                $link = config("app.account_url") . "ticket/" . $ticket_id;
                $message = $notificationTitle;
                $this->sendSupportMail($tech_user_id, $message, "Tech User Assignment '" . $ticket->title . "'", $link);

            }
            $ticket->tech_users = $tech_user_list ? json_encode($tech_user_list) : null;
            $ticket->save();
            DB::commit();
            $message = $action === "assign" ? "User is assigned to ticket" : "User is removed from ticket";
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'toast' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error while getting tech users: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'An error occurred: ' . $e->getMessage(), 'toast' => true]);
        }
    }
    protected function getTicketByUserRole($request, $user)
    {
        $ticketIdList = [];
        $ownTicket = SupportTicket::where('user_id', $user->id)->get();


        if (!($ownTicket->isEmpty())) {
            $ownTicket = $ownTicket->toArray();
            foreach ($ownTicket as $ticket) {
                if (!in_array($ticket['id'], $ticketIdList)) {
                    $ticketIdList[] = $ticket['id'];
                }
            }
        }
        $assignedTickets = null;

        if ($user->role_id == "1" || $user->role_id == "2") {
            $assignedTickets = SupportTicket::whereNotNull('tech_users')->get();
        }



        if ($assignedTickets && !($assignedTickets->isEmpty())) {
            $assignedTickets = $assignedTickets->toArray();
            foreach ($assignedTickets as $ticket) {
                $techUsers = json_decode($ticket['tech_users'], true);
                if (array_key_exists($user->id, $techUsers) && !in_array($ticket['id'], $ticketIdList)) {
                    $ticketIdList[] = $ticket['id'];
                }
            }
        }
        return $ticketIdList;
    }
    protected function sendSupportMail($user_id, $message, $subject, $link)
    {
        $user = User::where('id', $user_id)->first();
        if (isset($user->email)) {
            $data['logoUrl'] = asset('assets/images/logo/logo-dark.png');
            $data['title'] = config('app.app_name') . " Support Alert";
            $data['username'] = $user->username;
            $data['message'] = $message;
            $data['subject'] = $subject;
            $data['link'] = $link;
            $data['linkTitle'] = "View";
            $data['supportMail'] = config('app.support_mail');
            $data['projectName'] = config('app.app_name');
            $data['view'] = "mail-templates.support";
            Mail::to($user->email)->send(new SendMail($data, $data['view']));
        }
    }
}