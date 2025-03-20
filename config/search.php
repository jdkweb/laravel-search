<?php

return [

    /**
     * Settings Example
     *
     * variables: name get variables for the search (GET) request
     *
     * global: global search in several modals
     * articles: specific search on article pages
     *
     * fields: fields: to search with priority value (more weight when found)
     * conditions: special search conditions
     * result: define the fields to show in the search result (view)
     *
     * conditions and result:
     *
     * Closure: can be used in conditions and result
     * Method: method name can be called in
     */
    'settings' => [
        'global' => [
            'variables' => [
                'search_query' => 'search',     // search terms
                'actual_page' => 'page',        // result page
                'actual_filter' => 'filter'     // result filter
            ],    
            'App\Models\Articles' => [
                'fields' => [
                    'title' => 2.5,
                    'lead' => 2,
                    'body' => 1,
                ],
                'conditions' => [
                    'active' => 1,
                    'published' => 1,
                ],
                'result' => [
                    'title' => 'title',
                    'lead' => 'lead',
                    'url' => 'getSlug',
                    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')
                ]
            ],
            'App\Models\Blog' => [
                'fields' => [
                    'title' => 5,
                    'lead' => 2,
                    'body' => 0.5,
                ],
                'conditions' => [
                    'active' => 1,
                    'published' => 1,
                    'published_at:>=' => time(),
                    'user_id:in' => function () {
                        return App\Models\User::query()
                            ->where('active', 1)
                            ->select('id');
                    }
                ],
                'result' => [
                    'title' => 'title',
                    'lead' => 'lead',
                    'url' => fn () => '/artikelen/' . $this->slug,
                    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')
                ]
            ],
            'App\Models\Pages' => [
                'fields' => [
                    'pagetitle' => 2,
                    'intro' => 1,
                    'body' => 1,
                ],
                'conditions' => [
                    'active' => 1,
                ],
                'result' => [
                    'title' => 'pagetitle',
                    'lead' => 'intro',
                    'url' => 'getUrl',
                ]
            ],
        ],
        'articles' => [
            'App\Models\Articles' => [
                'fields' => [
                    'title' => 2.5,
                    'lead' => 2,
                    'body' => 1,
                ],
                'conditions' => [
                    'active' => 1,
                    'published' => 1,
                ],
                'result' => [
                    'title' => 'title',
                    'lead' => 'lead',
                    'url' => fn () => '/artikelen/' . $this->slug,
                    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')
                ]
            ],
        ]
    ],

    'filter_words' => [
        'nl' =>  'de, en, of, als, het, een, van, op, ook',
        'en' =>  'the, or, else, and, like'
    ]
];
