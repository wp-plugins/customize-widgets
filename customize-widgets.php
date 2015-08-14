<?php
/**
 * Plugin Name: Customize Widgets
 * Plugin URI: http://jess-mann.com
 * Description: Widgets cluttering up your site? This plugin allows you to hide unused widgets from appearing on the widgets page.
 * Version: 1.0
 * Author: Jess Mann
 * Author URI: http://jess-mann.com
 * License: GPLv2 or later
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

global $wp_registered_widgets;

/**
 * The cw_Widgets_Manager class records which widgets are currently loaded. It is a singleton with one primary method, cw_Widgets_Manager::getWidgets()
 */
class cw_Widgets_Manager 
{
	protected static $_instance;
	protected static $_widget_list;

	public function __construct()
	{
		self::$_instance = $this;
	}

	public static function getInstance()
	{
		if (!isset(self::$_instance))
		{
			self::$_instance = new cw_Widgets_Manager();
		}
		return self::$_instance;
	}

	/**
	 * @return array of registered widgets, including the widget name
	 */
	public static function getWidgets()
	{
		if (!isset(self::$_widget_list))
		{
    			if ( empty ( $GLOBALS['wp_widget_factory'] ) )
			        return;
			
			$widgets = array();
			$widget_list = $GLOBALS['wp_widget_factory']->widgets;
			foreach ($widget_list as $k => $v)
			{
				$widgets[$k] = $v->name;
			}
			
			self::$_widget_list = $widgets;
		}
		return self::$_widget_list;
	}
}

/** Add the admin menu */
add_action( 'admin_menu', 'customize_widgets_menu' );

function customize_widgets_menu() 
{
	add_submenu_page( 'themes.php', 'Customize Widgets Options', 'Customize Widgets', 'edit_theme_options', 'customize-widgets', 'customize_widgets_options' );
}

function customize_widgets_options() 
{
	global $wp_registered_widgets;

	//must check that the user has the required capability 
	if (!current_user_can('edit_theme_options'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	// See if the user has submitted the form
	// If they did, this hidden field will be set to 'Y'
	if( isset($_POST['disabled']) ):

		// Save the posted value in the database
		update_option( 'cw_disabled_widgets', $_POST['disabled'] );

		// Put an settings updated message on the screen
		?>
		<div class="updated"><p><strong><?php _e('settings saved.', 'customize-widgets' ); ?></strong></p></div>
		<?php

	endif;

	// Read in existing option value from database
	$widget_list = cw_Widgets_Manager::getWidgets();
	$list = get_option( 'cw_disabled_widgets' );
	$disabled_widgets = explode(',',$list);

	// Now display the settings editing screen
	echo '<div class="wrap">';
	echo "<h2>" . __( 'Customize Available Widgets', 'customize-widgets' ) . "</h2>";

	// settings form
	?>

	<link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ) ?>/style.css" type="text/css" /> 
	<h3>Available Widgets</h3>
	<p>
		This page shows the widgets that are currently enabled. Greyed out widgets are disabled, and will not appear available for use
		throughout the rest of the administrative backend. Click on any widget here to toggle its availability, and disable any widgets
		that you do not wish to clutter up your widget sections while editing.
	</p>

	<form id="customize-widgets" name="form1" method="post" action="">
	<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
	<input id="disabled" type="hidden" name="disabled" value="">

	<ul>
		<?php foreach($widget_list as $widget => $name):
			$disabled = false;
			if (in_array($widget,$disabled_widgets))
				$disabled = true;	
			echo '<label class="disable_'.$widget . ($disabled ? ' active' : '').'" for="disable_'.$widget.'"><input type="checkbox" '. ($disabled ? 'checked="checked" ' : '') .'name="disable_'.$widget.'" value="'.$widget.'" />'.$name.'</label>';
			
		endforeach; ?>
	</ul>

	<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
	</p>
	
	</form>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#customize-widgets label').click(function(e) {
				$(this).toggleClass('active');
				/** Use .active instead of :checked to be sure the ones ''appearing'' active are the ones we use, in case of js error */
				var list = $('#customize-widgets label.active input[type=checkbox]').map(function() { return this.value; }).get().join(',')
				$('#customize-widgets #disabled').val(list);
			});
		});
	</script>

<?php
 
}

/** Unregister all unwanted widgets on widgets_init */
function cw_unregister_widgets() 
{
	//Populate the widget list before unregistering anything
	$widget_list = cw_Widgets_Manager::getWidgets();

	$list = get_option('cw_disabled_widgets');
	$widgets = explode(',',$list);

	foreach ($widgets as $widget)
	{
		unregister_widget($widget);
	}
}
add_action( 'widgets_init', 'cw_unregister_widgets', 20 );
