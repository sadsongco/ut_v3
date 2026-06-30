<?php

namespace SUCheckout;
use \stdClass;

class SUCheckout
{
    private array $headers;
    private array | bool $order_details;
    private string $checkout_url = "https://api.sumup.com/v0.1/checkouts";
    private string $customer_url = "https://api.sumup.com/v0.1/customers";
    private object $response;
    private string $host;
    private string $checkout_id;
    private string $su_customer_id;

    public function __construct($order_details=false, $host=false)
    {
        if (!$order_details) throw new \Exception("No order details provided");
        if (!$host) throw new \Exception("No host provided");
        $this->headers = [
            "Authorization: Bearer " . SU_API_KEY,
            "Content-Type: application/json"
        ];
        $this->host = $host;
        $this->order_details = $order_details;
    }

    public function createCustomer()
    {
        $encrypted_id = openssl_encrypt($this->order_details['customer_id'], SU_ENCRYPTION_CIPHER, SU_ENCRYPTION_KEY, false, SU_ENCRYPTION_IV);
        $this->su_customer_id = $encrypted_id;
        $post_body = json_encode([
            'customer_id' => $encrypted_id,
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
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $this->customer_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
        $this->response = json_decode(curl_exec($ch));
        unset($ch);
        return $this;
    }

    public function listCheckouts()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->checkout_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $this->response = json_decode(curl_exec($ch));
        unset($ch);
        return $this;
    }

    public function createCheckout($subscription=false)
    {
        $params = [
            'checkout_reference' => $this->order_details['order_id'],
            'merchant_code' => SU_MERCHANT_CODE,
            'currency' => 'GBP',
            'amount' => $this->order_details['totals']['total'],
            'description' => "Purchase",
            'return_url' => $this->host . "/shop/su_response.php",
            'purpose' => 'CHECKOUT'
        ];
        if ($subscription) {
            $params['description'] = "Subscription";
            $params['customer_id'] = $this->su_customer_id;
            $params['purpose'] = 'SETUP_RECURRING_PAYMENT';
        }
        $post_body = json_encode($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $this->checkout_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
        $this->response = json_decode(curl_exec($ch));
        if (isset($this->response->id))
            $this->checkout_id = $this->response->id;
        unset($ch);
        return $this;
    }

    public function processCheckout()
    {
        if (!$this->checkout_id) {
            $response = new stdClass();
            $response->error_code = "NO_CHECKOUT_ID";
            $this->response = $response;
            return $this;
        }
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
        curl_setopt($ch, CURLOPT_URL, $this->checkout_url . "/" . $this->checkout_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
        $this->response = json_decode(curl_exec($ch));
        unset($ch);
        return $this;
    }

    public function deactivateCheckout()
    {
        $params = ["checkout_id"=>$this->response->id];
        $post_body = json_encode($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->checkout_url . "/" . $this->response->id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
        $this->response = json_decode(curl_exec($ch));
        unset($ch);
        return $this;
    }

    public function retrieveCheckout(string | bool $id=false)
    {
        if (!$id && !$this->checkout_id) {
            $response = new stdClass();
            $response->error_code = "NO_CHECKOUT_ID";
            $this->response = $response;
        }
        if (!$id) $id = $this->checkout_id;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->checkout_url . "/" . $id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $this->response = json_decode(curl_exec($ch));
        unset($ch);
        return $this;
    }

    public function getCheckoutId() {
        return $this->checkout_id;
    }

    public function setCheckoutId(string $id) {
        $this->checkout_id = $id;
        return $this;
    }

    public function listTransactions(array $query_params = [])
    {
        $query_string = http_build_query($query_params);
        $url =  "https://api.sumup.com/v2.1/merchants/".SU_MERCHANT_CODE."/transactions/history?order=descending&" . $query_string;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $this->response = json_decode(curl_exec($ch));
        unset($ch);
        return $this;

    }

    public function refundTransaction(string $transaction_id)
    {
        $url = "https://api.sumup.com/v0.1/me/refund/$transaction_id";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $this->response = json_decode(curl_exec($ch));
        unset($ch);
        return $this;
    }

    public function deleteTransaction(string $transaction_id) {
        $url = "https://api.sumup.com/v0.1/checkouts/$transaction_id";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $this->response = json_decode(curl_exec($ch));
        unset($ch);
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getOrderDetails()
    {
        return $this->order_details;
    }
}