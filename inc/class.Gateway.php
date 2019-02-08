<?php
ini_set('display_errors');

class Gateway
{
    public function __construct()
    {
        $configs = include('config.php');

        $this->api_id = $configs['api_id'];//'5bd2ed2e3c1eaf743c8b4568';
        $this->api_key = $configs['api_key'];//'j6liXicYuDS76NyWDyJjVYRt6kBnkISU';
        $this->merchant_id = $configs['merchant_id'];//'5c3f457e3b1eaf78238b456a';
        $this->test_mode = $configs['test_mode'];//'yes';
        $this->secret = $configs['secret'];//'M2Xhk62iapbRY5k8';
        $this->test_key = $configs['test_key'];//'xERBUnBWKf0KZI42';
        $this->enabledAPI = $configs['enabledAPI'];//'yes';
        $this->user_id = $configs['user_id'];//'9385294d6bc36c595c0d6054bfc717d3';
        $this->user_pas = $configs['user_pas'];//'8b8c0d9d102d113ff6d744495fc610ff';

        $url = explode('//', $configs['base_site_url']);//'myshop-tt465.myinsales.ru';
        $this->base_site_url = count($url) == 1 ? $url[0] : $url[1];//'myshop-tt465.myinsales.ru';
        $this->base_site_protokol = count($url) == 1 ? 'http://' : $url[1] . '//';//'myshop-tt465.myinsales.ru';

        $this->url_modul = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    }

    // Проверяет поддерживаеться ли валюта магазина,
    // возвращает ошибку или код валюты
    public function getCurrencyVerification()
    {
        $remote_url_ik = 'https://api.interkassa.com/v1/currency';
        $cur_ik = $this->getData($this->api_id, $this->api_key, $remote_url_ik);
        if (empty($cur_ik)) {
            $mes['error'] = 'Не получены валюты от Интеркассы';
            return $mes;
        }

        $remote_url = $this->base_site_protokol . $this->user_id . ':' . $this->user_pas . '@' . $this->base_site_url . '/admin/stock_currencies.json';
        $cur_shop = $this->getData($this->user_id, $this->user_pas, $remote_url);
        if (empty($cur_shop)) {
            $mes['error'] = 'Не получены валюты от магазина';
            return $mes;
        }

        $cur_for_mes = '';
        foreach ($cur_ik->data as $key => $item) {
            if ($cur_shop[0]->code == $key) {
                return $key;
            } elseif ($cur_shop[0]->code == 'RUR') {
                return 'RUB';
            } else {
                $cur_for_mes .= $key . ' ';
            }
        }

        $mes['error'] = 'Интеркасса не поддерживает валюту магазина. Доступные валюты: ' . $cur_for_mes;
        return $mes;
    }

    public function generate_form($order)
    {
        $order_url = $_SERVER['HTTP_ORIGIN'] . '/orders/' . $order['key'];
        $cur = $this->getCurrencyVerification();
        if (!array_key_exists('error', $cur)) {
            $order_id = $order['order_id'];
            $action_adr = "https://sci.interkassa.com/";

            $FormData = array(
                'ik_am' => $order['amount'],
                'ik_cur' => $cur,
                'ik_co_id' => $this->merchant_id,
                'ik_pm_no' => $order_id,
                'ik_desc' => "#$order_id",
                'ik_suc_u' => $this->url_modul,
                'ik_fal_u' => $this->url_modul,
            );

            if ($this->test_mode == 'yes')
                $FormData['ik_pw_via'] = 'test_interkassa_test_xts';

            $FormData["ik_sign"] = $this->IkSignFormation($FormData, $this->secret);
            $hidden_fields = '';
            foreach ($FormData as $key => $value) {
                $hidden_fields .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
            }
            $cancel_url = '<a class="button cancel" href="' . $order_url . '">Отказаться от оплаты & вернуться в корзину</a>';
        } else {
            $error_mes = $cur['error'];
            $cancel_url = '<a class="button cancel" href="' . $order_url . '">Вернуться к заказу</a>';
        }
        include_once 'tpl.php';
    }

