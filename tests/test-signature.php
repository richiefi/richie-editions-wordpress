<?php
/**
 * Class SignatureTest
 *
 * Tests richie editions signin signature calculation
 *
 * @package Richie
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions.php';

/**
 * Signature tests.
 * Spec and tests found here: https://richie.dev/docs/editions/sso#issue-sign-ons
 */
class SignatureTest extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        $this->secret    = '4361583c-be39-4dee-aa1c-a4ebe7f5ceda';
        $this->timestamp = 1432301730;
    }
    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Returns error with invalid timestamp
     */

    public function test_returns_error_with_invalid_timestamp() {
        $hash = richie_editions_generate_signature_hash( $this->secret, 'de27f9d8-b020-43d7-99a6-15184d5d986f', 'invalid_timestamp' );
        $this->assertWPError( $hash );
        $this->assertEquals( $hash->errors['timestamp'][0], 'Invalid timestamp, it must be an integer' );
    }
    /**
     * Returns correct signature.
     */
    public function test_returns_correct_signature_no_query() {
        $hash = richie_editions_generate_signature_hash( $this->secret, 'de27f9d8-b020-43d7-99a6-15184d5d986f', $this->timestamp );
        $this->assertEquals( $hash, '584345aa710a7b5ef512aa1224872f127d81950a4fff896568019cde64d5fd18' );
    }

    /**
     * Returns correct signature, one parameter
     */
    public function test_returns_correct_signature_one_parameter() {
        $hash = richie_editions_generate_signature_hash( $this->secret, 'b46a037f-5e08-4edc-828f-35201caddd49', $this->timestamp, 'user=foobar' );
        $this->assertEquals( $hash, '927c8ba1b336ed4788a1a15637c8e481439d104c78a00230ce1d1c7ad13e0aac' );
    }
    /**
     * Returns correct signature, multiple parameters
     */
    public function test_returns_correct_signature_multiple_parameters() {
        $params = array(
            array(
				'key'   => 'user',
				'value' => 'foobar',
			),
            array(
				'key'   => 'allow',
				'value' => 'm2',
			),
            array(
				'key'   => 'allow',
				'value' => 'm1',
			),
        );
        $query  = richie_editions_build_query( $params );
        $hash   = richie_editions_generate_signature_hash( $this->secret, '1e6f3357-80cc-4f54-81dc-152cc300164e', $this->timestamp, $query );
        $this->assertEquals( $hash, 'fb9ed2e7e61c8abd5a680955d54f89753d9e7f1a3319694db9629e50e005306b' );
    }
}
