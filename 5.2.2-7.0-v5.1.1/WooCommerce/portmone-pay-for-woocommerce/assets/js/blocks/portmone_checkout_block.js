const portmone_settings = window.wc.wcSettings.getSetting('portmone_data', {});
const portmone_label = window.wp.htmlEntities.decodeEntities(portmone_settings.title);
const portmone_Content = () => {
    return window.wp.htmlEntities.decodeEntities(portmone_settings.description);
};
const Portmone_Block_Gateway = {
    name: 'portmone',
    label: portmone_label,
    content: Object(window.wp.element.createElement)(portmone_Content, null ),
    edit: Object(window.wp.element.createElement)(portmone_Content, null ),
    canMakePayment: () => true,
    ariaLabel: portmone_label,
    supports: {
        //features: [],
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod( Portmone_Block_Gateway );
 