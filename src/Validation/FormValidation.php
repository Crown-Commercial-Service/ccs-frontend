<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Class to do validation for form submition before sending it to Salesforce
 * @package App\Validation
 */
class FormValidation
{
    public static function validationNameForEsourcingTraining($name)
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

    public static function validationName($name)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#name',
        ];

        if (empty(trim($name)) || preg_match('~[0-9]~', $name)) {
            $returnArray['errors'] = ['Enter your name'];
        } elseif (strlen($name) > 80) {
            $returnArray['errors'] = ['Name must be 80 characters or fewer'];
        }

        return $returnArray;
    }

    public static function validationEmail($email)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#email',
        ];

        if (empty(trim($email)) || !filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match("/[*,!,#,$,%,^,&,(,),?,<,>,=]/i", $email)) {
            $returnArray['errors'] = ['Enter an email address in the correct format, like name@example.com'];
        } elseif (strlen($email) > 80) {
            $returnArray['errors'] = ['Email address must be 80 characters or fewer'];
        }

        return $returnArray;
    }

    public static function validationPhoneOnly($phone)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#phone',
        ];

        if (empty(trim($phone)) || preg_match("/[a-z]/i", $phone) || preg_match("/[*,!,#,$,%,^,&,?,<,>,=]/i", $phone)) {
            $returnArray['errors'] = ['Enter a telephone number in the correct format'];
        }

        return $returnArray;
    }

    public static function validationPhone($phone, $callback)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#phone',
        ];

        if ($callback && empty(trim($phone))) {
            $returnArray['errors'] = ['Enter a telephone number'];
        } elseif (!empty($phone) && strlen($phone) > 20) {
            $returnArray['errors'] = ['Telephone number must be 20 characters or fewer'];
        }

        return $returnArray;
    }

    public static function validationCompany($company)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#company',
        ];

        if (empty(trim($company))) {
            $returnArray['errors'] = ['Enter an organisation'];
        } elseif (strlen($company) > 80) {
            $returnArray['errors'] = ['Organisation must be 80 characters or fewer'];
        }

        return $returnArray;
    }

    public static function validationJobTitle($jobTitle)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#jobTitle',
        ];

        $returnArray['errors'] = empty(trim($jobTitle)) ? ['Enter your job title'] : [] ;

        return $returnArray;
    }

    public static function validationDescription($description)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#description',
        ];

        if (empty($description)) {
            $returnArray['errors'] = ['Enter more detail'];
        }

        return $returnArray;
    }

    public static function validationAggregationOption($aggregationOption)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#aggregationOption',
        ];

        if ($aggregationOption == null) {
            $returnArray['errors'] = ['Please select one option'];
        }

        return $returnArray;
    }

    public static function validationJobTitleForContactCCS($jobTitle)
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
        $returnArray = [
        'errors' => [],
        'link' => '#date-error',
        ];

        if (is_null($customerType)) {
            return $returnArray;
        }

        if (is_null($buyerDate) && is_null($supplierDate)) {
            $returnArray['errors'] = ['Select the date that you would like to book your eSourcing tool training'];
        } else {
            $inputDate = is_null($buyerDate) ? $supplierDate : $buyerDate;

            $dateTime = explode("-", $inputDate);
            $inputDate = strtotime($dateTime[0]);
            $todayDate = strtotime(date("Y/m/d"));

            if ($todayDate > $inputDate) {
                $returnArray['errors'] = ['The date you have selected is in the past, select the date of an upcoming event'];
            }
        }

        return $returnArray;
    }
}
