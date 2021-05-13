<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Class to do validation for form submition before sending it to Salesforce
 * @package App\Validation
 */
class ContactCCSFormValidation
{

    public static function validationName($name)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#name',
        ];

        if (empty($name)) {
            return $returnArray;
        } else {
            if (preg_match('~[0-9]~', $name)) {
                $returnArray['errors'] = ['Please enter your name without numbers'];
            }
        }

        return $returnArray;
    }

    public static function validationEmail($email)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#email',
        ];


        if (empty($email)) {
            $returnArray['errors'] = ['Enter an email address in the correct format, like name@example.com'];
        } elseif (preg_match("/[*,!,#,$,%,^,&,(,),?,<,>,=]/i", $email)) {
            $returnArray['errors'] = ['Enter an email address that does not contain any of these invalid characters *, !, #, $, %, ^, &, (, ), ?, <, >, ='];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $returnArray['errors'] = ['Enter an email address in the correct format, like name@example.com'];
        }
        return $returnArray;
    }

    public static function validationPhone($phone)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#phone',
        ];

        if (empty($phone) || preg_match("/[a-z]/i", $phone) || preg_match("/[*,!,#,$,%,^,&,(,),?,<,>,=]/i", $phone)) {
            $returnArray['errors'] = ['Please enter a valid phone number using only numbers and no special characters'];
        }

        return $returnArray;
    }
}
