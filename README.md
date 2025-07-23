# Easy Filter WC

Un plugin simple y potente para WooCommerce que permite filtrar productos por categorías, etiquetas, precio y atributos de producto con funcionalidad AJAX.

## Descripción

Easy Filter WC es un plugin ligero y fácil de usar que permite a tus clientes encontrar productos fácilmente mediante filtros avanzados. Es perfecto para tiendas online con catálogos extensos de productos.

### Características Principales

- ✅ Filtrado por categorías de productos
- ✅ Filtrado por etiquetas de productos
- ✅ Filtrado por rango de precios (con slider o campos de entrada)
- ✅ Filtrado por atributos de productos personalizados
- ✅ Funcionalidad AJAX para una experiencia fluida sin recargar la página
- ✅ Diseño responsive que funciona en todos los dispositivos
- ✅ Panel de configuración en el administrador
- ✅ Compatible con cualquier tema de WooCommerce
- ✅ Lógica de filtrado inteligente (AND logic para selecciones múltiples)

## Instalación

1. Sube los archivos del plugin al directorio `/wp-content/plugins/easy-filter-wc/` o instálalo desde el administrador de WordPress
2. Activa el plugin en la pantalla 'Plugins' de WordPress
3. Ve a Configuración > Easy Filter WC para configurar el plugin
4. Usa los shortcodes para mostrar el filtro y los productos

## Configuración

### Panel de Administración

Ve a **Configuración > Easy Filter WC** para acceder al panel de configuración donde puedes:

- **Mostrar Categorías**: Habilitar/deshabilitar el filtro de categorías
- **Mostrar Etiquetas**: Habilitar/deshabilitar el filtro de etiquetas
- **Mostrar Filtro de Precio**: Habilitar/deshabilitar el filtro de precios
- **Mostrar Atributos**: Habilitar/deshabilitar el filtro de atributos
- **Usar Slider de Precio**: Cambiar entre slider y campos de entrada para precios
- **Habilitar Filtrado AJAX**: Activar filtrado sin recargar página
- **Seleccionar Atributos**: Elegir qué atributos específicos mostrar en el filtro

## Shortcodes

El plugin incluye dos shortcodes principales:

### 1. Shortcode del Filtro: `[easy_filter]`

Este shortcode muestra el widget de filtrado, normalmente se usa en el sidebar o en áreas de widgets.

```php
[easy_filter]
```

**Uso típico**: Colocar en un widget de texto en el sidebar de las páginas de productos.

### 2. Shortcode de Productos: `[easy_products]`

Este shortcode muestra una lista personalizada de productos que se actualiza dinámicamente con el filtro.

```php
[easy_products]
```

#### Parámetros del Shortcode `[easy_products]`:

| Parámetro | Tipo | Predeterminado | Descripción |
|-----------|------|----------------|-------------|
| `columns` | int | 4 | Número de columnas (1-5) |
| `per_page` | int | 12 | Productos por página |
| `category` | string | '' | Mostrar productos de una categoría específica |
| `tag` | string | '' | Mostrar productos de una etiqueta específica |
| `orderby` | string | menu_order | Ordenar productos (menu_order, date, price, popularity, rating) |
| `order` | string | ASC | Orden ascendente o descendente (ASC, DESC) |
| `show_pagination` | string | yes | Mostrar paginación (yes/no) |
| `show_sorting` | string | yes | Mostrar dropdown de ordenamiento (yes/no) |
| `show_result_count` | string | yes | Mostrar contador de resultados (yes/no) |

#### Ejemplos de Uso:

```php
// Mostrar productos en 4 columnas con 16 productos por página
[easy_products columns="4" per_page="16"]

// Mostrar productos de una categoría específica
[easy_products category="electronics" columns="3"]

// Mostrar productos ordenados por precio
[easy_products orderby="price" order="ASC"]

// Lista simple sin paginación ni ordenamiento
[easy_products show_pagination="no" show_sorting="no"]
```

