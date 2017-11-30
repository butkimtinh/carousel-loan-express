<?php

// enqueue styles for child theme
function example_enqueue_styles() {

    // enqueue child styles
    wp_enqueue_style('child-theme-style', get_stylesheet_directory_uri() . '/style.css', array());
    wp_enqueue_script('theme_js', get_stylesheet_directory_uri() . '/js/script.js', array('jquery'), '1.0', true);
}

add_action('wp_enqueue_scripts', 'example_enqueue_styles');

function get_abn_info() {
    $data = array();
    $q = isset($_POST['q']) ? trim($_POST['q']) : false;
    if ($q) {
        $data = getAbnInfo($q);
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    die;
}

function getAbnInfo($abn) {
    $data = array('errno' => 1, 'msg' => 'Are you sure this is the correct ABN number?');
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
                $bussinessData['name'] = $name;
                if ($businessEntity->mainBusinessPhysicalAddress) {
                    $bussinessData['stateCode'] = $businessEntity->mainBusinessPhysicalAddress->stateCode;
                    $bussinessData['stateName'] = getStateName($bussinessData['stateCode']);
                    $bussinessData['postcode'] = $businessEntity->mainBusinessPhysicalAddress->postcode;
                }
                $data['bussiness'] = $bussinessData;
                $data['errno'] = 0;
                $data['msg'] = 'Success';
            }
        }
    }
    return $data;
}

function getStateName($statecode) {
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

add_filter('wpcf7_validate_text*', 'abn_validation_filter', 20, 2);

function abn_validation_filter($result, $tag) {
    if ('ABN' == $tag->name) {
        $abn = isset($_POST['ABN']) ? trim($_POST['ABN']) : '';
        $data = getAbnInfo($abn);
        if ($data['errno'] == 1) {
            $result->invalidate($tag, $data['msg']);
        }
    }
    return $result;
}

add_action('wp_ajax_get_abn_info', 'get_abn_info');    // If called from admin panel
add_action('wp_ajax_nopriv_get_abn_info', 'get_abn_info');    // If called from front end
?>