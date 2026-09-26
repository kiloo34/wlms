<?php

namespace App\Modules\Identity\Presentation\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Laravel\Fortify\Fortify;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $redirect = Fortify::redirects('login');

        return $request->wantsJson()
            ? response()->json(['location' => $redirect])
            : redirect()->intended($redirect);
    }
}
