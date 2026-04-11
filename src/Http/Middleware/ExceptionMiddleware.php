<?php

declare(strict_types=1);

namespace Jotup\Http\Middleware;

use Jotup\Http\Exception\HttpException;
use Jotup\Http\Handler\ExceptionHandler;
use Jotup\Http\Response\Responder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

readonly class ExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
//        private readonly ExceptionHandler $fallbackHandler,
        private Responder       $responder,
        private LoggerInterface $logger,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $throwable) {
            $this->logger->error('Unhandled exception while processing request', [
                'exception' => $throwable,
                'exception_class' => $throwable::class,
                'exception_message' => $throwable->getMessage(),
                'request_method' => $request->getMethod(),
                'request_uri' => (string) $request->getUri(),
            ]);

//            if ($throwable instanceof HttpException) {
                return $this->responder->fromThrowable($throwable);
//            }

//            return $this->fallbackHandler->handle(
//                $request->withAttribute('exception', $throwable)
//            );
        }
    }
}
