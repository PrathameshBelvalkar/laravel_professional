<?php

use App\Models\Account\Connection;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\CommunityUserProfile;
use Illuminate\Support\Facades\Log;

if (!function_exists('getFollowStatus')) {
  function getFollowStatus($loggedUserId, $userId)
  {
    $status = ['following' => false, "follower" => false];

    $loggedUserData = UserProfile::where("user_id", $loggedUserId)->first();
    $userData = UserProfile::where("user_id", $userId)->first();

    if ($userData && $userData->toArray() && $userData->following) {
      $userDataFollowing = $userData->following ? json_decode($userData->following, true) : [];
      if ($userDataFollowing) {
        if (in_array($loggedUserId, $userDataFollowing)) {
          $status['follower'] = true;
        }
      }
    }
    if ($loggedUserData && $loggedUserData->toArray() && $loggedUserData->following) {
      $loggedUserFollowing = $loggedUserData->following ? json_decode($loggedUserData->following, true) : [];
      if ($loggedUserFollowing) {
        if (in_array($userId, $loggedUserFollowing)) {
          $status['following'] = true;
        }
      }
    }

    return $status;
  }
}
if (!function_exists('getConnectionStatus')) {
  function getConnectionStatus($loggedUserId, $userId)
  {
    $status = ["entry" => false, 'connected' => false, "message" => "No connection", "status" => null];

    $connectionExists = Connection::where(function ($query) use ($userId, $loggedUserId) {
      $query->where('user_1_id', $userId)
        ->where('user_2_id', $loggedUserId);
    })->orWhere(function ($query) use ($userId, $loggedUserId) {
      $query->where('user_2_id', $userId)
        ->where('user_1_id', $loggedUserId);
    })->first();
    if ($connectionExists && $connectionExists->toArray()) {
      $status['entry'] = true;
      if ($connectionExists->status == "0") {
        $status = ['entry' => true, 'connected' => false, "message" => "Pending", "status" => "0"];
      } else if ($connectionExists->status == "1") {
        $status = ['entry' => true, 'connected' => true, "message" => "Connected", "status" => "1"];
      } else if ($connectionExists->status == "2") {
        $status = ['entry' => true, 'connected' => false, "message" => "Rejected", "status" => "2"];
      } else if ($connectionExists->status == "3" || $connectionExists->status == "4") {
        $status = ['entry' => true, 'connected' => false, "message" => "Deleted", "status" => $connectionExists->status];
      } else if ($connectionExists->status == "5" || $connectionExists->status == "6") {
        $status = ['entry' => true, 'connected' => false, "message" => "Blocked", "status" => $connectionExists->status];
      }
    }
    return $status;
  }
}
if (!function_exists('getMutualConnections')) {
  function getMutualConnections($loggedUserId, $userId)
  {
    $mutualIds = [];
    $loggedUserConnectionIds = getConnectionsIds($loggedUserId);
    $userConnectionIds = getConnectionsIds($userId);

    if (is_array($userConnectionIds) && is_array($loggedUserConnectionIds) && $loggedUserConnectionIds && $loggedUserId) {
      $mutualIds = array_intersect($loggedUserConnectionIds, $userConnectionIds);
    }
    $mutualData = array("count" => count($mutualIds), "users" => null);
    if ($mutualIds) {
      $mutualUserList = User::whereIn("id", $mutualIds)->select(['id', 'username', 'email'])->get();
      if ($mutualUserList && $mutualUserList->toArray())
        $mutualData['users'] = $mutualUserList->toArray();
    }
    return $mutualData;
  }
}
if (!function_exists('getConnectionsIds')) {
  function getConnectionsIds($userId)
  {
    $ids = [];
    $tempConnectionIds = Connection::where(
      'status',
      "1"
    )->where(function ($query) use ($userId) {
      $query->where('user_1_id', $userId)
        ->orWhere('user_2_id', $userId);
    })->get();
    if ($tempConnectionIds && $tempConnectionIds->toArray()) {
      $tempConnectionIds = $tempConnectionIds->toArray();
      foreach ($tempConnectionIds as $row) {
        $otherUserId = $row['user_1_id'];
        if ($row['user_1_id'] == $userId)
          $otherUserId = $row['user_2_id'];
        $ids[] = $otherUserId;
      }
    }
    return $ids;
  }
}
// if (!function_exists('getFollowList')) {
//   function getFollowList($type, $user, $limit, $offset, $pagination, $isFollowed, $search = "")
//   {
//     $userProfileObj = $user_profile = UserProfile::where('user_id', $user->id)->first();
//     $list = [];
//     if ($user_profile && $user_profile->toArray()) {
//       $user_profile = $user_profile->toArray();
//       $tempFollowList = $user_profile[$type] ? json_decode($user_profile[$type], true) : [];
//       $tfList = null;
//       $tfListQuery = User::query();
//       // $tfListQuery->where("verify_email", "1");
//       $tfListQuery->select(['id', 'username', 'email']);
//       $tfListQuery->offset($offset)->limit($limit)->with("profile");
//       if ($search) {
//         $tfListQuery->where(function ($query) use ($search) {
//           $query->where('email', 'like', "%{$search}%")
//             ->orWhere('username', 'like', "%{$search}%");
//         });
//       }
//       if ($tempFollowList) {
//         if ($isFollowed)
//           $tfListQuery->whereIn("id", $tempFollowList);
//         else
//           $tfListQuery->whereNotIn("id", $tempFollowList);
//         $tfList = $tfListQuery->get();
//       } else if ($isFollowed == false) {
//         $tfList = $tfListQuery->get();
//       }
//       if ($tfList && $tfList->isNotEmpty()) {
//         $tfList = $tfList->toArray();
//         foreach ($tfList as $row) {
//           $tempRow = [];
//           if ($user->id == $row['id'])
//             continue;
//           $tempRow['cover_img'] = generateLinearGradient();
//           $tempRow['avatar_url'] = null;
//           $tempRow['user_id'] = $row['id'];
//           if (isset($row['profile']['cover_img']) && $row['profile']['cover_img']) {
//             $tempRow['cover_img'] = $row['profile']['cover_img'];
//           }
//           $tempRow['user_profile'] = null;
//           $tempRow['username'] = $row['username'];
//           $tempRow['email'] = $row['email'];
//           if ($type == "followers") {
//             $status = ['following' => false, "follower" => false];
//             if (isset($row['profile']['following']) && $row['profile']['following']) {
//               $userDataFollowing = $row['profile']['following'] ? json_decode($row['profile']['following'], true) : [];
//               if ($userDataFollowing) {
//                 if (in_array($user->id, $userDataFollowing)) {
//                   $status['follower'] = true;
//                 }
//               }
//             }
//             if ($userProfileObj && $userProfileObj->toArray() && $userProfileObj->following) {
//               $loggedUserFollowing = $userProfileObj->following ? json_decode($userProfileObj->following, true) : [];
//               if ($loggedUserFollowing) {
//                 if (in_array($user->id, $loggedUserFollowing)) {
//                   $status['following'] = true;
//                 }
//               }
//             }
//             $tempRow['following'] = $status['following'];
//           }
//           if (isset($row['profile']['profile_image_path']) && $row['profile']['profile_image_path']) {
//             $tempRow['avatar_url'] = getFileTemporaryURL($row['profile']['profile_image_path']);
//           }
//           $list[] = $tempRow;
//         }
//       }
//     }
//     return $list;
//   }
// }

