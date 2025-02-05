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
	$vi_width = 180;
	
	if(!empty($disklocation_error)) {
		$i=0;
		print("<h2 style=\"color: #FF0000; font-weight: bold;\">");
		while($i < count($disklocation_error)) {
			print("&middot; ERROR: " . $disklocation_error[$i] . "<br />");
			$i++;
		}
		print("</h2><hr style=\"border: 1px solid #FF0000;\" /><br /><br />");
	}
	
	$last_group_id = 0;
	$disk_layouts_config = "";
	$count_groups = 0;
	
	$array_groups = $get_groups;
	( is_array($array_groups) ?? ksort($array_groups, SORT_NUMERIC) );
	$array_devices = $get_devices;
	$array_locations = $get_locations;
	
	$group_ids = ( is_array($get_groups) ? array_keys($get_groups) : null );
	
	$custom_colors_array = array();
	
	array_push($custom_colors_array, strtoupper($bgcolor_empty));
	array_push($custom_colors_array, strtoupper($bgcolor_parity));
	array_push($custom_colors_array, strtoupper($bgcolor_unraid));
	array_push($custom_colors_array, strtoupper($bgcolor_cache));
	array_push($custom_colors_array, strtoupper($bgcolor_others));
	
	array_push($custom_colors_array, ( strtoupper($bgcolor_empty) != strtoupper($bgcolor_empty_default) ? $bgcolor_empty_default : null ) );
	array_push($custom_colors_array, ( strtoupper($bgcolor_parity) != strtoupper($bgcolor_parity_default) ? $bgcolor_parity_default : null ) );
	array_push($custom_colors_array, ( strtoupper($bgcolor_unraid) != strtoupper($bgcolor_unraid_default) ? $bgcolor_unraid_default : null ) );
	array_push($custom_colors_array, ( strtoupper($bgcolor_cache) != strtoupper($bgcolor_cache_default) ? $bgcolor_cache_default : null ) );
	array_push($custom_colors_array, ( strtoupper($bgcolor_others) != strtoupper($bgcolor_others_default) ? $bgcolor_others_default : null ) );
	
	foreach($array_groups as $gid => $value) {
		if($array_groups[$gid]["group_color"]) {
			array_push($custom_colors_array, strtoupper($array_groups[$gid]["group_color"]));
		}
		
		$css_grid_group = "
			grid-template-columns: " . $grid_columns_styles[$gid] . ";
			grid-template-rows: " . $grid_rows_styles[$gid] . ";
			grid-auto-flow: " . $array_groups[$gid]["grid_count"] . ";
		";
		
		$tray_direction = ( empty($array_groups[$gid]["tray_direction"]) ? 1 : $array_groups[$gid]["tray_direction"]);
		
		$disk_layouts_config .= "
			<td style=\"min-width: 240px; vertical-align: top;\">
				<p>
					<b>Name:</b><br />
					<input type=\"text\" name=\"group_name[$gid]\" value=\"" . stripslashes(htmlspecialchars($array_groups[$gid]["group_name"])) . "\" style=\"width: " . $vi_width . "px;\" />
				</p>
				<blockquote class=\"inline_help\" style=\"white-space: wrap;\">
					Enter a name for the group, optional.
				</blockquote>
				<p>
					<b>Default group color:</b><br />
					<input type=\"color\" required name=\"group_color[$gid]\" list=\"disklocationColorsDef\" value=\"#" . (!empty($array_groups[$gid]["group_color"]) ? $array_groups[$gid]["group_color"] : $bgcolor_empty) . "\" />
				</p>
				<blockquote class=\"inline_help\" style=\"white-space: wrap;\">
					Choose a color for the group, select the first color to disable it.
				</blockquote>
				<p>
					<b>Set sizes for trays:</b><br />
					<input type=\"number\" required min=\"100\" max=\"2000\" name=\"tray_width[$gid]\" value=\"" . $array_groups[$gid]["tray_width"] . "\" style=\"width: 50px;\" /> px longest side<br />
					<input type=\"number\" required min=\"30\" max=\"700\" name=\"tray_height[$gid]\" value=\"" . $array_groups[$gid]["tray_height"] . "\" style=\"width: 50px;\" /> px shortest side
				</p>
				<blockquote class=\"inline_help\" style=\"white-space: wrap;\">
					This is the HTML/CSS pixel size for a single harddisk tray, default sizes are: 400px longest side, and 70px shortest side.
				</blockquote>
				<p>
					<b>Set grid size:</b><br />
					<input type=\"number\" required min=\"1\" max=\"255\" name=\"grid_columns[$gid]\" value=\"" . $array_groups[$gid]["grid_columns"] . "\" style=\"width: 50px;\" /> columns<br />
					<input type=\"number\" required min=\"1\" max=\"255\" name=\"grid_rows[$gid]\" value=\"" . $array_groups[$gid]["grid_rows"] . "\" style=\"width: 50px;\" /> rows<br />
					" . ( !empty($array_groups[$gid]["grid_trays"]) ? "<span style=\"color: #FF0000;\"><b>Override is DEPRECATED!</b><br /><input type=\"number\" min=\"" . $array_groups[$gid]["grid_columns"] * $array_groups[$gid]["grid_rows"] . "\" max=\"255\" name=\"grid_trays[$gid]\" value=\"" . ( ( empty($array_groups[$gid]["grid_trays"]) ) ? null : $array_groups[$gid]["grid_trays"] ) . "\" style=\"width: 50px;\" /> total trays, override</span>" : null ) . "
					
				</p>
				<blockquote class=\"inline_help\" style=\"white-space: wrap;\">
					Set columns and rows to simulate the looks of your trays, ex. 4 columns * 6 rows = 24 total trays.<br />
					" . ( !empty($array_groups[$gid]["grid_trays"]) ? "<span style=\"color: #FF0000;\">Override is now deprecated and should not be used, please adjust the configuration. Adjust the grid size or create a new group and move the devices to new allocations, then delete the override number (blank) or the group altogheter after the devices has been moved.</span>" : null ) . "
				</blockquote>
				<p>
					<b>Set physical tray direction:</b><br />
					<input type=\"radio\" name=\"disk_tray_direction[$gid]\" value=\"h\" " . ( ($array_groups[$gid]["disk_tray_direction"] == "h") ? "checked" : null ) . " />horizontal
					<input type=\"radio\" name=\"disk_tray_direction[$gid]\" value=\"v\" " . ( ($array_groups[$gid]["disk_tray_direction"] == "v") ? "checked" : null ) . " />vertical
				</p>
				<blockquote class=\"inline_help\" style=\"white-space: wrap;\">
					This is the direction of the tray itself. Is it laying flat/horizontal, or is it vertical?
				</blockquote>
				<p>
					<b>Tray assigment count properties:</b><br />
					<input type=\"radio\" name=\"grid_count[$gid]\" value=\"column\" " . ( ($array_groups[$gid]["grid_count"] == "column") ? "checked" : null ) . " />count columns
					<input type=\"radio\" name=\"grid_count[$gid]\" value=\"row\" " . ( ($array_groups[$gid]["grid_count"] == "row") ? "checked" : null ) . " />count rows
				</p>
				<blockquote class=\"inline_help\" style=\"white-space: wrap;\">
					Select how to count the tray:<br />
					&middot; column: \"top to bottom\" or \"bottom to top\"<br />
					&middot; row: \"left to right\" or \"right to left\"
				</blockquote>
				<p>
					<b>Tray assigment count direction:</b><br />
					<input type=\"radio\" name=\"tray_direction[$gid]\" value=\"1\" " . ( ($array_groups[$gid]["tray_direction"] == 1) ? "checked" : null ) . " />left/top
					<input type=\"radio\" name=\"tray_direction[$gid]\" value=\"2\" " . ( ($array_groups[$gid]["tray_direction"] == 2) ? "checked" : null ) . " />left/bottom
					<br />
					<input type=\"radio\" name=\"tray_direction[$gid]\" value=\"3\" " . ( ($array_groups[$gid]["tray_direction"] == 3) ? "checked" : null ) . " />right/top
					<input type=\"radio\" name=\"tray_direction[$gid]\" value=\"4\" " . ( ($array_groups[$gid]["tray_direction"] == 4) ? "checked" : null ) . " />right/bottom
				</p>
				<blockquote class=\"inline_help\" style=\"white-space: wrap;\">
					Select the direction you want to count the trays.
				</blockquote>
				<p>
					<b>Tray count start:</b><br />
					<input type=\"number\" required min=\"0\" max=\"9999999\" name=\"tray_start_num[$gid]\" value=\"" . $array_groups[$gid]["tray_start_num"] . "\" style=\"width: 50px;\" />
				</p>
				<blockquote class=\"inline_help\" style=\"white-space: wrap;\">
					<p>Start counting tray from the entered number.</p>
				</blockquote>
				<p>
					<b>Select trays to bypass/hide:</b><br />
				</p>
				<p>
					<input type=\"checkbox\" name=\"count_bypass_tray[$gid]\" value=\"1\" " . (!empty($array_groups[$gid]["count_bypass_tray"]) ? "checked=\"checked\"" : null ) . " />
					Count bypassed tray numbers
				</p>
				<div class=\"grid-container\" style=\"" . $css_grid_group . "\">
					" . $disklocation_layout[$gid] . "
				</div>
				<blockquote class=\"inline_help\" style=\"white-space: wrap;\">
					This shows you an overview of your configured tray layout, you can also bypass/hide selected trays (only empty/unassigned trays can be selected). Choose also if you want to count the bypassed trays or not.
				</blockquote>
			</td>
		";
		
		if($count_groups >= 0) {
			$disk_layouts_config .= "
				<td style=\"max-width: 80px; vertical-align: top; position: relative; top: 20px;\">
					<button type=\"submit\" name=\"group_del\" onclick=\"return confirm('Are you sure you want to delete " . ( !empty($array_groups[$gid]["group_name"]) ? stripslashes(htmlspecialchars($array_groups[$gid]["group_name"])) : $gid ) . "?');\" title=\"Remove " . ( !empty($array_groups[$gid]["group_name"]) ? stripslashes(htmlspecialchars($array_groups[$gid]["group_name"])) : $gid ) . "\" value=\"" . $gid . "\" style=\"background-size: 0;\"><i style=\"font-size: 600%;\" class=\"fa fa-trash fa-lg\"></i></button><br />
					" . ( !empty($group_ids[($count_groups+1)]) ? "<button type=\"submit\" name=\"group_swap\" title=\"Swap groups\" value=\"" . $gid . ":" . $group_ids[($count_groups+1)] . "\" style=\"background-size: 0;\"><i style=\"font-size: 500%;\" class=\"fa fa-exchange fa-lg\"></i></button>" : null ) . "
					" . ( empty($group_ids[($count_groups+1)]) ? "<button type=\"submit\" name=\"group_add\" title=\"Add a new group\" value=\"" . $gid . "\" style=\"background-size: 0;\"><i style=\"font-size: 600%;\" class=\"fa fa-plus-circle fa-lg\"></i></button><br />" : null ) . "
					" . ( (empty($group_ids[($count_groups+1)]) && $total_groups > 0 ) ? "<button type=\"submit\" name=\"save_groupsettings\" title=\"Save all groups\" style=\"background-size: 0;\"><i style=\"font-size: 600%;\" class=\"fa fa-save fa-lg\"></i></button><br />" : null ) . "
				</td>
			";
		}
		
		$last_group_id = $gid;
		$count_groups++;
	}
	
	list($table_order_user, $table_order_system, $table_order_name, $table_order_full) = get_table_order("all", 0);
	array_multisort($table_order_user, $table_order_system, $table_order_name, $table_order_full);
	$arr_length = count($table_order_user);
	for($i=0;$i<$arr_length;$i++) {
		$inlinehelp_table_order .= "<tr style=\"white-space: nowrap; border: 1px solid black;\"><td style=\"white-space: nowrap;margin: 0; padding: 0 5px 0 5px;\">" . $table_order_user[$i] . "</td><td style=\"white-space: nowrap;margin: 0; padding: 0 5px 0 5px;\">" . $table_order_name[$i] . "</td><td style=\"margin: 0; padding: 0 5px 0 5px;\">" . $table_order_full[$i] . "</td></tr>";
	}
	
	$bgcolor_group_custom_array = "";
	if(isset($custom_colors_array)) {
		$custom_colors_array_dedup = array_values(array_unique($custom_colors_array));
		for($i=0; $i < count($custom_colors_array_dedup); ++$i) {
			$bgcolor_group_custom_array .= "<option>#" . strtoupper($custom_colors_array_dedup[$i]) . "</option>\n";
		}
	}
