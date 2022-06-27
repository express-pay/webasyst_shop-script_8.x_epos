<?php

/**
 *
 * @author LLC "TriIncom"
 * @name ExpressPay: E-POS
 * @description Плагин оплаты через E-POS.
 *
 *
 */

class eposexpresspaypaymentPayment extends waPayment implements waIPayment
{
    public function allowedCurrency()
    {
        $currency = array('BYN');

        return $currency;
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        // заполняем обязательный элемент данных с описанием заказа
        if (empty($order_data['description'])) {
            $order_data['description'] = 'Заказ ' . $order_data['order_id'];
        }
        $tmpCurrency = wa('shop')->getConfig()->getCurrency(true) == 'BYN' ? 933 : 000;

        $order = waOrder::factory($order_data);
        $hidden_fields = array(
            'ServiceId'         => $this->EPOS_EXPRESSPAY_SERVICEID,
            'AccountNo'         => str_replace('#', '', $order_data['id_str']),
            'Amount' => number_format($order->total, 2, '.', ''),
            'Currency' => "933",
            'Info' => $this->EPOS_EXPRESSPAY_INFO,
            'ReturnType' => "json",
            'ReturnUrl' => 'http://' . $_SERVER['HTTP_HOST'] . "/payments.php/eposexpresspaypayment/?app_id=" . $this->app_id . '_' . $this->merchant_id . '_' . $order_data['order_id'],
            'FailUrl' => $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL),
        );

        $hidden_fields['Signature'] = $this->compute_signature($hidden_fields, $this->EPOS_EXPRESSPAY_SECRET_WORD, $this->EPOS_EXPRESSPAY_TOKEN);
        $view = wa()->getView();

        $isTest = $this->EPOS_EXPRESSPAY_TESTMODE;
        $EPOS_EXPRESSPAY_SANDBOX_API_URL =  'https://sandbox-api.express-pay.by';
        $EPOS_EXPRESSPAY_API_URL =  'https://api.express-pay.by';

        if (isset($isTest) && $isTest) {
            $url = $EPOS_EXPRESSPAY_SANDBOX_API_URL;
        } else {
            $url = $EPOS_EXPRESSPAY_API_URL;
        }

        $url =   $url . "/v1/web_invoices";

        $respone = $this->sendRequestPOST($url, $hidden_fields);
        $respone = json_decode($respone, true);

        $serviceProviderEposCode = $this->EPOS_EXPRESSPAY_SERVICE_PROVIDER__EPOS_ID;
        $serviceEposId = $this->EPOS_EXPRESSPAY_SERVICE_EPOS_ID;
        $orderId = $order_data['id_str'];
        $eposCode = "$serviceProviderEposCode-$serviceEposId-$orderId";

        $qrCode = $this->getQrCode($respone['ExpressPayInvoiceNo']);
        $qrCodeDescription = 'Отсканируйте QR-код для оплаты';

