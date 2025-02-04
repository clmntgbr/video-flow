<?php

namespace App\MessageHandler;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Protobuf\SubtitleGeneratorApi;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SubtitleGeneratorApiMessageHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(SubtitleGeneratorApi $subtitleGeneratorApi): void
    {
        $this->logger->info(sprintf('Received SubtitleGeneratorApi message with mediaPod uuid : %s', $subtitleGeneratorApi->getMediaPod()->getUuid()));
    }
}
