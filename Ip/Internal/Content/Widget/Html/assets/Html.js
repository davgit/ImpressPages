/**
 * @package ImpressPages
 *
 */

var IpWidget_Html = function () {

    this.widgetObject = null;
    this.confirmButton = null;
    this.popup = null;
    this.data = {};
    this.textarea = null;

    this.init = function (widgetObject, data) {

        this.widgetObject = widgetObject;
        this.data = data;

        var container = this.widgetObject.find('.ipsContainer');

        if (this.data.html) {
            if (!ip.safeMode) {
                container.html(this.data.html);
            } else {

                container.html('<p>HTML widget content is hidden in safe mode.</p>'); // todo: translate
            }
        }

        var context = this; // set this so $.proxy would work below


        this.$widgetOverlay = $('<div></div>');
        this.widgetObject.prepend(this.$widgetOverlay);
        this.$widgetOverlay.on('click', $.proxy(openPopup, this));

        $(document).on('ipWidgetResized', function () {
            $.proxy(fixOverlay, context)();
        });
        $(window).on('resize', function () {
            $.proxy(fixOverlay, context)();
        });
        $.proxy(fixOverlay, context)();


        container.css('min-height', '30px');
    };


    var fixOverlay = function () {
        this.$widgetOverlay
            .css('position', 'absolute')
            .css('z-index', 1000) // should be higher enough but lower than widget controls
            .width(this.widgetObject.width())
            .height(this.widgetObject.height());
    }


    this.onAdd = function () {
        $.proxy(openPopup, this)();
    };

    var openPopup = function () {
        this.popup = $('#ipWidgetHtmlPopup');
        this.confirmButton = this.popup.find('.ipsConfirm');
        this.textarea = this.popup.find('textarea[name=html]');

        if (this.data.html) {
            this.textarea.val(this.data.html);
        } else {
            this.textarea.val(''); // cleanup value if it was set before
        }

        this.popup.modal(); // open modal popup

        this.confirmButton.off(); // ensure we will not bind second time
        this.confirmButton.on('click', $.proxy(save, this));
    };

    var save = function () {
        var data = {
            html: this.textarea.val()
        };

        this.widgetObject.save(data, 1); // save and reload widget
        this.popup.modal('hide');
    };

};

