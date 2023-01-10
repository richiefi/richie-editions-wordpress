<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://www.richie.fi
 * @since      1.0.0
 *
 * @package    Richie_Editions_Wp
 * @subpackage Richie_Editions_Wp/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Richie_Editions_Wp
 * @subpackage Richie_Editions_Wp/includes
 * @author     Richie OY <markku@richie.fi>
 */
class Richie_Editions_Wp_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        flush_rewrite_rules();
        $timestamp = wp_next_scheduled( 'richie_editions_cron_hook' );
        wp_unschedule_event( $timestamp, 'richie_editions_cron_hook' );
	}

}
