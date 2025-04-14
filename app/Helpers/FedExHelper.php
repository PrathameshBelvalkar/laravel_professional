<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FedExHelper
{
    protected $accessToken;
    protected $shippingUrl;

    public function __construct()
    {
        // Load configurations and initialize properties
        $this->shippingUrl = rtrim(config('shipment.fedex.url'), '/');
        $this->accessToken = $this->getAccessToken();

        if (!$this->accessToken) {
            Log::error('Failed to retrieve access token from FedEx API.');
        }
    }

    protected function getAccessToken()
    {
        $clientId = config('shipment.fedex.client_id');
        $clientSecret = config('shipment.fedex.client_secret');

        $response = Http::asForm()->post("{$this->shippingUrl}/oauth/token", [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if ($response->failed()) {
            Log::error('Failed to retrieve access token from FedEx API.', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();
        return $data['access_token'] ?? null;
    }

    public function validateAddress(array $data)
    {
        if (!$this->accessToken) {
            throw new \Exception('Access token not available');
        }

        $url = "{$this->shippingUrl}/address/v1/addresses/resolve";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $data);

        if ($response->failed()) {
            Log::error('Address validation request failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_payload' => $data
            ]);

            throw new \Exception('Address validation request failed: ' . $response->body());
        }

        return [
            'httpStatusCode' => $response->status(),
            'response' => $response->json()
        ];
    }



    public function getShippingRates(array $data)
    {
        if (!$this->accessToken) {
            throw new \Exception('Access token not available');
        }

        $url = "{$this->shippingUrl}/rate/v1/rates/quotes";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $data);

        if ($response->failed()) {
            Log::error('Shipping charges request failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_payload' => $data
            ]);

            throw new \Exception('Shipping charges request failed: ' . $response->body());
        }

        return [
            'httpStatusCode' => $response->status(),
            'response' => $response->json()
        ];
    }

    public function getProductShippingRate(array $data)
    {
        if (!$this->accessToken) {
            throw new \Exception('Access token not available');
        }

        $url = "{$this->shippingUrl}/rate/v1/rates/quotes";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $data);

        if ($response->failed()) {
            Log::error('Shipping charges request failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_payload' => $data
            ]);

            throw new \Exception('Shipping charges request failed: ' . $response->body());
        }

        return [
            'httpStatusCode' => $response->status(),
            'response' => $response->json()
        ];
    }

    public function getcreateShipment(array $data)
    {
        // Log request payload
        Log::info('FedEx Shipment Request', $data);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->post($this->shippingUrl . '/ship/v1/shipments', $data);

        // Log response
        Log::info('FedEx Shipment Response', $response->json());

        return $response;
    }

    public function getProductPickup(array $data)
    {
        // Log request payload
        Log::info('FedEx Shipment Request', $data);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->post($this->shippingUrl . '/pickup/v1/pickups', $data);

        // Log response
        Log::info('FedEx Shipment Response', $response->json());

        return $response;
    }

    public function getTrackingDetails1($trackingNumber)
    {

        $data = [
            "trackingNumberInfo" => [
                "trackingNumber" => $trackingNumber
            ],
            "includeDetailedScans" => true
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->post("{$this->shippingUrl}/track/v1/trackingnumbers", $data);

        // Debug response details
        Log::info('FedEx API Response:', [
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers(),
        ]);

        // Check if response is successful
        if ($response->successful()) {
            return $response->json();
        } else {
            // Handle errors, log them or return an error message
            return [
                'status' => 'error',
                'message' => 'Failed to fetch tracking details',
                'error' => $response->body()
            ];
        }
    }


    public function processTrackingData($response, $orderId)
    {
        $responseResult = [];



        $httpStatusCode = $response['status'];

        if ($httpStatusCode === 200) {
            if (isset($response['output']['completeTrackResults'][0]['trackResults'])) {
                $trackData = $response['output']['completeTrackResults'][0]['trackResults'][0]['scanEvents'];
                krsort($trackData);

                $trackRes = [];
                foreach ($trackData as $track) {
                    $address = isset($track['scanLocation']['city'])
                        ? $track['scanLocation']['city'] . "," . $track['scanLocation']['city'] . "," . $track['scanLocation']['countryName'] . "," . $track['scanLocation']['postalCode']
                        : $track['scanLocation']['countryName'] . "," . $track['scanLocation']['postalCode'];

                    $trackRes1 = [
                        'address' => $address,
                        'eventDescription' => $track['eventDescription'],
                        'eventstatus' => $this->getEventStatusDefinition($track['eventType']),
                        'datetime' => date("d M Y H:i a", strtotime($track['date']))
                    ];

                    $trackRes[] = $trackRes1;
                }

                $trackingNumber = $response['output']['completeTrackResults'][0]['trackResults'][0]['trackingNumberInfo']['trackingNumber'];
                $responseResult[] = [
                    "status" => true,
                    "data" => [
                        "tracking_number" => $trackingNumber,
                        "latestStatus" => $response['output']['completeTrackResults'][0]['trackResults'][0]['latestStatusDetail']['derivedCode']
                    ],
                    "possessionStatus" => $response['output']['completeTrackResults'][0]['trackResults'][0]['shipmentDetails'],
                    "order_id" => $orderId
                ];

                if ($response['output']['completeTrackResults'][0]['trackResults'][0]['latestStatusDetail']['derivedCode'] === "PU") {
                    // Call FedexShippingStart function
                } elseif ($response['output']['completeTrackResults'][0]['trackResults'][0]['latestStatusDetail']['derivedCode'] === "DL") {
                    // Call fedexProductDelivery function
                }
            } else {
                $responseResult[] = [
                    "status" => false,
                    "message" => "No result found",
                    "data" => [
                        "order_id" => $orderId,
                        "tracking_number" => $response['output']['completeTrackResults'][0]['trackResults'][0]['trackingNumberInfo']['trackingNumber'] ?? 'N/A',
                        "latestStatus" => $response['output']['completeTrackResults'][0]['trackResults'][0]['latestStatusDetail']['derivedCode'] ?? 'N/A'
                    ]
                ];
            }
        } else {
            $responseResult[] = [
                "status" => false,
                "message" => $response['errors'][0]['message'] ?? 'An error occurred',
                "data" => $response['errors'] ?? []
            ];
        }

        return $responseResult;
    }





    // public function getServicePackageOptions($postData)
    // {
    //     // Use dummy data for product details and seller details
    //     $productDetails = (object)[
    //         'user_id' => 1,
    //         'weight' => json_encode([
    //             'weight' => 10,
    //             'unitOfMeasurement' => 'LB',
    //         ])
    //     ];

    //     $sellerDetails = (object)[
    //         'postal_code' => '12345',
    //         'country_code' => 'US',
    //     ];

    //     $data = [
    //         "requestedShipment" => [
    //             "shipper" => [
    //                 "address" => [
    //                     "postalCode" => $sellerDetails->postal_code,
    //                     "countryCode" => $sellerDetails->country_code,
    //                 ],
    //             ],
    //             "recipients" => [
    //                 [
    //                     "address" => [
    //                         "postalCode" => $postData['recep_postal_code'],
    //                         "countryCode" => $postData['recep_country_iso'],
    //                     ],
    //                 ],
    //             ],
    //             "requestedPackageLineItems" => [json_decode($productDetails->weight)],
    //         ],
    //         "carrierCodes" => ["FDXE", "FDXG"],
    //     ];

    //     $response = Http::withHeaders([
    //         'Content-Type' => 'application/json',
    //         'Authorization' => 'Bearer ' . $this->accessToken,
    //     ])->post("{$this->shippingUrl}/availability/v1/packageandserviceoptions", $data);

    //     return $this->processApiResponse($response);
    // }

    protected function processApiResponse($response)
    {
        $res = $response->json();
        $httpStatusCode = $response->status();

        if ($httpStatusCode === 200) {
            if (isset($res['output']['packageOptions'])) {
                $servicesArr = array_map(function ($value) {
                    return [
                        "serviceName" => $value['serviceType']['displayText'],
                        "serviceType" => $value['serviceType']['key'],
                        "packageName" => $value['packageType']['displayText'],
                        "packageType" => $value['packageType']['key'],
                    ];
                }, $res['output']['packageOptions']);

                return [
                    "status" => true,
                    "data" => $servicesArr,
                ];
            } else {
                return [
                    "status" => false,
                    "message" => "No result found",
                    "data" => $res['output'] ?? [],
                ];
            }
        } else {
            return [
                "status" => false,
                "message" => $res['errors'][0]['message'] ?? 'An error occurred',
                "data" => $res['errors'] ?? [],
            ];
        }
    }


    public function getTrackingDetails($trackingNumber)
    {
        // Define the JSON payload
        $payload = [
            "includeDetailedScans" => true,
            "trackingInfo" => [
                [
                    "shipDateBegin" => "2020-03-29",
                    "shipDateEnd" => "2020-04-01",
                    "trackingNumberInfo" => [
                        "trackingNumber" => $trackingNumber,
                        "carrierCode" => "FDXE",
                        "trackingNumberUniqueId" => "245822~123456789012~FDEG"
                    ]
                ]
            ]
        ];

        try {
            // Perform the POST request
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'X-locale' => 'en_US',
                'Content-Type' => 'application/json'
            ])->post("{$this->shippingUrl}/track/v1/trackingnumbers", $payload);

            // Check if the response failed
            if ($response->failed()) {
                Log::error('FedEx API request failed.', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [
                    'status' => 'error',
                    'message' => 'FedEx API request failed.'
                ];
            }
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Exception occurred while fetching tracking details from FedEx API.', [
                'error' => $e->getMessage()
            ]);
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function getEventStatusDefinition($eventType)
    {
        $statusDefinitions = [
            'DL' => 'Delivered',
            'PU' => 'Picked Up',
            'OD' => 'On FedEx Vehicle for Delivery',
            'AR' => 'At FedEx Location',
            'DP' => 'Departed FedEx Location',
            'OC' => 'Shipment Information Sent to FedEx'
        ];

        return $statusDefinitions[$eventType] ?? $eventType;
    }


    public function getServicePackageOptions($post)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->post("{$this->shippingUrl}/availability/v1/packageandserviceoptions", $post);

        if ($response->failed()) {
            Log::error('Failed to retrieve package and service options from FedEx API.', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return null;
        }

        return $response->json();
    }


    public function cancelShipment(array $data)
    {
        if (!$this->accessToken) {
            throw new \Exception('Access token not available');
        }

        $url = "{$this->shippingUrl}/ship/v1/shipments/cancel";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->put($url, $data);

        if ($response->failed()) {
            Log::error('Shipment cancellation request failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_payload' => $data
            ]);

            throw new \Exception('Shipment cancellation request failed: ' . $response->body());
        }

        return [
            'httpStatusCode' => $response->status(),
            'response' => $response->json()
        ];
    }

    public function getLabel($trackingNumber)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->accessToken}",
            ])->get("{$this->shippingUrl}/track/v1/labels/{$trackingNumber}");

            if ($response->successful()) {
                return $response->json(); // Return the response data as an array
            }

            // Log error details for debugging
            Log::error('FedEx API error', [
                'trackingNumber' => $trackingNumber,
                'responseBody' => $response->body(),
                'statusCode' => $response->status(),
            ]);

            return [
                'error' => true,
                'message' => 'Failed to retrieve label from FedEx.',
                'details' => $response->json(), // Return the JSON response from FedEx
            ];
        } catch (\Exception $e) {
            // Log exception details
            Log::error('Exception while retrieving FedEx label', [
                'trackingNumber' => $trackingNumber,
                'exception' => $e->getMessage(),
            ]);

            return [
                'error' => true,
                'message' => 'An unexpected error occurred while retrieving the label.',
                'details' => $e->getMessage(),
            ];
        }
    }
}
