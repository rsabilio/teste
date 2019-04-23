<?php
//
// SIMP
// Descricao: Classe que controla dados armazenados em cache de arquivo
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.2.3
// Data: 12/08/2010
// Modificado: 19/07/2011
// Copyright (C) 2010  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Constantes
define('CACHE_ARQUIVO_DIRETORIO', $CFG->dirarquivos.'/simp_cache4/');

final class cache_arquivo {
    const MAX_VALORES = 100;        // Maximo de arquivos de dados (100)
    const MAX_TAMANHO = 52428800;   // Tamanho maximo de todos arquivos de cache (50M)


    //
    //     Construtor privado: utilize os metodos estaticos
    //
    private function __construct() {}


    //
    //     Verifica se um determinado valor esta em cache
    //
    static public function em_cache($id) {
    // String $id: identificador do valor em cache
    //
        global $CFG;

        $uid = self::get_id_usuario();
        if ($uid === false) {
            return false;
        }

        $id = self::filtrar_id($id);
        $dir = self::get_diretorio($id);

        if (!is_dir($dir)) {
            return false;
        }
        $dados = self::get_dados_id($id);

        // Limpar caso seja de outra versao
        if ($dados['versao'] != $CFG->versao) {
            self::remover_id($id);
            return false;
        }

        // Limpar caso expirou o tempo
        $agora = time();
        if ($dados['expira'] && $dados['expira'] < $agora) {
            self::remover_id($id);
            return false;
        }

        return true;
    }


    //
    //     Obtem um valor da cache
    //
    static public function get_valor($id) {
    // String $id: identificador do valor em cache
    //
        $id = self::filtrar_id($id);
        if (!self::em_cache($id)) {
            return false;
        }
        $valor = self::get_valor_id($id);
        return $valor;
    }


    //
    //     Obtem os itens ordenados por data de inicio
    //
    static private function get_itens() {
        $uid = self::get_id_usuario();
        $dir = CACHE_ARQUIVO_DIRETORIO.$uid.'/';

        //chmod($dir, 0700); // rwx
        if (is_readable($dir)) {
            $itens = scandir($dir);
        } else {
            trigger_error('Erro ao ler conteudo do diretorio "'.$dir.'" (nao foi possivel colocar permissao de leitura)', E_USER_WARNING);
            return false;
        }
        //chmod($dir, 0300); // -wx

        $vetor = array();
        $tempos = array();

        foreach ($itens as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $dados = self::get_dados_id($item);

            $vetor[$item]  = $dados;
            $tempos[$item] = $dados['inicio'];
        }
        array_multisort($tempos, SORT_ASC, SORT_NUMERIC, $vetor);
        return $vetor;
    }


    //
    //     Armazena um valor na cache
    //
    static public function set_valor($id, $valor, $tempo = null, $compacto = true) {
    // String $id: identificador do valor em cache
    // Mixed $valor: valor a ser armazenado em cache (nao utilize resource)
    // Int $tempo: tempo maximo em que o valor e' valido ou null para indefinido
    // Bool $compacto: armazenar de forma compactada
    //
        global $CFG;
        $uid = self::get_id_usuario();
        if ($uid === false) {
            return false;
        }

        $id = self::filtrar_id($id);
        $dir = self::get_diretorio($id);

        // Criar diretorio caso nao exista
        if (!is_dir($dir)) {
            $primeiro = !is_dir(CACHE_ARQUIVO_DIRETORIO);
            util::criar_diretorio_recursivo($dir, 0777); // rwx
        }

        // Gerar valor
        $valor_arquivo = self::codificar_conteudo($valor, $compacto);
        if ($compacto) {
            $compacto = function_exists('gzdeflate') && function_exists('gzinflate');
        }
        $tamanho = strlen($valor_arquivo);

        // Se o arquivo e' maior que o maximo permitido
        if (self::MAX_TAMANHO && $tamanho > self::MAX_TAMANHO) {
            return false;
        }

        // Gerar os dados
        $agora = time();
        if ($tempo === null) {
            $dados = array(
                'dir'      => $dir,
                'tamanho'  => $tamanho,
                'inicio'   => $agora,
                'expira'   => null,
                'versao'   => $CFG->versao,
                'compacto' => $compacto
            );
        } else {
            $dados = array(
                'dir'      => $dir,
                'tamanho'  => $tamanho,
                'inicio'   => $agora,
                'expira'   => $agora + $tempo,
                'versao'   => $CFG->versao,
                'compacto' => $compacto
            );
        }
        $dados_arquivo = self::codificar_conteudo($dados);

        // Salvar valores em arquivo
        $r = self::set_conteudo_arquivo($dir.'/c', $valor_arquivo) &&
             self::set_conteudo_arquivo($dir.'/d', $dados_arquivo);

        // Se nao salvou
        if (!$r) {
            util::remover_diretorio_recursivo($dir);
            return false;
        }

        $itens = self::get_itens();

        // Apagar o primeiro se passou o numero maximo de itens
        if (self::MAX_VALORES) {
            $total = count($itens);
            while ($total > self::MAX_VALORES) {
                $total -= 1;
                $primeiro = array_shift($itens);
                util::remover_diretorio_recursivo($primeiro['dir']);
            }
        }

        // Apagar os primeiros se passou o tamanho
        if (self::MAX_TAMANHO) {
            $tamanhos = array();
            foreach ($itens as $item) {
                $tamanhos[] = $item['tamanho'];
            }
            $tamanho_total = array_sum($tamanhos);

            while ($tamanho_total > self::MAX_TAMANHO) {
                $tamanho = array_shift($tamanhos);
                $tamanho_total -= $tamanho;

                $item = array_shift($itens);
                util::remover_diretorio_recursivo($item['dir']);
            }
        }
        return true;
    }


