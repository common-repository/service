<style type="text/css">
    .receipt {
        width: 310px;
        border: 2px solid black;
    }
    .rightpic {
        float: right;
        margin: 50px 50px 0 0;
    }
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 999;
        display: none
    }
    .popup_body {
        position: fixed;
        padding:20px;
        top: 50%;
        left: 50%;
        width: 690px;
        height: 400px;
        margin: -220px 0 0 -345px;
        background: #e9e9e9;
        border-radius: 5px;
    }
    .popup_body a.close {
        position: relative;
        float: right;
        margin: 15px 15px 0 0;
        display: block;
        width:12px;
        height:12px;
        background: #f51804;

    }
</style>
<div class="wrap">

    <div class="overlay" id="popup">
        <div class="popup_body" >

            <a href="javascript: void(0);" class="close" id="close"></a>


            <img id="image" class="rightpic" style="display:none;">

    <table class="receipt" id="rec">
        <tr>
            <td><label for="number"><?php _e('Phone:', 'service');?></label></td>
<td><input id="number" autocomplete="off" type="text" placeholder="+7(999)123-45-67"></td>
<tr>
<tr>
	<td><label for="client"><?php _e('Customer:', 'service');?></label></td>
	<td><input id="client" autocomplete="off" type="text"></td>
<tr>
<tr>
	<td><label for="serial"><?php _e('Serial number:', 'service');?></label></td>
	<td><input id="serial" autocomplete="off" type="text"></td>
<tr>
<tr>
	<td><label for="model"><?php _e('Model:', 'service');?></label></td>
	<td><input id="model" autocomplete="off" type="text"></td>
<tr>
<tr>
	<td><label for="malfunction"><?php _e('Malfunction:', 'service');?></label></td>
	<td><input id="malfunction" autocomplete="off" type="text"></td>
<tr>
<tr>
	<td><label for="cost_total"><?php _e('Cost:', 'service');?></label></td>
	<td><input id="cost_total" autocomplete="off" type="text"></td>
<tr>
<tr>
	<td><label for="manager_notes"><?php _e('Notes:', 'service');?></label></td>
	<td><input id="manager_notes" autocomplete="off" type="text"></td>
