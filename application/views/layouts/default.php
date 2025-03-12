<!DOCTYPE html>
<html>
<head>
    <title>OCMod Builder</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <base href="<?php echo APP_HOST; ?>">

    <link href="public/app/css/styles.css" rel="stylesheet"/>
    <link href="public/third_party/bootstrap/adminlte.css" rel="stylesheet"></link>
    <link href="public/third_party/toastr/toastr.min.css" rel="stylesheet"></link>
    <link href="public/third_party/font-awesome/css/font-awesome.min.css" rel="stylesheet"></link>

    <script type="application/javascript" src="public/third_party/jquery/jquery-3.7.0.min.js"></script>
    <script type="application/javascript" src="public/third_party/bootstrap/bootstrap.bundle.min.js"></script>
    <script type="application/javascript" src="public/third_party/knockout/knockout-3.5.1.min.js"></script>
    <script type="application/javascript" src="public/third_party/toastr/toastr.min.js"></script>
    <script type="application/javascript" src="public/third_party/ace/ace.js"></script>
    <script type="application/javascript" src="public/third_party/ace/ext-language_tools.js"></script>
    <script type="application/javascript" src="public/third_party/ace/mode-php.js"></script>
    <script type="application/javascript" src="public/third_party/ace/mode-twig.js"></script>
    <script type="application/javascript" src="public/third_party/ace/mode-javascript.js"></script>
    <script type="application/javascript" src="public/third_party/ace/mode-xml.js"></script>
    <script type="application/javascript" src="public/app/js/messages.js"></script>
    <script type="application/javascript" src="public/app/js/validation.js"></script>
    <?php
    Views::Partial('tree_tpl');
    ?>
</head>

<body>
<div id="content">
    <?php Views::Block('content'); ?>
</div>
</body>
</html>