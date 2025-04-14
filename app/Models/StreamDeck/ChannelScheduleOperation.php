<?php
namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use getID3;
use Carbon\Carbon;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ChannelScheduleOperation extends Model
{
	use HasFactory;
	public static function getStreamListData($params = [])
	{
		$query = ChannelsSchedule::select('channels_schedule.*', 'channels_content.*')
			->leftJoin('channels_content', 'channels_content.epg_id', '=', 'channels_schedule.epg_id');

		if (isset($params['where'])) {
			foreach ($params['where'] as $key => $value) {
				$query->where('channels_schedule.' . $key, $value);
			}
		}

		if (isset($params['like'])) {
			$searchKeyword = $params['like'];
			$query->where('stream_name', 'like', '%' . $searchKeyword . '%');
		}

		if (isset($params['order_by'])) {
			$query->orderBy($params['order_by']);
		} else {
			$query->orderBy('channels_schedule.id', 'desc');
		}

		if (isset($params['limit'])) {
			if (isset($params['start'])) {
				$query->skip($params['start']);
			}
			$query->take($params['limit']);
		}

		return $query->get();
	}
	public static function getSingleRow($tablename, $condition, $select_array = '*')
	{
		return DB::table($tablename)
			->select($select_array)
			->where($condition)
			->first();
	}
	public static function uploadImage($file, $userId, $folder, $existingFilePath = null, $user)
	{
		if ($existingFilePath) {
			Storage::delete($existingFilePath);
		}

		$filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
		return $file->storeAs("users/private/{$user->id}/streamdeck/{$folder}", $filename);
	}

	public static function updatestreamepgdata($epg_id, $start_time, $stream_date)
	{
		ChannelsContent::where('epg_id', $epg_id)->update(['start_time' => $start_time, 'streaming_date' => $stream_date, 'notification_status' => "0"]);
		return true;
	}


	public static function  getStreamListedit($channel_id, $stream_id, $schedule_status, $is_ad = '')
	{
		$query = ChannelsContent::select('p.*')
			->from('channels_content as p')
			->join('channels_schedule as st', 'p.channel_id', '=', 'st.channel_id', 'right')
			->where('p.schedule_id', 'st.id')
			->where('p.channel_id', $channel_id)
			->where('st.id', $stream_id)
			->orderBy('p.position');

		if (!empty($is_ad)) {
			$query->where('p.is_ad', $is_ad);
		}

		$result = $query->get();
		return $result;
	}

	public static function  fetchLastStartTime($channel_id, $schedule_id)
	{
		$query = ChannelsContent::select('*')
			->where('channel_id', $channel_id)
			->when($schedule_id == '', function ($query) {
				return $query->whereNull('schedule_id');
			}, function ($query) use ($schedule_id) {
				return $query->where('schedule_id', $schedule_id);
			})
			->orderByDesc('position')
			->first();
		return $query;
	}

	public static function addStreamContent($postData = [])
	{
		return ChannelsContent::create($postData);
	}

	public static function fetchLastInsEpg($channel_id, $current_time, $position, $schedule_id)
	{
		$query = ChannelsContent::select('epg_id')
			->where('channel_id', $channel_id)
			->when($schedule_id == '', function ($query) {
				return $query->whereNull('schedule_id');
			}, function ($query) use ($schedule_id) {
				return $query->where('schedule_id', $schedule_id);
			})
			->where('position', $position)
			->first();
		return $query;
	}

	public static function updateChannelContentAdd($schedule_id, $channel_id, $duration)
	{
		$query = "UPDATE channels_content SET streaming_date = DATE_FORMAT((SUBTIME(CONCAT((streaming_date), ' ', (start_time)), ?)), '%Y-%m-%d'), start_time = DATE_FORMAT(SUBTIME(CONCAT(TRIM(streaming_date), ' ', (start_time)), ?), '%H:%i:%s') WHERE schedule_id > ? AND channel_id = ?";
		return DB::update($query, [$duration, $duration, $schedule_id, $channel_id]);
	}

	public static function updateExtOtherSchedule($channel_id, $schedule_id, $duration)
	{
		$query = "UPDATE channels_schedule SET stream_end_time = SUBTIME(stream_end_time, ?) WHERE id = ? AND channel_id = ?";
		DB::update($query, [$duration, $schedule_id, $channel_id]);

		$query1 = "UPDATE channels_schedule SET stream_start_time = SUBTIME(stream_start_time, ?), stream_end_time = SUBTIME(stream_end_time, ?) WHERE id > ? AND channel_id = ?";
		return DB::update($query1, [$duration, $duration, $schedule_id, $channel_id]);
	}

	public static function  updateChannelEpg($transcode_id, $epg_id)
	{
		return ChannelsContent::where('epg_id', $epg_id)->update(['transcode_status_id' => $transcode_id]);
	}

	public static function  updateStreamContentThumb($epg_id, $thumbnail)
	{
		return ChannelsContent::where('epg_id', $epg_id)->update(['thumbnail' => $thumbnail]);
	}

	public static function  getStreamVideosEdit($channel_id, $schedule_id, $transcode_status)
	{
		return DB::table('channels_content as pd')
			->select('*')
			->where('channel_id', $channel_id)
			->where('schedule_id', $schedule_id)
			->orderBy('position')
			->get()
			->toArray();
	}

	public static function updateStreamschedule($stream_id, $epg_list)
	{
		return DB::table('channels_schedule')
			->where('id', $stream_id)
			->update(['epg_id' => $epg_list]);
	}



	public static function getStreamList($channel_id, $stream_id, $schedule_status, $is_ad = '')
	{
		$epgList = DB::table('channels_content as p')
			->join('channels_schedule as st', 'p.channel_id', '=', 'st.channel_id')
			->select('p.*')
			->where('p.schedule_id', $stream_id)
			->where('p.channel_id', $channel_id)
			->where('st.id', $stream_id)
			->orderBy('p.position')
			->get()
			->toArray();

		return $epgList;
	}


	public static function getContentExtList($params = [])
	{
		$query = DB::table('channels_content');

		if (isset($params['where'])) {
			$query->where($params['where']);
		}

		if (isset($params['like'])) {
			$search_keyword = $params['like'];
			$query->where('epg_name', 'LIKE', "%{$search_keyword}%");
		}

		if (isset($params['returnType']) && $params['returnType'] == 'count') {
			return $query->count();
		} elseif (isset($params['channel_id']) || (isset($params['returnType']) && $params['returnType'] == 'single')) {
			if (!empty($params['channel_id'])) {
				$query->where('channel_id', $params['channel_id']);
			}
			return $query->first();
		} else {
			if (isset($params['order_by'])) {
				$query->orderBy('epg_id', 'desc');
			} else {
				$query->orderBy('position', 'asc');
			}

			if (isset($params['start']) && isset($params['limit'])) {
				$query->offset($params['start'])->limit($params['limit']);
			} elseif (!isset($params['start']) && isset($params['limit'])) {
				$query->limit($params['limit']);
			}

			return $query->get()->toArray();
		}
	}


	public static function updateStreamInfo($postData = array(), $condition = array())
	{
		try {
			DB::table('channels_content')
				->where($condition)
				->update($postData);

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public static function updateChannelContent($epg_id, $channel_id, $duration, $stream_date, $start_time)
	{
		try {
			DB::table('channels_content')
				->where('streaming_date', '>=', $stream_date)
				->where('start_time', '>', $start_time)
				->where('channel_id', $channel_id)
				->update([
					'streaming_date' => DB::raw("DATE_FORMAT((SUBTIME(concat(streaming_date, ' ', start_time), '$duration')), '%Y-%m-%d')"),
					'start_time' => DB::raw("DATE_FORMAT(SUBTIME(concat(trim(streaming_date), ' ', start_time), '$duration'), '%H:%i:%s')")
				]);

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public static function removeStreamContent($condition)
	{
		try {
			ChannelsContent::where($condition)->delete();
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public static function iscurrentEpg($value)
	{
		$current_time = Carbon::now();
		$mediaStartTime = Carbon::parse($value->start_time);
		$mediaEndTime = $mediaStartTime->copy()->addSeconds($value->duration);

		$is_current_program = false;

		if ($mediaStartTime->gt($mediaEndTime)) {
			if ($current_time->gte($mediaStartTime) && $current_time->gte($mediaEndTime)) {
				$current_epg = $value;
				$seek_time = $current_time->diffInSeconds($mediaStartTime);
				if ($seek_time < 0) {
					$seek_time = 0;
				}
				$is_current_program = true;
			} else if ($current_time->gte(Carbon::parse('00:00')) && $current_time->lte($mediaEndTime)) {
				$current_epg = $value;
				$seek_time = Carbon::parse('midnight')->diffInSeconds($mediaStartTime) +
					$current_time->diffInSeconds(Carbon::parse('midnight'));
				if ($seek_time < 0) {
					$seek_time = 0;
				}
				$is_current_program = true;
			}
		} else {
			if ($current_time->between($mediaStartTime, $mediaEndTime)) {
				$current_epg = $value;
				$seek_time = $current_time->diffInSeconds($mediaStartTime);
				if ($seek_time < 0) {
					$seek_time = 0;
				}
				$is_current_program = true;
			}
		}

		if ($is_current_program) {
			$result['is_current_program'] = true;
			$result['current_epg'] = $value;
			$result['seek_time'] = $seek_time;
			return $result;
		} else {
			return false;
		}
	}


	public static function  fetchChannelVideosnew($channel_id, $streaming_date, $default_epg)
	{
		$instance = new self(); 
		$condition['order_by'] = 'streaming_date, start_time';
		$condition['where'] = ['channel_id' => $channel_id];
		return $instance->getEPGListnew($condition);
	}
	public static function getEPGListnew($params = [])
	{

		$date = Carbon::now();
		$curr_date = $date->format('Y-m-d');

		$query = DB::table('channels_content' . ' as ch')
			->select('ch.*', 'ch.thumbnail as channel_thumbnail', 'ch.description as channel_description')
			->where(function ($query) use ($params) {
				if (array_key_exists("where", $params)) {
					foreach ($params['where'] as $key => $val) {
						$query->where("ch.$key", $val);
					}
				}
			})
			->whereRaw("concat(ch.streaming_date, ' ', ch.start_time) > ?", [Carbon::now()->subDays(2)])
			->whereRaw("concat(ch.streaming_date, ' ', ch.start_time) < ?", [Carbon::now()->addDays(2)])
			->leftJoin('channels' . ' as m', 'ch.channel_id', '=', 'm.channel_id')
			->leftJoin('channels_schedule' . ' as cs', 'ch.schedule_id', '=', 'cs.id')
			->where('schedule_status', 3)
			->get();

		return $query;
	}

	public static function getChannelDetails($condition = null)
	{
		$instance = new self(); 
		$params = [];
		if (is_array($condition)) {
			$params['where'] = $condition;
		} else {
			$params['where'] = ['channel_id' => $condition];
		}
		$params['returnType'] = 'single';
		return $instance->getRows($params);
	}


	public static function getRows($params = [])
	{
		$query = DB::table('ch')->select('ch.*')->from('channels' . ' as ch');

		if (array_key_exists("where", $params)) {
			foreach ($params['where'] as $key => $val) {
				$query->where($key, $val);
			}
		}

		if (array_key_exists("like", $params)) {
			$search_keyword = $params['like'];
			$query->where('ch.name', 'like', "%$search_keyword%");
		}

		if (array_key_exists("returnType", $params) && $params['returnType'] == 'count') {
			$result = $query->count();
		} else {
			if (array_key_exists("channel_id", $params) || (array_key_exists("returnType", $params) && $params['returnType'] == 'single')) {
				if (!empty($params['channel_id'])) {
					$query->where('ch.channel_id', $params['channel_id']);
				}
				$result = $query->first();
			} else {
				$query->orderBy('ch.channel_id', 'desc');
				if (array_key_exists("start", $params) && array_key_exists("limit", $params)) {
					$query->offset($params['start'])->limit($params['limit']);
				} elseif (!array_key_exists("start", $params) && array_key_exists("limit", $params)) {
					$query->limit($params['limit']);
				}
				$result = $query->get();
			}
		}

		if (array_key_exists("with_epg", $params)) {
			if (array_key_exists("returnType", $params) && $params['returnType'] == 'single') {
				$result->epg_list = DB::table(TABLES::$CHANNELS_EPG)
					->where('channel_id', $result->channel_id)
					->get()
					->toArray();
			} else {
				foreach ($result as $key => $value) {
					$value->epg_list = DB::table(TABLES::$CHANNELS_EPG)
						->where('channel_id', $value->channel_id)
						->get()
						->toArray();
				}
			}
		}

		return $result;
	}

	public static function deleteScheduleData($channelId, $scheduleId)
	{
		$affectedRows = DB::table('channels_schedule')
			->whereRaw("FIND_IN_SET($scheduleId, epg_id)")
			->update([
				'epg_id' => DB::raw("TRIM(BOTH ',' FROM REPLACE(CONCAT(',', epg_id, ','), ',$scheduleId,', ','))")
			]);

		return $affectedRows > 0;
	}

	public static function deleteScheduleContentData($channelId, $scheduleId)
	{
		$deletedRows = ChannelsContent::where('channel_id', $channelId)
			->where('epg_id', $scheduleId)
			->delete();
		return $deletedRows > 0;
	}
	public static function getChannelList($params = [])
	{
		$query = DB::table('ch')->select('ch.*')->from('channels' . ' as ch');

		if (array_key_exists("where", $params)) {
			foreach ($params['where'] as $key => $val) {
				$query->where($key, $val);
			}
		}

		if (array_key_exists("like", $params)) {
			$search_keyword = $params['like'];
			$query->where('ch.name', 'like', "%$search_keyword%");
		}

		if (array_key_exists("returnType", $params) && $params['returnType'] == 'count') {
			$result = $query->count();
		} else {
			if (array_key_exists("channel_id", $params) || (array_key_exists("returnType", $params) && $params['returnType'] == 'single')) {
				if (!empty($params['channel_id'])) {
					$query->where('ch.channel_id', $params['channel_id']);
				}
				$result = $query->first();
			} else {
				$query->orderBy('ch.channel_id', 'desc');
				if (array_key_exists("start", $params) && array_key_exists("limit", $params)) {
					$query->offset($params['start'])->limit($params['limit']);
				} elseif (!array_key_exists("start", $params) && array_key_exists("limit", $params)) {
					$query->limit($params['limit']);
				}
				$result = $query->get();
			}
		}

		if (array_key_exists("with_epg", $params)) {
			if (array_key_exists("returnType", $params) && $params['returnType'] == 'single') {
				$result->epg_list = DB::table(TABLES::$CHANNELS_EPG)
					->where('channel_id', $result->channel_id)
					->get()
					->toArray();
			} else {
				foreach ($result as $key => $value) {
					$value->epg_list = DB::table(TABLES::$CHANNELS_EPG)
						->where('channel_id', $value->channel_id)
						->get()
						->toArray();
				}
			}
		}

		return $result;
	}


	public static function getVideoDuration($filePath)
	{
		if (!Storage::exists($filePath)) {
			$videoContents = file_get_contents($filePath);
			Storage::put($filePath, $videoContents);
		}
		$localFilePath = Storage::path($filePath);
		$getID3 = new \getID3;
		$fileInfo = $getID3->analyze($localFilePath);
		$durationSeconds = $fileInfo['playtime_seconds'];
		$durationFormatted = gmdate('H:i:s', $durationSeconds);
		return $durationFormatted;
	}
}
