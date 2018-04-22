<?php

namespace LaravelFly\Tests\Map\Feature;


use LaravelFly\Tests\Map\MapTestCase;

class SuperGlobalVarsTest extends MapTestCase
{

    function testExceptMonoLogAndSymfony()
    {

        /**
         * drop this, because
         * in bash ,I can use  {} to exclude multiple dirs or files
         * but in php system(), --eclude-dir not working. The cms should be with escapeshellcmd, but test very slowly.
         *
         *
         */
        // $cmd = 'grep  --exclude={_ide_helper.php,PHPConsoleHandler.php}  --exclude=*.{js,css,md,txt} --exclude-dir={node_modules/,storage/,whoops/,eaglewu/,debugbar/,phpunit,examples,laravel-fly,global-state,Tests,symfony} -E "\b_(GET|POST|FILES|COOKIE|SESSION|REQUEST)\b" -r ' . static::$root;
        // passthru(escapeshellcmd($cmd));

        /**
         * drop this , because grep not support nested directories
         * https://unix.stackexchange.com/questions/226493/excluding-nested-directories-with-grep
         */
        // $cmd = 'grep  --exclude=_ide_helper.php --exclude=PHPConsoleHandler.php  --exclude=*.{js,css,md,txt} --exclude-dir=node_modules --exclude-dir=storage --exclude-dir=whoops --exclude-dir=eaglewu --exclude-dir=debugbar --exclude-dir=phpunit --exclude-dir=examples  --exclude-dir=laravel-fly --exclude-dir=global-state --exclude-dir=Tests --exclude-dir=symfony  -E "\b_(GET|POST|FILES|COOKIE|SESSION|REQUEST)\b" -r ' . static::$root;

        // -l let grep ouput filename
       $cmd = 'cd '. static::$root .' && find . -path ./node_modules -prune -o  \
       -path ./resources -prune -o  \
       -path ./bootstrap/cache/laravelfly_preload.php -prune -o    \
       -path ./storage -prune -o   \
       -path ./vendor/filp/whoops -prune -o   \
       -path ./vendor/eaglewu/swoole-ide-helper -prune -o   \
       -path ./vendor/maximebf/debugbar -prune -o   \
       -path ./vendor/phpunit/phpunit -prune -o   \
       -path ./vendor/predis/predis/examples  -prune -o   \
       -path ./vendor/scil/laravel-fly -prune -o   \
       -path ./vendor/sebastian/global-state -prune -o   \
       -path  ./vendor/symfony/http-foundation/Tests  -prune -o  \
       -path ./vendor/symfony/http-foundation  -prune -o  \
       -path ./vendor/monolog/monolog -prune -o   \
       -type f  \
       -exec grep -E "\b_(GET|POST|FILES|COOKIE|SESSION|REQUEST)\b"  \
           --exclude=*.md  \
           --exclude=_ide_helper.php   -l  {} \; ';

        ob_start();
        passthru($cmd);
        $output = ob_get_clean();

        self::assertEquals('', $output);

    }

    function testMonolog()
    {
        $cmd =  'cd '. static::$root .'/vendor/monolog/monolog   && grep -E "\b_(GET|POST|FILES|COOKIE|SESSION|REQUEST)\b" -r --exclude=*.md    . ';

        ob_start();
        passthru($cmd);
        $output = ob_get_clean();

        $respect = <<<'F'
./src/Monolog/Handler/PHPConsoleHandler.php:        'dataStorage' => null, // PhpConsole\Storage|null Fixes problem with custom $_SESSION handler(see http://goo.gl/Ne8juJ)

F;
        self::assertEquals($respect, $output);
    }

    function testSymfony()
    {
        // grep re explanation: starting  with [[:space:]] which not followed by / or * which are signs for comments
        $cmd =  'cd '. static::$root .'/vendor/symfony/http-foundation  &&  grep -E "^[[:space:]]+[^/*[:space:]].*\b_(GET|POST|FILES|COOKIE|SESSION|REQUEST)\b" -r --exclude=*.md  --exclude-dir=Tests -n  . ';

        ob_start();
        passthru($cmd);
        $output = ob_get_clean();

        $respect = <<<'F'
./Request.php:314:        $request = self::createRequestFromFactory($_GET, $_POST, array(), $_COOKIE, $_FILES, $server);
./Request.php:562:        $_GET = $this->query->all();
./Request.php:563:        $_POST = $this->request->all();
./Request.php:565:        $_COOKIE = $this->cookies->all();
./Request.php:576:        $request = array('g' => $_GET, 'p' => $_POST, 'c' => $_COOKIE);
./Request.php:581:        $_REQUEST = array();
./Request.php:583:            $_REQUEST = array_merge($_REQUEST, $request[$order]);
./Session/Storage/NativeSessionStorage.php:222:        $session = $_SESSION;
./Session/Storage/NativeSessionStorage.php:225:            if (empty($_SESSION[$key = $bag->getStorageKey()])) {
./Session/Storage/NativeSessionStorage.php:226:                unset($_SESSION[$key]);
./Session/Storage/NativeSessionStorage.php:229:        if (array($key = $this->metadataBag->getStorageKey()) === array_keys($_SESSION)) {
./Session/Storage/NativeSessionStorage.php:230:            unset($_SESSION[$key]);
./Session/Storage/NativeSessionStorage.php:244:            $_SESSION = $session;
./Session/Storage/NativeSessionStorage.php:272:        $_SESSION = array();
./Session/Storage/NativeSessionStorage.php:431:            $session = &$_SESSION;

F;
        self::assertEquals($respect, $output);



         $cmd = 'cd '. static::$root .' && find .  \
         -path ./node_modules -prune -o   \
         -path ./storage -prune -o   \
         -path  ./vendor/phpunit/phpunit -prune -o   \
         -path  ./vendor/scil/laravel-fly -prune -o    \
         -path  ./vendor/symfony/http-foundation/Tests  -prune  -o   \
         -type f  \
         -exec grep -E "\bcreateFromGlobals\b"  \
            --exclude=*.md  \
            --exclude=./bootstrap/cache/laravelfly_preload.php \
            --exclude=_ide_helper.php   -H -n {} \;  ';

        ob_start();
        passthru($cmd);
        $output = ob_get_clean();

        $respect = <<<'F'
./vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:738:        return $this->request ?: Request::createFromGlobals();
./vendor/laravel/framework/src/Illuminate/Http/Request.php:59:        return static::createFromBase(SymfonyRequest::createFromGlobals());
./vendor/symfony/http-foundation/Request.php:299:    public static function createFromGlobals()

F;

        self::assertEquals($respect, $output);

    }
}