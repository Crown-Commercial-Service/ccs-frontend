<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class PageController extends AbstractController
{

    /**
     * Homepage
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function home()
    {
        return $this->render('pages/home.html.twig');
    }

    /**
     * Simple page controller to test frontend templates
     *
     * @param $slug
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pageTemplate(string $slug)
    {
        $template = 'pages/' . $slug . '.html.twig';

        if (!file_exists(__DIR__ . '/../../templates/' . $template)) {
            throw $this->createNotFoundException('Template not found at ' . $template);
        }

        return $this->render($template);
    }


    /**
     * Simple healthcheck
     *
     * @return JsonResponse
     */
    public function healthcheck()
    {
        $required = '7.1.3';
        if (version_compare(PHP_VERSION, $required) < 0) {
            return new JsonResponse(['message' => sprintf("PHP version must be %s or above, found '%s'", $required, PHP_VERSION)], 500);
        }

        // Check Composer has loaded required classes
        $required = [
            'Symfony\Bundle\FrameworkBundle\Controller\AbstractController',
            'Studio24\Frontend\Cms\RestData',
            'Studio24\Frontend\Cms\Wordpress'
        ];
        foreach ($required as $class) {
            if (!class_exists($class)) {
                return new JsonResponse(['message' => sprintf("Class '%s' does not exist", $class)], 500);
            }
        }

        // Check required environment variables
        $required = [
            'APP_API_BASE_URL',
            'APP_ENV'
        ];
        foreach ($required as $variable) {
            if (empty(getenv($variable))) {
                return new JsonResponse(['message' => sprintf("Environment variable '%s' not set", $variable)], 500);
            }
        }

        return new JsonResponse(['message' => 'OK']);
    }

}
