/**
 * @package ImpressPages
 *
 *
 */

(function ($) {
    "use strict";

    var methods = {
        init: function (options) {
            return this.each(function () {
                var $this = $(this);

                $this.html('');

                var data = Object();
                data.pluginName = options.pluginName;
                data.aa = 'Plugins.pluginPropertiesForm';
                data.securityToken = ip.securityToken;

                $.ajax({
                    type: 'GET',
                    url: ip.baseUrl,
                    data: data,
                    encoding: "UTF-8",
                    context: $this,
                    success: formResponse,
                    dataType: 'json'
                });

            });
        },
        destroy: function () {
            // TODO
        }
    };

    var formResponse = function (response) {
        var $this = this;
        $this.html(response.html);

//        // wrap fields in a div so accordion would work
//        $this.find('fieldset').each(function (index, fieldset) {
//            var $fieldset = $(fieldset);
//            var $legend = $fieldset.find('legend');
//
//            // if legend exist it means its option group
//            if ($legend.length) {
//                // adding required attributes to make collapse() to work
//                $legend
//                    .attr('data-toggle', 'collapse')
//                    .attr('data-target', '#propertiesCollapse'+index)
//                    .addClass('collapsed');
//                $fieldset.find('.form-group').wrapAll('<div class="collapse" id="propertiesCollapse'+index+'" />');
//            }
//        });

        ipInitForms();
        $this.find('form').on('ipSubmitResponse', function (e, response) {
            if (response.result) {
                //plugin has been successfully updated
                $this.trigger('update.ipPlugins');
                $this.find('.ipsSave').addClass('btn-default').removeClass('btn-primary');
            }
        });

        $this.find('.ipsDeactivate').on('click', function (e) {
            $this.trigger('deactivate.ipPlugins');
        });

        $this.find('.ipsActivate').on('click', function (e) {
            $this.trigger('activate.ipPlugins');
        });

        $this.find('.ipsDelete').on('click', function (e) {
            $this.trigger('delete.ipPlugins');
        });

//        $this.find('.ipsSave').off().on('click', function () {
//            $this.find('form').submit();
//        });

        $this.find('input,select,textarea').off().on('change keydown input', function () {
            $this.find('.ipsSave').removeClass('btn-default').addClass('btn-primary');
        });
    };

    $.fn.ipPluginProperties = function (method) {

        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.ipPluginProperties');
        }

    };

})(jQuery);


