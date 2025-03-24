<?php

namespace Jdkweb\Search;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jdkweb\Search\Controllers\SearchQuery;
use Jdkweb\Search\Controllers\SearchModel;

class Search
{
    /**
     * Object to handle String to search on
     * @var SearchQuery
     */
    public ?SearchQuery $searchQuery = null;

    /**
     * (Configuration) Search Preset
     * @var string|null
     */
    protected ?string $presetSearchQuery = null;

    /**
     * Split searchQuery into array
     * First is complete searchQuery
     * @var array
     */
    public array $terms = [];

    /**
     * Models used in search
     * @var array
     */
    public array $models = [];

    /**
     * query components in request url
     * @var array|string[]
     */
    protected array $parameters = [
        'search_query' => 'q',
        'actual_page' => 'p',
        'actual_filter' => 'f',
    ];

    protected string $hash;

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Handle search request
     *
     * @return void
     */
    public function get(): ?LengthAwarePaginator
    {
        // Check search status
        if (!$this->checkStatus()) {
            // empty
            return $this->paginate(null);
        }

        // search terms
        $this->searchQuery->handleSearchQuery();
        $this->terms = $this->searchQuery->getTerms() ?? [];

        // search query
        $results = $this->runSearch();

        // relevance
        $results = $this->setSearchRelevance($results);

        // order on relevance
        $results = $this->orderByRelevance($results);

        // paging
        return $this->paginate($results);
    }

    //------------------------------------------------------------------------------------------------------------------


