<?php

namespace App\SymfonyMessageHandler;

use App\SymfonyMessage\VisualizeSymfonyMessage;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
class VisualizeSymfonyMessageHandler
{
    private string $discordBotToken;

    private LoggerInterface $logger;

    public function __construct(
        string $discordBotToken,
        LoggerInterface $logger
    )
    {
        $this->discordBotToken = $discordBotToken;
        $this->logger = $logger;
    }

    public function __invoke(VisualizeSymfonyMessage $symfonyMessage): void
    {
        $this->logger->info("Got 'visualize' message with prompt '{$symfonyMessage->getPrompt()}' from channel id '{$symfonyMessage->getDiscordChannelId()}'.");

        $outdirpath = 'stable-diffusion-result-' . sha1(rand(0, PHP_INT_MAX));

        mkdir("/var/tmp/$outdirpath");
        file_put_contents(
            "/var/tmp/$outdirpath/info.txt",
            "prompt: {$symfonyMessage->getPrompt()}\nseed: {$symfonyMessage->getSeed()}\nformat: {$symfonyMessage->getFormat()}\ndiscordInteractionId: {$symfonyMessage->getDiscordInteractionId()}\ndiscordUserId: {$symfonyMessage->getDiscordUserId()}\ndiscordUserUsername: {$symfonyMessage->getDiscordUserUsername()}\ndiscordChannelId: {$symfonyMessage->getDiscordChannelId()}\n"
        );

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
            'token' => $this->discordBotToken,
        ]);

        $discord->on('ready', function (Discord $discord) use ($symfonyMessage, $outdirpath) {
            $this->logger->info('Discord is ready.');

            $this->logger->info('Starting to build message.');
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
            $this->logger->info('Finished building message.');

            $this->logger->info('Starting to add files to message.');
            for ($i = 0; $i < 10; $i++) {
                if (file_exists("/var/tmp/$outdirpath/samples/0000$i.png")) {
                    $messageBuilder->addFile("/var/tmp/$outdirpath/samples/0000$i.png");
                    $this->logger->info("Added file /var/tmp/$outdirpath/samples/0000$i.png to message.");
                } else {
                    break;
                }
            }
            $this->logger->info('Finished adding files to message.');

            $this->logger->info('Starting to send message.');
            $promise = $discord->getChannel($symfonyMessage->getDiscordChannelId())
                ->sendMessage($messageBuilder);

            $promise->then(function () use ($discord) {
                $this->logger->info('Message was sent, closing Discord.');
                $discord->close();
            });

            $promise->otherwise(function () use ($discord) {
                $this->logger->info('Message could not be sent, closing Discord.');
                $discord->close();
            });

            $promise->always(function () use ($discord) {
                try {
                    $discord->close();
                } catch (Throwable $throwable) {
                    $this->logger->warning("Got throwable {$throwable->getMessage()}.");
                }
                exit(0);
            });
        });

        $discord->run();

        $this->logger->info("Finished 'visualize' message with prompt '{$symfonyMessage->getPrompt()}'.");
    }
}
