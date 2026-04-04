<?php

namespace App\Http\Controllers;

use App\Models\SitePublicNotice;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $publicNotices = SitePublicNotice::visible()
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();

        return view('home', ['publicNotices' => $publicNotices]);
    }
}






