<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SitePublicNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SitePublicNoticeAdminController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $notices = SitePublicNotice::orderBy('published_at', 'desc')->paginate(30);

        return view('admin.public-notices.index', [
            'notices' => $notices,
            'categories' => SitePublicNotice::CATEGORIES,
        ]);
    }

    public function create()
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return view('admin.public-notices.form', [
            'notice' => null,
            'categories' => SitePublicNotice::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $data = $this->validated($request);
        SitePublicNotice::create($data);

        return redirect()->route('admin.public-notices.index')->with('success', 'お知らせを作成しました。');
    }

    public function edit(int $id)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $notice = SitePublicNotice::findOrFail($id);

        return view('admin.public-notices.form', [
            'notice' => $notice,
            'categories' => SitePublicNotice::CATEGORIES,
        ]);
    }

    public function update(Request $request, int $id)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $notice = SitePublicNotice::findOrFail($id);
        $notice->update($this->validated($request));

        return redirect()->route('admin.public-notices.index')->with('success', 'お知らせを更新しました。');
    }

    public function destroy(int $id)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        SitePublicNotice::findOrFail($id)->delete();

        return redirect()->route('admin.public-notices.index')->with('success', 'お知らせを削除しました。');
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'category' => 'required|string|in:' . implode(',', array_keys(SitePublicNotice::CATEGORIES)),
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:20000',
            'detail_url' => 'nullable|url|max:2048',
            'published_at' => 'required|date',
        ]);
        $v['is_visible'] = $request->boolean('is_visible');

        return $v;
    }
}
