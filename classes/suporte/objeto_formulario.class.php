<?php
//
// SIMP
// Descricao: Classe Abstrata Objeto Formulario, oferece formularios padrao para as entidades
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.4.0.17
// Data: 27/08/2007
// Modificado: 29/07/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Define se os campos gerados por campo_formulario mostrarao um "*" nos campos obrigatorios (que nao podem ser vazios)
define('OBJETO_FORMULARIO_ASTERISCO_OBRIGATORIO', false);

abstract class objeto_formulario extends objeto {
    protected $names;     // Array[String] vetor dos Names dos campos inseridos no formulario de inserir ou alterar
    protected $antigo;    // Object objeto com os dados antigos do objeto (antes de setar os novos valores em formularios de inserir, alterar ou importar)
    protected $disabled;  // Bool flag que indica se o campo do formulario deve ser desabilitado ou nao


/// @ METODOS QUE PODEM SER SOBRECARREGADOS


    //public function campo_formulario(&$form, $campo, $valor)
    //public function pode_ser_manipulado(&$usuario)
    //public function pode_acessar_formulario(&$usuario, &$motivo = '')
    //public function get_info_campo($campo)


/// @ METODOS DE LOGICA


    //
    //     Indica se o registro pode ser manipulado pelo usuario
    //
    public function pode_ser_manipulado(&$usuario) {
    // usuario $usuario: usuario a ser testado
    //
        return true;
    }


    //
    //     Indica se o formulario de um registro pode ser acessado ou nao por um usuario
    //
    public function pode_acessar_formulario(&$usuario, &$motivo = '') {
    // usuario $usuario: usuario a ser testado
    // String $motivo: motivo pelo qual nao se pode acessar o registro
    //
        return true;
    }


    //
    //     Monta as opcoes adicionais do metodo logica_formulario
    //
    final protected function montar_opcoes(&$dados, $campos, $opcoes = false) {
    // Object $dados: dados submetidos pelo formulario
    // Array[String] $campos: campos do formulario
    // Array[String => String] $opcoes: opcoes adicionais a serem inseridas nos dados
    //
        // Vetor (hierarquico) de campos a serem salvos
        $salvar_campos = array();
        foreach ($campos as $campo) {
            if (!$this->__isset($campo)) {
                continue;
            }
            $vt_campo = explode(':', $campo);
            $campo = array_pop($vt_campo);

            // Se e' um atributo da propria classe
            if (empty($vt_campo)) {
                $salvar_campos[] = $campo;

            // Se e' um atributo de um objeto filho
            } else {
                util::definir_vetor_nivel($salvar_campos, array_merge($vt_campo, array(null)), $campo);
            }
        }

        // Se possui opcoes para adicionar nos dados
        $classe = $this->get_classe();
        if ($opcoes && is_array($opcoes)) {
            foreach ($opcoes as $campo => $valor) {
                $vt_campo = explode(':', $campo);
                $campo = array_pop($vt_campo);

                // Se e' um atributo da propria classe
                if (empty($vt_campo)) {
                    if (!isset($dados->$classe)) {
                        $dados->$classe = new stdClass();
                    }
                    $dados->$classe->$campo = $valor;
                    if ($this->possui_atributo($campo)) {
                        $salvar_campos[] = $campo;
                    }

                // Se e' um atributo de um objeto filho
                } else {
                    util::definir_atributo_nivel($dados->$classe, array_merge($vt_campo, array($campo)), $valor);
                    util::definir_vetor_nivel($salvar_campos, array_merge($vt_campo, array(null)), $campo);
                }
            }
        }
        return $salvar_campos;
    }


    //
    //     Faz a Logica de um formulario simples de insercao ou alteracao (null = nao fez nada / false = erro / true = sucesso)
    //
    protected function logica_formulario($dados, $campos, $opcoes = false, $captcha = false, $modo_transacao = DRIVER_BASE_MODO_PADRAO) {
    // Object $dados: dados submetidos pelo formulario
    // Array[String] $campos: campos reais vindos do formulario
    // Array[String => String] $opcoes: opcoes adicionais a serem inseridas nos dados
    // Bool $captcha: indica se um campo captcha foi solicitado no formulario
    // Int $modo_transacao: tipo de transacao
    //
        // Se nem submeteu dados, ignorar
        if (isset($dados->default) || $dados->id_form != $this->id_form) {
            return null;
        }
        $classe = $this->get_classe();

        // Montar vetor de opcoes adicionais e retorna o vetor de campos a serem salvos
        $salvar_campos = $this->montar_opcoes($dados, $campos, $opcoes);
        if (is_array($opcoes) && !empty($opcoes)) {
            $opcoes_hierarquico = objeto::converter_notacao_vetor(array_keys($opcoes));
            $this->names = vetor::array_unique_recursivo(array_merge_recursive($this->names, $opcoes_hierarquico));
        }

        // Se o formulario possui um campo captcha
        if ($captcha && !captcha::validar($dados->captcha)) {
            $this->erros[] = 'O texto da imagem est&aacute; incorreto ('.texto::codificar($dados->captcha).')';
        }

        // Armazenar valores antigos
        if ($this->existe()) {
            $this->antigo = $this->get_dados(self::converter_notacao_vetor_hierarquico($this->names));
        } else {
            $this->antigo = new stdClass();
        }

        // Se conseguir salvar
        if ($this->set_valores($dados->$classe, $this->names, true) &&
            $this->salvar_completo($salvar_campos, 'salvar', $modo_transacao)) {
            $this->imprimir_avisos();
            return true;

        // Se nao conseguiu setar os valores e salvar
        } else {
            $this->imprimir_erros();
            return false;
        }
    }


    //
    //     Faz a Logica de um formulario simples de exclusao
    //
    protected function logica_formulario_excluir(&$dados, $modo_transacao = DRIVER_BASE_MODO_PADRAO) {
    // Object $dados: dados submetidos pelo formulario
    // Int $modo_transacao: tipo de transacao
    //
        // Se os dados nao foram submetidos
        if (!isset($dados->id_form) ||
            $dados->id_form != $this->id_form) {
            return null;
        }

        $chave_exclusao = $this->chave_exclusao();

        // Checar se o usuario confirmou a exclusao
        if (!$dados->confirmacao) {
            $this->avisos[] = 'Nada foi feito (marque a confirma&ccedil;&atilde;o)';
            $this->imprimir_avisos();
            return null;
        }

        // Fazer as validacoes sobre a chave de exclusao
        if (!isset($dados->chave_exclusao)) {
            $this->erros[] = 'N&atilde;o foi informada a chave de exclus&atilde;o (Erro inesperado)';
        } elseif (strcmp($dados->chave_exclusao, $chave_exclusao) != 0) {
            $this->erros[] = 'Chave para exclus&atilde;o n&atilde;o confere (Erro inesperado)';
        }

        // Se houve erros
        if ($this->possui_erros()) {
            $this->imprimir_erros();
            return false;
        }

        // Nenhum campo sera salvo
        $salvar_campos = array();

        // Se nao conseguir apagar
        if (!$this->validacao_final($dados) ||
            !$this->salvar_completo($salvar_campos, 'excluir', $modo_transacao)) {
            $this->imprimir_erros();
            return false;
        }

        // Se conseguiu excluir os valores
        $this->imprimir_avisos();
        return true;
    }


    //
    //     Faz a Logica de um formulario simples de insercao ou alteracao
    //
    protected function logica_formulario_relacionamento(&$dados, $chave_obj, $nome_vetor, &$vt_itens, &$disable, $modo_transacao = DRIVER_BASE_MODO_PADRAO) {
    // Object $dados: dados submetidos
    // String $chave_obj: nome do vetor submetido pelo formulario
    // String $nome_vetor: nome do vetor para listar os elementos
    // Array[Mixed => String] $vt_itens: vetor de itens possiveis
    // Array[Bool] $disable: vetor de itens desabilitados (nao podem mudar)
    // Int $modo_transacao: tipo de transacao
    //
        // Se os dados nao foram submetidos
        if (!isset($dados->id_form)) {
            return null;
        }

        // Se os dados foram submetidos deste formulario
        if ($dados->id_form == $this->id_form) {

            // Vetores
            $possui   = array();
            $esperado = array();
            $inserir  = array();
            $remover  = array();

            // Gerar vetor com os elementos que a entidade ja' possui
            foreach ($this->get_vetor_rel_un($nome_vetor) as $rel) {
                $possui[] = $rel->$chave_obj;
            }

            // Gerar vetor com os elementos que foram selecionados no formulario
            $esperado = $dados->$chave_obj;

            // Gerar Vetor de insercao
            foreach ($esperado as $item) {            // Para cada item esperado, checar se:
                if ((!in_array($item, $possui)) &&    // 1 - Nao possui ainda
                    in_array($item, $vt_itens) &&     // 2 - Pode inserir (estava no formulario)
                    (!in_array($item, $disable))) {   // 3 - Pode mudar (nao estava desabilitado)
                    $inserir[] = $item;
                }
            }

            // Gerar Vetor de remocao
            foreach ($possui as $item) {              // Para cada item que ele possui, checar se:
                if ((!in_array($item, $esperado)) &&  // 1 - Ele nao sera' inserido
                    in_array($item, $vt_itens) &&     // 2 - Ele pode ser removido (estava no formulario)
                    (!in_array($item, $disable))) {   // 3 - Pode mudar (nao estava desabilitado)
                    $remover[] = $item;
                }
            }

            $r         = true;  // Resultado
            $operacoes = 0;     // Numero de operacoes

            // Validacao final
            if (!$this->validacao_final($dados)) {
                $this->imprimir_erros();
                return false;
            }

            // Iniciar transacao
            $r = $r && objeto::inicio_transacao($modo_transacao);

            // Operacoes pre-salvar
            if (!$this->pre_salvar($salvar_campos)) {
                objeto::fim_transacao(true);
                $this->imprimir_erros();
                return false;
            }

            // Se tem elementos a serem inseridos
            if (!empty($inserir)) {
                foreach ($inserir as $item) {
                    $dados = new stdClass();
                    $dados->$chave_obj = $item;
                    $r = $r && $this->inserir_elemento_rel_un($nome_vetor, $dados);
                    $operacoes++;
                }
            }

            // Se tem elementos a serem removidos
            if (!empty($remover)) {
                foreach ($remover as $item) {
                    $r = $r && $this->remover_elemento_rel_un($nome_vetor, $item);
                    $operacoes++;
                }
            }

            // Operacoes pos-salvar
            if (!$this->pos_salvar()) {
                objeto::fim_transacao(true);
                $this->imprimir_erros();
                return false;
            }

            // Encerrar transacao
            $r = objeto::fim_transacao(!$r) && $r;

            // Gerar mensagem
            if ($r) {
                if ($operacoes) {
                    $this->avisos[] = 'Dados salvos com sucesso';
                    $this->imprimir_avisos();
                } else {
                    $this->avisos[] = 'Nenhum dado foi alterado';
                    $this->imprimir_avisos();
                }
                return true;
            } else {
                $this->erros[] = 'Erro ao salvar dados';
                $this->imprimir_erros();
                return false;
            }
        }
        return null;
    }


