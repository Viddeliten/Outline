<?php
require_once("config.php");
include("templates/default/functions.php");
include("functions/login/config.php");
include("functions/outline.php");
require_once("../../functions/string.php");
require_once("../../functions/password.php");
if(!defined('SITE_URL'))
	define("SITE_URL", "http://outline.berattelse.se");
?>