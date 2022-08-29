<?php

namespace App\Command;

use App\SymfonyMessage\VisualizeSymfonyMessage;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Footer;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:runbot')]
class Runbot extends Command
{
    private MessageBusInterface $messageBus;

    private EntityManagerInterface $entityManager;

    public function __construct(MessageBusInterface $messageBus, EntityManagerInterface $entityManager)
    {
        $this->messageBus = $messageBus;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $discord = new Discord([
            'token' => 'MTAxMzM3MzA0MzYyNTcwNTUxMw.Gy5jK6.ApVUkSGi9Y51z3cne5BV-sgLOoXuSFpb388FY0',
        ]);

        /*
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
                            'description' => 'The text prompt from to generate the image. Try "An astronaut riding a horse".',
                            'type' => Option::STRING,
                            'required' => true,
                            'min_length' => 10,
                            'max_length' => 500
                        ],
                        [
                            'name' => 'seed',
                            'description' => 'The seed to use when generating the image. Defaults to 42.',
                            'type' => Option::INTEGER,
                            'required' => false,
                            'min_value' => 0,
                            'max_value' => 999999999
                        ],

                    ],
                ]
            );
            $promise = $discord->application->commands->save($command);

            $promise->done(function () {
                echo "saved";
            });

        });

        $discord->run();
        */

        $discord->listenCommand('draw', function (Interaction $interaction) use ($discord) {
            $prompt = preg_replace(
                "/[^A-Za-z0-9,.\-:!' ]/",
                ' ',
                $interaction->data->options['prompt']['value']
            );
            $prompt = trim($prompt);

            if (   is_null($interaction->data->options['seed'])
                || is_null($interaction->data->options['seed']['value'])
            ) {
                $seed = 42;
            } else {
                $seed = (int)$interaction->data->options['seed']['value'];
            }

            $this->messageBus->dispatch(new VisualizeSymfonyMessage(
                $prompt,
                $seed,
                $interaction->id,
                $interaction->channel_id,
                $interaction->user->id,
                $interaction->user->username
            ));

            $sql = "
                SELECT COUNT(*) AS cnt
                FROM messenger_messages;
            ";

            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $resultSet = $stmt->executeQuery();

            $rows = $resultSet->fetchAllAssociative();
            $numberOfTasks = $rows[0]['cnt'];
            $waitMin = $numberOfTasks;
            $waitMax = $numberOfTasks * 2;

            if ($numberOfTasks === 1) {
                $text = "Your task is the first one in the queue.\nExpect between $waitMin and $waitMax minutes for your visualization to be finished.";
            } else {
                $text = "Your task is at position {$numberOfTasks} in the queue.\nExpect between $waitMin and $waitMax minutes for your visualization to be finished.";
            }

            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->addEmbed(
                        new Embed($discord, [
                            'title' => 'Draw task enqueued',
                            'description' => "I have enqueued visualization of prompt\n`$prompt`.",
                            'footer' => new Footer(
                                $discord,
                                ['text' => $text]
                            ),
                            'type' => Embed::TYPE_RICH,
                            'color' => '0x5b001e'
                        ])
                    )
            );
        });

        $discord->run();

        return 0;
    }
}
