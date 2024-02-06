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

//	Database Version:
	$current_db_ver = 9;

//	Common settings
//	Variable name		Default value	Description
//	--------------------------------------------------------------------------------
	$smart_exec_delay =		'200';		// set milliseconds for next execution for SMART shell_exec - needed to actually grab all the information for unassigned devices. Default: 200
	$smart_updates =		'disabled';	// set how often to update the cronjob [hourly|daily|weekly|monthly|disabled]
	$bgcolor_parity =		'ca3f33';	// background color for Unraid parity disks / critical temp
	$bgcolor_unraid =		'ca7233';	// background color for Unraid data disks / warning temp // old default: ef6441
	$bgcolor_cache =		'cabd33';	// background color for Unraid cache disks / normal temp // old default: ff884c
	$bgcolor_others =		'3398ca';	// background color for unassigned/other disks / unknown temp // old default: 41b5ef
	$bgcolor_empty =		'7c7c7c';	// background color for empty trays // old default: aaaaaa
	$tray_reduction_factor =	'10';		// set the scale divider for the mini tray layout
	$warranty_field =		'u';		// choose [u]nraid's way of entering warranty date (12/24/36... months) or enter [m]anual ISO dates.
	$dashboard_widget =		'1';		// choose background for the drives, Drive Type (0) or Heat Map (1)
	$dashboard_widget_pos = 	'0';		// make serial number friendlier, substr() value -99 - 99.
	$displayinfo =	json_encode(array(		// this will store an json_encoded array of display settings for the "Device" page.
		'tray' => 1,
		'leddiskop' => 1,
		'ledsmart' => 1,
		'ledtemp' => 1,
		'unraidinfo' => 1,
		'path' => 1,
		'devicenode' => 1,
		'luname' => 1,
		'manufacturer' => 1,
		'devicemodel' => 1,
		'serialnumber' => 1,
		'temperature' => 1,
		'powerontime' => 1,
		'loadcyclecount' => 1,
		'capacity' => 1,
		'rotation' => 1,
		'formfactor' => 1,
		'available_spare' => 0,
		'available_spare_threshold' => 0,
		'percentage_used' => 1,
		'data_units_read' => 1,
		'data_units_written' => 1,
		'warranty' => 1,
		'comment' => 0,
		'hideemptycontents' => 0,
		'flashwarning' => 0,
		'flashcritical' => 1
	));
	
	// Table names:
	//	"groupid", "tray", "device", "devicenode", "luname", "model_family", "model_name", "smart_status", "smart_serialnumber", "smart_temperature", "smart_powerontime", "smart_loadcycle", "smart_capacity", "smart_rotation", 
	//	"smart_formfactor", "smart_nvme_available_spare", "smart_nvme_available_spare_threshold", "smart_nvme_percentage_used", "smart_nvme_data_units_read", "smart_nvme_data_units_written", "manufactured", "purchased", "warranty_date", "comment"
	// User input names - must also match $sort:
	//	"group", "tray", "device", "node", "lun", "manufacturer", "model", "status", "serial", "temp", "powerontime", "loadcycle", "capacity",
	//	"rotation", "formfactor", "nvme_spare", "nvme_spare_thres", "nvme_used", "nvme_unit_r", "nvme_unit_w", "manufactured", "purchased", "warranty", "comment"
	
	$select_db_info = "group,node,manufacturer,model,serial,status,temp,powerontime,loadcycle,capacity,rotation,formfactor,nvme_used,nvme_unit_r,nvme_unit_w,manufactured,purchased,warranty,comment";
	$sort_db_info = "asc:group,tray";
	
	// mandatory: group,tray,locate,color
	$select_db_trayalloc = "device,node,lun,manufacturer,model,serial,capacity,rotation,formfactor,manufactured,purchased,warranty,comment";
	$sort_db_trayalloc = "asc:group,tray";
	
	$select_db_drives = "device,manufacturer,model,serial,capacity,rotation,formfactor,manufactured,purchased,warranty,comment";
	$sort_db_drives = "asc:device";
	
	//not used, but prepared just in case it will be added in the future:
	$select_db_devices = "";
	$sort_db_devices = "";
	
