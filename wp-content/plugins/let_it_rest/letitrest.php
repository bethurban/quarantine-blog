<?php
   /*
   Plugin Name: Let It Rest
   description: Customizing the Let It Rest blog.
   Version: 1.0
   Author: Beth Urban
   Author URI: http://bethurban.com
   */

   function let_it_rest_scripts() {
    wp_enqueue_style( 'custom', plugin_dir_path(__FILE__) . 'css/custom.css');   
   }

   add_action( 'wp_enqueue_scripts', 'let_it_rest_scripts' );
    

?>
