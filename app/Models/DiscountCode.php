<?php

namespace App\Models;

use Database\Factories\DiscountCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'discount_type', 'discount_value', 'min_order_amount', 'product_id', 'category_id'])]
class DiscountCode extends Model
{
    /** @use HasFactory<DiscountCodeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Sipariş kalemlerinden en az biri bu kodun ürün veya kategori kapsamına giriyor mu?
     *
     * @param  list<Product>  $products
     */
    public function appliesToProducts(array $products): bool
    {
        foreach ($products as $product) {
            if ($this->product_id && $product->id === $this->product_id) {
                return true;
            }

            if ($this->category_id && $product->category_id === $this->category_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verilen sipariş tutarından bu kodla düşülecek indirim tutarını hesaplar
     * (tutarı asla negatife düşürmez).
     */
    public function discountAmountFor(float $orderTotal): float
    {
        $rawDiscount = $this->discount_type === 'percentage'
            ? $orderTotal * (float) $this->discount_value / 100
            : (float) $this->discount_value;

        return round(min(max($rawDiscount, 0), $orderTotal), 2);
    }
}
