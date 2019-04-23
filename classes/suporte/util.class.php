<?php
//
// SIMP
// Descricao: Classe com metodos uteis
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.1.46
// Data: 22/08/2007
// Modificado: 15/07/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Tipos de filtragem de vetores
define('UTIL_PRIMEIRO', 1);
define('UTIL_ULTIMO',   2);

// Tipos de exibicao de variaveis
define('UTIL_EXIBIR_PADRAO',  1); // Valor
define('UTIL_EXIBIR_NATURAL', 2); // Valor compativel com localidade
define('UTIL_EXIBIR_PHP',     3); // Codigo PHP
define('UTIL_EXIBIR_TEXTO',   4); // Valor para arquivos CSV e XML (texto)

final class util {


    //
    //     Construtor privado: utilize os metodos estaticos
    //
    private function __construct() {}


    //
    //     Obtem o nome do modulo
    //
    static public function get_modulo($arq, $nome_completo = true) {
    // String $arq: nome completo do arquivo que esta' dentro do modulo
    // Bool $nome_completo: obter nome completo do modulo ou apenas o nome relativo
    //
        trigger_error('Metodo depreciado, use modulo::get_modulo', E_USER_DEPRECATED);
        return modulo::get_modulo($arq, $nome_completo);
    }


    //
    //     Obtem o nome do modulo pai
    //
    static public function get_modulo_pai($modulo) {
    // String $modulo: nome do modulo
    //
        trigger_error('Metodo depreciado, use modulo::get_modulo_pai', E_USER_DEPRECATED);
        return modulo::get_modulo_pai($modulo);
    }


    //
    //     Consulta uma entidade de acordo com o parametro recebido por GET
    //
    static public function get_entidade($classe, $campos = false, $nome_codigo = false, $tipo = false, $obrigatorio = true, $padrao = false) {
    // String $classe: nome da classe da entidade a ser consultada
    // Array[String] $campos: vetor de campos que se deseja consultar
    // String $nome_codigo: nome da chave usada para consulta
    // String $tipo: nome do tipo de parametro a ser rebecido
    // Bool $obrigatorio: indica se o campo a ser consultado e' obrigatorio ou nao
    // Mixed $padrao: Valor padrao da chave da consulta caso o campo nao seja obrigatorio
    //
        if (isset($GLOBALS['USUARIO'])) {
            $USUARIO = &$GLOBALS['USUARIO'];
        } else {
            $USUARIO = false;
        }
        $obj = objeto::get_objeto($classe);
        if ($obj->singleton() && $obj->existe()) {
            return $obj;
        }

        // Se nao informou o codigo, tentar consultar a chave primaria
        if (!$nome_codigo) {
            $nome_codigo = $obj->get_chave();
            $tipo = false;
        }

        // Se nao informou o tipo, tentar recupera-lo do objeto
        if (!$tipo) {
            $def  = $obj->get_definicao_atributo($nome_codigo);
            $tipo = $def->tipo;
        }

        // Consultar parametro recebido por GET
        $codigo = util::get_dado($nome_codigo, $tipo, $obrigatorio, $padrao);

        if (!$obj->consultar($nome_codigo, $codigo, $campos)) {
            $erros = array();
            $erros[] = "Erro ao consultar entidade ".$obj->get_entidade()." ({$nome_codigo} = {$codigo})";
            if (!DEVEL_BLOQUEADO) {
                $erros[] = $obj->get_erros();
            }
            pagina::erro($USUARIO, $erros, 'Erro');
            exit(1);
        }
        return $obj;
    }


