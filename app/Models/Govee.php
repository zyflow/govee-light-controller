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

	public static $offtime = "23:30";

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
		$this->turnOrangeBeforeTurningOff($now);
		$this->turnRedBeforeTurningOff($now, self::$offtime);

		return [
			'brightness' => $this->brightness,
			'color' => $this->color,
			'mode' => $this->mode,
		];
	}

	public function turnOnPreSunSet($now, $sunsetAt)
	{
		$isPreSunSet = $now->format('H:i:s') > Carbon::parse($sunsetAt)->subMinutes(50)->format('H:i:s');

		if ($isPreSunSet) {
			$govee = new GoveeClient();
			$govee->index('on');
			$govee->flashTurnOn();
			$govee->setBrightness(100);
		}
	}

	public function turnOrangeBeforeTurningOff($now)
	{
		$isLate = $this->checkIfMinutesBeforeTurnOff($now, 120);

		if ($isLate && $this->mode == 'on') {
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
