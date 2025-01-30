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
	
	if(!empty($disklocation_error)) {
		$i=0;
		print("<h2 style=\"color: #FF0000; font-weight: bold;\">");
		while($i < count($disklocation_error)) {
			print("&middot; ERROR: " . $disklocation_error[$i] . "<br />");
			$i++;
		}
		print("</h2><hr style=\"border: 1px solid #FF0000;\" /><br /><br />");
	}
	
	$print_benchmark = "
		<form action=\"\" method=\"post\">
			<b>Clicking the button will start the benchmark directly.</b>
			Only one record is stored per day, the latest run will be stored.<br />
			This might take a long time to complete, typically 5-15 seconds per drive, per iteration.<br />
			<b>Estimated time of completion with " .$count_installed_devices . " drives is " . round(($count_installed_devices * 7.5 * $bench_iterations) / 60) . " minutes.</b>
			<br />
			<input type='button' " . ( (!file_exists(DISKLOCATION_DEVICES)) ? "disabled=\"disabled\"" : null ) . " value='Start benchmark' onclick='openBox(\"" . BENCHMARK_URL . "?start=1\",\"Benchmark running\",600,800,true,\"loadlist\",\":return\")'>
			
			<blockquote class='inline_help'>
				<ul>
					<li>This will perform a read benchmark via hdparm</li>
					<li>You can also run this from the shell (average is presented):<br />
					<code style=\"white-space: nowrap;\">php -f " . BENCHMARK_FILE . "</code></li>
				</ul>
			</blockquote>
		</form>
	";
	
	
?>
<table><tr><td style="padding: 10px 10px 10px 10px;">
<h2 style="margin-top: -10px; padding: 0 0 25px 0;">Benchmark</h2>

<form action="" method="post">
	<table>
		<tr>
			<td style="vertical-align: top;">
				<p>
					<b>Settings:</b><br />
					<input type="number" required min="1" max="10" step="1" name="bench_iterations" value="<?php print($bench_iterations); ?>" style="margin: 0; width: 20px;" />
					Iterations to run
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" name="bench_median" value="1" <?php if(!empty($bench_median)) echo "checked"; ?> />
					Skip slowest and fastest results
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" name="bench_force" value="1" <?php if(!empty($bench_force)) echo "checked"; ?> />
					Ignore SMART power state
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" name="bench_auto_cron" value="1" <?php if(!empty($bench_auto_cron)) echo "checked"; ?> />
					Run monthly auto benchmark
					&nbsp;&nbsp;&nbsp;
					<input type="submit" name="save_benchmark_settings" value="Save" />
				</p>
				<blockquote class='inline_help'>
					<ol>
						<li>Set the number of cycles that hdparm should execute per drive.</li>
						<li>Choose if you want to use the slowest and the fastest results, or skip them</li>
						<li>Choose to ignore if a drive is in standby or not, enabling this will spin up sleeping drives.</li>
						<li>Run this benchmark monthly via crontab, default is set at 1st day of the month at 05:00. You can disable this and set up your own crontab using this command:<br />
						<code style="white-space: nowrap;">php -f <?php print(BENCHMARK_FILE); ?> auto silent</code></li>
					</ol>
				</blockquote>
			</td>
			<td style="vertical-align: top;">
			
			</td>
		</tr>
	</table>
</form>
<hr />
<?php print($print_benchmark); ?>
</td></tr></table>
