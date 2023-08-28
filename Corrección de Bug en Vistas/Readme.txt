# Solución al Bug de Selección de Equipos No Disponibles

En esta última actualización del código, se ha corregido un bug recurrente que afectaba la selección de equipos no disponibles en nuestro sistema. El problema había estado generando inconsistencias en la operación de la aplicación y afectaba la experiencia del usuario. Para solucionar este inconveniente, se implementó un cambio en la forma en que se maneja la presentación de las opciones de equipos no disponibles en un menú desplegable.

## Cambios Implementados

El cambio se encuentra en la línea 57 del código que se presenta en la vista SEC_alta_orde:

```
<?php foreach ($equipos as $equipo):?>
  <option value="<?=$equipo->id?>" <?=$this->ordenes_model->getIdsOrdenesAbiertas($equipo->serie,$sector) && $sector == 'R'?'style="color: red;" disabled ':''?>><?="$equipo->serie - $equipo->estado_descrip"?></option>
<?php endforeach;?>
```

Aquí se utilizó una expresión condicional en PHP para determinar si se debe aplicar un estilo especial o si la opción debe deshabilitarse. La implementación se explica de la siguiente manera:

- `$this->ordenes_model->getIdsOrdenesAbiertas($equipo->serie,$sector)`: Esta es una llamada a un método `getIdsOrdenesAbiertas` en el modelo de órdenes. El resultado de esta llamada determina si la opción debe tener un estilo especial o si debe estar deshabilitada.

- `$sector == 'R'`: Esta verificación comprueba si la variable `$sector` es igual a `'R'`.

- `?'style="color: red;" disabled ':' Esta es una construcción ternaria que modifica el estilo y la propiedad `disabled` de la opción en función de las condiciones anteriores. Si las condiciones se cumplen, se aplica un estilo de color rojo (`style="color: red;"`) y se deshabilita la opción (`disabled`). Si las condiciones no se cumplen, no se aplica ningún estilo ni deshabilitación.

Este cambio se implementó de manera rápida debido a la recurrencia del bug y su impacto en la aplicación. A través de esta solución, se ha logrado una experiencia de usuario más consistente y sin las molestias previamente experimentadas.

Por otra parte se agrego esta misma implementacion a la vista SEC_alta_solicitud para adelantarse a otro posible inconveniente.
