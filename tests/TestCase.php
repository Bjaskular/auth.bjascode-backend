<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

abstract class TestCase extends BaseTestCase
{
public function setUp(): void
    {
        parent::setUp();
        $this->registerTranslator();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerTranslator()
    {
        $this->redefineTranslation([]);

        $this->app->singleton('validator', function ($app) {
            $validator = new Factory($app['translator'], $app);

            if (isset($app['db'], $app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });
    }

    protected function redefineTranslation(array $paths = []): void
    {
        $this->app->singleton('translator', function ($app) use ($paths) {
            $loader = new FileLoader($app['files'], $paths);
            $locale = $app->getLocale();
            $trans = new Translator($loader, $locale);
            $trans->setFallback($app->getFallbackLocale());

            return $trans;
        });
    }
}
