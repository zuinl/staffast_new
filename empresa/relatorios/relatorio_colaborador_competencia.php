<?php

    include('../../include/auth.php');
    include('../../src/meta.php');
    require_once('../../classes/class_conexao_empresa.php');
    require_once('../../classes/class_queryHelper.php');
    require_once('../../classes/class_colaborador.php');
    require_once('../../classes/class_avaliacao.php');

    $conexao = new ConexaoEmpresa($_SESSION['empresa']['database']);
        $conn = $conexao->conecta();
    $helper = new QueryHelper($conn);
    
    $array_compet = $_POST['compet'];
    $competencias = explode("|", $array_compet);
    $competencia = $competencias[0]; //É NECESSÁRIO SELECIONAR 1 COMPETÊNCIA PARA ESTE RELATÓRIO

    $select = "SELECT AVG(t1.".$competencia.") as media FROM tbl_avaliacao t1 
    INNER JOIN tbl_colaborador t2 WHERE t2.col_cpf = t1.col_cpf";

    $colaborador = new Colaborador();

    //QUERY AVALIAÇÃO MAIS ANTIGA E MAIS RECENTE DA COMPETÊNCIA
    $select = "SELECT DATE_FORMAT(MAX(ava_data_criacao), '%d/%m/%Y %H:%i:%s') as mais_recente,
    DATE_FORMAT(MIN(ava_data_criacao), '%d/%m/%Y %H:%i:%s') as mais_antiga
    FROM tbl_avaliacao
    WHERE ava_data_liberacao <= NOW()".$condicao;
    $fetch = $helper->select($select, 2);

    $mais_recente = $fetch['mais_recente'];
    $mais_antiga = $fetch['mais_antiga'];

    //QUERY 5 MAIORES NOTAS DA COMPETÊNCIA
    $select = "SELECT ".$competencia." as compet,
    DATE_FORMAT(ava_data_criacao, '%d/%m/%Y %H:%i:%s') as data
    FROM tbl_avaliacao WHERE col_cpf = '$cpf' AND ava_data_liberacao <= NOW()".$condicao." 
    ORDER BY ".$competencia." DESC LIMIT 5";
    $query_maiores = $helper->select($select, 1);
    $maiores = array(0, 0, 0, 0, 0);
    $datas_maiores = array('Sem dados', 'Sem dados', 'Sem dados', 'Sem dados', 'Sem dados');

    $posicao = 0;
    while($f = mysqli_fetch_assoc($query_maiores)) {
        $maiores[$posicao] = $f['compet'];
        $datas_maiores[$posicao] = $f['data'];
        $posicao++;
    }

    //QUERY 5 MENORES NOTAS DA COMPETENCIA
    $select = "SELECT ".$competencia." as compet,
    DATE_FORMAT(ava_data_criacao, '%d/%m/%Y %H:%i:%s') as data
    FROM tbl_avaliacao WHERE col_cpf = '$cpf' AND ava_data_liberacao <= NOW()
    ".$condicao." ORDER BY ".$competencia." ASC LIMIT 5";
    $query_menores = $helper->select($select, 1);
    $menores = array(0, 0, 0, 0, 0);
    $datas_menores = array('Sem dados', 'Sem dados', 'Sem dados', 'Sem dados', 'Sem dados');

    $posicao = 0;
    while($f = mysqli_fetch_assoc($query_menores)) {
        $menores[$posicao] = $f['compet'];
        $datas_menores[$posicao] = $f['data'];
        $posicao++;
    }

    //QUERY PARA USAR NO RELATÓRIO, COM O HISTÓRICO COMPLETO
    $select = "SELECT DATE_FORMAT(ava_data_criacao, '%d/%m/%Y %H:%i:%s') as data,
    ".$competencia." as compet, 
    ".$competencia."_obs as obs, ges_cpf as cpf
    FROM tbl_avaliacao WHERE col_cpf = '$cpf' AND ava_data_liberacao <= NOW()
    ".$condicao." ORDER BY ava_data_criacao DESC";
    $query = $helper->select($select, 1);
    $num_avaliacoes = mysqli_num_rows($query);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Relatório de Avaliação Por Competência</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <script type="text/javascript">
    google.charts.load("current", {packages:['corechart']});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() {
      var data = google.visualization.arrayToDataTable([
        ["Data", "Nota", { role: "style" } ],
        ['<?php echo $datas_maiores[0]; ?>', <?php echo $maiores[0]; ?>, "green"],
        ['<?php echo $datas_maiores[1]; ?>', <?php echo $maiores[1]; ?>, "green"],
        ['<?php echo $datas_maiores[2]; ?>', <?php echo $maiores[2]; ?>, "green"],
        ['<?php echo $datas_maiores[3]; ?>', <?php echo $maiores[3]; ?>, "green"],
        ['<?php echo $datas_maiores[4]; ?>', <?php echo $maiores[4]; ?>, "green"]
      ]);

      var view = new google.visualization.DataView(data);
      view.setColumns([0, 1,
                       { calc: "stringify",
                         sourceColumn: 1,
                         type: "string",
                         role: "annotation" },
                       2]);

      var options = {
        title: "Melhores períodos de <?php echo $competencias[1]; ?>",
        legend: { position: "none" },
      };
      var chart = new google.visualization.ColumnChart(document.getElementById("grafico"));
      chart.draw(view, options);
  }
  </script>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <script type="text/javascript">
    google.charts.load("current", {packages:['corechart']});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() {
      var data = google.visualization.arrayToDataTable([
        ["Data", "Nota", { role: "style" } ],
        ['<?php echo $datas_menores[0]; ?>', <?php echo $menores[0]; ?>, "yellow"],
        ['<?php echo $datas_menores[1]; ?>', <?php echo $menores[1]; ?>, "yellow"],
        ['<?php echo $datas_menores[2]; ?>', <?php echo $menores[2]; ?>, "orange"],
        ['<?php echo $datas_menores[3]; ?>', <?php echo $menores[3]; ?>, "orange"],
        ['<?php echo $datas_menores[4]; ?>', <?php echo $menores[4]; ?>, "red"]
      ]);

      var view = new google.visualization.DataView(data);
      view.setColumns([0, 1,
                       { calc: "stringify",
                         sourceColumn: 1,
                         type: "string",
                         role: "annotation" },
                       2]);

      var options = {
        title: "Piores períodos de <?php echo $competencias[1]; ?>",
        legend: { position: "none" },
      };
      var chart = new google.visualization.ColumnChart(document.getElementById("grafico1"));
      chart.draw(view, options);
  }
  </script>
