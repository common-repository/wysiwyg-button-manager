<?php
/*
Plugin Name: WYSIWYG Button Manager
Plugin URI: http://www.codehooligans.com
Description: This plugin allow you to setup different button schemes for each user role.
Version: 0.5
Author: Paul Menard
Author URI: http://www.codehooligans.com
*/ 

class CH_WYSBtnMgrPlugin
{
	var $options_key;
	var $allow_mce_buttons;
	var	$button_info;
	var $page_name;

	function CH_WYSBtnMgrPlugin()
	{
		$this->options_key = "WYSIWYG_button_manager_data";
		$this->allow_mce_buttons = array( 	"bold", 
											"italic", 
											"underline", 
											"strikethrough", 
											"justifyleft", 
											"justifycenter", 
											"justifyright", 				
											"justifyfull",
											"bullist", 
											"numlist", 
											"outdent", 
											"indent", 
											"cut", 
											"copy", 
											"paste", 
											"undo", 
											"redo",	
											"link", 
											"unlink", 
											"image", 
											"cleanup", 
											"help", 
											"code", 
											"hr", 
											"removeformat", 
											"formatselect",
											"fontselect", 
											"fontsizeselect", 
											"styleselect", 
											"sub", 
											"sup", 
											"forecolor", 
											"backcolor",
											"charmap", 
											"visualaid", 
											"anchor", 
											"newdocument",
											"separator",
											"wp_more", 
											"spellchecker", 
											"wp_help", 
											"wp_adv_start", 
											"wp_adv_end",
											"wp_adv", 
											"pastetext", 
											"pasteword");

		$this->page_name = "wysiwyg_button_manager";
		
				
		$this->button_info = array();
		$this->load_button_panels();
		
		add_action('admin_menu', array(&$this,'add_manage_menu'));

		//echo "_REQUEST<pre>"; print_r($_REQUEST); echo "</pre>"; 
		
		// Run the install script if a plugin is activated
		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'activate')
			add_action('init', array(&$this,'button_manager_install'));

