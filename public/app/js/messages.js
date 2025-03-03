const btnOkCancel = {btnOk: {title: 'Aceptar'}, btnCancel: {title: 'Cancelar'}},
    btnYesCancel = {btnOk: {title: 'Sí'}, btnCancel: {title: 'Cancelar'}},
    btnYesNoCancel = {btnOk: {title: 'Sí'}, btnNo: {title: 'No'}, btnCancel: {title: 'Cancelar'}},
    btnYesNo = {btnOk: {title: 'Sí'}, btnNo: {title: 'No'}},
    btnClose = {btnCancel: {title: 'Cerrar'}},
    btnAccept = {btnOk: {title: 'Aceptar'}};

//Devuelve un jQuery para un cuadro modal
//title: Título del cuadro
//content: Contenido a mostrar
//buttons: Objeto cuyas llaves son el id del botón y sus valores son objetos con las propiedades: title, type
///Opciones
//  icon: Font-awesome icon (sin fa-)
//  noForm: Si es true no se encerrará el contenido dentro de un formulario
//  animate: true/false si se desea mostrar la animación del cuadro. Por defecto true
function getDlgContent(title, content, buttons, opts) {
    var btns = '';
    if (typeof (buttons) == 'string')
        btns = buttons;
    else {
        $.each(buttons, function (i, e) {
            var css = e.class ? ' ' + e.class : '',
                attr = e.attr ? ' ' + e.attr : '';

            switch (i) {
                case 'btnCancel':
                    btns += '<button id="btnCancel" type="button" data-skip="1" class="btn btn-md btn-default ml-2' + css + '"' + attr + ' data-dismiss="modal">' + e.title + '</button>';
                    break;
                case 'btnOk':
                    btns += '<button id="btnOk" type="button" data-skip="1" class="btn btn-md btn-primary ml-2' + css + '"' + attr + '>' + e.title + '</button>';
                    break;
                case 'btnNo':
                    btns += '<button id="btnNo" type="button" data-skip="1" class="btn btn-md btn-default ml-2' + css + '"' + attr + '>' + e.title + '</button>';
                    break;
                default:
                    btns += '<button id="' + i + '" type="' + (e.type ? e.type : 'button') + '" data-skip="1" class="btn btn-md btn-default ml-2' + css + '"' + attr + '>' + e.title + '</button>';
            }
        });
    }

    opts = opts || {animate: true};
    if (typeof (opts.animate) == 'undefined')
        opts.animate = true;
    if (typeof (opts.noButtons) == 'undefined')
        opts.noButtons = false;
    if (typeof (opts.size) == 'undefined')
        opts.size = 'lg';
    if (typeof (opts.closeButton) == 'undefined')
        opts.closeButton = true;

    opts.icon = opts.icon ? '<i class="fa fa-' + opts.icon + '"></i>&nbsp;' : '';

    var ret = $('<div class="modal' + (opts.animate ? ' fade' : '') + '" id="modal-default" role="dialog">\
        <div class="modal-dialog modal-dialog-centered modal-' + opts.size + '">\
          <div class="modal-content">' +
        (title ? '<div class="modal-header py-2">\
              <h4 class="modal-title lh-3 my-2">' + opts.icon + title + '</h4>' +
            (opts.closeButton ? '<button type="button" class="close no-focus py-2 mt-0" data-dismiss="modal" aria-label="Close">\
                <span aria-hidden="true">&times;</span>\
              </button>' : '') +
            '</div>' : '') +
        (opts.noForm ? '' : '<form role="form" class="form">') +
        '<div class="modal-body">' + (content instanceof jQuery ? '' : content) + '</div>' +
        (opts.noButtons ? '' : '<div class="modal-footer justify-content-end"><div class="d-flex flex-nowrap">' + btns + '</div></div>') +
        '</div>' +
        (opts.noForm ? '' : '</form>') +
        (typeof (opts.afterBody) == 'undefined' ? '' : opts.afterBody) +
        '</div>\
    </div>')
        .on('show.bs.modal', function (e) {
            e.target.prevFocus = document.prevFocus;
        })
        .on('hidden.bs.modal', function () {
            $(this).remove();
            $('.tooltip').tooltip('hide');
        });

    if (content instanceof jQuery)
        $('.modal-body', ret).append(content);

    return ret;
}

