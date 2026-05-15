<?php
/*
 * pages/cadastro.php
 * Página de cadastro de novos usuários
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/banco.php';
require_once __DIR__ . '/../includes/autenticacao.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: tarefas.php');
    exit;
}

$erro    = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha']      ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar)) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar) {
        $erro = 'As senhas não coincidem.';
    } else {
        $pdo = conectar();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)');
            $stmt->execute([$nome, $email, $hash]);
            $sucesso = 'Cadastro realizado com sucesso! Faça login para continuar.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro — TaskCollab</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/estilo.css">
</head>
<body class="pagina-auth">

<main class="container-auth">
    <section class="card-auth" aria-labelledby="titulo-cadastro">
        <header class="auth-header">
            <h1 id="titulo-cadastro" class="logo-auth">✅ TaskCollab</h1>
            <p class="subtitulo-auth">Criar nova conta</p>
        </header>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro" role="alert"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso" role="status"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST" action="cadastro.php" class="formulario-auth" novalidate>
            <div class="campo-grupo">
                <label for="nome" class="campo-label">Nome completo</label>
                <input type="text" id="nome" name="nome" class="campo-input"
                       value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                       placeholder="Seu nome" required autocomplete="name">
            </div>

            <div class="campo-grupo">
                <label for="email" class="campo-label">E-mail</label>
                <input type="email" id="email" name="email" class="campo-input"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="seu@email.com" required autocomplete="email">
            </div>

            <div class="campo-grupo">
                <label for="senha" class="campo-label">Senha</label>
                <input type="password" id="senha" name="senha" class="campo-input"
                       placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
            </div>

            <div class="campo-grupo">
                <label for="confirmar_senha" class="campo-label">Confirmar senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" class="campo-input"
                       placeholder="Repita a senha" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primario btn-bloco">Criar conta</button>
        </form>

        <footer class="auth-footer">
            <p>Já tem conta? <a href="login.php">Entrar</a></p>
        </footer>
    </section>
</main>

</body>
</html>
