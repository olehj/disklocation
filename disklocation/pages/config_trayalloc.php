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
	unset($print_drives);
	
	$sql = "SELECT * FROM settings_group ORDER BY id ASC";
	$results = $db->query($sql);
	
	$a=1;
	while($datagroup = $results->fetchArray(1)) {
		$group[$a]["id"] = $datagroup["id"];
		$group[$a]["group_name"] = $datagroup["group_name"];
		
		$a++;
	}
	
	if(!$total_groups) {
		$sql = "SELECT * FROM disks WHERE status IS NULL;";
	}
	else {
		$sql = "SELECT * FROM disks JOIN location ON disks.hash=location.hash WHERE status IS NULL ORDER BY groupid,tray ASC;";
	}
	
	$i=1;
	$i_empty=1;
	$i_drive=1;
	
	$print_drives = array();
	$datasql = array();
	$custom_colors_array = array();
	
	$results = $db->query($sql);	
	//while($i < $total_disks) {
	while($res = $results->fetchArray(1)) {
		array_push($datasql, $res);
		$warr_options = "";
		
		$data = $datasql[$i_drive-1];
		
		if($data["color"]) {
			array_push($custom_colors_array, $data["color"]);
		}
		
		$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
		
		$tray_options = "";
		$group_options = "";
		for($tray_i = 1; $tray_i <= $biggest_tray_group; ++$tray_i) {
			if($tray_assign == $tray_i) { $selected="selected"; } else { $selected=""; }
			$tray_options .= "<option value=\"$tray_i\" " . $selected . " style=\"text-align: right;\">$tray_i</option>";
		}
		
		for($group_i = 1; $group_i <= $total_groups; ++$group_i) {
			$gid = $group[$group_i]["id"];
			$gid_name = ( empty($group[$group_i]["group_name"]) ? $gid : $group[$group_i]["group_name"] );
			if($data["groupid"] == $gid) { $selected="selected"; } else { $selected=""; }
			$group_options .= "<option value=\"$gid\" " . $selected . " style=\"text-align: right;\">" . stripslashes(htmlspecialchars($gid_name)) . "</option>";
		}
		
		$warr_input = "";
		
		if($warranty_field == "u") {
			for($warr_i = 6; $warr_i <= (6*10); $warr_i+=6) {
				if($data["warranty"] == $warr_i) { $selected="selected"; } else { $selected=""; }
				$warr_options .= "<option value=\"$warr_i\" " . $selected . " style=\"text-align: right;\">$warr_i months</option>";
			}
			$warr_input = "<select name=\"warranty[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 80px; width: 80px;\"><option value=\"\" style=\"text-align: right;\">unknown</option>" . $warr_options . "</select>";
		}
		else {
			$warr_input = "<input type=\"date\" name=\"warranty_date[" . $data["hash"] . "]\" max=\"9999-12-31\" value=\"" . $data["warranty_date"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" />";
		}
		
		$smart_rotation = get_smart_rotation($data["smart_rotation"]);
		
		$bgcolor = ( empty($data["color"]) ? $color_array[$data["hash"]] : $data["color"] );
		
		$print_drives[$i_drive] = "
			<tr style=\"background: #" . ($color_array[$data["hash"]] ?? null) . ";\">
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><select name=\"groups[" . $data["hash"] . "]\" dir=\"rtl\" style=\"min-width: 0; max-width: 150px; min-width: 40px;\"><option value=\"\" selected style=\"text-align: right;\">--</option>" . $group_options . "</select></td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><select name=\"drives[" . $data["hash"] . "]\" dir=\"rtl\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><option value=\"\" selected style=\"text-align: right;\">--</option>" . $tray_options . "</select></td>
				<td style=\"padding: 0 10px 0 10px; text-align: center;\"><input type=\"button\" class=\"diskLocation\" style=\"transform: none;\" onclick=\"locateStart()\" value=\"Locate\" id=\"" . $data["device"] . "\" name=\"" . $data["device"] . "\" /></td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["device"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["luname"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["model_family"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["model_name"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["smart_serialnumber"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . human_filesize($data["smart_capacity"], 1, true) . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $smart_rotation . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $data["smart_formfactor"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"purchased[" . $data["hash"] . "]\" max=\"9999-12-31\" value=\"" . $data["purchased"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $warr_input . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><input type=\"text\" name=\"comment[" . $data["hash"] . "]\" value=\"" . stripslashes(htmlspecialchars($data["comment"])) . "\" style=\"width: 150px;\" /></td>
				<td style=\"padding: 0 10px 0 10px;\">
					<input type=\"color\" name=\"bgcolor_custom[" . $data["hash"] . "]\" list=\"disklocationColors\" value=\"#" . $bgcolor . "\" " . ($dashboard_widget ? "disabled=\"disabled\"" : null ) . " />
					" . ($dashboard_widget ? "<input type=\"hidden\" name=\"bgcolor_custom[" . $data["hash"] . "]\" value=\"#" . $bgcolor . "\" />" : null ) . "
				</td>
			</tr>
		";
		$i_drive++;
		$i++;
	}
	
	// get unassigned disks info
	$data = "";
	$sql = "SELECT * FROM disks WHERE status = 'h' ORDER BY ID ASC;";
	$results = $db->query($sql);
	$print_add_drives = "";
	$warr_options = "";
	
	while($data = $results->fetchArray(1)) {
		$tray_options = "";
		$group_options = "";
		
		for($tray_i = 1; $tray_i <= $biggest_tray_group; ++$tray_i) {
			$tray_options .= "<option value=\"$tray_i\" style=\"text-align: right;\">$tray_i</option>";
		}
		
		for($group_i = 1; $group_i <= $total_groups; ++$group_i) {
			$gid = $group[$group_i]["id"];
			$gid_name = ( empty($group[$group_i]["group_name"]) ? $gid : $group[$group_i]["group_name"] );
			$group_options .= "<option value=\"$gid\" style=\"text-align: right;\">" . stripslashes(htmlspecialchars($gid_name)) . "</option>";
			
		}
		
		$warr_input = "";
		if($warranty_field == "u") {
			$warr_input = "<select name=\"warranty[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 80px; width: 80px;\"><option value=\"\" style=\"text-align: right;\">unknown</option>" . $warr_options . "</select>";
		}
		else {
			$warr_input = "<input type=\"date\" name=\"warranty_date[" . $data["hash"] . "]\" value=\"" . $data["warranty_date"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" />";
		}
		
		$smart_rotation = get_smart_rotation($data["smart_rotation"]);
		
		$bgcolor = ( empty($data["color"]) ? $bgcolor_empty : $data["color"] );
		
		$print_add_drives .= "
			<tr style=\"background: #" . ($color_array[$data["hash"]] ?? null) . ";\">
				<td style=\"padding: 0 10px 0 10px;  text-align: right;\"><select name=\"groups[" . $data["hash"] . "]\" dir=\"rtl\" style=\"min-width: 0; max-width: 100px; min-width: 40px;\"><option value=\"\" selected style=\"text-align: right;\">--</option>" . $group_options . "</select></td>
				<td style=\"padding: 0 10px 0 10px;  text-align: right;\"><select name=\"drives[" . $data["hash"] . "]\" dir=\"rtl\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><option value=\"\" selected style=\"text-align: right;\">--</option>" . $tray_options . "</select></td>
				<td style=\"padding: 0 10px 0 10px; text-align: center;\"><input type=\"button\" class=\"diskLocation\" style=\"transform: none;\" onclick=\"locateStart()\" value=\"Locate\" id=\"" . $data["device"] . "\" name=\"" . $data["device"] . "\" /></td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["device"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["luname"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["model_family"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["model_name"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["smart_serialnumber"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . human_filesize($data["smart_capacity"], 1, true) . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $smart_rotation . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $data["smart_formfactor"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"purchased[" . $data["hash"] . "]\" value=\"" . $data["purchased"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $warr_input . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><input type=\"text\" name=\"comment[" . $data["hash"] . "]\" value=\"" . stripslashes(htmlspecialchars($data["comment"])) . "\" style=\"width: 150px;\" /></td>
				<td style=\"padding: 0 10px 0 10px;\">
					<input type=\"color\" name=\"bgcolor_custom[" . $data["hash"] . "]\" list=\"disklocationColors\" value=\"#" . $bgcolor . "\" " . ($dashboard_widget ? "disabled=\"disabled\"" : null ) . " />
					" . ($dashboard_widget ? "<input type=\"hidden\" name=\"bgcolor_custom[" . $data["hash"] . "]\" value=\"#" . $bgcolor . "\" />" : null ) . "
				</td>
			</tr>
		";
	}
	
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
	
	$disk_layouts_alloc = "";
	
	while($group = $results->fetchArray(1)) {
		extract($group);
		
		$gid = $id;
		$groupid = $gid;
		
		$css_grid_group = "
			grid-template-columns: " . $grid_columns_styles[$gid] . ";
			grid-template-rows: " . $grid_rows_styles[$gid] . ";
			grid-auto-flow: " . $grid_count . ";
		";
		
		$disk_layouts_alloc .= "
			<div style=\"float: left; padding: 10px 20px 10px 20px;\">
				<h2 style=\"text-align: center;\">
					" . ( empty($group_name) ? $gid : $group_name ) . "
				</h2>
				<blockquote class='inline_help'>
					This is the group name (if any)
				</blockquote>
				<div class=\"grid-container\" style=\"" . $css_grid_group . "\">
					" . $disklocation_alloc[$gid] . "
				</div>
				<blockquote class='inline_help'>
					This shows you an overview of your configured tray layout
				</blockquote>
			</div>
		";
		$a++;
	}
	
	$bgcolor_custom_array = "";
	if(isset($custom_colors_array)) {
		$custom_colors_array_dedup = array_values(array_unique($custom_colors_array));
		for($i=0; $i < count($custom_colors_array_dedup); ++$i) {
			$bgcolor_custom_array .= "<option>#" . $custom_colors_array_dedup[$i] . "</option>\n";
		}
	}
?>
<datalist id="disklocationColors">
	<option>#<?php echo $bgcolor_empty ?></option>
	<!--
	<option>#<?php echo $bgcolor_parity ?></option>
	<option>#<?php echo $bgcolor_unraid ?></option>
	<option>#<?php echo $bgcolor_cache ?></option>
	<option>#<?php echo $bgcolor_others ?></option>
	-->
	<?php echo $bgcolor_custom_array ?>
</datalist>
<form action="" method="post">
	<?php print($disk_layouts_alloc); ?>
	<div style="clear: both;"></div>
	<!--<blockquote class='inline_help'>-->
		<p style="color: red;"><b>OBS! When allocating drives you must use the TrayID numbers shown in bold and not the physical tray assignment shown on the right/bottom (these are only shown if the numbers differ).</b>
		<?php
			if($dashboard_widget) { 
				print("<br />Custom Color is disabled when \"Heat Map\" is used.");
			}
		?>
	<!--</blockquote>-->
		</p><div><br /></div>
	<table style="width: 800px; border-spacing: 3px; border-collapse: separate;">
		<tr>
			<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_parity); ?>">
				<b><?php echo (!$dashboard_widget ? "Parity" : "Critical") ?></b>
			</td>
			<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_unraid); ?>">
				<b><?php echo (!$dashboard_widget ? "Data" : "Warning") ?></b>
			</td>
			<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_cache); ?>">
				<b><?php echo (!$dashboard_widget ? "Cache" : "Normal") ?></b>
			</td>
			<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_others); ?>">
				<b><?php echo (!$dashboard_widget ? "Unassigned devices" : "Temperature N/A") ?></b>
			</td>
			<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_empty); ?>">
				<b>Empty trays</b>
			</td>
		</tr>
	</table>
	<div><br /><br /></div>
	<table style="width: 0;">
		<tr>
			<td style="vertical-align: top; padding-left: 20px;">
				<h2 style="padding-bottom: 25px;">Allocations</h2>
				<table>
					<tr style="border: solid 1px #000000;">
						<td style="padding: 0 10px 0 10px;"><b>Group</b></td>
						<td style="padding: 0 10px 0 10px;"><b>TrayID</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Locate</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Path</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Logical Unit Name</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Manufacturer</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Device Model</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Serial Number</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Capacity</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Rotation</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Form Factor</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Purchased</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Warranty</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Comment</b></td>
						<td style="padding: 0 10px 0 10px;"><b>Custom Color</b></td>
					</tr>
					<?php 
						$i=1;
						while($i <= count($print_drives)) {
							print($print_drives[$i]);
							$i++;
						}
						if(isset($print_add_drives)) {
							print("
								<tr>
									<td style=\"padding: 10px 10px 0 10px;\" colspan=\"15\">
										<h3>Devices not assigned or added</h3>
									</td>
								</tr>
								<tr style=\"border: solid 1px #000000;\">
									<td style=\"padding: 0 10px 0 10px;\"><b>Group</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>TrayID</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Locate</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Path</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Logical Unit Name</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Manufacturer</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Device Model</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Serial Number</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Capacity</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Rotation</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Form Factor</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Purchased</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Warranty</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Comment</b></td>
									<td style=\"padding: 0 10px 0 10px;\"><b>Custom Color</b></td>
								</tr>
								$print_add_drives
							");
						}
					?>
				</table>
				<blockquote class='inline_help'>
					<dt>Tray allocations</dt>
					<dd>Select where to assign the drives and the empty trays, be sure to select a unique tray slot number. It will detect failure and none of the new settings will be saved.</dd>
					
					<dt>Purchased and Warranty</dt>
					<dd>For Unraid array drives which already got the date set, this will be detected (and eventually overwrite) by the main configuration. This plugin will not touch that, unless if those does not exists in the first place. For unassigned devices, you can enter a date of purchase and warranty.</dd>
					
					<dt>Comment</dt>
					<dd>Enter a comment, like where you bought the drive or anything else you'd like. Formatting tools:</dd>
					<dd>
						Bold: [b]<b>text</b>[/b] or *<b>text</b>*<br />
						Italic: [i]<i>text</i>[/i] or _<i>text</i>_<br />
						<br />
						Font sizes:
						<span style="font-size: xx-small">tiny</span>
						<span style="font-size: x-small">small</span>
						<span style="font-size: medium">medium</span>
						<span style="font-size: large">large</span>
						<span style="font-size: x-large">huge</span>
						<span style="font-size: xx-large">massive</span>
						<br />
						Ex: [large]text[/large]<br />
						<br />
						Force break: [br]
					</dd>
					
					<dt>Custom Color</dt>
					<dd>
						Choosing a color here will store it for the specific disk and will override any other color properties.<br />
						Reset/delete the color by choosing the default color for empty (the first color available in the list).
					</dd>
					
					<dt>"Locate" button</dt>
					<dd>The "Locate" button will make your harddisk blink on the LED, this is mainly useful for typical hotswap trays with a LED per tray.</dd>
					
					<dt>"Locate" button does not work</dt>
					<dd>This might not work on all devices, like SSD's. <!--Also check the "Devices" page if the button is really active or not if you started it from the "Configuration" page. The button on the "Configuration" page will not change when pressed, but it will activate it.--></dd>
					
					<dt>LED is blinking continously after using "Locate"</dt>
					<dd>Just enter the plugin from the Unraid settings page and it should automatically shut down the locate script. Else it will run continously until stopped or rebooted.</dd>
				</blockquote>
				<hr />
				<input type="hidden" name="current_warranty_field" value="<?php echo $warranty_field ?>" />
				<input type="submit" name="save_allocations" value="Save" /><!--<input type="reset" value="Reset" />-->
				<!--<input type="submit" name="force_smart_scan" value="Force Scan All" />-->
				<span style="padding-left: 50px;"></span>
				<input type="submit" name="reset_all_colors" value="Reset All Colors" /> <b>or choose "Empty" color (first color listed) per device under "Custom Color" to reset, and then hit the "Save" button.</b>
				<blockquote class='inline_help'>
					<ul>
						<li>"Save" button will store all information entered.</li>
						<!--<li>"Reset" will just revert changes if you changed any values before you saved them, it will not undo the last save.</li>-->
					</ul>
				</blockquote>
			</td>
		</tr>
	</table>
</form>
<script type="text/javascript" src="<?autov("" . DISKLOCATION_PATH . "/pages/script/locate_script_bottom.js")?>"></script>
