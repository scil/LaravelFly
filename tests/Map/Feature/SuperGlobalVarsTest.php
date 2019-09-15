<?php

namespace LaravelFly\Tests\Map\Feature;


use LaravelFly\Tests\BaseTestCase as Base;

class SuperGlobalVarsTest extends Base
{

    // nexmo and guzzlehttp are required by laravel 5.7
    function testExceptMonoLogAndSymfonyAndNexmoAndGuzzlehttp()
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
       $cmd = 'cd '. static::$laravelAppRoot .' && find . -path ./node_modules -prune -o  \
       -path ./resources -prune -o  \
       -path ./bootstrap/cache/laravelfly_preload.php -prune -o    \
       -path ./storage -prune -o   \
       -path ./vendor/filp/whoops -prune -o   \
       -path ./vendor/eaglewu/swoole-ide-helper -prune -o   \
       -path ./vendor/maximebf/debugbar -prune -o   \
       -path ./vendor/phpunit/phpunit -prune -o   \
       -path ./vendor/predis/predis/examples  -prune -o   \
       -path ./vendor/scil/laravel-fly -prune -o   \
       -path ./vendor/scil/laravel-fly-local -prune -o   \
       -path ./vendor/scil/laravel-fly-files -prune -o   \
       -path ./vendor/scil/laravel-fly-files-local -prune -o   \
       -path ./vendor/scil/blog_for_test -prune -o   \
       -path ./vendor/hhxsv5/laravel-s -prune -o   \
       -path ./vendor/sebastian/global-state -prune -o   \
       -path  ./vendor/symfony/http-foundation/Tests  -prune -o  \
       -path ./vendor/symfony/http-foundation  -prune -o  \
       -path ./vendor/symfony/dom-crawler  -prune -o  \
       -path ./vendor/monolog/monolog -prune -o   \
       -path ./vendor/nexmo/client -prune -o   \
       -path ./vendor/zendframework/zend-diactoros -prune -o   \
       -path ./vendor/guzzlehttp/guzzle -prune -o   \
       -path ./vendor/guzzlehttp/psr7 -prune -o   \
       -path ./vendor/psr/http-message -prune -o   \
       -type f  \
       -exec grep -E "\b_(GET|POST|FILES|COOKIE|SESSION|REQUEST)\b"  \
           --exclude=*.md  \
           --exclude=_ide_helper.php   -l  {} \; ';

        ob_start();
        passthru($cmd);
        $output = ob_get_clean();

