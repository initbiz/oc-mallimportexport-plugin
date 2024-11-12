<?php

return [

    'plugin' => [
        'name' => 'Mall import/export',
        'description' => 'Import/Export Offline.Mall plugin',
        'author' => 'Initbiz'
    ],

    'permissions' => [
        'import' => 'Import produktów',
        'export' => 'Eksport produktów',
        'export_orders' => 'Eksport zamówień',
    ],

    'menus' => [
        'importexport' => 'Import lub eksport',
        'import' => 'Import produktów',
        'export' => 'Eksport produktów',
        'export_orders' => 'Eksport zamówień',
    ],

    'import' => [
        'title' => 'Import danych sklepu',
        'errors' => [
            'emptyline' => 'Pusta linia',
            'notaproduct' => 'ID użytkownika :ref nie odpowiada żadnemu produktowi ani wariantowi.',
            'forref' => 'Dla produktu :ref ',
            'notanumber' => ', kolumna :type (:price) nie jest poprawna',
            'notacurrency' => ', niepoprawny kod waluty (:code)',
        ],
    ],

    'export' => [
        'title' => 'Eksport danych sklepu',
    ],

    'export_orders' => [
        'title' => 'Eksport zamówień',
    ],

    'columns' => [
        'allow_out_of_stock_purchases' => 'Zamawianie poniżej stanu magazynowego',
        'name' => 'Nazwa',
        'description' => 'Opis',
        'price' => 'Cena',
        'published' => 'Opublikowany',
        'stock' => 'Stan magazynowy',
        'user_defined_id' => 'ID użytkownika',
        'weight' => 'Waga (g)',
        'link' => 'Link do produktu',
        'admin_link' => 'Link w panelu do produktu',
    ],

    'ux' => [
        'export_button' => 'Eksportuj dane',
        'import_button' => 'Importuj dane',
        'only_variants' => 'Tylko warianty produktów',
        'only_variants_comment' => 'dla produktów z wariantami, używaj tylko wariantów',
        'export_links' => 'Wyeksportuj link do produktu',
        'export_links_comment' => 'dodaj publiczny link do strony produktu',
        'export_admin_links' => 'Wyeksportuj link do produktu w panelu',
        'export_admin_links_comment' => 'dodaj link do strony zarządzania produktem',
        'return_list' => 'Powróć do listy produktów',
        'export_success_message' => 'Plik eksportu jest w trakcie generowania. Odśwież stronę za chwilę aby go pobrać.',
        'generated_file_label' => 'Kliknij poniżej aby pobrać najnowszy plik:',
        'refresh_page' => 'Odśwież stronę',
        'export_ongoing' => 'Eksport trwa, odśwież stronę za chwilę aby pobrać plik.',
        'export_queue_message' => 'Procedura eksportu uruchomiona. Odśwież stronę za chwilę, aby pobrać plik.',
        'export_queue_success_button' => 'Zamknij',
    ],
];
