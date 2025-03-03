<?php
$cfg = App::Config();
$projectsCfg = $cfg->projects;
$project = App::project();
$currentProject = App::currentProject();
$currentProjectIndex = $index = 0;

$projects = [];
if ($currentProject) {
    foreach ($projectsCfg as $id => &$value) {
        if ($currentProject == $id)
            $currentProjectIndex = $index;

        $projects[] = [
            'id' => $id,
            'name' => $value['projectName'],
        ];

        $index++;
    }
}

Views::BeginBlock('content');
?>

<!--Editor options dialog contents-->
<div class="d-none row" id="editorOptions">
    <div class="col-4">
        <div class="form-group">
            <label for="theme">Tema</label>
            <select id="theme" class="form-control" data-bind="foreach: themes">
                <option data-bind="text: text, attr: {value: value}"></option>
            </select>
        </div>
        <div class="form-group">
            <label for="aa">Tamaño de letra</label>
            <div class="d-flex justify-content-between align-items-center border rounded rounded-lg" data-bind="foreach: sizes">
                <button id="aa" type="button" class="btn p-1 btnSize flex-grow-1" style="width: 20%; height: 40px"
                        data-bind="text: $data, style: {'font-size': $data + 'pt'}, css: {'btn-dark': $data == $root.editorFontSize()},
                            click: function() { $root.editorFontSize($data); }"></button>
            </div>
        </div>
        <div class="form-group">
            <label for="tabSize">Ancho de tabulación</label>
            <div class="d-flex justify-content-between align-items-center border rounded rounded-lg" data-bind="foreach: tabSizes">
                <button id="aa" type="button" class="btn p-1 btnTabSize flex-grow-1" style="width: 20%; height: 40px"
                        data-bind="text: $data, css: {'btn-dark': $data == $root.editorTabSize()},
                            click: function() { $root.editorTabSize($data); }"></button>
            </div>
        </div>
        <div class="form-group form-check mt-3 mb-0">
            <input type="checkbox" class="form-check-input" id="lineNums">
            <label class="form-check-label" for="lineNums">Mostrar números de línea</label>
        </div>
        <div class="form-group form-check mb-0">
            <input type="checkbox" class="form-check-input" id="wrap">
            <label class="form-check-label" for="wrap">Ajustar líneas largas</label>
        </div>
        <div class="form-group form-check mt-0">
            <input type="checkbox" class="form-check-input" id="tabs">
            <label class="form-check-label" for="tabs">Sangría con tabulador</label>
        </div>
    </div>
    <div class="col-8">
        <div id="editorSample" class="border border-dark" style="width: 100%; height: 300px"></div>
    </div>
</div>

<!--New project dialog contents-->
<div id="newProject" class="d-none">
    <div class="row">
        <div class="col-6 col-lg-4">
            <div class="form-group">
                <label for="name">Nombre del proyecto</label>
                <input type="text" class="form-control" name="projectName" id="name" placeholder="Nombre del proyecto" autocomplete="false"
                       value="" data-rule="required_trim,regexp[re]" data-re="/^[\w\sáéíóúÁÉÍÓÚñÑüÜ]{3,64}$/i">
            </div>
            <div class="form-group">
                <label for="root">Carpeta raíz de OpenCart</label>
                <input type="text" class="form-control" name="root_path" id="root" placeholder="Carpeta raíz de OpenCart" value=""
                       data-rule="required_trim,checkRoot">
            </div>
        </div>
        <div class="col-6 col-lg-8">
            <div class="form-group">
                <label for="zipFilename">Nombre .ocmod.zip</label>
                <input type="text" class="form-control" name="zipFilename" id="zipFilename" placeholder="Nombre del archivo .ocmod.zip"
                       autocomplete="false" value="" data-rule="required_trim,regexp[re]" data-re="/^[a-z0-9_-]{3,64}$/i">
            </div>
            <div class="form-group">
                <label for="url">URL de OpenCart</label>
                <input type="text" class="form-control" name="url" id="url" placeholder="URL de OpenCart" value=""
                    data-rule="required,url,regexp[re],checkURL" data-re="/^https?/", data-msg="||La URL debe ser http o https">
            </div>
        </div>
    </div>
    <div class="card card-dark">
        <div class="card-header pl-2">Datos de archivo OCMod</div>
        <div class="card-body pt-2 pb-0" style="white-space: nowrap; overflow: hidden">
                <pre class="d-inline p-0">
