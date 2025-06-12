<?php

namespace Tests\Feature;

use App\Http\Clients\GoveeClient;
use App\Http\Clients\OnGoingHttpClientOrder;
use App\Models\Sunset;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
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
		Session::put('sunsetAt', '2024-01-01 15:40:39');

		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('on', $response['mode']);
		$this->assertEquals('100', $response['brightness']);
		$this->assertEquals('white', $response['color']);
	}

	public function test_lights_turn_on_twice(): void
	{
		$this->travelTo(Carbon::parse("2024-01-01 20:01:00"));
		$this->assertEquals($this->sunsetAt, $this->sunsetModel->sunset_at);
		Session::put('sunsetAt', '2024-01-01 15:40:39');

		$response = $this->makeRequest()
			->assertSuccessful()
			->json();


		$this->assertEquals('on', $response['mode']);
		$this->assertEquals('100', $response['brightness']);
		$this->assertEquals('white', $response['color']);
		$this->assertEquals(false, $response['skipped']);


		$response2 = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('on', $response2['mode']);
		$this->assertEquals(100, $response2['brightness']);
		$this->assertEquals('white', $response2['color']);
		$this->assertEquals(true, $response2['skipped']);
	}

	public function test_lights_turn_off(): void
	{
		$this->travelTo(Carbon::parse("2024-01-01 23:55:00"));
		Session::put('sunsetAt', '2024-01-01 15:40:39');

		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('off', $response['mode']);
	}

	public function test_lights_get_orange_at_21()
	{
		Session::put('mode', 'on');
		Session::put('sunsetAt', '2024-01-01 15:40:39');

		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		$this->travelTo(Carbon::parse("2024-01-01 21:01:00"));
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('orange', $response['mode']);
		$this->assertEquals("70", $response['brightness']);
		$this->assertEquals("orange", $response['color']);
		$this->assertEquals(false, $response['skipped']);
	}

	public function test_lights_get_orange_at_21_twice()
	{
		Session::put('mode', 'on');
		Session::put('sunsetAt', '2024-01-01 15:40:39');

		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		$this->travelTo(Carbon::parse("2024-01-01 21:01:00"));
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('orange', $response['mode']);
		$this->assertEquals("70", $response['brightness']);
		$this->assertEquals("orange", $response['color']);


		$response2 = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('orange', $response2['mode']);
		$this->assertEquals(70, $response2['brightness']);
		$this->assertEquals('orange', $response2['color']);
		$this->assertEquals(true, $response2['skipped']);
	}

	public function test_light_gets_orange_before_off()
	{
		Session::put('mode', 'orange');
		Session::put('sunsetAt', '2024-01-01 15:40:39');

		$this->sunsetModel->fill(['executed' => "TRUE"])->save();

		$this->travelTo(Carbon::parse("2024-01-01 22:01:00"));
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('orange', $response['mode']);
		$this->assertEquals(70, $response['brightness']);
		$this->assertEquals("orange", $response['color']);
		$this->assertEquals(true, $response['skipped']);
	}


	public function test_light_gets_orange_before_off_twice()
	{
		Session::put('mode', 'orange');
		Session::put('sunsetAt', '2024-01-01 15:40:39');

		$this->sunsetModel->fill(['executed' => "TRUE"])->save();

		$this->travelTo(Carbon::parse("2024-01-01 22:01:00"));
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('orange', $response['mode']);
		$this->assertEquals(70, $response['brightness']);
		$this->assertEquals("orange", $response['color']);
		$this->assertEquals(true, $response['skipped']);


		$response2 = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('orange', $response2['mode']);
		$this->assertEquals(70, $response2['brightness']);
		$this->assertEquals('orange', $response2['color']);
		$this->assertEquals(true, $response2['skipped']);
	}

	public function test_light_gets_red_before_turning_off()
	{
		Session::put('mode', 'orange');
		Session::put('sunsetAt', '2024-01-01 15:40:39');

		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		$this->travelTo(Carbon::parse("2024-01-01 23:01:00"));
		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('red', $response['mode']);
		$this->assertEquals("20", $response['brightness']);
		$this->assertEquals("red", $response['color']);
		$this->assertEquals(false, $response['skipped']);
	}

	public function test_lights_not_dont_change_mode_on_weekends()
	{
		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		Session::put('sunsetAt', '2024-01-06 15:40:39');

		Session::put('mode', 'white');
		$this->travelTo(Carbon::parse("2024-01-06 23:01:00")); // Saturday

		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('orange', $response['mode']);
		$this->assertEquals(70, $response['brightness']);
		$this->assertEquals('orange', $response['color']);
		$this->assertEquals(false, $response['skipped']);
	}

	public function test_lights_turn_off_on_weekends_schedule()
	{
		$this->sunsetModel->fill(['executed' => "TRUE"])->save();

		Session::put('sunsetAt', '2024-01-12 15:40:39');
		Session::put('mode', 'red');

		$this->travelTo(Carbon::parse("2024-01-13 00:45:00")); // Saturday


		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('off', $response['mode']);
		$this->assertEquals(null, $response['brightness']);
		$this->assertEquals(null, $response['color']);
		$this->assertEquals(true, $response['skipped']);
	}

	/**
	 * @return void A lot of days has passed by
	 */
	public function test_days_passed_with_mode()
	{
		$this->sunsetModel->fill(['executed' => "TRUE"])->save();
		Session::put('sunsetAt', '2024-01-12 15:40:39');

		$this->travelTo(Carbon::parse("2024-02-22 16:45:00")); // Saturday

		$response = $this->makeRequest()
			->assertSuccessful()
			->json();

		$this->assertEquals('off', $response['mode']);
		$this->assertEquals(null, $response['brightness']);
		$this->assertEquals(null, $response['color']);
		$this->assertEquals(true, $response['skipped']);



//		$this->sunsetModel->fill(['executed' => "false"])->save();
//		Session::put('sunsetAt', '2024-01-12 15:40:39');
//
//		$this->travelTo(Carbon::parse("2024-02-22 16:45:00")); // Saturday
//
//		$response = $this->makeRequest()
//			->assertSuccessful()
//			->json();
//
//		$this->assertEquals('off', $response['mode']);
//		$this->assertEquals(null, $response['brightness']);
//		$this->assertEquals(null, $response['color']);
//		$this->assertEquals(true, $response['skipped']);
	}


}
