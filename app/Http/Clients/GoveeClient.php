<?php

namespace App\Http\Clients;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoveeClient
{
	private $client;
	private $headers;
	private $goveeUrl;
	private $body;

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

	public function baseUrl()
	{
		return 'https://developer-api.govee.com/v1/';
	}

	public function url($path = null)
	{
		if (!$path) {
			$path = "devices/control?Govee-API-Key=' " . env('GOVEE_API_KEY');
		}

		return $this->baseUrl() . $path;
	}

	public function index($switch = 'off', $device = null)
	{
		$targetDevice = env("GOVEE_DEVICE");
		$targetModel = env("GOVEE_DEVICE_MODEL");
		if ($device === 'bed') {
			$targetDevice = env("GOVEE_DEVICE2");
			$targetModel = env("GOVEE_DEVICE_MODEL2");
		}

		$response = Http::withHeaders($this->headers)->put(url: $this->url(), data: [
			"device" => $targetDevice,
			"model" => $targetModel,
			"cmd" => [
				"name" => "turn",
				"value" => $switch,
				"brightness" => 1
			]
		]);

		return ['clientResponse' => $response->json(), 'device' => $targetDevice, 'code' => $response->status()];
	}

	public function setColor($color)
	{
		if ($color == 'red') {
			$this->body['cmd']['name'] = "color";
			$this->body['cmd']['value'] = [
				"r" => 250,
				"g" => 0,
				"b" => 0
			];
		}

		if ($color == 'orange') {
			$this->body['cmd']['name'] = "color";
			$this->body['cmd']['value'] = [
				"r" => 250,
				"g" => 100,
				"b" => 0
			];
		}

		if ($color == 'white') {
			$this->body['cmd']['name'] = "color";
			$this->body['cmd']['value'] = [
				"r" => 255,
				"g" => 255,
				"b" => 255
			];
		}

		$request = Http::withHeaders($this->headers)->put($this->goveeUrl, $this->body);
		return $request->json();
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
		$request = Http::withHeaders($this->headers)->put($this->goveeUrl, $this->body);

		return $request->json();
	}
}