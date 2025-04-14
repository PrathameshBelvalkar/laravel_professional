<?php

namespace App\Http\Controllers\API\V1\Account;

use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;

use App\Http\Requests\Account\AcceptConnectionRequest;
use App\Http\Requests\Account\AddConnectionRequest;
use App\Http\Requests\Account\ConnectionFollowListRequest;
use App\Http\Requests\Account\ConnectionInvitationsListRequest;
use App\Http\Requests\Account\ToggleFollowRequest;
use App\Http\Requests\Connection\DeleteConnectionRequest;
use App\Http\Requests\Connection\GetInvitationsRequest;
use App\Http\Requests\Connection\IgnoreConnectionRequest;
use App\Http\Requests\Connection\RejectInvitationsRequest;
use App\Models\Account\Connection;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\GetConnectionUserRequest;
use App\Http\Requests\Account\RemoveFollowersRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class ConnectionController extends Controller
{
  public $module = "";
  public $followerColumn = "";
  public $followingColumn = "";

  public function __construct()
  {
    $this->module = request()->input('module', 'account');
    if ($this->module === 'community') {
      $this->followingColumn = 'c_following';
      $this->followerColumn = 'c_followers';
    } else {
      $this->followingColumn = 'following';
      $this->followerColumn = 'followers';
    }
  }

  public $connectionStatus = ['pending', 'accepted', 'rejected', 'deleted', 'deleted', 'blocked', 'blocked'];
  public function getUsers(GetConnectionUserRequest $request)
  {
    try {
      // collect inputs
      $user = $request->attributes->get('user');
      $limit = isset($request->limit) ? $request->limit : 10;
      $page = isset($request->page) && $request->page ? $request->page : 1;
      $search = isset($request->search) ? $request->search : "";
      $type = $request->type;
      $pagination = true;
      $offset = ($page - 1) * $limit;
      // get only specific user's data if user_id is available in inputs
      if (isset($request->user_id)) {
        if ($user->id == $request->user_id) {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Can\'t get self information', 'toast' => true]);
        }
        $row = [];
        $requested_user = User::where("id", $request->user_id)->select(['id', 'username', 'email'])->first();

        $row['user_avatar'] = null;
        if (isset($requested_user->profile->profile_image_path) && $requested_user->profile->profile_image_path) {
          $row['user_avatar'] = getFileTemporaryURL($requested_user->profile->profile_image_path);
        }
        $requested_user = $requested_user->toArray();
        $row['id'] = $requested_user['id'];
        $row['username'] = $requested_user['username'];
        $row['email'] = $requested_user['email'];
        $row['follow_status'] = getFollowStatus($user->id, $requested_user['id']);
        $row['connection_status'] = getConnectionStatus($user->id, $requested_user['id']);
        $row['mutual_connection'] = getMutualConnections($user->id, $requested_user['id'])['count'];
        $row['mutual_connection_contacts'] = [];
        if ($row['mutual_connection']) {
          $mutual_users = getMutualConnections($user->id, $requested_user['id'])['users'];
          if ($mutual_users) {
            foreach ($mutual_users as $mu) {
              $tempMu = [];
              $tempMu['user_id'] = $mu['id'];
              $tempMu['username'] = $mu['username'];
              $tempMu['email'] = $mu['email'];
              $row['mutual_connection_contacts'][] = $tempMu;
            }
          }
        }
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User data retrieved', 'toast' => true], ['user' => $row]);
      } else if ($type == "not_connected") {

        $tempUserIds = getConnectedUsers($request, $user);

        $usersQuery = User::query();
        $usersQuery->whereNot('id', $user->id);
        $usersQuery->offset($offset)->limit($limit)->orderBy("username", "asc");

        if ($search) {
          $usersQuery->where(function ($query) use ($search) {
            $query->where('email', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%");
          });
        }
        if ($tempUserIds)
          $usersQuery->whereNotIn("id", $tempUserIds);
        $users = $usersQuery->select(['id', 'username', 'email'])->with("profile")->get();


        if ($users->isEmpty()) {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No users found', 'toast' => true]);
        } else {
          $users = $users->toArray();
          $user_list = [];
          foreach ($users as $userRow) {
            $row = [];
            $row['user_id'] = $userRow['id'];
            $row['avatar_url'] = null;
            $row['cover_img'] = generateLinearGradient();
            if (isset($userRow['profile']['profile_image_path']) && $userRow['profile']['profile_image_path']) {
              $row['avatar_url'] = getFileTemporaryURL($userRow['profile']['profile_image_path']);
            }
            if (isset($userRow['profile']['cover_img']) && $userRow['profile']['cover_img']) {
              $row['cover_img'] = $userRow['profile']['cover_img'];
            }
            $row['username'] = $userRow['username'];
            $row['email'] = $userRow['email'];
            $row['follow_status'] = getFollowStatus($user->id, $userRow['id']);
            $row['connection_status'] = getConnectionStatus($user->id, $userRow['id']);
            $row['sort_status'] = getConnectionStatus($user->id, $userRow['id'])['entry'] == null ? "1" : "2";
            $row['mutual_connection'] = getMutualConnections($user->id, $userRow['id'])['count'];
            $row['user_profile'] = null;
            $user_list[] = $row;
          }
          array_multisort(array_column($user_list, 'sort_status'), SORT_ASC, $user_list);

          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User list retrieved', 'toast' => true], ['user_list' => $user_list]);
        }
      } else if ($type == "connected") {
        return $this->connectionList($request);
      } else if ($type == "followed") {
        $list = getFollowList("following", $user, $limit, $offset, $pagination, true, $search);
        if ($list) {
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Following list found', 'toast' => true], ['list' => $list]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No Following list found', 'toast' => true]);
        }
      } else if ($type == "not_followed") {
        $list = getFollowList("following", $user, $limit, $offset, $pagination, false, $search);
        if ($list) {
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Not-following list found', 'toast' => true], ['list' => $list]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Not-following list not found', 'toast' => true]);
        }
      } else if ($type == "followers") {
        $list = getFollowList("followers", $user, $limit, $offset, $pagination, true, $search);
        if ($list) {
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Followers list found', 'toast' => true], ['list' => $list]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No Followers list found', 'toast' => true]);
        }
      } else if ($type == "not_followers") {
        $list = getFollowList("followers", $user, $limit, $offset, $pagination, false, $search);
        if ($list) {
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Not-followers list found', 'toast' => true], ['list' => $list]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Not-followers list not found', 'toast' => true]);
        }
      } else {
        $usersQuery = User::query();
        $usersQuery->whereNot('id', $user->id);
        $usersQuery->offset($offset)->limit($limit)->orderBy("username", "asc");
        if ($search) {
          $usersQuery->where(function ($query) use ($search) {
            $query->where('email', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%");
          });
        }
        $requestedUsers = $usersQuery->select(['id', 'username', 'email'])->with('profile')->get();
        if ($requestedUsers->isNotEmpty()) {
          $list = [];
          $requestedUsers = $requestedUsers->toArray();
          foreach ($requestedUsers as $tempUsers) {
            $row = [];
            $row['user_avatar'] = null;
            if (isset($tempUsers['profile']['profile_image_path']) && $tempUsers['profile']['profile_image_path']) {
              $row['user_avatar'] = getFileTemporaryURL($tempUsers['profile']['profile_image_path']);
            }
            $row['id'] = $tempUsers['id'];
            $row['username'] = $tempUsers['username'];
            $row['email'] = $tempUsers['email'];
            $row['follow_status'] = getFollowStatus($user->id, $tempUsers['id']);
            $row['connection_status'] = getConnectionStatus($user->id, $tempUsers['id']);
            $row['mutual_connection'] = getMutualConnections($user->id, $tempUsers['id'])['count'];
            $row['mutual_connection_contacts'] = [];
            if ($row['mutual_connection']) {
              $mutual_users = getMutualConnections($user->id, $tempUsers['id'])['users'];
              if ($mutual_users) {
                foreach ($mutual_users as $mu) {
                  $tempMu = [];
                  $tempMu['user_id'] = $mu['id'];
                  $tempMu['username'] = $mu['username'];
                  $tempMu['email'] = $mu['email'];
                  $row['mutual_connection_contacts'][] = $tempMu;
                }
              }
            }
            $list[] = $row;
          }
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User data retrieved', 'toast' => true], ['list' => $list]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No users found', 'toast' => true]);
        }
      }
    } catch (\Exception $e) {
      Log::info('ConnectionController getUsers Error : ' . $e->getMessage() . " " . $e->getLine() . " " . $e->getFile());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  // public function toggleFollow(ToggleFollowRequest $request)
  // {
  //   try {
  //     DB::beginTransaction();
  //     $user = $request->attributes->get('user');
  //     $status = $request->status;
  //     $type = $request->type;
  //     $requestUserId = isset($request->user_id) ? $request->user_id : null;

  //     if ($type == "all" && $status == "1") {
  //       return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'You only unfollow to all', 'toast' => true]);
  //     }

  //     if ($requestUserId && $user->id == $requestUserId) {
  //       return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Can\'t self follow', 'toast' => true]);
  //     }

  //     $loggedUserProfile = UserProfile::where('user_id', $user->id)->first();
  //     if ($requestUserId && $type == "single") {
  //       if ($loggedUserProfile && $loggedUserProfile->toArray()) {
  //         $following = $loggedUserProfile->following ? json_decode($loggedUserProfile->following, true) : [];
  //       } else {
  //         $loggedUserProfile = new UserProfile();
  //         $loggedUserProfile->user_id = $user->id;
  //         $following = [];
  //       }

  //       $requestUserProfile = UserProfile::where('user_id', $requestUserId)->first();
  //       if ($requestUserProfile && $requestUserProfile->toArray()) {
  //         $followers = $requestUserProfile->followers ? json_decode($requestUserProfile->followers, true) : [];
  //       } else {
  //         $requestUserProfile = new UserProfile();
  //         $requestUserProfile->user_id = $requestUserId;
  //         $followers = [];
  //       }
  //       if ($status == "1") {
  //         if (!in_array($user->id, $followers))
  //           $followers[] = $user->id;
  //         if (!in_array($requestUserId, $following))
  //           $following[] = $requestUserId;
  //       } else {
  //         if (($key = array_search($user->id, $followers)) !== false) {
  //           unset($followers[$key]);
  //         }
  //         if (($key = array_search($requestUserId, $following)) !== false) {
  //           unset($following[$key]);
  //         }
  //       }
  //       $loggedUserProfile->following = json_encode($following);
  //       $requestUserProfile->followers = json_encode($followers);

  //       $loggedUserProfile->save();
  //       $requestUserProfile->save();
  //       DB::commit();
  //       return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Following status changed', 'toast' => true]);
  //     } else if ($type == "all" && $status == "2") {
  //       if ($loggedUserProfile && $loggedUserProfile->toArray()) {
  //         $followingArr = $loggedUserProfile->following ? json_decode($loggedUserProfile->following, true) : [];
  //         if ($followingArr) {
  //           $loggedUserProfile->following = null;
  //           $loggedUserProfile->save();
  //           foreach ($followingArr as $f) {
  //             $requestedUnFollowedUser = UserProfile::where('user_id', $f)->first();
  //             $followersArr = $requestedUnFollowedUser->followers ? json_decode($requestedUnFollowedUser->followers, true) : [];
  //             if (($key = array_search($user->id, $followersArr)) !== false) {
  //               unset($followersArr[$key]);
  //             }
  //             $requestedUnFollowedUser->followers = json_encode($followersArr);
  //             $requestedUnFollowedUser->save();
  //           }
  //           DB::commit();
  //         }
  //         return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Unfollowed for all your following', 'toast' => true]);
  //       }
  //     } else {
  //       return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Something went wrong', 'toast' => true]);
  //     }
  //   } catch (\Exception $e) {
  //     DB::rollBack();
  //     Log::info('ConnectionController toggleFollow Error : ' . $e->getMessage());
  //     return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
  //   }
  // }

  public function toggleFollow(ToggleFollowRequest $request)
  {
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $status = $request->status;
      $type = $request->type;
      $requestUserId = $request->user_id ?? null;

      // Default module to 'account' if not provided
      $module = $request->input('module', 'account');
      $followingColumn = $module === 'community' ? 'c_following' : 'following';
      $followerColumn = $module === 'community' ? 'c_followers' : 'followers';

      if ($type == "all" && $status == "1") {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'You can only unfollow all', 'toast' => true]);
      }

      if ($requestUserId && $user->id == $requestUserId) {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Can\'t self-follow', 'toast' => true]);
      }

      $loggedUserProfile = UserProfile::where('user_id', $user->id)->first();

      if ($requestUserId && $type == "single") {
        if ($loggedUserProfile && $loggedUserProfile->toArray()) {
          $following = $loggedUserProfile->{$followingColumn} ? json_decode($loggedUserProfile->{$followingColumn}, true) : [];
        } else {
          $loggedUserProfile = new UserProfile();
          $loggedUserProfile->user_id = $user->id;
          $following = [];
        }

        $requestUserProfile = UserProfile::where('user_id', $requestUserId)->first();
        if ($requestUserProfile && $requestUserProfile->toArray()) {
          $followers = $requestUserProfile->{$followerColumn} ? json_decode($requestUserProfile->{$followerColumn}, true) : [];
        } else {
          $requestUserProfile = new UserProfile();
          $requestUserProfile->user_id = $requestUserId;
          $followers = [];
        }

        if ($status == "1") { // Follow
          if (!in_array($user->id, $followers)) {
            $followers[] = $user->id;
          }
          if (!in_array($requestUserId, $following)) {
            $following[] = $requestUserId;
          }
        } else { // Unfollow
          if (($key = array_search($user->id, $followers)) !== false) {
            unset($followers[$key]);
          }
          if (($key = array_search($requestUserId, $following)) !== false) {
            unset($following[$key]);
          }
        }

        $loggedUserProfile->{$followingColumn} = json_encode($following);
        $requestUserProfile->{$followerColumn} = json_encode($followers);
        $loggedUserProfile->save();
        $requestUserProfile->save();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Following status changed', 'toast' => true]);
      } else if ($type == "all" && $status == "2") { // Unfollow all
        if ($loggedUserProfile && $loggedUserProfile->toArray()) {
          $followingArr = $loggedUserProfile->{$followingColumn} ? json_decode($loggedUserProfile->{$followingColumn}, true) : [];
          if ($followingArr) {
            $loggedUserProfile->{$followingColumn} = null;
            $loggedUserProfile->save();
            foreach ($followingArr as $f) {
              $requestedUnFollowedUser = UserProfile::where('user_id', $f)->first();
              $followersArr = $requestedUnFollowedUser->{$followerColumn} ? json_decode($requestedUnFollowedUser->{$followerColumn}, true) : [];
              if (($key = array_search($user->id, $followersArr)) !== false) {
                unset($followersArr[$key]);
              }
              $requestedUnFollowedUser->{$followerColumn} = json_encode($followersArr);
              $requestedUnFollowedUser->save();
            }
            DB::commit();
          }
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Unfollowed for all your following', 'toast' => true]);
        }
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Something went wrong', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('ConnectionController toggleFollow Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function requestToConnect(AddConnectionRequest $request)
  {
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $requetedUserId = $request->user_id;
      $notificationtitle = $notificationDescription = $user->username . " want to connect with you";
      if ($user->id == $requetedUserId) {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Can\'t make self connection', 'toast' => true]);
      }
      $connectionExists = Connection::where(function ($query) use ($user, $requetedUserId) {
        $query->where('user_1_id', $user->id)
          ->where('user_2_id', $requetedUserId);
      })->orWhere(function ($query) use ($user, $requetedUserId) {
        $query->where('user_1_id', $requetedUserId)
          ->where('user_2_id', $user->id);
      })->whereNull('deleted_at')->first();


      if ($connectionExists && $connectionExists->toArray()) {
        $actions = $connectionExists->actions ? json_decode($connectionExists->actions, true) : [];
        if ($connectionExists->user_1_id == $user->id) {
          if (($connectionExists->status == "0" || $connectionExists->status == "3" || $connectionExists->status == "4" || $connectionExists->status == "5")) {
            $connectionExists->status = "0";
            $actions[] = array("user_id" => $user->id, "action" => "Make Request", "status" => "0", "time" => date("Y-m-d H:i:s A"));
            $connectionExists->actions = json_encode($actions);
            $connectionExists->save();
            addNotification($requetedUserId, $user->id, $notificationtitle, $notificationDescription, $connectionExists->id, "1", "/account-users?tab=Requests&requestTab=received");
            $this->sendConnectionMail($requetedUserId, $user->username . " has sent connection request to you", config('app.app_name') . " Connection Request");
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Connection request added', 'toast' => true]);
          } else if ($connectionExists->status == "1") {
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Already Connected', 'toast' => true]);
          } else if ($connectionExists->status == "2") {
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Already rejected by requested user', 'toast' => true]);
          } else if ($connectionExists->status == "6") {
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Blocked by requested user', 'toast' => true]);
          } else {
            DB::commit();
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'something went wrong', 'toast' => true]);
          }
        } else {
          if ($connectionExists->status == "0" || $connectionExists->status == "2") {
            $connectionExists->status = "1";
            $actions[] = array("user_id" => $user->id, "action" => "Accept Request", "status" => "1", "time" => date("Y-m-d H:i:s A"));
            $connectionExists->actions = json_encode($actions);
            $connectionExists->save();
            addNotification($requetedUserId, $user->id, $notificationtitle, $notificationDescription, $connectionExists->id, "1", "/account-users?tab=Requests&requestTab=received");
            $this->sendConnectionMail($requetedUserId, $user->username . " has sent connection request to you", config('app.app_name') . " Connection Request");
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Connection request accepted', 'toast' => true]);
          } else if ($connectionExists->status == "1") {
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Already Connected', 'toast' => true]);
          } else if ($connectionExists->status == "3" || $connectionExists->status == "4" || $connectionExists->status == "6") {
            $connectionExists->status = "0";
            $connectionExists->user_2_id = $requetedUserId;
            $connectionExists->user_1_id = $user->id;
            $actions[] = array("user_id" => $user->id, "action" => "Make Request", "status" => "0", "time" => date("Y-m-d H:i:s A"));
            $connectionExists->actions = json_encode($actions);
            $connectionExists->save();
            addNotification($requetedUserId, $user->id, $notificationtitle, $notificationDescription, $connectionExists->id, "1", "/account-users?tab=Requests&requestTab=received");
            $this->sendConnectionMail($requetedUserId, $user->username . " has sent connection request to you", config('app.app_name') . " Connection Request");
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Connection request added', 'toast' => true]);
          } else if ($connectionExists->status == "5") {
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blocked by requested user', 'toast' => true]);
          } else {
            DB::commit();
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'something went wrong', 'toast' => true]);
          }
        }
      } else {
        $connection = new Connection();
        $connection->user_1_id = $user->id;
        $connection->user_2_id = $requetedUserId;
        $actions = [];
        $actions[] = array("user_id" => $user->id, "action" => "Make Request", "status" => "0", "time" => date("Y-m-d H:i:s A"));
        $connection->actions = json_encode($actions);
        $connection->save();
        addNotification($requetedUserId, $user->id, $notificationtitle, $notificationDescription, $connection->id, "1", "/account-users?tab=Requests&requestTab=received");
        $this->sendConnectionMail($requetedUserId, $user->username . " has sent connection request to you", config('app.app_name') . " Connection Request");
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Connection request added', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('ConnectionController requestToConnect Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function acceptToConnect(AcceptConnectionRequest $request)
  {
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $connection_id = $request->connection_id;
      $connectionExists = Connection::where('user_1_id', $connection_id)->where("user_2_id", $user->id)->where('status', "0")->first();
      if ($connectionExists && $connectionExists->toArray()) {
        $actions = $connectionExists->actions ? json_decode($connectionExists->actions, true) : [];
        $connectionExists->status = "1";
        $actions[] = array("user_id" => $user->id, "action" => "Accept Request", "status" => "1", "time" => date("Y-m-d H:i:s A"));
        $connectionExists->actions = json_encode($actions);
        $connectionExists->save();
        $notificationtitle = $notificationDescription = $user->username . " has accepted request";
        addNotification($connectionExists->user_1_id, $user->id, $notificationtitle, $notificationDescription, $connectionExists->id, "1", "/account-users?tab=Connections");
        $this->sendConnectionMail($connectionExists->user_1_id, $user->username . " has accepted your connection request", config('app.app_name') . " New Connection");
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Connection request accepted', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No connection request', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('ConnectionController acceptToConnect Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function removeInvitation(RejectInvitationsRequest $request)
  {
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $connection_id = $request->connection_id;
      $type = $request->type;
      $connectionExists = null;
      if ($type == "reject") {
        $connectionExists = Connection::where('user_1_id', $connection_id)->where("user_2_id", $user->id)->where('status', "0")->first();
      } else if ($type == "remove") {
        $connectionExists = Connection::where('user_1_id', $user->id)->where("user_2_id", $connection_id)->where('status', "0")->first();
      }
      if ($connectionExists && $connectionExists->toArray()) {
        $actions = $connectionExists->actions ? json_decode($connectionExists->actions, true) : [];
        $tempStatus = $type == "reject" ? "2" : "3";
        $connectionExists->status = $tempStatus;
        $actions[] = array("user_id" => $user->id, "action" => ucfirst($type) . " Request", "status" => $connectionExists->status, "time" => date("Y-m-d H:i:s A"));
        $connectionExists->actions = json_encode($actions);
        $connectionExists->save();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Connection request removed', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No connection request', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('ConnectionController removeInvitation Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function deleteConnection(DeleteConnectionRequest $request)
  {
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $connection_id = $request->connection_id;
      $type = $request->type;
      if ($type == "single") {
        $tempConnection = Connection::where(function ($query) use ($user, $connection_id) {
          $query->where('user_1_id', $user->id)
            ->where('user_2_id', $connection_id);
        })->orWhere(function ($query) use ($user, $connection_id) {
          $query->where('user_2_id', $user->id)
            ->where('user_1_id', $connection_id);
        })->whereIn("status", ["1"])->first();
        if ($tempConnection && $tempConnection->toArray()) {
          return deleteConnection($user, $tempConnection);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No connection request found', 'toast' => true]);
        }
      } else if ($type == "all") {
        $connectionExists = Connection::where(function ($query) use ($user) {
          $query->where('user_1_id', $user->id)
            ->orWhere('user_2_id', $user->id);
        })->whereIn("status", ["1"])->get();
        if ($connectionExists && $connectionExists->toArray()) {
          $connectionExists = $connectionExists->toArray();
          foreach ($connectionExists as $conn) {
            $tempConnection = Connection::where("id", $conn['id'])->first();
            deleteConnection($user, $tempConnection, false);
          }
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Connection requests deleted', 'toast' => true]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No connection request found', 'toast' => true]);
        }
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Something went wrong', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('ConnectionController deleteConnection Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function connectionSummary(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $summaryData = getConnectionSummary($user);
      $member = getSubscribedPackageDataByKey($user->id) ? getSubscribedPackageDataByKey($user->id) . " Member" : null;
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Summary retrieved', 'toast' => true], ["summary" => $summaryData, "member" => $member]);
    } catch (\Exception $e) {
      Log::info('ConnectionController connectionSummary Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function connectionInvitation(ConnectionInvitationsListRequest $request)
  {
    try {
      $user = $request->attributes->get('user');

      $limit = isset($request->limit) ? $request->limit : 10;
      $page = isset($request->page) && $request->page ? $request->page : 1;
      $search = isset($request->search) ? $request->search : "";
      $offset = ($page - 1) * $limit;

      $tempInvitionsList = Connection::where(function ($query) use ($user) {
        $query->where('user_2_id', $user->id);
      })->where("status", "0")->orderBy("updated_at", "desc")->offset($offset)->limit($limit)->pluck("user_1_id");

      if ($tempInvitionsList && $tempInvitionsList->toArray()) {
        $tempInvitionsList = $tempInvitionsList->toArray();
        $list = [];

        $usersQuery = User::query();
        $usersQuery->whereNot('id', $user->id);
        $usersQuery->offset($offset)->limit($limit)->orderBy("username", "asc");
        if ($search) {
          $usersQuery->where(function ($query) use ($search) {
            $query->where('email', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%");
          });
        }
        $usersQuery->whereIn('id', $tempInvitionsList);
        $requestedUsers = $usersQuery->select(['id', 'username', 'email'])->get();
        if ($requestedUsers->isNotEmpty()) {
          $requestedUsers = $requestedUsers->toArray();
          foreach ($requestedUsers as $row) {
            $tempRow = [];
            $tempRow['connection_id'] = $row['id'];
            $tempRow['username'] = $row['username'];
            $tempRow['email'] = $row['email'];
            $tempRow['invitation_user_id'] = $row['id'];
            $list[] = $tempRow;
          }
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Invitations found', 'toast' => true], ['invitations' => $list]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Invitations not found', 'toast' => true]);
        }
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Invitations not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('ConnectionController connectionInvitions Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function connectionList(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $limit = isset($request->limit) ? $request->limit : 10;
      $page = isset($request->page) && $request->page ? $request->page : 1;
      $search = isset($request->search) ? $request->search : "";
      $pagination = true;
      $offset = ($page - 1) * $limit;
      $statusArr = ['1'];
      $request->attributes->remove('include');
      $tempConnectionUserIds = getConnectedUsers($request, $user, $statusArr);
      if ($tempConnectionUserIds) {
        $usersQuery = User::query();
        $usersQuery->whereNot('id', $user->id);
        if ($pagination) {
          $usersQuery->offset($offset)->limit($limit)->orderBy("username", "asc");
        }
        if ($search) {
          $usersQuery->where(function ($query) use ($search) {
            $query->where('email', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%");
          });
        }
        $usersQuery->whereIn("id", $tempConnectionUserIds);
        $users = $usersQuery->select(['id', 'username', 'email'])->with('profile')->get();
        $list = [];
        if ($users->isNotEmpty()) {
          $users = $users->toArray();
          foreach ($users as $row) {
            $tempRow = [];
            $tempRow['connection_id'] = $row['id'];
            $tempRow['username'] = $row['username'];
            $tempRow['email'] = $row['email'];
            $tempRow['user_id'] = $row['id'];
            $tempRow['cover_img'] = generateLinearGradient();
            $tempRow['avatar_url'] = null;
            if (isset($row['profile']['profile_image_path']) && $row['profile']['profile_image_path']) {
              $tempRow['avatar_url'] = getFileTemporaryURL($row['profile']['profile_image_path']);
            }
            if (isset($row['profile']['cover_img']) && $row['profile']['cover_img']) {
              $row['cover_img'] = $row['profile']['cover_img'];
            }
            $list[] = $tempRow;
          }
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Connections found', 'toast' => true], ['connections' => $list]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Connections not found', 'toast' => true]);
        }
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Connections not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('ConnectionController connectionInvitions Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function followList(ConnectionFollowListRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      $type = $request->type;
      $limit = isset($request->limit) ? $request->limit : 10;
      $page = isset($request->page) && $request->page ? $request->page : 1;
      $search = isset($request->search) ? $request->search : "";
      $offset = ($page - 1) * $limit;
      $pagination = true;

      $module = $request->input('module', 'account');

      $list = getFollowList($type, $user, $limit, $offset, $pagination, true, $search, $module);
      if ($list) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => ucfirst($type) . ' found', 'toast' => true], ['list' => $list]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No ' . ucfirst($type), 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('ConnectionController followList Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function connectionRequests(ConnectionInvitationsListRequest $request)
  {
    try {
      $user = $request->attributes->get('user');

      $user = $request->attributes->get('user');
      $limit = isset($request->limit) ? $request->limit : 10;
      $page = isset($request->page) && $request->page ? $request->page : 1;
      $search = isset($request->search) ? $request->search : "";
      $offset = ($page - 1) * $limit;

      $tempRequestsList = Connection::where(function ($query) use ($user) {
        $query->where('user_1_id', $user->id);
      })->where("status", "0")->orderBy("updated_at", "desc")->offset($offset)->limit($limit)->get();

      if ($tempRequestsList && $tempRequestsList->toArray()) {
        $tempRequestsList = $tempRequestsList->toArray();
        $list = [];
        foreach ($tempRequestsList as $row) {
          $tempRow = [];
          $tempRow['connection_id'] = $row['id'];
          $tempUser = User::where("id", $row['user_2_id'])->first();
          $tempRow['username'] = $tempUser->username;
          $tempRow['email'] = $tempUser->email;
          $tempRow['request_user_id'] = $tempUser->id;
          $list[] = $tempRow;
        }
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Requests found', 'toast' => true], ['requests' => $list]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Requests not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('ConnectionController connectionRequests Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getInvitation(GetInvitationsRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      $limit = isset($request->limit) ? $request->limit : 10;
      $page = isset($request->page) && $request->page ? $request->page : 1;
      $search = isset($request->search) ? $request->search : "";
      $type = $request->type;
      $offset = ($page - 1) * $limit;

      $connectionListQuery = Connection::query();
      if ($type == "sent") {
        $column = "user_2_id";
        $connectionList = $connectionListQuery->where(function ($query) use ($user) {
          $query->where('user_1_id', $user->id);
        });
      } else {
        if (isset($request->tab) && $request->tab == "home") {
          $connectionListQuery->where("ignored", "0");
        }
        $column = "user_1_id";
        $connectionList = $connectionListQuery->where(function ($query) use ($user) {
          $query->where('user_2_id', $user->id);
        });
      }
      $connectionListQuery->where("status", "0");
      $connectionList = $connectionListQuery->selectRaw("$column as uid")->pluck("uid");
      if ($connectionList && $connectionList->toArray()) {
        $connectionList = $connectionList->toArray();
        $usersQuery = User::query();
        $usersQuery->whereNot('id', $user->id);
        $usersQuery->offset($offset)->limit($limit)->orderBy("username", "asc");
        if ($search) {
          $usersQuery->where(function ($query) use ($search) {
            $query->where('email', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%");
          });
        }
        if ($connectionList)
          $usersQuery->whereIn("id", $connectionList);
        $users = $usersQuery->select(['id', 'username', 'email'])->with('profile')->get();

        $invitationsList = [];
        if ($users->isNotEmpty()) {
          foreach ($users as $userRow) {
            $tempUser = [];
            $tempUser['username'] = $userRow['username'];
            $tempUser['connection_id'] = $userRow['id'];
            $tempUser['user_id'] = $userRow['id'];
            $tempUser['email'] = $userRow['email'];
            $tempUser['avatar_url'] = null;
            $tempUser['numerical_status'] = "0";
            $tempUser['status'] = "pending";
            if (isset($userRow['profile']['profile_image_path']) && $userRow['profile']['profile_image_path']) {
              $tempUser['avatar_url'] = getFileTemporaryURL($userRow['profile']['profile_image_path']);
            }
            $invitationsList[] = $tempUser;
          }
        }
        if ($invitationsList) {
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Invitations found', 'toast' => true], ['requests' => $invitationsList]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No invitations found', 'toast' => true]);
        }
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No invitations found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('ConnectionController getInvitation Error : ' . $e->getMessage() . " " . $e->getLine());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function ignoreInvitation(IgnoreConnectionRequest $request)
  {
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $connection_id = $request->connection_id;
      $pendingConnection = Connection::where("user_1_id", $connection_id)->where('status', "0")->where(function ($query) use ($user) {
        $query->where('user_2_id', $user->id);
      })->first();
      if ($pendingConnection && $pendingConnection->toArray()) {
        $pendingConnection->ignored = "1";
        $pendingConnection->save();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Connection request ignored', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No pending connection with given request', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('ConnectionController ignoreInvitation Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  protected function sendConnectionMail($user_id, $message, $subject)
  {
    $user = User::where('id', $user_id)->first();
    if (isset($user->email)) {
      $data['logoUrl'] = asset('assets/images/logo/logo-dark.png');
      $data['title'] = config('app.app_name') . " Connection";
      $data['username'] = ucfirst($user->username);
      $data['message'] = $message;
      $data['subject'] = $subject;
      $data['link'] = config("app.account_url") . "account-users";
      $data['linkTitle'] = "Connection";
      $data['supportMail'] = config('app.support_mail');
      $data['projectName'] = config('app.app_name');
      $data['view'] = "mail-templates.connection";
      Mail::to($user->email)->send(new SendMail($data, $data['view']));
    }
  }
  public function removeFollowers(RemoveFollowersRequest $request)
  {
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $followerId = $request->follower_id ?? null;

      if (!$followerId) {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Follower ID is required', 'toast' => true]);
      }

      // Ensure the module is community
      $module = $request->input('module', 'account');
      if ($module !== 'community') {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'This operation is only allowed for the community module', 'toast' => true]);
      }

      // Load the user's profile
      $userProfile = UserProfile::where('user_id', $user->id)->first();
      if (!$userProfile || !$userProfile->toArray()) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'User profile not found', 'toast' => true]);
      }

      // Get the current followers from the c_followers column
      $followers = $userProfile->c_followers ? json_decode($userProfile->c_followers, true) : [];

      // Check if the follower exists in the list and remove them
      if (($key = array_search($followerId, $followers)) !== false) {
        unset($followers[$key]);

        // Update the user's follower list
        $userProfile->c_followers = json_encode(array_values($followers));
        $userProfile->save();

        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Follower removed successfully', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Follower not found in your community follower list', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('ConnectionController removeFollower Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
