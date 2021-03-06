<?php
//
// SIMP
// Descricao: Script de Instalacao das Permissoes padrao
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.1.0.13
// Data: 10/09/2007
// Modificado: 06/05/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//


//
//     Instala as permissoes padrao (para cada arquivo do diretorio permissoes ha as permissoes de determinado grupo)
//
function instalar_permissao(&$erros) {
// Array[String] $erros: erros ocorridos
//
    global $CFG;
    $r = true;

    if (objeto::get_objeto('permissao')->possui_registros()) {
        return true;
    }

    $diretorio = realpath($CFG->dirclasses.'instalacao/permissao/');

    $dir = opendir($diretorio);
    if (!$dir) {
        $erros[] = "Erro ao abrir diret&oacute;rio \"{$diretorio}\"";
        return false;
    }

    while (($item = readdir($dir)) !== false) {
        if (!preg_match('/\.ini$/', $item)) { continue; }

        $ini = parse_ini_file($diretorio.'/'.$item, true);
        if (!$ini) {
            $erros[] = "Erro no arquivo INI \"{$item}\".";
            $r = false;
        } elseif (!isset($ini['cod_grupo'])) {
            $erros[] = "N&atilde;o foi especificado o grupo (diretiva \"cod_grupo\") no arquivo INI \"{$item}\".";
            $r = false;
        } elseif (!is_numeric($ini['cod_grupo'])) {
            $erros[] = "A diretiva cod_grupo do arquivo INI \"{$item}\" n&atilde;o possui valor inteiro.";
            $erros[] = 'Verifique se foi criada uma constante chamada "'.texto::codificar($ini['cod_grupo']).'"';
            return false;
        } else {
            $cod_grupo = (int)$ini['cod_grupo'];
            unset($ini['cod_grupo']);
            foreach ($ini as $modulo => $arquivos) {
                $r = $r && inserir_permissoes($cod_grupo, $modulo, $arquivos, $erros, $item);
            }
        }
    }
    closedir($dir);

    return $r;
}


//
//     Retorna um vetor de classes dependentes
//
function dependencias_permissao() {
    return array('grupo', 'arquivo');
}


//
//     Insere permissoes de arquivos para um grupo
//
function inserir_permissoes($cod_grupo, $modulo, $arquivos, &$erros, $arq_ini) {
// Int $cod_grupo: codigo do grupo
// String $modulo: nome do modulo
// Array[String => Bool] $arquivos: vetor com nome e visibilidade dos arquivos
// Array[String] $erros: erros ocorridos
// String $arq_ini: nome do arquivo INI
//
    static $i = 1;
    static $cod_grupo_atual = 0;

    if ($cod_grupo_atual != $cod_grupo) {
        $i = 1;
        $cod_grupo_atual = $cod_grupo;
    }

    $r = true;
    foreach ($arquivos as $arquivo => $visivel) {
        $r = $r && inserir_permissao($cod_grupo, $modulo, $arquivo, $visivel, $i++, $erros, $arq_ini);
    }
    return $r;
}


//
//     Insere uma permissao para um grupo
//
function inserir_permissao($cod_grupo, $modulo, $arquivo, $visivel, $posicao, &$erros, $arq_ini) {
// Int $cod_grupo: codigo do grupo
// String $modulo: nome do modulo
// String $arquivo: nome do arquivo
// Bool $visivel: arquivo visivel no menu ou nao
// Int $posicao: posicao no menu
// Array[String] $erros: erros ocorridos
// String $arq_ini: nome do arquivo INI
//
    static $grupos = array();

    $vt_condicoes = array();
    $vt_condicoes[] = condicao_sql::montar('modulo', '=', $modulo);
    $vt_condicoes[] = condicao_sql::montar('arquivo', '=', $arquivo);
    $condicoes = condicao_sql::sql_and($vt_condicoes);

    $arq = new arquivo();
    $consultou = $arq->consultar_condicoes($condicoes);

    if (!$consultou) {
        $erros[] = "O arquivo {$arquivo} (m&oacute;dulo {$modulo}) n&atilde;o foi cadastrado, mas foi solicitado no arquivo de instala&ccedil;&atilde;o \"{$arq_ini}\"";
        $erros[] = 'Verifique se este arquivo existe de verdade e se foi especificado no arquivo "arquivo.ini"';
        return false;
    }
    $cod_arquivo = $arq->cod_arquivo;

    if (!isset($grupos[$cod_grupo])) {
        $grupo = new grupo('', $cod_grupo);
        $grupos[$cod_grupo] = $grupo->existe();
    }
    if (!$grupos[$cod_grupo]) {
        $erros[] = 'O grupo "'.$cod_grupo.'" n&atilde;o foi cadastrado ou n&atilde;o existe';
        return false;
    }

    $p = new permissao();
    $p->set_id_form($p->id_formulario_inserir());
    $p->cod_grupo   = $cod_grupo;
    $p->cod_arquivo = $cod_arquivo;
    $p->posicao     = (int)$posicao;
    $p->visivel     = (bool)$visivel;
    $dados = $p->get_dados(true);
    $r = $p->validacao_final($dados) && $p->salvar();

    if (!$r) {
        $erros[] = "Erro ao instalar arquivo {$arquivo} / m&oacute;dulo {$modulo} ({$arq_ini})";
        $erros[] = $p->get_erros();
    }
    return $r;
}


/// Funcao extra (usada pelo script simp-permissoes)


//
//     Instala as permissoes de um determinado grupo
//
function instalar_permissao_grupo($cod_grupo, &$erros) {
// Int $cod_grupo: codigo do grupo
// Array[String] $erros: erros ocorridos
//
    global $CFG;
    $r = true;

    $diretorio = realpath($CFG->dirclasses.'instalacao/permissao/');

    $dir = opendir($diretorio);
    if (!$dir) {
        $erros[] = "Erro ao abrir diret&oacute;rio \"{$diretorio}\"";
        return false;
    }

    while (($item = readdir($dir)) !== false) {
        if (!preg_match('/\.ini$/', $item)) { continue; }

        $ini = parse_ini_file($diretorio.'/'.$item, true);
        if (!$ini) {
            $erros[] = "Erro no arquivo INI \"{$item}\".";
            $r = false;
        } elseif (!isset($ini['cod_grupo']) || !is_numeric($ini['cod_grupo'])) {
            $erros[] = "N&atilde;o foi especificado o grupo (diretiva \"cod_grupo\") no arquivo INI \"{$item}\".";
            $r = false;
        } else {
            if ($cod_grupo == (int)$ini['cod_grupo']) {
                unset($ini['cod_grupo']);
                foreach ($ini as $modulo => $arquivos) {
                    $r = $r && inserir_permissoes($cod_grupo, $modulo, $arquivos, $erros, $item);
                }
            }
        }
    }
    closedir($dir);

    return $r;
}
