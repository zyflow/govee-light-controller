<?php

namespace App\Http\Controllers;

use App\Http\Clients\GoveeClient;
use App\Models\LightActivity;
use App\Models\Sunset;
use App\Services\LightService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoveeController extends Controller
{
	public function index(Request $request, $turn = 'off')
	{
		$switch = $turn;
		if ($request->get('turn')) {
			$switch = $request->get('turn');
		}

		$client = new GoveeClient();
		$clientResponse = $client->index($switch, $request->get('device'));

		return ['status' => $switch, 'clientResponse' => $clientResponse];
	}

	public function turnOff(Request $request)
	{
		$this->index($request, 'off');

		return ["status" => "ok"];
	}

	/**
	 * @return array Kernel calls this
	 */
	public function checkLights(): array
	{
		return (new LightService())->controlSunset();
	}

	public function setSunetTime()
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://api.sunrise-sunset.org/json?lat=56.951579&lng=24.117182&formatted=0',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
		));

		$response = curl_exec($curl);
		curl_close($curl);

		$data = json_decode($response);

		$dateTime = Carbon::parse($data->results->sunset)->setTimezone('Europe/Riga')->format('Y-m-d H:i:s');
		Sunset::setTime($dateTime);

		return ['status' => 'done'];
	}


	public function checkIfCompleted()
	{
		$exists = LightActivity::where(['execution_date' => Carbon::now()->format('Y-m-d')])->first();
		if (!$exists) {
			LightActivity::create(['execution_date' => Carbon::now()->format('Y-m-d'), 'completed' => false]);
			return false;
		}

		if ($exists && $exists->completed === 0) {
			return false;
		}

		return true;
	}

	public function movieTime(Request $request)
	{
		$switch = $request->get('turn');

		$client = new GoveeClient();
		$clientResponse = $client->index($switch);

		if ($switch === 'on') {
			$client->setColor('orange');
			$client->setBrightness(30);
		}


		return ['status' => $switch, 'clientResponse' => $clientResponse];
	}
}
