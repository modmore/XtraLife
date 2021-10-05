<?php
$snips = [
    'XtraLifePreRegister' => [
        'filename' => 'preregister',
        'description' => 'Used as prehook for Register to validate the email and set the class key.',
    ],
    'XtraLifeRegister' => [
        'filename' => 'register',
        'description' => 'Used as posthook for Register to create the user in XtraLife. ',
    ],
    'XtraLifeReset' => [
        'filename' => 'reset',
        'description' => 'Password reset flow for XtraLife users.',
    ],
    'XtraLifeUpdateProfile' => [
        'filename' => 'updateprofile',
        'description' => 'Used as posthook for UpdateProfile to update the email in XtraLife when changed from MODX.',
    ],
];

$snippets = array();
$idx = 0;

foreach ($snips as $name => $opts) {
    $idx++;
    $snippets[$idx] = $modx->newObject('modSnippet');
    $snippets[$idx]->fromArray(array(
       'name' => $name,
       'description' => $opts['description'] . ' (Part of XtraLife)',
       'snippet' => getSnippetContent($sources['snippets'] . $opts['filename'] . '.snippet.php')
    ));
}

return $snippets;
