<?php

$settings_editor = array(
	'wpautop'       => 0,
	'media_buttons' => 0,
	'textarea_name' => '',
	'textarea_rows' => 20,
	'tabindex'      => null,
	'editor_css'    => '',
	'editor_class'  => '',
	'editor_height' => 300,
	'teeny'         => 0,
	'dfw'           => 0,
	'tinymce'       => 1,
	'quicktags'     => 1,
	'drag_drop_upload' => false
);
?>

<script>
    jQuery(document).ready(function($) {

        $.get(ajaxurl, {action: 'get_api_keys'}, function (data) {
            data.imeiApiKey ? $('#imei_api_key').val(data.imeiApiKey) : $('#imei_api_key').attr("placeholder", "NOT SET");
            test_api_keys();
        });

        function test_api_keys(){
            $.get(ajaxurl, {action: 'test_api_keys', test_imei_api_key:$('#imei_api_key').val()}, function (data) {
                $('#imei_api_key').css('backgroundColor', data.testImeiApiKey ? 'green' : 'red');
            });
        }

        $("#imei_api_key").mask('AAAAAAAAAAAAAAAAAAAA', {
            onComplete: function(){
                test_api_keys();
            }, onChange: function () {
                $('#imei_api_key').css('backgroundColor','');
            }
        });

        function test_print(doc){
            $('#print').remove();
            $('<iframe>', {'id': "print", 'style': "width:0px; height:0px; border:0px;", 'srcdoc': tinyMCE.get(doc).getContent()}).appendTo('body');
            $('#print').load(function(){
                this.contentWindow.print();
                $(this).unbind('load');
            });
        }

        $("#test_print_receipt").click(function(){
            $('#edit_print_receipt-tmce').click();
            test_print('edit_print_receipt');
        });

        $("#test_print_warranty").click(function(){
            $('#edit_print_warranty-tmce').click();
            test_print('edit_print_warranty');
        });

        $("#save_settings").click(function(){
            $('#edit_print_receipt-tmce').click();
            $('#edit_print_warranty-tmce').click();
            $.post(ajaxurl, {action: 'save_settings',
                print_receipt_save: tinyMCE.get('edit_print_receipt').getContent(),
                print_warranty_save: tinyMCE.get('edit_print_warranty').getContent(),
                imei_api_key_save: $('#imei_api_key').val() });
        });
    });
</script>

<form style="width:50%">
	<h2><?php _e('Receipt ticket:', 'service');?></h2>
	<?php wp_editor( wp_unslash(get_option('service_print_receipt')), 'edit_print_receipt', $settings_editor ); ?>
</form>
<p><button id="test_print_receipt" class="button"><?php _e('Test', 'service');?></button></p>
<br>
<form style="width:50%">
	<h2><?php _e('Warranty ticket:', 'service');?></h2>
	<?php wp_editor( wp_unslash(get_option('service_print_warranty')), 'edit_print_warranty', $settings_editor ); ?>
</form>
<p><button id="test_print_warranty" class="button"><?php _e('Test', 'service');?></button></p>
<br>

<table>
	<h2>IMEI API KEY:</h2>
	<td><input id="imei_api_key" size="50" type="text" autocomplete="off"> <span><a href="https://imeidb.xyz/" target="_blank" rel="noopener">Get</a></span></td>
<?php if(get_option('service_imei_api_key_status') == 'true') (new imei())->get_balance();?>
</table>
<br>
<p><button id="save_settings" class="button"><?php _e('Save settings', 'service');?></button></p>