<?php

namespace App\Http\Controllers\API\V1\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\AddSpecificationRequest;
use App\Models\Marketplace\ProductSpecification;
use App\Models\MarketplaceProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceStore;
use App\Models\MarketplaceSubCategory;
use App\Models\StoreProductReviews;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProductStatusNotification;
use Illuminate\Support\Str;
use App\Models\Marketplace\MarketplaceSiteSubscription;
use App\Models\StoreProductQuestions;
use App\Models\UserProfile;
use App\Models\MarketplaceOrderReturnReplaceRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Codeboxr\CouponDiscount\Facades\Coupon; // Corrected import statement
use App\Models\Coupon as AppCoupon;
use App\Models\CouponHistory;

class ProductController extends Controller
{
    public function addProductSpecification(AddSpecificationRequest $request)
    {
        try {
            DB::beginTransaction();
            $specifications = $request->specifications;
            $product_id = $request->product_id;
            $user = $request->attributes->get('user');

            $product_query = MarketplaceProducts::query();
            $product = $product_query->where("id", $product_id)->where("user_id", $user->id)->first();
            if (!$product) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product not found', 'toast' => true]);
            }
            if (is_array($specifications)) {
                foreach ($specifications as $specification) {
                    if (isset($specification['description']) && isset($specification['title'])) {
                        $specificationRow = new ProductSpecification();
                        $specificationRow->product_id = $product->id;
                        $specificationRow->title = $specification['title'];
                        $specificationRow->description = $specification['description'];
                        $specificationRow->status = "1";
                        $specificationRow->save();
                    } else {
                        continue;
                    }
                }
                DB::commit();
                $productSpecifications = ProductSpecification::where("product_id", $product_id)->where("status", "1")->get();
                if ($productSpecifications->isNotEmpty()) {
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Specifications added to product', 'toast' => true], ["specifications" => $productSpecifications->toArray()]);
                } else {
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Specifications added to product', 'toast' => true]);
                }
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Provide valid array', 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductController addProductSpecification error: ' . $e->getMessage() . " line no " . $e->getLine() . " file: " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function getProductFile(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'request_file_path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'type' => 'error',
                'code' => 400,
                'status' => false,
                'message' => 'File path required',
                'errors' => $validator->errors(),
            ]);
        }
        $request_file_path = $request->input('request_file_path');

        $filePath = public_path($request_file_path);

