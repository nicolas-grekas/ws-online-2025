<?php

namespace App\Controller;

use App\Calculation\CalculationInterface;
use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class ConferenceController extends AbstractController
{
    public function __construct(
        #[AutowireIterator(CalculationInterface::class)]
        private iterable $calculations
    )
    {
        foreach ($this->calculations as $calculation) {
            dump([$calculation::class => $calculation->calculate()]);
        }
    }

    #[Route('/', name: 'homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll()
        ]);
    }

    #[Route('/conference/{slug:conference}', name: 'conference')]
    public function show(
        Conference $conference,
        CommentRepository $commentRepository,
        #[MapQueryParameter(options: ['min_range' => 0])]
        int $offset = 0,
    ): Response
    {
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::COMMENTS_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::COMMENTS_PER_PAGE),
        ]);
    }
}
