<?php
/*
 * pages/editar_tarefa.php
 * Edição de uma tarefa existente
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/banco.php';
require_once __DIR__ . '/../includes/autenticacao.php';

verificarLogin();

$pdo     = conectar();
$usuario = usuarioLogado();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare('SELECT * FROM tarefas WHERE id = ?');
$stmt->execute([$id]);
$tarefa = $stmt->fetch();

if (!$tarefa) {
    header('Location: tarefas.php');
    exit;
}

// Somente criador ou responsável pode editar
if ($tarefa['criado_por'] != $usuario['id'] && $tarefa['atribuido_a'] != $usuario['id']) {
    header('Location: detalhes_tarefa.php?id=' . $id);
    exit;
}

$stmtU = $pdo->query('SELECT id, nome FROM usuarios ORDER BY nome');
$usuarios = $stmtU->fetchAll();

$statusOpcoes = [
    'pendente'     => 'Pendente',
    'em_andamento' => 'Em andamento',
    'concluida'    => 'Concluída',
];

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo     = trim($_POST['titulo']     ?? '');
    $descricao  = trim($_POST['descricao']  ?? '');
    $dataLimite = $_POST['data_limite']     ?? '';
    $atribuidoA = $_POST['atribuido_a']     ?? '';
    $status     = $_POST['status']          ?? '';

    if (empty($titulo) || empty($atribuidoA) || !array_key_exists($status, $statusOpcoes)) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        $alteracoes = [];

        if ($tarefa['titulo'] !== $titulo) {
            $alteracoes[] = 'Título alterado de "' . $tarefa['titulo'] . '" para "' . $titulo . '".';
        }
        if ($tarefa['descricao'] !== $descricao) {
            $alteracoes[] = 'Descrição atualizada.';
        }
        if ($tarefa['status'] !== $status) {
            $alteracoes[] = 'Status alterado de "' . $statusOpcoes[$tarefa['status']] . '" para "' . $statusOpcoes[$status] . '".';
        }
        if ($tarefa['atribuido_a'] != $atribuidoA) {
            // Busca nome do novo responsável
            $stmtN = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?');
            $stmtN->execute([$atribuidoA]);
            $novoResp = $stmtN->fetchColumn();
            $alteracoes[] = 'Responsável alterado para "' . $novoResp . '".';
        }
        $dataAtual = $tarefa['data_limite'] ?? '';
        $novaData  = !empty($dataLimite) ? $dataLimite : null;
        if ($dataAtual !== $novaData) {
            $alteracoes[] = 'Data limite alterada para ' . ($novaData ? date('d/m/Y', strtotime($novaData)) : 'sem data') . '.';
        }

        $stmtUp = $pdo->prepare('
            UPDATE tarefas 
            SET titulo = ?, descricao = ?, data_limite = ?, status = ?, atribuido_a = ?
            WHERE id = ?
        ');
        $stmtUp->execute([$titulo, $descricao, $novaData, $status, $atribuidoA, $id]);

        // Grava histórico de cada alteração
        foreach ($alteracoes as $descAlteracao) {
            $stmtH = $pdo->prepare('INSERT INTO historico (tarefa_id, usuario_id, descricao) VALUES (?, ?, ?)');
            $stmtH->execute([$id, $usuario['id'], $descAlteracao]);
        }

        if (empty($alteracoes)) {
            $stmtH = $pdo->prepare('INSERT INTO historico (tarefa_id, usuario_id, descricao) VALUES (?, ?, ?)');
            $stmtH->execute([$id, $usuario['id'], 'Tarefa visualizada/salva sem alterações.']);
        }

        header('Location: detalhes_tarefa.php?id=' . $id);
        exit;
    }
}

$tituloPagina = 'Editar Tarefa';
require_once __DIR__ . '/../includes/cabecalho.php';
?>

<div class="container container-estreito">
    <section aria-labelledby="titulo-editar">
        <header class="pagina-titulo">
            <h2 id="titulo-editar">Editar Tarefa</h2>
            <a href="detalhes_tarefa.php?id=<?= $id ?>" class="btn btn-fantasma">← Voltar</a>
        </header>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro" role="alert"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="editar_tarefa.php?id=<?= $id ?>" class="formulario-cartao" novalidate>
            <div class="campo-grupo">
                <label for="titulo" class="campo-label">Título <span class="obrigatorio">*</span></label>
                <input type="text" id="titulo" name="titulo" class="campo-input"
                       value="<?= htmlspecialchars($_POST['titulo'] ?? $tarefa['titulo']) ?>"
                       required maxlength="200">
            </div>

            <div class="campo-grupo">
                <label for="descricao" class="campo-label">Descrição</label>
                <textarea id="descricao" name="descricao" class="campo-textarea" rows="4"><?= htmlspecialchars($_POST['descricao'] ?? $tarefa['descricao'] ?? '') ?></textarea>
            </div>

            <div class="campos-linha">
                <div class="campo-grupo">
                    <label for="status" class="campo-label">Status <span class="obrigatorio">*</span></label>
                    <select id="status" name="status" class="campo-select" required>
                        <?php foreach ($statusOpcoes as $valor => $label): ?>
                            <option value="<?= $valor ?>"
                                <?= ($tarefa['status'] === $valor) ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-grupo">
                    <label for="data_limite" class="campo-label">Data limite</label>
                    <input type="date" id="data_limite" name="data_limite" class="campo-input"
                           value="<?= htmlspecialchars($_POST['data_limite'] ?? $tarefa['data_limite'] ?? '') ?>">
                </div>
            </div>

            <div class="campo-grupo">
                <label for="atribuido_a" class="campo-label">Responsável <span class="obrigatorio">*</span></label>
                <select id="atribuido_a" name="atribuido_a" class="campo-select" required>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>"
                            <?= ($tarefa['atribuido_a'] == $u['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-acoes">
                <button type="submit" class="btn btn-primario">Salvar alterações</button>
                <a href="detalhes_tarefa.php?id=<?= $id ?>" class="btn btn-fantasma">Cancelar</a>
            </div>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
