<?php

add_action('wp_ajax_imei', function (){
	(new imei())->get_data($_GET['imei']);
});

add_action('wp_ajax_insert_client_number', function (){
	global $wpdb;
	$service_search_number = sanitize_text_field($_GET['search_number']);
	wp_send_json($wpdb->get_row("SELECT client FROM $wpdb->service_orders WHERE `number`=".$service_search_number, ARRAY_A));
});

add_action('wp_ajax_insert_tac_number', function (){
	global $wpdb;
	$service_search_number = sanitize_text_field($_GET['search_tac_number']);
	wp_send_json($wpdb->get_row("SELECT model FROM $wpdb->service_orders WHERE `serial` LIKE '".$service_search_number."%'", ARRAY_A));
});

add_action('wp_ajax_live_search', function (){
	global $wpdb;
	$service_field = sanitize_text_field($_GET['field']);
	$service_search = sanitize_text_field($_GET['search']);
	wp_send_json($wpdb->get_col("SELECT distinct(".$service_field.") FROM $wpdb->service_orders WHERE ".$service_field." LIKE '".$service_search."%' ORDER BY ".$service_field." ASC"));
});

add_action('wp_ajax_delete_orders', function (){
	if (current_user_can('manage_options')) {
		global $wpdb;
		$data[] = 0;
		foreach ($_GET['delete'] as $del_id) {
			$data[] = $del_id;
		}
		$data = implode(",", $data);
		wp_send_json($wpdb->query("DELETE FROM $wpdb->service_orders WHERE `id` in ($data)"));
	} else {
		wp_send_json('Not enough rights to delete an object!');
	}
});

add_action('wp_ajax_print_documents', function (){
	global $wpdb;
	$row = $wpdb->get_row( "SELECT id_label, serial, client, model, malfunction, number, work, cost_total FROM $wpdb->service_orders WHERE `id`= ".sanitize_text_field($_GET['id']), ARRAY_A );
	$replace_data =
		[
			'{id_label}'    => $row['id_label'],
			'{serial}'      => $row['serial'],
			'{date}'        => date_create($row['date'])->Format('d-m-Y'),
			'{client}'      => $row['client'],
			'{model}'       => $row['model'],
			'{malfunction}' => $row['malfunction'],
			'{number}'      => $row['number'],
			'{work}'        => $row['work'],
			'{cost_total}'  => $row['cost_total']
		];
	wp_send_json([ 'html' => str_replace(array_keys($replace_data),array_values($replace_data),wp_unslash(get_option(sanitize_text_field($_GET['data'])))) ]);
});

add_action('wp_ajax_update_order_info', function (){
	global $wpdb;
	$service_status =     sanitize_text_field($_POST['status']);
	$service_work =       sanitize_text_field($_POST['work']);
	$service_cost_spare = sanitize_text_field($_POST['cost_spare']);
	$service_cost_total = sanitize_text_field($_POST['cost_total']);
	$service_id =         sanitize_text_field($_POST['id']);
	$res = $wpdb->update($wpdb->service_orders,
		['status' => $service_status, 'work' => $service_work, 'cost_spare' => $service_cost_spare, 'cost_total' => $service_cost_total],
		['id' => $service_id]
	);
	if ($service_status == 7){
		$wpdb->update($wpdb->service_orders, ['date_issue' => date('Y.m.d H:i:s')], ['id' => $service_id]);
	}
	wp_send_json($res);
});

//settings page
add_action('wp_ajax_save_settings', function (){
	update_option('service_print_receipt', $_POST['print_receipt_save'], false);
	update_option('service_print_warranty', $_POST['print_warranty_save'], false);
	update_option('service_imei_api_key', $_POST['imei_api_key_save'], false );
});

add_action('wp_ajax_get_api_keys', function (){
	wp_send_json( ['imeiApiKey' => get_option('service_imei_api_key')] );
});

add_action('wp_ajax_test_api_keys', function (){
	wp_send_json(['testImeiApiKey' => (new imei())->test_api_key($_GET['test_imei_api_key']) ]);
});