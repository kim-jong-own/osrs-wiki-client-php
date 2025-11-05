<?php

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Alias classes in common App\ namespaces
 */
spl_autoload_register(function ($class) {
    $baseNamespace = 'KimJongOwn\\OsrsWiki\\';

    if (str_starts_with($class, $baseNamespace)) {
        return false;
    }

    $localClass = $baseNamespace . $class;
    if (class_exists($localClass)) {
        return class_alias($localClass, $class);
    }

    $directories = glob(__DIR__ . '/src/*', GLOB_ONLYDIR);
    $namespaces = array_map(fn($dir) => $baseNamespace . basename($dir), $directories);

    foreach ($namespaces as $namespace) {
        $namespaceClass = $namespace . '\\' . $class;
        if (class_exists($namespaceClass)) {
            return class_alias($namespaceClass, $class);
        }
    }

    return false;
});

/**
 * Return config
 */
return [
    'startupMessage' => sprintf('<info>Using local config file (%s)</info>', __FILE__),
    'errorLoggingLevel' => E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED,
    'useBracketedPaste' => true,
];
