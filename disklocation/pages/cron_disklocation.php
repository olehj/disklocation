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
	require_once("functions.php");
	
	if(in_array("install", $argv)) {
		if(file_exists(DISKLOCATION_CONF)) {
			$config_json = file_get_contents(DISKLOCATION_CONF);
			$config_json = json_decode($config_json, true);
			if($config_json["database_noscan"] == 1) {
				die("Scanning devices during installation and boot is disabled.\n");
			}
		}
	}
	
	if(isset($_GET["force_smart_scan"])) {
		$time_start = time();
		if(!isset($argv) || !in_array("silent", $argv)) {
			print("
				<!DOCTYPE HTML>
				<html>
				<head>
				<meta name=\"robots\" content=\"noindex, nofollow\">
				<style>
					@font-face{
					font-family:'clear-sans';font-weight:normal;font-style:normal;
					src:url('/webGui/styles/clear-sans.eot');src:url('/webGui/styles/clear-sans.eot?#iefix') format('embedded-opentype'),url('/webGui/styles/clear-sans.woff') format('woff'),url('/webGui/styles/clear-sans.ttf') format('truetype'),url('/webGui/styles/clear-sans.svg#clear-sans') format('svg');
					}
					@font-face{
					font-family:'clear-sans';font-weight:bold;font-style:normal;
					src:url('/webGui/styles/clear-sans-bold.eot');src:url('/webGui/styles/clear-sans-bold.eot?#iefix') format('embedded-opentype'),url('/webGui/styles/clear-sans-bold.woff') format('woff'),url('/webGui/styles/clear-sans-bold.ttf') format('truetype'),url('/webGui/styles/clear-sans-bold.svg#clear-sans-bold') format('svg');
					}
					@font-face{
					font-family:'clear-sans';font-weight:normal;font-style:italic;
					src:url('/webGui/styles/clear-sans-italic.eot');src:url('/webGui/styles/clear-sans-italic.eot?#iefix') format('embedded-opentype'),url('/webGui/styles/clear-sans-italic.woff') format('woff'),url('/webGui/styles/clear-sans-italic.ttf') format('truetype'),url('/webGui/styles/clear-sans-italic.svg#clear-sans-italic') format('svg');
					}
					@font-face{
					font-family:'clear-sans';font-weight:bold;font-style:italic;
					src:url('/webGui/styles/clear-sans-bold-italic.eot');src:url('/webGui/styles/clear-sans-bold-italic.eot?#iefix') format('embedded-opentype'),url('/webGui/styles/clear-sans-bold-italic.woff') format('woff'),url('/webGui/styles/clear-sans-bold-italic.ttf') format('truetype'),url('/webGui/styles/clear-sans-bold-italic.svg#clear-sans-bold-italic') format('svg');
					}
					@font-face{
					font-family:'bitstream';font-weight:normal;font-style:normal;
					src:url('/webGui/styles/bitstream.eot');src:url('/webGui/styles/bitstream.eot?#iefix') format('embedded-opentype'),url('/webGui/styles/bitstream.woff') format('woff'),url('/webGui/styles/bitstream.ttf') format('truetype'),url('/webGui/styles/bitstream.svg#bitstream') format('svg');
					}
					html{font-family:clear-sans;font-size:62.5%;height:100%}
					body{font-size:1.2rem;color:#1c1c1c;background:#f2f2f2;padding:0;margin:0;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
					.mono {font: small 'lucida console', Monaco, monospace;}
					input[type=button],input[type=reset],input[type=submit],button,button[type=button],a.button { 
						font-family:clear-sans;font-size:1.1rem;font-weight:bold;letter-spacing:2px;text-transform:uppercase;margin:10px 12px 10px 0;padding:9px 18px;text-decoration:none;white-space:nowrap;cursor:pointer;outline:none;border-radius:4px;border:0;color:#ff8c2f;background:-webkit-gradient(linear,left top,right top,from(#e22828),to(#ff8c2f)) 0 0 no-repeat,-webkit-gradient(linear,left top,right top,from(#e22828),to(#ff8c2f)) 0 100% no-repeat,-webkit-gradient(linear,left bottom,left top,from(#e22828),to(#e22828)) 0 100% no-repeat,-webkit-gradient(linear,left bottom,left top,from(#ff8c2f),to(#ff8c2f)) 100% 100% no-repeat;background:linear-gradient(90deg,#e22828 0,#ff8c2f) 0 0 no-repeat,linear-gradient(90deg,#e22828 0,#ff8c2f) 0 100% no-repeat,linear-gradient(0deg,#e22828 0,#e22828) 0 100% no-repeat,linear-gradient(0deg,#ff8c2f 0,#ff8c2f) 100% 100% no-repeat;background-size:100% 2px,100% 2px,2px 100%,2px 100%
					}
					input:hover[type=button],input:hover[type=reset],input:hover[type=submit],button:hover,button:hover[type=button],a.button:hover { 
						color:#f2f2f2;background:-webkit-gradient(linear,left top,right top,from(#e22828),to(#ff8c2f));background:linear-gradient(90deg,#e22828 0,#ff8c2f)
					}
					input[type=button][disabled],input[type=reset][disabled],input[type=submit][disabled],button[disabled],button[type=button][disabled],a.button[disabled]
					input:hover[type=button][disabled],input:hover[type=reset][disabled],input:hover[type=submit][disabled],button:hover[disabled],button:hover[type=button][disabled],a.button:hover[disabled]
					input:active[type=button][disabled],input:active[type=reset][disabled],input:active[type=submit][disabled],button:active[disabled],button:active[type=button][disabled],a.button:active[disabled] {
						cursor:default;color:#808080;background:-webkit-gradient(linear,left top,right top,from(#404040),to(#808080)) 0 0 no-repeat,-webkit-gradient(linear,left top,right top,from(#404040),to(#808080)) 0 100% no-repeat,-webkit-gradient(linear,left bottom,left top,from(#404040),to(#404040)) 0 100% no-repeat,-webkit-gradient(linear,left bottom,left top,from(#808080),to(#808080)) 100% 100% no-repeat;background:linear-gradient(90deg,#404040 0,#808080) 0 0 no-repeat,linear-gradient(90deg,#404040 0,#808080) 0 100% no-repeat,linear-gradient(0deg,#404040 0,#404040) 0 100% no-repeat,linear-gradient(0deg,#808080 0,#808080) 100% 100% no-repeat;background-size:100% 2px,100% 2px,2px 100%,2px 100%
					}
				</style>
				<h2>
					<b>Scanning drives, please wait until it is completed...</b>
				</h2>
				<p class=\"mono\">
			");
		}
	}
	
	$force_scan = 0;
	
	// add and update disk info
	if(isset($_POST["force_smart_scan"]) || isset($_GET["force_smart_scan"]) || isset($_GET["crontab"]) || $disklocation_new_install || in_array("force", $argv)) {
		$force_scan = 1; // trigger force_smart_scan post if it is a new install or if it is forced at CLI
	}
	
	/*
	if(isset($_GET["force_smart_scan"]) || isset($_POST["force_smart_scan"])) {
		$debugging_active = 3;
	}
	*/
	
	if($force_scan || in_array("cronjob", $argv)) {
		// wait until the cronjob has finished.
		while(shell_exec("pgrep -f disklocation.sh")) {
			$pid_cron_script = ( shell_exec("pgrep -f disklocation.sh") ? trim(shell_exec("pgrep -f disklocation.sh")) : 0 );
			$retry_delay = 5;
			debug_print($debugging_active, __LINE__, "delay", "PGREP: Cronjob (PID:$pid_cron_script) running, retrying every $retry_delay secs...");
			if(!isset($argv) || !in_array("silent", $argv)) {
				print("PGREP: Cronjob (PID:$pid_cron_script) running, retrying every $retry_delay secs...\n");
			}
			sleep($retry_delay);
		}
		
		$i=0;
		
		debug_print($debugging_active, __LINE__, "array", "LSSCSI:" . count($lsscsi_arr) . "");
		while($i < count($lsscsi_arr)) {
			$lsscsi_parser_array = lsscsi_parser($lsscsi_arr[$i]);
			
			$lsscsi_device[$i] = $lsscsi_parser_array["device"];					// get the device address: "1:0:0:0"
			//$lsscsi_type[$i] = $lsscsi_parser_array["type"];					// get the type: "disk" / "process" (not in use for this script)
			//$lsscsi_luname[$i] = $lsscsi_parser_array["luname"];					// get the logical unit name of the drive
			if($lsscsi_parser_array["devnode"]) {
				$lsscsi_devicenode[$i] = str_replace("/dev/", "", $lsscsi_parser_array["devnode"]);	// get only the node name: "sda"
			}
			else {
				$lsscsi_devicenode[$i] = str_replace("/dev/", "", $lsscsi_parser_array["sgnode"]);	// if no node name available, use sgnode instead (for nvme drives).
			}
			$lsscsi_devicenodesg[$i] = $lsscsi_parser_array["sgnode"];				// get the full path to SCSI Generic device node: "/dev/sg1|/dev/nvme*"
			
			if($lsscsi_device[$i] && $lsscsi_devicenodesg[$i]) {
				//debug_print($debugging_active, __LINE__, "loop", "Scanning " . $lsscsi_type[$i] . ": " . $lsscsi_device[$i] . " LUN: " . $lsscsi_luname[$i] . " Node: " . $lsscsi_devicenodesg[$i] . "");
				debug_print($debugging_active, __LINE__, "loop", "Scanning: " . $lsscsi_device[$i] . " Node: " . $lsscsi_devicenodesg[$i] . "");
				//$smart_check_operation = shell_exec("smartctl -n standby $lsscsi_devicenodesg[$i] | egrep 'ACTIVE|IDLE|NVMe'");
				$smart_cache = get_smart_cache("" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . "");
				
				$smart_check_operation = shell_exec("smartctl -n standby " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . " | egrep 'ACTIVE|IDLE|NVMe'");
				
				$smart_powermode_shell = shell_exec("smartctl -n standby " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . " | grep -i 'Device'");
				$smart_powermode = (isset($smart_powermode_shell) ? trim($smart_powermode_shell) : '');
				
				switch(true) {
					case strstr($smart_powermode, "ACTIVE"):
						$smart_powermode_status = "ACTIVE";
						break;
					case strstr($smart_powermode, "IDLE"):
						$smart_powermode_status = "IDLE";
						break;
					case strstr($smart_powermode, "STANDBY"):
						$smart_powermode_status = "STANDBY";
						break;
					case strstr($smart_powermode, "NVMe"):
						$smart_powermode_status = "ACTIVE";
						break;
					default:
						$smart_powermode_status = "UNKNOWN";
				}
				
				config("/tmp/disklocation/powermode.ini", 'w', $lsscsi_device[$i], $smart_powermode_status);
				
				usleep($smart_exec_delay . 000); // delay script to get the output of the next shell_exec()
				
				if(in_array("status", $argv)) {
					$i++;
					continue;
				}
				
				if(!isset($argv) || !in_array("silent", $argv)) {
					print("SMART: " . $lsscsi_devicenodesg[$i] . " ");
				}
				
				if(!empty($smart_check_operation) || $force_scan) { // only get SMART data if the disk is spinning, if it is a new install/empty database, or if scan is forced.
					$smart_standby_cmd = "";
					if(!$force_scan) {
						$smart_standby_cmd = "-n standby";
					}
					$smart_cmd[$i] = shell_exec("smartctl $smart_standby_cmd -x --json --quietmode=silent " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . ""); // get all SMART data for this device, we grab it ourselves to get all drives also attached to hardware raid cards.
					$smart_array = json_decode($smart_cmd[$i], true);
					debug_print($debugging_active, __LINE__, "SMART", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "=" . ( is_array($smart_array) ? "array" : "empty" ) . "");
					//debug_print($debugging_active, __LINE__, "SMART", "CMD: " . $smart_cmd[$i] . "");
					debug_print($debugging_active, __LINE__, "SMART", "CMD: smartctl $smart_standby_cmd -x --json --quietmode=silent " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . "");
					$smart_lun = "";
					if(isset($smart_array["device"]["protocol"]) && $smart_array["device"]["protocol"] == "SCSI") {
						$smart_lun = ( $smart_array["logical_unit_id"] ? $smart_array["logical_unit_id"] : null );
						$smart_model_family = ( $smart_array["scsi_product"] ? $smart_array["scsi_product"] : $smart_array["product"] );
						$smart_model_name = ( $smart_array["scsi_model_name"] ? $smart_array["scsi_model_name"] : $smart_array["model_name"] );
						
						if(is_array($smart_array["accumulated_load_unload_cycles"])) {
							$smart_loadcycle_find = $smart_array["accumulated_load_unload_cycles"];
						}
						
						debug_print($debugging_active, __LINE__, "SMART", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "|PROTOCOL=SCSI");
					}
					else {
						if(isset($smart_array["wwn"])) {
							$smart_lun = "" . ($smart_array["wwn"]["naa"] ?? null) . " " . ($smart_array["wwn"]["oui"] ?? null) . " " . ($smart_array["wwn"]["id"] ?? null) . "";
						}
						$smart_model_family = $smart_array["model_family"] ?? null;
						$smart_model_name = $smart_array["model_name"] ?? null;
						
						$smart_i=0;
						$smart_loadcycle_find = "";
						$smart_reallocated_sector_count = "";
						$smart_reported_uncorrectable_errors = "";
						$smart_command_timeout = "";
						$smart_current_pending_sector_count = "";
						$smart_offline_uncorrectable = "";
						$smart_units_written = "";
						$smart_units_read = "";
						
						if(isset($smart_array["ata_smart_attributes"]["table"])) {
							while($smart_i < count($smart_array["ata_smart_attributes"]["table"])) {
								if($smart_array["ata_smart_attributes"]["table"][$smart_i]["name"] == "Load_Cycle_Count") {
									$smart_loadcycle_find = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
									//$smart_i = count($smart_array["ata_smart_attributes"]["table"]);
								}
								if($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"] == 5) {
									$smart_reallocated_sector_count = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
									//$smart_i = count($smart_array["ata_smart_attributes"]["table"]);
								}
								if($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"] == 187) {
									$smart_reported_uncorrectable_errors = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
									//$smart_i = count($smart_array["ata_smart_attributes"]["table"]);
								}
								if($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"] == 188) {
									$smart_command_timeout = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
									//$smart_i = count($smart_array["ata_smart_attributes"]["table"]);
								}
								if($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"] == 197) {
									$smart_current_pending_sector_count = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
									//$smart_i = count($smart_array["ata_smart_attributes"]["table"]);
								}
								if($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"] == 198) {
									$smart_offline_uncorrectable = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
									//$smart_i = count($smart_array["ata_smart_attributes"]["table"]);
								}
								if($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"] == 241) {
									$smart_units_written = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
									//$smart_i = count($smart_array["ata_smart_attributes"]["table"]);
								}
								if($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"] == 242) {
									$smart_units_read = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
									//$smart_i = count($smart_array["ata_smart_attributes"]["table"]);
								}
								$smart_i++;
							}
						}
						debug_print($debugging_active, __LINE__, "SMART", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "|PROTOCOL=" . ( isset($smart_array["device"]["protocol"]) ? $smart_array["device"]["protocol"] : null . ""));
					}
					
					// Only check for SSD if rotation_rate doesn't exists.
					if(!isset($smart_array["rotation_rate"])) {
						$smart_array["rotation_rate"] = ( recursive_array_search("Solid State Device Statistics", $smart_array) ? -1 : null );
						if(isset($smart_array["device"]["type"]) && $smart_array["device"]["type"] == "nvme") {
							$smart_array["rotation_rate"] = -2;
						}
					}
					$deviceid[$i] = hash('sha256', $smart_model_name . ( isset($smart_array["serial_number"]) ? $smart_array["serial_number"] : null));
					
					if(!isset($argv) || !in_array("silent", $argv)) {
						print("(" . $deviceid[$i] . ") ");
					}
					if(!empty($unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"])) {
						if(!isset($argv) || !in_array("silent", $argv)) {
							print("CTRL CMD: " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " ");
						}
					}
					
					if(!$smart_units_read && !$smart_units_written && $smart_array["rotation_rate"] == -2) {
						$smart_units_read = $smart_array["nvme_smart_health_information_log"]["data_units_read"];
						$smart_units_written = $smart_array["nvme_smart_health_information_log"]["data_units_written"];
					}
					
					debug_print($debugging_active, __LINE__, "HASH", "#:" . $i . ":" . $deviceid[$i] . "");
					
					find_and_unset_reinserted_devices_status($db, $deviceid[$i]);	// tags old existing devices with 'null', delete device from location just in case it for whatever reason it already exists.
					
					if(isset($smart_array["serial_number"]) && $smart_model_name) {
						$sql = "
							INSERT INTO
								disks(
									device,
									devicenode,
									luname,
									model_family,
									model_name,
									smart_status,
									smart_serialnumber,
									smart_temperature,
									smart_powerontime,
									smart_loadcycle,
									smart_capacity,
									smart_cache,
									smart_rotation,
									smart_formfactor,
									smart_reallocated_sector_count,
									smart_reported_uncorrectable_errors,
									smart_command_timeout,
									smart_current_pending_sector_count,
									smart_offline_uncorrectable,
									smart_logical_block_size,
									smart_nvme_available_spare,
									smart_nvme_available_spare_threshold,
									smart_nvme_percentage_used,
									smart_units_read,
									smart_units_written,
									status,
									hash
								)
								VALUES(
									'" . ($lsscsi_device[$i] ?? null) . "',
									'" . ($lsscsi_devicenode[$i] ?? null) . "',
									'" . ($smart_lun ?? null) . "',
									'" . ($smart_model_family ?? null) . "',
									'" . ($smart_model_name ?? null) . "',
									'" . ($smart_array["smart_status"]["passed"] ?? null) . "',
									'" . ($smart_array["serial_number"] ?? null) . "',
									'" . ($smart_array["temperature"]["current"] ?? null) . "',
									'" . ($smart_array["power_on_time"]["hours"] ?? null) . "',
									'" . ($smart_loadcycle_find ?? null) . "',
									'" . ($smart_array["user_capacity"]["bytes"] ?? null) . "',
									'" . ($smart_cache ?? null) . "',
									'" . ($smart_array["rotation_rate"] ?? null) . "',
									'" . ($smart_array["form_factor"]["name"] ?? null) . "',
									'" . ($smart_reallocated_sector_count ?? null) . "',
									'" . ($smart_reported_uncorrectable_errors ?? null) . "',
									'" . ($smart_command_timeout ?? null) . "',
									'" . ($smart_current_pending_sector_count ?? null) . "',
									'" . ($smart_offline_uncorrectable ?? null) . "',
									'" . ($smart_array["logical_block_size"] ?? null) . "',
									'" . ($smart_array["nvme_smart_health_information_log"]["available_spare"] ?? null) . "',
									'" . ($smart_array["nvme_smart_health_information_log"]["available_spare_threshold"] ?? null) . "',
									'" . ($smart_array["nvme_smart_health_information_log"]["percentage_used"] ?? null) . "',
									'" . ($smart_units_read ?? null) . "',
									'" . ($smart_units_written ?? null) . "',
									'h',
									'" . ($deviceid[$i] ?? null) . "'
								)
								ON CONFLICT(hash) DO UPDATE SET
									device='" . ($lsscsi_device[$i] ?? null) . "',
									devicenode='" . ($lsscsi_devicenode[$i] ?? null) . "',
									luname='" . ($smart_lun ?? null) . "',
									model_family='" . ($smart_model_family ?? null) . "',
									smart_status='" . ($smart_array["smart_status"]["passed"] ?? null) . "',
									smart_temperature='" . ($smart_array["temperature"]["current"] ?? null) . "',
									smart_powerontime='" . ($smart_array["power_on_time"]["hours"] ?? null) . "',
									smart_loadcycle='" . ($smart_loadcycle_find ?? null) . "',
									smart_capacity='" . ($smart_array["user_capacity"]["bytes"] ?? null) . "',
									smart_cache='" . ($smart_cache ?? null) . "',
									smart_rotation='" . ($smart_array["rotation_rate"] ?? null) . "',
									smart_formfactor='" . ($smart_array["form_factor"]["name"] ?? null) . "',
									smart_reallocated_sector_count='" . ($smart_reallocated_sector_count ?? null) . "',
									smart_reported_uncorrectable_errors='" . ($smart_reported_uncorrectable_errors ?? null) . "',
									smart_command_timeout='" . ($smart_command_timeout ?? null) . "',
									smart_current_pending_sector_count='" . ($smart_current_pending_sector_count ?? null) . "',
									smart_offline_uncorrectable='" . ($smart_offline_uncorrectable ?? null) . "',
									smart_logical_block_size='" . ($smart_array["logical_block_size"] ?? null) . "',
									smart_nvme_available_spare='" . ($smart_array["nvme_smart_health_information_log"]["available_spare"] ?? null) . "',
									smart_nvme_available_spare_threshold='" . ($smart_array["nvme_smart_health_information_log"]["available_spare_threshold"] ?? null) . "',
									smart_nvme_percentage_used='" . ($smart_array["nvme_smart_health_information_log"]["percentage_used"] ?? null) . "',
									smart_units_read='" . ($smart_units_read ?? null) . "',
									smart_units_written='" . ($smart_units_written ?? null) . "'
									
								WHERE hash='" . $deviceid[$i] . "'
								;
						";
						
						if(is_array($unraid_disklog["" . str_replace(" ", "_", $smart_model_name) . "_" . str_replace(" ", "_", $smart_array["serial_number"]) . ""])) {
							$sql .= "
								UPDATE disks SET
									purchased='" . $unraid_disklog["" . str_replace(" ", "_", $smart_model_name) . "_" . str_replace(" ", "_", $smart_array["serial_number"]) . ""]["purchase"] . "',
									warranty='" . $unraid_disklog["" . str_replace(" ", "_", $smart_model_name) . "_" . str_replace(" ", "_", $smart_array["serial_number"]) . ""]["warranty"] . "',
									manufactured='" . $unraid_disklog["" . str_replace(" ", "_", $smart_model_name) . "_" . str_replace(" ", "_", $smart_array["serial_number"]) . ""]["date"] . "'
								WHERE hash = '" . $deviceid[$i] . "'
							";
						}
						
						debug_print($debugging_active, __LINE__, "SQL", "#:" . $i . ":<pre>" . $sql . "</pre>");
						//print("#:" . $i . ":<pre>" . $sql . "</pre>");
						
						$ret = $db->exec($sql);
						if(!$ret) {
							echo $db->lastErrorMsg();
						}
					}
					else {
						debug_print($debugging_active, __LINE__, "SQL", "#:" . $i . ":<pre>Invalid SMART information, skipping...</pre>");
					}
				}
				
				if(!isset($argv) || !in_array("silent", $argv)) {
					isset($deviceid[$i]) ? print("done.\n") : print("skipped.\n");
					
					if(!isset($argv) || !in_array("cronjob", $argv) && !in_array("force", $argv)) {
						print("<br />");
					}
				}
				
				unset($smart_array);
				
				flush();
			}
			$i++;
		}
		// check the existence of devices, must be run during force smart scan.
		if($force_scan) {
			find_and_set_removed_devices_status($db, $deviceid); 		// tags removed devices 'r', delete device from location
		}
	}
	if(isset($_GET["force_smart_scan"]) || isset($_POST["force_smart_scan"])) {
		$time_end = time();
		$total_exec_time = $time_end - $time_start;
		
		if(!isset($argv) || !in_array("silent", $argv)) {
			print("
				</p>
				<h2>
					<b>Completed after $total_exec_time seconds.</b>
				</h2>
				<p style=\"text-align: center;\">
					<button type=\"button\" onclick=\"window.top.location = '" . DISKLOCATIONCONF_URL . "';\">Done</button>
					<!-- onclick=\"top.Shadowbox.close()\" -->
				</p>
			");
		}
	}
	
	cronjob_runfile_updater();
	
	$db->close();
?>
