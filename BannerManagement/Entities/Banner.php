<?php

namespace Modules\BannerManagement\Entities;
use MongoDB\Laravel\Eloquent\Model as Eloquent;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class Banner extends Eloquent
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'banners'; 

    protected $guarded = [];

    protected $dates = ['deletedAt', 'createdAt', 'updatedAt'];

    public $timestamps = true;
}