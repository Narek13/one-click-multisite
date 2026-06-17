<?php

declare(strict_types=1);

namespace OneClickMultisite\Conversion;

class ConversionResult
{
    private bool $success;
    private string $message;

    public function __construct(bool $success, string $message = '')
    {
        $this->success = $success;
        $this->message = $message;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function message(): string
    {
        return $this->message;
    }
}
