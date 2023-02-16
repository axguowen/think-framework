<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2021 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Slince <taosikai@yeah.net>
// +----------------------------------------------------------------------

namespace think;

use Closure;
use InvalidArgumentException;
use LogicException;
use think\exception\Handle;
use Exception;

/**
 * 中间件管理类
 * @package think
 */
class Middleware
{
    /**
     * 中间件执行队列
     * @var array
     */
    protected $queue = [];

    /**
     * 应用对象
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 导入中间件
     * @access public
     * @param array  $middlewares
     * @param string $type 中间件类型
     * @return void
     */
    public function import($middlewares = [], $type = 'global')
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware, $type);
        }
    }

    /**
     * 注册中间件
     * @access public
     * @param mixed  $middleware
     * @param string $type 中间件类型
     * @return void
     */
    public function add($middleware, $type = 'global')
    {
        $middleware = $this->buildMiddleware($middleware, $type);

        if (!empty($middleware)) {
            $this->queue[$type][] = $middleware;
            $this->queue[$type]   = array_unique($this->queue[$type], SORT_REGULAR);
        }
    }

    /**
     * 注册路由中间件
     * @access public
     * @param mixed $middleware
     * @return void
     */
    public function route($middleware)
    {
        $this->add($middleware, 'route');
    }

    /**
     * 注册控制器中间件
     * @access public
     * @param mixed $middleware
     * @return void
     */
    public function controller($middleware)
    {
        $this->add($middleware, 'controller');
    }

    /**
     * 注册中间件到开始位置
     * @access public
     * @param mixed  $middleware
     * @param string $type 中间件类型
     */
    public function unshift($middleware, $type = 'global')
    {
        $middleware = $this->buildMiddleware($middleware, $type);

        if (!empty($middleware)) {
            if (!isset($this->queue[$type])) {
                $this->queue[$type] = [];
            }

            array_unshift($this->queue[$type], $middleware);
        }
    }

    /**
     * 获取注册的中间件
     * @access public
     * @param string $type 中间件类型
     * @return array
     */
    public function all($type = 'global')
    {
        return isset($this->queue[$type]) ? $this->queue[$type] : [];
    }

    /**
     * 调度管道
     * @access public
     * @param string $type 中间件类型
     * @return Pipeline
     */
    public function pipeline($type = 'global')
    {
        return (new Pipeline())
            ->through(array_map(function ($middleware) {
                return function ($request, $next) use ($middleware) {
                    list($call, $params) = $middleware;
                    if (is_array($call) && is_string($call[0])) {
                        $call = [$this->app->make($call[0]), $call[1]];
                    }
                    $response = call_user_func($call, $request, $next, ...$params);

                    if (!$response instanceof Response) {
                        throw new LogicException('The middleware must return Response instance');
                    }
                    return $response;
                };
            }, $this->sortMiddleware(isset($this->queue[$type]) ? $this->queue[$type] : [])))
            ->whenException([$this, 'handleException']);
    }

    /**
     * 结束调度
     * @param Response $response
     */
    public function end(Response $response)
    {
        foreach ($this->queue as $queue) {
            foreach ($queue as $middleware) {
                list($call) = $middleware;
                if (is_array($call) && is_string($call[0])) {
                    $instance = $this->app->make($call[0]);
                    if (method_exists($instance, 'end')) {
                        $instance->end($response);
                    }
                }
            }
        }
    }

    /**
     * 异常处理
     * @param Request   $passable
     * @param Exception $e
     * @return Response
     */
    public function handleException($passable, Exception $e)
    {
        /** @var Handle $handler */
        $handler = $this->app->make(Handle::class);

        $handler->report($e);

        return $handler->render($passable, $e);
    }

    /**
     * 解析中间件
     * @access protected
     * @param mixed  $middleware
     * @param string $type 中间件类型
     * @return array
     */
    protected function buildMiddleware($middleware, $type)
    {
        if (is_array($middleware)) {
            $tempMiddleware = $middleware;
            list($middleware, $params) = $tempMiddleware;
        }

        if ($middleware instanceof Closure) {
            return [$middleware, isset($params) ? $params : []];
        }

        if (!is_string($middleware)) {
            throw new InvalidArgumentException('The middleware is invalid');
        }

        //中间件别名检查
        $alias = $this->app->config->get('middleware.alias', []);

        if (isset($alias[$middleware])) {
            $middleware = $alias[$middleware];
        }

        if (is_array($middleware)) {
            $this->import($middleware, $type);
            return [];
        }

        return [[$middleware, 'handle'], isset($params) ? $params : []];
    }

    /**
     * 中间件排序
     * @param array $middlewares
     * @return array
     */
    protected function sortMiddleware($middlewares)
    {
        $priority = $this->app->config->get('middleware.priority', []);
        uasort($middlewares, function ($a, $b) use ($priority) {
            $aPriority = $this->getMiddlewarePriority($priority, $a);
            $bPriority = $this->getMiddlewarePriority($priority, $b);
            // 比较结果
            $result = $bPriority - $aPriority;
            // 解决PHP5中 uasort 排序不稳定的问题
            if($result == 0){
                $result = 1;
            }
            return $result;
        });
        return $middlewares;
    }

    /**
     * 获取中间件优先级
     * @param $priority
     * @param $middleware
     * @return int
     */
    protected function getMiddlewarePriority($priority, $middleware)
    {
        list($call) = $middleware;
        if (is_array($call) && is_string($call[0])) {
            $index = array_search($call[0], array_reverse($priority));
            return false === $index ? -1 : $index;
        }
        return -1;
    }

}
