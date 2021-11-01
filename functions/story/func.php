<?php
function story_receive()
{
	if(isset($_POST['story_create']))
		// $message=outline_userop('story_userop_create');
		$message=outline_userop('create', array("story", $_SESSION[login_PREFIX.'Userid']));
	else if(isset($_POST['story_info_change']))
		// $message=outline_userop('story_userop_info_change', $_GET['id']);
		$message=outline_userop('info_change', array("story", $_GET['id']));	
	else if(isset($_POST['story_delete']))
		$message=outline_userop('delete', array("story", $_POST['id']));	
	
	if(isset($message))
		return $message;
	else
		return NULL;
}

/*

/*	returns a list of users stories	
function story_get_story_list_user($user=NULL)
{
	if($user===NULL)
	{
		if(isset($_SESSION[login_PREFIX.'Userid']))
			$user=$_SESSION[login_PREFIX.'Userid'];
	}
	
	if($user===NULL)
		return NULL;
		
	//Connecta till databasen
	$conn=mysql_connect(db_server,db_username,db_password)
		or die("Database not accessible");
	$database=mysql_select_db(db_name)
		or die("Database not found");
	
	$sql="SELECT id, name, description FROM story WHERE user=".sql_safe($user);
	if($ss=mysql_query($sql))
	{
		$rs=array();
		while($s=mysql_fetch_array($ss))
			$rs[]=$s;
	}
	
	mysql_close($conn);
	
	if(isset($rs))
		return $rs;
	return NULL;
}
function story_get_name($id)
{
	//Connecta till databasen
	$conn=mysql_connect(db_server,db_username,db_password)
		or die("Database not accessible");
	$database=mysql_select_db(db_name)
		or die("Database not found");
	
	$sql="SELECT name FROM story WHERE id=".sql_safe($id);
	if($ss=mysql_query($sql))
	{
		if($s=mysql_fetch_array($ss))
			$rs=$s['name'];
	}
	mysql_close($conn);
	
	if(isset($rs))
		return $rs;
	return NULL;
}
function story_get_description($id)
{
	//Connecta till databasen
	$conn=mysql_connect(db_server,db_username,db_password)
		or die("Database not accessible");
	$database=mysql_select_db(db_name)
		or die("Database not found");
	
	$sql="SELECT description FROM story WHERE id=".sql_safe($id);
	if($ss=mysql_query($sql))
	{
		if($s=mysql_fetch_array($ss))
			$rs=$s['description'];
	}
	mysql_close($conn);
	
	if(isset($rs))
		return $rs;
	return NULL;
}
function story_get_user($id)
{
	//Connecta till databasen
	$conn=mysql_connect(db_server,db_username,db_password)
		or die("Database not accessible");
	$database=mysql_select_db(db_name)
		or die("Database not found");
	
	$sql="SELECT user FROM story WHERE id=".sql_safe($id);
	if($ss=mysql_query($sql))
	{
		if($s=mysql_fetch_array($ss))
			$rs=$s['user'];
	}
	mysql_close($conn);
	
	if(isset($rs))
		return $rs;
	return NULL;
}



function story_userop_create()
{
	$sql="INSERT INTO story SET user=".sql_safe($_SESSION[login_PREFIX.'Userid']);
	if(mysql_query($sql))
		return "Story created";
	else
		return "Error 341018";
}

function story_userop_info_change()
{
	$sql="UPDATE story SET 
		name='".sql_safe($_POST['story_name'])."',
		description='".sql_safe($_POST['story_description'])."'
	WHERE
		user=".sql_safe($_SESSION[login_PREFIX.'Userid'])."
	AND
		id=".sql_safe($_GET['id']).";";
	// echo "<br />DEBUG1120: $sql";
	if(mysql_query($sql))
		return "Story updated";
	else
		return "Error 1581114";
}

function story_display_story_name($id, $h_size=1)
{
	echo "<h$h_size>";
	//Check if it is logged in users story
	if(login_is_logged_in() && story_get_user($id)===$_SESSION[login_PREFIX.'Userid'])
	{
		//In that case display form
		?>
			<input type="text" name="story_name" value="<?php echo story_get_name($id); ?>">
		<?php
	}
	else
		echo story_get_name($id);
	echo "</h$h_size>";
}

function story_display_story_description($id)
{
	//Check if it is logged in users story
	if(login_is_logged_in() && story_get_user($id)===$_SESSION[login_PREFIX.'Userid'])
	{
		//In that case display form
		?>
			<textarea name="story_description"><?php echo story_get_description($id); ?></textarea>
		<?php
	}
	else
		echo story_get_description($id);
}*/

?>