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
	function disklocation_system($type, $operation) {
		$array = array();
		if($type == "backup") {
			if($operation == "list") {
				$file = explode("\n", (shell_exec("ls -1 " . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation_db_v*.sqlite.tar.bz2")));
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
			if($operation == "delete") {
				array_map('unlink', glob("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation_db_v*.sqlite.tar.bz2"));
			}
		}
		
		if($type == "database") {
			if($operation == "list") {
				if(file_exists("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.sqlite")) {
					return filesize("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.sqlite");
				}
				else {
					return false;
				}
			}
			if($operation == "delete") {
				unlink("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.sqlite");
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
	
	if(isset($_POST["del_backup"])) {
		disklocation_system("backup", "delete");
	}
	if(isset($_POST["del_debug"])) {
		disklocation_system("debug", "delete");
	}
	if(isset($_POST["del_database"])) {
		disklocation_system("database", "delete");
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	if(isset($_POST["undelete_devices"])) {
		force_undelete_devices($db, 'm');
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATIONCONF_URL . "\" />");
		exit;
	}
	if(isset($_POST["del_ldashleft"])) {
		disklocation_system("ldashleft", "delete");
	}
	if(isset($_POST["add_ldashleft"])) {
		disklocation_system("ldashleft", "create");
	}

	$print_list_backup = "";
	$print_list_debug = "";
	$print_list_database = "";
	$print_list_undelete = "";
	$print_list_ldashleft = "";
	
	$list_backup = disklocation_system("backup", "list");
	if($list_backup) {
		$print_list_backup .= "
			<h3>Automatic database upgrade backups</h3><br />
			<table style=\"width: 0;\">
				<tr>
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
						" . $list_backup[$i]["file"] . "
					</td>
					<td style=\"text-align: right; padding: 0 0 0 20px;\">
						" . $list_backup[$i]["size"] . " bytes
					</td>
				</tr>
			";
		}
		$print_list_backup .= "
			</table>
			<form action=\"\" method=\"post\">
				<input type=\"submit\" name=\"del_backup\" value=\"Delete all backup files\" />
			</form>
			<blockquote class='inline_help'>
				This will delete all databases which were automatically backed up during an update when the database was changed.
			</blockquote>
		";
	}
	
	$list_debug = disklocation_system("debug", "list");
	if($list_debug) {
		$print_list_debug = "
			<h3>Debug file</h3>
			<p>Debug filesize: " . $list_debug . "</p>
			<form action=\"\" method=\"post\">
				<input type=\"submit\" name=\"del_debug\" value=\"Delete debug file\" />
			</form>
			<blockquote class='inline_help'>
				This will delete the debug file, in general you don't want to have this enabled and running.
			</blockquote>
		";
	}
	
	$list_database = disklocation_system("database", "list");
	if($list_database) {
		$print_list_database = "
			<h3>Database file</h3>
			<p>Database filesize: " . $list_database . " bytes</p>
			<form action=\"\" method=\"post\">
				<input type=\"submit\" name=\"del_database\" value=\"Delete the database\" />
			</form>
			<blockquote class='inline_help'>
				This will delete the current database file in case there's an unresolvable problem, it will not be backed up before deletion.
			</blockquote>
		";
	}
	
	$list_undelete = force_undelete_devices($db, 'r');
	if($list_undelete) {
		$print_list_undelete = "
			<h3>Undelete devices</h3>
			<p>" . $list_undelete . " deleted devices found</p>
			<form action=\"\" method=\"post\">
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
				<form action=\"\" method=\"post\">
					<input type=\"submit\" name=\"del_ldashleft\" value=\"Move to right\" />
				</form>
			";
		}
		else {
			$print_list_ldashleft .= "
				<form action=\"\" method=\"post\">
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
?>
<link type="text/css" rel="stylesheet" href="<?autov("" . DISKLOCATION_PATH . "/pages/styles/help.css")?>">
<h2>System</h2>
<p style="color: red;">
	<b>NB! Operations done on this page will execute without warning or confirmation and cannot be undone after execution!</b>
</p>
<form action="" method="post">
	<b>When clicking "Force Scan All" the plugin starts collecting SMART data directly. Due to new system for compatibility with other controllers, the entire page must be loaded and will look like it's not doing anything. Just be patient, it will reload the page when it's done. It might take a few seconds to several minutes depending on the amount of devices it need to scan.</b>
	<!--<input type='button' value='Force Scan All' onclick='openBox("/plugins/disklocation/pages/system.php?force_smart_scan=1","Force Scanning",600,1000,true,"loadlist",":return")'>-->
	<br />
	<input type='submit' name="force_smart_scan" value="Force Scan All">
</form>
<?php echo $print_list_backup ?>
<?php echo $print_list_debug ?>
<?php echo $print_list_database ?>
<?php echo $print_list_undelete ?>
<?php echo $print_list_ldashleft ?>
