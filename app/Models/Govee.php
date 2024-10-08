<?php

namespace App\Models;

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

	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);
		$this->client = new GoveeClient();
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

		$this->turnOnForSunset($completed, $sunsetAt, $this->client, $now);
		$this->turnOrangeAt22($now);
		$this->turnOrangeBeforeTurningOff($now);
		$this->turnRedBeforeTurningOff($now, self::$offtime);

		return [
			'brightness' => $this->brightness,
			'color' => $this->color,
			'mode' => $this->mode,
		];
	}

	public function turnOrangeAt22($now) {
		$timeIsReady = $this->checkIfTimeIsToAct($now, 21, 0);

		if ($timeIsReady && $this->mode == 'on') {
			$this->brightness = 70;
			$this->mode = 'orange_21';
			$this->color = 'orange';

			$client = new GoveeClient();
			$client->setColor($this->color);
			$client->setBrightness($this->brightness);
		}
	}

	public function turnOrangeBeforeTurningOff($now)
	{
		$timeIsReady = $this->checkIfTimeIsToAct($now, 22, 00);

		if ($timeIsReady && $this->mode == 'orange_21') {
			$this->brightness = 40;
			$this->mode = 'orange';
			$this->color = 'orange';

			$client = new GoveeClient();
			$client->setColor($this->color);
			$client->setBrightness($this->brightness);
		}
	}

	public function turnRedBeforeTurningOff($now, $offTime)
	{
		$isLate = $this->checkIfMinutesBeforeTurnOff($now, 60);

		if ($isLate && $this->mode == 'orange') {
			$this->brightness = 20;
			$this->color = 'red';
			$this->mode = 'red';

			$client = new GoveeClient();
			$client->setColor($this->color);
			$client->setBrightness($this->brightness);
		}
	}

	public function turnOffLights($now)
	{
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
		switch ($now->englishDayOfWeek) {
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

	public function turnOnForSunset($completed, $sunsetAt, GoveeClient $client, $currentTimeObj): void
	{
		if ($completed) {
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
		}
	}

	public function isSunsetTime($now, $sunsetAt)
	{
		return $now->format('H:i:s') > Carbon::parse($sunsetAt)->format('H:i:s');
	}


}
