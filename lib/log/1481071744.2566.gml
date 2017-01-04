Array
(
    [service] => WFS
    [version] => 2.0.0
    [request] => DescribeFeatureType
    [typeNames] => walkin:geom
)

--------------------------------
<?xml version="1.0" encoding="UTF-8"?>
<xsd:schema xmlns:gml="http://www.opengis.net/gml/3.2" xmlns:walkin="walkin" xmlns:wfs="http://www.opengis.net/wfs/2.0" xmlns:xsd="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified" targetNamespace="walkin">
<xsd:import namespace="http://www.opengis.net/gml/3.2" schemaLocation="http://geopro.dev/wp-json/wfs/schemas/gml/3.2.1/gml.xsd"/>
<xsd:complexType name="geomType"><xsd:complexContent><xsd:extension base="gml:AbstractFeatureType">
<xsd:sequence>
<xsd:element maxOccurs="1" minOccurs="0" name="CTY_NAME" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="MAP_TITLE" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="WIA_ID" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="WIA_PDF" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="Map_Label_" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="GPSMapID" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="COUNTY_NUM" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="ACRES" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="SHAPE_Leng" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="SHAPE_Area" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="UserNotes" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="Atlas_Page" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="WIA_Tile" nillable="true" type="xsd:string"/>
<xsd:element maxOccurs="1" minOccurs="0" name="geom" nillable="true" type="gml:GeometryPropertyType"/>
</xsd:sequence>
</xsd:extension>
</xsd:complexContent>
</xsd:complexType>
<xsd:element name="geom" substitutionGroup="gml:AbstractFeature" type="walkin:geomType"/>
</xsd:schema>
