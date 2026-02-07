<?php

namespace Doedoe123boop\Sharepoint\Exceptions;

class SharepointApiException extends SharepointException
{
    protected int $statusCode;

    protected string $graphErrorCode;

    public function __construct(string $message, int $statusCode = 0, string $graphErrorCode = '', ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->graphErrorCode = $graphErrorCode;

        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(int $statusCode, array $body): static
    {
        $error = $body['error'] ?? [];
        $message = $error['message'] ?? 'Unknown Microsoft Graph API error';
        $code = $error['code'] ?? '';

        return new static(
            "Microsoft Graph API error [{$statusCode}]: {$message}",
            $statusCode,
            $code,
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getGraphErrorCode(): string
    {
        return $this->graphErrorCode;
    }
}
