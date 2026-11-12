<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\BiHubController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShoppingController;
use App\Http\Controllers\SpkController;
use App\Http\Controllers\WaReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Sistem Penunjang Keputusan (Decision Support System) ─────────────────
    Route::get('/spk', [SpkController::class, 'index'])->name('spk.index');
    Route::post('/spk/ai-recommendation', [SpkController::class, 'aiRecommendRestock'])->name('spk.ai_recommendation');
    Route::get('/spk/export-pdf', [SpkController::class, 'exportPdf'])->name('spk.export');

    // ── Laporan Tutup Warung via WhatsApp (wa.me direct link) ─────────────
    Route::get('/wa-report/preview', [WaReportController::class, 'preview'])->name('wa_report.preview');
    Route::post('/wa-report/send', [WaReportController::class, 'send'])->name('wa_report.send');

    // ── Asisten Virtual Interaktif "Tanya Stokku" (Contextual AI Chatbot) ──
    Route::post('/ai/chat', [AiAssistantController::class, 'chat'])->name('ai.chat');
    Route::post('/chatbot/message', [AiAssistantController::class, 'chat'])->name('chatbot.message');

    // ── Reports & Expenses ────────────────────────────────────────────────
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/finance', [ReportController::class, 'exportFinance'])->name('reports.finance');
    Route::get('/reports/inventory', [ReportController::class, 'exportInventory'])->name('reports.inventory');
    Route::post('/reports/expenses', [ReportController::class, 'storeExpense'])->name('reports.store_expense');

    // ── POS (Point of Sale) ───────────────────────────────────────────────
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');

    // ── Scan Struk Pasar & Kulakan AI (/purchases & /inventory/shopping) ──
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::post('/purchases/parse', [PurchaseController::class, 'parseReceipt'])->name('purchases.parse');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::post('/purchases/update-price', [PurchaseController::class, 'updateRetailPrice'])->name('purchases.update_price');

    Route::get('/inventory/shopping', [ShoppingController::class, 'index'])->name('inventory.shopping');
    Route::post('/inventory/shopping/parse', [ShoppingController::class, 'parseReceipt'])->name('inventory.shopping.parse');
    Route::post('/inventory/shopping/store', [ShoppingController::class, 'store'])->name('inventory.shopping.store');
    Route::post('/inventory/shopping', [ShoppingController::class, 'store']);

    // ── Inventory ─────────────────────────────────────────────────────────
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/materials', [InventoryController::class, 'materials'])->name('inventory.materials');
    Route::post('/inventory/materials/reset-aman', [InventoryController::class, 'resetAllAman'])->name('inventory.materials.reset_aman');
    Route::post('/inventory/materials', [InventoryController::class, 'storeIngredient'])->name('inventory.materials.store');
    Route::post('/inventory/materials/{ingredient}/update', [InventoryController::class, 'updateIngredient'])->name('inventory.materials.update');
    Route::post('/inventory/materials/{ingredient}/quick-status', [InventoryController::class, 'updateQuickStatus'])->name('inventory.materials.quick_status');
    Route::post('/inventory/materials/quick-ai-status', [InventoryController::class, 'quickAiStatus'])->name('inventory.materials.quick_ai');
    Route::get('/inventory/production', [InventoryController::class, 'production'])->name('inventory.production');
    Route::post('/inventory/purchase', [InventoryController::class, 'storePurchase'])->name('inventory.purchase');
    Route::post('/inventory/opname', [InventoryController::class, 'storeOpname'])->name('inventory.opname');
    Route::post('/inventory/produce', [InventoryController::class, 'storeProduction'])->name('inventory.produce');
    Route::post('/inventory/production-opname', [InventoryController::class, 'storeProductionOpname'])->name('inventory.production.opname');

    // ── Analisis Data ─────────────────────────────────────────────────────
    Route::get('/analysis/klasifikasi', [AnalysisController::class, 'klasifikasi'])->name('analysis.klasifikasi');
    Route::post('/analysis/ai-bcg', [AnalysisController::class, 'aiBcgInsight'])->name('analysis.ai_bcg');
    Route::get('/analysis/prediksi', [AnalysisController::class, 'prediksi'])->name('analysis.prediksi');
    Route::post('/analysis/ai-prediksi', [AnalysisController::class, 'aiPrediksiInsight'])->name('analysis.ai_prediksi');

    // ── BI Hub (Business Intelligence) ───────────────────────────────────
    Route::get('/bi-hub', [BiHubController::class, 'index'])->name('bi-hub.index');
    Route::get('/reports/sales-trend', [BiHubController::class, 'salesTrend'])->name('reports.sales_trend');
    Route::get('/reports/menu-ranking', [BiHubController::class, 'menuRanking'])->name('reports.menu_ranking');
    Route::get('/reports/waste-analysis', [BiHubController::class, 'wasteAnalysis'])->name('reports.waste_analysis');

    // ── Recipe Management ─────────────────────────────────────────────────
    Route::get('/recipe', [RecipeController::class, 'index'])->name('recipe.index');
    Route::post('/recipe', [RecipeController::class, 'store'])->name('recipe.store');
    Route::get('/recipe/{recipe}/edit', [RecipeController::class, 'edit'])->name('recipe.edit');
    Route::put('/recipe/{recipe}', [RecipeController::class, 'update'])->name('recipe.update');
    Route::delete('/recipe/{recipe}', [RecipeController::class, 'destroy'])->name('recipe.destroy');
});

require __DIR__.'/auth.php';
