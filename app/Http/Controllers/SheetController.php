<?php

namespace App\Http\Controllers;

use Google_Service_Sheets;

class SheetController extends Controller
{
    private $sheet;

    private $service;

    private $spreadSheetId;

    public function __construct()
    {
        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets and php');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        if (env('APP_ENV') === 'local') {
            $client->setAuthConfig("/var/www/html/google_cred.json");
        }
        $client->useApplicationDefaultCredentials();

//        $client->setAuthConfig("google_cred.json");
        $this->service = new Google_Service_Sheets($client);

        $this->spreadSheetId = env('SPREADSHEET_ID');
    }

    public function getSunset() {

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
        $values = [
            [$time]
        ];
        $body =  new \Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);

        $range = "sunsets!A2:A2";

        $params = [
            'valueInputOption' => 'RAW',
        ];

        $this->service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
    }

    public function setExecuted($value = "TRUE") {
        $values = [
            [$value]
        ];
        $body =  new \Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);

        $range = "sunsets!B2:B2";

        $params = [
            'valueInputOption' => 'RAW',
        ];

        $this->service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
    }
}