        self::assertEquals('', $output);

    }

    /**
    global vars:
    ./vendor/guzzlehttp/guzzle/src/Cookie/SessionCookieJar.php
    ./vendor/guzzlehttp/psr7/src/ServerRequest.php
    ./vendor/psr/http-message/src/ServerRequestInterface.php
    ./vendor/psr/http-message/src/UploadedFileInterface.php
     *
    ./vendor/nexmo/client/src/Client/Callback/Callback.php
    ./vendor/zendframework/zend-diactoros/src/functions/create_uploaded_file.php
    ./vendor/zendframework/zend-diactoros/src/functions/normalize_uploaded_files.php
    ./vendor/zendframework/zend-diactoros/src/Server.php
    ./vendor/zendframework/zend-diactoros/src/ServerRequestFactory.php
     */
    /**
     * createFromGlobals:
     ./vendor/nexmo/client/src/Message/InboundMessage.php:45:    public static function createFromGlobals()
     */
    function testPackagedAddedInLaravel57(){

    }

    function testMonolog()
    {
        $cmd =  'cd '. static::$laravelAppRoot .'/vendor/monolog/monolog   && grep -E "\b_(GET|POST|FILES|COOKIE|SESSION|REQUEST)\b" -r --exclude=*.md    . ';

        ob_start();
        passthru($cmd);
        $output = ob_get_clean();

        $respect = <<<'F'
./src/Monolog/Handler/PHPConsoleHandler.php:        'dataStorage' => null, // \PhpConsole\Storage|null Fixes problem with custom $_SESSION handler(see http://goo.gl/Ne8juJ)

F;
        self::assertEquals($respect, $output);
    }

    function testSymfony()
    {
        // grep re explanation: starting  with [[:space:]] which not followed by / or * which are signs for comments
        $cmd =  'cd '. static::$laravelAppRoot .'/vendor/symfony/http-foundation  &&  grep -E "^[[:space:]]+[^/*[:space:]].*\b_(SERVER|GET|POST|FILES|COOKIE|SESSION|REQUEST)\b" -r --exclude=*.md  --exclude-dir=Tests -n  . ';

        print('testSymfony to check global vars');
        print($cmd);
        ob_start();
        passthru($cmd);
        $output = ob_get_clean();

        $respect = <<<'F'
./Request.php:281:        $request = self::createRequestFromFactory($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);
./Request.php:533:        $_GET = $this->query->all();
./Request.php:534:        $_POST = $this->request->all();
./Request.php:535:        $_SERVER = $this->server->all();
./Request.php:536:        $_COOKIE = $this->cookies->all();
./Request.php:541:                $_SERVER[$key] = implode(', ', $value);
./Request.php:543:                $_SERVER['HTTP_'.$key] = implode(', ', $value);
./Request.php:547:        $request = ['g' => $_GET, 'p' => $_POST, 'c' => $_COOKIE];
./Request.php:552:        $_REQUEST = [[]];
./Request.php:555:            $_REQUEST[] = $request[$order];
./Request.php:558:        $_REQUEST = array_merge(...$_REQUEST);
./Session/Storage/Handler/AbstractSessionHandler.php:135:            if (null === $cookie || isset($_COOKIE[$this->sessionName])) {
./Session/Storage/NativeSessionStorage.php:245:        $session = $_SESSION;
./Session/Storage/NativeSessionStorage.php:248:            if (empty($_SESSION[$key = $bag->getStorageKey()])) {
./Session/Storage/NativeSessionStorage.php:249:                unset($_SESSION[$key]);
./Session/Storage/NativeSessionStorage.php:252:        if ([$key = $this->metadataBag->getStorageKey()] === array_keys($_SESSION)) {
./Session/Storage/NativeSessionStorage.php:253:            unset($_SESSION[$key]);
./Session/Storage/NativeSessionStorage.php:272:            if ($_SESSION) {
./Session/Storage/NativeSessionStorage.php:273:                $_SESSION = $session;
./Session/Storage/NativeSessionStorage.php:292:        $_SESSION = [];
./Session/Storage/NativeSessionStorage.php:454:            $session = &$_SESSION;

F;
        self::assertEquals($respect, $output);


         $cmd = 'cd '. static::$laravelAppRoot .' && find .  \
         -path ./node_modules -prune -o   \
         -path ./storage -prune -o   \
         -path  ./vendor/phpunit/phpunit -prune -o   \
         -path  ./vendor/scil/laravel-fly -prune -o    \
         -path  ./vendor/scil/laravel-fly-local -prune -o    \
         -path ./vendor/scil/laravel-fly-files -prune -o   \
         -path ./vendor/scil/laravel-fly-files-local -prune -o   \
         -path ./vendor/scil/blog_for_test -prune -o   \
         -path  ./vendor/swooletw  -prune  -o   \
         -path  ./vendor/symfony/http-foundation/Tests  -prune  -o   \
         -path  ./vendor/nexmo/client  -prune  -o   \
         -type f  \
         -exec grep -E "\bcreateFromGlobals\b"  \
            --exclude=*.md  \
            --exclude=./bootstrap/cache/laravelfly_preload.php \
            --exclude=_ide_helper.php   -H -n {} \;  ';

        ob_start();
        passthru($cmd);
        $output = ob_get_clean();

        $respect = <<<'F'
./vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:812:        return $this->request ?: Request::createFromGlobals();
./vendor/laravel/framework/src/Illuminate/Http/Request.php:59:        return static::createFromBase(SymfonyRequest::createFromGlobals());
./vendor/symfony/http-foundation/Request.php:279:    public static function createFromGlobals()

F;

        self::assertEquals($respect, $output);

    }
}