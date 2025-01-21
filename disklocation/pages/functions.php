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
	
	function debug_print($act, $line, $section, $message) {
		if($act == 1 && $section && $message) {
			// write out directly and flush out the results asap
			$out = "[" . date("H:i:s") . "] " . basename(__FILE__) . ":" . $line . " @ " . $section . ": " . $message . "\n";
			file_put_contents("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.log", $out, FILE_APPEND);
			return true;
		}
		if($act == 2 && $section != "SQL") {
			print("[" . date("H:i:s") . "] " . basename(__FILE__) . ":" . $line . " @ " . $section . ": " . $message . "\n");
			flush();
		}
		if($act == 3 && $section == "loop") {
			print("[" . date("H:i:s") . "] " . $message . "<br />\n");
			flush();
		}
		else {
			return false;
		}
	}
	
	debug_print($debugging_active, __LINE__, "functions", "Debug function active.");
	
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
			
			$new_array = json_encode($array, JSON_PRETTY_PRINT);
			
			if(file_put_contents($file, $new_array)) {
				return true;
			}
			else {
				return false;
			}
		}
		if($operation == 'r') {
			$contents = file_get_contents($file);
			$cur_array = json_decode($contents, true);
			return $cur_array;
		}
		else return false;
	}
	
	function put_ini_file($file, $array, $i = 0) {
		$str="";
		foreach ($array as $k => $v){
			if (is_array($v)){
				$str.=str_repeat(" ",$i*2)."[$k]".PHP_EOL; 
				$str.=put_ini_file("",$v, $i+1);
			}
			else {
				$str.=str_repeat(" ",$i*2)."$k = $v".PHP_EOL; 
			}
			if($file) {
				return file_put_contents($file,$str);
			}
			else {
				return $str;
			}
		}
	}
	
	function bscode2html($text) {
		$text = preg_replace("/\*(.*?)\*/", "<b>$1</b>", $text);
		$text = preg_replace("/_(.*?)_/", "<i>$1</i>", $text);
		$text = preg_replace("/\[b\](.*)\[\/b\]/", "<b>$1</b>", $text);
		$text = preg_replace("/\[i\](.*)\[\/i\]/", "<i>$1</i>", $text);
		$text = preg_replace("/\[tiny\](.*)\[\/tiny\]/", "<span style=\"font-size: xx-small;\">$1</span>", $text);
		$text = preg_replace("/\[small\](.*)\[\/small\]/", "<span style=\"font-size: x-small;\">$1</span>", $text);
		$text = preg_replace("/\[medium\](.*)\[\/medium\]/", "<span style=\"font-size: medium;\">$1</span>", $text);
		$text = preg_replace("/\[large\](.*)\[\/large\]/", "<span style=\"font-size: large;\">$1</span>", $text);
		$text = preg_replace("/\[huge\](.*)\[\/huge\]/", "<span style=\"font-size: x-large;\">$1</span>", $text);
		$text = preg_replace("/\[massive\](.*)\[\/massive\]/", "<span style=\"font-size: xx-large;\">$1</span>", $text);
		$text = preg_replace("/\[color:((?:[0-9a-fA-F]{3}){1,2})\](.*)\[\/color\]/", "<span style=\"color: #$1;\">$2</span>", $text);
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
			return str_replace(array_keys($array), array_values($array), $input);
		}
		else {
			return false;
		}
	}
	
	function get_table_order($select, $sort, $return = '0', $test = '') { // $return = 0: list() = multi-arrays || 1: SQL command variables || 2(column)/3(sort): validation + $test = string of valid inputs (eg. '1,1,0,0,....0')
		$select = preg_replace('/\s+/', '', $select);
		$sort = preg_replace('/\s+/', '', $sort);
		$table = array( // Table names:
			"groupid", "tray", "device", "node", "pool", "name", "lun", "manufacturer", "model", "serial", "capacity", "cache", "rotation", "formfactor", "manufactured", "purchased", "installed", "removed", "warranty", "expires", "comment", "smart_units_read", "smart_units_written"
		);
		$input = array( // User input names - must also match $sort:
			"group", "tray", "device", "node", "pool", "name", "lun", "manufacturer", "model", "serial", "capacity", "cache", "rotation", "formfactor", "manufactured", "purchased", "installed", "removed", "warranty", "expires", "comment", "read", "written"
		);
		$nice_names = array(
			"Group", "Tray", "Path", "Node", "Pool", "Name", "LUN", "Manufacturer", "Device Model", "S/N", "Capacity", "Cache", "Rotation", "FF", "Manufactured", "Purchased", "Installed", "Removed", "Warranty", "Expires", "Comment", "Read", "Written"
		);
		$full_names = array(
			"Group", "Tray", "Path", "Node", "Pool Name", "Disk Name", "Logic Unit Number", "Manufacturer", "Device Model", "Serial Number", "Capacity", "Cache Size", "Rotation", "Form Factor", "Manufactured Date", "Purchased Date", "Installed Date", "Removed Date", "Warranty Period", "Warranty Expires", "Comment", "Smart Units Read", "Smart Units Written"
		);
		$input_form = array(
			//                10                  20                  30
			1,1,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,0,1,0,1,0,0
		);
		
		if($select == "all") {
			$select = implode(",", $input);
			$sort = "asc:group";
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
					return "Sort value does not exist.\n";
					break;
				}
			}
			for($i=0;$i<count($return_sort);$i++) {
				$return_sort_str .= $return_sort[$i] . " SORT_" . strtoupper($sort_dir[0]);
				if($return_sort[$i+1]) { $return_sort_str .= ","; }
			}
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
			default:
				return false;
		}
	}
	
	function list_array($array, $type, $tray = '') {
		if($type == "html") {
			return array(
				"groupid" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . stripslashes(htmlspecialchars($array["group_name"])) . "</td>",
				"tray" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $tray . "</td>",
				"device" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["device"] . "</td>",
				"pool" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["pool"] . "</td>",
				"name" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["name"] . "</td>",
				"node" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["node"] . "</td>",
				"lun" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["lun"] . "</td>",
				"manufacturer" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["manufacturer"] . "</td>",
				"model" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["model"] . "</td>",
				"serial" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["serial"] . "</td>",
				"capacity" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["capacity"] . "</td>",
				"cache" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["cache"] . "</td>",
				"rotation" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["rotation"] . "</td>",
				"formfactor" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["formfactor"] . "</td>",
				"smart_status" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: center;\">" . $array["smart_status"] . "</td>",
				"temperature" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: left;\">" . $array["temp"] . " (" . $array["hotTemp"] . "/" . $array["maxTemp"] . ")</td>",
				"powerontime" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["powerontime"] . "</span></td>",
				"loadcycle" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["loadcycle"] . "</td>",
				"nvme_percentage_used" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["nvme_percentage_used"] . "</td>",
				"smart_units_read" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["smart_units_read"] . "</td>",
				"smart_units_written" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["smart_units_written"] . "</td>",
				"nvme_available_spare" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["nvme_available_spare"] . "</td>",
				"nvme_available_spare_threshold" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["nvme_available_spare_threshold"] . "</td>",
				//"benchmark_r" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["benchmark_r"] . "</td>",
				//"benchmark_w" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $data["benchmark_w"] . "</td>",
				"installed" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["installed"] . "</td>",
				"removed" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["removed"] . "</td>",
				"manufactured" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["manufactured"] . "</td>",
				"purchased" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["purchased"] . "</td>",
				"warranty" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\">" . $array["warranty"] . "</td>",
				"expires" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . $array["expires"] . "</td>",
				"comment" => "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">" . bscode2html(stripslashes(htmlspecialchars($array["comment"]))) . "</td>"
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
				"temperature" => "" . $array["temp"] . " (" . $array["hotTemp"] . "/" . $array["maxTemp"] . ")",
				"powerontime" => "" . $array["powerontime"] . "</span>",
				"loadcycle" => "" . $array["loadcycle"] . "",
				"nvme_percentage_used" => "" . $array["nvme_percentage_used"] . "",
				"smart_units_read" => "" . $array["smart_units_read"] . "",
				"smart_units_written" => "" . $array["smart_units_written"] . "",
				"nvme_available_spare" => "" . $array["nvme_available_spare"] . "",
				"nvme_available_spare_threshold" => "" . $array["nvme_available_spare_threshold"] . "",
				//"benchmark_r" => "" . $data["benchmark_r"] . "",
				//"benchmark_w" => "" . $data["benchmark_w"] . "",
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
	
	function smart_units_to_bytes($units, $block, $unit = false, $lba = false) {
		if($lba) {
			return $units * $block;
		}
		else {
			if(!$unit) {
				return $units * $block * 1024;
			}
			else {
				return $units * $block * 1000;
			}
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
			case 'green-on': $orb = 'circle'; $color = 'green'; $blink=0; $help = 'Normal operation, device is active'; break;
			case 'green-blink': $orb = 'circle'; $color = 'grey'; $blink=1; $help = 'Device is in standby mode (spun-down)'; break;
			case 'blue-on': $orb = 'square'; $color = 'blue'; $blink=0; $help = 'New device'; break;
			case 'blue-blink': $orb = 'square'; $color = 'grey'; $blink=1; $help = 'New device, in standby mode (spun-down)'; break;
			case 'yellow-on': $orb = 'warning'; $color = 'yellow'; $blink=0; $help = $type =='Parity' ? 'Parity is invalid' : 'Device contents emulated'; break;
			case 'yellow-blink': $orb = 'warning'; $color = 'grey'; $blink=1; $help = $type =='Parity' ? 'Parity is invalid, in standby mode (spun-down)' : 'Device contents emulated, in standby mode (spun-down)'; break;
			case 'red-on': $orb = 'times'; $color = 'red'; $blink=0; $help = $type=='Parity' ? 'Parity device is disabled' : 'Device is disabled, contents emulated'; break;
			case 'red-blink': $orb = 'times'; $color = 'red'; $blink=1; $help = $type=='Parity' ? 'Parity device is disabled' : 'Device is disabled, contents emulated'; break;
			case 'red-off': $orb = 'times'; $color = 'red'; $blink=0; $help = $type =='Parity' ? 'Parity device is missing' : 'Device is missing (disabled), contents emulated'; break;
			case 'grey-off': $orb = 'square'; $color = 'grey'; $blink=0; $help = 'Device not present'; break;
			// ZFS values
			case 'ONLINE': $orb = 'circle'; $color = 'green'; $blink=0; $help = 'Normal operation, device is online'; break;
			case 'FAULTED': $orb = 'warning'; $color = 'yellow'; $blink=1; $help = 'Device has faulted'; break;
			case 'DEGRADED': $orb = 'warning'; $color = 'yellow'; $blink=1; $help = 'Device is degraded'; break;
			case 'AVAIL': $orb = 'circle'; $color = 'green'; $blink=0; $help = 'Device is available'; break;
			case 'UNAVAIL': $orb = 'times'; $color = 'red'; $blink=1; $help = 'Device is unavailable'; break;
			case 'OFFLINE': $orb = 'times'; $color = 'red'; $blink=1; $help = 'Device is offline'; break;
		}
		
		if($force_orb_led == 1) {
			$orb = 'circle';
		}
		
		if($output == "color") {
			return $color;
		}
		if($output == "array") {
			$orb = "fa fa-".$orb." orb-disklocation ".$color."-orb-disklocation " . ( !empty($blink) ? $color."-blink-disklocation" : null ) . "";
			return array(
				'orb'	=> $orb,
				'color'	=> $color,
				'text'	=> $help
			);
		}
		else {
			return ("<a class='info'><i class='fa fa-$orb orb-disklocation $color-orb-disklocation " . ( !empty($blink) ? $color."-blink-disklocation" : null ) . "'></i><span>$help</span></a>");
		}
	}
	
	function zfs_check() {
		if(is_file("/usr/sbin/zpool")) {
			$status = shell_exec("/usr/sbin/zpool status");
			if(preg_match("/\bstate\b/i", $status)) {
				return 1;
			}
			else {
				return 0;
			}
		}
		else {
			return 0;
		}
	}
	
	function zfs_pools() {
		$str = shell_exec("/usr/sbin/zpool list");
		$matches = preg_split("/\r\n|\n|\r/", $str);
		$result = array();
		
		$i = 1; // skip first row
		while($i < count($matches)) {
			list($NAME,$SIZE,$ALLOC,$FREE,$CKPOINT,$EXPANDSZ,$FRAG,$CAP,$DEDUP,$HEALTH,$ALTROOT) = explode(" ", $matches[$i]);
			$result[] = $NAME;
			$i++;
		}
		
		return array_filter($result);
	}
	
	function zfs_parser() {
		$pools = zfs_pools();
		
		$result = array();
		$i = 0;
		while($i < count($pools)) {
			$str = shell_exec("/usr/sbin/zpool status " . $pools[$i] . "");
			$pattern = "/((pool|state|scan|errors): (.*)?\n|(config):[\s]+(.*)?\s\n)/Uis";
			preg_match_all($pattern, $str, $matches, PREG_SET_ORDER);
			
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
				$groups[$id]["group_color"] = 'test';
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
	
	function tray_number_assign($col, $row, $dir, $grid) {
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
					for($i=1; $i <= $total; ++$i) {
						$data[] = $i;
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
					for($i=1; $i <= $total; ++$i) {
						$data[$i_col][$i] = $i;
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
					for($i=1; $i <= $total; $i++) {
						$data[$i_row][$i] = $i;
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
					for($i=1; $i <= $total; $i++) {
						$data[$i_col][$i] = $i;
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
					for($i=1; $i <= $total; ++$i) {
						$data[$i_row][$i] = $i;
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

					for($i=1; $i <= $total; ++$i) {
						$data[] = $i;
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
	
	function check_smart_files() { // return true if files found
		$dir = array_diff(scandir(DISKLOCATION_TMP_PATH . "/smart"), array('..', '.'));
		return ( empty($dir) ? false : true );
	}
?>
