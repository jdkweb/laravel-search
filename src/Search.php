<?php

namespace Jdkweb\Search;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Items perPage or no paging
     * @var int|null
     */
    protected ?int $pagination = 15;

    protected string $hash;

    //------------------------------------------------------------------------------------------------------------------

    public function __construct(?string $settings = null)
    {
        return $this->settings($settings);
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Handle search request
     *
     * @return LengthAwarePaginator|null
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
     * @return Collection|null
     */
    protected function runSearch(): ?Collection
    {
        // Key on path, search query and actual filter
        $key = md5(request()->path().
        $this->searchQuery->getSearchQuery().
        request()->get($this->parameters['actual_filter']) ?? '');

        if (config('laravel-search.use_caching')) {
            // Cache for paging result without query
            $results = Cache::remember($key, config('laravel-search.caching_seconds'), function () {
                return $this->runSearchQuery();
            });
        } else {
            $results = $this->runSearchQuery();
        }

        return $results;
    }

    //------------------------------------------------------------------------------------------------------------------

    protected function runSearchQuery(): Collection
    {
        $collection = null;
        DB::transaction(function () use (&$collection) {

            // Walk thru models to search
            foreach ($this->models as $model => $settings) {
                // Start query
                $result = null;
                $result = $model::query();
                $this->nestedConditionsSearchQuery($result, $settings);
                $this->nestedSearchFieldsQuery($result, $settings);

                // Merge results from models
                if (is_null($collection)) {
                    $collection = $result->get();
                } else {
                    $collection = $collection->merge($result->get());

                }
            }
        });

        return $collection;
    }

    //------------------------------------------------------------------------------------------------------------------

    protected function nestedConditionsSearchQuery(Builder &$result, SearchModel $settings): void
    {
        $tablename = $result->getModel()->getTable();

        $result->whereNested(function ($query) use ($settings, $tablename) {

            foreach ($settings->searchConditions as $set) {

                // Add table name
                $set['field'] = $tablename.'.'.$set['field'];

                // Create Where Clause
                $whereMethod = 'where';

                // OR operator
                if ($set['condition'] === 'OR') {
                    $whereMethod = 'or'.ucfirst($whereMethod);
                }

                // IN / LIKE operators
                $postfix = match ($set['operator']) {
                    'IN' => "In",
                    'NOT IN' => "NotIn",
                    default => "",
                };

                if ($postfix != '') {
                    $whereMethod .= $postfix;
                }

                // Closure
                if (is_a($set['value'], 'Closure')) {
                    // Run closure
                    try {
                        $res = call_user_func($set['value']);
                    } catch (\Throwable $e) {
                        // error in closure, closure result is not included in query
                    } finally {
                        if (isset($res)) {
                            $query->{$whereMethod}(...[$set['field'], $res->get()]);
                        }
                    }
                } else {
                    $query->{$whereMethod}($set['field'], $set['operator'], $set['value']);
                }
            }
        });
    }

    //------------------------------------------------------------------------------------------------------------------

    protected function nestedSearchFieldsQuery(Builder &$result, SearchModel $settings): void
    {
        $tablename = $result->getModel()->getTable();

        $result->whereNested(function ($query) use ($settings, $tablename) {
            foreach ($settings->searchFields as $name) {
                foreach ($this->terms as $term) {
                    $query->orWhereRaw('LOWER('.$tablename.'.'.$name.') LIKE "%'.$term.'%"');
                }
            }
        });
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Search result add relevance
     * @param  Collection  $results
     * @return Collection
     */
    protected function setSearchRelevance(Collection $results): Collection
    {
        $search_result = collect([]);

        $results->each(function ($row) {
            $settings = $this->models[get_class($row)];
            $priority = $settings->searchFieldsPriority;
            $row->setAttribute('relevance', 0);
            $row->setAttribute('model', get_class($row));

            foreach ($row->getAttributes() as $key => $value) {
                // skip id's etc.
                if (is_numeric($value)) {
                    continue;
                }

                // skip if not in searchFields array
                if (!in_array($key, $settings->searchFields)) {
                    continue;
                }

                // if json
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $value = trim(strtolower($value));

                // Calculate the similarity between two strings
                $percent = 0;
                similar_text($value, $this->searchQuery->getTerm(), $percent);
                $row->relevance += $percent;

                $extra_relevance = 1;

                if (count($this->terms) > 0) {
                    $extra_relevance = 100 / count($this->terms);
                }

                // each word
                foreach ($this->terms as $term) {
                    if (Str::contains($value, $term)) {
                        $row->relevance += $extra_relevance * $priority[$key];
                    }
                }

                // all words
                if (Str::containsAll($value, $this->terms)) {
                    $row->relevance += $extra_relevance;
                }
            }

            // fill result fields for result page
            foreach ($settings->showResultFields as $key => $field) {

                if (is_a($field, 'Closure')) {
                    // bind(Closure, newThis)
                    $boundClosure = \Closure::bind($field, $row);
                    $row->{$key} = $row;
                    try {
                        $row->{$key} = $boundClosure();
                    } catch (\Throwable $e) {
                        // error in closure
                        $row->{$key} = '';
                    }
                } elseif (in_array($field, get_class_methods($row))) {
                    try {
                        $row->{$key} = $row->{$field}();
                    } catch (\Throwable $e) {
                        // error in closure
                        $row->{$key} = '';
                    }
                } else {
                    isset($row->{$field}) ? $row->{$key} = $row->{$field} : $row->{$key} = '';
                }
            }
        });

        // Collection sort on relevance and filter on specific search sets ($this->models)
        $results = $results->sortByDesc('relevance')->filter(function ($item) {
            return in_array(get_class($item), array_keys($this->models));
        });

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
    protected function paginate($items, $perPage = null, $page = null, $options = []): LengthAwarePaginator
    {
        // @TODO show all no pagination, working for billion items
        $perPage = $perPage ?? $this->pagination ?? 1000000000;
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

        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            $options
        );
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

    /**
     * Load settings form config
     * @param  string  $setting
     * @return $this
     */
    public function settings(string $setting = ''): static
    {
        $arr = config('laravel-search.settings.'.$setting);

        if (is_null($arr)) {
            return $this;
        }

        foreach ($arr as $model => $set) {
            // modify uri variable keys
            if ($model == 'parameters') {
                $this->setParams($set);
                continue;
            }

            // pagination
            if ($model == 'pagination') {
                $this->setPagination($set);
                continue;
            }

            // preset search
            if ($model == 'searchQuery') {
                $this->setSearchQuery($set);
                continue;
            }

            // handle search settings
            $this->setModel($model, $set['searchFields']);
            $this->showResults($model, $set['resultFields']);

            if (isset($set['conditions'])) {
                $this->setConditions($model, $set['conditions']);
            }
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
    public function setParams(array $vars): static
    {
        foreach ($this->parameters as $key => $value) {
            if (isset($vars[$key])) {
                $this->parameters[$key] = $vars[$key];
            }
        }

        return $this;
    }

    /**
     * items per page
     * @param  int|null  $pages
     * @return $this
     */
    public function setPagination(?int $pages = 10): static
    {
        $this->pagination = ($pages == false) ? null : (is_numeric($pages) ? $pages : 10);
        return $this;
    }
}
