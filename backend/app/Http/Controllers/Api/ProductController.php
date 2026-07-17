<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return Product::query()
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:150'],
            'barcode' => ['nullable', 'string', 'max:50', 'unique:products,barcode'],
            'type' => ['required', Rule::in([Product::RAW_MATERIAL, Product::FINISHED_GOOD])],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
        ]);

        return response()->json(Product::create($data), 201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku' => ['sometimes', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product->id)],
            'name' => ['sometimes', 'string', 'max:150'],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->ignore($product->id)],
            'unit' => ['sometimes', 'string', 'max:20'],
            'cost_price' => ['sometimes', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'numeric', 'min:0'],
            'reorder_level' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product->update($data);

        return $product;
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'تم حذف المنتج.']);
    }

    /** BOM management for a finished good. */
    public function updateBom(Request $request, Product $product)
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.raw_material_id' => ['required', 'exists:products,id'],
            'lines.*.quantity_required' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit' => ['required', 'string', 'max:20'],
            'lines.*.scrap_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $product->bomLines()->delete();

        foreach ($data['lines'] as $line) {
            $product->bomLines()->create([
                'raw_material_id' => $line['raw_material_id'],
                'quantity_required' => $line['quantity_required'],
                'unit' => $line['unit'],
                'scrap_percentage' => $line['scrap_percentage'] ?? 0,
            ]);
        }

        return $product->load('bomLines.rawMaterial');
    }
}
