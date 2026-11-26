<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    protected $primaryKey = 'portfolio_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'portfolio_id',
        'dataset',
        'code',
        'title',
        'summary',
        'sdg_num',
        'focus_group',
        'focus_area',
        'category',
        'layer',
        'total',
        'is_overview',
        'document_url',
        'title_normalized',
        'summary_is_template',
        'amount_unit',
        'amount_status', 'amount_source', 'amount_currency', 'amount_npr_bn', 'amount_basis',
        'layer_no', 'layer_name', 'parent_id', 'keywords', 'source_hash', 'imported_at',
    ];

    /** Cast database values to their expected PHP types. */
    protected function casts(): array
    {
        return [
            'portfolio_id' => 'integer',
            'sdg_num' => 'integer',
            'total' => 'decimal:2',
            'is_overview' => 'boolean',
            'summary_is_template' => 'boolean',
            'keywords' => 'array',
            'amount_npr_bn' => 'decimal:2',
        ];
    }
}
