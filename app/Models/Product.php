<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $discounted_price Sadece bazı controller'larda enrichment sırasında eklenir.
 * @property array<string, mixed>|null $active_campaign Sadece bazı controller'larda enrichment sırasında eklenir.
 */
#[Fillable(['category_id', 'name', 'sku', 'description', 'price', 'stock_quantity', 'is_published'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Bu ürüne veya kategorisine tanımlı, şu anda zaman aralığı içinde olan kampanyayı döner.
     * Ürüne özel bir kampanya varsa kategori kampanyasından önceliklidir.
     */
    public function activeCampaign(): ?Campaign
    {
        return Campaign::query()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->where(function ($query) {
                $query->where('product_id', $this->id)
                    ->orWhere('category_id', $this->category_id);
            })
            ->orderByRaw('product_id is null')
            ->first();
    }

    /**
     * activeCampaign varsa indirimli, yoksa normal fiyatı döner.
     */
    public function discountedPrice(): string
    {
        $campaign = $this->activeCampaign();

        if (! $campaign) {
            return (string) $this->price;
        }

        $price = (float) $this->price;

        $discounted = $campaign->discount_type === 'percentage'
            ? $price * (1 - (float) $campaign->discount_value / 100)
            : $price - (float) $campaign->discount_value;

        return number_format(max($discounted, 0), 2, '.', '');
    }
}
