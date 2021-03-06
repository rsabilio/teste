<?php
//
// SIMP
// Descricao: Classe Abstrata Objeto (classe base para as entidades do sistema)
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.3.5.10
// Data: 06/08/2007
// Modificado: 12/07/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

/// @ CONSTANTES

// Geral
define('OBJETO_DIR_CLASSES',         $CFG->dirclasses);      // Caminho para o diretorio de classes
define('OBJETO_CACHE_INSTANCIAS',    'cache');               // Indice no vetor $_SESSION reservado para guardar instancias
define('OBJETO_CACHE_DEFINICOES',    'cache_def');           // Indice no vetor $_SESSION reservado para guardar definicoes
define('OBJETO_FLAG_COLETA_PADRAO',  true);                  // Valor padrao da flag de coleta de instancias vazias

// Flags usadas no metodo get_campos_reais
define('OBJETO_REMOVER_CONSULTADOS', 1);     // 0001 Indica que deve remover os elementos consultados no metodo get_campos_reais
define('OBJETO_ADICIONAR_NOMES',     2);     // 0010 Indica que deve incluir os campos usados para o nome dos objetos no metodo get_campos_reais
define('OBJETO_ADICIONAR_CHAVES',    4);     // 0100 Indica que deve incluir as chaves dos objetos no metodo get_campos_reais
define('OBJETO_IGNORAR_IMPLICITOS',  8);     // 1000 Indica que nao deve buscar os campos reais utilizados em campos implicitos

// Modos de persistencia das instancias da memoria e do BD
define('OBJETO_MODO_CONGELAR',       1);     // Mantem os dados consultados/atualizados das instancias da memoria intactas ao consultar dados do BD
define('OBJETO_MODO_SOBRESCREVER',   2);     // Sobrescreve as instancias da memoria ao consultar qualquer dado do BD


abstract class objeto implements Iterator, Serializable {


/// @ ATRIBUTOS

    // Array[String => Object]: Vetor que armazena as definicoes das classes entidade
    static protected $definicoes;

    // &Object: Referencia para a definicao da entidade corrente (aponta para uma posicao do vetor self::$definicoes)
    protected $definicao;
    // $classe;               // String: Nome da classe
    // $singleton;            // Bool: Indica se a classe possui apenas uma instancia no BD
    // $entidade;             // Object: Dados do nome da entidade
    // -- $singular;          // String Nome da entidade no singular
    // -- $plural;            // String: Nome da entidade no plural
    // -- $genero;            // Char: Genero da entidade ('M' = Masculino / 'F' = Feminino / 'I' = Indeterminado)
    // $tabela;               // Array[String => Mixed]: Dados da tabela no BD
    // -- $nome;              // String: Nome da tabela no BD
    // -- $descricao;         // String: Descricao da Tabela no BD (ate' 60 caracteres)
    // -- $chave;             // String: Nome da chave primaria unica
    // $atributos;            // Array[String => atributo]: Vetor com os nomes dos atributos simples apontando para suas definicoes
    // $rel_uu;               // Array[String => Object]: Vetor com os nomes das chaves estrangeiras 1:1 apontando para as definicoes dos relacionamentos
    // -- $classe;            // String: Nome da classe relacionada (que possui a chave original do relacionamento)
    // -- $nome;              // String: Nome do objeto no vetor $this->instancia->objetos
    // -- $forte;             // Bool: Indica se o relacionamento e' forte (exige entidade valida) ou fraco (permite qualquer valor)
    // -- $descricao;         // String: Nome da entidade no singular que sobrepoe a definicao original da classe (opcional)
    // $rel_un;               // Array[String => Object]: Vetor com os nomes dos vetores apontando para as definicoes dos relacionamentos
    // -- $classe;            // String: Nome da classe relacionada (elementos do vetor)
    // -- $chave_fk;          // String: Nome da chave que e' estrangeira na tabela relacionada
    // -- $index;             // String: Nome do atributo usado na indexacao padrao do vetor
    // -- $impressao;         // String: Nome do atributo usado para impressao dos elementos do vetor
    // -- $ordem;             // Array[String => Bool] || String: Nome(s) do(s) atributo(s) usados para ordenacao padrao
    // -- $descricao;         // String: Descricao do vetor no plural que sobrepoe a definicao original da classe relacionada (opcional)
    // $implicitos;           // Array[String => Object]: Vetor com os nomes dos atributos implicitos apontando para as suas definicoes
    // -- $descricao;         // String: Descricao do atributo implicito para impressao
    // -- $metodo;            // String: Nome do metodo (da classe corrente) que retorna o atributo implicito
    // -- $atributos;         // Array[String]: Vetor de atributos simples necessarios para montar o atributo implicito
    // $uk_compostas;         // Array[Array[Int]]: Vetor de chaves compostas representadas por vetores com as posicoes dos atributos simples no vetor de atributos ($this->definicao->atributos)

    // Array[String => Array[Int => Object]]: Matriz de classes que armazenam vetores com objetos instanciados (indexado pela chave da instancia)
    static protected $instancias;

    // &Object: Referencia para os dados da instancia corrente (aponta para uma posicao do vetor self::$instancias)
    protected $instancia;
    // $valores;         // Array[String => Mixed]: Valores dos atributos simples da instancia (indexado pelo nome do atributo) / Se o atributo nao estiver presente, ele nao foi consultado
    // $objetos;         // Array[String => Object]: Objetos relacionados (1:1) (indexado pelo nome do objeto) / Se o objeto nao estiver presente, ele nao foi consultado
    // $vetores;         // Array[String => Array[Object]]: Vetores relacionados (1:N) (indexado pelo nome do vetor) / Se o vetor nao estiver presente, ele nao foi consultado
    // $flag_mudanca;    // Array[Int => Bool]: Indica os atributos simples modificados (indexado pela posicao do atributo no vetor $this->instancia->valores)
    // $flag_unicidade;  // Bool: Flag que indica se deve ser feita a validacao de unicidade
    // $referencias;     // Int: Quantidade de referencias para a instancia
    // $orfa;            // Bool: Indica se a instancia foi inserida no vetor de instancias ou nao

    // Erros e Avisos Internos
    protected $erros;               // Array[String]: Erros a serem exibidos para o usuario final
    protected $avisos;              // Array[String]: Avisos a serem exibidos para o usuario final

    // Atributos Secundarios
    protected $auxiliares;          // Array[String => Mixed]: Atributos auxiliares
    protected $id_form;             // String: ID do formulario corrente

    // Atributos estaticos
    static protected $dao;                   // objeto_dao: Camada de acesso a base de dados
    static protected $em_transacao;          // Bool: Flag que indica se uma transacao esta em andamento
    static protected $flag_bd;               // Bool: Flag que indica se os dados vem do BD ou nao
    static protected $flag_log;              // Bool: Flag que indica se os logs devem ser gerados ou nao
    static protected $modo;                  // Int: Flag que define o modo com que a persistencia dos dados da memoria e do BD serao sincronizados
    static protected $flag_coleta;           // Bool: Flag que indica se o coletor de lixo sera' chamado automaticamente ou nao
    static protected $consultas_por_demanda; // Int: Quantidade de consultas feitas sob demanda a um atributo especifico


/// @ METODOS EXIGIDOS PELAS CLASSES FILHAS


    //
    //     Deve apenas chamar o metodo criar_entidade com os dados basicos da entidade
    //
    abstract protected function definir_entidade();


    //
    //     Chama os metodos que definem os atributos da entidade (adicionar_atributo, adicionar_atributo_implicito, adicionar_rel_uu e adicionar_rel_un)
    //
    abstract protected function definir_atributos();


/// @ METODOS QUE PODEM SER SOBRECARREGADOS


    //public function get_campo_nome()
    //public function validacao_final(&$dados)
    //public function pre_salvar(&$salvar_campos)
    //public function pos_salvar()
    //public function pre_imprimir_dados($campos)
    //public function pos_imprimir_dados($campos)
    //public function dados_opcao($opcao, $modulo)
    //public function exibir_atributo($nome_atributo)
    //public function exibir_atributo_implicito($nome_atributo)
    //public function imprimir_objeto_rel_uu($nome_atributo, $return = false, $imprimir_descricao = true, $descricao_alternativa = false)
    //public function imprimir_vetor_rel_un($nome_vetor, $return = false, $imprimir_descricao = true)
    //public function pode_exibir(&$usuario, &$motivo = '')
    //public function get_definicao_atributo_validacao($nome_atributo)
    //protected function converter_componente($campo, $valor, $valores)


/// @ METODOS PARA MANIPULAR AS DEFINICOES/INSTANCIAS DAS ENTIDADES


    //
    //     Cria a definicao da entidade (caso ainda nao exista) e uma instancia vazia
    //
    final protected function criar_entidade($entidade, $entidade_plural, $genero, $classe, $tabela = false, $descricao = false, $singleton = false) {
    // String $entidade: nome da entidade no singular
    // String $entidade_plural: nome da entidade no plural
    // Char $genero: genero da entidade 'M' (masculino), 'F' (feminino) ou 'I' (indeterminado)
    // String $classe: nome da classe entidade
    // String $tabela: nome da tabela usada no BD (por padrao, usa-se o mesmo nome que a classe)
    // String $descricao: descricao da tabela usada no BD
    // Bool $singleton: flag indicando se a classe possui apenas uma instancia ou nao
    //
        // Definir propriedades gerais da entidade ($this->definicao)
        // comuns a todos os objetos da entidade
        $this->definir_classe($classe, $tabela, $descricao, $singleton);
        $this->set_nome_entidade($entidade, $entidade_plural, $genero);

        // Checar a consistencia da definicao (leva aproximadamente 0.00006 segundos por classe)
        if (!DEVEL_BLOQUEADO) {
            if ($classe != 'config') {
                $this->validar_integridade_entidade();
            }
        }

        // Definir uma nova instancia da classe ($this->instancia)
        $this->instancia = $this->criar_instancia();

        // Flags da instancia corrente
        $this->zerar_flags();

        // Mensagens de erros e avisos internos da instancia corrente
        $this->limpar_erros();
        $this->limpar_avisos();

        // Atributos secundarios da instancia corrente
        $this->limpar_auxiliares();
        $this->id_form = '';
    }


    //
    //     Define o genero e o nome da entidade no singular e plural
    //
    final public function set_nome_entidade($singular = false, $plural = false, $genero = false) {
    // String $singular: nome da entidade no singular
    // String $plural: nome da entidade no plural
    // Char $genero: genero da entidade 'M' (masculino), 'F' (feminino) ou 'I' (indeterminado)
    //
        if ($singular) {
            $this->definicao->entidade->singular = $singular;
        }
        if ($plural) {
            $this->definicao->entidade->plural = $plural;
        }
        if ($genero) {
            $genero = (string)$genero;
            $this->definicao->entidade->genero = $genero;
        }
    }


    //
    //     Define os dados da classe da entidade corrente e seus atributos
    //
    private function definir_classe($classe, $tabela, $descricao, $singleton) {
    // String $classe: nome da classe
    // String $tabela: nome da tabela usada no BD
    // String $descricao: descricao da tabela usada no BD (ate' 60 caracteres)
    // Bool $singleton: indica se a classe possui apenas uma instancia ou nao
    //
        // Se a classe ja' foi definida: apenas apontar a definicao local para a global correspondente
        if (self::possui_definicao_classe($classe)) {
            $this->definicao = &self::$definicoes[$classe];
            return;
        }

        // Se a classe ainda nao foi definida: criar a definicao global pela primeira vez

        // Validar os atributos informados
        if ($tabela === false) {
            $tabela = $classe;
        }
        if ($descricao === false) {
            $descricao = 'Tabela '.$tabela;
        }

        // Converter os atributos informados
        $classe    = (string)$classe;
        $tabela    = (string)$tabela;
        $descricao = (string)$descricao;
        $singleton = (bool)$singleton;

        // Criar a definicao global e apontar a definicao local para a global correspondente
        self::criar_definicao_classe($classe, $tabela, $descricao, $singleton);
        $this->definicao = &self::$definicoes[$classe];

        // Preencher a definicao global com os atributos da entidade (metodo abstrato)
        $this->definir_atributos();
    }


    //
    //     Valida a integridade de uma entidade disparando triggers
    //
    private function validar_integridade_entidade() {
        $classe = $this->get_classe();
        $tabela = $this->get_tabela();

        // Checar se a classe existe
        if (!class_exists($classe)) {
            trigger_error('A classe "'.$classe.'" nao existe', E_USER_ERROR);
        }

        // Checar se o nome da tabela e' valido
        if (empty($tabela)) {
            trigger_error('O nome da tabela nao pode ser nulo ('.$classe.')', E_USER_ERROR);
        }

        // Checar se a descricao da tabela e' valido
        $desc_tabela = $this->get_tabela(true);
        if ($desc_tabela != texto::strip_acentos(texto::decodificar($desc_tabela))) {
            trigger_error('A descricao da tabela nao pode ter acentos ('.$classe.')', E_USER_ERROR);
        } elseif (strlen($desc_tabela) > 60) {
            trigger_error('A descricao da tabela deve ter no maximo 60 caracteres ('.$classe.')', E_USER_ERROR);
        }

        // Checar se a entidade possui uma chave primaria
        if (!$this->get_chave()) {
            trigger_error('Toda entidade precisa de uma chave primaria', E_USER_ERROR);
        }

        // Checar o genero da entidade
        if (!preg_match('/^[MFI]$/', $this->definicao->entidade->genero)) {
            trigger_error('Genero invalido "'.$this->definicao->entidade->genero.'"', E_USER_ERROR);
        }

        // Checar o nome da entidade
        $tr = array(
            '&amp;'  => '',
            '&#x26;' => '',
            '&#038;' => ''
        );

        // Checar o nome da entidade no singular
        if (!is_string($this->definicao->entidade->singular)) {
            trigger_error('O nome da entidade deve ser uma string ('.gettype($$this->definicao->entidade->singular).')', E_USER_ERROR);
        } else {
            $teste = strtr($this->definicao->entidade->singular, $tr);
            $teste = html_entity_decode($teste, ENT_QUOTES, 'UTF-8');
            if (strpos($teste, '&') !== false) {
                trigger_error('A entidade "'.$classe.'" nao possui uma descricao valida no singular (checar entities)', E_USER_ERROR);
            }
        }

        // Checar o nome da entidade no plural
        if (!is_string($this->definicao->entidade->plural)) {
            trigger_error('O nome da entidade no plural deve ser uma string ('.gettype($this->definicao->entidade->plural).')', E_USER_ERROR);
        } else {
            $teste = strtr($this->definicao->entidade->plural, $tr);
            $teste = html_entity_decode($teste, ENT_QUOTES, 'UTF-8');
            if (strpos($teste, '&') !== false) {
                trigger_error('A entidade "'.$classe.'" nao possui uma descricao valida no plural (checar entities)', E_USER_ERROR);
            }
        }

        // Checar se a entidade possui um campo identificador
        $campo_nome = $this->get_campo_nome();
        if (!$campo_nome) {
            trigger_error('A classe "'.$classe.'" nao possui um campo que pode ser usado como nome (recomenda-se implementar o metodo get_campo_nome)', E_USER_NOTICE);
        } elseif (!is_string($campo_nome)) {
            trigger_error('O metodo get_campo_nome deve retornar sempre uma string ou false', E_USER_ERROR);
        }

        // Checar se algum nome de atributo foi usado duas vezes na mesma entidade
        $nomes_usados = array();
        foreach ($this->get_atributos() as $nome => $def) {
            $nomes_usados[$nome] = true;
        }
        foreach ($this->get_definicoes_rel_uu() as $nome_chave => $def) {
            $nome_obj = $def->nome;
            if (!isset($nomes_usados[$nome_obj])) {
                $nomes_usados[$nome_obj] = true;
            } else {
                trigger_error('O nome do objeto "'.$nome_obj.'" ja foi usado por algum atributo da classe "'.$this->get_classe().'"', E_USER_ERROR);
            }
        }
        foreach ($this->get_definicoes_rel_un() as $nome_vet => $def) {
            if (!isset($nomes_usados[$nome_vet])) {
                $nomes_usados[$nome_vet] = true;
            } else {
                trigger_error('O nome do vetor "'.$nome_vet.'" ja foi usado por algum atributo da classe "'.$this->get_classe().'"', E_USER_ERROR);
            }
        }
    }


    //
    //     Verifica se a definicao da classe existe
    //
    final static protected function possui_definicao_classe($classe) {
    // String $classe: nome da classe
    //
        return isset(self::$definicoes[$classe]);
    }


    //
    //     Retorna a definicao de uma classe
    //
    final static protected function get_definicao_classe($classe) {
    // String $classe: nome da classe
    //
        if (self::possui_definicao_classe($classe)) {
            return self::$definicoes[$classe];
        }
        trigger_error('Nao existe a definicao da classe "'.$classe.'"', E_USER_WARNING);
        return false;
    }


    //
    //     Limpa as definicoes de classes nao utilizadas (sem instancias) para economizar memoria
    //
    final static public function limpar_definicoes_classes() {
        $removidas = 0;
        reset(self::$definicoes);
        foreach (self::$definicoes as $classe => $def) {
            if (!isset(self::$instancias[$classe]) || empty(self::$instancias[$classe])) {
                unset(self::$definicoes[$classe]);
                ++$removidas;
            }
        }
        return $removidas;
    }


    //
    //     Cria a definicao de uma classe sem os atributos
    //
    static private function criar_definicao_classe($classe, $tabela, $descricao, $singleton) {
    // String $classe: nome da classe
    // String $tabela: nome da tabela
    // String $descricao: descricao da tabela
    // Bool $singleton: indica se a classe possui apenas uma instancia ou nao
    //
        // Gerar objeto com as caracteristicas da classe
        $definicao = new stdClass();

        // Propriedades basicas
        $definicao->classe = $classe;
        $definicao->singleton = $singleton;

        // Propriedades da tabela usada no BD
        $definicao->tabela = new stdClass();
        $definicao->tabela->nome      = $tabela;
        $definicao->tabela->descricao = $descricao;
        $definicao->tabela->chave     = false;

        // Propriedades da entidade (serao preenchidas pelo metodo set_nome_entidade)
        $definicao->entidade = new stdClass();
        $definicao->entidade->singular = '';
        $definicao->entidade->plural   = '';
        $definicao->entidade->genero   = 'I';

        // Atributos (serao preenchidos pelo metodo definir_atributos)
        $definicao->atributos    = array();
        $definicao->rel_uu       = array();
        $definicao->rel_un       = array();
        $definicao->implicitos   = array();
        $definicao->uk_compostas = array();

        if (isset(self::$definicoes[$classe])) {
            trigger_error('A entidade "'.$classe.'" foi definida mais de uma vez', E_USER_NOTICE);
        }

        // Armazenar a definicao da classe no vetor de definicoes globais
        self::$definicoes[$classe] = $definicao;

        // Criar um vetor de instancias da classe criada
        self::$instancias[$classe] = array();
    }


    //
    //     Define uma nova instancia para a classe
    //     (a instancia da chave precisa existir no vetor de instancias)
    //
    private function definir_instancia($classe, $chave = false) {
    // String $classe: nome da classe
    // Mixed $chave: chave primaria da instancia
    //
        // Se esta' tentando apontar para a mesma instancia
        if ($this->instancia === self::$instancias[$classe][$chave]) {
            return;
        }

        // Coletar a instancia atual
        $this->coletar_instancia();

        // Apontar a instancia para a que ja' existe
        $this->instancia = &self::$instancias[$classe][$chave];
        $this->instancia->referencias += 1;
    }


    //
    //     Retorna se existe uma instancia de uma classe com a chave informada
    //
    static private function possui_instancia($classe, $chave) {
    // String $classe: nome da classe
    // Mixed $chave: valor da chave primaria
    //
        return isset(self::$instancias[$classe][$chave]);
    }


    //
    //     Retorna um vetor de instancias de uma classe
    //
    final static public function get_instancias($classe) {
    // String $classe: nome da classe
    //
        // Se a classe foi definida, retornar o vetor de instancias
        if (self::possui_definicao_classe($classe)) {
            return self::$instancias[$classe];
        }

        // Se a classe nao foi definida, nao tem instancia nenhuma
        return array();

        // Nao emite trigger_error pois e' possivel que a classe simplesmente
        // nao tenha sido definida ainda, mas seja valida e sem instancias
    }


    //
    //     Cria uma nova instancia vazia da classe atual
    //
    private function criar_instancia() {
        $obj = new stdClass();
        $obj->valores        = array();
        $obj->objetos        = array();
        $obj->vetores        = array();
        $obj->flag_mudanca   = array_fill(0, count($this->get_atributos()), false);
        $obj->flag_unicidade = true;
        $obj->referencias    = 1;
        $obj->orfa           = true;

        return $obj;
    }


    //
    //     Limpa os atributos simples e relacionamentos da instancia corrente
    //
    private function limpar_instancia() {

        // Coletar a instancia atual
        $this->coletar_instancia();

        // Remover a referencia da instancia
        $null = null;
        $this->instancia = &$null;
        unset($null);

        // Receber uma instancia nova e vazia
        $this->instancia = $this->criar_instancia();
    }


    //
    //     Coleta as instancias orfas da memoria, liberando espaco.
    //     Observacao: caso a flag self::$flag_coleta esteja habilitada,
    //     esta coleta e' feita automaticamente.
    //
    final public static function coletar_lixo() {
        $quantidade = 0;
        foreach (self::$instancias as $classe => $instancias) {
            foreach ($instancias as $codigo => $instancia) {
                if ($instancia->referencias == 0) {
                    if (self::remover_instancia($classe, $codigo)) {
                        ++$quantidade;
                    }
                }
            }
        }
        return $quantidade;
    }


    //
    //     Desaloca as instancias da lista de instancias.
    //     Cuidado: se um objeto do PHP esta' referenciando uma
    //     instancia do Simp e ela e' apagada, o objeto nao
    //     funcionara' adequadamente.
    //     Prefira utilizar o metodo coletar_lixo.
    //
    final public static function remover_instancias($classe) {
    // String $classe: nome da classe da instancia
    //
        if (isset(self::$instancias[$classe])) {
            self::$instancias[$classe] = array();
            return true;
        }
        return false;
    }


    //
    //     Desaloca uma instancia da lista de instancias
    //     Cuidado: se um objeto do PHP esta' referenciando uma
    //     instancia do Simp e ela e' apagada, o objeto nao
    //     funcionara' adequadamente.
    //     Prefira utilizar o metodo coletar_lixo.
    //
    final public static function remover_instancia($classe, $codigo) {
    // String $classe: nome da classe da instancia
    // Int $codigo: codigo unico da instancia
    //
        // Se nao possui a definicao da classe
        if (!isset(self::$definicoes[$classe])) {
            return false;
        }

        // Nao precisa remover instancias singleton (ocupa muito pouco de memoria)
        if (self::$definicoes[$classe]->singleton) {
            return false;
        }

        // Checar numero de instancias
        if (isset(self::$instancias[$classe][$codigo])) {
            $referencias = self::$instancias[$classe][$codigo]->referencias;
            if ($referencias == 0) {
                unset(self::$instancias[$classe][$codigo]);
                return true;
            }
        }
        return false;
    }


    //
    //     Retorna um XML com as caracteristicas da classe
    //
    final public function get_definicao_xml() {
        $classe   = $this->get_classe();
        $singular = texto::decodificar($this->get_entidade());
        $plural   = texto::decodificar($this->get_entidade(true));
        $genero   = $this->get_genero();

        // Atributos Simples
        $vt_atributos = array();
        foreach ($this->get_atributos() as $atributo) {
            $vt_atributos[] = $atributo->get_definicao_xml();
        }
        $atributos = implode("\n", $vt_atributos);

        // Atributos Implicitos
        $vt_atributos = array();
        foreach ($this->get_implicitos() as $atributo => $def) {
            $vt_atributos[] = '<atributo>'.
                              '<nome><![CDATA['.$atributo.']]></nome>'.
                              '<descricao><![CDATA['.texto::decodificar($def->descricao).']]></descricao>'.
                              '</atributo>';
        }
        $implicitos = implode("\n", $vt_atributos);

        // Relacionamentos 1:1
        $vt_atributos = array();
        foreach ($this->get_definicoes_rel_uu() as $chave => $def) {
            $vt_atributos[] = '<atributo>'.
                              '<nome><![CDATA['.$def->nome.']]></nome>'.
                              '<descricao><![CDATA['.texto::decodificar($def->descricao).']]></descricao>'.
                              '<classe><![CDATA['.$def->classe.']]></classe>'.
                              '<forte><![CDATA['.util::exibir_var($def->forte, UTIL_EXIBIR_TEXTO).']]></forte>'.
                              '<chave><![CDATA['.$chave.']]></chave>'.
                              '</atributo>';
        }
        $rel_uu = implode("\n", $vt_atributos);

        // Relacionamentos 1:N
        $vt_atributos = array();
        foreach ($this->get_definicoes_rel_un() as $vetor => $def) {
            $vt_atributos[] = '<atributo>'.
                              '<nome><![CDATA['.$vetor.']]></nome>'.
                              '<classe><![CDATA['.$def->classe.']]></classe>'.
                              '<chave_fk><![CDATA['.$def->chave_fk.']]></chave_fk>'.
                              '<index><![CDATA['.$def->index.']]></index>'.
                              '<ordem><![CDATA['.$def->ordem.']]></ordem>'.
                              '</atributo>';
        }
        $rel_un = implode("\n", $vt_atributos);

        $arquivo = parser_simp::get_cabecalho_arquivo(OBJETO_DIR_CLASSES.'/entidade/'.$classe.'.class.php');

        return <<<XML
<classe>
  <descricao>
    <nome><![CDATA[{$classe}]]></nome>
    <singular><![CDATA[{$singular}]]></singular>
    <plural><![CDATA[{$plural}]]></plural>
    <genero><![CDATA[{$genero}]]></genero>
    <arquivo>
      <sistema><![CDATA[{$arquivo->sistema}]]></sistema>
      <versao><![CDATA[{$arquivo->versao}]]></versao>
      <data><![CDATA[{$arquivo->data}]]></data>
      <modificacao><![CDATA[{$arquivo->modificado}]]></modificacao>
      <autor>
        <nome><![CDATA[{$arquivo->autor}]]></nome>
        <email><![CDATA[{$arquivo->email}]]></email>
        <orgao><![CDATA[{$arquivo->orgao}]]></orgao>
      </autor>
    </arquivo>
  </descricao>
  <atributos>
{$atributos}
  </atributos>
  <implicitos>
{$implicitos}
  </implicitos>
  <rel_uu>
{$rel_uu}
  </rel_uu>
  <rel_un>
{$rel_un}
  </rel_un>
</classe>
XML;
    }


    //
    //     Retorna um XML com as caracteristicas de um atributo da classe
    //
    final public function get_definicao_atributo_xml($atributo) {
    // String $atributo: nome do atributo
    //
        if (!$this->possui_atributo($atributo)) {
            return ;
        }
        $def = $this->get_definicao_atributo($atributo);
        return $this->gerar_definicao_atributo_xml($def);
    }


    //
    //     Gera um XML com as caracteristicas de uma definicao de atributo
    //
    final public function gerar_definicao_atributo_xml($definicao) {
    // atributo $definicao: definicao do atributo
    //
        $xml = "<atributo>\n";
        $xml .= "  <descricao><![CDATA[".texto::decodificar($definicao->descricao)."]]></descricao>\n";
        $xml .= "  <tipo>{$definicao->tipo}</tipo>\n";
        $xml .= "  <chave>".$definicao->chave."</chave>\n";
        $xml .= "  <enum>".(method_exists($this, 'get_vetor_'.$definicao->nome) ? '1' : '0')."</enum>\n";
        $xml .= "  <unico>".($definicao->unico ? '1' : '0')."</unico>\n";
        $xml .= "  <pode_vazio>".($definicao->pode_vazio ? '1' : '0')."</pode_vazio>\n";
        switch ($definicao->tipo) {
        case 'float':
            $xml .= "  <casas_decimais>{$definicao->casas_decimais}</casas_decimais>\n";
            $xml .= "  <fixo>{$definicao->fixo}</fixo>\n";
        case 'int':
        case 'string':
        case 'binario':
        case 'data':
            $xml .= "  <minimo>{$definicao->minimo}</minimo>\n";
            $xml .= "  <maximo>{$definicao->maximo}</maximo>\n";
            break;
        }
        if ($definicao->validacao) {
            $def_validacao = validacao::get_definicao_tipo($definicao->validacao);

            $xml .= "  <validacao>\n";
            $xml .= "    <nome>{$definicao->validacao}</nome>\n";
            $xml .= "    <padrao><![CDATA[".texto::decodificar($def_validacao->padrao)."]]></padrao>\n";
            $xml .= "    <permite><![CDATA[".texto::decodificar($def_validacao->permite)."]]></permite>\n";
            if ($definicao->ajuda) {
                $xml .= "    <instrucoes><![CDATA[".texto::decodificar($definicao->ajuda)."]]></instrucoes>\n";
            } else {
                $xml .= "    <instrucoes><![CDATA[".texto::decodificar($def_validacao->instrucoes)."]]></instrucoes>\n";
            }
            if ($definicao->exemplo) {
                $xml .= "    <exemplo><![CDATA[".texto::decodificar($definicao->exemplo)."]]></exemplo>\n";
            } else {
                $xml .= "    <exemplo><![CDATA[".texto::decodificar($def_validacao->exemplo)."]]></exemplo>\n";
            }
            $xml .= "  </validacao>\n";
        }
        $xml .= "</atributo>";
        return $xml;
    }


/// @ METODOS ESPECIFICOS DA CLASSE OBJETO


    //
    //     Inicializador da classe objeto (construtor dos atributos estaticos)
    //
    final static public function iniciar() {
        static $iniciou = false;
        if (!$iniciou) {
            self::$dao = new objeto_dao();
            self::$definicoes            = array();
            self::$instancias            = array();
            self::$em_transacao          = false;
            self::$flag_log              = true;
            self::$modo                  = OBJETO_MODO_CONGELAR;
            self::$flag_coleta           = OBJETO_FLAG_COLETA_PADRAO;
            self::$consultas_por_demanda = 0;
            $iniciou = true;
        } else {
            trigger_error('O metodo iniciar so precisa ser chamado uma vez', E_USER_NOTICE);
        }
    }


    //
    //     Construtor padrao, que consulta um objeto por uma chave e valor
    //
    final public function __construct($chave = false, $valor = false, $campos = false, $consultar_memoria = true) {
    // String || Bool $chave: chave da busca ou false para usar a chave primaria
    // Mixed $valor: valor da busca (false para nao consultar nada)
    // Array[String] || Bool $campos: vetor de atributos simples ou implicitos desejados (true = todos | false = apenas PK)
    // Bool $consultar_memoria: indica se deve consultar primeiro na memoria (lista de instancias)
    //
        // Definir dados gerais da entidade
        // (Metodo que chama o metodo criar_entidade)
        // Ao final, deve estar preenchida a definicao da entidade ($this->definicao)
        // e a instancia atual deve estar vazia ($this->instancia)
        $this->definir_entidade();

        // Consultar a entidade pela chave e valor
        if (self::$dao->carregou('objeto')) {
            if ($this->singleton()) {
                if (!$campos) {
                    $campos = array();
                    foreach ($this->get_atributos() as $atributo) {
                        switch ($atributo->chave) {
                        case 'PK':
                        case 'FK':
                        case  'OFK':
                            break;
                        default:
                            $campos[] = $atributo->nome;
                            break;
                        }
                    }
                }
                $this->consultar('', 1, $campos, null, $consultar_memoria);
            } elseif ($valor !== false) {
                $this->consultar($chave, $valor, $campos, null, $consultar_memoria);
            }
        }
    }


