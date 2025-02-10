<?php

namespace App\Enum;

enum MediaPodStatus: string
{
    case UPLOAD_COMPLETE = 'upload_complete';

    case SOUND_EXTRACTOR_PENDING = 'sound_extractor_pending';
    case SOUND_EXTRACTOR_COMPLETE = 'sound_extractor_complete';
    case SOUND_EXTRACTOR_ERROR = 'sound_extractor_error';

    case SUBTITLE_GENERATOR_PENDING = 'subtitle_generator_pending';
    case SUBTITLE_GENERATOR_COMPLETE = 'subtitle_generator_complete';
    case SUBTITLE_GENERATOR_ERROR = 'subtitle_generator_error';

    case SUBTITLE_MERGER_PENDING = 'subtitle_merger_pending';
    case SUBTITLE_MERGER_COMPLETE = 'subtitle_merger_complete';
    case SUBTITLE_MERGER_ERROR = 'subtitle_merger_error';

    case SUBTITLE_INCRUSTATOR_PENDING = 'subtitle_incrustator_pending';
    case SUBTITLE_INCRUSTATOR_COMPLETE = 'subtitle_incrustator_complete';
    case SUBTITLE_INCRUSTATOR_ERROR = 'subtitle_incrustator_error';

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

    public static function exists(string $status): bool
    {
        return in_array($status, array_column(self::cases(), 'value'), true);
    }
}