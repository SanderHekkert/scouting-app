<?php

namespace App\Http\Controllers\Concerns;

use App\Support\SaveFormRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RedirectsAfterSave
{
    protected function redirectAfterSave(Request $request, ?string $fallbackRoute = null, array $fallbackParameters = []): RedirectResponse
    {
        return SaveFormRedirect::afterSave($request, $fallbackRoute, $fallbackParameters);
    }
}
