<?php

namespace Ichynul\Configx\Http\Controllers;

use Encore\Admin\Form;
use Ichynul\RowTable\Table;
use Ichynul\Configx\Configx;
use Illuminate\Http\Request;
use Ichynul\RowTable\TableRow;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Form\Field\Date;
use Encore\Admin\Form\Field\File;
use Encore\Admin\Form\Field\Html;
use Encore\Admin\Form\Field\Icon;
use Encore\Admin\Form\Field\Rate;
use Encore\Admin\Form\Field\Tags;
use Encore\Admin\Form\Field\Text;
use Encore\Admin\Form\Field\Time;
use Ichynul\Configx\ConfigxModel;
use Encore\Admin\Form\Field\Color;
use Encore\Admin\Form\Field\Image;
use Encore\Admin\Form\Field\Radio;
use Illuminate\Routing\Controller;
use Encore\Admin\Form\Field\Hidden;
use Encore\Admin\Form\Field\Number;
use Encore\Admin\Form\Field\Select;
use Encore\Admin\Form\Field\Listbox;
use Encore\Admin\Form\Field\Checkbox;
use Encore\Admin\Form\Field\Datetime;
use Encore\Admin\Form\Field\Textarea;
use Encore\Admin\Widgets\Tab as Wtab;
use Illuminate\Support\Facades\Session;
use Encore\Admin\Form\Field\MultipleFile;
use Encore\Admin\Form\Field\MultipleImage;
use Encore\Admin\Form\Field\MultipleSelect;
use Illuminate\Support\Facades\Validator as ValidatorTool;

class ConfigxController extends Controller
{
    protected $validator;

    public function edit($id = 0, Content $content)
    {
        $configx_options = ConfigxModel::where('name', '__configx__')->first();
        $tabs = Configx::config('tabs', []);
        if (empty($tabs)) {
            $tabs = ['configx_demo' => 'config tabs in /config/admin.php'];
        }
        $config = [];
        if ($id > 0) {
            $config = ConfigxModel::findOrFail($id);
            $tabs['new_config'] = trans('admin.edit') . '-' . trans('admin.configx.' . $config['name']);
        } else {
            $tabs['new_config'] = "+";
        }
        if (Configx::config('check_permission', false)) {
            $this->createPermissions($tabs);
        }
        $tab = new Wtab();
        $tree = [];
        $cx_options = [];
        if ($configx_options && $configx_options['description']) {
            $cx_options = json_decode($configx_options['description'], 1);
        }
        $tableFields = [];
        foreach ($tabs as $key => &$value) {
            if (empty($value)) {
                $value = trans('admin.configx.tabs.' . $key); // if tab name is empty , get from trans
            }
            if (Configx::config('check_permission', false) && !Admin::user()->can('confix.tab.' . $key)) {
                continue;
            }
            $subs = ConfigxModel::group($key);
            if ($cx_options) {
                $subs = $this->sortConfig($subs, $cx_options);
            }
            if ($key == 'new_config') {
                $subs = array(
                    ['id' => 'type', 'name' => 'new_config_type', 'value' => ''],
                    ['id' => 'key', 'name' => 'new_config_key', 'value' => ''],
                    ['id' => 'name', 'name' => 'new_config_name', 'value' => ''],
                    ['id' => 'element', 'name' => 'new_config_element', 'value' => ''],
                    ['id' => 'help', 'name' => 'new_config_help', 'value' => ''],
                    ['id' => 'options', 'name' => 'new_config_options', 'value' => ''],
                );
            }
            if ($key == 'configx_demo') {
                $tab->add($value, view('configx::help'), true);
                continue;
            }
            $formhtml = '';
            foreach ($subs as $val) {
                if (preg_match('/\$admin\$/i', $val['name'])) {
                    $old = $val['name'];
                    $new = preg_replace('/\$admin\$/i', 'admin_' . Admin::user()->id, $old);
                    $val = ConfigxModel::firstOrCreate(
                        ['name' => $new],
                        [
                            'description' => trans('admin.configx.' . $val['name']) . ' for admin-' . Admin::user()->id,
                            'value' => '1'
                        ]
                    );
                    $val['name'] = $old;
                }
                if (preg_match('/admin_\d+?/i', $val['name'])) {
                    continue;
                }
                if (isset($cx_options[$val['name']]) && isset($cx_options[$val['name']]['table_field'])) {
                    if ($cx_options && isset($cx_options[$val['name']])) {
                        $val['etype'] = $cx_options[$val['name']]['element'];
                    } else {
                        $val['etype'] = 'normal';
                    }
                    $tableFields[$val['name']] = $val;
                    continue;
                }
                $formhtml .= $this->createField($val, $tabs, $cx_options, $config);
                if ($key != 'new_config') {
                    if ($cx_options && isset($cx_options[$val['name']])) {
                        $val['etype'] = $cx_options[$val['name']]['element'];
                    } else {
                        $val['etype'] = 'normal';
                    }
                    $tree[$value][] = $val;
                }
            }
            if ($key == 'new_config') {
                if ($id > 0) {
                    $tab->add($value, '<a title="' . trans('admin.back') . '" href="' . admin_base_path('configx/edit') . '" style="color:#999;position:absolute;top:15px;right:25px;"><i class="fa fa-times"></i>' . '</a><div class="row"><div class="col-sm-9">' . $formhtml . '</div>' . $this->buildTree($tree, $tableFields, $cx_options) . '</div>', false);
                } else {
                    $tab->add($value, '<div class="row"><div class="col-sm-9">' . $formhtml . '</div>' . $this->buildTree($tree, $tableFields, $cx_options) . '</div>', false);
                }
            } else if ($id == 0) {
                $tab->add($value, '<div class="row">' . $formhtml . '</div>', false);
            }
        }
        $form = $this->createform($tab, $id);
        return $content
            ->header(trans('admin.configx.header'))
            ->description(trans('admin.configx.desc'))
            ->breadcrumb(
                ['text' => trans('admin.configx.header'), 'url' => 'configx/edit'],
                ['text' => trans('admin.configx.desc')]
            )
            ->row('<div style="background-color:#fff;">' . $form . '</div>')
            ->row(view(
                'configx::script',
                [
                    'call_back' => admin_base_path('configx/sort'),
                    'del_url' => admin_base_path('config'),
                    'deleteConfirm' => trans('admin.delete_confirm'),
                    'confirm' => trans('admin.confirm'),
                    'cancel' => trans('admin.cancel'),
                ]
            ));
    }

