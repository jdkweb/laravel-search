# laravel-search
Laravel-Search is a search-engine using the models. Search easily, flexible add an intelligent on your Laravel website or application.

Packagist: [laravel-search](https://packagist.org/packages/jdkweb/laravel-search) 

## Table of contents

- [Installation](#installation)
- [Usage](#usage)
  - [with config file](#Configure in the config file)
  - [without config file](#noconfig)
- [Methods and Closures](#methods)


## Installation
Requires PHP 8.1 and Laravel 10 or higher 

Install the package via composer:
```bash
composer require jdkweb/laravel-search
```

### config
For configuration settings you need to publish the config
```bash
php artisan vendor:publish --provider="Jdkweb\Search\SearchServiceProvider" --tag="config"
```
In the config is it possible to setup serveral search-engine setting. So you can setup a global website search and in the blog a specfic blog topic search.

## Usage
### Configure in the config file
```php
'settings' => [
    'global' => [                       // Config name 'global'
        'App\Models\Articles' => [      // Model to search 'Articles'
            'fields' => [               // Database fields to search in
                'title' => 2.5,         // field name with priority (extra weight) 
                'lead' => 2,            // title LIKE '%[search words]%' OR lead LIKE '%[search words]%' OR body LIKE...
                'body' => 1,
            ],
            'conditions' => [
                'active' => 1,          // Extra conditions to filter records  
                'published' => 1,       // active = 1 AND published = 1
            ],
            'result' => [               // Result configuration
                'title' => 'pagetitle', // pagetitle form record => {{ $title }} 
                'lead' => 'intotext',   // introtext => {{ $lead }}
                'url' => 'getSlug',     // call the method getSlug() in App\Models\Articles
                'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y') // Closure
        ]                
    ]
],
```
[See example of large configuration file with methods an closures](#examples) 

Use:

Laravel-search is working with GET variables ([can be renamed](#rename))
```php
search?q=some search words 
```
```php
$search = app('search')->settings('global');    // search with 'global' settings
// search result
$result = $search->get();
```
Result:
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
### Filters
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

### Rename
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

### Configure direct
Create search from scratch, without config settings
```php
$search = app('search')
    ->setGetVars([
        'search_query' => 'search',     // search terms
        'actual_page' => 'page',        // result page
        'actual_filter' => 'filter'     // result filter
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

### Configure



## Examples

```php
    'settings' => [
        'global' => [
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
                ...
            ],
        ]
        'blog' => [
            'App\Models\Blog' => [
            ...        
    ],
```
