<?php

namespace App\ApiResource;

use App\Service\UploadVideoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UploadVideoAction extends AbstractController
{
    public function __construct(
        private UploadVideoService $uploadVideoService,
    ) {
    }

    #[Route('/api/video/upload', name: 'api_video_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $video = $request->files->get('video');

        if (!$video instanceof UploadedFile) {
            return new JsonResponse([
                'message' => 'Aucun fichier video n\'a été envoyé',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->uploadVideoService->upload($video);
    }
}
