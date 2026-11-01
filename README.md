# Protección de cargas

Esta aplicación conserva el archivo anterior cuando una carga es rechazada en Nextcloud 34. Prepara el contenido en un archivo temporal y mantiene las comprobaciones de permisos del servidor. Una ubicación temporal sin permisos de creación impide la carga.

APS Conecta instala la aplicación desde una publicación firmada. La aplicación funciona de forma automática y no agrega opciones al personal.

Los fragmentos temporales utilizan la preparación de Nextcloud. La operación MOVE que ensambla el archivo conserva la protección de sustitución.

Las carpetas de equipo permiten cargar desde el primer acceso WebDAV. La preparación de sus carpetas superiores utiliza la interfaz nativa de archivos y conserva las restricciones de lectura y escritura. Nextcloud y DAV mantienen sus archivos originales.

El código utiliza una excepción de compatibilidad limitada al envoltorio de almacenamiento de Nextcloud 34. La decisión y su condición de retiro se registran en [ADR-0022](https://github.com/APS-Conecta/gestion/blob/main/docs/adr/0022-preserve-upload-staging-with-a-scoped-storage-guard.md). El código de Nextcloud y la aplicación antivirus conservan sus archivos originales.

La validación funcional corresponde a las pruebas de instalación de `gestion`: cargas nuevas, sustituciones aceptadas, sustituciones rechazadas, cargas por fragmentos y permisos. La publicación verifica la sintaxis PHP y empaqueta las clases mediante el flujo compartido de la organización.
