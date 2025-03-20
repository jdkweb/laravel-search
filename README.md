# laravel-search
Laravel-Search is a search-engine using the models. Search easily, flexible add an intelligent on your Laravel website or application.

Packagist: [laravel-search](https://packagist.org/packages/jdkweb/laravel-search) 

## Table of contents

- [Installation](#installation)
- [Usage](#usage)
  - [Configuration](#configuration)
  - [Configuration on the fly](#Configuration-1)
- [Filter specific words from the search](#Configuration-2)  
- [Methods and Closures](#Configuration-3)


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
In the config is it possible to setup serveral search-engine setting. So you can setup a global website search and in the blog a specfic blog topic search.

## Usage
### Configuration
#### Use config file, publish the config first 
Define the models used in search, set default search conditions and define the output variables.

This makes it possible to create multiple specific search engines for certain pages, in addition to a global search engine for the entire website 

In this example one model (Articles) is defined in a set named 'global'.
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

#### Rename
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

### Configuration
#### Create search from scratch, on the fly, without config settings
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

### Configuration
#### Filter specific words from the search
Language related list of words that are filtered from the search

Words like Linking words (the, and, ...). This makes it possible to keep the search results cleaner
```php
'filter_words' => [
    'nl' =>  'de, en, of, als, het, een, van, op, ook',
    'en' =>  'the, or, else, and, like',
    'de' =>  'das, der, die, und',
    'fr' =>  'le, la, un, une',
]
```

### Configuration
#### Use methods and Closures in the config file
The configurations above provide several examples of using methods and Closures.

This makes it possible to relate the models to be used to each other or to use external data in the search results.

#### Methods
```php
...
->showResults(\App\Models\Articles::class, [
    'title' => 'name',                     
    'lead' => 'intro',                     
    'url' => 'getSlug'    // Method getSlug in Articles class                     
])
```
\App\Models\Articles
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
    'url' => fn() => '/articles/' . $this->createSlug();                   
])
```

```php
->showResults(\App\Models\Articles::class, [
    'title' => 'name',                     
    'lead' => 'intro',                     
    'url' => function() {
        return $this->getSlug()     
    },
    'date' => fn () => \Carbon\Carbon::parse($this->created_at)->format('d/m/Y')                     
])
```
