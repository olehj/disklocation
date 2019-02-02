<?php
/*
	Database Version: current
*/

//	Variable name		Default value	Description
//	--------------------------------------------------------------------------------
	$smart_exec_delay =	'200';		// set milliseconds for next execution for SMART shell_exec - needed to actually grab all the information for unassigned devices. Default: 200
	$bgcolor_parity =	'eb4f41';	// background color for Unraid parity disks
	$bgcolor_unraid =	'ef6441';	// background color for Unraid data disks
	$bgcolor_cache =	'ff884c';	// background color for Unraid cache disks
	$bgcolor_others =	'41b5ef';	// background color for unassigned/other disks
	$bgcolor_empty =	'aaaaaa';	// background color for empty trays
	$grid_count =		'column';	// how to count the trays: [column]: trays ordered from top to bottom from left to right | [row]: ..from left to right from top to bottom
	$grid_columns =		'4';		// number of horizontal trays
	$grid_rows =		'6';		// number of verical trays
	$grid_trays = 		'';		// total number of trays. default this is (grid_columns * grid_rows), but we choose to add some flexibility for drives outside normal trays
	$disk_tray_direction =	'h';		// direction of the hard drive trays [h]horizontal | [v]ertical
	$tray_width =		'400';		// the pixel width of the hard drive tray: in the horizontal direction ===
	$tray_height =		'70';		// the pixel height of the hard drive tray: in the horizontal direction ===
	$warranty_field =	'u';		// choose [u]nraid's way of entering warranty date (12/24/36... months) or enter [m]anual ISO dates.
	$tempunit =		'C';		// choose default temperature unit to display, default is [C]elsius (as reported from harddisks), other options: [F]arenheit | [K]elvin.
	$displayinfo =	json_encode(array(	// this will store an json_encoded array of display settings for the "Device" page.
		'tray' => 1,
		'leddiskop' => 1,
		'ledsmart' => 1,
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
		'formfactor' => 1
	));

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
		status CHAR(1),
		purchased DATE,
		warranty SMALLINT,
		warranty_date DATE,
		comment VARCHAR(255),
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
		status,
		purchased,
		warranty,
		warranty_date,
		comment,
		hash
	";
	$sql_create_location = "
		id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
		hash VARCHAR(64) UNIQUE NOT NULL,
		empty VARCHAR(255),
		tray SMALLINT
	";
	$sql_tables_location = "
		id,
		hash,
		empty,
		tray
	";
	$sql_create_settings = "
		smart_exec_delay INT NOT NULL DEFAULT '200',
		bgcolor_parity CHAR(6) NOT NULL DEFAULT 'eb4f41',
		bgcolor_unraid CHAR(6) NOT NULL DEFAULT 'ef6441',
		bgcolor_cache CHAR(6) NOT NULL DEFAULT 'ff884c',
		bgcolor_others CHAR(6) NOT NULL DEFAULT '41b5ef',
		bgcolor_empty CHAR(6) NOT NULL DEFAULT 'aaaaaa',
		grid_groups SMALLINT,
		grid_count VARCHAR(6) NOT NULL DEFAULT 'column',
		grid_columns TINYINT NOT NULL DEFAULT '4',
		grid_rows TINYINT NOT NULL DEFAULT '6',
		grid_trays SMALLINT,
		disk_tray_direction CHAR(1) NOT NULL DEFAULT 'h',
		tray_width SMALLINT NOT NULL DEFAULT '400',
		tray_height SMALLINT NOT NULL DEFAULT '70',
		warranty_field CHAR(1) NOT NULL DEFAULT 'u',
		tempunit CHAR(1) NOT NULL DEFAULT 'C',
		displayinfo VARCHAR(1023)
	";
	$sql_tables_settings = "
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
			PRAGMA user_version = '3';
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
		
		if($db_update) {
			$db->close();
			print("<h3>Database successfully updated, refreshing...</h3><meta http-equiv=\"refresh\" content=\"3;url=" . DISKLOCATION_URL . "\" />");
			exit;
		}
	}
?>
