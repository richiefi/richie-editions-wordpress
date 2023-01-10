<?php

require_once plugin_dir_path( __FILE__ ) . 'class-gamajo-template-loader.php';

class Richie_Editions_Template_Loader extends Gamajo_Template_Loader {
  /**
   * Prefix for filter names.
   *
   * @since 1.0.0
   *
   * @var string
   */
  protected $filter_prefix = 'richie-editions-wp';

  /**
   * Directory name where custom templates for this plugin should be found in the theme.
   *
   * @since 1.0.0
   *
   * @var string
   */
  protected $theme_template_directory = 'richie-editions';

  /**
   * Reference to the root directory path of this plugin.
   *
   * Can either be a defined constant, or a relative reference from where the subclass lives.
   *
   * In this case, `RICHIE_EDITIONS_PLUGIN_DIR` would be defined in the root plugin file as:
   *
   * ~~~
   * define( 'RICHIE_EDITIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
   * ~~~
   *
   * @since 1.0.0
   *
   * @var string
   */
  protected $plugin_directory = RICHIE_EDITIONS_PLUGIN_DIR;

  /**
   * Directory name where templates are found in this plugin.
   *
   * Can either be a defined constant, or a relative reference from where the subclass lives.
   *
   * e.g. 'templates' or 'includes/templates', etc.
   *
   * @since 1.1.0
   *
   * @var string
   */
  protected $plugin_template_directory = 'templates';

}