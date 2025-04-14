<?php

namespace App\Http\Controllers\API\V1\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetDataRequest;
use App\Http\Requests\Marketplace\Store\AddStoreRequest;
use App\Http\Requests\Marketplace\Store\StoreCategoryRequest;
use App\Http\Requests\Marketplace\Store\StoreSubCategoryRequest;
use App\Http\Requests\Marketplace\Store\StoreSubCategoryTagRequest;
use App\Models\Marketplace\MarketplaceStore;
use App\Models\Marketplace\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceSubCategory;
use App\Models\MarketplaceSubCategoryTag;
use App\Models\Marketplace\MarketplaceStoreCategory;
use App\Http\Requests\MStoreCategoryRequest;
use Illuminate\Support\Str;

class MarketplaceStoreController extends Controller
{

    public function addStore(AddStoreRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = $request->attributes->get('user');
            $userId = $user->id;
            $date = now()->format('Y-m-d');

            // Define the base path with user ID and date
            $baseUploadPath = "assets/marketplace/$userId/$date/";
            $storeImageUploadPath = $baseUploadPath . 'store/images/';
            $paths = [
                $storeImageUploadPath,
                $storeImageUploadPath . '420x337/',
                $storeImageUploadPath . '1226x355/',
                $storeImageUploadPath . '1138x362/',
                $baseUploadPath . 'qrcodes/',
            ];

            // Ensure directories exist
            foreach ($paths as $path) {
                $fullPath = 'public/' . $path;
                if (!Storage::disk('public_uploads')->exists($fullPath)) {
                    Storage::disk('public_uploads')->makeDirectory($fullPath, 0755, true);
                }
            }

            $store = $request->has('id') ? MarketplaceStore::find($request->input('id')) : new MarketplaceStore();

            // Handle Thumbnail Upload
            if ($request->hasFile('thumbnail_path')) {
                $thumbnail = $request->file('thumbnail_path');
                $thumbnailName = time() . '_' . $thumbnail->getClientOriginalName();
                $thumbnailPath = $storeImageUploadPath . '420x337/' . $thumbnailName;

                // Save the thumbnail
                $thumbnail->storeAs($storeImageUploadPath . '420x337/', $thumbnailName, 'public_uploads');
                $store->thumbnail_path = $thumbnailPath;
            }

            // Handle Main Image Upload
            if ($request->hasFile('image_path')) {
                $imageFile = $request->file('image_path');
                $imageName = time() . '_' . $imageFile->getClientOriginalName();
                $imagePath = $storeImageUploadPath . '1226x355/' . $imageName;

                // Save the image
                $imageFile->storeAs($storeImageUploadPath . '1226x355/', $imageName, 'public_uploads');
                $store->image_path = $imagePath;
            }

            // Handle Banner Upload
            if ($request->hasFile('banner_path')) {
                $banner = $request->file('banner_path');
                $bannerName = time() . '_' . $banner->getClientOriginalName();
                $bannerPath = $storeImageUploadPath . '1138x362/' . $bannerName;

                // Save the banner
                $banner->storeAs($storeImageUploadPath . '1138x362/', $bannerName, 'public_uploads');
                $store->banner_path = $bannerPath;
            }

            // Handle Store Logo Upload
            if ($request->hasFile('store_logo')) {
                $storeLogo = $request->file('store_logo');
                $storeLogoName = time() . '_' . $storeLogo->getClientOriginalName();
                $storeLogoPath = $storeImageUploadPath . 'logo/' . $storeLogoName;

                // Save the store logo
                $storeLogo->storeAs($storeImageUploadPath . 'logo/', $storeLogoName, 'public_uploads');
                $store->store_logo = $storeLogoPath;
            }

            // Set Store Attributes
            $store->name = $request->input('name');
            $store->slug = Str::slug($request->input('name'));
            $store->store = $request->input('store');
            $store->product_type = $request->input('product_type');
            $store->theme = $request->input('theme');
            $store->description = $request->input('description');
            $store->is_disabled = $request->input('is_disabled');
            $store->user_id = $userId;

            // Handle QR Code Upload
            if ($request->has('qr_code_image') && $request->has('qr_code_image_ext')) {
                $qrCodeContent = $request->input('qr_code_image');
                $qrCodeExt = $request->input('qr_code_image_ext');
                $qrCodeFileName = 'store_qrcode_' . time() . '.' . $qrCodeExt;
                $qrCodeFilePath = $baseUploadPath . 'qrcodes/' . $qrCodeFileName;

                // Ensure QR Code directory exists
                $qrCodeDirectory = storage_path('app/public_uploads/' . $baseUploadPath . 'qrcodes/');
                if (!file_exists($qrCodeDirectory)) {
                    if (!mkdir($qrCodeDirectory, 0755, true)) {
                        throw new \Exception('Failed to create QR Code directory.');
                    }
                }

                // Generate and save the QR code
                QrCode::format('svg')
                    ->size(300)
                    ->generate($qrCodeContent, storage_path('app/public_uploads/' . $qrCodeFilePath));

                $store->qr_code_image = $qrCodeFileName;
                $store->qr_code_image_ext = $qrCodeExt;
            }

            // Save or update store
            if ($request->has('id')) {
                $store->update();
                $response = [
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Store updated successfully',
                    'toast' => true
                ];
            } else {
                $store->save();
                $storeId = $store->id;

                $response = [
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Store created successfully',
                    'toast' => true
                ];
            }

            DB::commit();

            return response()->json([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'New store request added',
                'toast' => true,
                'store' => $store->toArray()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log the error
            Log::error('Store creation/update failed: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'toast' => true
            ]);
        }
    }


