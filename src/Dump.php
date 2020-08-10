<?php

declare(strict_types=1);

/**
 * Bittr
 *
 * @license
 *
 * New BSD License
 *
 * Copyright (c) 2017, ghostff community
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *      1. Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *      2. Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *      3. All advertising materials mentioning features or use of this software
 *      must display the following acknowledgement:
 *      This product includes software developed by the ghostff.
 *      4. Neither the name of the ghostff nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY ghostff ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL GHOSTFF COMMUNITY BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

class Dump
{
    protected $isCli = false;

    private $indent = 0;

    private $nest_level = 20;

    private $pad_size = 3;

    private $isPosix = false;

    private $colors = [
        'string'    => ['0000FF', 'blue'],
        'integer'   => ['1BAABB', 'light_green'],
        'double'    => ['9C6E25', 'cyan'],
        'boolean'   => ['bb02ff', 'purple'],
        'keyword'   => ['bb02ff', 'purple'],
        'null'      => ['6789f8', 'white'],
        'type'      => ['AAAAAA', 'dark_gray'],
        'size'      => ['5BA415', 'green'],
        'recursion' => ['F00000', 'red'],
        'resource'  => ['F00000', 'red'],

        'array'              => ['000000', 'white'],
        'multi_array_key'    => ['59829e', 'yellow'],
        'single_array_key'   => ['f07b06', 'light_yellow'],
        'multi_array_arrow'  => ['e103c4', 'red'],
        'single_array_arrow' => ['f00000', 'red'],

        'object'              => ['000000', 'white'],
        'property_visibility' => ['741515', 'light_red'],
        'property_name'       => ['987a00', 'light_cyan'],
        'property_arrow'      => ['f00000', 'red'],
    ];

    private static $safe    = false;
    private static $changes = [];

    /**
     * Foreground colors map
     *
     * @var array
     */
    private $foregrounds = [
        'none'          => null,
        'black'         => 30,
        'red'           => 31,
        'green'         => 32,
        'yellow'        => 33,
        'blue'          => 34,
        'purple'        => 35,
        'cyan'          => 36,
        'light_gray'    => 37,
        'dark_gray'     => 90,
        'light_red'     => 91,
        'light_green'   => 92,
        'light_yellow'  => 93,
        'light_blue'    => 94,
        'light_magenta' => 95,
        'light_cyan'    => 96,
        'white'         => 97,
    ];

    /**
     * Background colors map
     *
     * @var array
     */
    private $backgrounds = [
        'none'          => null,
        'black'         => 40,
        'red'           => 41,
        'green'         => 42,
        'yellow'        => 43,
        'blue'          => 44,
        'purple'        => 45,
        'cyan'          => 46,
        'light_gray'    => 47,
        'dark_gray'     => 100,
        'light_red'     => 101,
        'light_green'   => 102,
        'light_yellow'  => 103,
        'light_blue'    => 104,
        'light_magenta' => 105,
        'light_cyan'    => 106,
        'white'         => 107,
    ];

    /**
     * Styles map
     *
     * @var array
     */
    private $styles = [
        'none'      => null,
        'bold'      => 1,
        'faint'     => 2,
        'italic'    => 3,
        'underline' => 4,
        'blink'     => 5,
        'negative'  => 7,
    ];


    /**
     * Dump constructor.
     */
    public function __construct(...$args)
    {
        if (substr(PHP_SAPI, 0, 3) == 'cli')
        {
            $this->isCli   = true;
            $this->isPosix = $this->isPosix();
        }

        $this->colors = self::$changes + $this->colors;
        $this->output($this->evaluate($args));
    }

    /**
     * Force debug to use posix, (For window users who are using tools like http://cmder.net/)
     */
    public static function safe(...$args)
    {
        self::$safe = true;
        new self(...$args);
    }

    /**
     * Updates color properties value.
     *
     * @param string $name
     * @param array  $value
     */
    public static function set(string $name, array $value)
    {
        self::$changes[$name] = $value;
    }


    /**
     * Assert code nesting doesn't surpass specified limit.
     *
     * @return bool
     */
    public function aboveNestLevel(): bool
    {
        return (count(debug_backtrace()) > $this->nest_level);
    }

    /**
     * Check if a resource is an interactive terminal
     *
     * @return bool
     */
    private function isPosix(): bool
    {
        if (self::$safe)
        {
            return false;
        }

        // disable posix errors about unknown resource types
        if (function_exists('posix_isatty'))
        {
            set_error_handler(function (){});
            $isPosix = posix_isatty(STDIN);
            restore_error_handler();

            return $isPosix;
        }

        return true;
    }

    /**
     * Format string using ANSI escape sequences
     *
     * @param string $string
     * @param string $format defaults to 'none|none|none'
     *
     * @return string
     */
    private function format(string $string, string $format = null): string
    {
        // format only for POSIX
        if ( ! $format || ! $this->isPosix)
        {
            return $string;
        }

        $formats = $format ? explode('|', $format) : [];

        $code = array_filter([
            $this->backgrounds[$formats[1] ?? null] ?? null,
            $this->styles[$formats[2] ?? null] ?? null,
            $this->foregrounds[$formats[0] ?? null] ?? null,
        ]);

        $code = implode(';', $code);

        return "\033[{$code}m{$string}\033[0m";
    }

    /**
     * Writes dump to console.
     *
     * @param $message
     */
    public function write(string $message)
    {
        echo $this->format($message);
    }

    /**
     * Outputs formatted dump files.
     *
     * @param string $data
     */
    private function output(string $data)
    {
        # Gets line
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($bt as $key => $value)
        {
            if ($value['file'] != __FILE__)
            {
                unset($bt[$key]);
            }
            else
            {
                $bt = $bt[((int) $key) + 1];
                break;
            }
        }

        $file = "{$bt['file']}(line:{$bt['line']})";
        if ($this->isCli)
        {
            $this->write("{$file}\n{$data}");
        }
        else
        {
            echo "<code><small>{$file}</small><br />{$data}</code>";
        }
    }

    /**
     * Sets string color based on sapi.
     *
     * @param        $value
     * @param string $name
     *
     * @return string
     */
    private function color($value, string $name): string
    {
        if ($this->isCli)
        {
            return $this->format($value, $this->colors[$name][1]);
        }

        if ($name == 'type')
        {
            return "<small style=\"color:#{$this->colors[$name][0]}\">{$value}</small>";
        }
        elseif ($name == 'array' || $name == 'object')
        {
            $value = preg_replace('/(\[|\]|array|object)/', '<b>$0</b>', $value);
        }

        return "<span  style=\"color:#{$this->colors[$name][0]}\">{$value}</span>";
    }

    /**
     * Formats the data type.
     *
     * @param string $type
     * @param string $before
     *
     * @return string
     */
    private function type(string $type, string $before = ''): string
    {
        return "{$before}{$this->color($type, 'type')}";
    }

    /**
     * Move cursor to next line.
     *
     * @return string
     */
    private function breakLine(): string
    {
        return $this->isCli ? PHP_EOL : '<br />';
    }

    /**
     * Indents line content.
     *
     * @param int $pad
     *
     * @return string
     */
    private function indent(int $pad): string
    {
        return str_repeat($this->isCli ? ' ' : '&nbsp;', $pad);
    }

    /**
     * Adds padding to the line.
     *
     * @param int $size
     *
     * @return string
     */
    private function pad(int $size): string
    {
        return str_repeat($this->isCli ? ' ' : '&nbsp;', $size < 0 ? 0 : $size);
    }

    /**
     * Formats array index.
     *
     * @param      $key
     * @param bool $parent
     *
     * @return string
     */
    private function arrayIndex(string $key, bool $parent = false): string
    {
        return $parent
            ? "{$this->color("'{$key}'", 'single_array_key')} {$this->color('=>', 'single_array_arrow')} "
            : "{$this->color("'{$key}'", 'multi_array_key')} {$this->color('=', 'multi_array_arrow')} ";
    }

    /**
     * Formats array elements.
     *
     * @param array $array
     * @param bool  $obj_call
     *
     * @return string
     */
    private function formatArray(array $array, bool $obj_call): string
    {
        $tmp          = '';
        $this->indent += $this->pad_size;
        $break_line   = $this->breakLine();
        $indent       = $this->indent($this->indent);
        foreach ($array as $key => $arr)
        {
            if (is_array($arr))
            {
                $tmp .= "{$break_line}{$indent}{$this->arrayIndex((string) $key)} {$this->color('(size=' . count($arr) . ')', 'size')}";
                $new = $this->formatArray($arr, $obj_call);
                $tmp .= ($new != '') ? " {{$new}{$indent}}" : ' {}';
            }
            else
            {
                $tmp .= "{$break_line}{$indent}{$this->arrayIndex((string) $key, true)}{$this->evaluate([$arr], true)}";
            }
        }

        $this->indent -= $this->pad_size;
        if ($tmp != '')
        {
            $tmp .= $break_line;
            if ($obj_call)
            {
                $tmp .= $this->indent($this->indent);
            }
        }

        return $tmp;
    }

    /**
     * Gets the id of an object. (DIRTY)
     *
     * @param $object
     *
     * @return string
     */
    private function refcount($object): string
    {
        ob_start();
        debug_zval_dump($object);
        if (preg_match('/object\(.*?\)#(\d+)\s+\(/', ob_get_clean(), $match))
        {
            return $match[1];
        }

        return '';
    }

    /**
     * Formats object elements.
     *
     * @param $object
     * @return string
     */
    private function formatObject($object): string
    {
        if ($this->aboveNestLevel())
        {
            return $this->color('...', 'recursion');
        }

        $reflection   = new \ReflectionObject($object);
        $class_name   = $reflection->getName();
        $properties   = [];
        $tmp          = '';
        $inherited    = [];
        $max_indent   = 0;
        $comments     = '';

        while ($reflection)
        {
            $tmp_class_name = $reflection->getName();
            $max_indent     = max($max_indent, strlen($tmp_class_name));
            $comments      .= $reflection->getDocComment() ?: '';

            foreach($reflection->getProperties() as $prop)
            {
                $prop_name              = $prop->getName();
                $properties[$prop_name] = $prop;
                $inherited[$prop_name]  = $tmp_class_name == $class_name ? null : $tmp_class_name;
            }

            if (strpos($comments, '@dumpignore-inheritance') !== false)
            {
                break;
            }

            $reflection = $reflection->getParentClass();
        }

        $indent          = $this->indent($this->indent += $this->pad_size);
        $private_color   = $this->color('private', 'property_visibility');
        $protected_color = $this->color('protected', 'property_visibility');
        $public_color    = $this->color('public', 'property_visibility');
        $property_color  = $this->color(':', 'property_arrow');
        $arrow_color     = $this->color('=>', 'property_arrow');
        $string_pad_2    = $this->pad(2);
        $string_pad_3    = $this->pad(3);
        $line_break      = $this->breakLine();
        $hide_private    = strpos($comments, '@dumpignore-private') !== false;
        $hide_protected  = strpos($comments, '@dumpignore-protected') !== false;
        $hide_public     = strpos($comments, '@dumpignore-public') !== false;
        $hide_in_class   = strpos($comments, '@dumpignore-inherited-class') !== false;

        foreach ($properties as $name => $prop)
        {
            $prop_comment = $prop->getDocComment();
            if ($prop_comment && (strpos($prop_comment, '@dumpignore') !== false))
            {
                continue;
            }

            $from = '';
            if (! $hide_in_class && isset($inherited[$name]))
            {
                $name = $inherited[$name];
                $from = $this->color("[{$name}]", 'property_arrow');
                $from .= $this->indent($max_indent - strlen($name));
            }

            if ($prop->isPrivate())
            {
                if ($hide_private)
                {
                    continue;
                }

                $tmp .= "{$line_break}{$indent}{$private_color}{$string_pad_2} {$property_color} ";
            }
            elseif ($prop->isProtected())
            {
                if ($hide_protected)
                {
                    continue;
                }

                $tmp .= "{$line_break}{$indent}{$protected_color} {$property_color} ";
            }
            elseif ($prop->isPublic())
            {
                if ($hide_public)
                {
                    continue;
                }

                $tmp .= "{$line_break}{$indent}{$public_color}{$string_pad_3} {$property_color} ";
            }

            $prop->setAccessible(true);
            if (version_compare(PHP_VERSION, '7.4.0') >= 0)
            {
                $value = $prop->isInitialized($object) ? $this->getValue($prop, $object, $class_name) : $this->type('uninitialized');
            }
            else
            {
                $value = $this->getValue($prop, $object, $class_name);
            }

            $tmp .= "{$from} {$this->color("'{$prop->getName()}'", 'property_name')} {$arrow_color} {$value}";
        }

        if ($tmp != '')
        {
            $tmp .= $this->breakLine();
        }

        $this->indent -= $this->pad_size;
        $tmp .= ($tmp != '') ? $this->indent($this->indent) : '';

        $tmp =  str_replace([':name', ':id', ':content'], [
            $class_name,
            $this->color("#{$this->refcount($object)}", 'size'),
            $tmp
        ], $this->color('object (:name) [:id] [:content]', 'object'));

        return $tmp;
    }

    /**
     * Formats object property values.
     *
     * @param \ReflectionProperty $property
     * @param                     $object
     * @param string              $class_name
     *
     * @return string
     */
    private function getValue(ReflectionProperty $property, $object, string $class_name): string
    {
        $value = $property->getValue($object);
        # Prevent infinite loop caused by nested object property. e.g. when an object property is pointing to the same
        # object.
        if (is_object($value) && $value instanceof $object && $value == $object) {
            return "{$this->type($class_name)} {$this->color('::self', 'keyword')}";
        }

        return $this->evaluate([$value], true, true);
    }

    /**
     * Couples all formats.
     *
     * @param array $args
     * @param bool $called
     * @param bool $from_obj
     * @return string
     */
    private function evaluate(array $args, bool $called = false, bool $from_obj = false): string
    {
        $tmp        = null;
        $null_color = $this->color('null', 'null');

        foreach ($args as $each)
        {
            $type = gettype($each);
            switch ($type)
            {
                case 'string':
                    if (! $this->isCli)
                    {
                        $each = nl2br(str_replace(['<', ' '], ['&lt;', '&nbsp;'], $each));
                    }

                    $tmp .= "{$this->type("{$type}:" . strlen($each))} {$this->color("'{$each}'", $type)}";
                    break;
                case 'integer':
                case 'double':
                    $tmp .=  "{$this->type($type)} {$this->color((string) $each, $type)}";
                    break;
                case 'NULL':
                    $tmp .= "{$this->type($type)} {$null_color}";
                    break;
                case 'boolean':
                    $tmp .= "{$this->type($type)} {$this->color($each ? 'true' : 'false', $type)}";
                    break;
                case 'array':
                    $tmp .= str_replace([':size', ':content'], [
                        $this->color('(size=' . count($each) . ')', 'size'),
                        $this->formatArray($each, $from_obj)
                    ], $this->color('array :size [:content]', $type));
                    break;
                case 'object':
                    $tmp .= $this->formatObject($each);
                    break;
                case 'resource':
                    $resource_type = get_resource_type($each);
                    $resource_id   = (int) $each;
                    $tmp .= $this->color( "Resource[{$this->color("#{$resource_id}", 'integer')}]({$this->color($resource_type, $type)}) ", 'object');
                    break;
            }

            if (! $called)
            {
                $tmp .= $this->breakLine();
            }
        }

        return $tmp;
    }
}
