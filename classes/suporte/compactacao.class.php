<?php
//
// SIMP
// Descricao: Classe de compactacao de arquivos
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.10
// Data: 04/10/2007
// Modificado: 04/10/2012
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
define('COMPACTACAO_TMP_DIR',   $CFG->dirarquivos.'simp_zip/');
define('COMPACTACAO_BIN_ZIP',   '/usr/bin/zip');
define('COMPACTACAO_BIN_UNZIP', '/usr/bin/unzip');

final class compactacao {


    //
    //     Construtor privado: utilize os metodos estaticos
    //
    private function __construct() {}


    //
    //     Compacta um conteudo e envia um header, caso seja possivel
    //
    static public function header(&$conteudo) {
    // String $conteudo: conteudo a ser compactado
    //
        $aceitos = $_SERVER['HTTP_ACCEPT_ENCODING'];
        if (function_exists('gzencode') && strpos($aceitos, 'gzip') !== false) {
            $conteudo = gzencode($conteudo);
            header('Content-encoding: gzip');
            header('Content-length: '.strlen($conteudo));
        } elseif (function_exists('gzdeflate') && strpos($aceitos, 'deflate') !== false) {
            $conteudo = gzdeflate($conteudo);
            header('Content-encoding: deflate');
            header('Content-length: '.strlen($conteudo));
        }
    }



    //
    //    Compacta um arquivo
    //
    static public function compactar($arquivos, $arquivo_zipado, $exibir = true) {
    // Array[String => String] $arquivos: vetor associativo com nome do arquivo e conteudo
    // String $arquivo_zipado: nome do arquivo zipado
    // Bool $exibir: exibir os dados ou apenas retornar
    //
        if (!is_dir(COMPACTACAO_TMP_DIR)) {
            if (!util::criar_diretorio_recursivo(COMPACTACAO_TMP_DIR, 0700)) {
                return false;
            }
        }

        // Tenta criar a pasta para manipular os arquivos zip
        $dir = tempnam(COMPACTACAO_TMP_DIR, 'dirzip');
        unlink($dir);
        if (!util::criar_diretorio_recursivo($dir, 0700)) {
            return false;
        }

        // Tenta entrar no diretorio
        if (!chdir($dir)) {
            return false;
        }

        // Se possui a extensao zip
        if (extension_loaded('zip')) {
            $zip = new ZipArchive();
            if ($zip->open($arquivo_zipado, ZIPARCHIVE::CREATE) !== true) {
                trigger_error('Erro ao criar arquivo zipado', E_USER_WARNING);
                return false;
            }

            foreach ($arquivos as $arquivo => $conteudo) {
                $zip->addFromString($arquivo, $conteudo);
            }
            $zip->close();

            // Obtem o conteudo do arquivo zipado
            $conteudo_zipado = file_get_contents($dir.'/'.$arquivo_zipado);

            // Remove o diretorio temporario
            util::remover_diretorio_recursivo($dir);

        // Se nao possui a extensao zip
        } else {

            // Tenta gerar os arquivos
            $arquivos_gerados = array();
            foreach ($arquivos as $arquivo => $dados) {
                if (!file_put_contents($dir.'/'.$arquivo, $dados)) {
                    return false;
                }
                $arquivos_gerados[] = escapeshellarg($arquivo);
            }

            // Tenta zipar o arquivo
            $cmd = COMPACTACAO_BIN_ZIP.' '.escapeshellarg($arquivo_zipado).' '.implode(' ', $arquivos_gerados);
            exec($cmd, $output, $status);
            if ($status != 0) {

                // Remove o diretorio temporario
                util::remover_diretorio_recursivo($dir);
                return false;
            }

            // Obtem o conteudo do arquivo zipado
            $conteudo_zipado = file_get_contents($dir.'/'.$arquivo_zipado);

            // Remove o diretorio temporario
            util::remover_diretorio_recursivo($dir);
        }

        // Checa se o conteudo foi recuperado
        if (!$conteudo_zipado) {
            return false;
        }

        // Retorna ou exibe o conteudo zipado
        if ($exibir) {
            $opcoes_http = array(
                'arquivo' => $arquivo_zipado,
                'disposition' => 'attachment'
            );
            http::cabecalho('application/zip', $opcoes_http);
            echo $conteudo_zipado;
            exit(0);
        }
        return $conteudo_zipado;
    }


    //
    //    Descompacta um arquivo retornando um vetor com os nomes e conteudos dos arquivos compactados
    //
    static public function descompactar($arquivo_zipado) {
    // String $arquivo_zipado: conteudo do arquivo zipado
    //
        // Tenta criar a pasta para manipular os arquivos zip
        $dir = COMPACTACAO_TMP_DIR.'/zipdir'.md5(microtime());
        if (!mkdir($dir)) {
            trigger_error('Erro ao criar diretorio', E_USER_WARNING);
            return false;
        }

        // Tenta entrar no diretorio
        if (!chdir($dir)) {
            trigger_error('Erro ao entrar diretorio', E_USER_WARNING);
            return false;
        }

        // Tenta gerar o arquivo zipado
        $nome_arquivo = basename(tempnam($dir, 'zip'));
        if (!file_put_contents($dir.'/'.$nome_arquivo, $arquivo_zipado)) {
            trigger_error('Erro ao gerar arquivo zipado', E_USER_WARNING);
            util::remover_diretorio_recursivo($dir);
            return false;
        }

        // Tenta descompactar o arquivo

        // Se possui a extensao zip
        if (extension_loaded('zip')) {
            $zip = new ZipArchive();
            if ($zip->open($dir.'/'.$nome_arquivo) !== true) {
                trigger_error('Erro ao abrir arquivo zip', E_USER_WARNING);
                return false;
            }
            $zip->extractTo($dir);
            unlink($dir.'/'.$nome_arquivo);

        // Se nao possui a extensao zip
        } else {
            $cmd = COMPACTACAO_BIN_UNZIP.' '.escapeshellarg($nome_arquivo);
            exec($cmd, $output, $status);
            unlink($dir.'/'.$nome_arquivo);
            if ($status != 0) {

                // Remove o diretorio temporario
                util::remover_diretorio_recursivo($dir);
                return false;
            }
        }

        // Obtem o conteudo dos arquivos obtidos
        $arquivos = array();
        self::obter_arquivos($dir, $arquivos);

        // Remove o diretorio temporario
        util::remover_diretorio_recursivo($dir);

        return $arquivos;
    }


    //
    //     Obtem o conteudo dos arquivos encontrados em um diretorio
    //
    private static function obter_arquivos($nome_dir, &$arquivos) {
    // String $nome_dir: nome do diretorio
    // Array[String => String] $arquivos: vetor de nome e conteudo dos arquivos
    //
        $dir = opendir($nome_dir);
        if (!$dir) {
            return false;
        }
        while (($i = readdir($dir)) !== false) {
            if ($i == '.' || $i == '..') { continue; }
            if (is_dir($nome.'/'.$i)) {
                self::obter_arquivos($nome_dir.'/'.$i, $arquivos);
            } else {
                $conteudo = file_get_contents($nome_dir.'/'.$i);
                if (!$conteudo) {
                    $arquivos = false;
                    closedir($dir);
                    return;
                }
                $arquivos[$nome_dir.'/'.$i] = $conteudo;
            }
        }
        closedir($nome_dir);
    }

}//class
