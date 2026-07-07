<?php
declare(strict_types=1);

namespace App\Tests\App\Mock;

use App\Helper\ControllerHelper;

class MockControllerHelper extends ControllerHelper
{
    private $homeMessageBanner;
    private string $cscMessage = '';
    private string $orgId = 'test_org_id';

    // Accept the arguments to satisfy Symfony's DI binder, 
    // but leave the body empty so it NEVER loads the real RestData or YAML files
    public function __construct(
        string $appApiBaseUrl = '',
        string $appEnv = '',
        string $orgIdProd = '',
        string $orgIdTest = ''
    ) {
    }

    public function setHomeMessageBanner($value): void
    {
        $this->homeMessageBanner = $value;
    }

    public function setCscMessage(string $value): void
    {
        $this->cscMessage = $value;
    }

    public function setOrgId(string $value): void
    {
        $this->orgId = $value;
    }

    public function getHomeMessageBanner()
    {
        return $this->homeMessageBanner;
    }

    public function getCSCMessage(): string
    {
        return $this->cscMessage;
    }

    public function getOrgId(): string
    {
        return $this->orgId;
    }

    public function getYoutubeVideo()
    {
        return [
            'video_link' => 'https://mock-youtube.com/embed/test',
            'video_caption' => 'Mock Video'
        ];
    }
}