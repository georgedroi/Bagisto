<?php

namespace App\Http\Controllers;

use App\Repositories\StorefrontPageRepository;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * Create the storefront journal controller.
     */
    public function __construct(protected StorefrontPageRepository $pageRepository) {}

    /**
     * Render genuine journal pages maintained through the CMS administration.
     */
    public function index(): View
    {
        return view('shop::blog.index', [
            'pages' => $this->pageRepository->getJournalPages(
                core()->getCurrentChannel()->id,
                app()->getLocale(),
            ),
        ]);
    }
}
