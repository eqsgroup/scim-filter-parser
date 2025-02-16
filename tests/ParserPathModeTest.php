<?php

namespace Tests\Tmilos\ScimFilterParser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tmilos\ScimFilterParser\Ast\Path;
use Tmilos\ScimFilterParser\Error\FilterException;
use Tmilos\ScimFilterParser\Mode;
use Tmilos\ScimFilterParser\Parser;

class ParserPathModeTest extends TestCase
{
    public static function valid_path_provider()
    {
        return [
            [
                'members',
                ['Path' => ['AttributePath' => 'members']],
            ],
            [
                'name.familyName',
                ['Path' => ['AttributePath' => 'name.familyName']],
            ],
            [
                'addresses[type eq "work"]',
                [
                    'Path' => [
                        'ValuePath' => [
                            ['AttributePath' => 'addresses'],
                            ['ComparisonExpression' => 'type eq work']
                        ]
                    ]
                ],
            ],
            [
                'members[value eq "2819c223-7f76-453a-919d-413861904646"]',
                [
                    'Path' => [
                        'ValuePath' => [
                            ['AttributePath' => 'members'],
                            ['ComparisonExpression' => 'value eq 2819c223-7f76-453a-919d-413861904646']
                        ]
                    ]
                ],
            ],
            [
                'members[value eq "2819c223-7f76-453a-919d-413861904646"].displayName',
                [
                    'ValuePath' => [
                        ['AttributePath' => 'members'],
                        ['ComparisonExpression' => 'value eq 2819c223-7f76-453a-919d-413861904646']
                    ],
                    'AttributePath' => 'displayName',
                ]
            ],
            [
                'foo.bar.baz[value eq "2819c223-7f76-453a-919d-413861904646"].displayName',
                [
                    'ValuePath' => [
                        ['AttributePath' => 'foo.bar.baz'],
                        ['ComparisonExpression' => 'value eq 2819c223-7f76-453a-919d-413861904646']
                    ],
                    'AttributePath' => 'displayName',
                ]
            ],
        ];
    }

    #[DataProvider('valid_path_provider')]
    public function test_valid_path($pathString, array $expectedDump)
    {
        $parser = $this->getParser();
        $node = $parser->parse($pathString);
        $this->assertEquals($expectedDump, $node->dump(), sprintf("\n\n%s\n%s\n\n", $pathString, json_encode($node->dump(), JSON_PRETTY_PRINT)));
        $this->assertInstanceOf(Path::class, $node);
    }

    #[TestWith(['roles[primary eq "True"]', true])]
    #[TestWith(['roles[primary eq "true"]', true])]
    #[TestWith(['roles[primary eq "False"]', false])]
    #[TestWith(['roles[primary eq "false"]', false])]
    public function test_type_juggling(string $filter, bool $expected)
    {
        $this->assertSame($expected, $this->getParser()->parse($filter)->valuePath->getFilter()->compareValue);
    }

    public static function invalid_path_provider()
    {
        return [
            ['userName eq "bjensen"', "[Syntax Error] line 0, col 8: Error: Expected end of input, got ' '"],
            ['title pr', "[Syntax Error] line 0, col 5: Error: Expected end of input, got ' '"],
            ['addresses[type eq "work"] and members', "[Syntax Error] line 0, col 25: Error: Expected end of input, got ' '"],
        ];
    }

    #[DataProvider('invalid_path_provider')]
    public function test_invalid_path($pathString, $expectedMessage, $expectedException = FilterException::class)
    {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        $parser = $this->getParser();
        $parser->parse($pathString);
    }

    /**
     * @return Parser
     */
    private function getParser()
    {
        return new Parser(Mode::PATH());
    }
}
