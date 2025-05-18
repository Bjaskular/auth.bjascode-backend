<?php

namespace App\Http\Controllers;

class AngularAppController extends Controller
{
    private readonly string $path;

    public function __construct()
    {
        $this->path = public_path(). '/angular/index.html';
    }

    public function index()
    {
        $indexHtml = '';
        if (file_exists($this->path)) {
            $indexHtml = file_get_contents($this->path);
        }

        return $indexHtml;
    }
}
