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
	$vi_width = 150;
	
	/*
	$dashboard_widget_array = dashboard_toggle("info");
	$dashboard_widget = $dashboard_widget_array["current"];
	$dashboard_widget_pos = $dashboard_widget_array["position"];
	*/
	
	if(!empty($disklocation_error)) {
		$i=0;
		print("<p style=\"color: #FF0000; font-weight: bold;\">");
		while($i < count($disklocation_error)) {
			print("&middot;" . $disklocation_error[$i] . "<br />");
			$i++;
		}
		print("</p><hr />");
	}
	
	$sql = "SELECT * FROM settings_group ORDER BY id ASC";
	$results = $db->query($sql);
	
	$disk_layouts_config = "";
	
	while($data = $results->fetchArray(1)) {
		extract($data);
		$gid = $id;
		
		$css_grid_group = "
			grid-template-columns: " . $grid_columns_styles[$gid] . ";
			grid-template-rows: " . $grid_rows_styles[$gid] . ";
			grid-auto-flow: " . $grid_count . ";
		";
		
		$tray_direction = ( empty($tray_direction) ? 1 : $tray_direction);
		
		$disk_layouts_config .= "
			<td style=\"min-width: 240px; vertical-align: top; border-left: 1px solid black;\">
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
						<!--
						<input type=\"radio\" name=\"tray_start_num\" value=\"0\" " . ( ($tray_start_num == 0) ? "checked" : null ) . " />0
						<input type=\"radio\" name=\"tray_start_num\" value=\"1\" " . ( ($tray_start_num == 1) ? "checked" : null ) . " />1
						-->
					</p>
					<blockquote class=\"inline_help\">
						<p>Start counting tray from the entered number.</p>
					</blockquote>
					<p style=\"text-align: center;\">
						<input type=\"hidden\" name=\"groupid\" value=\"$gid\" />
						<input type=\"submit\" name=\"save_groupsettings\" value=\"Save\" /><!--<input type=\"reset\" value=\"Reset\" />-->
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
	}
	
	$smart_updates = cronjob_current();
	
	//$plugin_update_scan = update_scan_toggle(0, 1);
	
	/*
	if(empty($dashboard_widget) || $dashboard_widget == "on" || $dashboard_widget == "off") {
		$dashboard_widget = 0;
	}
	*/