    protected function createPermissions($tabs)
    {
        foreach ($tabs as $key => $val) {
            if ($key == 'configx_demo') {
                continue;
            }
            ConfigxModel::createPermission($key, $val);
        }
    }

    protected function buildTree($tree, $tableFields, $cx_options)
    {
        $treeHtml = '<div class="col-sm-3"><div class="row">';
        foreach ($tree as $k => $v) {
            $treeHtml .= '<label class="control-label"><i class="fa fa-plus-square-o"></i>&nbsp;' . $k . '</label>';
            if (count($v)) {
                $treeHtml .= '<div class="dd"><ol class="dd-list">';
                foreach ($v as $c) {
                    if (preg_match('/\$admin\$/i', $c['name'])) {
                        $etype = $c['etype'];
                        $c = ConfigxModel::firstOrCreate(
                            ['name' => $c['name']],
                            [
                                'description' => trans('admin.configx.' . $c['name']),
                                'value' => 'do not delete'
                            ]
                        );
                        $c['etype'] = $etype;
                    }
                    $tfieldsHtml = '';
                    if ($c['etype'] == 'table') {
                        $tableInfo = json_decode($c['description'], 1);
                        if ($tableInfo) {
                            $tfieldsHtml .= '<ul class="dd-list">';
                            foreach ($tableInfo as $k => $v) {
                                if (!isset($tableFields[$k])) {
                                    continue;
                                }
                                $label = $cx_options && isset($cx_options[$k]) ? array_get($cx_options[$k], 'name') : '';
                                if (!$label) {
                                    $label =  trans('admin.configx.' . $k);
                                }

                                $tfieldsHtml .= '<li title="' . $k . '" style="border-bottom:1px dashed #e1e1e1;">' . $label . '-<b>[' . trans('admin.configx.element.' . $tableFields[$k]['etype']) . ']</b>'
                                    . '<a class="pull-right dd-nodrag" title="click to change" href="' . admin_base_path('configx/edit/' . $tableFields[$k]['id']) . '"><i class="fa fa-edit"></i></a>'
                                    . '</li>';
                            }
                            $tfieldsHtml .= '</ul>';
                        }
                    }
                    $label = $cx_options && isset($cx_options[$c['name']]) ? array_get($cx_options[$c['name']], 'name') : '';
                    if (!$label) {
                        $label =  trans('admin.configx.' . $c['name']);
                    }
                    $treeHtml .= '<li title="' . $c['name'] . '" style="border:1px dashed #c1c1c1;padding:5px;margin-bottom:5px;color:#666;" class="dd-item" data-id="' . $c['id'] . '"><span class="dd-drag"><i class="fa fa-arrows"></i>&nbsp;' . $label . '</span>' . '-<b>[' . trans('admin.configx.element.' . $c['etype']) . ']</b>'
                        . '<a style="margin-left:5px;" class="pull-right dd-nodrag" title="lelete" onclick="del(\'' . $c['id'] . '\');" href="javascript:;"><i class="fa fa-trash-o"></i></a>'
                        . '<a class="pull-right dd-nodrag" title="click to change" href="' . admin_base_path('configx/edit/' . $c['id']) . '"><i class="fa fa-edit"></i></a>'
                        . $tfieldsHtml
                        . '</li>';
                }
                $treeHtml .= '</ol></div>';
            }
        }

        $treeHtml .= '</div></div>';

        return $treeHtml;
    }

