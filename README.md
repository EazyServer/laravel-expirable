# Laravel Expirable

Add expiring feature to Eloquent models in Laravel 5.


## Background

This has been developed to simplify adding `expirable` feature to any eloquent model on your laravel project. 


## Installation
To install the package via Composer:

```shell
$ composer require yarob/laravel-expirable
```
Then, update `config/app.php` by adding an entry for the service provider.

```php
'providers' => [
    // ...
    Yarob\LaravelExpirable\ServiceProvider::class,
];
```
Finally, via terminal, publish the default configuration file (if you need to, see below):

```shell
php artisan vendor:publish --provider="Yarob\LaravelExpirable\ServiceProvider"
```
## Updating your Eloquent Models

Your models can now use the `Expirable` trait.
You must also add `expire_at` to your `$dates` array in the model as shown in the example below

```php
use Yarob\LaravelExpirable\Expirable;

class User extends Model
{
    use Expirable;
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'expire_at'
    ];

}
```

## Migration
Your model MUST have column named `expire_at` in the database to store the expiry date value.
You can add this manually via a migration on the intended model ` $table->timestamp('expire_at')->nullable(); `. 

## Usage

There are three basic functions in `Expirable` trait:
* `hasExpired()` to check if model has expired or not returns `boolean`
* `timeToLive()` returns number of seconds left on models life, could be minus if has expired already! returns `false` if not applicable.
* `reviveExpired($numberOfSeconds=null)` if model has expired you can then revive it by using this function and supplying `$numberOfSeconds` revival period starting from `now()`. If `$numberOfSeconds` is not supplied then will default back to the value set in `expirable.php` in config folder, if set! Otherwise it will not do anything and returns `false`.
* Model has `null` expiry date means a non-expirable model and lives forever, e.g. `$user->expire_at = null;`
 
 Example:-

```php
$user = App\User::get();

foreach($users as $user) {
		if($user->hasExpired())
		{
			var_dump($user->timeToLive());
			$user->reviveExpired();
			
			var_dump($user->timeToLive());	
		}
		else
		{
		    $user->expire_at = \Carbon\Carbon::now()->addDay(-10);// you can add minus values
            $user->save();
                        
            var_dump($user->timeToLive());	
		}
	}
```

## Querying Expirable Models
Expired models (passed it’s expiry date) will automatically be excluded from query results. 
For example:-
```php
 $users = App\User::get();
```
will only gets models that has NOT expired or has `null` expiry date. Any expired models WILL be auto-excluded.


### Including expired Models

You can force expired models to appear in query results using `withHasExpiry`

```php
$users = App\User::withHasExpiry()->get();
```

### Retrieving Only Expired Models
  
The `onlyHasExpiry` method can be used on a relationship query:

```php
$users = App\User::onlyHasExpiry()->get();
```
This will exclude ONLY `null` expiry date from results.
Please note this will get ALL models that have non `null` expiry date, regradless of their expiry date.

### Retrieving Models has `null` expiry
  
The `withoutHasExpiry` method can be used in this case:

```php
$users = App\User::withoutHasExpiry()->get();
```

This will bring models that has `null` expiry date.

## Configuration

Configuration is not usually needed, unless you want to set a default revival time to a model(s). A default value of `86400 seconds` is set for user model, as an example, but feel free to change that to any value you want.
Here is an example configuration:

```php
return [

	/**
	 * Revival Time in seconds used to extend life in expired models
	 */

	'User' => [
		'revival_time' => 24*60*60,
	]
];
```

Pay attention that Model name in `expirable.php` is case sensitive! so if you have a `foo` Model, then

```php
return [
    'foo' => [
    		'revival_time' => 24*60*60,
    	],
];
```

## Copyright and License

[laravel-expirable](https://github.com/EazyServer/laravel-expirable) was written by [Yarob Al-Taay](https://twitter.com/TheEpicVoyage) and is released under the 
[MIT License](LICENSE.md).

Copyright (c) 2017 Yarob Al-Taay