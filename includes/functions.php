<?php
/**
 * Richie helper functions
 *
 * @package richie
 * @subpackage richie/includes
 */

/**
 * Create rewrite rule for editions redirects
 *
 * Optionally flush rewrite rules (NOTE: that is an expensive operation, only do when absolute required)
 *
 * @since 1.0.0
 * @param boolean $flush Optional. Flushes rewrite rules if set. Default false.
 *
 * @return void
 */
function richie_editions_create_editions_rewrite_rules( $flush = false ) {
    add_rewrite_tag( '%richie_editions_redirect%', '([0-9a-fA-F-]+)' );
    add_rewrite_rule( '^richie-editions-redirect/([0-9a-fA-F-]+)/?$', 'index.php?richie_editions_redirect=$matches[1]', 'top' );

    if ( $flush ) {
        flush_rewrite_rules();
    }
}

/**
 * Sort array by key AND value
 *
 * Array is sorted by key first and if multiple equal keys, by value.
 * Note: sorting is done in place, modifying the given argument
 *
 * @since 1.0.0
 *
 * @param  array {
 *      @type string $key
 *      @type string $value
 * } $array Array to be sorted.
 *
 * @return array Sorted array
 */
function richie_editions_key_value_sort( $array ) {
    usort(
        $array,
        function( $a, $b ) {
            if ( $a['key'] === $b['key'] ) {
                return strcmp( $a['value'], $b['value'] );
            }
            return strcmp( $a['key'], $b['key'] );
        }
    );

    return $array;
}


/**
 * Build sorted query string
 *
 * @param  array {
 *      @type string $key
 *      @type string $value
 * } $params  key-value pairs
 *
 * @return string Query string key=value&key2=value2&... sorted by key and value
 */
function richie_editions_build_query( $params ) {
    $sorted = richie_key_value_sort( $params );
    $mapper = function( $param ) {
        return $param['key'] . '=' . $param['value'];
    };
    $pairs  = array_map( $mapper, $sorted );
    return implode( '&', $pairs );
}

/**
 * Generate editions authentication url signature hash
 *
 * @since 1.0.0
 *
 * @param  string $secret         Authentication secret.
 * @param  string $issue_id       Issue UUIDv4 id.
 * @param  int    $timestamp      Unix timestamp.
 * @param  string $query_string   Optional. Query string to be included in hash.
 *
 * @return string   $hash           Calculated signature hash to be included in signin url
 */
function richie_editions_generate_signature_hash( $secret, $issue_id, $timestamp, $query_string = '' ) {
    if ( ! isset( $secret ) ) {
        return new WP_Error( 'secret', __( 'Missing secret', 'richie-editions-wp' ) );
    }

    if ( ! wp_is_uuid( $issue_id ) ) {
        return new WP_Error( 'uuid', __( 'Invalid issue uuid', 'richie-editions-wp' ) );
    }

    if ( ! is_int( $timestamp ) ) {
        return new WP_Error( 'timestamp', __( 'Invalid timestamp, it must be an integer', 'richie-editions-wp' ) );
    }

    $signature_data = $issue_id . "\n" . $timestamp . "\n" . $query_string;
    $hash           = hash_hmac( 'sha256', $signature_data, $secret ); // Returns hex data.

    return $hash;
}


/**
 * Check if user has editions access
 *
 * Expects that current user is authenticated.
 *
 * @since 1.0.0
 *
 * @global $current_user
 *
 * @return boolean
 */
function richie_has_editions_access( ) {
    if ( ! is_user_logged_in() ) {
        return false;
    }
    return true;
}
