<?php

return [
    // Vaste incassopartner — alleen bevestigde, echte gegevens.
    'partner_name' => env('INCASSO_PARTNER_NAME', 'Armaere Gerechtsdeurwaarders'),

    // Waar elke nieuwe incasso-opdracht (compleet dossier) naartoe gaat.
    'claims_email' => env('INCASSO_CLAIMS_EMAIL', 'j.backers@armaere.nl'),
    'cc'           => env('INCASSO_CC', 'info@creditline.nl'),
];
