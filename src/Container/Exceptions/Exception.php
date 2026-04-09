<?php

declare(strict_types=1);

namespace Jotup\Container\Exceptions;

use Jotup\Exceptions\CoreException;
use Psr\Container\ContainerExceptionInterface;

class Exception extends CoreException implements ContainerExceptionInterface
{

}