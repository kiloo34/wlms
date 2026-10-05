<?php

namespace App\Modules\Identity\Presentation\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse implements LoginResponse
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $redirect = Fortify::redirects('login');

        return $request->wantsJson()
            ? response()->json(['location' => $redirect])
            : redirect()->intended($redirect);
    }
}
