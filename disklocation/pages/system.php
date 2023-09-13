<?php
	/*
	 *  Copyright 2019-2023, Ole-Henrik Jakobsen
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
	
	// get disk logs
	if(is_file(DISKLOGFILE)) {
		$unraid_disklog = parse_ini_file(DISKLOGFILE, true);
	}
	
	if(in_array("cronjob", $argv) || in_array("force", $argv)) {
		if(!isset($argv[2])) { 
			$debugging_active = 2;
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
*/	
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
	function cronjob_timer($time, $url) {
		$path = "/etc/cron.";
		$filename = "disklocation.sh";
		
		if(file_exists($path . "hourly/" . $filename)) unlink($path . "hourly/" . $filename);
		if(file_exists($path . "daily/" . $filename)) unlink($path . "daily/" . $filename);
		if(file_exists($path . "weekly/" . $filename)) unlink($path . "weekly/" . $filename);
		if(file_exists($path . "monthly/" . $filename)) unlink($path . "monthly/" . $filename);
		
		$cron_cmd = "wget -q --delete-after " . $url . "/Settings/disklocation?crontab=1";
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
				if(preg_match("/system\.php cronjob silent/", $contents)) {
					cronjob_timer($current, $GLOBALS["nginx"]["NGINX_DEFAULTURL"]);
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
	
	if(isset($_POST["delete"])) {
		$sql = "
			UPDATE disks SET
				status = 'd'
			WHERE hash = '" . SQLite3::escapeString($_POST["hash"]) . "'
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
			WHERE hash = '" . SQLite3::escapeString($_POST["hash"]) . "'
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
			DELETE FROM location WHERE groupid = '" . SQLite3::escapeString($_POST["last_group_id"]) . "';
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
	
	if(isset($_POST["save_settings"])) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE SETTINGS has been pressed.");
		$sql = "";
		
		if(isset($_POST["displayinfo"])) {
			$post_info = json_encode($_POST["displayinfo"]);
		}
		else {
			$post_info = "";
		}
		
		// settings
		if(!preg_match("/[0-9]{1,5}/", $_POST["smart_exec_delay"])) { $disklocation_error[] = "SMART execution delay missing or invalid number."; }
		if(!preg_match("/(hourly|daily|weekly|monthly|disabled)/", $_POST["smart_updates"])) { $disklocation_error[] = "Invalid data for SMART updates."; }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_parity"])) { $disklocation_error[] = "Background color for \"Parity\" invalid."; } else { $_POST["bgcolor_parity"] = str_replace("#", "", $_POST["bgcolor_parity"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_unraid"])) { $disklocation_error[] = "Background color for \"Data\" invalid."; } else { $_POST["bgcolor_unraid"] = str_replace("#", "", $_POST["bgcolor_unraid"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_cache"])) { $disklocation_error[] = "Background color for \"Cache\" invalid."; } else { $_POST["bgcolor_cache"] = str_replace("#", "", $_POST["bgcolor_cache"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_others"])) { $disklocation_error[] = "Background color for \"Unassigned devices\" invalid."; } else { $_POST["bgcolor_others"] = str_replace("#", "", $_POST["bgcolor_others"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_empty"])) { $disklocation_error[] = "Background color for \"Empty trays\" invalid."; } else { $_POST["bgcolor_empty"] = str_replace("#", "", $_POST["bgcolor_empty"]); }
		if(!is_numeric($_POST["tray_reduction_factor"])) { $disklocation_error[] = "The size divider is not numeric."; }
		if(!preg_match("/(u|m)/", $_POST["warranty_field"])) { $disklocation_error[] = "Warranty field is invalid."; }
		if(!preg_match("/[0-9]{1,4}/", $_POST["dashboard_widget_pos"])) { $disklocation_error[] = "Dashboard widget position invalid."; }
		
		/*
		$dashboard_widget_array = dashboard_toggle($_POST["dashboard_widget"], $_POST["dashboard_widget_pos"]);
		$dashboard_widget = $dashboard_widget_array["current"];
		$dashboard_widget_pos = $dashboard_widget_array["position"];
		*/
		cronjob_timer($_POST["smart_updates"],$_POST["smart_updates_url"]);
		//update_scan_toggle($_POST["plugin_update_scan"]);
		
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
						'" . SQLite3::escapeString($_POST["dashboard_widget"] ?? null) . "',
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
	
	if(isset($_POST["save_groupsettings"]) && isset($_POST["groupid"])) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE GROUP SETTINGS has been pressed.");
		$sql = "";
		
		// settings
		if(!preg_match("/\b(column|row)\b/", $_POST["grid_count"])) { $disklocation_error[] = "Physical tray assignment invalid."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["grid_columns"])) { $disklocation_error[] = "Grid columns missing or number invalid."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["grid_rows"])) { $disklocation_error[] = "Grid rows missing or number invalid."; }
		if($_POST["grid_trays"] && !preg_match("/[0-9]{1,3}/", $_POST["grid_trays"])) { $disklocation_error[] = "Grid trays number invalid."; }
		if(!preg_match("/(h|v)/", $_POST["disk_tray_direction"])) { $disklocation_error[] = "Physical tray direction invalid."; }
		if(!preg_match("/[0-9]{1}/", $_POST["tray_direction"])) { $disklocation_error[] = "Tray number direction invalid."; }
		if(!preg_match("/[0-9]{1,7}/", $_POST["tray_start_num"])) { $disklocation_error[] = "Tray start number invalid."; }
		if(!preg_match("/[0-9]{1,4}/", $_POST["tray_width"])) { $disklocation_error[] = "Tray's longest side outside limits or invalid number entered."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["tray_height"])) { $disklocation_error[] = "Tray's smallest side outside limits or invalid number entered."; }
		if(!preg_match("/[0-9]{1,}/", $_POST["groupid"])) { $disklocation_error[] = "Expected group ID to be an integer."; }
		
		if(empty($disklocation_error)) {
			$sql .= "
				UPDATE settings_group SET
					group_name = '" . SQLite3::escapeString($_POST["group_name"]) . "',
					grid_count = '" . $_POST["grid_count"] . "',
					grid_columns = '" . $_POST["grid_columns"] . "',
					grid_rows = '" . $_POST["grid_rows"] . "',
					grid_trays = '" . ( empty($_POST["grid_trays"]) ? null : $_POST["grid_trays"] ) . "',
					disk_tray_direction = '" . $_POST["disk_tray_direction"] . "',
					tray_direction = '" . $_POST["tray_direction"] . "',
					tray_start_num = '" . $_POST["tray_start_num"] . "',
					tray_width = '" . $_POST["tray_width"] . "',
					tray_height = '" . $_POST["tray_height"] . "'
				WHERE id = '" . $_POST["groupid"] . "'
				;
			";
			
			debug_print($debugging_active, __LINE__, "SQL", "GROUP SETTINGS: <pre>" . $sql . "</pre>");
			
			$ret = $db->exec($sql);
			if(!$ret) {
				echo $db->lastErrorMsg();
			}
		}
	}
	
	if(isset($_POST["save_allocations"])) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE ALLOCATIONS has been pressed.");
		// trays
		$sql = "";
		$post_drives = $_POST["drives"];
		$post_groups = $_POST["groups"];
		
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
						purchased = '" . SQLite3::escapeString($_POST["purchased"][$keys_drives[$i]]) . "',
				";
				if($_POST["current_warranty_field"] == "u") {
					$sql .= "warranty = '" . SQLite3::escapeString($_POST["warranty"][$keys_drives[$i]]) . "',";
				}
				else {
					$sql .= "warranty_date = '" . SQLite3::escapeString($_POST["warranty_date"][$keys_drives[$i]]) . "',";
				}
				$sql .= "
						comment = '" . SQLite3::escapeString($_POST["comment"][$keys_drives[$i]]) . "'
					";
				if(!in_array(str_replace("#", "", SQLite3::escapeString($_POST["bgcolor_custom"][$keys_drives[$i]])), array($bgcolor_parity, $bgcolor_unraid, $bgcolor_cache, $bgcolor_others, $bgcolor_empty))) {
					$sql .= ", color = '" . str_replace("#", "", SQLite3::escapeString($_POST["bgcolor_custom"][$keys_drives[$i]])) . "'";
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
	
// Common config
	$get_global_smType = ( isset($GLOBALS["var"]["smType"]) ? $GLOBALS["var"]["smType"] : null );
	
	$unraid_disks = $GLOBALS["disks"];
	$unraid_disks = array_values($unraid_disks);
	
	// get configured Unraid disks
	/*
	if(is_file(DISKINFORMATION)) {
		$unraid_disks_import = parse_ini_file(DISKINFORMATION, true);
		$unraid_disks = array_values($unraid_disks_import);
	}
	*/
	// modify the array to suit our needs
	$unraid_array = array();
	$smart_controller_unraid = "";
	$i=0;
	while($i < count($unraid_disks)) {
		$getdevicenode = $unraid_disks[$i]["device"];
		if(!isset($unraid_disks[$i]["hotTemp"])) { 
			$unraid_disks[$i]["hotTemp"] = 0;
		}
		if(!isset($unraid_disks[$i]["maxTemp"])) { 
			$unraid_disks[$i]["maxTemp"] = 0;
		}
		
		$smart_controller_unraid = "" . ( isset($unraid_disks[$i]["smType"]) ? $unraid_disks[$i]["smType"] : $get_global_smType ) . "" . ( isset($unraid_disks[$i]["smPort1"]) ? "," . $unraid_disks[$i]["smPort1"] : null ) . "" . ( isset($unraid_disks[$i]["smPort2"]) ? $unraid_disks[$i]["smGlue"] . "" . $unraid_disks[$i]["smPort2"] : null ) . "" . ( isset($unraid_disks[$i]["smPort3"]) ? $unraid_disks[$i]["smGlue"] . "" . $unraid_disks[$i]["smPort3"] : null ) . "" . ( isset($unraid_disks[$i]["smDevice"]) ? " /dev/" . $unraid_disks[$i]["smDevice"] : null ) . "";
		
		if($getdevicenode) {
			$unraid_array[$getdevicenode] = array(
				"name" => ($unraid_disks[$i]["name"] ?? null),
				"device" => ($unraid_disks[$i]["device"] ?? null),
				"status" => ($unraid_disks[$i]["status"] ?? null),
				"type" => ($unraid_disks[$i]["type"] ?? null),
				"temp" => ($unraid_disks[$i]["temp"] ?? null),
				"hotTemp" => ($unraid_disks[$i]["hotTemp"] ? $unraid_disks[$i]["hotTemp"] : $GLOBALS["display"]["hot"]),
				"maxTemp" => ($unraid_disks[$i]["maxTemp"] ? $unraid_disks[$i]["maxTemp"] : $GLOBALS["display"]["max"]),
				"color" => ($unraid_disks[$i]["color"] ?? null),
				"fscolor" => ($unraid_disks[$i]["fsColor"] ?? null),
				"smart_controller_cmd" => ($smart_controller_unraid ?? null)
			);
		}
		$i++;
	}
	
	// get unassigned Unraid disks
	
	$unraid_devs = $GLOBALS["devs"];
	$unraid_devs = array_values($unraid_devs);
	
	// modify the array to suit our needs
	$unraid_unassigned = array();
	$smart_controller_devs = "";
	$i=0;
	while($i < count($unraid_devs)) {
		$getdevicenode = $unraid_devs[$i]["device"];
		if(!isset($unraid_devs[$i]["hotTemp"])) { 
			$unraid_devs[$i]["hotTemp"] = 0;
		}
		if(!isset($unraid_devs[$i]["maxTemp"])) { 
			$unraid_devs[$i]["maxTemp"] = 0;
		}
		
		$smart_controller_devs = "" . ( isset($unraid_devs[$i]["smType"]) ? $unraid_devs[$i]["smType"] : $get_global_smType ) . "" . ( isset($unraid_devs[$i]["smPort1"]) ? "," . $unraid_devs[$i]["smPort1"] : null ) . "" . ( isset($unraid_devs[$i]["smPort2"]) ? $unraid_devs[$i]["smGlue"] . "" . $unraid_devs[$i]["smPort2"] : null ) . "" . ( isset($unraid_devs[$i]["smPort3"]) ? $unraid_devs[$i]["smGlue"] . "" . $unraid_devs[$i]["smPort3"] : null ) . "" . ( isset($unraid_devs[$i]["smDevice"]) ? " /dev/" . $unraid_devs[$i]["smDevice"] : null ) . "";
		
		if($getdevicenode) {
			$unraid_array[$getdevicenode] = array(
				"name" => ($unraid_devs[$i]["name"] ?? null),
				"device" => ($unraid_devs[$i]["device"] ?? null),
				"status" => ($unraid_devs[$i]["status"] ?? null),
				"type" => ($unraid_devs[$i]["type"] ?? null),
				"temp" => ($unraid_devs[$i]["temp"] ?? null),
				"hotTemp" => ($unraid_devs[$i]["hotTemp"] ? $unraid_devs[$i]["hotTemp"] : $GLOBALS["display"]["hot"]),
				"maxTemp" => ($unraid_devs[$i]["maxTemp"] ? $unraid_devs[$i]["maxTemp"] : $GLOBALS["display"]["max"]),
				"color" => ($unraid_devs[$i]["color"] ?? null),
				"fscolor" => ($unraid_devs[$i]["fsColor"] ?? null),
				"smart_controller_cmd" => ($smart_controller_devs ?? null)
			);
		}
		$i++;
	}
	
	//if(!in_array("cronjob", $argv)) {
		// get settings from DB as $var
		$sql = "SELECT * FROM settings";
		$results = $db->query($sql);
		
		while($data = $results->fetchArray(1)) {
			extract($data);
		}
		
		$displayinfo = json_decode($displayinfo, true);
		
		//dashboard_toggle($dashboard_widget_pos); 
		//cronjob_timer($smart_updates);
		
		$color_array = array();
		$color_array["empty"] = $bgcolor_empty;
		
	// Group config
		$last_group_id = 0;
		
		$sql = "SELECT * FROM settings_group ORDER BY id ASC";
		$results = $db->query($sql);
		
		while($data_group = $results->fetchArray(1)) {
			foreach($data_group as $key=>$value) {
				$group[$data_group["id"]][$key] = "".$value."";
			}
		}
		
		$count_groups = array();
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
	//}
	
	// get all attached SCSI drives - usually should grab all local drives available
	//$lsscsi_cmd = shell_exec("lsscsi -u -g");
	$lsscsi_cmd = shell_exec("lsscsi -b -g");
	$lsscsi_arr = explode(PHP_EOL, $lsscsi_cmd);
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
		if(isset($_GET["force_smart_scan"]) || isset($_POST["force_smart_scan"])) {
			print("
				<p>
					<b>Scanning drives, please wait until it is completed...</b>
				</p>
				<p class=\"mono\">
			");
		}
		
		// wait until the cronjob has finished.
		$pid_cron_script = ( shell_exec("pgrep -f disklocation.sh") ? trim(shell_exec("pgrep -f disklocation.sh")) : 0 );
		while(!empty(shell_exec("pgrep -f disklocation.sh")) && $force_scan) {
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
				//$smart_check_operation = shell_exec("smartctl -n standby $lsscsi_devicenodesg[$i] | egrep 'ACTIVE|IDLE|NVMe'");
				$smart_check_operation = shell_exec("smartctl -n standby " . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . " " . ( !preg_match("/dev/", "foo-" . $unraid_array[$lsscsi_devicenode[$i]]["smart_controller_cmd"] . "") ?? $lsscsi_devicenodesg[$i] ) . " | egrep 'ACTIVE|IDLE|NVMe'");
				
				usleep($smart_exec_delay . 000); // delay script to get the output of the next shell_exec()
				
				print("SMART: " . $lsscsi_devicenodesg[$i] . " ");
				
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
							$smart_lun = "" . $smart_array["wwn"]["naa"] ?? null . " " . $smart_array["wwn"]["oui"] ?? null . " " . $smart_array["wwn"]["id"] ?? null . "";
						}
						$smart_model_family = $smart_array["model_family"] ?? null;
						$smart_model_name = $smart_array["model_name"] ?? null;
						
						$smart_i=0;
						$smart_loadcycle_find = "";
						if(isset($smart_array["ata_smart_attributes"]["table"])) {
							while($smart_i < count($smart_array["ata_smart_attributes"]["table"])) {
								if($smart_array["ata_smart_attributes"]["table"][$smart_i]["name"] == "Load_Cycle_Count") {
									$smart_loadcycle_find = $smart_array["ata_smart_attributes"]["table"][$smart_i]["raw"]["value"];
									$smart_i = count($smart_array["ata_smart_attributes"]["table"]);
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
									smart_rotation,
									smart_formfactor,
									smart_nvme_available_spare,
									smart_nvme_available_spare_threshold,
									smart_nvme_percentage_used,
									smart_nvme_data_units_read,
									smart_nvme_data_units_written,
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
									'" . ($smart_array["rotation_rate"] ?? null) . "',
									'" . ($smart_array["form_factor"]["name"] ?? null) . "',
									'" . ($smart_array["nvme_smart_health_information_log"]["available_spare"] ?? null) . "',
									'" . ($smart_array["nvme_smart_health_information_log"]["available_spare_threshold"] ?? null) . "',
									'" . ($smart_array["nvme_smart_health_information_log"]["percentage_used"] ?? null) . "',
									'" . ($smart_array["nvme_smart_health_information_log"]["data_units_read"] ?? null) . "',
									'" . ($smart_array["nvme_smart_health_information_log"]["data_units_written"] ?? null) . "',
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
									smart_rotation='" . ($smart_array["rotation_rate"] ?? null) . "',
									smart_nvme_available_spare='" . ($smart_array["nvme_smart_health_information_log"]["available_spare"] ?? null) . "',
									smart_nvme_available_spare_threshold='" . ($smart_array["nvme_smart_health_information_log"]["available_spare_threshold"] ?? null) . "',
									smart_nvme_percentage_used='" . ($smart_array["nvme_smart_health_information_log"]["percentage_used"] ?? null) . "',
									smart_nvme_data_units_read='" . ($smart_array["nvme_smart_health_information_log"]["data_units_read"] ?? null) . "',
									smart_nvme_data_units_written='" . ($smart_array["nvme_smart_health_information_log"]["data_units_written"] ?? null) . "'
									
								WHERE hash='" . $deviceid[$i] . "'
								;
						";
						
						if(is_array($unraid_disklog["" . str_replace(" ", "_", $smart_model_name) . "_" . str_replace(" ", "_", $smart_array["serial_number"]) . ""])) {
							$sql .= "
								UPDATE disks SET
									purchased='" . $unraid_disklog["" . str_replace(" ", "_", $smart_model_name) . "_" . str_replace(" ", "_", $smart_array["serial_number"]) . ""]["purchase"] . "',
									warranty='" . $unraid_disklog["" . str_replace(" ", "_", $smart_model_name) . "_" . str_replace(" ", "_", $smart_array["serial_number"]) . ""]["warranty"] . "'
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
				
				print("done.<br />");
				
				unset($smart_array);
			}
			$i++;
		}
		// check the existence of devices, must be run during force smart scan.
		if($force_scan) {
			find_and_set_removed_devices_status($db, $deviceid); 		// tags removed devices 'r', delete device from location
		}
		
		if(isset($_GET["force_smart_scan"]) || isset($_POST["force_smart_scan"])) {
			print("
				</p>
				<p>
					<b>Scanning has completed, refreshing within 5 seconds...
				<!-- 
				Press \"Done\" and refresh the page.</b>
				</p>
				<p style=\"text-align: center;\">
					<button type=\"button\" onclick=\"top.Shadowbox.close()\">Done</button>
				-->
				</p>
				<script type=\"text/javascript\">
					function sleep (time) {
						return new Promise((resolve) => setTimeout(resolve, time));
					}
					sleep(5000).then(() => {
						window.top.location = '" . DISKLOCATIONCONF_URL . "';
					})
				</script>
			");
			die();
		}
	}
	
	if(isset($_POST["reset_all_colors"])) {
		force_reset_color($db, "*");
	}
	
	cronjob_runfile_updater();
?>
