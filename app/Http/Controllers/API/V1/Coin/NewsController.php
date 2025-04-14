<?php

namespace App\Http\Controllers\API\V1\Coin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\coin\NewsModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Coin\NewsAddRequest;
use App\Http\Requests\Coin\NewsUpdateRequest;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{

    public function getNews(Request $request)
{
    try {
        $user = $request->attributes->get('user');
     
        if (isset($request->id)) {
            $NewsDetails = NewsModel::where('id', $request->id)->get();
            $getTotalCount = count($NewsDetails);
        } else {
           
            // $newsQuery = NewsModel::query(); 
            $newsQuery = DB::table('coin_news')
            ->join('coin', 'coin_news.coin_id', '=', 'coin.id')
            ->select('coin_news.*', 'coin.coin_name');

            $getTotalCount = $newsQuery->count();

            if (isset($request->search_keyword)) {
                $searchKeyword = $request->search_keyword;
                $keywords = explode(' ', $searchKeyword);
                $newsQuery->where(function ($query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->where(function ($query) use ($keyword) {
                            $query->where("coin_news.title", "like", "%{$keyword}%")
                                  ->orWhere("coin_news.description", "like", "%{$keyword}%");
                        });
                    }
                });
            }
            if (isset($request->page)) {
                $start = ($request->page - 1) * $request->limit; // Changed page calculation
                $newsQuery->skip($start);
                $data["page"] = $request->page;
            }
            if (isset($request->limit)) {
                $newsQuery->take($request->limit);
            }

            $newsQuery->orderBy("id", "desc");
            $NewsDetails = $newsQuery->get();
        }
        $result = array();
        if (!empty($NewsDetails)) {
            foreach ($NewsDetails as $key => $value) {
                
                if (!empty($value->news_img)) {
                    $value->news_img = getFileTemporaryURL("public/" . $value->news_img);
               }else{
                $value->news_img = asset('assets/default/images/coin-news.png');
               }
   
                $result[] = $value;
            }
        }
   
        if (!empty($NewsDetails)) {
            $data["News"] = $NewsDetails;
            $data["totalRecords"] = $getTotalCount;
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'News data retrieved successfully', 'toast' => true, 'data' => $data]);
        } else {
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true, 'data' => ["packages" => array()]]);
        }
    } catch (\Exception $e) {
        Log::info('Get News API error: ' . $e->getMessage());
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
}

    public function addNews(NewsAddRequest $request){
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $role_id = $user->role_id;
            if ($role_id == '1' || $role_id == '2') {
                $news = new NewsModel();
                $news->coin_id = $request->coin_id;
                $news->title = $request->title;
                $news->news_img = $request->news_img;
                $news->description = $request->description;
                $news->author = $request->author;
             
                if ($request->hasFile('news_img')) {
                    $file = $request->file('news_img');
                    $fileName = $file->getClientOriginalName();
                    $news_img_Path = "public/assets/coin/{$user->id}/news_img/{$fileName}";
                    Storage::put($news_img_Path, file_get_contents($file));
                    $news_img_Path = substr($news_img_Path, strlen('public/'));
                    $news->news_img = $news_img_Path;
                }
               
                $news->save();
                DB::commit();
                $userReview = NewsModel::where('id', $news->id)->first();
                $news_data = [
                    'id' => $news->id,
                    'coin_id' => $news->coin_id,
                    'title' => $news->title,
                    'news_img' => getFileTemporaryURL("public/" . $news->news_img),
                    'description' => $news->description,
                    'author' => $news->author,
                ];
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'News data added successfully.', 'toast' => true], ['news' => $news_data]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('News add API error : ' . $e->getMessage());
            DB::rollBack();
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }


    public function updateNews(NewsUpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $role_id = $user->role_id;
            if ($role_id == '1' || $role_id == '2') {
                $news_id = $request->input('news_id');
                
                $news = NewsModel::where('id', $news_id)->first();
               
                if ($request->filled('coin_id')) {
                    $news->coin_id = $request->coin_id;
                }
                if ($request->filled('title')) {
                    $news->title = $request->title;
                }
                if ($request->filled('description')) {
                    $news->description = $request->description;
                }
                if ($request->filled('author')) {
                    $news->author = $request->author;
                }
               
               
                if ($request->hasFile('news_img')) {
                   
                    if ($news->event_attachment) {
                        Storage::delete($news->news_img);
                    }
                    $file = $request->file('news_img');
              
                    $fileName = $file->getClientOriginalName();
                    $news_img_Path = "public/assets/coin/{$user->id}/news_img/{$fileName}";
                    Storage::put($news_img_Path, file_get_contents($file));
                    $news_img_Path = substr($news_img_Path, strlen('public/'));
                    $news->news_img = $news_img_Path;
                }
               
                $news->save();  
                DB::commit();
                $news_silo = NewsModel::where('id', $news->id)->first();
                $news_data = [
                    'id' => $news_silo->id,
                    'coin_id' => $news_silo->coin_id,
                    'title' => $news_silo->title,
                    'description' => $news_silo->description,
                    'news_img' => getFileTemporaryURL("public/" . $news_silo->news_img),
                    'author' => $news_silo->author,
                ];
              
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'News updated successfully.', 'toast' => true], ['news' => $news_data]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('News update API error : ' . $e->getMessage());
            DB::rollBack();
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function deleteNews(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $role_id = $user->role_id;
            if ($role_id == '1' || $role_id == '2') {
                $news_id = $request->input('news_id');
                $news = NewsModel::where('id', $news_id)->first();

                if (!$news) {
                    DB::rollBack();
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'News not found', 'toast' => true]);
                }
                if ($news->news_img) {
                storage::delete($news->news_img);
                }
                $news->delete();
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'News deleted successfully.', 'toast' => true]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('News delete API error : ' . $e->getMessage());
            DB::rollBack();
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    
    
}
