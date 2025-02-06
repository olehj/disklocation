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
	
	unset($print_drives, $table_info_order_name_html);
	unset($get_info_select, $table_info_order_user, $table_info_order_system, $table_info_order_name, $table_info_order_full, $table_info_order_forms);
	
	$get_info_select = get_table_order($select_db_smart, ( !empty($sort_db_smart_override) ? $sort_db_smart_override : $sort_db_smart ), 1);
	
	$i=1;
	$i_empty=1;
	$i_drive=1;
	
	$array_groups = $get_groups;
	$array_locations = $get_locations;
	$print_drives = array();
	$print_smart = array();
	$data = array();
	$raw_devices = array();
	$disk_not_ack = array();
	
	list($table_info_order_user, $table_info_order_system, $table_info_order_name, $table_info_order_full, $table_info_order_forms) = get_table_order($select_db_smart, ( !empty($sort_db_smart_override) ? $sort_db_smart_override : $sort_db_smart ));
	
	$arr_length = count($table_info_order_user);
	for($i=0;$i<$arr_length;$i++) {
		$table_info_order_name_html .= "
		<td style=\"white-space: nowrap; padding: 0 10px 0 10px;\">
			<b style=\"cursor: help;\" title=\"" . $table_info_order_full[$i] . "\">" . $table_info_order_name[$i] . "</b><br />
			<button type=\"submit\" name=\"sort\" value=\"smart:asc:" . $table_info_order_user[$i] . "\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px;\" /><i style=\"font-size: 15px;\" class=\"fa fa-solid fa-sort-up\"/></i></button>
			<button type=\"submit\" name=\"sort\" value=\"smart:desc:" . $table_info_order_user[$i] . "\" style=\"margin: 0; padding: 0; min-width: 0; width: 20px; height: 20px;\" /><i style=\"font-size: 15px;\" class=\"fa fa-solid fa-sort-down\"/></i></button>
		</td>";
	}
	
	foreach($devices as $hash => $data) { // array as hash => array(raw/formatted)
		$raw_devices[] = array("hash" => $hash)+$data["raw"];
	}
	
	unset($data);
	
	$db_sort = explode(",", $get_info_select["db_sort"]);
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
		if(empty($data["status"]) && $data["groupid"]) {
			$hash = $data["hash"];
			
			$data = $devices[$hash];
			
			$formatted = $data["formatted"];
			$raw = $data["raw"];
			$data = $data["raw"];
			
			$gid = $data["groupid"];
			
			extract($array_groups[$gid]);
			
			$group_assign = ( empty($group_name) ? $data["groupid"] : $group_name );
			
			$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
			
			$total_trays = ( empty($grid_trays) ? $grid_columns * $grid_rows : $grid_trays );
			$total_trays_group += $total_trays;
			
			if($biggest_tray_group < $total_trays) {
				$biggest_tray_group = $total_trays;
			}
			
			if(!$tray_direction) { $tray_direction = 1; }
			$tray_number_override = tray_number_assign($grid_columns, $grid_rows, $tray_direction, $grid_count);
			
			if(!isset($tray_start_num)) { $tray_start_num = 1; }
			$tray_number_override_start = $tray_start_num;
			
			$total_main_trays = 0;
			if($total_trays > ($grid_columns * $grid_rows)) {
				$total_main_trays = $grid_columns * $grid_rows;
				$total_rows_override_trays = ($total_trays - $total_main_trays) / $grid_columns;
				$grid_columns_override_styles = str_repeat(" auto", $total_rows_override_trays);
			}
			
			$drive_tray_order[$hash] = get_tray_location($db, $hash, $gid);
			$drive_tray_order[$hash] = ( !isset($drive_tray_order[$hash]) ? $tray_assign : $drive_tray_order[$hash] );
			
			if($tray_number_override[$drive_tray_order[$hash]]) {
				$drive_tray_order_assign = $tray_number_override[$drive_tray_order[$hash]];
				$physical_traynumber = ( !isset($tray_number_override_start) ? --$tray_number_override[$drive_tray_order[$hash]] : ($tray_number_override_start + $tray_number_override[$drive_tray_order[$hash]] - 1));
			}
			else {
				$drive_tray_order_assign = $drive_tray_order[$hash];
				$physical_traynumber = ( !isset($tray_number_override_start) ? --$drive_tray_order[$hash] : $drive_tray_order[$hash]);
			}
			
			$get_smart_errors = array_values($data["smart_errors"]);
			if(count($get_smart_errors) > 0) {
				$listarray = list_array($formatted, 'html', $physical_traynumber, 'top');
				
				$print_drives[$i_drive] = "<tr style=\"border: 1px solid #000000; background: #" . $color_array[$hash] . ";\">";
				
				$arr_length = count($table_info_order_system);
				for($i=0;$i<$arr_length;$i++) {
					$print_drives[$i_drive] .= $listarray[$table_info_order_system[$i]];
				}
				
				$print_drives[$i_drive] .= "<td style=\"padding: 0 0 0 0; vertical-align: top;\"><table style=\"background-color: transparent; margin: 0;\">";
				$smart_i = 0;
				while($smart_i < count($get_smart_errors)) {
					$print_drives[$i_drive] .= "
						<tr><td style=\"vertical-align: top; white-space: nowrap; padding: 0 10px 0 10px;\">" . $get_smart_errors[$smart_i]["name"] . "</td><td style=\"text-align: right; vertical-align: top; white-space: nowrap; padding: 0 10px 0 10px;\">" . $get_smart_errors[$smart_i]["value"] . "</td></tr>
					";
					
					$smart_i++;
				}
				if(get_disk_ack($data["name"])) {
					$disk_ack = "YES";
				}
				else {
					$disk_ack = "NO";
					$disk_not_ack[] = $data["name"];
				}
				
				$print_drives[$i_drive] .= "</table></td><td style=\"vertical-align: top; white-space: nowrap; padding: 0 10px 0 10px; text-align: center;\">" . $disk_ack . "</td></tr>";
				
				$i_drive++;
			}
		}
	}