    //
    //     Destrutor padrao
    //
    final public function __destruct() {
        $this->coletar_instancia();

        // Remover referencias
        $null = null;
        $this->instancia = &$null;
        $this->definicao = &$null;
    }


    //
    //     Operacoes ao se clonar um objeto
    //
    final public function __clone() {
        $this->instancia->referencias += 1;
    }


    //
    //     Apenas para informar chamadas invalidas
    //
    final public function __call($metodo, $args) {
    // String $metodo: nome do metodo invocado indevidamente
    // Array[String => Mixed] $args: vetor de argumentos informados
    //
        trigger_error('Metodo desconhecido "'.$metodo.'"', E_USER_ERROR);
    }


/* *****************************
//TODO Liberar o metodo quando o PHP 5.3.0 estiver mais popular
// Este metodo serve para simplificar chamadas ao metodo get_objeto:
// $obj = objeto::get_objeto('usuario');
//
// Transformando em uma forma mais simples:
// $obj = objeto::usuario();
//
// Ou criando uma entidade ja existente
// $obj = objeto::usuario('login', 'admin');


    //
    //     Gera e retorna um novo objeto da classe especificada
    //
    public static function __callStatic($metodo, $args) {
        $classe = $metodo;
        if (count($args) >= 2) {
            $chave = $args[0];
            $valor = $args[1];
            $campos = isset($args[2]) ? $args[2] : false;
            $consultar_memoria = isset($args[3]) ? $args[3] : true;
            $obj = new $classe($chave, $valor, $campos, $consultar_memoria);
        } else {
            $obj = new $classe();
        }
        return $obj;
    }
*************************** */


    //
    //     Gera e retorna um novo objeto da classe especificada
    //
    final static public function get_objeto($classe) {
    // String $classe: nome da classe do objeto a ser retornado
    //
        $obj = new $classe();
        if (is_subclass_of($obj, __CLASS__)) {
            return $obj;
        }
        unset($obj);
        return null;
    }


    //
    //     Obtem um objeto pai especificando-se o nome da classe pai
    //
    final public function get_pai($classe_pai, $atributo = false, $campos = false, $consultar_memoria = true) {
    // String $classe_pai: nome da classe pai
    // String || Bool $atributo: nome do atributo do pai que aponta para um objeto da classe corrente (false para usar o nome da chave primaria da classe corrente)
    // Array[String] || Bool $campos: vetor de atributos simples ou implicitos desejados (true = todos | false = apenas PK)
    // Bool $consultar_memoria: indica se deve consultar primeiro da memoria (lista de instancias)
    //
        // Criar instancia do objeto pai
        $classe_pai = (string)$classe_pai;
        $pai = self::get_objeto($classe_pai);

        // Checar se o pai possui o objeto filho
        if (!$atributo) {
            $atributo = $this->get_chave();
        } elseif (!$pai->possui_atributo($atributo)) {
            trigger_error('A classe '.$classe_pai.' nao possui o atributo '.$atributo, E_USER_WARNING);
            return null;
        }

        // Checar se o filho existe para indentificar o pai
        if (!$this->existe()) {
            return $pai;
        }

        // Consultar o pai pelo valor da chave do filho
        $pai->consultar($atributo, $this->get_valor_chave(), $campos, null, $consultar_memoria);
        if (!$pai->existe()) {
            $pai->limpar_objeto();
        }

        // Retorna um objeto (preenchido ou nao)
        return $pai;
    }


    //
    //     Verifica se um objeto possui a classe especificada como pai
    //
    final public function possui_pai($classe_pai, $atributo) {
    // String $classe_pai: nome da classe pai
    // String || Bool $atributo: nome do atributo do pai que aponta para um objeto da classe corrente (false para usar o nome da chave primaria da classe corrente)
    //
        // Criar instancia do objeto pai
        $classe_pai = (string)$classe_pai;
        $pai = self::get_objeto($classe_pai);

        // Checar se foi informado o nome do atributo filho
        if (!$atributo) {
            $atributo = $this->get_chave();
        }

        return $pai->possui_atributo($atributo);
    }


    //
    //     Retorna o valor de um atributo simples da instancia (checar se foi consultado antes de usar para evitar NOTICE)
    //
    private function get_valor($nome_atributo) {
    // String $nome_atributo: nome do atributo a ser obtido
    //
        return $this->instancia->valores[$nome_atributo];
    }


    //
    //     Define o valor de um atributo simples da instancia
    //
    private function set_valor($nome_atributo, $valor) {
    // String $nome_atributo: nome do atributo a ser modificado
    // Mixed $valor: novo valor do atributo
    //
        $this->instancia->valores[$nome_atributo] = $valor;
        return true;
    }


    //
    //     Apaga o valor de um atributo da instancia indicando que ele nao foi consultado
    //
    private function desalocar_valor($nome_atributo) {
    // String $nome_atributo: nome do atributo a ser desalocado
    //
        unset($this->instancia->valores[$nome_atributo]);
        $this->set_flag_mudanca($nome_atributo, false);
    }


    //
    //     Define as flags com os valores padrao
    //
    private function zerar_flags() {
        $this->instancia->flag_unicidade = true;
    }


    //
    //     Define se a flag de BD esta ativada ou nao
    //
    final public static function set_flag_bd($flag) {
    // Bool $flag: valor da flag
    //
        self::$flag_bd = (bool)$flag;
    }


    //
    //     Obtem o valor da flag de BD
    //
    final public static function get_flag_bd() {
        return self::$flag_bd;
    }


    //
    //     Define se um atributo simples foi modificado ou nao
    //
    final protected function set_flag_mudanca($atributo, $valor) {
    // String $atributo: nome do atributo
    // Bool $valor: valor da flag
    //
        // Se usou a notacao objeto:atributo
        if (strpos($atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Obter a posicao do atributo no vetor de flags
        $pos_atributo = array_search($atributo, array_keys($this->get_atributos()));
        if ($pos_atributo === false) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$atributo.'"', E_USER_WARNING);
            return;
        }

        // Alterar a flag do atributo correspondente
        $this->instancia->flag_mudanca[$pos_atributo] = (bool)$valor;
    }


    //
    //     Checa se um atributo foi modificado ou nao
    //
    final public function get_flag_mudanca($atributo) {
    // String $atributo: nome do atributo
    //
        // Se usou a notacao objeto:atributo
        if (strpos($atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Obter a posicao do atributo no vetor de flags
        $pos_atributo = array_search($atributo, array_keys($this->get_atributos()));
        if ($pos_atributo === false) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$atributo.'"', E_USER_WARNING);
            return false;
        }

        // Retornar a flag correspondente
        return $this->instancia->flag_mudanca[$pos_atributo];
    }


    //
    //     Checa se um atributo foi consultado ou nao
    //
    final public function get_flag_consulta($atributo) {
    // String $atributo: nome do atributo
    //
        // Se usou a notacao objeto:atributo
        $pos = strpos($atributo, ':');
        if ($pos !== false) {
            $objeto = substr($atributo, 0, $pos);
            $resto = substr($atributo, $pos + 1);
            $chave = $this->get_nome_chave_rel_uu($objeto);
            return $this->get_flag_consulta($chave) && $this->get_objeto_rel_uu($objeto)->get_flag_consulta($resto);
        }
        return (bool)isset($this->instancia->valores[$atributo]);
    }


    //
    //     Define o modo de persistencia entre entidades da memoria e do BD
    //
    final static public function set_modo_persistencia($modo) {
    // Int $modo: codigo do modo (ver constantes)
    //
        switch ($modo) {
        case OBJETO_MODO_SOBRESCREVER:
        case OBJETO_MODO_CONGELAR:
            self::$modo = (int)$modo;
            break;
        default:
            trigger_error('Modo invalido "'.$modo.'" para o tipo de persistencia', E_USER_WARNING);
            break;
        }
    }


    //
    //     Coleta uma instancia caso ela nao esteja referenciada
    //
    private function coletar_instancia() {

        // Se existem referencias para a instancia
        if (isset($this->instancia->referencias) && $this->instancia->referencias > 0) {

            // Decrementar quantidade de referencias
            $this->instancia->referencias -= 1;

            // Se a instancia nao e' orfa (esta' no vetor de instancias)
            if (!$this->instancia->orfa) {

                // Se nao existem referencias e a flag de coleta esta' habilitada: remover instancia
                if (self::$flag_coleta && ($this->instancia->referencias == 0) && $this->existe()) {
                    self::remover_instancia($this->get_classe(), $this->get_valor_chave());
                }

            // Se e' uma instancia orfa
            } else {
                $null = null;
                $this->instancia = &$null;
            }
        }
    }


    //
    //     Define o modo de coleta de instancias orfas da memoria
    //
    final static public function set_flag_coleta_lixo($coletar_automatico = true) {
    // Bool $coletar_automatico: flag indicando se a coleta de instancias orfas deve ser automatica ou nao
    //
        self::$flag_coleta = (bool)$coletar_automatico;
    }


    //
    //     Obtem o modo de persistencia
    //
    final static public function get_modo_persistencia() {
        return self::$modo;
    }


    //
    //     Limpa o vetor de erros
    //
    final public function limpar_erros() {
        $this->erros = array();
    }


    //
    //     Limpa o vetor de avisos
    //
    final public function limpar_avisos() {
        $this->avisos = array();
    }


    //
    //     Limpa o vetor de atributos auxiliares
    //
    final public function limpar_auxiliares() {
        $this->auxiliares = array();
    }


    //
    //     Limpa o objeto como se ele acabasse de ser instanciado (apaga dados, flags, auxiliares, erros e avisos)
    //
    final public function limpar_objeto() {
        $this->limpar_auxiliares();
        $this->limpar_erros();
        $this->limpar_avisos();
        $this->limpar_instancia();
        $this->zerar_flags();
    }


    //
    //     Metodo SET: atribui valores a atributos simples ou auxiliares (atualiza objetos de relacionamentos 1:1 caso seja atribuido um valor a uma chave estrangeira)
    //
    final public function __set($nome_atributo, $valor) {
    // String $nome_atributo: nome do atributo simples ou na notacao objeto:atributo
    // Mixed $valor: valor a ser atribuido
    //
        // Se usou a notacao objeto:atributo
        $pos = strpos($nome_atributo, ':');
        if ($pos !== false) {
            $nome_filho = substr($nome_atributo, 0, $pos);
            $resto = substr($nome_atributo, $pos + 1);

            // Se esta definindo um valor para um objeto pai
            if (substr($nome_filho, 0, 1) == '^') {
                $nome_pai = substr($nome_filho, 1);
                $pos = strpos($nome_pai, '.');
                if ($pos !== false) {
                    $atributo_pai = substr($nome_pai, $pos + 1);
                    $nome_pai = substr($nome_pai, 0, $pos);
                } else {
                    $atributo_pai = false;
                }
                $r = $this->get_pai($nome_pai, $atributo_pai)->__set($resto, $valor);
                return $r;

            // Se esta definindo um valor para um objeto filho
            } elseif ($this->possui_rel_uu($nome_filho)) {
                $r = $this->get_objeto_rel_uu($nome_filho)->__set($resto, $valor);
                return $r;

            // Se nao existe pai nem filho com o nome especificado
            } else {
                trigger_error('A classe "'.$this->get_classe().'" nao possui o objeto "'.$nome_obj.'"', E_USER_WARNING);
                return false;
            }
        }

        // Se esta' atribuindo um objeto relacionado
        if ($this->possui_rel_uu($nome_atributo)) {
            if (!($valor instanceof self)) {
                trigger_error('Tipo invalido de valor "'.util::get_tipo($valor).'" (esperado um objeto da classe objeto)', E_USER_ERROR);
            }
            $def = $this->get_definicao_rel_uu($nome_atributo);
            if ($def->forte && !$valor->existe()) {
                trigger_error('O relacionamento forte so aceita objetos que existam', E_USER_ERROR);
            }
            $chave = $this->get_nome_chave_rel_uu($nome_atributo);
            return $this->__set($chave, $valor->get_valor_chave());
        }

        // Se nao possui o atributo simples: armazena em um vetor de valores auxiliares
        if (!$this->possui_atributo($nome_atributo)) {
            return $this->set_auxiliar($nome_atributo, $valor);
        }

        // Definir valor do atributo simples
        return $this->set_atributo($nome_atributo, $valor);
    }


