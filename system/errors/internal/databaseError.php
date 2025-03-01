<hmtl>
    <head>
        <meta charset="utf-8">
        <title>Error de conexión a la base de datos</title>
        <style>
            body {
                font-family: "Helvetica Neue", Calibri, Tahoma, Helvetica, Arial, sans-serif
            }
        </style>
    </head>

    <body>
    <h1>Error de conexión a la base de datos</h1>
    No ha sido posible establecer conexión con la base de datos del sistema. Lamentamos esta situación y nos disculpamos
    por las molestias ocasionadas.
    <br/>
    <php
            echo DB::error_message();
            ?>
    </body>
</hmtl>