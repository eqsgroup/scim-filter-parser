<?php

namespace Tests\Tmilos\ScimFilterParser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tmilos\ScimFilterParser\Error\FilterException;
use Tmilos\ScimFilterParser\Mode;
use Tmilos\ScimFilterParser\Parser;
use Tmilos\ScimFilterParser\Version;

class ParserFilterModeTest extends TestCase
{
    public static function parser_provider_v2()
    {
        return [
            [
                'userName eq "bjensen"',
                ['ComparisonExpression' => 'userName eq bjensen'],
            ],

            [
                'name.familyName co "O\'Malley"',
                ['ComparisonExpression' => 'name.familyName co O\'Malley'],
            ],

            [
                'userName sw "J"',
                ['ComparisonExpression' => 'userName sw J'],
            ],

            [
                'urn:ietf:params:scim:schemas:core:2.0:User:userName sw "J"',
                ['ComparisonExpression' => 'urn:ietf:params:scim:schemas:core:2.0:User : userName sw J'],
            ],
            [
                'urn:ietf:params:scim:schemas:core:2.0:User:roles[primary eq "True"]',
                [
                    'ValuePath' => [
                        ['AttributePath' => 'urn:ietf:params:scim:schemas:core:2.0:User : roles'],
                        ['ComparisonExpression' => 'primary eq 1'],
                    ],
                ],
            ],

            [
                'title pr',
                ['ComparisonExpression' => 'title pr'],
            ],

            [
                'meta.lastModified gt "2011-05-13T04:42:34Z"',
                ['ComparisonExpression' => 'meta.lastModified gt 2011-05-13T04:42:34Z'],
            ],

            [
                'title pr and userType eq "Employee"',
                [
                    'Conjunction' => [
                        ['ComparisonExpression' => 'title pr'],
                        ['ComparisonExpression' => 'userType eq Employee'],
                    ]
                ]
            ],

            [
                'title pr or userType eq "Intern"',
                [
                    'Disjunction' => [
                        ['ComparisonExpression' => 'title pr'],
                        ['ComparisonExpression' => 'userType eq Intern'],
                    ]
                ]
            ],

            [
                'schemas eq "urn:ietf:params:scim:schemas:extension:enterprise:2.0:User"',
                ['ComparisonExpression' => 'schemas eq urn:ietf:params:scim:schemas:extension:enterprise:2.0:User'],
            ],

            [
                'userType eq "Employee" and (emails co "example.com" or emails.value co "example.org")',
                [
                    'Conjunction' => [
                        ['ComparisonExpression' => 'userType eq Employee'],
                        [
                            'Disjunction' => [
                                ['ComparisonExpression' => 'emails co example.com'],
                                ['ComparisonExpression' => 'emails.value co example.org'],
                            ]
                        ]
                    ]
                ]
            ],

            [
                'userType ne "Employee" and not (emails co "example.com" or emails.value co "example.org")',
                [
                    'Conjunction' => [
                        ['ComparisonExpression' => 'userType ne Employee'],
                        [
                            'Negation' => [
                                'Disjunction' => [
                                    ['ComparisonExpression' => 'emails co example.com'],
                                    ['ComparisonExpression' => 'emails.value co example.org'],
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            [
                'userType eq "Employee" and (emails.type eq "work")',
                [
                    'Conjunction' => [
                        ['ComparisonExpression' => 'userType eq Employee'],
                        ['ComparisonExpression' => 'emails.type eq work']
                    ]
                ]
            ],

            [
                'emails[type eq "work"]',
                [
                    'ValuePath' => [
                        ['AttributePath' => 'emails'],
                        ['ComparisonExpression' => 'type eq work'],
                    ],
                ]
            ],

            [
                'userType eq "Employee" and emails[type eq "work" and value co "@example.com"]',
                [
                    'Conjunction' => [
                        ['ComparisonExpression' => 'userType eq Employee'],
                        [
                            'ValuePath' => [
                                ['AttributePath' => 'emails'],
                                [
                                    'Conjunction' => [
                                        ['ComparisonExpression' => 'type eq work'],
                                        ['ComparisonExpression' => 'value co @example.com'],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            [
                'emails[type eq "work" and value co "@example.com"] or ims[type eq "xmpp" and value co "@foo.com"]',
                [
                    'Disjunction' => [
                        [
                            'ValuePath' => [
                                ['AttributePath' => 'emails'],
                                [
                                    'Conjunction' => [
                                        ['ComparisonExpression' => 'type eq work'],
                                        ['ComparisonExpression' => 'value co @example.com'],
                                    ]
                                ]
                            ]
                        ],
                        [
                            'ValuePath' => [
                                ['AttributePath' => 'ims'],
                                [
                                    'Conjunction' => [
                                        ['ComparisonExpression' => 'type eq xmpp'],
                                        ['ComparisonExpression' => 'value co @foo.com'],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            [
                'username eq "john" and name sw "mike"',
                [
                    'Conjunction' => [
                        ['ComparisonExpression' => 'username eq john'],
                        ['ComparisonExpression' => 'name sw mike']
                    ]
                ]
            ],

            [
                'username eq "john" or name sw "mike"',
                [
                    'Disjunction' => [
                        ['ComparisonExpression' => 'username eq john'],
                        ['ComparisonExpression' => 'name sw mike']
                    ]
                ]
            ],

            [
                'username eq "john" or name sw "mike" and id ew "123"',
                [
                    'Disjunction' => [
                        ['ComparisonExpression' => 'username eq john'],
                        [
                            'Conjunction' => [
                                ['ComparisonExpression' => 'name sw mike'],
                                ['ComparisonExpression' => 'id ew 123'],
                            ]
                        ]
                    ]
                ]
            ],

            [
                'username eq "john" and (name sw "mike" or id ew "123")',
                [
                    'Conjunction' => [
                        ['ComparisonExpression' => 'username eq john'],
                        [
                            'Disjunction' => [
                                ['ComparisonExpression' => 'name sw mike'],
                                ['ComparisonExpression' => 'id ew 123'],
                            ]
                        ]
                    ]
                ]
            ],

            [
                'username eq "john" and not (name sw "mike" or id ew "123")',
                [
                    'Conjunction' => [
                        ['ComparisonExpression' => 'username eq john'],
                        [
                            'Negation' => [
                                'Disjunction' => [
                                    ['ComparisonExpression' => 'name sw mike'],
                                    ['ComparisonExpression' => 'id ew 123'],
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    #[DataProvider('parser_provider_v2')]
    public function test_parser_v2($filterString, array $expectedDump)
    {
        $parser = $this->getParser();
        $node = $parser->parse($filterString);
        $this->assertEquals($expectedDump, $node->dump(), sprintf("\n\n%s\n%s\n\n", $filterString, json_encode($node->dump(), JSON_PRETTY_PRINT)));
    }

    public static function error_provider_v2()
    {
        return [
            ['none a valid filter', "[Syntax Error] line 0, col 5: Error: Expected comparision operator, got 'a'"],
            ['username xx "mike"', "[Syntax Error] line 0, col 9: Error: Expected comparision operator, got 'xx'"],
            ['username eq', "[Syntax Error] line 0, col 9: Error: Expected SP, got end of string."],
            ['username eq ', "[Syntax Error] line 0, col 11: Error: Expected comparison value, got end of string."],
            ['emails[type[value eq "1"]]', "[Syntax Error] line 0, col 11: Error: Expected SP, got '['"],
            ['members.value', '[Syntax Error] line 0, col 8: Error: Expected SP, got end of string.'],
        ];
    }

    #[DataProvider('error_provider_v2')]
    public function test_error_v2($filterString, $expectedMessage, $expectedException = FilterException::class)
    {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        $parser = $this->getParser();
        $parser->parse($filterString);
    }

    public function test_v1_no_value_path()
    {
        $this->expectException(FilterException::class);
        $this->expectExceptionMessage("[Syntax Error] line 0, col 6: Error: Expected SP, got '['");
        $this->getParser(Version::V1())->parse('emails[type eq "work"]');
    }

    public function test_throws_error_for_value_path_with_attribute_path_in_filter_mode()
    {
        $this->expectException(FilterException::class);
        $this->expectExceptionMessage("[Syntax Error] line 0, col 25: Error: Expected end of input, got '.'");

        $this->getParser()->parse('addresses[type eq "work"].streetAddress co "main"');
    }

    /**
     * @param Version $version
     *
     * @return Parser
     */
    private function getParser(?Version $version = null)
    {
        $version = $version ?: Version::V2();

        return new Parser(Mode::FILTER(), $version);
    }
}
