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

    <hr>
    <h3><?php esc_html_e( 'Cache', 'richie-editions-wp' ); ?></h3>
    <?php if ( isset( $_GET['cache-cleared'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Editions cache cleared and refreshed.', 'richie-editions-wp' ); ?></p>
        </div>
    <?php endif; ?>
    <p><?php esc_html_e( 'Clear the cached editions index and fetch fresh data from the server.', 'richie-editions-wp' ); ?></p>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'richie_editions_clear_cache' ); ?>
        <input type="hidden" name="action" value="richie_editions_clear_cache">
        <?php submit_button( esc_html__( 'Clear Cache', 'richie-editions-wp' ), 'secondary', 'submit', false ); ?>
    </form>
</div>