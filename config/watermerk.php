<?php

return [
    // Statusstempel op factuur-PDF's (CONCEPT, BETAALD, VERVALLEN,
    // HERINNERING, AANMANING, GECREDITEERD). Uit te zetten zonder deploy
    // via WATERMERK_STEMPEL=false op Railway.
    'stempel' => env('WATERMERK_STEMPEL', true),
];
