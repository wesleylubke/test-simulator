<?php

namespace App;

class GoogleAccessTokenService
{
    private string $credentialsPath;

    public function __construct(string $credentialsPath)
    {
        $this->credentialsPath = $credentialsPath;
    }

    public function getAccessToken(): string
    {
        $credentials = json_decode(file_get_contents($this->credentialsPath), true);

        $clientEmail = $credentials['client_email'];
        $privateKey = $credentials['private_key'];
        $tokenUri = $credentials['token_uri'];

        $now = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        $payload = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => $tokenUri,
            'exp' => $now + 3600,
            'iat' => $now
        ];

        $jwt = $this->encodeJwt($header, $payload, $privateKey);

        $response = $this->requestAccessToken($tokenUri, $jwt);

        if (!isset($response['access_token'])) {
            throw new \Exception('Erro ao obter access token: ' . json_encode($response));
        }

        return $response['access_token'];
    }

    private function encodeJwt(array $header, array $payload, string $privateKey): string
    {
        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signatureInput = $base64UrlHeader . '.' . $base64UrlPayload;

        openssl_sign($signatureInput, $signature, $privateKey, 'sha256');

        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $signatureInput . '.' . $base64UrlSignature;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function requestAccessToken(string $tokenUri, string $jwt): array
    {
        $postFields = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        $ch = curl_init($tokenUri);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \Exception('Erro CURL: ' . curl_error($ch));
        }

        return json_decode($response, true);
    }
}