<?php
	/*
	 *  Copyright 2024-2025, Ole-Henrik Jakobsen
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
	$sql = "SELECT * FROM settings";
	$results = $db->query($sql);
	$displayinfo = ""; // reset variable, otherwise it will be reloaded as an array and fault.
	
	while($data = $results->fetchArray(1)) {
		extract($data);
	}
	
	$displayinfo = ( !empty($displayinfo) ? json_decode($displayinfo, true) : json_decode($displayinfo_default, true) ) ;
	
	//dashboard_toggle($dashboard_widget_pos); 
	//cronjob_timer($smart_updates);
	if($smart_updates != cronjob_current()) {
		cronjob_timer($smart_updates);
	}
	
	$color_array = array();
	$color_array["empty"] = $bgcolor_empty;
?>