        if (File::exists($filePath)) {
            return response()->file($filePath);
        } else {
            return response()->json([
                'type' => 'error',
                'code' => 400,
                'status' => false,
                'message' => 'File not found',
            ]);
        }
    }




    public function getMarketPlaceLoadMoreList(Request $request)
    {
        $limit = 8;
        $page = $request->input('page', 0);
        $filterDescAsc = $request->input('filter_desc_asc');
        $searchKeyword = $request->input('search_keyword', '');
        $start = ($page - 1) * $limit;
        $priceMin = '';
        $priceMax = '';
        $response = ['minPrice' => 0];
        $filter = [];
        $videoGltf = [];
        $result = [];

        if ($request->input('price_filter') != '') {
            $priceFilter = $request->input('price_filter');
            list($priceMin, $priceMax) = explode('-', $priceFilter);

            $filter[] = [
                'filter_name' => $priceFilter,
                'filter_id' => $priceFilter,
                'filter_type' => 'price_filter'
            ];
            $response['minPrice'] = $priceMin;
        }

        $param1 = $request->input('param1');
        $param2 = $request->input('param2');
        $param3 = $request->input('param3');  // Added to handle the third parameter
        $param4 = $request->input('param4');  // Added to handle the third parameter
        $condition = [];

        switch ($filterDescAsc) {
            case 'high_to_low_price':
                $condition['sort_by_column'] = 'price';
                $condition['sort_by_val'] = 'DESC';
                break;
            case 'low_to_high_price':
                $condition['sort_by_column'] = 'price';
                $condition['sort_by_val'] = 'ASC';
                break;
            case 'a_to_z_name':
                $condition['sort_by_column'] = 'product_name';
                $condition['sort_by_val'] = 'ASC';
                break;
            case 'z_to_a_name':
                $condition['sort_by_column'] = 'product_name';
                $condition['sort_by_val'] = 'DESC';
                break;
            case 'gltf_file':
                $videoGltf['column_name'] = '3d_product_file';
                break;
            case 'video_file':
                $videoGltf['column_name'] = 'product_video_path';
                break;
            default:
                $condition['sort_by_column'] = 'id';
                $condition['sort_by_val'] = 'DESC';
                break;
        }

        if (!empty($param1) && $param1 == 'stores') {
            if (!empty($param2)) {
                $store = MarketplaceStore::find($param2);

                $query = MarketplaceProducts::where('store_id', $param2)
                    ->where('is_public', 'Y')
                    ->where('status', '1')
                    ->where('product_name', 'LIKE', "%{$searchKeyword}%")
                    ->when($priceMin, function ($query) use ($priceMin) {
                        return $query->where('price', '>=', $priceMin);
                    })
                    ->when($priceMax, function ($query) use ($priceMax) {
                        return $query->where('price', '<=', $priceMax);
                    })
                    ->limit($limit)
                    ->offset($start)
                    ->orderBy($condition['sort_by_column'] ?? 'id', $condition['sort_by_val'] ?? 'DESC');

                if (!empty($videoGltf)) {
                    $query->whereNotNull($videoGltf['column_name']);
                }

                // Execute the query
                $list = $query->get();

                // Get the maximum price from the database
                $maxPrice = MarketplaceProducts::where('store_id', $param2)
                    ->where('is_public', 'Y')
                    ->where('status', '1')
                    ->max('price');
                $maxPrice = number_format($maxPrice, 0, ".", "");
                $final_value = (20 / 100) * $maxPrice;
                $max_val = $maxPrice + $final_value;

                if (!$list->isEmpty()) {
                    $prices = $list->pluck('price');
                    $maxPrice = $prices->max();
                    $final_value = (20 / 100) * $maxPrice;
                    $max_val = $maxPrice + $final_value;
                    $response["max_val"] = $max_val;

                    if (request()->input('price_filter') != '') {
                        $response["max_val"] = $priceMax + ((20 / 100) * $maxPrice);
                        $response["minPrice"] = $priceMin;
                    } else {
                        $response["maxPrice"] = $maxPrice;
                        $response["minPrice"] = 0;
                    }
                } else {
                    $response["max_val"] = 1000;
                    $response["maxPrice"] = 1000;
                }

                $response["filter"] = [
                    [
                        "filter_name" => $store->name,
                        "filter_id" => $store->id,
                        "filter_type" => "stores",
                    ]
                ];
            } else {
                $list = MarketplaceStore::limit($limit)
                    ->offset($start)
                    ->where('name', 'LIKE', "%{$searchKeyword}%")
                    ->get();

                $response["max_val"] = 1000;
                $response["maxPrice"] = 1000;
                $response["filter_name"] = "Stores";
                $response["filter"] = [
                    [
                        "filter_name" => "Stores",
                        "filter_id" => "",
                        "filter_type" => "stores",
                    ]
                ];
            }
        } else if (!empty($param1) && $param1 == 'categories') {
            if (!empty($param2)) {
                $catDetails = MarketplaceCategory::find($param2);
                $storeDetails = MarketplaceStore::find($param3);

                if (!empty($param3)) {
                    $list = $this->getCategoryWiseProductList(
                        $param2,
                        $searchKeyword,
                        $start,
                        $limit,
                        $priceMin,
                        $priceMax,
                        'all',
                        'all',
                        $condition,
                        $param3,
                        $videoGltf
                    );
                } else {
                    $list = $this->getCategoryWiseProductList(
                        $param2,
                        $searchKeyword,
                        $start,
                        $limit,
                        $priceMin,
                        $priceMax,
                        'all',
                        'all',
                        $condition,
                        '',
                        $videoGltf
                    );
                }

                $maxPrice = MarketplaceProducts::where('category_id', $param2)
                    ->where('is_public', 'Y')
                    ->where('status', '1')
                    ->max('price');

                $finalValue = (20 / 100) * $maxPrice;
                $maxVal = $maxPrice + $finalValue;

                if (!empty($list)) {
                    $listArray = $list->toArray();
                    $prices = array_column($listArray, 'price');

                    if (!empty($prices)) {
                        $maxPrice = max($prices);
                        $finalValue = (20 / 100) * $maxPrice;
                        $maxVal = $maxPrice + $finalValue;
                        $response["max_val"] = $maxVal;

                        if ($request->input('price_filter') != '') {
                            $response["max_val"] = $priceMax + $finalValue;
                            $response["minPrice"] = $priceMin;
                        } else {
                            $response["maxPrice"] = $maxPrice;
                            $response["minPrice"] = 0;
                        }
                    } else {
                        $response["max_val"] = 1000;
                        $response["maxPrice"] = 1000;
                        $response["minPrice"] = 0;
                    }
                } else {
                    $response["max_val"] = 1000;
                    $response["maxPrice"] = 1000;
                    $response["minPrice"] = 0;
                }

                $filter = []; // Initialize $filter array

                if ($storeDetails) {
                    $data = [
                        "filter_name" => $storeDetails->name,
                        "filter_id" => $storeDetails->id,
                        "filter_type" => "stores"
                    ];
                    $filter[] = $data;
                }

                if ($catDetails) {
                    $data1 = [
                        "filter_name" => $catDetails->category_name,
                        "filter_id" => $catDetails->id,
                        "filter_type" => "categories"
                    ];
                    $filter[] = $data1;
                } else {
                    // Handle the case where $catDetails is null
                    $filter[] = [
                        "filter_name" => "Unknown Category",
                        "filter_id" => $param2,
                        "filter_type" => "categories"
                    ];
                }

                $response["filter"] = $filter;
            } else {
                $list = $this->getAllCategoryPagination($searchKeyword, $start, $limit);
                $response["max_val"] = 1000;
                $response["maxPrice"] = 1000;
                $response["minPrice"] = 0;

                $filterE = [];
                $data1 = [
                    "filter_name" => "Category",
                    "filter_id" => "",
                    "filter_type" => "categories"
                ];
                $filterE[] = $data1;

                $response["filter"] = $filterE;
            }
        } else if ($param1 == 'subcategories' && !empty($param2)) {
            $subcatDetails = MarketplaceSubCategory::find($param2);
            $storeDetails = MarketplaceStore::find($param3);
            $catDetails = MarketplaceCategory::find($param4);

            // Get product list based on subcategory
            $list = $this->getSubCategoryWiseProductList(
                $param2,
                $searchKeyword,
                $start,
                $limit,
                $priceMin,
                $priceMax,
                'all',
                $condition,
                $param3,
                $param4,
                $videoGltf
            );

            // Calculate maximum price
            $maxPrice = MarketplaceProducts::where('sub_category_id', $param2)
                ->where('is_public', 'Y')
                ->where('status', '1')
                ->max('price');

            // Adjust response based on retrieved list
            if ($list->isNotEmpty()) {
                $prices = $list->pluck('price');
                $maxPrice = $prices->max();
                $finalValue = (20 / 100) * $maxPrice;
                $maxVal = $maxPrice + $finalValue;

                $response["max_val"] = $maxVal;
                $response["maxPrice"] = $request->input('price_filter') ? $priceMax + $finalValue : $maxPrice;
                $response["minPrice"] = $request->input('price_filter') ? $priceMin : 0;
            } else {
                $response["max_val"] = 1000;
                $response["maxPrice"] = 1000;
                $response["minPrice"] = 0;
            }

            // Build filter array
            $filter = [];
            if ($storeDetails) {
                $filter[] = [
                    "filter_name" => $storeDetails->name,
                    "filter_id" => $storeDetails->id,
                    "filter_type" => "stores"
                ];
            }
            if ($catDetails) {
                $filter[] = [
                    "filter_name" => $catDetails->category_name,
                    "filter_id" => $catDetails->id,
                    "filter_type" => "categories"
                ];
            }
            if ($subcatDetails) {
                $filter[] = [
                    "filter_name" => $subcatDetails->sub_category_name,
                    "filter_id" => $subcatDetails->id,
                    "filter_type" => "subcategories"
                ];
            }

            $response["filter"] = $filter;
        } else if (!empty($param1) && $param1 == 'wishlist') {
            $userId = $request->input('user_id');
            if ($userId) {
                $list = $this->getWishlistedProduct($priceMin, $priceMax, $start, $limit, $searchKeyword, $condition, $videoGltf, $userId);
                $maxPriceQuery = DB::table('marketplace_products')
                    ->where('is_public', 'Y')
                    ->where('status', '1')
                    ->where('wishlist', 'like', '%' . $userId . '%')
                    ->max('price');

                $maxPrice = $maxPriceQuery ?: 0;

                if ($list->isNotEmpty()) {
                    $prices = $list->pluck('price')->toArray();
                    $maxPrice = max($prices);
                    $finalValue = (20 / 100) * $maxPrice;
                    $maxVal = $maxPrice + $finalValue;

                    $response['max_val'] = $maxVal;
                    if ($request->input('price_filter') != '') {
                        $response['maxPrice'] = $priceMax + $finalValue;
                        $response['minPrice'] = $priceMin;
                    } else {
                        $response['maxPrice'] = $maxPrice;
                        $response['minPrice'] = 0;
                    }
                } else {
                    $response['max_val'] = 1000;
                    $response['maxPrice'] = 1000;
                }
                $response['filter'] = [];
            } else {
                $response['max_val'] = 1000;
                $response['maxPrice'] = 1000;
            }
        } else if (!empty($param1) && $param1 == 'storecat') {
            $list = $this->getAllCategoryWith($searchKeyword, $param2, $start, $limit);

            $response["max_val"] = 1000;
            $response["maxPrice"] = 1000;
            $data1 = [
                "filter_name" => "Category",
                "filter_id" => "",
                "filter_type" => "categories",
            ];
            $filter[] = $data1;
            $response["filter"] = $filter;
        } else if (!empty($param1) && $param1 == 'subcat') {
            $list = $this->getSubCategoryListWithCat($limit, $start, $searchKeyword, $param2);
            $response["max_val"] = 1000;
            $response["maxPrice"] = 1000;
            $data2 = [
                "filter_name" => "Subcategory",
                "filter_id" => "",
                "filter_type" => "subcategories",
            ];
            $filter[] = $data2;
            $response["filter"] = $filter;
        } else {
            $list = $this->getProductList($priceMin, $priceMax, 'all', $start, $limit, $searchKeyword, $condition, $videoGltf);
            $maxPrice = DB::table('marketplace_products')
                ->where('is_public', 'Y')
                ->where('status', '1')
                ->max('price');

            if (!empty($list)) {
                $list1 = $start != 0 ? $this->getProductList($priceMin, $priceMax, 'all', 0, $start, $searchKeyword, $condition) : $list;
                $prices = array_column($list1->toArray(), 'price');
                $maxPrice = max($prices);
                $finalValue = (20 / 100) * $maxPrice;
                $maxVal = $maxPrice + $finalValue;
                $response["max_val"] = $maxVal;
                if ($request->input('price_filter') != '') {
                    $response["max_val"] = $priceMax + ((20 / 100) * $maxPrice);
                    $response["maxPrice"] = $priceMax;
                } else {
                    $response["maxPrice"] = $maxPrice;
                    $response["minPrice"] = 0;
                }
            }
        }
        $response["list"] = $list;


        foreach ($list as $value) {
            $value->image = '';
            $value->link = '#';
            $value->alt = '';
            $value->event = '';
            $value->button_show = false;
            $value->details = '';
            $value->details_style = '';
            $value->no_of_products = '';
            $value->rating = '';
            $value->paid_price = '';
            $value->discount_percent = '';

            if ($param1 === "stores" && empty($param2)) {
                $value->image = $this->isFileExists(
                    url("uploads/{$value->image_path}"),
                    null
                );
                $value->alt = $this->displayCharactermarket($value->description, 100) . "-" . $this->displayCharactermarket($value->name, 30) . "-" . basename($value->image_path);
                $value->event = 'getProductFilterList(event,"stores","' . $value->id . '");';
                $value->button_show = false;
                $value->details = displayString(25, $value->description);
                $value->details_style = "fs13";
                $value->no_of_products = $value->no_of_products;
            } elseif ($param1 === "categories" && empty($param2)) {
                $value->image = $this->isFileExists(
                    url("uploads/{$value->image_path}"),
                    null
                );
                $value->alt = $this->displayCharactermarket($value->name, 100) . "-" . $this->displayCharactermarket($value->name, 30) . "-" . basename($value->image_path);
                $value->event = 'getProductFilterList(event,"categories","' . $value->id . '");';
                $value->button_show = false;
                $value->no_of_products = $value->product_count;
            } elseif ($param1 === "storecat" || $param1 === "subcategories" || $param1 === "subcat") {
                $value->image = $this->isFileExists(
                    url("uploads/{$value->image_path}"),
                    null
                );
                $value->alt = $this->displayCharactermarket($value->name, 100) . "-" . $this->displayCharactermarket($value->name, 30) . "-" . basename($value->image_path);
                $value->event = 'getProductFilterList(event,"categories","' . $value->id . '");';
                $value->button_show = false;
                $value->no_of_products = $value->product_count;
            } else {
                $value->image = $this->ProductisFileExists(url("uploads/{$value->product_thumb_path}"),  url("uploads/{$value->product_image_path}"));
                $value->link = url('marketplace/product/' . $value->id);
                $value->alt = $this->displayCharactermarket($value->description, 100) . "-" . $this->displayCharactermarket($value->name, 30) . "-" . basename($value->product_image_path);
                $value->button_show = true;
                $value->details = "$" . $value->price;
                $value->details_style = "fs20 text-theme";
                $value->rating = $this->StarReviewsCal($value->id);
                if (!empty($value->paid_price)) {
                    $discountPercentage = (($value->paid_price - $value->price) / $value->paid_price) * 100;
                    $value->paid_price = '<sub class="text-decoration-line-through">$' . $value->paid_price . '</sub>';
                    $value->discount_percent = ' <sub class="badge bg-theme-btn fs11 text-theme">' . round($discountPercentage) . '% off</sub>';
                }
            }

            $value->name = $value->name;
            $result[] = $value;
        }


        $response["status"] = !empty($result);
        $response["list"] = $result;
        $responseJson = json_encode($response);

        // Check if json_encode was successful
        if ($responseJson === false) {
            // Handle JSON encoding error
            return response()->json(['status' => false, 'message' => 'Failed to encode JSON'], 500);
        }

        // Convert the JSON string encoding to UTF-8
        $newString = mb_convert_encoding($responseJson, "UTF-8", "UTF-8");

        // Return the JSON response
        return response()->json(json_decode($newString, true));
    }


    public function getAllCategoryPagination($search_keyword = '', $start = '', $limit)
    {
        $query = MarketplaceCategory::query();

        if (!empty($search_keyword)) {
            $query->where('category_name', 'LIKE', "%$search_keyword%");
        }

        $query->select('id', 'category_name as name', 'image_path', 'image_ext')
            ->leftJoin('marketplace_products as spd', 'spd.category_id', '=', 'marketplace_category.id')
            ->leftJoin('marketplace_stores as sm', 'spd.store_id', '=', 'sm.id')
            ->where('category_name', '!=', '')
            ->where('sm.is_disabled', 'N')
            ->where('spd.is_public', 'Y')
            ->where('spd.status', '1')
            ->groupBy('id')
            ->havingRaw('count(spd.id) >= ?', [1])
            ->orderByRaw('count(spd.id) DESC')
            ->limit($limit)
            ->offset($start);

        return $query->get();
    }


    public function getCategoryWiseProductList($cat_id, $search_keyword = '', $start = '', $limit = '', $price_min = '', $price_max = '', $product_type = 'all', $sub_cat_id = 'all', $condition = [], $store_id = "", $video_gltf = [])
    {
        $query = MarketplaceProducts::query();

        if (!empty($search_keyword)) {
            $query->where(function ($q) use ($search_keyword) {
                $q->where('product_name', 'LIKE', "%$search_keyword%")
                    ->orWhereHas('store', function ($q) use ($search_keyword) {
                        $q->where('name', 'LIKE', "%$search_keyword%");
                    });
            });
        }

        if ($product_type != 'all') {
            $query->where('product_type', $product_type);
        }

        if ($sub_cat_id != 'all') {
            $query->where('sub_category_id', $sub_cat_id);
        }

        $query->select('id', 'user_id', 'store_id', 'product_name as name', 'description', 'category_id', 'sub_category_id', 'category_tag_id', 'qr_code_image', 'qr_code_image_ext', 'product_type', 'model_no_item_no', 'price', 'delivery_type', 'product_document_attachment', 'product_image_path', 'product_thumb_path', 'publisher_application_id', 'checkout_qr_code_image', 'checkout_qr_code_image_ext', 'is_public', 'shipping_place_id', 'delivery_charge', 'paid_price')
            ->where('category_id', $cat_id)
            ->whereHas('store', function ($q) {
                $q->where('is_disabled', 'N');
            })
            ->where('is_public', 'Y')
            ->where('status', '1')
            ->limit($limit)
            ->offset($start);

        if (is_array($condition) && array_key_exists("sort_by_column", $condition) && array_key_exists("sort_by_val", $condition)) {
            $column = ($condition['sort_by_column'] == "CAST(name AS UNSIGNED) ") ? "CAST(product_name AS UNSIGNED)" : $condition['sort_by_column'];
            $query->orderBy($column, $condition['sort_by_val']);
        }

        if ($price_min != '') {
            $query->where('price', '>=', $price_min);
        }

        if ($price_max != '') {
            $query->where('price', '<=', $price_max);
        }

        if (!empty($store_id)) {
            $query->where('store_id', $store_id);
        }

        if (!empty($video_gltf)) {
            $query->where($video_gltf['column_name'], '!=', 'null');
        }

        return $query->get();
    }


    public function getSubCategoryWiseProductList($sub_category_id, $search_keyword = '', $start = 0, $limit = 3, $price_min = '', $price_max = '', $product_type = 'all', $condition = [], $store_id = "", $category_id = "", $video_gltf = [])
    {
        $query = MarketplaceProducts::where('sub_category_id', $sub_category_id)
            ->where('is_public', 'Y')
            ->where('status', '1');

        if ($search_keyword) {
            $query->where('product_name', 'like', "%{$search_keyword}%");
        }
        if ($product_type != 'all') {
            $query->where('product_type', $product_type);
        }
        if ($price_min !== '') {
            $query->where('price', '>=', $price_min);
        }
        if ($price_max !== '') {
            $query->where('price', '<=', $price_max);
        }
        if ($store_id) {
            $query->where('store_id', $store_id);
        }
        if ($category_id) {
            $query->where('category_id', $category_id);
        }
        if (!empty($video_gltf)) {
            $query->whereNotNull($video_gltf['column_name']);
        }
        if (!empty($condition['sort_by_column']) && !empty($condition['sort_by_val'])) {
            $column = $condition['sort_by_column'] == "CAST(name AS UNSIGNED)" ? "CAST(product_name AS UNSIGNED)" : $condition['sort_by_column'];
            $query->orderBy($column, $condition['sort_by_val']);
        }

        return $query->skip($start)->take($limit)->get();
    }

    public function getSubCategoryList($limit = '', $start = '', $search_keyword = '')
    {
        $query = MarketplaceSubCategory::leftJoin('marketplace_category', 'marketplace_sub_category.category_id', '=', 'marketplace_category.id')
            ->leftJoin('marketplace_products', 'marketplace_sub_category.id', '=', 'marketplace_products.sub_category_id')
            ->select('marketplace_sub_category.id', 'marketplace_sub_category.category_id', 'marketplace_sub_category.sub_category_name', 'marketplace_sub_category.image_path', 'marketplace_sub_category.image_ext', 'marketplace_category.category_name', DB::raw('COUNT(marketplace_products.id) as product_count'))
            ->where('marketplace_sub_category.sub_category_name', '!=', '')
            ->where('marketplace_sub_category.type', '1')
            ->where('marketplace_products.is_public', 'Y')
            ->where('marketplace_products.status', '1');

        if ($search_keyword) {
            $query->where('marketplace_sub_category.sub_category_name', 'like', "%{$search_keyword}%");
        }

        if ($limit && $start) {
            $query->skip($start)->take($limit);
        } else {
            $query->take($limit);
        }

        return $query->groupBy('marketplace_sub_category.id')->orderBy(DB::raw('COUNT(marketplace_products.id)'), 'desc')->get();
    }


    protected function getWishlistedProduct($price_min, $price_max, $start, $limit, $search_keyword, $condition, $video_gltf, $user_id)
    {
        $query = DB::table('marketplace_products as spd')
            ->join('marketplace_stores as sm', 'spd.store_id', '=', 'sm.id')
            ->select([
                'spd.id',
                'spd.user_id',
                'spd.store_id',
                'spd.product_name as name',
                'spd.description',
                'spd.category_id',
                'spd.sub_category_id',
                'spd.category_tag_id',
                'spd.qr_code_image',
                'spd.qr_code_image_ext',
                'spd.product_type',
                'spd.model_no_item_no',
                'spd.price',
                'spd.delivery_type',
                'spd.product_document_attachment',
                'spd.product_image_path',
                'spd.product_thumb_path',
                'spd.publisher_application_id',
                'spd.checkout_qr_code_image',
                'spd.checkout_qr_code_image_ext',
                'spd.is_public',
                'spd.wishlist',
                'spd.shipping_place_id',
                'spd.delivery_charge',
                'spd.paid_price'
            ]);

        if (!empty($price_min)) {
            $query->where('spd.price', '>=', $price_min);
        }
        if (!empty($price_max)) {
            $query->where('spd.price', '<=', $price_max);
        }
        $query->where('sm.is_disabled', 'N')
            ->where('spd.is_public', 'Y')
            ->where('spd.status', '1')
            ->where('spd.wishlist', 'like', '%' . $user_id . '%');

        if (!empty($condition['sort_by_column']) && !empty($condition['sort_by_val'])) {
            $column = $condition['sort_by_column'];
            $query->orderBy($column, $condition['sort_by_val']);
        }

        if (!empty($video_gltf)) {
            $query->whereNotNull('spd.' . $video_gltf['column_name']);
        }

        // Print SQL query and bindings
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // For debugging purposes
        Log::info('SQL Query: ' . $sql);
        Log::info('Bindings: ' . print_r($bindings, true));

        return $query->skip($start)->take($limit)->get();
    }



    public function getAllCategoryWith($search_keyword = '', $store_id = "", $start = 0, $limit = 10)
    {
        $query = DB::table('marketplace_category as sc')
            ->select('sc.id', 'sc.category_name as name', 'sc.image_path', 'sc.image_ext', DB::raw('COUNT(spd.id) as product_count'))
            ->leftJoin('marketplace_products as spd', 'spd.category_id', '=', 'sc.id')
            ->leftJoin('marketplace_stores as sm', 'spd.store_id', '=', 'sm.id')
            ->where('sc.category_name', '!=', '')
            ->where('sm.is_disabled', 'N')
            ->where('spd.is_public', 'Y')
            ->where('spd.status', '1')
            ->where('spd.store_id', $store_id)
            ->groupBy('sc.id', 'sc.category_name', 'sc.image_path', 'sc.image_ext') // Include all non-aggregated columns here
            ->havingRaw('COUNT(spd.id) >= 1')
            ->orderBy('product_count', 'DESC')
            ->limit($limit)
            ->offset($start)
            ->get();

        return $query;
    }
    public function getSubCategoryListWithCat($limit = '', $start = '', $search_keyword = '', $cat_id = "")
    {
        try {
            $query = DB::table('marketplace_sub_category as sps')
                ->select('sps.id', 'sps.parent_category_id', 'sps.sub_category_name as name', 'sps.image_path', 'sps.image_ext', 'sps.date_time', 'spc.category_name as category_name', DB::raw('COUNT(pr.id) as product_count'))
                ->leftJoin('marketplace_category as spc', 'sps.parent_category_id', '=', 'spc.id')
                ->leftJoin('marketplace_products as pr', 'sps.id', '=', 'pr.sub_category_id')
                ->where('sps.sub_category_name', '!=', '')
                ->where('sps.type', 1)
                ->where('pr.is_public', 'Y')
                ->where('pr.status', '1')
                ->where('sps.parent_category_id', $cat_id)
                ->groupBy('sps.id', 'sps.parent_category_id', 'sps.sub_category_name', 'sps.image_path', 'sps.image_ext', 'sps.date_time', 'spc.category_name')
                ->orderBy('product_count', 'DESC');

            // Apply pagination if provided
            if ($limit !== '' && $start !== '') {
                $query->limit($limit)->offset($start);
            } else {
                $query->limit($limit);
            }

            // Get the SQL query for debugging
            $sql = $query->toSql();
            Log::info("SQL Query: " . $sql);

            // Bindings for the SQL query
            $bindings = $query->getBindings();
            Log::info("Bindings: ", $bindings);

            // Execute the query and return results
            return $query->get();
        } catch (\Exception $e) {
            // Log any exceptions
            Log::error("Error in getSubCategoryListWithCat: " . $e->getMessage());
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }


    public function getProductList($price_min = '', $price_max = '', $product_type = 'all', $start = 0, $limit = 10, $search_keyword = '', $condition = [], $video_gltf = [])
    {
        $query = DB::table('marketplace_products as spd')
            ->select('spd.id', 'spd.user_id', 'spd.store_id', 'spd.product_name as name', 'spd.description', 'spd.category_id', 'spd.sub_category_id', 'spd.category_tag_id', 'spd.qr_code_image', 'spd.qr_code_image_ext', 'spd.product_type', 'spd.model_no_item_no', 'spd.price', 'spd.delivery_type', 'spd.product_document_attachment', 'spd.product_image_path', 'spd.product_thumb_path', 'spd.publisher_application_id', 'spd.checkout_qr_code_image', 'spd.checkout_qr_code_image_ext', 'spd.is_public', 'spd.shipping_place_id', 'spd.delivery_charge', 'spd.paid_price', 'spd.product_thumb_path')
            ->leftJoin('marketplace_stores as sm', 'spd.store_id', '=', 'sm.id')
            ->where('sm.is_disabled', 'N')
            ->where('spd.is_public', 'Y')
            ->where('spd.status', '1');

        if ($product_type != 'all') {
            $query->where('spd.product_type', $product_type);
        }

        if (!empty($price_min)) {
            $query->where('spd.price', '>=', $price_min);
        }

        if (!empty($price_max)) {
            $query->where('spd.price', '<=', $price_max);
        }

        if (!empty($search_keyword)) {
            $query->where('spd.product_name', 'like', "%$search_keyword%");
        }

        if (!empty($condition['sort_by_column']) && !empty($condition['sort_by_val'])) {
            $column = $condition['sort_by_column'] == 'CAST(name AS UNSIGNED)' ? 'spd.name' : $condition['sort_by_column'];
            $query->orderBy($column, $condition['sort_by_val']);
        }

        if (!empty($video_gltf)) {
            $query->whereNotNull('spd.' . $video_gltf['column_name']);
        }

        return $query->limit($limit)->offset($start)->get();
    }


    public function starReviewsCal($product_id = '', $page = '')
    {
        $star_totals = [
            'five' => StoreProductReviews::where(['rating' => 5, 'product_id' => $product_id])->count(),
            'four' => StoreProductReviews::where(['rating' => 4, 'product_id' => $product_id])->count(),
            'three' => StoreProductReviews::where(['rating' => 3, 'product_id' => $product_id])->count(),
            'two' => StoreProductReviews::where(['rating' => 2, 'product_id' => $product_id])->count(),
            'one' => StoreProductReviews::where(['rating' => 1, 'product_id' => $product_id])->count(),
        ];

        $total_stars_from_review = ($star_totals['five'] * 5) + ($star_totals['four'] * 4) + ($star_totals['three'] * 3) + ($star_totals['two'] * 2) + $star_totals['one'];
        $count = $star_totals['five'] + $star_totals['four'] + $star_totals['three'] + $star_totals['two'] + $star_totals['one'];
        $rating_out_of_5 = $count > 0 ? number_format($total_stars_from_review / $count, 1, '.', '') : 0;

        $string = '';
        for ($i = 0; $i < 5; $i++) {
            if ($i < floor($rating_out_of_5)) {
                $string .= $page === 'dashboard' ? "<em class='icon ni ni-star-fill' style='color:red'></em>" : "<i class='bx bxs-star' style='color:red'></i>";
            } elseif ($i == floor($rating_out_of_5) && $rating_out_of_5 - $i >= 0.5) {
                $string .= $page === 'dashboard' ? "<em class='icon ni ni-star-half-fill' style='color:red'></em>" : " <i class='bx bxs-star-half' style='color:red'></i>";
            } else {
                $string .= $page === 'dashboard' ? "<em class='icon ni ni-star' style='color:red'></em>" : "<i class='bx bx-star' style='color:red'></i>";
            }
        }

        return $string . "($rating_out_of_5) ";
    }

    public  function isFileExists($image_path = '', $placeholder_image_path = '')
    {
        if (file_exists(public_path($image_path)) && !empty($image_path)) {
            return asset($image_path);
        } elseif (!empty($placeholder_image_path) && file_exists(public_path($placeholder_image_path))) {
            return asset($placeholder_image_path);
        } else {
            return asset('assets/images/image-placeholder.png');
        }
    }


    public  function displayCharactermarket($text, $limit)
    {
        $text = strip_tags($text);
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $text = str_replace("\n", "", $text);
        $text = trim($text);

        if (strlen($text) <= $limit) {
            return $text;
        }

        $truncatedText = substr($text, 0, $limit);
        $lastSpaceIndex = strrpos($truncatedText, ' ');

        if ($lastSpaceIndex !== false) {
            $truncatedText = substr($truncatedText, 0, $lastSpaceIndex);
        }

        return $truncatedText;
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

    public function addToWishList(Request $request)
    {
        DB::beginTransaction();

        try {
            // Retrieve the user from request attributes
            $user = $request->attributes->get('user');

            // Check if the user is authenticated
            if (!$user) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Please log in first',
                    'toast' => true
                ]);
            }

            $userId = $user->id; // Get the user's ID
            $productId = $request->input('product_id');

            // Check if product ID is provided
            if (empty($productId)) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Product ID is required',
                    'toast' => true
                ]);
            }

            $propertyData = $this->addToWishListHelper($userId, $productId);

            if ($propertyData['status'] == 0) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Product not found',
                    'toast' => true
                ]);
            } elseif ($propertyData['status'] == 2) {
                DB::commit();
                return generateResponse([
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Removed from wishlist',
                    'value' => '2',
                    'toast' => true
                ]);
            } else {
                DB::commit();
                return generateResponse([
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Added to wishlist',
                    'value' => '1',
                    'toast' => true
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to update wishlist: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while updating the wishlist. Please try again later.',
                'toast' => true
            ]);
        }
    }

    public function addToWishListHelper($userId, $productId)
    {
        DB::beginTransaction();

        try {
            $product = DB::table('marketplace_products')
                ->select('wishlist')
                ->where('id', $productId)
                ->first();

            // Check if the product exists
            if (!$product) {
                DB::rollBack();
                return ['status' => 0]; // Product not found
            }

            $wishlist = [];
            $status = 1;

            if (!empty($product->wishlist)) {
                $wishlist = explode(',', $product->wishlist);

                if (in_array($userId, $wishlist)) {
                    if (($key = array_search($userId, $wishlist)) !== false) {
                        unset($wishlist[$key]);
                        $status = 2;
                    }
                } else {
                    $wishlist[] = $userId;
                }
            } else {
                $wishlist = [$userId];
            }

            $wishlist = implode(',', $wishlist);

            DB::table('marketplace_products')
                ->where('id', $productId)
                ->update(['wishlist' => $wishlist]);

            DB::commit();
            return ['status' => $status];
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to update wishlist in helper: ' . $e->getMessage());

            // Return an error status
            return ['status' => 0];
        }
    }


    public function enableDisableProduct(Request $request)
    {
        DB::beginTransaction();

        try {
            $action = $request->input('action');
            $productId = $request->input('product_id');
            $prodStatusUserId = $request->input('prod_status_user_id');

            // Validate input
            if (empty($productId) || !in_array($action, ['Y', 'N'])) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 400,
                    'status' => false,
                    'message' => 'Invalid input data',
                    'toast' => true
                ]);
            }

            // Determine the new status based on the action
            $status = $action === 'N' ? 'Y' : 'N';
            $message = $status === 'Y' ? 'Product enabled successfully' : 'Product disabled successfully';

            // Update the product status in the database
            $updated = MarketplaceProducts::where('id', $productId)
                ->update([
                    'is_public' => $status,
                    'prod_status_user_id' => $prodStatusUserId,
                ]);

            if (!$updated) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 400,
                    'status' => false,
                    'message' => 'Failed to update product status',
                    'toast' => true
                ]);
            }

            DB::commit();
            // Prepare and return the response
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => $message,
                'toast' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to update product status: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while updating the product status. Please try again later.',
                'toast' => true
            ]);
        }
    }



    public function enableDisableStore(Request $request)
    {
        DB::beginTransaction();
        try {
            $action = $request->input('action');
            $storeId = $request->input('store_id');
            $userId = $request->input('user_id');

            // Determine the new status based on the action
            $status = $action === 'Y' ? 'N' : 'Y';
            $message = $status === 'Y' ? 'Store enabled successfully' : 'Store disabled successfully';

            // Update the store status in the database
            $updated = MarketplaceStore::where('id', $storeId)
                ->where('user_id', $userId)
                ->update([
                    'is_disabled' => $status,
                ]);

            if (!$updated) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 400,
                    'status' => false,
                    'message' => 'Failed to update store status',
                    'toast' => true
                ]);
            }

            DB::commit();
            // Prepare and return the response
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => $message,
                'toast' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to enable/disable store: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while updating the store status. Please try again later.',
                'toast' => true
            ]);
        }
    }

    public function adminStatusChange(Request $request)
    {
        DB::beginTransaction();

        try {
            // Retrieve the user performing the action from the request attributes
            $user = $request->attributes->get('user');

            // Fetch product details
            $productDetails = MarketplaceProducts::find($request->input('product_id'));

            // Check if product exists
            if (!$productDetails) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 404,
                    'status' => false,
                    'message' => 'Product not found',
                    'toast' => true
                ]);
            }

            // Fetch the user details of the product owner
            $userDetails = User::find($productDetails->user_id);

            // Determine action and set the status
            $status = $request->input('action');
            switch ($status) {
                case '1':
                    $notifType = 'product_status';
                    $noMsg = "Product Approved";
                    $responseMessage = 'Product Approved Successfully';
                    $message = "Your {$productDetails->product_name} product has been approved by an administrator and is now uploaded to the marketplace.";
                    break;
                case '2':
                    $notifType = 'product_status';
                    $noMsg = "Product Rejected";
                    $responseMessage = 'Product Rejected Successfully';
                    $message = "Your {$productDetails->product_name} product has been rejected by an administrator.";
                    break;
                case '4':
                    $notifType = 'product_status';
                    $noMsg = "Product Blocked";
                    $responseMessage = 'Product Blocked Successfully';
                    $message = "Your {$productDetails->product_name} product has been blocked by an administrator.";
                    break;
                default:
                    DB::rollBack();
                    return generateResponse([
                        'type' => 'error',
                        'code' => 400,
                        'status' => false,
                        'message' => 'Invalid action',
                        'toast' => true
                    ]);
            }

            // Prepare the link
            $link = url('/' . $productDetails->id . '/' . Str::slug($productDetails->product_name));

            // Update the product status
            $productDetails->status = $status;
            $productDetails->save();

            // Prepare the notification data
            $notifData = [
                'to_user_id' => $productDetails->user_id,
                'from_user_id' => $user->id, // Using the retrieved user (admin performing the action)
                'message' => $message,
                'title' => $noMsg,
                'link' => $link,
                'type' => $notifType,
            ];

            // Send notification email to the product owner
            Mail::to($userDetails->email)->send(new ProductStatusNotification($notifData));

            DB::commit();

            // Prepare and return the response
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => $responseMessage,
                'toast' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to change product status: ' . $e->getMessage());

            // Return a user-friendly error response
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while changing the product status. Please try again later.',
                'toast' => true
            ]);
        }
    }


    public function allowProduct(Request $request)
    {
        DB::beginTransaction();

        try {
            $productId = $request->input('id');
            $url = $request->input('url');
            $notify = $request->input('data_notify');

            // Find the product
            $product = MarketplaceProducts::find($productId);

            // Check if product exists
            if (!$product) {
                DB::rollBack();
                return generateResponse([
                    'type' => 'error',
                    'code' => 404,
                    'status' => false,
                    'message' => 'Product not found',
                    'toast' => true
                ]);
            }

            $msg = "";
            $color = "";
            $subject = "";

            // Determine the message and subject based on product stock
            if ($product->quantity == 0) {
                $msg = "The product {$product->product_name} is running low on stock. Consider restocking or managing inventory to avoid running out of stock.";
                $color = "#FF0000";
                $subject = "Alert: Product {$product->product_name} is out of stock";
            } elseif ($product->stock_notify_before_qnt > $product->quantity) {
                $msg = "The product {$product->product_name} is running low on stock. Consider restocking or managing inventory to avoid running out of stock.";
                $color = "#FF0000";
                $subject = "Alert: Product {$product->product_name} is low in stock";
            } else {
                $msg = "The product {$product->product_name} is currently in stock, and there are sufficient quantities available.";
                $color = "#008000";
                $subject = "Quality alert for new product: Take action now";
            }

            // Send notifications based on the 'data_notify' parameter
            if ($notify !== 'notify') {
                $subscribers = MarketplaceSiteSubscription::where('marketplace', 1)->get();

                foreach ($subscribers as $subscriber) {
                    try {
                        $this->sendNotificationEmail($subscriber->email_address, $product, $msg, $color, $subject, $url);
                    } catch (\Exception $e) {
                        Log::error('Failed to send notification email to subscriber: ' . $subscriber->email_address . ' Error: ' . $e->getMessage());
                    }
                }

                DB::commit();
                return generateResponse([
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Notifications sent to subscribers.',
                    'toast' => true
                ]);
            } else {
                $deliveryUser = User::find($product->user_id);
                if ($deliveryUser) {
                    try {
                        $this->sendNotificationEmail($deliveryUser->email, $product, $msg, $color, $subject, $url);
                    } catch (\Exception $e) {
                        Log::error('Failed to send notification email to delivery user: ' . $deliveryUser->email . ' Error: ' . $e->getMessage());
                    }
                }

                DB::commit();
                return generateResponse([
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Notification sent to the delivery user.',
                    'toast' => true
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to allow product: ' . $e->getMessage());

            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while processing your request. Please try again later.',
                'toast' => true
            ]);
        }
    }


    private function sendNotificationEmail($to, $product, $msg, $color, $subject, $url)
    {
        // Determine base URL for product image
        $baseUrl = file_exists(public_path($product->product_image_path))
            ? asset($product->product_image_path)
            : 'https://silocloud.com/assets/new_email_template/SiloCLoud.png';

        // Prepare the email view
        $data = [
            'msg' => $msg,
            'color' => $color,
            'description' => $product->description,
            'url' => $url,
            'base_url' => $baseUrl,
            'subject' => $subject,
            'linkTitle' => 'View Product', // Set a default link title or customize as needed
            'supportMail' => config('app.support_mail'), // Get support email from configuration
            'projectName' => config('app.app_name'), // Get project name from configuration
            'logoUrl' => asset('assets/images/logo/logo-dark.png') // Use a default logo URL
        ];

        // Send the email
        Mail::send('mail-templates.product_notification', $data, function ($message) use ($to, $subject) {
            $message->to($to)
                ->subject($subject);
        });
    }
    public function productInquiriesList(Request $request)
    {
        $start = $request->input('start');
        $limit = $request->input('length');
        $search = $request->input('search.value');
        $order_by = $request->input('order')[0] ?? [];
        $product_id = $request->input('product_id');

        // Default column ordering
        $order_by_column = 'id';
        $order_by_column_val = 'asc';

        if (isset($order_by['column'])) {
            $columns = $request->input('columns', []);
            $order_by_column = $columns[$order_by['column']]['data'] ?? $order_by_column;
            $order_by_column_val = $order_by['dir'] ?? $order_by_column_val;
        }

        // Column ordering and search condition
        $condition1 = [];
        if ($search) {
            $condition1 = ['question' => $search];
        }

        // Fetching questions related to the product
        $questions = StoreProductQuestions::where('product_id', $product_id)
            ->where($condition1)
            ->orderBy($order_by_column, $order_by_column_val)
            ->offset($start)
            ->limit($limit)
            ->get();

        $recordsFiltered = StoreProductQuestions::where('product_id', $product_id)->where($condition1)->count();
        $result = [];
        $i = 1;

        foreach ($questions as $question) {
            $product_details = MarketplaceProducts::find($question->product_id);
            $customer_details = User::find($question->user_id);
            $user_profile = UserProfile::where('user_id', $customer_details->id)->first();

            $result[] = [
                'id' => $i++,
                'question' => $question->question,
                'answer' => $question->answer,
                'product_name' => $product_details->product_name,
                'customer_name' => $customer_details->username,
                'customer_email' => $customer_details->email,
                'customer_first_name' => $user_profile->first_name,
                'customer_last_name' => $user_profile->last_name,
                'customer_phone' => $user_profile->phone_number,
                'is_answered' => $question->is_answered,
                'status' => $question->status,
                'created_at' => $question->created_at,
                'updated_at' => $question->updated_at,
            ];
        }

        // Preparing response
        $response = [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => StoreProductQuestions::where('product_id', $product_id)->count(),
            'recordsFiltered' => $recordsFiltered,
            'data' => $result,
        ];

        // Returning a successful response using generateResponse
        return generateResponse([
            'type' => 'success',
            'code' => 200,
            'status' => true,
            'message' => 'Product inquiries retrieved successfully',
            'toast' => true
        ], $response);
    }


    public function productReturnReplaceList(Request $request)
    {
        try {
            // Retrieve the user from request attributes
            $user = $request->attributes->get('user');
            if (!$user) {
                return generateResponse([
                    'type' => 'error',
                    'code' => 401,
                    'status' => false,
                    'message' => 'Unauthorized access.',
                    'toast' => true
                ], [], 401);
            }

            $start = $request->query('start', 0); // Default to 0 if not provided
            $limit = $request->query('length', 10); // Default to 10 if not provided
            $search = $request->query('search', []);
            $order = $request->query('order', []);
            $column = $request->query('column', 'id'); // Default to 'id' if not provided
            $value = $request->query('value', '');

            // Initialize conditions
            $conditions1 = [];

            // Apply search conditions if search value is provided
            if (!empty($search['value'])) {
                $conditions1['like'] = $search['value'];
            }

            // Initialize orderByColumn and orderByColumnVal with defaults
            $orderByColumn = 'id';
            $orderByColumnVal = 'asc';

            // Check if $order has at least one element and contains valid 'column' and 'dir'
            if (!empty($order[0]['column']) && !empty($order[0]['dir'])) {
                // Extract orderBy column and direction safely
                $columns = $request->query('columns', []);
                if (isset($columns[$order[0]['column']]['data'])) {
                    $orderByColumn = $columns[$order[0]['column']]['data'];
                }
                $orderByColumnVal = $order[0]['dir'];
            }

            // Retrieve data
            $requests = MarketplaceOrderReturnReplaceRequest::with('product', 'user')
                ->where('user_id', $user->id) // Use the user ID from the request attribute
                ->when($conditions1, function ($query) use ($conditions1) {
                    foreach ($conditions1 as $key => $value) {
                        $query->where('reason', 'like', "%{$value}%");
                    }
                })
                ->skip($start)
                ->take($limit)
                ->orderBy($orderByColumn, $orderByColumnVal)
                ->get();

            // Get the total number of records matching the filter
            $recordsFiltered = MarketplaceOrderReturnReplaceRequest::where('user_id', $user->id) // Use the user ID from the request attribute
                ->when($conditions1, function ($query) use ($conditions1) {
                    foreach ($conditions1 as $key => $value) {
                        $query->where('reason', 'like', "%{$value}%");
                    }
                })
                ->count();

            $result = [];

            foreach ($requests as $request) {
                $product = MarketplaceProducts::find($request->product_id);
                $customer = UserProfile::find($request->user_id);

                $request->customer = $customer ? $customer->first_name . ' ' . $customer->last_name : '';
                $request->product_name = '<span class="tb-product">
            <img src="' . (Storage::exists($request->product_image_path) ? Storage::url($request->product_image_path) : 'assets/images/image-placeholder.png') . '" alt="Product Image" class="thumb" style="height:50px;width:50px; border-radius:50%;margin-right: 10px;"> ' . \Illuminate\Support\Str::limit($request->product_name, 15) . '</span>';
                $request->order_id = $request->order_id;
                $request->paid_amount = "$" . number_format((($request->price * $request->quantity) + $request->delivery_charge), 2);

                $request->request_type = $request->request_type == "1" ? "Replace" : "Return";
                $request->reason = $request->reason ? \Illuminate\Support\Str::limit($request->reason, 80) : "-";
                $request->created_at = $request->created_at->format('d M Y H:i');

                $action = "";
                if ($request->request_status == '0') {
                    $action .= ' <a href="JavaScript:void(0);" data-toggle="tooltip" rel="tooltip" title="Start pickup" data-id="' . $request->id . '" class="fs-22px" onclick="openProductPopUp(event, ' . $request->order_id . ')"><em class="icon ni ni-package-fill"></em></a>';
                } else if ($request->product_collect_status != '1' && $request->request_status != '0') {
                    $action .= "-";
                }

                if ($request->product_collect_status == '1' && $request->product_approve == '3' && $request->closed_request == '0') {
                    $action .= ' <a href="JavaScript:void(0);" data-toggle="tooltip" rel="tooltip" title="Approve Reshipment" data-id="' . $request->id . '" class="fs-22px" onclick="ApproveReshipProduct(event, ' . $request->order_id . ')"><em class="icon ni ni-check-circle"></em></a>';
                } else if ($request->request_status != '0') {
                    $action .= "-";
                }

                $request->action = '<div class="m-auto d-flex">' . $action . '</div>';

                $result[] = $request;
            }

            // Generate response
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Product list fetched successfully.',
                'toast' => true
            ], [
                'data' => $result,
                'total_record_count' => $requests->count(),
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

    public function getProduct(Request $request)
    {
        try {
            // Get the user's IP address
            // For testing, using a hard-coded IP address
            $user_ip = $request->ip();

            // Initialize variables for location data
            $country = '';
            $latitude = '';
            $longitude = '';

            // Fetch location data from the API
            $api_url = "http://ip-api.com/json/{$user_ip}";
            $locationData = @file_get_contents($api_url);

            if ($locationData) {
                $locationData = json_decode($locationData, true);

                // Extract data from the API response
                $country = $locationData['country'] ?? 'Unknown';
                $latitude = $locationData['lat'] ?? '0';
                $longitude = $locationData['lon'] ?? '0';
            }

            // Retrieve the authenticated user's ID
            $user = $request->attributes->get('user');
            $user_id = $user->id ?? null; // Ensure user ID is retrieved safely

            // If the user is authenticated, proceed with updating the subscription info
            if ($user_id) {
                // Retrieve the user's email
                $user = DB::table('users')->where('id', $user_id)->first();
                if (!$user) {
                    return generateResponse([
                        'type' => 'error',
                        'code' => 404,
                        'status' => false,
                        'message' => 'User not found',
                        'toast' => true,
                    ]);
                }

                $email_address = $user->email;

                // Find the subscription record for the user by email address
                $subscription = DB::table('marketplace_site_subscription')
                    ->where('email_address', $email_address)
                    ->first();

                if ($subscription) {
                    // Decode the product visited count JSON
                    $product_visited_count = json_decode($subscription->product_visited_count, true) ?? [];
                    $product_visited_log = $subscription->product_visited_log;

                    // Increment or set the product visited count for the given product_id
                    if (!isset($product_visited_count[$request->product_id])) {
                        $product_visited_count[$request->product_id] = 1;
                    } else {
                        $product_visited_count[$request->product_id]++;
                    }

                    // Update the visited dates log
                    $visited_dates = !empty($product_visited_log) ? explode(",", $product_visited_log) : [];
                    $visited_dates[] = now()->format('Y-m-d H:i:s');
                    $average_time = array_sum(array_map('strtotime', $visited_dates)) / count($visited_dates);
                    $product_visited_log = date('Y-m-d H:i:s', $average_time);

                    // Update the site_subscription table with the new values
                    DB::table('marketplace_site_subscription')
                        ->where('email_address', $email_address)
                        ->update([
                            'product_visited_count' => json_encode($product_visited_count),
                            'product_visited_log' => $product_visited_log,
                            'country' => $country,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                        ]);

                    return generateResponse([
                        'type' => 'success',
                        'code' => 200,
                        'status' => true,
                        'message' => 'Product visit information updated successfully.',
                        'toast' => true,
                    ]);
                } else {
                    return generateResponse([
                        'type' => 'error',
                        'code' => 404,
                        'status' => false,
                        'message' => 'Subscription not found.',
                        'toast' => true,
                    ]);
                }
            } else {
                return generateResponse([
                    'type' => 'error',
                    'code' => 401,
                    'status' => false,
                    'message' => 'User not authenticated.',
                    'toast' => true,
                ]);
            }
        } catch (\Exception $e) {
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => $e->getMessage(),
                'toast' => true,
            ]);
        }
    }


    public function addCoupon(Request $request)
    {
        $coupon = Coupon::add($request->all());
        return response()->json(['message' => 'Coupon created successfully', 'coupon' => $coupon]);
    }

    public function listCoupons()
    {
        $coupons = Coupon::list()->get();
        return response()->json($coupons);
    }

    public function applyCoupon(Request $request)
    {
        $couponApplied = Coupon::apply($request->all());
        return response()->json($couponApplied);
    }
    public function checkValidity(Request $request)
    {
        // Retrieve parameters from the request body
        $code = $request->input('code');
        $amount = $request->input('amount');
        $user_id = $request->input('user_id');
        $device_name = $request->input('device_name', $request->header('User-Agent')); // Default to User-Agent if not provided
        $vendor_id = (int) $request->input('vendor_id'); // Cast vendor_id to integer

        // Ensure amount is converted to float
        $amount = (float) $amount;

        // Log the input data
        Log::info('Checking Coupon Validity:', [
            'code' => $code,
            'amount' => $amount,
            'user_id' => $user_id,
            'device_name' => $device_name,
            'vendor_id' => $vendor_id // Log vendor_id
        ]);

        try {
            // Check coupon validity
            $validity = Coupon::validity($code, $amount, $user_id, $device_name, $ipaddress = null, $vendor_id); // Pass vendor_id to validity method

            // Log the result
            Log::info('Coupon Validity Result:', ['validity' => $validity]);

            return response()->json($validity);
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Coupon Validity Error:', ['error' => $e->getMessage()]);

            // Return a JSON response with error message if there's an exception
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }



    public function updateCoupon(Request $request, $couponId)
{
    // Define validation rules
    $rules = [
        'coupon_code'       => 'required|string',
        'discount_type'     => 'required|string|in:percentage,fixed',
        'discount_amount'   => 'required|numeric',
        'start_date'        => 'required|date',
        'end_date'          => 'required|date',
        'status'            => 'required|boolean',
        'minimum_spend'     => 'nullable|numeric',
        'maximum_spend'     => 'nullable|numeric',
        'use_limit'         => 'nullable|integer',
        'same_ip_limit'     => 'nullable|integer',
        'use_limit_per_user' => 'nullable|integer',
        'use_device'        => 'nullable|string',
        'multiple_use'      => 'nullable|boolean',
        'vendor_id'         => 'nullable|integer',
        'object_type'       => 'nullable|string',
    ];

    // Create a validator instance
    $validator = Validator::make($request->all(), $rules);

    // Check if validation fails
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Get the validated data
    $validatedData = $validator->validated();

    // Add coupon ID to the data
    $validatedData['id'] = $couponId;

    try {
        // Update the coupon using the Coupon facade
        Coupon::update($validatedData, $couponId);

        return response()->json(['message' => 'Coupon updated successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}



public function deleteCoupon(Request $request, $couponId)
{
    try {
        // Ensure coupon ID is an integer
        $couponId = (int) $couponId;
        
        // Remove the coupon
        Coupon::remove($couponId);

        return response()->json(['message' => 'Coupon deleted successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}


}
