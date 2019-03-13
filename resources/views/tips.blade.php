<div class="row">
    <div class="elem group_elem col-sm-12">Use texts
        <pre>text1<br/>text2<br/>...</pre>Or key-texts<br />
        <pre>key1 : text1<br/>key2 : text2<br/>...</pre>
    </div>
    <div class="elem select_elem col-sm-12">Or load data from url:
        <pre>options_url : /admin/api/mydata</pre>
    </div>
    <div class="elem textarea_elem col-sm-12">
        <pre>rows : 5</pre>
    </div>
    <div class="elem number_elem col-sm-12">
        <pre>max : 100<br/>min : 1</pre>
    </div>
    <div class="elem color_elem col-sm-12">
        <pre>format : rgba<br/><br/>[hex,rgb,rgba]</pre>
    </div>
    <div class="elem map_elem col-sm-12">
        <pre>To use map ,you need to edit configs first.<br/><br/>map_provider in /config/admin.php<br/><br/>TENCENT_MAP_API_KEY or GOOGLE_API_KEY in /.env</pre>
    </div>
    <div class="elem table_elem col-sm-12">
        <pre>rows : 3<br/>cols : 3</pre>
        <span style="text-align:right;" class="btn btn-success" onclick="createTable();">Build table</span>
        <div class="row" id="table-div" style="margin-top:10px;">

        </div>
        <code style="visibility:hidden;" id="field-tip">&nbsp;</code>
    </div>
    <textarea class="hidden" id="saved-table">{{ json_encode($table) }}</textarea>
</div>
<script>
    var buildGrid = {};
    $(function() {
        $("body").on("ifChecked", "input:radio[name='values[c_element]']", function() {
            typeChange(this.value);
        });

        $("body").on("keyup", "input.table-field", function() {
            buildGrid[$(this).attr('data-key')] = $(this).val();
            getTdType($(this));
        });

        $("body").on("focus", "input.table-field", function() {
            getTdType($(this));
        });

        typeChange($("input:radio[name='values[c_element]']:checked").val());

        var saved_table = $('#saved-table').val();
        if (/^\{.+\}$/.test(saved_table)) {
            buildGrid = JSON.parse(saved_table) || {};
            createTable();
        }
    });

    function getTdType(el) {
        var key = el.attr('data-key');
        var val = el.val();
        $('#field-tip').css('visibility', 'visible');
        if (key == val) {
            $('#field-tip').html(key + '&nbsp;:&nbsp;render as input element.');
            el.css('color', '#666');
        } else {
            $('#field-tip').html(key + '&nbsp;:&nbsp;just show text \"' + val + '\".');
            el.css('color', '#000');
        }
    }

    function typeChange(value) {
        $('div.elem').addClass('hidden');
        if (value == 'radio_group' || value == 'checkbox_group' || value == 'select' ||
            value == 'textarea' || value == 'table' ||
            value == 'number' || value == 'color' || value == 'multiple_select' || value == 'listbox' || value == 'map'
        ) {
            $(".option-list").removeClass('hidden');
        } else {
            $(".option-list").addClass('hidden');
        }
        if (value == 'map') {
            $("textarea[name='values[c_options]']").addClass('hidden');
        } else {
            $("textarea[name='values[c_options]']").removeClass('hidden');
        }
        if (value == 'radio_group' || value == 'checkbox_group' || value == 'select' || value == 'multiple_select' ||
            value == 'listbox') {
            $('.group_elem').removeClass('hidden');
            if (value == 'select' || value == 'multiple_select') {
                $('.select_elem').removeClass('hidden');
            } else {
                $('.select_elem').addClass('hidden');
            }
        } else if (value == 'textarea') {
            $('.textarea_elem').removeClass('hidden');
        } else if (value == 'number') {
            $('.number_elem').removeClass('hidden');
        } else if (value == 'color') {
            $('.color_elem').removeClass('hidden');
        } else if (value == 'table') {
            $('.table_elem').removeClass('hidden');
        } else if (value == 'map') {
            $('.map_elem').removeClass('hidden');
        }
    }

    function createTable() {
        var text = $("textarea[name='values[c_options]']").val();
        if (text == '') {
            text = "rows:3\ncols:3";
            $("textarea[name='values[c_options]']").val(text);
        }
        var arr = text.split(/[\r\n]/);
        if (arr.length > 1) {
            var rows = arr[0].replace(/\D/g, '') || 3;
            var cols = arr[1].replace(/\D/g, '') || 3;
            if (cols < 3 || rows < 3) {
                alert('cols >= 3, rows >= 3');
                return;
            }
            build(rows, cols);
        }
    }

    function build(rows, cols) {
        var html = '<table class="table" style="border:1px solid #999;width:97%;margin:12px;">';
        $key = $("input[name='values[c_name]']").val();
        if (!$key) {
            $("input[name='values[c_name]']").focus();
            return;
        }
        var tdstyle =
            'width:100%;height:100%;border:none;border-bottom:1px solid #c1c1c1;text-align:center;';
        for (var i = 0; i < rows; i += 1) {
            html += '<tr>';
            for (var j = 0; j < cols; j += 1) {
                var fieldKey = $key + '_' + i + '_' + j;
                if (!buildGrid[fieldKey]) {
                    if (i == 0 && j == 0) {
                        buildGrid[fieldKey] = 'r_label\\c_label';
                    } else if (i == 0) {
                        buildGrid[fieldKey] = 'c_label' + j;
                    } else if (j == 0) {
                        buildGrid[fieldKey] = 'r_label' + i;
                    } else {
                        buildGrid[fieldKey] = fieldKey;
                    }
                }

                if (fieldKey == buildGrid[fieldKey]) {
                    var style = tdstyle + 'color:#666;';
                } else {
                    var style = tdstyle + 'color:#000;';
                }
                html += '<td style="border:1px solid #999;"><input data-key="' + fieldKey +
                    '" class="table-field" style="' + style +
                    '" name="table[' +
                    fieldKey +
                    ']" type="text" value="' + buildGrid[fieldKey] + '" placeholder="' + fieldKey + '" /></td>';
            }
            html += '</t>';
        }
        $('#table-div').html(html);
    }
</script> 