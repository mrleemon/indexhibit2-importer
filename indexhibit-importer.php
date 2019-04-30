<?php
/*
Plugin Name: Indexhibit Importer
Plugin URI: http://wordpress.org/extend/plugins/indexhibit-importer/
Description: Import categories, users, posts, comments, and links from an Indexhibit site.
Author: leemon
Author URI: http://wordpress.org/
Version: 0.2
License: GPL v2
*/

if ( !defined( 'WP_LOAD_IMPORTERS' ) ) {
    return;
}

/**
 * Add These Functions to make our lives easier
 */

if ( !function_exists( 'get_comment_count' ) ) {
	/**
	 * Get the comment count for posts.
	 *
	 * @package WordPress
	 * @subpackage Indexhibit_Import
	 *
	 * @param int $post_ID Post ID
	 * @return int
	 */
	function get_comment_count( $post_ID ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $wpdb->comments WHERE comment_post_ID = %d", $post_ID ) );
	}
}

/**
 * Convert from dotclear charset to utf8 if required
 *
 * @package WordPress
 * @subpackage Dotclear_Import
 *
 * @param string $s
 * @return string
 */
function csc( $s ) {
	if ( seems_utf8( $s ) ) {
		return $s;
	} else {
		return iconv( get_option ( "dccharset" ), "UTF-8", $s );
	}
}

/**
 * @package WordPress
 * @subpackage Indexhibit_Import
 *
 * @param string $s
 * @return string
 */
function textconv( $s ) {
	return csc( preg_replace( '|( ?<!<br /> )\s*\n|', ' ', $s ) );
}

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) ) {
        require_once $class_wp_importer;
    }
}

