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

Should namespaces be post, user, term, comment?

Or would it be posttype:geometa_name.

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

### /wfs/

We could go with /wfs/, then do like GeoServer

https://example.com/wfs/*post type*/?service=WFS&*rest of WFS Request string*


### /something/wfs/

Or we could it register an endpoint at /*something*/ and then use /*something*/wfs/ for WFS stuff, so that we can add /*something*/*somethingelse*/ in the future? 

https://example.com/*plugin name*/wfs/
http://example.com/*plugin name*/wfs?version=2.0.0&request=GetCapabilities

### /something/*layername*/wfs

If we broke it down this way, then each layer would have its services separate and we could do permissions more easily and stuff like that. 


### NOT REST

~ Another option would be to make any archive page a WFS endpoint so that http://example.com/posttype/?service=WFS... ~

Nevermind. I want URLs to be in a predictable place for ease of use.  


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
* DropStoredQuery
* ListStoredQueries
* DescribeStoredQueries


GML
---
 
I don't presently see PHP libraries that can write GML. We will likely need to extend geoPHP to read and write GML, at at least a subset of GML.


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
