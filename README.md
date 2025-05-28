# laravel Search
[![Packagist Version](https://img.shields.io/packagist/v/jdkweb/rdw-api-filament)](https://github.com/jdkweb/rdw-api-filament)
[![Static Badge](https://img.shields.io/badge/build-%20%3E%3D%208.1-brightgreen?style=flat&logo=php&logoSize=16&label=PHP)](https://github.com/jdkweb/rdw-api-filament) 
[![Static Badge](https://img.shields.io/badge/build-%20%3E%3D%2010-slategray?style=flat-square&logo=laravel&logoColor=white&logoSize=16&label=Laravel)](https://github.com/jdkweb/rdw-api-filament)

Laravel-Search is a search-engine using the models. Search easily, flexible add intelligent on your Laravel website or application.

[![Static Badge](https://img.shields.io/badge/build-jdkweb%2Fsearch-blue?style=flat-square&logo=github&label=Github)](https://github.com/jdkweb/search)
[![Static Badge](https://img.shields.io/badge/build-jdkweb%2Fsearch-blue?style=flat-square&logo=packagist&logoColor=white&label=Packagist)](https://packagist.org/packages/jdkweb/search)

![laravel search](https://www.jdkweb.nl/git/images/laravel-search.webp)

## Table of contents

- [Installation](#installation)
- [Usage](#usage)
  - [Configuration with config-file](#configuration-with-config-file)
    - [Search engine configuration](#search-engine-configuration)
      - [Searchable model configuration](#searchable-model-configuration)
      - [Example](#example)
    - [Rename query strings parameters](#rename-query-strings-parameters)
    - [Preset search words](#preset-search-words)
    - [Search Result items per page](#search-result-items-per-page)
    - [Example Config](#example-config)
  - [Configuration directly embed settings in script](#configuration-directly-embed-settings-in-script)
  - [Using the search engine](#using-the-search-engine) 
    - [Filters (search groups)](#filters-search-groups)
- [Operators for conditions](#operators-for-conditions)  
- [Filter specific words from the search](#filter-specific-words-from-the-search)  
- [Methods and Closures](#methods-and-closures)
- [Compare configuration settings](#compare-configuration-setting)


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
- To setup reusable independent search-engine setting.
- Change the list of words to filter from search
- Change cache settings

## Usage

### Configuration with config file
*Publish the config first.*

- Define the models that need to be used in search engine 
- set default search conditions 
- define the output variables.


#### Search engine configuration

Base configuration for the search engine in the config-file
```php
'settings' => [    
    '[CONFIG-NAME]' => [                      // Engine configuration name
        'searchQuery' => '[PRESET SEARCH]',   // Optional: preset search words, results directly shown
        'pagination' => 20,                   // Optional: search items per page , default = 15  
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

##### Searchable model configuration
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

##### Example

The example below shows the configuration of a search engine named 'global'.

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
In short, one can defining multiple search engines. This makes it possible to create multiple specific search sets that behave different. 
You can create a global search engine or one for a specific page/model.

[See example of large configuration file with methods an closures](#example-config)

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

#### Preset search words
It is possible to fire a searchQuery by default.

In config file
```php
'settings' => [
    'default' => [
        'searchQuery' => 'Adobe',           // Set default search
        ...
```

#### Search Result items per page
Change search result items per page

```php
'settings' => [
    'default' => [
        'pagination' => 30,                 // 30 items per page, default is 15
```

```php
'settings' => [
    'default' => [
        'pagination' => false,                 // All results on one page
```

#### Example config
A setup for three different search engine configurations, each in a other situation with more specific search results
- Global website search
- Book / Chapter search
- Article searching
```php
<?php

// Settings URL parameters 
$parameters = [
    'search_query' => 'search',
    'actual_page' => 'page',
    'actual_filter' => 'filter'
];

// App\Models\Books for searching
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
        'cover' => function() {
            return App\Models\BookCovers::query()
                ->where('id', $this->id)
                ->first()            
        }
        'url' => fn() => "/books/".getSlug($this->shortname)
    ]
];

// App\Models\Chapters (related to Books) for searching
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

// App\Models\Articles for searching
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
        'thumbnail' => fn() => $this->getThumbnail(),
        'url' => fn() => "/articles/".$this->slug
    ]
];

// App\Models\Pages for searching
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
            'pagination' => 20
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
            'pagination' => 5
            'App\Models\Articles' => $articles,
        ]
    ]
];
```

### Configuration directly embed settings in script
Without using a config file
```php
$search = app('search')
    ->setSearchQuery('Adobe');                  // Optional: preset search words, results directly shown 
    ->setPagination(10)                         // Optional: search items per page , default = 15
    ->setGetVars([                              // Optional: specific names query strings parameters
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
        'public:in' => function () {            // public value must be available, from Public model
            return App\Models\Public::query()
                ->where('active', 1)
                ->select('id');
        }
    ])
    ->showResults(\App\Models\User::class, [
        'title' => function () {                // show name of user with the company name (from an other model)
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

### Using the search engine

Laravel-search is working with GET variables ([query strings parameters can be renamed](#rename-query-strings-parameters))
```php
search?q=some+search+words 
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
![use result filters](https://www.jdkweb.nl/git/images/search-filters.webp)

The [searchable models](#searchable-model-configuration) we defined earlier can be used to create filters (or search groups). As shown in the example above.

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

### Operators for conditions
In the search conditions is it possible to use operators

| Operator                      | Type                   | Example                         | Query Builder                           |
|-------------------------------|------------------------|---------------------------------|-----------------------------------------| 
| =, <wbr/>eq                   | Equal                  | 'id' => 10  (default)           | ->where('id',10)                        
|                               |                        | 'id:=' => 10                    |
| **!=**,<wbr/> !eq,<wbr/> neq  | Unequal                | 'id:!=' => 10                   | ->where('id','!=', 10)                  
|                               |                        | 'id:neq' => 10                  |
| **\>**,<wbr/> gt              | Greater than           | 'age:>' => 35                   |
| **\>=**,<wbr/> gte            | Greater than or equal  | 'age:gte' => 35                 | ->where('age', '>=', 35)                
| **\<**,<wbr/> lt              | Less than              | 'age:<' => 12                   | ->where('age', '<', 12)                 
| **\<=**,<wbr/> lte            | Less than or equal     | 'age:lte' => 12                 | ->where('age', '<=', 12)                
| **in**                        | In                     | 'id:in' => [10,11,12]           | ->whereIn('id',[10,11,12])              
| **!in**,<wbr/> notin          | Not in                 | 'id:!in' => [2,4]               | ->whereNotIn('id',[2,4])                
| **like**                      | Like                   | 'title:like' => '%Linux%'       | ->where('title', 'LIKE', '%linux%')     
| **!like**,<wbr/> notlike      | Not like               | 'title:notlike' => '%linux%'    | ->where('title', 'NOT LIKE', '%linux%') 
| **or**                        | Or                     | 'or:published' => 1             | ->orWhere('published', 1)               
|                               | Or                     | 'or:id:in' => [10,11]           | ->orWhereIn('id', [10,11]])             

```php
'conditions' => [    
    'book_id:!in' => function () {
        return App\Models\Book::query()
            ->where('active', 0)
            ->where('published', 0)
            ->select('id');
    }
    'or:preview' => 1
],
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
    '[CONFIG-NAME]' => [
        'searchQuery' => [PRESET SEARCH],
        'pagination' => [ITEMS_PER_PAGE_OR_FALSE_FOR_ALL]
        'parameters' => [
            'search_query' => '[NAME]',  
            'actual_page' => '[NAME]',     
            'actual_filter' => '[NAME]'  
        ],        
        'MODEL\NAMESPACE' => [
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
```php
$search = app('search')->settings('[CONFIG-NAME]');
$searchResult = $search->get();
// Handle next search via GET-variable (searchQuery is overwritten)

// OR combine config with direct settings 
$search->setSearchQuery([NEW SEARCH_WORDS]);
$newsearchResult = $search->get();
```
Directly embed settings into the script
```php
$search = app('search')
    ->setSearchQuery([PRESET SEARCH]);   
    ->setPagination([ITEMS_PER_PAGE_OR_FALSE_FOR_ALL])             
    ->setParams([
        'search_query' => '[NAME]',  
        'actual_page' => '[NAME]',     
        'actual_filter' => '[NAME]'  
    ])
    ->setModel([MODEL\NAMESPACE]::class, [   
        [COLUMNAME] => [PRIORITY],
        ...
    ])
    ->setConditions([MODEL\NAMESPACE]::class, [
        [COLUMNNAME] => [VALUE | METHOD | CLOSURE],
        ...
    ])
    ->showResults([MODEL\NAMESPACE]::class, [
        [VARIABLENAME] => [COLUMNNAME | METHOD | CLOSURE],
        ...
    ])
```

```php
$searchResult = $search->get();
// Handle next search via GET-variable (setSearchQuery is overwritten)

// OR insert new search words
$search->setSearchQuery([NEW SEARCH WORDS]);
$newsearchResult = $search->get();
```