## English
OCMod builder is a tool designed to facilitate the creation of modification files for OpenCart, which is otherwise a cumbersome task.

This tool was developed from the code shared at: https://tech-en.netlify.app/articles/en529186/index.html. We would like to thank its author for his valuable contribution.

The main advantage of OCMod builder is that it allows you to write changes directly into the code of OpenCart files, whether PHP, Javascript or Twig. The changes are made in a copy of OpenCart so as not to alter the original files.

### How to use OCMod builder?

- Place the OCMod builder files in a folder on your local web server, for example www/ocmod-builder if you are using Wamp.
- Edit the **ocmod-builder.cfg.php** file and update the constant values according to your environment and the output data from the **install.xml** file.
- Open the browser and click on the **Restore OpenCart Copy** button, this will create a copy of the OpenCart files in the folder set in the ROOT_PATH constant.

## Español
OCMod builder es una herramienta pensada para facilitar la creación de archivos de modificación para OpenCart, que de otra forma es una tarea engorrosa.

Esta herramienta fue desarrollada a partir del código compartido en: https://tech-en.netlify.app/articles/en529186/index.html. Se agradece a su autor por su valioso aporte.

La principal ventaja de OCMod builder es la de permitir escribir los cambios directamente en el código de los archivos de OpenCart, ya sea PHP, Javascript o Twig. Los cambios se realizan en una copia de OpenCart para no alterar los archivos originales.

### ¿Cómo utilizar OCMod builder?

- Coloque los archivos de OCMod builder en una carpeta de su servidor web local, por ejemplo, www/ocmod-builder si utiliza Wamp.
- Edite el archivo **ocmod-builder.cfg.php** y actualice los valores de las constantes de acuerdo a su entorno y los datos de salida del archivo **install.xml**.
- Abra el navegador y haga clic en el botón **Restaurar copia de OpenCart**, esto creará una copia de los archivos de OpenCart en la carpeta configurada en la constante ROOT_PATH.

![Example 1](images/example1.png)