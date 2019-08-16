# Pretty Data Dump
A pretty version of php [var_dump](http://php.net/manual/en/function.var-dump.php). This class displays structured information about one or more expressions that includes its type and value.

_Check out [Dump5](https://github.com/Ghostff/Dump5) for PHP 5+_

# Installation   
You can download the  Latest [release version ](https://github.com/Ghostff/pretty_data_dump/releases/) as a standalone, alternatively you can use [Composer](https://getcomposer.org/) 
```json
$ composer require ghostff/dump7
```
```json
"require": {
    "ghostff/dump7": "^1.0"
}
```    

```php
class Foo
{
	private $string = 'string';
	protected $int = 10;
	public $array = [
	    'foo'   => 'bar'
	];
	protected static $bool = false;
}

$string = 'Foobar';
$array = ['foo', 'bar'];
$int = 327626;
$double = 22.223;
$null = null;
$bool = true;
$resource = fopen('LICENSE', 'r');

new Dump(new Foo, $string, $array, $int, $double, $null, $bool, $resource, [
    'foo' => 'bar',
    'bar' => 'foo',
    [
        'foo' => 'foobar',
        'bar_foo',
        2 => 'foo',
        'foo' => [
            'barbar' => 55,
            'foofoo' => false,
            'foobar' => null,
        ]
    ]
]);

new Dump(1 == '1', 1 === '1');
```
Replacing predefined colors:
```php
# set($name, [$cgi_color, $cli_color]);
Dump::set('boolean', ['bb02ff', 'purple']);
```
CGI output:    

![cgi screenshot](https://github.com/Ghostff/Dump7/blob/master/cgi.png)

CLI(Unix):     
    
![cli screenshot](https://github.com/Ghostff/Dump7/blob/master/posix.png)

CLI(Window):     

![cli screenshot](https://github.com/Ghostff/Dump7/blob/master/posixWin.png)

Windows user runing on terminal without color capabilities, can use `Dump::safe` method:
```php
Dump::safe(new Foo, $string, $array, $int, $double, $null, $bool, $resource, [
    'foo' => 'bar',
    'bar' => 'foo',
    [
        'foo' => 'foobar',
        'bar_foo',
        2 => 'foo',
        'foo' => [
            'barbar' => 55,
            'foofoo' => false,
            'foobar' => null,
        ]
    ]
]);
```
CLI Windows output:
