<link type="text/css" rel="stylesheet" href="<?autov("" . DISKLOCATION_PATH . "/pages/styles/help.css")?>">
<h2 style="margin-top: -10px; padding: 0 0 10px 0;">Help for Disk Location <?php echo DISKLOCATION_VERSION ?></h2>
<h3>Installation</h3>
<dl>
	<dt>Why does this plugin require smartmontools 7.0+?</dt>
	<dd>During installation, smartmontools 7.0 will be installed. This is required for JSON-output for the smartctl command.</dd>
	
	<dt>Why does this plugin require GIT tools?</dt>
	<dd>During installation the package is cloned from a git repository and archived for later use locally, this simplifies the install updates a bit.</dd>
	
	<dt>What else does it install in the system?</dt>
	<dd>It will install a smartlocate script in /usr/local/bin/, this is needed for the "Locate" function.</dd>
	
	<dt>How is the versioning working?</dt>
	<dd>The digits are as following: the first is the year, second the month, and third the day. Technically an ISO date. Multiple updates at the same day will get a letter behind the date increasing from [a]. First version released was 2019.01.22</dd>
	
	<dt>What's the requirements?</dt>
	<dd>A newer browser supporting HTML5, tested with Chrome-based browsers and Firefox.</dd>
</dl>

<h3>Configuration</h3>
<dl>
	<dt>How to save the configuration of the plugin and the drives?</dt>
	<dd>The "Save" button will store all information entered.</dd>
	
	<dt>Change background colors</dt>
	<dd>Select the color(s) you want, defaults are:
	<ul>
		<li>#eb4f41 "Parity"</li>
		<li>#ef6441 "Data"</li>
		<li>#ff884c "Cache"</li>
		<li>#41b5ef "Unassigned devices"</li>
		<li>#aaaaaa "Empty/available trays"</li>
	</ul>
	</dd>
	
	<dt>Set sizes for trays</dt>
	<dd>This is the HTML/CSS pixel size for a single harddisk tray, default sizes are: 400px longest side, and 70px shortest side.</dd>
			
	<dt>Set grid size</dt>
	<dd>Set columns and rows to simulate the looks of your trays, ex. 4 columns * 6 rows = 24 total trays. However, you can override the total amount for additional drives you might have which you don't want to include in the main setup. The total trays will always scale unless you enter a larger value yourself. This value can be left blank for saving.</dd>
	
	<dt>Set physical tray direction</dt>
	<dd>This is the direction of the tray itself. Is it laying flat/horizontal, or is it vertical?</dd>
	
	<dt>Set physical tray assignment direction</dt>
	<dd>Select how to count the tray, from "top to bottom" or from "left to right"</dd>
	
	<dt>Set temperature unit</dt>
	<dd>Select desired temperature unit, even Kelvin!</dd>
	
	<dt>Set warranty date entry</dt>
	<dd>Select how you want to enter the warranty date: the Unraid way of selecting amount of months, or manual ISO date for specific dates. Both values can be stored, but only one can be visible at a time.</dd>
	
	<dt>SMART execution delay</dt>
	<dd>This is a delay for execution of the next smartctl command in a loop, this might be necessary to be able to read all the SMART data from all the drives. Default value is 200ms, and seems to work very well. If you realize it won't detect all the data you can increase this value, but hardly any point decreasing it.</dd>
	
	<dt>Tray allocations</dt>
	<dd>Select where to assign the drives and the empty trays, be sure to select a unique tray slot number. It will detect failure and none of the new settings will be saved.</dd>
	
	<dt>Purchased and Warranty</dt>
	<dd>For Unraid array drives which already got the date set, this will be detected (and eventually overwrite) by the main configuration. This plugin will not touch that, unless those does not exists in the first place. For unassigned devices, you can enter a date of purchase and warranty.</dd>
	
	<dt>Comment</dt>
	<dd>Enter a comment, like where you bought the drive or anything else you'd like.</dd>
	
	<dt>Visible Information</dt>
	<dd>Select the information you want to display on the "Devices" page. Each row is based upon the layout.</dd>
</dl>

<h3>Operation</h3>
<dl>
	<dt>The tray lights</dt>
	<dd>Hover your cursor above the lights to display a tooltip to see what's going on.</dd>
	
	<dt>"Power On Hours" and "Warranty" hover</dt>
	<dd>Hover over text to get additional information or simpler readout.</dd>
	
	<dt>"Locate" button</dt>
	<dd>The "Locate" button will make your harddisk blink on the LED, this is mainly useful for typical hotswap trays with a LED per tray.</dd>
	
	<dt>"Locate" button does not work</dt>
	<dd>This might not work on all devices, like SSD's. <!--Also check the "Devices" page if the button is really active or not if you started it from the "Configuration" page. The button on the "Configuration" page will not change when pressed, but it will activate it.--></dd>
	
	<dt>LED is blinking continously after using "Locate"</dt>
	<dd>Just enter the plugin from the Unraid settings page and it should automatically shut down the locate script. Else it will run continously until stopped or rebooted.</dd>
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
