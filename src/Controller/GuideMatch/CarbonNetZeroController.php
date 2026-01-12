<?php

namespace App\Controller\GuideMatch;

use App\Controller\GuideMatch\BaseJourneyController;

/**
 * Controller for the Carbon Net Zero journey.
 *
 * Handles the specific landing page data for the Carbon Net Zero guide match journey.
 */
class CarbonNetZeroController extends BaseJourneyController
{
    /** @var string The name of the journey. */
    protected string $journeyName = 'cnz';

    protected function getLandingPageData(): array {
        return [
            'title' => 'Find a carbon net zero solution',
            'description' => 'Use this service to help you find a solution that can help you meet your carbon net zero goals.',
        ];
    }
}
