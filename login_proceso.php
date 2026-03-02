<?php
session_start();

$host = '172.24.10.251';
$port = '3050';
$db_path = 'C:\SisDLL20\BD\DB_SIDIST.FDB'; 
$user = 'SYSDBA';
$pass = '290990';

try {
    $dsn = "firebird:dbname=$host/$port:$db_path;charset=UTF8";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $usuario  = trim($_POST['usuario']);
        $password = trim($_POST['password']);
        $rol_esperado = $_POST['rol_id']; // 0, 1 o 2

        $sql = "SELECT USUARIO, ROL FROM USUARIOS 
                WHERE USUARIO = :usuario 
                AND CONTRASENA = :pass 
                AND ROL = :rol";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':pass'    => $password,
            ':rol'     => $rol_esperado
        ]);

        $datos = $stmt->fetch();

        if ($datos) {
            $_SESSION['usuario'] = $datos['USUARIO'];
            
            if ($datos['ROL'] == '0') {
                $_SESSION['rol'] = 'promotor';
                header("Location: promotores/inicio.php");
            } else if ($datos['ROL'] == '1') {
                $_SESSION['rol'] = 'supervisor';
                header("Location: supervisor/inicio.php");
            } else if ($datos['ROL'] == '2') {
                $_SESSION['rol'] = 'distribucion';
                header("Location: distribucion/inicio.php");
            }
            exit();
        } else {
            echo "<script>alert('Acceso denegado: Credenciales incorrectas para este nivel.'); window.history.back();</script>";
        }
    }
} catch (PDOException $e) {
    die("Error de conexión al servidor del Tec: " . $e->getMessage());
}