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

    public static function validationPhone($phone, $callback)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#phone',
        ];
        if ( !($callback == "No" || $callback == null) && empty(trim($phone))) {
            $returnArray['errors'] = ['Enter a telephone number, like 01632 960 001, 07700 900 982 or +44 0808 157 0192'];
        } elseif (preg_match("/[a-z]/i", $phone) || preg_match("/[*,!,#,$,%,^,&,?,<,>,=]/i", $phone)) {
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

    public static function validationJobTitle($jobTitle)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#00Nb0000009IXEs',
        ];

        $returnArray['errors'] = empty(trim($jobTitle)) ? ['Enter your job title'] : [] ;

        return $returnArray;
    }

    public static function validationMoreDetail($moreDetail)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#more-detail',
        ];

        $returnArray['errors'] = empty(trim($moreDetail)) ? ['Enter more detail'] : [] ;

        return $returnArray;
    }
}
