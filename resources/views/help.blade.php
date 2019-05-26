<pre>'extensions' => [
    'configx' => [
        // Set to `false` if you want to disable this extension
        'enable' => true,
        //
        'tabs' => [
            'base' => 'Base settign',
            'shop' => 'Shop settign',
            'uplaod' => 'Upload setting',
            'image' => '' // if tab name is empty , get from trans : trans('admin.configx.tabs.image'); tab名称留空则从翻译中获取
        ],
        // Whether check group permissions. if (!Admin::user()->can('confix.tab.base')) {/*hide base tab*/ } .
        'check_permission' => true
    ],
],</pre> 