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
	
	unset($print_drives);
	
	$get_info_select = get_table_order($select_db_info, $sort_db_info, 1);
	
	$i=1;
	$i_empty=1;
	$i_drive=1;
	
	$array_groups = $get_groups;
	$array_locations = $get_locations;
	$print_drives = array();
	$data = array();
	$raw_devices = array();
	
	list($table_info_order_user, $table_info_order_system, $table_info_order_name, $table_info_order_full, $table_info_order_forms) = get_table_order($select_db_info, $sort_db_info);
	
	$arr_length = count($table_info_order_user);
	for($i=0;$i<$arr_length;$i++) {
		$table_info_order_name_html .= "
		<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">
			<b style=\"cursor: help;\" title=\"" . $table_info_order_full[$i] . "\">" . $table_info_order_name[$i] . "</b><br />
			<button type=\"submit\" name=\"sort\" value=\"info:asc:" . $table_info_order_user[$i] . "\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px;\" /><i style=\"font-size: 15px;\" class=\"fa fa-solid fa-sort-up\"/></i></button>
			<button type=\"submit\" name=\"sort\" value=\"info:desc:" . $table_info_order_user[$i] . "\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px;\" /><i style=\"font-size: 15px;\" class=\"fa fa-solid fa-sort-down\"/></i></button>
		</td>";
	}
	
	foreach($devices as $hash => $data) { // array as hash => array(raw/formatted)
		$raw_devices[] = array("hash" => $hash)+$data["raw"];
	}
	
	unset($data);
	
	$db_sort = explode(",", $get_info_select["db_sort"]);
	$sort_dynamic = array();
	foreach($db_sort as $sort_by) {
		list($sort, $dir, $flag) = explode(" ", $sort_by);
		$dir = ( ($dir == 'SORT_ASC') ? SORT_ASC : SORT_DESC );
		$$sort  = array_column($raw_devices, $sort);
		$sort_dynamic[] = &$$sort;
		$sort_dynamic[] = $dir;
		if($flag) { 
			$sort_dynamic[] = $flag;
		}
	}
	call_user_func_array('array_multisort', array_merge($sort_dynamic, array(&$raw_devices)));
	
	foreach($raw_devices as $key => $data) {
		if(empty($data["status"]) && $data["groupid"]) {
			$hash = $data["hash"];
			
			$data = $devices[$hash];
			
			$formatted = $data["formatted"];
			$raw = $data["raw"];
			$data = $data["raw"];
			
			$gid = $data["groupid"];
			
			extract($array_groups[$gid]);
			
			$group_assign = ( empty($group_name) ? $data["groupid"] : $group_name );
			
			$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
			
			$total_trays = ( empty($grid_trays) ? $grid_columns * $grid_rows : $grid_trays );
			$total_trays_group += $total_trays;
			
			if($biggest_tray_group < $total_trays) {
				$biggest_tray_group = $total_trays;
			}
			
			if(!$tray_direction) { $tray_direction = 1; }
			$tray_number_override = tray_number_assign($grid_columns, $grid_rows, $tray_direction, $grid_count);
			
			if(!isset($tray_start_num)) { $tray_start_num = 1; }
			$tray_number_override_start = $tray_start_num;
			
			$total_main_trays = 0;
			if($total_trays > ($grid_columns * $grid_rows)) {
				$total_main_trays = $grid_columns * $grid_rows;
				$total_rows_override_trays = ($total_trays - $total_main_trays) / $grid_columns;
				$grid_columns_override_styles = str_repeat(" auto", $total_rows_override_trays);
			}
			
			$drive_tray_order[$hash] = get_tray_location($db, $hash, $gid);
			$drive_tray_order[$hash] = ( !isset($drive_tray_order[$hash]) ? $tray_assign : $drive_tray_order[$hash] );
			
			if($tray_number_override[$drive_tray_order[$hash]]) {
				$drive_tray_order_assign = $tray_number_override[$drive_tray_order[$hash]];
				$physical_traynumber = ( !isset($tray_number_override_start) ? --$tray_number_override[$drive_tray_order[$hash]] : ($tray_number_override_start + $tray_number_override[$drive_tray_order[$hash]] - 1));
			}
			else {
				$drive_tray_order_assign = $drive_tray_order[$hash];
				$physical_traynumber = ( !isset($tray_number_override_start) ? --$drive_tray_order[$hash] : $drive_tray_order[$hash]);
			}
			
			$columns_info_out = array(
				"groupid" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . stripslashes(htmlspecialchars($formatted["group_name"])) . "</td>",
				"tray" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $physical_traynumber . "</td>",
				"device" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $formatted["device"] . "</td>",
				"pool" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $formatted["pool"] . "</td>",
				"name" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $formatted["name"] . "</td>",
				"node" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $formatted["node"] . "</td>",
				"lun" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $formatted["lun"] . "</td>",
				"manufacturer" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $formatted["manufacturer"] . "</td>",
				"model" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $formatted["model"] . "</td>",
				"serial" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $formatted["serial"] . "</td>",
				"capacity" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["capacity"] . "</td>",
				"cache" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["cache"] . "</td>",
				"rotation" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["rotation"] . "</td>",
				"formfactor" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["formfactor"] . "</td>",
				"smart_status" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: center;\">" . $formatted["smart_status"] . "</td>",
				"temperature" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: left;\">" . $formatted["temp"] . " (" . $formatted["hotTemp"] . "/" . $formatted["maxTemp"] . ")</td>",
				"powerontime" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["powerontime"] . "</span></td>",
				"loadcycle" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["loadcycle"] . "</td>",
				"nvme_percentage_used" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["nvme_percentage_used"] . "</td>",
				"smart_units_read" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["smart_units_read"] . "</td>",
				"smart_units_written" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["smart_units_written"] . "</td>",
				"nvme_available_spare" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["nvme_available_spare"] . "</td>",
				"nvme_available_spare_threshold" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["nvme_available_spare_threshold"] . "</td>",
				//"benchmark_r" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["benchmark_r"] . "</td>",
				//"benchmark_w" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["benchmark_w"] . "</td>",
				"installed" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["installed"] . "</td>",
				"removed" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["removed"] . "</td>",
				"manufactured" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["manufactured"] . "</td>",
				"purchased" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["purchased"] . "</td>",
				"warranty" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $formatted["warranty"] . "</td>",
				"comment" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . bscode2html(stripslashes(htmlspecialchars($formatted["comment"]))) . "</td>"
			);
			
			$print_drives[$i_drive] = "<tr style=\"background: #" . $color_array[$hash] . ";\">";
			$arr_length = count($table_info_order_system);
			for($i=0;$i<$arr_length;$i++) {
				$print_drives[$i_drive] .= $columns_info_out[$table_info_order_system[$i]];
			}
			$print_drives[$i_drive] .= "</tr>";
			
			$i_drive++;
		}
	}
