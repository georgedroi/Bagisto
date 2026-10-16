<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Webkul\CMS\Repositories\PageRepository;

class StorefrontPageRepository extends PageRepository
{
    /**
     * List journal pages assigned to the current storefront and locale.
     */
    public function getJournalPages(int $channelId, string $locale): LengthAwarePaginator
    {
        return $this->model
            ->whereHas('channels', fn ($query) => $query->where('channels.id', $channelId))
            ->whereHas('translations', fn ($query) => $query
                ->where('locale', $locale)
                ->where('url_key', 'like', 'blog-%'))
            ->latest('created_at')
            ->paginate(9);
    }
}
