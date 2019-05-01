<?php
/*
  Plugin Name: Indexhibit 2 Importer
  Plugin URI: http://wordpress.org/extend/plugins/indexhibit-importer/
  Description: Import exhibits and images from an Indexhibit 2 site.
  Version: 0.1
  Author: leemon
  Text Domain: indexhibit-importer
  License: GPLv2 or later
*/

if ( !defined( 'WP_LOAD_IMPORTERS' ) ) {
    return;
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
     * Constructor. Intentionally left empty and public.
     */
    public function __construct() {}

    /**
     * header
     */
    public function header() {
    ?>
        <div class="wrap">
        <h2><?php _e( 'Import Indexhibit 2', 'indexhibit-importer' ); ?></h2>
    <?php
    }

    /**
     * footer
     */
    public function footer() {
    ?>
        </div>
    <?php
    }

    /**
     * greet
     */
    public function greet() {
    ?>
        <div class="narrow">
            <p><?php _e( 'This importer allows you to import the contents from an Indexhibit 2 database into your WordPress site.', 'indexhibit-importer' ); ?></p>
            <p><?php _e( 'The process may take a few minutes depending on the size of your database. Please be patient.', 'indexhibit-importer' ); ?></p>
            <p><?php _e( 'Fill the following form with your Indexhibit 2 configuration settings. They can be found in the <code>/ndxzsite/config/config.php</code> file.', 'indexhibit-importer' ); ?></p>
            <form action="admin.php?import=indexhibit2&amp;step=1" method="post">
    <?php
        wp_nonce_field( 'import-indexhibit' );
        $this->db_form();
    ?>
            <p class="submit"><input type="submit" name="submit" class="button" value="<?php echo esc_attr__( 'Import Contents', 'indexhibit-importer' ); ?>" /></p>
            </form>
        </div>
    <?php
    }

    /**
     * get_ix_exhibits
     */
    public function get_ix_exhibits() {
        $ixdb = new wpdb( get_option( 'ixuser' ), get_option( 'ixpass' ), get_option( 'ixname' ), get_option( 'ixhost' ) );
        $dbprefix = get_option( 'ixdbprefix' );

        // Get exhibits
        return $ixdb->get_results( "SELECT " . $dbprefix . "objects.* FROM " . $dbprefix . "objects", ARRAY_A );
    }

    /**
     * get_ix_media
     */
    public function get_ix_media( $post_id ) {
        $ixdb = new wpdb( get_option( 'ixuser' ), get_option( 'ixpass' ), get_option( 'ixname' ), get_option( 'ixhost' ) );
        $dbprefix = get_option( 'ixdbprefix' );

        // Get media from a specific post
        return $ixdb->get_results( 
            $ixdb->prepare( "SELECT " . $dbprefix . "media.* FROM " . $dbprefix . "media WHERE media_ref_id = %s AND media_mime NOT IN ( 'youtube', 'vimeo' ) ORDER BY media_order ASC", $post_id ), 
            ARRAY_A );
    }

    /**
     * exhibits2wp
     */
    public function exhibits2wp( $exhibits = '' ) {
        global $wpdb;
        $count = 0;
        $ixexhibits2wpposts = array();

        if ( is_array( $exhibits ) ) {
            echo '<p>' . __( 'Importing exhibits...', 'indexhibit-importer' ) . '<br /><br /></p>';
            foreach ( $exhibits as $exhibit ) {
                $count++;

                $post_author = get_current_user_id();
                $post_title = $exhibit['title'];
                $post_content = $exhibit['content'];
                $post_date = $exhibit['pdate'];
                $post_modified = $exhibit['udate'];

                // Set Indexhibit-to-WordPress status translation
                $ixstatus = array(
                    0 => 'draft',
                    1 => 'publish'
                );
                $post_status = $ixstatus[$exhibit['status']];

                // Import post data into WordPress
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
                            'post_type'         => 'page',
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
                            'post_type'         => 'page',
                            'comment_status'    => 'closed',
                            'ping_status'       => 'closed' )
                            );
                    if ( is_wp_error( $ret_id ) ) {
                        return $ret_id;
                    }
                }
                $ixexhibits2wpposts[$exhibit['id']] = $ret_id;

                $media = $this->get_ix_media( $exhibit['id'] );
                $result = $this->media2wp( $media, $ret_id );
                if ( is_wp_error( $result ) ) {
                    return $result;
                }

            }
        }
        // Store ID translation for later use
        add_option( 'ixexhibits2wpposts', $ixexhibits2wpposts );

        echo '<p>' . sprintf( __( 'Done! <strong>%1$s</strong> exhibits imported.', 'indexhibit-importer' ), $count ) . '<br /><br /></p>';
        return true;
    }

    /**
     * media2wp
     */
    public function media2wp( $images = '', $post_id ) {
        global $wpdb;
        $count = 0;

        if ( is_array( $images ) ) {
            echo '<p>' . sprintf( __( 'Importing media from exhibit %d...', 'indexhibit-importer' ), $post_id ) . '<br /><br /></p>';
            foreach ( $images as $image ) {
                $count++;
                $process = $this->process_attachment( $image, $post_id );
            }
        }

        return true;

    }

    /**
     * import_exhibits
     */
    public function import_exhibits() {
        // Post import
        $exhibits = $this->get_ix_exhibits();
        $result = $this->exhibits2wp( $exhibits );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        echo '<form action="admin.php?import=indexhibit2&amp;step=2" method="post">';
        wp_nonce_field( 'import-indexhibit' );
        printf( '<p class="submit"><input type="submit" name="submit" class="button" value="%s" /></p>', esc_attr__( 'Finish', 'indexhibit-importer' ) );
        echo '</form>';
    }
    
    /**
     * process_attachment
     */
    public function process_attachment( $image, $parent ) {

        $post = array(
            'post_title'    => $image['media_title'],
            'post_content'  => $image['media_caption'],
            'post_date'     => $image['media_udate'],
            'post_date_gmt' => $image['media_udate'],
            'post_parent'   => $parent,
        );

        $ixurl = get_option( 'ixurl' );
        $media_url = trailingslashit( $ixurl ) . '/files/gimgs/' . $image['media_file'];

        echo $media_url;

        $pre_process = $this->pre_process_attachment( $post, $media_url );
        if ( is_wp_error( $pre_process ) ) {
            return array(
                'fatal' => false,
                'type' => 'error',
                'code' => $pre_process->get_error_code(),
                'message' => $pre_process->get_error_message(),
                'text' => sprintf( __( '%1$s was not uploaded. (<strong>%2$s</strong>: %3$s)', 'indexhibit-importer' ), $post['post_title'], $pre_process->get_error_code(), $pre_process->get_error_message() )
            );
        }
        // If the URL is absolute, but does not contain address, then upload it assuming base_site_url
        if ( preg_match( '|^/[\w\W]+$|', $media_url ) ) {
            $base_url = get_home_url();
            $media_url = rtrim( $base_url, '/' ) . $media_url;
        }
        $upload = $this->fetch_remote_file( $media_url, $post );
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
            $upload = new WP_Error( 'attachment_processing_error', __( 'Invalid file type', 'indexhibit-importer' ) );
            return array(
                'fatal' => false,
                'type' => 'error',
                'code' => $upload->get_error_code(),
                'message' => $upload->get_error_message(),
                'text' => sprintf( __( '%1$s could not be uploaded because of an error. (<strong>%2$s</strong>: %3$s)', 'indexhibit-importer' ), $post['post_title'], $upload->get_error_code(), $upload->get_error_message() )
            );
        }
        $post['guid'] = $upload['url'];
        // Set author.
        $post['post_author'] = get_current_user_id();
        // as per wp-admin/includes/upload.php
        $post_id = wp_insert_attachment( $post, $upload['file'] );
        wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $upload['file'] ) );
        return array(
            'fatal' => false,
            'type' => 'updated',
            'text' => sprintf( __( '%s was uploaded successfully', 'indexhibit-importer' ), $post['post_title'] )
        );
    }

    /**
     * pre_process_attachment
     */
    public function pre_process_attachment( $post, $url ) {
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
                        $remote_response = wp_safe_remote_get( $url );
                        $headers = wp_remote_retrieve_headers( $remote_response );
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
    public function fetch_remote_file( $url, $post ) {
        // Extract the file name and extension from the url
        $file_name = basename( $url );
        // Get placeholder file in the upload dir with a unique, sanitized filename
        $upload = wp_upload_bits( $file_name, 0, '', $post['post_date'] );
        if ( $upload['error'] ) {
            return new WP_Error( 'upload_dir_error', $upload['error'] );
        }
        // Fetch the remote url and write it to the placeholder file
        $remote_response = wp_safe_remote_get( $url, array(
            'timeout'  => 300,
            'stream'   => true,
            'filename' => $upload['file'],
            )
        );
        $headers = wp_remote_retrieve_headers( $remote_response );

        // Request failed
        if ( ! $headers ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', __( 'Remote server did not respond', 'indexhibit-importer' ) );
        }
        // Make sure the fetch was successful
        $remote_response_code = wp_remote_retrieve_response_code( $remote_response );
        if ( $remote_response_code != '200' ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', sprintf( __( 'Remote server returned error response %1$d %2$s', 'indexhibit-importer' ), esc_html( $remote_response_code ), get_status_header_desc( $remote_response_code ) ) );
        }
        $filesize = filesize( $upload['file'] );
        if ( isset( $headers['content-length'] ) && $filesize != $headers['content-length'] ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', __( 'Remote file is incorrect size', 'indexhibit-importer' ) );
        }
        if ( 0 == $filesize ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', __( 'Zero size file downloaded', 'indexhibit-importer' ) );
        }
        return $upload;
    }

    /**
     * cleanup_import
     */
    public function cleanup_import() {
        delete_option( 'ixexhibits2wpposts' );
        delete_option( 'ixdbprefix' );
        delete_option( 'ixuser' );
        delete_option( 'ixpass' );
        delete_option( 'ixname' );
        delete_option( 'ixhost' );
        delete_option( 'ixurl' );
        do_action( 'import_done', 'indexhibit2' );
        $this->tips();
    }

    /**
     * tips
     */
    public function tips() {
    ?>
        <p><?php _e( 'Welcome to WordPress. We hope that you will find this platform incredibly rewarding! As a new WordPress user coming from Indexhibit 2, there are some things that we would like to point out. Hopefully, they will help your transition go as smoothly as possible.', 'indexhibit-importer' ); ?></p>
        <h3><?php _e( 'WordPress Resources', 'indexhibit-importer' ); ?></h3>
        <p><?php _e( 'Finally, there are numerous WordPress resources around the internet. Some of them are:', 'indexhibit-importer' ); ?></p>
        <ul>
        <li><?php _e( '<a href="http://wordpress.org/">The official WordPress site</a>', 'indexhibit-importer' ); ?></li>
        <li><?php _e( '<a href="http://wordpress.org/support/">The WordPress support forums</a>', 'indexhibit-importer' ); ?></li>
        <li><?php _e( '<a href="http://developer.wordpress.org/">The WordPress developer docs (In other words, the WordPress Bible)</a>', 'indexhibit-importer' ); ?></li>
        </ul>';
        <p><?php _e( 'That&#8217;s it! What are you waiting for? Go <a href="../wp-login.php">log in</a>!', 'indexhibit-importer' ); ?></p>
    <?php
    }

    /**
     * db_form
     */
    public function db_form() {
    ?>
        <table class="form-table">
        <tr>
            <th scope="row"><label for="ixurl"><?php _e( 'Indexhibit 2 Site Address', 'indexhibit-importer' ); ?></label></th>
            <td><input type="url" name="ixurl" id="ixurl" class="regular-text" required placeholder="http://" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="dbname"><?php _e( 'Indexhibit 2 Database Name', 'indexhibit-importer' ); ?></label></th>
            <td>
            <input type="text" name="dbname" id="dbname" class="regular-text" required />
            <p class="description" id="dbuser-description"><?php _e( "The <code>&dollar;indx['db']</code> value", 'indexhibit-importer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="dbuser"><?php _e( 'Indexhibit 2 Database User', 'indexhibit-importer' ); ?></label></th>
            <td>
            <input type="text" name="dbuser" id="dbuser" class="regular-text" required />
            <p class="description" id="dbuser-description"><?php _e( "The <code>&dollar;indx['user']</code> value", 'indexhibit-importer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="dbpass"><?php _e( 'Indexhibit 2 Database Password', 'indexhibit-importer' ); ?></label></th>
            <td>
            <input type="password" name="dbpass" id="dbpass" class="regular-text" required />
            <p class="description" id="dbuser-description"><?php _e( "The <code>&dollar;indx['pass']</code> value", 'indexhibit-importer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="dbhost"><?php _e( 'Indexhibit 2 Database Host', 'indexhibit-importer' ); ?></label></th>
            <td>
            <input type="text" name="dbhost" id="dbhost" class="regular-text" required placeholder="localhost" />
            <p class="description" id="dbuser-description"><?php _e( "The <code>&dollar;indx['host']</code> value", 'indexhibit-importer' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="dbprefix"><?php _e( 'Indexhibit 2 Table Prefix', 'indexhibit-importer' ); ?></label></th>
            <td>
            <input type="text" name="dbprefix" id="dbprefix" class="regular-text" required placeholder="ix_" />
            <p class="description" id="dbuser-description"><?php _e( "The <code>xxxx</code> value in <code>define('PX', 'xxxx')</code> value", 'indexhibit-importer' ); ?></p>
            </td>
        </tr>
        </table>
    <?php
    }

    /**
     * dispatch
     */
    public function dispatch() {

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
                add_option( 'ixuser', sanitize_text_field( $_POST['dbuser'] ) );
            }
            if ( $_POST['dbpass'] ) {
                if ( get_option( 'ixpass' ) ) {
                    delete_option( 'ixpass' );
                }
                add_option( 'ixpass', sanitize_text_field( $_POST['dbpass'] ) );
            }

            if ( $_POST['dbname'] ) {
                if ( get_option( 'ixname' ) ) {
                    delete_option( 'ixname' );
                }
                add_option( 'ixname', sanitize_text_field( $_POST['dbname'] ) );
            }
            if ( $_POST['dbhost'] ) {
                if ( get_option( 'ixhost' ) ) {
                    delete_option( 'ixhost' );
                }
                add_option( 'ixhost', sanitize_text_field( $_POST['dbhost'] ) );
            }
            if ( $_POST['ixurl'] ) {
                if ( get_option( 'ixurl' ) ) {
                    delete_option( 'ixurl' );
                }
                add_option( 'ixurl', sanitize_text_field( $_POST['ixurl'] ) );
            }
            if ( $_POST['dbprefix'] ) {
                if ( get_option( 'ixdbprefix' ) ) {
                    delete_option( 'ixdbprefix' );
                }
                add_option( 'ixdbprefix', sanitize_text_field( $_POST['dbprefix'] ) );
            }

        }

        switch ( $step ) {
            default:
            case 0 :
                $this->greet();
                break;
            case 1 :
                $result = $this->import_exhibits();
                if ( is_wp_error( $result ) ) {
                    echo $result->get_error_message();
                }
                break;
            case 2 :
                $this->cleanup_import();
                break;
        }

        $this->footer();
    }
}

$ix_import = new Indexhibit_Import();

register_importer( 'indexhibit2', __( 'Indexhibit 2', 'indexhibit-importer' ), __( 'Import exhibits and images from an Indexhibit 2 site.', 'indexhibit-importer' ), array( $ix_import, 'dispatch' ) );

}

/**
 * indexhibit_importer_init
 */
function indexhibit_importer_init() {
    load_plugin_textdomain( 'indexhibit-importer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'indexhibit_importer_init' );
