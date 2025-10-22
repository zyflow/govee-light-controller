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
		$brightOrangePhase = $this->getPhaseDateTime('Phase2'); // 23:00  00:10
		$redPhase = $this->getPhaseDateTime('Phase3'); // 23:30  00:30

//		dd($now, $sunset);

//		dump([
//				 'now' => $now->format('Y-m-d H:i'),
//				 'sunset' => $sunset->format('H:i'),
//				 'currmode' => $currentMode,
//				 'weekend' => $isWeekend,
//				 'dayOfWeek' => Carbon::now()->dayOfWeek,
//				 'sessions' => [
//					 'whitephase' => $whitePhase->toDateTimeString(),
//					 'brightorangephase' => $brightOrangePhase->toDateTimeString(),
//					 'redphase' => $redPhase->toDateTimeString(),
//				 ]
//			 ]);


//		\Log::info(['assumed mode' => $currentMode]);

		$this->skipped = false;

		if ($now->greaterThan($sunset) && $now->lessThan($whitePhase)) {
//			\Log::info(['p' => 1]);
			$this->turnOnForSunset($completed, $sunsetAt, $this->client, $now);
		}

		if ($now->greaterThan($whitePhase) && $now->lessThan($brightOrangePhase)) {
//			\Log::info(['p' => 2, $whitePhase->format('d H:i'), $brightOrangePhase->format('d H:i')]);
			$this->turnOrangeAt22();
		}

		if ($now->greaterThan($brightOrangePhase) && $now->lessThan($redPhase)) {
//			\Log::info(['p' => 3, $brightOrangePhase->format('d H:i'), $redPhase->format('d H:i')]);
			$this->turnRedBeforeTurningOff();
		}

		if ($now->greaterThan($redPhase)) {
//			\Log::info([
//						   'p' => 4,
//						   'curr time ' => $now->format('Y-m-d H:i'),
//						   'red phase datetome ' => $redPhase->format('Y-m-d H:i'),
//						   'now is more than red ? =) ' => $now->greaterThan($redPhase)
//					   ]);
//			\Log::info(['new moded	' => $this->mode]);
			$this->turnOffLights($now);
		}

//		\Log::info([
//					   'cauri' => 'cauri',
//					   'taga' => $now->format('Y-m-d H:i'),
//					   'p3 date' => $redPhase->format('Y-m-d H:i')
//				   ]);
		return [
			'brightness' => $this->brightness,
			'color' => $this->color,
			'mode' => $this->googleClient->getMode(),
			'skipped' => $this->skipped,
		];
	}

}