    //
    //     Faz a Logica de um formulario de importacao de dados de arquivos XML
    //
    protected function logica_formulario_importar_xml(&$dados, &$arquivos, &$obrigatorios, &$opcoes, $modo_transacao = DRIVER_BASE_MODO_PADRAO) {
    // Object $dados: dados submetidos
    // Array[String => Object] $arquivos: vetor com os arquivos submetidos
    // Array[String] $obrigatorios: vetor com os nomes dos campos obrigatorios
    // Array[String => String] $opcoes: opcoes adicionais a serem inseridas em cada registro
    // Int $modo_transacao: tipo de transacao
    //
        // Se os dados nao foram submetidos
        if (!isset($dados->id_form)) {
            $campos = array(
                'arquivo'       => '',
                'MAX_FILE_SIZE' => false,
                'direto_bd'     => false,
                'id_form'       => $this->id_form
            );
            $dados = formulario::montar_dados($campos, $dados);
            return null;
        }

        // Se os dados nao foram submetidos deste formulario
        if ($dados->id_form != $this->id_form) {
            return null;
        }

        // Checar se o arquivo esta' pronto para ser importado
        if (!$this->validar_arquivo_xml($arquivos->arquivo, $dados)) {
            $this->imprimir_erros();
            return false;
        }

        // Recuperar dados do arquivo e guardar em vetor de entidades
        $entidades = $this->get_entidades_xml($arquivos->arquivo, $opcoes);

        // Se nao conseguiu ler o arquivo
        if (!$entidades) {
            $this->erros[] = 'Erro ao ler arquivo XML';
            $this->imprimir_erros();
            return false;
        }

        // Checar a integridade dos dados do arquivo
        if ((!$dados->direto_bd) &&
            (!$this->validar_integridade($obrigatorios, $entidades))) {
            $this->imprimir_erros();
            return false;
        }

        // Tudo pronto, vamos importa'-los!
        $this->importar_entidades($entidades, $dados->direto_bd, $modo_transacao);

        // Se deu tudo certo
        if (!$this->possui_erros()) {
            $this->imprimir_avisos();
            return true;
        } else {
            $this->imprimir_erros();
            return false;
        }
    }


    //
    //     Faz a Logica de um formulario de importacao de dados de arquivos CSV
    //
    protected function logica_formulario_importar_csv(&$dados, &$arquivos, &$obrigatorios, &$opcoes, $modo_transacao = DRIVER_BASE_MODO_PADRAO) {
    // Object $dados: dados submetidos
    // Array[String => Object] $arquivos: vetor com os arquivos submetidos
    // Array[String] $obrigatorios: vetor com os nomes dos campos obrigatorios
    // Array[String => String] $opcoes: opcoes adicionais a serem inseridas em cada registro
    // Int $modo_transacao: tipo de transacao
    //
        // Se os dados nao foram submetidos
        if (!isset($dados->id_form)) {
            $campos = array(
                'arquivo'       => '',
                'separador'     => ',',
                'aspas'         => '"',
                'MAX_FILE_SIZE' => false,
                'direto_bd'     => false,
                'id_form'       => $this->id_form
            );
            $dados = formulario::montar_dados($campos, $dados);
            return null;
        }

        // Se os dados nao foram submetidos deste formulario
        if ($dados->id_form != $this->id_form) {
            return null;
        }

        // Validar os campos passados
        if (!$this->validar_dados_csv($dados)) {
            $this->imprimir_erros();
            return false;
        }

        // Checar se o arquivo esta' pronto para ser importado
        if (!$this->validar_arquivo_csv($arquivos->arquivo, $dados)) {
            $this->imprimir_erros();
            return false;
        }

        // Recuperar dados do arquivo e guardar em vetor de entidades
        $entidades = $this->get_entidades_csv($arquivos->arquivo, $dados->separador, $dados->aspas, $opcoes);

        // Checar a integridade dos dados do arquivo
        if ((!$dados->direto_bd) &&
            (!$this->validar_integridade($obrigatorios, $entidades))) {
            $this->imprimir_erros();
            return false;
        }

        // Tudo pronto, vamos importa'-los!
        $this->importar_entidades($entidades, $dados->direto_bd, $modo_transacao);

        // Se deu tudo certo
        if (!$this->possui_erros()) {
            $this->imprimir_avisos();
            return true;
        } else {
            $this->imprimir_erros();
            return false;
        }
    }


    //
    //     Valida os campos do formulario de importacao de arquivos CSV
    //
    private function validar_dados_csv(&$dados) {
    // Object $dados: dados submetidos
    //
        // Checar se foram passados o separador e as aspas
        if (empty($dados->separador)) {
            $this->erros[] = 'Faltou preencher o campo "Separador"';
        } elseif (strlen($dados->separador) > 1) {
            $this->erros[] = 'O campo "Separador" s&oacute; pode ter um caracter';
        }
        if (empty($dados->aspas)) {
            $this->erros[] = 'Faltou preencher o campo "Aspas"';
        } elseif (strlen($dados->separador) > 1) {
            $this->erros[] = 'O campo "Aspas" s&oacute; pode ter um caracter';
        }

        // Checar se nao sao iguais
        if ($dados->separador == $dados->aspas) {
            $this->erros[] = 'O campo "Separador" n&atilde;o pode ser igual ao campo "Aspas"';
        }
        return !$this->possui_erros();
    }


    //
    //     Checa se um arquivo CSV esta' pronto para ser importado corretamente
    //
    private function validar_arquivo_csv(&$arquivo, &$dados) {
    // Object $arquivo: dados do arquivo submetido
    // Object $dados: dados submetidos
    //
        $tipos_permitidos = array('text/plain', 'text/csv', 'text/x-plain', 'text/x-csv', 'text/comma-separated-values', 'application/octet-stream');
        return $this->validar_arquivo($arquivo, $dados, $tipos_permitidos);
    }


    //
    //     Checa se um arquivo XML esta' pronto para ser importado corretamente
    //
    private function validar_arquivo_xml(&$arquivo, &$dados) {
    // Object $arquivo: dados do arquivo submetido
    // Object $dados: dados submetidos
    //
        $tipos_permitidos = array('text/xml', 'application/xml', 'text/x-xml', 'application/x-xml', 'application/octet-stream');
        return $this->validar_arquivo($arquivo, $dados, $tipos_permitidos);
    }


    //
    //     Checa se um arquivo esta' pronto para ser importado corretamente
    //
    private function validar_arquivo(&$arquivo, &$dados, &$tipos_permitidos) {
    // Object $arquivo: dados do arquivo submetido
    // Object $dados: dados submetidos
    // Array[String] $tipos_permitidos: vetor de mime-types permitidos
    //
        // Checar se o arquivo foi informado
        if (empty($arquivo->name)) {
            $this->erros[] = 'Faltou informar o arquivo';
            return false;
        }

        // Checar o tamanho do arquivo
        if ($arquivo->size > 2097152) {
            $tam_enviado = memoria::formatar_bytes($arquivo->size, 1);
            $this->erros[] = 'Tamanho do arquivo enviado ('.$tam_enviado.') ultrapassa o limite (2<abbr title="Megabytes">MB</abbr></em>)';
        }

        // Checar o tipo de arquivo
        if (!in_array($arquivo->type, $tipos_permitidos)) {
            $this->erros[] = 'O tipo de arquivo deve ser: '.implode(' ou ', $tipos_permitidos).'). '.
                             'Foi enviado um arquivo do tipo '.$arquivo->type.'.';
        }

        // Checar se ocorreu um erro inesperado
        if ($arquivo->error) {
            $this->erros[] = formulario::get_erro_upload($arquivo_error);
        }

        return !$this->possui_erros();
    }


    //
    //     Gera um vetor de objetos stdClass a partir de um arquivo XML
    //
    private function get_entidades_xml(&$arquivo, &$opcoes) {
    // Object $arquivo: dados do arquivo submetido
    // Array[String => String] $opcoes: opcoes adicionais a serem inseridas em cada registro
    //
        $entidades_xml = simplexml_load_file($arquivo->tmp_name);
        if (!$entidades_xml) {
            return false;
        }
        return $this->xml_para_object($entidades_xml, $opcoes);
    }


    //
    //     Converte de SimpleXMLElement para stdClass
    //
    private function xml_para_object(&$xml, &$opcoes) {
    // Mixed $xml: alguma estrutura para ser convertida para stdClass (SimpleXMLElement ou valor escalar)
    // Array[String => String] $opcoes: opcoes adicionais a serem inseridas em cada registro
    //
        $obj = null;
        if (is_array($xml)) {
            $obj = array();
            foreach ($xml as $chave => $valor) {
                $obj[$chave] = $this->xml_para_object($valor);
            }
        } else {
            $var = get_object_vars($xml);
            if ($var) {
                $obj = new stdClass();
                foreach ($var as $chave => $valor) {
                    $obj->$chave = $this->xml_para_object($valor);
                }
                if ($opcoes) {
                    foreach ($opcoes as $chave => $valor) {
                        $obj->$chave = $valor;
                    }
                }
            } else {
                return strval($xml);
            }
        }
        return $obj;
    }



