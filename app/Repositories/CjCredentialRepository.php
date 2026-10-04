<?php

namespace App\Repositories;

use App\Dto\CjTokenPair;
use App\Models\Vendor;
use App\Models\VendorCjCredential;

class CjCredentialRepository
{
    public function store(Vendor $vendor, CjTokenPair $pair): VendorCjCredential
    {
        return VendorCjCredential::updateOrCreate(
            ['vendor_id' => $vendor->id],
            [
                'access_token' => $pair->accessToken,
                'refresh_token' => $pair->refreshToken,
                'access_token_expires_at' => $pair->accessTokenExpiresAt,
                'refresh_token_expires_at' => $pair->refreshTokenExpiresAt,
                'connected_at' => now(),
            ],
        );
    }

    public function forget(Vendor $vendor): void
    {
        VendorCjCredential::where('vendor_id', $vendor->id)->delete();
    }
}
