<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package borrow
 */
get_header(); ?>

<div class="page-header" <?php if($borrow_option['bg_blogpage'] != ''){ ?> style="background:linear-gradient(rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.2)), rgba(0, 0, 0, 0.2) url(<?php echo esc_url($borrow_option['bg_blogpage']['url']); ?>) no-repeat center;"<?php } ?>>
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="page-breadcrumb">
          <?php if($borrow_option['bread-switch']==true){ ?>
             <ol class="breadcrumb">
                <?php if(function_exists('bcn_display'))
                {
                    bcn_display();
                }?>
            </ol>
          <?php } ?>
        </div>
      </div>
      <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="bg-white pinside30">
          <div class="row">
            <div class="col-md-8 col-sm-7 col-xs-12">
              <h1 class="page-title">
                <?php the_title(); ?>
              </h1>
            </div>
            <?php if($borrow_option['action_link']!=''){ ?>
            <div class="col-md-4 col-sm-5 col-xs-12">
              <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                  <div class="btn-action"> <a href="<?php echo esc_url($borrow_option['action_link']); ?>" class="btn btn-default"><?php echo esc_attr($borrow_option['action_text']); ?></a> </div>
                </div>
              </div>
            </div>
            <?php } ?>
          </div>
        </div>
        <?php if($borrow_option['sub_nav']!=''){ ?>
        <div class="sub-nav" id="sub-nav">
          <ul class="nav nav-justified">
            <?php echo htmlspecialchars_decode($borrow_option['sub_nav']); ?>
          </ul>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
  <!-- subheader close -->
	<!-- content begin -->
<div class=""><!-- main container -->
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="wrapper-content bg-white pinside40">
          <div class="row">

            <div class="col-md-8 col-sm-12 col-xs-12">
              <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php while ( have_posts() ) : the_post(); ?>
                	
        					<?php the_content(); ?>

        					<?php
        						// If comments are open or we have at least one comment, load up the comment template.
        						if ( comments_open() || get_comments_number() ) :
        							comments_template();
        						endif;
        					?>
        					<?php wp_link_pages(); ?>
        					
        				<?php endwhile; // End of the loop. ?>
              </div>                   
            </div>

            <div class="col-md-4 col-sm-12 col-xs-12">                              
              <?php get_sidebar();?>                
            </div>
                        
          </div>
        </div>
	    </div>
    </div>
  </div>
</div>
    <!-- content close -->

<?php get_footer(); ?>