		add_filter('mce_plugins', 	array(&$this, 'extended_editor_mce_plugins'));
		add_filter('mce_buttons', 	array(&$this, 'user_mce_buttons_1'));
		add_filter('mce_buttons_2', array(&$this, 'user_mce_buttons_2'));
		add_filter('mce_buttons_3', array(&$this, 'user_mce_buttons_3'));
	}
	
	function add_manage_menu() 
	{
		// Add a new menu under Manage:
		add_management_page('WYSIWYG Manager', 'WYSIWYG Manager', 8, $this->page_name, array(&$this, 'button_manager_page'));
	}

	function button_manager_install()
	{
		add_option($this->options_key, serialize($this->button_info), "This is the serialized button structures used.");
	}

	function load_button_panels()
	{
		
		$this->button_info = get_option($this->options_key);
		if (!is_array($this->button_info))
			$this->button_info = unserialize($this->button_info);
			
		//echo "button_info<pre>"; print_r($this->button_info); echo "</pre>"; 
		if (!$this->button_info)
		{
			$button_default['panel_name'] = "WP-default";
	
			/*
			$button_default['panel_button1'] = "formatselect, bold, italic, underline, strikethrough, separator, bullist, numlist, indent, outdent, separator, justifyleft, justifycenter, justifyright, justifyfull, separator, link, unlink, anchor, image, hr, separator, cut, copy, paste, undo, redo, separator, table, sub, sup, forecolor, backcolor, charmap, separator, code, fullscreen, wordpress, wphelp";
			*/
/*	
			$button_default['panel_button1'] = "bold,italic, strikethrough, separator, bullist, numlist, outdent, indent, separator, justifyleft, justifycenter, justifyright ,separator, link, unlink, image, wordpress, separator, undo, redo, code, wphelp";
*/
			$button_default['panel_button1'] = "";
			$button_default['panel_button2'] = "";
			$button_default['panel_button3'] = "";	
			
			$this->button_info[] = $button_default;
		}
	}
	
	
	function load_button_profile($button_name)
	{
		foreach($this->button_info as $btn) 
		{
			if ($btn['panel_name'] == $button_name)
				return $btn;
		}
		return $this->button_info[0];
	}


	function button_manager_page() 
	{
		if (($_REQUEST['panel_action'] == "save_copy") 
		 || ($_REQUEST['panel_action'] == "save_edit")
		 || ($_REQUEST['panel_action'] == "delete"))
		{
			if ($this->update_panel())
			{
				?>
				<div class="updated">
					<p>Panel successfully updated.</p>
				</div>
				<?php
			}
		}
		
		if ($_REQUEST['panel_action'] == "update_users")
		{
			if ($this->update_panel())
			{
				?>
				<div class="updated">
					<p>User successfully updated.</p>
				</div>
				<?php
			}
		}
		?>
		<div class="wrap">
			<?php $this->display_roles_panels(); ?>
			<hr>
			<?php $this->display_buttons_editor(); ?>
			<h2>Instructions:</h2>
			<ol>
				<li>Define a new WYSIWYG Panel. This will allow you to define three rows of buttons for the WordPress Editor screens.</li>
				<li>Under the User Panels section select the WYSIWYG Panel for the user to use from the dropdown.</li>
			</ol>
			<p>The 'WP-default' Panel is an empty panel and will force the current WordPress WYSIWYG buttons to be used.</p>
		</div>
		<?php
	}

	function display_roles_panels()
	{
		?>
		<table cellpadding="3" cellspacing="0" width="80%" border="0">
		<tr>
			<td width="50%" valign="top"><?php $this->display_panels(); ?></td>
			<td width="50%" valign="top"><?php $this->display_roles(); ?></td>
		</tr>
		</table>
		<?php
	}
	
	function delete_usermeta_by_panels($panel_name)
	{
		global $wpdb;
		
		$sql_str = "DELETE FROM $wpdb->usermeta WHERE meta_key='WYSIWYG_panel' AND meta_value='" . $panel_name. "'";
		$wpdb->get_col($sql_str);
	}

	
	function display_roles()
	{
		global $wp_roles, $wpdb;
		
		$userids = $wpdb->get_col("SELECT ID FROM $wpdb->users;");
		foreach($userids as $userid) 
		{
			$tmp_user = new WP_User($userid);
			$roles = $tmp_user->roles;
			$role = array_shift($roles);
			$roleclasses[$role][$tmp_user->user_login] = $tmp_user;
		}	
		//echo "roleclasses<pre>"; print_r($roleclasses); echo "</pre>"; 
		?>
		<h2>User Panels</h2>
		<form action="" method="post" name="updateusers" id="updateusers">
		<input type="hidden" name="panel_action" value="update_users" />
	
			<table cellpadding="2" cellspacing="0" width="90%" border="0">
			<tr>
				<th><?php _e('Role/User Name') ?></th>
				<th><?php _e('WYSIWYG Button Panel') ?></th>
			</tr>
			<tr>
				<td colspan="2"><hr></td>
			</tr>						
				<?php
					foreach($roleclasses as $role => $roleclass) 
					{
						ksort($roleclass);
						?>
						<tr>
							<td><strong><?php echo $wp_roles->role_names[$role]; ?></strong></td>
							<td>&nbsp;</td>
						</tr>						
						<tr>
							<td colspan="2"><hr></td>
						</tr>						
						<?php
							$style = '';
							foreach ($roleclass as $user_object) 
							{
								$style = ('class="alternate"' == $style) ? '' : 'class="alternate"';
								?>
								<tr $style>
									<td>
										<?php echo $user_object->user_login; ?>
									</td>
									<td>
										<select name="users_panel[<?php echo $user_object->ID ?>]" style="width: 250px">
										<?php 
											$user_panel = get_usermeta($user_object->ID, "WYSIWYG_panel");
											//$button_info = $this->load_button_panels();
											foreach($this->button_info as $btn) 
											{
												?>
												<option value="<?php echo $btn['panel_name'] ?>"
													<?php
														if ($user_panel == $btn['panel_name'])
															echo "selected";
													?>
												><?php echo $btn['panel_name'] ?></option>
												<?php
											}
										?>
										</select>
									</td>
	
		
								</tr>
								<?php
							}
							?>
							<tr><td colspan="2"><hr></td></tr>
							<?php
					}
				?>
				<tr>
					<td> </td>
					<td><input type="submit" name="Submit" value="Update Users" /></td>
				</tr>
	
			</table>
		</form>
	
		<?php
	}
	
	function display_panels()
	{
		?>
		<h2>WYSIWYG Panels</h2>
		<table cellpadding="2" cellspacing="0" width="100%" border="0">
		<tr>
			<th><?php _e('Action') ?></th>
			<th><?php _e('WYSIWYG Button Panel') ?></th>
		</tr>
		<tr>
			<td colspan="2"><hr></td>
		</tr>
			<?php
				if (count($this->button_info))
				{
					foreach($this->button_info as $btn) 
					{
						?>
						<tr>
							<td>
								<?php
									if ($btn['panel_name'] != "WP-default")
									{ 
										?>
										<a href="?page=<?php echo $this->page_name ?>&amp;panel_action=delete&amp;panel_name=<?php echo $btn['panel_name'] ?>">Delete</a> <a href="?page=<?php echo $this->page_name ?>&amp;panel_action=edit&amp;panel_name=<?php echo $btn['panel_name'] ?>">Edit</a>
										<?php
									} 
								?> <a href="?page=<?php echo $this->page_name ?>&amp;panel_action=copy&amp;panel_name=<?php echo $btn['panel_name'] ?>">Copy</a> 
								</td>
							<td><?php echo $btn['panel_name'] ?></td>
						</tr>
						<?php
					}
				}
			?>
		</table>
		<?php
	}
	
	
	function display_buttons_editor()
	{
		$panel_action="";
		if ($_REQUEST['panel_action'] == "edit")
		{
			$button_info  = $this->load_button_profile($_REQUEST['panel_name']);
			$panel_action = "save_edit";
		}
		else if ($_REQUEST['panel_action'] == "copy")
		{
			$button_info  = $this->load_button_profile($_REQUEST['panel_name']);
			$button_info['panel_name'] = $button_info['panel_name'] . " " . date("Ymd H:i:s");
			$panel_action = "save_copy";
		}
		else
			return;
			
		?>
		<h2>Define WYSIWYG Button rows</h2>
		<form action="" method="GET" name="updatepanel" id="updatepanel">
			<input type="hidden" name="page" value="<?php echo $this->page_name; ?>" />
			<input type="hidden" name="panel_action" value="<?php echo $panel_action ?>" />
			<table cellpadding="3" cellspacing="3" width="90%" border="0">
			<tr>
				<th valign="top">Allowed MCE buttons:</th>
				<td>
					<?php
						foreach($this->allow_mce_buttons as $btn)
							echo $btn . ", ";
					?>
				</td>
			</tr>
			<tr>
				<th nowrap="nowrap" width="30%"><?php _e('Button Panel Name:') ?></th>
				<td><input name="panel_name" id="panel_name" value="<?php echo $button_info['panel_name'] ?>" size="30" maxlength="30" /> </td>
			</tr>
			<tr>
				<th nowrap="nowrap" valign="top"><?php _e('Buttons Row 1') ?></th>
				<td><textarea name="panel_button1" id="panel_button1" rows="15" cols="40"><?php echo $button_info['panel_button1']; ?></textarea></td>
			</tr>
			<tr>
				<th nowrap="nowrap" valign="top"><?php _e('Buttons Row 2') ?></th>
				<td><textarea name="panel_button2" id="panel_button2" rows="15" cols="40"><?php echo $button_info['panel_button2']; ?></textarea></td>
			</tr>
			<tr>
				<th nowrap="nowrap" valign="top"><?php _e('Buttons Row 3') ?></th>
				<td><textarea name="panel_button3" id="panel_button3" rows="15" cols="40"><?php echo $button_info['panel_button3']; ?></textarea></td>
			</tr>
			<tr>
				<td> </td>
				<td><input type="submit" name="Submit" value="save_panel" /></td>
			</tr>
			</table>
		</form>
		<hr>
		<?
	}

	function update_panel()
	{
		if ($_REQUEST['panel_action'] == "update_users")
		{
			if (count($_REQUEST['users_panel']))
			{
				$users_panel = array();
				$users_panel = $_REQUEST['users_panel'];
				foreach ($users_panel as $up_idx => $up_data)
				{
					$author_data = get_userdata($up_idx);
					update_usermeta($up_idx, "WYSIWYG_panel", $up_data);
				}
			}
		}
		if ($_REQUEST['panel_action'] == "delete")
		{
			$this->delete_usermeta_by_panels($_REQUEST['panel_name']);
	
			foreach($this->button_info as $btn_idx => $btn) 
			{
				if ($btn['panel_name'] == $_REQUEST['panel_name'])
				{
					unset($this->button_info[$btn_idx]);
					update_option($this->options_key, serialize($this->button_info));
					return true;				
				}
			}
		}
		else 
		{
			if (!isset($_REQUEST['panel_name'])) return;
			if (!isset($_REQUEST['panel_button1'])) return false;
			if (!isset($_REQUEST['panel_button2'])) return false;
			if (!isset($_REQUEST['panel_button3'])) return false;
			
			foreach($this->button_info as $btn_idx => $btn) 
			{
				if ($btn['panel_name'] == $_REQUEST['panel_name'])
				{
					// Updat the existing button
					$btn['panel_name'] = $_REQUEST['panel_name'];
					$btn['panel_button1'] = $_REQUEST['panel_button1'];
					$btn['panel_button2'] = $_REQUEST['panel_button2'];
					$btn['panel_button3'] = $_REQUEST['panel_button3'];
					$this->button_info[$btn_idx] = $btn;
					update_option($this->options_key, serialize($this->button_info));
					return true;
				}
			}
			
			// If here then we didn't find a match. So add a new one.
			$btn = array();
			$btn['panel_name'] = $_REQUEST['panel_name'];
			$btn['panel_button1'] = $_REQUEST['panel_button1'];
			$btn['panel_button2'] = $_REQUEST['panel_button2'];
			$btn['panel_button3'] = $_REQUEST['panel_button3'];
			$this->button_info[] = $btn;
			update_option($this->options_key, serialize($this->button_info));
			return true;
		}
	}
		

