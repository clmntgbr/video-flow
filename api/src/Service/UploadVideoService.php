<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Video;
use App\Entity\VideoHub;
use App\Repository\MediaPodRepository;
use App\Repository\VideoHubRepository;
use App\Repository\VideoRepository;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UploadVideoService
{
    public function __construct(
        private ValidatorInterface $validator,
        private FilesystemOperator $awsStorage,
        private Security $security,
        private MediaPodRepository $mediaPodRepository,
        private VideoRepository $videoRepository,
    ) {
    }

    public function upload(UploadedFile $file): JsonResponse
    {
        $constraints = new File([
            'mimeTypes' => [
                'video/mp4',
            ],
            'mimeTypesMessage' => 'Please upload a valid video file (MP4).',
        ]);

        $violations = $this->validator->validate($file, $constraints);

        if (count($violations) > 0) {
            return new JsonResponse([
                'message' => $violations[0]->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return new JsonResponse([
                'message' => 'You must be logged in to upload a video.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $fileName = sprintf('%s.%s', md5(uniqid()), $file->guessExtension());
            $path = sprintf('%s/%s', $user->getUuid(), $fileName);
            
            $stream = fopen($file->getPathname(), 'r');

            if (false === $stream) {
                return new JsonResponse([
                    'message' => 'An error occurred during the upload.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->awsStorage->writeStream($path, $stream, [
                'visibility' => 'public',
                'mimetype' => $file->getMimeType(),
            ]);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $this->createMediaPod($file, $fileName);
            return new JsonResponse([
                'message' => 'Video uploaded successfully.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'An error occurred during the upload: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function createMediaPod(UploadedFile $uploadedFile, string $fileName): void
    {
        $video = $this->videoRepository->create([
            'mimeType' => $uploadedFile->getMimeType(),
            'originalName' => $uploadedFile->getClientOriginalName(),
            'name' => $fileName,
            'size' => $uploadedFile->getSize(),
        ]);

        $this->mediaPodRepository->create([
            'user' => $this->security->getUser(),
            'originalVideo' => $video,
        ]);
    }
}
