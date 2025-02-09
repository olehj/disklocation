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
	$vi_width = 180;
	
	if(!empty($disklocation_error) && isset($_POST["save_groupsettings"])) {
		$i=0;
		print("<h2 style=\"margin: 0; color: #FF0000; font-weight: bold;\">ERROR Could not save the configuration (previous form restored):</h2><br /><span style=\"font-size: medium;\">");
		while($i < count($disklocation_error)) {
			print("&middot; " . $disklocation_error[$i] . "<br />");
			$i++;
		}
		print("</span><hr style=\"clear: both; border-bottom: 1px solid #FF0000;\" /><br /><br /><br />");
	}
	
	$last_group_id = 0;
	$disk_layouts_config = "";
	$count_groups = 0;
	
	$array_groups = $get_groups;
	( is_array($array_groups) ?? ksort($array_groups, SORT_NUMERIC) );
	
	$group_ids = ( is_array($get_groups) ? array_keys($get_groups) : null );
	
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
					<input type=\"color\" required name=\"group_color[$gid]\" list=\"disklocationColorsLay\" value=\"#" . (!empty($array_groups[$gid]["group_color"]) ? $array_groups[$gid]["group_color"] : $bgcolor_empty) . "\" />
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
			$bgcolor_group_custom_array .= ( isset($custom_colors_array_dedup[$i]) ? "<option>#" . strtoupper($custom_colors_array_dedup[$i]) . "</option>\n" : null );
		}
	}
?>
<?php if($db_update == 2) { print("<h3>Page unavailable due to database error.</h3><!--"); } ?>
<datalist id="disklocationColorsLay">
	<?php echo $bgcolor_group_custom_array ?>
</datalist>
<table><tr><td style="padding: 10px 10px 10px 10px;">
<h2 style="margin-top: -10px; padding: 0 0 25px 0;">Disk Tray Layout</h2>
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
