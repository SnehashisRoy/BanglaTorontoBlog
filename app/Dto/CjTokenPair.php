<?php

namespace App\Dto;

use Carbon\CarbonImmutable;

/**
 * A CJ Dropshipping access/refresh token pair, as returned by
 * authentication/getAccessToken and authentication/refreshAccessToken.
 */
final class CjTokenPair
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly CarbonImmutable $accessTokenExpiresAt,
        public readonly CarbonImmutable $refreshTokenExpiresAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data  the "data" object from CJ's response envelope
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: $data['accessToken'],
            refreshToken: $data['refreshToken'],
            accessTokenExpiresAt: CarbonImmutable::parse($data['accessTokenExpiryDate']),
            refreshTokenExpiresAt: CarbonImmutable::parse($data['refreshTokenExpiryDate']),
        );
    }
}
