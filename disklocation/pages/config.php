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
	$vi_width = 180;
	
	/*
	$dashboard_widget_array = dashboard_toggle("info");
	$dashboard_widget = $dashboard_widget_array["current"];
	$dashboard_widget_pos = $dashboard_widget_array["position"];
	*/
	
	if(!empty($disklocation_error)) {
		$i=0;
		print("<h2 style=\"color: #FF0000; font-weight: bold;\">");
		while($i < count($disklocation_error)) {
			print("&middot; ERROR: " . $disklocation_error[$i] . "<br />");
			$i++;
		}
		print("</h2><hr style=\"border: 1px solid #FF0000;\" /><br /><br />");
	}
	
	$sql = "SELECT * FROM settings_group ORDER BY id ASC";
	$results = $db->query($sql);
	
	$group_ids = array();
	$last_group_id = 0;
	$disk_layouts_config = "";
	$count_groups = 0;
	
	while($data = $results->fetchArray(1)) {
		$group_ids[] = $data["id"];
	}
	
	while($data = $results->fetchArray(1)) {
		extract($data);
		$gid = $id;
		
		$css_grid_group = "
			grid-template-columns: " . $grid_columns_styles[$gid] . ";
			grid-template-rows: " . $grid_rows_styles[$gid] . ";
			grid-auto-flow: " . $grid_count . ";
		";
		
		$tray_direction = ( empty($tray_direction) ? 1 : $tray_direction);
		
		if($count_groups > 0) {
			$disk_layouts_config .= "
				<td style=\"max-width: 80px; vertical-align: middle; position: relative; top: -10px;\">
					<form action=\"" . DISKLOCATION_PATH . "/pages/system.php\" method=\"post\">
						<button type=\"submit\" name=\"group_swap\" title=\"Swap groups\" value=\"" . $group_ids[($count_groups-1)] . ":" . $gid . "\" style=\"background-size: 0;\"><i style=\"font-size: 500%;\" class=\"fa fa-exchange fa-lg\"></i></button>
					</form>
				</td>
			";
		}
		
		$disk_layouts_config .= "
			<td style=\"min-width: 240px; vertical-align: top;\">
				<form action=\"\" method=\"post\">
					<p>
						<b>Name:</b><br />
						<input type=\"text\" name=\"group_name\" value=\"" . stripslashes(htmlspecialchars($group_name)) . "\" style=\"width: " . $vi_width . "px;\" />
					</p>
					<blockquote class=\"inline_help\">
						Enter a name for the group, optional.
					</blockquote>
					<p>
						<b>Set sizes for trays:</b><br />
						<input type=\"number\" required min=\"100\" max=\"2000\" name=\"tray_width\" value=\"$tray_width\" style=\"width: 50px;\" /> px longest side<br />
						<input type=\"number\" required min=\"30\" max=\"700\" name=\"tray_height\" value=\"$tray_height\" style=\"width: 50px;\" /> px shortest side
					</p>
					<blockquote class=\"inline_help\">
						This is the HTML/CSS pixel size for a single harddisk tray, default sizes are: 400px longest side, and 70px shortest side.
					</blockquote>
					<p>
						<b>Set grid size:</b><br />
						<input type=\"number\" required min=\"1\" max=\"255\" name=\"grid_columns\" value=\"$grid_columns\" style=\"width: 50px;\" /> columns<br />
						<input type=\"number\" required min=\"1\" max=\"255\" name=\"grid_rows\" value=\"$grid_rows\" style=\"width: 50px;\" /> rows<br />
						<input type=\"number\" min=\"$grid_columns * $grid_rows\" max=\"255\" name=\"grid_trays\" value=\"" . ( ( empty($grid_trays) ) ? null : $grid_trays ) . "\" style=\"width: 50px;\" /> total trays, override
					</p>
					<blockquote class=\"inline_help\">
						Set columns and rows to simulate the looks of your trays, ex. 4 columns * 6 rows = 24 total trays. However, you can override the total amount for additional drives you might have which you don\"t want to include in the main setup. The total trays will always scale unless you enter a larger value yourself. This value can be left blank for saving.
					</blockquote>
					<p>
						<b>Set physical tray direction:</b><br />
						<input type=\"radio\" name=\"disk_tray_direction\" value=\"h\" " . ( ($disk_tray_direction == "h") ? "checked" : null ) . " />horizontal
						<input type=\"radio\" name=\"disk_tray_direction\" value=\"v\" " . ( ($disk_tray_direction == "v") ? "checked" : null ) . " />vertical
					</p>
					<blockquote class=\"inline_help\">
						This is the direction of the tray itself. Is it laying flat/horizontal, or is it vertical?
					</blockquote>
					<p>
						<b>Tray assigment count properties:</b><br />
						<input type=\"radio\" name=\"grid_count\" value=\"column\" " . ( ($grid_count == "column") ? "checked" : null ) . " />count rows
						<input type=\"radio\" name=\"grid_count\" value=\"row\" " . ( ($grid_count == "row") ? "checked" : null ) . " />count colums
					</p>
					<blockquote class=\"inline_help\">
						Select how to count the tray:<br />
						&middot; column: \"top to bottom\" or \"bottom to top\"<br />
						&middot; row: \"left to right\" or \"right to left\"
					</blockquote>
					<p>
						<b>Tray assigment count direction:</b><br />
						<input type=\"radio\" name=\"tray_direction\" value=\"1\" " . ( ($tray_direction == 1) ? "checked" : null ) . " />left/top
						<input type=\"radio\" name=\"tray_direction\" value=\"2\" " . ( ($tray_direction == 2) ? "checked" : null ) . " />left/bottom
						<br />
						<input type=\"radio\" name=\"tray_direction\" value=\"3\" " . ( ($tray_direction == 3) ? "checked" : null ) . " />right/top
						<input type=\"radio\" name=\"tray_direction\" value=\"4\" " . ( ($tray_direction == 4) ? "checked" : null ) . " />right/bottom
					</p>
					<blockquote class=\"inline_help\">
						Select the direction you want to count the trays.
					</blockquote>
					<p>
						<b>Tray count start:</b><br />
						<input type=\"number\" required min=\"0\" max=\"9999999\" name=\"tray_start_num\" value=\"$tray_start_num\" style=\"width: 50px;\" />
					</p>
					<blockquote class=\"inline_help\">
						<p>Start counting tray from the entered number.</p>
					</blockquote>
					<p style=\"text-align: center;\">
						<input type=\"hidden\" name=\"groupid\" value=\"$gid\" />
						<input type=\"submit\" name=\"save_groupsettings\" value=\"Save\" />
					</p>
					<blockquote class=\"inline_help\">
						<p>Save the Disk Tray Layout. This does not save the Common Configuration and the Visible Frontpage Information.</p>
					</blockquote>
					<hr style=\"border: 1px solid black; height: 0!important;\" />
					<div class=\"grid-container\" style=\"" . $css_grid_group . "\">
						" . $disklocation_layout[$id] . "
					</div>
					<blockquote class=\"inline_help\">
						This shows you an overview of your configured tray layout
					</blockquote>
				</form>
			</td>
		";
		$last_group_id = $gid;
		$count_groups++;
	}
	
	$smart_updates_file = cronjob_current();
	
	$database_noscan = config(DISKLOCATION_CONF, 'r', 'database_noscan');
	
	/*
	if(empty($dashboard_widget) || $dashboard_widget == "on" || $dashboard_widget == "off") {
		$dashboard_widget = 0;
	}
	*/
	
	list($table_order_user, $table_order_system, $table_order_name, $table_order_full) = get_table_order("all", 0);
	
	$arr_length = count($table_order_user);
	for($i=0;$i<$arr_length;$i++) {
		$inlinehelp_table_order .= "<tr style=\"white-space: nowrap; border: 1px solid black;\"><td style=\"white-space: nowrap;margin: 0; padding: 0 5px 0 5px;\">" . $table_order_user[$i] . "</td><td style=\"white-space: nowrap;margin: 0; padding: 0 5px 0 5px;\">" . $table_order_name[$i] . "</td><td style=\"margin: 0; padding: 0 5px 0 5px;\">" . $table_order_full[$i] . "</td></tr>";
	}
