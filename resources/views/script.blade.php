<script>
    $(function() {
        $("body").on("ifChecked", "input:radio[name='values[c_type]']", function() {
            $('input[name="values[c_key]"]').val(this.value ? this.value + '.new_key_here' : '');
        });
        $("body").on("click", ".nav.nav-tabs li", function() {
            var index = $(".nav.nav-tabs li").index(this);
            $("input[name='tabindex']").val(index);
        });
        var index = $(".nav.nav-tabs li").index($(".nav.nav-tabs li.active"));
        var _index = $("input[name='tabindex']").val();
        if (index != _index) {
            $(".nav.nav-tabs li").eq(_index).find("a").trigger('click');
        }
        $('.dd').nestable({
            handleClass: 'dd-drag'
        }).on('change', function() {
            var data = $(this).nestable('serialize');
            $.ajax({
                url: '{{$call_back}}',
                type: "POST",
                data: {
                    data: data,
                    _token: LA.token,
                    _method: 'PUT'
                },
                success: function(data) {
                    toastr.success(data.message);
                }
            });
        });
        $('.input-group .input-group-btn').siblings().dblclick(function() {
            return false; //block numbox click
        });
        $(".checkbox-inline,.radio-inline").css('margin-left', '0');
    });

    function del(id) {
        swal({
            title: "{{$deleteConfirm}}",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "{{$confirm}}",
            showLoaderOnConfirm: true,
            cancelButtonText: "{{$cancel}}",
            preConfirm: function() {
                return new Promise(function(resolve) {
                    $.ajax({
                        method: 'post',
                        url: '{{$del_url}}/' + id,
                        data: {
                            _method: 'delete',
                            _token: LA.token,
                        },
                        success: function(data) {
                            $.pjax.reload('#pjax-container');

                            resolve(data);
                        }
                    });
                });
            }
        }).then(function(result) {
            var data = result.value;
            if (typeof data === 'object') {
                if (data.status) {
                    swal(data.message, '', 'success');
                } else {
                    swal(data.message, '', 'error');
                }
            }
        });
    }
</script> 