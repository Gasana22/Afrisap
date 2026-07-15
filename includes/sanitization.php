<?php
/**
 * Input sanitization helpers.
 */

function clean_string(mixed $value): string
{
    return trim(strip_tags((string) $value));
}

function clean_int(mixed $value): ?int
{
    return ($value === null || $value === '') ? null : (int) $value;
}

function clean_float(mixed $value): ?float
{
    return ($value === null || $value === '') ? null : (float) $value;
}

function clean_array(array $input, array $stringKeys = []): array
{
    $out = [];
    foreach ($stringKeys as $key) {
        if (array_key_exists($key, $input)) {
            $out[$key] = clean_string($input[$key]);
        }
    }

    return $out;
}

function sanitize_filename(string $name): string
{
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);

    return substr($name, 0, 200);
}
