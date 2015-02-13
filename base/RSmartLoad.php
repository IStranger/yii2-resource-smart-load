<?php

namespace istranger\rSmartLoad\base;

/**
 * Base class RSmartLoad
 *
 * @author  G.Azamat <m@fx4web.com>
 * @link    http://fx4.ru/
 * @link    https://github.com/IStranger/yii2-resource-smart-load
 */
abstract class RSmartLoad extends BaseObject
{
    /**
     * JS path in global namespace to extension client side
     */
    const JS_GLOBAL_OBJ_PATH = 'window.yiiResourceSmartLoad';

    const RESOURCE_TYPE_JS_FILE = 'jsFile';
    const RESOURCE_TYPE_JS_INLINE = 'jsInline';
    const RESOURCE_TYPE_CSS_FILE = 'cssFile';
    const RESOURCE_TYPE_CSS_INLINE = 'cssInline';

    /**
     * @var string[] Possible types of resources
     */
    static $resourceTypesAll = array(
        self::RESOURCE_TYPE_JS_FILE,
        self::RESOURCE_TYPE_JS_INLINE,
        self::RESOURCE_TYPE_CSS_FILE,
        self::RESOURCE_TYPE_CSS_INLINE,
    );

    /**
     * @return string Name of RequestReader class
     */
    public $requestReaderClassName;

    /**
     * @var string|callable Current hashing method (for resource names).<br/>
     *                      String - name of hashing method, possible values see {@link hash_algos} and
     *                          {@link http://php.net/manual/en/function.hash.php#104987} <br/>
     *                      Callback - function, that returned string hash:
     *                      <code> function ($str) { return hash('md5', $str); } </code>
     */
    public $hashMethod = 'crc32b';

    /**
     * @var string[] Types of resources, that will be tracked by current extension.
     * If =null, include all resource types {@link $resourceTypesAll}.
     */
    public $resourceTypes = array(self::RESOURCE_TYPE_JS_FILE);

    /**
     * @var string Enables log of registered/disabled resources (on server and client side)
     */
    public $enableLog = false;

    /**
     * @var string Activates "smart" disabling of resources on all pages.
     *             You can set =false, and call method {@link disableLoadedResources} in certain controllers/actions
     */
    public $activateOnAllPages = true;

    /**
     * @var string[] List of resources, that always should be loaded on client. Each resource can be presented: <br/>
     *               - resource file: as <b>hash</b>, or <b>full URL</b>, or <b>basename</b>.<br/>
     *               - resource inline block: as <b>hash</b>, or <b>resource content</b>.
     */
    public $alwaysReloadableResources = array();

    private $_resourceManager;
    private $_requestReader;

    abstract protected function writeLog($msg);

    /**
     * Registers client resources of this extension and corresponding scripts.
     * Should be register jQuery + publish 'resource_smart_load.js'
     */
    abstract protected function publishExtensionResources();


    /**
     * @param IResourceManager $resourceManager        Related resource manager instance
     *                                                 (ClientScript [yii 1.x] / View [yii 2.x])
     * @param array            $config                 Configuration of current object in format:
     *                                                 ['propName' => 'propValue']
     */
    public function __construct(IResourceManager $resourceManager, $config)
    {
        $this->setProperties($config);

        $this->_resourceManager = $resourceManager;
        $this->_requestReader = new $this->requestReaderClassName;

        $this->checkProperties();
    }

    /**
     * Checks properties values
     *
     * @throws \Exception   if some property contains incorrect value.
     */
    protected function checkProperties()
    {
        if (!$this->getRequestReader() instanceof RequestReader) {
            $this->throwException('Class for RequestReader should be inherit from "%baseClass%" ' .
                '(see SmartLoad option/property "%option%")', array(
                '%baseClass%' => RequestReader::className(),
                '%option%'    => 'requestReaderClassName'
            ));
        }

        $supportedHashAlgorithms = hash_algos();
        if (is_string($this->hashMethod) && !in_array($this->hashMethod, $supportedHashAlgorithms)) {
            $this->throwException('Incorrect hashing method (see SmartLoad option/property "%option%"). ' .
                'Supported hash algorithms: %hashAlgorithms% ', array(
                '%hashAlgorithms%' => join(', ', $supportedHashAlgorithms),
                '%option%'         => 'hashMethod'
            ));
        }

        // todo check other properties
    }

    /**
     * @return IResourceManager   Related resource manager instance
     *                            (ClientScript [yii 1.x] / View [yii 2.x])
     */
    public function getResourceManager()
    {
        return $this->_resourceManager;
    }

    /**
     * @return RequestReader   Object, that used for reading properties of current request
     */
    public function getRequestReader()
    {
        return $this->_requestReader;
    }

    public function init()
    {
        $this->publishExtensionResources();

        if ($this->activateOnAllPages) {
            $this->disableLoadedResources($this->resourceTypes);
        }
    }

