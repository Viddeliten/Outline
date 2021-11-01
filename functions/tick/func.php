<?php
function tick_receive()
{
	if(isset($_POST['tick_create']))
		$message=outline_userop('tick_create', array($_GET['id'], $_POST['y_pos'], $_POST['x_pos']));
	if(isset($_POST['tick_remove']))
		$message=outline_userop('tick_userop_remove');
	
	if(isset($message))
		return $message;
	else
		return NULL;
}

/* function tick_userop($operation)
{
	if(login_is_logged_in() && $_GET['p']=="story" && isset($_GET['id']) && story_get_user($_GET['id'])===$_SESSION[login_PREFIX.'Userid'])
	{
		//Connecta till databasen
		$conn_index=mysql_connect(db_server,db_username,db_password)
			or die("Database not accessible");
		$database=mysql_select_db(db_name)
			or die("Database not found");
			
		if($operation=='tick_create')
			$r_message=tick_create($_GET['id'], $_POST['y_pos'], $_POST['x_pos']);
		if($operation=='tick_userop_remove')
			$r_message=tick_userop_remove($_GET['id'], $_POST['y_pos'], $_POST['x_pos']);
			
		//Stäng databasen
		mysql_close($conn_index);
		
		return $r_message;
	}
	else
	{
		return "Not logged in or other error.";
	}
}
*/
function tick_create($story_id, $y_pos=NULL, $x_pos=NULL)
{
	if($y_pos===NULL || !is_numeric($y_pos))
	{
		$y_pos=NULL;
		
		$sql="SELECT MAX(y_pos) as y FROM tick WHERE story=".sql_safe($story_id).";";
		echo "<br />DEBUG1238: $sql";
		if($tt=mysql_query($sql))
			if($t=mysql_fetch_array($tt))
				$y_pos=$t['y'];
		if($y_pos!==NULL)
			$y_pos++;
		else
			$y_pos=0;
	}

	if($x_pos===NULL || !is_numeric($x_pos))
	{
		$x_pos=0;
		
		if($y_pos!==NULL)
		{
			$sql="SELECT MAX(x_pos) as x FROM tick WHERE story=".sql_safe($story_id)." AND y_pos=$y_pos;";
			if($tt=mysql_query($sql))
				if($t=mysql_fetch_array($tt))
					$x_pos=$t['x'];
			if($x_pos!==NULL)
				$x_pos++;
			else
				$x_pos=0;
		}
	}
	
	$sql="INSERT INTO tick SET y_pos=$y_pos, x_pos=$x_pos, story=".sql_safe($story_id);
	if(mysql_query($sql))
		return "tick created";
	else
		return "Error 531226";
}
function tick_userop_remove($story_id, $y_pos=NULL, $x_pos=NULL)
{
	if($y_pos===NULL || !is_numeric($y_pos) || $y_pos=="")
	{
		return "Y position undetermined";
	}
	
	if($x_pos=="")
		$x_pos=NULL;
		
	if($x_pos!==NULL && !is_numeric($x_pos))
		return "$x_pos X position?";
		
	$sql="UPDATE tick SET deleted='".date("YmdHis")."' WHERE story=".sql_safe($story_id)." AND y_pos=$y_pos";
	
	if($x_pos!==NULL)
	{
		$sql.=" AND x_pos=$x_pos";
	}
	
	// echo "<br />DEBUG1341: $sql";
	
	if(mysql_query($sql))
		return "tick removed";
	else
		return "Error 1191327";
}

function tick_get($what, $id)
{
	//Connecta till databasen
	$conn_index=mysql_connect(db_server,db_username,db_password)
		or die("Database not accessible");
	$database=mysql_select_db(db_name)
		or die("Database not found");
		
	if($what=='tick_get_ticks')
		$r_message=tick_get_ticks($id);
		
	//Stäng databasen
	mysql_close($conn_index);
	
	return $r_message;
}