    //
    //     Metodo UNSET: limpa o valor de um atributo simples ou auxiliar
    //
    final public function __unset($nome_atributo) {
    // String $nome_atributo: atributo a ser zerado
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        if ($this->possui_atributo($nome_atributo)) {

            // Se esta' limpando a chave primaria
            if ($this->get_chave() == $nome_atributo) {
                trigger_error('Nao pode atribuir um valor para a chave primaria', E_USER_WARNING);
                return;

            // Se esta' limpando um atributo qualquer
            } else {
                $this->desalocar_valor($nome_atributo);
            }
        } elseif ($this->possui_auxiliar($nome_atributo)) {
            unset($this->auxiliares[$nome_atributo]);
        } else {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$nome_atributo.'"', E_USER_WARNING);
        }
    }


    //
    //     Realiza um __set de acordo com o modo corrente (self::$modo)
    //
    private function set_acordo_modo($campo, $valor, $sobrescrever = false) {
    // String $campo: nome do campo
    // Mixed $valor: valor do campo
    // Bool $sobrescrever: indica se deve forcar a sobrescrita
    //
        $r = true;

        // Se forcou a sobrescrita
        if ($sobrescrever) {
            $r = $this->__set($campo, $valor);

        // Se e' um atributo simples e nao forcou a sobrescrita
        } else {

            // Checar o modo de persistencia
            switch (self::get_modo_persistencia()) {

            // Sobrescrever todos campos
            case OBJETO_MODO_SOBRESCREVER:
                $r = $this->__set($campo, $valor);
                break;

            // Apenas setar valores nao consultados
            case OBJETO_MODO_CONGELAR:
                if (!$this->get_flag_consulta($campo)) {
                    $r = $this->__set($campo, $valor);
                }
                break;
            }
        }
        return $r;
    }


    //
    //     Metodo GET: obtem valores de atributos simples, implicitos, relacionamentos ou auxiliares
    //
    final public function __get($nome_atributo) {
    // String $nome_atributo: nome do atributo a ser obtido do objeto
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        $retorno = null;

        // Se possui o atributo simples
        if ($this->possui_atributo($nome_atributo)) {
            $retorno = $this->get_atributo($nome_atributo);

        // Se possui o atributo implicito
        } elseif ($this->possui_atributo_implicito($nome_atributo)) {
            $retorno = $this->get_atributo_implicito($nome_atributo);

        // Se possui o objeto de relacionamento 1:1
        } elseif ($this->possui_rel_uu($nome_atributo)) {
            $retorno = $this->get_objeto_rel_uu($nome_atributo);

        // Se possui o vetor de relacionamento 1:N
        } elseif ($this->possui_rel_un($nome_atributo)) {
            $retorno = $this->get_vetor_rel_un($nome_atributo);

        // Se possui um valor auxiliar com o nome especificado
        } elseif ($this->possui_auxiliar($nome_atributo)) {
            $retorno = $this->get_auxiliar($nome_atributo);

        // Se nao existe em nenhum lugar
        } else {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$nome_atributo.'"', E_USER_WARNING);
        }

        return $retorno;
    }


    //
    //     Metodo ISSET: checa se existe o atributo desejado (simples, implicito, relacionamento ou auxiliar)
    //
    final public function __isset($nome_atributo) {
    // String $nome_atributo: nome do atributo a ser checado (aceita notacao objeto:atributo)
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        return $this->possui_atributo($nome_atributo) ||
               $this->possui_atributo_implicito($nome_atributo) ||
               $this->possui_rel_uu($nome_atributo) ||
               $this->possui_rel_un($nome_atributo) ||
               $this->possui_auxiliar($nome_atributo);
    }


    //
    //     Retorna o nome do objeto em formato legivel (sinonimo de get_nome)
    //
    final public function __toString() {
        return (string)$this->get_nome();
    }


    //
    //     Retorna o NOME DO ATRIBUTO (simples, implicito ou relacionamento 1:1) usado para identificar o objeto
    //
    public function get_campo_nome() {
        static $campo = array();

        // Se ja' achou o campo anteriormente, retorna-lo
        if (isset($campo[$this->get_classe()])) {
            return $campo[$this->get_classe()];
        }

        // Procurar pelos seguintes atributos
        $possiveis_atributos = array(
            'nome',
            'titulo',
            'descricao',
            'nome_breve',
            'sigla',
            'detalhes',
            'razao_social',
            'valor'
        );

        foreach ($possiveis_atributos as $atributo) {

            // Se encontrou um atributo que pode ser usado para nome:
            // guardar o nome do atributo em um vetor estatico para facilitar
            // as proximas buscas e retornar o nome encontrado
            if ($this->possui_atributo($atributo) || $this->possui_atributo_implicito($atributo)) {
                $campo[$this->get_classe()] = $atributo;
                return $atributo;
            }
        }

        // Se nao achou: guardar false no vetor estatico
        $campo[$this->get_classe()] = false;
        return false;
    }


    //
    //     Retorna o nome da entidade ou alguma notacao que a identifique
    //
    final public function get_nome() {
        $atributo = $this->get_campo_nome();
        if ($atributo) {
            if ($this->possui_rel_uu($atributo)) {
                return $this->get_objeto_rel_uu($atributo)->get_nome();
            }
            return $this->exibir($atributo);
        }

        // Se nao possui nenhum atributo que pode ser usado como nome,
        // montar um nome no formato "nome_classe#valor_chave"
        return $this->get_classe().'#'.$this->get_valor_chave();
    }


    //
    //     Retorna um vetor com os nomes dos atributos e suas definicoes (Array[String => Object])
    //
    final public function get_atributos() {
        return $this->definicao->atributos;
    }


    //
    //     Retorna um vetor com os nomes dos atributos com um determinado prefixo
    //
    final public function get_atributos_prefixo($prefixo = '', $remover = false) {
    // String $prefixo: prefixo usado antes de cada atributo
    // Array[String] $remover: nome dos atributos que nao se deseja no resultado
    //
        trigger_error('Use a classe filtro_atributo ao inves do metodo get_atributos_prefixo', E_USER_DEPRECATED);

        $atributos = array();
        if (!is_array($remover)) {
            $remover = array();
        }
        foreach ($this->get_atributos() as $atributo => $def) {
            if (in_array($atributo, $remover)) { continue; }
            $atributos[] = $prefixo.$atributo;
        }
        return $atributos;
    }


    //
    //     Retorna um vetor com os nomes dos atributos implicitos e suas definicoes (Array[String => Object])
    //
    final public function get_implicitos() {
        return $this->definicao->implicitos;
    }


    //
    //     Retorna a definicao de um atributo simples da classe
    //
    final public function get_definicao_atributo($nome_atributo) {
    // String $nome_atributo: nome do atributo desejado
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, false);
        }

        if ($this->possui_atributo($nome_atributo)) {
            return clone($this->definicao->atributos[$nome_atributo]);
        }
        trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$nome_atributo.'"', E_USER_WARNING);
        return null;
    }


    //
    //     Retorna a definicao de um atributo simples da classe para validacao
    //
    public function get_definicao_atributo_validacao($nome_atributo) {
    // String $nome_atributo: nome do atributo desejado
    //
        return $this->get_definicao_atributo($nome_atributo);
    }


    //
    //     Retorna a definicao de um atributo implicito da classe
    //
    final public function get_definicao_implicito($nome_atributo) {
    // String $nome_atributo: nome do atributo desejado
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, false);
        }

        if ($this->possui_atributo_implicito($nome_atributo)) {
            return $this->definicao->implicitos[$nome_atributo];
        }
        trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo implicito "'.$nome_atributo.'"', E_USER_WARNING);
        return null;
    }


    //
    //     Obtem a lista de chaves unicas compostas
    //
    final public function get_chaves_unicas_compostas() {
        $atributos = array_keys($this->get_atributos());
        $chaves = array();
        foreach ($this->definicao->uk_compostas as $indices_campos) {
            $campos = array();
            foreach ($indices_campos as $indice_campo) {
                $campos[] = $atributos[$indice_campo];
            }
            $chaves[] = $campos;
        }
        return $chaves;
    }


    //
    //     Retorna se o objeto existe (foi consultado com sucesso)
    //
    final public function existe() {
        return $this->get_flag_consulta($this->get_chave()) &&
               $this->instancia->valores[$this->get_chave()];
    }


    //
    //     Retorna se ocorreram erros
    //
    final public function possui_erros() {
        return !empty($this->erros);
    }


    //
    //     Retorna um vetor com os erros ocorridos
    //
    final public function get_erros() {
        return $this->erros;
    }


    //
    //     Retorna se ocorreram avisos
    //
    final public function possui_avisos() {
        return !empty($this->avisos);
    }


    //
    //     Retorna um vetor com os avisos ocorridos
    //
    final public function get_avisos() {
        return $this->avisos;
    }


    //
    //     Retorna um vetor com os nomes dos campos unicos
    //
    final public function campos_unicos() {
        $vt_unicos = array();
        foreach ($this->get_atributos() as $atributo) {
            if ($atributo->unico) {
                $vt_unicos[] = $atributo->nome;
            }
        }
        return $vt_unicos;
    }


    //
    //     Converte os dados submetidos em campos separados do formulario em valores a serem usados por determinado atributo
    //
    final public function converter_componentes($valores, $campos) {
    // Object $valores: objeto com os valores originais valores
    // Array[String || Type] $campos: vetor de campos a serem convertidos
    //
        $obj = new stdClass();
        foreach ($campos as $chave => $valor) {
            if (is_array($valor)) {
                $campo      = $chave;
                $sub_campos = $valor;
                $sub_valores = isset($valores->$campo) ? $valores->$campo : null;
                if (!$this->possui_rel_uu($campo)) {
                    trigger_error('A classe "'.$this->get_classe().'" nao possui o objeto "'.$campo.'"', E_USER_ERROR);
                    return false;
                }
                $obj->$campo = $this->get_objeto_rel_uu($campo)->converter_componentes($sub_valores, $sub_campos);
            } else {
                $campo = $valor;

                if ($this->possui_atributo($campo)) {
                    $def = $this->get_definicao_atributo($campo);
                    switch ($def->tipo) {
                    case 'data':
                        if (isset($valores->$campo)) {
                            $obj->$campo = $valores->$campo;
                        } else {
                            $dia     = isset($valores->{$campo.'_dia'})     ? $valores->{$campo.'_dia'}     : 0;
                            $mes     = isset($valores->{$campo.'_mes'})     ? $valores->{$campo.'_mes'}     : 0;
                            $ano     = isset($valores->{$campo.'_ano'})     ? $valores->{$campo.'_ano'}     : 0;
                            $hora    = isset($valores->{$campo.'_hora'})    ? $valores->{$campo.'_hora'}    : 0;
                            $minuto  = isset($valores->{$campo.'_minuto'})  ? $valores->{$campo.'_minuto'}  : 0;
                            $segundo = isset($valores->{$campo.'_segundo'}) ? $valores->{$campo.'_segundo'} : 0;
                            $obj->$campo = $dia.'-'.$mes.'-'.$ano.'-'.$hora.'-'.$minuto.'-'.$segundo;
                            unset(
                                $valores->{$campo.'_dia'},
                                $valores->{$campo.'_mes'},
                                $valores->{$campo.'_ano'},
                                $valores->{$campo.'_hora'},
                                $valores->{$campo.'_minuto'},
                                $valores->{$campo.'_segundo'}
                            );
                        }
                        break;
                    default:
                        $valor = isset($valores->$campo) ? $valores->$campo : null;
                        $obj->$campo = $this->converter_componente($campo, $valor, $valores);
                        break;
                    }
                } else {
                    $valor = isset($valores->$campo) ? $valores->$campo : null;
                    $obj->$campo = $this->converter_componente($campo, $valor, $valores);
                }
            }
        }
        return $obj;
    }


    //
    //     Converte as componentes de um atributo no respectivo valor do atributo final
    //
    protected function converter_componente($campo, $valor, $valores) {
    // String $campo: nome do campo
    // Mixed $valor: valor do campo
    // Object $valores: valores a serem convertidos
    //
        return $valor;
    }


    //
    //     Converte um vetor com elementos na notacao objeto:atributo para um vetor hierarquico
    //
    final public static function converter_notacao_vetor($vetor) {
    // Array[String] $vetor: vetor com nomes de atributos na notacao objeto:atributo
    //
        $novo = array();
        foreach ($vetor as $atributo) {
            $pos = strpos($atributo, ':');
            if ($pos !== false) {
                $vt_atributo = explode(':', $atributo);
                $ultimo = array_pop($vt_atributo);
                util::definir_vetor_nivel($novo, array_merge($vt_atributo, array(null)), $ultimo);
            } else {
                $novo[] = $atributo;
            }
        }
        return $novo;
    }


    //
    //     Converte um vetor com elementos hierarquicos em um vetor na notacao objeto:atributo
    //
    final public static function converter_notacao_vetor_hierarquico($vetor) {
    // Array[String || Type] $vetor: vetor hierarquico
    //
        $novo = array();
        foreach ($vetor as $chave => $valor) {
            if (is_array($valor)) {
                $sub = self::converter_notacao_vetor_hierarquico($valor);
                foreach ($sub as $valor_sub) {
                    $novo[] = $chave.':'.$valor_sub;
                }
            } else {
                $novo[] = $valor;
            }
        }
        return $novo;
    }


    //
    //     Define valores aos atributos simples do objeto
    //
    final public function set_valores($valores, $campos = false, $sobrescrever = false) {
    // Object $valores: objeto com os valores a serem atribuidos
    // Array[String] || Bool $campos: campos a serem atribuidos obrigatoriamente ou false para nenhum
    // Bool $sobrescrever: forca que os dados sejam sobrescritos
    //
        $r = true;

        // Desabilitar validacao de unicidade temporariamente
        $flag_unicidade = $this->instancia->flag_unicidade;
        $this->instancia->flag_unicidade = false;

        // Checar o tipo do parametro passado
        $valores = (object)$valores;
        $valores = clone($valores);

        $nome_pk = $this->get_chave();

        // Tentar setar cada valor em um atributo simples ou auxiliar
        if (!$campos) {
            $campos_filhos = array();

            // Se tem a chave primaria, coloca-la primeiro
            $nome_pk = $this->get_chave();
            if (isset($valores->$nome_pk)) {
                if ($this->existe()) {
                    $r = $this->set_acordo_modo($nome_pk, $valores->$nome_pk, $sobrescrever) && $r;
                } else {
                    $r = $this->__set($nome_pk, $valores->$nome_pk) && $r;
                }
                unset($valores->$nome_pk);
            }

            foreach ($valores as $campo => $valor) {

                // Se e' um objeto filho: deixar para depois (depende do valor da chave FK)
                if (is_object($valor)) {
                    $nome_obj = $campo;
                    if ($this->possui_rel_uu($nome_obj)) {
                        $campos_filhos[] = $nome_obj;
                    } else {
                        $this->instancia->flag_unicidade = true;
                        trigger_error('A classe "'.$this->get_classe().'" nao possui o objeto "'.$nome_obj.'"', E_USER_WARNING);
                        return false;
                    }

                // Se e' um atributo simples: setar de acordo com o modo corrente
                } else {
                    $r = $this->set_acordo_modo($campo, $valor, $sobrescrever) && $r;
                }
            }

            // Percorrer os objetos filhos
            foreach ($campos_filhos as $nome_obj) {
                $def_rel_uu = $this->get_definicao_rel_uu($nome_obj);
                $nome_fk = $this->get_nome_chave_rel_uu($nome_obj);

                // Caso os objetos em questao sao da mesma instancia: setar os valores
                if (!isset($valores->$nome_fk) || $valores->$nome_fk == $this->get_atributo($nome_fk)) {

                    // Se e' um objeto apontando para ele mesmo
                    if ($def_rel_uu->classe == $this->get_classe() && $valores->$nome_obj->$nome_pk && $valores->$nome_obj->$nome_pk == $this->get_valor_chave()) {
                        $valores_obj = $valores->$nome_obj;
                        unset($valores_obj->$nome_pk);
                        $r_obj = $this->set_valores($valores_obj, false, false);
                        $r = $r_obj && $r;
                    } else {
                        $r_obj = $this->get_objeto_rel_uu($nome_obj)->set_valores($valores->$nome_obj, false, $sobrescrever);
                        $r = $r_obj && $r;
                    }

                    // Se tem erros no objeto filho: importar os erros para objeto pai
                    if (!$r_obj) {
                        switch ($this->get_objeto_rel_uu($nome_obj)->get_genero()) {
                        case 'M':
                            $de = 'do';
                            break;
                        case 'F':
                            $de = 'da';
                            break;
                        case 'I':
                            $de = 'de';
                            break;
                        }
                        $this->erros[] = 'Erro ao definir valores '.$de.' '.$this->get_objeto_rel_uu($nome_obj)->get_entidade();
                        $this->erros[] = $this->get_objeto_rel_uu($nome_obj)->get_erros();
                        $this->get_objeto_rel_uu($nome_obj)->limpar_erros();
                    }
                }
            }

        // Utilizar os campos informados
        } else {
            $campos = (array)$campos;
            $campos_filhos = array();

            // Se tem a chave primaria, coloca-la primeiro
            $nome_pk = $this->get_chave();
            if (isset($campos[$nome_pk])) {
                if ($this->existe()) {
                    $r = $this->set_acordo_modo($nome_pk, $valores->$nome_pk, $sobrescrever) && $r;
                } else {
                    $r = $this->__set($nome_pk, $valores->$nome_pk) && $r;
                }
                unset($campos[$nome_pk]);
            }

            foreach ($campos as $chave => $valor) {

                // Se e' um objeto filho: deixar para depois (depende do valor da chave FK)
                if (is_array($valor)) {
                    $nome_obj = $chave;
                    if ($this->possui_rel_uu($nome_obj)) {
                        $campos_filhos[] = $nome_obj;
                    } else {
                        $this->instancia->flag_unicidade = true;
                        trigger_error('A classe "'.$this->get_classe().'" nao possui o objeto "'.$nome_obj.'"', E_USER_WARNING);
                        return false;
                    }

                // Se e' um atributo simples: setar de acordo com o modo corrente
                } else {
                    $campo = $valor;

                    // Se o valor veio no objeto
                    if (property_exists($valores, $campo)) {
                        $r = $this->set_acordo_modo($campo, $valores->$campo, $sobrescrever) && $r;

                    // Se o valor nao veio no objeto, pode ter vindo de $_FILES
                    } else {
                        trigger_error('Pediu-se para atribuir "'.$campo.'", mas ele nao foi especificado no objeto', E_USER_NOTICE);
                    }
                }
            }

            // Percorrer os objetos filhos
            foreach ($campos_filhos as $nome_obj) {
                $campos_obj = $campos[$nome_obj];
                $def_rel_uu = $this->get_definicao_rel_uu($nome_obj);
                $nome_fk = $this->get_nome_chave_rel_uu($nome_obj);

                // Caso os objetos em questao sao da mesma instancia: setar os valores
                if (!isset($valores->$nome_fk) || $valores->$nome_fk == $this->get_atributo($nome_fk)) {

                    // Se e' um objeto apontando para ele mesmo
                    if ($def_rel_uu->classe == $this->get_classe() && $valores->$nome_obj->$nome_pk && $valores->$nome_obj->$nome_pk == $this->get_valor_chave()) {
                        $valores_obj = $valores->$nome_obj;
                        unset($valores_obj->$nome_pk);

                        $r_obj = $this->set_valores($valores->$nome_obj, $campos_obj, false);
                        $r = $r_obj && $r;
                    } else {
                        $r_obj = $this->get_objeto_rel_uu($nome_obj)->set_valores($valores->$nome_obj, $campos_obj, $sobrescrever);
                        $r = $r_obj && $r;
                    }

                    // Se tem erros no objeto filho: importar os erros para objeto pai
                    if (!$r_obj) {
                        switch ($this->get_objeto_rel_uu($nome_obj)->get_genero()) {
                        case 'M':
                            $de = 'do';
                            break;
                        case 'F':
                            $de = 'da';
                            break;
                        case 'I':
                            $de = 'de';
                            break;
                        }
                        $this->erros[] = 'Erro ao definir valores '.$de.' '.$this->get_objeto_rel_uu($nome_obj)->get_entidade();
                        $this->erros[] = $this->get_objeto_rel_uu($nome_obj)->get_erros();
                        $this->get_objeto_rel_uu($nome_obj)->limpar_erros();
                    }
                }
            }
        }

        // Voltar a validacao de unicidade
        $this->instancia->flag_unicidade = $flag_unicidade;

        // Se os dados nao estao vindos do BD e a flag de unicidade esta' ativa
        if (!self::$flag_bd && $this->instancia->flag_unicidade) {
            $r = $r && $this->validar_unicidade();
        }

        // Se nao possui erros e os dados nao vieram do BD, fazer a validacao final
        if ($r && !self::$flag_bd) {
            $r = $r && $this->validacao_final($valores);
        }

        return $r;
    }


    //
    //     Realiza a validacao final (util para validar atributos dependentes)
    //
    public function validacao_final(&$dados) {
    // Object $dados: dados a serem validados
    //
        return true; // Metodo reservado para sobrescrita
    }


    //
    //     Retorna se a classe e' singleton ou nao (so pode ter uma instancia)
    //
    final public function singleton() {
        return $this->definicao->singleton;
    }


    //
    //     Retorna o nome da entidade no singular ou no plural
    //
    final public function get_entidade($plural = false) {
    // Bool $plural: nome da entidade no plural (true) ou no singular (false)
    //
        if ($plural) {
            return $this->definicao->entidade->plural;
        }
        return $this->definicao->entidade->singular;
    }


    //
    //     Retorna o genero da entidade (M, F ou I)
    //
    final public function get_genero() {
        return $this->definicao->entidade->genero;
    }


    //
    //     Retorna o nome do campo usado como chave primaria
    //
    final public function get_chave() {
        return $this->definicao->tabela->chave;
    }


    //
    //     Retorna o valor da chave primaria ou zero, caso nao tenha sido consultada
    //
    final public function get_valor_chave() {
        if ($this->existe()) {
            return $this->get_valor($this->get_chave());
        }
        return 0;
    }


    //
    //     Define o valor da chave primaria e atualiza a instancia, caso necessario
    //
    final protected function set_valor_chave($valor_chave) {
    // Mixed $valor_chave: valor da chave
    //
        if (!$valor_chave) {
            if (self::$flag_bd) {
                return true;
            }
            trigger_error('O valor da chave nao pode ser nulo', E_USER_WARNING);
            return false;
        }
        $r = true;

        // Se ja existe uma instancia com a chave especificada
        if (self::possui_instancia($this->get_classe(), $valor_chave)) {

            // Recuperar os valores atuais da instancia
            $valores = $this->instancia->valores;
            $campos = array_keys((array)$valores);

            // Apontar a instancia para aquela existente na base de instancias
            $this->definir_instancia($this->get_classe(), $valor_chave);

            // Realizar merge dos dados da instancia antiga com a instancia da base de instancias
            $this->consultar_campos($campos, OBJETO_REMOVER_CONSULTADOS);
            foreach ($valores as $nome_atributo => $valor) {
                $valor_flag = $this->get_valor_flag_mudanca($nome_atributo, $valor);
                $r = $r && $this->set_valor($nome_atributo, $valor);
                $this->set_flag_mudanca($nome_atributo, $valor_flag);
            }

        // Se nao possui uma instancia com a chave especificada
        } else {

            // Definir o valor da chave
            $this->set_valor($this->get_chave(), $valor_chave);

            // Criar a primeira instancia
            self::$instancias[$this->get_classe()][$valor_chave] = clone($this->instancia);
            $this->instancia = &self::$instancias[$this->get_classe()][$valor_chave];
            $this->instancia->referencias = 1;
            $this->instancia->orfa = false;
        }
        return $r;
    }


    //
    //     Retorna o nome da classe corrente
    //
    final public function get_classe() {
        return $this->definicao->classe;
    }


    //
    //     Retorna o nome da tabela do BD usada pela entidade ou a sua descricao
    //
    final public function get_tabela($descricao_completa = false) {
    // Bool $descricao_completa: indica se deseja consultar a descricao completa da tabela (true) ou apenas seu nome (false)
    //
        if ($descricao_completa) {
            return $this->definicao->tabela->descricao;
        }
        return $this->definicao->tabela->nome;
    }


    //
    //     Retorna um vetor associativo com os campos modificados apontanto para seus valores
    //
    final public function get_campos_modificados($campos = true) {
    // Array[String] || Bool $campos: Campos a serem analisados ou true para todos
    //
        $vt_campos = array();

        // Checar os campos pedidos
        if (is_array($campos)) {
            foreach (vetor::array_unique_recursivo($campos) as $i => $campo) {
                if (is_array($campo)) {
                    $vt_campos_obj = $this->get_objeto_rel_uu($i)->get_campos_modificados($campo);
                    if ($vt_campos_obj) {
                        $vt_campos[$i] = $vt_campos_obj;
                    }
                } else {
                    if ($this->possui_atributo($campo)) {
                        if ($this->get_flag_mudanca($campo)) {
                            $vt_campos[$campo] = $this->get_valor($campo);
                        }
                    } else {
                        trigger_error('O atributo "'.$campo.'" nao existe na classe "'.$this->get_classe().'"', E_USER_NOTICE);
                    }
                }
            }

        // Checar todos os campos
        } elseif ($campos === true) {
            foreach ($this->get_atributos() as $atributo => $def) {
                if ($this->get_flag_mudanca($atributo)) {
                    $vt_campos[$atributo] = $this->get_valor($atributo);
                }
            }
        }
        return $vt_campos;
    }


    //
    //     Retorna um objeto stdClass com os campos desejados ou todos
    //
    final public function get_dados($campos = true) {
    // Array[String] || Bool $campos: vetor de campos a serem retornados (true = todos)
    //
        // Se informou os campos desejados
        if (is_array($campos)) {
            if ($this->existe()) {
                $flag = OBJETO_ADICIONAR_CHAVES | OBJETO_REMOVER_CONSULTADOS;
                $vt_campos = $this->get_campos_reais($campos, $objetos, $vetores, $flag);
                if (!empty($vt_campos)) {
                    $this->consultar_campos($vt_campos);
                }
            }

            $obj = new stdClass();

            foreach ($campos as $campo) {

                // Se e' um atributo simples ou implicito
                if ($this->possui_atributo($campo) || $this->possui_atributo_implicito($campo)) {
                    util::definir_atributo_nivel($obj, explode(':', $campo), $this->__get($campo));

                // Se e' um relacionamento 1:1
                } elseif ($this->possui_rel_uu($campo)) {
                    util::definir_atributo_nivel($obj, explode(':', $campo), $this->get_objeto_rel_uu($campo)->get_dados());

                // Se e' um relacionamento 1:N
                } elseif ($this->possui_rel_un($campo)) {
                    $vt_original = $this->get_vetor_rel_un($campo);
                    $vt_objeto = array();
                    if (is_array($vt_original)) {
                        foreach ($vt_original as $chave => $item) {
                            $vt_objeto[$chave] = $item->get_dados();
                        }
                    }
                    util::definir_atributo_nivel($obj, explode(':', $campo), $vt_objeto);
                    unset($vt_objeto, $vt_original);

                // Se e' um atributo auxiliar
                } elseif ($this->possui_auxiliar($campo)) {
                    util::definir_atributo_nivel($obj, explode(':', $campo), $this->get_auxiliar($campo));
                }
            }

        // Se nao informou os campos desejados: usar todos
        } else {
            if ($this->existe()) {
                $this->consultar_campos(true);
            }

            // Obter os atributos simples
            $obj = (object)$this->instancia->valores;

            // Preencher com os atributos implicitos
            foreach ($this->get_implicitos() as $nome_atributo => $def) {
                $obj->$nome_atributo = $this->get_atributo_implicito($nome_atributo);
            }

            // Relacionamentos 1:1
            foreach ($this->get_objetos_rel_uu() as $nome_objeto => $objeto) {
                $obj->$nome_objeto = $objeto->get_dados(false);
            }

            // Relacionamentos 1:N
            foreach ($this->get_vetores_rel_un() as $nome_vetor => $vetor) {
                $obj->$nome_vetor = array();
                foreach ($vetor as $chave => $item) {
                    $obj->{$nome_vetor}[$chave] = $item->get_dados(false);
                }
            }
        }
        return $obj;
    }


    //
    //     Adiciona um atributo simples na definicao da classe entidade
    //
    final protected function adicionar_atributo($atributo, $classe = false) {
    // Object $atributo: objeto do tipo atributo
    // String $classe: nome da classe original do atributo
    //

        // Checagem de consistencia
        if (!DEVEL_BLOQUEADO && !$this->validar_integridade_atributo($atributo, $classe)) {
            return false;
        }
        if (!$classe) {
            $classe = $this->get_classe();
        }
        $atributo->set_classe($classe);

        // Incluir na lista de atributos da classe
        $this->definicao->atributos[$atributo->nome] = $atributo;

        // Se e' a chave PK
        if ($atributo->chave == 'PK') {
            $this->definicao->tabela->chave = $atributo->nome;
        }

        // Definir valores iniciais do atributo da instancia
        $this->set_flag_mudanca($atributo->nome, false);
    }


    //
    //     Verifica a integridade do atributo a ser inserido na classe
    //
    private function validar_integridade_atributo($atributo, $classe) {
    // atributo $atributo: definicao do atributo a ser inserido na classe
    // String $classe: nome da classe original do atributo
    //
        $classe_atributo = 'atributo';
        if (!($atributo instanceof $classe_atributo)) {
            trigger_error('Tipo invalido do atributo ("'.gettype($atributo).'") na classe "'.$this->get_classe().'"', E_USER_ERROR);
            return false;
        }

        // Classe do atributo
        if ($classe) {
            simp_autoload($classe);
        }

        // Obter nome do atributo
        $nome = $atributo->nome;
        if (empty($nome)) {
            trigger_error('Um dos atributos da classe "'.$this->get_classe().'" nao possui  nome', E_USER_ERROR);
            return false;
        }

        // Se nao possui o atributo
        if (!$this->possui_atributo($nome)) {
            if ($atributo->chave == 'PK') {
                if (!$this->definicao->tabela->chave) {
                    if (!is_null($atributo->padrao)) {
                        trigger_error('A chave primaria da classe "'.$this->get_classe().'" nao pode ter valor padrao', E_USER_ERROR);
                        return false;
                    }
                } else {
                    trigger_error('Foi definida mais de uma chave primaria na classe "'.$this->get_classe().'"', E_USER_ERROR);
                    return false;
                }
            }

        // Se o atributo ja existe
        } else {
            trigger_error('O atributo "'.$nome.'" foi definido mais de uma vez na classe "'.$this->get_classe().'"', E_USER_ERROR);
            return false;
        }
        return true;
    }


    //
    //     Adiciona um atributo implicito
    //
    final protected function adicionar_atributo_implicito($nome, $descricao, $metodo, $atributos_necessarios = array()) {
    // String $nome: nome do atributo implicito gerado no objeto
    // String $descricao: nome do atributo para o usuario
    // String $metodo: nome do metodo que retorna o valor do atributo (o metodo nao recebe parametros)
    // Array[String] $atributos_necessarios: vetor de atributos necessarios para montar o atributo implicito (apenas para otimizar consultas)
    //
        if (!DEVEL_BLOQUEADO && !$this->validar_integridade_atributo_implicito($nome, $descricao, $metodo, $atributos_necessarios)) {
            return false;
        }

        // Montar os dados do atributo implicito
        $definicao = new stdClass();
        $definicao->descricao = $descricao;
        $definicao->metodo    = $metodo;
        $definicao->atributos = $atributos_necessarios;

        // Adicionar os dados no vetor de atributos implicitos
        $this->definicao->implicitos[$nome] = $definicao;
    }


    //
    //     Verifica a integridade do atributo implicito a ser inserido na classe
    //
    private function validar_integridade_atributo_implicito($nome, $descricao, $metodo, $atributos_necessarios) {
    // String $nome: nome do atributo implicito gerado no objeto
    // String $descricao: nome do atributo para o usuario
    // String $metodo: nome do metodo que retorna o valor do atributo (o metodo nao recebe parametros)
    // Array[String] $atributos_necessarios: vetor de atributos necessarios para montar o atributo implicito (apenas para otimizar consultas)
    //
        if (empty($nome) || empty($descricao)) {
            trigger_error('O nome/descricao do atributo implicito nao pode ser vazio', E_USER_ERROR);
            return false;
        }

        // Se nao existe o metodo
        if (!method_exists($this, $metodo)) {
            trigger_error('O metodo "'.$metodo.'" nao existe na classe "'.$this->get_classe().'"', E_USER_ERROR);
            return false;

        // Se o metodo nao tem visibilidade adequada
        } elseif (!is_callable(array($this, $metodo))) {
            trigger_error('O metodo "'.$metodo.'" da classe "'.$this->get_classe().'" precisa ser publico', E_USER_ERROR);
            return false;
        }

        if (is_array($atributos_necessarios)) {
            foreach ($atributos_necessarios as $atributo) {
                if (!$this->possui_atributo($atributo) &&
                    !$this->possui_atributo_implicito($atributo) &&
                    !$this->possui_rel_uu($atributo)) {
                    trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$atributo.'" (especifique os atributos simples antes dos implicitos)', E_USER_ERROR);
                    return false;
                }
            }
        } elseif (is_string($atributos_necessarios)) {
            trigger_error('Os atributos necessarios do atributo implicito "'.$nome.'" da classe "'.$this->get_classe().'" precisa ser um vetor', E_USER_ERROR);
            return false;
        }
        return true;
    }


    //
    //     Adicionar chave unica composta
    //
    final protected function adicionar_chave_unica_composta($campos) {
    // Array[String] $campos: vetor de campos simples que foram a chave unica composta
    //
        // Obter a posicao dos campos chave no vetor de atributos
        $atributos = array_keys($this->get_atributos());
        $vt_indice_campo = array();
        foreach ($campos as $campo) {

            // Se e' a chave primaria
            if ($campo == $this->get_chave()) {
                trigger_error('Nao faz sentido usar a chave primaria como parte da chave unica composta, afinal, nunca havera dois registros com a mesma chava primaria', E_USER_ERROR);
                return false;
            }
            $posicao = array_search($campo, $atributos);
            if ($posicao !== false) {
                $vt_indice_campo[] = $posicao;
            } else {
                trigger_error('O campo "'.$campo.'" nao foi encontrado na classe "'.$this->get_classe().'" para fazer parte da chave unica composta', E_USER_ERROR);
                return false;
            }
        }

        $this->definicao->uk_compostas[] = $vt_indice_campo;
    }


    //
    //     Checa se existe o atributo simples na classe
    //
    final public function possui_atributo($nome_atributo) {
    // String $nome_atributo: nome do atributo
    //
        if (!is_string($nome_atributo)) {
            trigger_error('Tipo invalido: "'.gettype($nome_atributo).'"', E_USER_WARNING);
            return false;
        }

        // Checar se foi usada a notacao objeto:atributo
        $pos = strpos($nome_atributo, ':');
        if ($pos !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, false);
        }
        return isset($this->definicao->atributos[$nome_atributo]);
    }


    //
    //     Checa se existe o atributo implicito na classe
    //
    final public function possui_atributo_implicito($nome_atributo) {
    // String $nome_atributo: nome do atributo implicito
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, false);
        }
        return isset($this->definicao->implicitos[$nome_atributo]);
    }


    //
    //     Checa se existe o atributo auxiliar na classe
    //
    final public function possui_auxiliar($nome_atributo) {
    // String $nome_atributo: nome do atributo auxiliar
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }
        return array_key_exists($nome_atributo, $this->auxiliares);
    }


    //
    //     Checa se uma entidade possui o dado solicitado (especialmente util para checar relacionamentos 1:1 fracos)
    //
    final public function possui_dado($campo) {
    // String $campo: campo a ser checado
    //
        // Se usou a notacao objeto:atributo
        $pos = strpos($campo, ':');
        if ($pos !== false) {
            $nome_obj = substr($campo, 0, $pos);
            $resto = substr($campo, $pos + 1);
            if ($this->get_objeto_rel_uu($nome_obj)->existe()) {
                return $this->get_objeto_rel_uu($nome_obj)->possui_dado($resto);
            } else {
                return false;
            }
        }
        return $this->existe();
    }


    //
    //     Checa se uma entidade possui pelo menos um dos dados solicitados (especialmente util para checar relacionamentos 1:1 fracos)
    //
    final public function possui_dados($campos) {
    // Array[String] $campos: campos a serem checados
    //
        if (!$this->existe()) {
            return false;
        }
        foreach ($campos as $campo) {
            if ($this->possui_dado($campo)) {
                return true;
            }
        }
        return false;
    }


    //
    //     Checa se o sistema esta em transacao
    //
    final static public function em_transacao() {
        return self::$em_transacao;
    }


    //
    //     Inicia uma transacao do BD (sequencia de instrucoes SQL dependentes: ou realiza tudo ou nao realiza nada)
    //
    final static public function inicio_transacao($modo = null) {
    // Int $modo: modo de transacao
    //
        if (self::$em_transacao) {
            return true;
        }
        if (is_null($modo)) {
            self::$em_transacao = (bool)self::$dao->inicio_transacao();
        } else {
            self::$em_transacao = (bool)self::$dao->inicio_transacao($modo);
        }
        return self::$em_transacao;
    }


    //
    //     Encera uma transacao, executando os comandos em buffer (COMMIT) ou recuperando valores em caso de erro (ROLLBACK)
    //
    final static public function fim_transacao($rollback = false) {
    // Bool $rollback: forca a execucao do rollback
    //
        if (!self::$em_transacao) {
            trigger_error('A transacao nao foi aberta ou ja foi encerrada', E_USER_NOTICE);
            return false;
        }
        self::$em_transacao = false;
        $r = self::$dao->fim_transacao($rollback);
        if (!$r && !DEVEL_BLOQUEADO && self::$dao->possui_erros()) {
            $this->erros[] = '[DEBUG-DEVEL] Erros na camada DAO:';
            $this->erros[] = self::$dao->get_erros();
        }
        return $r;
    }


    //
    //     Indica se deve gerar logs ou nao
    //
    final static public function set_flag_log($flag_log) {
    // Bool $flag_log: indica de deve ou nao gerar logs
    //
        self::$flag_log = (bool)$flag_log;
    }


    //
    //     Retorna se a flag de geracao de logs esta' ativada ou nao
    //
    final static public function get_flag_log() {
        return self::$flag_log;
    }


    //
    //     Define o ID do formulario
    //
    final public function set_id_form($id_form, $prefixo = '') {
    // String $id_form: ID do formulario
    // String $prefixo: prefixo do ID do formulario
    //
        if (!empty($prefixo)) {
            $prefixo .= '_';
        }
        if (empty($id_form))  {
            trigger_error('O ID do formulario nao pode ser nulo', E_USER_ERROR);
        }

        // Definir o id_form da entidade
        $this->id_form = $prefixo.$id_form;
    }


    //
    //     Indica se o registro pode ser exibido ou nao para o usuario
    //
    public function pode_exibir(&$usuario, &$motivo = '') {
    // usuario $usuario: usuario a ser testado
    // String $motivo: motivo pelo qual nao se pode inserir um novo registro
    //
        return true;
    }


    //
    //     Realiza uma chamada recursiva a de um metodo que aceita a notacao objeto:atributo para um atributo
    //
    final protected function recursao_atributo($metodo, $parametros, $posicao = 0, $objeto_real = true) {
    // String $metodo: nome do metodo (nao estatico) que fez a chamada recursiva
    // Array[Mixed] $parametros: vetor de parametros
    // Int $posicao: posicao do atributo (na lista de parametros do metodo informado) que esta' com a notacao objeto:atributo
    // Bool $objeto_real: indica se o objeto filho/pai precisa dos dados do objeto ou apenas a definicao da entidade
    //
        $pos = strpos($parametros[$posicao], ':');
        $resto = substr($parametros[$posicao], $pos + 1);
        $nome_filho = substr($parametros[$posicao], 0, $pos);

        // Se esta buscando um objeto pai
        if (substr($nome_filho, 0, 1) == '^') {
            $nome_pai = substr($nome_filho, 1);
            $pos = strpos($nome_pai, '.');
            if ($pos !== false) {
                $atributo_pai = substr($nome_pai, $pos + 1);
                $nome_pai = substr($nome_pai, 0, $pos);
            } else {
                $atributo_pai = false;
            }

            if ($objeto_real) {
                $callback = array($this->get_pai($nome_pai, $atributo_pai), $metodo);
                $parametros[$posicao] = $resto;
                return call_user_func_array($callback, $parametros);
            } else {
                $objeto = objeto::get_objeto($nome_pai);
                $callback = array($objeto, $metodo);
                $parametros[$posicao] = $resto;
                return call_user_func_array($callback, $parametros);
            }
        }

        // Se esta buscando um objeto filho
        if ($this->possui_rel_uu($nome_filho)) {
            if ($objeto_real) {
                $callback = array($this->get_objeto_rel_uu($nome_filho), $metodo);
                $parametros[$posicao] = $resto;
                return call_user_func_array($callback, $parametros);
            } else {
                $def = $this->get_definicao_rel_uu($nome_filho);
                $objeto = objeto::get_objeto($def->classe);
                $callback = array($objeto, $metodo);
                $parametros[$posicao] = $resto;
                return call_user_func_array($callback, $parametros);
            }

        // Se esta buscando um vetor filho
        } elseif ($this->possui_rel_un($nome_filho)) {
            $def = $this->get_definicao_rel_un($nome_filho);
            $obj = self::get_objeto($def->classe);
            $callback = array($obj, $metodo);
            $parametros[$posicao] = $resto;
            return call_user_func_array($callback, $parametros);
        } else {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o objeto ou o vetor "'.$nome_filho.'" (metodo "'.$metodo.'")', E_USER_WARNING);
            return null;
        }
    }


    //
    //     Obtem a chave primaria de um registro a partir do valor de um atributo
    //
    public static function get_pk_por_atributo($classe, $valor, $atributo = 'identificador') {
    // String $classe: nome da classe
    // Mixed $valor: valor do atributo
    // String $atributo: nome do atributo
    //
        $obj = objeto::get_objeto($classe);
        $obj->consultar($atributo, $valor);
        return $obj->get_valor_chave();
    }


