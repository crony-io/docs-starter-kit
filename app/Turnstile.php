<?php

namespace App;

use App\Models\SystemConfig;
use Illuminate\Support\Facades\Http;

class Turnstile
{
    public function validate(string $response): array
    {
        $config = SystemConfig::instance();
        $secretKey = $config->turnstile_secret_key;

        if (! empty($secretKey)) {
            $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

            $http = app()->environment('local')
                ? Http::withoutVerifying()
                : Http::withOptions(['verify' => true]);

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $http
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'secret' => $secretKey,
                    'response' => $response,
                ]);

            if ($response->successful()) {
                $json = $response->json();
                if ($json['success']) {
                    return [
                        'status' => 1,
                    ];
                }

                return [
                    'status' => 0,
                    'turnstile_response' => $json,
                ];
            }

            return [
                'status' => 0,
                'error' => 'Unknown error occured',
            ];
        }

        return [
            'status' => 0,
            'error' => 'Turnstile secret not found',
        ];
    }
}
