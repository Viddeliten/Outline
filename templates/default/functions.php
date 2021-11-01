<?php

function template_display_top_menu()
{
	?><ul class="menu">
		<li><a href="<?php echo SITE_URL; ?>">Stories</a></li>
		<li><a href="<?php echo SITE_URL; ?>/?p=location">Locations</a></li>
		<li><a href="<?php echo SITE_URL; ?>/?p=person">Characters</a></li>
		<li><a href="<?php echo SITE_URL; ?>/?p=event">Events</a></li>
		<li><a href="<?php echo SITE_URL; ?>/?p=gene">Genes</a></li>
		<li><a href="/?p=feedback">Feedback</a></li>
	</ul><?php
}



function template_display_list($items, $type)
{
	
	if(!empty($items))
	{
		echo "<ul>";
		foreach($items as $s)
		{
			if($s['name']!="")
				$name=$s['name'];
			else
				$name=$type." #".$s['id'];
				
			echo "<li><a href=\"".SITE_URL."/?p=$type&amp;id=".$s['id']."\" title=\"".$s['description']."\">$name</a>";
			template_display_button("delete", $type, "Delete", "inline small", $s['id']);
			echo "</li>";
		}
		echo "</ul>";
	}
	else
		echo "<p>No items</p>";
}

function template_display_ticks($ticks)
{
	// echo "<pre>Ticks1035:
// ".print_r($ticks, 1)."</pre>";

	$y_pos=-1;
	
	echo "<ul class=\"nobullets\">";
	foreach($ticks as $tt)
	{
		$x_pos=0;
		$y_pos=$tt[0]['y_pos'];
		template_display_tick_row($tt, "li");
	}
	echo "</ul>";
	return $y_pos+1;
}

function template_display_tick_row($tick_row_arr, $container_type)
{
	// echo "<pre>".print_r($tick_row_arr, 1)."</pre>";
	$y_pos=$tick_row_arr[0]['y_pos'];
	$x_pos=0;
	echo "<$container_type class=\"blocker\" id=\"tick_row_$y_pos\">";
		echo "<span class=\"tick\">";
			tick_display_remove_button("-",$y_pos);
		echo "</span>";
		foreach($tick_row_arr as $t)
		{
			$x_pos=$t['x_pos'];
			template_display_tick($t);
		}
		tick_display_add_button("+",$y_pos,$x_pos+1);
	echo "</$container_type>";
}

function template_display_tick($tick_arr)
{
	echo "<form class=\"tick\" id=\"tick_".$tick_arr['id']."\">";
		// echo "<div class=\"property\">Tick: ".$tick_arr['y_pos'].",".$tick_arr['x_pos']."</div>";
		echo "<div class=\"property\">Location: ";
			template_display_droplist("location", "tick", $tick_arr['id'], $tick_arr['location']);
		echo "</div>";
		echo "<div class=\"property\">Person: ";
			template_display_droplist("person", "tick", $tick_arr['id'], $tick_arr['person']);
		echo "</div>";
		// echo "<div class=\"property\">Event: ".$tick_arr['event']."</div>";
		echo "<div class=\"property\">Event: ";
			template_display_droplist("event", "tick", $tick_arr['id'], $tick_arr['event']);
		echo "</div>";
	echo "</form>";
}

function template_display_location($id)
{
	return template_display_base_info("location", $id);
}

function template_display_person($id)
{
	return template_display_base_info("person", $id);
}

function template_display_event($id)
{
	return template_display_base_info("event", $id);
}

function template_display_gene($id)
{
	return template_display_base_info("gene", $id);
}

function template_display_story($id)
{
	//Story thing
	template_display_base_info("story", $id);
	
	//Ticks
	$tick_no=template_display_ticks(tick_get_ticks($id));
	echo "<p class=\"blocker\">&nbsp;</p>";
	tick_display_add_button("Add tick",$tick_no);
}

function template_display_base_info($type, $id)
{
	if(login_is_logged_in() && outline_get_info("user", $type, $id)===$_SESSION[login_PREFIX.'Userid'])
	{
		echo '<form method="post" class="base_info">';
		template_display_textbox($type, "name", $id);
		echo "<br />";
		template_display_textarea($type, "description", $id);
		echo "<br />";
		template_display_checkbox($type, "public", $id);
		echo '<br /><input type="submit" name="'.sql_safe($type).'_info_change" value="Save">';
		echo '</form>';
	}
	else if(outline_get_info("public",$type, $id))
	{
		echo "<div class=\"base_info\">";
			template_display_textbox($type, "name", $id);
			template_display_textarea($type, "description", $id);
		echo '</div>';
	}
	else
		return "Not visible publicly";
}

function template_display_button($buttontype="create", $type="story", $buttontext="Create", $class="", $id=NULL)
{
	?>
	<form method="post" class="<?php echo $class; ?>">
		<input type="hidden" name="id" value="<?php echo $id; ?>">
		<input type="submit" name="<?php echo $type; ?>_<?php echo $buttontype; ?>" value="<?php echo $buttontext; ?>">
	</form>
	<?php
}

