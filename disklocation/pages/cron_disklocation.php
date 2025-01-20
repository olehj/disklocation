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
	
	$smart_log = "";
	
	$time_start = hrtime(true);
	
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
					<b>Checking devices, please wait until it is completed...</b>
				</h2>
				<pre class=\"mono\" style=\"margin: 0; padding: 0 0 0 0;\">");
		}
	}
	
	$force_scan = 0;
	$force_scan_db = 0;
	
	// add and update disk info
	if(isset($_POST["force_smartdb_scan"]) || isset($_GET["force_smartdb_scan"]) || isset($_POST["force_smart_scan"]) || isset($_GET["force_smart_scan"]) || isset($_GET["crontab"]) || in_array("install", $argv) || in_array("force", $argv) || in_array("forceall", $argv)) {
		$force_scan = 1; // trigger force_smart_scan post if it is a new install or if it is forced at CLI
	}
	
	if(isset($_POST["force_smartdb_scan"]) || isset($_GET["force_smartdb_scan"]) || in_array("install", $argv) || in_array("forceall", $argv)) {
		$force_scan_db = 1; // trigger force_smart_scan post if it is a new install or if it is forced at CLI
		$zfs_check = 0;
		if(zfs_check()) {
			$zfs_parser = zfs_parser();
			$lsblk_array = json_decode(shell_exec("lsblk -p -o NAME,MOUNTPOINT,SERIAL,PATH --json"), true);
			$zfs_check = 1;
		}
	}
	
	if($force_scan || in_array("cronjob", $argv) || $_GET["active_smart_scan"] || $_POST["active_smart_scan"]) {
		if(!file_exists("/tmp/disklocation/smart")) {
			mkdir("/tmp/disklocation/smart");
		}
		
		$devices_current = $get_devices;
		$locations_current = $get_locations;
		
		if($force_scan_db && !in_array("status", $argv)) {
			// wait until the cronjob has finished.
			$retry_delay = 1;
			if(file_exists(DISKLOCATION_LOCK_FILE)) {
				print("Disk Location is performing background tasks, please wait.");
			}
			while(file_exists(DISKLOCATION_LOCK_FILE)) {
				debug_print($debugging_active, __LINE__, "delay", "PGREP: Cronjob running, retry: $retry_delay");
				if(!isset($argv) || !in_array("silent", $argv)) {
					print(".");
				}
				
				flush();
				sleep($retry_delay);
			}
			
			if(!file_exists(DISKLOCATION_LOCK_FILE)) {
				mkdir(dirname(DISKLOCATION_LOCK_FILE), 0755, true);
				touch(DISKLOCATION_LOCK_FILE);
				print("\n");
			}
		}
		
		$i=0;
		
		debug_print($debugging_active, __LINE__, "array", "LSSCSI:" . count($lsscsi_arr) . "");
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
				debug_print($debugging_active, __LINE__, "loop", "Scanning: " . $lsscsi_device[$i] . " Node: " . $lsscsi_devicenodesg[$i] . "");
				
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
				
				config(DISKLOCATION_TMP_PATH."/powermode.ini", 'w', $lsscsi_device[$i], $smart_powermode_status);
				
				if(in_array("status", $argv)) {
					$i++;
					continue;
				}
				
				if(!isset($argv) || !in_array("silent", $argv)) {
					$smart_output = "SMART: " . str_pad($lsscsi_devicenodesg[$i], 20) . " " . str_pad($smart_powermode_status, 8) . " ";
					print($smart_output);
					$smart_log .= "[" . date("Y-m-d H:i:s") . "] " . $smart_output;
				}
				
				if($smart_powermode_status == "ACTIVE" || $smart_powermode_status == "IDLE" || $force_scan) { // only get SMART data if the disk is spinning, if it is a new install/empty database, or if scan is forced.
					$smart_standby_cmd = "";
					if(!$force_scan) {
						$smart_standby_cmd = "-n standby";
					}
					
					$smart_cmd[$i] = shell_exec("smartctl $smart_standby_cmd -x --json --quietmode=silent " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . ""); // get all SMART data for this device, we grab it ourselves to get all drives also attached to hardware raid cards.
					
					$smart_array = json_decode($smart_cmd[$i], true);
					
					$smart_model_name = ( $smart_array["scsi_model_name"] ? $smart_array["scsi_model_name"] : $smart_array["model_name"] );
					
					debug_print($debugging_active, __LINE__, "SMART", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "|PROTOCOL=" . ( isset($smart_array["device"]["protocol"]) ? $smart_array["device"]["protocol"] : null . ""));
					
					$deviceid[$i] = hash('sha256', $smart_model_name . ( isset($smart_array["serial_number"]) ? $smart_array["serial_number"] : null));
					
					// store files in /tmp
					if(isset($smart_array["serial_number"]) && $smart_model_name) {
						$filename_smart_data_tmp = DISKLOCATION_TMP_PATH."/smart/".str_replace(" ", "_", $smart_model_name)."_" . $smart_array["serial_number"] . ".json";
						file_put_contents($filename_smart_data_tmp, $smart_cmd[$i]);
					}
					
					debug_print($debugging_active, __LINE__, "SMART", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "=" . ( is_array($smart_array) ? "array" : "empty" ) . "");
					debug_print($debugging_active, __LINE__, "SMART", "CMD: smartctl $smart_standby_cmd -x --json --quietmode=silent " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . "");
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
						debug_print($debugging_active, __LINE__, "HASH", "#:" . $i . ":" . $deviceid[$i] . "");
						
						$smart_model_family = ( $smart_array["scsi_product"] ? $smart_array["scsi_product"] : ( $smart_array["product"] ?: $smart_array["model_family"] ) );
						$smart_cache = get_smart_cache("" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . "");
						
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
								
								if(in_array($smart_array["ata_smart_attributes"]["table"][$smart_i]["id"], $smart_array)) {
									if($smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"] > 0) {
										$smart_errors[$smart_i]["name"] = "" . str_replace("_", " ", $smart_array["ata_smart_attributes"]["table"][$smart_i]["name"]) . "";
										$smart_errors[$smart_i]["value"] = "" . $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"] . "";
									}
								}
								$smart_i++;
							}
						}
						
						if(isset($smart_array["serial_number"]) && $smart_model_name) {
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
								"loadcycle" => $smart_loadcycle,
								"powerontime" => $smart_array["power_on_time"]["hours"],
								"status" => ( !file_exists(DISKLOCATION_DEVICES) ? 'h' : $devices_current[$deviceid[$i]]["status"] )
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
							debug_print($debugging_active, __LINE__, "DB", "#:" . $i . ":<pre>Invalid SMART information, skipping...</pre>");
						}
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
						print($smart_output);
						$smart_log .= $smart_output;
					}
					
					if(!isset($argv) || !in_array("cronjob", $argv) && !in_array("force", $argv)) {
						//print("<br />");
					}
				}
				
				unset($smart_array);
				
				flush();
			}
			$i++;
		}
		
		if($force_scan_db) {
			debug_print($debugging_active, __LINE__, "DB", "#:<pre>" . $devices . "</pre>");
			
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
	
	if(file_exists(DISKLOCATION_LOCK_FILE)) {
		unlink(DISKLOCATION_LOCK_FILE);
	}
?>