    //
    //     Gera um vetor de objetos stdClass a pratir de um arquivo CSV
    //
    private function get_entidades_csv(&$arquivo, $separador = ',', $aspas = '"', &$opcoes = false) {
    // Object $arquivo: dados do arquivo submetido
    // Char $separador: separador de dados CSV
    // Char $aspas: delimitador de dados CSV
    // Array[String => String] $opcoes: opcoes adicionais a serem inseridas em cada registro
    //
        $classe = $this->get_classe();
        $entidades = new stdClass();
        $entidades->$classe = array();

        $arq = fopen($arquivo->tmp_name, 'r');
        if ($arq) {

            // Recuperar cabecalho do arquivo CSV
            $cabecalho = fgetcsv($arq, 0, $separador, $aspas);

            // Recuperar dados do arquivo CSV
            while (!feof($arq)) {
                $linha = fgetcsv($arq, 0, $separador, $aspas);
                if (!$linha) { continue; }

                // Gerar objeto com os dados
                $entidade = (object)array_combine($cabecalho, $linha);
                if ($opcoes) {
                    foreach ($opcoes as $chave => $valor) {
                        $entidade->$chave = $valor;
                    }
                }

                // Guardar os dados no vetor de entidades
                $entidades->{$classe}[] = $entidade;
            }

            fclose($arq);
        } else {
            $this->erros[] = 'Erro ao abrir arquivo carregado';
            return false;
        }
        return $entidades;
    }


    //
    //     Verifica se os dados do arquivo sao validos para importacao
    //
    private function validar_integridade(&$obrigatorios, &$entidades) {
    // Array[String] $obrigatorios: vetor com os nomes dos campos obrigatorios
    // Array[Object] $entidades: vetor de objetos com os campos obtidos do arquivo
    //
        // Checar se o cabecalho possui os campos obrigatorios
        $classe = $this->get_classe();
        $cabecalho = array_keys((array)$entidades->{$classe}[0]);
        foreach ($obrigatorios as $obrigatorio) {
            if (!in_array($obrigatorio, $cabecalho)) {
                $this->erros[] = 'O arquivo n&atilde;o possui o campo obrigat&oacute;rio "'.$obrigatorio.'"';
            }
        }
        if (!empty($this->erros)) {
            return false;
        }

        // Checar se os campos estao no formato correto
        $obj       = new $classe();               // Objeto auxiliar
        $n         = 1;                           // Numero do registro
        $vt_unicos = $this->campos_unicos();      // Vetor com os campos unicos
        $mt_aux    = array();                     // Matriz auxiliar

        $vt_unicos = array_intersect($vt_unicos, $cabecalho);
        foreach ($entidades->$classe as $entidade) {

            // Tentar setar os dados no objeto
            $obj->limpar_objeto();
            $obj->set_valores($entidade, false, true);
            if ($obj->possui_erros()) {
                $this->erros[] = 'Erro no registro '.$n;
                $this->erros[] = $obj->get_erros();
            }

            // Checar a ocorrencia de campos repetidos no arquivo
            foreach ($vt_unicos as $unico) {
                if (isset($mt_aux[$unico][$entidade->$unico])) {
                    $n2 = $mt_aux[$unico][$entidade->$unico];
                    $min = min($n, $n2);
                    $max = max($n, $n2);
                    $this->erros[] = "O campo &uacute;nico \"{$unico}\" se repete nos registros {$min} e {$max} do arquivo";
                } else {
                    $mt_aux[$unico][$entidade->$unico] = $n;
                }
            }
            $n++;
        }
        return !$this->possui_erros();
    }


    //
    //     Importa as entidades para o BD
    //
    private function importar_entidades(&$entidades, $direto_bd = false, $modo_transacao = DRIVER_BASE_MODO_PADRAO) {
    // Array[Object] $entidades: vetor de objeto com os dados a serem inseridos
    // Bool $direto_bd: indica se os dados devem ser inseridos diretamente no BD ou passar pelas validacoes
    // Int $modo_transacao: tipo de transacao
    //
        $r = true;

        // Jogar dados diretamente no BD
        if ($direto_bd) {
            $n = 1;
            $classe = $this->get_classe();

            $ignore_user_abort = ini_get('ignore_user_abort');
            ini_set('ignore_user_abort', 1);
            $r = objeto::inicio_transacao($modo_transacao);
            foreach ($entidades->$classe as $entidade) {
                $inseriu = self::$dao->insert($this, $entidade);
                $r = $r && $inseriu;
                if (!$inseriu) {
                    $this->erros[] = 'Erro ao importar '.$this->get_entidade()." (registro {$n})";
                }
                $n++;
            }
            $r = objeto::fim_transacao(!$r) && $r;
            ini_set('ignore_user_abort', $ignore_user_abort);

        // Salvar no BD como se estivesse inserindo entidade por entidade
        } else {
            $classe = $this->get_classe();
            $obj    = new $classe();
            $n      = 1;

            $ignore_user_abort = ini_get('ignore_user_abort');
            ini_set('ignore_user_abort', 1);
            $r = objeto::inicio_transacao($modo_transacao);
            foreach ($entidades->$classe as $entidade) {
                $obj->limpar_objeto();
                $obj->set_id_form($this->id_form);
                $obj->antigo = $obj->get_dados(true);
                $obj->set_valores($entidade, false, true);
                $campos_entidade = array_keys((array)$entidade);

                // Se nao salvou
                if (!$obj->salvar_completo($campos_entidade, 'salvar')) {
                    $r = false;
                    $this->erros[] = 'Erro ao importar '.$this->get_entidade()." (registro {$n})";
                    $this->erros[] = $obj->get_erros();
                }
                $n++;
            }
            $r = objeto::fim_transacao(!$r) && $r;
            ini_set('ignore_user_abort', $ignore_user_abort);

            if (!$r) {
                $this->erros[] = 'Alguma opera&ccedil;&atilde;o falhou e todo processo foi cancelado';
            }
        }

        if ($r) {
            switch ($this->get_genero()) {
            case 'M':
                $this->avisos[] = $this->get_entidade(true).' importados com sucesso (total: '.$n.')';
                break;
            case 'F':
                $this->avisos[] = $this->get_entidade(true).' importadas com sucesso (total: '.$n.')';
                break;
            case 'I':
                $this->avisos[] = $this->get_entidade(true).' importados com sucesso (total: '.$n.')';
                break;
            }
        }
        return $r;
    }


    //
    //     Obtem informacoes sobre um campo do formulario
    //
    public function get_info_campo($campo) {
    // String $campo: campo desejado
    //
        if ($this->possui_atributo($campo)) {
            return $this->get_definicao_atributo($campo);
        }
        return false;
    }


    //
    //     Retorna a definicao de um atributo simples da classe para validacao
    //
    public function get_definicao_atributo_validacao($nome_atributo) {
    // String $nome_atributo: nome do atributo desejado
    //
        return $this->get_info_campo($nome_atributo);
    }


/// @ METODOS AUXILIARES


    //
    //     Retorna uma chave unica de exclusao de um elemento da entidade
    //
    final protected function chave_exclusao() {
         $c = $this->get_classe().'.'.                       // Nome da classe da entidade
              $this->get_chave().'.'.                        // Nome da chave primaria da entidade
              $_SERVER['REMOTE_ADDR'].'.'.                   // IP de quem esta' excluindo
              count($this->get_atributos()).'.'.             // Numero de atributos da entidade
              $this->get_valor_chave().'.'.                  // Valor da chave da entidade
              strftime('%d', time());                        // Dia em que ocorreu a exclusao

         $c = md5($c);

         return $c;
    }


    //
    //     ID do formulario de inserir
    //
    final public function id_formulario_inserir($prefixo = '') {
    // String $prefixo: prefixo do formulario
    //
        if (!empty($prefixo)) {
            $prefixo .= '_';
        }
        return $prefixo.'form_inserir_'.$this->get_classe();
    }


    //
    //     ID do formulario de alterar
    //
    final public function id_formulario_alterar($prefixo = '') {
    // String $prefixo: prefixo do formulario
    //
        if (!empty($prefixo)) {
            $prefixo .= '_';
        }
        return $prefixo.'form_alterar_'.$this->get_classe();
    }


    //
    //     ID do formulario de excluir
    //
    final public function id_formulario_excluir($prefixo = '') {
    // String $prefixo: prefixo do formulario
    //
        if (!empty($prefixo)) {
            $prefixo .= '_';
        }
        return $prefixo.'form_excluir_'.$this->get_classe();
    }


    //
    //    ID do formulario de relacionamento
    //
    final public function id_formulario_relacionamento($prefixo = '') {
    // String $prefixo: prefixo do formulario
    //
        if (!empty($prefixo)) {
            $prefixo .= '_';
        }
        return $prefixo.'form_relacionamento_'.$this->get_classe();
    }


    //
    //     ID do formulario de importacao de arquivos CSV
    //
    final public function id_formulario_importar_csv($prefixo = '') {
    // String $prefixo: prefixo do formulario
    //
        if (!empty($prefixo)) {
            $prefixo .= '_';
        }
        return $prefixo.'form_importar_csv_'.$this->get_classe();
    }


    //
    //     ID do formulario de importacao de arquivos XML
    //
    final public function id_formulario_importar_xml($prefixo = '') {
    // String $prefixo: prefixo do formulario
    //
        if (!empty($prefixo)) {
            $prefixo .= '_';
        }
        return $prefixo.'form_importar_xml_'.$this->get_classe();
    }


    //
    //     ID de um formulario generico
    //
    final public function id_formulario_generico($prefixo = '') {
    // String $prefixo: prefixo do formulario
    //
        if (!empty($prefixo)) {
            $prefixo .= '_';
        }
        return $prefixo.'form_'.$this->get_classe();
    }


