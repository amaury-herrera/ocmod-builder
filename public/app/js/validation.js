/**
 * Crea una instancia de Validator
 * @param form
 * Si se especifica es el id de la etiqueta FORM, de lo contrario se toma el primer FORM
 * @param elements
 * Solo válido cuando form no se especifica. Debe ser un array con los elementos
 * @param tooltips
 * Usar false si no se desea mostrar los tooltips de errores
 */
function Validator(form, elements, tooltips, options) {
    var t = this;

    t.tooltips = arguments.length < 3 ? true : tooltips;
    t.suffixIndex = 0;
    t.summary = [];
    t.elements = [];

    if (typeof (options) !== 'object')
        options = {extraElements: null};
    else if (typeof (options.extraElements) === 'undefined')
        options.extraElements = null;

    t.options = options;
    t.currentElement = null;

    if (form) {
        if (form instanceof jQuery)
            t.form = form;
        else
            t.form = $('form#' + form);
    } else {
        if (elements) {
            t.elemList = elements;
            t.refreshControls();
            return;
        } else if (document.forms.length > 0)
            t.form = $(document.forms[0]);
        else
            return;
    }

    if (!t.form.is('form'))
        return;

    t.refreshControls();
}

Validator.prototype = {
    _messages: {
        required: 'Debe llenar este campo',
        required_trim: 'Debe llenar este campo',
        trim: '',
        trim_spaces: '',
        ifchecked: 'Debe llenar este campo',
        ifselectedvalue: 'Debe llenar este campo',
        ifselectedindex: 'Debe llenar este campo',
        email: 'Especifique una dirección de correo válida',
        url: 'Especifique un URL válido',
        date: 'Escriba una fecha válida',
        list: 'El valor no se incluye entre los permitidos',
        between: 'Escriba un valor entre % y %',
        lt: 'Escriba un número menor que %',
        gt: 'Escriba un número mayor que %',
        lte: 'Escriba un número menor o igual que %',
        gte: 'Escriba un número mayor o igual que %',
        integer: 'Escriba un número entero',
        number: 'Escriba un número válido',
        money: 'Escriba un valor monetario válido',
        alpha: 'Escriba solo letras en este campo',
        alphanum: 'Escriba solo letras y números en este campo',
        without: 'El valor especificado contiene caracteres no permitidos',
        digits: 'Escriba solo números en este campo',
        minlength: 'Debe escribir al menos % caracteres',
        maxlength: 'El valor especificado contiene más de % caracteres',
        exactlength: 'La longitud debe ser exactamente % caracteres',
        regexp: 'El valor no tiene el formato requerido',
        neg_regexp: 'El valor no tiene el formato requerido',
        match: 'Los valores especificados no coinciden',
        any: 'Debe activar algún elemento'
    },

    errorClasses: 'has-error is-invalid',

    //Actualiza los controles. Utilizar antes de validar cuando se combine con Knockout.js
    refreshControls: function (includeAll) {
        var t = this;

        if (!t.form && t.elements.length > 0)
            return;

        var getElemData = function (k, e) {
            if (typeof (e) !== 'object')
                return;

            e = $(e);
            e.removeData('getMD5');

            if (!includeAll)
                if (e.is(':disabled') || (t.form && e.data('skip')) || (e.is(':hidden') && e.data('skipifhidden') && !e.data('force-evaluate')))
                    return;

            var ind = oldElems.indexOf(e[0]);
            if (ind >= 0) {
                t.elements.push(oldObjs[ind]);
                oldElems.splice(ind, 1);
                oldObjs.splice(ind, 1);
                return;
            }

            var rule = e.data('rule');
            if (rule && (rule = rule.trim())) {
                var rs = rule.split(','), i = 0, rules = [], required = false;
                while (i < rs.length) {
                    var rl = rs[i].split('[');
                    rl[0] = rl[0].trim().toLowerCase();
                    if (rl.length > 1)
                        rl[1] = rl[1].trim();

                    if (rl[0] === 'required' || rl[0] === 'required_trim') {
                        if (!required) {
                            required = true;
                            rules.unshift(rl[0]);
                        }
                    } else if (rl[0] === 'ifchecked') {
                        if (!required) {
                            required = true;
                            rules.unshift(rl.join('['));
                        }
                    } else
                        /*if (['ifchecked', 'ifselectedvalue', 'ifselectedindex'].indexOf(rl[0]) >= 0) {
                                if (!required) {
                                    required = true;
                                    rules.unshift(rl.join('['));
                                }
                            } else*/
                        rules.push(rl.join('['));

                    i++;
                }

                //var pr = e.parentsUntil('.form-group');
                //if (pr.length === 0)
                //pr = e.parentsUntil('.input-group');

                var parent = e.parent();//(pr.length > 0 ? pr : e.wrap('<div class="form-group" style="display: inline">')).parent();

                if (parent.is('body'))
                    parent = e.parentsUntil(':visible').parent();

                var msg = e.data('msg'),
                    o = {
                        e: e,
                        r: rules,
                        m: msg ? msg.trim().split('|') : [],
                        p: parent,
                        isCBRB: e.is(':checkbox') || e.is(':radio'),
                        onTooltipHidden: null
                    };

                var ttAt = e.data('tooltipat'), tt = e;
                if (ttAt) {
                    if (ttAt === 'self')
                        tt = e;
                    else if (ttAt === 'parent')
                        tt = e.parent();
                    else {
                        var ttp = $('#' + ttAt);
                        if (ttp.length === 1)
                            tt = ttp;
                        else
                            ttAt = false;
                    }
                }

                o.ttel = o.isCBRB ? o.p.first() : tt;
                o.ttopts = {
                    container: ttAt ? tt : (o.isCBRB ? o.ttel : o.p),
                    trigger: 'manual',
                    delay: 5000
                };

                o.ttel.attr('data-placement', e.data('tooltip-place') || 'top');
                o.ttel.attr('data-toggle', 'tooltip');
                o.ttel.tooltip(o.ttopts);

                t.elements.push(o);
            }
        };

        var oldElems = [], oldObjs = [];
        $.each(t.elements, function (i, d) {
            oldElems.push(d.e[0]);
            oldObjs.push(d);
        });

        t.elements = [];
        $.each(t.form ? t.form[0].elements : t.elemList, getElemData);

        if (t.options.extraElements)
            $.each(t.options.extraElements, getElemData);

        $.each(oldObjs, t.__$clearOne);

        delete t.elemList;
    },

    _empty: function (e, trim) {
        var v = this._elemValue(e);
        return v === null || v === false || v.length === 0 || (trim && v.toString().trim().length === 0);
    },

    //Cuando se combina con Knockout.js permite que los valores se obtengan a partir del observable y no de $.val()
    //$e es el objeto jQuery del control. obs es el valor observable
    registerObservable: function ($e, obs) {
        if ($e.length && typeof (obs) === 'function')
            $e[0].koObservable = obs;
    },

    _val: function (e) {
        return e.koObservable ? e.koObservable() : $(e).val();
    },

    _elemValue: function (e) {
        switch (e.tagName) {
            case 'INPUT':
                return e.type === 'checkbox' || e.type === 'radio'
                    ? (e.checked ? e.value : false)
                    : this._val(e);

            case 'TEXTAREA':
                return this._val(e);

            case 'SELECT':
                if (e.multiple) {
                    var v = [];
                    for (var o = 0; o < e.options.length; o++) {
                        var op = e.options[o];
                        if (op.selected)
                            v.push(op.value);
                    }
                    return v;
                } else if (e.options.length > 0)
                    return this._val(e);
                return '';

            default :
                return '';
        }
    },

    addRule: function (name, fn, msg) {
        if (typeof (name) == 'string') {
            name = name.trim().toLowerCase();
            if (typeof (fn) == 'function' && /^[a-zA-Z0-9_]+$/.test(name) && !this[name]) {
                this[name] = fn;
                this._messages[name] = typeof (msg) == 'function' ? msg : (msg.trim() || 'No válido');
            }
        }
        return this;
    },

    $lgthan: function (al, name, v, l, op) {
        if (al == 3) {
            v = parseFloat(v);
            if (isNaN(v))
                return false;

            l = parseFloat(l);
            if (isNaN(l))
                return false;

            var r = true;
            eval('r = v' + op + 'l');
            return r;
        } else
            console.log('La regla ' + name + ' espera un parámetro numérico');

        return false;
    },

//Valor requerido
    required: function (v, e) {
        return !this._empty(e[0]);
    },

//Valor requerido
    required_trim: function (v, e) {
        return !this._empty(e[0], 1);
    },

//Valor requerido solo si un radio o checkbox está marcado
    ifchecked: function (v, e, id) {
        var el = $('#' + id);
        if (el.length === 1) {
            if (el.is(':checked'))
                return v !== null && v !== false && v.length !== 0;
        } else
            console.log('ifchecked: No se ha encontrado el elemento con id: ' + id);

        return null; //Para pasar por alto el control en la validación
    },
    /*
    //Valor requerido solo si está activa una opción de un select
        ifselectedvalue: function (v, e, id, value) {
            var el = $('select#' + id);
            if (el.length === 1)
                return el.val() === value;

            console.log('ifselected: No se ha encontrado el elemento con id: ' + id);
            return null; //Para pasar por alto el control en la validación
        },

    //Valor requerido solo si está activo el indice especificado de un select
        ifselectedindex: function (v, e, id, index) {
            var el = $('select#' + id);
            if (el.length === 1)
                return el[0].selectedIndex === parseInt(index);

            console.log('ifselected: No se ha encontrado el elemento con id: ' + id);
            return null; //Para pasar por alto el control en la validación
        },
    */
//Valor dentro de un rango (inclusivo)
    between: function (v, e, p1, p2) {
        if (arguments.length == 4) {
            v = parseFloat(v);
            if (isNaN(v))
                return false;

            p1 = parseFloat(p1);
            p2 = parseFloat(p2);
            if (isNaN(p1) || isNaN(p2))
                console.log('Parámetros de between no válidos');
            else
                return p1 < p2 ? v >= p1 && v <= p2 : v >= p2 && v <= p1;
        } else
            console.log('La regla between debe contener dos parámetros numéricos');

        return false;
    },

//Menor que
    lt: function (v, e, l) {
        return this.$lgthan(arguments.length, 'less', v, l, '<');
    },

//Mayor que
    gt: function (v, e, l) {
        return this.$lgthan(arguments.length, 'greater', v, l, '>');
    },

//Menor o igual que
    lte: function (v, e, l) {
        return this.$lgthan(arguments.length, 'lessOrEqual', v, l, '<=');
    },

//Mayor o igual que
    gte: function (v, e, l) {
        return this.$lgthan(arguments.length, 'greaterOrEqual', v, l, '>=');
    },

//Número entero. Admite hasta 10 cifras
    integer: function (v) {
        return /^[\-]?[0-9]{1,10}$/.test(v);
    },

//Número válido. Admite signo, 10 cifras enteras y 4 decimales
    number: function (v) {
        return /^[\-]?[0-9]{1,10}\.?[0-9]{0,4}?$/.test(v);
    },

    //Valor monetario válido. Sin signo, 10(entera).2(decimal)
    money: function (v) {
        return /^[0-9]{1,10}\.?[0-9]{0,2}?$/.test(v);
    },

//Solo caracteres a-z, A-Z. Si accents se evalúa como true se incluyen los caracteres acentuados. Si spaces se evalúa
//como true se incluyen espacios
    alpha: function (v, e, accents, spaces) {
        return (parseInt(accents)
                ? (parseInt(spaces) ? /^[a-zñÑáéíóúÁÉÍÓÚüÜ\s]+$/i : /^[a-zñÑáéíóúÁÉÍÓÚüÜ]+$/i)
                : (parseInt(spaces) ? /^[a-z\s]+$/i : /^[a-zA-Z]+$/)
        ).test(v);
    },

//Solo letras y números
    alphanum: function (v, e, accents, spaces) {
        return (parseInt(accents)
                ? (parseInt(spaces)
                    ? /^[\sa-z0-9ñÑáéíóúÁÉÍÓÚüÜ]+$/i
                    : /^[a-z0-9ñÑáéíóúÁÉÍÓÚüÜ]+$/i)
                : (parseInt(spaces)
                    ? /^[a-z0-9\s]+$/i
                    : /\W/)
        ).test(v);
    },

    without: function (v, e, chars) {
        chars = chars.replace(/\*\^|\<|\{|\[|\(|\||\)|\]|\}|\>|\$|\\|\/|comma|quote|lbracket|rbracket|lt|gt/g, function (x) {
            if (x.length == 1)
                return '\\' + x;

            switch (x) {
                case 'comma':
                    return ',';
                case 'quote':
                    return '"';
                case 'lbracket':
                    return '\\[';
                case 'rbracket':
                    return '\\]';
                case 'lt':
                    return '\\<';
                case 'gt':
                    return '\\>';
            }

            return x;
        });

        if (typeof (chars) == 'string')
            return !(new RegExp('[' + chars + ']+', 'gim')).test(v);

        console.log('without: Debe especificar un string con los caracteres no permitidos');
    },

//Solo números
    digits: function (v) {
        return /^\d+$/.test(v);
    },

//Longitud mínima
    minlength: function (v, e, l) {
        if (arguments.length == 3) {
            l = parseInt(l);
            if (!isNaN(l))
                return v.length >= l;
        }
        console.log('MinLength espera un valor entero');
        return false;
    },

//Longitud máxima
    maxlength: function (v, e, l) {
        if (arguments.length == 3) {
            l = parseInt(l);
            if (!isNaN(l))
                return v.length <= l;
        }
        console.log('MaxLength espera un valor entero');
        return false;
    },

//Longitud exacta
    exactlength: function (v, e, l) {
        if (arguments.length == 3) {
            l = parseInt(l);
            if (!isNaN(l))
                return v.length == l;
        }
        console.log('ExactLength espera un valor entero');
        return false;
    },

//Lista de valores
    list: function (v, e) {
        var l = arguments.length;
        if (l == 2)
            return false;

        var a = 2;
        while (a < l)
            if (arguments[a++] == v)
                return true;

        return false;
    },

//Emparejamiento con otro campo
    match: function (v, e, m) {
        if (this.form) {
            if ((e = e[0].form[m]))
                return v == this._elemValue(e);
        } else {
            e = $.grep(this.elements, function (v) {
                return v.e.attr('name') == m || v.e.attr('id') == m;
            });

            if (e.length)
                return v == this._elemValue(e[0].e[0]);
        }

        console.log('No existe ningún elemento con id o name: ' + m);
        return false;
    },

//Dirección de correo electrónico
    email: function (v) {
        return /^\b[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,6}\b$/i.test(v);
    },

//URL
    url: function (v) {
        return /^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i.test(v);
    },

//Fecha con formato dd/mm/aaaa
    date: function (v) {
        var regex = /^(\d{1,2})\/(\d{1,2})\/(\d{1,4})$/;
        if (!regex.test(v))
            return false;

        var d = parseInt(RegExp.$1);
        var m = parseInt(RegExp.$2);
        var y = parseInt(RegExp.$3);

        if (d < 1 || m < 1 || m > 12 || y == 0)
            return false;

        if (m == 2)
            return d <= 28 + (((y % 4 === 0 && y % 100 !== 0) || y % 400 === 0) ? 1 : 0);

        return d <= [31, 0, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][m - 1];
    },

    //Expresión regular. Ejemplo: regexp[expreg1] => data-expreg1="/^1.*5$/ig". La expresión debe comenzar y terminar con /
    regexp: function (v, e, dataWithRegexp) {
        if (arguments.length === 3) {
            var d = e.data(dataWithRegexp);
            if (typeof (d) == 'string' || !d) {
                try {
                    var fc = d.charAt(0), regexp = '^' + fc + '(.*)' + fc + '([gi]+)?$';

                    if (!(new RegExp(regexp)).test(d)) {
                        console.log('No es correcta la expresión regular: ' + d + '. El primer caracter será utilizado como separador entre la expresión y las opciones.');
                        return false;
                    }
                    return (new RegExp(RegExp.$1, RegExp.$2)).test(v);
                } catch (e) {
                    console.log(d + ' no es una expresión regular válida');
                    return false;
                }
            }
            console.log('No existe data-' + dataWithRegexp);
            return false;
        }
        console.log('regexp necesita como argumento el data-xxxx que contiene la expresión regular');
        return false;
    },

    //Expresión regular negada. Ejemplo: regexp[expreg1] => data-expreg1="/^1.*5$/ig". La expresión debe comenzar y terminar con /
    neg_regexp: function (v, e, dataWithRegexp) {
        if (arguments.length === 3) {
            var d = e.data(dataWithRegexp);
            if (typeof (d) == 'string') {
                try {
                    var fc = d.charAt(0), regexp = '^' + fc + '(.*)' + fc + '([gi]+)?$';

                    if (!(new RegExp(regexp)).test(d)) {
                        console.log('No es correcta la expresión regular: ' + d + '. El primer caracter será utilizado como separador entre la expresión y las opciones.');
                        return false;
                    }
                    return !(new RegExp(RegExp.$1, RegExp.$2)).test(v);
                } catch (e) {
                    console.log(d + ' no es una expresión regular válida');
                    return false;
                }
            }
            console.log('No existe data-' + dataWithRegexp);
            return false;
        }
        console.log('neg_regexp necesita como argumento el data-xxxx que contiene la expresión regular');
        return false;
    },

    //Al menos un checkbox/radio debe estar activado.
    any: function (v, e) {
        return $('input[name="' + e[0].name + '"]:checked', this.form).length > 0;
    },

    md5: function (v, e) {
        $(e).data('getMD5', true);
        return true;
    },

    force_evaluate: function () {
        return true;
    },

    trim: function () {
        return true;
    },

    trim_spaces: function () {
        var e = $(this.currentElement);
        e.val(e.val().trim());
        return true;
    }
};

