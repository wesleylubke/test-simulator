<?php

require __DIR__ . '/vendor/autoload.php';

use App\GoogleAccessTokenService;

$credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

$service = new GoogleAccessTokenService($credentialsPath);

try {
    $token = $service->getAccessToken();
    echo "Token gerado com sucesso:\n\n";
    echo substr($token, 0, 50) . "...\n";
} catch (Exception $e) {
    echo "Erro:\n";
    echo $e->getMessage();
}