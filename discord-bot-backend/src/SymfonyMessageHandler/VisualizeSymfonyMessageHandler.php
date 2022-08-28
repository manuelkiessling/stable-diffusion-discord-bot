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
            $discord->getChannel($message->getDiscordChannelId())
                ->sendMessage(
                    MessageBuilder::new()->setContent("Starting visualization of prompt '{$message->getPrompt()}'. Please wait...")
                );



            $promise = $discord->getChannel($message->getDiscordChannelId())
                ->sendMessage(
                    MessageBuilder::new()
                        ->setContent("Here is the visualization of your prompt '{$message->getPrompt()}':")
                        ->addFile('/Users/manuel/sd-outputs/txt2img-samples/samples/00005.png')
                );

            $promise->then(function () use ($discord) {
                $this->logger->info('Message was sent, closing Discord.');
                $discord->close();
            });
        });

        $discord->run();

        $this->logger->info("Finished 'visualize' message with prompt '{$message->getPrompt()}'.");
    }
}
