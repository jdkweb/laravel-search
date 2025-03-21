<?php

return [

    'settings' => [
        'default' => [
            'searchQuery' => 'microsoft',
            'variables' => [
                'search_query' => 'search',     // search terms
                'actual_page' => 'page',        // result page
                'actual_filter' => 'filter'     // result filter
            ],
            'App\Models\Books\Book' => [
                'searchFields' => [
                    'name' => 10,
                    'body' => 2,
                    'colofon' => 1,
                    'intro' => 5
                ],
                'conditions' => [
                    'active:in' => [0,1],
                    'name:!like' => fn() => '%Handboek webcontent%'
                ],
                'resultFields' => [
                    'title' => 'name',
                    'lead' => 'intro',
                    //'url' => fn () => '/artikelen/' . $this->slug
                    'url' => 'getSlug'
                ]
            ],
            'App\Models\Books\Chapter' => [
                'searchFields' => [
                    'name' => 10,
                    'intro' => 5,
                    'body' => 2,
                ],
                'conditions' => [
                    'active' => 1,
                    'book_id:!in' => function () {
                        return App\Models\Books\Book::query()
                            ->where('name', 'like', '%Handboek webcontent%')
                            ->select('id');
                    }
                ],
                'resultFields' => [
//                    'title' => 'name',
                    'title' => function () {
                        return App\Models\Books\Book::query()
                            ->where('id', $this->book_id)
                            ->select('name')->first()->name .
                            " => " .
                            $this->name;
                    },
                    'lead' => 'intro',
                    'url' => 'getSlug'
                ]
            ],
            'App\Models\Articles' => [
                'searchFields' => [
                    'title' => 10,
                    'content' => 2,
                    'lead' => 5
                ],
                'conditions' => [
                    'active' => 1
                ],
                'resultFields' => [
                    'title' => 'title',
                    'lead' => 'lead',
                    //'url' => fn () => '/artikelen/' . $this->slug
                    'url' => 'getUrl'
//                    'url' => function() {
//                        $r = App\Models\Books\Book::query()
//                            ->where('id', 15)
//                            ->select('name')->first();
//
//                        return $this->getUrl($r);
//                    }
                ]
            ],


//        'App\Models\Catia\Activiteit' => [
//
//        ],
//        'App\Models\Articles' => [
//
//        ],
        ],
        'articles' => [
            'App\Models\Articles' => [
                'searchFields' => [
                    'title' => 10,
                    'content' => 2,
                    'lead' => 5
                ],
                'conditions' => [
                    'active' => 1
                ],
                'resultFields' => [
                    'title' => 'title',
                    'lead' => 'lead',
                    //'url' => fn () => '/artikelen/' . $this->slug
                    'url' => 'getUrl'
                ]
            ],
        ]
    ],



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
//    'settings' => [
//        'global' => [
//            'App\Models\Articles' => [
//                'searchFields' => [
//                    'title' => 2.5,
//                    'lead' => 2,
//                    'body' => 1,
//                ],
//                'conditions' => [
//                    'active' => 1,
//                    'published' => 1,
//                ],
//                'resultFields' => [
//                    'title' => 'title',
//                    'lead' => 'lead',
//                    'url' => 'getSlug',
//                    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')
//                ]
//            ],
//            'App\Models\Blog' => [
//                'searchFields' => [
//                    'title' => 5,
//                    'lead' => 2,
//                    'body' => 0.5,
//                ],
//                'conditions' => [
//                    'active' => 1,
//                    'published' => 1,
//                    'published_at:>=' => time(),
//                    'user_id:in' => function () {
//                        return App\Models\User::query()
//                            ->where('active', 1)
//                            ->select('id');
//                    }
//                ],
//                'resultFields' => [
//                    'title' => 'title',
//                    'lead' => 'lead',
//                    'url' => fn () => '/artikelen/' . $this->slug,
//                    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')
//                ]
//            ],
//            'App\Models\Pages' => [
//                'searchFields' => [
//                    'pagetitle' => 2,
//                    'intro' => 1,
//                    'body' => 1,
//                ],
//                'conditions' => [
//                    'active' => 1,
//                ],
//                'resultFields' => [
//                    'title' => 'pagetitle',
//                    'lead' => 'intro',
//                    'url' => 'getUrl',
//                ]
//            ],
//        ],
//        'articles' => [
//            'App\Models\Articles' => [
//                'searchFields' => [
//                    'title' => 2.5,
//                    'lead' => 2,
//                    'body' => 1,
//                ],
//                'conditions' => [
//                    'active' => 1,
//                    'published' => 1,
//                ],
//                'resultFields' => [
//                    'title' => 'title',
//                    'lead' => 'lead',
//                    'url' => fn () => '/artikelen/' . $this->slug,
//                    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')
//                ]
//            ],
//        ]
//    ],

    'use_caching' => true,
    'caching_seconds' => 345600,

    'filter_words' => [
        'nl' =>  'de, en, of, als, het, een, van, op, ook',
        'en' =>  'the, or, else, and, like',
        'de' =>  'das, der, die, und',
        'fr' =>  'le, la, un, une',
    ]
];