/// @ METODOS DE VALIDACAO E DE LOGICA (HANDLER)


    //
    //     Consulta os dados de um objeto no BD
    //
    final public function consultar($chave, $valor, $campos = false, $flag = null, $consultar_memoria = true) {
    // String || Bool $chave: chave da consulta (false para a chave primaria)
    // Mixed $valor: valor da busca
    // Array[String] || Bool $campos: campos desejados (true = todos | false = apenas PK)
    // Int $flag: flag de consulta na camada DAO ou null para o valor padrao
    // Bool $consultar_memoria: indica se deve consultar dados da memoria (lista de instancias)
    //
        // Checar se a chave de busca existe
        if (empty($chave)) {
            $chave = $this->get_chave();
        } elseif (!$this->possui_atributo($chave)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$chave.'"', E_USER_WARNING);
            return false;
        }

        // Chechar se o dado ja' esta' na lista de instancias (self::$instancias)
        if ($consultar_memoria) {
            $this->consultar_lista_instancias($chave, $valor);
        }

        // Montar a condicao de consulta
        if ($this->singleton()) {
            $condicoes = condicao_sql::vazia();
        } else {
            $condicoes = condicao_sql::montar($chave, '=', $valor);
        }

        // Montar flag
        if ($flag === null && $consultar_memoria) {
            $flag = OBJETO_ADICIONAR_CHAVES;

            // Se esta' consultando o mesmo objeto, remover os campos consultados
            if (self::get_modo_persistencia() == OBJETO_MODO_CONGELAR &&
                $this->get_flag_consulta($chave) &&
                $this->get_atributo($chave) == $valor) {
                $flag |= OBJETO_REMOVER_CONSULTADOS;
            }
        }

        return $this->consultar_dao($condicoes, $campos, $flag);
    }


    //
    //     Consulta os dados de um objeto no BD sob varias condicoes
    //
    final public function consultar_condicoes($condicoes, $campos = false, $flag = null) {
    // condicao_sql $condicoes: condicoes de busca
    // Array[String] || Bool $campos: campos desejados (true = todos | false = apenas PK)
    // Int $flag: flag de consulta na camada DAO ou null para o valor padrao
    //
        // Chechar se o dado ja' esta' na lista de instancias (self::$entidades)
        //TODO: ver uma forma de fazer isso para otimizar (e colocar um parametro $consultar_memoria = true)
        //$this->consultar_lista_instancias_condicoes($condicoes);

        // Montar flag
        if ($flag === null) {
            $flag = OBJETO_ADICIONAR_CHAVES;
        }

        return $this->consultar_dao($condicoes, $campos, $flag);
    }


    //
    //     Consulta dados na camada DAO
    //
    private function consultar_dao($condicoes, $campos, $flag) {
    // condicao_sql $condicoes: condicoes de busca
    // Array[String] || Bool $campos: campos desejados (true = todos | false = apenas PK)
    // Int $flag: flag de consulta
    //
        $r = true;
        $chave = $this->get_chave();

        // Campos a serem consultados
        $vt_campos = $this->get_campos_reais($campos, $objetos, $vetores, $flag);

        // Se nao possui atributos a serem consultados, mas o objeto nao existe: consultar a chave primaria
        if (empty($vt_campos) && !$this->existe()) {
            $vt_campos[] = $chave;
        }

        // Se possui atributos simples a serem consultados
        if (!empty($vt_campos)) {
            $iterador = self::$dao->select_iterador($this, $vt_campos, $condicoes, null, 1);

            // Se nao consultou devido um erro de BD
            if (is_bool($iterador)) {
                switch ($this->get_genero()) {
                case 'M':
                    $this->erros[] = 'Erro ao consultar os dados do '.$this->get_entidade();
                    break;
                case 'F':
                    $this->erros[] = 'Erro ao consultar os dados da '.$this->get_entidade();
                    break;
                case 'I':
                    $this->erros[] = 'Erro ao consultar os dados de '.$this->get_entidade();
                    break;
                }
                return false;

            // Se consultou, mas o resultado e' um conjunto vazio
            } elseif ($iterador->size() == 0) {

                // Se o objeto ja' existe: apagar
                if ($this->existe()) {
                    $this->limpar_objeto();
                }

                return false;
            }
            $obj = $iterador->current();
            unset($iterador);

            // Se o objeto ja' existe
            if ($this->existe()) {

                // Se esta consultando o mesmo objeto: nao precisa setar novamente a chave
                if ($this->get_valor_chave() == $obj->$chave) {
                    unset($obj->$chave);

                // Se e' outro objeto: limpar este objeto antes
                } else {
                    $this->limpar_objeto();
                }
            }

            // Desabilitar validacao para definir dados vindos do BD
            $ativou_flag_bd = false;
            if (!self::$flag_bd) {
                self::$flag_bd = true;
                $ativou_flag_bd = true;
            }
            $r = $this->set_valores($obj) && $r;
            if ($ativou_flag_bd) {
                self::$flag_bd = false;
            }
        }

        // Se possui vetores a serem consultados
        if (!empty($vetores)) {
            foreach ($vetores as $vetor) {
                $def = $this->get_definicao_rel_un($vetor);
                $r = $this->consultar_vetor_rel_un($vetor, array($def->impressao)) && $r;
                unset($def);
            }
        }

        // Se nao consultou, mas o objeto estava preenchido: limpa-lo
        if (!$r && $this->existe()) {
            $this->limpar_objeto();
        }

        return $r;
    }


    //
    //     Consulta os dados na lista de instancias (self::$instancias)
    //
    private function consultar_lista_instancias($campo, $valor) {
    // String $campo: nome do campo usado para busca
    // Mixed $valor: valor usado para busca
    //
        $nome_classe = $this->get_classe();

        // Se o campo de busca e' a chave primaria: otimo! fica mais facil
        if ($campo == $this->get_chave())  {

            // Se ja' existe a instancia na lista de instancias
            if (self::possui_instancia($nome_classe, $valor)) {

                // Definir a instancia da lista de instancias
                $this->definir_instancia($nome_classe, $valor);
                return true;
            }

        // Se o campo de busca nao e' a chave primaria: paciencia
        } else {
            $pos_atributo = array_search($campo, array_keys($this->get_atributos()));

            // Percorrer as instancias da lista de instancias
            foreach (self::get_instancias($nome_classe) as $instancia) {

                // Se o campo de busca foi consultado pela entidade na lista de instancias e nao foi modificado
                if (isset($instancia->valores[$campo]) && !$instancia->flag_mudanca[$pos_atributo]) {

                    // Se o valor de busca e' igual ao da lista de instancias
                    if ($instancia->valores[$campo] == $valor) {

                        // Definir a instancia da lista de instancias
                        $valor_chave = $instancia->valores[$this->get_chave()];
                        $this->definir_instancia($nome_classe, $valor_chave);
                        return true;
                    }
                }
            }
        }
        return false;
    }


    //
    //     Converte uma requisicao por campos em um vetor de campos reais (atributos simples)
    //
    final public function get_campos_reais($campos, &$objetos = array(), &$vetores = array(), $flag = 0) {
    // Array[String] || Bool $campos: campos desejados (true = todos | false = apenas PK)
    // Array[String] $objetos: vetor de nomes de objetos (relacionamentos 1:1)
    // Array[String] $vetores: vetor de nomes de vetores (relacionamentos 1:N)
    // Int $flag: especifica operacoes sobre o vetor obtido (ver constantes)
    //
        $vt_campos = array();
        if (!is_array($objetos)) {
            $objetos   = array();
        }
        if (!is_array($vetores)) {
            $vetores   = array();
        }

        // Consultar todos os campos
        if ($campos === true) {
            $vt_campos = array_keys($this->get_atributos());
            foreach ($this->get_implicitos() as $campo => $def) {
                if (is_array($def->atributos) && !empty($def->atributos)) {
                    $pos = strpos($campo, ':');
                    $nome_obj = ($pos !== false) ? substr($campo, 0, $pos).':' : '';
                    $vt_campos_reais = $this->get_campos_reais($def->atributos, $objetos, $vetores, $flag);
                    $vt_campos_reais_novo = array();
                    foreach ($vt_campos_reais as $campo_real) {
                        $vt_campos_reais_novo[] = $nome_obj.$campo_real;
                    }
                    $vt_campos = array_merge($vt_campos, $vt_campos_reais_novo);
                }
            }

        // Consultar apenas a chave primaria
        } elseif ($campos === false) {
            $vt_campos = array($this->get_chave());

        // Consultar campos desejados
        } elseif (is_array($campos)) {
            foreach ($campos as $chave => $campo) {

                // Vetor de campos
                if (is_array($campo)) {
                    $vt_campos = array_merge($vt_campos, $this->get_campos_reais($campo, $objetos, $vetores, $flag));

                // Atributo simples
                } elseif ($this->possui_atributo($campo)) {
                    $vt_campos[] = $campo;

                // Atributo implicito
                } elseif ($this->possui_atributo_implicito($campo)) {
                    if (!($flag & OBJETO_IGNORAR_IMPLICITOS)) {
                        $pos = strrpos($campo, ':');
                        $nome_obj = ($pos !== false) ? substr($campo, 0, $pos).':' : '';
                        $def = $this->get_definicao_implicito($campo);
                        if (is_array($def->atributos) && !empty($def->atributos)) {
                            $vt_atributos_necessarios = array();
                            foreach ($def->atributos as $atributo) {
                                $vt_atributos_necessarios[] = $nome_obj.$atributo;
                            }
                            $vt_campos_reais = $this->get_campos_reais($vt_atributos_necessarios, $objetos, $vetores, $flag);
                            $vt_campos = array_merge($vt_campos, $vt_campos_reais);
                        }
                    }

                // Relacionamento 1:1
                } elseif ($this->possui_rel_uu($campo)) {
                    $nome_obj = $campo;
                    $objetos[] = $nome_obj;

                    // Se pediu os campos necessarios para obter o nome do objeto
                    if ($flag & OBJETO_ADICIONAR_NOMES) {
                        $def_uu = $this->get_definicao_rel_uu($nome_obj);
                        $obj_uu = objeto::get_objeto($def_uu->classe);
                        $campo_nome = $obj_uu->get_campo_nome();
                        if ($campo_nome) {
                            if ($this->possui_atributo($nome_obj.':'.$campo_nome)) {
                                $vt_campos[] = $nome_obj.':'.$campo_nome;
                            } elseif ($this->possui_atributo_implicito($nome_obj.':'.$campo_nome)) {
                                $def = $obj_uu->get_definicao_implicito($campo_nome);
                                if (isset($def->atributos) && is_array($def->atributos)) {
                                    $vt_campo_nome = array();
                                    foreach ($def->atributos as $item_campo_nome) {
                                        $vt_campo_nome[] = $nome_obj.':'.$item_campo_nome;
                                    }
                                    $vt_campos = array_merge($vt_campos, $this->get_campos_reais($vt_campo_nome, $objetos, $vetores, $flag));
                                }
                            } else {
                                trigger_error('O objeto "'.$nome_obj.'" nao possui o atributo "'.$campo_nome.'"', E_USER_WARNING);
                            }
                        }
                    }

                    // Se pediu pelas chaves dos objetos
                    if ($flag & OBJETO_ADICIONAR_CHAVES) {
                        $pos = strrpos($nome_obj, ':');
                        if ($pos) {
                            $vt_campos[] = substr($nome_obj, 0, $pos).':'.$this->get_nome_chave_rel_uu($nome_obj);
                        } else {
                            $vt_campos[] = $this->get_nome_chave_rel_uu($nome_obj);
                        }
                        $vt_campos[] = $nome_obj.':'.$this->get_objeto_rel_uu($nome_obj)->get_chave();
                    }
                } elseif ($this->possui_rel_un($campo)) {
                    $vetores[] = $campo;
                }
            }
            $vt_campos = vetor::array_unique_recursivo($vt_campos);

        // Tipo incorreto
        } else {
            trigger_error('Tipo invalido para $campos ('.gettype($campos).')', E_USER_WARNING);
            $vt_campos = array();
        }

        // Se deseja realizar operacoes sobre o resultado
        if ($flag & OBJETO_REMOVER_CONSULTADOS) {
            $vt_novo = array();
            foreach ($vt_campos as $campo) {
                if (!$this->get_flag_consulta($campo)) {
                    $vt_novo[] = $campo;
                }
            }
            $vt_campos = $vt_novo;
            unset($vt_novo);
        }
        return $vt_campos;
    }


    //
    //     Converte um pedido de ordenacao em um vetor de campos reais
    //
    final public function get_campos_ordem($ordem) {
    // String || Bool || Array[String => Bool] $ordem: campo usado para ordenacao ou false para chave PK ou vetor de campos apontando para o tipo de ordenacao (true = crescente / false = decrescente)
    //
        if (is_string($ordem)) {
            $ordem = array($ordem => true);
        } elseif (is_bool($ordem)) {
            $ordem = array($this->get_chave() => true);
        } elseif (!is_array($ordem)) {
            if (!is_null($ordem)) {
                trigger_error('Tipo invalido "'.gettype($ordem).'"', E_USER_WARNING);
                $ordem = null;
            }
        }
        if (is_array($ordem)) {
            $ordem_novo = array();
            $flag = OBJETO_ADICIONAR_NOMES;
            foreach ($ordem as $campo_ordem => $tipo_ordem) {
                if ($this->possui_atributo($campo_ordem)) {
                    $ordem_novo[$campo_ordem] = $tipo_ordem;
                } elseif ($this->possui_atributo_implicito($campo_ordem)) {
                    $campos_reais = $this->get_campos_reais(array($campo_ordem), $objetos, $vetores, $flag);
                    foreach ($campos_reais as $campo_real) {
                        $ordem_novo[$campo_real] = $tipo_ordem;
                    }
                } elseif ($this->possui_rel_uu($campo_ordem)) {
                    $def = $this->get_definicao_rel_uu($campo_ordem);
                    $novo_campo_ordem = $this->get_objeto_rel_uu($campo_ordem)->get_campo_nome();
                    if ($novo_campo_ordem) {
                        $ordem = $ordem + $this->get_campos_ordem($campo_ordem.':'.$novo_campo_ordem);
                    }
                }
            }
            $ordem = $ordem_novo;
            unset($ordem_novo);
        }
        return $ordem;
    }


    //
    //     Consulta e retorna o valor de um atributo simples da classe
    //
    final public function get_atributo($nome_atributo) {
    // String $nome_atributo: nome do atributo
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Se nao possui o atributo, abortar
        if (!$this->possui_atributo($nome_atributo)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$nome_atributo.'"', E_USER_WARNING);
            return false;
        }

        // Se ainda nao consultou o atributo
        if (!$this->get_flag_consulta($nome_atributo)) {

            // Se o objeto nao existe, retorna false
            if (!$this->existe()) {
                trigger_error('O atributo "'.$nome_atributo.'" nao pode ser retornado pois o objeto nao existe', E_USER_NOTICE);
                return null;
            }

            // Consultar pela primeira vez
            $consultou = $this->consultar_campos(array($nome_atributo));
            trigger_error('O atributo "'.$nome_atributo.'" foi consultado sob demanda (classe "'.$this->get_classe().'")', E_USER_NOTICE);
            self::$consultas_por_demanda += 1;

            if (!$consultou) {
                return false;
            }
        }

        // Se conseguiu consultar
        return $this->get_valor($nome_atributo);
    }


    //
    //     Retorna o valor de um atributo implicito da classe
    //
    final public function get_atributo_implicito($nome_atributo) {
    // String $nome_atributo: nome do atributo implicito
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Se nao possui o atributo implicito, abortar
        if (!$this->possui_atributo_implicito($nome_atributo)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo implicito "'.$nome_atributo.'"', E_USER_WARNING);
            return false;
        }

        // Recuperar dados do atributo implicito
        $def = $this->get_definicao_implicito($nome_atributo);

        // Consultar campos utilizados para montar o atributo implicito
        if (is_array($def->atributos) && !empty($def->atributos)) {
            if ($this->existe()) {
                $this->consultar_campos($def->atributos);
            }
        }

        // Chamar o metodo que retorna o atributo implicito
        return call_user_func(array($this, $def->metodo));
    }


    //
    //     Obtem o valor de um atributo auxiliar
    //
    final public function get_auxiliar($nome_atributo) {
    // String $nome_atributo: nome do atributo auxiliar
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':')) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        if ($this->possui_auxiliar($nome_atributo)) {
            return $this->auxiliares[$nome_atributo];
        }
        trigger_error('O atributo auxiliar "'.$nome_atributo.'" nao existe no objeto', E_USER_WARNING);
        return null;
    }


    //
    //     Consulta os atributos simples e implicitos caso nao tenham sido consultados (para vetor utilize a consultar_vetor_rel_un)
    //
    final public function consultar_campos($campos, $flag = null, $consultar_memoria = true) {
    // Array[String] || Bool $campos: vetor de campos desejados (true = todos | false = apenas PK)
    // Int $flag: flag de consulta na camada DAO ou null para usar o valor padrao
    // Bool $consultar_memoria: indica se deve consultar dados da memoria (lista de instancias)
    //
        if (!$this->existe()) {
            trigger_error('O objeto da classe "'.$this->get_classe().'" nao pode consultar campos sem existir', E_USER_WARNING);
            return false;
        }
        return $this->consultar($this->get_chave(), $this->get_valor_chave(), $campos, $flag, $consultar_memoria);
    }


    //
    //     Define o valor de um atributo simples, passando por uma filtragem e validacao
    //
    final public function set_atributo($nome_atributo, $valor, $filtrar = true, $validar = true) {
    // String $nome_atributo: nome do atributo
    // Mixed $valor: valor do atributo
    // Bool $filtrar: flag indicando se o valor deve ser filtrado ou nao
    // Bool $validar: flag indicando se o valor deve ser validado ou nao
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Se nao validou: abortar
        if ($validar && !$this->validar_atributo($nome_atributo, $valor)) {
            return false;
        }

        // Se precisa filtrar
        if ($filtrar) {
            $valor = $this->filtrar_valor($nome_atributo, $valor);
        }
        $valor_flag = $this->get_valor_flag_mudanca($nome_atributo, $valor);

        $atribuiu = false;

        // Se e' a chave primaria: indicar que o objeto existe no BD
        if ($nome_atributo == $this->get_chave()) {
            if (self::$flag_bd) {
                $atribuiu = $this->set_valor_chave($valor);
            } else {
                trigger_error('Nao pode atribuir um valor para a chave primaria de '.$this->get_classe(), E_USER_WARNING);
                return false;
            }

        // Se o atributo e' a chave de um relacionamento: atualizar o objeto
        } elseif ($this->possui_rel_uu($nome_atributo, false)) {
            $atribuiu = $this->set_chave_rel_uu($nome_atributo, $valor);

        // Se e' um atributo qualquer
        } else {
            $atribuiu = $this->set_valor($nome_atributo, $valor);
        }

        if (!$atribuiu) {
            trigger_error('O atributo "'.$nome_atributo.'" nao pode ser atribuido na classe "'.$this->get_classe().'"', E_USER_WARNING);
            return false;
        }
        $this->set_flag_mudanca($nome_atributo, $valor_flag);
        return true;
    }


    //
    //     Define o dia de determinado atributo do tipo data
    //
    final public function set_dia($atributo, $dia) {
    // String $atributo: nome do atributo
    // Int $dia: valor do dia (1-31)
    //
        if ($dia < 1 || $dia > 31) {
            $def = $this->get_definicao_atributo($atributo);
            $this->erros[] = 'O dia deve estar entre 1 e 31 ('.$def->descricao.')';
            return false;
        }
        return $this->set_elemento_data($atributo, 'dia', sprintf('%02d', $dia));
    }


    //
    //     Define o mes de determinado atributo do tipo data
    //
    final public function set_mes($atributo, $mes) {
    // String $atributo: nome do atributo
    // Int $mes: valor do mes (1-12)
    //
        if ($mes < 1 || $mes > 12) {
            $def = $this->get_definicao_atributo($atributo);
            $this->erros[] = 'O m&ecirc;s deve estar entre 1 e 12 ('.$def->descricao.')';
            return false;
        }
        return $this->set_elemento_data($atributo, 'mes', sprintf('%02d', $mes));
    }


    //
    //     Define o ano de determinado atributo do tipo data
    //
    final public function set_ano($atributo, $ano) {
    // String $atributo: nome do atributo
    // Int $ano: valor do ano
    //
        if (!is_int($ano)) {
            $def = $this->get_definicao_atributo($atributo);
            $this->erros[] = 'O ano deve ser um inteiro ('.$def->descricao.')';
            return false;
        }
        return $this->set_elemento_data($atributo, 'ano', sprintf('%04d', $ano));
    }


    //
    //     Define a hora de determinado atributo do tipo data
    //
    final public function set_hora($atributo, $hora) {
    // String $atributo: nome do atributo
    // Int $hora: valor da hora (0-23)
    //
        if ($hora < 0 || $hora > 23) {
            $def = $this->get_definicao_atributo($atributo);
            $this->erros[] = 'A hora deve estar entre 0 e 23 ('.$def->descricao.')';
            return false;
        }
        return $this->set_elemento_data($atributo, 'hora', sprintf('%02d', $hora));
    }


    //
    //     Define o minuto de determinado atributo do tipo data
    //
    final public function set_minuto($atributo, $minuto) {
    // String $atributo: nome do atributo
    // Int $minuto: valor do minuto (0-59)
    //
        if ($minuto < 0 || $minuto > 59) {
            $def = $this->get_definicao_atributo($atributo);
            $this->erros[] = 'O minuto deve estar entre 0 e 59 ('.$def->descricao.')';
            return false;
        }
        return $this->set_elemento_data($atributo, 'minuto', sprintf('%02d', $minuto));
    }


    //
    //     Define o segundo de determinado atributo do tipo data
    //
    final public function set_segundo($atributo, $segundo) {
    // String $atributo: nome do atributo
    // Int $segundo: valor do segundo (0-59)
    //
        if ($segundo < 0 || $segundo > 59) {
            $def = $this->get_definicao_atributo($atributo);
            $this->erros[] = 'O segundo deve estar entre 0 e 59 ('.$def->descricao.')';
            return false;
        }
        return $this->set_elemento_data($atributo, 'segundo', sprintf('%02d', $segundo));
    }


    //
    //     Define o valor do atributo do tipo data a partir de um timestamp
    //
    final public function set_timestamp($atributo, $timestamp) {
    // String $atributo: nome do atributo
    // Int $timestamp: valor do timestamp
    //
        if (!is_numeric($timestamp)) {
            $def = $this->get_definicao_atributo($atributo);
            $this->erros[] = 'O timestamp precisa ser um n&uacute;mero inteiro ('.$def->descricao.')';
            return false;
        }
        return $this->set_atributo($atributo, strftime('%d-%m-%Y-%H-%M-%S', $timestamp), false, false);
    }


    //
    //     Define um elemento da data
    //
    private function set_elemento_data($atributo, $elemento, $valor) {
    // String $atributo: nome do atributo
    // String $elemento: codigo do elemento (dia, mes, ano, hora, minuto, segundo)
    // Int $valor: valor do elemento
    //
        $def = $this->get_definicao_atributo($atributo);
        if ($def->tipo != 'data') {
            trigger_error('O atributo '.$atributo.' nao eh do tipo data', E_USER_WARNING);
            return false;
        }
        $data = self::parse_data($this->__get($atributo));
        $data[$elemento] = $valor;
        $nova = implode('-', $data);
        return $this->set_valor($atributo, $nova);
    }


    //
    //     Obtem os elementos de um atributo do tipo data
    //
    final public function get_atributo_data($atributo, $formatado = true) {
    // String $atributo: nome do atributo do tipo data
    // Bool $formatado: indica se o vetor retornado tera valores na forma de string (true) ou inteiro (false)
    //
        return self::parse_data($this->__get($atributo), $formatado);
    }


    //
    //     Obtem o timestamp de um atributo do tipo data
    //
    final public function get_timestamp($atributo) {
    // String $atributo: nome do atributo do tipo data
    //
        $data = $this->get_atributo_data($atributo);
        return mktime($data['hora'], $data['minuto'], $data['segundo'], $data['mes'], $data['dia'], $data['ano']);
    }


    //
    //     Obtem a idade (em anos) de uma entidade atraves de um atributo do tipo data
    //
    final public function calcular_anos_atributo_data($atributo) {
    // String $atributo: nome do atributo do tipo data
    //
        $data = $this->get_atributo_data($atributo, false);
        $data_atual = self::parse_data(strftime('%d-%m-%Y'), false);

        $anos = $data_atual['ano'] - $data['ano'];
        if ($data_atual['mes'] < $data['mes']) {
            $anos -= 1;
        } elseif ($data_atual['mes'] == $data['mes']) {
            if ($data_atual['dia'] < $data['dia']) {
                $anos -= 1;
            }
        }
        return $anos;
    }


    //
    //     Soma componentes de data a um atributo de data
    //
    final public static function somar_data($data, $componentes) {
    // String $data: data no formato do Simp
    // Array[String => Int] $componentes: componentes de data a serem somados/subtraidos (posicoes dia, mes, ano, hora, minuto, segundo)
    //
        $d = self::parse_data($data, false);
        $elementos = array('dia', 'mes', 'ano', 'hora', 'minuto', 'segundo');
        foreach ($elementos as $elemento) {
            if (isset($componentes[$elemento])) {
                $d[$elemento] += $componentes[$elemento];
            }
        }
        $time = mktime($d['hora'], $d['minuto'], $d['segundo'], $d['mes'], $d['dia'], $d['ano']);
        return strftime('%d-%m-%Y-%H-%M-%S', $time);
    }


    //
    //     Checa se a data e' nula (ano zero)
    //
    final public function possui_data_nula($atributo) {
    // String $atributo: nome do atributo do tipo data
    //
        $data = $this->get_atributo_data($atributo, false);
        return $data['ano'] == 0;
    }


    //
    //     Compara um atributo (do tipo data) com uma data e retorna um numero:
    //     menor que 0 = se a primeira for menor que a segunda
    //     0           = se a primeira for igual a segunda
    //     maior que 0 = se a primeira for maior que a segunda
    //
    final public function comparar_data($atributo, $data) {
    // String $atributo: nome do atributo (do tipo data) a ser comparado
    // String $data: data no formato do Simp
    //
        return self::comparar_datas($this->__get($atributo), $data);
    }


    //
    //     Compara um atributo (do tipo data) com um horario e retorna um numero:
    //     menor que 0 = se a primeira for menor que a segunda
    //     0           = se a primeira for igual a segunda
    //     maior que 0 = se a primeira for maior que a segunda
    //
    final public function comparar_hora($atributo, $data) {
    // String $atributo: nome do atributo (do tipo data) a ser comparado
    // String $data: data no formato do Simp
    //
        return self::comparar_horas($this->__get($atributo), $data);
    }


    //
    //     Compara duas datas e retorna um numero:
    //     menor que 0 = se a primeira for menor que a segunda
    //     0           = se a primeira for igual a segunda
    //     maior que 0 = se a primeira for maior que a segunda
    //
    final public static function comparar_datas($data1, $data2) {
    // String $data1: primeira data no formato do Simp
    // String $data2: segunda data no formato do Simp
    //
        $componentes = array('ano', 'mes', 'dia', 'hora', 'minuto', 'segundo');
        return self::comparar_componentes_data($data1, $data2, $componentes);
    }


    //
    //     Compara duas horas e retorna um numero:
    //     menor que 0 = se a primeira for menor que a segunda
    //     0           = se a primeira for igual a segunda
    //     maior que 0 = se a primeira for maior que a segunda
    //
    final public static function comparar_horas($data1, $data2) {
    // String $data1: primeira data no formato do Simp
    // String $data2: segunda data no formato do Simp
    //
        $componentes = array('hora', 'minuto', 'segundo');
        return self::comparar_componentes_data($data1, $data2, $componentes);
    }


    //
    //     Compara componentes de data
    //
    final private static function comparar_componentes_data($data1, $data2, $componentes) {
    // String $data1: primeira data no formato do Simp
    // String $data2: segunda data no formato do Simp
    // Array[String] $componentes: componentes de data a serem comparados
    //
        $vt1 = self::parse_data($data1, false);
        $vt2 = self::parse_data($data2, false);

        foreach ($componentes as $componente) {
            if ($vt1[$componente] > $vt2[$componente]) {
                return 1;
            } elseif ($vt1[$componente] < $vt2[$componente]) {
                return -1;
            }
        }
        return 0;
    }


    //
    //     Calcula o valor a ser definido para a flag_mudanca de um atributo
    //
    private function get_valor_flag_mudanca($nome_atributo, $valor) {
    // String $nome_atributo: nome do atributo a ser testado
    // Mixed $valor: valor do atributo a ser testado
    //
        // Se o dado veio do BD
        if (self::$flag_bd) {
            $valor_flag = false;

        // Se o dado nao veio do BD, mas ja' existe: comparar com valor do BD
        } elseif ($this->existe()) {

            // Se o atributo ja' foi marcado como alterado: manter alterado
            if ($this->get_flag_mudanca($nome_atributo)) {
                $valor_flag = true;

            // Se ainda nao foi modificado, mas a entidade existe: checar o dado no BD
            } else {
                if (!$this->get_flag_consulta($nome_atributo)) {
                    $this->consultar_campos(array($nome_atributo));
                }
                $valor_antigo = $this->get_valor($nome_atributo);
                $def = $this->get_definicao_atributo($nome_atributo);
                switch ($def->tipo) {
                case 'int':
                case 'float':
                case 'char':
                case 'string':
                case 'binario':
                    $valor_flag = strcmp($valor_antigo, $valor) != 0;
                    break;
                case 'bool':
                    $valor_flag = $valor_antigo != $valor;
                    break;
                case 'data':
                    $data1 = self::parse_data($valor_antigo, false);
                    $data2 = self::parse_data($valor, false);
                    $valor_flag = $data1['dia'] != $data2['dia'] ||
                                  $data1['mes'] != $data2['mes'] ||
                                  $data1['ano'] != $data2['ano'] ||
                                  $data1['hora'] != $data2['hora'] ||
                                  $data1['minuto'] != $data2['minuto'] ||
                                  $data1['segundo'] != $data2['segundo'];
                    break;
                default:
                    trigger_error('Tipo desconhecido: '.$def->tipo, E_USER_WARNING);
                    $valor_flag = false;
                    break;
                }
            }

        // Se nao existe no BD ainda, entao o campo foi modificado
        } else {
            $valor_flag = true;
        }
        return $valor_flag;
    }


    //
    //     Define o valor de um atributo auxiliar
    //
    final public function set_auxiliar($nome_atributo, $valor) {
    // String $nome_atributo: nome do atributo auxiliar
    // Mixed $valor: valor do atributo auxiliar
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':')) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        $this->auxiliares[(string)$nome_atributo] = $valor;
        return true;
    }


    //
    //     Filtra o valor de um atributo simples para ser armazenado corretamente no Objeto
    //
    final public function filtrar_valor($nome_atributo, $valor) {
    // String $nome_atributo: nome do atributo
    // Mixed $valor: valor do atributo
    //
        // Se o dado veio do BD: assumir que nao precisa filtra-lo
        if (self::$flag_bd) {
            return $valor;
        }

        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':')) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Se o atributo nao existe, abortar
        if (!$this->possui_atributo($nome_atributo)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$nome_atributo.'"', E_USER_WARNING);
            return;
        }

        // Recuperar a definicao do atributo correspondente
        $def = $this->get_definicao_atributo_validacao($nome_atributo);

        // Se precisa passar por um filtro especifico
        if ($filtro = $def->filtro) {
            $classe = $def->classe;

            // Se o filtro e' da propria classe
            if ($classe == $this->get_classe()) {
                return call_user_func_array(array($this, $filtro), array($valor));

            // Se o filtro e' de outra classe
            } else {
                $outra = self::get_objeto($classe);
                return call_user_func_array(array($outra, $filtro), array($valor));
            }

        // Se nao usa um filtro especifico, filtrar pelo tipo de atributo
        } else {
            return $def->filtrar($valor);
        }
    }


    //
    //     Retorna se existem registros no BD com as condicoes especificadas (mais rapido que o metodo quantidade_registros)
    //
    final public function possui_registros($condicoes = null, $quantidade = 1) {
    // condicao_sql $condicoes: condicoes de consulta
    // Int $quantidade: quantidade de registros a ser verificada (retorna true se existirem pelo menos $quantidade registros)
    //
        $iterador = self::$dao->select_iterador($this, false, $condicoes, false, 1, $quantidade - 1);
        return ($iterador !== false) && (bool)$iterador->size();
    }


    //
    //     Retorna a quantidade de registro no BD com as condicoes especificadas
    //
    final public function quantidade_registros($condicoes = null) {
    // condicao_sql $condicoes: condicoes de consulta
    //
        return self::$dao->select_quantidade($this, $condicoes);
    }


    //
    //     Calcula a soma dos valores de um determinado atributo da entidade
    //
    final public function calcular_soma($campo, $condicoes = null) {
    // String $campo: nome do campo que sera somado
    // condicao_sql $condicoes: condicoes da busca
    //
        return self::$dao->select_soma($this, $campo, $condicoes);
    }


    //
    //     Consulta os dados pelo maior valor de um campo especificado
    //
    final public function consultar_maior($campo, $campos = false, $condicoes = null) {
    // String $campo: nome do campo que deve ser o maior
    // Array[String] || Bool $campos: vetor de campos desejados (true = todos; false = apenas PK)
    // condicao_sql $condicoes: condicoes da busca
    //
        return $this->consultar_extremo('maior', $campo, $campos, $condicoes);
    }


    //
    //     Consulta os dados pelo menor valor de um campo especificado
    //
    final public function consultar_menor($campo, $campos = false, $condicoes = null) {
    // String $campo: nome do campo que deve ser o menor
    // Array[String] || Bool $campos: vetor de campos desejados (true = todos; false = apenas PK)
    // condicao_sql $condicoes: condicoes da busca
    //
        return $this->consultar_extremo('menor', $campo, $campos, $condicoes);
    }


    //
    //     Consulta os dados dos extremos de um campo especificado
    //
    private function consultar_extremo($tipo_extremo, $campo, $campos, $condicoes, $consultar_memoria = true) {
    // String $tipo_extremo: maior ou menor
    // String $campo: nome do campo que deve ser o extremo
    // Array[String] || Bool $campos: vetor de campos desejados (true = todos; false = apenas PK)
    // condicao_sql $condicoes: condicoes da busca
    // Bool $consultar_memoria: indica se deve consultar primeiro os dados na memoria (lista de instancias)
    //

        // Checar se o campo testado e' um atributo simples
        if (!$this->possui_atributo($campo)) {
            trigger_error('Consulta ao maior campo precisa utilizar um atributo simples (classe "'.$this->get_classe().'" / campo "'.$campo.'")', E_USER_WARNING);
            return false;
        }

        switch ($tipo_extremo) {
        case 'maior':
            $extremo = self::$dao->select_maior($this, $campo, $condicoes);
            break;
        case 'menor':
            $extremo = self::$dao->select_menor($this, $campo, $condicoes);
            break;
        }
        if ($extremo === null) {
            $def = $this->get_definicao_atributo($campo);
            switch ($this->get_entidade()) {
            case 'M':
                $this->erros[] = 'Erro ao consultar o '.$this->get_entidade().' com '.$tipo_extremo.' '.$def->descricao;
                break;
            case 'F':
                $this->erros[] = 'Erro ao consultar a '.$this->get_entidade().' com '.$tipo_extremo.' '.$def->descricao;
                break;
            case 'I':
                $this->erros[] = 'Erro ao consultar '.$this->get_entidade().' com '.$tipo_extremo.' '.$def->descricao;
                break;
            }
            return null;
        }

        if (is_array($campos)) {
            $campos[] = $campo;
        } elseif ($campos === false) {
            $campos = array($campo);
        }

        if ($condicoes) {
            $vt_condicoes = array();
            $vt_condicoes[] = condicao_sql::montar($campo, '=', $extremo);
            $vt_condicoes[] = $condicoes;
            $condicoes = condicao_sql::sql_and($vt_condicoes);
            return $this->consultar_condicoes($condicoes, $campos, null);
        }
        $condicoes = condicao_sql::montar($campo, '=', $extremo);
        return $this->consultar($campo, $extremo, $campos, null, $consultar_memoria);
    }


    //
    //     Consulta varias entidades apartir de uma condicao e retorna o iterador
    //
    final public function consultar_varios_iterador($condicoes = null, $campos = false, $ordem = false, $limite = false, $inicio = 0, $montar_objeto = true) {
    // condicao_sql $condicoes: condicoes da busca
    // Array[String] || Bool $campos: campos desejados (true = todos | false = apenas PK)
    // Array[String => Bool] || String $ordem: nome do campo usado para ordenacao crescente ou vetor de campos usado para ordenacao apontando para a flag que indica se a ordem e' crescente (true) ou decrescente (false)
    // Int $limite: numero maximo de resultados retornados
    // Int $inicio: numero inicial dos resultados (offset iniciado em 0)
    // Bool $montar_objeto: indica se retornara um iterador de objetos entidade ou apenas objetos stdClass
    //
        // Campos a serem consultados
        $flag = OBJETO_ADICIONAR_NOMES | OBJETO_ADICIONAR_CHAVES;
        $vt_campos = $this->get_campos_reais($campos, $objetos, $vetores, $flag);

        // Checar ordem
        $ordem = $this->get_campos_ordem($ordem);

        if ($limite) {
            $limite = max(0, intval($limite));
        }
        $inicio = max(0, intval($inicio));

        $iterador = self::$dao->select_iterador($this, $vt_campos, $condicoes, $ordem, $limite, $inicio);
        $iterador->set_montar_objeto($montar_objeto);
        return $iterador;
    }


    //
    //     Consulta varias entidades apartir de uma condicao
    //
    final public function consultar_varios($condicoes = null, $campos = false, $ordem = false, $index = false, $limite = false, $inicio = 0, $montar_objeto = true) {
    // condicao_sql $condicoes: condicoes da busca
    // Array[String] || Bool $campos: campos desejados (true = todos | false = apenas PK)
    // Array[String => Bool] || String $ordem: nome do campo usado para ordenacao crescente ou vetor de campos usado para ordenacao apontando para a flag que indica se a ordem e' crescente (true) ou decrescente (false)
    // String $index: campo usado para indexacao ou null para nenhum
    // Int $limite: numero maximo de resultados retornados
    // Int $inicio: numero inicial dos resultados (offset iniciado em 0)
    // Bool $montar_objeto: indica se retornara um vetor de objetos entidade ou um vetor de objetos stdClass
    //
        // Campos a serem consultados
        $flag = OBJETO_ADICIONAR_NOMES | OBJETO_ADICIONAR_CHAVES;
        $vt_campos = $this->get_campos_reais($campos, $objetos, $vetores, $flag);

        // Checar ordem
        $ordem = $this->get_campos_ordem($ordem);

        // Checar index
        if ($index) {
            if ($this->possui_atributo($index)) {
                $vt_campos[] = $index;
            } else {
                trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$index.'"', E_USER_WARNING);
                $index = false;
            }
        }

        if ($limite) {
            $limite = max(0, intval($limite));
        }
        $inicio = max(0, intval($inicio));

        // Consultar no BD
        $iterador = self::$dao->select_iterador($this, $vt_campos, $condicoes, $ordem, $limite, $inicio);

        // Se nao conseguiu consultar
        if (is_bool($iterador)) {
            switch ($this->get_genero()) {
            case 'M':
                $this->erros[] = 'Erro ao consultar os '.$this->get_entidade(true);
                break;
            case 'F':
                $this->erros[] = 'Erro ao consultar as '.$this->get_entidade(true);
                break;
            case 'I':
                $this->erros[] = 'Erro ao consultar '.$this->get_entidade(true);
                break;
            }

            if (!DEVEL_BLOQUEADO && self::$dao->possui_erros()) {
                $this->erros[] = '[DEBUG-DEVEL] Erros na camada DAO:';
                $this->erros[] = self::$dao->get_erros();
            }

            return false;
        }

        // Gerar codigo especifico para guardar vetor indexado
        if ($index) {
            $caminho_index = '$registro->'.str_replace(':', '->', $index);
        } else {
            $caminho_index = '';
        }

        $retorno = array();

        if ($montar_objeto) {

            // Desabilitar validacao para recuperar os dados do BD
            $classe = $this->get_classe();
            $chave = $this->get_chave();
            $php = <<<PHP
\$obj = self::get_objeto('{$classe}');
\$ativou_flag_bd = false;
if (!self::\$flag_bd) {
    self::\$flag_bd = true;
    \$ativou_flag_bd = true;
}
foreach (\$iterador as \$registro) {
    if (self::possui_instancia('{$classe}', \$registro->{$chave})) {
        \$obj->consultar('', \$registro->{$chave});
    }
    \$obj->set_valores(\$registro);
    \$retorno[{$caminho_index}] = clone(\$obj);
    \$obj->limpar_objeto();
}
if (\$ativou_flag_bd) {
    self::\$flag_bd = false;
}

PHP;
            eval($php);

        } else {

            $php = <<<PHP
foreach (\$iterador as \$registro) {
    \$retorno[{$caminho_index}] = \$registro;
}
PHP;
            eval($php);
        }

        return $retorno;
    }


    //
    //     Retorna um vetor associativo montado com uma consulta (especialmente util para montar campos select)
    //
    final public function vetor_associativo($campo_index = false, $campo_valor = false, $condicoes = null, $ordem = null, $limite = null, $inicio = 0) {
    // String $campo_index: nome do campo usado para indexacao do vetor (padrao: PK)
    // String $campo_valor: nome do campo usado para guardar os valores (padrao: retorno de get_campo_nome())
    // condicao_sql $condicoes: condicoes da busca (padrao: nenhuma)
    // Array[String => Bool] $ordem: vetor de campos usados para ordenacao e a especificacao se a ordem e' crescente (true) ou descrescente (false)
    // Int $limite: numero maximo de resultados retornados
    // Int $inicio: numero inicial dos resultados (offset iniciado em 0)
    //
        // Recuperar o campo de indexacao e de apresentacao
        if (!$campo_index) {
            $campo_index = $this->get_chave();
        } elseif (!$this->possui_atributo($campo_index) && !$this->possui_atributo_implicito($campo_index)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$campo_index.'"', E_USER_WARNING);
            return false;
        }
        if (!$campo_valor) {
            $campo_valor = $this->get_campo_nome();
        } elseif (!$this->possui_atributo($campo_valor) && !$this->possui_atributo_implicito($campo_valor)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$campo_valor.'"', E_USER_WARNING);
            return false;
        }

        // Recuperar os campos a serem consultados
        $campos = $this->get_campos_reais(array($campo_index, $campo_valor), $objetos);
        foreach ($objetos as $nome_obj) {
            $def = $this->get_definicao_rel_uu($nome_obj);
            $campos_obj = $nome_obj.':'.self::get_objeto($def->classe)->get_campo_nome();
        }

        // Se nao tem ordem, ordenar pelo $campo_valor e $campo_index
        if (!$ordem) {
            $ordem = array($campo_valor => true,
                           $campo_index => true);
            $ordem = $this->get_campos_ordem($ordem);
        } else {
            $ordem = $this->get_campos_ordem($ordem);
        }

        // Consultar no BD
        $iterador = self::$dao->select_iterador($this, $campos, $condicoes, $ordem, $limite, $inicio);
        if (is_bool($iterador)) {
            switch ($this->get_genero()) {
            case 'M':
                $this->erros[] = 'Erro ao consultar os '.$this->get_entidade(true);
                break;
            case 'F':
                $this->erros[] = 'Erro ao consultar as '.$this->get_entidade(true);
                break;
            case 'I':
                $this->erros[] = 'Erro ao consultar '.$this->get_entidade(true);
                break;
            }

            if (!DEVEL_BLOQUEADO && self::$dao->possui_erros()) {
                $this->erros[] = '[DEBUG-DEVEL] Erros na camada DAO:';
                $this->erros[] = self::$dao->get_erros();
            }

            return false;
        } elseif ($iterador->size() == 0) {
            return array();
        }

        // Criar vetor associativo
        $associativo = array();

        $classe = $this->get_classe();
        $chave  = $this->get_chave();

        $obj = self::get_objeto($classe);
        $ativou_flag_bd = false;
        if (!self::$flag_bd) {
            self::$flag_bd = true;
            $ativou_flag_bd = true;
        }
        foreach ($iterador as $item) {
            $obj->set_valores($item);
            $associativo[$obj->__get($campo_index)] = (string)$obj->__get($campo_valor);
            $obj->limpar_objeto();
        }
        unset($obj);
        if ($ativou_flag_bd) {
            self::$flag_bd = false;
        }

        return $associativo;
    }


    //
    //     Retorna um vetor associativo hierarquico baseado em um objeto de relacionamento 1:1 (especialmente util para montar campos select com grupos)
    //
    final public function vetor_associativo_hierarquico($objeto_agrupamento, $campo_agrupamento = false, $campo_index = false, $campo_valor = false, $condicoes = null, $condicoes_agrupamento = null, $ordem = null, $ordem_agrupamento = null) {
    // String $objeto_agrupamento: nome do objeto de relacionamento 1:1 usado para categorizar os itens
    // String $campo_agrupamento: nome do campo do objeto de relacionamento 1:1 usado para nomear os agrupamentos (padrao: retorno de get_nome() da classe objeto_agrupamento)
    // String $campo_index: nome do campo usado para indexacao do vetor (padrao: PK)
    // String $campo_valor: nome do campo usado para guardar os valores (padrao: retorno de get_nome())
    // condicao_sql $condicoes: condicoes da busca
    // condicao_sql $condicoes_agrupamento: condicoes da consulta do objeto de agrupamento
    // Array[String => Bool] || String $ordem: campo usado para ordenacao
    // Array[String => Bool] || String $ordem_agrupamento: campo usado para ordenacao do objeto de agrupamento
    //
        if (!$this->possui_rel_uu($objeto_agrupamento)) {
            trigger_error('Parametro invalido para $objeto_agrupamento (esperado um objeto da classe)', E_USER_WARNING);
            return false;
        }

        // Obter objeto de agrupamento e dados necessarios para consulta
        $obj = $this->get_objeto_rel_uu($objeto_agrupamento);
        if (!$campo_agrupamento) {
            $campo_agrupamento = $obj->get_campo_nome();
        }
        if (!$obj->possui_atributo($campo_agrupamento) && !$obj->possui_atributo_implicito($campo_agrupamento)) {
            trigger_error('A classe "'.$obj->get_classe().'" nao possui o atributo "'.$campo_agrupamento.'"', E_USER_WARNING);
            return false;
        }
        $chave_fk = $this->get_nome_chave_rel_uu($objeto_agrupamento);
        $pos = strpos($objeto_agrupamento, ':');
        $prefixo = ($pos === false) ? '' : substr($objeto_agrupamento, 0, $pos).':';

        // Consultar registros de agrupamento
        $grupos = $obj->consultar_varios($condicoes_agrupamento, array($campo_agrupamento), $ordem_agrupamento);
        unset($condicoes_agrupamento, $ordem_agrupamento);

        // Vetor a ser retornado
        $vetor = array();

        // Checar se e' um relacionamento fraco
        $def = $this->get_definicao_rel_uu($objeto_agrupamento);
        if (!$def->forte) {
            if ($condicoes) {
                $condicao_extra = condicao_sql::montar($prefixo.$chave_fk, '=', null);
                $condicoes_agrupamento = condicao_sql::sql_and(array($condicoes, $condicao_extra));
            } else {
                $condicoes_agrupamento = condicao_sql::montar($prefixo.$chave_fk, '=', null);
            }
            $subvetor = $this->vetor_associativo($campo_index, $campo_valor, $condicoes_agrupamento, $ordem);
            $index = '[Sem '.$obj->get_entidade().']';
            $vetor[$index] = $subvetor ? $subvetor : array();
        }

        // Percorrer os valores de cada grupo
        foreach ($grupos as $grupo) {
            if ($condicoes) {
                $condicao_extra = condicao_sql::montar($prefixo.$chave_fk, '=', $grupo->get_valor_chave());
                $condicoes_agrupamento = condicao_sql::sql_and(array($condicoes, $condicao_extra));
            } else {
                $condicoes_agrupamento = condicao_sql::montar($prefixo.$chave_fk, '=', $grupo->get_valor_chave());
            }
            $subvetor = $this->vetor_associativo($campo_index, $campo_valor, $condicoes_agrupamento, $ordem);
            $index = (string)$grupo->__get($campo_agrupamento);
            $vetor[$index] = $subvetor ? $subvetor : array();
        }

        return $vetor;
    }


    //
    //     Retorna um vetor associativo hierarquico montado com condicoes de agrupamento (especialmente util para montar campos select com grupos)
    //
    final public function vetor_associativo_condicoes($vt_condicoes, $campo_index = false, $campo_valor = false, $ordem = null) {
    // Array[String => condicao_sql] $vt_condicoes: vetor com o nome do grupo e a respectiva condicao
    // String $campo_index: nome do campo usado para indexacao do vetor (padrao: PK)
    // String $campo_valor: nome do campo usado para guardar os valores (padrao: retorno de get_nome())
    // Array[String => Bool] || String $ordem: campo usado para ordenacao
    //
        if (!is_array($vt_condicoes) || empty($vt_condicoes)) {
            trigger_error('O vetor de condicoes nao pode ser vazio', E_USER_WARNING);
            return false;
        }

        $vetor = array();
        foreach ($vt_condicoes as $nome_grupo => $condicao) {
            $vetor[$nome_grupo] = $this->vetor_associativo($campo_index, $campo_valor, $condicao, $ordem);
        }
        return $vetor;
    }


    //
    //     Retorna um vetor associativo hierarquico montado com condicoes de agrupamento baseadas em um campo enum (especialmente util para montar campos select com grupos)
    //
    final public function vetor_associativo_enum($campo_enum, $campo_index = false, $campo_valor = false, $ordem = null, $condicoes_extras = null) {
    // String $campo_enum: nome do campo usado para agrupamento (enum: deve existir um metodo chamado get_vetor_{campo_enum})
    // String $campo_index: nome do campo usado para indexacao do vetor (padrao: PK)
    // String $campo_valor: nome do campo usado para guardar os valores (padrao: retorno de get_nome())
    // Array[String => Bool] || String $ordem: campo usado para ordenacao
    // condicao_sql $condicoes_extras: condicoes extras para obtencao dos elementos
    //
        if (!$this->possui_atributo($campo_enum)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$campo_enum.'"', E_USER_WARNING);
            return false;
        }
        if (!method_exists($this, 'get_vetor_'.$campo_enum)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o metodo "get_vetor_'.$campo_enum.'"', E_USER_WARNING);
            return false;
        }
        $vt_grupos = call_user_func(array($this, 'get_vetor_'.$campo_enum));
        $vt_condicoes = array();
        foreach ($vt_grupos as $cod_grupo => $nome_grupo) {
            $condicao_enum = condicao_sql::montar($campo_enum, '=', $cod_grupo);
            if ($condicoes_extras) {
                $condicao_enum = condicao_sql::sql_and(array($condicao_enum, $condicoes_extras));
            }
            $vt_condicoes[$nome_grupo] = $condicao_enum;
        }
        return $this->vetor_associativo_condicoes($vt_condicoes, $campo_index, $campo_valor, $ordem);
    }


    //
    //     Salva os dados da entidade no BD (realiza a insercao ou atualizacao dos dados da entidade)
    //
    final public function salvar($campos = true) {
    // Array[String] $campos: campos a serem salvos (true salva apenas os campos modificados)
    //
        $e = new stdClass();
        $e->singular = $this->get_entidade();
        $e->plural   = $this->get_entidade(true);
        $e->genero   = $this->get_genero();
        return $this->salvar_entidade($campos, $e);
    }


    //
    //     Salva os dados da entidade no BD (realiza a insercao ou atualizacao dos dados da entidade)
    //
    private function salvar_entidade($campos, $dados_entidade) {
    // Array[String] $campos: campos a serem salvos (true salva apenas os campos modificados)
    // stdClass $dados_entidade: dados da entidade no singular, plural e genero
    //

        // Se ja' existem erros, nem tenta salvar
        if ($this->possui_erros()) {
            trigger_error('Nao pode salvar um objeto que ainda possui erros', E_USER_WARNING);
            return false;
        }

        // Obtem a lista de campos que sofreram alteracoes
        $salvar_campos = $this->get_campos_modificados($campos);
        $count_salvar_campos = count($salvar_campos);

        // Se o objeto nao existe ainda: Setar os valores padrao nao preenchidos
        if (!$this->existe()) {
            $set_padrao = true;
            foreach ($this->get_atributos() as $def_atributo) {
                if (isset($salvar_campos[$def_atributo->nome])) {
                    continue;
                }

                // Se possui valor padrao
                if (!is_null($def_atributo->padrao)) {
                    switch ($def_atributo->tipo) {
                    case 'data':
                        if ($def_atributo->padrao == 'agora') {
                            $padrao = strftime('%d-%m-%Y-%H-%M-%S');
                        } else {
                            $padrao = $def_atributo->padrao;
                        }
                        break;
                    default:
                        $padrao = $def_atributo->padrao;
                        break;
                    }
                    $set_padrao = $this->__set($def_atributo->nome, $padrao) && $set_padrao;
                    $salvar_campos[$def_atributo->nome] = $padrao;

                // Se e' uma chave fraca: definir como nulo
                } elseif ($def_atributo->chave == 'OFK') {
                    $set_padrao = $this->__set($def_atributo->nome, null) && $set_padrao;
                    $salvar_campos[$def_atributo->nome] = null;

                // Se nao pode ser vazio e nao definiu um valor
                } elseif (!$def_atributo->pode_vazio &&
                          $def_atributo->chave != 'PK') {
                    trigger_error('O atributo "'.$def_atributo->nome.'" da classe "'.$this->get_classe().'" nao pode ser nulo, nao foi definido e nao possui valor padrao', E_USER_WARNING);
                    return false;
                }
            }
            if (!$set_padrao) {
                return false;
            }
            $count_salvar_campos = count($salvar_campos);
        }

        // Checar chaves unicas compostas
        if ($this->instancia->flag_unicidade && !empty($this->definicao->uk_compostas)) {

            $atributos = array_keys($this->get_atributos());
            foreach ($this->definicao->uk_compostas as $indices_campos_uk) {
                $classe = $this->get_classe();
                $vt_definicoes_uk = array();
                $vt_valores_uk    = array();
                $vt_condicoes_uk  = array();
                $campos_chave_unica_composta = array();
                $mudou_chave_composta = false;

                // Obter os nomes dos campos que formam a chave unica composta
                foreach ($indices_campos_uk as $indice_campo_uk) {
                    $campo_uk = $atributos[$indice_campo_uk];
                    $campos_chave_unica_composta[] = $campo_uk;
                    if (isset($salvar_campos[$campo_uk])) {
                        $mudou_chave_composta = true;
                    }
                }

                // Se nenhum atributo que faz parte da chave composta mudou, entao nao precisa verificar
                if (!$mudou_chave_composta) {
                    continue;
                }

                // Consultar os campos necessarios para checar a chave unica composta
                if ($this->existe()) {
                    $flag_consulta = OBJETO_ADICIONAR_NOMES | OBJETO_ADICIONAR_CHAVES | OBJETO_REMOVER_CONSULTADOS;
                    $campos_chave_unica_composta_com_nome = $campos_chave_unica_composta;
                    foreach ($campos_chave_unica_composta as $campo_uk) {
                        if ($this->possui_rel_uu($campo_uk, false)) {
                            $campos_chave_unica_composta_com_nome[] = $this->get_nome_objeto_rel_uu($campo_uk);
                        }
                    }
                    $this->consultar_campos($campos_chave_unica_composta_com_nome, $flag_consulta);
                    unset($campos_chave_unica_composta_com_nome);
                } else {
                    foreach ($campos_chave_unica_composta as $campo_uk) {
                        if ($this->possui_rel_uu($campo_uk, false)) {
                            $obj_aux = $this->get_objeto_rel_uu($campo_uk, false);
                            if ($obj_aux->existe()) {
                                $obj_aux->consultar_campos(array($obj_aux->get_campo_nome()));
                            }
                        }
                    }
                }

                // Montar as condicoes de checagem da unicidade
                foreach ($campos_chave_unica_composta as $campo_uk) {
                    $def_campo_uk = $this->get_definicao_atributo($campo_uk);
                    switch ($def_campo_uk->chave) {
                    case 'FK':
                    case 'OFK':
                        $nome_objeto_uk = $this->get_nome_objeto_rel_uu($campo_uk);
                        $vt_definicoes_uk[] = $this->get_entidade_rel_uu($campo_uk, false);
                        $vt_valores_uk[] = '"'.$this->imprimir_objeto_rel_uu($nome_objeto_uk, true, false).'" ('.$this->exibir($campo_uk).')';
                        break;
                    default:
                        $vt_definicoes_uk[] = $def_campo_uk->descricao;
                        $vt_valores_uk[] = '"'.$this->exibir($campo_uk).'"';
                        break;
                    }
                    $vt_condicoes_uk[] = condicao_sql::montar($campo_uk, '=', $this->__get($campo_uk));
                }
                $condicoes_uk = condicao_sql::sql_and($vt_condicoes_uk);
                unset($vt_condicoes_uk);

                $obj_uk = self::get_objeto($classe);

                $flag_consulta = OBJETO_ADICIONAR_NOMES | OBJETO_ADICIONAR_CHAVES;
                $obj_uk->consultar_condicoes($condicoes_uk, $campos_chave_unica_composta, $flag_consulta);
                if ($obj_uk->existe() && $obj_uk->get_valor_chave() != $this->get_valor_chave()) {

                    switch ($this->get_genero()) {
                    case 'M':
                        $this->erros[] = 'N&atilde;o podem existir dois ou mais '.$this->get_entidade(true).' com os campos '.implode(', ', $vt_definicoes_uk).' iguais (valor j&aacute; existente: '.implode(', ', $vt_valores_uk).')';
                        break;
                    case 'F':
                        $this->erros[] = 'N&atilde;o podem existir duas ou mais '.$this->get_entidade(true).' com os campos '.implode(', ', $vt_definicoes_uk).' iguais (valor j&aacute; existente: '.implode(', ', $vt_valores_uk).')';
                        break;
                    case 'I':
                        $this->erros[] = 'N&atilde;o podem existir dois ou mais '.$this->get_entidade(true).' com os campos '.implode(', ', $vt_definicoes_uk).' iguais (valor j&aacute; existente: '.implode(', ', $vt_valores_uk).')' ;
                        break;
                    }
                }
            }
            if ($this->possui_erros()) {
                return false;
            }
        }

        // Nao inserir/alterar a chave primaria
        if (isset($salvar_campos[$this->get_chave()])) {
            trigger_error('Nao pode alterar a chave primaria', E_USER_NOTICE);
            unset($salvar_campos[$this->get_chave()]);
        }

        // Se foi consultado do BD, atualiza-lo (UPDATE)
        if ($this->existe()) {

            // Se nao existem campos a serem salvos
            if (!$count_salvar_campos) {
                switch ($dados_entidade->genero) {
                case 'M':
                    $this->avisos[] = 'Nenhum dado do '.$dados_entidade->singular.' foi alterado';
                    break;
                case 'F':
                    $this->avisos[] = 'Nenhum dado da '.$dados_entidade->singular.' foi alterado';
                    break;
                case 'I':
                    $this->avisos[] = 'Nenhum dado de '.$dados_entidade->singular.' foi alterado';
                    break;
                }

                // Caso nenhum campo seja modificado, retorna true
                return true;
            }

            // Atualizar usando a chave primaria
            $condicoes = condicao_sql::montar($this->get_chave(), '=', $this->get_valor_chave());

            $s = self::$dao->update($this, (object)$salvar_campos, $condicoes);

            // Se atualizou corretamente
            if ($s) {
                switch ($dados_entidade->genero) {
                case 'M':
                    $this->avisos[] = $dados_entidade->singular.' atualizado com sucesso';
                    break;
                case 'F':
                    $this->avisos[] = $dados_entidade->singular.' atualizada com sucesso';
                    break;
                case 'I':
                    $this->avisos[] = 'Dados de '.$dados_entidade->singular.' atualizados com sucesso';
                    break;
                }

                if (!DEVEL_BLOQUEADO) {
                    $this->avisos[] = '[DEBUG-DEVEL] Os seguintes campos foram alterados: '.implode(', ', array_keys($salvar_campos));
                }

            // Se ocorreu um erro ao atualizar
            } else {
                switch ($dados_entidade->genero) {
                case 'M':
                    $this->erros[] = 'Erro ao alterar os dados do '.$dados_entidade->singular;
                    break;
                case 'F':
                    $this->erros[] = 'Erro ao alterar os dados da '.$dados_entidade->singular;
                    break;
                case 'I':
                    $this->erros[] = 'Erro ao alterar os dados de '.$dados_entidade->singular;
                    break;
                }

                if (!DEVEL_BLOQUEADO && self::$dao->possui_erros()) {
                    $this->erros[] = '[DEBUG-DEVEL] Erros na camada DAO:';
                    $this->erros[] = self::$dao->get_erros();
                }

            }

            // Gerar Log (caso nao seja a propria classe de logs)
            $pk = $this->get_valor_chave();
            if (self::get_flag_log() && $this->get_classe() != 'log_sistema') {
                $log = new log_sistema();
                $detalhes = '';
                foreach ($salvar_campos as $campo => $valor) {
                    $detalhes .= $campo.'='.util::exibir_var($valor, UTIL_EXIBIR_PHP).'; ';
                }
                $detalhes = rtrim($detalhes);
                $id_usuario = defined('COD_USUARIO') ? COD_USUARIO : 0;
                if (!$log->inserir($id_usuario, LOG_UPDATE, ($s ? 0 : 1), $pk, $this->get_classe(), $detalhes)) {
                    $this->erros[] = 'Erro ao gerar o log';
                    $this->erros[] = $log->get_erros();
                }
            }

            // Atualizar entidade PHP
            if (!$this->possui_erros()) {

                // Agora os campos ja' foram salvos, logo nao foram modificados
                reset($salvar_campos);
                foreach ($salvar_campos as $campo => $valor) {
                    $this->set_flag_mudanca($campo, false);
                }

                // Se a entidade esta' em cache: atualiza'-la
                if (self::em_cache($this->get_classe(), $pk)) {
                    self::set_cache($this->get_classe(), $pk);
                }
            }

        // Se e' um novo objeto, inseri-lo (INSERT)
        } else {

            // Inserir
            $s = self::$dao->insert($this, (object)$salvar_campos);

            // Se inseriu corretamente
            $pk = intval($s);
            if ($s) {
                switch ($dados_entidade->genero) {
                case 'M':
                    $this->avisos[] = $dados_entidade->singular.' cadastrado com sucesso';
                    break;
                case 'F':
                    $this->avisos[] = $dados_entidade->singular.' cadastrada com sucesso';
                    break;
                case 'I':
                    $this->avisos[] = 'Dados de '.$dados_entidade->singular.' cadastrados com sucesso';
                    break;
                }

            // Se ocorreu um erro ao inserir
            } else {
                switch ($dados_entidade->genero) {
                case 'M':
                    $this->erros[] = 'Erro ao cadastrar os dados do '.$dados_entidade->singular;
                    break;
                case 'F':
                    $this->erros[] = 'Erro ao cadastrar os dados da '.$dados_entidade->singular;
                    break;
                case 'I':
                    $this->erros[] = 'Erro ao cadastrar os dados de '.$dados_entidade->singular;
                    break;
                }

                if (!DEVEL_BLOQUEADO && self::$dao->possui_erros()) {
                    $this->erros[] = '[DEBUG-DEVEL] Erros na camada DAO:';
                    $this->erros[] = self::$dao->get_erros();
                }

            }

            // Gerar Log
            if (self::get_flag_log() && $this->get_classe() != 'log_sistema') {
                $log = new log_sistema();
                $detalhes = '';
                foreach ($salvar_campos as $campo => $valor) {
                    $detalhes .= $campo.'='.util::exibir_var($valor, UTIL_EXIBIR_PHP).'; ';
                }
                $detalhes = rtrim($detalhes);
                $id_usuario = defined('COD_USUARIO') ? COD_USUARIO : 0;
                if (!$log->inserir($id_usuario, LOG_INSERT, ($s ? 0 : 1), $pk, $this->get_classe(), $detalhes)) {
                    $this->erros[] = 'Erro ao gerar o log';
                    $this->erros[] = $log->get_erros();
                }
            }

            // Atualizar entidade PHP
            if (!$this->possui_erros()) {

                // Definir a chave primaria
                if (is_numeric($s)) {
                    $ativou_flag_bd = false;
                    if (!self::$flag_bd) {
                        self::$flag_bd = true;
                        $ativou_flag_bd = true;
                    }
                    $this->set_atributo($this->get_chave(), $s);
                    if ($ativou_flag_bd) {
                        self::$flag_bd = false;
                    }

                    // Agora os campos ja' foram salvos, logo nao foram modificados
                    foreach ($this->get_atributos() as $campo => $def) {
                        $this->set_flag_mudanca($campo, false);
                    }

                }

                // Se a entidade esta em cache: atualiza'-la
                if (self::em_cache($this->get_classe(), $pk)) {
                    self::set_cache($this->get_classe(), $pk);
                }
            }
        }

        if ($this->possui_erros()) {
            return false;
        }

        // Se salvou algum campo, retorna o numero de campos modificados
        return $count_salvar_campos;
    }


    //
    //     Operacoes realizadas antes de salvar os dados no BD
    //
    public function pre_salvar(&$salvar_campos) {
    // Array[String] $salvar_campos: vetor de campos a serem salvos
    //
        return true;    // Metodo reservado para ser sobrescrito pelas classes filhas
    }


    //
    //     Operacoes realizadas apos salvar os dados no BD
    //
    public function pos_salvar() {
        return true;    // Metodo reservado para ser sobrescrito pelas classes filhas
    }


    //
    //     Exclui PERMANENTEMENTE a entidade do BD (e os seus elementos filhos, dependendo do SGBD)
    //
    final public function excluir() {
        $e = new stdClass();
        $e->singular = $this->get_entidade();
        $e->plural   = $this->get_entidade(true);
        $e->genero   = $this->get_genero();
        return $this->excluir_entidade($e);
    }


    //
    //     Exclui PERMANENTEMENTE a entidade do BD (e os seus elementos filhos, dependendo do SGBD)
    //
    private function excluir_entidade($dados_entidade) {
    // stdClass $dados_entidade: dados da entidade no singular, plural e genero
    //
        // Se nao foi consultado do BD
        if (!$this->existe()) {
            trigger_error('Nao se pode excluir um objeto que nao foi consultado', E_USER_WARNING);
            return false;
        }

        $gerar_log = self::get_flag_log() && $this->get_classe() != 'log_sistema';

        // Obter detalhes do log
        if ($gerar_log) {
            $this->consultar_campos(true);
            $detalhes = '';
            foreach ($this->get_atributos() as $atributo => $def) {
                $detalhes .= $atributo.'='.util::exibir_var($this->get_atributo($atributo), UTIL_EXIBIR_PHP).'; ';
            }
            $detalhes = rtrim($detalhes);
        }

        // Se esta tudo OK

        // Utilizar chave primaria para montar a condicao de exclusao
        $pk = $this->get_valor_chave();
        $condicoes = condicao_sql::montar($this->get_chave(), '=', $pk);

        // Excluir
        $r = self::$dao->delete($this, $condicoes);

        // Gerar Mensagem
        if ($r) {
            switch ($dados_entidade->genero) {
            case 'M':
                $this->avisos[] = $dados_entidade->singular.' exclu&iacute;do do sistema';
                break;
            case 'F':
                $this->avisos[] = $dados_entidade->singular.' exclu&iacute;da do sistema';
                break;
            case 'I':
                $this->avisos[] = $dados_entidade->singular.' exclu&iacute;do(a) do sistema';
                break;
            }
        } else {
            switch ($dados_entidade->genero) {
            case 'M':
                $this->erros[] = 'Erro ao excluir o '.$dados_entidade->singular;
                break;
            case 'F':
                $this->erros[] = 'Erro ao excluir a '.$dados_entidade->singular;
                break;
            case 'I':
                $this->erros[] = 'Erro ao excluir '.$dados_entidade->singular;
                break;
            }

            if (!DEVEL_BLOQUEADO && self::$dao->possui_erros()) {
                $this->erros[] = '[DEBUG-DEVEL] Erros na camada DAO:';
                $this->erros[] = self::$dao->get_erros();
            }

        }

        // Gerar Log
        if ($gerar_log) {
            $log = new log_sistema();
            $id_usuario = defined('COD_USUARIO') ? COD_USUARIO : 0;
            if (!$log->inserir($id_usuario, LOG_DELETE, ($r ? 0 : 1), $pk, $this->get_classe(), $detalhes)) {
                $r = false;
                $this->erros[] = 'Erro ao gerar log';
                $this->erros[] = $log->get_erros();
            }
        }

        // Se excluiu com sucesso: apagar dados do objeto (menos os avisos e auxiliares)
        if ($r) {

            // Os valores de todas as instancias que apontam para o registro devem ser apagados
            $this->instancia->valores = array();
            $this->instancia->objetos = array();
            $this->instancia->vetores = array();

            //$this->limpar_auxiliares();
            //$this->limpar_avisos();
            $this->limpar_erros();
            $this->limpar_instancia();
            $this->zerar_flags();

            // Se a entidade esta' em cache: atualiza-la
            if (self::em_cache($this->get_classe(), $pk)) {
                self::limpar_cache($this->get_classe(), $pk);
            }
        }

        return !$this->possui_erros();
    }


    //
    //     Realiza as operacoes de (1) pre-salvar, (2) salvar ou excluir e (3) pos-salvar utilizando uma unica transacao
    //
    final public function salvar_completo($salvar_campos, $operacao = 'salvar', $modo_transacao = DRIVER_BASE_MODO_PADRAO) {
    // Array[String] $salvar_campos: campos a serem salvos
    // String $operacao: 'salvar' ou 'excluir'
    // Int $modo_transacao: tipo de transacao
    //
        $e = new stdClass();
        $e->singular = $this->get_entidade();
        $e->plural   = $this->get_entidade(true);
        $e->genero   = $this->get_genero();
        return $this->salvar_completo_entidade($salvar_campos, $operacao, $e, $modo_transacao);
    }


    //
    //     Realiza as operacoes de (1) pre-salvar, (2) salvar ou excluir e (3) pos-salvar utilizando uma unica transacao
    //
    private function salvar_completo_entidade($salvar_campos, $operacao, $dados_entidade, $modo_transacao = DRIVER_BASE_MODO_PADRAO) {
    // Array[String] $salvar_campos: campos a serem salvos
    // String $operacao: 'salvar' ou 'excluir'
    // stdClass $dados_entidade: dados da entidade no singular, plural e genero
    // Int $modo_transacao: tipo de transacao
    //
        $salvo = true;
        $iniciou_transacao = false;

        // Se nao iniciou uma transacao: inicia-la
        if (!self::$em_transacao) {
            $salvo = $salvo && self::inicio_transacao($modo_transacao);
            $iniciou_transacao = true;
        }

        // Realizar operacoes antes de salvar
        $salvo = $salvo &&  $this->pre_salvar($salvar_campos);

        switch ($operacao) {
        case 'salvar':
            $avisos_filhos = array();

            // Salvar os objetos filhos
            foreach ($this->get_definicoes_rel_uu() as $chave_fk => $def) {
                $nome_obj = $def->nome;
                $def_obj = $this->get_definicao_rel_uu($nome_obj);

                // Se nao pediu para salvar o objeto filho
                if (!isset($salvar_campos[$nome_obj]) && !isset($salvar_campos[$chave_fk])) {
                    if (!$this->existe() && !$this->get_objeto_rel_uu($nome_obj)->existe() && $def_obj->forte) {
                        $def_fk = $this->get_definicao_atributo($chave_fk);
                        if (is_null($def_fk->padrao)) {
                            trigger_error('Os dados do objeto "'.$nome_obj.'" (relacionamento forte) nao foram setados na classe "'.$this->get_classe().'"', E_USER_WARNING);
                            $salvo = false;
                        } else {
                            $salvo = $salvo && $this->__set($chave_fk, $def_fk->padrao);
                            $salvar_campos[] = $chave_fk;
                            continue;
                        }
                    }
                    continue;
                }
                $salvar_campos_obj = $salvar_campos[$nome_obj];

                // Salva primeiro os objetos filhos
                $e = new stdClass();
                $e->singular = $this->get_entidade_rel_uu($nome_obj);
                $e->plural = $this->get_objeto_rel_uu($nome_obj)->get_entidade(true);
                $e->genero = $this->get_objeto_rel_uu($nome_obj)->get_genero();
                $salvou_obj = $this->get_objeto_rel_uu($nome_obj)->salvar_completo_entidade($salvar_campos_obj, $operacao, $e);
                $salvo = $salvo && $salvou_obj;

                // Se salvou, obter os avisos e a chave-primaria
                if ($salvou_obj) {
                    $this->__set($chave_fk, $this->get_objeto_rel_uu($nome_obj)->get_valor_chave());
                    $salvar_campos[] = $chave_fk;
                    $avisos_filhos = array_merge($avisos_filhos, $this->get_objeto_rel_uu($nome_obj)->get_avisos());
                    $this->get_objeto_rel_uu($nome_obj)->limpar_avisos();

                // Se nao salvou, obter os erros
                } else {
                    switch ($this->get_objeto_rel_uu($nome_obj)->get_genero()) {
                    case 'M':
                        $de = 'do';
                        break;
                    case 'F':
                        $de = 'da';
                        break;
                    case 'I':
                        $de = 'de';
                        break;
                    }
                    $this->erros[] = 'Erro ao salvar os dados '.$de.' '.$this->get_objeto_rel_uu($nome_obj)->get_entidade();
                    $this->erros[] = $this->get_objeto_rel_uu($nome_obj)->get_erros();
                    $this->get_objeto_rel_uu($nome_obj)->limpar_erros();
                }

                // Se o objeto filho nao foi modificado e e' forte
                if (!$this->existe() && !$this->get_objeto_rel_uu($nome_obj)->existe() && $def_obj->forte) {
                    trigger_error('Os dados do objeto "'.$nome_obj.'" (relacionamento forte) nao foram setados na classe "'.$this->get_classe().'"', E_USER_WARNING);
                    $salvo = false;
                }
                unset($salvar_campos[$nome_obj]);
            }

            // Salvar-se
            if ($this->salvar_entidade($salvar_campos, $dados_entidade)) {
                if (!empty($avisos_filhos)) {
                    $this->avisos[] = $avisos_filhos;
                }
            } else {
                $salvo = false;
            }
            break;

        case 'excluir':

            // Excluir vetores relacionados
            foreach ($this->get_vetores_rel_un() as $nome_vetor => $vetor) {
                if (empty($vetor)) {
                    continue;
                }
                $def = $this->get_definicao_rel_un($nome_vetor);
                $obj = self::get_objeto($def->classe);
                $excluiu_vet = true;
                foreach ($vetor as $item) {
                    $excluiu_vet = $excluiu_vet && $item->salvar_completo($salvar_campos, $operacao);
                }
                $salvo = $salvo && $excluiu_vet;
                if ($excluiu_vet) {
                    $entidade = $obj->get_entidade(true);
                    switch ($obj->get_genero()) {
                    case 'M':
                        $this->avisos[] = $entidade.' relacionados exclu&iacute;dos com sucesso';
                        break;
                    case 'F':
                        $this->avisos[] = $entidade.' relacionadas exclu&iacute;das com sucesso';
                        break;
                    case 'I':
                        $this->avisos[] = $entidade.' relacionados(as) exclu&iacute;dos(as) com sucesso';
                        break;
                    }
                }
            }

            // Excluir-se
            $salvo = $salvo && $this->excluir_entidade($dados_entidade);
            break;
        }

        // Realizar operacoes apos salvar
        $salvo = $salvo && $this->pos_salvar();

        if ($iniciou_transacao) {
            $salvo = self::fim_transacao(!$salvo) && $salvo;
        }

        // Se alguma operacao falhou
        if (!$salvo) {

            // Apagar os avisos para o usuario nao se confundir com a mensagem (que na verdade sofreu um ROLLBACK)
            $this->limpar_avisos();

            // Informar sobre o ROLLBACK apenas para quem abriu a transacao
            if ($iniciou_transacao) {
                switch ($dados_entidade->genero) {
                case 'M':
                    $de = 'do';
                    break;
                case 'F':
                    $de = 'da';
                    break;
                case 'I':
                    $de = 'de';
                    break;
                }
                $this->erros[] = 'Alguma opera&ccedil;&atilde;o falhou ao salvar os dados '.$de.' '.$dados_entidade->singular.' e todo o processo foi cancelado.';
            }
            return false;
        }
        return true;
    }


    //
    //     Valida os atributos simples informados ou os do proprio objeto
    //
    final public function validar_atributos($dados = false) {
    // Object $dados: dados a serem validados ou false para validar o proprio objeto
    //
        // Se os dados estao vindo do BD, assumir que ja' passaram por validacao para serem salvos
        if (self::$flag_bd) {
            return true;
        }

        // Validar os dados informados
        if ($dados) {
            foreach ($dados as $campo => $valor) {
                if (!$this->validar_atributo($campo, $valor)) {
                    return false;
                }
            }

            // Validacao Final
            return $this->validacao_final($dados);

        // Validar o proprio objeto
        } else {
            foreach ($this->get_atributos() as $atributo => $def) {
                if ($this->get_flag_consulta($atributo) &&
                    !$this->validar_atributo($atributo, $this->get_valor($atributo))) {
                    return false;
                }
            }

            // Validacao Final
            $dados = (object)$this->instancia->valores;
            return $this->validacao_final($dados);
        }
    }


    //
    //     Valida um atributo simples
    //
    final public function validar_atributo($nome_atributo, $valor) {
    // String $nome_atributo: nome do atributo
    // Mixed $valor: valor a ser testado
    //
        // Se veio do BD, assumir que esta' valido
        if (self::$flag_bd) {
            return true;
        }

        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Recuperar a definicao do atributo especificado
        $atributo = clone($this->get_definicao_atributo_validacao($nome_atributo));

        // Filtra-lo antes
        if ($filtro = $atributo->filtro) {
            $classe = $atributo->classe;

            // Se o filtro e' da propria classe
            if ($classe == $this->get_classe()) {
                $valor = call_user_func_array(array($this, $filtro), array($valor));

            // Se o filtro e' de outra classe
            } else {
                $outra = self::get_objeto($classe);
                $valor = call_user_func_array(array($outra, $filtro), array($valor));
            }
        }

        // Trocar o nome da chave pelo nome da entidade relacionada
        switch ($atributo->chave) {
        case 'FK':
        case 'OFK':
            $atributo->descricao = $this->get_entidade_rel_uu($atributo->nome, false);
            break;
        }

        $validacao = validacao::get_instancia();

        // Fazer a validacao Geral
        $valido = $validacao->validar_atributo($atributo, $valor, $erros);
        if (!$valido) {
            $this->erros = array_merge($this->erros, $erros);
        }

        // Fazer a validacao de unicidade
        if ($atributo->unico && $this->instancia->flag_unicidade) {
            $nome_classe = $this->get_classe();
            $nome_chave  = $this->get_chave();
            $chave       = $this->existe() ? $this->get_valor_chave() : 0;

            $o = self::get_objeto($nome_classe);
            $o->consultar($atributo->nome, $valor);
            if ($o->existe() && ($o->get_valor_chave() != $chave)) {
                $this->erros[] = "Campo \"{$atributo->descricao}\" j&aacute; est&aacute; cadastrado e n&atilde;o pode se repetir";
                $valido = false;
            }
        }

        // Fazer a validacao especifica, caso exista
        if ($metodo = $atributo->validacao_especifica) {
            $classe = $atributo->classe;

            // Se o atributo e' da propria classe
            if ($classe == $this->get_classe()) {
                $valido = $valido &&
                          call_user_func_array(array($this, $metodo), array($valor, $atributo));

            // Se o atributo e' de outra classe
            } else {
                $outra = self::get_objeto($classe);
                $valido = $valido &&
                          call_user_func_array(array($outra, $metodo), array($valor, $atributo));
            }
        }
        return $valido;
    }


    //
    //     Realiza a validacao dos atributos unicos da entidade
    //
    final public function validar_unicidade() {
        $r = true;

        // Obter campos unicos da entidade
        $campos_unicos = $this->campos_unicos();

        // Se nao possui campos unicos, nao ha o que validar
        if (empty($campos_unicos)) {
            return true;
        }

        // Montar as condicoes de consulta
        $vt_condicoes = array();
        foreach ($campos_unicos as $campo_unico) {
            if ($this->get_flag_consulta($campo_unico)) {
                $vt_condicoes[] = condicao_sql::montar($campo_unico, '=', $this->__get($campo_unico));
            }
        }

        // Se nao possui campos unicos consultados, nao ha o que validar
        if (empty($vt_condicoes)) {
            return true;
        }

        $condicoes = condicao_sql::sql_or($vt_condicoes);

        // Se o objeto existe, entao nao deve desconsidera-lo do retorno da consulta
        if ($this->existe()) {
            $vt_condicoes = array();
            $vt_condicoes[] = condicao_sql::montar($this->get_chave(), '<>', $this->get_valor_chave());
            $vt_condicoes[] = $condicoes;
            $condicoes = condicao_sql::sql_and($vt_condicoes);
        }
        unset($vt_condicoes);

        // Checar se existe algum registro com o valor identico ao informado
        $objetos = $this->consultar_varios($condicoes, $campos_unicos);

        // Se encontrou um registro com os valores unicos
        if (!empty($objetos)) {
            $erros_unicidade = array();

            // Checar qual valor se repetiu para cada objeto consultado
            foreach ($objetos as $obj) {
                foreach ($campos_unicos as $campo_unico) {
                    if (isset($erros_unicidade[$campo_unico])) { continue; }
                    if ($this->__get($campo_unico) == $obj->__get($campo_unico)) {
                        $def = $this->get_definicao_atributo($campo_unico);
                        if ($def->chave == 'FK') {
                            $entidade_relacionada = $this->get_entidade_rel_uu($campo_unico, false);
                            $entidade = $this->get_entidade();
                            switch ($this->get_genero()) {
                            case 'M':
                                $erros_unicidade[$campo_unico] = "J&aacute; existe um {$entidade} relacionado com o(a) {$entidade_relacionada} escolhido(a)";
                                break;
                            case 'F':
                                $erros_unicidade[$campo_unico] = "J&aacute; existe uma {$entidade} relacionada com o(a) {$entidade_relacionada} escolhido(a)";
                                break;
                            case 'I':
                                $erros_unicidade[$campo_unico] = "J&aacute; existe um(a) {$entidade} relacionado(a) com o(a) {$entidade_relacionada} escolhido(a)";
                                break;
                            }
                        } else {
                            $erros_unicidade[$campo_unico] = "J&aacute; existe \"{$def->descricao}\" com o valor \"".$obj->exibir($campo_unico)."\" e este campo n&atilde;o pode se repetir";
                        }
                    }
                }
            }
            if (!empty($erros_unicidade)) {
                $r = false;
                $this->erros = $this->erros + array_values($erros_unicidade);
            }
        }
        return $r;
    }


    //
    //     Retorna se os atributos simples do objeto sao iguais (==) aos atributos de outro objeto (exceto a chave primaria)
    //
    final public function igual($objeto) {
    // objeto $objeto: um objeto derivado da classe objeto
    //
        // Checar se e' derivado da classe objeto
        if (!($objeto instanceof self)) {
            return false;
        }

        // Checar se e' da mesma classe
        if ($objeto->get_classe() != $this->get_classe()) {
            return false;
        }

        // Checar valores dos atributos
        $this->consultar_campos(true);
        $objeto->consultar_campos(true);
        foreach ($this->get_atributos() as $atributo => $def) {
            if ($def->chave == 'PK') { continue; }
            if ($this->__get($atributo) != $objeto->__get($atributo)) {
                return false;
            }
        }
        return true;
    }


    //
    //     Quebra uma data em partes, retornando um vetor com as posicoes: dia, mes, ano, hora, minuto e segundo
    //
    final public static function parse_data($data, $formatado = true) {
    // String $data: data no formato dd-mm-aaaa-HH-MM-SS
    // Bool $formatado: indica se o valor retornado deve ter formato dd-mm-aaaa-HH-MM-SS com o numero exato de digitos (tamanho 19)
    //
        switch ($data) {
        case false:
        case '':
            return self::parse_data('00-00-0000-00-00-00', $formatado);
        case 'agora':
            return self::parse_data(strftime('%d-%m-%Y-%H-%M-%S'), $formatado);
        default:
            $vetor = explode('-', $data);
            if ($formatado) {
                return array(
                    'dia'     => sprintf('%02d', isset($vetor[0]) ? $vetor[0] : 1),
                    'mes'     => sprintf('%02d', isset($vetor[1]) ? $vetor[1] : 1),
                    'ano'     => sprintf('%04d', isset($vetor[2]) ? $vetor[2] : 0),
                    'hora'    => sprintf('%02d', isset($vetor[3]) ? $vetor[3] : 0),
                    'minuto'  => sprintf('%02d', isset($vetor[4]) ? $vetor[4] : 0),
                    'segundo' => sprintf('%02d', isset($vetor[5]) ? $vetor[5] : 0)
                );
            } else {
                return array(
                    'dia'     => isset($vetor[0]) ? (int)$vetor[0] : 1,
                    'mes'     => isset($vetor[1]) ? (int)$vetor[1] : 1,
                    'ano'     => isset($vetor[2]) ? (int)$vetor[2] : 0,
                    'hora'    => isset($vetor[3]) ? (int)$vetor[3] : 0,
                    'minuto'  => isset($vetor[4]) ? (int)$vetor[4] : 0,
                    'segundo' => isset($vetor[5]) ? (int)$vetor[5] : 0
                );
            }
        }
    }


