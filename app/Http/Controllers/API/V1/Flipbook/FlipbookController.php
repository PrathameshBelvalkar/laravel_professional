<?php

namespace App\Http\Controllers\API\V1\Flipbook;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Flipbook\Flipbook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Flipbook\FlipbookSell;
use Illuminate\Support\Facades\Storage;
use App\Models\Flipbook\CollectionShare;
use Illuminate\Support\Facades\Validator;
use App\Models\Flipbook\FlipbookCollection;
use App\Models\Flipbook\FlipbookPublication;
use App\Http\Requests\Flipbook\GetFlipbookListRequest;
use App\Http\Requests\Flipbook\AddFlipbookUploadRequest;
use App\Http\Requests\Flipbook\GetListByCollectionRequest;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class FlipbookController extends Controller
{
  public function upload(AddFlipbookUploadRequest $request)
  {
    $user = $request->attributes->get('user');
    try {
      $isFirstPDF = Flipbook::where('user_id', $user->id)->doesntExist();
      $collectionId = $request->filled('collection_id') ? $request->collection_id : null;

      $pdf_data = new Flipbook();
      $pdf_data->user_id = $user->id;
      $pdf_data->collection_id = $collectionId;

      $thumbnailDirectoryPath = "users/private/{$user->id}/flipbook/pdfs/thumbnails/";
      $pdfDirectoryPath = "users/private/{$user->id}/flipbook/pdfs/";

      if ($collectionId) {
        $pdfDirectoryPath = "users/private/{$user->id}/flipbook/collections/{$collectionId}/pdfs/";
        $thumbnailDirectoryPath = "users/private/{$user->id}/flipbook/collections/{$collectionId}/pdfs/thumbnails/";
      }

      if (!Storage::exists($pdfDirectoryPath)) {
        Storage::makeDirectory($pdfDirectoryPath);
      }

      if (!Storage::exists($thumbnailDirectoryPath)) {
        Storage::makeDirectory($thumbnailDirectoryPath);
      }
      $title = "";
      if ($request->hasFile('pdf_file')) {
        $file = $request->file('pdf_file');
        $title = $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $fileSizeKB = round($fileSize / 1024, 1);
        $filePath = $pdfDirectoryPath . $fileName;

        $flipbookName = explode(".", $file->getClientOriginalName())[0];
        $tempFlipbookName = $flipbookName . "_" . date("Ymdhis");
        $tempFlipbookName = Str::slug($tempFlipbookName, "-");
        $tempFlipbookName = $tempFlipbookName . "." . $file->getClientOriginalExtension();
        $filePath = $pdfDirectoryPath . $tempFlipbookName;


        $stored = Storage::put($filePath, file_get_contents($file));

        $pdf_data->pdf_file = $filePath;
      }

      if ($request->hasFile('thumbnail')) {
        $thumbnail = $request->file('thumbnail');
        $thumbnailName = $thumbnail->getClientOriginalName();
        $thumbnailPath = $thumbnailDirectoryPath . $thumbnailName;

        $thumbnailName = explode(".", $thumbnail->getClientOriginalName())[0];
        $tempThumbnailNameName = "flipbook_thumbnail_" . date("Ymdhis") . "_" . $thumbnailName;
        $tempThumbnailNameName = Str::slug($tempThumbnailNameName, "-");
        $tempThumbnailNameName = $tempThumbnailNameName . "." . $thumbnail->getClientOriginalExtension();
        $thumbnailPath = $thumbnailDirectoryPath . $tempThumbnailNameName;

        $thumbnailStored = Storage::put($thumbnailPath, file_get_contents($thumbnail));
        $pdf_data->thumbnail = $thumbnailPath;
      }
      $pdf_data->save();

      if (isset($request->is_publish) && $request->is_publish == "1") {
        $visibility = $request->visibility;
        $title = isset($request->title) ? $request->title : $title;
        $flipbookPublication = new FlipbookPublication();
        $flipbookPublication->user_id = $user->id;
        $flipbookPublication->flipbook_id = $pdf_data->id;
        $status = "1";
        $flipbookPublication->status = $status;
        $flipbookPublication->description = "null";
        $flipbookPublication->collection_id = $pdf_data->collection_id;
        $flipbookPublication->title = $title;
        $flipbookPublication->visibility = $visibility;
        $flipbookPublication->save();
      }
      if ($isFirstPDF) {
        addNotification($user->id, $user->id, "You have just created your first Flipbook!", "", null, "16", "/collection", null);
      }

      $pdf_data->pdf_file_url = getFileTemporaryURL($pdf_data->pdf_file);
      $pdf_data->thumbnail_url = $pdf_data->thumbnail ? getFileTemporaryURL($pdf_data->thumbnail) : null;

      $data = [
        'id' => $pdf_data->id,
        'user_id' => $pdf_data->user_id,
        'collection_id' => $pdf_data->collection_id,
        'pdf_file' => $pdf_data->pdf_file,
        'thumbnail' => $pdf_data->thumbnail,
        'pdf_file_url' => $pdf_data->pdf_file_url,
        'thumbnail_url' => $pdf_data->thumbnail_url,
        'pdf_file_name' => $fileName,
        'pdf_file_size_kb' => "{$fileSizeKB} KB | PDF",
        'created_at' => $pdf_data->created_at,
        'updated_at' => $pdf_data->updated_at,
      ];
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'PDF uploaded successfully', 'toast' => true], $data);
    } catch (\Exception $e) {
      Log::error('PDF upload error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  //getpdflist
  public function getFlipbooks(GetFlipbookListRequest $request)
  {
    try {
      $limit = isset($request->limit) ? $request->limit : 10;
      $page = isset($request->page) ? $request->page : 1;
      $order_by = isset($request->order_by) ? $request->order_by : "updated_at";
      $order = isset($request->order) ? $request->order : "desc";
      if ($order_by == "collection_name")
        $order_by = "flipbook_collections.collection_name";
      else
        $order_by = "flipbooks." . $order_by;

      $offset = ($page - 1) * $limit;
      $user = $request->attributes->get('user');
      $collection_id = $request->input('collection_id');
      $flipbook_id = $request->input('flipbook_id');

      $query = Flipbook::where('flipbooks.user_id', $user->id)
        ->leftJoin('flipbook_collections', 'flipbooks.collection_id', '=', 'flipbook_collections.id')
        ->select(
          'flipbooks.id',
          'flipbooks.pdf_file',
          'flipbooks.thumbnail',
          'flipbooks.collection_id',
          'flipbook_collections.collection_name',
          'flipbooks.created_at',
          'flipbooks.updated_at',
        )->limit($limit)->offset($offset)->orderBy($order_by, $order);

      if ($collection_id !== null && $collection_id !== 'all') {
        $query->where('flipbooks.collection_id', $collection_id);
      }

      if ($flipbook_id !== null) {
        $query->where('flipbooks.id', $flipbook_id);
      }

      if ($request->filled('search_keyword')) {
        $searchKeyword = $request->search_keyword;
        $query->where(function ($query) use ($searchKeyword) {
          $query->orWhere('flipbooks.thumbnail', 'like', "%{$searchKeyword}%")
            ->orWhere('flipbooks.pdf_file', 'like', "%{$searchKeyword}%")->orWhere('flipbook_collections.collection_name', 'like', "%{$searchKeyword}%");
        });
      }

      $get_pdf = $query->orderBy('flipbooks.created_at', 'desc')->get();

      $result = [];
      foreach ($get_pdf as $pdf) {
        $pdfPath = $pdf->pdf_file;
        $pdfUrl = Storage::url($pdfPath);
        $pdfTemporaryUrl = getFileTemporaryURL($pdfPath);
        $thumbnailUrl = $pdf->thumbnail ? getFileTemporaryURL($pdf->thumbnail) : null;

        $fileName = basename($pdfPath);

        $fileSize = Storage::size($pdfPath);
        $fileSizeKB = round($fileSize / 1024, 1);

        $result[] = [
          'id' => $pdf->id,
          'pdf_file' => $pdfTemporaryUrl,
          'thumbnail' => $thumbnailUrl,
          'thumbnail_path' => $pdf->thumbnail,
          'collection_id' => $pdf->collection_id ?: null,
          'collection_name' => $pdf->collection_name,
          'pdf_file_name' => $fileName,
          'pdf_file_size_kb' => "{$fileSizeKB} KB | PDF",
        ];
      }
      if ($result) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Flipbook list retrieved successfully', 'toast' => true], ['pdf_list' => $result]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No flipbook data found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::error('Get PDF List error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  //get collection list on users
  public function getByCollectionList(GetListByCollectionRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      $type = $request->type;

      $limit = isset($request->limit) ? $request->limit : 10;
      $page = isset($request->page) ? $request->page : 1;
      $offset = ($page - 1) * $limit;

      $flipbookQuery = Flipbook::query()->limit($limit)->offset($offset);

      $flipbookQuery->where('flipbooks.user_id', $user->id);

      if ($type == "single") {
        $flipbookQuery->where('flipbooks.collection_id', $request->collection_id);
      } elseif ($type == "default") {
        $flipbookQuery->whereNull('flipbooks.collection_id');
      }
      if ($request->filled('search_keyword')) {
        $searchKeyword = $request->search_keyword;
        $flipbookQuery->where(function ($query) use ($searchKeyword) {
          $query->whereRaw("LOWER(REPLACE(SUBSTRING_INDEX(flipbooks.pdf_file, '/', -1), '.pdf', '')) LIKE ?", ["%" . strtolower($searchKeyword) . "%"])
            ->orWhereRaw("LOWER(REPLACE(SUBSTRING_INDEX(flipbooks.thumbnail, '/', -1), '.pdf', '')) LIKE ?", ["%" . strtolower($searchKeyword) . "%"])->orWhere('flipbook_collections.collection_name', 'like', "%{$searchKeyword}%");
        });
      }
      $flipbooks = $flipbookQuery->leftJoin('flipbook_collections', 'flipbooks.collection_id', '=', 'flipbook_collections.id')
        ->select(
          'flipbooks.id',
          'flipbooks.pdf_file',
          'flipbooks.thumbnail',
          'flipbooks.created_at',
          'flipbooks.updated_at',
          'flipbook_collections.collection_name',
          'flipbook_collections.id as collection_id'
        )->get();

      $result = [];
      foreach ($flipbooks as $flipbook) {
        $pdfTemporaryUrl = getFileTemporaryURL($flipbook->pdf_file);
        $thumbnailTemporaryUrl = $flipbook->thumbnail ? getFileTemporaryURL($flipbook->thumbnail) : null;

        $result[] = [
          'id' => $flipbook->id,
          'pdf_file' => $pdfTemporaryUrl,
          'thumbnail' => $thumbnailTemporaryUrl,
          'collection_name' => $flipbook->collection_name ? $flipbook->collection_name : null,
          'collection_id' => $flipbook->collection_id ? $flipbook->collection_id : null,
        ];
      }

      if (!empty($result)) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Flipbook list retrieved successfully', 'toast' => true], ['pdf_list' => $result]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No flipbook data found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::error('Get Records List error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  private function validateFlipbookId($id)
  {
    $validator = Validator::make(['id' => $id], [
      'id' => ['required', Rule::exists("flipbooks", "id")],
    ]);
    if ($validator->fails()) {
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid flipbook ID', 'toast' => true]);
    }
  }
  //Collection Delete Part
  //Every user will have Home collection which can't be deleted
  public function destroy(Request $request, $id)
  {
    $this->validateFlipbookId($id);

    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');

      $flipbook = Flipbook::where('id', $id)->where("user_id", $user->id)->first();
      if (!$flipbook) {
        DB::rollBack();
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Flipbook not found', 'toast' => true]);
      }
      $isFlipbookSold = FlipbookSell::where("flipbook_id", $flipbook->id)->count();
      if ($isFlipbookSold)
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => "Can't delete sold Flipbook",
          'toast' => true
        ]);

      $pdfFile = $flipbook->pdf_file;
      $thumbnailFile = $flipbook->thumbnail;
      if ($pdfFile && Storage::exists($pdfFile)) {
        Storage::delete($pdfFile);
      }
      if ($thumbnailFile && Storage::exists($thumbnailFile)) {
        Storage::delete($thumbnailFile);
      }
      $flipbook->delete();
      $flipBookPublicationDeletion = FlipbookPublication::where("flipbook_id", $id)->delete();
      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Flipbook deleted successfully', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error deleting collection and files: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  // collection share
  public function share(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $collectionId = $request->input('collection_id');
      $collection = FlipbookCollection::find($collectionId);
      $shareUsersIds = $request->input('share_users');
      $sharePermission = $request->input('share_permission');
      $shareVisibility = $request->input('visibility');
      $shareFiles = $request->input('share_files');

      if (!is_array($shareUsersIds)) {
        $shareUsersIds = json_decode($shareUsersIds, true);
      }

      if (!is_array($shareUsersIds)) {
        throw new \Exception('share_users must be an array');
      }

      if (!$collection) {
        throw new \Exception('Collection not found');
      }

      foreach ($shareUsersIds as $userId) {
        CollectionShare::updateOrCreate(
          ['collection_id' => $collectionId, 'user_id' => $userId],
          ['shared_permission' => $sharePermission, 'visibility' => $shareVisibility, 'is_shared' => '1']
        );
      }

      $collection->update(['is_shared' => '1']);

      $pdfFiles = Flipbook::where('collection_id', $collectionId)
        ->whereIn('pdf_file', $shareFiles)
        ->get();


      foreach ($pdfFiles as $pdf) {
        $sharedDirectoryPath = "users/shared/collections/{$collectionId}/pdfs/";
        $sharedFilePath = $sharedDirectoryPath . basename($pdf->pdf_file);

        if (!Storage::exists($sharedDirectoryPath)) {
          Storage::makeDirectory($sharedDirectoryPath);
        }

        if (!Storage::exists($sharedFilePath)) {
          Storage::copy($pdf->pdf_file, $sharedFilePath);
        }

        $pdf->pdf_file = $sharedFilePath;
        $pdf->save();
      }
      $data = [
        'collection' => $collection,
        'shared_users' => $shareUsersIds,
        'pdf_files' => $pdfFiles,
      ];
      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Collection shared successfully', 'toast' => true], $data);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Collection share error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  //fetch collection
  public function fetchSharedCollection(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $offset = $request->input('offset', 0);
      $limit = $request->input('limit', 12);
      $selectedPdfIds = $request->input('selected_pdf_ids', []);
      $isMoreCollectionsAvailable = false;

      $sharedCollectionsQuery = CollectionShare::where(function ($query) use ($user) {
        $query->where('user_id', $user->id)
          ->orWhereHas('collectionFlipbook', function ($query) use ($user) {
            $query->where('user_id', $user->id);
          });
      })->where('is_shared', '1')->distinct('collection_id');

      $totalSharedCollections = $sharedCollectionsQuery->count();

      if (($offset + $limit) < $totalSharedCollections) {
        $isMoreCollectionsAvailable = true;
      }

      $sharedCollections = $sharedCollectionsQuery->offset($offset)->limit($limit)->get();

      if ($sharedCollections->isEmpty()) {
        return response()->json([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'No shared collections found',
          'toast' => true,
        ]);
      }

      $sharedCollections = $sharedCollections->map(function ($collectionShare) use ($user, $selectedPdfIds) {
        $collection = $collectionShare->collectionFlipbook;
        $owner = ($collection->user_id == $user->id) ? "Me" : User::find($collection->user_id)->first_name . ' ' . User::find($collection->user_id)->last_name;

        $pdfFiles = Flipbook::where('collection_id', $collection->id)
          ->when(!empty($selectedPdfIds), function ($query) use ($selectedPdfIds) {
            return $query->whereIn('id', $selectedPdfIds);
          })
          ->get();

        $pdfFilesDetails = $pdfFiles->map(function ($pdf) {
          return [
            'pdf_id' => $pdf->id,
            'pdf_file' => $pdf->pdf_file,
            'created_at' => $pdf->created_at->format('Y-m-d H:i:s'),
          ];
        });

        $sharedUsers = CollectionShare::where('collection_id', $collection->id)->pluck('user_id')->toArray();

        $collectionDetails = [
          'collection_id' => $collection->id,
          'name' => $collection->collection_name,
          'owner' => $owner,
          'is_shared' => $collectionShare->is_shared,
          'visibility' => $collectionShare->visibility,
          'date' => $collectionShare->created_at->format('Y-m-d H:i:s'),
          'pdf_files' => $pdfFilesDetails,
          'shared_users' => $sharedUsers,
        ];

        return $collectionDetails;
      });

      $uniqueCollections = $sharedCollections->unique('collection_id')->values()->all();

      DB::commit();
      return response()->json([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Shared collections fetched successfully',
        'data' => $uniqueCollections,
        'is_more_collections_available' => $isMoreCollectionsAvailable,
        'toast' => true,
      ]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Fetch shared collections error: ' . $e->getMessage());
      return response()->json([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching shared collections: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function convertToPdf(Request $request)
  {
    $request->validate([
      'file' => 'required|mimes:docx',
    ]);
    $fileName = "doc_" . time() . '.' . $request->file->extension();
    $request->file->move(public_path('uploads'), $fileName);
    $domPdfPath = base_path('vendor/dompdf/dompdf');
    Settings::setPdfRendererPath($domPdfPath);
    Settings::setPdfRendererName('DomPDF');
    $docxFilePath = public_path('uploads/' . $fileName);
    $phpWord = IOFactory::load($docxFilePath);
    $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
    $pdfFileName = "doc_" . time() . '.pdf';
    $pdfFilePath = public_path('uploads/' . $pdfFileName);
    $pdfWriter->save($pdfFilePath);
    return response()->download($pdfFilePath);
  }
  public function viewFlipbookNotification(Request $request)
  {
    try {
      $authToken = $request->header('authToken');
      $flipbook_id = $request->flipbook_id;
      $username = "";
      $viewer_id = null;

      if ($authToken) {
        $decoded = JWT::decode($authToken, new Key(config('app.enc_key'), 'HS256'));
        $username = $decoded->username;
        $viewer_id = $decoded->cloudUserId;
      } else {
        $username = "Someone";
      }

      $flipbook = Flipbook::find($flipbook_id);

      if ($flipbook) {
        $owner_id = $flipbook->user_id;
        if (!$viewer_id || $viewer_id != $owner_id) {
          addNotification($owner_id, $owner_id, $username . " viewed your flipbook.", "", null, "16", "/", null);
        }
      } else {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Flipbook not found',
          'toast' => true
        ]);
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error on View ' . $e->getMessage());
      return response()->json([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Something went wrong on showing view ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }
}
