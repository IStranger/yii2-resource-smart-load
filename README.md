# Extension to prevent reloading JS/CSS resources (on AJAX request)

This extension for Yii 2 prevent reload resources (on AJAX request), which already exist on client.
Similar extension for [yii 1.1.x](https://github.com/IStranger/yii-resource-smart-load).
This extension has more functionality than the Yii 2 native resource filter, [see detail](#similar-extensions-native-resource-filtration-script)

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
+ Add in config file (by default /config/web.php):

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

You can more flexible manage resource loading on certain pages using methods (see [examples](#examples)): 

- ``` Yii::$app->view->getRSmartLoad()->disableLoadedResources(array $types = null); ```
    Disables loading of resources, which **already loaded on client**. Calling this method disables loading 
    "client" resources, even if they will registered after calling this method.
- ``` Yii::$app->view->getRSmartLoad()->disableResources(array $resourceList, array $types = null); ```
    Disables loading of **given** resources. Calling this method disables loading given resources, 
    even if they will registered after calling this method. Resource list can contain:
    - for JS/CSS files: full URL, basename, or hash
    - for JS/CSS inline blocks: full content of block, or hash
    - ```array('*')``` - disables all resources
 
These methods can be invoked in any actions. The argument **$types** is an array of tracked resource types, 
that should be excluded from the page. By default (=null), tracked all types.  
Array can be defined using constants:

```php
    array(
        RSmartLoad::RESOURCE_TYPE_JS_FILE,      // = 'jsFile'
        RSmartLoad::RESOURCE_TYPE_JS_INLINE,    // = 'jsInline'
        RSmartLoad::RESOURCE_TYPE_CSS_FILE,     // = 'cssFile'
        RSmartLoad::RESOURCE_TYPE_CSS_INLINE,   // = 'cssInline'
    )
```
This restriction has higher priority than $resourceList.


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
yii.resourceSmartLoad.resources = {
    "89cb8371": {
        "resource": "/test_yii2/basic/web/assets/a5ccee6b/jquery.js",
        "hash": "89cb8371",
        "source": "2015-02-14 12:27:08,
            GET,
            url = http://test.dev/test_yii2/basic/web/index.php?r=site%2Fabout,
            referrer = http://test.dev/test_yii2/basic/web/index.php"
    },
    "4cc53fc1": {
        "resource": "/test_yii2/basic/web/assets/94203850/css/bootstrap.css",
        "hash": "4cc53fc1",
        "source": "2015-02-14 12:27:08,
            GET,
            url = http://test.dev/test_yii2/basic/web/index.php?r=site%2Fabout,
            referrer = http://test.dev/test_yii2/basic/web/index.php"
    },
    "b66631ac": {
        "resource": "jQuery('#w1').yiiGridView({\"filterUrl\":\"/test_yii2/basic/web/index.php?r=user%2Findex\",\"filterSelect...",
        "hash": "b66631ac",
        "source": "2015-02-14 12:27:12,
            GET/AJAX,
            url = http://test.dev/test_yii2/basic/web/index.php?r=user%2Findex,
            referrer = http://test.dev/test_yii2/basic/web/index.php?r=site%2Fabout"
    },
    "125a821d": {
        "resource": "<style>body {font-size: 110%;}</style>",
        "hash": "125a821d",
        "source": "2015-02-14 12:27:11,
            GET/AJAX,
            url = http://test.dev/test_yii2/basic/web/index.php?r=site%2Fcontact,
            referrer = http://test.dev/test_yii2/basic/web/index.php?r=site%2Fabout"
    }
}
```

### Examples

Examples of usage in controller actions (it is assumed that **activateOnAllPages = false**).

#### Disable load of all CSS inline blocks:
    
```php
Yii::$app->view->getRSmartLoad()->disableResources(['*'], [RSmartLoad::RESOURCE_TYPE_CSS_INLINE]);
```
Note: specified resources will be excluded from the page for all requests (not only AJAX).

#### Disable load of certain resources files (for all AJAX requests):

```php
if(Yii::$app->request->isAjax){
    Yii::$app->view->getRSmartLoad()->disableResources(['yii.gridView.js', 'bootstrap.css']);
}
```
Note: at normal request (not AJAX) these files will be included into page.

#### Disable load of certain resources files with restriction by type:

```php
Yii::$app->view->getRSmartLoad()->disableResources(['yii.gridView.js', 'bootstrap.css'],[
    RSmartLoad::RESOURCE_TYPE_CSS_INLINE,
    RSmartLoad::RESOURCE_TYPE_JS_FILE
]);
```
Note: will be disabled only ```'yii.gridView.js'```, because restriction by type has higher priority than $resourceList.

#### Disable load of JS inline blocks and JS files, which already exist on client:

```php
$view->getRSmartLoad()->disableLoadedResources(['*'], [
    RSmartLoad::RESOURCE_TYPE_JS_INLINE, 
    RSmartLoad::RESOURCE_TYPE_JS_FILE
]);
```
Note: resources can be disabled only on AJAX request.

#### Disable load of all resources, which already exist on client:

```php
$view->getRSmartLoad()->disableLoadedResources(['*']);
```

## Tests

Tests will be later.

## Similar extensions. Native resource filtration script

To prevent reloading scripts for Yii 1.1.x you can use [nlsclientscript](https://github.com/nlac/nlsclientscript).

Similar approach used in [Yii 2 core](https://github.com/yiisoft/yii2/blob/master/framework/assets/yii.js#L50).

However, there are a few differences:

* Extension used different algorithm: at AJAX request duplicated resource files are deleted on the client **after**
receiving the content (not on the server, as in our realization). We assume that our approach is conceptually more correct.

* Our realization don't deletes (intentionally) the resources, which included directly in html code
(without registering through View). In this case, we assume that these resources are very necessary.

* Native filter don't deletes duplicate CSS files. But there is nothing wrong, because most browsers will not reload
the files.

* Native filter cannot prevent reload of JS/CSS inline blocks.

Our extension does not interfere with native filter. They can be used together or separately.
In our extension option **disableNativeScriptFilter** can partial disable native filter (if necessary).