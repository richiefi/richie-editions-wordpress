<?php
/**
 * Class for Richie Editions issue data
 *
 * @link       https://www.richie.fi
 * @since      1.1.0
 * @package    Richie
 * @subpackage Richie/includes
 */

/**
 * Class for Richie Editions issue data
 *
 * @since      1.1.0
 * @package    Richie
 * @subpackage Richie/includes
 * @author     Markku Uusitupa <markku@richie.fi>
 */
class Richie_Editions_Issue {
    /**
     * Product slug
     *
     * @var string
     */
    public $product;
    /**
     * Issue title
     *
     * @var string
     */
    public $title;
    /**
     * Issue date (as unix timestamp)
     *
     * @var int
     */
    public $date;
    /**
     * Flag for free issue
     *
     * @var boolean
     */
    public $is_free;
    /**
     * Issue UUID
     *
     * @var string
     */
    public $uuid;

    /**
     * URL to issue master cover
     *
     * @var object
     */
    public $master_cover;

    /**
     * Create instance of Editions issue
     *
     * @param string $product        Product slug.
     * @param object  $issue_data    Issue data from the json.
     */
    public function __construct( $product, $issue_data ) {
        // WordPress coding standard requires snake case variables, but json has camelCase.
        // phpcs:disable
        $this->product      = $product;
        $this->title        = $issue_data->name;
        $this->date         = strtotime( $issue_data->publishedAt );
        $this->is_free      = $issue_data->isFree;
        $this->uuid         = $issue_data->uuid;
        $this->master_cover = $issue_data->covers->master;
        // phpcs:enable
    }

    /**
     * Get cover url for given size
     *
     * @param int $width    Width of the image.
     * @param int $height   Height of the image.
     * @return url
     */
    public function get_cover( $width, $height ) {
        $url = $this->master_cover->url . '?width=' . $width . '&height=' . $height;
        return $url;
    }

    /**
     * Get redirect url to the issue
     *
     * @return url
     */
    public function get_redirect_url() {
        return get_site_url( null, '/richie-editions-redirect/' . $this->product . '/' . $this->uuid, 'relative' );
    }
}