?>
<?php if($db_update == 2) { print("<h3>Page unavailable due to database error.</h3><!--"); } ?>
<table><tr><td style="padding: 10px 10px 10px 10px;">
<h2 style="margin-top: -10px; padding: 0 0 25px 0;">SMART Errors</h2>
<form action="" method="post">
<table style="width: 800px; border-spacing: 3px; border-collapse: separate;">
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
<?php
	if(check_smart_files() && empty($print_drives)) {
		print("<h3 class=\"green\">No errors found</h3><!--");
	}
?>
<div><br /><br /><br /></div>
<table style="width: 0;">
	<tr style="border: solid 1px #000000;">
		<?php print($table_info_order_name_html); ?>
		<td style="width: 0; padding: 0 10px 0 10px; vertical-align: top;"><b>Smart Attribute / Value</b></td>
		<td style="width: 0; padding: 0 10px 0 10px; vertical-align: top;"><b>Acknowledged</b></td>
	</tr>
	<?php 
		if(!empty($print_drives)) {
			$i=1;
			while($i <= count($print_drives)) {
				print($print_drives[$i]);
				$i++;
			}
		}
	?>
</table>
<input type="submit" name="sort_reset" value="Set default sort" />
<?php 
	if(!empty($disk_not_ack) && $allow_unraid_edit) {
		print("<input type=\"hidden\" name=\"disk_ack_drives\" value=\"" . implode(",", $disk_not_ack) . "\">");
	}
	print("<input " . (!empty($disk_not_ack) && $allow_unraid_edit ? null : "disabled=disabled" ) . " type=\"submit\" name=\"disk_ack_all_ok\" value=\"Acknowledge all drives\" />");
?>
<blockquote class='inline_help'>
	"Acknowledge all drives" button is only accessible if "Allow editing of Unraid config" is set to "Yes" and there is drives that are not acknowledged.
	<br />
</blockquote>
<?php
	if(check_smart_files() && empty($print_drives)) {
		print("-->");
	}
?>
</form>
</td></tr></table>
<?php if($db_update == 2) { print("-->"); } ?>
