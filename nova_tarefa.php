<?php
/*
 * pages/nova_tarefa.php
 * Formulário para criação de nova tarefa
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/banco.php';
require_once __DIR__ . '/../includes/autenticacao.php';

verificarLogin();

$pdo     = conectar();
$usuario = usuarioLogado();

// Busca todos os usuários para atribuição
$stmtU   = $pdo->query('SELECT id, nome FROM usuarios ORDER BY nome');
$usuarios = $stmtU->fetchAll();

$erro    = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo']      ?? '');
    $descricao   = trim($_POST['descricao']   ?? '');
    $dataLimite  = $_POST['data_limite']      ?? '';
    $atribuidoA  = $_POST['atribuido_a']      ?? '';

    if (empty($titulo) || empty($atribuidoA)) {
        $erro = 'Título e responsável são obrigatórios.';
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO tarefas (titulo, descricao, data_limite, status, criado_por, atribuido_a)
            VALUES (?, ?, ?, \'pendente\', ?, ?)
        ');
        $stmt->execute([
            $titulo,
            $descricao,
            !empty($dataLimite) ? $dataLimite : null,
            $usuario['id'],
            $atribuidoA,
        ]);

        $novaId = $pdo->lastInsertId();

        // Registra histórico
        $stmtH = $pdo->prepare('
            INSERT INTO historico (tarefa_id, usuario_id, descricao)
            VALUES (?, ?, ?)
        ');
        $stmtH->execute([$novaId, $usuario['id'], 'Tarefa criada.']);

        header('Location: detalhes_tarefa.php?id=' . $novaId . '&criada=1');
        exit;
    }
}

$tituloPagina = 'Nova Tarefa';
require_once __DIR__ . '/../includes/cabecalho.php';
?>

<div class="container container-estreito">
    <section aria-labelledby="titulo-form">
        <header class="pagina-titulo">
            <h2 id="titulo-form">Nova Tarefa</h2>
            <a href="tarefas.php" class="btn btn-fantasma">← Voltar</a>
        </header>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro" role="alert"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="nova_tarefa.php" class="formulario-cartao" novalidate>
            <div class="campo-grupo">
                <label for="titulo" class="campo-label">Título <span class="obrigatorio">*</span></label>
                <input type="text" id="titulo" name="titulo" class="campo-input"
                       value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>"
                       placeholder="Nome da tarefa" required maxlength="200">
            </div>

            <div class="campo-grupo">
                <label for="descricao" class="campo-label">Descrição</label>
                <textarea id="descricao" name="descricao" class="campo-textarea" rows="4"
                          placeholder="Detalhes, requisitos, contexto..."><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
            </div>

            <div class="campos-linha">
                <div class="campo-grupo">
                    <label for="data_limite" class="campo-label">Data limite</label>
                    <input type="date" id="data_limite" name="data_limite" class="campo-input"
                           value="<?= htmlspecialchars($_POST['data_limite'] ?? '') ?>"
                           min="<?= date('Y-m-d') ?>">
                </div>

                <div class="campo-grupo">
                    <label for="atribuido_a" class="campo-label">Responsável <span class="obrigatorio">*</span></label>
                    <select id="atribuido_a" name="atribuido_a" class="campo-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>"
                                <?= ($u['id'] == $usuario['id']) ? 'selected' : '' ?>
                                <?= (isset($_POST['atribuido_a']) && $_POST['atribuido_a'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nome']) ?>
                                <?= ($u['id'] == $usuario['id']) ? '(eu)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="btn btn-primario">Criar Tarefa</button>
                <a href="tarefas.php" class="btn btn-fantasma">Cancelar</a>
            </div>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
