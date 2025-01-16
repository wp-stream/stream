<?php
namespace WP_Stream;

class Test_Date_Interval extends WP_StreamTestCase {
	/**
	 * Holds the date interval base class
	 *
	 * @var Date_Interval
	 */
	protected $date_interval;

	public function setUp(): void {
		parent::setUp();

		$this->date_interval = new Date_Interval();
		$this->assertNotEmpty( $this->date_interval );
	}

	public function test_construct() {
		$this->assertNotEmpty( $this->date_interval->intervals );
	}

	public function test_get_predefined_intervals() {
		$intervals = $this->date_interval->get_predefined_intervals();

		$expected_intervals = array(
			'today',
			'yesterday',
			'last-7-days',
			'last-14-days',
			'last-30-days',
			'this-month',
			'last-month',
			'last-3-months',
			'last-6-months',
			'last-12-months',
			'this-year',
			'last-year',
		);

		foreach ( $expected_intervals as $expected_interval ) {
			$this->assertArrayHasKey( $expected_interval, $intervals );
		}

		foreach ( $intervals as $interval ) {
			$this->assertArrayHasKey( 'label', $interval );
			$this->assertArrayHasKey( 'start', $interval );
			$this->assertArrayHasKey( 'end', $interval );
		}
	}

	/**
	 * Test generate_date_intervals
	 *
	 * @return void
	 * @throws \Exception Emits Exception in case of an error.
	 */
	public function test_generate_date_intervals() {
		$timezone  = new \DateTimeZone( 'UTC' );
		$date      = new \DateTimeImmutable( '2024-07-18', $timezone );
		$intervals = $this->date_interval->generate_date_intervals( $date );

		$this->assertSame( '2024-07-18T00:00:00+00:00', $intervals['today']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-07-18T23:59:59+00:00', $intervals['today']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2024-07-17T00:00:00+00:00', $intervals['yesterday']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-07-17T23:59:59+00:00', $intervals['yesterday']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2024-07-11T00:00:00+00:00', $intervals['last-7-days']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-07-18T00:00:00+00:00', $intervals['last-7-days']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2024-07-04T00:00:00+00:00', $intervals['last-14-days']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-07-18T00:00:00+00:00', $intervals['last-14-days']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2024-06-18T00:00:00+00:00', $intervals['last-30-days']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-07-18T00:00:00+00:00', $intervals['last-30-days']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2024-07-01T00:00:00+00:00', $intervals['this-month']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-07-31T23:59:59+00:00', $intervals['this-month']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2024-06-01T00:00:00+00:00', $intervals['last-month']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-06-30T23:59:59+00:00', $intervals['last-month']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2024-04-18T00:00:00+00:00', $intervals['last-3-months']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-07-18T00:00:00+00:00', $intervals['last-3-months']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2024-01-18T00:00:00+00:00', $intervals['last-6-months']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-07-18T00:00:00+00:00', $intervals['last-6-months']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2023-07-18T00:00:00+00:00', $intervals['last-12-months']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-07-18T00:00:00+00:00', $intervals['last-12-months']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2024-01-01T00:00:00+00:00', $intervals['this-year']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2024-12-31T23:59:59+00:00', $intervals['this-year']['end']->format( DATE_ATOM ) );

		$this->assertSame( '2023-01-01T00:00:00+00:00', $intervals['last-year']['start']->format( DATE_ATOM ) );
		$this->assertSame( '2023-12-31T23:59:59+00:00', $intervals['last-year']['end']->format( DATE_ATOM ) );
	}
}
