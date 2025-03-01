<?php
/**
 * Parámetros:
 * pageTitle: Título de la página. Por defecto: Error
 * message: Texto de error a mostrar
 */
Views::SetBlockContent('pagetitle', empty($pageTitle) ? 'Error' : $pageTitle);
?>
<div class="container-fluid">
    <h4><?php echo $message; ?></h4>
</div>
