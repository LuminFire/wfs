<?php

class WFS_200_capabilities_xml {

	var $xml;
	var $data;
	var $get;

	public function __construct( $data, $get ) {
		$this->data = $data;
		$this->get = $get;

		$this->xml = new SimpleXMLElement('<wfs:WFS_Capabilities xmlns:wfs="http://www.opengis.net/wfs/2.0" />');
		$this->header();
	}

	public function header() {

		// Set up all the namespaces

$this->xml->addAttribute('xmlns',"http://www.opengis.net/wfs/2.0" );
$this->xml->addAttribute('xmlns:xsi',"http://www.w3.org/2001/XMLSchema-instance",'xmlns' );
$this->xml->addAttribute('xmlns:ows',"http://www.opengis.net/ows/1.1", 'xmlns' );
$this->xml->addAttribute('xmlns:gml',"http://www.opengis.net/gml/3.2", 'xmlns' );
$this->xml->addAttribute('xmlns:fes',"http://www.opengis.net/fes/2.0", 'xmlns' );
$this->xml->addAttribute('xmlns:xlink',"http://www.w3.org/1999/xlink", 'xmlns' );
$this->xml->addAttribute('xmlns:xs',"http://www.w3.org/2001/XMLSchema", 'xmlns' );
$this->xml->addAttribute('xmlns:xml',"http://www.w3.org/XML/1998/namespace", 'xmlns' );
//$this->xml->addAttribute('xmlns:walkin',"walkin" );
//		xsi:schemaLocation="http://www.opengis.net/wfs/2.0 http://localhost:8080/geoserver/schemas/wfs/2.0/wfs.xsd" 
	}

	public function __toString() {
		return $this->xml->asXML();
	}
}
