<?php
//
// SIMP
// Descricao: Abre uma ajuda em popup
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.1
// Data: 27/06/2011
// Modificado: 04/10/2012
// Copyright (C) 2011  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

$js = <<<JAVASCRIPT
window.onload = function() {
    var conteudo = document.getElementById("conteudo");
    var titulo = document.getElementById("titulo");
    if (!conteudo || !titulo || !window.opener) {
        return false;
    }

    // Atualizar titulo
    titulo.appendChild(document.createTextNode(window.opener.document.title));

    // Atualizar ajuda
    var divs = window.opener.document.getElementsByTagName("div");
    var l = divs.length;
    for (var i = 0; i < l; i++) {
        var div = divs.item(i);
        if (div.className == "bloco_ajuda_aberto" || div.className == "bloco_ajuda_fechado") {
            var bq = div.getElementsByTagName("blockquote").item(0).cloneNode(true);
            var links = bq.getElementsByTagName("a");
            var l2 = links.length;
            for (var j = 0; j < l2; j++) {
                var link = links.item(j);
                if (link.className == "bt_ajuda_externa") {
                    bq.removeChild(link);
                }
            }

            conteudo.appendChild(bq);
        }
    }
    return true;
};
JAVASCRIPT;

// Codificar
$js_packer = new javascriptpacker($js);
$js = $js_packer->pack();

$opcoes_http = array(
    'arquivo' => 'popup.js',
    'tempo_expira' => TEMPO_EXPIRA,
    'compactacao' => true,
    'ultima_mudanca' => getlastmod()
);

// Exibir
http::cabecalho('text/javascript; charset='.$CFG->charset, $opcoes_http);
echo $js;
exit(0);