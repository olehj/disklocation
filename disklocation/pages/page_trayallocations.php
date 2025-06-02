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
	unset($print_drives);
	
	// Tray Allocations & Unassigned Devices
	
	if(!empty($disklocation_error) && isset($_POST["save_allocations"])) {
		$i=0;
		print("<h2 style=\"margin: 0; color: #FF0000; font-weight: bold;\">ERROR Could not save the configuration (previous form restored):</h2><br /><span style=\"font-size: medium;\">");
		while($i < count($disklocation_error)) {
			print("&middot; " . $disklocation_error[$i] . "<br />");
			$i++;
		}
		print("</span><hr style=\"clear: both; border-bottom: 1px solid #FF0000;\" /><br /><br /><br />");
	}
	
	$i=0;
	foreach($get_groups as $id => $data) {
		$group[$i]["id"] = $id;
		$group[$i]["group_name"] = $data["group_name"];
		$i++;
	}
	
	$select_db_trayalloc = "group,tray," . $select_db_trayalloc; // always include group and tray
	
	$get_trayalloc_select = get_table_order($select_db_trayalloc, $sort_db_trayalloc, 1);
	$get_drives_select = get_table_order($select_db_drives, ( !empty($sort_db_drives_override) ? $sort_db_drives_override : $sort_db_drives ), 1);
	
	$i=1;
	$i_empty=1;
	$i_drive=1;
	
	$array_groups = $get_groups;
	$array_locations = $get_locations;
	$print_drives = array();
	$data = array();
	$raw_devices = array();
	$table_colspan = 0;
	
	list($table_trayalloc_order_user, $table_trayalloc_order_system, $table_trayalloc_order_name, $table_trayalloc_order_full, $table_trayalloc_order_forms) = get_table_order($select_db_trayalloc, $sort_db_trayalloc);
	
	$arr_length = count($table_trayalloc_order_user);
	for($i=0;$i<$arr_length;$i++) {
		$table_trayalloc_order_name_html .= "<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\"><b style=\"cursor: help;\" title=\"" . $table_trayalloc_order_full[$i] . "\">" . $table_trayalloc_order_name[$i] . "</b></td>";
		$table_colspan = $i;
	}
	
	foreach($devices as $hash => $data) { // array as hash => array(raw/formatted)
		$raw_devices[] = array("hash" => $hash)+$data["raw"];
	}
	
	unset($data);
	
	$db_sort = explode(",", $get_trayalloc_select["db_sort"]);
	$sort_dynamic = array();
	foreach($db_sort as $sort_by) {
		list($sort, $dir, $flag) = explode(" ", $sort_by);
		$dir = ( ($dir == 'SORT_ASC') ? SORT_ASC : SORT_DESC );
		$$sort = ( is_array($raw_devices) ? array_column($raw_devices, $sort) : null );
		$sort_dynamic[] = &$$sort;
		$sort_dynamic[] = $dir;
		if($flag) { 
			$sort_dynamic[] = $flag;
		}
	}
	( is_array($raw_devices) ? call_user_func_array('array_multisort', array_merge($sort_dynamic, array(&$raw_devices))) : null );
	
	$smallest_tray_group = 99999999;
	$biggest_tray_group = 0;
	
	foreach($array_trayid as $arr_groupid => $arr_trayid) {
		if($smallest_tray_group > min($arr_trayid) && !is_null($arr_trayid)) {
			$smallest_tray_group = empty($smallest_tray_group) ? 0 : min($arr_trayid);
		}
		if($biggest_tray_group < max($arr_trayid)) {
			$biggest_tray_group = max($arr_trayid);
		}
	}
	
	foreach($raw_devices as $key => $data) {
		if($data["hash"]) {
			$status = ( !$data["status"] ? 'a' : $data["status"] );
			$allocated = ( ($status != "a") ? "unallocated" : "allocated" );
			$hash = $data["hash"];
			
			$data = $devices[$hash];
			
			$formatted = $data["formatted"];
			$raw = $data["raw"];
			$data = $data["raw"];
			
			$gid = $data["groupid"];
			
			if(is_array($array_groups[$gid])) {
				extract($array_groups[$gid]);
			}
			
			if($data["bgcolor"] && $status == "a") {
				array_push($custom_colors_array, strtoupper($data["bgcolor"]));
			}
			
			$tray_assign = ( !is_numeric($data["tray"]) ? null : $data["tray"] );
			$tray_options = "";
			
			for($tray_i = $smallest_tray_group; $tray_i <= $biggest_tray_group; ++$tray_i) {
				if($array_trayid[$gid][$tray_assign] === $tray_i) { $selected="selected"; } else { $selected=""; }
				$tray_options .= "<option value=\"" . $tray_i . "\" " . $selected . " style=\"text-align: right;\">" . $tray_i . "</option>";
			}
			
			$group_options = "";
			for($group_i = 0; $group_i < $total_groups; ++$group_i) {
				$gid = $group[$group_i]["id"];
				$gid_name = ( empty($group[$group_i]["group_name"]) ? $gid : $group[$group_i]["group_name"] );
				if($data["groupid"] == $gid) { $selected="selected"; } else { $selected=""; }
				$group_options .= "<option value=\"$gid\" " . $selected . " style=\"text-align: left;\">" . stripslashes(htmlspecialchars($gid_name)) . "</option>";
			}
			
			$warr_options = "";
			$warranty_months = array('6','12','18','24','36','48','60');
			for($warr_i = 0; $warr_i < count($warranty_months); ++$warr_i) {
				if($data["warranty"] == $warranty_months[$warr_i]) { $selected="selected"; } else { $selected=""; }
				$warr_options .= "<option value=\"$warranty_months[$warr_i]\" " . $selected . " " . ( (!$allow_unraid_edit && !$selected) ? "disabled=\"disabled\"" : null ) . " style=\"text-align: right;\">$warranty_months[$warr_i] months</option>";
			}
			
			$bgcolor = ( empty($data["bgcolor"]) ? $bgcolor_empty : $data["bgcolor"] );
			
			$listarray = list_array($formatted, 'html', $physical_traynumber);
			unset($listarray["groupid"]);
			unset($listarray["tray"]);
			// Override array for writable forms
			$listarray["groupid"] = "<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><select name=\"groups[" . $hash . "]\" style=\"min-width: 0; max-width: 150px; min-width: 40px;\"><option value=\"\" selected style=\"text-align: left;\">--</option>" . $group_options . "</select></td>";
			$listarray["tray"] = "<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><select name=\"drives[" . $hash . "]\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><option value=\"\" selected style=\"text-align: right;\">--</option>" . $tray_options . "</select></td>";
			$listarray["manufactured"] = "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input " . ( $allow_unraid_edit ? null : "readonly=\"readonly\"" ) . " type=\"date\" name=\"manufactured[" . $hash . "]\" max=\"9999-12-31\" value=\"" . $data["manufactured"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>";
			$listarray["purchased"] = "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input " . ( $allow_unraid_edit ? null : "readonly=\"readonly\"" ) . " type=\"date\" name=\"purchased[" . $hash . "]\" max=\"9999-12-31\" value=\"" . $data["purchased"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>";
			$listarray["installed"] = "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"installed[" . $hash . "]\" max=\"9999-12-31\" value=\"" . $data["installed"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>";
			$listarray["warranty"] = "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><select name=\"warranty[" . $hash . "]\" style=\"min-width: 0; max-width: 80px; width: 80px;\"><option value=\"\" style=\"text-align: right;\" " . ( $allow_unraid_edit ? null : "disabled=\"disabled\"" ) . ">unknown</option>" . $warr_options . "</select></td>";
			$listarray["comment"] = "<td style=\"white-space: nowrap; padding: 0 10px 0 10px; text-align: right;\"><input type=\"text\" name=\"comment[" . $hash . "]\" value=\"" . stripslashes(htmlspecialchars($data["comment"])) . "\" style=\"width: 150px;\" /></td>";
			
			$print_drives[$i_drive][$status] .= "<tr style=\"background: #" . $color_array[$hash] . ";\">";
			$print_drives[$i_drive][$status] .= "
				<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: left;\">
					<button type=\"submit\" name=\"hash_remove\" value=\"" . $hash . "\" title=\"This will force move the drive to the &quot;History&quot; section.\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px; background-color: #FFFFFF;\"><i style=\"font-size: 15px;\" class=\"fa fa-minus-circle fa-lg\"/></i></button>
				</td>
				<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px; text-align: center;\"><input type=\"button\" class=\"diskLocation\" style=\"background-color: #F2F2F2; transform: none;\" onclick=\"locateStart()\" value=\"Locate\" id=\"" . $data["device"] . "\" name=\"" . $allocated . "\" /></td>
			";
			
			$arr_length = count($table_trayalloc_order_system);
			for($i=0;$i<$arr_length;$i++) {
				$print_drives[$i_drive][$status] .= $listarray[$table_trayalloc_order_system[$i]];
			}
			
			$print_drives[$i_drive][$status] .= "
				<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">
					<input type=\"color\" name=\"bgcolor_custom[" . $hash . "]\" list=\"disklocationColorsCus\" value=\"#" . $bgcolor . "\" " . ($device_bg_color ? "disabled=\"disabled\"" : null ) . " />
					" . ($device_bg_color ? "<input type=\"hidden\" name=\"bgcolor_custom[" . $hash . "]\" value=\"#" . $bgcolor . "\" />" : null ) . "
				</td>

			";
			
			$print_drives[$i_drive][$status] .= "</tr>";
			
			$i_drive++;
			$i++;
		}
	}
	
	// History
	
	$data = "";
	
	list($table_drives_order_user, $table_drives_order_system, $table_drives_order_name, $table_drives_order_full, $table_drives_order_forms) = get_table_order($select_db_drives, ( !empty($sort_db_drives_override) ? $sort_db_drives_override : $sort_db_drives ));
	
	$arr_length = count($table_drives_order_user);
	for($i=0;$i<$arr_length;$i++) {
		$table_drives_order_name_html .= "
			<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">
				<b style=\"cursor: help;\" title=\"" . $table_drives_order_full[$i] . "\">" . $table_drives_order_name[$i] . "</b><br />
				<button type=\"submit\" name=\"sort\" value=\"drives:asc:" . $table_drives_order_user[$i] . "\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px;\" /><i style=\"font-size: 15px;\" class=\"fa fa-solid fa-sort-up\"/></i></button>
				<button type=\"submit\" name=\"sort\" value=\"drives:desc:" . $table_drives_order_user[$i] . "\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px;\" /><i style=\"font-size: 15px;\" class=\"fa fa-solid fa-sort-down\"/></i></button>
			</td>
		";
	}
	
	unset($data);
	unset($raw_devices);
	unset($sort_dynamic);
	unset($raw_devices);
	
	$i=0;
	foreach($devices as $hash => $data) { // array as hash => array(raw/formatted)
		if($devices[$hash]["raw"]["status"] == "r") {
			$raw_devices[$i] = array("hash" => $hash)+$data["raw"];
			// override from devices.json as SMART data will be gone when the disk is gone.
			$raw_devices[$i]["lun"] = $get_devices[$hash]["lun"];
			$raw_devices[$i]["manufacturer"] = $get_devices[$hash]["manufacturer"];
			$raw_devices[$i]["smart_status"] = $get_devices[$hash]["smart_status"];
			$raw_devices[$i]["powerontime"] = $get_devices[$hash]["powerontime"];
			$raw_devices[$i]["loadcycle"] = $get_devices[$hash]["loadcycle"];
			$raw_devices[$i]["capacity"] = $get_devices[$hash]["capacity"];
			$raw_devices[$i]["rotation"] = $get_devices[$hash]["rotation"];
			$raw_devices[$i]["formfactor"] = $get_devices[$hash]["formfactor"];
			$raw_devices[$i]["smart_units_read"] = $get_devices[$hash]["smart_units_read"];
			$raw_devices[$i]["smart_units_written"] = $get_devices[$hash]["smart_units_written"];
			$raw_devices[$i]["endurance"] = $get_devices[$hash]["endurance"];
			$raw_devices[$i]["manufactured"] = $get_devices[$hash]["manufactured"];
			$raw_devices[$i]["purchased"] = $get_devices[$hash]["purchased"];
			$raw_devices[$i]["installed"] = $get_devices[$hash]["installed"];
			$raw_devices[$i]["removed"] = $get_devices[$hash]["removed"];
			$raw_devices[$i]["warranty"] = $get_devices[$hash]["warranty"];
			
			$i++;
		}
	}
	
	$db_sort = explode(",", $get_drives_select["db_sort"]);
	$sort_dynamic = array();
	foreach($db_sort as $sort_by) {
		list($sort, $dir, $flag) = explode(" ", $sort_by);
		$dir = ( ($dir == 'SORT_ASC') ? SORT_ASC : SORT_DESC );
		$$sort = ( is_array($raw_devices) ? array_column($raw_devices, $sort) : null );
		$sort_dynamic[] = &$$sort;
		$sort_dynamic[] = $dir;
		if($flag) { 
			$sort_dynamic[] = $flag;
		}
	}
	( is_array($raw_devices) ? call_user_func_array('array_multisort', array_merge($sort_dynamic, array(&$raw_devices))) : null );
	
	foreach($raw_devices as $key => $data) {
		$hash = $data["hash"];
		
		$data = $devices[$hash];
		
		$formatted = $data["formatted"];
		$raw = $data["raw"];
		$data = $data["raw"];
		
		$gid = $data["groupid"];
		
		$listarray = list_array($formatted, 'html', $physical_traynumber);
		//unset($listarray["groupid"]);
		//unset($listarray["tray"]);
		
		$print_removed_drives .= "<tr style=\"background: #" . $color_array[$hash] . ";\">";
		$print_removed_drives .= "
			<td style=\"padding: 0 10px 0 10px; white-space: nowrap;\">
				<button type=\"submit\" name=\"hash_delete\" value=\"" . $hash . "\" title=\"Delete, this will flag the drive hidden in the database.\" style=\"min-width: 0; background-size: 0; margin: 0; padding: 0;\"><i style=\"font-size: 15px;\" class=\"fa fa-minus-circle fa-lg\"></i></button>
				<button type=\"submit\" name=\"hash_add\" value=\"" . $hash . "\" title=\"Add, will revert to &quot;not found list&quot; if the drive really does not exists.\" style=\"min-width: 0; background-size: 0; margin: 0; padding: 0;\"><i style=\"font-size: 15px;\" class=\"fa fa-plus-circle fa-lg\"></i></button>
			</td>
		";
		
		$arr_length = count($table_drives_order_system);
		for($i=0;$i<$arr_length;$i++) {
			$print_removed_drives .= $listarray[$table_drives_order_system[$i]];
		}
		$print_removed_drives .= "</tr>";
	}
	
	$array_groups = $get_groups;
	( is_array($array_groups) ?? ksort($array_groups, SORT_NUMERIC) );
	$array_devices = $get_devices;
	$array_locations = $get_locations;
	$disk_layouts_alloc = "";
	
	foreach($array_groups as $gid => $value) {
		$groupid = $gid;
		$gid_name = ( empty($array_groups[$gid]["group_name"]) ? $gid : $array_groups[$gid]["group_name"] );
		
		$css_grid_group = "
			grid-template-columns: " . $grid_columns_styles[$gid] . ";
			grid-template-rows: " . $grid_rows_styles[$gid] . ";
			grid-auto-flow: " . $array_groups[$gid]["grid_count"] . ";
		";
		
		$disk_layouts_alloc .= "
			<div style=\"float: left; padding: 10px 20px 10px 20px;\">
				<h2 style=\"text-align: center;\">
					" . stripslashes(htmlspecialchars($gid_name)) . "
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
			$bgcolor_custom_array .= ( isset($custom_colors_array_dedup[$i]) ? "<option>#" . strtoupper($custom_colors_array_dedup[$i]) . "</option>\n" : null );
		}
	}
?>
<?php if($db_update == 2) { print("<h3>Page unavailable due to database error.</h3><!--"); } ?>
<script type="text/javascript" src="<?autov("" . DISKLOCATION_PATH . "/pages/script/locate_script_top.js.php")?><?php print("&amp;path=" . DISKLOCATION_PATH . ""); ?>"></script>
<datalist id="disklocationColorsCus">
	<?php echo $bgcolor_custom_array ?>
</datalist>
<table><tr><td style="padding: 10px 10px 10px 10px;">
<form action="" method="post">
	<?php print($disk_layouts_alloc); ?>
	<div style="clear: both;"></div>
	<div style="padding: 0 0 40px 0;"></div>
	<table style="width: 800px; border-spacing: 3px; border-collapse: separate; padding: 0 20px 25px 20px;">
		<tr>
			<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_parity); ?>">
				<b><?php echo (!$device_bg_color ? "Parity" : "Critical") ?></b>
			</td>
			<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_unraid); ?>">
				<b><?php echo (!$device_bg_color ? "Data" : "Warning") ?></b>
			</td>
			<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_cache); ?>">
				<b><?php echo (!$device_bg_color ? "Cache/Pool" : "Normal") ?></b>
			</td>
			<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_others); ?>">
				<b><?php echo (!$device_bg_color ? "Unassigned devices" : "Temperature N/A") ?></b>
			</td>
			<td style="width: 20%; padding: 0 2px 0 2px; background: #<?php print($bgcolor_empty); ?>">
				<b>Empty trays</b>
			</td>
		</tr>
	</table>
	<div><br /><br /></div>
	<table style="table-layout: auto; width: 0;">
		<tr>
			<td style="vertical-align: top; padding-left: 20px;">
				<table style="table-layout: auto; width: 0; border-spacing: 2px; border-collapse: separate;">
					<tr>
						<td style="padding: 10px 10px 0 10px;" colspan="<?php print($table_colspan + 4); ?>">
							<h2>Allocations</h2>
							<p style="margin-top: -10px;">
								<?php
									if(!empty($check_select_trayalloc) || !empty($check_sort_trayalloc)) { print("<span style=\"display: block;\" class=\"red\"><b>Table column and/or sort is faulty, please correct it under Configuration. Default column and order is used.</b></span>"); }
								?>
								<b>Warning! Please use "Force SMART+DB" button under "System" tab before manually deleting and/or re-adding devices manually.</b><br />
								The <i class="fa fa-minus-circle fa-lg"></i> button will force the drive to be moved to the "History" section below. Use this if you have false drive(s) in your list.
								If you accidentally click the button on the wrong drive you have to do a "Force SMART+DB" and reassign the drive.
							</p>
							
							<p style="color: red; padding: 0 0 30px 0;">
								<!--
								<b>OBS! When allocating drives you must use the TrayID numbers shown in red bold and not the physical tray assignment shown on the right/bottom (these are only shown if the numbers differ).</b>
								-->
								<?php ( !empty($device_bg_color) ?? print("<!--<br />-->Custom Color is disabled when \"Heat Map\" is used.") ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<td style="width: 0; padding: 0 10px 0 10px;"><b>#</b></td>
						<td style="width: 0; padding: 0 10px 0 10px;"><b>Locate</b></td>
						<?php print($table_trayalloc_order_name_html); ?>
						<td style="width: 0; padding: 0 10px 0 10px;"><b>Custom Color</b></td>
					</tr>

					<?php 
						$i=1;
						while($i <= count($print_drives)) {
							print($print_drives[$i]["a"]);
							$print_add_drives .= $print_drives[$i]["h"];
							$i++;
						}
						$i=1;
						if(!empty($print_add_drives)) {
							print("
									<tr>
										<td style=\"padding: 10px 10px 0 10px;\" colspan=\"" . ($table_colspan + 4) . "\">
											<h2>Unassigned Devices</h2>
										</td>
									</tr>
									<tr style=\"border: solid 1px #000000;\">
										<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px;\"><b>#</b></td>
										<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px;\"><b>Locate</b></td>
										$table_trayalloc_order_name_html
										<td style=\"width: 0; white-space: nowrap; padding: 0 10px 0 10px;\"><b>Custom Color</b></td>
									</tr>
									$print_add_drives
							");
						}
					?>
					<tr>
						<td style="padding: 10px 10px 0 10px;" colspan="<?php print($table_colspan + 4); ?>">
							<?php print( !$allow_unraid_edit ? "<span class=\"red\">Columns containing \"Manufactured\", \"Purchased\" and \"Warranty\" is read-only. To enable editing, go to Configuration and set \"Allow editing of Unraid config\" to \"Yes\"</span>" : "" ); ?>
							<blockquote class='inline_help'>
								<dt>Tray allocations</dt>
								<dd>Select where to assign the drives and the empty trays, be sure to select a unique tray slot number. It will detect failure and none of the new settings will be saved.</dd>
								
								<dt>Manufactured, Purchased and Warranty</dt>
								<dd>For Unraid array drives which already got the date set, this will be detected automatically and used. You can use this plugin to enter all the dates in bulk if the "Allow editing of Unraid config" is set to "Yes" under Configuration.</dd>
								
								<dt>Comment</dt>
								<dd>Enter a comment, like where you bought the drive or anything else you'd like. Formatting tools:</dd>
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
									Force break/new line: [br]
									Color: [color:HEX]text[/color]<br />
									E.g. [color:0000FF][b]text[/b][/color] = <span style="color: #0000FF;"><b>text</b></span>
								</dd>
								
								<dt>Custom Color</dt>
								<dd>
									Choosing a color here will store it for the specific disk and will override any other color properties.<br />
									Reset/delete the color by choosing the default color for empty (the first color available in the list).
								</dd>
								
								<dt>"Locate" button</dt>
								<dd>The "Locate" button will make your harddisk blink on the LED, this is mainly useful for typical hotswap trays with a LED per tray.</dd>
								
								<dt>"Locate" button does not work</dt>
								<dd>This might not work on all devices, like SSD's.</dd>
								
								<dt>LED is blinking continously after using "Locate"</dt>
								<dd>Click the "Force Stop All Locate" button to kill the locating function.</dd>
							</blockquote>
							<hr />
							<input type="hidden" name="array_trayid" value="<?php print(serialize($array_trayid)) ?>" />
							<input type="submit" name="save_allocations" value="Save" />
							<span style="padding-left: 50px;"></span>
							<input type="submit" name="killall_smartlocate" value="Force Stop All Locate" />
							<span style="padding-left: 50px;"></span>
							<input type="submit" name="reset_all_colors" value="Reset All Custom Colors" /> <b>or choose "Empty" color (first color listed) per device under "Custom Color" to reset, and then hit the "Save" button.</b>
							<blockquote class='inline_help'>
								<ul>
									<li>"Save" button will store all information entered.</li>
									<li>"Force Stop All Locate" button will terminate all "Locate" runs.</li>
									<li>"Reset All Custom Colors" will delete all custome stored colors from the database.</li>
								</ul>
							</blockquote>
							<hr />
						</td>
					</tr>
				</table>
				<?php
					if(isset($print_removed_drives)) {
						print("
							<table style=\"table-layout: auto; width: 0; border-spacing: 2px; border-collapse: separate; margin: 0;\">
								<tr>
									<td style=\"padding: 10px 10px 0 10px;\" colspan=\"" . ($table_colspan + 4) . "\">
										<h2>History</h2>
										<p style=\"padding: 0 0 0 0;\">
											" . ( (!empty($check_select_drives) || !empty($check_sort_drives)) ? "<span style=\"display: block;\" class=\"red\"><b>Table column and/or sort is faulty, please correct it under Configuration. Default column and order is used.</b></span>" : null ) . "
											Warning! The <i class=\"fa fa-minus-circle fa-lg\"></i> button will hide the device permanently from this plugin and can only be reverted by manually changing the flag in the database file (\"Force SMART+DB\" button will not touch hidden devices).<br />
											While the <i class=\"fa fa-plus-circle fa-lg\"></i> button will re-add the drive to the main list for tray allocation, it will revert back to the not found list if the drive does actually not exists after using \"Force SMART+DB\".
										</p>
									</td>
								</tr>
								<tr style=\"border: solid 1px #000000;\">
									<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\"><b>#</b></td>
									$table_drives_order_name_html
								</tr>
								$print_removed_drives
							</table>
							<input type=\"submit\" name=\"sort_reset\" value=\"Set default sort\" />
						");
					}
				?>
				<blockquote class='inline_help'>
					<dt>"History buttons"</dt>
					<ul>
						<li>Delete, this will flag the drive hidden in the database.</li>
						<li>Add, will revert to &quot;not found list&quot; if the drive does not exists, but will reappear in the configuration if it really does. Usually it shouldn't be any need for this.</li>
					</ul>
				</blockquote>
			</td>
		</tr>
	</table>
</form>
</td></tr></table>
<script type="text/javascript" src="<?autov("" . DISKLOCATION_PATH . "/pages/script/locate_script_bottom.js")?>"></script>
<?php if($db_update == 2) { print("-->"); } ?>
