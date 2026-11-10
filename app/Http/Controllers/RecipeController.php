<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::with('items.ingredient')->get();
        $ingredients = \App\Models\Ingredient::orderBy('name')->get();
        return view('recipe.index', compact('recipes', 'ingredients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'price_eceran' => 'required|numeric|min:0',
            'price_reseller' => 'required|numeric|min:0',
            'price_agen' => 'required|numeric|min:0',
            'packaging_cost' => 'required|numeric|min:0',
            'overhead_cost' => 'required|numeric|min:0',
            'ingredients' => 'required|json',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            // 1. Buat Konversi Kemasan Baru
            $recipe = Recipe::create([
                'name' => 'Kemas Ulang ' . $request->name,
                'packaging_cost' => $request->packaging_cost,
                'overhead_cost' => $request->overhead_cost,
            ]);

            $ingredientsData = json_decode($request->ingredients, true);

            foreach ($ingredientsData as $item) {
                // Jika ingredient_id kosong, berarti bahan baru (diketik manual)
                if (empty($item['ingredient_id'])) {
                    $ingredient = \App\Models\Ingredient::create([
                        'name' => $item['name'],
                        'unit' => $item['unit'],
                        'min_stock' => 50, // default
                        'current_stock' => 0, // stok awal 0
                        'cost_per_unit' => $item['cost_per_unit'],
                    ]);
                    $ingredientId = $ingredient->id;
                } else {
                    $ingredientId = $item['ingredient_id'];
                }

                \App\Models\RecipeItem::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ingredientId,
                    'quantity' => $item['quantity'],
                ]);
            }

            // 2. Buat Menu Item Baru (Produk Jadi)
            $menu = \App\Models\MenuItem::create([
                'recipe_id' => $recipe->id,
                'name' => $request->name,
                'category' => $request->category,
                'price_eceran' => $request->price_eceran,
                'price_reseller' => $request->price_reseller,
                'price_agen' => $request->price_agen,
                'current_stock' => 0,
                'is_active' => true,
            ]);

            // Hitung HPP Otomatis
            $menu->syncHpp();
        });

        return redirect()->back()->with('success', 'Detail Kemasan dan Komoditas Baru berhasil ditambahkan! Stok otomatis tercatat di gudang.');
    }

    public function edit(Recipe $recipe)
    {
        $recipe->load('items.ingredient', 'menuItem');
        $ingredients = \App\Models\Ingredient::orderBy('name')->get();
        return view('recipe.edit', compact('recipe', 'ingredients'));
    }

    public function update(Request $request, Recipe $recipe)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'price_eceran' => 'required|numeric|min:0',
            'price_reseller' => 'required|numeric|min:0',
            'price_agen' => 'required|numeric|min:0',
            'packaging_cost' => 'required|numeric|min:0',
            'overhead_cost' => 'required|numeric|min:0',
            'ingredients' => 'required|json',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $recipe) {
            $recipe->update([
                'name' => 'Kemas Ulang ' . $request->name,
                'packaging_cost' => $request->packaging_cost,
                'overhead_cost' => $request->overhead_cost,
            ]);

            // Clear old items
            $recipe->items()->delete();

            $ingredientsData = json_decode($request->ingredients, true);
            foreach ($ingredientsData as $item) {
                if (empty($item['ingredient_id'])) {
                    $ingredient = \App\Models\Ingredient::create([
                        'name' => $item['name'],
                        'unit' => $item['unit'],
                        'min_stock' => 50,
                        'current_stock' => 0,
                        'cost_per_unit' => $item['cost_per_unit'],
                    ]);
                    $ingredientId = $ingredient->id;
                } else {
                    $ingredientId = $item['ingredient_id'];
                }

                \App\Models\RecipeItem::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ingredientId,
                    'quantity' => $item['quantity'],
                ]);
            }

            // Update menu item
            $menu = $recipe->menuItem;
            if ($menu) {
                $menu->update([
                    'name' => $request->name,
                    'category' => $request->category,
                    'price_eceran' => $request->price_eceran,
                    'price_reseller' => $request->price_reseller,
                    'price_agen' => $request->price_agen,
                ]);
                $menu->syncHpp();
            }
        });

        return redirect()->route('recipe.index')->with('success', 'Detail kemasan berhasil diperbarui!');
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->delete();
        return redirect()->route('recipe.index')->with('success', 'Detail kemasan berhasil dihapus.');
    }
}
