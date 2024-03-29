<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Filter;

use DanBettles\Defence\Envelope;
use DanBettles\Defence\Filter\AbstractFilter;
use DanBettles\Defence\Filter\FilterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

use function gethostname;

use const true;

class AbstractFilterTest extends TestCase
{
    public function testIsAbstract(): void
    {
        $reflectionClass = new ReflectionClass(AbstractFilter::class);

        $this->assertTrue($reflectionClass->isAbstract());
    }

    public function testImplementsFilterinterface(): void
    {
        $reflectionClass = new ReflectionClass(AbstractFilter::class);

        $this->assertTrue($reflectionClass->implementsInterface(FilterInterface::class));
    }

    public function testConstructor(): void
    {
        $filterMock = $this->getMockForAbstractClass(AbstractFilter::class, [
            'options' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ]);

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
            'foo' => 'bar',
            'baz' => 'qux',
        ], $filterMock->getOptions());
    }

    public function testOptionsAreOptional(): void
    {
        $filterMock = $this->getMockForAbstractClass(AbstractFilter::class);

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
        ], $filterMock->getOptions());
    }

    public function testAddlogentryIsProtected(): void
    {
        $reflectionMethod = new ReflectionMethod(AbstractFilter::class, 'envelopeAddLogEntry');

        $this->assertTrue($reflectionMethod->isProtected());
    }

    /** @return array<mixed[]> */
    public function providesFiltersAndTheLogEntriesTheyAdd(): array
    {
        $returnValue = [];

        $expectedLogLevel = LogLevel::EMERGENCY;
        $filterOptions = ['log_level' => $expectedLogLevel];

        $returnValue[] = [
            'expectedLogLevel' => $expectedLogLevel,
            'expectedLogMessage' => 'System is unusable.',
            'filter' => new class ($filterOptions) extends AbstractFilter {
                public function __invoke(Envelope $envelope): bool
                {
                    $this->envelopeAddLogEntry($envelope, 'System is unusable.');

                    return true;
                }
            },
        ];

        $returnValue[] = [
            'expectedLogLevel' => LogLevel::WARNING,
            'expectedLogMessage' => 'Exceptional occurrence that is not an error.',
            'filter' => new class extends AbstractFilter {
                public function __invoke(Envelope $envelope): bool
                {
                    $this->envelopeAddLogEntry($envelope, 'Exceptional occurrence that is not an error.');

                    return true;
                }
            },
        ];

        return $returnValue;
    }

    /** @dataProvider providesFiltersAndTheLogEntriesTheyAdd */
    public function testAddlogentryAddsALogEntryToTheLogger(
        string $expectedLogLevel,
        string $expectedLogMessage,
        AbstractFilter $filter
    ): void {
        $minimalRequest = new Request([], [], [], [], [], [
            'HTTP_HOST' => 'foo.com',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'bar=baz&qux=quux',
        ]);

        $loggerMock = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock()
        ;

        $loggerMock
            ->expects($this->once())
            ->method('log')
            ->with($expectedLogLevel, $expectedLogMessage, [
                'host_name' => gethostname(),
                'request_method' => 'GET',
                'uri' => 'http://foo.com/?bar=baz&qux=quux',
            ])
        ;

        /** @var LoggerInterface $loggerMock */

        $filter(new Envelope($minimalRequest, $loggerMock));
    }
}
