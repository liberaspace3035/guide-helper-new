<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\MatchingService;

class MatchingController extends Controller
{
    protected $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    public function show($id)
    {
        try {
            $matchings = $this->matchingService->getUserMatchings(Auth::id());
            $matchingId = (int) $id;
            $matching = collect($matchings)->firstWhere('id', $matchingId);
            
            if (!$matching) {
                \Log::warning('MatchingController::show - マッチングが見つかりません', [
                    'matching_id' => $matchingId,
                    'user_id' => Auth::id(),
                    'available_matchings' => collect($matchings)->pluck('id')->toArray(),
                ]);
                return redirect()->route('dashboard')
                    ->with('error', 'マッチングが見つかりません');
            }
            
            return view('matchings.show', [
                'id' => $id,
                'matching' => $matching,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', $e->getMessage());
        }
    }
}

