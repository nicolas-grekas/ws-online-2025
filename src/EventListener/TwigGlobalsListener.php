<?php

namespace App\EventListener;

use App\Entity\Conference;
use App\Repository\ConferenceRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

#[AsEventListener]
class TwigGlobalsListener
{
    public function __construct(
        private Environment $twig,
        private ConferenceRepository $conferenceRepository,
    ) {        
    }

    public function __invoke(RequestEvent $event): void
    {
        $this->twig->addGlobal('conferences', $this->conferenceRepository->findAll());
    }
}
