<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Queued',
            self::Processing => 'Processing',
            self::Processed => 'Ready',
            self::Failed => 'Failed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Processed, self::Failed], true);
    }
}
