<?php
//
// SIMP
// Descricao: JavaScript que mostra/esconde campos de senha
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.ufla.br
// Versao: 1.0.0.1
// Data: 06/06/2011
// Modificado: 04/10/2012
// Copyright (C) 2011  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

$js = <<<JAVASCRIPT

definir_eventos();

//
//     Define os eventos da ferramenta
//
function definir_eventos() {
    var r = document.getElementById('usuario-radio_geracao_senha_0');
    definir_operacao_senha(r);
    var r = document.getElementById('usuario-radio_geracao_senha_1');
    definir_operacao_senha(r);
}


//
//     Define uma funcao de evento para o input
//
function definir_operacao_senha(input) {
    if (!input) { return false; }
    input.onclick = function () {
        var field = document.getElementById('usuario-fieldset_ff64a1c43498d955147518733ac88c7c');
        if (this.checked) {
            field.style.display = (this.getAttribute("value") == 1) ? "none" : "block";
        }
    };
    if (input.checked) {
        input.onclick();
    }
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