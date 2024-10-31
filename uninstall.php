<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) die();

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS ". $wpdb -> prefix.'service_orders');

delete_option('service_print_receipt');
delete_option('service_print_warranty');
delete_option('service_sms_api_key');
delete_option( 'service_imei_api_key');
delete_option( 'service_imei_api_key_status');