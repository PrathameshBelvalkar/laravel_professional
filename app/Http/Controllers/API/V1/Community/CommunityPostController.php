<?php

namespace App\Http\Controllers\API\V1\Community;

use Illuminate\Http\Request;
use App\Models\CommunityPost;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\CommunityPostComment;
use App\Models\CommunityCommentReply;
use App\Models\CommunityPostTag;
use App\Models\CommunityUserProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Community\UpdatePostRequest;
use App\Http\Requests\Community\UploadPostRequest;
use App\Http\Requests\Community\ReportPostRequest;
use App\Http\Requests\Community\SetCommunityProfileRequest;

class CommunityPostController extends Controller
{
  public function uploadPost(UploadPostRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');

      $communityPost = new CommunityPost();

      $userFolder = "users/private/{$user->id}/community/post";
      Storage::makeDirectory($userFolder);

      $mediaData = [];

      if ($request->hasFile('media')) {
        $mediaFile = $request->file('media');
        $fileExtension = $mediaFile->extension();
        $fileName = $user->id . '_' . uniqid() . '.' . $fileExtension;
        $filePath = "{$userFolder}/{$fileName}";

        $mediaType = in_array(strtolower($fileExtension), ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', '3gp']) ? 'video' : 'image';

        Storage::put($filePath, file_get_contents($mediaFile));

        $mediaData[] = [
          'id' => 1,
          'path' => $filePath,
          'media_type' => $mediaType
        ];
      }

      $communityPost->user_id = $user->id;
      $communityPost->visibility = $request->visibility;
      $communityPost->caption = $request->caption;

      if ($request->has('location')) {
        $communityPost->location = $request->location;
      }

      $uploadTime = $request->upload_time;

      if ($uploadTime) {
        $communityPost->upload_time = $uploadTime;
      } else {
        $communityPost->upload_time = now();
      }

      if ($request->filled('tagged_users')) {
        $taggedUserIds = array_map('trim', explode(',', $request->tagged_users));
        $communityPost->tagged_users = json_encode($taggedUserIds);
      }

      if (!empty($mediaData)) {
        $communityPost->media = json_encode($mediaData);
      }

      $uniqueString = generateUniqueString('CommunityPost', 'unique_link', 32);
      $communityPost->unique_link = $uniqueString;
      $communityPost->save();

      if (!empty($taggedUserIds)) {
        foreach ($taggedUserIds as $userId) {
          CommunityPostTag::create([
            'post_id' => $communityPost->id,
            'user_id' => $userId,
          ]);
          $authToken = $request->header('authToken');
          addNotification($userId, $user->id, "{$user->username} Tagged you in a post.", "Tag", null, "18", "/community_post/{$communityPost->unique_link}", null, $authToken);
        }
      }

      addNotification($user->id, $user->id, "Post uploaded!", "Post uploaded successfully.", null,  "18", "/community_post/{$communityPost->unique_link}", null);

      DB::commit();
      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Post added successfully', 'toast' => true, 'postData' => $communityPost, 'media_urls' => $mediaData]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error adding post: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error adding post: ' . $e->getMessage(), 'toast' => true]);
    }
  }

  public function updatePost(UpdatePostRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $postId = $request->post_id;
      $communityPost = CommunityPost::where('user_id', $user->id)->findOrFail($postId);

      if ($request->filled('caption')) {
        $communityPost->caption = $request->caption;
      }

      if ($request->filled('location')) {
        $communityPost->location = $request->location;
      }

      if ($request->filled('tagged_users')) {
        $newTaggedUsers = array_map('trim', explode(',', $request->tagged_users));

        $existingTaggedUsers = json_decode($communityPost->tagged_users, true) ?? [];

        $usersToAdd = array_diff($newTaggedUsers, $existingTaggedUsers);

        $usersToRemove = array_diff($existingTaggedUsers, $newTaggedUsers);

        foreach ($usersToAdd as $userId) {
          CommunityPostTag::create([
            'post_id' => $communityPost->id,
            'user_id' => $userId,
          ]);
        }

        if (!empty($usersToRemove)) {
          CommunityPostTag::where('post_id', $communityPost->id)->whereIn('user_id', $usersToRemove)->delete();
        }

        $communityPost->tagged_users = json_encode(array_values($newTaggedUsers));
      }

      $communityPost->save();

      DB::commit();
      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Post updated successfully', 'toast' => true, 'postData' => $communityPost]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error updating post: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error updating post', 'toast' => true]);
    }
  }

  public function getTaggedPosts(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $loggedUserId = $user->id;

      $taggedRecords = CommunityPostTag::where('user_id', $loggedUserId)->where('hidden', false)->orderBy('created_at', 'desc')->get();

      $taggedPosts = [];

      foreach ($taggedRecords as $taggedRecord) {
        $post = CommunityPost::find($taggedRecord->post_id);

        if ($post && !$post->is_archived) {
          $user = User::find($post->user_id);
          $userProfile = UserProfile::where('user_id', $post->user_id)->first();
          $communityUserProfile = CommunityUserProfile::where('user_id', $post->user_id)->first();

          $likes = json_decode($post->likes, true) ?? [];
          $post->likes_count = count($likes);

          $likedByUser = false;
          foreach ($likes as $like) {
            if ($like['user_id'] == $loggedUserId) {
              $likedByUser = true;
              break;
            }
          }

          $taggedUserIds = json_decode($post->tagged_users, true);
          $taggedUsers = [];
          if (is_array($taggedUserIds)) {
            foreach ($taggedUserIds as $taggedUserId) {
              $taggedUser = User::find($taggedUserId);
              if ($taggedUser) {
                $taggedUserProfile = UserProfile::where('user_id', $taggedUser->id)->first();
                $taggedUserCommunityProfile = CommunityUserProfile::where('user_id', $taggedUser->id)->first();
                $profileImagePath = $taggedUserCommunityProfile ? $taggedUserCommunityProfile->profile_image_path : ($taggedUserProfile ? $taggedUserProfile->profile_image_path : null);

                $taggedUsers[] = [
                  'id' => $taggedUser->id,
                  'username' => $taggedUser->username,
                  'profile_image_path' => $profileImagePath,
                  'media_url' => $profileImagePath ? Storage::temporaryUrl($profileImagePath, now()->addMinutes(60)) : null,
                ];
              }
            }
          }

          $mediaDetails = json_decode($post->media, true);
          if (is_array($mediaDetails)) {
            foreach ($mediaDetails as &$media) {
              if (isset($media['path']) && is_string($media['path'])) {
                $media['media_urls'] = Storage::temporaryUrl($media['path'], now()->addMinutes(60));
              }
            }
          } else {
            $mediaDetails = [];
          }

          $comments = CommunityPostComment::where('post_id', $post->id)->get();
          $commentCount = $comments->count();
          $replyCount = CommunityCommentReply::whereIn('comment_id', $comments->pluck('id'))->count();
          $totalCommentAndReplyCount = $commentCount + $replyCount;

          $followStatus = getCommunityFollowStatus($loggedUserId, $post->user_id);

          $COMMUNITY_URL = env('COMMUNITY_URL');

          $taggedPosts[] = [
            'id' => $post->id,
            'user_id' => $user->id,
            'username' => $user->username,
            'visibility' => $post->visibility,
            'caption' => $post->caption,
            'media' => $post->media,
            'likes' => $post->likes,
            'likes_count' => $post->likes_count,
            'liked_by_user' => $likedByUser,
            'comment_count' => $totalCommentAndReplyCount,
            'shared_with' => json_decode($post->shared_with, true),
            'tagged_users' => $post->tagged_users,
            'location' => $post->location,
            'following_status' => $followStatus['following'],
            'follower_status' => $followStatus['follower'],
            'upload_time' => $post->upload_time,
            'deleted_at' => $post->deleted_at,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'profile_image_path' => $communityUserProfile ? $communityUserProfile->profile_image_path : ($userProfile ? $userProfile->profile_image_path : null),
            'media_url' => $communityUserProfile && $communityUserProfile->profile_image_path
              ? Storage::temporaryUrl($communityUserProfile->profile_image_path, now()->addMinutes(60))
              : ($userProfile && $userProfile->profile_image_path ? Storage::temporaryUrl($userProfile->profile_image_path, now()->addMinutes(60)) : null),
            'tagged_users_details' => $taggedUsers,
            'media_details' => $mediaDetails,
            'shareable_link' => $COMMUNITY_URL . '/community_post/' . $post->unique_link,
          ];
        }
      }

      DB::commit();

      if (empty($taggedPosts)) {
        return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You have not been tagged in any posts.', 'data' => [], 'toast' => true]);
      } else {
        return response()->json(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Tagged posts retrieved successfully.', 'data' => ['postData' => $taggedPosts], 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error retrieving tagged posts: ' . $e->getMessage());
      return response()->json(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error occurred while retrieving tagged posts.', 'data' => [], 'toast' => true]);
    }
  }

  public function hideUntagPost(Request $request)
  {
    $user = $request->attributes->get('user');
    $postId = $request->input('post_id');
    $action = $request->input('action');

    if (!in_array($action, ['hide', 'untag'])) {
      return response()->json(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Invalid action', 'toast' => true], 400);
    }

    $tagged = CommunityPostTag::where('post_id', $postId)->where('user_id', $user->id)->first();

    if (!$tagged) {
      return response()->json(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Tagged post not found', 'toast' => true], 404);
    }

    if ($action === 'hide') {
      $tagged->hidden = true;
      $tagged->save();
      return response()->json(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Post hidden successfully', 'toast' => true]);
    }

    if ($action === 'untag') {
      $tagged->delete();
      return response()->json(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Successfully removed from the post', 'toast' => true]);
    }
  }

  public function deletePost(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $post_id = $request->post_id;
      $CommunityPost = CommunityPost::where('user_id', $user->id)->where('id', $post_id)->first();

      if ($CommunityPost) {
        $mediaDetails = json_decode($CommunityPost->media, true);
        foreach ($mediaDetails as $media) {
          if (!empty($media['path'])) {
            Storage::delete($media['path']);
          }
        }
        $CommunityPost->delete();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Post deleted successfully.', 'toast' => true]);
      } else {
        DB::rollBack();
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Post not found.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('Error deleting post: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error deleting post.', 'toast' => true]);
    }
  }

  public function getPosts(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $loggedUserId = $user ? $user->id : null;
      $userId = $user ? $user->id : null;
      $postId = $request->input('post_id');
      $userPosts = $request->input('user_posts') === 'true';
      $includeArchived = $request->input('include_archived') === 'true';

      $query = CommunityPost::orderBy('created_at', 'desc');

      if ($userPosts) {
        if (!$loggedUserId) {
          return response()->json(['type' => 'error', 'status' => false, 'code' => 400, 'message' => 'User not authenticated.', 'toast' => true]);
        }
        $query->where('user_id', $loggedUserId);
      } else {
        if ($postId) {
          if (!is_numeric($postId)) {
            return response()->json(['type' => 'error', 'status' => false, 'code' => 400, 'message' => 'Invalid post_id parameter.', 'toast' => true]);
          }
          $query->where('id', $postId);
        }
      }

      if ($includeArchived) {
        if (!$loggedUserId) {
          return response()->json(['type' => 'error', 'status' => false, 'code' => 403, 'message' => 'Access to archived posts is restricted to logged-in users.', 'toast' => true]);
        }

        $query->where('user_id', $loggedUserId)->where('is_archived', true);
      } else {
        $query->where('is_archived', false);
      }

      $posts = $query->get();

      if ($userPosts && $userId) {
        $userProfile = UserProfile::where('user_id', $userId)->first();
        $communityUserProfile = CommunityUserProfile::where('user_id', $userId)->first();

        $followers = $userProfile ? json_decode($userProfile->c_followers, true) : [];
        $following = $userProfile ? json_decode($userProfile->c_following, true) : [];
        $followersCount = is_array($followers) ? count($followers) : 0;
        $followingCount = is_array($following) ? count($following) : 0;

        $followStatus = getCommunityFollowStatus($loggedUserId, $userId);

        $transformedPosts = $posts->transform(function ($post) use ($user, $loggedUserId) {
          $userProfile = UserProfile::where('user_id', $post->user_id)->first();
          $communityUserProfile = CommunityUserProfile::where('user_id', $post->user_id)->first();

          $likes = json_decode($post->likes, true) ?? [];
          $post->likes_count = count($likes);
          $likedByUser = false;

          foreach ($likes as $like) {
            if ($like['user_id'] == $loggedUserId) {
              $likedByUser = true;
              break;
            }
          }

          $taggedUserIds = json_decode($post->tagged_users, true);
          $taggedUsers = [];
          if (is_array($taggedUserIds)) {
            foreach ($taggedUserIds as $taggedUserId) {
              $taggedUser = User::find($taggedUserId);
              if ($taggedUser) {
                $taggedUserProfile = UserProfile::where('user_id', $taggedUser->id)->first();
                $taggedUserCommunityProfile = CommunityUserProfile::where('user_id', $taggedUser->id)->first();
                $profileImagePath = $taggedUserCommunityProfile ? $taggedUserCommunityProfile->profile_image_path : ($taggedUserProfile ? $taggedUserProfile->profile_image_path : null);

                $taggedUsers[] = [
                  'id' => $taggedUser->id,
                  'username' => $taggedUser->username,
                  'profile_image_path' => $profileImagePath,
                  'media_url' => $profileImagePath ? Storage::temporaryUrl($profileImagePath, now()->addMinutes(60)) : null,
                ];
              }
            }
          }

          $mediaDetails = json_decode($post->media, true);
          if (is_array($mediaDetails)) {
            foreach ($mediaDetails as &$media) {
              if (isset($media['path']) && is_string($media['path'])) {
                $media['media_urls'] = Storage::temporaryUrl($media['path'], now()->addMinutes(60));
              }
            }
          } else {
            $mediaDetails = [];
          }

          $comments = CommunityPostComment::where('post_id', $post->id)->get();
          $commentCount = $comments->count();
          $replyCount = CommunityCommentReply::whereIn('comment_id', $comments->pluck('id'))->count();
          $totalCommentAndReplyCount = $commentCount + $replyCount;

          $COMMUNITY_URL = env('COMMUNITY_URL');

          return [
            'id' => $post->id,
            'visibility' => $post->visibility,
            'caption' => $post->caption,
            'media' => $post->media,
            'likes' => $post->likes,
            'likes_count' => $post->likes_count,
            'liked_by_user' => $likedByUser,
            'comment_count' => $totalCommentAndReplyCount,
            'shared_with' => json_decode($post->shared_with, true),
            'tagged_users' => $post->tagged_users,
            'location' => $post->location,
            'upload_time' => $post->upload_time,
            'deleted_at' => $post->deleted_at,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'tagged_users_details' => $taggedUsers,
            'media_details' => $mediaDetails,
            'shareable_link' => $COMMUNITY_URL . '/community_post/' . $post->unique_link,
          ];
        });

        $responseData = [
          'user_id' => $userId,
          'username' => $user->username,
          'profile_image_path' => $communityUserProfile ? $communityUserProfile->profile_image_path : ($userProfile ? $userProfile->profile_image_path : null),
          'media_url' => $communityUserProfile && $communityUserProfile->profile_image_path
            ? Storage::temporaryUrl($communityUserProfile->profile_image_path, now()->addMinutes(60))
            : ($userProfile && $userProfile->profile_image_path ? Storage::temporaryUrl($userProfile->profile_image_path, now()->addMinutes(60)) : null),
          'about_me' => $communityUserProfile ? $communityUserProfile->about_me : ($userProfile ? $userProfile->about_me : null),
          'url' => $communityUserProfile && $communityUserProfile->url ? json_decode($communityUserProfile->url) : null,
          'followers_count' => $followersCount,
          'following_count' => $followingCount,
          'following_status' => $followStatus['following'],
          'follower_status' => $followStatus['follower'],
          'posts' => $transformedPosts->toArray(),
        ];
      } else {
        $responseData = $posts->transform(function ($post) use ($loggedUserId) {
          $user = User::find($post->user_id);
          $userProfile = UserProfile::where('user_id', $post->user_id)->first();
          $communityUserProfile = CommunityUserProfile::where('user_id', $post->user_id)->first();

          $likes = json_decode($post->likes, true) ?? [];
          $post->likes_count = count($likes);

          $likedByUser = false;
          foreach ($likes as $like) {
            if ($like['user_id'] == $loggedUserId) {
              $likedByUser = true;
              break;
            }
          }
          $taggedUserIds = json_decode($post->tagged_users, true);
          $taggedUsers = [];
          if (is_array($taggedUserIds)) {
            foreach ($taggedUserIds as $taggedUserId) {
              $taggedUser = User::find($taggedUserId);
              if ($taggedUser) {
                $taggedUserProfile = UserProfile::where('user_id', $taggedUser->id)->first();
                $taggedUserCommunityProfile = CommunityUserProfile::where('user_id', $taggedUser->id)->first();
                $profileImagePath = $taggedUserCommunityProfile ? $taggedUserCommunityProfile->profile_image_path : ($taggedUserProfile ? $taggedUserProfile->profile_image_path : null);

                $taggedUsers[] = [
                  'id' => $taggedUser->id,
                  'username' => $taggedUser->username,
                  'profile_image_path' => $profileImagePath,
                  'media_url' => $profileImagePath ? Storage::temporaryUrl($profileImagePath, now()->addMinutes(60)) : null,
                ];
              }
            }
          }

          $mediaDetails = json_decode($post->media, true);
          if (is_array($mediaDetails)) {
            foreach ($mediaDetails as &$media) {
              if (isset($media['path']) && is_string($media['path'])) {
                $media['media_urls'] = Storage::temporaryUrl($media['path'], now()->addMinutes(60));
              }
            }
          } else {
            $mediaDetails = [];
          }

          $comments = CommunityPostComment::where('post_id', $post->id)->get();
          $commentCount = $comments->count();
          $replyCount = CommunityCommentReply::whereIn('comment_id', $comments->pluck('id'))->count();
          $totalCommentAndReplyCount = $commentCount + $replyCount;
          $userId = $post->user_id;
          $followStatus = getCommunityFollowStatus($loggedUserId, $userId);
          $COMMUNITY_URL = env('COMMUNITY_URL');

          return [
            'id' => $post->id,
            'user_id' => $user->id,
            'username' => $user->username,
            'visibility' => $post->visibility,
            'caption' => $post->caption,
            'media' => $post->media,
            'likes' => $post->likes,
            'likes_count' => $post->likes_count,
            'liked_by_user' => $likedByUser,
            'comment_count' => $totalCommentAndReplyCount,
            'shared_with' => json_decode($post->shared_with, true),
            'tagged_users' => $post->tagged_users,
            'location' => $post->location,
            'following_status' => $followStatus['following'],
            'follower_status' => $followStatus['follower'],
            'upload_time' => $post->upload_time,
            'deleted_at' => $post->deleted_at,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'profile_image_path' => $communityUserProfile ? $communityUserProfile->profile_image_path : ($userProfile ? $userProfile->profile_image_path : null),
            'media_url' => $communityUserProfile && $communityUserProfile->profile_image_path
              ? Storage::temporaryUrl($communityUserProfile->profile_image_path, now()->addMinutes(60))
              : ($userProfile && $userProfile->profile_image_path ? Storage::temporaryUrl($userProfile->profile_image_path, now()->addMinutes(60)) : null),
            'tagged_users_details' => $taggedUsers,
            'media_details' => $mediaDetails,
            'shareable_link' => $COMMUNITY_URL . '/community_post/' . $post->unique_link,
          ];
        });
      }

      DB::commit();
      return response()->json(['type' => 'success', 'status' => true, 'code' => 200, 'message' => 'Posts retrieved successfully.', 'data' => $responseData, 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['type' => 'error', 'status' => false, 'code' => 500, 'message' => 'An error occurred while retrieving posts: ' . $e->getMessage(), 'toast' => true]);
    }
  }

  public function getAllUsersPosts(Request $request)
  {
    DB::beginTransaction();
    try {
      $userIdFilter = $request->input('user_id');
      $loggedUserId = $request->attributes->get('user')->id;
      $query = CommunityPost::where('is_archived', false)->orderBy('created_at', 'desc');
      if ($userIdFilter) {
        $query->where('user_id', $userIdFilter);
      }

      $posts = $query->get();
      $userPosts = [];

      foreach ($posts as $post) {
        $userId = $post->user_id;

        $user = User::find($userId);
        $userProfile = UserProfile::where('user_id', $userId)->first();
        $communityUserProfile = CommunityUserProfile::where('user_id', $userId)->first();

        $followers = $userProfile ? json_decode($userProfile->c_followers, true) : [];
        $following = $userProfile ? json_decode($userProfile->c_following, true) : [];

        $followersCount = is_array($followers) ? count($followers) : 0;
        $followingCount = is_array($following) ? count($following) : 0;

        $followStatus = getCommunityFollowStatus($loggedUserId, $userId);

        if (!isset($userPosts[$userId])) {
          $profileImagePath = null;
          $aboutMe = null;
          $url = null;

          if ($communityUserProfile) {
            $profileImagePath = $communityUserProfile->profile_image_path;
            $aboutMe = $communityUserProfile->about_me;
            $url = $communityUserProfile->url ? json_decode($communityUserProfile->url, true) : null;
          } elseif ($userProfile) {
            $profileImagePath = $userProfile->profile_image_path;
            $aboutMe = $userProfile->about_me;
          }

          $profileImageTempUrl = $profileImagePath ? Storage::temporaryUrl($profileImagePath, now()->addMinutes(60)) : null;

          $userPosts[$userId] = [
            'user_id' => $userId,
            'username' => $user ? $user->username : null,
            'profile_image_path' => $profileImagePath,
            'profile_image_temp_url' => $profileImageTempUrl,
            'about_me' => $aboutMe,
            'url' => $url,
            'followers_count' => $followersCount,
            'following_count' => $followingCount,
            'following_status' => $followStatus['following'],
            'follower_status' => $followStatus['follower'],
            'posts' => [],
          ];
        }

        $likes = json_decode($post->likes, true) ?? [];
        $post->likes_count = count($likes);
        $likedByUser = false;
        foreach ($likes as $like) {
          if ($like['user_id'] == $loggedUserId) {
            $likedByUser = true;
            break;
          }
        }

        $taggedUserIds = json_decode($post->tagged_users, true);
        $taggedUsers = [];
        if (is_array($taggedUserIds)) {
          foreach ($taggedUserIds as $taggedUserId) {
            $taggedUser = User::find($taggedUserId);
            if ($taggedUser) {
              $taggedUserProfile = UserProfile::where('user_id', $taggedUser->id)->first();
              $taggedUserCommunityProfile = CommunityUserProfile::where('user_id', $taggedUser->id)->first();

              $taggedProfileImagePath = null;
              if ($taggedUserCommunityProfile && $taggedUserCommunityProfile->profile_image_path) {
                $taggedProfileImagePath = $taggedUserCommunityProfile->profile_image_path;
              } elseif ($taggedUserProfile) {
                $taggedProfileImagePath = $taggedUserProfile->profile_image_path;
              }

              $taggedProfileImageTempUrl = $taggedProfileImagePath ? Storage::temporaryUrl($taggedProfileImagePath, now()->addMinutes(60)) : null;

              $taggedUsers[] = [
                'id' => $taggedUser->id,
                'username' => $taggedUser->username,
                'profile_image_path' => $taggedProfileImagePath,
                'profile_image_temp_url' => $taggedProfileImageTempUrl,
              ];
            }
          }
        }

        $mediaDetails = json_decode($post->media, true);
        if (is_array($mediaDetails)) {
          foreach ($mediaDetails as &$media) {
            if (isset($media['path']) && is_string($media['path'])) {
              $media['media_urls'] = Storage::temporaryUrl($media['path'], now()->addMinutes(60));
            }
          }
        } else {
          $mediaDetails = [];
        }

        $comments = CommunityPostComment::where('post_id', $post->id)->get();
        $commentCount = $comments->count();
        $replyCount = CommunityCommentReply::whereIn('comment_id', $comments->pluck('id'))->count();
        $totalCommentAndReplyCount = $commentCount + $replyCount;

        $COMMUNITY_URL = env('COMMUNITY_URL');

        $userPosts[$userId]['posts'][] = [
          'id' => $post->id,
          'visibility' => $post->visibility,
          'caption' => $post->caption,
          'media' => $post->media,
          'likes' => $post->likes,
          'likes_count' => $post->likes_count,
          'liked_by_user' => $likedByUser,
          'comment_count' => $totalCommentAndReplyCount,
          'shared_with' => json_decode($post->shared_with, true),
          'tagged_users' => $post->tagged_users,
          'location' => $post->location,
          'upload_time' => $post->upload_time,
          'deleted_at' => $post->deleted_at,
          'created_at' => $post->created_at,
          'updated_at' => $post->updated_at,
          'tagged_users_details' => $taggedUsers,
          'media_details' => $mediaDetails,
          'shareable_link' => $COMMUNITY_URL . '/community_post/' . $post->unique_link,
        ];
      }
      // Include users with no posts in the response
      if ($userIdFilter) {
        if (!isset($userPosts[$userIdFilter])) {
          $user = User::find($userIdFilter);
          $userProfile = UserProfile::where('user_id', $userIdFilter)->first();
          $communityUserProfile = CommunityUserProfile::where('user_id', $userIdFilter)->first();

          $followers = $userProfile ? json_decode($userProfile->c_followers, true) : [];
          $following = $userProfile ? json_decode($userProfile->c_following, true) : [];

          $followersCount = is_array($followers) ? count($followers) : 0;
          $followingCount = is_array($following) ? count($following) : 0;

          $followStatus = getCommunityFollowStatus($loggedUserId, $userIdFilter);

          $profileImagePath = null;
          $aboutMe = null;
          $url = null;

          if ($communityUserProfile) {
            $profileImagePath = $communityUserProfile->profile_image_path;
            $aboutMe = $communityUserProfile->about_me;
            $url = $communityUserProfile->url ? json_decode($communityUserProfile->url, true) : null;
          } elseif ($userProfile) {
            $profileImagePath = $userProfile->profile_image_path;
            $aboutMe = $userProfile->about_me;
          }

          $profileImageTempUrl = $profileImagePath ? Storage::temporaryUrl($profileImagePath, now()->addMinutes(60)) : null;

          $userPosts[$userIdFilter] = [
            'user_id' => $userIdFilter,
            'username' => $user ? $user->username : null,
            'profile_image_path' => $profileImagePath,
            'profile_image_temp_url' => $profileImageTempUrl,
            'about_me' => $aboutMe,
            'url' => $url,
            'followers_count' => $followersCount,
            'following_count' => $followingCount,
            'following_status' => $followStatus['following'],
            'follower_status' => $followStatus['follower'],
            'posts' => [],
          ];
        }
      }

      $responseData = array_values($userPosts);

      DB::commit();
      return response()->json(['type' => 'success', 'status' => true, 'code' => 200, 'message' => 'Posts retrieved successfully.', 'data' => $responseData, 'toast' => true,]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error retrieving posts: ' . $e->getMessage());
      return response()->json(['type' => 'error', 'status' => false, 'code' => 500, 'message' => 'An error occurred while retrieving posts.', 'toast' => true,]);
    }
  }

  public function getPostByUniqueLink(Request $request)
  {
    try {
      $loggedUserId = $request->attributes->get('user')->id;
      Log::info('Logged User ID: ' . $loggedUserId);
      $unique_link = $request->unique_link;

      $post = CommunityPost::where('unique_link', $unique_link)->first();

      if (!$post) {
        return response()->json(['type' => 'error', 'status' => false, 'code' => 404, 'message' => 'Post not found.', 'toast' => true,]);
      }

      $user = User::find($post->user_id);
      $userProfile = UserProfile::where('user_id', $post->user_id)->first();
      $communityUserProfile = CommunityUserProfile::where('user_id', $post->user_id)->first();

      $profileImagePath = null;
      $profileImageTemporaryUrl = null;

      if ($communityUserProfile && !empty($communityUserProfile->profile_image_path)) {
        $profileImagePath = $communityUserProfile->profile_image_path;
      } elseif ($userProfile && !empty($userProfile->profile_image_path)) {
        $profileImagePath = $userProfile->profile_image_path;
      }

      if ($profileImagePath) {
        $profileImageTemporaryUrl = Storage::temporaryUrl($profileImagePath, now()->addMinutes(60));
      }

      $likes = json_decode($post->likes, true) ?? [];
      $post->likes_count = count($likes);

      // $likedByUser = false;
      // foreach ($likes as $like) {
      //   if ($like['user_id'] == $user->id) {
      //     $likedByUser = true;
      //     break;
      //   }
      // }
      $likedByUser = false;
      foreach ($likes as $like) {
        if ($like['user_id'] == $loggedUserId) {
          $likedByUser = true;
          break;
        }
      }
      $taggedUserIds = json_decode($post->tagged_users, true);
      $taggedUsers = [];
      if (is_array($taggedUserIds)) {
        foreach ($taggedUserIds as $taggedUserId) {
          $taggedUser = User::find($taggedUserId);
          if ($taggedUser) {
            $taggedUserProfile = UserProfile::where('user_id', $taggedUser->id)->first();
            $taggedCommunityProfile = CommunityUserProfile::where('user_id', $taggedUser->id)->first();

            $taggedUserProfileImage = null;
            $taggedUserProfileImageTemporaryUrl = null;

            if ($taggedCommunityProfile && !empty($taggedCommunityProfile->profile_image_path)) {
              $taggedUserProfileImage = $taggedCommunityProfile->profile_image_path;
            } elseif ($taggedUserProfile && !empty($taggedUserProfile->profile_image_path)) {
              $taggedUserProfileImage = $taggedUserProfile->profile_image_path;
            }

            if ($taggedUserProfileImage) {
              $taggedUserProfileImageTemporaryUrl = Storage::temporaryUrl($taggedUserProfileImage, now()->addMinutes(60));
            }
            $taggedUsers[] = [
              'id' => $taggedUser->id,
              'username' => $taggedUser->username,
              'profile_image_path' => $taggedUserProfileImage,
              'media_url' => $taggedUserProfileImageTemporaryUrl,
            ];
          }
        }
      }

      $mediaDetails = json_decode($post->media, true);
      if (is_array($mediaDetails)) {
        foreach ($mediaDetails as &$media) {
          if (isset($media['path']) && is_string($media['path'])) {
            $media['media_urls'] = Storage::temporaryUrl($media['path'], now()->addMinutes(60));
          }
        }
      } else {
        $mediaDetails = [];
      }
      $comments = CommunityPostComment::where('post_id', $post->id)->orderBy('created_at', 'desc')->get();
      $transformedComments = $comments->transform(function ($comment) {
        $commentUser = User::find($comment->user_id);
        $userProfile = UserProfile::where('user_id', $comment->user_id)->first();
        $communityUserProfile = CommunityUserProfile::where('user_id', $comment->user_id)->first();

        $profileImagePath = null;
        $profileImageTemporaryUrl = null;

        if ($communityUserProfile && !empty($communityUserProfile->profile_image_path)) {
          $profileImagePath = $communityUserProfile->profile_image_path;
        } elseif ($userProfile && !empty($userProfile->profile_image_path)) {
          $profileImagePath = $userProfile->profile_image_path;
        }

        if ($profileImagePath) {
          $profileImageTemporaryUrl = Storage::temporaryUrl($profileImagePath, now()->addMinutes(60));
        }

        $replies = CommunityCommentReply::where('comment_id', $comment->id)->get()->transform(function ($reply) {
          $replyUser = User::find($reply->user_id);
          $replyUserProfile = UserProfile::where('user_id', $reply->user_id)->first();
          $replyCommunityProfile = CommunityUserProfile::where('user_id', $reply->user_id)->first();

          $replyProfileImagePath = null;
          $replyProfileImageTemporaryUrl = null;

          if ($replyCommunityProfile && !empty($replyCommunityProfile->profile_image_path)) {
            $replyProfileImagePath = $replyCommunityProfile->profile_image_path;
          } elseif ($replyUserProfile && !empty($replyUserProfile->profile_image_path)) {
            $replyProfileImagePath = $replyUserProfile->profile_image_path;
          }

          if ($replyProfileImagePath) {
            $replyProfileImageTemporaryUrl = Storage::temporaryUrl($replyProfileImagePath, now()->addMinutes(60));
          }

          return [
            'id' => $reply->id,
            'comment_id' => $reply->comment_id,
            'user_id' => $reply->user_id,
            'reply' => $reply->reply,
            'created_at' => $reply->created_at,
            'updated_at' => $reply->updated_at,
            'username' => $replyUser->username,
            'profile_image_path' => $replyProfileImagePath,
            'media_url' => $replyProfileImageTemporaryUrl,
          ];
        });

        return [
          'id' => $comment->id,
          'post_id' => $comment->post_id,
          'user_id' => $comment->user_id,
          'comment' => $comment->comment,
          'reply_on_comment' => $replies,
          'created_at' => $comment->created_at,
          'updated_at' => $comment->updated_at,
          'username' => $commentUser->username,
          'profile_image_path' => $profileImagePath,
          'media_url' => $profileImageTemporaryUrl,
        ];
      });

      $comments = CommunityPostComment::where('post_id', $post->id)->get();
      $commentCount = $comments->count();
      $replyCount = 0;

      foreach ($comments as $comment) {
        $replyCount += CommunityCommentReply::where('comment_id', $comment->id)->count();
      }
      $totalCommentAndReplyCount = $commentCount + $replyCount;

      $userId = $post->user_id;
      $followStatus = getCommunityFollowStatus($loggedUserId, $userId);

      $COMMUNITY_URL = env('COMMUNITY_URL');

      return response()->json([
        'type' => 'success',
        'status' => true,
        'code' => 200,
        'message' => 'Post retrieved successfully.',
        'toast' => true,
        'data' => [
          'post' => [
            'id' => $post->id,
            'user_id' => $post->user_id,
            'visibility' => $post->visibility,
            'caption' => $post->caption,
            'media' => $post->media,
            'likes' => $post->likes,
            'likes_count' => $post->likes_count,
            'liked_by_user' => $likedByUser,
            'comment_count' => $totalCommentAndReplyCount,
            'shared_with' => json_decode($post->shared_with, true),
            'tagged_users' => $post->tagged_users,
            'location' => $post->location,
            'following_status' => $followStatus['following'],
            'follower_status' => $followStatus['follower'],
            'upload_time' => $post->upload_time,
            'deleted_at' => $post->deleted_at,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'username' => $user->username,
            'email' => $user->email,
            'profile_image_path' => $profileImagePath,
            'media_url' => $profileImageTemporaryUrl,
            'tagged_users_details' => $taggedUsers,
            'media_details' => $mediaDetails,
            'comments' => $transformedComments,
            'shareable_link' => $COMMUNITY_URL . '/community_post/' . $post->unique_link,
          ],
        ],
      ]);
    } catch (\Exception $e) {
      Log::error('Error retrieving post by unique link:', ['error' => $e->getMessage()]);
      return response()->json(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error occurred while retrieving the post', 'toast' => true,]);
    }
  }

  public function toggleLikePost(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $post_id = $request->post_id;
      $post = CommunityPost::where('id', $post_id)->first();

      if (!$post) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Post not found.', 'toast' => true]);
      }

      $likes = json_decode($post->likes, true) ?? [];
      $user_id = $user->id;

      $userAlreadyLiked = false;
      $index = null;

      foreach ($likes as $key => $like) {
        if ($like['user_id'] == $user_id) {
          $userAlreadyLiked = true;
          $index = $key;
          break;
        }
      }

      if ($userAlreadyLiked) {
        unset($likes[$index]);
        $message = 'Post disliked successfully.';
      } else {
        $likes[] = ['user_id' => $user_id];
        $message = 'Post liked successfully.';
        $authToken = $request->header('authToken');
        addNotification($post->user_id, $user_id, "{$user->username} liked your post.", "liked your post", null, "18", "/community_post/{$post->unique_link}", null, $authToken);
      }

      $post->likes = json_encode(array_values($likes));
      $post->save();

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Post like/dislike error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error on post like/dislike.', 'toast' => true]);
    }
  }
  public function addArchivePost(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $postId = $request->post_id;
      $post = CommunityPost::where('id', $postId)->where('user_id', $user->id)->first();

      if (!$post) {
        return response()->json(['type' => 'error', 'status' => false, 'code' => 404, 'message' => 'Post not found.']);
      }

      $post->is_archived = true;
      $post->save();

      DB::commit();
      return response()->json(['type' => 'success', 'status' => true, 'code' => 200, 'message' => 'Post archived successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['type' => 'error', 'status' => false, 'code' => 500, 'message' => 'Error archiving post: ' . $e->getMessage(), 'toast' => true]);
    }
  }
  public function unarchivePost(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $postId = $request->post_id;

      $post = CommunityPost::where('id', $postId)->where('user_id', $user->id)->where('is_archived', true)->first();

      if (!$post) {
        return response()->json(['type' => 'error', 'status' => false, 'code' => 404, 'message' => 'Archived post not found.', 'toast' => true]);
      }

      $post->is_archived = false;
      $post->save();

      DB::commit();
      return response()->json(['type' => 'success', 'status' => true, 'code' => 200, 'message' => 'Post restored from archive successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['type' => 'error', 'status' => false, 'code' => 500, 'message' => 'Error restoring post: ' . $e->getMessage(), 'toast' => true]);
    }
  }
  public function deleteArchivedPost(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $postId = $request->post_id;
      $post = CommunityPost::where('id', $postId)->where('user_id', $user->id)->where('is_archived', true)->first();

      if (!$post) {
        return response()->json(['type' => 'error', 'status' => false, 'code' => 404, 'message' => 'Archived post not found.', 'toast' => true]);
      }

      $post->delete();

      DB::commit();
      return response()->json(['type' => 'success', 'status' => true, 'code' => 200, 'message' => 'Archived post deleted successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['type' => 'error', 'status' => false, 'code' => 500, 'message' => 'Error deleting archived post: ' . $e->getMessage(), 'toast' => true]);
    }
  }

  public function reportPost(ReportPostRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $postId = $request->post_id;

      $CommunityPost = CommunityPost::findOrFail($postId);
      if ($CommunityPost->user_id == $user->id) {
        return generateResponse(['type' => 'error', 'code' => 403, 'status' => false, 'message' => 'You cannot report your own post.', 'toast' => true]);
      }

      $reports = json_decode($CommunityPost->report_reason, true) ?? [];

      $reports[] = [
        'user_id' => $user->id,
        'reason' => $request->report_reason,
        'reported_at' => now()->toDateTimeString()
      ];

      $CommunityPost->report_reason = json_encode($reports);
      $CommunityPost->save();

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Post reported successfully.', 'toast' => true], ['reports' => $reports]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Post not found.', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Report add error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error processing the request', 'toast' => true]);
    }
  }
  public function users(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $user_id = isset($request->user_id) && is_numeric($request->user_id) ? $request->user_id : null;

      if ($user_id) {
        $user = User::selectRaw('username,email,id')->with('profile')->where("id", $user_id)->first();
        if ($user) {
          $profile_path = null;

          $communityUserProfile = CommunityUserProfile::where('user_id', $user_id)->first();
          $userProfile = UserProfile::where('user_id', $user_id)->first();

          if ($communityUserProfile && !empty($communityUserProfile->profile_image_path)) {
            $profile_path = getFileTemporaryURL($communityUserProfile->profile_image_path);
          } elseif ($userProfile && !empty($userProfile->profile_image_path)) {
            $profile_path = getFileTemporaryURL($userProfile->profile_image_path);
          }

          $user = $user->toArray();
          $user['profile_image_path'] = $profile_path;
          unset($user['profile']);
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Users retrieved successfully', 'toast' => true], ["user" => $user]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No user found', 'toast' => true]);
        }
      }

      $limit = isset($request->limit) && is_numeric($request->limit) && $request->limit > 0 ? $request->limit : 10;
      $page = isset($request->page) && is_numeric($request->page) && $request->page > 0 ? $request->page : 1;
      $search = isset($request->search) ? $request->search : "";
      $offset = ($page - 1) * $limit;

      $query = User::query();
      if ($search) {
        $query->where('username', 'like', "%$search%");
      }
      $query->offset($offset)->limit($limit);
      $query = $query->where("verify_email", "1")->whereNot("id", $user->id);
      $users = $query->selectRaw("id,username,email")->with('profile')->get();
      DB::commit();

      if ($users->isNotEmpty()) {
        $users->transform(function ($user) {
          $profile_path = null;

          $communityUserProfile = CommunityUserProfile::where('user_id', $user->id)->first();
          $userProfile = UserProfile::where('user_id', $user->id)->first();

          if ($communityUserProfile && !empty($communityUserProfile->profile_image_path)) {
            $profile_path = getFileTemporaryURL($communityUserProfile->profile_image_path);
          } elseif ($userProfile && !empty($userProfile->profile_image_path)) {
            $profile_path = getFileTemporaryURL($userProfile->profile_image_path);
          }

          $user->profile_image_path = $profile_path;
          unset($user->profile);
          return $user;
        });

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Users retrieved successfully', 'toast' => true], ["users" => $users]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Users not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Community users Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while retrieving users', 'toast' => true]);
    }
  }

  public function setCommunityProfile(SetCommunityProfileRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');

      $userProfile = CommunityUserProfile::where('user_id', $user->id)->first();

      if (!$userProfile) {
        $userProfile = new CommunityUserProfile();
        $userProfile->user_id = $user->id;
      }

      if ($request->has('profile_image_path')) {
        if ($request->profile_image_path === null) {
          $userProfile->profile_image_path = null;
        } elseif ($request->hasFile('profile_image_path')) {
          $uploadFile = $request->file('profile_image_path');
          $fileName = $user->id . '_' . uniqid() . '.' . $uploadFile->getClientOriginalExtension();
          $filePath = "users/private/{$user->id}/CommunityUserProfile/{$fileName}";
          Storage::put($filePath, file_get_contents($uploadFile));
          $userProfile->profile_image_path = $filePath;
        }
      }
      if ($request->has('about_me')) {
        $userProfile->about_me = $request->about_me;
      }
      if ($request->has('gender')) {
        $userProfile->gender = $request->gender;
      }

      if ($request->has('url')) {
        $newUrls = json_decode($request->url, true);
        $existingUrls = $userProfile->url ? json_decode($userProfile->url, true) : [];

        $existingUrlMap = [];
        foreach ($existingUrls as $index => $existingUrl) {
          $existingUrlMap[strtolower($existingUrl['title']) . '|' . strtolower($existingUrl['url'])] = $index;
        }

        $updatedUrls = [];

        foreach ($newUrls as $newUrl) {
          $key = strtolower($newUrl['title']) . '|' . strtolower($newUrl['url']);
          if (isset($existingUrlMap[$key])) {

            $index = $existingUrlMap[$key];
            $existingUrls[$index]['title'] = $newUrl['title'];
            $existingUrls[$index]['url'] = $newUrl['url'];
          } else {

            $existingUrls[] = $newUrl;
          }

          $updatedUrls[] = $key;
        }

        $existingUrls = array_filter($existingUrls, function ($existingUrl) use ($updatedUrls) {
          $key = strtolower($existingUrl['title']) . '|' . strtolower($existingUrl['url']);
          return in_array($key, $updatedUrls);
        });

        $userProfile->url = json_encode(array_values($existingUrls));
      }

      $userProfile->save();

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User profile updated', 'toast' => true, 'data' => $userProfile->toArray()]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::info('profile Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
