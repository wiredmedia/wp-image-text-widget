<?php
/*
Plugin Name: Image Text Widget
Description: A text widget with the ability to attach images from the media library.
Author: Wired Media (carl)
Version: 0.1
Author URI: http://wiredmedia.co.uk
*/


/*
 * You can filter the oputput the widget produces by using a filter,

    The filter is 'image_text_widget_' + the id of the sidebar the widget appears in

    e.g

    add_filter('image_text_widget_main-sidebar', function($output, $instance){ ?>
      <div class=""><?php echo $instance['text'] ?></div><?php
    }, 10, 2);

    You can also filter the size used for the images, again using the id of the sideba the widget appears in

    e.g

    add_filter('image_text_widget_size_main-sidebar', function(){
      return 'large'
    }, 10, 2);


 */
class Image_Text_widget extends WP_Widget {

  function __construct() {
    $this->WP_Widget('image-text-widget', 'Image Text Widget', array(
      'description' => 'Text widget with image attachment.',
      'classname' => 'image-text-widget'
    ));
  }

  function widget( $args, $instance ) {
    extract($args);
    $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
    $text = apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance );
    $attached_images = empty( $instance['attached_images'] ) ? array() : $instance['attached_images'];

    $output = '';

    $output .= $before_widget;

    $main_output = apply_filters('image_text_widget_' . $args['id'], '', $instance);

    if( empty($main_output) ){
      if ( !empty( $title ) ){
        $main_output .= $before_title . $title . $after_title;
      }

      $main_output .= '<div class="image">';
      foreach($attached_images as $imageId){
        $main_output .= wp_get_attachment_image( $imageId, apply_filters('image_text_widget_size_' . $args['id'], 'thumbnail') );
      }
      $main_output .='</div>';

      $main_output .= '<div class="text">'. wpautop( $text ) .'</div>';
      $output .= $main_output;
    }

    $output .= $after_widget;

    echo $output;
  }

  function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['attached_images'] = ( !empty($new_instance['attached_images']) ) ? $new_instance['attached_images'] : array();

    if ( current_user_can('unfiltered_html') )
      $instance['text'] = $new_instance['text'];
    else
      $instance['text'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['text']) ) ); // wp_filter_post_kses() expects slashed
    return $instance;
  }

  function form( $instance ) {
    $instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '', 'attached_images' => array() ) );
    $title = strip_tags($instance['title']);
    $text = esc_textarea($instance['text']);
    $attached_images = $instance['attached_images'];

    $image_bank = get_posts( array('post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID') );
    ?>

    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
    <p>
      <textarea class="widefat" rows="7" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>
    </p>
    <p class="itw-chosen">
      <label for="">Attached Images</label><br />

      <?php if( count($image_bank) > 0 ): ?>
        <select style="width:220px;height:24px;" name="<?php echo $this->get_field_name('attached_images'); ?>[]" class="chzn-select" data-placeholder="Attach Images ..." multiple tabindex="<?php echo count($image_bank); ?>">
          <option value="">Select images</option>
          <?php foreach($image_bank as $image):
            $option = '<option';
            $search = array_search($image->ID, $attached_images);
            $option .= (false !== $search) ? ' selected' : '';
            $option .= ' value="'. $image->ID .'"';
            $option .= '>'. $image->post_title .'</option>';
            echo $option;
            ?>
          <?php endforeach; ?>
        </select>

      <?php else: ?>
        <br />You currently have no images in your media library.
      <?php endif; ?>

    </p>
    <?php
  }

}

add_action('widgets_init', function(){
  register_widget('Image_Text_widget');
});

class ITW_Image_Text_widget{

  public function __construct(){
    add_action('init', array(&$this, 'scripts'));
    add_action( 'admin_enqueue_scripts', array(&$this, 'scripts') );
  }

  public function scripts($hook){
    if( 'widgets.php' == $hook ){
      wp_register_script( 'jquery-chosen', plugins_url('js/jquery.chosen.min.js', __FILE__), array('jquery'), '', true );
      wp_register_style( 'chosen', plugins_url('css/style.css', __FILE__) );
      wp_enqueue_script( 'jquery-chosen' );
      wp_enqueue_style( 'chosen' );
    }
  }

}

if(is_admin()){
  $setup_text_widget = new ITW_Image_Text_widget;
}
