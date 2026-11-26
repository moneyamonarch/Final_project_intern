<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index()
    {
        $dq = Storage::exists('dq_report.json') ? (json_decode(Storage::get('dq_report.json'), true) ?? []) : [];

        return view('admin', [
            'dq' => $dq,
            'weights' => DB::table('recommender_weights')->orderByDesc('version')->get(),
            'events' => DB::table('feedback_events')->selectRaw('event, COUNT(*) as c')->groupBy('event')->pluck('c', 'event'),
            'synthetic' => DB::table('feedback_events')->where('is_synthetic', true)->count(),
            'runs' => DB::table('recommendation_runs')->count(),
        ]);
    }
}