    //
    //     ID dos botoes de submit dos formularios
    //
    final protected function id_salvar() {
        return 'salvar_'.$this->get_classe();
    }


/// @ METODOS DE APRESENTACAO DOS FORMULARIOS


    //
    //     Exibe um campo do formulario
    //
    public function campo_formulario(&$form, $campo, $valor) {
    // formulario $form: formulario que deve receber o campo
    // String $campo: nome do campo a ser inserido
    // Mixed $valor: valor padrao do campo
    //
        $atributo = $this->get_definicao_atributo($campo);
        if (!$atributo) {
            return false;
        }

        // Obter valor padrao
        if ($atributo->usar_valor_padrao && $atributo->is_null($valor)) {
            switch ($atributo->tipo) {
            case 'data':
                if ($atributo->padrao == 'agora') {
                    $valor = strftime('%d-%m-%Y-%H-%M-%S');
                } else {
                    $valor = $atributo->padrao;
                }
                break;
            default:
                $valor = $atributo->padrao;
                break;
            }
        } elseif ($this->possui_auxiliar($campo)) {
            $valor = $this->get_auxiliar($campo);
        }
        $mascara = $atributo->get_mascara();

        // Se especificou um tipo de campo para aparecer no formulario
        if ($atributo->campo_formulario) {
            if ($atributo->tipo == 'string') {
                $maxlength = $atributo->maximo;
                $size = min(30, $atributo->maximo);
            } elseif ($atributo->tipo == 'int') {
                $maxlength = strlen($atributo->maximo);
                $size = min(30, $maxlength);
            } elseif ($atributo->tipo == 'float') {
                $maxlength = strlen($atributo->maximo) + 20;
                $size = min(30, $maxlength);
            } elseif ($atributo->tipo == 'char' || $atributo->tipo == 'bool') {
                $maxlength = 1;
                $size = 1;
            } else {
                $maxlength = false;
                $size = 30;
            }

            switch ($atributo->campo_formulario) {
            case 'text':
                $form->campo_text($atributo->nome, $atributo->nome, $valor, $maxlength, $size, $atributo->get_label($this->id_form), $this->disabled, 0, 0, $mascara, $atributo->ajuda);
                return true;

            case 'textarea':
                $form->campo_textarea($atributo->nome, $atributo->nome, $valor, 30, 5, $atributo->get_label($this->id_form), $this->disabled, 0, 0, $atributo->ajuda);
                return true;

            case 'password':
                $form->campo_password($atributo->nome, $atributo->nome, $maxlength, $size, $atributo->get_label($this->id_form), $this->disabled, 0, 0, $atributo->ajuda);
                return true;

            case 'radio':
            case 'select':
                $metodo = 'get_vetor_'.$atributo->nome;
                if (method_exists($this, $metodo)) {
                    $vetor = $this->$metodo();
                } elseif ($atributo->chave == 'FK' || $atributo->chave == 'OFK') {
                    $obj = $this->get_objeto_rel_uu($atributo->nome, false);
                    $vetor = $obj->vetor_associativo();
                    if ($atributo->chave == 'OFK') {
                        $vetor = array('0' => 'Nenhum') + $vetor;
                    }
                } else {
                    if ($atributo->tipo == 'int') {
                        $vetor = listas::numeros($atributo->minimo, $atributo->maximo, false, 1, true);
                    } elseif ($atributo->tipo == 'float') {
                        $passo = ((double)$atributo->maximo - (double)$atributo->minimo) / (double)100;
                        $vetor = array();
                        for ($i = $atributo->minimo; $i < $atributo->maximo; $i += $passo) {
                            $item = texto::numero($i, ($atributo->casas_decimais ? $atributo->casas_decimais : 2));
                            $vetor[$item] = $item;
                        }
                    } elseif ($atributo->tipo == 'char') {
                        $vetor = array();
                        for ($i = 0; $i < 255; $i++) {
                            $c = chr($i);
                            $c_utf = utf8_encode($c);
                            if ($c && utf8_decode($c_utf) == $c) {
                                $vetor[$c] = OBJETO_UTF8 ? $c_utf : $c;
                            }
                        }
                    } elseif ($atributo->tipo == 'bool') {
                        $vetor = array('0' => 'N&atilde;o', '1' => 'Sim');
                    } else {
                        $vetor = array($atributo->padrao => $atributo->padrao);
                    }
                }
                if ($atributo->campo_formulario == 'radio') {
                    $disabled = $this->disabled ? array_keys($vetor) : false;
                    $form->campo_radio($atributo->nome, $atributo->nome, $vetor, $valor, $atributo->get_label($this->id_form), $disabled, 0, 0, $atributo->ajuda);
                } else {
                    $form->campo_select($atributo->nome, $atributo->nome, $vetor, $valor, $atributo->get_label($this->id_form), $this->disabled, 0, 0, $atributo->ajuda);
                }
                return true;

            case 'bool':
                $form->campo_bool($atributo->nome, $atributo->nome, $atributo->get_label($this->id_form), $valor, $this->disabled, false, false, $atributo->ajuda);
                return true;

            case 'bool_radio':
                $form->campo_bool_radio($atributo->nome, $atributo->nome, $atributo->get_label($this->id_form), $valor, $this->disabled, false, false, $atributo->ajuda);
                return true;

            case 'relacionamento':
                if ($atributo->chave == 'FK' || $atributo->chave == 'OFK') {
                    $obj = $this->get_objeto_rel_uu($atributo->nome, false);
                    $form->campo_relacionamento($atributo->nome, $atributo->nome, $obj->get_classe(), $obj->get_chave(), $obj->get_campo_nome(), '', $maxlength, $size, $atributo->get_label($this->id_form), 1, 0, 0, $mascara, $atributo->ajuda);
                } else {
                    trigger_error('O atributo "'.$atributo->nome.'" nao pode ser do tipo relacionamento', E_USER_ERROR);
                }
                return true;

            case 'hidden':
                $form->campo_hidden($atributo->nome, $valor);
                return true;

            case 'file':
                $form->campo_file($atributo->nome, $atributo->nome, $atributo->get_label($this->id_form), $this->disabled, false, false, false, $atributo->ajuda);
                return true;

            case 'submit':
                $form->campo_submit($atributo->nome, $atributo->nome, $valor, 1, false, $this->disabled, false, $atributo->ajuda);
                return true;
            }
        }

        // Se e' uma chave primaria, assumir que e' um campo hidden
        if ($atributo->chave == 'PK') {
            $form->campo_hidden($atributo->nome, $valor);
            return true;
        }

        // Se e' uma chave estrangeira, assumir que e' um select ou um campo de busca
        if ($atributo->chave == 'FK' || $atributo->chave == 'OFK') {

            // Campo select pre-definido
            $metodo = 'get_vetor_'.$atributo->nome;
            if (method_exists($this, $metodo)) {
                $obj = $this->get_objeto_rel_uu($atributo->nome, false);
                $nome_obj = $this->get_nome_objeto_rel_uu($atributo->nome);
                $vetor = $this->$metodo();
                self::preparar_vetor_select($vetor);
                $form->campo_select($atributo->nome, $atributo->nome, $vetor, $valor, $this->get_entidade_rel_uu($nome_obj), $this->disabled, 0, 0, $atributo->ajuda);
                return true;

            // Campo select numerico ou campo relacionamento
            } elseif ($atributo->tipo == 'int' || $atributo->tipo == 'float')  {
                $obj = $this->get_objeto_rel_uu($atributo->nome, false);
                $nome_obj = $this->get_nome_objeto_rel_uu($atributo->nome);

                // Se sao poucos elementos possiveis: campo select
                if (((abs($atributo->maximo) - abs($atributo->minimo)) < 100) ||
                    (!$obj->possui_registros(null, 100))) {
                    $vetor = $obj->vetor_associativo();
                    if ($atributo->chave == 'OFK') {
                        $vetor = array(0 => 'Nenhum') + $vetor;
                    }
                    self::preparar_vetor_select($vetor);
                    $form->campo_select($atributo->nome, $atributo->nome, $vetor, $valor, $this->get_entidade_rel_uu($nome_obj), $this->disabled, 0, 0, $atributo->ajuda);
                    return true;

                // Se sao muitos elementos possiveis: campo relacionamento
                } else {
                    $form->campo_relacionamento($atributo->nome, $atributo->nome, $obj->get_classe(), $obj->get_chave(), $obj->get_campo_nome(), '', $valor, $atributo->maximo, 30, $atributo->get_label($this->id_form), 0, 0, 0, $mascara, $atributo->ajuda);
                    return true;
                }

            // Campo select simples
            } else {
                $obj = $this->get_objeto_rel_uu($atributo->nome, false);
                $nome_obj = $this->get_nome_rel_uu($atributo->nome);
                $vetor = $obj->vetor_associativo();
                if ($atributo->chave == 'OFK') {
                    $vetor = array(0 => 'Nenhum') + $vetor;
                }
                self::preparar_vetor_select($vetor);
                $form->campo-select($atributo->nome, $atributo->nome, $vetor, $valor, $this->get_entidade_rel_uu($nome_obj), $this->disabled, 0, 0, $atributo->ajuda);
                return true;
            }
        }

        // Campo select de valores
        $metodo = 'get_vetor_'.$atributo->nome;
        if (method_exists($this, $metodo)) {
            $vetor = $this->$metodo();
            self::preparar_vetor_select($vetor);
            $form->campo_select($atributo->nome, $atributo->nome, $vetor, $valor, $atributo->get_label($this->id_form), $this->disabled, 0, 0, $atributo->ajuda);
            return true;
        }

        // Campo password
        if (preg_match('/^(.+_)?(senha)(_.+)?$/', $atributo->nome)) {
            $form->campo_password($atributo->nome, $atributo->nome, $atributo->maximo, 30, $atributo->get_label($this->id_form), $this->disabled, 0, 0, $atributo->ajuda);
            return true;
        }

        // Campo bool
        if ($atributo->tipo == 'bool') {
            $form->campo_bool_radio($atributo->nome, $atributo->nome, $atributo->get_label($this->id_form), $valor, $this->disabled, false, false, $atributo->ajuda);
            return true;
        }

        // Campo data e/ou hora
        if ($atributo->tipo == 'data') {
            $ano_atual = (int)strftime('%Y');
            $anos_passado = null;
            $anos_futuro  = null;
            if ($atributo->data_inicio !== false) {
                switch ($atributo->tipo_data_inicio) {
                case ATRIBUTO_DATA_RELATIVA:
                    $anos_passado = $atributo->data_inicio;
                    break;
                case ATRIBUTO_DATA_ABSOLUTA:
                    $anos_passado = $ano_atual - $atributo->data_inicio;
                    break;
                }
            }
            if ($atributo->data_fim !== false) {
                switch ($atributo->tipo_data_fim) {
                case ATRIBUTO_DATA_RELATIVA:
                    $anos_futuro = $atributo->data_fim;
                    break;
                case ATRIBUTO_DATA_ABSOLUTA:
                    $anos_futuro = $atributo->data_fim - $ano_atual;
                    break;
                }
            }
            $data = objeto::parse_data($valor, false);
            $prefixo = $atributo->nome;

            switch ($atributo->campo_formulario) {
            case 'data':
                $form->campo_data($prefixo, $data['dia'], $data['mes'], $data['ano'], $atributo->get_label(), $anos_passado, $anos_futuro, $atributo->pode_vazio, 0, $atributo->ajuda);
                return true;
            case 'hora':
                $form->campo_hora($prefixo, $data['hora'], $data['minuto'], $data['segundo'], $atributo->get_label(), 0, $atributo->ajuda);
                return true;
            case 'data_hora':
            default:
                $form->inicio_bloco($atributo->get_label());
                $form->campo_data($prefixo, $data['dia'], $data['mes'], $data['ano'], 'Data', $anos_passado, $anos_futuro, $atributo->pode_vazio, 0, $atributo->ajuda);
                $form->campo_hora($prefixo, $data['hora'], $data['minuto'], $data['segundo'], 'Hora', 0, $atributo->ajuda);
                $form->fim_bloco();
                return true;
            }
        }

        // Campo de numeros
        if ($atributo->tipo == 'int') {

            // Quantidade baixa: Campo radio
            if (($atributo->maximo - $atributo->minimo) < 5) {
                $vetor = listas::numeros($atributo->minimo, $atributo->maximo, false, 1, true);
                $disabled = $this->disabled ? array_keys($vetor) : false;
                $form->campo_radio($atributo->nome, $atributo->nome, $vetor, $valor, $atributo->get_label($this->id_form), $disabled, 0, 0, $atributo->ajuda);
                return true;

            // Quantidade razoavel: Campo select
            } elseif (($atributo->maximo - $atributo->minimo) < 100) {
                $vetor = listas::numeros($atributo->minimo, $atributo->maximo, false, 1, true);
                $form->campo_select($atributo->nome, $atributo->nome, $vetor, $valor, $atributo->get_label($this->id_form), $this->disabled, 0, 0, $atributo->ajuda);
                return true;
            }
        }

        // Campo text ou textarea
        switch ($atributo->tipo) {
        case 'int':
            $maximo = strlen($atributo->maximo);
            $conv = validacao::get_convencoes_localidade();
            $pontos = $conv['thousands_sep'] !== '' ? floor($maximo / 3) : 0;
            $form->campo_text($atributo->nome, $atributo->nome, $valor, $maximo + $pontos, 30, $atributo->get_label($this->id_form), $this->disabled, false, false, $mascara, $atributo->ajuda);
            return true;
        case 'float':
            $form->campo_text($atributo->nome, $atributo->nome, $valor, strlen($atributo->maximo) + 10, 30, $atributo->get_label($this->id_form), $this->disabled, false, false, $mascara, $atributo->ajuda);
            return true;

        case 'string':
            if ($atributo->validacao != 'TEXTO') {
                $form->campo_text($atributo->nome, $atributo->nome, $valor, $atributo->maximo, 30, $atributo->get_label($this->id_form), $this->disabled, false, false, $mascara, $atributo->ajuda);
            } else {
                $form->campo_textarea($atributo->nome, $atributo->nome, $valor, 30, 5, $atributo->get_label($this->id_form), $this->disabled, 0, 0, $atributo->ajuda);
            }
            return true;

        case 'char':
            $form->campo_text($atributo->nome, $atributo->nome, $valor, 1, 5, $atributo->get_label($this->id_form), $this->disabled, 0, 0, 0, $atributo->ajuda);
            return true;

        default:
            $form->campo_text($atributo->nome, $atributo->nome, $valor, 1000, 30, $atributo->get_label($this->id_form), $this->disabled, false, false, $mascara, $atributo->ajuda);
            return true;
        }
    }


