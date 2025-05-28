<?php

return [

    /**
     * Settings Example
     *
     * global: global search in several modals
     * articles: specific search on article pages
     *
     * searchFields: fields to search with priority value (more weight when found)
     * conditions: special search conditions
     * resultFields: define the fields to show in the search result (view)
     *
     * conditions and result:
     *
     * Closure: can be used in conditions and result
     * Method: method name can be called in
     */
    'settings' => [
        'default' => [
            'searchQuery' => 'wcag',
            'parameters' => [
                'search_query' => 's',
                'actual_page' => 'page',
                'actual_filter' => 'f'
            ],
            'App\Models\About' => [
                'searchFields' => [
                    'title' => 2.5,
                    'intro_text' => 2,
                    'content' => 1,
                ],
                'conditions' => [
                    'active' => 1
                ],
                'resultFields' => [
                    'title' => 'title',
                    'text' => 'intro_text',
                    'url' => fn() => "/about/".$this->slug
                ]
            ],
            'App\Models\Pages' => [
                'searchFields' => [
                    'title' => 2,
                    'intro_text' => 1.5,
                    'content' => 1,
                ],
                'conditions' => [
                    'active' => 1
                ],
                'resultFields' => [
                    'title' => 'title',
                    'text' => 'intro_text',
                    'url' => fn() => "/".$this->slug
                ]
            ],
        ],
        'articles' => [
            'searchQuery' => 'wcag',
            'parameters' => [
                'search_query' => 's',
                'actual_page' => 'page',
                'actual_filter' => 'f'
            ],
            'App\Models\Articles' => [
                'searchFields' => [
                    'title' => 2.5,
                    'lead' => 2,
                    'content' => 1,
                ],
                'conditions' => [
                    //'title:like' => "VN%",
                    'active' => 1,
                ],
                'resultFields' => [
                    'title' => 'title',
                    'text' => 'lead',
                    'url' => fn () => '/artikelen/' . $this->slug,
                    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')
                ]
            ],
        ]
    ],

    'use_caching' => true,
    'caching_seconds' => 345600,

    'filter_words' => [
        'nl' =>  'de, en, of, als, het, een, van, op, ook',
        'en' =>  'the, or, else, and, like',
        'de' =>  'das, der, die, und',
        'fr' =>  'le, la, un, une',
    ]
];
