<?php

declare(strict_types=1);

namespace App\Helper;

use App\Utils\FrameworkCategories;
use Strata\Frontend\Cms\RestData;
use Strata\Frontend\ContentModel\ContentModel;
use Strata\Frontend\Api\Providers\RestApi;

class ControllerHelper
{
    private static function setUpAPI($contentField)
    {

        $api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $api->setContentType($contentField);
        return $api;
    }

    public static function honeyPot($honeyPotField)
    {
        if (!empty($honeyPotField) && (bool) $honeyPotField == true) {
            die;
        }
    }

    public static function getOrgId()
    {
        return getenv('APP_ENV') === 'prod' ? getenv('ORG_ID_PROD') : getenv('ORG_ID_TEST');
    }

    public static function getCSCMessage()
    {

        $api = ControllerHelper::setUpAPI('csc_message');

        try {
            $cscMessage = $api->getOne(0);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('CSC Message API broken, please check WordPress', $e);
        }

        return $cscMessage->getContent()->get('csc_message')->getValue();
    }

    public static function getHomeMessageBanner()
    {
        $api = ControllerHelper::setUpAPI('message_banner');

        try {
            $messageBanner = $api->getOne(0);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('MessageBanner API broken, please check WordPress', $e);
        }

        return $messageBanner->getContent()->get('message_banner')->getValue()[0];
    }

    public static function getYoutubeVideo()
    {
        $api = ControllerHelper::setUpAPI('homepage_content');

        $defultValue = [
            "video_link" => "https://www.youtube-nocookie.com/embed/mn-3isisTGM",
            'video_caption' => 'CCS: power to your procurement'
        ];

        try {
            $result = $api->getOne(0);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Homepage Content API broken, please check WordPress', $e);
        }

        $video = $result->getContent()->get('video')->getValue();

        return empty($video["video_link"]) ? $defultValue : $video;
    }

    public static function toSlug(string $string): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
        return $slug;
    }

    public static function toSlugList(array $inputArray, string $prefix = ""): string
    {
        $slugList = array_reduce(
            $inputArray,
            fn($output, $each) => $output .= $prefix . ControllerHelper::toSlug($each) . "|"
        );

        return rtrim((string) $slugList, "|");
    }

    public static function getFormData($params)
    {
        return [
            'name' => $params->get('name', null),
            'email' => $params->get('email', null),
            'company' => $params->get('company', null),
            'jobTitle' => $params->get('jobTitle', null),
        ];
    }

    public static function converArrayToStringForWordpress($selectedArray, $totalOption)
    {
        if ($selectedArray == null || count($selectedArray) == $totalOption) {
            return null;
        }

        return implode(',', (array) $selectedArray);
    }

    public static function extractRmNumberFromReferrer($referrer)
    {
        if (!(empty(trim((string) $referrer)) || is_null($referrer))) {
            $referrerInArray = explode("agreements/RM", (string) $referrer);

            $regex = "/^\d{4}(\.[a-zA-Z0-9]{1,4})?$/";   //4 digits follow by 4 decimal places

            if (isset($referrerInArray[1]) && preg_match($regex, $referrerInArray[1])) {
                return "RM{$referrerInArray[1]}";
            }
        }
        return null;
    }

    public static function removeFromArray(array $arrayToRemove, array $valuesToRemove)
    {
        foreach ($arrayToRemove as $key => $value) {
            if (in_array($value, $valuesToRemove)) {
                unset($arrayToRemove[$key]);
            }
        }
        return $arrayToRemove;
    }

    public static function getArrayFromStringForParam($request, string $paramName, string $allSelected = "")
    {

        if ($request->query->get($allSelected, false)) {
            return [];
        }

        if (!is_array($request->query->get($paramName))) {
            return $request->query->get($paramName) != null ? explode(",", (string) $request->query->get($paramName)) : [];
        }

        return array_map(fn($string) => str_replace('+', ' ', $string), $request->query->get($paramName));
    }

    public static function validateCategory($request, array $pillarArray, string $paramName)
    {

        if (!is_array($request->query->get($paramName))) {
            return $request->query->get($paramName) != null ? [explode(",", (string) $request->query->get($paramName)), $pillarArray] : [[], $pillarArray];
        }

        $pillarsAndCategories =  FrameworkCategories::getAllPillars()["pillars"];

        $selected = array_map(fn($string) => str_replace('+', ' ', $string), $request->query->get($paramName, []));

        foreach ($pillarsAndCategories as $eachPillar) {
            $allCat = array_column($eachPillar["categories"], "name");

            $isPillarIncluded = !empty(array_diff($allCat, $selected));
            $pillarName = $eachPillar["name"];

            if (!$isPillarIncluded) {
                if (!in_array($pillarName, $pillarArray)) {
                    $pillarArray[] = $pillarName;
                }
            } else {
                $key = array_search($pillarName, $pillarArray);
                if ($key !== false) {
                    unset($pillarArray[$key]);
                }
            }
        }

        return [$selected, $pillarArray];
    }
}
