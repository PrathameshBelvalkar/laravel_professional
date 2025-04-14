<?php

namespace App\Http\Controllers\API\V1\Marketplace;

use App\Http\Requests\Marketplace\ManageCartRequest;
use App\Models\Marketplace\MarketplaceStore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProducts;
use App\Models\MarketplaceUserCart;
use App\Models\StoreProductReviews;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\StoreProductQuestions;
use App\Models\MarketplaceSiteSetting;
use App\Models\MarketplaceSubCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Requests\Marketplace\AddProductRequest;
use App\Http\Requests\Marketplace\UpdateProductRequest;
use App\Http\Requests\Marketplace\StoreSliderRequest;
use App\Http\Requests\Marketplace\StorePaidBannerRequest;
use Illuminate\Support\Facades\URL;
use App\Models\Marketplace\MarketplaceSlider;
use App\Models\Marketplace\PaidBannerDisplay;
use App\Models\Marketplace\MerchantShipper;
use App\Models\Marketplace\MarketplaceStoreUserPermission;
use App\Models\Order;
use App\Models\TokenRequest;
use Illuminate\Support\Str;
use App\Models\Marketplace\MarketplaceProductPurchaseDetail;
use App\Models\Marketplace\MarketplaceProductOrderLogDetail;
use App\Models\Marketplace\MarketplaceSiteSubscription;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use Dompdf\Dompdf;
use Dompdf\Options;
use Maatwebsite\Excel\Facades\Excel;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\File;
use App\Models\Country;
use App\Models\UserProfile;
use App\Exports\ExportMplace;
use App\Notifications\OrderUpdateNotification;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\TokenTransactionLog;