<tr>
	</table>
	<br>
	<button id="insert_db" class="button" style="display:none;"><?php _e('Save', 'service');?></button>
	<br>

	</div>
	</div>


	<br>
    <table id="table_repair" class="display" style="width:100%">
    </table>
	<br>
	<button id="delete" class="button" style="display:none;"><?php _e('Delete', 'service');?></button>
	</div>

	<script>
        jQuery(document).ready(function($) {

            $("#close").click(function(){
                $("#image").attr('src', '');
                $('#image').hide(1000);
                $('#popup').fadeOut(500);
                $("#serial").val(''); $("#model").val(''); $("#malfunction").val(''); $("#client").val(''); $("#number").val(''); $("#cost_total").val(''); $("#manager_notes").val('');
                table.ajax.reload();
            });

            const orderStatus = {
                1:'<?php _e('Accepted', 'service');?>',
                2:'<?php _e('Repair', 'service');?>',
                3:'<?php _e('Reconciliation', 'service');?>',
                4:'<?php _e('Waiting', 'service');?>',
                5:'<?php _e('Ready', 'service');?>',
                6:'<?php _e('Refusal', 'service');?>',
                7:'<?php _e('Issued', 'service');?>'
            };

            function numberToStatus(arr, status){
                for (let i of Object.keys(arr)) {
                    if( arr[i] === status ) return i;
                }
                return 0;
            }

            function checkLuna(value){
                if (!/^\d{15}$/.test(value)) return false;
                let sum = 0;
                for (let i = 0; i < value.length; i++) {
                    let cardNum = parseInt(value[i]);
                    if ((value.length - i) % 2 === 0) {
                        cardNum = cardNum * 2;
                        if (cardNum > 9) {
                            cardNum = cardNum - 9;
                        }
                    }
                    sum += cardNum;
                }
                if(sum % 10 === 0){
                    return true;
                }
            }

            function print(doc, id){
                $.getJSON(ajaxurl,{action: 'print_documents', data:doc, id:id}, function(data){
                    $('#print').remove();
                    $('<iframe>', {'id': 'print', 'style': 'width:0px; height:0px; border:0px;', 'srcdoc': data.html}).appendTo('body');
                    $('#print').load(function(){
                        this.contentWindow.print();
                        $(this).unbind('load');
                    });
                });
            }

            $('#number').mask('+7(999) 999-99-99', {
                onComplete: function(){ $('#client').focus(); }
            });

            $("#number").bind("input", function() {
                table.ajax.reload();
            });

            $("#serial").bind("input", function() {
                const digits = this.value;
                if (checkLuna(digits)) {
                    $.getJSON(ajaxurl, {action: 'insert_tac_number', 'search_tac_number': digits.substr(0, 8)}, function (data) {
                        table.ajax.reload();
                        $("#model").val(data.model);
                    });

                    if ( $("#model").val() === '' && <?php echo get_option( 'service_imei_api_key_status' ); ?> ) {
                        $.getJSON(ajaxurl, {action: 'imei', 'imei': $("#serial").val()}, function (data) {
                            if (data.success === true) {
                                $('#model').val(data.data.name);
                                $("#image").attr('src', data.data.device_image);
                                $('#image').show(1000);
                            } else {
                                $("#model").focus();
                                alert("The imei was not found in the database");
                            }
                        });
                    }
                }
            });


            $(this).bind("input", function() {
                $("#cost_total").val($("#cost_total").val().replace(/[^0-9]/g, ''));
                if ($("#serial").val() != '' && $("#model").val() != '' && $("#malfunction").val() != '' && $("#client").val() != '' && $("#number").val() != '' && $("#cost_total").val() != ''){
                    $("#insert_db").show(1000);
                } else {
                    $("#insert_db").hide(1000);
                }
            });

            $("#insert_db").click(function(){
                $.ajax ({
                    url: '<?php echo get_rest_url(0, 'service/new-order'); ?>',
                    type: 'POST',
                    beforeSend : function ( xhr ) {
                        xhr.setRequestHeader( 'X-WP-Nonce', '<?php echo wp_create_nonce( 'wp_rest' );?>' );
                    },
                    data: {
                        serial:        $("#serial").val(),
                        model:         $("#model").val(),
                        malfunction:   $("#malfunction").val(),
                        client:        $("#client").val(),
                        number:        $("#number").val().replace(/[^0-9]/g,''),
                        cost_total:    $("#cost_total").val(),
                        manager_notes: $("#manager_notes").val()
                    }
                }).done(function(data){
                    if (data.id){
                        print('service_print_receipt', data.id);
                        $("#serial").val(''); $("#model").val(''); $("#malfunction").val(''); $("#client").val(''); $("#number").val(''); $("#cost_total").val(''); $("#manager_notes").val('');
                        $('#popup').fadeOut(1000);
                        $("#insert_db").hide(500);
                        $("#image").attr('src', '');
                        $('#image').hide(1000);
                        table.ajax.reload();
                    } else alert('Happened mistake');
                });
            });

            let table = $('#table_repair').DataTable({
				
				language: {
					url: '<?php echo plugins_url('lang/', dirname(__FILE__)) . get_locale();?>.json'
				},

                dom: 'Bfrtip',
                "searching": true,
                buttons: [
                    {
                        text: '<?php _e('New order', 'service');?>',
                        action: function ( e, dt, node, config ) {
                            $('#popup').fadeIn(500);
                        }
                    }
                ],
                serverSide: true,
                bFilter: false,
                ajax :{
                    url: '<?php echo get_rest_url(0, 'service/orders'); ?>',
                    type: 'GET',

                    beforeSend : function ( xhr ) {
                        xhr.setRequestHeader( 'X-WP-Nonce', '<?php echo wp_create_nonce( 'wp_rest' );?>' );
                    },



                    dataSrc: function ( json ) {

                        if ('<?php echo current_user_can('manage_options');?>' == 0) {
                            table.column(0).visible(false);
                        } else {
                            table.select.style( 'multi' );
                            table.select.selector( 'td:first-child' );
                        }
                        return json.data;
                    }

                },

                createdRow: function(row, data, index){

                    if (data.status == 7) {
                        $(row).closest('tr').css('backgroundColor', 'gray');
                    }

                },

                columns: [

                    {data: 'id',
                        render : function (data, type, row) {
                            return '<input type="checkbox" id ="' + data + '">'
                        }
                    },

                    {data: 'id_label', title: '№'},

                    {data: 'date', title: '<?php _e('Date', 'service');?>'},

                    {data: 'model', title: '<?php _e('Model', 'service');?>',
                        render : function (data, type, row) {
                            return '<b>' + data + '</b><br><span class="serial">' + row.serial + '</span>'
                        }
                    },

                    {data: 'malfunction', title: '<?php _e('Malfunction', 'service');?>'},

                    {data: 'client', title: '<?php _e('Client', 'service');?>',
                        render : function (data, type, row) {
                            return '<b>' + data + '</b><br><span class="number">' + row.number + '</span>'
                        }
                    },

                    {data: 'work', title: '<?php _e('Work', 'service');?>',
                        render : function (data, type, row) {
                            return '<input type="text" class="work" value = "'+data+'" >'
                        }
                    },

                    {data: 'cost_spare', title: '<?php _e('Cost spare', 'service');?>',
                        render : function (data) {
                            return '<input type="text" class="cost_spare" value = "'+data+'" size=5>'
                        }
                    },

                    {data: 'cost_total', title: '<?php _e('Total cost', 'service');?>',
                        render : function (data) {
                            return '<input type="text" class="cost_total" value = "'+data+'" size=5>'
                        }
                    },

                    {data: 'status', title: '<?php _e('Status', 'service');?>',
                        render : function (data, type, row) {
                            let select = '<select id="'+row.id+'"><option>'+orderStatus[data]+'</option>';
                            for (let i = 1; i<8; i++){
                                if (Number(data) !== i){
                                    select += '<option>'+orderStatus[i]+'</option>';
                                }
                            }
                            return select;
                        }
                    },

                    {data: 'manager_notes', title: '<?php _e('Notes', 'service');?>',
                        render : function (data, type, row) {
                            return '<input type="text" class="manager_notes" value = "'+data+'" >'
                        }
                    }


                ],

                scrollX: true


            });


            let delete_row;
            table.on( 'deselect', function ( e, dt, type, indexes ) {
                if ( type === 'row' ) {
                    delete_row = table.rows( { selected: true } ).data().pluck( 'id' ).toArray();
                    const id = table.rows(indexes).data().pluck('id')[0];
                    $('#'+ id).prop('checked', false);
                    if(table.rows('.selected').data().length === 0){
                        $('#delete').hide(1000);
                    }
                }
            });

            table.on( 'select', function ( e, dt, type, indexes ) {
                if ( type === 'row' ) {
                    delete_row = table.rows( { selected: true } ).data().pluck( 'id' ).toArray();
                    const id = table.rows(indexes).data().pluck('id')[0];
                    $('#'+ id).prop('checked', true);
                    $('#delete').show(1000);
                }
            });

            $('#delete').click(function(){
                if (confirm('Are you sure you want to delete it? Recovery is impossible!')) {
                    $.get(ajaxurl, {action: 'delete_orders', delete : delete_row}, function (data) {
                        $('#delete').hide(1000);
                        table.ajax.reload();
                    });
                }
            });

            $('.receipt').click(function(event) {
                let s = "";
                if(event.target.id !== ('cost_total')) {
                    $("#" + event.target.id).autocomplete({
                        source: function (request, response) {
                            if (event.target.id === "number") {
                                s = $("#" + event.target.id).val().replace(/\D+/g, '');
                            } else {
                                s = $("#" + event.target.id).val();
                            }
                            $.getJSON(ajaxurl, {action: 'live_search', field: event.target.id, table: 'service', search: s}, function (data) {
                                response(data);
                            });
                        }, select: function (event, ui) {

                            if (event.target.id === "number") {
                                $.getJSON(ajaxurl, {action: 'insert_client_number', 'search_number': ui.item.value}, function (data) {
                                    table.ajax.reload();
                                    $("#client").val(data.client);
                                });
                            }

                            if (event.target.id === "serial") {
                                $.getJSON(ajaxurl, {action: 'insert_tac_number', 'search_tac_number': ui.item.value}, function (data) {
                                    table.ajax.reload();
                                    $("#model").val(data.model);
                                });
                            }

                        }
                    });
                }
            });

            $('#table_repair').on('change', 'select', function() {
                let id =  $(this).attr('id');
                let work = $(this).closest('tr').find('.work').val();
                let cost_spare = $(this).closest('tr').find('.cost_spare').val();
                let cost_total = $(this).closest('tr').find('.cost_total').val();
                let status = Number(numberToStatus(orderStatus, $(this).find(':selected').text()));
                let val = this;
                $.post(ajaxurl, {action: 'update_order_info','status':status, 'id':id, 'work':work, 'cost_spare':cost_spare, 'cost_total':cost_total}, function(data){
                    $(val).closest('tr').css('backgroundColor', (data) ? 'green' : 'red');
                    if(status===7){
                        print('service_print_warranty', id);
                    }
                    table.ajax.reload();
                });
            });

            $('#table_repair').on('input', 'input', function () {
                let val = this.value;
                $(this).autocomplete({
                    source: function(request, response) {
                        $.getJSON(ajaxurl, {action:'live_search', field:'work', table:'service', search:val}, function(data) {response(data);});
                    }
                });
            });

        });
	</script>