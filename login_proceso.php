<?php
session_start();
require_once 'conexion.php'; 
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario      = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password     = isset($_POST['password']) ? trim($_POST['password']) : '';
    $rol_esperado = isset($_POST['rol_id']) ? (string)$_POST['rol_id'] : '';

    $paginas_login = [
        '0' => 'iniciosesionPromotor.php',
        '1' => 'iniciosesionSupervisor.php',
        '2' => 'iniciosesionDistribucion.php'
    ];
    
    $pagina_redirect = isset($paginas_login[$rol_esperado]) ? $paginas_login[$rol_esperado] : 'iniciosesionPromotor.php';

    if (empty($usuario) || empty($password) || $rol_esperado === '') {
        header("Location: " . $pagina_redirect . "?error=1");
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

            // 🔥 IMPORTANTE: regenerar sesión
            session_regenerate_id(true);

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
                    header("Location: " . $pagina_redirect . "?error=1");
                    break;
            }
            exit();
            
        } else {
            header("Location: " . $pagina_redirect . "?error=1");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Error en Login: " . $e->getMessage());
        die("Error interno en el servidor de base de datos.");
    }
}
?>