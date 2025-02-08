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
	
	if(!strstr($_SERVER["SCRIPT_NAME"], "page_system.php")) {
		require_once("variables.php");
		include("load_settings.php");
	}
	
	function debug($output, $file, $line, $program, $input = '') { // $output = 0: off | 1: write logfile | 2: return log | 3: write logfile & return log
		if($output) {
			if($output && $line && $program && $input) {
				$log = "[" . date("H:i:s") . "] " . $file . ":" . $line . " @ " . $program . ": " . ( is_array($input) ? print_r($input, true) : $input ) . "\n";
			}
			
			if($output == 1 || $output == 3) {
				file_put_contents(DISKLOCATION_TMP_PATH . "/disklocation.log", $log, FILE_APPEND);
				if($output == 2) {
					return true;
				}
			}
			
			if($output == 2 || $output == 3) {
				return $log;
			}
		}
		else {
			return false;
		}
	}
	
	function config($file, $operation, $key = '', $val = '') { // file, [r]ead/[w]rite, key (req. write), value (req. write)
		if($operation == 'w') {
			if(!file_exists($file)) {
				mkdir(dirname($file), 0755, true);
				touch($file);
			}
			$config_json = file_get_contents($file);
			$config_json = json_decode($config_json, true);
			$config_json[$key] = $val;
			$config_json = json_encode($config_json, JSON_PRETTY_PRINT);
			if(file_put_contents($file, $config_json)) {
				return true;
			}
			else {
				return false;
			}
		}
		if($operation == 'r') {
			$config_json = file_get_contents($file);
			$config_json = json_decode($config_json, true);
			if($key) {
				return $config_json[$key];
			}
			else {
				return $config_json;
			}
		}
		else return false;
	}
	function config_array($file, $operation, $array = '') { // file, [r]ead/[w]rite, array (req. write)
		if($operation == 'w' && is_array($array)) {
			if(!file_exists($file)) {
				mkdir(dirname($file), 0755, true);
				touch($file);
			}
			
			$func_array = json_encode($array, JSON_PRETTY_PRINT);
			
			if(file_put_contents($file, $func_array)) {
				return true;
			}
			else {
				return false;
			}
		}
		if($operation == 'r') {
			$contents = file_get_contents($file);
			$func_array = json_decode($contents, true);
			return $func_array;
		}
		else return false;
	}
	
	function write_ini_file($file, $array = []) { // from Lawrence Cherone @ stackoverflow.com
		// check first argument is string
		if (!is_string($file)) {
			throw new \InvalidArgumentException('Function argument 1 must be a string.');
		}
		
		// check second argument is array
		if (!is_array($array)) {
			throw new \InvalidArgumentException('Function argument 2 must be an array.');
		}
		
		// process array
		$data = array();
		foreach ($array as $key => $val) {
		if (is_array($val)) {
			$data[] = "[$key]";
			foreach ($val as $skey => $sval) {
			if (is_array($sval)) {
				foreach ($sval as $_skey => $_sval) {
				if (is_numeric($_skey)) {
					$data[] = $skey.'[] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
				} else {
					$data[] = $skey.'['.$_skey.'] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
				}
				}
			} else {
				$data[] = $skey.' = '.(is_numeric($sval) ? $sval : (ctype_upper($sval) ? $sval : '"'.$sval.'"'));
			}
			}
		} else {
			$data[] = $key.' = '.(is_numeric($val) ? $val : (ctype_upper($val) ? $val : '"'.$val.'"'));
		}
		// empty line
		$data[] = null;
		}
		
		// open file pointer, init flock options
		$fp = fopen($file, 'w');
		$retries = 0;
		$max_retries = 100;
		
		if (!$fp) {
		return false;
		}
		
		// loop until get lock, or reach max retries
		do {
		if ($retries > 0) {
			usleep(rand(1, 5000));
		}
		$retries += 1;
		} while (!flock($fp, LOCK_EX) && $retries <= $max_retries);
		
		// couldn't get the lock
		if ($retries == $max_retries) {
		return false;
		}
		
		// got lock, write data
		fwrite($fp, implode(PHP_EOL, $data).PHP_EOL);
		
		// release lock
		flock($fp, LOCK_UN);
		fclose($fp);
		
		return true;
	}
	
	function bscode2html($text, $strip = false) {
		$text = preg_replace("/\*(.*?)\*/", "<b>$1</b>", $text);
		$text = preg_replace("/_(.*?)_/", "<i>$1</i>", $text);
		$text = preg_replace("/\[b\](.*)\[\/b\]/", "<b>$1</b>", $text);
		$text = preg_replace("/\[i\](.*)\[\/i\]/", "<i>$1</i>", $text);
		$text = preg_replace("/\[tiny\](.*)\[\/tiny\]/", "" . ( !$strip ? "<span style=\"font-size: xx-small;\">$1</span>" : "$1" ) . "", $text);
		$text = preg_replace("/\[small\](.*)\[\/small\]/", "" . ( !$strip ? "<span style=\"font-size: x-small;\">$1</span>" : "$1" ) . "", $text);
		$text = preg_replace("/\[medium\](.*)\[\/medium\]/", "" . ( !$strip ? "<span style=\"font-size: medium;\">$1</span>" : "$1" ) . "", $text);
		$text = preg_replace("/\[large\](.*)\[\/large\]/", "" . ( !$strip ? "<span style=\"font-size: large;\">$1</span>" : "$1" ) . "", $text);
		$text = preg_replace("/\[huge\](.*)\[\/huge\]/", "" . ( !$strip ? "<span style=\"font-size: x-large;\">$1</span>" : "$1" ) . "", $text);
		$text = preg_replace("/\[massive\](.*)\[\/massive\]/", "" . ( !$strip ? "<span style=\"font-size: xx-large;\">$1</span>" : "$1" ) . "", $text);
		$text = preg_replace("/\[color:((?:[0-9a-fA-F]{3}){1,2})\](.*)\[\/color\]/", "" . ( !$strip ? "<span style=\"color: #$1;\">$2</span>" : "$2" ) . "", $text);
		$text = preg_replace("/\[br\]/", "<br />", $text);
		
		if($text) {
			return $text;
		}
		else {
			return false;
		}
	}
	
	function keys_to_content($input, $array) {
		$input_array = explode(" ", $input);
		if(is_array($array) && is_array($input_array)) {
			$data = str_replace(array_keys($array), array_values($array), $input);
			return $data;
		}
		else {
			return false;
		}
	}
	
	function get_table_order($select, $sort, $return = '0', $test = '') { // $return = 0: list() = multi-arrays || 1: SQL command variables || 2(column)/3(sort): validation + $test = string of valid inputs (eg. '1,1,0,0,....0')
		$select = preg_replace('/\s+/', '', $select);
		$sort = preg_replace('/\s+/', '', $sort);
		$table = array( // Table names:
			"groupid", "tray", "device", "node", "pool", "name", "lun", "manufacturer", "model", "serial", "capacity", "cache", "rotation", "formfactor", "manufactured", "purchased", "installed", "removed", "warranty", "expires", "comment", "smart_units_read", "smart_units_written", "smart_status", "temperature", "powerontime_hours", "powerontime", "loadcycle", "nvme_available_spare", "nvme_available_spare_threshold", "endurance"
		);
		$input = array( // User input names - must also match $sort:
			"group", "tray", "device", "node", "pool", "name", "lun", "manufacturer", "model", "serial", "capacity", "cache", "rotation", "formfactor", "manufactured", "purchased", "installed", "removed", "warranty", "expires", "comment", "read", "written", "status", "temp", "powerontime_hours", "powerontime", "loadcycle", "nvme_spare", "nvme_spare_thres", "endurance"
		);
		$nice_names = array(
			"Group", "Tray", "Path", "Node", "Pool", "Name", "LUN", "Manufacturer", "Device Model", "S/N", "Capacity", "Cache", "Rotation", "FF", "Manufactured", "Purchased", "Installed", "Removed", "Warranty", "Expires", "Comment", "Read", "Written", "Status", "Temperature", "Powered Hours", "Powered", "Cycles", "Spare", "Spare Threshold", "Endurance"
		);
		$full_names = array(
			"Group", "Tray", "Path", "Node", "Pool Name", "Disk Name", "Logic Unit Number", "Manufacturer", "Device Model", "Serial Number", "Capacity", "Cache Size", "Rotation", "Form Factor", "Manufactured Date", "Purchased Date", "Installed Date", "Removed Date", "Warranty Period", "Warranty Expires", "Comment", "Smart Units Read", "Smart Units Written", "Status", "Temperature", "Power On Time Hours", "Power On Time", "Load Cycle Count", "Available Spare", "Available Spare Threshold", "Endurance"
		);
		$input_form = array(
			//                10                  20                  30
			1,1,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,0,1,0,1,0,0,0,0,0,0,0,0,0,0
		);
		
		if($select == "all") {
			$select = implode(",", $input);
			$sort = "asc:group";
		}
		
		if($select == "allowed") {
			$select = implode(",", $input);
			$sort = "asc:".implode(",", $input);
		}
		
		$table_sql = array_combine($table, $input);
		$table_user = array_combine($input, $table);
		$table_names = array_combine($nice_names, $input);
		$table_full = array_combine($full_names, $input);
		$table_forms = array_combine($input, $input_form);
		if($return >= 2 && !empty($test)) {
			$allowed_inputs = explode(",", $test);
			$table_allowed = array_combine($input, $allowed_inputs);
		}
		
		$select = explode(",", $select);
		$sort_dir = explode(":", $sort);
		$sort_col = explode(",", $sort_dir[1]);
		
		$return_table = array();
		$return_names = array();
		$return_full = array();
		$return_forms = array();
		$return_allow_colm = array();
		$return_allow_sort = array();
		
		if($return != 4) {
			if($return != 3) {
				$arr_length = count($select);
				for($i=0;$i<$arr_length;$i++) {
					$return_table[$i] = array_search($select[$i], $table_sql);
					$return_names[$i] = array_search($select[$i], $table_names);
					$return_full[$i] = array_search($select[$i], $table_full);
					$return_forms[$i] = $table_forms[$select[$i]];
					if($return == 2 && !empty($test)) {
						if($table_allowed[$select[$i]] == 0) {
							$return_allow_colm[$select[$i]] = $table_allowed[$select[$i]];
						}
					}
					
					if($return_table[$i] === false) { 
						return "Table column \"" . $select[$i] . "\" does not exist.\n";
						break;
					}
				}
			}
			
			if($return != 2) {
				$return_sort = array();
				$arr_length = count($sort_col);
				for($i=0;$i<$arr_length;$i++) {
					$check_sort = array_search($sort_col[$i], $input);
					$return_sort[] = $table_user[$sort_col[$i]];
					if($return == 3 && !empty($test)) {
						if($table_allowed[$sort_col[$i]] == 0) {
							$return_allow_sort[$sort_col[$i]] = $table_allowed[$sort_col[$i]];
						}
					}
					if($check_sort === false) { 
						return "Sort value \"" . $sort_col[$i] . "\" does not exist.\n";
						break;
					}
				}
				for($i=0;$i<count($return_sort);$i++) {
					$return_sort_str .= $return_sort[$i] . " SORT_" . strtoupper($sort_dir[0]);
					if($return_sort[$i+1]) { $return_sort_str .= ","; }
				}
			}
		}
		else {
			foreach($table_allowed as $table => $value) {
				if($value == 1) {
					$return_table[] = $table;
				}
			}
			sort($return_table);
		}
		
		if($sort_dir[0] != "asc" && $sort_dir[0] != "desc") {
			return "Sort direction is invalid.\n";
		}
		
		switch($return) {
			case 0:
				return [$select, $return_table, $return_names, $return_full, $return_forms]; // user, column, gui, fulltext(hover), forms
				break;
			case 1:
				//return array( // the old way with SQL
				//	"db_select" => $return_table,
				//	"db_sort" => implode(",", $return_sort),
				//	"db_dir" => strtoupper($sort_dir[0])
				//);
				return array(
					"db_select" => $return_table,
					"db_sort" => $return_sort_str,
					"db_dir" => strtoupper($sort_dir[0])
				);
				
				break;
			case 2:
				if($return_allow_colm) {
					$return_allow_colm = array_keys($return_allow_colm, '0');
					return "Table column [" . implode(",", $return_allow_colm) . "] not allowed to use for this table.\n";
				}
				else {
					return false;
				}
				break;
			case 3:
				if($return_allow_sort) {
					$return_allow_sort = array_keys($return_allow_sort, '0');
					return "Table sort [" . implode(",", $return_allow_sort) . "] not allowed to use for this table.\n";
				}
				else {
					return false;
				}
				break;
			case 4:
				return $return_table;
				
				break;
			default:
				return false;
		}
	}
	
	function list_array($array, $type, $tray = '', $valign = 'middle') {
		if($type == "html") {
			return array(
				"groupid" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\">" . stripslashes(htmlspecialchars($array["group_name"])) . "</td>",
				"tray" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $tray . "</td>",
				"device" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["device"] . "</td>",
				"pool" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["pool"] . "</td>",
				"name" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\"><a class=\"none\" style=\"text-decoration: underline;\" href=\"/Main/Device?name=" . $array["name"] . "\">" . $array["name"] . "</a></td>",
				"node" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["node"] . "</td>",
				"lun" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["lun"] . "</td>",
				"manufacturer" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["manufacturer"] . "</td>",
				"model" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["model"] . "</td>",
				"serial" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["serial"] . "</td>",
				"capacity" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["capacity"] . "</td>",
				"cache" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["cache"] . "</td>",
				"rotation" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["rotation"] . "</td>",
				"formfactor" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["formfactor"] . "</td>",
				"smart_status" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: center;\">" . $array["smart_status"] . "</td>",
				"temperature" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: left;\">" . $array["temp"] . " " . ( !empty($array["temp"]) ? "(" . $array["hotTemp"] . "/" . $array["maxTemp"] . ")" : null ) . "</td>",
				"powerontime_hours" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["powerontime_hours"] . "</span></td>",
				"powerontime" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["powerontime"] . "</span></td>",
				"loadcycle" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["loadcycle"] . "</td>",
				"endurance" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["endurance"] . "</td>",
				"smart_units_read" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["smart_units_read"] . "</td>",
				"smart_units_written" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["smart_units_written"] . "</td>",
				"nvme_available_spare" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["nvme_available_spare"] . "</td>",
				"nvme_available_spare_threshold" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["nvme_available_spare_threshold"] . "</td>",
				"installed" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["installed"] . "</td>",
				"removed" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["removed"] . "</td>",
				"manufactured" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["manufactured"] . "</td>",
				"purchased" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["purchased"] . "</td>",
				"warranty" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["warranty"] . "</td>",
				"expires" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["expires"] . "</td>",
				"comment" => "<td style=\"vertical-align: " . $valign . "; white-space: nowrap; padding: 0 10px 0 10px;\">" . bscode2html(stripslashes(htmlspecialchars($array["comment"]))) . "</td>"
			);
		}
		else {
			return array(
				"groupid" => "" . stripslashes($array["group_name"]) . "",
				"tray" => "" . $tray . "",
				"device" => "" . $array["device"] . "",
				"pool" => "" . $array["pool"] . "",
				"name" => "" . $array["name"] . "",
				"node" => "" . $array["node"] . "",
				"lun" => "" . $array["lun"] . "",
				"manufacturer" => "" . $array["manufacturer"] . "",
				"model" => "" . $array["model"] . "",
				"serial" => "" . $array["serial"] . "",
				"capacity" => "" . $array["capacity"] . "",
				"cache" => "" . $array["cache"] . "",
				"rotation" => "" . $array["rotation"] . "",
				"formfactor" => "" . $array["formfactor"] . "",
				"smart_status" => "" . $array["smart_status"] . "",
				"temperature" => "" . $array["temp"] . " " . ( !empty($array["temp"]) ? "(" . $array["hotTemp"] . "/" . $array["maxTemp"] . ")" : null ) . "",
				"powerontime_hours" => "" . $array["powerontime_hours"] . "",
				"powerontime" => "" . $array["powerontime"] . "",
				"loadcycle" => "" . $array["loadcycle"] . "",
				"endurance" => "" . $array["endurance"] . "",
				"smart_units_read" => "" . $array["smart_units_read"] . "",
				"smart_units_written" => "" . $array["smart_units_written"] . "",
				"nvme_available_spare" => "" . $array["nvme_available_spare"] . "",
				"nvme_available_spare_threshold" => "" . $array["nvme_available_spare_threshold"] . "",
				"installed" => "" . $array["installed"] . "",
				"removed" => "" . $array["removed"] . "",
				"manufactured" => "" . $array["manufactured"] . "",
				"purchased" => "" . $array["purchased"] . "",
				"warranty" => "" . $array["warranty"] . "",
				"expires" => "" . $array["expires"] . "",
				"comment" => "" . stripslashes($array["comment"]) . ""
			);
		}
	}
	
	// function from: https://stackoverflow.com/questions/16251625/how-to-create-and-download-a-csv-file-from-php-script
	function array_to_csv_download($array, $filename = "output.tsv", $delimiter="\t") {
		// open raw memory as file so no temp files needed, you might run out of memory though
		$f = fopen('php://memory', 'w'); 
		// loop over the input array
		foreach ($array as $line) { 
			// generate csv lines from the inner arrays
			fputcsv($f, $line, $delimiter); 
		}
		// reset the file pointer to the start of the file
		fseek($f, 0);
		// tell the browser it's going to be a csv file
		//header('Content-Type: text/csv');
		header('Content-Type: application/csv');
		// tell the browser we want to save it instead of displaying it
		header('Content-Disposition: attachment; filename="'.$filename.'";');
		// make php send the generated csv lines to the browser
		fpassthru($f);
	}
	
	function is_tray_allocated($db, $tray, $gid) {
		$array_locations = $db;
		foreach($array_locations as $hash => $array) {
			return ( ($db[$hash]["tray"] == $tray && $db[$hash]["groupid"] == $gid) ? $hash : null );
		}
	}
	
	function get_tray_location($db, $hash, $gid) {
		if($db[$hash]["groupid"] == $gid) {
			return ( empty($db[$hash]["tray"]) ? false : $db[$hash]["tray"] );
		}
	}
	
	function count_table_rows($db) {
		return ( isset($db) ? count($db) : 0 );
	}
	
	function human_filesize($bytes, $decimals = 2, $unit = false) {
		if($bytes) {
			if(!$unit) {
				$size = array('iB','kiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB');
				$bytefactor = 1024;
			}
			else{ 
				$size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
				$bytefactor = 1000;
			}
			
			$factor = floor((strlen($bytes) - 1) / 3);
			return sprintf("%.{$decimals}f", $bytes / pow($bytefactor, $factor)) . @$size[$factor];
		}
		else {
			return false;
		}
	}
	
	function smart_units_to_bytes($units, $block, $factor = 1000, $lba = false) {
		if($lba) {
			return $units * $block;
		}
		else {
			return $units * $block * $factor;
		}
	}
	
	function temperature_conv($float, $input, $output) {
		// temperature_conv(floatnumber, F, C) : from F to C
		
		// Celcius to Farenheit, [F]=[C]*9/5+32 | [C]=5/9*([F]-32)
		// Celcius to Kelvin, 0C = 273.15K
		
		$result = 0;
		
		if(is_numeric($float) && ($input != $output)) {
			if($output == "C") {
				if($input == "F") {
					$result = ($float-32)*5/9;
				}
				if($input == "K") {
					$result = $float-273.15;
				}
			}
			if($output == "F") {
				if($input == "C") {
					$result = $float*9/5+32;
				}
				if($input == "K") {
					$result = ($float-273.15)*9/5+32;
				}
			}
			if($output == "K") {
				if($input == "C") {
					$result = $float+273.15;
				}
				if($input == "F") {
					$result = (($float-32)*5/9)+273.15;
				}
			}
		}
		else {
			$result = $float;
		}
		
		if($result) {
			return $result;
		}
		else {
			return false;
		}
	}
	
	function get_unraid_disk_status($color, $type = '', $output = '', $force_orb_led = 0) {
		switch($color) {
			case 'green-on': $orb = 'circle'; $color = 'green'; $blink = ''; $help = 'Normal operation, device is active'; break;
			case 'green-blink': $orb = 'circle'; $color = 'grey'; $blink = 'green'; $help = 'Device is in standby mode (spun-down)'; break;
			case 'blue-on': $orb = 'square'; $color = 'blue'; $blink = ''; $help = 'New device'; break;
			case 'blue-blink': $orb = 'square'; $color = 'grey'; $blink = 'blue'; $help = 'New device, in standby mode (spun-down)'; break;
			case 'yellow-on': $orb = 'warning'; $color = 'yellow'; $blink = ''; $help = $type =='Parity' ? 'Parity is invalid' : 'Device contents emulated'; break;
			case 'yellow-blink': $orb = 'warning'; $color = 'grey'; $blink = 'yellow'; $help = $type =='Parity' ? 'Parity is invalid, in standby mode (spun-down)' : 'Device contents emulated, in standby mode (spun-down)'; break;
			case 'red-on': $orb = 'times'; $color = 'red'; $blink=''; $help = $type=='Parity' ? 'Parity device is disabled' : 'Device is disabled, contents emulated'; break;
			case 'red-blink': $orb = 'times'; $color = 'red'; $blink = 'red'; $help = $type=='Parity' ? 'Parity device is disabled' : 'Device is disabled, contents emulated'; break;
			case 'red-off': $orb = 'times'; $color = 'red'; $blink = ''; $help = $type =='Parity' ? 'Parity device is missing' : 'Device is missing (disabled), contents emulated'; break;
			case 'grey-off': $orb = 'square'; $color = 'grey'; $blink = ''; $help = 'Device not present'; break;
			// ZFS values
			case 'ONLINE': $orb = 'circle'; $color = 'green'; $blink = ''; $help = 'Normal operation, device is online'; break;
			case 'FAULTED': $orb = 'warning'; $color = 'yellow'; $blink = 'yellow'; $help = 'Device has faulted'; break;
			case 'DEGRADED': $orb = 'warning'; $color = 'yellow'; $blink = 'yellow'; $help = 'Device is degraded'; break;
			case 'AVAIL': $orb = 'circle'; $color = 'green'; $blink = ''; $help = 'Device is available'; break;
			case 'UNAVAIL': $orb = 'times'; $color = 'red'; $blink = 'red'; $help = 'Device is unavailable'; break;
			case 'OFFLINE': $orb = 'times'; $color = 'red'; $blink = 'red'; $help = 'Device is offline'; break;
			case 'STANDBY': $orb = 'circle'; $color = 'grey'; $blink = 'green'; $help = 'Device is online and in standby mode'; break;
		}
		
		if($force_orb_led == 1) {
			$orb = 'circle';
		}
		
		if($output == "color") {
			return $color;
		}
		if($output == "array") {
			$orb = "fa fa-".$orb." orb-disklocation ".$color."-orb-disklocation " . ( !empty($blink) ? $blink."-blink-disklocation" : null ) . "";
			return array(
				'orb'	=> $orb,
				'color'	=> $color,
				'text'	=> $help
			);
		}
		else {
			return ("<a class='info'><i class='fa fa-$orb orb-disklocation $color-orb-disklocation " . ( !empty($blink) ? $blink."-blink-disklocation" : null ) . "'></i><span>$help</span></a>");
		}
	}
	
	function get_powermode($device) {
		switch(config(POWERMODE_FILE, 'r', $device)) {
			case "ACTIVE":
				return "green-on";
				break;
			case "IDLE":
				return "green-on";
				break;
			case "STANDBY":
				return "green-blink";
				break;
			case "UNKNOWN":
				return "grey-off";
				break;
			default:
				return "grey-off";
		}
	}
	
	function zfs_check() {
		if(is_file("/usr/sbin/zpool")) {
			$status = shell_exec("/usr/sbin/zpool status");
			if(preg_match("/\bstate\b/i", $status)) {
				return $status;
			}
			else {
				return 0;
			}
		}
		else {
			return 0;
		}
	}
	function zfs_parser($str) {
		$result = array();
		
		$pools_pattern = "/pool:.*errors:.*(\n\n|$)/Uis";
		preg_match_all($pools_pattern, $str, $pools, PREG_SET_ORDER);
		
		$i = 0;
		while($i < count($pools)) {
			$pattern = "/((pool|state|scan|errors): (.*)?\n|(config):[\s]+(.*)?\s\n)/Uis";
			preg_match_all($pattern, $pools[$i][0], $matches, PREG_SET_ORDER);
			
			foreach($matches as $match) {
				$length = count($match);
				$result[$i][$match[$length-2]] = $match[$length-1];
			}
			
			$i++;
		}
		
		return $result;
	}
	
	function zfs_node($disk, $array) {
		$key = array_search($disk, array_column($array["blockdevices"], 'serial'));
		
		$results = array(
			'name' => $array["blockdevices"][$key]["name"],
			'serial' => $array["blockdevices"][$key]["serial"],
			'path' => $array["blockdevices"][$key]["path"],
			'node' => str_replace("/dev/", "", $array["blockdevices"][$key]["path"])
		);
		
		return $results;
	}
	
	function zfs_disk($disk, $zfs_config, $lsblk_array, $config = 0) {
		$zfs_node = zfs_node($disk, $lsblk_array);
		
		$i_loop = 0;
		while($i_loop < count($zfs_config)) {
			$disks = explode("\n", $zfs_config[$i_loop]["config"]);
			// Array $match: 0 = disk-by-id | 1 = state | 2 = read | 3 = write | 4 = cksum
			for($i=0; $i < count($disks); ++$i) {
				if(preg_match("/(".$disk."|".$zfs_node["node"].")/", $disks[$i])) {
					return ( !empty($config) ? $zfs_config[$i_loop] : explode(":", preg_replace("/\s+/", ":", trim($disks[$i]))) );
				}
			}
			$i_loop++;
		}
	}
	
	function seconds_to_time($seconds, $array = '', $format = '') {
		$seconds = (int)$seconds;
		$dateTime = new DateTime();
		$dateTime->sub(new DateInterval("PT{$seconds}S"));
		$interval = (new DateTime())->diff($dateTime);
		$pieces = explode(' ', $interval->format('%y %m %d'));
		$intervals = ( ($format == "short") ? ['Y', 'M', 'D'] : [' year', ' month', ' day'] );
		$result = [];
		foreach ($pieces as $i => $value) {
			if (!$value) {
				continue;
			}
			$periodName = $intervals[$i];
			if ($value > 1 && $format != "short") {
				$periodName .= 's';
			}
			$result_arr[$intervals[$i]] = $value;
			$result[] = "{$value}{$periodName}";
		}
		if($array) {
			return $result_arr;
		}
		else {
			if($format == "short") {
				return implode(' ', $result);
			}
			else {
				return implode(', ', $result);
			}
		}
	}
	
	function find_device_ports() {
		$path = "/dev/disk/by-path/";
		
		$scandisks = array_values(preg_grep("/part/", array_diff(scandir($path), array('..', '.')), PREG_GREP_INVERT));
		
		for($i=0; $i < count($scandisks); ++$i) {
			$deviceports[str_replace("../../", "", readlink($path . $scandisks[$i]))] = $scandisks[$i];
		}
		
		if($deviceports) {
			return $deviceports;
		}
		else {
			return false;
		}
	}
	
	function find_and_set_removed_devices_status($db, $locations, $arr_hash) {
		foreach($db as $hash => $array) {
			( ($db[$hash]["status"] != 'd') ? $db_hash[] = $hash : null );
		}
		
		$arr_hash = array_filter($arr_hash);
		$db_hash = array_filter($db_hash);
		
		sort($arr_hash);
		sort($db_hash);
		
		$results = array_diff($db_hash, $arr_hash);
		$old_hash = array_values($results);
		
		$status = "";
		
		for($i=0; $i < count($old_hash); ++$i) {
			if($db[$old_hash[$i]]["status"] != 'r') {
				$db[$old_hash[$i]]["status"] = 'r';
				$db[$old_hash[$i]]["removed"] = date("Y-m-d");
				
				unset($locations[$old_hash[$i]]);
			}
		}
		
		config_array(DISKLOCATION_DEVICES, 'w', $db);
		config_array(DISKLOCATION_LOCATIONS, 'w', $locations);
	}
	
	function force_set_removed_device_status($db, $locations, $hash) {
		foreach($db as $key => $data) {
			if($hash == $key) {
				$db[$hash]["status"] = 'r';
				$db[$hash]["removed"] = date("Y-m-d");
				
				unset($locations[$hash]);
			}
		}
		
		return ( config_array(DISKLOCATION_DEVICES, 'w', $db) && config_array(DISKLOCATION_LOCATIONS, 'w', $locations) ? true : false );
	}
	
	function force_undelete_devices($db, $action) {
		// r = read
		// m = modify
		
		switch($action) {
			case 'r': // read
				$i=0;
				foreach($db as $key => $data) {
					$ret += ( $db[$key]["status"] == 'd' ?? ++$i );
				}
				return $ret;
				
				break;
			case 'm': // modify
				foreach($db as $key => $data) {
					if($db[$key]["status"] == 'd') {
						$db[$key]["status"] = 'r';
					}
				}
				
				return ( config_array(DISKLOCATION_DEVICES, 'w', $db) ? true : false );
				
				break;
			default:
				return false;
		}
	}
	
	function force_reset_color($config, $devices, $groups, $hash = 0) {
		global $bgcolor_parity_default, $bgcolor_unraid_default, $bgcolor_cache_default, $bgcolor_others_default, $bgcolor_empty_default;
		
		if($hash == '*' || $hash == 'all') {
			foreach($devices as $id => $data) { // id=hash not $hash
				$devices[$id]["color"] = '';
			}
			foreach($groups as $id => $data) {
				$groups[$id]["group_color"] = '';
			}
			return ((config_array(DISKLOCATION_DEVICES, 'w', $devices) && config_array(DISKLOCATION_GROUPS, 'w', $groups)) ? true : false );
		}
		else if($hash) {
			$devices[$hash]["color"] = '';
			return config_array(DISKLOCATION_DEVICES, 'w', $devices);
		}
		else {
			foreach($config as $key => $data) {
				$config["bgcolor_parity"] = $bgcolor_parity_default;
				$config["bgcolor_unraid"] = $bgcolor_unraid_default;
				$config["bgcolor_cache"] = $bgcolor_cache_default;
				$config["bgcolor_others"] = $bgcolor_others_default;
				$config["bgcolor_empty"] = $bgcolor_empty_default;
			}
			return !config_array(DISKLOCATION_CONF, 'w', $config);
		}
	}
	
	function array_duplicates($array) {
		return count(array_filter($array)) !== count(array_unique(array_filter($array)));
	}
	
	function recursive_array_search($needle,$haystack) { // from php.net: buddel
		if(is_array($haystack)) {
			foreach($haystack as $key=>$value) {
				$current_key=$key;
				if($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) {
					return $current_key;
				}
			}
		}
		return false;
	}
	
	function sort_array() { // from php.net: jimpoz
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if (is_string($field)) {
				$tmp = array();
				foreach ($data as $key => $row) {
					$tmp[$key] = $row[$field];
					$args[$n] = $tmp;
				}
			}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		return array_pop($args);
	}
	
	function tray_number_assign($col, $row, $dir, $grid, $skip = array()) {
		$total = $col * $row; // 6 = 3 * 2
		
		$start = 1;
		$data = array();
		$tmp = array();
		$results = array();
		
		switch($dir) {
			case 1: // ok
				if($grid) {
					/* 1 = left->right|top->bottom:		verified ok	(row: left->right)
						1-2-3
						4-5-6
					*/
					/* 1 = top->bottom|left->right:		verified ok	(column: top->bottom)
						1-3-5
						2-4-6
					*/
					
					$i_skip = 1;
					for($i=1; $i <= $total; ++$i) {
						if(empty($skip)) {
							$data[$i] = $i;
						}
						else if(!$skip[$i]) {
							$data[$i] = $i_skip;
							$i_skip++;
						}
					}
					array_unshift($data, $grid);
					
					$results = $data;
				}
				
				return $results;
				
				break;
				
			case 2: // ok
				if($grid == "row") {
					/* 2 = left->right|bottom->top:		verified ok	(row: left->right)
						4-5-6
						1-2-3
					*/
					$i_col = 1;
					$i_skip = 1;
					for($i=1; $i <= $total; ++$i) {
						if(empty($skip)) {
							$data[$i_col][$i] = $i;
						}
						else if(!$skip[$i]) {
							$data[$i_col][$i] = $i_skip;
							$i_skip++;
							
						}
						if($i % $col == 0) {
							$i_col++;
						}
					}
					
					for($i=count($data); $i >= 1; $i=$i-1) {
						array_push($tmp, $data[$i]);
					}
					
					$results = array_merge(... $tmp);
					array_unshift($results, $grid);
				}
				else {
					/* 2 = bottom->top|left->right:		verified ok	(column: top->bottom)
						2-4-6
						1-3-5
					*/
					
					$i_row = 1;
					$i_skip = 1;
					for($i=1; $i <= $total; $i++) {
						if(empty($skip)) {
							$data[$i_row][$i] = $i;
						}
						else if(!$skip[$i]) {
							$data[$i_row][$i] = $i_skip;
							$i_skip++;
							
						}
						if($i % $row == 0) {
							$i_row++;
						}
					}
					
					for($i=1; $i <= count($data); $i++) {
						array_push($tmp, array_reverse($data[$i]));
					}
					
					$results = array_merge(... $tmp);
					array_unshift($results, $grid);
					
					return $results;
				}
				
				return $results;
				
				break;
				
			case 3: // ok
				if($grid == "row") {
					/* 3 = right->left|top->bottom:		verified ok	(row: left->right)
						3-2-1
						6-5-4
					*/
					$i_col = 1;
					$i_skip = 1;
					for($i=1; $i <= $total; $i++) {
						if(empty($skip)) {
							$data[$i_col][$i] = $i;
						}
						else if(!$skip[$i]) {
							$data[$i_col][$i] = $i_skip;
							$i_skip++;
						}
						if($i % $col == 0) {
							$i_col++;
						}
					}
					
					for($i=1; $i <= count($data); $i++) {
						array_push($tmp, array_reverse($data[$i]));
					}
					
					$results = array_merge(... $tmp);
					array_unshift($results, $grid);
				}
				else {
					/* 3 = top->bottom|right->left:		verified ok	(column: top->bottom)
						5-3-1
						6-4-2
					*/
					
					$i_row = 1;
					$i_skip = 1;
					for($i=1; $i <= $total; ++$i) {
						if(empty($skip)) {
							$data[$i_row][$i] = $i;
						}
						else if(!$skip[$i]) {
							$data[$i_row][$i] = $i_skip;
							$i_skip++;
						}
						if($i % $row == 0) {
							$i_row++;
						}
					}
					
					for($i=count($data); $i >= 1; $i=$i-1) {
						array_push($tmp, $data[$i]);
					}
					
					$results = array_merge(... $tmp);
					array_unshift($results, $grid);
				}
				
				return $results;
				
				break;
				
			case 4: // ok
				if($grid) {
					/* 4 = right->left|bottom->top:		verified ok	(row: left->right)
						6-5-4
						3-2-1
					*/
					/* 4 = bottom->top|right->left:		verified ok	(column: top->bottom)
						6-4-2
						5-3-1
					*/
					$i_skip = 1;
					for($i=1; $i <= $total; ++$i) {
						if(empty($skip)) {
							$data[$i] = $i;
						}
						else if(!$skip[$i]) {
							$data[$i] = $i_skip;
							$i_skip++;
						}
					}
					
					rsort($data);
					array_unshift($data, $grid);
					
					$results = $data;
				}
				
				return $results;
				
				break;

			default:
				return false;
		}
	}
	
	function use_stylesheet($css = '') {
		if(is_file(EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/pages/styles/" . $css . "")) {
			unlink(EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/pages/styles/signals.css");
			symlink(EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/pages/styles/" . $css . "", EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/pages/styles/signals.css");
			touch(EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/pages/styles/signals.css", time(), time());
			return EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/pages/styles/" . $css . "";
		}
		else {
			return false;
		}
	}
	
	// lsscsi -bg
	function lsscsi_parser($input) {
		// \[(.+:.+:.+:.+)\]\s+(-|(\/dev\/(h|s)d[a-z]{1,})?)\s+((\/dev\/(nvme|sg)[0-9]{1,})(n[0-9]{1,})?)
		$pattern_device = "\[(.+:.+:.+:.+)\]\s+";						// $1
		//$pattern_devnode = "(-|(\/dev\/(h|s)d[a-z]{1,})?)\s+";				// $3 pre 6.9
		//$pattern_scsigendevnode = "((\/dev\/(nvme|sg)[0-9]{1,})(n[0-9]{1,})?)";		// $5 pre 6.9
		$pattern_devnode = "((\/dev\/((h|s)d[a-z]{1,}|nvme[0-9]{1,})(n[0-9]{1,})?))\s+";	// $2
		$pattern_scsigendevnode = "(-|(\/dev\/(sg)[0-9]{1,}))";					// $7
		
		if($input) {
			[$device, $devnode, $scsigendevnode] = explode("|", preg_replace("/" . $pattern_device . "" . $pattern_devnode . "" . $pattern_scsigendevnode . "/iu", "$1|$2|$7", $input));
			
			if($scsigendevnode) {
				$scsigendevnode = ( strstr($scsigendevnode, "-") ? $devnode : $scsigendevnode ); // script uses SG for most things, so we add nvme into it as well.
			}
			
			return array(
				'device'	=> ($device ? trim($device) : ''),
				'devnode'	=> ($devnode ? str_replace("-", "", trim($devnode)) : ''),
				'sgnode'	=> ($scsigendevnode ? trim($scsigendevnode) : '')
			);
		}
		else {
			return array(
				'device'	=> '',
				'devnode'	=> '',
				'sgnode'	=> ''
			);
		}
	}
	
	function get_smart_rotation($input) {
		switch($input) {
			case -2:
				$smart_rotation = "NVMe SSD";
				break;
			case -1:
				$smart_rotation = "SSD";
				break;
			case 0:
				$smart_rotation = "N/A";
				break;
			case null:
				$smart_rotation = "N/A";
				break;
			default:
				$smart_rotation = $input . " RPM";
		}
		return $smart_rotation;
	}
	
	function get_smart_cache($device) { // output MB
		$smart_id_data = shell_exec("smartctl " . $device . " -l gplog,0x30,2 | grep 0000420");
		$values = array();
		$values = explode(" ", $smart_id_data);
		
		$bytes = hexdec($values[4].$values[5].$values[6].$values[7]);
		
		return $bytes / 1024 / 1024;
	}
	
	function get_disk_ack($device, $file = EMHTTP_VAR . "/" . UNRAID_MONITOR_FILE) {
		$unraid_monitor = parse_ini_file($file, true);
		return (isset($unraid_monitor["smart"][$device.".ack"]) ? true : false);
	}
	
	function set_disk_ack($devices, $file = EMHTTP_VAR . "/" . UNRAID_MONITOR_FILE) {
		$unraid_monitor = parse_ini_file($file, true);
		$devices = explode(",", $devices);
		
		foreach($devices as $disk) {
			$unraid_monitor["smart"][$disk.".ack"] = "true";
		}
		if(!write_ini_file($file, $unraid_monitor)) {
			return false;
		}
		else return true;
	}
	
	function check_smart_files() { // return true if files found
		if(file_exists(DISKLOCATION_TMP_PATH . "/smart")) {
			$dir = array_diff(scandir(DISKLOCATION_TMP_PATH . "/smart"), array('..', '.'));
			return ( empty($dir) ? false : true );
		}
		else return false;
	}
	
	function check_devicepath_conflict($array) { // return difference or 1 if conflict, empty if no conflict
		if(is_array($array) && !empty($array)) {
			if(file_exists(DISKLOCATION_TMP_PATH . "/powermode.json")) {
				$json_array = json_decode(file_get_contents(DISKLOCATION_TMP_PATH . "/powermode.json"), true);
				if(is_array($json_array) && !empty($json_array)) {
					foreach($array as $key => $value) {
						$devicepath[] = ( $array[$key]["raw"]["status"] != 'r' ? $array[$key]["raw"]["device"] : null );
					}
					sort($devicepath);
					
					$json_powermode = array_diff($json_array, ['UNKNOWN']);
					$powermode = array_keys($json_powermode);
					sort($powermode);
					
					return (array_diff($powermode, array_filter($devicepath)));
				}
				else return 1; // 1 = found conflict
			}
			else return 1;
		}
		else return 1;
	}
?>
