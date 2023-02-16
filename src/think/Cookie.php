<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2021 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;

use DateTimeInterface;

/**
 * Cookie管理类
 * @package think
 */
class Cookie
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        // cookie 保存时间
        'expire'   => 0,
        // cookie 保存路径
        'path'     => '/',
        // cookie 有效域名
        'domain'   => '',
        //  cookie 启用安全传输
        'secure'   => false,
        // httponly设置
        'httponly' => false,
        // samesite 设置，支持 'strict' 'lax'
        'samesite' => '',
    ];

    /**
     * Cookie写入数据
     * @var array
     */
    protected $cookie = [];

    /**
     * 当前Request对象
     * @var Request
     */
    protected $request;

    /**
     * 构造方法
     * @access public
     */
    public function __construct(Request $request, $config = [])
    {
        $this->request = $request;
        $this->config  = array_merge($this->config, array_change_key_case($config));
    }

    public static function __make(Request $request, Config $config)
    {
        return new static($request, $config->get('cookie'));
    }

    /**
     * 获取cookie
     * @access public
     * @param  mixed  $name 数据名称
     * @param  string $default 默认值
     * @return mixed
     */
    public function get($name = '', $default = null)
    {
        return $this->request->cookie($name, $default);
    }

    /**
     * 是否存在Cookie参数
     * @access public
     * @param  string $name 变量名
     * @return bool
     */
    public function has($name)
    {
        return $this->request->has($name, 'cookie');
    }

    /**
     * Cookie 设置
     *
     * @access public
     * @param  string $name  cookie名称
     * @param  string $value cookie值
     * @param  mixed  $option 可选参数
     * @return void
     */
    public function set($name, $value, $option = null)
    {
        // 参数设置(会覆盖黙认设置)
        if (!is_null($option)) {
            if (is_numeric($option) || $option instanceof DateTimeInterface) {
                $option = ['expire' => $option];
            }

            $config = array_merge($this->config, array_change_key_case($option));
        } else {
            $config = $this->config;
        }

        if ($config['expire'] instanceof DateTimeInterface) {
            $expire = $config['expire']->getTimestamp();
        } else {
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
        }

        $this->setCookie($name, $value, $expire, $config);
    }

    /**
     * Cookie 保存
     *
     * @access public
     * @param  string $name  cookie名称
     * @param  string $value cookie值
     * @param  int    $expire 有效期
     * @param  array  $option 可选参数
     * @return void
     */
    protected function setCookie($name, $value, $expire, $option = [])
    {
        $this->cookie[$name] = [$value, $expire, $option];
    }

    /**
     * 永久保存Cookie数据
     * @access public
     * @param  string $name  cookie名称
     * @param  string $value cookie值
     * @param  mixed  $option 可选参数 可能会是 null|integer|string
     * @return void
     */
    public function forever($name, $value = '', $option = null)
    {
        if (is_null($option) || is_numeric($option)) {
            $option = [];
        }

        $option['expire'] = 315360000;

        $this->set($name, $value, $option);
    }

    /**
     * Cookie删除
     * @access public
     * @param  string $name cookie名称
     * @param  array  $options cookie参数
     * @return void
     */
    public function delete($name, $options = [])
    {
        $config = array_merge($this->config, array_change_key_case($options));
        $this->setCookie($name, '', time() - 3600, $config);
    }

    /**
     * 获取cookie保存数据
     * @access public
     * @return array
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * 保存Cookie
     * @access public
     * @return void
     */
    public function save()
    {
        foreach ($this->cookie as $name => $val) {
            list($value, $expire, $option) = $val;

            $this->saveCookie(
                $name,
                $value,
                $expire,
                $option['path'],
                $option['domain'],
                $option['secure'] ? true : false,
                $option['httponly'] ? true : false,
                $option['samesite']
            );
        }
    }

    /**
     * 保存Cookie
     * @access public
     * @param  string $name cookie名称
     * @param  string $value cookie值
     * @param  int    $expire cookie过期时间
     * @param  string $path 有效的服务器路径
     * @param  string $domain 有效域名/子域名
     * @param  bool   $secure 是否仅仅通过HTTPS
     * @param  bool   $httponly 仅可通过HTTP访问
     * @param  string $samesite 防止CSRF攻击和用户追踪
     * @return void
     */
    protected function saveCookie($name, $value, $expire, $path, $domain, $secure, $httponly, $samesite)
    {
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            setcookie($name, $value, [
                'expires'  => $expire,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ]);
        } else {
            setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }

}
