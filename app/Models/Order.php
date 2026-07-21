<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['customer_id' , 'order_number' , 'total_amount' ,'status'])]
class Order extends Model
{
    public function customer(){
        return $this->belongsTo(Customer::class);
    }
    public function orderItems(){
        return $this->hasMany(OrderItem::class);
    }
}
