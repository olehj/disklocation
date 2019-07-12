<?php
	unset($print_drives);
	
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
	$results = $db->query($sql);	
	//while($i < $total_disks) {
	while($res = $results->fetchArray(1)) {
		array_push($datasql, $res);
		
		$data = $datasql[$i_drive-1];
		
		$tray_assign = ( empty($data["tray"]) ? $i : $data["tray"] );
		
		$sql = "SELECT * FROM settings_group WHERE id = '" . $data["groupid"] . "'";
		$results2 = $db->query($sql);
		
		while($datagroup = $results2->fetchArray(1)) {
			$group_name = $datagroup["group_name"];
		}
		$group_assign = ( empty($group_name) ? $data["groupid"] : $group_name );
		
		$hash = $data["hash"];
		$smart_powerontime = ( empty($data["smart_powerontime"]) ? null : seconds_to_time($data["smart_powerontime"] * 60 * 60) );
		$smart_capacity = ( empty($data["smart_capacity"]) ? null : human_filesize($data["smart_capacity"], 1, true) );
		
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
		
		$print_drives[$i_drive] = "
			<tr style=\"background: #" . $color_array[$data["hash"]] . ";\">
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $group_assign . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $tray_assign . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["device"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["luname"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["model_family"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["model_name"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["smart_serialnumber"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $smart_capacity . "</td>
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
	
	// get removed disks info
	$data = "";
	
	$sql = "SELECT * FROM disks WHERE status = 'r' ORDER BY ID DESC;";
	
	$results = $db->query($sql);
	
	while($data = $results->fetchArray(1)) {
		$hash = $data["hash"];
		$smart_powerontime = ( empty($data["smart_powerontime"]) ? null : seconds_to_time($data["smart_powerontime"] * 60 * 60) );
		
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
			<tr style=\"background: #" . $color_array[$data["hash"]] . ";\">
				<td style=\"padding: 0 10px 0 10px;\">
					<form action=\"" . DISKLOCATION_PATH . "/pages/system.php\" method=\"post\">
						<input type=\"image\" name=\"delete\" src=\"" . DISKLOCATION_PATH . "/icons/delete.png\" title=\"Delete, this will flag the drive hidden in the database.\" />
						<input type=\"image\" name=\"add\" src=\"" . DISKLOCATION_PATH . "/icons/add.png\" title=\"Add, will revert to &quot;not found list&quot; if the drive does not exists.\" />
						<input type=\"hidden\" name=\"hash\" value=\"" . $data["hash"] . "\"  />
					</form>
				</td>
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
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><span style=\"cursor: help;\" title=\"Warranty: " . $data["warranty"] . " months. Expires: " . $warranty_left . "\">" . $warranty_expire . "</span></td>
				<td style=\"padding: 0 10px 0 10px;\">" . stripslashes(htmlspecialchars($data["comment"])) . "</td>
			</tr>
		";
	}
?>
<h2 style="margin-top: -10px; padding: 0 0 25px 0;">Disk Information</h2>
<blockquote class='inline_help'>
	<dt>"Power On Hours" and "Warranty" hover</dt>
	<dd>Hover over text to get additional information or simpler readout.</dd>
	<br />
</blockquote>
<table>
	<tr style="border: solid 1px #000000;">
		<td style="padding: 0 10px 0 10px;"><b>Group</b></td>
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
					<td style=\"padding: 10px 10px 0 10px;\" colspan=\"16\">
						<h3>Devices not found or removed</h3>
					</td>
				</tr>
				<tr style=\"border: solid 1px #000000;\">
					<td style=\"padding: 0 10px 0 10px;\" colspan=\"2\"><b>Operation</b></td>
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
<blockquote class='inline_help'>
	<dt>"Operation"</dt>
	<ul>
		<li>Delete, this will flag the drive hidden in the database.</li>
		<li>Add, will revert to &quot;not found list&quot; if the drive does not exists, but will reappear in the configuration if it really does. Usually it shouldn't be any need for this.</li>
	</ul>
</blockquote>