    public function getStores(Request $request)
    {
        try {
            // Retrieve the logged-in user
            $user = $request->attributes->get('user');

            // Get query parameters with default values
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 100); // Number of results per page
            $offset = ($page - 1) * $limit;
            $searchKeyword = $request->input('search_keyword', null); // Optional search keyword
            $storeId = $request->input('store_id', null); // Filter by store ID
            $isDisabled = $request->input('is_disabled', null); // Filter by disabled status
            $theme = $request->input('theme', null); // Filter by theme

            // Start the query for MarketplaceStore
            $query = MarketplaceStore::query();

            // Filter by store ID if provided
            if ($storeId) {
                $query->where('id', $storeId);
            }

            // Filter by disabled status if provided
            if ($isDisabled !== null) {
                $query->where('is_disabled', $isDisabled);
            }

            // Filter by theme if provided
            if ($theme !== null) {
                $query->where('theme', $theme);
            }

            // Search functionality
            if ($searchKeyword) {
                $query->where(function ($q) use ($searchKeyword) {
                    $q->where('name', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('description', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('product_type', 'like', '%' . $searchKeyword . '%');
                });
            }

            // Subquery to get the count of products for each store
            $query->withCount(['products' => function ($q) {
                $q->select(DB::raw('count(*)'));
            }]);

            // Apply pagination
            $stores = $query->offset($offset)->limit($limit)->get();

            // Modify store data to include complete URLs and product count
            $stores = $stores->map(function ($store) {
                $store->store_logo = $store->store_logo ? url('uploads/' . $store->store_logo) : null;
                $store->thumbnail_path = $store->thumbnail_path ? url('uploads/' . $store->thumbnail_path) : null;
                $store->image_path = $store->image_path ? url('uploads/' . $store->image_path) : null;
                $store->banner_path = $store->banner_path ? url('uploads/' . $store->banner_path) : null;
                return $store;
            });

            // Check if stores exist
            if ($stores->isEmpty()) {
                return generateResponse([
                    'type' => 'info',
                    'code' => 200,
                    'status' => true,
                    'message' => 'No stores found',
                    'toast' => false,
                    'data' => ['stores' => []]
                ]);
            }

            // Return success response with stores data
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Stores retrieved successfully',
                'toast' => false,
                'data' => ['stores' => $stores->toArray()]
            ]);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('getStores: ' . $e->getMessage(), ['exception' => $e]);

            // Return error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while retrieving stores',
                'toast' => true
            ]);
        }
    }


    public function getStoresprivate(Request $request)
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
            $storeId = $request->input('store_id', null); // Filter by store ID
            $isDisabled = $request->input('is_disabled', null); // Filter by disabled status
            $theme = $request->input('theme', null); // Filter by theme

            $query = MarketplaceStore::query();

            // Filter by store ID if provided
            if ($storeId) {
                $query->where('id', $storeId);
            }

            // Filter by disabled status if provided
            if ($isDisabled !== null) {
                $query->where('is_disabled', $isDisabled);
            }

            // Filter by theme if provided
            if ($theme !== null) {
                $query->where('theme', $theme);
            }

            // Filter by user ID to get stores specific to the logged-in user
            $query->where('user_id', $user->id);

            // Search functionality
            if ($searchKeyword) {
                $query->where(function ($q) use ($searchKeyword) {
                    $q->where('name', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('description', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('product_type', 'like', '%' . $searchKeyword . '%');
                });
            }

            // Apply pagination
            $stores = $query->offset($offset)->limit($limit)->get();

            // Check if stores exist
            if ($stores->isEmpty()) {
                return generateResponse([
                    'type' => 'info',
                    'code' => 200,
                    'status' => true,
                    'message' => 'No stores found',
                    'toast' => false,
                    'data' => ['stores' => []]
                ]);
            }


            // Process stores and include URLs in the response
            $stores->transform(function ($store) {
                $store->thumbnail_path = $store->thumbnail_path ? url("uploads/{$store->thumbnail_path}") : null;
                $store->image_path = $store->image_path ? url("uploads/{$store->image_path}") : null;
                $store->banner_path = $store->banner_path ? url("uploads/{$store->banner_path}") : null;
                $store->store_logo = $store->store_logo ? url("uploads/{$store->store_logo}") : null;
                $store->qr_code_image = $store->qr_code_image ? url("uploads/{$store->qr_code_image}") : null;

                return $store;
            });

            // Return success response with stores data
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Stores retrieved successfully',
                'toast' => false,
                'data' => ['stores' => $stores->toArray()]
            ]);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('getStores: ' . $e->getMessage(), ['exception' => $e]);

            // Return error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while retrieving stores',
                'toast' => true
            ]);
        }
    }


    public function updateStore(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = $request->attributes->get('user');
            $storeId = $request->input('id');

            $store = MarketplaceStore::find($storeId);

            if (!$store || $store->user_id !== $user->id) {
                return generateResponse([
                    'type' => 'error',
                    'code' => 404,
                    'status' => false,
                    'message' => 'Store not found or not authorized',
                    'toast' => true
                ]);
            }

            $userId = $user->id;
            $date = now()->format('Y-m-d');

            // Define the base path with user ID and date
            $baseUploadPath = "assets/marketplace/$userId/$date/";
            $storeImageUploadPath = $baseUploadPath . 'store/images/';
            $paths = [
                $storeImageUploadPath,
                $storeImageUploadPath . '420x337/',
                $storeImageUploadPath . '1226x355/',
                $storeImageUploadPath . '1138x362/',
                $storeImageUploadPath . 'logo/', // Add logo directory
            ];

            // Ensure directories exist
            foreach ($paths as $path) {
                $fullPath = 'public/' . $path;
                if (!Storage::disk('public_uploads')->exists($fullPath)) {
                    Storage::disk('public_uploads')->makeDirectory($fullPath, 0755, true);
                }
            }

            // Handle Thumbnail Upload
            if ($request->hasFile('thumbnail_path')) {
                $thumbnail = $request->file('thumbnail_path');
                $thumbnailName = time() . '_' . $thumbnail->getClientOriginalName();
                $thumbnailPath = $storeImageUploadPath . '420x337/' . $thumbnailName;

                // Save the thumbnail
                $thumbnail->storeAs($storeImageUploadPath . '420x337/', $thumbnailName, 'public_uploads');
                $store->thumbnail_path = $thumbnailPath;
            }

            // Handle Main Image Upload
            if ($request->hasFile('image_path')) {
                $imageFile = $request->file('image_path');
                $imageName = time() . '_' . $imageFile->getClientOriginalName();
                $imagePath = $storeImageUploadPath . '1226x355/' . $imageName;

                // Save the image
                $imageFile->storeAs($storeImageUploadPath . '1226x355/', $imageName, 'public_uploads');
                $store->image_path = $imagePath;
            }

            // Handle Banner Upload
            if ($request->hasFile('banner_path')) {
                $banner = $request->file('banner_path');
                $bannerName = time() . '_' . $banner->getClientOriginalName();
                $bannerPath = $storeImageUploadPath . '1138x362/' . $bannerName;

                // Save the banner
                $banner->storeAs($storeImageUploadPath . '1138x362/', $bannerName, 'public_uploads');
                $store->banner_path = $bannerPath;
            }

            // Handle Store Logo Upload
            if ($request->hasFile('store_logo')) {
                $storeLogo = $request->file('store_logo');
                $storeLogoName = time() . '_' . $storeLogo->getClientOriginalName();
                $storeLogoPath = $storeImageUploadPath . 'logo/' . $storeLogoName;

                // Save the store logo
                $storeLogo->storeAs($storeImageUploadPath . 'logo/', $storeLogoName, 'public_uploads');
                $store->store_logo = $storeLogoPath;
            }

            // Update other attributes
            if ($request->has('store')) {
                $store->store = $request->input('store');
            }
            if ($request->has('name')) {
                $store->name = $request->input('name');
            }
            if ($request->has('product_type')) {
                $store->product_type = $request->input('product_type');
            }
            if ($request->has('theme')) {
                $store->theme = $request->input('theme');
            }
            if ($request->has('description')) {
                $store->description = $request->input('description');
            }
            if ($request->has('is_disabled')) {
                $store->is_disabled = $request->input('is_disabled');
            }

            $store->save();

            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Store updated successfully',
                'toast' => true
            ], ['store' => $store->toArray()]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Store update failed: ' . $e->getMessage(), ['exception' => $e]);

            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'toast' => true
            ]);
        }
    }


    public function deleteStore(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = $request->attributes->get('user');
            $storeId = $request->input('id');

            $store = MarketplaceStore::find($storeId);

            if (!$store || $store->user_id !== $user->id) {
                return generateResponse([
                    'type' => 'error',
                    'code' => 404,
                    'status' => false,
                    'message' => 'Store not found or not authorized',
                    'toast' => true
                ]);
            }

            // Delete store images and QR code if necessary
            if ($store->thumbnail_path && file_exists(public_path($store->thumbnail_path))) {
                unlink(public_path($store->thumbnail_path));
            }

            if ($store->image_path && file_exists(public_path($store->image_path))) {
                unlink(public_path($store->image_path));
            }

            if ($store->banner_path && file_exists(public_path($store->banner_path))) {
                unlink(public_path($store->banner_path));
            }

            if ($store->qr_code_image && file_exists(public_path('qrcodes/' . $store->qr_code_image))) {
                unlink(public_path('qrcodes/' . $store->qr_code_image));
            }

            // Delete the store record
            $store->delete();

            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Store deleted successfully',
                'toast' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Store deletion failed: ' . $e->getMessage(), ['exception' => $e]);

            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'toast' => true
            ]);
        }
    }

    public function storeCategory(StoreCategoryRequest $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            $categoryCheck = MarketplaceCategory::where('category_name', $validatedData['categoryName'])->first();

            if ($categoryCheck) {
                DB::rollBack();
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Category already exists', 'toast' => true]);
            }

            $user = $request->attributes->get('user');
            $userId = $user->id;
            $date = now()->format('Y-m-d');

            $baseUploadPath = "uploads/$userId/$date/";

            $categoryData = [
                'category_name' => $validatedData['categoryName'],
            ];

            if ($request->hasFile('categoryThumbnail')) {
                $image = $request->file('categoryThumbnail');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $uploadPath = $baseUploadPath . 'store_product_category/';
                $fullImagePath = $uploadPath . $filename;

                if (!Storage::exists($uploadPath)) {
                    Storage::makeDirectory($uploadPath, 0755, true);
                }

                Storage::put($fullImagePath, file_get_contents($image));

                $thumbnailPath = $uploadPath . '419x419/' . $filename;
                $img = Image::read($image->getRealPath());
                $img->resize(419, 419, function ($constraint) {
                    $constraint->aspectRatio();
                });
                Storage::put($thumbnailPath, (string) $img->encode());

                $categoryData['image_path'] = $fullImagePath;
                $categoryData['image_ext'] = $image->getClientOriginalExtension();
            }

            $category = MarketplaceCategory::create($categoryData);
            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Category created', 'toast' => true], [
                'category' => $category->toArray(),
                'image_path' => $categoryData['image_path'] ?? null,
                'image_ext' => $categoryData['image_ext'] ?? null
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to store category: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'An error occurred while creating the category. Please try again later.', 'toast' => true]);
        }
    }

    public function getCategories(Request $request)
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
            $categoryId = $request->input('category_id', null); // Filter by category ID

            $query = MarketplaceCategory::query();

            // Filter by category ID if provided
            if ($categoryId) {
                $query->where('id', $categoryId);
            }

            // Search functionality
            if ($searchKeyword) {
                $query->where(function ($q) use ($searchKeyword) {
                    $q->where('category_name', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('description', 'like', '%' . $searchKeyword . '%');
                });
            }

            // Apply pagination
            $categories = $query->offset($offset)->limit($limit)->get();

            // Check if categories exist
            if ($categories->isEmpty()) {
                return generateResponse([
                    'type' => 'info',
                    'code' => 200,
                    'status' => true,
                    'message' => 'No categories found',
                    'toast' => false,
                    'data' => ['categories' => []]
                ]);
            }

            // Return success response with categories data
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Categories retrieved successfully',
                'toast' => false,
                'data' => ['categories' => $categories->toArray()]
            ]);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('getCategories: ' . $e->getMessage(), ['exception' => $e]);

            // Return error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while retrieving categories',
                'toast' => true
            ]);
        }
    }


    public function getSubCategories(Request $request)
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
            $categoryId = $request->input('category_id', null); // Filter by category ID

            // Build the query for fetching subcategories
            $query = MarketplaceSubCategory::query();

            // Filter by category ID if provided
            if ($categoryId) {
                $query->where('parent_category_id', $categoryId);
            }

            // Search functionality
            if ($searchKeyword) {
                $query->where(function ($q) use ($searchKeyword) {
                    $q->where('sub_category_name', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('description', 'like', '%' . $searchKeyword . '%');
                });
            }

            // Apply pagination
            $subCategories = $query->offset($offset)->limit($limit)->get();

            // Check if subcategories exist
            if ($subCategories->isEmpty()) {
                return generateResponse([
                    'type' => 'info',
                    'code' => 200,
                    'status' => true,
                    'message' => 'No subcategories found',
                    'toast' => false,
                    'data' => ['subCategories' => []]
                ]);
            }

            // Return success response with subcategories data
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Subcategories retrieved successfully',
                'toast' => false,
                'data' => ['subCategories' => $subCategories->toArray()]
            ]);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('getSubCategories: ' . $e->getMessage(), ['exception' => $e]);

            // Return error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while retrieving subcategories',
                'toast' => true
            ]);
        }
    }

    public function updateCategory(StoreCategoryRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $category = MarketplaceCategory::find($id);
            if (!$category) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Category not found',
                    'toast' => true
                ]);
            }

            $validatedData = $request->validated();

            $user = $request->attributes->get('user');
            $userId = $user->id;
            $date = now()->format('Y-m-d');

            $baseUploadPath = "uploads/$userId/$date/";

            $categoryData = [
                'category_name' => $validatedData['categoryName'],
            ];

            if ($request->hasFile('categoryThumbnail')) {
                // Delete the old image if it exists
                if ($category->image_path && Storage::exists($category->image_path)) {
                    Storage::delete($category->image_path);
                }

                $image = $request->file('categoryThumbnail');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $uploadPath = $baseUploadPath . 'store_product_category/';
                $fullImagePath = $uploadPath . $filename;

                if (!Storage::exists($uploadPath)) {
                    Storage::makeDirectory($uploadPath, 0755, true);
                }

                Storage::put($fullImagePath, file_get_contents($image));

                $thumbnailPath = $uploadPath . '419x419/' . $filename;
                $img = Image::read($image->getRealPath());
                $img->resize(419, 419, function ($constraint) {
                    $constraint->aspectRatio();
                });
                Storage::put($thumbnailPath, (string) $img->encode());

                $categoryData['image_path'] = $fullImagePath;
                $categoryData['image_ext'] = $image->getClientOriginalExtension();
            }

            $category->update($categoryData);
            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Category updated',
                'toast' => true
            ], [
                'category' => $category->toArray()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to update category: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while updating the category. Please try again later.',
                'toast' => true
            ]);
        }
    }

    public function deleteCategory($id)
    {
        DB::beginTransaction();

        try {
            $category = MarketplaceCategory::find($id);
            if (!$category) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Category not found',
                    'toast' => true
                ]);
            }

            // Fetch all subcategories where the parent_category_id matches the category ID
            $subCategories = MarketplaceSubCategory::where('parent_category_id', $id)->get();

            foreach ($subCategories as $subCategory) {
                // Delete all tags associated with the subcategory
                $tags = MarketplaceSubCategoryTag::where('sub_category_id', $subCategory->id)->get();
                foreach ($tags as $tag) {
                    $tag->delete();
                }

                // Delete the subcategory image if it exists
                if ($subCategory->image_path && Storage::exists($subCategory->image_path)) {
                    Storage::delete($subCategory->image_path);
                }

                // Delete the subcategory itself
                $subCategory->delete();
            }

            // Delete the category image if it exists
            if ($category->image_path && Storage::exists($category->image_path)) {
                Storage::delete($category->image_path);
            }

            // Delete the category itself
            $category->delete();

            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Category, subcategories, and tags deleted',
                'toast' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete category: ' . $e->getMessage());

            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while deleting the category. Please try again later.',
                'toast' => true
            ]);
        }
    }


    public function storeSubCategory(StoreSubCategoryRequest $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            $subCategoryCheck = MarketplaceSubCategory::where('sub_category_name', $validatedData['subCategoryName'])
                ->where('parent_category_id', $validatedData['parentCategoryId'])
                ->first();

            if ($subCategoryCheck) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Subcategory already exists',
                    'toast' => true
                ]);
            }

            $user = $request->attributes->get('user');
            $userId = $user->id;
            $date = now()->format('Y-m-d');

            $baseUploadPath = "uploads/$userId/$date/";

            $subCategoryData = [
                'sub_category_name' => $validatedData['subCategoryName'],
                'parent_category_id' => $validatedData['parentCategoryId'],
            ];

            if ($request->hasFile('subCategoryThumbnail')) {
                $image = $request->file('subCategoryThumbnail');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $uploadPath = $baseUploadPath . 'store_product_subcategory/';
                $fullImagePath = $uploadPath . $filename;

                if (!Storage::exists($uploadPath)) {
                    Storage::makeDirectory($uploadPath, 0755, true);
                }

                Storage::put($fullImagePath, file_get_contents($image));

                $thumbnailPath = $uploadPath . '419x419/' . $filename;
                $img = Image::read($image->getRealPath());
                $img->resize(419, 419, function ($constraint) {
                    $constraint->aspectRatio();
                });
                Storage::put($thumbnailPath, (string) $img->encode());

                $subCategoryData['image_path'] = $fullImagePath;
                $subCategoryData['image_ext'] = $image->getClientOriginalExtension();
            }

            $subCategory = MarketplaceSubCategory::create($subCategoryData);

            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Subcategory created',
                'toast' => true
            ], ['subCategory' => $subCategory->toArray()]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to store subcategory: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while creating the subcategory. Please try again later.',
                'toast' => true
            ]);
        }
    }

    public function updateSubCategory(StoreSubCategoryRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $subCategory = MarketplaceSubCategory::find($id);
            if (!$subCategory) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Subcategory not found',
                    'toast' => true
                ]);
            }

            $validatedData = $request->validated();

            $user = $request->attributes->get('user');
            $userId = $user->id;
            $date = now()->format('Y-m-d');

            $baseUploadPath = "uploads/$userId/$date/";

            $subCategoryData = [
                'sub_category_name' => $validatedData['subCategoryName'],
                'parent_category_id' => $validatedData['parentCategoryId'],
            ];

            if ($request->hasFile('subCategoryThumbnail')) {
                if ($subCategory->image_path && Storage::exists($subCategory->image_path)) {
                    Storage::delete($subCategory->image_path);
                }

                $image = $request->file('subCategoryThumbnail');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $uploadPath = $baseUploadPath . 'store_product_subcategory/';
                $fullImagePath = $uploadPath . $filename;

                if (!Storage::exists($uploadPath)) {
                    Storage::makeDirectory($uploadPath, 0755, true);
                }

                Storage::put($fullImagePath, file_get_contents($image));

                $thumbnailPath = $uploadPath . '419x419/' . $filename;
                $img = Image::read($image->getRealPath());
                $img->resize(419, 419, function ($constraint) {
                    $constraint->aspectRatio();
                });
                Storage::put($thumbnailPath, (string) $img->encode());

                $subCategoryData['image_path'] = $fullImagePath;
                $subCategoryData['image_ext'] = $image->getClientOriginalExtension();
            }

            $subCategory->update($subCategoryData);

            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Subcategory updated',
                'toast' => true
            ], ['subCategory' => $subCategory->toArray()]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to update subcategory: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while updating the subcategory. Please try again later.',
                'toast' => true
            ]);
        }
    }


    public function deleteSubCategory($id)
    {
        DB::beginTransaction();

        try {
            $subCategory = MarketplaceSubCategory::find($id);
            if (!$subCategory) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Subcategory not found',
                    'toast' => true
                ]);
            }

            if ($subCategory->image_path && Storage::exists($subCategory->image_path)) {
                Storage::delete($subCategory->image_path);
            }

            $subCategory->delete();

            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Subcategory deleted',
                'toast' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to delete subcategory: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while deleting the subcategory. Please try again later.',
                'toast' => true
            ]);
        }
    }

    public function storeSubCategoryTag(StoreSubCategoryTagRequest $request)
    {
        DB::beginTransaction();

        try {
            // Validate the request data
            $validatedData = $request->validated();

            // Prepare tag data
            $tagData = [
                'category_id' => $validatedData['categoryId'],
                'sub_category_id' => $validatedData['subCategoryId'],
                'tag' => $validatedData['tag'],
                'date_time' => now(),
            ];

            // Create the tag
            $tag = MarketplaceSubCategoryTag::create($tagData);

            // Commit the transaction
            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Tag created',
                'toast' => true
            ], ['tag' => $tag->toArray()]);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            // Log the error for debugging
            Log::error('Failed to create subcategory tag: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while creating the tag. Please try again later.',
                'toast' => true
            ]);
        }
    }


    public function getTags(Request $request)
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
            $categoryId = $request->input('category_id', null); // Filter by category ID
            $subCategoryId = $request->input('sub_category_id', null); // Filter by sub-category ID

            $query = MarketplaceSubCategoryTag::query();

            // Filter by category ID if provided
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            // Filter by sub-category ID if provided
            if ($subCategoryId) {
                $query->where('sub_category_id', $subCategoryId);
            }

            // Search functionality
            if ($searchKeyword) {
                $query->where('tag', 'like', '%' . $searchKeyword . '%');
            }

            // Apply pagination
            $tags = $query->offset($offset)->limit($limit)->get();

            // Check if tags exist
            if ($tags->isEmpty()) {
                return generateResponse([
                    'type' => 'info',
                    'code' => 200,
                    'status' => true,
                    'message' => 'No tags found',
                    'toast' => false,
                    'data' => ['tags' => []]
                ]);
            }

            // Return success response with tags data
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Tags retrieved successfully',
                'toast' => false,
                'data' => ['tags' => $tags->toArray()]
            ]);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('getTags: ' . $e->getMessage(), ['exception' => $e]);

            // Return error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while retrieving tags',
                'toast' => true
            ]);
        }
    }


    public function updateSubCategoryTag(StoreSubCategoryTagRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            // Find the tag by ID
            $tag = MarketplaceSubCategoryTag::find($id);
            if (!$tag) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Tag not found',
                    'toast' => true
                ]);
            }

            // Validate the request data
            $validatedData = $request->validated();

            // Update the tag
            $tag->update($validatedData);

            // Commit the transaction
            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Tag updated',
                'toast' => true
            ], ['tag' => $tag->toArray()]);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            // Log the error for debugging
            Log::error('Failed to update subcategory tag: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while updating the tag. Please try again later.',
                'toast' => true
            ]);
        }
    }


    public function deleteSubCategoryTag($id)
    {
        DB::beginTransaction();

        try {
            // Find the tag by ID
            $tag = MarketplaceSubCategoryTag::find($id);
            if (!$tag) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Tag not found',
                    'toast' => true
                ]);
            }

            // Delete the tag
            $tag->delete();

            // Commit the transaction
            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Tag deleted',
                'toast' => true
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            // Log the error for debugging
            Log::error('Failed to delete subcategory tag: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while deleting the tag. Please try again later.',
                'toast' => true
            ]);
        }
    }

    public function storeAddCat(MStoreCategoryRequest $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validated();
            $user = $request->attributes->get('user');
            $userId = $user->id;
            $date = now()->format('Y-m-d');
            $baseUploadPath = "uploads/$userId/$date/";

            $imagePath = null;
            $imageExt = null;

            // Handle file upload
            if ($request->hasFile('image_path')) {
                $image = $request->file('image_path');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $uploadPath = $baseUploadPath . 'store_category/';
                $fullImagePath = $uploadPath . $filename;

                if (!Storage::exists($uploadPath)) {
                    Storage::makeDirectory($uploadPath, 0755, true);
                }

                Storage::put($fullImagePath, file_get_contents($image));

                $thumbnailPath = $uploadPath . '419x419/' . $filename;
                $img = Image::read($image->getRealPath());
                $img->resize(419, 419, function ($constraint) {
                    $constraint->aspectRatio();
                });
                Storage::put($thumbnailPath, (string) $img->encode());

                $imagePath = $fullImagePath;
                $imageExt = $image->getClientOriginalExtension();
            }

            // Prepare category data
            $categoryData = [
                'name' => $validatedData['name'],
                'image_path' => $imagePath,
                'image_ext' => $imageExt,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Create the category
            $category = MarketplaceStoreCategory::create($categoryData);

            // Commit the transaction
            DB::commit();

            // Return success response
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Category created successfully',
                'toast' => true
            ], ['category' => $category->toArray()]);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            // Log the error for debugging
            Log::error('Failed to create category: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while creating the category. Please try again later.',
                'toast' => true
            ]);
        }
    }


    public function updateStoreCat(MStoreCategoryRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            // Find the category
            $category = MarketplaceStoreCategory::findOrFail($id);
            $user = $request->attributes->get('user');
            $userId = $user->id;
            $date = date('Y-m-d');

            $baseUploadPath = "uploads/$userId/$date/";

            // Directly get the validated input data
            $name = $request->input('name', $category->name);

            // Handle file upload
            if ($request->hasFile('image_path')) {
                // Delete old image if it exists
                if ($category->image_path && Storage::exists($category->image_path)) {
                    Storage::delete($category->image_path);
                }

                $image = $request->file('image_path');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $uploadPath = $baseUploadPath . 'store_category/';
                $fullImagePath = $uploadPath . $filename;

                if (!Storage::exists($uploadPath)) {
                    Storage::makeDirectory($uploadPath, 0755, true);
                }

                Storage::put($fullImagePath, file_get_contents($image));

                $thumbnailPath = $uploadPath . '419x419/' . $filename;
                $img = Image::read($image->getRealPath());
                $img->resize(419, 419, function ($constraint) {
                    $constraint->aspectRatio();
                });
                Storage::put($thumbnailPath, (string) $img->encode());

                $imagePath = $fullImagePath;
                $imageExt = $image->getClientOriginalExtension();
            } else {
                $imagePath = $category->image_path;
                $imageExt = $category->image_ext;
            }

            // Prepare category data
            $categoryData = [
                'name' => $name,
                'image_path' => $imagePath,
                'image_ext' => $imageExt,
                'updated_at' => now(),
            ];

            // Update the category
            $category->update($categoryData);

            DB::commit();

            // Return success response
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Category updated successfully',
                'toast' => true
            ], ['category' => $category->toArray()]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log the error for debugging
            Log::error('Failed to update store category: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while updating the category. Please try again later.',
                'toast' => true
            ]);
        }
    }


    public function deleteStoreCat($id)
    {
        DB::beginTransaction();

        try {
            // Find the category
            $category = MarketplaceStoreCategory::findOrFail($id);

            // Delete associated image if it exists
            if ($category->image_path && Storage::exists($category->image_path)) {
                Storage::delete($category->image_path);
            }

            // Delete the category
            $category->delete();

            // Commit the transaction
            DB::commit();

            // Return success response
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Category deleted successfully',
                'toast' => true
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            // Log the error for debugging
            Log::error('Failed to delete store category: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while deleting the category. Please try again later.',
                'toast' => true
            ]);
        }
    }

    public function getStoreCategories(Request $request)
    {
        $categories = MarketplaceStoreCategory::all();

        return generateResponse([
            'type' => 'success',
            'code' => 200,
            'status' => true,
            'message' => 'Categories retrieved successfully',
            'toast' => true
        ], ['categories' => $categories->toArray()]);
    }
}
