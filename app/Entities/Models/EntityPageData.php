<?php

namespace BookStack\Entities\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $page_id
 */
class EntityPageData extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'page_id';
    public $incrementing = false;

    /**
     * Default values for columns which have no database-level default.
     */
    protected $attributes = [
        'draft'          => false,
        'template'       => false,
        'revision_count' => 0,
        'editor'         => '',
        'html'           => '',
        'text'           => '',
        'markdown'       => '',
    ];

    public static array $fields = [
        'draft',
        'template',
        'revision_count',
        'editor',
        'html',
        'text',
        'markdown',
    ];
}
