<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
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

    /**
     * name/email/phone kolonlarından birini, turkishLower ile aynı kurallara göre
     * küçük harfe çeviren bir SQL ifadesine dönüştürür.
     *
     * @return literal-string
     */
    private function turkishLowerSql(string $column): string
    {
        return match ($column) {
            'name' => "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'İ', 'i'), 'I', 'ı'), 'Ç', 'ç'), 'Ğ', 'ğ'), 'Ö', 'ö'), 'Ş', 'ş'), 'Ü', 'ü'))",
            'email' => "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(email, 'İ', 'i'), 'I', 'ı'), 'Ç', 'ç'), 'Ğ', 'ğ'), 'Ö', 'ö'), 'Ş', 'ş'), 'Ü', 'ü'))",
            'phone' => "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, 'İ', 'i'), 'I', 'ı'), 'Ç', 'ç'), 'Ğ', 'ğ'), 'Ö', 'ö'), 'Ş', 'ş'), 'Ü', 'ü'))",
            default => throw new \InvalidArgumentException("Desteklenmeyen kolon: {$column}"),
        };
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = Customer::query();

        if ($search !== '') {
            $needle = '%'.$this->turkishLower($search).'%';

            $query->where(function ($q) use ($needle) {
                foreach (['name', 'email', 'phone'] as $column) {
                    $q->orWhereRaw($this->turkishLowerSql($column).' LIKE ?', [$needle]);
                }
            });
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        return Inertia::render('customers/index', [
            'customers' => $query->orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('customers/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'address_label' => 'required|string|max:100',
            'address_text' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            $customer = Customer::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
            ]);

            $customer->addresses()->create([
                'label' => $validated['address_label'],
                'address' => $validated['address_text'],
            ]);
        });

        return redirect()->route('customers.index');
    }

    public function edit(int $id): Response
    {
        return Inertia::render('customers/edit', [
            'customer' => Customer::with('addresses')->findOrFail($id),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email,'.$id,
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);

        if ($customer->orders()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Bu müşteriye bağlı siparişler olduğu için silinemez.',
            ]);

            return redirect()->route('customers.index');
        }

        $customer->delete();

        return redirect()->route('customers.index');
    }
}
