/**
 * @package ImpressPages
 *
 */

(function ($) {
    "use strict";

    var methods = {
        init: function (options) {
            return this.each(function () {
                var $this = $(this);
                var data = $this.data('ipInlineManagementLogo');
                // If the plugin hasn't been initialized yet
                if (!data) {
                    $this.data('ipInlineManagementLogo', {
                        'originalLogo': $this.clone(),
                        'imageUploadInitialized': false,
                        'cssClass': $this.data('cssClass')
                    });

                    $this.bind('ipModuleInlineManagementLogo.openEditPopup', $.proxy(openEditPopup, $this));

                    $this.ipModuleInlineManagementControls({
                        'Manage': function () {
                            $this.trigger('ipModuleInlineManagementLogo.openEditPopup');
                        }
                    });
                }
            });
        }
    };

    var updateType = function (e) {
        var $this = this;
        var $popup = $('.ipsModuleInlineManagementLogoModal');
        if ($popup.find('.active a[data-logotype]').data('logotype') == 'text') {
            // do nothing
        } else {
            if (!$this.data('ipInlineManagementLogo').imageUploadInitialized) {
                var $imageUploader = $popup.find('.ipsImage');
                $imageUploader.ipUploadImage($this.data('ipUploadImageOptions'));
                var data = $this.data('ipInlineManagementLogo');
                data.imageUploadInitialized = true;
                $this.data('ipInlineManagementLogo', data);

                $imageUploader.bind('change.ipUploadImage', $.proxy(preview, $this));
            }
        }
        $.proxy(preview, $this)();
    };

    var openEditPopup = function (event) {
        event.preventDefault();
        var $this = $(this);

        $('.ipsModuleInlineManagementLogoModal').remove();

        var $this = this;
        var data = Object();
        data.aa = 'InlineManagement.getManagementPopupLogo';
        data.securityToken = ip.securityToken;
        data.cssClass = $this.data('cssclass');

        $.ajax({
            type: 'POST',
            url: ip.baseUrl,
            data: data,
            context: $this,
            success: popupContentResponse,
            dataType: 'json'
        });
    };

    var confirm = function (event) {
        event.preventDefault();
        var $this = $(this);
        var $popup = $('.ipsModuleInlineManagementLogoModal');
        $this.trigger('ipInlineManagement.logoConfirm');
        var data = Object();
        data.aa = 'InlineManagement.saveLogo';
        data.securityToken = ip.securityToken;

        data.cssClass = $this.data('ipInlineManagementLogo').cssClass;

        if ($popup.find('.active a[data-logotype]').data('logotype') == 'text') {
            data.type = 'text';
        } else {
            data.type = 'image';
        }

        //TEXT LOGO
        data.text = $this.data('logoText').val();
        data.color = $this.data('colorPicker').css('background-color');
        data.font = $this.data('fontSelect').ipInlineManagementFontSelector('getFont');
        //IMAGE LOGO
        if ($this.data('ipInlineManagementLogo').imageUploadInitialized) {
            var ipUploadImage = $popup.find('.ipsImage');
            if (ipUploadImage.ipUploadImage('getNewImageUploaded')) {
                var newImage = ipUploadImage.ipUploadImage('getCurImage');
                if (newImage) {
                    data.newImage = newImage;
                }
            }

            if (ipUploadImage.ipUploadImage('getCropCoordinatesChanged') && ipUploadImage.ipUploadImage('getCurImage') != false) {
                var cropCoordinates = ipUploadImage.ipUploadImage('getCropCoordinates');
                if (cropCoordinates) {
                    data.cropX1 = cropCoordinates.x1;
                    data.cropY1 = cropCoordinates.y1;
                    data.cropX2 = cropCoordinates.x2;
                    data.cropY2 = cropCoordinates.y2;
                    data.windowWidth = ipUploadImage.ipUploadImage('width');
                    data.windowHeight = ipUploadImage.ipUploadImage('height');
                }
            }
        }

        //SAVE
        $.ajax({
            type: 'POST',
            url: ip.baseUrl,
            data: data,
            context: $this,
            success: confirmResponse,
            dataType: 'json'
        });
    };

    var confirmResponse = function (answer) {
        var $this = this;
        var $modal = $('.ipsModuleInlineManagementLogoModal');
        if (answer && answer.status == 'success') {
            $modal.modal('hide');

            if (answer.logoHtml) {
                var $newLogo = $(answer.logoHtml);
                $this.replaceWith($newLogo);
                $newLogo.ipModuleInlineManagementLogo();

            }
            $this.trigger('ipInlineManagement.logoConfirm');
        }
    };

    var cancel = function () {
        var $this = this;
        $this.removeClass('hidden');
        $this.data('previewText').remove();
        $this.data('previewImage').remove();
        var data = $this.data('ipInlineManagementLogo');
        data.imageUploadInitialized = false;
        $this.data('ipInlineManagementLogo', data);
        $this.trigger('ipInlineManagement.logoCancel');
    };

    var popupContentResponse = function (response) {
        var $this = this;

        var $responseHtml = $(response.html);
        $(document.body).append($responseHtml);
        var $modal = $responseHtml.find('.ipsModuleInlineManagementLogoModal');
        $modal.modal();

        $this.data('fontSelect', $modal.find('.ipsFontSelect'));
        $this.data('colorPicker', $modal.find('.ipsColorPicker'));
        $this.data('logoText', $modal.find('.ipsLogoText'));
        $this.data('previewText', $(response.textPreview));
        $this.data('previewImage', $(response.imagePreview));
        $this.after($this.data('previewText'));
        $this.after($this.data('previewImage'));
        $this.data('previewImage').addClass('hidden');
        $this.data('previewText').addClass('hidden');

        var $anchor = $this.data('previewText').find('a');
        var curColor = $anchor.css('color');
        var curText = $.trim($anchor.text());
        var curFont = $anchor.css('font-family');
        $anchor.css('font-family', ''); //remove font
        var defaultFont = $anchor.css('font-family'); //get default font
        $anchor.css('font-family', curFont); //restore font
        $modal.find('ipsDefaultFont').css('font-family', defaultFont);
        if (curFont.indexOf(',') == false) {
            curFont = curFont + ',sans-serif';
        }

        $this.data('logoText').css('font-family', curFont);
        $this.data('curFont', curFont);

        //init image editing
        var options = {};

        var logoData = response.logoData;

        $this.data('curData', logoData);

        options.image = logoData.imageOrig;
        options.cropX1 = logoData.x1;
        options.cropY1 = logoData.y1;
        options.cropX2 = logoData.x2;
        options.cropY2 = logoData.y2;
        options.windowWidth = logoData.requiredWidth;
        options.windowHeight = logoData.requiredHeight;
        if (logoData.x2 && !options.windowWidth) {
            options.windowWidth = logoData.x2 - logoData.x1;
        }
        if (logoData.y2 && !options.windowHeight) {
            options.windowHeight = logoData.y2 - logoData.y1;
        }
        if (!options.windowWidth) {
            options.windowWidth = 400; //default width;
        }
        if (!options.windowHeight) {
            options.windowHeight = 100; //default height;
        }
        options.enableChangeHeight = true;
        options.enableChangeWidth = true;
        options.enableUnderscale = true;
        options.minWindowWidth = 10;
        options.minWindowHeight = 10;
        options.maxWindowWidth = 774;
        options.maxWindowHeight = 310;

        $this.data('ipUploadImageOptions', options);

        //init text management
        $this.data('logoText').val(curText);
        $this.data('logoText').on('change keyup', $.proxy(preview, $this));

        $this.data('fontSelect').ipInlineManagementFontSelector({
            'hide_fallbacks': true,
            'initial': curFont,
            'selected': function (style) {
                $this.data('fontSelect').find('.ipsFontName').css('font-family', style);
                $this.data('curFont', style);
                $.proxy(preview, $this)();
            }
        });

        $this.data('colorPicker').css('backgroundColor', curColor);
        $this.data('colorPicker').ColorPicker({
            color: curColor,
            onShow: function (colpkr) {
                $(colpkr).css('zIndex', 2000);
                $(colpkr).fadeIn(300);
                return false;
            },
            onHide: function (colpkr) {
                $(colpkr).fadeOut(300);
                return false;
            },
            onChange: function (hsb, hex, rgb) {
                $this.data('colorPicker').css('backgroundColor', '#' + hex);
                $.proxy(preview, $this)();
            }
        });

        //type selection
        if (logoData.type == 'text') {
            $modal.find('a[data-logotype="text"]').tab('show');
        } else {
            $modal.find('a[data-logotype="image"]').tab('show');
        }

        $modal.find('.ipsImage').one('ready.ipUploadImage', $.proxy(preview, $this));
        $.proxy(updateType, $this)(); //initialize current type tab

        $modal.find('a[data-logotype]').on('shown.bs.tab', $.proxy(updateType, $this));
        $modal.find('.ipsConfirm').bind('click', $.proxy(confirm, $this));
        $modal.on('hide.bs.modal', function () {
            $.proxy(cancel, $this)();
        });
    };

    var preview = function () {
        var $this = this;
        var $modal = $('.ipsModuleInlineManagementLogoModal');

        $this.addClass('hidden');

        if ($modal.find('.active a[data-logotype]').data('logotype') == 'text') {
            $this.data('previewImage').addClass('hidden');
            $this.data('previewText').removeClass('hidden');
            $this.data('previewText').find('a')
                .text($this.data('logoText').val())
                .css('color', $this.data('colorPicker').css('background-color'))
                .css('font-family', $this.data('curFont'));
//            $this.data('logoText')
//                .css('font', $this.data('previewText').find('a').css('font'))
//                .css('font-size', '') // resetting font size to fit into input field
//                .css('color', $this.data('colorPicker').css('background-color')); // preview should look the same
        } else {
            $this.data('previewText').addClass('hidden');
            $this.data('previewImage').removeClass('hidden');
            var $imageUploader = $modal.find('.ipsImage');
            $this.data('previewImage').html('<div class="ip"></div>');
            $this.data('previewImage').find('div').append($imageUploader.find('.ipsModuleUploadWindow').clone());
            $this.data('previewImage').find('.ipsButtons').remove();
            $this.data('previewImage').find('.ui-resizable-handle').remove();
        }
    };

    $.fn.ipModuleInlineManagementLogo = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.ipInlineManagementLogo');
        }
    };

})(jQuery);
