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
	
	if(!empty($disklocation_error) && isset($_POST["save_settings"])) {
		$i=0;
		print("<h2 style=\"margin: 0; color: #FF0000; font-weight: bold;\">ERROR Could not save the configuration (previous form restored):</h2><br /><span style=\"font-size: medium;\">");
		while($i < count($disklocation_error)) {
			print("&middot; " . $disklocation_error[$i] . "<br />");
			$i++;
		}
		print("</span><hr style=\"clear: both; border-bottom: 1px solid #FF0000;\" /><br /><br /><br />");
	}
	
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
			$bgcolor_group_custom_array .= ( isset($custom_colors_array_dedup[$i]) ? "<option>#" . strtoupper($custom_colors_array_dedup[$i]) . "</option>\n" : null );
		}
	}
	
	// get sort config directly from the file to use for forms:
	$refresh_disklocation_file_config = file_exists(DISKLOCATION_CONF) ? json_decode(file_get_contents(DISKLOCATION_CONF), true) : null;
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
<table><tr><td style="padding: 0 10px 0 10px;">
<form action="" method="post" style="padding: 0;">
	<table>
		<tr>
			<td style="width: 250px; vertical-align: top;">
				<h2 style="margin: 0; padding-bottom: 25px;">Common Configuration</h2>
				<table style="width: auto;">
					<tr>
						<td style="vertical-align: top;">
							<b>Change background colors:</b>
						</td>
					</tr>
				</table>
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
					<b>Set Dashboard float position:</b><br />
					<input type="radio" name="dashboard_float" value="left" <?php if($dashboard_float == 'left') echo "checked"; ?> />Left
					<input type="radio" name="dashboard_float" value="right" <?php if($dashboard_float == 'right') echo "checked"; ?>/>Right
					<input type="radio" name="dashboard_float" value="none" <?php if($dashboard_float == 'none') echo "checked"; ?>/>Stack
				</p>
				<blockquote class="inline_help" style="white-space: wrap;">
					Sets the overall placement of the Dashboard, float Tray Layouts to the left, right or stack them.
					<br />
				</blockquote>
				<p>
					<b>Auto backup every:</b><br />
					<input type="number" required min="0" max="999999999" step="1" name="auto_backup_days" value="<?php print($auto_backup_days); ?>" style="width: 50px;" /> days
				</p>
				<blockquote class="inline_help" style="white-space: wrap;">
					Run auto backup every set full days, 0 to disable. Disable this if you plan to schedule auto backup on your own. See help under &quot;System&quot; tab.<br />
					This will only backup Disk Location files, and not Unraid config edited via this plugin, if enabled.
					<br />
				</blockquote>
				<p style="color: red;">
					<b>Allow editing of Unraid config:</b><br />
					<input type="radio" name="allow_unraid_edit" value="0" <?php if($allow_unraid_edit == 0) echo "checked"; ?> />No
					<input type="radio" name="allow_unraid_edit" value="1" <?php if($allow_unraid_edit == 1) echo "checked"; ?>/>Yes
				</p>
				<blockquote class="inline_help" style="white-space: wrap;">
					This will allow or disallow editing of Unraid config files via this plugin. E.g. acknowledgement of all drives at once, or editing warranty dates etc.
					When setting this to "NO", the options are not editable via the plugin.
					<br />
				</blockquote>
			</td>
			<td style="padding-left: 25px; vertical-align: top;">
				<h2 style="margin: 0; padding-bottom: 25px;">Visible Frontpage Information</h2>
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
							<b>Dashboard formatting:</b><br >
							<textarea type="text" name="select_db_devices" style="height: 80px; width: 95%;" /><?php print(!$select_db_devices ? $select_db_devices_default : $select_db_devices) ?></textarea>
							<blockquote class="inline_help" style="white-space: wrap;">
								<ul>
									<li><b>Possible selectors:</b> <?php print(implode(", ", get_table_order("allowed", 0, 4, $allowed_db_select_devices))); ?></li>
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
										E.g. [large]text[/large]
										<br />
										Color: [color:HEX]text[/color]<br />
										E.g. [color:0000FF][b]text[/b][/color] = <span style="color: #0000FF;"><b>text</b></span>
									</dd>
								</ul>
							</blockquote>
						</td>
					</tr>
					<tr>
						<td style="vertical-align: top;" colspan="2">
							<b>LED signals:</b><br />
							<input type="radio" name="signal_css" value="signals.dynamic.css" <?php if(!$signal_css || $signal_css == "signals.dynamic.css") { echo "checked"; } ?> />Dynamic
							<input type="radio" name="signal_css" value="signals.static.css" <?php if($signal_css == "signals.static.css") { echo "checked"; } ?> />Static
							<blockquote class="inline_help" style="white-space: wrap;">
								<p>
									<b>LED array</b><br />
									"Tray number": Show tray number.<br />
									"Disk Operation LED": This will show if the disk is active or in standby.<br />
									"SMART Status LED": Will show if there is a SMART failure, warning or if it is OK.<br />
									"Temperature LED": Display a LED for temperature warning.
								</p>
								<p>
									<b>Other configuration</b><br />
									"Hide empty tray contents": Nothing but the background color.<br />
									"Flash warning": the background will flash when the drive has a warning.<br />
									"Flash critical": the background will flash when the drive has a critical issue.
								</p>
								<p>
									<b>LED signals</b><br />
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
							Information &amp;<br />
							Benchmark (sort only)
							<?php print( !empty($check_sort_info) ? "<br /><span style=\"padding: 0 0 0 0;\" class=\"red\">Sort error,<br /> wrong tags:<br />" . implode(", ", $check_sort_info) . "</span>" : null ); ?>
							<?php print( !empty($check_select_info) ? "<br /><span style=\"padding: 0 0 0 0;\" class=\"red\">Column error,<br /> wrong tags:<br />" . implode(", ", $check_select_info) . "</span>" : null ); ?> 
						</td>
						<td>
							<input type="text" name="sort_db_info" value="<?php print( !empty($refresh_disklocation_file_config["sort_db_info"]) ? $refresh_disklocation_file_config["sort_db_info"] : $sort_db_info ); ?>" style="<?php print( !empty($check_sort_info) ? "border-bottom: 1px solid red;" : null ); ?> width: 95%;" />
						</td>
						<td style="width: 75%">
							<input type="text" name="select_db_info" value="<?php print( !empty($refresh_disklocation_file_config["select_db_info"]) ? $refresh_disklocation_file_config["select_db_info"] : $select_db_info ); ?>" style="<?php print( !empty($check_select_info) ? "border-bottom: 1px solid red;" : null ); ?> width: 95%;" />
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
							SMART
							<?php print( !empty($check_sort_smart) ? "<br /><span style=\"padding: 0 0 0 0;\" class=\"red\">Sort error,<br /> wrong tags: <br />" . implode(", ", $check_sort_smart) . "</span>" : null ); ?>
							<?php print( !empty($check_select_smart) ? "<br /><span style=\"padding: 0 0 0 0;\" class=\"red\">Column error,<br /> wrong tags: <br />" . implode(", ", $check_select_smart) . "</span>" : null ); ?> 
						</td>
						<td>
							<input type="text" name="sort_db_smart" value="<?php print( !empty($refresh_disklocation_file_config["sort_db_smart"]) ? $refresh_disklocation_file_config["sort_db_smart"] : $sort_db_smart ); ?>" style="<?php print( !empty($check_sort_smart) ? "border-bottom: 1px solid red;" : null ); ?> width: 95%;" />
						</td>
						<td style="width: 75%">
							<input type="text" name="select_db_smart" value="<?php print( !empty($refresh_disklocation_file_config["select_db_smart"]) ? $refresh_disklocation_file_config["select_db_smart"] : $select_db_smart ); ?>" style="<?php print( !empty($check_select_smart) ? "border-bottom: 1px solid red;" : null ); ?> width: 95%;" />
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
							<?php print( !empty($check_sort_trayalloc) ? "<br /><span style=\"padding: 0 0 0 0;\" class=\"red\">Sort error,<br /> wrong tags: <br />" . implode(", ", $check_sort_trayalloc) . "</span>" : null ); ?>
							<?php print( !empty($check_select_trayalloc) ? "<br /><span style=\"padding: 0 0 0 0;\" class=\"red\">Column error,<br /> wrong tags: <br />" . implode(", ", $check_select_trayalloc) . "</span>" : null ); ?> 
						</td>
						<td>
							<input type="text" name="sort_db_trayalloc" value="<?php print( !empty($refresh_disklocation_file_config["sort_db_trayalloc"]) ? $refresh_disklocation_file_config["sort_db_trayalloc"] : $sort_db_trayalloc ); ?>" style="<?php print( !empty($check_sort_trayalloc) ? "border-bottom: 1px solid red;" : null ); ?> width: 95%;" />
						</td>
						<td style="width: 75%">
							<input type="text" name="select_db_trayalloc" value="<?php print( !empty($refresh_disklocation_file_config["select_db_trayalloc"]) ? $refresh_disklocation_file_config["select_db_trayalloc"] : $select_db_trayalloc ); ?>" style="<?php print( !empty($check_select_trayalloc) ? "border-bottom: 1px solid red;" : null ); ?> width: 95%;" />
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
							<?php print( !empty($check_sort_drives) ? "<br /><span style=\"padding: 0 0 0 0;\" class=\"red\">Sort error,<br /> wrong tags: <br />" . implode(", ", $check_sort_drives) . "</span>" : null ); ?>
							<?php print( !empty($check_select_drives) ? "<br /><span style=\"padding: 0 0 0 0;\" class=\"red\">Column error,<br /> wrong tags: <br />" . implode(", ", $check_select_drives) . "</span>" : null ); ?> 
						</td>
						<td>
							<input type="text" name="sort_db_drives" value="<?php print( !empty($refresh_disklocation_file_config["sort_db_drives"]) ? $refresh_disklocation_file_config["sort_db_drives"] : $sort_db_drives ); ?>" style="<?php print( !empty($check_sort_drives) ? "border-bottom: 1px solid red;" : null ); ?> width: 95%;" />
						</td>
						<td style="width: 75%">
							<input type="text" name="select_db_drives" value="<?php print( !empty($refresh_disklocation_file_config["select_db_drives"]) ? $refresh_disklocation_file_config["select_db_drives"] : $select_db_drives ); ?>" style="<?php print( !empty($check_select_drives) ? "border-bottom: 1px solid red;" : null ); ?> width: 95%;" />
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
					<p>Save the Common Configuration and the Visible Frontpage Information. "Reset Common Colors" will set the default Disk Location colors.</p>
				</blockquote>
			</td>
		</tr>
	</table>
</form>
</td></tr></table>
<?php
	if($db_update == 2) { print("-->"); }
?>
