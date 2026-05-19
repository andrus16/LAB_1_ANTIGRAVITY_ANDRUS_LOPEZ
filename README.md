# 🎓 Laboratorio de Inteligencia Artificial - Sistema de Gestión Académica

Un sistema web de aprendizaje y gestión estudiantil interactivo con temática cyberpunk y de Inteligencia Artificial. La plataforma permite a los estudiantes registrarse, cursar módulos educativos de IA, realizar evaluaciones finales de intento único y descargar certificados de aprobación digitales una vez sean aprobados por el administrador.
---

## 🔑 Acceso Directo y Credenciales de Prueba (Docente)

Para facilitar la revisión del evaluador/profesor, la plataforma se encuentra desplegada y accesible en internet:

* **Sitio Web Público:** [http://69.55.60.184/](http://69.55.60.184/)
* **URL de Acceso Administrador:** [http://69.55.60.184/login.php](http://69.55.60.184/login.php)
* **Usuario de Administrador:** `admin`
* **Contraseña de Administrador:** `admin123`

---

## 🚀 Características Principales

### 🧑‍🎓 Panel del Estudiante
* **Registro de Aspirantes:** Formulario de registro con validación de datos.
* **Control de Acceso:** Login seguro con sesiones persistentes y encriptación de contraseñas.
* **Perfil de Usuario:** Los estudiantes pueden actualizar sus datos (biografía, teléfono alternativo) y cargar una foto de perfil.
* **Cursos Interactivos:** Acceso a cuatro módulos de IA especializados:
  1. Regresión Lineal
  2. Algoritmos Genéticos
  3. Machine Learning
  4. Procesamiento de Lenguaje Natural (NLP)
* **Evaluaciones Finales Seguras:** Sistema de exámenes interactivos bloqueados a un **intento único**. Si ya fue evaluado, muestra la nota final e impide reenvíos.
* **Certificaciones:** Botón dinámico para ver y guardar/imprimir el diploma de aprobación en formato PDF si el administrador lo autoriza.

### 👨‍💼 Panel del Administrador
* **Control de Solicitudes:** Aprobar o rechazar registros de alumnos pendientes.
* **Envío Automatizado de Credenciales:** Integración con **PHPMailer (SMTP)** para enviar contraseñas temporales por correo de forma segura a los alumnos aprobados.
* **Módulo de Reportes de Rendimiento:** 
  * Estadísticas generales de admisiones en tiempo real.
  * Tasas de aprobación, promedio de calificaciones y números de evaluados para cada uno de los 4 cursos representados con barras de progreso animadas.
  * Tabla con el desglose académico de calificaciones individuales de los alumnos.
* **Emisión de Certificados:** Botón para generar el certificado digital individual a alumnos con calificaciones aprobatorias ($\ge 60$).

---

## 🛠️ Stack Tecnológico

* **Frontend:** HTML5, CSS3 (Alineado con diseño cyberpunk de luces de neón y efectos dinámicos), JavaScript vanilla.
* **Backend:** PHP (Vanilla) con control de sesiones y variables.
* **Base de Datos:** MySQL mediante conexión **PDO** con sentencias preparadas para prevenir inyecciones SQL.
* **Envío de Correos:** PHPMailer.

---

## ⚙️ Requisitos de Instalación (Local)

1. Instalar un entorno PHP y MySQL local como **XAMPP**, **WampServer** o **Laragon**.
2. Clonar este repositorio en tu directorio de servidor local (ej. `htdocs` o `www`):
   ```bash
   git clone https://github.com/tu-usuario/nombre-del-repositorio.git
   ```
3. Importar el archivo `laboratorio_ia.sql` en tu gestor de base de datos MySQL (phpMyAdmin o similar) para crear la base de datos `laboratorio_ia` y sus tablas.
4. Las credenciales de base de datos configuradas por defecto son:
   * **Host:** `localhost`
   * **DB Name:** `laboratorio_ia`
   * **Usuario:** `root`
   * **Contraseña:** `123456789`
5. Iniciar los servicios de Apache y MySQL.
6. Abrir la URL en el navegador: `http://localhost/nombre-del-proyecto/`.

---

## ☁️ Guía de Despliegue en la Nube (DigitalOcean Droplet / LAMP)

Si deseas subir este proyecto a un servidor virtual (Droplet Ubuntu con LAMP):

1. **Instalar dependencias necesarias:**
   ```bash
   sudo apt update
   sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring php-xml -y
   ```
2. **Subir los archivos:** Mueve todo el contenido de este repositorio al directorio del servidor `/var/www/html/` (puedes usar FileZilla vía SFTP).
3. **Configurar la Base de Datos:**
   * Entra a MySQL en tu terminal: `sudo mysql`
   * Crea la base de datos: `CREATE DATABASE laboratorio_ia;`
   * Cambia la contraseña del usuario root de MySQL para coincidir con la del código local:
     ```sql
     ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '123456789';
     FLUSH PRIVILEGES;
     EXIT;
     ```
   * Importa la base de datos desde el archivo SQL subido:
     ```bash
     mysql -u root -p123456789 -D laboratorio_ia < /var/www/html/laboratorio_ia.sql
     ```
4. **Habilitar Certificado SSL (HTTPS) con Certbot:**
   * Apunta tu dominio o subdominio DNS (ej. DuckDNS) a la IP de tu Droplet.
   * Ejecuta:
     ```bash
     sudo apt install certbot python3-certbot-apache -y
     sudo certbot --apache
     ```
   * Sigue las instrucciones en pantalla para activar la redirección segura a HTTPS de forma gratuita.

---

## 🛡️ Seguridad Implementada
* **Hashing de Contraseñas:** Se utiliza `password_hash` con algoritmo **bcrypt** para todas las contraseñas.
* **Prevenir Inyección SQL:** Uso estricto de sentencias preparadas de PDO.
* **Protección XSS:** Sanitización de salidas HTML dinámicas mediante `htmlspecialchars()`.
* **Protección contra Reenvíos de Examen:** Bloqueo condicional en backend si la nota del estudiante es diferente de `NULL`.
