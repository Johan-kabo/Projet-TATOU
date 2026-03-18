<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - TAAJ Corp</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="login-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="icon-graduation-cap"></i>
                    </div>
                    <h1>TAAJ Corp</h1>
                </div>
                <p class="login-subtitle">Connectez-vous à votre espace</p>
            </div>

            <form class="form" method="post" action="dashboard.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="exemple@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="form-options">
                    <label class="checkbox">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        Se souvenir de moi
                    </label>
                    <a href="#" class="forgot-password">Mot de passe oublié?</a>
                </div>

                <button type="submit" class="btn btn--primary btn--full">
                    Se connecter
                </button>
            </form>

            <div class="divider">
                <span>ou</span>
            </div>

            <button class="btn btn--google">
                <i class="icon-google"></i>
                Continuer avec Google
            </button>

            <p class="signup-link">
                Pas encore de compte? <a href="#">Créer un compte</a>
            </p>
        </div>

        <div class="login-image">
            <div class="image-overlay">
                <div class="quote">
                    <blockquote>
                        "L'éducation est l'arme la plus puissante pour changer le monde."
                    </blockquote>
                    <cite>- Nelson Mandela</cite>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
