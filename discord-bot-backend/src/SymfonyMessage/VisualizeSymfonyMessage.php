<?php

namespace App\SymfonyMessage;

class VisualizeSymfonyMessage
{
    private string $prompt;

    private string $discordMessageId;

    private string $discordChannelId;

    public function __construct(string $prompt, string $discordMessageId, string $discordChannelId)
    {
        $this->prompt = $prompt;
        $this->discordMessageId = $discordMessageId;
        $this->discordChannelId = $discordChannelId;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getDiscordMessageId(): string
    {
        return $this->discordMessageId;
    }

    public function getDiscordChannelId(): string
    {
        return $this->discordChannelId;
    }
}
