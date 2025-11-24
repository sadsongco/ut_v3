<?php

namespace SUCheckout;

class SUCheckout
{
    private $headers;
    private $order_details = [];
    private $url = "https://api.sumup.com/v0.1/checkouts";
    private $response;
    private $checkout_id;

    public function __construct($order_details=false)
    {
        $this->headers = [
            "Authorization: Bearer " . SU_API_KEY,
            "Content-Type: application/json"
        ];
        $this->order_details = $order_details;
    }

    public function listCheckouts()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $this->response = curl_exec($ch);
        curl_close($ch);
        return $this;
    }

    public function createCheckout()
    {
        $post_body = json_encode([
            'checkout_reference' => $this->order_details['order_id'],
            'merchant_code' => SU_MERCHANT_CODE,
            'currency' => 'GBP',
            'amount' => $this->order_details['totals']['total']
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
        $this->response = json_decode(curl_exec($ch));
        curl_close($ch);
        $this->checkout_id = $this->response->id;
        return $this;
    }

    public function processCheckout()
    {
        $post_body = json_encode([
            'payment_type'=>'card',
            'card' => [
                'name' => $this->order_details['cc_name'],
                'number' => $this->order_details['cc_number'],
                'expiry_month' => $this->order_details['cc_exp_month'],
                'expiry_year' => $this->order_details['cc_exp_year'],
                'cvv' => $this->order_details['cc_cvv'],
                'type' => $this->order_details['cc_type'],
                'last_4_digits' => substr($this->order_details['cc_number'], -4)
            ],
            'personal_details' => [
                'first_name'=>$this->order_details['name'], 
                'last_name'=>$this->order_details['name'],
                'email'=>$this->order_details['email'],
                'address'=>[
                    'city'=>$this->order_details['billing-town'],
                    'country'=>$this->order_details['billing-country-code'],
                    'line1'=>$this->order_details['billing-address1'],
                    'line2'=>$this->order_details['billing-address2'],
                    'postal_code'=>$this->order_details['billing-postcode']
                ]
            ]
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->url . "/" . $this->checkout_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
        $this->response = json_decode(curl_exec($ch));
        curl_close($ch);
        return $this;
    }

    public function retrieveCheckout()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->url . "/" . $this->checkout_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $this->response = json_decode(curl_exec($ch));
        curl_close($ch);
        return $this;
    }

    public function listTransactions($query_params = [])
    {
        $query_string = http_build_query($query_params);
        $url =  "https://api.sumup.com/v2.1/merchants/".SU_MERCHANT_CODE."/transactions/history?order=descending&" . $query_string;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $this->response = json_decode(curl_exec($ch));
        curl_close($ch);
        return $this;

    }

    public function refundTransaction($transaction_id)
    {
        $url = "https://api.sumup.com/v0.1/me/refund/$transaction_id";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $this->response = json_decode(curl_exec($ch));
        curl_close($ch);
        // $this->checkout_id = $this->response->id;
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }
}