<?php

namespace App\Models;

use App\Http\Clients\GoogleCloudClient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sunset extends Model
{
	use HasFactory;

	private $googleCloudClient;

	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);
		$this->googleCloudClient = new GoogleCloudClient();
	}

	protected $fillable = [
		'sunset_at',
		'executed',
	];


	public static function getMinutesUntilSunset($now = false): string
	{
		$lastSunset = Sunset::orderBy('id', 'desc')->first();
		$time = Carbon::parse($lastSunset->sunset_at)->timezone('Europe/Riga');

		if (!$now) {
			$now = Carbon::now()->timezone('Europe/Riga');
		}

		$alteredDate = Carbon::parse($now)->setTime($time->hour, $time->minute, $time->second);

		if ($alteredDate->lt($now)) {
			$alteredDate->addDay();
		}

		return $alteredDate->shortAbsoluteDiffForHumans($now);
	}

	public static function getSunset()
	{
		if (env('APP_ENV') === 'testing') {
			return Sunset::first()->sunset_at;
		}

		$googleCloudClient = new GoogleCloudClient();
		return $googleCloudClient->getSunset();
	}

	public static function getExecuted()
	{
		if (env('APP_ENV') === 'testing') {
			return Sunset::first()->executed;
		}

		$googleCloudClient = new GoogleCloudClient();
		return $googleCloudClient->getExecuted();
	}

	public static function setTime($time) : void
	{
		if (env('APP_ENV') === 'testing') {
			$model = Sunset::first();
			$model->fill(['sunset_at' => $time])->save();
		}

		$googleCloudClient = new GoogleCloudClient();
		$googleCloudClient->setTime($time);
	}

	public static function setExecuted($value = "TRUE")
	{
		if (env('APP_ENV') === 'testing') {
			$model = Sunset::first();
			$model->fill(['executed' => $value])->save();
			return null;
		}

		$googleCloudClient = new GoogleCloudClient();
		return $googleCloudClient->setExecuted($value);
	}
}
