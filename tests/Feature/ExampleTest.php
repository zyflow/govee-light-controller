<?php

namespace Tests\Feature;

use App\Http\Clients\GoveeClient;
use App\Models\Govee;
use Carbon\Carbon;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
//    public function test_turn_on_for_sunset()
//    {
//		$govee = new Govee();
//		$sunsetAt = Carbon::parse('2024-01-01 15:00:00');
//		$client = new GoveeClient();
//		$currentTimeObj = Carbon::parse('2024-01-01 15:05:00');
//
//		$state = $govee->turnOnForSunset($sunsetAt, $client, $currentTimeObj);
//
//		$this->assertEquals(true, $state );
//    }

	public function test_turn_off()
    {
		$govee = new Govee();
		$currentTimeObj = Carbon::parse('2024-01-04 23:05:00');
		$state = $govee->checkIfLate($currentTimeObj);
		$this->assertEquals(false, $state );

		$currentTimeObj = Carbon::parse('2024-01-04 23:49:00');
		$state = $govee->checkIfLate($currentTimeObj);
		$this->assertEquals(false, $state );

		$currentTimeObj = Carbon::parse('2024-01-04 23:55:01');
		$state = $govee->checkIfLate($currentTimeObj);
		$this->assertEquals(true, $state );

		$currentTimeObj = Carbon::parse('2024-01-05 00:05:00');
		$state = $govee->checkIfLate($currentTimeObj);
		$this->assertEquals(false, $state );

		$currentTimeObj = Carbon::parse('2024-01-05 00:31:00');
		$state = $govee->checkIfLate($currentTimeObj);
		$this->assertEquals(true, $state );

		$currentTimeObj = Carbon::parse('2024-01-06 00:31:00');
		$state = $govee->checkIfLate($currentTimeObj);
		$this->assertEquals(true, $state );

		$currentTimeObj = Carbon::parse('2024-01-07 23:31:00');
		$state = $govee->checkIfLate($currentTimeObj);
		$this->assertEquals(false, $state );

		$currentTimeObj = Carbon::parse('2024-01-07 00:31:00');
		$state = $govee->checkIfLate($currentTimeObj);
		$this->assertEquals(false, $state );
    }


}
