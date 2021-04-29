<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot\Channels;

use Mitoop\Robot\Exceptions\ChannelErrorException;

class DingDingChannel extends Channel
{
    /**
     * @throws \Mitoop\Robot\Exceptions\ChannelErrorException
     */
    public function sendTextMsg($title, $content, array $at)
    {
        $message = $this->formatTextMessage($title, $content, $at);

        $result = $this->postJson($this->getWebhook(), $message, [
            'Content-Type' => 'application/json',
        ]);

        if ($this->isOk($result)) {
            return $result;
        }

        throw new ChannelErrorException('请求钉钉出错', 0, $result);
    }

    /**
     * @throws \Mitoop\Robot\Exceptions\ChannelErrorException
     */
    public function sendMarkdownMsg($content, array $at)
    {
        $message = $this->formatMarkdownMessage($content, $at);

        $result = $this->postJson($this->getWebhook(), $message, [
            'Content-Type' => 'application/json',
        ]);

        if ($this->isOk($result)) {
            return $result;
        }

        throw new ChannelErrorException('Robot请求钉钉出错', 0, $result);
    }

    protected function getWebhook()
    {
        $webhook = $this->config->get('webhook');

        if ($secret = $this->config->get('secret')) {
            $webhook = rtrim($webhook, '&').$this->generateSign($secret);
        }

        return $webhook;
    }

    protected function formatTextMessage($title, $content, $at)
    {
        $message = $this->formatGeneralTextMessage($title, $content);

        return [
            'msgtype' => 'text',
            'text' => [
                'content' => $message,
            ],
            'at' => $this->getAt($at),
        ];
    }

    protected function formatMarkdownMessage($content, $at)
    {
        if ($this->config->get('show_env')) {
            $content = sprintf("**[%s]**  \n  %s", $this->config->get('env', ''), $content);
        }

        return [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => '新消息...',
                'text' => $content,
            ],
            'at' => $this->getAt($at),
        ];
    }

    protected function isOk($result)
    {
        return is_array($result) && isset($result['errcode']) && 0 == $result['errcode'];
    }

    protected function getAt($at)
    {
        $mentionedList = $this->getMentionedList($at);
        $isAtAll = in_array('all', $mentionedList);
        if ($isAtAll) {
            $key = array_search('all', $mentionedList);
            unset($mentionedList[$key]);
        }

        return [
            'atMobiles' => $mentionedList,
            'isAtAll' => $isAtAll,
        ];
    }

    protected function generateSign($secret)
    {
        $timestamp = time() * 1000; // 钉钉单位为毫秒
        $sign = urlencode(base64_encode(hash_hmac('sha256', $timestamp."\n".$secret, $secret, true)));

        return sprintf('&timestamp=%s&sign=%s', $timestamp, $sign);
    }
}
