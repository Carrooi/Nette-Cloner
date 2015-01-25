# Carrooi/Cloner

Extension for auto-copying assets or any other files to your project.

## Installation

```
$ composer require carrooi/cloner
$ composer update
```

Then just enable nette extension in your config.neon:

```neon
extensions:
	cloner: Carrooi\Cloner\DI\ClonerExtension
```

## Configuration

```neon
extensions:
	cloner: Carrooi\Cloner\DI\ClonerExtension

cloner:
	
	paths:
		- [%appDir%/../www/node_modules/test/lib, %appDir%/../www/js]
		- [%appDir%/../www/node_modules/jquery/jquery.js, %appDir%/../www/js/jquery.js]
```

There you can see simple configuration which will copy everything from `node_modules/test/lib` directory to our `js` directory also with `jquery.js` file.

Each "source" / "target" path must be in one array (not in pair), but there can be many sources / one target paths. 

Take a look at testing [configuration](https://github.com/Carrooi/Nette-Cloner/blob/master/tests/CarrooiTests/Cloner/config/cloner.neon) for all possible path options.

## Running

This extension don't do anything by default, so you have to enable it.

```neon
cloner:

	autoRun: true
```

Also you have to be in `debug` mode, or set `debug` options to `true`.

Now at every request all configured paths will be checked and files updated.

## Update command

It is not the best idea to check all files at each request so there is terminal command for that.

```
$ php www/index.php cloner:run --force
```

If you remove `--force` option, Cloner will just print found different files which needs to be updated.

You can also register this command as post install/update script in your composer.json. Then it will be started automatically by composer after each update or install. See more at composer [documentation](https://getcomposer.org/doc/articles/scripts.md).

## Changelog

* 1.0.0
	+ First version
