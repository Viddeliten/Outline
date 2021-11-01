<?php
//$type, $info, $id, $new_value
include("db.php");
outline_userop("update_property",array($_GET['type'], $_GET['info'], $_GET['id'], $_GET['new_value']));
?>