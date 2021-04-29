<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot\Channels;

use Mitoop\Robot\Exceptions\ChannelErrorException;

/**
 * 企业微信
 *
 * @notice 企业微信消息发送频率限制: 每个机器人发送的消息不能超过20条/分钟
 * @notice 发消息的大小限制: 目前未在文档找到, 不建议非常大的长度
 */
class WeComChannel extends Channel
{
    /**
     * @throws \Mitoop\Robot\Exceptions\ChannelErrorException
     */
    public function sendTextMsg($title, $content, array $at)
    {
        $message = $this->formatTextMessage($title, $content, $at);

        $result = $this->postJson($this->config['webhook'], $message, [
            'Content-Type' => 'application/json',
        ]);

        if ($this->isOk($result)) {
            return $result;
        }

        throw new ChannelErrorException('请求企业微信出错', 0, $result);
    }

    /**
     * @throws \Mitoop\Robot\Exceptions\ChannelErrorException
     */
    public function sendMarkdownMsg($content, array $at)
    {
        $message = $this->formatMarkdownMessage($content, $at);

        $result = $this->postJson($this->config['webhook'], $message, [
            'Content-Type' => 'application/json',
        ]);

        if ($this->isOk($result)) {
            return $result;
        }

        throw new ChannelErrorException('Robot请求企业微信出错', 0, $result);
    }

    protected function isOk($result)
    {
        return is_array($result) && isset($result['errcode']) && 0 == $result['errcode'];
    }

    protected function formatTextMessage($title, $content, $at)
    {
        $message = $this->formatGeneralTextMessage($title, $content);

        $mentionedList = $this->getMentionedList($at);
        foreach ($mentionedList as $k => $v) {
            // 企业微信是`@all` 外部接口统一为 all
            if ('all' == $v) {
                $mentionedList[$k] = '@all';
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

        if ($mentionedList = $this->getMentionedList($at)) {
            $content .= "\n";
            // 企业微信在markdown中@手机号码无效 可以直接@名字
            foreach ($mentionedList as $mentioned) {
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