    //
    //     Apaga um registro da cache pelo ID
    //
    static public function apagar_id($id) {
    // String $id: identificador unico
    //
        $id = self::filtrar_id($id);
        return self::remover_id($id);
    }


    //
    //     Limpa os valores da cache
    //
    static public function limpar() {
        $itens = self::get_itens();
        $r = true;
        foreach ($itens as $id => $item) {
            $r = $r && self::remover_id($id);
        }
        return $r;
    }


    //
    //     Obtem o identificador do dado armazenado em cache
    //
    static public function get_id() {
        $backtrace = debug_backtrace(0, 1);
        $chamada = array_shift($backtrace);
        return sprintf('%s%05d', md5($chamada['file']), $chamada['line']);
    }


    //
    //     Filtra o identificador para evitar falhas de seguranca
    //
    static private function filtrar_id($id) {
    // String $id: identificador
    //
        return basename($id);
    }


    //
    //     Obtem o conteudo do arquivo
    //
    static private function get_conteudo_arquivo($arquivo) {
    // String $arquivo: arquivo a ser lido
    //
        //chmod($arquivo, 0400); // r--
        if (is_readable($arquivo)) {
            $conteudo = file_get_contents($arquivo);
        } else {
            trigger_error('Erro ao obter informacoes da cache de arquivos ('.$arquivo.') pois nao conseguiu colocar permissao de leitura', E_USER_WARNING);
            $conteudo = false;
        }
        //chmod($arquivo, 0000); // ---
        return $conteudo;
    }


    //
    //    Salva o conteudo em um arquivo
    //
    static private function set_conteudo_arquivo($arquivo, $conteudo) {
    // String $arquivo: nome do arquivo
    // String $conteudo: conteudo do arquivo
    //
        if (is_file($arquivo)) {
            //chmod($arquivo, 0600); // rw-
            if (!is_writable($arquivo)) {
                trigger_error('Erro ao gravar conteudo na cache de arquivos ('.$arquivo.') pois nao conseguiu colocar permissao de escrita', E_USER_WARNING);
                return false;
            }
        }
        $r = file_put_contents($arquivo, $conteudo);
        //chmod($arquivo, 0000); // ---
        return $r;
    }


    //
    //     Codifica os dados (retorna string)
    //
    static private function codificar_conteudo($conteudo, $compacto = false) {
    // Mixed $conteudo: conteudo a ser codificado na forma de string
    // Bool $compacto: codifica de forma compactada
    //
        if ($compacto) {
            $conteudo = serialize($conteudo);
            $conteudo = '1'.gzdeflate($conteudo);
        } else {
            $conteudo = '0'.serialize($conteudo);
        }
        return $conteudo;
    }


    //
    //     Decodifica os dados
    //
    static private function decodificar_conteudo($conteudo) {
    // String $conteudo: conteudo codificado na forma de string
    //
        $compacto = (bool)substr($conteudo, 0, 1);
        $conteudo = substr($conteudo, 1);
        if ($compacto) {
            $conteudo = gzinflate($conteudo);
            $conteudo = unserialize($conteudo);
        } else {
            $conteudo = unserialize($conteudo);
        }
        return $conteudo;
    }


    //
    //     Tenta obter o UID do usuario para evitar problemas de permissoes
    //
    static private function get_id_usuario() {
        static $uid = null;
        if ($uid === null) {
            if (extension_loaded('posix')) {
                $uid = texto::numero(posix_getuid(), 0, true, 'C');
            } else {
                $uid = trim(shell_exec('echo $UID'));
                if (!$uid) {
                    $uid = trim(shell_exec('whoami'));
                    if (!$uid) {
                        return false;
                    }
                }
            }
        }
        return $uid;
    }


    //
    //     Obtem o diretorio onde ficam os arquivos em cache
    //
    static private function get_diretorio($id) {
    // String $id: identificador do valor em cache
    //
        $uid = self::get_id_usuario();
        return CACHE_ARQUIVO_DIRETORIO.$uid.'/'.$id;
    }


    //
    //     Obtem os dados de um ID
    //
    static private function get_dados_id($id) {
    // String $id: identificador do valor em cache
    //
        $dir = self::get_diretorio($id);
        $conteudo = self::get_conteudo_arquivo($dir.'/d');
        return self::decodificar_conteudo($conteudo);
    }


    //
    //     Obtem o valor de um ID
    //
    static private function get_valor_id($id) {
    // String $id: identificador do valor em cache
    //
        $dir = self::get_diretorio($id);
        $conteudo = self::get_conteudo_arquivo($dir.'/c');
        return self::decodificar_conteudo($conteudo);
    }


    //
    //     Remove os dados do ID informado
    //
    static private function remover_id($id) {
    // String $id: identificador do valor em cache
    //
        $dir = self::get_diretorio($id);
        return util::remover_diretorio_recursivo($dir);
    }

}//class
