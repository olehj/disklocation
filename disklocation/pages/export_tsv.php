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
	if(isset($_GET["download_csv"])) {
		require_once("system.php");
		require_once("devices.php");
		
		$get_info_select = get_table_order($select_db_info, $sort_db_info, 1);
		
		$i=1;
		$i_empty=1;
		$i_drive=1;
		
		$array_groups = $get_groups;
		$print_drives = array();
		$datadb = array();
		
		list($table_info_order_user, $table_info_order_system, $table_info_order_name, $table_info_order_full, $table_info_order_forms) = get_table_order($select_db_info, $sort_db_info);
		
		$arr_length = count($table_info_order_user);
		for($i=0;$i<$arr_length;$i++) {
			$print_csv[0][$i] = $table_info_order_name[$i];
		}
		
		foreach($devices as $hash => $data) { // array as hash => array(raw/formatted)
			$raw_devices[] = array("hash" => $hash)+$data["raw"];
		}
		
		unset($data);
		
		$db_sort = explode(",", $get_info_select["db_sort"]);
		$dynamic = array();
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
			
			// Special rules
			if($raw["purchased"] && ($raw["warranty"])) {
				$warranty_start = strtotime($raw["purchased"]);
				
				$warranty_end = strtotime("" . $raw["purchased"] . " + " . $raw["warranty"] . " month");
				$warranty_expire = date("Y-m-d", $warranty_end);
			}
			
			if(isset($_GET["raw_data_csv"])) {
				$columns_info_csv = array(
					"groupid" => "" . $raw["group_name"] . "",
					"tray" => "" . $physical_traynumber . "",
					"device" => "" . $raw["device"] . "",
					"devicenode" => "" . $raw["node"] . "",
					"luname" => "" . $raw["lun"] . "",
					"model_family" => "" . $raw["manufacturer"] . "",
					"model_name" => "" . $raw["model"] . "",
					"smart_serialnumber" => "" . $raw["serial"] . "",
					"smart_capacity" => "" . $raw["capacity"] . "",
					"smart_cache" => "" . $raw["cache"] . "",
					"smart_rotation" => "" . $raw["rotation"] . "",
					"smart_formfactor" => "" . $raw["formfactor"] . "",
					"smart_status" => "" . $raw["smart_status"] . "",
					"smart_temperature" => "" . $raw["temp"] . " (" . $raw["hotTemp"] . "/" . $raw["maxTemp"] . ")",
					"smart_powerontime" => "" . $raw["powerontime"] . "</span>",
					"smart_loadcycle" => "" . $raw["loadcycle"] . "",
					"smart_nvme_percentage_used" => "" . $raw["nvme_percentage_used"] . "",
					"smart_units_read" => "" . $raw["smart_units_read"] . "",
					"smart_units_written" => "" . $raw["smart_units_written"] . "",
					"smart_nvme_available_spare" => "" . $raw["nvme_available_spare"] . "",
					"smart_nvme_available_spare_threshold" => "" . $raw["nvme_available_spare_threshold"] . "",
					//"benchmark_r" => "" . $data["benchmark_r"] . "",
					//"benchmark_w" => "" . $data["benchmark_w"] . "",
					"installed" => "" . $raw["installed"] . "",
					"removed" => "" . $raw["removed"] . "",
					"manufactured" => "" . $raw["manufactured"] . "",
					"purchased" => "" . $raw["purchased"] . "",
					"warranty_date" => "" . $raw["warranty"] . "",
					"comment" => "" . $raw["comment"] . ""
				);
				
				$raw_data_csv_file = ".raw";
			}
			else {
				$columns_info_csv = array(
					"groupid" => "" . stripslashes(htmlspecialchars($formatted["group_name"])) . "",
					"tray" => "" . $physical_traynumber . "",
					"device" => "" . $formatted["device"] . "",
					"devicenode" => "" . $formatted["node"] . "",
					"luname" => "" . $formatted["lun"] . "",
					"model_family" => "" . $formatted["manufacturer"] . "",
					"model_name" => "" . $formatted["model"] . "",
					"smart_serialnumber" => "" . substr($raw["serial"], $serial_trim) . "",
					"smart_capacity" => "" . $formatted["capacity"] . "",
					"smart_cache" => "" . $formatted["cache"] . "",
					"smart_rotation" => "" . $formatted["rotation"] . "",
					"smart_formfactor" => "" . $formatted["formfactor"] . "",
					"smart_status" => "" . $formatted["smart_status"] . "",
					"smart_temperature" => "" . $formatted["temp"] . " (" . $formatted["hotTemp"] . "/" . $formatted["maxTemp"] . ")",
					"smart_powerontime" => "" . $formatted["powerontime"] . "</span>",
					"smart_loadcycle" => "" . $formatted["loadcycle"] . "",
					"smart_nvme_percentage_used" => "" . $formatted["nvme_percentage_used"] . "",
					"smart_units_read" => "" . $formatted["smart_units_read"] . "",
					"smart_units_written" => "" . $formatted["smart_units_written"] . "",
					"smart_nvme_available_spare" => "" . $formatted["nvme_available_spare"] . "",
					"smart_nvme_available_spare_threshold" => "" . $formatted["nvme_available_spare_threshold"] . "",
					//"benchmark_r" => "" . $data["benchmark_r"] . "",
					//"benchmark_w" => "" . $data["benchmark_w"] . "",
					"installed" => "" . $raw["installed"] . "",
					"removed" => "" . $raw["removed"] . "",
					"manufactured" => "" . $raw["manufactured"] . "",
					"purchased" => "" . $raw["purchased"] . "",
					"warranty_date" => "" . $warranty_expire . "",
					"comment" => "" . stripslashes($formatted["comment"]) . ""
				);
			}
			
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
		$print_csv[$i_drive+1][$i+2] = ( isset($_GET["raw_data_csv"]) ? "RAW" : "FORMATTED" );
		array_to_csv_download($print_csv, "disklocation-" . date("Ymd-His") . "" . $raw_data_csv_file . ".tsv", "\t");
	}
?>
