<?php

namespace App\Http\Controllers;

use App\Models\LightActivity;
use App\Models\Sunset;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoveeController extends Controller
{

    /**
     * @param Request $request
     * @param $submissionID
     * @param $fileId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
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

    public function checkLights() : bool {
        $request = new Request();
        $sunsetObj = Sunset::orderBy('id', 'desc')->firstOrFail();
        $sunsetAt = $sunsetObj->sunset_at;
        $completed = $this->checkIfCompleted();

        if ($completed === false && Carbon::now()->format('H:i:s') > Carbon::parse($sunsetAt)->format('H:i:s')) {
            \Log::info('turning lights on');
            $this->index($request,'on');
            $this->saveAsCompleted();
            return true;
        }

        return false;
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
//        $filePath = storage_path('sunset.txt');
        Sunset::create([
            'sunset_at' => $data->results->sunset
        ]);
//        file_put_contents($filePath, $data->results->sunset);


        return ['status' => 'done'];
    }


    public function saveAsCompleted() : void {
        LightActivity::where(['execution_date' => Carbon::now()->format('Y-m-d')])->update(['completed' => true]);
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
