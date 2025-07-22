<?php

namespace App\Services;

class ApiResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly mixed $data = null,
        public readonly int $statusCode = 200,
        public readonly ?string $error = null,
        public readonly array $meta = []
    ) {}

    /**
     * Check if the response was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the response failed
     */
    public function failed(): bool
    {
        return !$this->success;
    }

    /**
     * Get the response data
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Get the error message if any
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get meta information
     */
    public function getMeta(string $key = null): mixed
    {
        if ($key === null) {
            return $this->meta;
        }
        
        return $this->meta[$key] ?? null;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'status_code' => $this->statusCode,
            'error' => $this->error,
            'meta' => $this->meta,
        ];
    }

    /**
     * Convert to JSON representation
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Create a successful response
     */
    public static function success(mixed $data = null, int $statusCode = 200, array $meta = []): self
    {
        return new self(true, $data, $statusCode, null, $meta);
    }

    /**
     * Create an error response
     */
    public static function error(string $message, int $statusCode = 500, array $meta = []): self
    {
        return new self(false, null, $statusCode, $message, $meta);
    }

    /**
     * Create response from HTTP response
     */
    public static function fromHttpResponse(bool $success, mixed $data, int $statusCode, ?string $error = null): self
    {
        return new self($success, $data, $statusCode, $error);
    }
}