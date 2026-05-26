<?php

namespace Hewerthomn\ErrorTracker\Data;

class ExternalIssueResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $repository,
        public readonly ?string $externalId,
        public readonly int $externalNumber,
        public readonly string $title,
        public readonly string $url,
        public readonly ?string $state,
        public readonly array $metadata = [],
    ) {}
}
