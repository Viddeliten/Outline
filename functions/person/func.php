<?php
function person_receive()
{
	if(isset($_POST['person_create']))
		$message=outline_userop('create', array("person", $_SESSION[login_PREFIX.'Userid']));
	else if(isset($_POST['person_info_change']))
		$message=outline_userop('info_change', array("person", $_GET['id']));
	else if(isset($_POST['person_delete']))
		$message=outline_userop('delete', array("person", $_POST['id']));	
		
	if(isset($message))
		return $message;
	else
		return NULL;
}
?>