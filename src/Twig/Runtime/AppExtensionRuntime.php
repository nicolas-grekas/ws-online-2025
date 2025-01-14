<?php

namespace App\Twig\Runtime;

use App\Repository\ConferenceRepository;
use Twig\Extension\RuntimeExtensionInterface;

class AppExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private ConferenceRepository $conferenceRepository
    )
    {
    }

    public function getConferences()
    {
        return $this->conferenceRepository->findAll();
    }
}