    /**
     * Returns list of hashes of resources, which already loaded on client.
     * This list is sent every ajax-request in "client" variable "resourcesList" {@link Helper::getClientVar}
     * (see. resourceSmartLoad.getLoadedResources() in resource_smart_load.js)
     *
     * @return string[]     List of hashes (hashed full name of the resource).
     *                      If "client" variable not found, returns empty array()
     * @see resourcesmartload/resource_smart_load.js
     */
    public function getLoadedResourcesHashes()
    {
        $resourcesList = $this->getRequestReader()->getClientVar('resourcesList');
        return $resourcesList
            ? json_decode($resourcesList)
            : array();
    }

    /**
     * Disables loading of all resources.
     *
     * <b>ATTENTION!</b> Calling this method disables loading <u><b>all</b></u> resources,
     * even if they will registered after calling this method.
     *
     * @param string[] $types Types of resources, that should be disabled.
     *                        Possible values see {@link resourceTypesAll}.
     *
     * @see RSmartLoadClientScript::disableLoadedResources
     */
    public function disableAllResources(array $types = null)
    {
        $self = $this;
        $this->getResourceManager()->executeRightBeforeResourceRender(function ($resourceManager) use ($self, $types) {
            $self->_filterResourcesAndUpdateOnClient(array('*'), $types);
        });
    }

    /**
     * Disables loading of resources, which already loaded on client. <br/>
     * Used at AJAX requests. List of resource hashes obtained from "client" variable {@link Request::getClientVar}.
     *
     * <b>ATTENTION!</b> Calling this method disables loading <u><b>"client"</b></u> resources,
     * even if they will registered after calling this method.
     *
     * @param string[] $types Types of resources, that should be disabled.
     *                        Possible values see {@link resourceTypesAll}.
     *
     * @see RSmartLoadClientScript::disableAllResources
     */
    public function disableLoadedResources(array $types = null)
    {
        $self = $this;
        $this->getResourceManager()->executeRightBeforeResourceRender(function ($resourceManager) use ($self, $types) {
            $hashList = $self->getLoadedResourcesHashes();
            $self->_filterResourcesAndUpdateOnClient($hashList, $types);
        });
    }

    /**
     * Filters resources by $excludeList ({@link filterResourcesByType}) depending on given types.
     *
     * @param string[] $excludeList  List of resources, to be excluded.
     *                               Format of array see in corresponding specific methods.
     * @param string[] $types        Types of resources, that should be filtered.
     *                               Possible values see {@link resourceTypesAll}.
     *                               Other types will be included certainly.
     * @returns string[]             List of resources, that will be included (only for tracked $types).
     */
    protected function filterResourcesByType(array $excludeList, array $types = null)
    {
        $self = $this;
        $types = is_array($types) ? $types : static::$resourceTypesAll;

        $incResourceList = array();
        $this->getResourceManager()->resourceFilterByFn(
            function ($resourceId, $type) use ($self, $excludeList, $types, &$incResourceList) {
                $include = true;
                if (in_array($type, $types)) {
                    $include = $self->shouldBeLoaded($resourceId, $excludeList);
                    if ($include) {
                        $incResourceList[] = $resourceId;
                    }
                }
                //$self->_log(array('$resourceId' => $resourceId, '$type' => $type, '$include' => $include));
                return $include;
            });
        return $incResourceList;
    }

    /**
     * Checks, should be loaded given resource
     *
     * @param string   $resource      Resource ID: Full URL, basename, or hash of resource [for files],
     *                                Content, or hash of resource [for inline blocks]
     * @param string[] $excludeList   List of resources, that should be excluded
     * @return bool                   If =TRUE, given resource will be included in page
     */
    protected function shouldBeLoaded($resource, array $excludeList)
    {
        if (in_array('*', $excludeList)) {
            return false;
        }

        $possibleEntries = array($resource, $this->hashString($resource)); // for URLs + inline blocks
        if ($baseName = basename($resource)) { // for URLs
            $possibleEntries[] = $baseName;
        }
        return
            (count(array_intersect($possibleEntries, $this->alwaysReloadableResources)) > 0) || // is "always reloadable"
            (count(array_intersect($possibleEntries, $excludeList)) === 0);          // is not contained in $excludeList
    }

    /**
     * Hash of given string with current hash method {@link hashMethod}
     *
     * @param string $str String, that will be hashed
     * @return string       Hashed string
     */
    protected function hashString($str)
    {
        if (is_callable($this->hashMethod)) {
            return call_user_func($this->hashMethod, $str);
        } else {
            return hash($this->hashMethod, $str);
        }
    }

