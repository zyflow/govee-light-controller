<?php

namespace App\Http\Controllers;

use App\Models\LightActivity;
use App\Models\Sunset;
use Carbon\Carbon;
use DateTime;
use Google\Service\Sheets\Sheet;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class GoveeController extends Controller
{
    private $client;
    private $headers;

    private $body;

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

    public function index(Request $request, $turn = 'off') {
        $switch = $turn;
        if ($request->get('turn')) {
            $switch = $request->get('turn');
        }

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
          CURLOPT_POSTFIELDS =>'{
            "device": "' . env("GOVEE_DEVICE") . '",
            "model": "'. env("GOVEE_DEVICE_MODEL") . '",
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

    public function setBrightness($brightness) {
        $this->body['cmd']['name'] = "brightness";
        $this->body['cmd']['value'] = $brightness;

        $request = new \GuzzleHttp\Psr7\Request('PUT', $this->goveeUrl, $this->headers, json_encode($this->body));
        $res = $this->client->sendAsync($request)->wait();

        return $res->getBody();
    }

    public function flashTurnOn(Request  $request, $turn = 'on') {
        $this->body['cmd']['name'] = "color";
        $this->body['cmd']['value'] = [
            "r" => 100,
            "g" => 100,
            "b" => 100
        ];

        $request = new \GuzzleHttp\Psr7\Request('PUT', $this->goveeUrl, $this->headers, json_encode($this->body));
        $res = $this->client->sendAsync($request)->wait();

        return $res->getBody();
    }

    public function colorOrange() {
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

    public function getState() {
        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL => 'https://developer-api.govee.com/v1/devices/state?device=' . env('GOVEE_DEVICE') . '&model=' . env('GOVEE_DEVICE_MODEL'),
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
        foreach($properties as $value) {
            if (isset($value->powerState)) {
                $powerState = $value->powerState;
            }
        }

        return $powerState;
    }

    public function turnOff(Request $request) {
        $this->index($request, 'off');

        return ["status" => "ok"];
    }

    public function checkLights() : array {

        $sheet = new SheetController();

        $sunsetAt = $sheet->getSunset();
        $completed = $sheet->getExecuted();

        $request = new Request();

        $isSunsetTime = Carbon::now()->format('H:i:s') > Carbon::parse($sunsetAt)->format('H:i:s');
        $is10MinBeforeSunset =  Carbon::now()->format('H:i:s') > Carbon::parse($sunsetAt)->subMinutes(10)->format('H:i:s');




//        dd(Carbon::parse($sunsetAt)->diffInMinutes(Carbon::parse($sunsetAt)->subMinutes(10)));
        if ($completed === "FALSE"  ) {

            if (!$isSunsetTime && $is10MinBeforeSunset) {
                $diffInMinutes = Carbon::parse($sunsetAt)->diffInMinutes(Carbon::now());

                $brightness = $diffInMinutes * 10;
                if ($brightness > 100) {
                    $brightness = 100;
                    $this->colorOrange();
                }

                if ($brightness < 0) {
                    $brightness = 1;
                }

                $this->setBrightness($brightness);
                $this->flashTurnOn($request,'on');
            }


            if ($isSunsetTime) {
                $this->index($request,'on');
                $sheet->setExecuted();
                return ['status' => 'switched on'];
            }

        }

        return ['status' => 'done'];
    }

    public function setSunetTime() {
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

        $sheet = new SheetController();
        $dateTime = new DateTime($data->results->sunset);
        $time = $dateTime->format("H:i:s");

        $sheet->setTime($time);

        return ['status' => 'done'];
    }


    public function checkIfCompleted() {
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
}
