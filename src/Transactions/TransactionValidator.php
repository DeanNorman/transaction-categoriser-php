<?php
declare(strict_types=1);

namespace App\Transactions;

use DateTimeImmutable;

/**
 * Turns an untrusted JSON body into a TransactionInput or throws a
 * ValidationException listing every failing field at once.
 *
 * Field names follow the CSV columns in snake_case: reference, amount,
 * timestamp, description, type, account_number. Unknown fields are ignored.
 */
final class TransactionValidator
{
    public const MAX_DESCRIPTION = 255;
    public const MAX_REFERENCE = 64;
    public const MAX_ACCOUNT_NUMBER = 34; // the longest IBAN
    public const MAX_ABS_AMOUNT = '999999999999.99';

    /** @param array<string,mixed> $body */
    public function validate(array $body): TransactionInput
    {
        $errors = [];

        $reference = $this->optionalString($body, 'reference', self::MAX_REFERENCE, $errors);
        $amount = $this->amount($body, $errors);
        $occurredAt = $this->timestamp($body, $errors);
        $description = $this->requiredString($body, 'description', self::MAX_DESCRIPTION, $errors);
        $type = $this->type($body, $errors);
        $accountNumber = $this->requiredString($body, 'account_number', self::MAX_ACCOUNT_NUMBER, $errors);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var string $amount */
        /** @var DateTimeImmutable $occurredAt */
        /** @var string $description */
        /** @var TransactionType $type */
        /** @var string $accountNumber */
        return new TransactionInput($reference, $amount, $occurredAt, $description, $type, $accountNumber);
    }

    /** @param array<string,mixed> $body @param array<string,string> $errors */
    private function optionalString(array $body, string $field, int $max, array &$errors): ?string
    {
        if (!array_key_exists($field, $body) || $body[$field] === null) {
            return null;
        }
        if (!is_string($body[$field])) {
            $errors[$field] = 'must be a string';
            return null;
        }
        $value = trim($body[$field]);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > $max) {
            $errors[$field] = "must be at most $max characters";
            return null;
        }

        return $value;
    }

    /** @param array<string,mixed> $body @param array<string,string> $errors */
    private function requiredString(array $body, string $field, int $max, array &$errors): ?string
    {
        if (!array_key_exists($field, $body) || $body[$field] === null) {
            $errors[$field] = 'is required';
            return null;
        }
        if (!is_string($body[$field])) {
            $errors[$field] = 'must be a string';
            return null;
        }
        $value = trim($body[$field]);
        if ($value === '') {
            $errors[$field] = 'must not be empty';
            return null;
        }
        if (mb_strlen($value) > $max) {
            $errors[$field] = "must be at most $max characters";
            return null;
        }

        return $value;
    }

    /** @param array<string,mixed> $body @param array<string,string> $errors */
    private function amount(array $body, array &$errors): ?string
    {
        if (!array_key_exists('amount', $body) || $body['amount'] === null) {
            $errors['amount'] = 'is required';
            return null;
        }
        $raw = $body['amount'];
        if (is_bool($raw) || (!is_int($raw) && !is_float($raw) && !is_string($raw))) {
            $errors['amount'] = 'must be a number';
            return null;
        }
        $text = is_string($raw) ? trim($raw) : (string) $raw;
        if (!preg_match('/^[+-]?\d+(\.\d+)?$/', $text)) {
            $errors['amount'] = 'must be a decimal number such as -87.18';
            return null;
        }
        $normalised = number_format((float) $text, 2, '.', '');
        if (self::compareAbs($normalised, self::MAX_ABS_AMOUNT) > 0) {
            $errors['amount'] = 'is out of range';
            return null;
        }

        return $normalised;
    }

    /** @param array<string,mixed> $body @param array<string,string> $errors */
    private function timestamp(array $body, array &$errors): ?DateTimeImmutable
    {
        if (!array_key_exists('timestamp', $body) || $body['timestamp'] === null) {
            $errors['timestamp'] = 'is required';
            return null;
        }
        if (!is_string($body['timestamp']) || trim($body['timestamp']) === '') {
            $errors['timestamp'] = 'must be a date-time string';
            return null;
        }
        $text = trim($body['timestamp']);
        // Accept the bank's "2024-05-16 07:22:18.808433" and ISO 8601 "2024-05-16T07:22:18Z".
        if (!preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2}(\.\d{1,6})?)?(Z|[+-]\d{2}:?\d{2})?$/', $text)) {
            $errors['timestamp'] = 'must look like 2024-05-16 07:22:18 or 2024-05-16T07:22:18Z';
            return null;
        }
        try {
            return new DateTimeImmutable($text);
        } catch (\Exception) {
            $errors['timestamp'] = 'is not a valid date-time';
            return null;
        }
    }

    /** @param array<string,mixed> $body @param array<string,string> $errors */
    private function type(array $body, array &$errors): ?TransactionType
    {
        if (!array_key_exists('type', $body) || $body['type'] === null) {
            $errors['type'] = 'is required';
            return null;
        }
        $type = is_string($body['type']) ? TransactionType::tryFrom(strtolower(trim($body['type']))) : null;
        if ($type === null) {
            $errors['type'] = 'must be debit or credit';
            return null;
        }

        return $type;
    }

    /** Compare |$a| with $b as decimal strings without the bcmath extension. */
    private static function compareAbs(string $a, string $b): int
    {
        $a = ltrim($a, '+-');
        [$ai, $af] = array_pad(explode('.', $a, 2), 2, '');
        [$bi, $bf] = array_pad(explode('.', $b, 2), 2, '');
        $ai = ltrim($ai, '0') ?: '0';
        $bi = ltrim($bi, '0') ?: '0';
        if (strlen($ai) !== strlen($bi)) {
            return strlen($ai) <=> strlen($bi);
        }
        $cmp = strcmp($ai, $bi);
        if ($cmp !== 0) {
            return $cmp <=> 0;
        }
        $len = max(strlen($af), strlen($bf));

        return strcmp(str_pad($af, $len, '0'), str_pad($bf, $len, '0')) <=> 0;
    }
}
