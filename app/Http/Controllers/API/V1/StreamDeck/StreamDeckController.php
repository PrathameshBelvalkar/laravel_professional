<?php

namespace App\Http\Controllers\API\V1\StreamDeck;

use getID3;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;
use Dompdf\Dompdf;
use FFMpeg\FFMpeg;
use Dompdf\Options;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Website;
// use App\Models\TvSeries;
use App\Models\Schedular;
use App\Models\LiveStream;
use App\Models\VideoStream;
use Illuminate\Support\Str;
use App\Exports\ChartExport;
use Illuminate\Http\Request;
use FFMpeg\Format\Video\X264;
use Illuminate\Http\Response;
use FFMpeg\Coordinate\TimeCode;
use App\Models\StreamDeck\Videos;
use App\Models\StreamDeck\Channel;
// use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\RequestWrapper;
use App\Models\StreamDeck\TvLivestream;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\StreamDeck\RecordedVideos;
use Illuminate\Support\Facades\Validator;
use App\Models\StreamDeck\ChannelsContent;
use App\Models\StreamDeck\ChannelsSchedule;
use App\Models\StreamDeck\ScheduleChannles;
use Illuminate\Console\Scheduling\Schedule;
// use App\Http\Requests\CreateTvSeriesRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StreamDeck\CreateWebsite;
use App\Http\Requests\StreamDeck\UpdateWebsite;
use Pawlox\VideoThumbnail\Facade\VideoThumbnail;
use App\Http\Requests\StreamDeck\StreamDeckVideos;
use App\Models\StreamDeck\ChannelScheduleOperation;
use App\Http\Requests\StreamDeck\UpdateVideoRequest;
use App\Http\Requests\StreamDeck\UploadVideoRequest;
use ProtoneMedia\LaravelFFMpeg\Exporters\HLSExporter;
use App\Http\Requests\StreamDeck\CreateChannelRequest;
use App\Http\Requests\StreamDeck\StreamDeckAddRequest;
use App\Http\Requests\StreamDeck\UpdateChannelRequest;
use App\Http\Requests\StreamDeck\UpdateLiveStreamRequest;
use App\Http\Requests\StreamDeck\ChannelsContentAddRequest;
use App\Http\Requests\StreamDeck\StreamDeckScheduleAddRequest;