Validator.prototype.getElementName = function (e) {
    return e.name || e.id || (e.tagName + (e.type ? '_' + e.type : '') + this.suffixIndex++);
};

Validator.prototype.getValues = function (includeInvisible) {
    var t = this, fs = {}, el = t.form ? t.form[0].elements : t.elements;

    t.suffixIndex = 0;

    for (var i = 0; i < el.length; i++) {
        var e = el[i], $e = $(e);

        if ($e.is(':disabled') || $e.data('skip'))
            continue;

        if (!includeInvisible && !($e.is(':visible') || $e.is('input[type="hidden"]')))
            continue;

        var fn = t.getElementName(e),
            fv = t._elemValue(e);

        if (fv === false)
            continue;

        if ($e.data('getMD5'))
            fv = MD5(fv);

        if (/^(.*)\[\]$/.test(fn)) {
            fn = RegExp.$1;
            if (typeof (fs[fn]) == 'undefined')
                fs[fn] = [];
        }

        typeof (fs[fn]) == 'object' ? fs[fn].push(fv) : fs[fn] = fv;
    }

    return fs;
};

Validator.prototype.showError = function (d) {
    var t = this;

    if (!d.isCBRB)
        d.p.addClass(t.errorClasses);

    d.ttel.tooltip('dispose');

    if (t.tooltips) {
        d.ttel.attr('data-original-title', d.error).attr('title', d.error);
        d.ttel.tooltip('show');
        d.e.addClass(t.errorClasses);
        d.e.bind('change.myEvents keyup.myEvents', function () {
            if (t.validate(d))
                d.e.unbind('.myEvents');
        });
    }
};

