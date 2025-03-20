# laravel-search
Laravel-Search is a search-engine using the models. Search easily, flexible add an intelligent on your Laravel website or application.

Packagist: [laravel-search](https://packagist.org/packages/jdkweb/laravel-search) 

## Table of contents

- [Installation](#installation)
- [Usage](#usage)
  - [with config file](#config)
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

Laravel-search is working with GET variables (can be renamed)
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
