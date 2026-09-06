<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Messages de validation (français)
|--------------------------------------------------------------------------
|
| Traductions françaises des messages de validation du framework pour
| les règles utilisées par l'application, plus des libellés d'attributs
| localisés pour que les messages se lisent naturellement.
|
*/

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'array' => 'Le champ :attribute doit être un tableau.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe fourni est incorrect.',
    'date' => 'Le champ :attribute doit être une date valide.',
    'digits' => 'Le champ :attribute doit compter :digits chiffres.',
    'email' => 'Le champ :attribute doit être une adresse courriel valide.',
    'enum' => 'La valeur sélectionnée pour :attribute est invalide.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'invite_code' => "Ce code d'invitation est invalide, expiré ou déjà utilisé.",
    'lowercase' => 'Le champ :attribute doit être en minuscules.',
    'max' => [
        'array' => 'Le champ :attribute ne doit pas avoir plus de :max éléments.',
        'numeric' => 'Le champ :attribute ne doit pas être supérieur à :max.',
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'min' => [
        'array' => 'Le champ :attribute doit avoir au moins :min éléments.',
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'min_numeric' => 'Le champ :attribute doit être au moins :min.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'present' => 'Le champ :attribute doit être présent.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_if' => 'Le champ :attribute est obligatoire quand :other est :value.',
    'required_without' => 'Le champ :attribute est obligatoire quand :values est absent.',
    'required_without_all' => 'Le champ :attribute est obligatoire quand aucune des valeurs :values n\'est présente.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'unique' => 'La valeur du champ :attribute est déjà utilisée.',
    'uuid' => 'Le champ :attribute doit être un UUID valide.',

    'attributes' => [
        'companyName' => 'nom de la compagnie',
        'email' => 'adresse courriel',
        'firstName' => 'prénom',
        'lastName' => 'nom',
        'phoneDay' => 'téléphone de jour',
        'cellPhone' => 'cellulaire',
        'addressStreet' => 'rue',
        'addressCity' => 'ville',
        'addressPostalCode' => 'code postal',
        'addressCountry' => 'pays',
        'remark' => 'remarque',
        'clientId' => 'client',
        'season' => 'saison',
        'label' => 'libellé',
        'amount' => 'montant',
        'services' => 'services',
        'payments' => 'versements',
        'name' => 'nom',
        'password' => 'mot de passe',
        'current_password' => 'mot de passe actuel',
        'role' => 'rôle',
        'code' => 'code',
        'invite_code' => "code d'invitation",
        'message' => 'message',
    ],
];
