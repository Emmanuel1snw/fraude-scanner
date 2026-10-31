<?php
// src/Apprendre.php
// Mode apprentissage : commandes /apprendre, /resume, /plan, /quiz, /reponse, /stop.
// Le cours, la question en cours et le score sont gardés dans la session PHP.

class Apprendre
{
    private GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /** Retourne ['type'=>'texte','mode'=>..., 'texte'=>...] */
    public function executer(string $message, ?array $image = null): array
    {
        $message = trim($message);
        $espace  = strpos($message, ' ');
        $cmd     = $espace === false ? $message : substr($message, 0, $espace);
        $reste   = $espace === false ? '' : trim(substr($message, $espace + 1));

        return match (mb_strtolower($cmd)) {
            '/apprendre' => $this->cmdApprendre($reste, $image),
            '/resume'    => $this->cmdResume(),
            '/plan'      => $this->cmdPlan($reste),
            '/quiz'      => $this->cmdQuiz(),
            '/reponse'   => $this->cmdReponse($reste),
            '/stop'      => $this->cmdStop(),
            default      => $this->aide(),
        };
    }

    // ---------- Commandes ----------
    private function cmdApprendre(string $reste, ?array $image): array
    {
        $cours = $reste;

        if ($image !== null) {
            // Seul le fournisseur google accepte les images (vision).
            $transcription = $this->gemini->generer(
                "Transcris ce document de cours en texte brut, fidèlement, en français. Réponds uniquement avec le texte transcrit.",
                $image
            );
            if ($transcription !== null && trim($transcription) !== '') {
                $cours = trim($transcription);
            } elseif ($cours === '') {
                return $this->texte("apprendre", "Je n'ai pas pu lire la photo. Colle le texte du cours après /apprendre, ou réessaie avec une image plus nette.");
            }
        }

        if ($cours === '') {
            if ($this->cours() === '') {
                return $this->texte("apprendre", "Mode apprentissage activé.\n\nEnvoie ton cours avec : /apprendre suivi du texte de ton cours (ou joins une photo).\n\nAutres commandes : /resume, /plan [jours], /quiz, /reponse ..., /stop");
            }
            return $this->texte("apprendre", "Ton cours est déjà enregistré (" . $this->tailleCours() . ").\nTu peux le remplacer avec /apprendre [nouveau cours], ou continuer avec /resume, /plan ou /quiz.");
        }

        if (mb_strlen($cours) > 20000) {
            $cours = mb_substr($cours, 0, 20000);
        }
        $_SESSION['fs_cours'] = $cours;
        $_SESSION['fs_question'] = null;
        $_SESSION['fs_attendu']  = null;

        return $this->texte("apprendre", "Cours enregistré (" . $this->tailleCours() . ") ! Que veux-tu faire maintenant ?\n/resume pour un résumé\n/plan 5 pour un programme de révision\n/quiz pour t'entraîner");
    }

    private function cmdResume(): array
    {
        $cours = $this->cours();
        if ($cours === '') {
            return $this->texte("apprendre", "Envoie d'abord ton cours avec : /apprendre suivi du texte de ton cours (ou joins une photo).");
        }

        $consigne = "Voici un cours. Fais-en un résumé en 5 points courts maximum, puis liste 5 mots clés.\n\nCOURS :\n\"\"\"\n$cours\n\"\"\"\n\nRéponds en français simple, avec des puces.";
        $resume = $this->gemini->generer($consigne);

        if ($resume === null) {
            // Résumé de secours sans Gemma : premières phrases + mots fréquents.
            $phrases = preg_split('/(?<=[.!?])\s+/u', $cours);
            $points  = array_slice(array_filter(array_map('trim', $phrases)), 0, 4);
            $mots    = $this->motsCles($cours, 5);
            $resume  = "Résumé automatique (Gemma n'a pas répondu) :\n" . implode("\n", array_map(fn($p) => "• $p", $points))
                     . "\n\nMots clés : " . implode(', ', $mots);
        }

        return $this->texte("apprendre", $resume);
    }

    private function cmdPlan(string $reste): array
    {
        $cours = $this->cours();
        if ($cours === '') {
            return $this->texte("apprendre", "Envoie d'abord ton cours avec /apprendre, puis demande un plan.");
        }

        $jours = max(1, min(14, (int) ($reste !== '' ? $reste : 3) ?: 3));
        $consigne = "Voici un cours. Crée un programme de révision sur $jours jours, un point par jour, adapté à un étudiant togolais. Réponds en français simple, sous la forme :\nJour 1 : ...\nJour 2 : ...\n\nCOURS :\n\"\"\"\n$cours\n\"\"\"";

        $plan = $this->gemini->generer($consigne);

        if ($plan === null) {
            $plan = "Plan de secours (Gemma n'a pas répondu) :\n";
            for ($i = 1; $i <= $jours; $i++) {
                $plan .= "Jour $i : " . match (true) {
                    $i === 1        => "Relis tout le cours une première fois.",
                    $i === $jours   => "Refais le quiz et révise tes erreurs.",
                    $i % 2 === 0    => "Révise les points clés vus la veille.",
                    default         => "Résume le cours de mémoire, puis compare avec le résumé.",
                } . "\n";
            }
            $plan .= "\nRéessaie /plan $jours quand la connexion revient pour un plan adapté à ton cours.";
        }

        return $this->texte("apprendre", $plan);
    }

