<?php

namespace Mitoop\Robot\Channels;

class LarkChannel extends FeiShuChannel
{
    protected function getName(): string
    {
        return 'lark';
    }

    protected function getBaseUrl(): string
    {
        return 'https://open.larksuite.com/open-apis/bot/v2/hook/';
    }
}