/**
 * Indexhibit Importer
 *
 * @package WordPress
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {
class Indexhibit_Import extends WP_Importer {

	function header() {
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>' . __( 'Import Indexhibit', 'indexhibit-importer' ) . '</h2>';
		echo '<p>' . __( 'Steps may take a few minutes depending on the size of your database. Please be patient.', 'indexhibit-importer' ) . '</p>';
	}

	function footer() {
		echo '</div>';
	}

	function greet() {
		echo '<div class="narrow"><p>' . __( 'Howdy! This importer allows you to extract posts from an Indexhibit database into your WordPress site.  Mileage may vary.', 'indexhibit-importer' ) . '</p>';
		echo '<p>' . __( 'Your Indexhibit Configuration settings are as follows:', 'indexhibit-importer' ) . '</p>';
		echo '<form action="admin.php?import=indexhibit&amp;step=1" method="post">';
		wp_nonce_field( 'import-indexhibit' );
		$this->db_form();
		echo '<p class="submit"><input type="submit" name="submit" class="button" value="' . esc_attr__( 'Import Categories', 'indexhibit-importer' ) . '" /></p>';
		echo '</form></div>';
	}

	function get_dc_cats() {
		global $wpdb;
		// General Housekeeping
		$dcdb = new wpdb( get_option( 'dcuser' ), get_option( 'dcpass' ), get_option( 'dcname' ), get_option( 'dchost' ) );
		set_magic_quotes_runtime( 0 );
		$dbprefix = get_option( 'dcdbprefix' );

		// Get Categories
		return $dcdb->get_results( 'SELECT * FROM '.$dbprefix.'categorie', ARRAY_A );
	}

	function get_dc_users()	{
		global $wpdb;
		// General Housekeeping
		$dcdb = new wpdb( get_option( 'dcuser' ), get_option( 'dcpass' ), get_option( 'dcname' ), get_option( 'dchost' ) );
		set_magic_quotes_runtime( 0 );
		$dbprefix = get_option( 'dcdbprefix' );

		// Get Users

		return $dcdb->get_results( 'SELECT * FROM '.$dbprefix.'user', ARRAY_A );
	}

	function get_dc_posts()	{
		// General Housekeeping
		$dcdb = new wpdb( get_option( 'dcuser' ), get_option( 'dcpass' ), get_option( 'dcname' ), get_option( 'dchost' ) );
		set_magic_quotes_runtime( 0 );
		$dbprefix = get_option( 'dcdbprefix' );

		// Get Posts
		/* return $dcdb->get_results( 'SELECT ' . $dbprefix . 'post.*, ' . $dbprefix . 'categorie.cat_libelle_url AS post_cat_name
						FROM ' . $dbprefix . 'post INNER JOIN ' . $dbprefix . 'categorie
                        ON ' . $dbprefix . 'post.cat_id = ' . $dbprefix . 'categorie.cat_id', ARRAY_A ); */

        // Get Posts
		return $dcdb->get_results( 'SELECT ' . $dbprefix . 'objects.* FROM ' . $dbprefix . 'objects', ARRAY_A );

	}

	function users2wp( $users = '' ) {
		// General Housekeeping
		global $wpdb;
		$count = 0;
		$dcid2wpid = array();

		// Midnight Mojo
		if ( is_array( $users ) ) {
			echo '<p>'.__( 'Importing Users...', 'indexhibit-importer' ).'<br /><br /></p>';
			foreach ( $users as $user ) {
				$count++;
				extract( $user );

				// Make Nice Variables
				$name = $wpdb->escape( csc( $name ) );
				$RealName = $wpdb->escape( csc( $user_pseudo ) );

				if ( $uinfo = get_userdatabylogin( $name ) ) {

					$ret_id = wp_insert_user( array(
								'ID'		    => $uinfo->ID,
								'user_login'	=> $user_id,
								'user_nicename'	=> $Realname,
								'user_email'	=> $user_email,
								'user_url'	    => 'http://',
								'display_name'	=> $Realname)
								);
				} else {
					$ret_id = wp_insert_user(array(
								'user_login'	=> $user_id,
								'user_nicename'	=> csc ( $user_pseudo ),
								'user_email'	=> $user_email,
								'user_url'	    => 'http://',
								'display_name'	=> $Realname)
								);
				}
				$dcid2wpid[$user_id] = $ret_id;

				// Set Indexhibit-to-WordPress permissions translation

				// Update Usermeta Data
				$user = new WP_User( $ret_id );
				$wp_perms = $user_level + 1;
				if ( 10 == $wp_perms ) {
                    $user->set_role( 'administrator' );
                } else if ( 9  == $wp_perms ) {
                    $user->set_role( 'editor' );
                } else if ( 5  <= $wp_perms ) {
                    $user->set_role( 'editor' );
                } else if ( 4  <= $wp_perms ) {
                    $user->set_role( 'author' );
                } else if ( 3  <= $wp_perms ) {
                    $user->set_role( 'contributor' );
                } else if ( 2  <= $wp_perms ) {
                    $user->set_role( 'contributor' );
                } else {
                    $user->set_role( 'subscriber' );
                }

				update_user_meta( $ret_id, 'wp_user_level', $wp_perms );
				update_user_meta( $ret_id, 'rich_editing', 'false' );
				update_user_meta( $ret_id, 'first_name', csc( $user_prenom ) );
				update_user_meta( $ret_id, 'last_name', csc( $user_nom ) );
			}// End foreach( $users as $user )

			// Store id translation array for future use
			add_option( 'dcid2wpid', $dcid2wpid );

			echo '<p>' . sprintf( __( 'Done! <strong>%1$s</strong> users imported.', 'indexhibit-importer' ), $count ) . '<br /><br /></p>';
			return true;
		}// End if(is_array( $users )

		echo __( 'No Users to Import!', 'indexhibit-importer' );
		return false;

	}// End function user2wp()

	function posts2wp( $posts = '' ) {
		// General Housekeeping
		global $wpdb;
		$count = 0;
		$dcposts2wpposts = array();
		$cats = array();

		// Do the Magic
		if ( is_array( $posts ) ) {
			echo '<p>' . __( 'Importing Posts...', 'indexhibit-importer' ) . '<br /><br /></p>';
			foreach ( $posts as $post ) {
				$count++;
				extract( $post );

				// Set Indexhibit-to-WordPress status translation
				$stattrans = array( 0 => 'draft', 1 => 'publish' );

				// Can we do this more efficiently?
				$uinfo = ( get_userdatabylogin( $user_id ) ) ? get_userdatabylogin( $user_id ) : 1;
				$authorid = ( is_object( $uinfo ) ) ? $uinfo->ID : $uinfo ;

                $post_author = get_current_user_id();
				$post_title = $wpdb->escape( csc( $title ) );
				$post_content = textconv ( $content );
				$post_content = $wpdb->escape( $post_content );
				$post_status = $stattrans[$status];

				// Import Post data into WordPress

				if ( $pinfo = post_exists( $post_title, $post_content ) ) {
					$ret_id = wp_insert_post( array(
							'ID'			    => $pinfo,
							'post_author'		=> $post_author,
							'post_date'		    => $pdate,
							'post_date_gmt'		=> $pdate,
							'post_modified'		=> $udate,
							'post_modified_gmt'	=> $udate,
							'post_title'		=> $post_title,
							'post_content'		=> $post_content,
							'post_status'		=> $post_status,
							'post_name'		    => $post_titre_url,
							'comment_status'	=> 'closed',
							'ping_status'		=> 'closed' )
							);
					if ( is_wp_error( $ret_id ) ) {
						return $ret_id;
                    }
				} else {
					$ret_id = wp_insert_post( array(
							'post_author'		=> $post_author,
							'post_date'		    => $pdate,
							'post_date_gmt'		=> $pdate,
							'post_modified'		=> $udate,
							'post_modified_gmt'	=> $udate,
							'post_title'		=> $post_title,
							'post_content'		=> $post_content,
							'post_status'		=> $post_status,
							'post_name'		    => $post_titre_url,
							'comment_status'	=> 'closed',
							'ping_status'		=> 'closed' )
							);
					if ( is_wp_error( $ret_id ) ) {
                        return $ret_id;
                    }
				}
				$dcposts2wpposts[$post_id] = $ret_id;

				// Make Post-to-Category associations
				$cats = array();
				$category1 = get_category_by_slug( $post_cat_name );
				$category1 = $category1->term_id;

				if ( $cat1 = $category1 ) {
                    $cats[1] = $cat1;
                }

				if ( !empty( $cats ) ) {
                    wp_set_post_categories( $ret_id, $cats );
                }
			}
		}
		// Store ID translation for later use
		add_option( 'dcposts2wpposts', $dcposts2wpposts );

		echo '<p>' . sprintf( __( 'Done! <strong>%1$s</strong> posts imported.', 'indexhibit-importer' ), $count ) . '<br /><br /></p>';
		return true;
	}

	function import_users() {
		// User Import
		$users = $this->get_dc_users();
		$this->users2wp( $users );

		echo '<form action="admin.php?import=indexhibit&amp;step=3" method="post">';
		wp_nonce_field( 'import-indexhibit' );
		printf( '<p class="submit"><input type="submit" name="submit" class="button" value="%s" /></p>', esc_attr__( 'Import Posts', 'indexhibit-importer' ) );
		echo '</form>';
	}

	function import_posts() {
		// Post Import
		$posts = $this->get_dc_posts();
		$result = $this->posts2wp( $posts );
		if ( is_wp_error( $result ) ) {
            return $result;
        }

		echo '<form action="admin.php?import=indexhibit&amp;step=4" method="post">';
		wp_nonce_field( 'import-indexhibit' );
		printf( '<p class="submit"><input type="submit" name="submit" class="button" value="%s" /></p>', esc_attr__( 'Import Comments', 'indexhibit-importer' ) );
		echo '</form>';
	}

	function cleanup_dcimport() {
		delete_option( 'dcdbprefix' );
		delete_option( 'dc_cats' );
		delete_option( 'dcid2wpid' );
		delete_option( 'dcposts2wpposts' );
		delete_option( 'dccm2wpcm' );
		delete_option( 'dcuser' );
		delete_option( 'dcpass' );
		delete_option( 'dcname' );
		delete_option( 'dchost' );
		delete_option( 'dccharset' );
		do_action( 'import_done', 'indexhibit' );
		$this->tips();
	}

	function tips() {
		echo '<p>'.__('Welcome to WordPress.  We hope ( and expect! ) that you will find this platform incredibly rewarding!  As a new WordPress user coming from Indexhibit, there are some things that we would like to point out.  Hopefully, they will help your transition go as smoothly as possible.', 'indexhibit-importer').'</p>';
		echo '<h3>'.__( 'Users', 'indexhibit-importer' ).'</h3>';
		echo '<p>'.sprintf(__( 'You have already setup WordPress and have been assigned an administrative login and password.  Forget it.  You didn&#8217;t have that login in Indexhibit, why should you have it here?  Instead we have taken care to import all of your users into our system.  Unfortunately there is one downside.  Because both WordPress and Indexhibit uses a strong encryption hash with passwords, it is impossible to decrypt it and we are forced to assign temporary passwords to all your users.  <strong>Every user has the same username, but their passwords are reset to password123.</strong>  So <a href="%1$s">Log in</a> and change it.', 'indexhibit-importer' ), '/wp-login.php').'</p>';
		echo '<h3>'.__( 'Preserving Authors', 'indexhibit-importer' ).'</h3>';
		echo '<p>'.__( 'Secondly, we have attempted to preserve post authors.  If you are the only author or contributor to your blog, then you are safe.  In most cases, we are successful in this preservation endeavor.  However, if we cannot ascertain the name of the writer due to discrepancies between database tables, we assign it to you, the administrative user.', 'indexhibit-importer' ).'</p>';
		echo '<h3>'.__( 'Textile', 'indexhibit-importer' ).'</h3>';
		echo '<p>'.__( 'Also, since you&#8217;re coming from Indexhibit, you probably have been using Textile to format your comments and posts.  If this is the case, we recommend downloading and installing <a href="http://www.huddledmasses.org/category/development/wordpress/textile/">Textile for WordPress</a>.  Trust me&#8230; You&#8217;ll want it.', 'indexhibit-importer' ).'</p>';
		echo '<h3>'.__( 'WordPress Resources', 'indexhibit-importer' ).'</h3>';
		echo '<p>'.__( 'Finally, there are numerous WordPress resources around the internet.  Some of them are:', 'indexhibit-importer' ).'</p>';
		echo '<ul>';
		echo '<li>'.__( '<a href="http://wordpress.org/">The official WordPress site</a>', 'indexhibit-importer' ).'</li>';
		echo '<li>'.__( '<a href="http://wordpress.org/support/">The WordPress support forums</a>', 'indexhibit-importer' ).'</li>';
		echo '<li>'.__('<a href="http://codex.wordpress.org/">The Codex ( In other words, the WordPress Bible )</a>', 'indexhibit-importer').'</li>';
		echo '</ul>';
		echo '<p>'.sprintf(__( 'That&#8217;s it! What are you waiting for? Go <a href="%1$s">log in</a>!', 'indexhibit-importer' ), '../wp-login.php').'</p>';
	}

	function db_form() {
		echo '<table class="form-table">';
		printf( '<tr><th><label for="dbuser">%s</label></th><td><input type="text" name="dbuser" id="dbuser" /></td></tr>', __( 'Indexhibit Database User:', 'indexhibit-importer' ) );
		printf( '<tr><th><label for="dbpass">%s</label></th><td><input type="password" name="dbpass" id="dbpass" /></td></tr>', __( 'Indexhibit Database Password:', 'indexhibit-importer' ) );
		printf( '<tr><th><label for="dbname">%s</label></th><td><input type="text" name="dbname" id="dbname" /></td></tr>', __( 'Indexhibit Database Name:', 'indexhibit-importer' ) );
		printf( '<tr><th><label for="dbhost">%s</label></th><td><input type="text" name="dbhost" id="dbhost" value="localhost" /></td></tr>', __( 'Indexhibit Database Host:', 'indexhibit-importer' ) );
		printf( '<tr><th><label for="dbprefix">%s</label></th><td><input type="text" name="dbprefix" id="dbprefix" value="dc_"/></td></tr>', __( 'Indexhibit Table prefix:', 'indexhibit-importer' ) );
		printf( '<tr><th><label for="dccharset">%s</label></th><td><input type="text" name="dccharset" id="dccharset" value="ISO-8859-15"/></td></tr>', __( 'Originating character set:', 'indexhibit-importer' ) );
		echo '</table>';
	}

	function dispatch() {

		if ( empty ( $_GET['step'] ) ) {
            $step = 0;
        } else {
            $step = ( int ) $_GET['step'];
        }
		$this->header();

		if ( $step > 0 ) {
			check_admin_referer( 'import-indexhibit' );

			if ( $_POST['dbuser'] ) {
				if ( get_option( 'dcuser' ) ) {
                    delete_option( 'dcuser' );
                }
				add_option( 'dcuser', sanitize_user( $_POST['dbuser'], true ) );
			}
			if ( $_POST['dbpass'] ) {
				if ( get_option( 'dcpass' ) ) {
                    delete_option( 'dcpass' );
                }
				add_option( 'dcpass', sanitize_user( $_POST['dbpass'], true ) );
			}

			if ( $_POST['dbname'] ) {
				if ( get_option( 'dcname' ) ) {
                    delete_option( 'dcname' );
                }
				add_option( 'dcname', sanitize_user( $_POST['dbname'], true ) );
			}
			if ( $_POST['dbhost'] ) {
				if ( get_option( 'dchost' ) ) {
                    delete_option( 'dchost' );
                }
				add_option( 'dchost', sanitize_user( $_POST['dbhost'], true ) );
			}
			if ( $_POST['dccharset'] ) {
				if ( get_option( 'dccharset' ) ) {
                    delete_option( 'dccharset' );
                }
				add_option( 'dccharset', sanitize_user( $_POST['dccharset'], true ) );
			}
			if ( $_POST['dbprefix'] ) {
				if ( get_option( 'dcdbprefix' ) ) {
                    delete_option( 'dcdbprefix' );
                }
				add_option( 'dcdbprefix', sanitize_user( $_POST['dbprefix'], true ) );
			}


		}

		switch ( $step ) {
			default:
			case 0 :
				$this->greet();
				break;
			case 1 :
				$this->import_categories();
				break;
			case 2 :
				$this->import_users();
				break;
			case 3 :
				$result = $this->import_posts();
				if ( is_wp_error( $result ) ) {
                    echo $result->get_error_message();
                }
				break;
			case 6 :
				$this->cleanup_dcimport();
				break;
		}

		$this->footer();
	}

	function __construct() {
		// Nothing.
	}
}

$dc_import = new Indexhibit_Import();

register_importer( 'indexhibit', __( 'Indexhibit', 'indexhibit-importer' ), __( 'Import categories, users, posts, comments, and links from an Indexhibit site.', 'indexhibit-importer' ), array ( $dc_import, 'dispatch' ) );

} // class_exists( 'WP_Importer' )

function indexhibit_importer_init() {
    load_plugin_textdomain( 'indexhibit-importer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'indexhibit_importer_init' );
