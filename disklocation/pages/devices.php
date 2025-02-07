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
	
	// Override old variables
	$reallocated_sector_w =		'1';			// SMART warnings (RAW)
	$reported_uncorr_w =		'1';			// -
	$command_timeout_w =		'0';			// '-> disabled by default as Seagate devices reports this different from other manufacturers.
	$pending_sector_w =		'1';			// -
	$offline_uncorr_w =		'1';			// -
	
	$biggest_tray_group = 0;
	
	$sql = "SELECT * FROM settings_group ORDER BY id ASC";
	$results = $db->query($sql);
	
	$total_trays_group = 0;
	
	$zfs_check = 0;
	if(zfs_check()) {
		$zfs_parser = zfs_parser();
		$lsblk_array = json_decode(shell_exec("lsblk -p -o NAME,MOUNTPOINT,SERIAL,PATH --json"), true);
		$zfs_check = 1;
	}
	
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
				else {
					$empty_tray = "";
				}
				
				if(!empty($displayinfo["leddiskop"]) && $displayinfo["leddiskop"] == 1 && empty($displayinfo["hideemptycontents"])) {
					$empty_leddiskop = get_unraid_disk_status("grey-off", '', '', $force_orb_led);
				}
				if(!empty($displayinfo["ledsmart"]) && $displayinfo["ledsmart"] == 1 && empty($displayinfo["hideemptycontents"])) {
					$empty_ledsmart = get_unraid_disk_status("grey-off", '', '', $force_orb_led);
				}
				if(!empty($displayinfo["ledtemp"]) && $displayinfo["ledtemp"] == 1 && empty($displayinfo["hideemptycontents"])) {
					$empty_ledtemp = get_unraid_disk_status("grey-off", '', '', $force_orb_led);
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
									" . get_unraid_disk_status("grey-off", '', '', $force_orb_led) . "
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
				$hash = $data["hash"];
				$color_override = $data["color"];
				$warranty_page = "";
				$purchased_date = "";
				$installed_date = "";
				$smart_status = 0;
				$smart_status_icon = "";
				$smart_powermode = "";
				$smart_modelfamily = "";
				$smart_modelname = "";
				$smart_serialnumber = "";
				$smart_powerontime = "";
				$smart_loadcycle = "";
				$smart_capacity = "";
				$smart_cache = "";
				$smart_reallocated_sector_count = "";
				$smart_reported_uncorrectable_errors = "";
				$smart_command_timeout = "";
				$smart_current_pending_sector_count = "";
				$smart_offline_uncorrectable = "";
				$device_comment = "";
				$smart_rotation = "";
				$smart_formfactor = "";
				$smart_temperature = 0;
				$smart_temperature_text = "";
				$smart_units_read = "";
				$smart_units_written = "";
				$smart_nvme_percentage_used = "";
				$smart_nvme_available_spare = null;
				$smart_nvme_available_spare_threshold = null;
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
				
				if(!empty($displayinfo["path"])) {
					$device_page = $device;
				}
				if(!empty($displayinfo["devicenode"])) {
					$devicenode_page = $devicenode;
				}
				if(!empty($displayinfo["luname"])) {
					$luname_page = "(" . $luname . ")";
				}
				if(!empty($displayinfo["manufacturer"])) {
					$smart_modelfamily = $data["model_family"];
				}
				if(!empty($displayinfo["devicemodel"])) {
					$smart_modelname = $data["model_name"];
				}
				if(!empty($displayinfo["serialnumber"])) {
					$smart_serialnumber = ( isset($data["smart_serialnumber"]) ? "<span style=\"white-space: nowrap; " . $css_serial_number_highlight . "\">" . substr($data["smart_serialnumber"], $dashboard_widget_pos) . "</span>" : null );
				}
				if(!empty($displayinfo["powerontime"])) {
					$smart_powerontime = ( !is_numeric($data["smart_powerontime"]) ? null : "<span style=\"cursor: help;\" title=\"" . seconds_to_time($data["smart_powerontime"] * 60 * 60) . "\">" . $data["smart_powerontime"] . "h</span>" );
				}
				if(!empty($displayinfo["loadcyclecount"])) {
					$smart_loadcycle = ( !is_numeric($data["smart_loadcycle"]) ? null : $data["smart_loadcycle"] . "c" );
				}
				if(!empty($displayinfo["capacity"])) {
					$smart_capacity = ( !is_numeric($data["smart_capacity"]) ? null : human_filesize($data["smart_capacity"], 1, true) );
				}
				if(!empty($displayinfo["cache"])) {
					$smart_cache = ($data["smart_cache"] ? $data["smart_cache"] . "MB" : "");
				}
				if(!empty($displayinfo["reallocated_sector_count"])) {
					$smart_reallocated_sector_count = "" . ( !empty($data["smart_reallocated_sector_count"]) ? "<span style=\"cursor: help;\" title=\"Reallocated sector count\">RSC:" . $data["smart_reallocated_sector_count"] . "</span>" : "<span style=\"cursor: help;\" title=\"Reallocated sector count\">RSC:0</span>" );
				}
				if(!empty($displayinfo["reported_uncorrectable_errors"])) {
					$smart_reported_uncorrectable_errors = "" . ( !empty($data["smart_reported_uncorrectable_errors"]) ? "<span style=\"cursor: help;\" title=\"Reported uncorrectable errors\">RUE:" . $data["smart_reported_uncorrectable_errors"] . "</span>" : "<span style=\"cursor: help;\" title=\"Reported uncorrectable errors\">RUE:0</span>" );
				}
				if(!empty($displayinfo["command_timeout"])) {
					$smart_command_timeout = "" . ( !empty($data["smart_command_timeout"]) ? "<span style=\"cursor: help;\" title=\"Command timeout\">T/O:" . $data["smart_command_timeout"] . "</span>" : "<span style=\"cursor: help;\" title=\"Command timeout\">T/O:0</span>" );
				}
				if(!empty($displayinfo["current_pending_sector_count"])) {
					$smart_current_pending_sector_count = "" . ( !empty($data["smart_current_pending_sector_count"]) ? "<span style=\"cursor: help;\" title=\"Current pending sector count\">PSC:" . $data["smart_current_pending_sector_count"] . "</span>" : "<span style=\"cursor: help;\" title=\"Current pending sector count\">PSC:0</span>" );
				}
				if(!empty($displayinfo["offline_uncorrectable"])) {
					$smart_offline_uncorrectable = "" . ( !empty($data["smart_offline_uncorrectable"]) ? "<span style=\"cursor: help;\" title=\"Offline uncorrectable\">OU:" . $data["smart_offline_uncorrectable"] . "</span>" : "<span style=\"cursor: help;\" title=\"Offline uncorrectable\">OU:0</span>" );
				}
				if(!empty($displayinfo["percentage_used"])) {
					$smart_nvme_percentage_used = ( !is_numeric($data["smart_nvme_percentage_used"]) ? null : "Used: " . $data["smart_nvme_percentage_used"] . "%" );
				}
				if($data["smart_rotation"] == -2) {
					if(!empty($displayinfo["units_read"])) {
						$smart_units_read = ( empty($data["smart_units_read"]) ? null : "R:" . human_filesize(smart_units_to_bytes($data["smart_units_read"], $data["smart_logical_block_size"], true), 1, true) );
					}
					if(!empty($displayinfo["units_written"])) {
						$smart_units_written = ( empty($data["smart_units_written"]) ? null : "W:" . human_filesize(smart_units_to_bytes($data["smart_units_written"], $data["smart_logical_block_size"], true), 1, true) );
					}
				}
				else {
					if(!empty($displayinfo["units_read"])) {
						$smart_units_read = ( empty($data["smart_units_read"]) ? null : "R:" . human_filesize(smart_units_to_bytes($data["smart_units_read"], $data["smart_logical_block_size"], true, true), 1, true) );
					}
					if(!empty($displayinfo["units_written"])) {
						$smart_units_written = ( empty($data["smart_units_written"]) ? null : "W:" . human_filesize(smart_units_to_bytes($data["smart_units_written"], $data["smart_logical_block_size"], true, true), 1, true) );
					}
				}
				if(!empty($displayinfo["manufactured"])) {
					$manufactured_page = $data["manufactured"];
				}
				if(!empty($displayinfo["manufactured"])) {
					$purchased_date = $data["purchased"];
				}
				if(!empty($displayinfo["manufactured"])) {
					$installed_date = $data["installed"];
				}
				if(!empty($displayinfo["warranty"]) && ($data["purchased"] && ($data["warranty"] || $data["warranty_date"]))) {
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
				if(!empty($displayinfo["comment"])) {
					$device_comment = ( !isset($data["comment"]) ? null : bscode2html(stripslashes(htmlspecialchars($data["comment"]))) );
				}
				
				$unraid_array[$data["devicenode"]]["hotTemp"] = ( $unraid_array[$data["devicenode"]]["hotTemp"] ? $unraid_array[$data["devicenode"]]["hotTemp"] : $GLOBALS["display"]["hot"] );
				$unraid_array[$data["devicenode"]]["maxTemp"] = ( $unraid_array[$data["devicenode"]]["maxTemp"] ? $unraid_array[$data["devicenode"]]["maxTemp"] : $GLOBALS["display"]["max"] );
				
				if(!empty($displayinfo["temperature"]) || !empty($displayinfo["ledtemp"])) {
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
					else {
						$smart_temperature = '';
						$smart_temperature_warning = '';
						$smart_temperature_critical = '';
					}
				}
				if(!empty($displayinfo["temperature"])) {
					$smart_temperature_text = $smart_temperature;
				}
				if(!empty($displayinfo["rotation"])) {
					$smart_rotation = get_smart_rotation($data["smart_rotation"]);
				}
				if(!empty($displayinfo["formfactor"])) {
					$smart_formfactor = str_replace(" inches", "&quot;", $data["smart_formfactor"]);
				}
				
				if(!empty($displayinfo["leddiskop"])) {
					$zfs_disk_status = "";
					if($zfs_check) {
						$zfs_disk_status = zfs_disk("" . $data["smart_serialnumber"] . "", $zfs_parser, $lsblk_array);
					}
					
					$unraid_disk_status_color = get_powermode($device);
					
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
				
				$smart_status = $data["smart_status"];
				switch($smart_status) {
					case 1:
						$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation green-orb-disklocation'></i><span>S.M.A.R.T: Passed</span></a>";
						$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation green-orb-disklocation', 'color' => 'green', 'text' => 'Passed');
						//$smart_status_icon = "<span class=\"green-on\" alt=\"S.M.A.R.T: Passed\" title=\"S.M.A.R.T: Passed\" />&#11044;</span>";
						break;
					case 0:
						$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation red-orb-disklocation red-blink-disklocation'></i><span>S.M.A.R.T: Failed!</span></a>";
						$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation red-orb-disklocation red-blink-disklocation', 'color' => 'red', 'text' => 'Failed');
						//$smart_status_icon = "<span class=\"red-on\" alt=\"S.M.A.R.T: Failed!\" title=\"S.M.A.R.T: Failed!\" />&#11044;</span>";
						break;
					default:
						$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation grey-orb-disklocation'></i><span>S.M.A.R.T: N/A</span></a>";
						$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation grey-orb-disklocation', 'color' => 'grey', 'text' => 'N/A');
						//$smart_status_icon = "<span class=\"grey-off\" alt=\"S.M.A.R.T: Off/None\" title=\"S.M.A.R.T: Off/None\" />&#11044;</span>";
				}
				// Set warning if available spare has reached the threshold and overwrite the smart_status if "PASSED":
				if($smart_status == 1) {
					if(!get_disk_ack($unraid_array[$data["devicenode"]]["name"])) { // skip SMART warning if it has been manually acknowledged.
						if((is_numeric($data["smart_nvme_available_spare"]) && is_numeric($data["smart_nvme_available_spare_threshold"])) && ($data["smart_nvme_available_spare"] <= $data["smart_nvme_available_spare_threshold"])) { // Available spare is less than or equal to the threshold, set warning:
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation'></i><span>S.M.A.R.T: Warning! Available spare has reached or exceeded the threshold.</span></a>";
							$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation', 'color' => 'yellow', 'text' => 'Warning');
							$smart_status = 2;
						}
						if((is_numeric($data["smart_reallocated_sector_count"]) && ($data["smart_reallocated_sector_count"] > $reallocated_sector_w) && $reallocated_sector_w)) {
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation'></i><span>S.M.A.R.T: Warning! Reallocated sector count is " . $data["smart_reallocated_sector_count"] . ".</span></a>";
							$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation', 'color' => 'yellow', 'text' => 'Warning');
							$smart_status = 2;
						}
						if((is_numeric($data["smart_reported_uncorrectable_errors"]) && ($data["smart_reported_uncorrectable_errors"] > $reported_uncorr_w) && $reported_uncorr_w)) {
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation'></i><span>S.M.A.R.T: Warning! Reported uncorrectable errors is " . $data["smart_reported_uncorrectable_errors"] . ".</span></a>";
							$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation', 'color' => 'yellow', 'text' => 'Warning');
							$smart_status = 2;
						}
						if((is_numeric($data["smart_command_timeout"]) && ($data["smart_command_timeout"] > $command_timeout_w) && $command_timeout_w)) {
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation'></i><span>S.M.A.R.T: Warning! Command timeout is " . $data["smart_command_timeout"] . ".</span></a>";
							$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation', 'color' => 'yellow', 'text' => 'Warning');
							$smart_status = 2;
						}
						if((is_numeric($data["smart_current_pending_sector_count"]) && ($data["smart_current_pending_sector_count"] > $pending_sector_w) && $pending_sector_w)) {
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation'></i><span>S.M.A.R.T: Warning! Current pending sector count is " . $data["smart_current_pending_sector_count"] . ".</span></a>";
							$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation', 'color' => 'yellow', 'text' => 'Warning');
							$smart_status = 2;
						}
						if((is_numeric($data["smart_offline_uncorrectable"]) && ($data["smart_offline_uncorrectable"] > $offline_uncorr_w) && $offline_uncorr_w)) {
							$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation'></i><span>S.M.A.R.T: Warning! Offline uncorrectable is " . $data["smart_offline_uncorrectable"] . ".</span></a>";
							$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation yellow-orb-disklocation yellow-blink-disklocation', 'color' => 'yellow', 'text' => 'Warning');
							$smart_status = 2;
						}
					}
				}
				
				if(!empty($displayinfo["available_spare"])) {
					$smart_nvme_available_spare = ( !is_numeric($data["smart_nvme_available_spare"]) ? null : "Spare: " . $data["smart_nvme_available_spare"] . "% (" . $data["smart_nvme_available_spare_threshold"] . "%)" );
				}
				
				if(!!empty($displayinfo["ledsmart"])) {
					$smart_status_icon = "";
				}
				
				if(!$unraid_array[$devicenode]["temp"] || !is_numeric($unraid_array[$devicenode]["temp"])) { // && (!$unraid_array[$devicenode]["temp"] && $unraid_array[$devicenode]["hotTemp"] == 0 && $unraid_array[$devicenode]["maxTemp"] == 0)) {
					$unraid_array[$devicenode]["temp"] = 0;
					
					$temp_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation grey-orb-disklocation'></i><span>Temperature unavailable</span></a>";
					$temp_status_info = array('orb' => 'fa fa-circle orb-disklocation grey-orb-disklocation', 'color' => 'grey', 'text' => 'N/A');
					$temp_status = 0;
				}
				else {
					if($unraid_array[$devicenode]["temp"] < $unraid_array[$devicenode]["hotTemp"]) {
						$temp_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation green-orb-disklocation'></i><span>" . $smart_temperature . "</span></a>";
						$temp_status_info = array('orb' => 'fa fa-circle orb-disklocation green-orb-disklocation', 'color' => 'green', 'text' => $smart_temperature);
						$temp_status = 1;
					}
					if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["hotTemp"]) {
						$temp_status_icon = "<a class='info' style=\"margin: 0; text-align:left;\"><i class='fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation yellow-orb-disklocation yellow-blink-disklocation'></i><span>" . $smart_temperature . " (Warning: &gt;" . $smart_temperature_warning . ")</span></a>";
						$temp_status_info = array('orb' => "fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation yellow-orb-disklocation yellow-blink-disklocation", 'color' => 'yellow', 'text' => $smart_temperature);
						$temp_status = 2;
					}
					if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["maxTemp"]) {
						$temp_status_icon = "<a class='info'><i class='fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation red-orb-disklocation red-blink-disklocation'></i><span>" . $smart_temperature . " (Critical: &gt;" . $smart_temperature_critical . ")</span></a>";
						$temp_status_info = array('orb' => "fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation red-orb-disklocation red-blink-disklocation", 'color' => 'red', 'text' => $smart_temperature);
						$temp_status = 3;
					}
				}
				if(!!empty($displayinfo["ledtemp"])) {
					$temp_status_icon = "";
				}
				
				if(!empty($displayinfo["unraidinfo"])) {
					if($zfs_check) {
						$zfs_disk_info = zfs_disk($data["smart_serialnumber"], $zfs_parser, $lsblk_array, 1);
					}
					if(isset($unraid_array[$devicenode]["type"])) {
						$unraid_dev = "<b>" . $unraid_array[$devicenode]["type"] . "</b>: " . $unraid_array[$devicenode]["name"];
					}
					if(isset($zfs_disk_info["pool"])) {
						$unraid_dev = "<b>" . ucfirst($zfs_disk_info["pool"]) . "</b>";
					}
					if(!$unraid_dev) {
						$unraid_dev = "<b>Unassigned</b>: ";
					}
				}
				
				$drive_tray_order[$hash] = get_tray_location($db, $hash, $gid);
				$drive_tray_order[$hash] = ( !isset($drive_tray_order[$hash]) ? $tray_assign : $drive_tray_order[$hash] );
				if(!empty($displayinfo["tray"])) {
					if($tray_number_override[$drive_tray_order[$hash]]) {
						$drive_tray_order_assign = $tray_number_override[$drive_tray_order[$hash]];
						$physical_traynumber = ( !isset($tray_number_override_start) ? --$tray_number_override[$drive_tray_order[$hash]] : ($tray_number_override_start + $tray_number_override[$drive_tray_order[$hash]] - 1));
					}
					else {
						$drive_tray_order_assign = $drive_tray_order[$hash];
						$physical_traynumber = ( !isset($tray_number_override_start) ? --$drive_tray_order[$hash] : $drive_tray_order[$hash]);
					}
				}
				
				if(!empty($displayinfo["devicenode"])) {
					$devicenode_page = str_replace("-", "", $devicenode);
				}
				
				$add_break_1 = "";
				$add_break_2 = "";
				$add_break_3 = "";
				$add_break_4 = "";
				if($unraid_dev || $device_page || $devicenode_page || $luname_page || $smart_capacity || $smart_rotation || $smart_formfactor) {
					$add_break_1 = "<br />";
				}
				if($smart_modelfamily || $smart_modelname || $smart_serialnumber) {
					$add_break_2 = "<br />";
				}
				if($smart_temperature_text || $smart_powerontime || $smart_loadcycle || $warranty_page || $manufactured_page || $smart_nvme_available_spare || $smart_nvme_percentage_used || $smart_units_read || $smart_units_written || $smart_reallocated_sector_count || $smart_reported_uncorrectable_errors || $smart_command_timeout || $smart_current_pending_sector_count || $smart_offline_uncorrectable) {
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
				if(!empty($displayinfo["flashwarning"]) && ($temp_status == 2 || $smart_status == 2 || $color_status == "yellow")) { // warning
					$color_array_blinker = "blinker-disklocation-yellow-bg";
					$add_anim_bg_class = "class=\"yellow-blink-disklocation-bg\"";
				}
				if(!empty($displayinfo["flashcritical"]) && ($temp_status == 3 || !$smart_status || $color_status == "red")) { // critical
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
									$unraid_dev
									$device_page
									$devicenode_page
									$luname_page
									$smart_capacity
									" . ( ($data["smart_rotation"] > -2) ? $smart_cache : null ) . "
									$smart_rotation
									$smart_formfactor
									$add_break_1
									
									$smart_modelfamily
									$smart_modelname
									$smart_serialnumber
									$add_break_2
									
									$smart_temperature_text
									$smart_powerontime
									$smart_units_read
									$smart_units_written
									" . ( ($data["smart_rotation"] == -2) ? $smart_nvme_available_spare : null ) . "
									" . ( ($data["smart_rotation"] == -2) ? $smart_nvme_percentage_used : null ) . "
									" . ( ($data["smart_rotation"] > -2) ? $smart_loadcycle : null ) . "
									" . ( ($data["smart_rotation"] > -2) ? $smart_reallocated_sector_count : null ) . "
									" . ( ($data["smart_rotation"] > -2) ? $smart_reported_uncorrectable_errors : null ) . "
									" . ( ($data["smart_rotation"] > -2) ? $smart_command_timeout : null ) . "
									" . ( ($data["smart_rotation"] > -2) ? $smart_current_pending_sector_count : null ) . "
									" . ( ($data["smart_rotation"] > -2) ? $smart_offline_uncorrectable : null ) . "
									$manufactured_page
									$purchased_date
									$installed_date
									$warranty_page
									$add_break_3
									
									$device_comment
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
					//$dashboard_led = $unraid_array_icon;
					$dashboard_orb = $unraid_array_info["orb"];
					
				}
				// TEMP STATUS=warning|critical: show temp warning
				if(isset($temp_status) && $temp_status > 1) { 
					//$dashboard_led = $temp_status_icon;
					$dashboard_orb = $temp_status_info["orb"];
					
				}
				// SMART=FAIL/WARN: show SMART LED
				if(isset($smart_status) && ($smart_status == 0 || $smart_status == 2)) {
					//$dashboard_led = $smart_status_icon;
					$dashboard_orb = $smart_status_info["orb"];
					
				}
				$dashboard_text = "" . $temp_status_info["text"] . " | SMART: " . $smart_status_info["text"] . ", " . $unraid_array_info["text"] . "";
				
				$disklocation_dash[$gid] .= "
					<div style=\"order: " . $drive_tray_order[$hash] . "\">
						<div class=\"flex-container-layout_" . $disk_tray_direction . "\">
							<div id=\"bg4-" . $device . "\" $add_anim_bg_class style=\"background-color: #" . ( !empty($add_anim_bg_class) ? $color_array_blinker : $color_array[$hash] ) . "; width: " . $tray_width/$tray_reduction_factor . "px; height: " . $tray_height/$tray_reduction_factor . "px;\">
								<div class=\"flex-container-start\" style=\"text-align: center;/*min-height: 15px;*/\">
									<a class='info'><i class='" . $dashboard_orb . "'></i><span>" . $dashboard_text . "</span></a>
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
