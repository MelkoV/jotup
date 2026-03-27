<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers;

use App\Repositories\UserRepository;

class TestController
{
    public function index()
    {
        $repository = new UserRepository();
    }
}