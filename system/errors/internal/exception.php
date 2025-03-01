<hmtl>
    <head>
        <meta charset="utf-8">
        <title>Clase no encontrada</title>
        <style>
            body {
                font-family: "Helvetica Neue", Calibri, Tahoma, Helvetica, Arial, sans-serif
            }
        </style>
    </head>

    <body>
    <h1>Ha ocurrido una excepción</h1>

    <h3 style="margin-bottom: 15px"><?php echo $exceptionMessage; ?>.</h3>

    <strong>Archivo:</strong> <?php echo substr(str_replace('\\', '/', $filename), strlen(APP_ROOT)) . ($line ? ' en la línea ' . $line : ''); ?>.
    <br/><br/>
    <?php if ($trace): ?>
        <table cellspacing="0" cellpadding="5">
            <tr style="background-color: silver">
                <th style="text-align: left">Función</th>
                <th style="text-align: left">Argumentos</th>
                <th style="text-align: left">Archivo</th>
                <th style="text-align: left">Línea</th>
            </tr>
            <?php
            for ($i = 0; $i < count($trace) - 1; $i++): ?>
                <tr>
                    <td><?php if (isset($trace[$i]['class'])) echo $trace[$i]['class'] . $trace[$i]['type'] . $trace[$i]['function']; ?></td>
                    <td>
                        <?php
                        if ($trace[$i]['args'])
                            echo json_encode($trace[$i]['args']);
                        ?>
                    </td>
                    <td>
                        <?php
                        if (isset($trace[$i]['file']))
                            echo substr(str_replace('\\', '/', $trace[$i]['file']), strlen(APP_ROOT));
                        ?>
                    </td>
                    <td>
                        <?php
                        if (isset($trace[$i]['line']))
                            echo $trace[$i]['line'];
                        ?>
                    </td>
                </tr>
            <?php
            endfor;
            ?>
        </table>
    <?php endif; ?>
    </body>
</hmtl>