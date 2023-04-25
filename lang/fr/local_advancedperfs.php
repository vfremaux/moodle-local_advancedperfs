<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

$string['advancedperfs:view'] = 'Voir les perfs avancées';
$string['advancedperfs:hasdebugrole'] = 'A un rôle de debug';

// Privacy
$string['privacy:metadata'] = 'Le plugin de mesure de performance technique ne stocke pas de données personnelles.';

$string['actives'] = 'Actifs';
$string['clear'] = 'Purger la trace';
$string['cleartrace'] = 'Vider la trace';
$string['configadvancedperfsenabled'] = 'Activation';
$string['configdebugdisplayreleasevalue'] = 'Mode d\'affichage du déboggage par défaut';
$string['configdebugfromips'] = 'Les sessions provenant de ces IPs sont placées en mode déboggage.';
$string['configdebugreleaseafter_desc'] = 'Modifiera les valeurs de déboggage si le niveau de déboggage est supérieur ou égal au seuil depuis au moins ce temps (en heure).';
$string['configdebugreleasethreshold_desc'] = 'Modifiera les valeurs de déboggage si le niveau de déboggage est supérieur ou égal à ce seuil.';
$string['configdebugreleaseafter'] = 'Relâcher le déboggage au bout de';
$string['configdebugreleasethreshold'] = 'Seuil de relâchement du déboggage';
$string['configdebugreleasevalue'] = 'Valeur de déboggage par défaut';
$string['configdebugusers'] = 'Les utilisateurs dont les IDs figurent dans cette liste (à virgules) sont passés en mode déboggage.';
$string['configdebugnotifyrelease'] = 'Notifier l\'administrateur';
$string['configdebugnotifyrelease_desc'] = 'Si actif, un courriel est envoyé à l\'administrateur lors du relâchement.';
$string['configfilelogging'] = 'Activer la journalisation fichiers';
$string['configfixenabled'] = 'Activer la correction de données';
$string['configmaxtracefilesize'] = 'Taille max du fichier de trace';
$string['configmaxtracefilesize_desc'] = 'Si la taille est limitée, le fichier de trace sera vidé lorsqu\'il dépasse cette limite.';
$string['configfixsql'] = 'SQL de correction';
$string['configlongpagethreshold'] = 'Temps de page longue';
$string['configslowpagederiv'] = 'Variation de pages lentes';
$string['configslowpagederivthreshold'] = 'Seuil d\'alerte sur la dérivée';
$string['configslowpagescounter'] = 'Compteur de pages lentes';
$string['configslowpagescounterrec'] = 'Mémoire de compteur (N-1)';
$string['configuserstosendto'] = 'Utilisateurs à avertir';
$string['configverylongpagethreshold'] = 'Temps de page très longue';
$string['configtrace'] = 'Tracer vers';
$string['configtrace_desc'] = 'Endroit de la trace. Doit être autorisé en écriture par le serveur Web. En général localisé à \$CFG->dataroot.\'/trace.log\'. La mention %DATAROOT%/trace.log est acceptée. Si elle n\'est pas définie, aucune trace ne sera produite.';
$string['configtraceout'] = 'Sortie de trace';
$string['configtraceout_desc'] = 'Si activé, Les sorties de trace debug_trace() produiront leur sortie (la localisation du fichier de trace doit être définie).';
$string['content'] = 'Contenu';
$string['data'] = 'Structures de données';
$string['debug'] = 'Deboggage';
$string['errors'] = 'Erreurs';
$string['notices'] = 'Notices';
$string['finedebug'] = 'Deboggage fin';
$string['datafixes'] = 'Correction de données';
$string['daysslow'] = 'Jours lents';
$string['db'] = 'DB';
$string['dbcalls'] = 'DB';
$string['dbcallsdist'] = 'Appels à la base';
$string['dbquerydist'] = 'distribution du nombre d\'appels';
$string['dbratiodist'] = 'Taux de temps de calcul dans la base';
$string['dbtimedist'] = 'Temps passé dans la base';
$string['debugfromips'] = 'IPs de debug';
$string['debugtrack'] = 'Relâchement automatique du déboggage';
$string['debugusers'] = 'Utilisateurs de debug';
$string['distinctusers'] = 'Utilisateurs distincts';
$string['distribution'] = 'Distribution';
$string['envusers'] = 'Utilisateurs dans l\'environnement';
$string['footer'] = 'Footer';
$string['header'] = 'Header';
$string['init'] = 'Initialisation';
$string['layoutinit'] = 'Layout intialisations';
$string['location'] = 'Emplacement';
$string['max'] = 'max';
$string['mean'] = 'Moyenne (SP/j)';
$string['mean'] = 'moy.';
$string['mem'] = 'Memoire';
$string['min'] = 'min';
$string['nolimit'] = 'Pas de limite';
$string['mostaffecteduser'] = 'Utilisateur le plus affecté';
$string['noroles'] = 'Aucun rôle';
$string['noslowpages'] = 'Pas de pages lentes détectées.';
$string['nothingsince'] = 'Rien depuis';
$string['nothingsince'] = 'Rien depuis';
$string['num'] = 'Ocurrences';
$string['no'] = 'Pas de trace';
$string['notracefile'] = 'Pas de fichier trace disponible.';
$string['numusersaffected'] = 'Nombre d\'utilisateurs affectés';
$string['occurrences'] = 'ocurrences';
$string['pluginname'] = 'Performances avancées';
$string['range'] = 'Période';
$string['ratioaffectedusers'] = 'Ratio des utilisateurs affectés';
$string['reset'] = 'RAZ';
$string['reload'] = 'Recharger';
$string['seetrace'] = 'Voir la trace';
$string['setup'] = 'Initial Setup';
$string['settings'] = 'Aller aux réglages';
$string['since'] = 'Depuis';
$string['slowpages'] = 'Pages lentes';
$string['slowpagescount'] = 'Pages lentes (> {$a}s)';
$string['slowpagesratio'] = 'Taux';
$string['slowpagesreport'] = 'Raport sur les performances de page.';
$string['taskmonitor'] = 'Surveillance des pages lentes';
$string['taskmonitor'] = 'Tâche d\'observation continue des performances';
$string['tasktrackdebug'] = 'Tâche de relâchement du déboggage.';
$string['timedist'] = 'distribution du temps passé';
$string['timeline'] = 'Calendrier des pages lentes (nombre par jour)';
$string['timerelmem'] = 'Temps passé vs. mémoire consomée';
$string['timerelusers'] = 'Temps passé vs. utilisateurs présents';
$string['timespent'] = 'Temps de calcul';
$string['trace'] = 'Trace technique';
$string['tracetoobig'] = 'Le fichier trace est trop grand';
$string['unconnectedusers'] = 'Non connectés';
$string['urls'] = 'Urls';
$string['urlsbyfreq'] = 'Fréquence par Url de base';
$string['users'] = 'Utilisateurs';
$string['worstday'] = 'Jour le plus lent';

