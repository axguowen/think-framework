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

namespace think\exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Exception;

class FuncNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
    protected $func;

    public function __construct($message, $func = '', Exception $previous = null)
    {
        $this->message = $message;
        $this->func   = $func;

        parent::__construct($message, 0, $previous);
    }

    /**
     * 获取方法名
     * @access public
     * @return string
     */
    public function getFunc()
    {
        return $this->func;
    }
}
