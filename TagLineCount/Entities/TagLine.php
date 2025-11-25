<?php

namespace Modules\TagLineCount\Entities;
use MongoDB\Laravel\Eloquent\Model as Eloquent;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class TagLine extends Eloquent
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'taglines'; 

    protected $guarded = [];

    protected $dates = ['deletedAt', 'createdAt', 'updatedAt'];

    public $timestamps = false;
}