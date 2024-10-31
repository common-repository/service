<?php

/**
 * Plugin Name:       Service
 * Plugin URI:        https://wordpress.org/plugins/service/
 * Description:       The CRM for service center
 * Version:           1.0.4
 * Requires at least: 5.9.2
 * Requires PHP:      7.4
 * Author:            serviceonline
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       service
 * Domain Path:       /lang
 */

if ( ! defined( 'ABSPATH' ) )
	die;

include __DIR__ . '/includes/api.php';
include __DIR__ . '/includes/function.php';

global $wpdb;
$wpdb->service_orders = $wpdb->prefix.'service_orders';

add_action( 'init', function(){
	load_plugin_textdomain( 'service', false, dirname(plugin_basename(__FILE__)) . '/lang' );
});

add_action('admin_menu', function (){
	add_menu_page(
		esc_html__('Service', 'service'),
		esc_html__('Service', 'service'),
		'service_user',
		'service.php',
		'',
		'dashicons-hammer',
		'100');
});

add_action('admin_menu', function (){
	$page = add_submenu_page(
		'service.php',
		esc_html__('Reception', 'service'),
		esc_html__('Orders', 'service'),
		'service_user',
		'service-orders',
		function() {include 'pages/orders.php';});
	remove_submenu_page('service.php','service.php');
	add_action( 'load-' . $page, function (){
		wp_enqueue_script('mask', plugins_url('/js/jquery.mask.min.js', __FILE__));
		wp_enqueue_script('jquery-ui-autocomplete');
		wp_enqueue_script('datatables', plugins_url('/js/jquery.dataTables.min.js', __FILE__) );
		wp_enqueue_script('selectdatatables', plugins_url('/js/dataTables.select.min.js', __FILE__) );
		wp_enqueue_script('buttonsdatatables', plugins_url('/js/dataTables.buttons.min.js', __FILE__) );
		wp_enqueue_style('datatablescss', plugins_url('/js/jquery.dataTables.min.css', __FILE__) );
	} );
});

add_action('admin_menu', function (){
	$page = add_submenu_page(
		'service.php',
		esc_html__('Settings', 'service'),
		esc_html__('Settings', 'service'),
		'service_user',
		'service-settings',
		function() {include 'pages/settings.php';});
	add_action( 'load-' . $page, function (){
		wp_enqueue_script('mask', plugins_url('/js/jquery.mask.min.js', __FILE__));
	} );
});

add_action('admin_menu', function (){
	$page = add_submenu_page('service.php',
		esc_html__('Statistics', 'service'),
		esc_html__('Statistics', 'service'),
		'service_user',
		'service-statistics',
		function() {include 'pages/statistics.php';});
	add_action( 'load-' . $page, function (){
		wp_enqueue_script( 'google_charts', plugins_url('/js/google.charts.js', __FILE__) );
	} );
});




register_activation_hook(__FILE__, function () {

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	global $wpdb;
	$sql = "CREATE TABLE {$wpdb->service_orders} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `manager` varchar(255) NOT NULL,
  `id_label` varchar(255) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `model` varchar(255) NOT NULL,
  `serial` varchar(255) NOT NULL,
  `malfunction` varchar(255) NOT NULL,
  `work` varchar(255) NOT NULL,
  `client` varchar(255) NOT NULL,
  `number` varchar(20) NOT NULL,
  `cost_spare` int(11) NOT NULL,
  `cost_total` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  `date_issue` datetime DEFAULT NULL,
  `sms_id` varchar(255) NOT NULL,
  `sms_status` int(11) NOT NULL,
  `manager_notes` varchar(255) NOT NULL, 
  `serviceman` varchar(255) NOT NULL,
  UNIQUE KEY id (id)
) DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate};";

	dbDelta($sql);

	add_role('service_engineer', 'Engineer', ['service_user' => true, 'read' => true]);
	get_role('administrator' )->add_cap( 'service_user' );

	if ( file_exists( __DIR__ . '/receipt.txt' ) ) {
		add_option( "service_print_receipt", file_get_contents( __DIR__ . '/receipt.txt', true ), '', false );
		wp_delete_file( __DIR__ . '/receipt.txt' );
	}
	if ( file_exists( __DIR__ . '/warranty.txt' ) ) {
		add_option( "service_print_warranty", file_get_contents( __DIR__ . '/warranty.txt', true ), '', false );
		wp_delete_file( __DIR__ . '/warranty.txt' );
	}

	add_option('service_sms_api_key', '');
	add_option('service_imei_api_key', '');
	add_option('service_imei_api_key_status', 'false');
});

register_deactivation_hook(__FILE__, function (){
	get_role( 'administrator' )->remove_cap( 'service_user' );
	remove_role( 'service_engineer' );
});