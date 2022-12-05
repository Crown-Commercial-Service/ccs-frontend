<?php

declare(strict_types=1);

namespace App\Helper;

use Strata\Frontend\Cms\RestData;
use Strata\Frontend\ContentModel\ContentModel;

class ControllerHelper
{
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

    public static function getCSCMessage($api)
    {
        $api->setContentType('csc_message');
        try {
            $cscMessage = $api->getOne(0);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('CSC Message API broken, please check WordPress', $e);
        }

        return $cscMessage->getContent()->get('csc_message')->getValue();
    }
}
