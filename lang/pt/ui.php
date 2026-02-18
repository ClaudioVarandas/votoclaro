<?php

return [
    // Navigation
    'nav' => [
        'dashboard' => 'Painel',
        'initiatives' => 'Iniciativas',
        'parties' => 'Partidos',
        'about' => 'Sobre',
    ],

    // Dashboard
    'dashboard' => [
        'title' => 'Painel Legislativo',
        'description' => 'Acompanhe as iniciativas legislativas da Assembleia da República Portuguesa.',
        'total_initiatives' => 'Total de Iniciativas',
        'approval_rate' => 'Taxa de Aprovação',
        'government_initiatives' => 'Iniciativas do Governo',
        'latest_votes' => 'Últimas Votações',
        'view_all' => 'Ver todas',
        'no_votes' => 'Sem votações registadas.',
    ],

    // Initiatives
    'initiatives' => [
        'title' => 'Iniciativas Legislativas',
        'description' => 'Consulte todas as iniciativas legislativas da Assembleia da República.',
        'search_placeholder' => 'Pesquisar iniciativas...',
        'filter_status' => 'Estado',
        'filter_author' => 'Autoria',
        'all_statuses' => 'Todos',
        'all_authors' => 'Todos',
        'load_more' => 'Carregar mais',
        'no_results' => 'Nenhuma iniciativa encontrada.',
        'entry_date' => 'Data de entrada',
        'final_vote_date' => 'Votação final',
        'days_in_progress' => ':count dias em discussão',
        'votes_section' => 'Votações',
        'no_votes' => 'Sem votações registadas para esta iniciativa.',
        'vote_on' => 'Votação de',
        'unanimous' => 'Unânime',
        'back_to_list' => 'Voltar às iniciativas',
        'showing_results' => ':count iniciativas encontradas',
        'type' => 'Tipo',
        'author' => 'Autor',
        'state' => 'Estado',
        'duration' => 'Duração',
        'entry' => 'Entrada',
        'final_vote' => 'Votação Final',
        'approved_in_days' => 'Aprovado em :count dias',
        'rejected_in_days' => 'Rejeitado em :count dias',
        'pending' => 'Pendente',
        'what_does_it_mean' => 'O que significa?',
        'duration_pending' => '—',
    ],

    // Initiative type explanations
    'initiative_type_info' => [
        'projeto_de_lei' => 'Proposta de lei apresentada por deputados, grupos parlamentares ou grupos de cidadãos. Se aprovada, torna-se lei.',
        'proposta_de_lei' => 'Proposta de lei apresentada pelo Governo. Se aprovada, torna-se lei.',
        'projeto_de_resolucao' => 'Proposta apresentada por deputados ou grupos parlamentares que não tem força de lei, mas expressa uma posição ou recomendação da Assembleia.',
        'proposta_de_resolucao' => 'Proposta de resolução apresentada pelo Governo, normalmente para ratificação de acordos internacionais.',
        'projeto_de_deliberacao' => 'Proposta relativa ao funcionamento interno da Assembleia da República (ex: composição de comissões, calendário de trabalhos).',
        'inquerito_parlamentar' => 'Proposta de abertura de uma comissão de inquérito para investigar matérias de interesse público.',
        'apreciacao_parlamentar' => 'Processo que permite à Assembleia apreciar e eventualmente alterar ou cessar decretos-lei do Governo.',
    ],

    // Breadcrumbs
    'breadcrumbs' => [
        'dashboard' => 'Painel',
        'initiatives' => 'Iniciativas',
    ],

    // Positions
    'position' => [
        'favor' => 'A Favor',
        'contra' => 'Contra',
        'abstencao' => 'Abstenção',
    ],

    // Vote results
    'vote_result' => [
        'Aprovado' => 'Aprovado',
        'Rejeitado' => 'Rejeitado',
        'Prejudicado' => 'Prejudicado',
    ],

    // Statuses
    'status' => [
        'approved' => 'Aprovado',
        'rejected' => 'Rejeitado',
        'in_progress' => 'Em Discussão',
    ],

    // Table headers
    'table' => [
        'initiative' => 'Iniciativa',
        'status' => 'Estado',
        'author' => 'Autoria',
        'date' => 'Data',
        'result' => 'Resultado',
    ],

    // Author types
    'author_type' => [
        'Government' => 'Governo',
        'Other' => 'Parlamento',
    ],

    // Footer
    'footer' => [
        'navigation' => 'Navegação',
        'information' => 'Informação',
        'contact' => 'Contacto',
        'about_text' => 'VotoClaro torna os dados legislativos da Assembleia da República acessíveis e compreensíveis para todos os cidadãos.',
        'open_data' => 'Dados Abertos',
        'methodology' => 'Metodologia',
        'privacy' => 'Privacidade',
        'source_code' => 'Código Fonte',
        'attribution' => 'Dados da Assembleia da República Portuguesa.',
        'rights' => 'Todos os direitos reservados.',
    ],

    // Parties
    'parties' => [
        'title' => 'Partidos Parlamentares',
        'description' => 'Consulte as estatísticas de votação de cada partido na Assembleia da República.',
        'total_votes' => 'Total de Votações',
        'favor_pct' => '% A Favor',
        'contra_pct' => '% Contra',
        'abstencao_pct' => '% Abstenção',
        'government_alignment' => 'Alinhamento com o Governo',
        'government_alignment_description' => 'Percentagem de votos a favor em iniciativas do Governo.',
        'monthly_trend' => 'Tendência Mensal',
        'show_trend' => 'Ver tendência mensal',
        'hide_trend' => 'Ocultar tendência mensal',
        'back_to_list' => 'Voltar aos partidos',
        'no_data' => 'Sem dados de votação disponíveis.',
    ],

    // About
    'about' => [
        'title' => 'Sobre o VotoClaro',
        'description' => 'Saiba mais sobre o VotoClaro e como funciona.',
        'what_is_title' => 'O que é o VotoClaro',
        'what_is_body' => 'O VotoClaro é uma plataforma independente e gratuita que torna os dados legislativos da Assembleia da República Portuguesa acessíveis e compreensíveis para todos os cidadãos. Acreditamos que a transparência parlamentar é essencial para uma democracia saudável.',
        'parliament_title' => 'Como funciona o Parlamento',
        'parliament_body' => 'A Assembleia da República é o órgão legislativo de Portugal, composta por 230 deputados eleitos. As iniciativas legislativas — propostas de lei, projetos de lei e outros — são debatidas e votadas em plenário. Cada partido parlamentar expressa a sua posição através do voto: a favor, contra ou abstenção.',
        'reading_data_title' => 'Como ler os dados',
        'reading_data_body' => 'Os dados apresentados no VotoClaro incluem todas as votações registadas na Assembleia da República. A taxa de aprovação indica a percentagem de iniciativas aprovadas. O alinhamento com o Governo mostra com que frequência cada partido vota a favor de iniciativas propostas pelo executivo. As tendências mensais permitem acompanhar a evolução do comportamento de voto ao longo do tempo.',
        'data_source_title' => 'Fonte dos dados',
        'data_source_body' => 'Todos os dados são obtidos a partir dos registos oficiais e abertos da Assembleia da República Portuguesa. A informação é atualizada periodicamente para refletir as votações mais recentes. O VotoClaro não altera nem edita os dados — apresenta-os de forma organizada e acessível.',
        'contact_title' => 'Contacto',
        'contact_body' => 'O VotoClaro é um projeto de código aberto. Se tiver sugestões, encontrar erros ou quiser contribuir, entre em contacto através do repositório do projeto no :github_link.',
    ],

    // Misc
    'skip_to_content' => 'Saltar para o conteúdo',
    'dark_mode' => 'Modo escuro',
    'light_mode' => 'Modo claro',
    'menu' => 'Menu',
    'government' => 'Governo',
    'days_average' => ':count dias em média',
];
