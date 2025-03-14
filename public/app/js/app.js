function Model() {
    const t = this,
        originalTree = folders();

    let xhr = null;

    let $pendingPost = false;

    const iniData = initData();

    t.sizes = [9, 10, 11, 12, 13, 14, 15, 16];
    t.tabSizes = [2, 3, 4, 5, 6];
    t.themes = [
        'ambiance',
        'chaos',
        'chrome',
        'cloud9_night',
        'clouds',
        'clouds_midnight',
        'cloud_editor',
        'cloud_editor_dark',
        'cobalt',
        'crimson_editor',
        'dawn',
        'dracula',
        'dreamweaver',
        'eclipse',
        'github_dark',
        'github_light_default',
        'gob',
        'gruvbox',
        'gruvbox_dark_hard',
        'idle_fingers',
        'iplastic',
        'katzenmilch',
        'kr_theme',
        'kuroir',
        'merbivore',
        'merbivore_soft',
        'monokai',
        'mono_industrial',
        'one_dark',
        'pastel_on_dark',
        'solarized_dark',
        'solarized_light',
        'sqlserver',
        'textmate',
        'tomorrow',
        'tomorrow_night',
        'tomorrow_night_blue',
        'tomorrow_night_bright',
        'twilight',
        'vibrant_ink',
        'xcode',
    ].map(function (theme) {
        return {
            text: theme.substring(0, 1).toUpperCase() + theme.substring(1).replaceAll('_', ' '),
            value: theme
        };
    });

    t.editorFontSize = ko.observable(iniData.fontSize || 12);
    t.editorFontSize.subscribe(function (size) {
        t.openedFiles().forEach(function (ed) {
            ed.editor.setOption('fontSize', size + 'pt');
        });
    });

    t.editorTabSize = ko.observable(iniData.tabSize || 4);
    t.editorTabSize.subscribe(function (size) {
        t.openedFiles().forEach(function (ed) {
            ed.editor.setOption('tabSize', size + 'pt');
        });
    });

    t.editorTheme = ko.observable(iniData.theme || 'vibrant_ink');
    t.editorTheme.subscribe(function (theme) {
        t.openedFiles().forEach(function (ed) {
            ed.editor.setTheme("ace/theme/" + theme);
        });
    });

    t.editorSoftWraps = ko.observable(iniData.softWraps);
    t.editorSoftWraps.subscribe(function (wrap) {
        t.openedFiles().forEach(function (ed) {
            ed.editor.setOption('wrap', wrap);
        });
    });

    t.editorLineNumbers = ko.observable(iniData.showLineNumbers);
    t.editorLineNumbers.subscribe(function (ln) {
        t.openedFiles().forEach(function (ed) {
            ed.editor.setOption('showLineNumbers', ln);
        });
    });

    t.editorSoftTabs = ko.observable(iniData.softTabs);
    t.editorSoftTabs.subscribe(function (tabs) {
        t.openedFiles().forEach(function (ed) {
            ed.editor.setOption('useSoftTabs', tabs);
        });
    });

    t.editorTabSize = ko.observable(iniData.tabSize);
    t.editorTabSize.subscribe(function (size) {
        t.openedFiles().forEach(function (ed) {
            ed.editor.setOption('tabSize', size);
        });
    });

    t.setEditorOptions = function () {
        const content = $('#editorOptions').clone().removeClass('d-none').attr('id', null);

        let editor,
            fontSize = t.editorFontSize(),
            tabSize = t.editorTabSize(),
            softWraps = t.editorSoftWraps(),
            softTabs = t.editorSoftTabs(),
            lineNums = t.editorLineNumbers();

        $('#editorSample', content).attr('id', 'editor_sample')

        let sel = $('select', content).on('change', function () {
            editor.setTheme('ace/theme/' + sel.val());
        });

        let sizeButtons = $('.btnSize', content)
            .on('click', function () {
                sizeButtons.removeClass('btn-dark');
                fontSize = parseInt($(this).text());
                editor.setOption('fontSize', fontSize + 'pt');
                $(this).addClass('btn-dark');
            });

        let tabSizeButtons = $('.btnTabSize', content)
            .on('click', function () {
                tabSizeButtons.removeClass('btn-dark');
                tabSize = parseInt($(this).text());
                editor.setOption('tabSize', tabSize);
                $(this).addClass('btn-dark');
            });

        $('#wrap', content)
            .attr('checked', t.editorSoftWraps())
            .on('click', function () {
                editor.setOption('wrap', softWraps = $(this).is(':checked'));
            });

        $('#tabs', content)
            .attr('checked', t.editorSoftTabs())
            .on('click', function () {
                editor.setOption('useSoftTabs', softTabs = $(this).is(':checked'));
            });

        $('#lineNums', content)
            .attr('checked', t.editorLineNumbers())
            .on('click', function () {
                editor.setOption('showLineNumbers', lineNums = $(this).is(':checked'));
            });

        sel.val(t.editorTheme());

        setTimeout(function () {
            editor = ace.edit(
                'editor_sample',
                {
                    mode: 'ace/mode/php',
                    theme: 'ace/theme/' + t.editorTheme(),
                    fontSize: fontSize + "pt",
                    tabSize: t.editorTabSize(),
                    showLineNumbers: t.editorLineNumbers(),
                    wrap: t.editorSoftWraps(),
                    useSoftTabs: t.editorSoftTabs(),
                    showPrintMargin: false,
                    readOnly: true
                }
            );

            editor.setValue('<?php\n\
class ControllerSample extends Controller {\n\
\tprivate $error = array();\n\
\n\
\tpublic function index() {\n\
\t\t$this->document->setTitle($this->language->get(\'sample_title\'));\n\
\n\
\t\t$this->getList();\n\
\t}\n\
}');
            editor.session.getSelection().clearSelection();
            editor.session.getUndoManager().reset();
        });

        showConfirm(content,
            {
                ok: function () {
                    t.editorTheme(sel.val());
                    t.editorFontSize(fontSize);
                    t.editorTabSize(tabSize);
                    t.editorSoftWraps(softWraps);
                    t.editorLineNumbers(lineNums);
                    t.editorSoftTabs(softTabs);

                    function failFn() {
                        sysMsgs.show('No ha sido posible guardar la configuración del editor.', __MSG_ERROR, true);
                    }

                    doPost('main/saveEditorOptions',
                        {
                            theme: t.editorTheme(),
                            fontSize: t.editorFontSize(),
                            tabSize: t.editorTabSize(),
                            showLineNumbers: t.editorLineNumbers() ? 1 : 0,
                            softWraps: t.editorSoftWraps() ? 1 : 0,
                            softTabs: t.editorSoftTabs() ? 1 : 0,
                        },
                        function (r) {
                            if (!r.ok)
                                failFn();
                        },
                        {
                            lock: false,
                            fail: failFn
                        })
                }
            },
            btnOkCancel,
            true,
            false,
            'Opciones del editor',
            {
                icon: '-',
                size: 'lg'
            });
    }

    t.recreatingCache = ko.observable(0);

    function doPost(uri, data, callback, options) {
        options = $.extend({
            type: 'json', //Tipo de respuesta
            failFn: null, //Callback en caso de fallo
            koObs: null,  //Observable de knockout que indica que se está procesando la petición
            lock: true,   //Poner a false si no se desea bloquear mientras hay una petición en proceso
            clearMsgs: true,
        }, options);

        if (options.lock && $pendingPost) {
            sysMsgs.show('Hay una petición en curso, espere un momento.', 'info', true);
        } else {
            if (options.koObs)
                options.koObs(options.koObs() + 1);

            if (options.lock)
                $pendingPost = true;

            if (typeof (sysMsgs) !== 'undefined' && options.clearMsgs)
                sysMsgs.clear();

            return $.post(uri, data || {},
                function () {
                    if (typeof (callback) == 'function')
                        callback.apply(null, arguments);
                },
                options.type
            )
                .always(function () {
                    if (options.koObs)
                        options.koObs(options.koObs() - 1);

                    if (options.lock)
                        $pendingPost = false;

                    if (typeof (options.always) === 'function')
                        options.always();
                })
                .done(function () {
                    if (typeof (options.done) === 'function')
                        options.done();
                })
                .fail(function (d) {
                    if (/*d.status !== 200 &&*/ d.statusText !== 'abort') {
                        if (typeof (options.failFn) == 'function')
                            options.failFn.apply(this, arguments);
                        else
                            sysMsgs.show("No ha sido posible realizar la operación", __MSG_ERROR, false);
                    }
                });
        }
    }

    t.showRoot = function () {
        return false;
    }

    t.isEditable = function (filename) {
        let i = filename.lastIndexOf('.');
        let ext = i > 0 ? filename.substring(i).toLowerCase() : '';
        return ['.php', '.twig', '.js'].indexOf(ext) >= 0;
    }

    t.getActionIcon = function (data) {
        switch (data.action) {
            case 'ocmod':
                return 'fa fa-pencil';
            case 'diff':
                return 'fa fa-exchange';
            case 'upload':
                return 'fa fa-upload';
            case 'orig':
                return 'orig-icon';
            case 'install-xml':
                return 'fa fa-tags';
            default:
                return '-';
        }
    }

    t.OCMODed = ko.observable(false);
    t.Uploaded = ko.observable(false);

    t.projects = ko.observableArray(projects());
    t.currentProject = ko.observable(t.projects().length > 0 ? t.projects()[iniData.projectIndex] : null);

    t.fileList = ko.observableArray([]).extend({notify: 'always'});
    t.curPath = ko.observable('');

    let isFiltered = false;

    t.toggle = function (obs) {
        t[obs](!t[obs]());
        $(':focus').blur();
        t.activeLeaf().$element.focus();

        rebuildTree();
    }

    function saveLastPath() {
        $.post('main/saveLastPath', {path: getPath(t.activeLeaf()), opened: t.activeLeaf().opened() ? 1 : 0});
    }

    t.toggleOpen = function (leaf) {
        leaf.opened(!leaf.opened());
        if ('originalLeaf' in leaf)
            leaf.originalLeaf.opened(leaf.opened());

        leaf == t.activeLeaf() && $.post('main/saveLastOpened', {opened: leaf.opened() ? 1 : 0});
    }

    t.someModified = function () {
        return t.openedFiles()
            .filter(function (f) {
                return f.modified();
            })
            .length > 0;
    }

    function cloneLeaf(leaf) {
        let newLeaf = {
            n: ko.observable(leaf.n()),
            o: ko.observable(leaf.o()),
            u: ko.observable(leaf.u()),
            f: ko.observable(leaf.f()),
            new: ko.observable(!!leaf.new()),
            c: ko.observableArray([]),
            opened: ko.observable(leaf.opened()),
            originalLeaf: leaf
        };

        return newLeaf;
    }

    function filterLeaf(originalLeaf, newLeaf, filterFn) {
        originalLeaf.c().forEach(function (leaf) {
            if (filterFn(leaf)) {
                let nLeaf = cloneLeaf(leaf);
                nLeaf.parent = newLeaf;
                newLeaf.c.push(nLeaf);

                filterLeaf(leaf, nLeaf, filterFn);
            }
        });
    }

    function findLeave(path, activate, useOriginal) {
        let leaf = useOriginal ? originalTree[0].c()[0] : t.tree().c()[0],
            someFound = false;

        path = path.substring(1).split('/');

        for (let i = 0; i < path.length; i++) {
            let children = leaf.c(),
                found = false;

            for (let l = 0; l < children.length; l++) {
                if (children[l].n() === path[i]) {
                    leaf = children[l];
                    someFound = found = true;
                    break;
                }
            }

            if (!found)
                break;
        }

        if (activate) {
            if (someFound)
                t.activeLeaf(leaf);
            else {
                let initLeaf = t.tree().c()[0];
                if (initLeaf.c().length)
                    initLeaf = initLeaf.c()[0];
                t.activeLeaf(initLeaf);
            }

            let parent = t.activeLeaf();
            while (parent = parent.parent) {
                parent.opened(true);
            }
            setTimeout(function () {
                t.activeLeaf().$element[0].scrollIntoView(false);
            });
        }

        return leaf;
    }

    function rebuildTree() {
        let path = getPath(t.activeLeaf());

        const ocmod = t.OCMODed(),
            uploaded = t.Uploaded();

        isFiltered = ocmod || uploaded;

        let newTree;

        if (isFiltered) {
            let filterFn = function (leaf) {
                return (ocmod && leaf.o()) ||
                    (uploaded && (leaf.u() || leaf.new() || leaf.f()));
            }

            newTree = [cloneLeaf(originalTree[0])];
            newTree[0].parent = null;

            filterLeaf(originalTree[0], newTree[0], filterFn);

            //Si el árbol queda vacío es porque no hay nada que filtrar, seguimos con el árbol original
            if (newTree[0].c().length == 0) {
                t.Uploaded(isFiltered = false);
                t.OCMODed(false);
                return;
            }
        } else {
            newTree = originalTree;
        }

        t.tree({c: ko.observableArray(newTree), parent: null, opened: ko.observable(true)});

        findLeave(path, true);
    }

    function getPath(leaf) {
        let path = '';

        while (leaf.parent) {
            path = '/' + leaf.n() + path;
            leaf = leaf.parent;
        }

        return path;
    }

    function updateTree(callback) {
        function _updateLeave(leaf, callback) {
            callback(leaf);
            leaf.c().forEach(function (v) {
                _updateLeave(v, callback);
            });
        }

        _updateLeave(originalTree[0], callback);

        if (isFiltered)
            _updateLeave(t.tree()[0], callback);
    }

    function koFn(e) {
        $.each(e.c, function (i, ie) {
            ie.parent = e;
            koFn(ie);
        });

        e.n = ko.observable(e.n);
        e.m = ko.observable(e.m || 0);
        e.o = ko.observable(e.o || 0);
        e.u = ko.observable(e.u || 0);
        e.f = ko.observable(e.f || 0);
        e.new = ko.observable(!!e.new);
        e.c = ko.observableArray(e.c);
        e.opened = ko.observable(!e.parent);
    }

    $.each(originalTree, function (i, e) {
        if (e.parent)
            e.parent = originalTree;
        e.o = ko.observable(e.o || 0);
        e.m = ko.observable(e.m || 0);
        e.u = ko.observable(e.u || 0);
        koFn(e);
    });

    t.tree = ko.observable({
        c: ko.observableArray(originalTree),
        parent: null,
        opened: ko.observable(true)
    }).extend({notify: 'always'});

    let isFirstLoad = true;

    t.activeLeaf = ko.observable();
    t.activeLeaf.extend({notify: 'always'});
    t.activeLeaf.subscribe(function (leaf) { //Cargar los archivos de la carpeta que se activó
        let path = getPath(leaf);

        t.curPath(path);

        xhr && xhr.abort();

        const pd = projectData();

        xhr = $.post('main/get_files', {path: path, opened: (isFirstLoad ? (pd ? pd.lastPathOpened || 0 : 0) : leaf.opened()) ? 1 : 0},
            function (d) {
                xhr = null;
                d.files.forEach(function (e) {
                    e.n = ko.observable(e.n);
                    e.o = ko.observable(!!e.o);
                    e.u = ko.observable(!!e.u);
                    e.m = ko.observable(!!e.m);
                });
                t.fileList(d.files);
            }, 'json');

        if (!pd || !pd.openedFiles || pd.openedFiles.length === 0)
            isFirstLoad = false;
    });

    if (originalTree.length > 0) {
        const pd = projectData();
        if (pd) {
            findLeave(pd.lastPath, true);
            t.activeLeaf().c().length && t.activeLeaf().opened(pd.lastPathOpened);
        }
    }

    $('.card.editor').delegate('button[id^="btnGo"]', 'click', null,
        function (e) {
            let ed = t.currentEditor();

            if (ed.isUploadFile) {
                ed.editor.focus();
                return;
            }

            let id = $(this).attr('id').substring(5),
                row = 0,
                sel = ed.editor.session.getSelection(),
                curPos = sel.cursor.getPosition(),
                index = 0,
                found = false;

            if (ed.markerList) {
                if (ed.markerList.length == 0) {
                    ed.editor.focus();
                    return;
                }

                switch (id) {
                    case 'First':
                        row = ed.markerList[0].startRow;
                        found = true;
                        break;

                    case 'Prev':
                        index = ed.markerList.length - 1;
                        while (index >= 0 && ed.markerList[index].startRow >= curPos.row)
                            index--;
                        if (index < 0)
                            break;
                        found = true;
                        row = ed.markerList[index].startRow;
                        break;

                    case 'Next':
                        index = 0;
                        while (index < ed.markerList.length && ed.markerList[index].startRow <= curPos.row)
                            index++;
                        if (index >= ed.markerList.length)
                            break;
                        found = true;
                        row = ed.markerList[index].startRow;
                        break;

                    case 'Last':
                        row = ed.markerList[ed.markerList.length - 1].startRow;
                        found = true;
                        break;
                }
            } else {
                let docLen = ed.editor.session.getLength();

                switch (id) {
                    case 'Next':
                    case 'First':
                        row = id === 'First' ? 0 : curPos.row + 1;
                        while (row < docLen && !isOpenOCMODTag(ed.editor.session.getLine(row)))
                            row++;
                        found = row < docLen;
                        break;

                    case 'Prev':
                    case 'Last':
                        row = id === 'Prev' ? curPos.row : docLen - 1;
                        while (row >= 0) {
                            if (isCloseOCMODTag(ed.editor.session.getLine(row))) {
                                let r = getCurrentOCMODRange(new ace.Range(row, 0, row, 0));
                                row = r.start.row;
                                break;
                            }
                            row--;
                        }
                        found = row >= 0;
                        break;
                }
            }

            if (found) {
                if (!ed.editor.isRowVisible(row))
                    ed.editor.scrollToLine(row, true, true);
                sel.cursor.setPosition(row, 0);
                sel.clearSelection();
            }

            ed.editor.focus();

            enableNavButtons();
        });

    function enableNavButtons() {
        let ed = t.currentEditor(),
            navButtons = $('button[id^="btnGo"]');

        if (!ed || !('editor' in ed) || ed.isUploadFile || t.openedFiles().length === 0) {
            navButtons.attr('disabled', 'disabled');
            return;
        }

        if (ed.markerList) {
            if (ed.markerList.length)
                navButtons.removeAttr('disabled');
            else
                navButtons.attr('disabled', 'disabled');

            return;
        }

        navButtons.removeAttr('disabled');
    }

    /*function recreateCache() {
        doPost('main/recreateCache', {}, null, {koObs: t.recreatingCache, lock: false});
    }*/

    let saveCancelled = false,
        isClosingAll = false;

    let execAfterCloseAllFunc = null,
        skipUpdateStatus = false;

    function closeNextFile() {
        if (isClosingAll) {
            if (saveCancelled || t.openedFiles().length === 0) {
                saveCancelled = false;
                isClosingAll = false;

                if (t.openedFiles().length === 0 && execAfterCloseAllFunc)
                    execAfterCloseAllFunc();

                execAfterCloseAllFunc = null;

                if (skipUpdateStatus)
                    skipUpdateStatus = false;
                else
                    updateStatus();
            } else
                t.closeFile(t.openedFiles()[0]);
        } else
            updateStatus();
    }

    function setDiffContent(editorData, lang, content) {
        editorData.markerList = [];

        let lines = [],
            lastAction = ' ',
            newMarker = null;

        for (let i = 0; i < content.length; i++) {
            let line = content[i];

            if (i === content.length - 1 && line === '\\ No newline at end of file\n')
                break;

            lines.push(line.substring(1));

            let action = line.charAt(0);

            if (action === ' ') {
                if (newMarker) {
                    newMarker.endRow = i;
                    editorData.markerList.push(newMarker);
                    newMarker = null;
                }
                lastAction = action;
            } else {
                if (lastAction !== action) {
                    if (newMarker) {
                        newMarker.endRow = i;
                        editorData.markerList.push(newMarker);
                    }
                    newMarker = {startRow: i, action: action};
                    lastAction = action;
                }
            }
        }

        if (newMarker) {
            newMarker.endRow = lines.length;
            editorData.markerList.push(newMarker);
        }

        // editorData.editor.setReadOnly(true);
        // editorData.editor.session.setMode("ace/mode/" + editorData.lang, highlightFactory(editorData));
        editorData.editor.setValue(lines.join(''));

        editorData.markerList.forEach(function (marker) {
            let clazz = marker.action === '+' ? 'added' : 'removed';

            editorData.editor.session.addMarker(new ace.Range(marker.startRow, 0, marker.endRow, 0), clazz, "line", false);
        });
    }

    function addCheckRootRule(validator) {
        validator.addRule('checkRoot',
            function (v) {
                const reqData = new FormData();
                reqData.append('path', v);

                async function get() {
                    const resp = await fetch('main/checkRoot',
                        {
                            method: 'POST',
                            body: reqData,
                            headers: {'X-Requested-With': 'XMLHttpRequest'}
                        });

                    if (resp.ok) {
                        const ret = await resp.json();
                        return ret.ok;
                    }

                    return true;
                }

                return get();
            }, 'La ruta no es válida');
    }

    function addCheckURLRule(validator) {
        validator.addRule('checkURL',
            function (v) {
                const reqData = new FormData();
                reqData.append('url', v);

                async function get() {
                    const resp = await fetch('main/checkURL',
                        {
                            method: 'POST',
                            body: reqData,
                            headers: {'X-Requested-With': 'XMLHttpRequest'}
                        });

                    if (resp.ok) {
                        const ret = await resp.json();
                        return ret.ok;
                    }

                    return true;
                }

                return get();
            }, 'La URL de OpenCart no es válida');
    }

    function updateStatus() {
        let curIndex = -1,
            openedFiles = t.openedFiles()
                .map(function (ed, i) {
                    if (ed.action === 'install-xml')
                        return '';

                    if (ed === t.currentEditor())
                        curIndex = i;
                    const s = ed.editor.session,
                        csrPos = ed.editor.getSelection().cursor.getPosition();
                    return ed.action +
                        '|' + csrPos.row + ',' + csrPos.column + ',' + Math.floor(s.$scrollLeft) + ',' + Math.floor(s.$scrollTop) +
                        '|' + ed.path + '/' + ed.filename;
                })
                .filter(function (f) {
                    return f.length > 0;
                });

        doPost('main/saveOpenedFiles', {openedFiles: openedFiles, lastOpenedFile: curIndex}, null, {
            lock: false, clearMsgs: false, failFn: function () {
            }
        });
    }

    function someFileModified() {
        return t.openedFiles().some(function (ed) {
            return ed.modified();
        });
    }

    /**
     * Crea un nuevo proyecto y lo activa si es el primero
     */
    t.newProject = function () {
        const content = $('#newProject').clone().removeClass('d-none').attr('id', null);

        const dlg = showConfirm(content,
            {
                ok: function (dlg) {
                    const validator = new Validator($('form', dlg));

                    addCheckRootRule(validator);
                    addCheckURLRule(validator);

                    validator.validate()
                        .then(function (ok) {
                            if (!ok)
                                return;

                            $('button', dlg).attr('disabled', 'disabled');

                            function onFail() {
                                $('button', dlg).attr('disabled', null);
                                dlg.okFailed();
                            }

                            doPost('main/createProject', validator.getValues(),
                                function (r) {
                                    if (r.ok) {
                                        dlg.modal('hide');
                                        if (isFirstProject || openAfterCreation.is(':checked')) {
                                            const dlg = getDlgContent('', '<strong>Preparando todo para abrir el proyecto...</strong>', null, {noButtons: true});

                                            dlg.on('shown.bs.modal', function (e) {
                                                document.location.reload();
                                            });

                                            dlg.modal({keyboard: false, backdrop: 'static'});
                                        }
                                    } else {
                                        sysMsgs.show(r.error, __MSG_ERROR);
                                        onFail();
                                    }
                                },
                                {
                                    failFn: function () {
                                        sysMsgs.show('No ha sido posible crear el proyecto.', __MSG_ERROR);
                                        onFail();
                                    }
                                }
                            );
                        })

                    return true; //Impedir que se cierre el diálogo
                }
            },
            btnOkCancel,
            true,
            true,
            'Crear proyecto',
            {
                icon: '-',
                size: 'lg'
            });

        const isFirstProject = t.projects().length === 0;
        const openAfterCreation = $('input[type="checkbox"]', dlg).attr({disabled: isFirstProject ? 'disabled' : null});
    }

    /**
     * Crea un nuevo proyecto a partir de un archivo install.xml y lo activa si es el primero
     */
    t.newProjectFromXML = function () {
        const content = $('#newProject').clone().removeClass('d-none').attr('id', null),
            isFirstProject = t.projects().length === 0;

        $('#ocmodData', content).remove();
        $('#openFilesDiv', content).removeClass('d-none');
        $('#installXML', content).removeClass('d-none');
        $('#btnSelect', content).click(function (e) {
            $('#fileInput', content).val('');
            $('#fileInput', content).click();
        })
        $('#fileInput', content).on('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    let prom = new Promise(function (resolve, reject) {
                        let promises = [];

                        if (e.target.result)
                            validator.clear($('textarea', dlg));

                        const parser = new DOMParser();
                        const xmlDoc = parser.parseFromString(e.target.result, "application/xml");

                        const parserError = xmlDoc.getElementsByTagName("parsererror");
                        if (parserError.length > 0) {
                            resolve('No ha sido posible parsear el contenido del archivo.');
                            return;
                        }

                        const nodes = xmlDoc.getElementsByTagName('modification');
                        if (nodes.length !== 1) {
                            resolve('Debe haber una única etiqueta <strong>&lt;modification&gt;</strong>.');
                            return;
                        }

                        const modificationNode = nodes[0];

                        const requiredNodes = ['name', 'code', 'version', 'author'];
                        for (let i = 0; i < requiredNodes.length; i++) {
                            let nodeName = requiredNodes[i];
                            let node = modificationNode.getElementsByTagName(nodeName);
                            if (node.length !== 1) {
                                resolve('Debe haber una única etiqueta <strong>&lt;' + nodeName + '&gt;</strong> dentro de la etiqueta &lt;modification&gt;.');
                                return;
                            }

                            if (!node[0].textContent.trim()) {
                                resolve('La etiqueta &lt;' + nodeName + '&gt; debe existir y no puede estar vacía.');
                                return;
                            }
                        }

                        let result = true;

                        const files = modificationNode.getElementsByTagName('file');
                        for (let f = 0; f < files.length; f++) {
                            let file = files[f];

                            if (!file.getAttribute('path')) {
                                resolve('Cada etiqueta <strong>&lt;file&gt;</strong> debe tener un atributo "<strong>path</strong>" no vacío.');
                                return;
                            }

                            const operations = file.getElementsByTagName('operation');
                            if (operations.length === 0) {
                                resolve('Cada etiqueta <strong>&lt;file&gt;</strong> debe tener al menos una etiqueta <strong>&lt;operation&gt;</strong>.');
                                return;
                            }

                            for (let o = 0; o < operations.length; o++) {
                                let operation = operations[o];

                                if (operation.getElementsByTagName('search').length !== 1 ||
                                    operation.getElementsByTagName('add').length !== 1) {
                                    resolve('Cada etiqueta <strong>&lt;operation&gt;</strong> debe tener una sola etiqueta ' +
                                        '<strong>&lt;search&gt;</strong> y otra <strong>&lt;add&gt;</strong>.');
                                    return;
                                }
                            }

                            const reqData = new FormData();
                            reqData.append('filePath', file.getAttribute('path'));

                            promises.push((async function () {
                                const resp = await fetch('main/checkFileExists',
                                    {
                                        method: 'POST',
                                        body: reqData,
                                        headers: {'X-Requested-With': 'XMLHttpRequest'}
                                    });

                                if (resp.ok) {
                                    const ret = await resp.json();
                                    if (!ret.ok) {
                                        sysMsgs.show('El archivo: <strong>' + file.getAttribute('path') + '</strong> no existe.', __MSG_ERROR, false, -1);
                                    }
                                    result &&= ret.ok;
                                }
                            })());
                        }

                        if (promises.length > 0) {
                            Promise
                                .all(promises)
                                .then(function () {
                                    resolve(result);
                                })
                                .catch(function (error) {
                                    reject(error);
                                });
                        } else
                            resolve(true);
                    });

                    prom.then(function (r) {
                        if (r) {
                            editor.setValue(e.target.result);
                            editor.gotoLine(0);
                            editor.session.getSelection().clearSelection();
                            editor.session.getUndoManager().reset();
                            editor.focus();
                        }
                    });
                }

                reader.readAsText(file);
            }
        });

        let editor, validator;

        const dlg = showConfirm(content,
            {
                ok: function (dlg) {
                    validator.validate()
                        .then(function (vResult) {
                            if (typeof vResult === 'string') {
                                sysMsgs.show(r.ok, __MSG_ERROR, true);
                                return;
                            }

                            if (!vResult)
                                return;

                            $('button', dlg).attr('disabled', 'disabled');

                            function onFail() {
                                $('button', dlg).attr('disabled', null);
                                dlg.okFailed();
                            }

                            const values = validator.getValues();

                            delete values.TEXTAREA_textarea0;

                            values.content = editor.getValue();

                            doPost('main/createProjectFromXML', values,
                                function (r) {
                                    if (r.ok) {
                                        dlg.modal('hide');
                                        if (isFirstProject || openAfterCreation.is(':checked')) {
                                            const dlg = getDlgContent('', '<strong>Preparando todo para abrir el proyecto...</strong>', null, {noButtons: true});

                                            dlg.on('shown.bs.modal', function (e) {
                                                document.location.reload();
                                            });

                                            dlg.modal({keyboard: false, backdrop: 'static'});
                                        }
                                    } else {
                                        sysMsgs.show(r.error, __MSG_ERROR);
                                        onFail();
                                    }
                                },
                                {
                                    failFn: function () {
                                        sysMsgs.show('No ha sido posible crear el proyecto.', __MSG_ERROR);
                                        onFail();
                                    }
                                }
                            );
                        })

                    return true; //Impedir que se cierre el diálogo
                }
            },
            btnOkCancel,
            true,
            true,
            'Crear proyecto desde XML',
            {
                icon: '-',
                size: 'lg',
                dontShow: true
            });

        const openAfterCreation = $('input[type="checkbox"]', dlg).attr({disabled: isFirstProject ? 'disabled' : null});

        dlg.on('show.bs.modal', function () {
            editor = ace.edit(
                $('#editorBox', content)[0],
                {
                    theme: 'ace/theme/' + t.editorTheme(),
                    fontSize: "12pt",
                    tabSize: 4,
                    showLineNumbers: false,
                    showGutter: false,
                    wrap: false,
                    useSoftTabs: false,
                    showPrintMargin: false,
                    readOnly: true,
                    mode: 'ace/mode/xml'
                }
            );

            $('textarea.ace_text-input', dlg).attr(
                {
                    'data-rule': 'required_trim,checkXML',
                    'data-tooltipat': 'editorBox',
                });

            validator = new Validator($('form', dlg), null, true, {});

            validator.addRule('checkXML',
                function (v) {
                    const xml = editor.getValue();
                    if (!xml)
                        return false;

                    return true;
                }, 'El XML no es válido.');

            addCheckRootRule(validator);
            addCheckURLRule(validator);
        });

        dlg.modal({keyboard: true, backdrop: 'static'});
    }

    /**
     * Actualiza los datos del proyecto activo
     */
    t.updateProject = function () {
        const content = $('#newProject').clone().removeClass('d-none').attr('id', null);

        const dlg = showConfirm(content,
            {
                ok: function (dlg) {
                    const validator = new Validator($('form', dlg));

                    addCheckRootRule(validator);
                    addCheckURLRule(validator);

                    validator.validate()
                        .then(function (ok) {
                            if (ok) {
                                $('button', dlg).attr('disabled', 'disabled');

                                t.saveAll(function () {
                                    function onFail() {
                                        $('button', dlg).attr('disabled', null);
                                        dlg.okFailed();
                                    }

                                    const newValues = validator.getValues();

                                    let modified = false;
                                    props.forEach(function (n) {
                                        modified ||= pdata[n] !== newValues[n];
                                    });
                                    modified ||= newValues.code !== t.currentProject().code;

                                    if (modified) {
                                        doPost('main/updateProject', newValues,
                                            function (r) {
                                                if (r.ok) {
                                                    document.location.reload();
                                                } else {
                                                    if (r.error)
                                                        sysMsgs.show(r.error, __MSG_ERROR);
                                                    onFail();
                                                }
                                            },
                                            {
                                                failFn: function () {
                                                    sysMsgs.show('No ha sido posible actualizar los datos del proyecto.', __MSG_ERROR);
                                                    onFail();
                                                }
                                            }
                                        );
                                    } else {
                                        onFail();
                                        sysMsgs.show('No ha introducido cambios que deban ser guardados.', __MSG_INFO, true);
                                    }
                                });
                            }
                        });

                    return true; //Impedir que se cierre el diálogo
                }
            },
            btnOkCancel,
            true,
            true,
            'Actualizar datos del proyecto',
            {
                icon: '-',
                size: 'lg'
            });

        //Llenar los controles con los valores actuales
        const pdata = projectData(),
            props = ['projectName', 'root_path', 'zipFilename', 'url', 'name', 'version', 'author', 'link'];
        props.forEach(function (n) {
            $('input[name="' + n + '"]', dlg).val(pdata[n]);
        });
        $('input[name="code"]', dlg).val(t.currentProject().code);

        $('#openProjectRow', dlg).remove();

        //Mostrar el cuadro de información solamente si hay archivos sin guardar
        if (t.openedFiles().some(function (f) {
            return f.modified();
        }))
            $('#reloadWarn', dlg).removeClass('d-none');
    }

    /**
     * Cierra todos los archivos y abre otro proyecto
     * @param project
     */
    t.openProject = function (project) {
        execAfterCloseAllFunc = function () {
            doPost('main/openProject', {projectCode: project.code},
                function (r) {
                    if (r.ok)
                        document.location.reload();
                    else
                        sysMsgs.show(r.error, __MSG_ERROR, true);
                },
                {
                    failFn: function () {
                        sysMsgs.show('No ha sido posible cambiar de proyecto.', __MSG_ERROR, true);
                    }
                })
        }

        skipUpdateStatus = true;
        t.closeAll();
    }

    /**
     * Elimina el proyecto activo
     */
    t.deleteProject = function () {
        showConfirm('Va a proceder a eliminar el proyecto:<div class="ml-3 mt-2"><strong>' + projectData().projectName + '</strong></div><br>' +
            '¿Está seguro que desea eliminarlo?', {
            ok: function () {
                doPost('main/deleteProject', {code: t.currentProject().code},
                    function (r) {
                        if (r.ok) {
                            document.location.reload();
                        } else
                            sysMsgs.show(r.error, __MSG_ERROR, true);
                    },
                    {
                        failFn: function () {
                            sysMsgs.show('No ha sido posible eliminar el proyecto.', __MSG_ERROR, true);
                        }
                    });
            }
        })
    }

    /**
     * Carga y muestra una copia actualizada del archivo install.xml
     */
    t.showInstallXML = function () {
        function loadFile() {
            t.loadFile('/install.xml', 'install-xml');
        }

        if (someFileModified()) {
            let dlg = showConfirm('<strong>Al menos uno de los archivos abiertos tiene cambios que no han sido guardados.</strong><br><br>' +
                '¿Desea guardar los archivos modificados antes de proceder?',
                {
                    ok: function () {
                        t.saveAll(loadFile);
                    },
                    no: loadFile,
                    cancel: function () {
                        dlg.modal('hide');
                    }
                }, btnYesNoCancel, true, true);
        } else
            loadFile();
    }

    /**
     * Carga un archivo a partir de información de la lista de archivos
     * @param fileData
     */
    t.loadFileFromData = function (fileData) {
        t.loadFile(fileData, fileData.u() ? 'upload' : (fileData.m() ? 'orig' : 'ocmod'));
    }

    /**
     * Carga un archivo en el editor o activa el editor si ya se encontraba abierto
     * @param filedata
     * Datos del archivo
     * @param action
     * Propósito del archivo:
     * -ocmod   Archivo con bloques OCMOD u original, si todavía no existe físicamente en projects/xxxx/ocmod
     * -orig    Archivo original de OpenCart
     * -diff    Diferencias entre el archivo original y el archivo en modifications
     * -upload  Archivo nuevo en projects/xxxx/publish/upload
     */
    t.loadFile = function (filedata, action, next) {
        const of = t.openedFiles();

        //Open file from the list of opened files
        if (of.indexOf(filedata) >= 0) {
            t.currentEditor(filedata);
            filedata.editor.focus();

            updateStatus();
            return;
        }

        const filename = typeof filedata === 'string'
            ? filedata.substring(filedata.lastIndexOf('/') + 1)
            : filedata.n();

        if (!t.isEditable(filename) && action !== 'install-xml')
            return;

        let editorData = null,
            found = false;

        //Find if the file is already opened
        for (let i = 0; i < of.length; i++) {
            let ed = of[i];

            if (action === 'install-xml')
                found = ed.filename === filename && ed.action === action;
            else
                found = ed.path === t.curPath() && ed.filename === filename && ed.action === action;

            if (found) {
                if (action === 'install-xml') {
                    editorData = ed;
                    break;
                }

                t.currentEditor(ed);
                ed.editor.focus();

                updateStatus();
                return;
            }
        }

        const
            file = typeof (filedata) === 'string'
                ? filedata
                : t.curPath() + '/' + filename,
            filePath = typeof filedata === 'string'
                ? filedata.substring(0, filedata.lastIndexOf('/'))
                : t.curPath(),
            ext = file.substring(file.lastIndexOf('.') + 1).toLowerCase(),
            langs = {
                php: 'php',
                twig: 'twig',
                js: 'javascript',
                xml: 'xml'
            },
            lang = ext in langs ? langs[ext] : '';

        function createEditor(content, isDiff) {
            editorData.isUploadFile = action === 'upload';
            editorData.isEditableFile = false;

            cutBlock = null;
            clipboardAction = null;

            t.openedFiles.push(editorData);

            editorData.editor = ace.edit(
                editorData.id,
                {
                    theme: 'ace/theme/' + t.editorTheme(),
                    fontSize: t.editorFontSize() + "pt",
                    tabSize: t.editorTabSize(),
                    showLineNumbers: t.editorLineNumbers(),
                    wrap: t.editorSoftWraps(),
                    useSoftTabs: t.editorSoftTabs(),
                    showPrintMargin: false,
                    readOnly: true
                }
            );

            t.openedFiles.sort(function (a, b) {
                let pa = a.path + '/' + a.filename,
                    pb = b.path + '/' + b.filename;
                return pa === pb ? a.action < b.action ? 1 : -1 : pa > pb ? 1 : -1;
            })

            initializeEditor(editorData);

            t.currentEditor(editorData);

            if (isDiff) {
                setDiffContent(editorData, lang, content);
            } else {
                editorData.markerList = null;
                editorData.isEditableFile = !(action === 'orig' || action === 'diff');

                editorData.editor.setReadOnly(!editorData.isEditableFile);

                editorData.editor.setValue(content);
            }

            editorData.editor.gotoLine(0);
            editorData.editor.session.getUndoManager().reset();
            editorData.editor.focus();
            editorData.modified(false);
            // console.log(editorData.editor.session.$mode.$highlightRules.$rules)

            enableNavButtons();
        }

        if (!editorData) {
            editorData = {
                id: 'ed_' + Date.now(),
                action: action,
                path: filePath,
                filename: filename,
                lang: lang,
                isEditableFile: false,
                isUploadFile: false,
                modified: ko.observable(false)
            }
        }

        //A file was just created
        if (!!filedata.new) {
            delete filedata.new;

            createEditor(lang === 'php' ? '<?php\n' : '', false);

            editorData.newFileData = filedata;
            editorData.modified(true);
            editorData.editor.getSelection().cursor.setPosition(1, 0);
            return;
        }

        //Load file from disk
        let data = {
            file: file,
            action: action
        };

        doPost('main/get_file', data, function (d) {
                if ('content' in d) {
                    if (found && action === 'install-xml') {
                        editorData.editor.setValue(d.content);
                        editorData.editor.gotoLine(0);
                        editorData.editor.session.getUndoManager().reset();
                        editorData.editor.focus();
                        editorData.modified(false);
                        editorData.editor.session.getSelection().clearSelection();
                        editorData.editor.focus();

                        t.currentEditor(editorData);
                    } else {
                        createEditor(d.content, d.isDiff);
                    }

                    if (!isFirstLoad)
                        updateStatus();

                    if (typeof (next) === 'function') {
                        setTimeout(function () {
                            next(editorData);
                        });
                    }
                } else {
                    if (typeof (next) === 'function')
                        setTimeout(next);
                    else
                        sysMsgs.show(d.error, __MSG_ERROR, true)
                }
            },
            {
                failFn: function () {
                    if (typeof (next) === 'function')
                        setTimeout(next);
                }
            });
    }

    /**
     * Descarga el archivo .ocmod.zip con los cambios, si los hay
     */
    t.downloadZip = function () {
        function doDownload() {
            fetch('ocmod/createZip')
                .then(function (response) {
                    if (!response.ok) {
                        sysMsgs.show('No se ha obtenido una respuesta válida del servidor.', __MSG_ERROR);
                        return;
                    }
                    return response.json();
                })
                .then(function (dlData) {
                    if ('error' in dlData) {
                        sysMsgs.show(dlData.error, __MSG_ERROR, true);
                        return;
                    }

                    if (dlData.errors.length > 0) {
                        sysMsgs.show('<div>Se han encontrado errores:</div><ul><li>' + dlData.errors.join('</li><li>') + '</li></ul>', __MSG_ERROR);
                    }

                    const reqData = new FormData();
                    reqData.append('filename', dlData.dlFilename);

                    //Solicitar el archivo a descargar
                    fetch('ocmod/downloadZip', {method: 'POST', body: reqData})
                        .then(function (response) {
                            if (!response.ok) {
                                sysMsgs.show('No se ha obtenido una respuesta válida del servidor.', __MSG_ERROR);
                                return;
                            }
                            return response.blob();
                        })
                        .then(function (blob) {
                            if (blob.size === 0) {
                                sysMsgs.show('No ha sido posible descargar el archivo.', __MSG_ERROR);
                                return;
                            }
                            const
                                fileBlob = new Blob([blob], {type: 'application/zip'}),
                                url = window.URL.createObjectURL(fileBlob),
                                a = $('<a style="display: none">')
                                    .attr('href', url)
                                    .attr('download', dlData.filename);

                            $('body').append(a);
                            a[0].click();
                            window.URL.revokeObjectURL(url);
                            a.remove();
                        })
                        .catch(function (error) {
                            sysMsgs.show('No se ha obtenido una respuesta válida del servidor.', __MSG_ERROR);
                            console.error('Error:', error);
                        });
                })
                .catch(function (error) {
                    sysMsgs.show('No se ha obtenido una respuesta válida del servidor.', __MSG_ERROR);
                    console.error('Error:', error);
                });
        }

        if (someFileModified()) {
            let dlg = showConfirm('<strong>Al menos uno de los archivos abiertos tiene cambios que no han sido guardados.</strong><br><br>' +
                '¿Desea guardar los archivos modificados antes de proceder?',
                {
                    ok: function () {
                        t.saveAll(doDownload);
                    },
                    no: doDownload,
                    cancel: function () {
                        dlg.modal('hide');
                    }
                }, btnYesNoCancel, true, true);
        } else
            doDownload();
    }

    /**
     * Instala los cambios en OpenCart
     * - Recarga la lista de archivos para reflejar cambios (diff)
     * - Regarga los archivos diff abiertos
     */
    t.install = function () {
        function doInstall() {
            const dlg = getDlgContent('', '<strong>Instalando cambios...</strong>', null, {noButtons: true});

            dlg.on('hidden.bs.modal', function (e) {
                const ed = t.currentEditor();
                if (ed) {
                    ed.editor.renderer.updateFull();
                    ed.editor.focus();
                }
            });

            dlg.on('shown.bs.modal', function (e) {
                doPost('ocmod/install', {}, function (r) {
                    dlg.modal('hide');

                    if ('error' in r)
                        sysMsgs.show(r.error, __MSG_ERROR, true);
                    else {
                        t.activeLeaf(t.activeLeaf());

                        //Actualizar los editores con diff
                        const diffFiles = t.openedFiles()
                            .filter(function (f) {
                                return f.action === 'diff';
                            });

                        function loadNextFile() {
                            if (diffFiles.length === 0)
                                return;

                            let ed = diffFiles.shift(),
                                data = {
                                    file: ed.path + '/' + ed.filename,
                                    action: 'diff'
                                };

                            doPost('main/get_file', data,
                                function (d) {
                                    if ('content' in d) {
                                        if (d.content === false) { //El archivo modificado no existe, cerrar el editor
                                            t.closeFile(ed, true);
                                            return;
                                        }

                                        try {
                                            let markers = ed.editor.session.getMarkers();
                                            for (let id in markers)
                                                ed.editor.session.removeMarker(id);

                                            setDiffContent(ed, ed.lang, d.content);
                                            ed.modified(false);
                                            ed.editor.gotoLine(0);
                                            ed.editor.session.getSelection().clearSelection();
                                            ed.editor.session.getUndoManager().reset();
                                        } catch (e) {
                                        }
                                    }
                                },
                                {
                                    lock: false,
                                    clearMsgs: false,
                                    always: loadNextFile
                                });
                        }

                        loadNextFile();

                        sysMsgs.show('Los cambios han sido instalados con éxito.', __MSG_SUCCESS, true);
                    }
                });
            });

            dlg.modal({keyboard: false, backdrop: 'static'});
        }

        if (someFileModified()) {
            let dlg = showConfirm('<strong>Al menos uno de los archivos abiertos tiene cambios que no han sido guardados.</strong><br><br>' +
                '¿Desea guardar los archivos modificados antes de proceder?',
                {
                    ok: function () {
                        t.saveAll(doInstall);
                    },
                    no: doInstall,
                    cancel: function () {
                        dlg.modal('hide');
                    }
                }, btnYesNoCancel, true, true);
        } else
            doInstall();
    }

    /**
     * Limpia la carpeta modifications del storage de OpenCart
     */
    t.clearModifications = function () {
        showConfirm('¿Está seguro que desea limpiar las modificaciones hechas a OpenCart?',
            {
                ok: function () {
                    $.post('ocmod/clearModifications', {},
                        function (d) {
                            if (d.ok === true) {
                                t.fileList().forEach(function (f) {
                                    f.m(false);
                                });
                            } else {
                                sysMsgs.show(d.result, __MSG_ERROR, true, 3000);
                            }
                        }, 'json');
                }
            });
    }

    /**
     * Crea un nuevo archivo del tipo especificado, sin agregarlo a la lista de archivos (se agrega al guardar)
     * @param f
     */
    t.newFile = function (f) {
        const path = t.curPath();

        showPrompt('Nombre del archivo ' + f.lang + ' (' + f.ext + ')', '',
            function (name) {
                let fileData = {
                    n: ko.observable(name + f.ext),
                    o: ko.observable(false),
                    u: ko.observable(true),
                    m: ko.observable(false),
                    new: true,
                    path: t.curPath()
                }

                t.loadFileFromData(fileData);
            },
            {
                rules: 'required_trim,maxlength[24],regexp[re],checkDups',
                regExp: {
                    re: '/^[a-zA-Z0-9_]+$/'
                },
                custom: [
                    {
                        name: 'checkDups',
                        fn: function (v) {
                            const fname = v + f.ext;

                            return (
                                t.fileList()
                                    .filter(function (fd) {
                                        return fd.n() === fname;
                                    })
                                    .length === 0
                                &&
                                t.openedFiles()
                                    .filter(function (fd) {
                                        return fd.path === path && fd.filename === fname;
                                    })
                                    .length === 0
                            );
                        },
                        msg: 'Ya existe un archivo con el nombre especificado en la carpeta activa'
                    },
                ],
                tooltipPlace: 'bottom'
            }
        );
    }

    /**
     * Eliminar un archivo.
     * - Si el archivo es nuevo (upload) se elimina físicamente y de la lista de archivos
     * - Si el archivo es ocmod, se elimina físicamente y solo actualiza su estado u(false) en la lista de archivos
     * @param file
     * Datos del archivo
     * @param e
     */
    t.deleteFile = function (file, e) {
        e.stopPropagation();

        const path = t.curPath();

        showConfirm('¿Está seguro que desea el archivo: <strong>' + file.n() + '</strong>?',
            {
                ok: function () {
                    $.post('main/deleteFile', {path: getPath(t.activeLeaf()), name: file.n(), m: file.m(), o: file.o(), u: file.u()},
                        function (d) {
                            if (d.result === true) {
                                //Actualizar la cantidad de elementos de cada tipo en los padres
                                let leaf = t.activeLeaf(),
                                    u = file.u(),
                                    o = file.o();

                                do {
                                    if (u) {
                                        leaf.u(Math.max(0, leaf.u() - 1));
                                    } else if (o)
                                        leaf.o(Math.max(0, leaf.o() - 1));

                                    if (leaf.originalLeaf) {
                                        if (u) {
                                            leaf.originalLeaf.u(Math.max(0, leaf.originalLeaf.u() - 1));
                                        } else if (o)
                                            leaf.originalLeaf.o(Math.max(0, leaf.originalLeaf.o() - 1));
                                    }
                                } while (leaf = leaf.parent);

                                if (file.u())
                                    t.fileList.remove(file);
                                else
                                    file.o() && file.o(false);

                                //Si el archivo está abierto, lo cerramos
                                const files = t.openedFiles();
                                for (let i = 0; i < files.length; i++) {
                                    let fl = files[i];
                                    if (fl.path === path && fl.filename === file.n()) {
                                        t.closeFile(fl, true);
                                        break;
                                    }
                                }
                            } else {
                                sysMsgs.show(d.result, __MSG_ERROR, true, 3000);
                            }
                        }, 'json');
                }
            });
    }

    /**
     * Guarda el archivo actual o el archivo especificado
     * - Si el archivo se acaba de crear físicamente, se agrega a la lista de archivos (si la carpeta de destino está activa)
     * - Si el archivo no contiene bloques OCMOD se elimina físicamente
     * @param onSaved
     * Función a ejecutar tras guardar con éxito
     * @param ed
     * Editor a guardar. Si no se especifica se asume el editor activo
     */
    t.save = function (onSaved, ed) {
        if (!ed)
            ed = t.currentEditor();

        const path = t.curPath();
        let leaf = t.activeLeaf();

        const fdesc =
            '<div class="d-flex align-items-center bg-white p-2 rounded-lg">' +
            '  <div style="overflow: hidden; text-overflow: ellipsis">' + ed.path + '/' + ed.filename + '&nbsp;' + '</div>' +
            '  <div><span class="' + t.getActionIcon(ed) + '"></span></div>' +
            '</div>';

        doPost('main/saveFile', {path: ed.path, filename: ed.filename, action: ed.action, content: ed.editor.getValue()},
            function (r) {
                if (r.error) {
                    sysMsgs.show(r.error + '<br>' + fdesc, __MSG_ERROR, true, 5000);

                    saveCancelled = true;
                    closeNextFile();
                    return;
                }

                ed.modified(false);

                function update(prop, delta, fileValue) {
                    do {
                        leaf[prop](leaf[prop]() + delta);
                        if (leaf.originalLeaf)
                            leaf.originalLeaf[prop](leaf.originalLeaf[prop]() + delta);
                    } while (leaf = leaf.parent);

                    if (t.curPath() === ed.path) {
                        const fl = t.fileList();
                        for (let i = 0; i < fl.length; i++) {
                            let file = fl[i];
                            if (file.n() === ed.filename) {
                                file[prop](fileValue);
                                break;
                            }
                        }
                    }
                }

                if (r.noChanges) { //No hay bloques OCMOD (action = get_ocomd)
                    if (r.deleted) {
                        //El archivo existía y fue eliminado al no tener bloques OCMOD, actualizar la cantidad de archivos ocmod en las ramas (-1)
                        update('o', -1, false);
                    }

                    if (typeof onSaved === 'function')
                        setTimeout(onSaved);

                    return;
                }

                if (r.justCreated) {
                    //El archivo se acaba de crear. Actualizar u|o en las ramas del árbol (+1), en dependencia de la acción
                    update(ed.action === 'ocmod' ? 'o' : 'u', 1, true);
                }

                //Si es un archivo nuevo lo agregamos a la lista de archivos, si la ruta activa es la misma que la del archivo
                if (ed.newFileData && ed.newFileData.path === path) {
                    t.fileList.push(ed.newFileData);
                    delete ed.newFileData;
                }

                if (typeof onSaved === 'function')
                    setTimeout(onSaved);
            },
            {
                failFn: function () {
                    sysMsgs.show('No ha sido posible guardar el archivo:<br>' + fdesc, __MSG_ERROR, true, 5000);

                    saveCancelled = true;
                    closeNextFile();
                }
            });
    }

    /**
     * Guarda todos los archivos modificados
     */
    t.saveAll = function (onAllSaved) {
        let unsaved = t.openedFiles().filter(function (f) {
            return f.modified();
        });

        if (unsaved.length > 0)
            t.save(function () {
                t.saveAll(onAllSaved);
            }, unsaved[0]);
        else if (typeof onAllSaved === 'function') {
            onAllSaved();
        }
    }

    /**
     * Cerrar todos los archivos abiertos.
     */
    t.closeAll = function () {
        saveCancelled = false;
        isClosingAll = true;
        closeNextFile();
    }

    /**
     * Cierra un editor (valor en t.openedFiles)
     * @param ed
     * Editor a cerrar
     * @param silent
     * Si se especifica un valor true, no preguntará si el archivo debe ser guardado si está modificado
     */
    t.closeFile = function (ed, silent) {
        if (!ed)
            ed = t.currentEditor();

        saveCancelled = false;

        function removeEditor() {
            ed.editor.session.destroy();

            t.openedFiles.remove(ed);

            if (t.openedFiles().length > 0)
                t.currentEditor(t.openedFiles()[0]);
            else
                t.currentEditor(null);

            closeNextFile();
        }

        if (!silent && (ed.isEditableFile || ed.isUploadFile) && ed.modified()) {
            showConfirm(
                '<div class="text-info"><div style="margin-bottom: 10px">Existen cambios que no han sido guardados en el archivo:</div>' +
                '<div class="ml-4"><strong>' + ed.path + '/' + ed.filename + '</strong></div></div><br>' +
                '¿Desea guardar los cambios antes de cerrar?',
                {
                    ok: function () {
                        t.save(removeEditor);
                    },
                    no: removeEditor,
                    cancel: function () {
                        saveCancelled = true;
                        closeNextFile();
                    }
                },
                btnYesNoCancel,
                true,
                true);
        } else
            removeEditor();
    }

    /**
     * Crea una carpeta dentro de la carpeta activa
     */
    t.createDir = function () {
        if (showPrompt('Nombre de la carpeta', '',
            function (dirName) {
                $.post('main/createDir', {name: dirName, path: getPath(t.activeLeaf())},
                    function (d) {
                        if (d.result === true) {
                            let act = t.activeLeaf();
                            let isFilteredTree = 'originalLeaf' in act;

                            let origLeaf = cloneLeaf(act);
                            origLeaf.n(dirName);
                            origLeaf.parent = isFilteredTree ? act.originalLeaf : act;
                            origLeaf.o(0);
                            origLeaf.u(0);
                            origLeaf.f(0);
                            origLeaf.new(true);
                            origLeaf.opened(true);
                            delete origLeaf.originalLeaf;
                            origLeaf.parent.c.push(origLeaf);

                            if (isFilteredTree) {
                                let newLeaf = cloneLeaf(origLeaf);
                                newLeaf.parent = act;
                                newLeaf.originalLeaf = origLeaf;
                                act.c.push(newLeaf);

                                while (newLeaf = newLeaf.parent)
                                    newLeaf.f(newLeaf.f() + 1);
                            }

                            while (origLeaf = origLeaf.parent)
                                origLeaf.f(origLeaf.f() + 1);
                        } else {
                            sysMsgs.show(d.result, __MSG_ERROR, true, 3000);
                        }
                    }, 'json');
            },
            {
                rules: 'required,maxlength[24],alphanum[0,0]'
            })) ;
    }

    /**
     * Renombrar la carpeta activa.
     * - Se actualiza la ruta activa (curPath)
     * - Se actualiza la ruta de los archivos abiertos
     * - Se actualiza la última ruta en la configuración del proyecto activo
     */
    t.renameDir = function () {
        const lastPath = t.curPath();

        showPrompt('Nuevo nombre para <strong>' + t.activeLeaf().n() + '</strong>', t.activeLeaf().n(),
            function (newName) {
                let act = t.activeLeaf();
                $.post('main/renameDir', {path: getPath(t.activeLeaf()), name: newName},
                    function (d) {
                        if (d.result == true) {
                            act.n(newName);
                            if ('originalLeaf' in act)
                                act.originalLeaf.n(newName);

                            const newPath = getPath(act);

                            t.curPath(newPath);

                            //Actualizar las rutas de archivos abiertos con la ruta nueva
                            t.openedFiles().forEach(function (f) {
                                if (f.path === lastPath)
                                    f.path = newPath;
                            });

                            t.currentEditor(t.currentEditor());

                            saveLastPath();
                        } else
                            sysMsgs.show(d.result, __MSG_ERROR, true, 3000);
                    }, 'json');
            });
    }

    /**
     * Eliminar la carpeta activa
     * - Se actualiza la cantidad de archivos nuevos y/o modificados y la cantidad de carpetas nuevas
     * - Se cierran los archivos abiertos que estén dentro de la ruta eliminada
     */
    t.removeDir = function () {
        const path = t.curPath();

        showConfirm(
            '<strong>Esta acción eliminará la carpeta seleccionada, incluyendo su contenido.</strong><br><br>' +
            '¿Está seguro que desea eliminarla?',
            {
                ok: function () {
                    $.post('main/removeDir', {path: getPath(t.activeLeaf())},
                        function (d) {
                            if (d.result === true) {
                                let act = t.activeLeaf(),
                                    o = act.o(),
                                    u = act.u(),
                                    f = act.f() + 1,
                                    p = act.parent;

                                if ('originalLeaf' in act)
                                    act.originalLeaf.parent.c.splice(act.originalLeaf.parent.c().indexOf(act), 1);

                                p.c.splice(p.c().indexOf(act), 1);

                                do {
                                    p.o(p.o() - o);
                                    p.u(p.u() - u);
                                    p.f(p.f() - f);

                                    if (p.originalLeaf) {
                                        p.originalLeaf.o(p.o());
                                        p.originalLeaf.u(p.u());
                                        p.originalLeaf.f(p.f());
                                    }
                                } while (p = p.parent);

                                t.activeLeaf(act.parent);

                                //Cerrar los archivos abiertos que estuvieran dentro de la carpeta o en otras dentro de la misma
                                t.openedFiles()
                                    .filter(function (f) {
                                        return f.path === path || f.path.startsWith(path + '/');
                                    })
                                    .forEach(function (f) {
                                        t.closeFile(f, true);
                                    });
                            } else {
                                sysMsgs.show('No ha sido posible eliminar la carpeta.', __MSG_ERROR, true, 3000);
                            }
                        }, 'json');
                }
            });
    }

    /*$(window).on('beforeunload', function (e) {
        let msg = 'Seguro?';
        e.preventDefault();
        e.stopPropagation();
        //e.cancel = true;
        return 'jj';
    });*/

    let cutBlock = null,
        clipboardAction = null;

    t.openedFiles = ko.observableArray([]);

    t.currentEditor = ko.observable();
    t.currentEditor.extend({notify: 'always'});
    t.currentEditor.subscribe(function (cur) {
        if (cur) {
            cur.editor.renderer.updateFull();
            cur.editor.focus();
        }
        enableNavButtons();
    });

    function isOpenOCMODTag(line) {
        return !!line.match(/({#|\/\*|<!--)?[ \t]*<OCMOD>[ \t]*(#}|\*\/|-->)?/ig);
    }

    function isCloseOCMODTag(line) {
        return !!line.match(/({#|\/\*|<!--)?[ \t]*<\/OCMOD>[ \t]*(#}|\*\/|-->)?/ig);
    }

    function isOCMODTag(line) {
        return !!line.match(/({#|\/\*|<!--)?[ \t]*<\/?OCMOD>[ \t]*(#}|\*\/|-->)?/ig);
    }

    function getCurrentOCMODRange(range) {
        let ed = t.currentEditor(),
            selection = ed.editor.getSelection(),
            selRange = range || selection.getRange(),
            resultRange = new ace.Range(-1, -1, -1, -1);

        if (selRange.start.row === selRange.end.row /*&& selRange.start.column === selRange.end.column*/) {
            resultRange.emptySelection = selRange.emptySelection = true;

            if (ed.editor.getReadOnly()) {
                let line = ed.editor.session.getLine(selRange.start.row).toUpperCase();
                if (!isOCMODTag(line))
                    return resultRange;
            }

            let lines = [];

            do {
                let line = ed.editor.session.getLine(selRange.start.row);
                lines.unshift(line);
                if (isOpenOCMODTag(line))
                    break;
            } while (--selRange.start.row >= 0);

            let docLen = ed.editor.session.getLength();

            if (!isCloseOCMODTag(lines[lines.length - 1])) {
                while (++selRange.end.row < docLen) {
                    let line = ed.editor.session.getLine(selRange.end.row);
                    lines.push(line);
                    if (isCloseOCMODTag(line))
                        break;
                }
            }

            selRange.start.column = 0;
            selRange.end.column = 0;
            selRange.lines = lines;

            return selRange;
        }

        resultRange.emptySelection = false;

        return resultRange;
    }

    /*
    Alt-O: Insertar bloque OCMOD
    Alt-P: Alterna la etiqueta <path_override>
    Shift-Del: Eliminar bloque activo
    Ctrl-Shift-Up: Subir bloque activo
    Ctrl-Shift-Down: Bajar bloque activo
    Ctrl-Shift-C: Copia el bloque activo, no produce cambio visual
    Ctrl-Shift-X: Corta el bloque activo y permite pegarlo solo una vez
    Ctrl-Shift-V: Pega el bloque copiado/cortado
    */

    ace.require("ace/ext/language_tools");

    let initializeCommands = true;

    function initializeEditor(ed) {
        let editor = ed.editor,
            tout = null;

        function copy_cut(action) {
            let ed = t.currentEditor();

            if (ed.isEditableFile && !ed.isUploadFile) {
                let range = getCurrentOCMODRange();
                if (range.start.row >= 0) {
                    if (action == 'cut')
                        ed.editor.session.doc.removeFullLines(range.start.row, range.end.row);
                    cutBlock = range;
                    clipboardAction = action;
                }
            }
        }

        function getCommentsFromContext(session, row) {
            session.doc.insertMergedLines({row: row, column: 0}, ['   ', '']);
            editor.renderer.updateFull(true);
            
            session.getMode().toggleBlockComment(
                session.getState(row),
                session,
                {start: {row: row, column: 0}, end: {row: row, column: 3}},
                {row: row, column: 3}
            );
            let line = session.getLine(row),
                comments = line.match(/([^ ]+)\s+([^ ]+)/);

            session.doc.removeFullLines(row, row);

            return {start: comments[1], end: comments[2]};
        }

        //Inserta un bloque OCMOD indentado y con los comentarios ajustados según el contexto
        function insertIndentedBlock(lines, row, indentRow) {
            const sess = editor.session,
                comments = getCommentsFromContext(sess, row);

            let indentLine, lineCount = sess.getLength();
            do {
                indentLine = sess.getLine(indentRow++);
                if (indentLine.trim())
                    break;
            } while (indentRow < lineCount);

            let re = /^([\s\t]+).*/,
                m = re && indentLine.match(re);

            lines = lines.map(function (ln) {
                let ml = ln.match(re);

                if (m)
                    ln = ml ? m[1] + ln.substring(ml[1].length) : m[1] + ln;
                else if (ml)
                    ln = ln.substring(ml[1].length);

                return ln
                    .replace(/^(\s*)({#|\/\*|<!--)?([ \t]*<\/?(OCMOD|search|add)(>| [^>\r\n]*>)[ \t]*)(#}|\*\/|-->)?/ig,
                        '$1' + comments.start + '$3' + comments.end)
                    .replace(/^(\s*)({#|\/\*|<!--)?([ \t]*<path_override\s*path=(['"]).*\4\/>[ \t]*)(#}|\*\/|-->)?(\s*)/ims,
                        '$1' + comments.start + '$3' + comments.end);
            });

            lines.push('');

            sess.doc.insertMergedLines({row: row, column: 0}, lines);

            return comments;
        }

        function updateStatusDebounce() {
            if (!(isFirstLoad || tout)) {
                tout = setTimeout(function () {
                    tout = null;
                    updateStatus();
                }, 3000);
            }
        }

        /*New commands*/
        editor.commands.addCommand({
            name: "togglePathOverride",
            bindKey: {win: "Alt-P", mac: "Command-Option-P"}, // Asignar la combinación de teclas Alt+P
            exec: function (editor) {
                let ed = t.currentEditor();

                if (!ed.isEditableFile || ed.isUploadFile)
                    return;

                let selection = editor.getSelection(),
                    range = getCurrentOCMODRange();

                if (range.start.row >= 0) {
                    let re = /^\s*({#|\/\*|<!--)?[ \t]*<path_override\s*path=(['"]).*\2\/>[ \t]*(#}|\*\/|-->)?\s*/ims,
                        match = range.lines.join('\n').match(re);

                    if (match) {
                        let row = range.start.row + 1;
                        while (!editor.session.getLine(row).match(/^\s*({#|\/\*|<!--)?[ \t]*<path_override\s*path=(['"]).*\2\/>[ \t]*(#}|\*\/|-->)?$/is))
                            row++;
                        while (!editor.session.getLine(row + 1).trim())
                            row++;

                        editor.session.doc.removeFullLines(range.start.row + 1, row);
                        selection.cursor.setPosition(range.start.row + 1, 0);
                        selection.clearSelection();
                    } else {
                        const comments = insertIndentedBlock(['/*<path_override path=""/>*/'],
                            range.start.row + 1, Math.max(0, range.start.row));

                        selection.cursor.setPosition(range.start.row + 1, editor.session.getLine(range.start.row + 1).length - comments.end.length - 3);
                        selection.clearSelection();
                    }
                }
            },
            readOnly: true // Permitir la edición
        });

        editor.commands.addCommand({
            name: "insertOCMODBlock",
            bindKey: {win: "Alt-O", mac: "Command-Option-O"}, // Asignar la combinación de teclas Alt+O
            exec: function (editor) {
                let ed = t.currentEditor();

                if (!editor.getReadOnly() || !ed.isEditableFile || ed.isUploadFile)
                    return;

                let selection = editor.getSelection();
                let range = selection.getRange();

                if (range.start.row === range.end.row && range.start.column === range.end.column) {
                    //No permitir incluir un bloque dentro de otro
                    if (isOpenOCMODTag(editor.session.getLine(range.start.row)))
                        return;

                    let token = editor.session.getTokenAt(range.start.row, range.start.column);
                    if (token && token.type) {
                        let tokens = token.type.split('.');
                        if (tokens.indexOf('ocmod-tag') >= 0)
                            return;
                    }

                    const comments = getCommentsFromContext(editor.session, range.start.row);

                    let lines = [
                        comments.start + '<OCMOD>' + comments.end,
                        comments.start + '<search trim="false">' + comments.end,
                        '',
                        comments.start + '</search>' + comments.end,
                        comments.start + '<add position="before">' + comments.end,
                        '',
                        comments.start + '</add>' + comments.end,
                        comments.start + '</OCMOD>' + comments.end,
                    ];

                    insertIndentedBlock(lines, range.start.row, Math.max(0, range.start.row - 1));

                    selection.cursor.setPosition(range.start.row + 2, editor.session.getLine(range.start.row + 2).length + 1);
                    selection.clearSelection();
                }
            },
            readOnly: true // Permitir la edición
        });

        editor.commands.addCommand({
            name: "deleteOCMODBlock",
            bindKey: {win: "Shift-Delete", mac: "Shift-Delete"},
            exec: function (editor) {
                let ed = t.currentEditor();

                if (ed.isEditableFile && !ed.isUploadFile) {
                    let range = getCurrentOCMODRange();
                    if (range.start.row >= 0)
                        editor.session.doc.removeFullLines(range.start.row, range.end.row);
                }
            },
            readOnly: true
        });

        editor.commands.addCommand({
            name: "copyOCMODBlock",
            bindKey: {win: "Ctrl-Shift-C", mac: "Ctrl-Shift-C"},
            exec: function () {
                copy_cut('copy');
            },
            readOnly: true
        });

        editor.commands.addCommand({
            name: "cutOCMODBlock",
            bindKey: {win: "Ctrl-Shift-X", mac: "Ctrl-Shift-X"},
            exec: function () {
                copy_cut('cut');
            },
            readOnly: true
        });

        editor.commands.addCommand({
            name: "pasteOCMODBlock",
            bindKey: {win: "Ctrl-Shift-V", mac: "Ctrl-Shift-V"},
            exec: function (editor) {
                let ed = t.currentEditor();

                if (cutBlock && ed.isEditableFile && !ed.isUploadFile) {
                    let range = getCurrentOCMODRange();
                    if (range.start.row < 0 && range.emptySelection) {
                        let curPos = editor.getCursorPosition().row;
                        insertIndentedBlock(cutBlock.lines, curPos, Math.max(0, curPos - 1));
                        if (clipboardAction === 'cut')
                            cutBlock = null;
                    }
                }
            },
            readOnly: true
        });

        editor.commands.addCommand({
            name: "moveOCMODBlockUp",
            bindKey: {win: "Ctrl-Shift-Up", mac: "Ctrl-Shift-Up"},
            exec: function (editor) {
                let ed = t.currentEditor();

                if (!ed.isEditableFile || ed.isUploadFile)
                    return;

                let range = getCurrentOCMODRange();

                if (range.start.row > 0) {
                    //Let's see if the line above the block is the end of another block
                    let upRow = range.start.row - 1,
                        prevLine = editor.session.getLine(upRow);

                    let sel = editor.getSelection(),
                        curPos = sel.cursor.getPosition(),
                        deltaRow = curPos.row - range.start.row,
                        deltaCol = editor.session.getLine(curPos.row).length - curPos.column;

                    if (isCloseOCMODTag(prevLine)) {
                        let aboveBlockRange = getCurrentOCMODRange(new ace.Range(upRow, 0, upRow, 0));
                        if (aboveBlockRange.start.row >= 0)
                            upRow = aboveBlockRange.start.row;
                    }

                    editor.session.doc.removeFullLines(range.start.row, range.end.row);

                    insertIndentedBlock(range.lines, upRow, Math.max(0, upRow - 1));

                    let cursorRow = upRow + deltaRow;

                    sel.cursor.setPosition(cursorRow, editor.session.getLine(cursorRow).length - deltaCol);
                    sel.clearSelection();

                    if (!editor.isRowVisible(cursorRow))
                        editor.scrollToLine(cursorRow, true, true);
                }
            },
            readOnly: true
        });

        editor.commands.addCommand({
            name: "moveOCMODBlockDown",
            bindKey: {win: "Ctrl-Shift-Down", mac: "Ctrl-Shift-Down"},
            exec: function (editor) {
                let ed = t.currentEditor();

                if (!ed.isEditableFile || ed.isUploadFile)
                    return;

                let range = getCurrentOCMODRange();

                if (range.end.row >= 0 && range.end.row < editor.session.getLength() - 2) {
                    let sel = editor.getSelection(),
                        curPos = sel.cursor.getPosition(),
                        deltaRow = curPos.row - range.start.row,
                        deltaCol = editor.session.getLine(curPos.row).length - curPos.column;

                    editor.session.doc.removeFullLines(range.start.row, range.end.row);

                    let belowRow = range.start.row;

                    //Let's see if the line below the block is the end of another block
                    if (isOpenOCMODTag(editor.session.getLine(belowRow))) {
                        let belowBlockRange = getCurrentOCMODRange(new ace.Range(belowRow, 0, belowRow, 0));
                        if (belowBlockRange.end.row >= 0)
                            belowRow = belowBlockRange.end.row + 1;
                    } else
                        belowRow++;

                    insertIndentedBlock(range.lines, belowRow, range.start.row);

                    let cursorRow = belowRow + deltaRow;

                    sel.clearSelection();
                    sel.cursor.setPosition(cursorRow, editor.session.getLine(cursorRow).length - deltaCol);
                    if (!editor.isRowVisible(cursorRow + 1))
                        editor.scrollToLine(cursorRow, true, true);
                }
            },
            readOnly: true
        });

        /*Events*/
        editor.session.on('changeScrollTop', updateStatusDebounce);

        editor.session.on('changeScrollLeft', updateStatusDebounce);

        editor.session.on('changeSelection', function (delta) {
            let ed = t.currentEditor();

            updateStatusDebounce();

            if (!ed.isEditableFile || ed.isUploadFile)
                return;

            let selection = editor.getSelection();

            if (selection.ranges.length > 0) { //No permitir selecciones múltiples
                editor.setReadOnly(true);
                return;
            }

            let cursorPosition = editor.getCursorPosition();

            //Permitir escribir solamente dentro del bloque
            let row = cursorPosition.row;
            while (row >= 0) {
                let line = editor.session.getLine(row);
                let token = editor.session.getTokenAt(row, line.length);

                if (token && token.type) {
                    let tokens = token.type.split('.');
                    if (tokens.indexOf('ocmod-tag') >= 0) {
                        let isReadOnly = true;
                        if (row < cursorPosition.row)
                            isReadOnly = tokens.indexOf('ocmod-end') >= 0;

                        editor.setReadOnly(isReadOnly);
                        if (isReadOnly)
                            return;

                        break;
                    }
                }

                if (isCloseOCMODTag(line)) {
                    editor.setReadOnly(true);
                    return;
                }

                row--;
            }

            if (row < 0) {
                editor.setReadOnly(true);
                return;
            }

            //Verificar que la selección no contenga etiquetas OCMOD, search o add
            let range = selection.getRange();
            //, emptySelection = range.start.row === range.end.row && range.start.column === range.end.column;

            row = range.start.row;
            while (row <= range.end.row) {
                let tokens = editor.session.getTokens(row);
                for (let i = 0; i < tokens.length; i++) {
                    let t = tokens[i].type.split('.');
                    if (t.indexOf('ocmod-tag') >= 0) {
                        editor.setReadOnly(true);
                        return;
                    }
                }

                let line = editor.session.getLine(row);

                if (isCloseOCMODTag(line)) {
                    editor.setReadOnly(true);
                    return;
                }

                row++;
            }
        });

        editor.session.on('change', function (e, v) {
            ed.modified(true);
        });

        /**********************************/
        editor.setOptions({
            dragEnabled: false,
            enableBasicAutocompletion: true,
            enableSnippets: true,
            enableLiveAutocompletion: false
        });

        if (ed.action === 'ocmod')
            editor.setOption("enableMultiselect", false); //No permitir múltiples cursores en archivos ocmod

        ed.editor.session.setMode("ace/mode/" + ed.lang, highlightFactory(ed));

        const cmds = editor.commands.byName;
        cmds.undo.readOnly = true;
        cmds.redo.readOnly = true;

        if (initializeCommands) {
            initializeCommands = false;

            //No permitir estos comandos
            ['togglerecording', 'openCommandPalette', 'openCommandPallete', 'replace']
                .forEach(function (cmd) {
                    cmds[cmd].exec = function () {
                    }
                })

            const ocmodBlockRE = /^[ \t]*(?:{#|\/\*|<!--)<OCMOD>(?:#}|\*\/|-->)\s*^(?:\s*(?:{#|\/\*|<!--)?[ \t]*<(?<path>path_override)\s*path=(['"]).*\2[ \t]*\/>[ \t]*(?:#}|\*\/|-->)?[\r\n])?(^\s*(?:{#|\/\*|<!--)?[ \t]*<(search|add)(>| [^>\r\n]*>)[ \t]*(?:#}|\*\/|-->)?[\r\n](.*)^\s*(?:{#|\/\*|<!--)?[ \t]*<\/\4>[ \t]*(?:#}|\*\/|-->)?[\r\n]){2}^\s*(?:{#|\/\*|<!--)<\/OCMOD>(?:#}|\*\/|-->)$/ims;

            //Siempre que se ejecuten los siguientes comandos, chequear que no rompe la integridad del bloque OCMOD
            [
                'backspace', 'insertstring', 'del', 'cut', 'paste', 'transposeletters',
                'togglecomment', 'toggleBlockComment', 'splitline', 'splitSelectionIntoLines', 'sortlines',
                'removeline', 'removewordleft', 'removewordright', 'removetolinestarthard', 'removetolinestart',
                'removetolineendhard', 'removetolineend',
                'copylinesdown', 'copylinesup', 'duplicateSelection', 'movelinesdown', 'movelinesup'
            ].forEach(
                function (cmd) {
                    const oldExec = cmds[cmd].exec;
                    cmds[cmd].exec = function (editor, args) {
                        let sess = editor.session,
                            ed = t.openedFiles().filter(function (e) {
                                return e.editor === editor
                            });

                        if (!ed)
                            return;

                        ed = ed[0];

                        const modified = ed.modified();

                        oldExec(editor, args);

                        const range = getCurrentOCMODRange(),
                            lines = range.lines ? range.lines.join('\n') : '';

                        if (!ocmodBlockRE.test(lines)) {
                            editor.undo();

                            sess.$undoManager.$redoStack.pop();     //No permitir el redo
                            sess.getSelection().clearSelection();

                            ed.modified(modified);
                        }
                    }
                }
            );
        }
    }

    const langsLoaded = [];

    function highlightFactory(editorData) {
        if (langsLoaded.indexOf(editorData.lang) >= 0)
            return;

        langsLoaded.push(editorData.lang);

        const startRe = /({#|\/\*|<!--)?[ \t]*<OCMOD>[ \t]*(#}|\*\/|-->)?/,
            endRe = /({#|\/\*|<!--)?[ \t]*<\/OCMOD>[ \t]*(#}|\*\/|-->)?/,
            langStarts = {
                twig: ['js-start', 'css-start', 'js-no_regex', 'start'],
                php: ['php-start', 'js-start', 'css-start', 'start'],
                javascript: ['start']
            }

        if (editorData.lang in langStarts) {
            return function () {
                langStarts[editorData.lang].forEach(function (start) {
                    editorData.editor.session.$mode.$highlightRules.$rules[start].unshift(
                        {
                            token: '.ocmod-tag.ocmod-start',
                            regex: startRe,
                            next: start
                        },
                        {
                            token: '.ocmod-tag.ocmod-end',
                            regex: endRe,
                            next: start
                        }
                    );
                })

                // Force recreation of tokenizer
                editorData.editor.session.$mode.$tokenizer = null;
                editorData.editor.session.bgTokenizer.setTokenizer(editorData.editor.session.$mode.getTokenizer());

                // force re-highlight whole document
                // editorData.editor.session.bgTokenizer.start(0);
            }
        }
    }

    enableNavButtons();

    //Abrir los últimos archivos abiertos
    if ('openedFiles' in projectData()) {
        const dlg = getDlgContent('', '<div><strong>Abriendo archivo...</strong></div><div class="d-inline-block text-info" id="fname"></div><div id="file"></div>', null, {noButtons: true}),
            projData = projectData(),
            lastOpenedFileData = projData.lastOpenedFile >= 0 ? projData.openedFiles[projData.lastOpenedFile] : '';

        function loadOpenedFiles() {
            if (projData.openedFiles) {
                const fileData = projData.openedFiles.shift();

                if (fileData) {
                    const fdata = fileData.split('|');

                    $('#file', dlg).removeClass().addClass('d-inline-block ml-3 ' + t.getActionIcon({action: fdata[0]}));
                    $('#fname', dlg).text(fdata[2] + ' ');

                    t.loadFile(fdata[2], fdata[0],
                        function (editorData) {
                            if (editorData) {
                                const cPos = fdata[1].split(','),
                                    sess = editorData.editor.session;
                                sess.getSelection().cursor.setPosition(parseInt(cPos[0]), parseInt(cPos[1]));
                                sess.setScrollLeft(parseInt(cPos[2]));
                                sess.setScrollTop(parseInt(cPos[3]));
                            }

                            setTimeout(loadOpenedFiles, 100);
                            // loadOpenedFiles();
                        });

                    return;
                }
            }

            isFirstLoad = false;

            //Buscar el último archivo activo y activarlo
            if (lastOpenedFileData) {
                const fdata = lastOpenedFileData.split('|');

                if (t.openedFiles().length > 0) {
                    let ed = t.openedFiles().filter(function (f) {
                        return f.path + '/' + f.filename === fdata[2] && f.action === fdata[0];
                    });

                    if (ed.length === 0)
                        ed = t.openedFiles();

                    t.currentEditor(ed[0]);

                    ed[0].editor.focus();
                }
            }

            dlg.modal('hide');
        }

        if (projData.openedFiles && projData.openedFiles.length > 0) {
            dlg.on('shown.bs.modal', function (e) {
                loadOpenedFiles();
            })

            dlg.modal({keyboard: false, backdrop: 'static'});
        } else
            loadOpenedFiles();
    }

    $('.dropdown-menu a.dropdown-toggle').on('click', function (e) {
        if (!$(this).next().hasClass('show')) {
            $(this).parents('.dropdown-menu').first().find('.show').removeClass('show');
        }
        var $subMenu = $(this).next('.dropdown-menu');
        $subMenu.toggleClass('show');


        $(this).parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function (e) {
            $('.dropdown-submenu .show').removeClass('show');
        });


        return false;
    });
}

function fn(e, va, ab, vm, bc) {
    var v = va();
    if (typeof (v) == 'string') {
        if (v.charAt(0) === '$')
            vm[v] = $(e).first();
        else
            vm[v]['$element'] = $(e).first();
    } else
        vm['$element'] = $(e).first();
}

ko.bindingHandlers['$element'] = {init: fn, update: fn};

ko.applyBindings(new Model, $('#content')[0]);