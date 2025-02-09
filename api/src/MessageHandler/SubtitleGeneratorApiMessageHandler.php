<?php

namespace App\MessageHandler;

use App\Entity\MediaPod;
use App\Enum\MediaPodStatus;
use App\Exception\MediaPodNotFoundException;
use App\Exception\MediaPodStatusException;
use App\Protobuf\SubtitleGeneratorApi;
use App\Repository\MediaPodRepository;
use Exception;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class SubtitleGeneratorApiMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private MediaPodRepository $mediaPodRepository,
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(SubtitleGeneratorApi $subtitleGeneratorApi): void
    {
        $this->logger->info(sprintf('Received SubtitleGeneratorApi message with mediaPod uuid : %s', $subtitleGeneratorApi->getMediaPod()->getUuid()));

        $mediaPod = $this->mediaPodRepository->findOneBy([
            'uuid' => $subtitleGeneratorApi->getMediaPod()->getUuid(),
        ]);

        if (!$mediaPod instanceof MediaPod) {
            throw new MediaPodNotFoundException();
        }

        if ($subtitleGeneratorApi->getMediaPod()->getStatus() !== MediaPodStatus::SUBTITLE_GENERATOR_COMPLETE->getValue()) {
            throw new MediaPodStatusException();
        }

        $mediaPod->getOriginalVideo()->setSubtitles([]);
        foreach ($subtitleGeneratorApi->getMediaPod()->getOriginalVideo()->getSubtitles()->getIterator() as $iterator) {
            $mediaPod->getOriginalVideo()->addSubtitles($iterator);
        }

        $mediaPod = $this->mediaPodRepository->update($mediaPod, [
            'statuses' => [MediaPodStatus::SUBTITLE_GENERATOR_COMPLETE->getValue(),],
            'status' => MediaPodStatus::SUBTITLE_GENERATOR_COMPLETE->getValue(),
        ]);
    }
}