    //
    //     Recupera um dado passado por GET ou POST
    //
    static public function get_dado($nome, $tipo = false, $obrigatoria = true, $padrao = null, $metodo = 'get', &$origem = false) {
    // String $nome: nome do campo a ser recuperado
    // String $tipo: tipo de filtro usado (int, float, binario, string, char, bool, array)
    // Bool $obrigatoria: o parametro e' obrigatorio para continuar a exibir a pagina
    // Mixed $padrao: valor padrao caso o parametro nao seja obrigatorio
    // String $metodo: metodo de recebimento dos dados (get, post ou false = qualquer um)
    // String $origem: obtem o metodo usado para recebimento (padrao, get, post)
    //
        $origem = false;

        // Se nao passou o parametro desejado por GET
        if (($metodo == 'get')  && (!isset($_GET[$nome])) or
            ($metodo == 'post') && (!isset($_POST[$nome])) or
            ($metodo == false)  && (!isset($_REQUEST[$nome]))) {

            // Se o campo era obrigatorio
            if ($obrigatoria) {
                mensagem::erro("Faltou informar o campo: {$nome}");
                exit(1);

            // Se existe um padrao, usa-lo
            } elseif (!is_null($padrao)) {
                $origem = 'padrao';
                return $padrao;

            // Se nao passou, nao tem padrao e nao e' obrigatorio...
            } else {
                return null;
            }
        }

        switch ($metodo) {
        case 'get':
            $dado = $_GET[$nome];
            $origem = 'get';
            break;
        case 'post':
            $dado = $_POST[$nome];
            $origem = 'post';
            break;
        default:
            if (isset($_POST[$nome])) {
                $dado = $_POST[$nome];
                $origem = 'post';
            } elseif (isset($_GET[$nome])) {
                $dado = $_GET[$nome];
                $origem = 'get';
            }
            break;
        }

        // Checar o tipo
        if ($tipo) {
            $dado = texto::strip_slashes_recursivo($dado);
            if (is_array($tipo)) {
                foreach ($tipo as $t) {
                    $f = self::filtrar_tipo($dado, $tipo);
                    if ($dado == $f) {
                        $dado = $f;
                        break;
                    }
                }
            } else {
                $dado = self::filtrar_tipo($dado, $tipo);
            }
        }
        return $dado;
    }


    //
    //     Filtra um valor
    //
    static public function filtrar_tipo($dado, $tipo) {
    // Mixed $dado: valor desejado
    // String $tipo: nome do tipo (int, float, binario, string, char, bool, array)
    //
        switch ($tipo) {
        case 'int':
            if ($dado > PHP_INT_MAX) {
                $dado = round($dado); // intval() so' aceita valores ate' PHP_INT_MAX
            } else {
                $dado = intval($dado);
            }
            break;
        case 'float':
        case 'double':
            $dado = floatval($dado);
            break;
        case 'binario':
        case 'string':
            $dado = strval($dado);
            break;
        case 'char':
            $s = strval($dado);
            $dado = $s[0];
            break;
        case 'bool':
            $dado = $dado ? 1 : 0;
            break;
        case 'array':
            $dado = (array)$dado;
            break;
        }
        return $dado;
    }


    //
    //     Cria um objeto com os campos e dados informados
    //
    static public function objeto($campos, $dados = null) {
    // Array[String => String] || Array[String] $campos: vetor com os campos desejados no objeto
    // Object $dados: objeto usado como base para criar o outro
    //
        $obj = new stdClass();
        foreach ($campos as $campo => $valor) {

            // Vetor simples
            if (is_numeric($campo)) {
                $obj->$valor = isset($dados->$valor) ? $dados->$valor : '';

            // Vetor associativo
            } else {
                $obj->$campo = isset($dados->$campo) ? $dados->$campo : $valor;
            }
        }
        return $obj;
    }


    //
    //     Monta um objeto com os dados previstos no vetor de valores padrao
    //
    static public function montar_objeto($padrao, $objeto) {
    // Array[String => Mixed] $padrao: valores padrao
    // stdClass $objeto: objeto com dados
    //
        // Objeto retornado
        $dados = new stdClass();

        // Se o padrao nao e' um array
        if (!is_array($padrao)) {
            if (is_object($padrao) && $padrao instanceof stdClass) {
                $padrao = (array)$padrao;
            } else {
                trigger_error('Tipo invalido para o parametro $padrao ('.gettype($padrao).')', E_USER_ERROR);
            }
        }

        // Se o array esta' vazio
        if (empty($padrao)) {
            return null;
        }

        // Para cada chave desejada: checar se existe o valor
        foreach ($padrao as $chave => $valor_padrao) {
            if (isset($objeto->$chave)) {
                $valor = $objeto->$chave;
            } else {
                $valor = null;
            }

            // Esperado um vetor
            if (is_array($valor_padrao)) {
                if (is_array($valor)) {
                    $dados->$chave = $valor;
                } elseif (is_object($valor) && $valor instanceof stdClass) {
                    $dados->$chave = (array)$valor;
                } else {
                    $dados->$chave = is_null($valor) ? $valor_padrao : $valor;
                }

            // Esperado um valor (nao vetor)
            } else {
                $dados->$chave = is_null($valor) ? $valor_padrao : $valor;
            }
        }
        return $dados;
    }