    protected function createTableConfigs($tableInfo, $cx_options)
    {
        if (empty($tableInfo)) {
            return;
        }
        foreach ($tableInfo as $k => $v) {
            if ($k == $v || '' == $v) {
                $conf = ConfigxModel::where('name', $k)->first();
                if (!$conf) {
                    ConfigxModel::create(['name' => $k, 'value' => '1', 'description' => 'Table field:' . $k]);
                }
            }
            if (!isset($cx_options[$k])) {
                $cx_options[$k] = ['element' => 'normal', 'options' => [], 'help' => '', 'name' => '', 'order' => 999];
            }
            if ($k == $v || '' == $v) {
                $cx_options[$k]['table_field'] = 1;
            } else {
                if (isset($cx_options[$k]['table_field'])) {
                    array_forget($cx_options[$k], 'table_field');
                }
            }
        }
        return $cx_options;
    }

    protected function sortConfig($configs, $cx_options)
    {
        $order = [];
        foreach ($configs as $conf) {
            if (isset($cx_options[$conf['name']]) && isset($cx_options[$conf['name']]['order'])) {
                $order[] = $cx_options[$conf['name']]['order'] ?: 999;
            } else {
                $order[] = 999;
            }
        }
        array_multisort($order, SORT_ASC, $configs);
        return $configs;
    }

