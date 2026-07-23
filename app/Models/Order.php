<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['customer_id' , 'order_number' , 'total_amount' ,'status'])]
class Order extends Model
{

    use HasFactory;

    public function customer(){
        return $this->belongsTo(Customer::class);
    }
    public function orderItems(){
        return $this->hasMany(OrderItem::class);
    }
}
