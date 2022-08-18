<?php

namespace ReemRadwan\Paymob;

use Illuminate\Support\Facades\Http;

class CardPayment extends Paymob
{
    public function authenticate()
    {
        //TODO
        return redirect('https://accept.paymobsolutions.com/api/acceptance/iframes/{{your_iframe_id}}?payment_token={{payment_token_obtained_from_step_3}}');
    }
}