&lt;?xml version="1.0" encoding="utf-8"?&gt;
&lt;modification&gt;
  &lt;name&gt;</pre>
            <div class="form-group d-inline-block mb-1">
                <input type="text" class="form-control form-control-sm" name="name" placeholder="Nombre" data-tooltip-place="right"
                       value="" data-rule="required_trim,maxlength[64],regexp[re]" data-re="/^[\w\sáéíóúÁÉÍÓÚñÑüÜ]{3,64}$/i">
            </div>
            <pre class="d-inline p-0" style="line-height: 1em">&lt;/name&gt;
  &lt;code&gt;</pre>
            <div class="form-group d-inline-block mb-1">
                <input type="text" class="form-control form-control-sm" name="code" placeholder="Código" data-tooltip-place="right"
                       value="" data-rule="required_trim,maxlength[64],regexp[re]" data-re="/^[a-z_-]{3,64}$/">
            </div>
            <pre class="d-inline p-0">&lt;/code&gt;
  &lt;version&gt;</pre>
            <div class="form-group d-inline-block mb-1">
                <input type="text" class="form-control form-control-sm" name="version" placeholder="Versión" data-tooltip-place="right"
                       value="" data-rule="required_trim,maxlength[32],regexp[re]" data-re="/^[1-9][0-9]{0,3}\.(0|[1-9][0-9]{0,3})(\.(0|[1-9][0-9]{0,3}))?$/">
            </div>
            <pre class="d-inline p-0">&lt;/version&gt;
  &lt;author&gt;</pre>
            <div class="form-group d-inline-block mb-1">
                <input type="text" class="form-control form-control-sm" name="author" placeholder="Autor" data-tooltip-place="right"
                       value="" data-rule="required_trim,maxlength[64],regexp[re]" data-re="/^[\w\sáéíóúÁÉÍÓÚñÑüÜ]{3,64}$/i">
            </div>
            <pre class="d-inline p-0">&lt;/author&gt;
  &lt;link&gt;</pre>
            <div class="form-group d-inline-block mb-1">
                <input type="text" class="form-control form-control-sm" name="link" placeholder="Link" data-tooltip-place="right"
                       value="" data-rule="maxlength[255],url">
            </div>
            <pre class="d-inline p-0">&lt;/link&gt;
&lt;/modification>
                </pre>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="form-group form-check mt-3 mb-0">
                <input type="checkbox" class="form-check-input" id="openProj" value="1" checked>
                <label class="form-check-label" for="openProj">Abrir proyecto luego de ser creado</label>
            </div>
        </div>
    </div>
</div>

