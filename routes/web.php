<?php

use App\Http\Controllers\DownloadController;
use App\Livewire\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

Route::get('/', fn () => view('home'))->name('home');

Route::post('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['es', 'en'], true), 404);
    session(['locale' => $locale]);

    return back();
})->name('locale');

Route::get('/download/{file}', DownloadController::class)
    ->middleware('signed')
    ->name('download.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/historial', History::class)->name('history');
});

Route::view('/terminos', 'legal.terms')->name('legal.terms');
Route::view('/privacidad', 'legal.privacy')->name('legal.privacy');
Route::view('/dmca', 'legal.dmca')->name('legal.dmca');
Route::view('/contacto', 'legal.contact')->name('legal.contact');

Route::post('/contacto', function (Request $request) {
    $data = $request->validate([
        'email' => ['required', 'email', 'max:255'],
        'message' => ['required', 'string', 'max:2000'],
    ]);

    Mail::raw($data['message'], function ($message) use ($data) {
        $message->to(config('downloader.contact_email'))
            ->replyTo($data['email'])
            ->subject('Postgrab');
    });

    return back()->with('status', __('app.contact_sent'));
})->middleware(['throttle:5,60', ProtectAgainstSpam::class])->name('legal.contact.store');

Route::get('/home', fn () => redirect()->route('home'));
