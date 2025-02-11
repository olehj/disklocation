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
	
	if(strstr($_SERVER["SCRIPT_NAME"], "page_system.php")) {
		// Set warning level
		//error_reporting(E_ERROR | E_WARNING | E_PARSE);
		error_reporting(E_ALL);
		
		define("UNRAID_CONFIG_PATH", "/boot/config");
		define("EMHTTP_ROOT", "/usr/local/emhttp");
		define("DISKLOCATION_PATH", "/plugins/disklocation");
		define("DISKLOCATION_URL", "/Tools/disklocation");
		define("DISKLOCATION_TMP_PATH", "/tmp/disklocation");
		define("DISKLOCATION_CONF", UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/settings.json");
		define("DISKLOCATION_DEVICES", UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/devices.json");
		define("DISKLOCATION_LOCATIONS", UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/locations.json");
		define("DISKLOCATION_GROUPS", UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/groups.json");
		define("DISKLOCATION_LOCK_FILE", DISKLOCATION_TMP_PATH . "/db.lock");
		define("CRONJOB_URL", DISKLOCATION_PATH . "/pages/cronjob.php");
		define("CRONJOB_FILE", EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/pages/cronjob.php");
		
		if(isset($_GET["logfile"])) {
			// open raw memory as file so no temp files needed, you might run out of memory though
			$logfile = DISKLOCATION_TMP_PATH . "/disklocation.log";
			$fp = fopen($logfile, 'r');
			header('Content-Type: text/plain');
			header("Content-Length: " . filesize($logfile));
			header('Content-Disposition: attachment; filename=disklocation-'.date("Y-m-d").'.log;');
			fpassthru($fp);
			exit;
		}
		if(isset($_GET["logfile_hddcheck"])) {
			// open raw memory as file so no temp files needed, you might run out of memory though
			$logfile = DISKLOCATION_TMP_PATH . "/hddcheck.log";
			$fp = fopen($logfile, 'r');
			header('Content-Type: text/plain');
			header("Content-Length: " . filesize($logfile));
			header('Content-Disposition: attachment; filename=disklocation_hddcheck-'.date("Y-m-d").'.log;');
			fpassthru($fp);
			exit;
		}
		
		if(file_exists(DISKLOCATION_CONF)) {
			$get_disklocation_config = json_decode(file_get_contents(DISKLOCATION_CONF), true);
		}
		if(file_exists(DISKLOCATION_DEVICES)) {
			$get_devices = json_decode(file_get_contents(DISKLOCATION_DEVICES), true);
		}
		if(file_exists(DISKLOCATION_LOCATIONS)) {
			$get_locations = json_decode(file_get_contents(DISKLOCATION_LOCATIONS), true);
		}
		if(file_exists(DISKLOCATION_GROUPS)) {
			$get_groups = json_decode(file_get_contents(DISKLOCATION_GROUPS), true);
		}
		
		$auto_backup_days = ( !empty($get_disklocation_config["auto_backup_days"]) ? $get_disklocation_config["auto_backup_days"] : 0 );
		
		$debug = 0;
		require_once("functions.php");
	}
	
	define("DISKLOCATION_DB_DEFAULT", UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.sqlite");
	if(file_exists(DISKLOCATION_CONF)) {
		$get_disklocation_config = json_decode(file_get_contents(DISKLOCATION_CONF), true);
		if(isset($get_disklocation_config["database_location"])) {
			define("DISKLOCATION_DB", $get_disklocation_config["database_location"]);
		}
		else {
			define("DISKLOCATION_DB", DISKLOCATION_DB_DEFAULT);
		}
	}
	else {
		define("DISKLOCATION_DB", DISKLOCATION_DB_DEFAULT);
	}
	
	$print_loc_db_err = "";
	$argv = ( !isset($argv) ? array() : $argv );
	
	function compress_file($src_array, $dst) {
		$data = array();
		
		for($i=0; $i < count($src_array); ++$i) {
			$filename = str_replace(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/", "", $src_array[$i]);
			$data[$filename] = file_get_contents($src_array[$i]);
		}
		$json = json_encode($data);
		$data = gzencode($json, 9);
		file_put_contents($dst, $data);
	}
	
	function decompress_file($src, $dst) {
		$data = file_get_contents($src);
		$gzdata = gzdecode($data);
		
		if(str_contains($src, "sqlite")) {
			file_put_contents($dst, $gzdata);
		}
		else {
			$json = json_decode($gzdata, true);
			foreach($json as $filename => $content) {
				if(!file_exists(dirname($dst . $filename))) { mkdir(dirname($dst . $filename), 0777, 1); }
				file_put_contents($dst . $filename, $json[$filename]);
			}
		}
	}
	
	function database_backup($files, $destination, $backup_filename = 'disklocation') {
		$files = explode(",", $files);
		for($i=0; $i < count($files); ++$i) {
			if(file_exists($files[$i])) {
				$file[] = $files[$i];
			}
		}
		$datetime = date("Ymd-His");
		mkdir($destination . "/" . $datetime, 0700, true);
		
		if(!empty($file)) {
			if(in_array(DISKLOCATION_DB, $file)) {
				compress_file($file, $destination . "/" . $datetime . "/" . $backup_filename . ".sqlite.gz");
			}
			else {
				compress_file($file, $destination . "/" . $datetime . "/" . $backup_filename . ".json.gz");
			}
		}
		else {
			return "No files available.";
		}
	}
	function database_restore($file, $destination) {
		if(str_contains($file, "sqlite")) {
			$destination = DISKLOCATION_DB;
			// must delete new json files if restoring old SQLite DB:
			( file_exists(DISKLOCATION_CONF) ? unlink(DISKLOCATION_CONF) : false );
			( file_exists(DISKLOCATION_DEVICES) ? unlink(DISKLOCATION_DEVICES) : false );
			( file_exists(DISKLOCATION_GROUPS) ? unlink(DISKLOCATION_GROUPS) : false );
			( file_exists(DISKLOCATION_LOCATIONS) ? unlink(DISKLOCATION_LOCATIONS) : false );
		}
		
		if(file_exists($file)) {
			decompress_file($file, $destination);
		}
		else {
			return "Database does not exist.";
		}
	}
	function disklocation_system($type, $operation, $file = "") {
		$array = array();
		if($type == "backup") {
			if($operation == "list") {
				$i=0;
				$backup_dir = array_diff(scandir(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/"), array('..', '.'));
				foreach($backup_dir as $contents) {
					$backup_dir_time = array_diff(scandir(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/" . $contents), array('..', '.'));
					foreach($backup_dir_time as $dir => $file) {
						if(strstr($file, ".gz")) {
							$array[$i]["file"] = UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/" . $contents . "/" . $file;
							$array[$i]["size"] = filesize(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/" . $contents . "/" . $file);
							$i++;
						}
					}
				}
				if($array[0]["file"]) {
					return $array;
				}
				else {
					return false;
				}
				
			}
			if($operation == "restore" && file_exists($file)) {
				database_restore($file, UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/");
			}
			if($operation == "delete" && file_exists($file)) {
				unlink($file);
				( is_dir(str_replace("disklocation.sqlite.gz", "", $file)) ? rmdir(str_replace("disklocation.sqlite.gz", "", $file)) : rmdir(str_replace("disklocation.json.gz", "", $file)) );
			}
			if($operation == "delete_all") {
				array_map('unlink', glob("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/*/*.gz"));
				array_map('rmdir', glob("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/*"));
			}
		}
		
		if($type == "database_lock") {
			if($operation == "list") {
				if(file_exists(DISKLOCATION_LOCK_FILE)) {
					return true;
				}
				else {
					return false;
				}
			}
			if($operation == "delete") {
				unlink(DISKLOCATION_LOCK_FILE);
			}
		}
		
		if($type == "debug") {
			if($operation == "enable") {
				touch(DISKLOCATION_TMP_PATH . "/.debug");
			}
			if($operation == "disable") {
				unlink(DISKLOCATION_TMP_PATH . "/.debug");
			}
			if($operation == "list") {
				if(file_exists("" . DISKLOCATION_TMP_PATH . "/disklocation.log")) {
					return filesize("" . DISKLOCATION_TMP_PATH . "/disklocation.log");
				}
				else {
					return false;
				}
			}
			if($operation == "delete") {
				unlink("" . DISKLOCATION_TMP_PATH . "/disklocation.log");
			}
		}
		
		if($type == "reset") {
			if($operation == "settings" || $operation == "all" || $operation == "wipe") {
				unlink(DISKLOCATION_CONF);
			}
			if($operation == "groups" || $operation == "all" || $operation == "wipe") {
				unlink(DISKLOCATION_GROUPS);
				unlink(DISKLOCATION_LOCATIONS);
			}
			if($operation == "locations" || $operation == "all" || $operation == "wipe") {
				unlink(DISKLOCATION_LOCATIONS);
			}
			if($operation == "devices" || $operation == "all" || $operation == "wipe") {
				unlink(DISKLOCATION_DEVICES);
			}
			if($operation == "wipe") {
				array_map('unlink', glob(DISKLOCATION_TMP_PATH . "/smart/*.json"));
				rmdir(DISKLOCATION_TMP_PATH . "/smart");
				array_map('unlink', glob(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/benchmark/*.json"));
				rmdir(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/benchmark");
			}
		}

	}
	if(isset($_POST["res_backup"])) {
		disklocation_system("backup", "restore", $_POST["backup_file_list"]);
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"5;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["del_backup"])) {
		disklocation_system("backup", "delete", $_POST["backup_file_list"]);
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["del_backup_all"])) {
		if(isset($_POST["del_backup_all_check"])) {
			disklocation_system("backup", "delete_all");
		}
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["del_debug"])) {
		disklocation_system("debug", "delete");
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["del_database_lock"])) {
		disklocation_system("database_lock", "delete");
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["undelete_devices"])) {
		force_undelete_devices($get_devices, 'm');
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["debug_enable"])) {
		disklocation_system('debug', 'enable');
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["debug_disable"])) {
		disklocation_system('debug', 'disable');
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["reset"]) && isset($_POST["reset_op"])) {
		disklocation_system('reset', $_POST["reset_op"]);
		header("Location: " . DISKLOCATION_URL . "");
		exit;
	}
		
	if(isset($_POST["backup_db"]) || in_array("backup", $argv)) {
		if(file_exists(DISKLOCATION_DEVICES) && file_exists(DISKLOCATION_GROUPS) && file_exists(DISKLOCATION_LOCATIONS)) {
			if(file_exists(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/benchmark/")) {
				$benchmark_files = array_diff(scandir(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/benchmark/"), array('..', '.'));
				foreach($benchmark_files as $file => $foo) {
					$benchmark_backup[] = UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/benchmark/" . $benchmark_files[$file];
				}
				$get_benchmark_files = implode(",", $benchmark_backup);
			}
			
			if(in_array("auto", $argv)) {
				$get_backup_files = disklocation_system("backup", "list");
				$get_backup_files = end($get_backup_files);
				
				$time_backup = trim(str_replace(dirname($get_backup_files["file"], 2) . "/", '', dirname($get_backup_files["file"], 1)));
				
				$time_now = new DateTime("now");
				$time_backup = DateTime::createFromFormat('Ymd-His', $time_backup);
				$time_diff = $time_now->diff($time_backup);
				
				if($auto_backup_days && $time_diff->format("%a") > $auto_backup_days) {
					database_backup(DISKLOCATION_CONF.",".DISKLOCATION_DEVICES.",".DISKLOCATION_GROUPS.",".DISKLOCATION_LOCATIONS.( !empty($get_benchmark_files) ? "," . $get_benchmark_files : null ), "" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/");
					print(in_array("silent", $argv) ? null : "Previous backup was more than $auto_backup_days days old, new backup created.\n");
				}
				else {
					print(in_array("silent", $argv) ? null : "Backup was made in the recent $auto_backup_days days or backup is disabled, skipping.\n");
				}
			}
			else {
				database_backup(DISKLOCATION_CONF.",".DISKLOCATION_DEVICES.",".DISKLOCATION_GROUPS.",".DISKLOCATION_LOCATIONS.( !empty($get_benchmark_files) ? "," . $get_benchmark_files : null ), "" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/");
			}
		}
		else {
			database_backup(DISKLOCATION_DB, "" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/");
		}
		if(!in_array("backup", $argv)) {
			header("Location: " . DISKLOCATION_URL . "");
		}
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	
	$print_force_scan = "";
	$print_list_backup = "";
	$print_list_debug = "";
	$print_list_database = "";
	$print_list_database_lock = "";
	$print_list_undelete = "";
	
	$list_backup = disklocation_system("backup", "list");
	if($list_backup) {
		$print_list_backup .= "
			<form action=\"" . DISKLOCATION_PATH . "/pages/page_system.php\" method=\"post\">
				<h3 style=\"padding: 0; margin: 0;\">Database backups</h3>
				<p>
					Backup your configuration and benchmarks. When restoring, you must likely run a Force SMART+DB afterwards.<br />
					This will NOT backup Unraid files (data containing manufacturer and purchase date and warranty period including SMART acknowledgements.). ONLY plugin related files.<br />
					<span class=\"red\">Backup and restore can take a few seconds after clicking the button, please wait until page is reloaded.</span>
				</p>
				<br />
				<table style=\"width: 0;\">
					<tr>
						<td></td>
						<td>
							<b>File</b>
						</td>
						<td style=\"padding: 0 0 0 20px;\">
							<b>Type</b>
						</td>
						<td style=\"padding: 0 0 0 20px;\">
							<b>Size</b>
						</td>
					</tr>
		";
		for($i=0; $i < count($list_backup); ++$i) {
			
			$file_type = ( strstr($list_backup[$i]["file"], "json") ? "JSON" : "SQLite" );
			$date_file = trim(str_replace(dirname($list_backup[$i]["file"], 2) . "/", "", dirname($list_backup[$i]["file"], 1)));
			$date_file = DateTime::createFromFormat('Ymd-His', $date_file);
			$date_file = $date_file->format("Y-m-d H:i:s");
			
			$print_list_backup .= "
					<tr>
						<td>
							<input type=\"radio\" name=\"backup_file_list\" value=\"" . $list_backup[$i]["file"] . "\" />
						</td>
						<td style=\"white-space: nowrap;\">
							" . $date_file . "
						</td>
						<td style=\"padding: 0 0 0 20px; white-space: nowrap;\">
							" . $file_type . "
						</td>
						<td style=\"text-align: right; padding: 0 0 0 20px; white-space: nowrap;\">
							" . ( function_exists('human_filesize') ? human_filesize($list_backup[$i]["size"], 1, true) : $list_backup[$i]["size"] . " bytes" ) . "
						</td>
					</tr>
			";
			$total_bak_size += $list_backup[$i]["size"];
		}
		$print_list_backup .= "
					<tr>
						<td></td>
						<td></td>
						<td style=\"text-align: right;\">Total size: </td>
						<td style=\"text-align: right; padding: 0 0 0 20px; white-space: nowrap;\">
							" . ( function_exists('human_filesize') ? human_filesize($total_bak_size, 1, true) : $total_bak_size . " bytes" ) . "
						</td>
					</tr>
				</table>
				<input type=\"submit\" name=\"backup_db\" value=\"Backup\" />
				<input type=\"submit\" name=\"res_backup\" value=\"Restore\" />
				<input type=\"submit\" name=\"del_backup\" value=\"Delete\" />
				<input type=\"submit\" name=\"del_backup_all\" value=\"Delete all\" />
				<input type=\"checkbox\" name=\"del_backup_all_check\" value=\"1\" title=\"Check this to delete all backups\" /> &lt;-- Check this box to delete all backups
			</form>
			<blockquote class='inline_help'>
				This will delete all databases which were backed up.
			</blockquote>
		";
	}
	else {
		$print_list_backup = "
			<form action=\"" . DISKLOCATION_PATH . "/pages/page_system.php\" method=\"post\">
				<h3>Database backups</h3><br />
				<table style=\"width: 0;\">
					<tr>
						<td>
							<input type=\"submit\" name=\"backup_db\" value=\"Backup\" />
						</td>
					</tr>
				</table>
			</form>
		";
	}
	$list_database_lock = disklocation_system("database_lock", "list");
	if($list_database_lock) {
		$print_list_database_lock = "
			<h3>Lock file</h3>
			<p style=\"color: red;\">
				This will delete the database lock file and should delete itself automagically. Only delete this if you know that the lock is stuck and the database is not updating in the background, otherwise you might corrupt the database.
			</p>
			<form action=\"" . DISKLOCATION_PATH . "/pages/page_system.php\" method=\"post\">
				<input type=\"submit\" name=\"del_database_lock\" value=\"Delete Lock file\" />
			</form>
		";
	}	
	$list_debug = disklocation_system("debug", "list");
	$print_list_debug = "
		<h3>Debugging</h3>
		<form action=\"" . DISKLOCATION_PATH . "/pages/page_system.php\" method=\"post\">
			" . ( !file_exists(DISKLOCATION_TMP_PATH . "/.debug") ? "<input type=\"submit\" name=\"debug_enable\" value=\"Enable\" />" : "<input type=\"submit\" name=\"debug_disable\" value=\"Disable\" />" ) . "
	";
	if($list_debug) {
		$print_list_debug .= "
			<input type=\"submit\" name=\"del_debug\" value=\"Delete debug file\" />
			<a href=\"/plugins/disklocation/pages/page_system.php?logfile=1\">Download debug file</a> (" . ( function_exists('human_filesize') ? human_filesize($list_debug, 1, true) : $list_debug . " bytes" ) . ")
			<blockquote class='inline_help'>
				Enable and disable debugging, in general you don't want to have this enabled and running. Will automatically turn off after reboot.<br />
				<span class=\"red\">WARNING! Logfile contains full serial numbers and all other information about your drive setup and layout.</span>
			</blockquote>
		";
	}
	$print_list_debug .= "</form>";
	
	$print_reset = "
		<h3>Plugin reset</h3>
		<form action=\"" . DISKLOCATION_PATH . "/pages/page_system.php\" method=\"post\">
			<input type=\"radio\" name=\"reset_op\" value=\"\" checked=\"checked\" /> none
			" . ( file_exists(DISKLOCATION_CONF) ? "<input type=\"radio\" name=\"reset_op\" value=\"settings\" /> configuration" : null ) . "
			" . ( file_exists(DISKLOCATION_GROUPS) ? "<input type=\"radio\" name=\"reset_op\" value=\"groups\" /> layout (includes tray allocations)" : null ) . "
			" . ( file_exists(DISKLOCATION_LOCATIONS) ? "<input type=\"radio\" name=\"reset_op\" value=\"locations\" /> tray allocations" : null ) . "
			" . ( file_exists(DISKLOCATION_DEVICES) ? "<input type=\"radio\" name=\"reset_op\" value=\"devices\" /> devices" : null ) . "
			" . ( (file_exists(DISKLOCATION_CONF) || file_exists(DISKLOCATION_GROUPS) || file_exists(DISKLOCATION_LOCATIONS) || file_exists(DISKLOCATION_DEVICES)) ? "<input type=\"radio\" name=\"reset_op\" value=\"all\" /> all" : null ) . "
			" . ( (file_exists(DISKLOCATION_CONF) || file_exists(DISKLOCATION_GROUPS) || file_exists(DISKLOCATION_LOCATIONS) || file_exists(DISKLOCATION_DEVICES) || file_exists(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/benchmark") || file_exists(DISKLOCATION_TMP_PATH . "/smart")) ? "<input type=\"radio\" name=\"reset_op\" value=\"wipe\" /> wipe (including backups, benchmarks and smart data)" : null ) . "
			<br />
			<input type=\"submit\" name=\"reset\" value=\"Reset\" />
		<form>
	";
	
	if(!strstr($_SERVER["SCRIPT_NAME"], "page_system.php") && $db_update != 2) {
		$list_undelete = force_undelete_devices($get_devices, 'r');
		
		if($list_undelete) {
			$print_list_undelete = "
				<h3>Undelete devices</h3>
				<p>" . $list_undelete . " deleted devices found</p>
				<form action=\"" . DISKLOCATION_PATH . "/pages/page_system.php\" method=\"post\">
					<input type=\"submit\" name=\"undelete_devices\" value=\"Undelete all devices\" />
				</form>
				<blockquote class='inline_help'>
					This will undelete devices that has been manually marked for deletion. The drives will be assigned under \"Devices not found or removed\" under the \"Drives\" tab.
				</blockquote>
			";
		}
		
		$print_force_scan = "
			<form action=\"" . DISKLOCATION_PATH . "/pages/page_system.php\" method=\"post\">
				<b>Clicking the buttons will update data directly. It might take a few seconds to several minutes depending on the amount of devices it need to scan.</b>
				<br />
				<input type='button' " . ( (!$check_smart_files || $check_devicepath_conflict || !file_exists(DISKLOCATION_DEVICES)) ? "disabled=\"disabled\"" : null ) . " value='SMART' onclick='openBox(\"" . CRONJOB_URL . "?active_smart_scan=1\",\"Updating SMART data on active devices\",600,800,true,\"loadlist\",\":return\")'>
				<input type='button' " . ( ($check_devicepath_conflict || !file_exists(DISKLOCATION_DEVICES)) ? "disabled=\"disabled\"" : null ) . " value='Force SMART' onclick='openBox(\"" . CRONJOB_URL . "?force_smart_scan=1\",\"Wake up all devices and update SMART data\",600,800,true,\"loadlist\",\":return\")'>
				<input type='button' value='Force SMART+DB' onclick='openBox(\"" . CRONJOB_URL . "?force_smartdb_scan=1\",\"Wake up all devices and update SMART data and the database\",600,800,true,\"loadlist\",\":return\")'>
				<span style=\"padding-left: 50px;\"></span>
				<input type='button' value='Check Seagate HDDs' onclick='openBox(\"" . DISKLOCATION_PATH . "/pages/hddcheck.php?check_hdd=1\",\"Wake up all devices and check Seagate drives\",600,800,true,\"loadlist\",\":return\")'>
				" . ( file_exists(DISKLOCATION_TMP_PATH . "/hddcheck.log") ? "<a href=\"/plugins/disklocation/pages/page_system.php?logfile_hddcheck=1\">Download Seagate HDD Logfile</a>" : "" ) . "
				<blockquote class='inline_help'>
					<ul>
						<li>\"SMART\" button will update only active (spinning) drives for SMART data, It might take a while to complete depending on your configuration.</li>
						<li>You can also run \"SMART\" from the shell and get direct output which might be useful for debugging:<br />
						<code style=\"white-space: nowrap;\">php -f " . CRONJOB_FILE . " start [silent]</code></li>
					</ul>
					<ul>
						<li>\"Force SMART\" button will force update all drives for SMART data. This button will and must wake up all drives into a spinning state and does so one by one. It might take a while to complete depending on your configuration.</li>
						<li>You can also run \"Force SMART\" from the shell and get direct output which might be useful for debugging:<br />
						<code style=\"white-space: nowrap;\">php -f " . CRONJOB_FILE . " force [silent]</code></li>
					</ul>
					<ul>
						<li>\"Force SMART+DB\" button will force update all drives for SMART data and move removed disks into the \"lost\" table under the \"Information\" tab. This button will and must wake up all drives into a spinning state and does so one by one. It might take a while to complete depending on your configuration.</li>
						<li>You can also run \"Force SMART+DB\" from the shell and get direct output which might be useful for debugging:<br />
						<code style=\"white-space: nowrap;\">php -f " . CRONJOB_FILE . " forceall [silent]</code></li>
					</ul>
					<ul>
						<li>\"Check Seagate HDDs\" button will wake up all drives and compare \"Power On Hours\" between SMART and FARM.</li>
						<li>You can also run \"Check Seagate HDDs\" from the shell:<br />
						<code style=\"white-space: nowrap;\">php -f " . DISKLOCATION_PATH . "/pages/hddcheck.php</code></li>
					</ul>
				</blockquote>
			</form>
		";
	}
	else if(strstr($_SERVER["SCRIPT_NAME"], "page_system.php")) {
		function autov($foo) {
			return $foo;
		}
	}
	if($db_update == 2) { $system_limited_text = " - limited page during database error."; }
	
	if(in_array("backup", $argv)) { exit; }
?>
<link type="text/css" rel="stylesheet" href="<?autov("" . DISKLOCATION_PATH . "/pages/styles/help.css")?>">
<table><tr><td style="padding: 10px 10px 10px 10px;">
<h2 style="margin-top: -10px; padding: 0 0 0 0; margin-bottom: 0;">System<?php print($system_limited_text); ?></h2>
<div>
	<?php
		$size_master = file_exists(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH."-master") ? dirsize(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH."-master") : 0;
		$size_devel = file_exists(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH."-devel") ? dirsize(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH."-devel") : 0;
		$size_total = $size_master+$size_devel;
		print( function_exists('human_filesize') && function_exists('dirsize') ? "Memory: " . human_filesize(dirsize(DISKLOCATION_TMP_PATH)+dirsize(EMHTTP_ROOT . "" . DISKLOCATION_PATH)+filesize("/usr/local/bin/smartlocate"), 1, true) . " (Interface: " . human_filesize(dirsize(EMHTTP_ROOT . "" . DISKLOCATION_PATH)+filesize("/usr/local/bin/smartlocate")) . " / Cache: " . human_filesize(dirsize(DISKLOCATION_TMP_PATH)) . ")" : null );
		print( function_exists('human_filesize') && function_exists('dirsize') ? "<br />Storage: " . human_filesize(dirsize(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH)+$size_total, 1, true) : null );
	?>
</div>
<h3 class="red">
	<b>NB! Operations done on this page will execute without warning or confirmation and cannot be undone after execution!</b>
</h3>
<?php echo $print_force_scan ?>
<?php echo $print_list_backup ?>
<?php echo $print_list_debug ?>
<?php echo $print_list_database ?>
<?php echo $print_list_database_lock ?>
<?php echo $print_list_undelete ?>
<?php echo $print_reset ?>
</td></tr></table>
