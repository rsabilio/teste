<?php
//
// SIMP
// Descricao: Script de configuracoes de e-mail
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.3
// Data: 11/11/2009
// Modificado: 04/10/2012
// Copyright (C) 2009  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

// Ao marcar a opcao de tipo de e-mail SMTP,
// deve ser aberto o fieldset com os campos especificos
// deste tipo de e-mail
$cod_smtp = CONFIG_EMAIL_SMTP;

$js = <<<JAVASCRIPT
atualizar_tipo();

//
//     Atualiza o select de tipo de e-mail
//
function atualizar_tipo() {
    var tipo = document.getElementById("config-tipo_email");
    if (!tipo) {
        return false;
    }
    tipo.onchange = function() {
        var fieldset = document.getElementById("config-fieldset_c2239a92bde29f0a9f9173193cc2fe00");

        switch (this.options[this.selectedIndex].value) {
        case '{$cod_smtp}':
            fieldset.style.display = "block";
            break;
        default:
            fieldset.style.display = "none";
            break;
        }
        return true;
    };
    tipo.onchange();
    return true;
}

JAVASCRIPT;

// Codificar
$js_packer = new javascriptpacker($js);
$js = $js_packer->pack();

$opcoes_http = array(
    'arquivo' => 'script.js',
    'tempo_expira' => TEMPO_EXPIRA,
    'compactacao' => true,
    'ultima_mudanca' => getlastmod()
);

// Exibir
http::cabecalho('text/javascript; charset='.$CFG->charset, $opcoes_http);
echo $js;
exit(0);