Validator.prototype.__$clearOne = function (i, d) {
    d.onTooltipHidden = null;
    d.ttel.tooltip('dispose');

    d.p.removeClass(Validator.prototype.errorClasses);
    d.e.removeClass(Validator.prototype.errorClasses);
    d.e.unbind('.myEvents');

    d.lastError = '';
};

Validator.prototype.clear = function (elem) {
    var t = this;

    if (elem) {
        if (typeof (elem) === 'string')
            elem = $(elem);

        if (elem instanceof jQuery) {
            if (elem.length === 0)
                return;

            elem = elem[0];
        } else if (typeof (elem) !== 'object')
            return;

        for (var i in t.elements) {
            var el = t.elements[i];
            if (el.e[0] === elem) {
                t.__$clearOne(0, el);
                return;
            }
        }
    } else
        $.each(t.elements, t.__$clearOne);
};

/**
 * Valida los datos del formulario o del elemento especificado
 * Pone en summary los errores para aquellos elementos que contengan data-label="Nombre del campo"
 * @returns {boolean}
 */
Validator.prototype.validate = function (elem) {
    var t = this, res = true;

    t.refreshControls(elem);

    if (elem) {
        if (typeof (elem) === 'string')
            elem = $(elem);

        if (elem instanceof jQuery) {
            if (elem.length === 0)
                return false;

            var elems = elem.get();

            elem = [];
            for (var i in t.elements) {
                var el = t.elements[i];
                if (elems.indexOf(el.e[0]) >= 0) {
                    elem.push(el);
                    break;
                }
            }

            if (elem.length === 0)
                return false;
        } else
            elem = [elem];
    }

    t.suffixIndex = 0;
    t.summary = [];
    var processedNames = [];

    $.each(elem ? elem : t.elements, function (i, d) {
        t.currentElement = d.e;
        d.error = null;

        var name = t.getElementName(d.e[0]);
        if (d.e.is(':disabled')) {
            d.p.removeClass(t.errorClasses);
            d.e.removeClass(t.errorClasses);
            d.e.unbind('.myEvents');
            return;
        }

        if (processedNames.indexOf(name) >= 0)
            return;

        processedNames.push(name);

        var val = t._elemValue(d.e[0]);

        if (d.r.indexOf('trim') >= 0)
            val = val.trim();

        if ((d.r[0] === 'required' || d.r[0] === 'required_trim')
            || d.r[0].indexOf('ifchecked') === 0
            || d.r[0].indexOf('force_evaluate') >= 0//=== 0
            //|| d.r[0].indexOf('ifselectedvalue') === 0
            //|| d.r[0].indexOf('ifselectedindex') === 0
            || val !== '') {
            $.each(d.r, function (ri, rule) {
                if (d.error)
                    return;

                /^([a-z]?[a-z0-9_]*)[\s]*(\[(.*)\])?$/i.test(rule);

                if (t[rule = RegExp.$1]) {
                    var params = RegExp.$3.split(';');
                    params.unshift(val, d.e);

                    var retVal = t[rule].apply(t, params);
                    if (retVal === null) { //Si devuelve null se pasa por alto el control
                        d.error = true;
                        return;
                    }

                    if (retVal === false) {
                        res = false;
                        var i = 2, msg = t._messages[rule];
                        if (typeof (msg) == 'function')
                            msg = msg();
                        d.error = (d.m[ri] || msg || 'No válido').replace(/%/g, function () {
                            return params[i++] || '';
                        });
                        var lab = d.e.data('label');
                        if (lab)
                            t.summary.push({label: lab, error: d.error});
                    }
                } else
                    console.log('Regla desconocida o mal formada:', rule);
            });
        }

        if (d.error === true)
            d.error = false;

        if (d.error === d.lastError)
            return;

        if (d.error)
            t.showError(d);
        else {
            d.p.removeClass(t.errorClasses);
            d.e.removeClass(t.errorClasses);
            d.e.unbind('.myEvents');

            d.ttel.tooltip('dispose');
        }

        d.lastError = d.error;
    });

    return res;
};