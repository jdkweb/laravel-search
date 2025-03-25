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
            'App\Models\Articles' => [
                'searchFields' => [
                    'title' => 2.5,
                    'lead' => 2,
                    'content' => 1,
                ],
                'conditions' => [
                    'active' => 1
                ],
                'resultFields' => [
                    'title' => 'title',
                    'text' => 'lead',
                    'url' => fn() => "/".config('ia.uri.artikelen')."/".$this->slug
                ]
            ],
            'App\Models\Advies' => [
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
                    'url' => fn() => "/".config('ia.uri.advies')."/".$this->slug
                ]
            ],
            'App\Models\Overons' => [
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
                    'url' => fn() => "/".config('ia.uri.over')."/".$this->slug
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
            'App\Models\Books\Book' => [
                'searchFields' => [
                    'name' => 2.5,
                    'shortname' => 1,
                    'body' => 2,
                    'colofon' => 1,
                    'intro' => 2
                ],
                'conditions' => [
                    'active' => 1,
                ],
                'resultFields' => [
                    'title' => 'name',
                    'text' => 'intro',
                    'url' => fn () => "/" . config('ia.uri.ebooks') ."/" . getSlug($this->shortname)
                ]
            ],
            'App\Models\Books\Chapter' => [
                'searchFields' => [
                    'name' => 2.5,
                    'intro' => 2.5,
                    'body' => 2,
                ],
                'conditions' => [
                    'active' => 1,
//                    'book_id:!in' => function () {
//                        return App\Models\Books\Book::query()
//                            ->where('active', 1)
//                            ->select('id');
//                    }
                ],
                'resultFields' => [
                    'title' => 'name',
                    'bookname' => function () {
                        return App\Models\Books\Book::query()
                            ->where('id', $this->book_id)
                            ->select('name')->first()->name;
                    },
                    'text' => 'intro',
                    'url' => 'getSlug'
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
