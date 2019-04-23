<?php
//
// SIMP
// Descricao: Classe arquivos do sistema
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.1.1.4
// Data: 10/09/2007
// Modificado: 27/04/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
final class arquivo extends arquivo_base {

    //
    //     Realiza a validacao final dos formularios
    //
    public function validacao_final(&$dados) {
    // Object $dados: dados a serem validados
    //
        $r = true;

        switch ($this->id_form) {
        case $this->id_formulario_inserir():
        case $this->id_formulario_alterar():
            if ($this->get_atributo('modulo')) {
                $arq = ARQUIVO_DIR_MODULOS.$this->get_atributo('modulo').'/'.$this->get_atributo('arquivo');
            } else {
                $arq = ARQUIVO_DIR_ROOT.$this->get_atributo('arquivo');
            }
            if (!is_file($arq)) {
                $r = false;
                $this->erros[] = 'Arquivo '.$arq.' n&atilde;o existe no sistema';
            }
            break;
        }
        return $r;
    }


    //
    //     Retorna um vetor de modulos
    //
    public function get_vetor_modulo() {
        return array('' => 'Nenhum') + listas::get_modulos();
    }


    //
    //     Consulta um arquivo pelo nome e modulo
    //
    public static function consultar_arquivo_modulo($nome_arquivo, $modulo, $campos = false) {
    // String $nome_arquivo: nome do arquivo
    // String $modulo: nome do modulo
    // Array[String] $campos: campos desejados (true = todos | false = apenas PK)
    //
        if (DIRECTORY_SEPARATOR != '/') {
            $modulo = str_replace(DIRECTORY_SEPARATOR, '/', $modulo);
        }
        
        // Buscar entre as entidades ja consultadas
        if (isset(self::$instancias[__CLASS__])) {
            foreach (self::$instancias[__CLASS__] as $cod_arquivo => $arquivo) {
                if (isset($arquivo->valores['arquivo']) && isset($arquivo->valores['modulo']) &&
                    $arquivo->valores['arquivo'] == $nome_arquivo && $arquivo->valores['modulo'] == $modulo) {
                    return new self('', $cod_arquivo);
                }
            }
        }

        // Se nao achou: buscar no BD
        $vt_condicoes = array();
        $vt_condicoes[] = condicao_sql::montar('arquivo', '=', $nome_arquivo);
        $vt_condicoes[] = condicao_sql::montar('modulo', '=', $modulo);
        $condicoes = condicao_sql::sql_and($vt_condicoes);

        $obj = new self();
        $obj->consultar_condicoes($condicoes, $campos);
        return $obj;
    }


    //
    //     Retorna o arquivo INI com os dados dos arquivos
    //
    public function get_ini() {
        $data = strftime('%d/%m/%Y');
        $ini = <<<INI
;
; SIMP
; Descricao: Lista de Arquivos por Modulo
; Autor: simp
; Versao: 1.0.0.0
; Data: {$data}
; Modificado: {$data}
; License: LICENSE.TXT
;

INI;

        $modulos = array('simp') + listas::get_modulos();
        $ordem = array('arquivo' => true, 'descricao' => true);

        foreach ($modulos as $modulo) {
            $ini .= "[{$modulo}]\n";
            if ($modulo == 'simp') { $modulo = ''; }
            $condicao = condicao_sql::montar('modulo', '=', $modulo);
            $arquivos = $this->vetor_associativo('arquivo', 'descricao', $condicao, $ordem);
            if (!empty($arquivos)) {
                $maior = max(array_map('strlen', array_keys($arquivos)));
                foreach ($arquivos as $arq => $desc) {
                    $desc = addslashes($desc);
                    $ini .= sprintf("%-{$maior}s = \"%s\"\n", $arq, $desc);
                }
            } else {
                $ini .= "; nenhum arquivo\n";
            }
            $ini .= "\n";
        }
        return $ini;
    }

}//class
