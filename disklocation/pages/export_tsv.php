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

	if($_POST["download_csv"]) {
		require_once("system.php");
		
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
		
		$print_csv = array();
		$datasql = array();
		
		list($table_info_order_user, $table_info_order_system, $table_info_order_name, $table_info_order_full ,$table_info_order_forms) = get_table_order($select_db_info, $sort_db_info);
		
		$arr_length = count($table_info_order_user);
		for($i=0;$i<$arr_length;$i++) {
			$print_csv[0][$i] = $table_info_order_name[$i];
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
					$date_warranty_csv = $warranty_expire;
				}
				else {
					$warranty_end = strtotime($data["warranty_date"]);
					$warranty_expire = $data["warranty_date"];
					$date_warranty = $data["warranty_date"];
					$date_warranty_csv = $data["warranty_date"];
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

			$columns_info_csv = array(
				"groupid" => stripslashes(htmlspecialchars($group_assign)),
				"tray" => $physical_traynumber,
				"device" => $data["device"],
				"devicenode" => $data["devicenode"],
				"luname" => $data["luname"],
				"model_family" => $data["model_family"],
				"model_name" => $data["model_name"],
				"smart_serialnumber" => substr($data["smart_serialnumber"], $dashboard_widget_pos),
				"smart_capacity" => $smart_capacity,
				"smart_rotation" => $smart_rotation,
				"smart_formfactor" => str_replace(" inches", "\"", $data["smart_formfactor"]),
				"smart_status" => ( empty($data["smart_status"]) ? "FAIL" : "OK" ),
				"smart_temperature" => $smart_temperature . "/" . $smart_temperature_warning . "/" . $smart_temperature_critical,
				"smart_powerontime" => $data["smart_powerontime"],
				"smart_loadcycle" => ( isset($data["smart_loadcycle"]) ? $data["smart_loadcycle"] : "N/A" ),
				"smart_reallocated_sector_count" => ( isset($data["smart_reallocated_sector_count"]) ? $data["smart_reallocated_sector_count"] : "" ),
				"smart_reported_uncorrectable_errors" => ( isset($data["smart_reported_uncorrectable_errors"]) ? $data["smart_reported_uncorrectable_errors"] : "" ),
				"smart_command_timeout" => ( isset($data["smart_command_timeout"]) ? $data["smart_command_timeout"] : "" ),
				"smart_current_pending_sector_count" => ( isset($data["smart_current_pending_sector_count"]) ? $data["smart_current_pending_sector_count"] : "" ),
				"smart_offline_uncorrectable" => ( isset($data["smart_offline_uncorrectable"]) ? $data["smart_offline_uncorrectable"] : "" ),
				"smart_nvme_percentage_used" => ( is_numeric($data["smart_nvme_percentage_used"]) ? $data["smart_nvme_percentage_used"] . "%" : "N/A" ),
				"smart_units_read" => $smart_units_read,
				"smart_units_written" => $smart_units_written,
				"smart_nvme_available_spare" => ( is_numeric($data["smart_nvme_available_spare"]) ? $data["smart_nvme_available_spare"] . "%" : "N/A" ),
				"smart_nvme_available_spare_threshold" => ( is_numeric($data["smart_nvme_available_spare_threshold"]) ? $data["smart_nvme_available_spare_threshold"] . "%" : "N/A" ),
				"manufactured" => $data["manufactured"],
				"purchased" => $data["purchased"],
				"warranty_date" => $date_warranty_csv,
				"comment" => bscode2html(stripslashes(htmlspecialchars($data["comment"])))
			);
			
			$arr_length = count($table_info_order_system);
			for($i=0;$i<$arr_length;$i++) {
				$print_csv[$i_drive][$i] = $columns_info_csv[$table_info_order_system[$i]];
			}
			$i_drive++;
		}
		$i++;
		$print_csv[$i_drive][$i+0] = " ";
		$print_csv[$i_drive+1][$i+0] = "Disk Location";
		$print_csv[$i_drive+1][$i+1] = "" . DISKLOCATION_VERSION . "";
		array_to_csv_download($print_csv, "disklocation-" . date("Ymd-His") . ".tsv", "\t");
	}
?>