    //
    //     Cria um objeto com varios niveis
    //
    static public function definir_atributo_nivel(&$obj, $atributo, $valor) {
    // stdClass $obj: objeto a ser modificado
    // String || Array[String] $atributo: nome do atributo ou vetor com o caminho ate' o do atributo
    // Mixed $valor: valor a ser definido
    //
        if (!($obj instanceof stdClass)) {
            $obj = new stdClass();
        }
        if (is_string($atributo)) {
            $obj->$atributo = $valor;
        } elseif (is_array($atributo)) {
            $count = count($atributo);
            if (!$count) {
                return false;
            }
            $a = array_shift($atributo);
            $count -= 1;
            if (!property_exists($obj, $a) || !($obj->$a instanceof stdClass)) {
                $obj->$a = new stdClass();
            }
            if ($count) {
                return self::definir_atributo_nivel($obj->$a, $atributo, $valor);
            } else {
                $obj->$a = $valor;
                return true;
            }
        }
        return false;
    }


    //
    //     Cria um vetor com varios niveis
    //
    static public function definir_vetor_nivel(&$vetor, $posicao, $valor) {
    // Array[Mixed] $vetor: vetor a ser modificado
    // Int || String || Array[String] $posicao: nome do vetor ou vetor com o caminho ate' a posicao
    // Mixed $valor: valor a ser definido
    //
        if (!is_array($vetor)) {
            $vetor = array();
        }
        if (is_scalar($posicao)) {
            $vetor[$atributo] = $valor;
        } elseif (is_array($posicao)) {
            $count = count($posicao);
            if (!$count) {
                return false;
            }
            $p = array_shift($posicao);
            $count -= 1;

            if ($p === null) {
                $vetor[] = $valor;
                return true;
            } else {
                if (!array_key_exists($p, $vetor) || !is_array($vetor[$p])) {
                    $vetor[$p] = array();
                }
                if ($count) {
                    return self::definir_vetor_nivel($vetor[$p], $posicao, $valor);
                } else {
                    $vetor[$p] = $valor;
                    return true;
                }
            }
        }
        return false;
    }


    //
    //     Retorna o erro de upload na forma textual
    //
    static public function erro_upload($cod_erro) {
    // Int $cod_erro: codigo do erro
    //
        trigger_error('Metodo depreciado, use formulario::get_erro_upload', E_USER_DEPRECATED);
        return formulario::get_erro_upload($cod_erro);
    }


    //
    //     Gera um bloco CDATA caso o arquivo seja XML
    //
    static public function get_cdata(&$cdata, &$fcdata, $comentario = true) {
    // String $cdata: tag para abrir o bloco de CDATA
    // String $fcdata: tag para fechar o bloco de CDATA
    // Bool $comentario: usar comentario caso nao seja um XML
    //
        global $CFG;
        if (isset($CFG->usa_cdata) && $CFG->usa_cdata) {
            $cdata  = '<![CDATA[';
            $fcdata = ']]>';
        } elseif ($comentario) {
            $cdata = '<!--';
            $fcdata = '-->';
        } else {
            $cdata  = '';
            $fcdata = '';
        }
    }


    //
    //     Filtra elementos cujos campos especificados sao repetidos
    //
    static public function filtrar($vetor, $campos_gerados, $campos_unicos, $preferencia = UTIL_PRIMEIRO) {
    // Array[Mixed => Object] $vetor: vetor de objetos a serem filtrados
    // Array[String] $campos_gerados: campos que devem ser gerados nos objetos do vetor retornado
    // Array[String] $campos_unicos: campos que devem ser unicos para montar o vetor
    // Int $preferencia: prioridade utilizada para obter os dados do vetor (UTIL_PRIMEIRO ou UTIL_ULTIMO)
    //
        trigger_error('Metodo depreciado, use vetor::filtrar_atributos_repetidos', E_USER_DEPRECATED);
        return vetor::filtrar_atributos_repetidos($vetor, $campos_unicos, $preferencia, $campos_gerados);
    }


