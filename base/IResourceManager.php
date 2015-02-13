<?php

namespace istranger\rSmartLoad\base;

/**
 * Interface for "resourceManager":
 *      - for yii 1.x implements {@link \CClientScript}
 *      - for yii 2.x implements {@link \yii\web\View}
 *
 * @author  G.Azamat <m@fx4web.com>
 * @link    http://fx4.ru/
 * @link    https://github.com/IStranger/yii2-resource-smart-load       Yii 2.0.x ext
 * @link    https://github.com/IStranger/yii-resource-smart-load        Yii 1.1.x ext
 */
interface IResourceManager
{
    /**
     * Returns RSmartLoad instance
     *
     * @return RSmartLoad
     */
    public function getRSmartLoad();

    /**
     * Executes given callback function after all manipulations with resources,
     * right before render of resources (in this time moment will be filtered resources). Use events for this.
     *
     * Example of callback:
     * <code>
     * ...->executeRightBeforeResourceRender(function ($resourceManager) {
     *      // $resourceManager - instance of resource manager class
     * });
     * </code>
     *
     * @param callable $callback
     */
    public function executeRightBeforeResourceRender(\Closure $callback);

    /**
     * Executes given callback function for each resource, and if it returns = FALSE (non strict),
     * deletes current resource from resource list (that should be rendered), otherwise - keeps it.
     *
     * Example of callback:
     * <code>
     * ...->resourceFilterByFn(function ($resourceId, $type) {
     *      // $resourceId - unique Id of resource (url, content, hash...)
     *      // $type - type of resource (see RSmartLoad::$resourceTypesAll)
     *      return false; // or true
     * });
     * </code>
     *
     * @param callable $callback
     */
    public function resourceFilterByFn(\Closure $callback);

    /**
     * @param string $jsCode
     */
    public function addPageJs($jsCode);
}