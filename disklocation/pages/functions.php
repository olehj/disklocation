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
	// Set to 1|2|3 to enable debugging:
	$debugging_active = 0;
	
	// Set warning level
	//error_reporting(E_ERROR | E_WARNING | E_PARSE);
	error_reporting(E_ERROR);
	
	$get_page_info = array();
	$get_page_info["Version"] = "";
	$get_page_info = parse_ini_file("/usr/local/emhttp/plugins/disklocation/disklocation.page");
	
	// define constants
	define("UNRAID_CONFIG_PATH", "/boot/config");
	define("DISKLOCATION_DB", "/boot/config/plugins/disklocation/disklocation.sqlite");
	define("DISKINFORMATION", "/var/local/emhttp/disks.ini");
	define("DISKLOGFILE", "/boot/config/disk.log");
	define("DISKLOCATION_VERSION", $get_page_info["Version"]);
	define("DISKLOCATION_URL", "/Settings/disklocation");
	define("DISKLOCATIONCONF_URL", "/Settings/disklocation");
	define("DISKLOCATION_PATH", "/plugins/disklocation");
	define("EMHTTP_ROOT", "/usr/local/emhttp");
	define("CRONJOB_URL", DISKLOCATION_PATH . "/pages/cron_disklocation.php");
	define("CRONJOB_FILE", EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/pages/cron_disklocation.php");
	define("EMHTTP_VAR", "/var/local/emhttp");
	define("UNRAID_DISKS_FILE", "disks.ini");
	define("UNRAID_DEVS_FILE", "devs.ini");
	define("SMART_ALL_FILE", "smart-all.cfg");
	define("SMART_ONE_FILE", "smart-one.cfg");
	
	if(is_file(EMHTTP_VAR . "/" . UNRAID_DISKS_FILE)) {
		$unraid_disks = parse_ini_file(EMHTTP_VAR . "/" . UNRAID_DISKS_FILE, true);
	}
	if(is_file(EMHTTP_VAR . "/" . UNRAID_DEVS_FILE)) {
		$unraid_devs = parse_ini_file(EMHTTP_VAR . "/" . UNRAID_DEVS_FILE, true);
	}
	if(is_file(UNRAID_CONFIG_PATH . "/" . SMART_ALL_FILE)) {
		$unraid_smart_all = parse_ini_file(UNRAID_CONFIG_PATH . "/" . SMART_ALL_FILE, true);
	}
	if(is_file(UNRAID_CONFIG_PATH . "/" . SMART_ONE_FILE)) {
		$unraid_smart_one = parse_ini_file(UNRAID_CONFIG_PATH . "/" . SMART_ONE_FILE, true);
	}
	
	$disklocation_error = array();
	$disklocation_new_install = 0;
	$group = array();
	$unraid_disklog = array();
	$installed_drives = array();
	
	global $unraid, $GLOBALS;
	
	if(!isset($argv)) {
		$argv = array();
	}
	
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
	
	$sql_status = "";

	// get Unraid disks
	$get_global_smType = ( isset($unraid_smart_all["smType"]) ? $unraid_smart_all["smType"] : null );
	
	$unraid_devs = array_values(array_merge($unraid_disks, $unraid_devs));
	
	// modify the array to suit our needs
	
	$unraid_array = array();
	//$unraid_unassigned = array();
	$smart_controller_devs = array();
	
	$i=0;
	while($i < count($unraid_devs)) {
		$getdevicenode = $unraid_devs[$i]["device"];
		$getdeviceid = $unraid_devs[$i]["id"];
		
		if(!isset($unraid_smart_one[$getdeviceid]["hotTemp"])) { 
			$unraid_smart_one[$getdeviceid]["hotTemp"] = 0;
		}
		if(!isset($unraid_smart_one[$getdeviceid]["maxTemp"])) { 
			$unraid_smart_one[$getdeviceid]["maxTemp"] = 0;
		}
		
		$smart_controller_devs[$i] = "" . ( isset($unraid_smart_one[$getdeviceid]["smType"]) ? $unraid_smart_one[$getdeviceid]["smType"] : $get_global_smType ) . "" . ( isset($unraid_smart_one[$getdeviceid]["smPort1"]) ? "," . $unraid_smart_one[$getdeviceid]["smPort1"] : null ) . "" . ( isset($unraid_smart_one[$getdeviceid]["smPort2"]) ? $unraid_smart_one[$getdeviceid]["smGlue"] . "" . $unraid_smart_one[$getdeviceid]["smPort2"] : null ) . "" . ( isset($unraid_smart_one[$getdeviceid]["smPort3"]) ? $unraid_smart_one[$getdeviceid]["smGlue"] . "" . $unraid_smart_one[$getdeviceid]["smPort3"] : null ) . "" . ( isset($unraid_smart_one[$getdeviceid]["smDevice"]) ? " /dev/" . $unraid_smart_one[$getdeviceid]["smDevice"] : null ) . "";
		
		if($getdevicenode) {
			$unraid_array[$getdevicenode] = array(
				"name" => ($unraid_devs[$i]["name"] ?? null),
				"device" => ($unraid_devs[$i]["device"] ?? null),
				"status" => ($unraid_devs[$i]["status"] ?? null),
				"type" => ($unraid_devs[$i]["type"] ?? null),
				"temp" => ($unraid_devs[$i]["temp"] ?? null),
				"hotTemp" => ($unraid_smart_one[$getdeviceid]["hotTemp"] ?? null),
				"maxTemp" => ($unraid_smart_one[$getdeviceid]["maxTemp"] ?? null),
				"color" => ($unraid_devs[$i]["color"] ?? null),
				"fscolor" => ($unraid_devs[$i]["fsColor"] ?? null),
				"smart_controller_cmd" => ($smart_controller_devs[$i] ?? null)
			);
		}
		$i++;
	}
	
	// get all attached SCSI drives - usually should grab all local drives available
	//$lsscsi_cmd = shell_exec("lsscsi -u -g");
	$lsscsi_cmd = shell_exec("lsscsi -b -g");
	$lsscsi_arr = explode(PHP_EOL, $lsscsi_cmd);
	
	// get disk logs
	if(is_file(DISKLOGFILE)) {
		$unraid_disklog = parse_ini_file(DISKLOGFILE, true);
	}
	
	if(in_array("cronjob", $argv) || in_array("force", $argv)) {
		if(!isset($argv[2])) { 
			$debugging_active = 0;
		}
		set_time_limit(600); // set to 10 minutes.
	}
	
	function debug_print($act, $line, $section, $message) {
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
		$text = preg_replace("/\[br\]/", "<br />", $text);
		
		if($text) {
			return $text;
		}
		else {
			return false;
		}
	}
	
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
	
	function smart_units_to_bytes($units) {
		return $units * 512 * 1024;
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
	
	function get_unraid_disk_status($color, $type = '', $output = '') {
		switch($color) {
			case 'green-on': $orb = 'circle'; $color = 'green'; $help = 'Normal operation, device is active'; break;
			case 'green-blink': $orb = 'circle'; $color = 'grey'; $help = 'Device is in standby mode (spun-down)'; break;
			case 'blue-on': $orb = 'square'; $color = 'blue'; $help = 'New device'; break;
			case 'blue-blink': $orb = 'square'; $color = 'grey'; $help = 'New device, in standby mode (spun-down)'; break;
			case 'yellow-on': $orb = 'warning'; $color = 'yellow'; $help = $type =='Parity' ? 'Parity is invalid' : 'Device contents emulated'; break;
			case 'yellow-blink': $orb = 'warning'; $color = 'grey'; $help = $type =='Parity' ? 'Parity is invalid, in standby mode (spun-down)' : 'Device contents emulated, in standby mode (spun-down)'; break;
			case 'red-on': $orb = 'times'; $color = 'red'; $help = $type=='Parity' ? 'Parity device is disabled' : 'Device is disabled, contents emulated'; break;
			case 'red-blink': $orb = 'times'; $color = 'red'; $help = $type=='Parity' ? 'Parity device is disabled' : 'Device is disabled, contents emulated'; break;
			case 'red-off': $orb = 'times'; $color = 'red'; $help = $type =='Parity' ? 'Parity device is missing' : 'Device is missing (disabled), contents emulated'; break;
			case 'grey-off': $orb = 'square'; $color = 'grey'; $help = 'Device not present'; break;
			// ZFS values
			case 'ONLINE': $orb = 'circle'; $color = 'green'; $help = 'Normal operation, device is online'; break;
			case 'FAULTED': $orb = 'warning'; $color = 'yellow'; $help = 'Device has faulted'; break;
			case 'DEGRADED': $orb = 'warning'; $color = 'yellow'; $help = 'Device is degraded'; break;
			case 'UNAVAIL': $orb = 'times'; $color = 'red'; $help = 'Device is unavailable'; break;
			case 'OFFLINE': $orb = 'times'; $color = 'red'; $help = 'Device is offline'; break;
		}
		
		if(!$output) {
			return ("<a class='info'><i class='fa fa-$orb orb-disklocation $color-orb-disklocation'></i><span>$help</span></a>");
		}
		else if($output == "color") {
			return $color;
		}
	}
	
	function zfs_check() {
		if(is_file("/usr/sbin/zpool")) {
			if(preg_match("/\bstate\b/i", ( shell_exec("/usr/sbin/zpool status") ? shell_exec("/usr/sbin/zpool status") : "none" ))) {
				return 1;
			}
		}
		else {
			return 0;
		}
	}
	
	function zfs_parser() {
		if(zfs_check()) {
			$str = shell_exec("/usr/sbin/zpool status");
			
			$pattern = "/((pool|state|scan|errors): (.*)?\n|(config):[\s]+(.*)?\s\n)/Uis";
			preg_match_all($pattern, $str, $matches, PREG_SET_ORDER);
			
			$result = array();

			foreach($matches as $match) {
				$length = count($match);
				$result[$match[$length-2]] = $match[$length-1];
			}
			
			return $result;
		}
		else {
			return false;
		}
	}
	
	function zfs_disk($disk) {
		if(zfs_check()) {
			$zfs_config = zfs_parser();
			$disks = explode("\n", $zfs_config["config"]);
			// Array $match: 0 = disk-by-id | 1 = state | 2 = read | 3 = write | 4 = cksum
			for($i=0; $i < count($disks); ++$i) {
				if(preg_match("/" . $disk . "/", $disks[$i])) {
					return explode(":", preg_replace("/\s+/", ":", trim($disks[$i])));
				}
			}
		}
		else {
			return false;
		}
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
		
		$sql_status = "";
		
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
		
		$sql_status = "";
		
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
			WHERE hash = '" . SQLite3::escapeString($hash) . "'
			;
			DELETE FROM location WHERE hash = '" . SQLite3::escapeString($hash) . "';
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
/*
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
*/	
	function cronjob_timer($time) {
		$path = "/etc/cron.";
		$filename = "disklocation.sh";
		
		if(file_exists($path . "hourly/" . $filename)) unlink($path . "hourly/" . $filename);
		if(file_exists($path . "daily/" . $filename)) unlink($path . "daily/" . $filename);
		if(file_exists($path . "weekly/" . $filename)) unlink($path . "weekly/" . $filename);
		if(file_exists($path . "monthly/" . $filename)) unlink($path . "monthly/" . $filename);
		
		$cron_cmd = "php " . CRONJOB_FILE . " cronjob silent";
		if($time != "disabled") {
			file_put_contents($path . "" . $time . "/" . $filename, $cron_cmd);
			chmod($path . "" . $time . "/" . $filename, 0777);
		}
	}
	function cronjob_current() {
		$path = "/etc/cron.";
		$filename = "disklocation.sh";
		
		if(file_exists($path . "hourly/" . $filename)) return "hourly";
		if(file_exists($path . "daily/" . $filename)) return "daily";
		if(file_exists($path . "weekly/" . $filename)) return "weekly";
		if(file_exists($path . "monthly/" . $filename)) return "monthly";
		else return "disabled";
	}
	
	function cronjob_runfile_updater() {
		$path = "/etc/cron.";
		$filename = "disklocation.sh";
		$current = cronjob_current();
		
		if($current != "disabled") {
			if(file_exists($path . "" . $current . "/" . $filename)) {
				$contents = file_get_contents($path . "" . $current . "/" . $filename);
				if(preg_match("/disklocation\?crontab/", $contents)) {
					cronjob_timer($current);
				}
			}
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
				$smart_rotation = "";
				break;
			default:
				$smart_rotation = $input . " RPM";
		}
		return $smart_rotation;
	}
?>