    //
    //     Obtem os erros de sintaxe de um arquivo
    //
    public static function erros_sintaxe($arq, &$erros) {
    // String $arq: nome completo do arquivo PHP a ser checado
    // Array[String] $erros: erros encontrados
    //

//TODO classe simp_debug
        static $tem_cli = 0;
        $retorno = false;
        $erros = array();

        // Versoes antigas do PHP
        if (function_exists('php_check_syntax')) {
            $retorno = php_check_syntax($arq, $vt_erros);
            if ($retorno) {
                $erros = explode("\n", $vt_erros);
            }

        // Versoes novas do PHP
        } else {

            // Se ainda nao checou: checar se tem o PHP CLI
            if (is_int($tem_cli)) {

                // Checar se tem o PHP CLI atraves de "php-config --php-sapis"
                $vt_saida = array();
                exec('php-config --php-sapis', $linhas_saida, $retorno_cli);
                if ($retorno_cli == 0) {
                    $saida = implode("\n", $linhas_saida);
                    $vt_saida = explode(' ', $saida);

                // Tentar checar se tem o PHP CLI atraves de "php --version"
                } else {
                    exec('php --version', $linhas_saida, $retorno_cli);
                    if ($retorno_cli == 0) {
                        $tr = array('(' => ' ', ')' => ' ');
                        $vt_saida = explode(' ', strtr(implode(' ', $linhas_saida), $tr));
                    } else {
                        $tem_cli = false;
                    }
                }
                $tem_cli = (bool)((int)$retorno_cli == 0 && in_array('cli', $vt_saida));
            }
            if ($tem_cli) {

                // -l = lint (checagem de sintaxe)
                // -n = nao usar arquivos php.ini (evitar que clientes com PHP-GTK nao consigam usar isso)
                exec('php -nl '.escapeshellarg($arq), $erros, $retorno);
            }
        }
        if ($erros) {
            $erros_aux = $erros;
            $erros = array();
            foreach ($erros_aux as $e) {
                $e_trim = trim($e);
                if ($e !== '') {
                    $erros[] = $e_trim;
                }
            }
        }
        return $retorno;
    }


    //
    //     Checa se a classe possui um loop entre relacionamentos 1:1 fortes
    //
    static public function possui_loop_relacionamento($classe, &$loop) {
    // String $classe: nome da classe
    // String $loop: indicacao do loop encontrado
    //
        simp_autoload($classe);

//TODO classe simp_debug

        try {
            $rc = new ReflectionClass($classe);
        } catch (Exception $e) {
            return false;
        }
        if (!$rc->isInstantiable() || !$rc->isSubclassOf('objeto')) {
            return false;
        }
        $vetor = array();
        return self::possui_loop_relacionamento_rec($classe, $vetor, $loop);
    }


    //
    //     Recursao do metodo possui_loop_relacionamento
    //
    static private function possui_loop_relacionamento_rec($classe, $vetor, &$loop) {
    // String $classe: nome da classe
    // Array[String] $vetor: vetor com o caminho das classes relacionadas
    // String $loop: indicacao do loop encontrado
    //

//TODO classe simp_debug

        $obj = new $classe();
        $relacionamentos = $obj->get_definicoes_rel_uu();
        foreach ($relacionamentos as $def) {
            if (!$def->forte) { continue; }
            $vetor2 = array_merge($vetor, array($def->classe));
            if (in_array($def->classe, $vetor)) {
                if ($vetor[0] != $def->classe) {
                    $pos = array_search($def->classe, $vetor2);
                    $loop = implode(' &rarr; ', array_splice($vetor2, $pos));
                } else {
                    $loop = implode(' &rarr; ', $vetor2);
                }
                return true;
            }
            if (self::possui_loop_relacionamento_rec($def->classe, $vetor2, $loop)) {
                return true;
            }
        }
        return false;
    }


    //
    //     Retorna o dia (1-31), mes (0-11) e ano (int) de um timestamp na forma de inteiros
    //
    static public function get_data_completa($time = false) {
    // Int $time: timestamp desejado
    //
        if (!$time) {
            $time = time();
        }
        $dia = (int)strftime('%d', $time);
        $mes = (int)strftime('%m', $time);
        $ano = (int)strftime('%Y', $time);
        return array($dia, $mes, $ano);
    }


