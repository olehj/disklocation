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
	require_once("functions.php");
	
	$smart_log = "";
	
	$time_start = hrtime(true);
	
	$iterations = $bench_iterations;
	$median = $bench_median;
	$force = $bench_force;
	
	if(in_array("auto", $argv) && !$bench_auto_cron) {
		exit;
	}
	
	if(isset($_GET["start"])) {
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
					<b>Benchmarking " . $iterations . " iterations per drive. Please wait until it is completed...</b>
				</h2>
				<pre class=\"mono\" style=\"margin: 0; padding: 0 0 0 0;\">");
		}
	}
	debug_print($debugging_active, __LINE__, "array", "DEVICES:" . count($get_devices ) . "");
	
	$i=0;
	foreach($get_devices as $hash => $data) {
		$time_start_individual = hrtime(true);
		if(empty($data["status"]) || $data["status"] == 'h') {
			
			$devicenode = $data["devicenode"];
			$serial_number = $data["smart_serialnumber"];
			$model_name = $data["model_name"];
			
			$smart_check_operation = shell_exec("smartctl -n standby " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ? "/dev/" . $devicenode : "" ) . " | grep -i 'Device'");
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
			
			if($smart_powermode_status == "ACTIVE" || $smart_powermode_status == "IDLE" || $force || $_GET["force"] || in_array("force", $argv)) {
				$speed_array = array();
				$speed_array_value = 0;
				
				if(!isset($argv) || !in_array("silent", $argv)) {
					$smart_output = "Device: " . str_pad($devicenode, 10) . " " . str_pad($smart_powermode_status, 8) . " ";
					print($smart_output);
					$smart_log .= "[" . date("Y-m-d H:i:s") . "] " . $smart_output;
				}
				flush();
				
				$deviceid[$i] = $hash;
				//$hdparm_cache_cmd = shell_exec("hdparm -T " . "/dev/" . $devicenode . "");
				
				for($dev=0;$dev<$iterations;$dev++) {
					$hdparm_device_cmd = shell_exec("hdparm -t " . "/dev/" . $devicenode . "");
					unset($this_device, $this_results);
					list($nothing, $this_device, $this_results) = preg_split('/\r\n|\r|\n/', $hdparm_device_cmd);
					list($garbage, $speed) = explode("=", $this_results);
					list($garbage, $number, $unit) = explode(" ", $speed);
					$smart_output = str_pad("[" . $dev+1 . "] " . $number . " ", 13);
					print($smart_output);
					$smart_log .= $smart_output;
					flush();
					$speed_array[] = $number;
				}
				
				sort($speed_array, SORT_NUMERIC);
				if($median && $iterations > 2) {
					$speed_array = array_slice($speed_array, 1, -1);
				}
				if(count($speed_array) > 1) {
					$speed_array[0] = array_sum($speed_array)/count($speed_array);
					$speed_array = array_slice($speed_array, 0, 1);
				}
				$speed_array_value = (!empty($speed_array[0]) ? round($speed_array[0], 2) : 0);
				
				$smart_output = str_pad("[AVG] " . $speed_array_value . " " . $unit . " ", 22);
				print($smart_output);
				$smart_log .= $smart_output;
				flush();
				
				if($serial_number && $model_name) {
					$filename_benchmark = UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/benchmark/".str_replace(" ", "_", $model_name)."_" . $serial_number . ".json";
					if(file_exists($filename_benchmark)) {
						$speed_array_contents = config_array($filename_benchmark, 'r');
					}
					$speed_array_contents[date("Y-m-d")] = $speed_array_value;
					
					config_array($filename_benchmark, 'w', $speed_array_contents);
				}
			}
			else if($smart_powermode_status != "UNKNOWN") {
				if(!isset($argv) || !in_array("silent", $argv)) {
					$smart_output = "skipped.\n";
					print($smart_output);
					$smart_log .= $smart_output;
				}
			}
			
			if(!isset($argv) || !in_array("silent", $argv)) {
				if(isset($deviceid[$i])) {
					$smart_output = "[TIME] " . round((hrtime(true)-$time_start_individual)/1e+9, 1) . " seconds.\n";
					print($smart_output);
					$smart_log .= $smart_output;
				}

				if(!isset($argv) || !in_array("force", $argv)) {
					//print("<br />");
				}
			}
			
			$i++;
			
			flush();
		}
	}
	
	if(isset($_GET["start"])) {
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
	
	if($smart_log) {
		file_put_contents(DISKLOCATION_TMP_PATH."/benchmark." . date("Y-m-d") . ".log", $smart_log);
	}

?>
