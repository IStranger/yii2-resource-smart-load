<?php
/**
 * Created by PhpStorm.
 */

namespace istranger\rSmartLoad;


use yii\base\BootstrapInterface;
use yii\base\Application;

class BootstrapClass implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $app->on(Application::EVENT_BEFORE_REQUEST, function () {
            echo 'My BootstrapClass from extension';
        });
    }
}