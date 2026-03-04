<?php
session_start();
require_once 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario      = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password     = isset($_POST['password']) ? trim($_POST['password']) : '';
    $rol_esperado = isset($_POST['rol_id']) ? (string)$_POST['rol_id'] : '';

    if (empty($usuario) || empty($password) || $rol_esperado === '') {
        echo "<script>alert('Por favor, complete todos los campos.'); window.history.back();</script>";
        exit();
    }

    try {
        $sql = "SELECT USUARIO, ROL FROM USUARIOS_INVENTARIOS
                WHERE USUARIO = :usuario 
                AND CONTRASENA = :pass 
                AND ROL = :rol";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->bindValue(':pass',    $password, PDO::PARAM_STR);
        $stmt->bindValue(':rol',     $rol_esperado, PDO::PARAM_STR);

        $stmt->execute();
        $datos = $stmt->fetch();

        if ($datos) {
            $_SESSION['usuario'] = $datos['USUARIO'];
            $rol_db = trim($datos['ROL']); 

            switch ($rol_db) {
                case '0':
                    $_SESSION['rol'] = 'promotor';
                    header("Location: promotores/inicio.php");
                    break;
                case '1':
                    $_SESSION['rol'] = 'supervisor';
                    header("Location: supervisor/inicio.php");
                    break;
                case '2':
                    $_SESSION['rol'] = 'distribucion';
                    header("Location: distribucion/inicio.php");
                    break;
                default:
                    echo "<script>alert('Rol no reconocido.'); window.history.back();</script>";
                    break;
            }
            exit();
            
        } else {
            echo "<script>alert('Acceso denegado: Usuario o contraseña incorrectos.'); window.history.back();</script>";
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error en Login: " . $e->getMessage());
        die("Error interno en el servidor de base de datos.");
    }
}
?>