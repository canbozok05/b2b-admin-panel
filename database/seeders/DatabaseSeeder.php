<?php

namespace Database\Seeders;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;
use App\Models\OrderItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;



class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $parentCategories = Category::factory()->count(5)->create();
        for($i = 0; $i < 10; $i++){
            Category::factory()->create([
                'parent_id' => $parentCategories ->random()->id,
            ]);
        }


        $categories = Category::all();
        for($i=0;$i<50;$i++){
            Product::factory()->create([
                'category_id' => $categories -> random() -> id,
            ]);
        }

        Customer::factory()->count(20)->create();

        $customers = Customer::all();
         for($i=0;$i<30;$i++){
            Order::factory()->create([
                'customer_id'=>$customers->random()->id,
            ]);
         }

         $products= Product::all();
         $orders = Order::all();

         foreach($orders as $order){
            $itemCount = rand(1,4);
            for($i = 0;$i<$itemCount; $i++){
                OrderItem::factory()->create([
                    'order_id'=>$order->id,
                    'product_id'=>$products->random()->id,
                ]);
            }
         }


    }
}
