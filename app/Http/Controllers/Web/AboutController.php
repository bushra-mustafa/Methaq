<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Support\PageMeta;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Web/About', ['meta' => PageMeta::make('من نحن', 'ميثاق منصة عربية لصناعة الدعوات الإلكترونية بظرف يُفتح، تصمّمينها وتشاركينها برابط واحد.', '/about')]);
    }
}
