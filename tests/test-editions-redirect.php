<?php
/**
 * Class RichieEditionsRedirectTest
 *
 * Tests Richie Editions signin redirection
 *
 * @package Richie
 */

/**
 * Richie Editions redirect tests.
 */
class RichieEditionsRedirectTest extends WP_UnitTestCase {
    protected $hostname = 'https://test.hostname';

	public function setUp(): void {
        parent::setUp();
        update_option(
            'richie-editions-wp',
            array(
                'editions_hostname' => $this->hostname,
                'editions_secret' => 'test-secret'
            )
        );
    }

    public function tearDown(): void {
        delete_option('richie-editions-wp');
        parent::tearDown();
    }

    public function test_redirection() {

        $editions_service = $this->getMockBuilder( Richie_Editions_Service::class )
        ->setConstructorArgs( [ $this->hostname ] )
        ->setMethods( [ 'is_issue_free' ] )
        ->getMock();

        // fake free issue
        $editions_service->method( 'is_issue_free' )
        ->willReturn( true );

        $richie_public = $this->getMockBuilder( Richie_Editions_Wp_Public::class )
        ->setConstructorArgs( array( 'richie-editions-wp', 'version' ) )
        ->setMethods( [ 'do_redirect', 'get_editions_service' ] )
        ->getMock();

        $richie_public->method( 'get_editions_service' )
        ->willReturn( $editions_service );

        $richie_public->expects( $this->once() )
            ->method( 'do_redirect' )
            ->with( $this->stringContains( 'https://test.hostname/_signin/' ) );

        // Ensure user has access so signin redirect is used
        add_filter( 'richie_editions_has_access', function () { return true; } );

        $uuid    = wp_generate_uuid4();
        $wp_mock = new StdClass();

        // Match plugin's expected query vars
        $wp_mock->query_vars = array(
            'richie_action' => 'richie_editions_redirect',
            'richie_issue'  => $uuid,
            'richie_prod'   => 'org/prod',
        );

        $richie_public->editions_redirect_request( $wp_mock );

    }

    public function test_redirection_on_error_external_referer() {
        $richie_public = $this->getMockBuilder( Richie_Editions_Wp_Public::class )
        ->setConstructorArgs( array( 'richie_editions_wp', 'version' ) )
        ->setMethods( [ 'do_redirect' ] )
        ->getMock();

        // fake referer.
        $referer = 'https://test.hostname/test/referrer';
        $_REQUEST['_wp_http_referer'] = $referer;

        $richie_public->expects( $this->once() )
            ->method( 'do_redirect' )
            ->with( get_home_url() ); // Should redirect to home url.

        add_filter(
            'allowed_redirect_hosts',
            function ( $content )  {
                $content[] = 'test.hostname';
                return $content;
            }
        );

        $richie_public->redirect_to_referer();

    }

    public function test_redirection_on_error_internal_referer() {
        $richie_public = $this->getMockBuilder( Richie_Editions_Wp_Public::class )
        ->setConstructorArgs( array( 'richie', 'version' ) )
        ->setMethods( [ 'do_redirect' ] )
        ->getMock();

        // fake referer.
        $referer = get_home_url() . '/test/referer';
        $_REQUEST['_wp_http_referer'] = $referer;

        $richie_public->expects( $this->once() )
            ->method( 'do_redirect' )
            ->with( $referer ); // Should redirect to referer.

        add_filter(
            'allowed_redirect_hosts',
            function ( $content )  {
                $content[] = 'test.hostname';
                return $content;
            }
        );

        $richie_public->redirect_to_referer();

    }

    public function test_redirection_with_params() {

        $editions_service = $this->getMockBuilder( Richie_Editions_Service::class )
        ->setConstructorArgs( [ $this->hostname ] )
        ->setMethods( [ 'is_issue_free' ] )
        ->getMock();

        // fake free issue
        $editions_service->method( 'is_issue_free' )
        ->willReturn( true );

        $richie_public = $this->getMockBuilder( Richie_Editions_Wp_Public::class )
        ->setConstructorArgs( array( 'richie-editions-wp', 'version' ) )
        ->setMethods( [ 'do_redirect', 'get_editions_service' ] )
        ->getMock();

        $richie_public->method( 'get_editions_service' )
        ->willReturn( $editions_service );

        $richie_public->expects( $this->once() )
            ->method( 'do_redirect' )
            ->with(
                $this->logicalAnd(
                    $this->stringContains( 'https://test.hostname/_signin/' ),
                    $this->stringContains( '&page=1' ),
                    $this->stringContains( '&q=search%20term' ),
                    $this->logicalNot( $this->stringContains( 'should_not_be_included' ) )
                )
            );

        // Ensure user has access so signin redirect is used
        add_filter( 'richie_editions_has_access', function () { return true; } );

        $uuid    = wp_generate_uuid4();
        $wp_mock = new StdClass();

        // Match plugin's expected query vars
        $wp_mock->query_vars = array(
            'richie_action' => 'richie_editions_redirect',
            'richie_issue'  => $uuid,
            'richie_prod'   => 'org/prod',
            'page'            => 1,
            'search'          => 'search%20term',
            'unknown'         => 'should_not_be_included',
        );

        $richie_public->editions_redirect_request( $wp_mock );

    }
}