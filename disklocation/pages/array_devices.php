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
	$count_installed_devices = 0;
	
	foreach($get_devices as $hash => $data) {
		$device = $data["device"];
		$devicenode = $data["devicenode"];
		//$hash = $data["hash"];
		$pool = "";
		
		$purchased_date = "";
		$installed_date = "";
		$smart_status = 0;
		$smart_status_icon = "";
		$smart_loadcycle = "";
		$smart_temperature = 0;
		$smart_endurance_used = null;
		
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
			$pool = "Unassigned";
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
		$devices[$hash]["raw"]["manufacturer"] = $data["manufacturer"];
		$devices[$hash]["formatted"]["manufacturer"] = $devices[$hash]["raw"]["manufacturer"];
		$devices[$hash]["raw"]["model"] = $data["model_name"];
		$devices[$hash]["formatted"]["model"] = $devices[$hash]["raw"]["model"];
		$devices[$hash]["raw"]["capacity"] = $data["capacity"];
		$devices[$hash]["formatted"]["capacity"] = ( !is_numeric($devices[$hash]["raw"]["capacity"]) ? null : human_filesize($devices[$hash]["raw"]["capacity"], 1, true) );
		$devices[$hash]["raw"]["rotation"] = $data["rotation"];
		$devices[$hash]["formatted"]["rotation"] = get_smart_rotation($devices[$hash]["raw"]["rotation"]);
		$devices[$hash]["raw"]["formfactor"] = $data["formfactor"];
		$devices[$hash]["formatted"]["formfactor"] = str_replace(" inches", "\"", $devices[$hash]["raw"]["formfactor"]);
		$devices[$hash]["raw"]["cache"] = $data["smart_cache"];
		$devices[$hash]["formatted"]["cache"] = "" . ( $devices[$hash]["raw"]["cache"] ? $devices[$hash]["raw"]["cache"] . "MB" : null );
		$devices[$hash]["raw"]["loadcycle"] = $data["loadcycle"];
		$devices[$hash]["formatted"]["loadcycle"] = ( !is_numeric($devices[$hash]["raw"]["loadcycle"]) ? null : $devices[$hash]["raw"]["loadcycle"] . "" );
		$devices[$hash]["raw"]["powerontime_hours"] = $data["powerontime"];
		$devices[$hash]["formatted"]["powerontime_hours"] = ( !is_numeric($devices[$hash]["raw"]["powerontime_hours"]) ? null : "" . $devices[$hash]["raw"]["powerontime_hours"] . "" );
		$devices[$hash]["raw"]["powerontime"] = $devices[$hash]["raw"]["powerontime_hours"];
		$devices[$hash]["formatted"]["powerontime"] = ( !is_numeric($devices[$hash]["raw"]["powerontime"]) ? null : seconds_to_time($devices[$hash]["raw"]["powerontime"] * 60 * 60) );
		$devices[$hash]["raw"]["installed"] = $data["installed"];
		$devices[$hash]["formatted"]["installed"] = $devices[$hash]["raw"]["installed"];
		$devices[$hash]["raw"]["removed"] = $data["removed"];
		$devices[$hash]["formatted"]["removed"] = $devices[$hash]["raw"]["removed"];
		$devices[$hash]["raw"]["comment"] = $data["comment"];
		$devices[$hash]["formatted"]["comment"] = $devices[$hash]["raw"]["comment"]; // bscode2html(stripslashes(htmlspecialchars($data["comment"])))
		$devices[$hash]["raw"]["bgcolor"] = $data["color"];
		$devices[$hash]["formatted"]["bgcolor"] = $devices[$hash]["raw"]["bgcolor"];
		
		// SMART files $smart_array:
		$smart_file[$hash] = file_get_contents(DISKLOCATION_TMP_PATH."/smart/".str_replace(" ", "_", $devices[$hash]["raw"]["model"])."_" . $devices[$hash]["raw"]["serial"] . ".json");
		$smart_json[$hash] = json_decode($smart_file[$hash], true);
		$smart_array = $smart_json[$hash];
		
		$devices[$hash]["raw"]["lun"] =  ( $smart_array["logical_unit_id"] ? $smart_array["logical_unit_id"] : "" . ($smart_array["wwn"]["naa"] ?? null) . " " . ($smart_array["wwn"]["oui"] ?? null) . " " . ($smart_array["wwn"]["id"] ?? null) . "" );
		$devices[$hash]["formatted"]["lun"] =  $devices[$hash]["raw"]["lun"];
		$devices[$hash]["raw"]["pool"] = $pool;
		$devices[$hash]["formatted"]["pool"] = ucfirst($devices[$hash]["raw"]["pool"]);
		$devices[$hash]["raw"]["smart_status"] = (!empty($smart_array["smart_status"]["passed"]) ? 1 : 0);
		$devices[$hash]["formatted"]["smart_status"] = ( ($devices[$hash]["raw"]["smart_status"] === 1) ? "PASSED" : "FAILED");
		$devices[$hash]["raw"]["logical_block_size"] = $smart_array["logical_block_size"];
		$devices[$hash]["formatted"]["logical_block_size"] = $devices[$hash]["raw"]["logical_block_size"];
		$devices[$hash]["raw"]["nvme_available_spare"]  = $smart_array["nvme_smart_health_information_log"]["available_spare"];
		$devices[$hash]["formatted"]["nvme_available_spare"]  = ( isset($devices[$hash]["raw"]["nvme_available_spare"]) ? $devices[$hash]["raw"]["nvme_available_spare"] . "%" : null );
		$devices[$hash]["raw"]["nvme_available_spare_threshold"] = $smart_array["nvme_smart_health_information_log"]["available_spare_threshold"];
		$devices[$hash]["formatted"]["nvme_available_spare_threshold"] = ( isset($devices[$hash]["raw"]["nvme_available_spare_threshold"]) ? $devices[$hash]["raw"]["nvme_available_spare_threshold"] . "%" : null );
		
		if(isset($smart_array["ata_device_statistics"]["pages"])) {
			$smart_i = 0;
			while($smart_i < count($smart_array["ata_device_statistics"]["pages"])) {
				if($smart_array["ata_device_statistics"]["pages"][$smart_i]["name"] == "Solid State Device Statistics") {
					$smart_ssd_stats = ( isset($smart_array["ata_device_statistics"]["pages"][$smart_i]["table"]) ? $smart_array["ata_device_statistics"]["pages"][$smart_i]["table"] : null );
					if(isset($smart_ssd_stats)) {
						foreach($smart_ssd_stats as $id => $value) {
							if($value["name"] == "Percentage Used Endurance Indicator") {
								$smart_endurance_used = 100-$value["value"];
							}
						}
					}
				}
				$smart_i++;
			}
		}
		
		$devices[$hash]["raw"]["endurance"] = ( isset($smart_array["nvme_smart_health_information_log"]["percentage_used"]) ? 100-$smart_array["nvme_smart_health_information_log"]["percentage_used"] : $smart_endurance_used );
		$devices[$hash]["formatted"]["endurance"] = ( isset($devices[$hash]["raw"]["endurance"]) ? $devices[$hash]["raw"]["endurance"] . "%" : null );
		
		// Both DB $data & SMART files $smart_array:
		$dev_calc_unit_size = ( $devices[$hash]["raw"]["rotation"] == -1 ? 32 : $devices[$hash]["raw"]["logical_block_size"] );
		$dev_calc_unit_factor = ( $devices[$hash]["raw"]["rotation"] == -1 ? 1024*1024 : 1000 );
		
		$devices[$hash]["raw"]["smart_units_read"] = ( ($devices[$hash]["raw"]["rotation"] < 0) ? smart_units_to_bytes(($data["smart_units_read"] ? $data["smart_units_read"] : 0), $dev_calc_unit_size, $dev_calc_unit_factor) : smart_units_to_bytes(($data["smart_units_read"] ? $data["smart_units_read"] : 0), $dev_calc_unit_size, $dev_calc_unit_factor, true) );
		$devices[$hash]["formatted"]["smart_units_read"] = human_filesize($devices[$hash]["raw"]["smart_units_read"], 1, true);
		
		$devices[$hash]["raw"]["smart_units_written"] = ( ($devices[$hash]["raw"]["rotation"] < 0) ? smart_units_to_bytes(($data["smart_units_written"] ? $data["smart_units_written"] : 0), $dev_calc_unit_size, $dev_calc_unit_factor) : smart_units_to_bytes(($data["smart_units_written"] ? $data["smart_units_written"] : 0), $dev_calc_unit_size, $dev_calc_unit_factor, true) );
		$devices[$hash]["formatted"]["smart_units_written"] = human_filesize($devices[$hash]["raw"]["smart_units_written"], 1, true);
		
		// SMART data to be parsed on deeper level:
		if(isset($smart_array["device"]["protocol"]) && $smart_array["device"]["protocol"] == "SCSI") {
			$smart_loadcycle = ( is_array($smart_array["accumulated_load_unload_cycles"]) ?? $smart_array["accumulated_load_unload_cycles"] );
		}
		$smart_errors = array();
		$unraid_smart_arr = explode("|", empty($unraid_array[$devicenode]["smEvents"]) ? $get_global_smEvents : $unraid_array[$devicenode]["smEvents"] );
		$smart_status = $devices[$hash]["raw"]["smart_status"];
		
		if(isset($smart_array["ata_smart_attributes"]["table"])) {
			$smart_i = 0;
			while($smart_i < count($smart_array["ata_smart_attributes"]["table"])) {
				if(in_array($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"], $unraid_smart_arr)) {
					if($smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"] > 0) {
						$smart_errors[$smart_i]["name"] = "" . str_replace("_", " ", $smart_array["ata_smart_attributes"]["table"][$smart_i]["name"]) . "";
						$smart_errors[$smart_i]["value"] = "" . $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"] . "";
					}
				}
				$smart_i++;
			}
		}
		
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
		
		$unraid_array[$data["devicenode"]]["hotTemp"] = (($unraid_array[$data["devicenode"]]["hotTemp"] == $GLOBALS["display"]["hotssd"]) && !empty($data["nvme_wctemp"])) ? $data["nvme_wctemp"] : $unraid_array[$data["devicenode"]]["hotTemp"];
		$unraid_array[$data["devicenode"]]["maxTemp"] = (($unraid_array[$data["devicenode"]]["maxTemp"] == $GLOBALS["display"]["maxssd"]) && !empty($data["nvme_cctemp"])) ? $data["nvme_cctemp"] : $unraid_array[$data["devicenode"]]["maxTemp"];
		
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
		
		// Unraid disk.log $unraid_disklog
		$devices[$hash]["raw"]["purchased"] = "" . $unraid_disklog["" . str_replace(" ", "_", $devices[$hash]["raw"]["model"]) . "_" . str_replace(" ", "_", $devices[$hash]["raw"]["serial"]) . ""]["purchase"] . "";
		$devices[$hash]["raw"]["warranty"] = "" . $unraid_disklog["" . str_replace(" ", "_", $devices[$hash]["raw"]["model"]) . "_" . str_replace(" ", "_", $devices[$hash]["raw"]["serial"]) . ""]["warranty"] . "";
		$devices[$hash]["raw"]["manufactured"] = "" . $unraid_disklog["" . str_replace(" ", "_", $devices[$hash]["raw"]["model"]) . "_" . str_replace(" ", "_", $devices[$hash]["raw"]["serial"]) . ""]["date"] . "";
		$date_warranty = "";
		$warranty_expire = "";
		$warranty_left = "";
		
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
		$devices[$hash]["raw"]["expires"] = "" . $warranty_expire . "";
		$devices[$hash]["formatted"]["expires"] = "" . $warranty_left . "";
		$devices[$hash]["formatted"]["manufactured"] = $devices[$hash]["raw"]["manufactured"];
		
		// get benchmarks
		$benchmark_file[$hash] = UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/benchmark/".str_replace(" ", "_", $data["model_name"])."_" . $data["smart_serialnumber"] . ".json";
		$devices[$hash]["benchmark"] = file_exists($benchmark_file[$hash]) ? array_slice(json_decode(file_get_contents($benchmark_file[$hash]), true), "-" . $bench_last_values . "") : null ;
		
		if(empty($devices[$hash]["raw"]["status"]) || $devices[$hash]["raw"]["status"] == 'h') {
			$count_installed_devices++;
		}
	}
	$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "ARRAY: devices", $devices);
?>
