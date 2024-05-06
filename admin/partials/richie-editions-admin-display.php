<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.richie.fi
 * @since      1.0.0
 *
 * @package    Richie_Editions_Wp
 * @subpackage Richie_Editions_Wp/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<?php
do_action( 'richie_editions_plugin_add_settings_sections' );
$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'settings';
?>

<div class="wrap richie-settings">
    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>

    <form method="post" name="richie-options" action="options.php">

        <?php
            settings_fields($this->settings_option_name);
            do_settings_sections($this->settings_option_name);
        ?>
        <p>
            <?php
                _e('Error urls value supports placeholder tags: <code>%%issue%%</code> and <code>%%product%%</code>, which are replaced with actual values. For example <code>https://example.com?i=%%issue%%&p=%%product%%</code>.', 'richie-editions-wp')
            ?>
        </p>

        <?php submit_button(esc_html__('Save all changes', 'richie-editions-wp'), 'primary','submit', TRUE); ?>
    </form>
</div>