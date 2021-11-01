<?php
session_start();

//Globals
require_once("../../globals/db_info.php");
require_once("../../globals/values.php");
require_once("../../globals/path.php");
//From functions
require_once("../user.php");
require_once("../inlog_functions.php");
require_once("../flattr.php");
require_once("../spam.php");
require_once("../comment/func.php");
//From Feedback
require_once("func.php");
require_once("../../../../functions/string.php");

//Connecta till databasen
$conn=mysql_connect($hostaddress,$inlogname,$password)
	or die("Database not accessible");
$databas=mysql_select_db($database)
	or die("Database not found");

if(isset($_SESSION[PREFIX."User"]) && isset($_SESSION[PREFIX."inloggad"]) && $_SESSION[PREFIX."inloggad"]>=3)
{
	if(isset($_GET['operation']) && isset($_GET['id']))
	{
		if($_GET['operation']=="unresolve")
		{
			feedback_set_unresolved($_GET['id']);
			feedback_status_show($_GET['id'], NULL, NULL, $_SESSION[PREFIX."inloggad"], $_GET['div_id']);
		}
		else if($_GET['operation']=="not_implemented")
		{
			$sql="UPDATE ".PREFIX."feedback SET not_implemented='".date("YmdHis")."', accepted=NULL, resolved=NULL WHERE id=".sql_safe($_GET['id']).";";
			mysql_query($sql);
			feedback_status_show($_GET['id'], NULL, NULL, $_SESSION[PREFIX."inloggad"], $_GET['div_id']);
		}
		else if($_GET['operation']=="feedback_accept")
		{
			feedback_set_accepted($_GET['id']);
			feedback_status_show($_GET['id'], NULL, NULL, $_SESSION[PREFIX."inloggad"], $_GET['div_id']);
		}
		else if($_GET['operation']=="feedback_resolve")
		{
			feedback_set_resolved($_GET['id']);
			feedback_status_show($_GET['id'], NULL, NULL, $_SESSION[PREFIX."inloggad"], $_GET['div_id']);
		}
		else if($_GET['operation']=="feedback_unaccept")
		{
			feedback_set_unaccepted($_GET['id']);
			feedback_status_show($_GET['id'], NULL, NULL, $_SESSION[PREFIX."inloggad"], $_GET['div_id']);
		}
		else if($_GET['operation']=="bugfix")
		{
			$sql="UPDATE ".PREFIX."feedback SET size=1 WHERE id=".sql_safe($_GET['id']).";";
			mysql_query($sql);
			feedback_display_size_buttons($_GET['id'], $_GET['div_id']);
		}
		else if($_GET['operation']=="small_improvement")
		{
			$sql="UPDATE ".PREFIX."feedback SET size=2 WHERE id=".sql_safe($_GET['id']).";";
			mysql_query($sql);
			feedback_display_size_buttons($_GET['id'], $_GET['div_id']);
		}
		else if($_GET['operation']=="big_change")
		{
			$sql="UPDATE ".PREFIX."feedback SET size=3 WHERE id=".sql_safe($_GET['id']).";";
			mysql_query($sql);
			feedback_display_size_buttons($_GET['id'], $_GET['div_id']);
		}
		else if($_GET['operation']=="merge" && isset($_GET['extra']))
		{
			$sql="UPDATE ".PREFIX."feedback SET merged_with=".sql_safe($_GET['extra'])." WHERE id=".sql_safe($_GET['id']).";";
			mysql_query($sql);
			feedback_display_merge_form($_GET['id'], $_GET['div_id']);
		}
		else if($_GET['operation']=="unmerge")
		{
			$sql="UPDATE ".PREFIX."feedback SET merged_with=NULL WHERE id=".sql_safe($_GET['id']).";";
			mysql_query($sql);
			feedback_display_merge_form($_GET['id'], $_GET['div_id']);
		}

	}
}	

if(isset($_GET['operation']) && isset($_GET['id']))
{
	if($_GET['operation']=="expand")
	{
		feedback_display_specific_headline($_GET['id'], TRUE);
	}
	else if($_GET['operation']=="colapse")
	{
		feedback_display_specific_headline($_GET['id'], FALSE);
	}
}

mysql_close($conn);
 ?>