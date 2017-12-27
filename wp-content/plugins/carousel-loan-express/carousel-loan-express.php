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

    const APP_STATUS_INIT = 'init';
    const APP_STATUS_PROCESSING = 'processing';
    const APP_STATUS_COMPLETE = 'complete';
    const APP_STATUS_FAILURE = 'failure';
    const APP_NOTIFIED = 1;
    const APP_NOT_NOTIFIED = 0;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_style'));
        if (is_admin()) {
            add_action('load-post.php', array($this, 'init_metabox'));
            add_action('load-post-new.php', array($this, 'init_metabox'));
            add_action('admin_init', array($this, 'add_role_caps'), 999);
            add_filter('pre_get_posts', array($this, 'applications_for_current_author'));
        }
        add_action("wp_ajax_search_lender", array($this, 'search_lender'));
        add_action("wp_ajax_nopriv_search_lender", array($this, 'search_lender'));

        add_action("wp_ajax_create_user", array($this, 'create_user'));
        add_action("wp_ajax_nopriv_create_user", array($this, 'create_user'));

        add_action("wp_ajax_create_application", array($this, 'create_application'));
        add_action("wp_ajax_nopriv_create_application", array($this, 'create_application'));

        add_action("wp_ajax_save_step", array($this, 'save_step'));
        add_action("wp_ajax_nopriv_save_step", array($this, 'save_step'));

        add_action('wp_ajax_get_abn_info', array($this, 'get_abn_info'));
        add_action('wp_ajax_nopriv_get_abn_info', array($this, 'get_abn_info'));

        add_action('wp_head', array($this, 'cloanexpress_js'));
        add_action('cloanexpress_schedule_event', array($this, 'cloanexpress_schedule'));

        add_action('lendclick_notification', array($this, 'sendEmail'));
        add_action('lendclick_notification', array($this, 'sendSms'));
        
        add_filter('cron_schedules', array($this, 'cloanexpress_time_schedule'));

        register_activation_hook(__FILE__, array($this, 'onActivation'));
        register_deactivation_hook(__FILE__, array($this, 'onDeactivation'));
        register_uninstall_hook(__FILE__, array(__CLASS__, 'onUninstall'));
    }

    public function cloanexpress_time_schedule($schedules) {
        $schedules['cloanexpress_time_schedule'] = array(
            'interval' => 21600,
            'display' => __('Cloanexpress Time Schedule')
        );
        return $schedules;
    }

    public function add_metabox() {
        add_meta_box('lender_id', __('Lender Info'), array($this, 'render_metabox_lender'), 'lender', 'normal', 'high');
        add_meta_box('application_id', __('Application Info'), array($this, 'render_metabox_application'), 'application', 'normal', 'high');
        add_meta_box('application_lender_id', __('Lenders'), array($this, 'render_metabox_application_lenders'), 'application', 'side', 'high');
    }

    public function add_role_caps() {
        $roles = array('manage_application', 'administrator');
        $caps = array(
            'edit_application',
            'read_application',
            'delete_application',
            'create_applications',
            'edit_applications',
            //'manage_applications',
            'publish_applications',
            'read',
            'delete_applications',
            'delete_private_applications',
            'delete_published_applications',
                //'edit_applications'
        );
        foreach ($roles as $the_role) {
            $role = get_role($the_role);
            if ($role) {
                foreach ($caps as $cap) {
                    $role->add_cap($cap);
                }
            }
        }
    }

    public function applications_for_current_author($query) {
        global $pagenow;
        if ('edit.php' != $pagenow || !$query->is_admin)
            return $query;
        if (!current_user_can('edit_others_posts')) {
            global $user_ID;
            $query->set('author', $user_ID);
        }
        if (!current_user_can('edit_users')) {
            global $user_ID;
            $query->set('author', $user_ID);
        }

        return $query;
    }

    public function sendSms($args) {
        extract($args);
        if ($phone) {
            $username = 'pabs';
            $password = 'hola!23';
            $ref = 'abc123';
            $params = 'username=' . rawurlencode($username) .
                    '&password=' . rawurlencode($password) .
                    '&to=' . rawurlencode($phone) .
                    '&from=' . rawurlencode($source) .
                    '&message=' . rawurlencode($content) .
                    '&ref=' . rawurlencode($ref);
            $ch = curl_init('https://api.smsbroadcast.com.au/api-adv.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
        }
    }

    public function sendEmail($args) {
        extract($args);
        if ($email) {
            $sitename = get_bloginfo('name');
            $siteemail = get_bloginfo('admin_email');
            $headers[] = sprintf('From: %s <%s>', $sitename, $siteemail);
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $notified = wp_mail($email, $source, $content, $headers);
            $this->updateCleConfig(array('status' => $status, 'notified' => $notified), array('token' => $token));
        }
    }

    public function cloanexpress_js() {
        ?>
        <script type="text/javascript">
            var cleconfig = $.parseJSON('<?php echo $this->getCleConfigJson() ?>');
            var cletoken = '<?php echo $this->getCleToken() ?>';
            var loanExpress;
            $(document).ready(function() {
                if ($('.cloanexpress').length > 0) {
                    loanExpress = new LoanExpress(cleconfig);
                    loanExpress.initialize();
                }
        <?php if (isset($_GET['_cletoken'])): ?>
                    $('html,body').animate({scrollTop: $('.cloanexpress').offset().top - 150}, 'slow');
        <?php endif ?>
            });
        </script>
        <?php
    }

    public function register_session() {
        if (!session_id()) {
            session_start();
        }
    }

    public function getCleConfigJson() {
        $_cletoken = $this->getCleToken();
        $result = $this->getCleConfig($_cletoken);
        if ($result) {
            return json_decode($result->data);
        } elseif (isset($_COOKIE[$_cletoken])) {
            return $_COOKIE[$_cletoken];
        } else {
            return json_encode(array());
        }
    }

    public function getCleToken() {
        if (isset($_GET['_cletoken'])) {
            $_cltoken = $_GET['_cletoken'];
        } elseif (isset($_COOKIE['_cletoken'])) {
            $_cltoken = $_COOKIE['_cletoken'];
        } else {
            $_cltoken = md5($_SERVER['REMOTE_ADDR'] . time());
        }
        //$_SESSION['_cletoken'] = $_cltoken;
        return $_cltoken;
    }

    public function init() {
        wp_register_script('noUiSlider', plugins_url('/assets/noUiSlider/nouislider.min.js', __FILE__), array('jquery'), '10.0.0');
        wp_register_script('icheck', plugins_url('/assets/icheck-1.0.2/icheck.min.js', __FILE__), array('jquery'), '1.0.2');
        wp_register_script('jquery.validate', plugins_url('/assets/jquery-validation-1.17.0/dist/jquery.validate.min.js', __FILE__), array('jquery'), '1.17.0');
        wp_register_script('jquery-ui-js', plugins_url('/assets/js/jquery-ui.js', __FILE__), array('jquery'), '0.0.1');
        wp_register_script('jquery.cookie', plugins_url('/assets/js/jquery.cookie.js', __FILE__), array('jquery'), '1.4.1');
        wp_register_script('jquery.mask', plugins_url('/assets/jQuery-Mask-Plugin-1.14.13/dist/jquery.mask.min.js', __FILE__), array('jquery'), '1.14.13');
        wp_register_script('bootstrap.min-js', plugins_url('/assets/js/bootstrap.min.js', __FILE__), array('jquery'), '3.3.7');
        wp_register_script('cloanexpress-js', plugins_url('/assets/js/cloanexpress.js', __FILE__), array('jquery'), '0.0.1');
        wp_register_script('cloanexpress-custom', plugins_url('/assets/js/custom.js', __FILE__), array('jquery'), '0.0.1');

        wp_register_style('noUiSlider', plugins_url('/assets/noUiSlider/nouislider.min.css', __FILE__), false, '10.0.0', 'all');
        wp_register_style('icheck-all', plugins_url('/assets/icheck-1.0.2/skins/all.css', __FILE__), false, '1.0.2', 'all');
        wp_register_style('cloanexpress-styles', plugins_url('/assets/css/styles.css', __FILE__), false, '0.0.1', 'all');
        add_shortcode('cloanexpress', array($this, 'toHtml'));
        $this->register_post_type();
        //$this->register_session();
        //$this->register_taxonomy();
    }

    public function enqueue_style() {
        wp_enqueue_script('noUiSlider');
        wp_enqueue_script('icheck');
        wp_enqueue_script('jquery.validate');
        wp_enqueue_script('cloanexpress-js');
        wp_enqueue_script('cloanexpress-custom');
        wp_enqueue_script('jquery-ui-js');
        wp_enqueue_script('jquery.cookie');
        wp_enqueue_script('jquery.mask');
        wp_enqueue_script('bootstrap.min-js');

        wp_enqueue_style('noUiSlider');
        wp_enqueue_style('icheck-all');
        wp_enqueue_style('cloanexpress-styles');
    }

    public function init_metabox() {
        add_action('add_meta_boxes', array($this, 'add_metabox'));
        add_action('save_post', array($this, 'save_metabox_lender'), 10, 2);
        add_action('save_post', array($this, 'save_metabox_application'), 10, 2);
    }

    public function render_metabox_lender() {
        ob_start();
        include __DIR__ . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . __FUNCTION__ . '.phtml';
        $html = ob_get_contents();
        ob_end_clean();
        echo $html;
    }

    public function render_metabox_application() {
        ob_start();
        include __DIR__ . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . __FUNCTION__ . '.phtml';
        $html = ob_get_contents();
        ob_end_clean();
        echo $html;
    }

    public function render_metabox_application_lenders() {
        ob_start();
        include __DIR__ . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . __FUNCTION__ . '.phtml';
        $html = ob_get_contents();
        ob_end_clean();
        echo $html;
    }

    public function save_metabox_lender($post_id, $post) {
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

    public function save_metabox_application($post_id, $post) {
        if ($post->post_type != 'application') {
            return;
        }
        $app_info = isset($_POST['app']) ? $_POST['app'] : array();
        update_post_meta($post_id, 'app_info', $app_info);
        $app_lenders = isset($_POST['app_lenders']) ? $_POST['app_lenders'] : array();
        ;
        update_post_meta($post_id, 'app_lenders', $app_lenders);
        $this->requestLenders($app_lenders, $app_info);
    }

    public function getLoanProductsCollection() {
        return array('Unsecured Business Loans', 'Invoice Finance', 'Line of Credit / Trade Finance', 'Equipment Finance', 'Vehicle Finance', 'Property Development Finance');
    }

    public function getLoanTermsCollection() {
        return array('Any', '3-6', '6-12', '12-24', '24+');
    }

    public function getLoanIndustryById($id) {
        $collection = $this->getLoanIndustryCollection();
        foreach ($collection as $item) {
            foreach ($item as $k => $v) {
                if ($k == $id) {
                    return $v;
                }
            }
        }
        return false;
    }

    public function getLoanIndustryCollection() {
        return array(
            'Automotive' => array(
                24 => 'Car Washes',
                25 => 'Dealership',
                26 => 'Parts and Accessories',
                27 => 'Repair and Maintenance'
            ),
            'Construction' => array(
                30 => 'Commercial',
                28 => 'New Construction',
                29 => 'Renovation &amp; Remodeling',
                31 => 'Residential',
            ),
            'Entertainment and Recreation' => array(
                1 => 'Adult Entertainment',
                70 => 'Arts',
                9 => 'Gambling',
                74 => 'Nightclubs',
                69 => 'Sports Club',
            ),
            'Health Services' => array(
                33 => 'Dentists',
                34 => 'Doctors Offices',
                37 => 'Optometrists',
                38 => 'Other Health Services',
                35 => 'Personal Care Services',
                36 => 'Pharmacies and Drug Stores'
            ),
            'Hospitality' => array(
                73 => 'Bed and Breakfasts',
                72 => 'Hotels &amp; Inns',
            ),
            'Other' => array(
                2 => 'Agriculture, Forestry, Fishing and Hunting',
                32 => 'Convenience Stores',
                8 => 'Firearm Sales',
                77 => 'Gas stations',
                13 => 'Manufacturing',
                14 => 'Mining (except Oil and Gas)',
                15 => 'Oil and Gas Extraction',
                16 => 'Other',
                17 => 'Real Estate',
                23 => 'Wholesale Trade'
            ),
            'Professional Services' => array(
                7 => 'Finance and Insurance',
                11 => 'IT, Media, or Publishing',
                12 => 'Legal Services',
            ),
            'Restaurants and Food Services' => array(
                40 => 'Catering',
                41 => 'Other Food Services',
                39 => 'Restaurants and Bars',
            ),
            'Retail Facilities' => array(
                49 => 'Beauty Salon &amp; Barbers',
                50 => 'Dry Cleaning &amp; Laundry',
                51 => 'Gym &amp; Fitness Center',
                52 => 'Nails Salon'
            ),
            'Retail Stores' => array(
                42 => 'Building Materials',
                43 => 'Electronics',
                44 => 'Fashion, Clothing, Sports Goods',
                46 => 'Garden &amp; Florists',
                45 => 'Grocery, Supermarkets and Bakeries',
                47 => 'Liquor Store',
                48 => 'Other Retail Store',
            ),
            'Transportation, Taxis and Trucking' => array(
                63 => 'Freight Trucking',
                64 => 'Limousine',
                67 => 'Other Transportaion &amp; Travel',
                65 => 'Taxis',
                66 => 'Travel Agencies'
            ),
            'Utilities and Home Services' => array(
                6 => 'Cleaning',
                60 => 'Landscaping Services',
                61 => 'Other home services',
                58 => 'Plumbing, Electricians &amp; HVAC'
            )
        );
    }

    public function requestLenders($lenders, $data = array()) {
        if (isset($data['loan_products'])) {
            $loan_products = array();
            $collectionProducts = $this->getLoanProductsCollection();
            foreach ($collectionProducts as $k => $v) {
                if (in_array($k, $data['loan_products'])) {
                    $loan_products[] = $v;
                }
            }
            $data['loan_products'] = $loan_products;
        }
        $data['loan_amount'] = isset($data['loan_amount']) ? (is_numeric($data['loan_amount']) ? sprintf('$%s', number_format($data['loan_amount'])) : $data['loan_amount']) : '';
        if (isset($data['loan_industry'])) {
            $data['loan_industry'] = $this->getLoanIndustryById($data['loan_industry']);
        }
        if (isset($data['loan_terms'])) {
            $data['loan_terms'] = sprintf('%s months', $data['loan_terms']);
        }

        if (isset($data['action'])) {
            unset($data['action']);
        }
        $query = new WP_Query(array(
            'post_type' => 'lender',
            'post_status' => 'publish',
            'post__in' => $lenders
        ));
        if ($query->have_posts()) {
            $posts = $query->get_posts();
            foreach ($posts as $post) {
                $hook = get_post_meta($post->ID, 'lender_webhook', true);
                if (filter_var($hook, FILTER_VALIDATE_URL) !== false) {
                    $this->requestZapier($hook, $data);
                }
            }
        }
    }

    public function requestZapier($url, $data = array()) {
        $jsonEncodedData = json_encode($data);
        $opts = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $jsonEncodedData,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Content-Length: ' . strlen($jsonEncodedData))
        );
        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    public function getAbnInfo($abn) {
        $data = array('errno' => 1, 'msg' => __('Are you sure this is the correct ABN number?'));
        $guid = 'a2a0c3fb-c364-44af-bae4-f21ad2405265';
        $wsdl = 'http://abr.business.gov.au/abrxmlsearch/ABRXMLSearch.asmx?WSDL';
        $params = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new SoapClient($wsdl, $params);

        $params = new stdClass();
        $params->searchString = $abn;
        $params->includeHistoricalDetails = 'N';
        $params->authenticationGuid = $guid;
        if ($result = $soap->ABRSearchByABN($params)) {
            $respone = $result->ABRPayloadSearchResults->response;
            if ($respone->exception) {
                $data['msg'] = $respone->exception->exceptionDescription;
            } else {
                if ($businessEntity = $respone->businessEntity) {
                    if ($businessEntity->legalName) {
                        $name = $businessEntity->legalName;
                    } elseif ($businessEntity->mainTradingName) {
                        $name = $businessEntity->mainTradingName->organisationName;
                    } elseif ($businessEntity->mainName) {
                        $name = $businessEntity->mainName->organisationName;
                    }
                    $bussinessData = array();
                    $bussinessData['id'] = $abn;
                    $bussinessData['name'] = $name;
                    if ($businessEntity->mainBusinessPhysicalAddress) {
                        $bussinessData['stateCode'] = $businessEntity->mainBusinessPhysicalAddress->stateCode;
                        $bussinessData['stateName'] = $this->getStateName($bussinessData['stateCode']);
                        $bussinessData['postcode'] = $businessEntity->mainBusinessPhysicalAddress->postcode;
                    }
                    if ($businessEntity->entityStatus) {
                        $bussinessData['effectiveFrom'] = $businessEntity->entityStatus->effectiveFrom;
                    }
                    $data['bussiness'] = $bussinessData;
                    $data['errno'] = 0;
                    $data['msg'] = 'Success';
                }
            }
        }
        return $data;
    }

    public function getStateName($statecode) {
        $name = '';
        switch ($statecode) {
            case 'NSW': {
                    $name = 'New South Wales';
                    break;
                }
            case 'ACT': {
                    $name = 'Australian Capital Territory';
                    break;
                }
            case 'VIC': {
                    $name = 'Victoria';
                    break;
                }
            case 'QLD': {
                    $name = 'Queensland';
                    break;
                }
            case 'SA': {
                    $name = 'South Australia';
                    break;
                }
            case 'WA': {
                    $name = 'Western Australia';
                    break;
                }
            case 'TAS': {
                    $name = 'Tasmania';
                    break;
                }
            case 'NT': {
                    $name = 'Northern Territory';
                    break;
                }
        }
        return $name;
    }

    public function get_abn_info() {
        $data = array();
        $q = isset($_POST['q']) ? trim($_POST['q']) : false;
        if ($q) {
            $data = $this->getAbnInfo($q);
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        die;
    }

    public function create_user() {
        header('Access-Control-Allow-Origin: *');
        $data = array(
            'errno' => 1,
            'msg' => 'Sorry! 404 Not found'
        );
        extract($_POST);
        if ($user_email && filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            if ($cletoken) {
                $phone = preg_replace('/\D/', '', $user_phone);
                $this->updateCleConfig(array(
                    'email' => $user_email,
                    'phone' => $phone,
                        ), array(
                    'token' => $cletoken
                ));
            }
            if (email_exists($user_email)) {
                $userdata = get_user_by('email', $user_email);
                $data['errno'] = 0;
                $data['msg'] = __('Success');
                $data['author_id'] = $userdata->ID;
            } else {
                $user_login = 'customer_' . md5($user_email . time());
                $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
                $userdata = array(
                    'user_login' => $user_login,
                    'user_pass' => $random_password,
                    'user_email' => $user_email,
                    'display_name' => $user_name,
                    'nickname' => $user_login,
                    'role' => 'manage_application'
                );
                $user_id = wp_insert_user($userdata);
                if (is_wp_error($user_id)) {
                    $data['msg'] = __('Cant create customer');
                } else {
                    $subject = __('[Lend Click] Your account is created automate');
                    $data['errno'] = 0;
                    $data['msg'] = __('Success');
                    $data['author_id'] = $user_id;
                    // send mail
                    $sitename = get_bloginfo('name');
                    $siteemail = get_bloginfo('admin_email');
                    $headers[] = sprintf('From: %s <%s>', $sitename, $siteemail);
                    $headers[] = 'Content-Type: text/html; charset=UTF-8';
                    $content = <<<EOD
                    <p> Thanks you! </p>
                    <p> Access login at: https://www.lendclick.com.au/wp-login.php</p>
                    <p> Username: $user_email</p>
                    <p> Password: $random_password</p>
EOD;
                    wp_mail($user_email, $subject, $content, $headers);
                }
            }
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        die;
    }

    public function create_application() {
        header('Access-Control-Allow-Origin: *');
        $data = array(
            'errno' => 1,
            'msg' => 'Sorry! 404 Not found'
        );
        extract($_POST);
        if ($loan_amount && $loan_terms && $loan_products && $loan_customer_email && is_numeric($loan_author_id)) {
            $my_post = array(
                'post_title' => sprintf('%s <%s>', $loan_customer_name, $loan_customer_email),
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'application',
                'post_author' => $loan_author_id,
            );
            // Insert the post into the database
            $result = wp_insert_post($my_post);
            if ($result == 0 || $result instanceof WP_Error) {
                $data['msg'] = __('Sorry we cant create an application at the moment. Please try again later.');
                $status = self::APP_STATUS_FAILURE;
                $content = __('We cant create your application');
            } else {
                $data['errno'] = 0;
                $data['msg'] = __('Thank you, our lenders will contact you shortly');
                $status = self::APP_STATUS_COMPLETE;
                $content = __('Thank you, your application is created success and our lenders will contact you shortly');
                update_post_meta($result, 'app_info', $_POST);
//                update_post_meta($result, 'app_lenders', $loan_lenders);
//                $this->requestLenders($loan_lenders, $_POST);
                if ($cletoken) {
                    $this->clearCleConfig($cletoken);
                }
            }

            $phone = preg_replace('/\D/', '', $loan_customer_phone);
            do_action('lendclick_notification', array(
                'email' => $loan_customer_email,
                'phone' => $phone,
                'token' => $cletoken,
                'content' => $content,
                'status' => $status,
                'source' => __('LendClick Notification')
            ));
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        die;
    }

    public function save_step() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        $data = array(
            'errno' => 1,
            'msg' => 'Sorry! 404 Not found'
        );
        if (isset($_POST['cletoken']) && isset($_POST['cledata']) && $this->saveCleConfig($_POST['cletoken'], $_POST['cledata'])) {
            $data['errno'] = 0;
            $data['msg'] = 'Success';
        }
        echo json_encode($data);
        die;
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
        $args['post_status'] = 'publish';


        $lender_amount = isset($_POST['lender_amount']) ? $_POST['lender_amount'] : false;
        if ($lender_amount) {
            $lender_amount = intval($lender_amount);
            $args['meta_query']['lender_amount_clause'] = array(
                array(
                    array(
                        'key' => 'lender_amount_min',
                        'value' => (int) $lender_amount,
                        'compare' => '<',
                        'type' => 'UNSIGNED',
                    ),
                    array(
                        'key' => 'lender_amount_max',
                        'value' => (int) $lender_amount,
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
        $lender_products = isset($_POST['lender_products']) ? $_POST['lender_products'] : false;
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

        if ($query->have_posts()) {
            $data['errno'] = 0;
            $data['msg'] = 'Request is success';
            $data['count'] = $query->found_posts;

            $lenders = array();
            while ($query->have_posts()) {
                $query->the_post();
                $featured_img_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                $min_price = sprintf('$%s', number_format(get_post_meta($post->ID, 'lender_amount_min', true)));
                $max_price = sprintf('$%s', number_format(get_post_meta($post->ID, 'lender_amount_max', true)));
                $collection = array('Unsecured Business Loans', 'Invoice Finance', 'Line of Credit / Trade Finance', 'Equipment Finance', 'Vehicle Finance', 'Property Development Finance');
                $products = get_post_meta($post->ID, 'lender_products', true);
                $label = array();
                foreach ($products as $k) {
                    $label[] = $collection[$k];
                }
                $lenders[] = array(
                    'ID' => $post->ID,
                    'title' => get_the_title(),
                    'email' => get_post_meta($post->ID, 'lender_email', true),
                    'phone' => get_post_meta($post->ID, 'lender_phone', true),
                    'term' => get_post_meta($post->ID, 'lender_term', true),
                    'products' => join(',', $label),
                    'thumbnail' => $featured_img_url,
                    'amount' => sprintf('%s - %s', $min_price, $max_price)
                );
            }
            $data['lenders'] = $lenders;
            ob_start();
            include __DIR__ . DIRECTORY_SEPARATOR . 'view/search_lender.phtml';
            $html = ob_get_contents();
            ob_end_clean();
            $data['html'] = $html;
        } else {
            $data['msg'] = __('Sorry not found any lender');
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
            'rewrite' => array('slug' => 'lender', 'with_front' => false),
            'query_var' => true,
            'menu_icon' => 'dashicons-groups',
            'menu_position' => 28,
            'supports' => array(
                'title',
                'thumbnail'
            )
        );
        register_post_type('lender', $args);
        $args = array(
            'label' => 'Application',
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => 'application'),
            'capability_type' => 'application',
            'map_meta_cap' => true,
            'capabilities' => array(
                // meta caps (don't assign these to roles)
                'edit_post' => 'edit_application',
                'read_post' => 'read_application',
                'delete_post' => 'delete_application',
                // primitive/meta caps
                'create_posts' => 'create_applications',
                // primitive caps used outside of map_meta_cap()
                'edit_posts' => 'edit_applications',
                'edit_others_posts' => 'manage_applications',
                'publish_posts' => 'publish_applications',
                'read_private_posts' => 'read',
                // primitive caps used inside of map_meta_cap()
                'read' => 'read',
                'delete_posts' => 'delete_applications',
                'delete_private_posts' => 'delete_private_applications',
                'delete_published_posts' => 'delete_published_applications',
                'delete_others_posts' => 'manage_applications',
                'edit_private_posts' => 'edit_applications',
                'edit_published_posts' => 'edit_applications'
            ),
            'query_var' => true,
            'menu_icon' => 'dashicons-groups',
            'menu_position' => 28,
            'supports' => array(
                'title'
            )
        );
        register_post_type('application', $args);
    }

    public function register_taxonomy() {
        $labels = array(
            'name' => __('Lenders'),
            'singular_name' => __('Lender'),
            'search_items' => __('Search Lenders'),
            'popular_items' => __('Popular Lenders'),
            'all_items' => __('All Lenders'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Edit Lender'),
            'update_item' => __('Update Lender'),
            'add_new_item' => __('Add New Lender'),
            'new_item_name' => __('New Lender Name'),
            'separate_items_with_commas' => __('Separate lenders with commas'),
            'add_or_remove_items' => __('Add or remove lenders'),
            'choose_from_most_used' => __('Choose from the most used lenders'),
            'not_found' => __('No lenders found.'),
            'menu_name' => __('Lenders'),
        );

        $args = array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var' => true,
            'rewrite' => array('slug' => 'lender', 'with_front' => false),
        );
        register_taxonomy('lender', 'application', $args);
    }

    public function lender_add_form_fields() {
        include __DIR__ . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . __FUNCTION__ . '.phtml';
    }

    public function save_taxonomy_lender_meta($term_id) {
        if (isset($_POST['lender_meta'])) {
            $t_id = $term_id;
            $term_meta = get_option("taxonomy_$t_id");
            $cat_keys = array_keys($_POST['lender_meta']);
            foreach ($cat_keys as $key) {
                if (isset($_POST['lender_meta'][$key])) {
                    $term_meta[$key] = $_POST['lender_meta'][$key];
                }
            }
            // Save the option array.
            update_option("taxonomy_$t_id", $term_meta);
        }
    }

    public function toHtml($attrs) {
        ob_start();
        include __DIR__ . DIRECTORY_SEPARATOR . 'view.phtml';
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    public function getIndustrySelect($id, $name, $selected = '', $options = array()) {
        extract($options);
        $industry = $this->getLoanIndustryCollection();
        $html = sprintf('<select id="%s" name="%s" class="%s" required="">', $id, $name, $class);
        $html .= '<option value="">Select Industry</option>';
        foreach ($industry as $grpname => $grpitem) {
            $html .= sprintf('<optgroup label="%s">', $grpname);
            foreach ($grpitem as $k => $v) {
                $attr = $k == $selected ? 'selected="selected"' : '';
                $html .= sprintf('<option value="%s" %s >%s</option>', $k, $attr, $v);
            }
            $html .= '</optgroup>';
        }
        $html .= '</select>';
        return $html;
    }

    public function cloanexpress_schedule() {
        global $wpdb;
        $clepxress_tbl = $wpdb->prefix . "clepxress";
        $sql = 'SELECT * FROM ' . $clepxress_tbl . ' WHERE updated_at < (NOW()- INTERVAL 6 HOUR) and status = \'processing\' and notified = 0';
        $results = $wpdb->get_results($sql);
        if (count($results)) {
            foreach ($results as $object) {
                if (!$object->email || !$object->phone) {
                    continue;
                }
                $email = $object->email;
                $token = $object->token;
                $data = json_decode(json_decode(stripslashes($object->data), true), true);
                $post_meta = $data && isset($data['data']) ? $data['data'] : array();

                $link = $this->getLinkApplicationNotComplete($email, $token, $post_meta);
                if (!$link) {
                    continue;
                }
                $phone = $object->phone;
                $params = array(
                    'email' => $email,
                    'phone' => $phone,
                    'token' => $token,
                    'content' => sprintf('Please complete this form: <a href="%s" target="_blank">%s</a>', $link, $link),
                    'status' => 'failure',
                    'source' => __('LendClick Notification')
                );
                do_action('lendclick_notification', $params);
            }
        }
        $wpdb->query('DELETE FROM ' . $clepxress_tbl . ' WHERE expired_at < NOW()');
    }

    protected function getLinkApplicationNotComplete($email, $token, $post_meta = array()) {
        require_once __DIR__ . '/../../../wp-includes/link-template.php';
        global $wpdb;
        $users_tbl = $wpdb->prefix . "users";
        $result = $wpdb->get_row("SELECT ID FROM $users_tbl WHERE user_email = '$email'");
        $home_url = get_home_url();
        if (!$result) {
            return $home_url . '?_cletoken=' . $token;
        }
        $my_post = array(
            'post_title' => __('Form Not Complete <' . $email . '>'),
            'post_content' => '',
            'post_status' => 'pending',
            'post_type' => 'application',
            'post_author' => $result->ID,
        );
        $result = wp_insert_post($my_post);
        if (!is_wp_error($result)) {
            add_post_meta($result, 'app_info', $post_meta);
            return $home_url . '/wp-admin/post.php?post=' . $result . '&action=edit';
        }
        return false;
    }

    public function onActivation() {
        global $wpdb;

        if (!wp_next_scheduled('cloanexpress_schedule_event')) {
            wp_schedule_event(time(), 'cloanexpress_time_schedule', 'cloanexpress_schedule_event');
        }

        add_role('manage_application', 'Manage Application', array(
            'upload_files' => false,
            'edit_users' => false,
            'level_0' => true,
        ));
        $table_name = $wpdb->prefix . "clepxress";
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
		`id` int(11) NOT NULL AUTO_INCREMENT,
                `email` varchar(128) NULL,
                `phone` varchar(64) NULL,
                `status` varchar(64) NULL,
                `notified` int(1) NULL,
                `token` varchar(32) NOT NULL,
                `data` text NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL,
                `expired_at` datetime NOT NULL,
		PRIMARY KEY (`id`),
                KEY `token` (`token`)
	) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    public function onDeactivation() {
        self::destroy();
    }

    public static function onUninstall() {
        self::destroy();
    }

    public static function destroy() {
        global $wpdb;
        remove_role('manage_application');
        wp_clear_scheduled_hook('cloanexpress_event');
        $table_name = $wpdb->prefix . "clepxress";
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    public function saveCleConfig($token, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . "clepxress";
        $token = esc_sql($token);
        $data = json_encode($data);
        $current_time = time();
        $created_at = date('Y-m-d H:i:s', $current_time);
        $expired_at = date('Y-m-d H:i:s', $current_time + 7 * 24 * 60 * 60);
        if ($this->hasCleToken($token)) {
            $r = $wpdb->update($table_name, array('data' => $data, 'status' => self::APP_STATUS_PROCESSING, 'notified' => self::APP_NOT_NOTIFIED, 'updated_at' => $created_at, 'expired_at' => $expired_at), array('token' => $token));
        } else {
            $r = $wpdb->insert($table_name, array('token' => $token, 'data' => $data, 'status' => self::APP_STATUS_INIT, 'notified' => self::APP_NOT_NOTIFIED, 'updated_at' => $created_at, 'created_at' => $created_at, 'expired_at' => $expired_at));
        }
        return $r;
    }

    public function updateCleConfig($data, $where) {
        global $wpdb;
        $table_name = $wpdb->prefix . "clepxress";
        $current_time = time();
        $created_at = date('Y-m-d H:i:s', $current_time);
        $expired_at = date('Y-m-d H:i:s', $current_time + 7 * 24 * 60 * 60);
        $data_default = array('updated_at' => $created_at, 'expired_at' => $expired_at);
        $wpdb->update($table_name, array_merge($data_default, $data), $where);
    }

    public function saveStatusCls($token, $status, $notified) {
        global $wpdb;
        $table_name = $wpdb->prefix . "clepxress";
        $current_time = time();
        $created_at = date('Y-m-d H:i:s', $current_time);
        $expired_at = date('Y-m-d H:i:s', $current_time + 7 * 24 * 60 * 60);
        $wpdb->update($table_name, array('status' => $status, 'notified' => $notified, 'updated_at' => $created_at, 'expired_at' => $expired_at), array('token' => $token));
    }

    public function hasCleToken($token) {
        global $wpdb;
        $table_name = $wpdb->prefix . "clepxress";
        $result = $wpdb->get_row("SELECT id FROM $table_name WHERE token = '$token'");
        return (bool) $result;
    }

    public function getCleConfig($token) {
        global $wpdb;
        $table_name = $wpdb->prefix . "clepxress";
        return $wpdb->get_row("SELECT data FROM $table_name WHERE token = '$token'");
    }

    public function deleteCleConfig($token) {
        global $wpdb;
        $table_name = $wpdb->prefix . "clepxress";
        return $wpdb->delete($table_name, array('token' => $token));
    }

    public function clearCleConfig($token) {
        unset($_COOKIE['_cletoken']);
        unset($_COOKIE[$token]);
        //unset($_SESSION['_cletoken']);
    }

}

$joebiz = new CarouselLoanExpress();