    public function saveall($id = 0, Request $request)
    {
        $configx_options = ConfigxModel::where('name', '__configx__')->first();
        $cx_options = [];
        if ($configx_options && $configx_options['description']) {
            $cx_options = json_decode($configx_options['description'], 1);
        } else {
            $configx_options = $this->createConfigx();
        }
        if ($id == 0) {
            Session::put('tabindex', $request->input('tabindex', 0));
        }
        $cx_options = $this->saveValues($request, $cx_options);

        if ((!empty($request->values['c_type']) && !empty($request->values['c_key'])) || $id > 0) {
            $config = [];
            $defaultVal = "1";
            if ($id > 0) {
                $config = ConfigxModel::findOrFail($id);
                $new_key = $config['name'];
            } else {
                $new_key = $request->values['c_key'];
                if ($request->values['c_type'] == 'configx_demo') {
                    admin_error('Error', "You need to add configs in [/config/admin.php] first!.");
                    return redirect()->back()->withInput();
                }
                if (!preg_match('/^' . $request->values['c_type'] . '\.\w{1,}/', $new_key)) {
                    $new_key = $request->values['c_type'] . '.' . $new_key;
                }
                if (ConfigxModel::where('name', $new_key)->first()) {
                    admin_error('Error', "The key `{$new_key}` exists in table.");
                    return redirect()->back()->withInput();
                }
                if ($request->values['c_element'] == "date") {
                    $defaultVal = "2019-01-01";
                } else if ($request->values['c_element'] == "datetime") {
                    $defaultVal = "2019-01-01 01:01:01";
                } else if ($request->values['c_element'] == "icon") {
                    $defaultVal = "fa-code";
                } else if ($request->values['c_element'] == "color") {
                    $defaultVal = "#ccc";
                } else if ($request->values['c_element'] == "multiple_image") {
                    $defaultVal = '-';
                }
            }
            if (!isset($cx_options[$new_key])) {
                $cx_options[$new_key] = [
                    'options' => [],
                    'element' => '',
                    'help' => '',
                    'name' => '',
                    'order' => 999,
                ];
            }
            $table_field = isset($cx_options[$new_key]['table_field']);
            $order = isset($cx_options[$new_key]['order']) ? 0 : $cx_options[$new_key]['order'];
            if (!empty($request->values['c_options'] && in_array(
                $request->values['c_element'],
                ['radio_group', 'checkbox_group', 'select', 'table', 'textarea', 'number', 'color', 'multiple_select', 'listbox']
            ))) {
                $c_options = explode(PHP_EOL, $request->values['c_options']);
                $arr = [];
                foreach ($c_options as $op) {
                    $kv = explode(":", $op);
                    if (count($kv) > 1) {
                        $arr[trim($kv[0])] = trim($kv[1]);
                    } else {
                        $arr[trim($kv[0])] = trim($kv[0]);
                    }
                }
                $cx_options[$new_key] = ['options' => $arr, 'element' => $request->values['c_element'], 'help' => $request->values['c_help'], 'name' => $request->values['c_name'], 'order' => 999];
                $keys = array_keys($arr);
                if ($request->values['c_element'] == "table") {
                    $defaultVal = 'do not delete';
                } else if ($keys) {
                    $defaultVal = $keys[0];
                }
            } else {
                if (in_array($request->values['c_element'], ['radio_group', 'checkbox_group', 'select', 'table', 'multiple_select', 'listbox'])) {
                    admin_error('Error', "The options is empty!");
                    return redirect()->back()->withInput();
                } else {
                    $cx_options[$new_key] = ['options' => [], 'element' => $request->values['c_element'], 'help' => $request->values['c_help'], 'name' => $request->values['c_name'], 'order' => 999];
                }
            }
            if ($request->values['c_element'] == 'table' && empty($request->table)) {
                admin_error('Error', "Build table befor save!");
                return redirect()->back()->withInput();
            }
            if ($id == 0) {
                if (preg_match('/\$admin\$/i', $new_key)) {
                    $defaultVal = 'do not delete';
                }
                $data = ['name' => $new_key, 'value' => $defaultVal, 'description' => $request->values['c_name'] ?: trans('admin.configx.' . $new_key)];
                if ($request->values['c_element'] == "table") {
                    $cx_options = $this->createTableConfigs($request->table, $cx_options);
                    $data['description'] = json_encode($request->table);
                }
                $config = new ConfigxModel($data);
                $config->save();
                $c_type = $request->values['c_type'];
            } else {
                $data = ['name' => $new_key];
                if ($request->values['c_element'] == "table") {
                    $cx_options = $this->createTableConfigs($request->table, $cx_options);
                    $data['description'] = json_encode($request->table);
                }
                $config->update($data);
                $c_type = $config->getPrefix();
            }
            $tabs = Configx::config('tabs', []);
            if (count($tabs)) {
                $i = 0;
                foreach ($tabs as $k => $v) {
                    if ($k == $c_type) {
                        Session::put('tabindex', $i);
                        break;
                    }
                    $i += 1;
                }
            }
            if ($table_field) {
                $cx_options[$new_key]['table_field'] = 1;
            }
            if ($order) {
                $cx_options[$new_key]['order'] = $order;
            }
            admin_toastr(trans('admin.save_succeeded'));
        } else {
            admin_toastr(trans('admin.update_succeeded'));
        }
        $cx_options = $this->remove($cx_options);
        $configx_options['description'] = json_encode($cx_options);
        $configx_options->save();
        if (!$this->validator->passes()) {
            return redirect()->to(admin_base_path('configx/edit'))->withErrors($this->validator->messages())->withInput();
        } else {
            return redirect()->to(admin_base_path('configx/edit/' . $id));
        }
    }

