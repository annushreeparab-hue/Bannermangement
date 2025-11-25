<?php

namespace Modules\TagLineCount\Entities;
use MongoDB\Laravel\Eloquent\Model as Eloquent;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class TagLineCount extends Eloquent
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'taglinecounts'; 

    protected $guarded = [];

    protected $dates = ['deletedAt', 'createdAt', 'updatedAt'];

    public $timestamps = false;
}