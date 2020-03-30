<?php
	/*
	 *  Copyright 2019-2020, Ole-Henrik Jakobsen
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
	// Comment out to enable debugging:
	//$debugging_active = 1;
	
	// Set warning level
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	$get_page_info = parse_ini_file("/usr/local/emhttp/plugins/disklocation/disklocation.page");
	
	// define constants
	define("UNRAID_CONFIG_PATH", "/boot/config");
	define("DISKLOCATION_DB", "/boot/config/plugins/disklocation/disklocation.sqlite");
	define("DISKINFORMATION", "/var/local/emhttp/disks.ini");
	define("DISKLOGFILE", "/boot/config/disk.log");
	define("DISKLOCATION_VERSION", $get_page_info["Version"]);
	define("DISKLOCATION_URL", "/Tools/disklocation");
	define("DISKLOCATIONCONF_URL", "/Settings/disklocationConfig");
	define("DISKLOCATION_PATH", "/plugins/disklocation");
	define("EMHTTP_ROOT", "/usr/local/emhttp");
	
	$disklocation_error = array();
	$disklocation_new_install = 0;
	$group = array();
	
	if(!is_file(DISKLOCATION_DB)) {
		$disklocation_new_install = 1;
	}
	
	// open and/or create database
	class DLDB extends SQLite3 {
		function __construct() {
			$this->open(DISKLOCATION_DB);
		}
	}
	
	$db = new DLDB();
	
	if(!$db) {
		echo $db->lastErrorMsg();
	}
	
	require_once("sqlite_tables.php");
	
	if($argv[1] == "cronjob" || $argv[1] == "force") {
		if(!$argv[2]) { 
			$debugging_active = 2;
		}
		set_time_limit(600); // set to 10 minutes.
	}
	
	function debug_print($act = 0, $line, $section, $message) {
		if($act == 1 && $section && $message) {
			// write out directly and flush out the results asap
			$out = "<span style=\"color: red;\">[" . date("His") . "] <b>" . basename(__FILE__) . ":<i>" . $line . "</i></b> @ " . $section . ": " . $message . "</span><br />\n";
			print($out);
			file_put_contents("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/debugging.html", $out, FILE_APPEND);
			flush();
			return true;
		}
		if($act == 2 && $section != "SQL") {
			print("[" . date("His") . "] " . basename(__FILE__) . ":" . $line . " @ " . $section . ": " . $message . "\n");
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
	
	function is_tray_allocated($db, $tray, $gid) {
		$sql = "SELECT hash FROM location WHERE tray = '" . $tray . "' AND groupid = '" . $gid . "'";
		$results = $db->query($sql);
		while($data = $results->fetchArray(1)) {
			return ( isset($data["hash"]) ? $data["hash"] : false);
		}
	}
	
	function get_tray_location($db, $hash, $gid) {
		$sql = "SELECT * FROM location WHERE hash = '" . $hash . "' AND groupid = '" . $gid . "'";
		$results = $db->query($sql);
		while($data = $results->fetchArray(1)) {
			if(!$data["empty"]) { 
				return ( empty($data["tray"]) ? false : $data["tray"] );
			}
		}
	}
	
	function count_table_rows($db, $table) {
		$sql = "SELECT COUNT(*) FROM " . $table . ";";
		$results = $db->query($sql);
		$data = $results->fetchArray(SQLITE3_NUM);
		return ( isset($data[0]) ? $data[0] : 0 );
	}
	
	function human_filesize($bytes, $decimals = 2, $unit = '') {
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
	
	function temperature_conv($float, $input, $output) {
		// temperature_conv(floatnumber, F, C) : from F to C
		
		// Celcius to Farenheit, [F]=[C]*9/5+32 | [C]=5/9*([F]-32)
		// Celcius to Kelvin, 0C = 273.15K
		
		$result = 0;
		if($input != $output) {
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
	
	function get_unraid_disk_status($color, $status, $type = '') {
		if($type == "cache") {
			$type = 1; // Cache drive(s)
		}
		else {
			$type = '';
		}
		
		$arr_color = array(
			// color	=> first digit
			"grey-off"	=> 0,
			"green-on"	=> 1,
			"green-blink"	=> 2,
			"yellow-on"	=> 3,
			"yellow-blink"	=> 4,
			"red-on"	=> 5,
			"red-blink"	=> 6,
			"red-off"	=> 7,
			"blue-on"	=> 8,
			"blue-blink"	=> 9
		);
		
		$arr_status = array(
			// color	=> second digit
			"DISK_NP"		=> 0,
			"DISK_OK"		=> 1,
			"DISK_INVALID"		=> 2,
			"DISK_DSBL"		=> 3,
			"DISK_NP_DSBL"		=> 4,
			"DISK_DSBL_NEW"		=> 5,
			"DISK_NP_MISSING"	=> 6,
			"DISK_WRONG"		=> 7,
			"DISK_NEW"		=> 8,
			"DISK_OK_NP"		=> 9
		);
		
		$arr_messages = array(
			"00" => "Disk unavailable or no information",
			"11" => "Disk valid: Active or idle",
			"21" => "Disk valid: Standby",
			"32" => "Disk invalid: Active or idle",
			"42" => "Disk invalid: Standby",
			"53" => "Disk emulated: Active or idle",
			"63" => "Disk emulated: Standby",
			"74" => "Disk emulated: No disk",
			"85" => "Disabled, new disk present: Active or idle",
			"95" => "Disabled, new disk present: Standby",
			"76" => "Enabled, disk not present",
			"57" => "Wrong disk, disk present: Active or idle",
			"67" => "Wrong disk, disk present: Standby",
			"88" => "New disk: Active or idle",
			"98" => "New disk: Standby",
			"100" => "Disk unavailable, not the first device of multi-disk pool",
			"109" => "Disk unavailable, first device of multi-disk pool",
			"111" => "Disk valid: Active or idle",
			"121" => "Disk valid: Standby",
			"188" => "New disk: Active or idle",
			"198" => "New disk: Standby"
		);
		
		return ( isset($arr_messages["" . $type . "" . $arr_color[$color] . "" . $arr_status[$status] . ""]) ? $arr_messages["" . $type . "" . $arr_color[$color] . "" . $arr_status[$status] . ""] : false );
	}
	
	function seconds_to_time($seconds, $array = '') {
		$seconds = (int)$seconds;
		$dateTime = new DateTime();
		$dateTime->sub(new DateInterval("PT{$seconds}S"));
		$interval = (new DateTime())->diff($dateTime);
		$pieces = explode(' ', $interval->format('%y %m %d'));
		$intervals = ['year', 'month', 'day'];
		$result = [];
		foreach ($pieces as $i => $value) {
			if (!$value) {
				continue;
			}
			$periodName = $intervals[$i];
			if ($value > 1) {
				$periodName .= 's';
			}
			$result_arr[$intervals[$i]] = $value;
			$result[] = "{$value} {$periodName}";
		}
		if($array) {
			return $result_arr;
		}
		else {
			return implode(', ', $result);
		}
	}
	
	function find_and_set_removed_devices_status($db, $arr_hash) {
		$sql = "SELECT hash FROM disks WHERE status IS NOT 'd';";
		$results = $db->query($sql);
		$sql_hash = array();
		while($res = $results->fetchArray(1)) {
			$sql_hash[] = $res["hash"];
		}
		
		$arr_hash = array_filter($arr_hash);
		$sql_hash = array_filter($sql_hash);
		
		sort($arr_hash);
		sort($sql_hash);
		
		$results = array_diff($sql_hash, $arr_hash);
		$old_hash = array_values($results);
		
		for($i=0; $i < count($old_hash); ++$i) {
			$sql_status .= "
				UPDATE disks SET
					status = 'r'
				WHERE hash = '" . $old_hash[$i] . "'
				;
				DELETE FROM location WHERE hash = '" . $old_hash[$i] . "';
			";
		}
		
		$ret = $db->exec($sql_status);
		if(!$ret) {
			return $db->lastErrorMsg();
		}
		else {
			return $old_hash;
		}
	}
	
	function find_and_unset_reinserted_devices_status($db, $hash) {
		$sql = "SELECT hash FROM location WHERE hash = '" . $hash . "';";
		$results = $db->query($sql);
		
		while($res = $results->fetchArray(1)) {
			$location = $res["hash"];
		}
		
		if(empty($location)) {
			$sql_status .= "
				UPDATE disks SET
					status = 'h'
				WHERE hash = '" . $hash . "'
				;
			";
			
			$ret = $db->exec($sql_status);
			if(!$ret) {
				return $db->lastErrorMsg();
			}
			else {
				return $hash;
			}
		}
		else {
			return false;
		}
	}
	
	function force_set_removed_device_status($db, $hash) {
		$sql_status .= "
			UPDATE disks SET
				status = 'r'
			WHERE hash = '" . $hash . "'
			;
			DELETE FROM location WHERE hash = '" . $hash . "';
		";
		
		$ret = $db->exec($sql_status);
		if(!$ret) {
			return $db->lastErrorMsg();
		}
		else {
			return $hash;
		}
	}
	
	function force_undelete_devices($db, $action) {
		// r = read
		// m = modify
		
		if($action == 'r') {
			$sql_status = "SELECT COUNT(status) FROM disks where status='d';";
			$ret = $db->querySingle($sql_status);
		}
		if($action == 'm') {
			$sql_status = "
				UPDATE disks SET
					status='r'
				WHERE status='d'
				;
			";
			$ret = $db->exec($sql_status);
		}
		
		if(!$ret && $action == 'm') {
			return $db->lastErrorMsg();
		}
		else {
			return $ret;
		}
	}
	
	function force_reset_color($db, $hash) {
		if($hash == '*' || $hash == 'all') {
			$sql_status = "
				UPDATE disks SET
					color = ''
				;
			";
		}
		else {
			$sql_status .= "
				UPDATE disks SET
					color = ''
				;
				WHERE hash = '" . $hash . "';
			";
		}
		
		$ret = $db->exec($sql_status);
		if(!$ret) {
			return $db->lastErrorMsg();
		}
		else {
			return $hash;
		}
	}
	
	function array_duplicates($array) {
		return count(array_filter($array)) !== count(array_unique(array_filter($array)));
	}
	
	function recursive_array_search($needle,$haystack) {
		if(is_array($haystack)) {
			/* from php.net: buddel */
			foreach($haystack as $key=>$value) {
				$current_key=$key;
				if($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) {
					return $current_key;
				}
			}
		}
		return false;
	}
	
	function tray_number_assign($col, $row, $dir, $grid) {
		$total = $col * $row; // 6 = 3 * 2
		
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
	
	function dashboard_toggle($widget = 0) {
		$path = "" . EMHTTP_ROOT . "" . DISKLOCATION_PATH . "";
		
		if(file_exists("" . $path . "/disklocation_dashboard.page")) { 
			$widget_status = "on";
		}
		else {
			$widget_status = "off";
		}
		
		if($widget_status == "on" && !$widget) {
			rename($path . "/disklocation_dashboard.page", $path . "/disklocation_dashboard.page.off");
			$widget_status = "off";
		}
		if($widget_status == "off" && $widget) {
			rename($path . "/disklocation_dashboard.page.off", $path . "/disklocation_dashboard.page");
			$widget_status = "on";
		}
	}
	
	function update_scan_toggle($set = 0, $get_status = 0) {
		$path = "" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "";
		
		if(file_exists("" . $path . "/disklocation.noscan")) { 
			$status = 0;
			if($set == 1 && !$get_status) {
				unlink("" . $path . "/disklocation.noscan");
				$status = 1;
			}
		}
		else {
			$status = 1;
			if(!$set && !$get_status) {
				touch("" . $path . "/disklocation.noscan");
				$status = 0;
			}
		}
		
		return $status;
	}
	
	function cronjob_timer($time = "") {
		$curpath = "";
		$path = "/etc/cron.";
		$filename = "disklocation.sh";
		$md5sum = "be076c2fc24b9be95dede402a17fd4b4";
		
		define("DISKLOCATION_PATH", "/plugins/disklocation");
		define("EMHTTP_ROOT", "/usr/local/emhttp");
		
		if(file_exists($path . "hourly/" . $filename) && md5_file($path . "hourly/" . $filename) == $md5sum) {
			$curtime = "hourly";
			if($time && $time != "disabled" && $time != $curtime) {
				rename($path . "hourly/" . $filename, $path . "" . $time . "/" . $filename);
			}
			if($time == "disabled") {
				unlink($path . "hourly/" . $filename);
			}
		}
		if(file_exists($path . "daily/" . $filename) && md5_file($path . "daily/" . $filename) == $md5sum) {
			$curtime = "daily";
			if($time && $time != "disabled" && $time != $curtime) {
				rename($path . "daily/" . $filename, $path . "" . $time . "/" . $filename);
			}
			if($time == "disabled") {
				unlink($path . "daily/" . $filename);
			}
		}
		if(file_exists($path . "weekly/" . $filename) && md5_file($path . "weekly/" . $filename) == $md5sum) {
			$curtime = "weekly";
			if($time && $time != "disabled" && $time != $curtime) {
				rename($path . "weekly/" . $filename, $path . "" . $time . "/" . $filename);
			}
			if($time == "disabled") {
				unlink($path . "weekly/" . $filename);
			}
		}
		if(file_exists($path . "monthly/" . $filename) && md5_file($path . "monthly/" . $filename) == $md5sum) {
			$curtime = "monthly";
			if($time && $time != "disabled" && $time != $curtime) {
				rename($path . "monthly/" . $filename, $path . "" . $time . "/" . $filename);
			}
			if($time == "disabled") {
				unlink($path . "monthly/" . $filename);
			}
		}
		
		if(!$curtime && $time && $time != "disabled") {
			copy(EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/disklocation.cron", $path . "" . $time . "/" . $filename);
			chmod($path . "" . $time . "/" . $filename, 0777);
			$curtime = $time;
		}
		
		if(!$curtime) {
			$curtime = "disabled";
		}
		
		return ( $curtime ? $curtime : $time );
	}
	
	// lsscsi -bg
	function lsscsi_parser($input) {
		// \[(.+:.+:.+:.+)\]\s+(-|(\/dev\/(h|s)d[a-z]{1,})?)\s+((\/dev\/(nvme|sg)[0-9]{1,})(n[0-9]{1,})?)
		$pattern_device = "\[(.+:.+:.+:.+)\]\s+";				// $1
		$pattern_devnode = "(-|(\/dev\/(h|s)d[a-z]{1,})?)\s+";			// $3
		$pattern_scsigendevnode = "((\/dev\/(nvme|sg)[0-9]{1,})(n[0-9]{1,})?)";	// $5
			
		list($device, $devnode, $scsigendevnode) = 
			explode("|", preg_replace("/" . $pattern_device . "" . $pattern_devnode . "" . $pattern_scsigendevnode . "/iu", "$1|$3|$5", $input));
		
		return array(
			"device"	=> trim($device),
			"devnode"	=> str_replace("-", "", trim($devnode)),
			"sgnode"	=> trim($scsigendevnode)
		);
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
				$smart_rotation = "";
				break;
			default:
				$smart_rotation = $input . " RPM";
		}
		return $smart_rotation;
	}
	
	if(isset($_POST["delete"])) {
		$sql = "
			UPDATE disks SET
				status = 'd'
			WHERE hash = '" . $_POST["hash"] . "'
			;
		";
		
		$ret = $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
		}
		
		$db->close();
		
		//header("Location: " . DISKLOCATION_URL);
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATIONCONF_URL . "\" />");
		exit;
	}
	
	if(isset($_POST["remove"])) {
		if(!force_set_removed_device_status($db, $_POST["hash"])) { die("<p style=\"color: red;\">ERROR: Could not set status for the drive with hash: " . $_POST["hash"] . "</p>"); }
		
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATIONCONF_URL . "\" />");
		exit;
	}
	
	if(isset($_POST["add"])) {
		$sql = "
			UPDATE disks SET
				status = 'h'
			WHERE hash = '" . $_POST["hash"] . "'
			;
		";
		
		$ret = $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
		}
		
		$db->close();
		
		//header("Location: " . DISKLOCATION_URL);
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATIONCONF_URL . "\" />");
		exit;
	}
	
	if(isset($_POST["group_add"])) {
		$sql = "
			INSERT INTO settings_group(group_name) VALUES('');
		";
		
		$ret = $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
		}
		
		$db->close();
		
		//header("Location: " . DISKLOCATIONCONF_URL);
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATIONCONF_URL . "\" />");
		exit;
	}
	if(isset($_POST["group_del"])) {
		$sql = "
			DELETE FROM settings_group WHERE id = (SELECT MAX(id) FROM settings_group);
			DELETE FROM location WHERE groupid = '" . $_POST["last_group_id"] . "';
		";
		
		$ret = $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
		}
		
		$db->close();
		
		//header("Location: " . DISKLOCATION_URL);
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATIONCONF_URL . "\" />");
		exit;
	}
	
	if($_POST["save_settings"]) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE SETTINGS has been pressed.");
		$sql = "";
		
		$post_info = json_encode($_POST["displayinfo"]);
		
		// settings
		if(!preg_match("/[0-9]{1,5}/", $_POST["smart_exec_delay"])) { $disklocation_error[] = "SMART execution delay missing or invalid number."; }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_parity"])) { $disklocation_error[] = "Background color for \"Parity\" invalid."; } else { $_POST["bgcolor_parity"] = str_replace("#", "", $_POST["bgcolor_parity"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_unraid"])) { $disklocation_error[] = "Background color for \"Data\" invalid."; } else { $_POST["bgcolor_unraid"] = str_replace("#", "", $_POST["bgcolor_unraid"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_cache"])) { $disklocation_error[] = "Background color for \"Cache\" invalid."; } else { $_POST["bgcolor_cache"] = str_replace("#", "", $_POST["bgcolor_cache"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_others"])) { $disklocation_error[] = "Background color for \"Unassigned devices\" invalid."; } else { $_POST["bgcolor_others"] = str_replace("#", "", $_POST["bgcolor_others"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_empty"])) { $disklocation_error[] = "Background color for \"Empty trays\" invalid."; } else { $_POST["bgcolor_empty"] = str_replace("#", "", $_POST["bgcolor_empty"]); }
		if(!preg_match("/(u|m)/", $_POST["warranty_field"])) { $disklocation_error[] = "Warranty field is invalid."; }
		if(!preg_match("/[0-9]{1,4}/", $_POST["dashboard_widget_pos"])) { $disklocation_error[] = "Dashboard widget position invalid."; }
		
		/*
		$dashboard_widget_array = dashboard_toggle($_POST["dashboard_widget"], $_POST["dashboard_widget_pos"]);
		$dashboard_widget = $dashboard_widget_array["current"];
		$dashboard_widget_pos = $dashboard_widget_array["position"];
		*/
		cronjob_timer($_POST["smart_updates"]);
		update_scan_toggle($_POST["plugin_update_scan"]);
		
		if(empty($disklocation_error)) {
			$sql .= "
				REPLACE INTO
					settings(
						id,
						smart_exec_delay,
						smart_updates,
						bgcolor_parity,
						bgcolor_unraid,
						bgcolor_cache,
						bgcolor_others,
						bgcolor_empty,
						tray_reduction_factor,
						warranty_field,
						dashboard_widget,
						dashboard_widget_pos,
						displayinfo
					)
					VALUES(
						'1',
						'" . $_POST["smart_exec_delay"] . "',
						'" . $_POST["smart_updates"] . "',
						'" . $_POST["bgcolor_parity"] . "',
						'" . $_POST["bgcolor_unraid"] . "',
						'" . $_POST["bgcolor_cache"] . "',
						'" . $_POST["bgcolor_others"] . "',
						'" . $_POST["bgcolor_empty"] . "',
						'" . $_POST["tray_reduction_factor"] . "',
						'" . $_POST["warranty_field"] . "',
						'" . $_POST["dashboard_widget"] . "',
						'" . $_POST["dashboard_widget_pos"] . "',
						'" . $post_info . "'
					)
				;
			";
			
			debug_print($debugging_active, __LINE__, "SQL", "SETTINGS: <pre>" . $sql . "</pre>");
			
			$ret = $db->exec($sql);
			if(!$ret) {
				echo $db->lastErrorMsg();
			}
		}
	}
	
	if($_POST["save_groupsettings"] && $_POST["groupid"]) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE GROUP SETTINGS has been pressed.");
		$sql = "";
		
		// settings
		if(!preg_match("/\b(column|row)\b/", $_POST["grid_count"])) { $disklocation_error[] = "Physical tray assignment invalid."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["grid_columns"])) { $disklocation_error[] = "Grid columns missing or number invalid."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["grid_rows"])) { $disklocation_error[] = "Grid rows missing or number invalid."; }
		if($_POST["grid_trays"] && !preg_match("/[0-9]{1,3}/", $_POST["grid_trays"])) { $disklocation_error[] = "Grid trays number invalid."; }
		if(!preg_match("/(h|v)/", $_POST["disk_tray_direction"])) { $disklocation_error[] = "Physical tray direction invalid."; }
		if(!preg_match("/[0-9]{1}/", $_POST["tray_direction"])) { $disklocation_error[] = "Tray number direction invalid."; }
		if(!preg_match("/[0-9]{1,4}/", $_POST["tray_width"])) { $disklocation_error[] = "Tray's longest side outside limits or invalid number entered."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["tray_height"])) { $disklocation_error[] = "Tray's smallest side outside limits or invalid number entered."; }
		
		if(empty($disklocation_error)) {
			$sql .= "
				UPDATE settings_group SET
					group_name = '" . $_POST["group_name"] . "',
					grid_count = '" . $_POST["grid_count"] . "',
					grid_columns = '" . $_POST["grid_columns"] . "',
					grid_rows = '" . $_POST["grid_rows"] . "',
					grid_trays = '" . ( empty($_POST["grid_trays"]) ? null : $_POST["grid_trays"] ) . "',
					disk_tray_direction = '" . $_POST["disk_tray_direction"] . "',
					tray_direction = '" . $_POST["tray_direction"] . "',
					tray_width = '" . $_POST["tray_width"] . "',
					tray_height = '" . $_POST["tray_height"] . "'
				WHERE id = '" . $_POST["groupid"] . "';
				;
			";
			
			debug_print($debugging_active, __LINE__, "SQL", "GROUP SETTINGS: <pre>" . $sql . "</pre>");
			
			$ret = $db->exec($sql);
			if(!$ret) {
				echo $db->lastErrorMsg();
			}
		}
	}

	if($_POST["save_allocations"]) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE ALLOCATIONS has been pressed.");
		// trays
		$sql = "";
		$post_drives = $_POST["drives"];
		$post_groups = $_POST["groups"];
		$post_empty = $_POST["empty"];
		
		if(empty($disklocation_error)) {
			$keys_drives = array_keys($post_drives);
			for($i=0; $i < count($keys_drives); ++$i) {
				$tray_assign = ( empty($post_drives[$keys_drives[$i]]) ? null : $post_drives[$keys_drives[$i]] );
				$group_assign = ( empty($post_groups[$keys_drives[$i]]) ? null : $post_groups[$keys_drives[$i]] );
				
				if(!$tray_assign || !$group_assign) {
					$sql .= "
						UPDATE disks SET
							status = 'h'
						WHERE hash = '" . $keys_drives[$i] . "'
						;
						DELETE FROM location
							WHERE tray = '" . $tray_assign . "' AND groupid = '" . $group_assign . "'
						;
					";
				}
				else {
					$sql .= "
						INSERT INTO
							location(
								hash,
								tray,
								groupid
							)
							VALUES(
								'" . $keys_drives[$i] . "',
								'" . $tray_assign . "',
								'" . $group_assign . "'
							)
							ON CONFLICT(hash) DO UPDATE SET
								tray='" . $tray_assign . "',
								groupid='" . $group_assign . "'
						;
						UPDATE disks SET
							status = NULL
						WHERE hash = '" . $keys_drives[$i] . "'
						;
					";
				}
			}
			
			debug_print($debugging_active, __LINE__, "SQL", "ALLOC: <pre>" . $sql . "</pre>");
			
			$ret = $db->exec($sql);
			if(!$ret) {
				echo $db->lastErrorMsg();
			}
			
			$sql = "";
			
			// Remove conflicting tray allocations, use only the newest assigned tray
			$sql = "SELECT id FROM location GROUP BY groupid,tray HAVING COUNT(*) > 1;";
			$results = $db->query($sql);
			
			while($res = $results->fetchArray(1)) {
				$sql_del = "DELETE FROM location WHERE id = '" . $res["id"] . "';";
				$ret = $db->exec($sql_del);
				if(!$ret) {
					return $db->lastErrorMsg();
				}
			}
			$sql = "";
			
			for($i=0; $i < count($keys_drives); ++$i) {
				$sql .= "
					UPDATE disks SET
						purchased = '" . $_POST["purchased"][$keys_drives[$i]] . "',
				";
				if($_POST["current_warranty_field"] == "u") {
					$sql .= "warranty = '" . $_POST["warranty"][$keys_drives[$i]] . "',";
				}
				else {
					$sql .= "warranty_date = '" . $_POST["warranty_date"][$keys_drives[$i]] . "',";
				}
				$sql .= "
						comment = '" . $_POST["comment"][$keys_drives[$i]] . "'
					";
				if(!in_array(str_replace("#", "", $_POST["bgcolor_custom"][$keys_drives[$i]]), array($bgcolor_parity, $bgcolor_unraid, $bgcolor_cache, $bgcolor_others, $bgcolor_empty))) {
					$sql .= ", color = '" . str_replace("#", "", $_POST["bgcolor_custom"][$keys_drives[$i]]) . "'";
				}
				else {
					$sql .= ", color = ''";
				}
				$sql .= "
					WHERE hash = '" . $keys_drives[$i] . "'
					;
				";
			}
			
			debug_print($debugging_active, __LINE__, "SQL", "POPULATED: <pre>" . $sql . "</pre>");
			
			$ret = $db->exec($sql);
			if(!$ret) {
				echo $db->lastErrorMsg();
			}
		}
	}
	
	// get all attached SCSI drives - usually should grab all local drives available
	//$lsscsi_cmd = shell_exec("lsscsi -u -g");
	$lsscsi_cmd = shell_exec("lsscsi -b -g");
	$lsscsi_arr = explode(PHP_EOL, $lsscsi_cmd);
	
	// add and update disk info
	if($_POST["force_smart_scan"] || $_GET["force_smart_scan"] || $disklocation_new_install || $argv[1] == "force") {
		$force_scan = 1; // trigger force_smart_scan post if it is a new install or if it is forced at CLI
	}
	
	if($_GET["force_smart_scan"]) {
		$debugging_active = 3;
	}
	
	if($force_scan || $argv[1] == "cronjob") {
		if($_GET["force_smart_scan"]) {
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
				<p>
					<b>Scanning drives, please wait until it is completed...</b>
				</p>
				<p class=\"mono\">
			");
		}
		
		// wait until the cronjob has finished.
		$pid_cron_script = trim(shell_exec("pgrep -f disklocation.sh"));
		while(!empty(trim(shell_exec("pgrep -f disklocation.sh"))) && $force_scan) {
			$retry_delay = 5;
			debug_print($debugging_active, __LINE__, "delay", "PGREP: Cronjob (PID:$pid_cron_script) running, retrying every $retry_delay secs...");
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
				$smart_check_operation = shell_exec("smartctl -n standby $lsscsi_devicenodesg[$i] | egrep 'ACTIVE|IDLE'");
				usleep($smart_exec_delay . 000); // delay script to get the output of the next shell_exec()
				
				if(!empty($smart_check_operation) || $force_scan) { // only get SMART data if the disk is spinning, if it is a new install/empty database, or if scan is forced.
					if(!$force_scan) {
						$smart_standby_cmd = "-n standby";
					}
					$smart_cmd[$i] = shell_exec("smartctl $smart_standby_cmd -x --json $lsscsi_devicenodesg[$i]"); // get all SMART data for this device, we grab it ourselves to get all drives also attached to hardware raid cards.
					$smart_array = json_decode($smart_cmd[$i], true);
					debug_print($debugging_active, __LINE__, "SMART", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "=" . ( is_array($smart_array) ? "array" : "empty" ) . "");
					
					$smart_i=0;
					$smart_loadcycle_find = "";
					if(is_array($smart_array["ata_smart_attributes"]["table"])) {
						while($smart_i < count($smart_array["ata_smart_attributes"]["table"])) {
							if($smart_array["ata_smart_attributes"]["table"][$smart_i]["name"] == "Load_Cycle_Count") {
								$smart_loadcycle_find = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
								$smart_i = count($smart_array["ata_smart_attributes"]["table"]);
							}
							$smart_i++;
						}
					}
					
					// Only check for SSD if rotation_rate doesn't exists.
					if(!$smart_array["rotation_rate"]) {
						$smart_array["rotation_rate"] = ( recursive_array_search("Solid State Device Statistics", $smart_array) ? -1 : $smart_array["rotation_rate"] );
						if($smart_array["device"]["type"] == "nvme") {
							$smart_array["rotation_rate"] = -2;
						}
					}
					$deviceid[$i] = hash('sha256', $smart_array["model_name"] . $smart_array["serial_number"]);
					
					debug_print($debugging_active, __LINE__, "HASH", "#:" . $i . ":" . $deviceid[$i] . "");
					
					find_and_unset_reinserted_devices_status($db, $deviceid[$i]);	// tags old existing devices with 'null', delete device from location just in case it for whatever reason it already exists.
					
					if($smart_array["serial_number"] && $smart_array["model_name"]) {
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
									smart_rotation,
									smart_formfactor,
									status,
									hash
								)
								VALUES(
									'" . $lsscsi_device[$i] . "',
									'" . $lsscsi_devicenode[$i] . "',
									'" . $smart_array["wwn"]["naa"] . " " . $smart_array["wwn"]["oui"] . " " . $smart_array["wwn"]["id"] . "',
									'" . $smart_array["model_family"] . "',
									'" . $smart_array["model_name"] . "',
									'" . $smart_array["smart_status"]["passed"] . "',
									'" . $smart_array["serial_number"] . "',
									'" . $smart_array["temperature"]["current"] . "',
									'" . $smart_array["power_on_time"]["hours"] . "',
									'" . $smart_loadcycle_find . "',
									'" . $smart_array["user_capacity"]["bytes"] . "',
									'" . $smart_array["rotation_rate"] . "',
									'" . $smart_array["form_factor"]["name"] . "',
									'h',
									'" . $deviceid[$i] . "'
								)
								ON CONFLICT(hash) DO UPDATE SET
									device='" . $lsscsi_device[$i] . "',
									devicenode='" . $lsscsi_devicenode[$i] . "',
									luname='" . $smart_array["wwn"]["naa"] . " " . $smart_array["wwn"]["oui"] . " " . $smart_array["wwn"]["id"] . "',
									model_family='" . $smart_array["model_family"] . "',
									smart_status='" . $smart_array["smart_status"]["passed"] . "',
									smart_temperature='" . $smart_array["temperature"]["current"] . "',
									smart_powerontime='" . $smart_array["power_on_time"]["hours"] . "',
									smart_loadcycle='" . $smart_loadcycle_find . "',
									smart_rotation='" . $smart_array["rotation_rate"] . "'
								WHERE hash='" . $deviceid[$i] . "';
						";
						
						if(is_array($unraid_disklog["" . str_replace(" ", "_", $smart_array["model_name"]) . "_" . str_replace(" ", "_", $smart_array["serial_number"]) . ""])) {
							$sql .= "
								UPDATE disks SET
									purchased='" . $unraid_disklog["" . str_replace(" ", "_", $smart_array["model_name"]) . "_" . str_replace(" ", "_", $smart_array["serial_number"]) . ""]["purchase"] . "',
									warranty='" . $unraid_disklog["" . str_replace(" ", "_", $smart_array["model_name"]) . "_" . str_replace(" ", "_", $smart_array["serial_number"]) . ""]["warranty"] . "'
								WHERE hash = '" . $deviceid[$i] . "'
								;
							";
						}
						
						debug_print($debugging_active, __LINE__, "SQL", "#:" . $i . ":<pre>" . $sql . "</pre>");
						
						$ret = $db->exec($sql);
						if(!$ret) {
							echo $db->lastErrorMsg();
						}
					}
					else {
						debug_print($debugging_active, __LINE__, "SQL", "#:" . $i . ":<pre>Invalid SMART information, skipping...</pre>");
					}
				}
				
				unset($smart_array);
			}
			$i++;
		}
		// check the existence of devices, must be run during force smart scan.
		if($force_scan) {
			find_and_set_removed_devices_status($db, $deviceid); 		// tags removed devices 'r', delete device from location
		}
		
		if($_GET["force_smart_scan"]) {
			print("
				</p>
				<p>
					<b>Scanning has completed, refreshing within 3 seconds...
<!-- press \"Done\" and refresh the page.</b>
</p>
<p style=\"text-align: center;\">
	<button type=\"button\" onclick=\"top.Shadowbox.close()\">Done</button>
</p>
-->
				</p>
				<script type=\"text/javascript\">
					function sleep (time) {
						return new Promise((resolve) => setTimeout(resolve, time));
					}
					sleep(3000).then(() => {
						window.top.location = '" . DISKLOCATIONCONF_URL . "';
					})
				</script>
			");
		}
	}
	
	if($_POST["reset_all_colors"]) {
		force_reset_color($db, "*");
	}
	
// Common config
	$unraid_disks = array();
	
	// get configured Unraid disks
	if(is_file(DISKINFORMATION)) {
		$unraid_disks_import = parse_ini_file(DISKINFORMATION, true);
		$unraid_disks = array_values($unraid_disks_import);
	}

	// modify the array to suit our needs
	$unraid_array = array();
	$i=0;
	while($i < count($unraid_disks)) {
		$getdevicenode = $unraid_disks[$i]["device"];
		if($getdevicenode) {
			$unraid_array[$getdevicenode] = array(
				"name" => $unraid_disks[$i]["name"],
				"device" => $unraid_disks[$i]["device"],
				"status" => $unraid_disks[$i]["status"],
				"type" => $unraid_disks[$i]["type"],
				"temp" => $unraid_disks[$i]["temp"],
				"color" => $unraid_disks[$i]["color"],
				"fscolor" => $unraid_disks[$i]["fsColor"]
			);
		}
		$i++;
	}
	
	// get disk logs
	if(is_file(DISKLOGFILE)) {
		$unraid_disklog = parse_ini_file(DISKLOGFILE, true);
	}
	
	// get settings from DB as $var
	
	$sql = "SELECT * FROM settings";
	$results = $db->query($sql);
	
	while($data = $results->fetchArray(1)) {
		extract($data);
	}
	
	$displayinfo = json_decode($displayinfo, true);
	
	dashboard_toggle($dashboard_widget_pos);
	cronjob_timer($smart_updates);
	
	$color_array = array();
	$color_array["empty"] = $bgcolor_empty;
	
// Group config
	$sql = "SELECT * FROM settings_group ORDER BY id ASC";
	$results = $db->query($sql);
	
	while($data_group = $results->fetchArray(1)) {
		foreach($data_group as $key=>$value) {
			$group[$data_group["id"]][$key] = "".$value."";
		}
	}
	
	$sql = "SELECT id FROM settings_group GROUP BY id;";
	$results = $db->query($sql);
	while($data = $results->fetchArray(1)) {
		 $count_groups[] = $data["id"];
	}
	$total_groups = ( is_array($count_groups) ? count($count_groups) : 0 );
	
	$sql = "SELECT id FROM settings_group ORDER BY id DESC limit 1;";
	$results = $db->query($sql);
	while($data = $results->fetchArray(1)) {
		 $last_group_id = $data["id"];
	}
	
	/*
	print_r($group);
	print(count($group));
	die();
	*/
?>
