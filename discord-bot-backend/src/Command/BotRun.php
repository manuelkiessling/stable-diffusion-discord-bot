<?php

namespace App\Command;

use App\SymfonyMessage\VisualizeSymfonyMessage;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Footer;
use Discord\Parts\Interactions\Interaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:bot:run')]
class BotRun extends Command
{
    private string $discordBotToken;

    private MessageBusInterface $messageBus;

    private EntityManagerInterface $entityManager;

    public function __construct(
        string $discordBotToken,
        MessageBusInterface $messageBus,
        EntityManagerInterface $entityManager
    )
    {
        $this->discordBotToken = $discordBotToken;
        $this->messageBus = $messageBus;
        $this->entityManager = $entityManager;
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

        $discord->listenCommand('draw', function (Interaction $interaction) use ($discord) {
            $prompt = mb_strtolower($interaction->data->options['prompt']['value']);
            $prompt = preg_replace(
                "/[^A-Za-z0-9,.\-:!šžáâãäåæçèéêëìíîïñòóôõöøùúûüýþßàðÿ' ]/",
                ' ',
                $prompt
            );
            $prompt = trim($prompt);

            if (   is_null($interaction->data->options['seed'])
                || is_null($interaction->data->options['seed']['value'])
            ) {
                $seed = 42;
            } else {
                $seed = (int)$interaction->data->options['seed']['value'];
            }

            if (   is_null($interaction->data->options['format'])
                || is_null($interaction->data->options['format']['value'])
            ) {
                $format = 'square';
            } else {
                $format = $interaction->data->options['format']['value'];
            }

            $this->messageBus->dispatch(new VisualizeSymfonyMessage(
                $prompt,
                $seed,
                $format,
                $interaction->id,
                $interaction->channel_id,
                $interaction->user->id,
                $interaction->user->username
            ));

            $sql = "
                SELECT COUNT(*) AS cnt
                FROM messenger_messages
                WHERE delivered_at IS NULL;
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
                            'description' => "I have enqueued visualization of prompt\n`$prompt` in format `$format` with seed `$seed`.",
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
