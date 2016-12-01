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

		$this->limit = 1000;

		add_action('rest_api_init', function() {
			register_rest_route( 'wfs','/([A-Z0-9a-z_-]+)/', array(
					'methods' => WP_REST_Server::ALLMETHODS,
					'callback' => array( $this, 'handle_wfs_request' ),
				) 
			);

			// Handle static XML schema requests
			register_rest_route( 'wfs','/schemas/.*', array(
					'methods' => WP_REST_Server::ALLMETHODS,
					'callback' => array( $this, 'handle_schema_request' ),
				) 
			);
		} );
	}

	public function handle_schema_request( $data ) {
		$route = preg_replace( '|^/wfs|', '', $data->get_route() );

		$file_on_disk = dirname( __FILE__ ) . '/..' . $route;

		if ( file_exists( $file_on_disk ) ) {
			header( 'Content-type: application/xml' );
			readfile( $file_on_disk );
			exit();
		}
	}

	public function handle_wfs_request( $data ) {
		// http://geopro.dev/wp-json/wfs/walkin/?service=wfs&version=2.0.0&request=GetFeature&typeNames=walkin:geom
		$params = $data->get_params();
		$get = array_change_key_case( $params );

		switch ( $get['request'] ) {
			case 'GetFeature':
				$this->get_feature( $data, $get );
				break;
			case 'DescribeFeatureType':
				$this->describe_feature_type( $data, $get );
				break;
		}
	}

	public function get_feature( $data, $get ) {
		/*
			Looks like these are all the WFS parameters, maybe?

			ALIASES
			BBOX
			* COUNT
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

			Stuff from GeoServer docs
			* propertyName
			* featureId
			* srsName
		*/	

		/*
		 * Prepare the Query
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

		$count = ( !empty( $get[ 'count' ] ) && $get[ 'count' ] <= $this->limit ? $get[ 'count' ] : $this->limit );

		$query_args = array(
			'posts_per_page' => $count,
			'post_type' => $namespace,
			'meta_query' => array(
				array(
					'key' => $featureType,
					'compare' => 'EXISTS'
					)
				),
		);

		// FeatureID
		if ( !empty( $get[ 'featureid' ] ) ) {
			$query_args[ 'post__in' ] = array( $get[ 'featureid' ] );
		}

		/*
		 * srsName -- supports formats like "EPSG:4326", etc
		 */
		if ( !empty( $get[ 'srsname' ] ) ) {
			$epsg = strtolower( $get[ 'srsname' ] );
			$epsg = str_replace( 'epsg:', '', $epsg );

			if ( is_numeric( $epsg ) ) {
				$query_args[ 'meta_query' ][] = array(
					'key' => $featureType,
					'value' => $epsg,
					'compare' => 'SRID',
				);
			}
		}

		/*
		 * BBOX! 4326 uses lon,lat,lon,lat
		 *
		 * BBOX=43.8702,-96.1523,44.3552,-95.5124
		 */
		if ( !empty( $get[ 'bbox' ] ) ) {
			$coords = explode( ',', $get[ 'bbox' ] );
			$bboxjson = array(
				'type' => 'Feature',
				'geometry' => array(
					'type' => 'Polygon',
					'coordinates' => array(
						array( $coords[1], $coords[0] ),
						array( $coords[1], $coords[2] ),
						array( $coords[3], $coords[2] ),
						array( $coords[3], $coords[0] ),
						array( $coords[1], $coords[0] ),
						),
					)
				);

			$bboxjson = json_encode( $bboxjson );

			$query_args[ 'meta_query' ][] = array(
				'key' => $featureType,
				'value' => $bboxjson,
				'compare' => 'intersects'
			);
		}

		/*
		 * Run the Query
		 */

		$query = new WP_Query( $query_args );

		/*
		 * Process the Query results
		 */

		$geojson = array(
			'type' => 'FeatureCollection',
			'features' => array()
			);

		$propertyname = array();
		if ( !empty( $get[ 'propertyname' ] ) ) {
			$propertyname = explode( ',', $get[ 'propertyname' ] );
		}

		/*
		 * TODO: turn this into streaming output
		 */
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
					'id' => $postID,
					'geometry' => json_decode( WP_GeoUtil::geom_to_geojson( $geom ) ),
					'properties' => array(),
					);

				if ( !empty( $propertyname ) ) {
					$meta = array_intersect_key( $meta, array_flip( $propertyname ) );
				}

				$meta = array_map('array_shift',$meta);
				$geojson_chunk['properties'] = array_merge( $geojson_chunk[ 'properties' ], $meta );

				$geojson['features'][] = $geojson_chunk;
			}
		}

		$this->send_search_result( $geojson );
	}

	public function send_search_result( $json, $format = 'json' ) {
		print json_encode( $json );
		exit();
	}

	public function describe_feature_type( $data, $get ) {

		if ( empty( $get[ 'typenames' ] ) ) {
			return;
		}

		$namespace = str_replace( '/wfs/', '', $data->get_route());
		$featureType = '';

		$nameParts = explode( ':', $get[ 'typenames' ] );

		if ( 2 === count( $nameParts ) ) {
			$namespace = $nameParts[0];
			$featureType = $nameParts[1];
		} else {
			$featureType = $get[ 'typenames' ];
		}

		$query_args = array(
			'posts_per_page' => 1,
			'post_type' => $namespace,
			'meta_query' => array(
				array(
					'key' => $featureType,
					'compare' => 'EXISTS'
					)
				),
		);

		$query = new WP_Query( $query_args );

		if ( $query->have_posts() ) {
				$query->the_post();
				$postID = get_the_ID();
				$meta = get_post_meta( $postID );
				unset( $meta[ $featureType ] );

				$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
				$xml .= '<xsd:schema xmlns:gml="http://www.opengis.net/gml/3.2" xmlns:' . esc_attr( $namespace ). '="' . esc_attr( $namespace )  . '" xmlns:wfs="http://www.opengis.net/wfs/2.0" xmlns:xsd="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified" targetNamespace="' . esc_attr( $namespace ) .'">' . "\n";
				$xml .= '<xsd:import namespace="http://www.opengis.net/gml/3.2" schemaLocation="' . get_rest_url() . 'wfs/schemas/gml/3.2.1/gml.xsd"/>' . "\n";
				$xml .= '<xsd:complexType name="geomType"><xsd:complexContent><xsd:extension base="gml:AbstractFeatureType">' . "\n";
				$xml .= '<xsd:sequence>' . "\n";

				foreach( $meta as $meta_key => $meta_value ) {
					$xml .= '<xsd:element maxOccurs="1" minOccurs="0" name="' . esc_attr( $meta_key ) . '" nillable="true" type="xsd:string"/>' . "\n";
				}

				$xml .= '<xsd:element maxOccurs="1" minOccurs="0" name="' . esc_attr( $featureType ) . '" nillable="true" type="gml:GeometryPropertyType"/>' . "\n";

				$xml .= '</xsd:sequence>' . "\n";
				$xml .= '</xsd:extension>' . "\n";
				$xml .= '</xsd:complexContent>' . "\n";
				$xml .= '</xsd:complexType>' . "\n";
				$xml .= '<xsd:element name="' . esc_attr($featureType) . '" substitutionGroup="gml:AbstractFeature" type="' . esc_attr( $namespace ) . ':geomType"/>' . "\n";
				$xml .= '</xsd:schema>' . "\n";

				header( 'Content-type: application/xml' );
				print $xml;
				exit();
		}
	}
}
