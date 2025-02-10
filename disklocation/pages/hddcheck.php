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
	
	if(isset($_GET["check_hdd"]) || isset($_POST["check_hdd"])) {
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
	
	if(isset($_GET["check_hdd"]) || isset($_POST["check_hdd"]) || isset($argv)) {
		$i=0;
		
		$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "HDDCHECK", "LSSCSI:" . count($lsscsi_arr) . "");
		
		$smart_output = str_pad("DEVICE", 28) . str_pad("MODEL NAME", 30) . str_pad("SERIAL NUMBER", 20) . str_pad("POWER ON TIME", 17) . str_pad("DIFF", 7) . str_pad("STATUS", 8) . str_pad("RUNTIME", 10) . "\n";
		$smart_log .= str_pad("TIME", 22) . $smart_output;
		print($smart_output);
		
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
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "HDDCHECK", "Scanning: " . $lsscsi_device[$i] . " Node: " . $lsscsi_devicenodesg[$i] . "");
				
				if(!isset($argv) || !in_array("silent", $argv)) {
					$smart_output = "SMART: " . str_pad($lsscsi_devicenodesg[$i], 20) . " ";
					print($smart_output);
					$smart_log .= "[" . date("Y-m-d H:i:s") . "] " . $smart_output;
				}
				
				$smart_cmd[$i] = shell_exec("smartctl -x --json --quietmode=silent " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . ""); // get all SMART data for this device, we grab it ourselves to get all drives also attached to hardware raid cards.
				usleep($smart_exec_delay . 000);
				$smart_cmd_farm[$i] = shell_exec("smartctl -l farm --json --quietmode=silent " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . ""); // get all SMART data for this device, we grab it ourselves to get all drives also attached to hardware raid cards.
				
				$smart_array = json_decode($smart_cmd[$i], true);
				$smart_array_farm = json_decode($smart_cmd_farm[$i], true);
				
				$smart_model_name = ( $smart_array["scsi_model_name"] ? $smart_array["scsi_model_name"] : $smart_array["model_name"] );
				$smart_serial_number = $smart_array["serial_number"];
				
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "HDDCHECK", "SMART", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "|PROTOCOL=" . ( isset($smart_array["device"]["protocol"]) ? $smart_array["device"]["protocol"] : null . ""));
				
				$deviceid[$i] = hash('sha256', $smart_model_name . ( isset($smart_array["serial_number"]) ? $smart_array["serial_number"] : null));
				
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "HDDCHECK_ARRAY", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "=" . ( is_array($smart_array) ? "array" : "empty" ) . "");
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "HDDCHECK_FARM", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "=" . ( is_array($smart_array_farm) ? "array" : "empty" ) . "");
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "HDDCHECK", "CMD: smartctl -l farm --json --quietmode=silent " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" .
				$unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? $lsscsi_devicenodesg[$i] : "" ) . "");
				$smart_lun = "";
				
				$smart_model_family = ( $smart_array["scsi_product"] ? $smart_array["scsi_product"] : ( $smart_array["product"] ?: $smart_array["model_family"] ) );
				
				if(!isset($argv) || !in_array("silent", $argv)) {
					$smart_output = str_pad("" . $smart_model_name . " ", 30);
					print($smart_output);
					$smart_log .= $smart_output;
					$smart_output = str_pad("" . $smart_serial_number . " ", 20);
					print($smart_output);
					$smart_log .= $smart_output;
				}
				
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "HDDCHECK", "#:" . $i . ":" . $deviceid[$i] . "");
				
				$powerontime = $smart_array["power_on_time"]["hours"];
				$powerontime_farm = 0;
				$powerontimespindle_farm = 0;
				$smart_output_h = "";
				$smart_output_msg = "";
				$color_output = "";
				
				if($smart_array_farm["seagate_farm_log"]["supported"] !== false && isset($smart_array_farm["seagate_farm_log"]["page_1_drive_information"]["poh"])) {
					$smart_i = 0;
					$powerontime_farm = $smart_array_farm["seagate_farm_log"]["page_1_drive_information"]["poh"];
					//$powerontimespindle_farm = trim($smart_array_farm["seagate_farm_log"]["page_1_drive_information"]["spoh"]);
					
					if($powerontime_farm == $powerontime) {
						$smart_output_h = str_pad("" . $powerontime . " = " . $powerontime_farm . "", 17);
						$smart_output_diff = str_pad("" . $powerontime_farm-$powerontime . "", 6);
						$smart_output_msg = "PASS";
						$color_output = "#00FF00";
					}
					else if($powerontime_farm-$powerontime <= 1) {
						$smart_output_h = str_pad("" . $powerontime . " < " . $powerontime_farm . "", 17);
						$smart_output_diff = str_pad("" . $powerontime_farm-$powerontime . "", 6);
						$smart_output_msg = "PASS";
						$color_output = "#00FF00";
						$extra_message = "Difference of 1 hour is safe, the timer between SMART and SEAGEATE FARM LOG is slightly different.";
					}
					else if($powerontime_farm-$powerontime <= 1000) {
						$smart_output_h = str_pad("" . $powerontime . " < " . $powerontime_farm . "", 17);
						$smart_output_diff = str_pad("" . $powerontime_farm-$powerontime . "", 6);
						$smart_output_msg = "WARN";
						$color_output = "#FF9900";
						$extra_message = "Found a difference of up to 1000 hours. This is probably OK, but the drive has likely been used.";
					}
					else {
						$smart_output_h = str_pad("" . $powerontime . " < " . $powerontime_farm . "", 17);
						$smart_output_diff = str_pad("" . $powerontime_farm-$powerontime . "", 6);
						$smart_output_msg = "FAIL";
						$color_output = "#FF0000";
						$extra_message = "Found a difference of over 1000 hours. Consider RMA.";
					}
				}
				else {
					$smart_output_h = str_pad(" ", 17);
					$smart_output_diff = str_pad(" ", 6);
					$smart_output_msg = "SKIP";
					$color_output = "";
				}
				
				if(isset($_GET["check_hdd"]) || isset($_POST["check_hdd"])) {
					$smart_output_html = $smart_output_h . "" . $smart_output_diff . " [<span style=\"color: " . $color_output . ";\">" . str_pad($smart_output_msg, 4) . "</span>] ";
					$smart_output_log = $smart_output_h . "" . $smart_output_diff . " [" . str_pad($smart_output_msg, 4) . "] ";
					print($smart_output_html);
				}
				else if(isset($argv)) {
					$smart_output_log = $smart_output_h . "" . $smart_output_diff . " [" . str_pad($smart_output_msg, 4) . "] ";
					print($smart_output_log);
				}
				
				$smart_log .= $smart_output_log;
				$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "HDDCHECK", $smart_output_log);
				
				if(!isset($argv) || !in_array("silent", $argv)) {
					if(!empty($deviceid[$i])) {
						$smart_output = " " . round((hrtime(true)-$time_start_individual)/1e+6) . "ms\n";
						print($smart_output);
						$smart_log .= $smart_output;
					}
					else {
						$smart_output = "\n";
						print($smart_output);
						$smart_log .= $smart_output;
					}
				}
				
				unset($smart_array);
				unset($smart_array_farm);
				
				flush();
			}
			$i++;
		}
		
		if(isset($extra_message)) {
			$smart_log .= $extra_message;
			print("\n" . $extra_message . "\n");
		}
		
		if(isset($_GET["check_hdd"]) || isset($_POST["check_hdd"])) {
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
		file_put_contents(DISKLOCATION_TMP_PATH."/hddcheck.log", $smart_log);
	}
	
	$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "HDDCHECK LOGFILE", DISKLOCATION_TMP_PATH."/hddcheck.log");
?>
