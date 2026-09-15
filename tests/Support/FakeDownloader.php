<?php

namespace HankChen\Canvas\Tests\Support;

use HankChen\Canvas\Contracts\DownloaderInterface;

/**
 * 测试用下载器桩：返回预置内容并记录调用，用于在不上网的情况下覆盖远程资源逻辑
 */
class FakeDownloader implements DownloaderInterface
{
    /**
     * 已请求过的 URL 列表
     *
     * @var string[]
     */
    public $calls = [];

    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function download($url)
    {
        $this->calls[] = $url;

        return $this->content;
    }
}
