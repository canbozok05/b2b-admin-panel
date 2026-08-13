<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(): Response
    {
        $campaigns = Campaign::with('product:id,name', 'category:id,name')
            ->latest()
            ->get()
            ->map(function (Campaign $campaign) {
                $campaign->status = match (true) {
                    now()->lt($campaign->starts_at) => 'Planlanan',
                    now()->gt($campaign->ends_at) => 'Sona Erdi',
                    default => 'Aktif',
                };

                return $campaign;
            });

        return Inertia::render('campaigns/index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('campaigns/create', [
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Campaign::create($this->validated($request));

        return redirect()->route('campaigns.index');
    }

    public function edit(int $id): Response
    {
        return Inertia::render('campaigns/edit', [
            'campaign' => Campaign::findOrFail($id),
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->update($this->validated($request));

        return redirect()->route('campaigns.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        Campaign::findOrFail($id)->delete();

        return redirect()->route('campaigns.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => $request->input('discount_type') === 'percentage'
                ? 'required|numeric|min:0.01|max:100'
                : 'required|numeric|min:0.01|max:99999999.99',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'product_id' => 'nullable|exists:products,id|required_without:category_id|prohibits:category_id',
            'category_id' => 'nullable|exists:categories,id|required_without:product_id|prohibits:product_id',
        ]);
    }
}