/// @ METODOS DE INTERFACE DIRETA COM O USUARIO (VIEW)


    //
    //     Imprime algum conteudo antes do quadro de dados
    //
    public function pre_imprimir_dados($campos) {
    // Array[String] $campos: campos solicitados
    //
        return '';
    }


    //
    //     Imprime algum conteudo apos o quadro de dados
    //
    public function pos_imprimir_dados($campos) {
    // Array[String] $campos: campos solicitados
    //
        return '';
    }


    //
    //     Imprime apenas os dados desejados
    //
    final public function imprimir_dados($campos = true, $return = false, $chaves = true, $titulo = false, $popup = false) {
    // Array[String || String => Array[String]] || Bool $campos: campos que se deseja imprimir (true = todos)
    // Bool $return: retorna ou imprime os campos
    // Bool $chaves: imprime as chaves ou os nomes correspondentes dos relacionamentos
    // String $titulo: titulo que sera impresso no topo da caixa onde estao os dados
    // Bool $popup: inserir classe de "popup" no div dos dados
    //
        global $CFG, $USUARIO;

        if (!$this->pode_exibir($USUARIO, $motivo)) {
            $aviso = 'N&atilde;o &eacute; poss&iacute;vel acessar estes dados';
            if ($motivo) {
                $aviso .= " (Motivo: {$motivo})";
            }
            mensagem::aviso($aviso);
            return null;
        }

        if (!$this->existe()) {
            trigger_error('Nao pode exibir os dados de um objeto nao consultado', E_USER_WARNING);
            return null;
        }

        // Gerar log
        $log = new log_sistema();
        $id_usuario = isset($USUARIO) ? $USUARIO->get_valor_chave() : 0;
        $log->inserir($id_usuario, LOG_CONSULTA, false, $this->get_valor_chave(), $this->get_classe(), '');

        $class = $popup ? 'conteudo_popup' : 'dados';

        $s = "<div class=\"{$class}\">\n";

        // Imprimir Titulo, caso desejado
        if ($titulo) {
            $s .= "   <strong class=\"titulo\">{$titulo}</strong>\n";
        }

        $s .= $this->pre_imprimir_dados($campos);

        // Imprimir todos os campos
        if ($campos === true) {
            $this->consultar_campos(true);
            $campos = array();

            // Imprimir os atributos simples da classe
            foreach ($this->get_atributos() as $atributo) {
                $s .= $this->imprimir_atributo_filtrado($atributo->nome, $chaves);
            }

            // Imprimir os atributos implicitos da classe
            foreach ($this->get_implicitos() as $nome => $dados) {
                $s .= $this->imprimir_atributo($nome, true);
            }

            // Imprimir os atributos dos relacionamentos 1:1
            foreach ($this->get_objetos_rel_uu() as $nome_obj => $obj) {
                $s .= $this->imprimir_atributo($nome_obj, true);
            }

            // Imprimir os atributos dos relacionamentos 1:N
            foreach ($this->get_vetores_rel_un() as $nome_vet => $vet) {
                $s .= $this->imprimir_vetor_rel_un($nome_vet, true, false);
            }

        // Imprimir campos selecionados
        } elseif (is_array($campos)) {
            $id_regiao = 0;
            $vt_campos = array();
            $regioes   = array();
            $possui_campos_reais = array();
            foreach ($campos as $chave => $campo) {
                $eh_regiao = is_array($campo);
                $vt_aux = array();
                if (is_string($campo)) {
                    $vt_aux = array($campo);
                } elseif ($eh_regiao) {
                    $vt_aux = $campo;
                } else {
                    trigger_error('Informado um tipo invalido ('.gettype($campo).')', E_USER_WARNING);
                    continue;
                }
                $objetos = $vetores = array();
                $vt_campos_reais = $this->get_campos_reais($vt_aux, $objetos, $vetores, OBJETO_ADICIONAR_NOMES);
                $possui_campos_reais[$chave] = !empty($vt_campos_reais) || !empty($objetos) || !empty($vetores);

                // Se nao possui campos reais, verificar se sao implicitos, pelo menos
                if (!$possui_campos_reais[$chave]) {
                    foreach ($vt_aux as $campo_aux) {
                        if ($this->possui_atributo_implicito($campo_aux)) {
                            $possui_campos_reais[$chave] = true;
                            break;
                        }
                    }
                }

                // Determinar nomes das regioes
                if ($possui_campos_reais[$chave]) {

                    // Incluir campos a serem consultados (exceto os vetores)
                    $vt_campos = array_merge($vt_campos, $vt_campos_reais, $objetos);
                    if ($eh_regiao) {
                        $id_regiao += 1;
                        $regioes[$chave] = 'fieldset'.$id_regiao;
                    }
                }
            }

            // Consultar os campos para evitar muitas consultas sob demanda
            $this->consultar_campos($vt_campos);

            // Imprimir indice de regioes
            if (count($regioes) > 3) {
                $links_regioes = array();
                $link_base = $CFG->site;
                $dados_link_base = parse_url($link_base);
                unset($dados_link_base['fragment']);
                $link_base = link::montar_url($dados_link_base);
                $count_links_regioes = 0;
                foreach ($regioes as $regiao => $id_regiao) {
                    $link_regiao = $link_base.'#'.$id_regiao;
                    $links_regioes[] = '<a href="'.$link_regiao.'">'.$regiao.'</a>';
                    $count_links_regioes++;
                }
                $s .= '<p><strong>&Iacute;ndice:</strong></p>';
                $s .= '<table class="indice">';
                $s .= '<tbody>';
                for ($i = 0; $i < $count_links_regioes; $i += 3) {
                    $s .= '<tr>';
                    for ($j = 0; $j < 3; $j++) {
                        if (isset($links_regioes[$i + $j])) {
                            $s .= '<td>'.$links_regioes[$i + $j].'</td>';
                        } else {
                            $s .= '<td></td>';
                        }
                    }
                    $s .= '</tr>';
                }
                $s .= '</tbody>';
                $s .= '</table>';
            }

            // Imprimir os atributos da classe
            foreach ($campos as $chave => $campo) {
                if (is_array($campo)) {
                    if ($possui_campos_reais[$chave]) {
                        $s .= "<fieldset id=\"drag_{$regioes[$chave]}\">".
                              "<legend class=\"drag\" id=\"{$regioes[$chave]}\">{$chave}</legend>\n";
                        if ($this->possui_dados($campo)) {
                            foreach ($campo as $c) {
                                if ($c == '-') {
                                    $s .= "<hr />\n";
                                } else {
                                    $s .= $this->imprimir_atributo_filtrado($c, $chaves);
                                }
                            }
                        } else {
                            $s .= "<p>N&atilde;o possui ou n&atilde;o foi definido(a)</p>\n";
                        }
                        $s .= "</fieldset>\n";
                    }
                } elseif ($campo == '-') {
                    $s .= "<hr />\n";
                } else {
                    $s .= $this->imprimir_atributo_filtrado($campo, $chaves);
                }
            }
        } else {
            trigger_error('Tipo invalido "'.gettype($campos).'"', E_USER_ERROR);
        }

        $s .= $this->pos_imprimir_dados($campos);
        $s .= "</div>\n";

        if ($return) {
            return $s;
        }
        echo $s;
    }


    //
    //     Retorna o atributo filtrando as chaves ou nao
    //
    final protected function imprimir_atributo_filtrado($campo, $chaves = false) {
    // String $campo: nome do atributo
    // Bool $chaves: exibir as chaves ou ignora-las
    //
        // Se usou a notacao objeto:atributo
        if (strpos($campo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        $s = '';

        // Imprimir atributos e chaves
        if ($chaves) {
            $s .= $this->imprimir_atributo($campo, true);

        // Imprimir apenas atributos
        } else {

            // Atributo Simples
            if ($this->possui_atributo($campo)) {

                $a = $this->get_definicao_atributo($campo);

                switch ($a->chave) {

                // Omitindo as chaves primarias
                case 'PK':
                    break;

                // Imprimir o nome dos objetos relacionados
                case 'OFK':
                case 'FK':
                    $nome_obj = $this->get_nome_objeto_rel_uu($campo);
                    $s .= $this->imprimir_objeto_rel_uu($nome_obj, true, true);
                    break;

                // Imprimir atributo simples
                default:
                    $s .= $this->imprimir_atributo($campo, true);
                    break;
                }

            // Atributo Implicito
            } elseif ($this->possui_atributo_implicito($campo)) {
                $s .= $this->imprimir_atributo($campo, true);

            // Imprimir nome do objeto 1:1
            } elseif ($this->possui_rel_uu($campo)) {
                $s .= $this->imprimir_objeto_rel_uu($campo, true);

            // Imprimir vetor
            } elseif ($this->possui_rel_un($campo)) {
                $s .= $this->imprimir_vetor_rel_un($campo, true);
            }
        }
        return $s;
    }


    //
    //     Imprime um atributo (simples, implicito, relacionamento 1:1 ou 1:N) na forma padrao
    //
    final public function imprimir_atributo($nome_atributo, $return = false, $imprimir_descricao = true, $descricao_alternativa = false) {
    // String $nome_atributo: nome do atributo
    // Bool $return: retorna ou imprime o campo
    // Bool $imprimir_descricao: imprime a descricao do atributo
    // String $descricao_alternativa: descricao alternativa para o atributo
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        $a = '';

        // Caso seja um atributo simples ou implicito
        if (($tipo_a = $this->possui_atributo($nome_atributo)) ||
            ($tipo_i = $this->possui_atributo_implicito($nome_atributo))) {

            // Caso seja um atributo simples
            if ($tipo_a) {
                $valor     = $this->exibir_atributo($nome_atributo);
                $definicao = $this->get_definicao_atributo($nome_atributo);
                $descricao = $definicao->descricao;
                $tipo      = $definicao->tipo;

            // Caso seja um atributo implicito
            } else {
                $dados     = $this->definicao->implicitos[$nome_atributo];
                $valor     = $this->exibir_atributo_implicito($nome_atributo);
                $descricao = $dados->descricao;
                $tipo      = util::get_tipo($valor);
                switch ($tipo) {
                default:
                case 'string':
                case 'int':
                case 'float':
                    break;
                case 'null':
                    $valor = 'N&atilde;o (nulo)';
                    break;
                case 'bool':
                    $valor = $valor ? 'Sim' : 'N&atilde;o';
                    break;
                }
            }
            $descricao = texto::codificar($descricao);
            if ($imprimir_descricao) {
                $descricao = $descricao_alternativa ? $descricao_alternativa : $descricao;
                if (strpos($valor, "\n") !== false) {
                    $valor_nl = nl2br($valor);
                    $a = "<p><strong>{$descricao}:</strong><div>{$valor_nl}</div></p>";
                } else {
                    $a = "<p><strong>{$descricao}:</strong> {$valor}</p>";
                }
            } else {
                $a = (string)$valor;
            }

        // Caso seja um relacionamento 1:1
        } elseif ($this->possui_rel_uu($nome_atributo)) {
            $descricao_alternativa_obj = $descricao_alternativa ? $descricao_alternativa
                                                                : $this->get_entidade_rel_uu($nome_atributo);
            $a = $this->imprimir_objeto_rel_uu($nome_atributo, true, true, $descricao_alternativa_obj);

        // Caso seja um relacionamento 1:N
        } elseif ($this->possui_rel_un($nome_atributo)) {
            $a = $this->imprimir_vetor_rel_un($nome_atributo, true, $imprimir_descricao);
        }

        if ($return) {
            return $a;
        }
        echo $a;
    }


    //
    //     Retorna um atributo simples, implicito, auxiliar, relacionamento 1:1 ou 1:N na forma como deve ser apresentada visualmente
    //
    final public function exibir($nome_atributo) {
    // String $nome_atributo: nome do atributo a ser exibido
    //
        if ($this->possui_atributo($nome_atributo)) {
            return $this->exibir_atributo($nome_atributo);
        } elseif ($this->possui_atributo_implicito($nome_atributo)) {
            return $this->exibir_atributo_implicito($nome_atributo);
        } elseif ($this->possui_rel_uu($nome_atributo)) {
            return $this->get_objeto_rel_uu($nome_atributo)->get_nome();
        } elseif ($this->possui_rel_un($nome_atributo)) {
            $nomes = array();
            foreach ($this->get_vetor_rel_un($nome_atributo) as $item) {
                $nomes[] = texto::codificar($item->get_nome());
            }
            return implode(', ', $nomes);
        } elseif ($this->possui_auxiliar($nome_atributo)) {
            return texto::codificar($this->get_auxiliar($nome_atributo));
        }
        trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$nome_atributo.'"', E_USER_WARNING);
        return false;
    }


    //
    //     Define a forma como um atributo simples e' exibido (pode ser sobrecarregado, desde que este metodo pai seja chamado no final)
    //
    public function exibir_atributo($nome_atributo) {
    // String $nome_atributo: nome do atributo a ser exibido
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        if (!$this->possui_atributo($nome_atributo)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o atributo "'.$nome_atributo.'"', E_USER_WARNING);
            return null;
        }

        // Se a entidade nao existe, entao nao possui o valor do atributo
        if (!$this->existe() && !$this->get_flag_consulta($nome_atributo)) {
            return 'Nenhum(a)';
        }

        // Obter a definicao e o valor do atributo
        $definicao = $this->get_definicao_atributo($nome_atributo);
        $valor = $this->get_atributo($nome_atributo);

        // Se tem um vetor que define os codigos do atributo
        $metodo = 'get_vetor_'.$nome_atributo;
        if (method_exists($this, $metodo)) {
            $vetor = call_user_func(array($this, $metodo));
            if (isset($vetor[$valor])) {
                return $vetor[$valor];
            }
        }
        return $definicao->exibir($valor);
    }


    //
    //     Define a forma como um atributo implicito e' exibido (pode ser sobrecarregado, desde que este metodo pai seja chamado no final)
    //
    public function exibir_atributo_implicito($nome_atributo) {
    // String $nome_atributo: nome do atributo implicito a ser exibido
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        $valor = $this->get_atributo_implicito($nome_atributo);
        switch (util::get_tipo($valor)) {
        case 'object':
            if ($valor instanceof self) {
                $valor = $valor->get_nome();
            }
            break;
        default:
            $valor = util::exibir_var($valor, UTIL_EXIBIR_NATURAL);
        }
        return texto::codificar($valor);
    }


    //
    //     Define a forma como um atributo do tipo data e' exibido atraves de um formato
    //
    final public function exibir_data($nome_atributo, $formato = null) {
    // String $nome_atributo: nome do atributo a ser exibido (do tipo data)
    // String $formato: formato usado por strftime para formatar a data (null para obter o valor padrao de acordo com as caracteristicas do atributo)
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_atributo, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        $data = $this->get_atributo_data($nome_atributo, false);
        $time = mktime($data['hora'], $data['minuto'], $data['segundo'], $data['mes'], $data['dia'], $data['ano']);
        if ($formato === null) {
            switch ($this->get_definicao_atributo($nome_atributo)->campo_formulario) {
            case 'data':
                $formato = ATRIBUTO_FORMATO_DATA;
                break;
            case 'hora':
                $formato = ATRIBUTO_FORMATO_HORA;
                break;
            case 'data_hora':
            default:
                $formato = ATRIBUTO_FORMATO_DATA_HORA;
                break;
            }
        }
        return strftime($formato, $time);
    }


    //
    //     Imprime uma lista de entidades
    //
    public function imprimir_lista($condicoes, $modulo, $id_lista, $link, $opcoes = true, $campos = false, $ordem = false, $index = false, $itens_pagina = false, $campos_consultar = false, $nomes = false, $formatacao = false) {
    // condicao_sql $condicoes: condicoes da busca
    // String $modulo: nome do modulo
    // String $id_lista: ID da lista
    // String $link: link da pagina atual
    // Array[String] $opcoes: opcoes da lista ('exibir', 'alterar', 'excluir')
    // Array[String] $campos: $campos a serem consultados para exibicao
    // String || Array[String => Bool] $ordem: campo utilizado para ordenacao ou campos utilizados para ordenacao apontando para o tipo de ordem (true = crescente / false = decrescente)
    // String $index: campo utilizado para indexar
    // Int $itens_pagina: numero maximo de itens por pagina
    // Array[String] $campos_consultar: campos a serem consultados alem dos campos de exibicao
    // Array[String => String] $nomes: nomes dos elementos da lista no singular, plural e genero
    // String $formatacao: formatacao dos campos da lista, tal que $1 e' o primeiro, $2 e' o segundo, etc. Por exemplo '$1 (entre $2 e $3)'
    //
        global $USUARIO;

        // Criar paginacao
        $paginacao = new paginacao($modulo, $id_lista, $link);
        if (!$campos) {
            $campos = array($this->get_campo_nome());
        } else {
            $campos = array_values($campos);
        }
        if (!$ordem) {
            $ordem = array($campos[0] => true);
        }
        if (!$index) {
            $index = $this->get_chave();
        }
        if ($this->possui_atributo('visivel') || $this->possui_atributo_implicito('visivel')) {
            $campos_consultar[] = 'visivel';
        }
        if ($nomes) {
            $singular = isset($nomes['singular']) ? $nomes['singular'] : $this->get_entidade();
            $plural   = isset($nomes['plural'])   ? $nomes['plural']   : $this->get_entidade(true);
            $genero   = isset($nomes['genero'])   ? $nomes['genero']   : $this->get_genero();
            $paginacao->set_nomes($singular, $plural, $genero);
        }

        // Consultar entidades
        $itens = $paginacao->inicio_lista($this->get_classe(), $condicoes, $campos, $ordem, $index, $itens_pagina, $campos_consultar);
        if (!$itens || !$itens->size()) {
            $paginacao->fim_lista();
            return;
        }

        // Checar se possui atributo cancelado
        $possui_atributo_cancelado = $this->possui_atributo('cancelado') || $this->possui_atributo_implicito('cancelado');

        $dados_opcao_padrao = array(
            'principal' => false,
            'icone' => false,
            'texto' => false,
            'exibir_texto' => false,
            'ajax' => true,
            'carregando' => true,
            'foco' => true,
            'arquivo' => false,
            'modulo' => false,
            'descricao' => '',
            'id' => '',
            'class' => ''
        );

        foreach ($itens as $item) {
            $cod   = $item->get_valor_chave();
            $chave = $item->get_chave();

            // Gerar as opcoes
            $com_exibir = false;
            $vt_opcoes = array();
            foreach ($opcoes as $opcao) {
                $dados_opcao = $item->dados_opcao($opcao, $modulo);
                if (!$dados_opcao) { continue; }
                $dados_opcao = util::montar_objeto($dados_opcao_padrao, $dados_opcao);

                // Checar se a opcao e' de exibir
                if ($dados_opcao->principal) {
                    if ($com_exibir) {
                        trigger_error('O metodo "dados_opcao" da classe "'.$this->get_classe().'" possui mais de uma opcao principal', E_USER_WARNING);
                    }
                    $dados_exibir = $dados_opcao;
                    $com_exibir = true;
                    continue;
                }

                // Se possui icone
                if ($dados_opcao->icone) {
                    if ($dados_opcao->arquivo) {
                        $link_arquivo_modulo = link::icone_modulo($USUARIO, $dados_opcao->modulo, "{$dados_opcao->arquivo}?op={$opcao}&amp;{$chave}={$cod}", $dados_opcao->icone, $dados_opcao->descricao, $dados_opcao->texto, $dados_opcao->exibir_texto, $dados_opcao->carregando, $dados_opcao->foco, $dados_opcao->ajax);
                        if ($link_arquivo_modulo) {
                            $vt_opcoes[] = $link_arquivo_modulo;
                        }
                    } else {
                        $nome_icone = pathinfo($dados_opcao->icone, PATHINFO_FILENAME);
                        $vt_opcoes[] = icone::img($nome_icone, $dados_opcao->descricao, $dados_opcao->class, $dados_opcao->id);
                    }

                // Se nao possui icone
                } else {
                    if ($dados_opcao->arquivo) {
                        $link_arquivo_modulo = link::arquivo_modulo($USUARIO, "{$dados_opcao->arquivo}?op={$opcao}&amp;{$chave}={$cod}", $dados_opcao->modulo, $dados_opcao->descricao, $dados_opcao->id, $dados_opcao->class, true, $dados_opcao->carregando, $dados_opcao->foco, $dados_opcao->ajax);
                        if ($link_arquivo_modulo) {
                            $vt_opcoes[] = $link_arquivo_modulo;
                        }
                    } else {
                        $class_opcao = $dados_opcao->class ? ' class="'.$dados_opcao->class.'"' : '';
                        $id_opcao = $dados_opcao->id ? ' id="'.$dados_opcao->id.'"' : '';
                        $vt_opcoes[] = '<span'.$class_opcao.$id_opcao.'>'.$dados_opcao->descricao.'</span>';
                    }
                }
            }

            // Montar as opcoes
            $st_opcoes = '<div class="opcoes">'.
                         '<strong class="hide">Op&ccedil;&otilde;es:</strong> ';
            if (empty($vt_opcoes)) {
                $st_opcoes .= '<span class="nenhuma_opcao">(Nenhuma op&ccedil;&atilde;o)</span>';
            } else {
                $st_opcoes .= implode('<span class="hide">|</span>', $vt_opcoes);
            }
            $st_opcoes .= "</div>\n";
            unset($vt_opcoes);

            // Montar o nome da entidade
            $campos_nome = array();
            foreach ($campos as $campo) {
                $campos_nome[] = $item->exibir($campo);
            }
            if ($formatacao) {
                $tr = array();
                $i = 1;
                foreach ($campos_nome as $valor_campo_nome) {
                    $tr['$'.$i] = $valor_campo_nome;
                    $i += 1;
                }
                $nome = strtr($formatacao, $tr);
            } else {
                $nome = implode(' - ', $campos_nome);
            }

            if ($possui_atributo_cancelado) {
                $class_label = $item->__get('cancelado') ? 'inativo' : 'label';
            } else {
                $class_label = 'label';
            }

            // Mostrar um link para a pagina de exibir
            if ($com_exibir) {
                $link_exibir = "{$dados_exibir->arquivo}?{$chave}={$cod}";
                $modulo = $dados_exibir->modulo;
                $class = (isset($dados_exibir->class) && $dados_exibir->class) ? $dados_exibir->class : $class_label;
                $id    = (isset($dados_exibir->id) && $dados_exibir->id) ? $dados_exibir->id : '';

                echo "  <div class=\"linha\">\n";
                link::arquivo_modulo($USUARIO, $link_exibir, $modulo, $nome, $id, $class);
                echo "    {$st_opcoes}\n";
                echo "  </div>\n";

            // Apenas exibir o nome
            } else {
                echo "  <div class=\"linha\">\n";
                echo "    <strong class=\"{$class_label}\">{$nome}</strong>\n";
                echo "    {$st_opcoes}\n";
                echo "  </div>\n";
            }
        }
        $paginacao->fim_lista();
    }


    //
    //     Retorna um vetor com os dados da opcao (icone) que aparece na lista de entidades (pode ser sobrecarregado, desde que este metodo pai seja chamado no final)
    //     O metodo deve retornar um objeto stdClass com os possiveis atributos:
    //     Bool principal: indica se a opcao e' o link principal da lista ou deve ficar na lista de opcoes (opcional, padrao false)
    //                     Apenas uma opcao pode ser a principal dentro de um conjunto de opcoes escolhidas
    //     String icone: endereco do icone desejado (opcional, padrao "")
    //     String texto: texto a ser colocado apos o icone (opcional, padrao "")
    //     Bool exibir_texto: indica de deve exibir o texto apos o icone ou apenas defini-lo como descricao do icone (opcional, padrao false)
    //     Bool ajax: indica se o link deve usar ajax (opcional, padrao true)
    //     Bool carregando: indica se o link deve exibir o "carregando" na tela (opcional, padrao true)
    //     Bool foco: indica se o link deve definir o foco ao primeiro campo da proxima pagina (opcional, padrao true)
    //     String arquivo: nome do arquivo para onde a opcao aponta (opcional, padrao "")
    //     String modulo: nome do modulo para onde a opcao aponta (opcional, padrao "")
    //     String descricao: breve descricao da opcao (opcional, padrao "")
    //     String id: identificador unico da opcao (opcional, padrao "")
    //     String class: classe CSS da opcao (opcional, padrao "")
    //
    public function dados_opcao($opcao, $modulo) {
    // String $opcao: identificador da opcao
    // String $modulo: nome do modulo
    //
        $dados = new stdClass();

        switch ($opcao) {
        case 'alterar':
            $dados->icone     = icone::endereco('editar');
            $dados->arquivo   = 'alterar.php';
            $dados->modulo    = $modulo;
            $dados->descricao = 'Editar';
            return $dados;
        case 'excluir':
            $dados->icone     = icone::endereco('excluir');
            $dados->arquivo   = 'excluir.php';
            $dados->modulo    = $modulo;
            $dados->descricao = 'Excluir';
            return $dados;
        case 'exibir':
            $dados->principal = true;
            $dados->arquivo   = 'exibir.php';
            $dados->modulo    = $modulo;
            $dados->descricao = 'Exibir';
            return $dados;
        case 'inserir':
            $dados->icone     = icone::endereco('adicionar');
            $dados->arquivo   = 'inserir.php';
            $dados->modulo    = $modulo;
            $dados->descricao = 'Inserir';
            return $dados;
        }
        return false;
    }


    //
    //     Exibe ou retorna os erros internos (formatados)
    //
    final public function imprimir_erros($return = false) {
    // Bool $return: retorna ou imprime o(s) erro(s)
    //
        return mensagem::erro($this->erros, $return);
    }


    //
    //     Exibe ou retorna os avisos internos (formatados)
    //
    final public function imprimir_avisos($return = false) {
    // Bool $return: retorna ou imprime o(s) aviso(s)
    //
        return mensagem::aviso($this->avisos, $return);
    }


/// @ METODOS PARA EXPORTACAO


    //
    //     Exporta o objeto para formato CSV
    //
    final public function exportar_csv($campos = true, $separador = ',', $aspas = '"') {
    // Array[String] $campos: campos para serem exportados (true = todos)
    // Char $separador: caracter usado como separador de dados CSV
    // Char $aspas: caracter usado como delimitador de dados CSV
    //
        // Exportar todos os atributos
        if ($campos === true) {
            $atributos_simples    = array_keys($this->get_atributos());
            $atributos_implicitos = array_keys($this->get_implicitos());
            $campos = array_merge($atributos_simples, $atributos_implicitos);
            unset($atributos_simples, $atributos_implicitos);
        }

        // Exportar
        $csv = array();
        foreach ($campos as $campo) {
            $valor = $this->__get($campo);
            $campo_csv = str_replace($aspas, $aspas.$aspas, util::exibir_var($valor, UTIL_EXIBIR_TEXTO));
            if (is_int(strpos($campo_csv, $aspas)) ||
                is_int(strpos($campo_csv, "\n")) ||
                is_int(strpos($campo_csv, $separador))) {
                $campo_csv = $aspas.$campo_csv.$aspas;
            }
            $csv[] = $campo_csv;
        }
        return implode($separador, $csv)."\n";
    }


    //
    //     Exporta o objeto para formato XML
    //
    final public function exportar_xml($campos = true, $nova_linha = "\n", $identacao = false) {
    // Array[String] $campos: campos para serem exportados (true = todos)
    // String $nova_linha: caracter de quebra de linha
    // Int $identacao: quantidade de espacos usados para identacao
    //
        // Exportar todos os atributos
        if ($campos === true) {
            $atributos_simples    = array_keys($this->get_atributos());
            $atributos_implicitos = array_keys($this->get_implicitos());
            $campos = array_merge($atributos_simples, $atributos_implicitos);
        }

        // A quebra de linha precisa ser \n, \r, \t, espaco ou combinacoes entre estes
        if (!preg_match('/^[\n\r\t\040]*$/', $nova_linha)) {
            $nova_linha = "\n";
        }

        $identacao = (int)$identacao;
        $str_identacao = str_repeat(' ', $identacao);

        // Exportar
        $classe = $this->get_classe();
        $xml = "<{$classe}>{$nova_linha}";
        foreach ($campos as $campo) {
            $valor = $this->__get($campo);
            $valor = util::exibir_var($valor, UTIL_EXIBIR_TEXTO);
            $xml .= "{$str_identacao}<{$campo}><![CDATA[{$valor}]]></{$campo}>{$nova_linha}";
        }
        $xml .= "</{$classe}>{$nova_linha}";
        return $xml;
    }


/// @ METODOS DE CONTROLE DE RELACIONAMENTOS 1:1


    //
    //     Adiciona um relacionamento 1:1 (gera um atributo simples e um objeto correspondente)
    //
    final public function adicionar_rel_uu($classe, $nome_objeto = false, $nome_atributo = false, $descricao_objeto = false, $descricao_atributo = false, $unico = false, $forte = true, $opcoes = null) {
    // String $classe: nome da classe relacionada
    // String $nome_objeto: nome do objeto gerado (por padrao e' o mesmo nome que a classe relacionada)
    // String $nome_atributo: nome da chave estrangeira gerada (por padrao e' o mesmo nome da chave primaria importada)
    // String $descricao_objeto: descricao do objeto na nova classe (por padrao e' o nome original da entidade relacionada)
    // String $descricao_atributo: nome da chave estrangeira na nova classe (por padrao e' a mesma descricao da PK original)
    // Bool $unico: indica se o atributo e' unico na nova tabela (por padrao nao e' unico)
    // Bool $forte: indica se o relacionamento e' forte (true / 1:1) ou fraco (false / 1:{0,1})
    // Array[String => Mixed] $opcoes: caracteristicas a serem sobrecarregadas do atributo
    //
        // 1 - Filtrar os parametros
        // 2 - Criar atributo simples que representa a FK
        // 3 - Criar o objeto que representa o relacionamento 1:1

        // 1 - Filtrar os parametros
        $classe             = (string)$classe;
        $nome_objeto        = $nome_objeto        ? (string)$nome_objeto        : false;
        $nome_atributo      = $nome_atributo      ? (string)$nome_atributo      : false;
        $descricao_objeto   = $descricao_objeto   ? (string)$descricao_objeto   : false;
        $descricao_atributo = $descricao_atributo ? (string)$descricao_atributo : false;
        $unico              = (bool)$unico;
        $forte              = (bool)$forte;

        if (!DEVEL_BLOQUEADO && !$this->validar_integridade_rel_uu($classe, $nome_objeto, $nome_atributo, $unico, $forte)) {
            return false;
        }

//TODO bolar uma outra maneira de obter dados da PK da entidade sem precisar instanciar um objeto da classe (gasta muita memoria em alguns casos)
// ou entao guardar as definicoes das classes em cache

        // Criar um objeto da classe do relacionamento
        $objeto = objeto::get_objeto($classe);

        // Consultar as informacoes da chave primaria original (chave estrangeira na entidade corrente)
        $def_pk = $objeto->get_definicao_atributo($objeto->get_chave());

        // 2 - Criar atributo simples que representa a FK
        $def_fk = clone($def_pk);

        // Sobrescrever nome e descricao do atributo na nova classe
        if ($nome_atributo) {
            $def_fk->nome = $nome_atributo;
        }
        if ($descricao_atributo) {
            $def_fk->descricao = $descricao_atributo;
        }
        if ($forte) {
            $def_fk->chave      = 'FK';
            $def_fk->pode_vazio = false;
            $def_fk->unico      = $unico;
        } else {
            $def_fk->chave      = 'OFK';
            $def_fk->pode_vazio = true;
            $def_fk->unico      = false;
            $def_fk->minimo     = min(0, $def_fk->minimo);
        }
        if (is_array($opcoes)) {
            foreach ($opcoes as $chave_opcao => $valor_opcao) {
                $def_fk->$chave_opcao = $valor_opcao;
            }
        }
        $this->adicionar_atributo($def_fk, $classe);

        // 3 - Criar o objeto que representa o relacionamento 1:1
        $def_rel = new stdClass();
        $def_rel->nome      = $nome_objeto      ? $nome_objeto      : $classe;
        $def_rel->descricao = $descricao_objeto ? $descricao_objeto : $objeto->get_entidade();
        $def_rel->classe    = $classe;
        $def_rel->forte     = $forte;

        // Inserir a definicao do objeto
        $this->definicao->rel_uu[$def_fk->nome] = $def_rel;
    }


    //
    //     Verifica a integridade do relacionamento a ser inserido
    //
    private function validar_integridade_rel_uu($classe, $nome_objeto, $nome_atributo, $unico, $forte) {
    // String $classe: nome da classe relacionada
    // String $nome_objeto: nome do objeto relacionado
    // String $nome_atributo: nome do atributo relacionado
    // Bool $unico: indica se o atributo e' unico na nova tabela (por padrao nao e' unico)
    // Bool $forte: indica se o relacionamento e' forte (true / 1:1) ou fraco (false / 1:{0,1})
    //
        if ($forte) {

            // Associacao unaria nao pode ser forte (do objeto para ele mesmo)
            if ($classe == $this->get_classe()) {
                trigger_error('Associacao unaria nao pode ser forte (objeto "'.$nome_objeto.'" / classe "'.$classe.'")', E_USER_ERROR);
                return false;
            }

        } else {

            // Associacao fraca nao pode ser unica
            if ($unico) {
                trigger_error('O campo "'.$nome_atributo.'" da classe "'.$this->get_classe().'" nao pode ser unico pois o relacionamento eh fraco', E_USER_WARNING);
                return false;
            }
        }
        return true;
    }


    //
    //     Retorna o nome da entidade relacionada
    //
    final public function get_entidade_rel_uu($nome, $por_objeto = true) {
    // String $nome: nome do objto ou da chave estrangeira do relacionamento
    // Bool $por_objeto: indica se o primeiro parametro e' o nome do objeto ou da chave estrangeira do relacionamento
    //
        if (!$this->possui_rel_uu($nome, $por_objeto)) {
            $desc = $por_objeto ? $nome : 'chave '.$nome;
            trigger_error('A classe "'.$this->get_classe().'" nao possui o objeto ('.$desc.')', E_USER_WARNING);
            return false;
        }
        if ($por_objeto) {
            $chave = $this->get_nome_chave_rel_uu($nome);
        } else {
            $chave = $nome;
        }
        return $this->definicao->rel_uu[$chave]->descricao;
    }


    //
    //     Obtem dados de um relacionamento 1:1 pelo nome do objeto ou da chave estrangeira do relacionamento
    //
    final public function get_definicao_rel_uu($nome, $por_objeto = true) {
    // String $nome: nome do objeto ou da chave estrangeira do relacionamento
    // Bool $por_objeto: indica se o primeiro parametro e' o nome do objeto ou da chave estrangeira do relacionamento
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, false);
        }

        if ($por_objeto) {

            // Obter a chave estrangeira e consultar a definicao
            $chave = $this->get_nome_chave_rel_uu($nome);
            if ($chave) {
                return $this->definicao->rel_uu[$chave];
            } else {
                trigger_error('A classe "'.$this->get_classe().'" nao possui o objeto "'.$nome.'"', E_USER_WARNING);
            }
        } else {

            // Testar diretamente a chave estrangeira e consultar a definicao
            if (isset($this->definicao->rel_uu[$nome])) {
                return $this->definicao->rel_uu[$nome];
            } else {
                trigger_error('A classe "'.$this->get_classe().'" nao possui objeto com a chave "'.$nome.'"', E_USER_WARNING);
            }
        }
        return false;
    }


    //
    //     Retorna um vetor com os dados dos relacionamentos 1:1 (indexado pelas chaves estrangeiras dos relacionamentos)
    //
    final public function get_definicoes_rel_uu() {
        return $this->definicao->rel_uu;
    }


    //
    //     Obtem o objeto pelo nome do objeto ou da chave de estrangeira do relacionamento
    //
    final public function get_objeto_rel_uu($nome, $por_objeto = true) {
    // String $nome: nome do objeto ou da chave estrangeira do relacionamento
    // Bool $por_objeto: indica se o primeiro parametro e' o nome do objeto ou da chave estrangeira do relacionamento
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        if (!$this->possui_rel_uu($nome, $por_objeto)) {
            $desc = $por_objeto ? 'nome "'.$nome.'"' : 'chave "'.$nome.'"';
            trigger_error('A classe "'.$this->get_classe().'" nao possui o objeto '.$nome.' ('.$desc.')', E_USER_WARNING);
            return null;
        }

        $def = $this->get_definicao_rel_uu($nome, $por_objeto);

        if ($por_objeto) {
            $nome_chave = $this->get_nome_chave_rel_uu($nome);
            $nome_obj   = $nome;
        } else {
            $nome_chave = $nome;
            $nome_obj   = $this->get_nome_objeto_rel_uu($nome);
        }

        // Se a instancia existe
        $existe = $this->existe();
        if ($existe) {

            // Checar se a chave foi consultada
            if (!$this->get_flag_consulta($nome_chave) && !self::$flag_bd) {

                // Ao consultar a chave, o objeto e' definido
                $this->consultar_campos(array($nome_chave));
            }
        }

        // Se o relacionamento nao foi definido
        if (!isset($this->instancia->objetos[$nome_obj])) {
            $this->instancia->objetos[$nome_obj] = self::get_objeto($def->classe);
        }
        return $this->instancia->objetos[$nome_obj];
    }


    //
    //     Obtem todos os objetos da instancia (se nao foram consultados, retorna entidades vazias)
    //
    final public function get_objetos_rel_uu() {
        return $this->instancia->objetos;
    }


    //
    //     Checa se existe o relacionamento 1:1 pelo nome do objeto ou da chave estrangeira
    //
    final public function possui_rel_uu($nome, $por_objeto = true) {
    // String $nome: nome do objeto ou da chave estrangeira do relacionamento
    // Bool $por_objeto: indica se o primeiro parametro e' o nome do objeto ou da chave estrangeira do relacionamento
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, false);
        }

        // Obter a chave do relacionamento e checar se existe a definicao
        if (!$por_objeto) {
            $chave = $nome;
        } else {
            $chave = false;
            foreach ($this->definicao->rel_uu as $chave_rel => $def) {
                if ($def->nome == $nome) {
                    $chave = $chave_rel;
                    break;
                }
            }
            if (!$chave) {
                return false;
            }
        }
        return isset($this->definicao->rel_uu[$chave]);
    }


    //
    //     Obtem o nome do objeto pelo nome da chave do relacionamento
    //
    final public function get_nome_objeto_rel_uu($chave) {
    // String $chave: nome da chave estrangeira do relacionamento
    //
        // Se usou a notacao objeto:atributo
        if (strpos($chave, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, false);
        }

        foreach ($this->definicao->rel_uu as $chave_rel => $def) {
            if ($chave == $chave_rel) {
                return $def->nome;
            }
        }
        trigger_error('A classe "'.$this->get_classe().'" nao possui objeto com a chave "'.$chave.'"', E_USER_WARNING);
        return false;
    }


    //
    //     Obtem o NOME da chave a partir do nome do objeto
    //
    final public function get_nome_chave_rel_uu($nome_obj) {
    // String $nome_obj: nome do objeto relacionado
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_obj, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, false);
        }

        foreach ($this->definicao->rel_uu as $chave_rel => $def) {
            if ($nome_obj == $def->nome) {
                return $chave_rel;
            }
        }
        trigger_error('A classe "'.$this->get_classe().'" nao possui objeto "'.$nome_obj.'"', E_USER_WARNING);
        return false;
    }


    //
    //     Atualiza um objeto da instancia
    //
    final protected function set_chave_rel_uu($chave, $valor) {
    // String $chave: nome da chave estrangeira atualizada
    // Mixed $valor: novo valor da chave
    //
        // Se usou a notacao objeto:atributo
        if (strpos($chave, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        if (!$this->possui_rel_uu($chave, false)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui objeto com a chave "'.$chave.'"', E_USER_WARNING);
            return false;
        }

        // Obter nome do objeto a ser atualizado
        $def      = $this->get_definicao_rel_uu($chave, false);
        $nome_obj = $def->nome;

        // Obter uma referencia para o objeto
        if (!isset($this->instancia->objetos[$nome_obj])) {
            $this->instancia->objetos[$nome_obj] = self::get_objeto($def->classe);
        }
        $obj = &$this->instancia->objetos[$nome_obj];

        // Se a chave nao esta' vindo do BD: checar se pode ou nao atribuir
        if (!self::$flag_bd) {

            // Se o relacionamento e' fraco: atribuir sem questionar
            if (!$def->forte) {

                // Se passou uma chave: checar se ela existe
                if ($valor) {
                    $set_pk = $obj->consultar('', $valor);

                // Se nao passou uma chave: ok, pois e' fraco
                } else {
                    $set_pk = true;
                }

                // Tentar setar a chave, caso tenha conseguido setar a PK
                $set_chave = $set_pk ? $this->set_valor($chave, $valor) : false;

                return $set_chave && $set_pk;

            // Se o relacionamento e' forte: so' aceitar valores validos
            } else {

                // Se nao foi informada uma chave valida
                if (!$valor) {
                    $this->erros[] = 'N&atilde;o foi informado(a) um(a) '.$def->descricao;
                    return false;
                }

                // Se nao encontrou o objeto com a chave informada
                if (!$obj->consultar('', $valor)) {
                    $this->erros[] = 'N&atilde;o pode escolher este(a) '.$def->descricao;
                    return false;
                }
                return $this->set_valor($chave, $valor) &&
                       $obj->set_valor_chave($valor);
            }

        }

        // Se a chave esta' vindo do BD: assumir que e' valida
        if ($def->forte) {
            return $this->set_valor($chave, $valor) &&
                   $obj->set_valor_chave($valor);
        } else {
            if ($valor) {
                return $this->set_valor($chave, $valor) &&
                       $obj->set_valor_chave($valor);
            } else {
                return $this->set_valor($chave, 0);
            }
        }
    }


    //
    //     Imprime o nome de um objeto filho (relacionamento 1:1)
    //
    public function imprimir_objeto_rel_uu($nome_atributo, $return = false, $imprimir_descricao = true, $descricao_alternativa = false) {
    // String $nome_atributo: nome do atributo referente ao relacionamento (nome do objeto)
    // Bool $return: indica se o resultado sera' retornado ou impresso
    // Bool $imprimir_descricao: indica se deve ser incluida a descricao do relacionamento
    // String $descricao_alternativa: valor da descricao alternativa a ser utilizada
    //
        $a = '';
        $def = $this->get_definicao_rel_uu($nome_atributo, true);
        $obj = $this->get_objeto_rel_uu($nome_atributo);
        if ($imprimir_descricao) {
            if ($descricao_alternativa) {
                $descricao = $descricao_alternativa;
            } else {
                $descricao = $this->get_entidade_rel_uu($nome_atributo, true);
                $descricao = texto::codificar($descricao);
            }
        }

        // Obter valor
        $chave = $this->get_nome_chave_rel_uu($nome_atributo);
        $metodo = 'get_vetor_'.$chave;
        if (method_exists($this, $metodo)) {
            $vetor = call_user_func(array($this, $metodo));
            if (isset($vetor[$this->__get($chave)])) {
                $valor = $vetor[$this->__get($chave)];
            } else {
                $valor = '(indefinido)';
            }
        } else {
            $nome = $obj->get_nome();
            if ($def->forte) {
                $valor = is_null($nome) ? '(indefinido)' : $nome;
            } else {
                $valor = (!$obj->get_valor_chave() || is_null($nome)) ? '(indefinido)' : $nome;
            }
        }
        if ($imprimir_descricao) {
            $a = "<p><strong>{$descricao}:</strong> {$valor}</p>";
        } else {
            $a = (string)$valor;
        }

        // Retornar ou imprimir
        if ($return) {
            return $a;
        }
        echo $a;
    }

/// @ METODOS DE CONTROLE DE RELACIONAMENTOS 1:N


    //
    //     Adiciona um relacionamento do tipo 1:N
    //
    final public function adicionar_rel_un($classe, $nome_vetor, $index = false, $impressao = false, $ordem = false, $chave_fk = false, $descricao = false) {
    // String $classe: nome da classe que o vetor vai armazenar
    // String $nome_vetor: nome do vetor gerado no objeto para referenciar o relacionamento
    // String $index: nome do campo usado para indexacao do vetor (atributo simples ou implicito)
    // String $impressao: nome do campo usado para impressao (atributo simples, implicito ou objeto)
    // String || Array[String => Bool] $ordem: campo usado para ordenar o vetor ou vetor de campos apontando para o tipo de ordenacao (true = crescente / false = decrescente)
    // String $chave_fk: nome da chave de ligacao entre as entidades
    // String $descricao: descricao no plural do vetor (ou false para obter o nome da entidade relacionada no plural)
    //
        // Filtrar os parametros
        if ($chave_fk === false) {
            $chave_fk = $this->get_chave();
        }
        $nome_vetor = (string)$nome_vetor;
        $index = $index ? (string)$index : false;
        $impressao = $impressao ? (string)$impressao : false;
        $chave_fk = (string)$chave_fk;
        if (!$ordem) {
            if ($impressao) {
                $ordem = array($impressao => true, $chave_fk => true);
            } else {
                $ordem = array($chave_fk => true);
            }
        }

        // Validar integridade do relacionamento
        if (!DEVEL_BLOQUEADO && !$this->validar_integridade_rel_un($nome_vetor)) {
            return false;
        }

        // Guardar dados do relacionamento
        $obj = new stdClass();
        $obj->chave_fk  = $chave_fk;   // Nome da chave estrangeira
        $obj->classe    = $classe;     // Nome da classe
        $obj->index     = $index;      // Nome do campo usado na indexacao
        $obj->impressao = $impressao;  // Nome do campo usado para impressao
        $obj->ordem     = $ordem;      // Campos usados para ordenar o vetor
        $obj->descricao = $descricao;  // Descricao do vetor

        $this->definicao->rel_un[$nome_vetor] = $obj;
    }


    //
    //     Verifica a integridade do relacionamento 1:N
    //
    private function validar_integridade_rel_un($nome_vetor) {
    // String $nome_vetor: nome do vetor
    //
        // O nome nao pode ser vazio
        if (empty($nome_vetor)) {
            trigger_error('O nome do vetor nao pode ser vazio na classe "'.$this->get_classe().'"', E_USER_ERROR);
            return false;
        }

        // Se ja existe um vetor com o nome especificado, abortar
        if ($this->possui_rel_un($nome_vetor)) {
            trigger_error('O vetor "'.$nome_vetor.'" ja foi especificado na classe "'.$this->get_classe().'"', E_USER_ERROR);
            return false;
        }

        return true;
    }


    //
    //     Obtem dados de um relacionamento com um vetor
    //
    final public function get_definicao_rel_un($nome_vetor) {
    // String $nome_vetor: nome do vetor relacionado
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, false);
        }

        if ($this->possui_rel_un($nome_vetor)) {
            return $this->definicao->rel_un[$nome_vetor];
        }
        trigger_error('Nao existe o vetor "'.$nome_vetor.'" na classe "'.$this->get_classe().'"', E_USER_WARNING);
        return false;
    }


    //
    //     Obtem os dados dos relacionamento com vetores
    //
    final public function get_definicoes_rel_un() {
        return $this->definicao->rel_un;
    }


    //
    //     Obtem um vetor pelo seu nome
    //
    final public function get_vetor_rel_un($nome_vetor) {
    // String $nome_vetor: nome do vetor
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        if ($this->possui_rel_un($nome_vetor)) {

            // Se ainda nao consultou o vetor
            if (!isset($this->instancia->vetores[$nome_vetor])) {
                $this->consultar_vetor_rel_un($nome_vetor);
            }
            return $this->instancia->vetores[$nome_vetor];
        }
        trigger_error('Nao existe o vetor "'.$nome_vetor.'" na classe "'.$this->get_classe().'"', E_USER_WARNING);
        return false;
    }


    //
    //     Retorna um vetor com os vetores consultados da instancia (indexados pelo nome no vetor)
    //
    final public function get_vetores_rel_un() {
        return $this->instancia->vetores;
    }


    //
    //     Checa se a classe possui o vetor
    //
    final public function possui_rel_un($nome_vetor) {
    // String $nome_vetor: nome do vetor
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, false);
        }

        return isset($this->definicao->rel_un[$nome_vetor]);
    }


    //
    //     Retorna se existem elementos no vetor
    //
    final public function possui_registros_rel_un($nome_vetor, $quantidade = 1, $forcar = false) {
    // String $nome_vetor: nome do vetor
    // Int $quantidade: quantidade de registros a ser verificada (retorna true se existirem pelo menos $quantidade registros)
    // Bool $forcar: forca que a consulta seja feita
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Checar se ja' consultou e nao pediu para forcar a consulta
        if (isset($this->instancia->vetores[$nome_vetor]) && !$forcar) {
            return count($this->instancia->vetores[$nome_vetor]) >= $quantidade;
        }

        // Consultar se existem $quantidade registros no vetor
        $def = $this->get_definicao_rel_un($nome_vetor);
        $condicoes = condicao_sql::montar($def->chave_fk, '=', $this->get_valor_chave());
        return self::get_objeto($def->classe)->possui_registros($condicoes, $quantidade);
    }


    //
    //     Retorna a quantidade de registro no vetor
    //
    final public function quantidade_registros_rel_un($nome_vetor, $forcar = false) {
    // String $nome_vetor: nome do vetor
    // Bool $forcar: forca que a consulta seja feita
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Checar se ja' consultou e nao pediu para forcar a consulta
        if (isset($this->instancia->vetores[$nome_vetor]) && !$forcar) {
            return count($this->instancia->vetores[$nome_vetor]);
        }

        // Consultar a quantidade de registros no vetor
        $def = $this->get_definicao_rel_un($nome_vetor);
        $condicoes = condicao_sql::montar($def->chave_fk, '=', $this->get_valor_chave());
        return self::get_objeto($def->classe)->quantidade_registros($condicoes);
    }


    //
    //     Consulta um vetor no BD
    //
    final public function consultar_vetor_rel_un($nome_vetor, $campos = false, $ordem = false, $forcar = false) {
    // String $nome_vetor: nome do vetor a ser consultado
    // Array[String] $campos: campos a serem consultados (true = todos | false = PK e Indice)
    // String $ordem: nome do campo usado para ordenacao
    // Bool $forcar: forca que a consulta seja feita
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Checar se ja' consultou e nao pediu para forcar a consulta
        if (isset($this->instancia->vetores[$nome_vetor]) && !$forcar) {
            return true;
        }

        // Precisa da chave primaria
        if (!$this->existe()) {
            trigger_error('Para consultar um vetor precisa existir a entidade', E_USER_WARNING);
            return false;
        }

        // Checar se existe o vetor
        if (!$this->possui_rel_un($nome_vetor)) {
            trigger_error('O vetor "'.$nome_vetor.'" nao existe na classe "'.$this->get_classe().'"', E_USER_WARNING);
            return false;
        }

        // Consultar dados do relacionamento
        $def = $this->get_definicao_rel_un($nome_vetor);
        $chave_fk = $def->chave_fk; // Nome da chave estrangeira
        $classe   = $def->classe;   // Nome da classe
        $index    = $def->index;    // Nome do campo de indexacao
        if (!$ordem) {
            $ordem = $def->ordem;
        }
        unset($def);

        // Montar vetor com os campos a serem consultados
        // Chave Primaria e, opcionalmente, o campo de indexacao
        if ($campos === false) {
            $campos = array($chave_fk);
            if ($index) {
                $campos[] = $index;
            }

        // Todos campos
        } elseif ($campos === true) {
            // Nada

        // Campos desejados
        } else {

            // Acrescentar chave e campo de indexacao
            if (!in_array($chave_fk, $campos)) {
                $campos[] = $chave_fk;
            }
            if ($index && !in_array($index, $campos)) {
                $campos[] = $index;
            }
        }

        // Criar um objeto da classe relacionada
        $objeto_rel = self::get_objeto($classe);

        if (!DEVEL_BLOQUEADO) {
            if (!$objeto_rel->possui_atributo($chave_fk)) {
                trigger_error('A chave FK do relacionamento 1:N "'.$nome_vetor.'" da classe "'.$this->get_classe().'" nao existe na classe "'.$classe.'"', E_USER_ERROR);
            }
        }

        // Consultar vetor baseando-se no valor da chave estrangeira
        $condicoes = condicao_sql::montar($chave_fk, '=', $this->get_valor_chave());

        // Consultar elementos relacionados
        $vet = $objeto_rel->consultar_varios($condicoes, $campos, $ordem);

        // Se conseguiu consultar
        if (is_array($vet)) {
            $this->guardar_vetor_rel_un($nome_vetor, $vet, $index);
            return true;
        }
        return false;
    }


    //
    //     Insere um elemento no vetor (relacao 1:N)
    //
    final public function inserir_elemento_rel_un($nome_vetor, $dados) {
    // String $nome_vetor: nome do vetor
    // Object $dados: dados a serem inseridos na relacao
    //
        // Clonar os dados para evitar sobrescrita
        $dados = clone($dados);

        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Precisa da chave primaria
        if (!$this->existe()) {
            trigger_error('Nao pode inserir elemento no vetor (entidade nao consultada)', E_USER_WARNING);
            switch ($this->get_genero()) {
            case 'M':
                $this->erros[] = 'N&atilde;o foi poss&iacute;vel inserir dados no '.$this->get_entidade().' (ele ainda n&atilde;o foi cadastrado no sistema)';
                break;
            case 'F':
                $this->erros[] = 'N&atilde;o foi poss&iacute;vel inserir dados na '.$this->get_entidade().' (ela ainda n&atilde;o foi cadastrada no sistema)';
                break;
            case 'I':
                $this->erros[] = 'N&atilde;o foi poss&iacute;vel inserir dados em '.$this->get_entidade().' (ainda n&atilde;o foi cadastrado no sistema)';
                break;
            }
            return false;
        }

        // Checar se existe o relacionamento
        if (!$this->possui_rel_un($nome_vetor)) {
            trigger_error('Nao existe o vetor "'.$nome_vetor.'" na entidade "'.$this->get_classe().'"', E_USER_ERROR);
            return false;
        }

        // Recuperar dados do relacionamento
        $def = $this->get_definicao_rel_un($nome_vetor);
        $classe   = $def->classe;    // Nome da classe
        $chave_fk = $def->chave_fk;  // Nome da chave estrangeira
        $index    = $def->index;     // Nome do campo usado para indexacao
        unset($def);

        // Definir a chave estrangeira
        if (!isset($dados->$chave_fk)) {
            $dados->$chave_fk = $this->get_valor_chave();
        } elseif ($dados->$chave_fk != $this->get_valor_chave()) {
            trigger_error('Chave FK informada nao pertence ao objeto corrente', E_USER_WARNING);
            return false;
        }

        // Criar objeto
        $obj = self::get_objeto($classe);
        $obj->id_form = $this->id_form;

        // Se o objeto nao possui chave para usar na indexacao
        if ($index && !$obj->possui_atributo($index)) {
            trigger_error('Nao existe o atributo '.$index.' na classe '.$obj->get_classe(), E_USER_WARNING);
            return false;
        }

        // Se consegiu setar os valores e salvar, adicionar no vetor correspondente
        if ($obj->set_valores($dados, false, true) &&
            $obj->pre_salvar($salvar_campos) &&
            $obj->salvar() &&
            $obj->pos_salvar()) {

            if ($index) {
                $this->instancia->vetores[$nome_vetor][$obj->{$index}] = $obj;
            } else {
                $this->instancia->vetores[$nome_vetor][] = $obj;
            }

            // Se o pai esta' em cache: atualiza'-lo
            // (a instancia e' atualizada na cache no metodo salvar)
            if (self::em_cache($this->get_classe(), $this->get_valor_chave())) {
                self::set_cache($this->get_classe(), $this->get_valor_chave());
            }
            return $obj;
        }

        // Se ocorreu algum erro
        switch ($this->get_genero()) {
        case 'M':
            $this->erros[] = 'Erro ao inserir '.$obj->get_entidade().' no '.$this->get_entidade();
            break;
        case 'F':
            $this->erros[] = 'Erro ao inserir '.$obj->get_entidade().' na '.$this->get_entidade();
            break;
        case 'I':
            $this->erros[] = 'Erro ao inserir '.$obj->get_entidade().' em '.$this->get_entidade();
            break;
        }
        $this->erros[] = $obj->get_erros();

        return false;
    }


    //
    //     Remove um elemento no vetor (relacao 1:N)
    //
    final public function remover_elemento_rel_un($nome_vetor, $dados) {
    // String $nome_vetor: nome do vetor
    // Mixed || Object $dados: valor da chave de indexacao do relacionamento (mixed) ou restricoes dos dados a serem removidos (object)
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Checar se existe o relacionamento
        if (!$this->possui_rel_un($nome_vetor)) {
            trigger_error('A classe "'.$this->get_classe().'" nao possui o vetor "'.$nome_vetor.'"', E_USER_ERROR);
            return false;
        }

        // Se a entidade nem existe
        if (!$this->existe()) {
            trigger_error('A entidade precisa existir para remover um elemento do vetor', E_USER_WARNING);
            switch ($this->get_genero()) {
            case 'M':
                $this->erros[] = 'O '.$this->get_entidade().' n&atilde;o possui dados para serem removidos';
                break;
            case 'F':
                $this->erros[] = 'A '.$this->get_entidade().' n&atilde;o possui dados para serem removidos';
                break;
            case 'I':
                $this->erros[] = $this->get_entidade().' n&atilde;o possui dados para serem removidos';
                break;
            }
            return false;
        }

        // Recuperar dados do relacionamento
        $def = $this->get_definicao_rel_un($nome_vetor);
        $index = $def->index;
        unset($def);

        // Consultar o vetor caso ainda nao tenha
        if (!$this->consultar_vetor_rel_un($nome_vetor)) {
            return false;
        }

        // Se foi solicitada a exclusao pelos dados passados
        if (is_object($dados)) {

            // Retorno da funcao
            $r = true;

            // Procurar pelo elemento a ser excluido
            foreach ($this->get_vetor_rel_un($nome_vetor) as $indice => $elemento) {

                // Checar os campos do elemento
                $achou = true;
                foreach ($dados as $campo => $valor) {
                    if ((!isset($elemento->$campo)) || ($elemento->$campo != $valor)) {
                        $achou = false;
                        break;
                    }
                }

                // Se os dados casam com o padrao
                if ($achou) {
                    $item = $this->instancia->vetores[$nome_vetor][$indice];
                    $e = $item->excluir();
                    if ($e) {
                        switch ($item->get_genero()) {
                        case 'M':
                            $this->avisos[] = $item->get_entidade().' exclu&iacute;do com sucesso';
                            break;
                        case 'F':
                            $this->avisos[] = $item->get_entidade().' exclu&iacute;da com sucesso';
                            break;
                        case 'I':
                            $this->avisos[] = $item->get_entidade().' exclu&iacute;do com sucesso';
                            break;
                        }
                        unset($this->instancia->vetores[$nome_vetor][$indice]);

                        // Se o pai esta' em cache: atualiza'-lo
                        // (a instancia e' atualizada na cache no metodo excluir)
                        if (self::em_cache($this->get_classe(), $this->get_valor_chave())) {
                            self::set_cache($this->get_classe(), $this->get_valor_chave());
                        }

                    } else {
                        switch ($item->get_genero()) {
                        case 'M':
                            $this->erros[] = 'Erro ao remover o '.$item->get_entidade();
                            break;
                        case 'F':
                            $this->erros[] = 'Erro ao remover a '.$item->get_entidade();
                            break;
                        case 'I':
                            $this->erros[] = 'Erro ao remover '.$item->get_entidade();
                            break;
                        }
                        $this->erros[] = $item->get_erros();
                        $r = false;
                    }
                }
            }

            // Se conseguiu remover
            return $r;

        // Se foi solicitada a exclusao pelo valor de indexacao
        } else {
            $r = true;

            // Se existe o elemento a ser excluido
            $indice = $dados;
            if (isset($this->instancia->vetores[$nome_vetor][$indice])) {
                $item = $this->instancia->vetores[$nome_vetor][$indice];
                $e = $item->excluir();
                if ($e) {
                    switch ($item->get_genero()) {
                    case 'M':
                        $this->avisos[] = $item->get_entidade().' exclu&iacute;do com sucesso';
                        break;
                    case 'F':
                        $this->avisos[] = $item->get_entidade().' exclu&iacute;da com sucesso';
                        break;
                    case 'I':
                        $this->avisos[] = $item->get_entidade().' exclu&iacute;do com sucesso';
                        break;
                    }
                    unset($this->instancia->vetores[$nome_vetor][$indice]);

                    // Se o pai esta' em cache: atualiza'-lo
                    // (a instancia e' atualizada na cache no metodo excluir)
                    if (self::em_cache($this->get_classe(), $this->get_valor_chave())) {
                        self::set_cache($this->get_classe(), $this->get_valor_chave());
                    }

                } else {
                    switch ($item->get_genero()) {
                    case 'M':
                        $this->erros[] = 'Erro ao remover o '.$item->get_entidade();
                        break;
                    case 'F':
                        $this->erros[] = 'Erro ao remover a '.$item->get_entidade();
                        break;
                    case 'I':
                        $this->erros[] = 'Erro ao remover '.$item->get_entidade();
                        break;
                    }
                    $this->erros[] = $item->get_erros();
                    $r = false;
                }
            } else {
                $this->erros[] = 'O elemento a ser exclu&iacute;do n&atilde;o existe';
                $r = false;
            }
            return $r;
        }
    }


    //
    //     Checa se um elemento esta no vetor (relacao 1:N)
    //
    final public function possui_elemento_rel_un($elemento, $nome_vetor) {
    // objeto $elemento: objeto a ser conferido (derivado da classe objeto)
    // String $nome_vetor: nome do vetor
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        if (!$this->possui_rel_un($nome_vetor)) {
            trigger_error('Nao existe o vetor "'.$nome_vetor.'" na classe "'.$this->get_classe().'"', E_USER_WARNING);
            return false;
        }
        $def = $this->get_definicao_rel_un($nome_vetor);
        if (!($elemento instanceof $def->classe)) {
            trigger_error('O elemento comparado deve ser da classe "'.$def->classe.'"', E_USER_WARNING);
            return false;
        }

        $chave = $elemento->get_valor_chave();
        foreach ($this->get_vetor_rel_un($nome_vetor) as $elemento_vetor) {
            if ($elemento_vetor->get_valor_chave() == $chave) {
                return true;
            }
        }
        return false;
    }


    //
    //     Checa se existem elementos no vetor (relacao 1:N)
    //
    final public function possui_elementos_rel_un($nome_vetor, $quantidade = 1) {
    // String $nome_vetor: nome do vetor
    // Int $quantidade: quantidade de elementos a ser verificada (retorna true se existirem pelo menos $quantidade elementos)
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        if (!$this->possui_rel_un($nome_vetor)) {
            trigger_error('Nao existe o vetor "'.$nome_vetor.'" na classe "'.$this->get_classe().'"', E_USER_WARNING);
            return false;
        }
        $def = $this->get_definicao_rel_un($nome_vetor);
        $condicao = condicao_sql::montar($def->chave_fk, '=', $this->get_valor_chave());
        return objeto::get_objeto($def->classe)->possui_registros($condicao, $quantidade);
    }


    //
    //     Imprime um vetor da entidade (relacionamento 1:N)
    //
    public function imprimir_vetor_rel_un($nome_vetor, $return = false, $imprimir_descricao = true) {
    // String $nome_vetor: nome do vetor
    // Bool $return: retornar ou imprimir o vetor
    // Bool $imprimir_descricao: imprime a descricao para cada item do vetor
    //
        // Se usou a notacao objeto:atributo
        if (strpos($nome_vetor, ':') !== false) {
            $args = func_get_args();
            return $this->recursao_atributo(__FUNCTION__, $args, 0, true);
        }

        // Checar se o vetor existe
        if (!$this->possui_rel_un($nome_vetor)) {
            trigger_error('Nao existe o vetor "'.$nome_vetor.'" na classe "'.$this->get_classe().'"', E_USER_WARNING);
            return false;
        }

        // Recuperar dados do relacionamento
        $def = $this->get_definicao_rel_un($nome_vetor);
        $classe    = $def->classe;
        $impressao = $def->impressao;
        unset($def);

        // Obter campos a serem consultados
        $campos = array($impressao);

        // Checar se o vetor foi consultado
        if (!$this->consultar_vetor_rel_un($nome_vetor, $campos)) {
            return false;
        }

        $v = '';

        $e = self::get_objeto($classe);
        if (!$impressao) {
            $impressao = $e->get_campo_nome();
        }

        // Obter a descricao do vetor
        if ($def->descricao) {
            $descricao_vetor = $def->descricao;
        } else {
            $descricao_vetor = $e->get_entidade(1);
        }
        $genero = $e->get_genero();
        unset($e);

        $visiveis = array();
        foreach ($this->instancia->vetores[$nome_vetor] as $elemento) {
            if (!isset($elemento->visivel) || $elemento->visivel) {
                $valor = $elemento->imprimir_atributo($impressao, true, $imprimir_descricao);
                $visiveis[] = "<li>{$valor}</li>\n";
            }
        }

        $v = "<p><strong>{$descricao_vetor}:</strong></p>\n";
        if (!empty($visiveis)) {
            $v .= "<ul class=\"relacionamento\">\n";
            foreach ($visiveis as $elemento) {
                $v .= $elemento;
            }
            $v .= "</ul>\n";
        } else {
            switch ($genero) {
            case 'M':
                $v .= "<p>Nenhum</p>\n";
                break;
            case 'F':
                $v .= "<p>Nenhuma</p>\n";
                break;
            case 'I':
                $v .= "<p>Nenhum(a)</p>\n";
                break;
            }
        }

        if ($return) {
            return $v;
        }
        echo $v;
    }


    //
    //     Armazena um vetor utilizando um indice especifico
    //
    private function guardar_vetor_rel_un($nome_vetor, &$vetor, $index = false) {
    // String $nome_vetor: nome do vetor
    // Array[Object] $vetor: vetor de elementos
    // String $index: campo usado para indexacao
    //
        // Limpar o vetor
        $this->instancia->vetores[$nome_vetor] = array();

        // Se nao tem elemento no vetor
        if (empty($vetor)) {
            return true;
        }

        // Guardar o vetor indexado por algum campo
        if ($index !== false) {
            reset($vetor);
            $obj = current($vetor);
            if (!$obj->possui_atributo($index) && !$obj->possui_atributo_implicito($index)) {
                trigger_error('Nao existe o atributo "'.$index.'" para indexar o vetor "'.$nome_vetor.'" da classe "'.$this->get_classe().'"', E_USER_ERROR);
                return false;
            }
            foreach ($vetor as $elemento) {
                $indice = $elemento->__get($index);
                $this->instancia->vetores[$nome_vetor][$indice] = $elemento;
            }

        // Guardar o vetor indexado pela chave primaria
        } else {
            foreach ($vetor as $elemento) {
                $indice = $elemento->get_valor_chave();
                $this->instancia->vetores[$nome_vetor][$indice] = $elemento;
            }
        }
        return true;
    }


