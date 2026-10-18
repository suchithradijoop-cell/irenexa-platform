<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A cleaned-up search phrase, safe to hand to MySQL BOOLEAN MODE.
 *
 * Boolean mode treats + - * " ( ) < > ~ @ as operators. If raw user input
 * reached MATCH ... AGAINST, a visitor could change what the query means
 * (or trigger a syntax error). We never pass user text through: we keep only
 * letters and digits, split into words, and build the operators ourselves.
 */
final readonly class SearchQuery
{
    /** Words shorter than this are noise ("a", "x") and are dropped. */
    private const MIN_WORD_LENGTH = 2;

    /** More words than this add cost but not meaning. */
    private const MAX_WORDS = 8;

    /**
     * @param  list<string>  $words
     */
    private function __construct(public array $words) {}

    public static function fromUserInput(string $input): self
    {
        // \p{L} = any letter in any language, \p{N} = any digit. Everything
        // else (operators, punctuation, '@', '.') becomes a separator, which
        // is also how InnoDB splits "john@acme.com" into john / acme / com.
        $words = preg_split('/[^\p{L}\p{N}]+/u', $input, -1, PREG_SPLIT_NO_EMPTY);

        $words = array_values(array_filter(
            $words === false ? [] : $words,
            static fn (string $word): bool => mb_strlen($word) >= self::MIN_WORD_LENGTH,
        ));

        return new self(array_slice($words, 0, self::MAX_WORDS));
    }

    public function isEmpty(): bool
    {
        return $this->words === [];
    }

    /**
     * "john acme" becomes "+john* +acme*": every word is REQUIRED (+) and
     * matches as a prefix (*), so "joh ac" already finds John at Acme while
     * the user is still typing.
     */
    public function toBooleanMode(): string
    {
        return implode(' ', array_map(
            static fn (string $word): string => '+'.$word.'*',
            $this->words,
        ));
    }
}
