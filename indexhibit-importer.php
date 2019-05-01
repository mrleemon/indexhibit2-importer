<?php
/*
Plugin Name: Indexhibit Importer
Plugin URI: http://wordpress.org/extend/plugins/indexhibit-importer/
Description: Import posts from an Indexhibit site.
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
        return iconv( get_option ( "ixcharset" ), "UTF-8", $s );
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

    /**
     * Constructor
     */
    function __construct() {
        // Nothing.
    }

    /**
     * header
     */
    function header() {
        echo '<div class="wrap">';
        screen_icon();
        echo '<h2>' . __( 'Import Indexhibit', 'indexhibit-importer' ) . '</h2>';
        echo '<p>' . __( 'Steps may take a few minutes depending on the size of your database. Please be patient.', 'indexhibit-importer' ) . '</p>';
    }

    /**
     * footer
     */
    function footer() {
        echo '</div>';
    }

    /**
     * greet
     */
    function greet() {
        echo '<div class="narrow"><p>' . __( 'Howdy! This importer allows you to extract posts from an Indexhibit database into your WordPress site.  Mileage may vary.', 'indexhibit-importer' ) . '</p>';
        echo '<p>' . __( 'Your Indexhibit Configuration settings are as follows:', 'indexhibit-importer' ) . '</p>';
        echo '<form action="admin.php?import=indexhibit&amp;step=1" method="post">';
        wp_nonce_field( 'import-indexhibit' );
        $this->db_form();
        echo '<p class="submit"><input type="submit" name="submit" class="button" value="' . esc_attr__( 'Import Posts', 'indexhibit-importer' ) . '" /></p>';
        echo '</form></div>';
    }

    /**
     * get_ix_posts
     */
    function get_ix_posts() {
        // General Housekeeping
        $ixdb = new wpdb( get_option( 'ixuser' ), get_option( 'ixpass' ), get_option( 'ixname' ), get_option( 'ixhost' ) );
        //set_magic_quotes_runtime( 0 );
        $dbprefix = get_option( 'ixdbprefix' );

        // Get Posts
        /* return $ixdb->get_results( 'SELECT ' . $dbprefix . 'post.*, ' . $dbprefix . 'categorie.cat_libelle_url AS post_cat_name
                        FROM ' . $dbprefix . 'post INNER JOIN ' . $dbprefix . 'categorie
                        ON ' . $dbprefix . 'post.cat_id = ' . $dbprefix . 'categorie.cat_id', ARRAY_A ); */

        // Get posts
        return $ixdb->get_results( "SELECT " . $dbprefix . "objects.* FROM " . $dbprefix . "objects", ARRAY_A );

    }

    /**
     * get_ix_media
     */
    function get_ix_media( $post_id ) {
        // General Housekeeping
        $ixdb = new wpdb( get_option( 'ixuser' ), get_option( 'ixpass' ), get_option( 'ixname' ), get_option( 'ixhost' ) );
        //set_magic_quotes_runtime( 0 );
        $dbprefix = get_option( 'ixdbprefix' );

        // Get media from a specific post
        return $ixdb->get_results( "SELECT " . $dbprefix . "media.* FROM " . $dbprefix . "media WHERE media_ref_id = " . $post_id . " AND media_mime NOT IN ( 'youtube', 'vimeo' ) ORDER BY media_order ASC", ARRAY_A );

    }

    /**
     * posts2wp
     */
    function posts2wp( $posts = '' ) {
        // General Housekeeping
        global $wpdb;
        $count = 0;
        $ixposts2wpposts = array();

        // Do the Magic
        if ( is_array( $posts ) ) {
            echo '<p>' . __( 'Importing Posts...', 'indexhibit-importer' ) . '<br /><br /></p>';
            foreach ( $posts as $post ) {
                $count++;
                //extract( $post );

                // Set Indexhibit-to-WordPress status translation
                $stattrans = array(
                    0 => 'draft',
                    1 => 'publish'
                );

                // Can we do this more efficiently?
                //$uinfo = ( get_userdatabylogin( $user_id ) ) ? get_userdatabylogin( $user_id ) : 1;
                //$authorid = ( is_object( $uinfo ) ) ? $uinfo->ID : $uinfo ;

                $post_author = get_current_user_id();
                $post_title = $wpdb->escape( csc( $post['title'] ) );
                $post_content = textconv( $post['content'] );
                $post_content = $wpdb->escape( $post_content );
                $post_date = $post['pdate'];
                $post_modified = $post['udate'];
                $post_status = $stattrans[$post['status']];

                // Import Post data into WordPress

                if ( $pinfo = post_exists( $post_title, $post_content ) ) {
                    $ret_id = wp_insert_post( array(
                            'ID'                => $pinfo,
                            'post_author'       => $post_author,
                            'post_date'         => $post_date,
                            'post_date_gmt'     => $post_date,
                            'post_modified'     => $post_modified,
                            'post_modified_gmt' => $post_modified,
                            'post_title'        => $post_title,
                            'post_content'      => $post_content,
                            'post_status'       => $post_status,
                            'comment_status'    => 'closed',
                            'ping_status'       => 'closed' )
                            );
                    if ( is_wp_error( $ret_id ) ) {
                        return $ret_id;
                    }
                } else {
                    $ret_id = wp_insert_post( array(
                            'post_author'       => $post_author,
                            'post_date'         => $post_date,
                            'post_date_gmt'     => $post_date,
                            'post_modified'     => $post_modified,
                            'post_modified_gmt' => $post_modified,
                            'post_title'        => $post_title,
                            'post_content'      => $post_content,
                            'post_status'       => $post_status,
                            'comment_status'    => 'closed',
                            'ping_status'       => 'closed' )
                            );
                    if ( is_wp_error( $ret_id ) ) {
                        return $ret_id;
                    }
                }
                $ixposts2wpposts[$post['id']] = $ret_id;

                $media = $this->get_ix_media( $post['id'] );
                $result = $this->media2wp( $media, $ret_id );
                if ( is_wp_error( $result ) ) {
                    return $result;
                }

            }
        }
        // Store ID translation for later use
        add_option( 'ixposts2wpposts', $ixposts2wpposts );

        echo '<p>' . sprintf( __( 'Done! <strong>%1$s</strong> posts imported.', 'indexhibit-importer' ), $count ) . '<br /><br /></p>';
        return true;
    }

    /**
     * media2wp
     */
     function media2wp( $images = '', $post_id ) {
        // General Housekeeping
        global $wpdb;
        $count = 0;

        // Do the Magic
        if ( is_array( $images ) ) {
            echo '<p>' . __( 'Importing Media...', 'indexhibit-importer' ) . '<br /><br /></p>';
            foreach ( $images as $image ) {
                $count++;
                //extract( $image );
                $media_url = '/files/gimgs/' . $image['media_file'];
                process_attachment( $post, $media_url );
            }
        }

        //echo '<p>' . sprintf( __( 'Done! <strong>%1$s</strong> posts imported.', 'indexhibit-importer' ), $count ) . '<br /><br /></p>';
        return true;

    }

    /**
     * import_posts
     */
    function import_posts() {
        // Post Import
        $posts = $this->get_ix_posts();
        $result = $this->posts2wp( $posts );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        echo '<form action="admin.php?import=indexhibit&amp;step=2" method="post">';
        wp_nonce_field( 'import-indexhibit' );
        printf( '<p class="submit"><input type="submit" name="submit" class="button" value="%s" /></p>', esc_attr__( 'Finish', 'indexhibit-importer' ) );
        echo '</form>';
    }
    
    /**
     * process_attachment
     */
    function process_attachment( $post, $media_url ) {
        
        $pre_process = pre_process_attachment( $post, $media_url );
        if ( is_wp_error( $pre_process ) ) {
            return array(
                'fatal' => false,
                'type' => 'error',
                'code' => $pre_process->get_error_code(),
                'message' => $pre_process->get_error_message(),
                'text' => sprintf( __( '%1$s was not uploaded. (<strong>%2$s</strong>: %3$s)', 'indexhibit-importer' ), $post['post_title'], $pre_process->get_error_code(), $pre_process->get_error_message() )
            );
        }
        // if the URL is absolute, but does not contain address, then upload it assuming base_site_url
        if ( preg_match( '|^/[\w\W]+$|', $media_url ) ) {
            $media_url = rtrim( $this->base_url, '/' ) . $media_url;
        }
        $upload = fetch_remote_file( $media_url, $post );
        if ( is_wp_error( $upload ) ) {
            return array(
                'fatal' => ( $upload->get_error_code() == 'upload_dir_error' && $upload->get_error_message() != 'Invalid file type' ? true : false ),
                'type' => 'error',
                'code' => $upload->get_error_code(),
                'message' => $upload->get_error_message(),
                'text' => sprintf( __( '%1$s could not be uploaded because of an error. (<strong>%2$s</strong>: %3$s)', 'indexhibit-importer' ), $post['post_title'], $upload->get_error_code(), $upload->get_error_message() )
            );
        }
        if ( $info = wp_check_filetype( $upload['file'] ) ) {
            $post['post_mime_type'] = $info['type'];
        } else {
            $upload = new WP_Error( 'attachment_processing_error', __('Invalid file type', 'indexhibit-importer') );
            return array(
                'fatal' => false,
                'type' => 'error',
                'code' => $upload->get_error_code(),
                'message' => $upload->get_error_message(),
                'text' => sprintf( __( '%1$s could not be uploaded because of an error. (<strong>%2$s</strong>: %3$s)', 'indexhibit-importer' ), $post['post_title'], $upload->get_error_code(), $upload->get_error_message() )
            );
        }
        $post['guid'] = $upload['url'];
        // Set parent.
        $post['post_parent'] = //get_current_user_id();
        // Set author.
        $post['post_author'] = get_current_user_id();
        // as per wp-admin/includes/upload.php
        $post_id = wp_insert_attachment( $post, $upload['file'] );
        wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $upload['file'] ) );
        // remap image URL's
        //backfill_attachment_urls( $url, $upload['url'] );
        return array(
            'fatal' => false,
            'type' => 'updated',
            'text' => sprintf( __( '%s was uploaded successfully', 'indexhibit-importer' ), $post['post_title'] )
        );
    }

    /**
     * pre_process_attachment
     */
    function pre_process_attachment( $post, $url ) {
        global $wpdb;
        $imported = $wpdb->get_results(
            $wpdb->prepare( "SELECT ID, post_date_gmt, guid
                FROM $wpdb->posts WHERE post_type = 'attachment' AND post_title = %s
                ",
                $post['post_title']
            )
        );
        if ( $imported ) {
            foreach ( $imported as $attachment ) {
                if ( basename( $url ) == basename( $attachment->guid ) ) {
                    if ( $post['post_date_gmt'] == $attachment->post_date_gmt ) {
                        $headers = wp_get_http( $url );
                        if ( filesize( get_attached_file( $attachment->ID ) ) == $headers['content-length'] ) {
                            return new WP_Error( 'duplicate_file_notice', __( 'File already exists', 'indexhibit-importer' ) );
                        }
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * fetch_remote_file
     */
    function fetch_remote_file( $url, $post ) {
        // extract the file name and extension from the url
        $file_name = basename( $url );
        // get placeholder file in the upload dir with a unique, sanitized filename
        $upload = wp_upload_bits( $file_name, 0, '', $post['post_date'] );
        if ( $upload['error'] ) {
            return new WP_Error( 'upload_dir_error', $upload['error'] );
        }
        // fetch the remote url and write it to the placeholder file
        $headers = wp_get_http( $url, $upload['file'] );
        // request failed
        if ( ! $headers ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', __('Remote server did not respond', 'indexhibit-importer') );
        }
        // make sure the fetch was successful
        if ( $headers['response'] != '200' ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', sprintf( __('Remote server returned error response %1$d %2$s', 'indexhibit-importer'), esc_html($headers['response']), get_status_header_desc($headers['response']) ) );
        }
        $filesize = filesize( $upload['file'] );
        if ( isset( $headers['content-length'] ) && $filesize != $headers['content-length'] ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', __('Remote file is incorrect size', 'indexhibit-importer') );
        }
        if ( 0 == $filesize ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', __('Zero size file downloaded', 'indexhibit-importer') );
        }
        return $upload;
    }

    /**
     * cleanup_iximport
     */
    function cleanup_iximport() {
        delete_option( 'ixdbprefix' );
        delete_option( 'ix_cats' );
        delete_option( 'ixid2wpid' );
        delete_option( 'ixposts2wpposts' );
        delete_option( 'ixcm2wpcm' );
        delete_option( 'ixuser' );
        delete_option( 'ixpass' );
        delete_option( 'ixname' );
        delete_option( 'ixhost' );
        delete_option( 'ixcharset' );
        do_action( 'import_done', 'indexhibit' );
        $this->tips();
    }

    /**
     * tips
     */
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

    /**
     * db_form
     */
    function db_form() {
        echo '<table class="form-table">';
        printf( '<tr><th><label for="dbuser">%s</label></th><td><input type="text" name="dbuser" id="dbuser" /></td></tr>', __( 'Indexhibit Database User:', 'indexhibit-importer' ) );
        printf( '<tr><th><label for="dbpass">%s</label></th><td><input type="password" name="dbpass" id="dbpass" /></td></tr>', __( 'Indexhibit Database Password:', 'indexhibit-importer' ) );
        printf( '<tr><th><label for="dbname">%s</label></th><td><input type="text" name="dbname" id="dbname" /></td></tr>', __( 'Indexhibit Database Name:', 'indexhibit-importer' ) );
        printf( '<tr><th><label for="dbhost">%s</label></th><td><input type="text" name="dbhost" id="dbhost" value="localhost" /></td></tr>', __( 'Indexhibit Database Host:', 'indexhibit-importer' ) );
        printf( '<tr><th><label for="dbprefix">%s</label></th><td><input type="text" name="dbprefix" id="dbprefix" value="ix_"/></td></tr>', __( 'Indexhibit Table prefix:', 'indexhibit-importer' ) );
        printf( '<tr><th><label for="ixcharset">%s</label></th><td><input type="text" name="ixcharset" id="ixcharset" value="ISO-8859-15"/></td></tr>', __( 'Originating character set:', 'indexhibit-importer' ) );
        echo '</table>';
    }

    /**
     * dispatch
     */
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
                if ( get_option( 'ixuser' ) ) {
                    delete_option( 'ixuser' );
                }
                add_option( 'ixuser', sanitize_user( $_POST['dbuser'], true ) );
            }
            if ( $_POST['dbpass'] ) {
                if ( get_option( 'ixpass' ) ) {
                    delete_option( 'ixpass' );
                }
                add_option( 'ixpass', sanitize_user( $_POST['dbpass'], true ) );
            }

            if ( $_POST['dbname'] ) {
                if ( get_option( 'ixname' ) ) {
                    delete_option( 'ixname' );
                }
                add_option( 'ixname', sanitize_user( $_POST['dbname'], true ) );
            }
            if ( $_POST['dbhost'] ) {
                if ( get_option( 'ixhost' ) ) {
                    delete_option( 'ixhost' );
                }
                add_option( 'ixhost', sanitize_user( $_POST['dbhost'], true ) );
            }
            if ( $_POST['ixcharset'] ) {
                if ( get_option( 'ixcharset' ) ) {
                    delete_option( 'ixcharset' );
                }
                add_option( 'ixcharset', sanitize_user( $_POST['ixcharset'], true ) );
            }
            if ( $_POST['dbprefix'] ) {
                if ( get_option( 'ixdbprefix' ) ) {
                    delete_option( 'ixdbprefix' );
                }
                add_option( 'ixdbprefix', sanitize_user( $_POST['dbprefix'], true ) );
            }


        }

        switch ( $step ) {
            default:
            case 0 :
                $this->greet();
                break;
            case 1 :
                $result = $this->import_posts();
                if ( is_wp_error( $result ) ) {
                    echo $result->get_error_message();
                }
                break;
            case 2 :
                $this->cleanup_iximport();
                break;
        }

        $this->footer();
    }

}

$ix_import = new Indexhibit_Import();

register_importer( 'indexhibit', __( 'Indexhibit', 'indexhibit-importer' ), __( 'Import posts an Indexhibit site.', 'indexhibit-importer' ), array ( $ix_import, 'dispatch' ) );

}

/**
 * indexhibit_importer_init
 */
function indexhibit_importer_init() {
    load_plugin_textdomain( 'indexhibit-importer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'indexhibit_importer_init' );
