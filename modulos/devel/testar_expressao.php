<?php
//
// SIMP
// Descricao: testa se um valor casa com uma expressao regular
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.1.1
// Data: 09/10/2007
// Modificado: 11/05/2010
// License: LICENSE.TXT
// Copyright (C) 2007  Rubens Takiguti Ribeiro
//
require_once('../../config.php');


/// Dados do Formulario
$action = $CFG->site;
$dados  = formulario::get_dados();


/// Dados da Pagina
$modulo = modulo::get_modulo(__FILE__);
$titulo = 'Teste de Express&atilde;o Regular';
$nav[$CFG->wwwmods.$modulo.'/index.php'] = 'Desenvolvimento';
$nav[''] = $titulo;
$estilos = array($CFG->wwwmods.$modulo.'/estilos.css');


/// Bloquear caso necessario
require_once($CFG->dirmods.$modulo.'/bloqueio.php');


/// Imprimir Pagina
$pagina = new pagina();
$pagina->cabecalho($titulo, $nav, $estilos);
$pagina->inicio_conteudo();
formulario_expressao($dados, $action);
if ($dados) {
    imprimir_resultado($dados);
}
imprimir_exemplos();
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


//
//     Imprime o formulario de expressao regular
//
function formulario_expressao($dados, $action) {
// Object $dados: dados submetidos
// String $action: endereco de destino dos dados
//
    $expressao = '';
    if (isset($_GET['expressao'])) {
        $def = validacao::get_definicao_tipo($_GET['expressao']);
        if ($def) {
            $expressao = $def->padrao;
        }
    }
    $padrao = array(
        'expressao' => $expressao,
        'valor' => ''
    );
    $dados = formulario::montar_dados($padrao, $dados);

    $form = new formulario($action, 'form_expressao_regular');
    $form->campo_text('expressao', 'expressao', $dados->expressao, 255, 30, 'Express&atilde;o Regular');
    $form->campo_textarea('valor', 'valor', $dados->valor, 50, 5, 'Valor');
    $form->campo_submit('enviar', 'enviar', 'Testar', 1);
    $form->imprimir();
}


//
//     Imprime o texto codificado
//
function imprimir_resultado($dados) {
// Object $dados: dados submetidos
//
    $resultado = preg_match($dados->expressao, $dados->valor, $match) ? '<span class="sim">SIM</span>'
                                                                      : '<span class="nao">N&Atilde;O</span>';
    echo "<div class=\"resultado\">\n";
    echo "  <p>Express&atilde;o: ".texto::codificar($dados->expressao)."</p>\n";
    echo "  <p>Valor: ".texto::codificar($dados->valor)."</p>\n";
    echo "  <p>Resultado do comando:</p>\n";
    echo "  <code>preg_match(\$expressao, \$valor, \$match)</code>\n";
    echo "  <p>Valor compat&iacute;vel com o padr&atilde;o: {$resultado}</p>\n";
    if (!empty($match)) {
        echo "  <p>Valores obtidos em <code>\$match</code>:</p>\n";
        echo '  <pre>';
        foreach ($match as $i => $valor) {
            echo "\$match[$i] = ".texto::codificar($valor)."\n";
        }
        echo '</pre>';
    }
    echo "</div>\n";
}


//
//     Imprime uma lista de exeplos de expressoes regulares
//
function imprimir_exemplos() {
    global $CFG;
    $link_base = $CFG->site;
    link::normalizar($link_base, true);

    $u = $CFG->utf8 ? 'u' : '';
    $tipos = validacao::get_tipos();

    echo "<hr />\n";
    echo "<h3>Exemplos:</h3>\n";
    echo "<ul>\n";
    foreach ($tipos as $tipo) {
        $def = validacao::get_definicao_tipo($tipo);
        if ($def) {
            $expressao = texto::codificar($def->padrao);
            $link = link::adicionar_atributo($link_base, 'expressao', $tipo);
            echo "  <li><strong><a href=\"{$link}\">{$tipo}</a>:</strong> <code>{$expressao}</code></li>\n";
        }
    }
    echo "</ul>\n";
    if ($CFG->utf8) {
        echo "<div class=\"observacao\">\n";
        echo "<p>A codifica&ccedil;&atilde;o utilizada &eacute; UTF-8, portanto as express&otilde;es possuem um \"u\" no final.</p>\n";
        echo "</div>\n";
    }
}
