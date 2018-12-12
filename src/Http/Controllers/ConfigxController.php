<?php

namespace Ichynul\Configx\Http\Controllers;

use Encore\Admin\Layout\Content;
use Encore\Admin\Form;
use Encore\Admin\Facades\Admin;
use Illuminate\Routing\Controller;
use Ichynul\Configx\ConfigxModel;
use Encore\Admin\Config\Config;
use Ichynul\Configx\Configx;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Tab as Wtab;
use Encore\Admin\Form\Field\Text;
use Encore\Admin\Form\Field\Select;
use Encore\Admin\Form\Field\Radio;
use Encore\Admin\Form\Field\Date;
use Encore\Admin\Form\Field\Time;
use Encore\Admin\Form\Field\Datetime;
use Encore\Admin\Form\Field\Image;

class ConfigxController extends Controller
{
    public function edit(Content $content)
    {
        $extconf = Configx::config('config', [
            'tabs' => [
                'base' => 'base setting',
                'demo' => 'config tabs in /config/admin.php',
            ]
        ]);
        $extconf['tabs']['new_config'] = "+";
        $tab = new Wtab();
        foreach ($extconf['tabs'] as $key => $value) {
            $formhtml = '<div class="col-md-12">';
            $subs = ConfigxModel::group($key);
            if ($key == 'new_config') {
                $subs = array(
                    ['id' => 'type', 'name' => 'new_config_type', 'value' => ''],
                    ['id' => 'name', 'name' => 'new_config_key', 'value' => '']
                );
            }
            foreach ($subs as $val) {
                $formhtml .= $this->createField($val, $extconf);
            }
            $formhtml .= '</div>';
            $tab->add($value, '<div class="row">' . $formhtml . '</div>', false);
        }
        $form = $this->createform($tab);
        $this->typeChange();
        return $content
            ->header(trans('admin.configx.header'))
            ->description(trans('admin.configx.desc'))
            ->body('<div style="background-color:#fff;">' . $form . '</div>');
    }

    public function saveall(Request $request)
    {
        foreach ($request->values as $key => $value) {
            if (in_array($key, ['c_type', 'c_name'])) {
                continue;
            }
            $id = preg_replace('/^c_/i', '', $key);
            $config = ConfigxModel::findOrFail($id);
            if (preg_match('/_image$/i', $config['name'])) {
                $rowname = 'values.c_' . $config['id'];
                $field = new Image($rowname, ['']);
                $value = $field->prepare($value);
            }
            $config->value = $value;
            $config->update();
        }
        if (!empty($request->values['c_type']) && !empty($request->values['c_name'])) {
            $new_key = $request->values['c_name'];
            if (!preg_match('/^' . $request->values['c_type'] . '\.\w{1,}/', $new_key)) {
                //admin_toastr('config key error', 'error');
                //$request->flash();
                //return redirect()->back();
                $new_key = $request->values['c_type'] . '.' . $new_key;
            }
            $config = new ConfigxModel(['name' => $new_key, 'value' => trans('admin.configx.' . $new_key)]);
            $config->save();
            admin_toastr(trans('admin.save_succeeded'));
        } else {
            admin_toastr(trans('admin.update_succeeded'));
        }
        return redirect()->back();
    }

    protected function createField($val, $extconf)
    {
        $label = trans('admin.configx.' . $val['name']);
        $rowname = 'values.c_' . $val['id'];
        if ($val['id'] == 'type') {
            $field = new Select($rowname, [$label]);
            array_pop($extconf['tabs']);
            $field->options($extconf['tabs']);
        } else {
            if (preg_match('/_image$/i', $val['name'])) {
                $field = new Image($rowname, [$label]);
            } else if (preg_match('/_image_(\d+)_(\d+)$/i', $val['name'], $mach)) {
                $field = new Image($rowname, [$label]);
                //$field->crop($mach[1], $mach[2]);//it dose not work ?
            } else if (preg_match('/_date$/i', $val['name'])) {
                $field = new Date($rowname, [$label]);
            } else if (preg_match('/_time$/i', $val['name'])) {
                $field = new Time($rowname, [$label]);
            } else if (preg_match('/_datetime$/i', $val['name'])) {
                $field = new Datetime($rowname, [$label]);
            } else if (preg_match('/_if$/i', $val['name'])) {
                $field = new Radio($rowname, [$label]);
                $field->options(['1' => trans('admin.yes'), '0' => trans('admin.no')]);
            } else {
                $field = new Text($rowname, [$label]);
            }
            $field->value($val['value']);
        }
        $html = '<div class="row">';
        $html .= $field->render();
        $html .= '</div>';
        return $html;
    }

    protected function createform($tab)
    {
        $fields = [$tab];
        $html = [];
        $buttons = ['reset', 'submit'];
        $attributes = [
            'method' => 'POST',
            'action' => admin_base_path('configx/saveall'),
            'class' => 'form-horizontal',
            'accept-charset' => 'UTF-8',
            'pjax-container' => true,
            'enctype' => 'multipart/form-data'
        ];
        foreach ($attributes as $key => $val) {
            $html[] = "$key=\"$val\"";
        }
        $attributes_str = implode(' ', $html) ? : '';
        $data = [
            'fields' => $fields,
            'attributes' => $attributes_str,
            'method' => $attributes['method'],
            'buttons' => $buttons,
        ];
        return view('admin::widgets.form', $data);
    }

    protected function typeChange()
    {
        $script = <<<EOT
$(document).on('change', 'select[name="values[c_type]"]', function () {
    $('input[name="values[c_name]"]').val(this.value?this.value + '.new_key_here':'');
});
EOT;
        Admin::script($script);
    }
}