<?php

namespace App\Http\Controllers;

use App\Mail\OrderStatusUpdated;
use App\Models\Customer;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Türkçe küçük harfe çevirme kuralları: İ->i, I->ı, Ç->ç, Ğ->ğ, Ö->ö, Ş->ş, Ü->ü.
     *
     * @var array<string, string>
     */
    private const TURKISH_LOWER_MAP = [
        'İ' => 'i',
        'I' => 'ı',
        'Ç' => 'ç',
        'Ğ' => 'ğ',
        'Ö' => 'ö',
        'Ş' => 'ş',
        'Ü' => 'ü',
    ];

    /**
     * Türkçe büyük/küçük harf kurallarına göre küçük harfe çevirir (ör. "İZMİR" -> "izmir").
     */
    private function turkishLower(string $value): string
    {
        return mb_strtolower(strtr($value, self::TURKISH_LOWER_MAP));
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = Order::with('customer');

        if ($search !== '') {
            $needle = '%'.$this->turkishLower($search).'%';

            $query->where(function ($q) use ($search, $needle) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($needle) {
                        $customerQuery->whereRaw(
                            "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'İ', 'i'), 'I', 'ı'), 'Ç', 'ç'), 'Ğ', 'ğ'), 'Ö', 'ö'), 'Ş', 'ş'), 'Ü', 'ü')) LIKE ?",
                            [$needle]
                        );
                    });
            });
        }

        if (in_array($status, ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        $orders = $query->orderByRaw("status = 'pending' desc")
            ->latest()
            ->get();

        return Inertia::render('orders/index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $order = Order::with('customer', 'customerAddress', 'discountCode', 'orderItems.product.category')->findOrFail($id);

        return Inertia::render('orders/show', [
            'order' => $order,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('orders/create', [
            'customers' => Customer::with('addresses')->get(),
            'products' => $this->productsWithCampaigns(),
        ]);
    }

    /**
     * @return Collection<int, Product>
     */
    private function productsWithCampaigns()
    {
        return Product::with('category:id,vat_rate')
            ->get(['id', 'category_id', 'name', 'sku', 'price', 'stock_quantity'])
            ->each(function (Product $product) {
                $campaign = $product->activeCampaign();
                $product->discounted_price = $product->discountedPrice();
                $product->active_campaign = $campaign ? [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'discount_type' => $campaign->discount_type,
                    'discount_value' => $campaign->discount_value,
                    'ends_at' => $campaign->ends_at,
                ] : null;
            });
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000000',
        ]);

        $customer = Customer::findOrFail((int) $validated['customer_id']);
        [$existingAddressId, $newAddress] = $this->resolveOrderAddress($request, $customer);

        $products = [];
        $rawTotal = 0;

        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail((int) $item['product_id']);

            if ($item['quantity'] > $product->stock_quantity) {
                return back()->withErrors([
                    'items' => "\"{$product->name}\" için yeterli stok yok (mevcut: {$product->stock_quantity}).",
                ]);
            }

            $products[] = $product;
            $rawTotal += $product->discountedPrice() * $item['quantity'];
        }

        [$discountCodeId, $discountAmount] = $this->resolveDiscountCode($request, $products, $rawTotal);

        DB::transaction(function () use ($validated, $customer, $existingAddressId, $newAddress, $rawTotal, $discountCodeId, $discountAmount) {
            do {
                $orderNumber = 'ORD-'.random_int(100000, 999999);
            } while (Order::where('order_number', $orderNumber)->exists());

            $customerAddressId = $newAddress
                ? $customer->addresses()->create($newAddress)->id
                : $existingAddressId;

            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'customer_address_id' => $customerAddressId,
                'order_number' => $orderNumber,
                'total_amount' => $rawTotal - $discountAmount,
                'discount_code_id' => $discountCodeId,
                'discount_amount' => $discountAmount,
                'status' => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->discountedPrice(),
                ]);

                $product->decrement('stock_quantity', $item['quantity']);
            }
        });

        return redirect()->route('orders.index');
    }

    /**
     * Verilen indirim kodunu değerlendirir: kod var mı, sipariş kalemlerindeki
     * ürünlerden biri kodun kapsamına giriyor mu, sipariş tutarı asgari
     * tutarı karşılıyor mu. Kod girilmemişse indirim uygulanmaz sayılır.
     *
     * @param  list<Product>  $products
     * @return array{valid: bool, id: int|null, discount_amount: float, message: string|null}
     */
    private function evaluateDiscountCode(string $code, array $products, float $rawTotal): array
    {
        if ($code === '') {
            return ['valid' => true, 'id' => null, 'discount_amount' => 0.0, 'message' => null];
        }

        $discountCode = DiscountCode::where('code', $code)->first();

        if (! $discountCode) {
            return ['valid' => false, 'id' => null, 'discount_amount' => 0.0, 'message' => 'Geçersiz indirim kodu.'];
        }

        if (! $discountCode->appliesToProducts($products)) {
            return ['valid' => false, 'id' => null, 'discount_amount' => 0.0, 'message' => 'Bu indirim kodu sepetinizdeki ürünler için geçerli değil.'];
        }

        if ($discountCode->min_order_amount && $rawTotal < $discountCode->min_order_amount) {
            return ['valid' => false, 'id' => null, 'discount_amount' => 0.0, 'message' => "Bu kod için sipariş tutarı en az {$discountCode->min_order_amount} ₺ olmalı."];
        }

        return [
            'valid' => true,
            'id' => $discountCode->id,
            'discount_amount' => $discountCode->discountAmountFor($rawTotal),
            'message' => null,
        ];
    }

    /**
     * evaluateDiscountCode sonucunu sipariş kaydı için kullanılabilir hale getirir;
     * kod geçersizse doğrudan doğrulama hatası fırlatır.
     *
     * @param  list<Product>  $products
     * @return array{0: int|null, 1: float}
     */
    private function resolveDiscountCode(Request $request, array $products, float $rawTotal): array
    {
        $code = trim((string) $request->input('discount_code'));
        $result = $this->evaluateDiscountCode($code, $products, $rawTotal);

        if (! $result['valid']) {
            throw ValidationException::withMessages([
                'discount_code' => $result['message'],
            ]);
        }

        return [$result['id'], $result['discount_amount']];
    }

    /**
     * Sipariş formundan, kaydetmeden önce bir indirim kodunun geçerli olup
     * olmadığını ve ne kadar indirim sağlayacağını sormak için kullanılır.
     */
    public function checkDiscountCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'discount_code' => 'required|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $products = [];
        $rawTotal = 0;

        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail((int) $item['product_id']);
            $products[] = $product;
            $rawTotal += $product->discountedPrice() * $item['quantity'];
        }

        $result = $this->evaluateDiscountCode(trim($validated['discount_code']), $products, $rawTotal);

        return response()->json([
            'valid' => $result['valid'],
            'message' => $result['message'],
            'discount_amount' => $result['discount_amount'],
        ]);
    }

    /**
     * Siparişin teslimat adresini doğrular. Müşterinin kayıtlı adresi varsa
     * ya bunlardan birinin seçilmesi ya da yeni bir adres girilmesi (address_mode=new)
     * gerekir; hiç adresi yoksa yeni bir adres girilmesi zorunludur.
     *
     * @return array{0: int|null, 1: array{label: string, address: string}|null}
     */
    private function resolveOrderAddress(Request $request, Customer $customer): array
    {
        $hasAddresses = $customer->addresses()->exists();
        $wantsNewAddress = ! $hasAddresses || $request->input('address_mode') === 'new';

        if ($wantsNewAddress) {
            $validated = $request->validate([
                'new_address_label' => 'required|string|max:100',
                'new_address_text' => 'required|string|max:500',
            ]);

            return [null, [
                'label' => $validated['new_address_label'],
                'address' => $validated['new_address_text'],
            ]];
        }

        $validated = $request->validate([
            'customer_address_id' => [
                'required',
                Rule::exists('customer_addresses', 'id')->where(
                    fn ($query) => $query->where('customer_id', $customer->id)
                ),
            ],
        ]);

        return [(int) $validated['customer_address_id'], null];
    }

    public function edit(int $id): Response
    {
        $order = Order::with('orderItems.product', 'customer', 'discountCode')->findOrFail($id);

        return Inertia::render('orders/edit', [
            'order' => $order,
            'customers' => Customer::with('addresses')->get(),
            'products' => $this->productsWithCampaigns(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $order = Order::with('orderItems')->findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000000',
        ]);

        $customer = Customer::findOrFail((int) $validated['customer_id']);
        [$existingAddressId, $newAddress] = $this->resolveOrderAddress($request, $customer);

        $products = [];
        $rawTotal = 0;

        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail((int) $item['product_id']);
            $products[] = $product;
            $rawTotal += $product->discountedPrice() * $item['quantity'];
        }

        [$discountCodeId, $discountAmount] = $this->resolveDiscountCode($request, $products, $rawTotal);

        DB::transaction(function () use ($order, $validated, $customer, $existingAddressId, $newAddress, $rawTotal, $discountCodeId, $discountAmount) {
            foreach ($order->orderItems as $oldItem) {
                Product::where('id', $oldItem->product_id)->increment('stock_quantity', $oldItem->quantity);
            }
            $order->orderItems()->delete();

            $customerAddressId = $newAddress
                ? $customer->addresses()->create($newAddress)->id
                : $existingAddressId;

            $order->update([
                'customer_id' => $validated['customer_id'],
                'customer_address_id' => $customerAddressId,
                'total_amount' => $rawTotal - $discountAmount,
                'discount_code_id' => $discountCodeId,
                'discount_amount' => $discountAmount,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->discountedPrice(),
                ]);

                $product->decrement('stock_quantity', $item['quantity']);
            }
        });

        return redirect()->route('orders.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $order = Order::with('orderItems')->findOrFail($id);

        DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $item) {
                Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
            }

            $order->orderItems()->delete();
            $order->delete();
        });

        return redirect()->route('orders.index');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,shipped,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $validated['status']]);

        Mail::to($order->customer->email)->send(new OrderStatusUpdated($order));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Sipariş durumu güncellendi.',
        ]);

        return redirect()->route('orders.show', $id);
    }
}