class MarketplaceController extends Controller
{
  public function addProducts(AddProductRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $store_id = $request->store_id;
      $isStoreOwner = isStoreOwner($store_id, $user);
      if (!$isStoreOwner['status']) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => $isStoreOwner['message'], 'toast' => true]);
      }

      $store = MarketplaceStore::find($store_id);
      $productCount = MarketplaceProducts::where('store_id', $store_id)->count();
      $productLimit = (int) $store->product_limit;


      if ($productCount >= $productLimit) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Store has reached its product limit.', 'toast' => true]);
      }

      $existingProduct = MarketplaceProducts::where('store_id', $store_id)
        ->where('product_name', $request->product_name)
        ->first();

      if ($existingProduct) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product already exists in this store.', 'toast' => true]);
      }

      $product = new MarketplaceProducts();
      $product->user_id = $user->id;
      $product->product_name = $request->product_name;
      $productSlug = generateUniqueSlug("MarketplaceProducts", "product_name", $request->product_name);
      $product->price = $request->price;
      $product->discount_percentage = $request->discount_percentage;
      $product->paid_price = $request->paid_price;
      $product->features = $request->features ?? '';
      $product->description = $request->description;
      $product->is_accessory = $request->is_accessory;
      $product->category_id = $request->category_id;
      $product->sub_category_id = $request->sub_category_id;
      $product->product_color = $request->product_color;
      $product->brand_name = $request->brand_name;
      $product->store_id = $request->store_id;
      $product->is_public = $request->is_public ?? 'Y';

      $product->save();
      $product_id = $product->id;

      // Handle chunked video upload
      if ($request->input('is_chunked_video')) {
        $productVideoPath = $this->handleChunkedUpload($request, 'product_video', $user->id, $request->store_id, 'product_video');
        $product->product_video = $productVideoPath;
      } elseif ($request->hasFile('product_video')) {
        $file = $request->file('product_video');
        $fileName = "product_video_" . $productSlug . "." . $file->extension();
        $productVideoPath = "assets/marketplace/{$user->id}/store/{$request->store_id}/product_video/{$fileName}";
        $this->checkAndCreateDirectory($productVideoPath);
        Storage::disk('public_uploads')->put($productVideoPath, file_get_contents($file));
        $product->product_video = $productVideoPath;
      }

      // Handle other files
      if ($request->hasFile('thumbnail')) {
        $file = $request->file('thumbnail');
        $fileName = $file->getClientOriginalName();
        $thumbnailPath = "assets/marketplace/{$user->id}/{$request->category_id}/{$request->sub_category_id}/{$product_id}/thumbnail/{$fileName}";
        $this->checkAndCreateDirectory($thumbnailPath);
        Storage::disk('public_uploads')->put($thumbnailPath, file_get_contents($file));
        $product->thumbnail = $thumbnailPath;
      }

      if ($request->hasFile('product_images')) {
        $productImagesPaths = [];
        foreach ($request->file('product_images') as $file) {
          $fileName = $file->getClientOriginalName();
          $productImagesPath = "assets/marketplace/{$user->id}/{$request->category_id}/{$request->sub_category_id}/{$product_id}/product_images/{$fileName}";
          $this->checkAndCreateDirectory($productImagesPath);
          Storage::disk('public_uploads')->put($productImagesPath, file_get_contents($file));
          $productImagesPaths[] = $productImagesPath;
        }
        $product->product_images = implode(',', $productImagesPaths);
      }

      if ($request->input('is_chunked_image')) {
        // Handle chunked upload for 3D image
        $threedImagePath = $this->handleChunkedUpload($request, 'threed_image', $user->id, $request->store_id, 'threed_image');
        if (!$threedImagePath) {
          throw new \Exception('Error processing 3D image.');
        }
        $product->threed_image = $threedImagePath;
      } elseif ($request->hasFile('threed_image')) {
        $file = $request->file('threed_image');
        $originalName = $file->getClientOriginalName();
        $fileExtension = $file->extension();
        $mimeType = $file->getMimeType();

        if ($fileExtension === 'txt' && $mimeType === 'text/plain') {
          $fileExtension = 'glb';
        } elseif ($fileExtension !== 'glb') {
          $fileExtension = 'glb';
        }

        if ($mimeType === 'model/gltf-binary' || $mimeType === 'application/octet-stream' || ($fileExtension === 'glb' && ($mimeType === 'text/plain' || $mimeType === 'model/gltf-binary'))) {
          $fileName = pathinfo($originalName, PATHINFO_FILENAME) . '.' . $fileExtension;
          $threedImagePath = "assets/marketplace/{$user->id}/{$request->category_id}/{$request->sub_category_id}/{$product_id}/threed_image/{$fileName}";
          $this->checkAndCreateDirectory($threedImagePath);

          Storage::disk('public_uploads')->put($threedImagePath, file_get_contents($file));
          $product->threed_image = $threedImagePath;
        } else {
          throw new \Exception('Invalid file type. Only .glb files are allowed.');
        }
      }



      $product->save();
      DB::commit();
      $username = $user->username;
      $productLink = url('/' . $product_id . '/' . Str::slug($product->product_name));
      $storeOwnerId = $store->user_id; // Assuming store owner is associated with the store
      $referenceId = $product->id; // ID of the newly added product
      addNotification(
        $storeOwnerId,
        $user->id,
        'New Product Added',
        "A new product '{$product->product_name}' has been added  by {$username}.",
        $referenceId,
        '5',
        $productLink,
        $is_admin = null
      );

      $marketplace_products = MarketplaceProducts::where('id', $product->id)->first();
      $product_data = [
        'id' => $marketplace_products->id,
        'user_id' => $marketplace_products->user_id,
        'product_name' => $marketplace_products->product_name,
        'price' => $marketplace_products->price,
        'paid_price' => $marketplace_products->paid_price,
        'discount_percentage' => $marketplace_products->discount_percentage,
        'features' => $marketplace_products->features,
        'description' => $marketplace_products->description,
        'is_accessory' => $marketplace_products->is_accessory,
        'category_id' => $marketplace_products->category_id,
        'sub_category_id' => $marketplace_products->sub_category_id,
        'store_id' => $marketplace_products->store_id,
        'thumbnail' => $marketplace_products->thumbnail,
        'product_images' => explode(',', $marketplace_products->product_images),
        'threed_image' => $marketplace_products->threed_image,
        'product_video' => $marketplace_products->product_video,
        'product_color' => $marketplace_products->product_color,
        'best_seller_count' => $marketplace_products->best_seller_count,
        'brand_name' => $marketplace_products->brand_name,
      ];

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Product added successfully.', 'toast' => true], ['channel' => $product_data]);
    } catch (\Exception $e) {
      // Log::error('Error adding product: ' . $e->getMessage());
      $line = $e->getLine();
      $file = $e->getFile();

      // Log the error message with file and line number
      Log::error('Error adding product in file ' . $file . ' on line ' . $line . ': ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }


  private function checkAndCreateDirectory($path)
  {
    $directory = dirname($path);
    if (!Storage::disk('public_uploads')->exists($directory)) {
      Storage::disk('public_uploads')->makeDirectory($directory);
    }
  }


  private function handleChunkedUpload(Request $request, $fileInputName, $userId, $storeId, $type)
  {
    $fileName = $request->input('fileName');
    $chunkIndex = $request->input('chunkIndex');
    $totalChunks = $request->input('totalChunks');
    $chunk = $request->file('chunk');

    $tempDir = storage_path("app/temp/{$userId}/store/{$storeId}/{$type}");
    if (!file_exists($tempDir)) {
      if (!mkdir($tempDir, 0777, true)) {
        Log::error("Failed to create temporary directory: {$tempDir}");
        throw new \Exception("Failed to create temporary directory: {$tempDir}");
      }
    }

    $chunkFilePath = "{$tempDir}/{$fileName}.part{$chunkIndex}";
    $chunk->move($tempDir, "{$fileName}.part{$chunkIndex}");

    Log::info("Chunk saved: {$chunkFilePath}");

    if ($this->allChunksUploaded($fileName, $totalChunks, $tempDir)) {
      $finalDir = "assets/marketplace/{$userId}/store/{$storeId}/{$type}";
      $finalFilePath = "{$finalDir}/{$fileName}";

      // Ensure the final directory exists
      $this->checkAndCreateDirectory(storage_path("app/{$finalDir}"));

      // Combine chunks
      $this->combineChunks($fileName, $totalChunks, $tempDir, storage_path("app/{$finalFilePath}"));

      if (file_exists(storage_path("app/{$finalFilePath}"))) {
        Log::info("Final file created: " . storage_path("app/{$finalFilePath}"));
        Storage::disk('public_uploads')->put($finalFilePath, file_get_contents(storage_path("app/{$finalFilePath}")));
      } else {
        Log::error("Final file does not exist: " . storage_path("app/{$finalFilePath}"));
      }

      $this->cleanupTempFiles($tempDir);
      return $finalFilePath;
    }

    return null;
  }


  private function cleanupTempFiles($tempDir)
  {
    // Remove temporary files and directory
    $files = glob("{$tempDir}/*");
    foreach ($files as $file) {
      unlink($file);
    }
    rmdir($tempDir);
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

  private function combineChunks($fileName, $totalChunks, $tempDir, $finalFilePath)
  {
    // Ensure the directory for the final file exists
    $finalDir = dirname($finalFilePath);
    if (!is_dir($finalDir)) {
      if (!mkdir($finalDir, 0777, true)) {
        Log::error("Failed to create directory: {$finalDir}");
        return;
      }
    }

    // Open the final file for writing
    $finalFile = fopen($finalFilePath, 'ab');
    if (!$finalFile) {
      Log::error("Failed to open final file for writing: {$finalFilePath}");
      return;
    }

    // Process each chunk file
    for ($i = 0; $i < $totalChunks; $i++) {
      $chunkFilePath = "{$tempDir}/{$fileName}.part{$i}";
      if (!file_exists($chunkFilePath)) {
        Log::error("Chunk file does not exist: {$chunkFilePath}");
        continue; // Skip non-existent chunk
      }

      $chunkFile = fopen($chunkFilePath, 'rb');
      if (!$chunkFile) {
        Log::error("Failed to open chunk file for reading: {$chunkFilePath}");
        continue; // Skip chunk if it cannot be opened
      }

      while ($chunk = fread($chunkFile, 4096)) {
        fwrite($finalFile, $chunk);
      }

      fclose($chunkFile);
      unlink($chunkFilePath); // Delete the chunk after appending to the final file
    }

    fclose($finalFile);
  }




  public function updateProduct(UpdateProductRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $product_id = $request->product_id;

      if (!$product_id) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product id not found.', 'toast' => true, 'data' => []]);
      }

      $product = MarketplaceProducts::where("user_id", $user->id)
        ->where('id', $product_id)
        ->first();

      if (!$product) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product with the provided product_id was not found.', 'toast' => true]);
      }

      $productName = $product->product_name;
      $store_id = $product->store_id;

      if ($request->has('product_name')) {
        $productName = $request->product_name;
        $product->product_name = $request->product_name;
      }

      $productSlug = generateUniqueSlug("MarketplaceProducts", "product_name", $productName);

      if ($request->has('price')) {
        $product->price = $request->price;
      }
      if ($request->has('paid_price')) {
        $product->price = $request->paid_price;
      }

      if ($request->has('features')) {
        $product->features = $request->features;
      }

      if ($request->has('description')) {
        $product->description = $request->description;
      }

      if ($request->has('is_accessory')) {
        $product->is_accessory = $request->is_accessory;
      }

      if ($request->has('stock')) {
        $product->stock = $request->stock;
      }

      if ($request->has('category_id')) {
        $product->category_id = $request->category_id;
      }

      if ($request->has('sub_category_id')) {
        $product->sub_category_id = $request->sub_category_id;
      }

      if ($request->has('store_id')) {
        $product->store_id = $request->store_id;
        $store_id = $request->store_id;
      }

      if ($request->has('product_color')) {
        $product->product_color = $request->product_color;
      }

      if ($request->has('best_seller_count')) {
        $product->best_seller_count = $request->best_seller_count;
      }

      if ($request->has('brand_name')) {
        $product->brand_name = $request->brand_name;
      }

      $product->save();

      // Handle thumbnail upload
      if ($request->hasFile('thumbnail')) {
        $file = $request->file('thumbnail');
        $fileName = $file->getClientOriginalName();
        $thumbnailPath = "assets/marketplace/{$user->id}/{$request->category_id}/{$request->sub_category_id}/{$product->id}/thumbnail/{$fileName}";
        Storage::disk('public_uploads')->put($thumbnailPath, file_get_contents($file));
        $product->thumbnail = $thumbnailPath;
      }

      // Handle product images upload
      if ($request->hasFile('product_images')) {
        $productImagesPaths = [];
        foreach ($request->file('product_images') as $file) {
          $fileName = $file->getClientOriginalName();
          $productImagesPath = "assets/marketplace/{$user->id}/{$request->category_id}/{$request->sub_category_id}/{$product->id}/product_images/{$fileName}";
          Storage::disk('public_uploads')->put($productImagesPath, file_get_contents($file));
          $productImagesPaths[] = $productImagesPath;
        }
        $product->product_images = implode(',', $productImagesPaths);
      }

      if ($request->input('is_chunked_image')) {
        // Handle chunked upload for 3D image
        $threedImagePath = $this->handleChunkedUpload($request, 'threed_image', $user->id, $request->store_id, 'threed_image');
        if (!$threedImagePath) {
          throw new \Exception('Error processing 3D image.');
        }
        $product->threed_image = $threedImagePath;
      } elseif ($request->hasFile('threed_image')) {
        $file = $request->file('threed_image');
        $originalName = $file->getClientOriginalName();
        $fileExtension = $file->extension();
        $mimeType = $file->getMimeType();
        if ($fileExtension === 'txt' && $mimeType === 'text/plain') {
          $fileExtension = 'glb';
        } elseif ($fileExtension !== 'glb') {
          $fileExtension = 'glb';
        }
        if ($mimeType === 'model/gltf-binary' || $mimeType === 'application/octet-stream' || ($fileExtension === 'glb' && ($mimeType === 'text/plain' || $mimeType === 'model/gltf-binary'))) {
          $fileName = pathinfo($originalName, PATHINFO_FILENAME) . '.' . $fileExtension;
          $threedImagePath = "assets/marketplace/{$user->id}/{$request->category_id}/{$request->sub_category_id}/{$product_id}/threed_image/{$fileName}";
          $this->checkAndCreateDirectory($threedImagePath);
          Storage::disk('public_uploads')->put($threedImagePath, file_get_contents($file));
          $product->threed_image = $threedImagePath;
        } else {
          throw new \Exception('Invalid file type. Only .glb files are allowed.');
        }
      }

      // Handle product video upload
      if ($request->input('is_chunked_video')) {
        $productVideoPath = $this->handleChunkedUpload($request, 'product_video', $user->id, $request->store_id, 'product_video');
        $product->product_video = $productVideoPath;
      } elseif ($request->hasFile('product_video')) {
        $file = $request->file('product_video');
        $fileName = "product_video_" . $productSlug . "." . $file->extension();
        $productVideoPath = "assets/marketplace/{$user->id}/store/{$request->store_id}/product_video/{$fileName}";
        $this->checkAndCreateDirectory($productVideoPath);
        Storage::disk('public_uploads')->put($productVideoPath, file_get_contents($file));
        $product->product_video = $productVideoPath;
      }

      $product->save();
      DB::commit();

      $product_data = [
        'id' => $product->id,
        'user_id' => $product->user_id,
        'product_name' => $product->product_name,
        'price' => $product->price,
        'paid_price' => $product->paid_price,
        'discount_percentage' => $product->discount_percentage,
        'features' => $product->features,
        'description' => $product->description,
        'is_accessory' => $product->is_accessory,
        'stock' => $product->stock,
        'category_id' => $product->category_id,
        'sub_category_id' => $product->sub_category_id,
        'store_id' => $product->store_id,
        'thumbnail' => $product->thumbnail,
        'product_images' => is_string($product->product_images)
          ? explode(',', $product->product_images)
          : $product->product_images,
        'threed_image' => $product->threed_image,
        'product_video' => $product->product_video,
        'product_color' => $product->product_color,
        'best_seller_count' => $product->best_seller_count,
        'brand_name' => $product->brand_name,
      ];

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Product updated successfully.', 'toast' => true], ['updated_product_data' => $product_data]);
    } catch (\Exception $e) {
      // Log::error('Error updating product: ' . $e->getMessage());
      $line = $e->getLine();
      $file = $e->getFile();

      // Log the error message with file and line number
      Log::error('Error adding product in file ' . $file . ' on line ' . $line . ': ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }



  public function deleteProduct(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $productId = $request->input('product_id');
      $storeId = $request->input('store_id');

      // Check if user is the store owner
      $isStoreOwner = isStoreOwner($storeId, $user);
      if (!$isStoreOwner['status']) {
        return generateResponse(['type' => 'error', 'code' => 403, 'status' => false, 'message' => $isStoreOwner['message'], 'toast' => true]);
      }

      // Fetch the product to delete
      $product = MarketplaceProducts::where('id', $productId)
        ->where('store_id', $storeId)
        ->first();

      if (!$product) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Product not found.', 'toast' => true]);
      }

      // Delete product files (thumbnails, images, videos)
      if ($product->thumbnail) {
        Storage::disk('public_uploads')->delete($product->thumbnail);
      }

      if ($product->product_images) {
        $productImages = explode(',', $product->product_images);
        foreach ($productImages as $image) {
          Storage::disk('public_uploads')->delete($image);
        }
      }

      if ($product->product_video) {
        Storage::disk('public_uploads')->delete($product->product_video);
      }

      if ($product->threed_image) {
        Storage::disk('public_uploads')->delete($product->threed_image);
      }

      // Finally, delete the product record
      $product->delete();

      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Product deleted successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error deleting product: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error deleting product.', 'toast' => true]);
    }
  }


  public function getProduct(Request $request)
  {
    try {
      $query = MarketplaceProducts::query();

      // Apply filters
      $this->applyFilters($query, $request);

      if ($request->filled('product_id')) {
        return $this->getProductById($request->product_id);
      }

      // Apply sorting and pagination
      $this->applySortingAndPagination($query, $request);

      // Apply search terms
      if ($request->filled('search')) {
        $this->applySearch($query, $request->input('search'));
      }

      $products = $query->get();

      // Process the results
      $this->processProducts($products);

      if ($products->isEmpty()) {
        return response()->json([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'No data found.',
          'toast' => true,
          'data' => []
        ]);
      }

      return response()->json([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Products fetched successfully',
        'toast' => true,
        'data' => ['products' => $products]
      ]);
    } catch (\Exception $e) {
      Log::error('Get product fetch error: ' . $e->getMessage());

      return response()->json([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true
      ]);
    }
  }



  private function applyFilters($query, $request)
  {
    if ($request->filled('category_id')) {
      $query->where('category_id', $request->category_id);
    }

    if ($request->filled('sub_category_id')) {
      $query->where('sub_category_id', $request->sub_category_id);
    }

    if ($request->filled('store_id')) {
      $query->where('store_id', $request->store_id);
    }

    if ($request->filled('is_accessory')) {
      $query->where('is_accessory', $request->is_accessory);
    }

    if ($request->filled('is_out_of_stock')) {
      $query->where('stock', '0');
    }
  }
  private function applySortingAndPagination($query, $request)
  {
    if ($request->filled('price')) {
      $query->orderBy('price', $request->price === 'asc' ? 'asc' : 'desc');
    }

    if ($request->filled('best_seller_count')) {
      $query->orderBy('best_seller_count', 'desc');
    }

    $limit = $request->input('limit', 10);
    $offset = $request->input('offset', 0);

    $query->orderBy('created_at', 'asc')->limit($limit)->offset($offset);

    if ($request->filled('top_rating') && $request->top_rating === 'true') {
      $query->select('marketplace_products.*')
        ->join('marketplace_store_product_reviews', 'marketplace_products.id', '=', 'marketplace_store_product_reviews.product_id')
        ->groupBy('marketplace_products.id')
        ->havingRaw('AVG(marketplace_store_product_reviews.rating) >= 4');
    }
  }
  private function applySearch($query, $searchTerm)
  {
    $query->where(function ($query) use ($searchTerm) {
      $query->where('product_name', 'like', "%$searchTerm%")
        ->orWhere('description', 'like', "%$searchTerm%")
        ->orWhere('price', 'like', "%$searchTerm%")
        ->orWhere('brand_name', 'like', "%$searchTerm%")
        ->orWhere('product_color', 'like', "%$searchTerm%")
        ->orWhereExists(function ($query) use ($searchTerm) {
          $query->select(DB::raw(1))
            ->from('marketplace_stores')
            ->where('name', 'like', "%$searchTerm%")
            ->whereRaw('marketplace_stores.id = marketplace_products.store_id');
        })
        ->orWhereExists(function ($query) use ($searchTerm) {
          $query->select(DB::raw(1))
            ->from('marketplace_category')
            ->where('category_name', 'like', "%$searchTerm%")
            ->whereRaw('marketplace_category.id = marketplace_products.category_id');
        })
        ->orWhereExists(function ($query) use ($searchTerm) {
          $query->select(DB::raw(1))
            ->from('marketplace_sub_category')
            ->where('sub_category_name', 'like', "%$searchTerm%")
            ->whereRaw('marketplace_sub_category.id = marketplace_products.sub_category_id');
        });
    });
  }
  private function getProductById($productId)
  {
    $mainProduct = MarketplaceProducts::with('specifications')->find($productId);
    if (!$mainProduct) {
      return response()->json([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Product not found.',
        'toast' => true,
        'data' => []
      ]);
    }

    $mainProduct->product_images = $mainProduct->product_images ? explode(',', $mainProduct->product_images) : [];
    $store = MarketplaceStore::find($mainProduct->store_id);
    $mainProduct->store_name = $store ? $store->name : 'Unknown';

    // Construct URLs for product images
    $mainProduct->thumbnail = url("uploads/{$mainProduct->thumbnail}");
    $mainProduct->threed_image = url("uploads/{$mainProduct->threed_image}");

    $productVideoPath = "uploads/{$mainProduct->product_video}";
    $mainProduct->product_video = $mainProduct->product_video && file_exists(public_path($productVideoPath)) ? url($productVideoPath) : null;

    $mainProduct->product_images = array_map(function ($image) {
      return url("uploads/{$image}");
    }, $mainProduct->product_images);

    // Handle related products
    $relatedProducts = MarketplaceProducts::where('sub_category_id', $mainProduct->sub_category_id)
      ->where('id', '!=', $productId)
      ->get();

    foreach ($relatedProducts as $product) {
      $product->product_images = $product->product_images ? explode(',', $product->product_images) : [];
      $store = MarketplaceStore::find($product->store_id);
      $product->store_name = $store ? $store->name : 'Unknown';

      $product->thumbnail = url("uploads/{$product->thumbnail}");
      $product->threed_image = url("uploads/{$product->threed_image}");

      $relatedProductVideoPath = "uploads/{$product->product_video}";
      $product->product_video = $product->product_video && file_exists(public_path($relatedProductVideoPath)) ? url($relatedProductVideoPath) : null;

      $product->product_images = array_map(function ($image) {
        return url("uploads/{$image}");
      }, $product->product_images);
    }

    return response()->json([
      'type' => 'success',
      'status' => true,
      'code' => 200,
      'message' => 'Products fetched successfully',
      'data' => ['main_product' => $mainProduct, 'related_products' => $relatedProducts],
      'toast' => true
    ]);
  }


  private function processProducts($products)
  {
    foreach ($products as $product) {
      if (is_string($product->product_images)) {
        $product->product_images = explode(',', $product->product_images);
      } else {
        $product->product_images = [];
      }

      $store = MarketplaceStore::find($product->store_id);
      $product->store_name = $store ? $store->name : 'Unknown';

      $product->thumbnail = url("uploads/{$product->thumbnail}");
      $product->threed_image = url("uploads/{$product->threed_image}");

      $productVideoPath = "uploads/{$product->product_video}";
      $product->product_video = $product->product_video && file_exists(public_path($productVideoPath)) ? url($productVideoPath) : null;

      $product->product_images = array_map(function ($image) {
        return url("uploads/{$image}");
      }, $product->product_images);
    }
  }

  public function storeProductQuestions(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $productQue = new StoreProductQuestions();
      $productQue->user_id = $user->id;
      $productQue->product_id = $request->product_id;

      $marketplaceProduct = MarketplaceProducts::where('id', $request->product_id)->first();

      if (!$marketplaceProduct) {
        DB::rollback();
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product not found.', 'toast' => true]);
      }
      $productQue->nick_name = $request->nick_name;
      $productQue->question = $request->question;
      $productQue->answer = $request->answer;
      $productQue->save();
      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Question added successfully.', 'toast' => true], ['questions' => $productQue]);
    } catch (\Exception $e) {
      Log::error('Error adding product' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function updateProductQuestionAnswer(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $questionId = $request->id;
      $productQue = StoreProductQuestions::find($questionId);

      if (!$productQue) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Question not found.', 'toast' => true]);
      }

      if ($productQue->user_id !== $user->id) {
        return generateResponse(['type' => 'error', 'code' => 403, 'status' => false, 'message' => 'Unauthorized to update this question.', 'toast' => true]);
      }

      if ($request->filled('question')) {
        $productQue->question = $request->question;
      }
      if ($request->filled('answer')) {
        $productQue->answer = $request->answer;
      }

      $productQue->save();
      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Question/Answer updated successfully.', 'toast' => true, 'data' => $productQue]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error updating question: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing.', 'toast' => true]);
    }
  }

  public function storeProductReviews(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $productRev = new StoreProductReviews();
      $productRev->user_id = $user->id;
      $productRev->product_id = $request->product_id;

      $marketplaceProduct = MarketplaceProducts::where('id', $request->product_id)->first();

      if (!$marketplaceProduct) {
        DB::rollback();
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product not found.', 'toast' => true]);
      }
      $productRev->store_id = $marketplaceProduct->store_id;
      $productRev->review_description = $request->review_description;
      $productRev->rating = $request->rating;
      if ($request->hasFile('review_media')) {
        $file = $request->file('review_media');
        $fileName = $file->getClientOriginalName();
        $filePath = "public/assets/marketplace/review/{$request->product_id}/{$fileName}";
        Storage::put($filePath, file_get_contents($file));
        $productRev->review_media = "assets/marketplace/review/{$request->product_id}/{$fileName}";
      }
      $productRev->save();
      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Review added successfully.', 'toast' => true], ['questions' => $productRev]);
    } catch (\Exception $e) {
      Log::error('Error adding product' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  // public function getStoreProductQuestions(Request $request)
  // {
  //   try {
  //     DB::beginTransaction();

  //     $query = StoreProductQuestions::query();

  //     if ($request->filled('search')) {
  //       $searchTerm = $request->input('search');
  //       $query->where('question', 'like', "%$searchTerm%")
  //         ->orWhere('answer', 'like', "%$searchTerm%");
  //     }

  //     $limit = $request->input('limit', 10);
  //     $offset = $request->input('offset', 0);
  //     if ($limit > 10) {
  //       $query->offset($offset)->limit($limit);
  //     }
  //     $productId = $request->product_id;
  //     if (!$productId) {
  //       return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product ID is required.', 'toast' => true]);
  //     }

  //     $query->where('product_id', $productId);

  //     $questions = $query->get();

  //     DB::commit();
  //     if ($questions->isEmpty()) {
  //       return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true, 'data' => []]);
  //     }
  //     return response()->json(['type' => 'success', 'status' => true, 'code' => 200, 'message' => 'Questions fetched successfully', 'data' => $questions, 'toast' => true]);
  //   } catch (\Exception $e) {
  //     DB::rollBack();
  //     Log::error('Error fetching product questions: ' . $e->getMessage());

  //     return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true, 'data' => []]);
  //   }
  // }

  public function getStoreProductQuestions(Request $request)
  {
    try {
      DB::beginTransaction();

      $query = StoreProductQuestions::query();

      if ($request->filled('search')) {
        $searchTerm = $request->input('search');
        $query->where('question', 'like', "%$searchTerm%")
          ->orWhere('answer', 'like', "%$searchTerm%");
      }

      $limit = $request->input('limit', 10);
      $offset = $request->input('offset', 0);
      if ($limit > 10) {
        $query->offset($offset)->limit($limit);
      }

      $productId = $request->product_id;
      if (!$productId) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product ID is required.', 'toast' => true]);
      }

      $query->where('product_id', $productId);

      $questions = $query->get()->map(function ($question) {
        $likesArray = json_decode($question->likes, true);
        $dislikesArray = json_decode($question->dislikes, true);

        $likesCount = is_array($likesArray) ? count($likesArray) : 0;
        $dislikesCount = is_array($dislikesArray) ? count($dislikesArray) : 0;

        return [
          'id' => $question->id,
          'user_id' => $question->user_id,
          'product_id' => $question->product_id,
          'merchant_id' => $question->merchant_id,
          'nick_name' => $question->nick_name,
          'question' => $question->question,
          'answer' => $question->answer,
          'is_answered' => $question->is_answered,
          'status' => $question->status,
          'likes' => $likesCount,
          'dislikes' => $dislikesCount,
          'created_at' => $question->created_at,
          'updated_at' => $question->updated_at
        ];
      });
      $sortedQuestions = $questions->sortByDesc('likes')->values();
      DB::commit();
      if ($sortedQuestions->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true, 'data' => []]);
      }
      return response()->json(['type' => 'success', 'status' => true, 'code' => 200, 'message' => 'Questions fetched successfully', 'data' => $sortedQuestions, 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error fetching product questions: ' . $e->getMessage());
      return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true, 'data' => []]);
    }
  }


  public function likeDislikeQuestion(Request $request)
  {
    DB::beginTransaction();
    try {
      $request->validate([
        'action' => 'required|in:like,dislike,remove_like,remove_dislike',
        'question_id' => 'required|exists:marketplace_store_product_questions,id',
      ]);

      $question = StoreProductQuestions::find($request->question_id);
      $userId = $request->attributes->get('user')->id;

      $likes = $question->likes ? json_decode($question->likes, true) : [];
      $dislikes = $question->dislikes ? json_decode($question->dislikes, true) : [];

      switch ($request->action) {
        case 'like':

          if (!in_array($userId, $likes)) {
            $likes[] = $userId;
          }

          if (($key = array_search($userId, $dislikes)) !== false) {
            unset($dislikes[$key]);
          }
          break;

        case 'dislike':

          if (!in_array($userId, $dislikes)) {
            $dislikes[] = $userId;
          }

          if (($key = array_search($userId, $likes)) !== false) {
            unset($likes[$key]);
          }
          break;

        case 'remove_like':
          if (($key = array_search($userId, $likes)) !== false) {
            unset($likes[$key]);
          }
          break;

        case 'remove_dislike':
          if (($key = array_search($userId, $dislikes)) !== false) {
            unset($dislikes[$key]);
          }
          break;
      }

      $question->likes = json_encode(array_values($likes));
      $question->dislikes = json_encode(array_values($dislikes));
      $question->save();

      DB::commit();
      return response()->json([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Like/Dislike action processed successfully.',
        'data' => [
          'likes' => count($likes),
          'dislikes' => count($dislikes)
        ],
        'toast' => true
      ]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error processing like/dislike action: ' . $e->getMessage());
      return response()->json([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while processing like/dislike action.',
        'toast' => true,
        'data' => []
      ]);
    }
  }

  public function getstoreProductReviews(Request $request)
  {
    try {
      DB::beginTransaction();

      $productId = $request->product_id;
      if (!$productId) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product ID is required.', 'toast' => true]);
      }

      $query = StoreProductReviews::where('product_id', $productId);

      $limit = $request->input('limit', 10);
      $offset = $request->input('offset', 0);
      if ($limit > 10) {
        $query->offset($offset)->limit($limit);
      }

      $reviews = $query->get();

      if ($reviews->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No reviews available', 'toast' => true, 'data' => []]);
      }

      $reviewData = [];
      $ratingCounts = [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
      ];

      $totalRatings = 0;
      $sumRatings = 0;

      foreach ($reviews as $review) {
        $ratingCounts[$review->rating]++;
        $sumRatings += $review->rating;
        $totalRatings++;

        if ($review->review_media) {
          // $review_image = storage_path('app/' . $review->review_media);
          // if (file_exists($review_image)) {
          //     $review_image_data = file_get_contents($review_image);
          $reviewData = url("storage/" . $review->review_media);
          // }
        }
      }

      $averageRating = ($totalRatings > 0) ? round($sumRatings / $totalRatings, 1) : 0;


      $ratingPercentages = [];
      foreach ($ratingCounts as $rating => $count) {
        $ratingPercentages[$rating] = ($totalRatings > 0) ? ($count / $totalRatings) * 100 : 0;
      }

      DB::commit();

      return response()->json([
        'type' => 'success',
        'status' => true,
        'code' => 200,
        'message' => 'Reviews fetched successfully',
        'data' => $reviews,
        'review_media_base64' => $reviewData,
        'rating_counts' => $ratingCounts,
        'rating_percentages' => $ratingPercentages,
        'total_ratings' => $totalRatings,
        'average_rating' => $averageRating,
        'toast' => true
      ]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error fetching product reviews: ' . $e->getMessage());
      return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true, 'data' => []]);
    }
  }

  public function manageCart(ManageCartRequest $request)
  {

    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $action = $request->action;
      $cart_data = isset($request->cart_data) ? $request->cart_data : [];

      $cart_table_data = [];

      if ($action === 'manage' || $action == 'add' || $action === 'buy-product') {
        if ($action === 'buy-product') {
          $cart_data = [
            [
              'product_id' => $request->product_id,
              'quantity' => $request->quantity
            ]
          ];
        }

        if ($cart_data && is_array($cart_data)) {
          $idArray = array_filter($cart_data, function ($product) {
            return isset($product['product_id']);
          });

          $productIdKeys = array_map(function ($product) {
            if (isset($product['product_id'])) {
              return $product['product_id'];
            } else {
              return null;
            }
          }, $idArray);

          $productIds = MarketplaceProducts::whereIn("id", $productIdKeys)
            ->selectRaw("id as productId")
            ->pluck("productId");

          if ($productIds && $productIds->toArray()) {
            $productIds = $productIds->toArray();

            foreach ($cart_data as $productItem) {
              if (in_array($productItem['product_id'], $productIds)) {
                if (isset($productItem['quantity']) && is_numeric($productItem['quantity']) && $productItem['quantity'] > 0) {
                  $row = [];
                  $row['product_id'] = $productItem['product_id'];
                  $row['quantity'] = $productItem['quantity'];
                  $cart_table_data[] = $row;
                }
              } else {
                continue;
              }
            }
          }
        }

        // valid product cart data get
        if ($cart_table_data) {
          $user_cart = MarketplaceUserCart::where("user_id", $user->id)->first();

          if (!$user_cart) {
            $user_cart = new MarketplaceUserCart();
            $user_cart->user_id = $user->id;
          }

          if ($action == 'manage') {
            $user_cart->products = json_encode($cart_table_data);
          } else {
            $previousCartData = [];
            if ($user_cart->products) {
              $previousCartData = json_decode($user_cart->products, true) ?: [];
            }

            if ($previousCartData) {
              $oldCartAssoc = []; // for old cart data
              $newCartAssoc = []; // for new cart data

              foreach ($previousCartData as $item) {
                $oldCartAssoc[$item['product_id']] = $item['quantity'];
              }

              foreach ($cart_table_data as $item) {
                $newCartAssoc[$item['product_id']] = $item['quantity'];
              }

              $oldCartArray = [];
              foreach ($oldCartAssoc as $productId => $quantity) {
                $oldCartArray[$productId] = $quantity + ($newCartAssoc[$productId] ?? 0);
                unset($newCartAssoc[$productId]);
              }

              $newCartArray = [];
              foreach ($newCartAssoc as $productId => $quantity) {
                $newCartArray[$productId] = $quantity;
              }

              $oldCartResult = [];
              foreach ($oldCartArray as $productId => $quantity) {
                $oldCartResult[] = ['product_id' => $productId, 'quantity' => $quantity];
              }

              $newCartResult = [];
              foreach ($newCartArray as $productId => $quantity) {
                $newCartResult[] = ['product_id' => $productId, 'quantity' => $quantity];
              }

              $result = array_merge($oldCartResult, $newCartResult);
              $user_cart->products = json_encode($result);
            } else {
              $user_cart->products = json_encode($cart_table_data);
            }
          }

          $user_cart->save();
          $cart_id = $user_cart->id;
          DB::commit();

          $cart_data = $this->getCartDataByUsers($user->id);
          $token_data = $this->getuserTokenData($user, true);

          return generateResponse(
            [
              'type' => 'success',
              'code' => 200,
              'status' => true,
              'message' => 'Cart retrieved successfully.',
              'toast' => true
            ],
            [
              'token_data' => $token_data,
              'cartItems' => $cart_data,
              'cartCount' => count($cart_data),
              'cartId' => $cart_id
            ]
          );
        } else {
          return generateResponse(
            [
              'type' => 'error',
              'code' => 200,
              'status' => false,
              'message' => 'Enter valid products with quantity',
              'toast' => true
            ]
          );
        }
      } else if ($action === 'delete') {
        $user_cart = MarketplaceUserCart::where("user_id", $user->id)->first();
        if ($user_cart) {
          if ($user_cart->products) {
            $products = json_decode($user_cart->products, true);
            if ($products) {
              $tempProductData = [];
              $product_id = $request->product_id;
              foreach ($products as $product) {
                if ($product_id != $product['product_id']) {
                  $tempProductData[] = $product;
                }
              }
              $user_cart->products = json_encode($tempProductData);
              $user_cart->save();
              DB::commit();

              $cart_data = $this->getCartDataByUsers($user->id);
              $token_data = $this->getuserTokenData($user, true);
              $cart_count = $cart_data ? count($cart_data) : 0;

              return generateResponse(
                [
                  'type' => 'success',
                  'code' => 200,
                  'status' => true,
                  'message' => 'Product deleted from cart',
                  'toast' => true
                ],
                [
                  'token_data' => $token_data,
                  'cart' => $cart_data,
                  'cart_count' => $cart_count
                ]
              );
            }
          }
        }
        return generateResponse(
          [
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Cart is already empty.',
            'toast' => true
          ]
        );
      } else if ($action === 'update') {
        $user_cart = MarketplaceUserCart::where("user_id", $user->id)->first();
        if ($user_cart) {
          if ($user_cart->products) {
            $products = json_decode($user_cart->products, true);
            if ($products) {
              $tempProductData = [];
              $product_id = $request->product_id;
              foreach ($products as $product) {
                if ($product_id == $product['product_id']) {
                  $product['quantity'] = $request->quantity;
                }
                $tempProductData[] = $product;
              }
              $user_cart->products = json_encode($tempProductData);
              $user_cart->save();
              DB::commit();

              $cart_data = $this->getCartDataByUsers($user->id);
              $token_data = $this->getuserTokenData($user, true);

              return generateResponse(
                [
                  'type' => 'success',
                  'code' => 200,
                  'status' => true,
                  'message' => 'Product count updated in cart',
                  'toast' => true
                ],
                [
                  'token_data' => $token_data,
                  'cart' => $cart_data,
                  'cart_count' => count($cart_data)
                ]
              );
            }
          }
        }
        return generateResponse(
          [
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Cart is already empty. Can\'t update',
            'toast' => true
          ]
        );
      } else if ($action === 'clear') {
        $cartItemsDeleted = MarketplaceUserCart::where('user_id', $user->id)->delete();
        if ($cartItemsDeleted === 0) {
          DB::rollback();
          return generateResponse(
            [
              'type' => 'error',
              'code' => 200,
              'status' => false,
              'message' => 'Cart is already empty.',
              'toast' => true
            ]
          );
        }
        DB::commit();
        $cartCount = 0;
        return generateResponse(
          [
            'type' => 'success',
            'code' => 200,
            'status' => true,
            'message' => 'Cart cleared successfully.',
            'toast' => true
          ],
          ['cart_count' => $cartCount]
        );
      } else if ($action === 'buy') {
        $product_id = $request->product_id;
        $quantity = $request->quantity;
        $token_data = $this->getuserTokenData($user, true);

        DB::beginTransaction(); // Start transaction

        // Validate input
        if (!$product_id || !$quantity || !is_numeric($quantity) || $quantity <= 0) {
          DB::rollback();
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Invalid product or quantity.',
            'toast' => true
          ]);
        }

        // Find the product
        $product = MarketplaceProducts::find($product_id);
        if (!$product) {
          DB::rollback();
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Product not found.',
            'toast' => true
          ]);
        }

        // Check if the product is out of stock
        if ($product->is_out_of_stock === '0') {
          DB::rollback();
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Product is out of stock.',
            'toast' => true
          ]);
        }

        // Check if there's enough stock
        if ($quantity > $product->quantity) {
          DB::rollback();
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Not enough stock available.',
            'toast' => true
          ]);
        }

        if ($quantity > $product->max_order_quantity) {
          DB::rollback();
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Quantity exceeds the maximum allowed for this product.',
            'toast' => true
          ]);
        }

        // Prepare product data for response
        $responseData = [
          'id' => $product->id,
          'product_name' => $product->product_name,
          'price' => $product->price,
          'discount_percentage' => $product->discount_percentage,
          'features' => $product->features,
          'description' => $product->description,
          'product_images' => $product->product_images,
          'thumbnail' => $product->thumbnail,
          'is_accessory' => $product->is_accessory,
          'stock' => $product->quantity,
          'category_id' => $product->category_id,
          'sub_category_id' => $product->sub_category_id,
          'store_id' => $product->store_id,
          'product_color' => $product->product_color,
          'best_seller_count' => $product->best_seller_count,
          'brand_name' => $product->brand_name,
          'max_buy' => $product->max_order_quantity,
          'quantity' => $quantity
        ];

        DB::commit(); // Commit transaction

        // Return a response to redirect the user to the checkout page
        return generateResponse([
          'type' => 'success',
          'status' => true,
          'code' => 200,
          'message' => 'Product ready for purchase. Redirecting to checkout.',
          'toast' => true
        ], [
          'checkout' => [
            'token_data' => $token_data, // This can be adjusted based on your checkout logic
            'product' => $responseData,
            'quantity' => $request->quantity
          ]
        ]);
      } else {
        DB::rollback();
        return generateResponse(
          [
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Invalid action.',
            'toast' => true
          ]
        );
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error managing cart: ' . $e->getMessage());
      return generateResponse(
        [
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Error while processing.',
          'toast' => true
        ]
      );
    }
  }

  protected function getCartDataByUsers($user_id)
  {
    // Retrieve the cart for the user
    $user_cart = MarketplaceUserCart::where("user_id", $user_id)->first();

    if (!$user_cart)
      return ['cart_id' => null, 'cart_items' => null];

    $cart_id = $user_cart->id;

    if (!$user_cart->products)
      return ['cart_id' => $cart_id, 'cart_items' => null];

    $products = json_decode($user_cart->products, true);
    if (!$products)
      return ['cart_id' => $cart_id, 'cart_items' => null];

    $tempProductData = [];

    foreach ($products as $product) {
      $tempProductData[$product['product_id']] = $product['quantity'];
    }

    $idArray = array_filter($products, function ($product) {
      return isset($product['product_id']);
    });

    $productIdKeys = array_map(function ($product) {
      if (isset($product['product_id']))
        return $product['product_id'];
      else
        return null;
    }, $idArray);

    $productDatas = MarketplaceProducts::whereIn("id", $productIdKeys)->select([
      'id',
      'product_name',
      'price',
      'discount_percentage',
      'features',
      'description',
      'product_images',
      'thumbnail',
      'is_accessory',
      'stock',
      'category_id',
      'sub_category_id',
      'store_id',
      'product_color',
      'best_seller_count',
      'brand_name',
      'max_buy'
    ])->get();

    $cartItemData = [];
    if ($productDatas->isNotEmpty()) {
      $productDatas = $productDatas->toArray();
      foreach ($productDatas as $productItem) {
        $productItem['quantity'] = $tempProductData[$productItem['id']];
        $cartItemData[] = $productItem;
      }
    }

    return ['cart_id' => $cart_id, 'cart_items' => $cartItemData];
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


  protected function getProductDataById($product_id)
  {
    $product = MarketplaceProducts::withTrashed()->where('id', $product_id)->first();
    if (!$product) {
      return generateResponse(
        [
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Product not found.',
          'toast' => true
        ],
        [],
        404
      );
    }

    // Convert product to array
    $productData = $product->toArray();

    // Get user token data
    $user = request()->attributes->get('user');
    $token_data = $this->getuserTokenData($user, true);

    return generateResponse(
      [
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Product retrieved successfully.',
        'toast' => true
      ],
      [
        'cartItems' => [$productData],
        'token_data' => $token_data,
      ],
      200
    );
  }

  public function getuserCart(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $productId = $request->input('product_id');
      // if ($productId) {
      //     return $this->getProductDataById($productId);
      // }
      $cartData = $this->getCartDataByUsers($user->id);

      if ($cartData['cart_items']) {
        $cartItems = $cartData['cart_items'];

        if ($productId) {
          $filteredItems = array_filter($cartItems, function ($item) use ($productId) {
            return $item['id'] == $productId;
          });

          if (empty($filteredItems)) {
            return generateResponse([
              'type' => 'error',
              'code' => 200,
              'status' => false,
              'message' => 'Product not found in the cart.',
              'toast' => true
            ]);
          }

          $cartItems = array_values($filteredItems);
        }

        $token_data = $this->getuserTokenData($user, true);
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Cart retrieved successfully.',
          'toast' => true
        ], [
          'token_data' => $token_data,
          'cartItems' => $cartItems,
          'cartCount' => count($cartItems),
          'cartId' => $cartData['cart_id']
        ]);
      } else {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Cart is empty.',
          'toast' => true
        ]);
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error retrieving cart: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error while processing.',
        'toast' => true
      ]);
    }
  }


  // public function getHeaderCategories()
  // {
  //     DB::beginTransaction();
  //     try {
  //         $categories = MarketplaceCategory::all();
  //         $categoriesWithSubcategories = [];

  //         foreach ($categories as $category) {
  //             $subcategories = MarketplaceSubCategory::where('parent_category_id', $category->id)->get();
  //             $subcategoriesWithProducts = [];

  //             foreach ($subcategories as $subcategory) {
  //                 $products = MarketplaceProducts::where('sub_category_id', $subcategory->id)->take(10)->get();
  //                 $subcategoriesWithProducts[] = [
  //                     'subcategory' => $subcategory->toArray(),
  //                     'products' => $products->toArray()
  //                 ];
  //             }
  //             $categoriesWithSubcategories[] = [
  //                 'category' => $category->toArray(),
  //                 'subcategories' => $subcategoriesWithProducts
  //             ];
  //         }

  //         if ($category && $category->toArray()) {
  //             return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Stores data retrieved successfully', 'toast' => false, 'data' => ['categories' => $categoriesWithSubcategories]]);
  //         } else {

  //             return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Stores data not found', 'toast' => true,]);
  //         }
  //     } catch (\Exception $e) {
  //         DB::rollback();
  //         Log::error('Error retrieving stores: ' . $e->getMessage());
  //         return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing.', 'toast' => true]);
  //     }
  // }

  public function getHeaderCategories()
  {
    DB::beginTransaction();
    try {
      $categories = MarketplaceCategory::all();
      $categoriesWithSubcategories = [];

      foreach ($categories as $category) {
        // Manually construct the category image URL
        $categoryImageURL = $category->image_path ? url("uploads/{$category->image_path}") : null;

        $subcategories = MarketplaceSubCategory::where('parent_category_id', $category->id)->get();
        $subcategoriesWithProducts = [];

        foreach ($subcategories as $subcategory) {
          // Manually construct the subcategory image URL
          $subcategoryImageURL = $subcategory->image_path ? url("uploads/{$subcategory->image_path}") : null;

          $products = MarketplaceProducts::where('sub_category_id', $subcategory->id)->take(10)->get();
          $productsWithImages = [];

          foreach ($products as $product) {
            // Manually construct the product image URL
            $productImageURL = $product->threed_image ? url("uploads/{$product->threed_image}") : null;
            $productsWithImages[] = array_merge(
              $product->toArray(),
              ['image_url' => $productImageURL]
            );
          }

          $subcategoriesWithProducts[] = [
            'subcategory' => array_merge(
              $subcategory->toArray(),
              ['image_url' => $subcategoryImageURL]
            ),
            'products' => $productsWithImages
          ];
        }

        $categoriesWithSubcategories[] = [
          'category' => array_merge(
            $category->toArray(),
            ['image_url' => $categoryImageURL]
          ),
          'subcategories' => $subcategoriesWithProducts
        ];
      }

      if ($category && $category->toArray()) {
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Categories data retrieved successfully',
          'toast' => false,
          'data' => ['categories' => $categoriesWithSubcategories]
        ]);
      } else {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Categories data not found',
          'toast' => true,
        ]);
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error retrieving categories: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error while processing.',
        'toast' => true
      ]);
    }
  }


  public function editHeaderCategory(Request $request, $categoryId)
  {
    DB::beginTransaction();
    try {
      // Find the category by ID
      $category = MarketplaceCategory::find($categoryId);
      if (!$category) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Category not found', 'toast' => true]);
      }

      // Prepare category data for update
      $categoryData = [
        'category_name' => $request->input('category_name'),
      ];

      // Handle category thumbnail update if provided
      if ($request->hasFile('categoryThumbnail')) {
        $image = $request->file('categoryThumbnail');
        $filename = time() . '.' . $image->getClientOriginalExtension();
        $uploadPath = 'uploads/admin/store_product_category/';
        $fullImagePath = $uploadPath . $filename;

        // Create directory if it does not exist
        if (!Storage::exists($uploadPath)) {
          Storage::makeDirectory($uploadPath, 0755, true);
        }

        // Store original image
        Storage::put($fullImagePath, file_get_contents($image));

        // Resize and store thumbnail
        $thumbnailPath = $uploadPath . '419x419/' . $filename;
        $img = Image::read($image->getRealPath());
        $img->resize(419, 419, function ($constraint) {
          $constraint->aspectRatio();
        });
        Storage::put($thumbnailPath, (string) $img->encode());

        $categoryData['image_path'] = $fullImagePath;
        $categoryData['image_ext'] = $image->getClientOriginalExtension();
      }

      // Update category details
      $category->update($categoryData);

      // Update subcategories if provided
      if ($request->has('subcategories')) {
        foreach ($request->input('subcategories') as $subcategoryData) {
          $subcategory = MarketplaceSubCategory::find($subcategoryData['id']);
          if ($subcategory) {
            $subcategoryDataToUpdate = [
              'sub_category_name' => $subcategoryData['sub_category_name'],
              'type' => $subcategoryData['type'],
            ];

            // Handle subcategory thumbnail update if provided
            if (isset($subcategoryData['subCategoryThumbnail'])) {
              $image = $subcategoryData['subCategoryThumbnail'];
              $filename = time() . '.' . $image->getClientOriginalExtension();
              $uploadPath = 'uploads/admin/store_product_subcategory/';
              $fullImagePath = $uploadPath . $filename;

              // Create directory if it does not exist
              if (!Storage::exists($uploadPath)) {
                Storage::makeDirectory($uploadPath, 0755, true);
              }

              // Store original image
              Storage::put($fullImagePath, file_get_contents($image));

              // Resize and store thumbnail
              $thumbnailPath = $uploadPath . '419x419/' . $filename;
              $img = Image::read($image->getRealPath());
              $img->resize(419, 419, function ($constraint) {
                $constraint->aspectRatio();
              });
              Storage::put($thumbnailPath, (string) $img->encode());

              $subcategoryDataToUpdate['image_path'] = $fullImagePath;
              $subcategoryDataToUpdate['image_ext'] = $image->getClientOriginalExtension();
            }

            // Update subcategory details
            $subcategory->update($subcategoryDataToUpdate);
          }
        }
      }

      // Commit the transaction
      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Category updated successfully', 'toast' => false]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error updating category: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while updating category.', 'toast' => true]);
    }
  }



  public function deleteHeaderCategory($categoryId)
  {
    DB::beginTransaction();
    try {
      // Find the category by ID
      $category = MarketplaceCategory::find($categoryId);
      if (!$category) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Category not found', 'toast' => true]);
      }

      // Find and delete subcategories and their associated products
      $subcategories = MarketplaceSubCategory::where('parent_category_id', $categoryId)->get();
      foreach ($subcategories as $subcategory) {
        MarketplaceProducts::where('sub_category_id', $subcategory->id)->delete();
        $subcategory->delete();
      }

      // Delete the main category
      $category->delete();

      // Commit the transaction
      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Category and its subcategories deleted successfully', 'toast' => false]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error deleting category: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while deleting category.', 'toast' => true]);
    }
  }


  public function addSiteBanner(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $role_id = $user->role_id;

      if ($role_id == '1' || $role_id == '2') {
        if ($request->has('field_keys') && $request->has('field_output_values')) {
          $fieldKeys = $request->input('field_keys');
          $fieldOutputValues = $request->file('field_output_values');

          foreach ($fieldKeys as $index => $fieldKey) {
            $siteSetting = MarketplaceSiteSetting::where('field_key', $fieldKey)->first();

            if (!$siteSetting) {
              return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Site setting not found for key: ' . $fieldKey, 'toast' => true]);
            }

            if (isset($fieldOutputValues[$index])) {
              $file = $fieldOutputValues[$index];
              $fileName = $file->getClientOriginalName();
              $filePath = "public/assets/marketplace/site_setting/{$fileName}";

              if ($siteSetting->field_output_value && Storage::exists($siteSetting->field_output_value)) {
                Storage::delete($siteSetting->field_output_value);
              }

              Storage::put($filePath, file_get_contents($file));
              $filePath = substr($filePath, strlen('public/'));
              $siteSetting->field_output_value = $filePath;

              if ($request->filled('category_id')) {
                $siteSetting->category_id = $request->category_id;
              }
              if ($request->filled('sub_category_id')) {
                $siteSetting->sub_category_id = $request->sub_category_id;
              }
              if ($request->filled('store_id')) {
                $siteSetting->store_id = $request->store_id;
              }
              if ($request->filled('product_id')) {
                $siteSetting->product_id = $request->product_id;
              }
            }
            $siteSetting->save();
          }

          DB::commit();

          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Site settings updated successfully.', 'toast' => true, 'data' => ["siteSetting" => $siteSetting->toArray()]]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Field keys and/or files are missing in the request.', 'toast' => true]);
        }
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You don\'t have privilege to perform the task', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error updating site settings: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getSiteBanner(Request $request)
  {
    try {
      $field_keys = $request->input('field_key');
      if ($field_keys) {
        $site_setting = MarketplaceSiteSetting::where('field_key', $field_keys)->first();
      } else {
        $site_setting = MarketplaceSiteSetting::all();
      }

      if ($site_setting instanceof Collection) {
        $site_data = $site_setting->map(function ($setting) {
          return [
            'id' => $setting->id,
            'field_name' => $setting->field_name,
            'field_key' => $setting->field_key,
            'field_output_value' => $setting->field_output_value,
            'category_id' => $setting->category_id,
            'sub_category_id' => $setting->sub_category_id,
            'store_id' => $setting->store_id,
            'product_id' => $setting->product_id,
          ];
        })->toArray();
      } else {
        $site_data = [
          'id' => $site_setting->id,
          'field_name' => $site_setting->field_name,
          'field_key' => $site_setting->field_key,
          'field_output_value' => $site_setting->field_output_value,
          'category_id' => $site_setting->category_id,
          'sub_category_id' => $site_setting->sub_category_id,
          'store_id' => $site_setting->store_id,
          'product_id' => $site_setting->product_id,
        ];
      }

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Site data retrieved successfully.', 'toast' => true], ['site_data' => $site_data]);
    } catch (\Exception $e) {
      Log::info('File add error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing.', 'toast' => true]);
    }
  }

  public function marketplaceSlider(StoreSliderRequest $request)
  {
    try {
      $validatedData = $request->validated();
      $baseUploadPath = "uploads/slider/";
      $sliderData = [];
      foreach (['slider1', 'slider2', 'slider3'] as $slider) {
        if ($request->hasFile($slider)) {
          $image = $request->file($slider);
          $filename = time() . $slider . '.' . $image->getClientOriginalExtension();
          $uploadPath = $baseUploadPath . $filename;

          if (!Storage::exists($baseUploadPath)) {
            Storage::makeDirectory($baseUploadPath, 0755, true);
          }

          Storage::put($uploadPath, file_get_contents($image));

          $sliderData[$slider] = $uploadPath;
        }
      }

      $sliderData['image_text1'] = $validatedData['image_text1'];
      $sliderData['image_text2'] = $validatedData['image_text2'];
      $sliderData['image_text3'] = $validatedData['image_text3'];

      $slider = MarketplaceSlider::create($sliderData);

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Slider created successfully.', 'toast' => true], ['slider' => $slider]);
    } catch (\Exception $e) {
      Log::info('Slider creation error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing.', 'toast' => true]);
    }
  }

  public function updateSlider(StoreSliderRequest $request, $id)
  {
    try {
      $validatedData = $request->validated();
      $slider = MarketplaceSlider::findOrFail($id);

      $baseUploadPath = "uploads/slider/";

      foreach (['slider1', 'slider2', 'slider3'] as $sliderField) {
        if ($request->hasFile($sliderField)) {
          $image = $request->file($sliderField);
          $filename = time() . $sliderField . '.' . $image->getClientOriginalExtension();
          $uploadPath = $baseUploadPath . $filename;

          if (!Storage::exists($baseUploadPath)) {
            Storage::makeDirectory($baseUploadPath, 0755, true);
          }

          Storage::put($uploadPath, file_get_contents($image));

          $sliderData[$sliderField] = $uploadPath;
        } else {
          $sliderData[$sliderField] = $slider->$sliderField; // Retain old value
        }
      }

      $sliderData['image_text1'] = $validatedData['image_text1'] ?? $slider->image_text1;
      $sliderData['image_text2'] = $validatedData['image_text2'] ?? $slider->image_text2;
      $sliderData['image_text3'] = $validatedData['image_text3'] ?? $slider->image_text3;

      $slider->update($sliderData);

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Slider updated successfully.', 'toast' => true], ['slider' => $slider]);
    } catch (\Exception $e) {
      Log::info('Slider update error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing.', 'toast' => true]);
    }
  }
  public function getSlider($id)
  {
    try {
      $slider = MarketplaceSlider::findOrFail($id);

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Slider retrieved successfully.', 'toast' => true], ['slider' => $slider]);
    } catch (\Exception $e) {
      Log::info('Slider retrieval error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing.', 'toast' => true]);
    }
  }

  public function deleteSlider($id)
  {
    try {
      $slider = MarketplaceSlider::findOrFail($id);
      $slider->delete();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Slider deleted successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      Log::info('Slider deletion error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing.', 'toast' => true]);
    }
  }

  public function createPaidBanner(StorePaidBannerRequest $request)
  {
    try {
      // Retrieve the authenticated user from request attributes
      $user = $request->attributes->get('user');

      // Ensure the user is available and has an ID
      if (!$user || !$user->id) {
        return response()->json([
          'type' => 'error',
          'code' => 400,
          'status' => false,
          'message' => 'User not authenticated.',
          'toast' => true,
        ], 400);
      }

      // Assign the user ID to the validated data
      $validatedData = $request->validated();
      $validatedData['user_id'] = $user->id;

      // Handle the banner image upload
      if ($request->hasFile('banner_image')) {
        $image = $request->file('banner_image');
        $filename = time() . '.' . $image->getClientOriginalExtension();
        $uploadPath = 'uploads/banners/' . $filename;

        if (!Storage::exists('uploads/banners')) {
          Storage::makeDirectory('uploads/banners', 0755, true);
        }

        Storage::put($uploadPath, file_get_contents($image));
        $validatedData['banner_image'] = $uploadPath;
      }

      // Create the new banner record
      $banner = PaidBannerDisplay::create($validatedData);

      // Return success response
      return response()->json([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Banner created successfully.',
        'toast' => true,
        'banner' => $banner,
      ]);
    } catch (\Exception $e) {
      // Log the error and return error response
      Log::error('Error creating banner: ' . $e->getMessage());
      return response()->json([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error creating banner.',
        'toast' => true,
      ]);
    }
  }

  public function getPaidBanners(StorePaidBannerRequest $request)
  {
    try {
      // Retrieve the logged-in user
      $user = $request->attributes->get('user');
      if (!$user) {
        return generateResponse([
          'type' => 'error',
          'code' => 401,
          'status' => false,
          'message' => 'User not authenticated',
          'toast' => true
        ]);
      }

      // Get query parameters with default values
      $page = $request->input('page', 1);
      $limit = $request->input('limit', 10); // Number of results per page
      $offset = ($page - 1) * $limit;
      $searchKeyword = $request->input('search_keyword', null); // Optional search keyword
      $orderColumn = $request->input('order_column', 'created_at'); // Default order column
      $orderDir = $request->input('order_dir', 'desc'); // Default order direction

      // Build the query for fetching paid banners
      $query = PaidBannerDisplay::query();

      // Search functionality
      if ($searchKeyword) {
        $query->where(function ($q) use ($searchKeyword) {
          $q->where('link', 'like', '%' . $searchKeyword . '%')
            ->orWhere('title', 'like', '%' . $searchKeyword . '%')
            ->orWhere('subtitle', 'like', '%' . $searchKeyword . '%')
            ->orWhere('description', 'like', '%' . $searchKeyword . '%');
        });
      }

      // Get total records count before applying pagination
      $totalRecords = $query->count();

      // Apply pagination and sorting
      $query->offset($offset)->limit($limit);
      $query->orderBy($orderColumn, $orderDir);

      // Fetch the banners
      $banners = $query->get();

      // Check if banners exist
      if ($banners->isEmpty()) {
        return generateResponse([
          'type' => 'info',
          'code' => 200,
          'status' => true,
          'message' => 'No paid banners found',
          'toast' => false,
          'data' => ['paidBanners' => []]
        ]);
      }

      // Return success response with banners data
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Paid banners retrieved successfully',
        'toast' => false,
        'data' => [
          'paidBanners' => $banners->toArray(),
          'recordsFiltered' => $totalRecords,
          'recordsTotal' => $totalRecords,
          'page' => $page,
          'limit' => $limit
        ]
      ]);
    } catch (\Exception $e) {
      // Log the exception
      Log::error('getPaidBanners: ' . $e->getMessage(), ['exception' => $e]);

      // Return error response
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'An error occurred while retrieving paid banners',
        'toast' => true
      ]);
    }
  }


  public function getPaidBannersMerchant(Request $request)
  {
    try {
      // Retrieve the logged-in user
      $user = $request->attributes->get('user');
      if (!$user) {
        return response()->json([
          'type' => 'error',
          'code' => 401,
          'status' => false,
          'message' => 'User not authenticated',
          'toast' => true
        ], 401);
      }

      // Get query parameters with default values
      $page = $request->input('page', 1);
      $limit = $request->input('limit', 10); // Number of results per page
      $offset = ($page - 1) * $limit;
      $searchKeyword = $request->input('search_keyword', null); // Optional search keyword
      $orderColumn = $request->input('order_column', 'created_at'); // Default order column
      $orderDir = $request->input('order_dir', 'desc'); // Default order direction

      // Build the query for fetching paid banners for the logged-in user
      $query = PaidBannerDisplay::query()->where('user_id', $user->id);

      // Search functionality
      if ($searchKeyword) {
        $query->where(function ($q) use ($searchKeyword) {
          $q->where('link', 'like', '%' . $searchKeyword . '%')
            ->orWhere('title', 'like', '%' . $searchKeyword . '%')
            ->orWhere('subtitle', 'like', '%' . $searchKeyword . '%')
            ->orWhere('description', 'like', '%' . $searchKeyword . '%');
        });
      }

      // Get total records count before applying pagination
      $totalRecords = $query->count();

      // Apply pagination and sorting
      $query->offset($offset)->limit($limit);
      $query->orderBy($orderColumn, $orderDir);

      // Fetch the banners
      $banners = $query->get();

      // Check if banners exist
      if ($banners->isEmpty()) {
        return response()->json([
          'type' => 'info',
          'code' => 200,
          'status' => true,
          'message' => 'No paid banners found',
          'toast' => false,
          'data' => ['paidBanners' => []]
        ], 200);
      }

      // Return success response with banners data
      return response()->json([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Paid banners retrieved successfully',
        'toast' => false,
        'data' => [
          'paidBanners' => $banners->toArray(),
          'recordsFiltered' => $totalRecords,
          'recordsTotal' => $totalRecords,
          'page' => $page,
          'limit' => $limit
        ]
      ], 200);
    } catch (\Exception $e) {
      // Log the exception
      Log::error('getPaidBanners: ' . $e->getMessage(), ['exception' => $e]);

      // Return error response
      return response()->json([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'An error occurred while retrieving paid banners',
        'toast' => true
      ], 500);
    }
  }



  public function feedback_rating($product_id)
  {
    // Fetch Review Counts
    $reviews = DB::table('marketplace_store_product_reviews')
      ->select('rating', DB::raw('count(*) as no_of_people'))
      ->where('product_id', $product_id)
      ->groupBy('rating')
      ->get();

    // Calculate Total Stars and Count
    $totalStars = 0;
    $totalCount = 0;
    foreach ($reviews as $review) {
      $totalStars += $review->rating * $review->no_of_people;
      $totalCount += $review->no_of_people;
    }

    // Calculate Average Rating
    $averageRating = $totalCount > 0 ? $totalStars / $totalCount : 0;

    // Generate Star Rating HTML (rounded to the nearest half)
    $roundedRating = round($averageRating * 2) / 2;
    $starHtml = $this->generateStarRatingHtml($roundedRating);

    // Return Review Data
    return [
      'average_rating' => $averageRating,
      'total_review_count' => $totalCount,
      'star_rating_html' => $starHtml,
    ];
  }

  private function generateStarRatingHtml($rating)
  {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
      if ($rating >= $i) {
        $html .= '<i class="fas fa-star"></i>';
      } elseif ($rating >= ($i - 0.5)) {
        $html .= '<i class="fas fa-star-half-alt"></i>';
      } else {
        $html .= '<i class="far fa-star"></i>';
      }
    }
    return $html;
  }


  public function getsuperadminProductListrating(Request $request)
  {
    // Retrieve Input Parameters
    $page = $request->input('page', 1);
    $limit = $request->input('limit', 10);
    $offset = ($page - 1) * $limit;
    $searchKeyword = $request->input('search_keyword', null);
    $orderColumn = $request->input('order_column', 'created_at');
    $orderDir = $request->input('order_dir', 'desc');

    // Fetch Products
    $query = DB::table('marketplace_products')
      ->where('is_public', 'Y')
      ->where('status', 1);

    if ($searchKeyword) {
      $query->where(function ($q) use ($searchKeyword) {
        $q->where('product_name', 'LIKE', "%{$searchKeyword}%")
          ->orWhere('product_thumb_path', 'LIKE', "%{$searchKeyword}%")
          ->orWhere('product_image_path', 'LIKE', "%{$searchKeyword}%");
      });
    }

    $products = $query->orderBy($orderColumn, $orderDir)
      ->offset($offset)
      ->limit($limit)
      ->get();

    // Process Each Product
    $products = $products->map(function ($product) {
      $product->product_thumb_path = $this->generateImageHtml($product->product_thumb_path);
      $ratingData = $this->feedback_rating($product->id);
      $product->rate_count = $ratingData['average_rating'];
      $product->review_count = $ratingData['total_review_count'];
      $product->product_rating = $ratingData['star_rating_html'];
      return $product;
    });

    // Sort Products
    $products = $products->sortByDesc('rate_count');

    // Prepare and Return Response
    return generateResponse([
      'type' => 'success',
      'code' => 200,
      'status' => true,
      'message' => 'Product list fetched successfully.',
      'toast' => true
    ], [
      'data' => $products,
      'total_record_count' => $products->count(),
      'filtered_record_count' => $products->count()
    ]);
  }

  private function generateImageHtml($path)
  {
    return '<img src="' . asset($path) . '" alt="Product Image">';
  }


  public function getProductListrating(Request $request)
  {
    // Check if user is authenticated
    $user = $request->attributes->get('user');
    if (!$user) {
      return generateResponse([
        'type' => 'error',
        'code' => 401,
        'status' => false,
        'message' => 'User not authenticated.',
        'toast' => true
      ], []);
    }

    // Retrieve Input Parameters
    $page = $request->input('page', 1);
    $limit = $request->input('limit', 10);
    $offset = ($page - 1) * $limit;
    $searchKeyword = $request->input('search_keyword', null);
    $orderColumn = $request->input('order_column', 'created_at');
    $orderDir = $request->input('order_dir', 'desc');

    // Fetch Products for the Logged-in User
    $query = DB::table('marketplace_products')
      ->where('user_id', $user->id)
      ->where('status', 1);

    if ($searchKeyword) {
      $query->where(function ($q) use ($searchKeyword) {
        $q->where('product_name', 'LIKE', "%{$searchKeyword}%")
          ->orWhere('product_thumb_path', 'LIKE', "%{$searchKeyword}%")
          ->orWhere('product_image_path', 'LIKE', "%{$searchKeyword}%");
      });
    }

    $products = $query->orderBy($orderColumn, $orderDir)
      ->offset($offset)
      ->limit($limit)
      ->get();

    // Process Each Product
    $products = $products->map(function ($product) {
      $product->product_thumb_path = $this->generateImageHtml($product->product_thumb_path);
      $ratingData = $this->feedback_rating($product->id);
      $product->rate_count = $ratingData['average_rating'];
      $product->review_count = $ratingData['total_review_count'];
      $product->product_rating = $ratingData['star_rating_html'];
      return $product;
    });

    // Sort Products
    $products = $products->sortByDesc('rate_count');

    // Prepare and Return Response
    return generateResponse([
      'type' => 'success',
      'code' => 200,
      'status' => true,
      'message' => 'Product list fetched successfully.',
      'toast' => true
    ], [
      'data' => $products,
      'total_record_count' => $products->count(),
      'filtered_record_count' => $products->count()
    ]);
  }


  public function addShipper(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      if (!$user) {
        return generateResponse([
          'type' => 'error',
          'code' => 401,
          'status' => false,
          'message' => 'User not authenticated.',
          'toast' => true
        ], [], 401);
      }

      $merchantShipper = new MerchantShipper();
      $merchantShipper->shipper_user_id = $request->input('shipper_user_id');
      $merchantShipper->merchant_user_id = $user->id;
      $merchantShipper->save();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Shipper added successfully.',
        'toast' => true
      ], []);
    } catch (\Illuminate\Validation\ValidationException $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 422,
        'status' => false,
        'message' => 'Validation error.',
        'toast' => true
      ], [
        'errors' => $e->errors()
      ], 422);
    } catch (\Exception $e) {
      Log::error('Shipper creation error: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while adding shipper.',
        'toast' => true
      ], [], 500);
    }
  }



  public function tableMerchantShippers(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      if (!$user) {
        return generateResponse([
          'type' => 'error',
          'code' => 401,
          'status' => false,
          'message' => 'User not authenticated.',
          'toast' => true
        ], [], 401);
      }

      $start = $request->input('start', 0);
      $limit = $request->input('length', 10);
      $search = $request->input('search')['value'] ?? null;
      $orderColumnIndex = $request->input('order')[0]['column'] ?? 0;
      $orderColumn = $request->input('columns')[$orderColumnIndex]['data'] ?? 'id';
      $orderDir = $request->input('order')[0]['dir'] ?? 'asc';

      $query = MerchantShipper::with('user')
        ->where('merchant_user_id', $user->id);

      if ($search) {
        $query->whereHas('user', function ($q) use ($search) {
          $q->where('username', 'like', "%{$search}%")
            ->orWhere('phone_no', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%");
        });
      }

      $totalRecords = $query->count();

      $shippers = $query->orderBy($orderColumn, $orderDir)
        ->offset($start)
        ->limit($limit)
        ->get();

      $result = $shippers->map(function ($shipper, $index) use ($start) {
        return [
          'name' => $shipper->user->username ?? 'N/A',
          'id' => $start + $index + 1,
          'last_loggedin' => isset($shipper->user->last_seen) ? date("d M Y", strtotime($shipper->user->last_seen)) : 'N/A',
          'phone_no' => $shipper->user->phone_no ?? 'N/A',
          'email' => $shipper->user->email ?? 'N/A',
          'username' => '<span class="tb-product">' . $this->getUserAvatar($shipper->shipper_user_id) . '<span class="title">' . ($shipper->user->username ?? 'N/A') . '</span></span>'
        ];
      });

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Shippers list fetched successfully.',
        'toast' => true
      ], [
        'data' => $result,
        'total_record_count' => $totalRecords,
        'filtered_record_count' => $totalRecords,
        'draw' => $request->input('draw')
      ]);
    } catch (\Exception $e) {
      Log::error('Error fetching shippers: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while fetching shippers.',
        'toast' => true
      ], [], 500);
    }
  }

  private function getUserAvatar($userId)
  {
    return '<img src="path_to_avatar" alt="avatar">';
  }


  public function getMyOrderList(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      if (!$user) {
        return generateResponse([
          'type' => 'error',
          'code' => 401,
          'status' => false,
          'message' => 'User not authenticated.',
          'toast' => true
        ], [], 401);
      }

      $query = DB::table('marketplace_product_purchase_details')
        ->join('marketplace_stores', 'marketplace_product_purchase_details.store_id', '=', 'marketplace_stores.id')
        ->join('marketplace_products', 'marketplace_product_purchase_details.product_id', '=', 'marketplace_products.id')
        ->select(
          'marketplace_product_purchase_details.id',
          'marketplace_product_purchase_details.order_id',
          'marketplace_product_purchase_details.user_id',
          'marketplace_product_purchase_details.store_id',
          'marketplace_product_purchase_details.product_name',
          'marketplace_product_purchase_details.price',
          'marketplace_product_purchase_details.quantity',
          'marketplace_product_purchase_details.total_amount_with_discount',
          'marketplace_product_purchase_details.payment_status',
          'marketplace_product_purchase_details.created_date_time',
          'marketplace_product_purchase_details.order_status',
          'marketplace_stores.name as store_name',
          'marketplace_products.product_images',
          'marketplace_products.thumbnail',
          'marketplace_products.product_video',
          'marketplace_products.description'
        )
        ->where('marketplace_product_purchase_details.user_id', $user->id)
        // ->where('marketplace_product_purchase_details.payment_status', '1')
        // ->where('marketplace_stores.is_disabled', 'N')
      ;

      // Apply filters if they are present
      if ($request->filled('order_status')) {
        $orderStatuses = explode(',', $request->input('order_status'));
        $query->whereIn('marketplace_product_purchase_details.order_status', $orderStatuses);
      }

      if ($request->filled('store_name')) {
        $query->where('marketplace_stores.name', 'like', '%' . $request->input('store_name') . '%');
      }

      if ($request->filled('product_name')) {
        $query->where('marketplace_product_purchase_details.product_name', 'like', '%' . $request->input('product_name') . '%');
      }

      if ($request->filled('date_from') || $request->filled('date_to')) {
        if ($request->filled('date_from') && $request->filled('date_to')) {
          $query->whereBetween('marketplace_product_purchase_details.created_date_time', [
            $request->input('date_from') . ' 00:00:00',
            $request->input('date_to') . ' 23:59:59'
          ]);
        } elseif ($request->filled('date_from')) {
          $query->where('marketplace_product_purchase_details.created_date_time', '>=', $request->input('date_from') . ' 00:00:00');
        } elseif ($request->filled('date_to')) {
          $query->where('marketplace_product_purchase_details.created_date_time', '<=', $request->input('date_to') . ' 23:59:59');
        }
      }

      if ($request->filled('sort_by') && $request->filled('sort_order')) {
        $query->orderBy($request->input('sort_by'), $request->input('sort_order'));
      } else {
        $query->orderBy('marketplace_product_purchase_details.created_date_time', 'desc');
      }

      if ($request->filled('last_30_days') && $request->input('last_30_days') == 1) {
        $query->where('marketplace_product_purchase_details.created_date_time', '>=', \Carbon\Carbon::now()->subDays(30)->startOfDay());
      }

      if ($request->filled('year')) {
        $years = explode(',', $request->input('year'));
        $query->whereIn(DB::raw('YEAR(marketplace_product_purchase_details.created_date_time)'), $years);
      }

      // Pagination
      $perPage = $request->input('perPage', 10);
      $page = $request->input('page', 1);
      $totalRecords = $query->count();
      $ordersData = $query->forPage($page, $perPage)->get();

      $result = [];
      $orderSrNo = ($page - 1) * $perPage + 1;
      foreach ($ordersData as $order) {
        $deliveryCharge = $order->delivery_charge ?? 0;
        $priceQty = $order->price * $order->quantity;
        $price = $priceQty - ($priceQty * 0.029); // Assuming a 2.9% discount
        $totalAmount = $price + $deliveryCharge;

        $createdDateTime = $order->created_date_time;
        if (is_string($createdDateTime)) {
          $createdDateTime = \Carbon\Carbon::parse($createdDateTime);
        }

        $productImages = explode(',', $order->product_images);
        $productImageUrl = !empty($productImages[0]) ? url("uploads/{$productImages[0]}") : null;
        $thumbnailUrl = $order->thumbnail ? url("uploads/{$order->thumbnail}") : null;
        $productVideoUrl = $order->product_video ? url("uploads/{$order->product_video}") : null;

        $result[] = [
          'id' => $orderSrNo++,
          'order_id' => $order->order_id,
          'product_name' => $order->product_name,
          'store_name' => Str::limit($order->store_name, 50),
          'description' => Str::limit($order->description, 100),
          'delivery_charge' => number_format($deliveryCharge, 2),
          'created_date_time' => $createdDateTime ? $createdDateTime->format('d M Y H:i') : null,
          'price' => "$" . number_format($order->price, 2),
          'purchased_quantity' => $order->quantity,
          'total' => "$" . number_format($totalAmount, 2),
          'totalquantity' => "$" . number_format($priceQty, 2),
          'order_status' => $this->Dashboard($order->order_status ?? '0'),
          'product_image_url' => $productImageUrl,
          'thumbnail_url' => $thumbnailUrl,
          'product_video_url' => $productVideoUrl,
        ];
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Orders list fetched successfully.',
        'toast' => true
      ], [
        'data' => $result,
        'total_record_count' => $totalRecords,
        'filtered_record_count' => $totalRecords,
        'page' => $page,
        'per_page' => $perPage
      ]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while fetching orders.',
        'toast' => true
      ], [], 200);
    }
  }




  private function Dashboard($status)
  {
    $statuses = [
      '0' => 'Order Placed',
      '1' => 'Confirmed',
      '2' => 'Canceled',
      '3' => 'Shipped',
      '4' => 'Closed',
      '5' => 'Completed',
      '6' => 'Refund Requested',
      '7' => 'Return/Replace Requested'
    ];

    return $statuses[$status] ?? 'Unknown';
  }



  public function getDeliveryOrderList(Request $request)
  {
    try {
      $user = $request->attributes->get('user');

      if (!$user) {
        return generateResponse([
          'type' => 'error',
          'code' => 401,
          'status' => false,
          'message' => 'Unauthorized',
          'toast' => true
        ], [], 401);
      }

      $search = $request->input('search', '');
      $order = $request->input('order', []);

      // Default values for ordering
      $orderByColumn = 'id';  // Default column
      $orderByColumnVal = 'asc';  // Default direction

      // Ensure 'order' array is not empty and has the 'column' key
      if (!empty($order) && isset($order[0]['column'])) {
        $columns = $request->input('columns', []);
        $orderByColumn = $columns[$order[0]['column']]['data'] ?? 'id';
        $orderByColumnVal = $order[0]['dir'] ?? 'asc';
      }

      // Fetch order data
      $orderData = $this->getDeliveryOrderDetails($user->id, '', $search, [
        'sort_by_column' => $orderByColumn,
        'sort_by_val' => $orderByColumnVal
      ]);

      // Format and prepare data for response
      $result = [];
      foreach ($orderData as $orderDetails) {
        $result[] = [
          'order_id' => $orderDetails->order_id,
          'product_name' => "<b>" . $this->displayString(8, $orderDetails->product_name) . "</b>",
          'email_address' => $this->displayString(10, $orderDetails->email),
          'phone_no' => $this->displayString(10, $orderDetails->shipping_phone_number),
          'shipping_address' => $this->displayString(20, htmlspecialchars($orderDetails->shipping_city) . " , " . htmlspecialchars($orderDetails->shipping_state) . " , " . htmlspecialchars($orderDetails->shipping_country) . " , " . htmlspecialchars($orderDetails->shipping_postal_code)),
          'delivery_charge' => !empty($orderDetails->delivery_charge) ? "$" . $orderDetails->delivery_charge : '-',
          'created_date_time' => $orderDetails->created_date_time,
          'order_by' => $this->displayString(10, $orderDetails->username ?? $orderDetails->first_name . ' ' . $orderDetails->last_name),
          'purchased_quantity' => $orderDetails->purchased_quantity,
          'price' => "$" . $orderDetails->price,
          'total' => "$" . (number_format($orderDetails->price - ($orderDetails->price * 0.029), 2) + ($orderDetails->delivery_charge ?? 0)),
          'delivery_by' => $this->displayString(10, optional($this->getRecords('users', 'username', ['id' => $orderDetails->delivery_person_id]))->username ?? $this->getRecords('users', 'username', ['id' => $user->id])->username),
          'order_status' => $this->getOrderStatus($orderDetails->order_status)['status']
        ];
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Order list fetched successfully.',
        'toast' => true
      ], [
        'data' => $result,
        'total_record_count' => count($orderData),
        'filtered_record_count' => count($orderData),
        'draw' => $request->get('draw', 1)
      ]);
    } catch (\Exception $e) {
      Log::error('Delivery Order List fetch error: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching order list.',
        'toast' => true
      ], [], 500);
    }
  }


  public function getDeliveryOrderDetails($userId = null, $orderId = null, $search = [], $order = [])
  {
    $query = DB::table('marketplace_product_purchase_details as objUp')
      ->select(
        'objUp.id',
        'objUp.*',
        'objUp.quantity as purchased_quantity',
        'objSp.product_name as product_name',  // Updated column name
        'objSp.price',
        'objSp.delivery_charge',
        'objU.first_name',
        'objU.last_name',
        'objU.username',
        'objU.email',
        'objUp.shipping_city',
        'objUp.shipping_state',
        'objUp.shipping_country',
        'objUp.shipping_postal_code',
        'objUp.shipping_phone_number',
        'objUp.created_date_time',
        'objUp.delivery_person_id',
        'objUp.order_status'
      )
      ->join('marketplace_products as objSp', 'objSp.id', '=', 'objUp.product_id')
      ->join('users as objU', 'objU.id', '=', 'objUp.user_id');

    if ($userId) {
      $query->where('objSp.user_id', $userId);
    }

    if ($orderId) {
      $query->where('objUp.order_id', $orderId);
    }

    if (!empty($search)) {
      $search_keyword = $search['like'];
      $query->where(function ($q) use ($search_keyword) {
        $q->where('objSp.product_name', 'like', "%$search_keyword%")  // Updated column name
          ->orWhere('objU.username', 'like', "%$search_keyword%");
      });
    }

    if (isset($order['sort_by_column']) && isset($order['sort_by_val']) && is_array($order)) {
      $query->orderBy($order['sort_by_column'], $order['sort_by_val']);
    }

    $query->where('objUp.order_status', '3')
      ->orderBy('objUp.id', 'DESC');

    return $query->get()->toArray();
  }




  private function getRecords($table, $fields = '', $condition = '', $order_by = '', $limit = '', $debug = 0, $group_by = '')
  {
    $query = DB::table($table);

    if (is_array($fields) && !empty($fields)) {
      $query->select($fields);
    } elseif ($fields != '') {
      $query->select($fields);
    } else {
      $query->select('*');
    }

    if (is_array($condition) && !empty($condition)) {
      foreach ($condition as $field_name => $field_value) {
        $query->where($field_name, $field_value);
      }
    } elseif ($condition != '') {
      $query->whereRaw($condition);
    }

    if ($limit != '') {
      $query->limit($limit);
    }

    if (is_array($order_by) && !empty($order_by)) {
      $query->orderBy($order_by[0], $order_by[1]);
    } elseif ($order_by != '') {
      $query->orderByRaw($order_by);
    }

    if ($group_by != '') {
      $query->groupBy($group_by);
    }

    if ($debug) {
      // Print the SQL query for debugging purposes
      dd($query->toSql(), $query->getBindings());
    }

    return $query->first();
  }


  public function getOrderStatus($statusCode)
  {
    $statuses = [
      '1' => ['status' => 'Pending'],
      '2' => ['status' => 'Processed'],
      '3' => ['status' => 'Shipped'],
      '4' => ['status' => 'Delivered'],
      '5' => ['status' => 'Cancelled'],
    ];

    return $statuses[$statusCode] ?? ['status' => 'Unknown'];
  }


  private function getOrderStatusLabelDashboard($status)
  {
    $statuses = [
      '0' => 'Order Placed',
      '1' => 'Confirmed',
      '2' => 'Canceled',
      '3' => 'Shipped',
      '4' => 'Closed',
      '5' => 'Delivered',
      '6' => 'Refund completed',
      '7' => 'Return & replace Process'
    ];

    return $statuses[$status] ?? 'Unknown';
  }



  public function getMyOrderListDashboard(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      if (!$user) {
        return generateResponse([
          'type' => 'error',
          'code' => 401,
          'status' => false,
          'message' => 'User not authenticated.',
          'toast' => true
        ], [], 401);
      }

      $query = DB::table('marketplace_product_purchase_details')
        ->join('marketplace_stores', 'marketplace_product_purchase_details.store_id', '=', 'marketplace_stores.id')
        ->select(
          'marketplace_product_purchase_details.id',
          'marketplace_product_purchase_details.order_id',
          'marketplace_product_purchase_details.user_id',
          'marketplace_product_purchase_details.store_id',
          'marketplace_product_purchase_details.product_name',
          'marketplace_product_purchase_details.price',
          'marketplace_product_purchase_details.quantity',
          'marketplace_product_purchase_details.total_amount_with_discount',
          'marketplace_product_purchase_details.payment_status',
          'marketplace_product_purchase_details.created_date_time',
          'marketplace_product_purchase_details.order_status',
          'marketplace_stores.name as store_name'
        )
        ->where('marketplace_product_purchase_details.user_id', $user->id)
        ->where('marketplace_product_purchase_details.payment_status', '1')
        ->where('marketplace_stores.is_disabled', 'N');

      if ($request->has('search.value')) {
        $searchKeyword = strtolower($request->input('search.value'));
        $query->where(function ($q) use ($searchKeyword) {
          $q->whereRaw('LOWER(marketplace_product_purchase_details.product_name) LIKE ?', ["%{$searchKeyword}%"])
            ->orWhereRaw('LOWER(marketplace_stores.name) LIKE ?', ["%{$searchKeyword}%"]);
        });
      }

      if ($request->has('column') && $request->has('value')) {
        $filterColumn = $request->input('column');
        $filterValue = $request->input('value');
        $query->where('marketplace_product_purchase_details.' . $filterColumn, $filterValue);
      }

      if ($request->has('order.0.column') && $request->has('order.0.dir')) {
        $orderByColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
        $orderByDirection = $request->input('order.0.dir');
        $query->orderBy($orderByColumn, $orderByDirection);
      } else {
        $query->orderBy('marketplace_product_purchase_details.created_date_time', 'desc');
      }
      $query->limit(4);
      $totalRecords = $query->count();
      $ordersData = $query->get();

      $result = [];
      $orderSrNo = 1;
      foreach ($ordersData as $order) {
        $deliveryCharge = $order->delivery_charge ?? 0;
        $priceQty = $order->price * $order->quantity;
        $price = $priceQty - ($priceQty * 0.029);
        $totalAmount = $price + $deliveryCharge;

        $createdDateTime = $order->created_date_time;
        if (is_string($createdDateTime)) {
          $createdDateTime = \Carbon\Carbon::parse($createdDateTime);
        }

        $result[] = [
          'id' => $orderSrNo++,
          'order_id' => $order->order_id,
          'product_name' => "<b>" . Str::limit($order->product_name, 50) . "</b>",
          'store_name' => Str::limit($order->store_name, 50),
          'delivery_charge' => number_format($deliveryCharge, 2),
          'created_date_time' => $createdDateTime ? $createdDateTime->format('d M Y H:i') : null,
          'username' => Str::limit($order->username ?? '', 50),
          'delivery_person' => $order->delivery_person ?? $user->username,
          'price' => "$" . number_format($order->price, 2),
          'purchased_quantity' => $order->quantity,
          'total' => "$" . number_format($totalAmount, 2),
          'totalquantity' => "$" . number_format($order->price * $order->quantity, 2),
          'order_status' => $this->getOrderStatusLabelDashboard($order->order_status ?? '0'),
          'action' => "<a href='marketplace-order-details/{$order->id}'><em class='icon ni ni-eye' style='font-size:20px;'></em></a>"
        ];
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Order list fetched successfully.',
        'toast' => true
      ], [
        'data' => $result,
        'total_record_count' => $totalRecords,
        'filtered_record_count' => $totalRecords,
        'draw' => $request->input('draw', 1)
      ]);
    } catch (\Exception $e) {
      Log::error('My Order List Dashboard fetch error: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching order list.',
        'toast' => true
      ], [], 500);
    }
  }



  public function orderDetailsReview($userId = null, $orderId = null, $condition = [])
  {
    $query = DB::table('marketplace_product_purchase_details')
      ->select([
        'marketplace_product_purchase_details.*',
        'marketplace_stores.name as store_name',
        'marketplace_products.store_id',
        'marketplace_products.product_name as name',
        'marketplace_products.description',
        'marketplace_products.category_id',
        'marketplace_products.sub_category_id',
        'marketplace_products.category_tag_id',
        'marketplace_products.qr_code_image',
        'marketplace_products.qr_code_image_ext',
        'marketplace_products.product_type',
        'marketplace_products.model_no_item_no',
        'marketplace_products.delivery_type',
        'marketplace_products.product_image_path',
        'marketplace_products.product_document_attachment',
        'marketplace_products.publisher_application_id',
        'marketplace_products.checkout_qr_code_image',
        'marketplace_products.checkout_qr_code_image_ext',
        'marketplace_products.machine_checkout_qr_code_image',
        'marketplace_products.machine_checkout_qr_code_image_ext',
        'marketplace_products.featured_product_id',
        'marketplace_products.is_public',
        'users.first_name',
        'users.last_name',
        'users.username',
        'marketplace_product_purchase_details.id as id',
        'marketplace_products.delivery_charge',
        'marketplace_product_purchase_details.delivery_person_id'
      ])
      ->join('marketplace_stores', 'marketplace_product_purchase_details.store_id', '=', 'marketplace_stores.id')
      ->join('marketplace_products', 'marketplace_products.id', '=', 'marketplace_product_purchase_details.product_id')
      ->join('users', 'users.id', '=', 'marketplace_product_purchase_details.user_id')
      ->where('marketplace_stores.is_disabled', 'N')
      ->where('marketplace_product_purchase_details.type', '5')
      ->orderBy('marketplace_product_purchase_details.id', 'DESC');

    if (!empty($userId)) {
      $query->where('marketplace_products.user_id', $userId);
    }

    if (!empty($orderId)) {
      $query->where('marketplace_product_purchase_details.order_id', $orderId);
    }

    if (!empty($condition)) {
      foreach ($condition as $field_name => $field_value) {
        if ($field_name != '' && $field_value != '') {
          $query->where('marketplace_product_purchase_details.' . $field_name, $field_value);
        }
      }
    }

    // Log the query for debugging
    Log::info($query->toSql());
    Log::info($query->getBindings());

    return $query->get()->toArray();
  }

  public function getStoreList(Request $request)
  {
    try {
      $search = $request->input('search');
      $page = $request->input('page', 1); // Default to 1 if not provided
      $status = $request->input('status');
      $limit = 10;
      $start = ($page - 1) * $limit;
      $condition1 = [];

      if ($status) {
        $condition1['is_disabled'] = $status;
      }

      if (!empty($search['value'])) {
        $condition1['name'] = $search['value'];
      }

      $user = $request->attributes->get('user');
      $userId = $user->id;
      $storeIds = $this->getAccessStoreId($userId);

      $userGeneratedStore = MarketplaceStore::where('user_id', $userId)->pluck('id')->toArray();
      if (!empty($userGeneratedStore)) {
        $storeIds = array_merge($storeIds, $userGeneratedStore);
      }

      $stores = $this->getStoreListByIDArr($storeIds, $start, $limit, $condition1);

      $recordsFiltered = count($stores);
      $result = [];

      // Fetch live token values
      $liveTokenRate = getTokenMetricsValues();

      foreach ($stores as $storeData) {
        $products = MarketplaceProducts::where('store_id', $storeData['id'])->get();
        $storeData['badge'] = $storeData['user_id'] != $userId
          ? '<img class="badge_img" src="' . asset('assets/cloud/images/pro_member.svg') . '" data-toggle="tooltip" rel="tooltip" title="Authorized store" >'
          : '';

        $storeData['product_limit'] -= count($products);
        $storeData['no_of_product'] = count($products);

        if ($storeData['image_path']) {
          $imagePath = 'uploads/store/images/1226x355/' . $storeData['image_path'];
          $storeData['thumbnail'] = '<img class="card-img-top" alt="' . Str::limit($storeData['description'], 100) . "-" . Str::limit($storeData['name'], 30) . "-" . basename($storeData['image_path']) . '" src="' . url('storage/' . $imagePath) . '" />';
          $storeData['thumbnail_path'] = url('uploads/' . $imagePath);
        }

        $storeData['product_type'] = $storeData['is_disabled'] == 'N'
          ? '<span class="tb-status text-success">Active</span>'
          : '<span class="tb-status text-info">Inactive</span>';

        $storeData['store_link'] = ($request->post('access_store') == "store-access" || $storeData['user_id'] != $userId)
          ? url('marketplace-access-product-list/' . $this->createSlug($storeData['id']))
          : "#";

        // Include live token rate in the result
        $storeData['livetokenprice'] = $liveTokenRate;

        $storeData['id'] = $storeData['id']; // Removed unnecessary increment
        $storeData['bannerdescription'] = $storeData['description'];
        $storeData['bannername'] = $storeData['name'];
        $storeData['description'] = Str::limit($storeData['description'], 25);
        unset($storeData['qr_code_image']);
        $result[] = $storeData;
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Store list fetched successfully.',
        'toast' => true
      ], [
        'data' => $result,
        'recordsFiltered' => $recordsFiltered,
        'recordsTotal' => $recordsFiltered,
        'draw' => $request->input('draw')
      ]);
    } catch (\Exception $e) {
      Log::error('Get Store List error: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching store list.',
        'toast' => true
      ], [], 500);
    }
  }



  private function getStoreListByIDArr($storeIds, $start, $limit, $condition1)
  {
    $stores = MarketplaceStore::whereIn('id', $storeIds)
      ->where($condition1)
      ->offset($start)
      ->limit($limit)
      ->get()
      ->toArray();

    return $stores;
  }

  private function isFileExists($path, $defaultPath)
  {
    return file_exists(public_path($path)) ? asset($path) : asset($defaultPath);
  }

  private function createSlug($id)
  {
    return 'store-' . $id;
  }

  public function getAccessStoreId($userId)
  {
    $storeIds = DB::table('marketplace_store_user_permission')
      ->whereRaw("JSON_SEARCH(allowed_permissions, 'one', ?, NULL, '$.user_role[*].user_id') IS NOT NULL", [$userId])
      ->pluck('store_id')
      ->toArray();

    return $storeIds;
  }



  public function getProductList(Request $request)
  {
    try {
      $start = $request->input('start', 0);
      $limit = $request->input('length', 10);
      $search = $request->input('search', []);
      $page = $request->input('page', 1);

      $query = MarketplaceProducts::query();

      // Adjust pagination logic based on the 'page' parameter
      if ($page > 1) {
        $limit = 100;
        $start = ($page - 1) * $limit;
      }

      // Apply sorting
      if ($request->has('order')) {
        $order_by_column = $request->input('columns')[$request->input('order')[0]['column']]['data'];
        $order_by_column_val = $request->input('order')[0]['dir'];
        $query->orderBy($order_by_column, $order_by_column_val);
      } else {
        $query->orderBy('id', 'desc');
      }

      $user = $request->attributes->get('user');
      $userId = $user->id;
      $query->where('user_id', $userId);

      // Apply search filters
      if (!empty($search['value'])) {
        $query->where('product_name', 'LIKE', "%{$search['value']}%");
      }

      // Apply stock status filter
      if (!empty($search['is_out_of_stock'])) {
        if ($search['is_out_of_stock'] == '0') {
          $query->where('is_out_of_stock', '0');
        } elseif ($search['is_out_of_stock'] == '3') {
          $query->where('quantity', '<', 'stock_notify_before_qnt');
        }
      }

      // Apply public status filter
      if (!empty($search['is_public'])) {
        $query->where('is_public', $search['is_public']);
      }

      // Apply product status filter
      if (!empty($search['status'])) {
        $query->where('status', $search['status']);
      }

      // Apply pagination
      if ($limit) {
        $query->offset($start)->limit($limit);
      }

      // Fetch the products
      $products = $query->get();
      $recordsFiltered = $query->count();

      // Fetch the live token rate
      $liveTokenPrice = getTokenMetricsValues();
      $liveTokenRate = $liveTokenPrice;

      $result = [];
      foreach ($products as $productdata) {
        $store = MarketplaceStore::find($productdata->store_id);
        $category = MarketplaceCategory::find($productdata->category_id);

        // Calculate the discounted price
        $discounted_price = $productdata->price - ($productdata->price * ($productdata->discount_percentage / 100));

        // Check if paid_price is empty, and if so, assign the discounted price
        $final_paid_price = !empty($productdata->paid_price) ? $productdata->paid_price : $discounted_price;

        $productData = [
          'id' => $productdata->id,
          'store' => $store ? $store->name : '',
          'store_id' => $productdata->store_id,
          'bannername' => $productdata->product_name,
          'banner_img' => $productdata->thumbnail ? url('uploads/' . $productdata->thumbnail) : '',
          'sku' => $productdata->sku,
          'category' => $category ? $category->category_name : '',
          'livetokenprice' => $liveTokenRate,
          'img' => '<img src="' . (ProductisFileExists('uploads/' . $productdata->thumbnail) ? url('uploads/' . $productdata->thumbnail) : '') . '" style="height:80px; width:auto;"/>',
          'price' => '$' . $productdata->price,

          // Assign the final paid price (discounted if paid_price is empty)
          'paid_price' => '$' . number_format($final_paid_price, 2),

          'description' => displayString(25, displayCharacter(str_replace('<br>', ' ', $productdata->description), 3)),
          'displayaction' => '
                        <ul class="link-list-opt no-bdr">
                            <li><a href="https://www.facebook.com/sharer/sharer.php?u=' . url('/' . $productdata->id . '/' . Str::slug($productdata->name)) . '" target="_blank"><em class="icon ni ni-facebook-f"></em></a></li>
                            <li><a href="http://twitter.com/share?text=I wanted you to check out this Product&amp;url=' . url('/' . $productdata->id . '/' . Str::slug($productdata->name)) . '" target="_blank"><em class="icon ni ni-twitter"></em></a></li>
                            <li><a href="mailto:?subject=I wanted you to check out this Product&amp;body=' . url('/' . $productdata->id . '/' . Str::slug($productdata->name)) . '" target="_blank"><em class="icon ni ni-mail"></em></a></li>
                        </ul>',
          'is_public' => $productdata->is_public == 'Y' ? '<span class="tb-status text-success">Active</span>' : '<span class="tb-status text-info">Inactive</span>',
          'status' => $productdata->status == '1' ? '<div class="product-status m-auto bg-success text-white"><em class="icon ni ni-check-thick" data-toggle="tooltip" data-placement="top" title="Approved"></em></div>' : ($productdata->status == '2' ? '<div class="product-status m-auto bg-danger text-white" data-toggle="tooltip" data-placement="top" title="Rejected"><em class="icon ni ni-cross-circle"></em></div>' : ($productdata->status == '3' ? '<div class="product-status m-auto bg-warning text-white" data-toggle="tooltip" data-placement="top" title="Pending"><em class="icon ni ni-clock"></em></div>' : '')),
        ];
        $result[] = $productData;
      }

      $totalRecords = MarketplaceProducts::count();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Product list fetched successfully.',
        'toast' => true
      ], [
        'data' => $result,
        'total_record_count' => $totalRecords,
        'filtered_record_count' => $recordsFiltered,
        'draw' => $request->input('draw', 1)
      ]);
    } catch (\Exception $e) {
      Log::error('Product List fetch error: ' . $e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching product list.',
        'toast' => true
      ], [], 500);
    }
  }



  public function addPermission(Request $request)
  {
    try {
      $postData = $request->all();

      // Check if role is an array
      if (!is_array($postData['role'])) {
        return generateResponse([
          'type' => 'error',
          'code' => 400,
          'status' => false,
          'message' => 'Role data must be an array.',
          'toast' => true
        ], [], 400);
      }

      $userAccess = [];
      foreach ($postData['role'] as $roleId) {
        $columnName = $this->getMenuColumnNamePermission($roleId);
        $access = [
          'store_id' => $postData['store_id'],
          'user_id' => $postData['modal_search_keyword'],  // Assuming modal_search_keyword represents the user ID
          'role_id' => $roleId
        ];

        if ($columnName) {
          $access['add_access'] = $postData['addAccess'] ?? null;
          $access['edit_access'] = $postData['editAccess'] ?? null;
          $access['view_access'] = $postData['viewAccess'] ?? null;
          $access['view_approve'] = $postData['view_approveAccess'] ?? null;
          $access['inquiries'] = $postData['inquiryAccess'] ?? null;
        }

        $userAccess[] = $access;
      }

      $permission = MarketplaceStoreUserPermission::updateOrCreate(
        ['store_id' => $postData['store_id']],
        ['allowed_permissions' => json_encode(['user_role' => $userAccess])]
      );

      if ($permission) {
        $user = User::find($postData['modal_search_keyword']);
        if (!$user) {
          return generateResponse([
            'type' => 'error',
            'code' => 404,
            'status' => false,
            'message' => 'User not found.',
            'toast' => true
          ], [], 404);
        }

        $merchant = $request->attributes->get('user');
        if (!$merchant) {
          return generateResponse([
            'type' => 'error',
            'code' => 404,
            'status' => false,
            'message' => 'Merchant not found.',
            'toast' => true
          ], [], 404);
        }

        $store = MarketplaceStore::find($postData['store_id']);
        if (!$store) {
          return generateResponse([
            'type' => 'error',
            'code' => 404,
            'status' => false,
            'message' => 'Store not found.',
            'toast' => true
          ], [], 404);
        }

        $accessString = '';
        if (isset($postData['addAccess']))
          $accessString .= 'add';
        if (isset($postData['editAccess']))
          $accessString .= ', edit';
        if (isset($postData['viewAccess']))
          $accessString .= ', view';
        if (isset($postData['view_approveAccess']))
          $accessString .= ' and activate';
        if (isset($postData['inquiryAccess']))
          $accessString .= ' and respond to inquiry';

        $data = [
          'name' => $user->username,
          'merchant_name' => $merchant->first_name . ' ' . $merchant->last_name,
          'store_name' => $store->name,
          'access_names' => $accessString
        ];
        sendAccessGrantedNotification($user, $store, $accessString);

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'User Access information stored successfully.',
          'toast' => true
        ], []);
      } else {
        return generateResponse([
          'type' => 'error',
          'code' => 500,
          'status' => false,
          'message' => 'Please try again later.',
          'toast' => true
        ], [], 500);
      }
    } catch (\Exception $e) {
      Log::error('Add Permission error: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error processing request.',
        'toast' => true
      ], [], 500);
    }
  }

  private function getMenuColumnNamePermission($role_id)
  {
    $columnNames = [
      1 => 'is_administrator',
      2 => 'is_moderator',
      3 => 'is_manager',
      4 => 'is_contributor',
      5 => 'is_support'
    ];

    return $columnNames[$role_id] ?? '';
  }



  public function getMerchantVisitorsProductList(Request $request)
  {
    $user = $request->attributes->get('user');
    $userId = $user->id;
    $start = $request->input('start', 0);
    $limit = $request->input('length', 10);
    $search = $request->input('search');
    $categoryFilter = $request->input('category');
    $subcategoryFilter = $request->input('subcategory');
    $subscribers = $request->input('subscribers');

    // Get interest categories
    $interestCategories = MarketplaceSiteSubscription::where('user_id', $userId)
      ->where('is_deleted', 0)
      ->pluck('interest_category')
      ->toArray();

    $interestCategoryCounts = [];
    foreach ($interestCategories as $categoryString) {
      $categoryIds = array_filter(explode(',', str_replace(['[', ']', '"'], '', $categoryString)));
      foreach ($categoryIds as $categoryId) {
        if (!isset($interestCategoryCounts[$categoryId])) {
          $interestCategoryCounts[$categoryId] = 0;
        }
        $interestCategoryCounts[$categoryId]++;
      }
    }

    // Get product visit counts based on subscription status
    $visitedCategoriesQuery = MarketplaceSiteSubscription::select('product_visited_count')
      ->whereNotNull('user_id');

    if ($subscribers !== null) {
      $visitedCategoriesQuery->where('is_deleted', $subscribers);
    } else {
      $visitedCategoriesQuery->where('is_deleted', 0);
    }

    $visitedCategories = $visitedCategoriesQuery->distinct()->get()->pluck('product_visited_count')->toArray();

    $productVisitedCounts = [];
    foreach ($visitedCategories as $visitedCategory) {
      $productVisitedArray = json_decode($visitedCategory, true);
      foreach ($productVisitedArray as $productId => $count) {
        if (!isset($productVisitedCounts[$productId])) {
          $productVisitedCounts[$productId] = 0;
        }
        $productVisitedCounts[$productId] += $count;
      }
    }

    // Fetch products based on visit counts
    $productVisitedCountsIds = array_keys($productVisitedCounts);
    $products = MarketplaceProducts::whereIn('id', $productVisitedCountsIds)
      ->where('user_id', $userId)
      ->get();

    // Filter related products based on interest categories
    $categoryIds = array_keys($interestCategoryCounts);
    $relatedProducts = $products->filter(function ($product) use ($categoryIds) {
      return in_array($product->category_id, $categoryIds);
    });

    // Apply filters
    if ($categoryFilter) {
      $relatedProducts = $relatedProducts->filter(function ($product) use ($categoryFilter) {
        return $product->category_id == $categoryFilter;
      });
    }

    if ($subcategoryFilter) {
      $relatedProducts = $relatedProducts->filter(function ($product) use ($subcategoryFilter) {
        return $product->sub_category_id == $subcategoryFilter;
      });
    }

    // Apply search
    if ($search && $search['value']) {
      $searchValue = strtolower($search['value']);
      $relatedProducts = $relatedProducts->filter(function ($product) use ($searchValue) {
        return stripos(strtolower($product->name), $searchValue) !== false ||
          stripos(strtolower($product->category_name), $searchValue) !== false ||
          stripos(strtolower($product->subcategory_name), $searchValue) !== false ||
          stripos(strtolower(strval($product->count)), $searchValue) !== false;
      });
    }

    // Sort results (example sorting by product name)
    $relatedProducts = $relatedProducts->sortBy(function ($product) {
      return strtolower($product->name);
    });

    // Paginate results
    $totalRecords = $relatedProducts->count();
    $result = $relatedProducts->slice($start, $limit)->values();

    // Generate category and subcategory filter options
    $categoryIds = array_unique($result->pluck('category_id')->toArray());
    $categoryOptions = '<option value="">All Categories</option>';
    foreach ($categoryIds as $categoryId) {
      $category = MarketplaceCategory::find($categoryId);
      $categoryOptions .= '<option value="' . $categoryId . '">' . ($category->category_name ?? '') . '</option>';
    }

    $subcategoryIds = array_unique($result->pluck('sub_category_id')->toArray());
    $subcategoryOptions = '<option value="">All Subcategories</option>';
    foreach ($subcategoryIds as $subcategoryId) {
      $subcategory = MarketplaceSubCategory::find($subcategoryId);
      $subcategoryOptions .= '<option value="' . $subcategoryId . '">' . ($subcategory->sub_category_name ?? '') . '</option>';
    }

    // Generate HTML for map (if applicable)
    $visitedCountries = []; // Your existing logic for visited countries

    // Map initialization script
    $newHtmlResponseMap = '<div id="map"></div>';
    $markers = '';
    foreach ($visitedCountries as $countryData) {
      $country = $countryData['country'];
      $lat = $countryData['latitude'];
      $lng = $countryData['longitude'];

      if (!empty($lat) && !empty($lng)) {
        $markers .= "addMarker($lat, $lng, '$country');";
      }
    }

    $newHtmlResponseMap .= '
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>
    <script>
        function initMap() {
            var map = new google.maps.Map(document.getElementById("map"), {
                zoom: 2,
                center: { lat: 0, lng: 0 }
            });
            function addMarker(lat, lng, country) {
                var marker = new google.maps.Marker({
                    position: { lat: lat, lng: lng },
                    map: map,
                    title: country
                });
            }
            ' . $markers . '
        }
    </script>';

    return response()->json([
      'recordsTotal' => $totalRecords,
      'recordsFiltered' => $totalRecords,
      'data' => $result,
      'map' => $newHtmlResponseMap,
      'filterOptions' => [
        'categories' => $categoryOptions,
        'subcategories' => $subcategoryOptions
      ]
    ]);
  }


  public function filterElements(Request $request)
  {
    try {
      $param1 = $request->input('param1');
      $param2 = $request->input('param2');
      $param3 = $request->input('param3');

      $data = [];

      if (!empty($param2) && in_array($param1, ['stores', 'storecat'])) {
        $data['stores'] = [];
        $data['categories'] = MarketplaceCategory::where('id', $param2)->get();
        $data['subCategories'] = MarketplaceSubCategory::where('parent_category_id', $param2)->get();
      } elseif (!empty($param2) && in_array($param1, ['categories', 'subcat'])) {
        $data['stores'] = [];
        $data['categories'] = [];
        $data['subCategories'] = MarketplaceSubCategory::where('parent_category_id', $param2)
          ->where('id', $param3)
          ->get();
      } elseif (!empty($param2) && in_array($param1, ['subcategories', 'subcat'])) {
        $data['stores'] = [];
        $data['categories'] = [];
        $subCateData = MarketplaceSubCategory::where('id', $param2)->first();
        $data['subCategories'] = MarketplaceSubCategory::where('parent_category_id', $subCateData->category_id)
          ->where('id', $param3)
          ->get();
      } else {
        $stores = MarketplaceStore::all();
        $stores = $stores->sortByDesc('product_count')->unique('id')->values()->all();
        $data['stores'] = $stores;

        $categories = MarketplaceCategory::all();
        $categories = $categories->sortByDesc('product_count')->unique('id')->values()->all();
        $data['categories'] = $categories;

        $subCategories = MarketplaceSubCategory::limit(5)->get();
        $data['subCategories'] = $subCategories;
      }

      $totalRecords = count($data['stores']) + count($data['categories']) + count($data['subCategories']);
      $recordsFiltered = $totalRecords; // Adjust according to your filtering logic

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Product list fetched successfully.',
        'toast' => true
      ], [
        'data' => $data,
        'total_record_count' => $totalRecords,
        'filtered_record_count' => $recordsFiltered,
        'draw' => $request->input('draw', 1)
      ]);
    } catch (\Exception $e) {
      Log::error('Product List fetch error: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching product list.',
        'toast' => true
      ], [], 500);
    }
  }


  public function emailUnsubscription(Request $request)
  {
    DB::beginTransaction(); // Start the transaction

    try {
      $email = $request->input('email_address');

      // Validate the email address input
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return generateResponse([
          'type' => 'error',
          'code' => 400,
          'status' => false,
          'message' => 'Invalid email address.',
          'toast' => true
        ], [], 400);
      }

      $subscription = MarketplaceSiteSubscription::where('email_address', $email)->first();

      if ($subscription) {
        $subscription->is_deleted = 1;
        $subscription->save();

        $this->unsubscribeEmail($email);

        // Commit the transaction
        DB::commit();

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Unsubscribed successfully.',
          'toast' => true
        ]);
      } else {
        // Rollback the transaction in case no subscription is found
        DB::rollBack();
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Email address not found.',
          'toast' => true
        ]);
      }
    } catch (\Exception $e) {
      // Rollback the transaction in case of an exception
      DB::rollBack();

      Log::error('Unsubscription error: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error processing unsubscription.',
        'toast' => true
      ], [], 500);
    }
  }

  public function emailResubscription(Request $request)
  {
    DB::beginTransaction(); // Start the transaction

    try {
      $email_resubscribe = $request->input('email_address');

      // Validate the email address input
      if (!filter_var($email_resubscribe, FILTER_VALIDATE_EMAIL)) {
        return generateResponse([
          'type' => 'error',
          'code' => 400,
          'status' => false,
          'message' => 'Invalid email address.',
          'toast' => true
        ], [], 400);
      }

      $subscription = MarketplaceSiteSubscription::where('email_address', $email_resubscribe)->first();

      if ($subscription) {
        $subscription->is_deleted = 0;
        $subscription->save();

        $this->subscribeEmail($email_resubscribe);

        // Commit the transaction
        DB::commit();

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Subscribed successfully.',
          'toast' => true
        ]);
      } else {
        // Rollback the transaction if no subscription is found
        DB::rollBack();
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Email address not found.',
          'toast' => true
        ]);
      }
    } catch (\Exception $e) {
      // Rollback the transaction in case of an exception
      DB::rollBack();

      Log::error('Resubscription error: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error processing resubscription.',
        'toast' => true
      ], [], 500);
    }
  }



  public function subscribe(Request $request)
  {
    try {
      // Begin a database transaction
      DB::beginTransaction();

      $data = $request->only([
        'email_address',
        'interest_category',
        'notification_period',
        'notification_cnt',
        'product_visited_log'
      ]);

      // Ensure 'marketplace' is set to 1
      $data['marketplace'] = 1;

      $subscription = MarketplaceSiteSubscription::updateOrCreate(
        ['email_address' => $data['email_address']],
        array_merge($data, ['is_deleted' => 0])
      );

      if ($subscription->wasRecentlyCreated) {
        // Call the subscribeEmail method
        $this->subscribeEmail($data['email_address']);

        $response = generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Subscribed successfully.',
          'toast' => true
        ]);
      } elseif ($subscription->is_deleted == 1) {
        // Re-subscription logic
        $response = generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Resubscribed successfully.',
          'toast' => true
        ]);
      } else {
        $response = generateResponse([
          'type' => 'error',
          'code' => 409,
          'status' => false,
          'message' => 'Subscription already exists.',
          'toast' => true
        ]);
      }

      // Commit the transaction
      DB::commit();

      return $response;
    } catch (\Exception $e) {
      // Rollback the transaction in case of error
      DB::rollBack();

      Log::error('Subscription error: ' . $e->getMessage());

      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error processing subscription.',
        'toast' => true
      ], [], 500);
    }
  }

  private function subscribeEmail($email_address)
  {
    $subject = 'Welcome to SiloCloud!';
    $encoded_email = base64_encode($email_address);
    $encoded_email = str_replace('=', '', $encoded_email);
    $unsubscribe_url = url('marketplace/unsubscribe_email/' . $encoded_email);

    Mail::send('mail-templates.marketplace_mail_subscription', ['unsubscribe_url' => $unsubscribe_url], function ($mail) use ($email_address, $subject) {
      $mail->to($email_address)->subject($subject);
    });
  }


  private function unsubscribeEmail($email_address)
  {
    $subject = 'You have unsubscribed from SiloCloud emails';
    $subscribe_url = url('marketplace/');

    Mail::send('mail-templates.admin_mail_unsubscribe', ['resubscribe_url' => $subscribe_url], function ($mail) use ($email_address, $subject) {
      $mail->to($email_address)->subject($subject);
    });
  }


  public function getAdminMerchantEarnings(Request $request)
  {
    try {
      $result = [];
      $i = 1;
      $user = $request->attributes->get('user');
      $userId = $user->id;

      $condition = ['order_status' => '4'];

      switch ($request->input('merchant')) {
        case 'merchant':
          $ordersData = $this->orderDetailsReviewexcel($userId, null, $condition, 0, PHP_INT_MAX);
          break;
        case 'admin-merchant':
          $ordersData = $this->orderDetailsReviewexcel(null, null, $condition, 0, PHP_INT_MAX);
          break;
        case 'merchantclosed':
        case 'admin-merchant-closed':
          $condition = [
            ['objUp.order_status', '!=', '4'],
            ['objUp.order_status', '!=', '2'],
            ['objUp.order_status', '!=', '6'],
            ['objUp.order_status', '!=', '7'],
          ];
          $ordersData = $this->orderDetailsReviewexcel($request->input('merchant') === 'merchantclosed' ? $userId : null, null, $condition, 0, PHP_INT_MAX);
          break;
        default:
          $ordersData = $this->orderDetailsReviewexcel(null, null, $condition, 0, PHP_INT_MAX);
      }

      Log::info('Orders Data:', ['ordersData' => $ordersData]);

      $getUserDetails = getUserDetails($userId);

      if ($ordersData instanceof \Illuminate\Http\JsonResponse) {
        $ordersData = json_decode($ordersData->getContent(), true);
      }

      if (!empty($ordersData)) {
        $groupedData = [];
        $silo = $request->input('dataid') === "" ? "Silo" : '';
        $productEarnings = 0;
        $productCommissionTotal = 0;

        foreach ($ordersData as $productData) {
          $total = (float) $productData->price * (float) $productData->quantity;
          $productCommission = $total * 0.029;
          $productEarnings = $total - $productCommission;

          $product = [
            "id" => $productData->product_id,
            "date" => $productData->created_date_time,
            "username" => $productData->username,
            "commission" => $productCommission, // Make sure commission is correctly assigned
            "data" => $productData->price * $productData->quantity,
            "SR" => $i++,
            "product_name" => $productData->product_name,
            "price" => $productEarnings,
            "per_price" => $productData->price,
            "quantity" => $productData->quantity,
            "first_name" => $getUserDetails['first_name'],
            "last_name" => $getUserDetails['last_name'],
            "store_name" => $productData->store_name
          ];

          $filter = date("Y-m-d", strtotime($product["date"])) . "_" . $product["username"] . "_" . $product["id"];

          if (!isset($groupedData[$filter])) {
            $groupedData[$filter] = [
              "totalEarnings" => 0,
              "totalCommission" => 0,
              "products" => []
            ];
          }

          $groupedData[$filter]["totalEarnings"] += $productEarnings;
          $groupedData[$filter]["totalCommission"] += $productCommission;
          $groupedData[$filter]["products"][] = $product;
        }



        $productTotal = [];
        $totalEarnings = 0;

        foreach ($groupedData as $group) {
          foreach ($group["products"] as $product) {
            $productId = $product["id"];
            if (!isset($productTotal[$productId])) {
              $productTotal[$productId] = [
                "product_name" => $product["product_name"],
                "totalEarnings" => 0,
                "totalCommission" => 0,
                "totalQuantity" => 0,
                "first_name" => $product["first_name"],
                "last_name" => $product["last_name"],
                "store_name" => $product["store_name"],
                "price" => $product["per_price"]
              ];
            }

            $productTotal[$productId]["totalEarnings"] += $product["price"];
            $productTotal[$productId]["totalQuantity"] += $product["quantity"];
            $productTotal[$productId]["totalCommission"] += (float) $product["commission"];

            Log::info('Product Aggregation:', [
              'productId' => $productId,
              'totalEarnings' => $productTotal[$productId]["totalEarnings"],
              'totalCommission' => $productTotal[$productId]["totalCommission"]
            ]);
          }
        }

        $result = array_values($productTotal);

        $totalEarnings = array_sum(array_column($result, 'totalEarnings'));
        $totalCommission = array_sum(array_column($result, 'totalCommission'));
        $result = array_values($productTotal);

        $totalEarnings = array_sum(array_column($result, 'totalEarnings'));
        $totalCommission = array_sum(array_column($result, 'totalCommission'));


        if ($request->input('datatype') === 'pdf') {
          $options = new Options();
          $options->set('isHtml5ParserEnabled', true);
          $options->set('isPhpEnabled', true);
          $options->set('defaultFont', 'Arial');
          $dompdf = new Dompdf($options);

          $html = view('silo_marketplace.marketplace_dashboard.pdf_template', [
            'data' => $result,
            'totalearnings' => $totalEarnings,
            'totalcommission' => $totalCommission,
            'earningDetailsHeading' => 'Earnings Report'
          ])->render();
          $dompdf->loadHtml($html);
          $dompdf->setPaper('A4', 'portrait');
          $dompdf->render();
          $output = $dompdf->output();

          $pdfFilePath = 'pdfs/earnings_report.pdf';
          Storage::put($pdfFilePath, $output);

          $publicUrl = Storage::url($pdfFilePath);
          return response()->json(['url' => $publicUrl]);
        } elseif ($request->input('datatype') === 'excel') {
          $filename = "earnings_report_" . date('YmdHis') . '.xlsx';
          $export = new ExportMplace($result);

          $publicPath = 'excel';
          if (!File::isDirectory(storage_path("app/public/{$publicPath}"))) {
            File::makeDirectory(storage_path("app/public/{$publicPath}"), 0755, true, true);
          }

          $path = "{$publicPath}/{$filename}";
          Excel::store($export, $path, 'public');

          $filePath = storage_path("app/public/{$path}");
          if (File::exists($filePath)) {
            return response()->download($filePath, $filename, [
              'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
              'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ])->deleteFileAfterSend(true);
          } else {
            return response()->json(['error' => 'File not found.'], 404);
          }
        }
      }
    } catch (\Exception $e) {
      Log::error('Failed to generate report: ' . $e->getMessage());
      return response()->json(['error' => 'Failed to generate report.', 'message' => $e->getMessage()], 500);
    }
  }

  public function orderDetailsReviewexcel($userId = null, $orderId = null, $condition = [], $start, $limit)
  {
    $query = DB::table('marketplace_product_purchase_details as objUp')
      ->select(
        'objUp.id',
        'objUp.product_id',
        'objUp.quantity as purchased_quantity',
        'objSm.name as store_name',
        'objSp.store_id',
        'objSp.product_name as name', // Updated this line
        'objSp.description',
        'objSp.category_id',
        'objSp.sub_category_id',
        'objSp.category_tag_id',
        'objSp.qr_code_image',
        'objSp.qr_code_image_ext',
        'objSp.product_type',
        'objSp.model_no_item_no',
        'objSp.delivery_type',
        'objSp.product_image_path',
        'objSp.product_document_attachment',
        'objSp.publisher_application_id',
        'objSp.checkout_qr_code_image',
        'objSp.checkout_qr_code_image_ext',
        'objSp.machine_checkout_qr_code_image',
        'objSp.machine_checkout_qr_code_image_ext',
        'objSp.featured_product_id',
        'objSp.is_public',
        'objU.first_name',
        'objU.last_name',
        'objU.username',
        'objUp.id as id',
        'objSp.delivery_charge',
        'objSp.price', // Added this line
        'objUp.quantity',
        'objUp.product_name',
        'objUp.created_date_time as created_date_time'
      )
      ->join('marketplace_stores as objSm', 'objUp.store_id', '=', 'objSm.id')
      ->join('marketplace_products as objSp', 'objSp.id', '=', 'objUp.product_id')
      ->join('users as objU', 'objU.id', '=', 'objUp.user_id')
      ->where('objSm.is_disabled', 'N')
      ->where('objUp.type', 5);

    if (!empty($userId)) {
      $query->where('objSp.user_id', $userId);
    }

    if (!empty($orderId)) {
      $query->where('objUp.order_id', $orderId);
    }

    if (!empty($condition) && !is_assoc($condition)) {
      foreach ($condition as $cond) {
        $query->where($cond);
      }
    } elseif (is_assoc($condition)) {
      foreach ($condition as $field_name => $field_value) {
        if (!empty($field_name) && !empty($field_value)) {
          $query->where('objUp.' . $field_name, $field_value);
        }
      }
    }

    $query->orderBy('objUp.id', 'DESC')
      ->offset($start)
      ->limit($limit);

    return $query->get();
  }


  public function getMerchantEarnings(Request $request)
  {
    try {
      $result = [];
      $data = [];
      $productEarnings = 0;
      $productCommissionTotal = 0;
      $total = 0;
      $user = $request->attributes->get('user');

      // Condition for fetching orders
      $condition = ['mppd.order_status' => '4']; // '4' represents Closed orders

      // Fetching order data
      $ordersData = DB::table('marketplace_product_purchase_details as mppd')
        ->join('marketplace_stores as ms', 'mppd.store_id', '=', 'ms.id')
        ->join('marketplace_products as mp', 'mp.id', '=', 'mppd.product_id')
        ->join('users as u', 'u.id', '=', 'mppd.user_id')
        ->select(
          'mppd.*',
          'mp.product_name as product_name',
          'ms.name as store_name'
        )
        ->where($condition)
        ->where('mp.user_id', $user->id)
        ->orderBy('mppd.created_date_time', 'asc')
        ->get();

      $productsCount = 0;
      $productIds = [];

      // Process orders data
      foreach ($ordersData as $order) {
        $productEarnings += (float) $order->total_amount_with_discount;
        $productCommissionTotal += (float) $order->price;

        // Track product IDs and counts
        if (!in_array($order->product_id, $productIds)) {
          $productIds[] = $order->product_id;
          $productsCount++;
        }

        // Organize data
        $data[] = [
          'product_name' => $order->product_name,
          'store_name' => $order->store_name ?? 'N/A',
          'quantity' => $order->quantity,
          'price' => $order->price,
          'total_amount_with_discount' => $order->total_amount_with_discount,
          'created_date_time' => $order->created_date_time,
        ];
      }

      // Final calculations
      $total = $productEarnings - $productCommissionTotal;

      // Prepare final result
      $result = [
        'total_earnings' => $productEarnings,
        'total_commission' => $productCommissionTotal,
        'net_earnings' => $total,
        'products_count' => $productsCount,
        'orders_data' => $data,
      ];

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Merchant earnings retrieved successfully.',
        'toast' => true
      ], $result, 200);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error retrieving merchant earnings.',
        'toast' => true
      ], [], 500);
    }
  }

  public function getSuperadminOrderList(Request $request)
  {
    try {
      // Define pagination parameters
      $perPage = $request->input('length', 10); // Number of items per page (default is 10)
      $page = $request->input('start', 0) / $perPage + 1; // Current page

      // Get the order status filter from the request
      $statusFilter = $request->input('status', null);

      $query = DB::table('marketplace_product_purchase_details as objUp')
        ->select(
          'objUp.*',
          'objSm.name as store_name',
          'objSp.product_image_path',
          'objU.username as buyer_name',
          'objUM.username as merchant'
        )
        ->leftJoin('marketplace_stores as objSm', 'objUp.store_id', '=', 'objSm.id')
        ->leftJoin('marketplace_products as objSp', 'objSp.id', '=', 'objUp.product_id')
        ->leftJoin('users as objU', 'objU.id', '=', 'objUp.user_id')
        ->leftJoin('users as objUM', 'objUM.id', '=', 'objSp.user_id')
        ->where('objSp.id', '!=', '')
        ->where('objUp.type', '5')
        ->where('objUp.payment_status', '1')
        ->orderBy('objUp.id', 'DESC'); // Default ordering

      // Apply status filter if provided
      if ($statusFilter !== null) {
        // Ensure the status filter is a valid integer
        if (array_key_exists($statusFilter, $this->orderStatusMap())) {
          $query->where('objUp.order_status', $statusFilter);
        } else {
          throw new \Exception('Invalid order status filter.');
        }
      }

      // Fetch paginated records
      $ordersData = $query->paginate($perPage, ['*'], 'page', $page);

      // Process the data
      $processedData = collect($ordersData->items())->map(function ($order) {
        // Ensure numeric conversion
        $price = isset($order->price) ? (float) $order->price : 0.0;
        $quantity = isset($order->quantity) ? (float) $order->quantity : 0.0;
        $deliveryCharge = isset($order->delivery_charge) ? (float) $order->delivery_charge : 0.0;

        // Calculate amounts
        $total = $price * $quantity;
        $adminReceived = $total * 0.029; // No need for number_format here
        $merchantReceived = $total - $adminReceived + $deliveryCharge;

        // Use number_format to format the final results
        $total = number_format($total, 2);
        $adminReceived = number_format($adminReceived, 2);
        $merchantReceived = number_format($merchantReceived, 2);

        // Order status mapping
        $orderStatus = $this->orderStatusMap();

        return [
          'order_id' => displayString(5, $order->id),
          'product_name' => "<b>" . displayString(8, $order->product_name) . "</b>",
          'store_name' => $order->store_name,
          'price' => "$" . number_format($price, 2),
          'quantity' => $quantity, // Adjusted field name
          'total' => "$" . $total,
          'admin_received' => "$" . $adminReceived,
          'merchant_received' => "$" . $merchantReceived,
          'merchant' => displayString(8, $order->merchant),
          'buyer_name' => displayString(8, $order->buyer_name),
          'delivery_charge' => number_format($deliveryCharge, 2),
          'created_date_time' => date('d M Y H:i', strtotime($order->created_date_time)),
          'status' => "<p><label class='label label-info' id='status_{$order->id}'>" . $orderStatus[$order->order_status] . "</label></p>",
          'id' => $order->id
        ];
      });

      // Return the paginated response
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Orders retrieved successfully.',
        'toast' => false
      ], [
        'data' => $processedData,
        'recordsFiltered' => $ordersData->total(),
        'recordsTotal' => $ordersData->total(),
        'draw' => $request->input('draw')
      ], 200);
    } catch (\Exception $e) {
      // Handle the exception and return an error response
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error retrieving orders.',
        'toast' => true
      ], [], 500);
    }
  }

  // Helper method to get the order status map
  protected function orderStatusMap()
  {
    return [
      '0' => 'Order Placed',
      '1' => 'Confirmed',
      '2' => 'Canceled',
      '3' => 'Shipped',
      '4' => 'Closed',
      '5' => 'Delivered',
      '6' => 'Refund Completed',
      '7' => 'Return & Replace Request'
    ];
  }


  public function updateOrderStatus(Request $request)
  {
    DB::beginTransaction(); // Start the transaction

    try {
      $orderId = $request->input('order_id');
      $productId = $request->input('product_id');
      $status = $request->input('status');

      // Validate the order and product existence
      $orderProductExists = DB::table('marketplace_product_purchase_details')
        ->where('order_id', $orderId)
        ->where('product_id', $productId)
        ->exists();

      if (!$orderProductExists) {
        DB::rollBack();
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Order ID or Product ID not found.',
        ], [], 404);
      }

      // Update shipping address if provided
      if ($request->filled('shipping_address')) {
        $updateData = [
          'shipping_address' => $request->input('shipping_address'),
          'shipping_phone_number' => $request->input('shipping_phone_number'),
          'shipping_country' => $request->input('shipping_country'),
          'shipping_state' => $request->input('shipping_state'),
          'shipping_city' => $request->input('shipping_city'),
          'shipping_postal_code' => $request->input('shipping_postal_code'),
          'shipping_email_id' => $request->input('shipping_email_id'),
        ];

        $updateStatus = MarketplaceProductPurchaseDetail::where('order_id', $orderId)
          ->where('product_id', $productId)
          ->update($updateData);

        if (!$updateStatus) {
          DB::rollBack();
          return generateResponse([
            'type' => 'error',
            'code' => 400,
            'status' => false,
            'message' => 'Failed to update shipping address.',
          ], [], 400);
        }
      }

      // Update order status if provided
      if ($request->filled('status')) {
        $orderStatus = $request->input('status');
        if ($orderStatus === '2') {
          // If status is 'cancelled', handle cancellation
          DB::rollBack();
          return $this->cancelOrderTransaction($request);
        }

        $updateStatus = MarketplaceProductPurchaseDetail::where('order_id', $orderId)
          ->where('product_id', $productId)
          ->update(['order_status' => $orderStatus]);

        if (!$updateStatus) {
          DB::rollBack();
          return generateResponse([
            'type' => 'error',
            'code' => 400,
            'status' => false,
            'message' => 'Failed to update order status.',
          ], [], 400);
        }

        $logDescription = $this->getOrderStatusDescription($orderStatus);
        $this->sendEmailAlertUpdateOrder($orderId);
        DB::commit();
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => $logDescription,
        ], [], 200);
      }

      // Add order comment if provided
      if ($request->filled('order_comment')) {
        $logDescription = $request->input('order_comment');
        $logType = '2'; // Assuming this is a predefined log type

        $logData = [
          'description' => $logDescription,
          'log_type' => $logType,
          'order_id' => $orderId,
          'user_id' => $request->attributes->get('user') ? $request->attributes->get('user')->id : Auth::id(),
        ];

        $logOrder = MarketplaceProductOrderLogDetail::create($logData);

        if (!$logOrder) {
          DB::rollBack();
          return generateResponse([
            'type' => 'error',
            'code' => 400,
            'status' => false,
            'message' => 'Failed to add comment.',
          ], [], 400);
        }

        DB::commit();
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Comment Added.',
        ], [], 200);
      }

      DB::rollBack();
      return generateResponse([
        'type' => 'error',
        'code' => 400,
        'status' => false,
        'message' => 'No action performed.',
      ], [], 400);
    } catch (\Exception $e) {
      DB::rollBack(); // Rollback the transaction in case of an exception
      Log::error('Update Order Status error: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error updating order status for the product.',
      ], [], 500);
    }
  }


  public function cancelOrderTransaction(Request $request)
  {
    $order_id = $request->input('order_id');
    $user = $request->attributes->get('user');

    // Retrieve all products related to the order_id
    $orderItems = MarketplaceProductPurchaseDetail::where('order_id', $order_id)->get();

    if ($orderItems->isEmpty()) {
      return response()->json([
        'type' => 'error',
        'code' => 404,
        'status' => false,
        'message' => 'Order not found',
        'toast' => true
      ]);
    }

    foreach ($orderItems as $order) {
      $mainTransactionLog = TokenTransactionLog::find($order->payment_id);
      $augerFeeTransactionLog = TokenTransactionLog::find($order->auger_fee_payment_id);

      if (!$mainTransactionLog || !$augerFeeTransactionLog) {
        return response()->json([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Transaction logs not found for one or more products',
          'toast' => true
        ]);
      }

      $receiver_id = $mainTransactionLog->receiver_id;
      $sender_id = $mainTransactionLog->sender_id;
      $order_total_amount = $order->total_amount_with_discount;

      $token_value = getTokenMetricsValues();
      $no_of_tokens = $order_total_amount / $token_value;

      $auger_fee_percentage = config('app.auger_fee');
      $auger_tokens = $no_of_tokens * ($auger_fee_percentage / 100);

      $total_tokens = $no_of_tokens + $auger_tokens;

      $sender_user = User::find($sender_id);
      $receiver_user = User::find($receiver_id);

      if (!$sender_user || !$receiver_user) {
        return response()->json([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'User not found for one or more products',
          'toast' => true
        ]);
      }

      try {
        DB::transaction(function () use ($sender_user, $receiver_user, $no_of_tokens, $auger_tokens, $total_tokens, $mainTransactionLog, $augerFeeTransactionLog, $order) {
          updateTransaction($mainTransactionLog->id, $no_of_tokens, "Updated for Order Cancellation: {$order->order_id}", '2');
          updateTransaction($augerFeeTransactionLog->id, $auger_tokens, "Updated for Auger Fee Refund: {$order->order_id}", '2');

          $order->update([
            'order_status' => '2',
            'payment_status' => '0',
          ]);

          $sender_user->account_tokens += $total_tokens;
          $receiver_user->account_tokens -= $total_tokens;

          $sender_user->save();
          $receiver_user->save();
        });
      } catch (\Exception $e) {
        return response()->json([
          'type' => 'error',
          'code' => 500,
          'status' => false,
          'message' => 'Order cancellation failed for one or more products',
          'toast' => true
        ]);
      }
    }

    return response()->json([
      'type' => 'success',
      'code' => 200,
      'status' => true,
      'message' => 'Order canceled and tokens refunded successfully for all products.',
      'toast' => true
    ]);
  }

  private function getOrderStatusDescription($status)
  {
    switch ($status) {
      case '1':
        return "Order Status changed to Order Confirmed";
      case '0':
        return "Order Status changed to Order Placed";
      case '2':
        return "Order Status changed to Order Rejected";
      case '3':
        return "Order Status changed to Order Shipped";
      case '4':
        return "Order Status changed to Order Closed";
      default:
        return "Order Status changed";
    }
  }



  public function sendEmailAlertUpdateOrder($orderId = null)
  {
    if ($orderId) {
      try {
        $customersOrderDetails = MarketplaceProductPurchaseDetail::with(['store', 'product', 'user'])
          ->where('order_id', $orderId)
          ->where('type', '5')
          ->where('payment_status', '1')
          ->first();

        if (!$customersOrderDetails) {
          return 'No order details found.';
        }

        $sellerId = $customersOrderDetails->store->user_id ?? null;

        if (!$sellerId) {
          return 'Seller details not found.';
        }

        $sellerDetails = User::where('id', $sellerId)
          ->where('status', '0')
          ->first();

        if (!$sellerDetails) {
          return 'Seller details not found.';
        }

        $orderStatuses = [
          '0' => 'Order Placed',
          '1' => 'Order Confirmed',
          '2' => 'Order Rejected',
          '3' => 'Order Shipped',
          '4' => 'Order Closed'
        ];

        $paymentStatuses = [
          '0' => 'Unpaid',
          '1' => 'Paid'
        ];

        $data = [
          'subject' => "Product Order on Scandisc.net: {$customersOrderDetails->product->product_name}",
          'logoUrl' => asset('assets/images/logo/logo-dark.png'),
          'sellerName' => "{$sellerDetails->first_name} {$sellerDetails->last_name}",
          'orderId' => $customersOrderDetails->order_id,
          'orderStatus' => $orderStatuses[$customersOrderDetails->order_status] ?? 'Unknown Status',
          'paymentStatus' => $paymentStatuses[$customersOrderDetails->payment_status] ?? 'Unknown Status',
          'productName' => $customersOrderDetails->product->product_name,
          'productLink' => url('uploads/' . $customersOrderDetails->product->product_id),
          'customerName' => "{$customersOrderDetails->user->first_name} {$customersOrderDetails->user->last_name}",
          'shippingAddress' => $customersOrderDetails->shipping_address,
          'shippingCity' => $customersOrderDetails->shipping_city,
          'shippingPostalCode' => $customersOrderDetails->shipping_postal_code,
          'shippingState' => $customersOrderDetails->shipping_state,
          'shippingCountry' => $customersOrderDetails->shipping_country,
          'shippingPhoneNumber' => $customersOrderDetails->shipping_phone_number,
          'shippingEmailId' => $customersOrderDetails->shipping_email_id,
          'projectName' => 'Scandisc.net',
        ];

        $sellerDetails->notify(new OrderUpdateNotification($data));

        return 'Email sent successfully.';
      } catch (\Exception $e) {
        Log::error($e->getMessage());
        return 'An error occurred while sending the email.';
      }
    }

    return 'Order ID is required.';
  }

  public function getMarketplaceRecordswithOffset($table, $fields = '', $condition = [], $orderBy = '', $perPage = 10, $page = 1, $search = [])
  {
    $query = DB::table($table);

    if (is_array($fields) && !empty($fields)) {
      $query->select($fields);
    } elseif (!empty($fields)) {
      $query->selectRaw($fields);
    } else {
      $query->select('*');
    }

    if (!empty($condition)) {
      $query->where($condition);
    }

    if (!empty($search)) {
      foreach ($search as $field => $value) {
        if (!empty($field) && !empty($value)) {
          $query->where($field, 'like', '%' . $value . '%');
        }
      }
    }

    if (!empty($orderBy)) {
      if (is_array($orderBy) && count($orderBy) === 2) {
        $query->orderBy($orderBy[0], $orderBy[1]);
      } elseif (!empty($orderBy)) {
        $query->orderByRaw($orderBy);
      }
    }

    $offset = ($page - 1) * $perPage;
    $query->limit($perPage)->offset($offset);

    return $query->get()->toArray();
  }

  public function getSuperAdminProductList(Request $request)
  {
    $param1 = $request->segment(2);
    $param2 = $request->segment(3);
    $perPage = $request->input('perPage', 10);
    $page = $request->input('page', 1);
    $search = $request->input('search', []);
    $status = $request->input('status');
    $notifyStatus = "false";
    $condition = [];
    $condition3 = [];

    $isProduct1Displayed = $request->input('isProduct1Displayed', 'false');

    if (!empty($status)) {
      if ($status === '0') {
        $condition3['quantity'] = 0;
        $notifyStatus = "true";
      } elseif ($status === '3') {
        $condition3[] = ['stock_notify_before_qnt', '>', 'quantity'];
        $notifyStatus = "true";
      }
    }

    if ($param1 === 'store' && !empty($param2)) {
      $condition['store_id'] = $param2;
    } elseif ($param1 === 'merchant' && !empty($param2)) {
      $condition['user_id'] = $param2;
    }

    $orderByColumn = $request->input('orderByColumn', 'id');
    $orderByColumnVal = $request->input('orderByDirection', 'asc');

    // Retrieve Admin Store
    $adminStore = DB::table('marketplace_stores')->where(['name' => 'Admin Store', 'user_id' => null])->first();

    if (!$adminStore) {
      return response()->json(['error' => 'Admin Store not found'], 404);
    }

    if ($isProduct1Displayed === "false") {
      $products = $this->getMarketplaceRecordswithOffset(
        'marketplace_products',
        '*',
        $condition3,
        [$orderByColumn, $orderByColumnVal],
        $perPage,
        $page,
        $search
      );
    } elseif ($isProduct1Displayed === "true" && empty($status) && $status !== '0' && $status !== '3') {
      $products = DB::table('marketplace_products')
        ->where(['user_id' => null, 'store_id' => $adminStore->id])
        ->get();
    } else {
      $products = $this->getMarketplaceRecordswithOffset(
        'marketplace_products',
        '*',
        $condition3,
        [$orderByColumn, $orderByColumnVal],
        $perPage,
        $page,
        $search
      );
    }

    $recordsFiltered = count($products);
    $result = [];
    foreach ($products as $i => $productdata) {
      $store = DB::table('marketplace_stores')->where('id', $productdata->store_id)->first();
      if (!$store || ($store->store === "Admin Store" && $isProduct1Displayed !== "true")) {
        continue;
      }

      $productdata->store = !empty($store->store) ? Str::limit($store->store, 10) : '';
      $productdata->bannername = $productdata->product_name;
      $productdata->product_name = Str::limit($productdata->product_name, 10);
      $descrPro = str_replace('<br>', ' ', $productdata->description);
      $productdata->description = Str::limit($descrPro, 25);
      $productdata->bannerdescription = $descrPro;

      $category = DB::table('marketplace_category')->where('id', $productdata->category_id)->first();
      $productdata->category = !empty($category->category_name) ? $category->category_name : '';
      $subcategory = DB::table('marketplace_sub_category')->where('id', $productdata->sub_category_id)->first();
      $productdata->subcategory = !empty($subcategory->sub_category_name) ? $subcategory->sub_category_name : '';

      $productdata->productstatus = $isProduct1Displayed === "true";
      $productdata->notifystatus = $notifyStatus;
      $productdata->status = $this->getStatusHtml($productdata->status);
      $productdata->is_public = $productdata->is_public === 'Y' ? '<span class="tb-status text-success">Active</span>' : '<span class="tb-status text-info">Inactive</span>';
      $productdata->page = "";
      $productdata->id = $i + 1;
      $img = ProductisFileExists($productdata->product_thumb_path, $productdata->product_image_path);
      $productdata->image = "<img class='lazy' src='" . $img . "' height='70px' width='auto'>";
      unset($productdata->qr_code_image, $productdata->checkout_qr_code_image, $productdata->machine_checkout_qr_code_image);
      $productdata->actions = [
        'preview' => "<a href='https://demo.silocloud.org/marketplace/product/{$productdata->id}'><em class='icon ni ni-eye'></em><span>Preview</span></a>",
        'notify' => "<a href='#' id='prod_id' data-url='https://demo.silocloud.org/marketplace/product/{$productdata->id}' data-id='{$productdata->id}' class='allow-notification'><em class='icon ni ni-notify'></em> Notify Subscribers</a>",
        'promote' => "<span id='promote'><em class='icon ni ni-share'></em><span>Promote</span>
                    <ul class='link-list-opt no-bdr Wei_Wuxian'>
                        <li><a href='https://www.facebook.com/sharer/sharer.php?u=https://demo.silocloud.org/marketplace/product/{$productdata->id}' target='_blank'><em class='icon ni ni-facebook-f'></em><span>Facebook</span></a></li>
                        <li><a href='http://twitter.com/share?text=I wanted you to check out this Product&url=https://demo.silocloud.org/marketplace/product/{$productdata->id}' target='_blank'><em class='icon ni ni-twitter'></em><span>Twitter</span></a></li>
                        <li><a href='mailto:?subject=I wanted you to check out this Product&body=https://demo.silocloud.org/marketplace/product/{$productdata->id}' target='_blank'><em class='icon ni ni-mail'></em><span>Mail</span></a></li>
                    </ul>
                </span>",
        'inquiry' => "<a href='https://demo.silocloud.org/marketplace-question-answer/{$productdata->id}'><em class='icon ni ni-question'></em><span>Inquiry</span></a>",
        'unblock' => "<a href='#' data-product-id='{$productdata->id}' data-status-id='1' class='btn-product-status'><em class='icon ni ni-na'></em><span>Unblock</span></a>",
        'make_inactive' => "<a href='#' data-product-id='{$productdata->id}' data-action-id='Y' class='btn-product-active'><em class='icon ni ni-cross-circle'></em><span>Make Inactive</span></a>"
      ];
      $result[] = $productdata;
    }

    $result = $this->filterResults($result, $search);
    $recordsFiltered = count($result);

    if ($isProduct1Displayed === "true" || (empty($status))) {
      $totalRecords = count(DB::table('marketplace_products')->where(['user_id' => null, 'store_id' => $adminStore->id])->get());
    } elseif (!empty($status)) {
      $totalRecords = count($this->getMarketplaceRecordswithOffset(
        'marketplace_products',
        '*',
        $condition3,
        [],
        null,
        null,
        []
      ));
    } else {
      $totalRecords = count($this->getMarketplaceRecordswithOffset(
        'marketplace_products',
        '*',
        $condition3,
        [],
        null,
        null,
        []
      ));
    }

    $result = array_slice($result, ($page - 1) * $perPage, $perPage);

    $response = [
      'data' => $result,
      'recordsFiltered' => $recordsFiltered,
      'recordsTotal' => $totalRecords,
      'draw' => $request->input('draw'),
    ];

    return response()->json($response);
  }


  protected function getStatusHtml($status)
  {
    switch ($status) {
      case 'active':
        return '<span class="tb-status text-success">Active</span>';
      case 'inactive':
        return '<span class="tb-status text-info">Inactive</span>';
      case 'pending':
        return '<span class="tb-status text-warning">Pending</span>';
      default:
        return '<span class="tb-status text-muted">Unknown</span>';
    }
  }

  function ProductisFileExists($thumbPath, $imagePath)
  {
    $fullPath = public_path($imagePath);

    if (file_exists($fullPath)) {
      return asset($imagePath);
    }

    $thumbFullPath = public_path($thumbPath);
    if (file_exists($thumbFullPath)) {
      return asset($thumbPath);
    }

    return asset('images/default-thumbnail.png');
  }

  protected function filterResults($results, $search)
  {
    if (empty($search)) {
      return $results;
    }

    return array_filter($results, function ($item) use ($search) {
      foreach ($search as $term) {
        // Ensure $term is an array with 'field' and 'value'
        if (isset($term['field']) && isset($term['value'])) {
          $field = $term['field'];
          $value = $term['value'];

          // Check if the item has the field and if the value matches
          if (!isset($item->$field) || stripos($item->$field, $value) === false) {
            return false;
          }
        }
      }
      return true;
    });
  }



  public function getBuyerList(Request $request)
  {
    $start = $request->input('start', 0);
    $limit = $request->input('length', 10);
    $search_keyword = $request->input('search.value', '');
    $order = $request->input('order', []);

    // Base query with joins and where condition
    $query = User::select(
      'users.id as buyer_id',
      'users.first_name',
      'users.last_name',
      'users.email',
      'users.username',
      DB::raw('MAX(user_profiles.profile_image_path) as user_image'),
      DB::raw('MAX(user_profiles.address_1) as home_address'),
      DB::raw('MAX(user_profiles.phone_number) as mobile'),
      DB::raw('MAX(marketplace_product_purchase_details.order_id) as order_id'),
      DB::raw('MAX(marketplace_product_purchase_details.price) as price'),
      DB::raw('MAX(marketplace_product_purchase_details.product_name) as product_name')
    )
      ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
      ->join('marketplace_product_purchase_details', 'marketplace_product_purchase_details.user_id', '=', 'users.id')
      ->where('marketplace_product_purchase_details.type', 5)
      ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.username')
      ->when($search_keyword, function ($query) use ($search_keyword) {
        $query->where(function ($q) use ($search_keyword) {
          $q->where('users.first_name', 'like', "%{$search_keyword}%")
            ->orWhere('users.username', 'like', "%{$search_keyword}%")
            ->orWhere('users.email', 'like', "%{$search_keyword}%")
            ->orWhere('user_profiles.phone_number', 'like', "%{$search_keyword}%");
        });
      })
      ->when(!empty($order), function ($query) use ($request, $order) {
        $orderByColumn = $request->input('columns')[$order[0]['column']]['data'];
        $orderByValue = $order[0]['dir'];
        $query->orderBy($orderByColumn, $orderByValue);
      })
      ->offset($start)
      ->limit($limit);

    // Execute the query
    $buyersList = $query->get();

    // Count total records for pagination
    $recordsFiltered = $query->count();
    $totalRecords = User::join('marketplace_product_purchase_details', 'marketplace_product_purchase_details.user_id', '=', 'users.id')
      ->where('marketplace_product_purchase_details.type', 5)
      ->count();

    // Format the results
    $result = $buyersList->map(function ($buyerdata, $i) {
      $buyerdata->first_name = $buyerdata->first_name ? $buyerdata->first_name . ' ' . $buyerdata->last_name : $buyerdata->username;
      $buyerdata->profile = $buyerdata->user_image ? '<img src="' . asset('paas-port/' . $buyerdata->user_image) . '" style="height:80px;width:auto;"/>' : '<img src="' . asset('assets/images/image-placeholder.png') . '" style="height:80px;width:auto;"/>';
      $buyerdata->id = $i + 1;
      $buyerdata->contact = $buyerdata->mobile;
      $buyerdata->email_address = $buyerdata->email;
      $buyerdata->home_address = $buyerdata->home_address ? Str::limit($buyerdata->home_address, 25) : '-';
      $buyerdata->page = request()->input('page');
      $buyerdata->product_name = $buyerdata->product_name;
      //$buyerdata->action = view('cloud.sellersbuyers.buyer-item-action', ['buyerdata' => $buyerdata])->render();
      return $buyerdata;
    });

    return response()->json([
      'data' => $result,
      'recordsFiltered' => $recordsFiltered,
      'recordsTotal' => $totalRecords,
      'draw' => $request->input('draw'),
    ]);
  }



  public function buyerOrderTable(Request $request)
  {
    // Retrieve buyer_id from request
    $buyer_id = $request->input('buyer_id');

    // Get other request parameters
    $start = $request->input('start', 0);
    $limit = $request->input('length', 10);
    $search_keyword = $request->input('search.value', '');
    $order = $request->input('order', []);

    // Base query with joins and where condition
    $query = DB::table('users')
      ->select(
        'users.id as buyer_id',
        'users.first_name',
        'users.last_name',
        'users.email as email_address',
        'users.username',
        'user_profiles.profile_image_path as user_image',
        'user_profiles.address_1 as home_address',
        'user_profiles.phone_number as mobile',
        'marketplace_product_purchase_details.order_id',
        'marketplace_product_purchase_details.price',
        'marketplace_product_purchase_details.product_name',
        'marketplace_product_purchase_details.quantity as product_quantity',
        'marketplace_product_purchase_details.order_status',
        'marketplace_product_purchase_details.delivery_charge',
        'marketplace_product_purchase_details.shipping_address',
        'marketplace_product_purchase_details.shipping_city',
        'marketplace_product_purchase_details.shipping_state',
        'marketplace_product_purchase_details.shipping_country',
        'marketplace_product_purchase_details.shipping_postal_code',
        'marketplace_product_purchase_details.shipping_phone_number',
        'marketplace_product_purchase_details.shipping_email_id',
        'marketplace_product_purchase_details.created_date_time'
      )
      ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
      ->join('marketplace_product_purchase_details', 'marketplace_product_purchase_details.user_id', '=', 'users.id')
      ->where('marketplace_product_purchase_details.user_id', $buyer_id)
      ->where('marketplace_product_purchase_details.type', 5)
      ->when($search_keyword, function ($query) use ($search_keyword) {
        $query->where(function ($q) use ($search_keyword) {
          $q->where('users.first_name', 'like', "%{$search_keyword}%")
            ->orWhere('users.username', 'like', "%{$search_keyword}%")
            ->orWhere('marketplace_product_purchase_details.order_id', 'like', "%{$search_keyword}%")
            ->orWhere('marketplace_product_purchase_details.product_name', 'like', "%{$search_keyword}%");
        });
      })
      ->when(!empty($order), function ($query) use ($request, $order) {
        $orderByColumn = $request->input('columns')[$order[0]['column']]['data'];
        $orderByValue = $order[0]['dir'];
        $query->orderBy('marketplace_product_purchase_details.' . $orderByColumn, $orderByValue);
      })
      ->offset($start)
      ->limit($limit);

    // Execute the query
    $buyerOrderList = $query->get();

    // Count total records for pagination
    $recordsFiltered = $query->count();
    $totalRecords = DB::table('users')
      ->join('marketplace_product_purchase_details', 'marketplace_product_purchase_details.user_id', '=', 'users.id')
      ->where('marketplace_product_purchase_details.user_id', $buyer_id)
      ->where('marketplace_product_purchase_details.type', 5)
      ->count();

    // Format the results
    $result = $buyerOrderList->map(function ($buyerdata, $i) {
      $buyerdata->name = $buyerdata->first_name ? $buyerdata->first_name . ' ' . $buyerdata->last_name : $buyerdata->username;
      $buyerdata->profile = $buyerdata->user_image ? '<img src="' . asset('paas-port/' . $buyerdata->user_image) . '" style="height:80px;width:auto;"/>' : '<img src="' . asset('assets/images/image-placeholder.png') . '" style="height:80px;width:auto;"/>';
      $buyerdata->id = $i + 1;
      $buyerdata->contact = $buyerdata->mobile;
      $buyerdata->email = $buyerdata->email_address;
      $buyerdata->address = $buyerdata->home_address ? $buyerdata->home_address : '-';
      $buyerdata->order_id = $buyerdata->order_id;
      $buyerdata->quantity = $buyerdata->product_quantity;
      $totalPrice = ((int)$buyerdata->product_quantity * (float)$buyerdata->price);
      $total = ((float)$totalPrice + (float)$buyerdata->delivery_charge);
      $buyerdata->total = "$" . number_format($total, 2);
      $buyerdata->product_name = Str::limit($buyerdata->product_name, 25);
      $buyerdata->delivery_charge = "$" . number_format($buyerdata->delivery_charge, 2);
      $buyerdata->price = "$" . number_format($buyerdata->price, 2);

      // Add any other required transformations here
      return $buyerdata;
    });

    return response()->json([
      'data' => $result,
      'recordsFiltered' => $recordsFiltered,
      'recordsTotal' => $totalRecords,
      'draw' => $request->input('draw'),
    ]);
  }


  public function ProductsCategory()
  {
    // Top Rating Products
    $latestProducts = $this->TopRating();

    // Most Purchased Products
    $mostPurchasedProducts = MarketplaceProductPurchaseDetail::select(
      'p.product_id',
      'p.product_name',
      'p.price',
      DB::raw('MAX(s.paid_price) as paid_price'),
      DB::raw('MAX(s.product_image_path) as product_image_path')
    )
      ->from('marketplace_product_purchase_details as p')
      ->join('marketplace_products as s', 'p.product_id', '=', 's.id')
      ->whereIn('p.product_id', function ($query) {
        $query->select('product_id')
          ->from('marketplace_product_purchase_details')
          ->where('order_type', '5')
          ->where('order_status', '4')
          ->groupBy('product_id')
          ->havingRaw('COUNT(*) >= 3');
      })
      ->groupBy('p.product_id', 'p.product_name', 'p.price')
      ->get();

    $latestProductIds = collect($latestProducts)->pluck('id')->toArray();
    $uniqueProducts = $mostPurchasedProducts->filter(function ($product) use ($latestProductIds) {
      return !in_array($product->product_id, $latestProductIds);
    })->values()->toArray();

    // Initialize selectedIds
    $selectedIds = []; // Define logic or source for selected products if needed

    // Product Categories
    $productCategory = MarketplaceCategory::select(
      'mc.id',
      'mc.category_name as name',
      'mc.image_path',
      DB::raw('COUNT(mp.id) AS product_count')
    )
      ->from('marketplace_category as mc')
      ->leftJoin('marketplace_products as mp', 'mp.category_id', '=', 'mc.id')
      ->leftJoin('marketplace_stores as ms', 'mp.store_id', '=', 'ms.id')
      ->where('ms.is_disabled', 'N')
      ->where('mp.is_public', 'Y')
      ->where('mp.status', '1')
      ->groupBy('mc.id', 'mc.category_name', 'mc.image_path')
      ->orderBy('product_count', 'DESC')
      ->get();

    // Transform the product category image path to a full URL
    $productCategory = $productCategory->map(function ($category) {
      $category->image_path = $category->image_path ? url($category->image_path) : null;
      return $category;
    });

    // Selected Category IDs (If needed, fetch from another table or use some default logic)
    $selectedCategoryIds = []; // Define logic or source for selected categories if needed

    // Fetching All Categories
    $categoryList = MarketplaceCategory::orderBy('id', 'DESC')->get();

    // Transform the category list image path to a full URL
    $categoryList = $categoryList->map(function ($category) {
      $category->image_path = $category->image_path ? url($category->image_path) : null;
      return $category;
    });

    // Transform the latest products image paths to URLs
    $latestProducts = $latestProducts->map(function ($product) {
      $product->thumbnail = url("uploads/{$product->thumbnail}");
      $product->product_images = array_map(function ($image) {
        return url("uploads/{$image}");
      }, explode(',', $product->product_images));
      return $product;
    });

    // Setting data for the view
    return generateResponse([
      'type' => 'success',  // Specify the response type
      'code' => 200,        // HTTP status code
      'status' => true,     // Success status
      'message' => 'Product category data retrieved successfully.',  // Custom message
      'toast' => true,      // Enable toast notification if needed
    ], [
      'latestProducts' => $latestProducts,
      'uniqueProducts' => $uniqueProducts,
      'selectedIds' => $selectedIds,
      'productCategory' => $productCategory,
      'selectedCategoryIds' => $selectedCategoryIds,
      'category_list' => $categoryList,
      'page' => 'product-category'
    ]);
  }


  public function TopRating()
  {
    $products = MarketplaceProducts::where('is_public', 'Y')
      ->whereHas('store', function ($query) {
        $query->where('is_disabled', 'N');
      })
      ->where('status', '1')
      ->with('store')
      ->get();

    $results = $products->map(function ($product) {
      $product_id = $product->id;

      $rating_count = StoreProductReviews::where('product_id', $product_id)
        ->selectRaw('
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) * 5 +
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) * 4 +
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) * 3 +
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) * 2 +
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) * 1 AS total_stars,
                    COUNT(*) AS total_reviews
                ')
        ->first();

      $rating_out_of_5 = $rating_count->total_reviews > 0
        ? number_format($rating_count->total_stars / $rating_count->total_reviews, 1)
        : 0;

      $product->rating_count = $rating_out_of_5;
      return $product;
    });

    $results = $results->sortByDesc('rating_count')->take(8)->values();
    return $results;
  }
}
