<?php

declare(strict_types=1);

namespace App\Controller;

use Strata\Frontend\Cms\RestData;
use Strata\Frontend\ContentModel\ContentModel;

class SharedController
{
    protected $api;

    public function __construct()
    {
        $this->api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
    }

    public function getCSCMessage()
    {

        try {
            $this->api->setContentType('csc_message');
            $cscMessage = $this->api->getOne(0);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('CSC Message API broken, please check WordPress', $e);
        }

        return $cscMessage->getContent()->get('csc_message')->getValue();
    }
}
