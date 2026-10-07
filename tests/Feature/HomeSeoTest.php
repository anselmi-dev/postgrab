<?php

use App\Support\Locales;

it('redirects the root to the default locale home', function () {
    $this->get('/')
        ->assertStatus(301)
        ->assertRedirect(route('home.locale', Locales::default()));
});

it('keeps a shared post link on the localized home', function () {
    $url = 'https://x.com/jack/status/20';

    $this->get('/?url='.urlencode($url))
        ->assertStatus(301)
        ->assertRedirect(route('home.locale', ['locale' => Locales::default(), 'url' => $url]));
});

it('publishes a crawlable home in each language', function (string $locale) {
    $this->get('/'.$locale)
        ->assertOk()
        ->assertSee('<html lang="'.Locales::hreflang($locale).'">', false)
        ->assertSee('<link rel="canonical" href="'.route('home.locale', $locale).'">', false)
        ->assertSee('hreflang="x-default"', false)
        ->assertSee('hreflang="pt-BR"', false)
        ->assertSee('hreflang="de"', false)
        ->assertSee('hreflang="fr"', false)
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('"@type":"HowTo"', false)
        ->assertSee(__('app.seo_how_title', [], $locale))
        ->assertSee(__('app.seo_faq.0.q', [], $locale))
        ->assertSee(__('app.seo_title', [], $locale), false)
        ->assertDontSee('app.seo_intro_body');
})->with(Locales::codes());

it('rejects an unknown locale', function () {
    $this->get('/xx')->assertNotFound();
});

it('remembers the language chosen on the home', function () {
    $this->get('/fr')->assertOk();

    $this->get('/terminos')->assertSee(__('legal.terms_title', [], 'fr'));
});

it('lists every language home in the sitemap', function () {
    $response = $this->get('/sitemap.xml')->assertOk();

    foreach (Locales::codes() as $locale) {
        $response->assertSee(route('home.locale', $locale), false);
    }

    $response->assertSee('hreflang="pt-BR"', false);
});

it('points robots.txt at the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false)
        ->assertSee('Disallow: /admin', false);
});