function tick_get_ticks($story_id, $row_nr=NULL)
{
	if($row_nr===NULL)
		$sql="SELECT * FROM tick
		WHERE story=".sql_safe($story_id)."
		AND deleted IS NULL
		ORDER BY y_pos ASC, x_pos ASC;";
	else
		$sql="SELECT * FROM tick
		WHERE story=".sql_safe($story_id)."
		AND deleted IS NULL
		AND y_pos=".sql_safe($row_nr)."
		ORDER BY y_pos ASC, x_pos ASC;";
		
	if($tt=mysql_query($sql))
	{
		$ticks=array();
		$y_pos=-1;
		while($t=mysql_fetch_array($tt))
		{
			if($y_pos!==$t['y_pos'])
			{
				if($y_pos>=0)
					$ticks[]=$tick;
				$tick=array();
			}
			$y_pos=$t['y_pos'];
			$tick[]=$t;
		}
		if(isset($tick))
			$ticks[]=$tick;
		return $ticks;
	}
	return NULL;
}

function tick_get_tick($tick_id)
{
	$sql="SELECT * FROM tick
	WHERE id=".sql_safe($tick_id).";";
	if($tt=mysql_query($sql))
	{
		if($t=mysql_fetch_array($tt))
		{
			return $t;
		}
	}
	return NULL;
}

/*
function tick_get_row($tick_id)
{
	$sql="SELECT y_pos FROM tick
	WHERE id=".sql_safe($tick_id).";";
	if($tt=mysql_query($sql))
	{
		if($t=mysql_fetch_array($tt))
		{
			return $t['y_pos'];
		}
	}
	return NULL;
}
function tick_get_story($tick_id)
{
	$sql="SELECT story FROM tick
	WHERE id=".sql_safe($tick_id).";";
	if($tt=mysql_query($sql))
	{
		if($t=mysql_fetch_array($tt))
		{
			return $t['story'];
		}
	}
	return NULL;
} */

function tick_display_add_button($button_text="Add tick", $y_pos=NULL, $x_pos=NULL)
{
	?><form method="post">
		<input type="hidden" name="y_pos" value="<?php echo $y_pos; ?>">
		<input type="hidden" name="x_pos" value="<?php echo $x_pos; ?>">
		<input type="submit" name="tick_create" value="<?php echo $button_text; ?>">
	</form><?php
}
function tick_display_remove_button($button_text="Remove tick", $y_pos=NULL, $x_pos=NULL)
{
	?><form method="post">
		<input type="hidden" name="y_pos" value="<?php echo $y_pos; ?>">
		<input type="hidden" name="x_pos" value="<?php echo $x_pos; ?>">
		<input type="submit" name="tick_remove" value="<?php echo $button_text; ?>">
	</form><?php
}

function tick_update($tick_id, $type, $type_id, $echo_tick=FALSE, $user_id=NULL)
{
	if(tick_user_right($user_id, $tick_id))
	{
		$sql="UPDATE tick SET ".sql_safe($type)."=".sql_safe($type_id)." WHERE id=".sql_safe($tick_id).";";
		mysql_query($sql);
		
		$y=outline_get_info("y_pos", "tick", $tick_id);
		$story_id=outline_get_info("story", "tick", $tick_id);
	
		if($type=="person")
		{
			$sql="UPDATE tick SET 
				person=NULL,
				event=NULL
			WHERE story=".sql_safe($story_id)."
			AND person=".sql_safe($type_id)."
			AND y_pos=".sql_safe($y)."
			AND id!=".sql_safe($tick_id).";";
			mysql_query($sql);
		}
		
		if($echo_tick)
		{
			$tt=tick_get_ticks($story_id, $y);
			template_display_tick_row($tt[0], "li");
		}
		
		return NULL;
	}
	else
		return "Invalid ownership";
}

function tick_user_right($user_id, $tick_id)
{
	if($user_id===NULL)
		$user=$_SESSION[login_PREFIX.'Userid'];
	else
		$user=$user_id;
	
	//Check level?
		
	//Check if user owns tick
	$sql="SELECT story.user FROM tick
	INNER JOIN story ON tick.story=story.id
	WHERE tick.id=".sql_safe($tick_id).";";
	if($uu=mysql_query($sql))
		if($u=mysql_fetch_array($uu))
			if($u['user']==$user)
				return TRUE;
	return FALSE;
}

?>