<?php
	/*
	 *  Copyright 2019-2020, Ole-Henrik Jakobsen
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
	unset($disklocation_page); unset($disklocation_layout);
	$biggest_tray_group = 0;
	
	$sql = "SELECT * FROM settings_group ORDER BY id ASC";
	$results = $db->query($sql);
	
	while($data = $results->fetchArray(1)) {
		extract($data);
		
		$gid = $id;
		$groupid = $gid;
		
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
		while($i <= $total_trays) {
			$data = $datasql[$i_drive-1];
			
			$tray_assign = $i;
			
			if($data["tray"] != $i) {
				debug_print($debugging_active, __LINE__, "loop", "Empty tray: " . $tray_assign . "");
				
				if($displayinfo["tray"] && !$displayinfo["hideemptycontents"]) {
					if($tray_number_override[$tray_assign]) {
						//$empty_tray = "<b>". $tray_number_override[$tray_assign] . "</b>" . $insert_break . "";
						$empty_tray = ( empty($tray_number_override_start) ? "<b>" . --$tray_number_override[$tray_assign] . "</b>" . $insert_break . "</b>" : "<b>" . $tray_number_override[$tray_assign] . "</b>" . $insert_break . "</b>");
						$empty_tray_assign = $tray_number_override[$tray_assign];
					}
					else {
						$empty_tray = "<b>" . $tray_assign . "</b>" . $insert_break . "";
						$empty_tray = ( empty($tray_number_override_start) ? "<b>" . --$tray_assign . "</b>" . $insert_break . "</b>" : "<b>" . $tray_assign . "</b>" . $insert_break . "</b>");
						$empty_tray_assign = $tray_assign;
					}
				}
				
				if($displayinfo["leddiskop"] && !$displayinfo["hideemptycontents"]) {
					$empty_leddiskop = get_unraid_disk_status("grey-off");
					//$empty_leddiskop = "<span class=\"grey-off\" alt=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" title=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" />&#11044;</span>" . $insert_break . "";
				}
				if($displayinfo["ledsmart"] && !$displayinfo["hideemptycontents"]) {
					$empty_ledsmart = get_unraid_disk_status("grey-off");
					//$empty_ledsmart = "<span class=\"grey-off\" alt=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" title=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" />&#11044;</span>";
				}
				if(!$displayinfo["hideemptycontents"]) {
					$empty_traytext = "<b>Available disk slot</b>";
				}
				$disklocation_page[$gid] .= "
					<div style=\"order: " . $tray_assign . "\">
						<div class=\"flex-container_" . $disk_tray_direction . "\">
							<div style=\"background-color: #" . $color_array["empty"] . "; width: " . $tray_width . "px; height: " . $tray_height . "px;\">
								<div class=\"flex-container-start\">
									$empty_tray
									$empty_leddiskop
									$empty_ledsmart
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
				
				$disklocation_layout[$gid] .= "
					<div style=\"order: " . $tray_assign . "\">
						<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
							<div style=\"background-color: #" . $color_array["empty"] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
								<b>" . $empty_tray_assign . "</b>
							</div>
						</div>
					</div>
				";
				
				$add_empty_physical_tray_order = "";
				if($tray_assign != $empty_tray_assign) {
					$add_empty_physical_tray_order = $empty_tray_assign;
				}
				$disklocation_alloc[$gid] .= "
					<div style=\"order: " . $tray_assign . "\">
						<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
							<div style=\"background-color: #" . $color_array["empty"] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
								<div class=\"flex-container-start\" style=\"/*min-height: 15px;*/\">
									<b>" . $tray_assign . "</b>
								</div>
								<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
								</div>
								<div class=\"flex-container-end\">
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
									<b>" . $empty_tray_assign . "</b>
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
				$hash = $data["hash"];
				$color_override = $data["color"];
				$warranty_page = "";
				
				if($displayinfo["path"]) {
					$device_page = $device;
				}
				if($displayinfo["devicenode"]) {
					$devicenode_page = $devicenode;
				}
				if($displayinfo["luname"]) {
					$luname_page = "(" . $luname . ")";
				}
				if($displayinfo["manufacturer"]) {
					$smart_modelfamily = $data["model_family"];
				}
				if($displayinfo["devicemodel"]) {
					$smart_modelname = $data["model_name"];
				}
				if($displayinfo["serialnumber"]) {
					$smart_serialnumber = ( isset($data["smart_serialnumber"]) ? "<span style=\"white-space: nowrap;\">(" . $data["smart_serialnumber"] . ")</span>" : null );
				}
				if($displayinfo["powerontime"]) {
					$smart_powerontime = ( empty($data["smart_powerontime"]) ? null : "<span style=\"cursor: help;\" title=\"" . seconds_to_time($data["smart_powerontime"] * 60 * 60) . "\">" . $data["smart_powerontime"] . "h</span>" );
				}
				if($displayinfo["loadcyclecount"]) {
					$smart_loadcycle = ( empty($data["smart_loadcycle"]) ? null : $data["smart_loadcycle"] . "c" );
				}
				if($displayinfo["capacity"]) {
					$smart_capacity = ( empty($data["smart_capacity"]) ? null : human_filesize($data["smart_capacity"], 1, true) );
				}
				if($displayinfo["warranty"] && ($data["purchased"] && ($data["warranty"] || $data["warranty_date"]))) {
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
				if($displayinfo["comment"]) {
					$device_comment = ( empty($data["comment"]) ? null : stripslashes(htmlspecialchars($data["comment"])) );
				}
				if($displayinfo["temperature"]) {
					if($data["smart_temperature"]) {
						switch($display["unit"]) {
							case 'F':
								$smart_temperature = round(temperature_conv($data["smart_temperature"], 'C', 'F')) . "°F";
								break;
							case 'K':
								$smart_temperature = round(temperature_conv($data["smart_temperature"], 'C', 'K')) . "K";
								break;
							default:
								$smart_temperature = $data["smart_temperature"] . "°C";
						}
					}
					else {
						$smart_temperature = '';
					}
				}
				if($displayinfo["rotation"]) {
					$smart_rotation = get_smart_rotation($data["smart_rotation"]);
				}
				if($displayinfo["formfactor"]) {
					$smart_formfactor = str_replace(" inches", "&quot;", $data["smart_formfactor"]);
				}
				
				if($displayinfo["leddiskop"]) {
					if($unraid_array[$devicenode]["color"] && $unraid_array[$devicenode]["status"]) {
						/*
						if($unraid_array[$devicenode]["type"] == "Cache") {
							$disk_status_type = "cache";
						}
						else {
							$disk_status_type = "";
						}
						
						$unraid_disk_status_message = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["status"], $disk_status_type);
						
						if($unraid_array[$devicenode]["color"] == "green-blink") { $unraid_add_greenblinkid = " class=\"greenblink\" id=\"greenblink\""; } else { $unraid_add_greenblinkid = ""; }
						$unraid_array_icon = "<span class=\"" . $unraid_array[$devicenode]["color"] . "\" alt=\"" . $unraid_disk_status_message . "\" title=\"" . $unraid_disk_status_message . "\" />&#11044;</span>" . $insert_break . "";
						*/
						$unraid_array_icon = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["type"]);
					}
					else {
						$device_lsscsi = lsscsi_parser(shell_exec("lsscsi -b -g " . $device . ""));
						usleep($smart_exec_delay . 000); // delay script to get the output of the next shell_exec()
						$smart_powermode = trim(shell_exec("smartctl -n standby " . $device_lsscsi["sgnode"] . " | grep Device"));
						
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
						/*
						if(strstr($smart_powermode, "ACTIVE")) {
							$unraid_disk_status_color = "green-on";
							$unraid_disk_status_message = get_unraid_disk_status($unraid_disk_status_color, "DISK_OK");
							$unraid_add_greenblinkid = "";
						}
						else if(strstr($smart_powermode, "IDLE")) {
							$unraid_disk_status_color = "green-on";
							$unraid_disk_status_message = get_unraid_disk_status($unraid_disk_status_color, "DISK_OK");
							$unraid_add_greenblinkid = "";
						}
						else if(strstr($smart_powermode, "STANDBY")) {
							$unraid_disk_status_color = "green-blink";
							$unraid_disk_status_message = get_unraid_disk_status($unraid_disk_status_color, "DISK_OK");
							$unraid_add_greenblinkid = " class=\"greenblink\" id=\"greenblink\"";
						}
						else {
							$unraid_disk_status_color = "grey-off";
							$unraid_disk_status_message = get_unraid_disk_status($unraid_disk_status_color, "DISK_NP");
							$unraid_add_greenblinkid = "";
						}
						
						$unraid_array_icon = "<span class=\"" . $unraid_disk_status_color . "\" alt=\"" . $unraid_disk_status_message . "\" title=\"" . $unraid_disk_status_message . "\" />&#11044;</span>" . $insert_break . "";
						*/
					}
				}
				
				if($displayinfo["ledsmart"]) {
					$smart_status = $data["smart_status"];
					switch($smart_status) {
						case 1:
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb green-orb'></i><span>S.M.A.R.T: Passed</span></a>";
							//$smart_status_icon = "<span class=\"green-on\" alt=\"S.M.A.R.T: Passed\" title=\"S.M.A.R.T: Passed\" />&#11044;</span>";
							break;
						case 0:
							$smart_status_icon = "<a class='info'><i class='fa fa-times orb red-orb'></i><span>S.M.A.R.T: Failed!</span></a>";
							//$smart_status_icon = "<span class=\"red-on\" alt=\"S.M.A.R.T: Failed!\" title=\"S.M.A.R.T: Failed!\" />&#11044;</span>";
							break;
						default:
							//$smart_status_icon = "<span class=\"grey-off\" alt=\"S.M.A.R.T: Off/None\" title=\"S.M.A.R.T: Off/None\" />&#11044;</span>";
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb grey-orb'></i><span>S.M.A.R.T: Off/None</span></a>";
					}
				}
				
				if($displayinfo["unraidinfo"]) {
					$unraid_dev = ( isset($unraid_array[$devicenode]["type"]) ? "<b>" . $unraid_array[$devicenode]["type"] . "</b>: " . $unraid_array[$devicenode]["name"] : "<b>Unassigned:</b>" );
				}
				
				$drive_tray_order[$hash] = get_tray_location($db, $hash, $gid);
				$drive_tray_order[$hash] = ( empty($drive_tray_order[$hash]) ? $tray_assign : $drive_tray_order[$hash] );
				if($displayinfo["tray"]) {
					if($tray_number_override[$drive_tray_order[$hash]]) {
						//$add_traynumber = "<b>" . $tray_number_override[$drive_tray_order[$hash]] . "</b>" . $insert_break . "";
						$add_traynumber = ( empty($tray_number_override_start) ? "<b>" . --$tray_number_override[$drive_tray_order[$hash]] . "</b>" . $insert_break . "</b>" : "<b>" . $tray_number_override[$drive_tray_order[$hash]] . "</b>" . $insert_break . "</b>");
						$drive_tray_order_assign = $tray_number_override[$drive_tray_order[$hash]];
					}
					else {
						//$add_traynumber = "<b>" . $drive_tray_order[$hash] . "</b>" . $insert_break . "";
						$add_traynumber = ( empty($tray_number_override_start) ? "<b>" . --$drive_tray_order[$hash] . "</b>" . $insert_break . "</b>" : "<b>" . $drive_tray_order[$hash] . "</b>" . $insert_break . "</b>");
						$drive_tray_order_assign = $drive_tray_order[$hash];
					}
				}
				
				if($displayinfo["devicenode"]) {
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
				
				switch(strtolower($unraid_array[$devicenode]["type"])) {
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
				
				$disklocation_page[$gid] .= "
					<div style=\"order: " . $drive_tray_order[$hash] . "\">
						<div class=\"flex-container_" . $disk_tray_direction . "\">
							<div style=\"background-color: #" . $color_array[$hash] . "; width: " . $tray_width . "px; height: " . $tray_height . "px;\">
								<div class=\"flex-container-start\">
									$add_traynumber
									$unraid_array_icon
									$smart_status_icon
									
								</div>
								<div class=\"flex-container-middle_" . $disk_tray_direction . "\">
									$unraid_dev $device_page $devicenode_page $luname_page $add_break_1
									$smart_modelfamily $smart_modelname $smart_serialnumber $add_break_2
									$smart_temperature $smart_powerontime $smart_loadcycle $smart_capacity $smart_rotation $smart_formfactor $warranty_page $add_break_3
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
				
				$disklocation_layout[$gid] .= "
					<div style=\"order: " . $drive_tray_order[$hash] . "\">
						<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
							<div style=\"background-color: #" . $color_array[$hash] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
								<b>" . $drive_tray_order_assign . "</b>
							</div>
						</div>
					</div>
				";
				
				$add_physical_tray_order = "";
				if($drive_tray_order[$hash] != $drive_tray_order_assign) {
					$add_physical_tray_order = $drive_tray_order_assign;
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
								<div class=\"flex-container-end\">
									" . $add_physical_tray_order . "
								</div>
							</div>
						</div>
					</div>
				";
				
				if($smart_status == 1) {
					$dashboard_led = $unraid_array_icon;
					//$dashboard_info = "<span class=\"green\"><b>Disks OK!</b></span>";
				}
				else if(isset($smart_status)) {
					$dashboard_led = $smart_status_icon;
					//$dashboard_info = "<span class=\"red\"><b>S.M.A.R.T Failed!</b></span>";
				}
				
				$disklocation_dash[$gid] .= "
					<div style=\"order: " . $drive_tray_order[$hash] . "\">
						<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
							<div style=\"background-color: #" . $color_array[$hash] . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
								<div class=\"flex-container-start\" style=\"/*min-height: 15px;*/\">
									$dashboard_led
								</div>
								<div class=\"flex-container-middle_" . $disk_tray_direction . "\" style=\"padding: 0 0 10px 0;\">
								</div>
								<div class=\"flex-container-end\">
									<b>$drive_tray_order_assign</b>
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
