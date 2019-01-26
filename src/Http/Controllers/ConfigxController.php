<?php

namespace Ichynul\Configx\Http\Controllers;

use Encore\Admin\Form;
use Ichynul\Configx\Configx;
use Illuminate\Http\Request;
use Encore\Admin\Config\Config;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Form\Field\Date;
use Encore\Admin\Form\Field\Rate;
use Encore\Admin\Form\Field\Text;
use Encore\Admin\Form\Field\Time;
use Ichynul\Configx\ConfigxModel;
use Encore\Admin\Form\Field\Image;
use Encore\Admin\Form\Field\Radio;
use Illuminate\Routing\Controller;
use Encore\Admin\Form\Field\Hidden;
use Encore\Admin\Form\Field\Number;
use Encore\Admin\Form\Field\Select;
use Encore\Admin\Form\Field\Checkbox;
use Encore\Admin\Form\Field\Datetime;
use Encore\Admin\Form\Field\Textarea;
use Encore\Admin\Widgets\Tab as Wtab;
use Illuminate\Support\Facades\Session;

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
            $formhtml = '<ol class="dd-list col-md-12">';
            $subs = ConfigxModel::group($key);
            if ($configx_options && $configx_options['description']) {
                $cx_options = json_decode($configx_options['description'], 1);
                $subs = $this->sortConfig($subs, $cx_options);
            }
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
            $formhtml .= '</ol>';
            $tab->add($value, ($key != 'new_config' ? '<div class="dd row">' : '<div class="row">') . $formhtml . '</div>', false);
        }
        $form = $this->createform($tab);
        $this->bindEvents();
        return $content
            ->header(trans('admin.configx.header'))
            ->description(trans('admin.configx.desc'))
            ->breadcrumb(
                ['text' => trans('admin.configx.header'), 'url' => '']
            )
            ->body('<div style="background-color:#fff;">' . $form . '</div>');
    }

    public function sortConfig($configs, $cx_options)
    {
        $order = [];
        foreach ($configs as $conf) {
            if (isset($cx_options[$conf['name']]) && isset($cx_options[$conf['name']]['order'])) {
                $order[] = $cx_options[$conf['name']]['order'] ? : 999;
            } else {
                $order[] = 999;
            }
        }
        array_multisort($order, SORT_ASC, $configs);
        return $configs;
    }

    public function saveall(Request $request)
    {
        $configx_options = ConfigxModel::where('name', '__configx__')->first();
        if ($configx_options && $configx_options['description']) {
            $cx_options = json_decode($configx_options['description'], 1);
        } else {
            $configx_options = $this->createConfigx();
        }
        \DB::beginTransaction();
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
            } else {
                $cx_options[$config['name']] = ['options' => [], 'element' => 'normal', 'order' => 999];
            }
            $config->value = $value;
            $config->update();
        }
        \DB::commit();
        if (!empty($request->values['c_type']) && !empty($request->values['c_name'])) {
            $new_key = $request->values['c_name'];
            $defaultVal = "1";
            if (!preg_match('/^' . $request->values['c_type'] . '\.\w{1,}/', $new_key)) {
                $new_key = $request->values['c_type'] . '.' . $new_key;
            }
            if ($request->values['c_element'] == "date") {
                $defaultVal = "2019-01-01";
            } else if ($request->values['c_element'] == "datetime") {
                $defaultVal = "2019-01-01 01:01:01";
            }
            if ($request->values['c_options']) {
                $c_options = explode("\r\n", $request->values['c_options']);
                $arr = [];
                foreach ($c_options as $op) {
                    $kv = explode(":", $op);
                    if (count($kv) > 1) {
                        $arr[$kv[0]] = $kv[1];
                    } else {
                        $arr[$kv[0]] = $kv[0];
                    }
                }
                $cx_options[$new_key] = ['options' => $arr, 'element' => $request->values['c_element'], 'order' => 999];
                $keys = array_keys($arr);
                if ($keys) {
                    $defaultVal = $keys[0];
                }
            } else {
                if (in_array($request->values['c_element'], ['radio_group', 'checkbox_group', 'select'])) {
                    admin_toastr('The options is empty !', 'error');
                    return redirect()->back();
                } else {
                    $cx_options[$new_key] = ['options' => [], 'element' => $request->values['c_element'], 'order' => 999];
                }
            }
            $config = new ConfigxModel(['name' => $new_key, 'value' => $defaultVal, 'description' => trans('admin.configx.' . $new_key)]);
            $config->save();
            admin_toastr(trans('admin.save_succeeded'));
        } else {
            admin_toastr(trans('admin.update_succeeded'));
        }
        $configx_options['description'] = json_encode($cx_options);
        $configx_options->save();
        Session::put('tabindex', $request->input('tabindex', 0));
        return redirect()->back();
    }

    public function sort(Request $request)
    {
        $configx_options = ConfigxModel::where('name', '__configx__')->first();
        if ($configx_options && $configx_options['description']) {
            $cx_options = json_decode($configx_options['description'], 1);
        } else {
            $configx_options = $this->createConfigx();
        }
        $data = $request->input('data');
        $i = 1;
        foreach ($data as $s) {
            $id = $s['id'];
            $config = ConfigxModel::findOrFail($id);
            if (isset($cx_options[$config['name']])) {
                $cx_options[$config['name']]['order'] = $i;
            } else {
                $cx_options[$config['name']] = ['options' => [], 'element' => 'normal', 'order' => $i];
            }
            $i += 5;
        }
        $configx_options['description'] = json_encode($cx_options);
        $configx_options->save();
        return response()->json(['status' => 1, 'message' => trans('admin.update_succeeded')]);
    }

    protected function createConfigx()
    {
        return new ConfigxModel(['name' => '__configx__', 'description' => '', 'value' => 'do not delete']);
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
            $elements = ['normal', 'date', 'time', 'datetime', 'image', 'yes_or_no', 'number', 'rate', 'editor', 'radio_group', 'checkbox_group', 'select'];
            $support = [];
            foreach ($elements as $el) {
                $support[$el] = trans('admin.configx.element.' . $el);
            }
            $field->options($support)->default('normal');
        } else if ($val['id'] == 'options') {
            $field = new Textarea($rowname, [$label]);
            $field->help("options <br/>text1<br/>text2<br/>...<br/>or<br/>key1:text1<br/>key2:text2<br/>...");
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
                        if (!isset(Form::$availableFields['editor'])) {
                            admin_toastr('The editor is unuseable !', 'warning');
                            $field = new Textarea($rowname, [$label]);
                        } else {
                            $field = new Form::$availableFields['editor']($rowname, [$label]);
                        }
                    } else if ($etype == 'number') {
                        $field = new Number($rowname, [$label]);
                    } else if ($etype == 'rate') {
                        $field = new Rate($rowname, [$label]);
                    } else if ($etype == 'radio_group') {
                        $field = new Radio($rowname, [$label]);
                        $field->options($cx_options[$val['name']]['options']);
                    } else if ($etype == 'checkbox_group') {
                        $field = new Checkbox($rowname, [$label]);
                        $field->options($cx_options[$val['name']]['options']);
                    } else if ($etype == 'select') {
                        $field = new Select($rowname, [$label]);
                        $field->options($cx_options[$val['name']]['options']);
                    } else {
                        $field = new Text($rowname, [$label]);
                    }
                    if ($etype == 'checkbox_group') {
                        $field->value(explode(',', $val['value']));
                    } else {
                        $field->value($val['value']);
                    }
                }

            } else {
                $field = new Text($rowname, [$label]);
                $field->value($val['value']);
            }
        }
        $html = !in_array($val['id'], ['type', 'options', 'element', 'name']) ? '<li class="dd-item" data-id="' . $val['id'] . '">'
            : ($val['id'] == 'options' ? '<li style="display:none;" id="options_li">'
            : '<li>');
        $html .= $field->render();
        $html .= '</li>';
        return $html;
    }

    protected function createform($tab)
    {
        $indexfield = new Hidden('tabindex', 'tabindex');
        $indexfield->value(Session::get('tabindex'));
        $indexfield->default(0);
        $fields = [$tab, $indexfield];
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
        $call_back = admin_base_path('configx/sort');
        $script = <<<EOT
$("input:radio[name='values[c_type]']").on('ifChecked', function(event){
    $('input[name="values[c_name]"]').val(this.value?this.value + '.new_key_here':'');
});
$("input:radio[name='values[c_element]']").on('ifChecked', function(event){
    $("#options_li").css('display',this.value=='radio_group'||this.value=='checkbox_group'||this.value=='select'?'':'none');
});
$("body").on("click",".nav.nav-tabs li",function(){
    var index = $(".nav.nav-tabs li").index(this);
    $("input[name='tabindex']").val(index);
});
var index = $(".nav.nav-tabs li").index($(".nav.nav-tabs li.active"));
var _index = $("input[name='tabindex']").val();
if(index != _index)
{
    $(".nav.nav-tabs li").eq(_index).find("a").trigger('click');
}
$('.dd').nestable({group: 1}).on('change', function(){ 
    var data = $(this).nestable('serialize'); 
    $('.dd-handle').removeClass('dd-handle');
    $.ajax({
        url: "{$call_back}",
        type: "POST",
        data: {
            data: data,
            _token: LA.token,
            _method: 'PUT'
        },
        success: function (data) {
            toastr.success(data.message);
        }
    });
});
$('.dd').dblclick(function(){
    $(this).find('li.dd-item .form-group').addClass('dd-handle');
});
EOT;
        Admin::script($script);
    }
}