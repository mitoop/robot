<?php

namespace Mitoop\Robot\Channels;

use Mitoop\Robot\Exceptions\ChannelErrorException;
use Mitoop\Robot\Exceptions\UnsupportedException;
use Mitoop\Robot\Support\Arr;
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

    abstract protected function getName();

    abstract protected function isOk($result);

    abstract protected function getBaseUrl();

    protected function getWebhook()
    {
        $hook = $this->config->get('webhook');

        if (strpos($hook, 'https://') === 0) {
            return $hook;
        }

        if ($baseUrl = $this->config->get('base_url')) {
            return rtrim($baseUrl, '/').ltrim($hook, '/');
        }

        return $this->getBaseUrl().ltrim($hook, '/');
    }

    protected function supportMarkdown()
    {
        return true;
    }

    public function sendRawMsg(array $data)
    {
        $response = $this->postJson($this->getWebhook(), $data, [
            'Content-Type' => 'application/json',
        ]);

        if ($this->isOk($response)) {
            return $response;
        }

        throw new ChannelErrorException(sprintf('Robot请求%s出错', $this->config->get('group')), 0, $response);
    }

    /**
     * @throws \Mitoop\Robot\Exceptions\ChannelErrorException
     */
    public function sendTextMsg($title, $content, $at)
    {
        $message = $this->formatTextMessage($title, $this->getTextContent($content), $this->getMentionedList($at));

        $response = $this->postJson($this->getWebhook(), $message, [
            'Content-Type' => 'application/json',
        ]);

        if ($this->isOk($response)) {
            return $response;
        }

        throw new ChannelErrorException(sprintf('Robot请求%s出错', $this->config->get('group')), 0, $response);
    }

    /**
     * @throws \Mitoop\Robot\Exceptions\ChannelErrorException
     * @throws \Mitoop\Robot\Exceptions\UnsupportedException
     */
    public function sendMarkdownMsg($content, $at)
    {
        if (! $this->supportMarkdown()) {
            throw new UnsupportedException(sprintf('%s不支持markdown消息', $this->getName()));
        }

        $message = $this->formatMarkdownMessage($this->getMarkdownContent($content), $this->getMentionedList($at));

        $response = $this->postJson($this->getWebhook(), $message, [
            'Content-Type' => 'application/json',
        ]);

        if ($this->isOk($response)) {
            return $response;
        }

        throw new ChannelErrorException(sprintf('Robot请求%s出错', $this->config->get('group')), 0, $response);
    }

    protected function getMentionedList($at)
    {
        $at = is_callable($at) ? $at($this) : (! empty($at) ? $at : $this->config->get('at', []));

        return array_unique(Arr::wrap($at));
    }

    protected function getTimeout()
    {
        return $this->config->get('timeout') ?: 3;
    }

    protected function formatGeneralTextMessage($title, $content)
    {
        $message = $title."\n\n";
        $message .= json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if ($this->config->get('show_env')) {
            $message = sprintf('[%s] %s', $this->config->get('env', ''), $message);
        }

        return $message;
    }

    protected function getMarkdownContent($content)
    {
        return is_callable($content) ? $content($this) : $content;
    }

    protected function getTextContent($content)
    {
        return Arr::wrap($content);
    }
}
