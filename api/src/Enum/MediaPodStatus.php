<?php

namespace App\Enum;

enum MediaPodStatus: string
{
    case UPLOAD_COMPLETE = 'upload_complete';

    case SOUND_EXTRACTOR_PENDING = 'sound_extractor_pending';
    case SOUND_EXTRACTOR_COMPLETE = 'sound_extractor_complete';

    case SUBTITLE_GENERATOR_PENDING = 'subtitle_generator_pending';
    case SUBTITLE_GENERATOR_COMPLETE = 'subtitle_generator_complete';

    case SUBTITLE_MERGER_PENDING = 'subtitle_merger_pending';
    case SUBTITLE_MERGER_COMPLETE = 'subtitle_merger_complete';

    case RESIZING = 'resizing';
    case RESIZED = 'resized';

    case READY_FOR_EXPORT = 'ready_for_export';
    case ERROR = 'error';

    public function getId(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
