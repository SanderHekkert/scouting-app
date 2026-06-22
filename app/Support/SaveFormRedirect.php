<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SaveFormRedirect
{
    /**
     * Return URL voor Inertia (query, sessie).
     */
    public static function sharedReturnUrl(Request $request): ?string
    {
        $fromQuery = $request->query('return_url');
        if (is_string($fromQuery) && trim($fromQuery) !== '') {
            return $fromQuery;
        }

        $fromSession = $request->session()->get('inertia_return_url');
        if (is_string($fromSession) && trim($fromSession) !== '') {
            return $fromSession;
        }

        return null;
    }

    public static function afterSave(
        Request $request,
        ?string $fallbackRoute = null,
        array $fallbackParameters = [],
    ): RedirectResponse {
        if (! $request->boolean('redirect_back')) {
            return back();
        }

        foreach (self::returnUrlCandidates($request) as $candidate) {
            if (self::isSafeReturnUrl($candidate, $request->url())) {
                return redirect($candidate);
            }
        }

        if ($fallbackRoute !== null) {
            return to_route($fallbackRoute, $fallbackParameters);
        }

        return back();
    }

    /**
     * @return list<string|null>
     */
    public static function returnUrlCandidates(Request $request): array
    {
        $returnTo = $request->input('return_url');

        return [
            is_string($returnTo) ? $returnTo : null,
            $request->session()->get('inertia_return_url'),
        ];
    }

    public static function isSafeReturnUrl(?string $url, string $currentUrl): bool
    {
        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        if (self::isSaveFormUrl($url)) {
            return false;
        }

        $allowedHosts = array_values(array_filter(array_unique([
            parse_url(rtrim((string) config('app.url'), '/'), PHP_URL_HOST),
            parse_url($currentUrl, PHP_URL_HOST),
        ])));

        $urlHost = parse_url($url, PHP_URL_HOST);
        if ($urlHost === null || $allowedHosts === [] || ! in_array($urlHost, $allowedHosts, true)) {
            return false;
        }

        return rtrim($url, '/') !== rtrim($currentUrl, '/');
    }

    public static function isSaveFormUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return (bool) preg_match('#/(bewerken|nieuw|uitnodigen)/?$#', $path);
    }
}
