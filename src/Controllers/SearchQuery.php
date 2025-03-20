<?php

namespace Jdkweb\Search\Controllers;

use Illuminate\Support\Str;

class SearchQuery
{
    /**
     * Query from user
     * @var string|null
     */
    protected ?string $searchQuery = null;

    /**
     * Cleaned up searchQuery
     * @var string|null
     */
    protected ?string $searchTerm = null;

    /**
     * searchQuery splitted up in separate words
     * @var array|null
     */
    protected ?array $terms = null;

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Set search query
     * @param  string|null  $value
     * @return void
     */
    public function setSearchQuery(?string $value)
    {
        $this->searchQuery = urldecode($value);
        $this->searchQuery = preg_replace("/&#?[a-z0-9]{2,8};/i","",htmlentities($this->searchQuery));
    }

    //------------------------------------------------------------------------------------------------------------------

    public function handleSearchQuery()
    {
        $this->setSearchTerm();
        $this->buildSearchTerms();
        $this->filterWords();
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Remove filter words form search terms
     * @return void
     */
    protected function filterWords(): void
    {
        if($this->terms === null) return;

        $words = explode(",", config('search.filter_words.nl'));
        $words = array_map(fn($word) => trim($word), $words);

        $this->terms = array_filter($this->terms, function ($term) use ($words) {
            return !in_array($term, $words);
        });

    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Build search terms
     * @return void
     */
    private function buildSearchTerms(): void
    {
        $this->terms = [];

        $singularTerm = Str::singular($this->searchTerm);
        if ($singularTerm != $this->searchTerm) {
            $this->terms[] = $singularTerm;
        }

        foreach (['\s', '\s|-'] as $split) {
            foreach (preg_split("/".$split."/", $this->searchTerm) as $word) {
                if (!$word || in_array($word, $this->terms)) {
                    continue;
                }
                $word = trim($word);
                $this->terms[] = $word;
                $singularWord = Str::singular($word);
                if ($singularWord != $word) {
                    $this->terms[] = $singularWord;
                }
            }
        }
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * String to lower and removes all extraneous white space from a string
     * @return void
     */
    protected function setSearchTerm():void
    {
        $this->searchTerm = Str::squish(
            Str::lower(
                Str::of($this->searchQuery)->stripTags()
            )
        );
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Get complete search term
     * @return array|null
     */
    public function getTerm(): ?string
    {
        return $this->searchTerm;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Get terms, explode the complete search term
     * @return array|null
     */
    public function getTerms(): ?array
    {
        return $this->terms;
    }
}
