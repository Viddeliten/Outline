<?php
function location_receive()
{
	if(isset($_POST['location_create']))
		$message=outline_userop('create', array("location", $_SESSION[login_PREFIX.'Userid']));
	else if(isset($_POST['location_info_change']))
		$message=outline_userop('info_change', array("location", $_GET['id']));
	else if(isset($_POST['location_delete']))
		$message=outline_userop('delete', array("location", $_POST['id']));	
		
	if(isset($message))
		return $message;
	else
		return NULL;
}

/*
function location_get($what)
{
	//Connecta till databasen
	$conn_index=mysql_connect(db_server,db_username,db_password)
		or die("Database not accessible");
	$database=mysql_select_db(db_name)
		or die("Database not found");
		
	if($what=='location_get_location_list_user')
		$r_message=location_get_location_list_user($_SESSION[login_PREFIX.'Userid']);
		
	//Stäng databasen
	mysql_close($conn_index);
	
	return $r_message;
}


function location_get_location_list_user($id)
{
	$sql="SELECT * FROM location
	WHERE user=".sql_safe($id)."
	AND DELETED IS NULL
	ORDER BY latest_change DESC;";
	
	if($ll=mysql_query($sql))
	{
		$r=array();
		while($l=mysql_fetch_array($ll))
		{
			$r[]=$l;
		}
		return $r;
	}
	return NULL;
}

function location_create($user_id)
{
	$sql="INSERT INTO location SET user=".sql_safe($user_id).";";
	// echo "<br />DEBUG0925: $sql";
	if(mysql_query($sql))
		return "Location created";
	else
		return "Error 490919";
}*/


?>