<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    
    //Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //Get and Store PublicKey
    Route::get('/user/{id}/public-key', [UserController::class, 'getPublicKey']);
    Route::post('/store-public-key', function (Request $request) {
        $user = Auth::user();
        $user->update([
            'public_key' => $request->publicKey,
        ]);
        return response()->json(['status' => 'Public key stored successfully']);
    })->name('store.public.key');

    //Album
    Route::get('/albums', [AlbumController::class, 'index'])->name('albums.index');
    Route::get('/albums/create', [AlbumController::class, 'create'])->name('albums.create');
    Route::post('/albums', [AlbumController::class, 'store'])->name('albums.store');
    Route::get('/albums/{album}', [AlbumController::class, 'show'])->name('albums.show');
    Route::delete('/albums/{album}', [AlbumController::class, 'destroy'])->name('albums.destroy');
    Route::post('/albums/{id}/share', [AlbumController::class, 'share'])->name('albums.share');

    //Photo
    Route::get('/photos', [PhotoController::class, 'index'])->name('photos.index');
    Route::post('/albums/photos', [PhotoController::class, 'store'])->name('photos.store');
    Route::post('/albums/{album}/photos', [PhotoController::class, 'storeWithAlbum'])->name('photos.storeWithAlbum');
    Route::get('/photos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
    Route::delete('/photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
    Route::post('/photos/{photo}/share', [PhotoController::class, 'share'])->name('photos.share');
});

require __DIR__.'/auth.php';
