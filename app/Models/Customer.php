<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name' , 'email' , 'phone' , 'address' , 'status'])]

class Customer extends Model
{
    
    use HasFactory;

    public function orders(){

        return $this->hasMany(Order::class);
    }


}
