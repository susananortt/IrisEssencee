<?php
    // Conexão com o banco de dados
    require_once 'conexao.php';
    
//BUSCA HORARIOS PARA UM DIA ESPECIFICO
    function fetchHorariosDisponiveis($pdo, $data, $ignorarHora = null) {
        $grade = ["08:00","09:00","10:00","11:00","13:00","14:00","15:00","16:00","17:00"];
        $sql = "SELECT hora FROM agendamentos WHERE data = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data]);
        $ocupados = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $ocupados = array_map(fn($h) => substr(trim($h), 0, 5), $ocupados);
        if ($ignorarHora !== null) {
            $ocupados = array_filter($ocupados, fn($h) => $h !== $ignorarHora);
        }
        return array_values(array_diff($grade, $ocupados));
    }
    
//AÇÃO RECEBIDA POR URL
    $action = 
    $_GET['action'] ?? '';
  
//MOSTRA RESUMO DOS AGENDAMENTOS
    switch ($action) {
        case 'resumo':
        $data = 
        $_GET['data'] ?? '';
        if (!$data) {
            echo json_encode([]);
            exit;
        }
        $sql = "SELECT nome, procedimento, hora FROM agendamentos WHERE data = ? ORDER BY hora";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data]);
        $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($agendamentos);
        break;
 
//MOSTRA DATALHES DO AGENDAMENTO
        case 'detalhes':
        $data = 
        $_GET['data'] ?? '';
        if (!$data) {
            echo "Data inválida.";
            exit;
        }
        $sql = "SELECT * FROM agendamentos WHERE data = ? ORDER BY hora";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data]);
        $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

<!--EXIBE OS DETALHES DOS AGENDAMENTOS-->
    <h5>Agendamentos para <?= date('d/m/Y', strtotime($data)) ?></h5>
    
    <table class="table table-striped">
    <thead>
    <tr>
    <th>Hora</th>
    <th>Nome</th>
    <th>Procedimento</th>
    <th>Ações</th>
    </tr>
    </thead>
    <tbody>
    <?php if (count($agendamentos) === 0): ?>
    <tr><td colspan="4"><em>Sem agendamentos.</em></td></tr>
    <?php else: ?>
    <?php foreach ($agendamentos as $ag): ?>
    <tr>
    <td><?= substr($ag['hora'], 0, 5) ?></td>
    <td><?= htmlspecialchars($ag['nome']) ?></td>
    <td><?= htmlspecialchars($ag['procedimento']) ?></td>
    <td>
    <button class="btn btn-sm btn-warning btn-editar" data-id="<?= $ag['id_agendamento'] ?>">Editar</button>
    <button class="btn btn-sm btn-danger btn-excluir" data-id="<?= $ag['id_agendamento'] ?>">Excluir</button>
    </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
    </table>
    
    <hr>
    <h5>Novo agendamento</h5>
    <form id="form-novo-agendamento">
    <input type="hidden" name="data" value="<?= htmlspecialchars($data) ?>" />
    <div class="mb-3">
    <label for="nome" class="form-label">Nome:</label>
    <input type="text" name="nome" id="nome" class="form-control" required />
    </div>
    <div class="mb-3">
    <label for="procedimento" class="form-label">Procedimento:</label>
    <select name="procedimento" id="procedimento" class="form-select" required>
    <optgroup label="Procedimentos Faciais">
    <option value="Limpeza de Pele">Limpeza de Pele</option>
    <option value="Preenchimento Labial">Preenchimento Labial</option>
    <option value="Microagulhamento">Microagulhamento</option>
    <option value="Botox">Botox</option>
    <option value="Tratamento para Acne">Tratamento para Acne</option>
    <option value="Rinomodelação">Rinomodelação</option>
    </optgroup>
    <optgroup label="Procedimentos Corporais">
    <option value="Massagem Modeladora">Massagem Modeladora</option>
    <option value="Drenagem Linfática">Drenagem Linfática</option>
    <option value="Depilação a Laser">Depilação a Laser</option>
    <option value="Depilação a Cera">Depilação a Cera</option>
    <option value="Massagem Relaxante">Massagem Relaxante</option>
    </optgroup>
    </select>
    </div>
    <div class="mb-3">
    <label for="hora" class="form-label">Hora:</label>
    <select name="hora" id="hora" class="form-select" required>
    <?php
        $horarios = fetchHorariosDisponiveis($pdo, $data);
        foreach ($horarios as $h) {
            echo "<option value=\"$h\">$h</option>";
        }
    ?>
    </select>
    </div>
    <button type="submit" class="btn btn-success">Cadastrar</button>
    </form>
    
    <?php
        break;

