<?php
/**
 * Class CachedRequestTest
 *
 * Tests for Richie_Editions_Cached_Request caching logic.
 *
 * @package Richie_Editions_Wp
 */

/**
 * Cached request tests.
 *
 * Uses the `pre_http_request` filter to mock wp_remote_get responses
 * without making actual HTTP requests.
 */
class CachedRequestTest extends WP_UnitTestCase {

	/**
	 * Test URL used for all cached request instances.
	 *
	 * @var string
	 */
	private $test_url = 'https://example.com/_data/index.json';

	/**
	 * Tracks the request headers sent by wp_remote_get.
	 *
	 * @var array
	 */
	private $captured_request_headers = array();

	/**
	 * Counter for how many HTTP requests were made.
	 *
	 * @var int
	 */
	private $request_count = 0;

	public function setUp(): void {
		parent::setUp();
		$this->captured_request_headers = array();
		$this->request_count            = 0;
		$this->clear_cache();
	}

	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		$this->clear_cache();
		parent::tearDown();
	}

	/**
	 * Clear the transient cache for the test URL.
	 */
	private function clear_cache() {
		$cache_key = md5( 'remote_request|' . $this->test_url );
		delete_transient( $cache_key );
	}

	/**
	 * Age the cache timestamp so it's beyond the minimum_cache_time.
	 *
	 * When minimum_cache_time=0, should_return_cache uses `<=` so a cache
	 * stored in the same second would still be "fresh". This helper pushes
	 * the timestamp back so the cache is considered stale.
	 *
	 * @param int $seconds_ago How far back to set the timestamp. Defaults to 2.
	 */
	private function age_cache( $seconds_ago = 2 ) {
		$cache_key = md5( 'remote_request|' . $this->test_url );
		$cache     = get_transient( $cache_key );
		if ( false !== $cache ) {
			$cache['timestamp'] = time() - $seconds_ago;
			set_transient( $cache_key, $cache, 3600 );
		}
	}

	/**
	 * Build a mock wp_remote_get response array.
	 *
	 * @param string $body            Response body.
	 * @param int    $status_code     HTTP status code.
	 * @param array  $headers         Response headers.
	 * @return array
	 */
	private function make_response( $body = '{"issues":{}}', $status_code = 200, $headers = array() ) {
		return array(
			'headers'  => new WpOrg\Requests\Utility\CaseInsensitiveDictionary( $headers ),
			'body'     => $body,
			'response' => array(
				'code'    => $status_code,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Register a pre_http_request filter that returns a fixed response and captures request headers.
	 *
	 * @param array $response Mock response to return.
	 */
	private function mock_http_response( $response ) {
		add_filter( 'pre_http_request', function ( $preempt, $parsed_args ) use ( $response ) {
			$this->captured_request_headers = $parsed_args['headers'];
			$this->request_count++;
			return $response;
		}, 10, 2 );
	}

	/**
	 * Register a pre_http_request filter that calls a callback for each request.
	 *
	 * @param callable $callback Function receiving ($parsed_args) and returning a response array.
	 */
	private function mock_http_callback( $callback ) {
		add_filter( 'pre_http_request', function ( $preempt, $parsed_args ) use ( $callback ) {
			$this->captured_request_headers = $parsed_args['headers'];
			$this->request_count++;
			return $callback( $parsed_args );
		}, 10, 2 );
	}

	// ─── Fresh request tests ───────────────────────────────────────────

	public function test_first_request_fetches_from_server() {
		$expected_body = '{"issues":{"org.magg.io/prod":[]}}';
		$this->mock_http_response( $this->make_response( $expected_body ) );

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 60, 3600 );
		$response       = $cached_request->get_response();

		$this->assertEquals( 1, $this->request_count );
		$this->assertEquals( $expected_body, wp_remote_retrieve_body( $response ) );
	}

	public function test_first_request_sends_no_conditional_headers() {
		$this->mock_http_response( $this->make_response() );

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 60, 3600 );
		$cached_request->get_response();

		$this->assertArrayNotHasKey( 'If-None-Match', $this->captured_request_headers );
		$this->assertArrayNotHasKey( 'If-Modified-Since', $this->captured_request_headers );
	}

	// ─── Cache within minimum_cache_time ────────────────────────────────

	public function test_returns_cache_within_minimum_cache_time() {
		$this->mock_http_response( $this->make_response( '{"first":"response"}' ) );

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 60, 3600 );
		$cached_request->get_response();

		$this->assertEquals( 1, $this->request_count );

		// Second call within minimum_cache_time should not make a request.
		$response = $cached_request->get_response();

		$this->assertEquals( 1, $this->request_count );
		$this->assertEquals( '{"first":"response"}', wp_remote_retrieve_body( $response ) );
	}

	// ─── ETag conditional request ──────────────────────────────────────

	public function test_sends_if_none_match_when_etag_present() {
		$call_count = 0;
		$this->mock_http_callback( function ( $parsed_args ) use ( &$call_count ) {
			$call_count++;
			if ( 1 === $call_count ) {
				return $this->make_response( '{"data":"v1"}', 200, array( 'etag' => '"abc123"' ) );
			}
			return $this->make_response( '', 304 );
		} );

		// minimum_cache_time = 0 so cache is always stale and we re-validate.
		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 0, 3600 );
		$cached_request->get_response();
		$this->age_cache();
		$cached_request->get_response();

		$this->assertEquals( 2, $this->request_count );
		$this->assertEquals( '"abc123"', $this->captured_request_headers['If-None-Match'] );
	}

	public function test_no_conditional_header_when_no_etag() {
		$call_count = 0;
		$this->mock_http_callback( function ( $parsed_args ) use ( &$call_count ) {
			$call_count++;
			return $this->make_response( '{"data":"v' . $call_count . '"}', 200 );
		} );

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 0, 3600 );
		$cached_request->get_response();
		$this->age_cache();
		$cached_request->get_response();

		$this->assertEquals( 2, $this->request_count );
		$this->assertArrayNotHasKey( 'If-None-Match', $this->captured_request_headers );
		$this->assertArrayNotHasKey( 'If-Modified-Since', $this->captured_request_headers );
	}

	// ─── 304 Not Modified handling ──────────────────────────────────────

	public function test_304_returns_cached_response() {
		$original_body = '{"issues":{"org.magg.io/prod":[{"uuid":"abc"}]}}';
		$call_count    = 0;
		$this->mock_http_callback( function ( $parsed_args ) use ( &$call_count, $original_body ) {
			$call_count++;
			if ( 1 === $call_count ) {
				return $this->make_response( $original_body, 200, array( 'etag' => '"v1"' ) );
			}
			return $this->make_response( '', 304 );
		} );

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 0, 3600 );
		$cached_request->get_response();
		$this->age_cache();
		$response = $cached_request->get_response();

		$this->assertEquals( $original_body, wp_remote_retrieve_body( $response ) );
	}

	public function test_304_preserves_original_timestamp() {
		$original_time = time() - 300; // 5 minutes ago.
		$call_count    = 0;

		$this->mock_http_callback( function ( $parsed_args ) use ( &$call_count ) {
			$call_count++;
			if ( 1 === $call_count ) {
				return $this->make_response( '{"data":"v1"}', 200, array( 'etag' => '"v1"' ) );
			}
			return $this->make_response( '', 304 );
		} );

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 0, 3600 );
		$cached_request->get_response();

		// Manually set an older timestamp in the cache to simulate passage of time.
		$cache_key = md5( 'remote_request|' . $this->test_url );
		$cache     = get_transient( $cache_key );
		$cache['timestamp'] = $original_time;
		set_transient( $cache_key, $cache, 3600 );

		// Trigger a 304 response.
		$cached_request->get_response();

		// Verify timestamp was preserved, not reset to time().
		$cache_after = get_transient( $cache_key );
		$this->assertEquals( $original_time, $cache_after['timestamp'] );
	}

	// ─── Force refresh ──────────────────────────────────────────────────

	public function test_force_refresh_bypasses_cache() {
		$call_count = 0;
		$this->mock_http_callback( function ( $parsed_args ) use ( &$call_count ) {
			$call_count++;
			return $this->make_response( '{"version":' . $call_count . '}', 200 );
		} );

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 3600, 7200 );
		$cached_request->get_response();
		$response = $cached_request->get_response( true );

		$this->assertEquals( 2, $this->request_count );
		$this->assertEquals( '{"version":2}', wp_remote_retrieve_body( $response ) );
	}

	public function test_force_refresh_sends_no_conditional_headers() {
		$this->mock_http_response(
			$this->make_response( '{"data":"v1"}', 200, array( 'etag' => '"old"' ) )
		);

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 3600, 7200 );
		$cached_request->get_response();

		// Replace mock for second call.
		remove_all_filters( 'pre_http_request' );
		$this->mock_http_response( $this->make_response( '{"data":"v2"}', 200 ) );

		$cached_request->get_response( true );

		// Force refresh deletes transient first, so no conditional headers should be sent.
		$this->assertArrayNotHasKey( 'If-None-Match', $this->captured_request_headers );
		$this->assertArrayNotHasKey( 'If-Modified-Since', $this->captured_request_headers );
	}

	// ─── Error handling ─────────────────────────────────────────────────

	public function test_wp_error_returns_cached_response_if_available() {
		$call_count = 0;
		$this->mock_http_callback( function ( $parsed_args ) use ( &$call_count ) {
			$call_count++;
			if ( 1 === $call_count ) {
				return $this->make_response( '{"cached":"data"}', 200 );
			}
			return new WP_Error( 'http_request_failed', 'Connection timed out' );
		} );

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 0, 3600 );
		$cached_request->get_response();
		$this->age_cache();
		$response = $cached_request->get_response();

		$this->assertNotWPError( $response );
		$this->assertEquals( '{"cached":"data"}', wp_remote_retrieve_body( $response ) );
	}

	public function test_wp_error_returns_error_when_no_cache() {
		$this->mock_http_callback( function ( $parsed_args ) {
			return new WP_Error( 'http_request_failed', 'DNS resolution failed' );
		} );

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 0, 3600 );
		$response       = $cached_request->get_response();

		$this->assertWPError( $response );
	}

	public function test_server_error_cached_briefly() {
		$call_count = 0;
		$this->mock_http_callback( function ( $parsed_args ) use ( &$call_count ) {
			$call_count++;
			if ( 1 === $call_count ) {
				return $this->make_response( 'Internal Server Error', 500 );
			}
			return $this->make_response( '{"data":"recovered"}', 200 );
		} );

		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 0, 3600 );
		$response       = $cached_request->get_response();

		$this->assertEquals( 500, wp_remote_retrieve_response_code( $response ) );

		// The 500 should be cached briefly (10s), so an immediate second call returns cached 500.
		$response2 = $cached_request->get_response();
		$this->assertEquals( 1, $this->request_count ); // No second request — cached.
		$this->assertEquals( 500, wp_remote_retrieve_response_code( $response2 ) );
	}

	// ─── max-age / Cache-Control handling ───────────────────────────────

	public function test_max_age_extends_cache_time() {
		$call_count = 0;
		$this->mock_http_callback( function ( $parsed_args ) use ( &$call_count ) {
			$call_count++;
			return $this->make_response(
				'{"data":"v' . $call_count . '"}',
				200,
				array( 'cache-control' => 'max-age=300' )
			);
		} );

		// minimum_cache_time=0, but server says max-age=300.
		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 0, 3600 );
		$cached_request->get_response();

		// Within max-age, should return cache without a new request.
		$cached_request->get_response();
		$this->assertEquals( 1, $this->request_count );
	}

	public function test_max_age_capped_by_maximum_cache_time() {
		$this->mock_http_response(
			$this->make_response( '{"data":"v1"}', 200, array( 'cache-control' => 'max-age=86400' ) )
		);

		// Server says 24h, but maximum_cache_time is 120s.
		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 0, 120 );
		$cached_request->get_response();

		// Simulate time passing beyond maximum_cache_time.
		$cache_key = md5( 'remote_request|' . $this->test_url );
		$cache     = get_transient( $cache_key );
		$cache['timestamp'] = time() - 130; // 130s ago, past 120s max.
		set_transient( $cache_key, $cache, 120 );

		remove_all_filters( 'pre_http_request' );
		$this->mock_http_response( $this->make_response( '{"data":"v2"}', 200 ) );

		$response = $cached_request->get_response();
		$this->assertEquals( 2, $this->request_count );
		$this->assertEquals( '{"data":"v2"}', wp_remote_retrieve_body( $response ) );
	}

	public function test_no_max_age_in_cache_control_header() {
		$this->mock_http_response(
			$this->make_response( '{"data":"v1"}', 200, array( 'cache-control' => 'no-cache, no-store' ) )
		);

		// minimum_cache_time=0, no max-age in header.
		$cached_request = new Richie_Editions_Cached_Request( $this->test_url, 0, 3600 );
		$cached_request->get_response();
		$this->age_cache();

		remove_all_filters( 'pre_http_request' );
		$this->mock_http_response( $this->make_response( '{"data":"v2"}', 200 ) );

		// Should re-fetch because no max-age and minimum_cache_time=0.
		$cached_request->get_response();
		$this->assertEquals( 2, $this->request_count );
	}

	// ─── Constructor validation ─────────────────────────────────────────

	public function test_constructor_throws_on_empty_url() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Missing url argument' );
		new Richie_Editions_Cached_Request( '', 60 );
	}
}
