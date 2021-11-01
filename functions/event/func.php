<?php
function event_receive()
{
	if(isset($_POST['event_create']))
		$message=outline_userop('create', array("event", $_SESSION[login_PREFIX.'Userid']));
	else if(isset($_POST['event_info_change']))
		$message=outline_userop('info_change', array("event", $_GET['id']));
	else if(isset($_POST['event_delete']))
		$message=outline_userop('delete', array("event", $_POST['id']));	
	
	if(isset($message))
		return $message;
	else
		return NULL;
}
?>