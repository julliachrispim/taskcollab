<?php
/*
 * pages/logout.php
 * Encerra sessão do usuário
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/autenticacao.php';

encerrarSessao();
header('Location: ' . BASE_URL . 'pages/login.php');
exit;
