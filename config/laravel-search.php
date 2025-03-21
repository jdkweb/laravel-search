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
        'global' => [
            'App\Models\Articles' => [
                'searchFields' => [
                    'title' => 2.5,
                    'lead' => 2,
                    'body' => 1,
                ],
                'conditions' => [
                    'active' => 1,
                    'published' => 1,
                ],
                'resultFields' => [
                    'title' => 'title',
                    'lead' => 'lead',
                    'url' => 'getSlug',
                    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')
                ]
            ],
            'App\Models\Blog' => [
                'searchFields' => [
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
                'resultFields' => [
                    'title' => 'title',
                    'lead' => 'lead',
                    'url' => fn () => '/artikelen/' . $this->slug,
                    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')
                ]
            ],
            'App\Models\Pages' => [
                'searchFields' => [
                    'pagetitle' => 2,
                    'intro' => 1,
                    'body' => 1,
                ],
                'conditions' => [
                    'active' => 1,
                ],
                'resultFields' => [
                    'title' => 'pagetitle',
                    'lead' => 'intro',
                    'url' => 'getUrl',
                ]
            ],
        ],
        'articles' => [
            'App\Models\Articles' => [
                'searchFields' => [
                    'title' => 2.5,
                    'lead' => 2,
                    'body' => 1,
                ],
                'conditions' => [
                    'active' => 1,
                    'published' => 1,
                ],
                'resultFields' => [
                    'title' => 'title',
                    'lead' => 'lead',
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
