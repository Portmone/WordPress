<div id="portmone-header">
    {logo}
</div>

{upgrade_notice}
{error}

<h2 class='nav-tab-wrapper woo-nav-tab-wrapper tabs'>
    <a href='#tab1' class="nav-tab">{configuration}</a>
    <a href='#tab2' class="nav-tab">{information}</a>
</h2>

<div id='tab1'>
    <table class="form-table">
        {settings}
    </table>
</div>

<div id='tab2'>
    <br class="clear">

    <table class="wc_status_table widefat" cellspacing="0" id="status" style="max-width:45%;">
        <thead>
        <tr>
            <th colspan="3">{payment_module}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{IC_version}:</td>
            <td>{version}</td>
        </tr>
        <tr>
            <td>{WC_version_label}:</td>
            <td>{WC_version}</td>
            <td>{WC_actual}</td>
        </tr>
        <tr>
            <td>{WP_version_label}:</td>
            <td>{WP_version}</td>
            <td>{WP_actual}</td>
        </tr>
        </tbody>
    </table>
</div>

