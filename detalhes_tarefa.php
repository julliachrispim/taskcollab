<?php
/*
 * pages/detalhes_tarefa.php
 * Exibe detalhes, comentários e histórico de uma tarefa
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/banco.php';
require_once __DIR__ . '/../includes/autenticacao.php';

verificarLogin();

$pdo     = conectar();
$usuario = usuarioLogado();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Busca tarefa
$stmt = $pdo->prepare('
    SELECT t.*, 
           u1.nome AS criador_nome,
           u2.nome AS responsavel_nome
    FROM tarefas t
    JOIN usuarios u1 ON t.criado_por  = u1.id
    JOIN usuarios u2 ON t.atribuido_a = u2.id
    WHERE t.id = ?
');
$stmt->execute([$id]);
$tarefa = $stmt->fetch();

if (!$tarefa) {
    header('Location: tarefas.php');
    exit;
}

$statusOpcoes = [
    'pendente'     => 'Pendente',
    'em_andamento' => 'Em andamento',
    'concluida'    => 'Concluída',
];

$podeMudarStatus = ($tarefa['criado_por'] == $usuario['id'] || $tarefa['atribuido_a'] == $usuario['id']);

$erro    = '';
$sucesso = isset($_GET['criada']) ? 'Tarefa criada com sucesso!' : '';

// Processa atualização de status via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if ($acao === 'atualizar_status' && $podeMudarStatus) {
        $novoStatus = $_POST['status'] ?? '';
        if (array_key_exists($novoStatus, $statusOpcoes)) {
            $statusAntigo = $tarefa['status'];
            $stmt = $pdo->prepare('UPDATE tarefas SET status = ? WHERE id = ?');
            $stmt->execute([$novoStatus, $id]);

            // Registra histórico
            $desc = 'Status alterado de "' . $statusOpcoes[$statusAntigo] . '" para "' . $statusOpcoes[$novoStatus] . '".';
            $stmtH = $pdo->prepare('INSERT INTO historico (tarefa_id, usuario_id, descricao) VALUES (?, ?, ?)');
            $stmtH->execute([$id, $usuario['id'], $desc]);

            $tarefa['status'] = $novoStatus;
            $sucesso = 'Status atualizado com sucesso!';
        }

    } elseif ($acao === 'comentar') {
        $texto = trim($_POST['comentario'] ?? '');
        if (!empty($texto)) {
            $stmtC = $pdo->prepare('INSERT INTO comentarios (tarefa_id, usuario_id, texto) VALUES (?, ?, ?)');
            $stmtC->execute([$id, $usuario['id'], $texto]);
            $sucesso = 'Comentário adicionado!';
        } else {
            $erro = 'O comentário não pode ser vazio.';
        }
    }
}

// Busca comentários
$stmtCom = $pdo->prepare('
    SELECT c.*, u.nome AS autor_nome
    FROM comentarios c
    JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.tarefa_id = ?
    ORDER BY c.criado_em ASC
');
$stmtCom->execute([$id]);
$comentarios = $stmtCom->fetchAll();

// Busca histórico
$stmtHist = $pdo->prepare('
    SELECT h.*, u.nome AS autor_nome
    FROM historico h
    JOIN usuarios u ON h.usuario_id = u.id
    WHERE h.tarefa_id = ?
    ORDER BY h.criado_em DESC
');
$stmtHist->execute([$id]);
$historico = $stmtHist->fetchAll();

$tituloPagina = htmlspecialchars($tarefa['titulo']);
require_once __DIR__ . '/../includes/cabecalho.php';
?>

<div class="container">
    <?php if ($erro): ?>
        <div class="alerta alerta-erro" role="alert"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
        <div class="alerta alerta-sucesso" role="status"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <div class="layout-detalhes">
        <!-- Coluna principal -->
        <section class="detalhes-principal" aria-labelledby="titulo-tarefa">
            <header class="detalhes-cabecalho">
                <div class="detalhes-titulo-linha">
                    <h2 id="titulo-tarefa"><?= htmlspecialchars($tarefa['titulo']) ?></h2>
                    <span class="badge badge-<?= $tarefa['status'] ?>">
                        <?= $statusOpcoes[$tarefa['status']] ?? $tarefa['status'] ?>
                    </span>
                </div>
                <div class="detalhes-acoes">
                    <?php if ($podeMudarStatus): ?>
                        <a href="editar_tarefa.php?id=<?= $tarefa['id'] ?>" class="btn btn-pequeno btn-secundario">Editar</a>
                    <?php endif; ?>
                    <a href="tarefas.php" class="btn btn-pequeno btn-fantasma">← Voltar</a>
                </div>
            </header>

            <div class="detalhes-info-grade">
                <div class="info-item">
                    <span class="info-rotulo">Responsável</span>
                    <span class="info-valor">👤 <?= htmlspecialchars($tarefa['responsavel_nome']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-rotulo">Criado por</span>
                    <span class="info-valor">✍️ <?= htmlspecialchars($tarefa['criador_nome']) ?></span>
                </div>
                <?php if ($tarefa['data_limite']): ?>
                    <div class="info-item">
                        <span class="info-rotulo">Data limite</span>
                        <span class="info-valor <?= $tarefa['data_limite'] < date('Y-m-d') && $tarefa['status'] !== 'concluida' ? 'texto-perigo' : '' ?>">
                            📅 <?= date('d/m/Y', strtotime($tarefa['data_limite'])) ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-rotulo">Criado em</span>
                    <span class="info-valor">🕒 <?= date('d/m/Y H:i', strtotime($tarefa['criado_em'])) ?></span>
                </div>
            </div>

            <?php if ($tarefa['descricao']): ?>
                <div class="detalhes-descricao">
                    <h3>Descrição</h3>
                    <p><?= nl2br(htmlspecialchars($tarefa['descricao'])) ?></p>
                </div>
            <?php endif; ?>

            <!-- Atualizar status -->
            <?php if ($podeMudarStatus): ?>
                <section class="secao-status" aria-labelledby="titulo-status">
                    <h3 id="titulo-status">Atualizar Status</h3>
                    <form method="POST" action="detalhes_tarefa.php?id=<?= $id ?>" class="formulario-inline">
                        <input type="hidden" name="acao" value="atualizar_status">
                        <select name="status" class="campo-select campo-select-inline">
                            <?php foreach ($statusOpcoes as $valor => $label): ?>
                                <option value="<?= $valor ?>" <?= $tarefa['status'] === $valor ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primario">Salvar</button>
                    </form>
                </section>
            <?php endif; ?>

            <!-- Comentários -->
            <section class="secao-comentarios" aria-labelledby="titulo-comentarios">
                <h3 id="titulo-comentarios">Comentários (<?= count($comentarios) ?>)</h3>

                <?php if (empty($comentarios)): ?>
                    <p class="texto-vazio">Nenhum comentário ainda. Seja o primeiro!</p>
                <?php else: ?>
                    <ol class="lista-comentarios">
                        <?php foreach ($comentarios as $comentario): ?>
                            <li class="comentario-item">
                                <header class="comentario-cabecalho">
                                    <strong class="comentario-autor"><?= htmlspecialchars($comentario['autor_nome']) ?></strong>
                                    <time class="comentario-hora" datetime="<?= $comentario['criado_em'] ?>">
                                        <?= date('d/m/Y H:i', strtotime($comentario['criado_em'])) ?>
                                    </time>
                                </header>
                                <p class="comentario-texto"><?= nl2br(htmlspecialchars($comentario['texto'])) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <!-- Form novo comentário -->
                <form method="POST" action="detalhes_tarefa.php?id=<?= $id ?>" class="formulario-comentario" novalidate>
                    <input type="hidden" name="acao" value="comentar">
                    <div class="campo-grupo">
                        <label for="comentario" class="campo-label">Adicionar comentário</label>
                        <textarea id="comentario" name="comentario" class="campo-textarea" rows="3"
                                  placeholder="Escreva seu comentário..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-secundario">Enviar comentário</button>
                </form>
            </section>
        </section>

        <!-- Coluna lateral: histórico -->
        <aside class="detalhes-lateral" aria-labelledby="titulo-historico">
            <h3 id="titulo-historico">Histórico de Alterações</h3>
            <?php if (empty($historico)): ?>
                <p class="texto-vazio">Nenhuma alteração registrada.</p>
            <?php else: ?>
                <ol class="lista-historico">
                    <?php foreach ($historico as $evento): ?>
                        <li class="historico-item">
                            <span class="historico-desc"><?= htmlspecialchars($evento['descricao']) ?></span>
                            <span class="historico-meta">
                                por <strong><?= htmlspecialchars($evento['autor_nome']) ?></strong>
                                em <?= date('d/m/Y H:i', strtotime($evento['criado_em'])) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
