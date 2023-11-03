<?php

declare(strict_types=1);

namespace App\Helper;

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
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
        return $slug;
    }

    public static function toSlugList(array $inputArray, string $prefix = ""): string
    {
        $slugList = array_reduce(
            $inputArray,
            fn($output, $each) => $output .= $prefix . ControllerHelper::toSlug($each) . "|"
        );

        return rtrim($slugList, "|");
    }

    public static function getFormData($params)
    {
        return [
            'name' => $params->get('name', null),
            'email' => $params->get('email', null),
            'company' => $params->get('company', null),
            'jobTitle' => $params->get('00Nb0000009IXEs', null),
        ];
    }
}