function template_display_textarea($type, $info, $id)
{
	//If logged in user owns this
	if(login_is_logged_in() && outline_get_info("user", $type, $id)===$_SESSION[login_PREFIX.'Userid'])
	{
		//In that case display form
		?>
			<textarea 
				name="<?php echo $type; ?>_<?php echo $info; ?>"
				placeholder="<?php echo $type." ".$info; ?>"
			><?php echo outline_get_info($info, $type, $id); ?></textarea>
		<?php
	}
	else
		echo "<p>".outline_get_info($info, $type, $id)."</p>";
		
}

function template_display_droplist($property_type, $main_type, $main_id, $selected, $user_id=NULL)
{
	if($user_id===NULL)
		$user=$_SESSION[login_PREFIX.'Userid'];
	else
		$user=$user_id;
		
	if($main_type=="tick")
		$target_container_id="#tick_row_".outline_get_info("y_pos", "tick", $main_id);
	else
		$target_container_id="#".$main_type."_$main_id";
	
	// echo "target: ".$target_container_id;
		
		
	$sql="SELECT id, name FROM ".sql_safe($property_type)."
		WHERE user=".sql_safe($user)."
		AND deleted IS NULL";
	// echo "<br />DEBUG1250 $sql";
	if($tt=mysql_query($sql)) 
	{
		if(mysql_affected_rows()>0)
		{
			echo "<select 
			name=\"".sql_safe($property_type)."\" 
			id=\"".$property_type."_droplist_$main_id\"
			onchange=\"javascript:replace_html('$target_container_id', '#".$property_type."_droplist_$main_id', 'wrapper.php/?".$main_type."=$main_id&type=".$property_type."');\">";
			echo "<option value=\"\"> -- </option>";
			while($t=mysql_fetch_array($tt))
			{
				echo "<option value=\"".$t['id']."\"";
				if($selected===$t['id'])
					echo " selected";
				echo ">".$t['name']."</option>";
			}
			echo "</select>";
		}
	}
}

function template_display_textbox($type, $info, $id, $placeholder=NULL)
{
	require_once("functions/color.php");
	//If logged in user owns this
	if(login_is_logged_in() && outline_get_info("user", $type, $id)===$_SESSION[login_PREFIX.'Userid'])
	{
		//In that case display form
		if($placeholder==NULL)
			$placeholder=$type." ".$info;
		
		$update_path="update_property.php/?type=$type&info=$info&id=$id&new_value=";
		
		$value=outline_get_info($info, $type, $id);
		if (strpos($info,'color') !== false) {
			$value=strtoupper(color_int_to_colorhex($value));
		}
			
		?>
			<input 
				type="text" 
				name="<?php echo $type; ?>_<?php echo $info; ?>" 
				value="<?php echo $value; ?>" 
				placeholder="<?php echo $placeholder;?>" 
				onblur="javascript:new_value('<?php echo $update_path; ?>'+this.value, this, <?echo $info."_".$id; ?>);"
			/>
		<?php
	}
	else
		echo "<p>".outline_get_info($info, $type, $id)."</p>";
	
	if (strpos($info,'color') !== false) {
		echo '<span id="'.$info."_".$id.'" style="background-color: #'.$value.'; width: 50px; height: 50px;">Color</span>';
	}
}

function template_display_checkbox($type, $info, $id)
{
	//If logged in user owns this
	if(login_is_logged_in() && outline_get_info("user", $type, $id)===$_SESSION[login_PREFIX.'Userid'])
	{
		//In that case display form

		$current_value=outline_get_info($info, $type, $id);
		?>
			<input 
				type="checkbox" 
				name="<?php echo $type; ?>_<?php echo $info; ?>" 
				<?php if($current_value) echo " checked "; ?>
			/>
		<?php
		echo ucfirst($info);
	}
}

function template_display_person_settings($id)
{
	$sql="SELECT display_color, genes, haircolor, skincolor, eyecolor, gender, mum, dad, created, deleted FROM person WHERE id=".sql_safe($id).";";
	
	echo "<form method=\"post\">";
	template_display_textbox("person", "display_color", $id, "Display color");
	echo "<br />";
	template_display_textarea("person", "genes", $id);
	echo "<br />";
	template_display_textbox("person", "haircolor", $id);
	echo "<br />";
	template_display_textbox("person", "skincolor", $id);
	echo "<br />";
	template_display_textbox("person", "eyecolor", $id);
	echo "<br />";
	template_display_textbox("person", "gender", $id);
	echo "<br />";
	template_display_textbox("person", "mum", $id);
	echo "<br />";
	template_display_textbox("person", "dad", $id);
	echo "<br />";
	template_display_textbox("person", "created", $id);
	echo "<br />";
	template_display_textbox("person", "deleted", $id);
	echo "</form>";
	
}

?>