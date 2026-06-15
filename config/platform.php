<?php

return [

    // Kontekst platformy jest USTAWIANY W RUNTIME przez ResolveTenant
    // (na podstawie hosta żądania / TENANT dla CLI). Poniższe wartości to
    // tylko bezpieczne wartości startowe przed rozwiązaniem tenanta — nie
    // wymagają wpisów w .env.
    //   role       => gateway | storefront
    //   shop_kind  => products | church | null
    'role' => 'gateway',

    'shop_kind' => null,

];
