<?php

defined( 'ABSPATH' ) or die( 'No direct access' );

class WFS {

	/**
	 * The instance variable
	 *
	 * @var $_instance
	 */
	private static $_instance = null;

	/**
	 * Get the singleton instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action('rest_api_init', function() {
			register_rest_route( 'wfs','/([A-Z0-9a-z_-]+)/', array(
					'methods' => WP_REST_Server::ALLMETHODS,
					'callback' => array( $this, 'handle_wfs_request' ),
				) 
			);
		} );
	}

	public function handle_wfs_request( $data ) {
		return ( print_r($data,true) );
	}

}
