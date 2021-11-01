<?php

define('REL_STR', "((plusones*3)
	+((".date("YmdHis")."-IFNULL(accepted,".date("YmdHis")."))/86400)
	+((".date("YmdHis")."-IFNULL(created,".date("YmdHis")."))/86400)
	-((".date("YmdHis")."-IFNULL(resolved,".date("YmdHis")."))/86400)
	-((".date("YmdHis")."-IFNULL(not_implemented,".date("YmdHis")."))/86400))
	+(4-size)");

function feedback_recieve()
{
	
	if(isset($_POST['postfeedback']) && $_POST['text']!="")
	{
		if(login_check_login_information()<1)
		{
			require_once('functions/recaptchalib.php');
			$resp = recaptcha_check_answer (ReCaptcha_privatekey,
							$_SERVER["REMOTE_ADDR"],
							$_POST["recaptcha_challenge_field"],
							$_POST["recaptcha_response_field"]);
		}

		if (login_check_login_information()<1 && !$resp->is_valid)
		{
			// What happens when the CAPTCHA was entered incorrectly
			die ("The reCAPTCHA wasn't entered correctly. Go back and try it again." .
			 "(reCAPTCHA said: " . $resp->error . ")");
		}
		else
		{
			// Your code here to handle a successful verification
			if(login_check_login_information()>0)
			{
				$user=$_SESSION[SESSION_user_id];
				$IP='NULL';
			}
			else
			{
				$user='NULL';
				$IP=$_SERVER['REMOTE_ADDR'];
			}
				
			$sql="INSERT INTO ".PREFIX."feedback SET
			subject='".sql_safe($_POST['subject'])."',
			text='".sql_safe($_POST['text'])."',
			user=".$user.",
			created='".date("YmdHis")."',
			IP='".sql_safe($IP)."';";
			// echo "<br />DEBUG 2133: $sql";
			mysql_query($sql);
			$id=mysql_insert_id();
			define('MESS', "<p><strong>You have submitted the following message.</strong></p>
			<h3>".sql_safe($_POST['subject'])."</h3>
			<p>".sql_safe($_POST['text'])."</p>
			<p><a href=\"?page=feedback&amp;id=$id\">[Permalink]</a></p>
			<p><strong>Thankyou for your input!</strong></p>");

			if(isset($_POST['nick']))
			{
				$sql="UPDATE ".PREFIX."feedback SET nick='".sql_safe($_POST['nick'])."'
				WHERE id=$id;";
				mysql_query($sql);
			}
			if(isset($_POST['email']))
			{
				$sql="UPDATE ".PREFIX."feedback SET email='".sql_safe($_POST['email'])."'
				WHERE id=$id;";
				mysql_query($sql);
			}
			if(isset($_POST['url']))
			{
				$sql="UPDATE ".PREFIX."feedback SET url='".sql_safe($_POST['url'])."'
				WHERE id=$id;";
				mysql_query($sql);
			}
			if(isset($_POST['flattrID']))
			{
				$sql="UPDATE ".PREFIX."feedback SET flattrID='".sql_safe($_POST['flattrID'])."'
				WHERE id=$id;";
				mysql_query($sql);
			}
		}
	}
		
	if(login_check_login_information()>0)
	{
		if(isset($_POST['feedback_plusone']))
		{
			//echo "<br />DEBUG: plusone on ".$_POST['id'];
			//Kolla så att man inte försöker plussa sina egna
			if($_SESSION[SESSION_user_id]==feedback_get_user($_POST['id']))
			{
				define("ERROR", "You cannot +1 on your own feedback, allthough we are sure it is nice.");
			}
			else
			{
				//echo "<br />DEBUG: ".$_SESSION[SESSION_user_id]."!=".feedback_get_user($_GET['id']);
				//Kolla om denna user redan har plussat denna
				$sql="SELECT * FROM ".PREFIX."plusone WHERE typ='feedback' AND user=".$_SESSION[SESSION_user_id]." AND plus_for=".sql_safe($_POST['id']).";";
				//echo "<br />DEBUG: $sql";
				mysql_query($sql);
				if(mysql_affected_rows()<1)
				{
					$sql="INSERT INTO ".PREFIX."plusone SET typ='feedback', user=".$_SESSION[SESSION_user_id].", plus_for=".sql_safe($_POST['id']).";";
					///echo "<br />DEBUG: $sql";
					mysql_query($sql);
					$sql="UPDATE ".PREFIX."feedback SET resolved=NULL WHERE id=".sql_safe($_POST['id']).";";
					// echo "<br />DEBUG1833: $sql";
					mysql_query($sql);
					add_message("Thankyou for putting emphasis on this suggestion!");
					feedback_count_plusone();
				}
				else
				{
					$sql="UPDATE ".PREFIX."feedback SET resolved=NULL WHERE id=".sql_safe($_POST['id']).";";
//					echo "<br />DEBUG: $sql";
					mysql_query($sql);
					define("MESS", "Thank you. You already put emphasis on this suggestion!");
				}
			}
		}
		
		if(isset($_POST['feedback_accept']))
		{	
			if($_SESSION[SESSION_user_logged_in]>=3)
			{
				$sql="UPDATE ".PREFIX."feedback SET accepted='".date("YmdHis")."', not_implemented=NULL WHERE id=".sql_safe($_POST['id']).";";
				if(mysql_query($sql))
				{
					define('MESS', "Task id ".$_POST['id']." accepted"); // ($sql)");
				}
				else
					define('ERROR', "Something went wron accepting the task");
			}
			else
				define('ERROR', "You are not logged in as an admin");
		}
		
		if(isset($_POST['feedback_unaccept']))
		{	
			if($_SESSION[SESSION_user_logged_in]>=5)
			{
				$sql="UPDATE ".PREFIX."feedback SET accepted=NULL WHERE id=".sql_safe($_POST['id']).";";
				if(mysql_query($sql))
				{
					define('MESS', "Task id ".$_POST['id']." unaccepted"); // ($sql)");
				}
				else
					define('ERROR', "Something went wron unaccepting the task");
			}
			else
				define('ERROR', "You are nowt logged in as an admin");
		}
		
		if(isset($_POST['feedback_resolve']))
		{	
			if($_SESSION[SESSION_user_logged_in]>=5)
			{
				$sql="UPDATE ".PREFIX."feedback SET resolved='".date("YmdHis")."' WHERE id=".sql_safe($_POST['id']).";";
				if(mysql_query($sql))
				{
					$sql="DELETE FROM ".PREFIX."plusone WHERE typ='feedback' AND plus_for=".sql_safe($_POST['id']).";";
					// echo "<br />DEBUG1320: $sql";
					if(mysql_query($sql))
					{
						feedback_count_plusone();
						// echo "<br />DEBUG1309: version_add_to_upcomping_version(".$_POST['id'].", 'feedback');";
						version_add_to_upcomping_version($_POST['id'], 'feedback');
						add_message("Task id ".$_POST['id']." resolved "); // ($sql)");
					}
				}
				else
					define('ERROR', "Something went wrong resolving the task");
			}
			else
				define('ERROR', "You are newt logged in as an admin");
		}
		
		if(isset($_POST['feedback_size_change']))
		{
			$id=$_POST['id'];
			if($_SESSION[PREFIX."User"]==feedback_get_user($id) || $_SESSION[PREFIX."inloggad"]>=3)
			{
				if($_POST['feedback_size_change']=="bugfix")
				{
					$size=1;
				}
				else if($_POST['feedback_size_change']=="small improvement")
				{
					$size=2;
				}
				else if($_POST['feedback_size_change']=="Big change")
				{
					$size=3;
				}
				if(isset($size))
				{
					$sql="UPDATE ".PREFIX."feedback SET size=".$size." WHERE id=".sql_safe($_POST['id']).";";
					// echo "<br /><br /><br /><br />".$sql;
					mysql_query($sql);
				}
			}
		}
	}
}

function feedback_show()
{
	if(isset($_GET['id']))
	{
		//Om vi ska visa en specifik feedback, så gör vi det här.
		$ff=feedback_get_list_specific($_GET['id']);
		feedback_list_print($ff);
		//Visa inmatningsformulär
		echo "<h2>New feedback</h2>";
		feedback_form_show();
	}
	else
	{
		echo "<h1>Feedback</h1>
		<p>Please suggest improvements, bufixes and ideas!</p>";

		//Visa inmatningsformulär
		feedback_form_show();
		
		//Visa sökformulär
		feedback_search_show();
				
		if(isset($_GET['search']))
		{
			//Visa sökresultat
			echo "<h2>Search results</h2>";
			$ff=feedback_search($_GET['search'], 0, 10);
			feedback_list_print($ff);
		}
		else
		{
			//Visa accepterade
			echo "<h2>Ongoing</h2>";
			feedback_display_accepted(3);
			//Visa några okategoriserade SOM länkar! Bara rubriker!
			feedback_display_list(0, 5, "Uncategorized", 2);
			//Visa några bugfixar SOM länkar! Bara rubriker!
			feedback_display_list(1, 5, "Reported bugs", 2);
			//Visa några små SOM länkar! Bara rubriker!
			feedback_display_list(2, 5, "Small improvements", 2);
			//Visa några bugfixar SOM länkar! Bara rubriker!
			feedback_display_list(3, 5, "Big changes", 2);
			//Visa några lösta
			feedback_display_list_resolved(10, "Resolved", 2);
			feedback_display_list_not_implemented(5, "Will not be done", 2);
		}
	}
}

function feedback_show_old()
{

	// if(defined('MESS'))
		// echo "<div class=\"message_box1\">".MESS."</div>";
	// if(defined('ERROR'))
		// echo "<div class=\"message_box error1\">".ERROR."</div>";

	if(isset($_GET['id']))
	{
		//Om vi ska visa en specifik feedback, så gör vi det här.
		$ff=feedback_get_list_specific($_GET['id']);
		feedback_list_print($ff);
		//Visa inmatningsformulär
		echo "<h2>New feedback</h2>";
		feedback_form_show();
	}
	else
	{
		echo "<h2>Feedback</h2>
		<p>Help us make the game even better! If you have noticed something on the site that you would prefer in another way, or maybe something that did not make sence to you at all, then this is where you tell us! </p>
		<p>Just write a suggestion or opinion in the box below and click the \"Tell us\"-button.</p>
		<p>If you agree with what someone else said, please click \"+1\" to tell us that there is one more person that thinks so. These suggestions will get a higher priority.</p>";

		//Visa inmatningsformulär
		feedback_form_show();
		
		//Visa sökformulär
		feedback_search_show();
				
		if(isset($_GET['search']))
		{
			//Visa sökresultat
			echo "<h2>Search results</h2>";
			$ff=feedback_search($_GET['search'], 0, 10);
			feedback_list_print($ff);
		}
		else if(!isset($_GET['relp']) && !isset($_GET['resp']))
		{
			//Visa de mest "relevanta" feedbackarna
			echo "<h2>Admin's next tasks</h2><p>This is what will be done next. This order may change if you click \"+1\" on other feedbacks!</p>";
			//Länkar för att navigera
			echo "<p><a href=\"?page=feedback&amp;relp=1\">[ More ]</a></p>";
			$ff=feedback_get_list_relevant(0,5);
			feedback_list_print($ff);
			//Länkar för att navigera
			echo "<p><a href=\"?page=feedback&amp;relp=1\">[ More ]</a></p>";
			
			//Visa random
			echo "<h2>Some random ones</h2>";
			$ff=feedback_get_list_random(3,0);
			feedback_list_print($ff);

			//Visa resolved
			echo "<h2>last resolved</h2>";
			//Länkar för att navigera
			echo "<p class=\"message_box\"><a href=\"?page=feedback&amp;resp=1\">More</a></p>";
			$ff=feedback_get_list_resolved(0,3);
			feedback_list_print($ff);
			//Länkar för att navigera
			echo "<p class=\"message_box\"><a href=\"?page=feedback&amp;resp=1\">More</a></p>";

			//Visa resolved
			echo "<h2>random resolved</h2>";
			$ff=feedback_get_list_random(5,1);
			feedback_list_print($ff);
		}
		else if(isset($_GET['relp']))
		{
			//Visa nästa sida "relevanta"
			echo "<h2>Admin's tasks (".(($_GET['relp']*5)+1)."-".(($_GET['relp']*5)+6).")</h2>";
			//Länkar för att navigera
			if($_GET['relp']>0)
				echo "<p><a href=\"?page=feedback&amp;relp=".($_GET['relp']-1)."\">Previous</a>  |  <a href=\"?page=feedback&amp;relp=".($_GET['relp']+1)."\">Next</a></p>";
			else
				echo "<p><a href=\"?page=feedback&amp;relp=".($_GET['relp']+1)."\">Next</a></p>";
			$ff=feedback_get_list_relevant($_GET['relp']*5,5);
			feedback_list_print($ff);
			//Länkar för att navigera
			if($_GET['relp']>0)
				echo "<p><a href=\"?page=feedback&amp;relp=".($_GET['relp']-1)."\">Previous</a>  |  <a href=\"?page=feedback&amp;relp=".($_GET['relp']+1)."\">Next</a></p>";
			else
				echo "<p><a href=\"?page=feedback&amp;relp=".($_GET['relp']+1)."\">Next</a></p>";
		}
		else if(isset($_GET['resp']))
		{
			echo "<h2>resolved (".(($_GET['resp']*3)+1)."-".(($_GET['resp']*3)+4).")</h2>";
			//Länkar för att navigera
			if($_GET['resp']>0)
				echo "<p class=\"message_box\"><a href=\"?page=feedback&amp;relp=".($_GET['resp']-1)."\">Previous</a>  |  <a href=\"?page=feedback&amp;resp=".($_GET['resp']+1)."\">Next</a></p>";
			else
				echo "<p class=\"message_box\"><a href=\"?page=feedback&amp;resp=".($_GET['resp']+1)."\">Next</a></p>";
			$ff=feedback_get_list_resolved($_GET['resp']*3,3);
			feedback_list_print($ff);
			//Länkar för att navigera
			if($_GET['resp']>0)
				echo "<p class=\"message_box\"><a href=\"?page=feedback&amp;relp=".($_GET['resp']-1)."\">Previous</a>  |  <a href=\"?page=feedback&amp;resp=".($_GET['resp']+1)."\">Next</a></p>";
			else
				echo "<p class=\"message_box\"><a href=\"?page=feedback&amp;resp=".($_GET['resp']+1)."\">Next</a></p>";
		}
	}
}

function feedback_search_show()
{
	?>
	<h3>Search existing feedbacks</h3>
	<form action="?page=feedback">
		<input type="hidden" name="page" value="feedback">
		<input type="text" name="search" value="<?php if(isset($_GET['search'])) echo $_GET['search']; ?>">
		<input type="submit" value="Search">
	</form>
	<?php
}

function feedback_form_show()
{
	echo "<h3>Add feedback</h3>
	<form method=\"post\" class=\"form-horizontal\">
		";
	if(login_check_login_information()<1)
	{
		//Man kanske vill ange namn, e-post, hemsida och Flattr-id om man inte Ã¤r inloggad
		echo "<div class=\"form-group\"><label for=\"nick\" class=\"col-sm-2\">Name:</label> <input type=\"text\" name=\"nick\" class=\"col-sm-10\"></div>";
		echo "<div class=\"form-group\"><label for=\"email\" class=\"col-sm-2\">Email:</label> <input type=\"text\" name=\"email\" class=\"col-sm-10\"></div>";
		echo "<div class=\"form-group\"><label for=\"url\" class=\"col-sm-2\">Website:</label> <input type=\"text\" name=\"url\" class=\"col-sm-10\"></div>";
		echo "<div class=\"form-group\"><label for=\"flattrID\" class=\"col-sm-2\">Flattr ID:</label> <input type=\"text\" name=\"flattrID\" class=\"col-sm-10\"></div>";
	}
	echo "<div class=\"form-group\"><label for=\"subject\" class=\"col-sm-2\">Subject:</label> <input type=\"text\" name=\"subject\" class=\"col-sm-10\"></div>";
	echo "<div class=\"form-group\"><label for=\"text\" class=\"col-sm-2\">Your Feedback:</label><textarea name=\"text\" class=\"col-sm-10\"></textarea></div>";
	//Om man inte Ã¤r inloggad mÃ¥ste man ange captcha
	if(login_check_login_information()<1)
	{
		require_once('functions/recaptchalib.php');
		echo recaptcha_get_html(ReCaptcha_publickey);
		echo "<p>Log in to get rid of the need of captchas...</p>";
	}
	echo "<div class=\"form-group\"><input type=\"submit\" name=\"postfeedback\" value=\"Tell us!\" class=\"col-sm-2\"></div>
	</form>";
}

function feedback_search($search_str, $from, $to)
{
	//hämtar sökresultat
	$str="%".sql_safe(str_replace(" ","%",$search_str))."%";
	$sql="SELECT  id, user, resolved, accepted, created, text, subject, plusones, nick, email, url, flattrID,
	".REL_STR." as rel
	FROM ".PREFIX."feedback
	WHERE (`text` LIKE '%$str%'	OR `subject` LIKE '%$str%')
	AND is_spam<1
	AND merged_with IS NULL
	ORDER BY rel DESC
	LIMIT ".sql_safe($from).",".sql_safe($to).";";
	//echo "<br />DEBUG: $sql";
	
	return mysql_query($sql);
}

function feedback_get_list_resolved($from, $to)
{
	//Visar de 20 mest "upptummade" feedback-texterna
	$sql="SELECT  id, user, resolved, accepted, not_implemented, created, subject, text, subject, plusones, nick, email, url, flattrID
	FROM ".PREFIX."feedback
	WHERE resolved IS NOT NULL
	AND is_spam<1
	AND merged_with IS NULL
	ORDER BY resolved DESC
	LIMIT ".sql_safe($from).",".sql_safe($to).";";

	//echo "<br />DEBUG: $sql";
	
	return mysql_query($sql);
}

function feedback_get_list_relevant($from, $to)
{
	//Formel= plusones + dagar sedan accepterad
	//ta inte med resolvade
	//Visar de 20 mest "upptummade" feedback-texterna
	$sql="SELECT *,
	".REL_STR." as rel
	FROM ".PREFIX."feedback
	WHERE resolved IS NULL
	AND is_spam<1
	AND merged_with IS NULL
	AND not_implemented IS NULL
	ORDER BY rel DESC, plusones DESC, created ASC
	LIMIT ".sql_safe($from).",".sql_safe($to).";";
	// echo "<br />DEBUG: $sql";
	
	return mysql_query($sql);
}

function feedback_get_list_specific($id)
{
	//Formel= plusones + dagar sedan accepterad
	//ta inte med resolvade
	//Visar de 20 mest "upptummade" feedback-texterna
	$sql="SELECT id, user, resolved, accepted, not_implemented, created, text, subject, plusones, nick, email, url, flattrID
	FROM ".PREFIX."feedback
	WHERE id=".sql_safe($id).";";
	//echo "<br />DEBUG: $sql";
	
	return mysql_query($sql);
}

function feedback_get_list_random($nr, $resolved)
{
	//Visar 20 random feedback-texter
	if($resolved==0)
		$sql="SELECT *
		FROM ".PREFIX."feedback
		WHERE resolved IS NULL
		AND is_spam<1
		ORDER BY RAND()
		LIMIT 0,".sql_safe($nr).";";
	if($resolved==1)
		$sql="SELECT *
		FROM ".PREFIX."feedback
		WHERE resolved IS NOT NULL
		AND is_spam<1
		ORDER BY RAND()
		LIMIT 0,".sql_safe($nr).";";
	if($resolved==2)
		$sql="SELECT *
		FROM ".PREFIX."feedback
		WHERE is_spam<1
		ORDER BY RAND()
		LIMIT 0,".sql_safe($nr).";";
	//echo "<br />DEBUG: $sql";
	
	return mysql_query($sql);
}

function feedback_get_user($id)
{
	$sql="SELECT user
	FROM ".PREFIX."feedback
	WHERE id=".sql_safe($id).";";
	if($ff=mysql_query($sql))
	{
		if($f=mysql_fetch_array($ff))
		{
			return $f['user'];
		}
	}
	return NULL;
}
function feedback_get_size($id)
{
	$sql="SELECT size
	FROM ".PREFIX."feedback
	WHERE id=".sql_safe($id).";";
	// echo $sql;
	if($ff=mysql_query($sql))
	{
		if($f=mysql_fetch_array($ff))
		{
			return $f['size'];
		}
		
	}
	return NULL;
}
function feedback_get_is_accepted($id)
{
	$sql="SELECT accepted
	FROM ".PREFIX."feedback
	WHERE id=".sql_safe($id).";";
	// echo $sql;
	if($ff=mysql_query($sql))
	{
		if($f=mysql_fetch_array($ff))
		{
			return $f['accepted'];
		}
		
	}
	return NULL;
}

function feedback_get_is_not_implemented($id)
{
	$sql="SELECT not_implemented
	FROM ".PREFIX."feedback
	WHERE id=".sql_safe($id).";";
	// echo $sql;
	if($ff=mysql_query($sql))
	{
		if($f=mysql_fetch_array($ff))
		{
			return $f['not_implemented'];
		}
		
	}
	return NULL;
}
function feedback_get_is_resolved($id)
{
	$sql="SELECT resolved
	FROM ".PREFIX."feedback
	WHERE id=".sql_safe($id).";";
	// echo $sql;
	if($ff=mysql_query($sql))
	{
		if($f=mysql_fetch_array($ff))
		{
			return $f['resolved'];
		}
		
	}
	return NULL;
}

function feedback_list_print($data)
{
	$inloggad=login_check_login_information();
	
	while($d=mysql_fetch_array($data)) 
	{
		$postedby=user_get_name($d['user']);
		if($d['not_implemented']!=NULL)
		{
			//accepterat
			echo "<div class=\"feedback feedback_not_implemented\">";
			// echo "<div class=\"comment\">";
		}
		else if($d['resolved']!=NULL)
		{
			//färdigt
			echo "<div class=\"feedback feedback_resolved\">";
		}
		else if($d['accepted']!=NULL)
		{
			//accepterat
			echo "<div class=\"feedback feedback_accepted\">";
			// echo "<div class=\"comment\">";
		}
		else
		{
			//"Ny"
			echo "<div class=\"feedback\">";
			// echo "<div class=\"comment\">";
		}

			echo "<div class=\"author\">";
				//Info om vem som la upp denna
				if($d['user']!=NULL)
					echo "Posted by <a href=\"?page=user&amp;user=".$d['user']."\">".$postedby."</a> at <a href=\"?page=feedback&amp;id=".$d['id']."\">".$d['created']."</a>";
				else if($d['nick']!=NULL)
				{
					if($d['url']!=NULL)
						echo "Posted by <a href=\"".$d['url']."\">".$d['nick']."</a> at <a href=\"?page=feedback&amp;id=".$d['id']."\">".$d['created']."</a>";
					else
						echo "Posted by ".$d['nick']." at <a href=\"?page=feedback&id=".$d['id']."\">".$d['created']."</a>";
				}
				else
					echo "Posted at <a href=\"?page=feedback&amp;id=".$d['id']."\">".$d['created']."</a>";
			echo "</div>";	

			if($d['subject']!="")
				$headline=$d['subject'];
			else
				$headline="Feedback #".$d['id'];
			echo "<h1><a href=\"".feedback_get_url($d['id'])."\">".$headline."</a></h1>";
				
			//Visa själva Feedbacken!
			feedback_display_body($d['id']);
		echo "</div>";					
	}
}

function feedback_status_show($id, $accepted=NULL, $resolved=NULL, $inloggad=NULL, $div_id)
{
	echo "<div id=\"".$div_id."\">";
	if($accepted==NULL)
		$accepted=feedback_get_is_accepted($id);
	if($resolved==NULL)
		$resolved=feedback_get_is_resolved($id);
	if($inloggad==NULL)
		$inloggad=login_check_login_information();
	
	$not_implemented=feedback_get_is_not_implemented($id);
		
	echo "<form method=\"post\">";
	echo "<input type=\"hidden\" name=\"id\" value=\"".$id."\">";
	//Skriv först ut status.
	if($not_implemented!=NULL)
		echo "[marked not implemented ".date("y-m-d",strtotime($not_implemented))."]";
	else if($resolved!=NULL)
		echo "[resolved ".date("y-m-d",strtotime($resolved))."]";
	else if($accepted!=NULL)
		echo "[accepted ".date("y-m-d",strtotime($accepted))."]";
	//Visa admin-knappar
	if($inloggad>=3) //Man behöver inte vara superadmin för att göra bedömning om saker ska göras.
	{
		//Button for unresolve
		if($resolved!=NULL)
			echo "<button id=\"unresolve_".$id."\" onclick=\"feedback_operation('unresolve',".$id.", '".$div_id."'); return false;\">Unresolve</button>";
		//acceptknapp
		if($accepted==NULL && $resolved==NULL)
			echo "<button id=\"feedback_accept_".$id."\" onclick=\"feedback_operation('feedback_accept',".$id.", '".$div_id."'); return false;\">Accept this task</button>";
		if($inloggad>=5) //...men för att bestämma att saker inte ska göras, eller att de är klara
		{
			if($resolved==NULL)
			{
				echo "<button id=\"feedback_resolve_".$id."\" onclick=\"feedback_operation('feedback_resolve',".$id.", '".$div_id."'); return false;\">Mark as done</button>";
				if($accepted!=NULL)
					echo "<button id=\"feedback_unaccept_".$id."\" onclick=\"feedback_operation('feedback_unaccept',".$id.", '".$div_id."'); return false;\">Unaccept</button>";
				if($not_implemented==NULL)
					echo "<button id=\"not_implemented_".$id."\" onclick=\"feedback_operation('not_implemented',".$id.", '".$div_id."'); return false;\">Will not be implemented</button>";
			}
		}
		echo "</form>";
	}
	echo "</div>";
}
function feedback_display_size_buttons($id, $div_id="")
{
	if($div_id=="")
		$div_id="feedback_size_buttons_".$id;
	echo "<div id=\"".$div_id."\">";
	if(isset($_SESSION[PREFIX."User"]))
	{
		if($_SESSION[PREFIX."User"]==feedback_get_user($id) || $_SESSION[PREFIX."inloggad"]>=3)
		{
			$size=feedback_get_size($id);
			if($size==1)
				echo "<strong>[bugfix]</strong> ";
			else
				echo "<input type=\"submit\" id=\"bug_".$id."\" 
					onclick=\"feedback_operation('bugfix',".$id.", '".$div_id."'); return false;\"
					touchstart=\"feedback_operation('bugfix',".$id.", '".$div_id."'); return false;\"
				value=\"bugfix\">";
			if($size==2)
				echo "<strong>[small improvement]</strong> ";
			else
				echo "<button id=\"bug_".$id."\" onclick=\"feedback_operation('small_improvement',".$id.", '".$div_id."'); return false;\">small improvement</button>";
			if($size==3)
				echo "<strong>[Big change]</strong> ";
			else
				echo "<button id=\"bug_".$id."\" onclick=\"feedback_operation('big_change',".$id.", '".$div_id."'); return false;\">Big change</button>";
		}
	}
	echo "</div>";
}
//räkna alla flaggor 
function feedback_count_plusone()
{
	//Man får ju börja med att sätta allt till noll..
	mysql_query("UPDATE ".PREFIX."feedback SET plusones=0;");
	
	$sql="SELECT ".PREFIX."plusone.plus_for,
	 count(".PREFIX."plusone.id) as plus
	 FROM ".PREFIX."plusone
	 WHERE typ='feedback'
	 GROUP BY ".PREFIX."plusone.plus_for";
	// echo "<br />DEBUG2309: $sql";
	if($ff=mysql_query($sql))
	{
		while($f=mysql_fetch_array($ff))
		{
			$id_to_add=$f['plus_for'];
			mysql_query("UPDATE ".PREFIX."feedback SET plusones=".$f['plus']." WHERE id=".$id_to_add.";");
			
			//Kolla om denna har föräldrar för isf ska det sättas på huvudföräldern också.
			$id_to_add=NULL;
			do
			{
				$parent_done=1;
				$sql="SELECT merged_with FROM ".PREFIX."feedback WHERE id=".$id_to_add.";";
				if($pp=mysql_query($sql))
				{
					if($p=mysql_fetch_array($pp))
					{
						$parent_done=1;
						$id_to_add=$p['merged_with'];
					}
				}
			}while(!$parent_done);
			if($id_to_add!==NULL)
				mysql_query("UPDATE ".PREFIX."feedback SET plusones=plusones+".$f['plus']." WHERE id=".$id_to_add.";");
		}
	}
}

function feedback_get_text($id)
{
	$sql="SELECT text
	FROM ".PREFIX."feedback
	WHERE id=".sql_safe($id).";";
	//echo "<br />DEBUG: $sql";
	if($ff=mysql_query($sql))
	{
		if($f=mysql_fetch_array($ff))
		{
			return $f['text'];
		}
		else
			return NULL;
	}
	else
		return NULL;
}

function feedback_show_latest_short($antal=3, $length=150, $headline_size=2)
{
	// id 	subject 	text 	user 	nick 	email 	url 	flattrID 	created 	plusones 	comments 	accepted Admin har tänkt att detta ska ske	resolved Admin tycker att detta är 
	$sql="SELECT id, subject, user, nick, email, url, flattrID, created, SUBSTRING(`text`, 1, ".sql_safe( $length).") AS texten 
	FROM ".PREFIX."feedback 
	WHERE is_spam<1
	ORDER BY created DESC 
	LIMIT 0,".sql_safe($antal).";";
	// echo "<br />DEBUG1323: $sql";
	if($ff=mysql_query($sql)) //Hämta bara de senaste
	{
		echo "<ul class=\"wdgtlist feedbacks\">";
		$first=1;
		while($f = mysql_fetch_array($ff))
		{
			$link=SITE_URL."?page=feedback&amp;id=".$f['id'];
			
			if($first)
			{
				echo "<li class=\"first\">";
				$first=0;
			}
			else
			{
				echo "<li>";
			}
			echo "<h".$headline_size."><a href=\"$link\">".$f['subject']."</a></h".$headline_size.">";

			echo "<div class=\"comment_head\">";
				//Skriv ut info om när kommentaren skrevs och av vem
				if($f['user']!=NULL)
				{
					//Kolla om vi har en avatar
					$sql="SELECT img_thumb FROM ".PREFIX."userimage WHERE user='".sql_safe($f['user'])."';";
					if($ii=mysql_query($sql))
					{
						if($im=mysql_fetch_array($ii))
						{	
							if($im['img_thumb']!=NULL)
								echo "<div class=\"left_avatar left\"><img src=\"".USER_IMG_URL.$im['img_thumb']."\" /></div>" ;
						}
					}
						
					if(!isset($im) || $im['img_thumb']==NULL)
					{
						echo "<div class=\"left_avatar\"><img src=\"http://www.gravatar.com/avatar/".md5( strtolower( trim( user_get_email($f['user']) ) ) )."?s=60\" /></div>" ;
					}
					echo "<div class=\"date\">Posted by <a href=\"?page=user&amp;user=".$f['user']."\"><strong>".user_get_name($f['user'])."</strong></a> at ";
				}
				else if($f['nick']!=NULL)
				{
					//Kolla om vi har en gravatar
					if($f['email']!=NULL)
					{
						echo "<img class=\"left_avatar\"  src=\"http://www.gravatar.com/avatar/".md5( strtolower( trim( $f['email'] ) ) )."?s=60\" />" ;
					}

					if($f['url']!=NULL)
						echo "<div class=\"date\">Posted by <a href=\"".$f['url']."\">".$f['nick']."</a> at ";
					else
						echo "<div class=\"date\">Posted by <strong>".$f['nick']."</strong> at ";
				}
				else
					echo "<div class=\"date\">Posted at ";
					
				echo "<a href=\"$link\">".date("Y-m-d H:i:s",strtotime($f['created']))."</a>";
				
				//Eventuell Flattr-knapp
				if($f['user']!=NULL && user_get_flattr_choice($f['user']))
					$flattrID=user_get_flattrID($f['user']);
				else if($f['flattrID']!=NULL)
					$flattrID=$f['flattrID'];
				else
					$flattrID=NULL;
				$text=str_replace("\n","<br />",$f['texten']);
				$text=str_replace("<br /><br />","<br />",$text);	
				if($flattrID)
				{
					echo "<br />";
					if($f['subject']!=NULL && $f['subject']!="")
						flattr_button_show($flattrID, $link , $f['subject']." - feedback on ".SITE_URL, $text, 'compact', 'en_GB');
					else
						flattr_button_show($flattrID, $link , "Feedback ".$f['id']." - feedback on ".SITE_URL, $text, 'compact', 'en_GB');
				}
			echo "</div>";
				
			// echo "<br />DEBUG 1252: $flattrID";
			
			echo "</div>";
			echo "<div class=\"comment_body\">";
				//Skriv ut texten
				echo "<p>$text<a href=\"$link\">[...]</a></p>";
			echo "</div>";
			echo "<div class=\"clearer\"></div></li>";
		}
		echo "</ul>";
	}
}

function feedback_display_specific_headline($id, $expanded=FALSE)
{
	$sql="SELECT id, user, resolved, accepted, not_implemented, created, text, subject, plusones, nick, email, url, flattrID
	FROM ".PREFIX."feedback
	WHERE id=".sql_safe($id).";";
	// echo $sql;
	if($data=mysql_query($sql))
	{
		if($d=mysql_fetch_array($data)) 
		{
			if($d['not_implemented']!=NULL)
				$extra_class="feedback_not_implemented";
			else if($d['resolved']!=NULL)
				$extra_class="feedback_resolved";
			else if($d['accepted']!=NULL)
				$extra_class="feedback_accepted";
			else
				$extra_class="new";
			
			if($expanded)
				$click_operation="colapse";
			else
				$click_operation="expand";			
				
			echo "<div class=\"feedback_list_line $extra_class\" id=\"feedback_line_".$id."\">
			<div class=\"col-sm-12\">
				<div class=\"col-xs-8 feedback_headline\">
					<a href=\"#\" onclick=\"feedback_operation('".$click_operation."', ".$id.", 'feedback_line_".$id."'); return false;\">";
				if($d['subject']!="")
					echo $d['subject'];
				else
					echo substr($d['text'],0,128);
				echo "</a></div>";
				echo "<div class=\"col-xs-2\">".user_get_link(feedback_get_user($id))."</div>";
				echo "<div class=\"col-xs-2 small smalldate\"><a href=\"".SITE_URL."?page=feedback&amp;id=".$id."\">".date("Y-m-d H:i" , strtotime($d['created']))."</a></div>
			</div>";
				
				//Display body
				if($expanded)
					feedback_display_body($id);
			echo "<div class=\"clearer\"></div></div>";
		}
	}
}

function feedback_display_specific($id, $headlinesize=2)
{
	$inloggad=login_check_login_information();
	
	$sql="SELECT id, user, resolved, accepted, not_implemented, created, text, subject, plusones, nick, email, url, flattrID
	FROM ".PREFIX."feedback
	WHERE id=".sql_safe($id).";";
	
	if($data=mysql_query($sql))
	{
		if($d=mysql_fetch_array($data)) 
		{
			$postedby=user_get_name($d['user']);
			if($d['not_implemented']!=NULL)
			{
				//not_implemented
				echo "<div class=\"feedback not_implemented\">";
			}
			else if($d['resolved']!=NULL)
			{
				//färdigt
				echo "<div class=\"feedback feedback_resolved\">";
			}
			else if($d['accepted']!=NULL)
			{
				//accepterat
				echo "<div class=\"feedback feedback_accepted\">";
				// echo "<div class=\"comment\">";
			}
			else
			{
				//"Ny"
				echo "<div class=\"feedback\">";
			}

				echo "<div class=\"author\">";
					//Info om vem som la upp denna
					if($d['user']!=NULL)
						echo "Posted by <a href=\"?page=user&amp;user=".$d['user']."\">".$postedby."</a> at <a href=\"?page=feedback&amp;id=".$d['id']."\">".$d['created']."</a>";
					else if($d['nick']!=NULL)
					{
						if($d['url']!=NULL)
							echo "Posted by <a href=\"".$d['url']."\">".$d['nick']."</a> at <a href=\"?page=feedback&amp;id=".$d['id']."\">".$d['created']."</a>";
						else
							echo "Posted by ".$d['nick']." at <a href=\"?page=feedback&amp;id=".$d['id']."\">".$d['created']."</a>";
					}
					else
						echo "Posted at <a href=\"?page=feedback&amp;id=".$d['id']."\">".$d['created']."</a>";
					
				echo "</div>";
				
				//Rubrik
				echo "<h$headlinesize>";
				// if($d['subject']!="")
					echo feedback_get_link($id);
				// else
					// echo "Feedback #".$id;
				echo "</h$headlinesize>";
					
				//Visa själva Feedbacken!
				feedback_display_body($id);
			echo "</div>";
		}
	}
}

function feedback_display_accepted($nr)
{
	$sql="SELECT id FROM ".PREFIX."feedback 
	WHERE is_spam<1
	AND accepted IS NOT NULL
	AND resolved IS NULL
	AND merged_with IS NULL
	ORDER BY size ASC, accepted ASC
	LIMIT ".sql_safe($nr).";";
	if($ff=mysql_query($sql))
	{
		while($f=mysql_fetch_array($ff))
		{
			feedback_display_specific($f['id']);
		}
	}
}

//Visa några nya SOM länkar! Bara rubriker!
function feedback_display_list($size, $nr, $headline, $headlinesize)
{
	$sql="SELECT id, ".REL_STR." as rel
	FROM ".PREFIX."feedback 
	WHERE is_spam<1
	AND size=".sql_safe($size)."
	AND resolved IS NULL
	AND not_implemented IS NULL
	AND merged_with IS NULL
	ORDER BY rel DESC, plusones DESC, created ASC
	LIMIT ".sql_safe($nr).";";
	if($ff=mysql_query($sql))
	{
		if(mysql_affected_rows()>0)
			echo "<h".$headlinesize.">".$headline."</h".$headlinesize.">";
		echo "<div class=\"row\">";
		while($f=mysql_fetch_array($ff))
		{
			feedback_display_specific_headline($f['id']);
		}
		echo "</div>";
	}
}

function feedback_display_list_resolved($nr, $headline, $headlinesize)
{
	$sql="SELECT id
	FROM ".PREFIX."feedback 
	WHERE is_spam<1
	AND resolved IS NOT NULL
	AND merged_with IS NULL
	ORDER BY resolved DESC, plusones DESC, created ASC
	LIMIT ".sql_safe($nr).";";
	if($ff=mysql_query($sql))
	{
		if(mysql_affected_rows()>0)
			echo "<h".$headlinesize.">".$headline."</h".$headlinesize.">";
		echo "<div class=\"row\">";
		while($f=mysql_fetch_array($ff))
		{
			feedback_display_specific_headline($f['id']);
		}
		echo "</div>";
	}
}
function feedback_display_list_not_implemented($nr, $headline, $headlinesize)
{
	$sql="SELECT id
	FROM ".PREFIX."feedback 
	WHERE is_spam<1
	AND not_implemented IS NOT NULL
	AND merged_with IS NULL
	ORDER BY resolved DESC, plusones DESC, created ASC
	LIMIT ".sql_safe($nr).";";
	if($ff=mysql_query($sql))
	{
		if(mysql_affected_rows()>0)
			echo "<h".$headlinesize.">".$headline."</h".$headlinesize.">";
		echo "<div class=\"row\">";
		while($f=mysql_fetch_array($ff))
		{
			feedback_display_specific_headline($f['id']);
		}
		echo "</div>";
	}
}

function feedback_display_merge_form($id, $div_id="")
{
	if(isset($_SESSION[PREFIX."User"]) && isset($_SESSION[PREFIX."inloggad"]) && $_SESSION[PREFIX."inloggad"]>=3)
	{
		if($div_id=="")
			$div_id="feedback_merge_form_".$id;
		echo "<div id=\"".$div_id."\">";
			//Kolla om feedbacken är mergad
			if($m_id=feedback_is_merged($id))
			{
				echo "Merged with ".feedback_get_link($m_id);
				echo "<button id=\"feedback_merge_button_".$id."\" onclick=\"feedback_operation('unmerge',".$id.", '".$div_id."'); return false;\">Detach</button>";
			}
			else
			{
				//Annars, skriv ut formulär för att merga
				echo "
					Merge with: ".feedback_get_droplist($id, "feedback_merge_droplist_".$id);
					echo "<button id=\"feedback_merge_button_".$id."\" onclick=\"feedback_operation('merge',".$id.", '".$div_id."', 'feedback_merge_droplist_".$id."'); return false;\">Merge</button>
				";
			}
		echo "</div>";
	}
}

function feedback_is_merged($id)
{
	$sql="SELECT merged_with FROM ".PREFIX."feedback WHERE id=".sql_safe($id).";";
	if($ff=mysql_query($sql))
	{
		if($f=mysql_fetch_array($ff))
		{
			return $f['merged_with'];
		}
	}
	return NULL;
}

function feedback_get_attached_feedbacks($id)
{
	$sql="SELECT id,
	".REL_STR."	as rel
	FROM ".PREFIX."feedback 
	WHERE merged_with=".sql_safe($id)."
	ORDER BY rel DESC, created ASC;";
	// echo $sql;
	if($ff=mysql_query($sql))
	{
		if(mysql_affected_rows()>0)
		{
			while($f=mysql_fetch_array($ff))
			{
				$r[]=$f['id'];
			}
			return $r;
		}
	}
	return NULL;
}

function feedback_get_link($id)
{
	$sql="SELECT subject, text FROM ".PREFIX."feedback WHERE id=".sql_safe($id).";";
	if($ff=mysql_query($sql))
	{
		if($f=mysql_fetch_array($ff))
		{
			if($f['subject']!=NULL)
				$str=$f['subject'];
			else
				$str=substr($f['text'], 0, 128);
			return "<a href=\"".feedback_get_url($id)."\">$str</a>";
		}
	}
	return NULL;
	
}
function feedback_get_url($id)
{
	return SITE_URL."?page=feedback&amp;id=".sql_safe($id);
}

function feedback_get_droplist($exclude_id, $droplist_id)
{
	if($rel=feedback_get_list_relevant(0, 500))
	{
		$r_str="<select id=\"".$droplist_id."\">";
		while($r=mysql_fetch_array($rel))
		{
			if($r['subject']!=NULL)
				$str=$r['subject'];
			else
				$str=substr($r['text'], 0, 64);
			$r_str.="<option value=\"".$r['id']."\">".$str."</option>";
		}
		$r_str.="</select>";
		return $r_str;
	}
	
	return NULL;
}

function feedback_set_accepted($id)
{
	//Accept the feedback
	$sql="UPDATE ".PREFIX."feedback SET not_implemented=NULL, accepted='".date("YmdHis")."', resolved=NULL WHERE id=".sql_safe($id).";";
	mysql_query($sql);
	
	//Set parent to accepted
	$sql="SELECT merged_with FROM ".PREFIX."feedback WHERE id=".sql_safe($id).";";
	if($ff=mysql_query($sql))
	{
		if($f=mysql_fetch_array($ff))
		{
			if($f['merged_with']!=NULL)
			{
				feedback_set_accepted($f['merged_with']);
			}
		}
	}
}
function feedback_set_unaccepted($id)
{
	//Unaccept the feedback
	$sql="UPDATE ".PREFIX."feedback SET accepted=NULL WHERE id=".sql_safe($id).";";
	mysql_query($sql);
	
	//Set children that is not done to unaccepted
	$sql="SELECT id FROM  ".PREFIX."feedback WHERE merged_with=".sql_safe($id)." AND resolved IS NULL;";
	// echo $sql;
	if($ff=mysql_query($sql))
	{
		while($f=mysql_fetch_array($ff))
		{
			feedback_set_unaccepted($f['id']);
		}
	}
}

function feedback_set_resolved($id)
{
	//Resolve the feedback
	$sql="UPDATE ".PREFIX."feedback SET not_implemented=NULL, resolved='".date("YmdHis")."' WHERE id=".sql_safe($id).";";
	mysql_query($sql);
	
	//Resolve any children
	//Find children
	$sql="SELECT id FROM  ".PREFIX."feedback WHERE merged_with=".sql_safe($id).";";
	if($ff=mysql_query($sql))
	{
		while($f=mysql_fetch_array($ff))
		{
			feedback_set_resolved($f['id']);
		}
	}
}
function feedback_set_unresolved($id)
{
	$sql="UPDATE ".PREFIX."feedback SET resolved=NULL WHERE id=".sql_safe($id).";";
	mysql_query($sql);
	
	//Set parent to unresolved
	$sql="SELECT merged_with FROM ".PREFIX."feedback WHERE id=".sql_safe($id).";";
	if($ff=mysql_query($sql))
	{
		if($f=mysql_fetch_array($ff))
		{
			if($f['merged_with']!=NULL)
			{
				feedback_set_unresolved($f['merged_with']);
			}
		}
	}
}

function feedback_display_body($id, $hidden=FALSE)
{
	if($hidden)
		$hide_str="style=\"display: none;\"";
	else
		$hide_str="";
		
	echo "<div id=\"feedback_body_".$id."\" ".$hide_str." class=\"feedback_body col-xs-12\">";
	
	//Shows everything but headline, username and time
	$shown=0;
	$sql="SELECT id, text, user, flattrID, plusones FROM ".PREFIX."feedback WHERE id=".sql_safe($id).";";
	if($dd=mysql_query($sql))
	{
		if($d=mysql_fetch_array($dd))
		{

			//Text
			echo "<div class=\"col-xs-12 feedback_text\">".sql_safe($d['text'])."</div>";
			
			
			//Bottom
			echo "<div class=\"col-xs-12 small feedback_bottom\" id=\"feedback_bottom_".$id."\" ".$hide_str.">";
				echo "<div class=\"col-sm-8\">";
					feedback_status_show($id, NULL, NULL, NULL, "feedback_status_".$id);
					feedback_display_size_buttons($id);
					feedback_display_merge_form($id);
				echo "</div>";
				echo "<div class=\"col-sm-4\">";
					//Eventuellt Flattr-knapp
					if($d['user']!=NULL)
					{
						if(user_get_flattr_choice($d['user']))
						{
							echo "<br />";
							flattr_button_show(user_get_flattrID($d['user']), SITE_URL."?page=feedback&amp;id=".$id , "Feedback ".$id." - a feedback post on Dreamhorse.se", $d['text'], 'compact', 'en_GB');
						}
					}
					else if($d['flattrID']!=NULL)
					{
						echo "<br />";
						flattr_button_show($d['flattrID'], SITE_URL."?page=feedback&amp;id=".$d['id'] , "Feedback ".$d['id']." - a feedback post on Dreamhorse.se", $d['text'], 'compact', 'en_GB');
					}
					echo "</div>";
					//Plus-knapp
					echo "<div class=\"plusone\">";
					echo "<form method=\"post\">";
					echo "<input type=\"submit\" name=\"feedback_plusone\" value=\"+".($d['plusones']+1)."\">
						<input type=\"hidden\" name=\"id\" value=\"".$d['id']."\">";
					// echo "<br />".$d['plusones']." +1's";
					echo "</form></div>";
					//Kolla om det är användarens feedback.
					if($d['user']==NULL || (isset($_SESSION[PREFIX."User"]) && strcmp($d['user'],$_SESSION[PREFIX."User"])))
						spam_show_clicker($d['id'], "feedback");
				echo "</div>";
				echo "<div class=\"col-sm-12\">";
				$attached=feedback_get_attached_feedbacks($d['id']);
				if($attached)
				{
					echo "<h3>Attached feedbacks</h3>";
					foreach($attached as $a)
						feedback_display_specific_headline($a);
				}
				echo "</div>";
				echo "<div class=\"col-sm-12\">";
					//visa kommentarer och om inloggad fält för att kommentera
					comments_show_comments_and_replies($d['id'], "feedback");
				echo "</div>";
			
			$shown=1;
		}
	}
	if(!$shown)
		echo "<p class=\"error\">Feedback could not be shown</p>";
	echo "<div class=\"clearer\"></div></div>";
}

?>
