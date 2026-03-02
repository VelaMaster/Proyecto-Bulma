<?php
session_start();
session_destroy();
header("Location: iniciosesionPromotor.php");
exit();