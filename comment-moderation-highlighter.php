<?php
/**
 * Plugin Name: Comment Moderation Highlighter
 * Plugin URI: http://www.superblogme.com/comment-moderation-highlighter/
 * Description: Highlights key phrases in comments during moderation to better spot manual spam or troll remarks.
 * Version: 0.2
 * Released: August 24th, 2014
 * Author: Super Blog Me
 * Author URI: http://www.superblogme.com
 * License: GPL2
 **/

define('COMMENT_MODERATION_HIGHLIGHTER_VERSION', '0.2');

defined('ABSPATH') or die ("Oops! This is a WordPress plugin and should not be called directly.\n");

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

register_activation_hook( __FILE__, 'cmh_activation' );
register_deactivation_hook( __FILE__, 'cmh_deactivation' );
add_action('admin_init', 'cmh_admin_init' );
add_action('admin_menu', 'cmh_add_options');

add_filter('comment_text', 'cmh_check_duplicate', 98);
add_filter('comment_text', 'cmh_moderate_comment', 99);
add_filter('comment_author', 'cmh_moderate_comment', 99);
add_filter('get_comment_author_IP', 'cmh_moderate_comment', 99);

function cmh_activation() {
	add_option('cmh_highlight_keywords', '');
}

function cmh_deactivation() {
}

function cmh_admin_init() {
        wp_register_style( 'cmhStyleSheet', plugins_url('comment-moderation-highlighter.css', __FILE__) );
}

function cmh_add_options() {
        if (function_exists('add_options_page')) {
                $page = add_options_page('Comment Moderation Highlighter Options', 'Comment Highlighter', 'manage_options', 'comment-moderation-highlighter', 'cmh_options_page');
        	add_action( 'admin_print_styles-' . $page, 'cmh_options_styles' );
        }
}

function cmh_options_styles() {
        wp_enqueue_style('cmhStyleSheet');
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

#add 'settings' link directly to plugin page
add_filter('plugin_action_links', 'cmh_plugin_action_links', 10, 2);

function cmh_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=comment-moderation-highlighter">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function cmh_options_page() {
        ?>
        <div class='wrap cmh'>
	<h2>Comment Moderation Highlighter v<?php echo COMMENT_MODERATION_HIGHLIGHTER_VERSION; ?></h2>
        <a target='_blank' href="http://wordpress.org/support/plugin/comment-moderation-highlighter" class="ibutton btnblack">Support Forum</a>
        <a target='_blank' href="http://wordpress.org/support/view/plugin-reviews/comment-moderation-highlighter#postform" class="ibutton btnblack">Leave a review</a>
        <a target='_blank' href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K3TGDA64L7WRG" class="ibutton btnblack">Donations</a>
	<p>
	This plugin will highlight keywords you specify on the admin moderation page, making it easier to spot manual spam and/or troll comments.
	</p>
	<?php
        if (!current_user_can('manage_options')) {
                ?><div id="message" class="error"><p><strong>
                You do not have permission to manage options.
                </strong></p></div><?php
        }
        else if (isset($_POST['update_options'])) {
                ?><div id="message" class="updated fade"><p><strong><?php
                update_option('cmh_highlight_keywords', $_POST['cmh_highlight_keywords']);
               	echo "Options Saved!";
            ?></strong></p></div><?php
        }
        ?>

        <hr />

        <?php cmh_main_options(); ?>

	</div>
<?php
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function cmh_main_options() {
?>
        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
        <fieldset class="options">
        <h3>Comment Moderation Highlighter Options</h3>

        <table width="100%" border="0" cellspacing="0" cellpadding="6">
        <tr valign="top"><td width="33%" align="right">
                <strong>Keywords to Highlight</strong>
<p/>
When a comment contains any of these words in its content, name, or IP, it will be highlighted on the admin moderation page. One word or IP per line. It will match inside words, so 'press' will match inside 'WordPress'.
        </td><td align="left">
<textarea rows="20" cols="40" name="cmh_highlight_keywords">
<?php echo get_option('cmh_highlight_keywords'); ?>
</textarea>
        </td></tr>
        </table>
        </fieldset>
        <div class="submit" style="text-align:center;">
                <input type="submit" name="update_options" value="<?php _e('Update options'); ?> &raquo;" />
        </div>
        </form>
<?php
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function cmh_check_duplicate($commentdata) {
global $pagenow;
    if ($pagenow !== 'edit-comments.php') { return $commentdata; }

	// check for duplicate pending comments
	$args = array( 'status' => 'hold' );
	$comments = get_comments( $args );
	$num = 0;
	$c = trim($commentdata);
	$c = strip_tags($c, '<p>');
	$c = strip_tags($c, '</p>');
	foreach ( $comments as $comment ) {
		if ( $comment->comment_content == $c ) $num++;
		if ( $num > 1 ) break; // don't need to check any more...
	}

	if ( $num > 1 ) {
		$replace = "<span style='color:#ff0; background-color:#000;'>" . $c . "</span>";
		$commentdata = str_ireplace($c,$replace,$commentdata);
	}
	return $commentdata;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function cmh_moderate_comment($commentdata) {
global $pagenow;
    if ($pagenow !== 'edit-comments.php') { return $commentdata; }

	$highlights = get_option('cmh_highlight_keywords');
	if (!empty($highlights)) {
		$keys = explode("\n",$highlights);
		foreach ($keys as $key) {
			$key = trim($key);
			if (empty($key)) continue;
			$replace = "<span style='background-color:#ff0;'>" . $key . "</span>";
			$commentdata = str_ireplace($key,$replace,$commentdata);
		}
	}
	return $commentdata;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

?>