<nav class="main-header navbar navbar-expand navbar-dark ml-0" data-bind="let: {noProj: !currentProject()}">
    <ul class="navbar-nav">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="mnuFile" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Archivo</a>
            <div class="dropdown-menu" aria-labelledby="mnuFile"
                 data-bind="let: {noFileOpened: openedFiles().length == 0,
                                  saveDisabled: openedFiles().length == 0 || (currentEditor() && !currentEditor().modified())}">
                <div class="dropdown-submenu">
                    <a class="dropdown-item dropdown-toggle" href="#" id="newDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                       aria-expanded="false">Nuevo</a>
                    <div class="dropdown-menu" aria-labelledby="newDropdown">
                        <a class="dropdown-item" href="#"
                           data-bind="css: {disabled: noProj}, click: function() { newFile({lang: 'PHP', ext:'.php'}); }">PHP</a>
                        <a class="dropdown-item" href="#"
                           data-bind="css: {disabled: noProj}, click: function() { newFile({lang: 'Javascript', ext:'.js'}); }">Javascript</a>
                        <a class="dropdown-item" href="#"
                           data-bind="css: {disabled: noProj}, click: function() { newFile({lang: 'Twig', ext:'.twig'}); }">Twig</a>
                    </div>
                </div>
                <a class="dropdown-item" href="#" data-bind="click: function() { save(); }, disable: saveDisabled,
                    css: {disabled: saveDisabled}">Guardar</a>
                <a class="dropdown-item" href="#" data-bind="click: saveAll, disable: !someModified(),
                    css: {disabled: saveDisabled}">Guardar todo</a>
                <a class="dropdown-item" href="#" data-bind="click: function() { closeFile(); }, disable: noFileOpened,
                    css: {disabled: noFileOpened}">Cerrar</a>
                <a class="dropdown-item" href="#" data-bind="click: closeAll, disable: noFileOpened,
                    css: {disabled: noFileOpened}">Cerrar todo</a>
                <!--<div class="dropdown-divider"></div>-->
            </div>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="mnuProj" role="button" data-toggle="dropdown" aria-haspopup="true"
               aria-expanded="false">Proyecto</a>
            <div class="dropdown-menu" aria-labelledby="mnuProj">
                <a class="dropdown-item" href="#" data-bind="click: newProject">Crear nuevo proyecto...</a>
                <a class="dropdown-item" href="#" data-bind="css: {disabled: noProj}, click: updateProject">Actualizar datos...</a>
            </div>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="mnuOcmod" role="button" data-toggle="dropdown" aria-haspopup="true"
               aria-expanded="false">OCMOD</a>
            <div class="dropdown-menu" aria-labelledby="mnuOcmod"
                 data-bind="let: {noFileOpened: openedFiles().length == 0, saveDisabled: openedFiles().length == 0 || (currentEditor() && !currentEditor().modified())}">
                <a class="dropdown-item" href="#" data-bind="css: {disabled: noProj}, click: install">Instalar cambios en OpenCart</a>
                <a class="dropdown-item" href="#" data-bind="css: {disabled: noProj}, click: clearModifications">Limpiar modificaciones</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-bind="css: {disabled: noProj}, click: downloadZip">Descargar archivo ocmod.zip</a>
            </div>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="mnuEdit" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Editor</a>
            <div class="dropdown-menu" aria-labelledby="mnuEdit">
                <a class="dropdown-item" href="#" data-bind="click: setEditorOptions">Preferencias...</a>
            </div>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
               aria-expanded="false">
                Help
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                <a class="dropdown-item" href="#">FAQ</a>
                <a class="dropdown-item" href="#">Support</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#">Contact</a>
            </div>
        </li>
    </ul>

    <!--Projects-->
    <ul class="navbar-nav ml-auto" data-bind="if: currentProject">
        <li class="nav-item dropdown">
            <a class="nav-link bg-danger rounded-pill" href="#" id="projectsDD" role="button" data-toggle="dropdown"
               aria-haspopup="true" aria-expanded="false" data-bind="css: {'dropdown-toggle': projects().length > 1}">
                <div class="d-inline-block">
                    <strong data-bind="text: currentProject().name"></strong>
                </div>
            </a>
            <!--ko if: projects().length > 1-->
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="projectsDD"
                 style="max-height: max(50px, calc(100vh - 150px)); overflow-y: auto"
                 data-bind="foreach: projects">
                <!--ko if: $root.currentProject() != $data-->
                <a class="dropdown-item" href="#" data-bind="text: name, click: $root.openProject"></a>
                <!--/ko-->
            </div>
            <!--/ko-->
        </li>
    </ul>
</nav>

