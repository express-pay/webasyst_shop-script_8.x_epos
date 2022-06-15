<?php
return array(
    'EPOS_EXPRESSPAY_TOKEN' => array(
        'value'         => '',
        'title'         => 'Токен',
        'description'   => 'API-ключ производителя услуг',
        'control_type'  => 'input'
    ),
    'EPOS_EXPRESSPAY_SERVICEID' => array(
        'value'         => '',
        'title'         => 'Номер услуги',
        'description'   => 'Номер услуги в системе express-pay.by',
        'control_type'  => 'input'
    ),
    'EPOS_EXPRESSPAY_SERVICE_PROVIDER__EPOS_ID' => array(
        'value'         => '',
        'title'         => 'Код производителя услуг',
        'description'   => 'Код производителя системе E-POS',
        'control_type'  => 'input'
    ),
    'EPOS_EXPRESSPAY_SERVICE_EPOS_ID' => array(
        'value'         => '',
        'title'         => 'Номер услуги в системе E-POS',
        'description'   => 'Номер услуги в системе E-POS',
        'control_type'  => 'input'
    ),
    'EPOS_EXPRESSPAY_SECRET_WORD' => array(
        'value'         => '',
        'title'         => 'Секретное слово',
        'description'   => 'Секретное слово используется для формирования цифровой подписи',
        'control_type'  => 'input'
    ),
    'EPOS_EXPRESSPAY_INFO' => array(
        'value'         => '',
        'title'         => 'Описание заказа',
        'description'   => 'Описание заказу будет отображаться при оплате в системе ЕРИП',
        'control_type'  => 'input'
    ),
    'EPOS_EXPRESSPAY_TESTMODE' => array(
        'value'         => false,
        'title'         => 'Тестовый режим',
        'description'   => '',
        'control_type' => 'checkbox'
    ),
    'EPOS_EXPRESSPAY_USE_NOTIF_SECRET_WORD' => array(
        'value'         => false,
        'title'         => 'Использовать цифровую подись для уведолений',
        'description'   => '',
        'control_type' => 'checkbox'
    ),
    'EPOS_EXPRESSPAY_NOTIF_SECRET_WORD' => array(
        'value'         => '',
        'title'         => 'Секретное слово для уведомлений',
        'description'   => 'Секретное слово используется для формирования цифровой подписи',
        'control_type' => 'input'
    ),
);