//	Group settings
	
	$grid_count =		'column';	// how to count the trays: [column]: trays ordered from top to bottom from left to right | [row]: ..from left to right from top to bottom
	$grid_columns =		'4';		// number of horizontal trays
	$grid_rows =		'6';		// number of verical trays
	$grid_trays = 		'';		// total number of trays. default this is (grid_columns * grid_rows), but we choose to add some flexibility for drives outside normal trays
	$disk_tray_direction =	'h';		// direction of the hard drive trays [h]horizontal | [v]ertical
	$tray_direction =	'1';		// tray count direction
	$tray_start_num = 	'1';		// tray count start number, 0 or 1
	$tray_width =		'400';		// the pixel width of the hard drive tray: in the horizontal direction ===
	$tray_height =		'70';		// the pixel height of the hard drive tray: in the horizontal direction ===
	
//	Create database
	
	$sql_create_disks = "
		id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
		device VARCHAR(16) NOT NULL,
		devicenode VARCHAR(8),
		luname VARCHAR(50) NOT NULL,
		model_family VARCHAR(50),
		model_name VARCHAR(50),
		smart_status TINYINT,
		smart_serialnumber VARCHAR(128),
		smart_temperature DECIMAL(4,1),
		smart_powerontime INT,
		smart_loadcycle INT,
		smart_capacity INT,
		smart_rotation INT,
		smart_formfactor VARCHAR(16),
		smart_nvme_available_spare INT,
		smart_nvme_available_spare_threshold INT,
		smart_nvme_percentage_used INT,
		smart_nvme_data_units_read INT,
		smart_nvme_data_units_written INT,
		status CHAR(1),
		manufactured DATE,
		purchased DATE,
		warranty SMALLINT,
		warranty_date DATE,
		comment VARCHAR(255),
		color CHAR(6) NULL,
		hash VARCHAR(64) UNIQUE
	";
	$sql_tables_disks = "
		id,
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
		manufactured,
		purchased,
		warranty,
		warranty_date,
		comment,
		color,
		hash
	";
	$sql_create_location = "
		id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
		groupid INTEGER NULL,
		hash VARCHAR(64) UNIQUE NOT NULL,
		empty VARCHAR(255),
		tray SMALLINT
	";
	$sql_tables_location = "
		id,
		groupid,
		hash,
		empty,
		tray
	";
	$sql_create_settings = "
		id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
		smart_exec_delay INT NOT NULL DEFAULT '$smart_exec_delay',
		smart_updates VARCHAR(8) NOT NULL DEFAULT '$smart_updates',
		bgcolor_parity CHAR(6) NOT NULL DEFAULT '$bgcolor_parity',
		bgcolor_unraid CHAR(6) NOT NULL DEFAULT '$bgcolor_unraid',
		bgcolor_cache CHAR(6) NOT NULL DEFAULT '$bgcolor_cache',
		bgcolor_others CHAR(6) NOT NULL DEFAULT '$bgcolor_others',
		bgcolor_empty CHAR(6) NOT NULL DEFAULT '$bgcolor_empty',
		tray_reduction_factor FLOAT NOT NULL DEFAULT '$tray_reduction_factor',
		warranty_field CHAR(1) NOT NULL DEFAULT '$warranty_field',
		dashboard_widget CHAR(3) NOT NULL DEFAULT '$dashboard_widget',
		dashboard_widget_pos INT NULL,
		displayinfo VARCHAR(1023),
		select_db_info VARCHAR(1023) NOT NULL DEFAULT '$select_db_info',
		sort_db_info VARCHAR(1023) NOT NULL DEFAULT '$sort_db_info',
		select_db_trayalloc VARCHAR(1023) NOT NULL DEFAULT '$select_db_trayalloc',
		sort_db_trayalloc VARCHAR(1023) NOT NULL DEFAULT '$sort_db_trayalloc',
		select_db_drives VARCHAR(1023) NOT NULL DEFAULT '$select_db_drives',
		sort_db_drives VARCHAR(1023) NOT NULL DEFAULT '$sort_db_drives',
		select_db_devices VARCHAR(1023) NOT NULL DEFAULT '$select_db_devices',
		sort_db_devices VARCHAR(1023) NOT NULL DEFAULT '$sort_db_devices'
	";
	$sql_tables_settings = "
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
		displayinfo,
		select_db_info,
		sort_db_info,
		select_db_trayalloc,
		sort_db_trayalloc,
		select_db_drives,
		sort_db_drives,
		select_db_devices,
		sort_db_devices
	";
	$sql_create_settings_group = "
		id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
		group_name VARCHAR(255) NULL,
		group_color CHAR(6) NULL,
		grid_count VARCHAR(6) NOT NULL DEFAULT 'column',
		grid_columns TINYINT NOT NULL DEFAULT '4',
		grid_rows TINYINT NOT NULL DEFAULT '6',
		grid_trays SMALLINT,
		disk_tray_direction CHAR(1) NOT NULL DEFAULT 'h',
		tray_direction TINYINT NOT NULL DEFAULT '1',
		tray_start_num TINYINT NOT NULL DEFAULT '1',
		tray_width SMALLINT NOT NULL DEFAULT '400',
		tray_height SMALLINT NOT NULL DEFAULT '70'
	";
	$sql_tables_settings_group = "
		id,
		group_name,
		group_color,
		grid_count,
		grid_columns,
		grid_rows,
		grid_trays,
		disk_tray_direction,
		tray_direction,
		tray_start_num,
		tray_width,
		tray_height
	";

	/*
		Database Version: 8
	*/
	
	$sql_tables_disks_v8 = "
		id,
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
		purchased,
		warranty,
		warranty_date,
		comment,
		color,
		hash
	";
	$sql_tables_settings_v8 = "
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
	";
	
	/*
		Database Version: 7
	*/
	
	$sql_tables_disks_v7 = "
		id,
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
		purchased,
		warranty,
		warranty_date,
		comment,
		color,
		hash
	";
	
	/*
		Database Version: 6
	*/
	
	$sql_tables_settings_group_v6 = "
		id,
		group_name,
		group_color,
		grid_count,
		grid_columns,
		grid_rows,
		grid_trays,
		disk_tray_direction,
		tray_direction,
		tray_width,
		tray_height
	";
	
	/*
		Database Version: 5
	*/
	
	$sql_tables_settings_v5 = "
		id,
		smart_exec_delay,
		smart_updates,
		bgcolor_parity,
		bgcolor_unraid,
		bgcolor_cache,
		bgcolor_others,
		bgcolor_empty,
		warranty_field,
		dashboard_widget,
		dashboard_widget_pos,
		displayinfo
	";
	$sql_tables_settings_group_v5 = "
		id,
		group_name,
		grid_count,
		grid_columns,
		grid_rows,
		grid_trays,
		disk_tray_direction,
		tray_direction,
		tray_width,
		tray_height
	";
	
	/*
		Database Version: 4
	*/
	
	$sql_tables_settings_v4 = "
		id,
		smart_exec_delay,
		bgcolor_parity,
		bgcolor_unraid,
		bgcolor_cache,
		bgcolor_others,
		bgcolor_empty,
		warranty_field,
		dashboard_widget,
		dashboard_widget_pos,
		displayinfo
	";
	
	/*
		Database Version: 3
	*/
	
	$sql_tables_location_v3 = "
		id,
		hash,
		empty,
		tray
	";
	$sql_tables_settings_v3 = "
		smart_exec_delay,
		bgcolor_parity,
		bgcolor_unraid,
		bgcolor_cache,
		bgcolor_others,
		bgcolor_empty,
		warranty_field,
		displayinfo
	";
	$sql_tables_settings_group_v3 = "
		grid_count,
		grid_columns,
		grid_rows,
		grid_trays,
		disk_tray_direction,
		tray_width,
		tray_height
	";
	$sql_tables_disks_v3 = "
		id,
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
		purchased,
		warranty,
		warranty_date,
		comment,
		hash
	";
	
	/*
		Database Version: 2
	*/
	
	$sql_tables_settings_v2 = "
		smart_exec_delay,
		bgcolor_unraid,
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
	";
	
	/*
		Database Version: 1
	*/
	
	$sql_tables_disks_v1 = "
		id,
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
		purchased,
		warranty,
		warranty_date,
		comment
	";
	$sql_tables_location_v1 = "
		id,
		luname,
		empty,
		tray
	";
	$sql_tables_location_conv_v2 = "
		id,
		hash,
		empty,
		tray
	";
	
	/*
		Database Version: 0
	*/
	
	$sql_tables_disks_v0 = "
		id,
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
		purchased,
		warranty,
		comment
	";
	$sql_tables_settings_v0 = "
		smart_exec_delay,
		bgcolor_unraid,
		bgcolor_others,
		bgcolor_empty,
		grid_count,
		grid_columns,
		grid_rows,
		grid_trays,
		disk_tray_direction,
		tray_width,
		tray_height
	";

