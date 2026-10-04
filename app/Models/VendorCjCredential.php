<?php

namespace App\Models;

use Database\Factories\VendorCjCredentialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Stores only the CJ-issued tokens, never the vendor's raw apiKey — that's
 * used once at connect-time to call getAccessToken, then discarded.
 *
 * @property Carbon $access_token_expires_at
 * @property Carbon $refresh_token_expires_at
 * @property Carbon $connected_at
 *
 * @use HasFactory<VendorCjCredentialFactory>
 */
class VendorCjCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'access_token', 'refresh_token',
        'access_token_expires_at', 'refresh_token_expires_at', 'connected_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'access_token_expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function accessTokenIsExpiring(): bool
    {
        return $this->access_token_expires_at->isBefore(now()->addHour());
    }
}
