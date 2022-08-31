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
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Throwable;

#[AsCommand(name: 'app:bot:run')]
class BotRun extends Command
{
    private string $discordBotToken;

    private MessageBusInterface $messageBus;

    private EntityManagerInterface $entityManager;

    private SerializerInterface $serializer;

    public function __construct(
        string $discordBotToken,
        MessageBusInterface $messageBus,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    )
    {
        $this->discordBotToken = $discordBotToken;
        $this->messageBus = $messageBus;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
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

        $discord->listenCommand('draw-status', function (Interaction $interaction) use ($discord, $output) {
            try {

                $sql = "
                    SELECT body, headers, created_at
                    FROM messenger_messages
                    WHERE queue_name = 'default'
                    AND delivered_at IS NULL
                    ORDER BY created_at ASC;
                ";

                $stmt = $this->entityManager->getConnection()->prepare($sql);
                $resultSet = $stmt->executeQuery();

                $rows = $resultSet->fetchAllAssociative();

                $numberOfTasks = sizeof($rows);
                $positions = [];
                $i = 1;
                foreach ($rows as $row) {
                    $envelope = $this->serializer->decode(['body' => $row['body'], 'headers' => $row['headers']]);
                    /** @var VisualizeSymfonyMessage $message */
                    $message = $envelope->getMessage();
                    if ($message->getDiscordUserId() === $interaction->user->id) {
                        $positions[] = "At position `" . str_pad($i, strlen((string)$numberOfTasks), ' ', STR_PAD_LEFT) . "`, from `{$row['created_at']}` UTC";
                    }
                    $i++;
                }

                if (sizeof($positions) === 0) {
                    $interaction->respondWithMessage((new MessageBuilder())->addEmbed(
                        new Embed($discord, [
                            'title' => 'Your draw status',
                            'description' => "There are currently **$numberOfTasks** tasks in the queue.\n\nYou do not have any tasks in the queue.",
                            'type' => Embed::TYPE_RICH,
                            'color' => '0x5b001e'
                        ])
                    ));
                } else {
                    $positionsText = implode("\n", $positions);
                    $interaction->respondWithMessage((new MessageBuilder())->addEmbed(
                        new Embed($discord, [
                            'title' => 'Your draw status',
                            'description' => "There are currently $numberOfTasks tasks in the queue.\n\nYou have the following tasks in the queue:\n\n$positionsText\n",
                            'footer' => new Footer(
                                $discord,
                                ['text' => 'Position 1 means that the task is next in line.']
                            ),
                            'type' => Embed::TYPE_RICH,
                            'color' => '0x5b001e'
                        ])
                    ));
                }
            } catch (Throwable $throwable) {
                $output->writeln("Got throwable with message '{$throwable->getMessage()}'.");
            }

        });

        $discord->listenCommand('draw', function (Interaction $interaction) use ($discord) {

            $sql = "
                    SELECT body, headers, created_at
                    FROM messenger_messages
                    WHERE queue_name = 'default'
                    AND delivered_at IS NULL
                    ORDER BY created_at ASC;
                ";

            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $resultSet = $stmt->executeQuery();

            $rows = $resultSet->fetchAllAssociative();

            $numberOfTasksForThisUserId = 0;
            foreach ($rows as $row) {
                $envelope = $this->serializer->decode(['body' => $row['body'], 'headers' => $row['headers']]);
                /** @var VisualizeSymfonyMessage $message */
                $message = $envelope->getMessage();
                if ($message->getDiscordUserId() === $interaction->user->id) {
                    $numberOfTasksForThisUserId++;
                }
            }

            if ($numberOfTasksForThisUserId >= 3) {
                $interaction->respondWithMessage(
                    MessageBuilder::new()
                        ->addEmbed(
                            new Embed($discord, [
                                'title' => 'Concurrent draw task limit reached',
                                'description' => "You currently have 3 or more tasks in the queue.\nYou can enqueue another task once you have less than 3 tasks in the queue.\n\nUse `/draw-status` to see the list of your currently enqueued tasks.",
                                'type' => Embed::TYPE_RICH,
                                'color' => '0x5b001e'
                            ])
                        )
                );
                return;
            }

            $prompt = mb_strtolower($interaction->data->options['prompt']['value']);
            $prompt = preg_replace(
                "/[^A-Za-z0-9,.\-:!šžáâãäåæçèéêëìíîïñòóôõöøùúûüýþßàðÿ ]/",
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
                WHERE
                        delivered_at IS NULL
                    AND queue_name = 'default';
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