<div class="container-fluid" id="content">
    <div class="row">
        <div class="col-12 mt-1">
            <div id="container">
                <div id="browser" data-bind="let: {actFolder: activeLeaf(), isNew: activeLeaf() && activeLeaf().new()}">
                    <div class="card card-dark" style="margin: 0">
                        <div class="card-header">
                            <h3 class="card-title strong">Carpetas</h3>
                            <div class="btn-group">
                                <button class="btn btn-sm" type="button" title="Carpetas con archivos con peticiones de cambio"
                                        data-bind="disable: !currentProject(), click: function() { toggle('OCMODed') }, css: {'btn-danger': OCMODed, 'btn-dark': !OCMODed()}">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button class="btn btn-sm" type="button" title="Carpetas con archivos nuevos"
                                        data-bind="disable: !currentProject(), click: function() { toggle('Uploaded') }, css: {'btn-danger': Uploaded, 'btn-dark': !Uploaded()}">
                                    <i class="fa fa-upload"></i>
                                </button>
                                <!--<button class="btn btn-sm" type="button" title="Carpetas con archivos modificados"
                                        data-bind="click: function() { toggle('Modified') }, css: {'btn-danger': Modified, 'btn-dark': !Modified()}">
                                    <i class="fa fa-exchange"></i>
                                </button>-->
                            </div>
                            <div class="separator">&nbsp;</div>
                            <button class="btn btn-info" type="button" data-bind="disable: !currentProject(), click: $root.createDir"><i
                                        class="fa fa-folder"></i><sup
                                        class="fa fa-asterisk"></sup></button>
                            <button class="btn btn-info" type="button" data-bind="disable: !isNew, click: $root.renameDir">
                                <i class="fa fa-folder"></i><sup class="fa fa-pencil"></sup></button>
                            <button class="btn btn-info" type="button" data-bind="disable: !isNew, click: $root.removeDir">
                                <i class="fa fa-folder"></i><sup class="fa fa-trash"></sup>
                            </button>
                        </div>
                        <div class="card-body" id="treePanel" data-bind="if: activeLeaf">
                            <div class="treeContainer"
                                 data-bind="template: {name: 'tree_tpl', data: {isRoot: true, leaf: tree().c, opened: function() { return true; }}}"></div>
                        </div>
                    </div>

                    <div class="card card-dark" style="margin: 0">
                        <div class="card-header">
                            <h3 class="card-title strong">Archivos</h3>
                            <ul class="navbar-nav">
                                <li class="nav-item dropdown">
                                    <a class="px-2 nav-link bg-info rounded fa fa-file-text" href="#" id="fileList" role="button"
                                       data-bind="css: {disabled: !currentProject()}"
                                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><sup class="fa fa-asterisk"></sup>&nbsp;<span
                                                class="fa fa-angle-down"></span>
                                    </a>
                                    <div class="dropdown-menu py-0" aria-labelledby="fileList"
                                         style="max-height: max(50px, calc(100vh - 150px)); overflow-y: auto"
                                         data-bind="foreach: {data: [{lang: 'PHP', ext: '.php'}, {lang: 'Javascript', ext: '.js'},{lang: 'Twig', ext: '.twig'}]}">
                                        <a class="dropdown-item bg-light text-dark" style="column-gap: 5px; border: solid 1px white"
                                           data-bind="click: $root.newFile, text: lang" href="#"></a>
                                    </div>
                                </li>
                            </ul>
                            <!--<button class="btn btn-info" type="button" title="Crear un nuevo archivo"><i class="fa fa-file-text"></i><sup
                                        class="fa fa-asterisk"></sup></button>-->
                        </div>
                        <div class="card-body" id="filesPanel">
                            <ul class="tree only_files" data-bind="foreach: {data: fileList}">
                                <li data-bind="let: {editable: $root.isEditable($data.n())}">
                                    <a href="#" data-bind="css: {
                                        isNew: $data.o() || $data.u(),
                                        isUpload: $data.u,
                                        disabled: !editable},
                                        click: $root.editFileData">
                                        <div data-bind="text: n, attr: {title: $data.u() ? 'Nuevo' : ($data.o() ? 'Con cambios' : 'Sin cambios')}"></div>
                                        <button class="btn btn-sm btn-default fa fa-edit" title="Cambiar nombre"
                                                data-bind="visible: $data.m() || $data.u(), click: $root.renameFile"></button>
                                        <button class="btn btn-sm btn-default fa fa-trash" title="Eliminar"
                                                data-bind="visible: $data.m() || $data.u(), click: $root.deleteFile"></button>
                                    </a>
                                    <button type="button" class="btn btn-info btn-sm fa fa-exchange" title="Ver archivo modificado (Diff)"
                                            data-bind="visible: editable && $data.m, click: function() { $root.editFile($data, 'diff'); }"></button>
                                    <button type="button" class="btn btn-info btn-sm fa fa-pencil" title="Editar"
                                            data-bind="click: function() { $root.editFile($data, $data.u() ? 'upload' : 'ocmod'); }, visible: editable"></button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!--<div class="small" data-bind="if: recreatingCache() > 0">Recreando caché...</div>-->
                </div>

                <div class="card card-dark editor" style="margin: 0">
                    <!--ko if: openedFiles().length > 0-->
                    <div class="card-header" data-bind="let: {cur: currentEditor() || {}}">
                        <div class="mr-2">
                            <ul class="navbar-nav">
                                <li class="nav-item dropdown">
                                    <a class="px-2 nav-link bg-info rounded fa fa-angle-down" href="#" id="fileList" role="button"
                                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    </a>
                                    <div class="dropdown-menu py-0" aria-labelledby="fileList"
                                         style="max-height: max(50px, calc(100vh - 150px)); overflow-y: auto"
                                         data-bind="foreach: openedFiles">
                                        <a class="dropdown-item d-flex align-items-center" style="column-gap: 5px; border: solid 1px white"
                                           data-bind="click: function() { $root.editFile($data); },
                                                css: {'disabled bg-info': cur == $data, 'bg-light text-dark': cur!=$data}" href="#">
                                            <div class="flex-grow-1" data-bind="text: $data.path+'/'+$data.filename"></div>
                                            <div class="flex-grow-0 pl-2 fa" data-bind="class: $root.getActionIcon($data)"></div>
                                        </a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div id="fileName" data-bind="css: {changed: cur.modified}">
                            <span data-bind="text: cur.path+'/'"></span><span data-bind="text: cur.filename"></span>
                        </div>
                        <div class="mr-1">
                            <button data-bind="click: function() { save(); }, disable: currentEditor() && !currentEditor().modified()"
                                    class="btn btn-info fa fa-save"></button>
                        </div>
                        <div>
                            <button data-bind="click: function() { closeFile(); }" class="btn btn-info fa fa-close"></button>
                        </div>
                        <div class="btn-group ml-1" style="overflow: hidden" data-bind="visible: ['orig','upload'].indexOf(cur.action) < 0">
                            <button class="btn btn-info fa fa-angle-double-left" type="button" id="btnGoFirst"></button>
                            <button class="btn btn-info fa fa-angle-left" type="button" id="btnGoPrev"></button>
                            <button class="btn btn-info fa fa-angle-right" type="button" id="btnGoNext"></button>
                            <button class="btn btn-info fa fa-angle-double-right" type="button" id="btnGoLast"></button>
                        </div>
                    </div>
                    <!--/ko-->
                    <div class="card-body bg-dark" data-bind="foreach: {data: openedFiles}">
                        <div class="editor-box" data-bind="visible: $data == $root.currentEditor(), attr: {id: $data.id}"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script type="application/javascript">
    function folders() {
        return <?php
            if (is_null($currentProject))
                echo '[]';
            elseif (is_readable(CACHE_FILE))
                readfile(CACHE_FILE);
            else
                echo json_encode([['n' => $project['name']]]);
            ?>;
    }

    function projects() {
        return <?php echo json_encode($projects); ?>;
    }

    function projectData() {
        return <?php echo json_encode($project) ?>;
    }

    function initData() {
        return {
            projectIndex: <?php echo $currentProjectIndex; ?>,
            theme: '<?php echo $cfg->theme; ?>',
            fontSize: <?php echo $cfg->fontSize; ?>,
            softWraps: <?php echo $cfg->softWraps ? 'true' : 'false'; ?>,
            softTabs: <?php echo $cfg->softTabs ? 'true' : 'false'; ?>,
            tabSize: <?php echo $cfg->tabSize; ?>,
            showLineNumbers: <?php echo $cfg->showLineNumbers ? 'true' : 'false'; ?>,
        };
    }
</script>
<script type="application/javascript" src="public/app/js/app.js"></script>