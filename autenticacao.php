<?php
/*
 * includes/autenticacao.php
 * Funções auxiliares de autenticação e sessão
 */

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . 'pages/login.php');
        exit;
    }
}

function usuarioLogado() {
    return isset($_SESSION['usuario_id']) ? [
        'id'    => $_SESSION['usuario_id'],
        'nome'  => $_SESSION['usuario_nome'],
        'email' => $_SESSION['usuario_email'],
    ] : null;
}

function iniciarSessao(array $usuario) {
    $_SESSION['usuario_id']    = $usuario['id'];
    $_SESSION['usuario_nome']  = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];

    // Cookie de "lembrar" por 7 dias
    setcookie('ultimo_email', $usuario['email'], time() + (7 * 24 * 60 * 60), '/');
}

function encerrarSessao() {
    $_SESSION = [];
    session_destroy();
    setcookie('ultimo_email', '', time() - 3600, '/');
}
