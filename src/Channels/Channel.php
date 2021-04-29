<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot\Channels;

use Mitoop\Robot\Support\Config;
use Mitoop\Robot\Traits\HttpRequestTrait;

abstract class Channel
{
    use HttpRequestTrait;

    /**
     * @var \Mitoop\Robot\Support\Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    abstract protected function isOk($result);

    protected function getMentionedList($at)
    {
        return !empty($at) ? $at : $this->config->get('at', []);
    }

    protected function getTimeout()
    {
        return $this->config->get('timeout') ?: 3;
    }

    protected function formatGeneralTextMessage($title, $content)
    {
        $message = $title."\n\n";
        foreach ($content as $k => $v) {
            $v = is_array($v) ? var_export($v, true) : $v;
            $message .= is_int($k) ? $v : sprintf('%s => %s', $k, $v);
            $message .= "\n";
        }

        if ($this->config->get('show_env')) {
            $message = sprintf('[%s] %s', $this->config->get('env', ''), $message);
        }

        return $message;
    }
}
