Digifact WordPress Plugin
--------------------------

# WooFelDigifact
Plugin de facturación electrónica para WordPress integrado con Digifact, diseñado para simplificar la generación y gestión de facturas en sitios web de comercio electrónico.

## Características
- Configuración de credenciales Digifact
- Generación de facturas electrónicas
- Gestión de facturas
- Anulación de facturas

## Requisitos
- WordPress 5.5+
- WooCommerce 4.0+
- PHP 7.4+

## Extensiones PHP:
- SimpleXML
- libxml

## Instalación
Sube el plugin a tu sitio WordPress
Activa el plugin
Configura las credenciales en Digifact > Configuración

## Configuración
1. Configura credenciales de Digifact en Digifact > Configuración
2. Activar las acciones en el listado de órdenes

## Uso
1. Crea una nueva orden
2. Dirigirse al listado de órdenes en Digifact > Órdenes WooCommerce
3. Genera factura en el botón "Generar Factura" en las acciones de la orden
    - Se abrirá una ventana modal con el formulario de factura electrónica para ingresar los datos:
        - NIT del cliente (si está disponible en la información de la orden se usará este)
        - Email del cliente (si está disponible en la información de la orden se usará este)
4. Dirigirse al menú Digifact > Facturas Emitidas
5. Filtrar por NIT del cliente
6. Filtrar por número de orden
7. Click en el botón "Ver factura" para ver la factura generada
8. Click en el botón "Anular factura" para anular la factura (En caso de anulación)
    - Se abrirá una ventana modal con el formulario de anulación de factura electrónica para ingresar los datos:
        - Motivo de anulación (requerido)

## Licencia
GPL v2 o posterior

## Changelog
1.1.0
* Se corrige el error en la generación de facturas
* Se agrega dashboard de estadísticas
* Se agrega menú de administración de Digifact con los diferentes menús disponibles
    - Dashboard
    - Órdenes WooCommerce
    - Facturas Emitidas
    - Configuración

1.0.0
* Versión inicial

## Desarrolladores
Rafael Granados
xr.grandoso@gmail.com
https://github.com/xrgranados
