<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    config(['app.debug' => false]);
});

it('renders minimal errors for direct and Inertia requests', function (int $code, string $title): void {
    Route::middleware('web')->get('/_test/error', fn () => abort($code, 'PRIVATE exception details', ['Retry-After' => '60']));

    foreach ([[], ['X-Inertia' => 'true']] as $headers) {
        if ($headers !== []) {
            $headers['X-Inertia-Version'] = Inertia::getVersion();
        }

        $response = $this->withUnencryptedCookie('locale', 'en')->get('/_test/error', $headers);
        $response->assertStatus($code)->assertHeader('Retry-After', '60')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        if ($headers !== []) {
            $response->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'error')
                ->assertJsonPath('props.code', $code)->assertJsonPath('props.title', $title)
                ->assertJsonMissingPath('props.auth')->assertJsonMissingPath('props.exception');
        } else {
            $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('error')->where('code', $code)->where('title', $title)
                ->missing('auth')->missing('exception'));
        }

        expect($response->getContent())->not->toContain('PRIVATE exception details');
    }
})->with([
    [400, 'Invalid request.'], [401, 'Please sign in.'], [403, 'Access denied.'],
    [404, 'Page not found.'], [405, 'Request not allowed.'], [408, 'Request timed out.'],
    [410, 'Page no longer available.'], [413, 'File too large.'], [419, 'Page expired.'],
    [422, 'Invalid request.'], [429, 'Too many requests.'], [500, 'Something went wrong.'],
    [502, 'Temporarily unavailable.'], [503, 'Temporarily unavailable.'], [504, 'Request timed out.'],
    [418, 'Something went wrong.'], [507, 'Something went wrong.'],
]);

it('handles missing routes without shared application data', function (): void {
    $this->get('/this-page-does-not-exist')->assertNotFound()->assertInertia(
        fn (AssertableInertia $page): AssertableInertia => $page->component('error')->where('code', 404)->missing('auth'),
    );
});

it('hides unexpected exception details even with debug enabled', function (): void {
    config(['app.debug' => true]);
    Route::middleware('web')->get('/_test/error', fn () => throw new RuntimeException('PRIVATE database credentials'));
    $response = $this->get('/_test/error')->assertStatus(500)->assertInertia(
        fn (AssertableInertia $page): AssertableInertia => $page->component('error')->where('code', 500),
    );
    expect($response->getContent())->not->toContain('PRIVATE database credentials');
});

it('keeps JSON errors and required HTTP headers', function (): void {
    Route::middleware('web')->get('/_test/error', fn () => abort(429, 'Rate limited', ['Retry-After' => '60']));
    $this->getJson('/_test/error')->assertStatus(429)->assertHeader('Retry-After', '60')
        ->assertJsonPath('message', 'Rate limited')->assertJsonMissingPath('component');
});

it('keeps validation redirects instead of replacing them with an error page', function (): void {
    Route::middleware('web')->post('/_test/validation', fn () => request()->validate(['name' => ['required']]));
    $this->from('/contact')->post('/_test/validation')->assertRedirect('/contact')->assertSessionHasErrors('name');
});

it('provides server-rendered fallbacks without frontend assets or exception details', function (string $view, int $code): void {
    $html = view('errors.'.$view, ['exception' => new HttpException($code, 'PRIVATE details')])->render();
    expect($html)->toContain((string) $code, 'noindex, nofollow')->not->toContain('PRIVATE details', '<script', '/build/');
})->with([['404', 404], ['500', 500], ['503', 503], ['4xx', 418], ['5xx', 507]]);

it('falls back to minimal HTML when the frontend cannot render', function (): void {
    Inertia::partialMock()->shouldReceive('render')->once()->andThrow(new RuntimeException('PRIVATE asset path'));
    $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('<main>', false)
        ->assertDontSee('PRIVATE asset path')->assertDontSee('/build/')->assertSee('noindex, nofollow');
});
