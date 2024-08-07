<?php
/**
 * Plugin Name: Indexhibit 2 Importer
 * Plugin URI: http://wordpress.org/plugins/indexhibit2-importer/
 * Description: Import exhibits and media files from an Indexhibit 2 site.
 * Version: 1.0.7
 * Author: leemon
 * Text Domain: indexhibit2-importer
 * License: GPLv2 or later
 *
 * @package indexhibit2-importer
 */

if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
	return;
}

// Load Importer API.
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) ) {
		require_once $class_wp_importer;
	}
}

/**
 * Indexhibit 2 Importer
 *
 * @package WordPress
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {

	/**
	 * Core class used to implement the plugin.
	 */
	class Ix2_Import extends WP_Importer {

		/**
		 * Database connection.
		 *
		 * @var object $ix2db
		 */
		public $ix2db;

		/**
		 * Constructor. Intentionally left empty and public.
		 */
		public function __construct() {}

		/**
		 * Display header
		 */
		public function header() {
			?>
		<div class="wrap">
		<h2><?php _e( 'Import Indexhibit 2', 'indexhibit2-importer' ); ?></h2>
			<?php
		}

		/**
		 * Display footer
		 */
		public function footer() {
			?>
		</div>
			<?php
		}

		/**
		 * Display introductory text and Indexhibit 2 settings form
		 */
		public function greet() {
			?>
		<div class="narrow">
			<p><?php _e( 'This importer allows you to import most of the contents from an Indexhibit 2 site into your WordPress site. It imports exhibits and media files but ignores links, sections, subsections and exhibit formats.', 'indexhibit2-importer' ); ?></p>
			<p><?php _e( 'The process may take a few minutes depending on the size of your database. Please be patient.', 'indexhibit2-importer' ); ?></p>
			<p><?php _e( 'Fill the following form with your Indexhibit 2 configuration settings. They can be found in the <code>/ndxzsite/config/config.php</code> file.', 'indexhibit2-importer' ); ?></p>
			<form action="admin.php?import=indexhibit2&amp;step=1" method="post">
			<?php
			wp_nonce_field( 'import-indexhibit2' );
			$this->db_form();
			?>
			<p class="submit"><input type="submit" name="submit" class="button" value="<?php echo esc_attr__( 'Import exhibits', 'indexhibit2-importer' ); ?>" /></p>
			</form>
		</div>
			<?php
		}

		/**
		 * Display Indexhibit 2 settings form
		 */
		public function db_form() {
			?>
		<table class="form-table">
		<tr>
			<th scope="row"><label for="ixurl"><?php _e( 'Indexhibit 2 Site Address', 'indexhibit2-importer' ); ?></label></th>
			<td>
			<input type="url" name="ixurl" id="ixurl" class="regular-text" required placeholder="http://" />
			<p class="description" id="ixurl-description"><?php _e( 'The URL where your Indexhibit 2 site is installed', 'indexhibit2-importer' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="dbname"><?php _e( 'Indexhibit 2 Database Name', 'indexhibit2-importer' ); ?></label></th>
			<td>
			<input type="text" name="dbname" id="dbname" class="regular-text" required />
			<p class="description" id="dbname-description"><?php _e( "The <code>&dollar;indx['db']</code> value", 'indexhibit2-importer' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="dbuser"><?php _e( 'Indexhibit 2 Database User', 'indexhibit2-importer' ); ?></label></th>
			<td>
			<input type="text" name="dbuser" id="dbuser" class="regular-text" required />
			<p class="description" id="dbuser-description"><?php _e( "The <code>&dollar;indx['user']</code> value", 'indexhibit2-importer' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="dbpass"><?php _e( 'Indexhibit 2 Database Password', 'indexhibit2-importer' ); ?></label></th>
			<td>
			<input type="password" name="dbpass" id="dbpass" class="regular-text" required />
			<p class="description" id="dbpass-description"><?php _e( "The <code>&dollar;indx['pass']</code> value", 'indexhibit2-importer' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="dbhost"><?php _e( 'Indexhibit 2 Database Host', 'indexhibit2-importer' ); ?></label></th>
			<td>
			<input type="text" name="dbhost" id="dbhost" class="regular-text" required placeholder="localhost" />
			<p class="description" id="dbhost-description"><?php _e( "The <code>&dollar;indx['host']</code> value", 'indexhibit2-importer' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="dbprefix"><?php _e( 'Indexhibit 2 Table Prefix', 'indexhibit2-importer' ); ?></label></th>
			<td>
			<input type="text" name="dbprefix" id="dbprefix" class="regular-text" required placeholder="ndxz_" />
			<p class="description" id="dbprefix-description"><?php _e( "The <code>xxxx</code> value in <code>define('PX', 'xxxx')</code> value", 'indexhibit2-importer' ); ?></p>
			</td>
		</tr>
		</table>
			<?php
		}

		/**
		 * Setup connection to Indexhibit 2 database
		 */
		public function init_ix2db() {
			$options     = get_option( 'ix2_options' );
			$this->ix2db = new wpdb( $options['ix2_user'], $options['ix2_pass'], $options['ix2_name'], $options['ix2_host'] );
			$result      = $this->ix2db->check_connection( false );
			if ( ! $result ) {
				return false;
			}
			return true;
		}

		/**
		 * Get exhibits from Indexhibit 2 database
		 */
		public function get_ix2_exhibits() {
			$options  = get_option( 'ix2_options' );
			$dbprefix = $options['ix2_dbprefix'];
			return $this->ix2db->get_results( 'SELECT * FROM ' . $dbprefix . "objects WHERE link = ''", ARRAY_A );
		}

		/**
		 * Get media files from Indexhibit 2 database
		 */
		public function get_ix2_media( $post_id ) {
			$options  = get_option( 'ix2_options' );
			$dbprefix = $options['ix2_dbprefix'];
			return $this->ix2db->get_results(
				$this->ix2db->prepare( 'SELECT * FROM ' . $dbprefix . "media WHERE media_ref_id = %s AND media_mime NOT IN ( 'youtube', 'vimeo' ) ORDER BY media_order ASC", $post_id ),
				ARRAY_A
			);
		}

		/**
		 * Import exhibits
		 */
		public function import_exhibits() {
			// Import exhibits.
			$exhibits = $this->get_ix2_exhibits();
			$result   = $this->exhibits2wp( $exhibits );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			?>
		<form action="admin.php?import=indexhibit2&amp;step=2" method="post">
			<?php wp_nonce_field( 'import-indexhibit2' ); ?>
		<table class="form-table">
			<tr>
			<th scope="row"><label for="mdinsert"><?php _e( 'Insert imported media files into pages content?', 'indexhibit2-importer' ); ?></label></th>
			<td>
			<input type="checkbox" name="mdinsert" id="mdinsert" value="1" checked />
			</td>
			</tr>
		</table>
		<p class="submit"><input type="submit" name="submit" class="button" value="<?php echo esc_attr__( 'Import media files', 'indexhibit2-importer' ); ?>" /></p>
		</form>
			<?php
		}

		/**
		 * Import media files
		 */
		public function import_media() {
			$options            = get_option( 'ix2_options' );
			$mdinsert           = $options['ix2_mdinsert'];
			$ixexhibits2wpposts = $options['ix2_exhibits2wpposts'];

			// Import media.
			foreach ( $ixexhibits2wpposts as $key => $value ) {
				$media  = $this->get_ix2_media( $key );
				$result = $this->media2wp( $media, $value );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				if ( ! empty( $mdinsert ) ) {
					$this->insert_attachments( $value );
				}
			}

			?>
		<form action="admin.php?import=indexhibit2&amp;step=3" method="post">
			<?php wp_nonce_field( 'import-indexhibit2' ); ?>
		<p class="submit"><input type="submit" name="submit" class="button" value="<?php echo esc_attr__( 'Finish', 'indexhibit2-importer' ); ?>" /></p>
		</form>
			<?php
		}

		/**
		 * Perform import of exhibits
		 */
		public function exhibits2wp( $exhibits = '' ) {
			$options            = get_option( 'ix2_options' );
			$count              = 0;
			$ixexhibits2wpposts = array();

			if ( is_array( $exhibits ) ) {
				echo '<p>' . __( 'Importing exhibits...', 'indexhibit2-importer' ) . '</p>';
				foreach ( $exhibits as $exhibit ) {
					$post_author   = get_current_user_id();
					$post_title    = $exhibit['title'];
					$post_content  = $exhibit['content'];
					$post_home     = $exhibit['home'];
					$post_date     = $exhibit['pdate'];
					$post_modified = $exhibit['udate'];
					$post_format   = $exhibit['format'];

					// Set Indexhibit-to-WordPress status translation.
					$ixstatus    = array(
						0 => 'draft',
						1 => 'publish',
					);
					$post_status = $ixstatus[ $exhibit['status'] ];

					$ret_id = 0;

					// Check if the exhibit is already imported.
					if ( $pinfo = post_exists( $post_title, '', $post_date ) ) {
						$ret_id = wp_insert_post(
							array(
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
								'ping_status'       => 'closed',
							)
						);
						if ( is_wp_error( $ret_id ) ) {
							return $ret_id;
						}
						add_post_meta( $ret_id, 'ix2_exhibit_format', $post_format );
					} else {
						$ret_id = wp_insert_post(
							array(
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
								'ping_status'       => 'closed',
							)
						);
						if ( is_wp_error( $ret_id ) ) {
							return $ret_id;
						}
						add_post_meta( $ret_id, 'ix2_exhibit_format', $post_format );
					}

					// Set front page.
					if ( ! empty( $post_home ) ) {
						update_option( 'page_on_front', $ret_id );
						update_option( 'show_on_front', 'page' );
					}

					$ixexhibits2wpposts[ $exhibit['id'] ] = $ret_id;
					++$count;
				}
			}

			// Store exhibit2post translation for later use.
			$options['ix2_exhibits2wpposts'] = $ixexhibits2wpposts;
			update_option( 'ix2_options', $options );

			echo '<p>' . sprintf( __( 'Done! <strong>%1$s</strong> exhibits imported.', 'indexhibit2-importer' ), $count ) . '<br /><br /></p>';
			return true;
		}

		/**
		 * Perform import of media files
		 */
		public function media2wp( $files = '', $post_id ) {
			$count = 0;

			if ( is_array( $files ) ) {
				echo '<p>' . sprintf( __( 'Importing media files from exhibit "%1$s"...', 'indexhibit2-importer' ), get_the_title( $post_id ) ) . '</p>';
				foreach ( $files as $file ) {
					++$count;
					$process = $this->process_attachment( $file, $post_id );
				}
			}

			echo '<p>' . sprintf( __( 'Done! <strong>%1$s</strong> media files imported.', 'indexhibit2-importer' ), $count ) . '<br /><br /></p>';
			return true;
		}

		/**
		 * Create new attachments based on import information
		 */
		public function process_attachment( $file, $parent_post ) {
			$options = get_option( 'ix2_options' );
			$ixurl   = $options['ix2_url'];

			$media_file       = $file['media_file'];
			$media_file_noext = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $media_file );

			$post = array(
				'post_title'    => ( ! empty( $file['media_title'] ) ? $file['media_title'] : $media_file_noext ),
				'post_content'  => $file['media_caption'],
				'post_date'     => $file['media_udate'],
				'post_date_gmt' => $file['media_udate'],
				'menu_order'    => $file['media_order'],
				'post_parent'   => $parent_post,
			);

			$media_url = trailingslashit( $ixurl ) . 'files/gimgs/' . $media_file;

			$pre_process = $this->pre_process_attachment( $post, $media_url );
			if ( is_wp_error( $pre_process ) ) {
				return array(
					'fatal'   => false,
					'type'    => 'error',
					'code'    => $pre_process->get_error_code(),
					'message' => $pre_process->get_error_message(),
					'text'    => sprintf( __( '%1$s was not uploaded. (<strong>%2$s</strong>: %3$s)', 'indexhibit2-importer' ), $post['post_title'], $pre_process->get_error_code(), $pre_process->get_error_message() ),
				);
			}
			// If the URL is absolute, but does not contain address, then upload it assuming base_site_url.
			if ( preg_match( '|^/[\w\W]+$|', $media_url ) ) {
				$base_url  = get_home_url();
				$media_url = rtrim( $base_url, '/' ) . $media_url;
			}
			$upload = $this->fetch_remote_file( $media_url, $post );
			if ( is_wp_error( $upload ) ) {
				return array(
					'fatal'   => ( $upload->get_error_code() === 'upload_dir_error' && $upload->get_error_message() !== 'Invalid file type' ? true : false ),
					'type'    => 'error',
					'code'    => $upload->get_error_code(),
					'message' => $upload->get_error_message(),
					'text'    => sprintf( __( '%1$s could not be uploaded because of an error. (<strong>%2$s</strong>: %3$s)', 'indexhibit2-importer' ), $post['post_title'], $upload->get_error_code(), $upload->get_error_message() ),
				);
			}
			if ( $info = wp_check_filetype( $upload['file'] ) ) {
				$post['post_mime_type'] = $info['type'];
			} else {
				$upload = new WP_Error( 'attachment_processing_error', __( 'Invalid file type', 'indexhibit2-importer' ) );
				return array(
					'fatal'   => false,
					'type'    => 'error',
					'code'    => $upload->get_error_code(),
					'message' => $upload->get_error_message(),
					'text'    => sprintf( __( '%1$s could not be uploaded because of an error. (<strong>%2$s</strong>: %3$s)', 'indexhibit2-importer' ), $post['post_title'], $upload->get_error_code(), $upload->get_error_message() ),
				);
			}
			$post['guid']        = $upload['url'];
			$post['post_author'] = get_current_user_id();
			// As per wp-admin/includes/upload.php.
			$post_id = wp_insert_attachment( $post, $upload['file'] );
			wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $upload['file'] ) );
			return array(
				'fatal' => false,
				'type'  => 'updated',
				'text'  => sprintf( __( '%s was uploaded successfully', 'indexhibit2-importer' ), $post['post_title'] ),
			);
		}

		/**
		 * Check if attachment already exist
		 */
		public function pre_process_attachment( $post, $url ) {
			global $wpdb;
			$imported = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_date_gmt, guid
                FROM $wpdb->posts WHERE post_type = 'attachment' AND post_title = %s
                ",
					$post['post_title']
				)
			);
			if ( $imported ) {
				foreach ( $imported as $attachment ) {
					if ( sanitize_file_name( basename( $url ) ) === basename( $attachment->guid ) ) {
						if ( $post['post_date_gmt'] === $attachment->post_date_gmt ) {
							$remote_response = wp_safe_remote_get( $url );
							$headers         = wp_remote_retrieve_headers( $remote_response );
							if ( filesize( get_attached_file( $attachment->ID ) ) === $headers['content-length'] ) {
								return new WP_Error( 'duplicate_file_notice', __( 'File already exists', 'indexhibit2-importer' ) );
							}
						}
					}
				}
			}
			return false;
		}

		/**
		 * Attempt to download a remote file attachment
		 */
		public function fetch_remote_file( $url, $post ) {
			// Extract the file name and extension from the url.
			$file_name = basename( $url );
			// Get placeholder file in the upload dir with a unique, sanitized filename.
			$upload = wp_upload_bits( $file_name, null, '', $post['post_date'] );
			if ( $upload['error'] ) {
				return new WP_Error( 'upload_dir_error', $upload['error'] );
			}
			// Fetch the remote url and write it to the placeholder file.
			$remote_response = wp_safe_remote_get(
				$url,
				array(
					'timeout'  => 300,
					'stream'   => true,
					'filename' => $upload['file'],
				)
			);
			$headers         = wp_remote_retrieve_headers( $remote_response );

			// Request failed.
			if ( ! $headers ) {
				@unlink( $upload['file'] );
				return new WP_Error( 'import_file_error', __( 'Remote server did not respond', 'indexhibit2-importer' ) );
			}
			// Make sure the fetch was successful.
			$remote_response_code = wp_remote_retrieve_response_code( $remote_response );
			if ( '200' != $remote_response_code ) {
				@unlink( $upload['file'] );
				return new WP_Error( 'import_file_error', sprintf( __( 'Remote server returned error response %1$d %2$s', 'indexhibit2-importer' ), esc_html( $remote_response_code ), get_status_header_desc( $remote_response_code ) ) );
			}
			$filesize = filesize( $upload['file'] );
			if ( isset( $headers['content-length'] ) && $filesize != $headers['content-length'] ) {
				@unlink( $upload['file'] );
				return new WP_Error( 'import_file_error', __( 'Remote file is incorrect size', 'indexhibit2-importer' ) );
			}
			if ( 0 === $filesize ) {
				@unlink( $upload['file'] );
				return new WP_Error( 'import_file_error', __( 'Zero size file downloaded', 'indexhibit2-importer' ) );
			}
			return $upload;
		}

		/**
		 * Insert attachments into posts content
		 */
		public function insert_attachments( $post_id ) {
			$attachments = get_attached_media( 'image', $post_id );
			if ( $attachments ) {
				$post    = get_post( $post_id );
				$content = $post->post_content;
				foreach ( $attachments as $attachment ) {
					$content .= wp_get_attachment_image(
						$attachment->ID,
						'full',
						false,
						array( 'class' => 'attachment-full size-full ' . sprintf( 'wp-image-%d', $attachment->ID ) )
					);
				}
				$updated_post = array(
					'ID'           => $post_id,
					'post_content' => $content,
				);
				// Update post.
				wp_update_post( $updated_post );
			}
		}

		/**
		 * Perform cleanup of plugin options
		 */
		public function clean_options() {
			delete_option( 'ix2_options' );
		}

		/**
		 * Display post-import information
		 */
		public function tips() {
			?>
		<p><?php _e( 'Welcome to WordPress. We hope that you will find this platform incredibly rewarding! As a new WordPress user coming from Indexhibit 2, there are some things that we would like to point out. Hopefully, they will help your transition go as smoothly as possible.', 'indexhibit2-importer' ); ?></p>
		<p><?php _e( 'This plugin imports exhibits and media files from Indexhibit 2 sites, but ignores links, sections, subsections and exhibit formats.', 'indexhibit2-importer' ); ?></p>
		<p><?php _e( 'Exhibits are imported as pages and media files are imported as attachments which are attached to their corresponding pages. If needed, you can convert the imported pages to posts or other post types with a plugin such as <a href="https://wordpress.org/plugins/post-type-switcher/">Post Type Switcher</a>.', 'indexhibit2-importer' ); ?></p>
		<h3><?php _e( 'WordPress Resources', 'indexhibit2-importer' ); ?></h3>
		<p><?php _e( 'Finally, there are numerous WordPress resources around the internet. Some of them are:', 'indexhibit2-importer' ); ?></p>
		<ul>
		<li><?php _e( '<a href="http://wordpress.org/">The official WordPress site</a>', 'indexhibit2-importer' ); ?></li>
		<li><?php _e( '<a href="http://wordpress.org/support/">The WordPress support forums</a>', 'indexhibit2-importer' ); ?></li>
		<li><?php _e( '<a href="http://developer.wordpress.org/">The WordPress developer docs (In other words, the WordPress Bible)</a>', 'indexhibit2-importer' ); ?></li>
		</ul>
		<p><?php _e( 'That&#8217;s it! What are you waiting for? Go <a href="../wp-login.php">log in</a>!', 'indexhibit2-importer' ); ?></p>
			<?php
		}

		/**
		 * Registered callback function for the importer
		 */
		public function dispatch() {
			$this->header();

			$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
			if ( $step > 0 ) {
				check_admin_referer( 'import-indexhibit2' );

				$options = get_option( 'ix2_options' );

				if ( isset( $_POST['ixurl'] ) ) {
					$options['ix2_url'] = sanitize_text_field( esc_url( $_POST['ixurl'] ) );
				}
				if ( isset( $_POST['dbname'] ) ) {
					$options['ix2_name'] = sanitize_text_field( $_POST['dbname'] );
				}
				if ( isset( $_POST['dbuser'] ) ) {
					$options['ix2_user'] = sanitize_text_field( $_POST['dbuser'] );
				}
				if ( isset( $_POST['dbpass'] ) ) {
					$options['ix2_pass'] = sanitize_text_field( $_POST['dbpass'] );
				}
				if ( isset( $_POST['dbhost'] ) ) {
					$options['ix2_host'] = sanitize_text_field( $_POST['dbhost'] );
				}
				if ( isset( $_POST['dbprefix'] ) ) {
					$options['ix2_dbprefix'] = sanitize_text_field( $_POST['dbprefix'] );
				}
				if ( isset( $_POST['mdinsert'] ) ) {
					$options['ix2_mdinsert'] = '1';
				} else {
					$options['ix2_mdinsert'] = '0';
				}

				update_option( 'ix2_options', $options );
			}

			switch ( $step ) {
				default:
				case 0:
					// Clean options.
					$this->clean_options();
					$this->greet();
					break;
				case 1:
					// Try to remove execution time limit to avoid timeouts.
					set_time_limit( 0 );
					// Initialize database connection.
					$conn = $this->init_ix2db();
					if ( $conn ) {
						// Import content.
						$result = $this->import_exhibits();
						if ( is_wp_error( $result ) ) {
							echo $result->get_error_message();
						}
					} else {
						echo '<p>' . __( 'Cannot connect to Indexhibit 2 database', 'indexhibit2-importer' ) . '</p>';
						echo '<a class="button" href="admin.php?import=indexhibit2">' . __( 'Try Again', 'indexhibit2-importer' ) . '</a>';
					}
					break;
				case 2:
					// Try to remove execution time limit to avoid timeouts.
					set_time_limit( 0 );
					// Initialize database connection.
					$conn = $this->init_ix2db();
					if ( $conn ) {
						// Import content.
						$result = $this->import_media();
						if ( is_wp_error( $result ) ) {
							echo $result->get_error_message();
						}
					} else {
						echo '<p>' . __( 'Cannot connect to Indexhibit 2 database', 'indexhibit2-importer' ) . '</p>';
						echo '<a class="button" href="admin.php?import=indexhibit2">' . __( 'Try Again', 'indexhibit2-importer' ) . '</a>';
					}
					break;
				case 3:
					do_action( 'import_done', 'indexhibit2' );
					$this->tips();
					break;
			}

			$this->footer();
		}
	}

}

/**
 * Register importer
 */
function ix2_importer_init() {
	load_plugin_textdomain( 'indexhibit2-importer' );

	$ix2_import = new Ix2_Import();
	register_importer( 'indexhibit2', __( 'Indexhibit 2', 'indexhibit2-importer' ), __( 'Import exhibits and media files from an Indexhibit 2 site.', 'indexhibit2-importer' ), array( $ix2_import, 'dispatch' ) );
}
add_action( 'init', 'ix2_importer_init' );