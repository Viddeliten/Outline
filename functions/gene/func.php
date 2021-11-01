<?php
function gene_receive()
{
	if(isset($_POST['gene_create']))
		$message=outline_userop('create', array("gene", $_SESSION[login_PREFIX.'Userid']));
	else if(isset($_POST['gene_info_change']))
		$message=outline_userop('info_change', array("gene", $_GET['id']));
	else if(isset($_POST['gene_delete']))
		$message=outline_userop('delete', array("gene", $_POST['id']));	
		
	if(isset($message))
		return $message;
	else
		return NULL;
}
?>