<?php

namespace App\Controller;

use App\Calculation\CalculationInterface;
use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

final class ConferenceController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'photo_dir')]
        private string $photoDir,
    )
    {
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
        MessageBusInterface $bus,
        Request $request,
        EntityManagerInterface $entityManager,
        Conference $conference,
        CommentRepository $commentRepository,
        SpamChecker $spamChecker,
        #[MapQueryParameter(options: ['min_range' => 0])]
        int $offset = 0,
    ): Response
    {
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $emptyForm = clone $form;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);
            $entityManager->persist($comment);

            if ($photo = $form['photo']->getData()) {
                /** @var UploadedFile $photo */
                $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                $photo->move($this->photoDir, $filename);
                $comment->setPhotoFilename($filename);
            }

            $entityManager->flush();

            $bus->dispatch(new CommentMessage($comment->getId(), [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ]));

            if (TurboBundle::STREAM_FORMAT !== $request->getPreferredFormat()) {
                return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
            }

            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->renderBlock('conference/show.html.twig', 'comment_stream', [
                'comment_form' => $emptyForm,
            ]);
        }

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::COMMENTS_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::COMMENTS_PER_PAGE),
            'comment_form' => $form,
        ]);
    }
}
