<?php

namespace App\MessageHandler;

use App\Entity\MediaPod;
use App\Enum\MediaPodStatus;
use App\Exception\MediaPodNotFoundException;
use App\Exception\MediaPodStatusException;
use App\Protobuf\ApiSubtitleIncrustator;
use App\Protobuf\ApiSubtitleMerger;
use App\Protobuf\SubtitleMergerApi;
use App\Repository\MediaPodRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class SubtitleMergerApiMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private MediaPodRepository $mediaPodRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(SubtitleMergerApi $subtitleMergerApi): void
    {
        $this->logger->info('############################################################################################################################################');
        $this->logger->info(sprintf('Received SubtitleMergerApi message with mediaPod uuid : %s', $subtitleMergerApi->getMediaPod()->getUuid()));

        $mediaPod = $this->mediaPodRepository->findOneBy([
            'uuid' => $subtitleMergerApi->getMediaPod()->getUuid(),
        ]);

        if (!$mediaPod instanceof MediaPod) {
            throw new MediaPodNotFoundException();
        }

        $status = $subtitleMergerApi->getMediaPod()->getStatus();
        
        $mediaPod = $this->mediaPodRepository->update($mediaPod, [
            'statuses' => [$status],
            'status' => $status,
        ]);

        if ($status !== MediaPodStatus::SUBTITLE_MERGER_COMPLETE->getValue()) {
            return;
        }

        $mediaPod->getOriginalVideo()->setSubtitle($subtitleMergerApi->getMediaPod()->getOriginalVideo()->getSubtitle());
        
        $mediaPod = $this->mediaPodRepository->update($mediaPod, [
            'statuses' => [MediaPodStatus::SUBTITLE_INCRUSTATOR_PENDING->getValue()],
            'status' => MediaPodStatus::SUBTITLE_INCRUSTATOR_PENDING->getValue(),
        ]);

        $apiSubtitleIncrustator = $this->createApiSubtitleMergerProto($subtitleMergerApi);

        $this->messageBus->dispatch($apiSubtitleIncrustator, [
            new AmqpStamp('api_subtitle_incrustator', 0, []),
        ]);
    }

    private function createApiSubtitleMergerProto(SubtitleMergerApi $subtitleMergerApi): ApiSubtitleIncrustator
    {
        $apiSubtitleIncrustator = new ApiSubtitleIncrustator();

        $mediaPod = $subtitleMergerApi->getMediaPod();
        $mediaPod->setStatus(MediaPodStatus::SUBTITLE_INCRUSTATOR_PENDING->getValue());

        $apiSubtitleIncrustator->setMediaPod($mediaPod);

        return $apiSubtitleIncrustator;
    }
}