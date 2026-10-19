<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\SearchQuery;
use PHPUnit\Framework\TestCase;

class SearchQueryTest extends TestCase
{
    public function test_boolean_operators_in_user_input_are_stripped_and_rebuilt_by_us(): void
    {
        $query = SearchQuery::fromUserInput('+john -acme* "smith" (x) ~sales');

        $this->assertSame(['john', 'acme', 'smith', 'sales'], $query->words);
        $this->assertSame('+john* +acme* +smith* +sales*', $query->toBooleanMode());
    }

    public function test_emails_are_split_the_way_the_index_splits_them(): void
    {
        $query = SearchQuery::fromUserInput('john@acme.com');

        $this->assertSame(['john', 'acme', 'com'], $query->words);
    }

    public function test_one_character_words_are_dropped(): void
    {
        $this->assertSame(['john'], SearchQuery::fromUserInput('a john x')->words);
    }

    public function test_input_with_only_operators_is_empty(): void
    {
        $query = SearchQuery::fromUserInput('+ - * " ( )');

        $this->assertTrue($query->isEmpty());
        $this->assertSame('', $query->toBooleanMode());
    }

    public function test_letters_from_any_language_are_kept(): void
    {
        $this->assertSame(['josé', 'അമ്മ'], SearchQuery::fromUserInput('josé അമ്മ')->words);
    }

    public function test_the_number_of_words_is_capped(): void
    {
        $query = SearchQuery::fromUserInput('aa bb cc dd ee ff gg hh ii jj');

        $this->assertCount(8, $query->words);
    }
}
