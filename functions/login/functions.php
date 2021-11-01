<?php

session_start();

/********************************/
/*	Handles requests from forms	*/
/********************************/
function login_receive()
{	
	$message=NULL;
	$conn=login_mysql_connect();
	
	if(isset($_POST['login']))
	{
		$message=login_login();
	}
	else if(isset($_POST['userregister']))
	{
		$message=login_register_user();
		// echo "mess: $message";
	}
	else if(isset($_POST['logout']))
	{
		$message=login_logout();
	}
	else if(isset($_POST['passwordreset']))
		$message=login_reset_password();
	else if(isset($_GET['passwordrecover']))
	{
		echo "Your code is \"".sql_safe($_GET['pass'])."\".";
		login_set_new_password($_GET['pass']);
	}
		
	mysql_close($conn);
	return $message;
}

function login_mysql_connect()
{
	$conn=@mysql_connect(login_DB_host, login_DB_user,login_DB_pass);
	if (!$conn)
	{
		echo "<p>Login: MySQL-server is not working ".login_DB_host.", ".login_DB_user."</p>";
		return NULL;
	}

	$databas=@mysql_select_db(login_DB_name);
	if (!$databas)
	{
		echo "<p>Login: Database is not working</p>";
		return NULL;
	}
	
	// echo "<p>Login: Database is working</p>";
	return $conn;
}

function login_display_login_form($login_headline="Login in", $login_message="", $forgot_message="I forgot my password", $register_message="Register")
{
	if(isset($_POST['register']))
	{
		//Don't show the login form while we are registering. That would be confusing.
		login_display_register_form();
	}
	else if((!isset($_SESSION[login_PREFIX.'inloggad']) || $_SESSION[login_PREFIX.'inloggad']<1))
	{
		echo "<div class=\"login_form\">
		<form method=\"post\">
		<h3>$login_headline</h3>
		<p>$login_message</p>

		<p>Username: <input type=\"text\" name=\"username\"></p>
		<p>Password: <input type=\"password\" name=\"password\"></p>
		<p><input type=\"submit\" name=\"login\" value=\"Log me in!\"></p>
		<p><input type=\"submit\" name=\"forgot\" value=\"$forgot_message\"></p>
		<p><input type=\"submit\" name=\"register\" value=\"$register_message\"></p>
		</form></div>
		";
	}
	else
		echo "<p>Logged in as ".$_SESSION[login_PREFIX."Username"]."</p><form method=\"post\"><input type=\"submit\" name=\"logout\" value=\"Log out\"></form>";
}

function login_display_register_form()
{
	echo "<h2>Register</h2>";
	echo "<form method=\"post\">
		<p>Username<br />";
	if(isset($_POST['username']))
		echo "<input type=\"text\" name=\"username\" value=\"".$_POST['username']."\"></p>";
	else
		echo "<input type=\"text\" name=\"username\"></p>";
	echo "
	<p>Email<br />";
	if(isset($_POST['email']))
		echo "<input type=\"text\" name=\"email\" value=\"$_POST[email]\">";
	else
		echo "<input type=\"text\" name=\"email\">";
	echo "<span class=\"smalltext\">Needs to be a correct one as your password will be sent here!</span></p>
		<p><input type=\"submit\" name=\"userregister\" value=\"Sign up\"></p>
	</form>
	";
}


function login_display_password_recover_form()
{
		echo "
	<form method=\"post\">
		<p>Reset your password? Enter your e-mail or your username.</p>
		<p>E-mail: <input type=\"text\" name=\"email\"></p>
		<p>Username:<input type=\"text\" name=\"username\"></p>
		<input type=\"submit\" name=\"passwordreset\" value=\"reset\">
	</form>";
}

function login_login()
{
	$_SESSION[login_PREFIX."inloggad"]=0;
	
	$sql="SELECT id,password,level FROM ".login_PREFIX."user WHERE username='".sql_safe($_POST['username'])."' AND blocked IS NULL;";
	// echo "<br />DEBUG1615: $sql";
	if($uu=mysql_query($sql))
	{
		if($u=mysql_fetch_array($uu))
		{
			if(!strcmp($u['password'], crypt($_POST['password'], login_CONFUSER)))
			{
				$_SESSION[login_PREFIX."Username"]=$_POST['username'];
				$_SESSION[login_PREFIX."Userid"]=$u['id'];
				$_SESSION[login_PREFIX."HTTP_USER_AGENT"] = md5($_SERVER['HTTP_USER_AGENT']);
				$_SESSION[login_PREFIX.'password']=$_POST['password'];

				$_SESSION[login_PREFIX."inloggad"]=$u['level'];
				
				//uppdatera login så att användaren blir aktiv
				// user_update_login($u['id']);	
				
				$message="Welcome, ".$_SESSION[login_PREFIX."Username"]."!";
			}
			else
				$_SESSION[login_PREFIX."inloggad"]=-1;
		}
		else
			$_SESSION[login_PREFIX."inloggad"]=-2;
	}
	else
		$_SESSION[login_PREFIX."inloggad"]=-3;

	if($_SESSION[login_PREFIX."inloggad"]<1)
	{
		$message="Log in failed (".$_SESSION[login_PREFIX."inloggad"]."). If you think this is in error, contact <a href=\"mailto:info@storybook.se\">admin</a>. You can try <a href=\"?\">logging in again.</a></p>
		<p><a href=\"?login=forgot\">Retrieve password</a></p>
		<p><a href=\"?login=register\">Register</a></p>";
	}
	return $message;
}

