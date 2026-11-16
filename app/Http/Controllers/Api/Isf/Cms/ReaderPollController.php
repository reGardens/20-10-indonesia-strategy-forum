<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\ReaderPollRequest;
use App\Models\Isf\ReaderPoll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Reader Poll (feature: reader_poll).
 */
class ReaderPollController extends CmsResourceController
{
    protected string $model = ReaderPoll::class;

    protected array $listColumns = [
        'id', 'question', 'slug', 'status', 'total_votes', 'is_featured',
        'start_date', 'end_date', 'order', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['question', 'description'];

    protected array $filterColumns = ['status', 'is_featured'];

    protected array $order = ['order' => 'asc', 'id' => 'desc'];

    public function show(ReaderPoll $readerPoll): JsonResponse
    {
        return $this->showModel($readerPoll);
    }

    public function store(ReaderPollRequest $request): JsonResponse
    {
        $validated = $request->validated();
        if (!isset($validated['total_votes'])) {
            $total = 0;
            if (isset($validated['options']) && is_array($validated['options'])) {
                foreach ($validated['options'] as $opt) {
                    $total += (int) ($opt['votes'] ?? 0);
                }
            }
            $validated['total_votes'] = $total;
        }

        return $this->storeModel($validated);
    }

    public function update(ReaderPollRequest $request, ReaderPoll $readerPoll): JsonResponse
    {
        $validated = $request->validated();
        if (isset($validated['options']) && is_array($validated['options'])) {
            $total = 0;
            foreach ($validated['options'] as $opt) {
                $total += (int) ($opt['votes'] ?? 0);
            }
            $validated['total_votes'] = $total;
        }

        return $this->updateModel($readerPoll, $validated);
    }

    public function destroy(ReaderPoll $readerPoll): Response
    {
        return $this->destroyModel($readerPoll);
    }
}

