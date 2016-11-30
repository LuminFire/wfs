WFS
===

This will provide a way to manage WFS endpoints for your spatial data in WordPress.

This is a work in progress and nothing here should be considered finalized. 


Tentative Plan
--------------

Even though WFS is SOAPy, we are going to build this as a WP REST endpoint. 

Any spatial metadata stored with WP-GeoMeta or a plugin that uses WP-GeoMeta should automatically be elligible to be served by WFS.

### Dashboard

There should be a dashboard where all GeoMeta fields are detected, and toggle switches (or radio buttons, or checkboxes...) allow the admin to enabled WFS for that post type and field along with any supported options.

The dashboard should detect other meta keys for a given post type and allow them to be served as attributes/properties on the feature. In other words, each Post (or page, or whatever) is a feature, and all of its meta values are properties that can be enabled.

Primary Post properties should be available as properties too including title, body, publish status, publish date, author, ID and permalink.

There should be a link to open a sample webmap with the current WFS layer and a sample of data from the layer with popups with the other metadata.

There should probably be an option on how to secure the layer and if it's listed or invisible.

### WFS Version

[WFS 2.0.0](http://docs.opengeospatial.org/is/09-025r2/09-025r2.html#125) will be the first support target, with support for 1.0.1 added at some future date, if the need is there.

### Reprojection / SRID
No reprojection will be supported, only the SRID that the geometry is stored in will be accepted. WP-GeoMeta defaults to EPSG:4326, so that would be the usual SRID.

### WFS-T
We will implement WFS-T so that this can be used for data collection and editing too.

### POST vs GET

GET Requests are made with URL parameters. POST requests are made with GML. We'll focus on GET requests first.

### Namespaces / Layer Names

Namespaces will be post types. For user, term and comment queries, the user can use wp-term, wp-comment or wp-user.

So if you had a post type of "State" you could have two separate spatial meta fields which would be available under state:capitol and state:boundaries.

Security
--------

REST endpoints shouldn't be listed in the GetCapabilities response unless an admin has explicitly enabled them.


Authentication
--------------

http://v2.wp-api.org/guide/authentication/

We'll start with just using cookie authentication, which we should basically get for free.


URLS
----

I'm not certain what we want to use for a URL structure. 

I do think we should claim something at the top level, and it should match our eventual plugin name. So /*wfs*/ or /*our plugin name*/

### /wfs/*something*/

We will be using /wfs/*something*/ as the WFS service URL. Since all REST endpoints are appended to wp-json (or whatever the user has set the REST url to, if they've customized it), they would be...

http://geopro.dev/wp-json/wfs/posts/(?WFS Query parts here)
http://geopro.dev/wp-json/wfs/states/(?WFS Query parts here)
http://geopro.dev/wp-json/wfs/wp-users/(?WFS Query parts here)

### URL

http://geopro.dev/wp-json/wfs/posts/?service=wfs&version=1.1.0&request=GetCapabilities
http://geopro.dev/wp-json/wfs/posts/?service=wfs&version=2.0.0&request=GetFeature&typeNames=namespace:featuretype
http://geopro.dev/wp-json/wfs/multi/?service=wfs&version=2.0.0&request=GetFeature&typeNames=namespace:featuretype&srsName=CRSbbox=a1,b1,a2,b2
http://geopro.dev/wp-json/wfs/posts/?service=wfs&version=2.0.0&request=GetFeature&typeNames=walkin:geom


Priorities
----------

Support should be implemented in roughly this order:

1. GetFeature
2. GetCapabilities
3. DescribeFeatureType

### Future Implementation

The following operations may be implemented in the future:

* LockFeature
* Transaction
* GetPropertyValue
* GetFeatureWithLock
* CreatedStoredQuery
* DropStoredQuery
* ListStoredQueries
* DescribeStoredQueries


GML
---
 
I don't presently see PHP libraries that can write GML. We will likely need to extend geoPHP to read and write GML, at at least a subset of GML.

### KVP vs XML

KVP (Key Value Parameters) is what the spec calls query string parameters. Requests can also be made using an XML ecoding. 

We'll deal with KVP first, and XML encoding later (maybe).


GeoJSON
-------

GeoJSON format support should be one of the top priorities since that's what most people will want for webmaps.


CQL/ECQL
--------

Should we support CQL/ECQL? It's so much more efficient to write CQL than to write a GML query document. What would it take to parse CQL?


References
----------

* [GeoServer WFS Reference](http://docs.geoserver.org/latest/en/user/services/wfs/reference.html)
* [WFS 2.0.2](http://docs.opengeospatial.org/is/09-025r2/09-025r2.html#125)
* [GML 3.3](http://www.opengeospatial.org/standards/gml)
