<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\PublicQuoteRequest;
use App\Models\Isf\PublicQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Kutipan Publik (feature: kutipan_publik).
 */
class PublicQuoteController extends CmsResourceController
{
    protected string $model = PublicQuote::class;

    protected array $listColumns = [
        'id', 'quote', 'author_name', 'author_title', 'author_type', 'tokoh_id',
        'topic', 'source_title', 'source_url', 'quote_date', 'order', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['quote', 'author_name', 'topic', 'source_title'];

    protected array $filterColumns = ['author_type', 'status'];

    protected array $order = ['order' => 'asc', 'quote_date' => 'desc', 'id' => 'desc'];

    public function show(PublicQuote $kutipanPublik): JsonResponse
    {
        return $this->showModel($kutipanPublik);
    }

    public function store(PublicQuoteRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(PublicQuoteRequest $request, PublicQuote $kutipanPublik): JsonResponse
    {
        return $this->updateModel($kutipanPublik, $request->validated());
    }

    public function destroy(PublicQuote $kutipanPublik): Response
    {
        return $this->destroyModel($kutipanPublik);
    }
}

