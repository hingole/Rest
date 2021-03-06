<?php

/*
 * This file is part of the Flintstones RestExtension.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flintstones\Rest;

use FOS\RestBundle\EventListener\BodyListener;
use FOS\RestBundle\EventListener\FormatListener;

use Silex\Application;
use Silex\ExtensionInterface;

use Symfony\Component\HttpKernel\KernelEvents as HttpKernelEvents;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class Extension implements ExtensionInterface
{
    public function register(Application $app)
    {
        $app['rest.serializer'] = $app->share(function () {
            $encoders = array (
                'json' => new JsonEncoder(),
                'xml'  => new XmlEncoder()
            );
            $serializer = new Serializer(array(), $encoders);
            return $serializer;
        });

        if (!isset($app['rest.priorities'])) {
            $app['rest.priorities'] = array('json', 'xml');
        }

        if (isset($app['rest.fos.class_path'])) {
            $app['autoloader']->registerNamespace('FOS\RestBundle', $app['rest.fos.class_path']);
        }

        if (isset($app['rest.serializer.class_path'])) {
            $app['autoloader']->registerNamespace('Symfony\Component\Serializer', $app['rest.serializer.class_path']);
        }

        $listener = new BodyListener($app['rest.serializer']);
        $app['dispatcher']->addListener(HttpKernelEvents::REQUEST, array($listener, 'onKernelRequest'));

        $app['dispatcher']->addListener(HttpKernelEvents::REQUEST, function () use ($app) {
            $listener = new FormatListener('html', $app['rest.priorities']);
            $app['dispatcher']->addListener(HttpKernelEvents::CONTROLLER, array($listener, 'onKernelController'), 10);
        });
    }
}