// Create and update database

	if(filesize(DISKLOCATION_DB) === 0) {
		$sql = "
			CREATE TABLE disks(
				$sql_create_disks
			);
			CREATE TABLE location(
				$sql_create_location
			);
			CREATE TABLE settings(
				$sql_create_settings
			);
			CREATE TABLE settings_group(
				$sql_create_settings_group
			);
			PRAGMA user_version = '$current_db_ver';
		";
		$ret = $db->exec($sql);
		if(!$ret) {
			echo $db->lastErrorMsg();
		}
	}
	else {
		$sql = "PRAGMA user_version";
		$database_version = $db->querySingle($sql);
		
		$db_update = 0;
		
		if($database_version < 1) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE disks RENAME TO old_disks;
				ALTER TABLE settings RENAME TO old_settings;
				
				CREATE TABLE disks($sql_create_disks);
				CREATE TABLE settings($sql_create_settings);
				
				INSERT INTO disks ($sql_tables_disks_v0) SELECT $sql_tables_disks_v0 FROM old_disks;
				INSERT INTO settings ($sql_tables_settings_v0) SELECT $sql_tables_settings_v0 FROM old_settings;
				
				DROP TABLE old_disks;
				DROP TABLE old_settings;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '1';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 0;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 2) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE disks RENAME TO old_disks;
				ALTER TABLE location RENAME TO old_location;
				
				CREATE TABLE disks($sql_create_disks);
				CREATE TABLE location($sql_create_location);
				
				INSERT INTO disks ($sql_tables_disks_v1) SELECT $sql_tables_disks_v1 FROM old_disks;
				INSERT INTO location ($sql_tables_location_conv_v2) SELECT $sql_tables_location_v1 FROM old_location;
				
				DROP TABLE old_disks;
				DROP TABLE old_location;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '2';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 0;
				echo $db->lastErrorMsg();
			}
			
			// Create hashes for devices and insert them into the database
			$sql = "SELECT luname,model_family,model_name,smart_serialnumber,hash FROM disks";
			$results = $db->query($sql);
			while($data = $results->fetchArray(1)) {
				$create_deviceid_sha256 = hash('sha256', $data["model_name"] . $data["smart_serialnumber"]);
				$sql_update_v2 .= "
					UPDATE location SET
						hash = '" . $create_deviceid_sha256 . "'
					WHERE hash = '" . $data["luname"] . "'
					;
					
					UPDATE disks SET
						hash = '" . $create_deviceid_sha256 . "'
					WHERE luname = '" . $data["luname"] . "'
					;
				";
			}
			$ret = $db->exec($sql_update_v2);
			if(!$ret) {
				$db_update = 0;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 3) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE settings RENAME TO old_settings;
				
				CREATE TABLE settings($sql_create_settings);
				
				INSERT INTO settings ($sql_tables_settings_v2) SELECT $sql_tables_settings_v2 FROM old_settings;
				
				DROP TABLE old_settings;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '3';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 0;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 4) {
			$db_update = 1;
			$sql = "SELECT * FROM settings";
			$results = $db->query($sql);
	
			while($data = $results->fetchArray(1)) {
				extract($data);
			}
			
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE settings RENAME TO old_settings;
				ALTER TABLE location RENAME TO old_location;
				ALTER TABLE disks RENAME TO old_disks;
				
				CREATE TABLE settings($sql_create_settings);
				CREATE TABLE settings_group($sql_create_settings_group);
				CREATE TABLE location($sql_create_location);
				CREATE TABLE disks($sql_create_disks);
				
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
						warranty_field,
						dashboard_widget,
						dashboard_widget_pos,
						displayinfo
					)
					VALUES(
						'1',
						'" . $smart_exec_delay . "',
						'" . $smart_updates . "',
						'" . $bgcolor_parity . "',
						'" . $bgcolor_unraid . "',
						'" . $bgcolor_cache . "',
						'" . $bgcolor_others . "',
						'" . $bgcolor_empty . "',
						'" . $warranty_field. "',
						'" . $dashboard_widget . "',
						'" . $dashboard_widget_pos . "',
						'" . $displayinfo . "'
					)
				;

				INSERT INTO
				settings_group(
						group_name,
						grid_count,
						grid_columns,
						grid_rows,
						grid_trays,
						disk_tray_direction,
						tray_width,
						tray_height
					)
					VALUES(
						'',
						'" . $grid_count . "',
						'" . $grid_columns . "',
						'" . $grid_rows . "',
						'" . $grid_trays . "',
						'" . $disk_tray_direction . "',
						'" . $tray_width . "',
						'" . $tray_height . "'
					)
				;
				
				INSERT INTO location ($sql_tables_location_v3) SELECT $sql_tables_location_v3 FROM old_location;
				INSERT INTO disks ($sql_tables_disks_v3) SELECT $sql_tables_disks_v3 FROM old_disks;
				
				UPDATE location SET groupid = '1' WHERE groupid IS NULL;
				
				DELETE FROM location WHERE hash = 'empty';
				
				DROP TABLE old_settings;
				DROP TABLE old_location;
				DROP TABLE old_disks;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '4';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 0;
				echo $db->lastErrorMsg();
			}
		
		}
		
		if($database_version < 5) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE settings RENAME TO old_settings;
				
				CREATE TABLE settings($sql_create_settings);
				
				INSERT INTO settings ($sql_tables_settings_v4) SELECT $sql_tables_settings_v4 FROM old_settings;
				
				DROP TABLE old_settings;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '5';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 0;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 6) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE settings RENAME TO old_settings;
				ALTER TABLE settings_group RENAME TO old_settings_group;
				
				CREATE TABLE settings($sql_create_settings);
				CREATE TABLE settings_group($sql_create_settings_group);
				
				INSERT INTO settings ($sql_tables_settings_v5) SELECT $sql_tables_settings_v5 FROM old_settings;
				INSERT INTO settings_group ($sql_tables_settings_group_v5) SELECT $sql_tables_settings_group_v5 FROM old_settings_group;
				
				DROP TABLE old_settings;
				DROP TABLE old_settings_group;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '6';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 0;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 7) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE settings_group RENAME TO old_settings_group;
				
				CREATE TABLE settings_group($sql_create_settings_group);
				
				INSERT INTO settings_group ($sql_tables_settings_group_v6) SELECT $sql_tables_settings_group_v6 FROM old_settings_group;
				
				DROP TABLE old_settings_group;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '7';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 0;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 8) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE disks RENAME TO old_disks;
				
				CREATE TABLE disks($sql_create_disks);
				
				INSERT INTO disks ($sql_tables_disks_v7) SELECT $sql_tables_disks_v7 FROM old_disks;
				
				DROP TABLE old_disks;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '8';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 0;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 9) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE settings RENAME TO old_settings;
				ALTER TABLE disks RENAME TO old_disks;
				
				CREATE TABLE settings($sql_create_settings);
				CREATE TABLE disks($sql_create_disks);
				
				INSERT INTO settings ($sql_tables_settings_v8) SELECT $sql_tables_settings_v8 FROM old_settings;
				INSERT INTO disks ($sql_tables_disks_v8) SELECT $sql_tables_disks_v8 FROM old_disks;
				
				DROP TABLE old_settings;
				DROP TABLE old_disks;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '9';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 0;
				echo $db->lastErrorMsg();
			}
		}
		
		if($db_update) {
			//$db->close();
			print("<h3>Database successfully updated</h3><!-- ', refreshing...'+ <meta http-equiv=\"refresh\" content=\"3;url=" . DISKLOCATION_URL . "\" />-->");
			//exit;
		}
	}
?>
