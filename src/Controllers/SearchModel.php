<?php

namespace Jdkweb\Search\Controllers;

class SearchModel
{
    public array $searchFields = [];

    public array $searchFieldsPriority = [];

    public array $searchConditions = [];

    public array $showResultFields = [];

    private array $allowedOperators = ['in','or','like','!like','!=','=','>','>=','<','<='];

    //------------------------------------------------------------------------------------------------------------------

    public function __construct(
        public string $namespace,
        array $searchFields = []
    )
    {
        $this->setSearchFields($searchFields);
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Set Model to search
     *
     * @param  string  $namespace
     */
    public function setModel(string $namespace)
    {
        $this->namespace = $namespace;
    }

    //------------------------------------------------------------------------------------------------------------------

    public function setSearchFields(array $searchFields)
    {
        foreach($searchFields as $field=>$priority) {
            $this->setSearchField($field);
            $this->setSearchFieldPriority($field, $priority);
        }
    }

    //------------------------------------------------------------------------------------------------------------------

    public function setSearchField(string $searchField): void
    {
        $this->searchFields[] = $searchField;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Priority for each search field
     * @param  string  $key
     * @param  int  $searchFieldPriority
     * @return void
     */
    public function setSearchFieldPriority(string $key, int $searchFieldPriority = 0): void
    {
        $this->searchFieldsPriority[$key] = $searchFieldPriority;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Fields to fill for the result array
     * @param  array  $showFields
     * @return void
     */
    public function setShowResultFields(array $showFields): void
    {
        foreach ($showFields as $key => $value) {
            $this->showResultFields[$key] = $value;
        }
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Walk thru search condition settings
     * @param  array  $searchConditions
     * @return void
     */
    public function setSearchConditions(array $searchConditions): void
    {
        foreach ($searchConditions as $key => $value) {
            $condition = $this->setSearchCondition($key, $value);
            if(!is_null($condition)) {
                $this->searchConditions[] = $condition;
            }
        }
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Search conditions and operator
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return array|null
     */
    public function setSearchCondition(string $key,  mixed $value): ?array
    {
        // base operator
        $operator = "=";

        // check prefix (orWhere)
        $or = '';
        if(preg_match("/^or:(.*)$/",$key)) {
            $or = 'or';
            $key = substr($key, 3);
        }

        // check suffix
        if(preg_match("/:/",$key)) {
            list($key, $operator) = preg_split("/:/",$key);
        }

        // Check operator
        if(!in_array($operator, $this->allowedOperators)) return null;

        // Translate not like
        if($operator == '!like') {
            $operator = 'not like';
        }

        return [$key, $operator, $value, $or];
    }
}
