<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use FacturaOnline\FacturaOnlineClient;

$apiKey = getenv('FACTURAONLINE_API_KEY') ?: '';
$secretKey = getenv('FACTURAONLINE_SECRET_KEY') ?: '';
$baseUrl = getenv('FACTURAONLINE_BASE_URL') ?: 'https://facturaonline.com.mx/api/';

if ($apiKey === '' || $secretKey === '') {
    throw new RuntimeException('Define FACTURAONLINE_API_KEY y FACTURAONLINE_SECRET_KEY para ejecutar esta prueba manual.');
}

$client = new FacturaOnlineClient($apiKey, $secretKey, $baseUrl);
$response = $client->empresa()->listarEmpresas();

print_r($response);
