<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot;

use Closure;
use Mitoop\Robot\Channels\DingDingChannel;
use Mitoop\Robot\Channels\FeiShuChannel;
use Mitoop\Robot\Channels\WeComChannel;
use Mitoop\Robot\Exceptions\ChannelErrorException;
use Mitoop\Robot\Exceptions\InvalidArgumentException;
use Mitoop\Robot\Support\Config;

/**
 * Class Messenger.
 *
 * @method sendTextMsg($title, $content, $at)
 * @method sendMarkdownMsg($content, $at)
 */
class Messenger
{
    const STATUS_SUCCESS = 'success';

    const STATUS_FAILURE = 'failure';

    /**
     * @var \Mitoop\Robot\Robot
     */
    protected $robot;

    public function __construct(Robot $robot)
    {
        $this->robot = $robot;
    }

    /**
     * @throws \Mitoop\Robot\Exceptions\InvalidArgumentException
     * @throws \Mitoop\Robot\Exceptions\UnsupportedException
     */
    protected function getChannel($channelGroup)
    {
        $robot = $this->robot;

        list($channel, $group) = explode('.', $channelGroup);

        if (empty($channel) || empty($group)) {
            throw new InvalidArgumentException('group不正确: '.$channelGroup);
        }

        $robotConfig = $robot->getConfig();

        $groupConfig = $robotConfig->get(sprintf('channels.%s.groups.%s', $channel, $group));

        if (empty($groupConfig)) {
            throw new InvalidArgumentException('未找到group: '.$channelGroup);
        }

        $groupConfig['group'] = $channelGroup;
        $groupConfig['env'] = $robotConfig->get('env');
        $groupConfig['timeout'] = isset($groupConfig['timeout']) ? $groupConfig['timeout'] : $robotConfig->get('timeout');

        $groupConfig = new Config($groupConfig);

        switch ($channel) {
            case 'feishu':
                return new FeiShuChannel($groupConfig);
            case 'wecom':
                return new WeComChannel($groupConfig);
            case 'dingding':
                return new DingDingChannel($groupConfig);
            default:
                return $robot->createCustomChannel($channel, $groupConfig);
        }
    }

    protected function formatResult(Closure $closure)
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
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_FAILURE,
                'exception_msg' => $e->getMessage(),
                'exception_file' => sprintf('%s:%s', $e->getFile(), $e->getLine()),
                'response' => null,
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

    protected function send(Closure $closure)
    {
        $results = [];
        $groups = $this->robot->getGroup();
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
