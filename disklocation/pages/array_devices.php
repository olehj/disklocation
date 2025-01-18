<?php
	/*
	 *  Copyright 2025, Ole-Henrik Jakobsen
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
	
	//require_once("system.php"); // for debugging
	$devices = array();
	$array_groups = $get_groups;
	$array_devices = $get_devices;
	$array_locations = $get_locations;
	
	foreach($get_devices as $hash => $data) {
		$device = $data["device"];
		$devicenode = $data["devicenode"];
		//$hash = $data["hash"];
		$pool = "";
		
		$purchased_date = "";
		$installed_date = "";
		$smart_status = 0;
		$smart_status_icon = "";
		$smart_powermode = "";
		$smart_loadcycle = "";
		$smart_temperature = 0;
		
		$gid = $array_locations[$hash]["groupid"];
		$groupid = $gid;
		
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
		$devices[$hash]["raw"]["status"] = $data["status"] ?? null;
		$devices[$hash]["formatted"]["status"] = $devices[$hash]["raw"]["status"];
		$devices[$hash]["raw"]["tray"] = $array_locations[$hash]["tray"] ?? 0;
		$devices[$hash]["formatted"]["tray"] = $devices[$hash]["raw"]["tray"];
		$devices[$hash]["raw"]["groupid"] = $groupid;
		$devices[$hash]["formatted"]["groupid"] = $devices[$hash]["raw"]["groupid"];
		$devices[$hash]["raw"]["group_name"] = $array_groups[$gid]["group_name"];
		$devices[$hash]["formatted"]["group_name"] = $devices[$hash]["raw"]["group_name"];
		$devices[$hash]["raw"]["device"] = $device;
		$devices[$hash]["formatted"]["device"] = $devices[$hash]["raw"]["device"];
		$devices[$hash]["raw"]["node"] = $devicenode;
		$devices[$hash]["formatted"]["node"] = str_replace("-", "", $devices[$hash]["raw"]["node"]);
		$devices[$hash]["raw"]["serial"] = $data["smart_serialnumber"];
		$devices[$hash]["formatted"]["serial"] = ( isset($devices[$hash]["raw"]["serial"]) ? "" . substr($devices[$hash]["raw"]["serial"], $serial_trim) . "" : null );
		$devices[$hash]["raw"]["model"] = $data["model_name"];
		$devices[$hash]["formatted"]["model"] = $devices[$hash]["raw"]["model"];
		$devices[$hash]["raw"]["cache"] = $data["smart_cache"];
		$devices[$hash]["formatted"]["cache"] = "" . ( $devices[$hash]["raw"]["cache"] ? $devices[$hash]["raw"]["cache"] . "MB" : null );
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
		$devices[$hash]["formatted"]["powerontime"] = ( !is_numeric($devices[$hash]["raw"]["powerontime"]) ? null : "" . $devices[$hash]["raw"]["powerontime"] . "h (" . seconds_to_time($devices[$hash]["raw"]["powerontime"] * 60 * 60) . ")" );
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
		
		$smart_errors_text = "";
		$smart_i = 0;
		$get_smart_errors = array_values($smart_errors);
		while($smart_i < count($get_smart_errors)) {
			$smart_errors_text .= $get_smart_errors[$smart_i]["name"] . ": " . $get_smart_errors[$smart_i]["value"] . "\n";
			
			$smart_i++;
		}
		
		$devices[$hash]["raw"]["smart_errors"] = $smart_errors;
		$devices[$hash]["formatted"]["smart_errors"] = $smart_errors_text;
		
		// Various Unraid files $unraid_array (various selected variables in multiple INI files)
		
		$unraid_array[$data["devicenode"]]["hotTemp"] = ( $unraid_array[$data["devicenode"]]["hotTemp"] ? $unraid_array[$data["devicenode"]]["hotTemp"] : $GLOBALS["display"]["hot"] );
		$unraid_array[$data["devicenode"]]["maxTemp"] = ( $unraid_array[$data["devicenode"]]["maxTemp"] ? $unraid_array[$data["devicenode"]]["maxTemp"] : $GLOBALS["display"]["max"] );
		
		if(!empty($unraid_array[$data["devicenode"]]["temp"]) && is_numeric($unraid_array[$data["devicenode"]]["temp"]) && is_numeric($unraid_array[$devicenode]["temp"])) {
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
			$date_warranty = $devices[$hash]["raw"]["warranty"] . " months.";
			
			$warranty_expire_left = $warranty_end-date("U");
			if($warranty_expire_left > 0) {
				$warranty_left = "" . seconds_to_time($warranty_expire_left);
			}
			else {
				$warranty_left = "Expired";
			}
		}
		// $display["date"] => %A, %Y-%m-%d --- might use in the future for formatting. Using ISO for now:
		$devices[$hash]["formatted"]["purchased"] = $devices[$hash]["raw"]["purchased"];
		$devices[$hash]["formatted"]["warranty"] = "" . $warranty_expire . "";
		$devices[$hash]["raw"]["expires"] = "" . $warranty_left . "";
		$devices[$hash]["formatted"]["expires"] = "" . $warranty_left . "";
		$devices[$hash]["formatted"]["manufactured"] = $devices[$hash]["raw"]["manufactured"];
	}
	//print_r($devices); // for debugging
?>
