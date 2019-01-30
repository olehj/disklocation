<?php
	$smartlocate_path = "/usr/local/bin";
	
	if($_GET["disklocation"] && $_GET["cmd"] == "start") {
		shell_exec("" . $smartlocate_path . "/smartlocate " . escapeshellarg($_GET["disklocation"] . ""));
		exit;
	}
	else if($_GET["disklocation"] && $_GET["cmd"] == "stop") {
		shell_exec("pkill -f \"smartlocate " . escapeshellarg($_GET["disklocation"] . "\""));
		exit;
	}
	else if($_GET["cmd"] == "killall") {
		shell_exec("pkill -f smartlocate");
		exit;
	}
	
	// Kill all on reload:
	shell_exec("pkill -f smartlocate");
?>
