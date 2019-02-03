<?php
	$i=1;
	$i_empty=1;
	$i_drive=1;
	unset($disklocation_page); unset($disklocation_layout);
	
	if($disk_tray_direction == "h") { $insert_break = "<br />"; } else { $insert_break = ""; }
	
	while($i <= $total_trays) {
		if(is_array($get_empty_trays)) {
			if($datasql[$i_drive-1]["tray"] == $i) { $data = $datasql[$i_drive-1]; } else { $data = ""; }
		}
		else {
			$data = $datasql[$i_drive-1];
		}
		$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
		
		if(!$data) {
			if($displayinfo["tray"]) {
				$empty_tray = "<b>" . $tray_assign . "</b>" . $insert_break . "";
			}
			if($displayinfo["leddiskop"]) {
				$empty_leddiskop = "<span class=\"grey-off\" alt=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" title=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" />&#11044;</span>" . $insert_break . "";
			}
			if($displayinfo["ledsmart"]) {
				$empty_ledsmart = "<span class=\"grey-off\" alt=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" title=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" />&#11044;</span>";
			}
		
			$disklocation_page .= "
				<div style=\"order: " . $tray_assign . "\">
					<div class=\"flex-container\">
						<div style=\"background-color: #" . $color_array["empty"] . ";\">
							<div class=\"flex-container-start\">
								$empty_tray
								$empty_leddiskop
								$empty_ledsmart
							</div>
							<div class=\"flex-container-middle\">
								<b>Available disk slot</b>
							</div>
							<div class=\"flex-container-end\">
								&nbsp;
							</div>
						</div>
					</div>
				</div>
			";
			
			$disklocation_layout .= "
				<div style=\"order: " . $tray_assign . "\">
					<div class=\"flex-container-layout\">
						<div style=\"background-color: #" . $color_array["empty"] . ";\">
							<b>" . $tray_assign . "</b>
						</div>
					</div>
				</div>
			";
			$i_empty++;
		}
		else {
			$device = $data["device"];
			$devicenode = $data["devicenode"];
			$luname = $data["luname"];
			$hash = $data["hash"];
			
			if($displayinfo["path"]) {
				$device_page = $device;
			}
			if($displayinfo["devicenode"]) {
				$devicenode_page = $devicenode;
			}
			if($displayinfo["luname"]) {
				$luname_page = "(" . $luname . ")";
			}
			if($displayinfo["manufacturer"]) {
				$smart_modelfamily = $data["model_family"];
			}
			if($displayinfo["devicemodel"]) {
				$smart_modelname = $data["model_name"];
			}
			if($displayinfo["serialnumber"]) {
				$smart_serialnumber = ( isset($data["smart_serialnumber"]) ? "<span style=\"white-space: nowrap;\">(" . $data["smart_serialnumber"] . ")</span>" : null );
			}
			if($displayinfo["powerontime"]) {
				$smart_powerontime = ( isset($data["smart_powerontime"]) ? "<span style=\"cursor: help;\" title=\"" . seconds_to_time($data["smart_powerontime"] * 60 * 60) . "\">" . $data["smart_powerontime"] . "h</span>" : null );
			}
			if($displayinfo["loadcyclecount"]) {
				$smart_loadcycle = ( empty($data["smart_loadcycle"]) ? null : $data["smart_loadcycle"] . "c" );
			}
			if($displayinfo["capacity"]) {
				$smart_capacity = human_filesize($data["smart_capacity"], 1, true);
			}
			if($displayinfo["temperature"]) {
				switch($tempunit) {
					case 'F':
						$smart_temperature = round(temperature_conv($data["smart_temperature"], 'C', 'F')) . "°F";
						break;
					case 'K':
						$smart_temperature = round(temperature_conv($data["smart_temperature"], 'C', 'K')) . "K";
						break;
					default:
						$smart_temperature = $data["smart_temperature"] . "°C";
				}
			}
			if($displayinfo["rotation"]) {
				switch($data["smart_rotation"]) {
					case -1:
						$smart_rotation = "SSD";
						break;
					case 0:
						$smart_rotation = "";
						break;
					default:
						$smart_rotation = $data["smart_rotation"] . "rpm";
				}
			}
			if($displayinfo["formfactor"]) {
				$smart_formfactor = str_replace(" inches", "&quot;", $data["smart_formfactor"]);
			}
			
			if($displayinfo["leddiskop"]) {
				if($unraid_array[$devicenode]["color"] && $unraid_array[$devicenode]["status"]) {
					if($unraid_array[$devicenode]["type"] == "Cache") {
						$disk_status_type = "cache";
					}
					else {
						$disk_status_type = "";
					}
					
					$unraid_disk_status_message = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["status"], $disk_status_type);
					
					if($unraid_array[$devicenode]["color"] == "green-blink") { $unraid_add_greenblinkid = " class=\"greenblink\" id=\"greenblink\""; } else { $unraid_add_greenblinkid = ""; }
					$unraid_array_icon = "<span class=\"" . $unraid_array[$devicenode]["color"] . "\" alt=\"" . $unraid_disk_status_message . "\" title=\"" . $unraid_disk_status_message . "\" />&#11044;</span>" . $insert_break . "";
				}
				else {
					usleep($smart_exec_delay . 000); // delay script to get the output of the next shell_exec()
					$smart_powermode = trim(shell_exec("smartctl -n standby /dev/bsg/$device | grep Device"));
					
					if(strstr($smart_powermode, "ACTIVE")) {
						$unraid_disk_status_color = "green-on";
						$unraid_disk_status_message = get_unraid_disk_status($unraid_disk_status_color, "DISK_OK");
						$unraid_add_greenblinkid = "";
					}
					else if(strstr($smart_powermode, "IDLE")) {
						$unraid_disk_status_color = "green-on";
						$unraid_disk_status_message = get_unraid_disk_status($unraid_disk_status_color, "DISK_OK");
						$unraid_add_greenblinkid = "";
					}
					else if(strstr($smart_powermode, "STANDBY")) {
						$unraid_disk_status_color = "green-blink";
						$unraid_disk_status_message = get_unraid_disk_status($unraid_disk_status_color, "DISK_OK");
						$unraid_add_greenblinkid = " class=\"greenblink\" id=\"greenblink\"";
					}
					else {
						$unraid_disk_status_color = "grey-off";
						$unraid_disk_status_message = get_unraid_disk_status($unraid_disk_status_color, "DISK_NP");
						$unraid_add_greenblinkid = "";
					}
					
					$unraid_array_icon = "<span class=\"" . $unraid_disk_status_color . "\" alt=\"" . $unraid_disk_status_message . "\" title=\"" . $unraid_disk_status_message . "\" />&#11044;</span>" . $insert_break . "";
				}
			}
			
			if($displayinfo["ledsmart"]) {
				$smart_status = $data["smart_status"];
				switch($smart_status) {
					case 1:
						$smart_status_icon = "<span class=\"green-on\" alt=\"S.M.A.R.T: Passed\" title=\"S.M.A.R.T: Passed\" />&#11044;</span>";
						break;
					case 0:
						$smart_status_icon = "<span class=\"red-on\" alt=\"S.M.A.R.T: Failed!\" title=\"S.M.A.R.T: Failed!\" />&#11044;</span>";
						break;
					default:
						$smart_status_icon = "<span class=\"grey-off\" alt=\"S.M.A.R.T: Off/None\" title=\"S.M.A.R.T: Off/None\" />&#11044;</span>";
				}
			}
			
			if($displayinfo["unraidinfo"]) {
				$unraid_dev = ( isset($unraid_array[$devicenode]["type"]) ? "<b>" . $unraid_array[$devicenode]["type"] . "</b>: " . $unraid_array[$devicenode]["name"] : "<b>Unassigned:</b>" );
			}
			
			$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
			$drive_tray_order[$hash] = get_tray_location($db, $hash);
			$drive_tray_order[$hash] = ( empty($drive_tray_order[$hash]) ? $count_real : $drive_tray_order[$hash] );
			if($displayinfo["tray"]) {
				$add_traynumber = "<b>" . $drive_tray_order[$hash] . "</b>" . $insert_break . "";
			}
			
			if($displayinfo["devicenode"]) {
				$devicenode_page = str_replace("-", "", $devicenode);
			}
			
			$add_break_1 = "";
			$add_break_2 = "";
			if($unraid_dev || $device_page || $devicenode_page || $luname_page) {
				$add_break_1 = "<br />";
			}
			if($smart_modelfamily || $smart_modelname || $smart_serialnumber) {
				$add_break_2 = "<br />";
			}
			
			$disklocation_page .= "
				<div style=\"order: " . $drive_tray_order[$hash] . "\">
					<div class=\"flex-container\">
						<div style=\"background-color: #" . $color_array[$hash] . ";\">
							<div class=\"flex-container-start\">
								$add_traynumber
								$unraid_array_icon
								$smart_status_icon
								
							</div>
							<div class=\"flex-container-middle\">
								$unraid_dev $device_page $devicenode_page $luname_page $add_break_1
								$smart_modelfamily $smart_modelname $smart_serialnumber $add_break_2
								$smart_temperature $smart_powerontime $smart_loadcycle $smart_capacity $smart_rotation $smart_formfactor
							</div>
							<!--
							<div class=\"flex-container-end\">
								<input type=\"button\" class=\"diskLocation\" onclick=\"locateStart()\" value=\"Locate\" id=\"" . $device . "\" name=\"" . $device . "\" />
							</div>
							-->
						</div>
					</div>
				</div>
			";
			
			$disklocation_layout .= "
				<div style=\"order: " . $drive_tray_order[$hash] . "\">
					<div class=\"flex-container-layout\">
						<div style=\"background-color: #" . $color_array[$hash] . ";\">
							<b>" . $drive_tray_order[$hash] . "</b>
						</div>
					</div>
				</div>
			";
			
			$i_drive++;
		}
		
		if($total_main_trays == $i) {
			$disklocation_page .= "</div><div class=\"grid-container\" style=\"grid-template-rows: " . $grid_columns_override_styles . "; margin: " . $tray_height . "px;\">";
			$disklocation_layout .= "</div><div class=\"grid-container\" style=\"grid-template-rows: " . $grid_columns_override_styles . "; margin: " . $tray_height / 10 . "px;\">";
		}
		
		$i++;
	}
	
	$grid_columns_styles = str_repeat(" auto", $grid_columns);
	$grid_rows_styles = str_repeat(" auto", $grid_rows);
?>
<style type="text/css">
	<?php require_once("styles/disk_" . $disk_tray_direction . ".css.php"); ?>
</style>
<link type="text/css" rel="stylesheet" href="<?autov("" . DISKLOCATION_PATH . "/pages/styles/signals.css")?>">
<script type="text/javascript" src="<?autov("" . DISKLOCATION_PATH . "/pages/script/locate_script_top.js.php")?><?php print("&amp;path=" . DISKLOCATION_PATH . ""); ?>"></script>
<table>
	<tr>
		<td style="padding: 25px 0 0 0;">
			<div class="grid-container">
				<?php print($disklocation_page); ?>
			</div>
		</td>
	</tr>
</table>
