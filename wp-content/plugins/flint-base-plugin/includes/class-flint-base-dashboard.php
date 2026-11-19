<?php
defined('ABSPATH') || exit;
final class Flint_Base_Dashboard {
 private static $instance;
 public static function instance(){if(!self::$instance)self::$instance=new self();return self::$instance;}
 private function __construct(){
  add_action('init',array($this,'sync_editor_capabilities'),20);
  add_action('admin_menu',array($this,'filter_admin_menus'),9999);
  add_action('admin_init',array($this,'guard_appearance_pages'),0);
  add_action('load-customize.php',array($this,'guard_customize'),0);
  add_action('load-nav-menus.php',array($this,'guard_menus'),0);
  add_action('load-themes.php',array($this,'deny_other_appearance'),0);
  add_action('load-widgets.php',array($this,'deny_other_appearance'),0);
  add_action('load-theme-editor.php',array($this,'deny_other_appearance'),0);
  add_action('load-site-editor.php',array($this,'deny_other_appearance'),0);
 }
 public function sync_editor_capabilities(){
  $role=get_role('editor'); if(!$role)return;
  $cfg=(array)flint_base_get_setting('editor_capabilities',array());
  $groups=array(
   'gravity_forms'=>array('gravityforms_create_form','gravityforms_edit_forms','gravityforms_view_entries','gravityforms_edit_entries'),
   'view_plugins'=>array('activate_plugins'),
   'update_plugins'=>array('update_plugins'),
   'manage_plugins'=>array('install_plugins','upload_plugins','delete_plugins'),
   'manage_users'=>array('list_users','create_users','edit_users','promote_users'),
  );
  foreach($groups as $key=>$caps) foreach($caps as $cap){ if(!empty($cfg[$key]))$role->add_cap($cap); else $role->remove_cap($cap); }
  // WordPress uses edit_theme_options for both Menus and Customize. Flint grants
  // that shared capability when either is enabled, then explicitly allowlists the
  // requested Appearance screen and blocks every other Appearance destination.
  if(!empty($cfg['appearance_customize']) || !empty($cfg['appearance_menus'])) $role->add_cap('edit_theme_options');
  else $role->remove_cap('edit_theme_options');
 }
 public function filter_admin_menus(){
  if(flint_base_feature_enabled('hide_comments_menu')) remove_menu_page('edit-comments.php');
  if(!$this->is_editor_user()) return;
  $cfg=(array)flint_base_get_setting('editor_capabilities',array());

  // Remove every Appearance submenu first. Re-add only the Flint-authorized
  // destinations. This prevents the shared edit_theme_options capability from
  // exposing Themes, Design/Site Editor, Widgets, Fonts, or theme editors.
  global $submenu;
  if(isset($submenu['themes.php'])) $submenu['themes.php']=array();

  if(!empty($cfg['appearance_customize'])){
   $url='customize.php?return='.rawurlencode(wp_unslash($_SERVER['REQUEST_URI'] ?? admin_url()));
   add_submenu_page('themes.php',__('Customize'),__('Customize'),'edit_theme_options',$url,'');
  }
  if(!empty($cfg['appearance_menus'])){
   add_submenu_page('themes.php',__('Menus'),__('Menus'),'edit_theme_options','nav-menus.php','');
  }
 }
 private function is_editor_user(){ $u=wp_get_current_user(); return in_array('editor',(array)$u->roles,true) && !current_user_can('manage_options'); }
 private function cfg(){ return (array)flint_base_get_setting('editor_capabilities',array()); }
 private function deny($message){ wp_die(esc_html($message),esc_html__('Access denied','flint-base'),array('response'=>403)); }
 public function guard_customize(){ if($this->is_editor_user() && empty($this->cfg()['appearance_customize'])) $this->deny(__('You do not have permission to access the Customizer.','flint-base')); }
 public function guard_menus(){ if($this->is_editor_user() && empty($this->cfg()['appearance_menus'])) $this->deny(__('You do not have permission to manage menus.','flint-base')); }
 public function deny_other_appearance(){ if($this->is_editor_user()) $this->deny(__('This Appearance section is not available to the Editor role.','flint-base')); }
 public function guard_appearance_pages(){
  if(!$this->is_editor_user()) return;
  $cfg=$this->cfg();
  global $pagenow;
  if('customize.php'===$pagenow && empty($cfg['appearance_customize'])) $this->deny(__('You do not have permission to access the Customizer.','flint-base'));
  if('nav-menus.php'===$pagenow && empty($cfg['appearance_menus'])) $this->deny(__('You do not have permission to manage menus.','flint-base'));
  if(in_array($pagenow,array('themes.php','widgets.php','theme-editor.php','site-editor.php'),true)) $this->deny(__('This Appearance section is not available to the Editor role.','flint-base'));
 }
}
