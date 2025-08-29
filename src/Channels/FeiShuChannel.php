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
        $lines = [];
        foreach ($content as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $lines[] = sprintf('**%s:**  %s', $this->escapeLarkMd($key), $this->escapeLarkMd($value));
        }

        $elements = [
            [
                'tag' => 'div',
                'text' => [
                    'tag' => 'lark_md',
                    'content' => implode("\n", $lines),
                ],
            ],
        ];

        if (! empty($at)) {
            $mentionText = implode(' ', array_map(function ($userId) {
                return "<at id={$userId}></at>";
            }, $at));
            $elements[] = [
                'tag' => 'div',
                'text' => [
                    'tag' => 'lark_md',
                    'content' => $mentionText,
                ],
            ];
        }

        if ($this->config->get('show_env')) {
            $title = sprintf('[%s] %s', $this->config->get('env', ''), $title);
        }

        $message = [
            'msg_type' => 'interactive',
            'card' => [
                'schema' => '2.0',
                'config' => [
                    'wide_screen_mode' => true,
                    'enable_forward' => true,
                ],
                'header' => [
                    'title' => [
                        'tag' => 'plain_text',
                        'content' => $title,
                    ],
                    'template' => 'blue',
                    'padding' => '8px 12px 8px 12px',
                ],
                'body' => [
                    'elements' => $elements,
                ],
            ],
        ];

        if ($secret = $this->config->get('secret')) {
            $message = array_merge($message, $this->generateSign($secret));
        }

        return $message;
    }

    protected function escapeLarkMd($text)
    {
        return preg_replace_callback(
            '/[-_*[\]()~`>#+|{}.!]/',
            function ($matches) {
                return '\\'.$matches[0];
            },
            (string) $text
        );
    }
}
