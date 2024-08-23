<?php

namespace App\Http\Clients;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class GoveeClient
{
	private $client;
	private $headers;


	private $goveeUrl;

	public function __construct()
	{
		$this->client = new Client();
		$this->goveeUrl = 'https://developer-api.govee.com/v1/devices/control';
		$this->headers = [
			'Govee-API-Key' => '1a22991c-c9ee-40ab-9a76-541f60dd02aa',
			'Content-Type' => 'application/json'
		];

		$this->body = [
			"device" => "55:7C:A4:C1:38:C1:3B:62",
			"model" => "H6143",
			"cmd" => [
				"name" => "color",
				"value" => [
					"r" => 255,
					"g" => 100,
					"b" => 0
				]
			]
		];
	}

	public function index($switch = 'off')
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => 'https://developer-api.govee.com/v1/devices/control?Govee-API-Key=' . env('GOVEE_API_KEY'),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_POSTFIELDS => '{
            "device": "' . env("GOVEE_DEVICE") . '",
            "model": "' . env("GOVEE_DEVICE_MODEL") . '",
            "cmd": {
                "name": "turn",
                "value": "' . $switch . '",
                "brightness": 1
            }
        }',
			CURLOPT_HTTPHEADER => [
				'Govee-API-Key: ' . env('GOVEE_API_KEY'),
				'Content-Type: application/json'
			],
		]);

		$response = curl_exec($curl);

		\Log::info(['logg' => $response]);
		curl_close($curl);

		return $response;
	}

	public function flashTurnOn()
	{
		$this->body['cmd']['name'] = "color";
		$this->body['cmd']['value'] = [
			"r" => 250,
			"g" => 250,
			"b" => 250
		];

		$request = new \GuzzleHttp\Psr7\Request('PUT', $this->goveeUrl, $this->headers, json_encode($this->body));
		$res = $this->client->sendAsync($request)->wait();

		return $res->getBody();
	}

	public function colorOrange()
	{
		$this->body['cmd']['name'] = "color";
		$this->body['cmd']['value'] = [
			"r" => 250,
			"g" => 100,
			"b" => 0
		];

		$request = new \GuzzleHttp\Psr7\Request('PUT', $this->goveeUrl, $this->headers, json_encode($this->body));
		$res = $this->client->sendAsync($request)->wait();

		return $res->getBody();
	}


	public function colorRed()
	{
		$this->body['cmd']['name'] = "color";
		$this->body['cmd']['value'] = [
			"r" => 250,
			"g" => 0,
			"b" => 0
		];

		$request = new \GuzzleHttp\Psr7\Request('PUT', $this->goveeUrl, $this->headers, json_encode($this->body));
		$res = $this->client->sendAsync($request)->wait();

		return $res->getBody();
	}

	public function getState()
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => 'https://developer-api.govee.com/v1/devices/state?device=' . env(
					'GOVEE_DEVICE'
				) . '&model=' . env('GOVEE_DEVICE_MODEL'),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => [
				'Govee-API-Key: ' . env('GOVEE_API_KEY'),
				'Content-Type: application/json'
			],
		]);

		$response = curl_exec($curl);
		curl_close($curl);
		$data = json_decode($response);
		$powerState = 'off';
		$properties = $data->data->properties;
		foreach ($properties as $value) {
			if (isset($value->powerState)) {
				$powerState = $value->powerState;
			}
		}

		return $powerState;
	}

	public function setBrightness($brightness)
	{
		$this->body['cmd']['name'] = "brightness";
		$this->body['cmd']['value'] = $brightness;

		$request = new \GuzzleHttp\Psr7\Request('PUT', $this->goveeUrl, $this->headers, json_encode($this->body));
		$res = $this->client->sendAsync($request)->wait();

		return $res->getBody();
	}
}