    //
    //     Retorna a hora (0-23), minuto (0-59) e segundo (0-59) de um timestamp na forma de inteiros
    //
    static public function get_hora_completa($time = false) {
    // Int $time: timestamp desejado
    //
        if (!$time) {
            $time = time();
        }
        $hora    = (int)strftime('%H', $time);
        $minuto  = (int)strftime('%M', $time);
        $segundo = (int)strftime('%S', $time);
        return array($hora, $minuto, $segundo);
    }


    //
    //     Cria o diretorio de arquivos de um modulo
    //
    static public function criar_diretorio_recursos($diretorio, $permissao = 0700) {
    // String $diretorio: nome do diretorio a ser criado no diretorio de recursos
    // Int $permissao: permissao a ser definida
    //
        global $CFG;
        $nome = $CFG->dirarquivos.$diretorio;
        return self::criar_diretorio_recursivo($nome, $permissao);
    }


    //
    //     Cria um diretorio recursivamente
    //
    static public function criar_diretorio_recursivo($diretorio, $permissao = 0700) {
    // String $diretorio: nome do diretorio a ser criado
    // Int $permissao: permissao a ser definida
    //
        clearstatcache();
        $nome = texto::strip_char_adjacente($diretorio, DIRECTORY_SEPARATOR);
        if (file_exists($nome)) {
            return true;
        }

        // Buscar ate' que ponto do caminho ja' existe diretorio criado
        $vt_nome = explode(DIRECTORY_SEPARATOR, $nome);
        $buf = array_shift($vt_nome).DIRECTORY_SEPARATOR;
        $count = count($vt_nome);
        for ($i = 0; $i < $count; $i++) {
            if ($vt_nome[$i] === '') {
                continue;
            }
            $nome_parcial = $buf.DIRECTORY_SEPARATOR.$vt_nome[$i];
            if (!file_exists($nome_parcial)) {
                break;

            // Se o caminho existe, mas nao e' diretorio ou nao pode entrar nele
            } elseif (!is_executable($nome_parcial) || !is_dir($nome_parcial)) {
                return false;
            }
            $buf .= DIRECTORY_SEPARATOR.$vt_nome[$i];
        }

        // Criar os diretorios que estao faltando
        $vt_diretorios = array();
        $buf2 = $buf;
        for ($j = $i; $j < $count; $j++) {
            if ($vt_nome[$j] === '') {
                continue;
            }
            $novo_dir = $buf2.DIRECTORY_SEPARATOR.$vt_nome[$j];
            if (mkdir($novo_dir)) {
                array_unshift($vt_diretorios, $novo_dir);
                chmod($novo_dir, 0700); // Colocar permissao total provisoriamente
            } else {
                return false;
            }
            $buf2 = $novo_dir;
        }

        // Colocar a permissao desejada
        foreach ($vt_diretorios as $dir) {
            chmod($dir, $permissao);
        }

        return true;
    }


    //
    //     Apaga um diretorio recursivamente
    //
    static public function remover_diretorio_recursivo($diretorio) {
    // String $diretorio: diretorio a ser removido
    //
        if (!is_dir($diretorio)) {
            return true;
        }
        if (!is_readable($diretorio) || !is_executable($diretorio)) {
            chmod($diretorio, 0700);
            if (!is_readable($diretorio) || !is_executable($diretorio)) {
                return false;
            }
        }
        $itens = scandir($diretorio);

        $r = true;
        foreach ($itens as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $item_completo = $diretorio.'/'.$item;
            if (is_file($item_completo) || is_link($item_completo)) {
                $r = $r && unlink($item_completo);
            } elseif (is_dir($item_completo)) {
                $r = $r && self::remover_diretorio_recursivo($item_completo);
            }
        }
        if (!$r) {
            return false;
        }
        return rmdir($diretorio);
    }


    //
    //     Retira elementos de uma matriz gerada pelo metodo vetor_associativo_hierarquico da classe objeto
    //
    public static function remover_elementos(&$vt_hierarquico, &$vt_remover) {
    // Array[String => Array[Mixed => String]] $vt_hierarquico: vetor hierarquico a ser filtrado
    // Array[Mixed => String] $vt_remover: vetor de valores a serem removidos
    //
        trigger_error('Metodo depreciado, use vetor::remover_elementos', E_USER_DEPRECATED);
        return vetor::remover_elementos($vt_hierarquico, $vt_remover);
    }


