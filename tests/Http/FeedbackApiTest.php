<?php

declare(strict_types=1);

namespace Tests\Http;

use Tests\Support\ApiTestCase;

final class FeedbackApiTest extends ApiTestCase
{
    public function testCreateFeedbackStoresMessageAndReturnsCreated(): void
    {
        $response = $this->postJson('/api/v1/feedback', [
            'name' => 'Anton',
            'email' => 'anton@example.com',
            'message' => 'Please add dark mode to the mobile app.',
        ]);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(['success' => true], $this->decodeJson($response));

        $row = $this->db->query()
            ->from('{{%feedback}}')
            ->where(['email' => 'anton@example.com'])
            ->one();

        self::assertIsArray($row);
        self::assertSame('Anton', $row['name']);
        self::assertSame('anton@example.com', $row['email']);
        self::assertSame('Please add dark mode to the mobile app.', $row['message']);
        self::assertNotEmpty($row['id']);
        self::assertNotEmpty($row['date']);
    }

    public function testCreateFeedbackValidatesEmail(): void
    {
        $response = $this->postJson('/api/v1/feedback', [
            'name' => 'Anton',
            'email' => 'not-an-email',
            'message' => 'Hello',
        ]);

        self::assertSame(422, $response->getStatusCode());

        $payload = $this->decodeJson($response);

        self::assertArrayHasKey('message', $payload);
        self::assertArrayHasKey('errors', $payload);
        self::assertArrayHasKey('email', $payload['errors']);
    }
}
