<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    /** Load a demo persona into the session and open its results. */
    public function start(Request $request, string $persona): RedirectResponse
    {
        $p = config('personas.'.$persona);
        abort_if($p === null, 404);

        $request->session()->put('questionnaire', $p['answers'] + [
            'completed' => true,
            'persona' => ['key' => $persona, 'label' => $p['label'], 'description' => $p['description']],
        ]);

        return redirect()->route('recommendations.index');
    }
}