    //
    //     Var dump mais pratico
    //
    public static function dump(&$entrada, $notacao = 1) {
    // Mixed $entrada: entrada de dados
    // Int $notacao: codigo da notacao adotada (0 = print_r / 1 = var_dump / 2 = debug_zval_dump)
    //

//TODO colocar em simp_debug::dump
        ob_start();
        switch ($notacao) {
        default:
        case 0:
            print_r($entrada);
            break;
        case 1:
            var_dump($entrada);
            break;
        case 2:
            debug_zval_dump($entrada);
            break;
        }
        $conteudo = ob_get_contents();
        ob_end_clean();

        echo '<pre>'.texto::codificar($conteudo).'</pre>';
    }


    //
    //     Realiza um backtrace legivel (deve ser utilizado dentro de metodos)
    //
    public static function debug($incluir_ultima_chamada = true, $notacao = UTIL_EXIBIR_PHP) {
    // Bool $incluir_ultima_chamada: incluir a chamada ao metodo debug
    // Int $notacao: notacao dos parametros mostrados com util::exibir_var
    //

//TODO colocar em simp_debug::trace

        $backtrace = debug_backtrace(0);
        if (!empty($backtrace) && !$incluir_ultima_chamada) {
            array_shift($backtrace);
        }
        foreach ($backtrace as $chamada) {
            $str_chamada = '<div style="border: 1px solid red;">';
            if (isset($chamada['class'])) {
                if ($chamada['type'] == '::') {
                    $tipo = ' Est&aacute;tico';
                    $forma_chamada = $chamada['class'].'::'.$chamada['function'].'(';
                } else {
                    if (isset($chamada['object'])) {
                        $tipo = '';
                        $forma_chamada = '$'.get_class($chamada['object']).'-&gt;'.$chamada['function'].'(';
                    } else {
                        $tipo = '';
                        $forma_chamada = '$'.$chamada['class'].'-&gt;'.$chamada['function'].'(';
                    }
                }
                $str_chamada .= '<p>M&eacute;todo'.$tipo.': <code>'.$forma_chamada;
            } else {
                $str_chamada .= '<p>Fun&ccedil;&atilde;o: <code>'.$chamada['function'].'(';
            }
            if (isset($chamada['args']) && !empty($chamada['args'])) {
                $vt_args = array();
                foreach ($chamada['args'] as $arg) {
                    $vt_args[] = self::exibir_var($arg, $notacao);
                }
                $str_chamada .= implode(', ', $vt_args);
            } else {
                $str_chamada .= 'void';
            }
            $str_chamada .= ')</code></p>';
            if (isset($chamada['file']) && isset($chamada['line'])) {
                $str_chamada .= '<p><small>Arquivo: '.$chamada['file'].' / Linha '.$chamada['line'].'</small></p>';
            }
            $str_chamada .= '</div>';

            echo $str_chamada;
        }
        echo '<hr />';
    }


