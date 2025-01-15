<?php

namespace App\Message;

final class CommentMessage
{
    public function __construct(
        public int $id,
        public array $context,
    ) {
    }
}