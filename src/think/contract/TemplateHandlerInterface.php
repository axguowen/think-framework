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

namespace think\contract;

/**
 * 视图驱动接口
 */
interface TemplateHandlerInterface
{
    /**
     * 检测是否存在模板文件
     * @access public
     * @param  string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists($template);

    /**
     * 渲染模板文件
     * @access public
     * @param  string $template 模板文件
     * @param  array  $data 模板变量
     * @return void
     */
    public function fetch($template, array $data = []);

    /**
     * 渲染模板内容
     * @access public
     * @param  string $content 模板内容
     * @param  array  $data 模板变量
     * @return void
     */
    public function display($content, array $data = []);

    /**
     * 配置模板引擎
     * @access private
     * @param  array $config 参数
     * @return void
     */
    public function config(array $config);

    /**
     * 获取模板引擎配置
     * @access public
     * @param  string $name 参数名
     * @return void
     */
    public function getConfig($name);
}
