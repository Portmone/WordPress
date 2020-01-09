
        <div id="portmone-header">
            <h3><img src="{img_src}" alt="{img_title}"></h3>
        </div>

        <h2 class='nav-tab-wrapper portmone'>
            <a href='#tab1' class="menu-portmone">{configuration}</a>
            <a href='#tab2' class="menu-portmone">{information}</a>
        </h2>

        <tr>
            <td>{payee_id_title} <span title="{payee_id_span_title}" class="red">*</span></td>
            <td>
                <input type='text' size='40' value='{payee_id}' name='portmone_payee_id' id='portmone_payee_id' />
                <p class='description'>{payee_id_description}</p>
            </td>
        </tr>
        <tr>
            <td>{login_title} <span title="{login_span_title}" class="red">*</span></td>
            <td>
                <input type='text' size='40' value='{login}' name='portmone_login' id='portmone_login' />
                <p class='description'>{login_description}</p>
            </td>
        </tr>
        <tr>
            <td>{password_title} <span title="{password_span_title}" class="red">*</span></td>
            <td>
                <input type='text' size='40' value='{password}' name='portmone_password' id='portmone_password' />
                <p class='description'>{password_description}</p>
            </td>
        </tr>
        <tr id="convert_money_title">
            <td>{convert_money_title}</span></td>
            <td>
                <input type="checkbox" name="portmone_convert_money" value="portmone_convert_money" {convert_money_checked}>
                <p class='description'>{convert_money_label}. {convert_money_description}</p>
            </td>
        </tr>
        <tr>
            <td>{exchange_rates_title}</td>
            <td>
                <input type='number' oninput="up(this)" step="0.01" size='40' value='{portmone_exchange_rates}' name='portmone_exchange_rates' id='portmone_exchange_rates' />
                <p class='description'>{exchange_rates_description}</p>
            </td>
        </tr id="exchange_rates_title">
        <tr>
            <td>{description_title}</td>
            <td>
                <textarea cols='40' rows='4' name='portmone_payment_instructions'>{portmone_description}</textarea>
                <p class='description'>{description_description}</p>
            </td>
        </tr>
        <tr>
            <td>{showlogo_title}</span></td>
            <td>
                <input  type="checkbox" name="portmone_showlogo" value="portmone_showlogo" {showlogo_checked}>
                <p class='description'>{showlogo_description}</p>
            </td>
        </tr>
        <tr>
            <td>{redirect_page_id_title}</td>
            <td>
                <select name='portmone_redirect'>
                    {portmone_redirect}
                </select>
                <p class='description'>{redirect_page_id_description}</p>
            </td>
        </tr>

        <div class="portmone_status_table">
            <div class="portmone-table">
                <div class="portmone-row">
                    <div class="portmone-col portmone-c50">{payment_module}</div>
                    <div class="portmone-col portmone-c50"></div>
                </div>
                <div class="portmone-row">
                    <div class="portmone-col portmone-c50">{IC_version}:</div>
                    <div class="portmone-col portmone-c50">{version}</div>
                </div>
                <div class="portmone-row">
                    <div class="portmone-col portmone-c50">{WP_EC_version_label}:</div>
                    <div class="portmone-col portmone-c50">{WP_EC_version}</div>
                </div>
                <div class="portmone-row">
                    <div class="portmone-col portmone-c50">{WP_version_label}:</div>
                    <div class="portmone-col portmone-c50">{WP_version}</div>
                </div>
            </div>
        </div>

        <script>
            jQuery(function () {
                (function ($) {
                    jQuery(".form-table").wrap("<div id='tab1'></div>");
                    jQuery(".portmone_status_table").wrap("<div id='tab2'></div>");
                    jQuery('h2.portmone').each(function() {
                        var active;
                        var content;
                        var links = jQuery(this).find('a');
                        active = $(links.filter('[href="'+location.hash+'"]')[0] || links[0]);
                        active.addClass('menu-portmone-active');
                        content = $(active[0].hash);
                        links.not(active).each(function () {
                            $(this.hash).hide();
                        });

                        jQuery(this).on('click', 'a', function(e){
                            active.removeClass('menu-portmone-active');
                            content.hide();
                            active = $(this);
                            content = $(this.hash);
                            active.addClass('menu-portmone-active');
                            active.blur();
                            content.show();

                            e.preventDefault();
                        });
                    });
                })(jQuery.noConflict());
            });
        </script>