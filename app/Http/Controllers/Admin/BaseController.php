<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;

class BaseController extends Controller
{
    /**
     * BaseController constructor.
     */
    public function __construct(private ViewFactory $view)
    {
    }

    /**
     * Return the admin index view.
     */
    public function index(): View
    {
        return $this->view->make('admin.index');
    }
}
