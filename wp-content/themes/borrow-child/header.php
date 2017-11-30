<?php
/**
 * The header for our theme.
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package borrow
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<?php global $borrow_option; ?>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE" />
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

    <!-- Favicons
    ================================================== -->
    <?php borrow_custom_favicon(); ?>

<?php wp_head(); ?>

<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<script type="text/javascript" href="https://lendclick.com.au/wp-content/themes/borrow-child/js/custom.js"></script>

<!-- Google Code for Lead Conversion Page
In your html page, add the snippet and call
goog_report_conversion when someone clicks on the
chosen link or button. -->
<script type="text/javascript">
/ <![CDATA[ /
goog_snippet_vars = function() {
var w = window;
w.google_conversion_id = 867218727;
w.google_conversion_label = "Hcp3CJ37sXYQp-rCnQM";
w.google_remarketing_only = false;
}
// DO NOT CHANGE THE CODE BELOW.
goog_report_conversion = function(url) {
goog_snippet_vars();
window.google_conversion_format = "3";
var opt = new Object();
opt.onload_callback = function() {
if (typeof(url) != 'undefined') {
window.location = url;
}
}
var conv_handler = window['google_trackConversion'];
if (typeof(conv_handler) == 'function') {
conv_handler(opt);
}
}
/ ]]> /
</script>
<script type="text/javascript"
src="https://www.googleadservices.com/pagead/conversion_async.js">
</script>

<script type="text/javascript">
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
</script>
</head>

<body <?php body_class('animsition'); ?>>

<?php 
    if(isset($borrow_option['version_type']) and $borrow_option['version_type']=="header2" ){
        get_template_part('framework/headers/header-2'); 
    }elseif(isset($borrow_option['version_type']) and $borrow_option['version_type']=="header3" ){
        get_template_part('framework/headers/header-3'); 
    }else{ 
?>

<!-- header close -->
<div class="collapse searchbar" id="searchbar">
  <div class="search-area">
    <div class="container">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
          <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <div class="input-group">
                <input type="text" class="search-query form-control" name="s" placeholder="<?php echo esc_html_e('Search for...','borrow'); ?>" value="<?php echo get_search_query() ?>">
                <span class="input-group-btn">
                  <button class="btn btn-default" type="submit"><?php echo esc_html_e('Go!','borrow'); ?></button>
                </span> 
            </div>
            <!-- /input-group -->
          </form>
        </div>
            <!-- /.col-lg-6 -->
      </div>
    </div>
  </div>
  <a class="search-close" role="button" data-toggle="collapse" href="#searchbar" aria-expanded="true"><i class="fa fa-close"></i></a>
</div>

<?php if($borrow_option['top_head']==true){ ?>
<div class="top-bar">
  <!-- top-bar -->
  <div class="container">
    <div class="row">
    <?php if($borrow_option['header_text']!=''){ ?>
      <div class="col-md-4 hidden-xs hidden-sm">
          <p class="mail-text"><?php echo htmlspecialchars_decode(do_shortcode($borrow_option['header_text'])); ?></p>
      </div>
      <?php } ?>
    <?php if($borrow_option['header_right']!=''){ ?>
      <div class="col-md-8 col-sm-12 text-right col-xs-12">
          <div class="top-nav"> 
            <?php echo htmlspecialchars_decode(do_shortcode($borrow_option['header_right'])); ?>
          </div>
      </div>
      <?php } ?>
    </div>
  </div>
</div>
<?php } ?>

<div class="header">
  <div class="container">
    <div class="row">
      <div class="col-md-2 col-sm-12 col-xs-6">
        <!-- logo -->
        <div class="logo">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <?php if($borrow_option['logo']['url'] != ''){ ?>
                <img src="<?php echo esc_url($borrow_option['logo']['url']); ?>" alt="">
            <?php }else{ ?>
                <img src="<?php echo get_template_directory_uri(); ?>/images/logo.png" alt="">
            <?php } ?>   
          </a>
        </div>
      </div>
      <div class="col-md-9 col-sm-12 col-xs-12">
        <div id="navigation">
          <?php
            $primary = array(
                'theme_location'  => 'primary',
                'menu'            => '',
                'container'       => '',
                'container_class' => '',
                'container_id'    => '',
                'menu_class'      => '',
                'menu_id'         => '',
                'echo'            => true,
                'fallback_cb'     => 'wp_bootstrap_navwalker::fallback',
                'walker'          => new wp_bootstrap_navwalker(),
                'before'          => '',
                'after'           => '',
                'link_before'     => '',
                'link_after'      => '',
                'items_wrap'      => '<ul>%3$s</ul>',
                'depth'           => 0,
            );
            if ( has_nav_menu( 'primary' ) ) {
                wp_nav_menu( $primary );
            }
          ?>  
        </div>
      </div>
      <div class="col-md-1 hidden-sm">
          <!-- search start-->
          <div class="search-nav btn-action"> 
            <a href="#section-apply" class="btn btn-default page-scroll">Apply Now</a>
          </div>
      </div>
    </div>
  </div>
</div>
<?php } ?>