<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Temporary controller to output templates
 * @todo Delete this method and route once pages integrated
 */
class TempTemplateController extends AbstractController
{
    /**
     * Homepage
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function home()
    {
        return $this->render('temp-templates/home.html.twig');
    }

    /**
     * Simple page controller to test frontend templates
     *
     * @todo Delete this method and route once pages integrated
     *
     * @param $slug
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pageTemplate(string $slug)
    {
        $template = 'temp-templates/' . $slug . '.html.twig';

        if (!file_exists(__DIR__ . '/../../templates/' . $template)) {
            throw $this->createNotFoundException('Template not found at ' . $template);
        }

        return $this->render($template);
    }

}
