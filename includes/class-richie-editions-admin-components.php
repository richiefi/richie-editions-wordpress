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

        /**
     * Render select box for given options
     *
     * @param array $args  Rendering options.
     *   string[] options Array of options.
     * @return void
     */
    public function select_field( array $args ) {
        $id          = $args['id'];
        $option_name = $args['option_name'];
        $name        = $option_name . '[' . $id . ']';
        $options     = $args['options'];
        $required    = isset( $args['required'] ) && true === $args['required'] ? 'required' : '';
        $selected    = isset( $args['selected'] ) ? $args['selected'] : null;

        ?>
        <select name='<?php echo esc_attr( $name ); ?>' id='<?php echo esc_attr( $id ); ?>' <?php echo esc_attr( $required ); ?>>
            <?php foreach ( $options as $opt ) : ?>
                <?php
                if ( isset( $opt['value'] ) ) {
                    $value = $opt['value'];
                    $title = isset( $opt['title'] ) ? $opt['title'] : $opt['value'];
                } else {
                    $value = $opt;
                    $title = $opt;
                }
                ?>
                <option value='<?php echo esc_attr( $value ); ?>' <?php selected( $selected, $value, true ); ?>><?php echo esc_attr( $title ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
        if ( isset( $args['description'] ) ) {
            printf( '<div><span class="description">%s</span></div>', esc_html( $args['description'] ) );
        }
    }
}
