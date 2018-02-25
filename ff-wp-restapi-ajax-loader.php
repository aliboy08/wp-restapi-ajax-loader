<?php
/*
Plugin Name:  FF WP Rest API Ajax Loader
Plugin URI:   https://www.fivebyfive.com.au/
Description:  Load posts using ajax
Version:      1.0
Author:       Five by Five
Author URI:   https://www.fivebyfive.com.au/
License: 	  GPLv3
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

if( ! defined( 'WPINC' ) ) die;

class FF_WP_Rest_Ajax_Loader {

	public function __construct(){
		add_action('wp_enqueue_scripts', array($this, 'register_assets'));
		add_shortcode('ff_restapi_ajax_loader', array($this, 'shortcode'));
	}
	
	/**
	 * Register Assets
	 */
	public function register_assets(){
		$dir = plugin_dir_url(__FILE__);
		wp_register_style('ff-restapi-ajax-loader-styles', $dir . 'css/styles.css');
		wp_register_script('mustache-js', 'https://cdnjs.cloudflare.com/ajax/libs/mustache.js/2.3.0/mustache.min.js', array(), '2.3.0', true);
		wp_register_script('ff-restapi-ajax-loader-js', $dir .'js/ff-wp-restapi-ajax-loader.js', array('jquery', 'mustache-js'), '2.0', true);
		wp_localize_script('ff-restapi-ajax-loader-js', 'wp_ajax_url', admin_url('admin-ajax.php'));
	}
	
	/**
	 * Load Assets
	 */
	public function load_assets(){
		wp_enqueue_style('ff-restapi-ajax-loader-styles');
		wp_enqueue_script('mustache-js');
		wp_enqueue_script('ff-restapi-ajax-loader-js');
	}
	
	/**
	 * Create Shortcode
	 */
	public function shortcode($atts){
		ob_start();
		extract( shortcode_atts( array(
		
			// Markup
			'container_class' => '',
			'result_container_class' => 'row',
			'item_class' => 'item col-xs-6 col-sm-4',
			'no_more_results_text' => 'No more results',
			'load_more_btn_text' => 'Load more',
			'load_more_btn_class' => '',
			'load_more_btn_container_class' => '',
			'loading_markup' => '<i class="fa fa-circle-notch spin"></i>',
			'template_name' => 'item-post', // normal template file
			'mst_template_name' => 'item-mst-post', // mustache template file
			
			// FF Ajax Loader Options
			'base_url' => 'https://fivebyfive.com.au/',
			'post_type' => 'posts',
			'per_page' => 9,
			'page' => 1,
			
			// Misc
			'load_assets' => true,
			
		), $atts));
		
		if( $load_assets ) {
			$this->load_assets();
		}
		
		$template_directory = get_template_directory() .'/item-templates/';
		$template_file = $template_directory . $template_name .'.php';
		$mst_template_file = $template_directory . $mst_template_name .'.php';
		
		// check if template file exists
		$template_file_exists = ( file_exists($template_file) ) ? true : false;
		$mst_template_file_exists = ( file_exists($mst_template_file) ) ? true : false;

		$template_file_exists = false;
		$mst_template_file_exists = false;
		
		$query_args = array(
			'post_type' => $post_type,
			'showposts' => $showposts,
		);
		?>
		<div class="ff-restapi-ajax-loader <?php echo $container_class ?>"
			data-base_url="<?php echo $base_url; ?>"
			data-post_type="<?php echo $post_type; ?>"
			data-per_page="<?php echo $per_page; ?>"
			data-page="<?php echo $page; ?>"
			data-template="#<?php echo $template_name; ?>">
			
			<div class="results-container <?php echo $result_container_class; ?>"></div>
			
			<div id="<?php echo $template_name ?>" style="display:none">
				<?php
				if( $mst_template_file_exists ) {
					include($mst_template_file);
				} else {
					// Default mustache item template
					$this->default_mustache_item_template();
				}
				?>
			</div>
			
			<div class="load-more-button-container <?php echo $load_more_btn_container_class ?>">
				<a href="#" class="btn load-more-button <?php echo $load_more_btn_class; ?>"><?php echo $load_more_btn_text; ?></a>
			</div>
			<div class="no-more-results" style="display:none"><?php echo $no_more_results_text; ?></div>
			<div class="loading" style="display:none"><?php echo $loading_markup; ?></div>
			
		</div>
		<?php
		return ob_get_clean();
	} // Shortcode
	
	/**
	 * Default item template
	 */ 
	public function default_item_template(){
		echo '<div class="item col-xs-6 col-sm-4">';
			echo '<h3 class="title">';
				echo '<a href="'. get_permalink() .'">';
					echo get_the_title();
				echo '</a>';
			echo '</h3>';
			
			echo '<div class="excerpt">'. get_the_excerpt() .'</div>';
			
			echo '<div class="read-more-container">';
				echo '<a href="'. get_permalink() .'" class="read-more">Read More</a>';
			echo '</div>';
		echo '</div>';
	}

	/**
	 * Default mustache item template
	 */
	public function default_mustache_item_template(){
		?>
		<div class="item col-xs-6 col-sm-4">
			<h5 class="title">
				<a href="{{link}}">
					{{title.rendered}}
				</a>
			</h5>
			
			{{#excerpt}}
			<div class="excerpt">
				{{excerpt.rendered}}
			</div>
			{{/excerpt}}
			
			{{#_embedded.wp:featuredmedia.0.source_url}}
			<img src="{{_embedded.wp:featuredmedia.0.source_url}}">
			{{/_embedded.wp:featuredmedia.0.source_url}}
			
			<div class="read-more-container">
				<a href="{{link}}" class="read-more">Read More</a>
			</div>
		</div>
		<?php
	}
	
	
}

$ff_wp_reset_ajax_loader = new FF_WP_Rest_Ajax_Loader();

if( !function_exists('ff_check_more_posts') ) {
	// Check if query have more posts, for ajax load more
	function ff_check_more_posts($args){
		$args['showpost'] = 1;
		$q = new WP_Query($args);
		if( $q->have_posts() ) {
			return true;
		} else {
			return false;
		}
	}
}