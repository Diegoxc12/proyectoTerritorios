<?php
session_start();
include './includes/conexion.php';

// Redirigir si ya está logueado

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validatePasswordInput($password) {
    return strlen($password) >= 4;
}

$loginError = '';
$loginSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordInput = sanitizeInput($_POST['password']);

    if (empty($passwordInput)) {
        $loginError = 'La contraseña es requerida';
    } elseif (!validatePasswordInput($passwordInput)) {
        $loginError = 'La contraseña debe tener al menos 4 caracteres.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, rol_usuario FROM usuarios WHERE contraseña = ?");
            $stmt->execute([$passwordInput]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Guardar sesión
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['rol_usuario'] = $user['rol_usuario'];
                $loginSuccess = true;

                // Redireccionar después de un pequeño retardo
                header("refresh:1.5;url=dashboard.php");
            } else {
                $loginError = 'Contraseña incorrecta';
            }
        } catch (PDOException $e) {
            error_log("Error de base de datos: " . $e->getMessage());
            $loginError = 'Error al conectar con la base de datos';
        } catch (Exception $e) {
            error_log("Error general: " . $e->getMessage());
            $loginError = 'Error inesperado';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Totoranorte</title>
    <style>
    body {
        margin: 0;
        padding: 0;
        background: linear-gradient(135deg, #0F1435 0%, #1a2951 100%);
        font-family: Helvetica, sans-serif;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-container {
        background-color: #ffffff;
        border-radius: 15px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        padding: 40px 30px;
        width: 100%;
        max-width: 400px;
        margin: 20px;
        position: relative;
        overflow: hidden;
    }

    .login-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #6C7EF4, #0F1435);
    }

    .logo-section {
        text-align: center;
        margin-bottom: 40px;
    }

    .logo-section img {
        width: 60px;
        height: auto;
        margin-bottom: 15px;
    }

    .logo-section h1 {
        color: #0F1435;
        font-size: 2.2rem;
        font-weight: 300;
        margin: 0;
        letter-spacing: -0.5px;
    }

    .login-subtitle {
        color: #8B8B8B;
        font-size: 1rem;
        margin-bottom: 30px;
        text-align: center;
    }

    .form-group {
        margin-bottom: 25px;
        position: relative;
    }

    .form-group label {
        display: block;
        color: #0F1435;
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .form-group input {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #E6E5E5;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: #fafafa;
        box-sizing: border-box;
    }

    .form-group input:focus {
        outline: none;
        border-color: #6C7EF4;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(108, 126, 244, 0.1);
    }

    .password-container {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #8B8B8B;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 5px;
        transition: color 0.3s ease;
    }

    .password-toggle:hover {
        color: #6C7EF4;
    }

    .login-button {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #6C7EF4, #0F1435);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .login-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(108, 126, 244, 0.3);
    }

    .login-button:active {
        transform: translateY(0);
    }

    .login-button:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .error-message {
        background-color: #ffebee;
        color: #c62828;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        border-left: 4px solid #c62828;
        display: <?php echo $loginError ? 'block' : 'none'; ?>;
    }

    .success-message {
        background-color: #e8f5e8;
        color: #2e7d32;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        border-left: 4px solid #2e7d32;
        display: <?php echo $loginSuccess ? 'block' : 'none'; ?>;
    }

    .loading {
        opacity: 0.7;
        pointer-events: none;
    }

    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 2px solid transparent;
        border-top: 2px solid #ffffff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Responsive Design */

    /* Tablets */
    @media (min-width: 768px) {
        .login-container {
            padding: 50px 40px;
            max-width: 450px;
        }
        
        .logo-section h1 {
            font-size: 2.5rem;
        }
        
        .login-subtitle {
            font-size: 1.1rem;
        }
        
        .form-group input {
            padding: 18px 25px;
            font-size: 1.05rem;
        }
        
        .login-button {
            padding: 18px;
            font-size: 1.15rem;
        }
    }

    /* Desktop y pantallas grandes */
    @media (min-width: 1024px) {
        .login-container {
            padding: 60px 50px;
            max-width: 500px;
        }
        
        .logo-section h1 {
            font-size: 2.8rem;
        }
        
        .login-subtitle {
            font-size: 1.2rem;
            margin-bottom: 40px;
        }
        
        .form-group {
            margin-bottom: 30px;
        }
        
        .form-group input {
            padding: 20px 25px;
            font-size: 1.1rem;
        }
        
        .login-button {
            padding: 20px;
            font-size: 1.2rem;
        }
    }


    /* Pantallas muy pequeñas */
    @media (max-width: 480px) {
        .login-container {
            margin: 10px;
            padding: 30px 20px;
        }
        
        .logo-section h1 {
            font-size: 1.8rem;
        }
        
        .login-subtitle {
            font-size: 0.9rem;
        }
        
        .form-group input {
            padding: 12px 15px;
        }
        
        .login-button {
            padding: 12px;
            font-size: 1rem;
        }
    }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <h1>Totoranorte</h1>
        </div>
        
        <p class="login-subtitle">Iniciar Sesión</p>
        
        <?php if ($loginError): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($loginError); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($loginSuccess): ?>
        <div class="success-message">
            Inicio de sesión exitoso. Redirigiendo...
        </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required 
                            <?php echo $loginSuccess ? 'disabled' : ''; ?>>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        
                    </button>
                </div>
            </div>
            
            <button type="submit" class="login-button" id="loginButton" 
                    <?php echo $loginSuccess ? 'disabled' : ''; ?>>
                <?php echo $loginSuccess ? 'Redirigiendo...' : 'Ingresar'; ?>
            </button>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';

            } else {
                passwordInput.type = 'password';

            }
        }

        // Agregar loading al enviar formulario
        document.getElementById('loginForm').addEventListener('submit', function() {
            const button = document.getElementById('loginButton');
            button.classList.add('loading');
            button.disabled = true;
            button.textContent = 'Verificando...';
        });

        // Auto-focus en el campo de contraseña
        document.getElementById('password').focus();
    </script>
</body>
</html>