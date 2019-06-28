<?php
	function disklocation_system($type, $operation) {
		$array = array();
		if($type == "backup") {
			if($operation == "list") {
				$file = explode("\n", (shell_exec("ls -1 " . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation_db_v*.sqlite.tar.bz2")));
				for($i=0; $i < count($file); ++$i) {
					$array[$i]["file"] = $file[$i];
					$array[$i]["size"] = filesize($file[$i]);
				}
				if($array[0]["file"]) {
					return $array;
				}
				else {
					return false;
				}
			}
			if($operation == "delete") {
				array_map('unlink', glob("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation_db_v*.sqlite.tar.bz2"));
			}
		}
		
		if($type == "database") {
			if($operation == "list") {
				if(file_exists("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.sqlite")) {
					return filesize("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.sqlite");
				}
				else {
					return false;
				}
			}
			if($operation == "delete") {
				unlink("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/disklocation.sqlite");
			}
		}
		
		if($type == "debug") {
			if($operation == "list") {
				if(file_exists("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/debugging.html")) {
					return filesize("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/debugging.html");
				}
				else {
					return false;
				}
			}
			if($operation == "delete") {
				unlink("" . UNRAID_CONFIG_PATH . "" . DISKLOCATION_PATH . "/debugging.html");
			}
		}
	}
	
	if($_POST["del_backup"]) {
		disklocation_system("backup", "delete");
	}
	if($_POST["del_debug"]) {
		disklocation_system("debug", "delete");
	}
	if($_POST["del_database"]) {
		disklocation_system("database", "delete");
		print("<meta http-equiv=\"refresh\" content=\"0;url=" . DISKLOCATION_URL . "\" />");
		exit;
	}
	
	$list_backup = disklocation_system("backup", "list");
	if($list_backup) {
		$print_list_backup .= "
			<h3>Automatic database upgrade backups</h3><br />
			<table style=\"width: 0;\">
				<tr>
					<td>
						<b>File</b>
					</td>
					<td style=\"padding: 0 0 0 20px;\">
						<b>Size</b>
					</td>
				</tr>
		";
		for($i=0; $i < count($list_backup); ++$i) {
			$print_list_backup .= "
				<tr>
					<td>
						" . $list_backup[$i]["file"] . "
					</td>
					<td style=\"text-align: right; padding: 0 0 0 20px;\">
						" . $list_backup[$i]["size"] . "
					</td>
				</tr>
			";
		}
		$print_list_backup .= "
			</table>
			<form action=\"\" method=\"post\">
				<input type=\"submit\" name=\"del_backup\" value=\"Delete all backup files\" />
			</form>
		";
	}
	
	$list_debug = disklocation_system("debug", "list");
	if($list_debug) {
		$print_list_debug = "
			<h3>Debug file</h3>
			<p>Debug filesize: " . $list_debug . "</p>
			<form action=\"\" method=\"post\">
				<input type=\"submit\" name=\"del_debug\" value=\"Delete debug file\" />
			</form>
		";
	}
	
	$list_database = disklocation_system("database", "list");
	if($list_database) {
		$print_list_database = "
			<h3>Database file</h3>
			<p>Database filesize: " . $list_database . ")</p>
			<form action=\"\" method=\"post\">
				<input type=\"submit\" name=\"del_database\" value=\"Delete the database\" />
			</form>
		";
	}
?>
<link type="text/css" rel="stylesheet" href="<?autov("" . DISKLOCATION_PATH . "/pages/styles/help.css")?>">
<h2>System Files</h2>
<p style="color: red;">
	<b>NB! Operations done on this page will execute without warning or confirmation and cannot be undone after execution!</b>
</p>
<?php echo $print_list_backup ?>
<?php echo $print_list_debug ?>
<?php echo $print_list_database ?>