$string['tracetoobig_desc'] = 'Le fichier de trace est trop grand pour être téléchargé. Vous devriez le vider et répeter votre cas de test.';

$string['configdebugdisplayreleasevalue_desc'] = '';

$string['configdebugreleaseafter_desc'] = 'Modifiera les valeurs de déboggage si le niveau de déboggage est supérieur
ou égal au seuil depuis au moins ce temps (en heure).';

$string['configdebugreleasethreshold_desc'] = 'Modifiera les valeurs de déboggage si le niveau de déboggage est
supérieur ou égal à ce seuil.';

$string['configdebugreleasevalue_desc'] = 'Le niveau de déboggage après relâchement.';

$string['tracetoobig_desc'] = 'Le fichier de trace est trop grand pour être téléchargé. Vous devriez le
vider et répeter votre cas de test.';

$string['configadvancedperfsenabled_desc'] = 'Si activé, des mesure de temps d\'exécution détaillées sont affichées pour
l\'administrateur.';

$string['configfilelogging_desc'] = 'Si activé, les événements sont journlisés en fichiers.';

$string['configlongpagethreshold_desc'] = 'Seuil de temps (en secondes) au dela duquel une page est considérée comme longue.';

$string['configslowpagederiv_desc'] = 'La valeur instantanée de la dérivée du compteur (pages lentes/minute).';

$string['configslowpagederivthreshold_desc'] = 'Seuil au dela duquel l\'accroissement du nombre de pages lentes doit être signalé
aux administrateurs.';

$string['configslowpagescounter_desc'] = 'Compte les occurences des pages exédent le seuil de temps \'long\'.';

$string['configslowpagescounterrec_desc'] = 'Le dernier état de compteur à l\'exécution de tâche précédente.';

$string['configuserstosendto_desc'] = 'Une liste à virgules de références d\'utilisateurs donnés par leur courriel, leur identifiant
ou leur ID numérique.';

$string['configverylongpagethreshold_desc'] = 'Seuil de temps d\'exécution au dela duquel une alerte immédiate est envoyée
aux administrateurs.';

$string['configfixenabled_desc'] = 'Si activé, alors la correction de données sera exécutée une fois par
jour (ou selon la programmation de la tâche programmée).';

$string['configfixsql_desc'] = 'Entrez les instructions SQL qui s\'exécuteront lors de la correction de données. Les
instructions doivent être terminées par un point-virgule (;).';

$string['datafixes_desc'] = 'La correction de données permet d\'ajouter un traitement symptomatique d\'erreurs de données simples dans 
la base de données de moodle. Il ne corrige pas les causes premières qui conduisent aux données erronées, mais permettent de sécuriser
temporairement une installation le temps que les correctifs puissent être produits et déployés.';
