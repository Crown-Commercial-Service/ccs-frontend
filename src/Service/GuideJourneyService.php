<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service for handling guide journeys.
 *
 * This service provides methods to retrieve journey data from JSON files
 * and navigate through nodes and outcomes within a journey.
 */
class GuideJourneyService
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * GuideJourneyService constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->projectDir = $kernel->getProjectDir();
    }

    /**
     * Get the data for a specific journey.
     *
     * @param string $journey
     * @return array|null
     */
    public function getJourneyData(string $journey): ?array 
    {
        $path = $this->projectDir . '/data/guide_journeys/' . $journey . '.json';
        if (!file_exists($path)) return null;

        return json_decode(file_get_contents($path), true);
    }

    /**
     * Find a node or outcome by ID.
     *
     * @param array $data
     * @param string $id
     * @return array|null
     */
    public function findNode(array $data, string $id): ?array
    {
        if (isset($data['nodes'][$id])) {
            return ['type' => 'question', 'data' => $data['nodes'][$id]];
        }
        if (isset($data['outcomes'][$id])) {
            return ['type' => 'outcome', 'data' => $data['outcomes'][$id]];
        }
        
        return null;
    }

    /**
     * Helper to find the UUID in nodes or outcomes
     *
     * @param string $uuid
     * @param array $data
     * @return array|null
     */
    public function findNodeOrOutcome(string $uuid, array $data): ?array
    {
        if (isset($data['nodes'][$uuid])) {
            return ['type' => 'node', 'data' => $data['nodes'][$uuid]];
        }
        
        if (isset($data['outcomes'][$uuid])) {
            return ['type' => 'outcome', 'data' => $data['outcomes'][$uuid]];
        }

        return null;
    }
}
