<?php

declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Filter;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Filter\AbstractFilter;
use ThreeStreams\Defence\Filter\FilterInterface;

class AbstractFilterTest extends TestCase
{
    public function testIsAbstract()
    {
        $reflectionClass = new ReflectionClass(AbstractFilter::class);

        $this->assertTrue($reflectionClass->isAbstract());
    }

    public function testImplementsFilterinterface()
    {
        $reflectionClass = new ReflectionClass(AbstractFilter::class);

        $this->assertTrue($reflectionClass->implementsInterface(FilterInterface::class));
    }

    public function testConstructor()
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

    public function testOptionsAreOptional()
    {
        $filterMock = $this->getMockForAbstractClass(AbstractFilter::class);

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
        ], $filterMock->getOptions());
    }

    public function testAddlogentryIsProtected()
    {
        $reflectionMethod = new ReflectionMethod(AbstractFilter::class, 'envelopeAddLogEntry');

        $this->assertTrue($reflectionMethod->isProtected());
    }

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

    /**
     * @dataProvider providesFiltersAndTheLogEntriesTheyAdd
     */
    public function testAddlogentryAddsALogEntryToTheLogger($expectedLogLevel, $expectedLogMessage, $filter)
    {
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

        $filter(new Envelope($minimalRequest, $loggerMock));
    }
}
