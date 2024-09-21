<?php

namespace App\Models;

use App\Http\Clients\GoveeClient;
use App\Http\Controllers\SheetController;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Govee extends Model
{
	use HasFactory;

	public static $offtime = "23:50";
	public function controlSunset($client)
	{
		$sheet = new SheetController();
		$sunsetAt = $sheet->getSunset();
		$completed = $sheet->getExecuted();

		$isTurnOn = $this->turnOnForSunset($completed, $sheet, $sunsetAt, Carbon::now());
		if ($isTurnOn) {
			$sheet->setExecuted();
		}

		$this->turnRedBeforeTurningOff(Carbon::now(), $sunsetAt);
		$this->turnOffLights();
		$this->turnOnPreSunSet(Carbon::now(), $sunsetAt);
	}

	public function turnOnPreSunSet($now, $sunsetAt) {
		$isPreSunSet = $now->format('H:i:s') > Carbon::parse($sunsetAt)->subMinutes(50)->format('H:i:s');

		if ($isPreSunSet) {
			$govee = new GoveeClient();
			$govee->index('on');
			$govee->flashTurnOn();
			$govee->setBrightness(100);
		}
	}

	public function turnRedBeforeTurningOff($now) {
		$isLate = $this->checkIfMinutesBeforeTurnOff($now, 40);

		if ($isLate) {
			$client = new GoveeClient();
			$client->colorRed();
			$client->setBrightness(40);

			return true;
		}

		return false;
	}
	public function turnOffLights($now) {
		$isLate = $this->checkIfLate($now);

		if ($isLate) {
			$client = new GoveeClient();
			$client->index('off');
		}
	}

	public function checkIfMinutesBeforeTurnOff($now, $timeBeforeTurnOff) {
		$switchOffTime = "23:50";
		$switchOffTimeArr = explode(':', $switchOffTime);
		$switchToRedTime = $now->copy();
		$switchToRedTime = $switchToRedTime->setTime($switchOffTimeArr[0], $switchOffTimeArr[1]);
		$switchToRedTime->subMinutes($timeBeforeTurnOff);

		$isChillDay = false;
		if ($now->weekday() >= 5) {
			$isChillDay = true;
		}

		if ($now->gt($switchToRedTime) && $isChillDay == false) {
			return true;
		}

		return false;
	}
	public function checkIfLate(Carbon $now) {
		$switchOffTime = "23:50";
		switch($now->englishDayOfWeek) {
			case 'Friday':
			case 'Saturday':
				$switchOffTime = "0:30";
				break;
		}

		$switchOffTimeArr = explode(':', $switchOffTime);
		$state = false;
		$turnOffTime = $now->copy();
		$turnOffTime->setTime($switchOffTimeArr[0], $switchOffTimeArr[1]);
		if ($now->gt($turnOffTime)) {
			$state = true;
		}

		return $state;
	}

	public function turnOnForSunset($completed, $sunsetAt, GoveeClient $client, $currentTimeObj) {
		if ($completed) {
			return false;
		}
		$state = false;
		$request = new Request();
		$isSunsetTime = $this->isSunsetTime($currentTimeObj, $sunsetAt);

		if ($isSunsetTime) {
			$state = true;
			$client->index($request, 'on');
			$client->setBrightness(40);
			$client->colorOrange();
		}

		return $state;
	}

	public function isSunsetTime($now, $sunsetAt) {
		return $now->format('H:i:s') > Carbon::parse($sunsetAt)->format('H:i:s');
	}




}
