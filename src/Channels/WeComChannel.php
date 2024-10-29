<?php

namespace Mitoop\Robot\Channels;

class WeComChannel extends Channel
{
    protected function getName()
    {
        return 'wecom';
    }

    protected function isOk($result)
    {
        return is_array($result) && isset($result['errcode']) && $result['errcode'] == 0;
    }

    protected function getBaseUrl()
    {
        return 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=';
    }

    protected function formatTextMessage($title, $content, $at)
    {
        $message = $this->formatGeneralTextMessage($title, $content);

        $mentionedList = $at;
        foreach ($mentionedList as $k => $v) {
            // 企业微信是`@all` 外部接口统一为 all
            if ($v == 'all') {
                $mentionedList[$k] = '@all';
                break;
            }
        }

        return [
            'msgtype' => 'text',
            'text' => [
                'content' => $message,
                'mentioned_mobile_list' => $mentionedList,
            ],
        ];
    }

    protected function formatMarkdownMessage($content, $at)
    {
        if ($this->config->get('show_env')) {
            $content = sprintf("**[%s]**  \n  %s", $this->config->get('env', ''), $content);
        }

        if (! empty($at)) {
            $content .= "\n";
            // 企业微信在markdown中@手机号码无效 可以直接@名字
            foreach ($at as $mentioned) {
                $content .= "<@{$mentioned}>";
            }
        }

        return [
            'msgtype' => 'markdown',
            'markdown' => [
                'content' => $content,
            ],
        ];
    }
}