    protected function remove($cx_options)
    {
        $forget = [];
        foreach (array_keys($cx_options) as $k) {
            $config = ConfigxModel::where('name', $k)->first();
            if (!$config) {
                $forget[] = $k;
            }
            if (preg_match('/admin_\d+?/i', $k)) {
                $forget[] = $k;
            }
        }
        if (count($forget)) {
            array_forget($cx_options, $forget);
        }
        return $cx_options;
    }

    protected function saveValues($request, $cx_options)
    {
        $allRules = [];
        $messages = [];
        $labels = [];
        \DB::beginTransaction();
        foreach ($request->values as $key => $value) {
            if (in_array($key, ['c_type', 'c_element', 'c_help', 'c_key', 'c_name', 'c_options'])) {
                continue;
            }
            $id = preg_replace('/\D/', '', $key);
            $config = ConfigxModel::findOrFail($id);
            if (Configx::config('check_permission', false) && !Admin::user()->can('confix.tab.' . $config->getPrefix())) {
                continue;
            }
            if (preg_match('/admin_\d+?/i', $config['name'])) {
                $name = preg_replace('/admin_\d+?/i', '$admin$', $config['name']);
                if (isset($cx_options[$name])) {
                    $cx_options[$config['name']]['element'] = $cx_options[$name]['element'];
                }
            }
            if (isset($cx_options[$config['name']])) {
                $etype = $cx_options[$config['name']]['element'];
                $label = trans('admin.configx.' . $config['name']);
                if ($etype == 'image') {
                    $field = new Image($key, [$label]);
                    $validator = $field->getValidator([$key => $value]);
                    if ($validator->fails()) {
                        $msg = $validator->errors()->first() ?: 'Image error-' . $label;
                        //
                        $allRules['values.c_' . $id] = ['image'];
                        $messages['values.c_' . $id . '.image'] = $msg;
                        $labels['values.c_' . $id] = $label;
                        //
                        admin_warning('Error', $msg, 'error');
                        continue;
                    }
                    $value = $field->prepare($value);
                } else if ($etype == 'multiple_image') {
                    $field = new MultipleImage($key, [$label]);
                    $validator = $field->getValidator([$key => $value]);
                    if ($validator->fails()) {
                        $msg = $validator->errors()->first();
                        $msg = $msg ? preg_replace('/c.\d+/i', $label, $msg) : 'Image error-' . $label;
                        //
                        $allRules['values.c_' . $id] = ['image'];
                        $messages['values.c_' . $id . '.image'] = $msg;
                        $labels['values.c_' . $id] = $label;
                        //
                        admin_warning('Error', $msg);
                        continue;
                    }
                    $value = implode(',', $field->prepare($value));
                } else if ($etype == 'file') {
                    $field = new File($key, [$label]);
                    $value = $field->prepare($value);
                } else if ($etype == 'multiple_file') {
                    $field = new MultipleFile($key, [$label]);
                    $value = implode(',', $field->prepare($value));
                } else if ($etype == 'checkbox_group' || $etype == 'tags' || $etype == 'multiple_select' || $etype == 'listbox') {
                    $value = implode(',', (array)$value);
                } else if ($etype == 'map' && isset($request->values["c_{$id}_latitude"])) {
                    $value = $request->values["c_{$id}_latitude"] . ',' . $request->values["c_{$id}_longitude"];
                }
                $textfield = new Text($key, [$label]);
                $textfield->rules('required');
                $fieldValidator = $textfield->getValidator([$key => $value]);
                if ($fieldValidator->fails()) {
                    $msg = $fieldValidator->errors()->first() ?: $label . ' is required.';
                    $allRules['values.c_' . $id] = ['required'];
                    $messages['values.c_' . $id . '.required'] = $msg;
                    $labels['values.c_' . $id] = $label;
                }
            } else if (!preg_match('/admin_\d+?/i', $config['name'])) {
                $cx_options[$config['name']] = ['options' => [], 'element' => 'normal', 'help' => '', 'name' => '', 'order' => 999];
            }
            if ($value == '' || $value == null) {
                admin_warning('Error', trans('admin.configx.' . $config['name']) . ' is empty!');
                continue;
            }
            $config->value = $value;
            $config->update();
        }
        \DB::commit();
        $this->validator = ValidatorTool::make($request->values, $allRules, $messages, $labels);
        return $cx_options;
    }

