<?php

namespace App\SymfonyMessage;

class VisualizeSymfonyMessage
{
    private string $prompt;

    public function __construct(string $prompt)
    {
        $this->prompt = $prompt;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }
}
