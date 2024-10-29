<?php

namespace Mitoop\Robot\Channels;

class DingDingChannel extends Channel
{
    protected function getName(): string
    {
        return 'dingding';
    }

    protected function isOk($result): bool
    {
        return is_array($result) && isset($result['errcode']) && $result['errcode'] == 0;
    }

    protected function getBaseUrl(): string
    {
        return 'https://oapi.dingtalk.com/robot/send?access_token=';
    }

    protected function getWebhook()
    {
        $webhook = parent::getWebhook();

        if ($secret = $this->config->get('secret')) {
            $webhook = rtrim($webhook, '&').$this->generateSign($secret);
        }

        return $webhook;
    }

    protected function formatTextMessage($title, $content, $at): array
    {
        $message = $this->formatGeneralTextMessage($title, $content);

        return [
            'msgtype' => 'text',
            'text' => [
                'content' => $message,
            ],
            'at' => $this->generateDingDingMentionedList($at),
        ];
    }

    protected function formatMarkdownMessage($content, $at): array
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
            'at' => $this->generateDingDingMentionedList($at),
        ];
    }

    protected function generateDingDingMentionedList($mentionedList): array
    {
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

    protected function generateSign($secret): string
    {
        $timestamp = time() * 1000; // 钉钉单位为毫秒
        $sign = urlencode(base64_encode(hash_hmac('sha256', $timestamp."\n".$secret, $secret, true)));

        return sprintf('&timestamp=%s&sign=%s', $timestamp, $sign);
    }
}
