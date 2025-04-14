<?php

namespace App\Http\Controllers\API\V1\Flipbook;

use App\Http\Requests\Flipbook\UpdateFlipbookCollectionRequest;
use App\Models\Flipbook\Flipbook;
use App\Models\Flipbook\FlipbookCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Flipbook\AddFlipbookCollectionRequest;
use App\Http\Requests\Flipbook\GetFlipbookCollectionRequest;
use App\Models\Flipbook\FlipbookPublication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class FlipbookCollectionController extends Controller
{
  public function index(GetFlipbookCollectionRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      $limit = isset($request->limit) ? $request->limit : 10;
      $page = isset($request->page) ? $request->page : 1;
      $search_keyword = isset($request->search_keyword) ? $request->search_keyword : "";
      $order_by = isset($request->order_by) ? $request->order_by : "updated_at";
      $order = isset($request->order) ? $request->order : "desc";
      $offset = ($page - 1) * $limit;

      $query = FlipbookCollection::query();
      $query->selectRaw('id, collection_name, thumbnail, slug, updated_at');

      $searchCollectionCount = $allCollectionCount = FlipbookCollection::where("user_id", $user->id)
        ->whereNull('deleted_at')
        ->count();
      if (!empty($search_keyword)) {
        $query->where('collection_name', 'LIKE', "%$search_keyword%");
        $searchCollectionCount = FlipbookCollection::where("user_id", $user->id)->where('collection_name', 'LIKE', "%$search_keyword%")
          ->whereNull('deleted_at')
          ->count();
      }

      $collections = $query->limit($limit)
        ->offset($offset)
        ->orderBy($order_by, $order)
        ->where("user_id", $user->id)
        ->whereNull('deleted_at')
        ->get();

      if (!$collections->isEmpty()) {

        $collections->each(function ($collection) {
          $collection->thumbnail = $collection->thumbnail ? getFileTemporaryURL($collection->thumbnail) : null;
          return $collection;
        });

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Collections found', 'toast' => true], ['collections' => $collections, 'allCollectionCount' => $allCollectionCount, "searchCollectionCount" => $searchCollectionCount]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No collections found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('FlipbookCollectionController Error in file index ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    //
  }
  /**
   * Store a newly created resource in storage.
   */
  protected function ensureHomeCollectionExists($userId)
  {
    $homeCollection = FlipbookCollection::where('user_id', $userId)->where('id')->first();

    if (!$homeCollection) {
      $homeCollection = new FlipbookCollection();
      $homeCollection->collection_name = 'Home';
      $homeCollection->user_id = $userId;
      $homeCollection->save();
    }
    return $homeCollection;
  }

  public function store(AddFlipbookCollectionRequest $request)
  {
    try {
      DB::beginTransaction();

      $user = $request->attributes->get('user');
      $collection_name = $request->collection_name;
      $collection = FlipbookCollection::where('user_id', $user->id)->where("collection_name", $collection_name)->first();
      if (!$collection) {
        $collection = new FlipbookCollection();
        $collection->collection_name = $collection_name;
        $collection->slug = Str::slug($collection_name, "-");
        $collection->user_id = $user->id;
      }
      $collection->save();
      if ($request->hasFile('thumbnail')) {
        $file = $request->file('thumbnail');
        $collection->thumbnail = $this->addCollectionThumbnail($file, $collection->id, $user->id);
        $collection->save();
        $collection->thumbnail = getFileTemporaryURL($collection->thumbnail);
      }
      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Collection added', 'toast' => true], ['collection' => $collection->toArray()]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('FlipbookCollectionController Error in file store ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  private function addCollectionThumbnail($thumbnail, $collection_id, $user_id)
  {
    $thumbnailDirectoryPath = "users/private/{$user_id}/flipbook/collections/{$collection_id}/thumbnails/";
    if (!Storage::exists($thumbnailDirectoryPath)) {
      Storage::makeDirectory($thumbnailDirectoryPath);
    }
    $fileName = explode(".", $thumbnail->getClientOriginalName())[0];
    $tempFileName = "collection_thumbnail_" . $collection_id . "_" . $fileName;
    $thumbnailName = Str::slug($tempFileName, "-");
    $thumbnailName = $thumbnailName . "." . $thumbnail->getClientOriginalExtension();
    $thumbnailPath = $thumbnailDirectoryPath . $thumbnailName;
    $thumbnailStored = Storage::put($thumbnailPath, file_get_contents($thumbnail));
    return $thumbnailPath;
  }
  /**
   * Display the specified resource.
   */
  public function show(Request $request, string $id)
  {
    $this->validateFlipbookCollectionId($id);
    try {
      $user = $request->attributes->get('user');
      $collection = FlipbookCollection::where("id", $id)->where('user_id', $user->id)->select(['collection_name', 'thumbnail', 'slug', 'updated_at'])->first();
      if ($collection) {
        $collection->thumbnail = $collection->thumbnail ? getFileTemporaryURL($collection->thumbnail) : null;
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Collection found', 'toast' => true], ['collection' => $collection->toArray()]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Collection not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('FlipbookCollectionController Error in file show ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  private function validateFlipbookCollectionId($id)
  {
    $validator = Validator::make(['id' => $id], [
      'id' => ['required', Rule::exists("flipbook_collections", "id")],
    ]);
    if ($validator->fails()) {
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid collection ID', 'toast' => true]);
    }
  }
  public function update(Request $request, string $id)
  {
    return response()->json(['id' => $id]);
  }
  /**
   * Update the specified resource in storage.
   */
  public function updateCollection(UpdateFlipbookCollectionRequest $request, string $id)
  {
    $this->validateFlipbookCollectionId($id);
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $collection = FlipbookCollection::where("id", $id)->where('user_id', $user->id)->first();
      if ($collection) {
        if (isset($request->collection_name)) {
          $otherCollection = FlipbookCollection::whereNot("id", $id)->where('user_id', $user->id)->where("collection_name", $request->collection_name)->first();
          if ($otherCollection) {
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Given collection name exists for one of your collection', 'toast' => true]);
          }
        }
        $collection->collection_name = isset($request->collection_name) ? $request->collection_name : $collection->collection_name;
        if ($request->hasFile('thumbnail')) {
          if ($collection->thumbnail) {
            $existingFilePath = storage_path("app/" . $collection->thumbnail);
            if (file_exists($existingFilePath)) {
              unlink($existingFilePath);
            }
          }
          $file = $request->file('thumbnail');
          $collection->thumbnail = $this->addCollectionThumbnail($file, $collection->id, $user->id);
        }
        $collection->slug = isset($request->collection_name) ? Str::slug($request->collection_name, "-") : $collection->slug;
        $collection->save();
        DB::commit();
        $collection->thumbnail = getFileTemporaryURL($collection->thumbnail);
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Collection updated', 'toast' => true], ['collection' => $collection->toArray()]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Collection not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('FlipbookCollectionController Error in update ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Request $request, string $id)
  {
    $this->validateFlipbookCollectionId($id);
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $collection = FlipbookCollection::where("id", $id)->where('user_id', $user->id)->first();
      if ($collection) {
        if ($collection->thumbnail) {
          $existingFilePath = storage_path("app/" . $collection->thumbnail);
          if (file_exists($existingFilePath)) {
            unlink($existingFilePath);
          }
        }
        $collection->delete();
        $flipbookUpdates = Flipbook::where('collection_id', $id)->update(['collection_id' => null]);
        $flipbookPublicationUpdates = FlipbookPublication::where('collection_id', $id)->update(['collection_id' => null]);
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Collection deleted', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Collection not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('FlipbookCollectionController Error in file delete ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
