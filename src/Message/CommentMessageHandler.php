<?php

namespace App\Message;

use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class CommentMessageHandler
{
    public function __construct(
        private SpamChecker $spamChecker,
        private EntityManagerInterface $entityManager,
        private CommentRepository $commentRepository,
    )
    {
    }
    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->id);

        if (2 === $this->spamChecker->getSpamScore($comment, $message->context)) {
            $comment->setState('spam');
        } else {
            $comment->setState('published');
        }

        $this->entityManager->flush();
    }
}