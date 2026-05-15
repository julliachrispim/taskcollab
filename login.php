<?php
/*
 * pages/login.php
 * Página de login do sistema
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/banco.php';
require_once __DIR__ . '/../includes/autenticacao.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: tarefas.php');
    exit;
}

$erro = '';
$emailSalvo = isset($_COOKIE['ultimo_email']) ? $_COOKIE['ultimo_email'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        $pdo = conectar();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            iniciarSessao($usuario);
            header('Location: tarefas.php');
            exit;
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — TaskCollab</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/estilo.css">
</head>
<body class="pagina-auth">

<main class="container-auth">
    <section class="card-auth" aria-labelledby="titulo-login">
        <header class="auth-header">
            <h1 id="titulo-login" class="logo-auth">✅ TaskCollab</h1>
            <p class="subtitulo-auth">Gerencie tarefas em equipe</p>
        </header>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro" role="alert"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="formulario-auth" novalidate>
            <div class="campo-grupo">
                <label for="email" class="campo-label">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="campo-input"
                    value="<?= htmlspecialchars($emailSalvo) ?>"
                    placeholder="seu@email.com"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="campo-grupo">
                <label for="senha" class="campo-label">Senha</label>
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    class="campo-input"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn btn-primario btn-bloco">Entrar</button>
        </form>

        <footer class="auth-footer">
            <p>Não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
        </footer>
    </section>
</main>

</body>
</html>
