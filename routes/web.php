<?php

use App\Http\Controllers\DownloadController;
use App\Livewire\History;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

Route::get('/', function (Request $request) {
    return redirect()->route('home.locale', array_merge(
        $request->except('locale'),
        ['locale' => Locales::default()],
    ), 301);
})->name('home');

Route::get('/robots.txt', function () {
    return response(implode("\n", [
        'User-agent: *',
        'Allow: /',
        'Disallow: /admin',
        'Disallow: /historial',
        'Disallow: /download',
        'Disallow: /login',
        'Disallow: /register',
        'Disallow: /forgot-password',
        'Disallow: /reset-password',
        'Disallow: /email',
        'Disallow: /horizon',
        'Disallow: /livewire',
        '',
        'Sitemap: '.url('/sitemap.xml'),
        '',
    ]), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});

Route::get('/sitemap.xml', fn () => response()
    ->view('sitemap')
    ->header('Content-Type', 'application/xml'));

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

Route::get('/home', fn () => redirect()->route('home.locale', Locales::default(), 301));

Route::get('/{locale}', function (string $locale) {
    abort_unless(Locales::supported($locale), 404);

    session(['locale' => $locale]);
    app()->setLocale($locale);

    return view('home');
})->whereIn('locale', Locales::CODES)->name('home.locale');
