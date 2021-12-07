<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Class to do validation for form submition before sending it to Salesforce
 * @package App\Validation
 */
class FormValidation
{

    public static function validationName($name)
    {
        $returnArray  = [
            'errors' => [],
            'link' => '#name',
        ];

        if (empty($name)) {
            $returnArray['errors'] = ['Enter your name'];
        } elseif (!empty($name) && strlen($name) > 80) {
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

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $returnArray['errors'] = ['Enter an email address in the correct format, like name@example.com'];
        } elseif (!empty($email) && strlen($email) > 80) {
            $returnArray['errors'] = ['Email address must be 80 characters or fewer'];
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

        if (empty($company)) {
            $returnArray['errors'] = ['Enter an organisation'];
        } elseif (!empty($company) && strlen($company) > 80) {
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

        if (empty($jobTitle)) {
            $returnArray['errors'] = ['Enter your job title'];
        }

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
}
