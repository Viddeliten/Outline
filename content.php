<?php

if(isset($_GET['p']))
	$page=$_GET['p'];
else
	$page="story";
	
switch ($page)
{
	case "feedback":
		feedback_recieve();
		feedback_show();
		break;
	case "story":
	case "location":
	case "person":
	case "event":
	case "gene":
		if(isset($_GET['id']))
		{
			if(isset($_GET['settings']))
				outline_userop("template_display_".$page."_settings", $_GET['id']);
			else
				outline_userop("template_display_".$page, $_GET['id']);
		}
		else
		{
			template_display_list(outline_userop("get_list", array($page, NULL)), $page);
			
			// template_display_form_create($page, "Create new ".$page);
			template_display_button("create", $page, "Create new ".$page);
		}
		break;
	default:
		include("error.php");
}

if(isset($_GET['id']))
{
	switch ($page)
	{
		case "person":
			outline_userop("display_person_settings",$_GET['id']);
			break;
	}
}