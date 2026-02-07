<?php

namespace Doedoe123boop\Sharepoint\Exceptions;

class SharepointAuthException extends SharepointException
{
    public static function tokenAcquisitionFailed(string $reason = ''): static
    {
        return new static("Failed to acquire access token from Microsoft Identity Platform. {$reason}");
    }

    public static function tokenExpiredAfterRetry(): static
    {
        return new static('Access token is invalid or expired, and retry was unsuccessful.');
    }
}
