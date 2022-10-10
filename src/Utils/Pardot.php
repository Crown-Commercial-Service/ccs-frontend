<?php

declare(strict_types=1);

namespace App\Utils;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Class to manage data submissions to Pardot
 * @package App\Utils
 */
class Pardot
{
    /** @var ResponseInterface */
    protected $lastResponse;

    /**
     * Submit email address to Pardot form handler
     *
     * @param string $formUrl Form URL to submit data to
     * @param string $email Email address to submit, the only required field
     * @param array $data Other data to submit to Pardot
     * @return bool
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function submitEmail(string $formUrl, string $email, array $data = []): bool
    {
        $params = ['email' => $email];
        foreach ($data as $key => $val) {
            $params[$key] = $val;
        }

        $client = HttpClient::create();
        $response = $client->request('POST', $formUrl, [
            'body' => $params
        ]);

        $this->lastResponse = $response;

        // Success?
        if (!$response->getStatusCode() === 200) {
            return false;
        }

        $content = $response->getContent();
        if (preg_match('/Please correct the following errors/', $content)) {
            return false;
        }

        return true;
    }

    /**
     * Return last response
     *
     * @return ResponseInterface
     */
    public function getLastResponse(): ResponseInterface
    {
        return $this->lastResponse;
    }
}
