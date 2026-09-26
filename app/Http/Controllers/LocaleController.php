<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController
{
    public function update(Request $request)
    {
        $locale = $request->validate(['locale' => 'required|in:en,id'])['locale'];

        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        session(['locale' => $locale]);

        return back();
    }
}
