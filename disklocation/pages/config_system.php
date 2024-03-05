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
	if(strstr($_SERVER["SCRIPT_NAME"], "config_system.php")) {
		// Set warning level
		//error_reporting(E_ERROR | E_WARNING | E_PARSE);
		error_reporting(E_ALL);
		
		define("UNRAID_CONFIG_PATH", "/boot/config");
		define("DISKLOCATION_PATH", "/plugins/disklocation");
		define("DISKLOCATION_URL", "/Tools/disklocation");
		define("DISKLOCATION_CONF", "" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.conf");
		define("DISKLOCATION_DB_DEFAULT", "" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.sqlite");
		
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
		
		function config($file, $operation, $key = '', $val = '') { // file, [r]ead/[w]rite, key (req. write), value (req. write)
			if($operation == 'w') {
				if(!file_exists($file)) {
					touch($file);
				}
				$config_json = file_get_contents($file);
				$config_json = json_decode($config_json, true);
				$config_json[$key] = $val;
				$config_json = json_encode($config_json);
				if(file_put_contents($file, $config_json)) {
					return true;
				}
				else {
					return "Failed updating the configuration file.";
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
	}
	
	$print_loc_db_err = "";
	
	function compress_file($src, $dst) {
		$data = file_get_contents($src);
		$gzdata = gzencode($data, 9);
		file_put_contents($dst, $gzdata);
	}
	
	function decompress_file($src, $dst) {
		$data = file_get_contents($src);
		$gzdata = gzdecode($data);
		file_put_contents($dst, $gzdata);
	}
	
	function database_location($cur, $new, $config) {
		if($cur != $new) {
			if(file_exists($cur) && !file_exists($new)) {
				if(copy($cur, $new)) {
					if(sha1_file($cur) == sha1_file($new)) {
						if(config($config, 'w', 'database_location', $new)) {
							unlink($cur);
							return true;
						}
						else {
							return "Failed updating the configuration file. Current database not deleted, but a copy might exist in the new location.";
						}
					}
					else {
						return "Failed moving the database, checksum on $cur and $new did not match. Check path, permissions and file system.";
					}
				}
				else {
					return "Failed to copy the database. Check path, permissions and file system.";
				}
			}
			else {
				return "File already exists in the new location.";
			}
		}
		else {
			return false;
		}
	}
	function database_backup($file, $backup_location) {
		if(file_exists($file)) {
			$datetime = date("Ymd-His");
			mkdir($backup_location . "/" . $datetime, 0700, true);
			compress_file($file, $backup_location . "/" . $datetime . "/disklocation.sqlite.gz");
		}
		else {
			return "Database does not exist.";
		}
	}
	function database_restore($file, $restore_location) {
		if(file_exists($file)) {
			decompress_file($file, $restore_location);
		}
		else {
			return "Database does not exist.";
		}
	}
	function disklocation_system($type, $operation, $file = "") {
		$array = array();
		if($type == "backup") {
			if($operation == "list") {
				$file = explode("\n", (shell_exec("ls -1 " . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/*/*.gz")));
				for($i=0; $i < count($file); ++$i) {
					$array[$i]["file"] = $file[$i];
					$array[$i]["size"] = filesize($file[$i]);
				}
				if($array[0]["file"]) {
					return $array;
				}
				else {
					return false;
				}
			}
			if($operation == "restore" && file_exists($file)) {
				database_restore($file, DISKLOCATION_DB);
			}
			if($operation == "delete" && file_exists($file)) {
				unlink($file);
				rmdir(str_replace("disklocation.sqlite.gz", "", $file));
			}
			if($operation == "delete_all") {
				array_map('unlink', glob("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/*/*.gz"));
				array_map('rmdir', glob("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/*"));
			}
		}
		
		if($type == "database") {
			if($operation == "list") {
				if(file_exists(DISKLOCATION_DB)) {
					return filesize(DISKLOCATION_DB);
				}
				else {
					return false;
				}
			}
			if($operation == "delete") {
				unlink(DISKLOCATION_DB);
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
			if($operation == "list") {
				if(file_exists("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/debugging.html")) {
					return filesize("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/debugging.html");
				}
				else {
					return false;
				}
			}
			if($operation == "delete") {
				unlink("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/debugging.html");
			}
		}
		
		if($type == "ldashleft") {
			if($operation == "list") {
				if(file_exists("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.ldashleft")) {
					return true;
				}
				else {
					return false;
				}
			}
			if($operation == "delete") {
				unlink("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.ldashleft");
			}
			if($operation == "create") {
				touch("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.ldashleft");
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
		disklocation_system("backup", "delete_all");
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
	if(isset($_POST["del_database"])) {
		disklocation_system("database", "delete");
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
		force_undelete_devices($db, 'm');
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["del_ldashleft"])) {
		disklocation_system("ldashleft", "delete");
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["add_ldashleft"])) {
		disklocation_system("ldashleft", "create");
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["move_db"])) {
		$move_db = database_location($_POST["cur_db_location"], $_POST["new_db_location"], DISKLOCATION_CONF);
		if($move_db === true) {
			header("Location: " . DISKLOCATION_URL . "");
			//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
			exit;
		}
		else {
			$print_loc_db_err = "<h2 style=\"color: red;\">ERROR: " . $move_db . "</h2>";
		}
	}
	if(isset($_POST["backup_db"])) {
		database_backup(DISKLOCATION_DB, "" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/");
		header("Location: " . DISKLOCATION_URL . "");
		//print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	
	$print_loc_db = "";
	$print_force_scan = "";
	$print_list_backup = "";
	$print_list_debug = "";
	$print_list_database = "";
	$print_list_database_lock = "";
	$print_list_undelete = "";
	$print_list_ldashleft = "";

	$list_database = disklocation_system("database", "list");
	$print_loc_db = "
		<h3>Database location</h3>
		<p>Database filesize: " . $list_database . " bytes</p>
		$print_loc_db_err
		<p style=\"color: red;\">
			<b>USE AT OWN RISK!<br/></b>
			Enter the full path including the filename! Make sure the path is accessible with the correct permissions.
			Choose a location which will be accessible from early boot, not behind encrypted devices or devices not mounted at boot.
			If stored behind e.g. Unraid shares, the plugin will not show any information at all until the array has started and mounted.
			You should disable the automatic system boot and update scan under \"Configuration\" if you anyway choose to do so, and rather rely on SMART updates and manual scans.
			If no path is entered, the file will be stored at \"/usr/local/emhttp/\" and will be gone next reboot, do NOT store the file without a full path!<br />
			Default plugin database file location: " . DISKLOCATION_DB_DEFAULT . "
		</p>
		<form action=\"" . DISKLOCATION_PATH . "/pages/config_system.php\" method=\"post\">
			<input type=\"text\" name=\"new_db_location\" value=\"" . DISKLOCATION_DB . "\" style=\"width: 400px;\" />
			<input type=\"hidden\" name=\"cur_db_location\" value=\"" . DISKLOCATION_DB . "\" style=\"width: 400px;\" />
			<br />
			<input type=\"submit\" name=\"move_db\" value=\"Move database\" />
			<input type=\"submit\" name=\"del_database\" value=\"Delete database\" />
			<input type=\"submit\" name=\"backup_db\" value=\"Backup database\" />
		</form>
	";
	
	$list_backup = disklocation_system("backup", "list");
	if($list_backup) {
		$print_list_backup .= "
			<form action=\"" . DISKLOCATION_PATH . "/pages/config_system.php\" method=\"post\">
				<h3>Database backups</h3><br />
				<table style=\"width: 0;\">
					<tr>
						<td></td>
						<td>
							<b>File</b>
						</td>
						<td style=\"padding: 0 0 0 20px;\">
							<b>Size</b>
						</td>
					</tr>
		";
		for($i=0; $i < count($list_backup)-1; ++$i) {
			$print_list_backup .= "
					<tr>
						<td>
							<input type=\"radio\" name=\"backup_file_list\" value=\"" . $list_backup[$i]["file"] . "\" />
						</td>
						<td style=\"white-space: nowrap;\">
							" . $list_backup[$i]["file"] . "
						</td>
						<td style=\"text-align: right; padding: 0 0 0 20px; white-space: nowrap;\">
							" . $list_backup[$i]["size"] . " bytes
						</td>
					</tr>
			";
		}
		$print_list_backup .= "
				</table>
				<input type=\"submit\" name=\"res_backup\" value=\"Restore\" />
				<input type=\"submit\" name=\"del_backup\" value=\"Delete\" />
				<input type=\"submit\" name=\"del_backup_all\" value=\"Delete all\" />
			</form>
			<blockquote class='inline_help'>
				This will delete all databases which were backed up.
			</blockquote>
		";
	}
	$list_database_lock = disklocation_system("database_lock", "list");
	if($list_database_lock) {
		$print_list_database_lock = "
			<h3>Lock file</h3>
			<p style=\"color: red;\">
				This will delete the database lock file and should delete itself automatigally. Only delete this if you know that the database is not updating in the background.
			</p>
			<form action=\"" . DISKLOCATION_PATH . "/pages/config_system.php\" method=\"post\">
				<input type=\"submit\" name=\"del_database_lock\" value=\"Delete Lock file\" />
			</form>
		";
	}	
	$list_debug = disklocation_system("debug", "list");
	if($list_debug) {
		$print_list_debug = "
			<h3>Debug file</h3>
			<p>Debug filesize: " . $list_debug . "</p>
			<form action=\"" . DISKLOCATION_PATH . "/pages/config_system.php\" method=\"post\">
				<input type=\"submit\" name=\"del_debug\" value=\"Delete debug file\" />
			</form>
			<blockquote class='inline_help'>
				This will delete the debug file, in general you don't want to have this enabled and running.
			</blockquote>
		";
	}
	if(!strstr($_SERVER["SCRIPT_NAME"], "config_system.php") && $db_update != 2) {
		$list_undelete = force_undelete_devices($db, 'r');
		if($list_undelete) {
			$print_list_undelete = "
				<h3>Undelete devices</h3>
				<p>" . $list_undelete . " deleted devices found</p>
				<form action=\"" . DISKLOCATION_PATH . "/pages/config_system.php\" method=\"post\">
					<input type=\"submit\" name=\"undelete_devices\" value=\"Undelete all devices\" />
				</form>
				<blockquote class='inline_help'>
					This will undelete devices that has been manually marked for deletion. The drives will be assigned under \"Devices not found or removed\" under the \"Drives\" tab.
				</blockquote>
			";
		}
		if(version_compare($GLOBALS["var"]["version"], "6.11.9", "<")) {
			$list_ldashleft = disklocation_system("ldashleft", "list");
			$print_list_ldashleft = "
				<h3>Legacy Dashboard location</h3>
				<p>
					This setting is only visible and usable for Unraid version below 6.12.
				</p>
			";
			if($list_ldashleft) {
				$print_list_ldashleft .= "
					<form action=\"" . DISKLOCATION_PATH . "/pages/config_system.php\" method=\"post\">
						<input type=\"submit\" name=\"del_ldashleft\" value=\"Move to right\" />
					</form>
				";
			}
			else {
				$print_list_ldashleft .= "
					<form action=\"" . DISKLOCATION_PATH . "/pages/config_system.php\" method=\"post\">
						<input type=\"submit\" name=\"add_ldashleft\" value=\"Move to left\" />
					</form>
				";
			}
		}
		else { // delete a file that's not required anymore, if it exists.
			if(disklocation_system("ldashleft", "list")) {
				disklocation_system("ldashleft", "delete");
			}
		}
		
		$print_force_scan = "
			<form action=\"" . DISKLOCATION_PATH . "/pages/config_system.php\" method=\"post\">
				<b>Clicking the buttons will start collecting SMART data directly. It might take a few seconds to several minutes depending on the amount of devices it need to scan.</b>
				<br />
				<input type='button' value='Update Active' onclick='openBox(\"" . CRONJOB_URL . "?active_smart_scan=1\",\"Updating Active Devices\",600,800,true,\"loadlist\",\":return\")'>
				<input type='button' value='Force Update All' onclick='openBox(\"" . CRONJOB_URL . "?force_smart_scan=1\",\"Force Updating All Devices\",600,800,true,\"loadlist\",\":return\")'>
				<!--<br />
				<input type='submit' name=\"active_smart_scan\" value=\"Update Active\">
				<input type='submit' name=\"force_smart_scan\" value=\"Force Update All\">-->
				<blockquote class='inline_help'>
					<ul>
						<li>\"Update Active\" button will update only active (spinning) drives for SMART data, It might take a while to complete depending on your configuration.</li>
						<li>You can also run \"Update Active\" from the shell and get direct output which might be useful for debugging:<br />
						<code style=\"white-space: nowrap;\">php -f /usr/local/emhttp/plugins/disklocation/pages/cron_disklocation.php cronjob [silent]</code></li>
					</ul>
					<ul>
						<li>\"Force Update All\" button will force update all drives for SMART data and move removed disks into the \"lost\" table under the \"Information\" tab. This button will and must wake up all drives into a spinning state and does so one by one. It might take a while to complete depending on your configuration.</li>
						<li>You can also run \"Force Update All\" from the shell and get direct output which might be useful for debugging:<br />
						<code style=\"white-space: nowrap;\">php -f /usr/local/emhttp/plugins/disklocation/pages/cron_disklocation.php force [silent]</code></li>
					</ul>
				</blockquote>
			</form>
		";
	}
	else if(strstr($_SERVER["SCRIPT_NAME"], "config_system.php")) {
		function autov($foo) {
			return $foo;
		}
	}
	if($db_update == 2) { $system_limited_text = " - limited page during database error."; }
?>
<link type="text/css" rel="stylesheet" href="<?autov("" . DISKLOCATION_PATH . "/pages/styles/help.css")?>">
<h2>System<?php print($system_limited_text); ?></h2>
<p style="color: red;">
	<b>NB! Operations done on this page will execute without warning or confirmation and cannot be undone after execution!</b>
</p>
<?php echo $print_force_scan ?>
<?php echo $print_list_backup ?>
<?php echo $print_list_debug ?>
<?php echo $print_list_database ?>
<?php echo $print_list_database_lock ?>
<?php echo $print_list_undelete ?>
<?php echo $print_list_ldashleft ?>
<?php echo $print_loc_db ?>
