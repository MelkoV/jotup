<?php

declare(strict_types=1);

namespace Tests\Container;

use Jotup\Container\BindData;
use Jotup\Container\Container;
use Jotup\Container\Exceptions\InvalidArgumentException;
use Jotup\Container\Exceptions\RecursiveLinkException;
use Jotup\Http\Factory\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class ContainerBuilderTest extends TestCase
{
    public function testSingletonBindingReturnsSameInstanceAcrossAliasLookups(): void
    {
        $container = new Container();
        $sharedBinding = $container->bind(TestSharedService::class, TestSharedService::class, true);
        $container->bind('test.shared.alias', $sharedBinding);

        $this->assertSame(
            $container->get(TestSharedService::class),
            $container->get('test.shared.alias')
        );
    }

    public function testNonSingletonBindingCreatesNewInstanceOnEachGet(): void
    {
        $container = new Container();
        $container->bind(TestNeedsShared::class, TestNeedsShared::class);

        $this->assertNotSame(
            $container->get(TestNeedsShared::class),
            $container->get(TestNeedsShared::class)
        );
    }

    public function testRuntimeValuesOverrideBindingValuesAndKeepDefaults(): void
    {
        $container = new Container();
        $container->bind(TestConfigurableService::class, TestConfigurableService::class, values: ['name' => 'default']);

        $configurable = $container->make(TestConfigurableService::class, ['name' => 'override']);

        $this->assertSame('override', $configurable->name);
        $this->assertSame(5432, $configurable->port);
    }

    public function testObjectBindingReturnsExactSameInstance(): void
    {
        $container = new Container();
        $readyObject = new TestContractImpl();
        $container->bind(TestContract::class, $readyObject);

        $this->assertSame($readyObject, $container->get(TestContract::class));
    }

    public function testBoundInterfacesResolveThroughConstructorAutowiring(): void
    {
        $container = new Container();
        $readyObject = new TestContractImpl();
        $container->bind(TestContract::class, $readyObject);
        $container->bind(TestNeedsContract::class, TestNeedsContract::class);

        $needsContract = $container->get(TestNeedsContract::class);

        $this->assertSame($readyObject, $needsContract->contract);
    }

    public function testNestedClassOverrideBuildsRequestedInnerService(): void
    {
        $container = new Container();
        $container->bind(
            TestLoggerContract::class,
            singleton: true,
            values: [
                'class' => TestScopedLogger::class,
                'logger' => [
                    'class' => TestPlainLogger::class,
                ],
            ]
        );

        $scopedLogger = $container->get(TestLoggerContract::class);

        $this->assertInstanceOf(TestScopedLogger::class, $scopedLogger);
        $this->assertInstanceOf(TestPlainLogger::class, $scopedLogger->logger);
    }

    public function testAliasBindingResolvesToTargetConcrete(): void
    {
        $container = new Container();
        $aliasTarget = $container->bind('test.alias.target', TestNeedsShared::class, true);
        $container->bind('test.alias.middle', $aliasTarget);
        $container->bind('test.alias.top', $container->getBinding('test.alias.middle'));

        $this->assertInstanceOf(TestNeedsShared::class, $container->get('test.alias.top'));
    }

    public function testManualAliasChainResolvesToFinalSingletonTarget(): void
    {
        $container = new Container();
        $container->bind('test.chain.c', TestSharedService::class, true);
        $container->bind('test.chain.b', new BindData('test.chain.c', TestSharedService::class, true));
        $container->bind('test.chain.a', new BindData('test.chain.b', TestSharedService::class, true));

        $this->assertSame(
            $container->get('test.chain.c'),
            $container->get('test.chain.a')
        );
    }

    public function testAliasCycleThrowsRecursiveLinkException(): void
    {
        $container = new Container();
        $container->bind('test.loop.a', TestSharedService::class, true);
        $container->bind('test.loop.b', new BindData('test.loop.a', TestSharedService::class, true));
        $container->bind('test.loop.a', new BindData('test.loop.b', TestSharedService::class, true));

        $this->expectException(RecursiveLinkException::class);

        $container->get('test.loop.a');
    }

    public function testConstructorCycleThrowsRecursiveLinkException(): void
    {
        $container = new Container();

        $this->expectException(RecursiveLinkException::class);

        $container->make(TestCycleA::class);
    }

    public function testRequiredScalarThrowsInvalidArgumentException(): void
    {
        $container = new Container();

        $this->expectException(InvalidArgumentException::class);

        $container->make(TestNeedsScalar::class);
    }

    public function testMakeMethodInjectsNamedArgumentsRequestAndDependencies(): void
    {
        $container = new Container();
        $container->bind(TestSharedService::class, TestSharedService::class, true);
        $request = (new HttpFactory())->createServerRequest('GET', '/test');

        $result = $container->makeMethod(new TestMethodController(), 'show', [
            'id' => '42',
            'request' => $request,
        ]);

        $this->assertSame('42', $result['id']);
        $this->assertSame($request, $result['request']);
        $this->assertSame(TestSharedService::class, $result['shared']);
    }
}

final class TestSharedService
{
}

final class TestNeedsShared
{
    public function __construct(
        public TestSharedService $shared,
    ) {
    }
}

final class TestNeedsScalar
{
    public function __construct(
        public string $name,
    ) {
    }
}

final class TestConfigurableService
{
    public function __construct(
        public string $name,
        public int $port = 5432,
    ) {
    }
}

interface TestContract
{
}

final class TestContractImpl implements TestContract
{
}

final class TestNeedsContract
{
    public function __construct(
        public TestContract $contract,
    ) {
    }
}

final class TestCycleA
{
    public function __construct(
        public TestCycleB $b,
    ) {
    }
}

final class TestCycleB
{
    public function __construct(
        public TestCycleA $a,
    ) {
    }
}

interface TestLoggerContract
{
}

final class TestPlainLogger implements TestLoggerContract
{
}

final class TestScopedLogger implements TestLoggerContract
{
    public function __construct(
        public TestLoggerContract $logger,
    ) {
    }
}

final class TestMethodController
{
    public function show(string $id, ServerRequestInterface $request, TestSharedService $shared): array
    {
        return [
            'id' => $id,
            'request' => $request,
            'shared' => $shared::class,
        ];
    }
}
