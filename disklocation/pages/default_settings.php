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
	 
//	Common settings
//	Variable name		Default value			Description
//	--------------------------------------------------------------------------------
	$smart_exec_delay =		'200';			// set milliseconds for next execution for SMART shell_exec - needed to actually grab all the information for unassigned devices. Default: 200
	$bgcolor_parity =		'aa2d2f';		// background color for Unraid parity disks / critical temp // old default: ca3f33
	$bgcolor_unraid =		'be7317';		// background color for Unraid data disks / warning temp // old default: ef6441 / ca7233
	$bgcolor_cache =		'5b7845';		// background color for Unraid cache disks / normal temp // old default: ff884c / cabd33
	$bgcolor_others =		'7c7c7c';		// background color for unassigned/other disks / unknown temp // old default: 41b5ef / 3398ca
	$bgcolor_empty =		'303030';		// background color for empty trays // old default: aaaaaa / 7c7c7c
	$tray_reduction_factor =	'10';			// set the scale divider for the mini tray layout
	$force_orb_led =		'0';			// set the LED to 0: show Unraid icons (triangle warning / hot critical) - 1: show circle LEDs (color coded circles).
	$device_bg_color =		'1';			// choose background for the drives, Drive Type (0) or Heat Map (1)
	$serial_trim		 = 	'0';			// make serial number friendlier, substr() value -99 - 99.
	$displayinfo =	array(			// this will store an array of display settings for the "Device" page.
		'tray' => 1,
		'leddiskop' => 1,
		'ledsmart' => 1,
		'ledtemp' => 1,
		'temperature' => 1,
		'hideemptycontents' => 0,
		'flashwarning' => 0,
		'flashcritical' => 1
	);
	
	$select_db_info = "group,tray,manufacturer,model,serial,capacity,cache,rotation,formfactor,read,written,manufactured,purchased,installed,expires,comment";
	$sort_db_info = "asc:group,tray";
	
	// mandatory: group,tray,locate,color
	$select_db_trayalloc = "node,manufacturer,model,serial,capacity,rotation,formfactor,manufactured,purchased,installed,warranty,comment";
	$sort_db_trayalloc = "asc:group,tray";
	
	$select_db_drives = "manufacturer,model,serial,capacity,rotation,formfactor,manufactured,purchased,installed,removed,comment";
	$sort_db_drives = "asc:serial";
	
	//not used, but prepared just in case it will be added in the future:
	$select_db_devices = "[huge]*pool*[/huge] name node capacity rotation formfactor [color:11ff00]*[serial]*[/color]\r\nmanufacturer model\r\ncomment";
	
//	Group settings
	
	$group_color = 		$bgcolor_empty;	// set default group background color to "empty/disabled"
	$grid_count =		'column';	// how to count the trays: [column]: trays ordered from top to bottom from left to right | [row]: ..from left to right from top to bottom
	$grid_columns =		'4';		// number of horizontal trays
	$grid_rows =		'6';		// number of verical trays
	$grid_trays = 		'';		// total number of trays. default this is (grid_columns * grid_rows), but we choose to add some flexibility for drives outside normal trays
	$disk_tray_direction =	'h';		// direction of the hard drive trays [h]horizontal | [v]ertical
	$tray_direction =	'1';		// tray count direction
	$tray_start_num = 	'1';		// tray count start number, 0 or 1
	$tray_width =		'400';		// the pixel width of the hard drive tray: in the horizontal direction ===
	$tray_height =		'70';		// the pixel height of the hard drive tray: in the horizontal direction ===
?>
