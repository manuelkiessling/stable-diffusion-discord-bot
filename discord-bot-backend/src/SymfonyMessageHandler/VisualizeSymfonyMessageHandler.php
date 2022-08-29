<?php

namespace App\SymfonyMessageHandler;

use App\SymfonyMessage\VisualizeSymfonyMessage;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
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

    public function __invoke(VisualizeSymfonyMessage $symfonyMessage): void
    {
        $this->logger->info("Got 'visualize' message with prompt '{$symfonyMessage->getPrompt()}' from channel id '{$symfonyMessage->getDiscordChannelId()}'.");

        $outdirpath = 'stable-diffusion-result-' . sha1(rand(0, PHP_INT_MAX));

        $w = 512;
        $h = 512;

        if ($symfonyMessage->getFormat() === 'landscape') {
            $h = 256;
        }

        if ($symfonyMessage->getFormat() === 'portrait') {
            $w = 768;
        }

        shell_exec("/usr/bin/env bash ~/discord-bot-backend/bin/visualize.sh \"{$symfonyMessage->getPrompt()}\" {$symfonyMessage->getSeed()} $outdirpath $w $h");

        $discord = new Discord([
            'token' => 'MTAxMzM3MzA0MzYyNTcwNTUxMw.Gy5jK6.ApVUkSGi9Y51z3cne5BV-sgLOoXuSFpb388FY0',
        ]);

        $discord->on('ready', function (Discord $discord) use ($symfonyMessage, $outdirpath) {
            $this->logger->info('Discord is ready.');

            $messageBuilder = MessageBuilder::new()
                ->setContent("<@{$symfonyMessage->getDiscordUserId()}>")
                ->addEmbed(
                    new Embed($discord, [
                        'title' => "Your visualization has been finished",
                        'description' => "Your prompt was\n`{$symfonyMessage->getPrompt()}`.",
                        'type' => Embed::TYPE_RICH,
                        'color' => '0x5b001e'
                    ]),
                );
            for ($i = 0; $i < 10; $i++) {
                if (file_exists("/var/tmp/$outdirpath/samples/0000$i.png")) {
                    $messageBuilder->addFile("/var/tmp/$outdirpath/samples/0000$i.png");
                } else {
                    break;
                }
            }

            $promise = $discord->getChannel($symfonyMessage->getDiscordChannelId())
                ->sendMessage($messageBuilder);

            $promise->then(function () use ($discord) {
                $this->logger->info('Message was sent, closing Discord.');
                $discord->close();
            });

        });

        $discord->run();

        $this->logger->info("Finished 'visualize' message with prompt '{$symfonyMessage->getPrompt()}'.");
    }
}
