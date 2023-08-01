<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot\Channels;

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

    protected function getBaseUrl()
    {
        return 'https://open.feishu.cn/open-apis/bot/v2/hook/';
    }

    protected function supportMarkdown()
    {
        return false;
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
                    'text' => is_int($k) ? (string)$v : sprintf('%s => %s', $k, $v),
                ],
            ];
        }

        $mentionedList = $at;
        if (!empty($mentionedList)) {
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
