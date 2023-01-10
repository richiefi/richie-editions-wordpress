<?php
/**
 * Service for handling Richie Editions issues
 *
 * @link       https://www.richie.fi
 * @since      1.1.0
 * @package    Richie
 * @subpackage Richie/includes
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-richie-editions-issue.php';

/**
 * Uses WordPress transient for caching request
 *
 * Constructor takes url, minimum cache time and optionally maximum cache time as arguments.
 *
 * Cache time values are in seconds. If cached request is newer than minimum time, new request isn't done at all.
 * This can be used to limit poll interval for the remote url. Maximum value is set to transient, it automatically
 * removes the cache after the time is passed, forcing new request to be made.
 *
 * @since 1.0.0
 * @package    Richie
 * @subpackage Richie/includes
 * @author     Markku Uusitupa <markku@richie.fi>
 */
class Richie_Editions_Cached_Request {

    /**
     * Remote request url
     *
     * @since 1.0.0
     * @access private
     * @var string $url
     */
    private $url;

    /**
     * Key for caching response in transient cache
     *
     * @since 1.0.0
     * @access private
     * @var string $cache_key
     */
    private $cache_key;

    /**
     * Always return cached response during that time. In seconds.
     *
     * @since 1.0.0
     * @access private
     * @var int $minimum_cache_time
     */
    private $minimum_cache_time;

    /**
     * Invalidates cache after this time, forcing new request to be made.
     *
     * @since 1.0.0
     * @access private
     * @var int $maximum_cache_time
     */
    private $maximum_cache_time;


    /**
     * Create instance of Cached_Request
     *
     * @param string $url                   Remote url.
     * @param int    $minimum_cache_time    Minimum cache time.
     * @param int    $maximum_cache_time    Maximum cache time. Defaults to 0.
     *
     * @throws Exception Throws generic expection if url is missing.
     */
    public function __construct( $url, $minimum_cache_time, $maximum_cache_time = 0 ) {
        if ( empty( $url ) ) {
            throw new Exception( 'Missing url argument' );
        }
        $this->url                = esc_url_raw( $url );
        $this->cache_key          = md5( 'remote_request|' . $this->url );
        $this->minimum_cache_time = $minimum_cache_time;
        $this->maximum_cache_time = $maximum_cache_time;
    }

    /**
     * Get remote url response
     *
     * Return cached response if possible.
     *
     * @since 1.0.0
     * @access public
     *
     * @param boolean $force_refresh Force cache update from the server.
     * @return WP_Error|array The response or WP_Error on failure.
     */
    public function get_response( $force_refresh = false ) {
        if ( true === $force_refresh ) {
            // If requesting forced refresh, remove cached response.
            delete_transient( $this->cache_key );
        }

        $cache = get_transient( $this->cache_key );
        $headers = [];

        if ( false !== $cache ) {
            if ( $this->should_return_cache( $cache ) ) {
                // We have cached request and minimum_cache_time has not passed, return it.
                return $cache['response'];
            }

            $etag = $this->get_etag( $cache );
            if ( $etag ) {
                $headers['If-None-Match'] = $etag;
            } else {
                $headers['If-Modified-Since'] = date( 'D, d M Y H:i:s', $cache['timestamp'] );
            }
        }

        $response = wp_remote_get(
            $this->url,
            array(
                'headers' => $headers,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $cache['response']; // Return cached response if any.
        }

        $response_code = wp_remote_retrieve_response_code( $response );

        if ( 304 === $response_code ) {
            // Nothing changed, return cached response.
            $this->set_cached_request( $cache['response'], $this->maximum_cache_time );
            return $cache['response'];
        }

        if ( $response_code >= 200 && $response_code < 400 ) {
            // We have changed data, cache and return it.
            $this->set_cached_request( $response, $this->maximum_cache_time );
        } else {
            // Cache invalid responses for a short time.
            $this->set_cached_request( $response, 10 );
        }

        return $response;
    }

    /**
     * Check if cached response should be returned
     *
     * @since 1.0.0
     * @param array $cache Array from transient.
     * @return boolean
     */
    private function should_return_cache( $cache ) {
        if ( empty( $cache ) ) {
            return false;
        }

        $max_age = $this->get_max_age( $cache );

        if ( false === $max_age ) {
            $max_age = 0;
        }

        $cache_time = max( $max_age, $this->minimum_cache_time );
        if ( isset( $cache, $cache['timestamp'] ) && ( time() - $cache['timestamp'] <= $cache_time ) ) {
            return true;
        }

        return false;
    }

    /**
     * Get etag from the cached response
     *
     * @since 1.0.0
     * @param array $cache Array from transient.
     * @return string|boolean Returns etag or false if not found
     */
    private function get_etag( $cache ) {
        if ( isset( $cache, $cache['response'] ) ) {
            return wp_remote_retrieve_header( $cache['response'], 'etag' );
        }

        return false;
    }

    /**
     * Parse max-age from cache-control header value
     *
     * @param string $cache_control Cache-Control header.
     * @return int
     */
    private function parse_max_age( $cache_control ) {
        $max_age = explode( 'max-age=', $cache_control );
        if ( count( $max_age ) > 0 ) {
            $max_age = explode( ',', $max_age[1] );
            $max_age = trim( $max_age[0] );
            $max_age = intval( $max_age );
            return $max_age;
        }
        return false;
    }
    /**
     * Get max-age from the cached response
     *
     * @since 1.0.0
     * @param array $cache Array from transient.
     * @return int|boolean Returns max-age or false if not found
     */
    private function get_max_age( $cache ) {
        if ( isset( $cache, $cache['response'] ) ) {
            $cache_control = wp_remote_retrieve_header( $cache['response'], 'cache-control' );
            if ( isset( $cache_control ) ) {
                $max_age = $this->parse_max_age( $cache_control );
                return $max_age;
            }
        }

        return false;
    }

    /**
     * Cache response in transient
     *
     * @since 1.0.0
     * @param array $response   Response from wp_remote_get.
     * @param int   $cache_time Optional. Delete cache after given time in seconds.
     */
    private function set_cached_request( $response, $cache_time = 0 ) {
        $cache = array(
            'timestamp' => time(),
            'response'  => $response,
        );

        set_transient( $this->cache_key, $cache, $cache_time );
    }

    public function get_cache() {
        return get_transient( $this->cache_key );

    }
}
/**
 * Fetch issues by using cached request
 *
 * @since 1.0.0
 * @package    Richie
 * @subpackage Richie/includes
 * @author     Markku Uusitupa <markku@richie.fi>
 */
class Richie_Editions_Service {
    /**
     * Cached response for the requested url.
     *
     * @var Richie_Editions_Cached_Request
     */
    private $cached_request;

