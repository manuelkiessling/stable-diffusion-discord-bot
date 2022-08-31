<?php

namespace App\Command;

use Discord\Discord;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Parts\Interactions\Command\Option;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:bot:register')]
class BotRegister extends Command
{
    private string $discordBotToken;

    public function __construct(string $discordBotToken)
    {
        $this->discordBotToken = $discordBotToken;
        parent::__construct();
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        $discord = new Discord([
            'token' => $this->discordBotToken,
        ]);

        $discord->on('ready', function (Discord $discord) {
            $command = new DiscordCommand(
                $discord,
                [
                    'name' => 'draw',
                    'description' => 'Will generate an image for the given text prompt using the Stable Diffusion Text2Image AI.',
                    'type' => DiscordCommand::CHAT_INPUT,
                    'options' => [
                        [
                            'name' => 'prompt',
                            'description' => 'The text prompt from which to generate the images. Try "An astronaut riding a horse".',
                            'type' => Option::STRING,
                            'required' => true,
                            'min_length' => 10,
                            'max_length' => 500
                        ],
                        [
                            'name' => 'seed',
                            'description' => 'The seed to use when generating the images. Defaults to 42.',
                            'type' => Option::INTEGER,
                            'required' => false,
                            'min_value' => 0,
                            'max_value' => 999999999
                        ],
                        [
                            'name' => 'format',
                            'description' => 'Format of the images.',
                            'type' => Option::STRING,
                            'required' => false,
                            'choices' => [
                                Choice::new($discord, 'Landscape', 'landscape'),
                                Choice::new($discord, 'Square', 'square'),
                                Choice::new($discord, 'Portrait', 'portrait')
                            ]
                        ],

                    ],
                ]
            );
            $promise = $discord->application->commands->save($command);

            $promise->done(function () use ($discord) {

                $command = new DiscordCommand(
                    $discord,
                    [
                        'name' => 'draw-status',
                        'description' => 'Show the status of this bot, including the task queue list.',
                        'type' => DiscordCommand::CHAT_INPUT
                    ]
                );
                $promise = $discord->application->commands->save($command);

                $promise->done(function () use ($discord) {
                    $discord->close();
                });
            });

        });

        $discord->run();

        return 0;
    }
}
