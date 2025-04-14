<?php

namespace App\Http\Controllers\API\V1\Coin;
use App\Models\coin\ReportsModel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Requests\Coin\AddReportsRequest;
use App\Http\Requests\Coin\ReportUpdateRequest;
use Illuminate\Support\Facades\Storage;


class ReportController extends Controller
{

 public function getCategory(Request $request)
{
 
    $categoryData = DB::table('coin_category')->get();

    if (!$categoryData->isEmpty()) {
        return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'data retrieved successfully','toast' => true,'data' => ['category_data' => $categoryData]
        ]);
    } else {
        return generateResponse(['type' => 'error','code' => 200,'status' => false,'message' => 'data not found','toast' => true
        ]);
    }
}


 public function getSubCategory(Request $request)
{
 
    $subcategoryData = DB::table('coin_sub_category')->get();

    if (!$subcategoryData->isEmpty()) {
        return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'data retrieved successfully','toast' => true,'data' => ['category_data' => $subcategoryData]
        ]);
    } else {
        return generateResponse(['type' => 'error','code' => 200,'status' => false,'message' => 'data not found','toast' => true
        ]);
    }
}

 public function addReports(AddReportsRequest $request)
{
    DB::beginTransaction();
    try {
        $user = $request->attributes->get('user');
        $role_id = $user->role_id;

        if ($role_id == '1' || $role_id == '2') {
            // Check if the combination already exists
            $existingReport = ReportsModel::where('coin_id', $request->coin_id)
                ->where('category_id', $request->category_id)
                ->where('sub_category_id', $request->sub_category_id)
                ->first();

            if ($existingReport) {
                return generateResponse([
                    'type' => 'error',
                    'code' => 400,
                    'status' => false,
                    'message' => 'A report with the same coin_id, category_id, and sub_category_id already exists.',
                    'toast' => true
                ]);
            }

            $Report_report = new ReportsModel();
            $Report_report->coin_id = $request->coin_id;
            $Report_report->category_id = $request->category_id;
            $Report_report->sub_category_id = $request->sub_category_id;

            if ($request->hasFile('report_file')) {
                if ($Report_report->report_file) {
                    Storage::delete($Report_report->report_file);
                }
                $file = $request->file('report_file');
                $fileName = $file->getClientOriginalName();
                $report_file_Path = "public/assets/coin/{$user->id}/report_file/{$fileName}";
                Storage::put($report_file_Path, file_get_contents($file));
                $report_file_Path = substr($report_file_Path, strlen('public/'));
                $Report_report->report_file = $report_file_Path;
            }
            $Report_report->save();

            DB::commit();
            $Report_report = ReportsModel::where('id', $Report_report->id)->first();
            $Reports = [
                'coin_id' => $Report_report->coin_id,
                'report_file' => getFileTemporaryURL("public/" . $Report_report->report_file),
            ];
            return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Report added successfully.','toast' => true], ['report' => $Reports]);
        } else {
            return generateResponse(['type' => 'error','code' => 403,'status' => false,'message' => 'You don\'t have privilege to perform the task','toast' => true
            ]);
        }
    } catch (\Exception $e) {
        Log::info('Coin Report API error : ' . $e->getMessage());
        DB::rollBack();
        return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Error while processing','toast' => true]);
    }
}

 public function getReportsCategory(Request $request)
{
    if ($request->has('report_id')) {
        $report_id = $request->input('report_id');

        $getreports = ReportsModel::where('report_id', $report_id)->get();
        $totalreportcount = $getreports->count();
          $result = array();
         if (!empty($getreports)) {
            foreach ($getreports as $key => $value) {
                $value["category_name"] = getCategoryOrSubCategory('coin_category','category_name',$value['category_id']);
                $value["sub_category_name"] = getCategoryOrSubCategory('coin_sub_category','sub_category_name',$value['sub_category_id']);
            }
        }
          $result[] = $value;
        if ($result) {
            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'categories retrieved successfully','toast' => false,'data' => ['category' => $getreports]
            ]);
        } else {
            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'No data found','toast' => false]);
        }
    } else {
        return response()->json(['type' => 'error','code' => 400,'status' => false,'message' => 'Coin ID is required','toast' => false]);
    }
}

 public function getReports(Request $request)
{   
      $coin_id = $request->input('coin_id');
      $category_id = $request->input('category_id');
      $sub_category_id = $request->input('sub_category_id');
    if ($coin_id) {
        $getreports = ReportsModel::where('coin_id', $coin_id)
            ->where('category_id', $category_id)
            ->where('sub_category_id', $sub_category_id)
            ->get();
          $result = array();
         if (!empty($getreports)) {
                foreach ($getreports as $key => $value) {
                    $value["report_file"] = getFileTemporaryURL("public/" . $value["report_file"]);
                    $result[] = $value;
                }
            }
    
        if ($result) {
            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'Report pdf retrieved successfully','toast' => false,'data' => ['count' => $getreports]
            ]);
        } else {
            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'No report data found','toast' => false]);
        }
    } else {
        return response()->json(['type' => 'error','code' => 400,'status' => false,'message' => 'Coin ID is required','toast' => false]);
    }
}


    public function getAdminReports(Request $request)
{
    try {
        $coin_id = $request->input('coin_id');
        $page = null;
        $limit = $request->input('limit', 10);
        $result = [];

        if ($request->filled('report_id')) {
            $report_id = $request->input('report_id');
            $reportArr = ReportsModel::where('id', $report_id)->first();
            if (!empty($reportArr)) {
                $Report[] = $reportArr->toArray();
            } else {
                $Report = [];
            }
            $getTotalCount = count($Report);
        } else {
            $Reportquery = DB::table('coin_reports')
                ->join('coin', 'coin_reports.coin_id', '=', 'coin.id')
                ->join('coin_category', 'coin_reports.category_id', '=', 'coin_category.id')
                ->join('coin_sub_category', 'coin_reports.sub_category_id', '=', 'coin_sub_category.id')
                ->select('coin_reports.id','coin_reports.report_file', 'coin.coin_name', 'coin_category.category_name', 'coin_sub_category.sub_category_name')
                ->orderBy('coin_reports.created_at', 'desc');

            if ($request->filled('search_keyword')) {
                $searchKeyword = $request->search_keyword;
                $keywords = explode(' ', $searchKeyword);
                $Reportquery->where(function ($query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->where(function ($query) use ($keyword) {
                            $query->where("coin.coin_name", "like", "%{$keyword}%")
                                ->orWhere("coin_category.category_name", "like", "%{$keyword}%")
                                ->orWhere("coin_sub_category.sub_category_name", "like", "%{$keyword}%");
                        });
                    }
                });
            }

            $getTotalCount = $Reportquery->count();

            if ($request->filled('page')) {
                $start = ($request->page - 1) * $limit;
                $Reportquery->skip($start);
                $page = $request->input('page');
            }

            $Reportquery->take($limit);

            $Report = $Reportquery->get();
        }

        if (!empty($Report)) {
            foreach ($Report as $value) {
                $value = (array) $value;
                
                if (isset($value['report_file'])) {
                    $value['report_url'] = $value['report_file'];
                    $value['report_file'] = getFileTemporaryURL("public/" . $value['report_file']);
                }

                $result[] = $value;
            }
        }

        if ($result) {
            return response()->json(['type' => 'success','code' => 200,'status' => true,'message' => 'Reports retrieved successfully','toast' => false,'data' => ["coin" => $result, "page" => $page, "count" => $getTotalCount]]);
        } else {
            return response()->json(['type' => 'error','code' => 200, 'status' => false, 'message' => 'No report data found','toast' => true]);
        }
    } catch (\Exception $e) {
        Log::info('Get report API error: ' . $e->getMessage());
        return response()->json(['type' => 'error','code' => 200,'status' => false,'message' => 'Error while processing','toast' => true]);
    }
}

   public function updateReport(ReportUpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $role_id = $user->role_id;
            if ($role_id == '1' || $role_id == '2') {
                $report_id = $request->input('report_id');
                $coin = ReportsModel::where('id', $report_id)->first();
                if ($request->filled('coin_id')) {
                    $coin->coin_id = $request->coin_id;
                }
                if ($request->filled('category_id')) {
                    $coin->category_id = $request->category_id;
                }
                if ($request->filled('sub_category_id')) {
                    $coin->sub_category_id = $request->sub_category_id;
                }
                
                if ($request->hasFile('report_file')) {
                    if ($coin->report_file) {
                        Storage::delete($coin->report_file);
                    }
                    $file = $request->file('report_file');
                    $fileName = $file->getClientOriginalName();
                    $report_file_Path = "public/assets/coin/{$user->id}/report_file/{$fileName}";
                    Storage::put($report_file_Path, file_get_contents($file));
                    $report_file_Path = substr($report_file_Path, strlen('public/'));
                    $coin->report_file = $report_file_Path;
                }
    
                $coin->save();
                DB::commit();
    
                $coin_silo = ReportsModel::where('id', $coin->id)->first();
             
                $coin_data = [
                    'id' => $coin_silo->id,
                    'coin_id' => $coin_silo->coin_id,
                    'category_name' => getCategoryOrSubCategory('coin_category','category_name',$coin_silo->category_id),
                    'sub_category_name' => getCategoryOrSubCategory('coin_sub_category','sub_category_name',$coin_silo->sub_category_id),
                    'report_file' => getFileTemporaryURL("public/" . $coin_silo->report_file),
                ];
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Report updated successfully.', 'toast' => true], ['coin' => $coin_data]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('Report update API error : ' . $e->getMessage());
            DB::rollBack();
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    
      
    public function deleteReport(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $role_id = $user->role_id;
            if ($role_id == '1' || $role_id == '2') {
                $report_id = $request->input('report_id');
                $report = ReportsModel::where('id', $report_id)->first();

                if (!$report) {
                    DB::rollBack();
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Report not found', 'toast' => true]);
                }
                storage::delete($report->report_file);
                $report->delete();
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Report deleted successfully.', 'toast' => true]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('Report delete API error : ' . $e->getMessage());
            DB::rollBack();
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
}
