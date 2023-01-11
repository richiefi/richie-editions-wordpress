<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.richie.fi
 * @since      1.0.0
 *
 * @package    Richie_Editions_Wp
 * @subpackage Richie_Editions_Wp/admin
 */

 require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-richie-editions-service.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Richie_Editions_Wp
 * @subpackage Richie_Editions_Wp/admin
 * @author     Richie OY <markku@richie.fi>
 */
class Richie_Editions_Wp_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
     * Option name for general settings
     *
     * @var string
     */
    private $settings_option_name;

    /**
     * Slug for settings page, used in /wp-admin/options-general.php?page=<slug>
     *
     * @var string
     */
    private $settings_page_slug;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->settings_option_name   = $plugin_name;
        $this->settings_page_slug     = $plugin_name;

        add_action( 'richie_editions_plugin_add_settings_sections', array( $this, 'generate_settings' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Richie_Editions_Wp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Richie_Editions_Wp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/richie-editions-wp-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Richie_Editions_Wp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Richie_Editions_Wp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/richie-editions-wp-admin.js', array( 'jquery' ), $this->version, false );

	}

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        /*
         * Add a settings page for this plugin to the Settings menu.
         *
         * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
         *
         *        Administration Menus: http://codex.wordpress.org/Administration_Menus
         *
         */
        add_options_page( 'Richie Editions Settings', 'Richie Editions', 'manage_options', $this->settings_page_slug, array( $this, 'load_admin_page_content' ) );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since 1.0.0
     * @param array $links Existing links array.
     */
    public function add_action_links( $links ) {
        /*
        *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
        */
        $settings_link = array(
            '<a href="' . admin_url( 'options-general.php?page=' . $this->settings_page_slug ) . '">' . __( 'Settings', 'richie-editions-wp' ) . '</a>',
        );
        return array_merge( $settings_link, $links );

    }

    /**
     * Load admin page
     *
     * @return void
     */
    public function load_admin_page_content() {
        require_once plugin_dir_path( __FILE__ ) . 'partials/richie-editions-admin-display.php';
    }

    /**
     * Validate general settings
     *
     * @param array $input Data from settings form.
     * @return array Validated values
     */
    public function validate_settings( $input ) {
        $valid = array();

        $valid['editions_secret']             = isset( $input['editions_secret'] ) ? sanitize_text_field( $input['editions_secret'] ) : '';
        $valid['editions_hostname']           = isset( $input['editions_hostname'] ) ? esc_url_raw( untrailingslashit( $input['editions_hostname'] ) ) : '';
        $valid['editions_index_range']        = isset( $input['editions_index_range'] ) ? sanitize_text_field( $input['editions_index_range'] ) : '';
        $valid['editions_error_url']          = isset( $input['editions_error_url'] ) ? esc_url( $input['editions_error_url'] ) : '';

        $options          = get_option( $this->settings_option_name );
        $current_hostname = isset( $options['editions_hostname'] ) ? $options['editions_hostname'] : '';
        $current_index    = isset( $options['editions_index_range'] ) ? $options['editions_index_range'] : '';

        if ( ! empty( $valid['editions_hostname'] ) && ( $current_hostname !== $valid['editions_hostname'] || $current_index !== $valid['editions_index_range'] ) ) {
            // Force cache refresh if hostname or index range changes.
            $ranges = $this->get_available_indexes( $valid['editions_hostname'] );
            if ( $input['editions_index_range'] === $current_index ) {
                // reset index range if hostname is changed and index range is not
                $valid['editions_index_range'] = $ranges[0]['value'];
            }

            $editions_service = new Richie_Editions_Service( $valid['editions_hostname'], $valid['editions_index_range'] );
            $editions_service->refresh_cached_response( true );
        }

        return $valid;
    }

    /**
     * Check if editions cache was updated recently.
     *
     * @param  int $threshold Optional. Compare age against threshold. Defaults to 5 seconds.
     *
     * @return boolean
     */
    public function editions_cache_updated( $threshold = 5 ) {
        $options         = get_option( $this->settings_option_name );
        $editions_hostname = isset( $options['editions_hostname'] ) ? $options['editions_hostname'] : '';
        $editions_index    = isset( $options['editions_index_range'] ) ? $options['editions_index_range'] : '';

        if ( ! empty( $editions_hostname ) ) {
            // Check if cache was updated recently.
            $editions_service = new Richie_Editions_Service( $editions_hostname, $editions_index );
            $cache          = $editions_service->get_cache();
            if ( false !== $cache ) {
                $timestamp = isset( $cache['timestamp'] ) ? intval( $cache['timestamp'] ) : 0;
                $age       = time() - $timestamp;
                if ( $age < $threshold ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Setup and register options using settings api
     *
     * @return void
     */
    public function options_update() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-richie-editions-settings-section.php';

        register_setting(
            $this->settings_option_name,
            $this->settings_option_name,
            array(
                'sanitize_callback' => array( $this, 'validate_settings' ),
            )
        );
    }


    /**
     * Generate settings groups and fields
     *
     * @return void
     */
    public function generate_settings() {
        $options = get_option( $this->settings_option_name );

        $editions_section_name  = 'richie_editions';

        // Create maggio section.
        $section = new Richie_Editions_Settings_Section( $editions_section_name, __( 'Richie Editions settings', 'richie-editions-wp' ), $this->settings_option_name );
        $section->add_field( 'editions_hostname', __( 'Richie Editions Web URL', 'richie-editions-wp' ), 'input_field', array( 'value' => $options['editions_hostname'], 'description' => __('Required. Richie Editions web url, including possible subtenant.', 'richie-editions-wp') ) );
        $section->add_field( 'editions_secret', __( 'Richie Editions secret', 'richie-editions-wp' ), 'input_field', array( 'value' => $options['editions_secret'], 'description' => __('Richie Editions secret key. This is required if using custom access control.', 'richie-editions-wp') ) );
        $section->add_field( 'editions_error_url', __( 'Richie Editions Web Error URL', 'richie-editions-wp' ), 'input_field', array( 'value' => $options['editions_error_url'], 'description' => __("Redirect url if user isn't authorized to open issue", 'richie-editions-wp' ) ) );


        // 'all' and 'latest' are available as default, other options can be updated.
        $available_indexes = $this->get_available_indexes();
        $selected = isset( $options['editions_index_range'] ) ? $options['editions_index_range'] : '/_data/index.json';
        $section->add_field( 'editions_index_range', __( 'Richie Editions index range', 'richie-editions-wp' ), 'select_field', array( 'options' => $available_indexes, 'selected' => $selected, 'description' => __('Select index to use. To get available options, save Editions Hostname setting first.', 'richie-editions-wp' ) ) );
    }

    private function get_translated_index_title( $title ) {
        $translations = array(
            'all'    => __( 'All', 'richie-editions-wp' ),
            'latest' => __( 'Latest', 'richie-editions-wp' ),
            '30 days' => __( '30 days', 'richie-editions-wp' ),
            '60 days' => __( '60 days', 'richie-editions-wp' ),
            '90 days' => __( '90 days', 'richie-editions-wp' ),
            '180 days' => __( '180 days', 'richie-editions-wp' ),
        );

        if ( isset( $translations[ $title ] ) ) {
            return $translations[ $title ];
        }
        return $title;
    }

    public function get_available_indexes( $baseurl = false ) {
        $options = get_option( $this->settings_option_name );

        $available_indexes = array();

        if ( ! empty( $baseurl ) ) {
            $host_url = $baseurl;
        } else {
            $host_url = isset( $options['editions_hostname'] ) ? $options['editions_hostname'] : false;
        }

        if ( $host_url ) {
            // We have hostname set, fetch available from the server.
            $url      = $host_url . '/_data/server_config.json';
            $response = wp_remote_get( $url );
            $body     = wp_remote_retrieve_body( $response );

            if ( ! empty( $body ) ) {
                $config = json_decode( $body, true );
                if ( JSON_ERROR_NONE === json_last_error() ) {
                    $indexes = $config['indexes'];

                    $available_indexes = array_map(
                        function( $index ) {
                            return array(
                                'title' => $this->get_translated_index_title( $index['range'] ),
                                'value' => $index['path'],
                            );
                        },
                        $indexes
                    );
                }
            }
        } else {
            // 'all' and 'latest' are available as default, other options can be updated.
            $available_indexes = array(
                array(
                    'title' => __('All', 'richie-editions-wp'),
                    'value' => '_data/index.json',
                ),
                array(
                    'title' => __('Latest', 'richie-editions-wp'),
                    'value' => '_data/latest.json',
                ),
            );
        }
        return $available_indexes;
    }

}
