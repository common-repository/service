<?php

class imei {

	private $ApiKey;
	private string $ImeiApiUrl = 'https://imeidb.xyz/api';

	public function __construct() {
		$this->ApiKey = get_option( 'service_imei_api_key' );
	}

	public function get_data($imei){
		wp_send_json(json_decode(wp_remote_retrieve_body(wp_remote_get($this->ImeiApiUrl . '/imei/'.$imei.'?token=' . $this->ApiKey))));
	}

	public function get_balance(){
		$result = json_decode(wp_remote_retrieve_body(wp_remote_get($this->ImeiApiUrl . '/balance?token=' . $this->ApiKey)));
		if ( isset($result->message) ) {
			echo 'Api imei: ';
			echo $result->message;
		} else {
			_e( 'Balance IMEI: ', 'service' );
			echo $result->balance.' '.$result->currency;
		}
	}

	public function test_api_key($key): bool {
		if(json_decode(wp_remote_retrieve_body(wp_remote_get($this->ImeiApiUrl . '/balance?token='.$key)))->balance == null){
			update_option('service_imei_api_key_status', 'false');
			return false;
		} else {
			update_option('service_imei_api_key_status', 'true');
			return true;
		}
	}

}

add_action('rest_api_init', function (){

	register_rest_route('service', 'statistics', array(
		'methods' => 'GET',
		'permission_callback' => function(){
			return current_user_can('service_user');
		},
		'callback' => function($request_data){

			global $wpdb;
			$day = [];
			$month = [];
			foreach ($wpdb->get_results("SELECT sum(cost_total-cost_spare), DATE_FORMAT(`date_issue`, '%Y-%m') as period FROM $wpdb->service_orders WHERE `date_issue` >= DATE_FORMAT(CURRENT_DATE - INTERVAL 10 YEAR, '%Y-%m-01') GROUP BY period", ARRAY_A) as $row) {
				$month[] = [$row['period'], (int)$row['sum(cost_total-cost_spare)']];
			}
			foreach ($wpdb->get_results("SELECT DATE_FORMAT(date_issue, '%d') as date, sum(cost_total-cost_spare) FROM $wpdb->service_orders WHERE MONTH(`date_issue`) = MONTH(NOW()) AND YEAR(`date_issue`) = YEAR(NOW()) GROUP BY 1 ASC", ARRAY_A) as $row) {
				$day[] = [$row['date'], (int)$row['sum(cost_total-cost_spare)']];
			}
			$res = $wpdb->get_row("SELECT (SELECT sum(cost_total-cost_spare) FROM $wpdb->service_orders WHERE (MONTH(`date_issue`) = MONTH(NOW()) AND (YEAR(`date_issue`) = YEAR(NOW())) )  ) / (EXTRACT(DAY FROM CURDATE()))", ARRAY_N);
			wp_send_json( [ 'day' => $day, 'month' => $month, 'forecast' => round($res[0]) ] );

		}
	));

	register_rest_route('service', 'orders', array(
		'methods' => 'GET',
		'permission_callback' => function(){
			return current_user_can('service_user');
		},
		'callback' => function($request_data){
			$parameters = $request_data->get_params();
			$search = sanitize_text_field($parameters['search']['value']);
			$whereAll = " concat(model, serial, client, number) like '%$search%' ";
			$limit = "LIMIT ".intval($parameters['start']).", ".intval($parameters['length']);
			$order = 'ORDER BY `status` ASC, `date_issue` DESC';
			$where = empty( $whereAll ) ? ' ' : 'WHERE ' . $whereAll;
			global $wpdb;
			$data = $wpdb->get_results( "SELECT id, id_label, date, model, serial, malfunction, client, number, work, cost_spare, cost_total, status, manager_notes FROM `$wpdb->service_orders` $where $order $limit", ARRAY_A );
			$resTotalLength = $wpdb->get_var("SELECT COUNT(`id`) FROM `$wpdb->service_orders` $where");
			wp_send_json([
				"draw"            => intval( $parameters['draw'] ) ,
				"recordsTotal"    => intval( $resTotalLength ),
				"recordsFiltered" => intval( $resTotalLength ),
				"data"            => $data
			]);
		}
	));

	register_rest_route('service', 'new-order', array(
		'methods' => 'POST',
		'permission_callback' => function(){
			return current_user_can('service_user');
		},
		'callback' => function($request_data){
			$parameters = $request_data->get_params();
			global $wpdb;
			do {
				$id_label = substr(date("Y"), -2).'/'.mt_rand(10000, 99999);
			}
			while($wpdb->query("SELECT `id_label` FROM $wpdb->service_orders WHERE `id_label` LIKE '". $id_label ."'"));
			$wpdb->insert($wpdb->service_orders,
				[
					'id_label'         => $id_label,
					'manager'          => wp_get_current_user()->user_login,
					'model'            => sanitize_text_field($parameters['model']),
					'serial'           => sanitize_text_field($parameters['serial']),
					'malfunction'      => sanitize_text_field($parameters['malfunction']),
					'client'           => sanitize_text_field($parameters['client']),
					'number'           => sanitize_text_field($parameters['number']),
					'cost_total'       => sanitize_text_field($parameters['cost_total']),
					'status'           => 1,
					'manager_notes'    => sanitize_text_field($parameters['manager_notes'])
				]
			);
			wp_send_json(['id' => $wpdb->insert_id]);
		}
	));

});