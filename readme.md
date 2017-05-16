<p align="center">
    <a href="LICENSE.txt" target="_blank">
        <img alt="Software License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square">
    </a>
    <a href="https://app.codeship.com/projects/220010" target="_blank">
        <img alt="Build Status" src="https://img.shields.io/codeship/b2c9f990-1c64-0135-200b-020110d102f0.svg?style=flat-square">
    </a>
    <a href="https://app.codeship.com/projects/220010" target="_blank">
        <img alt="Build Status" src="https://app.codeship.com/projects/47da23f0-1c84-0135-820b-56a4cb57cee4/status?branch=master&style=flat-square">
    </a>
    <a href="https://packagist.org/packages/okatsuralau/cakephp-mixpanel" target="_blank">
        <img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/okatsuralau/cakephp-mixpanel.svg?style=flat-square&label=stable">
    </a>
</p>

CakePHP Mixpanel Plugin
=======================

This plugin provides a Mixpanel component to track events from your controllers using the official [Mixpanel PHP library](https://github.com/mixpanel/mixpanel-php).

Requirements
------------

- PHP >= 5.6
- CakePHP >= 3.0


How to Install
----------

```
composer require okatsuralau/cakephp-mixpanel@1.0.0
```

How to Use
----------

Load the plugin in your `config/bootstrap.php` file:
```
Plugin::load('CakephpMixpanel');
```

Add the plugin configurations in your `config/app.php` or `config/app_custom.php` file:

```php
return [
    //...
    
    'Mixpanel' => [
        'token' => YOUR_TOKEN_HERE
    ]
]
```

Load the component in your `src/Controller/AppController.php`

```php
public function initialize()
{
    parent::initialize();

    $this->loadComponent('CakephpMixpanel.Mixpanel');
}
```

and (optionally) add the following code to your `beforeFilter()` method to identify the users actions

```php
public function beforeFilter(\Cake\Event\Event $event)
{
    // if a user is logged in
    $this->Mixpanel->identify($user_id);
    $this->Mixpanel->name_tag($user_name);
    $this->Mixpanel->register($superProperties);
    
    /* To make use of the people API */
    $this->Mixpanel->people($this->Auth->user('id'), array(
        '$username' => $this->Auth->user('username'),
        '$email' => $this->Auth->user('email'),
        '$created' => $this->Auth->user('created'),
        '$last_login' => $this->Auth->user('connected'),
        'my_custom_var' => $my_custom_var,
    ));
    
    // ...
    
    parent::beforeFilter($event);
}
```

To register an event, put the following code in your controller action.
`src/Controller/PostController.php`

```php
public function index()
{
    // ...
    
    $this->Mixpanel->track(
        'Post list', 
        [
            'author' => $this->Auth->user('name'),
            'category' => 'Post',
        ]
    );
}

public function create()
{
    if ($this->request->is('post')) {
        
        // ...
        
        $this->Mixpanel->track(
            'Post Created', 
            [
                'author' => $this->Auth->user('name'),
                'category' => 'Post',
            ]
        );
        
        $this->redirect(array('action'=>'index'));
    }
}
```

This should be enough to start sending events to Mixpanel.

License
-------

Copyright 2017 Gabriel Lau

Available for you to use under the MIT license. See: http://www.opensource.org/licenses/MIT