function login_logout()
{
	$sid = session_id();
	if($sid)
	{
		// Session exists!
		session_unset();
		session_destroy();
	}
	return "You are now logged out";
}

function login_register_user()
{
	$r="";
	$conn=login_mysql_connect();
	if(isset($_POST['username']) && $_POST['username']!="")
	{
		if(isset($_POST['email']) && $_POST['email']!="")
		{
			//Kolla om nicket redan finns
			//Kolla om emailen redan finns
			if(login_user_exist($_POST['username'], $_POST['email']))
			{
		
				$r= "Username or email already registered.";
			}
			else
			{
				$pass=password_generate(8);
				$went_fine=mysql_query("INSERT INTO ".login_PREFIX."user SET username='".sql_safe($_POST['username'])."', email='".sql_safe($_POST['email'])."', password='".crypt($pass, login_CONFUSER)."';");
				if($went_fine)
				{
					//Skicka ett email
					$to = $_POST['email'];
					$subject = "Your new registration";
					$body="Thankyou for signing up!

				Your new password is: $pass

Hope to see you soon!
				
you recieve this email because your email was used to register at ".login_SITE_URL." If this was not done by you, simply ignore this message, and we apologize for the inconvenience.
";
					$headers = 'From: '.login_CONTACT_EMAIL . "\r\n" .
	'Reply-To: '.login_CONTACT_EMAIL. "\r\n" .
	'X-Mailer: PHP/' . phpversion();
					
					//Send mail
					if (mail($to, $subject, $body, $headers))
					{
						echo "<p>Message successfully sent!</p>";
						$r= "<h2>Congratulations!</h2><p>Your registration went fine. You will be notified by email at $_POST[email] soon. This email will contain your new password.</p>";
					}
					else
					{
						echo "<p>Message delivery failed.</p>";
						$r= "Message delivery failed.";
					}
				}
			}
		}
		else
			$r= "You need to enter an email adress!";		
	}
	else
		$r= "You need to have a username!";
		
	mysql_close($conn);
}

function login_create_admin($username, $email)
{
	$r="";
	//Skapar en användare med level 5
	$conn=login_mysql_connect();
	if($conn)
	{
		echo "bleh";
		if($username!="")
		{
			if($email!="")
			{
				//Kolla om nicket redan finns
				//Kolla om emailen redan finns
				if(login_user_exist($username, $email))
				{
					$r= "Username or email already registered.";
				}
				else
				{
					$pass=password_generate(8);
					$went_fine=mysql_query("INSERT INTO ".login_PREFIX."user SET username='".sql_safe($username)."', email='".sql_safe($email)."', password='".crypt($pass, login_CONFUSER)."';");
					if($went_fine)
					{
						//Skicka ett email
						$to = $email;
						$subject = "Your new registration";
						$body="Thankyou for signing up!

					Your new password is: $pass

	Hope to see you soon!
					
	you recieve this email because your email was used to register at ".login_SITE_URL." If this was not done by you, simply ignore this message, and we apologize for the inconvenience.
	";
						$headers = 'From: '.login_CONTACT_EMAIL . "\r\n" .
		'Reply-To: '.login_CONTACT_EMAIL. "\r\n" .
		'X-Mailer: PHP/' . phpversion();
						
						//Send mail
						if (mail($to, $subject, $body, $headers))
						{
							echo "<p>Message successfully sent!</p>";
							$r= "<h2>Congratulations!</h2><p>Your registration went fine. You will be notified by email at $_POST[email] soon. This email will contain your new password.</p>";
						}
						else
						{
							echo "<p>Message delivery failed.</p>";
							$r= "Message delivery failed.";
						}
					}
				}
			}
			else
				$r= "You need to enter an email adress!";		
		}
		else
			$r="You need to have a username!";
	}
	else
		$r="Database trouble";
		
	mysql_close($conn);
	return $r;
}

