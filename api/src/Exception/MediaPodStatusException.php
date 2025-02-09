<?php

namespace App\Exception;

use Exception;

class MediaPodStatusException extends Exception
{
    public function __construct(int $statusCode = 400, string $message = "This media pod does have the good status.")
    {
        parent::__construct($message, $statusCode);
    }
}