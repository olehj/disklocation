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
			$disklocation_page .= "
				<div style=\"order: " . $tray_assign . "\">
					<div class=\"flex-container\">
						<div style=\"background-color: #" . $color_array["empty"] . ";\">
							<div class=\"flex-container-start\">
								<b>" . $tray_assign . "</b>" . $insert_break . "
								<span class=\"grey-off\" alt=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" title=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" />&#11044;</span>" . $insert_break . "
								<span class=\"grey-off\" alt=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" title=\"" . get_unraid_disk_status("grey-off", "DISK_NP") . "\" />&#11044;</span>
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
			
			$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
			$device = $data["device"];
			$devicenode = $data["devicenode"];
			$luname = $data["luname"];
			$smart_status = $data["smart_status"];
			$smart_modelfamily = $data["model_family"];
			$smart_modelname = $data["model_name"];
			$smart_serialnumber = ( isset($data["smart_serialnumber"]) ? "(" . $data["smart_serialnumber"] . ")" : null );
			$smart_temperature = ( isset($data["smart_temperature"]) ? $data["smart_temperature"] . "°C" : null );
			$smart_powerontime = ( isset($data["smart_powerontime"]) ? "<span style=\"cursor: help;\" title=\"" . seconds_to_time($data["smart_powerontime"] * 60 * 60) . "\">" . $data["smart_powerontime"] . "h</span>" : null );
			$smart_loadcycle = ( empty($data["smart_loadcycle"]) ? null : $data["smart_loadcycle"] . "c" );
			$smart_capacity = human_filesize($data["smart_capacity"], 1, true);
			
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
			
			$smart_formfactor = str_replace(" inches", "&quot;", $data["smart_formfactor"]);
			
			if($unraid_array[$devicenode]["color"] && $unraid_array[$devicenode]["status"]) {
				if($unraid_array[$devicenode]["type"] == "Cache") {
					$disk_status_type = "cache";
				}
				else {
					$disk_status_type = "";
				}
				
				$unraid_disk_status_message = get_unraid_disk_status($unraid_array[$devicenode]["color"], $unraid_array[$devicenode]["status"], $disk_status_type);
				
				if($unraid_array[$devicenode]["color"] == "green-blink") { $unraid_add_greenblinkid = " class=\"greenblink\" id=\"greenblink\""; } else { $unraid_add_greenblinkid = ""; }
				$unraid_array_icon = "<span class=\"" . $unraid_array[$devicenode]["color"] . "\" alt=\"" . $unraid_disk_status_message . "\" title=\"" . $unraid_disk_status_message . "\" />&#11044;</span>";
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
				
				$unraid_array_icon = "<span class=\"" . $unraid_disk_status_color . "\" alt=\"" . $unraid_disk_status_message . "\" title=\"" . $unraid_disk_status_message . "\" />&#11044;</span>";
			}
			
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
			
			$unraid_dev = ( isset($unraid_array[$devicenode]["type"]) ? "<b>" . $unraid_array[$devicenode]["type"] . "</b>: " . $unraid_array[$devicenode]["name"] : "<b>Unassigned:</b>" );
			
			$drive_tray_order[$luname] = get_tray_location($db, $luname);
			$drive_tray_order[$luname] = ( empty($drive_tray_order[$luname]) ? $count_real : $drive_tray_order[$luname] );
			
			$devicenode_page = str_replace("-", "", $devicenode);
			
			$disklocation_page .= "
				<div style=\"order: " . $drive_tray_order[$luname] . "\">
					<div class=\"flex-container\">
						<div style=\"background-color: #" . $color_array[$luname] . ";\">
							<div class=\"flex-container-start\">
								<b>" . $drive_tray_order[$luname] . "</b>" . $insert_break . "
								" . $unraid_array_icon . "" . $insert_break . "
								" . $smart_status_icon . "
								
							</div>
							<div class=\"flex-container-middle\">
								$unraid_dev $device $devicenode_page (" . $luname . ")<br />
								$smart_modelfamily $smart_modelname <span style=\"white-space: nowrap;\">$smart_serialnumber</span><br />
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
				<div style=\"order: " . $drive_tray_order[$luname] . "\">
					<div class=\"flex-container-layout\">
						<div style=\"background-color: #" . $color_array[$luname] . ";\">
							<b>" . $drive_tray_order[$luname] . "</b>
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
	
	find_and_set_removed_devices_status($db, $lsscsi_luname);
	
	$grid_columns_styles = str_repeat(" auto", $grid_columns);
	$grid_rows_styles = str_repeat(" auto", $grid_rows);
?>
<style type="text/css">
	<?php require_once("styles/disk_" . $disk_tray_direction . ".css.php"); ?>
</style>
<link type="text/css" rel="stylesheet" href="/plugins/disklocation/pages/styles/signals.css">
<script>
function locateStart(locateDisk){
	if(locateDisk) {
		//console.log("Locating started: " + locateDisk.id);
		locateDisk.removeEventListener("click", locateStart);
		locateDisk.addEventListener("click", locateStop);
		locateDisk.value = "Stop";
		locateDisk.style.backgroundColor = '#000000';
		
		$.get('/plugins/disklocation/pages/locate.php',{ disklocation:locateDisk.id, cmd:"start"},function(data) {
			// script is handled in the background, nothing to do here
		});
	}
}

function locateStop(locateDisk){
	//console.log("Locating stopped: " + locateDisk.id);
	locateDisk.removeEventListener("click", locateStop);
	locateDisk.addEventListener("click", locateStart);
	locateDisk.value = "Locate";
	locateDisk.style.backgroundColor = '#FFFFFF';
	
	$.get('/plugins/disklocation/pages/locate.php',{ disklocation:locateDisk.id, cmd:"stop"},function(data) {
		// script is handled in the background, nothing to do here
	});
}

function locateKillAll(locateDisk){
	var y = document.getElementsByClassName(locateDisk);
	var i;
	for (i = 0; i < y.length; i++) {
		y[i].removeEventListener("click", locateStop);
		y[i].addEventListener("click", locateStart);
		y[i].value = "Locate";
		y[i].style.backgroundColor = '#FFFFFF';
		//console.log("Locating killed: " + y[i].id);
	}
	
	$.get('/plugins/disklocation/pages/locate.php',{ disklocation:locateDisk.id, cmd:"killall"},function(data) {
		// script is handled in the background, nothing to do here
	});
}
</script>
<table>
	<tr>
		<td style="padding: 25px 0 0 0;">
			<div class="grid-container">
				<?php print($disklocation_page); ?>
			</div>
		</td>
	</tr>
</table>
