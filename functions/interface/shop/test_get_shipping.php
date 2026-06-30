<?php

namespace RM_PRICE;
include_once(__DIR__ . "/../../functions.php");

class RM_PRICE {

    private string $country_code;
    private int $weight;
    private array $headers;

    public function __construct(string $country_code, int $weight) {
        $this->country_code = $country_code;
        $this->weight = $weight;
        $this->headers = [
            "Authorization: Bearer " . RM_PRICE_API_KEY,
            "X-IBM-Client-Id: clientId",
            "accept: application/json"
        ];
    }

    public function getShippingOptions() {
        $url = RM_PRICE_API_BASE_URL . "?country=" . $this->country_code . "&weight=" . $this->weight;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $this->headers,
        ]);
        $response = curl_exec($ch);
        unset($ch);
        return $response;
    }
}

use RM_PRICE\RM_PRICE;
$rm_price = new RM_PRICE("GBR", 723);
$shipping_options = $rm_price->getShippingOptions();
p_2($shipping_options);
