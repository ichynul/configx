<?php

namespace Ichynul\Configx;

use Encore\Admin\Widgets\Form;

class FormWgt extends Form
{
    /**
     * Width for label and submit field.
     *
     * @var array
     */
    protected $width = [
        'label' => 2,
        'field' => 4,
    ];

    /**
     * Store a new record.
     * 
     */
    public function store()
    {
        $data = \request()->all();
    }

    /**
     * Determine if form fields has files.
     *
     * @return bool
     */
    public function hasFile()
    {
        return true;
    }
}
