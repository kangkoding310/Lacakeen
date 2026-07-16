<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function inbox(Request $request): Response
    {
        return Inertia::render('Inbox/Index', ['items' => $request->user()->notifications()->paginate(30)]);
    }

    public function help(): Response
    {
        return Inertia::render('HelpCenter/Index');
    }
}
