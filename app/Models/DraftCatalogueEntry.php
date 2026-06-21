<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DraftCatalogueEntry extends Model
{
    protected $table = 'draft_catalogue';

    protected $guarded = [];

    protected $casts = [
        'required_inputs'      => 'array',
        'optional_inputs'      => 'array',
        'forbidden_inputs'     => 'array',
        'statutes'             => 'array',
        'template_outline'     => 'array',
        'raw_ai_response'      => 'array',
        'reviewed_by_advocate' => 'boolean',
        'ai_confidence'        => 'float',
    ];
}
