<?php

namespace App\Http\Controllers\API\V1\Podcast;

use App\Http\Controllers\Controller;
use App\Models\Podcast\Artist;
use App\Models\Podcast\Episode;
use App\Models\Podcast\Podcasts;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ArtistController extends Controller
{

  public function getArtistById($id)
  {
    try {
      $artist = Artist::join('users', 'users.id', '=', 'podcast_artist.user_id')
        ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
        ->where('podcast_artist.id', $id)
        ->select(
          'podcast_artist.artist_name',
          'podcast_artist.artist_cover_image',
          'podcast_artist.artist_bio',
          'user_profiles.profile_image_path',
          'podcast_artist.user_id'
        )
        ->firstOrFail();
      $artistProfile = [
        'cover_image' => $artist->artist_cover_image ? url($artist->artist_cover_image) : asset('assets/images/default_profile/music_cover.jpg'),
        'profile_image' => $artist->profile_image_path ? getFileTemporaryURL($artist->profile_image_path) : asset('assets/images/default_profile/profile.png'),
        'artist_name' => $artist->artist_name,
        'artist_bio' => $artist->artist_bio,
      ];

      $rankedEpisodes = Episode::join('podcasts', 'podcast_episodes.podcast_id', '=', 'podcasts.id')
        ->where('podcasts.user_id', $artist->user_id)
        ->orderBy('podcast_episodes.listened', 'desc')
        ->select(
          'podcast_episodes.image_url as episode_image',
          'podcast_episodes.title as episode_title',
          'podcast_episodes.audio_url',
          'podcast_episodes.duration',
          'podcasts.title as podcast_title'
        )
        ->get()
        ->map(function ($episode, $index) {
          return [
            'sr_no' => $index + 1,
            'episode_image' => $episode->episode_image ? url($episode->episode_image) : null,
            'title' => $episode->episode_title,
            'audio_url' => $episode->audio_url ? url($episode->audio_url) : null,
            'podcast_title' => $episode->podcast_title,
            'duration' => $episode->duration,
          ];
        });

      $popularEpisodes = Episode::join('podcasts', 'podcast_episodes.podcast_id', '=', 'podcasts.id')
        ->where('podcasts.user_id', $artist->user_id)
        ->orderBy('podcast_episodes.listened', 'desc')
        ->limit(5)
        ->select(
          'podcast_episodes.image_url as episode_image',
          'podcasts.image_url as podcast_image',
          'podcast_episodes.audio_url',
          'podcast_episodes.title as episode_title'
        )
        ->get()
        ->map(function ($episode) {
          return [
            'episode_image' => $episode->episode_image ? url($episode->episode_image) : null,
            'podcast_image' => $episode->podcast_image ? url($episode->podcast_image) : null,
            'audio_url' => $episode->audio_url ? url($episode->audio_url) : null,
            'title' => $episode->episode_title,
          ];
        });

      $recentEpisodes = Episode::join('podcasts', 'podcast_episodes.podcast_id', '=', 'podcasts.id')
        ->where('podcasts.user_id', $artist->user_id)
        ->orderBy('podcast_episodes.created_at', 'desc')
        ->select(
          'podcast_episodes.image_url as episode_image',
          'podcast_episodes.title as episode_title',
          'podcast_episodes.audio_url',
          'podcast_episodes.duration',
          'podcasts.title as podcast_title'
        )
        ->get()
        ->map(function ($episode, $index) {
          return [
            'sr_no' => $index + 1,
            'episode_image' => $episode->episode_image ? url($episode->episode_image) : null,
            'title' => $episode->episode_title,
            'audio_url' => $episode->audio_url ? url($episode->audio_url) : null,
            'podcast_title' => $episode->podcast_title,
            'duration' => $episode->duration,
          ];
        });
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Artist fetched successfully',
        'toast' => true,
      ], [
        'artist_data' => [
          'artist_profile' => $artistProfile,
          'ranked_episodes' => $rankedEpisodes,
          'most_popular_episodes' => $popularEpisodes,
          'recent_episodes' => $recentEpisodes,
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Error fetching artist data: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching data: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function updateArtist(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userID = $user->id;
      $artist = Artist::where('user_id', $userID)->first();

      if (!$artist) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Artist profile not found',
          'toast' => true,
        ]);
      }

      if ($request->filled('artist_name')) {
        $artist->artist_name = $request->input('artist_name');
      }

      if ($request->filled('artist_bio')) {
        $artist->artist_bio = $request->input('artist_bio');
      }

      if ($request->hasFile('artist_image')) {
        $artistImageFile = $request->file('artist_image');
        $artistImageName = $artistImageFile->getClientOriginalName();
        $artistImagePath = "podcast/{$userID}/artist/{$artistImageName}";
        //  Storage::put($artistImagePath, file_get_contents($artistImageFile));
        $artistImageFile->move("podcast/{$userID}/artist", $artistImageName);

        $artist->artist_image = $artistImagePath;
      }

      if ($request->hasFile('artist_cover_image')) {
        $coverImageFile = $request->file('artist_cover_image');
        $coverImageName = $coverImageFile->getClientOriginalName();
        $coverImagePath = "podcast/{$userID}/artist/{$coverImageName}";
        //  Storage::put($coverImagePath, file_get_contents($coverImageFile));
        $coverImageFile->move("podcast/{$userID}/artist", $coverImageName);
        $artist->artist_cover_image = $coverImagePath;
      }

      $artist->save();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Profile updated successfully',
        'toast' => true,
      ], ['artist' => $artist]);
    } catch (\Exception $e) {
      Log::error('Error updating profile: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error updating profile: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function getArtist()
  {
    try {
      $artists = Artist::join('user_profiles', 'podcast_artist.user_id', '=', 'user_profiles.user_id')
        ->select(
          'podcast_artist.artist_name',
          'user_profiles.profile_image_path',
          'podcast_artist.followers_count',
          'podcast_artist.total_podcasts',
          'podcast_artist.artist_bio',
          'podcast_artist.id'
        )
        ->get();

      if ($artists->isEmpty()) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'No artists found',
          'toast' => true,
        ]);
      }
      $artists = $artists->map(function ($artist, $index) {
        $artist->srno = $index + 1;
        $artist->profile_image_path = $artist->profile_image_path ? getFileTemporaryURL($artist->profile_image_path) : asset('assets/images/default_profile/profile.png');
        $artist->id = $artist->id;
        return $artist;
      });

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Artists fetched successfully',
        'toast' => true,
      ], ['artists' => $artists]);
    } catch (\Exception $e) {
      Log::error('Error fetching artists: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching artists: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function toggleFollowArtist(Request $request, $id)
  {
    try {
      $user = $request->attributes->get('user');
      $userID = $user->id;
      $artist = Artist::find($id);
      if (!$artist) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Artist not found',
          'toast' => true,
        ]);
      }
      $usersFollowing = $artist->users_following ? explode(',', $artist->users_following) : [];

      if (in_array($userID, $usersFollowing)) {
        $usersFollowing = array_diff($usersFollowing, [$userID]);
        $artist->followers_count = max(0, $artist->followers_count - 1);
        $message = 'Unfollowed successfully';
      } else {
        $usersFollowing[] = $userID;
        $artist->followers_count++;
        $message = 'Followed successfully';
      }
      $artist->users_following = implode(',', $usersFollowing);
      $artist->save();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => $message,
        'toast' => true,
      ]);
    } catch (\Exception $e) {
      Log::error('Error toggling follow status: ' . $e->getMessage());

      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error toggling follow status: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }
  public function getFollowPodcastList(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      if (!$user || !isset($user->id)) {
        return response()->json(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User Not found', 'toast' => true]);
      }

      $followEpisodes = Artist::where('users_following', 'like', "%{$user->id}%")
        ->get();
      if ($followEpisodes->isEmpty()) {
        return response()->json(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'No follow podcasts found for this user', 'toast' => true]);
      }

      $followEpisodes->transform(function ($episode) {
        $episode->artist_image = url($episode->artist_image);
        $episode->artist_cover_image = url($episode->artist_cover_image);
        return $episode;
      });

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Followed podcast list retrieved successfully', 'toast' => true, 'data' => $followEpisodes]);
    } catch (\Exception $e) {
      Log::error($e->getMessage());
      return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
