<?php

namespace App\Jobs;

use App\Models\Request;
use App\Services\RequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyGuidesOfNewRequestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int[]
     */
    protected array $requestIds;

    public function __construct(array $requestIds)
    {
        $this->requestIds = $requestIds;
    }

    public function handle(RequestService $requestService): void
    {
        if (empty($this->requestIds)) {
            return;
        }
        $requests = Request::whereIn('id', $this->requestIds)->get()->all();
        if (empty($requests)) {
            return;
        }
        $requestService->notifyGuidesForRequests($requests);
    }
}
