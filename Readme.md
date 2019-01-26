# Composer-Plugin for checked-in githooks

By default, git hooks are located in `.git/hooks` and are not checked in. Since git v2.9 it is possible to set a configuration variable `core.hooksPath` with a custom path. This composer plugin takes care to set the configuration variable to `githooks` of the current repository when `composer install` is run.

It is based on [CaptainHook](https://github.com/captainhookphp/captainhook).