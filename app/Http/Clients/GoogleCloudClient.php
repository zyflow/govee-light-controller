<?php

namespace App\Http\Clients;

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
			return "15:40:59";
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
}