</head>
<body style="margin-top: 0em;">
<div class="container">
    <div class="row">
        <div class="col-sm" style="text-align: center;">
          <img src="/staffast/img/logo_staffast.png" width="300">
        </div>
    </div>

    <div class="row">
        <?php if($_SESSION['empresa']['logotipo'] != "") { ?>
        <div class="col-sm-1">
            <img src="<?php echo $_SESSION['empresa']['logotipo']; ?>" width="100">
        </div>
        <?php } ?>
        <div class="col-sm">
            <h4 class="high-text"><?php echo $colaborador->getNomeCompleto(); ?></h4>
        </div>
        <div class="col-sm">
            <h5 class="high-text">Competência: <?php echo $competencias[1]; ?></h5>
        </div>
        <div class="col-sm">
            <h5 class="high-text">Avaliações encontradas: <?php echo $num_avaliacoes; ?></h5>
        </div>
        <div class="col-sm">
            <h5 class="high-text">Mais antiga: <?php echo $mais_antiga; ?></h5>
        </div>
        <div class="col-sm">
            <h5 class="high-text">Mais recente: <?php echo $mais_recente; ?></h3>
        </div>
        <?php if(isset($_POST['gestor']) && $_POST['gestor'] != "") { ?>
        <div class="col-sm">
            <h5 class="high-text">Gestor: <?php echo $gestor->getNomeCompleto(); ?></h3>
        </div>
        <?php } ?>
    </div>

    <hr class="hr-divide-light">

    <div class="row">
      <div class="col-sm">
        <div id="grafico" style="width: 100%; height: 410px;"></div>
      </div>
      <div class="col-sm">
        <div id="grafico1" style="width: 100%; height: 410px;"></div>
      </div>
    </div>

    <hr class="hr-divide-super-light">

    <div class="row">
        <div class="col-sm">
          <h4 class="destaque-text">Relatório completo</h4>
        </div>
    </div>
    <?php
    if($num_avaliacoes == 0) { echo '<h1 class="text">Nada encontrado</h1>'; die(); }
    else {
      $string = "";
      while($fetch = mysqli_fetch_assoc($query)) {
          $nota = $fetch['compet'];
          $data = $fetch['data'];
          $fetch['obs'] == "" ? $obs = "Nada observado" : $obs = $fetch['obs'];

          $gestor = new Gestor();
          $gestor->setCpf($fetch['cpf']);
          $gestor = $gestor->retornarGestor($_SESSION['empresa']['database']);

          $string .= '
              <br><span class="text"><b>'.$data.'</b> - Nota: '.$nota.' - Gestor: '.$gestor->getNomeCompleto().'
              <br>Observações realizadas: '.$obs.'
              </span>
          ';
      }
    }

    echo $string;
    ?>
</div>