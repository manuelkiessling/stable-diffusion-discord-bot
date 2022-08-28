<?php

namespace App\SymfonyMessageHandler;

use App\SymfonyMessage\VisualizeSymfonyMessage;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\WebSockets\Event;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class VisualizeSymfonyMessageHandler
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(VisualizeSymfonyMessage $message): void
    {
        $this->logger->info("Got 'visualize' message with prompt '{$message->getPrompt()}' from channel id '{$message->getDiscordChannelId()}'.");

        $discord = new Discord([
            'token' => 'MTAxMzM3MzA0MzYyNTcwNTUxMw.Gy5jK6.ApVUkSGi9Y51z3cne5BV-sgLOoXuSFpb388FY0',
        ]);

        $discord->on('ready', function (Discord $discord) use ($message) {
            $this->logger->info('Discord is ready.');
            $promise = $discord->getChannel($message->getDiscordChannelId())
                ->sendMessage(
                    MessageBuilder::new()->setContent("Starting visualization of prompt '{$message->getPrompt()}'. Please wait...")
                );

            $promise->then(function () use ($discord, $message) {

                $outdirpath = 'stable-diffusion-result-' . sha1(rand(0, PHP_INT_MAX));

                shell_exec("/usr/bin/env bash ~/discord-bot-backend/bin/visualize.sh \"{$message->getPrompt()}\" $outdirpath");

                $promise = $discord->getChannel($message->getDiscordChannelId())
                    ->sendMessage(
                        MessageBuilder::new()
                            ->setContent("Here is the visualization of your prompt '{$message->getPrompt()}':")
                            ->addFile("/var/tmp/$outdirpath/samples/00000.png")
                    );

                $promise->then(function () use ($discord) {
                    $this->logger->info('Message was sent, closing Discord.');
                    $discord->close();
                });

            });
        });

        $discord->run();

        $this->logger->info("Finished 'visualize' message with prompt '{$message->getPrompt()}'.");
    }
}
