<?php

namespace App\Services;

use App\Http\Clients\GoogleCloudClient;
use App\Http\Clients\GoveeClient;
use App\Models\Sunset;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class LightService
{

	public $client;

	private $color;
	private $skipped;
	private $brightness;
	private $mode = 'on';
	private $googleClient;

	public static $offtime = "23:50";


	public function __construct(array $attributes = [])
	{
//		parent::__construct($attributes);
		$this->client = new GoveeClient();
		$this->googleClient = new GoogleCloudClient();
	}

	public function isSunsetTime($now, $sunsetAt)
	{
		return $now->format('H:i:s') > Carbon::parse($sunsetAt)->format('H:i:s');
	}

	public function turnOrangeAt22()
	{
		$this->brightness = 70;
		$this->mode = 'orange';
		$this->color = 'orange';

		if ($this->googleClient->getMode() === 'orange') {
			$this->skipped = true;
			return;
		}

		$client = new GoveeClient();
		$client->setColor($this->color);
		$client->setBrightness($this->brightness);

		$this->googleClient->setMode($this->mode);
	}

	public function getPhaseDateTime($phaseName)
	{
		return Session::get($phaseName);
	}

	public function turnRedBeforeTurningOff()
	{
		$this->brightness = 20;
		$this->color = 'red';
		$this->mode = 'red';

		if ($this->googleClient->getMode() === 'red') {
			$this->skipped = true;
			return;
		}

		$client = new GoveeClient();
		$client->setColor($this->color);
		$client->setBrightness($this->brightness);

		$this->googleClient->setMode($this->mode);
	}

	public function turnOffLights($now)
	{
		$client = new GoveeClient();
		$this->brightness = null;
		$this->color = null;
		$this->mode = 'off';
		$client->setColor('off');

		if ($this->googleClient->getMode() === 'off') {
			$this->skipped = true;
			return;
		}
		$this->googleClient->setMode($this->mode);

		$client->index('off');

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

	public function turnOnForSunset($completed, $sunsetAt, GoveeClient $client, $currentTimeObj): void
	{
		$this->brightness = 100;
		$this->color = 'white';
		$this->mode = 'on';

		if ($this->googleClient->getMode() === 'on') {
			$this->skipped = true;
			return;
		}

		$isSunsetTime = $this->isSunsetTime($currentTimeObj, $sunsetAt);

		if ($isSunsetTime) {
			$client->index($this->mode);
			$client->setBrightness($this->brightness);
			$client->setColor($this->color);
			Sunset::setExecuted();
			$this->googleClient->setMode($this->mode);
		}

		$this->skipped = false;
	}

	public function controlSunset()
	{
		$sunsetAt = Sunset::getSunset();
		$completed = Sunset::getExecuted();

		$sunsetDateObj = Carbon::parse($sunsetAt);
		$sunsetDateObjClone = $sunsetDateObj->clone();
		$now = Carbon::now()->setTimezone('Europe/Riga');
		$currTime = $now->clone();

		if ($this->mode === 'off') {
			return [
				'mode' => 'off'
			];
		}

		$sunset = Carbon::createFromTime($sunsetDateObj->hour, $sunsetDateObj->minute);

		$isWeekend = false;
		if (in_array($now->dayOfWeek, [5, 6])) {
			$isWeekend = true;
		}


		$this->googleClient->setPhases($sunsetDateObjClone);

		$currentMode = $this->googleClient->getMode();
		$whitePhase = $this->getPhaseDateTime('Phase1'); // 21:00  22:30
		$redPhase = $this->getPhaseDateTime('Phase3'); // 23:30  00:30

		$this->skipped = false;

		if ($now->greaterThan($sunset) && $completed === TRUE) {
//			\Log::info(['p' => 1]);
			$this->turnOnForSunset($completed, $sunsetAt, $this->client, $now);
		}

		if ($now->greaterThan($redPhase)) {
			$this->turnOffLights($now);
		}

		return [
			'brightness' => $this->brightness,
			'color' => $this->color,
			'mode' => $this->googleClient->getMode(),
			'skipped' => $this->skipped,
		];
	}

}