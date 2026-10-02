<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Validate    //checks user input. every method returns the error text (in the language of the visitor) or null when the input is fine
{
    //free text, also usable for a username or a password (only the length is checked): length is counted in characters, so umlauts count once
    public function ValidateText($input, $displayName, $min = 2, $max = 100)
    {
        $length = mb_strlen((string) $input);
        if ($length === 0) {
            return t('validate.missing', ['field' => $displayName]);
        } elseif ($length < $min) {
            return t('validate.too_short', ['field' => $displayName, 'min' => $min]);
        } elseif ($length > $max) {
            return t('validate.too_long', ['field' => $displayName, 'max' => $max]);
        }
    }

    //input that has to match a pattern, e.g. a phone number, a username or a year:
    //$validate->ValidatePattern($phone, 'Phone', '/^[0-9+()\/\-. ]+$/', 40). empty input is fine unless $required is true
    public function ValidatePattern($input, $displayName, $pattern, $max = 100, $required = false)
    {
        $input = (string) $input;
        if ($input === '') {
            return $required ? t('validate.missing', ['field' => $displayName]) : null;
        }
        if (mb_strlen($input) > $max) {
            return t('validate.too_long', ['field' => $displayName, 'max' => $max]);
        }
        if (!preg_match($pattern, $input)) {
            return t('validate.invalid', ['field' => $displayName]);
        }
    }

    public function ValidateEmail($input)
    {
        if (empty($input)) {
            return t('validate.email_missing');
        } elseif (strlen($input) > 254 || !filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return t('validate.email_invalid');
        }
    }
}
