<?php

namespace App\Models;

use Database\Factories\CjProductLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ties a local Product back to the CJ Dropshipping catalog item/variant it
 * was imported from, so it can be resynced (price, stock) later.
 *
 * @use HasFactory<CjProductLinkFactory>
 */
class CjProductLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'cj_product_id', 'cj_variant_id',
        'cj_cost_price', 'cj_warehouse_country', 'cj_last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'cj_cost_price' => 'decimal:2',
            'cj_last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * A rough, honest delivery-time estimate for the customer-facing
     * disclosure — deliberately conservative since this is "ships from
     * overseas," not a real carrier quote.
     */
    public function shippingEstimate(): string
    {
        return match ($this->cj_warehouse_country) {
            'US', 'CA', 'GB' => '1–2 weeks',
            default => '2–4 weeks',
        };
    }
}
