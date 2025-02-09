<?php

namespace App\MessageHandler;

use App\Entity\MediaPod;
use App\Enum\MediaPodStatus;
use App\Exception\MediaPodNotFoundException;
use App\Exception\MediaPodStatusException;
use App\Protobuf\ApiSubtitleGenerator;
use App\Protobuf\SoundExtractorApi;
use App\Protobuf\SubtitleGeneratorApi;
use App\Repository\MediaPodRepository;
use Exception;
use Monolog\Logger;
use NotFoundMediaPodException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class SoundExtractorApiMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private MediaPodRepository $mediaPodRepository,
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(SoundExtractorApi $soundExtractorApi): void
    {
        $this->logger->info(sprintf('Received SoundExtractorApi message with mediaPod uuid : %s', $soundExtractorApi->getMediaPod()->getUuid()));

        $mediaPod = $this->mediaPodRepository->findOneBy([
            'uuid' => $soundExtractorApi->getMediaPod()->getUuid(),
        ]);

        if (!$mediaPod instanceof MediaPod) {
            throw new MediaPodNotFoundException();
        }

        if ($soundExtractorApi->getMediaPod()->getStatus() !== MediaPodStatus::SOUND_EXTRACTOR_COMPLETE->getValue()) {
            throw new MediaPodStatusException();
        }

        $mediaPod->getOriginalVideo()->setAudios([]);
        foreach ($soundExtractorApi->getMediaPod()->getOriginalVideo()->getAudios()->getIterator() as $iterator) {
            $mediaPod->getOriginalVideo()->addAudios($iterator);
        }

        $mediaPod = $this->mediaPodRepository->update($mediaPod, [
            'statuses' => [MediaPodStatus::SOUND_EXTRACTOR_COMPLETE->getValue(), MediaPodStatus::SUBTITLE_GENERATOR_PENDING->getValue(),],
            'status' => MediaPodStatus::SUBTITLE_GENERATOR_PENDING->getValue(),
        ]);
        
        $apiSubtitleGenerator = $this->createApiSubtitleGeneratorProto($soundExtractorApi);

        $this->messageBus->dispatch($apiSubtitleGenerator, [
            new AmqpStamp('api_subtitle_generator', 0, []),
        ]);
    }

    private function createApiSubtitleGeneratorProto(SoundExtractorApi $soundExtractorApi): ApiSubtitleGenerator
    {
        $apiSubtitleGenerator = new ApiSubtitleGenerator();

        $mediaPod = $soundExtractorApi->getMediaPod();
        $mediaPod->setStatus(MediaPodStatus::SUBTITLE_GENERATOR_PENDING->getValue());

        $apiSubtitleGenerator->setMediaPod($mediaPod);

        return $apiSubtitleGenerator;
    }
}