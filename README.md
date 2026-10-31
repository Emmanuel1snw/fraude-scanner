# Hackathon « Build with Gemma » — Scanner de SMS Mobile Money

Hacktoberfest Hack Day Lomé × LBS CS Club · 31 octobre 2026 · Lomé Business School

## Le problème

Au Togo, le Mobile Money est utilisé partout, et les arnaques par SMS et appels falsifiés sont quotidiennes : « tu as reçu un dépôt par erreur, renvoie-moi… », faux concours, fausses tontines.

## Votre mission

Construire avec **Gemma** un bot (Web ou WhatsApp) qui permet à n'importe qui de savoir en quelques secondes si un SMS Mobile Money est authentique ou s'il s'agit d'une arnaque.

Le bot doit :
1. **Recevoir** la capture d'écran ou le texte d'un SMS Mobile Money suspect.
2. **Analyser** : extraire les infos clés (montant, expéditeur, syntaxe) et vérifier la conformité avec les vrais formats de SMS officiels (T-Money / Moov Money).
3. **Alerter** : répondre clairement, en français simple, si le message est légitime ou une tentative d'escroquerie.

## Règles (équipes de 2 à 4 personnes)

1. Choisir un canal : Web ou WhatsApp.
2. Gemma est essentielle : le projet ne doit pas pouvoir marcher sans elle.
3. Alerte en français simple (bonus : une langue locale, éwé ou kabiyè).
4. Démo en direct de 3 minutes avec de vrais exemples de SMS, légitimes et frauduleux.
5. Aucune vraie donnée personnelle ni vrai numéro : messages d'exemple ou anonymisés.
6. Volet « Apprendre » : actif seulement sur commande commençant par « / ».

### Volet « Apprendre » (complémentaire)

L'étudiant téléverse son cours (PDF, photo ou texte) et révise. Sans commande, le bot reste en mode scanner.

| Commande | Rôle |
| --- | --- |
| `/apprendre` | active le mode apprentissage et invite à téléverser le cours |
| `/plan` | plan d'apprentissage sur plusieurs jours selon la date d'examen |
| `/quiz` | questions sur le cours, avec correction |
| `/resume` | résumé ou fiches de révision |
| `/stop` | retour au scanner d'arnaques |

## Notation (5 critères × 4 points = 20)

1. Compréhension du problème et du public
2. Innovation et originalité
3. Usage de Gemma
4. Démo et qualité technique
5. Impact, faisabilité et respect des contraintes

Départage : critère 2 (innovation), puis critère 4 (démo).

## Planning

| Heure (GMT) | |
| --- | --- |
| 10h30 | début du code |
| 12h30 | déjeuner |
| 13h30 | reprise avec mentors |
| 15h45 | fin du code, préparation du pitch |
| 16h00 | présentations de 3 minutes |
| 17h00 | remise des prix |

## Votre repo

- Ce repo est **privé** et visible uniquement par votre équipe et les organisateurs.
- Commitez **souvent** et avec des messages clairs : un classement des équipes par activité s'affiche sur le wall de l'événement. Les merges, les commits vides et plus de 3 commits par minute ne sont pas comptés.
- Poussez sur la branche `main` (le wall lit la branche par défaut).
- **Ne commitez jamais de clé d'API.** Copiez `.env.example` en `.env`, mettez-y votre `GEMINI_API_KEY`, et gardez `.env` hors de git (déjà dans `.gitignore`).

## Démarrer

Le projet est une application Web PHP 8 (extensions `curl` et `fileinfo`).

```bash
cp .env.example .env
# renseigner les clés nécessaires dans .env
php -S localhost:8000 -t public
```

Ouvre ensuite http://localhost:8000.

L'application permet de coller un SMS ou d'envoyer une capture d'écran, puis
affiche une estimation du risque avec des explications en français simple. Le
mode apprentissage s'active uniquement avec une commande commençant par `/`.

## Configuration

| Variable | Rôle |
| --- | --- |
| `LLM_PROVIDER` | `google` ou `groq` |
| `GEMINI_API_KEY` | Clé Google AI Studio si le fournisseur est `google` |
| `GEMMA_MODEL` | Modèle Gemma utilisé avec Google |
| `GROQ_API_KEY` | Clé Groq si le fournisseur est `groq` |
| `GROQ_MODEL` | Modèle utilisé avec Groq |
| `SUPABASE_URL` | URL du projet Supabase (synchronisation facultative) |
| `SUPABASE_PUBLISHABLE_KEY` | Clé publishable Supabase (facultative) |

Ne publie jamais le fichier `.env` ou une clé `secret` / `service_role`
Supabase. La synchronisation Supabase nécessite aussi une configuration RLS.

## Déploiement sur Vercel

Le projet utilise le `Dockerfile` FrankenPHP à la racine. Garde la racine du
dépôt comme **Root Directory**, configure les variables d'environnement dont
l'application a besoin dans Vercel et redéploie après leur modification.

Le résultat du scanner est une estimation, pas une garantie. En cas de doute,
vérifie toujours dans l'application officielle de ton opérateur.

## Structure

```text
public/          pages Web et API
partials/        en-tête et pied de page partagés
src/             services Gemini, scanner et apprentissage
data/            formats officiels et signes d'arnaque
.env.example     modèle de configuration sans secrets
```
