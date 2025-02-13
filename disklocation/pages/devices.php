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
	
	unset($disklocation_page);
	unset($disklocation_layout);
	
	$biggest_tray_group = 0;
	$total_trays_group = 0;
	$datajson = array();
	$array_groups = $get_groups;
	( is_array($array_groups) ?? ksort($array_groups, SORT_NUMERIC) );
	$array_devices = $get_devices;
	$array_locations = $get_locations;
	
	$select_db_devices = ( !empty($select_db_devices) ? $select_db_devices : $select_db_devices_default );
	
	$page_time_load_array = ( $debug || isset($_GET["benchmark"]) ? hrtime(true) : null );
	require_once("array_devices.php");
	$page_time_load["array"] = round((hrtime(true)-$page_time_load_array)/1e+6, 1);
	print(isset($_GET["benchmark"]) ? "<h3 style=\"position: fixed; left: 900px; bottom: 60px; white-space: no-wrap; color: #0099FF; background-color: #111111;\">array: " . round((hrtime(true)-$page_time_load_array)/1e+6, 1) . " ms</h3>\n" : null);
	
	list($table_order_user, $table_order_system, $table_order_name, $table_order_full, $table_order_forms) = get_table_order("all", false);
	
	if(!empty($select_db_devices)) {
		$table_order_user_map = array_map(function($value) { return '/\b'.$value.'\b/u'; }, $table_order_user);
		$select_db_devices_str = preg_replace($table_order_user_map, $table_order_system, $select_db_devices);
	}
	else {
		$select_db_devices_str   = $select_db_devices;
	}
	
	foreach($array_groups as $id => $value) {
		$group_color = "";
		$grid_trays = 0;
		
		$hide_tray = array();
		$count_bypass_tray = 0;
		
		extract($value);
		
		if(!empty($id)) {
			$gid = $id;
			$groupid = $gid;
			$debug_log = debug($debug, basename(__FILE__), __LINE__, "groupid", $groupid);
			$disklocation_page[$gid] = "";
			$disklocation_layout[$gid] = "";
			$disklocation_alloc[$gid] = "";
			$disklocation_dash[$gid] = "";
			
			$i_arr=0;
			if(!$total_groups || empty($array_locations)) {
				foreach($array_devices as $hash => $array) {
					if(!$array_devices[$hash]["status"]) {
						$datajson[$i_arr] = $array_devices[$hash];
						$datajson[$i_arr]["hash"] = $hash;
						$i_arr++;
					}
				}
			}
			else {
				foreach($array_devices as $hash => $array) {
					if(!$array_devices[$hash]["status"] && $array_locations[$hash]["groupid"] == $gid) {
						$datajson[$i_arr] = $array_devices[$hash];
						$datajson[$i_arr]["hash"] = $hash;
						$datajson[$i_arr] += $array_locations[$hash];
						$i_arr++;
					}
					else {
					}
				}
				$datajson = ( !empty($datajson) ? sort_array($datajson, 'groupid', SORT_ASC, SORT_NUMERIC, 'tray', SORT_ASC, SORT_NUMERIC) : array() );
			}
			
			$total_trays = ( empty($grid_trays) ? $grid_columns * $grid_rows : $grid_trays );
			$total_trays_group += $total_trays;
			
			if($biggest_tray_group < $total_trays) {
				$biggest_tray_group = $total_trays;
			}
			
			if(!$tray_direction) { $tray_direction = 1; }
			$tray_number_override = tray_number_assign($grid_columns, $grid_rows, $tray_direction, $grid_count, $hide_tray);
			$tray_number_override = ( !$count_bypass_tray && is_array($hide_tray) ? tray_number_assign($grid_columns, $grid_rows, $tray_direction, $grid_count, $hide_tray) : tray_number_assign($grid_columns, $grid_rows, $tray_direction, $grid_count) );
			
			if(!isset($tray_start_num)) { $tray_start_num = 1; }
			$tray_number_override_start = $tray_start_num;
			
			$total_main_trays = 0;
			if($total_trays > ($grid_columns * $grid_rows)) {
				$total_main_trays = $grid_columns * $grid_rows;
				$total_rows_override_trays = ($total_trays - $total_main_trays) / $grid_columns;
				$grid_columns_override_styles = str_repeat(" auto", $total_rows_override_trays);
			}
		
			if($disk_tray_direction == "h") { 
				$insert_break = "<br />";
			}
			else { 
				$insert_break = "";
				
				$tray_swap_height = $tray_height;
				$tray_swap_width = $tray_width;
				
				$tray_height = $tray_swap_width;
				$tray_width = $tray_swap_height;
			}
			
			$debug_log = debug($debug, basename(__FILE__), __LINE__, "total_trays", $total_trays);
			$debug_log = debug($debug, basename(__FILE__), __LINE__, "tray_number_override", $tray_number_override);
			
			$i_empty=1;
			$i_drive=1;
			$i=1;
			$empty_tray = 0;
			$tray_number = 0;
			
			while($i <= $total_trays) {
				$data = isset($datajson[$i_drive-1]) ? $datajson[$i_drive-1] : 0;
				$smart = array();
				$tray_assign = $i;
				$empty_leddiskop = "";
				$empty_ledsmart = "";
				$empty_ledtemp = "";
				$empty_traytext = "";
				
				if(( isset($data["tray"]) ? $data["tray"] : 0 ) != $i) {
					if(!empty($hide_tray)) {
						if($count_bypass_tray) {
							$tray_number = $tray_assign;
						}
						else if(!$hide_tray[$tray_assign]) {
							$tray_number++;
						}
					}
					else {
						$tray_number = $tray_assign;
					}
					
					$debug_log = debug($debug, basename(__FILE__), __LINE__, "tray_assign", $tray_assign);
					$debug_log = debug($debug, basename(__FILE__), __LINE__, "tray_number", $tray_number);
					
					if($displayinfo["tray"] && empty($displayinfo["hideemptycontents"])) {
						if($tray_number_override[$tray_number]) {
							$empty_tray = ( !isset($tray_number_override_start) ? --$tray_number_override[$tray_number] : ($tray_number_override_start + (!is_numeric($tray_number_override[$tray_number]) ? 0 : $tray_number_override[$tray_number] - 1)));
						}
						else {
							$empty_tray = ( !isset($tray_number_override_start) ? --$tray_number : $tray_number_override_start + $tray_number - 1);
						}
					}
					else {
						$empty_tray = "";
					}
					
					$debug_log = debug($debug, basename(__FILE__), __LINE__, "empty_tray", $empty_tray);
					
					if(isset($displayinfo["leddiskop"]) && $displayinfo["leddiskop"] == 1 && empty($displayinfo["hideemptycontents"])) {
						$empty_leddiskop = get_unraid_disk_status("grey-off", '', '', $force_orb_led);
					}
					if(isset($displayinfo["ledsmart"]) && $displayinfo["ledsmart"] == 1 && empty($displayinfo["hideemptycontents"])) {
						$empty_ledsmart = get_unraid_disk_status("grey-off", '', '', $force_orb_led);
					}
					if(isset($displayinfo["ledtemp"]) && $displayinfo["ledtemp"] == 1 && empty($displayinfo["hideemptycontents"])) {
						$empty_ledtemp = get_unraid_disk_status("grey-off", '', '', $force_orb_led);
					}
					if(empty($displayinfo["hideemptycontents"])) {
						$empty_traytext = "<b>Available disk slot</b>";
					}
					
					$disklocation_page[$gid] .= "
						<div style=\"order: " . $tray_assign . "\">
							" . ( $hide_tray[$tray_assign] ? "<!--" : null ) . "
							<div class=\"flex-container_" . $disk_tray_direction . "\">
								<div style=\"background-color: #" . $color_array["empty"] . "; width: " . $tray_width . "px; height: " . $tray_height . "px;\">
									<div class=\"flex-container-start\" style=\"white-space: nowrap;\">
										<b>$empty_tray</b>$insert_break
										$empty_leddiskop $insert_break
										$empty_ledsmart $insert_break
										$empty_ledtemp
									</div>
									<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
										$empty_traytext
									</div>
									<div class=\"flex-container-end\" style=\"white-space: nowrap;\">
										&nbsp;
									</div>
								</div>
							</div>
							" . ( $hide_tray[$tray_assign] ? "-->" : null ) . "
						</div>
					";
					
					$add_empty_physical_tray_order = "";
					if($tray_assign != $empty_tray) {
						$add_empty_physical_tray_order = $tray_assign;
					}
					
					$disklocation_layout[$gid] .= "
						<div style=\"order: " . $tray_assign . "\">
							<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
								<div style=\"background-color: #" . $color_array["empty"] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
									<div class=\"flex-container-start\">
										<b>" .  ( (!$count_bypass_tray && $hide_tray[$tray_assign]) ? "" : $empty_tray ) . "</b>
									</div>
									<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
									</div>
									<div class=\"flex-container-end\">
										<input type=\"checkbox\" name=\"hide_tray[$groupid][$tray_assign]\" value=\"1\" " . (!empty($hide_tray[$tray_assign]) ? "checked=\"checked\"" : null ) . " style=\"background: transparent; margin: 0; padding: 0;\" />
										<!--" . $add_empty_physical_tray_order . "-->
									</div>
								</div>
							</div>
						</div>
					";
					
					$add_empty_physical_tray_order = "";
					if($tray_assign != $empty_tray) {
						$add_empty_physical_tray_order = $empty_tray;
					}
					
					$disklocation_alloc[$gid] .= "
						<div style=\"order: " . $tray_assign . "\">
							" . ( $hide_tray[$tray_assign] ? "<!--" : null ) . "
							<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
								<div style=\"background-color: #" . $color_array["empty"] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
									<div class=\"flex-container-start\" style=\"/*min-height: 15px;*/\">
										<b><span style=\"color: #FF0000;\">$tray_assign</span></b>
									</div>
									<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
									</div>
									<div class=\"flex-container-end\" style=\"font-size: xx-small;\">
										" . $add_empty_physical_tray_order . "
									</div>
								</div>
							</div>
							" . ( $hide_tray[$tray_assign] ? "-->" : null ) . "
						</div>
					";
					
					$disklocation_dash[$gid] .= "
						<div style=\"order: " . $tray_assign . "\">
							" . ( $hide_tray[$tray_assign] ? "<!--" : null ) . "
							<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
								<div style=\"background-color: #" . $color_array["empty"] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
									<div class=\"flex-container-start\" style=\"/*min-height: 15px;*/\">
										" . get_unraid_disk_status("grey-off", '', '', $force_orb_led) . "
									</div>
									<div class=\"flex-container-middle_" . $disk_tray_direction . "\" style=\"padding: 0 0 10px 0;\">
									</div>
									<div class=\"flex-container-end\">
										<b>" . $empty_tray . "</b>
									</div>
								</div>
							</div>
							" . ( $hide_tray[$tray_assign] ? "-->" : null ) . "
						</div>
					";
					
					$i_empty++;
				}
				else {
					$debug_log = debug($debug, basename(__FILE__), __LINE__, "tray_assign", $tray_assign);
					$device = $data["device"];
					$devicenode = $data["devicenode"];
					$hash = $data["hash"];
					$pool = "";
					$color_override = ( !empty($data["color"]) ? $data["color"] : $group_color );
					$temp_status = 0;
					$temp_status_icon = "";
					$color_status = "";
					$unraid_array_icon = "";
					$physical_traynumber = null;
					
					if(!$unraid_array[$devicenode]["temp"] || !is_numeric($unraid_array[$devicenode]["temp"])) { // && (!$unraid_array[$devicenode]["temp"] && $unraid_array[$devicenode]["hotTemp"] == 0 && $unraid_array[$devicenode]["maxTemp"] == 0)) {
						$unraid_array[$devicenode]["temp"] = 0;
						
						$temp_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation grey-orb-disklocation'></i><span>Temperature unavailable</span></a>";
						$temp_status_info = array('orb' => 'fa fa-circle orb-disklocation grey-orb-disklocation', 'color' => 'grey', 'text' => 'N/A');
						$temp_status = 0;
					}
					else {
						if($unraid_array[$devicenode]["temp"] < $unraid_array[$devicenode]["hotTemp"]) {
							$temp_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation green-orb-disklocation'></i><span>" . $devices[$hash]["formatted"]["temp"] . "</span></a>";
							$temp_status_info = array('orb' => 'fa fa-circle orb-disklocation green-orb-disklocation', 'color' => 'green', 'text' => $devices[$hash]["formatted"]["temp"]);
							$temp_status = 1;
						}
						if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["hotTemp"]) {
							$temp_status_icon = "<a class='info' style=\"margin: 0; text-align:left;\"><i class='fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation yellow-orb-disklocation yellow-blink-disklocation'></i><span>" . $smart_temperature . " (Warning: &gt;" . $devices[$hash]["formatted"]["hotTemp"] . ")</span></a>";
							$temp_status_info = array('orb' => "fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation yellow-orb-disklocation yellow-blink-disklocation", 'color' => 'yellow', 'text' => $devices[$hash]["formatted"]["temp"]);
							$temp_status = 2;
						}
						if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["maxTemp"]) {
							$temp_status_icon = "<a class='info'><i class='fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation red-blink-disklocation'></i><span>" . $devices[$hash]["formatted"]["temp"] . " (Critical: &gt;" . $devices[$hash]["formatted"]["maxTemp"] . ")</span></a>";
							$temp_status_info = array('orb' => "fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation red-blink-disklocation", 'color' => 'red', 'text' => $devices[$hash]["formatted"]["temp"]);
							$temp_status = 3;
						}
					}
					if(!isset($displayinfo["ledtemp"])) {
						$temp_status_icon = "";
					}
					
					// Set $smart_status = 2 if $smart_errors was found AND $smart_status has NOT failed AND disk has NOT been acknowledged, else set initial value:
					$smart_status = ((!empty($smart_errors) && !empty($smart_status) && !get_disk_ack($unraid_array[$data["devicenode"]]["name"])) ? 2 : $smart_status);
					switch($smart_status) {
						case 0:
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation red-orb-disklocation red-blink-disklocation'></i><span>S.M.A.R.T: Failed! " . $devices[$hash]["formatted"]["smart_errors"] . "</span></a>";
							$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation red-orb-disklocation red-blink-disklocation', 'color' => 'red', 'text' => 'Failed');
							break;
						case 1:
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation green-orb-disklocation'></i><span>S.M.A.R.T: Passed</span></a>";
							$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation green-orb-disklocation', 'color' => 'green', 'text' => 'Passed');
							break;
						case 2:
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation'></i><span>S.M.A.R.T: Warning! " . $devices[$hash]["formatted"]["smart_errors"] . "</span></a>";
							$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation', 'color' => 'yellow', 'text' => 'Warning');
							break;
						default:
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation grey-orb-disklocation'></i><span>S.M.A.R.T: N/A</span></a>";
							$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation grey-orb-disklocation', 'color' => 'grey', 'text' => 'N/A');
					}
					
					if(isset($displayinfo["leddiskop"])) {
						$zfs_disk_status = "";
						if($zfs_check) {
							$zfs_disk_status = zfs_disk("" . $data["smart_serialnumber"] . "", $zfs_parser, $lsblk_array);
						}
						
						$unraid_disk_status_color = get_powermode($device, $get_powermode);
						
						if(!empty($unraid_array[$devicenode]["color"]) && !empty($unraid_array[$devicenode]["status"])) {
							$unraid_array_icon = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["type"], '', $force_orb_led);
							$unraid_array_info = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["type"],'array');
							$color_status = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["type"],'color');
						}
						if(!empty($zfs_disk_status)) {
							$unraid_array_icon = get_unraid_disk_status($zfs_disk_status[1], '', '', $force_orb_led);
							$unraid_array_info = get_unraid_disk_status($zfs_disk_status[1],'','array');
							$color_status = get_unraid_disk_status($zfs_disk_status[1],'','color');
							if($color_status == "green" && $unraid_disk_status_color == "green-blink") {
								$unraid_array_icon = get_unraid_disk_status('STANDBY', '', '', $force_orb_led);
								$unraid_array_info = get_unraid_disk_status('STANDBY','','array');
								$color_status = get_unraid_disk_status('STANDBY','','color');
							}
						}
						else {
							$unraid_array_icon = get_unraid_disk_status($unraid_disk_status_color, '', '', $force_orb_led);
							$unraid_array_info = get_unraid_disk_status($unraid_disk_status_color,'','array');
							$color_status = get_unraid_disk_status($unraid_disk_status_color,'','color');
						}
					}
					
					$drive_tray_order[$hash] = get_tray_location($get_locations, $hash, $gid);
					$drive_tray_order[$hash] = ( !isset($drive_tray_order[$hash]) ? $tray_assign : $drive_tray_order[$hash] );
					if(isset($displayinfo["tray"])) {
						if($tray_number_override[$drive_tray_order[$hash]]) {
							$drive_tray_order_assign = $tray_number_override[$drive_tray_order[$hash]];
							$physical_traynumber = ( !isset($tray_number_override_start) ? --$tray_number_override[$drive_tray_order[$hash]] : ($tray_number_override_start + $tray_number_override[$drive_tray_order[$hash]] - 1));
						}
						else {
							$drive_tray_order_assign = $drive_tray_order[$hash];
							$physical_traynumber = ( !isset($tray_number_override_start) ? --$drive_tray_order[$hash] : $drive_tray_order[$hash]);
						}
					}
					
					$color_array[$hash] = "";
					
					if(!$device_bg_color) { // Disk Type / Heatmap setting.
						switch(strtolower($unraid_array[$devicenode]["type"] ?? '')) {
							case "parity":
								$color_array[$hash] = $bgcolor_parity;
								break;
							case "data":
								$color_array[$hash] = $bgcolor_unraid;
								break;
							case "cache":
								$color_array[$hash] = $bgcolor_cache;
								break;	
							default:
								$color_array[$hash] = $bgcolor_others;
						}
						if($color_override) {
							$color_array[$hash] = $color_override;
						}
					}
					else {
						if($unraid_array[$devicenode]["temp"] < $unraid_array[$devicenode]["hotTemp"]) {
							$color_array[$hash] = $bgcolor_cache;
						}
						if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["hotTemp"]) {
							$color_array[$hash] = $bgcolor_unraid;
						}
						if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["maxTemp"]) {
							$color_array[$hash] = $bgcolor_parity;
						}
						if(!$unraid_array[$devicenode]["temp"] || (!$unraid_array[$devicenode]["temp"] && $unraid_array[$devicenode]["hotTemp"] == 0 && $unraid_array[$devicenode]["maxTemp"] == 0)) {
							$color_array[$hash] = $bgcolor_others;
						}
					}
					
					$add_anim_bg_class = "";
					$color_array_blinker = "";
					if(isset($displayinfo["flashwarning"]) && ($temp_status == 2 || $smart_status == 2 || $color_status == "yellow")) { // warning
						$color_array_blinker = "blinker-disklocation-yellow-bg";
						$add_anim_bg_class = "class=\"yellow-blink-disklocation-bg\"";
					}
					if(isset($displayinfo["flashcritical"]) && ($temp_status == 3 || !$smart_status || $color_status == "red")) { // critical
						$color_array_blinker = "blinker-disklocation-red-bg";
						$add_anim_bg_class = "class=\"red-blink-disklocation-bg\"";
					}
					
					$disklocation_page[$gid] .= "
						<div style=\"order: " . $drive_tray_order[$hash] . "\">
							<div class=\"flex-container_" . $disk_tray_direction . "\">
								<div id=\"bg1-" . $device . "\" $add_anim_bg_class style=\"background-color: #" . ( !empty($add_anim_bg_class) ? $color_array_blinker : $color_array[$hash] ) . "; width: " . $tray_width . "px; height: " . $tray_height . "px;\">
									<div class=\"flex-container-start\" style=\"white-space: nowrap;\">
										<b>$physical_traynumber</b>$insert_break
										$unraid_array_icon $insert_break
										$smart_status_icon $insert_break
										$temp_status_icon
									</div>
									<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
										" . bscode2html(nl2br(stripslashes(htmlspecialchars(keys_to_content($select_db_devices_str, $devices[$hash]["formatted"]))))) . "
									</div>
								</div>
							</div>
						</div>
					";
					
					$add_physical_tray_order = "";
					if($drive_tray_order[$hash] != $physical_traynumber) {
						$add_physical_tray_order = $drive_tray_order[$hash];
					}
					
					$disklocation_layout[$gid] .= "
						<div style=\"order: " . $drive_tray_order[$hash] . "\">
							<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
								<div id=\"bg2-" . $device . "\" style=\"background-color: #" . $color_array[$hash] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
									<div class=\"flex-container-start\">
										<b>$physical_traynumber</b>
									</div>
									<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
									</div>
									<div class=\"flex-container-end\">
										<!--<input type=\"checkbox\" name=\"hide_tray[$drive_tray_order[$hash]]\" value=\"1\" style=\"background: transparent; margin: 0; padding: 0;\" />-->
										<!--" . $add_physical_tray_order . "-->
									</div>
								</div>
							</div>
						</div>
					";
					
					$add_physical_tray_order = "";
					if($drive_tray_order[$hash] != $physical_traynumber) {
						$add_physical_tray_order = $physical_traynumber;
					}
					
					$disklocation_alloc[$gid] .= "
						<div style=\"order: " . $drive_tray_order[$hash] . "\">
							<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
								<div id=\"bg3-" . $device . "\" class=\"\" style=\"background-color: #" . $color_array[$hash] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
									<div class=\"flex-container-start\" style=\"/*min-height: 15px;*/\">
										<b>" . $drive_tray_order[$hash] . "</b>
									</div>
									<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
									</div>
									<div class=\"flex-container-end\" style=\"font-size: xx-small;\">
										" . $add_physical_tray_order . "
									</div>
								</div>
							</div>
						</div>
					";
					
					// SMART=PASS: show array LED
					if($smart_status == 1) {
						$dashboard_orb = $unraid_array_info["orb"];
						
					}
					// TEMP STATUS=warning|critical: show temp warning
					if(isset($temp_status) && $temp_status > 1) { 
						$dashboard_orb = $temp_status_info["orb"];
						
					}
					// SMART=FAIL/WARN: show SMART LED
					if(isset($smart_status) && ($smart_status == 0 || $smart_status == 2)) {
						$dashboard_orb = $smart_status_info["orb"];
						
					}
					$dashboard_text = "" . $temp_status_info["text"] . " | SMART: " . $smart_status_info["text"] . " | " . $unraid_array_info["text"] . "";
					$dashboard_text .= "<br />" . bscode2html(nl2br(stripslashes(htmlspecialchars(keys_to_content($select_db_devices_str, $devices[$hash]["formatted"])))), true) . "";
					
					$disklocation_dash[$gid] .= "
						<div style=\"order: " . $drive_tray_order[$hash] . "\">
							<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
								<div id=\"bg4-" . $device . "\" $add_anim_bg_class style=\"background-color: #" . ( !empty($add_anim_bg_class) ? $color_array_blinker : $color_array[$hash] ) . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
									<div class=\"flex-container-start\" style=\"text-align: center;/*min-height: 15px;*/\">
										<a href=\"/Main/Device?name=" . $devices[$hash]["raw"]["name"] . "\" class='info'><i class='" . $dashboard_orb . "'></i><span style=\"text-align: left;\">" . $dashboard_text . "</span></a>
									</div>
									<div class=\"flex-container-middle_" . $disk_tray_direction . "\" style=\"padding: 0 0 10px 0;\">
									</div>
									<div class=\"flex-container-end\">
										<b>$physical_traynumber</b>
									</div>
								</div>
							</div>
						</div>
					";
					
					$tray_number = $drive_tray_order[$hash];
					$installed_drives[$gid] = $i_drive;
					$i_drive++;
				}
				
				if($total_main_trays == $i) {
					$disklocation_page[$gid] .= "</div><div class=\"grid-container\" style=\"grid-template-rows: " . $grid_columns_override_styles . "; margin: " . $tray_height / 2 . "px;\">";
					$disklocation_layout[$gid] .= "</div><div class=\"grid-container\" style=\"grid-template-rows: " . $grid_columns_override_styles . "; margin: " . $tray_height / 20 . "px;\">";
					$disklocation_alloc[$gid] .= "</div><div class=\"grid-container\" style=\"grid-template-rows: " . $grid_columns_override_styles . "; margin: " . $tray_height / 20 . "px;\">";
					$disklocation_dash[$gid] .= "</div><div class=\"grid-container\" style=\"grid-template-rows: " . $grid_columns_override_styles . "; margin: " . $tray_height / 20 . "px;\">";
				}
				
				$i++;
			}
			$grid_columns_styles[$gid] = str_repeat(" auto", $grid_columns);
			$grid_rows_styles[$gid] = str_repeat(" auto", $grid_rows);
			
			unset($datajson); // delete array
		}
	}
	
	$disklocation_page_out = "";
	
	$array_groups = $get_groups;
	( is_array($array_groups) ?? ksort($array_groups, SORT_NUMERIC) );
	
	foreach($array_groups as $gid => $value) {
		$gid_name = ( empty($array_groups[$gid]["group_name"]) ? $gid : $array_groups[$gid]["group_name"] );
		
		$css_grid_group = "
			grid-template-columns: " . $grid_columns_styles[$gid] . ";
			grid-template-rows: " . $grid_rows_styles[$gid] . ";
			grid-auto-flow: " . $array_groups[$gid]["grid_count"] . ";
		";
		
		$disklocation_page_out .= "
			<div style=\"float: left; top; padding: 0 10px 10px 10px;\">
				<h2 style=\"text-align: center;\">" . stripslashes(htmlspecialchars($gid_name)) . "</h2>
				<div class=\"grid-container\" style=\"$css_grid_group\">
					$disklocation_page[$gid]
				</div>
			</div>
		";
	}
	
	if($db_update == 2) {
		print("<!--");
	}
?>
