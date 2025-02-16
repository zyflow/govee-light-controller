<?php

namespace Tests\Feature;

use App\Http\Clients\GoveeClient;
use App\Http\Clients\OnGoingHttpClientOrder;
use App\Models\Sunset;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LightTest extends TestCase
{
	use RefreshDatabase;

	private $client;
	private $sunsetModel;
	private $sunsetAt = "20:00";

	protected function setUp(): void
	{
		parent::setUp();
		$this->fakeResponse();
	}


	protected function fakeResponse()
	{
		$this->fakeHttpResponses([
//									 $this->client->baseUrl() . "*" => function ($request) {
//										 $requestMethod = $request->method();
//										 if ($requestMethod == "GET") {
//											 return Http::response(
//												 body: ['status' => 'successfull get request']
//											 );
//										 }
//										 if ($requestMethod == "POST") {
//											 return Http::response(
//												 body: [
//														   "code" => 200,
//														   "message" => "Success",
//														   "data" => []
//													   ]
//											 );
//										 }
//										 if ($requestMethod == "PUT") {
//											 return Http::response(
//												 body: [
//														   "code" => 200,
//														   "message" => "Success",
//														   "data" => []
//													   ]
//											 );
//										 }
//									 },
									"https://developer-api.govee.com/v1/devices/control" => function($request) {
										return Http::response(
											body: ['status' =>  'done', 'code' => 200, 'message' => 'Success', 'data' => []]
										);
									}
								 ]);
	}

	protected function fakeHttpResponses(array $callbacks)
	{
		app()->forgetInstance(get_class(Http::getFacadeRoot()));
		Http::clearResolvedInstances();
		$callbacks["*"] = Http::response([
											 'status' => 'successful'
										 ]);
		Http::fake($callbacks);
	}

	/**
	 * A basic feature test example.
	 *
	 * @return void
	 */

	protected function makeRequest()
	{
		return $this->get(route("lights.index"));
	}


	public function test_light_off()
	{
		$response = $this->get(route("lights.index"))->assertStatus(200)
			->assertJson(['status' => 'off', 'clientResponse' => ['code' => 200]]);
	}

	public function test_light_on()
	{
		$response = $this->get(route("lights.index", ['turn' => 'on']))
			->assertStatus(200)
			->assertJson(['status' => 'on', 'clientResponse' => ['code' => 200]]);

	}

	public function test_light_off_with_bed()
	{
		$response = $this->get(route("lights.index", ['turn' => 'off', 'device' => 'bed']))
			->assertStatus(200)
			->assertJson([
							 'status' => 'off',
							 'clientResponse' => [
								 'code' => 200,
								 'device' => env('GOVEE_DEVICE2')
							 ],

						 ]);

	}

	public function test_light_on_with_bed()
	{
		$response = $this->get(route("lights.index", ['turn' => 'on', 'device' => 'bed']))
			->assertStatus(200)
			->assertJson([
							 'status' => 'on',
							 'clientResponse' => [
								 'code' => 200,
								 'device' => env('GOVEE_DEVICE2')
							 ],

						 ]);
//		dd($response);

	}
}
