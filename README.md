# MediSys

Aplicación web con **PHP + JavaScript + HTML + CSS** y MySQL.

## Funciones
- Login con validación en MySQL.
- Dashboard protegido por sesión.
- Módulos dinámicos cargados desde tabla `modules`.
- Módulo de recetas con formulario completo, medicamentos dinámicos (incluye campo cantidad), guardado en MySQL e impresión en PDF horizontal a dos cuerpos.
- Lista de medicamentos de recetas vinculada al Vademécum para extraer nombre/dosis/presentación.
- Módulo de historial con búsqueda por paciente, visualización horizontal y detalle en dos cuerpos para impresión PDF.
- Módulo de Vademécum con CRUD (nombre comercial, componente químico, dosis y presentación).
- Módulo de usuarios con CRUD (usuario, nombres y apellidos, nombre a mostrar, especialidad, teléfono, nivel de acceso).

## Configuración
1. Crear DB y tablas:
   ```sql
   SOURCE db/schema.sql;
   ```
2. Ajustar conexión en `config/config.php`.
3. Abrir `public/index.php` en Apache/XAMPP.
4. Ingresar al dashboard y abrir los módulos `recetas.php`, `historial.php` y `vademecum.php`.
5. El módulo `usuarios.php` solo es visible para cuentas con nivel `superusuario`.

Usuario demo: `admin` / `admin123`.
