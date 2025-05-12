<?php

// Chemin vers le dossier où les emails sont stockés
$mailDir = __DIR__ . '/../var/mail';

// Vérifier si le dossier existe
if (!is_dir($mailDir)) {
    echo "Le dossier des emails n'existe pas : {$mailDir}\n";
    exit(1);
}

// Lister tous les fichiers dans le dossier
$files = glob($mailDir . '/*.email');

if (empty($files)) {
    echo "Aucun email trouvé dans {$mailDir}\n";
    exit(0);
}

echo "Emails trouvés : " . count($files) . "\n\n";

// Afficher les emails du plus récent au plus ancien
usort($files, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});

foreach ($files as $file) {
    $filename = basename($file);
    $time = date('Y-m-d H:i:s', filemtime($file));

    echo "====================================================\n";
    echo "Email: {$filename}\n";
    echo "Date: {$time}\n";
    echo "====================================================\n";

    // Afficher le contenu du fichier
    $content = file_get_contents($file);
    echo $content . "\n\n";
}
