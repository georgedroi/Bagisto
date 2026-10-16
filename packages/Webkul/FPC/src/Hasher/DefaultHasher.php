<?php

namespace Webkul\FPC\Hasher;

use Illuminate\Http\Request;
use Spatie\ResponseCache\Hasher\DefaultHasher as BaseDefaultHasher;

class DefaultHasher extends BaseDefaultHasher
{
    /**
     * Keep cached HTML within its code release so updated inline menus reach visitors.
     */
    public function getHashFor(Request $request): string
    {
        $releasePath = realpath(base_path()) ?: base_path();

        return parent::getHashFor($request).'-'.substr(hash('sha256', $releasePath), 0, 16);
    }

    /**
     * Get the hash for the given request.
     */
    protected function getNormalizedRequestUri(Request $request): string
    {
        if (
            $request->routeIs('shop.search.index')
            && is_string($request->query('query'))
        ) {
            $queryString = '?query='.$request->query('query');

            return $request->getBaseUrl().$request->getPathInfo().$queryString;
        }

        return $request->getBaseUrl().$request->getPathInfo();
    }

    /**
     * Get the cache name suffix for the given request.
     */
    protected function getCacheNameSuffix(Request $request): string
    {
        if ($request->attributes->has('responsecache.cacheNameSuffix')) {
            return $request->attributes->get('responsecache.cacheNameSuffix');
        }

        $cacheNameSuffix = core()->getCurrentChannel()->code
            .'-'.core()->getCurrentLocale()->code
            .'-'.core()->getCurrentCurrency()->code
            .'-'.$this->cacheProfile->useCacheNameSuffix($request);

        return $cacheNameSuffix;
    }
}
