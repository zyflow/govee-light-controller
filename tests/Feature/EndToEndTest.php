<?php

namespace Tests\Feature;

use App\Http\Clients\GoveeClient;
use App\Http\Clients\OnGoingHttpClientOrder;
use App\Models\Sunset;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EndToEndTest extends TestCase
{
	use RefreshDatabase;

	private $client;
	private $sunsetModel;
	private $sunsetAt = "20:00";

	protected function setUp(): void
	{
		parent::setUp();
		$this->client = new GoveeClient();
		$this->fakeResponse();

		$sunssetAt = "20:00";
		$this->travelTo(Carbon::parse("2024-01-01 20:01:00"));
		Sunset::create(['sunset_at' => $sunssetAt]);
		$this->sunsetModel = Sunset::first();
	}


	protected function fakeResponse()
	{
		$this->fakeHttpResponses([
									 $this->client->baseUrl() . "*" => function ($request) {
										 $requestMethod = $request->method();
										 if ($requestMethod == "GET") {
											 return Http::response(
												 body: ['status' => 'successfull get request']
											 );
										 }
										 if ($requestMethod == "POST") {
											 return Http::response(
												 body: [
														   "code" => 200,
														   "message" => "Success",
														   "data" => []
													   ]
											 );
										 }
										 if ($requestMethod == "PUT") {
											 return Http::response(
												 body: [
														   "code" => 200,
														   "message" => "Success",
														   "data" => []
													   ]
											 );
										 }
									 },
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
		return $this->get(route("checkLights"));
	}

	public function test_lights_turn_on(): void
	{
		$this->assertEquals($this->sunsetAt, $this->sunsetModel->sunset_at);

		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('turning_on', $response['state']);
		$this->assertEquals('Success', $response['switchedOn']['message']);
		$this->assertEquals('Success', $response['settingBrightness']['message']);
		$this->assertEquals('Success', $response['settingColorOrange']['message']);
		$this->assertEquals('turning_on', $response['on_status']);
	}

	public function test_lights_turn_off(): void
	{
		$this->travelTo(Carbon::parse("2024-01-01 23:31:00"));

		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('turning_off', $response['on_status']);
	}

	public function test_light_gets_orange_before_off() {
		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		$this->travelTo(Carbon::parse("2024-01-01 22:31:00"));
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('turning_off', $response['on_status']);
	}
}
