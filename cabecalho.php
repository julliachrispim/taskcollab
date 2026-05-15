<?php
/*
 * includes/cabecalho.php
 * Cabeçalho comum a todas as páginas autenticadas
 */
$usuario = usuarioLogado();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($tituloPagina) ? htmlspecialchars($tituloPagina) . ' — ' : '' ?>TaskCollab</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/estilo.css">
</head>
<body>
<header class="cabecalho-principal">
    <div class="container cabecalho-interno">
        <a href="<?= BASE_URL ?>pages/tarefas.php" class="logo">✅ TaskCollab</a>
        <nav class="navegacao-principal">
            <a href="<?= BASE_URL ?>pages/tarefas.php">Tarefas</a>
            <a href="<?= BASE_URL ?>pages/nova_tarefa.php">+ Nova Tarefa</a>
            <a href="<?= BASE_URL ?>pages/usuarios.php">Equipe</a>
        </nav>
        <div class="usuario-info">
            <span>Olá, <strong><?= htmlspecialchars($usuario['nome']) ?></strong></span>
            <a href="<?= BASE_URL ?>pages/logout.php" class="btn-logout">Sair</a>
        </div>
    </div>
</header>
<main class="conteudo-principal">
