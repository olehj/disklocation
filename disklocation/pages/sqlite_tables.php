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
	
//	Database Version:
	$current_db_ver = 13;

	if(in_array("version", $argv)) {
		print($current_db_ver);
		exit();
	}
	
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
		smart_cache INT,
		smart_rotation INT,
		smart_formfactor VARCHAR(16),
		smart_reallocated_sector_count INT,
		smart_reported_uncorrectable_errors INT,
		smart_command_timeout INT,
		smart_current_pending_sector_count INT,
		smart_offline_uncorrectable INT,
		smart_logical_block_size INT,
		smart_nvme_available_spare INT,
		smart_nvme_available_spare_threshold INT,
		smart_nvme_percentage_used INT,
		smart_units_read INT,
		smart_units_written INT,
		status CHAR(1),
		benchmark_r INT,
		benchmark_w INT,
		manufactured DATE,
		purchased DATE,
		installed DATE,
		removed DATE,
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
		smart_cache,
		smart_rotation,
		smart_formfactor,
		smart_reallocated_sector_count,
		smart_reported_uncorrectable_errors,
		smart_command_timeout,
		smart_current_pending_sector_count,
		smart_offline_uncorrectable,
		smart_logical_block_size,
		smart_nvme_available_spare,
		smart_nvme_available_spare_threshold,
		smart_nvme_percentage_used,
		smart_units_read,
		smart_units_written,
		status,
		benchmark_r,
		benchmark_w,
		manufactured,
		purchased,
		installed,
		removed,
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
		force_orb_led INT NULL,
		warranty_field CHAR(1) NOT NULL DEFAULT '$warranty_field',
		dashboard_widget CHAR(3) NOT NULL DEFAULT '$dashboard_widget',
		dashboard_widget_pos INT NULL,
		reallocated_sector_w INT NOT NULL DEFAULT '$reallocated_sector_w',
		reported_uncorr_w INT NOT NULL DEFAULT '$reported_uncorr_w',
		command_timeout_w INT NOT NULL DEFAULT '$command_timeout_w',
		pending_sector_w INT NOT NULL DEFAULT '$pending_sector_w',
		offline_uncorr_w INT NOT NULL DEFAULT '$offline_uncorr_w',
		css_serial_number_highlight VARCHAR(1023) NOT NULL DEFAULT '$css_serial_number_highlight',
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
		force_orb_led,
		warranty_field,
		dashboard_widget,
		dashboard_widget_pos,
		reallocated_sector_w,
		reported_uncorr_w,
		command_timeout_w,
		pending_sector_w,
		offline_uncorr_w,
		css_serial_number_highlight,
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
		Database Version: 11
	*/
	
	$sql_create_settings_v11 = "
		id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
		smart_exec_delay INT NOT NULL DEFAULT '$smart_exec_delay',
		smart_updates VARCHAR(8) NOT NULL DEFAULT '$smart_updates',
		bgcolor_parity CHAR(6) NOT NULL DEFAULT '$bgcolor_parity',
		bgcolor_unraid CHAR(6) NOT NULL DEFAULT '$bgcolor_unraid',
		bgcolor_cache CHAR(6) NOT NULL DEFAULT '$bgcolor_cache',
		bgcolor_others CHAR(6) NOT NULL DEFAULT '$bgcolor_others',
		bgcolor_empty CHAR(6) NOT NULL DEFAULT '$bgcolor_empty',
		tray_reduction_factor FLOAT NOT NULL DEFAULT '$tray_reduction_factor',
		force_orb_led FLOAT NOT NULL DEFAULT '$force_orb_led',
		warranty_field CHAR(1) NOT NULL DEFAULT '$warranty_field',
		dashboard_widget CHAR(3) NOT NULL DEFAULT '$dashboard_widget',
		dashboard_widget_pos INT NULL,
		reallocated_sector_w INT NOT NULL DEFAULT '$reallocated_sector_w',
		reported_uncorr_w INT NOT NULL DEFAULT '$reported_uncorr_w',
		command_timeout_w INT NOT NULL DEFAULT '$command_timeout_w',
		pending_sector_w INT NOT NULL DEFAULT '$pending_sector_w',
		offline_uncorr_w INT NOT NULL DEFAULT '$offline_uncorr_w',
		css_serial_number_highlight VARCHAR(1023) NOT NULL DEFAULT '$css_serial_number_highlight',
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
	$sql_tables_settings_v11 = "
		id,
		smart_exec_delay,
		smart_updates,
		bgcolor_parity,
		bgcolor_unraid,
		bgcolor_cache,
		bgcolor_others,
		bgcolor_empty,
		tray_reduction_factor,
		force_orb_led,
		warranty_field,
		dashboard_widget,
		dashboard_widget_pos,
		reallocated_sector_w,
		reported_uncorr_w,
		command_timeout_w,
		pending_sector_w,
		offline_uncorr_w,
		css_serial_number_highlight,
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
	
	/*
		Database Version: 10
	*/
	
	$sql_create_settings_v10 = "
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
		reallocated_sector_w INT NOT NULL DEFAULT '$reallocated_sector_w',
		reported_uncorr_w INT NOT NULL DEFAULT '$reported_uncorr_w',
		command_timeout_w INT NOT NULL DEFAULT '$command_timeout_w',
		pending_sector_w INT NOT NULL DEFAULT '$pending_sector_w',
		offline_uncorr_w INT NOT NULL DEFAULT '$offline_uncorr_w',
		css_serial_number_highlight VARCHAR(1023) NOT NULL DEFAULT '$css_serial_number_highlight',
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
	$sql_tables_settings_v10 = "
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
		reallocated_sector_w,
		reported_uncorr_w,
		command_timeout_w,
		pending_sector_w,
		offline_uncorr_w,
		css_serial_number_highlight,
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
	
	/*
		Database Version: 9
	*/
	
	$sql_tables_disks_v9 = "
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
		smart_reallocated_sector_count,
		smart_reported_uncorrectable_errors,
		smart_command_timeout,
		smart_current_pending_sector_count,
		smart_offline_uncorrectable,
		smart_logical_block_size,
		smart_nvme_available_spare,
		smart_nvme_available_spare_threshold,
		smart_nvme_percentage_used,
		smart_units_read,
		smart_units_written,
		status,
		manufactured,
		purchased,
		warranty,
		warranty_date,
		comment,
		color,
		hash
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
	$sql_tables_disks_v8_conv_v9 = "
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
		smart_units_read,
		smart_units_written,
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
	