/// @ ITERACAO


    //
    //     Reinicia o iterador
    //
    final public function rewind() {
        reset($this->instancia->valores);
    }


    //
    //     Obtem o valor corrente do iterador
    //
    final public function current() {
        return current($this->instancia->valores);
    }


    //
    //     Obtem a chave corrente do iterador
    //
    final public function key() {
        return key($this->instancia->valores);
    }


    //
    //     Avanca uma posicao do iterador e retorna o proximo elemento
    //
    final public function next() {
        return next($this->instancia->valores);
    }


    //
    //     Indica se o iterador chegou ao final
    //
    final public function valid() {
        return $this->current() !== false;
    }


/// @ CACHE EM SESSAO


    //
    //     Serializa o proprio objeto
    //
    final public function serialize() {
        $classe = $this->get_classe();
        $valor_chave = $this->get_valor_chave();
        return self::serialize_instancia($classe, $valor_chave, self::$instancias[$classe][$valor_chave]);
    }


    //
    //     Desserializa o objeto
    //
    final public function unserialize($serializado) {
    // String $serializado: valor serializado
    //
        $i = unserialize($serializado);
        $classe = $i->classe;
        $valor_chave = $i->pk;

        $this->definir_entidade();

        // Se a instancia ainda nao foi consultada
        if (!isset(self::$instancias[$classe][$valor_chave])) {
            self::$instancias[$classe][$valor_chave] = false;
            $instancia = self::unserialize_instancia($serializado);
            self::$instancias[$classe][$valor_chave] = $instancia;
            self::$instancias[$classe][$valor_chave]->orfa = false;
            self::$instancias[$classe][$valor_chave]->referencias = 0;
        }
        $this->definir_instancia($classe, $valor_chave);
    }


    //
    //     Checa se uma instancia ou uma classe esta' em cache de sessao
    //
    final static public function em_cache($classe, $valor_chave = false) {
    // String $classe: nome da classe
    // Mixed $valor_chave: valor da chave primaria
    //
        if (!isset($_SESSION)) {
            return false;
        }
        if ($valor_chave) {
            return isset($_SESSION[OBJETO_CACHE_INSTANCIAS][$classe][$valor_chave]);
        } else {
            return isset($_SESSION[OBJETO_CACHE_INSTANCIAS][$classe]);
        }
    }


    //
    //     Guarda uma ou todas instancias de uma classe em cache de sessao
    //
    final static public function set_cache($classe, $valor_chave = false) {
    // String $classe: nome da classe
    // Mixed $valor_chave: valor da chave primaria
    //
        if (!isset($_SESSION)) {
            return false;
        }

        // Guardar uma instancia na cache
        if ($valor_chave) {
            if (!isset(self::$instancias[$classe][$valor_chave])) {
                trigger_error('Tentativa de armazenamento em cache de uma instancia nao consultada', E_USER_WARNING);
                return false;
            }
            $_SESSION[OBJETO_CACHE_INSTANCIAS][$classe][$valor_chave] = false;
            $_SESSION[OBJETO_CACHE_INSTANCIAS][$classe][$valor_chave] = self::serialize_instancia($classe, $valor_chave, self::$instancias[$classe][$valor_chave]);

        // Guardar todas instancias de uma classe na cache
        } else {
            foreach (self::$instancias[$classe] as $chave => $instancia) {
                $_SESSION[OBJETO_CACHE_INSTANCIAS][$classe][$chave] = false;
                $_SESSION[OBJETO_CACHE_INSTANCIAS][$classe][$chave] = self::serialize_instancia($classe, $chave, self::$instancias[$classe][$chave]);
            }
        }

        // Guardar definicao da classe
        if (!isset($_SESSION[OBJETO_CACHE_DEFINICOES][$classe])) {
            $_SESSION[OBJETO_CACHE_DEFINICOES][$classe] = serialize(self::$definicoes[$classe]);
        }
        return true;
    }


    //
    //     Restaura e retorna uma ou todas instancias de uma classe da cache de sessao
    //
    final static public function get_cache($classe, $valor_chave = false) {
    // String $classe: nome da classe
    // Mixed $valor_chave: valor da chave primaria
    //
        if (!self::em_cache($classe, $valor_chave)) {
            return false;
        }

        // Obter definicao, caso ainda nao exista
        if (!isset(self::$definicoes[$classe])) {
            if (isset($_SESSION[OBJETO_CACHE_DEFINICOES][$classe])) {
                self::$definicoes[$classe] = unserialize($_SESSION[OBJETO_CACHE_DEFINICOES][$classe]);
            } else {
                self::get_objeto($classe);
            }
        }

        // Obter uma instancia especifica
        if ($valor_chave) {

            // Se a instancia ainda nao foi consultada
            if (!isset(self::$instancias[$classe][$valor_chave])) {
                self::$instancias[$classe][$valor_chave] = false;
                $instancia = self::unserialize_instancia($_SESSION[OBJETO_CACHE_INSTANCIAS][$classe][$valor_chave]);
                self::$instancias[$classe][$valor_chave] = $instancia;
            }
            $obj = self::get_objeto($classe);
            $obj->definir_instancia($classe, $valor_chave);
            return $obj;

        // Obter todas instancias de uma classe
        } else {
            foreach ($_SESSION[OBJETO_CACHE_INSTANCIAS][$classe] as $chave => $instancia) {

                // Se a instancia ainda nao foi consultada
                if (!isset(self::$instancias[$classe][$chave])) {
                    self::$instancias[$classe][$chave] = false;
                    $instancia = self::unserialize_instancia($_SESSION[OBJETO_CACHE_INSTANCIAS][$classe][$chave]);
                    self::$instancias[$classe][$chave] = $instancia;
                }
                $obj = self::get_objeto($classe);
                $obj->definir_instancia($classe, $chave);
                $vetor[] = $obj;
            }
            return $vetor;
        }
    }


    //
    //     Limpa a cache de sessao de uma ou todas instancias de uma classe
    //
    final static public function limpar_cache($classe, $valor_chave = false) {
    // String $classe: nome da classe
    // Mixed $valor_chave: valor da chave primaria
    //
        if (!self::em_cache($classe, $valor_chave)) {
            return false;
        }
        if ($valor_chave) {
            unset($_SESSION[OBJETO_CACHE_INSTANCIAS][$classe][$valor_chave]);
        } else {
            unset($_SESSION[OBJETO_CACHE_INSTANCIAS][$classe],
                  $_SESSION[OBJETO_CACHE_DEFINICOES][$classe]);
        }
        return true;
    }


    //
    //     Serializa uma instancia para ser armazenada em cache (organiza referencias em formato especial)
    //
    static private function serialize_instancia($classe, $pk, &$instancia) {
    // String $classe: nome da classe da instancia
    // Int $pk: valor da chave primaria
    // Object $instancia: dados a serem serializados
    //
        // Ao salvar dados em sessao, nao e' permitido guardar referencias.
        // Para preservar as referencias da instancia, utilizou-se a notacao:
        // array($classe, $valor_pk)
        // para representar a referencia para uma entidade de uma classe especifica.
        // As referencias da instancia, portanto, sao guardadas em:
        // $instancia->ref_objetos (referencias de $instancia->objetos)
        // $instancia->ref_vetores (referencias de $instancia->vetores)
        // As flags e os vetores de referencias originais nao sao passados para a sessao.
        $i = new stdClass();
        $i->classe      = $classe;
        $i->pk          = $pk;
        $i->valores     = $instancia->valores;
        $i->ref_objetos = array();
        $i->ref_vetores = array();

        // Guardar referencias para objetos filhos
        foreach ($instancia->objetos as $nome_obj => $obj) {
            if ($obj->get_valor_chave() == 0) {
                continue;
            }
            if (!self::em_cache($obj->get_classe(), $obj->get_valor_chave())) {
                if (isset(self::$instancias[$obj->get_classe()][$obj->get_valor_chave()])) {
                    self::set_cache($obj->get_classe(), $obj->get_valor_chave());
                }
            }
            if (self::em_cache($obj->get_classe(), $obj->get_valor_chave())) {
                $i->ref_objetos[$nome_obj] = array($obj->get_classe(), $obj->get_valor_chave());
            }
        }

        // Guardar referencias para vetores
        foreach ($instancia->vetores as $nome_vet => $vet) {
            $i->ref_vetores[$nome_vet] = array();
            foreach ($vet as $index => $obj) {
                if ($obj->get_valor_chave() == 0) {
                    continue;
                }
                if (!self::em_cache($obj->get_classe(), $obj->get_valor_chave())) {
                    self::set_cache($obj->get_classe(), $obj->get_valor_chave());
                }
                $i->ref_vetores[$nome_vet][$index] = array($obj->get_classe(), $obj->get_valor_chave());
            }
        }

        // Serializar apenas dados permitidos
        return serialize($i);
    }


    //
    //     Desserializa uma instancia armazenada em sessao
    //
    static private function unserialize_instancia($instancia_serial) {
    // String $instancia_serial: valor serializado
    //
        // Obs.: ver comentario do metodo serialize_instancia.

        // Desserializar a instancia
        $i = unserialize($instancia_serial);
        $classe = $i->classe;

        // Criar a instancia
        $num_atributos = count(self::$definicoes[$classe]->atributos);

        $instancia = new stdClass();
        $instancia->flag_unicidade = true;
        $instancia->flag_mudanca   = array_fill(0, $num_atributos, false);
        $instancia->valores        = $i->valores;
        $instancia->objetos        = array();
        $instancia->vetores        = array();
        $instancia->referencias    = 1;
        $instancia->orfa           = false;

        // Restaurar referencias dos objetos filhos
        foreach ($i->ref_objetos as $nome_obj => $ref_obj) {
            list($classe_ref, $chave_ref) = $ref_obj;
            if ($chave_ref == 0) {
                // Ignorar
                continue;
            } elseif (!isset(self::$instancias[$classe_ref][$chave_ref])) {
                $instancia->objetos[$nome_obj] = self::get_cache($classe_ref, $chave_ref);
            } elseif (self::$instancias[$classe_ref][$chave_ref] === false) {
                // Aguardar
                continue;
            } else {
                $obj_ref = self::get_objeto($classe_ref);
                $obj_ref->definir_instancia($classe_ref, $chave_ref);
                $instancia->objetos[$nome_obj] = $obj_ref;
            }
        }

        // Restaurar referencias dos vetores
        foreach ($i->ref_vetores as $nome_vet => $vet) {
            $instancia->vetores[$nome_vet] = array();
            foreach ($vet as $indice => $ref_obj) {
                list($classe_ref, $chave_ref) = $ref_obj;
                if (!isset(self::$instancias[$classe_ref][$chave_ref])) {
                    $instancia->vetores[$nome_vet][$indice] = self::get_cache($classe_ref, $chave_ref);
                } elseif (self::$instancias[$classe_ref][$chave_ref] === false) {
                    // Aguardar
                    continue;
                } else {
                    $obj = self::get_objeto($classe_ref);
                    $obj->definir_instancia($classe_ref, $chave_ref);
                    $instancia->vetores[$nome_vet][$indice] = $obj;
                }
            }
        }

        return $instancia;
    }


