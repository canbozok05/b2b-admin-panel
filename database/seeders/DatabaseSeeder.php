<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Üç seviyeli, gerçekçi bir kategori ağacı: üst kategori -> alt kategori -> ürünlerin bağlı olduğu yaprak kategori.
     *
     * @var array<string, array{vat_rate: int, children: array<string, list<string>>}>
     */
    private array $categoryTree = [
        'Teknoloji' => [
            'vat_rate' => 20,
            'children' => [
                'Bilgisayar & Telefon' => ['Dizüstü Bilgisayar', 'Akıllı Telefon'],
                'Beyaz Eşya' => ['Buzdolabı', 'Çamaşır Makinesi'],
            ],
        ],
        'Mobilya' => [
            'vat_rate' => 20,
            'children' => [
                'Oturma Odası' => ['Koltuk Takımı'],
                'Yatak Odası' => ['Yatak'],
            ],
        ],
        'Ev Tekstili' => [
            'vat_rate' => 10,
            'children' => [
                'Yatak Takımları' => ['Nevresim Takımı'],
            ],
        ],
    ];

    /**
     * Yaprak kategori adına göre gerçekçi ürünler ve fiyat aralıkları (TL).
     *
     * @var array<string, list<array{name: string, sku: string, min: float, max: float}>>
     */
    private array $productCatalog = [
        'Dizüstü Bilgisayar' => [
            ['name' => 'Asus VivoBook 15 Dizüstü Bilgisayar', 'sku' => 'ASU-VB15', 'min' => 18000, 'max' => 32000],
            ['name' => 'Lenovo IdeaPad 3 Dizüstü Bilgisayar', 'sku' => 'LEN-IP3', 'min' => 16000, 'max' => 28000],
            ['name' => 'HP Pavilion 14 Dizüstü Bilgisayar', 'sku' => 'HP-PAV14', 'min' => 20000, 'max' => 35000],
        ],
        'Akıllı Telefon' => [
            ['name' => 'Samsung Galaxy A54 Akıllı Telefon', 'sku' => 'SAM-GA54', 'min' => 12000, 'max' => 22000],
            ['name' => 'Xiaomi Redmi Note 12 Akıllı Telefon', 'sku' => 'XMI-RN12', 'min' => 8000, 'max' => 15000],
            ['name' => 'Apple iPhone 13 Akıllı Telefon', 'sku' => 'APL-IP13', 'min' => 25000, 'max' => 40000],
        ],
        'Buzdolabı' => [
            ['name' => 'Arçelik No-Frost Buzdolabı', 'sku' => 'ARC-NF460', 'min' => 15000, 'max' => 28000],
            ['name' => 'Bosch Kombi Tipi Buzdolabı', 'sku' => 'BSH-KMB520', 'min' => 20000, 'max' => 38000],
        ],
        'Çamaşır Makinesi' => [
            ['name' => 'Bosch 9 kg Çamaşır Makinesi', 'sku' => 'BSH-CM9', 'min' => 14000, 'max' => 24000],
            ['name' => 'Vestel 8 kg Çamaşır Makinesi', 'sku' => 'VST-CM8', 'min' => 10000, 'max' => 18000],
        ],
        'Koltuk Takımı' => [
            ['name' => 'Bellona 3+3 Koltuk Takımı', 'sku' => 'BEL-KT33', 'min' => 25000, 'max' => 45000],
            ['name' => 'İstikbal Köşe Koltuk Takımı', 'sku' => 'IST-KOSE1', 'min' => 30000, 'max' => 55000],
        ],
        'Yatak' => [
            ['name' => 'Yataş Ortopedik Yatak', 'sku' => 'YTS-ORT160', 'min' => 9000, 'max' => 18000],
            ['name' => 'İstikbal Yaylı Yatak', 'sku' => 'IST-YAY160', 'min' => 7000, 'max' => 14000],
        ],
        'Nevresim Takımı' => [
            ['name' => 'Taç Çift Kişilik Nevresim Takımı', 'sku' => 'TAC-NEV160', 'min' => 800, 'max' => 2000],
            ['name' => 'English Home Tek Kişilik Nevresim Takımı', 'sku' => 'ENH-NEV100', 'min' => 500, 'max' => 1200],
        ],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $leafCategories = $this->seedCategories();
        $this->seedProducts($leafCategories);

        $customers = Customer::factory()->count(10)->create();
        $this->seedCustomerAddresses($customers);

        $this->seedOrders($customers);

        $this->call(RoleSeeder::class);
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $leafCategories = [];

        foreach ($this->categoryTree as $topName => $topData) {
            $top = Category::create([
                'name' => $topName,
                'slug' => Str::slug($topName),
                'is_active' => true,
                'parent_id' => null,
                'vat_rate' => $topData['vat_rate'],
            ]);

            foreach ($topData['children'] as $midName => $leafNames) {
                $mid = Category::create([
                    'name' => $midName,
                    'slug' => Str::slug($midName),
                    'is_active' => true,
                    'parent_id' => $top->id,
                    'vat_rate' => $topData['vat_rate'],
                ]);

                foreach ($leafNames as $leafName) {
                    $leafCategories[$leafName] = Category::create([
                        'name' => $leafName,
                        'slug' => Str::slug($leafName),
                        'is_active' => true,
                        'parent_id' => $mid->id,
                        'vat_rate' => $topData['vat_rate'],
                    ]);
                }
            }
        }

        return $leafCategories;
    }

    /**
     * @param  array<string, Category>  $leafCategories
     */
    private function seedProducts(array $leafCategories): void
    {
        foreach ($this->productCatalog as $leafName => $products) {
            foreach ($products as $productData) {
                Product::factory()->create([
                    'category_id' => $leafCategories[$leafName]->id,
                    'name' => $productData['name'],
                    'sku' => $productData['sku'],
                    'price' => fake()->randomFloat(2, $productData['min'], $productData['max']),
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Customer>  $customers
     */
    private function seedCustomerAddresses($customers): void
    {
        $labels = ['Ev', 'İş', 'Okul'];

        foreach ($customers as $customer) {
            $addressCount = fake()->numberBetween(1, 2);

            foreach (array_slice($labels, 0, $addressCount) as $label) {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'label' => $label,
                    'address' => fake()->streetAddress().', '.fake()->city(),
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Customer>  $customers
     */
    private function seedOrders($customers): void
    {
        $products = Product::all();

        for ($i = 0; $i < 15; $i++) {
            $customer = $customers->random();
            $address = $customer->addresses()->inRandomOrder()->first();

            $order = Order::factory()->create([
                'customer_id' => $customer->id,
                'customer_address_id' => fake()->boolean(70) ? $address?->id : null,
            ]);

            $itemCount = fake()->numberBetween(1, 3);
            for ($j = 0; $j < $itemCount; $j++) {
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $products->random()->id,
                ]);
            }
        }
    }
}
