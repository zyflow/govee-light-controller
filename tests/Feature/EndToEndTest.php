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
		$this->travelTo(Carbon::parse("2024-01-01 20:01:00"));
		$this->assertEquals($this->sunsetAt, $this->sunsetModel->sunset_at);

		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('on', $response['mode']);
		$this->assertEquals('100', $response['brightness']);
		$this->assertEquals('white', $response['color']);
	}

	public function test_lights_turn_off(): void
	{
		$this->travelTo(Carbon::parse("2024-01-01 23:55:00"));

		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('off', $response['mode']);
	}

	public function test_lights_get_orange_at_21() {
		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		$this->travelTo(Carbon::parse("2024-01-01 21:01:00"));
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals( 'orange_21', $response['mode']);
		$this->assertEquals("70", $response['brightness']);
		$this->assertEquals("orange", $response['color']);
	}

	public function test_light_gets_orange_before_off() {
		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		$this->travelTo(Carbon::parse("2024-01-01 22:50:00"));
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('orange', $response['mode']);
		$this->assertEquals("40", $response['brightness']);
		$this->assertEquals("orange", $response['color']);
	}

	public function test_light_gets_red_before_turning_off() {
		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		$this->travelTo(Carbon::parse("2024-01-01 23:10:00"));
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals( 'red', $response['mode']);
		$this->assertEquals("20", $response['brightness']);
		$this->assertEquals("red", $response['color']);
	}
}