    //
    //     Retorna o nome do script pai (que invocou alguma funcao ate chegar ao ponto corrente de execucao)
    //
    static public function get_arquivo() {
        static $arquivo = false;
        if ($arquivo !== false) {
            return $arquivo;
        }
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            $arquivo = $_SERVER['SCRIPT_FILENAME'];
        } else {
            $backtrace = debug_backtrace(0);
            $ultimo = array_pop($backtrace);
            if (isset($ultimo['file'])) {
                $arquivo = $ultimo['file'];
            }
        }
        return $arquivo;
    }


    //
    //     Retorna o tamanho medio da fila de processos do ultimo minuto (system load average)
    //
    static public function get_load_avg() {
        static $avg = null;

        if ($avg === null) {

            // Checar se tem em cache
            $id = sprintf('%s%05d', md5(__FILE__), __LINE__);
            if (cache_arquivo::em_cache($id)) {
                $avg = cache_arquivo::get_valor($id);

            // Obter valor
            } else {
                if (function_exists('sys_getloadavg')) {
                    $vt = sys_getloadavg();
                    $avg = $vt[0];
                } elseif (strtolower(PHP_OS) == 'linux') {
                    exec('uptime', $linhas, $retorno);
                    if ($retorno == 0) {
                        $linha = array_shift($linhas);
                        $str = 'load average:';
                        $pos = strpos($linha, $str) + strlen($str);
                        $tempos = trim(substr($linha, $pos));
                        $vt = explode(',', $tempos);
                        $avg = (float)trim($vt[0]);
                    }
                } else {
                    $avg = 0.0;
                }
                cache_arquivo::set_valor($id, $avg, 60, false);
            }
        }
        return $avg;
    }


    //
    //     Tenta retornar o tempo em que o servidor esta' ativo
    //
    static public function get_uptime() {
        if (strtolower(PHP_OS) == 'linux') {
            exec('uptime', $linhas, $retorno);
            if ($retorno == 0) {
                $linha = array_shift($linhas);

                // Ex.: 09:13:37 up 7 days,  2:08,  3 users,  load average: 0.00, 0.03, 0.00
                //      13:14:06 up 2 days, 4 min,  1 user,  load average: 0.24, 0.27, 0.14
                $exp = '/^(.+)[\040]*up[\040]*(.+),[\040]*([0-9]+)[\040]*user[s]?[\040]*,[\040]*(.+)$/';
                if (preg_match($exp, $linha, $match)) {
                    $tabela = array(
                        'days'    => 'dias',
                        'day'     => 'dia',
                        'hours'   => 'horas',
                        'hour'    => 'hora',
                        'min'     => 'minuto(s)',
                        'minutes' => 'minutos',
                        'minute'  => 'minuto',
                        'seconds' => 'segundos',
                        'second'  => 'segundo'
                    );
                    $uptime = strtr($match[2], $tabela);
                } else {
                    $uptime = $linha;
                }
            }
        } else {
            $uptime = false;
        }
        return $uptime;
    }


    //
    //     Obtem o tipo de uma variavel (int, float, string, bool, object, array, resource, null)
    //
    static public function get_tipo(&$var) {
    // Mixed $var: variavel a ser testada
    //
        if (is_int($var)) {
            return 'int';
        } elseif (is_float($var)) {
            return 'float';
        } elseif (is_string($var)) {
            return 'string';
        } elseif (is_bool($var)) {
            return 'bool';
        } elseif (is_object($var)) {
            return 'object';
        } elseif (is_array($var)) {
            return 'array';
        } elseif (is_resource($var)) {
            return 'resource';
        } elseif (is_null($var)) {
            return 'null';
        }
        return 'unknown type';
    }


    //
    //     Retorna o valor da variavel na forma de uma string legivel
    //
    static public function exibir_var($var, $tipo = UTIL_EXIBIR_PADRAO, $escapar = true) {
    // Mixed $var: variavel a ser convertida
    // Bool $tipo: codigo do tipo de exibicao
    // Bool $escapar: escapar valores string
    //
        global $CFG;
        switch (self::get_tipo($var)) {
        case 'int':
            switch ($tipo) {
            case UTIL_EXIBIR_PADRAO:
            case UTIL_EXIBIR_TEXTO:
            case UTIL_EXIBIR_PHP:
                $antigo = setlocale(LC_NUMERIC, '0');
                setlocale(LC_NUMERIC, 'C');
                $r = sprintf('%0.0f', $var);
                setlocale(LC_NUMERIC, $antigo);
                return $r;
            case UTIL_EXIBIR_NATURAL:
                return sprintf('%0.0f', $var);
            }
            break;
        case 'float':
            switch ($tipo) {
            case UTIL_EXIBIR_PADRAO:
            case UTIL_EXIBIR_PHP:
            case UTIL_EXIBIR_TEXTO:
                $antigo = setlocale(LC_NUMERIC, '0');
                setlocale(LC_NUMERIC, 'C');
                $sub = abs($var) - floor(abs($var));
                $casas_decimais = max(0, strlen($sub) - 2);
                $r = sprintf("%0.{$casas_decimais}f", $var);
                setlocale(LC_NUMERIC, $antigo);
                return $r;
            case UTIL_EXIBIR_NATURAL:
                $sub = abs($var) - floor(abs($var));
                $casas_decimais = max(0, strlen($sub) - 2);
                return sprintf("%0.{$casas_decimais}f", $var);
            }
            break;
        case 'string':
            switch ($tipo) {
            case UTIL_EXIBIR_PADRAO:
            case UTIL_EXIBIR_NATURAL:
            case UTIL_EXIBIR_TEXTO:
                return $var;
            case UTIL_EXIBIR_PHP:
                if ($escapar) {
                    $var = str_replace("'", "\\'", $var);
                }
                return "'".htmlentities($var, ENT_QUOTES, $CFG->charset)."'";
            }
            break;
        case 'bool':
            switch ($tipo) {
            case UTIL_EXIBIR_PADRAO:
            case UTIL_EXIBIR_PHP:
                return $var ? 'true' : 'false';
            case UTIL_EXIBIR_NATURAL:
                return $var ? 'sim' : 'n&atilde;o';
            case UTIL_EXIBIR_TEXTO:
                return $var ? 1 : 0;
            }
            break;
        case 'object':
            switch ($tipo) {
            case UTIL_EXIBIR_PADRAO:
                return 'object of class '.get_class($var);
            case UTIL_EXIBIR_TEXTO:
            case UTIL_EXIBIR_NATURAL:
                return 'objeto da classe '.get_class($var);
            case UTIL_EXIBIR_PHP:
                return 'new '.get_class($var).'(?)';
            }
            break;
        case 'array':
            switch ($tipo) {
            case UTIL_EXIBIR_PADRAO:
                return 'array of size '.count($var);
            case UTIL_EXIBIR_NATURAL:
            case UTIL_EXIBIR_TEXTO:
                return 'vetor de tamanho '.count($var);
            case UTIL_EXIBIR_PHP:
                $vt_retorno = array();

                // Vetor simples
                if (array_keys($var) === range(0, count($var) - 1, 1)) {
                    foreach ($var as $v) {
                        $vt_retorno[] = self::exibir_var($v, $tipo);
                    }

                // Vetor associativo
                } else {
                    foreach ($var as $k => $v) {
                        $vt_retorno[] = self::exibir_var($k, $tipo).' => '.self::exibir_var($v, $tipo);
                    }
                }
                $retorno = 'array('.implode(', ', $vt_retorno).')';
                return $retorno;
            }
            break;
        case 'resource':
            switch ($tipo) {
            case UTIL_EXIBIR_PADRAO:
                return 'resource of type '.get_resource_type($var);
            case UTIL_EXIBIR_NATURAL:
            case UTIL_EXIBIR_TEXTO:
                return 'recurso do tipo '.get_resource_type($var);
            case UTIL_EXIBIR_PHP:
                return '#resource ('.get_resource_type($var).')';
            }
            break;
        case 'null':
            switch ($tipo) {
            case UTIL_EXIBIR_PADRAO:
            case UTIL_EXIBIR_PHP:
            case UTIL_EXIBIR_TEXTO:
                return 'NULL';
            case UTIL_EXIBIR_NATURAL:
                return 'nenhum(a)';
            }
            break;
        }
        return strval($var);
    }


    //
    //     Obtem o mime type de um arquivo
    //
    static public function get_mime($arquivo, $com_charset = true) {
    // String $arquivo: nome do arquivo
    // Bool $com_charset: obtem o mime type com o charset incluso
    //
        $mime = false;

        if (extension_loaded('fileinfo')) {
            if ($com_charset) {
                $finfo = finfo_open(FILEINFO_MIME);
                $mime = finfo_file($finfo, $arquivo);
                finfo_close($finfo);
            } else {
                if (defined('FILEINFO_MIME_TYPE')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $arquivo);
                    finfo_close($finfo);
                } else {
                    $finfo = finfo_open(FILEINFO_MIME);
                    $mime = finfo_file($finfo, $arquivo);
                    finfo_close($finfo);
                    $mime = preg_replace('/(;?[\040]*charset=.*)$/', '', $mime);
                }
            }

        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($arquivo);
            if (!$com_charset) {
                $mime = preg_replace('/(;?[\040]*charset=.*)$/', '', $mime);
            }
        }
        return $mime;
    }


    //
    //     Realiza o array_unique de maneira recursiva
    //
    static public function array_unique_recursivo($vetor) {
    // Array[Mixed] $vetor: qualquer vetor
    //
        trigger_error('Metodo depreciado, use vetor::array_unique_recursivo', E_USER_DEPRECATED);
        return vetor::array_unique_recursivo($vetor);
    }


    //
    //     Indica se o host informado e' local ou nao
    //
    static public function host_local($host) {
    // String $host: host a ser testado
    //
        if ($host == 'localhost') {
            return true;
        }

        $ip = gethostbyname($host);
        if ($ip === false) {
            trigger_error('Host invalido: '.$host, E_USER_WARNING);
            return false;
        }
        $ip2 = gethostbyname('localhost');
        return $ip == $ip2;
    }

}//class