    //
    //     Prepara o vetor para ser usado em um campo_select
    //
    protected static function preparar_vetor_select(&$vetor) {
    // Array[Mixed] $vetor: vetor qualquer
    //
        $vetor2 = array();
        foreach ($vetor as $k => $v) {
            if (is_int($k) || is_float($k)) {
                $k = texto::numero($k);
            }
            if (is_array($v)) {
                self::preparar_vetor_select($v);
            }
            $vetor2[$k] = $v;
        }
        $vetor = $vetor2;
    }


    //
    //     Logica de geracao de um formulario generico
    //     Possiveis chaves/valores dos parametros extra:
    //     String $prefixo: prefixo do ID do formulario
    //     Array[String => Mixed] $opcoes: opcoes adicionais a serem inseridas nos dados
    //     String $class: nome da classe CSS utilizada
    //     Bool $ajax: usar ajax ou nao
    //     String $nome_botao: nome do botao de submeter os dados
    //     String $aviso_acesso: aviso caso o usuario nao tenha permissao de acesso ao formulario
    //     String $destino_formulario: destino do formulario ('imprimir_dados' ou 'imprimir_formulario')
    //
    public function formulario_generico(&$dados, &$campos, $action, $metodo, $extra = array()) {
    // Object $dados: dados submetidos
    // Array[String || String => Array[String]] || Bool $campos: campos do formulario de forma hierarquica (vetor de vetores) ou true (todos)
    // String $action: endereco para envio dos dados
    // String $metodo: nome do metodo que sera chamado para receber os dados (recebe por parametro $dados, $vt_campos, $opcoes)
    //
        global $USUARIO;

        $padrao = array(
            'nome_botao'         => 'Enviar',
            'aviso_acesso'       => 'N&atilde;o &eacute; poss&iacute;vel acessar este formul&aacute;rio no momento',
            'prefixo'            => '',
            'opcoes'             => false,
            'class'              => 'formulario',
            'ajax'               => true,
            'destino_formulario' => 'imprimir_formulario'
        );
        $extra = array_merge($padrao, $extra);

        $this->set_id_form($this->id_formulario_generico(), $extra['prefixo']);

        // Checar se pode acessar
        if (!$this->pode_acessar_formulario($USUARIO, $motivo)) {
            $aviso = $extra['aviso_acesso'];
            if ($motivo) {
                $aviso .= " (Motivo: {$motivo})";
            }
            mensagem::aviso($aviso);
            if (!empty($campos)) {
                $this->imprimir_dados($campos, false, false);
            } else {
                echo '<div class="dados">';
                echo '<p>Formul&aacute;rio indispon&iacute;vel</p>';
                echo '</div>';
            }
            return null;
        }

        if ($campos === true) {
            $campos = filtro_atributo::get_atributos_classe($this->get_classe(), '[S] , -[PK]');
        }
        $flag = OBJETO_IGNORAR_IMPLICITOS;
        $vt_campos = $this->get_campos_reais($campos, $objetos, $vetores, $flag);
        $form = $this->montar_formulario($action, $this->id_form, $extra['class'], $campos, $dados, $extra['opcoes'], $extra['nome_botao'], $extra['ajax']);

        if (!method_exists($this, $metodo)) {
            $this->erros[] = "O m&eacute;todo {$metodo} n&atilde;o existe";
            return false;
        }

        // Executar logica do formulario
        $r = $this->$metodo($dados, $vt_campos, $extra['opcoes']);

        if ($r === true) {
            if ($this->possui_avisos()) {
                $this->imprimir_avisos();
            }
            switch ($extra['destino_formulario']) {
            case 'imprimir_dados':
                $this->imprimir_dados($campos, false, false);
                break;

            case 'imprimir_formulario':
            default:
                $classe_formulario = 'formulario';
                if ($form instanceof $classe_formulario) {
                    $form->imprimir();
                } else {
                    trigger_error('O formulario possui erros e foi abortado', E_USER_ERROR);
                }
                break;
            }
        } else {
            $classe_formulario = 'formulario';
            if ($form instanceof $classe_formulario) {
                if ($this->possui_erros()) {
                    $this->imprimir_erros();
                } elseif ($this->possui_avisos()) {
                    $this->imprimir_avisos();
                }
                $form->imprimir();
            } else {
                trigger_error('O formulario possui erros e foi abortado', E_USER_ERROR);
            }
        }

        return $r;
    }


