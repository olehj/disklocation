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
	
	if(isset($_GET["download_csv"])) {
		require_once("system.php");
		require_once("array_devices.php");
		
		$benchmark_files = array_values(array_diff(scandir(UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/benchmark/"), array('..', '.')));
		
		for($i=0; $i < count($benchmark_files); ++$i) {
			// get ALL benchmarks
			$benchmark_file[$hash] = UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/benchmark/" . $benchmark_files[$i];
			$benchmark["" . str_replace(".json", "", $benchmark_files[$i]) . ""] = file_exists($benchmark_file[$hash]) ? json_decode(file_get_contents($benchmark_file[$hash]), true) : null ;
			ksort($benchmark["" . str_replace(".json", "", $benchmark_files[$i]) . ""]);
		}
		
		foreach($benchmark as $drive => $data) {
			if(!empty($data)) {
				$arr_length = count($data);
				foreach($data as $date => $speed) {
					$date_array[] = $date;
				}
				$drive_array[] = $drive;
			}
		}
		
		$date_array = array_unique($date_array);
		sort($date_array);
		
		$print_csv[0][0] = " ";
		
		for($i=0; $i < count($date_array); ++$i) {
			$print_csv[0][$i+1] = $date_array[$i];
		}
		for($i=0; $i < count($drive_array); ++$i) {
			$print_csv[$i+1][0] = $drive_array[$i];
		}
		for($i=1; $i < count($print_csv[0])+1; ++$i) {
			for($i2=0; $i2 < count($drive_array); ++$i2) {
				$print_csv[$i2+1][$i] = $benchmark[$drive_array[$i2]][$print_csv[0][$i]];
			}
		}
		
		$rows = count($drive_array)+2;
		$print_csv[$rows][0] = "Disk Location - Benchmark";
		$print_csv[$rows][1] = "" . DISKLOCATION_VERSION . "";
		//print_r($print_csv);
		array_to_csv_download($print_csv, "disklocation-benchmark-" . date("Ymd-His") . ".tsv", "\t");
	}
?>