    private function cmdQuiz(): array
    {
        $cours = $this->cours();
        if ($cours === '') {
            return $this->texte("apprendre", "Envoie d'abord ton cours avec /apprendre, puis lance le quiz.");
        }

        $consigne = "Voici un cours. Pose UNE question de compréhension dessus, niveau lycée.\nRéponds UNIQUEMENT en JSON valide : {\"question\":\"...\",\"reponse\":\"réponse attendue courte\"}\n\nCOURS :\n\"\"\"\n" . mb_substr($cours, 0, 6000) . "\n\"\"\"";
        $q = $this->gemini->genererJson($consigne);

        if ($q === null || empty($q['question'])) {
            return $this->texte("apprendre", "Je n'ai pas réussi à créer une question. Réessaie /quiz dans un instant.");
        }

        $_SESSION['fs_question'] = trim((string) $q['question']);
        $_SESSION['fs_attendu']  = trim((string) ($q['reponse'] ?? ''));

        return $this->texte("apprendre", "Question : " . $_SESSION['fs_question'] . "\n\nRéponds avec /reponse suivi de ta réponse.");
    }

    private function cmdReponse(string $reste): array
    {
        $question = $_SESSION['fs_question'] ?? '';
        if ($question === '' || $reste === '') {
            return $this->texte("apprendre", "Lance d'abord une question avec /quiz, puis réponds avec /reponse suivi de ta réponse.");
        }

        $attendu = (string) ($_SESSION['fs_attendu'] ?? '');
        $_SESSION['fs_total'] = (int) ($_SESSION['fs_total'] ?? 0) + 1;

        $correct = false;
        $explication = '';

        $consigne = "Question : $question\nRéponse attendue : $attendu\nRéponse de l'étudiant : $reste\n\nJuge si la réponse de l'étudiant est correcte ou équivalente.\nRéponds UNIQUEMENT en JSON : {\"correct\":true|false,\"explication\":\"une phrase courte en français simple\"}";
        $juge = $this->gemini->genererJson($consigne);

        if ($juge !== null) {
            $correct     = !empty($juge['correct']);
            $explication = trim((string) ($juge['explication'] ?? ''));
        } else {
            similar_text(mb_strtolower($reste), mb_strtolower($attendu), $pct);
            $correct = $pct >= 40;
            $explication = $correct
                ? "Réponse proche de la réponse attendue : $attendu"
                : "Réponse attendue : $attendu";
        }

        if ($correct) {
            $_SESSION['fs_score'] = (int) ($_SESSION['fs_score'] ?? 0) + 1;
        }

        $score = (int) ($_SESSION['fs_score'] ?? 0);
        $total = (int) ($_SESSION['fs_total'] ?? 0);

        $_SESSION['fs_question'] = null;
        $_SESSION['fs_attendu']  = null;

        $texte = ($correct ? "Correct ! " : "Pas tout à fait. ") . $explication
               . "\nScore : $score / $total."
               . "\nTape /quiz pour une nouvelle question, ou /stop pour revenir au scanner.";

        return $this->texte("apprendre", $texte);
    }

    private function cmdStop(): array
    {
        unset($_SESSION['fs_cours'], $_SESSION['fs_question'], $_SESSION['fs_attendu']);
        return $this->texte("scanner", "Mode apprentissage arrêté. Je reviens au scanner d'arnaques. Colle un SMS ou une capture pour l'analyser.");
    }

    private function aide(): array
    {
        return $this->texte("apprendre", "Commande inconnue. Voici les commandes disponibles :\n/apprendre [cours] - envoie ton cours (texte ou photo)\n/resume - résume le cours\n/plan [jours] - programme de révision\n/quiz - pose une question\n/reponse ... - réponds à la question\n/stop - revient au scanner");
    }

    // ---------- Outils ----------
    private function cours(): string
    {
        return (string) ($_SESSION['fs_cours'] ?? '');
    }

    private function tailleCours(): string
    {
        $n = mb_strlen($this->cours());
        return $n > 1000 ? round($n / 1000, 1) . " mille caractères" : "$n caractères";
    }

    private function motsCles(string $texte, int $max): array
    {
        $stop = ['dans', 'pour', 'avec', 'cette', 'comme', 'entre', 'leurs', 'mais', 'plus', 'être', 'avoir', 'fait', 'faut', 'leur', 'elles', 'cours'];
        preg_match_all('/[\p{L}]{5,}/u', mb_strtolower($texte), $m);
        $freq = array_count_values($m[0]);
        foreach ($stop as $s) {
            unset($freq[$s]);
        }
        arsort($freq);
        return array_slice(array_keys($freq), 0, $max);
    }

    private function texte(string $mode, string $texte): array
    {
        return ['type' => 'texte', 'mode' => $mode, 'texte' => $texte];
    }
}
