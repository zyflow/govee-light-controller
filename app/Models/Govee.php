<?php

namespace App\Models;

use App\Http\Clients\GoogleCloudClient;
use App\Http\Clients\GoveeClient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Govee extends Model
{
	use HasFactory;

	public $client;

	private $color;
	private $brightness;
	private $mode = 'on';
	private $googleClient;

	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);
		$this->client = new GoveeClient();
		$this->googleClient = new GoogleCloudClient();
	}

	public static $offtime = "23:50";

	public function controlSunset()
	{
		$sunsetAt = Sunset::getSunset();
		$completed = Sunset::getExecuted();

		$now = Carbon::now();

		$this->turnOffLights($now);
		if ($this->mode === 'off') {
			return [
				'mode' => 'off'
			];
		}

		$sunsetArr = explode(':', $sunsetAt);
		$sunset = Carbon::createFromTime($sunsetArr[0], $sunsetArr[1]);
		$isWeekend = $now->isWeekend();
		switch ($now) {
			case $now->between($sunset, Carbon::createFromTime(21, 00)) :
				$this->turnOnForSunset($completed, $sunsetAt, $this->client, $now);
				break;
			case $now->between(Carbon::createFromTime(21, 00), Carbon::createFromTime(22, 00))  && !$isWeekend:
				$this->turnOrangeAt22($now);
				break;
			case $now->between(Carbon::createFromTime(22, 00), Carbon::createFromTime(23, 00))  && !$isWeekend:
				$this->turnOrangeBeforeTurningOff($now);
				break;
			case $now->between(Carbon::createFromTime(23, 00), Carbon::createFromTime(23, 30)) && !$isWeekend:
				$this->turnRedBeforeTurningOff($now);
				break;
			case $now->isAfter(Carbon::createFromTime(23, 50));
				$this->turnOffLights($now);
				break;
		}

		return [
			'brightness' => $this->brightness,
			'color' => $this->color,
			'mode' => $this->googleClient->getMode()
		];
	}

	public function turnOrangeAt22($now)
	{
//		dump('setting', $this->mode, $this->googleClient->getMode());

		if ($this->googleClient->getMode() === 'orange_21') {
			return;
		}
		$this->brightness = 70;
		$this->mode = 'orange_21';
		$this->color = 'orange';

		$client = new GoveeClient();
		$client->setColor($this->color);
		$client->setBrightness($this->brightness);

		$this->googleClient->setMode($this->mode);
	}

	public function turnOrangeBeforeTurningOff($now)
	{
		if ($this->googleClient->getMode() === 'orange') {
			return;
		}

		$this->brightness = 40;
		$this->mode = 'orange';
		$this->color = 'orange';

		$client = new GoveeClient();
		$client->setColor($this->color);
		$client->setBrightness($this->brightness);

		$this->googleClient->setMode($this->mode);
	}

	public function turnRedBeforeTurningOff($now)
	{
		if ($this->googleClient->getMode() === 'red') {
			return;
		}

		$this->brightness = 20;
		$this->color = 'red';
		$this->mode = 'red';

		$client = new GoveeClient();
		$client->setColor($this->color);
		$client->setBrightness($this->brightness);
		$this->googleClient->setMode($this->mode);
	}

	public function turnOffLights($now)
	{
		// Friday evening,
		$isLate = $this->checkIfLate($now);

		if ($isLate) {
			$client = new GoveeClient();
			$client->index('off');
			$this->mode = 'off';
		}
	}

	public function checkIfMinutesBeforeTurnOff($now, $timeBeforeTurnOff)
	{
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

	public function checkIfTimeIsToAct($now, $timeWhenToActHours, $timeToActMinutes)
	{
		$compareToTime = $now->copy();
		$compareToTime = $compareToTime->setTime($timeWhenToActHours, $timeToActMinutes);

		if ($now->gt($compareToTime)) {
			return true;
		}

		return false;
	}

	public function checkIfLate(Carbon $now)
	{
		$switchOffTime = "23:50";
		$switchOffTimeArr = explode(':', $switchOffTime);
		$state = false;
		$turnOffTime = $now->copy();
		$turnOffTime->setTime($switchOffTimeArr[0], $switchOffTimeArr[1]);

		// If it is night and still in mode red, turn off (bypass by changing to different mode)
		if (!$now->hour > 1 && $now->hour < 14 ) {
			return true;
		}

		if (!$now->isWeekend() && $now->hour >= 0 && $now->hour < 14) {
			return true;
		}

		switch ($now->englishDayOfWeek) {
			case 'Saturday':
//			case 'Sunday':
				$switchOffTime = "0:30";
				$switchOffTimeArr = explode(':', $switchOffTime);
				$state = false;
				$turnOffTime = $now->copy();
				$turnOffTime->setTime($switchOffTimeArr[0], $switchOffTimeArr[1]);
				break;
		}


		if ($now->gt($turnOffTime)) {
			$state = true;
		}

		return $state;
	}

	public function turnOnForSunset($completed, $sunsetAt, GoveeClient $client, $currentTimeObj): void
	{
		if ($completed && $this->googleClient->getMode() === 'on') {
			return;
		}

		$isSunsetTime = $this->isSunsetTime($currentTimeObj, $sunsetAt);

		if ($isSunsetTime) {
			$this->brightness = 100;
			$this->color = 'white';
			$this->mode = 'on';

			$client->index($this->mode);
			$client->setBrightness($this->brightness);
			$client->setColor($this->color);
			Sunset::setExecuted();
			$this->googleClient->setMode($this->mode);
		}
	}

	public function isSunsetTime($now, $sunsetAt)
	{
		return $now->format('H:i:s') > Carbon::parse($sunsetAt)->format('H:i:s');
	}


}
