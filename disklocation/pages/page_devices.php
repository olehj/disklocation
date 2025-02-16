<link type="text/css" rel="stylesheet" href="<?autov("" . DISKLOCATION_PATH . "/pages/styles/signals.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("" . DISKLOCATION_PATH . "/pages/styles/help.css")?>">
<style type="text/css">
	<?php include("/usr/local/emhttp/plugins/disklocation/pages/styles/disk.css.php"); ?>
</style>

<blockquote class='inline_help'>
	<h3><b><?php echo $get_page_info["Title"] ?> ver. <?php echo DISKLOCATION_VERSION ?></b></h3>
	<p>
		Made by <?php echo $get_page_info["Author"] ?>. Copyright &copy;2018-2025. All rights reserved.
	</p>
	<form action="https://www.paypal.com/donate" method="post" target="_top">
		<input type="hidden" name="cmd" value="_donations" />
		<input type="hidden" name="business" value="RDPSXLQQX266E" />
		<input type="hidden" name="no_recurring" value="0" />
		<input type="hidden" name="item_name" value="For development of Disk Location plugin for Unraid" />
		<input type="hidden" name="currency_code" value="EUR" />
		<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
		<img alt="" border="0" src="https://www.paypal.com/en_NO/i/scr/pixel.gif" width="1" height="1" />
	</form>
	<p>
		Bug reports at <a href="https://github.com/olehj/disklocation">GitHub</a>, plugin support at <a href="https://forums.unraid.net/topic/77302-plugin-disk-location/">Unraid Forum</a>
	</p>
	<p>
	</p>
	<hr />
	<dt>The tray lights</dt>
	<dd>Hover your cursor above the lights to display a tooltip to see what's going on. The color and if it flashes will indicate various scenarios. Ex:</dd>
	<ul>
		<li><b>Upper LED</b> indicates the validity set by Unraid or ZFS array and the current activity, like active/idle (green) or standby (green blink).</li>
		<li><b>Middle LED</b> indicates the SMART check, failed (red), warning (yellow), passed (green).</li>
		<li><b>Lower LED</b> indicates the temperature, critical (red), warning (orange) or OK (green).</li>
	</ul>
	<br /><br />
</blockquote>
<blockquote class='inline_help'>
	<h1>Additional help</h1>
	
	<h3>Installation</h3>
	<dl>
		<dt>What else does it install in the system?</dt>
		<dd>It will install a smartlocate script in /usr/local/bin/, this is needed for the "Locate" function.</dd>
		
		<dt>How is the versioning working?</dt>
		<dd>The digits are as following: the first is the year, second the month, and third the day. Technically an ISO date. Multiple updates at the same day will get a letter behind the date increasing from [a]. First version released was 2019.01.22</dd>
		
		<dt>What's the requirements?</dt>
		<dd>A newer browser supporting HTML5, tested with Chrome-based browsers and Firefox.</dd>
	</dl>
	<h3>Other</h3>
	<dl>
		<dt>Why did you make this when it already exists something similar?</dt>
		<dd>The other script which inspired me creating this one, does not support drives not directly attached to Unraid. And since I have several attached to a hardware raid card, I found it useful to be able to list all the drives regardless.</dd>
		
		<dt>Where is the configuration files stored?</dt>
		<dd>The configration are stored in: /boot/config/plugins/disklocation/</dd>
		
		<dt>I want to reset everything to "Factory defaults", how?</dt>
		<dd>
			You can delete the database under "System" tab. This will be recreated with blank defaults when you enter the plugin page next. Remember, all settings and tray allocations will be deleted for this plugin.<br />
			Manual reset can be performed by deleting the config files, run: rm /boot/config/plugins/disklocation/*.json
		</dd>
	</dl>
</blockquote>

<?php
	$check_smart_files = check_smart_files();
	$check_devicepath_conflict = (!empty($devices) && is_array($devices) ? check_devicepath_conflict($devices) : null);
	
	if($db_update == 2) {
		print("-->");
	}
	else {
		if(empty($array_groups) || empty($array_locations)) {
			print("
				<table><tr><td style=\"padding: 10px 10px 10px 10px;\">
				<h1>Please configure Disk Location</h1>
				<ol>
					<li>First go to \"System\", click \"Force SMART+DB\", this might take some time depending on how many drives you have installed.</li>
					<li>Then go to \"Layout\" and add \"Disk Tray Layout\"</li>
					<li>Finally, assign your drives to your newly created layout under \"Tray Allocations\"</li>
				</ol>
				<p>
					More info available by pressing the Unraid \"Help\" icon for additional information per page.
				</p>
				<!--Go to <a href=\"" . DISKLOCATIONCONF_URL . "\">Disk Location Configuration</a>-->
				</td></tr></table>
			");
		}
		else if(!$check_smart_files || $check_devicepath_conflict) {
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "FUNCTION: check_smart_files", (check_smart_files() ? 1 : 0 ));
			$debug_log[] = debug($debug, basename(__FILE__), __LINE__, "FUNCTION: check_devicepath_conflict", check_devicepath_conflict($devices));
			print("<h1 class=\"red\" style=\"text-align: center;\">Go to System and initialize a \"Force SMART" . ( !empty($check_devicepath_conflict) ? "+DB" : null ) . "\"</h1>");
		}
		else {
			print($disklocation_page_out);
		}
	}
?>
