<?php

namespace App\Controller;

use App\Entity\MediaPod;
use App\Entity\User;
use App\Entity\Video;
use App\Protobuf\MediaPod as ProtoMediaPod;
use App\Protobuf\SubtitleGeneratorApi;
use App\Protobuf\Video as ProtoVideo;
use App\Repository\MediaPodRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/debug', name: 'api_debug')]
class DebugController extends AbstractController
{
    #[Route('/subtitle_generator_api', name: 'subtitle_generator_api', methods: ['GET'])]
    public function subtitleGeneratorApi(#[CurrentUser] ?User $user, EntityManagerInterface $entityManager, MediaPodRepository $mediaPodRepository, FilesystemOperator $awsStorage, MessageBusInterface $messageBus): JsonResponse
    {
        $mediaPodData = [
            '@id' => '/api/media_pods/35d74015-4b0e-46e9-a64c-044a75f27f15',
            '@type' => 'MediaPod',
            'videoName' => null,
            'originalVideo' => [
                '@id' => '/api/videos/7fb5d19e-002a-49d6-ba06-5f9f879137f7',
                '@type' => 'Video',
                'originalName' => 'video5.mp4',
                'name' => '16f6656a5d544e96b732151090caf12d.mp4',
                'mimeType' => 'video/mp4',
                'size' => 71541180,
                'subtitles' => [],
                'audios' => [
                    '16f6656a5d544e96b732151090caf12d_1.wav',
                    '16f6656a5d544e96b732151090caf12d_2.wav',
                    '16f6656a5d544e96b732151090caf12d_3.wav',
                    '16f6656a5d544e96b732151090caf12d_4.wav',
                    '16f6656a5d544e96b732151090caf12d_5.wav',
                ],
                'createdAt' => '2025-02-08T21:08:34+00:00',
                'updatedAt' => '2025-02-08T21:08:54+00:00',
                'uuid' => '7fb5d19e-002a-49d6-ba06-5f9f879137f7',
            ],
            'status' => 'subtitle_generator_pending',
            'statuses' => [
                'upload_complete',
                'sound_extractor_pending',
                'sound_extractor_complete',
                'subtitle_generator_pending',
            ],
            'createdAt' => '2025-02-08T21:08:34+00:00',
            'updatedAt' => '2025-02-08T21:08:54+00:00',
            'uuid' => '35d74015-4b0e-46e9-a64c-044a75f27f15',
        ];

        $mediaPod = $mediaPodRepository->findOneBy(['uuid' => '35d74015-4b0e-46e9-a64c-044a75f27f15']);

        if (!$mediaPod instanceof MediaPod) {
            $video = new Video();
            $video->setOriginalName($mediaPodData['originalVideo']['originalName']);
            $video->setUuid($mediaPodData['originalVideo']['uuid']);
            $video->setName($mediaPodData['originalVideo']['name']);
            $video->setMimeType($mediaPodData['originalVideo']['mimeType']);
            $video->setSize($mediaPodData['originalVideo']['size']);
            $video->setSubtitles($mediaPodData['originalVideo']['subtitles']);
            $video->setAudios($mediaPodData['originalVideo']['audios']);
            $video->setCreatedAt(new \DateTime($mediaPodData['originalVideo']['createdAt']));
            $video->setUpdatedAt(new \DateTime($mediaPodData['originalVideo']['updatedAt']));

            $mediaPod = new MediaPod();
            $mediaPod->setUser($user);
            $mediaPod->setUuid($mediaPodData['uuid']);
            $mediaPod->setVideoName($mediaPodData['videoName']);
            $mediaPod->setOriginalVideo($video);
            $mediaPod->setStatus($mediaPodData['status']);
            $mediaPod->setStatuses($mediaPodData['statuses']);
            $mediaPod->setCreatedAt(new \DateTime($mediaPodData['createdAt']));
            $mediaPod->setUpdatedAt(new \DateTime($mediaPodData['updatedAt']));

            $entityManager->persist($mediaPod);
            $entityManager->flush();
        }

        $path = sprintf('%s/%s/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d.mp4');
        $stream = fopen('/app/public/debug/16f6656a5d544e96b732151090caf12d.mp4', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        // Audios

        $path = sprintf('%s/%s/audios/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d_1.wav');
        $stream = fopen('/app/public/debug/audios/16f6656a5d544e96b732151090caf12d_1.wav', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d_2.wav');
        $stream = fopen('/app/public/debug/audios/16f6656a5d544e96b732151090caf12d_2.wav', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d_3.wav');
        $stream = fopen('/app/public/debug/audios/16f6656a5d544e96b732151090caf12d_3.wav', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d_4.wav');
        $stream = fopen('/app/public/debug/audios/16f6656a5d544e96b732151090caf12d_4.wav', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d_5.wav');
        $stream = fopen('/app/public/debug/audios/16f6656a5d544e96b732151090caf12d_5.wav', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        // Subtitles

        $path = sprintf('%s/%s/subtitles/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d_1.srt');
        $stream = fopen('/app/public/debug/subtitles/16f6656a5d544e96b732151090caf12d_1.srt', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d_2.srt');
        $stream = fopen('/app/public/debug/subtitles/16f6656a5d544e96b732151090caf12d_2.srt', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d_3.srt');
        $stream = fopen('/app/public/debug/subtitles/16f6656a5d544e96b732151090caf12d_3.srt', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d_4.srt');
        $stream = fopen('/app/public/debug/subtitles/16f6656a5d544e96b732151090caf12d_4.srt', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getUuid(), $mediaPod->getUuid(), '16f6656a5d544e96b732151090caf12d_5.srt');
        $stream = fopen('/app/public/debug/subtitles/16f6656a5d544e96b732151090caf12d_5.srt', 'r');
        $awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $protoVideo = new ProtoVideo();
        $protoVideo->setName($mediaPodData['originalVideo']['name']);
        $protoVideo->setMimeType($mediaPodData['originalVideo']['mimeType']);
        $protoVideo->setSize($mediaPodData['originalVideo']['size']);
        $protoVideo->setAudios($mediaPodData['originalVideo']['audios']);
        $protoVideo->setSubtitles([
            '16f6656a5d544e96b732151090caf12d_1.srt',
            '16f6656a5d544e96b732151090caf12d_2.srt',
            '16f6656a5d544e96b732151090caf12d_3.srt',
            '16f6656a5d544e96b732151090caf12d_4.srt',
            '16f6656a5d544e96b732151090caf12d_5.srt',
        ]);

        $protoMediaPod = new ProtoMediaPod();
        $protoMediaPod->setUuid($mediaPodData['uuid']);
        $protoMediaPod->setUserUuid($user->getUuid());
        $protoMediaPod->setOriginalVideo($protoVideo);
        $protoMediaPod->setStatus('subtitle_generator_complete');

        $subtitleGeneratorApi = new SubtitleGeneratorApi();
        $subtitleGeneratorApi->setMediaPod($protoMediaPod);

        $messageBus->dispatch($subtitleGeneratorApi, [
            new AmqpStamp('subtitle_generator_api', 0, []),
        ]);

        return new JsonResponse(data: [], status: Response::HTTP_CREATED);
    }
}