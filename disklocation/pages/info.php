<?php
	/*
	 *  Copyright 2019-2024, Ole-Henrik Jakobsen
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
	
	if(!$total_groups) {
		$sql = "SELECT * FROM disks WHERE status IS NULL;";
	}
	else {
		//$sql = "SELECT * FROM disks JOIN location ON disks.hash=location.hash WHERE status IS NULL ORDER BY groupid,tray ASC;";
		$sql = "SELECT disks.id,location.id,disks.hash,location.hash,smart_logical_block_size,color,warranty," . implode(",", $get_info_select["sql_select"]) . " FROM disks JOIN location ON disks.hash=location.hash WHERE status IS NULL ORDER BY " . $get_info_select["sql_sort"] . " " . $get_info_select["sql_dir"] . ";";
	}
	
	$i=1;
	$i_empty=1;
	$i_drive=1;
	
	$print_drives = array();
	$datasql = array();
	
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
	
	$results = $db->query($sql);
	while($res = $results->fetchArray(1)) {
		array_push($datasql, $res);
		
		$data = $datasql[$i_drive-1];
		
		$sql = "SELECT * FROM settings_group WHERE id = '" . $data["groupid"] . "'";
		$results2 = $db->query($sql);
		
		while($datagroup = $results2->fetchArray(1)) {
			extract($datagroup);
			$group_name = $datagroup["group_name"];
		}
		$group_assign = ( empty($group_name) ? $data["groupid"] : $group_name );
		
		$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
		
		$hash = $data["hash"];
		$gid = $data["groupid"];

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
		
		$smart_powerontime = ( empty($data["smart_powerontime"]) ? null : seconds_to_time($data["smart_powerontime"] * 60 * 60) );
		$smart_capacity = ( empty($data["smart_capacity"]) ? null : human_filesize($data["smart_capacity"], 1, true) );
		
		$smart_rotation = get_smart_rotation($data["smart_rotation"]);
		
		if($data["smart_rotation"] == -2) {
			$smart_units_read = ( empty($data["smart_units_read"]) ? null : human_filesize(smart_units_to_bytes($data["smart_units_read"], $data["smart_logical_block_size"], true), 1, true) );
			$smart_units_written = ( empty($data["smart_units_written"]) ? null : human_filesize(smart_units_to_bytes($data["smart_units_written"], $data["smart_logical_block_size"], true), 1, true) );
		}
		else {
			$smart_units_read = ( empty($data["smart_units_read"]) ? null : human_filesize(smart_units_to_bytes($data["smart_units_read"], $data["smart_logical_block_size"], true, true), 1, true) );
			$smart_units_written = ( empty($data["smart_units_written"]) ? null : human_filesize(smart_units_to_bytes($data["smart_units_written"], $data["smart_logical_block_size"], true, true), 1, true) );
		}
		
		$date_warranty = "";
		$warranty_expire = "";
		$warranty_left = "";
		if($data["purchased"] && ($data["warranty"] || $data["warranty_date"])) {
			$warranty_start = strtotime($data["purchased"]);
			
			if($warranty_field == "u") {
				$warranty_end = strtotime("" . $data["purchased"] . " + " . $data["warranty"] . " month");
				$warranty_expire = date("Y-m-d", $warranty_end);
				$date_warranty = $data["warranty"] . " months.";
			}
			else {
				$warranty_end = strtotime($data["warranty_date"]);
				$warranty_expire = $data["warranty_date"];
				$date_warranty = $data["warranty_date"];
			}
			
			$warranty_expire_left = $warranty_end-date("U");
			if($warranty_expire_left > 0) {
				$warranty_left = seconds_to_time($warranty_expire_left);
			}
			else {
				$warranty_left = "EXPIRED!";
			}
		}
		
		$smart_temperature = 0;
		$smart_temperature_warning = 0;
		$smart_temperature_critical = 0;
		$unraid_array[$data["devicenode"]]["hotTemp"] = ( $unraid_array[$data["devicenode"]]["hotTemp"] ? $unraid_array[$data["devicenode"]]["hotTemp"] : $GLOBALS["display"]["hot"] );
		$unraid_array[$data["devicenode"]]["maxTemp"] = ( $unraid_array[$data["devicenode"]]["maxTemp"] ? $unraid_array[$data["devicenode"]]["maxTemp"] : $GLOBALS["display"]["max"] );
		
		if(is_numeric($unraid_array[$data["devicenode"]]["temp"]) && is_numeric($unraid_array[$devicenode]["temp"])) {
			switch($display["unit"]) {
				case 'F':
					$smart_temperature = round(temperature_conv($unraid_array[$data["devicenode"]]["temp"], 'C', 'F')) . "°F";
					$smart_temperature_warning = round(temperature_conv($unraid_array[$data["devicenode"]]["hotTemp"], 'C', 'F')) . "°F";
					$smart_temperature_critical = round(temperature_conv($unraid_array[$data["devicenode"]]["maxTemp"], 'C', 'F')) . "°F";
					break;
				case 'K':
					$smart_temperature = round(temperature_conv($unraid_array[$data["devicenode"]]["temp"], 'C', 'K')) . "K";
					$smart_temperature_warning = round(temperature_conv($unraid_array[$data["devicenode"]]["hotTemp"], 'C', 'K')) . "K";
					$smart_temperature_critical = round(temperature_conv($unraid_array[$data["devicenode"]]["maxTemp"], 'C', 'K')) . "K";
					break;
				default:
					$smart_temperature = $unraid_array[$data["devicenode"]]["temp"] . "°C";
					$smart_temperature_warning = $unraid_array[$data["devicenode"]]["hotTemp"] . "°C";
					$smart_temperature_critical = $unraid_array[$data["devicenode"]]["maxTemp"] . "°C";
			}
		}
		
		$columns_info_out = array(
			"groupid" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . stripslashes(htmlspecialchars($group_assign)) . "</td>",
			"tray" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $physical_traynumber . "</td>",
			"device" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["device"] . "</td>",
			"devicenode" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["devicenode"] . "</td>",
			"luname" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["luname"] . "</td>",
			"model_family" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["model_family"] . "</td>",
			"model_name" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["model_name"] . "</td>",
			"smart_serialnumber" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . substr($data["smart_serialnumber"], $dashboard_widget_pos) . "</td>",
			"smart_capacity" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $smart_capacity . "</td>",
			"smart_cache" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ($data["smart_cache"] ? $data["smart_cache"] . "MB" : "") . "</td>",
			"smart_rotation" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $smart_rotation . "</td>",
			"smart_formfactor" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . str_replace(" inches", "&quot;", $data["smart_formfactor"]) . "</td>",
			"smart_status" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: center;\">" . ( empty($data["smart_status"]) ? "FAIL" : "OK" ) . "</td>",
			"smart_temperature" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: left;\">" . $smart_temperature . " (" . $smart_temperature_warning . "/" . $smart_temperature_critical . ")</td>",
			"smart_powerontime" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><span style=\"cursor: help;\" title=\"" . $smart_powerontime . "\">" . $data["smart_powerontime"] . "</span></td>",
			"smart_loadcycle" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ( isset($data["smart_loadcycle"]) ? $data["smart_loadcycle"] : "" ) . "</td>",
			"smart_reallocated_sector_count" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ( isset($data["smart_reallocated_sector_count"]) ? $data["smart_reallocated_sector_count"] : "" ) . "</td>",
			"smart_reported_uncorrectable_errors" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ( isset($data["smart_reported_uncorrectable_errors"]) ? $data["smart_reported_uncorrectable_errors"] : "" ) . "</td>",
			"smart_command_timeout" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ( isset($data["smart_command_timeout"]) ? $data["smart_command_timeout"] : "" ) . "</td>",
			"smart_current_pending_sector_count" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ( isset($data["smart_current_pending_sector_count"]) ? $data["smart_current_pending_sector_count"] : "" ) . "</td>",
			"smart_offline_uncorrectable" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ( isset($data["smart_offline_uncorrectable"]) ? $data["smart_offline_uncorrectable"] : "" ) . "</td>",
			"smart_nvme_percentage_used" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ( is_numeric($data["smart_nvme_percentage_used"]) ? $data["smart_nvme_percentage_used"] . "%" : "" ) . "</td>",
			"smart_units_read" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $smart_units_read . "</td>",
			"smart_units_written" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $smart_units_written . "</td>",
			"smart_nvme_available_spare" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ( is_numeric($data["smart_nvme_available_spare"]) ? $data["smart_nvme_available_spare"] . "%" : "" ) . "</td>",
			"smart_nvme_available_spare_threshold" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ( is_numeric($data["smart_nvme_available_spare_threshold"]) ? $data["smart_nvme_available_spare_threshold"] . "%" : "" ) . "</td>",
			"benchmark_r" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["benchmark_r"] . "</td>",
			"benchmark_w" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["benchmark_w"] . "</td>",
			"manufactured" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["manufactured"] . "</td>",
			"purchased" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["purchased"] . "</td>",
			"warranty_date" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><span style=\"cursor: help;\" title=\"Warranty: " . $date_warranty . " Expires: " . $warranty_left . "\">" . $warranty_expire . "</span></td>",
			"comment" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . bscode2html(stripslashes(htmlspecialchars($data["comment"]))) . "</td>"
		);
		
		$print_drives[$i_drive] = "<tr style=\"background: #" . $color_array[$data["hash"]] . ";\">";
		$arr_length = count($table_info_order_system);
		for($i=0;$i<$arr_length;$i++) {
			$print_drives[$i_drive] .= $columns_info_out[$table_info_order_system[$i]];
		}
		$print_drives[$i_drive] .= "</tr>";
		
		$i_drive++;
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
<form action="<?php echo DISKLOCATION_PATH ?>/pages/export_tsv.php" method="post">
<p>
	<input type="submit" name="download_csv" value="Download TSV" />
	<input type="checkbox" name="raw_data_csv" value="1" /> Output raw data
</p>
<blockquote class='inline_help'>
	<p>Download a TSV file based upon the selection and ordering of the Information table above. If you're using HTML in the comment section, it will include HTML code if inserted and will not parse it anyhow. TSV is the same as CSV, but the extension for TAB delimited instead of COMMA.</p>
	<p>Output raw data will not format numbers for the file output. Eg. HDD sizes like 8.0TB will be 8001563222016 instead. However, the SMART units read and written is calculated with the logical block size and shown in raw after that.</p>
</blockquote>
</form>
<?php if($db_update == 2) { print("-->"); } ?>