    public function ajaxSign_generate($request)
    {

        //if (!empty($data['ik_pw_via']) && $data['ik_pw_via'] == 'test_interkassa_test_xts')
        //    $new_ik_sign = $data["ik_sign"];
        //else
        //    $new_ik_sign = $this->IkSignFormation($data, $this->secret);

        header("Pragma: no-cache");
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
        header("Content-type: text/plain");
        $request = $_POST;

        if (isset($_POST['ik_act']) && $_POST['ik_act'] == 'process') {
            $request['ik_sign'] = $this->IkSignFormation($request, $this->secret);
            $data = $this->getAnswerFromAPI($request);
        } else
            $data = $this->IkSignFormation($request, $this->secret);

        return $data;
    }

    public function IkSignFormation($data, $secret_key)
    {
        if (!empty($data['ik_sign'])) unset($data['ik_sign']);

        $dataSet = array();
        foreach ($data as $key => $value) {
            if (!preg_match('/ik_/', $key)) continue;
            $dataSet[$key] = $value;
        }

        ksort($dataSet, SORT_STRING);
        array_push($dataSet, $secret_key);
        $arg = implode(':', $dataSet);
        $ik_sign = base64_encode(md5($arg, true));

        return $ik_sign;
    }

    public function getAnswerFromAPI($data)
    {
        $ch = curl_init('https://sci.interkassa.com/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        echo $result;
        exit;
    }

    public function getData($login, $pass, $url)
    {
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Authorization: Basic " . base64_encode($login . ':' . $pass)
            )
        );

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $json_data = json_decode($response); // оплачиваемый заказ
        return $json_data;
    }

    function getIkPaymentSystems()
    {
        $remote_url = 'https://api.interkassa.com/v1/paysystem-input-payway?checkoutId=' . $this->merchant_id;

        $json_data = $this->getData($this->api_id, $this->api_key, $remote_url);

        if (empty($json_data))
            return '<strong style="color:red;">Error!!! System response empty!</strong>';

        if ($json_data->status != 'error') {
            $payment_systems = array();
            if (!empty($json_data->data)) {
                foreach ($json_data->data as $ps => $info) {
                    $payment_system = $info->ser;
                    if (!array_key_exists($payment_system, $payment_systems)) {
                        $payment_systems[$payment_system] = array();
                        foreach ($info->name as $name) {
                            if ($name->l == 'en') {
                                $payment_systems[$payment_system]['title'] = ucfirst($name->v);
                            }
                            $payment_systems[$payment_system]['name'][$name->l] = $name->v;
                        }
                    }
                    $payment_systems[$payment_system]['currency'][strtoupper($info->curAls)] = $info->als;
                }
            }
            return !empty($payment_systems) ? $payment_systems : '<strong style="color:red;">API connection error or system response empty!</strong>';
        } else {
            if (!empty($json_data->message))
                return '<strong style="color:red;">API connection error!<br>' . $json_data->message . '</strong>';
            else
                return '<strong style="color:red;">API connection error or system response empty!</strong>';
        }
    }

    function answer($res)
    {
        $order_id = $res['ik_pm_no'];
        $remote_url = $this->base_site_protokol . $this->user_id . ':' . $this->user_pas . '@' . $this->base_site_url . '/admin/orders/' . $order_id . '.json';
        $json_data = $this->getData($this->user_id, $this->user_pas, $remote_url);

        if ($res['ik_inv_st'] == 'success') {
            $data_order = array('order' => array('financial_status' => 'paid'));
            $data = $this->send_financial_status($remote_url, $data_order);
            $order = json_decode($data); // обновленный заказ
        }

        header("Location: " . $this->base_site_protokol . $this->base_site_url . "/orders/" . $json_data->key);
    }

    public function send_financial_status($url, $data)
    {
        $myCurl = curl_init();
        curl_setopt_array($myCurl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array('Content-type: application/json', 'Authorization: Basic ' . base64_encode($this->user_id . ':' . $this->user_pas)),
            CURLOPT_FOLLOWLOCATION => 1
        ));
        $result = curl_exec($myCurl);

        curl_close($myCurl);

        return $result;
    }
}