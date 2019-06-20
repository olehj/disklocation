<?php
	$i=1;
	$i_empty=1;
	$i_drive=1;
	unset($print_drives);
	
	$dashboard_widget_array = dashboard_toggle("info");
	$dashboard_widget = $dashboard_widget_array["current"];
	$dashboard_widget_pos = $dashboard_widget_array["position"];
	
	if(!count_table_rows($db, "location")) {
		$add_empty_tray_disabled = "disabled";
	}
	
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
					<td style=\"padding: 0 10px 0 10px; text-align: right;\"><select $add_empty_tray_disabled name=\"empty[]\" dir=\"rtl\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><!--<option value=\"\" style=\"text-align: right;\">--</option>-->" . $tray_options . "</select></td>
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
			
			$warr_input = "";
			if($warranty_field == "u") {
				$warr_options = "";
				for($warr_i = 12; $warr_i <= (12*5); $warr_i+=12) {
					if($data["warranty"] == $warr_i) { $selected="selected"; } else { $selected=""; }
					$warr_options .= "<option value=\"$warr_i\" " . $selected . " style=\"text-align: right;\">$warr_i months</option>";
				}
				$warr_input = "<select name=\"warranty[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 80px; width: 80px;\"><option value=\"\" style=\"text-align: right;\">unknown</option>" . $warr_options . "</select>";
			}
			else {
				$warr_input = "<input type=\"date\" name=\"warranty_date[" . $data["hash"] . "]\" value=\"" . $data["warranty_date"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" />";
			}
			
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
			
			$print_drives[$tray_assign] = "
				<tr style=\"background: #" . $color_array[$data["hash"]] . ";\">
					<td style=\"padding: 0 10px 0 10px; text-align: right;\"><select name=\"drives[" . $data["hash"] . "]\" dir=\"rtl\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><option value=\"\" style=\"text-align: right;\">--</option>" . $tray_options . "</select></td>
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
		
		$warr_input = "";
		if($warranty_field == "u") {
			$warr_input = "<select name=\"warranty[" . $data["hash"] . "]\" style=\"min-width: 0; max-width: 80px; width: 80px;\"><option value=\"\" style=\"text-align: right;\">unknown</option>" . $warr_options . "</select>";
		}
		else {
			$warr_input = "<input type=\"date\" name=\"warranty_date[" . $data["hash"] . "]\" value=\"" . $data["warranty_date"] . "\" style=\"min-width: 0; max-width: 130px; width: 130px;\" />";
		}
		
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
		
		$print_add_drives .= "
			<tr style=\"background: #" . $color_array[$data["hash"]] . ";\">
				<td style=\"padding: 0 10px 0 10px;\"><select name=\"drives[" . $data["hash"] . "]\" dir=\"rtl\" style=\"min-width: 0; max-width: 50px; width: 40px;\"><option value=\"\" selected style=\"text-align: right;\">--</option>" . $tray_options . "</select></td>
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
			</tr>
		";
	}
	
	$db->close();
	
	$vi_width = 150;
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
				<blockquote class='inline_help'>
					This shows you an overview of your configured tray layout
				</blockquote>
				<p>
					<b>Change background colors:</b>
				</p>
				<div style="padding-top: 20px;">
					<table>
						<tr>
							<td style="padding: 0;">
								Parity
							</td>
							<td style="padding: 0;">
								Data
							</td>
							<td style="padding: 0;">
								Cache
							</td>
						</tr>
						<tr>
							<td style="padding: 0;">
								<input type="color" required name="bgcolor_parity" value="#<?php print($bgcolor_parity); ?>" />
							</td>
							<td style="padding: 0;">
								<input type="color" required name="bgcolor_unraid" value="#<?php print($bgcolor_unraid); ?>" />
							</td>
							<td style="padding: 0;">
								<input type="color" required name="bgcolor_cache" value="#<?php print($bgcolor_cache); ?>" />
							</td>
						</tr>
						<tr>
							<td style="padding: 0;">
								<input type="color" required name="bgcolor_others" value="#<?php print($bgcolor_others); ?>" />
							</td>
							<td style="padding: 0;" colspan="2">
								Unassigned devices
							</td>
						</tr>
						<tr>
							<td style="padding: 0;">
								<input type="color" required name="bgcolor_empty" value="#<?php print($bgcolor_empty); ?>" />
							</td>
							<td style="padding: 0;" colspan="2">
								Empty trays
							</td>
						</tr>
					</table>
				</div>
				<blockquote class='inline_help'>
					<dt>Select the color(s) you want, defaults are:</dt>
					<ul>
						<li>#eb4f41 "Parity"</li>
						<li>#ef6441 "Data"</li>
						<li>#ff884c "Cache"</li>
						<li>#41b5ef "Unassigned devices"</li>
						<li>#aaaaaa "Empty/available trays"</li>
					</ul>
				</blockquote>
				<p>
					<b>Set sizes for trays:</b><br />
					<input type="number" required min="100" max="2000" name="tray_width" value="<?php print($tray_width); ?>" style="width: 50px;" /> px longest side<br />
					<input type="number" required min="30" max="700" name="tray_height" value="<?php print($tray_height); ?>" style="width: 50px;" /> px shortest side
				</p>
				<blockquote class='inline_help'>
					This is the HTML/CSS pixel size for a single harddisk tray, default sizes are: 400px longest side, and 70px shortest side.
				</blockquote>
				<p>
					<b>Set grid size:</b><br />
					<input type="number" required min="1" max="255" name="grid_columns" value="<?php print($grid_columns); ?>" style="width: 50px;" /> columns<br />
					<input type="number" required min="1" max="255" name="grid_rows" value="<?php print($grid_rows); ?>" style="width: 50px;" /> rows<br />
					<input type="number" min="<?php print($grid_columns * $grid_rows); ?>" max="255" name="grid_trays" value="<?php print(empty($grid_trays) ? null : $grid_trays ); ?>" style="width: 50px;" /> total trays, override
				</p>
				<blockquote class='inline_help'>
					Set columns and rows to simulate the looks of your trays, ex. 4 columns * 6 rows = 24 total trays. However, you can override the total amount for additional drives you might have which you don't want to include in the main setup. The total trays will always scale unless you enter a larger value yourself. This value can be left blank for saving.
				</blockquote>
				<p>
					<b>Set physical tray direction:</b><br />
					<input type="radio" name="disk_tray_direction" value="h" <?php if($disk_tray_direction == "h") echo "checked"; ?> />horizontal
					<input type="radio" name="disk_tray_direction" value="v" <?php if($disk_tray_direction == "v") echo "checked"; ?> />vertical
				</p>
				<blockquote class='inline_help'>
					This is the direction of the tray itself. Is it laying flat/horizontal, or is it vertical?
				</blockquote>
				<p>
					<b>Set physical tray assignment direction:</b><br />
					<input type="radio" name="grid_count" value="column" <?php if($grid_count == "column") echo "checked"; ?> />top to bottom
					<input type="radio" name="grid_count" value="row" <?php if($grid_count == "row") echo "checked"; ?>/>left to right
				</p>
				<blockquote class='inline_help'>
					Select how to count the tray, from "top to bottom" or from "left to right"
				</blockquote>
				<!-- Will use system variable instead configured under "Display Settings"
				<p>
					<b>Set temperature unit:</b><br />
					<input type="radio" name="tempunit" value="C" <?php if($tempunit == "C") echo "checked"; ?> />°C
					<input type="radio" name="tempunit" value="F" <?php if($tempunit == "F") echo "checked"; ?>/>°F
					<input type="radio" name="tempunit" value="K" <?php if($tempunit == "K") echo "checked"; ?>/>K
				</p>
				<blockquote class='inline_help'>
					It's GONE! Use Unraids own "Display Settings" variable instead.
				</blockquote>
				-->
				<p>
					<b>Set warranty date entry:</b><br />
					<input type="radio" name="warranty_field" value="u" <?php if($warranty_field == "u") echo "checked"; ?> />Unraid
					<input type="radio" name="warranty_field" value="m" <?php if($warranty_field == "m") echo "checked"; ?>/>Manual ISO
				</p>
				<blockquote class='inline_help'>
					Select how you want to enter the warranty date: the Unraid way of selecting amount of months, or manual ISO date for specific dates. Both values can be stored, but only one can be visible at a time.
				</blockquote>
				<p>
					<b>Display plugin at Dashboard?</b><br />
					Position: <input type="number" required min="0" max="1000" name="dashboard_widget_pos" value="<?php print($dashboard_widget_pos); ?>" style="width: 50px;" />
					<input type="radio" name="dashboard_widget" value="on" <?php if($dashboard_widget == "on") echo "checked"; ?>/>Yes
					<input type="radio" name="dashboard_widget" value="off" <?php if($dashboard_widget == "off") echo "checked"; ?> />No
				</p>
				<blockquote class='inline_help'>
					Choose if you want to display this plugin in the Unraid Dashboard, "Enable" or "Disable"<br />
					Enter a number in the location box to decide where to put the dashboard widget, this is a bit experimental.
					Enter 0 and it will position itself automatically, usually at the bottom. Enter a number, like 10, and it will stay at the top of the page. 
					If the number you wrote has the same number as another plugin, it will stay above or underneath it, so change the number and try again.
					This feature is rather experimental and the behaviour might be unexpected, there's no real documentation for creating dashboard widgets with current Unraid Dashboard design.
					And the positioning isn't easy to customize by just adding it into the page.
				</blockquote>
				<p>
					<b>SMART execution delay:</b><br />
					<input type="number" required min="0" max="5000" name="smart_exec_delay" value="<?php print($smart_exec_delay); ?>" style="width: 50px;" />ms
				</p>
				<blockquote class='inline_help'>
					This is a delay for execution of the next smartctl command in a loop, this might be necessary to be able to read all the SMART data from all the drives. Default value is 200ms, and seems to work very well. If you realize it won't detect all the data you can increase this value, but hardly any point decreasing it.
				</blockquote>
				<p style="text-align: center;">
					<input type="hidden" name="current_warranty_field" value="<?php echo $warranty_field ?>" />
					<input type="submit" name="save_settings" value="Save" /><input type="reset" value="Reset" />
					<br />
					<input type="submit" name="force_smart_scan" value="Force Scan All" />
				</p>
				<blockquote class='inline_help'>
					<ul>
						<li>"Save" button will store all information entered.</li>
						<li>"Reset" will just revert changes if you changed any values before you saved them, it will not undo the last save.</li>
						<li>"Force Scan All" button will force scan all drives for updated SMART data and move removed disks into the "lost" table under the "Information" tab. This button will and must wake up all drives into a spinning state and does so one by one. It might take a while to complete depending on your configuration.</li>
					</ul>
					You can also run "Force Scan All" from the shell and get direct output which might be useful for debugging:<br />
					<code style="white-space: nowrap;">php -f /usr/local/emhttp/plugins/disklocation/pages/system.php force</code>
				</blockquote>
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
				<blockquote class='inline_help'>
					<dt>Tray allocations</dt>
					<dd>Select where to assign the drives and the empty trays, be sure to select a unique tray slot number. It will detect failure and none of the new settings will be saved.</dd>
					
					<dt>Purchased and Warranty</dt>
					<dd>For Unraid array drives which already got the date set, this will be detected (and eventually overwrite) by the main configuration. This plugin will not touch that, unless if those does not exists in the first place. For unassigned devices, you can enter a date of purchase and warranty.</dd>
					
					<dt>Comment</dt>
					<dd>Enter a comment, like where you bought the drive or anything else you'd like.</dd>
					
					<dt>"Locate" button</dt>
					<dd>The "Locate" button will make your harddisk blink on the LED, this is mainly useful for typical hotswap trays with a LED per tray.</dd>
					
					<dt>"Locate" button does not work</dt>
					<dd>This might not work on all devices, like SSD's. <!--Also check the "Devices" page if the button is really active or not if you started it from the "Configuration" page. The button on the "Configuration" page will not change when pressed, but it will activate it.--></dd>
					
					<dt>LED is blinking continously after using "Locate"</dt>
					<dd>Just enter the plugin from the Unraid settings page and it should automatically shut down the locate script. Else it will run continously until stopped or rebooted.</dd>
				</blockquote>
				<h2 style="padding-bottom: 25px;">Visible Information</h2>
				<table style="width: auto;">
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[tray]" value="1" <?php if($displayinfo["tray"]) echo "checked"; ?> />Tray number
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[leddiskop]" value="1" <?php if($displayinfo["leddiskop"]) echo "checked"; ?> />Disk Operation LED
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[ledsmart]" value="1" <?php if($displayinfo["ledsmart"]) echo "checked"; ?> />SMART Status LED
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[unraidinfo]" value="1" <?php if($displayinfo["unraidinfo"]) echo "checked"; ?> />Unraid info
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[path]" value="1" <?php if($displayinfo["path"]) echo "checked"; ?> />Path
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[devicenode]" value="1" <?php if($displayinfo["devicenode"]) echo "checked"; ?> />Device Node
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[luname]" value="1" <?php if($displayinfo["luname"]) echo "checked"; ?> />Logical Unit Name
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[manufacturer]" value="1" <?php if($displayinfo["manufacturer"]) echo "checked"; ?> />Manufacturer
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[devicemodel]" value="1" <?php if($displayinfo["devicemodel"]) echo "checked"; ?> />Device Model
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[serialnumber]" value="1" <?php if($displayinfo["serialnumber"]) echo "checked"; ?> />Serial Number
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[temperature]" value="1" <?php if($displayinfo["temperature"]) echo "checked"; ?> />Temperature
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[powerontime]" value="1" <?php if($displayinfo["powerontime"]) echo "checked"; ?> />Power On Time
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[loadcyclecount]" value="1" <?php if($displayinfo["loadcyclecount"]) echo "checked"; ?> />Load Cycle Count
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[capacity]" value="1" <?php if($displayinfo["capacity"]) echo "checked"; ?> />Capacity
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[rotation]" value="1" <?php if($displayinfo["rotation"]) echo "checked"; ?> />Rotation
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[formfactor]" value="1" <?php if($displayinfo["formfactor"]) echo "checked"; ?> />Form Factor
						</td>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[warranty]" value="1" <?php if($displayinfo["warranty"]) echo "checked"; ?> />Warranty Left
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;">
							<input type="checkbox" name="displayinfo[comment]" value="1" <?php if($displayinfo["comment"]) echo "checked"; ?> />Comment
						</td>
					</tr>
					<tr>
						<td style="width: <?php echo $vi_width ?>px;" colspan="2">
							<input type="checkbox" name="displayinfo[hideemptycontents]" value="1" <?php if($displayinfo["hideemptycontents"]) echo "checked"; ?> />Hide empty tray contents
						</td>
					</tr>
				</table>
				<blockquote class='inline_help'>
					<dt>Visible Information</dt>
					<dd>Select the information you want to display on the "Devices" page. Each row is based upon the layout.</dd>
				</blockquote>
				
				<blockquote class='inline_help'>
					<h1>Additional help</h1>
					
					<h3>Installation</h3>
					<dl>
						<dt>Why does this plugin require smartmontools 7.0+?</dt>
						<dd>During installation, smartmontools 7.0 will be installed for Unraid 6.6.x (it is included in Unraid 6.7+ as default). This is required for JSON-output for the smartctl command.</dd>
						
						<!--
						<dt>Why does this plugin require GIT tools?</dt>
						<dd>This needs to be installed via the Nerd Pack plugin or other methods. During installation the package is cloned from a git repository and archived for later use locally, this simplifies the install updates a bit.</dd>
						-->
						
						<dt>What else does it install in the system?</dt>
						<dd>It will install a smartlocate script in /usr/local/bin/, this is needed for the "Locate" function. It will also add a script for cronjob in /etc/cron.hourly/</dd>
						
						<dt>How is the versioning working?</dt>
						<dd>The digits are as following: the first is the year, second the month, and third the day. Technically an ISO date. Multiple updates at the same day will get a letter behind the date increasing from [a]. First version released was 2019.01.22</dd>
						
						<dt>What's the requirements?</dt>
						<dd>A newer browser supporting HTML5, tested with Chrome-based browsers and Firefox.</dd>
						
						<dt>It takes a long time to open the page!</dt>
						<dd>The first install will wake up all the drives and force scan SMART data to insert into a database, this might take a while. You can redo this later by clicking the "Force Scan All" button. The automagic cronjob will only scan drives which is already spinning (hopefully).</dd>
					</dl>
					
					<h3>Configuration</h3>
					<dl>
						<dt>Removed devices shows up in the "Configuration" area, it didn't before.</dt>
						<dd>The new system runs in the background and therefore does not run a function to remove these disks, but you can use the "Force Scan All" button to run it. NB! It will wake up all disks!</dd>
					</dl>
					
					<h3>Other</h3>
					<dl>
						<dt>Why did you make this when it already exists something similar?</dt>
						<dd>The other script which inspired me creating this one, does not support drives not directly attached to Unraid. And since I have several attached to a hardware raid card, I found it useful to be able to list all the drives regardless.</dd>
						
						<dt>How and where is the configuration file stored?</dt>
						<dd>The configration is stored in a SQLite database and is located at: /boot/config/plugins/disklocation/disklocation.sqlite</dd>
						
						<dt>I want to reset everything to "Factory defaults", how?</dt>
						<dd>For now, delete the SQLite database manually from the location above. This will be recreated with blank defaults when you enter the plugin page next. Remember, all settings and tray allocations will be deleted for this plugin.</dd>
					</dl>
				</blockquote>
			</td>
		</tr>
	</table>
</form>
<script type="text/javascript" src="<?autov("" . DISKLOCATION_PATH . "/pages/script/locate_script_bottom.js")?>"></script>
