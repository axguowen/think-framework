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

namespace think\session;

use think\contract\SessionHandlerInterface;
use think\helper\Arr;

class Store
{

    /**
     * Session数据
     * @var array
     */
    protected $data = [];

    /**
     * 是否初始化
     * @var bool
     */
    protected $init = null;

    /**
     * 记录Session name
     * @var string
     */
    protected $name = 'PHPSESSID';

    /**
     * 记录Session Id
     * @var string
     */
    protected $id;

    /**
     * @var SessionHandlerInterface
     */
    protected $handler;

    /** @var array */
    protected $serialize = [];

    public function __construct($name, SessionHandlerInterface $handler, $serialize = null)
    {
        $this->name    = $name;
        $this->handler = $handler;

        if (!empty($serialize)) {
            $this->serialize = $serialize;
        }

        $this->setId();
    }

    /**
     * 设置数据
     * @access public
     * @param array $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * session初始化
     * @access public
     * @return void
     */
    public function init()
    {
        // 读取缓存数据
        $data = $this->handler->read($this->getId());

        if (!empty($data)) {
            $this->data = array_merge($this->data, $this->unserialize($data));
        }

        $this->init = true;
    }

    /**
     * 设置SessionName
     * @access public
     * @param string $name session_name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * 获取sessionName
     * @access public
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * session_id设置
     * @access public
     * @param string $id session_id
     * @return void
     */
    public function setId($id = null)
    {
        $this->id = is_string($id) && strlen($id) === 32 && ctype_alnum($id) ? $id : md5(microtime(true) . uniqid());
    }

    /**
     * 获取session_id
     * @access public
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 获取所有数据
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * session设置
     * @access public
     * @param string $name  session名称
     * @param mixed  $value session值
     * @return void
     */
    public function set($name, $value)
    {
        Arr::set($this->data, $name, $value);
    }

    /**
     * session获取
     * @access public
     * @param string $name    session名称
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return Arr::get($this->data, $name, $default);
    }

    /**
     * session获取并删除
     * @access public
     * @param string $name session名称
     * @return mixed
     */
    public function pull($name)
    {
        return Arr::pull($this->data, $name);
    }

    /**
     * 添加数据到一个session数组
     * @access public
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * 判断session数据
     * @access public
     * @param string $name session名称
     * @return bool
     */
    public function has($name)
    {
        return Arr::has($this->data, $name);
    }

    /**
     * 删除session数据
     * @access public
     * @param string $name session名称
     * @return void
     */
    public function delete($name)
    {
        Arr::forget($this->data, $name);
    }

    /**
     * 清空session数据
     * @access public
     * @return void
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * 销毁session
     */
    public function destroy()
    {
        $this->clear();

        $this->regenerate(true);
    }

    /**
     * 重新生成session id
     * @param bool $destroy
     */
    public function regenerate($destroy = false)
    {
        if ($destroy) {
            $this->handler->delete($this->getId());
        }

        $this->setId();
    }

    /**
     * 保存session数据
     * @access public
     * @return void
     */
    public function save()
    {
        $this->clearFlashData();

        $sessionId = $this->getId();

        if (!empty($this->data)) {
            $data = $this->serialize($this->data);

            $this->handler->write($sessionId, $data);
        } else {
            $this->handler->delete($sessionId);
        }

        $this->init = false;
    }

    /**
     * session设置 下一次请求有效
     * @access public
     * @param string $name  session名称
     * @param mixed  $value session值
     * @return void
     */
    public function flash($name, $value)
    {
        $this->set($name, $value);
        $this->push('__flash__.__next__', $name);
        $this->set('__flash__.__current__', Arr::except($this->get('__flash__.__current__', []), $name));
    }

    /**
     * 将本次闪存数据推迟到下次请求
     *
     * @return void
     */
    public function reflash()
    {
        $keys   = $this->get('__flash__.__current__', []);
        $values = array_unique(array_merge($this->get('__flash__.__next__', []), $keys));
        $this->set('__flash__.__next__', $values);
        $this->set('__flash__.__current__', []);
    }

    /**
     * 清空当前请求的session数据
     * @access public
     * @return void
     */
    public function clearFlashData()
    {
        Arr::forget($this->data, $this->get('__flash__.__current__', []));
        if (!empty($next = $this->get('__flash__.__next__', []))) {
            $this->set('__flash__.__current__', $next);
        } else {
            $this->delete('__flash__.__current__');
        }
        $this->delete('__flash__.__next__');
    }

    /**
     * 序列化数据
     * @access protected
     * @param mixed $data
     * @return string
     */
    protected function serialize($data)
    {
        $serialize = isset($this->serialize[0]) ? $this->serialize[0] : 'serialize';

        return $serialize($data);
    }

    /**
     * 反序列化数据
     * @access protected
     * @param string $data
     * @return array
     */
    protected function unserialize($data)
    {
        $unserialize = isset($this->serialize[1]) ? $this->serialize[1] : 'unserialize';

        return (array) $unserialize($data);
    }

}