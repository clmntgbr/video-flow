<?php

namespace App\Exception;

class MediaPodNotFoundException extends \Exception
{
    public function __construct(int $statusCode = 400, string $message = 'This media pod does not exist.')
    {
        parent::__construct($message, $statusCode);
    }
}
