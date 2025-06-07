<?php

namespace App\Http\Clients;

use Carbon\Carbon;
use Google_Client;
use Google_Service_Sheets;
use Illuminate\Support\Facades\Session;

class GoogleCloudClient
{
	private $service;
	private $spreadSheetId;

	public function __construct()
	{
		$this->spreadSheetId = env('SPREADSHEET_ID');
		$client = app(Google_Client::class);
		$client->setApplicationName('Google Sheets and php');
		$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
		$client->setAccessType('offline');
		if (in_array(env('APP_ENV'), ['local'])) {
			$client->setAuthConfig("/var/www/html/google_cred.json");
		}
		if (in_array(env('APP_ENV'), ['prod', 'stage'])) {
			$client->useApplicationDefaultCredentials();
		}

		$this->service = new Google_Service_Sheets($client);
	}

	public function getSunset() {
		if (env('APP_ENV') === 'testing') {
			return "2025-01-07 15:40:59";
		}
		$this->spreadSheetId = env('SPREADSHEET_ID');
		$range = "sunsets!A2:A2";
		$response = $this->service->spreadsheets_values->get($this->spreadSheetId, $range);
		$values = $response->getValues();

		$sunsetAt = $values[0][0];

		return $sunsetAt;
	}

	public function getExecuted() {
		$range = "sunsets!B2:B2";
		$response = $this->service->spreadsheets_values->get($this->spreadSheetId, $range);
		$values = $response->getValues();

		$sunsetAt = $values[0][0];

		return $sunsetAt;
	}

	public function setTime($time) {
		$values = [[$time]];
		$body = new \Google_Service_Sheets_ValueRange(['values' => $values ]);

		$range = "sunsets!A2:A2";
		$params = ['valueInputOption' => 'RAW'];

		$this->service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
	}

	public function setCurrentTime($time) {
		$values = [[$time]];
		$body = new \Google_Service_Sheets_ValueRange(['values' => $values ]);

		$range = "sunsets!G2:G2";
		$params = ['valueInputOption' => 'RAW'];

		$this->service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
	}

	public function setExecuted($value) {
		$values = [[$value] ];
		$body = new \Google_Service_Sheets_ValueRange([ 'values' => $values  ]);
		$range = "sunsets!B2:B2";
		$params = [ 'valueInputOption' => 'RAW'];

		$this->service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
	}

	public function setMode($value) {
		if (env('APP_ENV') == 'testing') {
			Session::put("mode", $value);
			return ;
		}
		$values = [[$value] ];
		$body = new \Google_Service_Sheets_ValueRange([ 'values' => $values  ]);
		$range = "sunsets!C2:C2";
		$params = [ 'valueInputOption' => 'RAW'];

		$this->service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
	}

	public function getMode()
	{
		if (env('APP_ENV') == 'testing') {
			return Session::get("mode");
		}

		$range = "sunsets!C2:C2";

		$response = $this->service->spreadsheets_values->get($this->spreadSheetId, $range);
		$values = $response->getValues();

		return $values[0][0];;
	}

	public function setPhases($now) {

		$dayOfWeek = $now->dayOfWeek;

		$range = "sunsets!C19:E19";
		$dayPhases = [];
		$dayPhases[] = $this->createFromTime($now,'21:00');
		$dayPhases[] = $this->createFromTime($now,'23:00');
		$dayPhases[] = $this->createFromTime($now,'23:30');
		$dayPhases = [$dayPhases];

		if (in_array($dayOfWeek, [5,6 ])) {
			$dayPhases = [];
			$dayPhases[] = $this->createFromTime($now, '21:00');
			$dayPhases[] = $this->createFromTime($now, '23:59');
			$dayPhases[] = $this->createFromTime($now->addDay(), '00:30');
			$dayPhases = [$dayPhases];
		}

		if (env('APP_ENV') != 'testing') {
			$response = $this->service->spreadsheets_values->get($this->spreadSheetId, $range);
			$dayPhases = $response->getValues();
		}

		foreach($dayPhases[0] as $key => $dayPhase) {
			$phaseKey = 'Phase'.$key+1;
			Session::put($phaseKey, $dayPhase);
		}
	}

	public function createFromTime($now, $time) {
		$date = $now->createFromFormat('H:i', $time)->setDay($now->day);
//		if ($date->hour < 5) {
//			$date->addDay();
//		}

		return $date;
	}
}