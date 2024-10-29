<?php

namespace Mitoop\Robot;

use Closure;
use Mitoop\Robot\Exceptions\UnsupportedException;
use Mitoop\Robot\Support\Arr;
use Mitoop\Robot\Support\Config;

class Robot
{
    protected $config;

    protected $customChannels = [];

    protected $group;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    public function group($group)
    {
        $this->group = $group;

        return $this;
    }

    public function sendRawMsg(array $data)
    {
        return $this->getChannel()->sendRawMsg($data);
    }

    public function sendTextMsg($title, $content, $at = [])
    {
        return $this->getChannel()->sendTextMsg($title, $content, $at);
    }

    public function sendMarkdownMsg($content, $at = [])
    {
        return $this->getChannel()->sendMarkdownMsg($content, $at);
    }

    public function extend($name, Closure $closure)
    {
        $this->customChannels[$name] = $closure;

        return $this;
    }

    protected function getChannel(): Messenger
    {
        return new Messenger($this);
    }

    /**
     * @throws UnsupportedException
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

    public function getGroups(): array
    {
        $group = Arr::wrap($this->group ?? $this->config->get('default'));

        $this->group = null;

        return $group;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
