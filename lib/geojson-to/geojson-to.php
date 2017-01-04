<?php

class geojson_to {

	/**
	 * This will turn a GeoJSON FeatureCollection into a GML 3.2 SimpleXML document
	 *
	 * It will NOT add any attributes to the 
	 *
	 * @param array $geojson A GeoJSON compliant array
	 * @param array $namespace_featureType A dict of namespaces => featureTypes to create xmlns entries for.
	 * @param int   $projection The EPSG code to use
	 */
	public static function gml32( $geojson, $namespace_featureType = array(), $projection = 4326 ) {

	$xmlns = array(
		'xmlns:xs="http://www.w3.org/2001/XMLSchema"',
		'xmlns:wfs="http://www.opengis.net/wfs/2.0"',
		'xmlns:gml="http://www.opengis.net/gml/3.2"',
		'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
	);
	$ns_pairs = array();
	foreach( $namespace_featureType as $ns => $ft ) {
		$xmlns[] = 'xmlns:' . $ns . '="' . $ns . '"';
		$ns_pairs[] = "$ns%3A$ft";
	}


	$schemaLocations = array(
	'http://www.opengis.net/wfs/2.0',
	'http://schemas.opengis.net/wfs/2.0/wfs.xsd',
	'http://www.opengis.net/gml/3.2',
	'http://schemas.opengis.net/gml/3.2.1/gml.xsd',
	'states',
	'http://geopro.dev/wp-json/wfs/2.0.0/wfs?service=WFS&amp;version=2.0.0&amp;request=DescribeFeatureType&amp;typeName=' . implode(',',$ns_pairs),
	);
	// foreach ( $namespace_featureType as $ns => $ft ){
	// 	$schemaLocations[] = "$ns";
	// }

	$xml_string = '<?xml version="1.0" encoding="UTF-8"?>
	<wfs:FeatureCollection 
	numberMatched="32" 
	numberReturned="' . count( $geojson['features'] ) . '" 
	timeStamp="' . date( 'Y-m-d\TH:i:s\.000\Z' ) . '"
	' . implode( ' ', $xmlns ) . '
	xsi:schemaLocation="' . implode(' ', $schemaLocations ) . '"/>';
		
		$xml = simplexml_load_string($xml_string);

		$domElement = dom_import_simplexml( $xml );
		$dom = $domElement->ownerDocument;
		$dom->encoding = 'UTF-8';
		$dom->version = '1.0';

		foreach( $geojson['features'] as $feature ) {
			$ns = ( !empty( $feature['_ns'] ) ? $feature['_ns'] : $namespace );
			$featType = ( !empty( $feature['_feature'] ) ? $feature['_feature'] : $featType );

			$dom->createAttributeNS( $ns, $ns . ':' . $ns);

			$member = $xml->addChild( 'member' );
			$feat = $member->addChild( $featType, '', $ns);

			if ( !empty( $feature['id'] ) ) {
				$feat['gml:id'] = $feature['_ns'] . '.' . $feature['id'];
			}

			foreach( $feature['properties'] as $prop => $val ) {
				$feat->addChild( $prop, $val );
			}

			self::geojson_geom_to_geom( $feat, $feature['geometry'], $feature['_feature'], $projection );
		}

		return $dom;	
	}

	private static function geojson_geom_to_geom( $xml, $geometry, $feature_field, $projection ) {

		if ( empty( $geometry ) ) {
			return;
		}

		$geom = $xml->addChild($feature_field . '_geom');

		switch( $geometry['type'] ) {
			case 'Point': 
				$gml = $geom->addChild( 'Point', '', 'http://www.opengis.net/gml/3.2' );
				$gml->addChild('pos', $geometry['coordinates'][0] . ' ' . $geometry['coordinates'][1]);
				break;
			case 'Polygon':
				// $gml = $gkom->addChild( 'Polygon', '', 'http://www.opengis.net/gml/3.2' );
				$gml = self::make_gml_polygon( $geom, $geometry['coordinates'] );
				break;
			case 'MultiPolygon':
				$gml = $geom->addChild('geometryMember','', 'http://www.opengis.net/gml/3.2');
				$gml = $gml->addChild('MultiGeometry');
				foreach( $geometry['coordinates'] as $one_multi ) {
					$geometryMember = $gml->addChild('geometryMember');
					self::make_gml_polygon( $geometryMember, $one_multi );
				}
				break;
			default:
				print "IDK what to do with {$geometry['type']}\n";
		}

		$gml['srsName'] = 'urn:ogc:def:crs:EPSG::' . $projection;
		$gml['srsDimension'] = 2;
	}

	private static function coords_to_poslist($coord_array){
		$flatish = array_map( function($e){ return implode(' ', array_reverse( $e ) ); }, $coord_array);
		return implode( ' ', $flatish );
	}

	private static function make_gml_polygon( $gml, $one_multi ) {
		$exterior = array_shift($one_multi);
		$extpoly = $polygon = $gml->addChild( 'gml:Polygon','' , 'http://www.opengis.net/gml/3.2' );
		$positions = self::coords_to_poslist( $exterior );
		$polygon->addChild('exterior')->addChild('LinearRing')->addChild('posList',$positions);

		foreach( $one_multi as $interior_poly ) {
			$polygon = $gml->addChild('surfaceMember')->addChild('Polygon');
			$interior = array_shift($one_multi);
			$positions = self::coords_to_poslist( $interior );
			$polygon->addChild('interior')->addChild('LinearRing')->addChild('posList',$positions);
		}
		return $extpoly;
	}
}
