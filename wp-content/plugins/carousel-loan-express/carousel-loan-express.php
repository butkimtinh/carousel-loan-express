<?php

/*
  Plugin Name: Carousel Loan Express
  Plugin URI: http://www.onlinebizsoft.com/
  Description: Quick & Easy Unsecured Business Loans
  Author: Joe Vu
  Version: 1.0
  Author URI: http://www.onlinebizsoft.com/
 */

class CarouselLoanExpress {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_style'));
        if (is_admin()) {
            add_action('load-post.php', array($this, 'init_metabox'));
            add_action('load-post-new.php', array($this, 'init_metabox'));
        }
        add_action("wp_ajax_search_lender", array($this, 'search_lender'));
        add_action("wp_ajax_nopriv_search_lender", array($this, 'search_lender'));
    }

    public function init() {
        wp_register_script('noUiSlider', plugins_url('/assets/noUiSlider/nouislider.min.js', __FILE__), array('jquery'), '10.0.0');
        wp_register_script('icheck', plugins_url('/assets/icheck-1.0.2/icheck.min.js', __FILE__), array('jquery'), '1.0.2');
        wp_register_script('jquery.validate', plugins_url('/assets/jquery-validation-1.17.0/dist/jquery.validate.min.js', __FILE__), array('jquery'), '1.17.0');
        wp_register_script('jquery-ui-js', plugins_url('/assets/js/jquery-ui.js', __FILE__), array('jquery'), '0.0.1');
        wp_register_script('cloanexpress-js', plugins_url('/assets/js/cloanexpress.js', __FILE__), array('jquery'), '0.0.1');
        wp_register_script('cloanexpress-custom', plugins_url('/assets/js/custom.js', __FILE__), array('jquery'), '0.0.1');
        
        wp_register_style('noUiSlider', plugins_url('/assets/noUiSlider/nouislider.min.css', __FILE__), false, '10.0.0', 'all');
        wp_register_style('icheck-all', plugins_url('/assets/icheck-1.0.2/skins/all.css', __FILE__), false, '1.0.2', 'all');
        wp_register_style('cloanexpress-styles', plugins_url('/assets/css/styles.css', __FILE__), false, '0.0.1', 'all');
        add_shortcode('cloanexpress', array($this, 'toHtml'));
        $this->register_post_type();
    }

    public function enqueue_style() {
        wp_enqueue_script('noUiSlider');
        wp_enqueue_script('icheck');
        wp_enqueue_script('jquery.validate');
        wp_enqueue_script('cloanexpress-js');
        wp_enqueue_script('cloanexpress-custom');
        wp_enqueue_script('jquery-ui-js');

        wp_enqueue_style('noUiSlider');
        wp_enqueue_style('icheck-all');
        wp_enqueue_style('cloanexpress-styles');
    }

    public function init_metabox() {
        add_action('add_meta_boxes', array($this, 'add_metabox'));
        add_action('save_post', array($this, 'save_metabox'), 10, 2);
    }

    public function add_metabox() {
        add_meta_box('lender_id', __('Lender Info'), array($this, 'render_metabox'), 'lender', 'normal', 'high');
    }

    public function render_metabox() {
        ob_start();
        include __DIR__ . DIRECTORY_SEPARATOR . 'view/render_metabox_lender.phtml';
        $html = ob_get_contents();
        ob_end_clean();
        echo $html;
    }

    public function save_metabox($post_id, $post) {
        if ($post->post_type != 'lender') {
            return;
        }
        $lender_email = isset($_POST['lender_email']) ? $_POST['lender_email'] : '';
        update_post_meta($post_id, 'lender_email', $lender_email);

        $lender_phone = isset($_POST['lender_phone']) ? $_POST['lender_phone'] : '';
        update_post_meta($post_id, 'lender_phone', $lender_phone);

        $lender_term = isset($_POST['lender_term']) ? $_POST['lender_term'] : '';
        update_post_meta($post_id, 'lender_term', $lender_term);

        $lender_products = isset($_POST['lender_products']) ? $_POST['lender_products'] : array();
        update_post_meta($post_id, 'lender_products', $lender_products);
        $collection = array('Unsecured Business Loans', 'Invoice Finance', 'Line of Credit / Trade Finance', 'Equipment Finance', 'Vehicle Finance', 'Property Development Finance');
        foreach ($collection as $k => $v) {
            update_post_meta($post_id, 'lender_product_' . $k, in_array($k, $lender_products));
        }

        $lender_amount_min = isset($_POST['lender_amount_min']) ? $_POST['lender_amount_min'] : '';
        update_post_meta($post_id, 'lender_amount_min', $lender_amount_min);
      
        $lender_amount_max = isset($_POST['lender_amount_max']) ? $_POST['lender_amount_max'] : '';
        update_post_meta($post_id, 'lender_amount_max', $lender_amount_max);
      
        $lender_webhook = isset($_POST['lender_webhook']) ? $_POST['lender_webhook'] : '';
        update_post_meta($post_id, 'lender_webhook', $lender_webhook);
    }

    public function search_lender() {
        global $post;
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        $data = array(
            'errno' => 1,
            'msg' => 'Sorry! 404 Not found'
        );

        $args = array();
        $args['post_type'] = 'lender';

        $lender_amount = isset($_POST['lender_amount']) ? $_POST['lender_amount'] : false;
        if ($lender_amount) {
            $args['meta_query']['lender_amount_clause'] = array(
                array(
                    array(
                        'key' => 'lender_amount_min',
                        'value' => (int)$lender_amount,
                        'compare' => '<',
                        'type' => 'UNSIGNED',
                    ),
                    array(
                        'key' => 'lender_amount_max',
                        'value' => (int)$lender_amount,
                        'compare' => '>=',
                        'type' => 'UNSIGNED',
                    )
                )
            );
        }

        $lender_term = isset($_POST['lender_term']) ? $_POST['lender_term'] : false;
        if ($lender_term) {
            $args['meta_query']['lender_term_clause'] = array(
                'key' => 'lender_term',
                'value' => $lender_term,
            );
        }
        $lender_products = isset($_POST['lender_products']) ? $_POST['lender_products'] : false;;
        if ($lender_products) {
            $itmes = explode(',', $lender_products);
            foreach ($itmes as $k) {
                $args['meta_query']['lender_products_clause'][] = array(
                    'key' => 'lender_product_' . $k,
                    'value' => true,
                );
            }
        }
        $query = new WP_Query($args);
        
        if($query->have_posts()){
            $data['errno'] = 0;
            $data['msg'] = 'Request is success';
            $data['count'] = $query->found_posts;
            
            $lenders = array();
            while ($query->have_posts()){
                $query->the_post();
                $featured_img_url = get_the_post_thumbnail_url(get_the_ID(),'full'); 
                $lenders[] = array(
                    'ID' => $post->ID,
                    'title' => get_the_title(),
                    'email' => get_post_meta($post->ID, 'lender_email', true),
                    'phone' => get_post_meta($post->ID, 'lender_phone', true),
                    'term' => get_post_meta($post->ID, 'lender_term', true),
                    'thumbnail' => $featured_img_url,
                    'amount' => sprintf('%s - %s', get_post_meta($post->ID, 'lender_amount_min', true), get_post_meta($post->ID, 'lender_amount_max', true)) 
                );
            }
            $data['lenders'] = $lenders; 
        }
        echo json_encode($data);
        die;
    }

    public function register_post_type() {
        $args = array(
            'label' => 'Lender',
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => 'guest-apply'),
            'query_var' => true,
            'menu_icon' => 'dashicons-groups',
            'menu_position' => 28,
            'supports' => array(
                'title',
                'thumbnail'
            )
        );
        register_post_type('lender', $args);
    }

    public function toHtml($attrs) {
        ob_start();
        include __DIR__ . DIRECTORY_SEPARATOR . 'view.phtml';
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

}

$joebiz = new CarouselLoanExpress();
