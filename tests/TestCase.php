<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

	protected function fakeResponse($url = null) {
//        $purchaseOrderId = Arr::get($responseData, "purchaseOrderInfo.purchaseOrderId", null);
//        Http::fake([
//            $this->client->url("/purchaseOrders") => function($request) use($purchaseOrderId) {
//                $this->requestSent = $request;
//                return Http::response(
//                    body: ["purchaseOrderId" => $purchaseOrderId, "message" => "message"],
//                );
//            },
//            $this->client->url("/purchaseOrders". "/". $purchaseOrderId) => function($request) use($responseData) {
//                $this->hasFetched = true;
//                return Http::response(
//                    body: $responseData ?? [],
//                );
//            },
//            "*" => Http::response([])
//        ]);
    }
}
