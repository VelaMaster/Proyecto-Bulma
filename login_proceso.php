<?php
session_start();

// Datos de conexión validados
$host = '172.24.10.251';
$port = '3050';
$db_path = 'C:\SisDLL20\BD\DB_SIDIST.FDB'; 
$user = 'SYSDBA';
$pass = '290990';

try {
    $dsn = "firebird:dbname=$host/$port:$db_path;charset=UTF8";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Creamos la conexión
    $pdo = new PDO($dsn, $user, $pass, $options);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $usuario  = trim($_POST['usuario']);
        $password = trim($_POST['password']);
        
        // Consulta preparada para evitar inyecciones SQL
        // Nota: En Firebird, los nombres de tablas y campos suelen ir en MAYÚSCULAS
        $sql = "SELECT USUARIO, ROL FROM USUARIOS_INVENTARIOS
                WHERE USUARIO = :usuario 
                AND CONTRASENA = :pass 
                AND ROL = '0'";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':pass', $password);
        $stmt->execute();

        $datos = $stmt->fetch();

        if ($datos) {
            // Guardamos sesión
            $_SESSION['usuario'] = $datos['USUARIO'];
            $_SESSION['rol']     = 'promotor';

            // Redirigir a tu carpeta de residencia
            header("Location: promotores/inicio.php");
            exit();
        } else {
            echo "<script>
                    alert('Usuario o contraseña incorrectos para Promotor'); 
                    window.location.href='iniciosesionPromotor.php';
                  </script>";
        }
    }

} catch (PDOException $e) {
    // Si falla aquí, veremos el error exacto en el navegador
    die("Error de conexión: " . $e->getMessage());
}
?>