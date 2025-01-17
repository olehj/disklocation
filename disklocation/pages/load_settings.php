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
	
	if(isset($SUBMIT_RELOAD)) {
		print("
			<script type=\"text/javascript\">
				if ( window.history.replaceState ) {
					window.history.replaceState( null, null, window.location.href );
				}
			</script>
		");
	}
	
	if(file_exists(DISKLOCATION_CONF)) {
		$get_disklocation_config = json_decode(file_get_contents(DISKLOCATION_CONF), true);
	}
	if(file_exists(DISKLOCATION_LOCATIONS)) {
		$get_locations = json_decode(file_get_contents(DISKLOCATION_LOCATIONS), true);
	}
	if(file_exists(DISKLOCATION_GROUPS)) {
		$get_groups = json_decode(file_get_contents(DISKLOCATION_GROUPS), true);
	}
	
	$displayinfo = ""; // reset variable, otherwise it will be reloaded as an array and fault.
	
	extract($get_disklocation_config);
	
	$color_array = array();
	$color_array["empty"] = $bgcolor_empty;
?>
