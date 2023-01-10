<?php
/**
 * HTML Components for Richie Editions settings
 *
 * @link       https://www.richie.fi
 * @since 1.1.0
 * @package    Richie
 * @subpackage Richie/includes
 */

/**
 * HTML Components for Richie Editions settings
 *
 * @since 1.1.0
 * @package    Richie
 * @subpackage Richie/includes
 * @author     Markku Uusitupa <markku@richie.fi>
 */
class Richie_Editions_Admin_Components {

    /**
     * Render input field
     *
     * @param array $args  Rendering options.
     *
     * Rendering options (in args):
     *  string type: Input type.
     *  string value: Input value.
     *  string class: Input class name.
     *  string description: Description text after the element.
     *
     * @return void
     */
    public function input_field( array $args ) {
        $option_name = $args['option_name'];
        $id          = $args['id'];
        $type        = isset( $args['type'] ) ? $args['type'] : 'text';
        $name        = $option_name . '[' . $id . ']';
        $value       = isset( $args['value'] ) ? $args['value'] : '';
        $class_name  = isset( $args['class'] ) ? $args['class'] : 'regular-text';
        printf( '<input class="%s" type="%s" name="%s" value="%s">', esc_attr( $class_name ), esc_attr( $type ), esc_attr( $name ), esc_attr( $value ) );

        if ( isset( $args['description'] ) ) {
            printf( '<br><span class="description">%s</span>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Render checkbox
     *
     * @param array $args  Rendering options.
     *
     * Rendering options (in args):
     *  string value: Input value (defaults to 1).
     *  boolean checked: If true, checkbox is checked initially.
     *  string description: Description text after the element.
     *
     * @return void
     */
    public function checkbox( array $args ) {
        $id          = $args['id'];
        $option_name = $args['option_name'];
        $name        = $option_name . '[' . $id . ']';
        $checked     = isset( $args['checked'] ) && true === $args['checked'] ? 'checked' : '';
        $value       = isset( $args['value'] ) ? $args['value'] : '1';

        printf( '<input type="checkbox" name="%s" value="%s" %s>', esc_attr( $name ), esc_attr( $value ), esc_attr( $checked ) );

        if ( isset( $args['description'] ) ) {
            printf( '<span class="description">%s</span>', esc_html( $args['description'] ) );
        }
    }
}
