<?php

namespace istranger\rSmartLoad;

use istranger\rSmartLoad\base;

/**
 * Specific View class for SmartLoad
 *
 * @author  G.Azamat <m@fx4web.com>
 * @link    http://fx4.ru/
 * @link    https://github.com/IStranger/yii2-resource-smart-load
 * @since   2.0.2
 */
class View extends \yii\web\View implements base\IResourceManager
{

    private $_rSmartLoad;

    /**
     * @var array   Config for SmartLoad object. <br/>
     *              Can be defined from app config (by default, /config/main.php).
     */
    public $smartLoadConfig = array(
        // 'class' => 'ClassNameForSmartLoad',
        // other properties of RSmartLoadClass
    );

    /**
     * Set default values for SmartLoad object config {@link smartLoadConfig} (if not defined)
     */
    protected function setDefaultSmartLoadConfig()
    {
        $this->smartLoadConfig = array_merge(array(
            'class'                     => RSmartLoad::className(),
            'disableNativeScriptFilter' => false,
            'requestReaderClassName'    => RequestReader::className()
        ), $this->smartLoadConfig);
    }

    public function init()
    {
        parent::init();
        $this->setDefaultSmartLoadConfig();
        if ($this->smartLoadConfig['disableNativeScriptFilter']) {
            $this->disableNativeScriptFilter();
        }
        $this->getRSmartLoad()->init();
    }

    /**
     * Returns RSmartLoad instance
     *
     * @return RSmartLoad
     */
    public function getRSmartLoad()
    {
        if ($this->_rSmartLoad === null) {
            $smartLoadClassName = $this->smartLoadConfig['class'];
            $config = base\Helper::filterByKeys($this->smartLoadConfig, null, ['class', 'disableNativeScriptFilter']);
            $this->_rSmartLoad = new $smartLoadClassName ($this, $config);
        }
        return $this->_rSmartLoad;
    }

    /**
     * @inheritdoc
     */
    public function executeRightBeforeResourceRender(\Closure $callback)
    {
        $self = $this;
        $this->on(self::EVENT_END_PAGE, function ($event) use ($self, $callback) {
            call_user_func($callback, $self);
        });
    }

    /**
     * @inheritdoc
     */
    public function addPageJs($jsCode)
    {
        \yii\web\JqueryAsset::register($this);
        $this->registerJs($jsCode, self::POS_END);
    }

    /**
     * @inheritdoc
     *
     * @see filterJSFiles
     * @see filterJSInline
     * @see filterCSSFiles
     * @see filterCSSInline
     */
    public function resourceFilterByFn(\Closure $callback)
    {
        $this->filterJSFiles($callback);
        $this->filterJSInline($callback);
        $this->filterCSSFiles($callback);
        $this->filterCSSInline($callback);
    }

    /**
     * Excludes from array {@link View::jsFiles} (registered JS files) given files of resources,
     * if given callback function returns =FALSE (non strict), otherwise keeps it.
     *
     * @param callable $callback Function, that executes for each array entry and returns bool value
     */
    protected function filterJSFiles(\Closure $callback)
    {
        if ($this->jsFiles) {
            $filteredJsFiles = array();
            foreach ($this->jsFiles as $position => $jsFilesGroup) {
                $filteredJsFiles[$position] = array();
                foreach ($jsFilesGroup as $key => $jsScriptTag) {
                    if (call_user_func($callback, $key, RSmartLoad::RESOURCE_TYPE_JS_FILE)) {
                        $filteredJsFiles[$position][$key] = $jsScriptTag;
                    }
                }
            }
            $this->jsFiles = $filteredJsFiles;
        }
    }


    /**
     * Excludes from array {@link View::js} (registered inline JS blocks) given resources,
     * if given callback function returns =FALSE (non strict), otherwise keeps it.
     *
     * @param callable $callback Function, that executes for each array entry and returns bool value
     */
    protected function filterJSInline(\Closure $callback)
    {
        if ($this->js) {
            $filteredJs = array();
            foreach ($this->js as $position => $jsGroup) {
                $filteredJs[$position] = array();
                foreach ($jsGroup as $key => $jsCode) {
                    if (call_user_func($callback, $key, RSmartLoad::RESOURCE_TYPE_JS_INLINE)) {
                        $filteredJs[$position][$key] = $jsCode;
                    }
                }
            }
            $this->js = $filteredJs;
        }
    }

    /**
     * Excludes from array {@link cssFiles} (registered CSS files) given files of resources,
     * if given callback function returns =FALSE (non strict), otherwise keeps it.
     *
     * @param callable $callback Function, that executes for each array entry and returns bool value
     */
    protected function filterCSSFiles(\Closure $callback)
    {
        if ($this->cssFiles) {
            $filteredCSSFiles = array();
            foreach ($this->cssFiles as $key => $cssLinkTag) {
                if (call_user_func($callback, $key, RSmartLoad::RESOURCE_TYPE_CSS_FILE)) {
                    $filteredCSSFiles[$key] = $cssLinkTag;
                }
            }
            $this->cssFiles = $filteredCSSFiles;
        }
    }

    /**
     * Excludes from array {@link css} (registered inline CSS blocks) given resources,
     * if given callback function returns =FALSE (non strict), otherwise keeps it.
     *
     * @param callable $callback Function, that executes for each array entry and returns bool value
     */
    protected function filterCSSInline(\Closure $callback)
    {
        if ($this->css) {
            $filteredCSS = array();
            foreach ($this->css as $key => $cssStyleTag) {
                if (call_user_func($callback, $key, RSmartLoad::RESOURCE_TYPE_CSS_INLINE)) {
                    $filteredCSS[$key] = $cssStyleTag;
                }
            }
            $this->css = $filteredCSS;
        }
    }

    /**
     * Disables native filter of duplicate js/css resources (on AJAX request)
     */
    protected function disableNativeScriptFilter()
    {
        $this->registerJs(join("\n", [
            '(function(){ // yii-resource-smart-load extension',
            '   var app = ' . RSmartLoad::JS_GLOBAL_OBJ_PATH . ';',
            '   var parentFunc = app.addResource;',
            '   app.addResource = function(hash, resource, comment){',
            '       var isAlreadyLoaded = Boolean(app.getResourceByHash(hash));',
            '       if(!isAlreadyLoaded){',
            '           yii.reloadableScripts.push(resource);',
            '       }',
            '       parentFunc.apply(this, arguments);',
            '   } ',
            '})();'
        ]), self::POS_END);
    }
}