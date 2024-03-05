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
	
	// Tray Allocations
	
	if(!empty($disklocation_error)) {
		$i=0;
		print("<h2 style=\"color: #FF0000; font-weight: bold;\">");
		while($i < count($disklocation_error)) {
			print("&middot; ERROR: " . $disklocation_error[$i] . "<br />");
			$i++;
		}
		print("</h2><hr style=\"border: 1px solid #FF0000;\" />");
	}
	
	$get_trayalloc_select = get_table_order($select_db_trayalloc, $sort_db_trayalloc, 1);
	$get_drives_select = get_table_order($select_db_drives, $sort_db_drives, 1);

	$sql = "SELECT * FROM settings_group ORDER BY id ASC";
	$results = $db->query($sql);
	
	$a=1;
	while($datagroup = $results->fetchArray(1)) {
		$group[$a]["id"] = $datagroup["id"];
		$group[$a]["group_name"] = $datagroup["group_name"];
		
		$a++;
	}
	
	if(!$total_groups) {
		$sql = "SELECT * FROM disks WHERE status IS NULL;";
	}
	else {
		//$sql = "SELECT * FROM disks JOIN location ON disks.hash=location.hash WHERE status IS NULL ORDER BY groupid,tray ASC;";
		$sql = "SELECT disks.id,location.id,disks.hash,location.hash,groupid,tray,color,warranty," . implode(",", $get_trayalloc_select["sql_select"]) . " FROM disks JOIN location ON disks.hash=location.hash WHERE status IS NULL ORDER BY " . $get_trayalloc_select["sql_sort"] . " " . $get_trayalloc_select["sql_dir"] . ";";
	}
	
	$i=1;
	$i_empty=1;
	$i_drive=1;
	
	$print_drives = array();
	$datasql = array();
	$custom_colors_array = array();
	
	list($table_trayalloc_order_user, $table_trayalloc_order_system, $table_trayalloc_order_name, $table_trayalloc_order_full, $table_trayalloc_order_forms) = get_table_order($select_db_trayalloc, $sort_db_trayalloc);
	
	$arr_length = count($table_trayalloc_order_user);
	for($i=0;$i<$arr_length;$i++) {
		$table_trayalloc_order_name_html .= "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\"><b style=\"cursor: help;\" title=\"" . $table_trayalloc_order_full[$i] . "\">" . $table_trayalloc_order_name[$i] . "</b></td>";
	}
	
	$results = $db->query($sql);
	//while($i < $total_disks) {
	while($res = $results->fetchArray(1)) {
		array_push($datasql, $res);
		$warr_options = "";
		
		$data = $datasql[$i_drive-1];
		
		if($data["color"]) {
			array_push($custom_colors_array, $data["color"]);
		}
		
		$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
		
		$tray_options = "";
		$group_options = "";
		for($tray_i = 1; $tray_i <= $biggest_tray_group; ++$tray_i) {
			if($tray_assign == $tray_i) { $selected="selected"; } else { $selected=""; }
			$tray_options .= "<option value=\"$tray_i\" " . $selected . " style=\"text-align: right;\">$tray_i</option>";
		}
		
		for($group_i = 1; $group_i <= $total_groups; ++$group_i) {
			$gid = $group[$group_i]["id"];
			$gid_name = ( empty($group[$group_i]["group_name"]) ? $gid : $group[$group_i]["group_name"] );
			if($data["groupid"] == $gid) { $selected="selected"; } else { $selected=""; }
			$group_options .= "<option value=\"$gid\" " . $selected . " style=\"text-align: left;\">" . stripslashes(htmlspecialchars($gid_name)) . "</option>";
		}
		
		$warr_input = "";
		
		if($warranty_field == "u") {
			for($warr_i = 6; $warr_i <= (6*10); $warr_i+=6) {
				if($data["warranty"] == $warr_i) { $selected="selected"; } else { $selected=""; }
				$warr_options .= "<option value=\"$warr_i\" " . $selected . " style=\"text-align: right;\">$warr_i months</option>";
			}
			$warr_input = "<select name=\"warranty[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 80px; width: 80px;\"><option value=\"\" style=\"text-align: right;\">unknown</option>" . $warr_options . "</select>";
		}
		else {
			$warr_input = "<input type=\"date\" name=\"warranty_date[" . $data["hash"] . "]\" max=\"9999-12-31\" value=\"" . $data["warranty_date"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" />";
		}
		
		$smart_rotation = get_smart_rotation($data["smart_rotation"]);
		
		$bgcolor = ( empty($data["color"]) ? $color_array[$data["hash"]] : $data["color"] );
		
		$print_drives[$i_drive] .= "<tr style=\"background: #" . $color_array[$data["hash"]] . ";\">";
		$print_drives[$i_drive] .= "
				<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: left;\">
					<button type=\"submit\" name=\"hash_remove\" value=\"" . $data["hash"] . "\" title=\"This will force move the drive to the &quot;History&quot; section.\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px; background-color: #FFFFFF;\"><i style=\"font-size: 15px;\" class=\"fa fa-minus-circle fa-lg\"/></i></button>
				</td>
				<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><select name=\"groups[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 150px; min-width: 40px;\"><option value=\"\" selected style=\"text-align: left;\">--</option>" . $group_options . "</select></td>
				<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><select name=\"drives[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><option value=\"\" selected style=\"text-align: right;\">--</option>" . $tray_options . "</select></td>
				<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: center;\"><input type=\"button\" class=\"diskLocation\" style=\"transform: none;\" onclick=\"locateStart()\" value=\"Locate\" id=\"" . $data["device"] . "\" name=\"" . $data["device"] . "\" /></td>
		";
		
		$columns_trayalloc_out = array(
			"smart_status" => "<td><i>unavailable</i></td>",
			"smart_temperature" => "<td><i>unavailable</i></td>",
			"smart_powerontime" => "<td><i>unavailable</i></td>",
			"smart_loadcycle" => "<td><i>unavailable</i></td>",
			"smart_nvme_available_spare" => "<td><i>unavailable</i></td>",
			"smart_nvme_available_spare_threshold" => "<td><i>unavailable</i></td>",
			"smart_nvme_percentage_used" => "<td><i>unavailable</i></td>",
			"smart_nvme_data_units_read" => "<td><i>unavailable</i></td>",
			"smart_nvme_data_units_written" => "<td><i>unavailable</i></td>",
			"device" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["device"] . "</td>",
			"devicenode" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["devicenode"] . "</td>",
			"luname" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["luname"] . "</td>",
			"model_family" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["model_family"] . "</td>",
			"model_name" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["model_name"] . "</td>",
			"smart_serialnumber" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . substr($data["smart_serialnumber"], $dashboard_widget_pos) . "</td>",
			"smart_capacity" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . human_filesize($data["smart_capacity"], 1, true) . "</td>",
			"smart_cache" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ($data["smart_cache"] ? $data["smart_cache"] . "MB" : "") . "</td>",
			"smart_rotation" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $smart_rotation . "</td>",
			"smart_formfactor" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["smart_formfactor"] . "</td>",
			"manufactured" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"manufactured[" . $data["hash"] . "]\" max=\"9999-12-31\" value=\"" . $data["manufactured"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>",
			"purchased" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"purchased[" . $data["hash"] . "]\" max=\"9999-12-31\" value=\"" . $data["purchased"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>",
			"installed" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"installed[" . $data["hash"] . "]\" max=\"9999-12-31\" value=\"" . $data["installed"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>",
			"warranty_date" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $warr_input . "</td>",
			"comment" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input type=\"text\" name=\"comment[" . $data["hash"] . "]\" value=\"" . stripslashes(htmlspecialchars($data["comment"])) . "\" style=\"width: 150px;\" /></td>"
		);
		
		$arr_length = count($table_trayalloc_order_system);
		for($i=0;$i<$arr_length;$i++) {
			$print_drives[$i_drive] .= $columns_trayalloc_out[$table_trayalloc_order_system[$i]];
		}
		
		$print_drives[$i_drive] .= "
				<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">
					<input type=\"color\" name=\"bgcolor_custom[" . $data["hash"] . "]\" list=\"disklocationColors\" value=\"#" . $bgcolor . "\" " . ($dashboard_widget ? "disabled=\"disabled\"" : null ) . " />
					" . ($dashboard_widget ? "<input type=\"hidden\" name=\"bgcolor_custom[" . $data["hash"] . "]\" value=\"#" . $bgcolor . "\" />" : null ) . "
				</td>

		";
		$print_drives[$i_drive] .= "</tr>";
		
		$i_drive++;
		$i++;
	}
	
	// Unassigned Devices
	
	$data = "";
	//$sql = "SELECT * FROM disks WHERE status = 'h' ORDER BY ID ASC;";
	$sql = "SELECT id,hash,color,warranty," . implode(",", $get_trayalloc_select["sql_select"]) . " FROM disks WHERE status = 'h' ORDER BY id " . $get_trayalloc_select["sql_dir"] . ";";
	
	$results = $db->query($sql);
	$print_add_drives = "";
	$warr_options = "";
	
	while($data = $results->fetchArray(1)) {
		$tray_options = "";
		$group_options = "";
		
		for($tray_i = 1; $tray_i <= $biggest_tray_group; ++$tray_i) {
			$tray_options .= "<option value=\"$tray_i\" style=\"text-align: right;\">$tray_i</option>";
		}
		
		for($group_i = 1; $group_i <= $total_groups; ++$group_i) {
			$gid = $group[$group_i]["id"];
			$gid_name = ( empty($group[$group_i]["group_name"]) ? $gid : $group[$group_i]["group_name"] );
			$group_options .= "<option value=\"$gid\" style=\"text-align: left;\">" . stripslashes(htmlspecialchars($gid_name)) . "</option>";
			
		}
		
		$warr_input = "";
		
		if($warranty_field == "u") {
			for($warr_i = 6; $warr_i <= (6*10); $warr_i+=6) {
				if($data["warranty"] == $warr_i) { $selected="selected"; } else { $selected=""; }
				$warr_options .= "<option value=\"$warr_i\" " . $selected . " style=\"text-align: right;\">$warr_i months</option>";
			}
			$warr_input = "<select name=\"warranty[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 80px; width: 80px;\"><option value=\"\" style=\"text-align: right;\">unknown</option>" . $warr_options . "</select>";
		}
		else {
			$warr_input = "<input type=\"date\" name=\"warranty_date[" . $data["hash"] . "]\" max=\"9999-12-31\" value=\"" . $data["warranty_date"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" />";
		}
		
		$smart_rotation = get_smart_rotation($data["smart_rotation"]);
		
		$bgcolor = ( empty($data["color"]) ? $bgcolor_empty : $data["color"] );
		
		$print_add_drives .= "<tr style=\"background: #" . $color_array[$data["hash"]] . ";\">";
		$print_add_drives .= "
				<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: left;\">
					<button type=\"submit\" name=\"hash_remove\" value=\"" . $data["hash"] . "\" title=\"This will force move the drive to the &quot;History&quot; section.\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px; background-color: #FFFFFF;\"><i style=\"font-size: 15px;\" class=\"fa fa-minus-circle fa-lg\"/></i></button>
				</td>
				<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><select name=\"groups[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 150px; min-width: 40px;\"><option value=\"\" selected style=\"text-align: left;\">--</option>" . $group_options . "</select></td>
				<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><select name=\"drives[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><option value=\"\" selected style=\"text-align: right;\">--</option>" . $tray_options . "</select></td>
				<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: center;\"><input type=\"button\" class=\"diskLocation\" style=\"transform: none;\" onclick=\"locateStart()\" value=\"Locate\" id=\"" . $data["device"] . "\" name=\"" . $data["device"] . "\" /></td>
		";
		// "device" "devicenode" "luname" "model_family" "model_name" "smart_serial" "smart_capacity" "smart_rotation" "manufactured" "purchased" "warranty_date" "comment"
		$columns_drives_out = array(
			"smart_status" => "<td><i>unavailable</i></td>",
			"smart_temperature" => "<td><i>unavailable</i></td>",
			"smart_powerontime" => "<td><i>unavailable</i></td>",
			"smart_loadcycle" => "<td><i>unavailable</i></td>",
			"smart_nvme_available_spare" => "<td><i>unavailable</i></td>",
			"smart_nvme_available_spare_threshold" => "<td><i>unavailable</i></td>",
			"smart_nvme_percentage_used" => "<td><i>unavailable</i></td>",
			"smart_nvme_data_units_read" => "<td><i>unavailable</i></td>",
			"smart_nvme_data_units_written" => "<td><i>unavailable</i></td>",
			"device" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["device"] . "</td>",
			"devicenode" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["devicenode"] . "</td>",
			"luname" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["luname"] . "</td>",
			"model_family" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["model_family"] . "</td>",
			"model_name" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $data["model_name"] . "</td>",
			"smart_serialnumber" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . substr($data["smart_serialnumber"], $dashboard_widget_pos) . "</td>",
			"smart_capacity" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . human_filesize($data["smart_capacity"], 1, true) . "</td>",
			"smart_cache" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . ($data["smart_cache"] ? $data["smart_cache"] . "MB" : "") . "</td>",
			"smart_rotation" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $smart_rotation . "</td>",
			"smart_formfactor" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["smart_formfactor"] . "</td>",
			"manufactured" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"manufactured[" . $data["hash"] . "]\" max=\"9999-12-31\" value=\"" . $data["manufactured"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>",
			"purchased" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"purchased[" . $data["hash"] . "]\" max=\"9999-12-31\" value=\"" . $data["purchased"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>",
			"installed" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"installed[" . $data["hash"] . "]\" max=\"9999-12-31\" value=\"" . $data["installed"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>",
			"warranty_date" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $warr_input . "</td>",
			"comment" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input type=\"text\" name=\"comment[" . $data["hash"] . "]\" value=\"" . stripslashes(htmlspecialchars($data["comment"])) . "\" style=\"width: 150px;\" /></td>"
		);
		
		$arr_length = count($table_trayalloc_order_system);
		for($i=0;$i<$arr_length;$i++) {
			$print_add_drives .= $columns_drives_out[$table_trayalloc_order_system[$i]];
		}
		
		$print_add_drives .= "
				<td style=\"padding: 0 10px 0 10px;\">
					<input type=\"color\" name=\"bgcolor_custom[" . $data["hash"] . "]\" list=\"disklocationColors\" value=\"#" . $bgcolor . "\" " . ($dashboard_widget ? "disabled=\"disabled\"" : null ) . " />
					" . ($dashboard_widget ? "<input type=\"hidden\" name=\"bgcolor_custom[" . $data["hash"] . "]\" value=\"#" . $bgcolor . "\" />" : null ) . "
				</td>
		";
		$print_add_drives .= "</tr>";

	}
	
	// History
	
	$data = "";
	
	list($table_drives_order_user, $table_drives_order_system, $table_drives_order_name, $table_drives_order_full, $table_drives_order_forms) = get_table_order($select_db_drives, $sort_db_drives);
	
	$arr_length = count($table_drives_order_user);
	for($i=0;$i<$arr_length;$i++) {
		$table_drives_order_name_html .= "
			<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">
				<b style=\"cursor: help;\" title=\"" . $table_drives_order_full[$i] . "\">" . $table_drives_order_name[$i] . "</b><br />
				<button type=\"submit\" name=\"sort\" value=\"drives:asc:" . $table_drives_order_user[$i] . "\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px;\" /><i style=\"font-size: 15px;\" class=\"fa fa-solid fa-sort-up\"/></i></button>
				<button type=\"submit\" name=\"sort\" value=\"drives:desc:" . $table_drives_order_user[$i] . "\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px;\" /><i style=\"font-size: 15px;\" class=\"fa fa-solid fa-sort-down\"/></i></button>
			</td>
		";
	}
	
	//$sql = "SELECT * FROM disks WHERE status = 'r' ORDER BY ID DESC;";
	$sql = "SELECT id,hash,smart_logical_block_size,color,warranty," . implode(",", $get_drives_select["sql_select"]) . " FROM disks WHERE status = 'r' ORDER BY " . $get_drives_select["sql_sort"] . " " . $get_drives_select["sql_dir"] . ";";
	
	$results = $db->query($sql);
	$print_removed_drives = "";
	
	while($data = $results->fetchArray(1)) {
		$hash = $data["hash"];
		$smart_powerontime = ( empty($data["smart_powerontime"]) ? null : seconds_to_time($data["smart_powerontime"] * 60 * 60) );
		$smart_capacity = ( empty($data["smart_capacity"]) ? null : human_filesize($data["smart_capacity"], 1, true) );
		
		$warr_input = "";
		if($warranty_field == "u") {
			$warr_input = "<select name=\"warranty[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 80px; width: 80px;\"><option value=\"\" style=\"text-align: right;\">unknown</option>" . $warr_options . "</select>";
		}
		else {
			$warr_input = "<input type=\"date\" name=\"warranty_date[" . $data["hash"] . "]\" value=\"" . $data["warranty_date"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" />";
		}
		
		$smart_rotation = get_smart_rotation($data["smart_rotation"]);
		
		if($data["smart_rotation"] == -2) {
			$smart_units_read = ( empty($data["smart_units_read"]) ? null : human_filesize(smart_units_to_bytes($data["smart_units_read"], $data["smart_logical_block_size"], true), 1, true) );
			$smart_units_written = ( empty($data["smart_units_written"]) ? null : human_filesize(smart_units_to_bytes($data["smart_units_written"], $data["smart_logical_block_size"], true), 1, true) );
		}
		else {
			$smart_units_read = ( empty($data["smart_units_read"]) ? null : human_filesize(smart_units_to_bytes($data["smart_units_read"], $data["smart_logical_block_size"], true, true), 1, true) );
			$smart_units_written = ( empty($data["smart_units_written"]) ? null : human_filesize(smart_units_to_bytes($data["smart_units_written"], $data["smart_logical_block_size"], true, true), 1, true) );
		}
		
		$warranty_expire = "";
		$warranty_left = "";
		if($data["purchased"] && $data["warranty"]) {
			$warranty_start = strtotime($data["purchased"]);
			$warranty_end = strtotime("" . $data["purchased"] . " +" . $data["warranty"] . " month");
			$warranty_expire = date("Y-m-d", $warranty_end);
			$warranty_expire_left = $warranty_end-date("U");
			if($warranty_expire_left > 0) {
				$warranty_left = seconds_to_time($warranty_expire_left);
			}
			else {
				$warranty_left = "EXPIRED!";
			}
		}
		
		$columns_drives_out2 = array(
			"groupid" => "<td><i>unavailable</i></td>",
			"tray" => "<td><i>unavailable</i></td>",
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
			"smart_temperature" => "<td><i>unavailable</i></td>",
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
			"installed" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["installed"] . "</td>",
			"removed" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["removed"] . "</td>",
			"warranty_date" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><span style=\"cursor: help;\" title=\"Warranty: " . $date_warranty . " Expires: " . $warranty_left . "\">" . $warranty_expire . "</span></td>",
			"comment" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . bscode2html(stripslashes(htmlspecialchars($data["comment"]))) . "</td>"
		);
		
		$print_removed_drives .= "<tr style=\"background: #" . $color_array[$data["hash"]] . ";\">";
		$print_removed_drives .= "
			<td style=\"padding: 0 10px 0 10px; white-space: nowrap;\">
				<button type=\"submit\" name=\"hash_delete\" value=\"" . $data["hash"] . "\" title=\"Delete, this will flag the drive hidden in the database.\" style=\"min-width: 0; background-size: 0; margin: 0; padding: 0;\"><i style=\"font-size: 15px;\" class=\"fa fa-minus-circle fa-lg\"></i></button>
				<button type=\"submit\" name=\"hash_add\" value=\"" . $data["hash"] . "\" title=\"Add, will revert to &quot;not found list&quot; if the drive really does not exists.\" style=\"min-width: 0; background-size: 0; margin: 0; padding: 0;\"><i style=\"font-size: 15px;\" class=\"fa fa-plus-circle fa-lg\"></i></button>
			</td>
		";
		$arr_length = count($table_drives_order_system);
		for($i=0;$i<$arr_length;$i++) {
			$print_removed_drives .= $columns_drives_out2[$table_drives_order_system[$i]];
		}
		$print_removed_drives .= "</tr>";
	}
	
	$sql = "SELECT * FROM settings_group ORDER BY id ASC";
	$results = $db->query($sql);
	
	$disk_layouts_alloc = "";
	
	while($group = $results->fetchArray(1)) {
		extract($group);
		
		$gid = $id;
		$groupid = $gid;
		
		$css_grid_group = "
			grid-template-columns: " . $grid_columns_styles[$gid] . ";
			grid-template-rows: " . $grid_rows_styles[$gid] . ";
			grid-auto-flow: " . $grid_count . ";
		";
		
		$disk_layouts_alloc .= "
			<div style=\"float: left; padding: 10px 20px 10px 20px;\">
				<h2 style=\"text-align: center;\">
					" . ( empty($group_name) ? $gid : $group_name ) . "
				</h2>
				<blockquote class='inline_help'>
					This is the group name (if any)
				</blockquote>
				<div class=\"grid-container\" style=\"" . $css_grid_group . "\">
					" . $disklocation_alloc[$gid] . "
				</div>
				<blockquote class='inline_help'>
					This shows you an overview of your configured tray layout
				</blockquote>
			</div>
		";
		$a++;
	}
	
	$bgcolor_custom_array = "";
	if(isset($custom_colors_array)) {
		$custom_colors_array_dedup = array_values(array_unique($custom_colors_array));
		for($i=0; $i < count($custom_colors_array_dedup); ++$i) {
			$bgcolor_custom_array .= "<option>#" . $custom_colors_array_dedup[$i] . "</option>\n";
		}
	}
?>
<?php if($db_update == 2) { print("<h3>Page unavailable due to database error.</h3><!--"); } ?>
<datalist id="disklocationColors">
	<option>#<?php echo $bgcolor_empty ?></option>
	<?php echo $bgcolor_custom_array ?>
</datalist>
<form action="" method="post">
	<?php print($disk_layouts_alloc); ?>
	<div style="clear: both;"></div>
	<p style="color: red;"><b>OBS! When allocating drives you must use the TrayID numbers shown in bold and not the physical tray assignment shown on the right/bottom (these are only shown if the numbers differ).</b>
	<?php
		if($dashboard_widget) { 
			print("<br />Custom Color is disabled when \"Heat Map\" is used.");
		}
	?>
	</p>
	<div><br /></div>
	<table style="width: 800px; border-spacing: 3px; border-collapse: separate; padding: 0 0 25px 0;">
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
	<div><br /><br /></div>
	<table style="table-layout: auto; width: 0;">
		<tr>
			<td style="vertical-align: top; padding-left: 20px;">
				<table style="table-layout: auto; width: 0; border-spacing: 2px; border-collapse: separate;">
					<tr>
						<td style="padding: 10px 10px 0 10px;" colspan="15">
							<h2>Allocations</h2>
							<p style="margin-top: -10px; padding: 0 0 30px 0;">
								<b>Warning! Please use "Force scan all" button under "System" tab before manually deleting and/or re-adding devices manually.</b><br />
								The <i class="fa fa-minus-circle fa-lg"></i> button will force the drive to be moved to the "History" section below. Use this if you have false drive(s) in your list.
								If you accidentally click the button on the wrong drive you have to do a "Force scan all" and reassign the drive.
							</p>
						</td>
					</tr>
					<tr>
						<td style="width: 0; padding: 0 10px 0 10px;"><b>#</b></td>
						<td style="width: 0; padding: 0 10px 0 10px;"><b>Group</b></td>
						<td style="width: 0; padding: 0 10px 0 10px;"><b>TrayID</b></td>
						<td style="width: 0; padding: 0 10px 0 10px;"><b>Locate</b></td>
						<?php print($table_trayalloc_order_name_html); ?>
						<td style="width: 0; padding: 0 10px 0 10px;"><b>Custom Color</b></td>
					</tr>

					<?php 
						$i=1;
						while($i <= count($print_drives)) {
							print($print_drives[$i]);
							$i++;
						}
						if(!empty($print_add_drives)) {
							print("
									<tr>
										<td style=\"padding: 10px 10px 0 10px;\" colspan=\"15\">
											<h2>Unassigned Devices</h2>
										</td>
									</tr>
									<tr style=\"border: solid 1px #000000;\">
										<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px;\"><b>#</b></td>
										<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px;\"><b>Group</b></td>
										<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px;\"><b>TrayID</b></td>
										<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px;\"><b>Locate</b></td>
										$table_trayalloc_order_name_html
										<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px;\"><b>Custom Color</b></td>
									</tr>
									$print_add_drives
							");
						}
					?>
					<tr>
						<td style="padding: 10px 10px 0 10px;" colspan="15">
							<hr />
							<input type="hidden" name="current_warranty_field" value="<?php echo $warranty_field ?>" />
							<input type="submit" name="save_allocations" value="Save" />
							<span style="padding-left: 50px;"></span>
							<input type="submit" name="reset_all_colors" value="Reset All Custom Colors" /> <b>or choose "Empty" color (first color listed) per device under "Custom Color" to reset, and then hit the "Save" button.</b>
							<blockquote class='inline_help'>
								<ul>
									<li>"Save" button will store all information entered.</li>
									<li>"Reset All Custom Colors" will delete all custome stored colors from the database.</li>
								</ul>
							</blockquote>
						</td>
					</tr>
				</table>
				<?php
					if(isset($print_removed_drives)) {
						print("
							<table style=\"table-layout: auto; width: 0; border-spacing: 2px; border-collapse: separate;\">
								<tr>
									<td style=\"padding: 10px 10px 0 10px;\" colspan=\"15\">
										<h2>History</h2>
										<p style=\"padding: 0 0 30px 0;\">
											Warning! The <i class=\"fa fa-minus-circle fa-lg\"></i> button will hide the device permanently from this plugin and can only be reverted by manually changing the flag in the database file (\"Force scan all\" button will not touch hidden devices).<br />
											While the <i class=\"fa fa-plus-circle fa-lg\"></i> button will re-add the drive to the main list for tray allocation, it will revert back to the not found list if the drive does actually not exists after using \"Force scan all\".
										</p>
									</td>
								</tr>
								<tr style=\"border: solid 1px #000000;\">
									<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\"><b>#</b></td>
									$table_drives_order_name_html
								</tr>
								$print_removed_drives
							</table>
						");
					}
				?>
				<blockquote class='inline_help'>
					<dt>Tray allocations</dt>
					<dd>Select where to assign the drives and the empty trays, be sure to select a unique tray slot number. It will detect failure and none of the new settings will be saved.</dd>
					
					<dt>Purchased and Warranty</dt>
					<dd>For Unraid array drives which already got the date set, this will be detected (and eventually overwrite) by the main configuration. This plugin will not touch that, unless if those does not exists in the first place. For unassigned devices, you can enter a date of purchase and warranty.</dd>
					
					<dt>Comment</dt>
					<dd>Enter a comment, like where you bought the drive or anything else you'd like. Formatting tools:</dd>
					<dd>
						Bold: [b]<b>text</b>[/b] or *<b>text</b>*<br />
						Italic: [i]<i>text</i>[/i] or _<i>text</i>_<br />
						<br />
						Font sizes:
						<span style="font-size: xx-small">tiny</span>
						<span style="font-size: x-small">small</span>
						<span style="font-size: medium">medium</span>
						<span style="font-size: large">large</span>
						<span style="font-size: x-large">huge</span>
						<span style="font-size: xx-large">massive</span>
						<br />
						E.g. [large]text[/large]<br />
						<br />
						Force break: [br]<br />
						<br />
						Color: [color:HEX]text[/color]<br />
						E.g. [color:0000FF][b]text[/b][/color] = <span style="color: #0000FF;"><b>text</b></span>
					</dd>
					
					<dt>Custom Color</dt>
					<dd>
						Choosing a color here will store it for the specific disk and will override any other color properties.<br />
						Reset/delete the color by choosing the default color for empty (the first color available in the list).
					</dd>
					
					<dt>"Locate" button</dt>
					<dd>The "Locate" button will make your harddisk blink on the LED, this is mainly useful for typical hotswap trays with a LED per tray.</dd>
					
					<dt>"Locate" button does not work</dt>
					<dd>This might not work on all devices, like SSD's.</dd>
					
					<dt>LED is blinking continously after using "Locate"</dt>
					<dd>Just enter the plugin from the Unraid settings page and it should automatically shut down the locate script. Else it will run continously until stopped or rebooted.</dd>
				</blockquote>
				<blockquote class='inline_help'>
					<dt>"History buttons"</dt>
					<ul>
						<li>Delete, this will flag the drive hidden in the database.</li>
						<li>Add, will revert to &quot;not found list&quot; if the drive does not exists, but will reappear in the configuration if it really does. Usually it shouldn't be any need for this.</li>
					</ul>
				</blockquote>
			</td>
		</tr>
	</table>
</form>
<script type="text/javascript" src="<?autov("" . DISKLOCATION_PATH . "/pages/script/locate_script_bottom.js")?>"></script>
<?php if($db_update == 2) { print("-->"); } ?>
