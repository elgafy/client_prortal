<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Portal\BalanceController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\PaymentController as PortalPaymentController;
use App\Http\Controllers\Portal\ProjectController as PortalProjectController;
use App\Http\Controllers\Portal\StatementController as PortalStatementController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StatementController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Internal application — administrators and staff only.
Route::middleware(['auth', 'verified', 'internal'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('clients', ClientController::class)->except(['destroy']);
    Route::patch('clients/{client}/archive', [ClientController::class, 'archive'])->name('clients.archive');
    Route::post('clients/{client}/invite', [ClientController::class, 'invite'])->name('clients.invite');

    Route::resource('projects', ProjectController::class);

    Route::resource('payments', PaymentController::class)->except(['destroy']);
    Route::patch('payments/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');

    Route::get('clients/{client}/statement', [StatementController::class, 'show'])->name('clients.statement');
    Route::get('clients/{client}/statement/pdf', [StatementController::class, 'pdf'])->name('clients.statement.pdf');
    Route::get('clients/{client}/statement/excel', [StatementController::class, 'excel'])->name('clients.statement.excel');
});

// Comments — internal users comment on anything; client users on their own records only.
Route::middleware(['auth'])->group(function () {
    Route::post('comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

// Client portal — read-only, scoped to the authenticated client's own account.
Route::prefix('portal')->name('portal.')->middleware(['auth', 'client'])->group(function () {
    Route::get('dashboard', PortalDashboardController::class)->name('dashboard');
    Route::get('projects', [PortalProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
    Route::get('payments', [PortalPaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [PortalPaymentController::class, 'show'])->name('payments.show');
    Route::get('balance', BalanceController::class)->name('balance');
    Route::get('statement', [PortalStatementController::class, 'show'])->name('statement');
    Route::get('statement/pdf', [PortalStatementController::class, 'pdf'])->name('statement.pdf');
    Route::get('statement/excel', [PortalStatementController::class, 'excel'])->name('statement.excel');
});

require __DIR__.'/settings.php';
