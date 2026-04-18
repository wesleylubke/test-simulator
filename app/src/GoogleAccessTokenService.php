<?php
declare(strict_types=1);

namespace App;

final class GoogleAccessTokenService
{
    private string $credentialsPath;

    public function __construct(string $credentialsPath)
    {
        $this->credentialsPath = $credentialsPath;
    }

    public function getAccessToken(): string
    {
        if ($this->credentialsPath === '') {
            throw new \Exception('GOOGLE_APPLICATION_CREDENTIALS não definido.');
        }

        if (!file_exists($this->credentialsPath)) {
            throw new \Exception('Arquivo de credenciais não encontrado: ' . $this->credentialsPath);
        }

        if (!is_file($this->credentialsPath)) {
            throw new \Exception('O caminho de credenciais não é um arquivo: ' . $this->credentialsPath);
        }

        $raw = file_get_contents($this->credentialsPath);
        if ($raw === false) {
            throw new \Exception('Não foi possível ler o arquivo de credenciais: ' . $this->credentialsPath);
        }

        $credentials = json_decode($raw, true);
        if (!is_array($credentials)) {
            throw new \Exception('JSON de credenciais inválido.');
        }

        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;
        $tokenUri = $credentials['token_uri'] ?? null;

        if (!is_string($clientEmail) || $clientEmail === '') {
            throw new \Exception('client_email ausente no credentials.json');
        }

        if (!is_string($privateKey) || $privateKey === '') {
            throw new \Exception('private_key ausente no credentials.json');
        }

        if (!is_string($tokenUri) || $tokenUri === '') {
            throw new \Exception('token_uri ausente no credentials.json');
        }

        $now = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => $tokenUri,
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $jwt = $this->encodeJwt($header, $payload, $privateKey);
        $response = $this->requestAccessToken($tokenUri, $jwt);

        if (!isset($response['access_token']) || !is_string($response['access_token'])) {
            throw new \Exception('Erro ao obter access token: ' . json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        return $response['access_token'];
    }

    private function encodeJwt(array $header, array $payload, string $privateKey): string
    {
        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        $signatureInput = $base64UrlHeader . '.' . $base64UrlPayload;

        $success = openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if ($success !== true) {
            throw new \Exception('Falha ao assinar JWT com openssl_sign.');
        }

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
            'assertion' => $jwt,
        ]);

        $ch = curl_init($tokenUri);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \Exception('Erro CURL: ' . curl_error($ch));
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : [];
    }
}