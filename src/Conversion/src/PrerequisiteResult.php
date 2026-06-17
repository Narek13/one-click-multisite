<?php

declare(strict_types=1);

namespace OneClickMultisite\Conversion;

class PrerequisiteResult
{
    private string $label;
    private bool $passes;
    private string $message;

    public function __construct(string $label, bool $passes, string $message = '')
    {
        $this->label   = $label;
        $this->passes  = $passes;
        $this->message = $message;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function passes(): bool
    {
        return $this->passes;
    }

    public function message(): string
    {
        return $this->message;
    }
}
