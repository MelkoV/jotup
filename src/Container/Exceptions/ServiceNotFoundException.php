<?php

declare(strict_types=1);

namespace Jotup\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends Exception implements NotFoundExceptionInterface
{

}