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
	require_once("default_settings.php");
	
	$smart_exec_delay = 200; // set milliseconds for next execution for SMART shell_exec - needed to actually grab all the information for unassigned devices. Default: 200
	
	$smart_log = "";
	
	$time_start = hrtime(true);
	
	if(in_array("syslogread", $argv)) {
		$file = "/var/log/syslog";
		
		$contents = file_get_contents($file, false, null, -8192); // Get last 8KB
		
		if($contents === false) {
			$contents = file_get_contents($file, false, null);
			if ($contents === false) {
				die(); // ($file . " does not exists.");
			}
		}
		preg_match_all("/([A-Za-z]{3,3}) .([0-9]{1,2}) ([0-9]{2}:[0-9]{2}:[0-9]{2}) [\w]+ emhttpd: read SMART \/dev\/([a-z]{2,})/", $contents, $matches, PREG_SET_ORDER);
		
		$time_now = time();
		
		foreach ($matches as $value) {
			$time_log = $value[1] ." ". $value[2] ." ". $value[3];
			
			$time_log = DateTime::createFromFormat('M j H:i:s', $time_log);
			$time_log_ISO = $time_log->format("Y-m-d H:i:s");
			$time_log_UNIX = strtotime($time_log_ISO);
			
			if(($time_now - $time_log_UNIX) < 290) { // Only care about read devices the last 4:50 (min:sec). Less than 5 minutes cronjob to prevent multiple SMART reads.
				usleep($smart_exec_delay . 000); // delay script to get the output of the next shell_exec()
				
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "SMART READ", "TIME: " . $time_log_ISO . " DEVICE: " . $value[4] . "");
				if(!in_array("silent", $argv)) { print("TIME: " . $time_log_ISO . " DEVICE: " . $value[4] . " "); }
				
				// get all SMART data for this device, we grab it ourselves to get all drives also attached to hardware raid cards.
				$smart_cmd = "smartctl -x --json --quietmode=silent " . $unraid_array[$value[4]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$value[4]]["smart_controller_cmd"] . "") ? "/dev/" . $value[4] : "" ) . "";
				$smart_run = shell_exec($smart_cmd);
				
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "SMART CMD", $smart_cmd);
				
				$smart_array = json_decode($smart_run, true);
				
				$smart_model_name = ( $smart_array["scsi_model_name"] ? $smart_array["scsi_model_name"] : $smart_array["model_name"] );
				
				$deviceid[$i] = hash('sha256', $smart_model_name . ( isset($smart_array["serial_number"]) ? $smart_array["serial_number"] : null));
				
				// store files in /tmp
				if(isset($smart_array["serial_number"]) && $smart_model_name) {
					$filename_smart_data_tmp = DISKLOCATION_TMP_PATH."/smart/".str_replace(" ", "_", $smart_model_name)."_" . $smart_array["serial_number"] . ".json";
					$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "SMART FILE", $filename_smart_data_tmp);
					if(!in_array("silent", $argv)) { print("SMART FILE: " . $filename_smart_data_tmp . "\n"); }
					file_put_contents($filename_smart_data_tmp, $smart_run);
				}
			}
			else {
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "SMART OVERTIME", "TIME: " . $time_log_ISO . " DEVICE: " . $value[4] . "");
				if(!in_array("silent", $argv)) { print("SMART SKIPPED: " . $value[4] . " (exceeded time limit)\n"); }
			}
		}
		
		// grab changes just in case, this will decrease Disk Location plugin loading time drastically.
		$lsblk_json = shell_exec("lsblk -p -o NAME,MOUNTPOINT,SERIAL,PATH --json");
		file_put_contents(DISKLOCATION_LSBLK, $lsblk_json);
		
		if(is_file("/usr/sbin/zpool")) {
			$zpool_status = shell_exec("/usr/sbin/zpool status");
			file_put_contents(DISKLOCATION_ZPOOL, $zpool_status);
		}
		
		exit();
	}
	
	if(isset($_GET["force_smartdb_scan"]) || isset($_GET["force_smart_scan"]) || isset($_GET["active_smart_scan"])) {
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
					body{font-size:1.2rem;color:#f2f2f2;background:#" . $bgcolor_empty . ";padding:0;margin:0;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
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
					<b>Checking devices, please wait until it is completed...</b>
				</h2>
				<pre class=\"mono\" style=\"margin: 0; padding: 0 0 0 0;\">");
		}
	}
	
	$force_scan = 0;
	$force_scan_db = 0;
	
	// add and update disk info
	if(isset($_POST["force_smartdb_scan"]) || isset($_GET["force_smartdb_scan"]) || isset($_POST["force_smart_scan"]) || isset($_GET["force_smart_scan"]) || in_array("install", $argv) || in_array("force", $argv) || in_array("forceall", $argv)) {
		$force_scan = 1; // trigger force_smart_scan post if it is a new install or if it is forced at CLI
	}
	
	if(isset($_POST["force_smartdb_scan"]) || isset($_GET["force_smartdb_scan"]) || in_array("install", $argv) || in_array("forceall", $argv)) {
		$force_scan = 1;
		$force_scan_db = 1; // trigger force_smart_scan post if it is a new install or if it is forced at CLI
		include("system.php");
	}
	
	if($force_scan || $force_scan_db || in_array("start", $argv) || $_GET["active_smart_scan"] || $_POST["active_smart_scan"]) {
		if(!file_exists("/tmp/disklocation/smart")) {
			mkdir("/tmp/disklocation/smart", 0777, true);
		}
		
		// grab changes just in case, this will decrease Disk Location plugin loading time drastically.
		$lsblk_json = shell_exec("lsblk -p -o NAME,MOUNTPOINT,SERIAL,PATH --json");
		file_put_contents(DISKLOCATION_LSBLK, $lsblk_json);
		if(is_file("/usr/sbin/zpool")) {
			$zpool_status = shell_exec("/usr/sbin/zpool status");
			file_put_contents(DISKLOCATION_ZPOOL, $zpool_status);
		}
		
		if($force_scan_db && !in_array("status", $argv)) {
			// wait until the cronjob has finished.
			$retry_delay = 1;
			if(file_exists(DISKLOCATION_LOCK_FILE)) {
				print("Disk Location is performing background tasks, please wait.");
			}
			while(file_exists(DISKLOCATION_LOCK_FILE)) {
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB", "PGREP: Cronjob running, retry: " . $retry_delay . "");
				if(!isset($argv) || !in_array("silent", $argv)) {
					print(".");
				}
				
				flush();
				sleep($retry_delay);
			}
			
			if(!file_exists(DISKLOCATION_LOCK_FILE)) {
				mkdir(dirname(DISKLOCATION_LOCK_FILE), 0755, true);
				touch(DISKLOCATION_LOCK_FILE);
			}
			
			$devices_current = (empty($get_devices) ? array() : $get_devices);
			$locations_current = (empty($get_locations) ? array() : $get_locations);
		}
		
		$i=0;
		
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB", "LSSCSI:" . count($lsscsi_arr) . "");
		
		while($i < count($lsscsi_arr)) {
			usleep($smart_exec_delay . 000); // delay script to get the output of the next shell_exec()
			$time_start_individual = hrtime(true);
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
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB", "Scanning: " . $lsscsi_device[$i] . " Node: " . $lsscsi_devicenodesg[$i] . "");
				
				$smart_check_operation = shell_exec("smartctl -n standby " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . " | grep -i 'Device'");
				$smart_powermode = (isset($smart_check_operation) ? trim($smart_check_operation) : '');
				
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
				
				$powermode[$lsscsi_device[$i]] = $smart_powermode_status;
				
				if(in_array("status", $argv)) {
					$i++;
					continue;
				}
				
				if($smart_powermode_status == "ACTIVE" || $smart_powermode_status == "IDLE" || $force_scan) { // only get SMART data if the disk is spinning, if it is a new install/empty database, or if scan is forced.
					$smart_standby_cmd = "";
					if(!$force_scan) {
						$smart_standby_cmd = "-n standby";
					}
					// --quietmode=silent
					$smart_cmd[$i] = shell_exec("smartctl $smart_standby_cmd -x --json " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . ""); // get all SMART data for this device, we grab it ourselves to get all drives also attached to hardware raid cards.
					
					$smart_array = json_decode($smart_cmd[$i], true);
					
					$smart_model_name = ( $smart_array["scsi_model_name"] ? $smart_array["scsi_model_name"] : $smart_array["model_name"] );
					
					$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB", "SMART", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "|PROTOCOL=" . ( isset($smart_array["device"]["protocol"]) ? $smart_array["device"]["protocol"] : null . ""));
					
					$deviceid[$i] = hash('sha256', $smart_model_name . ( isset($smart_array["serial_number"]) ? $smart_array["serial_number"] : null));
					
					// skip if no device id exists
					if(!empty($deviceid[$i])) {
						// If error messages exists, try to find device in case of a controller failure
						$smart_array_messages = is_array($smart_array["smartctl"]["messages"]) ? $smart_array["smartctl"]["messages"] : null;
						$skip_force_update = 0;
						unset($smart_error_msg);
						if(is_array($smart_array_messages) && $smart_powermode_status != "UNKNOWN" && empty($smart_model_name) && empty($smart_array["serial_number"])) {
							if(empty($smart_array["serial_number"]) && empty($smart_model_name)) {
								for($ierr=0;$ierr<count($smart_array_messages);$ierr++) {
									if($smart_array_messages[$ierr]["severity"] == "error") {
										$smart_error_msg[$ierr] = $smart_array_messages[$ierr]["string"];
									}
								}
								
								$deviceid[$i] = recursive_array_search($lsscsi_devicenode[$i], $get_devices);
								$smart_array["serial_number"] = $get_devices[$deviceid[$i]]["smart_serialnumber"];
								$smart_model_name = $get_devices[$deviceid[$i]]["model_name"];
								$skip_force_update = 1;
								$get_notifications = json_decode(shell_exec("/usr/local/emhttp/webGui/scripts/notify get"), true);
								
								if(!empty($deviceid[$i]) && recursive_array_search("" . $smart_model_name . " " . $smart_array["serial_number"] . "", $get_notifications) === false) { // only send notification if it doesn't exists:
									shell_exec("/usr/local/emhttp/webGui/scripts/notify -e \"Disk Location: " . $lsscsi_devicenode[$i] . "\" -s \"Alert - Device failure\" -d \"" . $smart_model_name . " " . $smart_array["serial_number"] . "\" -i \"alert\" -m \"" . implode(", ", $smart_error_msg) . "\"");
								}
								$smart_output = "SMART: " . str_pad($lsscsi_devicenodesg[$i], 20) . " FAILED   ";
								print($smart_output);
								$smart_log .= $smart_output;
							}
						}
						
						if((!isset($argv) || !in_array("silent", $argv)) && !$skip_force_update) {
							$smart_output = "SMART: " . str_pad($lsscsi_devicenodesg[$i], 20) . " " . str_pad($smart_powermode_status, 8) . " ";
							print($smart_output);
							$smart_log .= "[" . date("Y-m-d H:i:s") . "] " . $smart_output;
						}
						
						// store files in /tmp
						if(isset($smart_array["serial_number"]) && $smart_model_name) {
							$filename_smart_data_tmp = DISKLOCATION_TMP_PATH."/smart/".str_replace(" ", "_", $smart_model_name)."_" . $smart_array["serial_number"] . ".json";
							file_put_contents($filename_smart_data_tmp, $smart_cmd[$i]);
						}
						
						$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "=" . ( is_array($smart_array) ? "array" : "empty" ) . "");
						$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB", "CMD: smartctl $smart_standby_cmd -x --json " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" .
						$unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . "");
						$smart_lun = "";

						if(!isset($argv) || !in_array("silent", $argv)) {
							$smart_output = str_pad("(" . substr($deviceid[$i], -10) . ")", 14);
							print($smart_output);
							$smart_log .= $smart_output;
						}
						if(!empty($unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"])) {
							if(!isset($argv) || !in_array("silent", $argv)) {
								$smart_output = str_pad("CTRL CMD: " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " ", 30);
								print($smart_output);
								$smart_log .= $smart_output;
							}
						}
						
						if($force_scan_db) {
							$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB", "#:" . $i . ":" . $deviceid[$i] . "");
							
							$smart_model_family = ( $smart_array["scsi_product"] ? $smart_array["scsi_product"] : ( $smart_array["product"] ?: $smart_array["model_family"] ) );
							$smart_cache = get_smart_cache("" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . "");
							
							$smart_units_read = $smart_array["nvme_smart_health_information_log"]["data_units_read"];
							$smart_units_written = $smart_array["nvme_smart_health_information_log"]["data_units_written"];
							if(isset($smart_array["ata_smart_attributes"]["table"])) {
								$smart_i = 0;
								while($smart_i < count($smart_array["ata_smart_attributes"]["table"])) {
									if($smart_array["ata_smart_attributes"]["table"][$smart_i]["name"] == "Power_On_Hours") {			// ID 9
										$smart_poweronhours = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"] ?? 0;
									}
									if($smart_array["ata_smart_attributes"]["table"][$smart_i]["name"] == "Load_Cycle_Count") {			// ID 193
										$smart_loadcycle = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"] ?? 0;
									}
									if($smart_array["ata_smart_attributes"]["table"][$smart_i]["name"] == "Total_LBAs_Written") {			// ID 241
										$smart_units_written = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"] ?? null;
									}
									if($smart_array["ata_smart_attributes"]["table"][$smart_i]["name"] == "Total_LBAs_Read") {			// ID 242
										$smart_units_read = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"] ?? null;
									}
									
									$smart_i++;
								}
							}
							
							if(isset($smart_array["device"]["protocol"]) && $smart_array["device"]["protocol"] == "SCSI") {
								$smart_loadcycle = ( is_array($smart_array["accumulated_load_unload_cycles"]) ? $smart_array["accumulated_load_unload_cycles"] : $smart_loadcycle );
							}
							
							$smart_endurance_used = null;
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
							
							$smart_endurance_used = ( isset($smart_array["nvme_smart_health_information_log"]["percentage_used"]) ? 100-$smart_array["nvme_smart_health_information_log"]["percentage_used"] : $smart_endurance_used );
							$devices[$hash]["formatted"]["endurance"] = ( isset($devices[$hash]["raw"]["endurance"]) ? $devices[$hash]["raw"]["endurance"] . "%" : null );
							
							$nvme_temp = array();
							if(isset($smart_array["device"]["type"]) && $smart_array["device"]["type"] == "nvme") {
								$nvme_cmd = shell_exec("nvme id-ctrl $lsscsi_devicenodesg[$i] | grep temp");
								if(!empty($nvme_cmd)) {
									list($wctemp_line, $cctemp_line) = explode("\n", $nvme_cmd);
									list($foo, $wctemp) = explode(":", $wctemp_line);
									list($foo, $cctemp) = explode(":", $cctemp_line);
									$nvme_temp["wctemp"] = (float)round(temperature_conv(trim($wctemp), 'K', 'C'));
									$nvme_temp["cctemp"] = (float)round(temperature_conv(trim($cctemp), 'K', 'C'));
								}
							}
							
							if(isset($smart_array["serial_number"]) && $smart_model_name && !$skip_force_update) {
								$update[$deviceid[$i]] = array( // overwrite selected values
									"device" => ($lsscsi_device[$i] ?? null),
									"devicenode" => ($lsscsi_devicenode[$i] ?? null),
									"manufacturer" => ($smart_array["model_family"] ?? null),
									"model_name" => ( empty($devices_current[$deviceid[$i]]["model_name"]) ? $smart_model_name : $devices_current[$deviceid[$i]]["model_name"] ),
									"smart_serialnumber" => ( empty($devices_current[$deviceid[$i]]["smart_serialnumber"]) ? $smart_array["serial_number"] : $devices_current[$deviceid[$i]]["smart_serialnumber"] ),
									"capacity" => ($smart_array["user_capacity"]["bytes"] ?? null),
									"rotation" => ( empty($smart_array["rotation_rate"]) && recursive_array_search("Solid State Device Statistics", $smart_array) ? -1 : ( isset($smart_array["device"]["type"]) && $smart_array["device"]["type"] == "nvme" ? -2 : $smart_array["rotation_rate"] )),
									"formfactor" => ($smart_array["form_factor"]["name"] ?? null),
									"smart_cache" => ($smart_cache ?? null),
									"logical_block_size" => $smart_array["logical_block_size"],
									"smart_units_read" => $smart_units_read,
									"smart_units_written" => $smart_units_written,
									"nvme_wctemp" => ($nvme_temp["wctemp"] > 0 ? $nvme_temp["wctemp"] : null),
									"nvme_cctemp" => ($nvme_temp["cctemp"] > 0 ? $nvme_temp["cctemp"] : null),
									"endurance" => $smart_endurance_used,
									"loadcycle" => $smart_loadcycle,
									"powerontime" => ( (!empty($smart_poweronhours) && $smart_poweronhours < $smart_array["power_on_time"]["hours"]) ? $smart_poweronhours : $smart_array["power_on_time"]["hours"] ),
									"status" => ( !file_exists(DISKLOCATION_DEVICES) ? 'h' : $devices_current[$deviceid[$i]]["status"] ),
									"errors" => ($smart_error_msg ?? null)
								);
								$devices_updates = array_replace_recursive($devices_current, $update);
								
								$location[$deviceid[$i]] = array();
								if(!array_key_exists($deviceid[$i], $locations_current)) { // if device is new or reappered, erase removed input and reset status
									$location[$deviceid[$i]] = array(
										"status" => 'h',
										"removed" => ''
									);
								}
								$location_update = array_replace_recursive($devices_updates, $location);
								
								$new_device[$deviceid[$i]] = array();
								if(!array_key_exists($deviceid[$i], $devices_current)) { // if device is new and never seen, add installation date:
									$new_device[$deviceid[$i]] = array(
										"installed" => date("Y-m-d")
									);
								}
								$devices = array_replace_recursive($location_update, $new_device);
							}
							else {
								$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB", "#:" . $i . ":Invalid SMART information, skipping.");
							}
						}
					}
					else {
						$powermode[$lsscsi_device[$i]] = "UNKNOWN"; // force UNKNOWN if there's no SMART data.
						$powermode_ignore[] = $lsscsi_device[$i]; // make an ignore list
						
						$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB", "#:" . $i . ":No device ID, skipping.");
					}
				}
				
				if(!isset($argv) || !in_array("silent", $argv)) {
					if(isset($deviceid[$i])) {
						$smart_output = "done in " . round((hrtime(true)-$time_start_individual)/1e+6) . "ms.\n";
						print($smart_output);
						$smart_log .= $smart_output;
					}
					else {
						$smart_output = "skipped.\n";
						//print($smart_output);
						$smart_log .= $smart_output;
					}
					
					if(!isset($argv) || !in_array("start", $argv) && !in_array("force", $argv)) {
						//print("<br />");
					}
				}
				
				unset($smart_array);
				
				flush();
			}
			$i++;
		}
		
		if(is_array($powermode) && !empty($powermode)) {
			config_array(POWERMODE_FILE, 'w', $powermode);
			if(isset($powermode_ignore)) {
				config_array(POWERMODE_IGNORE_FILE, 'w', $powermode_ignore);
			}
		}
		
		if($force_scan_db) {
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB", "#:" . $devices . "");
			
			if(!isset($argv) || !in_array("silent", $argv)) { 
				$smart_output = "\nWriting to the database... ";
				print($smart_output);
				$smart_log .= $smart_output;
				flush();
			}
			
			if(!config_array(DISKLOCATION_DEVICES, 'w', $devices)) {
				$smart_error = "Could not save file " . DISKLOCATION_DEVICES . "";
				print($smart_error);
				$smart_log .= $smart_error;
			}
			else {
				find_and_set_removed_devices_status($devices, $locations_current, $deviceid);
				$smart_output = ( (!in_array("silent", $argv)) ? "done in " . round((hrtime(true)-$time_start)/1e+9, 1) . " seconds.\n" : null );
				print($smart_output);
				$smart_log .= $smart_output;
			}
		}
		
		if(isset($_GET["force_smartdb_scan"]) || $_GET["force_smart_scan"] || isset($_GET["active_smart_scan"])) {
			if(!isset($argv) || !in_array("silent", $argv)) {
				print("</pre>
					<h3>
						<b>Completed after " . round((hrtime(true)-$time_start)/1e+9, 1) . " seconds.</b>
					</h3>
					<p style=\"text-align: center;\">
						<button type=\"button\" onclick=\"window.top.location = '" . DISKLOCATIONCONF_URL . "';\">Close</button>
						<!-- onclick=\"top.Shadowbox.close()\" -->
					</p>
				");
			}
		}
		
	}
	
	if($smart_log) {
		file_put_contents(DISKLOCATION_TMP_PATH."/cron_smart.log", $smart_log);
	}
	
	$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "CRONJOB LOGFILE", DISKLOCATION_TMP_PATH."/cron_smart.log");
	
	if(file_exists(DISKLOCATION_LOCK_FILE)) {
		unlink(DISKLOCATION_LOCK_FILE);
	}
?>
