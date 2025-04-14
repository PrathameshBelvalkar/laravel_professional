<?php

namespace App\Http\Controllers\API\V1\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Marketplace\MarketplaceProductPurchaseDetail;
use App\Models\TokenTransactionLog;
use App\Models\MarketplaceUserCart;
use App\Models\MarketplaceProducts;
use App\Models\Marketplace\MarketplaceStore;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Helpers\FedExHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Marketplace\StoreMarketplaceSellerBusinessDetailRequest;
use App\Models\MarketplaceSellerBusinessDetail;
use App\Models\Country;
use DateTime;
use App\Models\Marketplace\ShipmentDetail;
use App\Models\Marketplace\ShipmentSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Firebase\JWT\JWT;
use App\Models\MarketplacePurchaseDetailLog;
use Illuminate\Support\Carbon;
use App\Models\MarketplaceOrderReturnReplaceRequest;
use App\Mail\ShippingStartMail;
use Illuminate\Support\Facades\Mail;

class SellerBuyerController extends Controller
{
  protected $account_number;
  protected $fedExHelper;

  public function __construct()
  {
    // Initialize properties
    $this->account_number = config('shipment.fedex.account_number'); // Load account number from configuration
    $this->fedExHelper = new FedExHelper(); // Initialize FedExHelper
  }
  public function getSellersList(Request $request)
  {
    DB::beginTransaction(); // Start the transaction

    try {
      $search_keyword = $request->input('search_keyword');
      $start = $request->input('start', 0);
      $limit = $request->input('limit', 10);
      $order_column = $request->input('sort_by_column', 'users.id');
      $order_direction = $request->input('sort_by_val', 'DESC');

      $subQuery = DB::table('marketplace_stores')
        ->select('user_id', DB::raw('MIN(name) as store_name'))
        ->groupBy('user_id');

      $query = User::select([
        'users.id as seller_id',
        'users.first_name',
        'users.last_name',
        'users.email',
        'users.username',
        'user_profiles.profile_image_path as user_image',
        'marketplace_stores.store_name'
      ])
        ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id') // Join user_profiles
        ->joinSub($subQuery, 'marketplace_stores', function ($join) {
          $join->on('marketplace_stores.user_id', '=', 'users.id');
        });

      if (!empty($search_keyword)) {
        $query->where(function ($q) use ($search_keyword) {
          $q->where('users.first_name', 'LIKE', "%{$search_keyword}%")
            ->orWhere('users.email', 'LIKE', "%{$search_keyword}%")
            ->orWhere('users.username', 'LIKE', "%{$search_keyword}%")
            ->orWhere('marketplace_stores.store_name', 'LIKE', "%{$search_keyword}%");
        });
      }

      $query->orderBy($order_column, $order_direction)
        ->offset($start)
        ->limit($limit);

      $sellers = $query->get();

      $result = [];
      $i = $start + 1;

      foreach ($sellers as $seller) {
        $sellerData = [];

        if (!empty($seller->first_name)) {
          $sellerData['first_name'] = $seller->first_name . ' ' . $seller->last_name;
        } else {
          $sellerData['first_name'] = $seller->username;
        }
        $sellerData['id'] = $i++;

        $sellerData['contact'] = $seller->mobile ?? '';
        $sellerData['email'] = $seller->email ?? '';
        $sellerData['address'] = $seller->home_address ?? '';
        $sellerData['store_name'] = $this->displayString(25, $seller->store_name);

        // Add URL to store_image
        $sellerData['store_image'] = !empty($seller->user_image) ? url($seller->user_image) : null;

        $result[] = $sellerData;
      }

      DB::commit(); // Commit the transaction

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Sellers list retrieved successfully.',
        'toast' => true
      ], ['sellers' => $result]);
    } catch (\Exception $e) {
      DB::rollBack(); // Rollback the transaction in case of an error

      // Log the error for debugging purposes
      Log::error('Error retrieving sellers list: ' . $e->getMessage());

      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'An error occurred while retrieving the sellers list.',
        'toast' => true
      ], [], 500);
    }
  }

  public function getBuyersList(Request $request)
  {
    DB::beginTransaction(); // Start the transaction

    try {
      $search_keyword = $request->input('search_keyword');
      $start = $request->input('start', 0);
      $limit = $request->input('limit', 10);
      $order_column = $request->input('sort_by_column', 'users.id');
      $order_direction = $request->input('sort_by_val', 'DESC');

      // Subquery to get the minimum product name for each user
      $subQuery = DB::table('marketplace_product_purchase_details')
        ->select('user_id', DB::raw('MIN(product_name) as product_name'))
        ->groupBy('user_id');

      // Main query to get user and product details
      $query = User::select([
        'users.id as buyer_id',
        'users.first_name',
        'users.last_name',
        'users.email',
        'users.username',
        'purchase_details.product_name'
      ])
        ->joinSub($subQuery, 'purchase_details', function ($join) {
          $join->on('purchase_details.user_id', '=', 'users.id');
        });

      // If a search keyword is provided, filter the results
      if (!empty($search_keyword)) {
        $query->where(function ($q) use ($search_keyword) {
          $q->where('users.first_name', 'LIKE', "%{$search_keyword}%")
            ->orWhere('users.email', 'LIKE', "%{$search_keyword}%")
            ->orWhere('users.username', 'LIKE', "%{$search_keyword}%")
            ->orWhere('purchase_details.product_name', 'LIKE', "%{$search_keyword}%");
        });
      }

      // Apply sorting, pagination
      $query->orderBy($order_column, $order_direction)
        ->offset($start)
        ->limit($limit);

      // Get results
      $buyers = $query->get();

      // Prepare results for response
      $result = [];
      $i = $start + 1;

      foreach ($buyers as $buyer) {
        $buyerData = [];

        if (!empty($buyer->first_name)) {
          $buyerData['first_name'] = $buyer->first_name . ' ' . $buyer->last_name;
        } else {
          $buyerData['first_name'] = $buyer->username;
        }
        $buyerData['id'] = $i++;

        // Assuming `product_name` is the relevant information to display
        $buyerData['product_name'] = $this->displayString(25, $buyer->product_name);

        // Add any other required fields or defaults
        $buyerData['email'] = $buyer->email ?? '';
        $result[] = $buyerData;
      }

      DB::commit(); // Commit the transaction

      // Return success response
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Buyers list retrieved successfully.',
        'toast' => true
      ], ['buyers' => $result]);
    } catch (\Exception $e) {
      DB::rollBack(); // Rollback the transaction in case of an error

      // Log the error for debugging purposes
      Log::error('Error retrieving buyers list: ' . $e->getMessage());

      // Return error response
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'An error occurred while retrieving the buyers list.',
        'toast' => true
      ], [], 500);
    }
  }

  function displayString($length, $string)
  {
    if (strlen($string) > $length) {
      return substr($string, 0, $length) . '...';
    }

    return $string;
  }

  public function processOrderTransaction(Request $request)
  {
    // Step 1: Retrieve Order's Total Amount and Address ID
    $order_total_amount = $request->input('order_total_amount'); // USD
    $address_id = $request->input('address_id'); // Add address_id from request
    $user = $request->attributes->get('user');
    $user_id = $user->id;
    $influencer_id = null;
    if ($user->is_influencer == 1) {
      $influencer_id = $user_id;
    }
    $product_id = $request->input('product_id');
    $cart_id = $request->input('cart_id');

    // Determine if the transaction is for a single product or a cart
    if ($product_id) {
      // Transaction for a single product
      $product_quantity = $request->input('product_quantity', 1);
      $product = MarketplaceProducts::find($product_id);

      if (!$product) {
        Log::error('Product not found. Product ID: ' . $product_id . ', User ID: ' . $user_id);
        return response()->json([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Product not found',
          'toast' => true
        ]);
      }

      $products = [
        [
          'product_id' => $product->id,
          'quantity' => $product_quantity,
          'product_name' => $product->product_name,
          'price' => $product->price,
          'discount_percentage' => $product->discount_percentage,
        ]
      ];
    } elseif ($cart_id) {
      // Transaction for a cart
      $cart = MarketplaceUserCart::where('id', $cart_id)
        ->where('user_id', $user_id)
        ->first();

      if (!$cart) {
        Log::error('Cart not found. Cart ID: ' . $cart_id . ', User ID: ' . $user_id);
        return response()->json([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Cart not found',
          'toast' => true
        ]);
      }

      // Decode the products JSON from the cart
      $products = json_decode($cart->products, true);
    } else {
      // Neither product_id nor cart_id is provided
      return response()->json([
        'type' => 'error',
        'code' => 400,
        'status' => false,
        'message' => 'Product ID or Cart ID is required',
        'toast' => true
      ]);
    }

    // Generate a unique Order ID
    $order_id = $this->generateUniqueOrderId();

    // Retrieve Address Details
    $address = DB::table('addresses')->where('id', $address_id)->first();

    if (!$address) {
      Log::error('Address not found. Address ID: ' . $address_id . ', User ID: ' . $user_id);
      return response()->json([
        'type' => 'error',
        'code' => 404,
        'status' => false,
        'message' => 'Address not found',
        'toast' => true
      ]);
    }

    // Step 2: Token Conversion
    $token_value = getTokenMetricsValues(); // Retrieves the value of one token in USD
    $no_of_tokens = $order_total_amount / $token_value;

    // Step 3: Calculate Auger Fee
    $auger_fee_percentage = config('app.auger_fee'); // Fetch the Auger fee percentage from config
    $auger_tokens = $no_of_tokens * ($auger_fee_percentage / 100);
    $total_tokens = $no_of_tokens + $auger_tokens;

    // Step 4: Token Balance Validation
    if (!balanceValidations($user_id, $total_tokens)) {
      return response()->json([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Insufficient Tokens',
        'available_tokens' => $token_value,
        'required_tokens' => $no_of_tokens,
        'auger_fee' => $auger_tokens,
        'total_tokens' => $total_tokens,
        'toast' => true
      ]);
    }

    $transaction_id = null;
    // Step 5: Create Token Transactions
    try {
      DB::transaction(function () use ($user, $no_of_tokens, $auger_tokens, $order_id, $products, $order_total_amount, $user_id, $token_value, $address, &$transaction_id, $influencer_id) {
        $sender_user = $user;
        $receiver_user = User::where('role_id', 2)->orderBy('id', 'asc')->first();
        $perticulars = "Order Payment for Order ID: {$order_id}";

        // Main Transaction
        $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();
        $transaction_id = makeTransaction($sender_user, $receiver_user, $no_of_tokens, $perticulars, '4', '4', $lastTokenTransactionLog);

        if (!$transaction_id) {
          throw new \Exception('Failed to create main transaction');
        }

        // Auger Fee Transaction
        $perticulars_auger = "Auger Fee: " . $perticulars;
        $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();
        makeTransaction($sender_user, $receiver_user, $auger_tokens, $perticulars_auger, '5', '4', $lastTokenTransactionLog, $transaction_id);

        Log::info('Influencer ID:', ['influencer_id' => $influencer_id]);

        // Step 6: Update MarketplaceProductPurchaseDetail based on products
        foreach ($products as $product) {
          // Retrieve product details including store_id, thumbnail, and product_images
          $product_details = MarketplaceProducts::where('id', $product['product_id'])->first();

          if (!$product_details) {
            continue; // Skip if product details are not found
          }

          $store_id = $product_details->store_id; // Retrieve store_id from product details
          $thumbnail = $product_details->thumbnail;
          $product_images = $product_details->product_images;

          MarketplaceProductPurchaseDetail::updateOrCreate(
            ['product_id' => $product['product_id']],
            [
              'order_id' => $order_id,
              'user_id' => $user_id,
              'influencer_id' => $influencer_id,
              'store_id' => $store_id,  // Update with the correct store_id
              'type' => '5',
              'product_name' => $product_details->product_name,
              'product_type' => 'physical',
              'model_no' => $product_details->model_no_item_no,
              'quantity' => $product['quantity'],
              'price' => $product_details->price,
              'discount_percent' => $product_details->discount_percentage,
              'total_amount_with_discount' => $order_total_amount,
              'coupon_code' => 'SUMMER2024',
              'payment_id' => $transaction_id,
              'auger_fee_payment_id' => $lastTokenTransactionLog->id,
              'order_status' => '0',
              'order_otp' => null,
              'cancel_order_otp' => null,
              'payment_status' => '1',
              'admin_payment_id' => 'Admin123',
              'payment_type' => '1',
              'shipping_address' => $address->address_line_1,
              'shipping_city' => $address->city,
              'shipping_postal_code' => $address->zipcode,
              'shipping_state' => $address->state,
              'shipping_country' => $address->country,
              'shipping_phone_number' => $address->phone_number,
              'shipping_email_id' => $sender_user->email,
              'delivery_charge' => '5',
              'delivery_type' => '0',
              'created_date_time' => now(),
              'token_value' => $token_value,
              'shipping_service' => 'DHL',
              'product_thumb_path' => $thumbnail,
              'product_image_path' => $product_images,
            ]
          );
        }
      });
    } catch (\Exception $e) {
      Log::error('Transaction failed: ' . $e->getMessage());
      return response()->json([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Transaction failed',
        'toast' => true
      ]);
    }
    if ($cart_id) {
      MarketplaceUserCart::where('id', $cart_id)->where('user_id', $user_id)->delete();
    }
    // Step 7: Send Transaction Notifications
    $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();
    sendTransactionMail($lastTokenTransactionLog);

    // Return Success Response
    return response()->json([
      'type' => 'success',
      'code' => 200,
      'status' => true,
      'message' => 'Transaction processed successfully.',
      'order_id' => $order_id,
      'total_tokens' => $total_tokens,
      'toast' => true
    ]);
  }


  public function generateUniqueOrderId()
  {
    do {
      $order_id = Str::uuid(); // Generate a unique UUID
    } while (MarketplaceProductPurchaseDetail::where('order_id', $order_id)->exists());

    return $order_id;
  }

  public function orderDetails(Request $request, $order_id, $print = "")
  {
    // Get the authenticated user from the request attributes
    $user = $request->attributes->get('user');
    $user_id = $user->id;

    // Check if the order_id is provided
    if (!$order_id) {
      return generateResponse([
        'type' => 'error',
        'code' => 400,
        'status' => false,
        'message' => 'Order ID is required',
        'toast' => true
      ], []);
    }

    // Retrieve order details for the authenticated user
    $orderDetails = MarketplaceProductPurchaseDetail::where([
      ['user_id', '=', $user_id],
      ['order_id', '=', $order_id],
      // ['order_status', '!=', '2'],
      ['type', '=', '5']
    ])->get();

    // Check if order details exist
    if ($orderDetails->isEmpty()) {
      return generateResponse([
        'type' => 'error',
        'code' => 404,
        'status' => false,
        'message' => 'Order not found',
        'toast' => true
      ], []);
    }

    // Extract product IDs and store IDs
    $productIds = $orderDetails->pluck('product_id')->unique()->toArray();
    $storeIds = $orderDetails->pluck('store_id')->unique()->toArray();

    // Retrieve all products and stores
    $products = MarketplaceProducts::whereIn('id', $productIds)->get()->keyBy('id');
    $stores = MarketplaceStore::whereIn('id', $storeIds)->get()->keyBy('id');

    $productsData = [];
    foreach ($orderDetails as $orderDetail) {
      $product = $products->get($orderDetail->product_id); // Use 'get' for keyed collections
      $store = $stores->get($orderDetail->store_id);

      if ($product) {
        // Decode JSON to array if product_images is not null, otherwise default to empty array
        $productImages = $product->product_images ? json_decode($product->product_images, true) : [];

        $productsData[] = [
          'product_id' => $product->id,
          'product_name' => $product->product_name,
          'brand_name' => $product->brand_name,
          'product_short_name' => $product->short_name,
          'quantity' => $orderDetail->quantity,
          'price' => $orderDetail->price,
          'discount_percentage' => $orderDetail->discount_percentage,
          'total_amount_with_discount' => number_format(($orderDetail->price * $orderDetail->quantity) - $orderDetail->discount_amount, 2),
          'product_images' => is_array($productImages) ? array_map(fn($img) => url('uploads/' . $img), $productImages) : [],
          'thumbnail' => url('uploads/' . $product->thumbnail),
          'brand_name' => $product->brand_name,
          'delivery_type' => $orderDetail->delivery_type,
          'weight' => [
            'units' => json_decode($product->weight)->weight->units,
            'value' => json_decode($product->weight)->weight->value,
          ],
          'product_video' => $product->product_video ? url('uploads/' . $product->product_video) : null,
        ];
      }
    }

    // Remove duplicate products if any
    $productsData = array_values(array_unique($productsData, SORT_REGULAR));

    // Calculate totals and token values
    $totalPrice = (float) $orderDetails->sum(fn($detail) => $detail->price * $detail->quantity);
    $shippingPrice = (float) $orderDetails->sum('delivery_charge');
    $liveTokenRate = getTokenMetricsValues();
    $tokenAmount = (float) ($totalPrice / $liveTokenRate);
    // $totalTokens = round(($totalPrice + $shippingPrice) / $liveTokenRate, 2);
    $totalTokens = round(($totalPrice) / $liveTokenRate, 2);
    $augerTokens = (float) number_format(($totalPrice * 0.029) / $liveTokenRate, 2);

    // Calculate total amount including Auger fee
    // $totalAmount = $totalPrice + $shippingPrice + $augerTokens;
    $totalAmount = $totalPrice + $augerTokens;
    // Extract shipping address details
    $shippingAddress = $orderDetails->first();
    $shippingDetails = [
      'address' => $shippingAddress->shipping_address,
      'city' => $shippingAddress->shipping_city,
      'postal_code' => $shippingAddress->shipping_postal_code,
      'state' => $shippingAddress->shipping_state,
      'country' => $shippingAddress->shipping_country,
      'phone_number' => $shippingAddress->shipping_phone_number,
      'email_id' => $shippingAddress->shipping_email_id ?? '',
      'shipping_service' => $shippingAddress->shipping_service ?? '',
      'delivery_charge' => $shippingAddress->delivery_charge,
    ];

    // Order and payment details
    $orderData = [
      'order_id' => $order_id,
      'user_id' => $user_id,
      'store_id' => $orderDetails->first()->store_id,
      'order_date' => date('d F Y', strtotime($orderDetail->created_date_time)),
      'shipping_details' => $shippingDetails,
      'order_status' => $orderDetails->first()->order_status,
      'payment_status' => $orderDetails->first()->payment_status,
      'payment_details' => [
        'payment_id' => $orderDetails->first()->payment_id,
        'admin_payment_id' => $orderDetails->first()->admin_payment_id,
        'payment_type' => $orderDetails->first()->payment_type,
      ],
      'total' => $totalPrice,
      'coupon_code' => $orderDetails->first()->coupon_code ?? '',
      'live_token_rate' => $liveTokenRate,
      'token_amount' => $tokenAmount,
      'total_tokens' => $totalTokens,
      'auger_tokens' => $augerTokens,
      'total_amount' => number_format($totalAmount, 2), // Total amount including Auger fee
    ];

    return generateResponse([
      'type' => 'success',
      'status' => true,
      'code' => 200,
      'message' => 'Order details retrieved successfully.',
      'toast' => true
    ], [
      'order_details' => $orderData,
      'products' => $productsData,
    ]);
  }

  public function AddressValidation(Request $request)
  {
    // Prepare the payload for address validation
    $postData = [
      'addressesToValidate' => [
        [
          'address' => [
            'streetLines' => [
              $request->input('address'),
              $request->input('city') . ", " . $request->input('state') . ", " . $request->input('postal_code')
            ],
            'city' => $request->input('city'),
            'stateOrProvinceCode' => $request->input('state'),
            'postalCode' => $request->input('postal_code'),
            'countryCode' => $request->input('country_iso'),
          ]
        ]
      ]
    ];

    try {
      $result = $this->fedExHelper->validateAddress($postData);
      $httpStatusCode = $result['httpStatusCode'];

      if ($httpStatusCode === 200) {
        return response()->json([
          'status' => true,
          'message' => 'Address validated successfully',
          'data' => $result['response'],
        ]);
      } else {
        return response()->json([
          'status' => false,
          'message' => $result['response']['errors'][0]['message'] ?? 'Unknown error',
          'data' => $result['response']['errors'] ?? [],
        ]);
      }
    } catch (\Exception $e) {
      return response()->json([
        'status' => false,
        'message' => $e->getMessage(),
      ]);
    }
  }


  public function ShippingCharges(Request $request)
  {
    // Prepare the payload for shipping charges
    $postData = [
      'accountNumber' => [
        'value' => $this->account_number
      ],
      'requestedShipment' => [
        'shipper' => [
          'address' => [
            'postalCode' => '65247',
            'countryCode' => 'US'
          ]
        ],
        'recipient' => [
          'address' => [
            'postalCode' => $request->input('recep_postal_code'),
            'countryCode' => $request->input('recep_country_iso')
          ]
        ],
        'serviceType' => $request->input('service_type'),
        'packagingType' => $request->input('package_type'),
        'pickupType' => 'DROPOFF_AT_FEDEX_LOCATION',
        'rateRequestType' => ['PREFERRED'],
        'preferredCurrency' => 'USD',
        'requestedPackageLineItems' => [
          [
            'weight' => [
              'units' => 'LB',
              'value' => 10
            ]
          ]
        ],
        'carrierCodes' => ['FDXE']
      ]
    ];


    try {
      $result = $this->fedExHelper->getShippingRates($postData);
      $httpStatusCode = $result['httpStatusCode'];

      if ($httpStatusCode === 200) {
        $response = $result['response'];
        if (isset($response['output']['rateReplyDetails'][0]['ratedShipmentDetails'][0]['totalNetCharge'])) {
          $charges = [];
          foreach ($response['output']['rateReplyDetails'] as $value) {
            $charges[] = [
              'serviceName' => $value['serviceName'],
              'serviceType' => $value['serviceType'],
              'totalNetCharge' => $value['ratedShipmentDetails'][0]['totalNetCharge']
            ];
          }
          return response()->json([
            'status' => true,
            'data' => $response,
            'chargesArray' => $charges
          ]);
        } else {
          return response()->json([
            'status' => false,
            'message' => 'No result found',
            'data' => $response
          ]);
        }
      } else {
        return response()->json([
          'status' => false,
          'message' => $result['response']['errors'][0]['message'] ?? 'Unknown error',
          'data' => $result['response']['errors'] ?? []
        ]);
      }
    } catch (\Exception $e) {
      return response()->json([
        'status' => false,
        'message' => $e->getMessage()
      ]);
    }
  }


  public function ProductShippingCharges(Request $request)
  {
    $productId = $request->input('product_id');
    $recipientPostalCode = $request->input('recep_postal_code'); // Get recipient postal code from request
    $recipientCountryIso = $request->input('recep_country_iso'); // Get recipient country ISO from request

    // Fetch product details from the marketplace_products table
    $product = DB::table('marketplace_products')
      ->where('id', $productId)
      ->select('id', 'user_id', 'weight', 'pickup_type')
      ->first();

    if (!$product) {
      return response()->json([
        'status' => false,
        'message' => 'Product not found'
      ]);
    }

    // Fetch seller details from the marketplace_seller_business_details table
    $seller = DB::table('marketplace_seller_business_details')
      ->where('user_id', $product->user_id)
      ->select('user_id', 'postal_code', 'country_code')
      ->first();

    if (!$seller) {
      return response()->json([
        'status' => false,
        'message' => 'Seller not found'
      ]);
    }

    // Prepare data for service package options request
    $postData = [
      'recep_postal_code' => $recipientPostalCode,
      'recep_country_iso' => $recipientCountryIso,
      'product_id' => $productId,
    ];

    try {
      // Fetch service package options
      $servicePackageOptions = $this->handleServicePackageOptions($postData);

      if (!$servicePackageOptions['status'] || empty($servicePackageOptions['data'])) {
        // Return early if service package options fetch fails
        return response()->json([
          'status' => false,
          'message' => 'Failed to retrieve service package options',
          'data' => $servicePackageOptions
        ]);
      }

      // Extract service type and packaging type from the response
      $servicesArr = $servicePackageOptions['data'];
      $serviceType = $servicesArr[0]['serviceType'] ?? 'FEDEX_GROUND'; // Default service type
      $packagingType = $servicesArr[0]['packageType'] ?? 'YOUR_PACKAGING'; // Default packaging type

      // Decode weight from JSON
      $weight = json_decode($product->weight, true);

      // Prepare data for FedEx API
      $data = [
        'accountNumber' => [
          'value' => $this->account_number
        ],
        'requestedShipment' => [
          'shipper' => [
            'address' => [
              'postalCode' => $seller->postal_code,
              'countryCode' => $seller->country_code
            ]
          ],
          'recipient' => [
            'address' => [
              'postalCode' => $recipientPostalCode,
              'countryCode' => $recipientCountryIso
            ]
          ],
          'pickupType' => $product->pickup_type,
          'serviceType' => 'FEDEX_GROUND', // Default service type
          'packagingType' => 'YOUR_PACKAGING',
          'rateRequestType' => ['PREFERRED'],
          'preferredCurrency' => 'USD',
          'requestedPackageLineItems' => [
            [
              'weight' => [
                'units' => $weight['weight']['units'], // Ensure units are set correctly
                'value' => $weight['weight']['value']  // Ensure value is numeric
              ]
            ]
          ],
          'carrierCodes' => ['FDXE']
        ]
      ];

      // Call the FedEx helper method
      $result = $this->fedExHelper->getProductShippingRate($data);

      $httpStatusCode = $result['httpStatusCode'];
      $response = $result['response'];

      // Process the FedEx API response
      if ($httpStatusCode === 200) {
        if (isset($response['output']['rateReplyDetails'][0]['ratedShipmentDetails'][0]['totalNetCharge'])) {
          $charges = [];
          foreach ($response['output']['rateReplyDetails'] as $value) {
            $charges[] = [
              'serviceName' => $value['serviceName'],
              'serviceType' => $value['serviceType'],
              'totalNetCharge' => $value['ratedShipmentDetails'][0]['totalNetCharge']
            ];
          }
          return response()->json([
            'status' => true,
            'servicePackageOptions' => $servicePackageOptions,
            'chargesArray' => $charges
          ]);
        } else {
          return response()->json([
            'status' => false,
            'message' => 'No result found in FedEx response',
            'servicePackageOptions' => $servicePackageOptions,
            'data' => $response
          ]);
        }
      } else {
        return response()->json([
          'status' => false,
          'message' => $response['errors'][0]['message'] ?? 'Unknown error',
          'servicePackageOptions' => $servicePackageOptions,
          'data' => $response['errors'] ?? []
        ]);
      }
    } catch (\Exception $e) {
      return response()->json([
        'status' => false,
        'message' => $e->getMessage(),
        'servicePackageOptions' => $servicePackageOptions ?? []
      ]);
    }
  }


  public function getBuyerOrderDetail($orderId)
  {
    $result = MarketplaceProductPurchaseDetail::select(
      'marketplace_product_purchase_details.id',
      'users.id as buyer_id',
      'owners.id as owner_id',
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
      'marketplace_product_purchase_details.quantity',
      'marketplace_product_purchase_details.order_status',
      'marketplace_product_purchase_details.payment_status',
      'marketplace_product_purchase_details.delivery_type',
      'marketplace_product_purchase_details.shipping_address',
      'marketplace_product_purchase_details.shipping_city',
      'marketplace_product_purchase_details.shipping_state',
      'marketplace_product_purchase_details.shipping_country',
      'marketplace_product_purchase_details.shipping_postal_code',
      'marketplace_product_purchase_details.shipping_phone_number',
      'marketplace_product_purchase_details.shipping_email_id',
      'marketplace_product_purchase_details.created_date_time',
      'marketplace_products.description',
      'marketplace_products.product_image_path',
      'marketplace_products.product_thumb_path',
      'marketplace_stores.name as store_name',
      'marketplace_product_purchase_details.refund_expire_date',
      'marketplace_product_purchase_details.order_delivery_date',
      'marketplace_product_purchase_details.delivery_person_id',
      'marketplace_product_purchase_details.payment_type',
      'marketplace_product_purchase_details.delivery_charge',
      'marketplace_product_purchase_details.product_id',
      'marketplace_product_purchase_details.shipping_service'
    )
      ->join('users', 'marketplace_product_purchase_details.user_id', '=', 'users.id')
      ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
      ->join('marketplace_products', 'marketplace_product_purchase_details.product_id', '=', 'marketplace_products.id')
      ->join('marketplace_stores', 'marketplace_product_purchase_details.store_id', '=', 'marketplace_stores.id')
      ->where('marketplace_product_purchase_details.id', $orderId)
      ->first(); // Using first() to get a single record, assuming $orderId is unique.

    if (!$result) {
      return response()->json(['message' => 'Order not found'], 404);
    }

    return $result;
  }


  public function createShipment(Request $request)
  {
    $orderDetail = MarketplaceProductPurchaseDetail::where('order_id', $request->order_id)->firstOrFail();

    // Fetch product details
    $product = MarketplaceProducts::findOrFail($orderDetail->product_id);

    // Fetch seller business details
    $sellerDetails = MarketplaceSellerBusinessDetail::where('user_id', $product->user_id)->firstOrFail();

    // Fetch country details
    $country = Country::where('name', $orderDetail->shipping_country)->firstOrFail();

    $data = [
      'labelResponseOptions' => 'URL_ONLY',
      'requestedShipment' => [
        'shipper' => [
          'contact' => [
            'personName' => $sellerDetails->person_name,
            'phoneNumber' => $sellerDetails->phone_number,
            'companyName' => $sellerDetails->company_name,
          ],
          'address' => [
            'streetLines' => [$sellerDetails->street_address],
            'city' => $sellerDetails->city,
            'stateOrProvinceCode' => $sellerDetails->state_code,
            'postalCode' => $sellerDetails->postal_code,
            'countryCode' => $sellerDetails->country_code,
          ],
        ],
        'recipients' => [
          [
            'contact' => [
              'personName' => $orderDetail->shipping_email_id, // Assuming this is the recipient's name
              'phoneNumber' => $orderDetail->shipping_phone_number,
              'companyName' => '', // Default to empty if not provided
            ],
            'address' => [
              'streetLines' => [str_replace(
                [$orderDetail->shipping_city, $orderDetail->shipping_state, $orderDetail->shipping_postal_code, $orderDetail->shipping_country, ","],
                "",
                $orderDetail->shipping_address
              )],
              'city' => $orderDetail->shipping_city,
              'stateOrProvinceCode' => $orderDetail->shipping_state,
              'postalCode' => $orderDetail->shipping_postal_code,
              'countryCode' => $country->shortname,
            ],
          ]
        ],
        'shipDatestamp' => now()->format('Y-m-d'),
        'pickupType' => 'DROPOFF_AT_FEDEX_LOCATION',
        'serviceType' => 'FEDEX_GROUND',
        'packagingType' => 'YOUR_PACKAGING',
        'shippingChargesPayment' => [
          'paymentType' => 'SENDER'
        ],
        'labelSpecification' => [
          'imageType' => 'PDF',
          'labelStockType' => 'PAPER_4X6'
        ],
        'requestedPackageLineItems' => [
          [
            'weight' => [
              'units' => 'LB',
              'value' => $product->weight['value'] ?? 10
            ]
          ]
        ]
      ],
      'accountNumber' => [
        'value' => $this->account_number
      ]
    ];

    $response = $this->fedExHelper->getcreateShipment($data);

    if ($response->failed()) {
      return response()->json([
        'status' => false,
        'message' => $response->json('message') ?? 'Unknown error',
        'data' => $response->json('data') ?? []
      ], 500);
    }

    $responseData = $response->json();

    if (isset($responseData['output']['transactionShipments'])) {
      $shipment = $responseData['output']['transactionShipments'][0];
      $labelUrl = $shipment['pieceResponses'][0]['packageDocuments'][0]['url'] ?? null;

      if ($labelUrl) {
        $pdfResponse = Http::get($labelUrl);

        if ($pdfResponse->ok()) {
          $filename = 'shipment_label_' . $shipment['masterTrackingNumber'] . '.pdf';
          $filePath = 'uploads/' . $filename;
          Storage::disk('public')->put($filePath, $pdfResponse->body());

          // Store shipment details in the database
          $insData = [
            'order_id' => $request->order_id,
            'status' => '1',
            'create_shipment' => json_encode($responseData['output']),
          ];

          $existingRecord = DB::table('shipment_details')->where('order_id', $request->order_id)->first();
          if ($existingRecord) {
            DB::table('shipment_details')->where('order_id', $request->order_id)->update($insData);
          } else {
            DB::table('shipment_details')->insert($insData);
          }

          return response()->json([
            'status' => true,
            'data' => $responseData['output'],
            'masterTrackingNumber' => $shipment['masterTrackingNumber'] ?? null,
            'url' => asset('storage/' . $filePath)
          ]);
        } else {
          return response()->json([
            'status' => false,
            'message' => 'Failed to download label PDF',
            'data' => $responseData
          ]);
        }
      } else {
        return response()->json([
          'status' => false,
          'message' => 'No label URL found',
          'data' => $responseData
        ]);
      }
    } else {
      return response()->json([
        'status' => false,
        'message' => 'No result found',
        'data' => $responseData
      ]);
    }
  }


  public function getShipmentLabel($pdfUrl)
  {
    // PDF URL provided by FedEx
    // $pdfUrl = "https://wwwtest.fedex.com/document/v1/cache/retrieve/SH,63aa938e2fce0da8794797206616_SHIPPING_P?isLabel=true&autoPrint=false";

    // Fetch the PDF content using Laravel's HTTP client
    $response = Http::get($pdfUrl);

    if ($response->successful()) {
      // Return the PDF content directly
      return response($response->body(), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="shipment_label.pdf"');
    } else {
      // Handle the error if the request failed
      return response()->json(['error' => 'Failed to retrieve the PDF'], 500);
    }
  }
  public function getBuyerOrderDetail1($order_id)
  {
    try {
      $orderDetail = MarketplaceProductPurchaseDetail::select(
        'marketplace_product_purchase_details.id',
        'users.id as buyer_id',
        'products.user_id as owner_id',
        'users.first_name',
        'users.last_name',
        'users.email as email_address',
        'users.username',
        'user_profiles.profile_image_path as user_image',
        'user_profiles.address_1 as home_address',
        'user_profiles.phone_number as mobile',
        'marketplace_product_purchase_details.order_id',
        'marketplace_product_purchase_details.price as price',
        'marketplace_product_purchase_details.product_name as product_name',
        'marketplace_product_purchase_details.quantity',
        'marketplace_product_purchase_details.order_status',
        'marketplace_product_purchase_details.payment_status',
        'marketplace_product_purchase_details.delivery_type',
        'marketplace_product_purchase_details.shipping_address',
        'marketplace_product_purchase_details.shipping_city',
        'marketplace_product_purchase_details.shipping_state',
        'marketplace_product_purchase_details.shipping_country',
        'marketplace_product_purchase_details.shipping_postal_code',
        'marketplace_product_purchase_details.shipping_phone_number',
        'marketplace_product_purchase_details.shipping_email_id',
        'marketplace_product_purchase_details.created_date_time as created_date_time',
        'products.description',
        'products.product_image_path',
        'products.product_thumb_path',
        'stores.name as store_name',
        'marketplace_product_purchase_details.refund_expire_date',
        'marketplace_product_purchase_details.order_delivery_date',
        'marketplace_product_purchase_details.delivery_person_id',
        'marketplace_product_purchase_details.payment_type',
        'marketplace_product_purchase_details.delivery_charge',
        'marketplace_product_purchase_details.product_id',
        'marketplace_product_purchase_details.shipping_service'
      )
        ->join('users', 'marketplace_product_purchase_details.user_id', '=', 'users.id')
        ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
        ->join('marketplace_products as products', 'products.id', '=', 'marketplace_product_purchase_details.product_id')
        ->join('marketplace_stores as stores', 'stores.id', '=', 'marketplace_product_purchase_details.store_id')
        ->where('marketplace_product_purchase_details.order_id', $order_id)
        ->first();

      return $orderDetail;
    } catch (QueryException $e) {
      Log::error('QueryException occurred in getBuyerOrderDetail1 method', [
        'error' => $e->getMessage(),
        'query' => $e->getSql(),
        'bindings' => $e->getBindings(),
      ]);
      throw $e;
    } catch (\Exception $e) {
      Log::error('Exception occurred in getBuyerOrderDetail1 method', [
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  public function ProductPickup(Request $request)
  {
    $postData = $request->all();


    // Retrieve order details using the updated getBuyerOrderDetail1 method
    $orderDetail = $this->getBuyerOrderDetail1($postData['order_id']);

    $ProductDetails = MarketplaceProducts::where('id', $orderDetail->product_id)->firstOrFail();
    $sellerDetails = MarketplaceSellerBusinessDetail::where('user_id', $ProductDetails->user_id)->firstOrFail();
    $getCountry = Country::where('name', $orderDetail->shipping_country)->firstOrFail();

    Log::info('State code found', ['stateId' => $getCountry->shortname]);

    // Convert the ready date timestamp to ISO 8601 format
    $inputDateTimeObject = new DateTime($postData["readyDateTimestamp"] ?? '2024-08-21T15:30:00Z');
    $iso8601DateTime = $inputDateTimeObject->format('Y-m-d\TH:i:s\Z');

    // Prepare the data for the FedEx API request
    $data = [
      "associatedAccountNumber" => ["value" => $this->account_number], // Example account number, replace with actual
      "originDetail" => [
        "pickupLocation" => [
          "contact" => [
            "personName" => $sellerDetails->person_name,
            "phoneNumber" => $sellerDetails->phone_number
          ],
          "address" => [
            "streetLines" => [$orderDetail->shipping_address],
            "city" => $orderDetail->shipping_city,
            "stateOrProvinceCode" => getCountrySubdivisions($orderDetail->shipping_state, $getCountry->shortname),
            "postalCode" => $orderDetail->shipping_postal_code,
            "countryCode" => $getCountry->shortname,
            // "countryCode" => 'US'
          ]
        ],
        "readyDateTimestamp" => $iso8601DateTime,
        "customerCloseTime" => ($postData["customerCloseTime"] ?? '17:00') . ":00" // Default value if not provided
      ],
      "carrierCode" => "FDXE"
    ];

    Log::info('State code found', ['stateId' => $data]);
    // Use the FedExHelper to send the request
    $response = $this->fedExHelper->getProductPickup($data);
    $res = $response->json();
    $httpStatusCode = $response->status();

    // Handle FedEx API response
    if ($httpStatusCode === 200) {
      if (isset($res['output']['pickupConfirmationCode'])) {
        // Data to be inserted/updated in shipment_details
        $ins_data = [
          'order_id' => $postData['order_id'],
          'status' => '2', // '2' for Pickup Shipment
          'product_pickup' => json_encode($res['output']), // Store the response data as JSON
          'created_at' => now(),
          'updated_at' => now()
        ];

        // Check if a record already exists in shipment_details
        $getRecord = DB::table('shipment_details')
          ->where('order_id', $postData['order_id'])
          ->first();

        if (empty($getRecord)) {
          // Insert new record into shipment_details
          $ins_id = DB::table('shipment_details')->insertGetId($ins_data);
        } else {
          // Update existing record in shipment_details
          DB::table('shipment_details')
            ->where('order_id', $postData['order_id'])
            ->update($ins_data);
          $ins_id = $postData['order_id'];
        }

        return response()->json([
          "status" => true,
          "message" => "Pickup request added",
          "data" => $res['output'],
          "pickupConfirmationCode" => $res['output']['pickupConfirmationCode']
        ]);
      } else {
        return response()->json([
          "status" => false,
          "message" => "No result found",
          "data" => $res['output']
        ]);
      }
    } else {
      return response()->json([
        "status" => false,
        "message" => $res['errors'][0]['message'] ?? 'An error occurred',
        "data" => $res['errors'] ?? []
      ]);
    }
  }


  public function TrackingByNumber(Request $request)
  {
    $shipmentDetails = ShipmentDetail::all(); // Use Eloquent model

    if ($shipmentDetails->isEmpty()) {
      return response()->json([
        "status" => false,
        "message" => "No shipment details found for tracking"
      ]);
    }

    $responseResult = $shipmentDetails->map(function ($shipment) {
      $orderId = $shipment->order_id;
      $createShipDetails = json_decode($shipment->create_shipment);

      if (!isset($createShipDetails->transactionShipments[0]->masterTrackingNumber)) {
        return [
          "status" => false,
          "message" => "Tracking number not found for order ID: $orderId"
        ];
      }

      $trackingNumber = $createShipDetails->transactionShipments[0]->masterTrackingNumber;

      $apiResponse = $this->fedExHelper->getTrackingDetails($trackingNumber);
      return $this->processTrackingData($apiResponse, $orderId);
    });

    return response()->json($responseResult);
  }


  protected function processTrackingData($apiResponse, $orderId)
  {
    // Decode and validate the API response
    if (is_string($apiResponse)) {
      $apiResponse = json_decode($apiResponse, true);
    }

    if (!is_array($apiResponse)) {
      return [
        "status" => false,
        "message" => 'Invalid API response format',
        "data" => []
      ];
    }


    // if (empty($apiResponse['output']['completeTrackResults'][0]['trackResults'])) {
    //     return [
    //         "status" => false,
    //         "message" => "No result found",
    //         "data" => ["order_id" => $orderId]
    //     ];
    // }

    $trackData = $apiResponse['output']['completeTrackResults'][0]['trackResults'][0]['scanEvents'];

    krsort($trackData);

    $trackRes = array_map(function ($track) {
      $address = $this->formatAddress($track['scanLocation']);
      return [
        "address" => $address,
        "eventDescription" => $track['eventDescription'],
        "eventstatus" => $this->getEventStatusDefinition($track['eventType']),
        "datetime" => Carbon::parse($track['date'])->format('d M Y H:i a')
      ];
    }, $trackData);

    $trackingNumber = $apiResponse['output']['completeTrackResults'][0]['trackResults'][0]['trackingNumberInfo']['trackingNumber'];
    $statusCode = $apiResponse['output']['completeTrackResults'][0]['trackResults'][0]['latestStatusDetail']['derivedCode'];

    $response = [
      "status" => true,
      "data" => [
        "tracking_number" => $trackingNumber,
        "latestStatus" => $statusCode
      ],
      "location" => $trackRes,
      "order_id" => $orderId
    ];

    // Update shipment details
    $this->updateShipmentDetails($orderId, $apiResponse['output'], $trackRes);

    // Handle specific statuses
    if ($statusCode === "PU") {
      $this->startShipping($orderId, $trackingNumber);
    } elseif ($statusCode === "DL") {
      $this->productDelivery($orderId, $trackingNumber);
    }

    return $response;
  }


  protected function getEventStatusDefinition($eventType)
  {
    // Define a mapping of event types to descriptions
    $eventDefinitions = [
      'OUT_FOR_DELIVERY' => 'Out for delivery',
      'DELIVERED' => 'Delivered',
      'IN_TRANSIT' => 'In transit',
      'PICKED_UP' => 'Picked up',
      'RETURNED' => 'Returned',
    ];

    // Return the description if it exists, otherwise return 'Unknown status'
    return $eventDefinitions[$eventType] ?? 'Unknown status';
  }

  protected function formatAddress($scanLocation)
  {
    return implode(', ', array_filter([
      $scanLocation['city'] ?? null,
      $scanLocation['stateOrProvinceCode'] ?? null,
      $scanLocation['countryName'] ?? null,
      $scanLocation['postalCode'] ?? null
    ]));
  }


  public function productDelivery($orderId)
  {
    $order = MarketplaceProductPurchaseDetail::where('order_id', $orderId)->first();
    Log::info('Tracking data processed', [
      '$order' => $orderId
    ]);

    if (!$order) {
      return response()->json([
        'status' => 'error',
        'message' => 'Order not found.',
      ], 404);
    }

    $product = MarketplaceProducts::find($order->product_id);

    if (!$product) {
      return response()->json([
        'status' => 'error',
        'message' => 'Product not found.',
      ], 404);
    }

    $refundExp = ($product->order_type == "4")
      ? Carbon::now()->subDay()->toDateString()
      : Carbon::now()->subDays($product->return_days - 1)->toDateString();

    MarketplacePurchaseDetailLog::updateOrCreate(
      ['order_id' => $orderId],
      [
        'order_status' => '3', // Assuming '3' is for 'Shipped'
        'shipped_date' => Carbon::now(),
        'refund_date' => $refundExp,
      ]
    );

    MarketplaceOrderReturnReplaceRequest::updateOrCreate(
      ['order_id' => $orderId],
      [
        'request_type' => '2', // Assuming '2' is for 'Return'
        'request_status' => '0', // Assuming '0' is for 'Pending'
      ]
    );

    return response()->json([
      'status' => 'success',
      'message' => 'FedEx product delivery details updated successfully.',
    ]);
  }

  public function startShipping($orderId, $trackingId)
  {

    if (!empty($orderId)) {
      // Update order status and log
      $orderStatus = DB::table('marketplace_product_purchase_details')
        ->where('id', $orderId)
        ->update(['order_status' => '3']);

      $orderLog = DB::table('marketplace_purchase_details_log')
        ->where('order_id', $orderId)
        ->update(['order_status' => '3', 'shipped_date' => now()]);


      if (!$orderStatus) {
        // Retrieve order and user details
        $orderDetails = $this->getOrderDetails($orderId);
        $userDetails = $this->getUserDetails($orderDetails[0]->user_id);


        $prodImg = url($orderDetails[0]->thumbnail);

        // Prepare email content
        $messageProductDetails = "<tr><td style='padding-left:10px;'>1</td><td><img src='" . $prodImg . "'></td><td><div class='title'>" . $orderDetails[0]->product_name . "</div><div class='text'>" . $orderDetails[0]->model_no_item_no . "</div></td><td>Qty " . $orderDetails[0]->quantity . "</td><td align='right' style='padding-right:10px;'>$" . $orderDetails[0]->price . "</td></tr>";
        $trackingLink = url("marketplace/orders/" . $orderId);
        $placeholders = ['[NAME]', '[ORDER_ID]', '[TRACKING_ID]', '[TRACKING_LINK]', '[PRODUCT_DETAILS]'];
        $values = [$userDetails->first_name . " " . $userDetails->last_name, $orderId, $trackingId, $trackingLink, $messageProductDetails];

        try {
          // Send email
          Mail::to($userDetails->email)->send(new ShippingStartMail($placeholders, $values));

          $response = [
            "status" => true,
            "message" => "Shipment has been dispatched"
          ];
        } catch (\Exception $e) {
          $response = [
            "status" => false,
            "message" => "Shipment dispatched, but email sending failed: " . $e->getMessage()
          ];
        }
      } else {
        $response = [
          "status" => false,
          "message" => "Please try again later"
        ];
      }

      return $response;
    }

    return [
      "status" => false,
      "message" => "Invalid order ID"
    ];
  }


  protected function getOrderDetails($orderId)
  {
    return DB::table('marketplace_product_purchase_details as purchase_details')
      ->join('marketplace_products as products', 'purchase_details.product_id', '=', 'products.id')
      ->where('purchase_details.order_id', $orderId)
      ->select(
        'purchase_details.*', // Select all columns from purchase details
        'products.thumbnail', // Select the thumbnail from the products table
        'products.product_name', // Select product_name for email or other uses
        'products.model_no_item_no', // Select model_no_item_no for email or other uses
        'products.product_image_path', // Select product_image_path if needed
        'products.price' // Select price for email or other uses
      )
      ->get();
  }


  protected function getUserDetails($userId)
  {
    return DB::table('users')
      ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
      ->where('users.id', $userId)
      ->select('users.email', 'user_profiles.first_name', 'user_profiles.last_name', 'user_profiles.profile_image_path')
      ->first();
  }

  protected function isFileExists($filePath)
  {
    $fullPath = storage_path('app/' . $filePath);
    // Check if the file exists in the 'public' disk
    if (Storage::disk('public')->exists($filePath)) {
      return Storage::disk('public')->url($filePath); // Get the public URL of the file
    } else if (file_exists($fullPath)) {
      return Storage::disk('local')->url($filePath);
    } else {
      return asset('default-image.jpg');
    }
  }

  protected function updateShipmentDetails($orderId, $output, $trackRes)
  {
    $insData = [
      'status' => '3', // '3' for Tracking
      'tracking_details' => json_encode($output),
      'location' => json_encode($trackRes),
      'updated_at' => now()
    ];

    $exists = DB::table('shipment_details')->where('order_id', $orderId)->exists();

    if ($exists) {
      DB::table('shipment_details')->where('order_id', $orderId)->update($insData);
    } else {
      $insData['order_id'] = $orderId;
      $insData['created_at'] = now();
      DB::table('shipment_details')->insert($insData);
    }
  }

  public function ServicePackageOptions(Request $request)
  {
    $responseData = $this->handleServicePackageOptions($request->all());
    return response()->json($responseData);
  }

  private function handleServicePackageOptions(array $postData)
  {
    $product = MarketplaceProducts::find($postData['product_id']);

    if (!$product) {
      return ['status' => false, 'message' => 'Product not found'];
    }

    $sellerDetails = MarketplaceSellerBusinessDetail::where('user_id', $product->user_id)->first();

    if (!$sellerDetails) {
      return ['status' => false, 'message' => 'Seller details not found'];
    }

    $post_data = [
      'requestedShipment' => [
        'shipper' => [
          'address' => [
            'postalCode' => $sellerDetails->postal_code,
            'countryCode' => $sellerDetails->country_code,
          ],
        ],
        'recipients' => [ // Ensure this matches API expectations
          [
            'address' => [
              'postalCode' => $postData['recep_postal_code'],
              'countryCode' => $postData['recep_country_iso'],
            ],
          ],
        ],
      ],
      'carrierCodes' => ['FDXE', 'FDXG'],
    ];


    $response = $this->fedExHelper->getServicePackageOptions($post_data);
    if ($response === null) {
      return ['status' => false, 'message' => 'Failed to retrieve package options'];
    }

    if (isset($response['output']['packageOptions'])) {
      $servicesArr = [];
      foreach ($response['output']['packageOptions'] as $value) {
        $servicesArr[] = [
          'serviceName' => $value['serviceType']['displayText'] ?? 'N/A',
          'serviceType' => $value['serviceType']['key'] ?? 'N/A',
          'packageName' => $value['packageType']['displayText'] ?? 'N/A',
          'packageType' => $value['packageType']['key'] ?? 'N/A',
        ];
      }
      return ['status' => true, 'data' => $servicesArr];
    } else {
      return ['status' => false, 'message' => 'No result found', 'data' => $response['output']];
    }
  }


  // public function ServicePackageOptions(Request $request)
  // {
  //     $postData = $request->all();
  //     $product = MarketplaceProducts::find($postData["product_id"]);

  //     if (!$product) {
  //         return response()->json(['status' => false, 'message' => 'Product not found'], 404);
  //     }

  //     $sellerDetails = MarketplaceSellerBusinessDetail::where('user_id', $product->user_id)->first();

  //     if (!$sellerDetails) {
  //         return response()->json(['status' => false, 'message' => 'Seller details not found'], 404);
  //     }

  //     $post_data = [
  //         'requestedShipment' => [
  //             'shipper' => [
  //                 'address' => [
  //                     'postalCode' => $sellerDetails->postal_code,
  //                     'countryCode' => $sellerDetails->country_code,
  //                 ],
  //             ],
  //             'recipients' => [
  //                 [
  //                     'address' => [
  //                         'postalCode' => $postData['recep_postal_code'],
  //                         'countryCode' => $postData['recep_country_iso'],
  //                     ],
  //                 ],
  //             ],
  //         ],
  //         'carrierCodes' => ['FDXE', 'FDXG'],
  //     ];

  //     $response = $this->fedExHelper->getServicePackageOptions($post_data);

  //     if ($response === null) {
  //         return response()->json(['status' => false, 'message' => 'Failed to retrieve package options'], 500);
  //     }

  //     if (isset($response['output']['packageOptions'])) {
  //         $servicesArr = [];
  //         foreach ($response['output']['packageOptions'] as $value) {
  //             $servicesArr[] = [
  //                 "serviceName" => $value['serviceType']['displayText'],
  //                 "serviceType" => $value['serviceType']['key'],
  //                 "packageName" => $value['packageType']['displayText'],
  //                 "packageType" => $value['packageType']['key'],
  //             ];
  //         }
  //         $responseData = ["status" => true, "data" => $servicesArr];
  //     } else {
  //         $responseData = ["status" => false, "message" => "No result found", "data" => $response['output']];
  //     }

  //     return response()->json($responseData);
  // }


  public function cancelShipment(Request $request)
  {
    $shipmentData = [
      "accountNumber" => [
        "value" => $this->account_number
      ],
      "emailShipment" => "false",
      "senderCountryCode" => $request->input('sender_country_code', 'US'),
      "deletionControl" => "DELETE_ALL_PACKAGES",
      "trackingNumber" => $request->input('tracking_number')
    ];

    try {
      $response = $this->fedExHelper->cancelShipment($shipmentData);

      if ($response['httpStatusCode'] === 200) {
        return response()->json(['status' => true, 'message' => 'Shipment cancelled successfully']);
      } else {
        return response()->json(['status' => false, 'message' => 'Failed to cancel shipment'], $response['httpStatusCode']);
      }
    } catch (\Exception $e) {
      return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
    }
  }


  public function saveSellerDetails(StoreMarketplaceSellerBusinessDetailRequest $request)
  {

    $user = $request->attributes->get('user');
    $data = $request->validated();
    $data['user_id'] = $user->id;

    if ($request->has('id')) {
      $sellerDetail = MarketplaceSellerBusinessDetail::findOrFail($request->id);
      $sellerDetail->update($data);
    } else {
      $sellerDetail = MarketplaceSellerBusinessDetail::create($data);
    }

    return response()->json([
      'status' => true,
      'message' => 'Information saved successfully',
      'data' => $sellerDetail,
    ]);
  }

  public function getShipmentSettings(Request $request)
  {

    try {
      // Fetch all shipment settings
      $settings = ShipmentSetting::all();

      // Return response using generateResponse
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Shipment settings retrieved successfully.',
        'toast' => true
      ], ['shipment_settings' => $settings]);
    } catch (\Exception $e) {
      // Log error
      Log::error('Error fetching shipment settings: ' . $e->getMessage());

      // Return error response using generateResponse
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'An error occurred while fetching shipment settings.',
        'toast' => false
      ], ['error' => $e->getMessage()]);
    }
  }


  public function getShippingLabel($trackingNumber)
  {
    // Use the FedExHelper to get the label
    $labelResponse = $this->fedExHelper->getLabel($trackingNumber);

    // Check if there was an error in fetching the label
    if (isset($labelResponse['error']) && $labelResponse['error']) {
      return response()->json([
        'status' => 'error',
        'message' => $labelResponse['message'],
        'details' => $labelResponse['details'],
      ], 500);
    }

    return response()->json([
      'status' => 'success',
      'message' => 'Label retrieved successfully.',
      'label' => $labelResponse,  // Assuming the label data is returned here
    ], 200);
  }
}
