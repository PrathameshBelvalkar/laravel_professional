<?php

namespace App\Http\Controllers\API\V1\Flipbook;

use Carbon\Carbon;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Flipbook\Flipbook;
use App\Helpers\FlipbookPDFHelper;
use App\Models\Account\Connection;
use Illuminate\Support\Facades\DB;
use App\Models\TokenTransactionLog;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Flipbook\FlipbookSell;
use Illuminate\Support\Facades\Storage;
use App\Models\Flipbook\FlipbookReviews;
use Illuminate\Support\Facades\Validator;
use App\Models\Flipbook\FlipbookCategories;
use App\Models\Flipbook\FlipbookCollection;
use App\Models\Flipbook\FlipbookPublication;
use App\Http\Requests\Flipbook\SellPublicationRequest;
use App\Http\Requests\Flipbook\GetPublicationListRequest;
use App\Http\Requests\Flipbook\PurchasePublicationRequest;
use App\Http\Requests\Flipbook\UpdatePublicationListRequest;
use App\Http\Requests\Flipbook\AddFlipbookPublicationRequest;
use App\Http\Requests\Flipbook\FlipbookPublicationListRequest;
use App\Http\Requests\Flipbook\PurchasePublicationListRequest;
// use Imagick;

class FlipbookPublicationController extends Controller
{
  public function publish(AddFlipbookPublicationRequest $request)
  {
    $user = $request->attributes->get('user');
    try {
      DB::beginTransaction();
      $flipbook_id = $request->flipbook_id;
      $visibility = $request->visibility;
      $title = $request->title;
      $description = $request->description;
      $status = "1";

      $safe_mode = $request->safe_mode;
      $categories = $request->categories;

      $flipbook = Flipbook::where("id", $flipbook_id)->first();
      $collection_id = $flipbook->collection_id ?: null;
      $collection = null;
      if ($collection_id) {
        $collection = FlipbookCollection::where("id", $collection_id)->first();
      }

      $flipbookPublication = FlipbookPublication::where("user_id", $user->id)->where("flipbook_id", $flipbook_id)->first();
      if (!$flipbookPublication) {
        $flipbookPublication = new FlipbookPublication();
        $flipbookPublication->user_id = $user->id;
        $flipbookPublication->flipbook_id = $flipbook_id;
      }

      if ($flipbookPublication->status == "2") {
        $status = "2";
      }

      $flipbookPublication->safe_mode = $safe_mode;
      $flipbookPublication->categories = $categories;
      $flipbookPublication->status = $status;
      $flipbookPublication->description = $description;
      $flipbookPublication->collection_id = $collection_id;
      $flipbookPublication->title = $title;
      $flipbookPublication->visibility = $visibility;
      $flipbookPublication->save();

      addNotification($user->id, $user->id, "You have Published " . $flipbookPublication->title . "", "", null, "16", "/home", null);

      DB::commit();

      $flipbook->pdf_file = getFileTemporaryURL($flipbook->pdf_file);
      $flipbook->thumbnail = $flipbook->thumbnail ? getFileTemporaryURL($flipbook->thumbnail) : null;

      if ($collection) {
        $collection->thumbnail = $collection->thumbnail ? getFileTemporaryURL($collection->thumbnail) : null;
        $collection = $collection->toArray();
      }

      $data = [
        "publication" => $flipbookPublication->toArray(),
        "flipbook" => $flipbook->toArray(),
        "collection" => $collection,
      ];

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Flipbook published successfully', 'toast' => true], $data);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('PDF upload error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function getPublication(GetPublicationListRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      return $this->getPublicationData($user, $request, true);
    } catch (\Exception $e) {
      Log::error('FlipbookPublicationController getPublication List error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getPublicationSellList(FlipbookPublicationListRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      return $this->getPublicationData($user, $request, false, [], true);
    } catch (\Exception $e) {
      Log::error('FlipbookPublicationController getPublicationSellList List error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  private function getPublicationData($user, $request, $owner = true, $publicationIds = [], $isSell = false)
  {
    $limit = isset($request->limit) ? $request->limit : 10;
    $page = isset($request->page) ? $request->page : 1;
    $order_by = isset($request->order_by) ? "fb" . $request->order_by : "updated_at";
    $order = isset($request->order) ? $request->order : "desc";
    $status = isset($request->status) ? $request->status : null;
    $search_keyword = isset($request->search_keyword) ? $request->search_keyword : "";
    $offset = ($page - 1) * $limit;

    $flipbookPublicationQuery = DB::table("flipbook_publications as fp");
    if ($owner) {
      $flipbookPublicationQuery->where("fp.user_id", $user->id);
    } else if ($publicationIds) {
      $flipbookPublicationQuery->whereIn("fp.id", $publicationIds);
      $flipbookPublicationQuery->whereNot("fp.user_id", $user->id);
    } else if ($isSell) {
      $flipbookPublicationQuery->whereNot("fp.user_id", $user->id);
    }
    $flipbookPublicationQuery->whereNull("fp.deleted_at");
    $flipbookPublicationQuery->whereNull("fc.deleted_at");
    $flipbookPublicationQuery->whereNull("f.deleted_at");
    $flipbookPublicationQuery->leftJoin("flipbooks as f", 'fp.flipbook_id', '=', 'f.id');
    $flipbookPublicationQuery->leftJoin("flipbook_collections as fc", 'fp.collection_id', '=', 'fc.id');
    $flipbookPublicationQuery->orderBy($order_by, $order);
    $flipbookPublicationQuery->limit($limit)->offset($offset);

    $allPublicationCountQuery = $searchPublicationCountQuery = $flipbookPublicationQuery;

    if ($isSell) {
      $status = "2";
    }
    if ($status) {
      $flipbookPublicationQuery->where("status", $status);
      $allPublicationCountQuery->where("status", $status);
      $searchPublicationCountQuery->where("status", $status);
    }
    if ($search_keyword) {
      $flipbookPublicationQuery->where(function ($query) use ($search_keyword) {
        $query->where("fp.title", "like", "%{$search_keyword}%")->orWhere("fp.description", "like", "%{$search_keyword}%");
      });
      $searchPublicationCountQuery->where(function ($query) use ($search_keyword) {
        $query->where("fp.title", "like", "%{$search_keyword}%")->orWhere("fp.description", "like", "%{$search_keyword}%");
      });
    }
    $flipbookPublicationQuery->selectRaw("fp.*,f.thumbnail,f.pdf_file,fc.thumbnail as collection_thumbnail,fc.slug,fc.collection_name,f.id as flipbook_id");
    $list = $flipbookPublicationQuery->get();

    $result = [];
    foreach ($list as $pdf) {
      $pdfPath = $pdf->pdf_file;
      $pdfTemporaryUrl = $isSell ? null : getFileTemporaryURL($pdfPath);
      $download_path = $isSell ? null : config("app.url") . "file-download/" . $pdfPath;
      $thumbnailUrl = $pdf->thumbnail ? getFileTemporaryURL($pdf->thumbnail) : null;
      $collectionThumbnailUrl = $pdf->collection_thumbnail ? getFileTemporaryURL($pdf->collection_thumbnail) : null;

      $fileName = basename($pdfPath);

      $fileSize = Storage::size($pdfPath);
      $fileSizeKB = round($fileSize / 1024, 1);
      $createdAtHumanReadable = Carbon::parse($pdf->created_at)->diffForHumans();
      $createdAtExact = Carbon::parse($pdf->created_at)->format('Y-m-d');

      $publicationStatusArray = ["Inactive", "Published", "Selling"];

      $result[] = [
        'id' => $pdf->id,
        'pdf_file' => $pdfTemporaryUrl,
        'thumbnail' => $thumbnailUrl,
        'pdf_file_name' => $fileName,
        'pdf_file_size_kb' => "{$fileSizeKB} KB | PDF",
        'created_at' => "created $createdAtHumanReadable",
        'created_at_exact' => "dated $createdAtExact",
        'download_path' => $download_path,
        'title' => $pdf->title,
        'status' => $pdf->status,
        'statusText' => $publicationStatusArray[$pdf->status],
        'description' => $pdf->description,
        'collection_thumbnail' => $collectionThumbnailUrl,
        'collection_name' => $pdf->collection_name ?: null,
        'flipbook_id' => $pdf->flipbook_id,
      ];
    }

    if ($result) {
      $sellCount = null;
      if ($owner) {
        $sellCount = FlipbookPDFHelper::getUserSellCount($user->id);
      }
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Flipbook publication data retrieved successfully', 'toast' => true], ['list' => $result, "allPublicationsCount" => $allPublicationCountQuery->count(), "searchPublicationsCount" => $searchPublicationCountQuery->count(), "sellCount" => $sellCount]);
    } else {
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No flipbook publication data found', 'toast' => true]);
    }
  }
  public function updatePublication(UpdatePublicationListRequest $request)
  {
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $publication_id = $request->publication_id;
      $publication = FlipbookPublication::where('user_id', $user->id)
        ->where("id", $publication_id)
        ->first();

      if (!$publication) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Publication not found', 'toast' => true]);
      }

      if (isset($request->status)) {
        $publication->status = $request->status;
      }
      if (isset($request->title)) {
        $publication->title = $request->title;
      }
      if (isset($request->description)) {
        $publication->description = $request->description;
      }
      if (isset($request->visibility)) {
        $publication->visibility = $request->visibility;
      }
      if (isset($request->safe_mode)) {
        $publication->safe_mode = $request->safe_mode;
      }
      if (isset($request->categories)) {
        $publication->categories = $request->categories;
      }

      if (isset($request->flipbook_id)) {
        $flipbook = Flipbook::where("id", $request->flipbook_id)->first();
        if ($flipbook) {
          $publication->collection_id = $flipbook->collection_id;
        }
      }

      $publication->save();
      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Publication updated successfully', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Update publication error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function getPublicationList(FlipbookPublicationListRequest $request)
  {
    try {
      $user = $request->attributes->get('user');

      $followings = $user->profile->following ? json_decode($user->profile->following, true) : [];

      $followingsPublications = FlipbookPublication::where("visibility", '1')->where("status", "1")->whereIn("user_id", $followings)->pluck('id');
      $followingsPublications = $followingsPublications->toArray();
      $connections = Connection::where('status', '1')->where(function ($query) use ($user) {
        $query->where('user_1_id', $user->id)
          ->orWhere('user_2_id', $user->id);
      })->selectRaw("CASE WHEN user_1_id = {$user->id} THEN user_2_id ELSE user_1_id END as connected_user")->pluck("connected_user");
      $connectionsPublications = FlipbookPublication::where("visibility", '2')->where("status", "1")->whereIn("user_id", $connections->toArray())->pluck('id');
      $publicationIds = [];
      $connectionsPublications = $connectionsPublications->toArray();
      if ($followingsPublications || $connectionsPublications) {
        $publicationIds = array_merge($connectionsPublications, $followingsPublications);
      }

      if ($publicationIds)
        return $this->getPublicationData($user, $request, false, $publicationIds);
      else
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No publication data found', 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('getPublicationList publication error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function sellPublication(SellPublicationRequest $request)
  {
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $flipbook_id = $request->flipbook_id;
      $price = $request->price;
      $currency = isset($request->currency) ?: "usd";
      $pages = isset($request->pages) ? json_encode($request->pages) : null;
      $free_access_code = $request->free_access_code;
      $status = "2";
      $user_quantity = $request->user_quantity;
      $flipbookPublication = FlipbookPublication::where("user_id", $user->id)->where("flipbook_id", $flipbook_id)->first();

      if (!$flipbookPublication) {
        $sellCount = FlipbookPDFHelper::getUserSellCount($user->id);
        if (!$sellCount['remaining_count']) {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Free/Plan sell count exceeded', 'toast' => true]);
        }
      }
      $preview_file_path = null;
      $flipbook = Flipbook::where("id", $flipbook_id)->first();
      if ($request->hasFile('preview_link')) {
        $preview_file = $request->file('preview_link');
        $fileName = $preview_file->getClientOriginalName();
        $preview_file_path = "users/private/{$user->id}/flipbook/pdfs/sell_preview/{$fileName}";
        Storage::put($preview_file_path, file_get_contents($preview_file));
      }
      $flipbookPublication->status = $status;
      $flipbookPublication->price = $price;
      $flipbookPublication->currency = $currency;
      $flipbookPublication->pages = $pages;
      $flipbookPublication->preview_link = $preview_file_path;
      $flipbookPublication->free_access_code = $free_access_code;
      $flipbookPublication->user_quantity = $user_quantity;
      $flipbookPublication->save();
      addNotification($user->id, $user->id, "You have listed " . $flipbookPublication->title . " for sale on the marketplace.", "", null, "16", "/browse", null);

      DB::commit();
      $flipbook->pdf_file = getFileTemporaryURL($flipbook->pdf_file);
      $flipbook->thumbnail = $flipbook->thumbnail ? getFileTemporaryURL($flipbook->thumbnail) : null;


      $data = [
        "publication" => $flipbookPublication->toArray(),
        "flipbook" => $flipbook->toArray(),
      ];

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Flipbook published successfully', 'toast' => true], $data);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('sellPublication publication error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function purchasePublication(PurchasePublicationRequest $request)
  {
    try {
      DB::beginTransaction();

      $user = $request->attributes->get('user');
      $publication_id = $request->publication_id;

      $alreadyBoughtPublications = FlipbookSell::where('publication_id', $publication_id)->where("buyer_id", $user->id)->count();
      if ($alreadyBoughtPublications)
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Already bought publications', 'toast' => true]);


      $publication = FlipbookPublication::where("id", $publication_id)->first();
      $flipbook = Flipbook::where("id", $publication->flipbook_id)->first();

      $publication_price = $publication->price;
      $publication_auger_price = $publication_price * config("app.auger_fee") / 100;
      $publication_total_price = $publication_price + $publication_auger_price;

      if (!balanceValidations($user->id, $publication_price))
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Insufficient token balance', 'toast' => true]);

      $live_token_value = getTokenMetricsValues();
      $publication_token_price = $publication_price / $live_token_value;
      $publication_auger_token_price = $publication_auger_price / $live_token_value;


      $seller_user = User::where("id", $publication->user_id)->first();
      if (!$seller_user)
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Seller user not found', 'toast' => true]);
      $particulars = ucfirst($publication->title) . " flipbook purchase";
      $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();
      $transaction_id = makeTransaction($user, $seller_user, $publication_token_price, $particulars, "4", "3", $lastTokenTransactionLog);
      $admin_user = User::where("role_id", "2")->orderBy("id", "asc")->first();
      $auger_transaction_id = null;
      if ($admin_user)
        $auger_transaction_id = makeTransaction($user, $admin_user, $publication_auger_token_price, "Auger Fee: " . $particulars, "5", "3", $lastTokenTransactionLog, $transaction_id);

      //add sell history

      $flipbookSell = new FlipbookSell();
      $flipbookSell->seller_id = $publication->user_id;
      $flipbookSell->buyer_id = $user->id;
      $flipbookSell->publication_id = $publication->id;
      $flipbookSell->flipbook_id = $flipbook->id;
      $flipbookSell->price = $publication->price;
      $flipbookSell->pages = $publication->pages;
      $flipbookSell->thumbnail_path = $publication->thumbnail;
      $flipbookSell->path = $flipbook->pdf_file;
      $flipbookSell->currency = $publication->currency;
      $flipbookSell->transaction_id = $transaction_id;
      $flipbookSell->auger_transaction_id = $auger_transaction_id;
      $flipbookSell->save();

      DB::commit();

      // createNotification

      $description = $title = $publication->title . " Purchase";
      addNotification($publication->user_id, $user->id, $title, $description, $flipbookSell->id, "16", "#");
      addNotification($user->id, $publication->user_id, $title, $description, $flipbookSell->id, "16", "#");

      $txnLog = TokenTransactionLog::where("id", $transaction_id)->first();
      sendTransactionMail(
        $txnLog,
        null,
        'Thank you for your purchasing of Flipbook Publication: ' . $publication->title,
      );
      $augerTxnLog = TokenTransactionLog::where("id", $auger_transaction_id)->first();
      sendTransactionMail(
        $augerTxnLog
      );

      $updatedUser = User::where('id', $user->id)->first();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Flipbook publication purchased successfully', 'toast' => true], ["account_tokens" => $updatedUser->account_tokens, "transaction_id" => $transaction_id, "auger_transaction_id" => $auger_transaction_id]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('purchasePublication publication error: ' . $e->getMessage() . " line no " . $e->getLine() . " " . $e->getFile());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getPublicationPurchaseList(PurchasePublicationListRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      return $this->getSellList($user, $request, "buyer");
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('purchasePublication publication error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getPublicationSoldList(PurchasePublicationListRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      return $this->getSellList($user, $request, "seller");
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('purchasePublication publication error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  private function getSellList($user, $request, $type = "seller")
  {
    $limit = isset($request->limit) ? $request->limit : 10;
    $page = isset($request->page) ? $request->page : 1;
    $order_by = isset($request->order_by) ? $request->order_by : "updated_at";

    $fs_arr = ['currency', "price", "transaction_id", "auger_transaction_id", "seller_id", "buyer_id", "created_at", "updated_at"];
    $p_arr = ['title', "description"];

    if (in_array($order_by, $fs_arr))
      $order_by = "fs." . $order_by;
    elseif (in_array($order_by, $p_arr))
      $order_by = "p." . $order_by;

    $order = isset($request->order) ? $request->order : "desc";
    $status = isset($request->status) ? $request->status : null;
    $search_keyword = isset($request->search_keyword) ? $request->search_keyword : "";
    $offset = ($page - 1) * $limit;

    $flipbookSellListQuery = DB::table('flipbook_sells as fs');

    $flipbookSellListQuery->leftJoin("users as seller", "fs.seller_id", "=", "seller.id");
    $flipbookSellListQuery->leftJoin("users as buyer", "fs.buyer_id", "=", "buyer.id");
    $flipbookSellListQuery->leftJoin("flipbook_publications as p", "fs.publication_id", "=", "p.id");
    $flipbookSellListQuery->leftJoin("flipbooks as f", "fs.flipbook_id", "=", "f.id");

    $flipbookSellListQuery->selectRaw("fs.*,seller.username as seller_name,buyer.username as buyer_name");

    $flipbookSellListQuery->limit($limit)->offset($offset);
    $flipbookSellListQuery->orderBy($order_by, $order);

    if ($type == "seller") {
      $flipbookSellListQuery->where("fs.seller_id", $user->id);
    } else {
      $flipbookSellListQuery->where("fs.buyer_id", $user->id);
    }

    if ($search_keyword) {
      $flipbookSellListQuery->where(function ($query) use ($search_keyword) {
        $query->where("seller.username", "like", "%$search_keyword%");
        $query->orWhere("buyer.username", "like", "%$search_keyword%");
        $query->orWhere("seller.email", "like", "%$search_keyword%");
        $query->orWhere("buyer.email", "like", "%$search_keyword%");
        $query->orWhere("f.title", "like", "%$search_keyword%");
        $query->orWhere("f.description", "like", "%$search_keyword%");
        $query->orWhere("fs.price", "like", "%$search_keyword%");
      });
    }

    $sellList = $flipbookSellListQuery->get();

    if ($sellList->isNotEmpty()) {
      $sellList->transform(function ($item) {
        $item->path = getFileTemporaryURL($item->path);
        $item->thumbnail_path = getFileTemporaryURL($item->thumbnail_path);
        return $item;
      });
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Purchase history found', 'toast' => true], ["list" => $sellList->toArray()]);
    } else {
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No purchase history found', 'toast' => true]);
    }
  }
  public function deletePublication(Request $request, $id)
  {
    try {
      $user = $request->attributes->get('user');
      $validator = Validator::make(['id' => $id], [
        'id' => ['required', Rule::exists("flipbook_publications", "id")->whereNull("deleted_at")->where("user_id", $user->id)],
      ]);
      if ($validator->fails()) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid Flipbook Publications ID', 'toast' => true]);
      }
      DB::beginTransaction();

      $flipbook_publications = FlipbookPublication::where("id", $id)->where("user_id", $user->id)->first();
      if (!$flipbook_publications) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => "Flipbook Publication not found",
          'toast' => true
        ]);
      }

      $isFlipbookSold = FlipbookSell::where("publication_id", $id)->count();
      if ($isFlipbookSold)
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => "Can't delete sold Flipbook Publication",
          'toast' => true
        ]);

      $flipbook_publications->delete();
      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Publication deleted successfully', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('deletePublication publication error: ' . $e->getMessage() . " line no " . $e->getLine() . " " . $e->getFile());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  //Profile APi
  public function publicationProfile(Request $request)
  {
    try {
      if ($request->has('flipbook_id')) {

        $flipbook_id = $request->input('flipbook_id');
        $authToken = $request->header('authToken');
        $token_data = "";
        if ($authToken) {
          $decoded = JWT::decode($authToken, new Key(config('app.enc_key'), 'HS256'));
          $cloudUserId = $decoded->cloudUserId;
          $user = User::find($cloudUserId);
          $token_data = $this->getuserTokenData($user, true);
        }
        $flipbookPublication = FlipbookPublication::where('flipbook_id', $flipbook_id)
          ->where('status', "1")
          ->first();

        if (!$flipbookPublication) {
          return generateResponse([
            'type' => 'error',
            'code' => 404,
            'status' => false,
            'message' => 'Publication not found or not for sale',
            'toast' => true,
          ]);
        }
        $user_id = $flipbookPublication->user_id;
        $userData = User::find($user_id);

        $publication_category = $flipbookPublication->categories;
        $publication_id = $flipbookPublication->id;
        $related_books = [];
        $books = FlipbookPublication::where('id', '!=', $publication_id)->where('categories', $publication_category)->inRandomOrder()->limit(3)->get();
        foreach ($books as $book) {
          $flipbook_id = $book->flipbook_id;
          $related_flipbook = Flipbook::where('id', $flipbook_id)->first();
          $related_books[] = [
            'publication_id' => $book->id,
            'flipbook_id' => $flipbook_id,
            'book_title' => $book->title,
            'book_description' => $book->description,
            'book_thumbnail' => getFileTemporaryURL($related_flipbook->thumbnail),
            'published_at' => 'Published at ' . Carbon::parse($book->created_at)->diffForHumans(),
          ];
        }

        if (!$userData) {
          return generateResponse([
            'type' => 'error',
            'code' => 404,
            'status' => false,
            'message' => 'User not found',
            'toast' => true,
          ]);
        }

        $username = $userData->username;
        $userProfileData = UserProfile::where('user_id', $user_id)->first();
        if ($userProfileData && $userProfileData->profile_image_path) {
          $profile_image_path = getFileTemporaryURL($userProfileData->profile_image_path);
        } else {
          $profile_image_path = null;
        }
        //$profile_image_path = $userProfileData->profile_image_path ? getFileTemporaryURL($userProfileData->profile_image_path) : null;
        $flipbook = Flipbook::find($flipbookPublication->flipbook_id);
        $pdfFileUrl = getFileTemporaryURL($flipbook->pdf_file);
        $thumbnailUrl = getFileTemporaryURL($flipbook->thumbnail);
        $flipbookPublication_title = $flipbookPublication->title;
        $flipbookPublication_desc = $flipbookPublication->description ?? null;
        $flipbookPublication_date = $flipbookPublication->created_at;
        $flipbookPublication_id = $flipbookPublication->id;
        $flipbookPublication_category = $flipbookPublication->categories;
        $flipbookPublication_visibility = $flipbookPublication->visibility;
        $flipbookPublication_safemode = $flipbookPublication->safe_mode;
        $flipbookPublication_price = $flipbookPublication->price;
        $userPublicationsForSale = FlipbookPublication::where('user_id', $flipbookPublication->user_id)
          ->where('status', "1")
          ->get();

        $publicationsForSaleData = [];
        foreach ($userPublicationsForSale as $pub) {
          if ($pub->flipbook_id == $flipbook_id) {
            continue;
          }

          $flipbookForSale = Flipbook::find($pub->flipbook_id);

          $publicationsForSaleData[] = [
            'publication_id' => $pub->id,
            'flipbook_id' => $pub->flipbook_id,
            'title' => $pub->title,
            'published_date' => Carbon::parse($pub->created_at)->format('d F Y'),
            'thumbnail' => getFileTemporaryURL($flipbookForSale->thumbnail),
          ];
        }

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Publication found and for sale',
          'toast' => true,
        ], ['token_data' => $token_data, 'publisher_data' => [
          'username' => $username,
          'publication_id' => $flipbookPublication_id,
          'profile_image_path' => $profile_image_path,
          'pdf_file' => $pdfFileUrl,
          'thumbnail' => $thumbnailUrl,
          'title' => $flipbookPublication_title,
          'published_date' => Carbon::parse($flipbookPublication_date)->format('d F Y'),
          'description' => $flipbookPublication_desc,
          'price' => $flipbookPublication_price,
          'publications_for_sale' => $publicationsForSaleData,
          'related_books' => $related_books,
          'category' => $flipbookPublication_category,
          'visibility' => $flipbookPublication_visibility,
          'safe_mode' => $flipbookPublication_safemode,
        ],]);
      } else {
        $username_input = $request->input('username');
        $userData = User::where('username', 'LIKE', '%' . $username_input)->first();

        if (!$userData) {
          return generateResponse([
            'type' => 'error',
            'code' => 404,
            'status' => false,
            'message' => 'User not found',
            'toast' => true,
          ]);
        }

        $user_id = $userData->id;
        $username = $userData->username;
        $userProfileData = UserProfile::where('user_id', $user_id)->first();
        $profile_image_path = $userProfileData->profile_image_path ? getFileTemporaryURL($userProfileData->profile_image_path) : null;

        $flipbookPublications = FlipbookPublication::where('user_id', $user_id)->get();
        $publicationCount = $flipbookPublications->count();

        $publicationData = [];
        foreach ($flipbookPublications as $publication) {
          $flipbook = Flipbook::find($publication->flipbook_id);

          $formattedCreatedAt = Carbon::parse($publication->created_at)->format('d F Y');
          $publicationData[] = [
            'publication_id' => $publication->id,
            'title' => $publication->title,
            'visibility' => $publication->visibility,
            'description' => $publication->description,
            'price' => $publication->price,
            'currency' => $publication->currency,
            'status' => $publication->status,
            'pages' => $publication->pages,
            'published_date' => $formattedCreatedAt,
            'created_at' => $publication->created_at,
            'updated_at' => $publication->updated_at,
            'flipbook' => [
              'pdf_file' => getFileTemporaryURL($flipbook->pdf_file),
              'thumbnail' => getFileTemporaryURL($flipbook->thumbnail),
              'flipbook_id' => $flipbook->id
            ],
          ];
        }

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Data retrieved successfully',
          'toast' => true,
          'data' => [
            'username' => $username,
            'profile_image_path' => $profile_image_path,
            'publication_count' => $publicationCount,
            'publications' => $publicationData,
          ],
        ]);
      }
    } catch (\Exception $e) {
      Log::error($e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching data: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function searchPublication(Request $request)
  {
    try {
      $searchTerm = $request->query('search', '');
      $safeMode = $request->query('safe_mode', null);
      $categoryFilter = $request->query('category', null);
      $dateFilters = $request->query('date', null);
      $rateFilters = $request->query('ratings', null);

      $query = FlipbookPublication::with('flipbook')
        ->leftJoin('flipbook_reviews', 'flipbook_publications.id', '=', 'flipbook_reviews.publication_id')
        ->whereIn('status', ["1"])
        ->where('title', 'like', '%' . $searchTerm . '%')
        ->where('visibility', '1');

      if (!is_null($safeMode)) {
        $query->where('safe_mode', $safeMode);
      }

      if (!is_null($categoryFilter)) {
        $categoryIds = explode(',', $categoryFilter);
        $query->where(function ($query) use ($categoryIds) {
          foreach ($categoryIds as $categoryId) {
            $query->orWhereRaw('FIND_IN_SET(?, categories)', [$categoryId]);
          }
        });
      }
      if (!is_null($dateFilters)) {
        $now = now();
        $dates = explode(',', $dateFilters);

        $query->where(function ($query) use ($dates, $now) {
          foreach ($dates as $dateFilter) {
            if ($dateFilter === 'last_five_days') {
              $startDate = $now->copy()->subDays(5)->startOfDay();
              $endDate = $now->copy()->endOfDay();
            } elseif ($dateFilter === 'last_sixty_days') {
              $startDate = $now->copy()->subDays(60)->startOfDay();
              $endDate = $now->copy()->endOfDay();
            } elseif ($dateFilter === 'last_ninety_days') {
              $startDate = $now->copy()->subDays(90)->startOfDay();
              $endDate = $now->copy()->endOfDay();
            } elseif ($dateFilter === 'last_years') {
              $startDate = $now->copy()->subYears(1)->startOfDay();
              $endDate = $now->copy()->endOfDay();
            } elseif ($dateFilter === 'last_five_years') {
              $startDate = $now->copy()->subYears(5)->startOfDay();
              $endDate = $now->copy()->endOfDay();
            }

            $query->orWhereBetween('flipbook_publications.created_at', [$startDate, $endDate]);
          }
        });
      }

      if (!is_null($rateFilters)) {
        $rates = explode(',', $rateFilters);
        $query->where(function ($query) use ($rates) {
          foreach ($rates as $rate) {
            $rate = (float) $rate;
            $query->orWhereBetween('flipbook_reviews.average', [$rate - 0.5, $rate + 0.5]);
          }
        });
      }

      $contentData = $query->orderBy('flipbook_publications.created_at', 'DESC')
        ->get(['title', 'description', 'flipbook_id', 'user_id', 'flipbook_publications.created_at', 'categories', 'price', 'currency', 'safe_mode'])
        ->map(function ($publication) {
          $user = User::find($publication->user_id);
          $profile = UserProfile::where('user_id', $publication->user_id)->first(['profile_image_path']);

          $categoryIds = explode(',', $publication->categories);
          $categories = FlipbookCategories::whereIn('id', $categoryIds)->pluck('label');

          return [
            'title' => $publication->title,
            'description' => $publication->description,
            'flipbook_id' => optional($publication->flipbook)->id,
            'thumbnail' => optional($publication->flipbook)->thumbnail ? getFileTemporaryURL($publication->flipbook->thumbnail) : null,
            'user_id' => $publication->user_id,
            'username' => $user ? $user->username : null,
            'profile_image' => $profile ? getFileTemporaryURL($profile->profile_image_path) : null,
            'publish_date' => $publication->created_at->format('F j, Y'),
            'categories' => $categories,
            'price' => $publication->price,
            'currency' => $publication->currency,
            'safe_mode' => $publication->safe_mode,
          ];
        });

      return generateResponse([
        'type' => 'success',
        'status' => true,
        'code' => 200,
        'message' => $contentData->isEmpty() ? 'No data found' : 'Data retrieved successfully',
        'data' => [
          'content' => $contentData
        ],
        'toast' => true
      ]);
    } catch (\Exception $e) {
      Log::info("Error fetching data: {$e->getMessage()} on line {$e->getLine()}");
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching data',
        'data' => [],
        'toast' => true,
      ]);
    }
  }
  public function getPublicationDetails(Request $request, $publication_id)
  {
    try {
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Get Publication failed failed: ' . $e->getMessage(),
        'data' => [],
        'toast' => true,
      ]);
    }
  }
  protected function getuserTokenData($user, $isUserObject)
  {
    $token_value = getTokenMetricsValues();
    $auger_fee_percentage = config("app.auger_fee");
    $token_data = ['available_tokens' => 0, "token_value" => $token_value, "auger_fee_percent" => $auger_fee_percentage];
    if ($isUserObject) {
      $token_data['available_tokens'] = $user->account_tokens;
    } else {
      $user = User::where('id', $user)->first();
      if ($user) {
        $token_data['available_tokens'] = $user->account_tokens;
      }
    }
    return $token_data;
  }
  public function getFlipbookCategories()
  {
    try {
      $categories = FlipbookCategories::all(['id', 'label', 'value']);
      return generateResponse([
        'type' => 'success',
        'status' => true,
        'code' => 200,
        'message' => 'categories fetched successfully',
        'toast' => false,
      ], ['categories' => $categories]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Get Flipbook Categories failed: ' . $e->getMessage(),
        'data' => [],
        'toast' => true,
      ]);
    }
  }
  public function addflipbookReviews(Request $request)
  {
    try {
      $publication_id = $request->input('publication_id');
      $user = $request->attributes->get('user');
      $rating = $request->input('rating');
      $comment = $request->input('comment');

      if ($rating < 1 || $rating > 5) {
        return generateResponse([
          'type' => 'error',
          'code' => 400,
          'status' => false,
          'message' => 'Rating must be between 1 and 5.',
          'toast' => true,
        ]);
      }

      $flipbookReview = FlipbookReviews::where('publication_id', $publication_id)->first();

      if (!$flipbookReview) {
        $flipbookReview = new FlipbookReviews();
        $flipbookReview->publication_id = $publication_id;
        $flipbookReview->average = $rating;
        $data = [
          [
            'sr_no' => 1,
            'publication_id' => $publication_id,
            'user_id' => $user->id,
            'rating' => $rating,
            'comment' => $comment,
            'added_at' => date("M j, Y")
          ]
        ];
        $flipbookReview->data = json_encode($data);
        $flipbookReview->save();
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Flipbook review added successfully',
          'toast' => true,
        ]);
      }

      $existingData = json_decode($flipbookReview->data, true);
      $lastEntry = end($existingData);
      $sr_no = $lastEntry['sr_no'] + 1;
      $newData = [
        'sr_no' => $sr_no,
        'publication_id' => $publication_id,
        'user_id' => $user->id,
        'rating' => $rating,
        'comment' => $comment,
        'added_at' => date("M j, Y")
      ];
      $existingData[] = $newData;

      $flipbookReview->data = json_encode($existingData);

      $totalReviews = count($existingData);
      $currentAverage = $flipbookReview->average;
      $newAverage = ($currentAverage * ($totalReviews - 1) + $rating) / $totalReviews;

      $flipbookReview->average = $newAverage;
      $flipbookReview->save();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Flipbook review added successfully',
        'toast' => true,
      ]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error Adding Flipbook reviews: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }
  public function getFlipbookReviews(Request $request)
  {
    try {
      $publication_id = $request->get('publication_id');

      $flipbookReview = FlipbookReviews::where('publication_id', $publication_id)->first();
      // dd($flipbookReview);
      if (!$flipbookReview) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'No reviews found for this publication.',
          'toast' => true,
        ]);
      }

      $existingData = json_decode($flipbookReview->data, true);

      $response = [
        'average_rating' => $flipbookReview->average,
        'reviews' => []
      ];

      foreach ($existingData as $review) {
        $user = User::with('profile')->find($review['user_id']);
        if ($user) {
          $response['reviews'][] = [
            'sr_no' => $review['sr_no'],
            'rating' => $review['rating'],
            'comment' => $review['comment'],
            'username' => $user->username,
            'added_at' => $review['added_at'],
            'profile_image' => asset('assets/images/default_profile/profile.png'),
          ];
        }
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Flipbook review get successfully',
        'toast' => true,
      ], ['review_data' => $response,]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching Flipbook reviews: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }
}
