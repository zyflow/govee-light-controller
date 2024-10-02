<?php

namespace App\Models;

use App\Http\Clients\GoveeClient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Govee extends Model
{
	use HasFactory;

	public $client;
	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);
		$this->client = new GoveeClient();
	}

	public static $offtime = "23:30";
	public function controlSunset()
	{
		$status = 'turning_off';
		$sunsetAt = Sunset::getSunset();
		$completed = Sunset::getExecuted();

		$responseObj = $this->turnOnForSunset($completed, $sunsetAt, $this->client, Carbon::now());
		if ($responseObj['state']) {
			Sunset::setExecuted();
			$status = 'turning_on';
		}

		$responseObj['on_status'] = $status;

//		$responseObj = $this->turnRedBeforeTurningOff(Carbon::now(), self::$offtime);
		$this->turnOffLights(Carbon::now());
//		$this->turnOnPreSunSet(Carbon::now(), $sunsetAt);

		return $responseObj;
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

	public function turnRedBeforeTurningOff($now, $offTime) {
		$isLate = $this->checkIfMinutesBeforeTurnOff($now, 60);

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
		$state = null;
		if ($completed) {
			return [
				'state' => false,
			];
		}

		$isSunsetTime = $this->isSunsetTime($currentTimeObj, $sunsetAt);

		if ($isSunsetTime) {
			$state = true;
			$switchingOn = $client->index('on');
			$settingBrightness = $client->setBrightness(40);
			$settingColorOrange = $client->colorOrange();
		}

		return [
			'state' => $state,
			'switchedOn' => $switchingOn,
			'settingBrightness' => $settingBrightness,
			'settingColorOrange' => $settingColorOrange
		];
	}

	public function isSunsetTime($now, $sunsetAt) {
		return $now->format('H:i:s') > Carbon::parse($sunsetAt)->format('H:i:s');
	}




}