    protected function checkStatus()
    {
        // check if preset search isset
        if (is_null($this->searchQuery) || !empty(request()->get($this->parameters['search_query']))) {

            // get search query
            $search = request()->get($this->parameters['search_query']);
            if (trim($search) == '') {
                return false;
            }

            // Set searchQuery Model
            $this->setSearchQuery($search);
        }

        return true;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Search using available models
     * @return array|null
     */
    protected function runSearch(): ?array
    {
        // Key on path, search query and actual filter
        $key = md5(request()->path() .
            $this->searchQuery->getSearchQuery() .
            request()->get($this->parameters['actual_filter']) ?? '');

        if (config('laravel-search.use_caching')) {
            // Cache for paging result without query
            $results = Cache::remember($key, config('laravel-search.caching_seconds'), function () {
                return $this->runSearchQuery();
            });
        } else {
            $results = $this->runSearchQuery();
        }

        // Add models
        foreach ($this->models as $model => $settings) {
            $results[class_basename($model)]['settings'] = $settings;
        }

        return $results;
    }

    //------------------------------------------------------------------------------------------------------------------

    private function runSearchQuery(): array
    {
        $results = [];
        DB::transaction(function () use (&$results) {

            // Walk thru models to search
            foreach ($this->models as $model => $settings) {
                // Start query
                $results[class_basename($model)]['builder'] = $model::query();

                // Get tablename
                $tablename = app($model)->getTable();

                // Search conditions
                $results[class_basename($model)]['builder']->whereNested(function ($query) use (
                    $settings,
                    $tablename
                ) {
                    foreach ($settings->searchConditions as $set) {

                        // Add table name
                        $set[0] = $tablename.'.'.$set[0];

                        // Create Where Clause
                        $whereMethod = 'where';

                        // OR operator
                        if ($set[3] === 'or') {
                            $whereMethod = 'or'.ucfirst($whereMethod);
                        }

                        // IN operator
                        $postfix = match ($set[1]) {
                            'in' => "In",
                            'not_in' => "NotIn",
                            'like' => "Like",
                            'notlike' => "NotLike",
                            default => "",
                        };

                        if ($postfix != '') {
                            $whereMethod .= $postfix;
                            unset($set[1]);
                        }

                        // unset or operator
                        unset($set[3]);

                        // Closure
                        if (is_a(end($set), 'Closure')) {
                            // Run closure
                            try {
                                $res = call_user_func(end($set));
                                // remove Closure from array
                                array_pop($set);
                                $query->{$whereMethod}(...[...$set, $res]);
                            } catch (\Throwable $e) {
                                // error in closure, not included in query
                            }
                        } else {
                            $query->{$whereMethod}(...$set);
                        }
                    }
                });

                // Search on searchquery
                $results[class_basename($model)]['builder']->whereNested(function ($query) use (
                    $settings,
                    $tablename
                ) {
                    foreach ($settings->searchFields as $name) {
                        foreach ($this->terms as $term) {
                            $query->orWhereRaw('LOWER('.$tablename.'.'.$name.') LIKE "%'.$term.'%"');
                        }
                    }
                });

                // Result in collection
                $results[class_basename($model)]['builder'] = $results[class_basename($model)]['builder']->get();
            }
        });

        return $results;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Search result add relevance
     * @param  array  $results
     * @return array
     */
    protected function setSearchRelevance(array $results): array
    {
        $search_result = [];

        foreach ($results as $result) {

            foreach ($result['builder'] as $row) {
                $relevance = 0;

                // Walk thru each row where search words are found
                foreach ($row->toArray() as $key => $value) {

                    // skip id's etc.
                    if (is_numeric($value)) {
                        continue;
                    }

                    // skip if not in searchFields array
                    if (!in_array($key, $result['settings']->searchFields)) {
                        continue;
                    }

                    //json
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }

                    // Calculate the similarity between two strings
                    $percent = 0;
                    similar_text($value, $this->searchQuery->getTerm(), $percent);
                    $relevance += $percent;

                    $extra_relevance = 100 / count($this->terms);

                    // each word
                    foreach ($this->terms as $term) {

                        if (Str::contains($value, $term)) {
                            $relevance += $extra_relevance;
                        }
                    }

                    // all words
                    if (Str::containsAll($value, $this->terms)) {
                        $relevance += $extra_relevance;
                    }

                    // Priority multiply the relevance
                    if (in_array($key, $result['settings']->searchFields)) {
                        $prio = $result['settings']->searchFieldsPriority[$key];
                        $relevance = $relevance * $prio;
                    }
                }

                // result for the specific row
                $r = [
                    'id' => $row['id'],
                    'model' => get_class($row),
                    'relevance' => $relevance,
                ];

                // fill result fields for result page
                foreach ($result['settings']->showResultFields as $key => $field) {
                    if (is_a($field, 'Closure')) {
                        $boundClosure = \Closure::bind($field, $row);
                        $r[$key] = '';
                        try {
                            $r[$key] = $boundClosure();
                        } catch (\Throwable $e) {
                            // error in closure
                            $r[$key] = '';
                        }
                    } elseif (in_array($field, get_class_methods($row))) {
                        try {
                            $r[$key] = $row->{$field}();
                        } catch (\Throwable $e) {
                            // error in closure
                            $r[$key] = '';
                        }
                    } else {
                        isset($row->{$field}) ? $r[$key] = $row->{$field} : $r[$key] = '';
                    }
                }

                $search_result[] = $r;
            }
        }

        return $search_result;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Order by relevance
     * @param  array  $results
     * @return array
     */
    protected function orderByRelevance(array $results): array
    {
        $keys = array_column($results, 'relevance');
        array_multisort(
            $keys,
            SORT_DESC,
            $results
        );

        return $results;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Paging for search result
     * @param $items
     * @param $perPage
     * @param $page
     * @param $options
     * @return LengthAwarePaginator
     */
    protected function paginate($items, $perPage = 10, $page = null, $options = []): LengthAwarePaginator
    {
        $page = $page ?? request()->get($this->parameters['actual_page']) ?? (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        $options = [
            'path' => "/".request()->path(),
            'query' => [
                $this->parameters['search_query'] => request()->get($this->parameters['search_query']),
                $this->parameters['actual_filter'] => request()->get($this->parameters['actual_filter']),
            ],
        ];

        // page check
        $page = ($page < 1) ? 1 : $page;
        $maxPages = ceil($items->count() / $perPage);
        $page = ($page > $maxPages ? $maxPages : $page);

        $paginator = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page,
            $options);
        $paginator->setPageName($this->parameters['actual_page']);
        return $paginator;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Set search query and create search terms
     * @param  string|null  $value
     * @return $this
     */
    public function setSearchQuery(?string $value): static
    {
        $this->searchQuery = new SearchQuery();
        $this->searchQuery->setSearchQuery($value);
        return $this;
    }

    public function settings(string $setting): static
    {
        $arr = config('laravel-search.settings.'.$setting);

        foreach ($arr as $model => $set) {

            // modify uri variable keys
            if ($model == 'parameters') {
                $this->setGetVars($set);
                continue;
            }

            // preset search
            if ($model == 'searchQuery') {
                $this->setSearchQuery($set);
                continue;
            }

            // handle search settings
            $this->setModel($model, $set['searchFields']);
            $this->setConditions($model, $set['conditions']);
            $this->showResults($model, $set['resultFields']);
        }

        return $this;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Set model to use in search
     * @param  string  $model
     * @param  array  $fields
     * @return $this
     */
    public function setModel(string $model, array $fields): static
    {
        if (!isset($this->models[$model])) {
            $this->models[$model] = new SearchModel($model, $fields);
        } else {
            $this->models[$model]->setSearchFields($fields);
        }
        return $this;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Set search conditions special for model
     * @param  string  $model
     * @param  array  $fields
     * @return $this
     */
    public function setConditions(string $model, array $fields): static
    {
        if (!isset($this->models[$model])) {
            $this->models[$model] = new SearchModel($model);
        }

        $this->models[$model]->setSearchConditions($fields);
        return $this;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Set fields to use use in search result array
     * @param  string  $model
     * @param  array  $fields
     * @return $this
     */
    public function showResults(string $model, array $fields): static
    {
        if (!isset($this->models[$model])) {
            $this->models[$model] = new SearchModel($model);
        }

        $this->models[$model]->setShowResultFields($fields);
        return $this;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Set specific names for query components
     *
     * ?s=Search Terms&p=1&f=books
     *
     * s: search terms
     * p: result page
     * f: specific filter settings to search
     *
     * @param  array  $vars
     * @return $this
     */
    public function setGetVars(array $vars): static
    {
        foreach ($this->parameters as $key => $value) {
            if (isset($vars[$key])) {
                $this->parameters[$key] = $vars[$key];
            }
        }

        return $this;
    }
}