?>
<datalist id="disklocationColorsDef">
	<option>#<?php echo $bgcolor_parity ?></option>
	<option>#<?php echo $bgcolor_unraid ?></option>
	<option>#<?php echo $bgcolor_cache ?></option>
	<option>#<?php echo $bgcolor_others ?></option>
	<option>#<?php echo $bgcolor_empty ?></option>
	<?php echo ( $bgcolor_parity != "eb4f41" ? "<option>#eb4f41</option>" : null ) ?>
	<?php echo ( $bgcolor_unraid != "ef6441" ? "<option>#ef6441</option>" : null ) ?>
	<?php echo ( $bgcolor_cache != "ff884c" ? "<option>#ff884c</option>" : null ) ?>
	<?php echo ( $bgcolor_others != "41b5ef" ? "<option>#41b5ef</option>" : null ) ?>
	<?php echo ( $bgcolor_empty != "aaaaaa" ? "<option>#aaaaaa</option>" : null ) ?>
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
					<input type="radio" name="dashboard_widget" id="bgcolor_display_0" value="0" <?php if($dashboard_widget == "0") echo "checked"; ?> />Disk Type
					<input type="radio" name="dashboard_widget" id="bgcolor_display_1" value="1" <?php if($dashboard_widget == "1") echo "checked"; ?> />Heat Map
					<!-- reusing the deprecated dashboard variable instead of messing with the database -->
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
						<li>#eb4f41 "Parity"</li>
						<li>#ef6441 "Data"</li>
						<li>#ff884c "Cache"</li>
						<li>#41b5ef "Unassigned devices"</li>
						<li>#aaaaaa "Empty/available trays"</li>
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
					<b>Set warranty date entry:</b><br />
					<input type="radio" name="warranty_field" value="u" <?php if($warranty_field == "u") echo "checked"; ?> />Unraid
					<input type="radio" name="warranty_field" value="m" <?php if($warranty_field == "m") echo "checked"; ?>/>Manual ISO
				</p>
				<!--
				<blockquote class='inline_help'>
					Select how you want to enter the warranty date: the Unraid way of selecting amount of months, or manual ISO date for specific dates. Both values can be stored, but only one can be visible at a time.
				</blockquote>
				<p>
					<b>Dashboard plugin:</b><br />
					<input type="radio" name="dashboard_widget_pos" value="0" <?php if(!$dashboard_widget_pos) echo "checked"; ?> />Off
					<input type="radio" name="dashboard_widget_pos" value="1" <?php if($dashboard_widget_pos == 1) echo "checked"; ?> />On
				</p>
				<blockquote class='inline_help'>
					Choose if you want to display this plugin in the Unraid Dashboard, "Enable" or "Disable"<br />
				</blockquote>
				-->
				<input type="hidden" name="dashboard_widget_pos" value="0" /> <!-- new Dashboard system, just leaving this to disabled by default -->
			</td>
			<td style="padding-left: 25px; vertical-align: top;">
				<h2>Updates</h2>
				<!--
				<p>
					<b>Disk Location plugin on update scan:</b><br />
					<input type="radio" name="plugin_update_scan" value="1" <?php if($plugin_update_scan == 1) echo "checked"; ?> />Enabled
					<input type="radio" name="plugin_update_scan" value="0" <?php if($plugin_update_scan == 0) echo "checked"; ?> />Disabled
				</p>
				<blockquote class='inline_help'>
					Enable or disable the auto scan during a plugin update. If it's disabled it will rely on manual updates (Force Scan All) and S.M.A.R.T update schedules.
				</blockquote>
				-->
				<p>
					<b>S.M.A.R.T updates:</b><br />
					<input type="radio" name="smart_updates" value="hourly" <?php if($smart_updates == "hourly") echo "checked"; ?> />Hourly
					<input type="radio" name="smart_updates" value="daily" <?php if($smart_updates == "daily") echo "checked"; ?> />Daily
					<input type="radio" name="smart_updates" value="weekly" <?php if($smart_updates == "weekly") echo "checked"; ?> />Weekly
					<input type="radio" name="smart_updates" value="monthly" <?php if($smart_updates == "monthly") echo "checked"; ?> />Monthly
					<input type="radio" name="smart_updates" value="disabled" <?php if($smart_updates == "disabled") echo "checked"; ?> />Disabled
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
				<p style="position: relative; bottom: 0;">
					<input type="submit" name="save_settings" value="Save" /><!--<input type="reset" value="Reset" />-->
					<blockquote class='inline_help'>
						<p>Save the Common Configuration and the Visible Frontpage Information. This does not save the Disk Tray Layout.</p>
					</blockquote>
				</p>
			</td>
			<td style="padding-left: 25px; vertical-align: top;">
				<h2 style="padding-bottom: 25px;">Visible Frontpage Information</h2>
				<table style="width: auto;">
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[tray]" value="1" <?php if(isset($displayinfo["tray"])) echo "checked"; ?> />Tray number
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[leddiskop]" value="1" <?php if(isset($displayinfo["leddiskop"])) echo "checked"; ?> />Disk Operation LED
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[ledsmart]" value="1" <?php if(isset($displayinfo["ledsmart"])) echo "checked"; ?> />SMART Status LED
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[ledtemp]" value="1" <?php if(isset($displayinfo["ledtemp"])) echo "checked"; ?> />Temperature LED
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[unraidinfo]" value="1" <?php if(isset($displayinfo["unraidinfo"])) echo "checked"; ?> />Unraid info
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[path]" value="1" <?php if(isset($displayinfo["path"])) echo "checked"; ?> />Path
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[devicenode]" value="1" <?php if(isset($displayinfo["devicenode"])) echo "checked"; ?> />Device Node
						</td>
						<!--
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[luname]" value="1" <?php if(isset($displayinfo["luname"])) echo "checked"; ?> />Logical Unit Name
						</td>
						-->
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[capacity]" value="1" <?php if(isset($displayinfo["capacity"])) echo "checked"; ?> />Capacity
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[formfactor]" value="1" <?php if(isset($displayinfo["formfactor"])) echo "checked"; ?> />Form Factor
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[rotation]" value="1" <?php if(isset($displayinfo["rotation"])) echo "checked"; ?> />Rotation
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[manufacturer]" value="1" <?php if(isset($displayinfo["manufacturer"])) echo "checked"; ?> />Manufacturer
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[devicemodel]" value="1" <?php if(isset($displayinfo["devicemodel"])) echo "checked"; ?> />Device Model
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[serialnumber]" value="1" <?php if(isset($displayinfo["serialnumber"])) echo "checked"; ?> />Serial Number
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[temperature]" value="1" <?php if(isset($displayinfo["temperature"])) echo "checked"; ?> />Temperature
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[powerontime]" value="1" <?php if(isset($displayinfo["powerontime"])) echo "checked"; ?> />Power On Time
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[loadcyclecount]" value="1" <?php if(isset($displayinfo["loadcyclecount"])) echo "checked"; ?> />Load Cycle Count
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[available_spare]" value="1" <?php if(isset($displayinfo["available_spare"])) echo "checked"; ?> />Spare
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[available_spare_threshold]" value="1" <?php if(isset($displayinfo["available_spare_threshold"])) echo "checked"; ?> />Spare Threshold
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[percentage_used]" value="1" <?php if(isset($displayinfo["percentage_used"])) echo "checked"; ?> />Percentage Used
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[data_units_read]" value="1" <?php if(isset($displayinfo["data_units_read"])) echo "checked"; ?> />Data Read
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[data_units_written]" value="1" <?php if(isset($displayinfo["data_units_written"])) echo "checked"; ?> />Data Written
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[warranty]" value="1" <?php if(isset($displayinfo["warranty"])) echo "checked"; ?> />Warranty Left
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[comment]" value="1" <?php if(isset($displayinfo["comment"])) echo "checked"; ?> />Comment
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;" colspan="2">
							<input type="checkbox" name="displayinfo[hideemptycontents]" value="1" <?php if(isset($displayinfo["hideemptycontents"])) echo "checked"; ?> />Hide empty tray contents
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[flashwarning]" value="1" <?php if(isset($displayinfo["flashwarning"])) echo "checked"; ?> />Flash warning
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[flashcritical]" value="1" <?php if(isset($displayinfo["flashcritical"])) echo "checked"; ?> />Flash critical 
						</td>
					</tr>
					<tr>
						<td colspan="7">
							<blockquote class='inline_help'>
								<p>Select the information you want to display on the "Devices" page. Each row is based upon the layout.</p>
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
				</table>
			</td>
		</tr>
		<!--<tr>
			<td colspan="3" style="padding-left: 25px; vertical-align: top;">
				<input type="submit" name="save_settings" value="Save" /><input type="reset" value="Reset" />
				<blockquote class='inline_help'>
					<p>Save the Common Configuration and the Visible Frontpage Information. This does not save the Disk Tray Layout.</p>
				</blockquote>
			</td>
		</tr>
		-->
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
					<td>
						<form action="<?php echo DISKLOCATION_PATH ?>/pages/system.php" method="post">
							<input type="hidden" name="last_group_id" value="<?php echo $last_group_id ?>" />
							<button type="submit" name="group_add" title="Add a new group" style="background-size: 0;"><i style="font-size: 600%;" class="fa fa-plus-circle fa-lg"></i></button><br />
							<?php if($total_groups > 1) { print("<button type=\"submit\" name=\"group_del\" title=\"Remove last group\" style=\"background-size: 0;\"><i style=\"font-size: 600%;\" class=\"fa fa-minus-circle fa-lg\"></i></button>"); } ?>
						</form>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
