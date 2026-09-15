<?php

namespace App\Enums;

enum EmbeddingDriver: string
{
    case Local = 'local';
    case OpenAiCompatible = 'openai';
    case DeepSeek = 'deepseek';

    public function usesRemoteApi(): bool
    {
        return $this !== self::Local;
    }
}
