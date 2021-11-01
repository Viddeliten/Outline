<?php

/****************/
/*	Get POST	*/
/****************/
if(isset($_POST['configure_login']))
{
	//Create config.php for writing
	if(!$handle = fopen("config.php", "w"))
	{
        echo "Cannot open file (\"config.php\")";
        exit;
    }
	
	//Write everything
	try
	{
		fwrite ( $handle , "<?php\n");
		
		if($_POST['SITE_URL']!="")
			fwrite ( $handle , "define('login_SITE_URL','".$_POST['SITE_URL']."');\n");
		if($_POST['PREFIX']!="")
			fwrite ( $handle , "define('login_PREFIX','".$_POST['PREFIX']."');\n");
		if($_POST['confuser']!="")
			fwrite ( $handle , "define('login_CONFUSER','".$_POST['confuser']."');\n");
		if($_POST['admin_handle']!="")
			fwrite ( $handle , "define('login_ADMIN_HANDLE','".$_POST['admin_handle']."');\n");
		if($_POST['email']!="")
			fwrite ( $handle , "define('login_CONTACT_EMAIL','".$_POST['email']."');\n");
		
		if($_POST['DB_host']!="")
			fwrite ( $handle , "define('login_DB_host','".$_POST['DB_host']."');\n");
		if($_POST['DB_user']!="")
			fwrite ( $handle , "define('login_DB_user','".$_POST['DB_user']."');\n");
		if($_POST['DB_pass']!="")
			fwrite ( $handle , "define('login_DB_pass','".$_POST['DB_pass']."');\n");
		if($_POST['DB_name']!="")
			fwrite ( $handle , "define('login_DB_name','".$_POST['DB_name']."');\n");
		
		fwrite ( $handle , "include(\"functions.php\");\n");
						
		fwrite ( $handle , "?>");
	}
	catch (Exception $e)
	{
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
}

/********************************************************/
/*	Check if stuff is defined, otherwise ask for them!	*/
/********************************************************/
$defined=0;
	
if(file_exists("config.php"))
{
	include("config.php");
	$defined=1;

	//Some globals
	if(!defined('login_SITE_URL'))
		$defined=0;
	if(!defined('login_PREFIX'))
		$defined=0;
	if(!defined('login_CONFUSER'))
		$defined=0;
	if(!defined('login_ADMIN_HANDLE') || login_ADMIN_HANDLE=="")
		$defined=0;
	if(!defined('login_CONTACT_EMAIL') || login_CONTACT_EMAIL=="")
		$defined=0;

	//Database defines
	if(!defined('login_DB_host'))
		$defined=0;
	if(!defined('login_DB_user'))
		$defined=0;
	if(!defined('login_DB_pass'))
		$defined=0;
	if(!defined('login_DB_name'))
		$defined=0;

	//Try to connect to database	
	$conn=login_mysql_connect();
	if($conn==NULL)
		$defined=0;
}
	
if(!$defined)
{
	//Ask for config ?>
	<h1>Setup GetWebCode</h1>
	<form method="post">
		<h2>Some globals</h2>
			<p>
			Site URL :
			<input type="text" name="SITE_URL" value="<?php if(defined('login_SITE_URL')) echo login_SITE_URL; ?>">
			<br />
			(The base site where you want to use this from)<br />
			</p>
			<p>
			Confuser :
			<input type="text" name="confuser" value="<?php if(defined('login_CONFUSER')) echo login_CONFUSER; ?>">
			<br />
			(A string used for encryptions of passwords. Can be anything.)<br />
			</p>
			<p>
			Admin log in name :
			<input type="text" name="admin_handle" value="<?php if(defined('login_ADMIN_HANDLE')) echo login_ADMIN_HANDLE; ?>">
			<br />
			(You will log in to the admin interface with this)<br />
			</p>
			<p>
			Contact Email :
			<input type="text" name="email" value="<?php if(defined('login_CONTACT_EMAIL')) echo login_CONTACT_EMAIL; ?>">
			<br />
			(Emails will be sent from this adress upon registration)<br />
			</p>
		<h2>Database</h2>
			<p>
			Host :
			<input type="text" name="DB_host" value="<?php if(defined('login_DB_host')) echo login_DB_host; ?>"><br />
			Username :
			<input type="text" name="DB_user" value="<?php if(defined('login_DB_user')) echo login_DB_user; ?>"><br />
			Password :
			<input type="text" name="DB_pass" value="<?php if(defined('login_DB_pass')) echo login_DB_pass; ?>"><br />
			Database name :
			<input type="text" name="DB_name" value="<?php if(defined('login_DB_name')) echo login_DB_name; ?>"><br />
			Prefix :
			<input type="text" name="PREFIX" value="<?php if(defined('login_PREFIX')) echo login_PREFIX; ?>">
			<br />
			</p>
		<input type="submit" name="configure_login">
	</form>
	<?php
}
else
{
	//Check to see if tables exists, or create them.
	$sql="
	CREATE TABLE IF NOT EXISTS `".login_PREFIX."user` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `username` varchar(64) NOT NULL,
	  `password` varchar(64) NOT NULL,
	  `email` varchar(1024) NOT NULL,
	  `level` int(11) NOT NULL DEFAULT '1',
	  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	  `last_login` datetime DEFAULT NULL,
	  `deleted` datetime DEFAULT NULL,
	  `blocked` datetime DEFAULT NULL,
	  `delete_reason` text,
	  `block_reason` text,
	  PRIMARY KEY (`id`),
	  UNIQUE KEY `username` (`username`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
	if(!mysql_query($sql))
		$defined=0;
		
	$sql="
	CREATE TABLE IF NOT EXISTS `".login_PREFIX."reset_codes` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `user` int(11) NOT NULL,
	  `code` int(11) NOT NULL,
	  `generated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	  `used` datetime DEFAULT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
	if(!mysql_query($sql))
		$defined=0;
		
	//See that the admin is in the system
	require_once("functions.php");
	require_once("../../../../functions/string.php");
	require_once("../../../../functions/password.php");
	if(login_user_exist(login_ADMIN_HANDLE))
	{
		//Fixa level och e-post
		$sql="UPDATE `".login_PREFIX."user` SET level='5', email='".sql_safe(login_CONTACT_EMAIL)."' WHERE username='".sql_safe(login_ADMIN_HANDLE)."';";
		mysql_query($sql);
	}
	else
	{
		login_create_admin(login_ADMIN_HANDLE, login_CONTACT_EMAIL);
	}
}

if($defined)
	echo "Everything is allright.";
	
if(isset($conn) && $conn)
	@mysql_close($conn);
?>