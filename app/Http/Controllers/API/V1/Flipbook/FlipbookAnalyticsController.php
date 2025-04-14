<?php

namespace App\Http\Controllers\API\V1\Flipbook;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Flipbook\FlipbookAnalytics;
use App\Models\Flipbook\Flipbook;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FlipbookAnalyticsController extends Controller
{
    public function saveViews(Request $request){
        DB::beginTransaction();
        try{
            $user = $request->attributes->get('user');
            $user_id = $user ? $user->id : null;
            $flipbook=Flipbook::where('id',$request->flipbook_id)->first();
            if(!$flipbook){
                return generateResponse(['type'=>'error','code'=>'500','status'=>'false','message'=>'Invalid flipbook_id']);
            }
            $flipbook_views=FlipbookAnalytics::where('flipbook_id',$request->flipbook_id)->first();
            if($flipbook_views){
                $flipbook_views->views+=1;
                $flipbook_views->save();
                DB::commit();
                return generateResponse(['type' => 'success','status' => true,'code' => 201,'message' =>'Views Added','data'=>$flipbook_views]);
            }

            $flipbook_view=new FlipbookAnalytics;
            //$flipbook_view->user_id=$user_id;
            $flipbook_view->flipbook_id =$request->flipbook_id;
            $flipbook_view->views+=1;

            $flipbook_view->save();
            DB::commit();
            return response()->json(['type' => 'success','status' => true,'code' => 201,'message' => 'Views Added','data'=>$flipbook_view]);
        }catch(\Exception $e){
            DB::rollback();
            Log::error('Error in saving views count: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error in saving View count'.$e->getMessage(), 'toast' => true,]);
        }

    }

    public function saveDownload(Request $request){
        DB::beginTransaction();
        try{
            $flipbook_id=$request->flipbook_id;
            $flipbook=Flipbook::where('id',$request->flipbook_id)->first();
            if(!$flipbook){
                return generateResponse(['type'=>'error','code'=>'500','status'=>'false','message'=>'Invalid flipbook_id']);
            }
            $flipbook_downloads=FlipbookAnalytics::where('flipbook_id',$flipbook_id)->first();
            if($flipbook_downloads){
                $flipbook_downloads->downloads+=1;
                $flipbook_downloads->save();
                DB::commit();
                return generateResponse(['type' => 'success','status' => true,'code' => 201,'message' => 'Flipbook download added','data'=>$flipbook_downloads]);
            }
            $flipbook_download=new FlipbookAnalytics;
            $flipbook_download->flipbook_id =$flipbook_id;
            $flipbook_download->downloads+=1;
            $flipbook_download->save();
            DB::commit();
            return generateResponse(['type' => 'success','status' => true,'code' => 201,'message' => 'Flipbook download added','data'=>$flipbook_download]);
        }catch(\Exception $e){
            DB::rollback();
            Log::error('Error in saving downloads count: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error saving View count', 'toast' => true,]);
        }

    }

    public function saveCountries(Request $request){
        DB::beginTransaction();
        try{
            $flipbook_id=$request->flipbook_id;
            $flipbook=Flipbook::where('id',$flipbook_id)->first();
            if(!$flipbook){
                return generateResponse(['type'=>'error','code'=>'500','status'=>'false','message'=>'Invalid flipbook_id']);
            }
            $flipbook=FlipbookAnalytics::where('flipbook_id',$flipbook_id)->first();

            if(!$flipbook){
                return generateResponse(['type'=>'error','code'=>'500','status'=>'false','message'=>'Invalid flipbook_id']);
            }
            $country=$request->country;
            $countries=json_decode($flipbook->countries,true)??[];
            $country_exist=false;

            foreach($countries as &$existing_country){
                if($existing_country['country']==$country){
                    $existing_country['count']+=1;
                    $country_exist=true;
                    break;
                }
            }

            if(!$country_exist){
                $countries[]=[
                    'country'=>$country,
                    'count'=>1,
                    ];
            }

            $flipbook->countries=$countries;
            $flipbook->save();
            DB::commit();
            return generateResponse(['type' => 'success','status' => true,'code' => 200,'message' => 'Country saved  successfully','data'=>$flipbook]);
        }catch(\Exception $e){
            DB::rollback();
            Log::error('Error in saving country count: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error saving country count', 'toast' => true,]);
        }
    }

    public function saveDeviceName(Request $request){
        DB::beginTransaction();
        try{
            $flipbook_id=$request->flipbook_id;
            $flipbook=FlipbookAnalytics::where('flipbook_id',$flipbook_id)->first();
            if(!$flipbook){
                return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Invalid flipbook_id']);
            }
            $device=$request->device_name;
            $devicenames=json_decode($flipbook->device_names,true)??[];
            $device_exist=false;

            foreach($devicenames as &$devicename){
                if($devicename['name']==$request->device_name){
                    $devicename['count']+=1;
                    $device_exist=true;
                    break;
                }
            }

            if(!$device_exist){
                $devicenames[]=[
                    'name'=>$request->device_name,
                    'count'=>1
                ];
            }

            $flipbook->device_names=json_encode($devicenames);
            $flipbook->save();
            DB::commit();
            return generateResponse(['type' => 'success','code' => 201,'status' => true,'message' => 'Device added']);
        }catch(\Exception $e){
            DB::rollback();
            Log::error('Error in saving device name: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error saving device', 'toast' => true,]);
        }

    }

    public function saveClicks(Request $request){

        DB::beginTransaction();
        try{
            $user = $request->attributes->get('user');
            $user_id = $user ? $user->id : null;
            $flipbook=Flipbook::where('id',$request->flipbook_id)->first();
            if(!$flipbook){
                return generateResponse(['type'=>'error','code'=>'500','status'=>'false','message'=>'Invalid flipbook_id']);
            }
            $flipbook_clicks=FlipbookAnalytics::where('flipbook_id',$request->flipbook_id)->first();
            if($flipbook_clicks){
                $flipbook_clicks->clicks+=1;
                $flipbook_clicks->save();
                DB::commit();
                return generateResponse(['type' => 'success','status' => true,'code' => 201,'message' =>'Clicks Added','data'=>$flipbook_clicks]);
            }

            $flipbook_click=new FlipbookAnalytics;
            $flipbook_click->user_id=$user_id;
            $flipbook_click->flipbook_id =$request->flipbook_id;
            $flipbook_click->clicks+=1;

            $flipbook_click->save();
            DB::commit();
            return response()->json(['type' => 'success','status' => true,'code' => 201,'message' => 'Clicks Added','data'=>$flipbook_click]);
        }catch(\Exception $e){
            DB::rollback();
            Log::error('Error in saving views count: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error in saving Clicks count'.$e->getMessage(), 'toast' => true,]);
        }
    }
    public function getFlipbookAnalytics(Request $request){
        DB::beginTransaction();
        try{
            $flipbook=FlipbookAnalytics::where('flipbook_id',$request->flipbook_id)->first();
            if(!$flipbook){
                DB::commit();
                return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Invalid flipbook_id']);
            }
            $views=$flipbook['views'];
            $downloads=$flipbook['downloads'];
            $countries=json_decode($flipbook['countries']);
            $clicks=$flipbook['clicks'];
            $device=json_decode($flipbook['device_names']);
            $data=['user_id'=>$flipbook['user_id'],'flipbook_id'=>$flipbook['flipbook_id'],'views'=>$views,'downloads'=>$downloads,'countries'=>$countries,'clicks'=>$clicks,'device'=>$device];
            DB::commit();
            return generateResponse(['type' => 'success','status' => true,'code' => 200,'message' => 'Flipbook Details Featched successfully','data'=>$data]);
        }catch(\Exception $e){
            DB::rollback();
            return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Error in getting Flipbook details: ' . $e->getMessage(),]);
        }

    }


}
