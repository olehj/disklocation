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
		require_once("array_devices.php");
		
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
			$$sort = ( is_array($raw_devices) ? array_column($raw_devices, $sort) : null );
			$sort_dynamic[] = &$$sort;
			$sort_dynamic[] = $dir;
			if($flag) { 
				$sort_dynamic[] = $flag;
			}
		}
		( is_array($raw_devices) ? call_user_func_array('array_multisort', array_merge($sort_dynamic, array(&$raw_devices))) : null );
		
		foreach($raw_devices as $key => $data) {
			if(empty($data["status"]) && $data["groupid"]) {
				$hash = $data["hash"];
				
				$data = $devices[$hash];
				
				$array = ( isset($_GET["raw_data_csv"]) ? $data["raw"] : $data["formatted"] );
				
				$data = $data["raw"];
				
				$gid = $data["groupid"];
				
				extract($array_groups[$gid]);
				
				$group_assign = ( empty($group_name) ? $data["groupid"] : $group_name );
				
				$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
				
				$total_trays = ( empty($grid_trays) ? $grid_columns * $grid_rows : $grid_trays );
				
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
				
				$type = ( isset($_GET["raw_data_csv"]) ? "raw" : "formatted" );
				$listarray = list_array($array, $type, $physical_traynumber);
				
				$raw_data_csv_file = ( isset($_GET["raw_data_csv"]) ? ".raw" : ".format" );
				
				$arr_length = count($table_info_order_system);
				for($i=0;$i<$arr_length;$i++) {
					$print_csv[$i_drive][$i] = $listarray[$table_info_order_system[$i]];
				}
				$i_drive++;
			}
		}
		$i++;
		$print_csv[$i_drive][$i+0] = " ";
		$print_csv[$i_drive+1][$i+0] = "Disk Location";
		$print_csv[$i_drive+1][$i+1] = "" . DISKLOCATION_VERSION . "";
		$print_csv[$i_drive+1][$i+2] = ( isset($_GET["raw_data_csv"]) ? "RAW" : "FORMATTED" );
		$print_csv[$i_drive+1][$i+3] = date("Y-m-d");
		//print_r($print_csv);
		array_to_csv_download($print_csv, "disklocation-" . date("Ymd-His") . "" . $raw_data_csv_file . ".tsv", "\t");
	}
?>