    public function postSort(Request $request)
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
                $cx_options[$config['name']] = ['options' => [], 'element' => 'normal', 'help' => '', 'name' => '', 'order' => $i];
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

    protected function createField($val, $tabs, $cx_options, $config)
    {
        $label = $cx_options && isset($cx_options[$val['name']]) ? array_get($cx_options[$val['name']], 'name') : '';
        if (!$label) {
            $label =  trans('admin.configx.' . $val['name']);
        }
        if ($config) {
            $editName = $config['name'];
        }
        $rowname = 'values.c_' . $val['id'];
        if ($val['id'] == 'type') {
            if ($config) {
                $field = new Text($rowname, [$label]);
                $field->setWidth(10, 2);
                $field->readOnly();
                $typekey = explode('.', $editName)[0];
                $typename = array_get($tabs, $typekey);
                if (empty($typename)) {
                    $typename = trans('admin.configx.tabs.' . $typekey); // if tab name is empty , get from trans
                }
                if ($cx_options && isset($cx_options[$config['name']]) && isset($cx_options[$config['name']]['table_field'])) {
                    $tableKey = preg_replace('/^(\w+?\.\w+?)_\d+_\d+$/i', '$1', $editName);
                    if ($cx_options && isset($cx_options[$tableKey]) && isset($cx_options[$tableKey]['name'])) {
                        $typename .= '-' . $cx_options[$tableKey]['name'];
                    } else {
                        $typename .= '-' . trans('admin.configx.' . $tableKey);
                    }
                }
                $field->value($typename);
                //
            } else {
                $field = new Radio($rowname, [$label]);
                array_pop($tabs);
                $field->options($tabs)->setWidth(10, 2);
            }
        } else if ($val['id'] == 'key') {
            $field = new Text($rowname, [$label]);
            $field->setWidth(10, 2);
            if ($config) {
                $field->readOnly();
                $field->value($editName);
            }
        } else if ($val['id'] == 'name') {
            $field = new Text($rowname, [$label]);
            $field->setWidth(10, 2);
            if ($config) {
                if ($config && $cx_options && isset($cx_options[$config['name']]) && isset($cx_options[$config['name']]['name'])) {
                    $field->value($cx_options[$config['name']]['name']);
                }
            }
        } else if ($val['id'] == 'element') {
            $field = new Radio($rowname, [$label]);
            $elements = [
                'normal', 'date', 'time', 'datetime', 'image', 'multiple_image', 'file', 'multiple_file',
                'yes_or_no', 'rate', 'editor', 'tags', 'icon', 'color', 'number', 'table', 'textarea',
                'radio_group', 'checkbox_group', 'listbox', 'select', 'multiple_select', 'map',
            ];
            if ($config && isset($cx_options[$config['name']]) && isset($cx_options[$config['name']]['table_field'])) {
                array_delete($elements, 'table');
            }
            $support = [];
            foreach ($elements as $el) {
                $support[$el] = trans('admin.configx.element.' . $el);
            }
            $field->options($support)
                ->default('normal')
                ->setWidth(10, 2);
            if ($config && $cx_options && isset($cx_options[$config['name']]) && isset($cx_options[$config['name']]['element'])) {
                $field->value($cx_options[$config['name']]['element']);
            }
        } else if ($val['id'] == 'help') {
            $field = new Text($rowname, [$label]);
            $field->setWidth(10, 2);
            if ($config && $cx_options && isset($cx_options[$config['name']]) && isset($cx_options[$config['name']]['help'])) {
                $field->value($cx_options[$config['name']]['help']);
            }
        } else if ($val['id'] == 'options') {
            $field = new Textarea($rowname, [$label]);
            $table = \Request::old('table');
            if (
                !$table && $config && $cx_options && isset($cx_options[$config['name']])
                && isset($cx_options[$config['name']]['element'])
                && $cx_options[$config['name']]['element'] == 'table'
                && $config['description']
            ) {
                $table = json_decode($config['description']);
            }
            $field->help(view('configx::tips', ['table' => $table]))
                ->setWidth(10, 2)
                ->rows(3);
            if ($config && $cx_options && isset($cx_options[$config['name']]) && isset($cx_options[$config['name']]['options'])) {
                $arr = [];
                foreach ($cx_options[$config['name']]['options'] as $k => $v) {
                    if ($k == $v) {
                        $arr[] = $k;
                    } else {
                        $arr[] = $k . '  :  ' . $v;
                    }
                }
                $field->value(implode(PHP_EOL, $arr));
            }
        } else {
            if ($cx_options) {
                if (!isset($cx_options[$val['name']])) {
                    $field = new Text($rowname, [$label]);
                    $field->value($val['value']);
                } else {
                    $field = $this->getConfigField($cx_options, $val, $rowname, $label);
                }
            } else {
                $field = new Text($rowname, [$label]);
                $field->value($val['value']);
            }
        }
        if (in_array($val['id'], ['type', 'key', 'element'])) {
            $field->setLabelClass(['asterisk']);
        }
        return $val['id'] == 'options' ? '<div class="option-list hidden">' . $field->render() . '</div>' : $field->render();
    }

