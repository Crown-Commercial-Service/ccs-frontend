<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Class to do validation for form submition before sending it to Salesforce
 * @package App\Validation
 */
class EsourcingTrainingFormValidation
{
    public static function validatioCustomerType($customerType)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#customerType-error',
        ];

        $returnArray['errors'] = $customerType == null ? ['Select one option'] : [] ;


        return $returnArray;
    }

    public static function validationDate($customerType, $buyerDate, $supplierDate)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#date-error',
        ];

        if ($customerType == null) {
            return $returnArray;
        } elseif ($buyerDate == null && $supplierDate == null) {
            $returnArray['errors'] = ['Select the date that you would like to book your eSourcing tool training'];
        } else {
            $inputDate = $buyerDate == null ? $supplierDate : $buyerDate;

            $dateTime = explode("-", $inputDate);
            $inputDate = strtotime("$dateTime[0]");
            $todayDate = strtotime(date("Y/m/d"));

            if ($todayDate > $inputDate) {
                $returnArray['errors'] = ['The date you have selected is in the past, select the date of an upcoming event'];
            }
        }

        return $returnArray;
    }

    public static function validationName($name)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#name',
        ];

        if (preg_match('~[0-9]~', $name)) {
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

        if (empty(trim($phone)) || preg_match("/[a-z]/i", $phone) || preg_match("/[*,!,#,$,%,^,&,?,<,>,=]/i", $phone)) {
            $returnArray['errors'] = ['Enter a telephone number, like 01632 960 001, 07700 900 982 or +44 0808 157 0192'];
        }

        return $returnArray;
    }
}
