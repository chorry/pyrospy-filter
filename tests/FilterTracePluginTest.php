<?php

declare(strict_types=1);

use Amp\ByteStream\Payload;
use PHPUnit\Framework\Attributes\DataProvider;
use Zoon\PyroSpy\Plugins\Filtering\FilterMatchingType;
use Zoon\PyroSpy\Plugins\Filtering\FilterRule;
use Zoon\PyroSpy\Plugins\Filtering\FilterTrace;
use Zoon\PyroSpy\Processor;
use Zoon\PyroSpy\SampleSenderInterface;

class FilterTracePluginTest extends PHPUnit\Framework\TestCase
{
    #[DataProvider('filteringProvider')]
    public function testFilter(int $samplesSent, array $rules, string $traces): void
    {
        $sender = $this->createMock(SampleSenderInterface::class);
        $sender->expects($this->exactly($samplesSent))->method('sendSample');

        $processor = new Processor(
            interval: 100500,
            batchLimit: PHP_INT_MAX,
            sender: $sender,
            plugins: [
                new FilterTrace($rules),
            ],
            sendSampleFutureLimit: 999999999,
            concurrentRequestLimit: 1,
            dataReader: new Payload($traces),
        );
        $processor->process();
    }

    public static function filteringProvider(): Generator
    {
        yield 'Filter by regexp' => [
            'samplesSent' => 0,
            'rules' => [
                new FilterRule(
                    FilterMatchingType::CHK_REGEXP->value,
                    FilterMatchingType::SRC_METHOD->value | FilterMatchingType::SRC_CALLEE->value,
                    '#pyrospy#',
                ),
            ],
            'traces' => <<<EOT
0 Symfony\Component\Console\Application::run /some/vendor/symfony/console/Application.php:146
1 <main> /pyrospy/pyrospy.php:1

0 SomeNamespace\pyrospy::run /vendor/some/path:146
1 <main> /app.php:1


EOT,
        ];

        yield 'Filter by strict equality' => [
            'samplesSent' => 1,
            'rules' => [
                new FilterRule(
                    FilterMatchingType::CHK_DIRECT->value,
                    FilterMatchingType::SRC_METHOD->value | FilterMatchingType::SRC_CALLEE->value,
                    'usleep',
                ),
            ],
            'traces' => <<<EOT
0 usleep <internal>:-1
1 <main> <internal>:-1

0 usleepy <internal>:-1
1 <main> <internal>:-1


EOT,
        ];

        yield 'Filter by tag value' => [
            'samplesSent' => 1,
            'rules' => [
                new FilterRule(
                    FilterMatchingType::CHK_DIRECT->value,
                    FilterMatchingType::SRC_TAG_NAME->value,
                    'filterMeByValue!',
                ),
            ],
            'traces' => <<<EOT
0 whatever <internal>:-1
1 <main> <internal>:-1
#glopeek server.HOSTNAME = hostOne
#glopeek server.CUSTOM_TAG = filterMeByValue!


EOT,
        ];

        yield 'Filter by tag name' => [
            'samplesSent' => 0,
            'rules' => [
                new FilterRule(
                    FilterMatchingType::CHK_DIRECT->value,
                    FilterMatchingType::SRC_TAG_NAME->value,
                    'server.FILTER_ME_BY_TAG_NAME',
                ),
            ],
            'traces' => <<<EOT
0 <main> <internal>:-1
#glopeek server.HOSTNAME = hostOne
#glopeek server.FILTER_ME_BY_TAG_NAME = whatever


EOT,
        ];

        yield 'Filter by line number' => [
            'samplesSent' => 0,
            'rules' => [
                new FilterRule(
                    FilterMatchingType::CHK_REGEXP->value,
                    FilterMatchingType::SRC_METHOD->value,
                    '#sleep#',
                    2,
                ),
            ],
            'traces' => <<<EOT
0 usleep <internal>:-1
1 usleepy <internal>:-1
2 <main> <internal>:-1


EOT,
        ];

        yield 'Filter by line number, negative' => [
            'samplesSent' => 1,
            'rules' => [
                new FilterRule(
                    FilterMatchingType::CHK_REGEXP->value,
                    FilterMatchingType::SRC_METHOD->value,
                    '#sleep#',
                    2,
                ),
            ],
            'traces' => <<<EOT
0 usleep <internal>:-1
1 array_map <internal>:-1
2 <main> <internal>:-1


EOT,
        ];
    }
}
