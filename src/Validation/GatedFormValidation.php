<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Class to do validation for form submition before sending it to Salesforce
 * @package App\Validation
 */
class GatedFormValidation
{

    public static function validationName($name)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#name',
        ];

        if (empty(trim($name))) {
            $returnArray['errors'] = ['Enter your name'];
        } elseif (preg_match('~[0-9]~', $name)) {
            $returnArray['errors'] = ['Enter your name'];
        }

        return $returnArray;
    }

    public static function validationEmail($email)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#email',
        ];


        if (empty(trim($email))) {
            $returnArray['errors'] = ['Enter an email address in the correct format, like name@example.com'];
        } elseif (preg_match("/[*,!,#,$,%,^,&,(,),?,<,>,=]/i", $email)) {
            $returnArray['errors'] = ['Enter an email address in the correct format, like name@example.com'];
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

        if (preg_match("/[a-z]/i", $phone) || preg_match("/[*,!,#,$,%,^,&,?,<,>,=]/i", $phone)) {
            $returnArray['errors'] = ['Enter a telephone number in the correct format'];
        }

        return $returnArray;
    }

    public static function validationCompany($company)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#company',
        ];

        $returnArray['errors'] = empty(trim($company)) ? ['Enter your organisation'] : [] ;

        return $returnArray;
    }
}
