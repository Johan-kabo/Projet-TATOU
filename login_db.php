<?php
// Démarrer la session
session_start();

// Inclure la connexion DB
include 'db/mysql_connection_gestion_inscription.php';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validation des entrées
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        try {
            // Recherche de l'utilisateur dans la base de données
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Mise à jour de la dernière connexion
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Cookie "Se souvenir de moi" (30 jours)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (30 * 24 * 60 * 60);
                    
                    // Sauvegarder le token en base (optionnel)
                    setcookie('remember_token', $token, $expiry, '/', '', false, true);
                }
                
                // Redirection vers le tableau de bord
                header('Location: dashboard.php');
                exit;
                
            } else {
                $error = "Email ou mot de passe incorrect";
            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion. Veuillez réessayer.";
        }
    }
}

// Si déjà connecté, rediriger vers le dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>TAAJ Corp – Connexion</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
  :root {
    --accent: #1B4FA0 ;
    --accent-hover: #0c326e ;
    --text-primary: #0F172A;
    --text-muted: #64748B;
    --border: #E2E8F0;
    --page-bg: #F8FAFC;
    --font: 'Plus Jakarta Sans', sans-serif;
  }
  html, body {
    height: 100%; font-family: var(--font);
    background: var(--page-bg); color: var(--text-primary);
  }

  .page { display: flex; min-height: 100vh; }

  /* ── LEFT: FORM ── */
  .left {
    width: 50%; display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    padding: 48px 60px; background: #fff;
    position: relative; z-index: 1;
  }

  .form-wrap { width: 100%; max-width: 420px; }

  .brand { display: flex; align-items: center; justify-content: space-between; margin-bottom: 36px; }
  .brand-left { display: flex; align-items: center; gap: 12px; }
  .brand-icon {
    width: 44px; height: 44px; background: var(--accent);
    border-radius: 12px; display: flex; align-items: center;
    justify-content: center; font-size: 20px; font-weight: 800; color: #fff;
    flex-shrink: 0;
  }
  .brand-name { font-size: 18px; font-weight: 800; color: var(--text-primary); }
  .ecole-logo { width: 80px; height: 80px; }

  .form-title { font-size: 28px; font-weight: 800; color: var(--text-primary); line-height: 1.2; margin-bottom: 8px; }
  .form-sub { font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 36px; }

  .form-group { margin-bottom: 20px; }
  .form-label-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
  .form-label { font-size: 13.5px; font-weight: 600; color: var(--text-primary); }
  .forgot-link { font-size: 13px; font-weight: 600; color: var(--accent); text-decoration: none; }
  .forgot-link:hover { color: var(--accent-hover); }

  .input-wrap { position: relative; }
  .form-input {
    width: 100%; font-family: var(--font); font-size: 14px;
    color: var(--text-primary); background: #F8FAFC;
    border: 1.5px solid var(--border); border-radius: 12px;
    padding: 13px 16px; outline: none;
    transition: border-color .2s, background .2s;
  }
  .form-input:focus { border-color: var(--accent); background: #fff; }
  .form-input::placeholder { color: #B0BCCC; }

  .eye-btn {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    background: transparent; border: none; cursor: pointer; color: var(--text-muted);
    display: flex; align-items: center;
  }
  .eye-btn:hover { color: var(--text-primary); }

  .remember-row { display: flex; align-items: center; gap: 9px; margin-bottom: 24px; }
  .checkbox {
    width: 18px; height: 18px; border: 1.5px solid var(--border);
    border-radius: 5px; cursor: pointer; appearance: none;
    background: #fff; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; position: relative; transition: all .15s;
  }
  .checkbox:checked { background: var(--accent); border-color: var(--accent); }
  .checkbox:checked::after { content:''; position:absolute; width:5px; height:9px; border:2px solid #fff; border-left:none; border-top:none; transform:rotate(45deg) translate(-1px,-1px); }
  .remember-label { font-size: 13.5px; color: var(--text-muted); font-weight: 500; cursor: pointer; }

  .btn-login {
    width: 100%; background: var(--accent); color: #fff;
    border: none; border-radius: 12px; padding: 14px;
    font-size: 15px; font-weight: 700; font-family: var(--font);
    cursor: pointer; transition: background .18s, transform .1s;
    letter-spacing: 0.2px;
  }
  .btn-login:hover { background: var(--accent-hover); }
  .btn-login:active { transform: scale(0.99); }
  .btn-login:disabled { background: #94A3B8; cursor: not-allowed; transform: none; }

  .error-message {
    background: #FEE2E2; border: 1px solid #FECACA;
    border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;
    color: #991B1B; font-size: 13px; font-weight: 500;
    display: flex; align-items: center; gap: 8px;
  }
  .success-message {
    background: #D1FAE5; border: 1px solid #A7F3D0;
    border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;
    color: #065F46; font-size: 13px; font-weight: 500;
    display: flex; align-items: center; gap: 8px;
  }

  .divider { display: flex; align-items: center; gap: 14px; margin: 22px 0; }
  .divider-line { flex: 1; height: 1px; background: var(--border); }
  .divider-text { font-size: 12px; font-weight: 600; color: var(--text-muted); letter-spacing: 0.8px; white-space: nowrap; }

  .btn-google {
    width: 100%; background: #fff; border: 1.5px solid var(--border);
    border-radius: 12px; padding: 13px; font-size: 14px; font-weight: 600;
    font-family: var(--font); color: var(--text-primary); cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    transition: background .15s, border-color .15s;
  }
  .btn-google:hover { background: #F8FAFC; border-color: #CBD5E1; }

  .google-icon { width: 20px; height: 20px; }

  .signup-row { text-align: center; margin-top: 24px; font-size: 13.5px; color: var(--text-muted); }
  .signup-link { color: var(--accent); font-weight: 700; text-decoration: none; }
  .signup-link:hover { color: var(--accent-hover); }

  /* ── RIGHT: IMAGE PANEL ── */
  .right {
    width: 50%; position: relative; overflow: hidden;
    background: #0F1623;
  }

  .right-bg {
    position: absolute; inset: 0;
    background:
      linear-gradient(160deg, rgba(15,22,35,0.15) 0%, rgba(15,22,35,0.75) 100%);
    z-index: 2;
  }

  /* SVG campus illustration as background */
  .campus-svg {
    position: absolute; inset: 0; width: 100%; height: 100%;
    object-fit: cover; z-index: 1;
  }

  .right-content {
    position: absolute; bottom: 0; left: 0; right: 0;
    padding: 40px 44px; z-index: 3;
    background: linear-gradient(to top, rgba(15,22,35,0.95) 0%, transparent 100%);
  }

  .quote-text {
    font-size: 20px; font-weight: 700; color: #fff;
    line-height: 1.55; font-style: italic; margin-bottom: 14px;
  }
  .quote-author { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.65); }
  .quote-role   { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 2px; }

  .right-stats {
    display: flex; gap: 28px; margin-top: 28px;
    padding-top: 22px; border-top: 1px solid rgba(255,255,255,0.12);
  }
  .rs-item { text-align: center; }
  .rs-val { font-size: 22px; font-weight: 800; color: var(--accent); }
  .rs-lbl { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 2px; font-weight: 500; }

  /* top badge */
  .right-top {
    position: absolute; top: 28px; left: 40px; right: 40px;
    z-index: 3; display: flex; align-items: center; justify-content: space-between;
  }
  .right-badge {
    background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.15); border-radius: 30px;
    padding: 8px 16px; display: flex; align-items: center; gap: 8px;
  }
  .badge-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green, #10B981); animation: pulse 2s infinite; }
  .badge-text { font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.85); }
  @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(1.3)} }

  /* floating card */
  .float-card {
    position: absolute; top: 38%; right: 36px; z-index: 3;
    background: rgba(255,255,255,0.12); backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,0.2); border-radius: 14px;
    padding: 14px 18px; animation: floatAnim 4s ease-in-out infinite;
  }
  @keyframes floatAnim { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
  .fc-label { font-size:11px; color:rgba(255,255,255,0.6); font-weight:500; margin-bottom:4px; }
  .fc-val   { font-size:20px; font-weight:800; color:#fff; }
  .fc-trend { font-size:11px; font-weight:600; color:#10B981; margin-top:2px; }

  /* ANIMATIONS */
  @keyframes fadeIn { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
  .form-wrap { animation: fadeIn .5s ease both; }
  .right-content { animation: fadeIn .6s .1s ease both; }

  /* RESPONSIVE */
  @media (max-width: 768px) {
    .right { display: none; }
    .left  { width: 100%; padding: 32px 24px; }
  }
</style>
</head>
<body>
<div class="page">

  <!-- LEFT: FORM -->
  <div class="left">
    <div class="form-wrap">

      <!-- BRAND -->
      <div class="brand">
        <div class="brand-left">
          <div class="brand-icon">T</div>
          <span class="brand-name">TAAJ Corp</span>
        </div>
        <img src="assets/images/logo IME.png" alt="IME Business and Engineering School" class="ecole-logo" onerror="this.style.display='none';">
      </div>

      <h1 class="form-title">Gestion Des Inscriptions</h1>
      <p class="form-sub">Bienvenue sur votre plateforme de gestion des inscriptions académiques.<br>Connectez-vous pour accéder à votre tableau de bord.</p>

      <!-- FORMULAIRE DE CONNEXION -->
      <form method="POST" action="">
        <?php if (isset($error)): ?>
          <div class="error-message">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <!-- EMAIL -->
        <div class="form-group">
          <label class="form-label">Adresse Email</label>
          <div class="input-wrap">
            <input class="form-input" type="email" name="email" placeholder="nom@universite.edu" id="emailInput" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required />
          </div>
        </div>

        <!-- PASSWORD -->
        <div class="form-group">
          <label class="form-label">Mot de passe</label>
          <div class="input-wrap">
            <input class="form-input" type="password" name="password" placeholder="••••••" id="pwdInput" style="padding-right:46px;" required />
            <button type="button" class="eye-btn" onclick="togglePwd()" id="eyeBtn">
              <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <!-- REMEMBER -->
        <div class="remember-row">
          <input type="checkbox" class="checkbox" id="remember" name="remember" />
          <label class="remember-label" for="remember">Se souvenir de moi</label>
        </div>

        <!-- SUBMIT -->
        <button type="submit" class="btn-login" name="login">Se connecter</button>
      </form>
    </div>
  </div>

  <!-- RIGHT: IMAGE PANEL -->
  <div class="right">

    <!-- SVG Campus Scene -->
    <svg class="campus-svg" viewBox="0 0 800 900" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
      <!-- Sky gradient -->
      <defs>
        <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#1a2744"/>
          <stop offset="60%" stop-color="#2d4a7a"/>
          <stop offset="100%" stop-color="#1e3a5f"/>
        </linearGradient>
        <linearGradient id="ground" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#1a2f1a"/>
          <stop offset="100%" stop-color="#0d1a0d"/>
        </linearGradient>
        <linearGradient id="win1" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#FCD34D" stop-opacity="0.9"/>
          <stop offset="100%" stop-color="#F59E0B" stop-opacity="0.6"/>
        </linearGradient>
        <radialGradient id="moon" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#FEF9C3"/>
          <stop offset="100%" stop-color="#FCD34D"/>
        </radialGradient>
        <filter id="glow">
          <feGaussianBlur stdDeviation="3" result="blur"/>
          <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
        </filter>
      </defs>

      <!-- Background sky -->
      <rect width="800" height="900" fill="url(#sky)"/>

      <!-- Stars -->
      <circle cx="80" cy="60" r="1.5" fill="#fff" opacity=".8"/>
      <circle cx="200" cy="35" r="1" fill="#fff" opacity=".6"/>
      <circle cx="340" cy="80" r="1.5" fill="#fff" opacity=".9"/>
      <circle cx="450" cy="45" r="1" fill="#fff" opacity=".7"/>
      <circle cx="580" cy="70" r="2" fill="#fff" opacity=".8"/>
      <circle cx="650" cy="30" r="1" fill="#fff" opacity=".5"/>
      <circle cx="720" cy="90" r="1.5" fill="#fff" opacity=".7"/>
      <circle cx="130" cy="120" r="1" fill="#fff" opacity=".6"/>
      <circle cx="480" cy="100" r="1" fill="#fff" opacity=".5"/>
      <circle cx="260" cy="55" r="1.5" fill="#fff" opacity=".7"/>

      <!-- Moon -->
      <circle cx="680" cy="80" r="30" fill="url(#moon)" opacity=".9" filter="url(#glow)"/>
      <circle cx="692" cy="70" r="22" fill="#2d4a7a" opacity=".3"/>

      <!-- Clouds -->
      <ellipse cx="150" cy="130" rx="80" ry="20" fill="#2a3f6b" opacity=".5"/>
      <ellipse cx="120" cy="125" rx="50" ry="18" fill="#2a3f6b" opacity=".4"/>
      <ellipse cx="600" cy="110" rx="90" ry="22" fill="#1e3560" opacity=".5"/>

      <!-- Ground -->
      <rect x="0" y="700" width="800" height="200" fill="url(#ground)"/>
      <rect x="0" y="695" width="800" height="12" fill="#1a3a1a" opacity=".8"/>

      <!-- Path / walkway -->
      <polygon points="360,700 440,700 500,900 300,900" fill="#2a2a1a" opacity=".6"/>
      <line x1="400" y1="700" x2="400" y2="900" stroke="#3a3a2a" stroke-width="3" opacity=".4"/>

      <!-- Left tree -->
      <rect x="95" y="600" width="14" height="110" fill="#3d2b1a"/>
      <ellipse cx="102" cy="580" rx="45" ry="60" fill="#1a4a1a"/>
      <ellipse cx="88" cy="600" rx="32" ry="45" fill="#1e551e"/>
      <ellipse cx="116" cy="595" rx="35" ry="48" fill="#165a16"/>

      <!-- Right tree -->
      <rect x="670" y="610" width="12" height="100" fill="#3d2b1a"/>
      <ellipse cx="676" cy="590" rx="40" ry="55" fill="#1a4a1a"/>
      <ellipse cx="660" cy="608" rx="30" ry="42" fill="#1e551e"/>
      <ellipse cx="692" cy="604" rx="32" ry="45" fill="#165a16"/>

      <!-- ── MAIN BUILDING ── -->
      <!-- Base -->
      <rect x="160" y="380" width="480" height="330" fill="#1a2540"/>
      <!-- Facade texture -->
      <rect x="160" y="380" width="480" height="330" fill="#203060" opacity=".5"/>

      <!-- Columns -->
      <rect x="185" y="420" width="18" height="290" fill="#253570"/>
      <rect x="240" y="420" width="18" height="290" fill="#253570"/>
      <rect x="295" y="420" width="18" height="290" fill="#253570"/>
      <rect x="350" y="420" width="18" height="290" fill="#253570"/>
      <rect x="405" y="420" width="18" height="290" fill="#253570"/>
      <rect x="460" y="420" width="18" height="290" fill="#253570"/>
      <rect x="515" y="420" width="18" height="290" fill="#253570"/>
      <rect x="570" y="420" width="18" height="290" fill="#253570"/>

      <!-- Entablature / top band -->
      <rect x="155" y="375" width="490" height="50" fill="#1e2e58"/>
      <rect x="155" y="365" width="490" height="12" fill="#253575"/>

      <!-- Pediment triangle -->
      <polygon points="400,290 160,375 640,375" fill="#1a2548"/>
      <polygon points="400,300 170,372 630,372" fill="#203060"/>

      <!-- Pediment decoration -->
      <text x="400" y="345" font-family="serif" font-size="28" fill="#F59E0B" text-anchor="middle" opacity=".8">✦ TAAJ CORP ✦</text>

      <!-- Dome -->
      <ellipse cx="400" cy="290" rx="70" ry="35" fill="#162040"/>
      <ellipse cx="400" cy="272" rx="58" ry="50" fill="#1a2848"/>
      <ellipse cx="400" cy="255" rx="42" ry="38" fill="#1e3060"/>
      <!-- Dome windows -->
      <ellipse cx="380" cy="262" rx="8" ry="12" fill="url(#win1)" opacity=".7"/>
      <ellipse cx="400" cy="258" rx="8" ry="12" fill="url(#win1)" opacity=".9"/>
      <ellipse cx="420" cy="262" rx="8" ry="12" fill="url(#win1)" opacity=".7"/>
      <!-- Flagpole -->
      <line x1="400" y1="218" x2="400" y2="260" stroke="#8a9ab0" stroke-width="3"/>
      <polygon points="400,218 430,228 400,238" fill="#F59E0B"/>

      <!-- Main entrance door -->
      <rect x="355" y="570" width="90" height="140" rx="45" ry="12" fill="#0d1528"/>
      <rect x="363" y="572" width="74" height="130" rx="37" ry="10" fill="#111e38"/>
      <!-- Door frame -->
      <path d="M355,620 Q355,570 400,570 Q445,570 445,620 L445,710 L355,710 Z" fill="none" stroke="#F59E0B" stroke-width="2" opacity=".6"/>
      <!-- Door handle -->
      <circle cx="393" cy="648" r="4" fill="#F59E0B" opacity=".8"/>
      <circle cx="407" cy="648" r="4" fill="#F59E0B" opacity=".8"/>

      <!-- Windows Row 1 -->
      <rect x="182" y="430" width="50" height="70" rx="4" fill="#0d1528"/>
      <rect x="185" y="433" width="44" height="64" rx="3" fill="url(#win1)" opacity=".85"/>
      <line x1="207" y1="433" x2="207" y2="497" stroke="#1a2548" stroke-width="1.5"/>
      <line x1="185" y1="465" x2="229" y2="465" stroke="#1a2548" stroke-width="1.5"/>

      <rect x="257" y="430" width="50" height="70" rx="4" fill="#0d1528"/>
      <rect x="260" y="433" width="44" height="64" rx="3" fill="url(#win1)" opacity=".7"/>
      <line x1="282" y1="433" x2="282" y2="497" stroke="#1a2548" stroke-width="1.5"/>
      <line x1="260" y1="465" x2="304" y2="465" stroke="#1a2548" stroke-width="1.5"/>

      <rect x="493" y="430" width="50" height="70" rx="4" fill="#0d1528"/>
      <rect x="496" y="433" width="44" height="64" rx="3" fill="url(#win1)" opacity=".9"/>
      <line x1="518" y1="433" x2="518" y2="497" stroke="#1a2548" stroke-width="1.5"/>
      <line x1="496" y1="465" x2="540" y2="465" stroke="#1a2548" stroke-width="1.5"/>

      <rect x="568" y="430" width="50" height="70" rx="4" fill="#0d1528"/>
      <rect x="571" y="433" width="44" height="64" rx="3" fill="url(#win1)" opacity=".75"/>
      <line x1="593" y1="433" x2="593" y2="497" stroke="#1a2548" stroke-width="1.5"/>
      <line x1="571" y1="465" x2="615" y2="465" stroke="#1a2548" stroke-width="1.5"/>

      <!-- Windows Row 2 -->
      <rect x="182" y="530" width="50" height="60" rx="4" fill="#0d1528"/>
      <rect x="185" y="533" width="44" height="54" rx="3" fill="url(#win1)" opacity=".6"/>
      <line x1="207" y1="533" x2="207" y2="587" stroke="#1a2548" stroke-width="1.5"/>

      <rect x="257" y="530" width="50" height="60" rx="4" fill="#0d1528"/>
      <rect x="260" y="533" width="44" height="54" rx="3" fill="url(#win1)" opacity=".8"/>
      <line x1="282" y1="533" x2="282" y2="587" stroke="#1a2548" stroke-width="1.5"/>

      <rect x="493" y="530" width="50" height="60" rx="4" fill="#0d1528"/>
      <rect x="496" y="533" width="44" height="54" rx="3" fill="url(#win1)" opacity=".65"/>
      <line x1="518" y1="533" x2="518" y2="587" stroke="#1a2548" stroke-width="1.5"/>

      <rect x="568" y="530" width="50" height="60" rx="4" fill="#0d1528"/>
      <rect x="571" y="533" width="44" height="54" rx="3" fill="url(#win1)" opacity=".85"/>
      <line x1="593" y1="533" x2="593" y2="587" stroke="#1a2548" stroke-width="1.5"/>

      <!-- Side wings -->
      <rect x="80" y="480" width="80" height="230" fill="#162038"/>
      <rect x="640" y="480" width="80" height="230" fill="#162038"/>
      <!-- Wing windows -->
      <rect x="92" y="510" width="30" height="40" rx="3" fill="url(#win1)" opacity=".7"/>
      <rect x="92" y="570" width="30" height="40" rx="3" fill="url(#win1)" opacity=".5"/>
      <rect x="92" y="630" width="30" height="40" rx="3" fill="url(#win1)" opacity=".8"/>
      <rect x="655" y="510" width="30" height="40" rx="3" fill="url(#win1)" opacity=".6"/>
      <rect x="655" y="570" width="30" height="40" rx="3" fill="url(#win1)" opacity=".9"/>
      <rect x="655" y="630" width="30" height="40" rx="3" fill="url(#win1)" opacity=".5"/>

      <!-- Steps -->
      <rect x="300" y="700" width="200" height="12" fill="#1e3060" rx="2"/>
      <rect x="285" y="708" width="230" height="12" fill="#1a2850" rx="2"/>
      <rect x="270" y="716" width="260" height="12" fill="#162040" rx="2"/>

      <!-- Lamp posts -->
      <line x1="230" y1="700" x2="230" y2="640" stroke="#5a6a80" stroke-width="4"/>
      <circle cx="230" cy="635" r="10" fill="#FCD34D" opacity=".9" filter="url(#glow)"/>
      <ellipse cx="230" cy="700" rx="12" ry="4" fill="#3a4a60"/>

      <line x1="570" y1="700" x2="570" y2="640" stroke="#5a6a80" stroke-width="4"/>
      <circle cx="570" cy="635" r="10" fill="#FCD34D" opacity=".9" filter="url(#glow)"/>
      <ellipse cx="570" cy="700" rx="12" ry="4" fill="#3a4a60"/>

      <!-- Foreground bushes -->
      <ellipse cx="180" cy="700" rx="55" ry="22" fill="#143014"/>
      <ellipse cx="620" cy="700" rx="55" ry="22" fill="#143014"/>
      <ellipse cx="155" cy="695" rx="38" ry="18" fill="#1a401a"/>
      <ellipse cx="645" cy="695" rx="38" ry="18" fill="#1a401a"/>

      <!-- Subtle light beam from windows -->
      <polygon points="207,497 195,700 219,700" fill="#FCD34D" opacity=".04"/>
      <polygon points="518,497 506,700 530,700" fill="#FCD34D" opacity=".04"/>
    </svg>

    <div class="right-bg"></div>

    <!-- Top badge -->
    <div class="right-top">
      <div class="right-badge">
        <div class="badge-dot" style="background:#10B981;"></div>
        <span class="badge-text">Plateforme en Intranet</span>
      </div>
    </div>

    <!-- Floating stat card -->
    <div class="float-card">
      <div class="fc-label">Étudiants actifs</div>
      <div class="fc-val">124</div>
      <div class="fc-trend">↗ +12% ce mois</div>
    </div>

    <!-- Bottom content -->
    <div class="right-content">
      <p class="quote-text">"L'éducation est l'arme la plus puissante que vous pouvez utiliser pour changer le monde."</p>
      <div class="quote-author">Nelson Mandela</div>
      <div class="quote-role">Prix Nobel de la Paix</div>

      <div class="right-stats">
        <div class="rs-item">
          <div class="rs-val">124</div>
          <div class="rs-lbl">Étudiants</div>
        </div>
        <div class="rs-item">
          <div class="rs-val">7</div>
          <div class="rs-lbl">Programmes</div>
        </div>
        <div class="rs-item">
          <div class="rs-val">87%</div>
          <div class="rs-lbl">Taux de réussite</div>
        </div>
        <div class="rs-item">
          <div class="rs-val">3+</div>
          <div class="rs-lbl">Années d'expérience</div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  function togglePwd() {
    const inp = document.getElementById('pwdInput');
    const icon = document.getElementById('eyeIcon');
    if(inp.type === 'password') {
      inp.type = 'text';
      icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
      inp.type = 'password';
      icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    }
  }

  // Auto-focus sur le premier champ vide
  document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('emailInput');
    const pwdInput = document.getElementById('pwdInput');
    
    if (!emailInput.value) {
      emailInput.focus();
    } else if (!pwdInput.value) {
      pwdInput.focus();
    }
  });
</script>
</body>
</html>
