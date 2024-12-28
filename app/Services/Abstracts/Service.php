<?php

namespace App\Services\Abstracts;

use App\Repositories\Interfaces\IRepository;
use App\Services\Interfaces\IService;
use Illuminate\Http\Request;

abstract class Service implements IService
{
    protected readonly Request $request;
    protected readonly IRepository $repository;

    public function __construct(Request $request, IRepository $repository)
    {
        $this->request = $request;
        $this->repository = $repository;
    }
}
