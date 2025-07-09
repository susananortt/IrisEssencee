<?php
require_once 'conexao.php';
session_start();

// VERIFICA SE A SESSÃO FOI INICIADA CORRETAMENTE E PERFIL ESTÁ DEFINIDO
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] != 1 && $_SESSION['perfil'] != 2)) {
    echo "<script>alert('Acesso negado!');window.location.href='principal.php';</script>";
    exit();
}

$procedimentos = []; //INICIALIZA A VARIAVEL PARA EVITAR ERROS

//SE O FORMULÁRIO FOR ENVIADO, BUSCA O USUÁRIO PELO ID OU NOME
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['busca'])) {
    $busca = trim($_POST['busca']);

    //VERIFICA SE A BUSCA É UM NÚMERO(ID) OU UM NOME
    if (is_numeric($busca)) {
        $sql = "SELECT * FROM procedimento WHERE id_procedimento = :busca ORDER BY nome ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':busca', $busca, PDO::PARAM_INT);
    } else {
        $sql = "SELECT * FROM procedimento WHERE nome LIKE :busca_nome ORDER BY nome ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':busca_nome', "%$busca%", PDO::PARAM_STR);
    }
} else {
    $sql = "SELECT * FROM procedimento ORDER BY nome ASC";
    $stmt = $pdo->prepare($sql);
}
$stmt->execute();
$procedimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Íris Essence - Beauty Clinic</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<script src="script.js"></script>
<link rel="icon" href="../imgs/logo.jpg" type="image/x-icon">
</head>
<body class="cadastro-fundo">
<header>
    <nav>
        <ul>
            <a href="../html/index.html">
                <img src="../imgs/logo.jpg" class="logo" alt="Logo">
            </a>
            <li><a href="../html/index.html">HOME</a></li>
            <li>
                <a href="#">PROCEDIMENTOS FACIAIS</a>
                <div class="submenu">
                    <a href="../html/limpezapele.html">Limpeza de Pele</a>
                    <a href="../html/labial.html">Preenchimento labial</a>
                    <a href="../html/microagulhamento.html">Microagulhamento</a>
                    <a href="../html/botoxfacial.html">Botox</a>
                    <a href="../html/acne.html">Tratamento para Acne</a>
                    <a href="../html/rinomodelacao.html">Rinomodelação</a>
                </div>
            </li>
            <li>
                <a href="#">PROCEDIMENTOS CORPORAIS</a>
                <div class="submenu">
                    <a href="../html/massagemmodeladora.html">Massagem Modeladora</a>
                    <a href="../html/drenagemlinfatica.html">Drenagem Linfática</a>
                    <a href="../html/depilacaolaser.html">Depilação a Laser</a>
                    <a href="../html/depilacaocera.html">Depilação de cera</a>
                    <a href="../html/massagemrelaxante.html">Massagem Relaxante</a>
                </div>
            </li>
            <li><a href="../html/produtos.html">PRODUTOS</a></li>
            <li><a href="../html/login.php">LOGIN</a></li>
            <li><a href="../html/cadastro.html">CADASTRO</a></li>

            <div class="logout">
                <form action="logout.php" method="POST">
                    <button type="submit">Logout</button>
                </form>
            </div>
        </ul>
    </nav>
</header>
<br><br><br><br><br>

<div class="formulario">
<fieldset>

<!-- FORMULARIO PARA BUSCAR -->
<form action="buscar_procedimento.php" method="POST">
<legend>Listar procedimentos</legend>
<label for="busca">Digite o ID ou NOME(opcional):</label>
<input type="text" id="busca" name="busca">

<button type="submit">Pesquisar</button>
</form>

<?php if(!empty($procedimentos)): ?>
<table border="1">
<tr>
<th>ID</th>
<th>Nome</th>
<th>Descrição</th>
</tr>
<?php foreach($procedimentos as $procedimento): ?>
<tr>
<td><?= htmlspecialchars($procedimento['id_procedimento']) ?></td>
<td><?= htmlspecialchars($procedimento['nome']) ?></td>
<td><?= htmlspecialchars($procedimento['descricao']) ?></td>
<td>
<a href="alterar_procedimento.php?id=<?= htmlspecialchars($procedimento['id_procedimento']) ?>">✏️</a>
<a href="excluir_procedimento.php?id=<?= htmlspecialchars($procedimento['id_procedimento']) ?>" onclick="return confirm('Tem certeza que deseja excluir este procedimento?')">🗑️</a>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p>Nenhum procedimento encontrado.</p>
<?php endif; ?>

<br>
<button type="button" class="voltar-button" onclick="window.location.href='principal.php'">Voltar</button>

</fieldset>
</div>

<br><br><br><br><br><br>
<footer class="l-footer">&copy; 2025 Iris Essence - Beauty Clinic. Todos os direitos reservados.</footer>
</body>
</html>
