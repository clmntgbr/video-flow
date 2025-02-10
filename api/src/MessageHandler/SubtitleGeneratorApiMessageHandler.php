<?php

namespace App\MessageHandler;

use App\Entity\MediaPod;
use App\Enum\MediaPodStatus;
use App\Exception\MediaPodNotFoundException;
use App\Exception\MediaPodStatusException;
use App\Protobuf\ApiSubtitleMerger;
use App\Protobuf\SubtitleGeneratorApi;
use App\Repository\MediaPodRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class SubtitleGeneratorApiMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private MediaPodRepository $mediaPodRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(SubtitleGeneratorApi $subtitleGeneratorApi): void
    {
        $this->logger->info('############################################################################################################################################');
        $this->logger->info(sprintf('Received SubtitleGeneratorApi message with mediaPod uuid : %s', $subtitleGeneratorApi->getMediaPod()->getUuid()));

        $mediaPod = $this->mediaPodRepository->findOneBy([
            'uuid' => $subtitleGeneratorApi->getMediaPod()->getUuid(),
        ]);

        if (!$mediaPod instanceof MediaPod) {
            throw new MediaPodNotFoundException();
        }

        $status = $subtitleGeneratorApi->getMediaPod()->getStatus();
        
        $mediaPod = $this->mediaPodRepository->update($mediaPod, [
            'statuses' => [$status],
            'status' => $status,
        ]);

        if ($status !== MediaPodStatus::SUBTITLE_GENERATOR_COMPLETE->getValue()) {
            return;
        }

        $mediaPod->getOriginalVideo()->setSubtitles([]);
        $subtitles = [];
        foreach ($subtitleGeneratorApi->getMediaPod()->getOriginalVideo()->getSubtitles()->getIterator() as $iterator) {
            $subtitles[] = $iterator;
        }
        natsort($subtitles);
        $subtitles = array_values($subtitles);
        $mediaPod->getOriginalVideo()->setSubtitles($subtitles);

        $mediaPod = $this->mediaPodRepository->update($mediaPod, [
            'statuses' => [MediaPodStatus::SUBTITLE_MERGER_PENDING->getValue()],
            'status' => MediaPodStatus::SUBTITLE_MERGER_PENDING->getValue(),
        ]);

        $apiSubtitleMerger = $this->createApiSubtitleMergerProto($subtitleGeneratorApi);

        $this->messageBus->dispatch($apiSubtitleMerger, [
            new AmqpStamp('api_subtitle_merger', 0, []),
        ]);
    }

    private function createApiSubtitleMergerProto(SubtitleGeneratorApi $subtitleGeneratorApi): ApiSubtitleMerger
    {
        $apiSubtitleMerger = new ApiSubtitleMerger();

        $mediaPod = $subtitleGeneratorApi->getMediaPod();
        $mediaPod->setStatus(MediaPodStatus::SUBTITLE_MERGER_PENDING->getValue());

        $apiSubtitleMerger->setMediaPod($mediaPod);

        return $apiSubtitleMerger;
    }
}