function showDlgMessage(title, content, icon, opts) {
    var dlg = getDlgContent(title || 'Aviso', content, {btnCancel: {title: 'Cerrar'}}, {
        icon: icon,
        size: opts.size || 'lg',
        noForm: opts.noForm || 0,
        noButtons: opts.noButtons || false
    });

    var kbCancel = opts.escape || false;

    dlg = dlg.modal({
        show: false,
        keyboard: !kbCancel,
        backdrop: 'static'
    });

    if (typeof (opts) === 'undefined')
        opts = {};

    if (typeof (opts.onshow) === 'function')
        dlg.on('show.bs.modal', opts.onshow);
    if (typeof (opts.onshown) === 'function')
        dlg.on('shown.bs.modal', opts.onshown);
    if (typeof (opts.onhide) === 'function')
        dlg.on('hide.bs.modal', opts.onhide);
    if (typeof (opts.onhidden) === 'function')
        dlg.on('hidden.bs.modal', opts.onhidden);

    if (kbCancel) {
        $('body').on('keyup.myModalEvents', function (e) {
            e.stopPropagation();
            if (e.keyCode === 27)
                dlg.modal('hide');
        });
    }

    if (typeof (opts.show) === 'undefined' || opts.show) //show = true por defecto
        dlg.modal('show');

    return dlg;
}

//Muestra un cuadro de confirmación
//content: Contenido a mostrar
//callbacks: Objeto cuyas llaves puede ser: ok, no, yes, cancel y el valor es la función a ejecutar; en dependencia de los botones que se utilicen
//remove: Si es true (por defecto), elimina los elementos del DOM tras ocultarse el cuadro
function showConfirm(content, callbacks, buttons, remove, backdrop, title, opts) {
    var f = $(':focus').blur(),
        hiding = false,
        dlg = getDlgContent(title || 'Confirmación', content, buttons || btnOkCancel, opts || {icon: 'check', size: 'md'})
            .on('hide.bs.modal', function () {
                hiding = true;
            })
            .on('hidden.bs.modal', function () {
                if (typeof (remove) == 'undefined' || remove)
                    dlg.remove();

                try {
                    if (f.length)
                        f[0].focus();
                } catch (e) {
                }
            })/*
					.on('keydown', function(e) {
						if (e.keyCode === 13 && !$('#btnCancel', dlg).is(':focus')) {
							e.preventDefault();
							$('#btnOk', dlg).click();
						}
					})*/;

    $('button[id^="btn"][data-skip]', dlg).click(function (e) {
        e.preventDefault();

        if (!hiding) {
            let skipHide = true;
            let prop = $(this).attr('id').substring(3).toLowerCase();
            if (typeof callbacks[prop] === 'function')
                skipHide = callbacks[prop](dlg);

            if (!skipHide)
                dlg.modal('hide');
        }
    });

    dlg.modal({keyboard: true, backdrop: backdrop ? 'static' : ''});
    dlg.okFailed = function () {
        hiding = false;
    }

    return dlg;
}

//Muestra un cuadro de alerta
//content: Contenido a mostrar
//okFn: Función a ejecutar cuando se cierre el cuadro
function showAlert(content, okFn, opts) {
    $(':focus').blur();

    var hiding = false,
        body = $('body').on('keydown.msgEvent', function (e) {
            if (e.keyCode === 13 || e.keyCode === 27) {
                e.preventDefault();
                $('#btnOk', dlg).click();
            }
        }),
        dlg = getDlgContent(null, content, btnAccept, opts)
            .on('hide.bs.modal', function () {
                hiding = true;
                dlg.removeClass('fast zoomIn fadeInUp').addClass('moderated zoomOut');
            })
            .on('hidden.bs.modal', function () {
                body.off('keydown.msgEvent');

                if (typeof (remove) == 'undefined' || remove)
                    dlg.remove();

                if (typeof (okFn) == 'function')
                    okFn();
            });

    $('#btnOk', dlg).click(function (e) {
        e.preventDefault();

        if (!hiding)
            dlg.modal('hide');
    });

    dlg.addClass('animated fast zoomIn fadeInUp').modal({keyboard: !false, backdrop: 'static'});
}

/* Ventana de alerta estática. Se debe cerrar manualmente con la función <valor devuelto>.remove() */
function showStaticAlert(content, okFn) {
    var dlg = getDlgContent(null, '<h3>' + content + '</h3>', '');

    dlg.remove = function () {
        dlg.off().on('hidden.bs.modal', function (e) {
            dlg.remove();
            if (typeof (okFn) == 'function')
                okFn();
        }).modal('hide');
    };

    dlg.modal({keyboard: false, backdrop: 'static'});

    return dlg;
}

/**
 * Muestra un cuadro de introducción de datos
 * @param title
 * Título de la etiqueta LABEL
 * @param defaultValue
 * @param okFn
 * Función a ejecutar si se acepta. Debe esperar un parámetro con el valor especificado
 * @param validatorOpts
 * Opciones de validación. Es un objeto con las llaves:
 *    rules: Reglas de validación data-rule
 *    regExp: Opcional. Si las reglas incluyen expresiones regulares. Ejemplo: {re1: '^a.*$', re2: 'a$'}
 *    msg: Opcional. Mensajes para cada regla separados por comas
 *    custom: Reglas personalizadas. Ejemplo: [{name: 'myCheck', fn: function (val, e) {...}, msg: '...'}, ...];
 */
