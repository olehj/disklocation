<?php
	/*
	 *  Copyright 2019-2023, Ole-Henrik Jakobsen
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
	
	$sql = "SELECT * FROM settings_group ORDER BY id ASC";
	$results = $db->query($sql);
	
	$total_trays_group = 0;
	
	while($data = $results->fetchArray(1)) {
		extract($data);
		
		$gid = $id;
		$groupid = $gid;

		$disklocation_page[$gid] = "";
		$disklocation_layout[$gid] = "";
		$disklocation_alloc[$gid] = "";
		$disklocation_dash[$gid] = "";
		
		if(!$total_groups) {
			$sql = "SELECT * FROM disks WHERE status IS NULL;";
		}
		else {
			$sql = "SELECT * FROM disks JOIN location ON disks.hash=location.hash WHERE status IS NULL AND groupid = '" . $gid . "' ORDER BY groupid,tray ASC;";
		}
		
		$results_disks = $db->query($sql);
		
		$datasql = array();
		while($res = $results_disks->fetchArray(1)) {
			array_push($datasql, $res);
		}
		
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
		
		debug_print($debugging_active, __LINE__, "var", "Total trays: " . $total_trays . "");
		
		$i_empty=1;
		$i_drive=1;
		$i=1;
		$empty_tray = 0;
		
		while($i <= $total_trays) {
			$data = isset($datasql[$i_drive-1]) ? $datasql[$i_drive-1] : 0;
			$tray_assign = $i;
			$empty_leddiskop = "";
			$empty_ledsmart = "";
			$empty_ledtemp = "";
			$empty_tray_assign = "";
			$empty_traytext = "";
			
			if(( isset($data["tray"]) ? $data["tray"] : 0 ) != $i) {
				debug_print($debugging_active, __LINE__, "loop", "Empty tray: " . $tray_assign . "");
				
				if($displayinfo["tray"] && empty($displayinfo["hideemptycontents"])) {
					if($tray_number_override[$tray_assign]) {
						$empty_tray = ( !isset($tray_number_override_start) ? --$tray_number_override[$tray_assign] : ($tray_number_override_start + $tray_number_override[$tray_assign] - 1));
						$empty_tray_assign = $tray_number_override[$tray_assign];
					}
					
					else {
						$empty_tray = ( !isset($tray_number_override_start) ? --$tray_assign : $tray_number_override_start + $tray_assign -1);
						$empty_tray_assign = $tray_assign;
					}
				}
				
				if(isset($displayinfo["leddiskop"]) && $displayinfo["leddiskop"] == 1 && empty($displayinfo["hideemptycontents"])) {
					$empty_leddiskop = get_unraid_disk_status("grey-off");
				}
				if(isset($displayinfo["ledsmart"]) && $displayinfo["ledsmart"] == 1 && empty($displayinfo["hideemptycontents"])) {
					$empty_ledsmart = get_unraid_disk_status("grey-off");
				}
				if(isset($displayinfo["ledtemp"]) && $displayinfo["ledtemp"] == 1 && empty($displayinfo["hideemptycontents"])) {
					$empty_ledtemp = get_unraid_disk_status("grey-off");
				}
				if(empty($displayinfo["hideemptycontents"])) {
					$empty_traytext = "<b>Available disk slot</b>";
				}
				$disklocation_page[$gid] .= "
					<div style=\"order: " . $tray_assign . "\">
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
								<div class=\"flex-container-end\">
									&nbsp;
								</div>
							</div>
						</div>
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
									<b>$empty_tray</b>
								</div>
								<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
								</div>
								<div class=\"flex-container-end\">
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
						<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
							<div style=\"background-color: #" . $color_array["empty"] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
								<div class=\"flex-container-start\" style=\"/*min-height: 15px;*/\">
									<b>$tray_assign</b>
								</div>
								<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
								</div>
								<div class=\"flex-container-end\" style=\"font-size: xx-small;\">
									" . $add_empty_physical_tray_order . "
								</div>
							</div>
						</div>
					</div>
				";
				
				$disklocation_dash[$gid] .= "
					<div style=\"order: " . $tray_assign . "\">
						<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
							<div style=\"background-color: #" . $color_array["empty"] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
								<div class=\"flex-container-start\" style=\"/*min-height: 15px;*/\">
									" . get_unraid_disk_status("grey-off") . "
								</div>
								<div class=\"flex-container-middle_" . $disk_tray_direction . "\" style=\"padding: 0 0 10px 0;\">
								</div>
								<div class=\"flex-container-end\">
									<b>" . $empty_tray . "</b>
								</div>
							</div>
						</div>
					</div>
				";
				
				$i_empty++;
			}
			else {
				debug_print($debugging_active, __LINE__, "loop", "Populated tray: " . $tray_assign . "");
				
				$device = $data["device"];
				$devicenode = $data["devicenode"];
				$luname = $data["luname"];
				$luname_page = "";
				$hash = $data["hash"];
				$color_override = $data["color"];
				$warranty_page = "";
				$smart_status = 0;
				$smart_status_icon = "";
				$smart_modelfamily = "";
				$smart_modelname = "";
				$smart_serialnumber = "";
				$smart_powerontime = "";
				$smart_loadcycle = "";
				$smart_capacity = "";
				$device_comment = "";
				$smart_rotation = "";
				$smart_formfactor = "";
				$smart_temperature = 0;
				$smart_temperature_text = "";
				$temp_status = 0;
				$temp_status_icon = "";
				$color_status = "";
				$dashboard_led = "";
				$unraid_array_icon = "";
				$physical_traynumber = null;
				$unraid_dev = "";
				$device_page = "";
				$devicenode_page = "";
				$luname_page = "";
				
				if(isset($displayinfo["path"])) {
					$device_page = $device;
				}
				if(isset($displayinfo["devicenode"])) {
					$devicenode_page = $devicenode;
				}
				if(isset($displayinfo["luname"])) {
					$luname_page = "(" . $luname . ")";
				}
				if(isset($displayinfo["manufacturer"])) {
					$smart_modelfamily = $data["model_family"];
				}
				if(isset($displayinfo["devicemodel"])) {
					$smart_modelname = $data["model_name"];
				}
				if(isset($displayinfo["serialnumber"])) {
					$smart_serialnumber = ( isset($data["smart_serialnumber"]) ? "<span style=\"white-space: nowrap;\">(" . $data["smart_serialnumber"] . ")</span>" : null );
				}
				if(isset($displayinfo["powerontime"])) {
					$smart_powerontime = ( !is_numeric($data["smart_powerontime"]) ? null : "<span style=\"cursor: help;\" title=\"" . seconds_to_time($data["smart_powerontime"] * 60 * 60) . "\">" . $data["smart_powerontime"] . "h</span>" );
				}
				if(isset($displayinfo["loadcyclecount"])) {
					$smart_loadcycle = ( !is_numeric($data["smart_loadcycle"]) ? null : $data["smart_loadcycle"] . "c" );
				}
				if(isset($displayinfo["capacity"])) {
					$smart_capacity = ( !is_numeric($data["smart_capacity"]) ? null : human_filesize($data["smart_capacity"], 1, true) );
				}
				if(isset($displayinfo["warranty"]) && ($data["purchased"] && ($data["warranty"] || $data["warranty_date"]))) {
					$warranty_start = strtotime($data["purchased"]);
					$warranty_end = "";
					
					if($warranty_field == "u") {
						$warranty_end = strtotime("" . $data["purchased"] . " + " . $data["warranty"] . " month");
					}
					else {
						$warranty_end = strtotime($data["warranty_date"]);
					}
					$warranty_left = "";
					$warranty_expire_left = $warranty_end-date("U");
					if($warranty_expire_left > 0) {
						$warranty_left = seconds_to_time($warranty_expire_left);
						$warranty_left_days = floor($warranty_expire_left / 60 / 60 / 24);
						$warranty_page = "<span style=\"cursor: help;\" title=\"Warranty left: " . $warranty_left . "\">WTY:" . $warranty_left_days . "d</span>";
					}
					else {
						$warranty_page = "<span style=\"cursor: help;\" title=\"Warranty has expired\">WTY:expired</span>";
					}
				}
				if(isset($displayinfo["comment"])) {
					$device_comment = ( !isset($data["comment"]) ? null : bscode2html(stripslashes(htmlspecialchars($data["comment"]))) );
				}
				if(isset($displayinfo["temperature"]) || isset($displayinfo["ledtemp"])) {
					if($unraid_array[$data["devicenode"]]["temp"] && $unraid_array[$devicenode]["temp"]) {
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
					else {
						$smart_temperature = '';
						$smart_temperature_warning = '';
						$smart_temperature_critical = '';
					}
				}
				if(isset($displayinfo["temperature"])) {
					$smart_temperature_text = $smart_temperature;
				}
				if(isset($displayinfo["rotation"])) {
					$smart_rotation = get_smart_rotation($data["smart_rotation"]);
				}
				if(isset($displayinfo["formfactor"])) {
					$smart_formfactor = str_replace(" inches", "&quot;", $data["smart_formfactor"]);
				}
				
				if(isset($displayinfo["leddiskop"])) {
					if(isset($unraid_array[$devicenode]["color"]) && isset($unraid_array[$devicenode]["status"])) {
						$unraid_array_icon = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["type"]);
						$color_status = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["type"],'color');
					}
					else {
						$device_lsscsi = lsscsi_parser(shell_exec("lsscsi -b -g " . $device . ""));
						usleep($smart_exec_delay . 000); // delay script to get the output of the next shell_exec()
						
						$smart_powermode_shell = shell_exec("smartctl -n standby " . $device_lsscsi["sgnode"] . " | grep Device");
						$smart_powermode = (isset($smart_powermode_shell) ? trim($smart_powermode_shell) : '');
						
						if(strstr($smart_powermode, "ACTIVE")) {
							$unraid_disk_status_color = "green-on";
						}
						else if(strstr($smart_powermode, "IDLE")) {
							$unraid_disk_status_color = "green-on";
						}
						else if(strstr($smart_powermode, "STANDBY")) {
							$unraid_disk_status_color = "green-blink";
						}
						else {
							$unraid_disk_status_color = "grey-off";
						}
						
						$unraid_array_icon = get_unraid_disk_status($unraid_disk_status_color);
						$color_status = get_unraid_disk_status($unraid_disk_status_color,'','color');
						
						if(zfs_check()) {
							$zfs_disk_status = zfs_disk("" . $data["smart_serialnumber"] . "");
							if($zfs_disk_status) {
								$unraid_array_icon = get_unraid_disk_status($zfs_disk_status[1]);
								$color_status = get_unraid_disk_status($zfs_disk_status[1],'','color');
							}
						}
					}
				}
				
				$smart_status = $data["smart_status"];
				switch($smart_status) {
					case 1:
						$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation green-orb-disklocation'></i><span>S.M.A.R.T: Passed</span></a>";
						//$smart_status_icon = "<span class=\"green-on\" alt=\"S.M.A.R.T: Passed\" title=\"S.M.A.R.T: Passed\" />&#11044;</span>";
						break;
					case 0:
						$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation red-orb-disklocation'></i><span>S.M.A.R.T: Failed!</span></a>";
						//$smart_status_icon = "<span class=\"red-on\" alt=\"S.M.A.R.T: Failed!\" title=\"S.M.A.R.T: Failed!\" />&#11044;</span>";
						break;
					default:
						//$smart_status_icon = "<span class=\"grey-off\" alt=\"S.M.A.R.T: Off/None\" title=\"S.M.A.R.T: Off/None\" />&#11044;</span>";
						$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation grey-orb-disklocation'></i><span>S.M.A.R.T: Off/None</span></a>";
				}
				if(!isset($displayinfo["ledsmart"])) {
					$smart_status_icon = "";
				}
				
				if(!$unraid_array[$devicenode]["temp"] || !is_numeric($unraid_array[$devicenode]["temp"])) { // && (!$unraid_array[$devicenode]["temp"] && $unraid_array[$devicenode]["hotTemp"] == 0 && $unraid_array[$devicenode]["maxTemp"] == 0)) {
					$unraid_array[$devicenode]["temp"] = 0;
					
					$temp_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation grey-orb-disklocation'></i><span>Temperature unavailable</span></a>";
					$temp_status = 0;
				}
				else {
					if($unraid_array[$devicenode]["temp"] < $unraid_array[$devicenode]["hotTemp"]) {
						$temp_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation green-orb-disklocation'></i><span>" . $smart_temperature . "</span></a>";
						$temp_status = 1;
					}
					if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["hotTemp"]) {
						$temp_status_icon = "<a class='info' style=\"margin: 0; text-align:left;\"><i class='fa fa-fire yellow-orb-disklocation'></i><span>" . $smart_temperature . " (Warning: &gt;" . $smart_temperature_warning . ")</span></a>";
						$temp_status = 2;
					}
					if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["maxTemp"]) {
						$temp_status_icon = "<a class='info'><i class='fa fa-fire red-blink-disklocation'></i><span>" . $smart_temperature . " (Critical: &gt;" . $smart_temperature_critical . ")</span></a>";
						$temp_status = 3;
					}
				}
				if(!isset($displayinfo["ledtemp"])) {
					$temp_status_icon = "";
				}
				
				if(isset($displayinfo["unraidinfo"])) {
					$unraid_dev = ( isset($unraid_array[$devicenode]["type"]) ? "<b>" . $unraid_array[$devicenode]["type"] . "</b>: " . $unraid_array[$devicenode]["name"] : "<b>Unassigned:</b>" );
				}
				
				$drive_tray_order[$hash] = get_tray_location($db, $hash, $gid);
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
				
				if(isset($displayinfo["devicenode"])) {
					$devicenode_page = str_replace("-", "", $devicenode);
				}
				
				$add_break_1 = "";
				$add_break_2 = "";
				$add_break_3 = "";
				if($unraid_dev || $device_page || $devicenode_page || $luname_page) {
					$add_break_1 = "<br />";
				}
				if($smart_modelfamily || $smart_modelname || $smart_serialnumber) {
					$add_break_2 = "<br />";
				}
				if($smart_temperature || $smart_powerontime || $smart_loadcycle || $smart_capacity || $smart_rotation || $smart_formfactor || $warranty_page) {
					$add_break_3 = "<br />";
				}
				
				$deviceid = hash('sha256', $data["model_name"] . $data["smart_serialnumber"]);
				
				$color_array[$deviceid] = "";
				
				if(!$dashboard_widget) { // $dashboard_widget is really for Disk Type / Heatmap setting.
					switch(strtolower($unraid_array[$devicenode]["type"] ?? '')) {
						case "parity":
							$color_array[$deviceid] = $bgcolor_parity;
							break;
						case "data":
							$color_array[$deviceid] = $bgcolor_unraid;
							break;
						case "cache":
							$color_array[$deviceid] = $bgcolor_cache;
							break;	
						default:
							$color_array[$deviceid] = $bgcolor_others;
					}
					if($color_override) {
						$color_array[$deviceid] = $color_override;
					}
				}
				else {
					if($unraid_array[$devicenode]["temp"] < $unraid_array[$devicenode]["hotTemp"]) {
						$color_array[$deviceid] = $bgcolor_cache;
					}
					if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["hotTemp"]) {
						$color_array[$deviceid] = $bgcolor_unraid;
					}
					if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["maxTemp"]) {
						$color_array[$deviceid] = $bgcolor_parity;
					}
					if(!$unraid_array[$devicenode]["temp"] && (!$unraid_array[$devicenode]["temp"] && $unraid_array[$devicenode]["hotTemp"] == 0 && $unraid_array[$devicenode]["maxTemp"] == 0)) {
						$color_array[$deviceid] = $bgcolor_others;
					}
				}
				
				$add_anim_bg_class = "";
				$color_array_blinker = "";
				if(isset($displayinfo["flashwarning"]) && ($temp_status == 2 || $color_status == "yellow")) { // warning
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
							<div $add_anim_bg_class style=\"background-color: #" . ( !empty($add_anim_bg_class) ? $color_array_blinker : $color_array[$hash] ) . "; width: " . $tray_width . "px; height: " . $tray_height . "px;\">
								<div class=\"flex-container-start\" style=\"white-space: nowrap;\">
									<b>$physical_traynumber</b>$insert_break
									$unraid_array_icon $insert_break
									$smart_status_icon $insert_break
									$temp_status_icon
								</div>
								<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
									$unraid_dev $device_page $devicenode_page $luname_page $add_break_1
									$smart_modelfamily $smart_modelname $smart_serialnumber $add_break_2
									$smart_temperature_text $smart_powerontime $smart_loadcycle $smart_capacity $smart_rotation $smart_formfactor $warranty_page $add_break_3
									$device_comment
								</div>
								<!--
								<div class=\"flex-container-end\">
									<input type=\"button\" class=\"diskLocation_" . $disk_tray_direction . "\" onclick=\"locateStart()\" value=\"Locate\" id=\"" . $device . "\" name=\"" . $device . "\" />
								</div>
								-->
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
							<div style=\"background-color: #" . $color_array[$hash] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
								<div class=\"flex-container-start\">
									<b>$physical_traynumber</b>
								</div>
								<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
								</div>
								<div class=\"flex-container-end\">
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
							<div style=\"background-color: #" . $color_array[$hash] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
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
				
				if($smart_status == 1) {
					$dashboard_led = $unraid_array_icon;
				}
				else if(isset($smart_status)) {
					$dashboard_led = $smart_status_icon;
				}
				if(isset($temp_status) && $temp_status > 1) {
					$dashboard_led = $temp_status_icon;
				}
				
				$disklocation_dash[$gid] .= "
					<div style=\"order: " . $drive_tray_order[$hash] . "\">
						<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
							<div $add_anim_bg_class style=\"background-color: #" . ( !empty($add_anim_bg_class) ? $color_array_blinker : $color_array[$hash] ) . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
								<div class=\"flex-container-start\" style=\"text-align: center;/*min-height: 15px;*/\">
									$dashboard_led
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
	}
?>
