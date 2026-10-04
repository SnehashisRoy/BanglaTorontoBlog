<?php

namespace App\Models;

use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

/**
 * @use HasFactory<VendorFactory>
 */
class Vendor extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'user_id', 'name', 'slug', 'description', 'logo_path',
        'phone', 'email', 'whatsapp', 'address', 'city', 'website', 'status',
        'cj_dropshipping_enabled', 'cj_markup_percent',
    ];

    protected function casts(): array
    {
        return [
            'cj_dropshipping_enabled' => 'boolean',
            'cj_markup_percent' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @param  Builder<Vendor>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_APPROVED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /**
     * @return HasOne<VendorCjCredential, $this>
     */
    public function cjCredential(): HasOne
    {
        return $this->hasOne(VendorCjCredential::class);
    }

    public function hasConnectedCj(): bool
    {
        return $this->cj_dropshipping_enabled && $this->cjCredential !== null;
    }
}
