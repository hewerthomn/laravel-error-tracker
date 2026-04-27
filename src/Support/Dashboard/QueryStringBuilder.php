<?php

namespace Hewerthomn\ErrorTracker\Support\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class QueryStringBuilder
{
    public function __construct(
        protected Request $request,
        protected string $baseUrl,
    ) {}

    public static function fromRequest(Request $request, ?string $baseUrl = null): self
    {
        return new self($request, $baseUrl ?? $request->url());
    }

    /**
     * @param  string|array<int, string>|null  $value
     */
    public function replace(string $key, string|array|null $value): string
    {
        return $this->url([$key => $value]);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @param  array<int, string>  $remove
     */
    public function url(array $changes = [], array $remove = []): string
    {
        $query = $this->request->query();

        foreach ($remove as $key) {
            unset($query[$key]);
        }

        foreach ($changes as $key => $value) {
            if (! $this->hasValue($value)) {
                unset($query[$key]);

                continue;
            }

            $query[$key] = $value;
        }

        if ($changes !== [] || $remove !== []) {
            unset($query['page']);
        }

        return $this->toUrl($query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function toUrl(array $query): string
    {
        if ($query === []) {
            return $this->baseUrl;
        }

        return $this->baseUrl.'?'.Arr::query($query);
    }

    protected function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [] && $value !== 'all';
    }
}
