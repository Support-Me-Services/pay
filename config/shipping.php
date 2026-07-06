<?php

return [

    // Metody dostawy sklepu. Cena w groszach. `point` => true dla metod
    // wymagających wskazania punktu/paczkomatu. `enabled` => false oznacza
    // metodę zapowiedzianą (widoczną, ale niewybieralną — „wkrótce").
    'methods' => [
        'pickup'           => ['label' => 'Odbiór osobisty',       'price' => 0,    'point' => false, 'enabled' => true],
        'inpost_paczkomat' => ['label' => 'Paczkomat InPost 24/7', 'price' => 1299, 'point' => true,  'enabled' => false],
        'orlen_paczka'     => ['label' => 'Orlen Paczka',          'price' => 999,  'point' => true,  'enabled' => false],
        'inpost_kurier'    => ['label' => 'Kurier InPost',         'price' => 1599, 'point' => false, 'enabled' => false],
        'dpd'              => ['label' => 'Kurier DPD',            'price' => 1699, 'point' => false, 'enabled' => false],
        'dhl'              => ['label' => 'Kurier DHL',            'price' => 1899, 'point' => false, 'enabled' => false],
    ],

    'default' => 'pickup',

];
