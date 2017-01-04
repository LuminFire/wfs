<?php

class WFS_200_capabilities_xml {

	var $xml;
	var $data;
	var $get;
	var $wfs;

	public function __construct( $data, $get, $wfs ) {
		$this->data = $data;
		$this->get = $get;
		$this->wfs = $wfs;

		$this->wfs_url = get_rest_url() . 'wfs/2.0.0/';
		$this->wfs_url .= ( !empty( $this->get['namespace'] ) ? $this->get['namespace'] . '/' : '');

		$this->header();
		$this->service_identification();
		$this->service_provider();
		$this->operations_metadata();
		$this->feature_type_list();
	}

	private function header() {
		$xmlString = 
			'<wfs:WFS_Capabilities 
			version="2.0.0" 
			xmlns="http://www.opengis.net/wfs/2.0" 
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
			xmlns:wfs="http://www.opengis.net/wfs/2.0" 
			xmlns:ows="http://www.opengis.net/ows/1.1" 
			xmlns:gml="http://www.opengis.net/gml/3.2" 
			xmlns:fes="http://www.opengis.net/fes/2.0" 
			xmlns:xlink="http://www.w3.org/1999/xlink" 
			xmlns:xs="http://www.w3.org/2001/XMLSchema" 
			xmlns:xml="http://www.w3.org/XML/1998/namespace"';
		$xmlString .= 'xsi:schemaLocation="http://www.opengis.net/wfs/2.0 http://schemas.opengis.net/wfs/2.0/wfs.xsd" ';

		$namespace = ( !empty( $this->get['namespace'] ) ? $this->get['namespace'] : null );
		$post_types = WFS_Rest::allowed_feature_types( $namespace );

		foreach( $post_types as $schema => $featureTypes) {
			$xmlString .= ' xmlns:' . $schema . '="' . $schema . '"';
		}

		$xmlString .= '/>';

		$this->xml = simplexml_load_string( $xmlString, 'SimpleXMLElement', 0, 'wfs', true );
	}

	private function service_identification() {
		$service = $this->xml->addChild('ows:ServiceIdentification','','http://www.opengis.net/ows/1.1');
		$service->addChild('Title','WFS for WordPress');
		$service->addChild('Abstract','This is the WFS implementation for WordPress. It targets WFS 2.0.0');

		$keywords = $service->addChild('Keywords');
		$keywords->addChild('Keyword','WFS');
		$keywords->addChild('Keyword','WordPress');

		$service->addChild('ServiceType','WFS');
		$service->addChild('ServiceTypeVersion','2.0.0');
		$service->addChild('Fees','NONE');
		$service->addChild('AccessConstraints','NONE');
	}

	private function service_provider() {
		$provider = $this->xml->addChild('ows:ServiceProvider','','http://www.opengis.net/ows/1.1');
		$provider->addChild('ProviderName', get_bloginfo() );
		$contact = $provider->addChild('ServiceContact');
		$contact->addChild('IndividualName', get_bloginfo('admin_email') );
	}

	private function operations_metadata() {
		$ops = $this->xml->addChild('ows:OperationsMetadata','','http://www.opengis.net/ows/1.1');

		$op = $ops->addChild('Operation');
		$op['name'] = 'GetCapabilities';
		$this->add_dcp($op);
		$this->add_parameter($op,'AcceptVersions', array( '2.0.0' ) );
		$this->add_parameter($op,'AcceptFormats', array( 'text/xml' ) );

		$op = $ops->addChild('Operation');
		$op['name'] = 'DescribeFeatureType';
		$this->add_dcp($op);
		$this->add_parameter($op,'outputFormat',array( 'text/xml; subtype=gml/3.2' ) );

		$op = $ops->addChild('Operation');
		$op['name'] = 'GetFeature';
		$this->add_dcp($op);
		$this->add_parameter( $op,'resultType',array('results','hits') );
		$this->add_parameter( $op,'outputFormat',array('text/xml; subtype=gml/3.2', 'application/gml+xml; version=3.2','application/json','gml32','json') );
		$this->add_constraint($op, 'PagingIsTransactionSafe', 'FALSE' );
		$this->add_constraint($op, 'CountDefault', $this->wfs->limit );

		$this->add_constraint( $ops, 'ImplementsBasicWFS','TRUE');
		$this->add_constraint( $ops, 'KVPEncoding','TRUE');
		$this->add_constraint( $ops, 'XMLEncoding','TRUE');
		$this->add_constraint( $ops, 'ImplementsResultPaging','TRUE');
		$this->add_constraint( $ops, 'ImplementsStandardJoins','TRUE');
		$this->add_constraint( $ops, 'ImplementsSpatialJoins','TRUE');
	}

	private function add_constraint( $node, $name, $defaultValue ){
		$con = $node->addChild('Constraint');
		$con['name'] = $name;
		$con->addChild('NoValues');
		$con->addChild('DefaultValue',$defaultValue);
	}

	private function add_dcp($node){
		$http = $node->addChild('DCP')->addChild('HTTP');
		$http->addChild('Get')['xlink:href'] = $this->wfs_url;
		$http->addChild('Post')['xlink:href'] = $this->wfs_url;
	}

	private function add_parameter( $node, $name, $values ) {
		$params = $node->addChild('Parameter');
		$params['name'] = $name;
		$allowed = $params->addChild('AllowedValues');
		foreach( $values as $value ) {
			$allowed->addChild('Value',$value);
		}
	}

	private function feature_type_list() {
		$features = $this->xml->addChild('FeatureTypeList','','http://www.opengis.net/wfs/2.0');

		$namespace = ( !empty( $this->get['namespace'] ) ? $this->get['namespace'] : null );
		$post_types = WFS_Rest::allowed_feature_types( $namespace );

		foreach( $post_types as $schema => $featureTypes ) {
			foreach( $featureTypes as $featureType ) {
				$child = $features->addChild('FeatureType');
				$child[ 'xmlns:' . $featureType ] = $featureType;

				$child->addChild('Name', $schema . ':' . $featureType );
				$child->addChild('Title',$featureType);

				$child->addChild('Abstract');

				$keywords = $child->addChild('ows:Keywords','','http://www.opengis.net/ows/1.1');
				$keywords->addChild('Keyword','features');
				$keywords->addChild('Keyword',$schema);
				$keywords->addChild('Keyword',$featureType);

				$child->addChild( 'DefaultCRS','urn:ogc:def:crs:EPSG::' . WP_GeoUtil::get_srid() );

				if ( 4326 === WP_GeoUtil::get_srid() ) {
					$bbox = $child->addChild('ows:WGS84BoundingBox','','http://www.opengis.net/ows/1.1');
					$bbox->addChild('LowerCorner','-180.0 -90.0');
					$bbox->addChild('UpperCorner','180.0 90.0');
				}

			}
		}
	}

	public function __toString() {
		$domElement = dom_import_simplexml( $this->xml );
		$dom = $domElement->ownerDocument;
		$dom->preserveWhiteSpace = false; 
		$dom->formatOutput = false; 
		$dom->encoding = 'UTF-8';
		return $dom->saveXML();
	}
}
