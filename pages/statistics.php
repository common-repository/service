<script>
 jQuery(document).ready(function($) {

     function googleChart(title, elem, rows){
         google.charts.setOnLoadCallback(function () {
             const data = new google.visualization.DataTable();
             data.addColumn('string', 'Task');
             data.addColumn('number', '');
             data.addRows(rows);
             const chart = new google.visualization.ColumnChart(document.getElementById(elem));
             chart.draw(data, {'title': title});
         });
     }

     google.charts.load('current', {packages: ["corechart"]});

     $.ajax ({
         url: '<?php echo get_rest_url(0, 'service/statistics'); ?>',
         type: 'GET',
         beforeSend : function ( xhr ) {
             xhr.setRequestHeader( 'X-WP-Nonce', '<?php echo wp_create_nonce( 'wp_rest' );?>' );
         }}).done(function(result){

         googleChart('<?php _e('Day:', 'service');?>', 'day', result.day);
             googleChart('<?php _e('Month:', 'service');?>', 'month', result.month);

             $("#forecast").html('<tr><td colspan="6" style="text-align:right"><?php _e('Average daily income:', 'service');?></td><td>' + result.forecast  + '</td></tr>');
             $("#forecast").append('<tr><td colspan="6" style="text-align:right"><?php _e('Forecasted profit for the month:', 'service');?></td><td>' + result.forecast * <?php echo (int)date('t'); ?> + '</td></tr>');

     })
 });
</script>

<div id="day" style="width: 1000px; height: 300px;"></div>
<div id="month" style="width: 1000px; height: 300px;"></div>
<table style="font: 15px Tahoma; font-weight: 900; color:black;">
<tbody>
<tr>
<td id="forecast"></td>
</tr>
</tbody>
</table>