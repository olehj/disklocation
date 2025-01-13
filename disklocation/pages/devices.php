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
	$devices = array();
	$datajson = array();
	
	$zfs_check = 0;
	if(zfs_check()) {
		$zfs_parser = zfs_parser();
		$lsblk_array = json_decode(shell_exec("lsblk -p -o NAME,MOUNTPOINT,SERIAL,PATH --json"), true);
		$zfs_check = 1;
	}
	
	$array_groups = $get_groups;
	ksort($array_groups, SORT_NUMERIC);
	$array_devices = $get_devices;
	$array_locations = $get_locations;
	
	foreach($array_groups as $id => $value) {
		extract($value);
		
		$gid = $id;
		$groupid = $gid;

		$disklocation_page[$gid] = "";
		$disklocation_layout[$gid] = "";
		$disklocation_alloc[$gid] = "";
		$disklocation_dash[$gid] = "";
		
		$i_arr=0;
		if(!$total_groups) {
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
			}
			$datajson = sort_array($datajson, 'groupid', SORT_ASC, SORT_NUMERIC, 'tray', SORT_ASC, SORT_NUMERIC);
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
			$data = isset($datajson[$i_drive-1]) ? $datajson[$i_drive-1] : 0;
			$smart = array();
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
				$hash = $data["hash"];
				$pool = "";
				$color_override = $data["color"];
				$purchased_date = "";
				$installed_date = "";
				$smart_status = 0;
				$smart_status_icon = "";
				$smart_powermode = "";
				$smart_loadcycle = "";
				$smart_temperature = 0;
				$temp_status = 0;
				$temp_status_icon = "";
				$color_status = "";
				$unraid_array_icon = "";
				$physical_traynumber = null;
				
				if($zfs_check) {
					$zfs_disk_info = zfs_disk($data["smart_serialnumber"], $zfs_parser, $lsblk_array, 1);
				}
				if(isset($unraid_array[$devicenode]["type"])) {
					$pool = $unraid_array[$devicenode]["type"];
				}
				if(isset($zfs_disk_info["pool"])) {
					$pool = $zfs_disk_info["pool"];
				}
				if(!$pool) {
					$pool = "";
				}
				
				// DB $data:
				$devices[$hash]["raw"]["device"] = $device;
				$devices[$hash]["formatted"]["device"] = $devices[$hash]["raw"]["device"];
				$devices[$hash]["raw"]["node"] = $devicenode;
				$devices[$hash]["formatted"]["node"] = str_replace("-", "", $devices[$hash]["raw"]["node"]);
				$devices[$hash]["raw"]["serial"] = $data["smart_serialnumber"];
				$devices[$hash]["formatted"]["serial"] = ( isset($devices[$hash]["raw"]["serial"]) ? "<span style=\"white-space: nowrap;\">" . substr($devices[$hash]["raw"]["serial"], $serial_trim) . "</span>" : null );
				$devices[$hash]["raw"]["model"] = $data["model_name"];
				$devices[$hash]["formatted"]["model"] = $devices[$hash]["raw"]["model"];
				$devices[$hash]["raw"]["cache"] = $data["smart_cache"];
				$devices[$hash]["formatted"]["cache"] = "" . $devices[$hash]["raw"]["cache"] . "MB";
				$devices[$hash]["raw"]["installed"] = $data["installed"];
				$devices[$hash]["formatted"]["installed"] = $devices[$hash]["raw"]["installed"];
				$devices[$hash]["raw"]["removed"] = $data["removed"];
				$devices[$hash]["formatted"]["removed"] = $devices[$hash]["raw"]["removed"];
				$devices[$hash]["raw"]["comment"] = $data["comment"];
				$devices[$hash]["formatted"]["comment"] = $devices[$hash]["raw"]["comment"]; // bscode2html(stripslashes(htmlspecialchars($data["comment"])))
				
				// SMART files $smart_array:
				$smart_file[$hash] = file_get_contents(DISKLOCATION_TMP_PATH."/smart/".str_replace(" ", "_", $devices[$hash]["raw"]["model"])."_" . $devices[$hash]["raw"]["serial"] . ".json");
				$smart_json[$hash] = json_decode($smart_file[$hash], true);
				$smart_array = $smart_json[$hash];
				
				$devices[$hash]["raw"]["lun"] =  ( $smart_array["logical_unit_id"] ? $smart_array["logical_unit_id"] : "" . ($smart_array["wwn"]["naa"] ?? null) . " " . ($smart_array["wwn"]["oui"] ?? null) . " " . ($smart_array["wwn"]["id"] ?? null) . "" );
				$devices[$hash]["formatted"]["lun"] =  $devices[$hash]["raw"]["lun"];
				$devices[$hash]["raw"]["pool"] = $pool;
				$devices[$hash]["formatted"]["pool"] = ucfirst($devices[$hash]["raw"]["pool"]);
				$devices[$hash]["raw"]["manufacturer"] = $smart_array["model_family"];
				$devices[$hash]["formatted"]["manufacturer"] = $devices[$hash]["raw"]["manufacturer"];
				$devices[$hash]["raw"]["capacity"] = $smart_array["user_capacity"]["bytes"];
				$devices[$hash]["formatted"]["capacity"] = ( !is_numeric($devices[$hash]["raw"]["capacity"]) ? null : human_filesize($devices[$hash]["raw"]["capacity"], 1, true) );
				$devices[$hash]["raw"]["rotation"] = ( empty($smart_array["rotation_rate"]) && recursive_array_search("Solid State Device Statistics", $smart_array) ? -1 : ( isset($smart_array["device"]["type"]) && $smart_array["device"]["type"] == "nvme" ? -2 : $smart_array["rotation_rate"] ));
				$devices[$hash]["formatted"]["rotation"] = get_smart_rotation($devices[$hash]["raw"]["rotation"]);
				$devices[$hash]["raw"]["formfactor"] = $smart_array["form_factor"]["name"];
				$devices[$hash]["formatted"]["formfactor"] = str_replace(" inches", "&quot;", $devices[$hash]["raw"]["formfactor"]);
				$devices[$hash]["raw"]["smart_status"] = $smart_array["smart_status"]["passed"];
				$devices[$hash]["formatted"]["smart_status"] = ( ($devices[$hash]["raw"]["smart_status"] == true) ? "OK" : "FAIL");
				$devices[$hash]["raw"]["powerontime"] = $smart_array["power_on_time"]["hours"];
				$devices[$hash]["formatted"]["powerontime"] = ( !is_numeric($devices[$hash]["raw"]["powerontime"]) ? null : "<span style=\"cursor: help;\" title=\"" . seconds_to_time($devices[$hash]["raw"]["powerontime"] * 60 * 60) . "\">" . $devices[$hash]["raw"]["powerontime"] . "h</span>" );
				$devices[$hash]["raw"]["logical_block_size"] = $smart_array["logical_block_size"];
				$devices[$hash]["formatted"]["logical_block_size"] = $devices[$hash]["raw"]["logical_block_size"];
				$devices[$hash]["raw"]["nvme_available_spare"]  = $smart_array["nvme_smart_health_information_log"]["available_spare"];
				$devices[$hash]["formatted"]["nvme_available_spare"]  = "Spare: " . $devices[$hash]["raw"]["nvme_available_spare"] . "%";
				$devices[$hash]["raw"]["nvme_available_spare_threshold"] = $smart_array["nvme_smart_health_information_log"]["available_spare_threshold"];
				$devices[$hash]["formatted"]["nvme_available_spare_threshold"] = $devices[$hash]["raw"]["nvme_available_spare_threshold"];
				$devices[$hash]["raw"]["nvme_percentage_used"] =  $smart_array["nvme_smart_health_information_log"]["percentage_used"];
				$devices[$hash]["formatted"]["nvme_percentage_used"] =  $devices[$hash]["raw"]["nvme_percentage_used"] . "%";
				
				// SMART data to be parsed on deeper level:
				if(isset($smart_array["device"]["protocol"]) && $smart_array["device"]["protocol"] == "SCSI") {
					$smart_loadcycle = ( is_array($smart_array["accumulated_load_unload_cycles"]) ?? $smart_array["accumulated_load_unload_cycles"] );
				}
				$smart_errors = array();
				$unraid_smart_arr = explode("|", empty($unraid_array[$devicenode]["smEvents"]) ? $get_global_smEvents : $unraid_array[$devicenode]["smEvents"] );
				$smart_status = ( ($smart_array["smart_status"]["passed"] == true) ? 1 : 0);
				$smart_units_read = $smart_array["nvme_smart_health_information_log"]["data_units_read"];
				$smart_units_written = $smart_array["nvme_smart_health_information_log"]["data_units_written"];
				if(isset($smart_array["ata_smart_attributes"]["table"])) {
					$smart_i = 0;
					while($smart_i < count($smart_array["ata_smart_attributes"]["table"])) {
						if($smart_array["ata_smart_attributes"]["table"][$smart_i]["name"] == "Load_Cycle_Count") {
							$smart_loadcycle = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
						}
						if($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"] == 241) {
							$smart_units_written = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
						}
						if($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"] == 242) {
							$smart_units_read = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
						}
						
						if(in_array($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"], $unraid_smart_arr)) {
							if($smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"] > 0) {
								$smart_errors[$smart_i]["name"] = "" . str_replace("_", " ", $smart_array["ata_smart_attributes"]["table"][$smart_i]["name"]) . "";
								$smart_errors[$smart_i]["value"] = "" . $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"] . "";
							}
						}
						$smart_i++;
					}
				}
				
				$devices[$hash]["raw"]["smart_units_read"] = $smart_units_read;
				$devices[$hash]["formatted"]["smart_units_read"] = (($devices[$hash]["raw"]["rotation"] == -2) ? human_filesize(smart_units_to_bytes($devices[$hash]["raw"]["smart_units_read"], $devices[$hash]["raw"]["logical_block_size"], true), 1, true) : human_filesize(smart_units_to_bytes($devices[$hash]["raw"]["smart_units_read"], $devices[$hash]["raw"]["logical_block_size"], true, true), 1, true) );
				$devices[$hash]["raw"]["smart_units_written"] = $smart_units_written;
				$devices[$hash]["formatted"]["smart_units_written"] = (($devices[$hash]["raw"]["rotation"] == -2) ? human_filesize(smart_units_to_bytes($devices[$hash]["raw"]["smart_units_written"], $devices[$hash]["raw"]["logical_block_size"], true), 1, true) : human_filesize(smart_units_to_bytes($devices[$hash]["raw"]["smart_units_written"], $devices[$hash]["raw"]["logical_block_size"], true, true), 1, true) );
				$devices[$hash]["raw"]["loadcycle"] = $smart_loadcycle;
				$devices[$hash]["formatted"]["loadcycle"] = ( !is_numeric($smart_loadcycle) ? null : $smart_loadcycle . "c" );
				
				// Set $smart_status = 2 if $smart_errors was found AND $smart_status has NOT failed AND disk has NOT been acknowledged, else set initial value:
				$smart_status = ((!empty($smart_errors) && !empty($smart_status) && !get_disk_ack($unraid_array[$data["devicenode"]]["name"])) ? 2 : $smart_status);
				
				$smart_errors_text = "";
				$smart_i = 0;
				$get_smart_errors = array_values($smart_errors);
				while($smart_i < count($get_smart_errors)) {
					$smart_errors_text .= $get_smart_errors[$smart_i]["name"] . ": " . $get_smart_errors[$smart_i]["value"] . "\n";
					
					$smart_i++;
				}
				
				switch($smart_status) {
					case 0:
						$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation red-orb-disklocation'></i><span>S.M.A.R.T: Failed! " . $smart_errors_text . "</span></a>";
						$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation red-orb-disklocation', 'color' => 'red', 'text' => 'Failed');
						break;
					case 1:
						$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation green-orb-disklocation'></i><span>S.M.A.R.T: Passed</span></a>";
						$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation green-orb-disklocation', 'color' => 'green', 'text' => 'Passed');
						break;
					case 2:
						$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation yellow-orb-disklocation'></i><span>S.M.A.R.T: Warning! " . $smart_errors_text . "</span></a>";
						$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation yellow-orb-disklocation', 'color' => 'yellow', 'text' => 'Warning');
						break;
					default:
						$smart_status_icon = "<a class='info'><i class='fa fa-circle orb-disklocation grey-orb-disklocation'></i><span>S.M.A.R.T: N/A</span></a>";
						$smart_status_info = array('orb' => 'fa fa-circle orb-disklocation grey-orb-disklocation', 'color' => 'grey', 'text' => 'N/A');
				}
				$devices[$hash]["raw"]["smart_errors"] = $smart_errors;
				$devices[$hash]["formatted"]["smart_errors"] = $smart_errors_text;
				
				// Various Unraid files $unraid_array (various selected variables in multiple INI files)
				
				$unraid_array[$data["devicenode"]]["hotTemp"] = ( $unraid_array[$data["devicenode"]]["hotTemp"] ? $unraid_array[$data["devicenode"]]["hotTemp"] : $GLOBALS["display"]["hot"] );
				$unraid_array[$data["devicenode"]]["maxTemp"] = ( $unraid_array[$data["devicenode"]]["maxTemp"] ? $unraid_array[$data["devicenode"]]["maxTemp"] : $GLOBALS["display"]["max"] );
				
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
						$temp_status_icon = "<a class='info' style=\"margin: 0; text-align:left;\"><i class='fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation yellow-orb-disklocation'></i><span>" . $smart_temperature . " (Warning: &gt;" . $smart_temperature_warning . ")</span></a>";
						$temp_status_info = array('orb' => "fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation yellow-orb-disklocation", 'color' => 'yellow', 'text' => $smart_temperature);
						$temp_status = 2;
					}
					if($unraid_array[$devicenode]["temp"] >= $unraid_array[$devicenode]["maxTemp"]) {
						$temp_status_icon = "<a class='info'><i class='fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation red-blink-disklocation'></i><span>" . $smart_temperature . " (Critical: &gt;" . $smart_temperature_critical . ")</span></a>";
						$temp_status_info = array('orb' => "fa fa-" . ( !$force_orb_led ? 'fire' : 'circle' ) . " orb-disklocation red-blink-disklocation", 'color' => 'red', 'text' => $smart_temperature);
						$temp_status = 3;
					}
				}
				if(!isset($displayinfo["ledtemp"])) {
					$temp_status_icon = "";
				}
				
				$devices[$hash]["raw"]["name"] = $unraid_array[$devicenode]["name"];
				$devices[$hash]["formatted"]["name"] = $devices[$hash]["raw"]["name"];
				$devices[$hash]["raw"]["temp"] = $unraid_array[$data["devicenode"]]["temp"];
				$devices[$hash]["formatted"]["temp"] = $smart_temperature;
				$devices[$hash]["raw"]["hotTemp"] = $unraid_array[$data["devicenode"]]["hotTemp"];
				$devices[$hash]["formatted"]["hotTemp"] = $smart_temperature_warning;
				$devices[$hash]["raw"]["maxTemp"] = $unraid_array[$data["devicenode"]]["maxTemp"];
				$devices[$hash]["formatted"]["maxTemp"] = $smart_temperature_critical;
				
				// Unraid disk.ini $unraid_disklog
				$devices[$hash]["raw"]["purchased"] = "" . $unraid_disklog["" . str_replace(" ", "_", $devices[$hash]["raw"]["model"]) . "_" . str_replace(" ", "_", $devices[$hash]["raw"]["serial"]) . ""]["purchase"] . "";
				$devices[$hash]["raw"]["warranty"] = "" . $unraid_disklog["" . str_replace(" ", "_", $devices[$hash]["raw"]["model"]) . "_" . str_replace(" ", "_", $devices[$hash]["raw"]["serial"]) . ""]["warranty"] . "";
				$devices[$hash]["raw"]["manufactured"] = "" . $unraid_disklog["" . str_replace(" ", "_", $devices[$hash]["raw"]["model"]) . "_" . str_replace(" ", "_", $devices[$hash]["raw"]["serial"]) . ""]["date"] . "";
				$date_warranty = "";
				$warranty_expire = "";
				$warranty_left = "";
				//"warranty_date" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><span style=\"cursor: help;\" title=\"Warranty: " . $date_warranty . " Expires: " . $warranty_left . "\">" . $warranty_expire . "</span></td>",
				if($devices[$hash]["raw"]["purchased"] && ($devices[$hash]["raw"]["warranty"])) {
					$warranty_start = strtotime($devices[$hash]["raw"]["purchased"]);
					
					$warranty_end = strtotime("" . $devices[$hash]["raw"]["purchased"] . " + " . $devices[$hash]["raw"]["warranty"] . " month");
					$warranty_expire = date("Y-m-d", $warranty_end);
					$date_warranty = $data["warranty"] . " months.";
					
					$warranty_expire_left = $warranty_end-date("U");
					if($warranty_expire_left > 0) {
						$warranty_left = seconds_to_time($warranty_expire_left);
					}
					else {
						$warranty_left = "EXPIRED!";
					}
				}
				// $display["date"] => %A, %Y-%m-%d --- might use in the future for formatting. Using ISO for now:
				$devices[$hash]["formatted"]["purchased"] = $devices[$hash]["raw"]["purchased"];
				$devices[$hash]["formatted"]["warranty"] = "<span style=\"cursor: help;\" title=\"Warranty: " . $date_warranty . " Expires: " . $warranty_left . "\">" . $warranty_expire . "</span>";
				$devices[$hash]["formatted"]["manufactured"] = $devices[$hash]["raw"]["manufactured"];
				
				if(isset($displayinfo["leddiskop"])) {
					$zfs_disk_status = "";
					if($zfs_check) {
						$zfs_disk_status = zfs_disk("" . $data["smart_serialnumber"] . "", $zfs_parser, $lsblk_array);
					}
					
					if(!$zfs_disk_status && isset($unraid_array[$devicenode]["color"]) && isset($unraid_array[$devicenode]["status"])) {
						$unraid_array_icon = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["type"], '', $force_orb_led);
						$unraid_array_info = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["type"],'array');
						$color_status = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["type"],'color');
					}
					else {
						$smart_powermode = config("/tmp/disklocation/powermode.ini", 'r', $device);
						switch($smart_powermode) {
							case "ACTIVE":
								$unraid_disk_status_color = "green-on";
								break;
							case "IDLE":
								$unraid_disk_status_color = "green-on";
								break;
							case "STANDBY":
								$unraid_disk_status_color = "green-blink";
								break;
							case "UNKNOWN":
								$unraid_disk_status_color = "grey-off";
								break;
							default:
								$unraid_disk_status_color = "grey-off";
						}
						if($zfs_disk_status) {
							$unraid_array_icon = get_unraid_disk_status($zfs_disk_status[1], '', '', $force_orb_led);
							$unraid_array_info = get_unraid_disk_status($zfs_disk_status[1],'','array');
							$color_status = get_unraid_disk_status($zfs_disk_status[1],'','color');
						}
						else {
							$unraid_array_icon = get_unraid_disk_status($unraid_disk_status_color, '', '', $force_orb_led);
							$unraid_array_info = get_unraid_disk_status($unraid_disk_status_color,'','array');
							$color_status = get_unraid_disk_status($unraid_disk_status_color,'','color');
						}
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
				
				if(!$dashboard_widget) { // $dashboard_widget is really for Disk Type / Heatmap setting.
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
					if(!$unraid_array[$devicenode]["temp"] && (!$unraid_array[$devicenode]["temp"] && $unraid_array[$devicenode]["hotTemp"] == 0 && $unraid_array[$devicenode]["maxTemp"] == 0)) {
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
									" . keys_to_content(bscode2html(nl2br(stripslashes(htmlspecialchars($select_db_devices)))), $devices[$hash]["formatted"]) . "
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
		
		unset($datajson); // delete array
	}
	
	//print_r($devices); // for debugging
?>