function showPrompt(title, defaultValue, okFn, validatorOpts) {
    var rules = '';

    if (validatorOpts) {
        rules = 'data-tooltipat="self" data-tooltip="toggle"';

        if (validatorOpts.tooltipPlace)
            rules += ' data-tooltip-place="' + validatorOpts.tooltipPlace + '"';

        if (validatorOpts.rules)
            rules += ' data-rule="' + validatorOpts.rules + '"';

        if (validatorOpts.msg)
            rules += ' data-msg="' + validatorOpts.msg + '"';

        if (validatorOpts.regExp)
            $.each(validatorOpts.regExp, function (i, e) {
                rules += ' data-' + i + '="' + e + '"';
            });

        if (rules.length === 43 && 'getRulesFrom' in validatorOpts) {
            var f = validatorOpts.getRulesFrom;

            if (typeof (f) === 'string')
                f = $(f);

            if (f instanceof jQuery) {
                var r = f.data('rule');

                if (r) {
                    rules += 'data-rule="' + r + '" ';

                    r.replace(/regexp\[([^\]]+)\]/img, function (s) {
                        var rn = s.substr(7, s.length - 8),
                            re = f.data(rn);
                        if (re)
                            rules += 'data-' + rn + '="' + re + '" ';
                    });

                    if ((r = f.data('msg')))
                        rules += 'data-msg="' + r + '" ';
                }
            }
        }
    }

    var pref = '', sufx = '', dltTitle = null;
    if (typeof (title) == 'object') {
        if (title.padBefore)
            pref = title.padBefore;
        if (title.padAfter)
            sufx = title.padAfter;
        if (title.dlgTitle)
            dltTitle = title.dlgTitle;
        title = title.title ? title.title : '';
    }

    var dlg = getDlgContent(dltTitle, pref + '<label for="txtInp">' + title + '</label><br/><input class="form-control" ' + rules + ' type="text" id="txtInp" autocomplete="off"/>' + sufx, btnOkCancel, {size: 'md'})
        .on('shown.bs.modal', function () {
            txtInp.focus();
        })
        .on('hide.bs.modal', function () {
            txtInp.tooltip('dispose');
        })
        .on('hidden.bs.modal', function () {
            dlg.remove();
        })
        .on('keydown', function (e) {
            if (e.keyCode === 13 && !$('#btnCancel', dlg).is(':focus')) {
                e.preventDefault();
                $('#btnOk', dlg).click();
            }
        });

    var txtInp = $('#txtInp', dlg).val(defaultValue);

    $('#btnOk', dlg).click(function (e) {
        e.preventDefault();

        if (rules) {
            var v = new Validator($('form', dlg));

            if (validatorOpts.custom)
                $.each(validatorOpts.custom, function (i, e) {
                    v.addRule(e.name, e.fn, e.msg);
                });

            if (!v.validate())
                return;
        }

        dlg.on('hidden.bs.modal', function (e) {
            okFn(txtInp.val());
        }).modal('hide');
    });

    dlg.modal({keyboard: true, backdrop: 'static'});
}

//Mensajes flash. Aparecen en la esquina inferior derecha.
var __MSG_INFO = 'info',
    __MSG_SUCCESS = 'success',
    __MSG_ERROR = 'error',
    __MSG_WARNING = 'warning';

sysMsgs = new function () {
    var t = this;
    var titles = {info: 'Información', success: 'Operación exitosa', error: 'Error', warning: 'Advertencia'};

    //Muestra un mensaje según el tipo (info (por defecto), warning, success, error)
    t.show = function (msg, tp, rm, time) {
        toastr.options = {
            closeButton: true,
            debug: false,
            newestOnTop: true,
            progressBar: true,
            positionClass: "toast-bottom-left",
            preventDuplicates: false,
            onclick: null,
            showDuration: 300,
            hideDuration: 300,
            timeOut: time || 5000,
            extendedTimeOut: 1000,
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut"
        };

        if (rm)
            toastr.clear();

        tp = (tp || 'info').toLowerCase();
        if ('success|info|warning|error'.indexOf(tp) !== -1)
            toastr[tp](msg, titles[tp]);
    };

    t.clear = function () {
        toastr.clear();
    };

    //Mostrar los mensajes enviados con la página a través de la variable sysMessages
    if (typeof (sysMessages) !== 'undefined')
        $.each(sysMessages, function (i, e) {
            t.show(e.msg, e.type);
        });
};