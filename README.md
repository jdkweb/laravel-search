# laravel-search
Laravel-Search is a search-engine using the models. Search easily, flexible add intelligent on your Laravel website or application.

Packagist: [laravel-search](https://packagist.org/packages/jdkweb/laravel-search) 

## Table of contents

- [Installation](#installation)
- [Usage](#usage)
  - [Configuration with config-file](#configuration-with-config-file)
    - [Using the search engine](#using-the-search-engine) 
    - [Filters, search groups](#filters-search-groups)
    - [Rename query strings parameters](#rename-query-strings-parameters)
    - [Preset search words](#preset-search-words)
  - [Configuration directly embed settings in script](#configuration-directly-embed-settings-in-script) 
- [Filter specific words from the search](#filter-specific-words-from-the-search)  
- [Methods and Closures](#methods-and-closures)
- [Compare configuration settings](#compare-configuration-setting)
- [Example Config](#example-config)


## Installation
Requires PHP 8.1 and Laravel 10 or higher 

Install the package via composer:
```bash
composer require jdkweb/laravel-search
```

### Config
For configuration settings you need to publish the config
```bash
php artisan vendor:publish --provider="Jdkweb\Search\SearchServiceProvider" --tag="config"
```
In the config is needed for:
- To setup reusable independend search-engine setting.
- Change the list of words to filter from search
- Change cache settings

## Usage
### Configuration with config file
Publish the config first.

- Define the models used in search engine 
- set default search conditions 
- define the output variables.

Base configuration for the search engine in the config-file
```php
'settings' => [    
    '[CONFIG-NAME]' => [                      // Engine configuration name
        'searchQuery' => '[PRESET SEARCH]',   // Optional: preset search words, results directly shown
        'parameters' => [                     // Optional: specific names query strings parameters           
            'search_query' => '[NAME]',       // search terms, default: q
            'actual_page' => '[NAME]',        // result page, default: p
            'actual_filter' => '[NAME]'       // result filter, default: f
        ],    
        // Model configuration
        ...
    ]
]
```

Model configuration
```php
[
    '[MODEL\NAMESPACE]' => [
        'searchFields' => [
            [COLUMNAME] => [PRIORITY],
            ...
        ],
        'conditions' => [
            [COLUMNNAME] => [VALUE | METHOD | CLOSURE],
            ...
        ],
        'resultFields' => [
            [VARIABLENAME] => [COLUMNNAME | METHOD | CLOSURE],
            ...
        ]
]    
    ]
]
```
Example
```php
'settings' => [    
    'global' => [                       // Settings name 'global'
        'searchQuery' => 'linux',       // Preset search 'linux'
        'App\Models\Articles' => [      // Model to search: 'Articles'
            'searchFields' => [         
                'title' => 2.5,         // Column: title,  priority: 2.5 (extra weight) 
                'lead' => 2,            // Column: lead,   priority: 2  
                'body' => 1,            
                                        // In query: title LIKE '%[search words]%' OR lead LIKE '%[search words]%' ...
            ],
            'conditions' => [
                'active' => 1,          // query: active = 1 AND published = 1  
                'published' => 1,       // 
            ],
            'resultFields' => [         
                'title' => 'pagetitle', // pagetitle usable as {{ $title }} 
                'lead' => 'intotext',   // introtext => {{ $lead }}
                'url' => 'getSlug',     // call the method getSlug() in App\Models\Articles
                                        // Closure to format a date in search result
                'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y') 
        ]                
    ]
],
```
The example above is a set named 'global'. This makes it possible to create multiple specific sets that behave different. In addition to a global search engine for the entire website it is posible to make a specific page related search

[See example of large configuration file with methods an closures](#Configuration-3)

#### Using the search engine

Laravel-search is working with GET variables ([can be renamed](#rename))
```php
search?q=some search words 
```
```php
$search = app('search')->settings('global');    // search with 'global' settings
// search result
$result = $search->get();
```
**Result:**
```php
#items: array:4 [▼
  0 => array:6 [▼
    "id" => 214
    "model" => "App\Models\Articles"
    "relevance" => 4221.0572158193
    "title" => "Sed non leo ac massa dignissim condimentum"
    "lead" => "Donec efficitur dictum justo vitae auctor. Curabitur eu diam a nisi eleifend tristique eget non augue. Integer sed metus non nisl fringilla venenatis."
    "url" => "/articles/sed-non-leo-ac-massa-dignissim",
    "date" => "11/01/2025"
  ]
  1 => array:6 [▼
    "id" => 78
    "model" => "App\Models\Articles"
    "relevance" => 3947.883125879
    "title" => "Cras non urna vitae risus suscipit varius eu eget lorem"
    "lead" => "Nullam sed nisl mi. Vivamus interdum ut turpis ac aliquet. Vestibulum vulputate, ex ut iaculis venenatis, nisl neque bibendum ex"
    "url" => "/articles/cras-non-urna-vitae-risus"
    "date" => "31/11/2024"
  ]
  2 => array:6 [▶]
  3 => array:6 [▶]
```
#### Filters (search groups)
![use result filters](./images/search-filters.webp)
Use specific model or group of models for searching
```php
// available filters
$filters = ['all','blog','articles','workshops']

// get filter &f=[FILTER] 
$filter = request()->get('f');

$search = match($filter) {
    'blog' => app('search')->settings('blog');              
    'articles' => app('search')->settings('articles');
    'workshops' => app('search')->settings('workshops');
    default => app('search')->settings('default');          
};

$result = $search->get();
```

#### Rename query strings parameters
Renaming the GET variables that appear in the URL
```php
// Default
search?q=some search words&p=1&f=articles

// Modified using the setting below
search?search=some search words&page=1&filter=articles 
```

```php
'settings' => [
    'global' => [                               // Config name 'global'
            'variables' => [                    
                'search_query' => 'search',     // search terms
                'actual_page' => 'page',        // result page
                'actual_filter' => 'filter'     // result filter
            ],    
            'App\Models\Articles' => [
            ...  
```

### Preset search words
It is possible to fire a searchQuery by default.

In config file
```php
'settings' => [
    'default' => [
        'searchQuery' => 'Adobe',           // Set default search
        ...
```

### Configuration directly embed settings in script
Without using a config file 
```php
$search = app('search')
    ->setSearchQuery('Adobe');                  // Set default search 
    ->setGetVars([
        'search_query' => 'search',             // search terms
        'actual_page' => 'page',                // result page
        'actual_filter' => 'filter'             // result filter
    ])
    ->setModel(\App\Models\Articles::class, [   // Database Article table to search in
        'title' => 2,                           // Fieldnames with priority (extra weight)     
        'lead' => 1.5,       
        'body' => 1,
    ])
    ->setConditions(\App\Models\Articles::class, [
        'active' => 1,                          // active must be true
        'created_at:>' => '01-01-2025',         // articles created after 01 jan. 2025 
    ])
    ->showResults(\App\Models\Articles::class, [
        'title' => 'name',                      // pass database field = 'name' as 'title' to results
        'lead' => 'intro',                      // pass database field = 'intro' as 'lead' to results
        'url' => 'getSlug'                      // method in \App\Models\Articles pass as 'url' to results
    ])
    ->setModel(\App\Models\User::class, [       // Database User table to search in
        'name' => 1,                            // Fieldnames with priority (extra weight)
        'email' => 1,
    ])
    ->setConditions(\App\Models\User::class, [
        'active' => 1,                          // active must be true
        'public:in' => function () {            // public value must be available in Public-table
            return App\Models\Public::query()
                ->where('active', 1)
                ->select('id');
        }
    ])
    ->showResults(\App\Models\User::class, [
        'title' => function () {                // show name of user with the company name (from other table)
            $company_name = App\Models\Companies::query()
                ->where('user_id', $this->id)
                ->select('company_name')->first()->company_name        
            return $this->name . " (" . $company_name .")"
        },
        'lead' => 'intro',
        'url' => fn() => '/users/' . $this->slug  // closure arrow function to get slug passed as url to result    
    ]);
}
```

### Filter specific words from the search
Language related list of words that are filtered from the search

Removing Linking words (the, and, ...) makes it possible to keep the search results cleaner.
```php
'filter_words' => [
    'nl' =>  'de, en, of, als, het, een, van, op, ook',
    'en' =>  'the, or, else, and, like',
    'de' =>  'das, der, die, und',
    'fr' =>  'le, la, un, une',
]
```

### Methods and Closures
The configurations above provide several examples of using methods and Closures.

This makes it possible to relate the models to Closure functions, and methods form (other) models or controllers.

#### Methods
In config file
```php
'resultFields' => [         
    'title' => 'name', 
    'lead' => 'intro',
    'url' => 'getSlug'      // Method getSlug bind to the Articles class
```
Directly into the script
```php
->showResults(\App\Models\Articles::class, [
    'title' => 'name',
    'lead' => 'intro',
    'url' => 'getSlug'      // Method getSlug bind to the Articles class
])
```
Method in model (\App\Models\Articles)
```php
public function getSlug()
{
    return '/articles/' . $this->createSlug();
}
```
#### Closures 
```php
->showResults(\App\Models\Articles::class, [
    'title' => 'name',                     
    'lead' => 'intro',                     
    'url' => fn() => '/articles/' . $this->createSlug();      // Arrow function             
])
```

```php
->showResults(\App\Models\Articles::class, [
    'title' => 'name',                     
    'lead' => 'intro',                     
    'url' => function() {                   // Closure
        return $this->getSlug()             // Method getSlug bind to the Articles class
    },
    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')   // Arrow function                  
])
```
## Compare configuration settings

Config-file
```php
'settings' => [
    'SETNAME' => [
        'searchQuery' => SEARCHWORDS,
        'variables' => [
            'search_query' => 'SEARCH-GETVARNAME',  
            'actual_page' => 'PAGE-GETVARNAME',     
            'actual_filter' => 'FILTER-GETVARNAME'  
        ],        
        'MODEL\NAMESPACE' => [
            'searchFields' => [
                COLUMNAME => PRIORITY,
                ...
            ],
            'conditions' => [
                COLUMNNAME => VALUE | METHOD | CLOSURE,
                ...
            ],
            'resultFields' => [
                VARIABLENAME => COLUMNNAME | METHOD | CLOSURE,
                ...
            ]
        ]    
    ]
]
```
```php
$search = app('search')->settings('SETNAME');
$searchResult = $search->get();
// Handle next search via GET-variable (searchQuery is overwritten)

// OR combine config with direct settings 
$search->setSearchQuery(NEW_SEARCH_WORDS);
$newsearchResult = $search->get();
```
Directly embed settings into the script
```php
$search = app('search')
    ->setSearchQuery(SEARCHWORDS);                
    ->setGetVars([
        'search_query' => 'SEARCH-GETVARNAME',  
        'actual_page' => 'PAGE-GETVARNAME',     
        'actual_filter' => 'PAGE-GETVARNAME'  
    ])
    ->setModel(MODEL\NAMESPACE::class, [   
        COLUMNAME => PRIORITY,
        ...
    ])
    ->setConditions(MODEL\NAMESPACE::class, [
        COLUMNNAME => VALUE | METHOD | CLOSURE,
        ...
    ])
    ->showResults(MODEL\NAMESPACE::class, [
        VARIABLENAME => COLUMNNAME | METHOD | CLOSURE,
        ...
    ])
```

```php
$searchResult = $search->get();
// Handle next search via GET-variable (setSearchQuery is overwritten)

// OR insert new search words
$search->setSearchQuery(NEW_SEARCH_WORDS);
$newsearchResult = $search->get();
```

### Example config
```php
<?php

$parameters = [
    'search_query' => 'search',
    'actual_page' => 'page',
    'actual_filter' => 'filter'
];

$books = [
    'searchFields' => [
        'name' => 3,
        'body' => 2,
        'intro' => 2
    ],
    'conditions' => [
        'active' => 1,
    ],
    'resultFields' => [
        'title' => 'name',
        'text' => 'intro',
        'url' => fn() => "/books/".getSlug($this->shortname)
    ]
];

$chapter = [
    'searchFields' => [
        'name' => 3,
        'intro' => 3,
        'body' => 2,
    ],
    'conditions' => [
        'active' => 1,
        'book_id:in' => function () {
            return App\Models\Book::query()
                ->where('active', 1)
                ->select('id');
        }
    ],
    'resultFields' => [
        'title' => 'name',
        'bookname' => function () {
            return App\Models\Book::query()
                ->where('id', $this->book_id)
                ->select('name')->first()->name;
        },
        'text' => 'intro',
        'url' => 'getSlug'
    ]
];

$articles = [
    'searchFields' => [
        'title' => 3,
        'lead' => 2,
        'content' => 1,
    ],
    'conditions' => [
        'active' => 1,
        'published:<' => time()
    ],
    'resultFields' => [
        'title' => 'title',
        'text' => 'lead',
        'url' => fn() => "/articles/".$this->slug
    ]
];


$pages = [
    'searchFields' => [
        'title' => 3,
        'introduction' => 3,
        'content' => 2
    ],
    'conditions' => [
        'active' => 1,
        'parent_id:!=' => 88
    ],
    'resultFields' => [
        'title' => 'title',
        'text' => 'introduction',
        'url' => fn() => "/pages/".getSlug($this->name)
    ]
];

return [
    'settings' => [
        'global' => [                                       // Global search on search page on the website
            'parameters' => $parameters,
            'App\Models\Articles' => $articles,         
            'App\Models\Pages' => $pages
            'App\Models\Books\Book' => $books,
            'App\Models\Books\Chapter' => $chapter
        ],
        'books' => [                                        // searching in books and chapters
            'parameters' => $parameters,
            'App\Models\Books\Book' => $books,
            'App\Models\Books\Chapter' => $chapter,
        ],
        'articles' => [                                     // searching in articles
            'parameters' => $parameters,
            'App\Models\Articles' => $articles,
        ]
    ]
];
```