/// @ DEBUG


    //
    //     Exibe a lista as instancias (self::$instancias)
    //
    final static public function dump_instancias($classe = false) {
    // String $classe: classe a ser analisada
    //
        if ($classe) {
            $vt_classes = array($classe => &self::$instancias[$classe]);
        } else {
            $vt_classes = &self::$instancias;
        }
        $quantidade_classes = count($vt_classes);


        echo '<div style="border: 1px solid red">';
        echo '<p style="background-color: #FFEEEE; margin: 0; padding: .5em; border-bottom: 1px solid green;">INST&Acirc;NCIAS ('.$quantidade_classes.' classe'.(($quantidade_classes != 1) ? 's' : '').')</p>';
        foreach ($vt_classes as $classe => &$instancias) {
            $quantidade_instancias = count($instancias);
            echo '<div style="margin: 1em; border: 1px solid green;">';
            echo '<p style="background-color: #EEFFEE; margin: 0; padding: .5em; border-bottom: 1px solid green;">CLASSE: '.$classe.' ('.$quantidade_instancias.' inst&acirc;ncia'.(($quantidade_instancias != 1) ? 's' : '').')</p>';
            if ($quantidade_instancias) {
                foreach ($instancias as $pos => &$i) {
                    echo '<div style="margin: 1em; border: 1px solid blue;">';
                    echo '<p style="background-color: #EEEEFF; margin: 0; padding: .5em; border-bottom: 1px solid blue;">';
                    echo $classe.'['.$pos.'] ('.$i->referencias.' refer&ecirc;ncia'.(($i->referencias != 1) ? 's' : '').')';
                    echo '</p>';
                    echo '<ul>';
                    foreach ($i->valores as $atributo => $valor) {
                        echo '<li>'.$atributo.' = '.util::exibir_var($valor, UTIL_EXIBIR_PHP).' ('.gettype($valor).')</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
            } else {
                echo '<p>Nenhum</p>';
            }
            echo '</div>';
        }
        echo '</div>';
    }


    //
    //     Exibe as instancias na forma completa
    //
    final public function dump_instancias_completa() {
        util::dump(self::$instancias, 2);
    }


    //
    //     Obtem a quantidade de consultas feitas sob demanda
    //
    final public static function get_quantidade_consultas_por_demanda() {
        return self::$consultas_por_demanda;
    }

}//class
