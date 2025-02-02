<?php

namespace App\ApiResource;

use App\Service\UploadVideoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UploadVideoAction extends AbstractController
{
    public function __construct(
        private UploadVideoService $uploadVideoService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $video = $request->files->get('video');

        if (!$video instanceof UploadedFile) {
            return new JsonResponse([
                'message' => 'No video file has been sent',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->uploadVideoService->upload($video);
    }
}
