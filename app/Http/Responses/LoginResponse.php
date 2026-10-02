<?php

namespace App\Http\Responses;

use App\Support\PostLoginRedirect;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $url = redirect()->intended(PostLoginRedirect::for($request->user()))->getTargetUrl();

        // The login form is an Inertia page, but the destination (admin/vendor
        // dashboards, the public home page) is a plain Blade view. Inertia::location()
        // forces a real browser visit on Inertia requests instead of an SPA
        // transition — otherwise Inertia shows its raw-response error modal
        // when it can't render a non-Inertia response as a page.
        return Inertia::location($url);
    }
}
