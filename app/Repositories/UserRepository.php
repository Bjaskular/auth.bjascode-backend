<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Abstracts\CrudRepository;
use App\Repositories\Interfaces\IUserRepository;

class UserRepository extends CrudRepository implements IUserRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
