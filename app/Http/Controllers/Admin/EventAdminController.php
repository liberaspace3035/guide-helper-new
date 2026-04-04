<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventAdminController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $events = Event::query()->orderBy('start_at', 'desc')->paginate(40);

        return view('admin.events.index', [
            'events' => $events,
            'categories' => Event::CATEGORIES,
        ]);
    }

    public function edit(int $id)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $event = Event::findOrFail($id);

        return view('admin.events.edit', [
            'event' => $event,
            'categories' => Event::CATEGORIES,
        ]);
    }

    public function update(Request $request, int $id)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $event = Event::findOrFail($id);

        $request->merge([
            'end_at' => $request->filled('end_at') ? $request->input('end_at') : null,
        ]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(Event::CATEGORIES)),
            'prefecture' => 'nullable|string|max:20',
            'place' => 'nullable|string|max:255',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'url' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:5000',
            'status' => 'required|string|in:' . Event::STATUS_PENDING . ',' . Event::STATUS_PUBLISHED . ',' . Event::STATUS_CANCELLED,
        ]);

        $event->update($validated);

        return redirect()->route('admin.events.index')->with('success', 'イベントを更新しました。');
    }

    public function destroy(int $id)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        Event::findOrFail($id)->delete();

        return redirect()->route('admin.events.index')->with('success', 'イベントを削除しました。');
    }

    public function bulkDestroy(Request $request)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:events,id'])['ids'];
        Event::whereIn('id', $ids)->delete();

        return redirect()->route('admin.events.index')->with('success', count($ids) . ' 件を削除しました。');
    }

    public function importCsv(Request $request)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt|max:5120']);

        $path = $request->file('csv_file')->getRealPath();
        $fh = fopen($path, 'r');
        if ($fh === false) {
            return back()->withErrors(['csv_file' => 'ファイルを開けませんでした。']);
        }

        $header = fgetcsv($fh);
        if (!$header) {
            fclose($fh);
            return back()->withErrors(['csv_file' => 'CSVが空です。']);
        }

        $normalize = function (string $h): string {
            return strtolower(trim(str_replace(["\xEF\xBB\xBF"], '', $h)));
        };
        $header = array_map($normalize, $header);
        $idx = array_flip($header);
        foreach (['title', 'category', 'start_at'] as $col) {
            if (!isset($idx[$col])) {
                fclose($fh);
                return back()->withErrors(['csv_file' => "必須列がありません: {$col}（先頭行に title,category,start_at,... を含めてください）"]);
            }
        }

        $adminId = Auth::id();
        $created = 0;
        $rowNum = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;
            if (count(array_filter($row, fn ($c) => $c !== null && trim((string) $c) !== '')) === 0) {
                continue;
            }
            $title = trim($row[$idx['title']] ?? '');
            $category = trim($row[$idx['category']] ?? '');
            $startAt = trim($row[$idx['start_at']] ?? '');
            if ($title === '' || $category === '' || $startAt === '') {
                continue;
            }
            if (!array_key_exists($category, Event::CATEGORIES)) {
                fclose($fh);
                return back()->withErrors(['csv_file' => "{$rowNum}行目: カテゴリキーが不正です（{$category}）。outing_experience などのキーを指定してください。"]);
            }
            try {
                $start = Carbon::parse($startAt);
            } catch (\Throwable $e) {
                fclose($fh);
                return back()->withErrors(['csv_file' => "{$rowNum}行目: 開始日時を解釈できません（{$startAt}）"]);
            }
            $endAt = isset($idx['end_at']) ? trim($row[$idx['end_at']] ?? '') : '';
            $end = null;
            if ($endAt !== '') {
                try {
                    $end = Carbon::parse($endAt);
                } catch (\Throwable $e) {
                    fclose($fh);
                    return back()->withErrors(['csv_file' => "{$rowNum}行目: 終了日時を解釈できません"]);
                }
            }

            Event::create([
                'title' => $title,
                'category' => $category,
                'prefecture' => isset($idx['prefecture']) ? trim($row[$idx['prefecture']] ?? '') ?: null : null,
                'place' => isset($idx['place']) ? trim($row[$idx['place']] ?? '') ?: null : null,
                'start_at' => $start,
                'end_at' => $end,
                'url' => isset($idx['url']) ? trim($row[$idx['url']] ?? '') ?: null : null,
                'description' => isset($idx['description']) ? trim($row[$idx['description']] ?? '') ?: null : null,
                'created_by' => $adminId,
                'submitter_name' => null,
                'submitter_email' => null,
                'email_verified_at' => now(),
                'verification_token' => null,
                'verification_token_expires_at' => null,
                'status' => Event::STATUS_PUBLISHED,
            ]);
            $created++;
        }
        fclose($fh);

        return redirect()->route('admin.events.index')->with('success', "CSVから {$created} 件を登録しました。");
    }
}
