<?php
	$i=1;
	$i_empty=1;
	$i_drive=1;
	unset($print_drives);
	while($i <= $total_trays) {
		if(is_array($get_empty_trays)) {
			if($datasql[$i_drive-1]["tray"] == $i) { $data = $datasql[$i_drive-1]; } else { $data = ""; }
		}
		else {
			$data = $datasql[$i_drive-1];
		}
		$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
		
		if(!$data) {
			$print_drives[$tray_assign] = "
				<tr style=\"background: #" . $color_array["empty"] . ";\">
					<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $tray_assign . "</td>
					<td colspan=\"15\"></td>
				</tr>
			";
			$i_empty++;
		}
		else {
			$smart_powerontime = seconds_to_time($data["smart_powerontime"] * 60 * 60);
			
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
			
			$warranty_expire = "";
			$warranty_left = "";
			if($data["purchased"] && ($data["warranty"] || $data["warranty_date"])) {
				$warranty_start = strtotime($data["purchased"]);
				
				if($warranty_field == "u") {
					$warranty_end = strtotime("" . $data["purchased"] . " + " . $data["warranty"] . " month");
					$warranty_expire = date("Y-m-d", $warranty_end);
					$date_warranty = $data["warranty"] . " months.";
				}
				else {
					$warranty_end = strtotime($data["warranty_date"]);
					$warranty_expire = $data["warranty_date"];
					$date_warranty = $data["warranty_date"];
				}
				
				$warranty_expire_left = $warranty_end-date("U");
				if($warranty_expire_left > 0) {
					$warranty_left = seconds_to_time($warranty_expire_left);
				}
				else {
					$warranty_left = "EXPIRED!";
				}
			}
			
			$print_drives[$tray_assign] = "
				<tr style=\"background: #" . $color_array[$data["luname"]] . ";\">
					<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $tray_assign . "</td>
					<td style=\"padding: 0 10px 0 10px;\">" . $data["device"] . "</td>
					<td style=\"padding: 0 10px 0 10px;\">" . $data["luname"] . "</td>
					<td style=\"padding: 0 10px 0 10px;\">" . $data["model_family"] . "</td>
					<td style=\"padding: 0 10px 0 10px;\">" . $data["model_name"] . "</td>
					<td style=\"padding: 0 10px 0 10px;\">" . $data["smart_serialnumber"] . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . human_filesize($data["smart_capacity"], 1, true) . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $smart_rotation . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $data["smart_formfactor"] . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: center;\">" . ( empty($data["smart_status"]) ? "FAILED" : "PASSED" ) . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\"><span style=\"cursor: help;\" title=\"" . $smart_powerontime . "\">" . $data["smart_powerontime"] . "</span></td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $data["smart_loadcycle"] . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $data["purchased"] . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\"><span style=\"cursor: help;\" title=\"Warranty: " . $date_warranty . " Expires: " . $warranty_left . "\">" . $warranty_expire . "</span></td>
					<td style=\"padding: 0 10px 0 10px;\">" . stripslashes(htmlspecialchars($data["comment"])) . "</td>
				</tr>
			";
			$i_drive++;
		}
		$i++;
	}
	
	while($tray_assign < $total_trays) {
		$tray_assign++;
		$print_drives[$tray_assign] = "
			<tr style=\"background: #" . $color_array["empty"] . ";\">
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $tray_assign . "</td>
				<td colspan=\"15\"></td>
			</tr>
		";
	}
	
	// get removed disks info
	$data = "";
	
	$sql = "SELECT * FROM disks WHERE status = 'r' ORDER BY ID DESC;";
	
	$results = $db->query($sql);
	
	while($data = $results->fetchArray(1)) {
		$smart_powerontime = seconds_to_time($data["smart_powerontime"] * 60 * 60);
		
		$warranty_expire = "";
		$warranty_left = "";
		if($data["purchased"] && $data["warranty"]) {
			$warranty_start = strtotime($data["purchased"]);
			$warranty_end = strtotime("" . $data["purchased"] . " +" . $data["warranty"] . " month");
			$warranty_expire = date("Y-m-d", $warranty_end);
			$warranty_expire_left = $warranty_end-date("U");
			if($warranty_expire_left > 0) {
				$warranty_left = seconds_to_time($warranty_expire_left);
			}
			else {
				$warranty_left = "EXPIRED!";
			}
			
		}
		
		$print_removed_drives .= "
			<tr style=\"background: #" . $color_array[$data["luname"]] . ";\">
				<td style=\"padding: 0 10px 0 10px;\"><form action=\"/plugins/disklocation/pages/system.php\" method=\"post\"><input type=\"image\" name=\"delete\" src=\"/plugins/disklocation/icons/delete.png\"  /><input type=\"hidden\" name=\"luname\" value=\"" . $data["luname"] . "\"  /></form></td><!-- onclick=\"deleteDisk('" . $data["luname"] . "');\" -->
				<td style=\"padding: 0 10px 0 10px;\">" . $data["device"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["luname"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["model_family"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["model_name"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["smart_serialnumber"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . human_filesize($data["smart_capacity"], 1, true) . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . ( empty($data["smart_rotation"]) ? "SSD" : $data["smart_rotation"] . " rpm" ) . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $data["smart_formfactor"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: center;\">" . ( empty($data["smart_status"]) ? "FAILED" : "PASSED" ) . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><span style=\"cursor: help;\" title=\"" . $smart_powerontime . "\">" . $data["smart_powerontime"] . "</span></td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $data["smart_loadcycle"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $data["purchased"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><span style=\"cursor: help;\" title=\"Warranty: " . $data["warranty"] . " months. Expires: " . $warranty_left . "\">" . $warranty_expire . "</span></td>
				<td style=\"padding: 0 10px 0 10px;\">" . stripslashes(htmlspecialchars($data["comment"])) . "</td>
			</tr>
		";
	}
?>
<h2 style="margin-top: -10px; padding: 0 0 25px 0;">Disk Information</h2>
<table>
	<tr style="border: solid 1px #000000;">
		<td style="padding: 0 10px 0 10px;"><b>Tray</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Path</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Logical Unit Name</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Manufacturer</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Device Model</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Serial Number</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Capacity</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Rotation</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Form Factor</b></td>
		<td style="padding: 0 10px 0 10px;"><b>SMART Status</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Power On Hours</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Load Cycle Count</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Purchased</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Warranty</b></td>
		<td style="padding: 0 10px 0 10px;"><b>Comment</b></td>
	</tr>
	<?php 
		$i=1;
		while($i <= count($print_drives)) {
			print($print_drives[$i]);
			$i++;
		}
		if($print_removed_drives) {
			print("
				<tr>
					<td style=\"padding: 10px 10px 0 10px;\" colspan=\"15\">
						<h3>Devices not found or removed</h3>
					</td>
				</tr>
				<tr style=\"border: solid 1px #000000;\">
					<td style=\"padding: 0 10px 0 10px;\"><b>Delete</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Path</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Logical Unit Name</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Manufacturer</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Device Model</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Serial Number</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Capacity</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Rotation</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Form Factor</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>SMART Status</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Power On Hours</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Load Cycle Count</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Purchased</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Warranty</b></td>
					<td style=\"padding: 0 10px 0 10px;\"><b>Comment</b></td>
				</tr>
				$print_removed_drives
			");
		}
	?>
</table>