?>
<?php if($db_update == 2) { print("<h3>Page unavailable due to database error.</h3><!--"); } ?>
<datalist id="disklocationColorsDef">
	<option>#<?php echo $bgcolor_parity ?></option>
	<option>#<?php echo $bgcolor_unraid ?></option>
	<option>#<?php echo $bgcolor_cache ?></option>
	<option>#<?php echo $bgcolor_others ?></option>
	<option>#<?php echo $bgcolor_empty ?></option>
	<?php echo ( $bgcolor_parity != $bgcolor_parity_default ? "<option>#" . $bgcolor_parity_default . "</option>" : null ) ?>
	<?php echo ( $bgcolor_unraid != $bgcolor_unraid_default ? "<option>#" . $bgcolor_unraid_default . "</option>" : null ) ?>
	<?php echo ( $bgcolor_cache != $bgcolor_cache_default ? "<option>#" . $bgcolor_cache_default . "</option>" : null ) ?>
	<?php echo ( $bgcolor_others != $bgcolor_others_default ? "<option>#" . $bgcolor_others_default . "</option>" : null ) ?>
	<?php echo ( $bgcolor_empty != $bgcolor_empty_default ? "<option>#" . $bgcolor_empty_default . "</option>" : null ) ?>
</datalist>
<script>
$(document).ready(function(){
	$('input:radio[name="dashboard_widget"]').change(function(){
	var n = $(this).val();
	switch(n) {
		case '0':
			$('#disp_parity').html("Parity");
			$('#disp_data').html("Data");
			$('#disp_cache').html("Cache");
			$('#disp_unassigned').html("Unassigned devices");
			break;
		case '1':
			$('#disp_parity').html("Critical");
			$('#disp_data').html("Warning");
			$('#disp_cache').html("Normal");
			$('#disp_unassigned').html("Temperature N/A");
			break;
        }
    });
});
</script>
<form action="" method="post">
	<table>
		<tr>
			<td style="width: 250px; vertical-align: top;">
				<h2>Common Configuration</h2>
				<p>
					<b>Change background colors:</b>
				</p>
				<p>
					<input type="radio" name="dashboard_widget" id="bgcolor_display_0" value="0" <?php if($dashboard_widget == "0") echo "checked"; // reusing the deprecated dashboard variable instead of messing with the database ?> />Disk Type
					<input type="radio" name="dashboard_widget" id="bgcolor_display_1" value="1" <?php if($dashboard_widget == "1") echo "checked"; // reusing the deprecated dashboard variable instead of messing with the database ?> />Heat Map
				</p>
				<blockquote class='inline_help'>
					Choose "Disk Type" for the traditional color scheme over the array and disk type.<br />
					Choose "Heat Map" for backgrounds that depends on the temperature range set in Unraid, per disk or global.
				</blockquote>
				<div style="padding-top: 20px;">
					<table>
						<tr>
							<td style="padding: 0;">
								<div id="disp_parity"><?php echo (!$dashboard_widget ? "Parity" : "Critical") ?></div>
							</td>
							<td style="padding: 0;">
								<div id="disp_data"><?php echo (!$dashboard_widget ? "Data" : "Warning") ?></div></div>
							</td>
							<td style="padding: 0;">
								<div id="disp_cache"><?php echo (!$dashboard_widget ? "Cache" : "Normal") ?></div></div>
							</td>
						</tr>
						<tr>
							<td style="padding: 0;">
								<input type="color" required name="bgcolor_parity" list="disklocationColorsDef" value="#<?php print($bgcolor_parity); ?>" />
							</td>
							<td style="padding: 0;">
								<input type="color" required name="bgcolor_unraid" list="disklocationColorsDef" value="#<?php print($bgcolor_unraid); ?>" />
							</td>
							<td style="padding: 0;">
								<input type="color" required name="bgcolor_cache" list="disklocationColorsDef" value="#<?php print($bgcolor_cache); ?>" />
							</td>
						</tr>
						<tr>
							<td style="padding: 0;">
								<input type="color" required name="bgcolor_others" list="disklocationColorsDef" value="#<?php print($bgcolor_others); ?>" />
							</td>
							<td style="padding: 0;" colspan="2">
								<div id="disp_unassigned"><?php echo (!$dashboard_widget ? "Unassigned devices" : "Temperature N/A") ?></div>
							</td>
						</tr>
						<tr>
							<td style="padding: 0;">
								<input type="color" required name="bgcolor_empty" list="disklocationColorsDef" value="#<?php print($bgcolor_empty); ?>" />
							</td>
							<td style="padding: 0;" colspan="2">
								Empty trays
							</td>
						</tr>
					</table>
				</div>
				<blockquote class='inline_help'>
					<dt>Select the color(s) you want, defaults are:</dt>
					<ul>
						<li><?php echo $bgcolor_parity_default ?> "Parity"</li>
						<li><?php echo $bgcolor_unraid_default ?> "Data"</li>
						<li><?php echo $bgcolor_cache_default ?> "Cache"</li>
						<li><?php echo $bgcolor_others_default ?> "Unassigned devices"</li>
						<li><?php echo $bgcolor_empty_default ?> "Empty/available trays"</li>
					</ul>
				</blockquote>
				<p>
					<b>Set the size divider for mini layout:</b><br />
					<input type="number" required min="1" max="1000" step="0.1" name="tray_reduction_factor" value="<?php print($tray_reduction_factor); ?>" style="width: 50px;" />
				</p>
				<blockquote class='inline_help'>
					This number will divide from the set height and width sizes defined per group, and display its divided size as a mini layout/dashboard device. Default: 10 [1.0-1000.0 stepping 0.1]. Larger number is smaller in size.
				</blockquote>
				<p>
					<b>LED display:</b><br />
					<input type="radio" name="force_orb_led" value="0" <?php if($force_orb_led == 0) echo "checked"; ?> />Unraid icons
					<input type="radio" name="force_orb_led" value="1" <?php if($force_orb_led == 1) echo "checked"; ?>/>Circular LEDs
				</p>
				<blockquote class='inline_help'>
					Show how to display the LEDs on the overview and dashboard. Unraid icons will show triangluar warning signs, crossed critival signs etc. Circular LEDs will all be color coded circular lights.
				</blockquote>
				<p>
					<b>Set warranty date entry:</b><br />
					<input type="radio" name="warranty_field" value="u" <?php if($warranty_field == "u") echo "checked"; ?> />Unraid
					<input type="radio" name="warranty_field" value="m" <?php if($warranty_field == "m") echo "checked"; ?>/>Manual ISO
				</p>
				
				<blockquote class='inline_help'>
					Select how you want to enter the warranty date: the Unraid way of selecting amount of months, or manual ISO date for specific dates. Both values can be stored, but only one can be visible at a time.
				</blockquote>
				<p>
					<b>Trim serial numbers:</b><br />
					<input type="number" required min="-99" max="99" step="1" name="dashboard_widget_pos" value="<?php print($dashboard_widget_pos); // reusing the deprecated dashboard_pos variable instead of messing with the database ?>" style="width: 50px;" />
				</p>
				<blockquote class='inline_help'>
					Serial number will be cut either the first or last part of this value, 0 does nothing. Negative number will display X last characters, positive the X first characters.
					The sort function will still sort after the actual serial number, and not the shortened ones.
					<br />
				</blockquote>
				<p>
					<b>User styles for serial numbers:</b><br />
					<input type="text" maxlenght="1000" name="css_serial_number_highlight" value="<?php print($css_serial_number_highlight); ?>" style="width: 250px;" />
				</p>
				<blockquote class='inline_help'>
					Styles for serial number, pure CSS expected.
					<br />
				</blockquote>
			</td>
			<td style="padding-left: 25px; vertical-align: top;">
				<h2>Updates</h2>
				<p>
					<b>Automatic system boot and update scan:</b><br />
					<input type="radio" name="database_noscan" value="0" <?php if($database_noscan == 0) echo "checked"; ?> />Enabled
					<input type="radio" name="database_noscan" value="1" <?php if($database_noscan == 1) echo "checked"; ?> />Disabled
				</p>
				<blockquote class='inline_help'>
					Enable or disable the auto scan during a plugin installation, update or system boot. If it's disabled it will rely on manual updates (Force Scan All) and S.M.A.R.T update schedules (cronjob).
					Should likely be disabled if using custom database location which requires Unraid to start and mount arrays.
				</blockquote>
				<p>
					<b>S.M.A.R.T updates:</b><br />
					<input type="radio" name="smart_updates" value="hourly" <?php if($smart_updates_file == "hourly") echo "checked"; ?> />Hourly
					<input type="radio" name="smart_updates" value="daily" <?php if($smart_updates_file == "daily") echo "checked"; ?> />Daily
					<input type="radio" name="smart_updates" value="weekly" <?php if($smart_updates_file == "weekly") echo "checked"; ?> />Weekly
					<input type="radio" name="smart_updates" value="monthly" <?php if($smart_updates_file == "monthly") echo "checked"; ?> />Monthly
					<input type="radio" name="smart_updates" value="disabled" <?php if($smart_updates_file == "disabled") echo "checked"; ?> />Disabled
					<input type="hidden" name="smart_updates_url" value="<?php print($GLOBALS["nginx"]["NGINX_DEFAULTURL"]); ?>" />
				</p>
				<blockquote class='inline_help'>
					Choose how often you want the S.M.A.R.T data to be updated, or disable it.<br />
					Recommended: daily or longer for flash devices, hourly can probably be safely used if Unraid is installed on a HDD, SSD, NVME device.<br />
					Earlier it updated hourly, which caused some flash memories to wear out over time. The new default is "Disabled"<br />
					Be aware that S.M.A.R.T status LED and other data will be less accurate the longer interval you set.<br />
					If it's disabled, you have to run "Force Scan All" under "Tray Allocation" to update the information manually.
				</blockquote>
				<p>
					<b>S.M.A.R.T execution delay:</b><br />
					<input type="number" required min="0" max="5000" name="smart_exec_delay" value="<?php print($smart_exec_delay); ?>" style="width: 50px;" />ms
				</p>
				<blockquote class='inline_help'>
					This is a delay for execution of the next smartctl command in a loop, this might be necessary to be able to read all the S.M.A.R.T data from all the drives. Default value is 200ms, and seems to work very well. If you realize it won't detect all the data you can increase this value, but hardly any point decreasing it.
				</blockquote>
				<p style="margin: 0;">
					<b>S.M.A.R.T warning limits:</b>
				</p>
				<table style="table-layout: auto; padding: 0 0 0 0; margin: 0; width: 0;">
					<tr>
						<td style="white-space: nowrap;">Reallocated sector count:</td>
						<td><input type="number" required min="0" max="9999" name="reallocated_sector_w" value="<?php print($reallocated_sector_w); ?>" style="width: 50px;" /></td>
					</tr>
					<tr>
						<td style="white-space: nowrap;">Reported uncorrectable_errors:</td>
						<td><input type="number" required min="0" max="9999" name="reported_uncorr_w" value="<?php print($reported_uncorr_w); ?>" style="width: 50px;" /></td>
					</tr>
					<tr>
						<td style="white-space: nowrap;">Command timeout:</td>
						<td><input type="number" required min="0" max="9999" name="command_timeout_w" value="<?php print($command_timeout_w); ?>" style="width: 50px;" /></td>
					</tr>
					<tr>
						<td style="white-space: nowrap;">Current pending sector count:</td>
						<td><input type="number" required min="0" max="9999" name="pending_sector_w" value="<?php print($pending_sector_w); ?>" style="width: 50px;" /></td>
					</tr>
					<tr>
						<td style="white-space: nowrap;">Offline uncorrectable sectors:</td>
						<td><input type="number" required min="0" max="9999" name="offline_uncorr_w" value="<?php print($offline_uncorr_w); ?>" style="width: 50px;" /></td>
					</tr>
				</table>
				<blockquote class='inline_help'>
					Set the RAW limit when the SMART warning should trigger, this is only valid for SATA drives. 0 disables the warning.
				</blockquote>
			</td>
			<td style="padding-left: 25px; vertical-align: top;">
				<h2 style="padding-bottom: 25px;">Visible Frontpage Information</h2>
				<table style="width: auto;">
					<tr>
						<td style="vertical-align: top; width: <?php echo $vi_width ?>px;">
							<b>LED array:</b><br />
							<input type="checkbox" name="displayinfo[tray]" value="1" <?php if(isset($displayinfo["tray"])) echo "checked"; ?> />Tray number<br />
							<input type="checkbox" name="displayinfo[leddiskop]" value="1" <?php if(isset($displayinfo["leddiskop"])) echo "checked"; ?> />Disk Operation LED<br />
							<input type="checkbox" name="displayinfo[ledsmart]" value="1" <?php if(isset($displayinfo["ledsmart"])) echo "checked"; ?> />SMART Status LED<br />
							<input type="checkbox" name="displayinfo[ledtemp]" value="1" <?php if(isset($displayinfo["ledtemp"])) echo "checked"; ?> />Temperature LED<br />
						</td>
						<td style="vertical-align: top; width: <?php echo $vi_width ?>px;">
							<b>Common logic info:</b><br />
							<input type="checkbox" name="displayinfo[unraidinfo]" value="1" <?php if(isset($displayinfo["unraidinfo"])) echo "checked"; ?> />Unraid info<br />
							<input type="checkbox" name="displayinfo[path]" value="1" <?php if(isset($displayinfo["path"])) echo "checked"; ?> />Path<br />
							<input type="checkbox" name="displayinfo[devicenode]" value="1" <?php if(isset($displayinfo["devicenode"])) echo "checked"; ?> />Device Node<br />
							<input type="checkbox" name="displayinfo[luname]" value="1" <?php if(isset($displayinfo["luname"])) echo "checked"; ?> />Logical Unit Number<br />
						</td>
						<td style="vertical-align: top; width: <?php echo $vi_width ?>px;">
							<b>Drive manufacturer info:</b><br />
							<input type="checkbox" name="displayinfo[manufacturer]" value="1" <?php if(isset($displayinfo["manufacturer"])) echo "checked"; ?> />Manufacturer<br />
							<input type="checkbox" name="displayinfo[devicemodel]" value="1" <?php if(isset($displayinfo["devicemodel"])) echo "checked"; ?> />Device Model<br />
							<input type="checkbox" name="displayinfo[serialnumber]" value="1" <?php if(isset($displayinfo["serialnumber"])) echo "checked"; ?> />Serial Number<br />
							<input type="checkbox" name="displayinfo[capacity]" value="1" <?php if(isset($displayinfo["capacity"])) echo "checked"; ?> />Capacity<br />
							<input type="checkbox" name="displayinfo[rotation]" value="1" <?php if(isset($displayinfo["rotation"])) echo "checked"; ?> />Rotation<br />
							<input type="checkbox" name="displayinfo[formfactor]" value="1" <?php if(isset($displayinfo["formfactor"])) echo "checked"; ?> />Form Factor<br />
						</td>
						<td style="vertical-align: top; width: <?php echo $vi_width ?>px;">
							<b>Common SMART info:</b><br />
							<input type="checkbox" name="displayinfo[temperature]" value="1" <?php if(isset($displayinfo["temperature"])) echo "checked"; ?> />Temperature<br />
							<input type="checkbox" name="displayinfo[powerontime]" value="1" <?php if(isset($displayinfo["powerontime"])) echo "checked"; ?> />Power On Time<br />
							<input type="checkbox" name="displayinfo[units_read]" value="1" <?php if(isset($displayinfo["units_read"])) echo "checked"; ?> />Data Read<br />
							<input type="checkbox" name="displayinfo[units_written]" value="1" <?php if(isset($displayinfo["units_written"])) echo "checked"; ?> />Data Written<br />
						</td>
					</tr>
					<tr>
						<td style="vertical-align: top; width: <?php echo $vi_width ?>px;">
							<b>SATA drives info (HDD/SSD):</b><br />
							<input type="checkbox" name="displayinfo[cache]" value="1" <?php if(isset($displayinfo["cache"])) echo "checked"; ?> />Drive Cache<br />
							<input type="checkbox" name="displayinfo[loadcycle]" value="1" <?php if(isset($displayinfo["loadcycle"])) echo "checked"; ?> />Load Cycles<br />
							<input type="checkbox" name="displayinfo[reallocated_sector_count]" value="1" <?php if(isset($displayinfo["reallocated_sector_count"])) echo "checked"; ?> />Reallocated Sector<br />
							<input type="checkbox" name="displayinfo[reported_uncorrectable_errors]" value="1" <?php if(isset($displayinfo["reported_uncorrectable_errors"])) echo "checked"; ?> />Reported Uncorrectable<br />
							<input type="checkbox" name="displayinfo[command_timeout]" value="1" <?php if(isset($displayinfo["command_timeout"])) echo "checked"; ?> />Command Timeout<br />
							<input type="checkbox" name="displayinfo[current_pending_sector_count]" value="1" <?php if(isset($displayinfo["current_pending_sector_count"])) echo "checked"; ?> />Current Pending Sectors<br />
							<input type="checkbox" name="displayinfo[offline_uncorrectable]" value="1" <?php if(isset($displayinfo["offline_uncorrectable"])) echo "checked"; ?> />Offline Uncorrectable<br />
						</td>
						<td style="vertical-align: top; width: <?php echo $vi_width ?>px;">
							<b>PCIE drives info (NVMe):</b><br />
							<input type="checkbox" name="displayinfo[available_spare]" value="1" <?php if(isset($displayinfo["available_spare"])) echo "checked"; ?> />Spare<br />
							<input type="checkbox" name="displayinfo[percentage_used]" value="1" <?php if(isset($displayinfo["percentage_used"])) echo "checked"; ?> />Percentage Used<br />
						</td>
						<td style="vertical-align: top; width: <?php echo $vi_width ?>px;">
							<b>User inputs:</b><br />
							<input type="checkbox" name="displayinfo[manufactured]" value="1" <?php if(isset($displayinfo["manufactured"])) echo "checked"; ?> />Manufacture Date<br />
							<input type="checkbox" name="displayinfo[purchased]" value="1" <?php if(isset($displayinfo["purchased"])) echo "checked"; ?> />Purchase Date<br />
							<input type="checkbox" name="displayinfo[installed]" value="1" <?php if(isset($displayinfo["installed"])) echo "checked"; ?> />Installation Date<br />
							<input type="checkbox" name="displayinfo[warranty]" value="1" <?php if(isset($displayinfo["warranty"])) echo "checked"; ?> />Warranty Left<br />
							<input type="checkbox" name="displayinfo[comment]" value="1" <?php if(isset($displayinfo["comment"])) echo "checked"; ?> />Comment<br />
						</td>
						<td style="vertical-align: top; width: <?php echo $vi_width ?>px;">
							<b>Other configurations:</b><br />
							<input type="checkbox" name="displayinfo[hideemptycontents]" value="1" <?php if(isset($displayinfo["hideemptycontents"])) echo "checked"; ?> />Hide empty tray contents<br />
							<input type="checkbox" name="displayinfo[flashwarning]" value="1" <?php if(isset($displayinfo["flashwarning"])) echo "checked"; ?> />Flash warning<br />
							<input type="checkbox" name="displayinfo[flashcritical]" value="1" <?php if(isset($displayinfo["flashcritical"])) echo "checked"; ?> />Flash critical<br />
						</td>
					</tr>
					<tr>
						<td colspan="8">
							<blockquote class='inline_help'>
								<p>
									Select the information you want to display on the "Devices" page. Each row is based upon the layout.
									Either SATA or PCIE is shown depending on what the drive is detected as.
								</p>
							</blockquote>
							<blockquote class='inline_help'>
								<p>
									Hide empty tray contents: Nothing but the background color.<br />
									Flash warning: the background will flash when the drive has a warning.<br />
									Flash critical: the background will flash when the drive has a critical issue.
								</p>
							</blockquote>
						</td>
					</tr>
					<tr>
						<td style="vertical-align: top;" colspan="8">
							<b>LED signals:</b><br />
							<input type="radio" name="signal_css" value="signals.dynamic.css" <?php if(strstr(use_stylesheet(), "signals.dynamic.css")) { echo "checked"; } ?> />Dynamic
							<input type="radio" name="signal_css" value="signals.static.css" <?php if(strstr(use_stylesheet(), "signals.static.css")) { echo "checked"; } ?> />Static
							<blockquote class='inline_help'>
								<p>
									Select if you want LEDs to flash or not.
								</p>
							</blockquote>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<hr />
	<table style="width: 100%;">
		<tr>
			<td colspan="3"><h2>Sorting of tables</h2></td>
		</tr>
		<tr>
			<td><b>Table</b></td>
			<td><b>Sort</b></td>
			<td><b>Column</b></td>
		</tr>
		<tr>
			<td>
				Information
			</td>
			<td>
				<input type="text" name="sort_db_info" value="<?php print($sort_db_info); ?>" style="width: 95%;" />
			</td>
			<td style="width: 75%">
				<input type="text" name="select_db_info" value="<?php print($select_db_info); ?>" style="width: 95%;" />
			</td>
		</tr>
		<tr>
			<td colspan="3" style="margin: 0; padding: 0 0 0 0 ;">
				<blockquote class='inline_help'>
					<ul>
						<li><b>Possible selectors:</b> group, tray, device, node, lun, manufacturer, model, status, serial, temp, powerontime, loadcycle, reallocated, reported, timeout, pending, offline, capacity, cache, rotation, formfactor, nvme_spare, nvme_spare_thres, nvme_used, read, written, <!-- bench_r, bench_w, --> manufactured, purchased, installed, warranty, comment</li>
						<li>Sort: The "tray" sorting does sort by "TrayID" and not the physical tray. If the tray count is backwards or or have different starting points, it might affect this and show a weird behaviour. It's not a bug nor a feature, it just is what it is.</li>
					</ul>
				</blockquote>
			</td>
		</tr>
		<tr>
			<td>
				Tray Allocations / Unassigned Devices
			</td>
			<td>
				<input type="text" name="sort_db_trayalloc" value="<?php print($sort_db_trayalloc); ?>" style="width: 95%;" />
			</td>
			<td style="width: 75%">
				<input type="text" name="select_db_trayalloc" value="<?php print($select_db_trayalloc); ?>" style="width: 95%;" />
			</td>
		</tr>
		<tr>
			<td colspan="3" style="margin: 0; padding: 0 0 0 0 ;">
				<blockquote class='inline_help'>
					<ul>
						<li><b>Possible selectors:</b> (group, tray)*, device, node, lun, manufacturer, model, serial, capacity, cache, rotation, formfactor, manufactured, purchased, installed, warranty, comment</li>
						<li><b>Sort:</b> "Unassigned Devices" will only sort by an internal ID, but will follow the set direction, ascending or descending. *) Sorting by group and tray is therefore possible with "Tray Allocations", but not to be included in columns.</li>
					</ul>
				</blockquote>
			</td>
		</tr>
		<tr>
			<td>
				History
			</td>
			<td>
				<input type="text" name="sort_db_drives" value="<?php print($sort_db_drives); ?>" style="width: 95%;" />
			</td>
			<td style="width: 75%">
				<input type="text" name="select_db_drives" value="<?php print($select_db_drives); ?>" style="width: 95%;" />
			</td>
		</tr>
		<tr>
			<td colspan="3" style="margin: 0; padding: 0 0 0 0 ;">
				<blockquote class='inline_help'>
					<ul>
						<li><b>Possible selectors:</b> device, node, lun, manufacturer, model, status, serial, powerontime, loadcycle, reallocated, reported, timeout, pending, offline, capacity, cache, rotation, formfactor, nvme_spare, nvme_spare_thres, nvme_used, read, written, <!-- bench_r, bench_w, --> manufactured, purchased, installed, removed, warranty, comment</li>
					</ul>
				</blockquote>
			</td>
		</tr>
		<tr>
			<td colspan="3">
				<blockquote class='inline_help'>
					<p>
						<b>Sort format: direction:column1[,column2]</b><br />
						<br />
						direction: asc or desc (ascending/descending)<br />
						column?: see the table reference for valid inputs.<br />
						<br />
						Valid sort selectors are the same as the allowed columns. With an exception for "Tray Allocations" which can include "group" and "tray"
					</p>
					<p>
						<b>Column format: column1,column2,column3</b><br />
						<br />
						column?: see the table reference for valid inputs. Columns can be repeated, and any order is possible. Columns containing input data will erase contents from database if disabled (e.g. Tray Allocations). 
						Tray Allocations will always have "group,tray" at the beginning including the "Locate" button. No elements with input forms should be duplicated even if it's possible to do it.
						Only selectors underneath each section is possible to use, others will show the column with "unavailable".
					</p>
					<p>
						<b>Reset</b><br />
						Values can be reset to default by deleting the contents and saving it.
					</p>
					<p style="padding: 0 0 50px 0;">
						NB! "bench_r" and "bench_w" are not supported yet but prepared for future project.
					</p>
					<table style="background: none; border-spacing: 0px;; width: 300px;">
						<tr style="border: 1px solid black;">
							<td style="margin: 0; padding: 0 0 0 0;"><b>Input</b></td>
							<td style="margin: 0; padding: 0 0 0 0;"><b>Table name</b></td>
							<td style="margin: 0; padding: 0 0 0 0;"><b>Full name</b></td>
						</tr>
						<?php print($inlinehelp_table_order); ?>
					</table>
				</blockquote>
			</td>
		</tr>
		<tr>
			<td colspan="3" style="vertical-align: top;">
				<span style="padding: 0 0 0 75px;">
					<input type="submit" name="save_settings" value="Save" />
					<span style="padding-left: 50px;"></span>
					<input type="submit" name="reset_common_colors" value="Reset Common Colors" />
				</span>
				<blockquote class='inline_help'>
					<p>Save the Common Configuration and the Visible Frontpage Information. This does not save the Disk Tray Layout.</p>
				</blockquote>
			</td>
		</tr>
	</table>
</form>
<hr style="border: 1px solid black; height: 0!important;" />
<h2 style="padding-bottom: 20px;">Disk Tray Layout</h2>
<table style="width: 0;">
	<tr>
		<td>
			<table style="width: 0; margin: 0;">
				<tr>
					<?php print($disk_layouts_config); ?>
				</tr>
			</table>
		</td>
		<td style="padding-left: 20px;">
			<table style="width: 0;">
				<tr>
					<td style="vertical-align: middle;">
						<form action="<?php echo DISKLOCATION_PATH ?>/pages/system.php" method="post">
							<input type="hidden" name="last_group_id" value="<?php echo $last_group_id ?>" />
							<button type="submit" name="group_add" title="Add a new group" style="background-size: 0;"><i style="font-size: 600%;" class="fa fa-plus-circle fa-lg"></i></button><br />
							<?php if($total_groups > 1) { print("<button type=\"submit\" name=\"group_del\" title=\"Remove last group\" style=\"background-size: 0;\"><i style=\"font-size: 600%;\" class=\"fa fa-trash fa-lg\"></i></button>"); } ?>
						</form>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php if($db_update == 2) { print("-->"); } ?>
