# Extension to prevent reloading JS/CSS resources (on AJAX request)

This extension for Yii 2 prevent reload resources (on AJAX request), which already exist on client.
Similar extension for [yii 1.1.x](https://github.com/IStranger/yii-resource-smart-load).
This extension has more functionality than the Yii 2 native resource filter, [see detail](#advanced-use-case)

## Requirements

+ PHP 5.4.0+. 
+ YiiFramework 2.0.2+

## Features

+ Prevent reload of JS files
+ Prevent reload of JS inline blocks
+ Prevent reload of CSS files
+ Prevent reload of CSS inline blocks
+ Flexible configuration of resources disabling
+ Can disable native filtration of JS/CSS files

## Limitations

+ Increases incoming traffic (from client to server), because extension uses cookie and http headers.
This is especially important for sites with a large number of included resource files.
This can be adjusted by changing the hash method (see options of extension).
In addition it should be remembered that the size of the cookie is limited (in browser).
+ Extension does not work if the user has enabled filtering of http headers (for example, on the corporate proxy),
and browser not to accept cookies. However, we assume the probability of such events is low.

## Installation

+ **Installation via composer:** add to your composer.json file ("require" section) the following line  <code>"istranger/yii2-resource-smart-load": "*"</code>
  (see <a href="https://packagist.org/packages/istranger/yii2-resource-smart-load">packagist page</a>)
+ Add in config file (by default config/main.php):

```php
'components' => [
    // ...
    'view' => [
        'class'           => 'istranger\rSmartLoad\View',
        'smartLoadConfig' => [
            // Hashing method for resource names (if string value),
            // see possible values: http://php.net/manual/en/function.hash.php#104987
            // Can be assigned "callable", for example: function ($str) { return hash('sha256', $str); }
            // 'hashMethod'               => 'md5', // default = 'crc32b'

            // Types of resources, that will be tracked by current extension
            // If =null, include all resource types: ['jsFile', 'cssFile', 'jsInline', 'cssInline']
            // 'resourceTypes'            => ['jsFile', 'jsInline'],  // default = ['jsFile']

            // Enable log on server and client side (debug mode)
            // 'enableLog'                => true, // default = false

            // Activate "smart" disabling of resources on all pages
            // 'activateOnAllPages'       => true, // default = true

            // List of resources, that always should be loaded on client
            // (by name, hash, or full URL)
            // 'alwaysReloadableResources' => ['bootstrap.js'],  // default = []

            // Disable native script filter 
            // (only for resource types specified in 'resourceTypes')
            // 'disableNativeScriptFilter' => false, // default = true
        ]
    ],
    // ...
],
```

## Usage

### Typical use case

By default, this extension disables reloading of js files only, css files and js/css inline blocks not tracked 
(see option **resourceTypes**). But you can (carefully!) disable other resources types. 
That is, each "tracked" resource on the page will be loaded **only once**, even if it will later be removed from this page.
Therefore, all JS callbacks **on first load** should be bind to the global containers (for example, document) 
using jQuery-method **.on()**. 
At the subsequent AJAX requests already loaded CSS inline blocks (or CSS files) may be replaced by new content, 
therefore, in case you have problems with several CSS styles you configure exclusions (see below).

### Advanced use case

For the analysis of disabled/loaded scripts is convenient to use an option **enableLog**, 
that output useful debug information in browser console: 

```php
    'enableLog' => true, // default = false
```

You can more flexible manage resource loading on certain pages using methods: 

+ **Yii::$app->view->getRSmartLoad()->disableAllResources(array $types = null);**
    Disables loading of **all** resources. Calling this method disables loading all resources, 
    even if they will registered after calling this method.
+ **Yii::$app->view->getRSmartLoad()->disableLoadedResources(array $types = null);**
    Disables loading of resources, which **already loaded on client**. Calling this method disables loading 
    "client" resources, even if they will registered after calling this method.
    
These methods can be invoked in any actions. The argument **$types** is an array of resource types, 
that can be defined using constants:

```php
    array(
        RSmartLoad::RESOURCE_TYPE_JS_FILE,      // = 'jsFile'
        RSmartLoad::RESOURCE_TYPE_JS_INLINE,    // = 'jsInline'
        RSmartLoad::RESOURCE_TYPE_CSS_FILE,     // = 'cssFile'
        RSmartLoad::RESOURCE_TYPE_CSS_INLINE,   // = 'cssInline'
    )
```

In addition, you can set **activateOnAllPages = false**, and extension will be disabled on all pages. 
You will need to manually configure disabling of resources on certain pages (with the help of these methods).

Alternatively, you can configure exclusion list of resources:

```php
    'alwaysReloadableResources' => ['bootstrap.js']  // default = []
```

These resources always will be loaded on client. Each resource can be presented: 

+ resource file: as **hash**, or **full URL**, or **basename**.
+ resource inline block: as **hash**, or **resource content**.

The hash of specific resource can be get through browser console in the global object of extension (enableLog == true).
For example:
 
```javascript
yiiResourceSmartLoad.resources = 
  {
    "fd425af9": {
      "resource": "/test_yii/assets/62fbda6e/jquery.maskedinput.js",
      "hash": "fd425af9",
      "source": "2015-02-08 23:14:30,
		GET,
		url = /test_yii/index.php?r=site/ajaxForm,
		referrer = http://test.dev/test_yii/index.php?r=site/contact"
    },
    "5e030b8c": {
      "resource": "jQuery('#yw0').tabs({'collapsible':true});",
      "hash": "5e030b8c",
      "source": "2015-02-08 23:14:30,
		GET,
		url = /test_yii/index.php?r=site/ajaxForm,
		referrer = http://test.dev/test_yii/index.php?r=site/contact"
    },
    "5ce96349": {
      "resource": "(function ($) {   // yii-resource-smart-load extension
        window.yiiResourceSmartLoad.initExtension({\"...",
      "hash": "5ce96349",
      "source": "2015-02-08 23:14:30,
		GET,
		url = /test_yii/index.php?r=site/ajaxForm,
		referrer = http://test.dev/test_yii/index.php?r=site/contact"
    },
    "d60b9939": {
      "resource": "jQuery('#contact-form').yiiactiveform({'validateOnSubmit':true,'attributes':[{'id':'ContactForm_name...",
      "hash": "d60b9939",
      "source": "2015-02-08 23:29:09,
		GET/AJAX,
		url = /test_yii/index.php?r=site/contact,
		referrer = http://test.dev/test_yii/index.php?r=site/ajaxForm"
    }
  }
```


## Similar extensions. Native resource filtration script

To prevent reloading scripts for Yii 1.1.x you can use [nlsclientscript](https://github.com/nlac/nlsclientscript)
Similar approach used in [Yii 2 core](https://github.com/yiisoft/yii2/blob/master/framework/assets/yii.js#L50).

However, there are a few differences:

* Extension used different algorithm: at AJAX request duplicated resource files are deleted on the client **after**
receiving the content (not on the server, as in our realization). We assume that our approach is conceptually more correct.

* Our realization don't deletes (intentionally) the resources, which included directly in html code
(without registering through View). In this case, we assume that these resources are very necessary.

* Native filter don't deletes duplicate CSS files. But there is nothing wrong, because most browsers will not reload
the files.

* Native filter cannot prevent reload of JS/CSS inline blocks.

In our extension option **disableNativeScriptFilter** can partial disable native filter (only if necessary).