    /**
     * Filters resources by $excludeList ({@link filterResourcesByType}) and publishes js code,
     * that init extension and will update list of included resources (on client).
     * For details see {@link _publishExtensionClientInit} and {@link _publishRegisteredResourcesUpdater}
     *
     * @param string[] $excludeList  List of resources, to be excluded.
     *                               Format of array see in corresponding specific methods.
     * @param string[] $types        Types of resources, that should be filtered.
     *                               Possible values see {@link resourceTypesAll}.
     *                               Other types will be included certainly.
     */
    private function _filterResourcesAndUpdateOnClient(array $excludeList, array $types = null)
    {
        $incResources = $this->filterResourcesByType($excludeList, $types);
        $this->_publishExtensionClientInit();
        $this->_publishRegisteredResourcesUpdater($incResources);
    }


    /**
     * Returns prepared data on registered resources, and current request.
     * If options {@link enableLog} is TRUE, method include extended comment data.
     *
     * @param array $incResources Plain array of resources, that will be included in page (after filtration)
     *
     * @return array Array in format: [ ['resource' => ..., 'hash' => ..., 'comment' =>  ], ... ]
     */
    private function _prepareDataOnRegisteredResources(array $incResources)
    {
        // data on current request
        $self = $this;
        $requestInfo = $this->getRequestReader()->getMethod() . ($this->getRequestReader()->getIsAjax() ? '/AJAX' : '');
        $comment = $this->enableLog
            ? array(
                date('Y-m-d H:i:s'),
                $requestInfo,
                'url = ' . $this->getRequestReader()->getCurrentURL(),
                'referrer = ' . ($this->getRequestReader()->getReferrer() ?: '')
            )
            : array($requestInfo);

        // data on registered resources
        return Helper::createByFn($incResources,
            function ($key, $resource) use ($self, $comment) {
                return array($key, array(
                    'resource' => $self->_limitStr($resource, 100),
                    'hash'     => $self->hashString($resource),
                    'comment'  => join(',' . "\n", $comment)
                ));
            });
    }


    /**
     * Logs given array (to system log)
     *
     * @param array  $resources
     * @param string $msg Message for log
     */
    private function _log($resources, $msg = 'Disabled following resources:')
    {
        if ($this->enableLog) {
            $this->writeLog($msg . "\n" . var_export($resources, true));
        }
    }

    /**
     * Registers client script for initialization of client side (+ extension options export to client side)
     */
    private function _publishExtensionClientInit()
    {
        $extensionOptionsJson = json_encode(array(
            'hashMethod'                => is_string($this->hashMethod) ? $this->hashMethod : 'php callback function',
            'resourceTypes'             => $this->resourceTypes,
            'enableLog'                 => $this->enableLog,
            'activateOnAllPages'        => $this->activateOnAllPages,
            'alwaysReloadableResources' => $this->alwaysReloadableResources,
        ));
        $this->_publishExtensionJs('%extensionObject%.initExtension(%optionsJson%); ', array(
            '%extensionObject%' => self::JS_GLOBAL_OBJ_PATH,
            '%optionsJson%'     => $extensionOptionsJson,
        ));
    }

    /**
     * Publish script, that extended (on client) list of loaded resources
     *
     * @param array $incResources Plain array of resources, that will be included in page (after filtration)
     */
    private function _publishRegisteredResourcesUpdater(array $incResources)
    {
        $resourcesJson = json_encode($this->_prepareDataOnRegisteredResources($incResources));

        $this->_publishExtensionJs(
            array(
                '$(function () {',
                '    var app = %extensionObject%;',
                '    var resources = %resourcesJson%;',
                '    resources.map(function (dataObj) { ',
                '        app.addResource(dataObj.hash, dataObj.resource, dataObj.comment); ',
                '    });',
                '});',
            ),
            array(
                '%extensionObject%' => self::JS_GLOBAL_OBJ_PATH,
                '%resourcesJson%'   => $resourcesJson,
            )
        );
    }

    /**
     * Wraps in js-callback and registers given js code.
     * Used <b>only</b> for publication of scripts of <b>current extension</b>
     *
     * @param string|string[] $jsScriptLines Lines (or single line) of js code (will be joined EOL)
     * @param array           $replaceParams Params, that will be replaced in js code. Format: ['%from%' => 'to']
     */
    private function _publishExtensionJs($jsScriptLines, $replaceParams = array())
    {
        if (!is_array($jsScriptLines)) {
            $jsScriptLines = array($jsScriptLines);
        }
        array_unshift($jsScriptLines, '(function ($) {   // yii-resource-smart-load extension');
        array_push($jsScriptLines, '})(jQuery);');
        $scriptCode = join("\n", $jsScriptLines);
        $scriptCode = strtr($scriptCode, $replaceParams);

        $this->getResourceManager()->addPageJs($scriptCode);
    }

    private function _limitStr($str, $maxLength = 100)
    {
        return mb_substr($str, 0, $maxLength) . ((mb_strlen($str) > $maxLength) ? '...' : '');
    }
}