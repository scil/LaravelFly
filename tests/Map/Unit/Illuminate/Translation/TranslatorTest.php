<?php

namespace LaravelFly\Tests\Unit\Illuminate\Translation;


use LaravelFly\Tests\BaseTestCase;


class TranslatorTest extends BaseTestCase
{

    function testLocale()
    {
        $default_locale = 'zh';

        $this->requestAndTestAfterRoute(
            [
                [
                    'get',
                    static::testBaseUrl . 'test1',
                    function () {
                        $configLocale = \App::getLocale();
                        $transLocale  = app('translator')->getLocale();
                        $newLocale= 'en';
                        \App::setLocale($newLocale);
                        $configLocale2 = \App::getLocale();
                        $transLocale2  = app('translator')->getLocale();
                        return "config: $configLocale -> $configLocale2; trans: $transLocale -> $transLocale2";
                    }
                ],
                [
                    'get',
                    static::testBaseUrl . 'test2',
                    function () {
                        \Co::sleep(2);
                        $configLocale = \App::getLocale();
                        $transLocale  = app('translator')->getLocale();
                        return "config: $configLocale; trans: $transLocale";
                    }
                ],
            ],
            [
                static::testCurlBaseUrl . 'test1',
                static::testCurlBaseUrl . 'test2',
            ],
            [
                "config: $default_locale -> en; trans: $default_locale -> en",
                "config: $default_locale; trans: $default_locale"
            ]
        );

    }
}
