<?php

namespace App\Command;

use App\SymfonyMessage\VisualizeSymfonyMessage;
use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Message;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
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
        $discord = new DiscordCommandClient([
            'token' => 'MTAxMzM3MzA0MzYyNTcwNTUxMw.Gy5jK6.ApVUkSGi9Y51z3cne5BV-sgLOoXuSFpb388FY0',
        ]);

        $discord->registerCommand('visualize', function (Message $message, array $params) {
            $prompt = implode(' ', $params);
            $prompt = preg_replace("/[^A-Za-z0-9,.-: ]/", '', $prompt);

            $this->messageBus->dispatch(new VisualizeSymfonyMessage($prompt, $message->id, $message->channel_id));

            $sql = "
                SELECT COUNT(*) AS cnt
                FROM messenger_messages;
            ";

            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $resultSet = $stmt->executeQuery();

            $rows = $resultSet->fetchAllAssociative();

            return "I have enqueued visualization of prompt '$prompt'. There are currently {$rows[0]['cnt']} visualization tasks in the queue.";
        }, [
            'description' => 'Visualize the given prompt using Stable Diffusion.',
        ]);

        $discord->run();

        return 0;
    }
}
