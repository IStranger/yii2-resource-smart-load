Extension to prevent reloading resources (on AJAX request)
==========================================================
The extension for prevent reload (on AJAX request) resources, which already exist on client

    Note: extension under active development, will be uploaded later

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist istranger/yii2-resource-smart-load "*"
```

or add

```
"istranger/yii2-resource-smart-load": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \istranger\rSmartLoad\AutoloadExample::widget(); ?>```