//CADASTRO NOVO AGENDAMENTO
        case 'cadastrar':
        $nome = trim(// Dados enviados via formulário
        $_POST['nome'] ?? '');
        $procedimento = trim(// Dados enviados via formulário
        $_POST['procedimento'] ?? '');
        $data = // Dados enviados via formulário
        $_POST['data'] ?? '';
        $hora = // Dados enviados via formulário
        $_POST['hora'] ?? '';
//VERIFICA SE TODOS OS CAMPOS FORAM PREENCHIDOS
        if ($nome === '' || $procedimento === '' || !$data || !$hora) {
            echo json_encode(['sucesso'=>false, 'msg'=>'Preencha todos os campos corretamente.']);
            exit;
        }
        // VERIFICA SE O HORARIO JA ESTA OCUPADO
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE data = ? AND hora = ?");
        $stmt->execute([$data, $hora]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['sucesso'=>false, 'msg'=>'Horário já ocupado.']);
            exit;
        }
        //INSERE O NOVO AGENDAMENTO
        $ins = $pdo->prepare("INSERT INTO agendamentos (nome, procedimento, data, hora) VALUES (?, ?, ?, ?)");
        if ($ins->execute([$nome, $procedimento, $data, $hora])) {
            echo json_encode(['sucesso'=>true, 'msg'=>'Agendamento cadastrado com sucesso!']);
        } else {
            echo json_encode(['sucesso'=>false, 'msg'=>'Erro ao cadastrar.']);
        }
        break;

//EXCLUI O AGENDAMENTO
        case 'excluir':
        $id = (int)(// Dados recebidos via URL
        $_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['sucesso'=>false, 'msg'=>'ID inválido.']);
            exit;
        }
        $del = $pdo->prepare("DELETE FROM agendamentos WHERE id_agendamento = ?");
        if ($del->execute([$id])) {
            echo json_encode(['sucesso'=>true, 'msg'=>'Agendamento excluído com sucesso!']);
        } else {
            echo json_encode(['sucesso'=>false, 'msg'=>'Erro ao excluir.']);
        }
        break;

//EDITA O AGENDAMENTO
        case 'editar':
        $id = (int)(// Dados recebidos via URL
        $_GET['id'] ?? 0);
        if ($id <= 0) {
            echo "ID inválido.";
            exit;
        }
        $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id_agendamento = ?");
        $stmt->execute([$id]);
        $ag = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ag) {
            echo "Agendamento não encontrado.";
            exit;
        }
    ?>

