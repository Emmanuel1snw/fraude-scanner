<?php
// src/GeminiService.php
// Appelle Gemma, soit via l'API Google (generativelanguage), soit via Groq.
// La clé et le modèle viennent du fichier .env, jamais du code.

class GeminiService
{
    private string $fournisseur;
    private string $cleGoogle;
    private string $cleGroq;
    private string $modeleGoogle;
    private string $modeleGroq;

    public function __construct(?string $fichierEnv = null)
    {
        $env = self::lireEnv($fichierEnv ?? __DIR__ . '/../.env');
        $this->fournisseur  = $env['LLM_PROVIDER']   ?? 'google';   // google ou groq
        $this->cleGoogle    = $env['GEMINI_API_KEY'] ?? '';
        $this->cleGroq      = $env['GROQ_API_KEY']   ?? '';
        $this->modeleGoogle = $env['GEMMA_MODEL']    ?? 'gemma-4-26b-a4b-it';
        $this->modeleGroq   = $env['GROQ_MODEL']     ?? 'gemma2-9b-it';
    }

    /**
     * Envoie une consigne (et une image facultative) à Gemma.
     * $image = ['mime' => 'image/png', 'data' => '<contenu binaire>'] (Google seulement).
     * Retourne le texte de la réponse, ou null si l'appel a échoué.
     */
    public function generer(string $consigne, ?array $image = null): ?string
    {
        return $this->fournisseur === 'groq'
            ? $this->appelerGroq($consigne)
            : $this->appelerGoogle($consigne, $image);
    }

    /** Comme generer(), mais décode une réponse JSON. Retourne null si ce n'est pas du JSON valide. */
    public function genererJson(string $consigne, ?array $image = null): ?array
    {
        $texte = $this->generer($consigne, $image);
        if ($texte === null) {
            return null;
        }
        // Gemma entoure parfois le JSON de ```json ... ```
        if (preg_match('/\{.*\}/s', $texte, $m)) {
            $donnees = json_decode($m[0], true);
            return is_array($donnees) ? $donnees : null;
        }
        return null;
    }

    private function appelerGoogle(string $consigne, ?array $image): ?string
    {
        if ($this->cleGoogle === '') {
            return null;
        }
        // Les modèles Gemma n'acceptent pas toujours "systemInstruction" :
        // toute la consigne est donc placée dans le message.
        $parties = [['text' => $consigne]];
        if ($image !== null) {
            $parties[] = ['inlineData' => [
                'mimeType' => $image['mime'],
                'data'     => base64_encode($image['data']),
            ]];
        }
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
             . rawurlencode($this->modeleGoogle) . ':generateContent';

        $reponse = $this->post($url, ['x-goog-api-key: ' . $this->cleGoogle], [
            'contents'         => [['parts' => $parties]],
            'generationConfig' => ['temperature' => 0.2],
        ]);
        return $reponse['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    private function appelerGroq(string $consigne): ?string
    {
        if ($this->cleGroq === '') {
            return null;
        }
        $reponse = $this->post('https://api.groq.com/openai/v1/chat/completions',
            ['Authorization: Bearer ' . $this->cleGroq],
            [
                'model'       => $this->modeleGroq,
                'temperature' => 0.2,
                'messages'    => [['role' => 'user', 'content' => $consigne]],
            ]);
        return $reponse['choices'][0]['message']['content'] ?? null;
    }

    private function post(string $url, array $entetes, array $corps): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $entetes),
            CURLOPT_POSTFIELDS     => json_encode($corps, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 25,
        ]);
        $brut = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($brut === false || $code < 200 || $code >= 300) {
            return null;
        }
        $donnees = json_decode($brut, true);
        return is_array($donnees) ? $donnees : null;
    }

    private static function lireEnv(string $chemin): array
    {
        $env = [];
        if (!is_readable($chemin)) {
            return $env;
        }
        foreach (file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ligne) {
            if ($ligne[0] === '#' || strpos($ligne, '=') === false) {
                continue;
            }
            [$cle, $valeur] = explode('=', $ligne, 2);
            $env[trim($cle)] = trim($valeur, " \t\"'");
        }
        foreach (['LLM_PROVIDER', 'GEMINI_API_KEY', 'GEMMA_MODEL', 'GROQ_API_KEY', 'GROQ_MODEL'] as $cle) {
            $valeur = getenv($cle);
            if ($valeur !== false) {
                $env[$cle] = $valeur;
            }
        }
        return $env;
    }
}