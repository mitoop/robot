<?php

namespace Mitoop\Robot;

use Closure;
use Mitoop\Robot\Channels\DingDingChannel;
use Mitoop\Robot\Channels\FeiShuChannel;
use Mitoop\Robot\Channels\LarkChannel;
use Mitoop\Robot\Channels\WeComChannel;
use Mitoop\Robot\Exceptions\ChannelErrorException;
use Mitoop\Robot\Exceptions\InvalidArgumentException;
use Mitoop\Robot\Exceptions\UnsupportedException;
use Mitoop\Robot\Support\Config;

/**
 * Class Messenger.
 *
 * @method sendRawMsg(array $data)
 * @method sendTextMsg($title, $content, $at)
 * @method sendMarkdownMsg($content, $at)
 */
class Messenger
{
    const STATUS_SUCCESS = 'success';

    const STATUS_FAILURE = 'failure';

    protected $robot;

    public function __construct(Robot $robot)
    {
        $this->robot = $robot;
    }

    /**
     * @throws InvalidArgumentException
     * @throws UnsupportedException
     */
    protected function getChannel($channelGroup)
    {
        $robot = $this->robot;

        [$channel, $group] = explode('.', $channelGroup);

        if (empty($channel) || empty($group)) {
            throw new InvalidArgumentException('group不正确: '.$channelGroup);
        }

        $robotConfig = $robot->getConfig();

        $groupConfig = $robotConfig->get(sprintf('channels.%s.%s', $channel, $group));

        if (empty($groupConfig)) {
            throw new InvalidArgumentException('未找到group: '.$channelGroup);
        }

        $groupConfig['group'] = $channelGroup;
        $groupConfig['env'] = $robotConfig->get('env');
        $groupConfig['timeout'] = $groupConfig['timeout'] ?? $robotConfig->get('timeout');
        $groupConfig['show_env'] = $groupConfig['show_env'] ?? $robotConfig->get('show_env', true);
        $groupConfig['base_url'] = $robotConfig->get(sprintf('channels.%s.base_url', $channel));

        $groupConfig = new Config($groupConfig);

        switch ($channel) {
            case 'dingding':
                return new DingDingChannel($groupConfig);
            case 'feishu':
                return new FeiShuChannel($groupConfig);
            case 'lark':
                return new LarkChannel($groupConfig);
            case 'wecom':
                return new WeComChannel($groupConfig);
            default:
                return $robot->createCustomChannel($channel, $groupConfig);
        }
    }

    protected function formatResult(Closure $closure): array
    {
        try {
            return [
                'status' => self::STATUS_SUCCESS,
                'result' => $closure(),
            ];
        } catch (ChannelErrorException $e) {
            return [
                'status' => self::STATUS_FAILURE,
                'exception_msg' => $e->getMessage(),
                'exception_file' => sprintf('%s:%s', $e->getFile(), $e->getLine()),
                'response' => $e->getRawResponse(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => self::STATUS_FAILURE,
                'exception_msg' => $e->getMessage(),
                'exception_file' => sprintf('%s:%s', $e->getFile(), $e->getLine()),
                'response' => null,
            ];
        }
    }

    protected function send(Closure $closure): array
    {
        $results = [];
        $groups = $this->robot->getGroups();

        foreach ($groups as $group) {
            $results[$group] = $this->formatResult(function () use ($closure, $group) {
                return $closure($group);
            });
        }

        return $results;
    }

    public function __call($method, $args)
    {
        return $this->send(function ($group) use ($method, $args) {
            return $this->getChannel($group)->$method(...$args);
        });
    }
}
