<?php
	$sql = "SELECT * FROM settings";
	$results = $db->query($sql);
	
	while($data = $results->fetchArray(1)) {
		extract($data);
	}
	
	$displayinfo = json_decode($displayinfo, true);
	
	//dashboard_toggle($dashboard_widget_pos); 
	//cronjob_timer($smart_updates);
	if($smart_updates != cronjob_current()) {
		cronjob_timer($smart_updates);
	}
	
	$color_array = array();
	$color_array["empty"] = $bgcolor_empty;
?>
