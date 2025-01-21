<?php

namespace App\Repositories\Abstracts;

use App\Repositories\Interfaces\IRepository;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;

abstract class CrudRepository implements IRepository
{
    use ForwardsCalls;

    protected Model $model;
    protected Builder $query;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->query = $model->query();
    }

    public function clear(): void
    {
        $this->model = new $this->model();
        $this->query = $this->model->query();
    }

    public function __call($name, $arguments): mixed
    {
        $result = $this->forwardDecoratedCallTo($this->query, $name, $arguments);

        if (! $result instanceof IRepository) {
            $this->clear();
        }

        return $result;
    }
}
