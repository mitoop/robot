<?php

namespace Mitoop\Robot\Channels;

class FeiShuChannel extends Channel
{
    protected function getName(): string
    {
        return 'feishu';
    }

    protected function isOk($result): bool
    {
        return is_array($result) && isset($result['code']) && $result['code'] == 0;
    }

    protected function getBaseUrl(): string
    {
        return 'https://open.feishu.cn/open-apis/bot/v2/hook/';
    }

    protected function supportMarkdown(): bool
    {
        return false;
    }

    protected function generateSign($secret): array
    {
        $timestamp = time();
        $sign = base64_encode(hash_hmac('sha256', '', $timestamp."\n".$secret, true));

        return compact('timestamp', 'sign');
    }

    protected function formatTextMessage($title, $content, $at): array
    {
        $contentArr = [
            [
                'tag' => 'text',
                'text' => json_encode($content, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT),
            ],
        ];

        $mentionedList = $at;
        if (! empty($mentionedList)) {
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
