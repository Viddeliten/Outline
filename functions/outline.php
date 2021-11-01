<?php

function outline_userop($operation, $parameter=NULL)
{
	// echo "<br />DEBUG0921: $operation, ".print_r($parameter,1);
	//Connecta till databasen
	$conn_index=mysql_connect(db_server,db_username,db_password)
		or die("Database not accessible");
	$database=mysql_select_db(db_name)
		or die("Database not found");

	//Funtioner som kan köras utan att man är inloggad
	if($operation=='template_display_location')
		$r_message=template_display_location($parameter);
	else if($operation=='template_display_story')
		$r_message=template_display_story($parameter);
	else if($operation=='template_display_person')
		$r_message=template_display_person($parameter);
	else if($operation=='template_display_event')
		$r_message=template_display_event($parameter);
	else if($operation=='template_display_gene')
		$r_message=template_display_gene($parameter);
	else if($operation=='get_list')
		$r_message=outline_get_list($parameter[0],$parameter[1]);
	else if($operation=="display_person_settings")
		$r_message=template_display_person_settings($parameter);
	else if(login_is_logged_in()) //För funktioner som bara får göras när man är inloggad
	{
		if($operation=='create')
			$r_message=outline_create($parameter[0], $parameter[1]);
		else if($operation=='info_change')
			$r_message=outline_info_change($parameter[0], $parameter[1]);
		else if($operation=='delete')
			$r_message=outline_delete($parameter[0], $parameter[1]);
		else if($operation=='tick_update')
			$r_message=tick_update($parameter[0], $parameter[1], $parameter[2], $parameter[3]); 
		else if($operation=='update_property')
			$r_message=outline_property_update($parameter[0], $parameter[1], $parameter[2], $parameter[3]); 
		else if($operation=='story_userop_create')
			$r_message=outline_create("story", $parameter);
		else if($operation=='location_create')
			$r_message=outline_create("location", $parameter);
		else if(isset($_GET['p']) && $_GET['p']=="story" && isset($_GET['id']) && outline_get_info("user", "story", $_GET['id'])===$_SESSION[login_PREFIX.'Userid'])
		{
			if($operation=='tick_create')
				$r_message=tick_create($parameter[0],$parameter[1],$parameter[2]);
			else if($operation=='tick_userop_remove')
				$r_message=tick_userop_remove($_GET['id'], $_POST['y_pos'], $_POST['x_pos']);
			else if($operation=='tick_get_ticks')
				$r_message=tick_get_ticks($parameter);
		}
	}
	else
	{
		$r_message="Not logged in.";
	}
	//Stäng databasen
	mysql_close($conn_index);
	
	if(isset($r_message))
		return $r_message;
	else
		return NULL;
}

function outline_create($type, $user_id)
{
	$sql="INSERT INTO ".sql_safe($type)." SET user=".sql_safe($user_id).";";
	echo "<br />DEBUG0925: $sql";
	if(mysql_query($sql))
		return ucfirst($type)." created";
	else
		return "Error 571130";
}

function outline_get_info($info, $type, $type_id)
{
	$sql="SELECT ".sql_safe($info)." FROM ".sql_safe($type)." WHERE id=".sql_safe($type_id);
	if($ss=mysql_query($sql))
	{
		if($s=mysql_fetch_array($ss))
			return $s[$info];
	}
	
	return NULL;
}
function outline_get_info_datatype($info, $table)
{
	// $sql="SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".sql_safe($table)."' AND COLUMN_NAME = '".sql_safe($info)."';";
	$sql="describe ".sql_safe($table)." ".sql_safe($info).";";
	if($tt=mysql_query($sql))
		if($t=mysql_fetch_array($tt))
			return $t['Type'];
	return NULL;
}

function outline_delete($type, $id)
{
	$sql="UPDATE ".sql_safe($type)."
		SET deleted='".date("YmdHis")."'
		WHERE id=".sql_safe($id)."
		AND user=".sql_safe($_SESSION[login_PREFIX.'Userid']).";";
	// echo "<br />DEBUG1551: $sql";
	if(mysql_query($sql))
		return ucfirst($type)." deleted";
	else
		return "Error 931549";
}

//returns a list of things
function outline_get_list($type, $user=NULL)
{
	if($user===NULL)
	{
		if(isset($_SESSION[login_PREFIX.'Userid']))
			$user=$_SESSION[login_PREFIX.'Userid'];
	}
	
	if($user===NULL)
		return NULL;
		
	$sql="SELECT id, name, description FROM ".sql_safe($type)." 
		WHERE user=".sql_safe($user)."
		AND deleted IS NULL;";
	// echo "<br />DEBUG1600: $sql";
	if($ss=mysql_query($sql))
	{
		$rs=array();
		while($s=mysql_fetch_array($ss))
			$rs[]=$s;
	}
	
	if(isset($rs))
		return $rs;
	return NULL;
}

function outline_info_change($type, $id)
{
	if(isset($_POST[$type.'_public']))
		$public=1;
	else
		$public=0;
	$sql="UPDATE ".sql_safe($type)." SET 
		name='".sql_safe($_POST[$type.'_name'])."',
		description='".sql_safe($_POST[$type.'_description'])."',
		public='".$public."'
	WHERE
		user=".sql_safe($_SESSION[login_PREFIX.'Userid'])."
	AND
		id=".sql_safe($id).";";
	// echo "<br />DEBU1122: $sql";
	if(mysql_query($sql))
		return ucfirst($type)." updated";
	else
		return "Error 751122";
}

function outline_property_update($type, $info, $id, $new_value)
{
	require_once("functions/color.php");
	//If user owns it
	if(outline_get_info("user", $type, $id)==$_SESSION[login_PREFIX.'Userid'])
	{
		//check type of info
		$datatype=outline_get_info_datatype($info, $type);
		if(!strcmp(substr($datatype,0,3),"int"))
		{
			
			$new_value=color_string_to_color($new_value);
		}
		//Update info
		$sql="UPDATE ".sql_safe($type)." SET ".sql_safe($info)."='".sql_safe($new_value)."' WHERE id=".sql_safe($id).";";
		if(mysql_query($sql))
		{
			if (strpos($info,'color') !== false) {
				$new_value=strtoupper(color_int_to_colorhex($new_value));
			}
			echo $new_value;
		}
		else
			echo $sql;
	}
}

?>