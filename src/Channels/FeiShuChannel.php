<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot\Channels;

use Mitoop\Robot\Exceptions\ChannelErrorException;
use Mitoop\Robot\Exceptions\UnsupportedException;

/**
 * 飞书.
 *
 * @notice 飞书消息发送频率限制目前无限制
 * @notice 发消息的大小限制: 建议 JSON 的长度不超过 30k(已测, 超过限制发送失败)
 * @notice 飞书目前不支持markdown消息
 */
class FeiShuChannel extends Channel
{
    /**
     * @throws \Mitoop\Robot\Exceptions\ChannelErrorException
     */
    public function sendTextMsg($title, $content, array $at)
    {
        return $this->sendPostMsg($title, $content, $at);
    }

    /**
     * @throws \Mitoop\Robot\Exceptions\ChannelErrorException
     */
    protected function sendPostMsg($title, $content, array $at)
    {
        $message = $this->formatPostMessage($title, $content, $at);

        $result = $this->postJson($this->config->get('webhook'), $message, [
            'Content-Type' => 'application/json',
        ]);

        if ($this->isOk($result)) {
            return $result;
        }

        throw new ChannelErrorException('Robot请求飞书出错', 0, $result);
    }

    /**
     * @throws \Mitoop\Robot\Exceptions\UnsupportedException
     */
    public function sendMarkdownMsg($content, array $at)
    {
        throw new UnsupportedException('飞书目前不支持markdown消息');
    }

    protected function isOk($result)
    {
        return is_array($result) && isset($result['StatusCode']) && 0 == $result['StatusCode'];
    }

    protected function generateSign($secret)
    {
        $timestamp = time();
        $sign = base64_encode(hash_hmac('sha256', '', $timestamp."\n".$secret, true));

        return compact('timestamp', 'sign');
    }

    protected function formatPostMessage($title, $content, array $at)
    {
        $contentArr = [];
        foreach ($content as $k => $v) {
            $v = $this->exportVar($v);
            $contentArr[] = [
                [
                    'tag' => 'text',
                    'text' => is_int($k) ? $v : sprintf('%s => %s', $k, $v),
                ],
            ];
        }

        if (!empty($mentionedList = $this->getMentionedList($at))) {
            $at = [];
            foreach ($mentionedList as $mentioned) {
                $at[] = [
                    'tag' => 'at',
                    'user_id' => $mentioned,
                ];
            }

            $contentArr[] = $at;
        }

        if ($this->config->get('show_env')) {
            $title = sprintf('[%s] %s', $this->config->get('env', ''), $title);
        }

        $message = [
            'msg_type' => 'post',
            'content' => [
                'post' => [
                    'zh_cn' => [
                        'title' => $title,
                        'content' => $contentArr,
                    ],
                ],
            ],
        ];

        if ($secret = $this->config->get('secret')) {
            $message = array_merge($message, $this->generateSign($secret));
        }

        return $message;
    }
}
