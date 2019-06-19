<?php
	// Comment out to enable debugging:
	//$debugging_active = 1;
	
	// Set warning level
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	$get_page_info = parse_ini_file("/usr/local/emhttp/plugins/disklocation/disklocation.page");
	
	// define constants
	define("DISKLOCATION_DB", "/boot/config/plugins/disklocation/disklocation.sqlite");
	define("DISKINFORMATION", "/var/local/emhttp/disks.ini");
	define("DISKLOGFILE", "/boot/config/disk.log");
	define("DISKLOCATION_VERSION", $get_page_info["Version"]);
	define("DISKLOCATION_URL", "/Settings/disklocation");
	define("DISKLOCATION_PATH", "/plugins/disklocation");
	
	$disklocation_error = array();
	$disklocation_new_install = 0;
	
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
	
	if($_POST["delete_x"] && $_POST["delete_y"]) {
		$sql = "
			UPDATE disks SET
				status = 'd'
			WHERE luname = '" . $_POST["luname"] . "'
			;
		";
		
		$ret = $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
		}
		
		$db->close();
		
		//header("Location: " . DISKLOCATION_URL);
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	
	if($_POST["add_x"] && $_POST["add_y"]) {
		$sql = "
			UPDATE disks SET
				status = ''
			WHERE luname = '" . $_POST["luname"] . "'
			;
		";
		
		$ret = $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
		}
		
		$db->close();
		
		//header("Location: " . DISKLOCATION_URL);
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	
	require_once("sqlite_tables.php");
	
	if($argv[1] == "cronjob") {
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
			file_put_contents("/boot/config/" . DISKLOCATION_PATH . "/debugging.html", $out, FILE_APPEND);
			flush();
			return true;
		}
		if($act == 2 && $section != "SQL") {
			print("[" . date("His") . "] " . basename(__FILE__) . ":" . $line . " @ " . $section . ": " . $message . "\n");
			flush();
		}
		else {
			return false;
		}
	}
	
	debug_print($debugging_active, __LINE__, "functions", "Debug function active.");
	
	function is_tray_allocated($db, $tray) {
		$sql = "SELECT hash FROM location WHERE tray = '" . $tray . "'";
		$results = $db->query($sql);
		while($data = $results->fetchArray(1)) {
			return ( isset($data["hash"]) ? $data["hash"] : false);
		}
	}
	
	function get_tray_location($db, $hash, $empty = '') {
		$sql = "SELECT * FROM location WHERE hash = '" . $hash . "'";
		$results = $db->query($sql);
		if(!$empty) {
			while($data = $results->fetchArray(1)) {
				if(!$data["empty"]) { return ( empty($data["tray"]) ? false : $data["tray"] ); }
			}
		}
		else {
			while($data = $results->fetchArray(1)) {
				$exp_arr = explode(",", $data["empty"]);
				$trim_arr=array_filter($exp_arr);
				return ( empty($trim_arr) ? false : $trim_arr );
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
			"00" => "Disk unavailable or unconfigured",
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
		$sql = "SELECT hash FROM disks WHERE status IS NULL;";
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
	
	if($_POST["save_settings"]) {
		debug_print($debugging_active, __LINE__, "POST", "Button: SAVE has been pressed.");
		// trays
		$sql = "";
		$post_drives = $_POST["drives"];
		$post_empty = $_POST["empty"];
		$post_info = json_encode($_POST["displayinfo"]);
		
		//if(array_duplicates($post_drives)) { $disklocation_error[] = "Duplicate tray assignment found, be sure to assign trays in a unique order."; }
		
		// settings
		if(!preg_match("/[0-9]{1,5}/", $_POST["smart_exec_delay"])) { $disklocation_error[] = "SMART execution delay missing or invalid number."; }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_parity"])) { $disklocation_error[] = "Background color for \"Parity\" invalid."; } else { $_POST["bgcolor_parity"] = str_replace("#", "", $_POST["bgcolor_parity"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_unraid"])) { $disklocation_error[] = "Background color for \"Data\" invalid."; } else { $_POST["bgcolor_unraid"] = str_replace("#", "", $_POST["bgcolor_unraid"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_cache"])) { $disklocation_error[] = "Background color for \"Cache\" invalid."; } else { $_POST["bgcolor_cache"] = str_replace("#", "", $_POST["bgcolor_cache"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_others"])) { $disklocation_error[] = "Background color for \"Unassigned devices\" invalid."; } else { $_POST["bgcolor_others"] = str_replace("#", "", $_POST["bgcolor_others"]); }
		if(!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $_POST["bgcolor_empty"])) { $disklocation_error[] = "Background color for \"Empty trays\" invalid."; } else { $_POST["bgcolor_empty"] = str_replace("#", "", $_POST["bgcolor_empty"]); }
		if(!preg_match("/\b(column|row)\b/", $_POST["grid_count"])) { $disklocation_error[] = "Physical tray assignment invalid."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["grid_columns"])) { $disklocation_error[] = "Grid columns missing or number invalid."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["grid_rows"])) { $disklocation_error[] = "Grid rows missing or number invalid."; }
		if($_POST["grid_trays"] && !preg_match("/[0-9]{1,3}/", $_POST["grid_trays"])) { $disklocation_error[] = "Grid trays number invalid."; }
		if(!preg_match("/(h|v)/", $_POST["disk_tray_direction"])) { $disklocation_error[] = "Physical tray direction invalid."; }
		if(!preg_match("/[0-9]{1,4}/", $_POST["tray_width"])) { $disklocation_error[] = "Tray's longest side outside limits or invalid number entered."; }
		if(!preg_match("/[0-9]{1,3}/", $_POST["tray_height"])) { $disklocation_error[] = "Tray's smallest side outside limits or invalid number entered."; }
		if(!preg_match("/(u|m)/", $_POST["warranty_field"])) { $disklocation_error[] = "Warranty field is invalid."; }
		//if(!preg_match("/(C|F|K)/", $_POST["tempunit"])) { $disklocation_error[] = "Temperature unit is invalid."; }
		
		if(empty($disklocation_error)) {
			$keys_drives = array_keys($post_drives);
			for($i=0; $i < count($keys_drives); ++$i) {
				$tray_assign = ( empty($post_drives[$keys_drives[$i]]) ? null : $post_drives[$keys_drives[$i]] );
				
				if(!$tray_assign) {
					$sql .= "
						UPDATE disks SET
							status = 'h'
						WHERE hash = '" . $keys_drives[$i] . "'
						;
						DELETE FROM location
							WHERE tray = '" . $tray_assign . "'
						;
					";
				}
				else {
					$sql .= "
						INSERT INTO
							location(
								hash,
								tray
							)
							VALUES(
								'" . $keys_drives[$i] . "',
								'" . $tray_assign . "'
							)
							ON CONFLICT(hash) DO UPDATE SET
								tray='" . $tray_assign . "'
						;
						UPDATE disks SET
							status = NULL
						WHERE hash = '" . $keys_drives[$i] . "'
						;
					";
				}
			}
			
			debug_print($debugging_active, __LINE__, "SQL", "EMPTY: <pre>" . $sql . "</pre>");
			
			$ret = $db->exec($sql);
			if(!$ret) {
				echo $db->lastErrorMsg();
			}
			
			$sql = "";
			
			// Remove conflicting tray allocations, use only the newest assigned tray
			$sql = "SELECT id FROM location GROUP BY tray HAVING COUNT(*) > 1;";
			$results = $db->query($sql);
			
			while($res = $results->fetchArray(1)) {
				$sql_del = "DELETE FROM location WHERE id = '" . $res["id"] . "';";
				$ret = $db->exec($sql_del);
				if(!$ret) {
					return $db->lastErrorMsg();
				}
			}
			$sql = "";
			
			$sql .= "DELETE FROM location WHERE hash = 'empty';";
			
			if(is_array($post_empty)) { // Future reference: from php 7.3 and later, use is_countable instead.
				for($i=0; $i < count($post_empty); ++$i) {
					if($post_empty[$i] > $_POST["grid_trays"]) { 
						$i = count($post_empty);
					}
					else {
						if(!is_tray_allocated($db, (int)$post_empty[$i])) {
							$post_empty_sql .= "" . $post_empty[$i] . ",";
						}
					}
				}
			}
			else {
				$post_empty_sql = "";
			}
			$sql .= "
				INSERT INTO 
					location(
						hash,
						empty
					)
					VALUES(
						'empty',
						'" . $post_empty_sql . "'
					)
				;
			";
			$sql .= "
				REPLACE INTO
					settings(
						smart_exec_delay,
						bgcolor_parity,
						bgcolor_unraid,
						bgcolor_cache,
						bgcolor_others,
						bgcolor_empty,
						grid_count,
						grid_columns,
						grid_rows,
						grid_trays,
						disk_tray_direction,
						tray_width,
						tray_height,
						warranty_field,
						tempunit,
						displayinfo
					)
					VALUES(
						'" . $_POST["smart_exec_delay"] . "',
						'" . $_POST["bgcolor_parity"] . "',
						'" . $_POST["bgcolor_unraid"] . "',
						'" . $_POST["bgcolor_cache"] . "',
						'" . $_POST["bgcolor_others"] . "',
						'" . $_POST["bgcolor_empty"] . "',
						'" . $_POST["grid_count"] . "',
						'" . $_POST["grid_columns"] . "',
						'" . $_POST["grid_rows"] . "',
						'" . ( empty($_POST["grid_trays"]) ? null : $_POST["grid_trays"] ) . "',
						'" . $_POST["disk_tray_direction"] . "',
						'" . $_POST["tray_width"] . "',
						'" . $_POST["tray_height"] . "',
						'" . $_POST["warranty_field"] . "',
						'" . $_POST["tempunit"] . "',
						'" . $post_info . "'
					)
				;
			";
			
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
					WHERE hash = '" . $keys_drives[$i] . "'
					;
				";
			}
			
			debug_print($debugging_active, __LINE__, "SQL", "POPULATED: <pre>" . $sql . "</pre>");
			
			$ret = $db->exec($sql);
			if(!$ret) {
				echo $db->lastErrorMsg();
			}
			
			//$post_empty_arr = explode(",", $post_empty_sql);
			
			//$sql = "SELECT MIN(NULLIF(CAST(tray as INTEGER),0)) AS tray_min , MAX(CAST(tray as INTEGER)) AS tray_max FROM location;";
			$sql = "SELECT MIN(CAST(tray as INTEGER)) AS tray_min , MAX(CAST(tray as INTEGER)) AS tray_max FROM location;";
			$results = $db->query($sql);
			
			while($data = $results->fetchArray(1)) {
				extract($data);
			}
			
			for($i = $tray_min; $i <= $tray_max; ++$i) {
				if(!is_tray_allocated($db, $i)) {
					$empty_results .= "" . $i . ",";
				}
			}
			
			$sql = "
				UPDATE location SET
					empty = '" . $empty_results . "'
				WHERE hash = 'empty'
				;
			";
			
			$ret = $db->exec($sql);
			if(!$ret) {
				echo $db->lastErrorMsg();
			}
		}
	}
	
	// get settings from DB as $var
	
	$sql = "SELECT * FROM settings";
	$results = $db->query($sql);
	
	while($data = $results->fetchArray(1)) {
		extract($data);
	}
	
	$displayinfo = json_decode($displayinfo, true);
	
	// get all attached SCSI drives - usually should grab all local drives available
	$lsscsi_cmd = shell_exec("lsscsi -u -g");
	$lsscsi_arr = explode(PHP_EOL, $lsscsi_cmd);
	
	// get configured Unraid disks
	if(is_file(DISKINFORMATION)) {
		$unraid_disks_import = parse_ini_file(DISKINFORMATION, true);
		$unraid_disks = array_values($unraid_disks_import);
	}
	
	// get disk logs
	if(is_file(DISKLOGFILE)) {
		$unraid_disklog = parse_ini_file(DISKLOGFILE, true);
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
	
	$empty_tray_order = ( empty(get_tray_location($db, "empty", 1)) ? null : array_values(get_tray_location($db, "empty", 1)) );
	
	$color_array = array();
	$color_array["empty"] = $bgcolor_empty;
	
	// add and update disk info
	if($disklocation_new_install) {
		$_POST["force_smart_scan"] = 1; // trigger force_smart_scan post if it is a new install
	}
	
	if($_POST["force_smart_scan"] || $argv[1] == "cronjob") {
		$i=0;
		debug_print($debugging_active, __LINE__, "array", "LSSCSI:" . count($lsscsi_arr) . "");
		while($i < count($lsscsi_arr)) {
			$pattern_device = "\[(.*)\]";					// $1
			$pattern_type = "\s+(\w+)";					// $2
			$pattern_luname = "\s+(.*?)[-|\/]";				// $3
			$pattern_devicenodefp = "(dev[\/]([h|s]d[a-z]{1,}))?";		// $5
			$pattern_scsigenericdevicenode = "\s+([sg[0-9]{1,})?";		// $7
			
			list($device[], $type[], $luname[], $devicenodefp[], $scsigenericdevicenode[]) = 
				explode("|", preg_replace("/^" . $pattern_device . "" . $pattern_type . "" . $pattern_luname . "" . $pattern_devicenodefp . "" . $pattern_scsigenericdevicenode . "/iu", "$1|$2|$3|$5|$7", $lsscsi_arr[$i]));
			
			$lsscsi_device[$i] = trim($device[$i]);							// get the device address: "1:0:0:0"
			$lsscsi_type[$i] = trim($type[$i]);							// get the type: "disk" / "process" (not in use for this script)
			$lsscsi_luname[$i] = str_replace(" ", "", str_replace("none", "", trim($luname[$i])));	// get the logical unit name of the drive
			$lsscsi_devicenode[$i] = str_replace("-", "", trim($devicenodefp[$i]));			// get only the node name: "sda"
			$lsscsi_devicenodesg[$i] = trim($scsigenericdevicenode[$i]);				// get the full path to SCSI Generic device node: "/dev/sg1"
			
			debug_print($debugging_active, __LINE__, "loop", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "|TYPE:" . $lsscsi_type[$i] . "|LUN:" . $lsscsi_luname[$i] . "|SCSIGEN:" . $lsscsi_devicenodesg[$i] . "");
			
			if($lsscsi_device[$i] && $lsscsi_luname[$i]) { // only care about real hard drives
				$smart_check_operation = shell_exec("smartctl -n standby /dev/bsg/$lsscsi_device[$i] | egrep 'ACTIVE|IDLE'");
				usleep($smart_exec_delay . 000); // delay script to get the output of the next shell_exec()
				
				if(!empty($smart_check_operation) || $_POST["force_smart_scan"]) { // only get SMART data if the disk is spinning, if it is a new install/empty database, or if scan is forced.
					if(!$_POST["force_smart_scan"]) {
						$smart_standby_cmd = "-n standby";
					}
					$smart_cmd[$i] = shell_exec("smartctl $smart_standby_cmd -x --json /dev/bsg/$lsscsi_device[$i]");	// get all SMART data for this device, we grab it ourselves to get all drives also attached to hardware raid cards.
					$smart_array = json_decode($smart_cmd[$i], true);
					debug_print($debugging_active, __LINE__, "SMART", "#:" . $i . "|DEV:" . $lsscsi_device[$i] . "=" . is_array($smart_array) . " (1=array available)");
					
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
					
					$rotation_rate = ( recursive_array_search("Solid State Device Statistics", $smart_array) ? -1 : $smart_array["rotation_rate"] );
					$deviceid[$i] = hash('sha256', $smart_array["model_name"] . $smart_array["serial_number"]);
					
					debug_print($debugging_active, __LINE__, "HASH", "#:" . $i . ":" . $deviceid[$i] . "");
					
					find_and_unset_reinserted_devices_status($db, $deviceid[$i]);	// tags old existing devices with 'null', delete device from location just in case it for whatever reason it already exists.
					
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
								'" . $lsscsi_luname[$i] . "',
								'" . $smart_array["model_family"] . "',
								'" . $smart_array["model_name"] . "',
								'" . $smart_array["smart_status"]["passed"] . "',
								'" . $smart_array["serial_number"] . "',
								'" . $smart_array["temperature"]["current"] . "',
								'" . $smart_array["power_on_time"]["hours"] . "',
								'" . $smart_loadcycle_find . "',
								'" . $smart_array["user_capacity"]["bytes"] . "',
								'" . $rotation_rate . "',
								'" . $smart_array["form_factor"]["name"] . "',
								'h',
								'" . $deviceid[$i] . "'
							)
							ON CONFLICT(hash) DO UPDATE SET
								device='" . $lsscsi_device[$i] . "',
								devicenode='" . $lsscsi_devicenode[$i] . "',
								luname='" . $lsscsi_luname[$i] . "',
								model_family='" . $smart_array["model_family"] . "',
								smart_status='" . $smart_array["smart_status"]["passed"] . "',
								smart_temperature='" . $smart_array["temperature"]["current"] . "',
								smart_powerontime='" . $smart_array["power_on_time"]["hours"] . "',
								smart_loadcycle='" . $smart_loadcycle_find . "',
								smart_rotation='" . $rotation_rate . "'
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
				
				unset($smart_array);
			}
			$i++;
		}
		// check the existens of devices
		find_and_set_removed_devices_status($db, $deviceid); 		// tags removed devices 'r', delete device from location
	}
	
	// get disk info for "Information" and "Configuration"
	
	$total_trays = ( empty($grid_trays) ? $grid_columns * $grid_rows : $grid_trays );
	$get_empty_trays = get_tray_location($db, "empty", 1);
	
	$total_main_trays = 0;
	if($total_trays > ($grid_columns * $grid_rows)) {
		$total_main_trays = $grid_columns * $grid_rows;
		$total_rows_override_trays = ($total_trays - $total_main_trays) / $grid_columns;
		$grid_columns_override_styles = str_repeat(" auto", $total_rows_override_trays);
	}
	
	if(!is_array($get_empty_trays) && !count_table_rows($db, "disks")) {
		$sql = "SELECT * FROM disks WHERE status IS NULL;";
	}
	else {
		$sql = "SELECT * FROM disks JOIN location ON disks.hash=location.hash WHERE status IS NULL ORDER BY tray ASC;";
	}
	
	$results = $db->query($sql);
	
	$datasql = array();
	while($res = $results->fetchArray(1)) {
		array_push($datasql, $res);
	}
?>
