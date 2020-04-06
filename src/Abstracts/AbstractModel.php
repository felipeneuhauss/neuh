<?php


namespace Neuh\Abstracts;

use Illuminate\Database\Eloquent\Model;
use Neuh\Traits\UuidModel;

class AbstractModel extends Model
{
    use UuidModel;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;
}
