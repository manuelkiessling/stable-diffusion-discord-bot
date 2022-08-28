<?php

namespace App\Command;

use App\SymfonyMessage\VisualizeSymfonyMessage;
use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Message;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:runbot')]
class Runbot extends Command
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $discord = new DiscordCommandClient([
            'token' => 'MTAxMzM3MzA0MzYyNTcwNTUxMw.Gy5jK6.ApVUkSGi9Y51z3cne5BV-sgLOoXuSFpb388FY0',
        ]);

        $discord->registerCommand('visualize', function (Message $message, array $params) {
            $prompt = implode(' ', $params);

            $this->messageBus->dispatch(new VisualizeSymfonyMessage($prompt, $message->id, $message->channel_id));

            return "I have enqueued visualization of prompt '$prompt'. Please wait...";
        }, [
            'description' => 'Visualize the given prompt using Stable Diffusion.',
        ]);

        $discord->run();

        return 0;
    }
}
