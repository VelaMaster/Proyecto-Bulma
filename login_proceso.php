<?php
session_start();
$host = '172.24.10.251';
$port = '3050';
$db_path = 'C:\SisDLL20\BD\DB_SIDIST.FDB'; 
$user = 'SYSDBA';
$pass = '290990';

try {
    $dsn = "firebird:dbname=$host/$port:$db_path;charset=UTF8";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $usuario      = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
        $password     = isset($_POST['password']) ? trim($_POST['password']) : '';
        $rol_esperado = isset($_POST['rol_id']) ? (string)$_POST['rol_id'] : '';

        if (empty($usuario) || empty($password) || $rol_esperado === '') {
            echo "<script>alert('Por favor, complete todos los campos.'); window.history.back();</script>";
            exit();
        }
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

            if ($rol_db === '0') {
                $_SESSION['rol'] = 'promotor';
                header("Location: promotores/inicio.php");
            } else if ($rol_db === '1') {
                $_SESSION['rol'] = 'supervisor';
                header("Location: supervisor/inicio.php");
            } else if ($rol_db === '2') {
                $_SESSION['rol'] = 'distribucion';
                header("Location: distribucion/inicio.php");
            } else {
                echo "<script>alert('Rol no reconocido en el sistema.'); window.history.back();</script>";
            }
            exit();
            
        } else {
            echo "<script>alert('Acceso denegado: Usuario o contraseña incorrectos para este nivel.'); window.history.back();</script>";
            exit();
        }
    }
} catch (PDOException $e) {
    die("Error de conexión al servidor Firebird: " . $e->getMessage());
}
?>