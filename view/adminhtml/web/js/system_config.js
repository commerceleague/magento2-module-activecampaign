require(['jquery', 'domReady!'], function ($) {
    'use strict';

    $(document).ready(function () {
        const selectorGeneralEnabled = '#activecampaign_general_enabled';
        const selectorExportSettingsSection = '#activecampaign_export-link';
        const selectorCustomerExportEnabled = '#activecampaign_export_customer_enabled';
        const selectorCustomerExportSection = '#activecampaign_customer_export-link';
        const selectorOrderExportEnabled = '#activecampaign_export_order_enabled';
        const selectorOrderExportSection = '#activecampaign_order_export-link';
        const selectorNewsletterExportSection = '#activecampaign_newsletter_export-link';
        const selectorWebhookSection = '#activecampaign_webhook-link';

        function init() {
            if ($(selectorGeneralEnabled).val() === '0') {
                toggleDependantGroups(true);
            }
            toggleSectionVisibilityBasedOnConfig(selectorOrderExportSection, selectorOrderExportEnabled);
            toggleSectionVisibilityBasedOnConfig(selectorCustomerExportSection, selectorCustomerExportEnabled);

            $(selectorGeneralEnabled).change(function () {
                if ($(this).val() === '0') {
                    toggleDependantGroups(true);
                } else {
                    toggleDependantGroups(false);
                }
            });

            $(selectorOrderExportEnabled).change(function () {
                toggleSectionVisibilityBasedOnConfig(selectorOrderExportSection, selectorOrderExportEnabled);
            });

            $(selectorCustomerExportEnabled).change(function () {
                toggleSectionVisibilityBasedOnConfig(selectorCustomerExportSection, selectorCustomerExportEnabled);
            });
        }

        function toggleDependantGroups(hide = true) {
            if (hide) {
                $(selectorExportSettingsSection).closest('div.section-config').hide();
                $(selectorOrderExportSection).closest('div.section-config').hide();
                $(selectorCustomerExportSection).closest('div.section-config').hide();
                $(selectorNewsletterExportSection).closest('div.section-config').hide();
                $(selectorWebhookSection).closest('div.section-config').hide();
                $(selectorWebhookSection).closest('div.section-config').css('border-bottom-width', '0px');
            } else {
                $(selectorExportSettingsSection).closest('div.section-config').show();

                if ($(selectorOrderExportEnabled).val() === '1') {
                    $(selectorOrderExportSection).closest('div.section-config').show();
                }

                if ($(selectorCustomerExportEnabled).val() === '1') {
                    $(selectorCustomerExportSection).closest('div.section-config').show();
                }

                $(selectorNewsletterExportSection).closest('div.section-config').show();
                $(selectorWebhookSection).closest('div.section-config').show();
                $(selectorWebhookSection).closest('div.section-config').css('border-bottom-width', '1px');
            }
        }

        function toggleSectionVisibilityBasedOnConfig(sectionSelector, configSelector) {
            if ($(configSelector).val() === '0') {
                $(sectionSelector).closest('div.section-config').hide();
            } else {
                $(sectionSelector).closest('div.section-config').show();
            }
        }

        init();
    });
});

