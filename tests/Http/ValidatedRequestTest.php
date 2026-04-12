<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Api\Requests\List\FilteredListRequest;
use App\Http\Api\Requests\User\SignInRequest;
use Jotup\Http\Request\ServerRequest;
use Jotup\Container\Container;
use Jotup\Http\Dispatcher\ControllerDispatcher;
use Jotup\Http\Exception\ValidationException;
use Jotup\Http\Factory\HttpFactory;
use Jotup\Http\Response\Responder;
use PHPUnit\Framework\TestCase;

final class ValidatedRequestTest extends TestCase
{
    public function testDispatcherInjectsValidatedCustomRequestIntoAction(): void
    {
        $container = new Container();

        $factory = new HttpFactory();
        $dispatcher = new ControllerDispatcher($container, new Responder($factory, $factory));
        $request = $factory->createServerRequest('GET', '/api/v1/lists?text=milk&type=shopping&page=2')
            ->withQueryParams([
                'text' => 'milk',
                'type' => 'shopping',
                'page' => '2',
            ])
            ->withAttribute('user_id', '3d594650-b971-4368-b547-a57db6aa98cb');

        $response = $dispatcher->dispatch(ValidatedRequestController::class, 'signUp', [
            'request' => $request,
        ]);

        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('milk', $payload['text']);
        $this->assertSame('shopping', $payload['type']);
        $this->assertSame(2, $payload['page']);
        $this->assertSame(100, $payload['per_page']);
    }

    public function testCustomRequestThrowsValidationExceptionForInvalidPayload(): void
    {
        $container = new Container();

        $factory = new HttpFactory();
        $dispatcher = new ControllerDispatcher($container, new Responder($factory, $factory));
        $request = $factory->createServerRequest('GET', '/api/v1/lists?page=0&type=desktop')
            ->withQueryParams([
                'page' => '0',
                'type' => 'desktop',
            ]);

        $this->expectException(ValidationException::class);

        $dispatcher->dispatch(ValidatedRequestController::class, 'signUp', [
            'request' => $request,
        ]);
    }

    public function testValidationExceptionResponseContainsHumanReadableMessage(): void
    {
        $factory = new HttpFactory();
        $responder = new Responder($factory, $factory);

        $response = $responder->fromThrowable(new ValidationException([
            'email' => ['auth.failed'],
        ], 'auth.failed'));

        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('auth.failed', $payload['message']);
        $this->assertSame(['auth.failed'], $payload['errors']['email']);
        $this->assertArrayNotHasKey('error', $payload);
        $this->assertArrayNotHasKey('status', $payload);
    }

    public function testCustomRequestReadsJsonPayloadFromParsedBody(): void
    {
        $request = new ServerRequest(
            serverParams: ['CONTENT_TYPE' => 'application/json'],
            method: 'POST',
            uri: (new HttpFactory())->createUri('/api/v1/user/sign-in'),
            parsedBody: [
                'email' => 'anton@melkov.xyz',
                'password' => 'jndfuf88',
                'device' => 'web',
                'device_id' => 'c10d53e2-f51b-4bf9-8c25-9597cd2e78ab',
            ],
        );

        $validated = new SignInRequest($request);

        $this->assertSame('anton@melkov.xyz', $validated->validated()['email']);
        $this->assertSame('jndfuf88', $validated->validated()['password']);
        $this->assertSame('web', $validated->validated()['device']->value);
        $this->assertSame('c10d53e2-f51b-4bf9-8c25-9597cd2e78ab', $validated->validated()['device_id']);
    }
}

final class ValidatedRequestController
{
    public function signUp(FilteredListRequest $request): array
    {
        $data = $request->toData();

        return [
            'text' => $data->text,
            'type' => $data->type?->value,
            'page' => $data->page,
            'per_page' => $data->per_page,
        ];
    }
}
