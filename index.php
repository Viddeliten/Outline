<?php

include("db.php");

include("functions/feedback/func.php");
include("functions/story/func.php");
include("functions/tick/func.php");
include("functions/location/func.php");
include("functions/person/func.php");
include("functions/event/func.php");
include("functions/gene/func.php");
include("functions/login/functions.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"

        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>

<title>Outliner</title>

<meta charset="ISO-8859-1">
<meta name="description" content="Create stories semi-automatically" />
<meta name="keywords" content="stories, free, share, creativity" />

	
<link rel="stylesheet" href="templates/default/style.css" type="text/css" />

<!-- Flattr -->

<script type="text/javascript">

<!--//--><![CDATA[//><!--

    (function() {

        var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];

        s.type = 'text/javascript';

        s.async = true;

        s.src = 'http://api.flattr.com/js/0.6/load.js?mode=auto';

        t.parentNode.insertBefore(s, t);

    })();

//--><!]]>

</script>
<!-- slut Flattr -->

</head>
<body>	

<?php
template_display_top_menu();

$message=login_receive();
if($message!==NULL)
	echo "<p>".$message."</p>";
login_check_login_information();

$message=story_receive();
if($message!==NULL)
	echo "<p>".$message."</p>";
	
$message=tick_receive();
if($message!==NULL)
	echo "<p>".$message."</p>";

$message=location_receive();
if($message!==NULL)
	echo "<p>".$message."</p>";
	
$message=person_receive();
if($message!==NULL)
	echo "<p>".$message."</p>";

$message=event_receive();
if($message!==NULL)
	echo "<p>".$message."</p>";

$message=gene_receive();
if($message!==NULL)
	echo "<p>".$message."</p>";

$message=login_display_login_form();
if($message!==NULL)
	echo "<p>".$message."</p>";


include("content.php");

?>
<script src="//code.jquery.com/jquery-2.1.0.min.js"></script>
<script src="./functions/java.js"></script>

</body>
</html>
<?php 
?>