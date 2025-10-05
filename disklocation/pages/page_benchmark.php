<?php
	/*
	 *  Copyright 2019-2025, Ole-Henrik Jakobsen
	 *
	 *  This file is part of Disk Location for Unraid.
	 *
	 *  Disk Location for Unraid is free software: you can redistribute it and/or modify
	 *  it under the terms of the GNU General Public License as published by
	 *  the Free Software Foundation, either version 3 of the License, or
	 *  (at your option) any later version.
	 *
	 *  Disk Location for Unraid is distributed in the hope that it will be useful,
	 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
	 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 *  GNU General Public License for more details.
	 *
	 *  You should have received a copy of the GNU General Public License
	 *  along with Disk Location for Unraid.  If not, see <https://www.gnu.org/licenses/>.
	 *
	 */
	
	if(!empty($disklocation_error) && isset($_POST["save_benchmark_settings"])) {
		$i=0;
		print("<h2 style=\"margin: 0; color: #FF0000; font-weight: bold;\">ERROR Could not save the configuration (previous form restored):</h2><br /><span style=\"font-size: medium;\">");
		while($i < count($disklocation_error)) {
			print("&middot; " . $disklocation_error[$i] . "<br />");
			$i++;
		}
		print("</span><hr style=\"clear: both; border-bottom: 1px solid #FF0000;\" /><br /><br /><br />");
	}
	
	$print_benchmark = "
		<form action=\"\" method=\"post\">
			<b>Clicking the button will start the benchmark directly.</b>
			Only one record is stored per day, the latest run will be stored.<br />
			This might take a long time to complete, typically 5-15 seconds per drive, per iteration.<br />
			<b>Estimated time of completion with " .$count_installed_devices . " drives is " . round(($count_installed_devices * 7.5 * $bench_iterations) / 60) . " minutes.</b>
			<br />
			<input type='button' " . ( (!file_exists(DISKLOCATION_DEVICES)) ? "disabled=\"disabled\"" : null ) . " value='Start benchmark' onclick='openBox(\"" . BENCHMARK_URL . "?start=1\",\"Benchmark running\",600,800,true,\"loadlist\",\":return\")'>
			
			<blockquote class='inline_help'>
				<ul>
					<li>This will perform a read benchmark via hdparm</li>
					<li>You can also run this from the shell (average is presented):<br />
					<code style=\"white-space: nowrap;\">php -f " . BENCHMARK_FILE . "</code></li>
				</ul>
			</blockquote>
		</form>
	";
	
	$get_info_select = get_table_order($select_db_info, ( !empty($sort_db_info_override) ? $sort_db_info_override : $sort_db_info ), 1);
	$raw_devices = array();
	
	foreach($devices as $hash => $data) { // array as hash => array(raw/formatted)
		$raw_devices[] = array("hash" => $hash)+$data["raw"];
	}
	
	unset($data);
	
	$db_sort = explode(",", $get_info_select["db_sort"]);
	$sort_dynamic = array();
	foreach($db_sort as $sort_by) {
		list($sort, $dir, $flag) = explode(" ", $sort_by);
		$dir = ( ($dir == 'SORT_ASC') ? SORT_ASC : SORT_DESC );
		$$sort = ( is_array($raw_devices) ? array_column($raw_devices, $sort) : null );
		$sort_dynamic[] = &$$sort;
		$sort_dynamic[] = $dir;
		if($flag) { 
			$sort_dynamic[] = $flag;
		}
	}
	( is_array($raw_devices) ? call_user_func_array('array_multisort', array_merge($sort_dynamic, array(&$raw_devices))) : null );
	
	$benchmark_array = array();
	
	foreach($raw_devices as $hash => $data) { // array as hash => array(raw/formatted)
		$hash = $data["hash"];
		if(!empty($devices[$hash]["benchmark"]) && empty($devices[$hash]["raw"]["status"])) {
			$benchmark = array();
			//$speed_values = array_slice($devices[$hash]["benchmark"], $bench_last_values);
			$speed_values = $devices[$hash]["benchmark"];
			$speed_graph_text = array();
			$graph_height = 150; // also used for calculating the graph position y
			
			sort($speed_values);
			ksort($devices[$hash]["benchmark"]);
			
			$benchmark_array[$hash]["manufacturer"] = $devices[$hash]["raw"]["manufacturer"];
			$benchmark_array[$hash]["model"] = $devices[$hash]["raw"]["model"];
			$benchmark_array[$hash]["serial"] = $devices[$hash]["formatted"]["serial"];
			$benchmark_array[$hash]["node"] = $devices[$hash]["raw"]["node"];
			$benchmark_array[$hash]["name"] = $devices[$hash]["raw"]["name"];
			$benchmark_array[$hash]["rotation"] = $devices[$hash]["formatted"]["rotation"];
			$benchmark_array[$hash]["benchmark"] = $devices[$hash]["benchmark"];
			//$benchmark = array_slice($benchmark_array[$hash]["benchmark"], $bench_last_values);
			$benchmark = $benchmark_array[$hash]["benchmark"];
			
			$speed_graph_text["slow"] = floor($speed_values[array_key_first($speed_values)] / 100) * 100;
			$speed_graph_text["fast"] = ceil($speed_values[array_key_last($speed_values)] / 100) * 100;
			if($speed_graph_text["slow"] == $speed_graph_text["fast"]) {
				$speed_graph_text["slow"] = $speed_graph_text["slow"]-50;
				$speed_graph_text["fast"] = $speed_graph_text["fast"]+50;
			}
			
			//$speed_graph_text["midl"] = ((($speed_graph_text["fast"] + $speed_graph_text["slow"]) / 2) * 2) / 2;
			$speed_graph_text["3333"] = round((33.33 * ($speed_graph_text["fast"] - $speed_graph_text["slow"]) / 100) + $speed_graph_text["slow"]);
			$speed_graph_text["6666"] = round((66.66 * ($speed_graph_text["fast"] - $speed_graph_text["slow"]) / 100) + $speed_graph_text["slow"]);
			
			$graph_pos = "";
			$graph_pos_dots = "";
			
			$graph_dates_x = 100;
			foreach($benchmark as $date => $speed) {
				$graph_dates .= "<text x=\"$graph_dates_x\" y=\"170\">" . $date . "</text>\n";
				
				$percent = round(((($speed - $speed_graph_text["slow"]) * 100) / ($speed_graph_text["fast"] - $speed_graph_text["slow"])), 1);
				$graph_pos_y = $graph_height - round(($percent * $graph_height) / 100);
				$graph_pos .= "" . $graph_dates_x . "," . $graph_pos_y . "\n";
				
				$graph_pos_dots .= "<circle class=\"bench-graph-dot\" cx=\"" . $graph_dates_x . "\" cy=\"" . $graph_pos_y . "\" data-value=\"" . round($speed) . "\" r=\"5\" title=\"" . $date . "\"><title>" . round($speed) . " MB/s</title></circle>\n";
				
				$graph_dates_x+=100;
			}
			
			sort($speed_graph_text);
			
			$graph_speed_y = 160;
			for($i=0;$i<count($speed_graph_text);$i++) {
				$graph_speed .= "<text x=\"42\" y=\"$graph_speed_y\">" . $speed_graph_text[$i] . "</text>\n";
				$graph_speed_y-=50;
			}
			
			$graph_out .= "
				<p><b>" . $benchmark_array[$hash]["manufacturer"] . " " . $benchmark_array[$hash]["model"] . " (" . $benchmark_array[$hash]["serial"] . ") " . $benchmark_array[$hash]["rotation"] . " [<a href=\"/Main/Device?name=" . $benchmark_array[$hash]["name"] . "\">" . $benchmark_array[$hash]["name"] . "</a>]</b></p>
				<svg version=\"1.2\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" class=\"bench-graph\">
				<defs>
					<pattern id=\"grid\" width=\"100\" height=\"50\" patternUnits=\"userSpaceOnUse\">
					<path d=\"M 100 0 L 0 0 0 50\" fill=\"none\" stroke=\"#" . $bgcolor_empty . "\" stroke-width=\"1\"></path>
					</pattern>
				</defs>
				<rect x=\"50\" width=\"calc(100% - 50px)\" height=\"" . $graph_height . "px\" fill=\"url(#grid)\" stroke=\"#" . $bgcolor_others . "\"></rect>

				<g class=\"label-title\">
					<text x=\"-75\" y=\"10\" transform=\"rotate(-90)\">Speed MB/s</text>
				</g>
				<g class=\"x-labels\">
					" . $graph_dates . "
				</g>
				<g class=\"y-labels\">
					" . $graph_speed . "
				</g>
				
				<polyline fill=\"none\" stroke=\"#" . $bgcolor_unraid . "\" stroke-width=\"2\" points=\"
					" . $graph_pos . "
				\"></polyline>
				<g>
					" . $graph_pos_dots . "
				</g>
				</svg>
			";
			
			unset($graph_dates, $graph_speed);
		}
	}
?>
<table><tr><td style="padding: 10px 10px 10px 10px;">
<h2 style="margin-top: -10px; padding: 0 0 <?php print($unraid_version_720 ? "0" : "25px") ?> 0;">Benchmark</h2>
<style type="text/css">      
	.bench-graph {
		padding: 10px; 
		height: 200px;
		width: <?php print($bench_last_values); ?>50px;
	}
	.bench-graph .x-labels {
		fill: #F2F2F2;
		text-anchor: middle;
	}
	.bench-graph .y-labels {
		fill: #F2F2F2;
		text-anchor: end;
	}
	.label-title {
		text-anchor: middle;
		font-size: 12px;
		fill: #F2F2F2;
	}
	.bench-graph-dot {
		fill: #<?php print($bgcolor_parity); ?>;
		stroke-width: 0;
		stroke: #<?php print($bgcolor_parity); ?>;
	}
</style>
<form action="" method="post" <?php print($unraid_version_720 ? "style=\"margin: 0;\"" : null) ?>>
	<table>
		<tr>
			<td style="vertical-align: top;">
					<b>Settings:</b><br />
					<input type="number" required min="1" max="10" step="1" name="bench_iterations" value="<?php print($bench_iterations); ?>" style="margin: 0; width: 20px;" />
					Iterations to run
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" name="bench_median" value="1" <?php if(!empty($bench_median)) echo "checked"; ?> />
					Skip slowest and fastest results
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" name="bench_force" value="1" <?php if(!empty($bench_force)) echo "checked"; ?> />
					Ignore SMART power state
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" name="bench_auto_cron" value="1" <?php if(!empty($bench_auto_cron)) echo "checked"; ?> />
					Run monthly auto benchmark
					&nbsp;&nbsp;&nbsp;
					<input type="number" required min="1" max="1000" step="1" name="bench_last_values" value="<?php print($bench_last_values); ?>" style="margin: 0; width: 30px;" />
					Last benchmarks shown
					&nbsp;&nbsp;&nbsp;
					<input type="submit" name="save_benchmark_settings" value="Save" />
				<blockquote class='inline_help'>
					<ol>
						<li>Set the number of cycles that hdparm should execute per drive.</li>
						<li>Choose if you want to use the slowest and the fastest results, or skip them</li>
						<li>Choose to ignore if a drive is in standby or not, enabling this will spin up sleeping drives.</li>
						<li>Run this benchmark monthly via crontab, default is set at 1st day of the month at 05:00. You can disable this and set up your own crontab using this command:<br />
						<code style="white-space: nowrap;">php -f <?php print(BENCHMARK_FILE); ?> auto silent</code></li>
						<li>Enter how many benchmarks to include in the graph.</li>
					</ol>
				</blockquote>
			</td>
			<td style="vertical-align: top;">
			
			</td>
		</tr>
	</table>
</form>
<hr />
<?php print($print_benchmark); ?>
<hr />
<blockquote class='inline_help'>
	<h3>Graphs</h3>
	<ul>
		<li>Graphs are or will be shown below.</li>
		<li>The sorting order follows the "Information" list.</li>
	</ul>
</blockquote>
<?php if(!empty($graph_out)) { print("<h2>Export: <a href=\"" . DISKLOCATION_PATH . "/pages/export_bench_tsv.php?download_csv=1\">all benchmarks</a></h2><hr />"); } ?>
<div>
<?php 
	if(!empty($graph_out)) { print($graph_out); }
?>
</div>
</td></tr></table>
