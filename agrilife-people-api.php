<?php
/**
 * Plugin Name: AgriLife People API
 * Plugin URI: https://github.com/AgriLife/agrilife-people-api
 * Description: Display AgriLife People API data
 * Version: 1.0.0
 * Author: Zach Watkins
 * Author URI: http://github.com/ZachWatkins
 * Author Email: zachary.watkins@ag.tamu.edu
 * License: GPL2+
 */

require 'vendor/autoload.php';

define( 'AG_PEOPLEAPI_DIRNAME', 'agrilife-people-api' );
define( 'AG_PEOPLEAPI_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'AG_PEOPLEAPI_DIR_FILE', __FILE__ );
define( 'AG_PEOPLEAPI_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'AG_PEOPLEAPI_TEMPLATE_PATH', AG_PEOPLEAPI_DIR_PATH . 'view' );

$apapi_home_template = new AgriLife\PeopleAPI\PageTemplate();
$apapi_home_template->with_path( AG_PEOPLEAPI_TEMPLATE_PATH )->with_file( 'people-list' )->with_name( 'People' );
$apapi_home_template->register();

function agppl_add_query_vars($vars) {
	$vars[] = 'single_person';
	$vars[] = 'person_id';
	return $vars;
}
add_filter('query_vars', 'agppl_add_query_vars');
