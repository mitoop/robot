<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot\Channels;

use Mitoop\Robot\Exceptions\UnsupportedException;

class FeiShuChannel extends Channel
{
    protected function getName()
    {
        return 'feishu';
    }

    protected function isOk($result)
    {
        return is_array($result) && isset($result['StatusCode']) && 0 == $result['StatusCode'];
    }

    /**
     * @throws \Mitoop\Robot\Exceptions\UnsupportedException
     */
    public function sendMarkdownMsg($content, $at)
    {
        throw new UnsupportedException('飞书目前不支持markdown消息');
    }

    protected function generateSign($secret)
    {
        $timestamp = time();
        $sign = base64_encode(hash_hmac('sha256', '', $timestamp."\n".$secret, true));

        return compact('timestamp', 'sign');
    }

    protected function formatTextMessage($title, $content, $at)
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
