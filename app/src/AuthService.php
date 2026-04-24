<?php
declare(strict_types=1);

namespace App;

final class AuthService
{
    public function signIn(string $email, string $password): array
    {
        $apiKey = getenv('FIREBASE_API_KEY');

        if (!is_string($apiKey) || $apiKey === '') {
            throw new \RuntimeException('FIREBASE_API_KEY não configurada.');
        }

        $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . urlencode($apiKey);

        $payload = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true,
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \RuntimeException('Erro CURL Auth: ' . curl_error($ch));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $decoded = json_decode($response, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = $decoded['error']['message'] ?? 'Erro ao autenticar.';
            throw new \RuntimeException('Falha no login: ' . $message);
        }

        return [
            'uid' => (string) ($decoded['localId'] ?? ''),
            'email' => (string) ($decoded['email'] ?? ''),
            'idToken' => (string) ($decoded['idToken'] ?? ''),
            'refreshToken' => (string) ($decoded['refreshToken'] ?? ''),
        ];
    }

    public static function requireLogin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function user(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return $_SESSION['user'] ?? null;
    }
}