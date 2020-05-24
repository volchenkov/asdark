<?php

namespace App\Vk;

class Ad
{

    const FORMAT_TEXT = 1;
    const FORMAT_BIG_ING = 2;
    const FORMAT_PROMO = 4;
    const FORMAT_SPEC_FOR_GROUPS = 8;
    const FORMAT_GROUP_POST = 9;
    const FORMAT_ADAPTIVE = 11;

    const COST_TYPE_CLICKS = 0;
    const COST_TYPE_VIEWS = 1;
    const COST_TYPE_OPTIMIZED_VIEWS = 3;

    public int $format;
    public string $name;
    public int $campaignId;
    public int $costType;
    public int $goalType;
    public int $autobidding = 0;
    public float $cpm;
    public float $cpc;
    public float $ocpm;
    public float $dayLimit;
    public int $category1Id;
    public WallPostStealth $post;
    public AdTargeting $targeting;
    public ?int $id;

}
