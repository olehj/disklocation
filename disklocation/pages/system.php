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
	include("load_settings.php");
	
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
		$sql = "
			INSERT INTO settings_group(group_name) VALUES('');
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
	if(isset($_POST["group_del"]) && isset($_POST["last_group_id"])) {
		$sql = "
			DELETE FROM settings_group WHERE id = '" . $_POST["last_group_id"] . "';
			DELETE FROM location WHERE groupid = '" . $_POST["last_group_id"] . "';
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
	if(isset($_POST["group_swap"])) {
		list($group_left, $group_right) = explode(":", $_POST["group_swap"]);
		$sql = "
			UPDATE location SET
				groupid = (CASE WHEN groupid = '" . $group_left . "' THEN '" . $group_right . "' ELSE '" . $group_left . "' END) WHERE groupid IN (" . $group_left . ", " . $group_right . ")
			;
			BEGIN;
			CREATE TEMPORARY TABLE tmp_sg AS SELECT * FROM settings_group WHERE id = '" . $group_left . "';
			DELETE FROM settings_group WHERE id = '" . $group_left . "';
			UPDATE settings_group SET id = '" . $group_left . "' WHERE id = " . $group_right . ";
			UPDATE tmp_sg SET id = " . $group_right . " WHERE id = '" . $group_left . "';
			INSERT INTO settings_group SELECT * FROM tmp_sg;
			DROP TABLE tmp_sg;
			COMMIT;
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
	
	if(isset($_POST["save_settings"])) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE SETTINGS has been pressed.");
		$sql = "";
		
		if(isset($_POST["displayinfo"])) {
			$post_info = json_encode($_POST["displayinfo"]);
		}
		else {
			$post_info = "";
		}
		
		// settings
		if(!preg_match("/[0-9]{1,5}/", $_POST["smart_exec_delay"])) { $disklocation_error[] = "SMART execution delay missing or invalid number."; }
		if(!preg_match("/(hourly|daily|weekly|monthly|disabled)/", $_POST["smart_updates"])) { $disklocation_error[] = "Invalid data for SMART updates."; }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_parity"])) { $disklocation_error[] = "Background color for \"Parity\" invalid."; } else { $_POST["bgcolor_parity"] = str_replace("#", "", $_POST["bgcolor_parity"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_unraid"])) { $disklocation_error[] = "Background color for \"Data\" invalid."; } else { $_POST["bgcolor_unraid"] = str_replace("#", "", $_POST["bgcolor_unraid"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_cache"])) { $disklocation_error[] = "Background color for \"Cache\" invalid."; } else { $_POST["bgcolor_cache"] = str_replace("#", "", $_POST["bgcolor_cache"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_others"])) { $disklocation_error[] = "Background color for \"Unassigned devices\" invalid."; } else { $_POST["bgcolor_others"] = str_replace("#", "", $_POST["bgcolor_others"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_empty"])) { $disklocation_error[] = "Background color for \"Empty trays\" invalid."; } else { $_POST["bgcolor_empty"] = str_replace("#", "", $_POST["bgcolor_empty"]); }
		if(!is_numeric($_POST["tray_reduction_factor"])) { $disklocation_error[] = "The size divider is not numeric."; }
		if(!preg_match("/(0|1)/", $_POST["force_orb_led"])) { $disklocation_error[] = "LED display field is invalid."; }
		if(!preg_match("/(u|m)/", $_POST["warranty_field"])) { $disklocation_error[] = "Warranty field is invalid."; }
		if(!preg_match("/[0-9]{1,4}/", $_POST["dashboard_widget_pos"])) { $disklocation_error[] = "Dashboard widget position invalid."; }
		
		//if(!preg_match("/[0-9]{1,5}/", $_POST["reallocated_sector_w"])) { $disklocation_error[] = "SMART: Invalid number."; }
		//if(!preg_match("/[0-9]{1,5}/", $_POST["reported_uncorr_w"])) { $disklocation_error[] = "SMART: Invalid number."; }
		//if(!preg_match("/[0-9]{1,5}/", $_POST["command_timeout_w"])) { $disklocation_error[] = "SMART: Invalid number."; }
		//if(!preg_match("/[0-9]{1,5}/", $_POST["pending_sector_w"])) { $disklocation_error[] = "SMART: Invalid number."; }
		//if(!preg_match("/[0-9]{1,5}/", $_POST["offline_uncorr_w"])) { $disklocation_error[] = "SMART: Invalid number."; }
		
		/*
		$dashboard_widget_array = dashboard_toggle($_POST["dashboard_widget"], $_POST["dashboard_widget_pos"]);
		$dashboard_widget = $dashboard_widget_array["current"];
		$dashboard_widget_pos = $dashboard_widget_array["position"];
		*/
		cronjob_timer($_POST["smart_updates"],$_POST["smart_updates_url"]);
		config(DISKLOCATION_CONF, 'w', 'database_noscan', $_POST["database_noscan"]);
		config(DISKLOCATION_CONF, 'w', 'signal_css', $_POST["signal_css"]);
		use_stylesheet($_POST["signal_css"]);
		
		// Infomation
		if(empty($_POST["select_db_info"])) { $_POST["select_db_info"] = $select_db_info_default; }
		if(empty($_POST["sort_db_info"])) { $_POST["sort_db_info"] = $sort_db_info_default; }
		$get_table_order_info = get_table_order($_POST["select_db_info"], $_POST["sort_db_info"], 2, "1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1");
		$get_table_order_info .= get_table_order($_POST["select_db_info"], $_POST["sort_db_info"], 3, "1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1");
		if($get_table_order_info) { $disklocation_error[] = "Table \"Information\": " . $get_table_order_info; }
		
		// Tray Allocations / Unassigned
		if(empty($_POST["select_db_trayalloc"])) { $_POST["select_db_trayalloc"] = $select_db_trayalloc_default; }
		if(empty($_POST["sort_db_trayalloc"])) { $_POST["sort_db_trayalloc"] = $sort_db_trayalloc_default; }
		$get_table_order_trayalloc = get_table_order($_POST["select_db_trayalloc"], $_POST["sort_db_trayalloc"], 2, "0,0,1,1,1,1,1,0,1,0,0,0,0,0,0,0,0,1,1,1,1,0,0,0,0,0,0,0,1,1,1,0,1,1");
		$get_table_order_trayalloc .= get_table_order($_POST["select_db_trayalloc"], $_POST["sort_db_trayalloc"], 3, "1,1,1,1,1,1,1,0,1,0,0,0,0,0,0,0,0,1,1,1,1,0,0,0,0,0,0,0,1,1,1,0,1,1");
		if($get_table_order_trayalloc) { $disklocation_error[] = "Table \"Tray Allocations\": " . $get_table_order_trayalloc; }
		
		// History
		if(empty($_POST["select_db_drives"])) { $_POST["select_db_drives"] = $select_db_drives_default; }
		if(empty($_POST["sort_db_drives"])) { $_POST["sort_db_drives"] = $sort_db_drives_default; }
		$get_table_order_drives = get_table_order($_POST["select_db_drives"], $_POST["sort_db_drives"], 2, "0,0,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1");
		$get_table_order_drives .= get_table_order($_POST["select_db_drives"], $_POST["sort_db_drives"], 3, "0,0,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1");
		if($get_table_order_drives) { $disklocation_error[] = "Table \"History\": " . $get_table_order_drives; }
		
		// Devices textarea, manual sort
		if(empty($_POST["select_db_devices"])) { $_POST["select_db_devices"] = $select_db_devices_default; }
		if(!bscode2html($_POST["select_db_devices"])) { $disklocation_error[] = "Table \"Devices\": Content could not be parsed."; }
		
		if(empty($disklocation_error)) {
			$sql .= "
				REPLACE INTO
					settings(
						id,
						smart_exec_delay,
						smart_updates,
						bgcolor_parity,
						bgcolor_unraid,
						bgcolor_cache,
						bgcolor_others,
						bgcolor_empty,
						tray_reduction_factor,
						force_orb_led,
						warranty_field,
						dashboard_widget,
						dashboard_widget_pos,
						css_serial_number_highlight,
						displayinfo,
						select_db_info,
						sort_db_info,
						select_db_trayalloc,
						sort_db_trayalloc,
						select_db_drives,
						sort_db_drives,
						select_db_devices
					)
					VALUES(
						'1',
						'" . $_POST["smart_exec_delay"] . "',
						'" . $_POST["smart_updates"] . "',
						'" . $_POST["bgcolor_parity"] . "',
						'" . $_POST["bgcolor_unraid"] . "',
						'" . $_POST["bgcolor_cache"] . "',
						'" . $_POST["bgcolor_others"] . "',
						'" . $_POST["bgcolor_empty"] . "',
						'" . $_POST["tray_reduction_factor"] . "',
						'" . $_POST["force_orb_led"] . "',
						'" . $_POST["warranty_field"] . "',
						'" . SQLite3::escapeString($_POST["dashboard_widget"] ?? null) . "',
						'" . $_POST["dashboard_widget_pos"] . "',
						'" . SQLite3::escapeString($_POST["css_serial_number_highlight"] ?? $css_serial_number_highlight_default) . "',
						'" . $post_info . "',
						'" . SQLite3::escapeString($_POST["select_db_info"] ?? $select_db_info_default) . "',
						'" . SQLite3::escapeString($_POST["sort_db_info"] ?? $sort_db_info_default) . "',
						'" . SQLite3::escapeString($_POST["select_db_trayalloc"] ?? $select_db_trayalloc_default) . "',
						'" . SQLite3::escapeString($_POST["sort_db_trayalloc"] ?? $sort_db_trayalloc_default) . "',
						'" . SQLite3::escapeString($_POST["select_db_drives"] ?? $select_db_drives_default) . "',
						'" . SQLite3::escapeString($_POST["sort_db_drives"] ?? $sort_db_drives_default) . "',
						'" . SQLite3::escapeString($_POST["select_db_devices"] ?? $select_db_devices_default) . "'
					)
				;
			";
			
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
	}
	
	if(isset($_POST["save_groupsettings"]) && isset($_POST["groupid"])) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE GROUP SETTINGS has been pressed.");
		$sql = "";
		
		// settings
		if(!preg_match("/\b(column|row)\b/", $_POST["grid_count"])) { $disklocation_error[] = "Physical tray assignment invalid."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["grid_columns"])) { $disklocation_error[] = "Grid columns missing or number invalid."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["grid_rows"])) { $disklocation_error[] = "Grid rows missing or number invalid."; }
		if($_POST["grid_trays"] && !preg_match("/[0-9]{1,3}/", $_POST["grid_trays"])) { $disklocation_error[] = "Grid trays number invalid."; }
		if(!preg_match("/(h|v)/", $_POST["disk_tray_direction"])) { $disklocation_error[] = "Physical tray direction invalid."; }
		if(!preg_match("/[0-9]{1}/", $_POST["tray_direction"])) { $disklocation_error[] = "Tray number direction invalid."; }
		if(!preg_match("/[0-9]{1,7}/", $_POST["tray_start_num"])) { $disklocation_error[] = "Tray start number invalid."; }
		if(!preg_match("/[0-9]{1,4}/", $_POST["tray_width"])) { $disklocation_error[] = "Tray's longest side outside limits or invalid number entered."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["tray_height"])) { $disklocation_error[] = "Tray's smallest side outside limits or invalid number entered."; }
		if(!preg_match("/[0-9]{1,}/", $_POST["groupid"])) { $disklocation_error[] = "Expected group ID to be an integer."; }
		
		if(empty($disklocation_error)) {
			$sql .= "
				UPDATE settings_group SET
					group_name = '" . SQLite3::escapeString($_POST["group_name"]) . "',
					grid_count = '" . $_POST["grid_count"] . "',
					grid_columns = '" . $_POST["grid_columns"] . "',
					grid_rows = '" . $_POST["grid_rows"] . "',
					grid_trays = '" . ( empty($_POST["grid_trays"]) ? null : $_POST["grid_trays"] ) . "',
					disk_tray_direction = '" . $_POST["disk_tray_direction"] . "',
					tray_direction = '" . $_POST["tray_direction"] . "',
					tray_start_num = '" . $_POST["tray_start_num"] . "',
					tray_width = '" . $_POST["tray_width"] . "',
					tray_height = '" . $_POST["tray_height"] . "'
				WHERE id = '" . $_POST["groupid"] . "'
				;
			";
			
			debug_print($debugging_active, __LINE__, "SQL", "GROUP SETTINGS: <pre>" . $sql . "</pre>");
			
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
		force_reset_color($db, "*");
		//if(force_reset_color($db, "*")) {
			//$db->close();
			//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
			//exit;
		//}
	}
	if(isset($_POST["reset_common_colors"])) {
		force_reset_color($db);
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
