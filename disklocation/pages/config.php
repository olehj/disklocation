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
			$tray_options = "";
			for($tray_i = 1; $tray_i <= $total_trays; ++$tray_i) {
				if($tray_assign == $tray_i) { $selected="selected"; } else { $selected=""; }
				$tray_options .= "<option value=\"$tray_i\" " . $selected . " style=\"text-align: right;\">$tray_i</option>";
			}
			$print_drives[$tray_assign] = "
				<tr style=\"background: #" . $color_array["empty"] . ";\">
					<td style=\"padding: 0 10px 0 10px; text-align: right;\"><select name=\"empty[]\" dir=\"rtl\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><option value=\"\" style=\"text-align: right;\">--</option>" . $tray_options . "</select></td>
					<td colspan=\"12\"></td>
				</tr>
			";
			$i_empty++;
		}
		else {
			$tray_options = "";
			for($tray_i = 1; $tray_i <= $total_trays; ++$tray_i) {
				if($tray_assign == $tray_i) { $selected="selected"; } else { $selected=""; }
				$tray_options .= "<option value=\"$tray_i\" " . $selected . " style=\"text-align: right;\">$tray_i</option>";
			}
			
			$warr_options = "";
			for($warr_i = 12; $warr_i <= (12*5); $warr_i+=12) {
				if($data["warranty"] == $warr_i) { $selected="selected"; } else { $selected=""; }
				$warr_options .= "<option value=\"$warr_i\" " . $selected . " style=\"text-align: right;\">$warr_i months</option>";
			}
			$print_drives[$tray_assign] = "
				<tr style=\"background: #" . $color_array[$data["luname"]] . ";\">
					<td style=\"padding: 0 10px 0 10px; text-align: right;\"><select name=\"drives[" . $data["luname"] . "]\" dir=\"rtl\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><option value=\"\" style=\"text-align: right;\">--</option>" . $tray_options . "</select></td>
					<td style=\"padding: 0 10px 0 10px; text-align: center;\"><input type=\"button\" class=\"diskLocation\" style=\"transform: none;\" onclick=\"locateStart()\" value=\"Locate\" id=\"" . $data["device"] . "\" name=\"" . $data["device"] . "\" /></td>
					<td style=\"padding: 0 10px 0 10px;\">" . $data["device"] . "</td>
					<td style=\"padding: 0 10px 0 10px;\">" . $data["luname"] . "</td>
					<td style=\"padding: 0 10px 0 10px;\">" . $data["model_family"] . "</td>
					<td style=\"padding: 0 10px 0 10px;\">" . $data["model_name"] . "</td>
					<td style=\"padding: 0 10px 0 10px;\">" . $data["smart_serialnumber"] . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . human_filesize($data["smart_capacity"], 1, true) . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . ( empty($data["smart_rotation"]) ? "SSD" : $data["smart_rotation"] . " rpm" ) . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $data["smart_formfactor"] . "</td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"purchased[" . $data["luname"] . "]\" value=\"" . $data["purchased"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\"><select name=\"warranty[" . $data["luname"] . "]\" style=\"min-width: 0; max-width: 80px; width: 80px;\"><option value=\"\" style=\"text-align: right;\">unknown</option>" . $warr_options . "</select></td>
					<td style=\"padding: 0 10px 0 10px; text-align: right;\"><input type=\"text\" name=\"comment[" . $data["luname"] . "]\" value=\"" . stripslashes(htmlspecialchars($data["comment"])) . "\" style=\"width: 150px;\" /></td>
				</tr>
			";
			$i_drive++;
		}
		$i++;
	}
	
	while($tray_assign < $total_trays) {
		$tray_options = "";
		$tray_assign++;
		for($tray_i = 1; $tray_i <= $total_trays; ++$tray_i) {
			if($tray_assign == $tray_i) { $selected="selected"; } else { $selected=""; }
			$tray_options .= "<option value=\"$tray_i\" ". $selected . " style=\"text-align: right;\">$tray_i</option>";
		}
		$print_drives[$tray_assign] = "
			<tr style=\"background: #" . $color_array["empty"] . ";\">
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><select name=\"empty[]\" dir=\"rtl\" style=\"min-width: 0; max-width: 50px; width: 40px;\">" . $tray_options . "</selected></td>
				<td colspan=\"12\"></td>
			</tr>
		";
	}
	
	// get unassigned disks info
	$data = "";
	
	$sql = "SELECT * FROM disks WHERE status = 'h' ORDER BY ID ASC;";
	
	$results = $db->query($sql);
	
	while($data = $results->fetchArray(1)) {
		$tray_options = "";
			for($tray_i = 1; $tray_i <= $total_trays; ++$tray_i) {
				$tray_options .= "<option value=\"$tray_i\" style=\"text-align: right;\">$tray_i</option>";
		}
		
		$print_add_drives .= "
			<tr style=\"background: #" . $color_array[$data["luname"]] . ";\">
				<td style=\"padding: 0 10px 0 10px;\"><select name=\"drives[" . $data["luname"] . "]\" dir=\"rtl\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><option value=\"\" selected style=\"text-align: right;\">--</option>" . $tray_options . "</select></td>
				<td style=\"padding: 0 10px 0 10px; text-align: center;\"><input type=\"button\" class=\"diskLocation\" style=\"transform: none;\" onclick=\"locateStart()\" value=\"Locate\" id=\"" . $data["device"] . "\" name=\"" . $data["device"] . "\" /></td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["device"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["luname"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["model_family"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["model_name"] . "</td>
				<td style=\"padding: 0 10px 0 10px;\">" . $data["smart_serialnumber"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . human_filesize($data["smart_capacity"], 1, true) . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . ( empty($data["smart_rotation"]) ? "SSD" : $data["smart_rotation"] . " rpm" ) . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\">" . $data["smart_formfactor"] . "</td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><input type=\"date\" name=\"purchased[" . $data["luname"] . "]\" value=\"" . $data["purchased"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" /></td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><select name=\"warranty[" . $data["luname"] . "]\" style=\"min-width: 0; max-width: 80px; width: 80px;\"><option value=\"\" style=\"text-align: right;\">unknown</option>" . $warr_options . "</select></td>
				<td style=\"padding: 0 10px 0 10px; text-align: right;\"><input type=\"text\" name=\"comment[" . $data["luname"] . "]\" value=\"" . stripslashes(htmlspecialchars($data["comment"])) . "\" style=\"width: 150px;\" /></td>
			</tr>
		";
	}
	
	$db->close();
?>
<?php
	if(!empty($disklocation_error)) {
		$i=0;
		print("<p style=\"color: #FF0000; font-weight: bold;\">");
		while($i < count($disklocation_error)) {
			print("&middot;" . $disklocation_error[$i] . "<br />");
			$i++;
		}
		print("</p><hr />");
	}
?>
<form action="" method="post">
	<table style="width: 0;">
		<tr>
			<td style="vertical-align: top;">
				<h2>Disk Tray Layout</h2>
				<div class="grid-container">
					<?php print($disklocation_layout); ?>
				</div>
				<p>
					<b>Change background colors:</b><br />
					<input type="color" required name="bgcolor_unraid" value="#<?php print($bgcolor_unraid); ?>" /> Unraid array<br />
					<input type="color" required name="bgcolor_others" value="#<?php print($bgcolor_others); ?>" /> Unassigned devices<br />
					<input type="color" required name="bgcolor_empty" value="#<?php print($bgcolor_empty); ?>" /> Empty trays
				</p>
				<p>
					<b>Set sizes for trays:</b><br />
					<input type="number" required min="200" max="2000" name="tray_width" value="<?php print($tray_width); ?>" style="width: 50px;" /> px longest side<br />
					<input type="number" required min="70" max="700" name="tray_height" value="<?php print($tray_height); ?>" style="width: 50px;" /> px shortest side
				</p>
					<b>Set grid size:</b><br />
					<input type="number" required min="1" max="255" name="grid_columns" value="<?php print($grid_columns); ?>" style="width: 50px;" /> columns<br />
					<input type="number" required min="1" max="255" name="grid_rows" value="<?php print($grid_rows); ?>" style="width: 50px;" /> rows<br />
					<input type="number" min="<?php print($grid_columns * $grid_rows); ?>" max="255" name="grid_trays" value="<?php print(empty($grid_trays) ? null : $grid_trays ); ?>" style="width: 50px;" /> total trays, override
				</p>
				<p>
					<b>Set physical tray direction:</b><br />
					<input type="radio" name="disk_tray_direction" value="h" <?php if($disk_tray_direction == "h") echo "checked"; ?> />horizontal
					<input type="radio" name="disk_tray_direction" value="v" <?php if($disk_tray_direction == "v") echo "checked"; ?> />vertical
				</p>
				<p>
					<b>Set physical tray assignment direction:</b><br />
					<input type="radio" name="grid_count" value="column" <?php if($grid_count == "column") echo "checked"; ?> />top to bottom
					<input type="radio" name="grid_count" value="row" <?php if($grid_count == "row") echo "checked"; ?>/>left to right
				</p>
				<p>
					<b>SMART execution delay:</b><br />
					<input type="number" required min="0" max="5000" name="smart_exec_delay" value="<?php print($smart_exec_delay); ?>" style="width: 50px;" />ms
				</p>
				<p style="text-align: center;">
					<input type="submit" name="save_settings" value="Save" /><input type="reset" value="Reset" />
				</p>
			</td>
			<td style="vertical-align: top; padding-left: 20px;">
				<h2 style="padding-bottom: 25px;">Allocations</h2>
				<table>
					<tr style="border: solid 1px #000000;">
						<td style="padding: 0 10px 0 10px;"><b>Tray</b></td>
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
					</tr>
					<?php 
						$i=1;
						while($i <= count($print_drives)) {
							print($print_drives[$i]);
							$i++;
						}
						if($print_add_drives) {
							print("
								<tr>
									<td style=\"padding: 10px 10px 0 10px;\" colspan=\"15\">
										<h3>Devices not assigned or added</h3>
									</td>
								</tr>
								<tr style=\"border: solid 1px #000000;\">
									<td style=\"padding: 0 10px 0 10px;\"><b>Tray</b></td>
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
								</tr>
								$print_add_drives
							");
						}
					?>
				</table>
			</td>
		</tr>
	</table>
</form>
<script>
$('.diskLocation').click(function(e) {
	var locateDisk = document.getElementById(this.id);
	
	if(locateDisk.value == "Locate") {
		locateKillAll(locateDisk.className);
		locateStart(locateDisk);
	}
	else {
		locateStop(locateDisk);
		locateKillAll(locateDisk.className);
	}
});
</script>
