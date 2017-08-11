<?php 
 /*
   Plugin Name: WP Track
   Plugin URI: http://wp-track.com
   Description: A Plugin that generates and tracks tracking pixels for any usage
   Version: 1.0
   Author: Christopher Budd
   Author URI: http://mynameischrisbuddandstuff.com
   License: TBD
 */

function wp_track_initialize_post_Types() {
  register_post_type('wptrack_tracking',
    [
      'labels'      => [
        'name'          => __('Tracking Pixels'),
        'singular_name' => __('Tracking Pixel'),
      ],
      'public'      => true,
      'has_archive' => false,
      'rewrite'     => ['slug' => 'wptrack_tracking'],
      'supports' => array('title', 'wptrack_gform_id', 'wptrack_tracking_id', 'last_viewed', 'last_viewed_by'),
    ]
  );

}

function setup_wp_track_table() {
  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();

  $table_name = $wpdb->prefix . "wp_track";
  
  $sql = "CREATE TABLE $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    ip_address text NOT NULL,
    wp_track_id text NOT NULL,
    PRIMARY KEY (id) 
  ) $charset_collate;";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );
}
function init_wp_track_metaboxes() {
  
}

function wptrack_custom_meta_boxes() {
  add_meta_box('wptrack_gform_id', 'GravityForms ID', 'wptrack_gform_id_box_html', 'wptrack_tracking');
  add_meta_box('wptrack_tracking_id', 'Tracking Code', 'wptrack_tracking_id_box_html', 'wptrack_tracking');
  add_meta_box('wptrack_tracking_html', 'Tracking Beacons', 'wptrack_tracking_html', 'wptrack_tracking');
}
function wptrack_tracking_id_box_html($post)
{
  if( $post->ID ) {
    $tracking_id = get_post_meta($post->ID, 'wptrack_tracking_id', true);
  } 
  if (! $tracking_id ) { 
    $tracking_id = 'This will generate after you save';
  }

  wp_nonce_field( 'wptrack_tracking_id_save', 'wptrack_tracking_id_nonce' );
    ?>
    <div>
      <label for="wptrack_tracking_id">Tracking ID:</label>
      <input name="wptrack_tracking_id" disabled id="wptrack_tracking_id" class="postbox" type="text" value="<?php echo $tracking_id ?>" />
      Tracking URL: <h3><?php echo get_site_url()?>/wptrack.png?wptrack_id=<?php echo $tracking_id ?></h3>
    </div>
    <?php
}
function wptrack_gform_id_box_html($post)
{
  $value = get_post_meta($post->ID, 'wptrack_gform_id', true);
  wp_nonce_field( 'wptrack_gform_id_save', 'wptrack_gform_id_nonce' );
    ?>
    <div>
      <label for="wptrack_gform_id">gform_id</label>
      <input name="wptrack_gform_id" id="wptrack_gform_id" class="postbox" type="text" value="<?php echo $value ?>" />
    </div>
    <?php
}
function wptrack_tracking_html($post){
  global $wpdb;
  $table = $wpdb->prefix . 'wp_track';
  if( $post->ID ) {
    $tracking_id = get_post_meta($post->ID, 'wptrack_tracking_id', true);
    $results = $wpdb->get_results( "SELECT * FROM $table WHERE wp_track_id = '$tracking_id';");
    $value = get_post_meta($post->ID, 'wptrack_gform_id', true);
    if ( $tracking_id) {

      ?>
      <ul>
        <?php
          for ($i = 0; $i < count($results); $i++) {
            
        ?>
          <li>
            Viewed at <?php echo $results[$i]->time; ?> from <?php echo $results[$i]->ip_address ?>
          </li>
        <?php
          }
        ?>
      </ul>
      <?php
    }
  } 
}
function wptrack_save_postdata($post_id)
{
  $tracking_id = get_post_meta($post_id, 'wptrack_tracking_id', true);

  if (array_key_exists('wptrack_gform_id', $_POST)) {
    if (  isset( $_POST['wptrack_gform_id_nonce'])
      &&  wp_verify_nonce( $_POST['wptrack_gform_id_nonce'], 'wptrack_gform_id_save' )
    ) {
      update_post_meta(
        $post_id,
        'wptrack_gform_id',
        $_POST['wptrack_gform_id']
      );
    }
  }
  if( !$tracking_id ) { 
    if (  isset( $_POST['wptrack_tracking_id_nonce'])
      &&  wp_verify_nonce( $_POST['wptrack_tracking_id_nonce'], 'wptrack_tracking_id_save' ) )
    {
      //generate a UUID
      $tracking_id = uniqid();
      update_post_meta(
        $post_id,
        'wptrack_tracking_id',
        $tracking_id
      );
    }
  }
}


// flush_rules() if our rules are not yet included
function wptrack_flush_rules(){
    $rules = get_option( 'rewrite_rules' );

    if ( ! isset( $rules['track/(.+?)'] ) ) {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }
}

// Adding a new rule
function wptrack_insert_rewrite_rules( $rules )
{
    $newrules = array();
    $newrules['^wptrack.png$'] = 'index.php?wptrack_id=$matches[1]';
    return $newrules + $rules;
}

// Adding the id var so that WP recognizes it
function wptrack_insert_query_vars( $vars )
{
    array_push($vars,'wptrack_id');
    return $vars;
}

function wptrack_parse_request (&$wp) {
  if(array_key_exists('wptrack_id', $wp->query_vars)) {
    include( dirname( __FILE__  ) . '/track.php');
    exit();
  } 
}

add_filter( 'rewrite_rules_array','wptrack_insert_rewrite_rules' );
add_filter( 'query_vars','wptrack_insert_query_vars' );
add_action( 'wp_loaded','wptrack_flush_rules' );
add_action('parse_request', 'wptrack_parse_request');

add_action('init', 'wp_track_initialize_post_Types');
add_action('admin_menu', 'init_wp_track_metaboxes');
add_action('add_meta_boxes', 'wptrack_custom_meta_boxes');
add_action('save_post', 'wptrack_save_postdata');

register_activation_hook( __FILE__, 'setup_wp_track_table' );
?>