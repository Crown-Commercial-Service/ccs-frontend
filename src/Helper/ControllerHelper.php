<?php

declare(strict_types=1);

namespace App\Helper;

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
}
