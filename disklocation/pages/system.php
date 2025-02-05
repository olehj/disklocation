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
	
	require_once("functions.php");
	
	$zfs_check = zfs_check();
	if(!empty($zfs_check)) {
		$zfs_parser = ( empty($zfs_parser) ? zfs_parser($zfs_check) : $zfs_parser );
		$lsblk_array = json_decode(shell_exec("lsblk -p -o NAME,MOUNTPOINT,SERIAL,PATH --json"), true);
		$zfs_check = 1;
	}
	if(!empty($force_scan_db)) { return true; } // do not run rest of this file if it's a cronjob.
	
	if(isset($_POST["hash_delete"])) {
		foreach($get_devices as $key => $data) {
			if($_POST["hash_delete"] == $key) {
				$get_devices[$_POST["hash_delete"]]["status"] = 'd';
			}
		}
		
		config_array(DISKLOCATION_DEVICES, 'w', $get_devices);
		
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: hash_delete", $get_devices);
	}
	
	if(isset($_POST["hash_remove"])) {
		if(!force_set_removed_device_status($get_devices, $get_locations, $_POST["hash_remove"])) { print("<p style=\"color: red;\">ERROR: Could not set status for the drive with hash: " . $_POST["hash_remove"] . "</p>"); }
		
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: hash_remove", $_POST["hash_remove"]);
	}
	
	if(isset($_POST["hash_add"])) {
		foreach($get_devices as $key => $data) {
			if($_POST["hash_add"] == $key) {
				$get_devices[$_POST["hash_add"]]["status"] = 'h';
			}
		}
		
		config_array(DISKLOCATION_DEVICES, 'w', $get_devices);
		
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: hash_add", $get_devices);
	}
	
	if(isset($_POST["group_add"])) {
		$gid = ( empty($_POST["group_add"]) ? 1 : $_POST["group_add"]+1 );
		$groups = config_array(DISKLOCATION_GROUPS, 'r');
		$groups[$gid] = array(
			"group_color" => null,
			"grid_count" => $grid_count,
			"grid_columns" => $grid_columns,
			"grid_rows" => $grid_rows,
			"grid_trays" => $grid_trays,
			"disk_tray_direction" => $disk_tray_direction,
			"tray_direction" => $tray_direction,
			"tray_start_num" => $tray_start_num,
			"tray_width" => $tray_width,
			"tray_height" => $tray_height
		);
		config_array(DISKLOCATION_GROUPS, 'w', $groups);
		
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: group_add", $groups);
	}
	if(isset($_POST["group_del"])) {
		$gid = $_POST["group_del"];
		$groups = config_array(DISKLOCATION_GROUPS, 'r');
		$devices = config_array(DISKLOCATION_DEVICES, 'r');
		unset($groups[$gid]);
		
		$locations = config_array(DISKLOCATION_LOCATIONS, 'r');
		foreach($locations as $hash => $array) {
			if($locations[$hash]["groupid"] == $gid) {
				unset($locations[$hash]);
				$devices[$hash]["status"] = 'h';
			}
		}
		
		config_array(DISKLOCATION_GROUPS, 'w', $groups);
		config_array(DISKLOCATION_LOCATIONS, 'w', $locations);
		config_array(DISKLOCATION_DEVICES, 'w', $devices);
		
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: group_del", array($devices, $locations, $groups));
	}
	if(isset($_POST["group_swap"])) {
		list($group_left, $group_right) = explode(":", $_POST["group_swap"]);
		$groups = config_array(DISKLOCATION_GROUPS, 'r');
		$groups["swap_left"] = $groups[$group_left];
		$groups["swap_right"] = $groups[$group_right];
		$groups[$group_right] = $groups["swap_left"];
		$groups[$group_left] = $groups["swap_right"];
		unset($groups["swap_left"]);
		unset($groups["swap_right"]);
		
		$locations = config_array(DISKLOCATION_LOCATIONS, 'r');
		foreach($locations as $hash => $array) {
			if($array["groupid"] == $group_left) {
				$locations[$hash]["swap_right"] = $group_right;
			}
			if($array["groupid"] == $group_right) {
				$locations[$hash]["swap_left"] = $group_left;
			}
		}
		foreach($locations as $hash => $array) {
			if($array["groupid"] == $group_left) {
				$locations[$hash]["groupid"] = $locations[$hash]["swap_right"];
				unset($locations[$hash]["swap_right"]);
			}
			if($array["groupid"] == $group_right) {
				$locations[$hash]["groupid"] = $locations[$hash]["swap_left"];
				unset($locations[$hash]["swap_left"]);
			}
		}
		
		config_array(DISKLOCATION_GROUPS, 'w', $groups);
		config_array(DISKLOCATION_LOCATIONS, 'w', $locations);
		
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: group_swap", array($locations, $groups));
	}
	
	if(isset($_POST["save_settings"])) {
		if(isset($_POST["displayinfo"])) {
			$post_info = json_encode($_POST["displayinfo"]);
		}
		else {
			$post_info = "";
		}
		
		$_POST["force_orb_led"] = $_POST["force_orb_led"] ? 1 : 0;
		$_POST["allow_unraid_edit"] = $_POST["allow_unraid_edit"] ? 1 : 0;
		$_POST["auto_backup_days"] = $_POST["auto_backup_days"] ? $_POST["auto_backup_days"] : 0;
		
		// settings
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_parity"])) { $disklocation_error[] = "Background color for \"Parity\" invalid."; } else { $_POST["bgcolor_parity"] = str_replace("#", "", strtoupper($_POST["bgcolor_parity"])); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_unraid"])) { $disklocation_error[] = "Background color for \"Data\" invalid."; } else { $_POST["bgcolor_unraid"] = str_replace("#", "", strtoupper($_POST["bgcolor_unraid"])); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_cache"])) { $disklocation_error[] = "Background color for \"Cache\" invalid."; } else { $_POST["bgcolor_cache"] = str_replace("#", "", strtoupper($_POST["bgcolor_cache"])); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_others"])) { $disklocation_error[] = "Background color for \"Unassigned devices\" invalid."; } else { $_POST["bgcolor_others"] = str_replace("#", "", strtoupper($_POST["bgcolor_others"])); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_empty"])) { $disklocation_error[] = "Background color for \"Empty trays\" invalid."; } else { $_POST["bgcolor_empty"] = str_replace("#", "", strtoupper($_POST["bgcolor_empty"])); }
		if(!is_numeric($_POST["tray_reduction_factor"])) { $disklocation_error[] = "The size divider is not numeric."; }
		if(!preg_match("/(0|1)/", $_POST["force_orb_led"])) { $disklocation_error[] = "LED display field is invalid."; }
		if(!preg_match("/(0|1)/", $_POST["allow_unraid_edit"])) { $disklocation_error[] = "Unraid condig edit field is invalid."; }
		if(!preg_match("/[0-9]{1,4}/", $_POST["serial_trim"])) { $disklocation_error[] = "Serial number trim number invalid."; }
		if(!preg_match("/[0-9]{1,9}/", $_POST["auto_backup_days"])) { $disklocation_error[] = "Invalid number of days."; }
		
		use_stylesheet($_POST["signal_css"]);
		
		// Infomation
		if(empty($_POST["select_db_info"])) { $_POST["select_db_info"] = $select_db_info_default; }
		if(empty($_POST["sort_db_info"])) { $_POST["sort_db_info"] = $sort_db_info_default; }
		$get_table_order_info = get_table_order($_POST["select_db_info"], $_POST["sort_db_info"], 2, $allowed_db_select_info);
		$get_table_order_info .= get_table_order($_POST["select_db_info"], $_POST["sort_db_info"], 3, $allowed_db_sort_info);
		if($get_table_order_info) { $disklocation_error[] = "Table \"Information\": " . $get_table_order_info; }
		
		// SMART
		if(empty($_POST["select_db_smart"])) { $_POST["select_db_smart"] = $select_db_smart_default; }
		if(empty($_POST["sort_db_smart"])) { $_POST["sort_db_smart"] = $sort_db_smart_default; }
		$get_table_order_smart = get_table_order($_POST["select_db_smart"], $_POST["sort_db_smart"], 2, $allowed_db_select_smart);
		$get_table_order_smart .= get_table_order($_POST["select_db_smart"], $_POST["sort_db_smart"], 3, $allowed_db_sort_smart);
		if($get_table_order_smart) { $disklocation_error[] = "Table \"S.M.A.R.T\": " . $get_table_order_smart; }
		
		// Tray Allocations / Unassigned
		if(empty($_POST["select_db_trayalloc"])) { $_POST["select_db_trayalloc"] = $select_db_trayalloc_default; }
		if(empty($_POST["sort_db_trayalloc"])) { $_POST["sort_db_trayalloc"] = $sort_db_trayalloc_default; }
		$get_table_order_trayalloc = get_table_order($_POST["select_db_trayalloc"], $_POST["sort_db_trayalloc"], 2, $allowed_db_select_trayalloc);
		$get_table_order_trayalloc .= get_table_order($_POST["select_db_trayalloc"], $_POST["sort_db_trayalloc"], 3, $allowed_db_sort_trayalloc);
		if($get_table_order_trayalloc) { $disklocation_error[] = "Table \"Tray Allocations\": " . $get_table_order_trayalloc; }
		
		// History
		if(empty($_POST["select_db_drives"])) { $_POST["select_db_drives"] = $select_db_drives_default; }
		if(empty($_POST["sort_db_drives"])) { $_POST["sort_db_drives"] = $sort_db_drives_default; }
		$get_table_order_drives = get_table_order($_POST["select_db_drives"], $_POST["sort_db_drives"], 2, $allowed_db_select_drives);
		$get_table_order_drives .= get_table_order($_POST["select_db_drives"], $_POST["sort_db_drives"], 3, $allowed_db_sort_drives);
		if($get_table_order_drives) { $disklocation_error[] = "Table \"History\": " . $get_table_order_drives; }
		
		// Devices textarea, manual sort
		if(empty($_POST["select_db_devices"])) { $_POST["select_db_devices"] = $select_db_devices_default; }
		if(!bscode2html($_POST["select_db_devices"])) { $disklocation_error[] = "Table \"Devices\": Content could not be parsed."; }
		
		if(empty($disklocation_error)) {
			$array = config_array(DISKLOCATION_CONF, 'r');
			unset($_POST["save_settings"]);
			unset($_POST["database_noscan"]);
			unset($_POST["warranty_field"]);
			foreach($_POST as $key => $data) {
				$array[$key] = $data;
			}
			
			config_array(DISKLOCATION_CONF, 'w', $array);
			
			$SUBMIT_RELOAD = 1;
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: save_settings", $array);
		}
		else {
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: save_settings", $disklocation_error);
		}
	}
	
	if(isset($_POST["save_groupsettings"])) {
		unset($_POST["save_groupsettings"]);
		unset($_POST["last_group_id"]);
		
		$new_array = array();
		foreach($_POST as $settings => $array) {
			foreach($array as $id => $data) {
				$new_array[$id][$settings] = $data;
				$new_array[$id]["group_name"] = stripslashes(htmlspecialchars($new_array[$id]["group_name"]));
			}
		}
		
		foreach($new_array as $id => $setting) {
			if($new_array[$id]["group_color"] && !preg_match("/#([a-f0-9]{3}){1,2}\b/i", $new_array[$id]["group_color"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Background color invalid."; } else { $new_array[$id]["group_color"] = ( strtoupper($new_array[$id]["group_color"]) != "#".strtoupper($bgcolor_empty) ? str_replace("#", "", strtoupper($new_array[$id]["group_color"])) : null ); }
			if($new_array[$id]["grid_count"] && !preg_match("/\b(column|row)\b/", $new_array[$id]["grid_count"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Physical tray assignment invalid."; }
			if($new_array[$id]["grid_columns"] && !preg_match("/[0-9]{1,3}/", $new_array[$id]["grid_columns"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Grid columns missing or number invalid."; }
			if($new_array[$id]["grid_rows"] && !preg_match("/[0-9]{1,3}/", $new_array[$id]["grid_rows"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Grid rows missing or number invalid."; }
			if($new_array[$id]["disk_tray_direction"] && !preg_match("/(h|v)/", $new_array[$id]["disk_tray_direction"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Physical tray direction invalid."; }
			if($new_array[$id]["tray_direction"] && !preg_match("/[0-9]{1}/", $new_array[$id]["tray_direction"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Tray number direction invalid."; }
			if($new_array[$id]["tray_start_num"] && !preg_match("/[0-9]{1,7}/", $new_array[$id]["tray_start_num"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Tray start number invalid."; }
			if($new_array[$id]["tray_width"] && !preg_match("/[0-9]{1,4}/", $new_array[$id]["tray_width"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Tray's longest side outside limits or invalid number entered."; }
			if($new_array[$id]["tray_height"] && !preg_match("/[0-9]{1,3}/", $new_array[$id]["tray_height"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Tray's smallest side outside limits or invalid number entered."; }
			if($id && !preg_match("/[0-9]{1,}/", $id)) { $disklocation_error[] = "" . $id . ": Expected group ID to be an integer."; }
		}
		
		if(empty($disklocation_error)) {
			config_array(DISKLOCATION_GROUPS, "w", $new_array);
			
			$SUBMIT_RELOAD = 1;
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: save_groupsettings", $new_array);
		}
		else {
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: save_groupsettings", $disklocation_error);
		}
	}
	
	if(isset($_POST["save_allocations"])) {
		// trays
		$post_drives = $_POST["drives"];
		$post_groups = $_POST["groups"];
		$array_devices = $get_devices;
		$array_locations = $get_locations;
		$array_groups = $get_groups;
		$create_disklog_ini = array();
		
		if(empty($disklocation_error)) {
			// Get new allocations and adjust location array:
			
			$keys_drives = array_keys($post_drives);
			for($i=0; $i < count($keys_drives); ++$i) {
				$tray_assign = ( empty($post_drives[$keys_drives[$i]]) ? null : $post_drives[$keys_drives[$i]] );
				$group_assign = ( empty($post_groups[$keys_drives[$i]]) ? null : $post_groups[$keys_drives[$i]] );
				
				$array_devices[$keys_drives[$i]]["status"] = 'h'; // force all to be unassigned while allocating
				
				if(!$tray_assign || !$group_assign) {
					unset($array_locations[$keys_drives[$i]]);
				}
				else {
					$array_locations[$keys_drives[$i]]["groupid"] = $group_assign;
					$array_locations[$keys_drives[$i]]["tray"] = $tray_assign;
					
					if(!empty($array_groups[$group_assign]["hide_tray"][$tray_assign])) {
						$disklocation_error[] = "Tray " . $tray_assign . " in group " . $group_assign . " is marked as bypassed. Drive can't be assigned.";
						unset($array_locations[$keys_drives[$i]]);
					}
				}
			}
			
			// Remove existing/duplicated allocations, keep the newest, and enable assigned device:
			
			$results = array();
			foreach($array_locations as $hash => $value) {
				$results[$value["groupid"] ."|". $value["tray"]] = $value;
				$results[$value["groupid"] ."|". $value["tray"]]["hash"] = $hash;
			}
			
			// Create new location array and adjust devices array:
			
			$array_locations = array(); // clear
			foreach($results as $id => $array) {
				$array_locations[$results[$id]["hash"]] = $results[$id];
				unset($array_locations[$results[$id]["hash"]]["hash"]);
				$array_devices[$results[$id]["hash"]]["status"] = null;  // enable found and assigned devices
				
				$array_devices[$results[$id]["hash"]]["manufactured"] = ( !empty($_POST["manufactured"][$results[$id]["hash"]]) ? $_POST["manufactured"][$results[$id]["hash"]] : null );
				$array_devices[$results[$id]["hash"]]["purchased"] = ( !empty($_POST["purchased"][$results[$id]["hash"]]) ? $_POST["purchased"][$results[$id]["hash"]] : null );
				$array_devices[$results[$id]["hash"]]["installed"] = ( !empty($_POST["installed"][$results[$id]["hash"]]) ? $_POST["installed"][$results[$id]["hash"]] : null );
				$array_devices[$results[$id]["hash"]]["warranty"] = ( !empty($_POST["warranty"][$results[$id]["hash"]]) ? $_POST["warranty"][$results[$id]["hash"]] : null );
				
				$array_devices[$results[$id]["hash"]]["comment"] = ( !empty($_POST["comment"][$results[$id]["hash"]]) ? $_POST["comment"][$results[$id]["hash"]] : null );
				
				$array_devices[$results[$id]["hash"]]["color"] = ( (!empty($_POST["bgcolor_custom"][$results[$id]["hash"]]) && strtoupper($_POST["bgcolor_custom"][$results[$id]["hash"]]) != "#".strtoupper($bgcolor_empty)) ? str_replace("#", "", strtoupper($_POST["bgcolor_custom"][$results[$id]["hash"]])) : null );
				
				if($allow_unraid_edit) {
					if($array_devices[$results[$id]["hash"]]["manufactured"]) { $create_disklog_ini[str_replace(" ", "_", $array_devices[$results[$id]["hash"]]["model_name"] . "_" . $array_devices[$results[$id]["hash"]]["smart_serialnumber"])]["date"] = $_POST["manufactured"][$results[$id]["hash"]]; }
					if($array_devices[$results[$id]["hash"]]["purchased"]) { $create_disklog_ini[str_replace(" ", "_", $array_devices[$results[$id]["hash"]]["model_name"] . "_" . $array_devices[$results[$id]["hash"]]["smart_serialnumber"])]["purchase"] = $_POST["purchased"][$results[$id]["hash"]]; }
					if($array_devices[$results[$id]["hash"]]["warranty"]) { $create_disklog_ini[str_replace(" ", "_", $array_devices[$results[$id]["hash"]]["model_name"] . "_" . $array_devices[$results[$id]["hash"]]["smart_serialnumber"])]["warranty"] = $_POST["warranty"][$results[$id]["hash"]]; }
				}
			}
			
			config_array(DISKLOCATION_DEVICES, "w", $array_devices);
			config_array(DISKLOCATION_LOCATIONS, "w", $array_locations);
			
			if($allow_unraid_edit) { 
				$new_disklog = array_merge($unraid_disklog, $create_disklog_ini);
				write_ini_file(DISKLOGFILE, $new_disklog);
			}
			
			$SUBMIT_RELOAD = 1;
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: save_allocations", array($results, $array_devices, $array_locations, $new_disklog));
		}
		if(!empty($disklocation_error)) {
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: save_allocations", $disklocation_error);
		}
	}
	
	if(isset($_POST["save_benchmark_settings"])) {
		if(!preg_match("/[0-9]{1,2}/", $_POST["bench_iterations"])) { $disklocation_error[] = "Iterations value is not a number."; }
		if($_POST["bench_iterations"] < 1 && $_POST["bench_iterations"] > 10) { $disklocation_error[] = "Iterations value is out of range."; };
		if(!preg_match("/(^$|1)/", $_POST["bench_median"])) { $disklocation_error[] = "Median value invalid."; }
		if(!preg_match("/(^$|1)/", $_POST["bench_force"])) { $disklocation_error[] = "Force value invalid."; }
		if(!preg_match("/(^$|1)/", $_POST["bench_auto_cron"])) { $disklocation_error[] = "Auto crontab value invalid."; }
		if(!preg_match("/[0-9]{1,4}/", $_POST["bench_last_values"])) { $disklocation_error[] = "Last benchmarks value is not a number."; }
		if($_POST["bench_last_values"] < 1 && $_POST["bench_last_values"] > 1000) { $disklocation_error[] = "Last benchmarks value is out of range."; };
		
		$_POST["bench_median"] = $_POST["bench_median"] ? 1 : 0;
		$_POST["bench_force"] = $_POST["bench_force"] ? 1 : 0;
		$_POST["bench_auto_cron"] = $_POST["bench_auto_cron"] ? 1 : 0;
		
		if(empty($disklocation_error)) {
			$array = config_array(DISKLOCATION_CONF, 'r');
			
			unset($_POST["save_benchmark_settings"]);
			foreach($_POST as $key => $data) {
				$array[$key] = $data;
			}
			
			config_array(DISKLOCATION_CONF, 'w', $array);
			
			$SUBMIT_RELOAD = 1;
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: save_benchmark_settings", $array);
		}
		else {
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: save_benchmark_settings", $disklocation_error);
		}
	}
	
	if(isset($_POST["sort"])) {
		list($table, $dir, $column) = explode(":", $_POST["sort"]);
		
		$sort = $dir . ":" . $column;
		
		${"sort_db_" . $table . "_override"} = $sort;
		
		$SUBMIT_RELOAD = 0;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: sort", $sort);
	}
	
	if(isset($_POST["sort_reset"])) {
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: sort_reset", "true");
	}
	
	if(isset($_POST["reset_all_colors"])) {
		force_reset_color($get_disklocation_config, $get_devices, $get_groups, "*");
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: reset_all_colors", "true");
	}
	if(isset($_POST["reset_common_colors"])) {
		force_reset_color($get_disklocation_config, $get_devices, $get_groups);
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: force_reset_color", "true");
	}
	
	if(isset($_POST["disk_ack_all_ok"]) && isset($_POST["disk_ack_drives"])) {
		set_disk_ack($_POST["disk_ack_drives"], EMHTTP_VAR . "/" . UNRAID_MONITOR_FILE);
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: disk_ack_all_ok", "true");
	}
	
	if(isset($_POST["killall_smartlocate"])) {
		shell_exec("pkill -f smartlocate");
		$SUBMIT_RELOAD = 1;
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "POST: killall_smartlocate", "true");
	}
	
	// RELOAD: get settings from DB as $var
	include("load_settings.php");
	
	// Group config
	$last_group_id = 0;
	
	$array_groups = $get_groups;
	( is_array($array_groups) ?? ksort($array_groups, SORT_NUMERIC) );
	
	$total_groups = ( is_array($array_groups) ? count($array_groups) : 0 );
	
	$last_group_id = ( is_array($array_groups) ? array_key_last($array_groups) : 0 );
	
	$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "VAR: total_groups", $total_groups);
	$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "VAR: last_group_id", $last_group_id);
?>
