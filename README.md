PSR Match Example
=================

An example of PSR-X, PSR-R, and PSR Path Matching working together in order to
show how extracting the algortihm from PSR-X can be applied in other useful
ways including being used by PSR-R.

Clone and run `example.php`.

Given:

    src/
        Bat.php  // Foo\Bar\Bat "Foo Bar Bat!"
        Baz.php  // Foo\Bar\Baz "Foo Bar Baz!"
    overrides/
        Baz.php  // Foo\Bar\Baz "FOO BAR BAZ OVERRIDE!"

and mapping:

    {
        "Foo\\Bar": ["overrides", "src"]
    }

The following can be expeccted by running `example.php`:

    // var_dump(new Foo\Bar\Baz)
    object(Foo\Bar\Baz)#2 (1) {
      ["name"]=>
      string(21) "FOO BAR BAZ OVERRIDE!"
    }

    // var_dump(new Foo\Bar\Bat)
    object(Foo\Bar\Bat)#2 (1) {
      ["name"]=>
      string(12) "Foo Bar Bat!"
    }

    // findResource('classpath:///Foo/Bar/Baz.php')
    overrides/Baz.php

    // findResourceVariants('classpath:///Foo/Bar/Baz.php')
    Array
    (
        [0] => overrides/Baz.php
        [1] => src/Baz.php
    )

    // findResource('classpath:///Foo/Bar/Bat.php')
    src/Bat.php

    // findResourceVariants('classpath:///Foo/Bar/Bat.php')
    Array
    (
        [0] => src/Bat.php
    )

    // findResource('classpath:///Foo/Bar/')
    overrides

    // findResourceVariants('classpath:///Foo/Bar/')
    Array
    (
        [0] => overrides
        [1] => src
    )

    // findNamespacedResource('Foo\Bar\Baz.php')
    overrides/Baz.php

This is setup in the following way:

    $classLoader = new ClassLoader;

    // Register path mappings, still supports ordering.
    $classLoader->addNamespace("Foo\\Bar", "src");
    $classLoader->addNamespace("Foo\\Bar", "overrides", true);


    // Register the PSR-X autoloader
    $classLoader->register();


Purpose of this Example
-----------------------

The only thing that changed from the General-Purpose PSR-X Autoloader
implementation is that a trailing `\` is added to each path as it is added.

This example is not meant to be bulletproof. It is only meant to show that there
is a lot more in common between PSR-X and PSR-R, especially in the matching
algorithm, than might be immediately obvious without spending a lot of time
thinking about this.
