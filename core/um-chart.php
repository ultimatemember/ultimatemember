<?php

class UM_Chart {

	function __construct() {

	}
	
	/***
	***	@Create a new chart
	***/
	function create( $args=array() ){
	
		$defaults = array(
			'id' => 0,
			'type' => 'LineChart',
			'data' => null,
			'x_label' => null,
			'y_label' => null,
			'vertical_max_lines' => 6,
			'colors' => '#3ba1da',
			'backgroundcolor' => 'transparent',
			'basetextcolor' => '#666',
			'basebordercolor' => '#bbb',
			'days' => 30
		);
		
		$args = wp_parse_args( $args, $defaults );
		extract($args);

		if ($type == 'LineChart'){
			$this->linechart( $args );
		}

	}
	
	/***
	***	@LineChart
	***/
	function linechart(	$args ){
		
		global $ultimatemember;
		extract($args);
		
		?>
		
		<script type="text/javascript">

		google.load("visualization", "1", {packages:["corechart"]});

		google.setOnLoadCallback(drawChart);

		function drawChart() {
			
			var data = new google.visualization.DataTable();
			data.addColumn('string', '<?php echo $x_label; ?>');
			data.addColumn('number', '<?php echo $y_label; ?>');
			
			<?php
			
				if ($data == 'new_users'){
					$data_y = $ultimatemember->datetime->get_last_days( $days );
				}
				
				if ($data == 'deleted_users'){
					$data_y = $ultimatemember->query->deleted_users_per_day( $days );
				}
			
				if (isset($data_y) && !empty($data_y)){
				
					foreach($data_y as $key => $val){
					
						if ($data == 'new_users'){
						$row_value = $ultimatemember->query->count_users_registered_on( $key );
						?>data.addRow(['<?php echo $val; ?>', <?php echo $row_value; ?>]);<?php
						}
						
						if ($data == 'deleted_users'){
						$key = $ultimatemember->datetime->format( $key, 'm/d' );
						?>data.addRow(['<?php echo $key; ?>', <?php echo $val; ?>]);<?php
						}
				
					}
				
				}
				
			?>
			
			var min_data = 0;
			var max_data = data.getColumnRange(1).max;
			
			var vgrid_count = <?php echo $vertical_max_lines; ?>;
			var hgrid_count = Math.floor( data.getNumberOfRows() / 4 );

			/* Options */
			var options = {
				backgroundColor: '<?php echo $backgroundcolor; ?>',
				colors: ['<?php echo $colors; ?>'],
				curveType: 'function',
				pointSize: 8,
				lineWidth: 4,
				vAxis:{
					baselineColor: '<?php echo $basebordercolor; ?>',
					gridlineColor: '<?php echo $basebordercolor; ?>',
					gridlines: {color: 'transparent', count: vgrid_count},
					textStyle: {color: '<?php echo $basetextcolor; ?>', fontSize: 12 },
					format: '#',
					viewWindow: {min: min_data, max: max_data + 10}
				},
				hAxis:{
					textStyle: {color: '<?php echo $basetextcolor; ?>', fontSize: 12, italic: true },
					showTextEvery: hgrid_count,
					maxAlternation: 1,
					maxTextLines: 1
				},
				legend: {
					position: 'top',
					alignment: 'start',
					textStyle: {color: '<?php echo $basetextcolor; ?>', fontSize: 13}
				},
				tooltip: {
					textStyle: {color: '<?php echo $basetextcolor; ?>', fontSize: 12}
				},
				chartArea: {
					top:50,left:30,width: '95%', 'height' : ( vgrid_count * 50 ) - 100,
					backgroundColor: {
						stroke: '<?php echo $basebordercolor; ?>',
						strokeWidth: 1
					}
				},
				width: '100%',
				height: ( vgrid_count * 50 )
			};
			
			var chart = new google.visualization.LineChart(document.getElementById('chart_<?php echo $data . $id; ?>'));
			chart.draw(data, options);
			
		}

		</script>

		<div id="chart_<?php echo $data . $id; ?>">

		</div>
		
		<?php
		
	}
	
}