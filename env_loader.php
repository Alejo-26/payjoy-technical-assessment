<?php

function loadEnv(string $filePath): void
{
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        // Ignore empty lines or comments
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Split using the first equal sign
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // removing the quotation simple or doubles if they exist
            $value = trim($value, "\"'");

            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}