<?php

namespace App\Repositories;

use App\Models\Application;
use App\Repositories\Abstracts\CrudRepository;
use App\Repositories\Interfaces\IApplicationRepository;

class ApplicationRepository extends CrudRepository implements IApplicationRepository
{
    public function __construct(Application $model)
    {
        parent::__construct($model);
    }
}
