<?php

namespace App\SymfonyMessage;

class VisualizeSymfonyMessage
{
    private string $prompt;

    private int $seed;

    private string $format;

    private string $discordInteractionId;

    private string $discordChannelId;

    private string $discordUserId;

    private string $discordUserUsername;

    public function __construct(
        string $prompt,
        int $seed,
        string $format,
        string $discordInteractionId,
        string $discordChannelId,
        string $discordUserId,
        string $discordUserUsername,
    )
    {
        $this->prompt = $prompt;
        $this->seed = $seed;
        $this->format = $format;
        $this->discordInteractionId = $discordInteractionId;
        $this->discordChannelId = $discordChannelId;
        $this->discordUserId = $discordUserId;
        $this->discordUserUsername = $discordUserUsername;

    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getSeed(): int
    {
        return $this->seed;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getDiscordInteractionId(): string
    {
        return $this->discordInteractionId;
    }

    public function getDiscordChannelId(): string
    {
        return $this->discordChannelId;
    }

    public function getDiscordUserId(): string
    {
        return $this->discordUserId;
    }

    public function getDiscordUserUsername(): string
    {
        return $this->discordUserUsername;
    }
}