## Implementación Recomendada

### Para Páginas de Categorías

La implementación más efectiva es usar ambos shortcodes juntos:

1. **En el sidebar**: `[easy_filter]` - Para mostrar los filtros
2. **En el área de contenido**: `[easy_products]` - Para mostrar los productos filtrados

### Configuración Típica:

**Sidebar (widget de texto)**:
```php
[easy_filter]
```

**Área de contenido principal**:
```php
[easy_products columns="3" per_page="12" show_pagination="yes"]
```

### Para Temas Divi

El plugin es compatible con el constructor Divi. Puedes usar los shortcodes en:
- Módulos de Texto
- Módulos de Código
- Widgets del sidebar

## Características Técnicas

### Filtrado Inteligente

- **Lógica AND**: Cuando se seleccionan múltiples opciones en diferentes filtros, los productos deben cumplir TODOS los criterios
- **Contadores dinámicos**: Los números junto a cada opción de filtro muestran cuántos productos coinciden
- **Filtrado contextual**: Los filtros se adaptan automáticamente al contexto (página de categoría, etiqueta, etc.)

### Rendimiento

- **AJAX optimizado**: Filtrado sin recargar la página
- **Consultas eficientes**: Uso de consultas SQL optimizadas
- **Carga condicional**: Los scripts solo se cargan en páginas relevantes

### Compatibilidad

- **WordPress**: 5.0+
- **WooCommerce**: 3.0+
- **PHP**: 7.4+
- **Temas**: Compatible con cualquier tema de WooCommerce
- **Constructores**: Funciona con Divi, Elementor, y otros constructores

## Personalización

### CSS Personalizado

Puedes personalizar la apariencia del filtro agregando CSS personalizado:

```css
/* Personalizar el contenedor del filtro */
.easy-filter-widget {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
}

/* Personalizar los títulos de los grupos de filtros */
.filter-group h4 {
    color: #333;
    font-size: 16px;
    margin-bottom: 10px;
}

/* Personalizar los checkboxes */
.filter-group label {
    display: block;
    margin-bottom: 8px;
    cursor: pointer;
}

/* Personalizar el slider de precios */
#price-slider {
    margin: 10px 0;
}
```

### Hooks para Desarrolladores

El plugin incluye varios hooks para personalización avanzada:

```php
// Modificar argumentos de consulta de productos
add_filter('easy_filter_wc_query_args', function($args) {
    // Modificar $args aquí
    return $args;
});

// Personalizar HTML del filtro
add_filter('easy_filter_wc_filter_html', function($html) {
    // Modificar $html aquí
    return $html;
});
```

## Resolución de Problemas

### El filtro no aparece
1. Verifica que WooCommerce esté activado
2. Asegúrate de usar el shortcode `[easy_filter]` correctamente
3. Revisa la configuración en Configuración > Easy Filter WC

### Los productos no se filtran
1. Verifica que el filtrado AJAX esté habilitado
2. Comprueba la consola del navegador para errores JavaScript
3. Asegúrate de que los productos tengan las categorías/atributos configurados

### Problemas de estilo
1. Verifica que no haya conflictos CSS con tu tema
2. Usa CSS personalizado para ajustar la apariencia
3. Comprueba que los archivos CSS del plugin se estén cargando

## Soporte

Para obtener soporte técnico o reportar problemas:

1. Revisa la documentación
2. Verifica los logs de error de WordPress
3. Comprueba la compatibilidad con tu tema y otros plugins

## Changelog

### 1.0.0
- Lanzamiento inicial
- Filtrado por categorías, etiquetas, precio y atributos
- Funcionalidad AJAX completa
- Panel de administración
- Shortcodes `[easy_filter]` y `[easy_products]`
- Diseño responsive
- Lógica de filtrado AND inteligente

## Licencia

Este plugin está licenciado bajo GPL v2 o posterior.

---

**Desarrollado para mejorar la experiencia de compra en tiendas WooCommerce** 🛒✨