<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WordpressService
{
    private string $wpUrl;
    protected string $wpUser;
    protected string $wpPassword;

    public function __construct()
    {
        $this->wpUrl = config('services.wordpress.url');
        $this->wpUser = config('services.wordpress.username');
        $this->wpPassword = config('services.wordpress.password');
    }

    public function doPost(array $datos): array
    {
        $response = Http::withBasicAuth($this->wpUser, $this->wpPassword)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->post($this->wpUrl . '/wp-json/wp/v2/posts', $datos);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->body(),
        ];
    }
}
