<?php
/**
 * WFS provides WFS (Web Feature Service) endpoints for WordPress.
 *
 * Plugin Name: WFS
 * Description: Turn your spatial data into endpoints
 * Plugin URI: https://github.com/cimburadotcom/wfs
 * Author: Michael Moore
 * Author URI: http://cimbura.com
 * Version: 0.0.1
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wfs
 * Domain Path: /lang
 *
 * @package wfs
 **/

require_once( dirname( __FILE__ ) . '/lib/rest.php' );
require_once( dirname( __FILE__ ) . '/lib/wp-geometa-lib/wp-geometa-lib-loader.php' );
WFS_Rest::get_instance();
