<?php

return [

    // Metody dostawy sklepu. Cena w groszach. `point` => true dla metod
    // wymagających wskazania punktu/paczkomatu (kod podawany w koszyku).
    'methods' => [
        'inpost_paczkomat' => ['label' => 'Paczkomat InPost 24/7', 'price' => 1299, 'point' => true],
        'orlen_paczka'     => ['label' => 'Orlen Paczka',          'price' => 999,  'point' => true],
        'inpost_kurier'    => ['label' => 'Kurier InPost',         'price' => 1599, 'point' => false],
        'dpd'              => ['label' => 'Kurier DPD',            'price' => 1699, 'point' => false],
        'dhl'              => ['label' => 'Kurier DHL',            'price' => 1899, 'point' => false],
        'pickup'           => ['label' => 'Odbiór osobisty (Pruszków)', 'price' => 0, 'point' => false],
    ],

    'default' => 'inpost_paczkomat',

];
