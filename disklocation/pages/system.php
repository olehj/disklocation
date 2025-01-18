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
	
	// get settings from DB as $var
	// include("load_settings.php");
	
	if(isset($_POST["hash_delete"])) {
		$sql = "
			UPDATE disks SET
				status = 'd'
			WHERE hash = '" . SQLite3::escapeString($_POST["hash_delete"]) . "'
			;
		";
		
		$ret = $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
		}
		
		//$db->close();
		
		//header("Location: " . DISKLOCATION_URL);
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		//exit;
	}
	
	if(isset($_POST["hash_remove"])) {
		if(!force_set_removed_device_status($db, $_POST["hash_remove"])) { die("<p style=\"color: red;\">ERROR: Could not set status for the drive with hash: " . $_POST["hash_remove"] . "</p>"); }
		
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	
	if(isset($_POST["hash_add"])) {
		$sql = "
			UPDATE disks SET
				status = 'h'
			WHERE hash = '" . SQLite3::escapeString($_POST["hash_add"]) . "'
			;
		";
		
		$ret = $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
		}
		
		//$db->close();
		
		//header("Location: " . DISKLOCATION_URL);
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		//exit;
	}
	
	if(isset($_POST["group_add"])) {
		$gid = ( empty($_POST["last_group_id"]) ? 1 : $_POST["last_group_id"]+1 );
		$groups = config_array(DISKLOCATION_GROUPS, 'r');
		$groups[$gid] = array(
			"group_color" => $group_color,
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
		//header("Location: " . DISKLOCATION_URL);
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		//exit;
	}
	if(isset($_POST["group_del"]) && isset($_POST["last_group_id"])) {
		$gid = $_POST["last_group_id"];
		$groups = config_array(DISKLOCATION_GROUPS, 'r');
		unset($groups[$gid]);
		
		$locations = config_array(DISKLOCATION_LOCATIONS, 'r');
		foreach($locations as $hash => $array) {
			if($locations[$hash]["groupid"] == $gid) {
				unset($locations[$hash]);
			}
		}
		
		config_array(DISKLOCATION_GROUPS, 'w', $groups);
		config_array(DISKLOCATION_LOCATIONS, 'w', $locations);
		
		$SUBMIT_RELOAD = 1;
		//header("Location: " . DISKLOCATION_URL);
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		//exit;
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
		//header("Location: " . DISKLOCATION_URL);
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		//exit;
	}
	
	if(isset($_POST["save_settings"])) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE SETTINGS has been pressed.");
		
		if(isset($_POST["displayinfo"])) {
			$post_info = json_encode($_POST["displayinfo"]);
		}
		else {
			$post_info = "";
		}
		
		// settings
		//if(!preg_match("/[0-9]{1,5}/", $_POST["smart_exec_delay"])) { $disklocation_error[] = "SMART execution delay missing or invalid number."; }
		//if(!preg_match("/(hourly|daily|weekly|monthly|disabled)/", $_POST["smart_updates"])) { $disklocation_error[] = "Invalid data for SMART updates."; }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_parity"])) { $disklocation_error[] = "Background color for \"Parity\" invalid."; } else { $_POST["bgcolor_parity"] = str_replace("#", "", $_POST["bgcolor_parity"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_unraid"])) { $disklocation_error[] = "Background color for \"Data\" invalid."; } else { $_POST["bgcolor_unraid"] = str_replace("#", "", $_POST["bgcolor_unraid"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_cache"])) { $disklocation_error[] = "Background color for \"Cache\" invalid."; } else { $_POST["bgcolor_cache"] = str_replace("#", "", $_POST["bgcolor_cache"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_others"])) { $disklocation_error[] = "Background color for \"Unassigned devices\" invalid."; } else { $_POST["bgcolor_others"] = str_replace("#", "", $_POST["bgcolor_others"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_empty"])) { $disklocation_error[] = "Background color for \"Empty trays\" invalid."; } else { $_POST["bgcolor_empty"] = str_replace("#", "", $_POST["bgcolor_empty"]); }
		if(!is_numeric($_POST["tray_reduction_factor"])) { $disklocation_error[] = "The size divider is not numeric."; }
		if(!preg_match("/(0|1)/", $_POST["force_orb_led"])) { $disklocation_error[] = "LED display field is invalid."; }
		//if(!preg_match("/(u|m)/", $_POST["warranty_field"])) { $disklocation_error[] = "Warranty field is invalid."; }
		if(!preg_match("/[0-9]{1,4}/", $_POST["serial_trim"])) { $disklocation_error[] = "Serial number trim number invalid."; }
		
		//config(DISKLOCATION_CONF, 'w', 'signal_css', $_POST["signal_css"]);
		use_stylesheet($_POST["signal_css"]);
		
		//"group", "tray", "device", "node", "pool", "name", "lun", "manufacturer", "model", "serial", "capacity", "cache", "rotation", "formfactor", "manufactured", "purchased", "installed", "removed", "warranty", "warranty_exp", "comment"
		// Infomation
		if(empty($_POST["select_db_info"])) { $_POST["select_db_info"] = $select_db_info_default; }
		if(empty($_POST["sort_db_info"])) { $_POST["sort_db_info"] = $sort_db_info_default; }
		$get_table_order_info = get_table_order($_POST["select_db_info"], $_POST["sort_db_info"], 2, "1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1");
		$get_table_order_info .= get_table_order($_POST["select_db_info"], $_POST["sort_db_info"], 3, "1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1");
		if($get_table_order_info) { $disklocation_error[] = "Table \"Information\": " . $get_table_order_info; }
		
		// Tray Allocations / Unassigned
		if(empty($_POST["select_db_trayalloc"])) { $_POST["select_db_trayalloc"] = $select_db_trayalloc_default; }
		if(empty($_POST["sort_db_trayalloc"])) { $_POST["sort_db_trayalloc"] = $sort_db_trayalloc_default; }
		$get_table_order_trayalloc = get_table_order($_POST["select_db_trayalloc"], $_POST["sort_db_trayalloc"], 2, "0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1");
		$get_table_order_trayalloc .= get_table_order($_POST["select_db_trayalloc"], $_POST["sort_db_trayalloc"], 3, "1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1");
		if($get_table_order_trayalloc) { $disklocation_error[] = "Table \"Tray Allocations\": " . $get_table_order_trayalloc; }
		
		// History
		if(empty($_POST["select_db_drives"])) { $_POST["select_db_drives"] = $select_db_drives_default; }
		if(empty($_POST["sort_db_drives"])) { $_POST["sort_db_drives"] = $sort_db_drives_default; }
		$get_table_order_drives = get_table_order($_POST["select_db_drives"], $_POST["sort_db_drives"], 2, "0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1");
		$get_table_order_drives .= get_table_order($_POST["select_db_drives"], $_POST["sort_db_drives"], 3, "0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1");
		if($get_table_order_drives) { $disklocation_error[] = "Table \"History\": " . $get_table_order_drives; }
		
		// Devices textarea, manual sort
		if(empty($_POST["select_db_devices"])) { $_POST["select_db_devices"] = $select_db_devices_default; }
		if(!bscode2html($_POST["select_db_devices"])) { $disklocation_error[] = "Table \"Devices\": Content could not be parsed."; }
		
		if(empty($disklocation_error)) {
			unset($_POST["save_settings"]);
			unset($_POST["database_noscan"]);
			unset($_POST["warranty_field"]);
			foreach($_POST as $key => $data) {
				$array[$key] = $data;
			}
			
			debug_print($debugging_active, __LINE__, "SQL", "SETTINGS: <pre>" . $array . "</pre>");
			
			config_array(DISKLOCATION_CONF, 'w', $array);
			
			$SUBMIT_RELOAD = 1;
			//header("Location: " . DISKLOCATION_URL);
			//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
			//exit;
		}
	}
	
	if(isset($_POST["save_groupsettings"])) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE GROUP SETTINGS has been pressed.");
		
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
			if($new_array[$id]["group_color"] && !preg_match("/#([a-f0-9]{3}){1,2}\b/i", $new_array[$id]["group_color"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Background color invalid."; } else { $new_array[$id]["group_color"] = str_replace("#", "", $new_array[$id]["group_color"]); }
			if($new_array[$id]["grid_count"] && !preg_match("/\b(column|row)\b/", $new_array[$id]["grid_count"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Physical tray assignment invalid."; }
			if($new_array[$id]["grid_columns"] && !preg_match("/[0-9]{1,3}/", $new_array[$id]["grid_columns"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Grid columns missing or number invalid."; }
			if($new_array[$id]["grid_rows"] && !preg_match("/[0-9]{1,3}/", $new_array[$id]["grid_rows"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Grid rows missing or number invalid."; }
			if($new_array[$id]["grid_trays"] && !preg_match("/[0-9]{1,3}/", $new_array[$id]["grid_trays"])) { $disklocation_error[] = "" . $new_array[$id]["group_name"] . ": Grid trays number invalid."; }
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
			//header("Location: " . DISKLOCATION_URL);
			//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
			//exit;
		}
	}
	
	if(isset($_POST["save_allocations"])) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE ALLOCATIONS has been pressed.");
		// trays
		$sql = "";
		$post_drives = $_POST["drives"];
		$post_groups = $_POST["groups"];
		
		if(empty($disklocation_error)) {
			$keys_drives = array_keys($post_drives);
			for($i=0; $i < count($keys_drives); ++$i) {
				$tray_assign = ( empty($post_drives[$keys_drives[$i]]) ? null : $post_drives[$keys_drives[$i]] );
				$group_assign = ( empty($post_groups[$keys_drives[$i]]) ? null : $post_groups[$keys_drives[$i]] );
				
				if(!$tray_assign || !$group_assign) {
					$sql .= "
						UPDATE disks SET
							status = 'h'
						WHERE hash = '" . $keys_drives[$i] . "'
						;
						DELETE FROM location
							WHERE tray = '" . $tray_assign . "' AND groupid = '" . $group_assign . "'
						;
					";
				}
				else {
					$sql .= "
						INSERT INTO
							location(
								hash,
								tray,
								groupid
							)
							VALUES(
								'" . $keys_drives[$i] . "',
								'" . $tray_assign . "',
								'" . $group_assign . "'
							)
							ON CONFLICT(hash) DO UPDATE SET
								tray='" . $tray_assign . "',
								groupid='" . $group_assign . "'
						;
						UPDATE disks SET
							status = NULL
						WHERE hash = '" . $keys_drives[$i] . "'
						;
					";
				}
			}
			
			debug_print($debugging_active, __LINE__, "SQL", "ALLOC: <pre>" . $sql . "</pre>");
			
			$ret = $db->exec($sql);
			if(!$ret) {
				echo $db->lastErrorMsg();
			}
			
			$sql = "";
			
			// Remove conflicting tray allocations, use only the newest assigned tray
			$sql = "SELECT id FROM location GROUP BY groupid,tray HAVING COUNT(*) > 1;";
			$results = $db->query($sql);
			
			while($res = $results->fetchArray(1)) {
				$sql_del = "DELETE FROM location WHERE id = '" . $res["id"] . "';";
				$ret = $db->exec($sql_del);
				if(!$ret) {
					return $db->lastErrorMsg();
				}
			}
			$sql = "";
			
			for($i=0; $i < count($keys_drives); ++$i) {
				$sql .= "
					UPDATE disks SET
						manufactured = '" . SQLite3::escapeString($_POST["manufactured"][$keys_drives[$i]]) . "',
						purchased = '" . SQLite3::escapeString($_POST["purchased"][$keys_drives[$i]]) . "',
						installed = '" . SQLite3::escapeString($_POST["installed"][$keys_drives[$i]]) . "',
				";
				if($_POST["current_warranty_field"] == "u") {
					$sql .= "warranty = '" . SQLite3::escapeString($_POST["warranty"][$keys_drives[$i]]) . "',";
				}
				else {
					$sql .= "warranty_date = '" . SQLite3::escapeString($_POST["warranty_date"][$keys_drives[$i]]) . "',";
				}
				$sql .= "
						comment = '" . SQLite3::escapeString($_POST["comment"][$keys_drives[$i]]) . "'
					";
				if(!in_array(str_replace("#", "", SQLite3::escapeString($_POST["bgcolor_custom"][$keys_drives[$i]])), array($bgcolor_parity, $bgcolor_unraid, $bgcolor_cache, $bgcolor_others, $bgcolor_empty))) {
					$sql .= ", color = '" . str_replace("#", "", SQLite3::escapeString($_POST["bgcolor_custom"][$keys_drives[$i]])) . "'";
				}
				else {
					$sql .= ", color = ''";
				}
				$sql .= "
					WHERE hash = '" . $keys_drives[$i] . "'
					;
				";
			}
			
			debug_print($debugging_active, __LINE__, "SQL", "POPULATED: <pre>" . $sql . "</pre>");
			
			$ret = $db->exec($sql);
			if(!$ret) {
				echo $db->lastErrorMsg();
			}
			
			//$db->close();
			
			//header("Location: " . DISKLOCATION_URL);
			//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
			//exit;
		}
	}
	
	if(isset($_POST["sort"])) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SORT has been pressed.");
		$sql = "";
		print($_POST["sort"]);
		list($table, $dir, $column) = explode(":", $_POST["sort"]);
		
		$sort = $dir . ":" . $column;
		
		$sql = "UPDATE settings SET sort_db_" . $table . " = '" . SQLite3::escapeString($sort ?? $sort_db_info_default) . "' WHERE id = '1';";
		
		debug_print($debugging_active, __LINE__, "SQL", "SETTINGS: <pre>" . $sql . "</pre>");
		
		$ret = $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
		}
		
		//$db->close();
		
		//header("Location: " . DISKLOCATION_URL);
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		//exit;
	}
	
	if(isset($_POST["reset_all_colors"])) {
		force_reset_color($get_disklocation_config, $get_devices, $get_groups, "*");
		//if(force_reset_color($db, "*")) {
			//$db->close();
			//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
			//exit;
		//}
	}
	if(isset($_POST["reset_common_colors"])) {
		force_reset_color($get_disklocation_config, $get_devices, $get_groups);
		//if(force_reset_color($db)) {
			//$db->close();
			//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
			//exit;
		//}
	}
	
	// RELOAD: get settings from DB as $var
	include("load_settings.php");
	
	// Group config
	$last_group_id = 0;
	
	$array_groups = $get_groups;
	ksort($array_groups, SORT_NUMERIC);
	
	$total_groups = ( is_array($array_groups) ? count($array_groups) : 0 );
	
	$last_group_id = array_key_last($array_groups);
?>