<!--EXIBE FORMULARIO DE EDITAR-->
    <h5>Editar Agendamento</h5>
    <form id="form-editar-agendamento">
    <input type="hidden" name="id" value="<?= $ag['id_agendamento'] ?>" />
    <div class="mb-3">
    <label for="nome_edit" class="form-label">Nome:</label>
    <input type="text" name="nome" id="nome_edit" class="form-control" required value="<?= htmlspecialchars($ag['nome']) ?>" />
    </div>
    <div class="mb-3">
    <label for="procedimento_edit" class="form-label">Procedimento:</label>
    <select name="procedimento" id="procedimento_edit" class="form-select" required>
    <optgroup label="Procedimentos Faciais">
    <option value="Limpeza de Pele" <?= $ag['procedimento']=='Limpeza de Pele' ? 'selected' : '' ?>>Limpeza de Pele</option>
    <option value="Preenchimento Labial" <?= $ag['procedimento']=='Preenchimento Labial' ? 'selected' : '' ?>>Preenchimento Labial</option>
    <option value="Microagulhamento" <?= $ag['procedimento']=='Microagulhamento' ? 'selected' : '' ?>>Microagulhamento</option>
    <option value="Botox" <?= $ag['procedimento']=='Botox' ? 'selected' : '' ?>>Botox</option>
    <option value="Tratamento para Acne" <?= $ag['procedimento']=='Tratamento para Acne' ? 'selected' : '' ?>>Tratamento para Acne</option>
    <option value="Rinomodelação" <?= $ag['procedimento']=='Rinomodelação' ? 'selected' : '' ?>>Rinomodelação</option>
    </optgroup>
    <optgroup label="Procedimentos Corporais">
    <option value="Massagem Modeladora" <?= $ag['procedimento']=='Massagem Modeladora' ? 'selected' : '' ?>>Massagem Modeladora</option>
    <option value="Drenagem Linfática" <?= $ag['procedimento']=='Drenagem Linfática' ? 'selected' : '' ?>>Drenagem Linfática</option>
    <option value="Depilação a Laser" <?= $ag['procedimento']=='Depilação a Laser' ? 'selected' : '' ?>>Depilação a Laser</option>
    <option value="Depilação a Cera" <?= $ag['procedimento']=='Depilação a Cera' ? 'selected' : '' ?>>Depilação a Cera</option>
    <option value="Massagem Relaxante" <?= $ag['procedimento']=='Massagem Relaxante' ? 'selected' : '' ?>>Massagem Relaxante</option>
    </optgroup>
    </select>
    </div>
    <div class="mb-3">
    <label for="hora_edit" class="form-label">Hora:</label>
    <select name="hora" id="hora_edit" class="form-select" required>
    <?php
    //HORARIOS DISPONIVEIS
        $horarios = fetchHorariosDisponiveis($pdo, $ag['data'], substr($ag['hora'],0,5));
        // Coloca o horário atual na lista também
        if (!in_array(substr($ag['hora'],0,5), $horarios)) {
            $horarios[] = substr($ag['hora'],0,5);
        }
        sort($horarios);
        foreach ($horarios as $h) {
            $sel = ($h == substr($ag['hora'],0,5)) ? 'selected' : '';
            echo "<option value=\"$h\" $sel>$h</option>";
        }
    ?>
    </select>
    </div>
    <input type="hidden" name="data" value="<?= htmlspecialchars($ag['data']) ?>" />
    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
    </form>
    <?php
        break;
 
//ALTERA AGENDAMENTO(SALVA ALTERAÇÃO)
        case 'alterar':
        $id = (int)(// Dados enviados via formulário
        $_POST['id'] ?? 0);
        $nome = trim(// Dados enviados via formulário
        $_POST['nome'] ?? '');
        $procedimento = trim(// Dados enviados via formulário
        $_POST['procedimento'] ?? '');
        $data = // Dados enviados via formulário
        $_POST['data'] ?? '';
        $hora = // Dados enviados via formulário
        $_POST['hora'] ?? '';
        
        if ($id <= 0 || $nome === '' || $procedimento === '' || !$data || !$hora) {
            echo json_encode(['sucesso'=>false, 'msg'=>'Preencha todos os campos corretamente.']);
            exit;
        }
        
        // Verifica conflito ignorando o próprio id
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE data = ? AND hora = ? AND id_agendamento != ?");
        $stmt->execute([$data, $hora, $id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['sucesso'=>false, 'msg'=>'Horário já ocupado.']);
            exit;
        }

//ATUALIZA AGENDAMENTO NO BANCO DE DADOS
        $upd = $pdo->prepare("UPDATE agendamentos SET nome = ?, procedimento = ?, data = ?, hora = ? WHERE id_agendamento = ?");
        if ($upd->execute([$nome, $procedimento, $data, $hora, $id])) {
            echo json_encode(['sucesso'=>true, 'msg'=>'Agendamento atualizado com sucesso!']);
        } else {
            echo json_encode(['sucesso'=>false, 'msg'=>'Erro ao atualizar.']);
        }
        break;
        
        default:
        echo "Ação inválida.";
        break;
    }
    