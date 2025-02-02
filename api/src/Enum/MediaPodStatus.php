<?php

namespace App\Enum;

enum MediaPodStatus: string
{
    case UPLOAD_COMPLETE = 'upload_complete';
    case SUBTITLE_GENERATION = 'subtitle_generation';
    case RESIZING = 'resizing';
    case RESIZED = 'resized';
    case READY_FOR_EXPORT = 'ready_for_export';
    case ERROR = 'error';

    public function toString(): string
    {
        return $this->value;
    }
}
