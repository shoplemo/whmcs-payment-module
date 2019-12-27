<?php

    if (!defined('WHMCS'))
    {
        exit('This file cannot be accessed directly');
    }
    function shoplemo_config()
    {
        return [
            'FriendlyName' => [
                'Type' => 'System',
                'Value' => 'Shoplemo',
            ],
            'shoplemoApiKey' => ['FriendlyName' => 'Shoplemo Api Key', 'Type' => 'text', 'Size' => '80'],
            'shoplemoApiSecret' => ['FriendlyName' => 'Shoplemo Api Secret', 'Type' => 'text', 'Size' => '80'],
        ];
    }

    function shoplemo_activate()
    {
    }

    function shoplemo_link($params)
    {
        ini_set('display_errors', 0);
        error_reporting(0);
        if (isset($_SERVER['HTTP_CLIENT_IP']))
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $orderItems = [];
        $orderItems[] = [
            'category' => 0,
            'name' => $params['description'],
            'quantity' => 1,
            'type' => 1,
            'price' => (int) (number_format($params['amount'], 2, '.', '') * 100),
        ];

        $requestBody = [
            'user_email' => $params['clientdetails']['email'],
            'buyer_details' => [
                'ip' => $ip,
                'port' => $_SERVER['REMOTE_PORT'],
                'city' => $params['clientdetails']['city'],
                'country' => $params['clientdetails']['country'],
                'gsm' => $params['clientdetails']['phonenumber'],
                'name' => $params['clientdetails']['firstname'],
                'surname' => $params['clientdetails']['lastname'],
            ],
            'basket_details' => [
                'currency' => 'TRY',
                'total_price' => (int) (number_format($params['amount'], 2, '.', '') * 100),
                'discount_price' => 0,
                'items' => $orderItems,
            ],
            'shipping_details' => [
                'full_name' => $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'],
                'phone' => $params['clientdetails']['phonenumber'],
                'address' => $params['clientdetails']['address1'] . ' ' . $params['clientdetails']['address2'] . ' ' . $params['clientdetails']['state'],
                'city' => $params['clientdetails']['city'],
                'country' => $params['clientdetails']['country'],
                'postalcode' => $params['clientdetails']['postcode'],
            ],
            'billing_details' => [
                'full_name' => $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'],
                'phone' => $params['clientdetails']['phonenumber'],
                'address' => $params['clientdetails']['address1'] . ' ' . $params['clientdetails']['address2'] . ' ' . $params['clientdetails']['state'],
                'city' => $params['clientdetails']['city'],
                'country' => $params['clientdetails']['country'],
                'postalcode' => $params['clientdetails']['postcode'],
            ],
            'custom_params' => json_encode([
                'invoice_id' => $params['invoiceid'],
            ]),
            'redirect_url' => $params['returnurl'],
            'fail_redirect_url' => $params['systemurl'],
        ];

        $requestBody = json_encode($requestBody);
        if (function_exists('curl_version'))
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://payment.shoplemo.com/paywith/credit_card');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 90);
            curl_setopt($ch, CURLOPT_SSLVERSION, 6);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($requestBody),
                'Authorization: Basic ' . base64_encode($params['shoplemoApiKey'] . ':' . $params['shoplemoApiSecret']),
            ]);
            $result = @curl_exec($ch);

            if (curl_errno($ch))
            {
                die('Shoplemo connection error. Details: ' . curl_error($ch));
            }

            curl_close($ch);
            try
            {
                $result = json_decode($result, 1);
            }
            catch (Exception $ex)
            {
                return 'Failed to handle response';
            }
        }
        else
        {
            echo 'CURL fonksiyonu yüklü değil?';
        }
        if ($result['status'] == 'success')
        {
            return '<a href="' . $result['url'] . '" class="btn btn-info">' . $params['langpaynow'] . '</a>';
        }

        $errors = '';
        foreach ($result['details'] as $detail)
        {
            $errors .= $detail . '<br />';
        }

        return $errors;
    }
