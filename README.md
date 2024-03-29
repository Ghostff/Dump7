# Pretty Data Dump
A pretty version of php [var_dump](http://php.net/manual/en/function.var-dump.php). This class displays structured information about one or more expressions that includes its type and value.

_Check out [Dump5](https://github.com/Ghostff/Dump5) for PHP 5+_

# Installation   
You can download the  Latest [release version ](https://github.com/Ghostff/pretty_data_dump/releases/) as a standalone, alternatively you can use [Composer](https://getcomposer.org/) 
```bash
composer require ghostff/dump7
```
```json
"require": {
    "ghostff/dump7": "^1.0"
}
```    
# Display Flags
You can simple hide or show some object attribute using a Doc block flag:

|                               |                                                   |
|-------------------------------|---------------------------------------------------|
| `@dumpignore-inheritance`     | Hides inherited class properties.                 |
| `@dumpignore-inherited-class` | Hides the class name from inherited properties.   |
| `@dumpignore-private`         | Show all properties except the **private** ones.  |
| `@dumpignore-protected`       | Show all properties except the **protected** ones.|
| `@dumpignore-public`          | Show all properties except the **public** ones.   |
| `@dumpignore`                 | Hide the property the Doc comment belongs to.     |
```php
/**
* @dumpignore-inheritance
* @dumpignore-inherited-class
* @dumpignore-private
* @dumpignore-public
* @dumpignore-public
*/
Class Foo extends Bar {
    /** @dumpignore */
    private ?BigObject $foo = null;
}
```

# Usage

```php
class FooBar
{
    private $inherited_int   = 123;
    private $inherited_array = ['string'];
}

class Bar extends FooBar
{
    private $inherited_float = 0.22;
    private $inherited_bool  = 1 == '1';
}

class Foo extends Bar
{
    private $string = 'string';
    protected $int  = 10;
    public $array   = [
        'foo' => 'bar'
    ];
    protected static $bool = false;
}

$string   = 'Foobar';
$array    = ['foo', 'bar'];
$int      = 327626;
$double   = 22.223;
$null     = null;
$bool     = true;
$resource = fopen('LICENSE', 'r');
$m        = microtime(true);

new Dump(new Foo, $string, $array, $int, $double, $null, $bool, [
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
], $resource);

new Dump(1 == '1', 1 === '1');
Dump::safe(...$args); # running on terminal without color capabilities.
```
Replacing predefined colors:
```php
# set($name, [$cgi_color, $cli_color]);
Dump::set('boolean', ['bb02ff', 'purple']);
```

By default, when `Dump` is called inside a function, the call line is set to `new Dump` inside the function instead of the function
call. With `setTraceOffset` you can set the offset of each call line.
```php
function dump()
{
    Dump::setTraceOffset(2);
    new Dump(...func_get_args()); # Dont use this test.php(line:4) as call line
}

dump('foo', 22, 'bar', true); // Use test.php(line:7) instead
```

CGI output:    

![cgi screenshot](https://github.com/Ghostff/Dump7/blob/master/cgi.png)

CLI(Unix):     
    
![cli screenshot](https://github.com/Ghostff/Dump7/blob/master/posix.png)

CLI(Window):     

![cli screenshot](https://github.com/Ghostff/Dump7/blob/master/posixWin.png)