        if (isset($respone['Errors'])) {
            return array(
                'redirect' => $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL),
            );
        } else {
            $view = wa()->getView();
            $view->assign('order_id', str_replace('#', '', $orderId));
            $view->assign('epos_code', str_replace('#', '', $eposCode));
            $view->assign('qr_code', str_replace('#', '', '<img src="data:image/jpeg;base64,' . $qrCode . '"  width="400" height="200"/>'));
            $view->assign('qr_code_description', str_replace('#', '', $qrCodeDescription));
            echo ($this->path);
            return $view->fetch($this->path . '/templates/success.html');
        }
    }

    // Отправка POST запроса
    public function sendRequestPOST($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * 
     * Получения Qr-кода
     * 
     * @param int $invoice_no Номер счета полученый в процессе выставления его выставления
     * 
     * @return string $result Qr-код в формате base64
     * 
     */
    private function getQrCode($invoice_no)
    {
        $params = array(
            "Token" => $this->EPOS_EXPRESSPAY_TOKEN,
            "InvoiceId" => $invoice_no,
            'ViewType' => 'base64'
        );

        $url = 'https://api.express-pay.by/v1/qrcode/getqrcode/?';

        $params["Signature"] = $this->compute_signature($params, $this->EPOS_EXPRESSPAY_SECRET_WORD, $this->EPOS_EXPRESSPAY_TOKEN, 'get_qr_code');

        $request_params_for_qr  = http_build_query($params);

        $response_qr = file_get_contents($url . $request_params_for_qr);
        $response_qr = json_decode($response_qr, true);

        return $response_qr['QrCodeBody'];
    }

    private function compute_signature($request_params, $secret_word, $token, $method = 'add_invoice')
    {
        $secret_word = trim($secret_word);
        $normalized_params = array_change_key_case($request_params, CASE_LOWER);
        $api_method = array(
            'add_invoice' => array(
                "serviceid",
                "accountno",
                "amount",
                "currency",
                "expiration",
                "info",
                "surname",
                "firstname",
                "patronymic",
                "city",
                "street",
                "house",
                "building",
                "apartment",
                "isnameeditable",
                "isaddresseditable",
                "isamounteditable",
                "emailnotification",
                "smsphone",
                "returntype",
                "returnurl",
                "failurl"
            ),
            'get_qr_code' => array(
                "invoiceid",
                "viewtype",
                "imagewidth",
                "imageheight"
            ),
            'add_invoice_return' => array(
                "accountno",
                "invoiceno"
            )
        );

        $result = $token;

        foreach ($api_method[$method] as $item)
            $result .= (isset($normalized_params[$item])) ? $normalized_params[$item] : '';
        $hash = strtoupper(hash_hmac('sha1', $result, $secret_word));

        return $hash;
    }

    function computeSignature($request_params, $secret_word, $token)
    {
        $secret_word = trim($secret_word);
        $normalized_params = array_change_key_case($request_params, CASE_LOWER);
        $api_method = array(
            "expresspayaccountnumber",
            "expresspayinvoiceno"
        );
        $result = $token;
        foreach ($api_method as $item)
            $result .= (isset($normalized_params[$item])) ? strtolower($normalized_params[$item]) : '';

        $hash = strtoupper(hash_hmac('sha1', $result, $secret_word));

        return $hash;
    }

    protected function callbackInit($request)
    {
        $pattern = '/^([a-z]+)_(.+)_(.+)$/';
        $pattern2 = '/^([a-z]+)_(.+)$/';
        if (isset($request['action']) && $request['action'] == 'notify' && !empty($request['app_id']) && preg_match($pattern2, $request['app_id'], $match)) {
            $data = $_POST['Data'];
            $data = json_decode($data, true);
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];

            $strtmp = str_replace('{$order.id}', '', wa()->getSetting('order_format'));
            $strtmp = str_replace('#', '', $strtmp);
            $this->order_id = str_replace($strtmp, '', $data['AccountNo']);
        } elseif (!empty($request['app_id']) && preg_match($pattern, $request['app_id'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];
        }
        return parent::callbackInit($request);
    }

    protected function callbackHandler($request)
    {
        if (isset($request['action']) && $request['action'] == 'notify') {
            $data = $_POST['Data'];
            $signature = $_POST['Signature'];
            $data = json_decode($data, true);
            if ($this->EPOS_EXPRESSPAY_USE_NOTIF_SECRET_WORD || isset($signature)) {
                $computeSignature = $this->computeNotifSignature($_POST['Data'], $this->EPOS_EXPRESSPAY_NOTIF_SECRET_WORD);
                if ($computeSignature == $signature) {
                    $transaction_data = $this->formalizeDataTwo($request, $data);
                } else {
                    header("HTTP/1.0 403 Forbidden");
                    echo 'Incorrect Digital Signature';
                    die();
                }
            } else {
                $transaction_data = $this->formalizeDataTwo($request, $data);
            }
        } elseif (isset($request['ExpressPayAccountNumber']) && isset($request['ExpressPayInvoiceNo']) && isset($request['Signature'])) {
            $arrayInfo = array(
                'ExpressPayAccountNumber'     => $request['ExpressPayAccountNumber'],
                'ExpressPayInvoiceNo'         => $request['ExpressPayInvoiceNo']
            );

            $tmpSignature = $this->computeSignature($arrayInfo, $this->EPOS_EXPRESSPAY_SECRET_WORD, $this->EPOS_EXPRESSPAY_TOKEN);;
            if ($tmpSignature != $request['Signature']) {
                return array(
                    'redirect' => $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL)
                );
            }

            $transaction_data = $this->formalizeData($request);
            $transaction_data['type'] = self::OPERATION_CHECK;
        } else {
            return array(
                'redirect' => $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data),
            );
        }

        switch ($transaction_data['type']) {
            case self::OPERATION_CHECK: //в обработке-> обработан
                $app_payment_method = self::CALLBACK_CONFIRMATION;
                $transaction_data['state'] = self::STATE_AUTH;
                break;
            case self::OPERATION_AUTH_CAPTURE: //оплата
                $app_payment_method = self::CALLBACK_PAYMENT;
                $transaction_data['state'] = self::STATE_CAPTURED;
                break;
            case self::OPERATION_CANCEL: //отмена
                $app_payment_method = self::CALLBACK_REFUND;
                $transaction_data['state'] = self::STATE_CANCELED;
                break;
            default:
                break;
        }

        // сохраняем данные транзакции в базу данных
        $transaction_data = $this->saveTransaction($transaction_data, $request);

        // вызываем соответствующий обработчик приложения для каждого из поддерживаемых типов транзакций
        $result = $this->execAppCallback($app_payment_method, $transaction_data);

        if (isset($request['action']) && $request['action'] == 'notify') {
            header("HTTP/1.0 200 OK");
            echo 'SUCCESS';
            die();
        } else {
            if (isset($this->EPOS_EXPRESSPAY_SUCCESS_URL) && $this->EPOS_EXPRESSPAY_SUCCESS_URL != '') {
                return array(
                    'redirect' => $this->EPOS_EXPRESSPAY_SUCCESS_URL . "/?ExpressPayAccountNumber=" . $transaction_data['order_id'],
                );
            }
            return array(
                'redirect' => $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS),
            );
            $view = wa()->getView();
            echo ($this->path);
            return $view->fetch($this->path . '/templates/success.html');
        }
        header("HTTP/1.0 500");
        echo 'FAIL';
        die();
    }
    function formalizeData($request)
    {
        // формируем полный список полей, относящихся к транзакциям
        $fields = array(
            'wa_app',
            'wa_merchant_contact_id'

        );
        foreach ($fields as $f) {
            if (!isset($request[$f])) {
                $request[$f] = null;
            }
        }
        // выполняем базовую обработку данных
        $transaction_data = parent::formalizeData($request);
        // добавляем дополнительные данные:

        $transaction_data['native_id'] = $request['ExpressPayInvoiceNo'];


        // номер заказа
        $strtmp = str_replace('{$order.id}', '', wa()->getSetting('order_format'));
        $strtmp = str_replace('#', '', $strtmp);
        $transaction_data['order_id'] = str_replace($strtmp, '', $request['ExpressPayAccountNumber']);
        // сумма заказа
        $transaction_data['amount'] = $request['amount'];
        // идентификатор валюты заказа
        $transaction_data['currency_id'] = 'BYN';

        return $transaction_data;
    }

    function formalizeDataTwo($request, $data)
    {
        // формируем полный список полей, относящихся к транзакциям, которые обрабатываются платежной системой 
        $fields = array(
            'wa_app',
            'wa_merchant_contact_id'

        );
        foreach ($fields as $f) {
            if (!isset($request[$f])) {
                $request[$f] = null;
            }
        }
        // выполняем базовую обработку данных
        $transaction_data = parent::formalizeData($request);
        // добавляем дополнительные данные:
        // тип транзакции


        if ($data['CmdType'] == 1) {
            $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
        } elseif ($data['CmdType'] == 2) {
            $transaction_data['type'] = self::OPERATION_CANCEL;
        } elseif ($data['CmdType'] == 3 && $data['Status'] == 3) {
            $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
        }

        $transaction_data['native_id'] =  $data['PaymentNo'];

        $strtmp = str_replace('{$order.id}', '', wa()->getSetting('order_format'));
        $strtmp = str_replace('#', '', $strtmp);
        // номер заказа
        $transaction_data['order_id'] =  str_replace($strtmp, '', $data['AccountNo']);
        // сумма заказа
        $transaction_data['amount'] = $data['Amount'];
        // идентификатор валюты заказа
        $transaction_data['currency_id'] = 'BYN';
        return $transaction_data;
    }

    // Проверка электронной подписи
    function computeNotifSignature($json, $secretWord)
    {
        $hash = NULL;

        $secretWord = trim($secretWord);

        if (empty($secretWord))
            $hash = strtoupper(hash_hmac('sha1', $json, ""));
        else
            $hash = strtoupper(hash_hmac('sha1', $json, $secretWord));
        return $hash;
    }
}
