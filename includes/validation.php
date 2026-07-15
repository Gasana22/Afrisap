<?php
/**
 * Lightweight input validation.
 *
 * validate() returns an array of field => error message for anything that
 * failed. An empty array means the input passed all rules.
 *
 * Rule syntax: 'required|email', 'required|min:3|max:50', 'numeric', 'in:a,b,c'
 */

function validate(array $data, array $rules): array
{
    $errors = [];

    foreach ($rules as $field => $ruleString) {
        $value = $data[$field] ?? null;
        $fieldRules = explode('|', $ruleString);

        foreach ($fieldRules as $rule) {
            [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

            $error = validate_rule($name, $param, $field, $value);
            if ($error) {
                $errors[$field] = $error;
                break;
            }
        }
    }

    return $errors;
}

function validate_rule(string $name, ?string $param, string $field, mixed $value): ?string
{
    $label = humanize($field);

    return match ($name) {
        'required' => ($value === null || $value === '') ? "$label is required." : null,
        'email' => ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) ? "$label must be a valid email address." : null,
        'numeric' => ($value !== null && $value !== '' && !is_numeric($value)) ? "$label must be a number." : null,
        'min' => ($value !== null && $value !== '' && strlen((string) $value) < (int) $param) ? "$label must be at least $param characters." : null,
        'max' => ($value !== null && strlen((string) $value) > (int) $param) ? "$label must not exceed $param characters." : null,
        'in' => ($value !== null && $value !== '' && !in_array($value, explode(',', (string) $param), true)) ? "$label is invalid." : null,
        'date' => ($value && !strtotime($value)) ? "$label must be a valid date." : null,
        default => null,
    };
}

function validation_failed(array $errors): bool
{
    return !empty($errors);
}
