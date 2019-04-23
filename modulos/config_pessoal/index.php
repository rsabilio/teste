<?php
//
// SIMP
// Descricao: Script de configuracoes pessoais
// Autor: Rubens Takiguti Ribeiro && Rodrigo Pereira Moreira
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.14
// Data: 23/07/2007
// Modificado: 04/10/2012
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');
require_once($CFG->dirroot.'sessao.php');

simp_autoload('mensagem');

/// Dados do formulario
$modulo = modulo::get_modulo(__FILE__);
$dados  = formulario::get_dados();
sanitizar($dados);
$campos = array(
    'tema',
    'ajax',
    'fonte',
    'tamanho',
    'tamanho_icones',
    'imagens',
    'transparencia',
    'som'
);
$action = $CFG->site;
$ajuda  = <<<AJUDA
  <p>Este formul&aacute;rio apresenta algumas op&ccedil;&otilde;es espec&iacute;ficas de
  apresenta&ccedil;&atilde;o.</p><p><acronym title="Asynchronous Javascript And XML">Ajax</acronym>
  &eacute; um conjunto de tecnologias que tornam aplica&ccedil;&otilde;es <em>Web</em> mais
  din&acirc;micas. Deve ser desabilitado caso as p&aacute;ginas n&atilde;o sejam carregadas
  corretamente.</p>
AJUDA;


/// Dados da pagina
$titulo  = 'Configura&ccedil;&otilde;es Pessoais';
$nav[]   = '#index.php';
$nav[]   = $modulo.'#'.basename(__FILE__);
$estilos = array($CFG->wwwmods.$modulo.'/estilos.css');


/// Se submeteu os dados, grava-los no $CFG->cookies
if ($dados) {
    if (!MENSAGEM_SOM) {
        $dados->som = 0;
    }
    foreach ($campos as $campo) {
        $CFG->cookies[$campo] = $dados->$campo;
        $CFG->pessoal->$campo = $dados->$campo;
    }
    if (!$CFG->pessoal->ajax) {
        $CFG->ajax = false;
        $CFG->cookies['ajax'] = 0;
    }
    $CFG->cookies['modificacao'] = $CFG->time;
    $CFG->pessoal->modificacao = $CFG->time;
}


/// Imprimir Pagina
$pagina = new pagina();
$pagina->cabecalho($titulo, $nav, $estilos);
$pagina->imprimir_menu($USUARIO);
$pagina->inicio_conteudo($titulo);
mensagem::comentario($CFG->site, $ajuda);
logica_formulario($dados, $campos, $action);
$pagina->fim_conteudo();
$pagina->rodape();
exit(0);


//
//    Logica do formulario de alterar configuracoes pessoais
//
function logica_formulario($dados, $campos, $action) {
// Object $dados: dados enviados pelo formulario
// Array[String] $campos: campos do formulario
// String $action: endereco de destino dos dados
//
    global $CFG;
    if ($dados) {
        if (pagina::$salvou_cookies === -1) {
            mensagem::aviso('Nenhum dado foi alterado');
        } elseif (pagina::$salvou_cookies) {
            mensagem::aviso('Dados salvos com sucesso');
        } else {
            mensagem::erro('Erro ao salvar os dados');
        }
    }
    imprimir_form($CFG->pessoal, $campos, $action);
}


//
//     Imprime o formulario de configuracoes pessoais
//
function imprimir_form($dados, $campos, $action) {
// Object $dados: dados enviados pelo formulario
// Array[String] $campos: campos do formulario
// String $action: endereco de destino dos dados
//
    global $CFG;
    $dados = util::objeto($campos, $dados);

    $vt_ajax = array(1 => 'Sim (Padr&atilde;o)',
                     0 => 'N&atilde;o');

    $vt_fontes = listas::get_fontes();

    $vt_tamanhos = array(
        '50%' => '50% (Menor)',
        '55%' => '55%',
        '60%' => '60%',
        '65%' => '65%',
        '70%' => '70%',
        '75%' => '75%',
        '80%' => '80%',
        '85%' => '85% (Ideal)',
        '90%' => '90%',
        '95%' => '95%',
        '100%' => '100% (Padr&atilde;o)',
        '110%' => '110%',
        '120%' => '120%',
        '130%' => '130%',
        '140%' => '140%',
        '150%' => '150% (Maior)'
    );
    $vt_tamanhos_icones = array(
        '0' => 'Pequeno',
        '1' => 'Normal',
        '2' => 'Grande',
        '3' => 'Muito Grande'
    );

    $form = new formulario($action, 'form_config_pessoal', false, 'post', false);
    $form->campo_aviso('As modifica&ccedil;&otilde;es deste formul&aacute;rio s&atilde;o aplicadas neste Navegador e n&atilde;o tem rela&ccedil;&atilde;o direta com o usu&aacute;rio que aplicou as modifica&ccedil;&otilde;es.');
    $form->campo_select('fonte', 'fonte', $vt_fontes, $dados->fonte, 'Fonte Padr&atilde;o');
    $form->campo_select('tamanho', 'tamanho', $vt_tamanhos, $dados->tamanho, 'Tamanho da Fonte');
    $form->campo_select('tamanho_icones', 'tamanho_icones', $vt_tamanhos_icones, $dados->tamanho_icones, 'Tamanho dos &Iacute;cones');
    $form->campo_select('tema', 'tema', $CFG->vt_temas, $dados->tema, 'Tema');
    $form->campo_bool_radio('ajax', 'ajax', 'Usar <acronym title="Asynchronous Javascript And XML">Ajax</acronym>', $dados->ajax);
    $form->campo_bool_radio('imagens', 'imagens', 'Imagens', $dados->imagens);
    $form->campo_bool_radio('transparencia', 'transparencia', 'Transpar&ecirc;ncia', $dados->transparencia);
    if (MENSAGEM_SOM) {
        $form->campo_bool_radio('som', 'som', 'Som nas ajudas', $dados->som);
    }
    $form->campo_submit('enviar', 'enviar', 'Alterar', true, true);
    $form->imprimir();
}


//
//     Sanitiza os dados submetidos
//
function sanitizar(&$dados) {
// stdClass $dados: dados a serem sanitizados
//
    if (!$dados) {
        return;
    }
    $dados->ajax = formulario::filtrar('bool', $dados->ajax);
    $dados->string = formulario::filtrar('string', $dados->fonte);
    $dados->tamanho = formulario::filtrar('string', $dados->tamanho);
    $dados->tamanho_icones = formulario::filtrar('int', $dados->tamanho_icones);
    $dados->tema = formulario::filtrar('string', $dados->tema);
    $dados->imagens = formulario::filtrar('bool', $dados->imagens);
    $dados->transparencia = formulario::filtrar('bool', $dados->transparencia);
    if (MENSAGEM_SOM) {
        $dados->som = formulario::filtrar('bool', $dados->som);
    }
}
