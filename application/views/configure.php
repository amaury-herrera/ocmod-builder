<?php
Views::UseLayout('default');
Views::BeginBlock('content');
?>
<div class="container">
    <div class="row">
        <div class="col-xs-12">
            <h3>Configuración inicial de OCMod Builder</h3>
        </div>
        <div class="col-xs-12 col-md-9">
            <br>
            <form method="post" action="configure">
                <div class="form-group">
                    <label for="name">Nombre del proyecto</label>
                    <input type="text" class="form-control" name="projectName" id="name" placeholder="Nombre del proyecto" autocomplete="false"
                           value="<?php echo Post::projectName(); ?>">
                </div>
                <div class="form-group">
                    <label for="zipFilename">Nombre del archivo .ocmod.zip</label>
                    <input type="text" class="form-control" name="zipFilename" id="zipFilename" placeholder="Nombre del archivo .ocmod.zip" autocomplete="false"
                           value="<?php echo Post::zipFilename(); ?>">
                </div>
                <div class="form-group">
                    <label for="root">Carpeta raíz de OpenCart</label>
                    <input type="text" class="form-control" name="root_path" id="root" placeholder="Carpeta raíz de OpenCart"
                           value="<?php echo Post::root_path(); ?>">
                </div>
                <div class="form-group">
                    <label for="url">URL de OpenCart</label>
                    <input type="text" class="form-control" name="url" id="url" placeholder="URL de OpenCart"
                           value="<?php echo Post::url() ? Post::url() : 'http://localhost/opencart'; ?>">
                </div>
                <div class="card card-dark">
                    <div class="card-header pl-2">Datos de archivo OCMod</div>
                    <div class="card-body pt-2 pb-0" style="white-space: nowrap; overflow: hidden">
                <pre class="d-inline p-0">
&lt;?xml version="1.0" encoding="utf-8"?&gt;
&lt;modification&gt;
  &lt;name&gt;</pre>
                        <div class="form-group d-inline-block mb-1">
                            <input type="text" class="form-control form-control-sm" name="name" placeholder="Nombre"
                                   value="<?php echo Post::name(); ?>">
                        </div>
                        <pre class="d-inline p-0" style="line-height: 1em">&lt;/name&gt;
  &lt;code&gt;</pre>
                        <div class="form-group d-inline-block mb-1">
                            <input type="text" class="form-control form-control-sm" name="code" placeholder="Código"
                                   value="<?php echo Post::code(); ?>">
                        </div>
                        <pre class="d-inline p-0">&lt;/code&gt;
  &lt;version&gt;</pre>
                        <div class="form-group d-inline-block mb-1">
                            <input type="text" class="form-control form-control-sm" name="version" placeholder="Versión"
                                   value="<?php echo Post::version() ? Post::version() : '1.0'; ?>">
                        </div>
                        <pre class="d-inline p-0">&lt;/version&gt;
  &lt;author&gt;</pre>
                        <div class="form-group d-inline-block mb-1">
                            <input type="text" class="form-control form-control-sm" name="author" placeholder="Autor"
                                   value="<?php echo Post::author(); ?>">
                        </div>
                        <pre class="d-inline p-0">&lt;/author&gt;
  &lt;link&gt;</pre>
                        <div class="form-group d-inline-block mb-1">
                            <input type="text" class="form-control form-control-sm" name="link" placeholder="Link"
                                   value="<?php echo Post::link(); ?>">
                        </div>
                        <pre class="d-inline p-0">&lt;/author&gt;
&lt;/modification>
                </pre>
                    </div>
                </div>
                <div class="text-right">
                    <br>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>

            <?php if (!empty($errors)): ?>
                <br>
                <div class="alert alert-warning alert-dismissible pb-0" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo $errors; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
