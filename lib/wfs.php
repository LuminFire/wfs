<?php

defined( 'ABSPATH' ) or die( 'No direct access' );

class WFS {

	private $limit;

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

		$this->limit = 10;

		add_action('rest_api_init', function() {
			register_rest_route( 'wfs','/([A-Z0-9a-z_-]+)/', array(
					'methods' => WP_REST_Server::ALLMETHODS,
					'callback' => array( $this, 'handle_wfs_request' ),
				) 
			);
		} );
	}

	public function handle_wfs_request( $data ) {
		// http://geopro.dev/wp-json/wfs/walkin/?service=wfs&version=2.0.0&request=GetFeature&typeNames=walkin:geom
		$params = $data->get_params();
		$get = array_change_key_case( $params );

		switch ( $get['request'] ) {
			case 'GetFeature':
				$this->getFeature( $data, $get );
				break;
		}
	}

	public function getFeature( $data, $get ) {
		/*
			Looks like these are all the WFS parameters, maybe?

			ALIASES
			BBOX
			COUNT
			FILTER
			FILTER_LANGUAGE
			NAMESPACES
			OUTPUTFORMAT
			RESOLVE
			RESOLVEDEPTH
			RESOLVETIMEOUT
			RESOURCEID
			RESULTTYPE
			SORTBY
			SRSNAME
			STARTINDEX
			STOREDQUERY_ID
			TYPENAMES
			VSP
		*/	

		$namespace = str_replace( '/wfs/', '', $data->get_route());
		$featureType = '';
	
		if ( !empty( $get[ 'typenames' ] ) ) {

			$nameParts = explode( ':', $get[ 'typenames' ] );

			if ( 2 === count( $nameParts ) ) {
				$namespace = $nameParts[0];
				$featureType = $nameParts[1];
			} else {
				$featureType = $get[ 'typenames' ];
			}
		} else if ( !empty( $get[ 'resourceid' ] ) ) {
			die( "Do something with resourceId" );
		} else {
			return;
		}

		$res = array(
			'posts_per_page' => $this->limit,
			'post_type' => $namespace,
			'meta_query' => array(
				array(
					'key' => $featureType,
					'compare' => 'EXISTS'
					)
				),
		);

		$query = new WP_Query( $res );

		$geojson = array(
			'type' => 'FeatureCollection',
			'features' => array()
			);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$a = 1;
				$query->the_post();
				$postID = get_the_ID();
				$meta = get_post_meta( $postID );

				$geom_field = $meta[ $featureType ][ 0 ];
				unset( $meta[ $featureType ] );

				$geom = WP_GeoUtil::metaval_to_geom( $geom_field );

				$geojson_chunk = array(
					'type' => 'Feature',
					'geometry' => json_decode( WP_GeoUtil::geom_to_geojson( $geom ) ),
					'properties' => array(),
					);

				foreach( $meta as $k => $v ) {
					$geojson_chunk['properties'][$k] = $v;
				}

				$geojson['features'][] = $geojson_chunk;
			}
		}

		$this->send_search_result( $geojson );
	}

	public function send_search_result( $json, $format = 'json' ) {
		print json_encode( $json );
		exit();
	}
}
