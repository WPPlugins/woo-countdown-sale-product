<?php 
/*
 * Plugin Name: C4D Woocommerce Countdown Sale
 * Plugin URI: https://coffee4dev.com
 * Description: Create countdown clock for sale product
 * Author: Coffee4Dev
 * Author URI: http://coffee4dev.com
 * Version: 2.0.0
*/
define('C4D_WCD_PLUGIN_URI', plugins_url('', __FILE__));

add_action( 'wp_enqueue_scripts', 'c4d_wcd_safely_add_stylesheet_to_frontsite');
add_shortcode('c4d_wcd_clock', 'c4d_wcd_clock');
add_shortcode('c4d_wcd_template', 'c4d_wcd_template');
add_filter( 'plugin_row_meta', 'c4d_wcd_plugin_row_meta', 10, 2 );

function c4d_wcd_plugin_row_meta( $links, $file ) {
    if ( strpos( $file, basename(__FILE__) ) !== false ) {
        $new_links = array(
            'visit' => '<a href="http://coffee4dev.com">Visit Plugin Site</<a>',
            'forum' => '<a href="http://coffee4dev.com/forums/">Forum</<a>',
            'premium' => '<a href="http://coffee4dev.com">Premium Support</<a>'
        );
        
        $links = array_merge( $links, $new_links );
    }
    
    return $links;
}

function c4d_wcd_template($atts) {
    $query_args = array(
        'numberpost'        => 1,
        'posts_per_page'    => 1,
        'no_found_rows'     => 1,
        'post_status'       => 'publish',
        'post_type'         => 'product',
        'meta_query'        => WC()->query->get_meta_query(),
        'post__in'          => isset($atts['id']) ? array($atts['id']) : wc_get_product_ids_on_sale(),
        'orderby'           => 'date',
        'order'             => 'desc'
    );

    $query = new WP_Query( $query_args );
    ob_start();
    while ( $query->have_posts() ):
        $product = $query->the_post();
        $file = get_template_directory(). '/c4d-woo-countdown/templates/default.php';
        if (file_exists($file)) {
            require $file;
        } else {
            require dirname(__FILE__). '/templates/default.php';
        }
    endwhile; 
    woocommerce_reset_loop();
    wp_reset_postdata();
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
}
function c4d_wcd_clock($params) {

    if (!isset($params['id'])) return;
    $product = get_product($params['id']);
    $current = time();
    $to = get_post_meta($params['id'], '_sale_price_dates_to', true);
    
    if (!$to || $to == '') {
        return '';
   	}

    $from = get_post_meta($params['id'], '_sale_price_dates_from', true);
    
    if ($from && $current < $from) return '';

    $id = 'c4d-wcd-'.uniqid();
    $html = '<div id="'.$id.'"><div class="c4d-wcd__clock"></div></div>
    	<script>
        (function($){
            $(document).ready(function(){
                $("#'.$id.' > .c4d-wcd__clock").countdown({
                    until: new Date("'.date("Y-m-d H:i:s", $to).'"),
                    format: "dhMS",
                    padZeroes: true
                    //format: "'.(isset($params['format']) ? $params['format'] : 'dGMS').'"
                });
            });
        })(jQuery);
        </script>';
    return $html;
}

/**
 * Add stylesheet to the page
 */
function c4d_wcd_safely_add_stylesheet_to_frontsite( $page ) {
    if(!defined('C4DPLUGINMANAGER')) {
    	wp_enqueue_style( 'c4d-wcd-frontsite-style', C4D_WCD_PLUGIN_URI.'/assets/default.css' );
    }
    wp_enqueue_script( 'c4d-wcd-frontsite-plugin-js', C4D_WCD_PLUGIN_URI.'/jquery.plugin.min.js', array( 'jquery' ), false, true ); 
    wp_enqueue_script( 'c4d-wcd-frontsite-countdown-js', C4D_WCD_PLUGIN_URI.'/jquery.countdown.min.js', array( 'jquery' ), false, true ); 
}
?>