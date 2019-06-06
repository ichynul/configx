<?php

namespace Ichynul\Configx\Extend;

use Encore\Admin\Form\Field\Text;

class TestText extends Text
{
    protected $view = 'configx::extend.testtext';

    /**
     * Prepare for a field value before update or insert.
     *
     * @param $value
     *
     * @return mixed
     */
    public function prepare($value)
    {
        return preg_replace('/@Yuanfang/is', '', $value) . '@Yuanfang';
    }
}