function login_reset_password()
{
	$r="";
	
	$id=login_get_user_id($_POST['username'],$_POST['email']);
	$code=password_generate(16);
	//Skapa en reset-kod
	$sql="INSERT INTO `".login_PREFIX."reset_codes` SET 
	`user`='".sql_safe($id)."',
	`code`='".sql_safe($code)."';";
	mysql_query($sql);
	
	//Skicka länken till människan
	//Skicka ett email
	$sql="SELECT email FROM ".login_PREFIX."user WHERE id='$id';";
	if($uu=mysql_query($sql))
	{
		if($u=mysql_fetch_array($uu))
		{
			$to = $u['email'];
			$subject = "Password recovery";
			$body="Hello,
	
To change password, please visit the adress below.

".login_SITE_URL."/?passwordrecover&pass=$code

you recieve this email because someone requested a password recovery at ".login_SITE_URL.". If this was not done by you, simply ignore this message, and we apologize for the inconvenience.
";
			$headers = 'From: '.login_CONTACT_EMAIL . "\r\n" .
		'Reply-To: '.login_CONTACT_EMAIL. "\r\n" .
		'X-Mailer: PHP/' . phpversion();
			
			//Send mail
			if (mail($to, $subject, $body, $headers))
			{
				// echo "<br />DEBUG1640: mail($to, $subject, $body, $headers)";
				$r= "Recovery message successfully sent.";
			}
			else
			{
				$r= "Message delivery failed.";
			}
		}
		else
			$r="User not found";
	}
	else
		$r="Unknown database error";
			
	return $r;
}

function login_set_new_password($reset_code)
{
	$sql="SELECT user
	FROM ".login_PREFIX."reset_codes
	WHERE code='".sql_safe($reset_code)."'
	AND used IS NULL
	ORDER BY generated DESC
	LIMIT 1;";
	// echo "<br />DEBUG1044: $sql";
	if($cc=mysql_query($sql))
	{
		if($c=mysql_fetch_array($cc))
		{
			$pass=password_generate(8);
			$went_fine=mysql_query("UPDATE ".login_PREFIX."user SET password='".crypt($pass, login_CONFUSER)."';");
			mysql_query("UPDATE ".login_PREFIX."reset_codes SET used='".date("YmdHis")."' WHERE code='".sql_safe($reset_code)."';");
			if($went_fine)
				echo "<p>New password: $pass</p>";
			else
				echo "<p>No</p>";
		}
	}
}
function login_check_login_information()
{
	$conn=login_mysql_connect();
	if(!isset($_SESSION[login_PREFIX."inloggad"]) || $_SESSION[login_PREFIX."inloggad"]<1)
	{
		$message="Session expired";
	}
	else
	{
		$sql="SELECT id,password,level, username FROM ".login_PREFIX."user WHERE id='".sql_safe($_SESSION[login_PREFIX."Userid"])."' AND blocked IS NULL;";
		// echo "<br />DEBUG1615: $sql";
		if($uu=mysql_query($sql))
		{
			if($u=mysql_fetch_array($uu))
			{
				if(!strcmp($u['password'], crypt($_SESSION[login_PREFIX.'password'], login_CONFUSER)))
				{
					if(!strcmp($u['username'], $_SESSION[login_PREFIX."Username"]))
					{
						if(!strcmp($_SESSION[login_PREFIX."HTTP_USER_AGENT"], md5($_SERVER['HTTP_USER_AGENT'])))
						{
							if($_SESSION[login_PREFIX."inloggad"]==$u['level'])
							{
								//Korrekt fortfarande inloggad.
								
								//uppdatera login så att användaren blir aktiv
								// user_update_login($u['id']);	
							}
							else
								$message="Unmatching level";

						}
						else
							$message="Logged in at another computer";
					
					}
					else
						$message="Incorrect userinformation";
				}
				else
					$message="Incorrect information";
			}
			else
				$message="Invalid user";
		}
		else
			$message="Invalid user id";
	}

	if(isset($message))
	{
		login_logout();
	}
	
	mysql_close($conn);
	if(isset($message))
		return $message;
	else
		return NULL;
}

//Controll to see if user is logged in by checking session. Returns level. NEVER use this before calling login_check_login_information(). It's not safe.
function login_is_logged_in()
{
	if(isset($_SESSION[login_PREFIX."inloggad"]))
	{
		return $_SESSION[login_PREFIX."inloggad"];
	}
	return 0;
}

function login_user_exist($username=NULL, $email=NULL)
{
	$exists=0;
	
	if($username!=NULL && $email!=NULL)
		$sql="SELECT username, email FROM ".login_PREFIX."user WHERE (username='".sql_safe($username)."' OR email='".sql_safe($email)."');";
	else if($username!=NULL)
		$sql="SELECT username, email FROM ".login_PREFIX."user WHERE username='".sql_safe($username)."';";
	else if($email!=NULL)
		$sql="SELECT username, email FROM ".login_PREFIX."user WHERE email='".sql_safe($email)."';";
	else
		$exists=-1;
	// echo "<br />DEBUG1652: $sql";
	if($exists==0 && $uu=mysql_query($sql))
	{
		if(mysql_affected_rows()>0)
		{
			$exists=1;
		}
	}
				
	return $exists;
}

function login_get_user_id($username=NULL, $email=NULL)
{
	if($username!=NULL && $username!="")
	{
		$sql="SELECT id FROM ".login_PREFIX."user WHERE username='".sql_safe($username)."';";
	}
	else if($email!=NULL && $email!="")
	{
		$sql="SELECT id FROM ".login_PREFIX."user WHERE email='".sql_safe($email)."';";
	}
	if(isset($sql))
	{
		if($uu=mysql_query($sql))
		{
			if($u=mysql_fetch_array($uu))
			{
				return $u['id'];
			}
		}
	}
	return NULL;
}

?>
