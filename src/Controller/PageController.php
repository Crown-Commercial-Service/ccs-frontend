<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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

}