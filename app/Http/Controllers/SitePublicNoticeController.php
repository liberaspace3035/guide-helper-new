<?php

namespace App\Http\Controllers;

use App\Models\SitePublicNotice;
use Illuminate\Http\Request;

class SitePublicNoticeController extends Controller
{
    public function index(Request $request)
    {
        $query = SitePublicNotice::visible()->orderBy('published_at', 'desc');
        if ($request->filled('category') && array_key_exists($request->query('category'), SitePublicNotice::CATEGORIES)) {
            $query->where('category', $request->query('category'));
        }
        $notices = $query->paginate(20);

        return view('public-notices.index', [
            'notices' => $notices,
            'categories' => SitePublicNotice::CATEGORIES,
        ]);
    }
}