    protected function getConfigField($cx_options, $val, $rowname, $label)
    {
        $etype = isset($cx_options[$val['name']]) ? array_get($cx_options[$val['name']], 'element')  : 'normal';
        if ($etype == 'image') {
            $field = new Image($rowname, [$label]);
        } else if ($etype == 'multiple_image') {
            $field = new MultipleImage($rowname, [$label]);
            $field->removable();
        } else if ($etype == 'file') {
            $field = new File($rowname, [$label]);
        } else if ($etype == 'multiple_file') {
            $field = new MultipleFile($rowname, [$label]);
            $field->removable();
        } else if ($etype == 'textarea') {
            $field = new Textarea($rowname, [$label]);
            if (isset($cx_options[$val['name']]['options']['rows'])) {
                $field->rows($cx_options[$val['name']]['options']['rows']);
            }
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
                $field = new Textarea($rowname, [$label]);
            } else {
                $field = new Form::$availableFields['editor']($rowname, [$label]);
            }
        } else if ($etype == 'number') {
            $field = new Number($rowname, [$label]);
            if (isset($cx_options[$val['name']]['options']['max'])) {
                $field->max($cx_options[$val['name']]['options']['max']);
            }
            if (isset($cx_options[$val['name']]['options']['min'])) {
                $field->min($cx_options[$val['name']]['options']['min']);
            }
        } else if ($etype == 'rate') {
            $field = new Rate($rowname, [$label]);
        } else if ($etype == 'radio_group') {
            $field = new Radio($rowname, [$label]);
            $field->options($cx_options[$val['name']]['options']);
        } else if ($etype == 'checkbox_group') {
            $field = new Checkbox($rowname, [$label]);
            $field->options($cx_options[$val['name']]['options']);
        } else if ($etype == 'listbox') {
            $field = new Listbox($rowname, [$label]);
            $field->options($cx_options[$val['name']]['options']);
        } else if ($etype == 'select') {
            $field = new Select($rowname, [$label]);
            if (isset($cx_options[$val['name']]['options']['options_url'])) {
                $field->options($cx_options[$val['name']]['options']['options_url']);
            } else {
                $field->options($cx_options[$val['name']]['options']);
            }
        } else if ($etype == 'multiple_select') {
            $field = new MultipleSelect($rowname, [$label]);
            if (isset($cx_options[$val['name']]['options']['options_url'])) {
                $field->options($cx_options[$val['name']]['options']['options_url']);
            } else {
                $field->options($cx_options[$val['name']]['options']);
            }
        } else if ($etype == 'tags') {
            $field = new Tags($rowname, [$label]);
        } else if ($etype == 'icon') {
            $field = new Icon($rowname, [$label]);
        } else if ($etype == 'color') {
            $field = new Color($rowname, [$label]);
            if (isset($cx_options[$val['name']]['options']['format'])) {
                $field->options(['format' => $cx_options[$val['name']]['options']['format']]);
            }
        } else if ($etype == 'map') {
            if (!isset(Form::$availableFields['map'])) {
                $field = new Text($rowname, [$label]);
            } else {
                $latitude = $rowname . '_' . 'latitude';
                $longitude = $rowname . '_' . 'longitude';
                $field = new Form::$availableFields['map']($latitude, [$longitude, $label]);
                $values = explode(',', $val['value']);
                if (count($values) < 2) {
                    $values = ['33.100745405144245', '107.05078326165676'];
                }
                $field->fill([$latitude => $values[0], $longitude => $values[1]]);
            }
        } else if ($etype == 'table') {
            if (
                $val['description'] && isset($cx_options[$val['name']]['options']['rows'])
                && isset($cx_options[$val['name']]['options']['cols'])
            ) {
                $tableInfo = json_decode($val['description'], 1);
                if ($tableInfo) {
                    $this->createTableConfigs($tableInfo, []);
                }
                $field = new Table($label);
                $rows = [];
                for ($i = 0; $i < $cx_options[$val['name']]['options']['rows']; $i += 1) {
                    $tableRow = new TableRow();
                    for ($j = 0; $j < $cx_options[$val['name']]['options']['cols']; $j += 1) {
                        $fieldKey = $val['name'] . '_' . $i . '_' . $j;
                        if ($tableInfo[$fieldKey] == $fieldKey || '' == $tableInfo[$fieldKey]) {
                            $label = trans($fieldKey);
                            $conf = ConfigxModel::where('name', $fieldKey)->first();
                            if ($conf) {
                                $rowname = 'values.c_' . $conf['id'];
                                $tableField = $this->getConfigField($cx_options, $conf, $rowname, $label);
                                $tableRow->pushField($tableField, 1);
                            }
                        } else {
                            $text = $tableInfo[$fieldKey];
                            if (preg_match('/^trans\.(\w+)/i', $text, $mchs)) { //if text is trans.sometext , get from trans : trans("admin.configx.{$tab}.{$tablekey}.{$sometext}")
                                $text = trans("admin.configx.{$val['name']}.{$mchs[1]}");
                            }
                            $tableRow->show($text)->Textalign('center');
                        }
                    }
                    $rows[] = $tableRow;
                }
                $field->setRows($rows);
            } else {
                $field = new html('', [$label]);
            }
        } else {
            $field = new Text($rowname, [$label]);
        }
        //
        if ($etype == 'checkbox_group' || $etype == 'tags' || $etype == 'multiple_select' || $etype == 'listbox') {
            $val['value'] = preg_replace('/,$/', '', $val['value']);
            $field->value(explode(',', $val['value']));
        } else if ($etype == 'multiple_image') {
            $val['value'] = preg_replace('/,$/', '', $val['value']);
            $images = explode(',', $val['value']);
            if ($val['value'] && count($images)) {
                $field->value($images);
            }
        } else if (!($etype == 'map' && isset(Form::$availableFields['map']))) {
            $field->value($val['value']);
        }
        if (isset($cx_options[$val['name']]['options']) && !empty($cx_options[$val['name']]['help'])) {
            if ($etype == 'editor' && !isset(Form::$availableFields['editor'])) {
                $field->help('<span class="label label-warning">The editor is unuseable!</span><br />' . $cx_options[$val['name']]['help']);
            } else if ($etype == 'map' && !isset(Form::$availableFields['map'])) {
                $field->help('<span class="label label-warning">The map is unuseable!</span><br />' . $cx_options[$val['name']]['help']);
            } else {
                $field->help($cx_options[$val['name']]['help']);
            }
        } else {
            if ($etype == 'editor' && !isset(Form::$availableFields['editor'])) {
                $field->help('<span class="label label-warning">The editor is unuseable!</span>');
            } else if ($etype == 'map' && !isset(Form::$availableFields['map'])) {
                $field->help('<span class="label label-warning">The map is unuseable!</span>');
            }
        }
        return $field;
    }

    protected function createform($tab, $id)
    {
        $indexField = new Hidden('tabindex', 'tabindex');
        $indexField->value(Session::get('tabindex'));
        $indexField->default(0);
        $fields = [$tab, $indexField];
        $html = [];
        $buttons = ['reset', 'submit'];
        $attributes = [
            'method' => 'POST',
            'action' => admin_base_path('configx/saveall/' . $id),
            'class' => 'form-horizontal',
            'accept-charset' => 'UTF-8',
            'pjax-container' => true,
            'enctype' => 'multipart/form-data',
        ];
        foreach ($attributes as $key => $val) {
            $html[] = "$key=\"$val\"";
        }
        $attributes_str = implode(' ', $html) ?: '';
        $data = [
            'fields' => $fields,
            'attributes' => $attributes_str,
            'method' => $attributes['method'],
            'buttons' => $buttons,
        ];
        return view('admin::widgets.form', $data);
    }
}
