<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.richie.fi
 * @since      1.0.0
 *
 * @package    Richie_Editions_Wp
 * @subpackage Richie_Editions_Wp/public
 */

 require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-richie-editions-service.php';
 require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-richie-editions-template-loader.php';

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Richie_Editions_Wp
 * @subpackage Richie_Editions_Wp/public
 * @author     Richie OY <markku@richie.fi>
 */
class Richie_Editions_Wp_Public {

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
     * Richie options
     *
     * @since   1.0.0
     * @access  private
     * @var     array      $Richie_options
     */
    private $richie_options;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version  = $version;
        $this->richie_options = get_option( $plugin_name );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/richie-editions-wp-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/richie-editions-wp-public.js', array( 'jquery' ), $this->version, false );

	}

    /**
     * Return editions service instance or false if error
     *
     * @return Richie_Editions_Service | boolean
     */
    public function get_editions_service() {
        $host_name      = $this->richie_options['editions_hostname'];
        $selected_index = isset( $this->richie_options['editions_index_range'] ) ? $this->richie_options['editions_index_range'] : null;

        try {
            $editions_service = new Richie_Editions_Service( $host_name, $selected_index );
            return $editions_service;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Load editions display content
     *
     * @param array $attributes Shortcode attributes.
     */
    public function load_editions_index_content( $attributes ) {
        if ( ! isset( $this->richie_options['editions_hostname'] ) || empty( $this->richie_options['editions_hostname'] ) ) {
            return sprintf( '<div>%s</div>', esc_html__( 'Invalid configuration, missing hostname in settings', 'richie-editions-wp' ) );
        }

        $atts = shortcode_atts(
            array(
                'product'          => null,
                'organization'     => isset( $this->richie_options['editions_organization'] ) ? $this->richie_options['editions_organization'] : null,
                'number_of_issues' => null,
            ),
            $attributes,
            'richie_editions'
        );

        if ( empty( $atts['product'] ) ) {
            return sprintf( '<div>%s</div>', esc_html__( '"product" attribute is required', 'richie-editions-wp' ) );
        }

        if ( empty( $atts['organization'] ) ) {
            return sprintf( '<div>%s</div>', esc_html__( 'Invalid organization', 'richie-editions-wp' ) );
        }

        $organization   = $atts['organization'];
        $product        = $atts['product'];

        $editions_service = $this->get_editions_service();

        if ( false === $editions_service ) {
            return sprintf( '<div>%s</div>', esc_html__( 'Failed to fetch issues', 'richie-editions-wp' ) );
        }

        $issues               = $editions_service->get_issues( $organization, $product, intval( $atts['number_of_issues'] ) );
        $user_has_access      = richie_has_editions_access( );

        if ( false === $issues ) {
            return sprintf( '<div>%s</div>', esc_html__( 'Failed to fetch issues', 'richie-editions-wp' ) );
        }

        $richie_template_loader = new Richie_Editions_Template_Loader();

        $template = $richie_template_loader->locate_template( 'richie-editions-index.php', false, false );
        ob_start();
        include $template;

        return ob_get_clean();
    }

    /**
     * Register short code for displaying editions index page
     */
    public function register_shortcodes() {
        add_shortcode( 'richie_editions', array( $this, 'load_editions_index_content' ) );
    }

    /**
     * Create redirection for editions issues.
     */
    public function register_redirect_route() {
        richie_editions_create_editions_rewrite_rules();
        add_action( 'parse_request', array( $this, 'editions_redirect_request' ) );

        $host_name = isset( $this->richie_options['editions_hostname'] ) ? $this->richie_options['editions_hostname'] : false;

        if ( ! empty( $host_name ) ) {
            add_filter(
                'allowed_redirect_hosts',
                function ( $content ) use ( &$host_name ) {
                    $content[] = wp_parse_url( $host_name, PHP_URL_HOST );
                    return $content;
                }
            );
        }
    }

    /**
     * Redirects to the referer (or home if referer not found).
     * Only internal referers allowed.
     * Exits after redirection, to prevent code execution after that.
     */
    public function redirect_to_referer() {
        $allow_referer = false;

        if ( wp_get_referer() ) {
            $wp_host = wp_parse_url( get_home_url(), PHP_URL_HOST );
            $referer_host = wp_parse_url( wp_get_referer(), PHP_URL_HOST );
            $allow_referer = $wp_host === $referer_host;
        }

        if ( $allow_referer ) {
            $this->do_redirect( wp_get_referer() );
        } else {
            $this->do_redirect( get_home_url() );
        }
    }

    /**
     * Create redirection to editions signin service.
     * Exits process after redirection.
     *
     * @param WP $wp WordPress instance variable.
     */
    public function editions_redirect_request( $wp ) {
        if (
            ! empty( $wp->query_vars['richie_editions_redirect'] ) &&
            wp_is_uuid( $wp->query_vars['richie_editions_redirect'] )
        ) {
            if (
                ! isset( $this->richie_options['editions_secret'] ) ||
                ! isset( $this->richie_options['editions_hostname'] )
            ) {
                // invalid configuration.
                $this->redirect_to_referer();
            }

            $editions_service = $this->get_editions_service();

            if ( false === $editions_service ) {
                return sprintf( '<div>%s</div>', esc_html__( 'Failed to fetch issues', 'richie-editions-wp' ) );
            }

            $hostname      = $this->richie_options['editions_hostname'];
            $uuid          = $wp->query_vars['richie_editions_redirect'];
            $is_free_issue = $editions_service->is_issue_free( $uuid );

            if ( ! $is_free_issue && ! richie_has_editions_access() ) {
                $this->redirect_to_referer();
            }

            // has access, continue redirect.
            $timestamp = time();

            $secret = $this->richie_options['editions_secret'];

            $return_link = wp_get_referer() ? wp_get_referer() : get_home_url();

            $auth_params = array(
                array( 'key' => 'return_link', 'value' => $return_link )
            );

            $query_string = richie_editions_build_query( $auth_params );

            $hash = richie_editions_generate_signature_hash( $secret, $uuid, $timestamp, $query_string );

            // Pass extra query params to signin route.
            if ( ! empty( $wp->query_vars['page'] ) ) {
                $query_string = $query_string . '&page=' . $wp->query_vars['page'];
            }

            if ( ! empty( $wp->query_vars['search'] ) ) {
                // Support for search term, if needed in the future.
                $query_string = $query_string . '&q=' . $wp->query_vars['search'];
            }

            $url = "{$hostname}/_signin/${uuid}/${timestamp}/${hash}" . '?' . $query_string;
            $this->do_redirect( $url );
        }
    }

    /**
     * Make safe direct to the url. Exit process after redirection.
     *
     * @param string $url Redirection target. Must be in allowed urls.
     */
    protected function do_redirect( $url ) {
        if ( ! empty( $url ) ) {
            wp_safe_redirect( esc_url_raw( $url ) );
        }

        exit();
    }

    /**
     * Refresh editions cache.
     *
     * @return void
     */
    public function refresh_editions_cache() {
        $editions_service = $this->get_editions_service();

        if ( false !== $editions_service ) {
            $editions_service->refresh_cached_response();
        }
    }

}