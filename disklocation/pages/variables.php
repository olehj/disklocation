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
	
	// Set to 1 (log to file) | 2 (same as 1, but prints directly on the page)| 3 (prints directly, limited log) to enable debugging (it is slow!):
	$debugging_active = 0;
	
	// Set warning level
	//error_reporting(E_ERROR | E_WARNING | E_PARSE);
	error_reporting(E_ERROR);
	
	// define constants
	define("UNRAID_CONFIG_PATH", "/boot/config");
	define("EMHTTP_ROOT", "/usr/local/emhttp");
	define("EMHTTP_VAR", "/var/local/emhttp");
	define("DISKLOCATION_URL", "/Tools/disklocation");
	define("DISKLOCATIONCONF_URL", "/Tools/disklocation");
	define("DISKLOCATION_PATH", "/plugins/disklocation");
	define("DISKLOCATION_TMP_PATH", "/tmp/disklocation");
	define("DISKLOCATION_CONF", UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/settings.json");
	define("DISKLOCATION_DEVICES", UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/devices.json");
	define("DISKLOCATION_LOCATIONS", UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/locations.json");
	define("DISKLOCATION_GROUPS", UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/groups.json");
	define("DISKLOCATION_LOCK_FILE", DISKLOCATION_TMP_PATH . "/db.lock");
	define("CRONJOB_URL", DISKLOCATION_PATH . "/pages/cronjob.php");
	define("CRONJOB_FILE", EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/pages/cronjob.php");
	define("DISKLOGFILE", UNRAID_CONFIG_PATH . "/disk.log");
	define("UNRAID_DISKS_FILE", "disks.ini");
	define("UNRAID_DEVS_FILE", "devs.ini");
	define("UNRAID_MONITOR_FILE", "monitor.ini");
	define("SMART_ALL_FILE", "smart-all.cfg");
	define("SMART_ONE_FILE", "smart-one.cfg");
	
	$get_page_info = array();
	$get_page_info["Version"] = "";
	$get_page_info = parse_ini_file("" . EMHTTP_ROOT . "" . DISKLOCATION_PATH . "/disklocation.page");
	define("DISKLOCATION_VERSION", $get_page_info["Version"]);
	
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
	
	$unraid_disks = array();
	$unraid_devs = array();
	
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
	
	require_once("default_settings.php");
	
	if(!file_exists(DISKLOCATION_DEVICES)) { // do not load SQLite anymore if the devices.json exists.
		$disklocation_new_install = 1;
		require_once("sqlite_tables.php");
	}
	//( (file_exists("sqlite_tables.php") && file_exists(DISKLOCATION_DEVICES) && file_exists(DISKLOCATION_LOCATIONS) && file_exists(DISKLOCATION_GROUPS)) ?? unlink("sqlite_table.php") );
	
	$select_db_info_default = $select_db_info;
	$sort_db_info_default = $sort_db_info;
	$allowed_db_select_info =      "1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,1,1,1,1,1";
	$allowed_db_sort_info =        "1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,1,1,1,1,1";
	
	$select_db_trayalloc_default = $select_db_trayalloc;
	$sort_db_trayalloc_default = $sort_db_trayalloc;
	$allowed_db_select_trayalloc = "0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,1,1,1,1,1";
	$allowed_db_sort_trayalloc =   "1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,1,1,1,1,1";
	
	$select_db_drives_default = $select_db_drives;
	$sort_db_drives_default = $sort_db_drives;
	$allowed_db_select_drives =    "0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,0,1,1,1,0,0,0";
	$allowed_db_sort_drives =      "0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,0,1,1,1,0,0,0";
	
	$select_db_devices_default = $select_db_devices;
	
	$css_serial_number_highlight_default = $css_serial_number_highlight;
	
	$bgcolor_parity_default = strtoupper($bgcolor_parity);
	$bgcolor_unraid_default = strtoupper($bgcolor_unraid);
	$bgcolor_cache_default = strtoupper($bgcolor_cache);
	$bgcolor_others_default = strtoupper($bgcolor_others);
	$bgcolor_empty_default = strtoupper($bgcolor_empty);
	
	$sql_status = "";

	// get Unraid disks
	$get_default_smEvents = "5|187|197|198|199"; // default Unraid smEvents
	$get_global_smType = ( isset($unraid_smart_all["smType"]) ? $unraid_smart_all["smType"] : null );
	$get_global_smSelect = ( isset($unraid_smart_all["smSelect"]) ? $unraid_smart_all["smSelect"] : null );
	$get_global_smEvents = ( isset($unraid_smart_all["smEvents"]) ? $unraid_smart_all["smEvents"] : $get_default_smEvents );
	$get_global_smCustom = ( isset($unraid_smart_all["smCustom"]) ? $unraid_smart_all["smCustom"] : null );
	
	if(is_array($unraid_disks) && is_array($unraid_devs)) {
		$unraid_devs = array_values(array_merge($unraid_disks, $unraid_devs));
	}
	else {
		if(is_array($unraid_disks)) {
			$unraid_devs = array_values($unraid_disks);
		}
		else if(is_array($unraid_devs)) {
			$unraid_devs = array_values($unraid_devs);
		}
	}
	
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
				"smart_controller_cmd" => ($smart_controller_devs[$i] ?? null),
				"smSelect" => ($unraid_smart_one[$getdeviceid]["smSelect"] ?? null),
				"smEvents" => ($unraid_smart_one[$getdeviceid]["smEvents"] ?? null),
				"smCustom" => ($unraid_smart_one[$getdeviceid]["smCustom"] ?? null),
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
		set_time_limit(3600); // set to 1 hour.
	}
?>
