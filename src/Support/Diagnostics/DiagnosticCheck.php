<?php

namespace Hewerthomn\ErrorTracker\Support\Diagnostics;

class DiagnosticCheck
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $status,
        public readonly string $target,
        public readonly string $description,
        public readonly ?string $fixCommand = null,
        public readonly bool $required = true,
        public readonly ?string $feature = null,
    ) {}

    /**
     * @return array{key: string, label: string, status: string, target: string, description: string, fix_command: string|null, required: bool, feature: string|null, tone: string, ok: bool, detail: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'status' => $this->status,
            'target' => $this->target,
            'description' => $this->description,
            'fix_command' => $this->fixCommand,
            'required' => $this->required,
            'feature' => $this->feature,
            'tone' => $this->tone(),
            'ok' => $this->status === 'ok',
            'detail' => $this->target,
        ];
    }

    public function tone(): string
    {
        return match ($this->status) {
            'ok' => 'success',
            'missing' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'neutral',
        };
    }
}
