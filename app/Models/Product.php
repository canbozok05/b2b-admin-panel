<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
#[Fillable(['category_id' , 'name' , 'sku' , 'description' , 'price' , 'stock_quantity' , 'is_published'])]
class Product extends Model
{
    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function orderItems(){
        return $this->hasMany(OrderItem::class);
    }
}
