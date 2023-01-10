<?php
/**
 * Wrapper for WordPress settings api
 *
 * @link       https://www.richie.fi
 * @since 1.0.3
 * @package    Richie
 * @subpackage Richie/includes
 */

/**
 * Wrapper for WordPress settings api
 *
 * @since 1.0.3
 * @package    Richie
 * @subpackage Richie/includes
 * @author     Markku Uusitupa <markku@richie.fi>
 */
class Richie_Editions_Settings_Section {
    /**
     * Slug for the section
     *
     * @var string
     */
    private $section_slug;

    /**
     * Title for the section
     *
     * @var string
     */
    private $section_title;

    /**
     * Option name
     *
     * @var string
     */
    private $option_name;

    /**
     * Admin components
     *
     * @var Richie_Admin_Components
     */
    private $admin_components;

    /**
     * Create new section instance
     *
     * @param string $section_slug  Section slug.
     * @param string $section_title Section title.
     * @param string $option_name   Option name.
     *
     * @throws Exception Throws an general exepction if required arguments missing.
     */
    public function __construct( $section_slug, $section_title, $option_name ) {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-richie-admin-components.php';
        $this->admin_components = new Richie_Admin_Components();

        if ( empty( $section_slug ) || empty( $section_title ) || empty( $option_name ) ) {
            throw new Exception( 'Invalid arguments' );
        }

        $this->section_slug  = $section_slug;
        $this->section_title = $section_title;
        $this->option_name   = $option_name;

        add_settings_section( $section_slug, $section_title, null, $option_name );
    }

    /**
     * Add new field to the section. Uses settings api.
     *
     * @param string $id Field id.
     * @param string $title Field title.
     * @param string $component Component to render, see class-richie-adming-components.
     * @param array  $component_args Extra arguments passed to the component render function.
     * @return void
     */
    public function add_field( $id, $title, $component, $component_args = [] ) {
        $default_args = array(
            'option_name' => $this->option_name,
            'id'          => $id,
        );

        $args = array(
            $id,
            $title,
            array( $this->admin_components, $component ),
            $this->option_name,
            $this->section_slug,
            array_merge( $default_args, $component_args ),
        );

        call_user_func_array( 'add_settings_field', $args );
    }
}
