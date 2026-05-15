<?php
/*
 * pages/tarefas.php
 * Lista todas as tarefas com filtros
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/banco.php';
require_once __DIR__ . '/../includes/autenticacao.php';

verificarLogin();

$pdo     = conectar();
$usuario = usuarioLogado();

// Filtros via GET
$filtroUsuario = $_GET['usuario']    ?? '';
$filtroStatus  = $_GET['status']     ?? '';
$filtroData    = $_GET['data_limite'] ?? '';

// Monta query com filtros dinâmicos
$sql    = '
    SELECT t.*, 
           u1.nome AS criador_nome, 
           u2.nome AS responsavel_nome
    FROM tarefas t
    JOIN usuarios u1 ON t.criado_por   = u1.id
    JOIN usuarios u2 ON t.atribuido_a  = u2.id
    WHERE 1=1
';
$params = [];

if (!empty($filtroUsuario)) {
    $sql .= ' AND t.atribuido_a = ?';
    $params[] = $filtroUsuario;
}

if (!empty($filtroStatus)) {
    $sql .= ' AND t.status = ?';
    $params[] = $filtroStatus;
}

if (!empty($filtroData)) {
    $sql .= ' AND t.data_limite = ?';
    $params[] = $filtroData;
}

$sql .= ' ORDER BY t.data_limite ASC, t.criado_em DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tarefas = $stmt->fetchAll();

// Lista de usuários para o filtro
$stmtUsuarios = $pdo->query('SELECT id, nome FROM usuarios ORDER BY nome');
$usuarios = $stmtUsuarios->fetchAll();

$statusOpcoes = [
    'pendente'     => 'Pendente',
    'em_andamento' => 'Em andamento',
    'concluida'    => 'Concluída',
];

$tituloPagina = 'Tarefas';
require_once __DIR__ . '/../includes/cabecalho.php';
?>

<div class="container">
    <section class="pagina-titulo">
        <h2>Todas as Tarefas</h2>
        <a href="nova_tarefa.php" class="btn btn-primario">+ Nova Tarefa</a>
    </section>

    <!-- Formulário de filtros via GET -->
    <section class="filtros-secao" aria-label="Filtros">
        <form method="GET" action="tarefas.php" class="formulario-filtros">
            <div class="filtros-linha">
                <div class="campo-grupo">
                    <label for="filtro-usuario" class="campo-label">Responsável</label>
                    <select id="filtro-usuario" name="usuario" class="campo-select">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>"
                                <?= $filtroUsuario == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-grupo">
                    <label for="filtro-status" class="campo-label">Status</label>
                    <select id="filtro-status" name="status" class="campo-select">
                        <option value="">Todos</option>
                        <?php foreach ($statusOpcoes as $valor => $label): ?>
                            <option value="<?= $valor ?>"
                                <?= $filtroStatus === $valor ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-grupo">
                    <label for="filtro-data" class="campo-label">Data limite</label>
                    <input type="date" id="filtro-data" name="data_limite" class="campo-input"
                           value="<?= htmlspecialchars($filtroData) ?>">
                </div>

                <div class="filtros-acoes">
                    <button type="submit" class="btn btn-secundario">Filtrar</button>
                    <a href="tarefas.php" class="btn btn-fantasma">Limpar</a>
                </div>
            </div>
        </form>
    </section>

    <!-- Lista de tarefas -->
    <section class="tarefas-lista" aria-label="Lista de tarefas">
        <?php if (empty($tarefas)): ?>
            <div class="estado-vazio">
                <p>Nenhuma tarefa encontrada.</p>
                <a href="nova_tarefa.php" class="btn btn-primario">Criar primeira tarefa</a>
            </div>
        <?php else: ?>
            <?php foreach ($tarefas as $tarefa): ?>
                <article class="cartao-tarefa cartao-status-<?= htmlspecialchars($tarefa['status']) ?>">
                    <header class="cartao-cabecalho">
                        <h3 class="cartao-titulo">
                            <a href="detalhes_tarefa.php?id=<?= $tarefa['id'] ?>">
                                <?= htmlspecialchars($tarefa['titulo']) ?>
                            </a>
                        </h3>
                        <span class="badge badge-<?= $tarefa['status'] ?>">
                            <?= $statusOpcoes[$tarefa['status']] ?? $tarefa['status'] ?>
                        </span>
                    </header>

                    <p class="cartao-descricao">
                        <?= htmlspecialchars(mb_strimwidth($tarefa['descricao'] ?? '', 0, 120, '...')) ?>
                    </p>

                    <footer class="cartao-rodape">
                        <div class="cartao-meta">
                            <span class="meta-item">
                                👤 <strong><?= htmlspecialchars($tarefa['responsavel_nome']) ?></strong>
                            </span>
                            <?php if ($tarefa['data_limite']): ?>
                                <span class="meta-item <?= $tarefa['data_limite'] < date('Y-m-d') && $tarefa['status'] !== 'concluida' ? 'meta-atrasada' : '' ?>">
                                    📅 <?= date('d/m/Y', strtotime($tarefa['data_limite'])) ?>
                                </span>
                            <?php endif; ?>
                            <span class="meta-item">
                                ✍️ <?= htmlspecialchars($tarefa['criador_nome']) ?>
                            </span>
                        </div>
                        <div class="cartao-acoes">
                            <a href="detalhes_tarefa.php?id=<?= $tarefa['id'] ?>" class="btn btn-pequeno btn-secundario">
                                Ver detalhes
                            </a>
                            <?php if ($tarefa['criado_por'] == $usuario['id'] || $tarefa['atribuido_a'] == $usuario['id']): ?>
                                <a href="editar_tarefa.php?id=<?= $tarefa['id'] ?>" class="btn btn-pequeno btn-fantasma">
                                    Editar
                                </a>
                            <?php endif; ?>
                        </div>
                    </footer>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