    //
    //     Logica de geracao de um formulario de cadastro de dados
    //     Possiveis chaves/valores dos parametros extra:
    //     String $prefixo: prefixo do ID do formulario
    //     Array[String => Mixed] $opcoes: opcoes adicionais a serem inseridas nos dados
    //     String $class: nome da classe CSS utilizada
    //     Bool $ajax: usar ajax ou nao
    //     Bool $outro: imprime um link para cadastrar outro elemento
    //     String $texto_link_outro: texto do link para cadastrar outro elemento
    //     String $link_outro: link para cadastrar outro elemento
    //     String $nome_botao: nome do botao de inserir dados
    //     String $aviso_acesso: aviso caso o usuario nao tenha permissao de acesso ao formulario
    //     Int $modo_transacao: tipo de transacao
    //
    public function formulario_inserir(&$dados, &$campos, $action, $extra = array()) {
    // Object $dados: dados submetidos
    // Array[String || String => Array[String]] || Bool $campos: campos do formulario de forma hierarquica (vetor de vetores) ou true (todos)
    // String $action: endereco para envio dos dados
    // Array[String => Mixed] $extra: vetor associativo com parametros extra (ver descricao do metodo)
    //
        global $USUARIO;

        $padrao = array(
            'nome_botao'     => 'Cadastrar',
            'aviso_acesso'   => 'N&atilde;o &eacute; poss&iacute;vel inserir um novo registro no momento',
            'prefixo'        => '',
            'opcoes'         => false,
            'class'          => 'formulario',
            'ajax'           => true,
            'outro'          => true,
            'modo_transacao' => DRIVER_BASE_MODO_PADRAO
        );
        $extra = array_merge($padrao, $extra);

        $this->set_id_form($this->id_formulario_inserir(), $extra['prefixo']);

        // Checar se pode inserir
        if (isset($USUARIO) && !$this->pode_acessar_formulario($USUARIO, $motivo)) {
            $aviso = $extra['aviso_acesso'];
            if ($motivo) {
                $aviso .= " (Motivo: {$motivo})";
            }
            mensagem::aviso($aviso);
            if (!empty($campos) && $this->existe()) {
                $this->imprimir_dados($campos, false, false);
            } else {
                echo '<div class="dados">';
                echo '<p>Formul&aacute;rio indispon&iacute;vel</p>';
                echo '</div>';
            }
            return null;
        }

        if ($campos === true) {
            $campos = filtro_atributo::get_atributos_classe($this->get_classe(), '[S] , -[PK]');
        }
        $flag = OBJETO_ADICIONAR_CHAVES | OBJETO_IGNORAR_IMPLICITOS;
        $vt_campos = $this->get_campos_reais($campos, $objetos, $vetores, $flag);
        $captcha = in_array('captcha', $campos);

        $form = $this->montar_formulario($action, $this->id_form, $extra['class'], $campos, $dados, $extra['opcoes'], $extra['nome_botao'], $extra['ajax']);

        $r = $this->logica_formulario($dados, $vt_campos, $extra['opcoes'], $captcha, $extra['modo_transacao']);
        if ($r === true) {
            if (empty($vt_campos)) {
                $nome = $this->get_campo_nome();
                if ($nome) {
                    $this->imprimir_dados(array($nome), false, false);
                } else {
                    echo '<div class="dados">';
                    switch ($this->get_genero()) {
                    case 'M':
                    case 'I':
                        echo $this->get_entidade().' cadastrado com sucesso';
                        break;
                    case 'F':
                        echo $this->get_entidade().' cadastrada com sucesso';
                        break;
                    }
                    echo '</div>';
                }
            } else {
                $this->imprimir_dados($campos, false, false);
            }
            if ($extra['outro']) {
                $link_outro = isset($extra['link_outro']) ? $extra['link_outro'] : $action;
                echo '<p>';
                if (isset($extra['texto_link_outro'])) {
                    link::texto($link_outro, $extra['texto_link_outro']);
                } else {
                    switch ($this->get_genero()) {
                    case 'M':
                        link::texto($link_outro, 'Cadastrar outro '.$this->get_entidade());
                        break;
                    case 'F':
                        link::texto($link_outro, 'Cadastrar outra '.$this->get_entidade());
                        break;
                    case 'I':
                        link::texto($link_outro, 'Cadastrar outro(a) '.$this->get_entidade());
                        break;
                    }
                }
                echo '</p>';
            }
        } else {
            $classe_formulario = 'formulario';
            if ($form instanceof $classe_formulario) {
                $form->imprimir();
            } else {
                trigger_error('O formulario possui erros e foi abortado', E_USER_ERROR);
            }
        }

        return $r;
    }


    //
    //     Logica de geracao de um formulario de alteracao de dados
    //     Possiveis chaves/valores dos parametros extra:
    //     Array[String => String] $opcoes: opcoes adicionais a serem inseridas nos dados
    //     String $class: nome da classe CSS utilizada
    //     Bool $ajax: usar ajax ou nao
    //     String $nome_botao: nome do botado de alterar os dados
    //     String $aviso_acesso: aviso caso o usuario nao tenha permissao de acesso ao formulario
    //     String $prefixo: prefixo do ID do formulario
    //     Int $modo_transacao: tipo de transacao
    //
    public function formulario_alterar(&$dados, &$campos, $action, $extra = array()) {
    // Object $dados: dados submetidos
    // Array[String || String => Array[String]] || Bool $campos: campos do formulario de forma hierarquica (vetor de vetores) ou true (todos)
    // String $action: endereco para envio dos dados
    // Array[String => Mixed] $extra: vetor associativo com parametros extra (ver descricao do metodo)
    //
        global $USUARIO;

        $padrao = array(
            'nome_botao'     => 'Alterar',
            'aviso_acesso'   => 'Este registro n&atilde;o pode ser alterado',
            'prefixo'        => '',
            'opcoes'         => false,
            'class'          => 'formulario',
            'ajax'           => true,
            'modo_transacao' => DRIVER_BASE_MODO_PADRAO
        );
        $extra = array_merge($padrao, $extra);

        if (!$this->existe()) {
            trigger_error('Para exibir o formulario precisa ter consultado o objeto', E_USER_WARNING);
            return false;
        }
        $this->set_id_form($this->id_formulario_alterar(), $extra['prefixo']);

        // Checar se pode alterar
        if (!$this->pode_acessar_formulario($USUARIO, $motivo)) {
            $aviso = $extra['aviso_acesso'];
            if ($motivo) {
                $aviso .= " (Motivo: {$motivo})";
            }
            mensagem::aviso($aviso);
            if (!empty($campos)) {
                $this->imprimir_dados($campos, false, false);
            } else {
                echo '<div class="dados">';
                echo '<p>Formul&aacute;rio indispon&iacute;vel</p>';
                echo '</div>';
            }
            return null;
        }

        if ($campos === true) {
            $campos = filtro_atributo::get_atributos_classe($this->get_classe(), '[S] , -[PK]');
        }
        $flag = OBJETO_ADICIONAR_CHAVES | OBJETO_IGNORAR_IMPLICITOS;
        $vt_campos = $this->get_campos_reais($campos, $objetos, $vetores, $flag);
        $captcha = in_array('captcha', $campos);

        $this->consultar_campos($vt_campos);
        $form = $this->montar_formulario($action, $this->id_form, $extra['class'], $campos, $dados, $extra['opcoes'], $extra['nome_botao'], $extra['ajax']);

        $r = $this->logica_formulario($dados, $vt_campos, $extra['opcoes'], $captcha, $extra['modo_transacao']);
        $classe_formulario = 'formulario';
        if ($form instanceof $classe_formulario) {
            if ($r === true) {
                $dados_vazios = array();
                $form = $this->montar_formulario($action, $this->id_form, $extra['class'], $campos, $dados_vazios, $extra['opcoes'], $extra['nome_botao'], $extra['ajax']);
            }
            $form->imprimir();
        } else {
            trigger_error('O formulario possui erros e foi abortado', E_USER_ERROR);
        }
        return $r;
    }


    //
    //     Logica de geracao de um formulario de exclusao
    //     Possiveis chaves/valores dos parametros extra:
    //     String $titulo: Define o titulo do formulario
    //     String $aviso_acesso: aviso caso o usuario nao tenha permissao de acesso ao formulario
    //     String $aviso: Define um aviso extra do formulario
    //     Int $modo_transacao: Define o modo de transacao (isolamento)
    //     String $nome_botao: nome do botao de excluir os dados
    //     String $mensagem_confirmacao: Mensagem do campo de confirmacao (checkbox)
    //     Bool $ajax: usar ajax ou nao
    //     String $class: nome da classe CSS utilizada
    //     String $prefixo: prefixo do ID do formulario
    //
    public function formulario_excluir(&$dados, &$campos, $action, $extra = array()) {
    // Object $dados: dados submetidos
    // Array[String || String => Array[String]] $campos: campos do formulario (true = todos)
    // String $action: endereco para envio dos dados
    // Array[String => Mixed] $extra: vetor associativo com parametros extra (ver descricao do metodo)
    //
        global $USUARIO;

        $padrao = array(
            'nome_botao'           => 'Excluir',
            'mensagem_confirmacao' => 'Marque para Confirmar',
            'aviso_acesso'         => 'Este registro n&atilde;o pode ser exclu&iacute;do',
            'ajax'                 => true,
            'class'                => 'formulario',
            'prefixo'              => '',
            'modo_transacao'       => DRIVER_BASE_MODO_PADRAO
        );
        $extra = array_merge($padrao, $extra);

        if (!$this->existe()) {
            trigger_error('Para excluir precisa ter consultado o objeto', E_USER_WARNING);
            return false;
        }
        $this->set_id_form($this->id_formulario_excluir(), $extra['prefixo']);

        // Checar se pode excluir
        if (!$this->pode_acessar_formulario($USUARIO, $motivo)) {
            $aviso = $extra['aviso_acesso'];
            if ($motivo) {
                $aviso .= " (Motivo: {$motivo})";
            }
            mensagem::aviso($aviso);
            if (!empty($campos)) {
                $this->imprimir_dados($campos, false, false);
            } else {
                echo '<div class="dados">';
                echo '<p>Formul&aacute;rio indispon&iacute;vel</p>';
                echo '</div>';
            }
            return null;
        }

        // Montar formulario
        $form = $this->montar_formulario_excluir($action, $campos, $extra);

        switch ($this->get_genero()) {
        case 'M':
            $titulo = 'Dados do '.$this->get_entidade().' exclu&iacute;do';
            break;
        case 'F':
            $titulo = 'Dados da '.$this->get_entidade().' exclu&iacute;da';
            break;
        case 'I':
            $titulo = 'Dados de '.$this->get_entidade().' exclu&iacute;do';
            break;
        }
        $dados_excluidos = $this->imprimir_dados($campos, true, false, $titulo);

        // Aplicar logica
        $r = $this->logica_formulario_excluir($dados, $extra['modo_transacao']);

        // Se excluiu: exibir dados excluidos
        if ($r) {
            echo $dados_excluidos;

        // Se nao excluiu: mostrar formulario
        } else {
            $form->imprimir();
        }

        return $r;
    }


