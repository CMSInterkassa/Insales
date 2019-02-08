<?php
define('HOST_PATH', $_SERVER['HTTP_HOST']);
define('MODULE_PATH', __DIR__);
define('TPL_PATH', MODULE_PATH . '/tpl/');

$vars = array();
require_once(MODULE_PATH . '/inc/class.Gateway.php');
$Gateway = new Gateway;

if (!empty($_POST)) {
    if (!empty($_POST['order_id'])) {
        echo $Gateway->generate_form($_POST);
    }

    if (!empty($_POST['ik_sign'])) {
        $a = $Gateway->ajaxSign_generate($_POST);
        echo $a;
    }
    if (!empty($_POST['ik_inv_st'])) {
        $Gateway->answer($_POST);
    }
}