    /**
     * Create a new Richie Editions Service instance
     *
     * Fetches issue data from the editions host and manages cache.
     *
     * @param string $host_name Editions hostname.
     * @param string $index_path Path to the index json file, defaults to "all" version.
     */
    public function __construct( $host_name, $index_path = '/_data/index.json' ) {
        $minimum_cache_time   = MINUTE_IN_SECONDS;
        $maximum_cache_time   = 0; // No cache.
        $index_url            = $host_name . $index_path;
        $this->cached_request = new Richie_Editions_Cached_Request( $index_url, $minimum_cache_time, $maximum_cache_time );
    }

    /**
     * Refresh request cache using the force!
     *
     * @return void
     */
    public function refresh_cached_response( $force = false ) {
        $this->cached_request->get_response( $force );
    }

    public function get_cache() {
        return $this->cached_request->get_cache();
    }

    /**
     * Returns the response from the cached url.
     *
     * @return object
     */
    private function get_cached_response() {
        $response = $this->cached_request->get_response();

        if ( false === $response ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );

        $data = json_decode( $body );

        if ( empty( $data ) ) {
            return false;
        }

        return $data;
    }

    /**
     * Check if issue is free.
     *
     * @param string $issue_uuid Issue uuid.
     * @return boolean
     */
    public function is_issue_free( $issue_uuid ) {
        if ( empty( $issue_uuid ) || ! wp_is_uuid( $issue_uuid ) ) {
            return false;
        }

        $data = $this->get_cached_response();

        if ( false === $data ) {
            return false;
        }

        $found = false;
        foreach ( $data->issues as $org => $issues ) {
            foreach ( $issues as $issue ) {
                if ( $issue->uuid === $issue_uuid ) {
                    return $issue->isFree === true; // phpcs:ignore
                }
            }
        }

        return false;
    }

    /**
     * Get issues for the organization and product.
     *
     * @param string  $organization Editions organization.
     * @param string  $product Editions product.
     * @param integer $number_of_issues Number of wanted issues.
     * @return Richie_Editions_Issue[]
     */
    public function get_issues( $organization, $product, $number_of_issues = 0 ) {
        if ( empty( $organization ) || empty( $product ) ) {
            return false;
        }

        $data = $this->get_cached_response();

        if ( false === $data ) {
            return false;
        }

        $issues     = array();
        $product_id = $organization . '.magg.io/' . $product;

        if ( ! isset( $data->issues->{ $product_id } ) ) {
            return false;
        }
        $product_issues = $data->issues->{$product_id};

        if ( ! empty( $product_issues ) ) {

            if ( is_object( $product_issues ) ) {
                $issue = new Richie_Editions_Issue( $product, $product_issues );
                return array( $issue );
            }

            foreach ( $product_issues as $issue_data ) {
                $issue = new Richie_Editions_Issue( $product, $issue_data );
                array_push( $issues, $issue );
            }
        }

        usort(
            $issues,
            function( $a, $b ) {
                return $b->date - $a->date;
            }
        );

        if ( $number_of_issues > 0 ) {
            return array_slice( $issues, 0, $number_of_issues );
        }

        return $issues;
    }

}