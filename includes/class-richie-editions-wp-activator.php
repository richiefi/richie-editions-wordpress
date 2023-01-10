<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.richie.fi
 * @since      1.0.0
 *
 * @package    Richie_Editions_Wp
 * @subpackage Richie_Editions_Wp/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Richie_Editions_Wp
 * @subpackage Richie_Editions_Wp/includes
 * @author     Richie OY <markku@richie.fi>
 */
class Richie_Editions_Wp_Activator {

	/**
	 * Activate rewrite rules
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions.php';
        richie_editions_create_editions_rewrite_rules( true );
	}

}
