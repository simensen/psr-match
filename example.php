<?php

//
// PSR Path Matching algorithm
//

function match_path($path, array $path_mappings, $separator, $return_on_first_match = true)
{
    // remember the length of the path
    $path_length = strlen($path);

    // first see if the complete path is mapped
    $path_prefix = $path;

    // path relative to the path prefix
    $relative_path = '';

    // the reverse offset of the separator dividing the path
    // prefix from the relative path
    $cursor = -1;

    $matches = array();

    while (true && abs($cursor) !== $path_length) {
        // are there any base paths for this path prefix?
        if (isset($path_mappings[$path_prefix])) {
            // look through base paths for this path prefix
            foreach ((array) $path_mappings[$path_prefix] as $base_path) {
                // separators must be replaced by directory separators
                $relative_path = strtr($relative_path, $separator, DIRECTORY_SEPARATOR);

                // create a potential match from the base path and the
                // relative path
                $potential_match = $base_path . $relative_path;

                // can we read the file from the file system?
                if (is_readable($potential_match)) {
                    // yes, we have a match
                    if ($return_on_first_match) {
                        // return first match
                        return $potential_match;
                    } else {
                        // append match to list of successful matches
                        $matches[] = $potential_match;
                    }
                }
            }
        }

        // once the cursor tested the first character, the
        // algorithm terminates
        if ($path_prefix === $separator) {
            return;
        }

        // place the cursor on the next separator to the left
        $cursor = strrpos($path, $separator, $cursor - 1) - $path_length;

        // the relative path is the part right of and including
        // the cursor, e.g. "/Parser.php"
        $relative_path = substr($path, $cursor);

        // the path prefix is the part left of and including
        // the cursor, e.g. "/Acme/Demo/"
        $path_prefix = substr($path, 0, $cursor + 1);
    }

    if ($return_on_first_match) {
        // return null if only wanted first match and no matches were found
        return null;
    }

    // return list of successful matches
    return $matches;
}


//
// PSR Resource Locator interface
//

interface ResourceLocatorInterface
{
    public function findResource($uri);

    public function findResourceVariants($uri);
}


//
// PSR-X Class Loader
// (that also happens to be a PSR-R Resource Locator)
//

class ClassLoader implements ResourceLocatorInterface
{
    protected $prefixes = array();

    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    public function addNamespace($prefix, $base, $prepend = false)
    {
        $prefix = trim($prefix, '\\')."\\";
        $base = rtrim($base, DIRECTORY_SEPARATOR);

        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = array();
        }

        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $base);
        } else {
            array_push($this->prefixes[$prefix], $base);
        }
    }

    //
    // This method satisfies the entirety of PSR-X
    //

    public function loadClass($class)
    {
        // remove any leading backslash
        $class = ltrim($class, '\\');

        if ($match = match_path($class.'.php', $this->prefixes, '\\')) {
            include $match;
        }
    }

    //
    // These two methods are the entirety of the PSR-R public interface
    //

    public function findResource($uri)
    {
        list ($scheme, $path) = $this->parseUri($uri);
        if ('classpath' === $scheme) {
            return match_path($path, $this->prefixes, '\\');
        }

        // intentionally leaving out other schemes for brevity
    }

    public function findResourceVariants($uri)
    {
        list ($scheme, $path) = $this->parseUri($uri);
        if ('classpath' === $scheme) {
            return match_path($path, $this->prefixes, '\\', false);
        }

        // intentionally leaving out other schemes for brevity
    }

    public function findNamespacedResource($namespacedResource)
    {
        // A completely native/separate namespaced file lookup scheme that
        // does not rely on PSR-R's classhpath scheme requirement.
        //
        // For example, one would just request Foo\Bar\Baz.php directly.
        return match_path($namespacedResource, $this->prefixes, '\\');
    }

    protected function parseUri($uri)
    {
        $index = strpos($uri, ':///');

        $scheme = substr($uri, 0, $index);
        $path = substr($uri, $index + 4);

        // translate the path to the separator we are using in our mapping.
        $path = strtr($path, '/', '\\');

        return array($scheme, $path);
    }
}


//
// Usage
//

$classLoader = new ClassLoader;

// Register path mappings, still supports ordering.
$classLoader->addNamespace("Foo\\Bar", "src");
$classLoader->addNamespace("Foo\\Bar", "overrides", true);


// Register the PSR-X autoloader
$classLoader->register();


//
// Instantiate some instances of some classes
//

// Shows override
echo "// var_dump(new Foo\Bar\Baz)\n";
var_dump(new Foo\Bar\Baz);
echo "\n";

// Shows expected
echo "// var_dump(new Foo\Bar\Bat)\n";
var_dump(new Foo\Bar\Bat);
echo "\n";


//
// Find some regular file resources (in this case, .php files)
//

echo "// findResource('classpath:///Foo/Bar/Baz.php')\n";
echo $classLoader->findResource('classpath:///Foo/Bar/Baz.php'). "\n\n";
echo "// findResourceVariants('classpath:///Foo/Bar/Baz.php')\n";
print_r($classLoader->findResourceVariants('classpath:///Foo/Bar/Baz.php'));
echo "\n";

echo "// findResource('classpath:///Foo/Bar/Bat.php')\n";
echo $classLoader->findResource('classpath:///Foo/Bar/Bat.php'). "\n\n";
echo "// findResourceVariants('classpath:///Foo/Bar/Bat.php')\n";
print_r($classLoader->findResourceVariants('classpath:///Foo/Bar/Bat.php'));
echo "\n";


//
// Find a directory resource
//

echo "// findResource('classpath:///Foo/Bar/')\n";
echo $classLoader->findResource('classpath:///Foo/Bar/'). "\n\n";
echo "// findResourceVariants('classpath:///Foo/Bar/')\n";
print_r($classLoader->findResourceVariants('classpath:///Foo/Bar/'));
echo "\n";


//
// Find a namespaced resource
echo "// findNamespacedResource('Foo\Bar\Baz.php')\n";
echo $classLoader->findNamespacedResource('Foo\\Bar\\Baz.php');
echo "\n";