if (!function_exists('getFollowList')) {
  function getFollowList($type, $user, $limit, $offset, $pagination, $isFollowed, $search = "", $module = 'account')
  {
    $userProfileObj = UserProfile::where('user_id', $user->id)->first();
    $list = [];

    if ($userProfileObj && $userProfileObj->toArray()) {
      $userProfileArray = $userProfileObj->toArray();

      $followListKey = $module === 'community' ? 'c_' . $type : $type;
      $tempFollowList = $userProfileArray[$followListKey] ? json_decode($userProfileArray[$followListKey], true) : [];

      if (empty($tempFollowList) && $isFollowed) {
        return $list;
      }

      $tfListQuery = User::query();
      $tfListQuery->select(['id', 'username', 'email']);
      $tfListQuery->offset($offset)->limit($limit)->with("profile");

      if ($search) {
        $tfListQuery->where(function ($query) use ($search) {
          $query->where('email', 'like', "%{$search}%")
            ->orWhere('username', 'like', "%{$search}%");
        });
      }

      if (!empty($tempFollowList)) {
        if ($isFollowed) {
          $tfListQuery->whereIn("id", $tempFollowList);
        } else {
          $tfListQuery->whereNotIn("id", $tempFollowList);
        }
      }

      $tfList = $tfListQuery->get();

      if ($tfList && $tfList->isNotEmpty()) {
        foreach ($tfList as $row) {
          $tempRow = [];
          if ($user->id == $row['id']) {
            continue;
          }

          $tempRow['cover_img'] = generateLinearGradient();
          $tempRow['avatar_url'] = null;
          $tempRow['user_id'] = $row['id'];
          $tempRow['user_profile'] = null;
          $tempRow['username'] = $row['username'];
          $tempRow['email'] = $row['email'];

          if (isset($row['profile']['cover_img']) && $row['profile']['cover_img']) {
            $tempRow['cover_img'] = $row['profile']['cover_img'];
          }

          if ($type == "followers") {
            $status = ['following' => false, "follower" => false];

            if (isset($row['profile']['following']) && $row['profile']['following']) {
              $userDataFollowing = json_decode($row['profile']['following'], true);
              if (in_array($user->id, $userDataFollowing)) {
                $status['follower'] = true;
              }
            }

            if ($userProfileObj && $userProfileObj->$followListKey) {
              $loggedUserFollowing = json_decode($userProfileObj->$followListKey, true);
              if (in_array($row['id'], $loggedUserFollowing)) {
                $status['following'] = true;
              }
            }

            $tempRow['following'] = $status['following'];
          }

          if ($module === 'community') {
            $communityProfile = CommunityUserProfile::where('user_id', $row['id'])->first();
            if ($communityProfile && $communityProfile->profile_image_path) {
              $tempRow['avatar_url'] = getFileTemporaryURL($communityProfile->profile_image_path);
            } elseif (isset($row['profile']['profile_image_path']) && $row['profile']['profile_image_path']) {
              $tempRow['avatar_url'] = getFileTemporaryURL($row['profile']['profile_image_path']);
            } else {
              $tempRow['avatar_url'] = null;
            }
          } else {
            if (isset($row['profile']['profile_image_path']) && $row['profile']['profile_image_path']) {
              $tempRow['avatar_url'] = getFileTemporaryURL($row['profile']['profile_image_path']);
            }
          }

          $list[] = $tempRow;
        }
      }
    }

    return $list;
  }
}
if (!function_exists('deleteConnection')) {
  function deleteConnection($user, $connection, $returnJSON = true)
  {
    if ($connection->status == "1" || $connection->status == "0") {
      $tempStatus = $connection->user_1_id == $user->id ? "3" : "4";
      $connection->status = $tempStatus;
      $actions[] = array("user_id" => $user->id, "action" => "Delete Request", "status" => $tempStatus, "time" => date("Y-m-d H:i:s A"));
      $connection->actions = json_encode($actions);
      $connection->save();
      DB::commit();
      if ($returnJSON)
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Connection request deleted', 'toast' => true]);
      else
        return null;
    } else if ($connection->status == "3" || $connection->status == "4") {
      if ($returnJSON)
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Already deleted connection request', 'toast' => true]);
      else
        return null;
    } else {
      if ($returnJSON)
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Connection in blocked state', 'toast' => true]);
      else
        return null;
    }
  }
}
if (!function_exists('getConnectionSummary')) {
  function getConnectionSummary($user, $combinedFollowersSummary = true)
  {
    $allVerifieduserCount = User::whereNot('id', $user->id)->count();
    $data['connectedUserCount'] = Connection::where(function ($query) use ($user) {
      $query->where('user_1_id', $user->id)
        ->orWhere('user_2_id', $user->id);
    })->where("status", "1")->count();

    $data['connectionRequestedUserCount'] = Connection::where(function ($query) use ($user) {
      $query->where('user_1_id', $user->id);
    })->where("status", "0")->count();

    $data['connectionInvitationCount'] = Connection::where(function ($query) use ($user) {
      $query->where('user_2_id', $user->id);
    })->where("status", "0")->count();

    $userProfile = UserProfile::where("user_id", $user->id)->first();
    $data['following'] = 0;
    $data['followers'] = 0;
    if ($userProfile && $userProfile->toArray()) {
      $followers = $userProfile->followers ? json_decode($userProfile->followers, true) : [];
      $following = $userProfile->following ? json_decode($userProfile->following, true) : [];
      $data['following'] = count($following);
      $data['followers'] = count($followers);
    }
    if ($combinedFollowersSummary) {
      $followSummary = array(
        [
          "label" => "Following & Follower",
          "values" => [
            ["label" => "Following", 'value' => "followed", "amount" => $data['following']],
            ["label" => "Followers", 'value' => "followers", "amount" => $data['followers']],
            ["label" => "Networks", 'value' => "not_followed", "amount" => $allVerifieduserCount - $data['following']],
          ],
        ]
      );
    } else {
      $followSummary = array(
        [
          "label" => "Following",
          "values" => [
            ["label" => "Following", 'value' => "followed", "amount" => $data['following']],
          ],
        ],
        [
          "label" => "Followers",
          "values" => [
            ["label" => "Followers", 'value' => "followers", "amount" => $data['followers']],
          ],
        ]

      );
    }
    $summaryData = array(
      [
        "label" => "Connections",
        "values" => [
          ['label' => "Connections", "value" => "connected", "amount" => $data['connectedUserCount']],
          ['label' => "Suggestions", "value" => "not_connected", "amount" => $allVerifieduserCount - $data['connectedUserCount']],
        ],
      ],
      [
        "label" => "Requests",
        "values" => [
          ['label' => "Sent", "value" => "sent", "amount" => $data['connectionRequestedUserCount']],
          ['label' => "Received", "value" => "received", "amount" => $data['connectionInvitationCount']],
        ],
      ],
      ...$followSummary

    );
    return $summaryData;
  }
}
if (!function_exists('getConnectedUsers')) {
  function getConnectedUsers($request, $user, $statusTempArr = array("1", "5", "6"))
  {
    $loggedUserConnectionsQuery = Connection::query();
    if (isset($request->include) && $request->include == "pending") {
      $statusTempArr = ["0", "1", "5", "6"];
    }
    $loggedUserConnections = $loggedUserConnectionsQuery->where(function ($query) use ($user, $statusTempArr) {
      $query->whereIn('status', $statusTempArr);
    })->where(function ($query) use ($user) {
      $query->where('user_1_id', $user->id)
        ->orWhere('user_2_id', $user->id);
    })->get();
    $tempUserIds = [];
    if ($loggedUserConnections && $loggedUserConnections->toArray()) {
      $loggedUserConnections = $loggedUserConnections->toArray();
      foreach ($loggedUserConnections as $luc) {
        $otherUserId = $luc['user_1_id'];
        if ($luc['user_1_id'] == $user->id)
          $otherUserId = $luc['user_2_id'];
        $tempUserIds[] = $otherUserId;
      }
    }
    return $tempUserIds;
  }
}
if (!function_exists('getConnectionsAndFollowerUserIds')) {
  function getConnectionsAndFollowerUserIds($loggedUserId)
  {
    $userIds = ['connection_user_ids' => [], "follower_user_ids" => []];
    $connections = Connection::where('status', '1')->where(function ($query) use ($loggedUserId) {
      $query->where('user_1_id', $loggedUserId)
        ->orWhere('user_2_id', $loggedUserId);
    })->selectRaw("CASE WHEN user_1_id = {$loggedUserId} THEN user_2_id ELSE user_1_id END as connected_user")->pluck("connected_user");
    if ($connections->isNotEmpty()) {
      $userIds['connection_user_ids'] = $connections->toArray();
    }
    $userProfile = UserProfile::where('user_id', $loggedUserId)->first();
    if ($userProfile) {
      if ($userProfile->followers) {
        $userIds['follower_user_ids'] = $userProfile->followers ? json_decode($userProfile->followers, true) : [];
      }
    }
    return $userIds;
  }

  if (!function_exists('getCommunityFollowStatus')) {
    function getCommunityFollowStatus($loggedUserId, $userId)
    {
      $status = ['following' => false, 'follower' => false];

      $loggedUserData = UserProfile::where("user_id", $loggedUserId)->first();
      $userData = UserProfile::where("user_id", $userId)->first();

      if ($userData && $userData->c_following) {
        $userDataFollowing = json_decode($userData->c_following, true) ?? [];
        if (in_array($loggedUserId, array_map('intval', $userDataFollowing))) {
          $status['follower'] = true;
        }
      }

      if ($loggedUserData && $loggedUserData->c_following) {
        $loggedUserFollowing = json_decode($loggedUserData->c_following, true) ?? [];
        if (in_array($userId, array_map('intval', $loggedUserFollowing))) {
          $status['following'] = true;
        }
      }
      return $status;
    }
  }
}
