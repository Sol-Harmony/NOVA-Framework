<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 * 
 * Copyright (C) 2025 Hamzah Mansor 
 **/
class Validate    //class containing functions, to check the user input
{
    public function stdValidate($input, $displayName)
    {
        if (empty($input)) {
            return  "Missing " . $displayName;
        } elseif (strlen($input) > 32) {
            return $displayName . " is too long!!!";
        } elseif (strlen($input) < 2) {
            return $displayName . " is too short!";
        } elseif (!preg_match('/^[a-zA-Z0-9_\.\!\-\(\)]+$/', $input)) {
            return "Invalid " . $displayName . ". Only (a-z, A-Z, ( ! . - _ ) and numbers are allowed!";
        }
    }

    public function Validatepassword($input)
    {
        if (empty($input)) {
            return "Password missing!";
        } elseif (strlen($input) > 32 || strlen($input) < 8) {
            return "Password lengh must be between 8 and 32";
        }
        //no character check on purpose: passwords get hashed, so special characters are safe and make them stronger
    }

    public function ValidateUsername($input)
    {
        if (empty($input)) {
            return "Username missing!";
        } elseif (strlen($input) > 32 || strlen($input) < 4) {
            return "Username has to have a lengh between 4 and 32";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $input)) {
            return "Invalid Username";
        }
    }

    public function ValidateDate($input)
    {
        if (empty($input)) {
            return "Date missing!";
        } elseif (!preg_match('/^(1[89]|20)\d{2}$/', $input)) {
            return "Invalid Date";
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

    //free text (name, message): length is counted in characters, so umlauts count once
    public function ValidateText($input, $displayName, $min = 2, $max = 100)
    {
        $length = mb_strlen($input);
        if ($length === 0) {
            return t('validate.missing', ['field' => $displayName]);
        } elseif ($length < $min) {
            return t('validate.too_short', ['field' => $displayName, 'min' => $min]);
        } elseif ($length > $max) {
            return t('validate.too_long', ['field' => $displayName, 'max' => $max]);
        }
    }

    public function ValidateEmpty($input)
    {
        if (is_null($input) || strlen($input) == 0) {
            return $input . " missing!";
        }
    }
}