?>
<?php if($db_update == 2) { print("<h3>Page unavailable due to database error.</h3><!--"); } ?>
<datalist id="disklocationColorsDef">
	<?php echo $bgcolor_group_custom_array ?>
</datalist>
<script>
$(document).ready(function(){
	$('input:radio[name="device_bg_color"]').change(function(){
	var n = $(this).val();
	switch(n) {
		case '0':
			$('#disp_parity').html("Parity");
			$('#disp_data').html("Data");
			$('#disp_cache').html("Cache/Pool");
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
<table><tr><td style="padding: 10px 10px 10px 10px;">
<form action="" method="post">
	<table>
		<tr>
			<td style="width: 250px; vertical-align: top;">
				<h2>Common Configuration</h2>
				<p>
					<b>Change background colors:</b>
				</p>
				<p>
					<input type="radio" name="device_bg_color" id="bgcolor_display_0" value="0" <?php if($device_bg_color == "0") echo "checked"; // reusing the deprecated dashboard variable instead of messing with the database ?> />Disk Type
					<input type="radio" name="device_bg_color" id="bgcolor_display_1" value="1" <?php if($device_bg_color == "1") echo "checked"; // reusing the deprecated dashboard variable instead of messing with the database ?> />Heat Map
				</p>
				<blockquote class="inline_help" style="white-space: wrap;">
					Choose "Disk Type" for the traditional color scheme over the array and disk type.<br />
					Choose "Heat Map" for backgrounds that depends on the temperature range set in Unraid, per disk or global.
				</blockquote>
				<div style="padding-top: 20px;">
					<table>
						<tr>
							<td style="padding: 0;">
								<div id="disp_parity"><?php echo (!$device_bg_color ? "Parity" : "Critical") ?></div>
							</td>
							<td style="padding: 0;">
								<div id="disp_data"><?php echo (!$device_bg_color ? "Data" : "Warning") ?></div></div>
							</td>
							<td style="padding: 0;">
								<div id="disp_cache"><?php echo (!$device_bg_color ? "Cache/Pool" : "Normal") ?></div></div>
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
								<div id="disp_unassigned"><?php echo (!$device_bg_color ? "Unassigned devices" : "Temperature N/A") ?></div>
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
				<blockquote class="inline_help" style="white-space: wrap;">
					<p>Select the color(s) you want, defaults are:</p>
					<p>
						<span style="color: #<?php echo $bgcolor_parity_default ?>">&#11200;</span> #<?php echo $bgcolor_parity_default ?> "Parity"<br />
						<span style="color: #<?php echo $bgcolor_unraid_default ?>">&#11200;</span> #<?php echo $bgcolor_unraid_default ?> "Data"<br />
						<span style="color: #<?php echo $bgcolor_cache_default ?>">&#11200;</span> #<?php echo $bgcolor_cache_default ?> "Cache/Pool"<br />
						<span style="color: #<?php echo $bgcolor_others_default ?>">&#11200;</span> #<?php echo $bgcolor_others_default ?> "Unassigned devices"<br />
						<span style="color: #<?php echo $bgcolor_empty_default ?>">&#11200;</span> #<?php echo $bgcolor_empty_default ?> "Empty/Available trays"<br />
					</p>
				</blockquote>
				<p>
					<b>Set the size divider for mini layout:</b><br />
					<input type="number" required min="1" max="1000" step="0.1" name="tray_reduction_factor" value="<?php print($tray_reduction_factor); ?>" style="width: 50px;" />
				</p>
				<blockquote class="inline_help" style="white-space: wrap;">
					This number will divide from the set height and width sizes defined per group, and display its divided size as a mini layout/dashboard device. Default: 10 [1.0-1000.0 stepping 0.1]. Larger number is smaller in size.
				</blockquote>
				<p>
					<b>LED display:</b><br />
					<input type="radio" name="force_orb_led" value="0" <?php if($force_orb_led == 0) echo "checked"; ?> />Unraid icons
					<input type="radio" name="force_orb_led" value="1" <?php if($force_orb_led == 1) echo "checked"; ?>/>Circular LEDs
				</p>
				<blockquote class="inline_help" style="white-space: wrap;">
					Show how to display the LEDs on the overview and dashboard. Unraid icons will show triangluar warning signs, crossed critival signs etc. Circular LEDs will all be color coded circular lights.
				</blockquote>
				<p>
					<b>Trim serial numbers:</b><br />
					<input type="number" required min="-99" max="99" step="1" name="serial_trim" value="<?php print($serial_trim); ?>" style="width: 50px;" />
				</p>
				<blockquote class="inline_help" style="white-space: wrap;">
					Serial number will be cut either the first or last part of this value, 0 does nothing. Negative number will display X last characters, positive the X first characters.
					The sort function will still sort after the actual serial number, and not the shortened ones.
					<br />
				</blockquote>
				<p>
					<b>Auto backup every:</b><br />
					<input type="number" required min="0" max="999999999" step="1" name="auto_backup_days" value="<?php print($auto_backup_days); ?>" style="width: 50px;" /> days
				</p>
				<blockquote class="inline_help" style="white-space: wrap;">
					Run auto backup every set full days, 0 to disable. This will only backup Disk Location files, and not Unraid config edited via this plugin, if enabled.
					<br />
				</blockquote>
				<p style="color: red;">
					<b>Allow editing of Unraid config:</b><br />
					<input type="radio" name="allow_unraid_edit" value="0" <?php if($allow_unraid_edit == 0) echo "checked"; ?> />No
					<input type="radio" name="allow_unraid_edit" value="1" <?php if($allow_unraid_edit == 1) echo "checked"; ?>/>Yes
				</p>
				<blockquote class="inline_help" style="white-space: wrap;">
					This will allow or disallow editing of Unraid config files via this plugin. E.g. acknowledgement of all drives at once, or editing warranty dates etc.
					When setting this to "NO", the options are neither visible nor editable.
					<br />
				</blockquote>
			</td>
			<td style="padding-left: 25px; vertical-align: top;">
				<h2 style="padding-bottom: 25px;">Visible Frontpage Information</h2>
				<table style="width: auto;">
					<tr>
						<td style="vertical-align: top; width: <?php echo $vi_width ?>px;">
							<b>LED array:</b><br />
							<input type="checkbox" name="displayinfo[tray]" value="1" <?php if(!empty($displayinfo["tray"])) echo "checked"; ?> />Tray number<br />
							<input type="checkbox" name="displayinfo[leddiskop]" value="1" <?php if(!empty($displayinfo["leddiskop"])) echo "checked"; ?> />Disk Operation LED<br />
							<input type="checkbox" name="displayinfo[ledsmart]" value="1" <?php if(!empty($displayinfo["ledsmart"])) echo "checked"; ?> />SMART Status LED<br />
							<input type="checkbox" name="displayinfo[ledtemp]" value="1" <?php if(!empty($displayinfo["ledtemp"])) echo "checked"; ?> />Temperature LED<br />
						</td>
						<td style="vertical-align: top; width: <?php echo $vi_width ?>px;">
							<b>Other configurations:</b><br />
							<input type="checkbox" name="displayinfo[hideemptycontents]" value="1" <?php if(!empty($displayinfo["hideemptycontents"])) echo "checked"; ?> />Hide empty tray contents<br />
							<input type="checkbox" name="displayinfo[flashwarning]" value="1" <?php if(!empty($displayinfo["flashwarning"])) echo "checked"; ?> />Flash warning<br />
							<input type="checkbox" name="displayinfo[flashcritical]" value="1" <?php if(!empty($displayinfo["flashcritical"])) echo "checked"; ?> />Flash critical<br />
						</td>
						<td style="vertical-align: top; width: 60%;" rowspan="2">
							<b>Devices formatting:</b><br >
							<textarea type="text" name="select_db_devices" style="height: 80px; width: 95%;" /><?php print(!$select_db_devices ? $select_db_devices_default : $select_db_devices) ?></textarea>
							<blockquote class="inline_help" style="white-space: wrap;">
								<ul>
									<li><b>Possible selectors:</b> pool, name, device, node, lun, manufacturer, model, serial, temp, capacity, cache, rotation, formfactor, comment</li>
									<li><b>Formatting tools:</b></li>
									<dd>
										Bold: [b]<b>text</b>[/b] or *<b>text</b>*<br />
										Italic: [i]<i>text</i>[/i] or _<i>text</i>_<br />
										
										Font sizes:
										<span style="font-size: xx-small">tiny</span>
										<span style="font-size: x-small">small</span>
										<span style="font-size: medium">medium</span>
										<span style="font-size: large">large</span>
										<span style="font-size: x-large">huge</span>
										<span style="font-size: xx-large">massive</span>
										<br />
										E.g. [large]text[/large]<br />
										<br />
										Color: [color:HEX]text[/color]<br />
										E.g. [color:0000FF][b]text[/b][/color] = <span style="color: #0000FF;"><b>text</b></span>
									</dd>
								</ul>
							</blockquote>
						</td>
					</tr>
					<tr>
						<td style="vertical-align: top;" colspan="3">
							<b>LED signals:</b><br />
							<input type="radio" name="signal_css" value="signals.dynamic.css" <?php if(!$signal_css || $signal_css == "signals.dynamic.css") { echo "checked"; } ?> />Dynamic
							<input type="radio" name="signal_css" value="signals.static.css" <?php if($signal_css == "signals.static.css") { echo "checked"; } ?> />Static
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<blockquote class="inline_help" style="white-space: wrap;">
								<p>
									Hide empty tray contents: Nothing but the background color.<br />
									Flash warning: the background will flash when the drive has a warning.<br />
									Flash critical: the background will flash when the drive has a critical issue.
								</p>
								<p>
									Select if you want LEDs to flash or not. This will not affect flashing backgrounds.
								</p>
							</blockquote>
						</td>
					</tr>
					
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
							<blockquote class="inline_help" style="white-space: wrap;">
								<ul>
									<li><b>Possible selectors:</b> <?php print(implode(", ", get_table_order("allowed", 0, 4, $allowed_db_select_info))); ?></li>
									<li><b>Sort:</b> [asc|desc]:<?php print(implode(", ", get_table_order("allowed", 0, 4, $allowed_db_sort_info))); ?>
								</ul>
							</blockquote>
						</td>
					</tr>
					<tr>
						<td>
							S.M.A.R.T
						</td>
						<td>
							<input type="text" name="sort_db_smart" value="<?php print($sort_db_smart); ?>" style="width: 95%;" />
						</td>
						<td style="width: 75%">
							<input type="text" name="select_db_smart" value="<?php print($select_db_smart); ?>" style="width: 95%;" />
						</td>
					</tr>
					<tr>
						<td colspan="3" style="margin: 0; padding: 0 0 0 0 ;">
							<blockquote class="inline_help" style="white-space: wrap;">
								<ul>
									<li><b>Possible selectors:</b> <?php print(implode(", ", get_table_order("allowed", 0, 4, $allowed_db_select_smart))); ?></li>
									<li><b>Sort:</b> [asc|desc]:<?php print(implode(", ", get_table_order("allowed", 0, 4, $allowed_db_sort_smart))); ?>
								</ul>
							</blockquote>
						</td>
					</tr>
					<tr>
						<td>
							Tray Allocations &amp;<br/>
							Unassigned Devices
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
							<blockquote class="inline_help" style="white-space: wrap;">
								<ul>
									<li><b>Possible selectors:</b> <?php print(implode(", ", get_table_order("allowed", 0, 4, $allowed_db_select_trayalloc))); ?></li>
									<li><b>Sort:</b> [asc|desc]:<?php print(implode(", ", get_table_order("allowed", 0, 4, $allowed_db_sort_trayalloc))); ?></li></li>
									<li>"Unassigned Devices" will only sort by an internal ID, but will follow the set direction.</li>
									<li>Sorting by group and tray is possible with "Tray Allocations", but not to be included in columns as it's enabled by default and is required.</li>
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
							<blockquote class="inline_help" style="white-space: wrap;">
								<ul>
									<li><b>Possible selectors:</b> <?php print(implode(", ", get_table_order("allowed", 0, 4, $allowed_db_select_drives))); ?></li>
									<li><b>Sort:</b> [asc|desc]:<?php print(implode(", ", get_table_order("allowed", 0, 4, $allowed_db_sort_drives))); ?>
								</ul>
							</blockquote>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<blockquote class="inline_help" style="white-space: wrap;">
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
									column?: see the table reference for valid inputs. Columns can be repeated, and any order is possible. 
									Tray Allocations will always have "group,tray" at the beginning including the "Locate" button. No elements with input forms should be duplicated even if it's possible to do it.
									Only selectors underneath each section is possible to use, others will show the column with "unavailable".
								</p>
								<p>
									<b>Reset</b><br />
									Values can be reset to default by deleting the contents and saving it.
								</p>
								<p style="padding: 0 0 50px 0;">
									
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
				</table>
			</td>
		</tr>
	</table>
	<hr />
	<table style="width: 100%;">
		<tr>
			<td colspan="3" style="vertical-align: top;">
				<span style="padding: 0 0 0 75px;">
					<input type="submit" name="save_settings" value="Save" />
					<span style="padding-left: 50px;"></span>
					<input type="submit" name="reset_common_colors" value="Reset Common Colors" />
				</span>
				<blockquote class="inline_help" style="white-space: wrap;">
					<p>Save the Common Configuration and the Visible Frontpage Information. This does not save the Disk Tray Layout.</p>
				</blockquote>
			</td>
		</tr>
	</table>
</form>
<hr style="border: 1px solid black; height: 0!important;" />
<h2 style="padding-bottom: 20px;">Disk Tray Layout</h2>
<form action="" method="post">
	<table style="width: 0;">
		<tr>
			<td>
				<table style="width: 0; margin: 0;">
					<tr>
						<?php print($disk_layouts_config); ?>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
</td></tr></table>
<?php
	if($db_update == 2) { print("-->"); }
?>
