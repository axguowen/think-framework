<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2021 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace think;

use InvalidArgumentException;
use think\helper\Str;

abstract class Manager
{
    /** @var App */
    protected $app;

    /**
     * 驱动
     * @var array
     */
    protected $drivers = [];

    /**
     * 驱动的命名空间
     * @var string
     */
    protected $namespace = null;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 获取驱动实例
     * @param null|string $name
     * @return mixed
     */
    protected function driver($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        if (is_null($name)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].',
                static::class
            ));
        }

        return $this->drivers[$name] = $this->getDriver($name);
    }

    /**
     * 获取驱动实例
     * @param string $name
     * @return mixed
     */
    protected function getDriver($name)
    {
        return isset($this->drivers[$name]) ? $this->drivers[$name] : $this->createDriver($name);
    }

    /**
     * 获取驱动类型
     * @param string $name
     * @return mixed
     */
    protected function resolveType($name)
    {
        return $name;
    }

    /**
     * 获取驱动配置
     * @param string $name
     * @return mixed
     */
    protected function resolveConfig($name)
    {
        return $name;
    }

    /**
     * 获取驱动类
     * @param string $type
     * @return string
     */
    protected function resolveClass($type)
    {
        if ($this->namespace || false !== strpos($type, '\\')) {
            $class = false !== strpos($type, '\\') ? $type : $this->namespace . Str::studly($type);
            if (class_exists($class)) {
                return $class;
            }
        }

        throw new InvalidArgumentException("Driver [$type] not supported.");
    }

    /**
     * 获取驱动参数
     * @param $name
     * @return array
     */
    protected function resolveParams($name)
    {
        $config = $this->resolveConfig($name);
        return [$config];
    }

    /**
     * 创建驱动
     *
     * @param string $name
     * @return mixed
     *
     */
    protected function createDriver($name)
    {
        $type = $this->resolveType($name);

        $method = 'create' . Str::studly($type) . 'Driver';

        $params = $this->resolveParams($name);

        if (method_exists($this, $method)) {
            return $this->$method(...$params);
        }
        
        $class = $this->resolveClass($type);

        return $this->app->invokeClass($class, $params);
    }

    /**
     * 移除一个驱动实例
     *
     * @param array|string|null $name
     * @return $this
     */
    public function forgetDriver($name = null)
    {
        $name = isset($name) ? $name : $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            if (isset($this->drivers[$cacheName])) {
                unset($this->drivers[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * 默认驱动
     * @return string|null
     */
    abstract public function getDefaultDriver();

    /**
     * 动态调用
     * @param string $method
     * @param array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