// Create and update database
	if(filesize(DISKLOCATION_DB) === 0) {
		/*$sql = "
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
		*/
		$db->close();
		unlink(DISKLOCATION_DB);
	}
	else {
		$sql = "PRAGMA user_version";
		$database_version = $db->querySingle($sql);
		
		$db_update = 0;
		
		// make automatic backup if the database is being upgraded:
		if($database_version < $current_db_ver) {
			if(file_exists(DISKLOCATION_DB)) {
				$datetime = date("Ymd-His");
				mkdir(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/" . $datetime, 0700, true);
				
				$sqlite_db = file_get_contents(DISKLOCATION_DB);
				$sqlite_db_gzdata = gzencode($sqlite_db, 9);
				file_put_contents(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/backup/" . $datetime . "/disklocation.sqlite.gz", $sqlite_db_gzdata);
			}
		}
		
		// Version below "next" is not supported anymore:
		
		if($database_version < 3) {
			// database to old, deleting to create a new one...
			unlink(DISKLOCATION_DB);
			print("<h3 style=\"color: #FF0000;\">Database too old, a new fresh one will be created. </h3><meta http-equiv=\"refresh\" content=\"3;url=" . DISKLOCATION_URL . "\" /><br />Refreshing...");
			exit;
			
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
				$db_update = 2;
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
				$db_update = 2;
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
				$db_update = 2;
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
				$db_update = 2;
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
				$db_update = 2;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 9) {
			$db_update = 1;
			$insert_sql = "";
			
			$sql = "SELECT smart_nvme_data_units_read FROM disks;";
			$results = $db->exec($sql);
			
			if(!$results) {
				// did not find the old name for smart_units_*
				$insert_sql = "INSERT INTO disks ($sql_tables_disks_v8_conv_v9) SELECT $sql_tables_disks_v8_conv_v9 FROM old_disks;";
			}
			else {
				$insert_sql = "
					ALTER TABLE old_disks RENAME COLUMN smart_nvme_data_units_read TO smart_units_read;
					ALTER TABLE old_disks RENAME COLUMN smart_nvme_data_units_written TO smart_units_written;
					
					INSERT INTO disks ($sql_tables_disks_v8_conv_v9) SELECT $sql_tables_disks_v8_conv_v9 FROM old_disks;
				";
			}
			
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE settings RENAME TO old_settings;
				ALTER TABLE disks RENAME TO old_disks;
				
				CREATE TABLE settings($sql_create_settings);
				CREATE TABLE disks($sql_create_disks);
				
				INSERT INTO settings ($sql_tables_settings_v8) SELECT $sql_tables_settings_v8 FROM old_settings;
				" . $insert_sql . "
				
				DROP TABLE old_settings;
				DROP TABLE old_disks;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '9';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 2;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 10) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE disks RENAME TO old_disks;
				
				CREATE TABLE disks($sql_create_disks);
				
				INSERT INTO disks ($sql_tables_disks_v9) SELECT $sql_tables_disks_v9 FROM old_disks;
				
				DROP TABLE old_disks;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '10';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 2;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 11) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE settings RENAME TO old_settings;
				
				CREATE TABLE settings($sql_create_settings);
				
				INSERT INTO settings ($sql_tables_settings_v10) SELECT $sql_tables_settings_v10 FROM old_settings;
				
				DROP TABLE old_settings;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '11';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 2;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 12) {
			$db_update = 1;
			$sql = "
				PRAGMA foreign_keys = off;
				
				BEGIN TRANSACTION;
				
				ALTER TABLE settings RENAME TO old_settings;
				
				CREATE TABLE settings($sql_create_settings);
				
				INSERT INTO settings ($sql_tables_settings_v11) SELECT $sql_tables_settings_v11 FROM old_settings;
				
				DROP TABLE old_settings;
				
				COMMIT;
				
				PRAGMA foreign_keys = on;
				PRAGMA user_version = '12';
				
				VACUUM;
			";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 2;
				echo $db->lastErrorMsg();
			}
		}
		
		if($database_version < 13) {
			$db_update = 1;
			
		// Settings
			if(file_exists(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.conf")) {
				$settings = $get_disklocation_config; // = json_decode(file_get_contents(DISKLOCATION_CONF), true);
			}
			( file_exists(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.noscan") ? unlink(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.noscan") : null );
			
			$removed_selectors = array("reallocated", "reported", "timeout", "pending", "offline", "bench_r", "bench_w");
			
			$sql = "SELECT * FROM settings";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 2;
				echo $db->lastErrorMsg();
			}
			else {
				$results = $db->query($sql);
				$sql_hash = array();
				while($res = $results->fetchArray(1)) {
					$settings["bgcolor_parity"] = strtoupper($res["bgcolor_parity"]);
					$settings["bgcolor_unraid"] = strtoupper($res["bgcolor_unraid"]);
					$settings["bgcolor_cache"] = strtoupper($res["bgcolor_cache"]);
					$settings["bgcolor_others"] = strtoupper($res["bgcolor_others"]);
					$settings["bgcolor_empty"] = strtoupper($res["bgcolor_empty"]);
					$settings["tray_reduction_factor"] = $res["tray_reduction_factor"];
					$settings["force_orb_led"] = $res["force_orb_led"];
					$settings["device_bg_color"] = $res["dashboard_widget"];
					$settings["serial_trim"] = $res["dashboard_widget_pos"];
					$settings["displayinfo"] = json_decode($res["displayinfo"], true);
					$settings["select_db_info"] = implode(",", array_filter(explode(",", preg_replace("/\b(" . implode("|", $removed_selectors) . ")\b/", "", $res["select_db_info"]))));
					$settings["sort_db_info"] = implode(",", array_filter(explode(",", preg_replace("/\b(" . implode("|", $removed_selectors) . ")\b/", "", $res["sort_db_info"]))));
					$settings["select_db_trayalloc"] = implode(",", array_filter(explode(",", preg_replace("/\b(" . implode("|", $removed_selectors) . ")\b/", "", $res["select_db_trayalloc"]))));
					$settings["sort_db_trayalloc"] = implode(",", array_filter(explode(",", preg_replace("/\b(" . implode("|", $removed_selectors) . ")\b/", "", $res["sort_db_trayalloc"]))));
					$settings["select_db_drives"] = implode(",", array_filter(explode(",", preg_replace("/\b(" . implode("|", $removed_selectors) . ")\b/", "", $res["select_db_drives"]))));
					$settings["sort_db_drives"] = implode(",", array_filter(explode(",", preg_replace("/\b(" . implode("|", $removed_selectors) . ")\b/", "", $res["sort_db_drives"]))));
					$settings["select_db_devices"] = $select_db_devices_default;
				}
			}
			if(!file_put_contents(DISKLOCATION_CONF, json_encode($settings, JSON_PRETTY_PRINT))) {
				print("ERROR: could not save file " . DISKLOCATION_CONF . "");
			}
			
		// Locations
			$sql = "SELECT * FROM location";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 2;
				echo $db->lastErrorMsg();
			}
			else {
				$results = $db->query($sql);
				$sql_hash = array();
				while($res = $results->fetchArray(1)) {
					$locations[$res["hash"]]["groupid"] = $res["groupid"];
					$locations[$res["hash"]]["tray"] = $res["tray"];
				}
			}
			if(!file_put_contents(DISKLOCATION_LOCATIONS, json_encode($locations, JSON_PRETTY_PRINT))) {
				print("ERROR: could not save file " . DISKLOCATION_LOCATIONS . "");
			}
			
		// Groups
			$sql = "SELECT * FROM settings_group";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 2;
				echo $db->lastErrorMsg();
			}
			else {
				$results = $db->query($sql);
				$sql_hash = array();
				while($res = $results->fetchArray(1)) {
					$groups[$res["id"]]["group_name"] = $res["group_name"];
					$groups[$res["id"]]["group_color"] = strtoupper($res["group_color"]);
					$groups[$res["id"]]["grid_count"] = $res["grid_count"];
					$groups[$res["id"]]["grid_columns"] = $res["grid_columns"];
					$groups[$res["id"]]["grid_rows"] = $res["grid_rows"];
					$groups[$res["id"]]["grid_trays"] = $res["grid_trays"];
					$groups[$res["id"]]["disk_tray_direction"] = $res["disk_tray_direction"];
					$groups[$res["id"]]["tray_direction"] = $res["tray_direction"];
					$groups[$res["id"]]["tray_start_num"] = $res["tray_start_num"];
					$groups[$res["id"]]["tray_width"] = $res["tray_width"];
					$groups[$res["id"]]["tray_height"] = $res["tray_height"];
				}
			}
			if(!file_put_contents(DISKLOCATION_GROUPS, json_encode($groups, JSON_PRETTY_PRINT))) {
				print("ERROR: could not save file " . DISKLOCATION_GROUPS . "");
			}
		}
		
		// Devices
			$sql = "SELECT * FROM disks";
			$ret = $db->exec($sql);
			if(!$ret) {
				$db_update = 2;
				echo $db->lastErrorMsg();
			}
			else {
				$results = $db->query($sql);
				$sql_hash = array();
				while($res = $results->fetchArray(1)) {
					$devices[$res["hash"]]["device"] = $res["device"];
					$devices[$res["hash"]]["devicenode"] = $res["devicenode"];
					$devices[$res["hash"]]["model_name"] = $res["model_name"];
					$devices[$res["hash"]]["smart_serialnumber"] = $res["smart_serialnumber"];
					$devices[$res["hash"]]["smart_cache"] = $res["smart_cache"];
					$devices[$res["hash"]]["status"] = $res["status"];
					$devices[$res["hash"]]["color"] = strtoupper($res["color"]);
					$devices[$res["hash"]]["installed"] = $res["installed"];
					$devices[$res["hash"]]["removed"] = $res["removed"];
					$devices[$res["hash"]]["comment"] = $res["comment"];
					// store these values too, for "History", but otherwise not required. Will still be updated as long as they are in the system.
					$devices[$res["hash"]]["lun"] = $res["luname"];
					$devices[$res["hash"]]["manufacturer"] = $res["model_family"];
					$devices[$res["hash"]]["smart_status"] = $res["smart_status"];
					$devices[$res["hash"]]["powerontime"] = $res["smart_powerontime"];
					$devices[$res["hash"]]["loadcycle"] = $res["smart_loadcycle"];
					$devices[$res["hash"]]["capacity"] = $res["smart_capacity"];
					$devices[$res["hash"]]["rotation"] = $res["smart_rotation"];
					$devices[$res["hash"]]["formfactor"] = $res["smart_formfactor"];
					$devices[$res["hash"]]["logical_block_size"] = $res["smart_logical_block_size"];
					$devices[$res["hash"]]["smart_units_read"] = $res["smart_units_read"];
					$devices[$res["hash"]]["smart_units_written"] = $res["smart_units_written"];
					$devices[$res["hash"]]["manufactured"] = $res["manufactured"];
					$devices[$res["hash"]]["purchased"] = $res["purchased"];
					$devices[$res["hash"]]["installed"] = $res["installed"];
					$devices[$res["hash"]]["removed"] = $res["removed"];
					$devices[$res["hash"]]["warranty"] = $res["warranty"];
				}
			}
			if(!file_put_contents(DISKLOCATION_DEVICES, json_encode($devices, JSON_PRETTY_PRINT))) {
				print("ERROR: could not save file " . DISKLOCATION_DEVICES . "");
			}
		
		if($db_update == 1) {
			print("<h3>Database successfully converted.</h3>"); // updated.
			if(!in_array("cronjob", $argv)) {
				$db->close();
					print("<meta http-equiv=\"refresh\" content=\"3;url=" . DISKLOCATION_URL . "\" /><br />refreshing...");
				exit;
			}
		}
	}
?>
