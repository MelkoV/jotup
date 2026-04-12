<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\JwtServiceContract;
use App\Data\User\JwtTokenData;
use App\Enums\JwtTokenType;
use App\Enums\UserStatus;
use App\Exceptions\JwtException;
use Jotup\ExecutionScope\ExecutionScopeProviderInterface;
use Jotup\Http\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractHandleJwtToken implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtServiceContract $jwtService,
        private readonly UserRepositoryInterface $users,
        private readonly ?ExecutionScopeProviderInterface $executionScopeProvider = null,
    ) {
    }

    abstract protected function getJwtTokenTokenType(): JwtTokenType;

    /**
     * @throws JwtException
     */
    abstract protected function getToken(ServerRequestInterface $request): string;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $token = $this->getToken($request);
            $tokenData = $this->decodeToken($token);
            $user = $this->users->findById($tokenData->userId);

            if ($user->status !== UserStatus::Active) {
                throw new JwtException('User is inactive');
            }
        } catch (JwtException | \Throwable $e) {
            throw new HttpException(401, $e->getMessage() !== '' ? $e->getMessage() : 'Unauthorized', previous: $e);
        }

        $this->executionScopeProvider?->setUserId($user->id);

        return $handler->handle($request->withAttribute('user_id', $user->id));
    }

    /**
     * @throws JwtException
     */
    protected function decodeToken(string $token): JwtTokenData
    {
        $data = $this->jwtService->decode($token);
        if ($data->type !== $this->getJwtTokenTokenType()) {
            throw new JwtException('Invalid token type');
        }

        return $data;
    }
}