?>
<?php if($db_update == 2) { print("<h3>Page unavailable due to database error.</h3><!--"); } ?>
<h2 style="margin-top: -10px; padding: 0 0 25px 0;">Disk Information</h2>
<form action="" method="post">
<table style="width: 800px; border-spacing: 3px; border-collapse: separate;">
	<tr>
		<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_parity); ?>">
			<b><?php echo (!$dashboard_widget ? "Parity" : "Critical") ?></b>
		</td>
		<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_unraid); ?>">
			<b><?php echo (!$dashboard_widget ? "Data" : "Warning") ?></b>
		</td>
		<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_cache); ?>">
			<b><?php echo (!$dashboard_widget ? "Cache" : "Normal") ?></b>
		</td>
		<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_others); ?>">
			<b><?php echo (!$dashboard_widget ? "Unassigned devices" : "Temperature N/A") ?></b>
		</td>
		<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_empty); ?>">
			<b>Empty trays</b>
		</td>
	</tr>
</table>
<div><br /><br /><br /></div>
<blockquote class='inline_help'>
	<dt>"Power On Hours" and "Warranty" hover</dt>
	<dd>Hover over text to get additional information or simpler readout.</dd>
	<br />
</blockquote>
<table style="width: 0;">
	<tr style="border: solid 1px #000000;">
		<?php print($table_info_order_name_html); ?>
	</tr>
	<?php 
		$i=1;
		while($i <= count($print_drives)) {
			print($print_drives[$i]);
			$i++;
		}
	?>
</table>
</form>
<h2>
	Export:
	<a href="<?php echo DISKLOCATION_PATH ?>/pages/export_tsv.php?download_csv=1">formatted</a>
	|
	<a href="<?php echo DISKLOCATION_PATH ?>/pages/export_tsv.php?download_csv=1&amp;raw_data_csv=1">raw data</a>
	
</h2>
<blockquote class='inline_help'>
	<p>Download a TSV file based upon the selection and ordering of the Information table above. If you're using HTML in the comment section, it will include HTML code if inserted and will not parse it anyhow. TSV is the same as CSV, but the extension for TAB delimited instead of COMMA.</p>
	<p>Output raw data will not format numbers for the file output. Eg. HDD sizes like 8.0TB will be 8001563222016 instead. However, the SMART units read and written is calculated with the logical block size and shown in raw after that.</p>
</blockquote>
<?php if($db_update == 2) { print("-->"); } ?>
