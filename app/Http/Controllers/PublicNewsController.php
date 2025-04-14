<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetPublicNewsRequest;
use App\Http\Requests\ReadPublicNewsRequest;
use App\Models\PublicNews;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicNewsController extends Controller
{
    public function fetchPublicNews(Request $request, $type)
    {
        try {
            if (!in_array($type, ['top-news', 'search-news', 'sports'])) {
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Invalid type", 'toast' => true]);
            }
            DB::beginTransaction();
            $key = $type == "top-news" ? "top" : "search";

            if ($type == "top")
                $news = $this->getWorldNews($key);
            else if ($type == "sports")
                $news = $this->getUSNews();
            else {
                $news = $this->getWorldNews($key, "entertainment");
                $news = $this->getWorldNews($key, "politics");
                $news = $this->getWorldNews($key, "sports");
            }

            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "News Fetching in progress", 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Error in PublicNewsController for fetchPublicNews " . $e->getMessage() . " @" . $e->getLine() . " file " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Something went wrong", 'toast' => true]);
        }
    }
    private function getWorldNews($type = "top", $category = "politics,sports,entertainment", $number = 50)
    {
        $newsArray = [];

        $topURL = "https://api.worldnewsapi.com/top-news?source-country=us&language=en&date=" . date("Y-m-d");
        // $searchURL = "https://api.worldnewsapi.com/search-news?number=$number&category=$category&language=en&earliest-publish-date=" . date("Y-m-d");
        $searchURL = "https://api.worldnewsapi.com/search-news?source-country=us&language=en&date=" . date("Y-m-d") . "&categories=$category&number=$number";
        $apiKey = config("app.world_news_api_key");

        $url = $type == "top" ? $topURL : $searchURL;
        if ($type == "search")
            $apiKey = config("app.world_news_api_search_key");

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'x-api-key: ' . $apiKey
        ));

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);

        if ($curl_error) {
            return [];
        } else {
            $data = json_decode($response, true);
            // Log::info("data => " . json_encode($data));
            if (isset($data['code']) && $data['code'] == "401") {
                return [];
            }
            if ($type == "top") {
                if (isset($data['top_news'])) {
                    foreach ($data['top_news'] as $item) {
                        if (isset($item['news']) && isset($item['news'][0])) {
                            $newsArray[] = $item['news'][0];
                        }
                    }
                }
            } else {
                if (isset($data['news'])) {
                    foreach ($data['news'] as $news) {
                        $newsArray[] = $news;
                    }
                }
            }
        }
        curl_close($ch);
        Log::info("Public URL " . $url . " key " . $apiKey);
        if (is_array($newsArray)) {
            Log::info("News Count " . count($newsArray));
            foreach ($newsArray as $row) {
                if (!empty($row['title']) && !empty($row['text']) && !empty($row['id'])) {
                    $publicNews = PublicNews::where("api_id", $row['id'])->where("api_source", "world_news")->first();
                    if (!$publicNews) {
                        $publicNews = new PublicNews();
                        $publicNews->api_id = $row['id'];
                        $publicNews->api_source = "world_news";
                    }
                    $publicNews->title = $row['title'];
                    $publicNews->news_text = $row['text'];
                    if (isset($row['summary']))
                        $publicNews->summary = $row['summary'];
                    if (isset($row['url']))
                        $publicNews->url = $row['url'];
                    if (isset($row['author']))
                        $publicNews->author = $row['author'];
                    if (isset($row['publish_date']))
                        $publicNews->publish_date = $row['publish_date'];
                    if (isset($row['video']))
                        $publicNews->video = $row['video'];
                    if (isset($row['catgory']))
                        $publicNews->catgory = $row['catgory'];
                    if (isset($row['image']))
                        $publicNews->image = $row['image'];
                    if (isset($row['source_country']))
                        $publicNews->source_country = $row['source_country'];
                    if (isset($row['language']))
                        $publicNews->language = $row['language'];
                    $publicNews->save();
                }
            }
        }

        return $newsArray;
    }

    private function getUSNews($type = "sports")
    {
        $newsArray = [];
        $source_country = 'us';
        $apiKey = config("app.sport_news_api_key");
        $sportsURL = "https://newsapi.org/v2/top-headlines?country=$source_country&category=$type&apiKey=$apiKey";

        $ch = curl_init($sportsURL);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_USERAGENT, "silocloud/1.0");

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);

        if ($curl_error) {
            return [];
        } else {
            $data = json_decode($response, true);
            // Log::info("data => " . json_encode($data));
            if (isset($data['code']) && $data['code'] == "401") {
                return [];
            }
            if ($type == "sports") {
                if (isset($data['articles'])) {
                    foreach ($data['articles'] as $item) {
                        if (isset($item['title'], $item['content'], $item['description'], $item['url'], $item['author'], $item['urlToImage'], $item['publishedAt'])) {
                            $newsArray[] = $item;
                        }
                    }
                }
            }
        }
        curl_close($ch);
        Log::info("Public URL " . $sportsURL . " key " . $apiKey);
        if (is_array($newsArray)) {
            Log::info("News Count " . count($newsArray));
            foreach ($newsArray as $row) {
                if (!empty($row['title']) && !empty($row['content']) && !empty($row['url'])) {
                    $publicNews = PublicNews::where("title", $row['title'])->where("api_source", "news_api")->first();
                    if (!$publicNews) {
                        $publicNews = new PublicNews();
                        $publicNews->title = $row['title'];
                        $publicNews->api_source = "news_api";
                    }

                    $publicNews->news_text = $row['content'];
                    if (isset($row['description']))
                        $publicNews->summary = $row['description'];
                    if (isset($row['url']))
                        $publicNews->url = $row['url'];
                    if (isset($row['author']))
                        $publicNews->author = $row['author'];
                    if (isset($row['publishedAt']))
                        // $publicNews->publish_date = $row['publishedAt'];
                        $publicNews->publish_date = \Carbon\Carbon::parse($row['publishedAt'])->format('Y-m-d H:i:s');
                    if (isset($row['video']))
                        $publicNews->video = $row['video'];
                    // if (isset($row['category']))
                    $publicNews->catgory = $type;
                    if (isset($row['urlToImage']))
                        $publicNews->image = $row['urlToImage'];
                    $publicNews->source_country = $source_country;
                    // if (isset($row['language']))
                    $publicNews->language = $source_country == 'us' ? "en" : null;
                    $publicNews->save();
                }
            }
        }

        return $newsArray;
    }

    public function getPublicNews(GetPublicNewsRequest $request)
    {
        try {
            $limit = isset($request->limit) ? $request->limit : 10;
            $last_days = isset($request->last_days) ? $request->last_days : null;
            $page = isset($request->page) ? $request->page : 1;
            $order_by = isset($request->order_by) ? $request->order_by : "publish_date";
            $order = isset($request->order) ? $request->order : "desc";
            $search_keyword = isset($request->search_keyword) ? $request->search_keyword : "";
            $category = isset($request->category) ? $request->category : null;
            $api_id = isset($request->api_id) ? $request->api_id : null;

            $offset = ($page - 1) * $limit;

            $newsListQuery = PublicNews::query();

            $newsListQuery->select(['id', 'title', 'news_text', 'api_id', 'summary', "url", "author", "publish_date", "video", "image", "source_country", "language", "catgory", "created_at", "updated_at", "read_count"]);
            $newsListQuery->orderBy($order_by, $order);

            $allCountQuery = $searchCountQuery = $newsListQuery;
            $allCount = $allCountQuery->count();
            if ($search_keyword) {
                $newsListQuery->where(function ($query) use ($search_keyword) {
                    $query->where("title", "like", "%$search_keyword%")->orWhere("news_text", "like", "%$search_keyword%");
                });
                $searchCountQuery->where(function ($query) use ($search_keyword) {
                    $query->where("title", "like", "%$search_keyword%")->orWhere("news_text", "like", "%$search_keyword%");
                });
            }
            $searchCount = $searchCountQuery->count();
            if ($api_id)
                $newsListQuery->where("api_id", $api_id);

            if ($last_days)
                $newsListQuery->whereDate('publish_date', '>=', now()->subDays($last_days));

            if ($category)
                $newsListQuery->where('catgory', $category);

            $newsListQuery->limit($limit)->offset($offset);

            $newsList = $newsListQuery->get();
            $count = count($newsList);
            if ($newsList->isNotEmpty()) {
                $newsList->transform(function ($news) {
                    if (!$news->image) {
                        $news->image = asset("assets/default/images/news_default.png");
                    }
                    return $news;
                });
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Public news found", 'toast' => true], ["news" => $newsList->toArray(), "count" => $count, "allCount" => $allCount, "searchCount" => $searchCount]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "No data found", 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Error in PublicNewsController for getPublicNews " . $e->getMessage() . " @" . $e->getLine() . " file " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Something went wrong", 'toast' => true]);
        }
    }
    public function removePublicNews(Request $request)
    {
        try {
            DB::beginTransaction();

            $max_public_news_read_count = config("app.max_public_news_read_count");
            $max_public_news_table_count = config("app.max_public_news_table_count");

            $allNewsCount = PublicNews::count();

            if ($allNewsCount < $max_public_news_table_count) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "News read count not exceeded yet", 'toast' => true]);
            }

            $threeDaysAgo = Carbon::now()->subDays(3);

            $newsListQuery = PublicNews::query();
            // $newsListByIPCount = $newsListQuery->where('publish_date', '<', $threeDaysAgo)->where(function ($query) use ($max_public_news_read_count) {
            //     $query->whereNull("visitors")->orWhereRaw("JSON_LENGTH(visitors)<$max_public_news_read_count");
            // })->selectRaw("id, visitors, JSON_LENGTH(visitors) as read_count");
            // $data['count'] = $newsListQuery->count();

            $newsListByReadCount = $newsListQuery->where('publish_date', '<', $threeDaysAgo)->where(function ($query) use ($max_public_news_read_count) {
                $query->where("read_count", "<", $max_public_news_read_count);
            })->selectRaw("id, read_count");
            $data['count'] = $newsListQuery->count();

            $newsListQuery->forceDelete();
            DB::commit();
            // $data['sql'] = $newsListQuery->toRawSql();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "News removing in process", 'toast' => true], $data);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Error in removePublicNews" . $e->getMessage() . " @" . $e->getLine() . " file " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Something went wrong", 'toast' => true]);
        }
    }
    public function readPublicNews(ReadPublicNewsRequest $request)
    {
        try {
            DB::beginTransaction();
            $ip_address = $request->ip_address;
            $id = $request->id;

            $news = PublicNews::where("id", $id)->first();
            if (!$news) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "No news found", 'toast' => true]);
            }
            $visitors = $news->visitors ? json_decode($news->visitors, true) : [];

            if (!in_array($ip_address, $visitors)) {
                $visitors[] = $ip_address;
                $read_count = count($visitors);
                $visitors = json_encode($visitors);
                $news->visitors = $visitors;
                // $news->read_count = $read_count;
                $news->save();
                DB::commit();
            }
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "News visitors updated", 'toast' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Error in readPublicNews " . $e->getMessage() . " @" . $e->getLine() . " file " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Something went wrong", 'toast' => true]);
        }
    }
}
