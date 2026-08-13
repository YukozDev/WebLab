<?php

use App\Http\Controllers\Admin\AuthLogController;
use App\Http\Controllers\Admin\SecuritySettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

/*
|--------------------------------------------------------------------------
| Routes accessibles aux visiteurs non authentifies
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'afficherFormulaire'])->name('login');

    // throttle limite le nombre de soumissions par minute et par adresse IP.
    // Cette limite est independante du compteur de tentatives par compte : elle
    // freine un attaquant qui balaierait de nombreux identifiants differents,
    // ce que le compteur par compte ne detecterait pas.
    Route::post('/connexion', [LoginController::class, 'connecter'])->middleware('throttle:10,1');
});

/*
|--------------------------------------------------------------------------
| Routes reservees aux utilisateurs authentifies
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'password.current'])->group(function () {

    Route::post('/deconnexion', [LoginController::class, 'deconnecter'])->name('logout');

    Route::get('/tableau-de-bord', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/mot-de-passe', [PasswordController::class, 'afficherFormulaire'])->name('password.edit');
    Route::put('/mot-de-passe', [PasswordController::class, 'modifier'])->name('password.update');

    // --- Listes de clients ---
    Route::get('/clients/residentiels', [ClientController::class, 'residentiels'])
        ->middleware('role:' . Role::ADMINISTRATEUR . ',' . Role::PREPOSE_RESIDENTIEL)
        ->name('clients.residentiels');

    Route::get('/clients/affaires', [ClientController::class, 'affaires'])
        ->middleware('role:' . Role::ADMINISTRATEUR . ',' . Role::PREPOSE_AFFAIRE)
        ->name('clients.affaires');

    // --- Administration : reservee au seul role Administrateur ---
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:' . Role::ADMINISTRATEUR)
        ->group(function () {
            Route::get('/parametres', [SecuritySettingController::class, 'edit'])->name('parametres.edit');
            Route::put('/parametres', [SecuritySettingController::class, 'update'])->name('parametres.update');

            Route::get('/utilisateurs', [UserController::class, 'index'])->name('utilisateurs.index');
            Route::get('/utilisateurs/creer', [UserController::class, 'create'])->name('utilisateurs.create');
            Route::post('/utilisateurs', [UserController::class, 'store'])->name('utilisateurs.store');
            
            Route::get('/journaux', [AuthLogController::class, 'index'])->name('journaux.index');
        });
});