class StreamDeckController extends Controller
{
	public function createchannel(CreateChannelRequest $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$channel = new Channel();
			$channel->user_id = $user->id;
			if ($request->filled('channel_name')) {
				$channel->channel_name = $request->channel_name;
			}
			if ($request->filled('channel_type')) {
				$channel->channel_type = $request->channel_type;
			}
			if ($request->filled('linear_channel_type')) {
				$channel->linear_channel_type = $request->linear_channel_type;
			}
			if ($request->filled('schedule_duration')) {
				$channel->schedule_duration = $request->schedule_duration;
			}
			if ($request->filled('start_time')) {
				$channel->start_time = $request->start_time;
			}
			if ($request->filled('logo_on_off')) {
				$channel->logo_on_off = $request->logo_on_off;
			}
			if ($request->logo_on_off === '1') {
				if ($request->filled('logo_position')) {
					$channel->logo_position = $request->logo_position;
				}
				if ($request->filled('main_color')) {
					$channel->main_color = $request->main_color;
				}
				if ($request->hasFile('logo')) {
					$file = $request->file('logo');
					$fileName = $file->getClientOriginalName();
					$filePath = "users/private/{$user->id}/streamdeck/{$fileName}";
					Storage::put($filePath, file_get_contents($file));
					$channel->logo = $filePath;
				}
				if ($request->filled('base_64_logo')) {
					$base64Logo = $request->input('base_64_logo');
					preg_match('/^data:image\/(\w+);base64,/', $base64Logo, $matches);
					$imageExtension = $matches[1];
					$base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Logo);
					$decodedData = base64_decode($base64Data);

					$fileName = 'logo_' . time() . '.' . $imageExtension;
					$filePath = "users/private/{$user->id}/streamdeck/{$fileName}";

					Storage::put($filePath, $decodedData);
					$channel->logo = $filePath;
				}
				if ($request->filled('logo_link')) {
					$channel->logo = $request->logo_link;
				}
			}
			if ($request->filled('channel_embedded')) {
				$channel->channel_embedded = $request->channel_embedded;
			}
			if ($request->filled('add_tag_url')) {
				$channel->add_tag_url = $request->add_tag_url;
			}
			if ($request->filled('no_of_adds_in_hour')) {
				$channel->no_of_adds_in_hour = $request->no_of_adds_in_hour;
			}
			if ($request->filled('Seconds_per_add_break')) {
				$channel->Seconds_per_add_break = $request->Seconds_per_add_break;
			}
			if ($request->filled('views')) {
				$channel->views = $request->views;
			}
			$channel->channelUuid = Str::uuid();
			$channel->save();
			addNotification($user->id, $user->id, "New Channel Created", "You Have created " . $request->channel_name . " channel", $channel->id, "4", '/channels',);
			DB::commit();
			$newChannel = Channel::where('id', $channel->id)->first();
			$channel_data = [
				'id' => $newChannel->id,
				'channel_name' => $newChannel->channel_name,
				'channel_type' => $newChannel->channel_type,
				'linear_channel_type' => $newChannel->linear_channel_type,
				'schedule_duration' => $newChannel->schedule_duration,
				'start_time' => $newChannel->start_time,
				'logo_position' => $newChannel->logo_position,
				'logo_on_off' => $newChannel->logo_on_off,
				'main_color' => $newChannel->main_color,
				'channel_embedded' => $newChannel->channel_embedded,
				'add_tag_url' => explode(',', $newChannel->add_tag_url),
				'no_of_adds_in_hour' => $newChannel->no_of_adds_in_hour,
				'Seconds_per_add_break' => $newChannel->Seconds_per_add_break,
				'logo' => $newChannel->logo,
				'views' => $newChannel->views,
			];
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Channel added successfully.', 'toast' => true], ['channel' => $channel_data]);
		} catch (\Exception $e) {
			Log::info('Error while processing channel: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing channel.', 'toast' => true]);
		}
	}
	public function updatechannel(UpdateChannelRequest $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$id = $request->input('channel_id');
			$channel = Channel::where('user_id', $user->id)->where('id', $id)->first();

			if (!$channel) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'Channel not found.',
					'toast' => true
				]);
			}

			if ($request->filled('channel_name')) {
				$channel->channel_name = $request->channel_name;
			}
			if ($request->filled('channel_type')) {
				$channel->channel_type = $request->channel_type;
			}
			if ($request->filled('linear_channel_type')) {
				$channel->linear_channel_type = $request->linear_channel_type;
			}
			if ($request->filled('schedule_duration')) {
				$channel->schedule_duration = $request->schedule_duration;
			}
			if ($request->filled('start_time')) {
				$channel->start_time = $request->start_time;
			}
			if ($request->filled('logo_on_off')) {
				$channel->logo_on_off = $request->logo_on_off;
			}
			if ($request->logo_on_off === '1') {
				if ($request->filled('logo_position')) {
					$channel->logo_position = $request->logo_position;
				}
				if ($request->filled('main_color')) {
					$channel->main_color = $request->main_color;
				}
				if ($request->hasFile('logo')) {
					if ($channel->logo) {
						Storage::delete($channel->logo);
					}
					$file = $request->file('logo');
					$fileName = $file->getClientOriginalName();
					$filePath = "users/private/{$user->id}/streamdeck/{$fileName}";
					Storage::put($filePath, file_get_contents($file));
					$channel->logo = $filePath;
				}
				if ($request->filled('logo_link')) {
					$channel->logo = $request->logo_link;
				}
			}
			if ($request->filled('channel_embedded')) {
				$channel->channel_embedded = $request->channel_embedded;
			}
			if ($request->filled('add_tag_url')) {
				$channel->add_tag_url = $request->add_tag_url;
			}
			if ($request->filled('no_of_adds_in_hour')) {
				$channel->no_of_adds_in_hour = $request->no_of_adds_in_hour;
			}
			if ($request->filled('Seconds_per_add_break')) {
				$channel->Seconds_per_add_break = $request->Seconds_per_add_break;
			}
			$channel->save();
			DB::commit();
			$updated_channel = Channel::where('id', $id)->first();
			$updated_channel_data = [
				'id' => $updated_channel->id,
				'channel_name' => $updated_channel->channel_name,
				'channel_type' => $updated_channel->channel_type,
				'linear_channel_type' => $updated_channel->linear_channel_type,
				'schedule_duration' => $updated_channel->schedule_duration,
				'start_time' => $updated_channel->start_time,
				'logo_position' => $updated_channel->logo_position,
				'logo_on_off' => $updated_channel->logo_on_off,
				'main_color' => $updated_channel->main_color,
				'channel_embedded' => $updated_channel->channel_embedded,
				'add_tag_url' => explode(',', $updated_channel->add_tag_url),
				'no_of_adds_in_hour' => $updated_channel->no_of_adds_in_hour,
				'Seconds_per_add_break' => $updated_channel->Seconds_per_add_break,
				'logo' => $updated_channel->logo,
			];
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Channel updated successfully.', 'toast' => true], ['channel' => $updated_channel_data]);
		} catch (\Exception $e) {
			Log::info('Error while updating channel: ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error updating channel.', 'toast' => true]);
		}
	}
	public function deletechannel(Request $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$channel_id = $request->input('channel_id');
			$channel = Channel::where('user_id', $user->id)->where('id', $channel_id)->first();

			if (!$channel) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'Id not found.',
					'toast' => true
				]);
			}

			// Check if the channel is in tv_livestreams with today's date
			$today = date('Y-m-d');
			$isBroadcastingToday = TvLivestream::where('channel_id', $channel_id)
				->where('date', $today)
				->where('status', '1')
				->exists();

			if ($isBroadcastingToday) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'You can\'t delete the channel because it is being broadcasted today.',
					'toast' => true
				], [
					'broadcasting_today' => true
				]);
			}

			// Proceed with deleting the channel
			$logoPath = $channel->logo;

			if ($logoPath) {
				Storage::delete($logoPath);
			}
			$channel->delete();

			DB::commit();
			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Channel deleted successfully.',
				'toast' => true
			]);
		} catch (\Exception $e) {
			Log::info('Error while deleting channel: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Error deleting channel.',
				'toast' => true
			]);
		}
	}

	public function getchannel(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$channelId = $request->input('channel_id');
			$epgChannelId = $request->input('epgchannel');

			$destinationChannels = DB::table('live_stream')
				->where('user_id', $user->id)
				->pluck('destination_id')
				->toArray();

			$destinationChannelsArray = [];
			foreach ($destinationChannels as $channels) {
				$destinationChannelsArray = array_merge($destinationChannelsArray, explode(',', $channels));
			}
			$destinationChannelsArray = array_map('trim', $destinationChannelsArray);
			$destinationChannelsArray = array_unique($destinationChannelsArray);

			if ($channelId) {
				$channel = DB::table('channels')
					->where('user_id', $user->id)
					->where('id', $channelId)
					->first();
				if (!$channel) {
					return generateResponse([
						'type' => 'error',
						'code' => 200,
						'status' => false,
						'message' => 'Channel not found.',
						'toast' => true
					]);
				}
				$channels = collect([$channel]);
			} else {
				$channels = DB::table('channels')
					->where('user_id', $user->id)
					->orderByDesc('id')
					->get();
			}

			if ($channels->isNotEmpty()) {
				foreach ($channels as $key => $value) {
					$value->logo = getFileTemporaryURL($value->logo);

					if (isset($value->views)) {
						$viewData = json_decode($value->views, true);
						$value->view_count = is_array($viewData) ? count($viewData) : 0;
					} else {
						$value->view_count = 0;
					}
				}

				if ($epgChannelId) {
					$channels = $channels->filter(function ($channel) use ($epgChannelId) {
						return $channel->id == $epgChannelId;
					});
				}

				$filteredChannels = $channels->filter(function ($channel) use ($destinationChannelsArray) {
					return !in_array($channel->id, $destinationChannelsArray);
				});

				return generateResponse([
					'type' => 'success',
					'code' => 200,
					'status' => true,
					'message' => 'Channel(s) retrieved successfully.',
					'toast' => true
				], [
					'channel' => $channels->values(),
					'destination_channels' => $filteredChannels->values()
				]);
			} else {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'No channel available',
					'toast' => true
				], ['channel' => $channels]);
			}
		} catch (\Exception $e) {
			Log::info('Error while retrieving channel: ' . $e->getMessage());
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Error retrieving channel.',
				'toast' => true
			]);
		}
	}

	public function getchannellogo(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$channelId = $request->input('channel_id');
			$logo_path = Channel::where('user_id', $user->id)->where('id', $channelId)->first();

			if (!$logo_path) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Id not found.', 'toast' => true]);
			}

			if ($logo_path) {
				if ($logo_path->logo) {
					$filePath = storage_path('app/' . $logo_path->logo);

					if (!file_exists($filePath)) {
						return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Logo retrived successfully.', 'toast' => true], ['logo' => $logo_path->logo]);
					} else {
						return response()->file($filePath);
					}
				} else {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Requested channel does not exist for the user.', 'toast' => true]);
				}
			}
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error fetching the logo.', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('File add error : ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error processing the Request.', 'toast' => true]);
		}
	}
	public function createlivestream(Request $request)
	{
		DB::beginTransaction();
		$livestream = new LiveStream();
		try {
			$existingLiveStream = LiveStream::where('stream_title', $request->stream_title)->first();
			if ($existingLiveStream) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'The live stream title has already been taken.', 'toast' => true, 'data' => []]);
			}
			$user = $request->attributes->get('user');
			$livestream->user_id = $user->id;

			if (!$request->filled('stream_title')) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Live stream title not provided', 'toast' => true, 'data' => []]);
			}

			if ($request->filled('stream_title')) {
				$livestream->stream_title = $request->stream_title;
			}
			if (!$request->filled('stream_key_id')) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Stream key id not provided', 'toast' => true, 'data' => []]);
			}
			$existingLiveStream = LiveStream::where('stream_key_id', $request->stream_key_id)->first();
			if ($existingLiveStream) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'The stream key id has already been taken.', 'toast' => true, 'data' => []]);
			}
			if ($request->filled('stream_key_id')) {
				$livestream->stream_key_id = $request->stream_key_id;
			}
			if ($request->filled('destination_id')) {
				$livestream->destination_id = $request->destination_id;
			}
			if ($request->filled('destination_on_off')) {
				$livestream->destination_on_off = $request->destination_on_off;
			}
			$livestream->stream_key = hash('sha256', Str::random(32));
			$livestream->playback_url_key = hash('sha256', Str::random(32));
			$livestream->save();
			DB::commit();
			$newlivestream = LiveStream::where('id', $livestream->id)->first();
			$livestream_data = [
				'id' => $newlivestream->id,
				'stream_title' => $newlivestream->stream_title,
				'stream_key_id' => $newlivestream->stream_key_id,
				'stream_key' => $newlivestream->stream_key,
				'playback_url_key' => $newlivestream->playback_url_key,
				'destination_id' => explode(',', $newlivestream->destination_id),
				'destination_on_off' => $newlivestream->destination_on_off,
			];
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Live stream created successfully.', 'toast' => true, 'data' => ['livestream_data' => $livestream_data]]);
		} catch (\Exception $e) {
			Log::info('Error while creating live stream: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error creating live stream.', 'toast' => true]);
		}
	}
	public function updateLiveStream(UpdateLiveStreamRequest $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$stream_id = $request->input('stream_id');
			$LiveStream = LiveStream::where('user_id', $userId)->where('id', $stream_id)->first();
			if (!$LiveStream) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'Live stream not found.',
					'toast' => true
				]);
			}
			if ($request->filled('stream_title')) {
				if ($LiveStream->stream_title == $request->stream_title) {
					return generateResponse([
						'type' => 'error',
						'code' => 200,
						'status' => false,
						'message' => 'Please provide another title.',
						'toast' => true
					]);
				}
				$LiveStream->stream_title = $request->stream_title;
			}
			if ($request->has('stream_key')) {
				$LiveStream->stream_key = hash('sha256', Str::random(32));
			}
			if ($request->has('playback_url_key')) {
				$LiveStream->playback_url_key = hash('sha256', Str::random(32));
			}
			if ($request->filled('destination_id')) {
				$newDestinationId = $request->input('destination_id');
				$currentDestinationIdString = trim($LiveStream->destination_id, ',');
				$currentDestinationIds = $currentDestinationIdString !== '' ? explode(',', $currentDestinationIdString) : [];
				if (!in_array($newDestinationId, $currentDestinationIds)) {
					$currentDestinationIds[] = $newDestinationId;
					$LiveStream->destination_id = implode(',', $currentDestinationIds);
				}
			}
			if ($request->filled('destination_on_off')) {
				$LiveStream->destination_on_off = $request->destination_on_off;
			}
			$LiveStream->save();
			DB::commit();
			$newlivestream = LiveStream::where('id', $LiveStream->id)->first();
			$livestream_data = [
				'id' => $newlivestream->id,
				'stream_title' => $newlivestream->stream_title,
				'stream_key_id' => $newlivestream->stream_key_id,
				'stream_key' => $newlivestream->stream_key,
				'playback_url_key' => $newlivestream->playback_url_key,
				'destination_id' => explode(',', $newlivestream->destination_id),
				'destination_on_off' => $newlivestream->destination_on_off,
			];
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Live stream updated successfully.', 'toast' => true], ['livestream' => $livestream_data]);
		} catch (\Exception $e) {
			Log::info('Error while updating live stream: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error updating live stream.', 'toast' => true]);
		}
	}
	public function deletelivestream(Request $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$stream_id = $request->stream_id;
			$LiveStream = LiveStream::where('user_id', $userId)->where('id', $stream_id)->first();

			if (!$LiveStream) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'Id not found.',
					'toast' => true
				]);
			}

			$LiveStream->delete();
			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Live stream deleted successfully.', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('Error while deleting live stream: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error deleting live stream.', 'toast' => true]);
		}
	}
	public function getlivestream(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$searchTitle = $request->input('stream_title');
			$orderBy = $request->input('order_by');

			$liveStream = LiveStream::where('user_id', $userId);

			if ($searchTitle) {
				$liveStream->where('stream_title', 'like', '%' . $searchTitle . '%');
			}

			if ($request->filled('stream_key_id')) {
				$liveStream = LiveStream::where('user_id', $userId)
					->where('stream_key_id', $request->stream_key_id)
					->first();
				$playbackStream = "users/private/" . $userId . "/streamdeck/manifest/" . $liveStream->playback_url_key . "/stream.m3u8";
				$playbackStreamtemp = getFileTemporaryURL($playbackStream);
				if (!$liveStream) {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No matching live stream found for the provided stream_key_id', 'toast' => true, 'data' => []]);
				}

				$destinationIds = explode(',', $liveStream->destination_id);
				$liveStream->destination_channel = Channel::whereIn('id', $destinationIds)->get();


				return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Live stream retrieved successfully', 'toast' => true, 'data' => ['livestream' => $liveStream, 'playbackStream' => $playbackStreamtemp]]);
			} else {
				// Handle multiple live streams
				switch ($orderBy) {
					case 'oldest':
						$liveStream->orderBy('created_at');
						break;
					case 'newest':
						$liveStream->orderByDesc('created_at');
						break;
					case 'az':
						$liveStream->orderBy('stream_title');
						break;
					case 'za':
						$liveStream->orderByDesc('stream_title');
						break;
					default:
						$liveStream->orderByDesc('created_at');
						break;
				}

				// Retrieve all streams
				$liveStreams = $liveStream->get();

				if ($liveStreams->isEmpty()) {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true, 'data' => []]);
				}

				$srNo = 1;
				$playbackStreamList = [];

				foreach ($liveStreams as $stream) {
					// Generate playback URL for each stream
					$playbackStream = "users/private/" . $userId . "/streamdeck/manifest/" . $stream->playback_url_key . "/stream.m3u8";
					$playbackStreamtemp = getFileTemporaryURL($playbackStream);

					// Get destination channels
					$destinationIds = explode(',', $stream->destination_id);
					$channels = Channel::whereIn('id', $destinationIds)->pluck('channel_name')->toArray();
					$stream->destination_channel = $channels;

					// Add playbackStreamtemp and sr.no inside livestream key for each stream
					$playbackStreamList[] = [
						'sr_no' => $srNo++,  // Increment sr.no for each stream
						'playbackStream' => $playbackStreamtemp,
						'id' => $stream->id,
						'user_id' => $stream->user_id,
						'stream_title' => $stream->stream_title,
						'stream_key_id' => $stream->stream_key_id,
						'stream_key' => $stream->stream_key,
						'playback_url_key' => $stream->playback_url_key,
						'destination_id' => $stream->destination_id,
						'stream_status' => $stream->stream_status,
						'live_start_time' => $stream->live_start_time,
						'destination_on_off' => $stream->destination_on_off,
						'stream_url_live' => $stream->stream_url_live,
						'created_at' => $stream->created_at,
						'updated_at' => $stream->updated_at,
						'destination_channel' => $stream->destination_channel
					];
				}

				// Return all streams with sr.no and playbackStreamtemp within 'livestream' key
				return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'All data retrieved successfully', 'toast' => true, 'data' => ['livestreams' => $playbackStreamList]]);
			}
		} catch (\Exception $e) {
			Log::info('Error while getting data : ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
		}
	}
	// public function addvideo(UploadVideoRequest $request)
	// {
	// 	DB::beginTransaction();
	// 	try {
	// 		$user = $request->attributes->get('user');
	// 		$userId = $user->id;
	// 		$videostream = new VideoStream();
	// 		$videostream->user_id = $user->id;
	// 		$total_duration = 0;

	// 		if ($request->filled('video_url')) {
	// 			$videostream->video_url = $request->video_url;

	// 			if ($request->filled('title')) {
	// 				$videostream->title = $request->title;
	// 			} else {
	// 				$videostream->title = '.m3u8';
	// 			}
	// 			$videostream->save();
	// 		} else {
	// 			if ($request->hasFile('file_path')) {
	// 				foreach ($request->file('file_path') as $file) {
	// 					$fileNameWithExtension = $file->getClientOriginalName();
	// 					$fileName = pathinfo($fileNameWithExtension, PATHINFO_FILENAME);
	// 					$filePath = "users/private/{$user->id}/video/{$fileNameWithExtension}";
	// 					Storage::put($filePath, file_get_contents($file));
	// 					$thumbnailvideourl = getFileTemporaryURL($filePath);
	// 					$thumbnailPath = $this->generateThumbnail($userId, $fileName, $filePath);
	// 					// $outputFilePath = $this->concatenateMP4AddedFiles($request->file('file_path'), $userId);
	// 					// dd($outputFilePath);
	// 					if ($thumbnailPath === null) {
	// 						return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Thumbnail generation failed.', 'toast' => true]);
	// 					}


	// 					$getID3 = new getID3();
	// 					$fileInfo = $getID3->analyze($file->getRealPath());
	// 					$duration = $fileInfo['playtime_seconds'];
	// 					$durationInSeconds = (int) $duration;

	// 					$videostream->user_id = $user->id;
	// 					$videostream->file_path = $filePath;
	// 					$videostream->title = $fileName;
	// 					$videostream->thumbnail = $thumbnailPath;
	// 					// $videostream->thumbnail = '';
	// 					$videostream->duration = $durationInSeconds;
	// 					$videostream->channel_uuid = Str::uuid();
	// 					$videostream->save();
	// 				}
	// 				$userVideos = VideoStream::where('user_id', $user->id)->get();
	// 				foreach ($userVideos as $video) {
	// 					$total_duration += $video->duration;
	// 				}
	// 			} else {
	// 				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No files provided.', 'toast' => true]);
	// 			}
	// 		}
	// 		DB::commit();
	// 		return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Video added successfully.', 'toast' => true, 'data' => [$videostream, 'total_duration' => $total_duration]]);
	// 	} catch (\Exception $e) {
	// 		Log::info('Error while adding video: ' . $e->getMessage());
	// 		DB::rollBack();
	// 		return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error adding video.', 'toast' => true]);
	// 	}
	// }
	public function addVideo(UploadVideoRequest $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}
			$userId = $user->id;
			$videostream = new VideoStream();
			$total_duration = 0;
			if ($request->has('video_url')) {
				if ($request->video_url === "" || $request->video_url === null) {
					return generateResponse([
						'type' => 'error',
						'code' => 200,
						'status' => false,
						'message' => 'Please provide valid link',
						'toast' => true,
					]);
				} else {
					$videostream->user_id = $userId;
					$videostream->video_url = $request->video_url;
					$videostream->title = $request->filled('title') ? $request->title : '.m3u8';
					// $videostream->thumbnail = $thumbnailPath;
					$videostream->save();
				}
			}
			$storageDirectory = storage_path("app/users/private/{$userId}/video");
			if (!file_exists($storageDirectory)) {
				if (!mkdir($storageDirectory, 0755, true)) {
					throw new \Exception("Failed to create directory: {$storageDirectory}");
				}
			}
			if ($request->has('fileName') && $request->has('chunkIndex') && $request->has('totalChunks') && $request->hasFile('chunk')) {
				$response = $this->uploadChunk($request);
				if ($response->getStatusCode() !== 200) {
					DB::rollBack();
					return $response;
				}
				$responseData = $response->original;
				if (!isset($responseData['data']) || !isset($responseData['data']['videostream'])) {
					throw new \Exception('Invalid response format from uploadChunk');
				}
				$videostream = $responseData['data']['videostream'];
				$total_duration = VideoStream::where('user_id', $userId)->sum('duration');
			}

			DB::commit();

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Video added successfully.',
				'toast' => true,
				'data' => ['videostream' => $videostream, 'total_duration' => $total_duration],
			]);
		} catch (\Exception $e) {
			DB::rollBack();

			return generateResponse([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => 'Error adding video: ' . $e->getMessage(),
				'toast' => true,
			]);
		}
	}


	private function uploadChunk(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;

			$fileName = $request->input('fileName');
			$chunkIndex = $request->input('chunkIndex');
			$totalChunks = $request->input('totalChunks');
			$chunk = $request->file('chunk');

			$tempDir = storage_path("app/temp/{$userId}");
			if (!file_exists($tempDir)) {
				if (!mkdir($tempDir, 0777, true)) {
					return response()->json(['type' => 'error', 'status' => false, 'message' => "Failed to create temporary directory: {$tempDir}"], 500);
				}
			}

			// Save the chunk to a temporary file
			$chunkFilePath = "{$tempDir}/{$fileName}.part{$chunkIndex}";
			$chunk->move($tempDir, "{$fileName}.part{$chunkIndex}");

			// If all chunks are uploaded, combine them
			if ($this->allChunksUploaded($fileName, $totalChunks, $tempDir)) {
				$finalFilePath = storage_path("app/users/private/{$userId}/video/{$fileName}");
				$this->combineChunks($fileName, $totalChunks, $tempDir, $finalFilePath);

				// Process the final file as needed
				$videostream = new VideoStream();
				$videostream->user_id = $userId;
				$videostream->file_path = "users/private/{$userId}/video/{$fileName}";
				$videostream->title = pathinfo($fileName, PATHINFO_FILENAME);
				$thumbnailPath = $this->generateThumbnail($userId, $videostream->title, $videostream->file_path);
				if ($thumbnailPath === null) {
					return response()->json(['type' => 'error', 'status' => false, 'message' => 'Thumbnail generation failed.'], 500);
				}

				$getID3 = new getID3();
				$fileInfo = $getID3->analyze($finalFilePath);
				$duration = $fileInfo['playtime_seconds'];
				$videostream->duration = round($duration); // Save the duration in seconds
				$videostream->thumbnail = $thumbnailPath;
				$videostream->channel_uuid = Str::uuid();
				$videostream->save();

				return response()->json([
					'type' => 'success',
					'status' => true,
					'message' => 'Video uploaded and processed successfully.',
					'data' => ['videostream' => $videostream]
				], 200);
			}

			return response()->json(['type' => 'success', 'status' => true, 'message' => 'Chunk uploaded successfully.'], 200);
		} catch (\Exception $e) {
			// Log::error('Error in uploadChunk at line ' . __LINE__ . ': ' . $e->getMessage());
			return response()->json(['type' => 'error', 'status' => false, 'message' => 'Error uploading chunk: ' . $e->getMessage()], 500);
		}
	}

	private function combineChunks($fileName, $totalChunks, $tempDir, $finalFilePath)
	{
		$finalFile = fopen($finalFilePath, 'ab');
		for ($i = 0; $i < $totalChunks; $i++) {
			$chunkFilePath = "{$tempDir}/{$fileName}.part{$i}";
			$chunkFile = fopen($chunkFilePath, 'rb');
			while ($chunk = fread($chunkFile, 4096)) {
				fwrite($finalFile, $chunk);
			}
			fclose($chunkFile);
			unlink($chunkFilePath); // Delete the chunk after appending to the final file
		}
		fclose($finalFile);
	}

	private function allChunksUploaded($fileName, $totalChunks, $tempDir)
	{
		for ($i = 0; $i < $totalChunks; $i++) {
			if (!file_exists("{$tempDir}/{$fileName}.part{$i}")) {
				return false;
			}
		}
		return true;
	}

	private function concatenateMP4AddedFiles($files, $userId)
	{
		DB::beginTransaction();
		try {
			$filePaths = [];
			$outputFileName = 'out-' . time() . '.mp4';
			$outputFilePath = "users/private/{$userId}/video/binary/{$outputFileName}";
			$ffmpeg = \FFMpeg\FFMpeg::create(
				[
					'ffmpeg.binaries'  => config('app.ffmpeg_binaries'),
					'ffprobe.binaries' => config('app.ffprobe_binaries'),
				]
			);

			Storage::makeDirectory("users/private/{$userId}/video/binary/");
			foreach ($files as $file) {
				$fileNameWithExtension = $file->getClientOriginalName();
				$fileName = pathinfo($fileNameWithExtension, PATHINFO_FILENAME);
				$filePath = "users/private/{$userId}/video/binary/{$fileNameWithExtension}";
				Storage::put($filePath, file_get_contents($file));
				$filePaths[] = storage_path("app/" . $filePath); // Get the full path of the stored file
				$extension = pathinfo($fileNameWithExtension, PATHINFO_EXTENSION);
				if ($extension !== 'mp4') {
					$tempOutputFilePath = storage_path("app/users/private/{$userId}/video/binary/temp_{$fileName}.mp4");
					$command = "ffmpeg -i " . escapeshellarg(storage_path("app/" . $filePath)) . " -c:v libx264 -preset ultrafast -c:a aac " . escapeshellarg($tempOutputFilePath);
					exec($command);
					$filePaths[] = $tempOutputFilePath;
				}
			}
			if (empty($filePaths)) {
				return null;
			}
			$ffmpeg->open($filePaths[0])
				->concat($filePaths)
				->saveFromSameCodecs(storage_path("app/" . $outputFilePath), true);
			foreach ($filePaths as $tempFile) {
				if (file_exists($tempFile) && strpos($tempFile, 'temp_') !== false) {
					unlink($tempFile);
				}
			}
			DB::commit();
			return $outputFilePath;
		} catch (\Exception $e) {
			Log::info('Error while concatenating videos: ' . $e->getMessage());
			DB::rollBack();
			return null;
		}
	}
	public function generateThumbnail($userId, $fileName, $filePath)
	{
		$filePath = storage_path("app/" . $filePath);
		$thumbnailFileName = "{$fileName}.jpg";
		$thumbnailDirectory = "users/private/{$userId}/video/thumbnails";
		$thumbnailPath = "{$thumbnailDirectory}/{$thumbnailFileName}";

		if (!Storage::exists($thumbnailDirectory)) {
			Storage::makeDirectory($thumbnailDirectory, 0755, true);
		}
		try {
			$ffmpeg = \FFMpeg\FFMpeg::create(
				[
					'ffmpeg.binaries'  => config('app.ffmpeg_binaries'),
					'ffprobe.binaries' => config('app.ffprobe_binaries'),
				]
			);
			$video = $ffmpeg->open($filePath);
			$frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1));
			$frame->save(storage_path("app/{$thumbnailPath}"));
			return $thumbnailPath;
		} catch (\Exception $e) {
			Log::error('Error while generating thumbnail for file: ' . $e->getMessage());
			DB::rollBack();
			return response()->json([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => 'Error while processing.',
				'toast' => true
			]);
		}
	}

	public function getm3u8VideoLink(Request $request)
	{
		try {
			$m3u8link = $request->video_link;
			$temprorylink = getFileTemporaryURL($m3u8link);
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Video added successfully.', 'toast' => true], ['temprorylink' => $temprorylink]);
		} catch (\Exception $e) {
			Log::error('Error while generating m3u8 link for file: ' . $e->getMessage());
			DB::rollBack();
			return response()->json([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => 'Error while processing.',
				'toast' => true
			]);
		}
	}

	public function updatevideo(UpdateVideoRequest $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$stream_id = $request->stream_id;

			$videostream = VideoStream::where('user_id', $userId)->where('id', $stream_id)->first();

			if (!$videostream) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Video id not found.', 'toast' => true]);
			}

			if ($request->filled('description')) {
				$videostream->description = $request->description;
			}

			if ($request->filled('video_url')) {
				$videostream->video_url = $request->video_url;
			}

			if ($request->filled('file_path')) {
				$videostream->file_path = $request->file_path;
			}

			if ($request->filled('tags')) {
				$tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
				$videostream->tags = json_encode($tags);
			} else {
				$videostream->tags = null;
			}
			if ($request->filled('is_featured')) {
				$videostream->is_featured = $request->is_featured;
			}
			if ($request->filled('views')) {
				$videostream->views = $request->views;
			}
			if ($request->filled('is_private')) {
				$videostream->is_private = $request->is_private;
			}
			if ($request->filled('title')) {
				$videostream->title = $request->title;
			}

			$videostream->save();
			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Video stream updated successfully.', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('Error while updating video: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error updating video stream.', 'toast' => true]);
		}
	}
	public function deletesvideo(Request $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$Videostream_id = $request->video_stream_id;
			$LiveStream = VideoStream::where('user_id', $userId)->where('id', $Videostream_id)->first();

			if (!$LiveStream) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'Id not found.',
					'toast' => true
				]);
			}

			if ($LiveStream->file_path && Storage::exists($LiveStream->file_path)) {
				Storage::delete($LiveStream->file_path);
			}

			if ($LiveStream->thumbnail && Storage::exists($LiveStream->thumbnail)) {
				Storage::delete($LiveStream->thumbnail);
			}

			$LiveStream->delete();
			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Video deleted successfully.', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('Error while deleting video: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error deleting video.', 'toast' => true]);
		}
	}

	public function getallvideo(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;

			$total_duration = 0;
			$total_records_count = VideoStream::where('user_id', $userId)->count();

			$searchTitle = $request->input('title');
			$searchTag = $request->input('tags');
			$alltags = $request->input('alltags');
			$orderBy = $request->input('order_by');
			$channel_count = getUserChannelsCount($userId);
			$channel_id = $request->channel_id;

			$video_id = $request->video_id;
			if ($video_id) {
				$video = VideoStream::where('user_id', $user->id)->where('id', $video_id)->first();
				if ($video) {
					return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Data retrieved successfully', 'toast' => true, 'data' => [$video]]);
				} else {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true]);
				}
			}
			$videoStreamQuery = VideoStream::where('user_id', $userId);

			if ($searchTitle) {
				$videoStreamQuery->where('title', 'like', '%' . $searchTitle . '%');
			}

			if ($searchTag && $alltags) {
				$searchConditions = [];
				foreach ($searchTag as $tag) {
					$searchConditions[] = "JSON_SEARCH(tags, 'one', ?) IS NOT NULL";
				}
				$videoStreamQuery->whereRaw(implode(' AND ', $searchConditions), $searchTag);
			} elseif ($searchTag) {
				$searchConditions = [];
				foreach ($searchTag as $tag) {
					$searchConditions[] = "JSON_SEARCH(tags, 'one', ?) IS NOT NULL";
				}
				$videoStreamQuery->whereRaw(implode(' OR ', $searchConditions), $searchTag);
			}


			if ($request->input('external_link')) {
				$videoStreamQuery->whereNotNull('video_url');
			}

			if ($request->input('hosted_video')) {
				$videoStreamQuery->whereNotNull('file_path');
			}

			switch ($orderBy) {
				case 'oldest':
					$videoStreamQuery->orderBy('created_at');
					break;
				case 'newest':
					$videoStreamQuery->orderByDesc('created_at');
					break;
				case 'az':
					$videoStreamQuery->orderBy('title');
					break;
				case 'za':
					$videoStreamQuery->orderByDesc('title');
					break;
				case 'shortest':
					$videoStreamQuery->orderByRaw('CAST(duration AS DECIMAL)');
					break;
				case 'longest':
					$videoStreamQuery->orderByRaw('CAST(duration AS DECIMAL) DESC');
					break;
				default:
					$videoStreamQuery->orderByDesc('created_at');
					break;
			}
			$perPage = 10;
			$currentPage = $request->input('page', 1);
			Paginator::currentPageResolver(function () use ($currentPage) {
				return $currentPage;
			});
			$videoStreams = $videoStreamQuery->paginate($perPage);
			$videoStream = $videoStreamQuery->get();

			if ($videoStream->isEmpty()) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true, 'data' => []]);
			}

			$videoStreams = $videoStream->toArray();
			$videoStreams = [];
			foreach ($videoStream as $video) {
				$videoData = $video->toArray();
				$videoData['tags'] = json_decode($videoData['tags']);
				$videoData['title'] = Str::limit($videoData['title'], 20);

				$thumbnailPath = $video->thumbnail;
				if ($thumbnailPath) {
					$fullThumbnailPath = storage_path('app/' . $thumbnailPath);
					if (file_exists($fullThumbnailPath)) {
						$videoData['videothumbnails'] = getFileTemporaryURL($video->thumbnail);
					} else {
						$defaultThumbnailPath = config("app.APP_URL") . "assets/default/images/default_image.png";
						$videoData['videothumbnails'] = $defaultThumbnailPath;
					}
				} else {
					$defaultThumbnailPath = config("app.APP_URL") . "assets/default/images/default_image.png";
					$videoData['videothumbnails'] = $defaultThumbnailPath;
				}
				$videoData['download_path'] = url('file-download/' . $video->file_path);
				$videoData['extension'] = null;
				if ($video->file_path != null) {
					$extension = pathinfo($video->file_path);
					$videoData['extension'] = $extension['extension'] ? $extension['extension'] : 'm3u8';
					$videoData['video_url'] = getFileTemporaryURL($video->file_path);
				}
				$videoStreams[] = $videoData;
			}

			$userVideos = VideoStream::where('user_id', $user->id)->get();
			if ($userVideos) {
				foreach ($userVideos as $video) {
					$total_duration += $video->duration;
				}
			}
			if ($channel_id) {
				$videos = Schedular::where('channel_id', $channel_id)->get();
				if ($videos->isNotEmpty()) {
					$video_ids = $videos->pluck('video_id')->toArray();
				}
			}

			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'All data retrieved successfully', 'toast' => true, 'data' => ["videostreams" => $videoStreams, 'total_duration' => $total_duration, 'total_records_count' => $total_records_count, 'video_ids' => $video_ids ?? [], 'channel_count' => $channel_count]]);
		} catch (\Exception $e) {
			Log::info('Error while getting data: ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
		}
	}
	public function getvideo(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$video_id = $request->video_id;

			$video = VideoStream::where('user_id', $user->id)->where('id', $video_id)->first();

			if ($video) {
				if ($video->file_path) {
					$filePath = storage_path('app/' . $video->file_path);
					if (!file_exists($filePath)) {
						return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true, 'data' => []]);
					} else {
						$videofilepath = getFileTemporaryURL($video->file_path);
						$duration = $video->duration;
						$thumbnail = $video->thumbnail;
						$download = url('file-download/' . $video->file_path);
						return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Video URL found', 'toast' => true], ['videoStream' => $videofilepath, 'duration' => $duration, 'thumbnail' => $thumbnail, 'download' => $download]);
					}
				} else if ($video->video_url) {
					return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Video URL found', 'toast' => true, 'data' => ['video_url' => $video->video_url, 'video_title' => $video->title, 'video_url_data' => true]]);
				} else {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'File not found', 'toast' => true]);
				}
			}
		} catch (\Exception $e) {
			Log::info('Error while getting data: ' . $e->getMessage());
		}
	}
	public function downloadvideo(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$video_id = $request->video_id;

			$video = VideoStream::where('user_id', $user->id)->where('id', $video_id)->first();
			if ($video) {
				$videoPath = storage_path('app/' . $video->file_path);
				if (file_exists($videoPath)) {
					ob_clean();
					return response()->download($videoPath);
				}
			}

			return generateResponse(['type' => 'error', 'code' => 200, 'status' => true, 'message' => 'File not Found.', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('File add error : ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error Fetching the file for download', 'toast' => true]);
		}
	}
	public function createwebsite(CreateWebsite $request)
	{
		DB::beginTransaction();
		$website = new Website();
		try {
			$existingwebsite = Website::where('title', $request->website_title)->first();
			if ($existingwebsite) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'The website title has already been taken.', 'toast' => true, 'data' => []]);
			}
			$user = $request->attributes->get('user');
			$username = $user->username;
			$website->user_id = $user->id;
			$randomString = Str::random(10);
			if (!$request->filled('website_title')) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Website title not provided', 'toast' => true, 'data' => []]);
			}

			if ($request->filled('website_title')) {
				$website->title = $request->website_title;
			}
			if ($request->filled('website_title') && $username) {
				$sanitizedTitle = preg_replace('/[^\w-]/', '-', $request->website_title);
				$website->domain = "https://tv.silocloud.io/watch/" . $username . "-" . $sanitizedTitle . "/" . $randomString;
			}
			if ($randomString) {
				$website->domain_id = $randomString;
			}
			$channelIds = explode(',', $request->channels);

			$existingChannels = Channel::where('user_id', $user->id)
				->whereIn('id', $channelIds)
				->pluck('id');

			$missingChannels = array_diff($channelIds, $existingChannels->toArray());

			if (!empty($missingChannels)) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Channel(s) not found: ' . implode(', ', $missingChannels), 'toast' => true, 'data' => []]);
			}

			if ($request->filled('channels')) {
				$website->channels = $request->channels;
			}
			if ($request->hasFile('site_logo')) {
				$siteLogoFile = $request->file('site_logo');
				$siteLogoPath = $this->storeFileAndGetPath($user, $siteLogoFile);
				$website->site_logo = $siteLogoPath;
			}
			if ($request->hasFile('site_favicon')) {
				$siteFaviconFile = $request->file('site_favicon');
				$siteFaviconPath = $this->storeFileAndGetPath($user, $siteFaviconFile);
				$website->site_favicon = $siteFaviconPath;
			}
			if ($request->filled('header')) {
				$website->header = $request->header;
			}
			if ($request->filled('page_layout')) {
				$website->page_layout = $request->page_layout;
			}
			if ($request->filled('background_color')) {
				$website->background_color = $request->background_color;
			}
			if ($request->filled('font_color')) {
				$website->font_color = $request->font_color;
			}
			if ($request->filled('highlight_color')) {
				$website->highlight_color = $request->highlight_color;
			}
			if ($request->filled('playback_options')) {
				$playback_options = is_array($request->playback_options) ? $request->playback_options : explode(',', $request->playback_options);
				$trimmed_playback_options = array_map('trim', $playback_options);

				$website->playback_options = json_encode($trimmed_playback_options);
			}
			if ($request->filled('display_options')) {
				$display_options = is_array($request->display_options) ? $request->display_options : explode(',', $request->display_options);
				$trimmed_playback_options = array_map('trim', $display_options);

				$website->display_options = json_encode($trimmed_playback_options);
			}
			if ($request->filled('footer_text')) {
				$website->footer_text = $request->footer_text;
			}
			if ($request->filled('footer_description')) {
				$website->footer_description = $request->footer_description;
			}
			$website->save();
			// $website->playback_options = json_decode($website->playback_options, true);
			// $website->display_options = json_decode($website->display_options, true);
			addNotification($user->id, $user->id, "New Website Created", "You Have Created " . $request->website_title . " Website", $website->id, "4", '/websites/website-edit/' . $randomString);
			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Website created successfully.', 'toast' => true, 'data' => [$website]]);
		} catch (\Exception $e) {
			Log::info('Error while creating website: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error creating website.', 'toast' => true]);
		}
	}
	private function storeFileAndGetPath($user, $file)
	{
		$fileNameWithExtension = $file->getClientOriginalName();
		$fileName = pathinfo($fileNameWithExtension, PATHINFO_FILENAME);
		$filePath = "users/private/{$user->id}/website/{$fileNameWithExtension}";
		// $filePath = "public/users/{$user->id}/website/{$fileNameWithExtension}";
		Storage::put($filePath, file_get_contents($file));
		return $filePath;
	}
	public function updatewebsite(UpdateWebsite $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$website_id = $request->website_id;
			$username = $user->username;
			$website = Website::where('user_id', $userId)->where('domain_id', $website_id)->first();
			$randomString = Str::random(10);
			if (!$website) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Video stream not found.', 'toast' => true]);
			}
			if (!$request->filled('website_title')) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Website title not provided', 'toast' => true, 'data' => []]);
			}
			if ($request->filled('website_title') && $request->website_title !== $website->title) {
				$existingTitle = Website::where('user_id', $userId)->where('title', $request->website_title)->get();
				if (!$existingTitle->isEmpty()) {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'The website title has already been taken.', 'toast' => true, 'data' => []]);
				}
			}

			if ($request->filled('website_title')) {
				$website->title = $request->website_title;
			}
			if ($request->filled('website_title') && $username) {
				$sanitizedTitle = preg_replace('/[^\w-]/', '-', $request->website_title);
				$website->domain = "https://tv.silocloud.io/watch/" . $username . "-" . $sanitizedTitle . "/" . $website->domain_id;
			}
			if ($request->filled('channels')) {
				$channelIds = explode(',', $request->channels);
				$existingChannels = Channel::where('user_id', $userId)
					->whereIn('id', $channelIds)
					->pluck('id');
				$missingChannels = array_diff($channelIds, $existingChannels->toArray());
				if (!empty($missingChannels)) {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Channel(s) not found: ' . implode(', ', $missingChannels), 'toast' => true, 'data' => []]);
				}
				$website->channels = $request->channels;
			}
			if ($request->has('site_logo')) {
				$siteLogoFile = $request->file('site_logo');
				$siteLogoPath = $this->storeFileAndGetPath($user, $siteLogoFile);
				$website->site_logo = $siteLogoPath;
			}
			if ($request->has('site_favicon')) {
				$siteFaviconFile = $request->file('site_favicon');
				$siteFaviconPath = $this->storeFileAndGetPath($user, $siteFaviconFile);
				$website->site_favicon = $siteFaviconPath;
			}
			if ($request->filled('header')) {
				$website->header = $request->header;
			}
			if ($request->filled('page_layout')) {
				$website->page_layout = $request->page_layout;
			}
			if ($request->filled('background_color')) {
				$website->background_color = $request->background_color;
			}
			if ($request->filled('font_color')) {
				$website->font_color = $request->font_color;
			}
			if ($request->filled('highlight_color')) {
				$website->highlight_color = $request->highlight_color;
			}
			if ($request->filled('playback_options')) {
				$playback_options = is_array($request->playback_options) ? $request->playback_options : explode(',', $request->playback_options);
				$trimmed_playback_options = array_map('trim', $playback_options);

				$website->playback_options = json_encode($trimmed_playback_options);
			}
			if ($request->filled('display_options')) {
				$display_options = is_array($request->display_options) ? $request->display_options : explode(',', $request->display_options);
				$trimmed_playback_options = array_map('trim', $display_options);

				$website->display_options = json_encode($trimmed_playback_options);
			}
			if ($request->filled('footer_text')) {
				$website->footer_text = $request->footer_text;
			}
			if ($request->filled('footer_description')) {
				$website->footer_description = $request->footer_description;
			}

			$website->save();
			$website->playback_options = json_decode($website->playback_options, true);
			$website->display_options = json_decode($website->display_options, true);
			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Website updated successfully.', 'toast' => true, 'data' => [$website]]);
		} catch (\Exception $e) {
			Log::info('Error while updating website: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error updating website.', 'toast' => true]);
		}
	}
	public function deletewebsite(Request $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$Website_id = $request->website_id;
			$channelIdToDelete = $request->channel_id;
			$Webiste = Website::where('user_id', $userId)->where('id', $Website_id)->first();

			if (!$Webiste) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'Website not found.',
					'toast' => true
				]);
			}
			if ($channelIdToDelete) {
				$channels = explode(',', $Webiste->channels);

				if (!in_array($channelIdToDelete, $channels)) {
					DB::rollBack();
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Channel not found in website.', 'toast' => true]);
				}

				$updatedChannels = array_diff($channels, [$channelIdToDelete]);
				$Webiste->channels = implode(',', $updatedChannels);

				$Webiste->save();

				Website::where('channels', $channelIdToDelete)->delete();
				DB::commit();
				return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Channel deleted successfully from website.', 'toast' => true]);
			}

			$Webiste->delete();
			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Website deleted successfully.', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('Error while deleting website : ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error deleting website.', 'toast' => true]);
		}
	}
	public function getwebsite(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;

			$websites = Website::where('user_id', $userId)
				->orderByDesc('created_at')
				->get();

			$websites_id = $request->website_id;
			if ($websites_id) {
				$websites_data = Website::where('user_id', $user->id)->where('domain_id', $websites_id)->first();
				if ($websites_data) {
					$channelIds = explode(',', $websites_data->channels);

					$channels = Channel::whereIn('id', $channelIds)
						->select('id', 'channel_name')
						->get();
					if ($websites_data->site_logo) {
						$site_logo_path = storage_path("app/" . $websites_data->site_logo);
						if (file_exists($site_logo_path)) {
							$websites_data['site_logo__base64'] = base64_encode(file_get_contents($site_logo_path));
						}
					}

					if ($websites_data->site_favicon) {
						$site_favicon_path = storage_path("app/" . $websites_data->site_favicon);
						if (file_exists($site_favicon_path)) {
							$websites_data['site_favicon__base64'] = base64_encode(file_get_contents($site_favicon_path));
						}
					}

					$websites_data->channels = $channels;
					$websites_data->site_logo = getFileTemporaryURL($websites_data->site_logo);
					$websites_data->site_favicon = getFileTemporaryURL($websites_data->site_favicon);

					$websites_data['playback_options'] = json_decode($websites_data['playback_options']);
					$websites_data['display_options'] = json_decode($websites_data['display_options']);
					return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Data retrieved successfully', 'toast' => true, 'data' => [$websites_data]]);
				} else {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true]);
				}
			}

			if ($websites->isEmpty()) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No logs available', 'toast' => true, 'data' => []]);
			}
			$websites = $websites->map(function ($website) {
				$channelIds = explode(',', $website->channels);

				$channels = Channel::whereIn('id', $channelIds)
					->select('id', 'channel_name')
					->get();

				$website->channels = $channels;
				if ($website->site_logo) {
					$site_logo_path = storage_path('app/' . $website->site_logo);
					if (file_exists($site_logo_path)) {
						$site_logo_data = file_get_contents($site_logo_path);
						$website['site_logo__base64'] = base64_encode($site_logo_data);
					}
				}
				if ($website->site_favicon) {
					$site_favicon_path = storage_path('app/' . $website->site_favicon);
					if (file_exists($site_favicon_path)) {
						$site_favicon_data = file_get_contents($site_favicon_path);
						$website['site_favicon__base64'] = base64_encode($site_favicon_data);
					}
				}
				$website['playback_options'] = json_decode($website['playback_options']);
				$website['display_options'] = json_decode($website['display_options']);
				return $website;
			});

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'All data retrieved successfully',
				'toast' => true,
				'data' => ['website_data' => $websites->toArray()]
			]);
		} catch (\Exception $e) {
			Log::info('Error while getting data : ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
		}
	}
	// public function schedulechannel(Request $request)
	// {
	// 	try {
	// 		$user = $request->attributes->get('user');
	// 		$userId = $user->id;
	// 		$schedule = new Schedular();
	// 		$schedule->user_id = $user->id;

	// 		$channel_id = $request->channel_id;
	// 		$video_id = $request->video_id;

	// 		$channel = Channel::where('user_id', $userId)->where('id', $channel_id)->first();
	// 		$video = VideoStream::where('user_id', $userId)->where('id', $video_id)->first();

	// 		if (!$channel) {
	// 			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Channel id not found.', 'toast' => true]);
	// 		}

	// 		if (!$video) {
	// 			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Video id not found.', 'toast' => true]);
	// 		}
	// 		$newStartTime = $request->start_time;
	// 		$newEndTime = $request->end_time;

	// 		$existingScheduleOverlap = Schedular::where('channel_id', $channel_id)
	// 			->where(function ($query) use ($newStartTime, $newEndTime) {
	// 				$query->where(function ($query) use ($newStartTime, $newEndTime) {
	// 					$query->where('start_time', '<', $newEndTime)
	// 						->where('end_time', '>', $newStartTime);
	// 				})
	// 					->orWhere(function ($query) use ($newStartTime, $newEndTime) {
	// 						$query->where('start_time', '>=', $newStartTime)
	// 							->where('start_time', '<', $newEndTime);
	// 					})
	// 					->orWhere(function ($query) use ($newStartTime, $newEndTime) {
	// 						$query->where('end_time', '>', $newStartTime)
	// 							->where('end_time', '<=', $newEndTime);
	// 					});
	// 			});

	// 		if ($channel->schedule_duration == '0') {
	// 			$existingScheduleOverlap->whereNull('day');
	// 		} else {
	// 			$existingScheduleOverlap->where('day', $request->day);
	// 		}

	// 		$existingScheduleOverlap = $existingScheduleOverlap->first();

	// 		if ($existingScheduleOverlap) {
	// 			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Schedule already exists.', 'toast' => true, 'data' => []]);
	// 		}
	// 		if ($request->filled('channel_id')) {
	// 			$schedule->channel_id = $channel_id;
	// 		}
	// 		if ($request->filled('video_id')) {
	// 			$schedule->video_id = $video_id;
	// 		}
	// 		if ($channel) {
	// 			$schedule->channel_name = $channel->channel_name;
	// 		}
	// 		if ($channel) {
	// 			$schedule->channel_type = $channel->channel_type;
	// 		}
	// 		if ($channel) {
	// 			$schedule->linear_channel_type = $channel->linear_channel_type;
	// 		}
	// 		if ($request->filled('start_time')) {
	// 			if (!$this->isValid24HourTime($request->start_time)) {
	// 				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid start time format. Please provide a valid 24-hour time.', 'toast' => true]);
	// 			}
	// 			$schedule->start_time = $request->start_time;
	// 		}
	// 		if ($request->filled('end_time')) {
	// 			if (!$this->isValid24HourTime($request->end_time)) {
	// 				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid end time format. Please provide a valid 24-hour time.', 'toast' => true]);
	// 			}
	// 			$schedule->end_time = $request->end_time;
	// 		}
	// 		if ($video) {
	// 			$schedule->duration = $video->duration;
	// 		}
	// 		if ($channel) {
	// 			$schedule->schedule_duration = $channel->schedule_duration;
	// 		}
	// 		if ($channel->schedule_duration == '1') {
	// 			$schedule->day = $request->day;
	// 		} else {
	// 			$schedule->day = null;
	// 		}
	// 		if ($channel->channel_type === '1') {
	// 			$schedule->no_of_adds_in_hour = $channel->no_of_adds_in_hour;
	// 			$schedule->Seconds_per_add_break = $channel->Seconds_per_add_break;
	// 		} else {
	// 			$schedule->no_of_adds_in_hour = null;
	// 			$schedule->Seconds_per_add_break = null;
	// 		}

	// 		$schedule->save();

	// 		//Create m3u8 code
	// 		$new_video_id = $request->video_id;

	// 		$m3u8FolderPath1 = $channel->concatenate_path;
	// 		$m3u8FolderPath = dirname($m3u8FolderPath1);
	// 		$outputFilePath = $channel->output_file_path;

	// 		if (!empty($m3u8FolderPath) && Storage::exists($m3u8FolderPath)) {
	// 			try {
	// 				Storage::deleteDirectory($m3u8FolderPath);
	// 			} catch (\Exception $e) {
	// 				Log::error('Error deleting files: ' . $e->getMessage());
	// 			}
	// 		}
	// 		if (!empty($m3u8FolderPath) && Storage::exists($outputFilePath)) {
	// 			try {
	// 				Storage::delete($outputFilePath);
	// 			} catch (\Exception $e) {
	// 				Log::error('Error deleting files: ' . $e->getMessage());
	// 			}
	// 		}

	// 		$scheduler_videos = Schedular::where('user_id', $userId)
	// 			->where('channel_id', $channel_id)
	// 			->orderBy('start_time', 'asc')
	// 			->pluck('video_id')
	// 			->toArray();

	// 		$new_video_ids = explode(',', $new_video_id);
	// 		$video_ids_data = array_merge($scheduler_videos, $new_video_ids);

	// 		$filePaths = [];

	// 		foreach ($video_ids_data as $video_id) {
	// 			$video = VideoStream::where('user_id', $userId)
	// 				->where('id', $video_id)
	// 				->first();

	// 			if (!$video) {
	// 				continue;
	// 			}

	// 			$fileRelativePath = $video->file_path;
	// 			$absoluteFilePath = storage_path("app/{$fileRelativePath}");
	// 			$filePaths[] = $absoluteFilePath;
	// 		}

	// 		$m3u8_path = $this->conCatenate($filePaths, $userId);
	// 		if ($m3u8_path->getStatusCode() !== 200) {
	// 			$errorMessage = $m3u8_path->getData()->error;
	// 			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => $errorMessage, 'toast' => true]);
	// 		}

	// 		$content = $m3u8_path->getContent();

	// 		$data = json_decode($content, true);
	// 		if (isset($data['m3u8_path'])) {
	// 			$m3u8Path = $data['m3u8_path'];
	// 			$m3u8Path = str_replace('//', '/', $m3u8Path);
	// 			$output_file = $data['output_file'];
	// 			Channel::where('user_id', $userId)
	// 				->where('id', $channel_id)
	// 				->update(['concatenate_path' => $m3u8Path, 'output_file_path' => $output_file]);
	// 		}

	// 		return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Channel scheduled successfully', 'toast' => true, 'data' => $schedule]);
	// 	} catch (\Exception $e) {
	// 		Log::info('Error while creating schedule: ' . $e->getMessage());
	// 		return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
	// 	}
	// }
	// public function updateschedulechannel(Request $request)
	// {
	// 	try {
	// 		$user = $request->attributes->get('user');
	// 		$userId = $user->id;

	// 		$schedule_id = $request->schedule_id;

	// 		$schedule = Schedular::where('user_id', $userId)->where('id', $schedule_id)->first();
	// 		if (!$schedule) {
	// 			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Schedule id not found.', 'toast' => true]);
	// 		}
	// 		$schedule_duration = $schedule->schedule_duration;

	// 		$channel_id = $request->channel_id;
	// 		$video_id = $request->video_id;
	// 		$channel = Channel::where('user_id', $userId)->where('id', $channel_id)->first();

	// 		if (!$channel) {
	// 			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Channel id not found.', 'toast' => true]);
	// 		}
	// 		if ($request->filled('start_time')) {
	// 			if (!$this->isValid24HourTime($request->start_time)) {
	// 				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid start time format. Please provide a valid 24-hour time.', 'toast' => true]);
	// 			}
	// 			$schedule->start_time = $request->start_time;
	// 		}
	// 		if ($request->filled('end_time')) {
	// 			if (!$this->isValid24HourTime($request->end_time)) {
	// 				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid end time format. Please provide a valid 24-hour time.', 'toast' => true]);
	// 			}
	// 			$schedule->end_time = $request->end_time;
	// 		}

	// 		$existingScheduleOverlap = Schedular::where('channel_id', $schedule->channel_id)
	// 			->where('id', '!=', $schedule->id)
	// 			->where(function ($query) use ($schedule, $schedule_duration, $request) {
	// 				$query->where('start_time', '<', $schedule->end_time)
	// 					->where('end_time', '>', $schedule->start_time);

	// 				if ($schedule_duration != '0') {
	// 					$query->where('day', $request->day);
	// 				}
	// 			})
	// 			->first();

	// 		if ($existingScheduleOverlap) {
	// 			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Schedule conflicts with existing schedule.', 'toast' => true]);
	// 		}

	// 		$schedule->save();

	// 		//Create m3u8 code

	// 		$m3u8FolderPath1 = $channel->concatenate_path;
	// 		$m3u8FolderPath = dirname($m3u8FolderPath1);
	// 		$outputFilePath = $channel->output_file_path;

	// 		if (!empty($m3u8FolderPath) && Storage::exists($m3u8FolderPath)) {
	// 			try {
	// 				Storage::deleteDirectory($m3u8FolderPath);
	// 			} catch (\Exception $e) {
	// 				Log::error('Error deleting files: ' . $e->getMessage());
	// 			}
	// 		}
	// 		if (!empty($m3u8FolderPath) && Storage::exists($outputFilePath)) {
	// 			try {
	// 				Storage::delete($outputFilePath);
	// 			} catch (\Exception $e) {
	// 				Log::error('Error deleting files: ' . $e->getMessage());
	// 			}
	// 		}

	// 		$scheduler_videos = Schedular::where('user_id', $userId)
	// 			->where('channel_id', $channel_id)
	// 			->orderBy('start_time', 'asc')
	// 			->pluck('video_id')
	// 			->toArray();

	// 		$video_ids_data = array_merge($scheduler_videos);

	// 		$filePaths = [];

	// 		foreach ($video_ids_data as $video_id) {
	// 			$video = VideoStream::where('user_id', $userId)
	// 				->where('id', $video_id)
	// 				->first();

	// 			if (!$video) {
	// 				continue;
	// 			}

	// 			$fileRelativePath = $video->file_path;
	// 			$absoluteFilePath = storage_path("app/{$fileRelativePath}");
	// 			$filePaths[] = $absoluteFilePath;
	// 		}

	// 		$m3u8_path = $this->conCatenate($filePaths, $userId);
	// 		if ($m3u8_path->getStatusCode() !== 200) {
	// 			$errorMessage = $m3u8_path->getData()->error;
	// 			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => $errorMessage, 'toast' => true]);
	// 		}

	// 		$content = $m3u8_path->getContent();

	// 		$data = json_decode($content, true);
	// 		if (isset($data['m3u8_path'])) {
	// 			$m3u8Path = $data['m3u8_path'];
	// 			$m3u8Path = str_replace('//', '/', $m3u8Path);
	// 			$output_file = $data['output_file'];
	// 			Channel::where('user_id', $userId)
	// 				->where('id', $channel_id)
	// 				->update(['concatenate_path' => $m3u8Path, 'output_file_path' => $output_file]);
	// 		}

	// 		return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Channel schedule updated successfully.', 'toast' => true, 'data' => $schedule]);
	// 	} catch (\Exception $e) {
	// 		Log::info('Error while updating schedule: ' . $e->getMessage());
	// 		return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
	// 	}
	// }
	private function isValid24HourTime($time)
	{
		return preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/', $time);
	}
	public function deleteschedulechannel(Request $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$schedularIds = json_decode($request->schedular_ids);
			$schedularData = Schedular::where('user_id', $userId)->whereIn('id', $schedularIds)->get();

			if ($schedularData->isEmpty()) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'No matching records found.',
					'toast' => true
				]);
			}
			foreach ($schedularData as $schedular) {
				$schedular->delete();
			}

			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Schedulars deleted successfully.', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('Error while deleting schedule: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error deleting schedulars.', 'toast' => true]);
		}
	}
	public function clearschedulechannel(Request $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$channelId = $request->channel_id;
			$schedularDay = $request->day;

			if ($schedularDay === null) {
				$query_data = Schedular::where('user_id', $userId)
					->where('channel_id', $channelId)
					->first();

				if ($query_data && $query_data->schedule_duration === '1') {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Day is required for weekly schedules.', 'toast' => true]);
				}
			}
			$query = Schedular::where('user_id', $userId)
				->where('channel_id', $channelId);

			if ($schedularDay !== null) {
				$query->where('day', $schedularDay);
			}
			$schedularData = $query->get();

			if ($schedularData->isEmpty()) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No matching records found.', 'toast' => true]);
			}
			foreach ($schedularData as $schedular) {
				$schedular->delete();
			}
			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Schedulars deleted successfully.', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('Error while deleting schedule: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => $e->getMessage(), 'toast' => true]);
		}
	}
	public function getschedulechannel(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$total_duration = 0;
			$channelId = $request->channel_id;
			$scheduledChannel = ScheduleChannles::where('channel_id', $channelId)->first();
			if (!$scheduledChannel) {
				return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Channel not found', 'toast' => true]);
			}

			$epgData = json_decode($scheduledChannel->epg_data, true);
			$videosData = [];

			foreach ($epgData as $index => $item) {
				$start_time = new \DateTime($item['since']);
				$day = $start_time->format('l');

				$videosData[] = [
					'id' => $item['id'],
					'title' => $item['title'],
					'duration' => $item['duration'],
					'start_time' => $start_time->format('Y-m-d H:i:s'),
					'scheduler_id' => $item['channel_id'],
					'day' => $day,
					'thumbnail_base64' => $item['image']
				];

				$total_duration += $item['duration'];
			}
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'All video data retrieved successfully for this channel', 'toast' => true, 'data' => ['videos_data' => $videosData, 'total_duration' => $total_duration,]]);
		} catch (\Exception $e) {
			Log::info('Error while getting data : ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
		}
	}

	public function loopedaddschedulevideo(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$channel_id = $request->channel_id;
			$video_ids = $request->video_ids;
			$video_ids = explode(',', $request->video_ids);
			$order = $request->order;
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Videos scheduled successfully', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('Error while creating schedule : ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
		}
	}
	public function deleteloopedschedulevideo(Request $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$id = $request->loop_id;
			$channel = Schedular::where('user_id', $user->id)->where('id', $id)->first();
			if (!$channel) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Scheduled video not found.', 'toast' => true]);
			}
			$channel->delete();

			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Video deleted successfully.', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('Error while deleting schedule: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error deleting channel.', 'toast' => true]);
		}
	}
	public function getloopedschedulevideo(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$total_duration = 0;
			$searchTitle = $request->video_title;

			$schedulers = Schedular::where('user_id', $user->id)
				->where('linear_channel_type', '1')
				->get();

			$videosData = [];
			$totalVideosCount = 0;

			foreach ($schedulers as $scheduler) {
				$videoQuery = VideoStream::where('user_id', $user->id)
					->where('id', $scheduler->video_id);

				if ($searchTitle) {
					$videoQuery->where('title', 'like', '%' . $searchTitle . '%');
				}

				$video = $videoQuery->first();

				if ($video) {
					$totalVideosCount++;
					$videosData[] = $video;
					$total_duration += $video->duration;
				}
			}
			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'All data retrieved successfully',
				'toast' => true,
				'data' => [
					'videos_count' => $totalVideosCount,
					'total_duration' => $total_duration,
					'videos' => $videosData
				]
			]);
		} catch (\Exception $e) {
			Log::info('Error while getting data : ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
		}
	}
	// public function rearrangevideos(Request $request)
	// {
	// 	try {
	// 		$user = $request->attributes->get('user');
	// 		$userId = $user->id;
	// 		$channel_id = $request->channel_id;
	// 		$videos = $request->videos;

	// 		$channel = Channel::where('user_id', $user->id)->where('id', $channel_id)->first();

	// 		if (!$channel_id) {
	// 			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Channel id not found', 'toast' => true]);
	// 		}

	// 		if (!empty($videos)) {
	// 			foreach ($videos as $index => $videoItem) {
	// 				$videoId = $videoItem['id'];
	// 				$sequence = $videoItem['sequence'];

	// 				$video = Schedular::where('user_id', $user->id)
	// 					->where('channel_id', $channel_id)
	// 					->where('id', $videoId)
	// 					->first();

	// 				if ($video) {
	// 					$video->order = $sequence;
	// 					$video->save();
	// 				} else {
	// 					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Video with id ' . $videoId . ' not found for rearrangement', 'toast' => true]);
	// 				}
	// 			}
	// 			//Create m3u8 code

	// 			$m3u8FolderPath1 = $channel->concatenate_path;
	// 			$m3u8FolderPath = dirname($m3u8FolderPath1);
	// 			$outputFilePath = $channel->output_file_path;

	// 			if (!empty($m3u8FolderPath) && Storage::exists($m3u8FolderPath)) {
	// 				try {
	// 					Storage::deleteDirectory($m3u8FolderPath);
	// 				} catch (\Exception $e) {
	// 					Log::error('Error deleting files: ' . $e->getMessage());
	// 				}
	// 			}
	// 			if (!empty($m3u8FolderPath) && Storage::exists($outputFilePath)) {
	// 				try {
	// 					Storage::delete($outputFilePath);
	// 				} catch (\Exception $e) {
	// 					Log::error('Error deleting files: ' . $e->getMessage());
	// 				}
	// 			}

	// 			$video_ids_data = Schedular::where('user_id', $userId)
	// 				->where('channel_id', $channel_id)
	// 				->orderBy('order', 'asc')
	// 				->pluck('video_id')
	// 				->toArray();

	// 			$filePaths = [];

	// 			foreach ($video_ids_data as $video_id) {
	// 				$video = VideoStream::where('user_id', $userId)
	// 					->where('id', $video_id)
	// 					->first();

	// 				if (!$video) {
	// 					continue;
	// 				}

	// 				$fileRelativePath = $video->file_path;
	// 				$absoluteFilePath = storage_path("app/{$fileRelativePath}");
	// 				$filePaths[] = $absoluteFilePath;
	// 			}

	// 			$m3u8_path = $this->conCatenate();
	// 			if ($m3u8_path->getStatusCode() !== 200) {
	// 				$errorMessage = $m3u8_path->getData()->error;
	// 				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => $errorMessage, 'toast' => true]);
	// 			}
	// 			$content = $m3u8_path->getContent();

	// 			$data = json_decode($content, true);
	// 			if (isset($data['m3u8_path'])) {
	// 				$m3u8Path = $data['m3u8_path'];
	// 				$m3u8Path = str_replace('//', '/', $m3u8Path);
	// 				$output_file = $data['output_file'];
	// 				// $schedule->concatenate_path = $m3u8Path;
	// 				Channel::where('user_id', $userId)
	// 					->where('id', $channel_id)
	// 					->update(['concatenate_path' => $m3u8Path, 'output_file_path' => $output_file]);
	// 			}

	// 			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Videos rearranged successfully', 'toast' => true]);
	// 		} else {
	// 			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No videos provided for rearrangement', 'toast' => true]);
	// 		}
	// 	} catch (\Exception $e) {
	// 		Log::error('Error while rearranging videos: ' . $e->getMessage());
	// 		return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while rearranging videos', 'toast' => true]);
	// 	}
	// }
	public function copychannel(Request $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$channel_id = $request->channel_id;
			$originalEntry = Channel::where('id', $channel_id)->where('user_id', $user->id)->first();

			if (!$originalEntry) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Channel not found', 'toast' => true]);
			}

			$existingCopies = Channel::where('channel_name', 'like', "copy of " . $originalEntry->channel_name . '%')->count();
			$counter = $existingCopies > 0 ? " ($existingCopies)" : '';

			$duplicateEntry = $originalEntry->replicate();
			$duplicateEntry->channel_name = "copy of " . $originalEntry->channel_name . $counter;
			$duplicateEntry->save();
			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Channel copy successfully created', 'toast' => true]);
		} catch (\Exception $e) {
			Log::info('Channel copy error : ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
		}
	}
	public function analytics(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$channelId = $request->input('channel_id');
			$channel_data_show = [];

			$channel_data = channel::where('user_id', $user->id)->get();

			if ($channelId) {
				$channel = DB::table('channels')->where('user_id', $user->id)->where('id', $channelId)->first();
				if (!$channel) {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Channel not found.', 'toast' => true]);
				}
				$channel_data_show = [
					'id' => $channel->id,
					'channel_name' => $channel->channel_name,
					'views' => $channel->views,
				];
			} else {
				$channel = DB::table('channels')->where('user_id', $user->id)->get();
				$channel_data_show = $channel->map(function ($channel_item) {
					return [
						'id' => $channel_item->id,
						'channel_name' => $channel_item->channel_name,
						'views' => $channel_item->views,
					];
				})->toArray();
			}
			$total_views = $channel_data->sum('views');

			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Channel(s) retrieved successfully.', 'toast' => true], ['channel' => $channel_data_show, 'total_views' => $total_views]);
		} catch (\Exception $e) {
			Log::info('Error while getting anlystics: ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error retrieving channel.', 'toast' => true]);
		}
	}
	public function checkChannelName(Request $request)
	{
		try {
			$channelName = $request->input('channel_name');
			$counter = 0;
			if ($channelName === "Untitled Channel") {
				$existingChannelsCount = channel::where('channel_name', 'like', "%$channelName%")->count();
				if ($existingChannelsCount === 0) {
					return generateResponse(['type' => 'success', 'code' => 200, 'status' => false, 'message' => 'Channel name available.', 'toast' => true]);
				} else {
					$existingChannels = channel::where('channel_name', 'like', "%$channelName%")->get();
					$highestCounter = 0;
					foreach ($existingChannels as $existingChannel) {
						$matches = [];
						if (preg_match('/Untitled Channel \((\d+)\)/', $existingChannel->channel_name, $matches)) {
							$counter = intval($matches[1]);
							if ($counter > $highestCounter) {
								$highestCounter = $counter;
							}
						}
					}
					$counter = $highestCounter + 1;
					$channelName = "Untitled Channel ($counter)";
					return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Channel name already exists.', 'toast' => true, 'data' => ['counter' => $counter, 'channelName' => $channelName]]);
				}
			} else {
				$channelExists = Channel::where('channel_name', $channelName)->exists();

				if ($channelExists) {
					return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Channel name already exists.', 'toast' => true, 'data' => ['counter' => $counter, 'channelName' => $channelName]]);
				} else {
					return generateResponse(['type' => 'success', 'code' => 200, 'status' => false, 'message' => 'Channel name available.', 'toast' => true]);
				}
			}
		} catch (\Exception $e) {
			Log::info('Error while checking channel name: ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while checking channel name.', 'toast' => true]);
		}
	}
	public function removedestinationchannel(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$live_stream_id = $request->input('live_stream_id');
			$destinationChannelId = $request->input('destination_channel_id');

			$liveStream = LiveStream::where('user_id', $userId)->where('id', $live_stream_id)->where('destination_id', 'like', '%' . $destinationChannelId . '%')->first();

			if (!$liveStream) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No matching LiveStream found for the provided destination_channel_id', 'toast' => true, 'data' => []]);
			}
			$destinationIds = explode(',', $liveStream->destination_id);

			if (($key = array_search($destinationChannelId, $destinationIds)) !== false) {
				unset($destinationIds[$key]);
				$liveStream->destination_id = implode(',', $destinationIds);
				$liveStream->save();
				return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Destination channel removed successfully', 'toast' => true, 'data' => ['livestream' => $liveStream]]);
			} else {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'The destination_channel_id does not exist in the destination_id array', 'toast' => true, 'data' => []]);
			}
		} catch (\Exception $e) {
			Log::error('Error while removing destination channel: ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while removing destination channel', 'toast' => true, 'data' => []]);
		}
	}
	public function removeBlankSpace(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;

			$channel_id = $request->input('channel_id');
			$day = $request->input('day');

			$schedules = Schedular::where('channel_id', $channel_id)
				->where('user_id', $userId)
				->where('day', $day)
				->orderBy('start_time')
				->get();

			if ($schedules->isEmpty()) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No schedules found for the given channel and day.', 'toast' => true, 'data' => []]);
			}
			$previousEndTime = '00:00:00';

			foreach ($schedules as $schedule) {
				if (empty($schedule->start_time)) {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No start time found for one or more schedules.', 'toast' => true, 'data' => []]);
				}

				$schedule->start_time = $this->removeWhiteSpace($schedule->start_time);
				$schedule->end_time = $this->removeWhiteSpace($schedule->end_time);

				$endTime = date('H:i:s', strtotime($schedule->start_time) + $schedule->duration);

				$schedule->start_time = $previousEndTime;

				$schedule->end_time = date('H:i:s', strtotime($schedule->start_time) + $schedule->duration);
				$previousEndTime = $schedule->end_time;

				$schedule->save();
			}

			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blank spaces removed and schedules updated successfully for the channel.', 'toast' => true, 'data' => $schedules]);
		} catch (\Exception $e) {
			Log::error('Error while removing blank spaces: ' . $e->getMessage());
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing. Please try again later.', 'toast' => true]);
		}
	}
	private function removeWhiteSpace($time)
	{
		return str_replace(' ', '', $time);
	}

	// public function conCatenate(Request $request)
	// {
	// 	$outputFilePath = $this->concatenateMP4Files();

	// 	$response = $this->convertToHLS($outputFilePath);

	// 	$outputBlob = file_get_contents($outputFilePath);

	// 	$blobFileName = uniqid() . '.mp4';

	// 	$directory = public_path('scheduledvideos/2/2/2024-06-20/');


	// 	$outputBlobPath = $directory . $blobFileName;


	// 	file_put_contents($outputBlobPath, $outputBlob);

	// 	return response()->json([
	// 		'message' => 'Concatenation complete',
	// 		'output_file' => $outputFilePath,
	// 		'output_blob' => $outputBlobPath
	// 	]);
	// 	return response()->json(['error' => 'No files uploaded'], 400);
	// }
	// public function conCatenate(Request $request)
	// {
	// 	$user = $request->attributes->get('user');
	// 	$user_id = $user->id;
	// 	$channel_id = $request->channel_id;
	// 	$date = $request->date;
	// 	$epgDataJson = ScheduleChannles::where('channel_id', $channel_id)
	// 		->value('epg_data');

	// 	$epgData = json_decode($epgDataJson, true);

	// 	if (empty($epgData)) {
	// 		return generateResponse([
	// 			'type' => 'error',
	// 			'code' => 200,
	// 			'status' => false,
	// 			'message' => 'No data found for today.',
	// 			'toast' => true
	// 		]);
	// 	}

	// 	// Check if all entries have the same date
	// 	$uniqueDates = array_unique(array_column($epgData, 'date'));

	// 	$gaps = [];
	// 	if (count($uniqueDates) > 1) {
	// 		for ($i = 0; $i < count($epgData) - 1; $i++) {
	// 			$firstVideoTill = new \DateTime($epgData[$i]['till']);
	// 			$secondVideoSince = new \DateTime($epgData[$i + 1]['since']);

	// 			$interval = $firstVideoTill->diff($secondVideoSince);
	// 			$gapInSeconds = ($interval->days * 24 * 60 * 60) +
	// 				($interval->h * 60 * 60) +
	// 				($interval->i * 60) +
	// 				$interval->s;

	// 			$gaps[] = $gapInSeconds;
	// 		}

	// 		if (empty($gaps)) {
	// 			return generateResponse([
	// 				'type' => 'error',
	// 				'code' => 200,
	// 				'status' => false,
	// 				'message' => 'No gaps calculated between videos.',
	// 				'toast' => true
	// 			]);
	// 		}
	// 	}

	// 	$todayData = array_filter($epgData, function ($entry) use ($date) {
	// 		return $entry['date'] === $date;
	// 	});

	// 	if (empty($todayData)) {
	// 		return generateResponse([
	// 			'type' => 'error',
	// 			'code' => 200,
	// 			'status' => false,
	// 			'message' => 'No data found for today.',
	// 			'toast' => true
	// 		]);
	// 	}

	// 	$sinceTimes = array_column($todayData, 'since');
	// 	$tillTimes = array_column($todayData, 'till');
	// 	$earliestSince = min($sinceTimes);
	// 	$latestTill = max($tillTimes);

	// 	$outputFilePath = $this->concatenateMP4Files($user_id, $channel_id, $date, $gaps, $epgData);
	// 	Log::info($outputFilePath);
	// 	if (!$outputFilePath) {
	// 		return generateResponse([
	// 			'type' => 'error',
	// 			'code' => 200,
	// 			'status' => false,
	// 			'message' => 'Error while processing. Please try again later.',
	// 			'toast' => true
	// 		]);
	// 	}

	// 	$playlistpath = $this->convertToHLS($outputFilePath, $user_id, $channel_id, $date);
	// 	$playlistpathLink = getFileTemporaryURL($playlistpath);
	// 	$outputBlob = file_get_contents($outputFilePath);
	// 	$blobFileName = uniqid() . '.mp4';
	// 	$directory = "users/private/{$user_id}/streamdeck/{$channel_id}/{$date}/binary/";
	// 	$outputBlobPath = $directory . $blobFileName;
	// 	Storage::put($outputBlobPath, $outputBlob);

	// 	TvLivestream::updateOrCreate(
	// 		[
	// 			'user_id' => $user_id,
	// 			'channel_id' => $channel_id,
	// 			'date' => $date,
	// 		],
	// 		[
	// 			'output_file' => $outputFilePath,
	// 			'output_blob' => $outputBlobPath,
	// 			'playlistpathLink' => $playlistpath,
	// 			'earliest_since' => $earliestSince,
	// 			'latest_till' => $latestTill
	// 		]
	// 	);
	// 	$this->cleanupOldFilesAndDatabaseEntries($user_id, $channel_id, $date);

	// 	return generateResponse([
	// 		'type' => 'success',
	// 		'code' => 200,
	// 		'status' => true,
	// 		'message' => 'Videos m3u8 and concatenation successful',
	// 		'toast' => true
	// 	], [
	// 		'output_file' => $outputFilePath,
	// 		'output_blob' => $outputBlobPath,
	// 		'playlistpathLink' => $playlistpathLink,
	// 		'earliest_since' => $earliestSince,
	// 		'latest_till' => $latestTill,
	// 		'gaps' => $gaps
	// 	]);
	// }
	// public function cleanupOldFilesAndDatabaseEntries($user_id, $channel_id, $date)
	// {
	// 	$directory = public_path("scheduledvideos/{$user_id}/{$channel_id}/{$date}/");
	// 	if (is_dir($directory)) {
	// 		$files = scandir($directory);
	// 		foreach ($files as $file) {
	// 			if ($file === '.' || $file === '..') {
	// 				continue;
	// 			}
	// 			// Check if the file name starts with "out-"
	// 			if (strpos($file, 'out-') === 0) {
	// 				$filePath = $directory . $file;
	// 				if (is_file($filePath)) {
	// 					unlink($filePath);
	// 				}
	// 			}
	// 		}
	// 		// If the directory is empty after deleting files, remove the directory
	// 		if (count(array_diff(scandir($directory), array('.', '..'))) === 0) {
	// 			rmdir($directory);
	// 		}
	// 	}
	// 	// Also delete related entries from ScheduleChannels
	// 	// ScheduleChannels::where('channel_id', $channel_id)
	// 	//     ->forceDelete();
	// }

	// public function concatenateMP4Files($user_id, $channel_id, $date, $gaps, $epgData)
	// {
	// 	$directory = public_path("scheduledvideos/{$user_id}/{$channel_id}/{$date}/");
	// 	$files = scandir($directory);
	// 	$filePaths = [];
	// 	$orderedFilePaths = [];
	// 	$epgFileNames = [];

	// 	// Create a mapping of file names from $epgData
	// 	foreach ($epgData as $epg) {
	// 		$epgFileNames[] = basename($epg['public_file_path']);
	// 	}

	// 	$ffmpegPath = config('app.ffmpeg_binaries');

	// 	$ffmpeg = \FFMpeg\FFMpeg::create([
	// 		'ffmpeg.binaries'  => config('app.ffmpeg_binaries'),
	// 		'ffprobe.binaries' => config('app.ffprobe_binaries'),
	// 	]);

	// 	// Path to the gap.mp4 file
	// 	$gapFilePath = public_path('4/video/gap.mp4');
	// 	if (!file_exists($gapFilePath)) {
	// 		// Handle if gap.mp4 does not exist
	// 		return null;
	// 	}

	// 	foreach ($files as $file) {
	// 		$filePath = $directory . $file;
	// 		if (!is_file($filePath)) {
	// 			continue;
	// 		}

	// 		$extension = pathinfo($file, PATHINFO_EXTENSION);
	// 		if ($extension === 'mp4') {
	// 			$filePaths[basename($file)] = $filePath;
	// 		} else {
	// 			$outputFilePath = $directory . 'temp_' . $file . '.mp4';
	// 			$command = "{$ffmpegPath} -i " . escapeshellarg($filePath) . " -c:v libx264 -preset ultrafast -c:a aac " . escapeshellarg($outputFilePath);
	// 			exec($command);
	// 			$filePaths[basename($file)] = $outputFilePath;
	// 		}
	// 	}

	// 	// Order the file paths according to $epgData
	// 	foreach ($epgFileNames as $fileName) {
	// 		if (isset($filePaths[$fileName])) {
	// 			$orderedFilePaths[] = $filePaths[$fileName];
	// 		}
	// 	}

	// 	if (empty($orderedFilePaths)) {
	// 		return null;
	// 	}

	// 	// If there is only one file, return its path directly
	// 	if (count($orderedFilePaths) === 1) {
	// 		return $orderedFilePaths[0];
	// 	}

	// 	$outputFileName = 'out-' . time() . '.mp4';
	// 	$outputFilePath = public_path("scheduledvideos/{$user_id}/{$channel_id}/{$date}/" . $outputFileName);

	// 	$finalFileList = [];
	// 	$finalFileList[] = $orderedFilePaths[0];

	// 	for ($i = 1; $i < count($orderedFilePaths); $i++) {
	// 		if (!empty($gaps)) {
	// 			$gapDuration = $gaps[$i - 1];

	// 			// Create temporary gap file with the selected duration
	// 			$tempGapFilePath = $directory . 'temp_gap_' . uniqid() . '.mp4';
	// 			$command = "{$ffmpegPath} -stream_loop -1 -t {$gapDuration} -i " . escapeshellarg($gapFilePath) . " -c copy " . escapeshellarg($tempGapFilePath);
	// 			exec($command);

	// 			$finalFileList[] = $tempGapFilePath;
	// 		}
	// 		$finalFileList[] = $orderedFilePaths[$i];
	// 	}

	// 	// Concatenate files using FFMpeg
	// 	$ffmpeg->open($finalFileList[0])
	// 		->concat($finalFileList)
	// 		->saveFromSameCodecs($outputFilePath, true);

	// 	// Clean up temporary files
	// 	foreach ($filePaths as $tempFile) {
	// 		if (file_exists($tempFile) && strpos($tempFile, 'temp_') !== false) {
	// 			unlink($tempFile);
	// 		}
	// 	}

	// 	foreach ($finalFileList as $tempGapFile) {
	// 		if (strpos($tempGapFile, 'temp_gap_') !== false && file_exists($tempGapFile)) {
	// 			unlink($tempGapFile);
	// 		}
	// 	}

	// 	return $outputFilePath;
	// }


	// public function convertToHLS($outputFilePath, $user_id, $channel_id, $date)
	// {
	// 	$folderPath = "users/private/{$user_id}/streamdeck/{$channel_id}/{$date}/m3u8";

	// 	$ffmpegPath = config('app.ffmpeg_binaries');

	// 	if (!Storage::exists($folderPath)) {
	// 		Storage::makeDirectory($folderPath);
	// 	}

	// 	$playlistPath = storage_path("app/{$folderPath}/playlist.m3u8");

	// 	$command = "{$ffmpegPath} -i " . escapeshellarg($outputFilePath) . " -codec: copy -start_number 0 -hls_time 10 -hls_list_size 0 -f hls " . escapeshellarg($playlistPath);
	// 	exec($command);

	// 	$returnedPath = "{$folderPath}/playlist.m3u8";

	// 	return $returnedPath;
	// }
	public function conCatenate(Request $request)
	{
		$user = $request->attributes->get('user');
		$user_id = $user->id;
		$channel_id = $request->channel_id;
		$date = $request->date;

		$existingData = TvLivestream::where('user_id', $user_id)
			->where('channel_id', $channel_id)
			->where('date', $date)
			->first();

		if ($existingData) {
			$this->deleteExistingFolders($existingData->output_blob, $existingData->playlistpathLink);
		}

		$epgDataJson = ScheduleChannles::where('channel_id', $channel_id)
			->value('epg_data');

		$epgData = json_decode($epgDataJson, true);

		if (empty($epgData)) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'No data found for today.',
				'toast' => true
			]);
		}

		// Check if all entries have the same date
		$uniqueDates = array_unique(array_column($epgData, 'date'));

		$todayData = array_filter($epgData, function ($entry) use ($date) {
			return $entry['date'] === $date;
		});

		if (empty($todayData)) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'No data found for today.',
				'toast' => true
			]);
		}

		$sinceTimes = array_column($todayData, 'since');
		$tillTimes = array_column($todayData, 'till');
		$earliestSince = min($sinceTimes);
		$latestTill = max($tillTimes);

		$outputFilePath = $this->concatenateMP4Files($user_id, $channel_id, $date, $epgData);
		Log::info($outputFilePath);
		if (!$outputFilePath) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Error while processing. Please try again later.',
				'toast' => true
			]);
		}

		$playlistpath = $this->convertToHLS($outputFilePath, $user_id, $channel_id, $date);
		$playlistpathLink = getFileTemporaryURL($playlistpath);
		$outputBlob = file_get_contents($outputFilePath);
		$blobFileName = uniqid() . '.mp4';
		$directory = "users/private/{$user_id}/streamdeck/{$channel_id}/{$date}/binary/";
		$outputBlobPath = $directory . $blobFileName;
		Storage::put($outputBlobPath, $outputBlob);

		TvLivestream::updateOrCreate(
			[
				'user_id' => $user_id,
				'channel_id' => $channel_id,
				'date' => $date,
				'status' => '1'
			],
			[
				'output_file' => $outputFilePath,
				'output_blob' => $outputBlobPath,
				'playlistpathLink' => $playlistpath,
				'earliest_since' => $earliestSince,
				'latest_till' => $latestTill
			]
		);
		$this->cleanupOldFilesAndDatabaseEntries($user_id, $channel_id, $date);
		$this->broadcastStatus($channel_id);
		return generateResponse([
			'type' => 'success',
			'code' => 200,
			'status' => true,
			'message' => 'Videos m3u8 and concatenation successful',
			'toast' => true
		], [
			'output_file' => $outputFilePath,
			'output_blob' => $outputBlobPath,
			'playlistpathLink' => $playlistpathLink,
			'earliest_since' => $earliestSince,
			'latest_till' => $latestTill
		]);
	}
	private function deleteExistingFolders($outputBlobPath, $playlistPathLink)
	{
		$blobDirectory = dirname(storage_path("app/" . $outputBlobPath));
		$playlistDirectory = dirname(storage_path("app/" . $playlistPathLink));
		if (is_dir($blobDirectory)) {
			Storage::deleteDirectory($blobDirectory);
		}
		if (is_dir($playlistDirectory)) {
			Storage::deleteDirectory($playlistDirectory);
		}
	}
	private function broadcastStatus($channel_id)
	{
		try {
			DB::beginTransaction();
			// $channel_id = $request->channel_id;
			$scheduleChannels = DB::table('schedule_channles')
				->where('channel_id', $channel_id)
				->get();

			foreach ($scheduleChannels as $channel) {
				$epgData = json_decode($channel->epg_data, true);
				foreach ($epgData as &$program) {
					$program['is_broadcasted'] = 1;
				}
				$updatedEpgData = json_encode($epgData);
				DB::table('schedule_channles')
					->where('id', $channel->id)
					->update(['epg_data' => $updatedEpgData]);
			}
			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => false, 'message' => 'Success', 'toast' => false]);
		} catch (\Exception $e) {
			Log::info('Error: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error', 'toast' => true]);
		}
	}
	public function cleanupOldFilesAndDatabaseEntries($user_id, $channel_id, $date)
	{
		$directory = public_path("scheduledvideos/{$user_id}/{$channel_id}/{$date}/");
		if (is_dir($directory)) {
			$files = scandir($directory);
			foreach ($files as $file) {
				if ($file === '.' || $file === '..') {
					continue;
				}
				// Check if the file name starts with "out-"
				if (strpos($file, 'out-') === 0) {
					$filePath = $directory . $file;
					if (is_file($filePath)) {
						unlink($filePath);
					}
				}
			}
			// If the directory is empty after deleting files, remove the directory
			if (count(array_diff(scandir($directory), array('.', '..'))) === 0) {
				rmdir($directory);
			}
		}
		// Also delete related entries from ScheduleChannels
		// ScheduleChannels::where('channel_id', $channel_id)
		//     ->forceDelete();
	}

	public function concatenateMP4Files($user_id, $channel_id, $date, $epgData)
	{
		$directory = public_path("scheduledvideos/{$user_id}/{$channel_id}/{$date}/");
		$files = scandir($directory);
		$filePaths = [];
		$orderedFilePaths = [];
		$epgFileNames = [];

		// Create a mapping of file names from $epgData
		foreach ($epgData as $epg) {
			$epgFileNames[] = basename($epg['public_file_path']);
		}

		$ffmpegPath = config('app.ffmpeg_binaries');

		$ffmpeg = \FFMpeg\FFMpeg::create([
			'ffmpeg.binaries'  => config('app.ffmpeg_binaries'),
			'ffprobe.binaries' => config('app.ffprobe_binaries'),
		]);

		foreach ($files as $file) {
			$filePath = $directory . $file;
			if (!is_file($filePath)) {
				continue;
			}

			$extension = pathinfo($file, PATHINFO_EXTENSION);
			if ($extension === 'mp4') {
				$filePaths[basename($file)] = $filePath;
			} else {
				$outputFilePath = $directory . 'temp_' . $file . '.mp4';
				$command = "{$ffmpegPath} -i " . escapeshellarg($filePath) . " -c:v libx264 -preset ultrafast -c:a aac " . escapeshellarg($outputFilePath);
				exec($command);
				$filePaths[basename($file)] = $outputFilePath;
			}
		}

		// Order the file paths according to $epgData
		foreach ($epgFileNames as $fileName) {
			if (isset($filePaths[$fileName])) {
				$orderedFilePaths[] = $filePaths[$fileName];
			}
		}

		if (empty($orderedFilePaths)) {
			return null;
		}

		// If there is only one file, return its path directly
		if (count($orderedFilePaths) === 1) {
			return $orderedFilePaths[0];
		}

		$outputFileName = 'out-' . time() . '.mp4';
		$outputFilePath = public_path("scheduledvideos/{$user_id}/{$channel_id}/{$date}/" . $outputFileName);

		// Concatenate files using FFMpeg
		$ffmpeg->open($orderedFilePaths[0])
			->concat($orderedFilePaths)
			->saveFromSameCodecs($outputFilePath, true);

		// Clean up temporary files
		foreach ($filePaths as $tempFile) {
			if (file_exists($tempFile) && strpos($tempFile, 'temp_') !== false) {
				unlink($tempFile);
			}
		}

		return $outputFilePath;
	}

	public function convertToHLS($outputFilePath, $user_id, $channel_id, $date)
	{
		$folderPath = "users/private/{$user_id}/streamdeck/{$channel_id}/{$date}/m3u8";

		$ffmpegPath = config('app.ffmpeg_binaries');

		if (!Storage::exists($folderPath)) {
			Storage::makeDirectory($folderPath);
		}


		// Generate 480p version
		$playlistPath480p = storage_path("app/{$folderPath}/playlist_480p.m3u8");
		$command480p = "{$ffmpegPath} -i " . escapeshellarg($outputFilePath) . " -vf scale=w=854:h=480 -c:a copy -start_number 0 -hls_time 10 -hls_list_size 0 -f hls " . escapeshellarg($playlistPath480p);
		exec($command480p);

		$returnedPath = "{$folderPath}/playlist_480p.m3u8";

		return $returnedPath;
	}

	// public function convertToHLS($outputFilePath, $user_id, $channel_id, $date)
	// {
	// 	$folderPath = "users/private/{$user_id}/streamdeck/{$channel_id}/{$date}/m3u8";

	// 	$ffmpegPath = config('app.ffmpeg_binaries');
	// 	$ffmpegprobePath = config('app.ffprobe_binaries');

	// 	if (!Storage::exists($folderPath)) {
	// 		Storage::makeDirectory($folderPath);
	// 	}

	// 	$tsFilePath = storage_path("app/{$folderPath}/single_segment.ts");
	// 	$playlistPath = storage_path("app/{$folderPath}/playlist.m3u8");

	// 	// Execute ffmpeg command to convert the input file into a single .ts segment
	// 	$command = "{$ffmpegPath} -i " . escapeshellarg($outputFilePath) . " -c copy -bsf:v h264_mp4toannexb -f mpegts " . escapeshellarg($tsFilePath);
	// 	exec($command);

	// 	// Execute ffprobe command to get segment duration
	// 	$ffprobeCommand = "{$ffmpegprobePath} -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($tsFilePath);
	// 	$segmentDuration = (float) shell_exec($ffprobeCommand);

	// 	// Ensure minimum duration of 1 second for empty or very short segments
	// 	if ($segmentDuration <= 0.1) {
	// 		$segmentDuration = 1.0;
	// 	}

	// 	// Create the HLS playlist content
	// 	$playlistContent = "#EXTM3U\n";
	// 	$playlistContent .= "#EXT-X-VERSION:6\n";
	// 	$playlistContent .= "#EXT-X-TARGETDURATION:" . ceil($segmentDuration) . "\n";
	// 	$playlistContent .= "#EXT-X-MEDIA-SEQUENCE:0\n";
	// 	$playlistContent .= "#EXT-X-DISCONTINUITY-SEQUENCE:0\n";
	// 	$playlistContent .= "#EXT-X-ALLOW-CACHE:NO\n\n";
	// 	$playlistContent .= "#EXT-X-DISCONTINUITY\n";
	// 	$playlistContent .= "#EXTINF:{$segmentDuration},\n";
	// 	$playlistContent .= "single_segment.ts\n\n";
	// 	$playlistContent .= "#EXT-X-ENDLIST\n";

	// 	// Write playlist to file
	// 	file_put_contents($playlistPath, $playlistContent);
	// 	$returnedPath = "{$folderPath}/playlist.m3u8";

	// 	return $returnedPath;
	// }

	public function setBroadcastStatus(Request $request)
	{
		try {
			$channel = TVLivestream::where('channel_id', $request->channel_id)->firstOrFail();
			$channel->status = $channel->status == '1' ? '0' : '1';
			$channel->save();
			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Broadcast status updated successfully',
				'toast' => true
			]);
		} catch (\Exception $e) {
			Log::error('Error uploading status: ' . $e->getMessage());
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Error while processing',
				'toast' => true
			]);
		}
	}

	//This function is to add videos in scheduler
	public function addScheduler(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$videoIdsInput = $request->input('video_id');
			$video_ids = json_decode($videoIdsInput, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$video_ids = [$videoIdsInput];
			} elseif (!is_array($video_ids)) {
				$video_ids = [$video_ids];
			}
			foreach ($video_ids as $video_id) {
				VideoStream::where('id', $video_id)->update(['is_scheduled' => "1"]);
			}

			if ($request->has('deleteSchedule')) {
				VideoStream::where('id', $request->get('deleteSchedule'))->update(['is_scheduled' => "0"]);
				return generateResponse([
					'type' => 'success',
					'code' => 200,
					'status' => true,
					'message' => 'Video removed from scheduler',
					'toast' => true
				]);
			}

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Video(s) added to scheduler successfully',
				'toast' => true
			]);
		} catch (\Exception $e) {
			Log::error('Error during adding video to scheduler: ' . $e->getMessage());
			return generateResponse([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => 'Error while processing',
				'toast' => true
			]);
		}
	}

	public function showSchedule(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$user_id = $user->id;
			$schedules = VideoStream::where('user_id', $user_id)->where('is_scheduled', '1')->get();
			$currentTime = now();

			$formattedSchedules = $schedules->map(function ($schedule) use ($currentTime, $user_id) {
				$durationInSeconds = $schedule->duration * 60;
				$endTime = $currentTime->copy()->addSeconds($durationInSeconds);

				return [
					'id' => $schedule->channel_uuid,
					'channel_id' => $schedule->id,
					'video_id' => $schedule->id,
					'title' => $schedule->title,
					'image' => getFileTemporaryURL($schedule->thumbnail),
					// 'till' => $endTime->format('H:i:s'),
					'duration' => $schedule->duration,
					'file_path' => $schedule->file_path,
					'playback_path' => getFileTemporaryURL($schedule->file_path),
					'user_id' => $user_id,
				];
			});

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Schedules fetched successfully',
				'toast' => true,
				'data' => $formattedSchedules,
			]);
		} catch (\Exception $e) {
			Log::error('Error during fetching schedules: ' . $e->getMessage());
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Error while processing',
				'toast' => true,
			]);
		}
	}


	public function setSchedule(Request $request)
	{
		try {
			$items = $request->json()->all();

			if (!is_array($items)) {
				return response()->json(['error' => 'Invalid data format'], 400);
			}
			foreach ($items as $item) {
				if (isset($item['is_saved']) && $item['is_saved'] == 1) {
					continue;
				}
				$channelUuid = $item['channelUuid'] ?? null;
				$channel_id = $item['channel_id'] ?? null;
				$itemId = $item['id'] ?? null;
				$filePath = $item['file_path'] ?? null;
				$user_id = $item['user_id'] ?? null;

				if ($channelUuid === null || $itemId === null || $filePath === null) {
					return response()->json(['error' => 'Missing channelUuid, id, or file_path'], 400);
				}

				$todaysDate = $item['date'] ?? null;
				$baseFileName = basename($filePath);
				$newFileDir = public_path("scheduledvideos/{$user_id}/{$channel_id}/{$todaysDate}/");
				$newFilePath = $newFileDir . $baseFileName;

				if (!File::isDirectory($newFileDir)) {
					File::makeDirectory($newFileDir, 0755, true);
				}

				$fileCounter = 1;
				$fileNameWithoutExtension = pathinfo($baseFileName, PATHINFO_FILENAME);
				$fileExtension = pathinfo($baseFileName, PATHINFO_EXTENSION);

				while (File::exists($newFilePath)) {
					$newFileName = "{$fileNameWithoutExtension}({$fileCounter}).{$fileExtension}";
					$newFilePath = $newFileDir . $newFileName;
					$fileCounter++;
				}

				File::copy(storage_path("app/{$filePath}"), $newFilePath);

				$publicFilePath = "scheduledvideos/{$user_id}/{$channel_id}/{$todaysDate}/" . basename($newFilePath);
				$item['public_file_path'] = $publicFilePath;
				$item['is_broadcasted'] = 0;
				$item['is_saved'] = 1;

				$scheduleChannel = ScheduleChannles::where('channelUuid', $channelUuid)->first();

				if ($scheduleChannel) {
					$existingData = json_decode($scheduleChannel->epg_data, true);
					$updated = false;

					foreach ($existingData as &$existingItem) {
						if ($existingItem['id'] === $itemId) {
							$existingItem = $item;
							$updated = true;
							break;
						}
					}

					if (!$updated) {
						$existingData[] = $item;
					}

					$scheduleChannel->update([
						'epg_data' => json_encode($existingData)
					]);
				} else {
					ScheduleChannles::create([
						'channel_id' => $channel_id,
						'channelUuid' => $channelUuid,
						'epg_data' => json_encode([$item])
					]);
				}
			}

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Program Scheduled Successfully',
				'toast' => true
			]);
		} catch (\Exception $e) {
			Log::error('Error during setting schedules: ' . $e->getMessage());
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Error while processing',
				'toast' => true,
			]);
		}
	}


	public function getScheduleData(Request $request)
	{
		try {
			$channelId = $request->channel_id;
			// $scheduleChannel = ScheduleChannles::find($channelId);
			$scheduleChannel = ScheduleChannles::where('channel_id', $channelId)
				->first();
			if ($scheduleChannel) {
				$epgData = $scheduleChannel->epg_data;

				return response()->json([
					'type' => 'success',
					'code' => 200,
					'status' => true,
					'message' => 'Data fetched successfully',
					'data' => $epgData,
					'toast' => true,
				]);
			} else {
				return response()->json([
					'type' => 'error',
					'code' => 404,
					'status' => false,
					'message' => 'Record not found',
					'toast' => true,
				]);
			}
		} catch (\Exception $e) {
			Log::error('Error during fetching program data: ' . $e->getMessage());
			return response()->json([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => 'Error while processing',
				'toast' => true,
			]);
		}
	}

	public function setStreamStatus(Request $request)
	{
		try {
			$identifierKey = $request->identifier_key;
			$streamStatus = $request->stream_status;
			$todaysTime = date('H:i:s');
			$stream = LiveStream::where('stream_title', $identifierKey)
				->orWhere('stream_key_id', $identifierKey)
				->first();

			if (!$stream) {
				return response()->json([
					'type' => 'error',
					'code' => 404,
					'status' => false,
					'message' => 'Stream not found',
					'toast' => true,
				], 404);
			}
			$stream->stream_status = $streamStatus;
			$stream->live_start_time = $todaysTime;
			$stream->save();

			return response()->json([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Stream status updated successfully',
				'toast' => true,
			], 200);
		} catch (\Exception $e) {
			Log::error('Error during seting stream status: ' . $e->getMessage());
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Error while processing',
				'toast' => true,
			]);
		}
	}
	//To store streaming files in laravel 
	public function uploadM3U8Stream(Request $request)
	{
		$ffmpegBinaries = config('app.ffmpeg_binaries');
		$ffprobeBinaries = config('app.ffprobe_binaries');

		// Initialize FFMpeg instance
		$ffmpeg = FFMpeg::create(
			[
				'ffmpeg.binaries'  => $ffmpegBinaries,
				'ffprobe.binaries' => $ffprobeBinaries,
			]
		);

		$userId = $request->input('userId');
		$sessionId = $request->input('sessionId');
		$videoBitrate = 1000; // Assuming video bitrate in kbps

		$outputDir1 = 'users/private';
		$userFolder = "$outputDir1/$userId/streamdeck";
		$userDirectory = "$userFolder/session_$sessionId";

		// Create necessary directories if they don't exist
		if (!Storage::exists($userDirectory)) {
			Storage::makeDirectory($userDirectory);
		}

		// Move the uploaded segment to the designated directory
		$segmentCount = count(Storage::files($userDirectory)) - 1; // Exclude existing playlist.m3u8
		$segmentFilename = "segment$segmentCount.ts";
		$request->file('file')->storeAs($userDirectory, $segmentFilename);

		// Delete the existing output.mp4 file if it exists
		$outputMp4Path = "$userDirectory/output.mp4";
		if (Storage::exists($outputMp4Path)) {
			Storage::delete($outputMp4Path);
		}

		// Concatenate all .ts files into a single .mp4 file
		$tsFiles = Storage::files($userDirectory);
		$concatenatedTsList = "";
		foreach ($tsFiles as $tsFile) {
			if (pathinfo($tsFile, PATHINFO_EXTENSION) === 'ts') {
				$concatenatedTsList .= "file '" . Storage::path($tsFile) . "'\n";
			}
		}
		Storage::put("$userDirectory/ts_list.txt", $concatenatedTsList);
		$ffmpegConcatCommand = "ffmpeg -f concat -safe 0 -i " . escapeshellarg(Storage::path("$userDirectory/ts_list.txt")) . " -c copy " . escapeshellarg(Storage::path($outputMp4Path));
		shell_exec($ffmpegConcatCommand);

		// Clean up: delete the temporary .ts list file
		Storage::delete("$userDirectory/ts_list.txt");

		// Generate M3U8 playlist content
		$maxDuration = 0;
		$playlistContent = "#EXTM3U\n#EXT-X-VERSION:6\n#EXT-X-MEDIA-SEQUENCE:0\n";
		foreach ($tsFiles as $tsFile) {
			$filename = pathinfo($tsFile, PATHINFO_BASENAME);
			if (pathinfo($tsFile, PATHINFO_EXTENSION) === 'ts') { // Exclude the playlist and mp4 file from the playlist
				// Get the duration of the .ts file using ffprobe
				$ffprobeCommand = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg(Storage::path($tsFile));
				$segmentDuration = floatval(shell_exec($ffprobeCommand));
				$maxDuration = max($maxDuration, $segmentDuration);
				$playlistContent .= "#EXT-X-DISCONTINUITY\n#EXTINF:" . number_format($segmentDuration, 3) . ",\n" . $filename . "\n";
			}
		}
		$playlistContent = "#EXTM3U\n#EXT-X-VERSION:6\n#EXT-X-MEDIA-SEQUENCE:0\n#EXT-X-TARGETDURATION:" . ceil($maxDuration) . "\n" . $playlistContent;
		$playlistContent .= "#EXT-X-ENDLIST\n";

		// Write playlist content to file
		$playlistPath = "$userDirectory/playlist.m3u8";
		Storage::put($playlistPath, $playlistContent);

		// Validate the MP4 duration (optional)
		$ffprobeMp4Command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg(Storage::path($outputMp4Path));
		$mp4Duration = floatval(shell_exec($ffprobeMp4Command));
		$thumbnailPath = "$userDirectory/thumbnail.jpg";
		$video = $ffmpeg->open(Storage::path($outputMp4Path));
		$frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1));
		$frame->save(Storage::path($thumbnailPath));
		return response()->json([
			'playlist_path' => Storage::url($playlistPath),
			'mp4_duration' => $mp4Duration,
		]);
	}

	public function getVideos($userId)
	{
		$outputDir1 = 'users/private';
		$userDirectory = "$outputDir1/$userId/streamdeck";

		try {
			if (!Storage::exists($userDirectory) || !Storage::directories($userDirectory)) {
				return response()->json(['error' => 'User directory not found'], 404);
			}

			$folders = Storage::directories($userDirectory);
			$baseURL = url("storage/$userDirectory");

			$videoLinks = array_filter($folders, function ($folder) {
				return strpos(basename($folder), 'session_') === 0;
			});

			$videoLinks = array_map(function ($folder) use ($userId, $baseURL, $outputDir1) {
				$folderName = basename($folder);

				preg_match('/session_(\d{8})/', $folderName, $matches);
				if (isset($matches[1])) {
					$videoTime = DateTime::createFromFormat('Ymd', $matches[1])->format('Y-m-d');
				} else {
					$videoTime = null;
				}

				$relativePath = "$outputDir1/$userId/streamdeck/$folderName/playlist.m3u8";
				$outputpath = "$outputDir1/$userId/streamdeck/$folderName/output.mp4";
				$storagePath = storage_path("app/" . $outputpath);
				$link = "$baseURL/$folderName/playlist.m3u8";
				$thumbnail_path = "$outputDir1/$userId/streamdeck/$folderName/thumbnail.jpg";
				$getID3 = new getID3();
				$fileInfo = $getID3->analyze($storagePath);
				if (isset($fileInfo['playtime_seconds'])) {
					$duration = $fileInfo['playtime_seconds'];
				} else {
					$duration = null;
				}

				return [
					'folder' => $folderName,
					'link' => $link,
					'userId' => $userId,
					'relativePath' => getFileTemporaryURL($relativePath),
					'thumbnail' => getFileTemporaryURL($thumbnail_path),
					'filepath' => $outputpath,
					'thumbnail_path' => $thumbnail_path,
					'video_time' => $videoTime,
					'duration' => (int)$duration,
				];
			}, $videoLinks);

			usort($videoLinks, function ($a, $b) {
				return strcmp($b['video_time'], $a['video_time']);
			});

			return response()->json($videoLinks);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
		}
	}

	public function deleteVideo($userId, $folder)
	{
		$outputDir1 = 'users/private';
		$videoPath = "$outputDir1/$userId/streamdeck/$folder";

		try {
			if (!Storage::exists($videoPath)) {
				return response()->json(['error' => 'Video directory not found'], 404);
			}

			Storage::deleteDirectory($videoPath);

			return response()->json(['message' => 'Video deleted successfully']);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
		}
	}

	public function uploadM3U8(Request $request)
	{
		$ffmpegBinaries = config('app.ffmpeg_binaries');
		$ffprobeBinaries = config('app.ffprobe_binaries');

		$ffmpeg = FFMpeg::create(
			[
				'ffmpeg.binaries'  => $ffmpegBinaries,
				'ffprobe.binaries' => $ffprobeBinaries,
			]
		);
		$userId = $request->input('userId');
		$channelKey = $request->input('channel_key');
		$segment = $request->file('file');

		$filePath = "users/private/$userId/streamdeck/manifest/$channelKey";
		$fullPath = storage_path("app/$filePath");

		if (!File::exists($fullPath)) {
			File::makeDirectory($fullPath, 0755, true);
		}

		$tsFiles = File::files($fullPath);
		$segmentCount = count($tsFiles);
		$segmentFilename = "$segmentCount.ts";
		$segment->move($fullPath, $segmentFilename);

		$outputMp4Path = "$fullPath/output.mp4";
		if (File::exists($outputMp4Path)) {
			File::delete($outputMp4Path);
		}

		$concatenatedTsList = "";
		foreach ($tsFiles as $tsFile) {
			if ($tsFile->getExtension() === 'ts') {
				$concatenatedTsList .= "file '" . $tsFile->getRealPath() . "'\n";
			}
		}
		File::put("$fullPath/ts_list.txt", $concatenatedTsList);
		$ffmpegCommand = "ffmpeg -f concat -safe 0 -i " . escapeshellarg("$fullPath/ts_list.txt") . " -c copy " . escapeshellarg($outputMp4Path);
		shell_exec($ffmpegCommand);

		File::delete("$fullPath/ts_list.txt");

		$maxDuration = 0;
		foreach ($tsFiles as $tsFile) {
			if ($tsFile->getExtension() === 'ts') {
				try {
					$ffprobeCommand = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($tsFile->getRealPath());
					$segmentDuration = floatval(shell_exec($ffprobeCommand));
					$maxDuration = max($maxDuration, $segmentDuration);
				} catch (\Exception $e) {
					Log::error("Error getting duration for {$tsFile->getFilename()}: {$e->getMessage()}");
				}
			}
		}

		$playlistContent = "#EXTM3U\n#EXT-X-VERSION:6\n#EXT-X-TARGETDURATION:" . ceil($maxDuration) . "\n#EXT-X-MEDIA-SEQUENCE:0\n#EXT-X-DISCONTINUITY\n";

		$segmentCount = 0;
		foreach ($tsFiles as $index => $tsFile) {
			if ($tsFile->getExtension() === 'ts') {
				try {
					$ffprobeCommand = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($tsFile->getRealPath());
					$segmentDuration = floatval(shell_exec($ffprobeCommand));
					$playlistContent .= "#EXTINF:" . number_format($segmentDuration, 3) . ",\n" . $segmentCount++ . ".ts\n#EXT-X-DISCONTINUITY\n";
				} catch (\Exception $e) {
					Log::error("Error getting duration for {$tsFile->getFilename()}: {$e->getMessage()}");
				}
			}
		}

		$playlistContent .= "#EXT-X-ENDLIST\n";
		$playlistPath = "$fullPath/stream.m3u8";
		File::put($playlistPath, $playlistContent);

		$ffprobeMp4Command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($outputMp4Path);
		$mp4Duration = floatval(shell_exec($ffprobeMp4Command));

		// Generate the thumbnail
		try {
			$video = $ffmpeg->open($outputMp4Path);
			$frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1));
			$thumbnailPath = "$fullPath/thumbnail.jpg";
			$frame->save($thumbnailPath);
		} catch (\Exception $e) {
			Log::error("Error generating thumbnail for {$outputMp4Path}: {$e->getMessage()}");
		}

		return response()->json([
			'playlist_path' => Storage::url("$filePath/stream.m3u8"),
			'mp4_path' => Storage::url("$filePath/output.mp4"),
			'relative_path' => $filePath . "/stream.m3u8",
			'mp4_duration' => $mp4Duration,
			'thumbnail_path' => Storage::url("$filePath/thumbnail.jpg")
		]);
	}

	public function deleteManifestFile($userId, $key, $fileName)
	{
		$folderPath = "users/private/$userId/streamdeck/manifest/$key";

		try {
			if (File::exists(storage_path("app/$folderPath"))) {
				File::deleteDirectory(storage_path("app/$folderPath"));

				return response()->json(['message' => "Manifest folder $key and its contents deleted successfully"]);
			} else {
				return response()->json(['error' => "Manifest folder $key not found"], 404);
			}
		} catch (\Exception $e) {
			Log::error("Error deleting manifest folder $key: {$e->getMessage()}");
			return response()->json(['error' => 'Internal Server Error'], 500);
		}
	}

	public function getManifestFiles($userId)
	{
		$manifestDir = storage_path("app/users/private/$userId/streamdeck/manifest");

		if (!File::exists($manifestDir) || !File::isDirectory($manifestDir)) {
			return response()->json(['message' => 'Manifest directory not found']);
		}

		try {
			$keys = File::directories($manifestDir);
			$manifestLinks = [];
			foreach ($keys as $key) {
				$keyName = basename($key);
				$m3u8File = "users/private/$userId/streamdeck/manifest/$keyName/stream.m3u8";
				$outputFile = "users/private/$userId/streamdeck/manifest/$keyName/output.mp4";
				$thumbnailFile = "users/private/$userId/streamdeck/manifest/$keyName/thumbnail.jpg";
				$storagePath = storage_path("app/$outputFile");
				$manifestLink = getFileTemporaryURL($m3u8File);
				$thumbnailLink = getFileTemporaryURL($thumbnailFile);
				$outputmp4File = getFileTemporaryURL($outputFile);
				$getID3 = new getID3();
				$fileInfo = $getID3->analyze($storagePath);
				$duration = isset($fileInfo['playtime_seconds']) ? $fileInfo['playtime_seconds'] : null;

				$manifestdata[] = [
					'manifest_link' => $manifestLink,
					'm3u8File' => $m3u8File,
					'outputFile' => $outputFile,
					'outputMP4File' => $outputmp4File,
					'duration' => (int)$duration,
					'thumbnail' => $thumbnailLink,
					'thumbnailFile' => $thumbnailFile,
					'filename' => $keyName
				];
			}

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Data updated successfully',
				'toast' => false,
			], [
				'manifest_data' => $manifestdata
			]);
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Error while processing',
				'toast' => true,
			]);
		}
	}


	//Tally setting api
	public function deleteProgram(Request $request)
	{
		try {
			$programId = $request->program_id;
			$channelId = $request->channel_id;

			$scheduleChannel = ScheduleChannles::where('channel_id', $channelId)->first();

			if (!$scheduleChannel) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'Channel not found',
					'toast' => true,
				]);
			}

			$epgData = json_decode($scheduleChannel->epg_data, true);

			$programToDelete = null;

			// Locate the program in the JSON data
			foreach ($epgData as $key => $program) {
				if ($program['id'] === $programId) {
					$programToDelete = $program;
					break;
				}
			}

			if (!$programToDelete) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'Program not found',
					'toast' => true,
				]);
			}

			$publicFilePath = $programToDelete['public_file_path'];
			$fullFilePath = public_path($publicFilePath);

			// Attempt to delete the file
			if (File::exists($fullFilePath)) {
				if (!File::delete($fullFilePath)) {
					return generateResponse([
						'type' => 'error',
						'code' => 200,
						'status' => false,
						'message' => 'File deletion failed',
						'toast' => true,
					]);
				}
			}

			// Remove the program from the array after the file has been deleted
			$epgData = array_filter($epgData, function ($program) use ($programId) {
				return $program['id'] !== $programId;
			});

			// Reindex the array to ensure there are no gaps in the index
			$epgData = array_values($epgData);

			// Update the epg_data in the database
			$scheduleChannel->epg_data = json_encode($epgData);
			$scheduleChannel->save();

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Program deleted successfully',
				'toast' => true,
			]);
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => $e->getMessage(),
				'toast' => true,
			]);
		}
	}


	public function tallySettings(Request $request)
	{
		try {
			$action = $request->action;
			$channelId = $request->channel_id;
			$scheduleChannel = ScheduleChannles::where('channel_id', $channelId)->first();

			if ($action === 'fetch') {
				if ($scheduleChannel) {
					$epgData = json_decode($scheduleChannel->epg_data, true);
					$today = date('Y-m-d');

					$epgData = array_filter($epgData, function ($item) use ($today) {
						return $item['date'] === $today;
					});

					usort($epgData, function ($a, $b) {
						return strtotime($a['since']) - strtotime($b['since']);
					});

					$filteredData = array_map(function ($item) {
						return [
							'id' => $item['id'],
							'since' => $item['since'],
							'till' => $item['till'],
							'image' => $item['image'],
							'title' => $item['title'],
							'public_file_path' => $item['public_file_path']
						];
					}, $epgData);

					return generateResponse([
						'type' => 'success',
						'code' => 200,
						'status' => true,
						'message' => "Tally fetch successfully",
						'toast' => false,
					], ["tallyData" => $filteredData]);
				} else {
					return generateResponse([
						'type' => 'success',
						'code' => 200,
						'status' => true,
						'message' => 'Channel not found',
						'toast' => true,
					]);
				}
			} else if ($action === 'fetchone') {
				if ($scheduleChannel) {
					$programId = $request->program_id;
					$epgData = json_decode($scheduleChannel->epg_data, true);

					$programData = array_filter($epgData, function ($item) use ($programId, $channelId) {
						return $item['id'] === $programId && $item['channel_id'] == $channelId;
					});

					if (!empty($programData)) {
						$programData = array_values($programData)[0];

						$filteredProgramData = [
							'id' => $programData['id'],
							'title' => $programData['title'],
							'duration' => $programData['duration'],
							'file_path' => getFileTemporaryURL($programData['file_path'])
						];

						return generateResponse([
							'type' => 'success',
							'code' => 200,
							'status' => true,
							'message' => "Program fetch successfully",
							'toast' => false,
						], ["programData" => $filteredProgramData]);
					} else {
						$filteredProgramData = [
							'id' => "",
							'title' => "",
							'duration' => "",
							'file_path' => "",
						];
						return generateResponse([
							'type' => 'success',
							'code' => 200,
							'status' => true,
							'message' => "Program fetch successfully",
							'toast' => false,
						], ["programData" => $filteredProgramData, 'is_data_saved' => true]);
					}
				} else {
					return generateResponse([
						'type' => 'success',
						'code' => 200,
						'status' => true,
						'message' => 'Channel not found',
						'toast' => true,
					]);
				}
			} else if ($action === 'updateOne') {
				if ($scheduleChannel) {
					$programId = $request->program_id;
					$programType = $request->program_type;
					$program_name = $request->program_name;
					if (isset($request->mobile_number)) {
						$mobile_number = $request->mobile_number;
					}
					$epgData = json_decode($scheduleChannel->epg_data, true);
					$updated = false;
					foreach ($epgData as &$program) {
						if ($program['id'] === $programId) {
							$program['program_type'] = $programType;
							$program['title'] = $program_name;
							$updated = true;
							break;
						}
					}

					if ($updated) {
						$scheduleChannel->epg_data = json_encode($epgData);
						$scheduleChannel->save();
						// $phonenumber = $countryCode . "" . $mobile_number;
						$message = "Dear User,

We are pleased to inform you that your advertisement will be broadcasted on TV. Please tune in to watch it live between on sunday 1:00:25 and 1:01:10. We appreciate your business and hope this brings great visibility to your brand.

Thank you for choosing our services.

Best regards,
SiloCloud";
						// send_sms($phonenumber, $message);
						return generateResponse([
							'type' => 'success',
							'code' => 200,
							'status' => true,
							'message' => "Program type updated successfully",
							'toast' => true,
						]);
					} else {
						return generateResponse([
							'type' => 'error',
							'code' => 200,
							'status' => false,
							'message' => 'Program not found',
							'toast' => true,
						]);
					}
				} else {
					return generateResponse([
						'type' => 'error',
						'code' => 200,
						'status' => false,
						'message' => 'Channel not found',
						'toast' => true,
					]);
				}
			}
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => $e->getMessage(),
				'toast' => true,
			]);
		}
	}

	public function swapTally()
	{
		try {
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => $e->getMessage(),
				'toast' => true,
			]);
		}
	}

	public function fetchProgramDetailsByDate(Request $request)
	{
		try {
			$date = $request->date;
			$channelId = $request->channel_id;
			$domainId = $request->domain_id;
			$time_zone = $request->time_zone ? $request->time_zone : "Asia/Calcutta";
			$today = $date;
			// $todayTime = gmdate('H:i:s', strtotime('+5 hours 30 minutes'));
			$todayTime = (new DateTime('now', new DateTimeZone($time_zone)))->format('H:i:s');
			// Start the query with necessary joins
			$programDetailsQuery = TvLivestream::join('schedule_channles', 'tv_livestreams.channel_id', '=', 'schedule_channles.channel_id')
				->join('channels', 'tv_livestreams.channel_id', '=', 'channels.id')
				->select(
					'tv_livestreams.output_blob',
					'tv_livestreams.playlistpathLink',
					'schedule_channles.epg_data',
					'channels.channel_name',
					'channels.logo',
					'channels.channelUuid',
					'channels.schedule_duration',
					'tv_livestreams.channel_id',
					'tv_livestreams.id'
				);

			if ($domainId) {
				$channelIds = DB::table('website')
					->where('domain_id', $domainId)
					->value('channels');

				if ($channelIds) {
					$channelIdsArray = explode(',', $channelIds);
					Log::info('Filtered Channel IDs: ', $channelIdsArray);
					$programDetailsQuery->whereIn('tv_livestreams.channel_id', $channelIdsArray);
				} else {
					return generateResponse([
						'type' => 'error',
						'code' => 404,
						'status' => false,
						'message' => 'No channels found for the provided domain_id.',
						'toast' => true,
					]);
				}
			} elseif ($channelId) {
				$programDetailsQuery->where('tv_livestreams.channel_id', $channelId);
			}

			$programDetails = $programDetailsQuery->get();

			$liveStreams = DB::table('live_stream')
				->where('stream_status', '1')
				->get();

			$liveStreamMap = $liveStreams->mapWithKeys(function ($item) {
				if (!empty($item->destination_id)) {
					$destinations = explode(',', $item->destination_id);
					return array_fill_keys($destinations, $item->stream_url_live);
				}
				return [];
			});

			// Get additional channels that are in live stream map
			$additionalChannels = DB::table('channels')
				->whereIn('id', array_keys($liveStreamMap->toArray()))
				->get()
				->keyBy('id');

			if ($channelId) {
				$programDetail = $programDetails->first();
				if (!$programDetail) {
					if (isset($liveStreamMap[$channelId])) {
						return generateResponse([
							'type' => 'success',
							'code' => 200,
							'status' => true,
							'message' => 'Program details fetched successfully.',
							'toast' => false,
						], [
							'output_blob' => '',
							'playlistpathLink' => $liveStreamMap[$channelId],
							'epg_data' => [
								[
									"id" => "00a1cbd8-7ac8-4747-95ff-ef2b5c0f82c7",
									"title" => "Live",
									"image" => config('app.url') . "assets/default/images/apps/logos/Streaming.png",
									"since" => $today . "T" . $todayTime,
									"till" => $today . "T23:59:59",
									"date" => $today,
									"channelUuid" => $additionalChannels[$channelId]->channelUuid,
									"channel_id" => $channelId,
									"isLive" => true,
									"channelIndex" => 0,
									"channelPosition" => [
										"top" => 0,
										"height" => 80
									],
									"index" => 0,
								]
							],
							'channelData' => [
								'channel_name' => $additionalChannels[$channelId]->channel_name,
								'logo' => config('app.url') . "assets/default/images/apps/logos/Streaming.png",
								'uuid' => $additionalChannels[$channelId]->channelUuid,
								'is_live' => true,
							],
						]);
					}

					return generateResponse([
						'type' => 'error',
						'code' => 404,
						'status' => false,
						'message' => 'No program details found for the given date and channel.',
						'toast' => true,
					]);
				}

				if (isset($liveStreamMap[$channelId])) {
					return generateResponse([
						'type' => 'success',
						'code' => 200,
						'status' => true,
						'message' => 'Program details fetched successfully.',
						'toast' => false,
					], [
						'output_blob' => '',
						'playlistpathLink' => $liveStreamMap[$channelId],
						'epg_data' => [
							[
								"id" => "00a1cbd8-7ac8-4747-95ff-ef2b5c0f82c7",
								"title" => "Live",
								"image" => config('app.url') . "assets/default/images/apps/logos/Streaming.png",
								"since" => $today . "T" . $todayTime,
								"till" => $today . "T23:59:59",
								"date" => $today,
								"channelUuid" => $programDetail->channelUuid,
								"channel_id" => $channelId,
								"isLive" => true,
								"channelIndex" => 0,
								"channelPosition" => [
									"top" => 0,
									"height" => 80
								],
								"index" => 0,
							]
						],
						'channelData' => [
							'channel_name' => $programDetail->channel_name,
							'logo' => config('app.url') . "assets/default/images/apps/logos/streamdeck_logo.png",
							'uuid' => $programDetail->channelUuid,
							'is_live' => true,
						],
					]);
				}

				return generateResponse([
					'type' => 'success',
					'code' => 200,
					'status' => true,
					'message' => 'Program details fetched successfully.',
					'toast' => false,
				], [
					'output_blob' => getFileTemporaryURL($programDetail->output_blob),
					'playlistpathLink' => getFileTemporaryURL($programDetail->playlistpathLink),
					// 'epg_data' => array_values(array_filter(json_decode($programDetail->epg_data, true), function ($item) use ($today) {
					//   return isset($item['date']) && $item['date'] === $today;
					// })),
					'epg_data' => array_values(json_decode($programDetail->epg_data, true)),
					'channelData' => [
						'channel_name' => $programDetail->channel_name,
						'logo' => $programDetail->logo ? getFileTemporaryURL($programDetail->logo) : config('app.url') . "assets/default/images/apps/logos/streamdeck_logo.png",
						'uuid' => $programDetail->channelUuid,
						'is_live' => false,
						'schedule_duration' => $programDetail->schedule_duration,
						'channel_id' => $programDetail->channel_id,
						'livestream_id' => $programDetail->id,
					],
				]);
			} else {
				if ($programDetails->isEmpty() && $liveStreamMap->isEmpty()) {
					// Fetch channels based on channelIdsArray
					$channels = DB::table('channels')
						->whereIn('id', $channelIdsArray)
						->get();

					$allPlaylistpathLinks = [];
					$allEpgData = [];
					$allChannelData = [];

					foreach ($channels as $channel) {
						$allChannelData[] = [
							'channel_name' => $channel->channel_name,
							'logo' => $channel->logo ? getFileTemporaryURL($channel->logo) : config('app.url') . "assets/default/images/apps/logos/streamdeck_logo.png",
							'channel_id' => $channel->id,
							'is_live' => false,
							'schedule_duration' => $channel->schedule_duration
						];

						$allPlaylistpathLinks[] = [
							'channel_id' => $channel->id,
							'playlistpathLink' => ''
						];
					}

					return generateResponse([
						'type' => 'success',
						'code' => 200,
						'status' => true,
						'message' => 'Program details fetched successfully.',
						'toast' => false,
					], [
						'output_blob' => '',
						'playlistpathLinks' => $allPlaylistpathLinks,
						'epg_data' => $allEpgData,
						'channelData' => $allChannelData,
					]);
				}

				$allEpgData = [];
				$allChannelData = [];
				$allPlaylistpathLinks = [];

				foreach ($programDetails as $programDetail) {
					$epgData = json_decode($programDetail->epg_data, true);

					// Filter epg_data to only include today's date
					// $epgData = array_filter($epgData, function ($item) use ($today) {
					//   return isset($item['date']) && $item['date'] === $today;
					// });

					// Determine if the channel is live
					$isLive = isset($liveStreamMap[$programDetail->channel_id]);

					if ($isLive) {
						$epgData = [
							[
								"id" => "00a1cbd8-7ac8-4747-95ff-ef2b5c0f82c7",
								"title" => "Live",
								"image" => config('app.url') . "assets/default/images/apps/logos/Streaming.png",
								"since" => $today . "T" . $todayTime,
								"till" => $today . "T23:59:59",
								"date" => $today,
								"channelUuid" => $programDetail->channelUuid,
								"channel_id" => $programDetail->channel_id,
								"isLive" => true,
								"channelIndex" => 0,
								"channelPosition" => [
									"top" => 0,
									"height" => 80
								],
								"index" => 0,
							]
						];
					}

					foreach ($epgData as $epg) {
						$allEpgData[] = $epg;
					}

					$channelData = [
						'channel_name' => $programDetail->channel_name,
						'logo' => getFileTemporaryURL($programDetail->logo),
						'uuid' => $programDetail->channelUuid,
						'channel_id' => $programDetail->channel_id,
						'is_live' => $isLive,
						'livestream_id' => $programDetail->id,
						'schedule_duration' => $programDetail->schedule_duration
					];

					$allChannelData[] = $channelData;

					$playlistpathLink = $isLive ? $liveStreamMap[$programDetail->channel_id] : getFileTemporaryURL($programDetail->playlistpathLink);

					$allPlaylistpathLinks[] = [
						'channel_id' => $programDetail->channel_id,
						'playlistpathLink' => $playlistpathLink,
					];
				}

				foreach ($liveStreamMap as $channelId => $streamUrl) {
					if (!$programDetails->contains('channel_id', $channelId)) {
						$additionalChannel = $additionalChannels[$channelId];

						$allEpgData[] = [
							"id" => "00a1cbd8-7ac8-4747-95ff-ef2b5c0f82c7",
							"title" => "Live",
							"image" => config('app.url') . "assets/default/images/apps/logos/Streaming.png",
							"since" => $today . "T" . $todayTime,
							"till" => $today . "T23:59:59",
							"date" => $today,
							"channelUuid" => $additionalChannel->channelUuid,
							"channel_id" => $channelId,
							"isLive" => true,
							"channelIndex" => 0,
							"channelPosition" => [
								"top" => 0,
								"height" => 80
							],
							"index" => 0,
						];

						$allChannelData[] = [
							'channel_name' => $additionalChannel->channel_name,
							'logo' => $additionalChannel->logo ? getFileTemporaryURL($additionalChannel->logo) : config('app.url') . "assets/default/images/apps/logos/streamdeck_logo.png",
							'uuid' => $additionalChannel->channelUuid,
							'channel_id' => $channelId,
							'is_live' => true,
							'livestream_id' => null,
							'schedule_duration' => $additionalChannel->schedule_duration
						];

						$allPlaylistpathLinks[] = [
							'channel_id' => $channelId,
							'playlistpathLink' => $streamUrl,
						];
					}
				}

				return generateResponse([
					'type' => 'success',
					'code' => 200,
					'status' => true,
					'message' => 'Program details fetched successfully.',
					'toast' => false,
				], [
					'output_blob' => '',
					'playlistpathLinks' => $allPlaylistpathLinks,
					'epg_data' => $allEpgData,
					'channelData' => $allChannelData,
				]);
			}
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => 'An error occurred while fetching program details.',
				'toast' => true,
			]);
		}
	}


	public function isChannelSchedule(Request $request)
	{
		try {
			$channelId = $request->channel_id;
			$todayDate = $request->date;

			$scheduleChannel = TvLivestream::where('channel_id', $channelId)
				// ->whereDate('date', $todayDate)
				->first();

			// dd($todayDate);
			if ($scheduleChannel) {
				$isScheduled = $scheduleChannel->status === '0' ? true : false;
				return generateResponse([
					'type' => 'success',
					'code' => 200,
					'status' => true,
					'message' => 'Channel is scheduled for today.',
					'toast' => false,
				], [
					'isScheduled' => $isScheduled,
					'output_blob' => getFileTemporaryURL($scheduleChannel->output_blob),
					// 'playlistpathLink' => getFileTemporaryURL($scheduleChannel->playlistpathLink),
					'playlistpathLink' => url('get-file-content/' . $scheduleChannel->playlistpathLink),
				]);
			} else {
				return generateResponse([
					'type' => 'success',
					'code' => 200,
					'status' => false,
					'message' => 'Channel is not scheduled for today.',
					'toast' => false,
					'false' => true,
				], ['isScheduled' => true]);
			}
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => $e->getMessage(),
				'toast' => true,
			]);
		}
	}
	public function getChannelByUser(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$user_id = $user->id;
			$channels = Channel::where('user_id', $user_id)->get(['id', 'logo', 'channel_name']);
			$channels->transform(function ($channel) {
				$channel->logo = getFileTemporaryURL($channel->logo);
				return $channel;
			});

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Channel Fetched Successfully',
				'toast' => false,

			], [
				'channel' => $channels
			]);
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => $e->getMessage(),
				'toast' => true,
			]);
		}
	}
	// Code to handle tally setting
	public function updateProgramSchedule(Request $request)
	{
		$data = $request->json()->all();
		if (empty($data) || count($data) < 2) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Insufficient data provided',
				'toast' => true,
			]);
		}

		try {
			$channelIdToUpdate = $data[0]['channel_id'];
			$existingSchedules = ScheduleChannles::where('channel_id', $channelIdToUpdate)->get()->keyBy('id');

			$index = 0;
			while ($index < count($data) - 1) {
				if ($data[$index]['index'] > $data[$index + 1]['index']) {
					$tempSince = $data[$index]['since'];
					$data[$index]['since'] = $data[$index + 1]['since'];
					$data[$index + 1]['since'] = $tempSince;

					$tempTill = $data[$index]['till'];
					$data[$index]['till'] = $data[$index + 1]['till'];
					$data[$index + 1]['till'] = $tempTill;

					$tempIndex = $data[$index]['index'];
					$data[$index]['index'] = $data[$index + 1]['index'];
					$data[$index + 1]['index'] = $tempIndex;

					$startTime1 = strtotime($data[$index]['since']);
					$duration1 = intval($data[$index]['duration']);
					$endTime1 = $startTime1 + $duration1;
					$data[$index]['till'] = date('Y-m-d H:i:s', $endTime1);

					$startTime2 = strtotime($data[$index + 1]['since']);
					$duration2 = intval($data[$index + 1]['duration']);
					$endTime2 = $startTime2 + $duration2;
					$data[$index + 1]['till'] = date('Y-m-d H:i:s', $endTime2);

					$index++;
				} else {
					$index++;
				}
			}

			for ($i = 0; $i < count($data) - 1; $i++) {
				$currentTill = strtotime($data[$i]['till']);
				$nextSince = strtotime($data[$i + 1]['since']);

				if ($currentTill > $nextSince) {
					$data[$i + 1]['since'] = date('Y-m-d H:i:s', $currentTill);
					$nextDuration = intval($data[$i + 1]['duration']);
					$nextEndTime = $currentTill + $nextDuration;
					$data[$i + 1]['till'] = date('Y-m-d H:i:s', $nextEndTime);
				}
			}

			foreach ($data as &$item) {
				$item['public_file_path'] = '';

				foreach ($existingSchedules as $existingSchedule) {
					$epgData = json_decode($existingSchedule->epg_data, true);

					foreach ($epgData as $existingItem) {
						if ($existingItem['id'] === $item['id']) {
							$item['public_file_path'] = $existingItem['public_file_path'];
							break 2;
						}
					}
				}
			}

			$scheduleChannels = ScheduleChannles::where('channel_id', $channelIdToUpdate)->get();
			foreach ($scheduleChannels as $scheduleChannel) {
				$scheduleChannel->epg_data = json_encode($data);
				// $scheduleChannel->save();
			}

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Data updated successfully',
				'toast' => false,
			], [
				'data' => $data
			]);
		} catch (\Exception $e) {
			Log::error('Failed to update program schedule: ' . $e->getMessage());
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => $e->getMessage(),
				'toast' => true,
			]);
		}
	}
	public function updateLiveStreamUrl(Request $request)
	{
		try {
			$responseUrl = $request->live_url;
			$stream_title = $request->stream_name;
			$stream_status = $request->stream_status;
			$todaysTime = date('H:i:s');
			$livestream = LiveStream::where('stream_title', $stream_title)->first();

			if ($livestream) {
				$livestream->stream_url_live = $responseUrl;
				$livestream->stream_status = '1';
				$livestream->live_start_time = $todaysTime;
				$livestream->save();
			} else {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'Stream title not found',
					'toast' => true,
				]);
			}

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Stream URL updated successfully',
				'toast' => false,
			]);
			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Stream URL added successfully',
				'toast' => false,
			]);
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Failed to update live stream URL',
				'toast' => true,
			]);
		}
	}

	public function addStreamToMediaLibrary(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$user_id = $user->id;
			if ($request->duration < 20) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => 'You can\'t add videos less than 20 seconds',
					'toast' => true,
				]);
			}
			$video = new VideoStream();
			$video->user_id = $user_id;
			$video->title = $request->title;
			$video->file_path = $request->filepath;
			$video->thumbnail = $request->thumbnail;
			$video->duration = $request->duration;
			$video->channel_uuid = Str::uuid();
			$video->save();
			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Data updated successfully',
				'toast' => false,
			], [
				'video_data' => $video
			]);
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => $e->getMessage(),
				'toast' => true,
			]);
		}
	}
	// get gallery code
	public function getVideosGallery(Request $request)
	{
		try {
			$user = $request->attributes->get('user');

			// Check if user is authenticated
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			$userId = $user->id;
			$outputDir1 = 'users/private';
			$userDirectory = "$outputDir1/$userId/streamdeck";

			// Check if user directory exists
			if (!Storage::exists($userDirectory) || !Storage::directories($userDirectory)) {
				return response()->json(['error' => 'User directory not found'], 404);
			}

			$folders = Storage::directories($userDirectory);
			$baseURL = url("storage/$userDirectory");

			// Filter folders that start with "session_"
			$videoLinks = array_filter($folders, function ($folder) {
				return strpos(basename($folder), 'session_') === 0;
			});

			// Map folders to video links and thumbnails
			$videoLinks = array_map(function ($folder) use ($userId, $baseURL, $outputDir1) {
				$folderName = basename($folder);
				$relativePath = "$outputDir1/$userId/streamdeck/$folderName/playlist.m3u8";
				$link = "$baseURL/$folderName/playlist.m3u8";
				$thumbnailPath = "$outputDir1/$userId/streamdeck/$folderName/thumbnail.jpg";

				// Generate temporary URLs for both video link and thumbnail
				$linkURL = getFileTemporaryURL($relativePath);
				$thumbnailURL = getFileTemporaryURL($thumbnailPath);

				return [
					'folder' => $folderName,
					'link' => $link,
					'userId' => $userId,
					'relativePath' => $linkURL,
					'thumbnail' => $thumbnailURL
				];
			}, $videoLinks);

			return response()->json(array_values($videoLinks)); // Convert associative array to indexed array
		} catch (\Exception $e) {
			return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
		}
	}
	public function deleteVideoGallery(Request $request, $folder)
	{
		try {
			$user = $request->attributes->get('user');

			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			$userId = $user->id;
			$outputDir1 = 'users/private';
			$userDirectory = "$outputDir1/$userId/streamdeck/$folder";

			if (!Storage::exists($userDirectory)) {
				return response()->json(['error' => 'Folder not found'], 404);
			}

			Storage::deleteDirectory($userDirectory);

			return response()->json(['success' => 'Folder deleted successfully']);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
		}
	}

	//chart API
	public function downloadChartPdf(Request $request)
	{
		try {
			$viewData = [];
			$type = $request->type;
			$period = $request->input('period');

			// Fetch user from request attributes
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			// Fetch the required data based on the type
			if ($type == 'pie') {
				// Fetch data for Pie Chart
				$data = $this->fetchPieChartData($user, $period);
				$viewData['data'] = $data ?: []; // Ensure data is an array
				$viewData['title'] = 'Views per Channel';
			} else if ($type == 'doughnut') {
				// Fetch data for Doughnut Chart
				$data = $this->fetchDoughnutChartData($user, $period);
				$viewData['data'] = $data ?: []; // Ensure data is an array
				$viewData['title'] = 'Streams per Channel';
			} else if ($type == 'line') {
				// Fetch data for Line Chart
				$data = $this->fetchLineChartData($user, $period);
				$viewData['data'] = $data ?: []; // Ensure data is an array
				$viewData['title'] = 'Views vs Time';
			}

			// Check if the data is empty and return an appropriate response
			if (!isset($viewData['data']) || !is_array($viewData['data']) || empty($viewData['data'])) {
				return response()->json(['error' => 'No data available for the selected chart type.'], 404);
			}

			// Configure Dompdf according to your requirements
			$options = new Options();
			$options->set('isHtml5ParserEnabled', true); // Enable HTML5 parser
			$options->set('isPhpEnabled', true); // Enable embedded PHP
			$options->set('defaultFont', 'Arial'); // Set default font

			// Instantiate Dompdf with the configured options
			$dompdf = new Dompdf($options);

			// Load HTML content from the Blade view
			$html = view('chart_pdf', $viewData)->render();
			$dompdf->loadHtml($html);

			// Set paper size and orientation
			$dompdf->setPaper('A4', 'portrait');

			// Render PDF
			$dompdf->render();

			// Get the generated PDF content
			$output = $dompdf->output();

			// Define the folder path within the public directory
			$publicPath = 'storage/pdfs';

			// Check if the directory exists in the public path
			if (!File::isDirectory(public_path($publicPath))) {
				File::makeDirectory(public_path($publicPath), 0755, true, true);
			}

			// Save PDF to the public directory
			$pdfFilePath = public_path("$publicPath/{$type}_chart.pdf");
			file_put_contents($pdfFilePath, $output);

			// Return the public URL to access the saved PDF file
			$publicUrl = asset("$publicPath/{$type}_chart.pdf");

			return response()->json(['url' => $publicUrl]);
		} catch (\Exception $e) {
			Log::error('Failed to generate PDF: ' . $e->getMessage());
			return response()->json(['error' => 'Failed to generate PDF.', 'message' => $e->getMessage()], 500);
		}
	}



	// Fetch Pie Chart data
	public function fetchPieChartData($user, $period)
	{
		try {
			$userId = $user->id;
			$channels = Channel::where('user_id', $userId)->get();
			$uniqueViews = 0;
			$signedOutViews = 0;
			$periodCondition = $this->getPeriodCondition($period);

			foreach ($channels as $channel) {
				$views = json_decode($channel->views, true);
				if ($periodCondition) {
					$views = array_filter($views, function ($view) use ($periodCondition) {
						return Carbon::parse($view['timestamp'])->gte($periodCondition);
					});
				}
				$uniqueViews += collect($views)->unique('user_id')->count();
				$signedOutViews += collect($views)->whereNull('user_id')->count();
			}

			$totalViews = $uniqueViews + $signedOutViews;
			return [
				['Metric' => 'Unique Views', 'Value' => $uniqueViews],
				['Metric' => 'Total Views', 'Value' => $totalViews],
				['Metric' => 'Signed Out Views', 'Value' => $signedOutViews]
			];
		} catch (\Exception $e) {
			Log::error('Error fetching Pie Chart data: ' . $e->getMessage());
			return [];
		}
	}


	public function fetchDoughnutChartData($user, $period)
	{
		try {
			$liveStreams = TvLivestream::where('user_id', $user->id)->get();
			$periodCondition = $this->getPeriodCondition($period);
			if ($periodCondition) {
				$liveStreams = $liveStreams->filter(function ($stream) use ($periodCondition) {
					return Carbon::parse($stream->created_at)->gte($periodCondition);
				});
			}

			$channelCounts = $liveStreams->groupBy('channel_id')->map(function ($item) {
				return $item->count();
			});

			$channelIds = $channelCounts->keys();
			$channels = Channel::whereIn('id', $channelIds)->pluck('channel_name', 'id');

			$data = [];
			foreach ($channelIds as $id) {
				$data[] = [
					'Channel' => $channels[$id] ?? "Unknown Channel",
					'Count' => $channelCounts[$id]
				];
			}

			return $data;
		} catch (\Exception $e) {
			Log::error('Error fetching Doughnut Chart data: ' . $e->getMessage());
			return [];
		}
	}

	// Fetch Line Chart data
	public function fetchLineChartData($user, $period)
	{
		try {
			// Fetch channels for the user
			$channels = Channel::where('user_id', $user->id)->get();
			$allViews = collect();
			$periodCondition = $this->getPeriodCondition($period);

			foreach ($channels as $channel) {
				$views = json_decode($channel->views, true);
				if (is_array($views)) {
					// Filter views based on the period condition
					if ($periodCondition) {
						$views = array_filter($views, function ($view) use ($periodCondition) {
							return isset($view['timestamp']) && Carbon::parse($view['timestamp'])->gte($periodCondition);
						});
					}

					// Process the filtered views
					foreach ($views as $view) {
						$view['channel_name'] = $channel->channel_name;
						$allViews->push($view);
					}
				}
			}

			// Group views by hour and channel name
			$viewsByHour = $allViews->groupBy(function ($view) {
				return Carbon::parse($view['timestamp'])->format('Y-m-d H:i:s');
			})->map(function ($group) {
				return [
					'hour' => Carbon::parse($group->first()['timestamp'])->format('Y-m-d H:i:s'),
					'views' => $group->count(),
					'channel_name' => $group->first()['channel_name']
				];
			})->values();

			return $viewsByHour->toArray();
		} catch (\Exception $e) {
			Log::error('Error fetching Line Chart data: ' . $e->getMessage());
			return [];
		}
	}
	private function getPeriodCondition($period)
	{
		switch ($period) {
			case '7D':
				return Carbon::now()->subDays(7);
			case '1M':
				return Carbon::now()->subMonth();
			case 'ALL':
			default:
				return null;
		}
	}

	// Track View
	public function trackView(Request $request)
	{
		$user = $request->attributes->get('user');
		$channelId = $request->channel_id;
		$livestreamId = $request->livestream_id;
		$deviceType = $request->device_type;
		$browserInfo = $request->browser_info;
		$location = $request->location;
		$userId = $user ? $user->id : null;

		// Find the channel
		$channel = Channel::findOrFail($channelId);

		// Get current views and decode the JSON data
		$views = json_decode($channel->views, true);

		// If views is null, initialize it as an array
		if (!$views) {
			$views = [];
		}

		// Add the new view with additional information
		$views[] = [
			'livestream_id' => $livestreamId,
			'user_id' => $userId,
			'timestamp' => now()->toDateTimeString(),
			'device_type' => $deviceType,
			'browser_info' => $browserInfo,
			'location' => $location,
		];

		// Update the channel with the new views
		$channel->update([
			'views' => json_encode($views),
		]);

		return response()->json(['message' => 'View tracked successfully', 'views' => $views], 201);
	}

	public function getChannelViews($channelId)
	{

		try {
			if (empty($channelId)) {
				return response()->json(['error' => 'Channel ID is required'], 400);
			}

			// Find the channel
			$channel = Channel::findOrFail($channelId);

			// Ensure views field exists and is not empty
			if (!$channel->views) {
				return response()->json(['error' => 'No views data found for the channel'], 404);
			}

			// Decode the views JSON string into an associative array
			$views = json_decode($channel->views, true);

			// Group views by exact timestamp
			$viewsByHour = collect($views)->groupBy(function ($view) {
				return Carbon::parse($view['timestamp'])->format('Y-m-d H:i:s');
			})->map(function ($group) use ($channel) {
				return [
					'hour' => Carbon::parse($group->first()['timestamp'])->format('Y-m-d H:i:s'),
					'views' => $group->count(),
					'channel_name' => $channel->channel_name
				];
			})->values()->all(); // Convert to indexed array

			return response()->json($viewsByHour);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
		}
	}


	public function getAnalyticsOverviewLive(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			$userId = $user->id;

			// Get all channels for the user
			$channels = Channel::where('user_id', $userId)->get();

			// Initialize counters
			$uniqueViews = 0;
			$signedOutViews = 0;

			foreach ($channels as $channel) {
				$views = json_decode($channel->views, true);

				$uniqueViews += collect($views)->unique('user_id')->count();
				$signedOutViews += collect($views)->whereNull('user_id')->count();
			}

			// Calculate total views
			$totalViews = $uniqueViews + $signedOutViews;

			return response()->json([
				'unique_views' => $uniqueViews,
				'total_views' => $totalViews,
				'signed_out_views' => $signedOutViews,
			], 200);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
		}
	}


	public function getAllChannelsViews(Request $request)
	{
		try {
			// Get the authenticated user
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			// Fetch channels related to the user, assuming a 'user_id' field in the channels table
			$channels = Channel::where('user_id', $user->id)->get();

			// Initialize a collection to aggregate views
			$allViews = collect();

			// Iterate over each channel and collect views
			foreach ($channels as $channel) {
				$views = json_decode($channel->views, true);

				// If views is not null, add to the allViews collection
				if ($views) {
					foreach ($views as $view) {
						$view['channel_name'] = $channel->channel_name;
						$allViews->push($view);
					}
				}
			}

			// Group views by hour and channel name
			$viewsByHour = $allViews->groupBy(function ($view) {
				return Carbon::parse($view['timestamp'])->format('Y-m-d H:i:s');
			})->map(function ($group) {
				return [
					'hour' => Carbon::parse($group->first()['timestamp'])->format('Y-m-d H:i:s'),
					'views' => $group->count(),
					'channel_name' => $group->first()['channel_name']
				];
			})->values(); // Convert to array and reset keys

			return response()->json($viewsByHour);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
		}
	}

	public function getLiveStreamChartData(Request $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			// Fetch live streams for the user
			$liveStreams = TvLivestream::where('user_id', $user->id)->get();

			// Process data to extract channel counts
			$channelCounts = $liveStreams->groupBy('channel_id')->map(function ($item) {
				return $item->count();
			});

			// Fetch channel names
			$channelIds = $channelCounts->keys();
			$channels = Channel::whereIn('id', $channelIds)->pluck('channel_name', 'id');

			// Prepare labels and datasets
			$labels = $channelIds->map(function ($id) use ($channels) {
				return $channels[$id] ?? "Unknown Channel";
			});

			$datasets = [
				[
					'data' => $channelCounts->values()
				]
			];

			DB::commit();
			return response()->json([
				'labels' => $labels,
				'datasets' => $datasets
			]);
		} catch (\Exception $e) {
			Log::info('Error while fetching live stream chart data: ' . $e->getMessage());
			DB::rollBack();
			return response()->json(['error' => 'Error fetching chart data'], 500);
		}
	}


	public function getAnalyticsOverview(Request $request)
	{
		DB::beginTransaction();
		try {
			// Retrieve authenticated user
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			$userId = $user->id;

			// Fetch total channels
			$totalChannels = TvLivestream::where('user_id', $userId)
				->distinct('channel_id')
				->count('channel_id');

			// Fetch total streams
			$totalStreams = TvLivestream::where('user_id', $userId)->count();

			// Calculate total stream time in seconds
			$totalStreamTimeInSeconds = TvLivestream::where('user_id', $userId)
				->sum(DB::raw("TIME_TO_SEC(TIMEDIFF(latest_till, earliest_since))"));

			// Calculate average stream time in seconds
			$avgStreamTimeInSeconds = TvLivestream::where('user_id', $userId)
				->avg(DB::raw("TIME_TO_SEC(TIMEDIFF(latest_till, earliest_since))"));

			// Convert total stream time to hours, minutes, and seconds
			$totalStreamTime = $this->convertSecondsToHMS($totalStreamTimeInSeconds);

			// Convert average stream time to hours, minutes, and seconds
			$avgStreamTime = $this->convertSecondsToHMS($avgStreamTimeInSeconds);

			DB::commit();

			return response()->json([
				'totalChannels' => $totalChannels,
				'totalStreams' => $totalStreams,
				'totalStreamTime' => $totalStreamTime,
				'avgStreamTime' => $avgStreamTime,
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error fetching analytics overview: ' . $e->getMessage());
			return response()->json(['error' => 'Error fetching analytics overview', 'message' => $e->getMessage()], 500);
		}
	}

	private function convertSecondsToHMS($seconds)
	{
		$hours = floor($seconds / 3600);
		$minutes = floor(($seconds % 3600) / 60);
		$seconds = $seconds % 60;
		return sprintf('%02dH:%02dM:%02dS', $hours, $minutes, $seconds);
	}

	public function getChannelforView(Request $request)
	{
		try {
			// Get the authenticated user
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			// Fetch channels related to the user, selecting only necessary fields
			$channels = Channel::select('id', 'channel_name')
				->where('user_id', $user->id)
				->get();

			// Format channels for select dropdown options
			$channelOptions = $channels->map(function ($channel) {
				return [
					'value' => $channel->id,
					'label' => $channel->channel_name,
				];
			});

			return response()->json($channelOptions);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
		}
	}

	public function getLocations(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			// Get all channels for the user
			$channels = Channel::where('user_id', $user->id)->get();

			// Initialize an array to store locations
			$locations = [];

			foreach ($channels as $channel) {
				// Get current views and decode the JSON data
				$views = json_decode($channel->views, true);

				if ($views) {
					// Extract and merge location data
					$channelLocations = array_map(function ($view) {
						return json_decode($view['location'], true);
					}, $views);

					$locations = array_merge($locations, ...$channelLocations);
				}
			}

			// Check if there are any locations to return
			if (empty($locations)) {
				return response()->json(['message' => 'No locations found'], 404);
			}

			return response()->json($locations);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
		}
	}


	public function getCountryPercentages(Request $request)
	{
		try {
			// Retrieve the authenticated user
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			// Get all channels for the user
			$channels = Channel::where('user_id', $user->id)->get();

			// Initialize arrays to store country counts and browser counts
			$signedInCountries = [];
			$signedOutCountryCount = [];
			$browserCount = [];

			foreach ($channels as $channel) {
				// Decode the JSON data of views
				$views = json_decode($channel->views, true);

				if ($views) {
					// Extract and count country and browser data from views
					foreach ($views as $view) {
						$viewLocations = json_decode($view['location'], true);
						if ($viewLocations) {
							foreach ($viewLocations as $location) {
								if (isset($location['country'])) {
									$country = $location['country'];
									// Determine if the user is signed in or signed out
									$isSignedIn = $view['user_id'] == $user->id;

									// Track unique countries for signed-in users
									if ($isSignedIn) {
										$signedInCountries[$country] = 1;
									} else {
										// Count occurrences of each country for signed-out users
										if (!isset($signedOutCountryCount[$country])) {
											$signedOutCountryCount[$country] = 0;
										}
										$signedOutCountryCount[$country]++;
									}
								}
							}
						}

						// Count occurrences of each browser
						if (isset($view['browser_info'])) {
							$browser = $view['browser_info'];
							if (!isset($browserCount[$browser])) {
								$browserCount[$browser] = 0;
							}
							$browserCount[$browser]++;
						}
					}
				}
			}

			// Count unique signed-in records (1 per country)
			$uniqueSignedInCount = count($signedInCountries);

			// Merge signed-in and signed-out country counts
			$mergedCountryCount = $signedInCountries;
			foreach ($signedOutCountryCount as $country => $count) {
				if (!isset($mergedCountryCount[$country])) {
					$mergedCountryCount[$country] = 0;
				}
				$mergedCountryCount[$country] += $count;
			}

			// Total records calculation
			$totalRecords = $uniqueSignedInCount + array_sum($signedOutCountryCount);

			if ($totalRecords <= 0) {
				return response()->json(['message' => 'No records found'], 404);
			}

			// Calculate the percentage for each country
			$countryPercentages = [];
			foreach ($mergedCountryCount as $country => $count) {
				$countryPercentages[$country] = round(($count / $totalRecords) * 100, 2);
			}

			// Sort the percentages in descending order
			arsort($countryPercentages);

			// Calculate the total number of browser records
			$totalBrowserRecords = array_sum($browserCount);

			if ($totalBrowserRecords <= 0) {
				return response()->json(['message' => 'No browser records found'], 404);
			}

			// Calculate the percentage for each browser
			$browserPercentages = [];
			foreach ($browserCount as $browser => $count) {
				$browserPercentages[$browser] = round(($count / $totalBrowserRecords) * 100, 2);
			}

			// Sort the percentages in descending order
			arsort($browserPercentages);

			// Return both country and browser percentages
			return response()->json([
				'countryPercentages' => $countryPercentages,
				'browserPercentages' => $browserPercentages
			]);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
		}
	}


	public function downloadChartExcel(Request $request)
	{
		try {
			$type = $request->type;
			$period = $request->input('period');

			// Fetch user from request attributes
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			// Fetch the required data based on the type and period
			if ($type == 'pie') {
				$data = $this->fetchPieChartData($user, $period);
			} else if ($type == 'doughnut') {
				$data = $this->fetchDoughnutChartData($user, $period);
			} else if ($type == 'line') {
				$data = $this->fetchLineChartData($user, $period);
			} else {
				return response()->json(['error' => 'Invalid chart type.'], 400);
			}

			if (empty($data)) {
				return response()->json(['error' => 'No data available for the selected chart type.'], 404);
			}

			// Define the filename and export the data
			$filename = "{$type}_chart_" . date('YmdHis') . rand() . '.xlsx';
			$export = new ChartExport($data);

			// Define the public path
			$publicPath = 'excel';

			// Check if the directory exists in public path, create if not
			if (!File::isDirectory(storage_path("app/public/{$publicPath}"))) {
				File::makeDirectory(storage_path("app/public/{$publicPath}"), 0755, true, true);
			}

			// Store the Excel file temporarily
			$path = "{$publicPath}/{$filename}";
			Excel::store($export, $path, 'public');

			// Fetch the file and return it as a response
			$filePath = storage_path("app/public/{$path}");
			if (File::exists($filePath)) {
				return response()->download($filePath, $filename, [
					'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
					'Content-Disposition' => "attachment; filename=\"{$filename}\"",
				])->deleteFileAfterSend(true);
			} else {
				return response()->json(['error' => 'File not found.'], 404);
			}
		} catch (\Exception $e) {
			Log::error('Failed to generate Excel: ' . $e->getMessage());
			return response()->json(['error' => 'Failed to generate Excel.', 'message' => $e->getMessage()], 500);
		}
	}


	public function getAnalyticsStreamOverview(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			if (!$user) {
				return response()->json(['error' => 'User not authenticated'], 401);
			}

			$userId = $user->id;

			// Get all channels for the user
			$channels = Channel::where('user_id', $userId)->get();

			// Initialize counters and storage
			$totalViews = 0;
			$totalStreams = 0;
			$maxViews = 0;

			foreach ($channels as $channel) {
				$views = json_decode($channel->views, true);

				// Check if views are available
				if ($views) {
					$viewsCount = count($views);
					$totalViews += $viewsCount;
					$totalStreams++;

					// Update maximum views
					$channelMaxViews = collect($views)->count();
					if ($channelMaxViews > $maxViews) {
						$maxViews = $channelMaxViews;
					}
				}
			}

			// Calculate average views per stream
			$avgViewsPerStream = $totalStreams > 0 ? $totalViews / $totalStreams : 0;

			return response()->json([
				'avg_stream_views' => $avgViewsPerStream,
				'max_stream_views' => $maxViews,
			], 200);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
		}
	}
	public function upload(Request $request)
	{
		$userId = $request->input('userId');
		$sessionId = $request->input('sessionId');
		$ffmpeg = \FFMpeg\FFMpeg::create([
			'ffmpeg.binaries'  => config('app.ffmpeg_binaries'),
			'ffprobe.binaries' => config('app.ffprobe_binaries'),
		]);
		$ffmpegPath = config('app.ffmpeg_binaries');
		$timestamp = time();
		$relativeDirectory = "users/private/{$userId}/streamdeck/recorded_videos_{$timestamp}";
		$storageDirectory = storage_path("app/{$relativeDirectory}");

		$outputDir = "{$storageDirectory}/output_file";
		$m3u8Dir = "{$storageDirectory}/m3u8";
		if (!is_dir($outputDir)) {
			mkdir($outputDir, 0755, true);
		}
		if (!is_dir($m3u8Dir)) {
			mkdir($m3u8Dir, 0755, true);
		}

		if ($request->hasFile('file')) {
			$file = $request->file('file');
			$originalFileName = uniqid() . '.' . $file->getClientOriginalExtension();
			$originalFilePath = "{$outputDir}/{$originalFileName}";
			$file->move($outputDir, $originalFileName);

			// Convert to MP4
			$mp4FileName = pathinfo($originalFileName, PATHINFO_FILENAME) . '.mp4';
			$mp4FilePath = "{$outputDir}/{$mp4FileName}";

			$command = "ffmpeg -i {$originalFilePath} -g 30 -c:v libvpx-vp9 -b:v 1M -c:a libvorbis {$mp4FilePath}";
			shell_exec($command);

			// Convert MP4 to HLS 
			$playlistPath480p = "{$m3u8Dir}/playlist_480p.m3u8";
			$command480p = "{$ffmpegPath} -i " . escapeshellarg($mp4FilePath) . " -vf scale=w=854:h=480 -preset veryfast -c:v libx264 -b:v 1M -c:a aac -start_number 0 -hls_time 10 -hls_list_size 0 -f hls " . escapeshellarg($playlistPath480p);
			shell_exec($command480p);

			$thumbnailPath = "{$storageDirectory}/thumbnail.jpg";
			$video = $ffmpeg->open($mp4FilePath);
			$frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1));
			$frame->save($thumbnailPath);

			$getID3 = new \getID3();
			$fileInfo = $getID3->analyze($mp4FilePath);
			$duration = isset($fileInfo['playtime_seconds']) ? $fileInfo['playtime_seconds'] : null;

			$video = new RecordedVideos();
			$video->user_id = $userId;
			$video->title = $originalFileName;
			$video->live_path = "{$relativeDirectory}/m3u8/playlist_480p.m3u8";
			$video->file_path = "{$relativeDirectory}/output_file/{$mp4FileName}";
			$video->thumbnail = "{$relativeDirectory}/thumbnail.jpg";
			$video->duration = $duration;
			$video->save();

			if (file_exists($originalFilePath)) {
				unlink($originalFilePath);
			}

			return response()->json([
				'success' => 'File uploaded, encoded, and converted successfully',
				'video' => $video,
			], 200);
		}

		return response()->json(['error' => 'No file uploaded'], 400);
	}

	public function getWebmVideos($userId)
	{
		$videos = RecordedVideos::where('user_id', $userId)->orderBy('created_at', 'desc')->get();

		$response = $videos->map(function ($video) {
			return [
				'fileName' => basename($video->file_path),
				'video_id' => $video->id,
				'fileURL' => getFileTemporaryURL($video->file_path),
				'thumbnail' => getFileTemporaryURL($video->thumbnail),
				'duration' => (int)$video->duration,
				'date' => $video->created_at->format('Y-m-d'),
				'm3u8Path' => getFileTemporaryURL($video->live_path),
				'relative_path' => $video->file_path,
				'thumbnails_relative' => $video->thumbnail
			];
		});

		return response()->json($response);
	}


	public function deleteWebVideo(Request $request)
	{
		try {
			$videoId = $request->input('videoId');
			$video = RecordedVideos::find($videoId);

			if (!$video) {
				return response()->json(['error' => 'Video not found'], 404);
			}

			if (Storage::exists($video->file_path)) {
				Storage::delete($video->file_path);
			}

			if (Storage::exists($video->thumbnail)) {
				Storage::delete($video->thumbnail);
			}

			if (Storage::exists($video->live_path)) {
				$m3u8Dir = dirname($video->live_path);
				Storage::deleteDirectory($m3u8Dir);
			}

			$video->delete();

			return response()->json(['success' => 'Video deleted successfully'], 200);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
		}
	}

	public function resetStream(Request $request)
	{
		try {
		} catch (\Exception $e) {
		}
	}

	public function getBroadcastPDF(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$channel_id = $request->channel_id;
			$channelData = DB::table('channels')
				->join('schedule_channles', 'channels.id', '=', 'schedule_channles.channel_id')
				->select('channels.channel_name', 'schedule_channles.epg_data')
				->where('channels.id', $channel_id)
				->first();

			if (!$channelData) {
				return generateResponse([
					'type' => 'error',
					'code' => 200,
					'status' => false,
					'message' => "Channel not found",
					'toast' => true,
				]);
			}

			$epgData = json_decode($channelData->epg_data, true);

			$broadcastData = [];
			$totalDurationInSeconds = 0;
			foreach ($epgData as $item) {
				$duration = $item['duration'];
				$broadcastData[] = [
					'title' => $item['title'],
					'image' => $item['image'],
					'since' => $this->formatDateTime($item['since']),
					'till' => $this->formatDateTime($item['till']),
					'date' => $item['date'],
					'duration' => $this->formatDuration($duration),
					'raw_duration' => $duration
				];
				$totalDurationInSeconds += $duration;
			}

			$totalDurationFormatted = $this->formatDuration($totalDurationInSeconds);

			$pdfData = [
				'channel_name' => $channelData->channel_name,
				'broadcasts' => $broadcastData,
				'total_duration' => $totalDurationFormatted
			];

			$html = view('broadcast_pdf', compact('pdfData'))->render();

			$options = new Options();
			$options->set('defaultFont', 'Inter');
			$dompdf = new Dompdf($options);
			$dompdf->loadHtml($html);
			$dompdf->setPaper('A3', 'landscape');
			$dompdf->render();

			$filePath = "users/private/{$user->id}/streamdeck/broadcast.pdf";
			$pdfContent = $dompdf->output();

			Storage::put($filePath, $pdfContent);

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'PDF Generated Successfully',
				'toast' => true,
			], ['path' => getFileTemporaryURL($filePath)]);
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => $e->getMessage(),
				'toast' => true,
			]);
		}
	}


	private function formatDateTime($dateTime)
	{
		$date = new DateTime($dateTime);
		return $date->format('F j, Y g:i A');
	}
	private function formatDuration($seconds)
	{
		$hours = floor($seconds / 3600);
		$minutes = floor(($seconds % 3600) / 60);
		$remainingSeconds = $seconds % 60;

		if ($hours > 0) {
			return sprintf('%d:%02d:%02d hrs', $hours, $minutes, $remainingSeconds);
		} elseif ($minutes > 0) {
			return sprintf('%d:%02d mins', $minutes, $remainingSeconds);
		} else {
			return sprintf('%d seconds', $remainingSeconds);
		}
	}

	public function sendLiveNotiFicationToConnectedUser(Request $request)
	{
		try {
			$authToken = $request->header('authToken');
			$user = $request->attributes->get('user');
			$from_user_id = $user->id;
			$userName = $user->username;
			$ConnectionandFollowers = getConnectionsAndFollowerUserIds($from_user_id);
			// dd(getConnectionsAndFollowerUserIds($from_user_id));
			addNotificationsBulk($ConnectionandFollowers['connection_user_ids'], $from_user_id, $userName . " is going Live", "Go check his live content", null, "4", "/", null, $authToken);

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Notification sent successfully to connected user',
				'toast' => true
			]);
		} catch (\Exception $e) {
			Log::info('Notification Error' . $e->getMessage());
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Error adding notification',
				'toast' => true
			]);
		}
	}
	public function getUserConnections(Request $request)
	{
		try {
			$user = $request->attributes->get('user');
			$user_ID = $user->id;
			$ConnectionandFollowers = getConnectionsAndFollowerUserIds($user_ID);
			$search = $request->query('search');
			$connectionUserQuery = User::with('profile')
				->whereIn('id', $ConnectionandFollowers['connection_user_ids']);
			if (!empty($search)) {
				$connectionUserQuery->where(function ($query) use ($search) {
					$query->where('email', 'like', '%' . $search . '%')
						->orWhere('username', 'like', '%' . $search . '%');
				});
			}

			$connectionUserDetails = $connectionUserQuery->get(['id', 'username', 'email'])
				->map(function ($user) {
					return [
						'id' => $user->id,
						'username' => $user->username,
						'email' => $user->email,
						'silomail' => $user->username . "@silocloud.io",
						'profile_image_path' => $user->profile->profile_image_path ? getFileTemporaryURL($user->profile->profile_image_path) : null,
						'initials' => substr($user->username, 0, 2)
					];
				});
			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Fetched connected users successfully',
				'toast' => true
			], ['connectionandfollowers' => $connectionUserDetails]);
		} catch (\Exception $e) {
			Log::info('Notification Error: ' . $e->getMessage());
			return generateResponse([
				'type' => 'error',
				'code' => 200,
				'status' => false,
				'message' => 'Error fetching connected users',
				'toast' => true
			]);
		}
	}
	public function getTallySettingData(Request $request, $channelId)
	{
		try {
			$scheduleChannel = ScheduleChannles::where('channel_id', $channelId)->first();

			if ($scheduleChannel && !empty($scheduleChannel->epg_data)) {
				$epgDataArray = json_decode($scheduleChannel->epg_data, true);
				$totalFileSize = 0;
				$filePlace = null;

				$createdAtTimestamp = Carbon::parse($scheduleChannel->created_at)->timestamp;
				$fileName = "{$createdAtTimestamp}.mp4";

				foreach ($epgDataArray as $program) {
					$filePath = $program['file_path'];

					if (Storage::exists($filePath)) {
						$totalFileSize += Storage::size($filePath);
					}

					if (!$filePlace) {
						$filePlace = dirname($program['file_path']);
					}
				}

				$formattedTotalFileSize = formatFileSize($totalFileSize);

				$htmlResponse = '
                <div class="row py-2 line-height border-top border-3">
                    <div class="col-md-6">
                        <ul class="text-end">
                            <li>File Name :</li>
                            <li>File Size :</li>
                            <li>Format :</li>
                            <li>Streamkey :</li>
                            <li>RTMP Attachment :</li>
                            <li>File Place :</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul>
                            <li>' . $fileName . '</li>
                            <li>' . $formattedTotalFileSize . '</li>
                            <li>MP4</li>
                            <li>' . ($streamKey ?? 'N/A') . '</li>
                            <li>' . ($streamKey ?? 'N/A') . '</li>
                            <li>' . $filePlace . '</li>
                        </ul>
                    </div>
                </div>';

				return generateResponse([
					'type' => 'success',
					'code' => 200,
					'status' => true,
					'message' => 'Fetched tally data successfully',
					'toast' => true,
				], ['html' => $htmlResponse]);
			} else {
				return generateResponse([
					'type' => 'success',
					'code' => 200,
					'status' => true,
					'message' => 'No data found for the provided channel_id',
					'toast' => true,
				], ['html' => '']);
			}
		} catch (\Exception $e) {
			return generateResponse([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => 'Error fetching tally data',
				'toast' => true,
				'error' => $e->getMessage(),
				'html' => ''
			]);
		}
	}
	public function sendMailToConnections(Request $request)
	{
		DB::beginTransaction();
		try {
			$user_ids = $request->input('user_ids');
			$image_url = $request->input('image_url');
			$user = $request->attributes->get('user');
			$sender_name = $user->username;
			if (empty($user_ids)) {
				return generateResponse([
					'type' => 'error',
					'code' => 400,
					'status' => false,
					'message' => 'No user IDs provided',
					'toast' => true,
				]);
			}

			$user_data = User::select('id', 'username', 'email')
				->whereIn('id', $user_ids)
				->get();

			if ($user_data->isEmpty()) {
				return generateResponse([
					'type' => 'error',
					'code' => 404,
					'status' => false,
					'message' => 'No users found for the provided IDs',
					'toast' => true,
				]);
			}

			$usernames = $user_data->pluck('username')->toArray();
			$emails = $user_data->pluck('email')->toArray();
			if ($request->filled('live_stream')) {
				sendConnectionMails($usernames, $emails, $sender_name, $image_url, $live_stream = true);
			} else {
				sendConnectionMails($usernames, $emails, $sender_name, $image_url, $live_stream = false);
			}
			DB::commit();

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Emails sent successfully',
				'toast' => true,
			], [
				'user_data' => [
					'usernames' => $usernames,
					'emails' => $emails
				]
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			return generateResponse([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => 'Error sending mail',
				'toast' => true,
				'error' => $e->getMessage(),
			]);
		}
	}
	public function checkService(Request $request)
	{
		$user = $request->attributes->get('user');
		$user_id = $user->id;
		$subscription = getSubscriptionDetails($user_id, 2);
		$duration = getUserVideosDuration($user_id);
		$channel_count = getUserChannelsCount($user_id);
		$broadcast_count = getUserTvLivestreamsCount($user_id);
		try {
			$service_name = 'Free';
			$channel_value = 2;
			$video_value = 100;
			$broadcast_value = 0;

			if ($subscription) {
				$service_plan_data = is_array($subscription) ? $subscription['service_plan_data'] : $subscription->service_plan_data;
				$features = is_array($service_plan_data) ? $service_plan_data['features'] : $service_plan_data->features;

				$service_name = is_array($service_plan_data) ? $service_plan_data['name'] : $service_plan_data->name;
				$channel_value = is_array($features) ? $features['Channel']['value'] : $features->Channel->value;
				$video_value = is_array($features) ? $features['Video']['value'] : $features->Video->value;
				$broadcast_value = is_array($features) ? $features['Broadcast']['value'] : $features->Broadcast->value;
			}

			return generateResponse([
				'type' => 'success',
				'code' => 200,
				'status' => true,
				'message' => 'Subscription fetched successfully',
				'toast' => true,
			], [
				'service_name' => $service_name,
				'subscription_channel_count' => $channel_value,
				'subscription_video_duration' => $video_value,
				'subscription_broadcast_count' => $broadcast_value,
				'user_videos_duration' => $duration,
				'user_channel_count' => $channel_count,
				'user_broadcast_count' => $broadcast_count
			]);
		} catch (\Exception $e) {
			Log::info("Error fetching subscription" . $e->getMessage());
			return generateResponse([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => 'Error fetching subscription',
				'toast' => true,
			]);
		}
	}
	public function addHLSvideo(UploadVideoRequest $request)
	{
		DB::beginTransaction();
		try {
			$user = $request->attributes->get('user');
			$userId = $user->id;
			$videostream = new VideoStream();
			$total_duration = 0;

			if ($request->filled('video_url')) {
				// Handle video URL case
				$videostream->video_url = $request->video_url;

				if ($request->filled('title')) {
					$videostream->title = $request->title;
				} else {
					$videostream->title = '.m3u8';
				}
				$videostream->save();
			} else {
				// Handle file upload case
				if ($request->hasFile('file_path')) {
					foreach ($request->file('file_path') as $file) {
						$fileNameWithExtension = $file->getClientOriginalName();
						$filePath = "users/private/{$user->id}/video/{$fileNameWithExtension}";

						// Save the file to storage
						Storage::put($filePath, file_get_contents($file));

						// Generate thumbnail and get video duration
						$thumbnailPath = $this->generateThumbnail($userId, pathinfo($fileNameWithExtension, PATHINFO_FILENAME), $filePath);
						if ($thumbnailPath === null) {
							return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Thumbnail generation failed.', 'toast' => true]);
						}

						$getID3 = new getID3();
						$fileInfo = $getID3->analyze($file->getRealPath());
						$durationInSeconds = (int) $fileInfo['playtime_seconds'];
						$videostream->user_id = $userId;
						$videostream->file_path = $filePath;
						$videostream->title = pathinfo($fileNameWithExtension, PATHINFO_FILENAME);
						$videostream->thumbnail = $thumbnailPath;
						$videostream->duration = $durationInSeconds;
						$videostream->channel_uuid = Str::uuid();
						$videostream->save();
						// $outputFilePath = storage_path("app/{$filePath}");
						// $playlistPath = $this->convertToHLS($outputFilePath, $userId, $channelId, $date);
						// $videostream->file_path = $playlistPath;
					}
					$userVideos = VideoStream::where('user_id', $userId)->get();
					foreach ($userVideos as $video) {
						$total_duration += $video->duration;
					}
				} else {
					return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No files provided.', 'toast' => true]);
				}
			}

			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Video added successfully.', 'toast' => true, 'data' => [$videostream, 'total_duration' => $total_duration]]);
		} catch (\Exception $e) {
			Log::info('Error while adding video: ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error adding video.', 'toast' => true]);
		}
	}
	public function getChannelLogoPosition(Request $request)
	{
		DB::beginTransaction();
		try {
			$channel_id = $request->channel_id;
			$logo_position = Channel::where('id', $channel_id)->value('logo_position');
			if ($logo_position === null) {
				return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No logo position found for this channel.', 'toast' => true]);
			}
			$style = $logo_position === "0" ? "rightlogo" : "leftlogo";
			DB::commit();
			return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Fetched logo position.', 'toast' => true], ['style' => $style]);
		} catch (\Exception $e) {
			Log::info('Error while fetching position ' . $e->getMessage());
			DB::rollBack();
			return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while fetching position.', 'toast' => true]);
		}
	}
	public function deletePrograms(Request $request)
	{
		try {
			$toDeleteJson = $request->input('toDeleteJson');
			$channelId = $request->input('channel_id');
			$programIds = json_decode($toDeleteJson, true);
			if (json_last_error() !== JSON_ERROR_NONE || !is_array($programIds)) {
				return generateResponse([
					'type' => 'error',
					'code' => 400,
					'status' => false,
					'message' => 'Invalid toDeleteJson format, expected a JSON array.',
					'toast' => true
				]);
			}

			if ($programIds && $channelId) {
				$scheduleChannel = DB::table('schedule_channles')->where('channel_id', $channelId)->first();

				if ($scheduleChannel) {
					$epgData = json_decode($scheduleChannel->epg_data, true);

					if (json_last_error() !== JSON_ERROR_NONE) {
						return generateResponse([
							'type' => 'error',
							'code' => 400,
							'status' => false,
							'message' => 'Failed to decode epg_data JSON: ' . json_last_error_msg(),
							'toast' => true
						]);
					}

					if ($epgData && is_array($epgData)) {
						$filteredEpgData = array_filter($epgData, function ($program) use ($programIds) {
							return !in_array($program['id'], $programIds);
						});
						$updatedEpgData = json_encode(array_values($filteredEpgData));

						DB::table('schedule_channles')
							->where('channel_id', $channelId)
							->update(['epg_data' => $updatedEpgData]);
						return generateResponse([
							'type' => 'success',
							'code' => 200,
							'status' => true,
							'message' => 'Deleted programs successfully.',
							'toast' => true
						]);
					} else {
						return generateResponse([
							'type' => 'error',
							'code' => 400,
							'status' => false,
							'message' => 'Invalid EPG data.',
							'toast' => true
						]);
					}
				} else {
					return generateResponse([
						'type' => 'error',
						'code' => 404,
						'status' => false,
						'message' => 'Channel not found.',
						'toast' => true
					]);
				}
			} else {
				return generateResponse([
					'type' => 'error',
					'code' => 400,
					'status' => false,
					'message' => 'No program IDs or channel ID provided.',
					'toast' => true
				]);
			}
		} catch (\Exception $e) {
			// Log the error
			Log::error('Error while deleting programs: ' . $e->getMessage());

			// Return error response
			return generateResponse([
				'type' => 'error',
				'code' => 500,
				'status' => false,
				'message' => 'Error while deleting programs.',
				'toast' => true
			]);
		}
	}
}
