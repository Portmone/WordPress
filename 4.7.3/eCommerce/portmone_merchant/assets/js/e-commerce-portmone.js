function up(e) {
    if (e.value.indexOf(".") != '-1') {
        e.value=e.value.substring(0, e.value.indexOf(".") + 3);
    }
}
function openbox(){
    display = document.getElementById('message-portmone').style.display;
    if(display=='block'){
        document.getElementById('message-portmone').style.display='none';
    }
}
jQuery(function () {
    (function ($) {
        $('form').submit(function () {
            var val = $( this ).serializeArray();
            var name_input = [
                'payee_id',
                'login',
                'password'
            ];
            var i,j,g;
            var error = 0;
            if (val!==undefined) {
                for (i = 0; i<val.length; i++) {
                    if (val[i].value == 'wpsc_merchant_portmone') {
                        $('input').removeClass('portmone_input_error');
                        $('#message').remove();
                        for (j = 0; j<val.length; j++) {
                            for (g = 0; g<name_input.length; g++) {
                                if (val[j].name == 'portmone_' + name_input[g] && val[j].value == '') {
                                    $('#portmone_' + name_input[g]).addClass('portmone_input_error');
                                    error++;
                                }
                            }
                            if (val[j].name == 'portmone_convert_money' && val[j].value == "portmone_convert_money" ) {
                                if (val[j+1].value =='' ) {
                                    $('#portmone_exchange_rates').addClass('portmone_input_error');
                                    error++;
                                }
                            }
                        }
                        if (error>0 ) {
                            $('#portmone-header').after('<div id="message" class="error" ><p><strong>Не заполнены обязательные поля</strong></p></div>');
                            return false;
                        }
                    }
                }
            }
        });
    })(jQuery.noConflict());
});
