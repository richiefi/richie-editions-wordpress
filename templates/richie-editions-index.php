<?php

/**
 * Creates Richie Editions issue list
 *
 *
 * @link       https://www.richie.fi
 * @since      1.0.0
 *
 * @package    Richie
 * @subpackage Richie/public/templates
 *
 *
 * Available variables:
 * $issues          : Array of issues
 * $user_has_access : true if user has access to the issues
 *
 * Single issue:
 * $issue->title                  : Title of the issue
 * $issue->is_free                : True if issue is free (not requiring authentication)
 * $issue->get_cover(width, size) : Request cover url with specific size
 * $issue->get_redirect_url()     : Returns url to redirection path which manages authentication and redirect
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<?php if ( ! $user_has_access && ( ! empty( $issues[0] && true !== $issues[0]->is_free ) ) ) : ?>
    <div style="text-align: center;">
        <strong>
            <?php esc_html_e( 'You need to be logged in and have an active subscription to access these issues', 'richie' ); ?>
        </strong>
    </div>
<?php endif; ?>
<div class="richie-editions-issue-container">
    <?php foreach( $issues as $issue ): ?>
        <div class="richie-editions-issue">
            <?php if( $user_has_access || $issue->is_free ): ?>
            <a href="<?php echo $issue->get_redirect_url(); ?>" target="_blank" rel="noopener" >
            <?php endif; ?>
                <figure class="richie-editions-issue-cover">
                        <img src="<?php echo $issue->get_cover(200,300); ?>">
                        <figcaption class="richie-editions-issue-title"><?php esc_html_e($issue->title, $this->plugin_name) ?></figcaption>
                </figure>
            <?php if( $user_has_access || $issue->is_free ): ?>
            </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>