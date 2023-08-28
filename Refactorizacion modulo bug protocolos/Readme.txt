# Refactorización de Método en el modelo edicion_model

En esta actualización del código en el archivo `edicion_model` se ha realizado una implementacion del método controlar_protocolo(), anteriormente no se permitía verificar la situación de los protocolos.

## Cambios Realizados

1. Se ha incorporado el método que maneja la verificación de situaciones de protocolos en el archivo `edicion_model`.

2. Se implementó una consulta en la tabla `protocolos_control` para seleccionar protocolos en los estados requeridos (10 y 20) que corresponden a protocolos normales.

3. Se realizó una verificación adicional para asegurarse de que estos protocolos no tengan registros asociados en la tabla `entrada`.

4. Se introdujeron condiciones para manejar diferentes escenarios que podrían surgir en función de los resultados de la consulta y la verificación.

5. En cada escenario identificado, se generó un retorno adecuado para el usuario administrador. Esto garantiza una solución efectiva y transparente para cada caso.

## Objetivos

- Mejorar la funcionalidad de verificación de situaciones de protocolos en el sistema.
- Garantizar que los protocolos en los estados 10 y 20, considerados como normales, se gestionen correctamente.
- Proporcionar respuestas claras y efectivas al usuario administrador en función de los distintos escenarios encontrados.

