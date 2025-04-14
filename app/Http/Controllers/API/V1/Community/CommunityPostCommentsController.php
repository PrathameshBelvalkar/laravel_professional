<?php

namespace App\Http\Controllers\API\V1\Community;

use Illuminate\Http\Request;
use App\Models\CommunityPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\CommunityPostComment;
use App\Models\CommunityCommentReply;
use App\Models\CommunityUserProfile;

class CommunityPostCommentsController extends Controller
{
  public function addcomment(Request $request)
  {

    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $postId = $request->post_id;

      $CommunityPost = CommunityPost::findOrFail($postId);
      if ($CommunityPost) {
        $CommunityPostComments = new CommunityPostComment();
        $CommunityPostComments->comment = $request->comment;
        $CommunityPostComments->post_id = $postId;
        $CommunityPostComments->user_id = $user->id;
        $commentData = $CommunityPostComments->save();

        $postOwnerId = $CommunityPost->user_id;

        $authToken = $request->header('authToken');
        addNotification($postOwnerId, $user->id, "{$user->username} commented on your post.", "Tag", null, "18", "/community_post/{$CommunityPost->unique_link}", null, $authToken);

        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Comment added successfully. ', 'toast' => true], ['postData' => $commentData]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Post not found.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Comment add error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error processing the request', 'toast' => true]);
    }
  }

  public function getcomments(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $post_id = $request->post_id;
      $CommunityPostComments = CommunityPostComment::where('post_id', $post_id)->orderBy('created_at', 'desc')->get();

      if ($CommunityPostComments->isNotEmpty()) {
        $transformedComments = $CommunityPostComments->transform(function ($comment) {
          $commentUser = User::find($comment->user_id);
          $userProfile = UserProfile::where('user_id', $comment->user_id)->first();
          $communityUserProfile = CommunityUserProfile::where('user_id', $comment->user_id)->first();

          $replies = CommunityCommentReply::where('comment_id', $comment->id)->get()->transform(function ($reply) {
            $replyUser = User::find($reply->user_id);
            $replyUserProfile = UserProfile::where('user_id', $reply->user_id)->first();
            $communityReplyUserProfile = CommunityUserProfile::where('user_id', $reply->user_id)->first();

            $replyProfileImagePath = null;
            $replyTempUrl = null;
            if ($communityReplyUserProfile && $communityReplyUserProfile->profile_image_path) {
              $replyProfileImagePath = $communityReplyUserProfile->profile_image_path;
              $replyTempUrl = Storage::temporaryUrl($replyProfileImagePath, now()->addMinutes(60));
            } elseif ($replyUserProfile && $replyUserProfile->profile_image_path) {
              $replyProfileImagePath = $replyUserProfile->profile_image_path;
              $replyTempUrl = Storage::temporaryUrl($replyProfileImagePath, now()->addMinutes(60));
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
              'media_url' => $replyTempUrl,
            ];
          });

          $profileImagePath = null;
          $tempUrl = null;
          if ($communityUserProfile && $communityUserProfile->profile_image_path) {
            $profileImagePath = $communityUserProfile->profile_image_path;
            $tempUrl = Storage::temporaryUrl($profileImagePath, now()->addMinutes(60));
          } elseif ($userProfile && $userProfile->profile_image_path) {
            $profileImagePath = $userProfile->profile_image_path;
            $tempUrl = Storage::temporaryUrl($profileImagePath, now()->addMinutes(60));
          }
          return [
            'id' => $comment->id,
            'post_id' => $comment->post_id,
            'user_id' => $comment->user_id,
            'comment' => $comment->comment,
            'reply_on_comment' => $comment->reply_on_comment,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
            'username' => $commentUser->username,
            'profile_image_path' => $profileImagePath,
            'media_url' => $tempUrl,
            'reply_on_comment' => $replies,
          ];
        });

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Comments retrieved successfully', 'toast' => true], ['comments' => $transformedComments]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Comments not found.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::error('Error fetching comments: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error fetching comments.', 'toast' => true]);
    }
  }

  public function commentreply(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $commentId = $request->comment_id;
      $replyText = $request->reply;

      $comment = CommunityPostComment::findOrFail($commentId);
      $communityPost = CommunityPost::findOrFail($comment->post_id);
      $reply = new CommunityCommentReply();
      $reply->comment_id = $commentId;
      $reply->user_id = $user->id;
      $reply->reply = $replyText;
      $reply->save();
      $authToken = $request->header('authToken');
      addNotification($comment->user_id, $user->id, "{$user->username} mentioned you in a comment.", "mentioned you in a comment", null, "18", "/community_post/{$communityPost->unique_link}", null, $authToken);
      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Reply added successfully.', 'toast' => true], ['replyData' => $reply]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Comment reply error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error on reply.', 'toast' => true]);
    }
  }

  public function deleteCommentOrReply(Request $request)
  {
    DB::beginTransaction();
    try {

      $userId = $request->attributes->get('user')->id;

      if ($request->filled('comment_id')) {
        $commentId = $request->comment_id;
        $comment = CommunityPostComment::find($commentId);

        if ($comment) {

          $post = CommunityPost::find($comment->post_id);

          if ($post->user_id == $userId || $comment->user_id == $userId) {
            $comment->delete();
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Comment deleted successfully...!!', 'toast' => true]);
          } else {
            return generateResponse(['type' => 'error', 'code' => 403, 'status' => false, 'message' => 'You are not authorized to delete this comment.', 'toast' => true]);
          }
        } else {
          return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Comment not found...!!', 'toast' => true]);
        }
      } elseif ($request->filled('reply_id')) {
        $replyId = $request->reply_id;
        $reply = CommunityCommentReply::find($replyId);

        if ($reply) {

          $comment = CommunityPostComment::find($reply->comment_id);
          $post = CommunityPost::find($comment->post_id);

          if ($post->user_id == $userId || $reply->user_id == $userId) {
            $reply->delete();
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Reply deleted successfully...!!', 'toast' => true]);
          } else {
            return generateResponse(['type' => 'error', 'code' => 403, 'status' => false, 'message' => 'You are not authorized to delete this reply.', 'toast' => true]);
          }
        } else {
          return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Reply not found...!!', 'toast' => true]);
        }
      } else {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Invalid request. Please provide a comment_id or reply_id.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Delete error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error occurred while deleting.', 'toast' => true]);
    }
  }


  public function toggleCommentLike(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $comment_id = $request->comment_id;

      if (!is_numeric($comment_id)) {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Invalid comment ID.', 'toast' => true]);
      }

      $CommunityPostComment = CommunityPostComment::find($comment_id);

      if (!$CommunityPostComment) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Comment not found.', 'toast' => true]);
      }
      $communityPost = CommunityPost::findOrFail($CommunityPostComment->post_id);

      $likes = json_decode($CommunityPostComment->like_dislike, true) ?? [];

      $index = array_search($user->id, array_column($likes, 'user_id'));

      if ($index !== false) {
        unset($likes[$index]);
        $CommunityPostComment->like_dislike = json_encode(array_values($likes));
        $CommunityPostComment->save();
        DB::commit();

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Comment disliked successfully.', 'toast' => true]);
      } else {
        $likes[] = ['user_id' => $user->id];
        $CommunityPostComment->like_dislike = json_encode($likes);
        $CommunityPostComment->save();

        $authToken = $request->header('authToken');
        addNotification($CommunityPostComment->user_id, $user->id, "{$user->username} liked your comment.", "liked your comment.", null, "18", "/community_post/{$communityPost->unique_link}", null, $authToken);
        DB::commit();

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Comment liked successfully.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Comment toggle like error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error processing comment like/dislike.', 'toast' => true]);
    }
  }
  public function toggleLikeReply(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $reply_id = $request->reply_id;
      $reply = CommunityCommentReply::where('id', $reply_id)->first();

      if (!$reply) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Reply not found.', 'toast' => true]);
      }
      $comment = CommunityPostComment::findOrFail($reply->comment_id);
      $communityPost = CommunityPost::findOrFail($comment->post_id);

      $likes = json_decode($reply->like_dislike, true) ?? [];
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
        $message = 'Reply disliked successfully.';
      } else {
        $likes[] = ['user_id' => $user_id];
        $message = 'Reply liked successfully.';

        $authToken = $request->header('authToken');
        addNotification($reply->user_id,  $user->id, "{$user->username} liked your comment.", "liked your comment.", null, "18", "/community_post/{$communityPost->unique_link}", null, $authToken);
      }

      $reply->like_dislike = json_encode(array_values($likes));
      $reply->save();

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Reply like/dislike error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error on reply like/dislike.', 'toast' => true]);
    }
  }

  public function getCommentReplyLikesCount(Request $request)
  {
    try {
      if ($request->filled('comment_id')) {
        $commentId = $request->comment_id;
        $comment = CommunityPostComment::find($commentId);

        if (!$comment) {
          return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Comment not found.', 'toast' => true]);
        }

        $likes = json_decode($comment->like_dislike, true) ?? [];
        $likesCount = count($likes);

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Likes on comment..!!', 'toast' => true], ['likes_count' => $likesCount]);
      } elseif ($request->filled('reply_id')) {
        $replyId = $request->reply_id;
        $reply = CommunityCommentReply::find($replyId);

        if (!$reply) {
          return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Reply not found.', 'toast' => true]);
        }

        $likes = json_decode($reply->like_dislike, true) ?? [];
        $likesCount = count($likes);

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Likes on reply..!!', 'toast' => true], ['likes_count' => $likesCount]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Invalid request. Please provide a comment_id or reply_id.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::error('Get likes count error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error getting likes count..!!', 'toast' => true]);
    }
  }
}
