<?php
Views::UseLayout(Config::$defaultLayout);

App::pageTitle('Página no encontrada');

Views::BeginBlock('content');
?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <p>El contenido que ha solicitado no existe o su usuario no tiene los permisos necesarios para mostrarlo.</p>
                <p>Utilice los vínculos que ofrece el sistema para evitar que esto suceda.</p>
            </div>
        </div>
    </div>

<?php Views::Partial('!application/partials/make_content_visible');