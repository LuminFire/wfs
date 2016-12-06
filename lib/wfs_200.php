<?php

/**
 * Class implementing WFS 2.0.0
 */
class WFS_200 {

	var $limit = 500;

	public function __construct( $data ) {

		// http://geopro.dev/wp-json/wfs/walkin/?service=wfs&version=2.0.0&request=GetFeature&typeNames=walkin:geom
		$params = $data->get_params();
		$get = array_change_key_case( $params );

		if ( !empty( $get['typename'] ) ) {
			$get['typenames'] = $get['typename'];
		}

		switch ( $get['request'] ) {
			case 'GetCapabilities':
				$this->get_capabilities( $data, $get );
			case 'GetFeature':
				$this->get_feature( $data, $get );
				break;
			case 'DescribeFeatureType':
				$this->describe_feature_type( $data, $get );
				break;
			default:
				$this->unsupported_request( $get['request'] );
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

		$namespace = ( !empty( $get['namespace'] ) ? $get['namespace'] . '/' : '');
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

	public function send_search_result( $json, $format = 'gml32' ) {
		print json_encode( $json );
		exit();
	}

	public function describe_feature_type( $data, $get ) {

		$namespace = ( !empty( $get['namespace'] ) ? $get['namespace'] . '/' : '');
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

	public function unsupported_request( $req ) {
		header( $_SERVER['SERVER_PROTOCOL'] . ' 500 Not Implemented', true, 501 );
		print json_encode( array(
			'Oops' => 'Looks like you made an unsupported request. If we should support it, tell us at https://github.com/cimburadotcom/wfs',
			'Documentation' => array( 
				'https://portal.opengeospatial.org/files/?artifact_id=66933',
				'http://docs.geoserver.org/latest/en/user/services/wfs/reference.html',
				'https://github.com/cimburadotcom/wfs'
			)
			) );
		exit();
	}

	public function get_capabilities( $data, $get ) {
		require_once( dirname( __FILE__ ) . '/wfs_200_capabilities_xml.php' );
		$xml = new WFS_200_capabilities_xml( $data, $get, $this );
		header( 'Content-type: application/xml' );
		print $xml;
		exit();
	}
}