    //
    //     Imprime um formulario (com checkbox) para adicionar ou remover relacionamentos 1:N simples
    //     Possiveis chaves/valores dos parametros extra:
    //     String $prefixo: prefixo do ID do formulario
    //     condicao_sql $condicoes: condicoes de filtro dos elementos do vetor
    //     Array[String] $disable: vetor de itens desabilitados
    //     String $class: nome da classe CSS usada
    //     Bool $ajax: usar Ajax ou nao
    //     String $nome_botao: nome do botao de salvar os dados
    //     String $aviso_acesso: aviso caso o usuario nao tenha permissao de acesso ao formulario
    //     Int $modo_transacao: tipo de transacao
    //
    public function formulario_relacionamento(&$dados, $action, $nome_vetor, $classe_relacionada, $extra = array()) {
    // Object $dados: dados submetidos
    // String $action: endereco de destino dos dados
    // String $nome_vetor: nome do vetor para listar os elementos
    // String $classe_relacionada: nome da classe do objeto a ser relacionado
    // Array[String => Mixed] $extra: vetor associativo com parametros extra (ver descricao do metodo)
    //
        global $USUARIO;

        $padrao = array(
            'nome_botao'           => 'Enviar',
            'aviso_acesso'         => 'Este registro n&atilde;o pode ser alterado',
            'ajax'                 => true,
            'class'                => 'formulario',
            'prefixo'              => '',
            'condicoes'            => condicao_sql::vazia(),
            'disable'              => array(),
            'modo_transacao'       => DRIVER_BASE_MODO_PADRAO
        );
        $extra = array_merge($padrao, $extra);

        if (!$this->possui_rel_un($nome_vetor)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o vetor "'.$nome_vetor.'"', E_USER_ERROR);
        }

        $this->set_id_form($this->id_formulario_relacionamento(), $extra['prefixo']);

        // Checar se pode alterar
        if (!$this->pode_acessar_formulario($USUARIO, $motivo)) {
            $aviso = $extra['aviso_acesso'];
            if ($motivo) {
                $aviso .= " (Motivo: {$motivo})";
            }
            mensagem::aviso($aviso);
            return null;
        }

        // A ideia do metodo e' relacionar this com um objeto relacionado:
        // Objeto              Relacionamento         Objeto Relacionado
        // $this  <----------------> $rel <----------------> $obj

        // [1] RECUPERAR DADOS DO RELACIONAMENTO
        $relacionamento = $this->get_definicao_rel_un($nome_vetor);
        $classe = $relacionamento->classe;
        $rel = objeto::get_objeto($classe);
        $label = $rel->get_entidade(true);

        // [2] VETOR DE ITENS POSSIVEIS
        $obj = objeto::get_objeto($classe_relacionada);
        $chave_obj = $obj->get_chave();
        $vt_itens  = $obj->vetor_associativo(false, false, $extra['condicoes']);

        // [3] LOGICA DE NEGOCIOS PARA RELACIONAMENTOS
        $vt_chaves = array_keys($vt_itens);
        $r = $this->logica_formulario_relacionamento($dados, $chave_obj, $nome_vetor, $vt_chaves, $extra['disable'], $extra['modo_transacao']);

        // [4] VETOR DE ITENS CHECADOS
        $vt_check = array();
        $vt_rel = $this->get_vetor_rel_un($nome_vetor);
        foreach ($vt_rel as $rel) {
            $chave = $rel->$chave_obj;
            $vt_check[] = $chave;
        }

        // [5] FINALMENTE, IMPRIMIR O FORMULARIO
        $form = new formulario($action, $this->id_form, $extra['class'], 'post', $extra['ajax']);
        if (count($vt_itens) <= 30) {
            $form->campo_checkbox($chave_obj, $chave_obj, $vt_itens, $vt_check, $label, 1, $extra['disable'], 1);
        } else {
            $form->campo_listbox($chave_obj, $chave_obj, $vt_itens, $vt_check, $label, 10, $extra['disable']);
        }
        $form->campo_hidden('id_form', $this->id_form);
        $form->campo_submit($this->id_salvar(), $this->id_salvar(), $extra['nome_botao'], 1, 1);
        $form->imprimir();

        // [6] RETORNAR O RESULTADO DA LOGICA
        return $r;
    }


    //
    //     Logica de geracao de formulario de Importacao de Dados por arquivos XML
    //     Possiveis chaves/valores dos parametros extra:
    //     String $prefixo: prefixo do ID do formulario
    //     Array[String] $campos: vetor com os nomes dos campos obrigatorios
    //     Array[String => String] $opcoes: opcoes adicionais a serem inseridas em cada registro
    //     String $class: classe CSS usada no formulario
    //     String $nome_botao: nome do botao de enviar os dados
    //     Int $modo_transacao: tipo de transacao
    //
    public function formulario_importar_xml(&$dados, &$arquivos, $action, $extra = array()) {
    // Object $dados: dados submetidos
    // Array[String => Object] $arquivos: vetor com os arquivos submetidos
    // String $action: endereco de destino dos dados
    // Array[String => Mixed] $extra: vetor associativo com parametros extra (ver descricao do metodo)
    //
        if (!ini_get('file_uploads')) {
            echo '<p>Este sistema n&atilde;o est&aacute; configurado para aceitar o envio de arquivos.</p>';
            return false;
        }
        $padrao = array(
            'nome_botao'           => 'Enviar',
            'class'                => 'formulario',
            'prefixo'              => '',
            'campos'               => array(),
            'opcoes'               => array(),
            'modo_transacao'       => DRIVER_BASE_MODO_PADRAO
        );
        $extra = array_merge($padrao, $extra);
        $this->set_id_form($this->id_formulario_importar_xml(), $extra['prefixo']);
        $r = $this->logica_formulario_importar_xml($dados, $arquivos, $extra['campos'], $extra['opcoes'], $extra['modo_transacao']);

        // Imprimir o formulario
        $form = new formulario($action, $this->id_form, $extra['class'], 'post', false);
        $form->campo_file('arquivo', 'arquivo', 'Arquivo', 2097152);
        $form->campo_bool('direto_bd', 'direto_bd', 'Importar diretamente para o BD', $dados->direto_bd);
        $form->campo_hidden('id_form', $this->id_form);
        $form->campo_submit($this->id_salvar(), $this->id_salvar(), $extra['nome_botao'], 1, 1);
        $form->imprimir();

        return $r;
    }


    //
    //     Logica de geracao de formulario de Importacao de Dados por arquivos CSV
    //     Possiveis chaves/valores dos parametros extra:
    //     String $prefixo: prefixo do ID do formulario
    //     Array[String] $campos: vetor com os nomes dos campos obrigatorios
    //     Array[String => String] $opcoes: opcoes adicionais a serem inseridas em cada registro
    //     String $class: classe CSS usada no formulario
    //     String $nome_botao: nome do botao de enviar os dados
    //     Int $modo_transacao: tipo de transacao
    //
    public function formulario_importar_csv(&$dados, &$arquivos, $action, $extra = array()) {
    // Object $dados: dados submetidos
    // Array[String => Object] $arquivos: vetor com os arquivos submetidos
    // String $action: endereco de destino dos dados
    // Array[String => Mixed] $extra: vetor associativo com parametros extra (ver descricao do metodo)
    //
        if (!ini_get('file_uploads')) {
            echo '<p>Este sistema n&atilde;o est&aacute; configurado para aceitar o envio de arquivos.</p>';
            return false;
        }
        $padrao = array(
            'nome_botao'           => 'Enviar',
            'class'                => 'formulario',
            'prefixo'              => '',
            'campos'               => array(),
            'opcoes'               => array(),
            'modo_transacao'       => DRIVER_BASE_MODO_PADRAO
        );
        $extra = array_merge($padrao, $extra);

        $this->set_id_form($this->id_formulario_importar_csv(), $extra['prefixo']);
        $r = $this->logica_formulario_importar_csv($dados, $arquivos, $extra['campos'], $extra['opcoes'], $extra['modo_transacao']);

        // Imprimir o formulario
        $form = new formulario($action, $this->id_form, $extra['class'], 'post', false);
        $form->campo_file('arquivo', 'arquivo', 'Arquivo', 2097152);
        $form->campo_text('separador', 'separador', $dados->separador, 1, 3, 'Separador');
        $form->campo_text('aspas', 'aspas', $dados->aspas, 1, 3, 'Aspas');
        $form->campo_bool('direto_bd', 'direto_bd', 'Importar diretamente para o BD', $dados->direto_bd);
        $form->campo_hidden('id_form', $this->id_form);
        $form->campo_submit($this->id_salvar(), $this->id_salvar(), $extra['nome_botao'], 1, 1);
        $form->imprimir();

        return $r;
    }


/// @ METODOS DE INTERFACE DIRETA COM O USUARIO (VIEW)


