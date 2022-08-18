<?php

namespace ReemRadwan\Paymob;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Paymob
{
    public $amount;
    public $PAYMOB_API_KEY;

    public function __construct()
    {
        $this->amount = null;
        $this->PAYMOB_API_KEY = config('PAYMOB_API_KEY');
    }


    public function authenticate()
    {
        $response = Http::post('https://accept.paymob.com/api/auth/tokens',
            ["api_key" => $this->PAYMOB_API_KEY]);
        return $response->json();
    }

    public function register_order()
    {
        $key = $this->authenticate();
        $order = Http::post('https://accept.paymob.com/api/ecommerce/orders',
            [
                "auth_token" => $key['auth_token'],
                "delivery_needed" => "false",
                "amount_cents" => $this->amount * 100,
                "currency"=> "EGP",
                // "merchant_order_id" =>
                "items" => []
            ]);





        return $order->json();
    }

    public function paymentkey_request()
    {
        $key = $this->authenticate();
        $order = $this->register_order();
        $payment_details = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
            "auth_token" => $key['token'],
            "expiration" => 36000,
            "amount_cents" => $order['amount_cents'],
            "order_id" => $order['id'],
            "billing_data" => ["apartment" => "NA",
                "email" => Auth::user()->email,
                "floor" => "NA",
                "first_name" => (null ==Auth::user()->first_name) ? Auth::user()->name :Auth::user()->first_name,
                "street" => "NA",
                "building" => "NA",
                "phone_number" =>Auth::user()->phone,
                "shipping_method" => "NA",
                "postal_code" => "NA",
                "city" => "NA",
                "country" => "NA",
                "last_name" => (null == Auth::user()->last_name) ? Auth::user()->name : Auth::user()->last_name,
                "state" => "NA"],
            "currency" => "EGP",
            "integration_id" => env('PAYMOB_MODE') == "live" ? env('PAYMOB_LIVE_INTEGRATION_ID') : env('PAYMOB_TEST_INTEGRATION_ID')
        ]);
        return $payment_details->json();
    }

    public function verify_payment(Request $request)
    {

        $string = $request['amount_cents'] . $request['created_at'] . $request['currency'] . $request['error_occured'] . $request['has_parent_transaction'] . $request['id'] . $request['integration_id'] . $request['is_3d_secure'] . $request['is_auth'] . $request['is_capture'] . $request['is_refunded'] . $request['is_standalone_payment'] . $request['is_voided'] . $request['order'] . $request['owner'] . $request['pending'] . $request['source_data_pan'] . $request['source_data_sub_type'] . $request['source_data_type'] . $request['success'];
        if (hash_hmac('sha512', $string, env('PAYMOB_HMAC')))
        {
            $this->make_payment();
        }
        else
        {
            return "Something went wrong";
        }
    }

    public function make_payment(){
        $payment_token = $this->paymentkey_request();
        $payment = redirect("https://accept.paymobsolutions.com/api/acceptance/iframes/" . env("PAYMOB_IFRAME_ID") . "?payment_token=" . $payment_token['token']);
        return $payment;

    }

    public function capture_order($token, $transactionId, $amount)
    {
        $key = $this->authenticate();
        $order = $this->register_order();
        $json = [
            "auth_token" => $key['token'],
            'transaction_id' => $transactionId,
            'amount_cents'   => $order['amount_cents']
        ];

        $capture_transaction = Http::post('https://accept.paymob.com/api/acceptance/capture', $json);


        return $capture_transaction->json();
    }


}
