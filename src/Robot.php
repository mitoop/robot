<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot;

use Closure;
use Mitoop\Robot\Exceptions\UnsupportedException;
use Mitoop\Robot\Support\Arr;
use Mitoop\Robot\Support\Config;

class Robot
{
    /**
     * @var \Mitoop\Robot\Support\Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $customChannels = [];

    /**
     * @var string
     */
    protected $group;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * 设置机器人分组.
     *
     * @param  $group  string|array 机器人分组 如: `feishu.kehu` 发给飞书中的客服组
     * @return $this
     */
    public function group($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * 发送原始信息.
     *
     * @return array
     */
    public function sendRawMsg(array $data)
    {
        return $this->getChannel()->sendRawMsg($data);
    }

    /**
     * 发送文本信息.
     *
     * @param  string  $title  标题
     * @param  array|string  $content  消息
     * @param  array|string|Closure  $at  @人员 此处设置会覆盖配置中的`at`
     * @return array
     */
    public function sendTextMsg($title, $content, $at = [])
    {
        return $this->getChannel()->sendTextMsg($title, $content, $at);
    }

    /**
     * 发送markdown消息.
     *
     * @param  string|Closure  $content  markdown文本
     * @param  array|string|Closure  $at  @人员 此处设置会覆盖配置中的`at`
     * @return array
     */
    public function sendMarkdownMsg($content, $at = [])
    {
        return $this->getChannel()->sendMarkdownMsg($content, $at);
    }

    public function extend($name, Closure $closure)
    {
        $this->customChannels[$name] = $closure;

        return $this;
    }

    protected function getChannel()
    {
        return new Messenger($this);
    }

    /**
     * @throws \Mitoop\Robot\Exceptions\UnsupportedException
     */
    public function createCustomChannel($channel, Config $config)
    {
        if (isset($this->customChannels[$channel])) {
            return $this->callCustomChannel($channel, $config);
        }

        throw new UnsupportedException('不支持该通道: '.$channel);
    }

    protected function callCustomChannel($channel, Config $config)
    {
        return $this->customChannels[$channel]($config);
    }

    /**
     * @return array
     */
    public function getGroup()
    {
        $group = $this->group ?: $this->config->get('default');

        $this->group = null;

        return Arr::wrap($group);
    }

    /**
     * @return \Mitoop\Robot\Support\Config
     */
    public function getConfig()
    {
        return $this->config;
    }
}
