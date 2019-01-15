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
use Encore\Admin\Form\Field\Checkbox;
use Encore\Admin\Form\Field\Date;
use Encore\Admin\Form\Field\Time;
use Encore\Admin\Form\Field\Datetime;
use Encore\Admin\Form\Field\Image;
use Encore\Admin\Form\Field\Editor;
use Encore\Admin\Form\Field\Textarea;

class ConfigxController extends Controller
{
    public function edit(Content $content)
    {
        $configx_options = ConfigxModel::where('name', '__configx__')->first();
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
                    ['id' => 'element', 'name' => 'new_config_element', 'value' => ''],
                    ['id' => 'name', 'name' => 'new_config_key', 'value' => ''],
                    ['id' => 'options', 'name' => 'new_config_options', 'value' => '']
                );
            }
            foreach ($subs as $val) {
                $formhtml .= $this->createField($val, $extconf, $configx_options);
            }
            $formhtml .= '</div>';
            $tab->add($value, '<div class="row">' . $formhtml . '</div>', false);
        }
        $form = $this->createform($tab);
        $this->bindEvents();
        return $content
            ->header(trans('admin.configx.header'))
            ->description(trans('admin.configx.desc'))
            ->body('<div style="background-color:#fff;">' . $form . '</div>');
    }

    public function saveall(Request $request)
    {
        $configx_options = ConfigxModel::where('name', '__configx__')->first();
        if ($configx_options && $configx_options['description']) {
            $cx_options = json_decode($configx_options['description'], 1);
        }
        foreach ($request->values as $key => $value) {
            if (in_array($key, ['c_type', 'c_element', 'c_name', 'c_options'])) {
                continue;
            }
            $id = preg_replace('/^c_/i', '', $key);
            $config = ConfigxModel::findOrFail($id);
            if (isset($cx_options[$config['name']])) {
                $etype = $cx_options[$config['name']]['element'];
                if ($etype == 'image') {
                    $rowname = 'values.c_' . $config['id'];
                    $field = new Image($rowname, ['']);
                    $value = $field->prepare($value);
                } else if ($etype == 'checkbox_group') {
                    $value = implode(',', $value);
                }
            }
            $config->value = $value;
            $config->update();
        }
        if (!empty($request->values['c_type']) && !empty($request->values['c_name'])) {
            $new_key = $request->values['c_name'];
            if (!preg_match('/^' . $request->values['c_type'] . '\.\w{1,}/', $new_key)) {
                $new_key = $request->values['c_type'] . '.' . $new_key;
            }
            $config = new ConfigxModel(['name' => $new_key, 'value' => '1', 'description' => trans('admin.configx.' . $new_key)]);
            $config->save();
            $cx_options[$new_key] = ['options' => $request->values['c_options'] ? json_decode($request->values['c_options']) : [], 'element' => $request->values['c_element']];
            if ($configx_options) {
                $configx_options['description'] = json_encode($cx_options);
                $configx_options->save();
            } else {
                $configx_options = new ConfigxModel(['name' => '__configx__', 'description' => json_encode($cx_options), 'value' => 'do not delete']);
                $configx_options->save();
            }
            admin_toastr(trans('admin.save_succeeded'));
        } else {
            admin_toastr(trans('admin.update_succeeded'));
        }
        return redirect()->back();
    }

    protected function createField($val, $extconf, $configx_options)
    {
        $label = trans('admin.configx.' . $val['name']);
        $rowname = 'values.c_' . $val['id'];
        if ($val['id'] == 'type') {
            $field = new Radio($rowname, [$label]);
            array_pop($extconf['tabs']);
            $field->options($extconf['tabs']);
        } else if ($val['id'] == 'name') {
            $field = new Text($rowname, [$label]);
        } else if ($val['id'] == 'element') {
            $field = new Radio($rowname, [$label]);
            $elements = ['normal', 'date', 'time', 'datetime', 'image', 'yes_or_no', 'editor', 'radio_group', 'checkbox_group'];
            $support = [];
            foreach ($elements as $el) {
                $support[$el] = trans('admin.configx.element.' . $el);
            }
            $field->options($support)->default('normal');
        } else if ($val['id'] == 'options') {
            $field = new Textarea($rowname, [$label]);
            $field->help("options {\"key1\":\"text1\",\"key2\":\"text2\",..}");
        } else {
            if ($configx_options && $configx_options['description']) {
                $cx_options = json_decode($configx_options['description'], 1);
                if (!isset($cx_options[$val['name']])) {
                    $field = new Text($rowname, [$label]);
                    $field->value($val['value']);
                } else {
                    $etype = $cx_options[$val['name']]['element'];
                    if ($etype == 'image') {
                        $field = new Image($rowname, [$label]);
                    } else if ($etype == 'date') {
                        $field = new Date($rowname, [$label]);
                    } else if ($etype == 'time') {
                        $field = new Time($rowname, [$label]);
                    } else if ($etype == 'datetime') {
                        $field = new Datetime($rowname, [$label]);
                    } else if ($etype == 'yes_or_no') {
                        $field = new Radio($rowname, [$label]);
                        $field->options(['1' => trans('admin.yes'), '0' => trans('admin.no')]);
                    } else if ($etype == 'editor') {
                        $field = new Editor($rowname, [$label]);
                        if (!isset(Form::$availableFields['editor'])) {
                            admin_toastr('The editor unuseable !', 'warning');
                        }
                    } else if ($etype == 'radio_group') {
                        $field = new Radio($rowname, [$label]);
                        $field->options($cx_options[$val['name']]['options']);
                    } else if ($etype == 'checkbox_group') {
                        $field = new Checkbox($rowname, [$label]);
                        $field->options($cx_options[$val['name']]['options']);
                    } else {
                        $field = new Text($rowname, [$label]);
                    }
                    if ($etype == 'checkbox_group') {
                        $field->default(explode(',', $val['value']));
                    } else {
                        $field->value($val['value']);
                    }
                }

            } else {
                $field = new Text($rowname, [$label]);
                $field->value($val['value']);
            }
        }
        $html = $val['id'] == 'options' ? '<div class="row" style="display:none;" id="options_div">' : '<div class="row">';
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

    protected function bindEvents()
    {
        $script = <<<EOT
$("input:radio[name='values[c_type]']").on('ifChecked', function(event){
    $('input[name="values[c_name]"]').val(this.value?this.value + '.new_key_here':'');
});
$("input:radio[name='values[c_element]']").on('ifChecked', function(event){
    $("#options_div").css('display',this.value=='radio_group'||this.value=='checkbox_group'?'':'none');
});
EOT;
        Admin::script($script);
    }
}