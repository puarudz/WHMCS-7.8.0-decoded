/*!
 * bootstrap-fileinput v4.3.9
 * http://plugins.krajee.com/file-input
 *
 * Author: Kartik Visweswaran
 * Copyright: 2014 - 2017, Kartik Visweswaran, Krajee.com
 *
 * Licensed under the BSD 3-Clause
 * https://github.com/kartik-v/bootstrap-fileinput/blob/master/LICENSE.md
 */
(function (factory) {
    "use strict";
    //noinspection JSUnresolvedVariable
    if (typeof define === 'function' && define.amd) { // jshint ignore:line
        // AMD. Register as an anonymous module.
        define(['jquery'], factory); // jshint ignore:line
    } else { // noinspection JSUnresolvedVariable
        if (typeof module === 'object' && module.exports) { // jshint ignore:line
            // Node/CommonJS
            // noinspection JSUnresolvedVariable
            module.exports = factory(require('jquery')); // jshint ignore:line
        } else {
            // Browser globals
            factory(window.jQuery);
        }
    }
}(function ($) {
    "use strict";

    $.fn.fileinputLocales = {};
    $.fn.fileinputThemes = {};

    var $h, FileInput;

    // fileinput helper object for all global variables and internal helper methods
    //noinspection JSUnresolvedVariable
    $h = {
        FRAMES: '.kv-preview-thumb',
        SORT_CSS: 'file-sortable',
        STYLE_SETTING: 'style="width:{width};height:{height};"',
        OBJECT_PARAMS: '<param name="controller" value="true" />\n' +
        '<param name="allowFullScreen" value="true" />\n' +
        '<param name="allowScriptAccess" value="always" />\n' +
        '<param name="autoPlay" value="false" />\n' +
        '<param name="autoStart" value="false" />\n' +
        '<param name="quality" value="high" />\n',
        DEFAULT_PREVIEW: '<div class="file-preview-other">\n' +
        '<span class="{previewFileIconClass}">{previewFileIcon}</span>\n' +
        '</div>',
        MODAL_ID: 'kvFileinputModal',
        MODAL_EVENTS: ['show', 'shown', 'hide', 'hidden', 'loaded'],
        objUrl: window.URL || window.webkitURL,
        compare: function (input, str, exact) {
            return input !== undefined && (exact ? input === str : input.match(str));
        },
        isIE: function (ver) {
            // check for IE versions < 11
            if (navigator.appName !== 'Microsoft Internet Explorer') {
                return false;
            }
            if (ver === 10) {
                return new RegExp('msie\\s' + ver, 'i').test(navigator.userAgent);
            }
            var div = document.createElement("div"), status;
            div.innerHTML = "<!--[if IE " + ver + "]> <i></i> <![endif]-->";
            status = div.getElementsByTagName("i").length;
            document.body.appendChild(div);
            div.parentNode.removeChild(div);
            return status;
        },
        initModal: function ($modal) {
            var $body = $('body');
            if ($body.length) {
                $modal.appendTo($body);
            }
        },
        isEmpty: function (value, trim) {
            return value === undefined || value === null || value.length === 0 || (trim && $.trim(value) === '');
        },
        isArray: function (a) {
            return Array.isArray(a) || Object.prototype.toString.call(a) === '[object Array]';
        },
        ifSet: function (needle, haystack, def) {
            def = def || '';
            return (haystack && typeof haystack === 'object' && needle in haystack) ? haystack[needle] : def;
        },
        cleanArray: function (arr) {
            if (!(arr instanceof Array)) {
                arr = [];
            }
            return arr.filter(function (e) {
                return (e !== undefined && e !== null);
            });
        },
        spliceArray: function (arr, index) {
            var i, j = 0, out = [];
            if (!(arr instanceof Array)) {
                return [];
            }
            for (i = 0; i < arr.length; i++) {
                if (i !== index) {
                    out[j] = arr[i];
                    j++;
                }
            }
            return out;
        },
        getNum: function (num, def) {
            def = def || 0;
            if (typeof num === "number") {
                return num;
            }
            if (typeof num === "string") {
                num = parseFloat(num);
            }
            return isNaN(num) ? def : num;
        },
        hasFileAPISupport: function () {
            return !!(window.File && window.FileReader);
        },
        hasDragDropSupport: function () {
            var div = document.createElement('div');
            /** @namespace div.draggable */
            /** @namespace div.ondragstart */
            /** @namespace div.ondrop */
            return !$h.isIE(9) &&
                (div.draggable !== undefined || (div.ondragstart !== undefined && div.ondrop !== undefined));
        },
        hasFileUploadSupport: function () {
            return $h.hasFileAPISupport() && window.FormData;
        },
        addCss: function ($el, css) {
            $el.removeClass(css).addClass(css);
        },
        getElement: function (options, param, value) {
            return ($h.isEmpty(options) || $h.isEmpty(options[param])) ? value : $(options[param]);
        },
        uniqId: function () {
            return Math.round(new Date().getTime() + (Math.random() * 100));
        },
        htmlEncode: function (str) {
            return str.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&apos;');
        },
        replaceTags: function (str, tags) {
            var out = str;
            if (!tags) {
                return out;
            }
            $.each(tags, function (key, value) {
                if (typeof value === "function") {
                    value = value();
                }
                out = out.split(key).join(value);
            });
            return out;
        },
        cleanMemory: function ($thumb) {
            var data = $thumb.is('img') ? $thumb.attr('src') : $thumb.find('source').attr('src');
            /** @namespace $h.objUrl.revokeObjectURL */
            $h.objUrl.revokeObjectURL(data);
        },
        findFileName: function (filePath) {
            var sepIndex = filePath.lastIndexOf('/');
            if (sepIndex === -1) {
                sepIndex = filePath.lastIndexOf('\\');
            }
            return filePath.split(filePath.substring(sepIndex, sepIndex + 1)).pop();
        },
        checkFullScreen: function () {
            //noinspection JSUnresolvedVariable
            return document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement ||
                document.msFullscreenElement;
        },
        toggleFullScreen: function (maximize) {
            var doc = document, de = doc.documentElement;
            if (de && maximize && !$h.checkFullScreen()) {
                /** @namespace document.requestFullscreen */
                /** @namespace document.msRequestFullscreen */
                /** @namespace document.mozRequestFullScreen */
                /** @namespace document.webkitRequestFullscreen */
                /** @namespace Element.ALLOW_KEYBOARD_INPUT */
                if (de.requestFullscreen) {
                    de.requestFullscreen();
                } else if (de.msRequestFullscreen) {
                    de.msRequestFullscreen();
                } else if (de.mozRequestFullScreen) {
                    de.mozRequestFullScreen();
                } else if (de.webkitRequestFullscreen) {
                    de.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                }
            } else {
                /** @namespace document.exitFullscreen */
                /** @namespace document.msExitFullscreen */
                /** @namespace document.mozCancelFullScreen */
                /** @namespace document.webkitExitFullscreen */
                if (doc.exitFullscreen) {
                    doc.exitFullscreen();
                } else if (doc.msExitFullscreen) {
                    doc.msExitFullscreen();
                } else if (doc.mozCancelFullScreen) {
                    doc.mozCancelFullScreen();
                } else if (doc.webkitExitFullscreen) {
                    doc.webkitExitFullscreen();
                }
            }
        },
        moveArray: function (arr, oldIndex, newIndex) {
            if (newIndex >= arr.length) {
                var k = newIndex - arr.length;
                while ((k--) + 1) {
                    arr.push(undefined);
                }
            }
            arr.splice(newIndex, 0, arr.splice(oldIndex, 1)[0]);
            return arr;
        },
        cleanZoomCache: function ($el) {
            var $cache = $el.closest('.kv-zoom-cache-theme');
            if (!$cache.length) {
                $cache = $el.closest('.kv-zoom-cache');
            }
            $cache.remove();
        }
    };
    FileInput = function (element, options) {
        var self = this;
        self.$element = $(element);
        if (!self._validate()) {
            return;
        }
        self.isPreviewable = $h.hasFileAPISupport();
        self.isIE9 = $h.isIE(9);
        self.isIE10 = $h.isIE(10);
        if (self.isPreviewable || self.isIE9) {
            self._init(options);
            self._listen();
        } else {
            self.$element.removeClass('file-loading');
        }
    };
    FileInput.prototype = {
        constructor: FileInput,
        _init: function (options) {
            var self = this, $el = self.$element, $cont, t;
            self.options = options;
            $.each(options, function (key, value) {
                switch (key) {
                    case 'minFileCount':
                    case 'maxFileCount':
                    case 'maxFileSize':
                        self[key] = $h.getNum(value);
                        break;
                    default:
                        self[key] = value;
                        break;
                }
            });
            self.$form = $el.closest('form');
            self._initTemplateDefaults();
            self.fileInputCleared = false;
            self.fileBatchCompleted = true;
            if (!self.isPreviewable) {
                self.showPreview = false;
            }
            self.uploadFileAttr = !$h.isEmpty($el.attr('name')) ? $el.attr('name') : 'file_data';
            self.reader = null;
            self.formdata = {};
            self.clearStack();
            self.uploadCount = 0;
            self.uploadStatus = {};
            self.uploadLog = [];
            self.uploadAsyncCount = 0;
            self.loadedImages = [];
            self.totalImagesCount = 0;
            self.ajaxRequests = [];
            self.isError = false;
            self.ajaxAborted = false;
            self.cancelling = false;
            t = self._getLayoutTemplate('progress');
            self.progressTemplate = t.replace('{class}', self.progressClass);
            self.progressCompleteTemplate = t.replace('{class}', self.progressCompleteClass);
            self.progressErrorTemplate = t.replace('{class}', self.progressErrorClass);
            self.dropZoneEnabled = $h.hasDragDropSupport() && self.dropZoneEnabled;
            self.isDisabled = $el.attr('disabled') || $el.attr('readonly');
            if (self.isDisabled) {
                $el.attr('disabled', true);
            }
            self.isUploadable = $h.hasFileUploadSupport() && !$h.isEmpty(self.uploadUrl);
            self.isClickable = self.browseOnZoneClick && self.showPreview &&
                (self.isUploadable && self.dropZoneEnabled || !$h.isEmpty(self.defaultPreviewContent));
            self.slug = typeof options.slugCallback === "function" ? options.slugCallback : self._slugDefault;
            self.mainTemplate = self.showCaption ? self._getLayoutTemplate('main1') : self._getLayoutTemplate('main2');
            self.captionTemplate = self._getLayoutTemplate('caption');
            self.previewGenericTemplate = self._getPreviewTemplate('generic');
            if (self.resizeImage && (self.maxImageWidth || self.maxImageHeight)) {
                self.imageCanvas = document.createElement('canvas');
                self.imageCanvasContext = self.imageCanvas.getContext('2d');
            }
            if ($h.isEmpty($el.attr('id'))) {
                $el.attr('id', $h.uniqId());
            }
            self.namespace = '.fileinput_' + $el.attr('id').replace(/-/g, '_');
            if (self.$container === undefined) {
                self.$container = self._createContainer();
            } else {
                self._refreshContainer();
            }
            $cont = self.$container;
            self.$dropZone = $cont.find('.file-drop-zone');
            self.$progress = $cont.find('.kv-upload-progress');
            self.$btnUpload = $cont.find('.fileinput-upload');
            self.$captionContainer = $h.getElement(options, 'elCaptionContainer', $cont.find('.file-caption'));
            self.$caption = $h.getElement(options, 'elCaptionText', $cont.find('.file-caption-name'));
            self.$previewContainer = $h.getElement(options, 'elPreviewContainer', $cont.find('.file-preview'));
            self.$preview = $h.getElement(options, 'elPreviewImage', $cont.find('.file-preview-thumbnails'));
            self.$previewStatus = $h.getElement(options, 'elPreviewStatus', $cont.find('.file-preview-status'));
            self.$errorContainer = $h.getElement(options, 'elErrorContainer',
                self.$previewContainer.find('.kv-fileinput-error'));
            if (!$h.isEmpty(self.msgErrorClass)) {
                $h.addCss(self.$errorContainer, self.msgErrorClass);
            }
            self.$errorContainer.hide();
            self.previewInitId = "preview-" + $h.uniqId();
            self._initPreviewCache();
            self._initPreview(true);
            self._initPreviewActions();
            self._setFileDropZoneTitle();
            $el.removeClass('file-loading');
            if ($el.attr('disabled')) {
                self.disable();
            }
            self._initZoom();
        },
        _initTemplateDefaults: function () {
            var self = this, tMain1, tMain2, tPreview, tFileIcon, tClose, tCaption, tBtnDefault, tBtnLink, tBtnBrowse,
                tModalMain, tModal, tProgress, tSize, tFooter, tActions, tActionDelete, tActionUpload, tActionZoom,
                tActionDrag, tTagBef, tTagBef1, tTagBef2, tTagAft, tGeneric, tHtml, tImage, tText, tVideo, tAudio,
                tFlash, tObject, tPdf, tOther, tZoomCache;
            tMain1 = '{preview}\n' +
                '<div class="kv-upload-progress hide"></div>\n' +
                '<div class="input-group {class}">\n' +
                '   {caption}\n' +
                '   <div class="input-group-btn">\n' +
                '       {remove}\n' +
                '       {cancel}\n' +
                '       {upload}\n' +
                '       {browse}\n' +
                '   </div>\n' +
                '</div>';
            tMain2 = '{preview}\n<div class="kv-upload-progress hide"></div>\n{remove}\n{cancel}\n{upload}\n{browse}\n';
            tPreview = '<div class="file-preview {class}">\n' +
                '    {close}' +
                '    <div class="{dropClass}">\n' +
                '    <div class="file-preview-thumbnails">\n' +
                '    </div>\n' +
                '    <div class="clearfix"></div>' +
                '    <div class="file-preview-status text-center text-success"></div>\n' +
                '    <div class="kv-fileinput-error"></div>\n' +
                '    </div>\n' +
                '</div>';
            tClose = '<div class="close fileinput-remove">&times;</div>\n';
            tFileIcon = '<i class="glyphicon glyphicon-file kv-caption-icon"></i>';
            tCaption = '<div tabindex="500" class="form-control file-caption {class}">\n' +
                '   <div class="file-caption-name"></div>\n' +
                '</div>\n';
            //noinspection HtmlUnknownAttribute
            tBtnDefault = '<button type="{type}" tabindex="500" title="{title}" class="{css}" ' +
                '{status}>{icon} {label}</button>';
            //noinspection HtmlUnknownAttribute
            tBtnLink = '<a href="{href}" tabindex="500" title="{title}" class="{css}" {status}>{icon} {label}</a>';
            //noinspection HtmlUnknownAttribute
            tBtnBrowse = '<div tabindex="500" class="{css}" {status}>{icon} {label}</div>';
            tModalMain = '<div id="' + $h.MODAL_ID + '" class="file-zoom-dialog modal fade" ' +
                'tabindex="-1" aria-labelledby="' + $h.MODAL_ID + 'Label"></div>';
            tModal = '<div class="modal-dialog modal-lg" role="document">\n' +
                '  <div class="modal-content">\n' +
                '    <div class="modal-header">\n' +
                '      <div class="kv-zoom-actions pull-right">{toggleheader}{fullscreen}{borderless}{close}</div>\n' +
                '      <h3 class="modal-title">{heading} <small><span class="kv-zoom-title"></span></small></h3>\n' +
                '    </div>\n' +
                '    <div class="modal-body">\n' +
                '      <div class="floating-buttons"></div>\n' +
                '      <div class="kv-zoom-body file-zoom-content {zoomFrameClass}"></div>\n' + '{prev} {next}\n' +
                '    </div>\n' +
                '  </div>\n' +
                '</div>\n';
            tProgress = '<div class="progress">\n' +
                '    <div class="{class}" role="progressbar"' +
                ' aria-valuenow="{percent}" aria-valuemin="0" aria-valuemax="100" style="width:{percent}%;">\n' +
                '        {status}\n' +
                '     </div>\n' +
                '</div>';
            tSize = ' <samp>({sizeText})</samp>';
            tFooter = '<div class="file-thumbnail-footer">\n' +
                '    <div class="file-footer-caption" title="{caption}">{caption}<br>{size}</div>\n' +
                '    {progress} {actions}\n' +
                '</div>';
            tActions = '<div class="file-upload-indicator" title="{indicatorTitle}">{indicator}</div>\n' +
                '{drag}\n' +
                '<div class="file-actions">\n' +
                '    <div class="file-footer-buttons">\n' +
                '        {upload} {delete} {zoom} {other}' +
                '    </div>\n' +
                '    <div class="clearfix"></div>\n' +
                '</div>';
            //noinspection HtmlUnknownAttribute
            tActionDelete = '<button type="button" class="kv-file-remove {removeClass}" ' +
                'title="{removeTitle}" {dataUrl}{dataKey}>{removeIcon}</button>\n';
            tActionUpload = '<button type="button" class="kv-file-upload {uploadClass}" title="{uploadTitle}">' +
                '{uploadIcon}</button>';
            tActionZoom = '<button type="button" class="kv-file-zoom {zoomClass}" ' +
                'title="{zoomTitle}">{zoomIcon}</button>';
            tActionDrag = '<span class="file-drag-handle {dragClass}" title="{dragTitle}">{dragIcon}</span>';
            tTagBef = '<div class="file-preview-frame {frameClass}" id="{previewId}" data-fileindex="{fileindex}"' +
                ' data-template="{template}"';
            tTagBef1 = tTagBef + '><div class="kv-file-content">\n';
            tTagBef2 = tTagBef + ' title="{caption}"><div class="kv-file-content">\n';
            tTagAft = '</div>{footer}\n</div>\n';
            tGeneric = '{content}\n';
            tHtml = '<div class="kv-preview-data file-preview-html" title="{caption}" ' + $h.STYLE_SETTING +
                '>{data}</div>\n';
            tImage = '<img src="{data}" class="file-preview-image kv-preview-data" title="{caption}" alt="{caption}" ' +
                $h.STYLE_SETTING + '>\n';
            tText = '<textarea class="kv-preview-data file-preview-text" title="{caption}" readonly ' +
                $h.STYLE_SETTING + '>{data}</textarea>\n';
            tVideo = '<video class="kv-preview-data file-preview-video" width="{width}" ' +
                'height="{height}" controls>\n' + '<source src="{data}" type="{type}">\n' + $h.DEFAULT_PREVIEW +
                '\n</video>\n';
            tAudio = '<div class="file-preview-audio"><audio class="kv-preview-data" controls>\n<source src="{data}" ' +
                'type="{type}">\n' + $h.DEFAULT_PREVIEW + '\n</audio></div>\n';
            tFlash = '<object class="kv-preview-data file-object" type="application/x-shockwave-flash" ' +
                'width="{width}" height="{height}" data="{data}">\n' + $h.OBJECT_PARAMS + ' ' + $h.DEFAULT_PREVIEW +
                '\n</object>\n';
            tObject = '<object class="kv-preview-data file-object" data="{data}" type="{type}" ' +
                'width="{width}" height="{height}">\n' + '<param name="movie" value="{caption}" />\n' +
                $h.OBJECT_PARAMS + ' ' + $h.DEFAULT_PREVIEW + '\n</object>\n';
            tPdf = '<embed class="kv-preview-data" src="{data}" ' +
                'width="{width}" height="{height}" type="application/pdf">\n';
            tOther = '<div class="kv-preview-data file-preview-other-frame">\n' + $h.DEFAULT_PREVIEW + '\n</div>\n';
            tZoomCache = '<div class="kv-zoom-cache" style="display:none">{zoomContent}</div>';
            self.defaults = {
                layoutTemplates: {
                    main1: tMain1,
                    main2: tMain2,
                    preview: tPreview,
                    close: tClose,
                    fileIcon: tFileIcon,
                    caption: tCaption,
                    modalMain: tModalMain,
                    modal: tModal,
                    progress: tProgress,
                    size: tSize,
                    footer: tFooter,
                    actions: tActions,
                    actionDelete: tActionDelete,
                    actionUpload: tActionUpload,
                    actionZoom: tActionZoom,
                    actionDrag: tActionDrag,
                    btnDefault: tBtnDefault,
                    btnLink: tBtnLink,
                    btnBrowse: tBtnBrowse,
                    zoomCache: tZoomCache
                },
                previewMarkupTags: {
                    tagBefore1: tTagBef1,
                    tagBefore2: tTagBef2,
                    tagAfter: tTagAft
                },
                previewContentTemplates: {
                    generic: tGeneric,
                    html: tHtml,
                    image: tImage,
                    text: tText,
                    video: tVideo,
                    audio: tAudio,
                    flash: tFlash,
                    object: tObject,
                    pdf: tPdf,
                    other: tOther
                },
                allowedPreviewTypes: ['image', 'html', 'text', 'video', 'audio', 'flash', 'pdf', 'object'],
                previewTemplates: {},
                previewSettings: {
                    image: {width: "auto", height: "160px"},
                    html: {width: "213px", height: "160px"},
                    text: {width: "213px", height: "160px"},
                    video: {width: "213px", height: "160px"},
                    audio: {width: "213px", height: "80px"},
                    flash: {width: "213px", height: "160px"},
                    object: {width: "160px", height: "auto"},
                    pdf: {width: "160px", height: "160px"},
                    other: {width: "160px", height: "160px"}
                },
                previewZoomSettings: {
                    image: {width: "auto", height: "auto", 'max-width': "100%", 'max-height': "100%"},
                    html: {width: "100%", height: "100%", 'min-height': "480px"},
                    text: {width: "100%", height: "100%", 'min-height': "480px"},
                    video: {width: "auto", height: "100%", 'max-width': "100%"},
                    audio: {width: "100%", height: "30px"},
                    flash: {width: "auto", height: "480px"},
                    object: {width: "auto", height: "100%", 'min-height': "480px"},
                    pdf: {width: "100%", height: "100%", 'min-height': "480px"},
                    other: {width: "auto", height: "100%", 'min-height': "480px"}
                },
                fileTypeSettings: {
                    image: function (vType, vName) {
                        return $h.compare(vType, 'image.*') || $h.compare(vName, /\.(gif|png|jpe?g)$/i);
                    },
                    html: function (vType, vName) {
                        return $h.compare(vType, 'text/html') || $h.compare(vName, /\.(htm|html)$/i);
                    },
                    text: function (vType, vName) {
                        return $h.compare(vType, 'text.*') || $h.compare(vName, /\.(xml|javascript)$/i) ||
                            $h.compare(vName, /\.(txt|md|csv|nfo|ini|json|php|js|css)$/i);
                    },
                    video: function (vType, vName) {
                        return $h.compare(vType, 'video.*') && ($h.compare(vType, /(ogg|mp4|mp?g|mov|webm|3gp)$/i) ||
                            $h.compare(vName, /\.(og?|mp4|webm|mp?g|mov|3gp)$/i));
                    },
                    audio: function (vType, vName) {
                        return $h.compare(vType, 'audio.*') && ($h.compare(vName, /(ogg|mp3|mp?g|wav)$/i) ||
                            $h.compare(vName, /\.(og?|mp3|mp?g|wav)$/i));
                    },
                    flash: function (vType, vName) {
                        return $h.compare(vType, 'application/x-shockwave-flash', true) || $h.compare(vName, /\.(swf)$/i);
                    },
                    pdf: function (vType, vName) {
                        return $h.compare(vType, 'application/pdf', true) || $h.compare(vName, /\.(pdf)$/i);
                    },
                    object: function () {
                        return true;
                    },
                    other: function () {
                        return true;
                    }
                },
                fileActionSettings: {
                    showRemove: true,
                    showUpload: true,
                    showZoom: true,
                    showDrag: true,
                    removeIcon: '<i class="glyphicon glyphicon-trash text-danger"></i>',
                    removeClass: 'btn btn-xs btn-default',
                    removeTitle: 'Remove file',
                    uploadIcon: '<i class="glyphicon glyphicon-upload text-info"></i>',
                    uploadClass: 'btn btn-xs btn-default',
                    uploadTitle: 'Upload file',
                    zoomIcon: '<i class="glyphicon glyphicon-zoom-in"></i>',
                    zoomClass: 'btn btn-xs btn-default',
                    zoomTitle: 'View Details',
                    dragIcon: '<i class="glyphicon glyphicon-menu-hamburger"></i>',
                    dragClass: 'text-info',
                    dragTitle: 'Move / Rearrange',
                    dragSettings: {},
                    indicatorNew: '<i class="glyphicon glyphicon-hand-down text-warning"></i>',
                    indicatorSuccess: '<i class="glyphicon glyphicon-ok-sign text-success"></i>',
                    indicatorError: '<i class="glyphicon glyphicon-exclamation-sign text-danger"></i>',
                    indicatorLoading: '<i class="glyphicon glyphicon-hand-up text-muted"></i>',
                    indicatorNewTitle: 'Not uploaded yet',
                    indicatorSuccessTitle: 'Uploaded',
                    indicatorErrorTitle: 'Upload Error',
                    indicatorLoadingTitle: 'Uploading ...'
                }
            };
            $.each(self.defaults, function (key, setting) {
                if (key === 'allowedPreviewTypes') {
                    if (self.allowedPreviewTypes === undefined) {
                        self.allowedPreviewTypes = setting;
                    }
                    return;
                }
                self[key] = $.extend(true, {}, setting, self[key]);
            });
            self._initPreviewTemplates();
        },
        _initPreviewTemplates: function () {
            var self = this, cfg = self.defaults, tags = self.previewMarkupTags, tagBef, tagAft = tags.tagAfter;
            $.each(cfg.previewContentTemplates, function (key, value) {
                if ($h.isEmpty(self.previewTemplates[key])) {
                    tagBef = tags.tagBefore2;
                    if (key === 'generic' || key === 'image' || key === 'html' || key === 'text') {
                        tagBef = tags.tagBefore1;
                    }
                    self.previewTemplates[key] = tagBef + value + tagAft;
                }
            });
        },
        _initPreviewCache: function () {
            var self = this;
            self.previewCache = {
                data: {},
                init: function () {
                    var content = self.initialPreview;
                    if (content.length > 0 && !$h.isArray(content)) {
                        content = content.split(self.initialPreviewDelimiter);
                    }
                    self.previewCache.data = {
                        content: content,
                        config: self.initialPreviewConfig,
                        tags: self.initialPreviewThumbTags
                    };
                },
                fetch: function () {
                    return self.previewCache.data.content.filter(function (n) {
                        return n !== null;
                    });
                },
                count: function (all) {
                    return !!self.previewCache.data && !!self.previewCache.data.content ?
                        (all ? self.previewCache.data.content.length : self.previewCache.fetch().length) : 0;
                },
                get: function (i, isDisabled) {
                    var ind = 'init_' + i, data = self.previewCache.data, config = data.config[i],
                        content = data.content[i], previewId = self.previewInitId + '-' + ind, out, $tmp, cat, ftr,
                        fname, ftype, frameClass, asData = $h.ifSet('previewAsData', config, self.initialPreviewAsData),
                        parseTemplate = function (cat, dat, fn, ft, id, ftr, ind, fc, t) {
                            fc = ' file-preview-initial ' + $h.SORT_CSS + (fc ? ' ' + fc : '');
                            return self._generatePreviewTemplate(cat, dat, fn, ft, id, false, null, fc, ftr, ind, t);
                        };
                    if (!content) {
                        return '';
                    }
                    isDisabled = isDisabled === undefined ? true : isDisabled;
                    cat = $h.ifSet('type', config, self.initialPreviewFileType || 'generic');
                    fname = $h.ifSet('filename', config, $h.ifSet('caption', config));
                    ftype = $h.ifSet('filetype', config, cat);
                    ftr = self.previewCache.footer(i, isDisabled, (config && config.size || null));
                    frameClass = $h.ifSet('frameClass', config);
                    if (asData) {
                        out = parseTemplate(cat, content, fname, ftype, previewId, ftr, ind, frameClass);
                    } else {
                        out = parseTemplate('generic', content, fname, ftype, previewId, ftr, ind, frameClass, cat)
                            .replace(/\{content}/g, data.content[i]);
                    }
                    if (data.tags.length && data.tags[i]) {
                        out = $h.replaceTags(out, data.tags[i]);
                    }
                    /** @namespace config.frameAttr */
                    if (!$h.isEmpty(config) && !$h.isEmpty(config.frameAttr)) {
                        $tmp = $(document.createElement('div')).html(out);
                        $tmp.find('.file-preview-initial').attr(config.frameAttr);
                        out = $tmp.html();
                        $tmp.remove();
                    }
                    return out;
                },
                add: function (content, config, tags, append) {
                    var data = self.previewCache.data, index;
                    if (!$h.isArray(content)) {
                        content = content.split(self.initialPreviewDelimiter);
                    }
                    if (append) {
                        index = data.content.push(content) - 1;
                        data.config[index] = config;
                        data.tags[index] = tags;
                    } else {
                        index = content.length - 1;
                        data.content = content;
                        data.config = config;
                        data.tags = tags;
                    }
                    self.previewCache.data = data;
                    return index;
                },
                set: function (content, config, tags, append) {
                    var data = self.previewCache.data, i, chk;
                    if (!content || !content.length) {
                        return;
                    }
                    if (!$h.isArray(content)) {
                        content = content.split(self.initialPreviewDelimiter);
                    }
                    chk = content.filter(function (n) {
                        return n !== null;
                    });
                    if (!chk.length) {
                        return;
                    }
                    if (data.content === undefined) {
                        data.content = [];
                    }
                    if (data.config === undefined) {
                        data.config = [];
                    }
                    if (data.tags === undefined) {
                        data.tags = [];
                    }
                    if (append) {
                        for (i = 0; i < content.length; i++) {
                            if (content[i]) {
                                data.content.push(content[i]);
                            }
                        }
                        for (i = 0; i < config.length; i++) {
                            if (config[i]) {
                                data.config.push(config[i]);
                            }
                        }
                        for (i = 0; i < tags.length; i++) {
                            if (tags[i]) {
                                data.tags.push(tags[i]);
                            }
                        }
                    } else {
                        data.content = content;
                        data.config = config;
                        data.tags = tags;
                    }
                    self.previewCache.data = data;
                },
                unset: function (index) {
                    var chk = self.previewCache.count();
                    if (!chk) {
                        return;
                    }
                    if (chk === 1) {
                        self.previewCache.data.content = [];
                        self.previewCache.data.config = [];
                        self.previewCache.data.tags = [];
                        self.initialPreview = [];
                        self.initialPreviewConfig = [];
                        self.initialPreviewThumbTags = [];
                        return;
                    }
                    self.previewCache.data.content[index] = null;
                    self.previewCache.data.config[index] = null;
                    self.previewCache.data.tags[index] = null;
                },
                out: function () {
                    var html = '', caption, len = self.previewCache.count(true), i;
                    if (len === 0) {
                        return {content: '', caption: ''};
                    }
                    for (i = 0; i < len; i++) {
                        html += self.previewCache.get(i);
                    }
                    caption = self._getMsgSelected(self.previewCache.count());
                    return {content: html, caption: caption};
                },
                footer: function (i, isDisabled, size) {
                    var data = self.previewCache.data;
                    if (!data || !data.config || data.config.length === 0 || $h.isEmpty(data.config[i])) {
                        return '';
                    }
                    isDisabled = isDisabled === undefined ? true : isDisabled;
                    var config = data.config[i], caption = $h.ifSet('caption', config), actions = '',
                        width = $h.ifSet('width', config, 'auto'), url = $h.ifSet('url', config, false),
                        key = $h.ifSet('key', config, null), fs = self.fileActionSettings,
                        showDel = $h.ifSet('showDelete', config, true), showZoom = $h.ifSet('showZoom', config, fs.showZoom),
                        showDrag = $h.ifSet('showDrag', config, fs.showDrag), disabled = (url === false) && isDisabled;
                    if (self.initialPreviewShowDelete) {
                        actions = self._renderFileActions(false, showDel, showZoom, showDrag, disabled, url, key, true);
                    }
                    return self._getLayoutTemplate('footer').replace(/\{progress}/g, self._renderThumbProgress())
                        .replace(/\{actions}/g, actions).replace(/\{caption}/g, caption)
                        .replace(/\{size}/g, self._getSize(size)).replace(/\{width}/g, width)
                        .replace(/\{indicator}/g, '').replace(/\{indicatorTitle}/g, '');
                }
            };
            self.previewCache.init();
        },
        _handler: function ($el, event, callback) {
            var self = this, ns = self.namespace, ev = event.split(' ').join(ns + ' ') + ns;
            if (!$el || !$el.length) {
                return;
            }
            $el.off(ev).on(ev, callback);
        },
        _log: function (msg) {
            var self = this, id = self.$element.attr('id');
            if (id) {
                msg = '"' + id + '": ' + msg;
            }
            if (typeof window.console.log !== "undefined") {
                window.console.log(msg);
            } else {
                window.alert(msg);
            }
        },
        _validate: function () {
            var self = this, status = self.$element.attr('type') === 'file';
            if (!status) {
                self._log('The input "type" must be set to "file" for initializing the "bootstrap-fileinput" plugin.');
            }
            return status;
        },
        _errorsExist: function () {
            var self = this, $err;
            if (self.$errorContainer.find('li').length) {
                return true;
            }
            $err = $(document.createElement('div')).html(self.$errorContainer.html());
            $err.find('span.kv-error-close').remove();
            $err.find('ul').remove();
            return $.trim($err.text()).length ? true : false;
        },
        _errorHandler: function (evt, caption) {
            var self = this, err = evt.target.error;
            /** @namespace err.NOT_FOUND_ERR */
            /** @namespace err.SECURITY_ERR */
            /** @namespace err.NOT_READABLE_ERR */
            if (err.code === err.NOT_FOUND_ERR) {
                self._showError(self.msgFileNotFound.replace('{name}', caption));
            } else if (err.code === err.SECURITY_ERR) {
                self._showError(self.msgFileSecured.replace('{name}', caption));
            } else if (err.code === err.NOT_READABLE_ERR) {
                self._showError(self.msgFileNotReadable.replace('{name}', caption));
            } else if (err.code === err.ABORT_ERR) {
                self._showError(self.msgFilePreviewAborted.replace('{name}', caption));
            } else {
                self._showError(self.msgFilePreviewError.replace('{name}', caption));
            }
        },
        _addError: function (msg) {
            var self = this, $error = self.$errorContainer;
            if (msg && $error.length) {
                $error.html(self.errorCloseButton + msg);
                self._handler($error.find('.kv-error-close'), 'click', function () {
                    $error.fadeOut('slow');
                });
            }
        },
        _resetErrors: function (fade) {
            var self = this, $error = self.$errorContainer;
            self.isError = false;
            self.$container.removeClass('has-error');
            $error.html('');
            if (fade) {
                $error.fadeOut('slow');
            } else {
                $error.hide();
            }
        },
        _showFolderError: function (folders) {
            var self = this, $error = self.$errorContainer, msg;
            if (!folders) {
                return;
            }
            msg = self.msgFoldersNotAllowed.replace(/\{n}/g, folders);
            self._addError(msg);
            $h.addCss(self.$container, 'has-error');
            $error.fadeIn(800);
            self._raise('filefoldererror', [folders, msg]);
        },
        _showUploadError: function (msg, params, event) {
            var self = this, $error = self.$errorContainer, ev = event || 'fileuploaderror', e = params && params.id ?
            '<li data-file-id="' + params.id + '">' + msg + '</li>' : '<li>' + msg + '</li>';
            if ($error.find('ul').length === 0) {
                self._addError('<ul>' + e + '</ul>');
            } else {
                $error.find('ul').append(e);
            }
            $error.fadeIn(800);
            self._raise(ev, [params, msg]);
            self.$container.removeClass('file-input-new');
            $h.addCss(self.$container, 'has-error');
            return true;
        },
        _showError: function (msg, params, event) {
            var self = this, $error = self.$errorContainer, ev = event || 'fileerror';
            params = params || {};
            params.reader = self.reader;
            self._addError(msg);
            $error.fadeIn(800);
            self._raise(ev, [params, msg]);
            if (!self.isUploadable) {
                self._clearFileInput();
            }
            self.$container.removeClass('file-input-new');
            $h.addCss(self.$container, 'has-error');
            self.$btnUpload.attr('disabled', true);
            return true;
        },
        _noFilesError: function (params) {
            var self = this, label = self.minFileCount > 1 ? self.filePlural : self.fileSingle,
                msg = self.msgFilesTooLess.replace('{n}', self.minFileCount).replace('{files}', label),
                $error = self.$errorContainer;
            self._addError(msg);
            self.isError = true;
            self._updateFileDetails(0);
            $error.fadeIn(800);
            self._raise('fileerror', [params, msg]);
            self._clearFileInput();
            $h.addCss(self.$container, 'has-error');
        },
        _parseError: function (operation, jqXHR, errorThrown, fileName) {
            /** @namespace jqXHR.responseJSON */
            var self = this, errMsg = $.trim(errorThrown + ''), dot = errMsg.slice(-1) === '.' ? '' : '.',
                text = jqXHR.responseJSON !== undefined && jqXHR.responseJSON.error !== undefined ?
                    jqXHR.responseJSON.error : jqXHR.responseText;
            if (self.cancelling && self.msgUploadAborted) {
                errMsg = self.msgUploadAborted;
            }
            if (self.showAjaxErrorDetails && text) {
                text = $.trim(text.replace(/\n\s*\n/g, '\n'));
                text = text.length > 0 ? '<pre>' + text + '</pre>' : '';
                errMsg += dot + text;
            } else {
                errMsg += dot;
            }
            if (errMsg === dot) {
                errMsg = self.msgAjaxError.replace('{operation}', operation);
            }
            self.cancelling = false;
            return fileName ? '<b>' + fileName + ': </b>' + errMsg : errMsg;
        },
        _parseFileType: function (file) {
            var self = this, isValid, vType, cat, i, types = self.allowedPreviewTypes || [];
            for (i = 0; i < types.length; i++) {
                cat = types[i];
                isValid = self.fileTypeSettings[cat];
                vType = isValid(file.type, file.name) ? cat : '';
                if (!$h.isEmpty(vType)) {
                    return vType;
                }
            }
            return 'other';
        },
        _getPreviewIcon: function (fname) {
            var self = this, ext, out = null;
            if (fname && fname.indexOf('.') > -1) {
                ext = fname.split('.').pop();
                if (self.previewFileIconSettings && self.previewFileIconSettings[ext]) {
                    out = self.previewFileIconSettings[ext];
                }
                if (self.previewFileExtSettings) {
                    $.each(self.previewFileExtSettings, function (key, func) {
                        if (self.previewFileIconSettings[key] && func(ext)) {
                            out = self.previewFileIconSettings[key];
                            //noinspection UnnecessaryReturnStatementJS
                            return;
                        }
                    });
                }
            }
            return out;
        },
        _parseFilePreviewIcon: function (content, fname) {
            var self = this, icn = self._getPreviewIcon(fname) || self.previewFileIcon;
            if (content.indexOf('{previewFileIcon}') > -1) {
                content = content.replace(/\{previewFileIconClass}/g, self.previewFileIconClass).replace(
                    /\{previewFileIcon}/g, icn);
            }
            return content;
        },
        _raise: function (event, params) {
            var self = this, e = $.Event(event);
            if (params !== undefined) {
                self.$element.trigger(e, params);
            } else {
                self.$element.trigger(e);
            }
            if (e.isDefaultPrevented() || e.result === false) {
                return false;
            }
            switch (event) {
                // ignore these events
                case 'filebatchuploadcomplete':
                case 'filebatchuploadsuccess':
                case 'fileuploaded':
                case 'fileclear':
                case 'filecleared':
                case 'filereset':
                case 'fileerror':
                case 'filefoldererror':
                case 'fileuploaderror':
                case 'filebatchuploaderror':
                case 'filedeleteerror':
                case 'filecustomerror':
                case 'filesuccessremove':
                    break;
                // receive data response via `filecustomerror` event`
                default:
                    self.ajaxAborted = e.result;
                    break;
            }
            return true;
        },
        _listenFullScreen: function (isFullScreen) {
            var self = this, $modal = self.$modal, $btnFull, $btnBord;
            if (!$modal || !$modal.length) {
                return;
            }
            $btnFull = $modal && $modal.find('.btn-fullscreen');
            $btnBord = $modal && $modal.find('.btn-borderless');
            if (!$btnFull.length || !$btnBord.length) {
                return;
            }
            $btnFull.removeClass('active').attr('aria-pressed', 'false');
            $btnBord.removeClass('active').attr('aria-pressed', 'false');
            if (isFullScreen) {
                $btnFull.addClass('active').attr('aria-pressed', 'true');
            } else {
                $btnBord.addClass('active').attr('aria-pressed', 'true');
            }
            if ($modal.hasClass('file-zoom-fullscreen')) {
                self._maximizeZoomDialog();
            } else {
                if (isFullScreen) {
                    self._maximizeZoomDialog();
                } else {
                    $btnBord.removeClass('active').attr('aria-pressed', 'false');
                }
            }
        },
        _listen: function () {
            var self = this, $el = self.$element, $form = self.$form, $cont = self.$container, fullScreenEvents;
            self._handler($el, 'change', $.proxy(self._change, self));
            if (self.showBrowse) {
                self._handler(self.$btnFile, 'click', $.proxy(self._browse, self));
            }
            self._handler($cont.find('.fileinput-remove:not([disabled])'), 'click', $.proxy(self.clear, self));
            self._handler($cont.find('.fileinput-cancel'), 'click', $.proxy(self.cancel, self));
            self._initDragDrop();
            self._handler($form, 'reset', $.proxy(self.reset, self));
            if (!self.isUploadable) {
                self._handler($form, 'submit', $.proxy(self._submitForm, self));
            }
            self._handler(self.$container.find('.fileinput-upload'), 'click', $.proxy(self._uploadClick, self));
            self._handler($(window), 'resize', function () {
                self._listenFullScreen(screen.width === window.innerWidth && screen.height === window.innerHeight);
            });
            fullScreenEvents = 'webkitfullscreenchange mozfullscreenchange fullscreenchange MSFullscreenChange';
            self._handler($(document), fullScreenEvents, function () {
                self._listenFullScreen($h.checkFullScreen());
            });
            self._initClickable();
        },
        _initClickable: function () {
            var self = this, $zone;
            if (!self.isClickable) {
                return;
            }
            $zone = self.isUploadable ? self.$dropZone : self.$preview.find('.file-default-preview');
            $h.addCss($zone, 'clickable');
            $zone.attr('tabindex', -1);
            self._handler($zone, 'click', function (e) {
                var $tar = $(e.target);
                if (!$tar.parents('.file-preview-thumbnails').length || $tar.parents('.file-default-preview').length) {
                    self.$element.trigger('click');
                    $zone.blur();
                }
            });
        },
        _initDragDrop: function () {
            var self = this, $zone = self.$dropZone;
            if (self.isUploadable && self.dropZoneEnabled && self.showPreview) {
                self._handler($zone, 'dragenter dragover', $.proxy(self._zoneDragEnter, self));
                self._handler($zone, 'dragleave', $.proxy(self._zoneDragLeave, self));
                self._handler($zone, 'drop', $.proxy(self._zoneDrop, self));
                self._handler($(document), 'dragenter dragover drop', self._zoneDragDropInit);
            }
        },
        _zoneDragDropInit: function (e) {
            e.stopPropagation();
            e.preventDefault();
        },
        _zoneDragEnter: function (e) {
            var self = this, hasFiles = $.inArray('Files', e.originalEvent.dataTransfer.types) > -1;
            self._zoneDragDropInit(e);
            if (self.isDisabled || !hasFiles) {
                e.originalEvent.dataTransfer.effectAllowed = 'none';
                e.originalEvent.dataTransfer.dropEffect = 'none';
                return;
            }
            $h.addCss(self.$dropZone, 'file-highlighted');
        },
        _zoneDragLeave: function (e) {
            var self = this;
            self._zoneDragDropInit(e);
            if (self.isDisabled) {
                return;
            }
            self.$dropZone.removeClass('file-highlighted');
        },
        _zoneDrop: function (e) {
            var self = this;
            e.preventDefault();
            /** @namespace e.originalEvent.dataTransfer */
            if (self.isDisabled || $h.isEmpty(e.originalEvent.dataTransfer.files)) {
                return;
            }
            self._change(e, 'dragdrop');
            self.$dropZone.removeClass('file-highlighted');
        },
        _uploadClick: function (e) {
            var self = this, $btn = self.$container.find('.fileinput-upload'), $form,
                isEnabled = !$btn.hasClass('disabled') && $h.isEmpty($btn.attr('disabled'));
            if (e && e.isDefaultPrevented()) {
                return;
            }
            if (!self.isUploadable) {
                if (isEnabled && $btn.attr('type') !== 'submit') {
                    $form = $btn.closest('form');
                    // downgrade to normal form submit if possible
                    if ($form.length) {
                        $form.trigger('submit');
                    }
                    e.preventDefault();
                }
                return;
            }
            e.preventDefault();
            if (isEnabled) {
                self.upload();
            }
        },
        _submitForm: function () {
            var self = this, $el = self.$element, files = $el.get(0).files;
            if (files && self.minFileCount > 0 && self._getFileCount(files.length) < self.minFileCount) {
                self._noFilesError({});
                return false;
            }
            return !self._abort({});
        },
        _clearPreview: function () {
            var self = this, $p = self.$preview,
                $thumbs = self.showUploadedThumbs ? $p.find($h.FRAMES + ':not(.file-preview-success)') : $p.find($h.FRAMES);
            $thumbs.each(function () {
                var $thumb = $(this);
                $thumb.remove();
                $h.cleanZoomCache($p.find('#zoom-' + $thumb.attr('id')));
            });
            if (!self.$preview.find($h.FRAMES).length || !self.showPreview) {
                self._resetUpload();
            }
            self._validateDefaultPreview();
        },
        _initSortable: function () {
            var self = this, $el = self.$preview, settings, selector = '.' + $h.SORT_CSS;
            if (!window.KvSortable || $el.find(selector).length === 0) {
                return;
            }
            //noinspection JSUnusedGlobalSymbols
            settings = {
                handle: '.drag-handle-init',
                dataIdAttr: 'data-preview-id',
                draggable: selector,
                onSort: function (e) {
                    var oldIndex = e.oldIndex, newIndex = e.newIndex, key, $frame;
                    self.initialPreview = $h.moveArray(self.initialPreview, oldIndex, newIndex);
                    self.initialPreviewConfig = $h.moveArray(self.initialPreviewConfig, oldIndex, newIndex);
                    self.previewCache.init();
                    for (var i = 0; i < self.initialPreviewConfig.length; i++) {
                        if (self.initialPreviewConfig[i] !== null) {
                            key = self.initialPreviewConfig[i].key;
                            $frame = $(".kv-file-remove[data-key='" + key + "']").closest($h.FRAMES);
                            $frame.attr('data-fileindex', 'init_' + i).data('fileindex', 'init_' + i);
                        }
                    }
                    self._raise('filesorted', {
                        previewId: $(e.item).attr('id'),
                        'oldIndex': oldIndex,
                        'newIndex': newIndex,
                        stack: self.initialPreviewConfig
                    });
                }
            };
            if ($el.data('kvsortable')) {
                $el.kvsortable('destroy');
            }
            $.extend(true, settings, self.fileActionSettings.dragSettings);
            $el.kvsortable(settings);
        },
        _initPreview: function (isInit) {
            var self = this, cap = self.initialCaption || '', out;
            if (!self.previewCache.count()) {
                self._clearPreview();
                if (isInit) {
                    self._setCaption(cap);
                } else {
                    self._initCaption();
                }
                return;
            }
            out = self.previewCache.out();
            cap = isInit && self.initialCaption ? self.initialCaption : out.caption;
            self.$preview.html(out.content);
            self._setInitThumbAttr();
            self._setCaption(cap);
            self._initSortable();
            if (!$h.isEmpty(out.content)) {
                self.$container.removeClass('file-input-new');
            }
        },
        _getZoomButton: function (type) {
            var self = this, label = self.previewZoomButtonIcons[type], css = self.previewZoomButtonClasses[type],
                title = ' title="' + (self.previewZoomButtonTitles[type] || '') + '" ',
                params = title + (type === 'close' ? ' data-dismiss="modal" aria-hidden="true"' : '');
            if (type === 'fullscreen' || type === 'borderless' || type === 'toggleheader') {
                params += ' data-toggle="button" aria-pressed="false" autocomplete="off"';
            }
            return '<button type="button" class="' + css + ' btn-' + type + '"' + params + '>' + label + '</button>';
        },
        _getModalContent: function () {
            var self = this;
            return self._getLayoutTemplate('modal')
                .replace(/\{zoomFrameClass}/g, self.frameClass)
                .replace(/\{heading}/g, self.msgZoomModalHeading)
                .replace(/\{prev}/g, self._getZoomButton('prev'))
                .replace(/\{next}/g, self._getZoomButton('next'))
                .replace(/\{toggleheader}/g, self._getZoomButton('toggleheader'))
                .replace(/\{fullscreen}/g, self._getZoomButton('fullscreen'))
                .replace(/\{borderless}/g, self._getZoomButton('borderless'))
                .replace(/\{close}/g, self._getZoomButton('close'));
        },
        _listenModalEvent: function (event) {
            var self = this, $modal = self.$modal, getParams = function (e) {
                return {
                    sourceEvent: e,
                    previewId: $modal.data('previewId'),
                    modal: $modal
                };
            };
            $modal.on(event + '.bs.modal', function (e) {
                var $btnFull = $modal.find('.btn-fullscreen'), $btnBord = $modal.find('.btn-borderless');
                self._raise('filezoom' + event, getParams(e));
                if (event === 'shown') {
                    $btnBord.removeClass('active').attr('aria-pressed', 'false');
                    $btnFull.removeClass('active').attr('aria-pressed', 'false');
                    if ($modal.hasClass('file-zoom-fullscreen')) {
                        self._maximizeZoomDialog();
                        if ($h.checkFullScreen()) {
                            $btnFull.addClass('active').attr('aria-pressed', 'true');
                        } else {
                            $btnBord.addClass('active').attr('aria-pressed', 'true');
                        }
                    }
                }
            });
        },
        _initZoom: function () {
            var self = this, $dialog, modalMain = self._getLayoutTemplate('modalMain'), modalId = '#' + $h.MODAL_ID;
            if (!self.showPreview) {
                return;
            }
            self.$modal = $(modalId);
            if (!self.$modal || !self.$modal.length) {
                $dialog = $(document.createElement('div')).html(modalMain).insertAfter(self.$container);
                self.$modal = $(modalId).insertBefore($dialog);
                $dialog.remove();
            }
            $h.initModal(self.$modal);
            self.$modal.html(self._getModalContent());
            $.each($h.MODAL_EVENTS, function (key, event) {
                self._listenModalEvent(event);
            });
        },
        _initZoomButtons: function () {
            var self = this, previewId = self.$modal.data('previewId') || '', $first, $last, $preview = self.$preview,
                thumbs = $preview.find($h.FRAMES).toArray(), len = thumbs.length,
                $prev = self.$modal.find('.btn-prev'), $next = self.$modal.find('.btn-next');
            if (thumbs.length < 2) {
                $prev.hide();
                $next.hide();
                return;
            } else {
                $prev.show();
                $next.show();
            }
            if (!len) {
                return;
            }
            $first = $(thumbs[0]);
            $last = $(thumbs[len - 1]);
            $prev.removeAttr('disabled');
            $next.removeAttr('disabled');
            if ($first.length && $first.attr('id') === previewId) {
                $prev.attr('disabled', true);
            }
            if ($last.length && $last.attr('id') === previewId) {
                $next.attr('disabled', true);
            }
        },
        _maximizeZoomDialog: function () {
            var self = this, $modal = self.$modal, $head = $modal.find('.modal-header:visible'),
                $foot = $modal.find('.modal-footer:visible'), $body = $modal.find('.modal-body'),
                h = $(window).height(), diff = 0;
            $modal.addClass('file-zoom-fullscreen');
            if ($head && $head.length) {
                h -= $head.outerHeight(true);
            }
            if ($foot && $foot.length) {
                h -= $foot.outerHeight(true);
            }
            if ($body && $body.length) {
                diff = $body.outerHeight(true) - $body.height();
                h -= diff;
            }
            $modal.find('.kv-zoom-body').height(h);
        },
        _resizeZoomDialog: function (fullScreen) {
            var self = this, $modal = self.$modal, $btnFull = $modal.find('.btn-fullscreen'),
                $btnBord = $modal.find('.btn-borderless');
            if ($modal.hasClass('file-zoom-fullscreen')) {
                $h.toggleFullScreen(false);
                if (!fullScreen) {
                    if (!$btnFull.hasClass('active')) {
                        $modal.removeClass('file-zoom-fullscreen');
                        self.$modal.find('.kv-zoom-body').css('height', self.zoomModalHeight);
                    } else {
                        $btnFull.removeClass('active').attr('aria-pressed', 'false');
                    }
                } else {
                    if (!$btnFull.hasClass('active')) {
                        $modal.removeClass('file-zoom-fullscreen');
                        self._resizeZoomDialog(true);
                        if ($btnBord.hasClass('active')) {
                            $btnBord.removeClass('active').attr('aria-pressed', 'false');
                        }
                    }
                }
            } else {
                if (!fullScreen) {
                    self._maximizeZoomDialog();
                    return;
                }
                $h.toggleFullScreen(true);
            }
            $modal.focus();
        },
        _setZoomContent: function ($frame, animate) {
            var self = this, $content, tmplt, body, title, $body, $dataEl, config, pid = $frame.attr('id'),
                $modal = self.$modal, $prev = $modal.find('.btn-prev'), $next = $modal.find('.btn-next'), $tmp,
                $btnFull = $modal.find('.btn-fullscreen'), $btnBord = $modal.find('.btn-borderless'), cap, size,
                $btnTogh = $modal.find('.btn-toggleheader'), $zoomPreview = self.$preview.find('#zoom-' + pid);
            tmplt = $zoomPreview.attr('data-template') || 'generic';
            $content = $zoomPreview.find('.kv-file-content');
            body = $content.length ? $content.html() : '';
            cap = $frame.data('caption') || '';
            size = $frame.data('size') || '';
            title = cap + ' ' + size;
            $modal.find('.kv-zoom-title').html(title);
            $body = $modal.find('.kv-zoom-body');
            $modal.removeClass('kv-single-content');
            if (animate) {
                $tmp = $body.clone().insertAfter($body);
                $body.html(body).hide();
                $tmp.fadeOut('fast', function () {
                    $body.fadeIn('fast');
                    $tmp.remove();
                });
            } else {
                $body.html(body);
            }
            config = self.previewZoomSettings[tmplt];
            if (config) {
                $dataEl = $body.find('.kv-preview-data');
                $h.addCss($dataEl, 'file-zoom-detail');
                $.each(config, function (key, value) {
                    $dataEl.css(key, value);
                    if (($dataEl.attr('width') && key === 'width') || ($dataEl.attr('height') && key === 'height')) {
                        $dataEl.removeAttr(key);
                    }
                });
            }
            $modal.data('previewId', pid);
            self._handler($prev, 'click', function () {
                self._zoomSlideShow('prev', pid);
            });
            self._handler($next, 'click', function () {
                self._zoomSlideShow('next', pid);
            });
            self._handler($btnFull, 'click', function () {
                self._resizeZoomDialog(true);
            });
            self._handler($btnBord, 'click', function () {
                self._resizeZoomDialog(false);
            });
            self._handler($btnTogh, 'click', function () {
                var $header = $modal.find('.modal-header'), $floatBar = $modal.find('.modal-body .floating-buttons'),
                    ht, $actions = $header.find('.kv-zoom-actions'), resize = function (height) {
                        var $body = self.$modal.find('.kv-zoom-body'), h = self.zoomModalHeight;
                        if ($modal.hasClass('file-zoom-fullscreen')) {
                            h = $body.outerHeight(true);
                            if (!height) {
                                h = h - $header.outerHeight(true);
                            }
                        }
                        $body.css('height', height ? h + height : h);
                    };
                if ($header.is(':visible')) {
                    ht = $header.outerHeight(true);
                    $header.slideUp('slow', function () {
                        $actions.find('.btn').appendTo($floatBar);
                        resize(ht);
                    });
                } else {
                    $floatBar.find('.btn').appendTo($actions);
                    $header.slideDown('slow', function () {
                        resize();
                    });
                }
                $modal.focus();
            });
            self._handler($modal, 'keydown', function (e) {
                var key = e.which || e.keyCode;
                if (key === 37 && !$prev.attr('disabled')) {
                    self._zoomSlideShow('prev', pid);
                }
                if (key === 39 && !$next.attr('disabled')) {
                    self._zoomSlideShow('next', pid);
                }
            });
        },
        _zoomPreview: function ($btn) {
            var self = this, $frame, $modal = self.$modal;
            if (!$btn.length) {
                throw 'Cannot zoom to detailed preview!';
            }
            $h.initModal($modal);
            $modal.html(self._getModalContent());
            $frame = $btn.closest($h.FRAMES);
            self._setZoomContent($frame);
            $modal.modal('show');
            self._initZoomButtons();
        },
        _zoomSlideShow: function (dir, previewId) {
            var self = this, $btn = self.$modal.find('.kv-zoom-actions .btn-' + dir), $targFrame, i,
                thumbs = self.$preview.find($h.FRAMES).toArray(), len = thumbs.length, out;
            if ($btn.attr('disabled')) {
                return;
            }
            for (i = 0; i < len; i++) {
                if ($(thumbs[i]).attr('id') === previewId) {
                    out = dir === 'prev' ? i - 1 : i + 1;
                    break;
                }
            }
            if (out < 0 || out >= len || !thumbs[out]) {
                return;
            }
            $targFrame = $(thumbs[out]);
            if ($targFrame.length) {
                self._setZoomContent($targFrame, true);
            }
            self._initZoomButtons();
            self._raise('filezoom' + dir, {'previewId': previewId, modal: self.$modal});
        },
        _initZoomButton: function () {
            var self = this;
            self.$preview.find('.kv-file-zoom').each(function () {
                var $el = $(this);
                self._handler($el, 'click', function () {
                    self._zoomPreview($el);
                });
            });
        },
        _clearObjects: function ($el) {
            $el.find('video audio').each(function () {
                this.pause();
                $(this).remove();
            });
            $el.find('img object div').each(function () {
                $(this).remove();
            });
        },
        _clearFileInput: function () {
            var self = this, $el = self.$element, $srcFrm, $tmpFrm, $tmpEl;
            self.fileInputCleared = true;
            if ($h.isEmpty($el.val())) {
                return;
            }
            // Fix for IE ver < 11, that does not clear file inputs. Requires a sequence of steps to prevent IE
            // crashing but still allow clearing of the file input.
            if (self.isIE9 || self.isIE10) {
                $srcFrm = $el.closest('form');
                $tmpFrm = $(document.createElement('form'));
                $tmpEl = $(document.createElement('div'));
                $el.before($tmpEl);
                if ($srcFrm.length) {
                    $srcFrm.after($tmpFrm);
                } else {
                    $tmpEl.after($tmpFrm);
                }
                $tmpFrm.append($el).trigger('reset');
                $tmpEl.before($el).remove();
                $tmpFrm.remove();
            } else { // normal input clear behavior for other sane browsers
                $el.val('');
            }
        },
        _resetUpload: function () {
            var self = this;
            self.uploadCache = {content: [], config: [], tags: [], append: true};
            self.uploadCount = 0;
            self.uploadStatus = {};
            self.uploadLog = [];
            self.uploadAsyncCount = 0;
            self.loadedImages = [];
            self.totalImagesCount = 0;
            self.$btnUpload.removeAttr('disabled');
            self._setProgress(0);
            $h.addCss(self.$progress, 'hide');
            self._resetErrors(false);
            self.ajaxAborted = false;
            self.ajaxRequests = [];
            self._resetCanvas();
            self.cacheInitialPreview = {};
            if (self.overwriteInitial) {
                self.initialPreview = [];
                self.initialPreviewConfig = [];
                self.initialPreviewThumbTags = [];
                self.previewCache.data = {
                    content: [],
                    config: [],
                    tags: []
                };
            }
        },
        _resetCanvas: function () {
            var self = this;
            if (self.canvas && self.imageCanvasContext) {
                self.imageCanvasContext.clearRect(0, 0, self.canvas.width, self.canvas.height);
            }
        },
        _hasInitialPreview: function () {
            var self = this;
            return !self.overwriteInitial && self.previewCache.count();
        },
        _resetPreview: function () {
            var self = this, out, cap;
            if (self.previewCache.count()) {
                out = self.previewCache.out();
                self.$preview.html(out.content);
                self._setInitThumbAttr();
                cap = self.initialCaption ? self.initialCaption : out.caption;
                self._setCaption(cap);
            } else {
                self._clearPreview();
                self._initCaption();
            }
            if (self.showPreview) {
                self._initZoom();
                self._initSortable();
            }
        },
        _clearDefaultPreview: function () {
            var self = this;
            self.$preview.find('.file-default-preview').remove();
        },
        _validateDefaultPreview: function () {
            var self = this;
            if (!self.showPreview || $h.isEmpty(self.defaultPreviewContent)) {
                return;
            }
            self.$preview.html('<div class="file-default-preview">' + self.defaultPreviewContent + '</div>');
            self.$container.removeClass('file-input-new');
            self._initClickable();
        },
        _resetPreviewThumbs: function (isAjax) {
            var self = this, out;
            if (isAjax) {
                self._clearPreview();
                self.clearStack();
                return;
            }
            if (self._hasInitialPreview()) {
                out = self.previewCache.out();
                self.$preview.html(out.content);
                self._setInitThumbAttr();
                self._setCaption(out.caption);
                self._initPreviewActions();
            } else {
                self._clearPreview();
            }
        },
        _getLayoutTemplate: function (t) {
            var self = this, template = self.layoutTemplates[t];
            if ($h.isEmpty(self.customLayoutTags)) {
                return template;
            }
            return $h.replaceTags(template, self.customLayoutTags);
        },
        _getPreviewTemplate: function (t) {
            var self = this, template = self.previewTemplates[t];
            if ($h.isEmpty(self.customPreviewTags)) {
                return template;
            }
            return $h.replaceTags(template, self.customPreviewTags);
        },
        _getOutData: function (jqXHR, responseData, filesData) {
            var self = this;
            jqXHR = jqXHR || {};
            responseData = responseData || {};
            filesData = filesData || self.filestack.slice(0) || {};
            return {
                form: self.formdata,
                files: filesData,
                filenames: self.filenames,
                filescount: self.getFilesCount(),
                extra: self._getExtraData(),
                response: responseData,
                reader: self.reader,
                jqXHR: jqXHR
            };
        },
        _getMsgSelected: function (n) {
            var self = this, strFiles = n === 1 ? self.fileSingle : self.filePlural;
            return n > 0 ? self.msgSelected.replace('{n}', n).replace('{files}', strFiles) : self.msgNoFilesSelected;
        },
        _getThumbs: function (css) {
            css = css || '';
            return this.$preview.find($h.FRAMES + ':not(.file-preview-initial)' + css);
        },
        _getExtraData: function (previewId, index) {
            var self = this, data = self.uploadExtraData;
            if (typeof self.uploadExtraData === "function") {
                data = self.uploadExtraData(previewId, index);
            }
            return data;
        },
        _initXhr: function (xhrobj, previewId, fileCount) {
            var self = this;
            if (xhrobj.upload) {
                xhrobj.upload.addEventListener('progress', function (event) {
                    var pct = 0, total = event.total, position = event.loaded || event.position;
                    /** @namespace event.lengthComputable */
                    if (event.lengthComputable) {
                        pct = Math.floor(position / total * 100);
                    }
                    if (previewId) {
                        self._setAsyncUploadStatus(previewId, pct, fileCount);
                    } else {
                        self._setProgress(pct);
                    }
                }, false);
            }
            return xhrobj;
        },
        _ajaxSubmit: function (fnBefore, fnSuccess, fnComplete, fnError, previewId, index) {
            var self = this, settings;
            if (!self._raise('filepreajax', [previewId, index])) {
                return;
            }
            self._uploadExtra(previewId, index);
            settings = $.extend(true, {}, {
                xhr: function () {
                    var xhrobj = $.ajaxSettings.xhr();
                    return self._initXhr(xhrobj, previewId, self.getFileStack().length);
                },
                url: self.uploadUrl,
                type: 'POST',
                dataType: 'json',
                data: self.formdata,
                cache: false,
                processData: false,
                contentType: false,
                beforeSend: fnBefore,
                success: fnSuccess,
                complete: fnComplete,
                error: fnError
            }, self.ajaxSettings);
            self.ajaxRequests.push($.ajax(settings));
        },
        _mergeArray: function (prop, content) {
            var self = this, arr1 = $h.cleanArray(self[prop]), arr2 = $h.cleanArray(content);
            self[prop] = arr1.concat(arr2);
        },
        _initUploadSuccess: function (out, $thumb, allFiles) {
            var self = this, append, data, index, $div, $newCache, content, config, tags, i;
            if (!self.showPreview || typeof out !== 'object' || $.isEmptyObject(out)) {
                return;
            }
            if (out.initialPreview !== undefined && out.initialPreview.length > 0) {
                self.hasInitData = true;
                content = out.initialPreview || [];
                config = out.initialPreviewConfig || [];
                tags = out.initialPreviewThumbTags || [];
                append = out.append === undefined || out.append ? true : false;
                if (content.length > 0 && !$h.isArray(content)) {
                    content = content.split(self.initialPreviewDelimiter);
                }
                self._mergeArray('initialPreview', content);
                self._mergeArray('initialPreviewConfig', config);
                self._mergeArray('initialPreviewThumbTags', tags);
                if ($thumb !== undefined) {
                    if (!allFiles) {
                        index = self.previewCache.add(content, config[0], tags[0], append);
                        data = self.previewCache.get(index, false);
                        $div = $(document.createElement('div')).html(data).hide().insertAfter($thumb);
                        $newCache = $div.find('.kv-zoom-cache');
                        if ($newCache && $newCache.length) {
                            $newCache.insertAfter($thumb);
                        }
                        $thumb.fadeOut('slow', function () {
                            var $newThumb = $div.find('.file-preview-frame');
                            if ($newThumb && $newThumb.length) {
                                $newThumb.insertBefore($thumb).fadeIn('slow').css('display:inline-block');
                            }
                            self._initPreviewActions();
                            self._clearFileInput();
                            $h.cleanZoomCache(self.$preview.find('#zoom-' + $thumb.attr('id')));
                            $thumb.remove();
                            $div.remove();
                            self._initSortable();
                        });
                    } else {
                        i = $thumb.attr('data-fileindex');
                        self.uploadCache.content[i] = content[0];
                        self.uploadCache.config[i] = config[0] || [];
                        self.uploadCache.tags[i] = tags[0] || [];
                        self.uploadCache.append = append;
                    }
                } else {
                    self.previewCache.set(content, config, tags, append);
                    self._initPreview();
                    self._initPreviewActions();
                }
            }
        },
        _initSuccessThumbs: function () {
            var self = this;
            if (!self.showPreview) {
                return;
            }
            self._getThumbs($h.FRAMES + '.file-preview-success').each(function () {
                var $thumb = $(this), $preview = self.$preview, $remove = $thumb.find('.kv-file-remove');
                $remove.removeAttr('disabled');
                self._handler($remove, 'click', function () {
                    var id = $thumb.attr('id'), out = self._raise('filesuccessremove', [id, $thumb.data('fileindex')]);
                    $h.cleanMemory($thumb);
                    if (out === false) {
                        return;
                    }
                    $thumb.fadeOut('slow', function () {
                        $h.cleanZoomCache($preview.find('#zoom-' + id));
                        $thumb.remove();
                        if (!$preview.find($h.FRAMES).length) {
                            self.reset();
                        }
                    });
                });
            });
        },
        _checkAsyncComplete: function () {
            var self = this, previewId, i;
            for (i = 0; i < self.filestack.length; i++) {
                if (self.filestack[i]) {
                    previewId = self.previewInitId + "-" + i;
                    if ($.inArray(previewId, self.uploadLog) === -1) {
                        return false;
                    }
                }
            }
            return (self.uploadAsyncCount === self.uploadLog.length);
        },
        _uploadExtra: function (previewId, index) {
            var self = this, data = self._getExtraData(previewId, index);
            if (data.length === 0) {
                return;
            }
            $.each(data, function (key, value) {
                self.formdata.append(key, value);
            });
        },
        _uploadSingle: function (i, files, allFiles) {
            var self = this, total = self.getFileStack().length, formdata = new FormData(), outData,
                previewId = self.previewInitId + "-" + i, $thumb, chkComplete, $btnUpload, $btnDelete,
                hasPostData = self.filestack.length > 0 || !$.isEmptyObject(self.uploadExtraData),
                $prog = $('#' + previewId).find('.file-thumb-progress'),
                fnBefore, fnSuccess, fnComplete, fnError, updateUploadLog, params = {id: previewId, index: i};
            self.formdata = formdata;
            if (self.showPreview) {
                $thumb = $('#' + previewId + ':not(.file-preview-initial)');
                $btnUpload = $thumb.find('.kv-file-upload');
                $btnDelete = $thumb.find('.kv-file-remove');
                $prog.removeClass('hide');
            }
            if (total === 0 || !hasPostData || ($btnUpload && $btnUpload.hasClass('disabled')) || self._abort(params)) {
                return;
            }
            updateUploadLog = function (i, previewId) {
                self.updateStack(i, undefined);
                self.uploadLog.push(previewId);
                if (self._checkAsyncComplete()) {
                    self.fileBatchCompleted = true;
                }
            };
            chkComplete = function () {
                var u = self.uploadCache, $initThumbs, i, j, len = 0, data = self.cacheInitialPreview;
                if (!self.fileBatchCompleted) {
                    return;
                }
                if (data && data.content) {
                    len = data.content.length;
                }
                setTimeout(function () {
                    if (self.showPreview) {
                        self.previewCache.set(u.content, u.config, u.tags, u.append);
                        if (len) {
                            for (i = 0; i < u.content.length; i++) {
                                j = i + len;
                                data.content[j] = u.content[i];
                                if (data.config.length) {
                                    data.config[j] = u.config[i];
                                }
                                if (data.tags.length) {
                                    data.tags[j] = u.tags[i];
                                }
                            }
                            self.initialPreview = $h.cleanArray(data.content);
                            self.initialPreviewConfig = $h.cleanArray(data.config);
                            self.initialPreviewThumbTags = $h.cleanArray(data.tags);
                        } else {
                            self.initialPreview = u.content;
                            self.initialPreviewConfig = u.config;
                            self.initialPreviewThumbTags = u.tags;
                        }
                        self.cacheInitialPreview = {};
                        if (self.hasInitData) {
                            self._initPreview();
                            self._initPreviewActions();
                        }
                    }
                    self.unlock();
                    self._clearFileInput();
                    $initThumbs = self.$preview.find('.file-preview-initial');
                    if (self.uploadAsync && $initThumbs.length) {
                        $h.addCss($initThumbs, $h.SORT_CSS);
                        self._initSortable();
                    }
                    self._raise('filebatchuploadcomplete', [self.filestack, self._getExtraData()]);
                    self.uploadCount = 0;
                    self.uploadStatus = {};
                    self.uploadLog = [];
                    self._setProgress(101);
                }, 100);
            };
            fnBefore = function (jqXHR) {
                outData = self._getOutData(jqXHR);
                self.fileBatchCompleted = false;
                if (self.showPreview) {
                    if (!$thumb.hasClass('file-preview-success')) {
                        self._setThumbStatus($thumb, 'Loading');
                        $h.addCss($thumb, 'file-uploading');
                    }
                    $btnUpload.attr('disabled', true);
                    $btnDelete.attr('disabled', true);
                }
                if (!allFiles) {
                    self.lock();
                }
                self._raise('filepreupload', [outData, previewId, i]);
                $.extend(true, params, outData);
                if (self._abort(params)) {
                    jqXHR.abort();
                    self._setProgressCancelled();
                }
            };
            fnSuccess = function (data, textStatus, jqXHR) {
                var pid = self.showPreview && $thumb.attr('id') ? $thumb.attr('id') : previewId;
                outData = self._getOutData(jqXHR, data);
                $.extend(true, params, outData);
                setTimeout(function () {
                    if ($h.isEmpty(data) || $h.isEmpty(data.error)) {
                        if (self.showPreview) {
                            self._setThumbStatus($thumb, 'Success');
                            $btnUpload.hide();
                            self._initUploadSuccess(data, $thumb, allFiles);
                            self._setProgress(101, $prog);
                        }
                        self._raise('fileuploaded', [outData, pid, i]);
                        if (!allFiles) {
                            self.updateStack(i, undefined);
                        } else {
                            updateUploadLog(i, pid);
                        }
                    } else {
                        self._showUploadError(data.error, params);
                        self._setPreviewError($thumb, i);
                        if (allFiles) {
                            updateUploadLog(i, pid);
                        }
                    }
                }, 100);
            };
            fnComplete = function () {
                setTimeout(function () {
                    if (self.showPreview) {
                        $btnUpload.removeAttr('disabled');
                        $btnDelete.removeAttr('disabled');
                        $thumb.removeClass('file-uploading');
                    }
                    if (!allFiles) {
                        self.unlock(false);
                        self._clearFileInput();
                    } else {
                        chkComplete();
                    }
                    self._initSuccessThumbs();
                }, 100);
            };
            fnError = function (jqXHR, textStatus, errorThrown) {
                var op = self.ajaxOperations.uploadThumb,
                    errMsg = self._parseError(op, jqXHR, errorThrown, (allFiles ? files[i].name : null));
                setTimeout(function () {
                    if (allFiles) {
                        updateUploadLog(i, previewId);
                    }
                    self.uploadStatus[previewId] = 100;
                    self._setPreviewError($thumb, i);
                    $.extend(true, params, self._getOutData(jqXHR));
                    self._setProgress(101, $prog, self.msgAjaxProgressError.replace('{operation}', op));
                    self._showUploadError(errMsg, params);
                }, 100);
            };
            formdata.append(self.uploadFileAttr, files[i], self.filenames[i]);
            formdata.append('file_id', i);
            self._ajaxSubmit(fnBefore, fnSuccess, fnComplete, fnError, previewId, i);
        },
        _uploadBatch: function () {
            var self = this, files = self.filestack, total = files.length, params = {}, fnBefore, fnSuccess, fnError,
                fnComplete, hasPostData = self.filestack.length > 0 || !$.isEmptyObject(self.uploadExtraData),
                setAllUploaded;
            self.formdata = new FormData();
            if (total === 0 || !hasPostData || self._abort(params)) {
                return;
            }
            setAllUploaded = function () {
                $.each(files, function (key) {
                    self.updateStack(key, undefined);
                });
                self._clearFileInput();
            };
            fnBefore = function (jqXHR) {
                self.lock();
                var outData = self._getOutData(jqXHR);
                if (self.showPreview) {
                    self._getThumbs().each(function () {
                        var $thumb = $(this), $btnUpload = $thumb.find('.kv-file-upload'),
                            $btnDelete = $thumb.find('.kv-file-remove');
                        if (!$thumb.hasClass('file-preview-success')) {
                            self._setThumbStatus($thumb, 'Loading');
                            $h.addCss($thumb, 'file-uploading');
                        }
                        $btnUpload.attr('disabled', true);
                        $btnDelete.attr('disabled', true);
                    });
                }
                self._raise('filebatchpreupload', [outData]);
                if (self._abort(outData)) {
                    jqXHR.abort();
                    self._setProgressCancelled();
                }
            };
            fnSuccess = function (data, textStatus, jqXHR) {
                /** @namespace data.errorkeys */
                var outData = self._getOutData(jqXHR, data), $thumbs = self._getThumbs(':not(.file-preview-error)'), key = 0,
                    keys = $h.isEmpty(data) || $h.isEmpty(data.errorkeys) ? [] : data.errorkeys;
                if ($h.isEmpty(data) || $h.isEmpty(data.error)) {
                    self._raise('filebatchuploadsuccess', [outData]);
                    setAllUploaded();
                    if (self.showPreview) {
                        $thumbs.each(function () {
                            var $thumb = $(this), $btnUpload = $thumb.find('.kv-file-upload');
                            $thumb.find('.kv-file-upload').hide();
                            self._setThumbStatus($thumb, 'Success');
                            $thumb.removeClass('file-uploading');
                            $btnUpload.removeAttr('disabled');
                        });
                        self._initUploadSuccess(data);
                    } else {
                        self.reset();
                    }
                    self._setProgress(101);
                } else {
                    if (self.showPreview) {
                        $thumbs.each(function () {
                            var $thumb = $(this), $btnDelete = $thumb.find('.kv-file-remove'),
                                $btnUpload = $thumb.find('.kv-file-upload');
                            $thumb.removeClass('file-uploading');
                            $btnUpload.removeAttr('disabled');
                            $btnDelete.removeAttr('disabled');
                            if (keys.length === 0) {
                                self._setPreviewError($thumb);
                                return;
                            }
                            if ($.inArray(key, keys) !== -1) {
                                self._setPreviewError($thumb);
                            } else {
                                $thumb.find('.kv-file-upload').hide();
                                self._setThumbStatus($thumb, 'Success');
                                self.updateStack(key, undefined);
                            }
                            key++;
                        });
                        self._initUploadSuccess(data);
                    }
                    self._showUploadError(data.error, outData, 'filebatchuploaderror');
                }
            };
            fnComplete = function () {
                self.unlock();
                self._initSuccessThumbs();
                self._clearFileInput();
                self._raise('filebatchuploadcomplete', [self.filestack, self._getExtraData()]);
            };
            fnError = function (jqXHR, textStatus, errorThrown) {
                var outData = self._getOutData(jqXHR), op = self.ajaxOperations.uploadBatch,
                    errMsg = self._parseError(op, jqXHR, errorThrown);
                self._showUploadError(errMsg, outData, 'filebatchuploaderror');
                self.uploadFileCount = total - 1;
                if (!self.showPreview) {
                    return;
                }
                self._getThumbs().each(function () {
                    var $thumb = $(this), key = $thumb.attr('data-fileindex');
                    $thumb.removeClass('file-uploading');
                    if (self.filestack[key] !== undefined) {
                        self._setPreviewError($thumb);
                    }
                });
                self._getThumbs().removeClass('file-uploading');
                self._getThumbs(' .kv-file-upload').removeAttr('disabled');
                self._getThumbs(' .kv-file-delete').removeAttr('disabled');
                self._setProgress(101, self.$progress, self.msgAjaxProgressError.replace('{operation}', op));
            };
            $.each(files, function (key, data) {
                if (!$h.isEmpty(files[key])) {
                    self.formdata.append(self.uploadFileAttr, data, self.filenames[key]);
                }
            });
            self._ajaxSubmit(fnBefore, fnSuccess, fnComplete, fnError);
        },
        _uploadExtraOnly: function () {
            var self = this, params = {}, fnBefore, fnSuccess, fnComplete, fnError;
            self.formdata = new FormData();
            if (self._abort(params)) {
                return;
            }
            fnBefore = function (jqXHR) {
                self.lock();
                var outData = self._getOutData(jqXHR);
                self._raise('filebatchpreupload', [outData]);
                self._setProgress(50);
                params.data = outData;
                params.xhr = jqXHR;
                if (self._abort(params)) {
                    jqXHR.abort();
                    self._setProgressCancelled();
                }
            };
            fnSuccess = function (data, textStatus, jqXHR) {
                var outData = self._getOutData(jqXHR, data);
                if ($h.isEmpty(data) || $h.isEmpty(data.error)) {
                    self._raise('filebatchuploadsuccess', [outData]);
                    self._clearFileInput();
                    self._initUploadSuccess(data);
                    self._setProgress(101);
                } else {
                    self._showUploadError(data.error, outData, 'filebatchuploaderror');
                }
            };
            fnComplete = function () {
                self.unlock();
                self._clearFileInput();
                self._raise('filebatchuploadcomplete', [self.filestack, self._getExtraData()]);
            };
            fnError = function (jqXHR, textStatus, errorThrown) {
                var outData = self._getOutData(jqXHR), op = self.ajaxOperations.uploadExtra,
                    errMsg = self._parseError(op, jqXHR, errorThrown);
                params.data = outData;
                self._showUploadError(errMsg, outData, 'filebatchuploaderror');
                self._setProgress(101, self.$progress, self.msgAjaxProgressError.replace('{operation}', op));
            };
            self._ajaxSubmit(fnBefore, fnSuccess, fnComplete, fnError);
        },
        _deleteFileIndex: function ($frame) {
            var self = this, ind = $frame.attr('data-fileindex');
            if (ind.substring(0, 5) === 'init_') {
                ind = parseInt(ind.replace('init_', ''));
                self.initialPreview = $h.spliceArray(self.initialPreview, ind);
                self.initialPreviewConfig = $h.spliceArray(self.initialPreviewConfig, ind);
                self.initialPreviewThumbTags = $h.spliceArray(self.initialPreviewThumbTags, ind);
                self.$preview.find($h.FRAMES).each(function () {
                    var $nFrame = $(this), nInd = $nFrame.attr('data-fileindex');
                    if (nInd.substring(0, 5) === 'init_') {
                        nInd = parseInt(nInd.replace('init_', ''));
                        if (nInd > ind) {
                            nInd--;
                            $nFrame.attr('data-fileindex', 'init_' + nInd);
                        }
                    }
                });
                if (self.uploadAsync) {
                    self.cacheInitialPreview = self.getPreview();
                }
            }
        },
        _initFileActions: function () {
            var self = this, $preview = self.$preview;
            if (!self.showPreview) {
                return;
            }
            self._initZoomButton();
            $preview.find($h.FRAMES + ' .kv-file-remove').each(function () {
                var $el = $(this), $frame = $el.closest($h.FRAMES), hasError, id = $frame.attr('id'),
                    ind = $frame.attr('data-fileindex'), n, cap, status;
                self._handler($el, 'click', function () {
                    status = self._raise('filepreremove', [id, ind]);
                    if (status === false || !self._validateMinCount()) {
                        return false;
                    }
                    hasError = $frame.hasClass('file-preview-error');
                    $h.cleanMemory($frame);
                    $frame.fadeOut('slow', function () {
                        $h.cleanZoomCache($preview.find('#zoom-' + id));
                        self.updateStack(ind, undefined);
                        self._clearObjects($frame);
                        $frame.remove();
                        if (id && hasError) {
                            self.$errorContainer.find('li[data-file-id="' + id + '"]').fadeOut('fast', function () {
                                $(this).remove();
                                if (!self._errorsExist()) {
                                    self._resetErrors();
                                }
                            });
                        }
                        self._clearFileInput();
                        var filestack = self.getFileStack(true), chk = self.previewCache.count(),
                            len = filestack.length,
                            hasThumb = self.showPreview && $preview.find($h.FRAMES).length;
                        if (len === 0 && chk === 0 && !hasThumb) {
                            self.reset();
                        } else {
                            n = chk + len;
                            cap = n > 1 ? self._getMsgSelected(n) : (filestack[0] ? self._getFileNames()[0] : '');
                            self._setCaption(cap);
                        }
                        self._raise('fileremoved', [id, ind]);
                    });
                });
            });
            self.$preview.find($h.FRAMES + ' .kv-file-upload').each(function () {
                var $el = $(this);
                self._handler($el, 'click', function () {
                    var $frame = $el.closest($h.FRAMES), ind = $frame.attr('data-fileindex');
                    if (!$frame.hasClass('file-preview-error')) {
                        self._uploadSingle(ind, self.filestack, false);
                    }
                });
            });
        },
        _initPreviewActions: function () {
            var self = this, $preview = self.$preview, deleteExtraData = self.deleteExtraData || {},
                btnRemove = $h.FRAMES + ' .kv-file-remove',
                resetProgress = function () {
                    var hasFiles = self.isUploadable ? self.previewCache.count() : self.$element.get(0).files.length;
                    if ($preview.find(btnRemove).length === 0 && !hasFiles) {
                        self.reset();
                        self.initialCaption = '';
                    }
                };
            self._initZoomButton();
            $preview.find(btnRemove).each(function () {
                var $el = $(this), vUrl = $el.data('url') || self.deleteUrl, vKey = $el.data('key');
                if ($h.isEmpty(vUrl) || vKey === undefined) {
                    return;
                }
                var $frame = $el.closest($h.FRAMES), cache = self.previewCache.data,
                    settings, params, index = $frame.data('fileindex'), config, extraData;
                index = parseInt(index.replace('init_', ''));
                config = $h.isEmpty(cache.config) && $h.isEmpty(cache.config[index]) ? null : cache.config[index];
                extraData = $h.isEmpty(config) || $h.isEmpty(config.extra) ? deleteExtraData : config.extra;
                if (typeof extraData === "function") {
                    extraData = extraData();
                }
                params = {id: $el.attr('id'), key: vKey, extra: extraData};
                settings = $.extend(true, {}, {
                    url: vUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: $.extend(true, {}, {key: vKey}, extraData),
                    beforeSend: function (jqXHR) {
                        self.ajaxAborted = false;
                        self._raise('filepredelete', [vKey, jqXHR, extraData]);
                        if (self.ajaxAborted) {
                            jqXHR.abort();
                        } else {
                            $h.addCss($frame, 'file-uploading');
                            $h.addCss($el, 'disabled');
                        }
                    },
                    success: function (data, textStatus, jqXHR) {
                        var n, cap;
                        if ($h.isEmpty(data) || $h.isEmpty(data.error)) {
                            self.previewCache.init();
                            index = parseInt(($frame.data('fileindex')).replace('init_', ''));
                            self.previewCache.unset(index);
                            n = self.previewCache.count();
                            cap = n > 0 ? self._getMsgSelected(n) : '';
                            self._deleteFileIndex($frame);
                            self._setCaption(cap);
                            self._raise('filedeleted', [vKey, jqXHR, extraData]);
                        } else {
                            params.jqXHR = jqXHR;
                            params.response = data;
                            self._showError(data.error, params, 'filedeleteerror');
                            $frame.removeClass('file-uploading');
                            $el.removeClass('disabled');
                            resetProgress();
                            return;
                        }
                        $frame.removeClass('file-uploading').addClass('file-deleted');
                        $frame.fadeOut('slow', function () {
                            $h.cleanZoomCache($preview.find('#zoom-' + $frame.attr('id')));
                            self._clearObjects($frame);
                            $frame.remove();
                            resetProgress();
                            if (!n && self.getFileStack().length === 0) {
                                self._setCaption('');
                                self.reset();
                            }
                        });
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        var op = self.ajaxOperations.deleteThumb, errMsg = self._parseError(op, jqXHR, errorThrown);
                        params.jqXHR = jqXHR;
                        params.response = {};
                        self._showError(errMsg, params, 'filedeleteerror');
                        $frame.removeClass('file-uploading');
                        resetProgress();
                    }
                }, self.ajaxDeleteSettings);
                self._handler($el, 'click', function () {
                    if (!self._validateMinCount()) {
                        return false;
                    }
                    $.ajax(settings);
                });
            });
        },
        _hideFileIcon: function () {
            if (this.overwriteInitial) {
                this.$captionContainer.find('.kv-caption-icon').hide();
            }
        },
        _showFileIcon: function () {
            this.$captionContainer.find('.kv-caption-icon').show();
        },
        _getSize: function (bytes) {
            var self = this, size = parseFloat(bytes), i, func = self.fileSizeGetter, sizes, out;
            if (!$.isNumeric(bytes) || !$.isNumeric(size)) {
                return '';
            }
            if (typeof func === 'function') {
                out = func(size);
            } else {
                if (size === 0) {
                    out = '0.00 B';
                } else {
                    i = Math.floor(Math.log(size) / Math.log(1024));
                    sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
                    out = (size / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + sizes[i];
                }
            }
            return self._getLayoutTemplate('size').replace('{sizeText}', out);
        },
        _generatePreviewTemplate: function (cat, data, fname, ftype, previewId, isError, size, frameClass, foot, ind, templ) {
            var self = this, caption = self.slug(fname), prevContent, zoomContent = '',
                config = self.previewSettings[cat] || self.defaults.previewSettings[cat],
                w = config && config.width ? config.width : '', h = config && config.height ? config.height : '',
                footer = foot || self._renderFileFooter(caption, size, ($h.isEmpty(w) ? 'auto' : w), isError),
                hasIconSetting = self._getPreviewIcon(fname),
                forcePrevIcon = hasIconSetting && self.preferIconicPreview,
                forceZoomIcon = hasIconSetting && self.preferIconicZoomPreview,
                getContent = function (c, d, zoom, frameCss) {
                    var id = zoom ? 'zoom-' + previewId : previewId, tmplt = self._getPreviewTemplate(c),
                        css = (frameClass || '') + ' ' + frameCss;
                    if (self.frameClass) {
                        css = self.frameClass + ' ' + css;
                    }
                    if (zoom) {
                        css = css.replace(' ' + $h.SORT_CSS, '');
                    }
                    tmplt = self._parseFilePreviewIcon(tmplt, fname);
                    if (c === 'text') {
                        d = $h.htmlEncode(d);
                    }
                    return tmplt.replace(/\{previewId}/g, id).replace(/\{caption}/g, caption)
                        .replace(/\{frameClass}/g, css).replace(/\{type}/g, ftype).replace(/\{fileindex}/g, ind)
                        .replace(/\{width}/g, w).replace(/\{height}/g, h)
                        .replace(/\{footer}/g, footer).replace(/\{data}/g, d).replace(/\{template}/g, templ || cat);
                };
            ind = ind || previewId.slice(previewId.lastIndexOf('-') + 1);
            if (self.fileActionSettings.showZoom) {
                zoomContent = getContent((forceZoomIcon ? 'other' : cat), data, true, 'kv-zoom-thumb');
            }
            zoomContent = '\n' + self._getLayoutTemplate('zoomCache').replace('{zoomContent}', zoomContent);
            prevContent = getContent((forcePrevIcon ? 'other' : cat), data, false, 'kv-preview-thumb');
            return prevContent + zoomContent;
        },
        _previewDefault: function (file, previewId, isDisabled) {
            var self = this, $preview = self.$preview;
            if (!self.showPreview) {
                return;
            }
            var fname = file ? file.name : '', ftype = file ? file.type : '', content, size = file.size || 0,
                caption = self.slug(fname), isError = isDisabled === true && !self.isUploadable,
                data = $h.objUrl.createObjectURL(file);
            self._clearDefaultPreview();
            content = self._generatePreviewTemplate('other', data, fname, ftype, previewId, isError, size);
            $preview.append("\n" + content);
            self._setThumbAttr(previewId, caption, size);
            if (isDisabled === true && self.isUploadable) {
                self._setThumbStatus($('#' + previewId), 'Error');
            }
        },
        _previewFile: function (i, file, theFile, previewId, data) {
            if (!this.showPreview) {
                return;
            }
            var self = this, cat = self._parseFileType(file), fname = file ? file.name : '', caption = self.slug(fname),
                types = self.allowedPreviewTypes, mimes = self.allowedPreviewMimeTypes, $preview = self.$preview,
                chkTypes = types && types.indexOf(cat) >= 0, size = file.size || 0,
                iData = (cat === 'text' || cat === 'html' || cat === 'image') ? theFile.target.result : data, content,
                chkMimes = mimes && mimes.indexOf(file.type) !== -1;
            /** @namespace window.DOMPurify */
            if (cat === 'html' && self.purifyHtml && window.DOMPurify) {
                iData = window.DOMPurify.sanitize(iData);
            }
            if (chkTypes || chkMimes) {
                content = self._generatePreviewTemplate(cat, iData, fname, file.type, previewId, false, size);
                self._clearDefaultPreview();
                $preview.append("\n" + content);
                self._validateImage(previewId, caption, file.type);
            } else {
                self._previewDefault(file, previewId);
            }
            self._setThumbAttr(previewId, caption, size);
            self._initSortable();
        },
        _setThumbAttr: function (id, caption, size) {
            var self = this, $frame = $('#' + id);
            if ($frame.length) {
                size = size && size > 0 ? self._getSize(size) : '';
                $frame.data({'caption': caption, 'size': size});
            }
        },
        _setInitThumbAttr: function () {
            var self = this, data = self.previewCache.data, len = self.previewCache.count(true), config,
                caption, size, previewId;
            if (len === 0) {
                return;
            }
            for (var i = 0; i < len; i++) {
                config = data.config[i];
                previewId = self.previewInitId + '-' + 'init_' + i;
                caption = $h.ifSet('caption', config, $h.ifSet('filename', config));
                size = $h.ifSet('size', config);
                self._setThumbAttr(previewId, caption, size);
            }
        },
        _slugDefault: function (text) {
            return $h.isEmpty(text) ? '' : String(text).replace(/[\-\[\]\/\{}:;#%=\(\)\*\+\?\\\^\$\|<>&"']/g, '_');
        },
        _readFiles: function (files) {
            this.reader = new FileReader();
            var self = this, $el = self.$element, $preview = self.$preview, reader = self.reader,
                $container = self.$previewContainer, $status = self.$previewStatus, msgLoading = self.msgLoading,
                msgProgress = self.msgProgress, previewInitId = self.previewInitId, numFiles = files.length,
                settings = self.fileTypeSettings, ctr = self.filestack.length, readFile,
                fileTypes = self.allowedFileTypes, typLen = fileTypes ? fileTypes.length : 0,
                fileExt = self.allowedFileExtensions, strExt = $h.isEmpty(fileExt) ? '' : fileExt.join(', '),
                maxPreviewSize = self.maxFilePreviewSize && parseFloat(self.maxFilePreviewSize),
                canPreview = $preview.length && (!maxPreviewSize || isNaN(maxPreviewSize)),
                throwError = function (msg, file, previewId, index) {
                    var p1 = $.extend(true, {}, self._getOutData({}, {}, files), {id: previewId, index: index}),
                        p2 = {id: previewId, index: index, file: file, files: files};
                    self._previewDefault(file, previewId, true);
                    if (self.isUploadable) {
                        self.addToStack(undefined);
                        setTimeout(function () {
                            readFile(index + 1);
                        }, 100);
                    }
                    self._initFileActions();
                    if (self.removeFromPreviewOnError) {
                        $('#' + previewId).remove();
                    }
                    return self.isUploadable ? self._showUploadError(msg, p1) : self._showError(msg, p2);
                };

            self.loadedImages = [];
            self.totalImagesCount = 0;

            $.each(files, function (key, file) {
                var func = self.fileTypeSettings.image;
                if (func && func(file.type)) {
                    self.totalImagesCount++;
                }
            });
            readFile = function (i) {
                if ($h.isEmpty($el.attr('multiple'))) {
                    numFiles = 1;
                }
                if (i >= numFiles) {
                    if (self.isUploadable && self.filestack.length > 0) {
                        self._raise('filebatchselected', [self.getFileStack()]);
                    } else {
                        self._raise('filebatchselected', [files]);
                    }
                    $container.removeClass('file-thumb-loading');
                    $status.html('');
                    return;
                }
                var node = ctr + i, previewId = previewInitId + "-" + node, isText, isImage, file = files[i], fSizeKB,
                    caption = file.name ? self.slug(file.name) : '', fileSize = (file.size || 0) / 1000, j, msg,
                    fileExtExpr = '', previewData = $h.objUrl.createObjectURL(file), typ, chk, typ1, typ2,
                    fileCount = 0, strTypes = '', func;
                if (typLen > 0) {
                    for (j = 0; j < typLen; j++) {
                        typ1 = fileTypes[j];
                        typ2 = self.msgFileTypes[typ1] || typ1;
                        strTypes += j === 0 ? typ2 : ', ' + typ2;
                    }
                }
                if (caption === false) {
                    readFile(i + 1);
                    return;
                }
                if (caption.length === 0) {
                    msg = self.msgInvalidFileName.replace('{name}', $h.htmlEncode(file.name));
                    self.isError = throwError(msg, file, previewId, i);
                    return;
                }
                if (!$h.isEmpty(fileExt)) {
                    fileExtExpr = new RegExp('\\.(' + fileExt.join('|') + ')$', 'i');
                }
                fSizeKB = fileSize.toFixed(2);
                if (self.maxFileSize > 0 && fileSize > self.maxFileSize) {
                    msg = self.msgSizeTooLarge.replace('{name}', caption).replace('{size}', fSizeKB)
                        .replace('{maxSize}', self.maxFileSize);
                    self.isError = throwError(msg, file, previewId, i);
                    return;
                }
                if (self.minFileSize !== null && fileSize <= $h.getNum(self.minFileSize)) {
                    msg = self.msgSizeTooSmall.replace('{name}', caption).replace('{size}', fSizeKB)
                        .replace('{minSize}', self.minFileSize);
                    self.isError = throwError(msg, file, previewId, i);
                    return;
                }
                if (!$h.isEmpty(fileTypes) && $h.isArray(fileTypes)) {
                    for (j = 0; j < fileTypes.length; j += 1) {
                        typ = fileTypes[j];
                        func = settings[typ];
                        fileCount += !func || (typeof func !== 'function') ? 0 : (func(file.type, file.name) ? 1 : 0);
                    }
                    if (fileCount === 0) {
                        msg = self.msgInvalidFileType.replace('{name}', caption).replace('{types}', strTypes);
                        self.isError = throwError(msg, file, previewId, i);
                        return;
                    }
                }
                if (fileCount === 0 && !$h.isEmpty(fileExt) && $h.isArray(fileExt) && !$h.isEmpty(fileExtExpr)) {
                    chk = $h.compare(caption, fileExtExpr);
                    fileCount += $h.isEmpty(chk) ? 0 : chk.length;
                    if (fileCount === 0) {
                        msg = self.msgInvalidFileExtension.replace('{name}', caption).replace('{extensions}', strExt);
                        self.isError = throwError(msg, file, previewId, i);
                        return;
                    }
                }
                if (!self.showPreview) {
                    self.addToStack(file);
                    setTimeout(function () {
                        readFile(i + 1);
                    }, 100);
                    self._raise('fileloaded', [file, previewId, i, reader]);
                    return;
                }
                if (!canPreview && fileSize > maxPreviewSize) {
                    self.addToStack(file);
                    $container.addClass('file-thumb-loading');
                    self._previewDefault(file, previewId);
                    self._initFileActions();
                    self._updateFileDetails(numFiles);
                    readFile(i + 1);
                    return;
                }
                if ($preview.length && FileReader !== undefined) {
                    $status.html(msgLoading.replace('{index}', i + 1).replace('{files}', numFiles));
                    $container.addClass('file-thumb-loading');
                    reader.onerror = function (evt) {
                        self._errorHandler(evt, caption);
                    };
                    reader.onload = function (theFile) {
                        self._previewFile(i, file, theFile, previewId, previewData);
                        self._initFileActions();
                    };
                    reader.onloadend = function () {
                        msg = msgProgress.replace('{index}', i + 1).replace('{files}', numFiles)
                            .replace('{percent}', 50).replace('{name}', caption);
                        setTimeout(function () {
                            $status.html(msg);
                            self._updateFileDetails(numFiles);
                            readFile(i + 1);
                        }, 100);
                        self._raise('fileloaded', [file, previewId, i, reader]);
                    };
                    reader.onprogress = function (data) {
                        if (data.lengthComputable) {
                            var fact = (data.loaded / data.total) * 100, progress = Math.ceil(fact);
                            msg = msgProgress.replace('{index}', i + 1).replace('{files}', numFiles)
                                .replace('{percent}', progress).replace('{name}', caption);
                            setTimeout(function () {
                                $status.html(msg);
                            }, 100);
                        }
                    };
                    isText = settings.text;
                    isImage = settings.image;

                    if (isText(file.type, caption)) {
                        reader.readAsText(file, self.textEncoding);
                    } else {
                        if (isImage(file.type, caption)) {
                            reader.readAsDataURL(file);
                        } else {
                            reader.readAsArrayBuffer(file);
                        }
                    }
                } else {
                    self._previewDefault(file, previewId);
                    setTimeout(function () {
                        readFile(i + 1);
                        self._updateFileDetails(numFiles);
                    }, 100);
                    self._raise('fileloaded', [file, previewId, i, reader]);
                }
                self.addToStack(file);
            };

            readFile(0);
            self._updateFileDetails(numFiles, false);
        },
        _updateFileDetails: function (numFiles) {
            var self = this, $el = self.$element, fileStack = self.getFileStack(),
                name = ($h.isIE(9) && $h.findFileName($el.val())) ||
                    ($el[0].files[0] && $el[0].files[0].name) || (fileStack.length && fileStack[0].name) || '',
                label = self.slug(name), n = self.isUploadable ? fileStack.length : numFiles,
                nFiles = self.previewCache.count() + n, log = n > 1 ? self._getMsgSelected(nFiles) : label;
            if (self.isError) {
                self.$previewContainer.removeClass('file-thumb-loading');
                self.$previewStatus.html('');
                self.$captionContainer.find('.kv-caption-icon').hide();
            } else {
                self._showFileIcon();
            }
            self._setCaption(log, self.isError);
            self.$container.removeClass('file-input-new file-input-ajax-new');
            if (arguments.length === 1) {
                self._raise('fileselect', [numFiles, label]);
            }
            if (self.previewCache.count()) {
                self._initPreviewActions();
            }
        },
        _setThumbStatus: function ($thumb, status) {
            var self = this;
            if (!self.showPreview) {
                return;
            }
            var icon = 'indicator' + status, msg = icon + 'Title',
                css = 'file-preview-' + status.toLowerCase(),
                $indicator = $thumb.find('.file-upload-indicator'),
                config = self.fileActionSettings;
            $thumb.removeClass('file-preview-success file-preview-error file-preview-loading');
            if (status === 'Error') {
                $thumb.find('.kv-file-upload').attr('disabled', true);
            }
            if (status === 'Success') {
                $thumb.find('.file-drag-handle').remove();
                $indicator.css('margin-left', 0);
            }
            $indicator.html(config[icon]);
            $indicator.attr('title', config[msg]);
            $thumb.addClass(css);
        },
        _setProgressCancelled: function () {
            var self = this;
            self._setProgress(101, self.$progress, self.msgCancelled);
        },
        _setProgress: function (p, $el, error) {
            var self = this, pct = Math.min(p, 100), out, status, pctLimit = self.progressUploadThreshold,
                t = p <= 100 ? self.progressTemplate : self.progressCompleteTemplate,
                template = pct < 100 ? self.progressTemplate : (error ? self.progressErrorTemplate : t);
            $el = $el || self.$progress;
            if (!$h.isEmpty(template)) {
                if (pctLimit && pct > pctLimit && p <= 100) {
                    out = template.replace(/\{percent}/g, pctLimit).replace(/\{status}/g, self.msgUploadThreshold);
                } else {
                    status = p > 100 ? self.msgUploadEnd : pct + '%';
                    out = template.replace(/\{percent}/g, pct).replace(/\{status}/g, status);
                }
                $el.html(out);
                if (error) {
                    $el.find('[role="progressbar"]').html(error);
                }
            }
        },
        _setFileDropZoneTitle: function () {
            var self = this, $zone = self.$container.find('.file-drop-zone'), title = self.dropZoneTitle, strFiles;
            if (self.isClickable) {
                strFiles = $h.isEmpty(self.$element.attr('multiple')) ? self.fileSingle : self.filePlural;
                title += self.dropZoneClickTitle.replace('{files}', strFiles);
            }
            $zone.find('.' + self.dropZoneTitleClass).remove();
            if (!self.isUploadable || !self.showPreview || $zone.length === 0 || self.getFileStack().length > 0 || !self.dropZoneEnabled) {
                return;
            }
            if ($zone.find($h.FRAMES).length === 0 && $h.isEmpty(self.defaultPreviewContent)) {
                $zone.prepend('<div class="' + self.dropZoneTitleClass + '">' + title + '</div>');
            }
            self.$container.removeClass('file-input-new');
            $h.addCss(self.$container, 'file-input-ajax-new');
        },
        _setAsyncUploadStatus: function (previewId, pct, total) {
            var self = this, sum = 0;
            self._setProgress(pct, $('#' + previewId).find('.file-thumb-progress'));
            self.uploadStatus[previewId] = pct;
            $.each(self.uploadStatus, function (key, value) {
                sum += value;
            });
            self._setProgress(Math.floor(sum / total));

        },
        _validateMinCount: function () {
            var self = this, len = self.isUploadable ? self.getFileStack().length : self.$element.get(0).files.length;
            if (self.validateInitialCount && self.minFileCount > 0 && self._getFileCount(len - 1) < self.minFileCount) {
                self._noFilesError({});
                return false;
            }
            return true;
        },
        _getFileCount: function (fileCount) {
            var self = this, addCount = 0;
            if (self.validateInitialCount && !self.overwriteInitial) {
                addCount = self.previewCache.count();
                fileCount += addCount;
            }
            return fileCount;
        },
        _getFileId: function (file) {
            var self = this, custom = self.generateFileId, relativePath;
            if (typeof custom === 'function') {
                return custom(file, event);
            }
            if (!file) {
                return null;
            }
            /** @namespace file.webkitRelativePath */
            relativePath = file.webkitRelativePath || file.fileName || file.name || null;
            if (!relativePath) {
                return null;
            }
            return (file.size + '-' + relativePath.replace(/[^0-9a-zA-Z_-]/img, ''));
        },
        _getFileName: function (file) {
            return file && file.name ? this.slug(file.name) : undefined;
        },
        _getFileIds: function (skipNull) {
            var self = this;
            return self.fileids.filter(function (n) {
                return (skipNull ? n !== undefined : n !== undefined && n !== null);
            });
        },
        _getFileNames: function (skipNull) {
            var self = this;
            return self.filenames.filter(function (n) {
                return (skipNull ? n !== undefined : n !== undefined && n !== null);
            });
        },
        _setPreviewError: function ($thumb, i, val) {
            var self = this;
            if (i !== undefined) {
                self.updateStack(i, val);
            }
            if (self.removeFromPreviewOnError) {
                $thumb.remove();
            } else {
                self._setThumbStatus($thumb, 'Error');
            }
        },
        _checkDimensions: function (i, chk, $img, $thumb, fname, type, params) {
            var self = this, msg, dim, tag = chk === 'Small' ? 'min' : 'max', limit = self[tag + 'Image' + type],
                $imgEl, isValid;
            if ($h.isEmpty(limit) || !$img.length) {
                return;
            }
            $imgEl = $img[0];
            dim = (type === 'Width') ? $imgEl.naturalWidth || $imgEl.width : $imgEl.naturalHeight || $imgEl.height;
            isValid = chk === 'Small' ? dim >= limit : dim <= limit;
            if (isValid) {
                return;
            }
            msg = self['msgImage' + type + chk].replace('{name}', fname).replace('{size}', limit);
            self._showUploadError(msg, params);
            self._setPreviewError($thumb, i, null);
        },
        _validateImage: function (previewId, fname, ftype) {
            var self = this, $preview = self.$preview, params, w1, w2, $thumb = $preview.find("#" + previewId),
                i = $thumb.attr('data-fileindex'), $img = $thumb.find('img');
            fname = fname || 'Untitled';
            if (!$img.length) {
                return;
            }
            self._handler($img, 'load', function () {
                w1 = $thumb.width();
                w2 = $preview.width();
                if (w1 > w2) {
                    $img.css('width', '100%');
                    $thumb.css('width', '97%');
                }
                params = {ind: i, id: previewId};
                self._checkDimensions(i, 'Small', $img, $thumb, fname, 'Width', params);
                self._checkDimensions(i, 'Small', $img, $thumb, fname, 'Height', params);
                if (!self.resizeImage) {
                    self._checkDimensions(i, 'Large', $img, $thumb, fname, 'Width', params);
                    self._checkDimensions(i, 'Large', $img, $thumb, fname, 'Height', params);
                }
                self._raise('fileimageloaded', [previewId]);
                self.loadedImages.push({ind: i, img: $img, thumb: $thumb, pid: previewId, typ: ftype});
                self._validateAllImages();
            });
        },
        _validateAllImages: function () {
            var self = this, i, counter = {val: 0}, numImgs = self.loadedImages.length;
            if (numImgs !== self.totalImagesCount) {
                return;
            }
            self._raise('fileimagesloaded');
            if (!self.resizeImage) {
                return;
            }
            for (i = 0; i < self.loadedImages.length; i++) {
                self._getResizedImage(self.loadedImages[i], counter, numImgs);
            }
        },
        _getResizedImage: function (config, counter, numImgs) {
            var self = this, img = $(config.img)[0], width = img.naturalWidth, height = img.naturalHeight,
                ratio = 1, maxWidth = self.maxImageWidth || width, maxHeight = self.maxImageHeight || height,
                isValidImage = !!(width && height), chkWidth, chkHeight, canvas = self.imageCanvas,
                context = self.imageCanvasContext, type = config.typ, pid = config.pid, ind = config.ind,
                thumb = config.thumb, throwError, msg;
            throwError = function (msg, params, ev) {
                if (self.isUploadable) {
                    self._showUploadError(msg, params, ev);
                } else {
                    self._showError(msg, params, ev);
                }
                self._setPreviewError(thumb, ind);
            };
            if (!self.filestack[ind] || !isValidImage || (width <= maxWidth && height <= maxHeight)) {
                if (isValidImage && self.filestack[ind]) {
                    self._raise('fileimageresized', [pid, ind]);
                }
                counter.val++;
                if (counter.val === numImgs) {
                    self._raise('fileimagesresized');
                }
                if (!isValidImage) {
                    throwError(self.msgImageResizeError, {id: pid, 'index': ind}, 'fileimageresizeerror');
                    return;
                }
            }
            type = type || self.resizeDefaultImageType;
            chkWidth = width > maxWidth;
            chkHeight = height > maxHeight;
            if (self.resizePreference === 'width') {
                ratio = chkWidth ? maxWidth / width : (chkHeight ? maxHeight / height : 1);
            } else {
                ratio = chkHeight ? maxHeight / height : (chkWidth ? maxWidth / width : 1);
            }
            self._resetCanvas();
            width *= ratio;
            height *= ratio;
            canvas.width = width;
            canvas.height = height;
            try {
                context.drawImage(img, 0, 0, width, height);
                canvas.toBlob(function (blob) {
                    self.filestack[ind] = blob;
                    self._raise('fileimageresized', [pid, ind]);
                    counter.val++;
                    if (counter.val === numImgs) {
                        self._raise('fileimagesresized', [undefined, undefined]);
                    }
                    if (!(blob instanceof Blob)) {
                        throwError(self.msgImageResizeError, {id: pid, 'index': ind}, 'fileimageresizeerror');
                    }
                }, type, self.resizeQuality);
            }
            catch (err) {
                counter.val++;
                if (counter.val === numImgs) {
                    self._raise('fileimagesresized', [undefined, undefined]);
                }
                msg = self.msgImageResizeException.replace('{errors}', err.message);
                throwError(msg, {id: pid, 'index': ind}, 'fileimageresizeexception');
            }
        },
        _initBrowse: function ($container) {
            var self = this;
            if (self.showBrowse) {
                self.$btnFile = $container.find('.btn-file');
                self.$btnFile.append(self.$element);
            } else {
                self.$element.hide();
            }
        },
        _initCaption: function () {
            var self = this, cap = self.initialCaption || '';
            if (self.overwriteInitial || $h.isEmpty(cap)) {
                self.$caption.html('');
                return false;
            }
            self._setCaption(cap);
            return true;
        },
        _setCaption: function (content, isError) {
            var self = this, title, out, n, cap, stack = self.getFileStack();
            if (!self.$caption.length) {
                return;
            }
            if (isError) {
                title = $('<div>' + self.msgValidationError + '</div>').text();
                n = stack.length;
                if (n) {
                    cap = n === 1 && stack[0] ? self._getFileNames()[0] : self._getMsgSelected(n);
                } else {
                    cap = self._getMsgSelected(self.msgNo);
                }
                out = '<span class="' + self.msgValidationErrorClass + '">' + self.msgValidationErrorIcon +
                    ($h.isEmpty(content) ? cap : content) + '</span>';
            } else {
                if ($h.isEmpty(content)) {
                    return;
                }
                title = $('<div>' + content + '</div>').text();
                out = self._getLayoutTemplate('fileIcon') + title;
            }
            self.$caption.html(out);
            self.$caption.attr('title', title);
            self.$captionContainer.find('.file-caption-ellipsis').attr('title', title);
        },
        _createContainer: function () {
            var self = this, $container = $(document.createElement("div"))
                .attr({"class": 'file-input file-input-new'})
                .html(self._renderMain());
            self.$element.before($container);
            self._initBrowse($container);
            if (self.theme) {
                $container.addClass('theme-' + self.theme);
            }
            return $container;
        },
        _refreshContainer: function () {
            var self = this, $container = self.$container;
            $container.before(self.$element);
            $container.html(self._renderMain());
            self._initBrowse($container);
        },
        _renderMain: function () {
            var self = this, dropCss = (self.isUploadable && self.dropZoneEnabled) ? ' file-drop-zone' : 'file-drop-disabled',
                close = !self.showClose ? '' : self._getLayoutTemplate('close'),
                preview = !self.showPreview ? '' : self._getLayoutTemplate('preview')
                    .replace(/\{class}/g, self.previewClass)
                    .replace(/\{dropClass}/g, dropCss),
                css = self.isDisabled ? self.captionClass + ' file-caption-disabled' : self.captionClass,
                caption = self.captionTemplate.replace(/\{class}/g, css + ' kv-fileinput-caption');
            return self.mainTemplate.replace(/\{class}/g, self.mainClass +
                (!self.showBrowse && self.showCaption ? ' no-browse' : ''))
                .replace(/\{preview}/g, preview)
                .replace(/\{close}/g, close)
                .replace(/\{caption}/g, caption)
                .replace(/\{upload}/g, self._renderButton('upload'))
                .replace(/\{remove}/g, self._renderButton('remove'))
                .replace(/\{cancel}/g, self._renderButton('cancel'))
                .replace(/\{browse}/g, self._renderButton('browse'));
        },
        _renderButton: function (type) {
            var self = this, tmplt = self._getLayoutTemplate('btnDefault'), css = self[type + 'Class'],
                title = self[type + 'Title'], icon = self[type + 'Icon'], label = self[type + 'Label'],
                status = self.isDisabled ? ' disabled' : '', btnType = 'button';
            switch (type) {
                case 'remove':
                    if (!self.showRemove) {
                        return '';
                    }
                    break;
                case 'cancel':
                    if (!self.showCancel) {
                        return '';
                    }
                    css += ' hide';
                    break;
                case 'upload':
                    if (!self.showUpload) {
                        return '';
                    }
                    if (self.isUploadable && !self.isDisabled) {
                        tmplt = self._getLayoutTemplate('btnLink').replace('{href}', self.uploadUrl);
                    } else {
                        btnType = 'submit';
                    }
                    break;
                case 'browse':
                    if (!self.showBrowse) {
                        return '';
                    }
                    tmplt = self._getLayoutTemplate('btnBrowse');
                    break;
                default:
                    return '';
            }

            css += type === 'browse' ? ' btn-file' : ' fileinput-' + type + ' fileinput-' + type + '-button';
            if (!$h.isEmpty(label)) {
                label = ' <span class="' + self.buttonLabelClass + '">' + label + '</span>';
            }
            return tmplt.replace('{type}', btnType).replace('{css}', css).replace('{title}', title)
                .replace('{status}', status).replace('{icon}', icon).replace('{label}', label);
        },
        _renderThumbProgress: function () {
            var self = this;
            return '<div class="file-thumb-progress hide">' + self.progressTemplate.replace(/\{percent}/g, '0')
                    .replace(/\{status}/g, self.msgUploadBegin) + '</div>';
        },
        _renderFileFooter: function (caption, size, width, isError) {
            var self = this, config = self.fileActionSettings, rem = config.showRemove, drg = config.showDrag,
                upl = config.showUpload, zoom = config.showZoom, out, template = self._getLayoutTemplate('footer'),
                indicator = isError ? config.indicatorError : config.indicatorNew,
                title = isError ? config.indicatorErrorTitle : config.indicatorNewTitle;
            size = self._getSize(size);
            if (self.isUploadable) {
                out = template.replace(/\{actions}/g, self._renderFileActions(upl, rem, zoom, drg, false, false, false))
                    .replace(/\{caption}/g, caption).replace(/\{size}/g, size).replace(/\{width}/g, width)
                    .replace(/\{progress}/g, self._renderThumbProgress()).replace(/\{indicator}/g, indicator)
                    .replace(/\{indicatorTitle}/g, title);
            } else {
                out = template.replace(/\{actions}/g,
                    self._renderFileActions(false, false, zoom, drg, false, false, false))
                    .replace(/\{caption}/g, caption).replace(/\{size}/g, size).replace(/\{width}/g, width)
                    .replace(/\{progress}/g, '').replace(/\{indicator}/g, indicator)
                    .replace(/\{indicatorTitle}/g, title);
            }
            out = $h.replaceTags(out, self.previewThumbTags);
            return out;
        },
        _renderFileActions: function (showUpload, showDelete, showZoom, showDrag, disabled, url, key, isInit) {
            if (!showUpload && !showDelete && !showZoom && !showDrag) {
                return '';
            }
            var self = this,
                vUrl = url === false ? '' : ' data-url="' + url + '"',
                vKey = key === false ? '' : ' data-key="' + key + '"',
                btnDelete = '', btnUpload = '', btnZoom = '', btnDrag = '', css,
                template = self._getLayoutTemplate('actions'), config = self.fileActionSettings,
                otherButtons = self.otherActionButtons.replace(/\{dataKey}/g, vKey),
                removeClass = disabled ? config.removeClass + ' disabled' : config.removeClass;
            if (showDelete) {
                btnDelete = self._getLayoutTemplate('actionDelete')
                    .replace(/\{removeClass}/g, removeClass)
                    .replace(/\{removeIcon}/g, config.removeIcon)
                    .replace(/\{removeTitle}/g, config.removeTitle)
                    .replace(/\{dataUrl}/g, vUrl)
                    .replace(/\{dataKey}/g, vKey);
            }
            if (showUpload) {
                btnUpload = self._getLayoutTemplate('actionUpload')
                    .replace(/\{uploadClass}/g, config.uploadClass)
                    .replace(/\{uploadIcon}/g, config.uploadIcon)
                    .replace(/\{uploadTitle}/g, config.uploadTitle);
            }
            if (showZoom) {
                btnZoom = self._getLayoutTemplate('actionZoom')
                    .replace(/\{zoomClass}/g, config.zoomClass)
                    .replace(/\{zoomIcon}/g, config.zoomIcon)
                    .replace(/\{zoomTitle}/g, config.zoomTitle);
            }
            if (showDrag && isInit) {
                css = 'drag-handle-init ' + config.dragClass;
                btnDrag = self._getLayoutTemplate('actionDrag').replace(/\{dragClass}/g, css)
                    .replace(/\{dragTitle}/g, config.dragTitle)
                    .replace(/\{dragIcon}/g, config.dragIcon);
            }
            return template.replace(/\{delete}/g, btnDelete)
                .replace(/\{upload}/g, btnUpload)
                .replace(/\{zoom}/g, btnZoom)
                .replace(/\{drag}/g, btnDrag)
                .replace(/\{other}/g, otherButtons);
        },
        _browse: function (e) {
            var self = this;
            self._raise('filebrowse');
            if (e && e.isDefaultPrevented()) {
                return;
            }
            if (self.isError && !self.isUploadable) {
                self.clear();
            }
            self.$captionContainer.focus();
        },
        _filterDuplicate: function (file, files, fileIds) {
            var self = this, fileId = self._getFileId(file);
            if (fileId && fileIds && fileIds.indexOf(fileId) > -1) {
                return;
            }
            if (!fileIds) {
                fileIds = [];
            }
            files.push(file);
            fileIds.push(fileId);
        },
        _change: function (e) {
            var self = this, $el = self.$element;
            if (!self.isUploadable && $h.isEmpty($el.val()) && self.fileInputCleared) { // IE 11 fix
                self.fileInputCleared = false;
                return;
            }
            self.fileInputCleared = false;
            var tfiles = [], msg, total, isDragDrop = arguments.length > 1, isAjaxUpload = self.isUploadable, n, len,
                files = isDragDrop ? e.originalEvent.dataTransfer.files : $el.get(0).files, ctr = self.filestack.length,
                isSingleUpload = $h.isEmpty($el.attr('multiple')), flagSingle = (isSingleUpload && ctr > 0),
                folders = 0, fileIds = self._getFileIds(), throwError = function (mesg, file, previewId, index) {
                    var p1 = $.extend(true, {}, self._getOutData({}, {}, files), {id: previewId, index: index}),
                        p2 = {id: previewId, index: index, file: file, files: files};
                    return self.isUploadable ? self._showUploadError(mesg, p1) : self._showError(mesg, p2);
                };
            self.reader = null;
            self._resetUpload();
            self._hideFileIcon();
            if (self.isUploadable) {
                self.$container.find('.file-drop-zone .' + self.dropZoneTitleClass).remove();
            }
            if (isDragDrop) {
                $.each(files, function (i, f) {
                    if (f && !f.type && f.size !== undefined && f.size % 4096 === 0) {
                        folders++;
                    } else {
                        self._filterDuplicate(f, tfiles, fileIds);
                    }
                });
            } else {
                if (e.target && e.target.files === undefined) {
                    files = e.target.value ? [{name: e.target.value.replace(/^.+\\/, '')}] : [];
                } else {
                    files = e.target.files || {};
                }
                $.each(files, function (i, f) {
                    self._filterDuplicate(f, tfiles, fileIds);
                });
            }
            if ($h.isEmpty(tfiles) || tfiles.length === 0) {
                if (!isAjaxUpload) {
                    self.clear();
                }
                self._showFolderError(folders);
                self._raise('fileselectnone');
                return;
            }
            self._resetErrors();
            len = tfiles.length;
            total = self._getFileCount(self.isUploadable ? (self.getFileStack().length + len) : len);
            if (self.maxFileCount > 0 && total > self.maxFileCount) {
                if (!self.autoReplace || len > self.maxFileCount) {
                    n = (self.autoReplace && len > self.maxFileCount) ? len : total;
                    msg = self.msgFilesTooMany.replace('{m}', self.maxFileCount).replace('{n}', n);
                    self.isError = throwError(msg, null, null, null);
                    self.$captionContainer.find('.kv-caption-icon').hide();
                    self._setCaption('', true);
                    self.$container.removeClass('file-input-new file-input-ajax-new');
                    return;
                }
                if (total > self.maxFileCount) {
                    self._resetPreviewThumbs(isAjaxUpload);
                }
            } else {
                if (!isAjaxUpload || flagSingle) {
                    self._resetPreviewThumbs(false);
                    if (flagSingle) {
                        self.clearStack();
                    }
                } else {
                    if (isAjaxUpload && ctr === 0 && (!self.previewCache.count() || self.overwriteInitial)) {
                        self._resetPreviewThumbs(true);
                    }
                }
            }
            if (self.isPreviewable) {
                self._readFiles(tfiles);
            } else {
                self._updateFileDetails(1);
            }
            self._showFolderError(folders);
        },
        _abort: function (params) {
            var self = this, data;
            if (self.ajaxAborted && typeof self.ajaxAborted === "object" && self.ajaxAborted.message !== undefined) {
                data = $.extend(true, {}, self._getOutData(), params);
                data.abortData = self.ajaxAborted.data || {};
                data.abortMessage = self.ajaxAborted.message;
                self.cancel();
                self._setProgress(101, self.$progress, self.msgCancelled);
                self._showUploadError(self.ajaxAborted.message, data, 'filecustomerror');
                return true;
            }
            return false;
        },
        _resetFileStack: function () {
            var self = this, i = 0, newstack = [], newnames = [], newids = [];
            self._getThumbs().each(function () {
                var $thumb = $(this), ind = $thumb.attr('data-fileindex'), file = self.filestack[ind],
                    pid = $thumb.attr('id'), newId;
                if (ind === '-1' || ind === -1) {
                    return;
                }
                if (file !== undefined) {
                    newstack[i] = file;
                    newnames[i] = self._getFileName(file);
                    newids[i] = self._getFileId(file);
                    $thumb.attr({'id': self.previewInitId + '-' + i, 'data-fileindex': i});
                    i++;
                } else {
                    newId = 'uploaded-' + $h.uniqId();
                    $thumb.attr({'id': newId, 'data-fileindex': '-1'});
                    self.$preview.find('#zoom-' + pid).attr('id', 'zoom-' + newId);
                }
            });
            self.filestack = newstack;
            self.filenames = newnames;
            self.fileids = newids;
        },
        clearStack: function () {
            var self = this;
            self.filestack = [];
            self.filenames = [];
            self.fileids = [];
            return self.$element;
        },
        updateStack: function (i, file) {
            var self = this;
            self.filestack[i] = file;
            self.filenames[i] = self._getFileName(file);
            self.fileids[i] = file && self._getFileId(file) || null;
            return self.$element;
        },
        addToStack: function (file) {
            var self = this;
            self.filestack.push(file);
            self.filenames.push(self._getFileName(file));
            self.fileids.push(self._getFileId(file));
            return self.$element;
        },
        getFileStack: function (skipNull) {
            var self = this;
            return self.filestack.filter(function (n) {
                return (skipNull ? n !== undefined : n !== undefined && n !== null);
            });
        },
        getFilesCount: function () {
            var self = this, len = self.isUploadable ? self.getFileStack().length : self.$element.get(0).files.length;
            return self._getFileCount(len);
        },
        lock: function () {
            var self = this;
            self._resetErrors();
            self.disable();
            if (self.showRemove) {
                $h.addCss(self.$container.find('.fileinput-remove'), 'hide');
            }
            if (self.showCancel) {
                self.$container.find('.fileinput-cancel').removeClass('hide');
            }
            self._raise('filelock', [self.filestack, self._getExtraData()]);
            return self.$element;
        },
        unlock: function (reset) {
            var self = this;
            if (reset === undefined) {
                reset = true;
            }
            self.enable();
            if (self.showCancel) {
                $h.addCss(self.$container.find('.fileinput-cancel'), 'hide');
            }
            if (self.showRemove) {
                self.$container.find('.fileinput-remove').removeClass('hide');
            }
            if (reset) {
                self._resetFileStack();
            }
            self._raise('fileunlock', [self.filestack, self._getExtraData()]);
            return self.$element;
        },
        cancel: function () {
            var self = this, xhr = self.ajaxRequests, len = xhr.length, i;
            if (len > 0) {
                for (i = 0; i < len; i += 1) {
                    self.cancelling = true;
                    xhr[i].abort();
                }
            }
            self._setProgressCancelled();
            self._getThumbs().each(function () {
                var $thumb = $(this), ind = $thumb.attr('data-fileindex');
                $thumb.removeClass('file-uploading');
                if (self.filestack[ind] !== undefined) {
                    $thumb.find('.kv-file-upload').removeClass('disabled').removeAttr('disabled');
                    $thumb.find('.kv-file-remove').removeClass('disabled').removeAttr('disabled');
                }
                self.unlock();
            });
            return self.$element;
        },
        clear: function () {
            var self = this, cap;
            if (!self._raise('fileclear')) {
                return;
            }
            self.$btnUpload.removeAttr('disabled');
            self._getThumbs().find('video,audio,img').each(function () {
                $h.cleanMemory($(this));
            });
            self._resetUpload();
            self.clearStack();
            self._clearFileInput();
            self._resetErrors(true);
            if (self._hasInitialPreview()) {
                self._showFileIcon();
                self._resetPreview();
                self._initPreviewActions();
                self.$container.removeClass('file-input-new');
            } else {
                self._getThumbs().each(function () {
                    self._clearObjects($(this));
                });
                if (self.isUploadable) {
                    self.previewCache.data = {};
                }
                self.$preview.html('');
                cap = (!self.overwriteInitial && self.initialCaption.length > 0) ? self.initialCaption : '';
                self.$caption.html(cap);
                self.$caption.attr('title', '');
                $h.addCss(self.$container, 'file-input-new');
                self._validateDefaultPreview();
            }
            if (self.$container.find($h.FRAMES).length === 0) {
                if (!self._initCaption()) {
                    self.$captionContainer.find('.kv-caption-icon').hide();
                }
            }
            self._hideFileIcon();
            self._raise('filecleared');
            self.$captionContainer.focus();
            self._setFileDropZoneTitle();
            return self.$element;
        },
        reset: function () {
            var self = this;
            if (!self._raise('filereset')) {
                return;
            }
            self._resetPreview();
            self.$container.find('.fileinput-filename').text('');
            $h.addCss(self.$container, 'file-input-new');
            if (self.$preview.find($h.FRAMES).length || self.isUploadable && self.dropZoneEnabled) {
                self.$container.removeClass('file-input-new');
            }
            self._setFileDropZoneTitle();
            self.clearStack();
            self.formdata = {};
            return self.$element;
        },
        disable: function () {
            var self = this;
            self.isDisabled = true;
            self._raise('filedisabled');
            self.$element.attr('disabled', 'disabled');
            self.$container.find(".kv-fileinput-caption").addClass("file-caption-disabled");
            self.$container.find(".btn-file, .fileinput-remove, .fileinput-upload, .file-preview-frame button").attr(
                "disabled",
                true);
            self._initDragDrop();
            return self.$element;
        },
        enable: function () {
            var self = this;
            self.isDisabled = false;
            self._raise('fileenabled');
            self.$element.removeAttr('disabled');
            self.$container.find(".kv-fileinput-caption").removeClass("file-caption-disabled");
            self.$container.find(
                ".btn-file, .fileinput-remove, .fileinput-upload, .file-preview-frame button").removeAttr("disabled");
            self._initDragDrop();
            return self.$element;
        },
        upload: function () {
            var self = this, totLen = self.getFileStack().length, params = {}, i, outData, len,
                hasExtraData = !$.isEmptyObject(self._getExtraData());
            if (!self.isUploadable || self.isDisabled) {
                return;
            }
            if (self.minFileCount > 0 && self._getFileCount(totLen) < self.minFileCount) {
                self._noFilesError(params);
                return;
            }
            self._resetUpload();
            if (totLen === 0 && !hasExtraData) {
                self._showUploadError(self.msgUploadEmpty);
                return;
            }
            self.$progress.removeClass('hide');
            self.uploadCount = 0;
            self.uploadStatus = {};
            self.uploadLog = [];
            self.lock();
            self._setProgress(2);
            if (totLen === 0 && hasExtraData) {
                self._uploadExtraOnly();
                return;
            }
            len = self.filestack.length;
            self.hasInitData = false;
            if (self.uploadAsync) {
                outData = self._getOutData();
                self._raise('filebatchpreupload', [outData]);
                self.fileBatchCompleted = false;
                self.uploadCache = {content: [], config: [], tags: [], append: true};
                self.uploadAsyncCount = self.getFileStack().length;
                for (i = 0; i < len; i++) {
                    self.uploadCache.content[i] = null;
                    self.uploadCache.config[i] = null;
                    self.uploadCache.tags[i] = null;
                }
                self.$preview.find('.file-preview-initial').removeClass($h.SORT_CSS);
                self._initSortable();
                self.cacheInitialPreview = self.getPreview();

                for (i = 0; i < len; i++) {
                    if (self.filestack[i] !== undefined) {
                        self._uploadSingle(i, self.filestack, true);
                    }
                }
                return;
            }
            self._uploadBatch();
            return self.$element;
        },
        destroy: function () {
            var self = this, $form = self.$form, $cont = self.$container, $el = self.$element, ns = self.namespace;
            $(document).off(ns);
            $(window).off(ns);
            if ($form && $form.length) {
                $form.off(ns);
            }
            $el.insertBefore($cont).off(ns).removeData();
            $cont.off().remove();
            return $el;
        },
        refresh: function (options) {
            var self = this, $el = self.$element;
            options = options ? $.extend(true, {}, self.options, options) : self.options;
            self.destroy();
            $el.fileinput(options);
            if ($el.val()) {
                $el.trigger('change.fileinput');
            }
            return $el;
        },
        zoom: function (frameId) {
            var self = this, $frame = $('#' + frameId), $modal = self.$modal;
            if (!$frame.length) {
                self._log('Cannot zoom to detailed preview! Invalid frame with id: "' + frameId + '".');
                return;
            }
            $h.initModal($modal);
            $modal.html(self._getModalContent());
            self._setZoomContent($frame);
            $modal.modal('show');
            self._initZoomButtons();
        },
        getPreview: function () {
            var self = this;
            return {
                content: self.initialPreview,
                config: self.initialPreviewConfig,
                tags: self.initialPreviewThumbTags
            };
        }
    };

    $.fn.fileinput = function (option) {
        if (!$h.hasFileAPISupport() && !$h.isIE(9)) {
            return;
        }
        var args = Array.apply(null, arguments), retvals = [];
        args.shift();
        this.each(function () {
            var self = $(this), data = self.data('fileinput'), options = typeof option === 'object' && option,
                theme = options.theme || self.data('theme'), l = {}, t = {},
                lang = options.language || self.data('language') || $.fn.fileinput.defaults.language || 'en', opt;
            if (!data) {
                if (theme) {
                    t = $.fn.fileinputThemes[theme] || {};
                }
                if (lang !== 'en' && !$h.isEmpty($.fn.fileinputLocales[lang])) {
                    l = $.fn.fileinputLocales[lang] || {};
                }
                opt = $.extend(true, {}, $.fn.fileinput.defaults, t, $.fn.fileinputLocales.en, l, options, self.data());
                data = new FileInput(this, opt);
                self.data('fileinput', data);
            }

            if (typeof option === 'string') {
                retvals.push(data[option].apply(data, args));
            }
        });
        switch (retvals.length) {
            case 0:
                return this;
            case 1:
                return retvals[0];
            default:
                return retvals;
        }
    };

    $.fn.fileinput.defaults = {
        language: 'en',
        showCaption: true,
        showBrowse: true,
        showPreview: true,
        showRemove: true,
        showUpload: true,
        showCancel: true,
        showClose: true,
        showUploadedThumbs: true,
        browseOnZoneClick: false,
        autoReplace: false,
        generateFileId: null,
        previewClass: '',
        captionClass: '',
        frameClass: 'krajee-default',
        mainClass: 'file-caption-main',
        mainTemplate: null,
        purifyHtml: true,
        fileSizeGetter: null,
        initialCaption: '',
        initialPreview: [],
        initialPreviewDelimiter: '*$$*',
        initialPreviewAsData: false,
        initialPreviewFileType: 'image',
        initialPreviewConfig: [],
        initialPreviewThumbTags: [],
        previewThumbTags: {},
        initialPreviewShowDelete: true,
        removeFromPreviewOnError: false,
        deleteUrl: '',
        deleteExtraData: {},
        overwriteInitial: true,
        previewZoomButtonIcons: {
            prev: '<i class="glyphicon glyphicon-triangle-left"></i>',
            next: '<i class="glyphicon glyphicon-triangle-right"></i>',
            toggleheader: '<i class="glyphicon glyphicon-resize-vertical"></i>',
            fullscreen: '<i class="glyphicon glyphicon-fullscreen"></i>',
            borderless: '<i class="glyphicon glyphicon-resize-full"></i>',
            close: '<i class="glyphicon glyphicon-remove"></i>'
        },
        previewZoomButtonClasses: {
            prev: 'btn btn-navigate',
            next: 'btn btn-navigate',
            toggleheader: 'btn btn-default btn-header-toggle',
            fullscreen: 'btn btn-default',
            borderless: 'btn btn-default',
            close: 'btn btn-default'
        },
        preferIconicPreview: false,
        preferIconicZoomPreview: false,
        allowedPreviewTypes: undefined,
        allowedPreviewMimeTypes: null,
        allowedFileTypes: null,
        allowedFileExtensions: null,
        defaultPreviewContent: null,
        customLayoutTags: {},
        customPreviewTags: {},
        previewFileIcon: '<i class="glyphicon glyphicon-file"></i>',
        previewFileIconClass: 'file-other-icon',
        previewFileIconSettings: {},
        previewFileExtSettings: {},
        buttonLabelClass: 'hidden-xs',
        browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>&nbsp;',
        browseClass: 'btn btn-primary',
        removeIcon: '<i class="glyphicon glyphicon-trash"></i>',
        removeClass: 'btn btn-default',
        cancelIcon: '<i class="glyphicon glyphicon-ban-circle"></i>',
        cancelClass: 'btn btn-default',
        uploadIcon: '<i class="glyphicon glyphicon-upload"></i>',
        uploadClass: 'btn btn-default',
        uploadUrl: null,
        uploadAsync: true,
        uploadExtraData: {},
        zoomModalHeight: 480,
        minImageWidth: null,
        minImageHeight: null,
        maxImageWidth: null,
        maxImageHeight: null,
        resizeImage: false,
        resizePreference: 'width',
        resizeQuality: 0.92,
        resizeDefaultImageType: 'image/jpeg',
        minFileSize: 0,
        maxFileSize: 0,
        maxFilePreviewSize: 25600, // 25 MB
        minFileCount: 0,
        maxFileCount: 0,
        validateInitialCount: false,
        msgValidationErrorClass: 'text-danger',
        msgValidationErrorIcon: '<i class="glyphicon glyphicon-exclamation-sign"></i> ',
        msgErrorClass: 'file-error-message',
        progressThumbClass: "progress-bar progress-bar-success progress-bar-striped active",
        progressClass: "progress-bar progress-bar-success progress-bar-striped active",
        progressCompleteClass: "progress-bar progress-bar-success",
        progressErrorClass: "progress-bar progress-bar-danger",
        progressUploadThreshold: 99,
        previewFileType: 'image',
        elCaptionContainer: null,
        elCaptionText: null,
        elPreviewContainer: null,
        elPreviewImage: null,
        elPreviewStatus: null,
        elErrorContainer: null,
        errorCloseButton: '<span class="close kv-error-close">&times;</span>',
        slugCallback: null,
        dropZoneEnabled: true,
        dropZoneTitleClass: 'file-drop-zone-title',
        fileActionSettings: {},
        otherActionButtons: '',
        textEncoding: 'UTF-8',
        ajaxSettings: {},
        ajaxDeleteSettings: {},
        showAjaxErrorDetails: true
    };

    $.fn.fileinputLocales.en = {
        fileSingle: 'file',
        filePlural: 'files',
        browseLabel: 'Browse &hellip;',
        removeLabel: 'Remove',
        removeTitle: 'Clear selected files',
        cancelLabel: 'Cancel',
        cancelTitle: 'Abort ongoing upload',
        uploadLabel: 'Upload',
        uploadTitle: 'Upload selected files',
        msgNo: 'No',
        msgNoFilesSelected: 'No files selected',
        msgCancelled: 'Cancelled',
        msgZoomModalHeading: 'Detailed Preview',
        msgSizeTooSmall: 'File "{name}" (<b>{size} KB</b>) is too small and must be larger than <b>{minSize} KB</b>.',
        msgSizeTooLarge: 'File "{name}" (<b>{size} KB</b>) exceeds maximum allowed upload size of <b>{maxSize} KB</b>.',
        msgFilesTooLess: 'You must select at least <b>{n}</b> {files} to upload.',
        msgFilesTooMany: 'Number of files selected for upload <b>({n})</b> exceeds maximum allowed limit of <b>{m}</b>.',
        msgFileNotFound: 'File "{name}" not found!',
        msgFileSecured: 'Security restrictions prevent reading the file "{name}".',
        msgFileNotReadable: 'File "{name}" is not readable.',
        msgFilePreviewAborted: 'File preview aborted for "{name}".',
        msgFilePreviewError: 'An error occurred while reading the file "{name}".',
        msgInvalidFileName: 'Invalid or unsupported characters in file name "{name}".',
        msgInvalidFileType: 'Invalid type for file "{name}". Only "{types}" files are supported.',
        msgInvalidFileExtension: 'Invalid extension for file "{name}". Only "{extensions}" files are supported.',
        msgFileTypes: {
            'image': 'image',
            'html': 'HTML',
            'text': 'text',
            'video': 'video',
            'audio': 'audio',
            'flash': 'flash',
            'pdf': 'PDF',
            'object': 'object'
        },
        msgUploadAborted: 'The file upload was aborted',
        msgUploadThreshold: 'Processing...',
        msgUploadBegin: 'Initializing...',
        msgUploadEnd: 'Done',
        msgUploadEmpty: 'No valid data available for upload.',
        msgValidationError: 'Validation Error',
        msgLoading: 'Loading file {index} of {files} &hellip;',
        msgProgress: 'Loading file {index} of {files} - {name} - {percent}% completed.',
        msgSelected: '{n} {files} selected',
        msgFoldersNotAllowed: 'Drag & drop files only! {n} folder(s) dropped were skipped.',
        msgImageWidthSmall: 'Width of image file "{name}" must be at least {size} px.',
        msgImageHeightSmall: 'Height of image file "{name}" must be at least {size} px.',
        msgImageWidthLarge: 'Width of image file "{name}" cannot exceed {size} px.',
        msgImageHeightLarge: 'Height of image file "{name}" cannot exceed {size} px.',
        msgImageResizeError: 'Could not get the image dimensions to resize.',
        msgImageResizeException: 'Error while resizing the image.<pre>{errors}</pre>',
        msgAjaxError: 'Something went wrong with the {operation} operation. Please try again later!',
        msgAjaxProgressError: '{operation} failed',
        ajaxOperations: {
            deleteThumb: 'file delete',
            uploadThumb: 'file upload',
            uploadBatch: 'batch file upload',
            uploadExtra: 'form data upload'
        },
        dropZoneTitle: 'Drag & drop files here &hellip;',
        dropZoneClickTitle: '<br>(or click to select {files})',
        previewZoomButtonTitles: {
            prev: 'View previous file',
            next: 'View next file',
            toggleheader: 'Toggle header',
            fullscreen: 'Toggle full screen',
            borderless: 'Toggle borderless mode',
            close: 'Close detailed preview'
        }
    };

    $.fn.fileinput.Constructor = FileInput;

    /**
     * Convert automatically file inputs with class 'file' into a bootstrap fileinput control.
     */
    $(document).ready(function () {
        var $input = $('input.file[type=file]');
        if ($input.length) {
            $input.fileinput();
        }
    });
}));
/*!
 * datepair.js v0.4.15 - A javascript plugin for intelligently selecting date and time ranges inspired by Google Calendar.
 * Copyright (c) 2016 Jon Thornton - http://jonthornton.github.com/Datepair.js
 * License: MIT
 */

(function(window, document) {

	'use strict';

	var _ONE_DAY = 86400000;
	var jq = window.Zepto || window.jQuery;
	
	function simpleExtend(obj1, obj2) {
		var out = obj2 || {};
	
		for (var i in obj1) {
			if (!(i in out)) {
				out[i] = obj1[i];
			}
		}
	
		return out;
	}
	
	// IE's custom event support is totally borked.
	// Use jQuery if possible
	function triggerSimpleCustomEvent(el, eventName) {
		if (jq) {
			jq(el).trigger(eventName);
		} else {
			var event = document.createEvent('CustomEvent');
			event.initCustomEvent(eventName, true, true, {});
			el.dispatchEvent(event);
		}
	}
	
	// el.classList not supported by < IE10
	// use jQuery if available
	function hasClass(el, className) {
		if (jq) {
			return jq(el).hasClass(className);
		} else {
			return el.classList.contains(className);
		}
	}
	
	function Datepair(container, options) {
		this.dateDelta = null;
		this.timeDelta = null;
		this._defaults =	{
			startClass: 'start',
			endClass: 'end',
			timeClass: 'time',
			dateClass: 'date',
			defaultDateDelta: 0,
			defaultTimeDelta: 3600000,
			anchor: 'start',
	
			// defaults for jquery-timepicker; override when using other input widgets
			parseTime: function(input){
				return jq(input).timepicker('getTime');
			},
			updateTime: function(input, dateObj){
				jq(input).timepicker('setTime', dateObj);
			},
			setMinTime: function(input, dateObj){
				jq(input).timepicker('option', 'minTime', dateObj);
			},
	
			// defaults for bootstrap datepicker; override when using other input widgets
			parseDate: function(input){
				return input.value && jq(input).datepicker('getDate');
			},
			updateDate: function(input, dateObj){
				jq(input).datepicker('update', dateObj);
			}
		};
	
		this.container = container;
		this.settings = simpleExtend(this._defaults, options);
	
		this.startDateInput = this.container.querySelector('.'+this.settings.startClass+'.'+this.settings.dateClass);
		this.endDateInput = this.container.querySelector('.'+this.settings.endClass+'.'+this.settings.dateClass);
		this.startTimeInput = this.container.querySelector('.'+this.settings.startClass+'.'+this.settings.timeClass);
		this.endTimeInput = this.container.querySelector('.'+this.settings.endClass+'.'+this.settings.timeClass);
	
		// initialize date and time deltas
		this.refresh();
	
		// init starts here
		this._bindChangeHandler();
	}
	
	Datepair.prototype = {
		constructor: Datepair,
	
		option: function(key, value)
		{
			if (typeof key == 'object') {
				this.settings = simpleExtend(this.settings, key);
	
			} else if (typeof key == 'string' && typeof value != 'undefined') {
				this.settings[key] = value;
	
			} else if (typeof key == 'string') {
				return this.settings[key];
			}
	
			this._updateEndMintime();
		},
	
		getTimeDiff: function()
		{
			// due to the fact that times can wrap around, timeDiff for any
			// time-only pair will always be >= 0
			var delta = this.dateDelta + this.timeDelta;
			if (delta < 0 && (!this.startDateInput || !this.endDateInput) ) {
				delta += _ONE_DAY;
			}
	
			return delta;
		},
	
		refresh: function()
		{
			if (this.startDateInput && this.startDateInput.value && this.endDateInput && this.endDateInput.value) {
				var startDate = this.settings.parseDate(this.startDateInput);
				var endDate = this.settings.parseDate(this.endDateInput);
				if (startDate && endDate) {
					this.dateDelta = endDate.getTime() - startDate.getTime();
				}
			}
			if (this.startTimeInput && this.startTimeInput.value && this.endTimeInput && this.endTimeInput.value) {
				var startTime = this.settings.parseTime(this.startTimeInput);
				var endTime = this.settings.parseTime(this.endTimeInput);
				if (startTime && endTime) {
					this.timeDelta = endTime.getTime() - startTime.getTime();
					this._updateEndMintime();
				}
			}
		},
	
		remove: function()
		{
			this._unbindChangeHandler();
		},
	
		_bindChangeHandler: function(){
			// addEventListener doesn't work with synthetic "change" events
			// fired by jQuery's trigger() functioin. If jQuery is present,
			// use that for event binding
			if (jq) {
				jq(this.container).on('change.datepair', jq.proxy(this.handleEvent, this));
			} else {
				this.container.addEventListener('change', this, false);
			}
		},
	
		_unbindChangeHandler: function(){
			if (jq) {
				jq(this.container).off('change.datepair');
			} else {
				this.container.removeEventListener('change', this, false);
			}
		},
	
		// This function will be called when passing 'this' to addEventListener
		handleEvent: function(e){
			// temporarily unbind the change handler to prevent triggering this
			// if we update other inputs
			this._unbindChangeHandler();
	
			if (hasClass(e.target, this.settings.dateClass)) {
				if (e.target.value != '') {
					this._dateChanged(e.target);
					this._timeChanged(e.target);
				} else {
					this.dateDelta = null;
				}
	
			} else if (hasClass(e.target, this.settings.timeClass)) {
				if (e.target.value != '') {
					this._timeChanged(e.target);
				} else {
					this.timeDelta = null;
				}
			}
	
			this._validateRanges();
			this._updateEndMintime();
			this._bindChangeHandler();
		},
	
		_dateChanged: function(target){
			if (!this.startDateInput || !this.endDateInput) {
				return;
			}
	
			var startDate = this.settings.parseDate(this.startDateInput);
			var endDate = this.settings.parseDate(this.endDateInput);
	
			if (!startDate || !endDate) {
				if (this.settings.defaultDateDelta !== null) {
					if (startDate) {
						var newEnd = new Date(startDate.getTime() + this.settings.defaultDateDelta * _ONE_DAY);
						this.settings.updateDate(this.endDateInput, newEnd);
	
					} else if (endDate) {
						var newStart = new Date(endDate.getTime() - this.settings.defaultDateDelta * _ONE_DAY);
						this.settings.updateDate(this.startDateInput, newStart);
					}
	
					this.dateDelta = this.settings.defaultDateDelta * _ONE_DAY;
				} else {
					this.dateDelta = null;
				}
	
				return;
			}
	
			if (this.settings.anchor == 'start' && hasClass(target, this.settings.startClass)) {
				var newDate = new Date(startDate.getTime() + this.dateDelta);
				this.settings.updateDate(this.endDateInput, newDate);
			} else if (this.settings.anchor == 'end' && hasClass(target, this.settings.endClass)) {
				var newDate = new Date(endDate.getTime() - this.dateDelta);
				this.settings.updateDate(this.startDateInput, newDate);
			} else {
				if (endDate < startDate) {
					var otherInput = hasClass(target, this.settings.startClass) ? this.endDateInput : this.startDateInput;
					var selectedDate = this.settings.parseDate(target);
					this.dateDelta = 0;
					this.settings.updateDate(otherInput, selectedDate);
				} else {
					this.dateDelta = endDate.getTime() - startDate.getTime();
				}
			}
		},
	
		_timeChanged: function(target){
			if (!this.startTimeInput || !this.endTimeInput) {
				return;
			}
	
			var startTime = this.settings.parseTime(this.startTimeInput);
			var endTime = this.settings.parseTime(this.endTimeInput);
	
			if (!startTime || !endTime) {
				if (this.settings.defaultTimeDelta !== null) {
					if (startTime) {
						var newEnd = new Date(startTime.getTime() + this.settings.defaultTimeDelta);
						this.settings.updateTime(this.endTimeInput, newEnd);
					} else if (endTime) {
						var newStart = new Date(endTime.getTime() - this.settings.defaultTimeDelta);
						this.settings.updateTime(this.startTimeInput, newStart);
					}
	
					this.timeDelta = this.settings.defaultTimeDelta;
				} else {
					this.timeDelta = null;
				}
	
				return;
			}
	
			if (this.settings.anchor == 'start' && hasClass(target, this.settings.startClass)) {
				var newTime = new Date(startTime.getTime() + this.timeDelta);
				this.settings.updateTime(this.endTimeInput, newTime);
				endTime = this.settings.parseTime(this.endTimeInput);
	
				this._doMidnightRollover(startTime, endTime);
			} else if (this.settings.anchor == 'end' && hasClass(target, this.settings.endClass)) {
				var newTime = new Date(endTime.getTime() - this.timeDelta);
				this.settings.updateTime(this.startTimeInput, newTime);
				startTime = this.settings.parseTime(this.startTimeInput);
	
				this._doMidnightRollover(startTime, endTime);
			} else {
				this._doMidnightRollover(startTime, endTime);
	
				var startDate, endDate;
				if (this.startDateInput && this.endDateInput) {
					startDate = this.settings.parseDate(this.startDateInput);
					endDate = this.settings.parseDate(this.endDateInput);
				}
	
				if ((+startDate == +endDate) && (endTime < startTime)) {
					var thisInput  = hasClass(target, this.settings.endClass) ? this.endTimeInput : this.startTimeInput;
					var otherInput = hasClass(target, this.settings.startClass) ? this.endTimeInput : this.startTimeInput;
					var selectedTime = this.settings.parseTime(thisInput);
					this.timeDelta = 0;
					this.settings.updateTime(otherInput, selectedTime);
				} else {
					this.timeDelta = endTime.getTime() - startTime.getTime();
				}
			}
	
	
		},
	
		_doMidnightRollover: function(startTime, endTime) {
			if (!this.startDateInput || !this.endDateInput) {
				return;
			}
	
			var endDate = this.settings.parseDate(this.endDateInput);
			var startDate = this.settings.parseDate(this.startDateInput);
			var newDelta = endTime.getTime() - startTime.getTime();
			var offset = (endTime < startTime) ? _ONE_DAY : -1 * _ONE_DAY;
	
			if (this.dateDelta !== null
					&& this.dateDelta + this.timeDelta <= _ONE_DAY
					&& this.dateDelta + newDelta != 0
					&& (offset > 0 || this.dateDelta != 0)
					&& ((newDelta >= 0 && this.timeDelta < 0) || (newDelta < 0 && this.timeDelta >= 0))) {
	
				if (this.settings.anchor == 'start') {
					this.settings.updateDate(this.endDateInput, new Date(endDate.getTime() + offset));
					this._dateChanged(this.endDateInput);
				} else if (this.settings.anchor == 'end') {
					this.settings.updateDate(this.startDateInput, new Date(startDate.getTime() - offset));
					this._dateChanged(this.startDateInput);
				}
			}
			this.timeDelta = newDelta;
		},
	
		_updateEndMintime: function(){
			if (typeof this.settings.setMinTime != 'function') return;
	
			var baseTime = null;
			if (this.settings.anchor == 'start' && (!this.dateDelta || this.dateDelta < _ONE_DAY || (this.timeDelta && this.dateDelta + this.timeDelta < _ONE_DAY))) {
				baseTime = this.settings.parseTime(this.startTimeInput);
			}
	
			this.settings.setMinTime(this.endTimeInput, baseTime);
		},
	
		_validateRanges: function(){
			if (this.startTimeInput && this.endTimeInput && this.timeDelta === null) {
				triggerSimpleCustomEvent(this.container, 'rangeIncomplete');
				return;
			}
	
			if (this.startDateInput && this.endDateInput && this.dateDelta === null) {
				triggerSimpleCustomEvent(this.container, 'rangeIncomplete');
				return;
			}
	
			// due to the fact that times can wrap around, any time-only pair will be considered valid
			if (!this.startDateInput || !this.endDateInput || this.dateDelta + this.timeDelta >= 0) {
				triggerSimpleCustomEvent(this.container, 'rangeSelected');
			} else {
				triggerSimpleCustomEvent(this.container, 'rangeError');
			}
		}
	};

	window.Datepair = Datepair;

}(window, document));
/*!
 * datepair.js v0.4.15 - A javascript plugin for intelligently selecting date and time ranges inspired by Google Calendar.
 * Copyright (c) 2016 Jon Thornton - http://jonthornton.github.com/Datepair.js
 * License: MIT
 */

(function($) {

	if(!$) {
		return;
	}

	////////////
	// Plugin //
	////////////

	$.fn.datepair = function(option) {
		var out;
		this.each(function() {
			var $this = $(this);
			var data = $this.data('datepair');
			var options = typeof option === 'object' && option;

			if (!data) {
				data = new Datepair(this, options);
				$this.data('datepair', data);
			}

			if (option === 'remove') {
				out = data['remove']();
				$this.removeData('datepair', data);
			}

			if (typeof option === 'string') {
				out = data[option]();
			}
		});

		return out || this;
	};

	//////////////
	// Data API //
	//////////////

	$('[data-datepair]').each(function() {
		var $this = $(this);
		$this.datepair($this.data());
	});

}(window.Zepto || window.jQuery));

/*
 *
 * More info at [www.dropzonejs.com](http://www.dropzonejs.com)
 *
 * Copyright (c) 2012, Matias Meno
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */

(function() {
  var Dropzone, Emitter, camelize, contentLoaded, detectVerticalSquash, drawImageIOSFix, noop, without,
    __slice = [].slice,
    __hasProp = {}.hasOwnProperty,
    __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; };

  noop = function() {};

  Emitter = (function() {
    function Emitter() {}

    Emitter.prototype.addEventListener = Emitter.prototype.on;

    Emitter.prototype.on = function(event, fn) {
      this._callbacks = this._callbacks || {};
      if (!this._callbacks[event]) {
        this._callbacks[event] = [];
      }
      this._callbacks[event].push(fn);
      return this;
    };

    Emitter.prototype.emit = function() {
      var args, callback, callbacks, event, _i, _len;
      event = arguments[0], args = 2 <= arguments.length ? __slice.call(arguments, 1) : [];
      this._callbacks = this._callbacks || {};
      callbacks = this._callbacks[event];
      if (callbacks) {
        for (_i = 0, _len = callbacks.length; _i < _len; _i++) {
          callback = callbacks[_i];
          callback.apply(this, args);
        }
      }
      return this;
    };

    Emitter.prototype.removeListener = Emitter.prototype.off;

    Emitter.prototype.removeAllListeners = Emitter.prototype.off;

    Emitter.prototype.removeEventListener = Emitter.prototype.off;

    Emitter.prototype.off = function(event, fn) {
      var callback, callbacks, i, _i, _len;
      if (!this._callbacks || arguments.length === 0) {
        this._callbacks = {};
        return this;
      }
      callbacks = this._callbacks[event];
      if (!callbacks) {
        return this;
      }
      if (arguments.length === 1) {
        delete this._callbacks[event];
        return this;
      }
      for (i = _i = 0, _len = callbacks.length; _i < _len; i = ++_i) {
        callback = callbacks[i];
        if (callback === fn) {
          callbacks.splice(i, 1);
          break;
        }
      }
      return this;
    };

    return Emitter;

  })();

  Dropzone = (function(_super) {
    var extend, resolveOption;

    __extends(Dropzone, _super);

    Dropzone.prototype.Emitter = Emitter;


    /*
    This is a list of all available events you can register on a dropzone object.
    
    You can register an event handler like this:
    
        dropzone.on("dragEnter", function() { });
     */

    Dropzone.prototype.events = ["drop", "dragstart", "dragend", "dragenter", "dragover", "dragleave", "addedfile", "addedfiles", "removedfile", "thumbnail", "error", "errormultiple", "processing", "processingmultiple", "uploadprogress", "totaluploadprogress", "sending", "sendingmultiple", "success", "successmultiple", "canceled", "canceledmultiple", "complete", "completemultiple", "reset", "maxfilesexceeded", "maxfilesreached", "queuecomplete"];

    Dropzone.prototype.defaultOptions = {
      url: null,
      method: "post",
      withCredentials: false,
      parallelUploads: 2,
      uploadMultiple: false,
      maxFilesize: 256,
      paramName: "file",
      createImageThumbnails: true,
      maxThumbnailFilesize: 10,
      thumbnailWidth: 120,
      thumbnailHeight: 120,
      filesizeBase: 1000,
      maxFiles: null,
      params: {},
      clickable: true,
      ignoreHiddenFiles: true,
      acceptedFiles: null,
      acceptedMimeTypes: null,
      autoProcessQueue: true,
      autoQueue: true,
      addRemoveLinks: false,
      previewsContainer: null,
      hiddenInputContainer: "body",
      capture: null,
      renameFilename: null,
      dictDefaultMessage: "Drop files here to upload",
      dictFallbackMessage: "Your browser does not support drag'n'drop file uploads.",
      dictFallbackText: "Please use the fallback form below to upload your files like in the olden days.",
      dictFileTooBig: "File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.",
      dictInvalidFileType: "You can't upload files of this type.",
      dictResponseError: "Server responded with {{statusCode}} code.",
      dictCancelUpload: "Cancel upload",
      dictCancelUploadConfirmation: "Are you sure you want to cancel this upload?",
      dictRemoveFile: "Remove file",
      dictRemoveFileConfirmation: null,
      dictMaxFilesExceeded: "You can not upload any more files.",
      accept: function(file, done) {
        return done();
      },
      init: function() {
        return noop;
      },
      forceFallback: false,
      fallback: function() {
        var child, messageElement, span, _i, _len, _ref;
        this.element.className = "" + this.element.className + " dz-browser-not-supported";
        _ref = this.element.getElementsByTagName("div");
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          child = _ref[_i];
          if (/(^| )dz-message($| )/.test(child.className)) {
            messageElement = child;
            child.className = "dz-message";
            continue;
          }
        }
        if (!messageElement) {
          messageElement = Dropzone.createElement("<div class=\"dz-message\"><span></span></div>");
          this.element.appendChild(messageElement);
        }
        span = messageElement.getElementsByTagName("span")[0];
        if (span) {
          if (span.textContent != null) {
            span.textContent = this.options.dictFallbackMessage;
          } else if (span.innerText != null) {
            span.innerText = this.options.dictFallbackMessage;
          }
        }
        return this.element.appendChild(this.getFallbackForm());
      },
      resize: function(file) {
        var info, srcRatio, trgRatio;
        info = {
          srcX: 0,
          srcY: 0,
          srcWidth: file.width,
          srcHeight: file.height
        };
        srcRatio = file.width / file.height;
        info.optWidth = this.options.thumbnailWidth;
        info.optHeight = this.options.thumbnailHeight;
        if ((info.optWidth == null) && (info.optHeight == null)) {
          info.optWidth = info.srcWidth;
          info.optHeight = info.srcHeight;
        } else if (info.optWidth == null) {
          info.optWidth = srcRatio * info.optHeight;
        } else if (info.optHeight == null) {
          info.optHeight = (1 / srcRatio) * info.optWidth;
        }
        trgRatio = info.optWidth / info.optHeight;
        if (file.height < info.optHeight || file.width < info.optWidth) {
          info.trgHeight = info.srcHeight;
          info.trgWidth = info.srcWidth;
        } else {
          if (srcRatio > trgRatio) {
            info.srcHeight = file.height;
            info.srcWidth = info.srcHeight * trgRatio;
          } else {
            info.srcWidth = file.width;
            info.srcHeight = info.srcWidth / trgRatio;
          }
        }
        info.srcX = (file.width - info.srcWidth) / 2;
        info.srcY = (file.height - info.srcHeight) / 2;
        return info;
      },

      /*
      Those functions register themselves to the events on init and handle all
      the user interface specific stuff. Overwriting them won't break the upload
      but can break the way it's displayed.
      You can overwrite them if you don't like the default behavior. If you just
      want to add an additional event handler, register it on the dropzone object
      and don't overwrite those options.
       */
      drop: function(e) {
        return this.element.classList.remove("dz-drag-hover");
      },
      dragstart: noop,
      dragend: function(e) {
        return this.element.classList.remove("dz-drag-hover");
      },
      dragenter: function(e) {
        return this.element.classList.add("dz-drag-hover");
      },
      dragover: function(e) {
        return this.element.classList.add("dz-drag-hover");
      },
      dragleave: function(e) {
        return this.element.classList.remove("dz-drag-hover");
      },
      paste: noop,
      reset: function() {
        return this.element.classList.remove("dz-started");
      },
      addedfile: function(file) {
        var node, removeFileEvent, removeLink, _i, _j, _k, _len, _len1, _len2, _ref, _ref1, _ref2, _results;
        if (this.element === this.previewsContainer) {
          this.element.classList.add("dz-started");
        }
        if (this.previewsContainer) {
          file.previewElement = Dropzone.createElement(this.options.previewTemplate.trim());
          file.previewTemplate = file.previewElement;
          this.previewsContainer.appendChild(file.previewElement);
          _ref = file.previewElement.querySelectorAll("[data-dz-name]");
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            node = _ref[_i];
            node.textContent = this._renameFilename(file.name);
          }
          _ref1 = file.previewElement.querySelectorAll("[data-dz-size]");
          for (_j = 0, _len1 = _ref1.length; _j < _len1; _j++) {
            node = _ref1[_j];
            node.innerHTML = this.filesize(file.size);
          }
          if (this.options.addRemoveLinks) {
            file._removeLink = Dropzone.createElement("<a class=\"dz-remove\" href=\"javascript:undefined;\" data-dz-remove>" + this.options.dictRemoveFile + "</a>");
            file.previewElement.appendChild(file._removeLink);
          }
          removeFileEvent = (function(_this) {
            return function(e) {
              e.preventDefault();
              e.stopPropagation();
              if (file.status === Dropzone.UPLOADING) {
                return Dropzone.confirm(_this.options.dictCancelUploadConfirmation, function() {
                  return _this.removeFile(file);
                });
              } else {
                if (_this.options.dictRemoveFileConfirmation) {
                  return Dropzone.confirm(_this.options.dictRemoveFileConfirmation, function() {
                    return _this.removeFile(file);
                  });
                } else {
                  return _this.removeFile(file);
                }
              }
            };
          })(this);
          _ref2 = file.previewElement.querySelectorAll("[data-dz-remove]");
          _results = [];
          for (_k = 0, _len2 = _ref2.length; _k < _len2; _k++) {
            removeLink = _ref2[_k];
            _results.push(removeLink.addEventListener("click", removeFileEvent));
          }
          return _results;
        }
      },
      removedfile: function(file) {
        var _ref;
        if (file.previewElement) {
          if ((_ref = file.previewElement) != null) {
            _ref.parentNode.removeChild(file.previewElement);
          }
        }
        return this._updateMaxFilesReachedClass();
      },
      thumbnail: function(file, dataUrl) {
        var thumbnailElement, _i, _len, _ref;
        if (file.previewElement) {
          file.previewElement.classList.remove("dz-file-preview");
          _ref = file.previewElement.querySelectorAll("[data-dz-thumbnail]");
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            thumbnailElement = _ref[_i];
            thumbnailElement.alt = file.name;
            thumbnailElement.src = dataUrl;
          }
          return setTimeout(((function(_this) {
            return function() {
              return file.previewElement.classList.add("dz-image-preview");
            };
          })(this)), 1);
        }
      },
      error: function(file, message) {
        var node, _i, _len, _ref, _results;
        if (file.previewElement) {
          file.previewElement.classList.add("dz-error");
          if (typeof message !== "String" && message.error) {
            message = message.error;
          }
          _ref = file.previewElement.querySelectorAll("[data-dz-errormessage]");
          _results = [];
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            node = _ref[_i];
            _results.push(node.textContent = message);
          }
          return _results;
        }
      },
      errormultiple: noop,
      processing: function(file) {
        if (file.previewElement) {
          file.previewElement.classList.add("dz-processing");
          if (file._removeLink) {
            return file._removeLink.textContent = this.options.dictCancelUpload;
          }
        }
      },
      processingmultiple: noop,
      uploadprogress: function(file, progress, bytesSent) {
        var node, _i, _len, _ref, _results;
        if (file.previewElement) {
          _ref = file.previewElement.querySelectorAll("[data-dz-uploadprogress]");
          _results = [];
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            node = _ref[_i];
            if (node.nodeName === 'PROGRESS') {
              _results.push(node.value = progress);
            } else {
              _results.push(node.style.width = "" + progress + "%");
            }
          }
          return _results;
        }
      },
      totaluploadprogress: noop,
      sending: noop,
      sendingmultiple: noop,
      success: function(file) {
        if (file.previewElement) {
          return file.previewElement.classList.add("dz-success");
        }
      },
      successmultiple: noop,
      canceled: function(file) {
        return this.emit("error", file, "Upload canceled.");
      },
      canceledmultiple: noop,
      complete: function(file) {
        if (file._removeLink) {
          file._removeLink.textContent = this.options.dictRemoveFile;
        }
        if (file.previewElement) {
          return file.previewElement.classList.add("dz-complete");
        }
      },
      completemultiple: noop,
      maxfilesexceeded: noop,
      maxfilesreached: noop,
      queuecomplete: noop,
      addedfiles: noop,
      previewTemplate: "<div class=\"dz-preview dz-file-preview\">\n  <div class=\"dz-image\"><img data-dz-thumbnail /></div>\n  <div class=\"dz-details\">\n    <div class=\"dz-size\"><span data-dz-size></span></div>\n    <div class=\"dz-filename\"><span data-dz-name></span></div>\n  </div>\n  <div class=\"dz-progress\"><span class=\"dz-upload\" data-dz-uploadprogress></span></div>\n  <div class=\"dz-error-message\"><span data-dz-errormessage></span></div>\n  <div class=\"dz-success-mark\">\n    <svg width=\"54px\" height=\"54px\" viewBox=\"0 0 54 54\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:sketch=\"http://www.bohemiancoding.com/sketch/ns\">\n      <title>Check</title>\n      <defs></defs>\n      <g id=\"Page-1\" stroke=\"none\" stroke-width=\"1\" fill=\"none\" fill-rule=\"evenodd\" sketch:type=\"MSPage\">\n        <path d=\"M23.5,31.8431458 L17.5852419,25.9283877 C16.0248253,24.3679711 13.4910294,24.366835 11.9289322,25.9289322 C10.3700136,27.4878508 10.3665912,30.0234455 11.9283877,31.5852419 L20.4147581,40.0716123 C20.5133999,40.1702541 20.6159315,40.2626649 20.7218615,40.3488435 C22.2835669,41.8725651 24.794234,41.8626202 26.3461564,40.3106978 L43.3106978,23.3461564 C44.8771021,21.7797521 44.8758057,19.2483887 43.3137085,17.6862915 C41.7547899,16.1273729 39.2176035,16.1255422 37.6538436,17.6893022 L23.5,31.8431458 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z\" id=\"Oval-2\" stroke-opacity=\"0.198794158\" stroke=\"#747474\" fill-opacity=\"0.816519475\" fill=\"#FFFFFF\" sketch:type=\"MSShapeGroup\"></path>\n      </g>\n    </svg>\n  </div>\n  <div class=\"dz-error-mark\">\n    <svg width=\"54px\" height=\"54px\" viewBox=\"0 0 54 54\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:sketch=\"http://www.bohemiancoding.com/sketch/ns\">\n      <title>Error</title>\n      <defs></defs>\n      <g id=\"Page-1\" stroke=\"none\" stroke-width=\"1\" fill=\"none\" fill-rule=\"evenodd\" sketch:type=\"MSPage\">\n        <g id=\"Check-+-Oval-2\" sketch:type=\"MSLayerGroup\" stroke=\"#747474\" stroke-opacity=\"0.198794158\" fill=\"#FFFFFF\" fill-opacity=\"0.816519475\">\n          <path d=\"M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z\" id=\"Oval-2\" sketch:type=\"MSShapeGroup\"></path>\n        </g>\n      </g>\n    </svg>\n  </div>\n</div>"
    };

    extend = function() {
      var key, object, objects, target, val, _i, _len;
      target = arguments[0], objects = 2 <= arguments.length ? __slice.call(arguments, 1) : [];
      for (_i = 0, _len = objects.length; _i < _len; _i++) {
        object = objects[_i];
        for (key in object) {
          val = object[key];
          target[key] = val;
        }
      }
      return target;
    };

    function Dropzone(element, options) {
      var elementOptions, fallback, _ref;
      this.element = element;
      this.version = Dropzone.version;
      this.defaultOptions.previewTemplate = this.defaultOptions.previewTemplate.replace(/\n*/g, "");
      this.clickableElements = [];
      this.listeners = [];
      this.files = [];
      if (typeof this.element === "string") {
        this.element = document.querySelector(this.element);
      }
      if (!(this.element && (this.element.nodeType != null))) {
        throw new Error("Invalid dropzone element.");
      }
      if (this.element.dropzone) {
        throw new Error("Dropzone already attached.");
      }
      Dropzone.instances.push(this);
      this.element.dropzone = this;
      elementOptions = (_ref = Dropzone.optionsForElement(this.element)) != null ? _ref : {};
      this.options = extend({}, this.defaultOptions, elementOptions, options != null ? options : {});
      if (this.options.forceFallback || !Dropzone.isBrowserSupported()) {
        return this.options.fallback.call(this);
      }
      if (this.options.url == null) {
        this.options.url = this.element.getAttribute("action");
      }
      if (!this.options.url) {
        throw new Error("No URL provided.");
      }
      if (this.options.acceptedFiles && this.options.acceptedMimeTypes) {
        throw new Error("You can't provide both 'acceptedFiles' and 'acceptedMimeTypes'. 'acceptedMimeTypes' is deprecated.");
      }
      if (this.options.acceptedMimeTypes) {
        this.options.acceptedFiles = this.options.acceptedMimeTypes;
        delete this.options.acceptedMimeTypes;
      }
      this.options.method = this.options.method.toUpperCase();
      if ((fallback = this.getExistingFallback()) && fallback.parentNode) {
        fallback.parentNode.removeChild(fallback);
      }
      if (this.options.previewsContainer !== false) {
        if (this.options.previewsContainer) {
          this.previewsContainer = Dropzone.getElement(this.options.previewsContainer, "previewsContainer");
        } else {
          this.previewsContainer = this.element;
        }
      }
      if (this.options.clickable) {
        if (this.options.clickable === true) {
          this.clickableElements = [this.element];
        } else {
          this.clickableElements = Dropzone.getElements(this.options.clickable, "clickable");
        }
      }
      this.init();
    }

    Dropzone.prototype.getAcceptedFiles = function() {
      var file, _i, _len, _ref, _results;
      _ref = this.files;
      _results = [];
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        file = _ref[_i];
        if (file.accepted) {
          _results.push(file);
        }
      }
      return _results;
    };

    Dropzone.prototype.getRejectedFiles = function() {
      var file, _i, _len, _ref, _results;
      _ref = this.files;
      _results = [];
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        file = _ref[_i];
        if (!file.accepted) {
          _results.push(file);
        }
      }
      return _results;
    };

    Dropzone.prototype.getFilesWithStatus = function(status) {
      var file, _i, _len, _ref, _results;
      _ref = this.files;
      _results = [];
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        file = _ref[_i];
        if (file.status === status) {
          _results.push(file);
        }
      }
      return _results;
    };

    Dropzone.prototype.getQueuedFiles = function() {
      return this.getFilesWithStatus(Dropzone.QUEUED);
    };

    Dropzone.prototype.getUploadingFiles = function() {
      return this.getFilesWithStatus(Dropzone.UPLOADING);
    };

    Dropzone.prototype.getAddedFiles = function() {
      return this.getFilesWithStatus(Dropzone.ADDED);
    };

    Dropzone.prototype.getActiveFiles = function() {
      var file, _i, _len, _ref, _results;
      _ref = this.files;
      _results = [];
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        file = _ref[_i];
        if (file.status === Dropzone.UPLOADING || file.status === Dropzone.QUEUED) {
          _results.push(file);
        }
      }
      return _results;
    };

    Dropzone.prototype.init = function() {
      var eventName, noPropagation, setupHiddenFileInput, _i, _len, _ref, _ref1;
      if (this.element.tagName === "form") {
        this.element.setAttribute("enctype", "multipart/form-data");
      }
      if (this.element.classList.contains("dropzone") && !this.element.querySelector(".dz-message")) {
        this.element.appendChild(Dropzone.createElement("<div class=\"dz-default dz-message\"><span>" + this.options.dictDefaultMessage + "</span></div>"));
      }
      if (this.clickableElements.length) {
        setupHiddenFileInput = (function(_this) {
          return function() {
            if (_this.hiddenFileInput) {
              _this.hiddenFileInput.parentNode.removeChild(_this.hiddenFileInput);
            }
            _this.hiddenFileInput = document.createElement("input");
            _this.hiddenFileInput.setAttribute("type", "file");
            if ((_this.options.maxFiles == null) || _this.options.maxFiles > 1) {
              _this.hiddenFileInput.setAttribute("multiple", "multiple");
            }
            _this.hiddenFileInput.className = "dz-hidden-input";
            if (_this.options.acceptedFiles != null) {
              _this.hiddenFileInput.setAttribute("accept", _this.options.acceptedFiles);
            }
            if (_this.options.capture != null) {
              _this.hiddenFileInput.setAttribute("capture", _this.options.capture);
            }
            _this.hiddenFileInput.style.visibility = "hidden";
            _this.hiddenFileInput.style.position = "absolute";
            _this.hiddenFileInput.style.top = "0";
            _this.hiddenFileInput.style.left = "0";
            _this.hiddenFileInput.style.height = "0";
            _this.hiddenFileInput.style.width = "0";
            document.querySelector(_this.options.hiddenInputContainer).appendChild(_this.hiddenFileInput);
            return _this.hiddenFileInput.addEventListener("change", function() {
              var file, files, _i, _len;
              files = _this.hiddenFileInput.files;
              if (files.length) {
                for (_i = 0, _len = files.length; _i < _len; _i++) {
                  file = files[_i];
                  _this.addFile(file);
                }
              }
              _this.emit("addedfiles", files);
              return setupHiddenFileInput();
            });
          };
        })(this);
        setupHiddenFileInput();
      }
      this.URL = (_ref = window.URL) != null ? _ref : window.webkitURL;
      _ref1 = this.events;
      for (_i = 0, _len = _ref1.length; _i < _len; _i++) {
        eventName = _ref1[_i];
        this.on(eventName, this.options[eventName]);
      }
      this.on("uploadprogress", (function(_this) {
        return function() {
          return _this.updateTotalUploadProgress();
        };
      })(this));
      this.on("removedfile", (function(_this) {
        return function() {
          return _this.updateTotalUploadProgress();
        };
      })(this));
      this.on("canceled", (function(_this) {
        return function(file) {
          return _this.emit("complete", file);
        };
      })(this));
      this.on("complete", (function(_this) {
        return function(file) {
          if (_this.getAddedFiles().length === 0 && _this.getUploadingFiles().length === 0 && _this.getQueuedFiles().length === 0) {
            return setTimeout((function() {
              return _this.emit("queuecomplete");
            }), 0);
          }
        };
      })(this));
      noPropagation = function(e) {
        e.stopPropagation();
        if (e.preventDefault) {
          return e.preventDefault();
        } else {
          return e.returnValue = false;
        }
      };
      this.listeners = [
        {
          element: this.element,
          events: {
            "dragstart": (function(_this) {
              return function(e) {
                return _this.emit("dragstart", e);
              };
            })(this),
            "dragenter": (function(_this) {
              return function(e) {
                noPropagation(e);
                return _this.emit("dragenter", e);
              };
            })(this),
            "dragover": (function(_this) {
              return function(e) {
                var efct;
                try {
                  efct = e.dataTransfer.effectAllowed;
                } catch (_error) {}
                e.dataTransfer.dropEffect = 'move' === efct || 'linkMove' === efct ? 'move' : 'copy';
                noPropagation(e);
                return _this.emit("dragover", e);
              };
            })(this),
            "dragleave": (function(_this) {
              return function(e) {
                return _this.emit("dragleave", e);
              };
            })(this),
            "drop": (function(_this) {
              return function(e) {
                noPropagation(e);
                return _this.drop(e);
              };
            })(this),
            "dragend": (function(_this) {
              return function(e) {
                return _this.emit("dragend", e);
              };
            })(this)
          }
        }
      ];
      this.clickableElements.forEach((function(_this) {
        return function(clickableElement) {
          return _this.listeners.push({
            element: clickableElement,
            events: {
              "click": function(evt) {
                if ((clickableElement !== _this.element) || (evt.target === _this.element || Dropzone.elementInside(evt.target, _this.element.querySelector(".dz-message")))) {
                  _this.hiddenFileInput.click();
                }
                return true;
              }
            }
          });
        };
      })(this));
      this.enable();
      return this.options.init.call(this);
    };

    Dropzone.prototype.destroy = function() {
      var _ref;
      this.disable();
      this.removeAllFiles(true);
      if ((_ref = this.hiddenFileInput) != null ? _ref.parentNode : void 0) {
        this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput);
        this.hiddenFileInput = null;
      }
      delete this.element.dropzone;
      return Dropzone.instances.splice(Dropzone.instances.indexOf(this), 1);
    };

    Dropzone.prototype.updateTotalUploadProgress = function() {
      var activeFiles, file, totalBytes, totalBytesSent, totalUploadProgress, _i, _len, _ref;
      totalBytesSent = 0;
      totalBytes = 0;
      activeFiles = this.getActiveFiles();
      if (activeFiles.length) {
        _ref = this.getActiveFiles();
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          file = _ref[_i];
          totalBytesSent += file.upload.bytesSent;
          totalBytes += file.upload.total;
        }
        totalUploadProgress = 100 * totalBytesSent / totalBytes;
      } else {
        totalUploadProgress = 100;
      }
      return this.emit("totaluploadprogress", totalUploadProgress, totalBytes, totalBytesSent);
    };

    Dropzone.prototype._getParamName = function(n) {
      if (typeof this.options.paramName === "function") {
        return this.options.paramName(n);
      } else {
        return "" + this.options.paramName + (this.options.uploadMultiple ? "[" + n + "]" : "");
      }
    };

    Dropzone.prototype._renameFilename = function(name) {
      if (typeof this.options.renameFilename !== "function") {
        return name;
      }
      return this.options.renameFilename(name);
    };

    Dropzone.prototype.getFallbackForm = function() {
      var existingFallback, fields, fieldsString, form;
      if (existingFallback = this.getExistingFallback()) {
        return existingFallback;
      }
      fieldsString = "<div class=\"dz-fallback\">";
      if (this.options.dictFallbackText) {
        fieldsString += "<p>" + this.options.dictFallbackText + "</p>";
      }
      fieldsString += "<input type=\"file\" name=\"" + (this._getParamName(0)) + "\" " + (this.options.uploadMultiple ? 'multiple="multiple"' : void 0) + " /><input type=\"submit\" value=\"Upload!\"></div>";
      fields = Dropzone.createElement(fieldsString);
      if (this.element.tagName !== "FORM") {
        form = Dropzone.createElement("<form action=\"" + this.options.url + "\" enctype=\"multipart/form-data\" method=\"" + this.options.method + "\"></form>");
        form.appendChild(fields);
      } else {
        this.element.setAttribute("enctype", "multipart/form-data");
        this.element.setAttribute("method", this.options.method);
      }
      return form != null ? form : fields;
    };

    Dropzone.prototype.getExistingFallback = function() {
      var fallback, getFallback, tagName, _i, _len, _ref;
      getFallback = function(elements) {
        var el, _i, _len;
        for (_i = 0, _len = elements.length; _i < _len; _i++) {
          el = elements[_i];
          if (/(^| )fallback($| )/.test(el.className)) {
            return el;
          }
        }
      };
      _ref = ["div", "form"];
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        tagName = _ref[_i];
        if (fallback = getFallback(this.element.getElementsByTagName(tagName))) {
          return fallback;
        }
      }
    };

    Dropzone.prototype.setupEventListeners = function() {
      var elementListeners, event, listener, _i, _len, _ref, _results;
      _ref = this.listeners;
      _results = [];
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        elementListeners = _ref[_i];
        _results.push((function() {
          var _ref1, _results1;
          _ref1 = elementListeners.events;
          _results1 = [];
          for (event in _ref1) {
            listener = _ref1[event];
            _results1.push(elementListeners.element.addEventListener(event, listener, false));
          }
          return _results1;
        })());
      }
      return _results;
    };

    Dropzone.prototype.removeEventListeners = function() {
      var elementListeners, event, listener, _i, _len, _ref, _results;
      _ref = this.listeners;
      _results = [];
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        elementListeners = _ref[_i];
        _results.push((function() {
          var _ref1, _results1;
          _ref1 = elementListeners.events;
          _results1 = [];
          for (event in _ref1) {
            listener = _ref1[event];
            _results1.push(elementListeners.element.removeEventListener(event, listener, false));
          }
          return _results1;
        })());
      }
      return _results;
    };

    Dropzone.prototype.disable = function() {
      var file, _i, _len, _ref, _results;
      this.clickableElements.forEach(function(element) {
        return element.classList.remove("dz-clickable");
      });
      this.removeEventListeners();
      _ref = this.files;
      _results = [];
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        file = _ref[_i];
        _results.push(this.cancelUpload(file));
      }
      return _results;
    };

    Dropzone.prototype.enable = function() {
      this.clickableElements.forEach(function(element) {
        return element.classList.add("dz-clickable");
      });
      return this.setupEventListeners();
    };

    Dropzone.prototype.filesize = function(size) {
      var cutoff, i, selectedSize, selectedUnit, unit, units, _i, _len;
      selectedSize = 0;
      selectedUnit = "b";
      if (size > 0) {
        units = ['TB', 'GB', 'MB', 'KB', 'b'];
        for (i = _i = 0, _len = units.length; _i < _len; i = ++_i) {
          unit = units[i];
          cutoff = Math.pow(this.options.filesizeBase, 4 - i) / 10;
          if (size >= cutoff) {
            selectedSize = size / Math.pow(this.options.filesizeBase, 4 - i);
            selectedUnit = unit;
            break;
          }
        }
        selectedSize = Math.round(10 * selectedSize) / 10;
      }
      return "<strong>" + selectedSize + "</strong> " + selectedUnit;
    };

    Dropzone.prototype._updateMaxFilesReachedClass = function() {
      if ((this.options.maxFiles != null) && this.getAcceptedFiles().length >= this.options.maxFiles) {
        if (this.getAcceptedFiles().length === this.options.maxFiles) {
          this.emit('maxfilesreached', this.files);
        }
        return this.element.classList.add("dz-max-files-reached");
      } else {
        return this.element.classList.remove("dz-max-files-reached");
      }
    };

    Dropzone.prototype.drop = function(e) {
      var files, items;
      if (!e.dataTransfer) {
        return;
      }
      this.emit("drop", e);
      files = e.dataTransfer.files;
      this.emit("addedfiles", files);
      if (files.length) {
        items = e.dataTransfer.items;
        if (items && items.length && (items[0].webkitGetAsEntry != null)) {
          this._addFilesFromItems(items);
        } else {
          this.handleFiles(files);
        }
      }
    };

    Dropzone.prototype.paste = function(e) {
      var items, _ref;
      if ((e != null ? (_ref = e.clipboardData) != null ? _ref.items : void 0 : void 0) == null) {
        return;
      }
      this.emit("paste", e);
      items = e.clipboardData.items;
      if (items.length) {
        return this._addFilesFromItems(items);
      }
    };

    Dropzone.prototype.handleFiles = function(files) {
      var file, _i, _len, _results;
      _results = [];
      for (_i = 0, _len = files.length; _i < _len; _i++) {
        file = files[_i];
        _results.push(this.addFile(file));
      }
      return _results;
    };

    Dropzone.prototype._addFilesFromItems = function(items) {
      var entry, item, _i, _len, _results;
      _results = [];
      for (_i = 0, _len = items.length; _i < _len; _i++) {
        item = items[_i];
        if ((item.webkitGetAsEntry != null) && (entry = item.webkitGetAsEntry())) {
          if (entry.isFile) {
            _results.push(this.addFile(item.getAsFile()));
          } else if (entry.isDirectory) {
            _results.push(this._addFilesFromDirectory(entry, entry.name));
          } else {
            _results.push(void 0);
          }
        } else if (item.getAsFile != null) {
          if ((item.kind == null) || item.kind === "file") {
            _results.push(this.addFile(item.getAsFile()));
          } else {
            _results.push(void 0);
          }
        } else {
          _results.push(void 0);
        }
      }
      return _results;
    };

    Dropzone.prototype._addFilesFromDirectory = function(directory, path) {
      var dirReader, errorHandler, readEntries;
      dirReader = directory.createReader();
      errorHandler = function(error) {
        return typeof console !== "undefined" && console !== null ? typeof console.log === "function" ? console.log(error) : void 0 : void 0;
      };
      readEntries = (function(_this) {
        return function() {
          return dirReader.readEntries(function(entries) {
            var entry, _i, _len;
            if (entries.length > 0) {
              for (_i = 0, _len = entries.length; _i < _len; _i++) {
                entry = entries[_i];
                if (entry.isFile) {
                  entry.file(function(file) {
                    if (_this.options.ignoreHiddenFiles && file.name.substring(0, 1) === '.') {
                      return;
                    }
                    file.fullPath = "" + path + "/" + file.name;
                    return _this.addFile(file);
                  });
                } else if (entry.isDirectory) {
                  _this._addFilesFromDirectory(entry, "" + path + "/" + entry.name);
                }
              }
              readEntries();
            }
            return null;
          }, errorHandler);
        };
      })(this);
      return readEntries();
    };

    Dropzone.prototype.accept = function(file, done) {
      if (file.size > this.options.maxFilesize * 1024 * 1024) {
        return done(this.options.dictFileTooBig.replace("{{filesize}}", Math.round(file.size / 1024 / 10.24) / 100).replace("{{maxFilesize}}", this.options.maxFilesize));
      } else if (!Dropzone.isValidFile(file, this.options.acceptedFiles)) {
        return done(this.options.dictInvalidFileType);
      } else if ((this.options.maxFiles != null) && this.getAcceptedFiles().length >= this.options.maxFiles) {
        done(this.options.dictMaxFilesExceeded.replace("{{maxFiles}}", this.options.maxFiles));
        return this.emit("maxfilesexceeded", file);
      } else {
        return this.options.accept.call(this, file, done);
      }
    };

    Dropzone.prototype.addFile = function(file) {
      file.upload = {
        progress: 0,
        total: file.size,
        bytesSent: 0
      };
      this.files.push(file);
      file.status = Dropzone.ADDED;
      this.emit("addedfile", file);
      this._enqueueThumbnail(file);
      return this.accept(file, (function(_this) {
        return function(error) {
          if (error) {
            file.accepted = false;
            _this._errorProcessing([file], error);
          } else {
            file.accepted = true;
            if (_this.options.autoQueue) {
              _this.enqueueFile(file);
            }
          }
          return _this._updateMaxFilesReachedClass();
        };
      })(this));
    };

    Dropzone.prototype.enqueueFiles = function(files) {
      var file, _i, _len;
      for (_i = 0, _len = files.length; _i < _len; _i++) {
        file = files[_i];
        this.enqueueFile(file);
      }
      return null;
    };

    Dropzone.prototype.enqueueFile = function(file) {
      if (file.status === Dropzone.ADDED && file.accepted === true) {
        file.status = Dropzone.QUEUED;
        if (this.options.autoProcessQueue) {
          return setTimeout(((function(_this) {
            return function() {
              return _this.processQueue();
            };
          })(this)), 0);
        }
      } else {
        throw new Error("This file can't be queued because it has already been processed or was rejected.");
      }
    };

    Dropzone.prototype._thumbnailQueue = [];

    Dropzone.prototype._processingThumbnail = false;

    Dropzone.prototype._enqueueThumbnail = function(file) {
      if (this.options.createImageThumbnails && file.type.match(/image.*/) && file.size <= this.options.maxThumbnailFilesize * 1024 * 1024) {
        this._thumbnailQueue.push(file);
        return setTimeout(((function(_this) {
          return function() {
            return _this._processThumbnailQueue();
          };
        })(this)), 0);
      }
    };

    Dropzone.prototype._processThumbnailQueue = function() {
      if (this._processingThumbnail || this._thumbnailQueue.length === 0) {
        return;
      }
      this._processingThumbnail = true;
      return this.createThumbnail(this._thumbnailQueue.shift(), (function(_this) {
        return function() {
          _this._processingThumbnail = false;
          return _this._processThumbnailQueue();
        };
      })(this));
    };

    Dropzone.prototype.removeFile = function(file) {
      if (file.status === Dropzone.UPLOADING) {
        this.cancelUpload(file);
      }
      this.files = without(this.files, file);
      this.emit("removedfile", file);
      if (this.files.length === 0) {
        return this.emit("reset");
      }
    };

    Dropzone.prototype.removeAllFiles = function(cancelIfNecessary) {
      var file, _i, _len, _ref;
      if (cancelIfNecessary == null) {
        cancelIfNecessary = false;
      }
      _ref = this.files.slice();
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        file = _ref[_i];
        if (file.status !== Dropzone.UPLOADING || cancelIfNecessary) {
          this.removeFile(file);
        }
      }
      return null;
    };

    Dropzone.prototype.createThumbnail = function(file, callback) {
      var fileReader;
      fileReader = new FileReader;
      fileReader.onload = (function(_this) {
        return function() {
          if (file.type === "image/svg+xml") {
            _this.emit("thumbnail", file, fileReader.result);
            if (callback != null) {
              callback();
            }
            return;
          }
          return _this.createThumbnailFromUrl(file, fileReader.result, callback);
        };
      })(this);
      return fileReader.readAsDataURL(file);
    };

    Dropzone.prototype.createThumbnailFromUrl = function(file, imageUrl, callback, crossOrigin) {
      var img;
      img = document.createElement("img");
      if (crossOrigin) {
        img.crossOrigin = crossOrigin;
      }
      img.onload = (function(_this) {
        return function() {
          var canvas, ctx, resizeInfo, thumbnail, _ref, _ref1, _ref2, _ref3;
          file.width = img.width;
          file.height = img.height;
          resizeInfo = _this.options.resize.call(_this, file);
          if (resizeInfo.trgWidth == null) {
            resizeInfo.trgWidth = resizeInfo.optWidth;
          }
          if (resizeInfo.trgHeight == null) {
            resizeInfo.trgHeight = resizeInfo.optHeight;
          }
          canvas = document.createElement("canvas");
          ctx = canvas.getContext("2d");
          canvas.width = resizeInfo.trgWidth;
          canvas.height = resizeInfo.trgHeight;
          drawImageIOSFix(ctx, img, (_ref = resizeInfo.srcX) != null ? _ref : 0, (_ref1 = resizeInfo.srcY) != null ? _ref1 : 0, resizeInfo.srcWidth, resizeInfo.srcHeight, (_ref2 = resizeInfo.trgX) != null ? _ref2 : 0, (_ref3 = resizeInfo.trgY) != null ? _ref3 : 0, resizeInfo.trgWidth, resizeInfo.trgHeight);
          thumbnail = canvas.toDataURL("image/png");
          _this.emit("thumbnail", file, thumbnail);
          if (callback != null) {
            return callback();
          }
        };
      })(this);
      if (callback != null) {
        img.onerror = callback;
      }
      return img.src = imageUrl;
    };

    Dropzone.prototype.processQueue = function() {
      var i, parallelUploads, processingLength, queuedFiles;
      parallelUploads = this.options.parallelUploads;
      processingLength = this.getUploadingFiles().length;
      i = processingLength;
      if (processingLength >= parallelUploads) {
        return;
      }
      queuedFiles = this.getQueuedFiles();
      if (!(queuedFiles.length > 0)) {
        return;
      }
      if (this.options.uploadMultiple) {
        return this.processFiles(queuedFiles.slice(0, parallelUploads - processingLength));
      } else {
        while (i < parallelUploads) {
          if (!queuedFiles.length) {
            return;
          }
          this.processFile(queuedFiles.shift());
          i++;
        }
      }
    };

    Dropzone.prototype.processFile = function(file) {
      return this.processFiles([file]);
    };

    Dropzone.prototype.processFiles = function(files) {
      var file, _i, _len;
      for (_i = 0, _len = files.length; _i < _len; _i++) {
        file = files[_i];
        file.processing = true;
        file.status = Dropzone.UPLOADING;
        this.emit("processing", file);
      }
      if (this.options.uploadMultiple) {
        this.emit("processingmultiple", files);
      }
      return this.uploadFiles(files);
    };

    Dropzone.prototype._getFilesWithXhr = function(xhr) {
      var file, files;
      return files = (function() {
        var _i, _len, _ref, _results;
        _ref = this.files;
        _results = [];
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          file = _ref[_i];
          if (file.xhr === xhr) {
            _results.push(file);
          }
        }
        return _results;
      }).call(this);
    };

    Dropzone.prototype.cancelUpload = function(file) {
      var groupedFile, groupedFiles, _i, _j, _len, _len1, _ref;
      if (file.status === Dropzone.UPLOADING) {
        groupedFiles = this._getFilesWithXhr(file.xhr);
        for (_i = 0, _len = groupedFiles.length; _i < _len; _i++) {
          groupedFile = groupedFiles[_i];
          groupedFile.status = Dropzone.CANCELED;
        }
        file.xhr.abort();
        for (_j = 0, _len1 = groupedFiles.length; _j < _len1; _j++) {
          groupedFile = groupedFiles[_j];
          this.emit("canceled", groupedFile);
        }
        if (this.options.uploadMultiple) {
          this.emit("canceledmultiple", groupedFiles);
        }
      } else if ((_ref = file.status) === Dropzone.ADDED || _ref === Dropzone.QUEUED) {
        file.status = Dropzone.CANCELED;
        this.emit("canceled", file);
        if (this.options.uploadMultiple) {
          this.emit("canceledmultiple", [file]);
        }
      }
      if (this.options.autoProcessQueue) {
        return this.processQueue();
      }
    };

    resolveOption = function() {
      var args, option;
      option = arguments[0], args = 2 <= arguments.length ? __slice.call(arguments, 1) : [];
      if (typeof option === 'function') {
        return option.apply(this, args);
      }
      return option;
    };

    Dropzone.prototype.uploadFile = function(file) {
      return this.uploadFiles([file]);
    };

    Dropzone.prototype.uploadFiles = function(files) {
      var file, formData, handleError, headerName, headerValue, headers, i, input, inputName, inputType, key, method, option, progressObj, response, updateProgress, url, value, xhr, _i, _j, _k, _l, _len, _len1, _len2, _len3, _m, _ref, _ref1, _ref2, _ref3, _ref4, _ref5;
      xhr = new XMLHttpRequest();
      for (_i = 0, _len = files.length; _i < _len; _i++) {
        file = files[_i];
        file.xhr = xhr;
      }
      method = resolveOption(this.options.method, files);
      url = resolveOption(this.options.url, files);
      xhr.open(method, url, true);
      xhr.withCredentials = !!this.options.withCredentials;
      response = null;
      handleError = (function(_this) {
        return function() {
          var _j, _len1, _results;
          _results = [];
          for (_j = 0, _len1 = files.length; _j < _len1; _j++) {
            file = files[_j];
            _results.push(_this._errorProcessing(files, response || _this.options.dictResponseError.replace("{{statusCode}}", xhr.status), xhr));
          }
          return _results;
        };
      })(this);
      updateProgress = (function(_this) {
        return function(e) {
          var allFilesFinished, progress, _j, _k, _l, _len1, _len2, _len3, _results;
          if (e != null) {
            progress = 100 * e.loaded / e.total;
            for (_j = 0, _len1 = files.length; _j < _len1; _j++) {
              file = files[_j];
              file.upload = {
                progress: progress,
                total: e.total,
                bytesSent: e.loaded
              };
            }
          } else {
            allFilesFinished = true;
            progress = 100;
            for (_k = 0, _len2 = files.length; _k < _len2; _k++) {
              file = files[_k];
              if (!(file.upload.progress === 100 && file.upload.bytesSent === file.upload.total)) {
                allFilesFinished = false;
              }
              file.upload.progress = progress;
              file.upload.bytesSent = file.upload.total;
            }
            if (allFilesFinished) {
              return;
            }
          }
          _results = [];
          for (_l = 0, _len3 = files.length; _l < _len3; _l++) {
            file = files[_l];
            _results.push(_this.emit("uploadprogress", file, progress, file.upload.bytesSent));
          }
          return _results;
        };
      })(this);
      xhr.onload = (function(_this) {
        return function(e) {
          var _ref;
          if (files[0].status === Dropzone.CANCELED) {
            return;
          }
          if (xhr.readyState !== 4) {
            return;
          }
          response = xhr.responseText;
          if (xhr.getResponseHeader("content-type") && ~xhr.getResponseHeader("content-type").indexOf("application/json")) {
            try {
              response = JSON.parse(response);
            } catch (_error) {
              e = _error;
              response = "Invalid JSON response from server.";
            }
          }
          updateProgress();
          if (!((200 <= (_ref = xhr.status) && _ref < 300))) {
            return handleError();
          } else {
            return _this._finished(files, response, e);
          }
        };
      })(this);
      xhr.onerror = (function(_this) {
        return function() {
          if (files[0].status === Dropzone.CANCELED) {
            return;
          }
          return handleError();
        };
      })(this);
      progressObj = (_ref = xhr.upload) != null ? _ref : xhr;
      progressObj.onprogress = updateProgress;
      headers = {
        "Accept": "application/json",
        "Cache-Control": "no-cache",
        "X-Requested-With": "XMLHttpRequest"
      };
      if (this.options.headers) {
        extend(headers, this.options.headers);
      }
      for (headerName in headers) {
        headerValue = headers[headerName];
        if (headerValue) {
          xhr.setRequestHeader(headerName, headerValue);
        }
      }
      formData = new FormData();
      if (this.options.params) {
        _ref1 = this.options.params;
        for (key in _ref1) {
          value = _ref1[key];
          formData.append(key, value);
        }
      }
      for (_j = 0, _len1 = files.length; _j < _len1; _j++) {
        file = files[_j];
        this.emit("sending", file, xhr, formData);
      }
      if (this.options.uploadMultiple) {
        this.emit("sendingmultiple", files, xhr, formData);
      }
      if (this.element.tagName === "FORM") {
        _ref2 = this.element.querySelectorAll("input, textarea, select, button");
        for (_k = 0, _len2 = _ref2.length; _k < _len2; _k++) {
          input = _ref2[_k];
          inputName = input.getAttribute("name");
          inputType = input.getAttribute("type");
          if (input.tagName === "SELECT" && input.hasAttribute("multiple")) {
            _ref3 = input.options;
            for (_l = 0, _len3 = _ref3.length; _l < _len3; _l++) {
              option = _ref3[_l];
              if (option.selected) {
                formData.append(inputName, option.value);
              }
            }
          } else if (!inputType || ((_ref4 = inputType.toLowerCase()) !== "checkbox" && _ref4 !== "radio") || input.checked) {
            formData.append(inputName, input.value);
          }
        }
      }
      for (i = _m = 0, _ref5 = files.length - 1; 0 <= _ref5 ? _m <= _ref5 : _m >= _ref5; i = 0 <= _ref5 ? ++_m : --_m) {
        formData.append(this._getParamName(i), files[i], this._renameFilename(files[i].name));
      }
      return this.submitRequest(xhr, formData, files);
    };

    Dropzone.prototype.submitRequest = function(xhr, formData, files) {
      return xhr.send(formData);
    };

    Dropzone.prototype._finished = function(files, responseText, e) {
      var file, _i, _len;
      for (_i = 0, _len = files.length; _i < _len; _i++) {
        file = files[_i];
        file.status = Dropzone.SUCCESS;
        this.emit("success", file, responseText, e);
        this.emit("complete", file);
      }
      if (this.options.uploadMultiple) {
        this.emit("successmultiple", files, responseText, e);
        this.emit("completemultiple", files);
      }
      if (this.options.autoProcessQueue) {
        return this.processQueue();
      }
    };

    Dropzone.prototype._errorProcessing = function(files, message, xhr) {
      var file, _i, _len;
      for (_i = 0, _len = files.length; _i < _len; _i++) {
        file = files[_i];
        file.status = Dropzone.ERROR;
        this.emit("error", file, message, xhr);
        this.emit("complete", file);
      }
      if (this.options.uploadMultiple) {
        this.emit("errormultiple", files, message, xhr);
        this.emit("completemultiple", files);
      }
      if (this.options.autoProcessQueue) {
        return this.processQueue();
      }
    };

    return Dropzone;

  })(Emitter);

  Dropzone.version = "4.3.0";

  Dropzone.options = {};

  Dropzone.optionsForElement = function(element) {
    if (element.getAttribute("id")) {
      return Dropzone.options[camelize(element.getAttribute("id"))];
    } else {
      return void 0;
    }
  };

  Dropzone.instances = [];

  Dropzone.forElement = function(element) {
    if (typeof element === "string") {
      element = document.querySelector(element);
    }
    if ((element != null ? element.dropzone : void 0) == null) {
      throw new Error("No Dropzone found for given element. This is probably because you're trying to access it before Dropzone had the time to initialize. Use the `init` option to setup any additional observers on your Dropzone.");
    }
    return element.dropzone;
  };

  Dropzone.autoDiscover = true;

  Dropzone.discover = function() {
    var checkElements, dropzone, dropzones, _i, _len, _results;
    if (document.querySelectorAll) {
      dropzones = document.querySelectorAll(".dropzone");
    } else {
      dropzones = [];
      checkElements = function(elements) {
        var el, _i, _len, _results;
        _results = [];
        for (_i = 0, _len = elements.length; _i < _len; _i++) {
          el = elements[_i];
          if (/(^| )dropzone($| )/.test(el.className)) {
            _results.push(dropzones.push(el));
          } else {
            _results.push(void 0);
          }
        }
        return _results;
      };
      checkElements(document.getElementsByTagName("div"));
      checkElements(document.getElementsByTagName("form"));
    }
    _results = [];
    for (_i = 0, _len = dropzones.length; _i < _len; _i++) {
      dropzone = dropzones[_i];
      if (Dropzone.optionsForElement(dropzone) !== false) {
        _results.push(new Dropzone(dropzone));
      } else {
        _results.push(void 0);
      }
    }
    return _results;
  };

  Dropzone.blacklistedBrowsers = [/opera.*Macintosh.*version\/12/i];

  Dropzone.isBrowserSupported = function() {
    var capableBrowser, regex, _i, _len, _ref;
    capableBrowser = true;
    if (window.File && window.FileReader && window.FileList && window.Blob && window.FormData && document.querySelector) {
      if (!("classList" in document.createElement("a"))) {
        capableBrowser = false;
      } else {
        _ref = Dropzone.blacklistedBrowsers;
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          regex = _ref[_i];
          if (regex.test(navigator.userAgent)) {
            capableBrowser = false;
            continue;
          }
        }
      }
    } else {
      capableBrowser = false;
    }
    return capableBrowser;
  };

  without = function(list, rejectedItem) {
    var item, _i, _len, _results;
    _results = [];
    for (_i = 0, _len = list.length; _i < _len; _i++) {
      item = list[_i];
      if (item !== rejectedItem) {
        _results.push(item);
      }
    }
    return _results;
  };

  camelize = function(str) {
    return str.replace(/[\-_](\w)/g, function(match) {
      return match.charAt(1).toUpperCase();
    });
  };

  Dropzone.createElement = function(string) {
    var div;
    div = document.createElement("div");
    div.innerHTML = string;
    return div.childNodes[0];
  };

  Dropzone.elementInside = function(element, container) {
    if (element === container) {
      return true;
    }
    while (element = element.parentNode) {
      if (element === container) {
        return true;
      }
    }
    return false;
  };

  Dropzone.getElement = function(el, name) {
    var element;
    if (typeof el === "string") {
      element = document.querySelector(el);
    } else if (el.nodeType != null) {
      element = el;
    }
    if (element == null) {
      throw new Error("Invalid `" + name + "` option provided. Please provide a CSS selector or a plain HTML element.");
    }
    return element;
  };

  Dropzone.getElements = function(els, name) {
    var e, el, elements, _i, _j, _len, _len1, _ref;
    if (els instanceof Array) {
      elements = [];
      try {
        for (_i = 0, _len = els.length; _i < _len; _i++) {
          el = els[_i];
          elements.push(this.getElement(el, name));
        }
      } catch (_error) {
        e = _error;
        elements = null;
      }
    } else if (typeof els === "string") {
      elements = [];
      _ref = document.querySelectorAll(els);
      for (_j = 0, _len1 = _ref.length; _j < _len1; _j++) {
        el = _ref[_j];
        elements.push(el);
      }
    } else if (els.nodeType != null) {
      elements = [els];
    }
    if (!((elements != null) && elements.length)) {
      throw new Error("Invalid `" + name + "` option provided. Please provide a CSS selector, a plain HTML element or a list of those.");
    }
    return elements;
  };

  Dropzone.confirm = function(question, accepted, rejected) {
    if (window.confirm(question)) {
      return accepted();
    } else if (rejected != null) {
      return rejected();
    }
  };

  Dropzone.isValidFile = function(file, acceptedFiles) {
    var baseMimeType, mimeType, validType, _i, _len;
    if (!acceptedFiles) {
      return true;
    }
    acceptedFiles = acceptedFiles.split(",");
    mimeType = file.type;
    baseMimeType = mimeType.replace(/\/.*$/, "");
    for (_i = 0, _len = acceptedFiles.length; _i < _len; _i++) {
      validType = acceptedFiles[_i];
      validType = validType.trim();
      if (validType.charAt(0) === ".") {
        if (file.name.toLowerCase().indexOf(validType.toLowerCase(), file.name.length - validType.length) !== -1) {
          return true;
        }
      } else if (/\/\*$/.test(validType)) {
        if (baseMimeType === validType.replace(/\/.*$/, "")) {
          return true;
        }
      } else {
        if (mimeType === validType) {
          return true;
        }
      }
    }
    return false;
  };

  if (typeof jQuery !== "undefined" && jQuery !== null) {
    jQuery.fn.dropzone = function(options) {
      return this.each(function() {
        return new Dropzone(this, options);
      });
    };
  }

  if (typeof module !== "undefined" && module !== null) {
    module.exports = Dropzone;
  } else {
    window.Dropzone = Dropzone;
  }

  Dropzone.ADDED = "added";

  Dropzone.QUEUED = "queued";

  Dropzone.ACCEPTED = Dropzone.QUEUED;

  Dropzone.UPLOADING = "uploading";

  Dropzone.PROCESSING = Dropzone.UPLOADING;

  Dropzone.CANCELED = "canceled";

  Dropzone.ERROR = "error";

  Dropzone.SUCCESS = "success";


  /*
  
  Bugfix for iOS 6 and 7
  Source: http://stackoverflow.com/questions/11929099/html5-canvas-drawimage-ratio-bug-ios
  based on the work of https://github.com/stomita/ios-imagefile-megapixel
   */

  detectVerticalSquash = function(img) {
    var alpha, canvas, ctx, data, ey, ih, iw, py, ratio, sy;
    iw = img.naturalWidth;
    ih = img.naturalHeight;
    canvas = document.createElement("canvas");
    canvas.width = 1;
    canvas.height = ih;
    ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    data = ctx.getImageData(0, 0, 1, ih).data;
    sy = 0;
    ey = ih;
    py = ih;
    while (py > sy) {
      alpha = data[(py - 1) * 4 + 3];
      if (alpha === 0) {
        ey = py;
      } else {
        sy = py;
      }
      py = (ey + sy) >> 1;
    }
    ratio = py / ih;
    if (ratio === 0) {
      return 1;
    } else {
      return ratio;
    }
  };

  drawImageIOSFix = function(ctx, img, sx, sy, sw, sh, dx, dy, dw, dh) {
    var vertSquashRatio;
    vertSquashRatio = detectVerticalSquash(img);
    return ctx.drawImage(img, sx, sy, sw, sh, dx, dy, dw, dh / vertSquashRatio);
  };


  /*
   * contentloaded.js
   *
   * Author: Diego Perini (diego.perini at gmail.com)
   * Summary: cross-browser wrapper for DOMContentLoaded
   * Updated: 20101020
   * License: MIT
   * Version: 1.2
   *
   * URL:
   * http://javascript.nwbox.com/ContentLoaded/
   * http://javascript.nwbox.com/ContentLoaded/MIT-LICENSE
   */

  contentLoaded = function(win, fn) {
    var add, doc, done, init, poll, pre, rem, root, top;
    done = false;
    top = true;
    doc = win.document;
    root = doc.documentElement;
    add = (doc.addEventListener ? "addEventListener" : "attachEvent");
    rem = (doc.addEventListener ? "removeEventListener" : "detachEvent");
    pre = (doc.addEventListener ? "" : "on");
    init = function(e) {
      if (e.type === "readystatechange" && doc.readyState !== "complete") {
        return;
      }
      (e.type === "load" ? win : doc)[rem](pre + e.type, init, false);
      if (!done && (done = true)) {
        return fn.call(win, e.type || e);
      }
    };
    poll = function() {
      var e;
      try {
        root.doScroll("left");
      } catch (_error) {
        e = _error;
        setTimeout(poll, 50);
        return;
      }
      return init("poll");
    };
    if (doc.readyState !== "complete") {
      if (doc.createEventObject && root.doScroll) {
        try {
          top = !win.frameElement;
        } catch (_error) {}
        if (top) {
          poll();
        }
      }
      doc[add](pre + "DOMContentLoaded", init, false);
      doc[add](pre + "readystatechange", init, false);
      return win[add](pre + "load", init, false);
    }
  };

  Dropzone._autoDiscoverFunction = function() {
    if (Dropzone.autoDiscover) {
      return Dropzone.discover();
    }
  };

  contentLoaded(window, Dropzone._autoDiscoverFunction);

}).call(this);

/*!
 * jquery-timepicker v1.10.1 - A jQuery timepicker plugin inspired by Google Calendar. It supports both mouse and keyboard navigation.
 * Copyright (c) 2015 Jon Thornton - http://jonthornton.github.com/jquery-timepicker/
 * License: MIT
 */


(function (factory) {
    if (typeof exports === "object" && exports &&
        typeof module === "object" && module && module.exports === exports) {
        // Browserify. Attach to jQuery module.
        factory(require("jquery"));
    } else if (typeof define === 'function' && define.amd) {
		// AMD. Register as an anonymous module.
		define(['jquery'], factory);
	} else {
		// Browser globals
		factory(jQuery);
	}
}(function ($) {
	var _ONE_DAY = 86400;
	var _lang = {
		am: 'am',
		pm: 'pm',
		AM: 'AM',
		PM: 'PM',
		decimal: '.',
		mins: 'mins',
		hr: 'hr',
		hrs: 'hrs'
	};

	var methods = {
		init: function(options)
		{
			return this.each(function()
			{
				var self = $(this);

				// pick up settings from data attributes
				var attributeOptions = [];
				for (var key in $.fn.timepicker.defaults) {
					if (self.data(key))  {
						attributeOptions[key] = self.data(key);
					}
				}

				var settings = $.extend({}, $.fn.timepicker.defaults, attributeOptions, options);

				if (settings.lang) {
					_lang = $.extend(_lang, settings.lang);
				}

				settings = _parseSettings(settings);
				self.data('timepicker-settings', settings);
				self.addClass('ui-timepicker-input');

				if (settings.useSelect) {
					_render(self);
				} else {
					self.prop('autocomplete', 'off');
					if (settings.showOn) {
						for (var i in settings.showOn) {
							self.on(settings.showOn[i]+'.timepicker', methods.show);
						}
					}
					self.on('change.timepicker', _formatValue);
					self.on('keydown.timepicker', _keydownhandler);
					self.on('keyup.timepicker', _keyuphandler);
					if (settings.disableTextInput) {
						self.on('keydown.timepicker', _disableTextInputHandler);
					}

					_formatValue.call(self.get(0));
				}
			});
		},

		show: function(e)
		{
			var self = $(this);
			var settings = self.data('timepicker-settings');

			if (e) {
				e.preventDefault();
			}

			if (settings.useSelect) {
				self.data('timepicker-list').focus();
				return;
			}

			if (_hideKeyboard(self)) {
				// block the keyboard on mobile devices
				self.blur();
			}

			var list = self.data('timepicker-list');

			// check if input is readonly
			if (self.prop('readonly')) {
				return;
			}

			// check if list needs to be rendered
			if (!list || list.length === 0 || typeof settings.durationTime === 'function') {
				_render(self);
				list = self.data('timepicker-list');
			}

			if (_isVisible(list)) {
				return;
			}

			self.data('ui-timepicker-value', self.val());
			_setSelected(self, list);

			// make sure other pickers are hidden
			methods.hide();

			// position the dropdown relative to the input
			list.show();
			var listOffset = {};

			if (settings.orientation.match(/r/)) {
				// right-align the dropdown
				listOffset.left = self.offset().left + self.outerWidth() - list.outerWidth() + parseInt(list.css('marginLeft').replace('px', ''), 10);
			} else {
				// left-align the dropdown
				listOffset.left = self.offset().left + parseInt(list.css('marginLeft').replace('px', ''), 10);
			}

			var verticalOrientation;
			if (settings.orientation.match(/t/)) {
				verticalOrientation = 't';
			} else if (settings.orientation.match(/b/)) {
				verticalOrientation = 'b';
			} else if ((self.offset().top + self.outerHeight(true) + list.outerHeight()) > $(window).height() + $(window).scrollTop()) {
				verticalOrientation = 't';
			} else {
				verticalOrientation = 'b';
			}

			if (verticalOrientation == 't') {
				// position the dropdown on top
				list.addClass('ui-timepicker-positioned-top');
				listOffset.top = self.offset().top - list.outerHeight() + parseInt(list.css('marginTop').replace('px', ''), 10);
			} else {
				// put it under the input
				list.removeClass('ui-timepicker-positioned-top');
				listOffset.top = self.offset().top + self.outerHeight() + parseInt(list.css('marginTop').replace('px', ''), 10);
			}

			list.offset(listOffset);

			// position scrolling
			var selected = list.find('.ui-timepicker-selected');

			if (!selected.length) {
				var timeInt = _time2int(_getTimeValue(self));
				if (timeInt !== null) {
					selected = _findRow(self, list, timeInt);
				} else if (settings.scrollDefault) {
					selected = _findRow(self, list, settings.scrollDefault());
				}
			}

			if (selected && selected.length) {
				var topOffset = list.scrollTop() + selected.position().top - selected.outerHeight();
				list.scrollTop(topOffset);
			} else {
				list.scrollTop(0);
			}

			// prevent scroll propagation
			if(settings.stopScrollPropagation) {
				$(document).on('wheel.ui-timepicker', '.ui-timepicker-wrapper', function(e){
					e.preventDefault();
					var currentScroll = $(this).scrollTop();
					$(this).scrollTop(currentScroll + e.originalEvent.deltaY);
				});
			}

			// attach close handlers
			$(document).on('touchstart.ui-timepicker mousedown.ui-timepicker', _closeHandler);
			$(window).on('resize.ui-timepicker', _closeHandler);
			if (settings.closeOnWindowScroll) {
				$(document).on('scroll.ui-timepicker', _closeHandler);
			}

			self.trigger('showTimepicker');

			return this;
		},

		hide: function(e)
		{
			var self = $(this);
			var settings = self.data('timepicker-settings');

			if (settings && settings.useSelect) {
				self.blur();
			}

			$('.ui-timepicker-wrapper').each(function() {
				var list = $(this);
				if (!_isVisible(list)) {
					return;
				}

				var self = list.data('timepicker-input');
				var settings = self.data('timepicker-settings');

				if (settings && settings.selectOnBlur) {
					_selectValue(self);
				}

				list.hide();
				self.trigger('hideTimepicker');
			});

			return this;
		},

		option: function(key, value)
		{
			if (typeof key == 'string' && typeof value == 'undefined') {
				return $(this).data('timepicker-settings')[key];
			}

			return this.each(function(){
				var self = $(this);
				var settings = self.data('timepicker-settings');
				var list = self.data('timepicker-list');

				if (typeof key == 'object') {
					settings = $.extend(settings, key);
				} else if (typeof key == 'string') {
					settings[key] = value;
				}

				settings = _parseSettings(settings);

				self.data('timepicker-settings', settings);

				if (list) {
					list.remove();
					self.data('timepicker-list', false);
				}

				if (settings.useSelect) {
					_render(self);
				}
			});
		},

		getSecondsFromMidnight: function()
		{
			return _time2int(_getTimeValue(this));
		},

		getTime: function(relative_date)
		{
			var self = this;

			var time_string = _getTimeValue(self);
			if (!time_string) {
				return null;
			}

			var offset = _time2int(time_string);
			if (offset === null) {
				return null;
			}

			if (!relative_date) {
				relative_date = new Date();
			}

			// construct a Date from relative date, and offset's time
			var time = new Date(relative_date);
			time.setHours(offset / 3600);
			time.setMinutes(offset % 3600 / 60);
			time.setSeconds(offset % 60);
			time.setMilliseconds(0);

			return time;
		},

		isVisible: function() {
			var self = this;
			var list = self.data('timepicker-list');
			return !!(list && _isVisible(list));
		},

		setTime: function(value)
		{
			var self = this;
			var settings = self.data('timepicker-settings');

			if (settings.forceRoundTime) {
				var prettyTime = _roundAndFormatTime(_time2int(value), settings)
			} else {
				var prettyTime = _int2time(_time2int(value), settings);
			}

			if (value && prettyTime === null && settings.noneOption) {
				prettyTime = value;
			}

			_setTimeValue(self, prettyTime);
			if (self.data('timepicker-list')) {
				_setSelected(self, self.data('timepicker-list'));
			}

			return this;
		},

		remove: function()
		{
			var self = this;

			// check if this element is a timepicker
			if (!self.hasClass('ui-timepicker-input')) {
				return;
			}

			var settings = self.data('timepicker-settings');
			self.removeAttr('autocomplete', 'off');
			self.removeClass('ui-timepicker-input');
			self.removeData('timepicker-settings');
			self.off('.timepicker');

			// timepicker-list won't be present unless the user has interacted with this timepicker
			if (self.data('timepicker-list')) {
				self.data('timepicker-list').remove();
			}

			if (settings.useSelect) {
				self.show();
			}

			self.removeData('timepicker-list');

			return this;
		}
	};

	// private methods

	function _isVisible(elem)
	{
		var el = elem[0];
		return el.offsetWidth > 0 && el.offsetHeight > 0;
	}

	function _parseSettings(settings)
	{
		if (settings.minTime) {
			settings.minTime = _time2int(settings.minTime);
		}

		if (settings.maxTime) {
			settings.maxTime = _time2int(settings.maxTime);
		}

		if (settings.durationTime && typeof settings.durationTime !== 'function') {
			settings.durationTime = _time2int(settings.durationTime);
		}

		if (settings.scrollDefault == 'now') {
			settings.scrollDefault = function() {
				return settings.roundingFunction(_time2int(new Date()), settings);
			}
		} else if (settings.scrollDefault && typeof settings.scrollDefault != 'function') {
			var val = settings.scrollDefault;
			settings.scrollDefault = function() {
				return settings.roundingFunction(_time2int(val), settings);
			}
		} else if (settings.minTime) {
			settings.scrollDefault = function() {
				return settings.roundingFunction(settings.minTime, settings);
			}
		}

		if ($.type(settings.timeFormat) === "string" && settings.timeFormat.match(/[gh]/)) {
			settings._twelveHourTime = true;
		}

		if (settings.showOnFocus === false && settings.showOn.indexOf('focus') != -1) {
			settings.showOn.splice(settings.showOn.indexOf('focus'), 1);
		}

		if (settings.disableTimeRanges.length > 0) {
			// convert string times to integers
			for (var i in settings.disableTimeRanges) {
				settings.disableTimeRanges[i] = [
					_time2int(settings.disableTimeRanges[i][0]),
					_time2int(settings.disableTimeRanges[i][1])
				];
			}

			// sort by starting time
			settings.disableTimeRanges = settings.disableTimeRanges.sort(function(a, b){
				return a[0] - b[0];
			});

			// merge any overlapping ranges
			for (var i = settings.disableTimeRanges.length-1; i > 0; i--) {
				if (settings.disableTimeRanges[i][0] <= settings.disableTimeRanges[i-1][1]) {
					settings.disableTimeRanges[i-1] = [
						Math.min(settings.disableTimeRanges[i][0], settings.disableTimeRanges[i-1][0]),
						Math.max(settings.disableTimeRanges[i][1], settings.disableTimeRanges[i-1][1])
					];
					settings.disableTimeRanges.splice(i, 1);
				}
			}
		}

		return settings;
	}

	function _render(self)
	{
		var settings = self.data('timepicker-settings');
		var list = self.data('timepicker-list');

		if (list && list.length) {
			list.remove();
			self.data('timepicker-list', false);
		}

		if (settings.useSelect) {
			list = $('<select />', { 'class': 'ui-timepicker-select' });
			var wrapped_list = list;
		} else {
			list = $('<ul />', { 'class': 'ui-timepicker-list' });

			var wrapped_list = $('<div />', { 'class': 'ui-timepicker-wrapper', 'tabindex': -1 });
			wrapped_list.css({'display':'none', 'position': 'absolute' }).append(list);
		}

		if (settings.noneOption) {
			if (settings.noneOption === true) {
				settings.noneOption = (settings.useSelect) ? 'Time...' : 'None';
			}

			if ($.isArray(settings.noneOption)) {
				for (var i in settings.noneOption) {
					if (parseInt(i, 10) == i){
						var noneElement = _generateNoneElement(settings.noneOption[i], settings.useSelect);
						list.append(noneElement);
					}
				}
			} else {
				var noneElement = _generateNoneElement(settings.noneOption, settings.useSelect);
				list.append(noneElement);
			}
		}

		if (settings.className) {
			wrapped_list.addClass(settings.className);
		}

		if ((settings.minTime !== null || settings.durationTime !== null) && settings.showDuration) {
			var stepval = typeof settings.step == 'function' ? 'function' : settings.step;
			wrapped_list.addClass('ui-timepicker-with-duration');
			wrapped_list.addClass('ui-timepicker-step-'+settings.step);
		}

		var durStart = settings.minTime;
		if (typeof settings.durationTime === 'function') {
			durStart = _time2int(settings.durationTime());
		} else if (settings.durationTime !== null) {
			durStart = settings.durationTime;
		}
		var start = (settings.minTime !== null) ? settings.minTime : 0;
		var end = (settings.maxTime !== null) ? settings.maxTime : (start + _ONE_DAY - 1);

		if (end < start) {
			// make sure the end time is greater than start time, otherwise there will be no list to show
			end += _ONE_DAY;
		}

		if (end === _ONE_DAY-1 && $.type(settings.timeFormat) === "string" && settings.show2400) {
			// show a 24:00 option when using military time
			end = _ONE_DAY;
		}

		var dr = settings.disableTimeRanges;
		var drCur = 0;
		var drLen = dr.length;

		var stepFunc = settings.step;
		if (typeof stepFunc != 'function') {
			stepFunc = function() {
				return settings.step;
			}
		}

		for (var i=start, j=0; i <= end; j++, i += stepFunc(j)*60) {
			var timeInt = i;
			var timeString = _int2time(timeInt, settings);

			if (settings.useSelect) {
				var row = $('<option />', { 'value': timeString });
				row.text(timeString);
			} else {
				var row = $('<li />');
				row.addClass(timeInt % 86400 < 43200 ? 'ui-timepicker-am' : 'ui-timepicker-pm');
				row.data('time', (timeInt <= 86400 ? timeInt : timeInt % 86400));
				row.text(timeString);
			}

			if ((settings.minTime !== null || settings.durationTime !== null) && settings.showDuration) {
				var durationString = _int2duration(i - durStart, settings.step);
				if (settings.useSelect) {
					row.text(row.text()+' ('+durationString+')');
				} else {
					var duration = $('<span />', { 'class': 'ui-timepicker-duration' });
					duration.text(' ('+durationString+')');
					row.append(duration);
				}
			}

			if (drCur < drLen) {
				if (timeInt >= dr[drCur][1]) {
					drCur += 1;
				}

				if (dr[drCur] && timeInt >= dr[drCur][0] && timeInt < dr[drCur][1]) {
					if (settings.useSelect) {
						row.prop('disabled', true);
					} else {
						row.addClass('ui-timepicker-disabled');
					}
				}
			}

			list.append(row);
		}

		wrapped_list.data('timepicker-input', self);
		self.data('timepicker-list', wrapped_list);

		if (settings.useSelect) {
			if (self.val()) {
				list.val(_roundAndFormatTime(_time2int(self.val()), settings));
			}

			list.on('focus', function(){
				$(this).data('timepicker-input').trigger('showTimepicker');
			});
			list.on('blur', function(){
				$(this).data('timepicker-input').trigger('hideTimepicker');
			});
			list.on('change', function(){
				_setTimeValue(self, $(this).val(), 'select');
			});

			_setTimeValue(self, list.val(), 'initial');
			self.hide().after(list);
		} else {
			var appendTo = settings.appendTo;
			if (typeof appendTo === 'string') {
				appendTo = $(appendTo);
			} else if (typeof appendTo === 'function') {
				appendTo = appendTo(self);
			}
			appendTo.append(wrapped_list);
			_setSelected(self, list);

			list.on('mousedown touchstart', 'li', function(e) {

				// hack: temporarily disable the focus handler
				// to deal with the fact that IE fires 'focus'
				// events asynchronously
				self.off('focus.timepicker');
				self.on('focus.timepicker-ie-hack', function(){
					self.off('focus.timepicker-ie-hack');
					self.on('focus.timepicker', methods.show);
				});

				if (!_hideKeyboard(self)) {
					self[0].focus();
				}

				// make sure only the clicked row is selected
				list.find('li').removeClass('ui-timepicker-selected');
				$(this).addClass('ui-timepicker-selected');

				if (_selectValue(self)) {
					self.trigger('hideTimepicker');

					list.on('mouseup.timepicker touchend.timepicker', 'li', function(e) {
						list.off('mouseup.timepicker touchend.timepicker');
						wrapped_list.hide();
					});
				}
			});
		}
	}

	function _generateNoneElement(optionValue, useSelect)
	{
		var label, className, value;

		if (typeof optionValue == 'object') {
			label = optionValue.label;
			className = optionValue.className;
			value = optionValue.value;
		} else if (typeof optionValue == 'string') {
			label = optionValue;
		} else {
			$.error('Invalid noneOption value');
		}

		if (useSelect) {
			return $('<option />', {
					'value': value,
					'class': className,
					'text': label
				});
		} else {
			return $('<li />', {
					'class': className,
					'text': label
				}).data('time', String(value));
		}
	}

	function _roundAndFormatTime(seconds, settings)
	{
		seconds = settings.roundingFunction(seconds, settings);
		if (seconds !== null) {
			return _int2time(seconds, settings);
		}
	}

	// event handler to decide whether to close timepicker
	function _closeHandler(e)
	{
		var target = $(e.target);
		var input = target.closest('.ui-timepicker-input');
		if (input.length === 0 && target.closest('.ui-timepicker-wrapper').length === 0) {
			methods.hide();
			$(document).unbind('.ui-timepicker');
			$(window).unbind('.ui-timepicker');
		}
	}

	function _hideKeyboard(self)
	{
		var settings = self.data('timepicker-settings');
		return ((window.navigator.msMaxTouchPoints || 'ontouchstart' in document) && settings.disableTouchKeyboard);
	}

	function _findRow(self, list, value)
	{
		if (!value && value !== 0) {
			return false;
		}

		var settings = self.data('timepicker-settings');
		var out = false;
		var value = settings.roundingFunction(value, settings);

		// loop through the menu items
		list.find('li').each(function(i, obj) {
			var jObj = $(obj);
			if (typeof jObj.data('time') != 'number') {
				return;
			}

			if (jObj.data('time') == value) {
				out = jObj;
				return false;
			}
		});

		return out;
	}

	function _setSelected(self, list)
	{
		list.find('li').removeClass('ui-timepicker-selected');

		var timeValue = _time2int(_getTimeValue(self), self.data('timepicker-settings'));
		if (timeValue === null) {
			return;
		}

		var selected = _findRow(self, list, timeValue);
		if (selected) {

			var topDelta = selected.offset().top - list.offset().top;

			if (topDelta + selected.outerHeight() > list.outerHeight() || topDelta < 0) {
				list.scrollTop(list.scrollTop() + selected.position().top - selected.outerHeight());
			}

			selected.addClass('ui-timepicker-selected');
		}
	}


	function _formatValue(e, origin)
	{
		if (this.value === '' || origin == 'timepicker') {
			return;
		}

		var self = $(this);

		if (self.is(':focus') && (!e || e.type != 'change')) {
			return;
		}

		var settings = self.data('timepicker-settings');
		var seconds = _time2int(this.value, settings);

		if (seconds === null) {
			self.trigger('timeFormatError');
			return;
		}

		var rangeError = false;
		// check that the time in within bounds
		if (settings.minTime !== null && seconds < settings.minTime
			&& settings.maxTime !== null && seconds > settings.maxTime) {
			rangeError = true;
		}

		// check that time isn't within disabled time ranges
		$.each(settings.disableTimeRanges, function(){
			if (seconds >= this[0] && seconds < this[1]) {
				rangeError = true;
				return false;
			}
		});

		if (settings.forceRoundTime) {
			seconds = settings.roundingFunction(seconds, settings);
		}

		var prettyTime = _int2time(seconds, settings);

		if (rangeError) {
			if (_setTimeValue(self, prettyTime, 'error')) {
				self.trigger('timeRangeError');
			}
		} else {
			_setTimeValue(self, prettyTime);
		}
	}

	function _getTimeValue(self)
	{
		if (self.is('input')) {
			return self.val();
		} else {
			// use the element's data attributes to store values
			return self.data('ui-timepicker-value');
		}
	}

	function _setTimeValue(self, value, source)
	{
		if (self.is('input')) {
			self.val(value);

			var settings = self.data('timepicker-settings');
			if (settings.useSelect && source != 'select' && source != 'initial') {
				self.data('timepicker-list').val(_roundAndFormatTime(_time2int(value), settings));
			}
		}

		if (self.data('ui-timepicker-value') != value) {
			self.data('ui-timepicker-value', value);
			if (source == 'select') {
				self.trigger('selectTime').trigger('changeTime').trigger('change', 'timepicker');
			} else if (source != 'error') {
				self.trigger('changeTime');
			}

			return true;
		} else {
			self.trigger('selectTime');
			return false;
		}
	}

	/*
	*  Filter freeform input
	*/
	function _disableTextInputHandler(e)
	{
		switch (e.keyCode) {
			case 13: // return
			case 9: //tab
				return;

			default:
				e.preventDefault();
		}
	}

	/*
	*  Keyboard navigation via arrow keys
	*/
	function _keydownhandler(e)
	{
		var self = $(this);
		var list = self.data('timepicker-list');

		if (!list || !_isVisible(list)) {
			if (e.keyCode == 40) {
				// show the list!
				methods.show.call(self.get(0));
				list = self.data('timepicker-list');
				if (!_hideKeyboard(self)) {
					self.focus();
				}
			} else {
				return true;
			}
		}

		switch (e.keyCode) {

			case 13: // return
				if (_selectValue(self)) {
					methods.hide.apply(this);
				}

				e.preventDefault();
				return false;

			case 38: // up
				var selected = list.find('.ui-timepicker-selected');

				if (!selected.length) {
					list.find('li').each(function(i, obj) {
						if ($(obj).position().top > 0) {
							selected = $(obj);
							return false;
						}
					});
					selected.addClass('ui-timepicker-selected');

				} else if (!selected.is(':first-child')) {
					selected.removeClass('ui-timepicker-selected');
					selected.prev().addClass('ui-timepicker-selected');

					if (selected.prev().position().top < selected.outerHeight()) {
						list.scrollTop(list.scrollTop() - selected.outerHeight());
					}
				}

				return false;

			case 40: // down
				selected = list.find('.ui-timepicker-selected');

				if (selected.length === 0) {
					list.find('li').each(function(i, obj) {
						if ($(obj).position().top > 0) {
							selected = $(obj);
							return false;
						}
					});

					selected.addClass('ui-timepicker-selected');
				} else if (!selected.is(':last-child')) {
					selected.removeClass('ui-timepicker-selected');
					selected.next().addClass('ui-timepicker-selected');

					if (selected.next().position().top + 2*selected.outerHeight() > list.outerHeight()) {
						list.scrollTop(list.scrollTop() + selected.outerHeight());
					}
				}

				return false;

			case 27: // escape
				list.find('li').removeClass('ui-timepicker-selected');
				methods.hide();
				break;

			case 9: //tab
				methods.hide();
				break;

			default:
				return true;
		}
	}

	/*
	*	Time typeahead
	*/
	function _keyuphandler(e)
	{
		var self = $(this);
		var list = self.data('timepicker-list');
		var settings = self.data('timepicker-settings');

		if (!list || !_isVisible(list) || settings.disableTextInput) {
			return true;
		}

		switch (e.keyCode) {

			case 96: // numpad numerals
			case 97:
			case 98:
			case 99:
			case 100:
			case 101:
			case 102:
			case 103:
			case 104:
			case 105:
			case 48: // numerals
			case 49:
			case 50:
			case 51:
			case 52:
			case 53:
			case 54:
			case 55:
			case 56:
			case 57:
			case 65: // a
			case 77: // m
			case 80: // p
			case 186: // colon
			case 8: // backspace
			case 46: // delete
				if (settings.typeaheadHighlight) {
					_setSelected(self, list);
				} else {
					list.hide();
				}
				break;
		}
	}

	function _selectValue(self)
	{
		var settings = self.data('timepicker-settings');
		var list = self.data('timepicker-list');
		var timeValue = null;

		var cursor = list.find('.ui-timepicker-selected');

		if (cursor.hasClass('ui-timepicker-disabled')) {
			return false;
		}

		if (cursor.length) {
			// selected value found
			timeValue = cursor.data('time');
		}

		if (timeValue !== null) {
			if (typeof timeValue != 'string') {
				timeValue = _int2time(timeValue, settings);
			}

			_setTimeValue(self, timeValue, 'select');
		}

		return true;
	}

	function _int2duration(seconds, step)
	{
		seconds = Math.abs(seconds);
		var minutes = Math.round(seconds/60),
			duration = [],
			hours, mins;

		if (minutes < 60) {
			// Only show (x mins) under 1 hour
			duration = [minutes, _lang.mins];
		} else {
			hours = Math.floor(minutes/60);
			mins = minutes%60;

			// Show decimal notation (eg: 1.5 hrs) for 30 minute steps
			if (step == 30 && mins == 30) {
				hours += _lang.decimal + 5;
			}

			duration.push(hours);
			duration.push(hours == 1 ? _lang.hr : _lang.hrs);

			// Show remainder minutes notation (eg: 1 hr 15 mins) for non-30 minute steps
			// and only if there are remainder minutes to show
			if (step != 30 && mins) {
				duration.push(mins);
				duration.push(_lang.mins);
			}
		}

		return duration.join(' ');
	}

	function _int2time(timeInt, settings)
	{
		if (typeof timeInt != 'number') {
			return null;
		}

		var seconds = parseInt(timeInt%60)
			, minutes = parseInt((timeInt/60)%60)
			, hours = parseInt((timeInt/(60*60))%24);

		var time = new Date(1970, 0, 2, hours, minutes, seconds, 0);

		if (isNaN(time.getTime())) {
			return null;
		}

		if ($.type(settings.timeFormat) === "function") {
			return settings.timeFormat(time);
		}

		var output = '';
		var hour, code;
		for (var i=0; i<settings.timeFormat.length; i++) {

			code = settings.timeFormat.charAt(i);
			switch (code) {

				case 'a':
					output += (time.getHours() > 11) ? _lang.pm : _lang.am;
					break;

				case 'A':
					output += (time.getHours() > 11) ? _lang.PM : _lang.AM;
					break;

				case 'g':
					hour = time.getHours() % 12;
					output += (hour === 0) ? '12' : hour;
					break;

				case 'G':
					hour = time.getHours();
					if (timeInt === _ONE_DAY) hour = settings.show2400 ? 24 : 0;
					output += hour;
					break;

				case 'h':
					hour = time.getHours() % 12;

					if (hour !== 0 && hour < 10) {
						hour = '0'+hour;
					}

					output += (hour === 0) ? '12' : hour;
					break;

				case 'H':
					hour = time.getHours();
					if (timeInt === _ONE_DAY) hour = settings.show2400 ? 24 : 0;
					output += (hour > 9) ? hour : '0'+hour;
					break;

				case 'i':
					var minutes = time.getMinutes();
					output += (minutes > 9) ? minutes : '0'+minutes;
					break;

				case 's':
					seconds = time.getSeconds();
					output += (seconds > 9) ? seconds : '0'+seconds;
					break;

				case '\\':
					// escape character; add the next character and skip ahead
					i++;
					output += settings.timeFormat.charAt(i);
					break;

				default:
					output += code;
			}
		}

		return output;
	}

	function _time2int(timeString, settings)
	{
		if (timeString === '' || timeString === null) return null;
		if (typeof timeString == 'object') {
			return timeString.getHours()*3600 + timeString.getMinutes()*60 + timeString.getSeconds();
		}
		if (typeof timeString != 'string') {
			return timeString;
		}

		timeString = timeString.toLowerCase().replace(/[\s\.]/g, '');

		// if the last character is an "a" or "p", add the "m"
		if (timeString.slice(-1) == 'a' || timeString.slice(-1) == 'p') {
			timeString += 'm';
		}

		var ampmRegex = '(' +
			_lang.am.replace('.', '')+'|' +
			_lang.pm.replace('.', '')+'|' +
			_lang.AM.replace('.', '')+'|' +
			_lang.PM.replace('.', '')+')?';

		// try to parse time input
		var pattern = new RegExp('^'+ampmRegex+'([0-9]?[0-9])\\W?([0-5][0-9])?\\W?([0-5][0-9])?'+ampmRegex+'$');

		var time = timeString.match(pattern);
		if (!time) {
			return null;
		}

		var unboundedHour = parseInt(time[2]*1, 10);
		var hour = (unboundedHour > 24) ? unboundedHour % 24 : unboundedHour;
		var ampm = time[1] || time[5];
		var hours = hour;

		if (hour <= 12 && ampm) {
			var isPm = (ampm == _lang.pm || ampm == _lang.PM);

			if (hour == 12) {
				hours = isPm ? 12 : 0;
			} else {
				hours = (hour + (isPm ? 12 : 0));
			}
		}

		var minutes = ( time[3]*1 || 0 );
		var seconds = ( time[4]*1 || 0 );
		var timeInt = hours*3600 + minutes*60 + seconds;

		// if no am/pm provided, intelligently guess based on the scrollDefault
		if (hour < 12 && !ampm && settings && settings._twelveHourTime && settings.scrollDefault) {
			var delta = timeInt - settings.scrollDefault();
			if (delta < 0 && delta >= _ONE_DAY / -2) {
				timeInt = (timeInt + (_ONE_DAY / 2)) % _ONE_DAY;
			}
		}

		return timeInt;
	}

	function _pad2(n) {
		return ("0" + n).slice(-2);
	}

	// Plugin entry
	$.fn.timepicker = function(method)
	{
		if (!this.length) return this;
		if (methods[method]) {
			// check if this element is a timepicker
			if (!this.hasClass('ui-timepicker-input')) {
				return this;
			}
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		}
		else if(typeof method === "object" || !method) { return methods.init.apply(this, arguments); }
		else { $.error("Method "+ method + " does not exist on jQuery.timepicker"); }
	};
	// Global defaults
	$.fn.timepicker.defaults = {
		appendTo: 'body',
		className: null,
		closeOnWindowScroll: false,
		disableTextInput: false,
		disableTimeRanges: [],
		disableTouchKeyboard: false,
		durationTime: null,
		forceRoundTime: false,
		maxTime: null,
		minTime: null,
		noneOption: false,
		orientation: 'l',
		roundingFunction: function(seconds, settings) {
			if (seconds === null) {
				return null;
			} else if (typeof settings.step !== "number") {
				// TODO: nearest fit irregular steps
				return seconds;
			} else {
				var offset = seconds % (settings.step*60); // step is in minutes

				if (offset >= settings.step*30) {
					// if offset is larger than a half step, round up
					seconds += (settings.step*60) - offset;
				} else {
					// round down
					seconds -= offset;
				}

				if (seconds == _ONE_DAY && settings.show2400) {
					return seconds;
				}

				return seconds%_ONE_DAY;
			}
		},
		scrollDefault: null,
		selectOnBlur: false,
		show2400: false,
		showDuration: false,
		showOn: ['click', 'focus'],
		showOnFocus: true,
		step: 30,
		stopScrollPropagation: false,
		timeFormat: 'g:ia',
		typeaheadHighlight: true,
		useSelect: false
	};
}));

$( document ).ready(function() {
    var url = document.location.toString();
    if (url.match('#')) {
        $('.nav-tabs a[href="#' + url.split('#')[1] + '"]').tab('show');
        window.scrollTo(0, 0);
    }

    $('.nav-tabs a').on('shown.bs.tab', function (e) {
        if (history.pushState) {
            history.pushState(null, null, e.target.hash);
        } else {
            window.location.hash = e.target.hash; //Polyfill for old browsers
        }
    });

    $('.ajaxfrm, .modalajaxfrm').submit(function(e) {
        e.preventDefault();
        var t = $(this);
        var postData = t.serialize();
        var action = t.data('action');
        var requestVars = t.data('requestvars');
        t.find('.btn').attr('disabled', 'disabled');
        WHMCS.http.jqClient.post("addonmodules.php?module=project_management", postData + '&' + requestVars + '&ajax=1&action=' + action, function(data) {
            if (data.status == 0) {
                t.find('.error-feedback').html(data.error).hide().removeClass('hidden').fadeIn();
            } else {
                window["ProjectManagerHandlers"][action](data);
                //t.reset();
                if (t.hasClass('modalajaxfrm')) {
                    t.find('.modal').modal('hide');
                }
            }
            t.find('.btn').removeAttr('disabled');
        }, "json");
    });

    $('.ajaxclick').click(function(e) {
        e.preventDefault();
        var t = this;
        var postData = $(t).serialize();
        var action = $(t).data('action');
        var requestVars = $(t).data('requestvars');
        WHMCS.http.jqClient.post("addonmodules.php?module=project_management", requestVars + '&ajax=1&action=' + action, function(data) {
            if (data.status == 0) {
                jQuery.growl.error(
                    {
                        title: lang.error,
                        message: data.error
                    }
                );
            } else {
                window["ProjectManagerHandlers"][action](data);
            }
        }, "json");
    });

    var standardSelectize = jQuery('.selectize-select-pm'),
        currentSelectizeValue = 0;

    if (standardSelectize.length > 0) {
    jQuery(standardSelectize).selectize(
        {
            valueField: jQuery(standardSelectize).attr('data-value-field'),
            labelField: 'name',
            searchField: jQuery(standardSelectize).attr('data-search-field').split('|'),
            allowEmptyOption: true,
            create: false,
            maxItems: 1,
            render: {
                item: function(item, escape) {
                    return '<div><span class="name">' + ((typeof item.id == 'number' || (item.id.substring(0, 1) != 'p')) ? lang.taskTemplate + ': ' : '') + escape(item.name) + '</span></div>';
                },
                option: function(item, escape) {
                    return '<div><span class="name">' + escape(item.name) + '</span>'
                        + (((typeof item.id != 'number') && (item.id.substring(0, 1) == 'p')) ? '<span class="project">' + escape(lang.projectNum) + escape(item.id.substring(1)) + '</span>' : '')
                        +'</div>';
                }
            },
            load: function(query, callback) {
                if (!query.length) return callback();
                jQuery.ajax({
                    url: window.location.pathname + window.location.search,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ajax: 1,
                        token: jQuery('#csrfToken').val(),
                        project: jQuery('#projectId').val(),
                        action: 'taskSearch',
                        search: query
                    },
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        callback(res.options);
                    }
                });
            },
            onFocus: function() {
                currentSelectizeValue = this.getValue();
                this.clear();
            },
            onBlur: function()
            {
                if (this.getValue() == '') {
                    this.setValue(currentSelectizeValue);
                }
            },
            onChange: function(value) {
                if (value.length) {
                    ProjectManager.call(
                        jQuery(standardSelectize).attr('data-pm-action'),
                        'selected=' + value
                    );
                }
            }
        }
    );
    }
});

var ProjectManager = {

    call: function (action, requestVars) {
        var error = '';
        var result = false;
        WHMCS.http.jqClient.post("addonmodules.php?module=project_management", requestVars + '&projectid=' + $('#projectId').val() + '&ajax=1&action=' + action + '&token=' + $('#csrfToken').val(), function(data) {
            if (data.status == 0) {
                error = data.error;
            } else {
                window["ProjectManagerHandlers"][action](data);
            }
        }, "json");
    },

    confirm: function (action, requestVars) {
        jQuery('#modalConfirmMsg').html(lang[action]);
        $('#frmModalConfirm').data('action', action).data('requestvars', requestVars);
        $('#modalConfirm').modal('show').find('.btn-success').focus();
    }

};

function decodeHtml(html) {
    return $('<div>').html(html).text();
}

$( document ).ready(function() {
    $('#predefinedFilters a').click(function(e) {
        e.preventDefault();
        $('#predefinedFilters a').removeClass('active');
        $(this).addClass('active');
        $('#inputPredefinedFilter').val($(this).data('filter'));
    })
});

$( document ).ready(function() {
    $("#inputWatch").bootstrapSwitch({
        size: 'small',
        onColor: 'success',
        labelText: lang.watching
    }).on('switchChange.bootstrapSwitch', function(event, state) {
        if (state) {
            ProjectManager.call('watch', '');
        } else {
            ProjectManager.call('unwatch', '');
        }
    });

    $(document).on('click', '.task-status-indicator', function() {
        var taskId = $(this).parent('td').parent('tr').attr('id');
        ProjectManager.call('taskstatustoggle', 'taskid=' + taskId.substr(5));
    });

    $(document).on('click', '.task-edit', function() {
        var taskId = $(this).parent('div').parent('td').parent('tr').attr('id');
        ProjectManager.call('gettaskinfo', 'taskid=' + taskId.substr(5));
        $('#modalTaskEdit').modal('show');
    });

    $(document).on('click', '.task-delete', function() {
        var taskId = $(this).parent('div').parent('td').parent('tr').attr('id');
        ProjectManager.confirm('deletetask', 'taskid=' + taskId.substr(5));
    });

    $(document).on('click', '.task-delete-button', function() {
        var taskId = $('#inputTaskId').val();
        $('#modalTaskEdit').modal('hide');
        ProjectManager.confirm('deletetask', 'taskid=' + taskId);
    });

    $(document).on('click', '.timer-edit', function() {
        var timerId = $(this).parent('td').parent('tr').attr('id');
        ProjectManager.call('gettimerinfo', 'timerid=' + timerId.substr(6));
        $('#modalEditTimer').modal('show');
    });

    $(document).on('click', '.timer-delete', function() {
        var timerId = $(this).data('timer-id');
        ProjectManager.confirm('deleteTimer', 'timerId=' + timerId);
    });

    $(document).on('click', '.ticket-result', function(e) {
        e.preventDefault();
        var ticketTid = $(this).attr('data-ticket-tid');
        ProjectManager.call('addticket', 'ticketmask=' + ticketTid);
    });

    $(document).on('click', '.invoice-result', function(e) {
        e.preventDefault();
        var invoiceId = $(this).attr('data-invoice-id');
        ProjectManager.call('addInvoice', 'invoice=' + invoiceId);
    });

    $(document).on('click', '.unlink-ticket', function() {
        ProjectManager.call('unlinkTicket', 'ticketmask=' + $(this).data('ticket-tid'));
    });

    $(document).on('click', '.view-ticket', function() {
        var child = window.open();
        child.opener = null;
        child.location = 'supporttickets.php?action=view&id=' + $(this).data('ticket-id');
    });

    $(document).on('click', '.unlink-invoice', function() {
        ProjectManager.call('unlinkInvoice', 'invoice=' + $(this).data('invoice-id'));
    });

    $(document).on('click', '.view-invoice', function() {
        var child = window.open();
        child.opener = null;
        child.location = 'invoices.php?action=edit&id=' + $(this).data('invoice-id');
    });

    $('#btnStartTimer').click(function() {
        $(this).prop('disabled', true);
        $(this).find('i').toggleClass('fa-clock fa-spinner fa-spin');
        ProjectManager.call('starttimer', '');
    });

    $('#btnEndTimer').click(function() {
        $(this).prop('disabled', true);
        $(this).find('i').toggleClass('fa-clock fa-spinner fa-spin');
        ProjectManager.call('endtimer', 'timerid=' + $(this).data('timerid'));
    });

    if (typeof Sortable !== "undefined" && $('#adminList').length) {

        Sortable.create(
            adminList,
            {
                group: {
                    name: 'adminSort',
                    pull: 'clone',
                    put: false
                },
                sort: false,
                ghostClass: 'ghost'
            }
        );

    }

    jQuery('span[id^="assigned-admin-task-"]').each(function(index, group) {
        Sortable.create(
            group,
            {
                group: {
                    name: 'admin' + index,
                    pull: false,
                    put: ['adminSort']
                },
                sort: false,
                ghostClass: 'ghost',
                onAdd: function(evt) {
                    jQuery(evt.target).html(evt.item);

                    ProjectManager.call(
                        'assigntask',
                        'taskid=' + jQuery(evt.target).data('id')
                            + '&admin=' + jQuery(evt.item).data('id')
                    );
                }
            }
        );
    });

    jQuery('#formSaveProject, #inputDueDateDiv, #dueDatePicker').find('.date').datepicker({
        dateFormat: datepickerformat,
        showOn: "both",
        buttonImage: "images/showcalendar.gif",
        buttonImageOnly: true,
        showButtonPanel: true,
        showOtherMonths: true,
        selectOtherMonths: true
    });

    if (typeof Sortable !== "undefined" && $('#dueDatePicker').length) {
        Sortable.create(
            dueDatePicker,
            {
                group: {
                    name: 'dueDateSelect',
                    pull: 'clone',
                    put: false,
                    revertClone: true
                },
                sort: false,
                ghostClass: 'ghost',
                handle: '.handle'
            }
        );
    }

    jQuery('span[id^="task-due-date-"]').each(function(index, group) {
        Sortable.create(
            group,
            {
                group: {
                    name: 'due-date-' + index,
                    pull: false,
                    put: ['dueDateSelect']
                },
                sort: false,
                ghostClass: 'ghost',
                onAdd: function(evt) {
                    jQuery(evt.target).html('<span class="label label-assigned-user">' + lang.updating + '...</span>');
                    ProjectManager.call(
                        'taskduedate',
                        'taskid=' + jQuery(evt.target).data('id')
                        + '&duedate=' + jQuery(evt.item).find('input').val()
                    );

                    jQuery('#dueDatePicker').find('.ui-datepicker-trigger').remove().end()
                        .find('.date').removeClass('hasDatepicker').datepicker({
                        dateFormat: datepickerformat,
                        showOn: "both",
                        buttonImage: "images/showcalendar.gif",
                        buttonImageOnly: true,
                        showButtonPanel: true,
                        showOtherMonths: true,
                        selectOtherMonths: true
                    });

                }
            }
        );
    });

    jQuery('.icheck-button').each(function() {
        var self = jQuery(this);
        self.iCheck({
            checkboxClass: 'icheckbox_flat-blue',
            radioClass: 'iradio_flat-blue'
        });
    });

    var toggleCompleteHide = jQuery('#toggleCompleteHide');
    toggleCompleteHide.on('ifChecked', function(event){
        jQuery('.task-line-item-completed').each(function() {
            jQuery(this).children().toggle('highlight');
        });
    });

    toggleCompleteHide.on('ifUnchecked', function(event){
        jQuery('.task-line-item-completed').each(function() {
            jQuery(this).children().toggle('highlight');
        });
    });

    Dropzone.options.myProjectUploads = {
        maxFilesize: maximumFileSize, // MB
        addRemoveLinks: true,
        init: function() {
            this.on("sending", function(file, xhr, formData) {
                // Will send the filesize along with the file as POST data.
                formData.append("filesize", file.size);
            });
            this.on('success', function(file, response) {
                response = jQuery.parseJSON(response);
                if (response.status != "1") {
                    jQuery.growl.error(
                        {
                            title: lang.error,
                            message: response.error
                        }
                    );
                } else {
                    jQuery('#fileCount').html(response.fileCount);
                    var className;
                    if (response.isImage) {
                        className = 'lightbox';
                    }
                    if (response.browserViewable) {
                        className = ''
                    }

                    appendFile(response.key, response);
                    this.removeFile(file);
                    jQuery.growl.notice(
                        {
                            title: lang.success,
                            message: lang.fileUploadSuccess
                        }
                    );
                }
            });
            this.on('error', function(file, error, xhr) {
                jQuery.growl.error(
                    {
                        title: lang.error,
                        message: error
                    }
                );
            });
        }
    };

    var counter = 0;
    if (jQuery("#newTicketMessage").length > 0) {
    newProjectTicket = jQuery("#newTicketMessage").markdown(
        {
            footer: '<div id="newTicketMessage-footer" class="markdown-editor-status"></div>',
            autofocus: false,
            savable: false,
            resize: 'vertical',
            iconlibrary: 'glyph',
            onShow: function(e){
                var content = '',
                    save_enabled = false;
                if(typeof(Storage) !== "undefined") {
                    // Code for localStorage/sessionStorage.
                    content = localStorage.getItem("project-" + jQuery('#projectId').val() + "-new-ticket");
                    save_enabled = true;
                    if (content && typeof(content) !== "undefined") {
                        e.setContent(content);
                    }
                }
                jQuery("#newTicketMessage-footer").html(parseMdeFooter(content, save_enabled, lang.saved));
            },
            onChange: function(e){
                var content = e.getContent(),
                    save_enabled = false;
                if(typeof(Storage) !== "undefined") {
                    counter = 3;
                    save_enabled = true;
                    localStorage.setItem("project-" + jQuery('#projectId').val() + "-new-ticket", content);
                    doCountdown();
                }
                jQuery("#newTicketMessage-footer").html(parseMdeFooter(content, save_enabled));
            },
            onPreview: function(e){
                var originalContent = e.getContent(),
                    parsedContent;

                jQuery.ajax({
                    url: window.location.pathname + window.location.search,
                    async: false,
                    data: {
                        token: jQuery('#csrfToken').val(),
                        action: 'parseMarkdown',
                        content: originalContent,
                        ajax: 1,
                        projectId: jQuery('#projectId').val()
                    },
                    dataType: 'json',
                    success: function (data) {
                        parsedContent = data;
                    }
                });

                return parsedContent.body ? parsedContent.body : '';
            },
            additionalButtons: [
                [{
                    name: "groupCustom",
                    data: [{
                        name: "cmdHelp",
                        title: lang.help,
                        hotkey: "Ctrl+F1",
                        btnClass: "btn open-modal",
                        href: "supporttickets.php?action=markdown",
                        icon: {
                            glyph: 'fas fa-question-circle',
                            fa: 'fa fa-question-circle',
                            'fa-3': 'icon-question-sign'
                        },
                        callback: function(e) {
                            e.$editor.removeClass("md-fullscreen-mode");
                        },
                        additionalAttr: [
                            {
                                name: 'data-modal-title',
                                value: lang.markdownGuide
                            },
                            {
                                name: 'data-modal-size',
                                value: 'modal-lg'
                            }
                        ]
                    }]
                }]
            ]
        }
    );
    }

    /**
     * Parse the content to populate the markdown editor footer.
     *
     * @param {string} content
     * @param {bool} auto_save
     * @param {string} [saveText]
     * @returns {string}
     */
    function parseMdeFooter(content, auto_save, saveText)
    {
        saveText = saveText || lang.saving;
        var pattern = /[^\s]+/g,
            m = [],
            word_count = 0,
            line_count = 0;
        if (content) {
            m = content.match(pattern);
            line_count = content.split(/\\r\\n|\\r|\\n/).length;
        }
        if (m) {
            for (var i = 0; i < m.length; i++) {
                if (m[i].charCodeAt(0) >= 0x4E00) {
                    word_count += m[i].length;
                } else {
                    word_count += 1;
                }
            }
        }
        return '<div class="small-font">' + lang.lines + ': ' + line_count
            + '&nbsp;&nbsp;&nbsp;' + lang.words + ': ' + word_count + ''
            + (auto_save ? '&nbsp;&nbsp;&nbsp;<span class="markdown-save">' + saveText + '</span>' : '')
            + '</div>';
    }

    /**
     * Countdown the save timeout. When zero, the span will update to show saved.
     */
    function doCountdown()
    {
        if (counter >= 0) {
            if (counter == 0) {
                jQuery("span.markdown-save").html('saved');
            }
            counter--;
            setTimeout(doCountdown, 1000);
        }
    }

    jQuery('#associatedTicketSearch').on('keydown', function(e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            if (jQuery(this).val().length < 3) {
                return;
            }
            jQuery('#associatedTicketResults').find('.ticket').hide().remove();
            jQuery('#resultInfo').fadeIn('fast');
            ProjectManager.call(
                'searchTickets',
                'search=' + jQuery(this).val()
            );
        }

    });

    jQuery('#modalAssociateInvoice').on('show.bs.modal', function() {
        jQuery('#associatedInvoiceResults').find('.invoice').hide().remove();
        jQuery('#invoiceResultInfo').fadeIn('fast');
        ProjectManager.call(
            'searchInvoices',
            'search=0'
        );
    });

    jQuery('#associatedInvoiceSearch').on('keydown', function(e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            if (jQuery(this).val().length < 1) {
                return;
            }
            jQuery('#associatedInvoiceResults').find('.invoice').hide().remove();
            jQuery('#invoiceResultInfo').fadeIn('fast');
            ProjectManager.call(
                'searchInvoices',
                'search=' + jQuery(this).val()
            );
        }

    });

    jQuery('#btnMainUploadFile').on('click', function() {
        jQuery('#myProjectUploads').click();
    });

    jQuery('#addMessage').on('submit', function(e) {
        e.preventDefault();
        var file = jQuery('#addMessageFiles'),
            message = jQuery('#inputAddMessage'),
            form = jQuery(this);

        form.find('.btn').attr('disabled', 'disabled');

        if (!message.val()) {
            jQuery(this).find('.error-feedback')
                .html(lang.messageRequired)
                .hide()
                .removeClass('hidden')
                .fadeIn()
                .end();
            form.find('.btn').removeAttr('disabled');
            return false;
        }
        if (file.val()) {
            file.off('filebatchuploaderror');
            file.off('filebatchuploadsuccess');
            file.on('filebatchuploaderror', function(event, data, message) {
                data.form.find('.error-feedback').html(message).hide().removeClass('hidden').fadeIn();
                data.form.find('.btn').removeAttr('disabled');
            });
            file.on('filebatchuploadsuccess', sendAddMessagePost);
            file.fileinput('upload');
        } else {
            sendAddMessagePost();
        }
    });

    function sendAddMessagePost(event, data)
    {
        var form = jQuery('#addMessage'),
            postData = form.serialize(),
            action = form.data('action');

        if (typeof data !== 'undefined' && data.response.newFiles.length) {
            jQuery.each(data.response.newFiles, function (index, value) {
                postData += '&fileId[]=' + value;
            });
        }

        WHMCS.http.jqClient.post("addonmodules.php?module=project_management", postData + '&' + '&ajax=1&action=' + action, function(data) {
            if (data.status === 0) {
                form.find('.error-feedback').html(data.error).hide().removeClass('hidden').fadeIn();
            } else {
                window["ProjectManagerHandlers"][action](data);
            }
            form.find('.btn').removeAttr('disabled');
        }, "json");
    }

    jQuery('#btnInvoiceSelected').on('click', function() {
        var timers = jQuery('table.timers').find('input:checked[name="timerId[]"]');
        if (timers.length === 0) {
            jQuery.growl.error(
                {
                    title: lang.error,
                    message: lang.selectTimers
                }
            );
            return false;
        }
        jQuery(this)
            .prop('disabled', true)
            .find('i')
            .toggleClass('fa fa-spinner fa-spin');

        var requestVars = '';
        timers.each(function () {
            requestVars += 'timerId[]=' + jQuery(this).val() + '&';
        });


        var timersToInvoice = jQuery('#timersToInvoice'),
            modalId = jQuery('#modalInvoiceItems');
        timersToInvoice.html('');
        WHMCS.http.jqClient.post(
            'addonmodules.php?module=project_management',
            requestVars + 'ajax=1&action=prepareInvoiceTimers&projectid=' + jQuery('#iIProjectId').val() + '&token=' + jQuery('#csrfToken').val() + '&rate=' + jQuery('#defaultRate').val(),
            function(data) {
                if (data.status === 0) {
                    modalId.find('.error-feedback')
                        .html(data.error)
                        .hide()
                        .removeClass('hidden')
                        .fadeIn();
                } else {
                    modalId.find('.error-feedback')
                        .html('')
                        .hide();
                    jQuery.each(data.times, function(key, time) {
                        var newTimer = jQuery('#timersToInvoiceSample').clone();
                        newTimer.find('.description')
                            .attr('name', 'description[' + key + ']')
                            .val(time.description)
                            .end()
                            .find('.hours')
                            .attr('name', 'hours[' + key + ']')
                            .attr('id', 'invoiceItemsHours' + key)
                            .val((time.seconds/3600).toFixed(2))
                            .end()
                            .find('.displayHours')
                            .attr('name', 'displayHours[' + key + ']')
                            .attr('data-key', key)
                            .attr('id', 'invoiceItemsDisplayHours' + key)
                            .val(time.hours)
                            .end()
                            .find('.itemRate')
                            .attr('name', 'rate[' + key + ']')
                            .attr('data-key', key)
                            .attr('id', 'invoiceItemsRate' + key)
                            .val(time.rate)
                            .end()
                            .find('.invoiceAmount')
                            .attr('name', 'amount[' + key + ']')
                            .attr('data-key', key)
                            .attr('id', 'invoiceItemsAmount' + key)
                            .val(time.amount)
                            .end()
                            .find('span.currency')
                            .html(data.currency.prefix)
                            .end()
                            .find('span.currency-suffix')
                            .html(data.currency.suffix)
                            .end();
                        timersToInvoice.append(newTimer.children('div'));
                    });
                }
                jQuery('#formInvoiceItems').data('requestvars', requestVars);
                modalId.modal('show').find('.btn-success').focus();
            },
            'json'
        );
    });

    jQuery('#modalInvoiceItems').on('hidden.bs.modal', function() {
        jQuery('#btnInvoiceSelected')
            .prop('disabled', false)
            .find('i')
            .toggleClass('fa fa-spinner fa-spin');
    });
    jQuery('#modalSaveProject').on('show.bs.modal', function() {
        jQuery(this).find('input[name="title"]').focus();
    });


    jQuery(document).on('keyup', '.displayHours', function(){
        var hms = jQuery(this).val().split(":"),
            hours = Number(hms[0]) + Number(hms[1] / 60) + Number(hms[2] / 3600),
            id = jQuery(this).data('key'),
            hoursFloat = jQuery("#invoiceItemsHours" + id);
        hoursFloat.val(hours);
        var amount = hoursFloat.val() * jQuery("#invoiceItemsRate" + id).val();
        jQuery("#invoiceItemsAmount" + id).val(amount.toFixed(2));
    }).on('keyup', '.itemRate', function() {
        var id = jQuery(this).data('key'),
            hours = jQuery("#invoiceItemsHours" + id).val(),
            rate = jQuery(this).val();
        jQuery("#invoiceItemsAmount" + id).val(parseFloat(hours * rate).toFixed(2));
    }).on('click', '.message-file', function(e) {
        e.preventDefault();
        var key = jQuery(this).data('key');
        document.getElementById('fileView' + key).click();
    });

    if (jQuery('#tableLog').length > 0) {
    jQuery('#tableLog').DataTable({
        'dom': '<"listtable"fit>pl',
        'info': false,
        'filter': true,
        'responsive': true,
        'oLanguage': {
            'sEmptyTable': lang.noRecordsFound,
            'sInfo': lang.tableShowingXOfY,
            'sInfoEmpty': lang.tableShowingEmpty,
            'sInfoFiltered': lang.tableFiltered,
            'sInfoPostFix': "",
            'sInfoThousands': ',',
            'sLengthMenu': lang.tableMenuLength,
            'sLoadingRecords': lang.tableLoading,
            'sProcessing': lang.tableProcessing,
            'sSearch': "",
            'sZeroRecords': lang.noRecordsFound,
            'oPaginate': {
                'sFirst': lang.tableFirst,
                'sLast': lang.tableLast,
                'sNext': lang.tableNext,
                'sPrevious': lang.tablePrevious
            }
        },
        'pageLength': 10,
        'order': [[0, 'desc']],
        'lengthMenu': [
            [10, 25, 50, -1],
            [10, 25, 50, lang.all]
        ],
        'aoColumnDefs': [
            {
                'bSortable': false,
                'aTargets': [1, 2]
            }
        ],
        "columns": [
            { "width": "15%" },
            { "width": "65%" },
            null
        ],
        'stateSave': true
    });
    jQuery('.dataTables_filter input').attr('placeholder', lang.enterSearch);
    }

    jQuery("#addMessageFiles").fileinput({
        maxFileSize: maximumFileSize * 1024,
        showUpload: false,
        allowedPreviewTypes: ['image', 'html', 'text', 'pdf'],
        showCaption: true,
        initialCaption: lang.multipleFiles,
        overwriteInitial: false,
        uploadUrl: 'addonmodules.php?module=project_management&action=uploadFileForMessage&ajax=1&projectid=' + jQuery('#projectId').val() + '&token=' + jQuery('#csrfToken').val(),
        uploadAsync: false,
        dropZoneEnabled: false
    });

    jQuery('#btnAddComment').on('click', function() {
        jQuery('#tabMessages').find('a').click();
        jQuery('#inputAddMessage').focus();
    });
});

function appendFile(key, attachment)
{
    var projectId = jQuery('#projectId').val();
    jQuery('#fileList').append(
        '<div class="file" id="file-' + key + '">\
    <div class="pull-right">\
        <a id="fileView' + key + '" type="button" class="btn btn-default file-view" href="addonmodules.php?module=project_management&amp;action=dl&amp;projectid=' + projectId + '&amp;i=' + key + '&amp;msg=' + attachment.messageId + (attachment.browserViewable ? '&amp;view=inline" target="_blank"' : '"') + (attachment.isImage ? ' data-lightbox="project-image-' + projectId + '"' : '') + '>\
            <i class="fas fa-search"></i>\
            ' + lang.view + '\
        </a>\
        <a id="fileDownload' + key + '" href="addonmodules.php?module=project_management&amp;action=dl&amp;projectid=' + projectId + '&amp;i=' + key + '&amp;msg=' + attachment.messageId + '" class="btn btn-default">\
            ' + lang.download + '\
        </a>\
        <button type="button" class="btn btn-danger" onclick="ProjectManager.confirm(\'deletefile\', \'num=' + key + '\')">\
            ' + lang['delete'] + '\
        </button>\
    </div>\
    <span class="title">' + attachment.filename + '</span><span class="extension">' + attachment.extension + '</span>\
    <span class="info">' + lang.by + ' <strong>' + attachment.admin + '</strong> x ' + lang.justNow + ' X ' + attachment.filesize + '</span>\
</div>'
    );
}

function appendTicket(ticket)
{
    jQuery('#noTickets').hide();
    jQuery('#tickets').find('.tickets').append(
        '<div class="ticket" id="ticket-' + ticket.id + '">' +
        '<div class="pull-right">' +
        '<button type="button" class="btn btn-default view-ticket" data-ticket-id="' + ticket.id + '">'
        + '<i class="fas fa-search"></i> ' + lang.view + '</button> ' +
        '<button type="button" class="btn btn-danger unlink-ticket" data-ticket-tid="' + ticket.tid + '">' + lang.unlink + '</button>' +
        '</div>' +
        '<span class="ticketnum">#' + ticket.tid + '</span>' +
        '<span class="subject"> ' + ticket.title +
        ' <span class="label" style="background-color: ' + ticket.statusColour + '; color: ' + ticket.statusTextColour + ';">' + ticket.status + '</span></span>' +
        '<span class="info">' + lang.ticketUser + ':' + ticket.userDetails + '<br>' +
        lang.department + ': ' + ticket.departmentName + '<br>' +
        lang.lastReplyBy + ' <strong>' + ticket.lastReplyUser +'</strong>' +
        (ticket.isAdminReply ? ' (' + lang.staffMember + ') - ' : ' - ') + ticket.lastreply + '</span>' +
        '</div>'
    );
}

function appendInvoice(invoice)
{
    jQuery('#noInvoices').hide();
    jQuery('#invoices').append(
        '<tr id="invoice-' + invoice.id + '" class="invoice">' +
        '<td class="invoice-num">' + lang.invoiceNum + (invoice.invoiceNumber ? invoice.invoiceNumber : invoice.id) + '</td>' +
        '<td class="invoice-data">' + invoice.dateCreated + ' </td>' +
        '<td class="invoice-data">' + invoice.dateDue + ' </td>' +
        '<td class="invoice-data">' + invoice.total + '</td>' +
        '<td class="invoice-data">' + invoice.balance + '</td>' +
        '<td><span class="label ' + invoice.status.toLowerCase() + '">' + invoice.status + '</span></td>' +
        '<td class="text-right">' +
        '<button type="button" class="btn btn-default view-invoice" data-invoice-id="' + invoice.id + '">' +
        '<i class="fas fa-search"></i> ' + lang.view + '</button> ' +
        '<button type="button" class="btn btn-danger unlink-invoice" data-invoice-id="' + invoice.id + '"> ' + lang.unlink + ' </button>' +
        '</td></tr>'
    );
}

var newProjectTicket;

var ProjectManagerHandlers = {

    addmessage: function (data) {
        var newMessage = data.newMessage[0],
            attached = '',
            projectId = jQuery('#projectId').val(),
            deleteMessagePerm = data.deletePermission,
            deleteButton = '';
        if (newMessage.attachment) {
            jQuery.each(newMessage.attachment, function (index, attachment) {
                attached = attached + '<a href="#" class="message-file" data-key="'+ index + '">' + attachment.displayFilename + '</a>';
                appendFile(index, attachment);
            });
        }
        if (deleteMessagePerm) {
            deleteButton = '<div class="pull-right"><input type="button" value="Delete" class="btn btn-danger btn-xs" onclick="ProjectManager.confirm(\'deletemsg\', \'msgid=' + newMessage.id + '\')"></div>';
        }

        jQuery('.messages').prepend('<div class="message hidden" id="message-' + newMessage.id + '"><div class="user-gravatar"><img src="' + newMessage.gravatarUrl + '"></div><div class="number">' + newMessage.number + '.</div><div class="content"><span class="user">' + newMessage.name + '</span><span class="date">' + newMessage.date + '</span>' + deleteButton + '<span class="msg">' + newMessage.message + '</span>' + attached + '</div></div>');
        jQuery('#message-' + newMessage.id).hide().removeClass('hidden').slideDown();
        jQuery('#addMessage').find('.fileinput-remove').click();
        jQuery('#inputAddMessage').val('').focus();
        jQuery('#tabMessages').find('span').html(data.messageCount);
        jQuery('#fileCount').html(data.fileCount);
    },

    addtask: function (data) {
        var newTask = data.newTask[0],
            assigned = '<span id="assigned-admin-task-' + newTask.id +  '" class="assigned-admin" data-id="' + newTask.adminId + '">' + newTask.assigned + '</span>',
            dueDate = '<span id="task-due-date-' + newTask.id +  '" data-id="' + newTask.id +  '" class="task-due-date"> ' + newTask.duedate + ' </span>',
            editTasksPermission = data.editPermission,
            deleteTasksPermission = data.deletePermission,
            actionButtons = '';

        if (editTasksPermission) {
            actionButtons = '<i class="task-edit far fa-pencil-alt"></i>';
        }
        if (deleteTasksPermission) {
            if (actionButtons !== '') {
                actionButtons = actionButtons + '&nbsp;';
            }
            actionButtons = actionButtons + '<i class="task-delete far fa-trash-alt"></i>';
        }
        jQuery('#noTasks').hide();
        jQuery('.tasks').append('<tr class="task-line-item hidden" id="task-' + newTask.id + '"><td><i class="task-status-indicator far fa-check-circle"></i><span class="description"> ' + decodeHtml(newTask.task) + '</span> ' + assigned + dueDate + '<div class="actions">' + actionButtons + '</div><span id="total-time-task-' + newTask.id + '" class="pull-right label label-assigned-user total-time" data-task-id="' + newTask.id + '">00:00:00</span><br /><span class="text-grey">' + decodeHtml(newTask.notes) + '</span></td></tr>');

        Sortable.create(
            document.getElementById('assigned-admin-task-' + newTask.id),
            {
                group: {
                    name: 'admin' + newTask.id,
                    pull: false,
                    put: ['adminSort']
                },
                sort: false,
                ghostClass: 'ghost',
                onAdd: function(evt) {
                    jQuery(evt.target).html(evt.item);

                    ProjectManager.call(
                        'assigntask',
                        'taskid=' + jQuery(evt.target).data('id')
                        + '&admin=' + jQuery(evt.item).data('id')
                    );
                }
            }
        );

        Sortable.create(
            document.getElementById('task-due-date-' + newTask.id),
            {
                group: {
                    name: 'due-date-' + newTask.id,
                    pull: false,
                    put: ['dueDateSelect']
                },
                sort: false,
                ghostClass: 'ghost',
                onAdd: function(evt) {
                    jQuery(evt.target).html('<span class="label label-assigned-user">Updating...</span>');
                    ProjectManager.call(
                        'taskduedate',
                        'taskid=' + jQuery(evt.target).data('id')
                        + '&duedate=' + jQuery(evt.item).data('id')
                    );
                }
            }
        );

        jQuery('#task-' + newTask.id).hide().removeClass('hidden').slideDown();
        jQuery('#inputAddTask, #inputAddDueDate').val('');
        jQuery('#inputAssignId').val(0);
        jQuery('#totalTasks').html(data.summary.total);
        jQuery('#completedTasks').html(data.summary.completed);

        jQuery('#inputTaskAssignment').append(jQuery('<option></option>', {
            value: newTask.id,
            text: newTask.task
        }));

        jQuery('#editTimerTaskId').append(jQuery('<option></option>', {
            value: newTask.id,
            text: newTask.task
        }));
    },

    deletetask: function (data) {
        jQuery('#task-' + data.deletedTaskId).fadeOut('fast', function() {
            if (data.summary.total === "0") {
                jQuery('#noTasks').hide().removeClass('hidden').show();
            }
        });
        jQuery('#totalTasks').html(data.summary.total);
        jQuery('#completedTasks').html(data.summary.completed);
        jQuery('#inputTaskAssignment').find("option[value='" + data.deletedTaskId + "']").remove();
        jQuery('#editTimerTaskId').find("option[value='" + data.deletedTaskId + "']").remove();
    },

    taskstatustoggle: function (data) {
        var taskLine = $('#task-' + data.taskId);
        if (data.isCompleted == 1) {
            taskLine.addClass('task-line-item-completed');
            if ($('#toggleCompleteHide').is(':checked')) {
                taskLine.addClass('hidden');
            }
        } else {
            taskLine.removeClass('task-line-item-completed');
        }
        jQuery('#totalTasks').html(data.summary.total);
        jQuery('#completedTasks').html(data.summary.completed);
    },

    taskedit: function (data) {
        var task = data.task[0],
            taskRow = $('#task-' + task.id).find('td');
        taskRow.find('.description').html(decodeHtml(task.task));
        taskRow.find('.assigned-admin').html(task.assigned);
        taskRow.find('.task-due-date').html(task.duedate);
        taskRow.find('.task-notes').html(decodeHtml(task.notes));
    },

    gettaskinfo: function (data) {
        var task = data.task[0];
        $('#inputTaskId').val(task.id);
        $('#inputTaskTitle').val(decodeHtml(task.task));
        $('#inputTaskNotes').val(decodeHtml(task.notes));
        $('#inputTaskEditAdminAssignment').val(task.adminId);
        $('#inputTaskDue').val(task.rawDueDate);
    },

    deletemsg: function (data) {
        jQuery('#message-' + data.deletedMessageId).fadeOut();
        jQuery('#tabMessages').find('span').html(data.messageCount);
        jQuery.each(data.deletedFiles, function(index, value) {
            jQuery('#file-' + value).fadeOut();
        });
        jQuery('#fileCount').html(data.fileCount);
    },

    deletefile: function (data) {
        jQuery('#file-' + data.deletedFileNumber).fadeOut();
        jQuery('a.message-file[data-key="' + data.deletedFileNumber + '"]').remove();
        jQuery('#fileCount').html(data.fileCount);
    },

    addticket: function (data) {
        if (data.status == "0") {
            jQuery('#frmAddTicket').find('.error-feedback').html(data.error).hide().removeClass('hidden').fadeIn();
        } else {
            appendTicket(data.ticket);

            jQuery('#ticketCount').text(data.ticketCount);

            jQuery.growl.notice(
                {
                    title: lang.success,
                    message: lang.ticketAssociated.replace(':tid', data.ticket.tid)
                }
            );
            jQuery('#frmAddTicket').find('.modal').modal('hide');
            jQuery('#associatedTicketResults').find('.ticket').remove();
            jQuery('#resultInfo').fadeIn('fast');
        }
    },

    openticket: function (data) {
        if (data.status == "0") {
            jQuery.growl.error(
                {
                    title: lang.error,
                    message: data.error
                }
            );
        } else {
            appendTicket(data.ticket);
            jQuery('#modalOpenTicket').find('input').not('#newTicketClientRO').each(function() {
                jQuery(this).val('');
            });
            jQuery('#newTicketContact').val('0');
            if(typeof(Storage) !== "undefined") {
                // Code for localStorage/sessionStorage.
                localStorage.removeItem("project-" + jQuery('#projectId').val() + "-new-ticket");
            }
            newProjectTicket[0].value = '';

            jQuery('#ticketCount').text(data.ticketCount);

            jQuery.growl.notice(
                {
                    title: lang.success,
                    message: lang.ticketCreated.replace(':tid', data.ticket.tid)
                }
            );
        }
    },

    starttimer: function (data) {
        $('#btnStartTimer').hide().find('i').toggleClass('fa-clock fa-spinner fa-spin');
        $('#btnEndTimer').removeProp('disabled').removeClass('hidden').show().data('timerid', data.newTimerId);
        var timer = data.newTimer[0];
        $('.timers').append('<tr id="timer-' + timer.id + '"><td><input title="Timer ' + timer.id + '" type="checkbox" name="timerId[]" value="' + timer.id + '" /></td><td>' + timer.date + '</td><td>' + timer.adminName + '</td><td>' + timer.taskName + '</td><td>' + timer.startTime + '</td><td class="timer-end-time">' + timer.endTime + '</td><td><i class="fas fa-times-circle"></i></td><td class="timer-duration">' + timer.duration + '</td><td><a href="#" class="timer-edit"><i class="fas fa-pencil-alt"></i></a></td><td></td></tr>');
        $('#timer-' + timer.id).find('input[type="checkbox"]').prop('disabled', true).addClass('disabled');
        jQuery('#noTimers').hide();
    },

    endtimer: function (data) {
        $('#btnStartTimer').removeProp('disabled').removeClass('hidden').show();
        $('#btnEndTimer').hide().find('i').toggleClass('fa-clock fa-spinner fa-spin');
        var timerId = data.endedTimerId,
            timerRow = $('#timer-' + timerId),
            timer = data.timer[0],
            taskSpan = $('#total-time-task-' + timer.taskId);

        timerRow.find('.timer-end-time').html(timer.endTime);
        timerRow.find('.timer-duration').html(timer.duration);
        timerRow.find('input[type="checkbox"]').prop('disabled', false).removeClass('disabled');
        if (timer.taskId && taskSpan.length) {
            taskSpan.html(timer.totalTaskTime);
        }
        $('#time-logged').html(data.totalTime);
        $('#time-billed').html(data.totalBilled);

    },

    deleteTimer: function(data) {
        var timerId = data.deletedTimerId,
            timerRow = $('#timer-' + timerId);

        $(timerRow).fadeOut('fast', function() {
            if (!data.totalCount) {
                $('#noTimers').hide().removeClass('hidden').show();
            }
            if (!data.openTimerId) {
                $('#btnStartTimer').removeProp('disabled').removeClass('hidden').show();
                $('#btnEndTimer').hide();
            }
        });

        $('#time-logged').html(data.totalTime);
        $('#time-billed').html(data.totalBilled);
    },

    taskduedate: function(data) {
        if (data.status == "0") {
            jQuery.growl.error(
                {
                    title: lang.error,
                    message: data.error
                }
            );
        } else {
            jQuery.growl.notice(
                {
                    title: lang.success,
                    message: lang.dueDateUpdated
                }
            );
            jQuery('#task-due-date-' + data.taskId).html(data.dueDate);
        }
    },

    assigntask: function(data) {
        if (data.status == "0") {
            jQuery.growl.error(
                {
                    title: lang.error,
                    message: data.error
                }
            );
        } else {
            jQuery.growl.notice(
                {
                    title: lang.success,
                    message: lang.assignmentUpdated
                }
            );
        }
    },

    searchTickets: function(data) {
        if (data.status == "0") {
            jQuery('#frmAddTicket').find('.error-feedback').html(data.error).hide().removeClass('hidden').fadeIn();
            jQuery('#associatedTicketResults').find('.ticket').hide().remove();
            jQuery('#resultInfo').fadeIn('fast');
        } else {
            jQuery('#resultInfo').fadeOut('fast', function(){
                if (data.tickets.length == 0) {
                    jQuery('#associatedTicketResults').append(
                        '<a class="list-group-item ticket bottom-margin-5">' + lang.noTicketsFound + '</a>'
                    );
                } else {
                    data.tickets.forEach(
                        function (ticket)
                        {
                            jQuery('#associatedTicketResults').append(
                                '<a class="list-group-item ticket bottom-margin-5 ticket-result" data-ticket-tid="' + ticket.tid + '" href="#">' +
                                '<span class="ticketnum">' + lang.ticketNum + ticket.tid + '</span> ' +
                                '<span class="subject">' + ticket.title + '</span> ' +
                                '<span class="label" style="background-color: ' + ticket.statusColour + '; color: ' + ticket.statusTextColour + ';">' + ticket.status + '</span>' +
                                '</a>'
                            )
                        }
                    );
                }
            });
        }

    },

    unlinkTicket: function(data) {
        if (data.status == "0") {
            jQuery.growl.error(
                {
                    title: lang.error,
                    message: data.error
                }
            );
        } else {
            jQuery('#ticket-' + data.ticketId).toggle('highlight').remove();
            jQuery('#ticketCount').text(data.ticketCount);
            if (data.ticketCount === "0") {
                jQuery('#noTickets').show();
            }
            jQuery.growl.notice(
                {
                    title: lang.success,
                    message: lang.ticketRemoved
                }
            );
        }
    },

    unlinkInvoice: function(data) {
        if (data.status == "0") {
            jQuery.growl.error(
                {
                    title: lang.error,
                    message: data.error
                }
            );
        } else {
            jQuery('#invoice-' + data.invoiceId).toggle('highlight').remove();
            jQuery('#associatedInvoiceCount').text(data.invoiceCount);
            if (data.invoiceCount === "0") {
                jQuery('#noInvoices').show();
            }
            jQuery.growl.notice(
                {
                    title: lang.success,
                    message: lang.invoiceRemoved
                }
            );
        }
    },

    searchInvoices: function(data) {
        if (data.status == "0") {
            jQuery('#frmAddInvoice').find('.error-feedback').html(data.error).hide().removeClass('hidden').fadeIn();
            jQuery('#associatedInvoiceResults').find('.invoice').remove(function() {
                jQuery('#invoiceResultInfo').fadeIn('fast');
            });
        } else {
            jQuery('#invoiceResultInfo').fadeOut('fast', function(){
                if (data.invoices.length == 0) {
                    jQuery('#associatedInvoiceResults').append(
                        '<a class="list-group-item invoice bottom-margin-5">' + lang.noInvoicesFound + '</a>'
                    );
                } else {
                    data.invoices.forEach(
                        function (invoice)
                        {
                            jQuery('#associatedInvoiceResults').append(
                                '<a class="list-group-item invoice bottom-margin-5 invoice-result" data-invoice-id="' + invoice.id + '" href="#">' +
                                '<span class="invoice-data">' + lang.invoiceNum + (invoice.invoiceNumber ? invoice.invoiceNumber : invoice.id) + '</span> ' +
                                '<span class="invoice-data"> - ' + lang.total + ': ' + invoice.total + '</span>' +
                                '<span class="invoice-data"> - ' + lang.due + ': ' + invoice.dateDue + '</span>' +
                                '<span class="label ' + invoice.status.toLowerCase() + '">' + invoice.status + '</span>' +
                                '</a>'
                            )
                        }
                    );
                }
            });
        }

    },

    addInvoice: function (data) {
        if (data.status == "0") {
            jQuery('#frmAddInvoice').find('.error-feedback').html(data.error).hide().removeClass('hidden').fadeIn();
        } else {
            var invoice = data.invoice;
            appendInvoice(invoice);

            jQuery('#associatedInvoiceCount').text(data.invoiceCount);

            jQuery.growl.notice(
                {
                    title: lang.success,
                    message: lang.invoiceAssociated.replace(':num', invoice.id)
                }
            );
            jQuery('#frmAddInvoice').find('.modal').modal('hide');
            jQuery('#associatedInvoiceResults').find('.ticket').remove();
            jQuery('#invoiceResultInfo').fadeIn('fast');
        }
    },

    createInvoice: function (data) {
        if (data.status == "0") {
            jQuery('#frmCreateInvoice').find('.error-feedback').html(data.error).hide().removeClass('hidden').fadeIn();
        } else {
            var invoice = data.invoice;
            appendInvoice(invoice);

            jQuery('#associatedInvoiceCount').text(data.invoiceCount);

            jQuery.growl.notice(
                {
                    title: lang.success,
                    message: lang.invoiceCreated.replace(':num', invoice.id)
                }
            );
            jQuery('#frmCreateInvoice').find('.modal').modal('hide');
            jQuery('#createInvoiceApplyTax').removeAttr('checked');
            jQuery('#createInvoiceSendEmail').removeAttr('checked');
            jQuery('#createInvoiceCreated').val('');
            jQuery('#createInvoiceDue').val('');
            jQuery('#createInvoiceAmount').val('');
            jQuery('#createInvoiceDescription').val('');
        }
    },

    selectTaskList: function (data) {
        if (data.status == "0") {
            jQuery('#formImportTasks').find('.error-feedback').html(data.error).hide().removeClass('hidden').fadeIn();
        } else {
            var tasks = data.tasks;
            var taskResults = jQuery('#importTaskResults');
            jQuery('#tasksResultInfo').fadeOut('fast', function(){
                jQuery(this).addClass('hidden').show();
                taskResults.find('.ticket').fadeOut('fast', function() {
                    jQuery(this).remove();
                });

                if (typeof tasks == "undefined") {
                    taskResults.append(
                        '<a class="list-group-item ticket bottom-margin-5">' + lang.noTasksFound + '</a>'
                    );
                } else {
                    tasks.forEach(
                        function (task)
                        {
                            taskResults.append(
                                '<div class="row list-group-item ticket bottom-margin-5">' +
                                '<div class="col-md-1">' +
                                '<input type="checkbox" name="taskId[]" value="' + task.id + '" checked="checked" class="checkbox-inline">' +
                                '</div>' +
                                '<div class="col-md-9">' +
                                '<span class="subject">' + task.name + '</span>' +
                                '</div>' +
                                '</div>'
                            )
                        }
                    );
                    taskResults.append('<input type="hidden" name="reference" value="' + data.reference + '" />');
                }
            });
        }

    },

    importTasks: function (data) {
        var editTasksPermission = data.editPermission,
            deleteTasksPermission = data.deletePermission,
            actionButtons = '';

        if (editTasksPermission) {
            actionButtons = '<i class="task-edit far fa-pencil-alt"></i>';
        }
        if (deleteTasksPermission) {
            if (actionButtons !== '') {
                actionButtons = actionButtons + '&nbsp;';
            }
            actionButtons = actionButtons + '<i class="task-delete far fa-trash-alt"></i>';
        }

        data.tasks.forEach(
            function (newTask) {
                $('.tasks').append('<tr class="task-line-item hidden" id="task-' + newTask.id + '"><td><i class="task-status-indicator far fa-check-circle"></i><span class="description"> ' + newTask.task + '</span> ' + newTask.assigned + newTask.duedate + '<div class="actions">' + actionButtons + '</div><br /><span class="text-grey">' + newTask.notes + '</span></td></tr>');
                $('#task-' + newTask.id).hide().removeClass('hidden').slideDown();
            }
        );
        jQuery('#totalTasks').html(data.summary.total);
        jQuery('#completedTasks').html(data.summary.completed);
    },

    watch: function (data) {
        //do nothing
    },

    unwatch: function (data) {
        //do nothing
    },
    saveTaskList: function (data) {
        jQuery('#formSaveTaskList').find('.modal').modal('hide').end()
            .find('input[name="name"]').val('');
        jQuery.growl.notice(
            {
                title: lang.success,
                message: lang.taskListCreated + ': ' + data.taskListName
            }
        );
    },
    gettimerinfo: function (data) {
        var editTimers = jQuery('#editTimers'),
            timer = data.timer[0],
            editTimerTaskId = jQuery('#editTimerTaskId'),
            editTimerAdminId = jQuery('#editTimerAdminId'),
            editTimerStartDate = jQuery('#editTimerStartDate'),
            editTimerEndDate = jQuery('#editTimerEndDate');

        jQuery('#frmTimeTrackingTimerId').val(timer.id);
        editTimerTaskId.val(timer.taskId);
        editTimerAdminId.val(timer.adminId);
        editTimerEndDate.val(timer.endDateTime);
        editTimerEndDate.data('daterangepicker').setStartDate(timer.endDateTime);
        editTimerEndDate.data('daterangepicker').setEndDate(timer.endDateTime);
        editTimerStartDate.val(timer.dateTime);
        editTimerStartDate.data('daterangepicker').setStartDate(timer.dateTime);
        editTimerStartDate.data('daterangepicker').setEndDate(timer.dateTime);
        jQuery('#frmTimeTracking').find('.btn-primary').focus();
    },
    updateTimer: function (data) {
        var timer = data.timer[0],
            billed = '<i class="fas fa-times-circle"></i>',
            taskSpan = jQuery('#total-time-task-' + timer.taskId);

        if (timer.billed === 1) {
            billed = '<i class="fas fa-check-circle"></i>';
        }
        jQuery('#timer-' + timer.id).html(
            '<td><input title="Timer ' + timer.id + '" type="checkbox" name="timerId[]" value="' + timer.id + '"/></td><td>' + timer.date + '</td><td>' + timer.adminName + '</td><td>' + timer.taskName + '</td><td>' + timer.startTime + '</td><td class="timer-end-time">' + timer.endTime + '</td><td>' + billed + '</td><td class="timer-duration">' + timer.duration + '</td><td><a href="#" class="timer-edit"><i class="fas fa-pencil-alt"></i></a></td><td></td>'
        );
        if (timer.endTime === '-') {
            jQuery('#timer-' + timerId).find('input[type="checkbox"]').prop('disabled', true).addClass('disabled');
        }
        if (timer.taskId && taskSpan.length) {
            taskSpan.html(timer.totalTaskTime);
        }
        jQuery.growl.notice(
            {
                title: lang.success,
                message: lang.timerEdited
            }
        );
    },
    taskTimeAdd: function (data) {
        if (data.status === "0") {
            jQuery.growl.error(
                {
                    title: lang.error,
                    message: data.error
                }
            );
        } else {
            var timer = data.timer[0],
                billed = '<i class="fas fa-times-circle"></i>',
                taskSpan = jQuery('#total-time-task-' + timer.taskId);

            if (timer.billed === 1) {
                billed = '<i class="fas fa-check-circle"></i>';
            }
            jQuery('#noTimers').hide();
            jQuery('#timersTable').append('<tr id="timer-' + timer.id + '"><td><input title="Timer ' + timer.id + '" type="checkbox" name="timerId[]" value="' + timer.id + '"/></td><td>' + timer.date + '</td><td>' + timer.adminName + '</td><td>' + timer.taskName + '</td><td>' + timer.startTime + '</td><td class="timer-end-time">' + timer.endTime + '</td><td>' + billed + '</td><td class="timer-duration">' + timer.duration + '</td><td><a href="#" class="timer-edit"><i class="fas fa-pencil-alt"></i></a></td></tr>');
            if (timer.taskId && taskSpan.length) {
                taskSpan.html(timer.totalTaskTime);
            }
            $('#time-logged').html(data.totalTime);
            $('#time-billed').html(data.totalBilled);
            jQuery.growl.notice(
                {
                    title: lang.success,
                    message: lang.timeAdded
                }
            );
        }


    },
    invoiceItems: function (data) {
        var timersTable = jQuery('#timersTable'),
            timers = data.timers,
            invoice = data.invoice;

        timersTable.find('tbody').html('');

        jQuery.each(timers, function(key, timer) {
            var disabled = '',
                thisClass = '';

            if (timer.endTime === '-' || timer.billed) {
                disabled = ' disabled="disabled"';
                thisClass = ' class="disabled"';
            }
            timersTable.find('tbody').append(
                '<tr id="' + timer.id + '">' +
                '<td><input title="Timer ' + timer.id + '" type="checkbox" name="timerId[]" value="' + timer.id + '"' + disabled + thisClass + '/></td>' +
                '<td>' + timer.date + '</td>' +
                '<td>' + timer.adminName + '</td>' +
                '<td>' + timer.taskName + '</td>' +
                '<td>' + timer.startTime + '</td>' +
                '<td' + (timer.endTime !== '-' ? '' : ' class="timer-end-time"') + '>' + timer.endTime + '</td>' +
                '<td>' + (timer.billed ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') + '</td>' +
                '<td' + (timer.endTime !== '-' ? '' : ' class="timer-duration"') + '>' + timer.duration + '</td>' +
                '<td><a href="#" class="timer-edit" onclick="return false;"><i class="fas fa-pencil-alt"></i></a></td>' +
                '</tr>'
            );
        });

        jQuery('#time-logged').html(data.totalTime);
        jQuery('#time-billed').html(data.totalBilled);

        jQuery('#associatedInvoiceCount').text(data.invoiceCount);

        appendInvoice(invoice);

        jQuery.growl.notice(
            {
                title: lang.success,
                message: lang.invoiceGenerated + ': ' + data.invoiceId
            }
        );
    },
    saveProject: function (data) {
        var dueDate = jQuery('#detailsDue'),
            assigned = jQuery('#detailsAssigned'),
            client = jQuery('#detailsClient'),
            status = jQuery('#detailsStatus'),
            updated = jQuery('#detailsUpdated'),
            createInvoice = jQuery('#createInvoice'),
            btnInvoiceSelected = jQuery('#btnInvoiceSelected'),
            openTicketClient = jQuery('#openTicketClient'),
            openTicketNoClient = jQuery('#openTicketNoClient'),
            newTicketClientRO = jQuery('#newTicketClientRO'),
            btnSendEmail = jQuery('#btnSendEmail'),
            projectTitle = jQuery('#projectTitle'),
            detailsClientLi = jQuery('#detailsClientLi');

        projectTitle.html(data.title);
        dueDate.html(data.due);
        assigned.html(data.admin);
        client.html(data.client);
        status.html(data.status);
        updated.html(data.modified);
        createInvoice.addClass('disabled').prop('disabled', true);
        btnInvoiceSelected.addClass('disabled').prop('disabled', true);
        openTicketClient.addClass('hidden');
        openTicketNoClient.removeClass('hidden');
        btnSendEmail.addClass('hidden');
        detailsClientLi.attr('data-toggle', 'modal');
        if (data.clientId) {
            detailsClientLi.removeAttr('data-toggle');
            createInvoice.removeClass('disabled').prop('disabled', false);
            btnInvoiceSelected.removeClass('disabled').prop('disabled', false);
            openTicketClient.removeClass('hidden');
            openTicketNoClient.addClass('hidden');
            newTicketClientRO.val(data.clientName);
            btnSendEmail.removeClass('hidden');
        }
    },
    sendEmail: function(data) {
        if (data.status === "0") {
            jQuery('#formSendEmail').find('.error-feedback').html(data.error).hide().removeClass('hidden').fadeIn();
        } else {
            jQuery.growl.notice(
                {
                    title: lang.success,
                    message: lang.emailSent
                }
            );
        }
    },
    duplicateProject: function(data) {
        if (data.status === "0") {
            jQuery('#formSendEmail').find('.error-feedback').html(data.error).hide().removeClass('hidden').fadeIn();
        } else {
            window.location.href = 'addonmodules.php?module=project_management&m=view&projectid=' + data.newProjectId;
        }
    }

};

/**
 * selectize.js (v0.12.4)
 * Copyright (c) 2013–2015 Brian Reavis & contributors
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this
 * file except in compliance with the License. You may obtain a copy of the License at:
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF
 * ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 *
 * @author Brian Reavis <brian@thirdroute.com>
 */

/*jshint curly:false */
/*jshint browser:true */

(function(root, factory) {
	if (typeof define === 'function' && define.amd) {
		define(['jquery','sifter','microplugin'], factory);
	} else if (typeof exports === 'object') {
		module.exports = factory(require('jquery'), require('sifter'), require('microplugin'));
	} else {
		root.Selectize = factory(root.jQuery, root.Sifter, root.MicroPlugin);
	}
}(this, function($, Sifter, MicroPlugin) {
	'use strict';

	var highlight = function($element, pattern) {
		if (typeof pattern === 'string' && !pattern.length) return;
		var regex = (typeof pattern === 'string') ? new RegExp(pattern, 'i') : pattern;
	
		var highlight = function(node) {
			var skip = 0;
			if (node.nodeType === 3) {
				var pos = node.data.search(regex);
				if (pos >= 0 && node.data.length > 0) {
					var match = node.data.match(regex);
					var spannode = document.createElement('span');
					spannode.className = 'highlight';
					var middlebit = node.splitText(pos);
					var endbit = middlebit.splitText(match[0].length);
					var middleclone = middlebit.cloneNode(true);
					spannode.appendChild(middleclone);
					middlebit.parentNode.replaceChild(spannode, middlebit);
					skip = 1;
				}
			} else if (node.nodeType === 1 && node.childNodes && !/(script|style)/i.test(node.tagName)) {
				for (var i = 0; i < node.childNodes.length; ++i) {
					i += highlight(node.childNodes[i]);
				}
			}
			return skip;
		};
	
		return $element.each(function() {
			highlight(this);
		});
	};
	
	/**
	 * removeHighlight fn copied from highlight v5 and
	 * edited to remove with() and pass js strict mode
	 */
	$.fn.removeHighlight = function() {
		return this.find("span.highlight").each(function() {
			this.parentNode.firstChild.nodeName;
			var parent = this.parentNode;
			parent.replaceChild(this.firstChild, this);
			parent.normalize();
		}).end();
	};
	
	
	var MicroEvent = function() {};
	MicroEvent.prototype = {
		on: function(event, fct){
			this._events = this._events || {};
			this._events[event] = this._events[event] || [];
			this._events[event].push(fct);
		},
		off: function(event, fct){
			var n = arguments.length;
			if (n === 0) return delete this._events;
			if (n === 1) return delete this._events[event];
	
			this._events = this._events || {};
			if (event in this._events === false) return;
			this._events[event].splice(this._events[event].indexOf(fct), 1);
		},
		trigger: function(event /* , args... */){
			this._events = this._events || {};
			if (event in this._events === false) return;
			for (var i = 0; i < this._events[event].length; i++){
				this._events[event][i].apply(this, Array.prototype.slice.call(arguments, 1));
			}
		}
	};
	
	/**
	 * Mixin will delegate all MicroEvent.js function in the destination object.
	 *
	 * - MicroEvent.mixin(Foobar) will make Foobar able to use MicroEvent
	 *
	 * @param {object} the object which will support MicroEvent
	 */
	MicroEvent.mixin = function(destObject){
		var props = ['on', 'off', 'trigger'];
		for (var i = 0; i < props.length; i++){
			destObject.prototype[props[i]] = MicroEvent.prototype[props[i]];
		}
	};
	
	var IS_MAC        = /Mac/.test(navigator.userAgent);
	
	var KEY_A         = 65;
	var KEY_COMMA     = 188;
	var KEY_RETURN    = 13;
	var KEY_ESC       = 27;
	var KEY_LEFT      = 37;
	var KEY_UP        = 38;
	var KEY_P         = 80;
	var KEY_RIGHT     = 39;
	var KEY_DOWN      = 40;
	var KEY_N         = 78;
	var KEY_BACKSPACE = 8;
	var KEY_DELETE    = 46;
	var KEY_SHIFT     = 16;
	var KEY_CMD       = IS_MAC ? 91 : 17;
	var KEY_CTRL      = IS_MAC ? 18 : 17;
	var KEY_TAB       = 9;
	
	var TAG_SELECT    = 1;
	var TAG_INPUT     = 2;
	
	// for now, android support in general is too spotty to support validity
	var SUPPORTS_VALIDITY_API = !/android/i.test(window.navigator.userAgent) && !!document.createElement('input').validity;
	
	
	var isset = function(object) {
		return typeof object !== 'undefined';
	};
	
	/**
	 * Converts a scalar to its best string representation
	 * for hash keys and HTML attribute values.
	 *
	 * Transformations:
	 *   'str'     -> 'str'
	 *   null      -> ''
	 *   undefined -> ''
	 *   true      -> '1'
	 *   false     -> '0'
	 *   0         -> '0'
	 *   1         -> '1'
	 *
	 * @param {string} value
	 * @returns {string|null}
	 */
	var hash_key = function(value) {
		if (typeof value === 'undefined' || value === null) return null;
		if (typeof value === 'boolean') return value ? '1' : '0';
		return value + '';
	};
	
	/**
	 * Escapes a string for use within HTML.
	 *
	 * @param {string} str
	 * @returns {string}
	 */
	var escape_html = function(str) {
		return (str + '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	};
	
	/**
	 * Escapes "$" characters in replacement strings.
	 *
	 * @param {string} str
	 * @returns {string}
	 */
	var escape_replace = function(str) {
		return (str + '').replace(/\$/g, '$$$$');
	};
	
	var hook = {};
	
	/**
	 * Wraps `method` on `self` so that `fn`
	 * is invoked before the original method.
	 *
	 * @param {object} self
	 * @param {string} method
	 * @param {function} fn
	 */
	hook.before = function(self, method, fn) {
		var original = self[method];
		self[method] = function() {
			fn.apply(self, arguments);
			return original.apply(self, arguments);
		};
	};
	
	/**
	 * Wraps `method` on `self` so that `fn`
	 * is invoked after the original method.
	 *
	 * @param {object} self
	 * @param {string} method
	 * @param {function} fn
	 */
	hook.after = function(self, method, fn) {
		var original = self[method];
		self[method] = function() {
			var result = original.apply(self, arguments);
			fn.apply(self, arguments);
			return result;
		};
	};
	
	/**
	 * Wraps `fn` so that it can only be invoked once.
	 *
	 * @param {function} fn
	 * @returns {function}
	 */
	var once = function(fn) {
		var called = false;
		return function() {
			if (called) return;
			called = true;
			fn.apply(this, arguments);
		};
	};
	
	/**
	 * Wraps `fn` so that it can only be called once
	 * every `delay` milliseconds (invoked on the falling edge).
	 *
	 * @param {function} fn
	 * @param {int} delay
	 * @returns {function}
	 */
	var debounce = function(fn, delay) {
		var timeout;
		return function() {
			var self = this;
			var args = arguments;
			window.clearTimeout(timeout);
			timeout = window.setTimeout(function() {
				fn.apply(self, args);
			}, delay);
		};
	};
	
	/**
	 * Debounce all fired events types listed in `types`
	 * while executing the provided `fn`.
	 *
	 * @param {object} self
	 * @param {array} types
	 * @param {function} fn
	 */
	var debounce_events = function(self, types, fn) {
		var type;
		var trigger = self.trigger;
		var event_args = {};
	
		// override trigger method
		self.trigger = function() {
			var type = arguments[0];
			if (types.indexOf(type) !== -1) {
				event_args[type] = arguments;
			} else {
				return trigger.apply(self, arguments);
			}
		};
	
		// invoke provided function
		fn.apply(self, []);
		self.trigger = trigger;
	
		// trigger queued events
		for (type in event_args) {
			if (event_args.hasOwnProperty(type)) {
				trigger.apply(self, event_args[type]);
			}
		}
	};
	
	/**
	 * A workaround for http://bugs.jquery.com/ticket/6696
	 *
	 * @param {object} $parent - Parent element to listen on.
	 * @param {string} event - Event name.
	 * @param {string} selector - Descendant selector to filter by.
	 * @param {function} fn - Event handler.
	 */
	var watchChildEvent = function($parent, event, selector, fn) {
		$parent.on(event, selector, function(e) {
			var child = e.target;
			while (child && child.parentNode !== $parent[0]) {
				child = child.parentNode;
			}
			e.currentTarget = child;
			return fn.apply(this, [e]);
		});
	};
	
	/**
	 * Determines the current selection within a text input control.
	 * Returns an object containing:
	 *   - start
	 *   - length
	 *
	 * @param {object} input
	 * @returns {object}
	 */
	var getSelection = function(input) {
		var result = {};
		if ('selectionStart' in input) {
			result.start = input.selectionStart;
			result.length = input.selectionEnd - result.start;
		} else if (document.selection) {
			input.focus();
			var sel = document.selection.createRange();
			var selLen = document.selection.createRange().text.length;
			sel.moveStart('character', -input.value.length);
			result.start = sel.text.length - selLen;
			result.length = selLen;
		}
		return result;
	};
	
	/**
	 * Copies CSS properties from one element to another.
	 *
	 * @param {object} $from
	 * @param {object} $to
	 * @param {array} properties
	 */
	var transferStyles = function($from, $to, properties) {
		var i, n, styles = {};
		if (properties) {
			for (i = 0, n = properties.length; i < n; i++) {
				styles[properties[i]] = $from.css(properties[i]);
			}
		} else {
			styles = $from.css();
		}
		$to.css(styles);
	};
	
	/**
	 * Measures the width of a string within a
	 * parent element (in pixels).
	 *
	 * @param {string} str
	 * @param {object} $parent
	 * @returns {int}
	 */
	var measureString = function(str, $parent) {
		if (!str) {
			return 0;
		}
	
		var $test = $('<test>').css({
			position: 'absolute',
			top: -99999,
			left: -99999,
			width: 'auto',
			padding: 0,
			whiteSpace: 'pre'
		}).text(str).appendTo('body');
	
		transferStyles($parent, $test, [
			'letterSpacing',
			'fontSize',
			'fontFamily',
			'fontWeight',
			'textTransform'
		]);
	
		var width = $test.width();
		$test.remove();
	
		return width;
	};
	
	/**
	 * Sets up an input to grow horizontally as the user
	 * types. If the value is changed manually, you can
	 * trigger the "update" handler to resize:
	 *
	 * $input.trigger('update');
	 *
	 * @param {object} $input
	 */
	var autoGrow = function($input) {
		var currentWidth = null;
	
		var update = function(e, options) {
			var value, keyCode, printable, placeholder, width;
			var shift, character, selection;
			e = e || window.event || {};
			options = options || {};
	
			if (e.metaKey || e.altKey) return;
			if (!options.force && $input.data('grow') === false) return;
	
			value = $input.val();
			if (e.type && e.type.toLowerCase() === 'keydown') {
				keyCode = e.keyCode;
				printable = (
					(keyCode >= 97 && keyCode <= 122) || // a-z
					(keyCode >= 65 && keyCode <= 90)  || // A-Z
					(keyCode >= 48 && keyCode <= 57)  || // 0-9
					keyCode === 32 // space
				);
	
				if (keyCode === KEY_DELETE || keyCode === KEY_BACKSPACE) {
					selection = getSelection($input[0]);
					if (selection.length) {
						value = value.substring(0, selection.start) + value.substring(selection.start + selection.length);
					} else if (keyCode === KEY_BACKSPACE && selection.start) {
						value = value.substring(0, selection.start - 1) + value.substring(selection.start + 1);
					} else if (keyCode === KEY_DELETE && typeof selection.start !== 'undefined') {
						value = value.substring(0, selection.start) + value.substring(selection.start + 1);
					}
				} else if (printable) {
					shift = e.shiftKey;
					character = String.fromCharCode(e.keyCode);
					if (shift) character = character.toUpperCase();
					else character = character.toLowerCase();
					value += character;
				}
			}
	
			placeholder = $input.attr('placeholder');
			if (!value && placeholder) {
				value = placeholder;
			}
	
			width = measureString(value, $input) + 4;
			if (width !== currentWidth) {
				currentWidth = width;
				$input.width(width);
				$input.triggerHandler('resize');
			}
		};
	
		$input.on('keydown keyup update blur', update);
		update();
	};
	
	var domToString = function(d) {
		var tmp = document.createElement('div');
	
		tmp.appendChild(d.cloneNode(true));
	
		return tmp.innerHTML;
	};
	
	var logError = function(message, options){
		if(!options) options = {};
		var component = "Selectize";
	
		console.error(component + ": " + message)
	
		if(options.explanation){
			// console.group is undefined in <IE11
			if(console.group) console.group();
			console.error(options.explanation);
			if(console.group) console.groupEnd();
		}
	}
	
	
	var Selectize = function($input, settings) {
		var key, i, n, dir, input, self = this;
		input = $input[0];
		input.selectize = self;
	
		// detect rtl environment
		var computedStyle = window.getComputedStyle && window.getComputedStyle(input, null);
		dir = computedStyle ? computedStyle.getPropertyValue('direction') : input.currentStyle && input.currentStyle.direction;
		dir = dir || $input.parents('[dir]:first').attr('dir') || '';
	
		// setup default state
		$.extend(self, {
			order            : 0,
			settings         : settings,
			$input           : $input,
			tabIndex         : $input.attr('tabindex') || '',
			tagType          : input.tagName.toLowerCase() === 'select' ? TAG_SELECT : TAG_INPUT,
			rtl              : /rtl/i.test(dir),
	
			eventNS          : '.selectize' + (++Selectize.count),
			highlightedValue : null,
			isOpen           : false,
			isDisabled       : false,
			isRequired       : $input.is('[required]'),
			isInvalid        : false,
			isLocked         : false,
			isFocused        : false,
			isInputHidden    : false,
			isSetup          : false,
			isShiftDown      : false,
			isCmdDown        : false,
			isCtrlDown       : false,
			ignoreFocus      : false,
			ignoreBlur       : false,
			ignoreHover      : false,
			hasOptions       : false,
			currentResults   : null,
			lastValue        : '',
			caretPos         : 0,
			loading          : 0,
			loadedSearches   : {},
	
			$activeOption    : null,
			$activeItems     : [],
	
			optgroups        : {},
			options          : {},
			userOptions      : {},
			items            : [],
			renderCache      : {},
			onSearchChange   : settings.loadThrottle === null ? self.onSearchChange : debounce(self.onSearchChange, settings.loadThrottle)
		});
	
		// search system
		self.sifter = new Sifter(this.options, {diacritics: settings.diacritics});
	
		// build options table
		if (self.settings.options) {
			for (i = 0, n = self.settings.options.length; i < n; i++) {
				self.registerOption(self.settings.options[i]);
			}
			delete self.settings.options;
		}
	
		// build optgroup table
		if (self.settings.optgroups) {
			for (i = 0, n = self.settings.optgroups.length; i < n; i++) {
				self.registerOptionGroup(self.settings.optgroups[i]);
			}
			delete self.settings.optgroups;
		}
	
		// option-dependent defaults
		self.settings.mode = self.settings.mode || (self.settings.maxItems === 1 ? 'single' : 'multi');
		if (typeof self.settings.hideSelected !== 'boolean') {
			self.settings.hideSelected = self.settings.mode === 'multi';
		}
	
		self.initializePlugins(self.settings.plugins);
		self.setupCallbacks();
		self.setupTemplates();
		self.setup();
	};
	
	// mixins
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	MicroEvent.mixin(Selectize);
	
	if(typeof MicroPlugin !== "undefined"){
		MicroPlugin.mixin(Selectize);
	}else{
		logError("Dependency MicroPlugin is missing",
			{explanation:
				"Make sure you either: (1) are using the \"standalone\" "+
				"version of Selectize, or (2) require MicroPlugin before you "+
				"load Selectize."}
		);
	}
	
	
	// methods
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$.extend(Selectize.prototype, {
	
		/**
		 * Creates all elements and sets up event bindings.
		 */
		setup: function() {
			var self      = this;
			var settings  = self.settings;
			var eventNS   = self.eventNS;
			var $window   = $(window);
			var $document = $(document);
			var $input    = self.$input;
	
			var $wrapper;
			var $control;
			var $control_input;
			var $dropdown;
			var $dropdown_content;
			var $dropdown_parent;
			var inputMode;
			var timeout_blur;
			var timeout_focus;
			var classes;
			var classes_plugins;
			var inputId;
	
			inputMode         = self.settings.mode;
			classes           = $input.attr('class') || '';
	
			$wrapper          = $('<div>').addClass(settings.wrapperClass).addClass(classes).addClass(inputMode);
			$control          = $('<div>').addClass(settings.inputClass).addClass('items').appendTo($wrapper);
			$control_input    = $('<input type="text" autocomplete="off" />').appendTo($control).attr('tabindex', $input.is(':disabled') ? '-1' : self.tabIndex);
			$dropdown_parent  = $(settings.dropdownParent || $wrapper);
			$dropdown         = $('<div>').addClass(settings.dropdownClass).addClass(inputMode).hide().appendTo($dropdown_parent);
			$dropdown_content = $('<div>').addClass(settings.dropdownContentClass).appendTo($dropdown);
	
			if(inputId = $input.attr('id')) {
				$control_input.attr('id', inputId + '-selectized');
				$("label[for='"+inputId+"']").attr('for', inputId + '-selectized');
			}
	
			if(self.settings.copyClassesToDropdown) {
				$dropdown.addClass(classes);
			}
	
			$wrapper.css({
				width: $input[0].style.width
			});
	
			if (self.plugins.names.length) {
				classes_plugins = 'plugin-' + self.plugins.names.join(' plugin-');
				$wrapper.addClass(classes_plugins);
				$dropdown.addClass(classes_plugins);
			}
	
			if ((settings.maxItems === null || settings.maxItems > 1) && self.tagType === TAG_SELECT) {
				$input.attr('multiple', 'multiple');
			}
	
			if (self.settings.placeholder) {
				$control_input.attr('placeholder', settings.placeholder);
			}
	
			// if splitOn was not passed in, construct it from the delimiter to allow pasting universally
			if (!self.settings.splitOn && self.settings.delimiter) {
				var delimiterEscaped = self.settings.delimiter.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
				self.settings.splitOn = new RegExp('\\s*' + delimiterEscaped + '+\\s*');
			}
	
			if ($input.attr('autocorrect')) {
				$control_input.attr('autocorrect', $input.attr('autocorrect'));
			}
	
			if ($input.attr('autocapitalize')) {
				$control_input.attr('autocapitalize', $input.attr('autocapitalize'));
			}
	
			self.$wrapper          = $wrapper;
			self.$control          = $control;
			self.$control_input    = $control_input;
			self.$dropdown         = $dropdown;
			self.$dropdown_content = $dropdown_content;
	
			$dropdown.on('mouseenter', '[data-selectable]', function() { return self.onOptionHover.apply(self, arguments); });
			$dropdown.on('mousedown click', '[data-selectable]', function() { return self.onOptionSelect.apply(self, arguments); });
			watchChildEvent($control, 'mousedown', '*:not(input)', function() { return self.onItemSelect.apply(self, arguments); });
			autoGrow($control_input);
	
			$control.on({
				mousedown : function() { return self.onMouseDown.apply(self, arguments); },
				click     : function() { return self.onClick.apply(self, arguments); }
			});
	
			$control_input.on({
				mousedown : function(e) { e.stopPropagation(); },
				keydown   : function() { return self.onKeyDown.apply(self, arguments); },
				keyup     : function() { return self.onKeyUp.apply(self, arguments); },
				keypress  : function() { return self.onKeyPress.apply(self, arguments); },
				resize    : function() { self.positionDropdown.apply(self, []); },
				blur      : function() { return self.onBlur.apply(self, arguments); },
				focus     : function() { self.ignoreBlur = false; return self.onFocus.apply(self, arguments); },
				paste     : function() { return self.onPaste.apply(self, arguments); }
			});
	
			$document.on('keydown' + eventNS, function(e) {
				self.isCmdDown = e[IS_MAC ? 'metaKey' : 'ctrlKey'];
				self.isCtrlDown = e[IS_MAC ? 'altKey' : 'ctrlKey'];
				self.isShiftDown = e.shiftKey;
			});
	
			$document.on('keyup' + eventNS, function(e) {
				if (e.keyCode === KEY_CTRL) self.isCtrlDown = false;
				if (e.keyCode === KEY_SHIFT) self.isShiftDown = false;
				if (e.keyCode === KEY_CMD) self.isCmdDown = false;
			});
	
			$document.on('mousedown' + eventNS, function(e) {
				if (self.isFocused) {
					// prevent events on the dropdown scrollbar from causing the control to blur
					if (e.target === self.$dropdown[0] || e.target.parentNode === self.$dropdown[0]) {
						return false;
					}
					// blur on click outside
					if (!self.$control.has(e.target).length && e.target !== self.$control[0]) {
						self.blur(e.target);
					}
				}
			});
	
			$window.on(['scroll' + eventNS, 'resize' + eventNS].join(' '), function() {
				if (self.isOpen) {
					self.positionDropdown.apply(self, arguments);
				}
			});
			$window.on('mousemove' + eventNS, function() {
				self.ignoreHover = false;
			});
	
			// store original children and tab index so that they can be
			// restored when the destroy() method is called.
			this.revertSettings = {
				$children : $input.children().detach(),
				tabindex  : $input.attr('tabindex')
			};
	
			$input.attr('tabindex', -1).hide().after(self.$wrapper);
	
			if ($.isArray(settings.items)) {
				self.setValue(settings.items);
				delete settings.items;
			}
	
			// feature detect for the validation API
			if (SUPPORTS_VALIDITY_API) {
				$input.on('invalid' + eventNS, function(e) {
					e.preventDefault();
					self.isInvalid = true;
					self.refreshState();
				});
			}
	
			self.updateOriginalInput();
			self.refreshItems();
			self.refreshState();
			self.updatePlaceholder();
			self.isSetup = true;
	
			if ($input.is(':disabled')) {
				self.disable();
			}
	
			self.on('change', this.onChange);
	
			$input.data('selectize', self);
			$input.addClass('selectized');
			self.trigger('initialize');
	
			// preload options
			if (settings.preload === true) {
				self.onSearchChange('');
			}
	
		},
	
		/**
		 * Sets up default rendering functions.
		 */
		setupTemplates: function() {
			var self = this;
			var field_label = self.settings.labelField;
			var field_optgroup = self.settings.optgroupLabelField;
	
			var templates = {
				'optgroup': function(data) {
					return '<div class="optgroup">' + data.html + '</div>';
				},
				'optgroup_header': function(data, escape) {
					return '<div class="optgroup-header">' + escape(data[field_optgroup]) + '</div>';
				},
				'option': function(data, escape) {
					return '<div class="option">' + escape(data[field_label]) + '</div>';
				},
				'item': function(data, escape) {
					return '<div class="item">' + escape(data[field_label]) + '</div>';
				},
				'option_create': function(data, escape) {
					return '<div class="create">Add <strong>' + escape(data.input) + '</strong>&hellip;</div>';
				}
			};
	
			self.settings.render = $.extend({}, templates, self.settings.render);
		},
	
		/**
		 * Maps fired events to callbacks provided
		 * in the settings used when creating the control.
		 */
		setupCallbacks: function() {
			var key, fn, callbacks = {
				'initialize'      : 'onInitialize',
				'change'          : 'onChange',
				'item_add'        : 'onItemAdd',
				'item_remove'     : 'onItemRemove',
				'clear'           : 'onClear',
				'option_add'      : 'onOptionAdd',
				'option_remove'   : 'onOptionRemove',
				'option_clear'    : 'onOptionClear',
				'optgroup_add'    : 'onOptionGroupAdd',
				'optgroup_remove' : 'onOptionGroupRemove',
				'optgroup_clear'  : 'onOptionGroupClear',
				'dropdown_open'   : 'onDropdownOpen',
				'dropdown_close'  : 'onDropdownClose',
				'type'            : 'onType',
				'load'            : 'onLoad',
				'focus'           : 'onFocus',
				'blur'            : 'onBlur'
			};
	
			for (key in callbacks) {
				if (callbacks.hasOwnProperty(key)) {
					fn = this.settings[callbacks[key]];
					if (fn) this.on(key, fn);
				}
			}
		},
	
		/**
		 * Triggered when the main control element
		 * has a click event.
		 *
		 * @param {object} e
		 * @return {boolean}
		 */
		onClick: function(e) {
			var self = this;
	
			// necessary for mobile webkit devices (manual focus triggering
			// is ignored unless invoked within a click event)
			if (!self.isFocused) {
				self.focus();
				e.preventDefault();
			}
		},
	
		/**
		 * Triggered when the main control element
		 * has a mouse down event.
		 *
		 * @param {object} e
		 * @return {boolean}
		 */
		onMouseDown: function(e) {
			var self = this;
			var defaultPrevented = e.isDefaultPrevented();
			var $target = $(e.target);
	
			if (self.isFocused) {
				// retain focus by preventing native handling. if the
				// event target is the input it should not be modified.
				// otherwise, text selection within the input won't work.
				if (e.target !== self.$control_input[0]) {
					if (self.settings.mode === 'single') {
						// toggle dropdown
						self.isOpen ? self.close() : self.open();
					} else if (!defaultPrevented) {
						self.setActiveItem(null);
					}
					return false;
				}
			} else {
				// give control focus
				if (!defaultPrevented) {
					window.setTimeout(function() {
						self.focus();
					}, 0);
				}
			}
		},
	
		/**
		 * Triggered when the value of the control has been changed.
		 * This should propagate the event to the original DOM
		 * input / select element.
		 */
		onChange: function() {
			this.$input.trigger('change');
		},
	
		/**
		 * Triggered on <input> paste.
		 *
		 * @param {object} e
		 * @returns {boolean}
		 */
		onPaste: function(e) {
			var self = this;
	
			if (self.isFull() || self.isInputHidden || self.isLocked) {
				e.preventDefault();
				return;
			}
	
			// If a regex or string is included, this will split the pasted
			// input and create Items for each separate value
			if (self.settings.splitOn) {
	
				// Wait for pasted text to be recognized in value
				setTimeout(function() {
					var pastedText = self.$control_input.val();
					if(!pastedText.match(self.settings.splitOn)){ return }
	
					var splitInput = $.trim(pastedText).split(self.settings.splitOn);
					for (var i = 0, n = splitInput.length; i < n; i++) {
						self.createItem(splitInput[i]);
					}
				}, 0);
			}
		},
	
		/**
		 * Triggered on <input> keypress.
		 *
		 * @param {object} e
		 * @returns {boolean}
		 */
		onKeyPress: function(e) {
			if (this.isLocked) return e && e.preventDefault();
			var character = String.fromCharCode(e.keyCode || e.which);
			if (this.settings.create && this.settings.mode === 'multi' && character === this.settings.delimiter) {
				this.createItem();
				e.preventDefault();
				return false;
			}
		},
	
		/**
		 * Triggered on <input> keydown.
		 *
		 * @param {object} e
		 * @returns {boolean}
		 */
		onKeyDown: function(e) {
			var isInput = e.target === this.$control_input[0];
			var self = this;
	
			if (self.isLocked) {
				if (e.keyCode !== KEY_TAB) {
					e.preventDefault();
				}
				return;
			}
	
			switch (e.keyCode) {
				case KEY_A:
					if (self.isCmdDown) {
						self.selectAll();
						return;
					}
					break;
				case KEY_ESC:
					if (self.isOpen) {
						e.preventDefault();
						e.stopPropagation();
						self.close();
					}
					return;
				case KEY_N:
					if (!e.ctrlKey || e.altKey) break;
				case KEY_DOWN:
					if (!self.isOpen && self.hasOptions) {
						self.open();
					} else if (self.$activeOption) {
						self.ignoreHover = true;
						var $next = self.getAdjacentOption(self.$activeOption, 1);
						if ($next.length) self.setActiveOption($next, true, true);
					}
					e.preventDefault();
					return;
				case KEY_P:
					if (!e.ctrlKey || e.altKey) break;
				case KEY_UP:
					if (self.$activeOption) {
						self.ignoreHover = true;
						var $prev = self.getAdjacentOption(self.$activeOption, -1);
						if ($prev.length) self.setActiveOption($prev, true, true);
					}
					e.preventDefault();
					return;
				case KEY_RETURN:
					if (self.isOpen && self.$activeOption) {
						self.onOptionSelect({currentTarget: self.$activeOption});
						e.preventDefault();
					}
					return;
				case KEY_LEFT:
					self.advanceSelection(-1, e);
					return;
				case KEY_RIGHT:
					self.advanceSelection(1, e);
					return;
				case KEY_TAB:
					if (self.settings.selectOnTab && self.isOpen && self.$activeOption) {
						self.onOptionSelect({currentTarget: self.$activeOption});
	
						// Default behaviour is to jump to the next field, we only want this
						// if the current field doesn't accept any more entries
						if (!self.isFull()) {
							e.preventDefault();
						}
					}
					if (self.settings.create && self.createItem()) {
						e.preventDefault();
					}
					return;
				case KEY_BACKSPACE:
				case KEY_DELETE:
					self.deleteSelection(e);
					return;
			}
	
			if ((self.isFull() || self.isInputHidden) && !(IS_MAC ? e.metaKey : e.ctrlKey)) {
				e.preventDefault();
				return;
			}
		},
	
		/**
		 * Triggered on <input> keyup.
		 *
		 * @param {object} e
		 * @returns {boolean}
		 */
		onKeyUp: function(e) {
			var self = this;
	
			if (self.isLocked) return e && e.preventDefault();
			var value = self.$control_input.val() || '';
			if (self.lastValue !== value) {
				self.lastValue = value;
				self.onSearchChange(value);
				self.refreshOptions();
				self.trigger('type', value);
			}
		},
	
		/**
		 * Invokes the user-provide option provider / loader.
		 *
		 * Note: this function is debounced in the Selectize
		 * constructor (by `settings.loadThrottle` milliseconds)
		 *
		 * @param {string} value
		 */
		onSearchChange: function(value) {
			var self = this;
			var fn = self.settings.load;
			if (!fn) return;
			if (self.loadedSearches.hasOwnProperty(value)) return;
			self.loadedSearches[value] = true;
			self.load(function(callback) {
				fn.apply(self, [value, callback]);
			});
		},
	
		/**
		 * Triggered on <input> focus.
		 *
		 * @param {object} e (optional)
		 * @returns {boolean}
		 */
		onFocus: function(e) {
			var self = this;
			var wasFocused = self.isFocused;
	
			if (self.isDisabled) {
				self.blur();
				e && e.preventDefault();
				return false;
			}
	
			if (self.ignoreFocus) return;
			self.isFocused = true;
			if (self.settings.preload === 'focus') self.onSearchChange('');
	
			if (!wasFocused) self.trigger('focus');
	
			if (!self.$activeItems.length) {
				self.showInput();
				self.setActiveItem(null);
				self.refreshOptions(!!self.settings.openOnFocus);
			}
	
			self.refreshState();
		},
	
		/**
		 * Triggered on <input> blur.
		 *
		 * @param {object} e
		 * @param {Element} dest
		 */
		onBlur: function(e, dest) {
			var self = this;
			if (!self.isFocused) return;
			self.isFocused = false;
	
			if (self.ignoreFocus) {
				return;
			} else if (!self.ignoreBlur && document.activeElement === self.$dropdown_content[0]) {
				// necessary to prevent IE closing the dropdown when the scrollbar is clicked
				self.ignoreBlur = true;
				self.onFocus(e);
				return;
			}
	
			var deactivate = function() {
				self.close();
				self.setTextboxValue('');
				self.setActiveItem(null);
				self.setActiveOption(null);
				self.setCaret(self.items.length);
				self.refreshState();
	
				// IE11 bug: element still marked as active
				dest && dest.focus && dest.focus();
	
				self.ignoreFocus = false;
				self.trigger('blur');
			};
	
			self.ignoreFocus = true;
			if (self.settings.create && self.settings.createOnBlur) {
				self.createItem(null, false, deactivate);
			} else {
				deactivate();
			}
		},
	
		/**
		 * Triggered when the user rolls over
		 * an option in the autocomplete dropdown menu.
		 *
		 * @param {object} e
		 * @returns {boolean}
		 */
		onOptionHover: function(e) {
			if (this.ignoreHover) return;
			this.setActiveOption(e.currentTarget, false);
		},
	
		/**
		 * Triggered when the user clicks on an option
		 * in the autocomplete dropdown menu.
		 *
		 * @param {object} e
		 * @returns {boolean}
		 */
		onOptionSelect: function(e) {
			var value, $target, $option, self = this;
	
			if (e.preventDefault) {
				e.preventDefault();
				e.stopPropagation();
			}
	
			$target = $(e.currentTarget);
			if ($target.hasClass('create')) {
				self.createItem(null, function() {
					if (self.settings.closeAfterSelect) {
						self.close();
					}
				});
			} else {
				value = $target.attr('data-value');
				if (typeof value !== 'undefined') {
					self.lastQuery = null;
					self.setTextboxValue('');
					self.addItem(value);
					if (self.settings.closeAfterSelect) {
						self.close();
					} else if (!self.settings.hideSelected && e.type && /mouse/.test(e.type)) {
						self.setActiveOption(self.getOption(value));
					}
				}
			}
		},
	
		/**
		 * Triggered when the user clicks on an item
		 * that has been selected.
		 *
		 * @param {object} e
		 * @returns {boolean}
		 */
		onItemSelect: function(e) {
			var self = this;
	
			if (self.isLocked) return;
			if (self.settings.mode === 'multi') {
				e.preventDefault();
				self.setActiveItem(e.currentTarget, e);
			}
		},
	
		/**
		 * Invokes the provided method that provides
		 * results to a callback---which are then added
		 * as options to the control.
		 *
		 * @param {function} fn
		 */
		load: function(fn) {
			var self = this;
			var $wrapper = self.$wrapper.addClass(self.settings.loadingClass);
	
			self.loading++;
			fn.apply(self, [function(results) {
				self.loading = Math.max(self.loading - 1, 0);
				if (results && results.length) {
					self.addOption(results);
					self.refreshOptions(self.isFocused && !self.isInputHidden);
				}
				if (!self.loading) {
					$wrapper.removeClass(self.settings.loadingClass);
				}
				self.trigger('load', results);
			}]);
		},
	
		/**
		 * Sets the input field of the control to the specified value.
		 *
		 * @param {string} value
		 */
		setTextboxValue: function(value) {
			var $input = this.$control_input;
			var changed = $input.val() !== value;
			if (changed) {
				$input.val(value).triggerHandler('update');
				this.lastValue = value;
			}
		},
	
		/**
		 * Returns the value of the control. If multiple items
		 * can be selected (e.g. <select multiple>), this returns
		 * an array. If only one item can be selected, this
		 * returns a string.
		 *
		 * @returns {mixed}
		 */
		getValue: function() {
			if (this.tagType === TAG_SELECT && this.$input.attr('multiple')) {
				return this.items;
			} else {
				return this.items.join(this.settings.delimiter);
			}
		},
	
		/**
		 * Resets the selected items to the given value.
		 *
		 * @param {mixed} value
		 */
		setValue: function(value, silent) {
			var events = silent ? [] : ['change'];
	
			debounce_events(this, events, function() {
				this.clear(silent);
				this.addItems(value, silent);
			});
		},
	
		/**
		 * Sets the selected item.
		 *
		 * @param {object} $item
		 * @param {object} e (optional)
		 */
		setActiveItem: function($item, e) {
			var self = this;
			var eventName;
			var i, idx, begin, end, item, swap;
			var $last;
	
			if (self.settings.mode === 'single') return;
			$item = $($item);
	
			// clear the active selection
			if (!$item.length) {
				$(self.$activeItems).removeClass('active');
				self.$activeItems = [];
				if (self.isFocused) {
					self.showInput();
				}
				return;
			}
	
			// modify selection
			eventName = e && e.type.toLowerCase();
	
			if (eventName === 'mousedown' && self.isShiftDown && self.$activeItems.length) {
				$last = self.$control.children('.active:last');
				begin = Array.prototype.indexOf.apply(self.$control[0].childNodes, [$last[0]]);
				end   = Array.prototype.indexOf.apply(self.$control[0].childNodes, [$item[0]]);
				if (begin > end) {
					swap  = begin;
					begin = end;
					end   = swap;
				}
				for (i = begin; i <= end; i++) {
					item = self.$control[0].childNodes[i];
					if (self.$activeItems.indexOf(item) === -1) {
						$(item).addClass('active');
						self.$activeItems.push(item);
					}
				}
				e.preventDefault();
			} else if ((eventName === 'mousedown' && self.isCtrlDown) || (eventName === 'keydown' && this.isShiftDown)) {
				if ($item.hasClass('active')) {
					idx = self.$activeItems.indexOf($item[0]);
					self.$activeItems.splice(idx, 1);
					$item.removeClass('active');
				} else {
					self.$activeItems.push($item.addClass('active')[0]);
				}
			} else {
				$(self.$activeItems).removeClass('active');
				self.$activeItems = [$item.addClass('active')[0]];
			}
	
			// ensure control has focus
			self.hideInput();
			if (!this.isFocused) {
				self.focus();
			}
		},
	
		/**
		 * Sets the selected item in the dropdown menu
		 * of available options.
		 *
		 * @param {object} $object
		 * @param {boolean} scroll
		 * @param {boolean} animate
		 */
		setActiveOption: function($option, scroll, animate) {
			var height_menu, height_item, y;
			var scroll_top, scroll_bottom;
			var self = this;
	
			if (self.$activeOption) self.$activeOption.removeClass('active');
			self.$activeOption = null;
	
			$option = $($option);
			if (!$option.length) return;
	
			self.$activeOption = $option.addClass('active');
	
			if (scroll || !isset(scroll)) {
	
				height_menu   = self.$dropdown_content.height();
				height_item   = self.$activeOption.outerHeight(true);
				scroll        = self.$dropdown_content.scrollTop() || 0;
				y             = self.$activeOption.offset().top - self.$dropdown_content.offset().top + scroll;
				scroll_top    = y;
				scroll_bottom = y - height_menu + height_item;
	
				if (y + height_item > height_menu + scroll) {
					self.$dropdown_content.stop().animate({scrollTop: scroll_bottom}, animate ? self.settings.scrollDuration : 0);
				} else if (y < scroll) {
					self.$dropdown_content.stop().animate({scrollTop: scroll_top}, animate ? self.settings.scrollDuration : 0);
				}
	
			}
		},
	
		/**
		 * Selects all items (CTRL + A).
		 */
		selectAll: function() {
			var self = this;
			if (self.settings.mode === 'single') return;
	
			self.$activeItems = Array.prototype.slice.apply(self.$control.children(':not(input)').addClass('active'));
			if (self.$activeItems.length) {
				self.hideInput();
				self.close();
			}
			self.focus();
		},
	
		/**
		 * Hides the input element out of view, while
		 * retaining its focus.
		 */
		hideInput: function() {
			var self = this;
	
			self.setTextboxValue('');
			self.$control_input.css({opacity: 0, position: 'absolute', left: self.rtl ? 10000 : -10000});
			self.isInputHidden = true;
		},
	
		/**
		 * Restores input visibility.
		 */
		showInput: function() {
			this.$control_input.css({opacity: 1, position: 'relative', left: 0});
			this.isInputHidden = false;
		},
	
		/**
		 * Gives the control focus.
		 */
		focus: function() {
			var self = this;
			if (self.isDisabled) return;
	
			self.ignoreFocus = true;
			self.$control_input[0].focus();
			window.setTimeout(function() {
				self.ignoreFocus = false;
				self.onFocus();
			}, 0);
		},
	
		/**
		 * Forces the control out of focus.
		 *
		 * @param {Element} dest
		 */
		blur: function(dest) {
			this.$control_input[0].blur();
			this.onBlur(null, dest);
		},
	
		/**
		 * Returns a function that scores an object
		 * to show how good of a match it is to the
		 * provided query.
		 *
		 * @param {string} query
		 * @param {object} options
		 * @return {function}
		 */
		getScoreFunction: function(query) {
			return this.sifter.getScoreFunction(query, this.getSearchOptions());
		},
	
		/**
		 * Returns search options for sifter (the system
		 * for scoring and sorting results).
		 *
		 * @see https://github.com/brianreavis/sifter.js
		 * @return {object}
		 */
		getSearchOptions: function() {
			var settings = this.settings;
			var sort = settings.sortField;
			if (typeof sort === 'string') {
				sort = [{field: sort}];
			}
	
			return {
				fields      : settings.searchField,
				conjunction : settings.searchConjunction,
				sort        : sort
			};
		},
	
		/**
		 * Searches through available options and returns
		 * a sorted array of matches.
		 *
		 * Returns an object containing:
		 *
		 *   - query {string}
		 *   - tokens {array}
		 *   - total {int}
		 *   - items {array}
		 *
		 * @param {string} query
		 * @returns {object}
		 */
		search: function(query) {
			var i, value, score, result, calculateScore;
			var self     = this;
			var settings = self.settings;
			var options  = this.getSearchOptions();
	
			// validate user-provided result scoring function
			if (settings.score) {
				calculateScore = self.settings.score.apply(this, [query]);
				if (typeof calculateScore !== 'function') {
					throw new Error('Selectize "score" setting must be a function that returns a function');
				}
			}
	
			// perform search
			if (query !== self.lastQuery) {
				self.lastQuery = query;
				result = self.sifter.search(query, $.extend(options, {score: calculateScore}));
				self.currentResults = result;
			} else {
				result = $.extend(true, {}, self.currentResults);
			}
	
			// filter out selected items
			if (settings.hideSelected) {
				for (i = result.items.length - 1; i >= 0; i--) {
					if (self.items.indexOf(hash_key(result.items[i].id)) !== -1) {
						result.items.splice(i, 1);
					}
				}
			}
	
			return result;
		},
	
		/**
		 * Refreshes the list of available options shown
		 * in the autocomplete dropdown menu.
		 *
		 * @param {boolean} triggerDropdown
		 */
		refreshOptions: function(triggerDropdown) {
			var i, j, k, n, groups, groups_order, option, option_html, optgroup, optgroups, html, html_children, has_create_option;
			var $active, $active_before, $create;
	
			if (typeof triggerDropdown === 'undefined') {
				triggerDropdown = true;
			}
	
			var self              = this;
			var query             = $.trim(self.$control_input.val());
			var results           = self.search(query);
			var $dropdown_content = self.$dropdown_content;
			var active_before     = self.$activeOption && hash_key(self.$activeOption.attr('data-value'));
	
			// build markup
			n = results.items.length;
			if (typeof self.settings.maxOptions === 'number') {
				n = Math.min(n, self.settings.maxOptions);
			}
	
			// render and group available options individually
			groups = {};
			groups_order = [];
	
			for (i = 0; i < n; i++) {
				option      = self.options[results.items[i].id];
				option_html = self.render('option', option);
				optgroup    = option[self.settings.optgroupField] || '';
				optgroups   = $.isArray(optgroup) ? optgroup : [optgroup];
	
				for (j = 0, k = optgroups && optgroups.length; j < k; j++) {
					optgroup = optgroups[j];
					if (!self.optgroups.hasOwnProperty(optgroup)) {
						optgroup = '';
					}
					if (!groups.hasOwnProperty(optgroup)) {
						groups[optgroup] = document.createDocumentFragment();
						groups_order.push(optgroup);
					}
					groups[optgroup].appendChild(option_html);
				}
			}
	
			// sort optgroups
			if (this.settings.lockOptgroupOrder) {
				groups_order.sort(function(a, b) {
					var a_order = self.optgroups[a].$order || 0;
					var b_order = self.optgroups[b].$order || 0;
					return a_order - b_order;
				});
			}
	
			// render optgroup headers & join groups
			html = document.createDocumentFragment();
			for (i = 0, n = groups_order.length; i < n; i++) {
				optgroup = groups_order[i];
				if (self.optgroups.hasOwnProperty(optgroup) && groups[optgroup].childNodes.length) {
					// render the optgroup header and options within it,
					// then pass it to the wrapper template
					html_children = document.createDocumentFragment();
					html_children.appendChild(self.render('optgroup_header', self.optgroups[optgroup]));
					html_children.appendChild(groups[optgroup]);
	
					html.appendChild(self.render('optgroup', $.extend({}, self.optgroups[optgroup], {
						html: domToString(html_children),
						dom:  html_children
					})));
				} else {
					html.appendChild(groups[optgroup]);
				}
			}
	
			$dropdown_content.html(html);
	
			// highlight matching terms inline
			if (self.settings.highlight && results.query.length && results.tokens.length) {
				$dropdown_content.removeHighlight();
				for (i = 0, n = results.tokens.length; i < n; i++) {
					highlight($dropdown_content, results.tokens[i].regex);
				}
			}
	
			// add "selected" class to selected options
			if (!self.settings.hideSelected) {
				for (i = 0, n = self.items.length; i < n; i++) {
					self.getOption(self.items[i]).addClass('selected');
				}
			}
	
			// add create option
			has_create_option = self.canCreate(query);
			if (has_create_option) {
				$dropdown_content.prepend(self.render('option_create', {input: query}));
				$create = $($dropdown_content[0].childNodes[0]);
			}
	
			// activate
			self.hasOptions = results.items.length > 0 || has_create_option;
			if (self.hasOptions) {
				if (results.items.length > 0) {
					$active_before = active_before && self.getOption(active_before);
					if ($active_before && $active_before.length) {
						$active = $active_before;
					} else if (self.settings.mode === 'single' && self.items.length) {
						$active = self.getOption(self.items[0]);
					}
					if (!$active || !$active.length) {
						if ($create && !self.settings.addPrecedence) {
							$active = self.getAdjacentOption($create, 1);
						} else {
							$active = $dropdown_content.find('[data-selectable]:first');
						}
					}
				} else {
					$active = $create;
				}
				self.setActiveOption($active);
				if (triggerDropdown && !self.isOpen) { self.open(); }
			} else {
				self.setActiveOption(null);
				if (triggerDropdown && self.isOpen) { self.close(); }
			}
		},
	
		/**
		 * Adds an available option. If it already exists,
		 * nothing will happen. Note: this does not refresh
		 * the options list dropdown (use `refreshOptions`
		 * for that).
		 *
		 * Usage:
		 *
		 *   this.addOption(data)
		 *
		 * @param {object|array} data
		 */
		addOption: function(data) {
			var i, n, value, self = this;
	
			if ($.isArray(data)) {
				for (i = 0, n = data.length; i < n; i++) {
					self.addOption(data[i]);
				}
				return;
			}
	
			if (value = self.registerOption(data)) {
				self.userOptions[value] = true;
				self.lastQuery = null;
				self.trigger('option_add', value, data);
			}
		},
	
		/**
		 * Registers an option to the pool of options.
		 *
		 * @param {object} data
		 * @return {boolean|string}
		 */
		registerOption: function(data) {
			var key = hash_key(data[this.settings.valueField]);
			if (typeof key === 'undefined' || key === null || this.options.hasOwnProperty(key)) return false;
			data.$order = data.$order || ++this.order;
			this.options[key] = data;
			return key;
		},
	
		/**
		 * Registers an option group to the pool of option groups.
		 *
		 * @param {object} data
		 * @return {boolean|string}
		 */
		registerOptionGroup: function(data) {
			var key = hash_key(data[this.settings.optgroupValueField]);
			if (!key) return false;
	
			data.$order = data.$order || ++this.order;
			this.optgroups[key] = data;
			return key;
		},
	
		/**
		 * Registers a new optgroup for options
		 * to be bucketed into.
		 *
		 * @param {string} id
		 * @param {object} data
		 */
		addOptionGroup: function(id, data) {
			data[this.settings.optgroupValueField] = id;
			if (id = this.registerOptionGroup(data)) {
				this.trigger('optgroup_add', id, data);
			}
		},
	
		/**
		 * Removes an existing option group.
		 *
		 * @param {string} id
		 */
		removeOptionGroup: function(id) {
			if (this.optgroups.hasOwnProperty(id)) {
				delete this.optgroups[id];
				this.renderCache = {};
				this.trigger('optgroup_remove', id);
			}
		},
	
		/**
		 * Clears all existing option groups.
		 */
		clearOptionGroups: function() {
			this.optgroups = {};
			this.renderCache = {};
			this.trigger('optgroup_clear');
		},
	
		/**
		 * Updates an option available for selection. If
		 * it is visible in the selected items or options
		 * dropdown, it will be re-rendered automatically.
		 *
		 * @param {string} value
		 * @param {object} data
		 */
		updateOption: function(value, data) {
			var self = this;
			var $item, $item_new;
			var value_new, index_item, cache_items, cache_options, order_old;
	
			value     = hash_key(value);
			value_new = hash_key(data[self.settings.valueField]);
	
			// sanity checks
			if (value === null) return;
			if (!self.options.hasOwnProperty(value)) return;
			if (typeof value_new !== 'string') throw new Error('Value must be set in option data');
	
			order_old = self.options[value].$order;
	
			// update references
			if (value_new !== value) {
				delete self.options[value];
				index_item = self.items.indexOf(value);
				if (index_item !== -1) {
					self.items.splice(index_item, 1, value_new);
				}
			}
			data.$order = data.$order || order_old;
			self.options[value_new] = data;
	
			// invalidate render cache
			cache_items = self.renderCache['item'];
			cache_options = self.renderCache['option'];
	
			if (cache_items) {
				delete cache_items[value];
				delete cache_items[value_new];
			}
			if (cache_options) {
				delete cache_options[value];
				delete cache_options[value_new];
			}
	
			// update the item if it's selected
			if (self.items.indexOf(value_new) !== -1) {
				$item = self.getItem(value);
				$item_new = $(self.render('item', data));
				if ($item.hasClass('active')) $item_new.addClass('active');
				$item.replaceWith($item_new);
			}
	
			// invalidate last query because we might have updated the sortField
			self.lastQuery = null;
	
			// update dropdown contents
			if (self.isOpen) {
				self.refreshOptions(false);
			}
		},
	
		/**
		 * Removes a single option.
		 *
		 * @param {string} value
		 * @param {boolean} silent
		 */
		removeOption: function(value, silent) {
			var self = this;
			value = hash_key(value);
	
			var cache_items = self.renderCache['item'];
			var cache_options = self.renderCache['option'];
			if (cache_items) delete cache_items[value];
			if (cache_options) delete cache_options[value];
	
			delete self.userOptions[value];
			delete self.options[value];
			self.lastQuery = null;
			self.trigger('option_remove', value);
			self.removeItem(value, silent);
		},
	
		/**
		 * Clears all options.
		 */
		clearOptions: function() {
			var self = this;
	
			self.loadedSearches = {};
			self.userOptions = {};
			self.renderCache = {};
			self.options = self.sifter.items = {};
			self.lastQuery = null;
			self.trigger('option_clear');
			self.clear();
		},
	
		/**
		 * Returns the jQuery element of the option
		 * matching the given value.
		 *
		 * @param {string} value
		 * @returns {object}
		 */
		getOption: function(value) {
			return this.getElementWithValue(value, this.$dropdown_content.find('[data-selectable]'));
		},
	
		/**
		 * Returns the jQuery element of the next or
		 * previous selectable option.
		 *
		 * @param {object} $option
		 * @param {int} direction  can be 1 for next or -1 for previous
		 * @return {object}
		 */
		getAdjacentOption: function($option, direction) {
			var $options = this.$dropdown.find('[data-selectable]');
			var index    = $options.index($option) + direction;
	
			return index >= 0 && index < $options.length ? $options.eq(index) : $();
		},
	
		/**
		 * Finds the first element with a "data-value" attribute
		 * that matches the given value.
		 *
		 * @param {mixed} value
		 * @param {object} $els
		 * @return {object}
		 */
		getElementWithValue: function(value, $els) {
			value = hash_key(value);
	
			if (typeof value !== 'undefined' && value !== null) {
				for (var i = 0, n = $els.length; i < n; i++) {
					if ($els[i].getAttribute('data-value') === value) {
						return $($els[i]);
					}
				}
			}
	
			return $();
		},
	
		/**
		 * Returns the jQuery element of the item
		 * matching the given value.
		 *
		 * @param {string} value
		 * @returns {object}
		 */
		getItem: function(value) {
			return this.getElementWithValue(value, this.$control.children());
		},
	
		/**
		 * "Selects" multiple items at once. Adds them to the list
		 * at the current caret position.
		 *
		 * @param {string} value
		 * @param {boolean} silent
		 */
		addItems: function(values, silent) {
			var items = $.isArray(values) ? values : [values];
			for (var i = 0, n = items.length; i < n; i++) {
				this.isPending = (i < n - 1);
				this.addItem(items[i], silent);
			}
		},
	
		/**
		 * "Selects" an item. Adds it to the list
		 * at the current caret position.
		 *
		 * @param {string} value
		 * @param {boolean} silent
		 */
		addItem: function(value, silent) {
			var events = silent ? [] : ['change'];
	
			debounce_events(this, events, function() {
				var $item, $option, $options;
				var self = this;
				var inputMode = self.settings.mode;
				var i, active, value_next, wasFull;
				value = hash_key(value);
	
				if (self.items.indexOf(value) !== -1) {
					if (inputMode === 'single') self.close();
					return;
				}
	
				if (!self.options.hasOwnProperty(value)) return;
				if (inputMode === 'single') self.clear(silent);
				if (inputMode === 'multi' && self.isFull()) return;
	
				$item = $(self.render('item', self.options[value]));
				wasFull = self.isFull();
				self.items.splice(self.caretPos, 0, value);
				self.insertAtCaret($item);
				if (!self.isPending || (!wasFull && self.isFull())) {
					self.refreshState();
				}
	
				if (self.isSetup) {
					$options = self.$dropdown_content.find('[data-selectable]');
	
					// update menu / remove the option (if this is not one item being added as part of series)
					if (!self.isPending) {
						$option = self.getOption(value);
						value_next = self.getAdjacentOption($option, 1).attr('data-value');
						self.refreshOptions(self.isFocused && inputMode !== 'single');
						if (value_next) {
							self.setActiveOption(self.getOption(value_next));
						}
					}
	
					// hide the menu if the maximum number of items have been selected or no options are left
					if (!$options.length || self.isFull()) {
						self.close();
					} else {
						self.positionDropdown();
					}
	
					self.updatePlaceholder();
					self.trigger('item_add', value, $item);
					self.updateOriginalInput({silent: silent});
				}
			});
		},
	
		/**
		 * Removes the selected item matching
		 * the provided value.
		 *
		 * @param {string} value
		 */
		removeItem: function(value, silent) {
			var self = this;
			var $item, i, idx;
	
			$item = (value instanceof $) ? value : self.getItem(value);
			value = hash_key($item.attr('data-value'));
			i = self.items.indexOf(value);
	
			if (i !== -1) {
				$item.remove();
				if ($item.hasClass('active')) {
					idx = self.$activeItems.indexOf($item[0]);
					self.$activeItems.splice(idx, 1);
				}
	
				self.items.splice(i, 1);
				self.lastQuery = null;
				if (!self.settings.persist && self.userOptions.hasOwnProperty(value)) {
					self.removeOption(value, silent);
				}
	
				if (i < self.caretPos) {
					self.setCaret(self.caretPos - 1);
				}
	
				self.refreshState();
				self.updatePlaceholder();
				self.updateOriginalInput({silent: silent});
				self.positionDropdown();
				self.trigger('item_remove', value, $item);
			}
		},
	
		/**
		 * Invokes the `create` method provided in the
		 * selectize options that should provide the data
		 * for the new item, given the user input.
		 *
		 * Once this completes, it will be added
		 * to the item list.
		 *
		 * @param {string} value
		 * @param {boolean} [triggerDropdown]
		 * @param {function} [callback]
		 * @return {boolean}
		 */
		createItem: function(input, triggerDropdown) {
			var self  = this;
			var caret = self.caretPos;
			input = input || $.trim(self.$control_input.val() || '');
	
			var callback = arguments[arguments.length - 1];
			if (typeof callback !== 'function') callback = function() {};
	
			if (typeof triggerDropdown !== 'boolean') {
				triggerDropdown = true;
			}
	
			if (!self.canCreate(input)) {
				callback();
				return false;
			}
	
			self.lock();
	
			var setup = (typeof self.settings.create === 'function') ? this.settings.create : function(input) {
				var data = {};
				data[self.settings.labelField] = input;
				data[self.settings.valueField] = input;
				return data;
			};
	
			var create = once(function(data) {
				self.unlock();
	
				if (!data || typeof data !== 'object') return callback();
				var value = hash_key(data[self.settings.valueField]);
				if (typeof value !== 'string') return callback();
	
				self.setTextboxValue('');
				self.addOption(data);
				self.setCaret(caret);
				self.addItem(value);
				self.refreshOptions(triggerDropdown && self.settings.mode !== 'single');
				callback(data);
			});
	
			var output = setup.apply(this, [input, create]);
			if (typeof output !== 'undefined') {
				create(output);
			}
	
			return true;
		},
	
		/**
		 * Re-renders the selected item lists.
		 */
		refreshItems: function() {
			this.lastQuery = null;
	
			if (this.isSetup) {
				this.addItem(this.items);
			}
	
			this.refreshState();
			this.updateOriginalInput();
		},
	
		/**
		 * Updates all state-dependent attributes
		 * and CSS classes.
		 */
		refreshState: function() {
			this.refreshValidityState();
			this.refreshClasses();
		},
	
		/**
		 * Update the `required` attribute of both input and control input.
		 *
		 * The `required` property needs to be activated on the control input
		 * for the error to be displayed at the right place. `required` also
		 * needs to be temporarily deactivated on the input since the input is
		 * hidden and can't show errors.
		 */
		refreshValidityState: function() {
			if (!this.isRequired) return false;
	
			var invalid = !this.items.length;
	
			this.isInvalid = invalid;
			this.$control_input.prop('required', invalid);
			this.$input.prop('required', !invalid);
		},
	
		/**
		 * Updates all state-dependent CSS classes.
		 */
		refreshClasses: function() {
			var self     = this;
			var isFull   = self.isFull();
			var isLocked = self.isLocked;
	
			self.$wrapper
				.toggleClass('rtl', self.rtl);
	
			self.$control
				.toggleClass('focus', self.isFocused)
				.toggleClass('disabled', self.isDisabled)
				.toggleClass('required', self.isRequired)
				.toggleClass('invalid', self.isInvalid)
				.toggleClass('locked', isLocked)
				.toggleClass('full', isFull).toggleClass('not-full', !isFull)
				.toggleClass('input-active', self.isFocused && !self.isInputHidden)
				.toggleClass('dropdown-active', self.isOpen)
				.toggleClass('has-options', !$.isEmptyObject(self.options))
				.toggleClass('has-items', self.items.length > 0);
	
			self.$control_input.data('grow', !isFull && !isLocked);
		},
	
		/**
		 * Determines whether or not more items can be added
		 * to the control without exceeding the user-defined maximum.
		 *
		 * @returns {boolean}
		 */
		isFull: function() {
			return this.settings.maxItems !== null && this.items.length >= this.settings.maxItems;
		},
	
		/**
		 * Refreshes the original <select> or <input>
		 * element to reflect the current state.
		 */
		updateOriginalInput: function(opts) {
			var i, n, options, label, self = this;
			opts = opts || {};
	
			if (self.tagType === TAG_SELECT) {
				options = [];
				for (i = 0, n = self.items.length; i < n; i++) {
					label = self.options[self.items[i]][self.settings.labelField] || '';
					options.push('<option value="' + escape_html(self.items[i]) + '" selected="selected">' + escape_html(label) + '</option>');
				}
				if (!options.length && !this.$input.attr('multiple')) {
					options.push('<option value="" selected="selected"></option>');
				}
				self.$input.html(options.join(''));
			} else {
				self.$input.val(self.getValue());
				self.$input.attr('value',self.$input.val());
			}
	
			if (self.isSetup) {
				if (!opts.silent) {
					self.trigger('change', self.$input.val());
				}
			}
		},
	
		/**
		 * Shows/hide the input placeholder depending
		 * on if there items in the list already.
		 */
		updatePlaceholder: function() {
			if (!this.settings.placeholder) return;
			var $input = this.$control_input;
	
			if (this.items.length) {
				$input.removeAttr('placeholder');
			} else {
				$input.attr('placeholder', this.settings.placeholder);
			}
			$input.triggerHandler('update', {force: true});
		},
	
		/**
		 * Shows the autocomplete dropdown containing
		 * the available options.
		 */
		open: function() {
			var self = this;
	
			if (self.isLocked || self.isOpen || (self.settings.mode === 'multi' && self.isFull())) return;
			self.focus();
			self.isOpen = true;
			self.refreshState();
			self.$dropdown.css({visibility: 'hidden', display: 'block'});
			self.positionDropdown();
			self.$dropdown.css({visibility: 'visible'});
			self.trigger('dropdown_open', self.$dropdown);
		},
	
		/**
		 * Closes the autocomplete dropdown menu.
		 */
		close: function() {
			var self = this;
			var trigger = self.isOpen;
	
			if (self.settings.mode === 'single' && self.items.length) {
				self.hideInput();
				self.$control_input.blur(); // close keyboard on iOS
			}
	
			self.isOpen = false;
			self.$dropdown.hide();
			self.setActiveOption(null);
			self.refreshState();
	
			if (trigger) self.trigger('dropdown_close', self.$dropdown);
		},
	
		/**
		 * Calculates and applies the appropriate
		 * position of the dropdown.
		 */
		positionDropdown: function() {
			var $control = this.$control;
			var offset = this.settings.dropdownParent === 'body' ? $control.offset() : $control.position();
			offset.top += $control.outerHeight(true);
	
			this.$dropdown.css({
				width : $control.outerWidth(),
				top   : offset.top,
				left  : offset.left
			});
		},
	
		/**
		 * Resets / clears all selected items
		 * from the control.
		 *
		 * @param {boolean} silent
		 */
		clear: function(silent) {
			var self = this;
	
			if (!self.items.length) return;
			self.$control.children(':not(input)').remove();
			self.items = [];
			self.lastQuery = null;
			self.setCaret(0);
			self.setActiveItem(null);
			self.updatePlaceholder();
			self.updateOriginalInput({silent: silent});
			self.refreshState();
			self.showInput();
			self.trigger('clear');
		},
	
		/**
		 * A helper method for inserting an element
		 * at the current caret position.
		 *
		 * @param {object} $el
		 */
		insertAtCaret: function($el) {
			var caret = Math.min(this.caretPos, this.items.length);
			if (caret === 0) {
				this.$control.prepend($el);
			} else {
				$(this.$control[0].childNodes[caret]).before($el);
			}
			this.setCaret(caret + 1);
		},
	
		/**
		 * Removes the current selected item(s).
		 *
		 * @param {object} e (optional)
		 * @returns {boolean}
		 */
		deleteSelection: function(e) {
			var i, n, direction, selection, values, caret, option_select, $option_select, $tail;
			var self = this;
	
			direction = (e && e.keyCode === KEY_BACKSPACE) ? -1 : 1;
			selection = getSelection(self.$control_input[0]);
	
			if (self.$activeOption && !self.settings.hideSelected) {
				option_select = self.getAdjacentOption(self.$activeOption, -1).attr('data-value');
			}
	
			// determine items that will be removed
			values = [];
	
			if (self.$activeItems.length) {
				$tail = self.$control.children('.active:' + (direction > 0 ? 'last' : 'first'));
				caret = self.$control.children(':not(input)').index($tail);
				if (direction > 0) { caret++; }
	
				for (i = 0, n = self.$activeItems.length; i < n; i++) {
					values.push($(self.$activeItems[i]).attr('data-value'));
				}
				if (e) {
					e.preventDefault();
					e.stopPropagation();
				}
			} else if ((self.isFocused || self.settings.mode === 'single') && self.items.length) {
				if (direction < 0 && selection.start === 0 && selection.length === 0) {
					values.push(self.items[self.caretPos - 1]);
				} else if (direction > 0 && selection.start === self.$control_input.val().length) {
					values.push(self.items[self.caretPos]);
				}
			}
	
			// allow the callback to abort
			if (!values.length || (typeof self.settings.onDelete === 'function' && self.settings.onDelete.apply(self, [values]) === false)) {
				return false;
			}
	
			// perform removal
			if (typeof caret !== 'undefined') {
				self.setCaret(caret);
			}
			while (values.length) {
				self.removeItem(values.pop());
			}
	
			self.showInput();
			self.positionDropdown();
			self.refreshOptions(true);
	
			// select previous option
			if (option_select) {
				$option_select = self.getOption(option_select);
				if ($option_select.length) {
					self.setActiveOption($option_select);
				}
			}
	
			return true;
		},
	
		/**
		 * Selects the previous / next item (depending
		 * on the `direction` argument).
		 *
		 * > 0 - right
		 * < 0 - left
		 *
		 * @param {int} direction
		 * @param {object} e (optional)
		 */
		advanceSelection: function(direction, e) {
			var tail, selection, idx, valueLength, cursorAtEdge, $tail;
			var self = this;
	
			if (direction === 0) return;
			if (self.rtl) direction *= -1;
	
			tail = direction > 0 ? 'last' : 'first';
			selection = getSelection(self.$control_input[0]);
	
			if (self.isFocused && !self.isInputHidden) {
				valueLength = self.$control_input.val().length;
				cursorAtEdge = direction < 0
					? selection.start === 0 && selection.length === 0
					: selection.start === valueLength;
	
				if (cursorAtEdge && !valueLength) {
					self.advanceCaret(direction, e);
				}
			} else {
				$tail = self.$control.children('.active:' + tail);
				if ($tail.length) {
					idx = self.$control.children(':not(input)').index($tail);
					self.setActiveItem(null);
					self.setCaret(direction > 0 ? idx + 1 : idx);
				}
			}
		},
	
		/**
		 * Moves the caret left / right.
		 *
		 * @param {int} direction
		 * @param {object} e (optional)
		 */
		advanceCaret: function(direction, e) {
			var self = this, fn, $adj;
	
			if (direction === 0) return;
	
			fn = direction > 0 ? 'next' : 'prev';
			if (self.isShiftDown) {
				$adj = self.$control_input[fn]();
				if ($adj.length) {
					self.hideInput();
					self.setActiveItem($adj);
					e && e.preventDefault();
				}
			} else {
				self.setCaret(self.caretPos + direction);
			}
		},
	
		/**
		 * Moves the caret to the specified index.
		 *
		 * @param {int} i
		 */
		setCaret: function(i) {
			var self = this;
	
			if (self.settings.mode === 'single') {
				i = self.items.length;
			} else {
				i = Math.max(0, Math.min(self.items.length, i));
			}
	
			if(!self.isPending) {
				// the input must be moved by leaving it in place and moving the
				// siblings, due to the fact that focus cannot be restored once lost
				// on mobile webkit devices
				var j, n, fn, $children, $child;
				$children = self.$control.children(':not(input)');
				for (j = 0, n = $children.length; j < n; j++) {
					$child = $($children[j]).detach();
					if (j <  i) {
						self.$control_input.before($child);
					} else {
						self.$control.append($child);
					}
				}
			}
	
			self.caretPos = i;
		},
	
		/**
		 * Disables user input on the control. Used while
		 * items are being asynchronously created.
		 */
		lock: function() {
			this.close();
			this.isLocked = true;
			this.refreshState();
		},
	
		/**
		 * Re-enables user input on the control.
		 */
		unlock: function() {
			this.isLocked = false;
			this.refreshState();
		},
	
		/**
		 * Disables user input on the control completely.
		 * While disabled, it cannot receive focus.
		 */
		disable: function() {
			var self = this;
			self.$input.prop('disabled', true);
			self.$control_input.prop('disabled', true).prop('tabindex', -1);
			self.isDisabled = true;
			self.lock();
		},
	
		/**
		 * Enables the control so that it can respond
		 * to focus and user input.
		 */
		enable: function() {
			var self = this;
			self.$input.prop('disabled', false);
			self.$control_input.prop('disabled', false).prop('tabindex', self.tabIndex);
			self.isDisabled = false;
			self.unlock();
		},
	
		/**
		 * Completely destroys the control and
		 * unbinds all event listeners so that it can
		 * be garbage collected.
		 */
		destroy: function() {
			var self = this;
			var eventNS = self.eventNS;
			var revertSettings = self.revertSettings;
	
			self.trigger('destroy');
			self.off();
			self.$wrapper.remove();
			self.$dropdown.remove();
	
			self.$input
				.html('')
				.append(revertSettings.$children)
				.removeAttr('tabindex')
				.removeClass('selectized')
				.attr({tabindex: revertSettings.tabindex})
				.show();
	
			self.$control_input.removeData('grow');
			self.$input.removeData('selectize');
	
			$(window).off(eventNS);
			$(document).off(eventNS);
			$(document.body).off(eventNS);
	
			delete self.$input[0].selectize;
		},
	
		/**
		 * A helper method for rendering "item" and
		 * "option" templates, given the data.
		 *
		 * @param {string} templateName
		 * @param {object} data
		 * @returns {string}
		 */
		render: function(templateName, data) {
			var value, id, label;
			var html = '';
			var cache = false;
			var self = this;
			var regex_tag = /^[\t \r\n]*<([a-z][a-z0-9\-_]*(?:\:[a-z][a-z0-9\-_]*)?)/i;
	
			if (templateName === 'option' || templateName === 'item') {
				value = hash_key(data[self.settings.valueField]);
				cache = !!value;
			}
	
			// pull markup from cache if it exists
			if (cache) {
				if (!isset(self.renderCache[templateName])) {
					self.renderCache[templateName] = {};
				}
				if (self.renderCache[templateName].hasOwnProperty(value)) {
					return self.renderCache[templateName][value];
				}
			}
	
			// render markup
			html = $(self.settings.render[templateName].apply(this, [data, escape_html]));
	
			// add mandatory attributes
			if (templateName === 'option' || templateName === 'option_create') {
				html.attr('data-selectable', '');
			}
			else if (templateName === 'optgroup') {
				id = data[self.settings.optgroupValueField] || '';
				html.attr('data-group', id);
			}
			if (templateName === 'option' || templateName === 'item') {
				html.attr('data-value', value || '');
			}
	
			// update cache
			if (cache) {
				self.renderCache[templateName][value] = html[0];
			}
	
			return html[0];
		},
	
		/**
		 * Clears the render cache for a template. If
		 * no template is given, clears all render
		 * caches.
		 *
		 * @param {string} templateName
		 */
		clearCache: function(templateName) {
			var self = this;
			if (typeof templateName === 'undefined') {
				self.renderCache = {};
			} else {
				delete self.renderCache[templateName];
			}
		},
	
		/**
		 * Determines whether or not to display the
		 * create item prompt, given a user input.
		 *
		 * @param {string} input
		 * @return {boolean}
		 */
		canCreate: function(input) {
			var self = this;
			if (!self.settings.create) return false;
			var filter = self.settings.createFilter;
			return input.length
				&& (typeof filter !== 'function' || filter.apply(self, [input]))
				&& (typeof filter !== 'string' || new RegExp(filter).test(input))
				&& (!(filter instanceof RegExp) || filter.test(input));
		}
	
	});
	
	
	Selectize.count = 0;
	Selectize.defaults = {
		options: [],
		optgroups: [],
	
		plugins: [],
		delimiter: ',',
		splitOn: null, // regexp or string for splitting up values from a paste command
		persist: true,
		diacritics: true,
		create: false,
		createOnBlur: false,
		createFilter: null,
		highlight: true,
		openOnFocus: true,
		maxOptions: 1000,
		maxItems: null,
		hideSelected: null,
		addPrecedence: false,
		selectOnTab: false,
		preload: false,
		allowEmptyOption: false,
		closeAfterSelect: false,
	
		scrollDuration: 60,
		loadThrottle: 300,
		loadingClass: 'loading',
	
		dataAttr: 'data-data',
		optgroupField: 'optgroup',
		valueField: 'value',
		labelField: 'text',
		optgroupLabelField: 'label',
		optgroupValueField: 'value',
		lockOptgroupOrder: false,
	
		sortField: '$order',
		searchField: ['text'],
		searchConjunction: 'and',
	
		mode: null,
		wrapperClass: 'selectize-control',
		inputClass: 'selectize-input',
		dropdownClass: 'selectize-dropdown',
		dropdownContentClass: 'selectize-dropdown-content',
	
		dropdownParent: null,
	
		copyClassesToDropdown: true,
	
		/*
		load                 : null, // function(query, callback) { ... }
		score                : null, // function(search) { ... }
		onInitialize         : null, // function() { ... }
		onChange             : null, // function(value) { ... }
		onItemAdd            : null, // function(value, $item) { ... }
		onItemRemove         : null, // function(value) { ... }
		onClear              : null, // function() { ... }
		onOptionAdd          : null, // function(value, data) { ... }
		onOptionRemove       : null, // function(value) { ... }
		onOptionClear        : null, // function() { ... }
		onOptionGroupAdd     : null, // function(id, data) { ... }
		onOptionGroupRemove  : null, // function(id) { ... }
		onOptionGroupClear   : null, // function() { ... }
		onDropdownOpen       : null, // function($dropdown) { ... }
		onDropdownClose      : null, // function($dropdown) { ... }
		onType               : null, // function(str) { ... }
		onDelete             : null, // function(values) { ... }
		*/
	
		render: {
			/*
			item: null,
			optgroup: null,
			optgroup_header: null,
			option: null,
			option_create: null
			*/
		}
	};
	
	
	$.fn.selectize = function(settings_user) {
		var defaults             = $.fn.selectize.defaults;
		var settings             = $.extend({}, defaults, settings_user);
		var attr_data            = settings.dataAttr;
		var field_label          = settings.labelField;
		var field_value          = settings.valueField;
		var field_optgroup       = settings.optgroupField;
		var field_optgroup_label = settings.optgroupLabelField;
		var field_optgroup_value = settings.optgroupValueField;
	
		/**
		 * Initializes selectize from a <input type="text"> element.
		 *
		 * @param {object} $input
		 * @param {object} settings_element
		 */
		var init_textbox = function($input, settings_element) {
			var i, n, values, option;
	
			var data_raw = $input.attr(attr_data);
	
			if (!data_raw) {
				var value = $.trim($input.val() || '');
				if (!settings.allowEmptyOption && !value.length) return;
				values = value.split(settings.delimiter);
				for (i = 0, n = values.length; i < n; i++) {
					option = {};
					option[field_label] = values[i];
					option[field_value] = values[i];
					settings_element.options.push(option);
				}
				settings_element.items = values;
			} else {
				settings_element.options = JSON.parse(data_raw);
				for (i = 0, n = settings_element.options.length; i < n; i++) {
					settings_element.items.push(settings_element.options[i][field_value]);
				}
			}
		};
	
		/**
		 * Initializes selectize from a <select> element.
		 *
		 * @param {object} $input
		 * @param {object} settings_element
		 */
		var init_select = function($input, settings_element) {
			var i, n, tagName, $children, order = 0;
			var options = settings_element.options;
			var optionsMap = {};
	
			var readData = function($el) {
				var data = attr_data && $el.attr(attr_data);
				if (typeof data === 'string' && data.length) {
					return JSON.parse(data);
				}
				return null;
			};
	
			var addOption = function($option, group) {
				$option = $($option);
	
				var value = hash_key($option.val());
				if (!value && !settings.allowEmptyOption) return;
	
				// if the option already exists, it's probably been
				// duplicated in another optgroup. in this case, push
				// the current group to the "optgroup" property on the
				// existing option so that it's rendered in both places.
				if (optionsMap.hasOwnProperty(value)) {
					if (group) {
						var arr = optionsMap[value][field_optgroup];
						if (!arr) {
							optionsMap[value][field_optgroup] = group;
						} else if (!$.isArray(arr)) {
							optionsMap[value][field_optgroup] = [arr, group];
						} else {
							arr.push(group);
						}
					}
					return;
				}
	
				var option             = readData($option) || {};
				option[field_label]    = option[field_label] || $option.text();
				option[field_value]    = option[field_value] || value;
				option[field_optgroup] = option[field_optgroup] || group;
	
				optionsMap[value] = option;
				options.push(option);
	
				if ($option.is(':selected')) {
					settings_element.items.push(value);
				}
			};
	
			var addGroup = function($optgroup) {
				var i, n, id, optgroup, $options;
	
				$optgroup = $($optgroup);
				id = $optgroup.attr('label');
	
				if (id) {
					optgroup = readData($optgroup) || {};
					optgroup[field_optgroup_label] = id;
					optgroup[field_optgroup_value] = id;
					settings_element.optgroups.push(optgroup);
				}
	
				$options = $('option', $optgroup);
				for (i = 0, n = $options.length; i < n; i++) {
					addOption($options[i], id);
				}
			};
	
			settings_element.maxItems = $input.attr('multiple') ? null : 1;
	
			$children = $input.children();
			for (i = 0, n = $children.length; i < n; i++) {
				tagName = $children[i].tagName.toLowerCase();
				if (tagName === 'optgroup') {
					addGroup($children[i]);
				} else if (tagName === 'option') {
					addOption($children[i]);
				}
			}
		};
	
		return this.each(function() {
			if (this.selectize) return;
	
			var instance;
			var $input = $(this);
			var tag_name = this.tagName.toLowerCase();
			var placeholder = $input.attr('placeholder') || $input.attr('data-placeholder');
			if (!placeholder && !settings.allowEmptyOption) {
				placeholder = $input.children('option[value=""]').text();
			}
	
			var settings_element = {
				'placeholder' : placeholder,
				'options'     : [],
				'optgroups'   : [],
				'items'       : []
			};
	
			if (tag_name === 'select') {
				init_select($input, settings_element);
			} else {
				init_textbox($input, settings_element);
			}
	
			instance = new Selectize($input, $.extend(true, {}, defaults, settings_element, settings_user));
		});
	};
	
	$.fn.selectize.defaults = Selectize.defaults;
	$.fn.selectize.support = {
		validity: SUPPORTS_VALIDITY_API
	};
	
	
	Selectize.define('drag_drop', function(options) {
		if (!$.fn.sortable) throw new Error('The "drag_drop" plugin requires jQuery UI "sortable".');
		if (this.settings.mode !== 'multi') return;
		var self = this;
	
		self.lock = (function() {
			var original = self.lock;
			return function() {
				var sortable = self.$control.data('sortable');
				if (sortable) sortable.disable();
				return original.apply(self, arguments);
			};
		})();
	
		self.unlock = (function() {
			var original = self.unlock;
			return function() {
				var sortable = self.$control.data('sortable');
				if (sortable) sortable.enable();
				return original.apply(self, arguments);
			};
		})();
	
		self.setup = (function() {
			var original = self.setup;
			return function() {
				original.apply(this, arguments);
	
				var $control = self.$control.sortable({
					items: '[data-value]',
					forcePlaceholderSize: true,
					disabled: self.isLocked,
					start: function(e, ui) {
						ui.placeholder.css('width', ui.helper.css('width'));
						$control.css({overflow: 'visible'});
					},
					stop: function() {
						$control.css({overflow: 'hidden'});
						var active = self.$activeItems ? self.$activeItems.slice() : null;
						var values = [];
						$control.children('[data-value]').each(function() {
							values.push($(this).attr('data-value'));
						});
						self.setValue(values);
						self.setActiveItem(active);
					}
				});
			};
		})();
	
	});
	
	Selectize.define('dropdown_header', function(options) {
		var self = this;
	
		options = $.extend({
			title         : 'Untitled',
			headerClass   : 'selectize-dropdown-header',
			titleRowClass : 'selectize-dropdown-header-title',
			labelClass    : 'selectize-dropdown-header-label',
			closeClass    : 'selectize-dropdown-header-close',
	
			html: function(data) {
				return (
					'<div class="' + data.headerClass + '">' +
						'<div class="' + data.titleRowClass + '">' +
							'<span class="' + data.labelClass + '">' + data.title + '</span>' +
							'<a href="javascript:void(0)" class="' + data.closeClass + '">&times;</a>' +
						'</div>' +
					'</div>'
				);
			}
		}, options);
	
		self.setup = (function() {
			var original = self.setup;
			return function() {
				original.apply(self, arguments);
				self.$dropdown_header = $(options.html(options));
				self.$dropdown.prepend(self.$dropdown_header);
			};
		})();
	
	});
	
	Selectize.define('optgroup_columns', function(options) {
		var self = this;
	
		options = $.extend({
			equalizeWidth  : true,
			equalizeHeight : true
		}, options);
	
		this.getAdjacentOption = function($option, direction) {
			var $options = $option.closest('[data-group]').find('[data-selectable]');
			var index    = $options.index($option) + direction;
	
			return index >= 0 && index < $options.length ? $options.eq(index) : $();
		};
	
		this.onKeyDown = (function() {
			var original = self.onKeyDown;
			return function(e) {
				var index, $option, $options, $optgroup;
	
				if (this.isOpen && (e.keyCode === KEY_LEFT || e.keyCode === KEY_RIGHT)) {
					self.ignoreHover = true;
					$optgroup = this.$activeOption.closest('[data-group]');
					index = $optgroup.find('[data-selectable]').index(this.$activeOption);
	
					if(e.keyCode === KEY_LEFT) {
						$optgroup = $optgroup.prev('[data-group]');
					} else {
						$optgroup = $optgroup.next('[data-group]');
					}
	
					$options = $optgroup.find('[data-selectable]');
					$option  = $options.eq(Math.min($options.length - 1, index));
					if ($option.length) {
						this.setActiveOption($option);
					}
					return;
				}
	
				return original.apply(this, arguments);
			};
		})();
	
		var getScrollbarWidth = function() {
			var div;
			var width = getScrollbarWidth.width;
			var doc = document;
	
			if (typeof width === 'undefined') {
				div = doc.createElement('div');
				div.innerHTML = '<div style="width:50px;height:50px;position:absolute;left:-50px;top:-50px;overflow:auto;"><div style="width:1px;height:100px;"></div></div>';
				div = div.firstChild;
				doc.body.appendChild(div);
				width = getScrollbarWidth.width = div.offsetWidth - div.clientWidth;
				doc.body.removeChild(div);
			}
			return width;
		};
	
		var equalizeSizes = function() {
			var i, n, height_max, width, width_last, width_parent, $optgroups;
	
			$optgroups = $('[data-group]', self.$dropdown_content);
			n = $optgroups.length;
			if (!n || !self.$dropdown_content.width()) return;
	
			if (options.equalizeHeight) {
				height_max = 0;
				for (i = 0; i < n; i++) {
					height_max = Math.max(height_max, $optgroups.eq(i).height());
				}
				$optgroups.css({height: height_max});
			}
	
			if (options.equalizeWidth) {
				width_parent = self.$dropdown_content.innerWidth() - getScrollbarWidth();
				width = Math.round(width_parent / n);
				$optgroups.css({width: width});
				if (n > 1) {
					width_last = width_parent - width * (n - 1);
					$optgroups.eq(n - 1).css({width: width_last});
				}
			}
		};
	
		if (options.equalizeHeight || options.equalizeWidth) {
			hook.after(this, 'positionDropdown', equalizeSizes);
			hook.after(this, 'refreshOptions', equalizeSizes);
		}
	
	
	});
	
	Selectize.define('remove_button', function(options) {
		options = $.extend({
				label     : '&times;',
				title     : 'Remove',
				className : 'remove',
				append    : true
			}, options);
	
			var singleClose = function(thisRef, options) {
	
				options.className = 'remove-single';
	
				var self = thisRef;
				var html = '<a href="javascript:void(0)" class="' + options.className + '" tabindex="-1" title="' + escape_html(options.title) + '">' + options.label + '</a>';
	
				/**
				 * Appends an element as a child (with raw HTML).
				 *
				 * @param {string} html_container
				 * @param {string} html_element
				 * @return {string}
				 */
				var append = function(html_container, html_element) {
					return html_container + html_element;
				};
	
				thisRef.setup = (function() {
					var original = self.setup;
					return function() {
						// override the item rendering method to add the button to each
						if (options.append) {
							var id = $(self.$input.context).attr('id');
							var selectizer = $('#'+id);
	
							var render_item = self.settings.render.item;
							self.settings.render.item = function(data) {
								return append(render_item.apply(thisRef, arguments), html);
							};
						}
	
						original.apply(thisRef, arguments);
	
						// add event listener
						thisRef.$control.on('click', '.' + options.className, function(e) {
							e.preventDefault();
							if (self.isLocked) return;
	
							self.clear();
						});
	
					};
				})();
			};
	
			var multiClose = function(thisRef, options) {
	
				var self = thisRef;
				var html = '<a href="javascript:void(0)" class="' + options.className + '" tabindex="-1" title="' + escape_html(options.title) + '">' + options.label + '</a>';
	
				/**
				 * Appends an element as a child (with raw HTML).
				 *
				 * @param {string} html_container
				 * @param {string} html_element
				 * @return {string}
				 */
				var append = function(html_container, html_element) {
					var pos = html_container.search(/(<\/[^>]+>\s*)$/);
					return html_container.substring(0, pos) + html_element + html_container.substring(pos);
				};
	
				thisRef.setup = (function() {
					var original = self.setup;
					return function() {
						// override the item rendering method to add the button to each
						if (options.append) {
							var render_item = self.settings.render.item;
							self.settings.render.item = function(data) {
								return append(render_item.apply(thisRef, arguments), html);
							};
						}
	
						original.apply(thisRef, arguments);
	
						// add event listener
						thisRef.$control.on('click', '.' + options.className, function(e) {
							e.preventDefault();
							if (self.isLocked) return;
	
							var $item = $(e.currentTarget).parent();
							self.setActiveItem($item);
							if (self.deleteSelection()) {
								self.setCaret(self.items.length);
							}
						});
	
					};
				})();
			};
	
			if (this.settings.mode === 'single') {
				singleClose(this, options);
				return;
			} else {
				multiClose(this, options);
			}
	});
	
	
	Selectize.define('restore_on_backspace', function(options) {
		var self = this;
	
		options.text = options.text || function(option) {
			return option[this.settings.labelField];
		};
	
		this.onKeyDown = (function() {
			var original = self.onKeyDown;
			return function(e) {
				var index, option;
				if (e.keyCode === KEY_BACKSPACE && this.$control_input.val() === '' && !this.$activeItems.length) {
					index = this.caretPos - 1;
					if (index >= 0 && index < this.items.length) {
						option = this.options[this.items[index]];
						if (this.deleteSelection(e)) {
							this.setTextboxValue(options.text.apply(this, [option]));
							this.refreshOptions(true);
						}
						e.preventDefault();
						return;
					}
				}
				return original.apply(this, arguments);
			};
		})();
	});
	

	return Selectize;
}));
/*!
 * WHMCS Dynamic Client Dropdown Library
 *
 * Based upon Selectize.js
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

$(document).ready(function(){

    Selectize.define('whmcs_no_results', function(options) {
        var self = this;
        this.search = (function() {
            var original = self.search;

            return function() {
                var results = original.apply(this, arguments);

                var isActualItem = function (item) {
                    // item.id may be 'client' - this is an actual item
                    return isNaN(item.id) || item.id > 0;
                };

                var actualItems = results.items.filter(function (item) {
                    return isActualItem(item);
                });

                var noResultsItems = results.items.filter(function (item) {
                    return !isActualItem(item);
                });

                if (actualItems.length > 0) {
                    results.items = actualItems;
                } else if (noResultsItems.length > 0) {
                    results.items = [noResultsItems[0]];
                }

                return results;
            };
        })();
    });

    if (typeof WHMCS.selectize !== "undefined") {
        jQuery('.selectize-client-search').data('search-url', getClientSearchPostUrl());
        WHMCS.selectize.clientSearch();
    } else {
        var clientDropdown = jQuery(".selectize-client-search");

        var clientSearchSelectize = clientDropdown.selectize(
            {
                plugins: ['whmcs_no_results'],
                valueField: clientDropdown.data('value-field'),
                labelField: 'name',
                searchField: ['name', 'email', 'companyname'],
                create: false,
                maxItems: 1,
                preload: 'focus',
                optgroupField: 'status',
                optgroupLabelField: 'name',
                optgroupValueField: 'id',
                optgroups: [
                    {$order: 1, id: 'active', name: clientDropdown.data('active-label')},
                    {$order: 2, id: 'inactive', name: clientDropdown.data('inactive-label')}
                ],
                render: {
                    item: function (item, escape) {
                        if (typeof dropdownSelectClient == "function") {
                            dropdownSelectClient(
                                escape(item.id),
                                escape(item.name) + (item.companyname ? ' (' + escape(item.companyname) + ')' : '') +
                                (item.id > 0 ? ' - #' + escape(item.id) : ''),
                                escape(item.email)
                            );
                        }
                        return '<div><span class="name">' + escape(item.name) +
                            (item.companyname ? ' (' + escape(item.companyname) + ')' : '') +
                            (item.id > 0 ? ' - #' + escape(item.id) : '') + '</span></div>';
                    },
                    option: function (item, escape) {
                        return '<div><span class="name">'
                            + escape(item.name) + (item.companyname ? ' (' + escape(item.companyname) + ')' : '')
                            + (item.id > 0 ? ' - #' + escape(item.id) : '') + '</span>' +
                            (item.email ? '<span class="email">' + escape(item.email) + '</span>' : '') + '</div>';
                    }
                },
                load: function (query, callback) {
                    jQuery.ajax({
                        url: getClientSearchPostUrl(),
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            dropdownsearchq: query,
                            clientId: currentValue
                        },
                        error: function () {
                            callback();
                        },
                        success: function (res) {
                            callback(res);
                        }
                    });
                },
                score: function(search) {
                    var score = this.getScoreFunction(search);
                    return function(item) {
                        var thisScore = score(item);
                        if (thisScore && item.status === 'inactive') {
                            thisScore = 0.0000001;
                        }
                        return thisScore;
                    };
                },
                onChange: function (value) {
                    if (jQuery('#goButton').length) {
                        if (value.length && value != currentValue) {
                            jQuery('#goButton').click();
                        }
                    }
                },
                onFocus: function () {
                    currentValue = clientSearchSelectize.getValue();
                    clientSearchSelectize.clear();
                },
                onBlur: function () {
                    if (clientSearchSelectize.getValue() == '' || clientSearchSelectize.getValue() < 1) {
                        clientSearchSelectize.setValue(currentValue);
                    }
                }
            });
        var currentValue = '';

        if (clientSearchSelectize.length) {
            /**
             * selectize assigns any items to an array. In order to be able to run additional
             * functions on this (like auto-submit and clear).
             *
             * @link https://github.com/brianreavis/selectize.js/blob/master/examples/api.html
             */
            clientSearchSelectize = clientSearchSelectize[0].selectize;
        }
    }
});
