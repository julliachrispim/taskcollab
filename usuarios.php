<?php
/*
 * pages/usuarios.php
 * Lista os membros da equipe cadastrados
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/banco.php';
require_once __DIR__ . '/../includes/autenticacao.php';

verificarLogin();

$pdo     = conectar();
$usuario = usuarioLogado();

$stmt = $pdo->query('
    SELECT u.id, u.nome, u.email, u.criado_em,
           COUNT(t.id) AS total_tarefas,
           SUM(CASE WHEN t.status = \'concluida\'    THEN 1 ELSE 0 END) AS concluidas,
           SUM(CASE WHEN t.status = \'em_andamento\' THEN 1 ELSE 0 END) AS em_andamento,
           SUM(CASE WHEN t.status = \'pendente\'     THEN 1 ELSE 0 END) AS pendentes
    FROM usuarios u
    LEFT JOIN tarefas t ON t.atribuido_a = u.id
    GROUP BY u.id, u.nome, u.email, u.criado_em
    ORDER BY u.nome
');
$membros = $stmt->fetchAll();

$tituloPagina = 'Equipe';
require_once __DIR__ . '/../includes/cabecalho.php';
?>

<div class="container">
    <section aria-labelledby="titulo-equipe">
        <header class="pagina-titulo">
            <h2 id="titulo-equipe">Equipe</h2>
        </header>

        <div class="grade-usuarios">
            <?php foreach ($membros as $membro): ?>
                <article class="cartao-usuario <?= $membro['id'] == $usuario['id'] ? 'cartao-usuario-eu' : '' ?>">
                    <header class="usuario-avatar-bloco">
                        <div class="avatar-circulo">
                            <?= mb_strtoupper(mb_substr($membro['nome'], 0, 1)) ?>
                        </div>
                        <div>
                            <h3 class="usuario-nome">
                                <?= htmlspecialchars($membro['nome']) ?>
                                <?php if ($membro['id'] == $usuario['id']): ?>
                                    <span class="badge-eu">Você</span>
                                <?php endif; ?>
                            </h3>
                            <p class="usuario-email"><?= htmlspecialchars($membro['email']) ?></p>
                        </div>
                    </header>

                    <div class="usuario-stats">
                        <div class="stat-item">
                            <span class="stat-numero"><?= $membro['total_tarefas'] ?></span>
                            <span class="stat-label">Total</span>
                        </div>
                        <div class="stat-item stat-pendente">
                            <span class="stat-numero"><?= $membro['pendentes'] ?></span>
                            <span class="stat-label">Pendentes</span>
                        </div>
                        <div class="stat-item stat-andamento">
                            <span class="stat-numero"><?= $membro['em_andamento'] ?></span>
                            <span class="stat-label">Em andamento</span>
                        </div>
                        <div class="stat-item stat-concluida">
                            <span class="stat-numero"><?= $membro['concluidas'] ?></span>
                            <span class="stat-label">Concluídas</span>
                        </div>
                    </div>

                    <footer class="usuario-acoes">
                        <a href="tarefas.php?usuario=<?= $membro['id'] ?>" class="btn btn-pequeno btn-secundario">
                            Ver tarefas
                        </a>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
