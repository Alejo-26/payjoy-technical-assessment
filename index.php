<?php

require_once __DIR__ . '/env_loader.php';

// upload the enviroment variables .env
loadEnv(__DIR__ . '/.env');

// to always answer in JSON format
header('Content-Type: application/json; charset=utf-8');

// 1. Validate the API_KEY in the enviroment
$apiKey = $_ENV['EXCHANGE_RATE_API_KEY'] ?? null;
if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server configuration error: EXCHANGE_RATE_API_KEY is not defined in .env'
    ]);
    exit;
}

// 2. Extract the parameters GET
$rawAmount = $_GET['amount'] ?? null;
$rawCurrency = $_GET['currency'] ?? null;

// Validation: Required parameters
if ($rawAmount === null || $rawCurrency === null) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required parameters. Both "amount" and "currency" are required.'
    ]);
    exit;
}

// Validation: Amount numeric and positive
if (!is_numeric($rawAmount) || floatval($rawAmount) <= 0) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid "amount". It must be a positive numeric value.'
    ]);
    exit;
}

$amount = floatval($rawAmount);
$currency = strtoupper(trim($rawCurrency));

// 3. Call the ExchangeRate API
$apiUrl = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_FOLLOWLOCATION => true,
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode([
        'error' => 'External service error: ' . $curlError
    ]);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['result']) || $data['result'] !== 'success') {
    http_response_code(502);
    echo json_encode([
        'error' => 'ExchangeRate API call failed',
        'details' => $data['error-type'] ?? 'Unknown external error'
    ]);
    exit;
}

// 4. Validate if the currency exist on the currency catalog
$rates = $data['conversion_rates'] ?? [];
if (!array_key_exists($currency, $rates)) {
    http_response_code(404);
    echo json_encode([
        'error' => "Unsupported or invalid target currency code: '{$currency}'"
    ]);
    exit;
}

// 5. Calculate the currency conversion and build JSON output 
$rate = (float) $rates[$currency];
$convertedAmount = round($amount * $rate, 2);

http_response_code(200);
echo json_encode([
    'amount_usd' => $amount,
    'currency'   => $currency,
    'converted'  => $convertedAmount,
    'rate'       => $rate
], JSON_PRETTY_PRINT);