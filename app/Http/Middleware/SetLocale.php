<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the locale chosen by the visitor (or the French default) so
 * validation messages, flash statuses, and shared props all resolve in
 * the same language. The choice persists in the unencrypted `locale`
 * cookie, mirroring the appearance cookie pattern.
 */
final readonly class SetLocale
{
    private const string DEFAULT_LOCALE = 'fr';

    private const array SUPPORTED_LOCALES = ['fr', 'en'];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        App::setLocale(
            is_string($locale) && in_array($locale, self::SUPPORTED_LOCALES, true)
                ? $locale
                : self::DEFAULT_LOCALE,
        );

        return $next($request);
    }
}