///////////////////////////////////////////////////////////////////////////////////////////////////

	function extended_editor_mce_plugins($plugins) 
	{
		array_push($plugins, "table", "fullscreen");
		return $plugins;
	}

	function user_mce_buttons_1($buttons) 
	{
		global $user_ID;
		
		$user_panel = get_usermeta($user_ID, "WYSIWYG_panel");
		//echo "user_panel=[" . $user_panel . "]<br>";
	
		if (strlen($user_panel))
		{
			foreach($this->button_info as $btn) 
			{
				if ($btn['panel_name'] == $user_panel)
				{
					if (strlen($btn['panel_button1']))
					{
						$btn_array = split(",", $btn['panel_button1']);
						return $btn_array;
					}
				}
			}
		}
		return $buttons;
	}
	
	function user_mce_buttons_2($buttons) 
	{
		global $user_ID;
	
		$user_panel = get_usermeta($user_ID, "WYSIWYG_panel");
		if (strlen($user_panel))
		{
			foreach($this->button_info as $btn) 
			{
				if ($btn['panel_name'] == $user_panel)
				{
					if (strlen($btn['panel_button2']))
					{
						$btn_array = split(",", $btn['panel_button2']);
						return $btn_array;
					}
				}
			}
		}
		return $buttons;
	}
	
	function user_mce_buttons_3($buttons) 
	{
		global $user_ID;
	
		$user_panel = get_usermeta($user_ID, "WYSIWYG_panel");

		if (strlen($user_panel))
		{
			foreach($this->button_info as $btn) 
			{
				if ($btn['panel_name'] == $user_panel)
				{
					if (strlen($btn['panel_button3']))
					{
						$btn_array = split(",", $btn['panel_button3']);
						return $btn_array;
					}
				}
			}
		}
		return $buttons;
	}

}
$wp_wysbtnmgr = new CH_WYSBtnMgrPlugin();
?>