<?php

namespace App\Vk;

class WallPostStealth
{

    public int $ownerId;
    public string $message;
    public string $guid;
    public array $attachments = [];
    public int $signed = 0;
    public ?string $linkButton;
    public ?string $linkTitle;
    public ?string $linkImage;
    public ?string $linkVideo;

}