    //
    //     Retorna um formulario com os campos especificados e permitidos
    //
    final public function montar_formulario($action, $id_form, $class, $campos, &$valores, $opcoes = false, $botao = false, $ajax = true) {
    // String $action: endereco para onde os dados sao enviados
    // String $id_form: nome do formulario
    // String $class: nome da classe CSS utilizada
    // Array[String || String => Array[String]] $campos: vetor com os campos do formulario de forma hierarquica (vetor de vetor)
    // Object $valores: valores a serem preenchidos automaticamente
    // Array[String => String] $opcoes: opcoes adicionais a serem inseridas nos dados
    // String $botao: nome do botao do formulario
    // Bool $ajax: usar ajax ou nao
    //
        // Se os dados nao foram submetidos, utilizar os campos da entidade
        $classe = $this->get_classe();

        // Se nao submeteu os dados do formulario
        if (!isset($valores->id_form)) {
            $flag = OBJETO_ADICIONAR_CHAVES | OBJETO_IGNORAR_IMPLICITOS;
            $vt_campos = $this->get_campos_reais($campos, $objetos, $vetores, $flag);
            if ($this->existe()) {
                $valores = new stdClass();
                $valores->$classe = $this->get_dados($vt_campos);
            } elseif ($valores) {
                $vt_campos = array();
                foreach ($campos as $campo) {
                    if (is_array($campo)) {
                        $vt_campos = array_merge($vt_campos, $campo);
                    } else {
                        $vt_campos[] = $campo;
                    }
                }
                $this->montar_opcoes($valores, $vt_campos, $opcoes);
                $vt_campos = objeto::converter_notacao_vetor($vt_campos);
                $valores->$classe = $this->converter_componentes($valores->$classe, $vt_campos);
            } else {
                $valores = new stdClass();
            }
            $valores->default = true;

        // Converte os dados submetidos em campos separados nos respectivos atributos
        } else {
            $vt_campos = array();
            foreach ($campos as $campo) {
                if (is_array($campo)) {
                    $vt_campos = array_merge($vt_campos, $campo);
                } else {
                    $vt_campos[] = $campo;
                }
            }
            $this->montar_opcoes($valores, $vt_campos, $opcoes);
            $vt_campos = objeto::converter_notacao_vetor($vt_campos);
            $valores->$classe = $this->converter_componentes($valores->$classe, $vt_campos);
        }

        // Montar o formulario
        $form = new formulario($action, $id_form, $class, 'post', $ajax);
        foreach ($campos as $chave => $campo) {
            if (is_array($campo)) {
                $form->inicio_bloco($chave, false, false, 'fieldset_'.md5($chave));
                foreach ($campo as $c) {
                    if ($c == '-') {
                        $form->campo_generico('<hr />');
                    } elseif (!$this->inserir_campo_formulario($form, $c, $valores, $id_form)) {
                        return false;
                    }
                }
                $form->fim_bloco();
            } else {
                if ($campo == '-') {
                    $form->campo_generico('<hr />');
                } elseif (!$this->inserir_campo_formulario($form, $campo, $valores, $id_form)) {
                    return false;
                }
            }
        }
        $form->set_nome(array());
        $form->campo_hidden('id_form', $this->id_form);
        if ($botao) {
            $form->campo_submit($this->id_salvar(), $this->id_salvar(), $botao, 1, 1);
        }
        return $form;
    }


    //
    //     Monta um formulario de excluir (sem o botao)
    //
    private function montar_formulario_excluir($action, $campos, $extra) {
    // String $action: endereco de destino dos dados
    // Array[String] $campos: campos a serem mostrados para confirmar a exclusao
    // Array[String => Mixed] $extra: dados extra
    //
        // Cria o formulario
        $form = new formulario($action, $this->id_form, $extra['class'], 'post', $extra['ajax']);

        // Imprime uma confirmacao
        if (isset($extra['titulo'])) {
            $form->titulo_formulario($extra['titulo']);
        } else {
            switch ($this->get_genero()) {
            case 'M':
                $form->titulo_formulario('Voc&ecirc; tem certeza que deseja excluir este '.$this->get_entidade().'?');
                break;
            case 'F':
                $form->titulo_formulario('Voc&ecirc; tem certeza que deseja excluir esta '.$this->get_entidade().'?');
                break;
            case 'I':
                $form->titulo_formulario('Voc&ecirc; tem certeza que deseja excluir este(a) '.$this->get_entidade().'?');
                break;
            }
        }

        if (isset($extra['aviso'])) {
            $form->campo_aviso($extra['aviso']);
        }

        // Imprime dados sobre o elemento a ser removido
        if ($campos === true) {
            $campos = filtro_atributo::get_atributos_classe($this->get_classe(), '[S]');
        }
        $dados_entidade = '';
        foreach ($campos as $chave => $campo) {
            if (is_array($campo)) {
                $dados_entidade .= "<fieldset><legend>{$chave}</legend>\n";
                foreach ($campo as $c) {
                    $dados_entidade .= $this->imprimir_atributo($c, 1);
                }
                $dados_entidade .= "</fieldset>\n";
             } else {
                $dados_entidade .= $this->imprimir_atributo($campo, 1);
            }
        }
        $form->campo_informacao($dados_entidade);

        // Gera uma chave para garantir a remocao
        $chave_exclusao = $this->chave_exclusao();
        $form->campo_hidden('chave_exclusao', $chave_exclusao);
        $form->campo_hidden('id_form', $this->id_form);
        $form->campo_bool('confirmacao', 'confirmacao', $extra['mensagem_confirmacao'], 0);
        $form->campo_submit($this->id_salvar(), $this->id_salvar(), $extra['nome_botao'], true);
        return $form;
    }


    //
    //     Tenta inserir um campo no formulario
    //
    final protected function inserir_campo_formulario(&$form, $campo, &$valores, $id_form) {
    // Object $form: formulario que vai adicionar o campo
    // String $campo: nome do atributo desejado
    // Object $valores: valores a serem preenchidos automaticamente
    // String $id_form: nome do formulario
    //
        $classe = $this->get_classe();
        $default = '';

        $vt_atributo = explode(':', $campo);
        $nome_campo = array_pop($vt_atributo);

        // Se e' um campo captcha
        if (strtolower($nome_campo) == 'captcha') {
            $form->campo_captcha();
            $this->names[] = 'captcha';

        // Se e' um atributo da propria classe
        } elseif (empty($vt_atributo)) {
            if ($this->possui_rel_uu($nome_campo)) {
                $nome_campo = $this->get_nome_chave_rel_uu($nome_campo);
            }
            $valor = isset($valores->$classe->$nome_campo) ? $valores->$classe->$nome_campo : null;
            $form->set_nome($this->get_classe());
            $inseriu_campo = $this->inserir_campo_formulario_asterisco($form, $nome_campo, $valor);

            if (!$inseriu_campo) {
                trigger_error('O campo "'.$nome_campo.'" nao foi especificado na entidade, nem no metodo campo_formulario (o metodo deveria retornar um valor "true" e esta retornando um "'.util::exibir_var($inseriu_campo).'")', E_USER_ERROR);
            } else {
                $this->names[] = $nome_campo;
                if (FORMULARIO_AJAX && $this->get_info_campo($nome_campo)) {
                    $id_campo = $form->montar_id($nome_campo);
                    $meta_valor = base64_encode($this->get_classe().':'.$nome_campo.':'.$this->id_form);
                    $form->meta_informacao($id_campo, $meta_valor);
                }
            }

        // Se e' o atributo de um objeto filho
        } else {
            if ($this->__isset(implode(':', $vt_atributo))) {
                $obj = '$valores->'.$classe.'->'.implode('->', $vt_atributo).'->'.$nome_campo;
                $php = '$valor = isset('.$obj.') ? '.$obj.' : null;';
                eval($php);

                $vt_nome = array_merge(array($this->get_classe()),
                                       $vt_atributo);

                $nome_objeto = implode(':', $vt_atributo);
                $form->set_nome($vt_nome);

                $obj_filho = $this->get_objeto_rel_uu($nome_objeto);
                $obj_filho->set_id_form($this->id_form);
                $inseriu_campo = $obj_filho->inserir_campo_formulario_asterisco($form, $nome_campo, $valor);

                if (!$inseriu_campo) {
                    trigger_error('O campo "'.$nome_campo.'" nao foi especificado na entidade, nem no metodo campo_formulario (o metodo retornou um tipo "'.util::get_tipo($inseriu_campo).'" e deveria retornar um "bool")', E_USER_ERROR);
                } else {
                    if (FORMULARIO_AJAX && $this->get_objeto_rel_uu($nome_objeto)->get_info_campo($nome_campo)) {
                        $id_campo = $form->montar_id($nome_campo);
                        $meta_valor = base64_encode($this->get_objeto_rel_uu($nome_objeto)->get_classe().':'.$nome_campo.':'.$this->id_form);
                        $form->meta_informacao($id_campo, $meta_valor);
                    }

                    util::definir_vetor_nivel($this->names, array_merge($vt_atributo, array(null)), $nome_campo);
                }
            }
        }
        return true;
    }


    //
    //     Insere um campo no formulario incluindo um * ou nao
    //     (de acordo com configuracao da constante OBJETO_FORMULARIO_ASTERISCO_OBRIGATORIO e definicao da validacao do campo)
    //
    final protected function inserir_campo_formulario_asterisco(&$form, $nome_campo, $valor) {
    // Object $form: formulario que vai adicionar o campo
    // String $nome_campo: nome do atributo desejado
    // Object $valor: valor a ser preenchido automaticamente
    //
        $inseriu_campo = false;
        $asterisco = '<span class="obrigatorio">*</span>';

        if (OBJETO_FORMULARIO_ASTERISCO_OBRIGATORIO && $this->possui_atributo($nome_campo)) {
            $def_validacao = $this->get_definicao_atributo_validacao($nome_campo);
            if (!$def_validacao->pode_vazio) {
                $descricao = $this->definicao->atributos[$nome_campo]->descricao;
                $label     = $this->definicao->atributos[$nome_campo]->label;

                $this->definicao->atributos[$nome_campo]->descricao = $descricao.$asterisco;
                if (is_string($label)) {
                    $this->definicao->atributos[$nome_campo]->label = $label.$asterisco;
                } elseif (is_array($label)) {
                    $label_novo = array();
                    foreach ($label as $i => $l) {
                        $label_novo[$i] = $l.$asterisco;
                    }
                    $this->definicao->atributos[$nome_campo]->label = $label_novo;
                }

                // Inserir o campo
                $inseriu_campo = $this->campo_formulario($form, $nome_campo, $valor);

                // Voltar definicao para o estado inicial
                $this->definicao->atributos[$nome_campo]->descricao = $descricao;
                if ($label) {
                    $this->definicao->atributos[$nome_campo]->label = $label;
                }
            } else {
                $inseriu_campo = $this->campo_formulario($form, $nome_campo, $valor);
            }
        } else {
            $inseriu_campo = $this->campo_formulario($form, $nome_campo, $valor);
        }
        return $inseriu_campo;
    }

}//class
